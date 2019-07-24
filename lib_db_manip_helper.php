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

// set new status and subtract amounts from chemical_storage if conditions are met, return array of SQL commands to do this
function performReactionOnInventory($db_id,$dbObj,$reaction_id,$new_status) {
	global $reaction_chemical_lists,$g_settings,$permissions;
	
	$retval=array();
	
	if ($new_status<2 || empty($reaction_id)) { // do nothing
		return $retval;
	}
	
	if (($permissions & (_chemical_create | _chemical_edit | _chemical_edit_own | _chemical_borrow | _chemical_inventarise | _chemical_delete))!=0) { // if there is no permission, do not update amounts
		// check if status was changed from 1 to something higher
		list($reaction)=mysql_select_array(array(
			"table" => "reaction", 
			"dbs" => -1, 
			"filter" => "reaction.status=1 AND reaction.reaction_id=".fixNull($reaction_id), 
			"limit" => 1, 
			"flags" => QUERY_EDIT, 
		));
		
		if (!empty($reaction["reaction_id"])) { // update amounts
			for ($a=0;$a<2;$a++) {
				$list_int_name=$reaction_chemical_lists[$a];
				if (is_array($reaction[$list_int_name])) foreach ($reaction[$list_int_name] as $reaction_chemical) {
					if ($reaction_chemical["other_db_id"]!=-1 || empty($reaction_chemical["chemical_storage_id"])) {
						continue;
					}
					
					// alte Menge abfragen
					$filter="chemical_storage.chemical_storage_id=".fixNull($reaction_chemical["chemical_storage_id"]); // wird später noch mal benötigt
					list($chemical_storage_result)=mysql_select_array(array(
						"dbs" => -1, 
						"table" => "chemical_storage", 
						"filter" => $filter, 
						"limit" => 1, 
					)); // bestehende Daten abfragen
					
					// disabled for this molecule or this chemical?
					if (
						$chemical_storage_result["actual_amount"]>0 // otherwise senseless
						&& 
						(
							$chemical_storage_result["chemical_storage_bilancing"]==1
							|| 
							(
								$chemical_storage_result["chemical_storage_bilancing"]=="" // default
								&&
								(
									$chemical_storage_result["molecule_bilancing"]==1
									|| 
									(
										$chemical_storage_result["molecule_bilancing"]=="" // default
										&& 
										getSetting("general_bilancing") // active in general
									)
								)
							)
						)
					) {
						
						// Welche Einheit?
						switch (strtolower($chemical_storage_result["amount_unit_type"])) {
						case "m":
							$cmdText="(".fixNull($reaction_chemical["m_brutto"])." * (SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch($reaction_chemical["mass_unit"])." LIMIT 1))";
						break;
						case "v":
							$cmdText="(".fixNull($reaction_chemical["volume"])." * (SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch($reaction_chemical["volume_unit"])." LIMIT 1))";
						break;
						// otherwise do nothing
						}
						
						// neue Menge setzen
						if (!empty($cmdText)) {
							if ($g_settings["bilancing_percent"]!=="") {
								$cmdText.="*".(1+$g_settings["bilancing_percent"]/100);
							}
							$retval[]="UPDATE chemical_storage SET actual_amount=actual_amount-".$cmdText." WHERE ".$filter.";";
						}
						
					}
				}
			}


		}
	}
	
	// set new status
	if ($new_status>$reaction["status"]) {
		$retval[]="UPDATE reaction SET status=".fixNull($new_status)." WHERE reaction.reaction_id=".fixNull($reaction_id).";";
	}
	
	return $retval;
}

/*------------------------------------------------------------------------------
 * Function: build_reaction_chemical
 * Purpose: take values defining a reaction component from $_REQUEST and build a
 * hash, which is returned
 * Parameter:
 * 		$list_int_name : reactant, reagent or product
 *  		$UID : defining the line
 *  		$reaction_chemical_id : used as prefix in copyTable
 *  		$int_name :
 * 
 * Return : new reaction_chemical
 * -----------------------------------------------------------------------------
 * History:
 *----------------------------------------------------------------------------*/
function build_reaction_chemical($list_int_name,$UID,$reaction_chemical_id,$int_name) {
	$reaction_chemical=array();
	$fields=array("molecule_id","other_db_id","molfile_blob","standard_name","package_name","cas_nr");
	if ($int_name!="products") {
		array_push($fields,
			"from_reaction_id", 
			"from_reaction_chemical_id", 
			"chemical_storage_id"
		);
	}
	for ($a=0;$a<count($fields);$a++) {
		$reaction_chemical[ $fields[$a] ]=getValueUID($list_int_name,$UID,$fields[$a],$reaction_chemical_id);
	}
	return $reaction_chemical;
}

function select_chemical_for_reaction(& $molResult,$v) {
	$molResult=arr_merge(
		$molResult,
		$molResult["chemical_storage"][$v]
	);
	addPackageName($molResult);
	unset($molResult["chemical_storage"]); // remove crap
	unset($molResult["amount"]); // remove crap
	unset($molResult["amount_unit"]); // remove crap
}

