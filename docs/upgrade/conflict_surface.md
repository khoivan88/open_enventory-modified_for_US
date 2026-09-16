# Conflict surface: your changes vs Felix 2021-05-06 → 2026-07-06

Generated 2026-09-16 by diffing three trees (whitespace- and CRLF-insensitive):

- **base** = git commit `3adf870` (last Felix code merged into this repo: OE-2021-05-06 + Sial fix + install script)
- **mine** = git commit `7eb082d` (tip of `develop`)
- **felix** = SourceForge `open_enventory_2026-07-06.zip`

Binary files (jar/png/gif/pdf/exe/swf/zip/ttf/ico/cdx/map) and `.svn` folders are excluded.

## Files changed on BOTH sides (need manual 3-way merge)

`my Δ` / `felix Δ` = number of changed lines on each side. Sorted by my Δ, i.e. how much of my work is at stake in that file.

| my Δ | felix Δ | file |
|-----:|--------:|------|
| 1409 | 2 | `lib_import.php` |
| 1073 | 5 | `style.css.php` |
| 783 | 92 | `import.php` |
| 437 | 201 | `lib_global_funcs.php` |
| 417 | 288 | `suppliers/Sial.php` |
| 354 | 31 | `sidenav.php` |
| 353 | 8 | `topnav.php` |
| 192 | 506 | `lib_db_manip_edit.php` |
| 186 | 35 | `lib_sidenav_funcs.php` |
| 151 | 6 | `lib/formatting.js` |
| 128 | 205 | `lib_formatting.php` |
| 104 | 17 | `lib_language_en.php` |
| 85 | 113 | `lib_db_manip.php` |
| 71 | 2 | `lib/barcode_terminal.js` |
| 66 | 4 | `barcodeTerminal.php` |
| 47 | 63 | `edit.php` |
| 44 | 6 | `lib/sidenav.js` |
| 42 | 226 | `lib/rxnfile.js` |
| 34 | 26 | `list.php` |
| 31 | 14 | `main.php` |
| 30 | 2 | `ChemDoodle/php/sketcher.php` |
| 29 | 51 | `barcodeTerminalAsync.php` |
| 27 | 16 | `printBarcodeList.php` |
| 26 | 146 | `lib_applet.php` |
| 21 | 167 | `lib_edit_reaction_subitemlist.php` |
| 18 | 185 | `lib_supplier_scraping.php` |
| 14 | 186 | `lib_form_elements_helper.php` |
| 13 | 15 | `lib/edit.js` |
| 12 | 131 | `lib_db_filter.php` |
| 11 | 17 | `lib_constants_barcode.php` |
| 10 | 14 | `perm_settings.php` |
| 10 | 366 | `lib_db_query.php` |
| 9 | 2 | `lib_global_settings.php` |
| 9 | 12 | `lib_edit_person.php` |
| 9 | 17 | `g_settings.php` |
| 8 | 276 | `lib_output.php` |
| 6 | 28 | `suppliers/lib/chemexper.php` |
| 5 | 28 | `editWin.php` |
| 5 | 93 | `lib_db_manip_helper.php` |
| 4 | 16 | `lib_io.php` |
| 4 | 23 | `searchRxn.php` |
| 4 | 48 | `lib_person.php` |
| 4 | 354 | `lib_form_elements.php` |
| 3 | 4 | `lib_constants_tables_inventory.php` |
| 3 | 8 | `lib_customization.sample.php` |
| 3 | 14 | `lib_constants.php` |
| 3 | 24 | `lib_constants_default_dataset.php` |
| 3 | 25 | `lib_db_order_by.php` |
| 3 | 32 | `fixFields.php` |
| 3 | 34 | `lib_gd_common.php` |
| 3 | 65 | `lib_simple_forms.php` |
| 3 | 84 | `lib_navigation.php` |
| 2 | 6 | `lib/controls.js` |
| 2 | 6 | `lib/subitemlist.js` |
| 2 | 6 | `lib_instructions_pdf.php` |
| 2 | 6 | `lj_main.php` |
| 2 | 8 | `lib_language_fr.php` |
| 2 | 8 | `lib_language_it.php` |
| 2 | 8 | `lib_language_pt.php` |
| 2 | 10 | `lib_language_es.php` |
| 2 | 17 | `lib_language_de.php` |
| 2 | 43 | `copyReaction.php` |
| 1 | 18 | `lib_url.php` |
| 1 | 33 | `lib_http.php` |
| 1 | 186 | `lib_form_elements_subitemlist.php` |
| 1 | 254 | `lib_root_funcs.php` |

## Files only I changed (Felix untouched since 2021 → merge cleanly)

