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
/*
Funktionen zur Abfrage von Anbieter: VWR, TCI, Acros, Strem, Sial
Funktionen zur Abfrage von emolecules (CAS-Nr), euSDB
Funktionsnamen: AnbieterGetHitlist und AnbieterGetInfo
AnbieterGetHitlist gibt array von Hashes zurück, Indices: catNo, name, addInfo (Dinge wie p.a.,..), infoURL (Link auf Anbieterseite), supplierCode (Merck, Sial,..)
AnbieterGetInfo liefert Hash mit indices wie in db
*/
require_once "lib_formatting.php";
require_once "lib_simple_forms.php";
require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
require_once "lib_constants.php";
require_once "lib_constants_default_settings.php";
require_once "lib_db_query.php";
require_once "lib_db_filter.php";
require_once "lib_http.php";

$ext_crits=array("molecule_name","cas_nr","emp_formula");

function getFunctionHeader() {
	global $code;
	return 'global $suppliers,$noResults,$noConnection,$default_http_options;
	$code="'.$code.'";
	$self=& $suppliers[$code];
	$urls=& $self["urls"];';
}

function getStepFromSupplierCode($code) {
	global $steps,$suppliers;
	if (is_numeric($code)) {
		return intval($code);
	}
	return array_search($code,$steps);
}

function getVendors() {
	global $suppliers;
	$retval=array(
		"int_names" => array(),
		"texts" => array(),
	);
	// get supplier list
	if (is_array($suppliers)) foreach ($suppliers as $code => $supplier) { // include suppliers from supplier_offer
		if ($supplier["vendor"]) {
			$retval["int_names"][]=$code;
			$retval["texts"][]=$supplier["name"];
		}
	}
	return $retval;
}

$noResults=array();
$noConnection=false;
$suppliers=array();

require_once_r(installPath."suppliers");
if (!isset($g_settings["supplier_order"])) {
	$defaults=getDefaultGlobalSettings();
	$g_settings["supplier_order"]=$defaults["supplier_order"];
}
// add suppliers not in the list to the end
autoAddSteps();

setSteps();

$addInfo=array(
	array("own",1000), // unreachable
	array("Acros",28), 
	array("NIST",26), 
	array("cactus",26), // just structure, quality worse than Acros or NIST, but quick
	array("Sial",22), 
	array("Merck",21), 
	array("ChemicalBook",20), 
	array("TCI",19), 
	array("carlroth",17), 
	array("VWR",15), 
	array("Strem",12), 
); // Abfragereihenfolge Details mit Limits
$strSearch=array("cactus","pubchem","NIST","emol"); // Reihenfolge Struktursuche

$simpleExtSearch=array("NIST","emol");

// do not modify the following

function setSteps() {
	global $g_settings,$suppliers,$steps;
	$steps=array();
	for ($a=0;$a<count($g_settings["supplier_order"]);$a++) { // filter invalid steps
		if ($g_settings["supplier_order"][$a]["disabled"]) {
			continue;
		}
		$code=& $g_settings["supplier_order"][$a]["code"];
		if (is_array($suppliers[$code]) && count($suppliers[$code])>0) {
			$steps[]=$code;
		}
	}
}

function autoAddSteps() { // call only if going to global settings
	global $g_settings,$suppliers;
	$known=array();
	for ($a=0;$a<count($g_settings["supplier_order"]);$a++) {
		$known[]=$g_settings["supplier_order"][$a]["code"];
	}
	if (is_array($suppliers)) foreach ($suppliers as $code => $supplier) { // add steps not in list at the end
		if (!$supplier["noExtSearch"] && !in_array($code,$known)) {
			$g_settings["supplier_order"][]=array(
				"code" => $code, 
				"name" => $supplier["name"], 
				//~ "disabled" => true, 
			);
		}
	}
}

