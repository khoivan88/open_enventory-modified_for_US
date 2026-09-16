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
require_once "lib_db_query.php";
require_once "lib_db_manip.php";
require_once "lib_formatting.php";
require_once "lib_constants_barcode.php";
// print_r($_REQUEST);
$page_type="async";
$barcodeTerminal=true;
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

// fake request for barcodeUser
$person_id=$_REQUEST["person_id"];
$db_user=$_REQUEST["username"];
list($own_data)=mysql_select_array(array(
	"table" => "person", 
	"filterDisabled" => true, 
	"filter" => "person.username=".fixStrSQL($db_user), 
	"dbs" => ($g_settings["global_barcodes"]?"":"-1"), // search barcodes locally or globally?
	"limit" => 1, 
	"noErrors" => true, 
));
//~ $permissions=$own_data["permissions"] & $permissions; // does the active user have sufficient privileges? Restrictions for user barcode remain in place. Does not work somehow...
$permissions=$own_data["permissions"];

$_REQUEST["table"]="chemical_storage";
$_REQUEST["db_id"]=-1;

// parameter: barcode=, table=
if (in_array($_REQUEST["desired_action"],array("inventory","del"))) {
	// => handleDesiredAction
}
elseif (!empty($_REQUEST["barcode"])) {
	$barcodeData=interpretBarcode($_REQUEST["barcode"],1);
	//~ print_r($barcodeData);die();
	
	$_REQUEST["pk"]=$barcodeData["pk"];
	switch ($barcodeData["table"]) {
	case "mpi_order":
		$url="edit.php?db_id=".$barcodeData["db_id"]."&".getSelfRef(array("~script~","table","db_id","pk","cached_query","no_cache"))."table=chemical_storage&mpi_order_id=".$barcodeData["pk"];
		echo "window.open(".fixStr($url).");\n";
	break;
	case "person":
		if (count($barcodeData["result"])) {
			echo "parent.setActivePerson(".json_encode($barcodeData["result"]).");\n"; // may also come from other db
			$output.=getSound("login");
		}
		else {
			echo "parent.setActivePerson();\n";
			$output.=getSound("error");
		}
	break;
	case "chemical_storage":
		if ($_REQUEST["desired_action"]!="loadDataForInventory" && !empty($barcodeData["pk"])) {
			$_REQUEST["desired_action"]="borrow";
			
			if (!empty($barcodeData["result"]["borrowed_by_person_id"])) { // rückgabe
				if (empty($person_id)) { // automatisches login auslösen für die person zum inventarisieren
					list($person_result)=mysql_select_array(array(
						"table" => "person", 
						"dbs" => ($g_settings["global_barcodes"]?$barcodeData["result"]["borrowed_by_db_id"]:"-1"), 
						"filter" => "person.person_id=".fixNull($barcodeData["result"]["borrowed_by_person_id"]), 
						"limit" => 1, 
					));
					$person_id=$person_result["person_id"];
					$db_user=$person_result["username"];
					$permissions=$person_result["permissions"];
					
					echo "parent.setActivePerson(".json_encode($person_result).");\n";
				}
				$output.=getSound("zurueckgeben");
			}
			elseif ($_REQUEST["person_id"]) { // ausleihe
				$_REQUEST["borrowed_by_db_id"]=$_REQUEST["person_db_id"];
				$_REQUEST["borrowed_by_person_id"]=$_REQUEST["person_id"];
				$output.=getSound("ausleihen");
			}
			else { // do nothing
				$output.=getSound("error");
			}
		}
		if ($_REQUEST["storage_permanent"]) {
			// just do not overwrite values in form
			unset($barcodeData["result"]["storage_id"]);
			unset($barcodeData["result"]["compartment"]);
		}
	break;
	case "storage":
		echo "parent.setStorage(".fixNull($barcodeData["pk"]).");
parent.doInventar();\n";
	break;
	default:
		$_REQUEST["desired_action"]="";
	}
}
else {
	$_REQUEST["desired_action"]="";
}

// request vorbereitet
setGlobalVars();
/* echo "/*";
print_r($barcodeData);
print_r($_REQUEST);
echo "* /\n"; */
if (!empty($_REQUEST["desired_action"])) {
	list($success,$message,$pks_added)=handleDesiredAction(); // schreiboperation durchführen
}

if ($_REQUEST["barcode"]) {
	switch ($barcodeData["table"]) {
	case "chemical_storage":
		if ($success==SUCCESS && $_REQUEST["desired_action"]=="borrow") { // erspart erneute abfrage
			$barcodeData["result"]["borrowed_by_person_id"]=$_REQUEST["borrowed_by_person_id"];
		}
		echo "parent.setActiveChemicalStorage(".json_encode($barcodeData["result"]).");\n";
	break;
	}
}

if ($success) { // has tried to do sth
	echo "parent.showMessage(".fixStr($message).");\n";
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