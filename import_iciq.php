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
$unit_result=mysql_select_array(array( // to convert units if required
	"table" => "units", 
	"dbs" => "-1", 
));

function quickTrans($german) {
	return str_replace(
		array("alkohol","aldehyd","oesäure","säure","schwefel","oxid","harnstoff","benzol","toluol","natrium","kalium","silber","kupfer","wolfram","jod","fluor","chlor","brom","iod","fluoroid","chloroid","bromoid","iodoid","oo"),
		array(" alcohol"," aldehyde","oic acid ","ic acid ","sulfur","oxide","urea","benzene","toluene","sodium ","potassium ","silver ","copper ","tungsten ","iod","fluoro","chloro","bromo","iodo","fluoride","chloride","bromide","iodide","o"),
		strtolower($german));
}

function getCASfromName($molecule_name) {
	global $addInfo,$suppliers;
	
	foreach ($addInfo as $idx => $setting) {
		if (in_array($setting[0],array("own","Acros"))) {
			continue;
		}
		echo $setting[0];
		$hitlist=$suppliers[ $setting[0] ]["getHitlist"]($molecule_name,"molecule_name","eq");
		if (count($hitlist)) {
			$bestHit=$suppliers[ $setting[0] ]["getBestHit"]($hitlist);
			$molecule=$suppliers[ $setting[0] ]["getInfo"]($hitlist[$bestHit]["catNo"]);
			return $molecule["cas_nr"];
		}
	}
}

$handle=fopen($db_name.".csv","r");
// /home/fr/storage/trunk/inventar_dev/
// /srv/www/htdocs/inventar_dev/
while (!feof($handle)) {
	$buffer=fgets($handle,16384);
	//~ $zeilen[]=utf8_encode($buffer);
	$zeilen[]=$buffer;
}
fclose ($handle);

