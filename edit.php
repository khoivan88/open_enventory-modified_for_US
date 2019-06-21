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
require_once "lib_db_manip.php";
require_once "lib_db_query.php";
require_once "lib_navigation.php";
require_once "lib_constants.php";
require_once "lib_form_elements.php";
//~ require_once "lib_output.php";
require_once "lib_formatting.php";
require_once "lib_constants_default_dataset.php";
require_once "lib_array.php";
require_once "lib_chem_lang.php";
require_once "lib_simple_forms.php";
require_once "lib_edit.php";

//~ var_dump($_REQUEST);

setGlobalVars();
if ($table=="chemical_storage" && !empty($_REQUEST["db_id"]) && !empty($_REQUEST["mpi_order_item_id"]) ) {
	$barcodeTerminal=true;
}

//~ $page_transparent_params=array("dbs","db_id","pk","fields","view_options","page","per_page","ref_cache_id","ref_reaction_db_id","ref_reaction_id","buttons");
$page_transparent_params=array("dbs","fields","view_options","page","per_page","ref_cache_id","ref_reaction_db_id","ref_reaction_id","buttons");
pageHeader();
activateEditViews($baseTable);

$clientCache=ifempty($query[$table]["clientCache"],$clientCache);

if (!is_array($query[$table])) {
	displayFatalError("fatal_no_table");
}

$mayCreate=mayCreate($baseTable);
$mayWrite=mayWrite($baseTable);

if ($mayWrite[ $_REQUEST["db_id"] ]) { // to create new reaction which is then opened
	echo script;
	list($success,$message,$pks_added)=handleDesiredAction(); // success 1: successful 2: failure 3: interaction (unlock dataset?)
	echo _script;
}

if (empty($success)) {
	$message=strip_tags($_REQUEST["message"]);
}

$cache=readCache($_REQUEST["cached_query"]); // brauchen wir fr zurück-Button

echo "<title>".s("lab_journal_title")." ".$g_settings["organisation_name"]."</title>".
	stylesheet.
	style.
	getFixedStyleBlock().
	_style.
	loadJS(array("DYMO.Label.Framework.3.0.js","chem.js","safety.js","controls.js","jsDatePick.min.1.3.js","forms.js","folder_browser.js","literature.js","sds.js","molecule_edit.js","searchpk.js","subitemlist_helper.js","subitemlist_get.js","subitemlist.js","client_cache.js","edit.js","units.js",),"lib/").
	loadJS(array("wyzz.js")). // wyzz
	script.
	getRefReaction();

$actionText=s("add1").s($table).s("add2");
$backURL="list.php";

// print_r($_REQUEST);

