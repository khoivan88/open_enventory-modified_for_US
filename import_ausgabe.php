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
require_once "lib_formatting.php";
require_once "lib_db_manip.php";
require_once "lib_supplier_scraping.php";
require_once "lib_import.php";

pageHeader();
$supplier_code="ausgabe";
$handle=fopen($supplier_code.".csv","r");

while (!feof($handle)) {
	$buffer=fgets($handle,16384);
	//~ $zeilen[]=utf8_encode($buffer);
	$zeilen[]=$buffer;
}
fclose ($handle);

for ($a=1;$a<count($zeilen);$a++) {
	
	$molecule=array();
	$supplier_offer=array();
	$cells=explode("\t",$zeilen[$a]);
	for ($b=0;$b<count($cells);$b++) {
		$cells[$b]=trim($cells[$b]);
	}
	
	$supplier_offer["beautifulCatNo"]=$cells[0]; // A
	$molecule["comment_supplier_offer"]=$cells[1]; // B, useless as molecule name or purity
	$molecule["cas_nr"]=$cells[2]; // C
	
	$supplier_offer["so_package_amount"]=1;
	$supplier_offer["so_package_amount_unit"]=strtolower($cells[4]); // E
	
	$supplier_offer["so_price"]=$cells[7]; // H
	$supplier_offer["so_price_currency"]="EUR";

	$supplier_offer["so_date"]="2010-05-25";
	
	if (empty($molecule["cas_nr"])) {
		continue; // leave away crazy stuff
	}
	
	set_time_limit(90);
	// find cas
	echo $molecule["cas_nr"]."<br>";
	/*
	flush();
	ob_flush(); */
	//~ $supplier_offer["molecule_id"]=getMoleculeFromOwnDB($molecule["cas_nr"]);
	list($db_result)=mysql_select_array(array(
		"table" => "molecule", 
		"filter" => "molecule.cas_nr=".fixStr($molecule["cas_nr"]), 
		"flags" => 1, 
		"dbs" => -1, 
		"limit" => 1, 
	));
	if ($db_result["molecule_id"]=="") { // nicht gefunden, neues Molekül
		getAddInfo($molecule); // Daten von suppliern holen, kann dauern
	}
	else {
		// add some info to existing datasets??
		$molecule=arr_merge($db_result,$molecule);
	}
	extendMoleculeNames($molecule);
	$oldReq=$_REQUEST;
	$_REQUEST=array_merge($_REQUEST,$molecule);
	$list_int_name="molecule_property";
	if (is_array($molecule[$list_int_name])) foreach ($molecule[$list_int_name] as $UID => $property) {
		$_REQUEST[$list_int_name][]=$UID;
		$_REQUEST["desired_action_".$list_int_name."_".$UID]="add";
		$_REQUEST[$list_int_name."_".$UID."_class"]=$property["class"];
		$_REQUEST[$list_int_name."_".$UID."_source"]=$property["source"];
		$_REQUEST[$list_int_name."_".$UID."_conditions"]=$property["conditions"];
		$_REQUEST[$list_int_name."_".$UID."_value_low"]=$property["value_low"];
		$_REQUEST[$list_int_name."_".$UID."_value_high"]=$property["value_high"];
		$_REQUEST[$list_int_name."_".$UID."_unit"]=$property["unit"];
	}
	
	performEdit("molecule",-1,$db);
	$supplier_offer["molecule_id"]=$_REQUEST["molecule_id"];
	$_REQUEST=$oldReq;

	// do we have to create storage first?
	$oldReq=$_REQUEST;
	$_REQUEST=array_merge($_REQUEST,$supplier_offer);
	//~ print_r($_REQUEST);die("X");
	performEdit("supplier_offer",-1,$db);
	$_REQUEST=$oldReq;
}
?>