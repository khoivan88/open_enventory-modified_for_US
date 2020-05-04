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
require_once "lib_simple_forms.php";
require_once "lib_navigation.php";
require_once "lib_output.php";
require_once "lib_constants.php";
require_once "lib_supplier_scraping.php";
require_once "lib_form_elements.php";
require_once "lib_simpleExtSearch.php";
require_once "lib_chem_lang.php";

setGlobalVars();
$page_transparent_params=array("dbs",$pk_name,"fields","view_options","page","per_page","ref_cache_id","ref_reaction_db_id","ref_reaction_id","buttons");
pageHeader();

activateEditViews($baseTable);

if (!is_array($query[$table])) {
	displayFatalError("fatal_no_table");
}

//~ echo script;

$mayCreate=mayCreate($baseTable);
$mayWrite=mayWrite($baseTable);
$mayDelete=mayDelete($baseTable);

list($res,$dataArray,$sort_hints)=handleQueryRequest(QUERY_LIST);
$totalCount=& $dataArray["totalCount"];
$page=& $dataArray["page"];
$skip=& $dataArray["skip"];
$per_page=& $dataArray["per_page"];
$from_cache=& $dataArray["cache_active"];
//~ print_r($res);
//~ die();

$view_options=json_decode($_REQUEST["view_options"],true);

if ($table=="molecule") {
	$delWarning=s("delWarningMolecule");
}
else {
	$delWarning=s("delWarning");
}

$desired_action=$_REQUEST["desired_action"];
$name=s($baseTable);

// Khoi: added $_REQUEST["table"] != "disposed_chemical_storage" to have disposed_chemicals list display correctly
if ($_REQUEST["table"] != "disposed_chemical_storage" && empty($_REQUEST["fields"]) && !empty($g_settings["views"][$baseTable]["view_standard"])) {
	$_REQUEST["fields"]=$g_settings["views"][$baseTable]["view_standard"];
}

list($fields,$hidden)=getFields($columns[$table],$_REQUEST["fields"]);


switch($baseTable) {
case "reaction":
	echo loadJS(array("compare_rxn.js"),"lib/");
// no break;
case "chemical_storage":
case "molecule":
case "supplier_offer":
	echo loadJS(array("safety_".$lang.".js","chem.js","safety.js"),"lib/");
break;
case "literature":
	echo loadJS(array("literature.js"),"lib/");
break;
}

// pageParams=".fixStr(getSelfRef(array("~script~","page","db_id","pk"))).",
echo "<title>".s("lab_journal_title")." ".$g_settings["organisation_name"]."</title>".
	stylesheet.
	style.
	getFixedStyleBlock().
	_style.
	loadJS(array("molecule_edit.js","list.js"),"lib/").
	script.
	getRefReaction().
	addParamsJS().