// Normalfall: Bearbeiten eines bestehenden Datensatzes ========================================================
if ((isset($_REQUEST["query"]) || !isEmptyStr($_REQUEST["cached_query"])) && $_REQUEST["desired_action"]!="new") {
	$editMode=true;
	
	$actionText=s("edit1").s($table).s("edit2");
	
	list($result,$dataArray)=handleQueryRequest(); // ,$page,$skip,$per_page,$from_cache
	$totalCount=& $dataArray["totalCount"];
	echo "setRefCacheId(".fixStr($_REQUEST["cached_query"]).",".fixNull($totalCount).",".fixStr($_REQUEST["ref_cache_id"]).");
	setSideNavRadio(\"view_mode\",\"edit\");\n";
	
	$res=makeResultsFlat($result,$table); // ergebnisnummer => db_id,pk
	
	echo "dbIdx=".json_encode($res).";\n";
	
	if (!empty($_REQUEST["select_query"]) && isset($dataArray["goto"])) { // goto dataset specified by select_query
		$_REQUEST["db_id"]=$dataArray["goto"]["db_id"];
		$pk=$dataArray["goto"]["pk"];
		unset($_REQUEST["goto_page"]);
	}

	if (isset($_REQUEST["goto_page"])) { // goto_page
		$page=$_REQUEST["goto_page"];
		if ($page<0) {
			$page+=count($res);
		}
		$page=constrainVal($page,0,count($res)-1);
		$_REQUEST["db_id"]=ifnotset($_REQUEST["db_id"],$res[$page]["db_id"]);
		$pk=ifnotset($pk,$res[$page]["pk"]);
	}
	else { // db_id pk
		for ($a=0;$a<count($res);$a++) { // finde aktives ergebnis
			if ($res[$a]["db_id"]==$_REQUEST["db_id"] && $res[$a]["pk"]==$pk) {
				$page=$a;
				break;
			}
		}
	}
	$page+=0;
	$_REQUEST["db_id"]=$res[$page]["db_id"];
	$pk=$res[$page]["pk"];
	
	// put +-5 datasets into cache
	// print_r($result);
	$result=array_slice_r($result["db"],max(0,$page-$clientCache["detail_cache_range"]),2*$clientCache["detail_cache_range"]+1);
	$result=getFulldataFromPrimaryKeys(array(
		//~ "table" => $baseTable, 
		"table" => $table, 
		"flags" => QUERY_EDIT, 
	), $result);
	
	echo "updateInProgress=false;
refUpdateInProgress=false;
actIdx=".$page.";
detail_cache_range=".$clientCache["detail_cache_range"].";
fast_cache_range=".$clientCache["fast_cache_range"].";
min_reload=".$clientCache["min_reload"].";
force_distance=".$clientCache["force_distance"].";
fastmodeWait=".$clientCache["fastmodeWait"].";
fastmodeInt=".$clientCache["fastmodeInt"].";
scrollInt=".scrollInt.";
maxDatasets=".$clientCache["maxDatasets"].";
";

}
// data from supplier ============================================================================
elseif (!empty($_REQUEST["supplier"]) && !empty($_REQUEST["extCatNo"])) {
	require_once "lib_supplier_scraping.php";
	
	$result[0]=getDefaultDataset($table);
	$result[0]=$suppliers[ $_REQUEST["supplier"] ]["getInfo"]($_REQUEST["extCatNo"]);
	extendMoleculeNames($result[0]);
	$result[0]["db_id"]=-1;
	$backURL="searchExt.php";
}
// neue Bestellung einer Einzelchemikalie ================================================================
elseif (
	$baseTable=="chemical_order" 
	&& (
		(
			!empty($_REQUEST["db_id"]) 
			&& !empty($_REQUEST["supplier_offer_id"])
		) 
		|| count($_REQUEST["order_alternative"])
	)
) {
	$result[0]=getDefaultDataset($table);
	
	if (!empty($_REQUEST["supplier_offer_id"])) {
		$result[0]["order_alternative"]=mysql_select_array(array(
			"dbs" => $_REQUEST["db_id"], 
			"table" => "supplier_offer", 
			"filter" => "supplier_offer.supplier_offer_id=".fixNull($_REQUEST["supplier_offer_id"]), 
			"flags" => QUERY_CREATE, // faster
			"limit" => 1, 
		)); // for address and supplier_codes
		$result[0]["order_alternative"][0]["number_packages"]=1;
		$result[0]["order_alternative"][0]["package_amount"]=$result[0]["order_alternative"][0]["so_package_amount"];
		$result[0]["order_alternative"][0]["package_amount_unit"]=$result[0]["order_alternative"][0]["so_package_amount_unit"];
		$result[0]["order_alternative"][0]["price"]=$result[0]["order_alternative"][0]["so_price"];
		$result[0]["order_alternative"][0]["price_currency"]=$result[0]["order_alternative"][0]["so_price_currency"];
		$result[0]["order_alternative"][0]["vat_rate"]=$result[0]["order_alternative"][0]["so_vat_rate"];
	}
	else {
		$backURL="searchExt.php";
		for ($a=0;$a<count($_REQUEST["order_alternative"]);$a++) {
			$result[0]["order_alternative"][$a]=unserialize(stripslashes($_REQUEST["order_alternative"][$a]));
			
			if (!empty($result[0]["order_alternative"][$a]["amount_unit"])) {
				$result[0]["order_alternative"][$a]["package_amount"]=$result[0]["order_alternative"][$a]["amount"];
				$result[0]["order_alternative"][$a]["package_amount_unit"]=$result[0]["order_alternative"][$a]["amount_unit"];
			}
			else { // auto split, deprecated
				list(
					$result[0]["order_alternative"][$a]["package_amount"], 
					$result[0]["order_alternative"][$a]["package_amount_unit"], 
				)=explode(" ",$result[0]["order_alternative"][$a]["amount"],2);
			}
			
			$result[0]["order_alternative"][$a]["vat_rate"]=$g_settings["default_vat_rate"];
			$result[0]["order_alternative"][$a]["number_packages"]=1;
			if ($result[0]["order_alternative"][$a]["beautifulCatNo"] && $result[0]["order_alternative"][$a]["price"]) { // catNo and price is already defined
				$result[0]["order_alternative"][$a][READONLY]=true;
			}
		}
	}
	//~ var_dump($result);die();
}
// neue Sammelbestellung ===========================================================================
elseif ($baseTable=="order_comp" && !empty($_REQUEST["institution_id"])) {
	// mache liste mit allen Bestellungen für supplier_codes von institution_id, oben Adreßfelder für Absender (wir) und Anbieter (institution_id)
	list($result[0])=mysql_select_array(array(
		"dbs" => "-1", 
		"table" => "vendor", 
		"filter" => "institution.institution_id=".fixNull($_REQUEST["institution_id"]), 
		"flags" => QUERY_CREATE, 
		"limit" => 1, 
	)); // for address and supplier_codes
	
	$result[0]=arr_merge($result[0],getDefaultDataset($table));
}
// new offer for existing molecule ==========================================================
elseif ($table=="supplier_offer" && !empty($_REQUEST["molecule_id"])) {
	$result=mysql_select_array(array(
		"dbs" => $_REQUEST["db_id"], 
		"table" => "molecule", 
		"filter" => "molecule.molecule_id=".fixNull($_REQUEST["molecule_id"]), 
		"limit" => 1, 
		"flags" => QUERY_EDIT, 
	));
	$result[0]=arr_merge(getDefaultDataset($table),$result[0]);
	if ($_REQUEST["db_id"]!="-1") { // molekül aus fremder db
		//~ $result[0]["molecule_id"]=""; // should be enough
		$result[0]["action_molecule"]="add";
		//~ $_REQUEST["db_id"]=-1;
		$result[0]["db_id"]="-1";
	}
}
// Produkt aus Reaktion in Bestand übernehmen, Daten per JS aus opener holen ==============================================
elseif ($table=="chemical_storage" && !empty($_REQUEST["from_reaction_id"]) && !empty($_REQUEST["list_int_name"]) && !empty($_REQUEST["UID"])) {
	//~ if (true || !empty($_REQUEST["molecule_id"]) || !empty($_REQUEST["smiles_stereo"])) {
	if (!empty($_REQUEST["molecule_id"]) || !empty($_REQUEST["smiles_stereo"])) {
		$filter="molecule.molecule_id=".fixNull($_REQUEST["molecule_id"]);
		$order_obj0_field="(molecule.molecule_id=".fixNull($_REQUEST["molecule_id"]).")";
		if (!empty($_REQUEST["smiles_stereo"])) {
			$filter.=" OR molecule.smiles_stereo LIKE BINARY ".fixStrSQL($_REQUEST["smiles_stereo"]);
			$order_obj0_field.="+(molecule.smiles_stereo LIKE BINARY ".fixStrSQL($_REQUEST["smiles_stereo"]).")*2";
		}
		$result=mysql_select_array(array(
			"dbs" => "-1", 
			"table" => "molecule", 
			"filter" => $filter, 
			"limit" => 1, 
			"flags" => QUERY_EDIT, 
			"order_obj" => array(
				array("field" => $order_obj0_field, "order" => "DESC")
			), 
		));
		if (count($result)) {
			$result[0]=arr_merge(getDefaultDataset($table),$result[0]);
		}
	}
	if (count($result)==0) {
		$result[0]["molecule_id"]=""; // important
		$rc_to_molecule=true; // transfer data from reaction
	}
	$result[0]["db_id"]=-1;
	$result[0]["from_reaction_id"]=fixNull($_REQUEST["from_reaction_id"]);
	$result[0]["lab_journal_id"]=fixNull($_REQUEST["lab_journal_id"]);
	$result[0]["owner_person_id"]=$person_id;
	$rc_to_chemical_storage=true;
	echo "var opener_list_int_name=".fixStr($_REQUEST["list_int_name"]).",opener_UID=".fixStr($_REQUEST["UID"]).";\n";
}
// new gebinde on existing molecule ================================================================
elseif ($table=="chemical_storage" && (!empty($_REQUEST["molecule_id"]) || !empty($_REQUEST["chemical_storage_id"])) ) {
	if (!empty($_REQUEST["chemical_storage_id"])) {
		$result=mysql_select_array(array(
			"dbs" => $_REQUEST["db_id"], 
			"table" => "chemical_storage", 
			"filter" => "chemical_storage.chemical_storage_id=".fixNull($_REQUEST["chemical_storage_id"]), 
			"limit" => 1, 
			"flags" => QUERY_EDIT, 
		));
		// dont copy the following
		$result[0]["chemical_storage_id"]="";
		$result[0]["chemical_storage_barcode"]="";
		$result[0]["chemical_created_by"]="";
		$result[0]["chemical_created_when"]="";
		$result[0]["chemical_changed_by"]="";
		$result[0]["chemical_changed_when"]="";
		
		unset($result[0]["tmd"]);
		unset($result[0]["tmd_unit"]);
		
		resetSDB($result);
	}
	elseif (!empty($_REQUEST["molecule_id"])) {
		$result=mysql_select_array(array(
			"dbs" => $_REQUEST["db_id"], 
			"table" => "molecule", 
			"filter" => "molecule.molecule_id=".fixNull($_REQUEST["molecule_id"]), 
			"limit" => 1, 
			"flags" => QUERY_EDIT, 
		));
		
		// reload from supplier
		resetSDB($result);
	}
	else {
		$result[0]=array();
	}
	$result[0]=arr_merge(getDefaultDataset($table),$result[0]);
	if ($_REQUEST["db_id"]!="-1") { // molekül aus fremder db
		//~ $result[0]["molecule_id"]=""; // should be enough
		$result[0]["action_molecule"]="add";
		//$result[0]["default_safety_sheet_url"]="-".$result["default_safety_sheet_url"];
		//~ $_REQUEST["db_id"]=-1;
		$result[0]["db_id"]="-1";
		// $_REQUEST["molecule_id"]="";
		// $result[0]["molecule_id"]="";
	}
}
elseif (
	$table=="chemical_storage" 
	&& (
			(!empty($_REQUEST["db_id"]) && !empty($_REQUEST["mpi_order_item_id"])) 
			|| !empty($_REQUEST["order_uid"])
		) 
	) {
	// get info
	if (!empty($_REQUEST["mpi_order_item_id"])) {
		$result=mysql_select_array(array(
			"dbs" => $_REQUEST["db_id"], 
			"table" => "mpi_order_item", 
			"filter" => "mpi_order_item.mpi_order_item_id=".fixNull($_REQUEST["mpi_order_item_id"]), 
			"limit" => 1, 
			"flags" => QUERY_EDIT, 
		));
		$result[0]["migrate_id_mol"]=$result[0]["bessi"];
		$result[0]["migrate_id_cheminstor"]=$result[0]["bessi"];
	}
	elseif (!empty($_REQUEST["order_uid"])) {
		$result=mysql_select_array(array(
			"table" => "chemical_order", 
			"filter" => "chemical_order.order_uid LIKE BINARY ".fixBlob($_REQUEST["order_uid"]), 
			"limit" => 1, 
			"flags" => QUERY_EDIT, 
		));
		$result[0]["molecule_names_edit"]=$result[0]["name"];
		$result[0]["amount"]=$result[0]["package_amount"];
		$result[0]["amount_unit"]=$result[0]["package_amount_unit"];
		$result[0]["order_uid"]=$result[0]["order_uid"];
	}
	
	if (!empty($result[0]["cas_nr"])) {
		// check if we have molecule
		list($molecule)=mysql_select_array(array(
			"dbs" => -1, 
			"table" => "molecule", 
			"filter" => "molecule.cas_nr=".fixStrSQL($result[0]["cas_nr"]), 
			"limit" => 1, 
			"flags" => QUERY_EDIT, 
		));
	}
	
	$result[0]=arr_merge(getDefaultDataset($table),$result[0]);
	
	// reset this stuff
	$result[0]["db_id"]=-1;
	$result[0]["chemical_storage_id"]=""; // new one
	$result[0]["actual_amount"]=$result[0]["amount"]; // assume we buy full bottles
	
	resetSDB($result);
	
	// something found
	if (!empty($molecule["molecule_id"])) { // add to molecule
		$result[0]=arr_merge($result[0],$molecule);
	}
	elseif (!empty($molecule["cas_nr"])) { // start reading extData for cas_nr
		$autoStartReadExt=true;
	}
}
elseif ($table=="chemical_storage" && !empty($_REQUEST["db_id"]) && !empty($_REQUEST["transferred_to_db_id"]) ) {
	// get info
	$result=mysql_select_array(array(
		"dbs" => $_REQUEST["db_id"], 
		"table" => "chemical_storage_barcode", 
		"filter" => "chemical_storage.chemical_storage_id=".fixNull($_REQUEST["transferred_to_db_id"]), 
		"limit" => 1, 
		"flags" => QUERY_EDIT, 
	));
	
	// check if we have molecule
	list($molecule)=mysql_select_array(array(
		"dbs" => -1, 
		"table" => "molecule", 
		"filter" => "molecule.cas_nr=".fixStrSQL($result[0]["cas_nr"]), 
		"limit" => 1, 
		"flags" => QUERY_EDIT, 
	));
	
	$result[0]=arr_merge(getDefaultDataset($table),$result[0]);
	
	// reset this stuff
	$result[0]["db_id"]=-1;
	$result[0]["chemical_storage_id"]=""; // new one
	$result[0]["from_reaction_id"]="";
	$result[0]["storage_id"]="";
	$result[0]["owner_person_id"]="";
	$result[0]["borrowed_by_db_id"]="";
	$result[0]["borrowed_by_person_id"]="";
	$result[0]["transferred_to_db_id"]="";
	
	resetSDB($result);
	
	// something found
	if (!empty($molecule["molecule_id"])) { // add to molecule
		$result[0]=arr_merge($result[0],$molecule);
	}
	else { // start reading extData for cas_nr
		$autoStartReadExt=true;
	}
}
// neues Spektrum, Analytikart voreingestellt ================================================================
elseif ($table=="analytical_data" && isset($_REQUEST["analytics_type_name"])) {
	list($analytics_type)=mysql_select_array(array(
		"dbs" => "-1", 
		"table" => "analytics_type", 
		"filter" => "analytics_type.analytics_type_name LIKE ".fixStr($_REQUEST["analytics_type_name"]), 
		"limit" => 1, 
	));
	$result[0]=getDefaultDataset($table);
	if (!isEmptyStr($analytics_type["analytics_type_id"])) {
		$result[0]["analytics_type_id"]=$analytics_type["analytics_type_id"];
	}
}
// neues Gerät, Analytikart voreingestellt ================================================================
elseif ($table=="analytics_device" && isset($_REQUEST["analytics_type_id"])) {
	list($analytics_type)=mysql_select_array(array(
		"dbs" => "-1", 
		"table" => "analytics_type", 
		"filter" => "analytics_type.analytics_type_id=".fixNull($_REQUEST["analytics_type_id"]), 
		"limit" => 1, 
	));
	$result[0]=getDefaultDataset($table);
	$result[0]["analytics_type_id"]=$analytics_type["analytics_type_id"];
}
// neue Methode, Gerät voreingestellt ================================================================
elseif ($table=="analytics_method" && isset($_REQUEST["analytics_device_id"])) {
	list($analytics_device)=mysql_select_array(array(
		"dbs" => "-1", 
		"table" => "analytics_device", 
		"filter" => "analytics_device.analytics_device_id=".fixNull($_REQUEST["analytics_device_id"]), 
		"limit" => 1, 
	));
	$result[0]=getDefaultDataset($table);
	$result[0]=arr_merge($result[0],$analytics_device);
}
// neues Laborjournal, Person voreingestellt ================================================================
elseif ($table=="lab_journal" && isset($_REQUEST["person_id"])) {
	$result[0]=getDefaultDataset($table);
	$result[0]["person_id"]=$_REQUEST["person_id"];
}
// Bestellung übernehmen ================================================================
elseif ($table=="accepted_order" && !empty($_REQUEST["order_uid"])) {
	list($chemical_order)=mysql_select_array(array(
		"table" => "chemical_order", 
		"filter" => "chemical_order.order_uid LIKE BINARY ".fixBlob($_REQUEST["order_uid"]), 
		"limit" => 1, 
		"flags" => QUERY_EDIT, 
	)); // get existing values
	
	// get institution for supplier
	list($institution)=mysql_select_array(array(
		"table" => "institution", 
		//~ "filter" => "institution_code.supplier_code LIKE BINARY ".fixBlob($chemical_order["supplier"]), 
		"filter" => "institution_code.supplier_code=".fixBlob($chemical_order["supplier"]), 
		"limit" => 1, 
		"flags" => QUERY_EDIT, 
	));
	
	$result[0]=getDefaultDataset($table);
	$result[0]["ordered_by_username_cp"]=$chemical_order["ordered_by_username"];
	$result[0]["order_uid_cp"]=$chemical_order["order_uid"];
	$result[0]["customer_order_date_cp"]=$chemical_order["customer_order_date"];
	$result[0]["order_cost_centre_cp"]=$chemical_order["order_cost_centre"];
	$result[0]["order_acc_no_cp"]=$chemical_order["order_acc_no"];
	$result[0]["selected_alternative_id"]=$chemical_order["customer_selected_alternative_id"];
	$result[0]["vendor_id"]=$institution["institution_id"];
	//~ $result[0][""]=$chemical_order[""];
	//~ $result[0]=arr_merge($result[0],array_key_filter($chemical_order,array("name","cas_nr")));
	//~ var_dump($result[0]);
	//~ var_dump($chemical_order);die();
	$result[0]=arr_merge($result[0],$chemical_order,true);
	$result[0]=arr_merge($result[0],$institution,true);
	$result[0]["db_id"]="-1";
}
// completely new dataset ===========================================================================
else {
	$result[0]=getDefaultDataset($table);
	//~ print_r($result);
}

if (!$mayCreate[-1] && !$editMode) {
	exit();
}
	
getViewHelper($table);

echo "var ".addParamsJS().",allowCreate=".json_encode($mayCreate).",allowWrite=".json_encode($mayWrite).",fastMode=false,fastCount=0,table=".fixStr($baseTable).",writeCapabilities=0,listURL=".fixStr($backURL."?".getSelfRef(array("~script~","page","fields","ref_reaction_db_id","ref_reaction_id"))).",maxKleinauftrag=".fixNull($g_settings["maxKleinauftrag"]).",delTimeout;\n"; // make definition of views for active table available to JS

switch ($baseTable) {

//~ case "chemical_order": // darf ggf. remote schreiben
	//~ echo "writeCapabilities=2;\n";
//~ break;

case "settlement": // für den Druck benötigt
	echo "var ausgabe_name=".fixStr(ausgabe_name).
		",cost_centre=".json_encode(mysql_select_array(array(
			"table" => "cost_centre", 
			"dbs" => -1, 
			"order_by" => "cost_centre", 
		))).";\n";
break;

case "molecule":
	echo "localizedString[\"delWarning\"]=".fixStr(s("delWarningMolecule")).";\n";
break;

}

echo "function barcodeRead(barcode) {
".getJSbarcodeHandling(false).<<<END
	// barcodes für datensatz
	var url="checkBarcodeAsync.php?tables[]="+table+"
END;
if ($table=="molecule" && $_REQUEST["desired_action"]=="new") {
	echo "&tables[]=chemical_storage";
}
echo <<<END
&barcode="+barcode+"&db_id="+a_db_id+"&pk="+a_pk+"&"+getSelfRef(["~script~","table"]);
	//~ alert(url);
	window.frames["comm"].location.href=url;
}

