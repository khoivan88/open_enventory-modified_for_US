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
$supplier_code="VWR";
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
	
	if (preg_match("/(.*)(,? )(>?=?[\d\.]+\+?%)/",$cells[0],$match)) { // Quality
		$cells[0]=$match[1];
		$supplier_offer["so_purity"]=$match[2];
		
		$cells[1]=substr($cells[1],0,strlen($cells[1])-strlen($match[1].$match[2])); // cut away end
	}
	
	$molecule["molecule_names_array"]=array(ucwords(strtolower($cells[0])), ucwords(strtolower($cells[1])), ucwords(strtolower($cells[2])), ); // A,B,C
	$supplier_offer["beautifulCatNo"]=ifempty($cells[3],$cells[8]); // D or I
	$supplier_offer["so_price"]=$cells[6]; // G (our price)
	$supplier_offer["so_price_currency"]="EUR";
	$supplier_offer["so_vat_rate"]=19;
	$supplier_offer["so_date"]="2010-09-17";
	
	//~ preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Z]+)/",$cells[9],$amount_data); // J (amount)
	//~ $supplier_offer["so_package_amount"]=$amount_data[1]*ifempty($cells[10],1); // K
	$supplier_offer["so_package_amount"]=$cells[11]*ifempty($cells[10],1); // L,K
	/* $amount_data[2]=str_replace(
		array("litro", "litros", "gr", ), 
		array("l", "l", "g", ), 
		strtolower($amount_data[2]));*/
	//~ $supplier_offer["so_package_amount_unit"]=$amount_data[2];
	$supplier_offer["so_package_amount_unit"]=str_replace(array("gr","lt"),array("g","l"),strtolower($cells[12])); // M
	$supplier_offer["comment_supplier_offer"]=$cells[13]; // N: comment, brand, etc
	
	$molecule["cas_nr"]=$cells[14]; // O
	if (empty($molecule["cas_nr"])) {
		continue; // leave away crazy stuff
	}
	
	$molecule["safety_r"]=str_replace(array("R"," "),array("","-"),$cells[15]); // P
	$molecule["safety_s"]=str_replace(array("S"," "),array("","-"),$cells[16]); // Q
	$molecule["density_20"]=getNumber($cells[17]); // R
	/*
	list($molecule["mp_low"],$molecule["mp_high"])=getRange($cells[19]); // S mp
	
	list($molecule["bp_low"],$molecule["bp_high"],$press)=getRange($cells[19]); // T bp
	$molecule["bp_press"]="1";
	$molecule["press_unit"]="bar";			
	if (trim($press)!="") {
		$molecule["bp_press"]=getNumber($press);
		if (strpos($press,"mm")!==FALSE) {
			$molecule["press_unit"]="torr";
		}
		elseif (strpos($press,"mbar")!==FALSE) {
			$molecule["press_unit"]="mbar";
		}
	}*/
	
	$molecule["molecule_property"]=array();
	/*
	if (!empty($cells[20])) {
		array_push($molecule["molecule_property"],array(
			"class" => "FP", 
			"source" => "Sigma-Aldrich", 
			"value_high" => getNumber($cells[20]), 
			"unit" => "°C", 
		));
		$molecule["density_20"]=$cells[20]; // U flashp
	}*/
	
	$molecule["emp_formula"]=getEmpFormulaHill(str_replace(array(chr(183),"."," "),array("*","*",""),$cells[21])); // V
	//~ $molecule["safety_sym"]=str_replace(" ","",$cells[22]); // W
	$molecule["safety_sym"]=ucwords(strtolower($cells[22])); // W
	if (empty($supplier_offer["so_purity"])) {
		$supplier_offer["so_purity"]=getNumber($cells[23]); // X
	}
	$supplier_offer["supplier"]=ifempty($cells[24],$supplier_code); // Y supplier
	
	set_time_limit(90);
	// find cas
	echo $molecule["cas_nr"]."<br/>";
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