function load_reaction_chemical(& $reaction_chemical,$prototype,$int_name) { // considering the existing data, load as much as possible from DB
	$newMolObj=readMolfile($reaction_chemical["molfile_blob"],array("quick" => true) );
	
	// give reaction reference preference
	if (empty($reaction_chemical["chemical_storage_id"]) // otherwise chemical_storage is set
		&& (!empty($reaction_chemical["from_reaction_id"]) || !empty($reaction_chemical["from_reaction_chemical_id"]))) { // aus Reaktion
		if (!empty($reaction_chemical["from_reaction_chemical_id"])) {
			$filter="reaction_chemical.reaction_chemical_id=".fixNull($reaction_chemical["from_reaction_chemical_id"]);
			$is_reaction_chemical=true;
		}
		else {
			$filter="reaction.reaction_id=".fixNull($reaction_chemical["from_reaction_id"]);
			$is_reaction_chemical=false;
		}
		
		list($reaction)=mysql_select_array(array(
			"table" => "reaction_chemical_for_reaction", 
			"dbs" => $reaction_chemical["other_db_id"], 
			"filter" => $filter, 
			"limit" => 1, 
			"flags" => 1, 
		));
		unset($reaction["m_brutto"]); // leave prototype intact
		unset($reaction["mass_unit"]); // leave prototype intact
		unset($reaction["rc_amount"]); // leave prototype intact
		unset($reaction["rc_amount_unit"]); // leave prototype intact
		procReactionProduct($reaction,$is_reaction_chemical);
		$reaction_chemical=arr_merge(
			$reaction_chemical, 
			$reaction
		);
	}
	elseif (!empty($reaction_chemical["molecule_id"])) { // aus Inventar
		unset($reaction_chemical["package_name"]); // keine Altlasten
		
		list($molResult)=mysql_select_array(array(
			"table" => "molecule_for_reaction", 
			"dbs" => $reaction_chemical["other_db_id"], 
			//~ "filter" => $filter, 
			"filter" => "molecule.molecule_id=".fixNull($reaction_chemical["molecule_id"]), 
			"order_obj" => array(
				"field" => "chemical_storage.chemical_storage_id=".fixNull($reaction_chemical["chemical_storage_id"]), "order" => "DESC", "no_hints" => true
			),
			"flags" => ($int_name!="products" ? QUERY_EDIT:QUERY_SIMPLE), 
			"limit" => 1, 
		));
		
		// do we have to merge data for chemical_storage??
		if (!empty($reaction_chemical["chemical_storage_id"])) {
			$something_found=false;
			for ($v=0;$v<count($molResult["chemical_storage"]);$v++) {
				if ($molResult["chemical_storage"][$v]["chemical_storage_id"]==$reaction_chemical["chemical_storage_id"]) {
					select_chemical_for_reaction($molResult,$v);
					$something_found=true;
					break;
				}
			}
			if (!$something_found) { // take first available
				select_chemical_for_reaction($molResult,0);
			}
		}
		
		$reaction_chemical=arr_merge(
			$reaction_chemical, 
			$molResult
		);
		
	}
	elseif (count($newMolObj["atoms"]) || 
		!empty($reaction_chemical["standard_name"]) || 
		!empty($reaction_chemical["cas_nr"]) || 
		!empty($reaction_chemical["package_name"])
	) { // unknown structure or unchanged
		if (!empty($reaction_chemical["smiles_stereo"]) && $reaction_chemical["smiles_stereo"]==$prototype["smiles_stereo"]) { // no change, only rotated/etc
				unset($newMolObj);
		}
		else {
			$reaction_chemical=array_merge($reaction_chemical,array(
				"smiles" => $newMolObj["smiles"], 
				"smiles_stereo" => $newMolObj["smiles_stereo"], 
				"mw" => $newMolObj["mw"], 
				"emp_formula" => $newMolObj["emp_formula_string"], 
			));
		}
	}
	else {
		return false;
	}
	
	return $newMolObj;
}