for ($a=1+$_REQUEST["skip"];$a<count($zeilen);$a++) {
	$molecule=array();
	$chemical_storage=array();
	$cells=explode("\t",$zeilen[$a]);
	for ($b=0;$b<count($cells);$b++) {
		$cells[$b]=trim($cells[$b]);
	}
	if (empty($cells[0]) && empty($cells[1])) {
		continue;
	}
	
	$molecule["molecule_names_array"]=array($cells[0]); // A
	//~ $molecule["molecule_names_array"]=explode(";",$cells[0]); // A
	$molecule["cas_nr"]=$cells[1]; // B
	$molecule["emp_formula"]=$cells[2]; // C, shall we really do this???
	$molecule["storage_name"]=$cells[3]; // D
	$molecule["supplier"]=$cells[4]; // E
	$molecule["price"]=getNumber($cells[5]); // F
	$molecule["price_currency"]="EUR";
	
	$cells[6]=str_replace(array("(", ")", ),"",$cells[6]); // G
	if (preg_match("/(?ims)([\d\.\,]+)\s*x\s*(.*)/",$cells[6],$amount_data)) { // de Mendoza-Fix
		$cells[9]=$amount_data[1];
		$cells[6]=$amount_data[2];
	}
	preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ]+)/",$cells[6],$amount_data);
	$molecule["amount"]=fixNumber($amount_data[1]);
	$amount_data[2]=str_replace(
		array("litro", "litros", "gr", "umol", ), 
		array("l", "l", "g", "µmol", ), 
		strtolower($amount_data[2])
	);
	$molecule["amount_unit"]=$amount_data[2];
	
	$molecule["order_date"]=getSQLFormatDate(getTimestampFromDate($cells[7])); // H
	$molecule["open_date"]=getSQLFormatDate(getTimestampFromDate($cells[8])); // I
	$molecule["add_multiple"]=ifempty(getNumber($cells[9]),1); // J
	if ($molecule["add_multiple"]>10) { // probably an error
		$molecule["add_multiple"]=1;
	}
	$molecule["migrate_id_mol"]=$cells[10]; // K
	$chemical_storage["migrate_id_cheminstor"]=$cells[11]; // L
	$chemical_storage["comment_cheminstor"]=$cells[12]; // M
	$chemical_storage["compartment"]=$cells[13]; // N
	$chemical_storage["chemical_storage_barcode"]=$cells[14]; // O
	$chemical_storage["actual_amount"]=$molecule["amount"]*getNumber($cells[15])/100; // P
	
	// purity concentration/ solvent
	if (preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ\/%]+)(\sin\s)?(.*)?/",$cells[16],$concentration_data)) { // Q
		$chemical_storage["chemical_storage_conc"]=fixNumber($concentration_data[1]);
		$chemical_storage["chemical_storage_conc_unit"]=strtolower(str_replace(
			array("M", "gr", "umol", ), 
			array("mol/l", "g", "µmol", ), 
			$concentration_data[2]
		));
		// solvent, empty if not provided
		$chemical_storage["chemical_storage_solvent"]=$concentration_data[4];
		if (!empty($cells[17])) {
			$chemical_storage["chemical_storage_density_20"]=fixNumber($cells[17]); // R
		}
	}
	
	// molfile in folder molfiles
	if (!empty($cells[18])) {
		$MOLfilename="./molfiles/".trim($cells[18]); // S
		if (file_exists($MOLfilename)) {
			$molecule["molfile_blob"]=file_get_contents($MOLfilename);
		}
	}
	
	// each ($data as $bessi => $molecule
	//~ if ($bessi<=7883) {
		//~ continue;
	//~ }
	set_time_limit(90);
	// find cas
	echo $molecule["cas_nr"]."<br>";
	/*
	flush();
	ob_flush(); */
	$chemical_storage["molecule_id"]=getMoleculeFromOwnDB($molecule["cas_nr"]);
	if ($chemical_storage["molecule_id"]=="") { // neues Molekül
		/* if (empty($molecule["cas_nr"])) {
			$search=quickTrans($molecule["molecule_names_array"][0]);
			$molecule["cas_nr"]=getCASfromName($search);

		}*/
		
		getAddInfo($molecule); // Daten von suppliern holen, kann dauern
		extendMoleculeNames($molecule);
		//~ print_r($molecule);die("X");
		$oldReq=$_REQUEST;
		$_REQUEST=array_merge($_REQUEST,$molecule);
		$list_int_name="molecule_property";
		$_REQUEST[$list_int_name]=array();
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
		$chemical_storage["molecule_id"]=$_REQUEST["molecule_id"];
		$_REQUEST=$oldReq;
	}

	// make mass out of moles, fix for Ligon
	if (getUnitType($molecule["amount_unit"])=="n") {
		// get mw
		list($result)=mysql_select_array(array(
			"table" => "molecule", 
			"filter" => "molecule.molecule_id=".fixNull($chemical_storage["molecule_id"]), 
			"dbs" => -1, 
			"flags" => QUERY_CUSTOM, 
		));
		
		// get suitable mass unit
		$mass_unit=getComparableUnit($molecule["amount_unit"],"m",$molecule["amount"]*$result["mw"]);
		//~ $mass_unit="mg";
		
		// calc mass
		$molecule["amount"]=get_mass_from_amount($mass_unit,$molecule["amount"],$molecule["amount_unit"],$result["mw"]);
		$molecule["amount_unit"]=$mass_unit;
	}
	
	// do we have to create chemical_storage?
	if ($molecule["storage_name"]!="") {
		$chemical_storage["storage_id"]=createStorageIfNotExist($molecule["storage_name"]);
	}
	else {
		$chemical_storage["storage_id"]="";
	}
	$chemical_storage=array_merge(
		$chemical_storage,
		array_key_filter(
			$molecule,
			array(
				"supplier", 
				"price", 
				"price_currency", 
				"comment_cheminstor", 
				"purity", 
				"amount", 
				"amount_unit", 
			)
		)
	);
	// do we have to create storage first?
	$oldReq=$_REQUEST;
	$_REQUEST=array_merge($_REQUEST,$chemical_storage);
	//~ print_r($_REQUEST);die("X");
	performEdit("chemical_storage",-1,$db);
	$_REQUEST=$oldReq;
}
?>