function getAddInfoFromSupplier($code,& $molecule,$paramHash=array()) { // daten holen
	global $suppliers;
	$molecule["cas_nr"]=trim($molecule["cas_nr"]);
	if (empty($molecule["cas_nr"]) || count($suppliers[$code])==0) {
		return false;
	}
	$hitlist=$suppliers[$code]["getHitlist"]($molecule["cas_nr"],"cas_nr","ex",$paramHash);
	//~ echo $code;
	//~ print_r($hitlist);
	switch (count($hitlist)) {
	case 0:
		return false;
	break;
	case 1:
		$new_molecule=$hitlist[0];
		if ($suppliers[$code]["alwaysProcDetail"]) {
			$new_molecule=$suppliers[$code]["getInfo"]($hitlist[0]["catNo"]);
		}
	break;
	default:
		$bestHit=$suppliers[$code]["getBestHit"]($hitlist,$molecule["molecule_name"]);
		$new_molecule=$suppliers[$code]["getInfo"]($hitlist[$bestHit]["catNo"]);
	}
	//~ var_dump($new_molecule);
	includeMoleculeData($molecule,$new_molecule);
	return true;
}

function getAddInfo(& $molecule,$silent=false,$paramHash=array()) { // genutzt für Import und Ergänzung der Details
	global $addInfo,$g_settings,$suppliers;
	$paramHash["db_list"]=getDbList();
	set_time_limit(180);
	foreach ($addInfo as $idx => $setting) {
		if (!$suppliers[$setting[0]]) {
			continue;
		}
		if (!$silent) {
			echo $suppliers[$setting[0]]["name"];
		}
		getAddInfoFromSupplier($setting[0],$molecule,$paramHash);
		if (!$silent) {
			echo ": ".count($molecule)."<br>";
		}
		if ($idx<$paramHash["min_number"] 
			|| empty($molecule["default_safety_sheet_by"])
			|| ($g_settings["scrape_alt_safety_sheet"] && empty($molecule["alt_default_safety_sheet_by"]))
			|| (empty($molecule["safety_sym_ghs"]) && empty($molecule["safety_h"]) && empty($molecule["safety_p"]))) {
			continue;
		}
		if ($idx>0 && count($molecule)>$setting[1]) {
			//~ die($setting[0]);
			break;
		}
	}
	extendMoleculeNames($molecule); // Namen aus molecule_names_array neu erzeugen
	
	// generate CMR categories based on R/S/H/P, like in Sciformation
	if (isEmptyStr($molecule["safety_cancer"])) {
		if (stripos($molecule["safety_r"],"45")!==FALSE || stripos($molecule["safety_r"],"49")!==FALSE
				|| stripos($molecule["safety_h"],"350")!==FALSE) {
			$molecule["safety_cancer"]="1";
		} else if (stripos($molecule["safety_r"],"40")!==FALSE
				|| stripos($molecule["safety_h"],"351")!==FALSE) {
			$molecule["safety_cancer"]="2";
		}
	}
	if (isEmptyStr($molecule["safety_mutagen"])) {
		if (stripos($molecule["safety_r"],"46")!==FALSE
				|| stripos($molecule["safety_h"],"340")!==FALSE) {
			$molecule["safety_mutagen"]="1";
		} else if (stripos($molecule["safety_h"],"341")!==FALSE) {
			$molecule["safety_mutagen"]="2";
		}
	}
	if (isEmptyStr($molecule["safety_reprod"])) {
		if (stripos($molecule["safety_r"],"60")!==FALSE || stripos($molecule["safety_r"],"61")!==FALSE || stripos($molecule["safety_r"],"64")!==FALSE
				|| stripos($molecule["safety_h"],"360")!==FALSE || stripos($molecule["safety_h"],"362")!==FALSE) {
			$molecule["safety_reprod"]="1";
		} else if (stripos($molecule["safety_r"],"62")!==FALSE || stripos($molecule["safety_r"],"63")!==FALSE
				|| stripos($molecule["safety_h"],"361")!==FALSE) {
			$molecule["safety_reprod"]="2";
		}
	}
	if (isEmptyStr($molecule["safety_text"])) { // safety_text and safety_danger swapped in relationship to SE
		$corrosive = (stripos($molecule["safety_sym"],"C")!==FALSE || stripos($molecule["safety_sym_ghs"],"5")!==FALSE);
		$environ = (stripos($molecule["safety_sym"],"N")!==FALSE || stripos($molecule["safety_sym_ghs"],"9")!==FALSE);
		if (stripos($molecule["safety_sym_ghs"],"1")!==FALSE // explosive
				|| stripos($molecule["safety_sym"],"E")!==FALSE
				|| stripos($molecule["safety_sym_ghs"],"2")!==FALSE // flammable
				|| stripos($molecule["safety_sym"],"F")!==FALSE
				|| stripos($molecule["safety_sym_ghs"],"3")!==FALSE // oxidizing
				|| stripos($molecule["safety_sym"],"O")!==FALSE
				|| stripos($molecule["safety_sym_ghs"],"6")!==FALSE // toxic
				|| stripos($molecule["safety_sym"],"T")!==FALSE
				|| stripos($molecule["safety_sym_ghs"],"8")!==FALSE // noxious
				|| (stripos($molecule["safety_sym"],"XN")!==FALSE && stripos($molecule["safety_sym_ghs"],"7")===FALSE)
				|| ($corrosive && (stripos($molecule["safety_sym"],"XI")!==FALSE || stripos($molecule["safety_sym_ghs"],"7")!==FALSE)) // http://de.wikipedia.org/wiki/Global_harmonisiertes_System_zur_Einstufung_und_Kennzeichnung_von_Chemikalien#FN_.285.29
				|| ($environ && (stripos($molecule["safety_r"],"59")!==FALSE || stripos($molecule["safety_h"],"420")!==FALSE))) { // http://de.wikipedia.org/wiki/Global_harmonisiertes_System_zur_Einstufung_und_Kennzeichnung_von_Chemikalien#FN_.289.29
			$molecule["safety_text"]=s("safety_sigword_danger");
		} else if ($corrosive
				|| $environ
				|| stripos($molecule["safety_sym_ghs"],"4")!==FALSE) { // gas cylinder
			$molecule["safety_text"]=s("safety_sigword_warning");
		}
	}
}

