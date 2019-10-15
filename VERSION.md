TO DO:
- Add global_settings for root user to turn on location sharing (show locations for users of other databases)
- create sublocation and include barcode
- create function to update chemicals through uploading "excel"

2019-09-30:
- Add storage barcode and person barcode columns in their respective setting pages
- Add option to shorten the criteria list in Simple search in Inventory 
    (developed for Baylor University)
- Fix bug where date are deleted in edit mode
- Add placeholder for input type date in edit mode with yyyy-mm-dd
- Add function to delete multiple containers via import text
- Update ChemDoodle to ChemDoodleWeb Component v8.0.0
- Upgrade Ketcher to v1.1-beta
- Add 'liters' and 'liter' to the list of recognizing units when importing text file
- Let the cursor default to be in 'Database' input field on login page,
    fix for Firefox
- Add User guides section


2019-07-29:
- fixed Sigma-Aldrich cannot be accessed from A2 Hosting
- fixed changed location inside normal OE window does not record in History text
- add storage barcode for export functions
- add user barcode for export functions
- add show column for storage
- add show column for user
- fix minor issue with "Disposed chemicals" list does not show correct view

2019-07-17:
- Added date style to yyyy-mm-dd hh:mm:ss when display in OE so there is no confusion in date style
- Added a new login page with mobile responsive
- Modified sidenav, topnav to use Bootstrap4
- Added option for admin user to turn Bootstrap 4 option on/off in global_settings

2019-07-03:
- fixed bug in Terminal mode: barcodeTerminalAsync.php and lib_language_en.php
        while doing inventory for a container (inventory mode or "Set storage 
        for all following containers"), if you scan a non-existing barcode, 
        the location will be removed. When a non-existent barcode is scanned,
        an error pop-up window appears.
- modified History log text to add storage_name; also added History log text
        when changing storage in edit mode (lib_db_manip.php, lib_db_manip_edit.php)

2019-06-11:
- import.php, lib_import.php: added importing function for locations and
        users using tab-separated text file
- lib_import.php: fix for importing chemical_storage_barcode bug. 
        When import tab-dilimited text file of chemical containers, if the
        barcode column is the last column, it will add white space or \n
        character, making the barcodes inaccurate. The fix will trim all the
        white space (\t\n) on the right side of the input column
- topnav.php, style.css.php, lib_global_funcs.php, lib_sidenav_funcs.php
        sidenav.php: edited some fonts, styles

2019-06-04:
- lib_language_en.php, sidenav.php, barcode_autogeneration.php: 
        Creating option for admin user to auto generate all location and 
        user barcodes while using "Existing barcodes" functions

2019-05-25:
- lib_db_manip.php: edit logging text to reflect chemical containers when 
    being moved from one location to another

2019-05-23:
- multiple files: Fixed functions for php7 warning
- Fixed "Set storage for all following containers" in Terminal
- Added barcode Type 128 generation for user using existing barcode
- import.php, lib_import.php: Fixed added order_date and open_date in 
        Import tab-separated text file function
