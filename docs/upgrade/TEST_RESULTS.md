# Test results — Felix 2026-07-06 merge + PHP 8 + Bootstrap 5 (2026-09-16)

Everything below was run against the tip of the work branch on a throw-away stack:

| | |
|---|---|
| PHP | 8.4.19 CLI, built-in web server (`php -S`), `error_reporting=E_ALL`, `log_errors=1` |
| DB | MariaDB 10.11.14 (`root` via `mysql_native_password`) |
| Extras | `PEAR/Exception.php` + `PEAR.php` from pear-core placed on the include path (Felix's `HTTP/Request2` needs a system `php-pear`) |
| Browser | headless Chromium via Playwright 1.56 (console errors, page errors, failed requests captured across all frames) |
| Data | a fresh group DB `testlab` created by root login; users, storage, molecule and container created **through the application**, not by SQL |

The only external resources still referenced are Google Fonts on the barcode-terminal page (blocked by this sandbox's proxy — the `ERR_CERT_AUTHORITY_INVALID` console lines below are that) and outgoing links.

## Static

| Check | Result |
|---|---|
| `php -l` on all 410 PHP files (PHP 8.4) | **1 failure**: `File/Archive/Reader/Uncompress.php` — unused PEAR File_Archive code, present in Felix's release, never included. Everything else parses. Before the work: 15 files failed. |
| `READONLY` (reserved word since 8.1) | 0 occurrences |
| `E_STRICT` (deprecated in 8.4) | 0 occurrences |
| CDN references in live PHP (`stackpath`, `cdnjs`, `code.jquery`, `jsdelivr`, `fontawesome.com`) | 0 |
| Select2 | 0 occurrences (Felix's `jquery.scombobox` used instead) |
| Line endings | index fully LF; `.gitattributes` in place |

## Root / database

| Step | Result |
|---|---|
| Login as `root` with a new DB name | DB `testlab` created, 130 tables, redirected to `main.php` |
| Schema of the created DB | `chemical_storage.disposed_when` **DATETIME** (ours), `person.username` **VARCHAR(32)** (ours), `analytics_device.analytics_device_text` present (Felix 2022) |
| Root pages | `main`, `topnav`, `sidenav`(search/settings), `list`(message_in, chemical_storage, person, storage), `root_db_man`, `g_settings`, `settings`, `perm_settings`, `import`, `import_edit`, `import_only`, `delete_multiple`, `barcode_autogeneration`, `printBarcodeList`, `edit person` — all HTTP 200, `error.log` clean after fixes |
| Predefined permission group **External borrow** | present in `perm_settings.php` |
| `refresh_user.php` (MySQL users from persons) | created `testuser@localhost`, `guests@localhost` |
| **Migration rehearsal**: aged the DB (`disposed_when` → DATE, dropped `analytics_device_text`, stored Version 0.5), then root login | root login redirects to `update.php` (Felix's design: the update is confirmed by a click, not automatic). Dry run re-created the missing column; **perform** (`&update=true`) restored `disposed_when` to DATETIME via our `fixFields.php` ALTER, set Version 0.817, kept all rows, kept MySQL users and their passwords — `testuser` could still log in afterwards. No PHP warnings. |

## Inventory (as `testuser`, full permissions)

| Step | Result |
|---|---|
| Login → frameset (`main`, `sidenav`, `topnav`, `mainpage`) | OK, 0 JS errors |
| Sidenav search box | Felix's `scombobox` initialised on the criteria select; typing filters the list; **runs on jQuery 3.7.1** (Felix shipped 1.12.4; verified and swapped) |
| Search CAS `64-17-5`, search barcode `TEST001` (via list URL) | both return the Ethanol container |
| Topnav (Bootstrap 5) | `nav.navbar` rendered, `window.bootstrap` present, `data-bs-toggle` attributes, burger **collapse toggles** (`.collapse.show`) |
| Add storage / molecule / container via `editAsync.php` (`db_id=-1`) | all three persisted ("Data set added."), including barcode `TEST001`, order date, price/currency |
| Edit form (`edit.php?table=chemical_storage`), list views, storage list, person list | HTTP 200, no PHP warnings after fixes |
| Import pages (`import`, `import_edit`, `import_only`, `delete_multiple`) | render; column lists include Felix's MSDS-URL columns; SimpleXLSX 1.1.19 parses a generated `.xlsx` on PHP 8.4 (`parse()->rows()`) |
| Barcode auto-generation (storage, then user) | "Success", barcodes `92000010`… / `91000019`, `91000026` written; page now starts the session before output (was "headers already sent") |
| Personal settings page | new **Date format** select with the 4 options; global settings page has the same |
| Date display | ISO (`2026-09-01`) with the fork default; `date_format` personal > global > language |

## Barcode terminal (as `testuser`; `barcode_allow_any` + `barcode_ignore_prefix` on, as our help text prescribes for custom barcodes)

| Scan | Result |
|---|---|
| Person barcode `91000019` | `parent.setActivePerson({…testuser…})` |
| Container `TEST001` with person 1 | "Ethanol borrowed.", `borrowed_by_person_id=1` |
| Storage `SHELF-A` with person 1 | `parent.setStorage(1); parent.doInventar()` (our set-storage-for-following-containers path) |
| Container `TEST001`, nobody logged in | auto-login of borrower, "Ethanol returned.", `borrowed_by_person_id=NULL` |
| Container `TEST001` as **guest account** (bit `_BORROW_EXTERNAL`) with borrower info in `history_entry` | "Ethanol borrowed.", history line `Guest Account: Ethanol 500 (100) ml borrowed. ; {"borrower_name":"Jane Doe",…}` |
| Unknown barcode `NOPE999` with person logged in | `alert("Barcode not found!")` (our popup) |
| Terminal page in the browser | `jQuery` 3.7.1 and `$.modal` (jquery-modal, now vendored) present, `#externalBorrower` form present, `storage_permanent` control present; 0 JS errors |

## Lab journal (as `testuser`)

`lj_main` frameset, `edit reaction (add)`, `list reaction/literature/analytical_data`, `sidenav (lj)`, `searchRxn`, disposed-chemicals list — all HTTP 200, `error.log` empty, no local 4xx/5xx.

The only failed requests on the lab-journal pages are Felix's DYMO Label Framework probing `https://localhost:41951-41960/DYMO/DLS/Printing/StatusConnected` (no DYMO software installed here — expected, and identical to upstream). `g_settings.php` / `perm_settings.php` were fetched with the lab-journal session in that run and came back as short stub pages; with the inventory session they render fully (global settings incl. the new Date format select, 109 kB). Not investigated further — it is Felix's frameset logic, unchanged by this work.

## PHP 8 warnings fixed along the way (all `??` guards unless noted)

`lib_global_funcs.php` (login logos/title, `showTopLinkBootstrap`), `topnav.php` (`order_system`, `$selected_text`, `arrCount`), `root_db_man.php` (3× `desired_action`), `printBarcodeList.php` (`table`, `$res`), `lib_constants_barcode.php` (`table`, `field`, 2× `global_barcodes`), `import_only/import_edit/delete_multiple.php` (`desired_action` switch), `sidenav.php` (2× `customization`), `searchExt.php` (`crits`, `supplier`), `barcodeTerminal.php` + `barcodeTerminalAsync.php` (`barcode_sound`, `result`), `lib_db_manip.php` (`version_after`, `borrowed_by_person_id`), `lib_db_manip_edit.php` (`full_logging`, `compartment`), `lib_formatting.php` (`fixCompartment(null)`), `E_STRICT` in three `error_reporting()` calls, `class gdImage` → `oeGdImage` (fatal on PHP 8), `ChemDoodle/ChemDoodleWeb.css` path.

## Not tested here (needs a real network, a real browser session, or production data)

- Supplier scraping (Sigma, Fisher, BLDpharm, …) and MSDS download — outbound HTTP is proxied/blocked in this sandbox.
- ChemDraw JS (licence file), Ketcher/ChemDoodle/VectorMol drawing inside the edit form (page loads; drawing not exercised).
- PDF generation (FPDF 1.86) and analytics spectrum rendering (`lib_draw_analytics.php` — the renamed class loads, rendering not exercised).
- Excel import end-to-end through the UI (library parse verified; the upload form was not driven).
- Behaviour on an existing production database with years of history: rehearsed only on a synthetic "aged" schema. **Run `update.php` on a copy first.**
- Visual polish of `style.css.php` overrides on Bootstrap 5: login, main frameset, search results and terminal were screenshotted and look right; other pages were not eyeballed.

## Round 2 (same day): quick fixes + `lib_import.php` refactor

| Check | Result |
|---|---|
| Pages after the fixes (login, frameset, sidenav, topnav, list, edit, import, barcode terminal, settings) in headless Chromium | 0 JS errors, 0 local 4xx/5xx; **no external host** referenced by any page (fonts now `lib/fonts/fonts.css`); exactly one `<meta viewport>` per page; no script loaded twice |
| `Set-Cookie` on login page | `enventory=…; path=/; HttpOnly; SameSite=Lax` |
| `l()` fallback | German UI: `import_edit_tab_sep` → "Import and Edit via text file", `barcode_autogeneration` → English text; German keys (`search_menu` → "Suchen") unaffected; Polish likewise |
| **Import — add** (`import.php`, tab-separated, `skip_lines=1`) | existing CAS `64-17-5` → new container `IMP001` on new storage "Shelf B" with order date and supplier; **name-only row** ("Sodium chloride", no CAS) → new molecule + container `IMP002`; "2 x 500 mL" → multipack parsed; blank trailing line skipped silently |
| **Import — edit by barcode** (`import_edit.php`) | row `IMP001 / Shelf C / A1` → container **updated** (storage + compartment), order date and supplier **kept**, no new container; row without name/CAS/barcode reported as skipped |
| **Import — add only** (`import_only.php`) | new barcode `IMP003` added; existing `IMP001` added again (no "baylor" customization active — unchanged behaviour) |
| **Import — storages / users** (`import.php`, `table=storage` / `person`) | 2 storages with barcodes created; user `alovelace` created with permissions 135680 (read + borrow + LJ read) |
| Upload-path restriction | `import_file_upload=/etc/hostname` → "Invalid import file." |
| PHP warnings during all of the above | none after fixing `$uploadedFile` init, `str_getcsv()` escape (8.4 deprecation), two `lib_db_manip_edit.php` guards |