function includeMoleculeData(& $molecule,$molecule_data) { // daten "einflechten"
	if (!is_array($molecule_data) || count($molecule_data)==0 || ($molecule["cas_nr"] && $molecule["cas_nr"]!=$molecule_data["cas_nr"] )) {
		return;
	}
	foreach($molecule_data as $name => $value) {
		if (is_array($value)) {
			$molecule[$name]=arr_merge($molecule[$name],$molecule_data[$name]);
		}
		elseif (!isset($molecule[$name]) || $molecule[$name]==="") {
			if (is_string($value)) {
				$value=trim($value,"\r\n");
				if (isEmptyStr($value)) { // leere Werte aus Ergebnissatz entfernen
					unset($molecule_data[$name]);
					if ($name=="bp_high") {
						unset($molecule_data["bp_low"]);
						unset($molecule_data["bp_press"]);
						unset($molecule_data["press_unit"]);
					}
					continue;
				}
			}
			elseif (is_null($value)) {
				continue;
			}
			// write to target
			$molecule[$name]=$value;
		}
	}
	// namen zwischen molecule und molecule_data abgleichen, dubletten vermeiden
	if (!count($molecule["molecule_names_array"])) {
		$molecule["molecule_names_array"]=array();
	}
	if (!count($molecule_data["molecule_names_array"])) {
		$molecule_data["molecule_names_array"]=array();
	}
	// namen von molecule_data trimmen
	array_walk($molecule_data["molecule_names_array"],"arrTrim");
	// namen übernehmen
	$molecule["molecule_names_array"]=array_unique(array_merge($molecule_data["molecule_names_array"],$molecule["molecule_names_array"]));
}

