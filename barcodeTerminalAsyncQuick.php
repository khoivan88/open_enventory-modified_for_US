<?php
/*
Copyright 2006-2018 Felix Rudolphi and Lukas Goossen
open enventory is distributed under the terms of the GNU Affero General Public License, see COPYING for details. You can also find the license under http://www.gnu.org/licenses/agpl.txt

open enventory is a registered trademark of Felix Rudolphi and Lukas Goossen. Usage of the name "open enventory" or the logo requires prior written permission of the trademark holders.

This file is part of open enventory.

open enventory is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

open enventory is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with open enventory.  If not, see <http://www.gnu.org/licenses/>.
*/
require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
require_once "lib_formatting.php";
require_once "lib_constants_barcode.php";
// print_r($_REQUEST);
$page_type = "async";
$barcodeTerminal = true;
pageHeader();

function getSound($obj_name) {
    global $g_settings;
    if ($g_settings["barcode_sound"]) {
        return
script."
parent.$(\"snd_".$obj_name."\").Play();
"._script;
    }
}

echo script."
if (parent && parent!=self) {
";

$person_id = $_REQUEST["person_id"];
$db_user = $_REQUEST["username"];
$permissions=$own_data["permissions"];
$_REQUEST["table"]="chemical_storage";
$_REQUEST["db_id"]=-1;

// parameter: barcode=, table=
if (in_array($_REQUEST["desired_action"], array('inventory', 'del'))) {
    // => handleDesiredAction
}
elseif (!empty($_REQUEST["barcode"])) {
    $barcodeData=interpretBarcode($_REQUEST["barcode"], 1);
    //~ print_r($barcodeData);die();
    $_REQUEST["pk"]=$barcodeData["pk"];
}
else {
    $_REQUEST["desired_action"]="";
}

// request vorbereitet
setGlobalVars();

// echo 'console.log(`';
// print_r($barcodeData);
// print_r($barcodeData["result"]["borrowed_by_person_id"]);
// print_r($_REQUEST);
// echo '`);';

if ($barcodeData["table"] == "chemical_storage"
    && empty($barcodeData["result"]["borrowed_by_person_id"])
    && $_REQUEST["person_id"]
) { // ausleihe
    echo "console.log('External user is borrowing!');";

    // Open modal to ask for borrower info:
    echo "parent.jQuery('#externalBorrower').modal({clickClose: false, showClose: false, });";
    // Focus onto the first element of the form
    echo "
        parent.setTimeout(function() {
            // console.log('trying to get autofocus on borrowing form');
            parent.document.querySelector('#borrower_name').focus();
        }, 300);
    ";
}
else {
    echo 'console.log("Not external users or not borrowing");';
    echo 'console.log("'; print_r($_REQUEST['barcode']); echo '");';
    echo 'parent.barcodeReadToServer("'; print_r($_REQUEST['barcode']); echo '");';
    // return;
}

echo "
}
"._script."
</head>
<body>
".
$output."
</body>
</html>";

completeDoc();
?>