function complete_reaction_chemical(& $reaction_chemical,$ref_amount,$ref_amount_unit,$factor=1) { // take incomplete reaction component and calculate missing stuff, scale by factor
	if (empty($reaction_chemical["rc_conc"]) || empty($reaction_chemical["rc_conc_unit"])) { // fixPurity must stay in place
		$reaction_chemical["rc_conc"]=100;
		$reaction_chemical["rc_conc_unit"]="%";
	}
	
	if ($reaction_chemical["measured"]==0) { // autodetect from amount_unit (NOT rc_amount_unit, which is the moles)
		
		if ($reaction_chemical["amount_unit"]=="-1") { // eq => mmol
			$reaction_chemical["amount"]=get_amount_from_stoch_coeff($reaction_chemical["amount"],$reaction_chemical["rc_amount_unit"],$ref_amount,$ref_amount_unit);
			$reaction_chemical["amount_unit"]=$reaction_chemical["rc_amount_unit"];
		}
	
		switch (getUnitType($reaction_chemical["amount_unit"])) {
		case "m":
			$reaction_chemical["measured"]=1;
			$reaction_chemical["m_brutto"]=$reaction_chemical["amount"];
			if (empty($reaction_chemical["mass_unit"])) { // indiv
				$reaction_chemical["mass_unit"]=$reaction_chemical["amount_unit"];
			}
			else {
				$reaction_chemical["m_brutto"]*=getUnitFactor($reaction_chemical["amount_unit"])/getUnitFactor($reaction_chemical["mass_unit"]);
			}
		break;
		case "v":
			$reaction_chemical["measured"]=2;
			$reaction_chemical["volume"]=$reaction_chemical["amount"];
			if (empty($reaction_chemical["volume_unit"])) { // indiv
				$reaction_chemical["volume_unit"]=$reaction_chemical["amount_unit"];
			}
			else {
				$reaction_chemical["volume"]*=getUnitFactor($reaction_chemical["amount_unit"])/getUnitFactor($reaction_chemical["volume_unit"]);
			}
		break;
		case "n":
			$reaction_chemical["measured"]=3;
			$reaction_chemical["rc_amount"]=$reaction_chemical["amount"];
			if (empty($reaction_chemical["rc_amount_unit"])) { // indiv
				$reaction_chemical["rc_amount_unit"]=$reaction_chemical["amount_unit"];
			}
			else {
				$reaction_chemical["rc_amount"]*=getUnitFactor($reaction_chemical["amount_unit"])/getUnitFactor($reaction_chemical["rc_amount_unit"]);
			}
		break;
		default: // unknown
			return;
		}
	}
	
	// analog reaction.js
	switch ($reaction_chemical["measured"]) {
	case 1: // m
		multiplyIfNotEmpty($reaction_chemical["m_brutto"],$factor);
		
		// über die Dichte das Volumen berechnen
		$reaction_chemical["volume"]=get_volume(
			$reaction_chemical["volume_unit"],
			$reaction_chemical["m_brutto"],
			$reaction_chemical["mass_unit"],
			$reaction_chemical["density_20"]
		);
		
		switch (getUnitType($reaction_chemical["rc_conc_unit"])) {
		case "m/m":
			// über die Molmasse die Stoffmenge berechnen
			$reaction_chemical["rc_amount"]=get_amount(
				$reaction_chemical["rc_amount_unit"],
				$reaction_chemical["m_brutto"]*fixPurity($reaction_chemical["rc_conc"]*getUnitFactor($reaction_chemical["rc_conc_unit"])),
				$reaction_chemical["mass_unit"],
				$reaction_chemical["mw"]
			);
		break;
		case "molal":
			// über die Masse und die Molalität die Stoffmenge
			$reaction_chemical["rc_amount"]=get_amount_from_mass_molal(
				$reaction_chemical["rc_amount_unit"],
				$reaction_chemical["m_brutto"],
				$reaction_chemical["mass_unit"],
				$reaction_chemical["rc_conc"],
				$reaction_chemical["rc_conc_unit"]
			);
		break;
		case "c":
		// über das Volumen und die Konz die Stoffmenge
			$reaction_chemical["rc_amount"]=get_amount_from_volume(
				$reaction_chemical["rc_amount_unit"],
				$reaction_chemical["volume"],
				$reaction_chemical["volume_unit"],
				$reaction_chemical["rc_conc"],
				$reaction_chemical["rc_conc_unit"]
			);
		break;
		}
	break;
	case 2: // v
		multiplyIfNotEmpty($reaction_chemical["volume"],$factor);
		
		// über die Dichte die Masse berechnen
		$reaction_chemical["m_brutto"]=get_mass_from_volume(
			$reaction_chemical["mass_unit"],
			$reaction_chemical["volume"],
			$reaction_chemical["volume_unit"],
			$reaction_chemical["density_20"]
		);
		
		switch (getUnitType($reaction_chemical["rc_conc_unit"])) {
		case "m/m":
			// über die Molmasse die Stoffmenge berechnen
			$reaction_chemical["rc_amount"]=get_amount(
				$reaction_chemical["rc_amount_unit"],
				$reaction_chemical["m_brutto"]*fixPurity($reaction_chemical["rc_conc"]*getUnitFactor($reaction_chemical["rc_conc_unit"])),
				$reaction_chemical["mass_unit"],
				$reaction_chemical["mw"]
			);
		break;
		case "molal":
			// über die Masse und die Molalität die Stoffmenge
			$reaction_chemical["rc_amount"]=get_amount_from_mass_molal(
				$reaction_chemical["rc_amount_unit"],
				$reaction_chemical["m_brutto"],
				$reaction_chemical["mass_unit"],
				$reaction_chemical["rc_conc"],
				$reaction_chemical["rc_conc_unit"]
			);
		break;
		case "c":
			// über das Volumen und die Konz die Stoffmenge
			$reaction_chemical["rc_amount"]=get_amount_from_volume(
				$reaction_chemical["rc_amount_unit"],
				$reaction_chemical["volume"],
				$reaction_chemical["volume_unit"],
				$reaction_chemical["rc_conc"],
				$reaction_chemical["rc_conc_unit"]
			);
		break;
		}
	break;
	case 3: // n
		multiplyIfNotEmpty($reaction_chemical["rc_amount"],$factor);
		
		switch (getUnitType($reaction_chemical["rc_conc_unit"])) {
		case "m/m":
			// über die Molmasse die Masse berechnen
			$reaction_chemical["m_brutto"]=get_mass_from_amount(
				$reaction_chemical["mass_unit"],
				$reaction_chemical["rc_amount"]/fixPurity($reaction_chemical["rc_conc"]*getUnitFactor($reaction_chemical["rc_conc_unit"])),
				$reaction_chemical["rc_amount_unit"],
				$reaction_chemical["mw"]
			);
		
			// über die Dichte das Volumen berechnen
			$reaction_chemical["volume"]=get_volume(
				$reaction_chemical["volume_unit"],
				$reaction_chemical["m_brutto"],
				$reaction_chemical["mass_unit"],
				$reaction_chemical["density_20"]
			);
		break;
		case "molal":
			// aus Stoffmenge und Molalität das Masse berechnen
			$reaction_chemical["m_brutto"]=get_mass_from_amount_molal(
				$reaction_chemical["mass_unit"],
				$reaction_chemical["rc_amount"],
				$reaction_chemical["rc_amount_unit"],
				$reaction_chemical["rc_conc"],
				$reaction_chemical["rc_conc_unit"]
			);
			
			// über die Dichte das Volumen berechnen
			$reaction_chemical["volume"]=get_volume(
				$reaction_chemical["volume_unit"],
				$reaction_chemical["m_brutto"],
				$reaction_chemical["mass_unit"],
				$reaction_chemical["density_20"]
			);
		break;
		case "c":
			// über das Volumen und die Konz das Volumen berechnen
			$reaction_chemical["volume"]=get_volume_from_amount(
				$reaction_chemical["volume_unit"],
				$reaction_chemical["rc_amount"],
				$reaction_chemical["rc_amount_unit"],
				$reaction_chemical["rc_conc"],
				$reaction_chemical["rc_conc_unit"]
			);
			
			// über die Dichte die Masse berechnen
			$reaction_chemical["m_brutto"]=get_mass_from_volume(
				$reaction_chemical["mass_unit"],
				$reaction_chemical["volume"],
				$reaction_chemical["volume_unit"],
				$reaction_chemical["density_20"]
			);
		break;
		}
	break;
	}
	
	$reaction_chemical["stoch_coeff"]=get_stoch_coeff_from_amount(
		$reaction_chemical["rc_amount"],
		$reaction_chemical["rc_amount_unit"],
		$ref_amount,
		$ref_amount_unit
	);

}