END;
echo "var perPage=".fixNull($_REQUEST["per_page"]).",selectActive=".(in_array($table,$selectTables)?"true":"false").",oldFields=".fixStr($_REQUEST["fields"]).";
setTitle();
";
if ($editMode) {
	echo "window.onbeforeunload=saveOpenDataset;";
}

echo _script."
</head>
<body class=\"mainbody\">".
getHelperTop()."
<div id=\"browsenav\">";

if ($editMode) {
	// < 123 >
	if ($baseTable=="reaction") {
		$right[]=getEditButton("laws");
	}
	
	$right[]=getMessageButton();
	
	if ($_REQUEST["style"]=="lj") {
		$right[]=getInventoryButton();
	}

	if (count($res)) {
		$center.="<form onsubmit=\"gotoNum();return false\" name=\"browseForm\">
		<a href=\"Javascript:gotoFirst()\"><img src=\"lib/1st.png\" id=\"imgfirst\" width=\"16\" height=\"19\" border=\"0\"".getTooltip("btn_1st")."></a> 
		<a href=\"Javascript:gotoPrev()\"><img src=\"lib/prev.png\" id=\"imgprev\" width=\"16\" height=\"18\" border=\"0\"".getTooltip("btn_prev")."></a> 
		<input type=\"text\" name=\"idx\" id=\"idx\" size=\"5\" maxlength=\"10\" title=".fixStr(s("select_dataset"))." onKeydown=\"idxHandleKeydown(event)\" onKeyup=\"idxHandleKeyup(event)\" autocomplete=\"off\">/<span id=\"totalCount\"></span>
		<a href=\"Javascript:gotoNext()\"><img src=\"lib/next.png\" id=\"imgnext\" width=\"16\" height=\"18\" border=\"0\"".getTooltip("btn_next")."></a> 
		<a href=\"Javascript:gotoLast()\"><img src=\"lib/last.png\" id=\"imglast\" width=\"16\" height=\"19\" border=\"0\"".getTooltip("btn_last")."></a> ".
		getHiddenSubmit();
		
		$right[]="<a href=\"Javascript:showPrintMenu()\" class=\"imgButtonSm\"><img src=\"lib/print_sm.png\" border=\"0\"".getTooltip("print")."></a>";
		
		$buttons_ro_other="<table id=\"buttons_ro_other\" style=\"display:none\" class=\"noborder\"><tr>";
		$buttons_ro="<table id=\"buttons_ro\" class=\"noborder\"><tr>";
		
		if ($baseTable!="chemical_storage" || ($permissions & (_chemical_edit | _chemical_edit_own)) > 0) {
			$buttons_ro.="<td><a href=\"Javascript:startEditMode()\" class=\"imgButtonSm\" id=\"btn_edit\"><img src=\"lib/edit_sm.png\" border=\"0\"".getTooltip("edit")."></a></td>";
		}
		
		if ($baseTable=="reaction") { // new for this LJ
			$buttons_ro_other.="<td><a href=\"javascript:void getNewReaction()\" class=\"imgButtonSm\" id=\"buttons_add\"><img src=\"lib/".$table."_sm.png\" border=\"0\"".getTooltip("new").">+</a></td>";
			if (count($settings["include_in_auto_transfer"])) {
				$buttons_ro.=getEditButton("auto_trans");
			}
			$buttons_ro_other.=getEditButton("copy_reaction").
				getEditButton("compare_reaction").
				getEditButton("reaction_pdf");
		}
		else { // new, do not use special icon, **baseTable**
			if ($baseTable!="chemical_storage" || ($permissions & (_chemical_edit | _chemical_create)) > 0) {
				$buttons_ro_other.="<td><nobr><a href=\"".getSelfRef(array("table"))."&table=".$baseTable."&desired_action=new\" class=\"imgButtonSm\" id=\"buttons_add\"><img src=\"lib/".$baseTable."_sm.png\" border=\"0\"".getTooltip("new").">+</a></nobr></td>";
			}
		}
		
		if (!$tables[$baseTable]["noDelete"]) {
			$buttons_ro.=getEditButton("del");
		}
		if ($editMode && file_exists("forms/dymo/".$baseTable.".label")) {
			if ($baseTable=="reaction") {
				// allows to print labels for closed experiments
				$buttons_ro_other.=getEditButton("dymo");
			}
			else {
				// no labels for other group's chemicals
				$buttons_ro.=getEditButton("dymo");
			}
		}
		
		if (isset($tables[$baseTable]["merge"])) {
			$mergeParams=array(
				"int_name" => "merge", 
				"table" => $table, 
				"allowLock" => "never", 
				"allowNone" => true, 
				"setNoneText" => s("cancel"), 
				"forMerge" => true, 
				"pkName" => $pk_name, 
				"nameField" => $tables[$baseTable]["merge"]["nameField"], 
			);
			$buttons_ro.=getEditButton("merge");
		}
			
		if (!$filterOff) {
			$buttons_ro_other.=getEditButton("filter_off");
		}
		if ($baseTable=="reaction") {
			$buttons_ro_other.=getEditButton("this_lab_journal").
				getEditButton("this_project");
		}
		
		$buttons_rw="<table id=\"buttons_rw\" class=\"noborder\"><tr>".
			getEditButton("cancel_edit").
			getEditButton("save");
		
		if (hasTableArchive($baseTable)) {
			$buttons_rw.=getEditButton("save_version");
			$buttons_ro_other.=getEditButton("versions_list");
		}
		
		// Spezialknöpfe
		switch ($baseTable) {
		
		case "accepted_order":
			$buttons_ro.=getEditButton("set_order_status"); // depending on central_order_status
			$buttons_ro_other.=getEditButton("add_package");
		break;
		
		case "chemical_order":
			$buttons_ro_other.=
				getEditButton("accept_order"). // if customer_order_status=3
				getEditButton("goto_settlement");
			
			$buttons_ro.=
				getEditButton("approve_chemical_order"). // if customer_order_status=2
				getEditButton("co_to_chemical_storage"). // if chemical_storage=null
				getEditButton("goto_chemical_storage"). // if chemical_storage=something
				getEditButton("add_package");
		break;
		
		case "rent":
			$buttons_ro_other.=getEditButton("goto_settlement").
				getEditButton("return_rent");
		break;
		
		case "supplier_offer":
			$buttons_ro_other.=getEditButton("goto_molecule").
				getEditButton("new_chemical_order");
		break;
		
		case "chemical_storage":
			// gehe zu Molekül
			$buttons_ro_other.=
				getEditButton("goto_molecule").
				getEditButton("undelete");
			
			 if (!empty($person_id)) { // Ausleihen/Zurückgeben
				$buttons_ro_other.=getEditButton("borrow");
			}
		// kein break;
		case "molecule":
			// Bezugsquellen
			$buttons_ro_other.=getEditButton("search_commercial").
				getEditButton("add_package");
		break;
		
		case "order_comp":
			// gehe zu Anbieter
			$buttons_ro_other.=getEditButton("goto_institution");
		
		break;
		
		case "project":
			$buttons_rw.=getEditButton("add_project_literature").getEditButton("add_literature_doi");
			// literature_navigator
			echo "<div id=\"litnav\" style=\"position:absolute;top:100px;right:20px\" onMouseover=\"showLitNav(this);\" onMouseout=\"hideOverlay()\"><img src=\"lib/specnav.png\"></div>";
		break;
		
		case "person":
		case "storage":
			$buttons_ro.="<td>".getPrintBarcodesButton($baseTable)."</td>";
		break;
		
		case "reaction":
			if (!$g_settings["show_rc_stoch"]) {
				$buttons_rw.=getEditButton("scale_reaction");
			}
			$buttons_rw.=getEditButton("add_analytical_data").getEditButton("add_literature_doi");
			// analytics_navigator
			echo "<div id=\"specnav\" style=\"position:absolute;top:100px;right:20px\" onMouseover=\"showSpecNav(this);\" onMouseout=\"hideOverlay()\"><img src=\"lib/specnav.png\"></div>";
		break;
		
		}
		
		// Auswählen
		if (in_array($table,$selectTables)) {
			$buttons_ro_other.=getEditButton("do_select");
		}
		
		// checkbox zum Auswählen
		$buttons_ro_other.="<td><input type=\"checkbox\" id=\"sel\" onClick=\"setSingleSelect()\" title=".fixStr(s("do_select"))."></td>";
		
		// Anzeige db_name
		$buttons_ro_other.="<td>&nbsp;
	<span id=\"show_db_beauty_name\"></span>
	</td>";
		
		// Keep open check
		//~ $buttons_rw.="<td>".showCheck(array("int_name" => "keep_edit_open", "noChangeEffect" => true))."</td>";
		
		$termTable="</tr></table>";
		$buttons_ro.=$termTable;
		$buttons_rw.=$termTable;
		$buttons_ro_other.=$termTable;
		$left=array($buttons_ro,$buttons_rw,$buttons_ro_other);
	}
	
	//~ $left[]="<span id=\"feedback_message\"></span>";
	$center.="<span id=\"info_box\"></span>";

	echo getAlignTable($left,$center,$right)."<span id=\"feedback_message\"></span>";
	
	// back button
	echo "<table id=\"tab_bar\" cellspacing=\"0\"><tr>".
		getEditViewTabs($table). // Detailansichten
		getViews($table,false). // Listenansichten
		getExtTabs($table).
	"</tr></table>";

	echo "</form>".gutCustomMenu($table);
	
}
else { // NEU
	echo "<table class=\"noprint\"><tr>
			<td><a href=\"Javascript:goBack()\" class=\"imgButtonSm\"><img src=\"lib/list_sm.png\" border=\"0\"".getTooltip("back")."></a></td>
			<td><a id=\"btn_create\" href=\"Javascript:saveDataset()\" class=\"imgButtonSm\"><img src=\"lib/";
	if (!empty($_REQUEST["tableSelect"])) {
		echo "select_sm.png\"".getTooltip("add_and_select_dataset");
	}
	else {
		echo "save_sm.png\"".getTooltip("add_dataset");
	}
	echo " border=\"0\"></a></td>
		</tr></table>
		<span id=\"feedback_message\"></span>";
}
//~ print_r($result);
echo "
</div><div id=\"browsemain\" onScroll=\"saveScrollPos();\">
<span id=\"feedback_message2\"></span>".$message;

