# Plan: bring this fork up to Felix's latest Open Enventory (PHP 8) without losing our features

Written 2026-09-16. Companion data: [`conflict_surface.md`](conflict_surface.md) (full per-file table).

## 0. Where things stand (measured, not guessed)

| | |
|---|---|
| Last Felix code in this repo | **OE-2021-05-06** (git `3adf870`, merged into `develop` at `b57363b`) |
| Latest Felix release on SourceForge | **OE-2026-07-06** (PHP 8.5-ready; 14 full releases since 2021) |
| Felix's PHP 8 rewrite | **OE-2022-09-07** — "almost every file was affected", one DB field added, DB update runs on first root login |
| Our commits on top of Felix 2021 | 123 files modified, 31 files added, 0 deleted |
| Felix 2021 → 2026 | 314 files modified, 27 deleted, 41 added |
| **Files changed on both sides** | **66** — but only ~12 have >100 changed lines on our side (see §4) |
| Our tree under `php -l` (PHP 8.4) | **15 files fail to parse** (§5) — the fork does not run on PHP 8 today |
| Felix 2026 tree under `php -l` (PHP 8.4) | 1 real failure (`lib_draw_analytics.php`, §8) + 1 unused PEAR file |

Felix's zips are **CRLF** for ~100 files and ship **21 `.svn/` folders**; our git history is mostly LF. Every import must strip `.svn` and normalize line endings, otherwise every file looks fully rewritten and git's 3-way merge is useless.

Felix does not publish a git/svn repo we can pull from — the "upstream" has to be reconstructed from the SourceForge zips.

## 1. Strategy in one paragraph

Rebuild Felix's history as a **vendor branch** (`felix-upstream`) that starts at our existing `3adf870` and gets one commit per SourceForge full release. Then merge that branch into `develop` **in three steps** (pre-PHP8 → PHP8 rewrite → latest) so each merge's conflicts are small and thematically coherent, instead of one 5-year, 300-file merge. Git's 3-way merge then does most of the work: the 57 files only *we* touched and the ~250 files only *Felix* touched merge automatically; hand work concentrates on the ~12 heavy files. After the merges, port our own PHP code to PHP 8 (mostly the `READONLY` rename) and decide feature-by-feature what Felix has since superseded. Keep the vendor branch afterwards, so every future Felix release is a 1-commit import + 1 merge.

## 2. Phase 0 — Preparation (½ day)