function transfer_reaction_chemical(& $reaction_chemical,& $newReaction,$int_name) {
	// UID setzen
	$newUID=uniqid().$reaction_chemical["nr_in_reaction"]; // otherwise identical IDs may appear => copy bug
	$newReaction[$int_name][]=$newUID;
	$newReaction["desired_action_".$int_name."_".$newUID]="add";
								
	if (is_array($reaction_chemical)) foreach ($reaction_chemical as $name => $value) {
		if (in_array($name,array("reaction_chemical_id","gif_file","svg_file","gc_yield","yield"))) { // diese Werte NICHT kopieren
			continue;
		}
		if ($c==2 && in_array($name,array("purity","m_brutto")) ) { // Reinheit der Produkte nicht übernehmen
			continue;
		}
		$newReaction[$int_name."_".$newUID."_".$name]=$value;
	}
}

function addConditionalDelete(& $queryArray,$table,$db_id,$condition) { // $condition also includes limit,...
	if (mayWrite($table,$db_id)) {
		$queryArray[]="DELETE FROM ".$table." WHERE ".$condition.";";
	}
}

function addConditionalUpdate(& $queryArray,$table,$db_id,$values,$condition) { // $condition also includes limit,...
	if (mayWrite($table,$db_id)) {
		$queryArray[]="UPDATE ".$table." SET ".$values." WHERE ".$condition.";";
	}
}

function hashArray($arr,$now) {
	$res=hash_init(hash_algo);
	hash_update($res,$now.":");
	if (count($arr)) {
		// exclude order effects
		ksort($arr);
		foreach ($arr as $field => $value) { // do not add slashes, waste of time
			hash_update($res,$field.":");
			hash_update($res,fixStr($value).":");
		}
	}
	return hash_final($res,true); // have it binary for mem eco
}

function compareChangesUID(& $old,& $new,$list_int_name,$UID,$fields,$langKey=null) {
	if (!is_array($fields)) {
		$fields=array($fields);
	}
	if (is_null($langKey)) {
		$langKey=$fields[0];
	}
	foreach ($fields as $field) {
		if ($old[$field]!=$new[ $list_int_name."_".$UID."_".$field ]) {
			$retval=s($langKey).": ";
			$retval2="";
			foreach ($fields as $field2) {
				$retval.=$old[$field2]." ";
				$retval2.=" ".$new[ $list_int_name."_".$UID."_".$field2 ];
			}
			return $retval."=>".$retval2;
		}
	}
}

function compareChanges(& $old,& $new,$fields,$langKey=null) {
	if (!is_array($fields)) {
		$fields=array($fields);
	}
	if (is_null($langKey)) {
		$langKey=$fields[0];
	}
	foreach ($fields as $field) {
		if ($old[$field]!=$new[$field]) {
			$retval=s($langKey).": ";
			$retval2="";
			foreach ($fields as $field2) {
				$retval.=$old[$field2]." ";
				$retval2.=" ".$new[$field2];
			}
			return $retval."=>".$retval2;
		}
	}
}

function getHistorySQL($now,$text) {
	global $own_data;
	if (!empty($text)) { // append history comment
		return "history=CONCAT(history,".fixStrSQL("\n".getGermanDate($now,true)." ".formatPersonNameNatural($own_data).": ".$text)."),";
	}
}

function getChemicalStorageLogText($old,$new,$difference=false) {
	$unit_changed=($old["amount_unit"]!=$new["amount_unit"]);
	if ($old["actual_amount"]!=$new["actual_amount"] || $unit_changed) {
		$retval=$old["molecule_name"]." ".
			$old["amount"]." (".
			$old["actual_amount"].
			($unit_changed?" ".$old["amount_unit"]:"");
		
		if ($difference) {
			$retval.=", ".s("withdrawn1")." ".
				$new["amount"].
				($unit_changed?" ".$new["amount_unit"]:"").
				" ".s("withdrawn2");
		}
		else {
			$retval.=" => ".
				$new["actual_amount"].
				($unit_changed?" ".$new["amount_unit"]:"");
		}
		
		$retval.=")".
			($unit_changed?"":" ".$new["amount_unit"]);
		return $retval;
	}
}

function getSDSSQL($fieldName) {
	// prüft, ob ein Minus am Anfang der default_safety_sheet_url steht, wenn ja, dann wird das Molekül mit einem neuen SDB versehen. Die SBDs gehören zum Gebinde, im Molekül wird immer das zuletzt aktualisierte zwischengespeichert. Ein neues Gebinde erhält dann standardmäßig das SDB des Moleküls
	$firstChar=substr($_REQUEST[$fieldName."_url"],0,1);
	if ($firstChar=="-") { // new one
		require_once "lib_http.php";

		$_REQUEST[$fieldName."_url"]=substr($_REQUEST[$fieldName."_url"],1);
		$response=@oe_http_get($_REQUEST[$fieldName."_url"],array("redirect" => maxRedir, "useragent" => uA));
		if ($response!==FALSE) {
			$_REQUEST[$fieldName."_blob"]=@$response->getBody();
			$_REQUEST[$fieldName."_mime"]=@$response->getHeader("Content-Type");
		}
		if (empty($_REQUEST[$fieldName."_blob"])) {
			$_REQUEST[$fieldName."_url"]="";
			$_REQUEST[$fieldName."_by"]="";
		}
		return nvp($fieldName."_url",SQL_TEXT).
			nvp($fieldName."_by",SQL_TEXT).
			nvp($fieldName."_mime",SQL_TEXT).
			nvp($fieldName."_blob",SQL_BLOB);
	} elseif ($firstChar=="+") {
		if (strlen($_REQUEST[$fieldName."_url"])==1) {
			// delete
			return $fieldName."_url=\"\",".$fieldName."_by=\"\",".$fieldName."_mime=\"\",".$fieldName."_blob=\"\",";
		} elseif (pathSafe($_REQUEST[$fieldName."_url"],"..")) { // file in temp directory
			$tmpdir=oe_get_temp_dir();
			$filename=$tmpdir."/".substr($_REQUEST[$fieldName."_url"],1);
			$_REQUEST[$fieldName."_blob"]=file_get_contents($filename);
			if (isPDF($_REQUEST[$fieldName."_blob"])) {
				$_REQUEST[$fieldName."_mime"]="application/pdf";
			}
			@unlink($filename);
			$_REQUEST[$fieldName."_url"]="";
			
			return nvp($fieldName."_url",SQL_TEXT).
				nvp($fieldName."_by",SQL_TEXT).
				nvp($fieldName."_mime",SQL_TEXT).
				nvp($fieldName."_blob",SQL_BLOB);
		}
	}
	return "";
}