function strSearch($molfile,$mode="se") { // $smiles,
	global $strSearch,$suppliers;
	$molecule=readMolfile($molfile,array("smiles" => true));
	$molfile=writeMolfile($molecule);
	//~ $smiles=$molecule["smiles"];
	$smiles=$molecule["smiles_stereo"];
	$hitlist=array();
	if (is_array($strSearch)) foreach ($strSearch as $code) {
		switch ($suppliers[$code]["strSearchFormat"]) {
		case "SMILES":
			$hitlist=$suppliers[$code]["strSearch"]($smiles,$mode);
		break;
		case "Molfile":
			$hitlist=$suppliers[$code]["strSearch"]($molfile,$mode);
		break;
		}
		if (count($hitlist)) {
			return array("hitlist" => $hitlist, "supplier" => $code);
		}
	}
	return false;
}

function getCASfromStr($molfile) {
	global $suppliers;
	$result=strSearch($molfile);
	if ($result===FALSE || count($result["hitlist"])==0) {
		return;
	}
	$bestHit=$suppliers[ $result["supplier"] ]["getBestHit"]($result["hitlist"]);
	if (empty($result["hitlist"][$bestHit]["cas_nr"])) {
		// getCAS-No
		$molecule=$suppliers[ $result["supplier"] ]["getInfo"]($result["hitlist"][$bestHit]["catNo"]);
		$result["hitlist"][$bestHit]["cas_nr"]=$molecule["cas_nr"];
	}
	return $result["hitlist"][$bestHit];
}

function getOrderAlternativeCheckbox(& $row,$price_index=0) { // include amount/quality in $row
	$complete_id=$step."_".$id."_".$price_index;
	$catNo=ifempty($row["price"][$price_index]["catNo"], $row["catNo"]);
	/*if (is_array($row["price"][$price_index])) {
		$price_data=$row["price"][$price_index];
	}
	else {
		$price_data=$row;
	}*/
	$data=array(
		"name" => utf8_encode($row["name"]), 
		"cas_nr" => utf8_encode($row["cas_nr"]), 
		//~ "supplier" => utf8_encode(ifempty($price_data["supplierCode"],$row["supplierCode"])), 
		"catNo" => utf8_encode($catNo), 
		"beautifulCatNo" => utf8_encode(ifempty($row["beautifulCatNo"], $catNo)), 
		//~ "price" => $price_data["price"], 
		//~ "price_currency" => utf8_encode($price_data["currency"]), 
		//~ "addInfo" => utf8_encode($price_data["addInfo"]), 
		//~ "amount" => utf8_encode($price_data["amount"]), 
		//~ "amount_unit" => utf8_encode($price_data["amount_unit"]), 
		"supplier" => utf8_encode(ifempty($row["price"][$price_index]["supplierCode"], $row["supplierCode"])), 
		"price" => $row["price"][$price_index]["price"], 
		"price_currency" => utf8_encode($row["price"][$price_index]["currency"]), 
		"addInfo" => utf8_encode(ifempty($row["price"][$price_index]["addInfo"], $row["addInfo"])), 
		"amount" => utf8_encode(ifempty($row["price"][$price_index]["amount"], $row["amount"])), 
		"amount_unit" => utf8_encode(ifempty($row["price"][$price_index]["amount_unit"], $row["amount_unit"])), 	);
	return getDataCheckbox("order_alternative[]",$data);
	//~ return "<input type=\"checkbox\" name=\"order_alternative[]\" value=\"".htmlspecialchars(serialize($data))."\">";
}

function getInquireLink(&$row,$id) {
	$title=s("inquire_price");
	return "<span id=\"".$row["supplierCode"]."_".$id."\"><a href=\"getPrice.php?id=".$id."&supplier=".$row["supplierCode"]."&extCatNo=".$row["catNo"]."\" target=\"comm2\" class=\"imgButtonSm\"><img src=\"./lib/inquire_price_sm.png\" border=\"0\" alt=\"".$title."\" title=\"".$title."\"></a></span>";
}