if ($editMode && isset($tables[$baseTable]["merge"])) { // suchfeld für 2. datensatz
	echo getPk($mergeParams);
}

if ($editMode) {
	$desired_action="unlock";
}
else {
	$desired_action="add";
}
	
echo "<form name=\"main\" id=\"main\" action=\"editAsync.php?".getSelfRef(array("~script~","cached_query"))."\" method=\"post\" target=\"edit\" enctype=\"multipart/form-data\" onSubmit=\"return false;\"><span id=\"additionalFields\" style=\"display:none\"></span>".
showHidden(array("int_name" => "desired_action", "value" => $desired_action)).
showHidden(array("int_name" => "version_before", )).
showHidden(array("int_name" => "version_comment_before", )).
showHidden(array("int_name" => "version_after", )).
showHidden(array("int_name" => "version_comment_after", )).
showHidden(array("int_name" => "ignore", ));

// 2 iframes avoids conflicts between the different functions
showCommFrame(); // for molecule and pk_search
showCommFrame(array("name" => "edit")); // for async ops (reload data, save data)

// echo "<div id=\"debug\" style=\"width:100%;heigth:100px;overflow:scroll\"></div>";

$paramHash=array("text" => $actionText, "editMode" => $editMode, "table" => $baseTable, );

if ($editMode && count($res)==0) { // keine Ergebnisse
	echo s("no_results").
script."
activateEditView();
showMessage(".fixStr(s("no_results")).");
"._script;

	// spell checking
	switch($baseTable) {
	case "chemical_storage":
	case "molecule":
	case "supplier_offer":
		if (!count($res)) {
			displayFixedQuery();
		}
	break;
	}
	echo "</form></div>".
		getHelperBottom().
		getLawMenu();
}
else {
	switch($baseTable) {
	case "analytical_data":
		require_once "lib_edit_analytical_data.php";
		if (in_array($baseTable,$selectTables)) {
			$paramHash["reducedMode"]=true;
		}
		echo showAnalyticalDataEditForm($paramHash);
	break;
	case "analytics_device":
		require_once "lib_edit_analytics_device.php";
		echo showAnalyticsDeviceEditForm($paramHash);
	break;
	case "analytics_method":
		require_once "lib_edit_analytics_method.php";
		echo showAnalyticsMethodEditForm($paramHash);
	break;
	case "analytics_type":
		require_once "lib_edit_analytics_type.php";
		echo showAnalyticsTypeEditForm($paramHash);
	break;
	
	case "accepted_order": // for order manager
		require_once "lib_supplier_scraping.php";
		require_once "lib_edit_accepted_order.php";
		
		$paramHash["accepted_order_multi"]=(!$editMode && empty($_REQUEST["order_uid"]));
		
		echo showAcceptedChemicalOrderForm($paramHash);
		
		if (!$paramHash["accepted_order_multi"]) { // order_uid: diese Daten übernehmen
			require_once "lib_edit_chemical_order.php";
			require_once "lib_edit_institution.php";
			
			echo showChemicalOrderForm(array("text" => s("information_customer"), "no_db_id_pk" => true, ));
			echo showInstEditForm(array("text" => s("information_vendor"), "no_db_id_pk" => true, "prefix" => "v_", ));
		}
	break;
	case "chemical_order": // for person who ordered
		require_once "lib_supplier_scraping.php";
		require_once "lib_edit_chemical_order.php";
		
		echo showChemicalOrderForm($paramHash);
		
		if ($editMode) {
			require_once "lib_edit_accepted_order.php";
			//~ require_once "lib_edit_institution.php";
			
			echo showAcceptedChemicalOrderForm(array("text" => s("information_order_manager"), "no_db_id_pk" => true, ));
			//~ echo showInstEditForm(array("text" => s("information_vendor"), "no_db_id_pk" => true, "prefix" => "v_", ));
		}
	break;
	
	case "chemical_storage":
		echo simpleHidden("split_chemical_storage_id"); // to subtract amount from this package
		require_once "lib_edit_molecule.php";
		require_once "lib_edit_chemical_storage.php";
		echo showChemicalStorageEditForm($paramHash);
		echo showMoleculeEditForm(array("text" => s("information_molecule"), "no_db_id_pk" => true, ));
		// Mol und Cheminstor editieren gibt locking-Probleme, mte beides locken
	break;
	case "chemical_storage_type":
		require_once "lib_edit_chemical_storage_type.php";
		echo showChemicalStorageTypeEditForm($paramHash);
	break;
	case "cost_centre":
		require_once "lib_edit_cost_centre.php";
		echo showCostCentreEditForm($paramHash);
	break;
	case "institution":
		require_once "lib_edit_institution.php";
		echo showInstEditForm($paramHash);
	break;
	case "lab_journal":
		require_once "lib_edit_lab_journal.php";
		echo showLabJournalEditForm($paramHash);
	break;
	case "literature":
		require_once "lib_edit_sci_journal.php";
		require_once "lib_edit_literature.php";
		echo showLiteratureEditForm($paramHash);
		echo showSciJournalEditForm(array("text" => s("create_new_journal"), "no_db_id_pk" => true));
	break;
	case "message":
		require_once "lib_edit_message.php";
		echo showMessageEditForm($paramHash);
		//~ if ($readOnly && count($result["person"])) { // sowohl der absender als auch die empfnger sehen den bearbeitungsstatus
			//~ echo outputList($result["person"],$columns["message_person"],array("noButtons" => true));
		//~ }
	break;
	case "molecule":
		require_once "lib_edit_molecule.php";
		echo showMoleculeEditForm($paramHash); // 
		if (!$editMode) {
			require_once "lib_edit_chemical_storage.php";
			echo showChemicalStorageEditForm(array(
				"text" => "<label for=\"new_chemical_storage\"><input type=\"checkbox\" name=\"new_chemical_storage\" id=\"new_chemical_storage\" onClick=\"validate_new_chemical_storage()\" value=\"true\"> ".s("new_chemical_storage")."</label>", 
				"new_molecule" => true, 
				LOCKED => true, 
				"no_db_id_pk" => true, 
			));
		}
	break;
	case "molecule_type":
		require_once "lib_edit_molecule_type.php";
		echo showMoleculeTypeEditForm($paramHash);
	break;
	case "mpi_order":
		require_once "lib_edit_mpi_order.php";
		echo showMPIOrderForm($paramHash);
	break;
	case "order_comp":
		require_once "lib_edit_order_comp.php";
		echo showOrderCompForm($paramHash);
	break;
	case "other_db":
		require_once "lib_edit_other_db.php";
		echo showOtherdbEditForm($paramHash);
	break;
	case "person":
		require_once "lib_edit_person.php";
		echo showPersonEditForm($paramHash);
		if (!$editMode) {
			require_once "lib_edit_lab_journal.php";
			$result[0]=arr_merge(getDefaultDataset("lab_journal"),$result[0]);
			
			echo showLabJournalEditForm(array(
				"text" => "<label for=\"new_lab_journal\"><input type=\"checkbox\" name=\"new_lab_journal\" id=\"new_lab_journal\" onClick=\"validate_new_lab_journal()\" value=\"true\"> ".s("new_lab_journal")."</label>", 
				"new_person" => true, 
				LOCKED => true, 
				"no_db_id_pk" => true, 
			));
		}
	break;
	case "project":
		require_once "lib_edit_project.php";
		echo showProjectEditForm($paramHash);
	break;
	case "reaction":
		require_once "lib_edit_reaction.php";
		echo showReactionEditForm($paramHash);
	break;
	case "reaction_type":
		require_once "lib_edit_reaction_type.php";
		echo showReactionTypeEditForm($paramHash);
	break;
	case "rent":
		require_once "lib_edit_rent.php";
		echo showRentEditForm($paramHash);
	break;
	case "sci_journal":
		require_once "lib_edit_sci_journal.php";
		echo showSciJournalEditForm($paramHash);
	break;
	case "settlement":
		require_once "lib_edit_settlement.php";
		echo showSettlementEditForm($paramHash);
	break;
	case "storage":
		require_once "lib_edit_storage.php";
		echo showStorageEditForm($paramHash);
	break;
	case "supplier_offer":
		//~ require_once "lib_supplier_scraping.php";
		require_once "lib_edit_supplier_offer.php";
		require_once "lib_edit_molecule.php";
		echo showSupplierOfferEditForm($paramHash);
		echo showMoleculeEditForm(array("text" => s("information_molecule"), "no_db_id_pk" => true));
	break;
	default:
		echo "Unknown table ".$baseTable;
	}

	echo "</form></div>".
		getHelperBottom().
		getLawMenu().
		getTransferMenu().
		getVersionMenu().
		getPrintMenu($baseTable).
		script;

	echo "readOnly=".intval($editMode).",editMode=".intval($editMode).";\n";
	if ($editMode) { // Datensatz bearbeiten

		// cache initial dataset
		for ($a=0;$a<count($result);$a++) {
			cacheDataset($a,$result[$a]["db_id"]!=$_REQUEST["db_id"] || $result[$a][$pk_name]!=$pk);
			//~ if ($result[$a]["db_id"]==$_REQUEST["db_id"] && $result[$a][$pk_name]==$pk) {
			//~ }
		}
		
		echo "
gotoDataset(".intval($page).",false);
focusInput(\"idx\");\n";

		// cache the rest with 300 ms delay
		echo "window.setTimeout(function () {";
		for ($a=0;$a<count($result);$a++) {
			if ($result[$a]["db_id"]!=$_REQUEST["db_id"] || $result[$a][$pk_name]!=$pk) {
				cacheDataset($a);
			}
		}
		echo "},".$clientCache["initLoadDelay"].");\n";
	}
	else { // neuer Datensatz
		if (count($result)==1) { // Vorgabewerte
			//~ print_r($result[0]);die();
			echo "setControlValues(".json_encode($result[0]).",false);\n";
		}

		echo "
startEditMode();
setAction(\"add\");\n";
		
		// >>> FR 091025
		if ($table=="lab_journal") { // preset sigle for selected person
			echo "touchOnChange(\"person_id\");\n";
		}
		// <<< FR 091025
	}
	
	if ($autoStartReadExt) { // to read additional data for cas_nr entered by lager
		echo "hideObj(\"btn_create\");
readExt();\n";
	}
	
	if ($rc_to_chemical_storage) { // load data from opener
		echo "
if (opener) {
";
		if ($rc_to_molecule) {
			// load molfile, mw, emp_formula, density_20 from opener
			// update GIF
			echo <<<END
	transferFromOpener(opener_list_int_name,opener_UID,"molfile_blob");
	transferFromOpener(opener_list_int_name,opener_UID,"mw");
	transferFromOpener(opener_list_int_name,opener_UID,"emp_formula");
	transferFromOpener(opener_list_int_name,opener_UID,"cas_nr");
	addMoleculeToUpdateQueue("molfile_blob");
	updateMolecules();
	
END;
		}
		echo <<<END
	// amount setzen
	var amount=opener.SILgetValue(opener_list_int_name,opener_UID,"m_brutto"),data=new Array(),a_db_id=-1;
	data["amount_unit"]=opener.SILgetValue(opener_list_int_name,opener_UID,"mass_unit");
	data["amount"]=amount;
	data["actual_amount"]=amount;
	data["supplier"]=opener.getControlValue("reaction_carried_out_by");
	data["chemical_storage_conc"]=opener.SILgetValue(opener_list_int_name,opener_UID,"rc_conc");
	data["chemical_storage_conc_unit"]=opener.SILgetValue(opener_list_int_name,opener_UID,"rc_conc_unit");
	setControlValues(data,true);
}

END;
	}

	// start directly in edit mode
	if ($_REQUEST["edit"]=="true") {
		echo "startEditMode();\n";
	}

	echo "updateTotalCount();
activateSearch(false);
self.focus();
"._script;
}

echo script."
if (parent && parent.searchBottom) {
	showObj(\"view_search\");
	if (parent.searchBottom.updateListOp) {
		parent.searchBottom.updateListOp();
	}
}

if (parent && parent.sidenav && parent.sidenav.updateListOp) {
	parent.sidenav.updateListOp();
}

setDefaultScrollObj(\"browsemain\");
";

if (isset($_REQUEST["print_nr_in_lab_journal"]) && $table="reaction" && $_REQUEST["crit0"]=="lab_journal_id") {
	echo "activateEditView(\"ergebnis\");
printRange(".fixNull($paperFormats["A4"]["w"]-$rand["w"]).",".fixNull($paperFormats["A4"]["h"]-$rand["h"]).",".fixStr($_REQUEST["print_nr_in_lab_journal"]).",\"lj_print\");
var url=\"editAsync.php?desired_action=set_reaction_printed&table=reaction&db_id=-1&lab_journal_id=".$_REQUEST["val0"]."&nr_in_lab_journal=".$_REQUEST["print_nr_in_lab_journal"]."&\"+getSelfRef([\"~script~\",\"table\"]);
setFrameURL(\"edit\",url);
";
}

if (!empty($_REQUEST["transferBarcode"])) {
	echo "barcodeRead(".fixStr($_REQUEST["transferBarcode"]).");\n";
}

if (!$editMode && in_array($table,array("chemical_storage","molecule")) && !empty($result[0]["molfile_blob"])) { // Acros/NIST
	echo "addMoleculeToUpdateQueue(\"molfile_blob\");
updateMolecules();\n";
}

if ($editMode) {
	echo "document.onbeforeunload=saveOpenDataset;
document.body.onbeforeunload=saveOpenDataset;";
}

echo "window.onload=frameworkInitShim;";

echo _script."</body></html>";
completeDoc();
?>