// calculating rxnCopies
function fixPurity($purity) {
	if ($purity>=0 && $purity<=1) {
		return $purity;
	}
	return 1;
}

function get_volume($volume_unit,$mass,$mass_unit,$density_20) { // aus Masse und Dichte das Volumen berechnen
	$factor=getUnitFactor($mass_unit);
	$divisor=getUnitFactor($volume_unit);
	if ($density_20>0 && $mass>0 && $divisor>0) {
		return $mass/$density_20*$factor/$divisor;
	}
	return "";
}

function get_amount($amount_unit,$mass,$mass_unit,$mw) { // aus Masse und Molmasse die Stoffmenge berechnen
	$factor=getUnitFactor($mass_unit);
	$divisor=getUnitFactor($amount_unit);
	if ($mw>0 && $mass>0 && $divisor>0) {
		return $mass/$mw*$factor/$divisor;
	}
	return "";
}

function get_mass_from_volume($mass_unit,$volume,$volume_unit,$density_20) { // aus Volumen und Dichte die Masse berechnen
	$factor=getUnitFactor($volume_unit);
	$divisor=getUnitFactor($mass_unit);
	if ($density_20>0 && $volume>0 && $divisor>0) {
		return $volume*$density_20*$factor/$divisor;
	}
	return "";
}

function get_mass_from_amount($mass_unit,$amount,$amount_unit,$mw) { // aus Stoffmenge und Molmasse die Masse berechnen
	$factor=getUnitFactor($amount_unit);
	$divisor=getUnitFactor($mass_unit);
	if ($mw>0 && $amount>0 && $divisor>0) {
		return $amount*$mw*$factor/$divisor;
	}
	return "";
}

function get_stoch_coeff_from_amount($amount,$amount_unit,$ref_amount,$ref_amount_unit) {
	$factor=getUnitFactor($amount_unit);
	$divisor=getUnitFactor($ref_amount_unit);
	if ($ref_amount>0 && $divisor>0) {
		return $amount/$ref_amount*$factor/$divisor;
	}
	return "";
}

function get_amount_from_stoch_coeff($stoch_coeff,$amount_unit,$ref_amount,$ref_amount_unit) {
	$factor=getUnitFactor($ref_amount_unit);
	$divisor=getUnitFactor($amount_unit);
	if ($ref_amount>0 && $divisor>0) {
		return $stoch_coeff*$ref_amount*$factor/$divisor;
	}
	return "";
}

// conc

function get_amount_from_volume($amount_unit,$volume,$volume_unit,$conc,$conc_unit) { // aus Volumen und Konzentration die Stoffmenge berechnen
	$factor=getUnitFactor($conc_unit)*getUnitFactor($volume_unit);
	$divisor=getUnitFactor($amount_unit);
	if ($volume>0 && $conc>0 && $divisor>0) {
		return $volume*$conc*$factor/$divisor;
	}
	return "";
}

function get_volume_from_amount($volume_unit,$amount,$amount_unit,$conc,$conc_unit) { // aus Stoffmenge und Konz das Volumen berechnen
	$factor=getUnitFactor($amount_unit);
	$divisor=getUnitFactor($volume_unit)*getUnitFactor($conc_unit);
	if ($amount>0 && $conc>0 && $divisor>0) {
		return $amount*$factor/$conc/$divisor;
	}
	return "";
}

// molal related

function get_amount_from_mass_molal($amount_unit,$mass,$mass_unit,$molal,$molal_unit) { // aus Masse und Molalität die Stoffmenge berechnen
	$factor=getUnitFactor($molal_unit)*getUnitFactor($mass_unit)/getUnitFactor($amount_unit);
	if ($mass>0 && $molal>0) {
		return $mass*$molal*$factor;
	}
	return "";
}

function get_mass_from_amount_molal($mass_unit,$amount,$amount_unit,$molal,$molal_unit) { // aus Stoffmenge und Molalität das Masse berechnen
	$factor=getUnitFactor($amount_unit)/getUnitFactor($mass_unit)/getUnitFactor($molal_unit);
	if ($amount>0 && $molal>0) {
		return $amount*$factor/$molal;
	}
	return "";
}

function initUnits() {
	global $unit_result;
	
	if (!count($unit_result)) {
		$unit_result=mysql_select_array(array("table" => "units", "dbs" => "-1"));
	}
}

function getUnitProperty($unit_name,$property_name) {
	global $unit_result;
	
	initUnits();
	for ($b=0;$b<count($unit_result);$b++) {
		if ($unit_name==$unit_result[$b]["unit_name"]) {
			return $unit_result[$b][$property_name];
		}
	}
}

function getUnitFactor($unit_name) {
	return getUnitProperty($unit_name,"unit_factor");
}

function getUnitType($unit_name) {
	return getUnitProperty($unit_name,"unit_type");
}