function displayPrice($result,$catalogHierarchy=0,$hasPriceList=0) {
	$retval="";
	$price=& $result["price"];
	if (count($price)==0) {
		// do nothing
	}
	elseif ($hasPriceList==1) {
		$retval.=formatPrice($price[0]);
	}
	else {
		$retval.="<table class=\"exttable\"><thead><tr><td>".s("amount")."</td>";
		
		if ($catalogHierarchy==1) {
			$retval.="<td>".s("product_quality")."</td>";
			if ($hasPriceList!=0) {
				$retval.="<td>".s("beautifulCatNo")."</td>";
			}
		}
		
		$retval.="<td>".s("price")."</td>";
		if (capabilities & 1) {
			$retval.="<td>".s("possible_choice")."</td>";
		}
		$retval.="</tr></thead><tbody>";
		
		for ($a=0;$a<count($price);$a++) {
			$retval.="<tr><td>".$price[$a]["amount"]."&nbsp;".$price[$a]["amount_unit"]."</td>";
			if ($catalogHierarchy==1) {
				$retval.="<td>".$price[$a]["addInfo"]."</td>";
				if ($hasPriceList!=0) {
					$retval.="<td>".fixHtmlOut(ifempty($price[$a]["beautifulCatNo"],$price[$a]["catNo"]))."</td>";
				}
			}
			$retval.="<td>".formatPrice($price[$a])."</td>";
			if (capabilities & 1) {
				$retval.="<td>".getOrderAlternativeCheckbox($result,$a)."</td>"; // ,$step,$_REQUEST["id"]
			}
			$retval.="</tr>";
		}
		$retval.="</tbody></table>";		
	}
	return $retval;
}