- `.gitignore`
- `README.md`
- `getSrc.php`
- `index.php`
- `ketcher/chem/cis_trans.js`
- `ketcher/chem/element.js`
- `ketcher/chem/molfile.js`
- `ketcher/chem/sgroup.js`
- `ketcher/chem/smiles.js`
- `ketcher/chem/stereocenters.js`
- `ketcher/chem/struct.js`
- `ketcher/chem/struct_valence.js`
- `ketcher/ketcher.css`
- `ketcher/ketcher.html`
- `ketcher/ketcher.js`
- `ketcher/pack-zip.sh`
- `ketcher/raphael.js`
- `ketcher/rnd/editor.js`
- `ketcher/rnd/elem_table.js`
- `ketcher/rnd/render.js`
- `ketcher/rnd/restruct.js`
- `ketcher/rnd/restruct_rendering.js`
- `ketcher/rnd/rgroup_table.js`
- `ketcher/rnd/visel.js`
- `ketcher/ui/actions.js`
- `ketcher/ui/ui.js`
- `ketcher/util/common.js`
- `ketcher/util/map.js`
- `ketcher/util/pool.js`
- `ketcher/util/set.js`
- `ketcher/util/vec2.js`
- `ketcher2/ketcher.css.map`
- `ketcher2/ketcher.js.map`
- `lib/chem_order.js`
- `lib/chemical_storage.png`
- `lib/chemical_storage_sm.png`
- `lib/del_sm.png`
- `lib/details_sm.png`
- `lib/export_sm.png`
- `lib/list.js`
- `lib/message_in_sm.png`
- `lib/message_out_sm.png`
- `lib/person_barcode_sm.png`
- `lib/person_sm.png`
- `lib/safe_dom.js`
- `lib/search.png`
- `lib/settlement.js`
- `lib/storage_sm.png`
- `lib/tci.gif`
- `lib_constants_default_settings.php`
- `lib_constants_order_by.php`
- `lib_constants_permissions.php`
- `lib_db_query_helper.php`
- `lib_fingerprint.php`
- `lib_smiles.php`
- `lit_main.php`
- `suppliers/VWR.php`

## Files I changed that Felix DELETED

- `suppliers/VWR.php` (Felix dropped VWR 2026-01-08 because of Cloudflare)
- `.gitignore`, `README.md` (not in Felix's zip at all — keep ours)

## Files Felix deleted (2021 → 2026)

- `.gitignore`
- `ChemDoodle/ChemDoodleWeb-libs.js`
- `ChemDoodle/sketcher`
- `INSTALL/jquery-1.8.3.min.js`
- `JChemPaint`
- `LICENSE`
- `README.md`
- `VERSION.txt`
- `VecMol/mit_lic/jquery-1.8.3.min.js`
- `VecMol/mit_lic/jquery.rule-min.js`
- `VecMol/mit_lic/jquery.watermark.min.js`
- `import_iciq.php`
- `install_open_enventory.sh`
- `jodconverter-2.2.2`
- `labels.php.old`
- `lib/DYMO.Label.Framework.2.0.2.js`
- `lib/DYMO.js`
- `lib_io`
- `lib_units.php`
- `sap_split.php`
- `suppliers/Activate.php`
- `suppliers/Alfa.php`
- `suppliers/VWR.php`
- `suppliers/emol.php`
- `suppliers/fluorochem.php`
- `temp_form.php`
- `temp_form2.php`

## Files Felix added (2021 → 2026), excluding .svn

- `FPDF/FAQ.htm`
- `FPDF/changelog.htm`
- `FPDF/doc`
- `FPDF/install.txt`
- `FPDF/tutorial`
- `INSTALL/jquery-3.6.1.min.js`
- `VecMol/mit_lic/jquery-3.7.1.min.js`
- `VecMol/mit_lic/jquery.mousewheel.js`
- `VecMol/mit_lic/jquery.rule.js`
- `VecMol/mit_lic/raphael.js`
- `VecMol/mit_lic/underscore.string.min.js`
- `jodconverter-cli-4.4.11`
- `lib_language_pl.php`
- `literature/aps.php.bak`
- `literature/rsc.php.bak`
- `literature/springer.php.bak`
- `literature/vch.php.bak`
- `macro_new.ahk`
- `searchChemical.php`
- `suppliers/bldpharm.php`

## Files I added (not in Felix at all)

- `.github`
- `INSTALL/install_open_enventory.sh`
- `SimpleXLS.php`
- `SimpleXLSX.php`
- `VERSION.md`
- `barcodeTerminalAsyncQuick.php`
- `barcode_autogeneration.php`
- `chemdraw`
- `delete_multiple.php`
- `docs`
- `extras`
- `getBarcode128.php`
- `import_edit.php`
- `import_only.php`
- `ketcher/chem/inchi.js`
- `ketcher/demo.html`
- `ketcher/icons`
- `ketcher/ketcher.py`
- `ketcher/reaxys`
- `ketcher/render_sdf.sh`
- `ketcher/render_templates.sh`
- `ketcher/rnd/templates.js`
- `ketcher/server`
- `ketcher/templates.sdf`
- `ketcher/third_party`
- `lib/storage_import_template.xlsx`
- `lib/user_import_template.xlsx`
- `lib_customization.mit.php`
- `suppliers/TCI.php.bak`
- `test.php`
- `user-guides.html`