function getComparableUnit($unit_name,$target_type,$additional_factor=1) {
	global $unit_result;
	$target_factor=getUnitFactor($unit_name)*$additional_factor;
	
	for ($b=0;$b<count($unit_result);$b++) {
		if ($target_type==$unit_result[$b]["unit_type"]) { // of the target type
			if ($unit_result[$b]["unit_factor"]==$target_factor) {
				return $unit_result[$b]["unit_name"];
			}
			if (!isset($best_factor) || abs($best_factor-$target_factor)>abs($unit_result[$b]["unit_factor"]-$target_factor)) {
				$best_factor=$unit_result[$b]["unit_factor"];
				$best_name=$unit_result[$b]["unit_name"];
			}
		}
	}
	return $best_name;
}

function makeAnalyticsPathSafe(& $path) { // stellt sicher, daß der Pfad zum Analytikgerät zulässig ist
	global $importActive,$analyticsAllowedProtocols;
	if ($importActive) {
		return;
	}
	// check if URL is empty, ftp:// or within localAnalyticsPath
	if ($path==="" || localAnalyticsPath=="") {
		return;
	}
	foreach ($analyticsAllowedProtocols as $protocol) {
		if (startswith($path,$protocol."://")) {
			return;
		}
	}
	if (!isSubPath($path,localAnalyticsPath)) { // lokaler Pfad
		$path="";
	}
}

function getOpenReactions() {
	global $person_id;
	if (maxLJnotPrinted>0 && !empty($person_id)) { // check if creation of new entries is allowed
		list($open_reaction_count)=mysql_select_array(array(
			"table" => "reaction_count", 
			"filter" => "person_id=".fixNull($person_id)." AND status<6", 
			"dbs" => "-1", 
		));
		return $open_reaction_count["count"];
	}
	else {
		return TRUE;
	}
}

function getReactionsLeft() {
	if (maxLJnotPrinted>0 && maxLJblockExcess>0) { // check if creation of new entries is allowed
		$open_reaction_count=getOpenReactions();
		if ($open_reactions===TRUE) {
			return TRUE;
		}
		return maxLJnotPrinted+maxLJblockExcess-$open_reaction_count;
	}
	else {
		return TRUE;
	}
}

function getNewReactionPermit() {
	global $person_id;
	if (maxLJnotPrinted>0 && !empty($person_id)) { // check if creation of new entries is allowed
		$open_reaction_count=getOpenReactions();
		if ($open_reactions===TRUE) {
			return array(1,"",-1);
		}
		elseif (maxLJblockExcess>0 && $open_reaction_count>maxLJnotPrinted+maxLJblockExcess) {
			// block
			return array(2,s("error_too_many_open"),0);
		}
		elseif (maxLJwarningExcess>0 && $open_reaction_count>maxLJnotPrinted+maxLJwarningExcess) {
			// warn
			$left=(maxLJnotPrinted+maxLJblockExcess-$open_reaction_count);
			return array(1,s("warning_many_open1").$open_reaction_count.s("warning_many_open2").$left.s("warning_many_open3"),$left);
		}
	}
	else {
		return array(1,"",-1);
	}
}

function getDoubleFindFilter($lab_journal_codes) {
	$retval=array();
	$lab_journal_codes=array_unique($lab_journal_codes); // remove double if no - in LJ code
	for ($a=0;$a<count($lab_journal_codes);$a++) {
		$retval[]="analytical_data_identifier LIKE ".fixStrSQL($lab_journal_codes[$a]."%");
	}
	return join(" OR ",$retval);
}

function backupAnalyticalDataBackend(& $zipdata,$lab_journal_code="",$nr_in_lab_journal="",$analytics_type_name="",$analytics_method_name="",$analytical_data_identifier="") {
	require_once "lib_io.php";
	global $g_settings;
	// Backup anlegen
	if (!empty($g_settings["spz_backup_dir"])) {
		$path=joinIfNotEmpty(array(fixPath($g_settings["spz_backup_dir"]),$lab_journal_code,$nr_in_lab_journal,$analytics_type_name,$analytics_method_name),"/")."/";
		// file_put_contents erstellt den Pfad nicht automatisch
		makePath($path);
		if (!isEmptyStr($analytical_data_identifier)) {
			$path.=$analytical_data_identifier;
		}
		else {
			$path.=time();
		}
		$path=fixPath($path);
		$path.=".tgz";
		//~ die($path);
		return file_put_contents($path,$zipdata);
	}
	return true;
}

function backupAnalyticalData($analytical_data_id) {
	// get all stuff from database
	list($analytical_data)=mysql_select_array(array(
		"table" => "analytical_data_spz", 
		"dbs" => -1, 
		"filter" => "analytical_data.analytical_data_id=".fixNull($analytical_data_id), 
		"limit" => 1, 
	));
	backupAnalyticalDataBackend($analytical_data["analytical_data_blob"],$analytical_data["lab_journal_code"],$analytical_data["nr_in_lab_journal"],$analytical_data["analytics_type_name"],$analytical_data["analytics_method_name"],$analytical_data["analytical_data_identifier"]);
}

function refreshActiveData($db_id,$pk_arr) {
	if (empty($pk_arr)) {
		return;
	}
	if (is_array($pk_arr)) {
		$pk_arr=join(",",$pk_arr);
	}
	for ($a=0;$a<count($_REQUEST["refresh_data"]);$a++) {
		if (startswith($_REQUEST["refresh_data"][$a],$db_id.",")) {
			$_REQUEST["refresh_data"][$a].=",".$pk_arr;
		}
	}
	if (!count($_REQUEST["refresh_data"])) { // aktuellen Datensatz ggf. aktualisieren
		$_REQUEST["refresh_data"]=array($db_id.",".$pk_arr);
	}
}

function getFingerprintSQL(& $molecule,$isLast=false) {
	for ($a=0;$a<14;$a++) {
		$retval.="fingerprint".($a+1)."=".fixNull(intval($molecule["fingerprints"][$a])).",";
	}
	if ($isLast) {
		$retval=substr($retval,0,strlen($retval)-1);
	}
	return $retval;
}