function getExtResultList($res,$step,$paramHash=array()) {
	global $cache,$suppliers,$steps,$permissions;
	
	$code=$steps[$step];
	$supplier_obj=& $suppliers[$code];
	$id=0;
	$resOut.="<br>";
	if ($res===FALSE) {
		$resOut.=s("no_connection1")."<b>".$supplier_obj["name"]."</b>".s("no_connection2").".<br>";
	}
	elseif (count($res)==0) {
		$resOut.=s("no_results1"); // ."<b>".$supplier_obj["name"]."</b>".s("no_results2").".<br>";
		if (!isEmptyStr($step)) {
			$resOut.="<a href=\"getResultList.php?query=<0>&val0=".$cache["filter_obj"]["vals"][0][0]."&crit0=".$cache["filter_obj"]["crits"][0]."&op0=".$cache["filter_obj"]["ops"][0]."&supplier=".$code."\" target=\"_blank\">";
		}
		$resOut.=getSupplierLogo($supplier_obj);
		if (!isEmptyStr($step)) {
			$resOut.="</a>";
		}
		$resOut.=s("no_results2").".<br>";
	}
	else {
		$resOut.=s("results_from1")." ";
		if (!isEmptyStr($step)) {
			$resOut.="<a href=\"getResultList.php?query=<0>&val0=".$cache["filter_obj"]["vals"][0][0]."&crit0=".$cache["filter_obj"]["crits"][0]."&op0=".$cache["filter_obj"]["ops"][0]."&supplier=".$code."\" target=\"_blank\">";
		}
		$resOut.=getSupplierLogo($supplier_obj);
		if (!isEmptyStr($step)) {
			$resOut.="</a>";
		}
		
		// Überschrift
		$resOut.=s("results_from2")."<br><table class=\"exttable\"><thead><tr><td>".s("molecule_name")."</td>";
		if (!$supplier_obj["catalogHierarchy"]) {
			if ($supplier_obj["hasPurity"]) {
				$resOut.="<td>".s("purity")."</td>";
			}
			if ($supplier_obj["hasPriceList"]!=0) {
				$resOut.="<td>".s("beautifulCatNo")."</td>";
			}
		}
		$resOut.="<td>&nbsp;</td>";
		switch ($supplier_obj["hasPriceList"]) {
		case 1: // always, VWR (single results)
		case 3: // always, ABCR (multiple results)
			$resOut.="<td>".s("price")."</td>";
		case 0: // always, Sial (no prices, but possibility to order)
			if ((capabilities & 1) && $supplier_obj["vendor"] && in_array($supplier_obj["hasPriceList"],array(0,1))) {
				$resOut.="<td>".s("possible_choice")."</td>";
			}
		break;
		case 2: // on request
			$resOut.=addslashes("<td>".s("inquire_price")."</td>");
		break; 
		}
		$resOut.="</tr></head><tbody>";
		
		// Liste
		for ($a=0;$a<count($res);$a++) {
			$resOut.="<tr><td>".fixHtmlOut($res[$a]["name"]).ifnotempty(" (",fixHtmlOut($res[$a]["addInfo"]),")").ifnotempty(" (",fixHtmlOut(trim($res[$a]["amount"]." ".$res[$a]["amount_unit"])),")")."</td>";
			if (!$supplier_obj["catalogHierarchy"]) {
				if ($supplier_obj["hasPurity"]) {
					$resOut.="<td>".fixHtmlOut(ifNotEmpty("",$res[$a]["purity"],"%"))."</td>";
				}
				if ($supplier_obj["hasPriceList"]!=0) {
					$resOut.="<td>".fixHtmlOut(ifempty($res[$a]["beautifulCatNo"],$res[$a]["catNo"]))."</td>";
				}
			}
			$resOut.="<td>";
			if (($permissions & (_chemical_create + _chemical_edit)) > 0
				&& !empty($res[$a]["supplierCode"]) 
				&& !empty($res[$a]["catNo"]) 
				&& !$paramHash["noAddButtons"]) {
				$resOut.="<a href=\"edit.php?table=molecule&supplier=".$res[$a]["supplierCode"]."&extCatNo=".$res[$a]["catNo"]."&cached_query=".$_REQUEST["cached_query"]."&desired_action=new&".getSelfRef(array("~script~","table","cached_query"))."\">".s("use_data")."</a> ";
			}
			$infoURL=$suppliers[$code]["getDetailPageURL"]($res[$a]["catNo"]);
			if (!empty($infoURL)) {
				$resOut.="<a href=\"".$infoURL."\" target=\"_blank\">".s("goto_supplier_page")."</a> ";
			}
			/*
			if (!empty($extResults[$b][$a]["addToCart"]))
				$resOut.=addslashes("<a href=\"".$res[$a]["addToCart"]."\" target=\"_blank\">".s("add_to_cart")."</a> ");
			*/
				switch ($supplier_obj["hasPriceList"]) {
				case 1: // always, VWR (single results)
				case 3: // always, ABCR (multiple results)
					$resOut.="</td><td style=\"text-align:right\">".fixNbsp(displayPrice($res[$a],$supplier_obj["catalogHierarchy"],$supplier_obj["hasPriceList"]));
				case 0: // always, Sial (no prices, but possibility to order)
					if ((capabilities & 1) && $supplier_obj["vendor"] && in_array($supplier_obj["hasPriceList"],array(0,1))) {
						$resOut.="</td><td>".getOrderAlternativeCheckbox($res[$a]); // ,$step,$id
					}
				break;
				case 2: // on request, button for inquiry
					$resOut.="</td><td style=\"text-align:right\">".getInquireLink($res[$a],$id);
				break; 
				}
				$id++;
			}
			$resOut.="</td></tr>";
		$resOut.="</tbody></table>";
	}
	return $resOut;
}

function splitAmount($str) { // 1ML => 1, ml
	$str=strtolower($str);
	preg_match("/([\d\.\,]+)(?:\s*)([A-Za-z]+)/",$str,$retval);
	if (!count($retval)) {
		return $str;
	}
	array_shift($retval);
	$retval[1]=strtolower($retval[1]);
	return $retval;
}
?>