",delWarning=".fixStr($delWarning).",setRefCacheId(".fixStr($_REQUEST["cached_query"]).",".fixNull($totalCount).",".fixStr($_REQUEST["ref_cache_id"]).");
setSideNavRadio(\"view_mode\",\"list\");
var compare_obj=[],compare_status=0,currentView=\"\",archive_entity,fields=".fixStr(@join(",",$fields)).",table=".fixStr($table).",view_options=(".json_encode($view_options).");

</script>
</head>
<body class=\"mainbody\">";

showCommFrame(array("debug" => false)); // for barcode search and select

echo "<form name=\"main\" method=\"get\" action=".fixStr(getenv("REQUEST_URI"))." onSubmit=\"return false; \">
<div id=\"browsenav\">";

if (!empty($_REQUEST["message"])) { // Nachricht über letzte Op anzeigen
	$message=strip_tags($_REQUEST["message"]);
}

if ($_REQUEST["buttons"]=="print_labels") { // Liste der ausgewählten Gebinde
	//~ echo $message;
	//~ if (count($settings["selection"][$table])) {
	if (getSelectionCount($table)) {
		$label_formats=array("no_barcode");
		if ($g_settings["print_barcodes_on_labels"]) {
			$label_formats[]="barcode";
		}
		
		//~ $title=s("labels_per_page");
		$left[]=s("print_labels").": ";
		
		if (is_array($label_formats)) foreach($label_formats as $label_format) {
			if (isset($label_dimensions[ $label_format ]["lang_key"] )) {
				$left[]=s($label_dimensions[ $label_format ]["lang_key"]).":";
			}
			if (is_array($label_dimensions[ $label_format ]["types"])) foreach ($label_dimensions[ $label_format ]["types"] as $size => $parameters) {
				$left[]="<nobr><a href=\"javascript:printLabels(".$parameters["size"].",".$parameters["per_row"].",".$parameters["per_col"].",&quot;".$parameters["parameter"]."&quot;)\" class=\"imgButtonSm\"><img src=\"./lib/".$parameters["img"]."\" border=\"0\"".getTooltipP(s("labels_".$size).$parameters["per_row"]."x".$parameters["per_col"].s("labels_per_page").s($parameters["lang_key"])).">".$parameters["per_row"]."x".$parameters["per_col"]."</a></nobr>";
			}
			//~ echo "<br>";
		}
		
		$left[]="<a href=\"javascript:resetSelection()\" class=\"imgButtonSm\"><img src=\"./lib/reset_sm.png\" border=\"0\"".getTooltip("reset_selection")."></a>";
	}
	$left[]="<span id=\"feedback_message\">".$message."</span>";
	$right[]=listGetPrintButton();
	$right[]=listGetExportButton();
	echo getTwoAlignTable($left,$right);
}
else { // Ergebnisliste
	$center[]=getNavigationSelect(getSelfRef(array("page","per_page")),$page,$per_page,$totalCount,$sort_hints);
	
	$combiParamHash=array(
		"number" => $totalCount, 
		"table" => $table, 
		"number_id" => "list_count",
	);
	
	if ($mayCreate["-1"] && $baseTable!="reaction" && $table!="message_in") { // reactions are added to lab journals, otherwise the owner_id is unclear
		$combiParamHash["parameter"]=""; // must be set
		//~ $left[]=getImgAddButton($baseTable,"","new");
	}
	
	$left[]=getCombiButton($combiParamHash);
	
	// multi-Lösch-button
	
	if ($from_cache) {
		$right[]=getRefreshButton(); // Button zum Aktualisieren, kann wohl demnächst wegfallen
	}
	switch ($baseTable) {
	case "person":
	case "storage":
		$left[]=getPrintBarcodesButton($baseTable);
	break;
	}
	
	$right[]=getMessageButton();
	if ($_REQUEST["style"]=="lj") {
		$right[]=getInventoryButton();
	}

	$right[]=listGetPrintButton();
	$right[]=listGetExportButton();
	//~ if ($query[$table]["showPerPageSelect"]) {
		//~ $center[]=getPerPageSelect($skip);
	//~ }
	$left[]="<span id=\"feedback_message\">".$message."</span>";
	echo getAlignTable($left,$center,$right);
}

echo "<table id=\"tab_bar\" cellspacing=\"0\"><tr>";
//~ if ($_REQUEST["selected"]!="true") {
	echo getListEditViewTabs($table,$res[0]["db_id"],$res[0][$pk_name]); // Detailansichten
	echo getViews($table,true); // Listenansichten
	echo getExtTabs($table);
	//~ if ($query[$table]["showPerPageSelect"]) {
		if ($per_page!=-1) {
			$per_page_text=$per_page." ";
		}
		$per_page_text.=s("results_per_page");
		if (count($res)) {
			echo getTabLink(array("class" => "tab_light", "url" => "Javascript:void(0)", "onMouseover" => "hideOverlayId(&quot;showColumnOverlay&quot;,1);  showOverlayId(this,&quot;perPageOverlay&quot;,0,0,8)", "onMouseout" => "hideOverlayId(&quot;perPageOverlay&quot;)", "text" => $per_page_text ) );
		}
	//~ }
//~ }
if (count($res) && count($hidden)) {
	echo getTabLink(array("class" => "tab_light", "url" => "Javascript:void(0)", "onMouseover" => "hideOverlayId(&quot;perPageOverlay&quot;,1); showOverlayId(this,&quot;showColumnOverlay&quot;,0,0,8)", "onMouseout" => "hideOverlayId(&quot;showColumnOverlay&quot;)", "text" => s("show_column") ) );
}
echo "</tr></table>";

echo "</div><div id=\"browsemain\">";


$paramHash=array(
	"output_type" => "html", 
	"separatorField" => "db_id", 
);

if ($baseTable=="message") { // nur Nachrichten, wo person_id==from_person oder person_id==to_person, die auch auf der Startseite
	$paramHash["noResMessage"]="<br>";
	$paramHash["noButtons"]=false;
	if ($table=="message_in") {
		$paramHash["noResMessage"].=s("no_message_in");
	}
	elseif ($table=="message_out") {
		$paramHash["noResMessage"].=s("no_message_out");
	}
	else {
		$paramHash["noResMessage"].=s("no_message");
	}
}

//~ print_r($res);
echo outputList($res,$fields,$paramHash);

if (in_array($baseTable,array("chemical_storage","molecule")) && !count($res)) {
	displayFixedQuery();
}

echo "<br><table class=\"noborder blind\"><tr>".getSimpleExtSearchLinks()."</tr></table>".procBin($_REQUEST["val0"])."
</div>
</form>
<form name=\"simpleExtSearch\" id=\"simpleExtSearch\" action=\"getResultList.php\" target=\"_blank\" method=\"get\">
<input type=\"hidden\" name=\"supplier\" id=\"simpleExtSearchSupplier\">
<input type=\"hidden\" name=\"cached_query\" value=".fixStr($_REQUEST["cached_query"])."></form>".
getExportMenu().
getHelperBottom().
$view_options_HTML;

//~ echo getListOptionsMenu($table);
//~ if ($query[$table]["showPerPageSelect"]) {
	echo getPerPageOverlay($skip,$per_page);
//~ }
if (count($res) && count($hidden)) {
	echo getHiddenColsOverlay($table,$hidden);
}

echo script;
$itemData=array();
for ($a=0;$a<count($res);$a++) { // aus SESSION die selektion in JS speichern
	$itemData[]=array(
		$res[$a]["db_id"],
		$res[$a][$pk_name],
		$settings["selection"][$table][ $res[$a]["db_id"] ][ $res[$a][$pk_name] ]
	);
}

echo "
itemData=".json_encode($itemData).";

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
activateSearch(false);
";

// position by hash
$skipPage=$skip;
if ($per_page>0) {
	$skipPage%=$per_page;
}
if ($skipPage>0) {
	echo "document.location.hash=".fixStr("item".$skipPage).";\n";
}
//~ echo "focusInput(".fixStr("input_actual_amount_".$skipPage).");\n"; // GOOSSEN HACK, remove!!

echo _script."
</body>
</html>";
completeDoc();
?>