function getPkCondition($table,$pk,$semicolon=true) {
	//~ global $query;
	$retval=" WHERE ";
	$pkName=getLongPrimary($table);
	if (empty($pkName)) {
		return "";
	}
	if (empty($pk) || empty($pkName)) {
		$retval.="FALSE"; // avoid errors or data manipulation
	}
	else {
		$retval.=$pkName."=".$pk;
	}
	if ($semicolon) {
		$retval.=";";
	}
	return $retval;
}

function SQLgetCreateRecord($table,$now,$nvp=false) {
	return SQLgetRecord("created",$table,$now,$nvp);
}

function SQLgetChangeRecord($table,$now,$nvp=false) {
	return SQLgetRecord("changed",$table,$now,$nvp);
}

function SQLgetRecord($action,$table,$now,$nvp=false) {
	global $tables,$db_user;
	$pref="";
	if (!empty($table)) {
		$pref.=$table."_";
	}
	$pref.=$action;
	if ($nvp) {
		return array($pref."_by" => fixStrSQL($db_user), $pref."_when" => "FROM_UNIXTIME(".fixNull($now).")" );
	}
	else {
		return $pref."_by=".fixStrSQL($db_user).",".$pref."_when=FROM_UNIXTIME(".fixNull($now).")";
	}
}

function nvp($field,$handling,$paramHash=array()) {
	// bereitet Daten je nach Typ für das Schreiben in die DB vor und gibt ein Paar name=wert zurück, wobei der Wert aus dem Request stammt. Bei Zahlen und Bools wird auf Zahlsein geprüft und ansonsten durch NULL ersetzt. Bei Texten werden Anführungszeichen gesetzt und HTML-Tags entfernt, um Script-Injection-Angriffe o.ä. zu verhindern. Bei Blobs werden slashes hinzugefügt, bei Datum auf Gültigkeit überprüft und Ungültiges durch 0000-00-00 ersetzt. Zum Schluß wird ein Komma angehängt, sofern nicht isLast gesetzt ist
	// returns fieldname=value,..
	// handling: 0= nixtun, 1= einfügen, 2=fixnull, 3=fixstr, 4=fixblob, 5=fixSet
	return nvpArray($_REQUEST,$field,$handling,$paramHash);
}

function nvpArray(& $hash,$field,$handling,$paramHash=array()) {
	// bereitet Daten je nach Typ für das Schreiben in die DB vor und gibt ein Paar name=wert zurück, wobei der Wert aus $hash stammt. Bei Zahlen und Bools wird auf Zahlsein geprüft und ansonsten durch NULL ersetzt. Bei Texten werden Anführungszeichen gesetzt und HTML-Tags entfernt, um Script-Injection-Angriffe o.ä. zu verhindern. Bei Blobs werden slashes hinzugefügt, bei Datum auf Gültigkeit überprüft und Ungültiges durch 0000-00-00 ersetzt. Zum Schluß wird ein Komma angehängt, sofern nicht isLast gesetzt ist
	// returns fieldname=value,..
	// handling: 0= nixtun, 1= einfügen, 2=fixnull, 3=fixstr, 4=fixblob, 5=fixSet
	if ($handling==0) {
		return "";
	}
	if ($paramHash===true) { // compat
		$paramHash=array("isLast" => true, );
	}
	return $field."=".SQLformat($hash[$paramHash["prefix"].$field],$handling,$paramHash["isLast"]);
}

function getDesiredAction($list_int_name,$UID) {
	return $_REQUEST["desired_action_".$list_int_name."_".$UID];
}

function getValueUID($list_int_name,$UID,$int_name,$group="") {
	$key=$list_int_name."_".$UID."_".$int_name;
	if ($group!=="") {
		$key.="_".$group;
	}
	return $_REQUEST[$key];
}

function nvpUnit($int_name,$unit_int_name,$isLast=false) {
	return $int_name."=(".fixNull($_REQUEST[$int_name])." * (SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch($_REQUEST[$unit_int_name])." LIMIT 1)),".
		nvp($unit_int_name,SQL_TEXT,$isLast);
}

function nvpUIDUnit($list_int_name,$UID,$int_name,$unit_int_name,$isLast=false) {
	return $int_name."=(".fixNull(getValueUID($list_int_name,$UID,$int_name))." * (SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch(getValueUID($list_int_name,$UID,$unit_int_name))." LIMIT 1)),".
		nvpUID($list_int_name,$UID,$unit_int_name,SQL_TEXT,$isLast);
}

function nvpUID($list_int_name,$UID,$int_name,$handling,$isLast=false) { // group not available here
	if ($handling==0) {
		return "";
	}
	return $int_name."=".SQLformat(getValueUID($list_int_name,$UID,$int_name),$handling,$isLast);
}

function nvpi($field,$index,$handling,$isLast=false) {
	if ($handling==0) {
		return "";
	}
	return $field."=".SQLformat($_REQUEST[$field."_".$index],$handling,$isLast);
}

function fixDateSQL($date,$alsoTime=false) {
	return fixStrSQL(fixDate(strip_tags($date),$alsoTime));
}

function SQLformat($value,$handling,$isLast=false) {
	switch ($handling) {
	case 1:
		$retval.=strip_tags($value);
	break;
	case SQL_NUM:
		//~ if (is_array($value)) {
			//~ debug_print_backtrace();
		//~ }
		$retval.=fixNull(trim(strip_tags($value)));
	break;
	case SQL_TEXT:
		//~ $retval.=fixStrSQL(strip_tags($value));
		$retval.=fixStrSQL(makeHTMLSafe($value));
	break;
	case SQL_BLOB:
		$retval.=fixBlob($value);
	break;
	case SQL_SET:
		$retval.=fixStrSQL(@join(",",$value));
	break;
	case SQL_DATE:
		$retval.=fixDateSQL($value);
	break;
	case SQL_DATETIME:
		$retval.=fixDateSQL($value,true);
	break;
	case SQL_URLENCODE:
		//~ $retval.=fixStrSQL(urlencode($value));
		$retval.=fixStrSQL(htmlspecialchars($value));
	break;
	}
	return $retval.($isLast?"":",");
}