1. **Freeze a baseline.** Tag `develop` as `pre-felix-2026-merge` so we can always diff/rollback.
2. **Line endings.** Add `.gitattributes` with `* text=auto eol=lf` plus `-text` for binaries (`*.jar *.png *.gif *.pdf *.exe *.swf *.zip *.ttf *.ico *.cdx *.xlsx`). Commit a one-off `git add --renormalize .` on `develop` **before** the merges so both sides are LF.
3. **CI lint.** Add `.github/workflows/php-lint.yml` running `php -l` on every `*.php` under PHP 8.1 and 8.4 (matrix). Today it would report 15 failures — that's the point; it turns green as we go and prevents regressions.
4. **Test bed.** `docker-compose` with `php:8.1-apache` (+ `gd mysqli mbstring`) and `mariadb:10.11`, mounting the working tree. Import a copy of a real production DB dump (a group's `chemical_storage` with barcodes, storages, users, a few lab-journal entries). Every phase below ends with the smoke checklist in §9 on this box.
5. **Feature inventory.** Confirm the list in §7 against `VERSION.md`; anything missing there gets added before we start, since it's the acceptance list.

## 3. Phase 1 — Build the `felix-upstream` vendor branch (½ day, scriptable)

Full-release zips that exist on SourceForge (the incremental `update_*.zip`s are not needed):

```
2022-02-20  2022-09-07_php8  2022-11-15_php8  2022-11-29_php8  2023-01-29_php8
2023-04-02  2023-06-21  2023-10-14  2024-04-25  2024-05-15  2024-05-24
2024-12-14  2026-01-08  2026-07-06
```

Script (one commit per release, chronological):

```bash
git checkout -b felix-upstream 3adf870          # last Felix tree already in git
for rel in 2022-02-20 2022-09-07_php8 2022-11-15_php8 2022-11-29_php8 2023-01-29_php8 \
           2023-04-02 2023-06-21 2023-10-14 2024-04-25 2024-05-15 2024-05-24 \
           2024-12-14 2026-01-08 2026-07-06; do
  curl -sSL "https://sourceforge.net/projects/enventory/files/open_enventory_${rel}.zip/download" -o /tmp/oe.zip
  # wipe tracked files except our git metadata, then unpack the release's top folder in place
  git rm -rq . && rm -rf ./*                    # keep .git and .gitattributes
  git checkout HEAD -- .gitattributes 2>/dev/null || true
  unzip -q /tmp/oe.zip -d /tmp/oe && cp -a /tmp/oe/open_enventory_*/. . && rm -rf /tmp/oe
  find . -type d -name .svn -prune -exec rm -rf {} +
  git add -A && git commit -qm "Felix OE-${rel%_php8}" --date="${rel%_php8}T12:00:00"
done
```

Sanity check after the loop: `git diff --stat felix-upstream~13 felix-upstream` should show ≈314 modified / 27 deleted / 41 added text files (matches `conflict_surface.md`). Intermediate commits are cheap and make `git log -p felix-upstream -- <file>` useful when resolving conflicts ("what did Felix change here, and in which release, and why").

Keep a `docs/upgrade/FELIX_CHANGELOG.txt` copy of Felix's SourceForge README on this branch so the release notes are in-repo.

## 4. Phase 2–4 — Three merges into `develop`

Do all three on a branch `merge/felix-2026` cut from `develop`; merge it to `develop` only at the end.

| Step | Merge target | Size | Why a separate step |
|---|---|---|---|
| **A** | `felix-upstream` @ OE-2022-02-20 | 91 files changed by Felix | Small, pre-PHP8: last chance to validate our features on a PHP 7.4 box before the rewrite. Removes legacy Java/Flash editors, adds analytics-on-molecules. |
| **B** | @ OE-2022-09-07 | 279 files | The PHP 8 rewrite. Nearly every conflict in the table below lands here. Do this step with `php -l` in the loop. |
| **C** | @ OE-2026-07-06 | 123 files | Bugfixes, supplier scrapers, FPDF 1.86, JODConverter 4.4.11, Polish language, VectorMol updates. Mostly clean. |

Merge command for each step (whitespace-tolerant, conflict markers with the base shown):

```bash
git merge -Xignore-space-change --no-ff <felix-commit>
git config merge.conflictstyle diff3     # once; shows the 2021 base inside conflict hunks
```

### Heavy conflict files and the intended resolution

From `conflict_surface.md` (my Δ / Felix Δ in lines):

| File | Ours | Felix | Resolution approach |
|---|---:|---:|---|
| `lib_import.php` | 1409 | 2 | **Take ours**, re-apply Felix's 2 lines. Our Excel/CSV/user/storage import lives here. Then PHP 8-port it ourselves (§5). |
| `style.css.php` | 1073 | 5 | Take ours (Bootstrap 4 theme), re-apply Felix's 5 lines. |
| `import.php` | 783 | 92 | Ours is the base of `import_edit.php`/`import_only.php`/`delete_multiple.php`. Merge by hand; Felix's 92 lines are mostly `??` null-guards — apply the same guards to the three derived files. |
| `lib_global_funcs.php` | 437 | 201 | Hand merge. Ours: date-format `yyyy-mm-dd`, currency-prefix parsing, misc. Felix's: PHP 8 guards. Low overlap in function bodies. |
| `suppliers/Sial.php` | 417 | 288 | **Take Felix's** (his Sigma scraper was rewritten 3× since 2021 and ours targets the old site), then re-check our currency-prefix fix still applies. |
| `sidenav.php` + `lib/sidenav.js` + `lib_sidenav_funcs.php` | 354+44+186 | 31+6+35 | Hand merge. Decision needed on Select2 vs Felix's `jquery.scombobox` search box (§7). |
| `topnav.php` | 353 | 8 | Take ours (Bootstrap 4 topnav), re-apply Felix's 8 lines. |
| `lib_db_manip_edit.php` | 192 | 506 | Take Felix's, re-apply ours (history-log text for storage moves, external-borrow history entry, `disposed_when` DATETIME). |
| `lib/formatting.js` | 151 | 6 | Take ours, re-apply 6 lines. |
| `lib_formatting.php` | 128 | 205 | Hand merge; ours = date display & currency, Felix = localized dates (2020-07-27) + PHP 8. Overlapping intent — see §7 "date format". |
| `lib_language_en.php` | 104 | 17 | Take Felix's + re-add our ~50 keys (import_edit, external borrow, barcode autogen, …). Other languages: 2-line diffs, trivial. |
| `lib_db_manip.php` | 85 | 113 | Hand merge (history text + external borrow vs PHP 8 guards). |
| `lib/barcode_terminal.js`, `barcodeTerminal*.php` | 71+66+29 | 2+4+51 | Take ours, re-apply Felix's barcode-terminal fixes from 2023-06-21. |
| everything else in the table | ≤50 | — | Take Felix's, re-apply our few lines (mostly 1–10 line tweaks; each is marked with a `Khoi:` comment, `grep -n Khoi` finds them). |

Rule of thumb: whichever side has the *larger* diff becomes the starting text, and the other side's hunks are re-applied on top. Every one of our hunks is (nearly always) tagged with a `// Khoi:` comment, which is what makes this tractable: `git diff 3adf870 7eb082d -- <file>` lists exactly what we added.

Files Felix deleted that we modified: `suppliers/VWR.php` (Cloudflare-blocked; drop ours too), `.gitignore`/`README.md` (never in Felix's zip; keep ours — resolve as "ours" in every step).

Our modified `ketcher/` (v1.1-beta) and `ketcher2/` files: Felix didn't touch them, they merge clean. Felix's 2022-02-20 note "removed legacy Java and Flash structure editors" removes `JChemPaint/`, `flame.swf` etc. — accept.

## 5. Phase 5 — PHP 8 port of *our* code (1–2 days)

Felix only ported his files. Our added/derived files fail on PHP 8 today. What `php -l` (8.4) and grep found in our tree:

| Problem | Where | Fix |
|---|---|---|
| `READONLY` became a reserved word in PHP 8.1 | 29 files use it as a constant; ours: `import_edit.php`, `import_only.php`, `delete_multiple.php`, `import.php`, `sidenav.php`, `editWin.php`, `lib_form_elements.php`, `lib_edit_chemical_storage.php`, … | Felix renamed it to **`READ_ONLY`** everywhere. After merge step B the shared files are fixed; do a repo-wide `READONLY` → `READ_ONLY` for our files (`grep -rlw READONLY --include='*.php'` must return 0). |
| `$str{$i}` string offset syntax (removed in 8.0) | `getBarcode.php`, `OLE.php`, `lib_analytics.php` (Felix fixed these — merge takes care), none in our own files | Comes with the merge. |
| Undefined array key / null to `count()` / `strlen(null)` warnings & TypeErrors | our import trio (`import_edit.php`, `import_only.php`, `delete_multiple.php`), `barcode_autogeneration.php`, `getBarcode128.php`, `barcodeTerminalAsyncQuick.php`, our blocks in `lib_import.php` (75 `Khoi:` hunks), `lib_global_funcs.php`, `lib_formatting.php` | Same treatment Felix applied: `$a["k"]??null`, `is_array($x) && count($x)`, `(string)`. Run each feature on the PHP 8 test box with `error_reporting(E_ALL)` and `display_errors=1` and fix what's logged. |
| `SimpleXLS.php` / `SimpleXLSX.php` (2020 copies) | our Excel import | Replace with current `shuchkin/simplexlsx` ≥ 1.1 and `shuchkin/simplexls` (both PHP 8-clean, same API: `SimpleXLSX::parse()->rows()`), or vendor via Composer. Verify `lib_import.php` date-cell handling (`yyyy-mm-dd` feature) still works. |
| `chemdraw/chemdraw.php` | ChemDraw JS integration | 2 hazard hits; review by hand, then confirm the license-file path logic under PHP 8. |
| `lib_customization.mit.php`, `lib_global_settings.USD.php` | config | 1 hazard hit; re-derive from Felix 2026's `lib_customization.sample.php` / `lib_global_settings.php` and re-add our keys (`customization`, `use_bootstrap4`, currency USD, `safety_sheet_lang=en`). |

Definition of done for this phase: `php -l` green on 8.1 and 8.4 for every file except the two known upstream ones (§8), and Apache `error.log` clean while walking the §9 checklist.

## 6. Phase 6 — DB schema and migration

* Felix's update routine (`lib_db_manip_version.php` / `root_db_man.php`) runs when **root logs in for the first time** after the code update and reconciles all tables against `lib_constants_tables_*.php`. Our schema deltas must be in those files before that login, or they get reverted:
  * `chemical_storage.disposed_when`: ours **DATETIME**, Felix **DATE**. Keep DATETIME (our history text depends on time). Note it inline.
  * `chemical_storage_barcode` in lab-journal tables: Felix changed `varbinary(20)` → `TINYTEXT COLLATE bin` — accept (we also allow long custom barcodes).
  * Felix added `analytics_device_text`, `no_ref_int` flags, and dropped the unique index on `unit_name` — accept.
* **Permission bit `_BORROW_EXTERNAL = 4194304`** (our external-borrow feature): verified Felix's highest bit in 2026 is still `2097152` (`_order_accept`), so **no collision**. Re-add the define, the `borrow_external` predefined-permission group and `$permissions_groups=array(5,8,6,3,1)` in `lib_constants_permissions.php`.
* Run the migration on the docker test DB first (root login → update), diff `SHOW CREATE TABLE` before/after, then repeat on a fresh copy of production before the real cut-over.

## 7. Phase 7 — Feature-by-feature decisions (do these *during* the merges, not after)

Our features from `VERSION.md`, with what Felix has done since and the recommended call:

| Our feature | Felix since 2021 | Recommendation |
|---|---|---|
| Excel/CSV import; import-and-edit / import-only; delete-multiple; storage & user import; import templates | nothing comparable | **Keep.** Biggest port effort (§5). |
| External-borrow (guest) account + history popup | nothing | **Keep.** Bit is free (§6). |
| Bootstrap 4 topnav/sidenav, responsive login, resizable sidenav | nothing | **Keep.** Mostly in files Felix barely touched. |
| Select2 search-criteria box | Felix implemented his own combobox (`lib/jquery.scombobox.min.js`, 2020-10-30, credited to us) | **Drop ours, take Felix's.** Removes ~4 hunks from `sidenav.php`/`sidenav.js` and one JS dependency; re-evaluate after using his for a week. |
| `yyyy-mm-dd` date display everywhere + date placeholder in edit mode | Felix localizes date format per user language (2020-07-27) and fixed the date picker (2024-05-24) | **Take Felix's, then add "ISO" as a selectable date format** (one entry in his format table) instead of hard-coding ours. Cleanest way to keep the behaviour without the conflict. |
| Currency-prefix parsing (`$12.50`) | supplier scrapers rewritten several times | Re-apply as a small helper in `lib_supplier_scraping.php`; test against Sigma/Fisher/Oakwood results. |
| Sigma (Sial) scraper fixes, TCI scraper | Sigma re-fixed 2022-11-29 & 2024-04-25; TCI absent upstream | Take Felix's Sigma. TCI: `TCI.php.bak` stays disabled unless someone re-implements it against the current site. |
| VWR scraper changes | Felix removed VWR (Cloudflare, 2026-01-08) | Drop. |
| Barcode: Code-128 generation, auto-generate location/user barcodes, storage/person barcode columns & export, quick async terminal, "set storage for following containers" (Felix has his own version since 2019) | terminal bugfixes 2023-06-21 | Keep ours; port Felix's 2023 terminal fixes by hand (51 lines in `barcodeTerminalAsync.php`). |
| ChemDraw JS drawing option | nothing (Felix keeps VectorMol/ChemDoodle/Ketcher) | Keep. Check `lib_applet.php` (Felix Δ 146) still dispatches on our `chemdraw` value. |
| Ketcher 1.1-beta upgrade, Ketcher 2 | Felix ships his own Ketcher 2 | Keep ours in step A; after step C compare `ketcher2/` contents and keep the newer. |
| Sort by `order_date`; username 32 chars; search fix for custom barcodes starting with '2'; DYMO button fix; misc PHP 7 `count()` fixes | Felix's PHP 8 work likely subsumes the `count()` fixes | Re-apply the first three (small); drop our PHP 7 warning fixes where Felix's version already guards. |
| `install_open_enventory.sh`, docs, gitbook links, Docker notes, README, VERSION.md | Felix updated his install script and `INSTALL/INSTALL.html` (PHP 8.1 tested) | Take Felix's script; keep our README/VERSION.md and add a 2026 entry. |
| `.github/FUNDING.yml`, `lib_customization.mit.php`, `lib_global_settings.USD.php` | — | Keep. |

Anything not in this table that shows up with a `Khoi:` comment during conflict resolution: keep it, and add a line to `VERSION.md` so the inventory stays true.

## 8. Known problems in Felix's 2026-07-06 release (fix in our fork, report upstream)

1. **`lib_draw_analytics.php` cannot load on PHP ≥ 8.0.** It declares `class gdImage {}` and `class specImage extends gdImage`; PHP 8 has a built-in final class `GdImage` (class names are case-insensitive), so this is `Fatal error: Cannot redeclare class GdImage` as soon as the file is included (analytics spectrum rendering). Fix: rename the userland class to e.g. `oeGdImage` in that file. Two-line change; worth emailing Felix.
2. `File/Archive/Reader/Uncompress.php` (old PEAR File_Archive) has PHP 8 parse errors — it's not included by any OE page, ignore.
3. The zips contain `.svn/` directories and mixed CRLF — handled by the import script.

## 9. Test checklist (run on the docker box after steps A, B, C and phase 5; then on staging with a production DB copy)

Generic OE: root login triggers DB update without SQL errors → create group DB → user login → inventory simple/advanced search, structure search (VectorMol, ChemDoodle, Ketcher 2, **ChemDraw**) → add container via Sigma/Fisher/Oakwood/BLDpharm lookup → edit/dispose container (check `disposed_when` has time) → print labels & Code-128 barcode sheet → lab journal: new reaction, PDF export (FPDF 1.86) → analytics upload (exercises §8.1) → MSDS upload/URL import → settings pages (personal/global/permissions).

Our features: Excel `.xlsx`/`.xls`/CSV/tab import of containers (with `yyyy-mm-dd` dates and currency prefixes) → Import-and-edit vs Import-only by barcode → delete-multiple → storage & user import → barcode auto-generation for storages/users → barcode terminal: user + container scan, "set storage for all following", non-existent barcode error popup, **external-borrow account popup and history line** → Bootstrap 4 UI on desktop and phone, resizable sidenav → sort by `order_date` → 32-char username → search barcode `2xxxx`.

Watch `error.log` throughout; PHP 8 deprecations are the leading indicator of the next TypeError.

## 10. Effort and order

| Phase | Est. | Notes |
|---|---|---|
| 0 Prep | ½ day | |
| 1 Vendor branch | ½ day | scripted |
| 2 Merge A (2022-02-20) | ½ day | |
| 3 Merge B (PHP 8) | 2–3 days | the real work; ~12 files by hand |
| 4 Merge C (2026-07-06) | ½–1 day | |
| 5 PHP 8 port of our code | 1–2 days | import trio + `lib_import.php` dominate |
| 6 DB migration rehearsal | ½ day | |
| 7 Feature decisions | folded into 3–5 | |
| 9 Testing + staging | 1–2 days | |
| **Total** | **~7–10 working days** | serial; merges A→B→C cannot be parallelized |

## 11. Afterwards: staying in sync

Each future Felix release is: download zip → run the Phase 1 loop body once on `felix-upstream` → `git merge felix-upstream` into `develop` → resolve (usually only the files listed in §4) → checklist. Keep every one of our hunks tagged `// Khoi:` and every feature listed in `VERSION.md`; those two habits are what kept this upgrade tractable.
