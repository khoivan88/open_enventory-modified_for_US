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
require_once "lib_formatting.php";
require_once "lib_constants_barcode.php";
// print_r($_REQUEST);
$page_type="async";
$barcodeTerminal=true;
pageHeader();
setGlobalVars();

function gotoBarcode($barcodeData,$targetFrame="parent") {
	// ask if to go to scanned dataset
	$targetFrame=ifempty($targetFrame,"parent");
	$url="edit.php?db_id=".$barcodeData["db_id"]."&".getSelfRef(array("~script~","table","db_id","pk","cached_query","no_cache"))."&";
	if ($barcodeData["table"]=="mpi_order_item") {
		$url.="table=chemical_storage&db_id=".$barcodeData["db_id"]."&mpi_order_item_id=".$barcodeData["pk"];
		$retval=$targetFrame.".location.href=".fixStr($url).";\n";
	}
	elseif ($barcodeData["table"]=="chemical_storage" && !empty($barcodeData["result"]["transferred_to_db_id"])) {
		$url.="table=chemical_storage&db_id=".$barcodeData["db_id"]."&transferred_to_db_id=".$barcodeData["pk"];
		$retval=$targetFrame.".location.href=".fixStr($url).";\n";
	}
	else {
		$url.="dbs=".$barcodeData["db_id"]."&table=".$barcodeData["table"]."&query=&pk=".$barcodeData["pk"];
		if (isset($barcodeData["view"])) {
			$url.="&view=".$barcodeData["view"];
		}
	$retval="if (confirm(".fixStr( s("change_barcode1").s($barcodeData["table"]).s("change_barcode2") ).")) {
	".$targetFrame.".location.href=".fixStr($url).";
}\n";
	}
	return $retval;
}

$handleScanCurrent="
	if (parent.readOnly) {
		parent.startEditMode();
	}
	else {
		parent.valChanged();
		if (parent.editMode) {
			parent.saveChanges();
		}
		else {
			parent.saveDataset();
		}
	}
	
";

echo script."
if (parent) {
";

// parameter: barcode=, table=
$barcodeData=interpretBarcode($_REQUEST["barcode"]);

//~ print_r($barcodeData);print_r($_REQUEST);die();

if (!is_array($_REQUEST["tables"])) {
	$_REQUEST["tables"]=array($_REQUEST["tables"]);
}

if (in_array("mpi_order",$_REQUEST["tables"])) {
	$_REQUEST["tables"][]="chemical_storage";
}

if (
	in_array($barcodeData["table"],$_REQUEST["tables"]) 
	&& $barcodeData["pk"]==$_REQUEST["pk"] 
	&& $barcodeData["db_id"]==$_REQUEST["db_id"] 
) { // !$barcodeData["variable"]  && 
	// Datensatz ist aktueller und gedrucktes Label gescannt
	echo $handleScanCurrent;
}
elseif ($barcodeData["table"]=="chemical_storage" && !empty($barcodeData["result"]["transferred_to_db_id"])) { // abgegeben
	echo gotoBarcode($barcodeData,$_REQUEST["target"]);
}
elseif ($barcodeData["table"]=="mpi_order_item") {
	echo gotoBarcode($barcodeData,$_REQUEST["target"]);
}
elseif (
	(
	$g_settings["barcode_ignore_prefix"] || 
		(
		in_array($barcodeData["table"],$_REQUEST["tables"])
		&& $barcodeData["variable"] 
		)
	)
	&& $barcodeData["pk"]=="" 
	&& $barcodeData["db_id"]=="" // nicht verwendet
) {
	// aufgeklebten Barcode gescannt, dieser ist noch nicht vergeben
	// set field and either start or end edit
	if (!empty($barcodeData["result"]["transferred_to_db_id"])) {
		$dataset=$barcodeData["result"];
	}
	else {
		if ($g_settings["barcode_ignore_prefix"]) { // can be any table
			$barcodeData["fieldName"]=getBarcodeFieldName($_REQUEST["tables"][0]);
		}
		$dataset=array($barcodeData["fieldName"] => $_REQUEST["barcode"]);
	}
	echo "
var currentBarcode=parent.getControlValue(".fixStr($barcodeData["fieldName"]).");
if (currentBarcode==".fixStr($_REQUEST["barcode"]).") { // Barcode ist aktueller Datensatz
".$handleScanCurrent."
}
else if (currentBarcode==\"\" || currentBarcode==0 || confirm(".fixStr(s("overwrite_barcode")).")) { // Barcode setzen
	var dataset=".json_encode($dataset).";
	if (parent.readOnly) {
		parent.loadValues=dataset;
		parent.startEditMode(true);
	}
	else {
		parent.setControlValues(dataset,true);
		parent.valChanged();
		if (parent.editMode) {
			parent.saveChanges();
		}
		else if (!parent.editMode) {
			parent.saveDataset();
		}
	}
}
";
}
elseif ( // Wert in Formular setzen
	($barcodeData["table"]=="storage" || $barcodeData["table"]=="person") 
	&& in_array("chemical_storage",$_REQUEST["tables"]) 
	&& $barcodeData["pk"]!="" && $barcodeData["db_id"]=="-1" // Wert setzen in Formular
) {
	if ($barcodeData["table"]=="storage") {
		$dataset=array("storage_id" => $barcodeData["pk"]);
	}
	elseif ($barcodeData["table"]=="person") {
		$dataset=array("owner_person_id" => $barcodeData["pk"]);
	}
	// barcode von lager gescannt für offenes Gebinde, setzen
	echo "
	var dataset=".json_encode($dataset).";
	if (parent.readOnly) {
		parent.loadValues=dataset;
		parent.startEditMode(true);
	}
	else {
		parent.setControlValues(dataset,true);
		// do not save dataset after this
	}
";
}
elseif (
	$barcodeData["pk"]!="" && 
	$barcodeData["db_id"]!="" && 
	(
		$barcodeData["pk"]!=$_REQUEST["pk"] || 
		$barcodeData["db_id"]!=$_REQUEST["db_id"] || 
		$barcodeData["table"]!=$_REQUEST["table"]
	)
) { // zum Datensatz wechseln
	echo gotoBarcode($barcodeData,$_REQUEST["target"]);
}

// print_r($barcodeData);

echo <<<END
}
</script>
</head>
<body>
</body>
</html>
END;

completeDoc();
?>