function getSafePk($base_table) {
	$archive_table=getArchiveTable($base_table);
	if (empty($archive_table)) {
		return "NULL"; // use auto_increment
	}
	$pkName=getShortPrimary($base_table);
	return "GREATEST((SELECT IFNULL(MAX(max_pk.".$pkName."),0)+1 FROM (SELECT ".$pkName." FROM ".$base_table.") AS max_pk),(SELECT IFNULL(MAX(".$archive_table.".".$pkName."),0)+1 FROM ".$archive_table."))";
}

// For INSERT
function addNvpArray(& $hash,& $arr,$field,$handling) {
	$arr[$field]=SQLformat($hash[$field],$handling,true); // no comma
}

function addNvp(& $arr,$field,$handling) {
	addNvpArray($_REQUEST,$arr,$field,$handling);
}

// switch to name => value pairs, also for Ora Compatibility, $value MUST already be prepared w/ fixNull/fixStrSQL
//~ function getInsertPk($table,$query,$dbObj,$ignoreErrors=false) {
	//~ $result=mysqli_query($dbObj,"INSERT INTO ".$table." ".$query); // FIXME
function getInsertPk($table,& $queryArray,$dbObj,$ignoreErrors=false) {
	$pkName=getShortPrimary($table);
	if (!isset($queryArray[$pkName])) {
		$queryArray[$pkName]=getSafePk($table);
	}
	//~ print_r(debug_backtrace());
	$query="INSERT INTO ".$table." (".join(",",array_keys($queryArray)).") VALUES (".join(",",$queryArray).");";
	
	$mysql_error="";
	$retries=0;
	do {
		$result=mysqli_query($dbObj,$query);
		if ($result) {
			break;
		}
		$mysql_error=mysqli_error($dbObj);
		$retries++;
	} while(empty($mysql_error) && $retries<10); // ignore 10 silent failures by retrying
	
	if (!$result && !$ignoreErrors) {
		echo $mysql_error;
		cancelTransaction($dbObj);
		die($query."X".$mysql_error); // FIXME
		return false;
	}
	else {
		$queryArray=array();
	}
	
	return mysqli_insert_id($dbObj);
}

function startTransaction($dbObj) {
	mysqli_query($dbObj,"START TRANSACTION;");
}

function endTransaction($dbObj) {
	mysqli_query($dbObj,"COMMIT;");
}

function cancelTransaction($dbObj) {
	//~ print_r(debug_backtrace());
	mysqli_query($dbObj,"ROLLBACK;");
}

function performQueries(& $queryArray,$db,$ignoreErrors=false) {
//>>>MST00
        global $MPI_TRANSACTION_AND_COMMIT;
//<<<MST00
	if (empty($queryArray)) {
		$queryArray=array();
		return true;
	}
	if (is_string($queryArray)) {
		$queryArray=array($queryArray);
	}
	// add Transaction tags
//>>>MST00
	if (autoTransaction && $MPI_TRANSACTION_AND_COMMIT ) {
//<<<MST00
		array_unshift($queryArray,"START TRANSACTION;");
		array_push($queryArray,"COMMIT;");
	}
	//~ print_r($queryArray);
	foreach ($queryArray as $query) {
		$retval=mysqli_query($db,$query);
		if (!$retval && !$ignoreErrors) {
			$mysql_error=mysqli_error($db);
			cancelTransaction($db);
			//~ print_r($queryArray);
			dieAsync($mysql_error.$query);
			return false;
		}
	}
	$queryArray=array(); // kill to avoid multiple executions
	return true;
}

function performQueriesDbs(& $dbQueryArray,$ignoreErrors=false) {
	global $db;
	
	if (!is_array($dbQueryArray) || !count($dbQueryArray)) {
		return true;
	}
	
	if (is_array($dbQueryArray)) foreach ($dbQueryArray as $other_db_id => $queryArray) {
		if (!count($queryArray)) { // ignore empty arrays
			continue;
		}
		if ($other_db_id==-1) {
			$dbObj=$db;
			//~ $close_db=false;
		}
		else {
			if (empty($db_data)) {
				continue;
			}
			$dbObj=getForeignDbObj($other_db_id);
			if (!$dbObj) {
				$retval[$other_db_id]=false;
				continue;
			}
			
			//~ $close_db=true;
		}
		$retval[$other_db_id]=performQueries($queryArray,$dbObj,$ignoreErrors);
		//~ if ($close_db) {
			//~ mysqli_close($dbObj); // FIXME
		//~ }
	}
	$dbQueryArray=array();
	return $retval;
}

function getForeignDbObjFromData($db_data) {
	$dbObj=@mysqli_connect($db_data["host"],$db_data["db_user"],$db_data["db_pass"]);
	if (!$dbObj) {
		return false;
	}
	if (switchDB($db_data["db_name"],$dbObj)) {
	//~ if (mysqli_query($dbObj,"USE ".$db_data["db_name"]) { // FIXME
		//~ mysqli_query($dbObj,"SET CHARACTER SET utf8;");
		//~ mysqli_query($dbObj,"SET NAMES utf8;");
		return $dbObj;
	}
	else {
		return false;
	}
}

function getForeignDbObj($idx) {
	global $other_db_data;
	$extDb=& $other_db_data[$idx]["connection"];
	if (!$extDb) {
		$other_db_data[$idx]["connection"]=getForeignDbObjFromData($other_db_data[$idx]);
	}
	return $extDb;
}

function getForeignDbObjFromDBid($db_id) {
	global $other_db_data;
	for ($a=0;$a<count($other_db_data);$a++) {
		if ($other_db_data[$a]["other_db_disabled"]) {
			continue;
		}
		if ($other_db_data[$a]["other_db_id"]==$db_id) {
			return getForeignDbObj($a);
		}
	}
}
?>