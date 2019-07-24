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
Seitenmenü
*/
require_once "lib_global_funcs.php";
require_once "lib_simple_forms.php";
require_once "lib_navigation.php";
require_once "lib_global_settings.php";
require_once "lib_constants.php";
require_once "lib_formatting.php";
require_once "lib_sidenav_funcs.php";
require_once "lib_applet.php";

$_REQUEST["no_cache"]="";
$_REQUEST["order_by"]=""; // fix errors when changing from LJ

pageHeader();
require_once "lib_supplier_scraping.php";


echo "<link type=\"text/css\" rel=\"stylesheet\" href=\"style.css.php?style=sidenav\">".
loadJS(array("chem.js","sidenav.js","controls.js","jsDatePick.min.1.3.js","forms.js","searchpk.js","molecule_edit.js"),"lib/").
script."
var ".addParamsJS().",ref_reaction;";

if ($_REQUEST["desired_action"]=="detail_search") { // JS functions for detail search
	$_REQUEST["table"]=ifempty($_REQUEST["table"],"molecule");
	echo getQueryPartInputs($_REQUEST["table"]);	
} // end of JS functions for detail search

if (!empty($_REQUEST["tableSelect"])) { // Stil normal, kein topnav
	//~ $background="lib/side_without_top.png";
	$background="lib/sidenav_new_search.png";
	$background_down="lib/sidenav_new_search_down.png";
	$background_small="lib/side_without_top35.png";
}
elseif ($_REQUEST["style"]=="lj") { // Stil Laborjournal rote Linie, kein topnav
	//~ $background="lib/side_red_line.png";
	$background="lib/sidenav_new_lj.png";
	$background_down="lib/sidenav_new_lj_down.png";
	$background_small="lib/side_red_line35.png";
}
else { // Auswahl, Stil normal, kein topnav
	//~ $background="lib/side.png";
	$background="lib/sidenav_new.png";
	$background_down="lib/sidenav_new_down.png";
	$background_small="lib/side35.png";
}
echo _script.
style."
#bg_down { position:absolute;left:0px;top:0px;width:100%;height:100%;background-image:url(".$background.");background-repeat:no-repeat }
"._style."
</head>
<body style=\"background-image:url(".$background_down.");background-repeat:repeat-y\"><div id=\"bg_down\"></div><div id=\"uni_logo\">".getImageLink($g_settings["links_in_topnav"]["uni_logo"])."</div>";
showCommFrame(array());
copyPasteAppletHelper();
echo "<div id=\"sideDiv\">";

$selectTables=explode(",",$_REQUEST["tableSelect"]);
$linkParams=getSelfRef(array("~script~","table","no_cache","cached_query"));

// default values
$paramHash=array(
	"noFieldSet" => true, 
	READONLY => false, 
	"no_db_id_pk" => true, 
	"noInputHighlight" => true, 
);

switch($_REQUEST["desired_action"]) {

// laborjournal --------------------------------------------------------------------------------------------------------------------------
case "lab_journal":
	echo loadJS(array("edit.js","compare_rxn.js"),"lib/").
		script."
dependent={\"dbs\":[\"val32\"],\"val32\":[\"val0\"],\"val0\":[\"val1\"],\"val1\":[\"val2\"]};
"._script.
		"<form id=\"searchForm\" onSubmit=\"search_reaction(); \" name=\"searchForm\" action=\"\" target=\"mainpage\" method=\"post\">";

	$paramHash["int_name"]="reaction_search";
	$paramHash["onLoad"]='selectUpdated("dbs"); updateDate(); var initDone=false; ';
	$paramHash["queryFields"]=array(
		"val32" => 32, 
		"val0" => 0, 
		"val1" => 1, 
		"val2" => 2, 
		"val3" => 3, 
		"val4" => 4
	);

	
	//~ $status_int_names=array_merge(array(""),range(1,5));
	//~ $status_texts=array_merge(array(s("any")),s("status_list"));
	
	$update_LJ="";
	if (!empty($settings["default_lj"])) {
		$update_LJ.="if (!initDone) { setInputValue(\"val1\",".fixNull($settings["default_lj"])."); trackDynValue(\"val1\"); } ";
	}
	$update_LJ.="selectUpdated(int_name); ";
	
	// dbs: Datenbank: alle oder bestimmte, default -1
	echo 
		showHidden(array("int_name" => "query")). // , "value" => "<0> AND <1> AND <2> AND <3> AND <4>"
		showHidden(array("int_name" => "fields")).
		showHidden(array("int_name" => "view_options")).
		showHidden(array("int_name" => "view")).
		showHidden(array(
			"int_name" => "per_page", 
			"value" => ifempty($settings["default_per_page"],default_per_page), 
		)).
		// Table: reaction
		showHidden(array(
			"int_name" => "style", 
			"value" => "lj", 
		)).
		showHidden(array("int_name" => "prev_cache_id")). // speichert die vorherige cache_id
		showHidden(array("int_name" => "ref_cache_id")). // speichert die aktuelle cache_id
		showHidden(array("int_name" => "goto_page", "value" => "-1", )). // speichert die aktuelle cache_id
		showHidden(array(
			"int_name" => "table", 
			"value" => "reaction", 
		)).
		getFormElements(
			$paramHash,
			array(
				"tableStart",
					// dbs
				array(
					"item" => "pk_select", 
					"text" => s("database"), 
					"int_name" => "dbs", 
					"pkName" => "other_db_id", 
					"nameField" => "db_beauty_name", 
					"table" => "other_db", 
					"order_obj" => getUserDefOrderObj("other_db"), 
					//~ "order_obj" => array(
						//~ array("field" => (is_array($settings["other_db_order"]) && count($settings["other_db_order"]))?"FIELD(other_db_id,".fixArrayListString(array_values($settings["other_db_order"])).")":""), 
					//~ ),  
					"filterDisabled" => true, 
					"allowNone" => true, 
					"noneText" => s("any"), 
					"defaultValue" => "-1", 
					"clearbutton" => true, // none is better as value is ""
					"onChange" => "selectUpdated(&quot;dbs&quot;); sidenavToRxn(&quot;dbs&quot;); ", 
				),
					// <32> person_id
				array("item" => "hidden", "int_name" => "crit32", "value" => "lab_journal.person_id"),
				array("item" => "hidden", "int_name" => "op32", "value" => "eq"),
				array(
					"item" => "pk_select", 
					"text" => s("person_id"), 
					"int_name" => "val32", 
					"pkName" => "person_id", 
					"table" => "person", 
					"allowAuto" => true, 
					"autoText" => s("any"), 
					"order_by" => "person_name_disabled",  // disabled at the end
					"clearbutton" => true, 
					"onChange" => "sidenavToRxn(&quot;val32&quot;); ", 
					"updateFunction" => "if (!initDone) { setInputValue(\"val32\",".fixNull($_REQUEST["person_id"])."); trackDynValue(\"val32\"); } selectUpdated(int_name); ", 
					"dynamic" => true, 
					"maxTextLen" => 20, 
					//~ "filterDisabled" => true, 
					
					"getFilter" => 
						'var retval=[]; '.
						'retval["filter"]="query="; '.
						'retval["dbs"]=getControlValue("dbs"); '.
						'return retval;', 
					
					"getText" => 
						//~ 'return formatPersonNameNatural(rowData);'
						'return formatPerson(rowData);'
				),
					// <0> Projekt: (zu Datenbank passende) alle oder einzelnes
				array("item" => "hidden", "int_name" => "crit0", "value" => "reaction.project_id"),
				array("item" => "hidden", "int_name" => "op0", "value" => "eq"),
				array(
					"item" => "pk_select", 
					"text" => s("project"), 
					"int_name" => "val0", 
					"pkName" => "project_id", 
					"nameField" => "project_name", 
					"table" => "project", 
					"allowNone" => true, 
					"noneText" => s("none"), 
					"allowAuto" => true, 
					"autoText" => s("any"), 
					//~ "order_by" => "project_created_when", 
					"order_by" => "project_name", 
					"clearbutton" => true, 
					"onChange" => "sidenavToRxn(&quot;val0&quot;); ", 
					"updateFunction" => "if (!initDone) { setInputValue(\"val0\",-1); } selectUpdated(int_name); ", 
					"dynamic" => true, 
					"maxTextLen" => 20, 
					
					"getFilter" => 
						'var query=[],retval=[],url="",person_id=getControlValue("val32"); '.
						'if (person_id!=-1) { '. // person_id
							'query.push("<32>"); '.
							'url+="&crit32=project_person.person_id&op32=eq&val32="+person_id; '.
						'} '.
						'retval["filter"]="query="+query.join(" AND ")+url; '.
						'retval["dbs"]=getControlValue("dbs"); '.
						'return retval;', 
					
					"getText" => 
						'return rowData["project_name"];'
				),

					// <1> Laborjournale: (zu Datenbank und Projekt passende) alle oder einzelnes
				array("item" => "hidden", "int_name" => "crit1", "value" => "reaction.lab_journal_id"),
				array("item" => "hidden", "int_name" => "op1", "value" => "eq"),
				array(
					"item" => "pk_select", 
					"text" => s("lab_journal"), 
					"int_name" => "val1", 
					"pkName" => "lab_journal_id", 
					"nameField" => "lab_journal_code", 
					"table" => "lab_journal", 
					"allowAuto" => true, 
					"autoText" => s("any"), 
					"order_by" => "lab_journal_code", 
					"clearbutton" => true, 
					"onChange" => "sidenavToRxn(&quot;val1&quot;); ", 
					"updateFunction" => $update_LJ, 
					"dynamic" => true, 
					"filterDisabled" => true, 
					
					"getFilter" => 
						'var query=[],retval=[],url="",person_id=getControlValue("val32"); '.
						'if (person_id!=-1) { '. // lab_journal
							'query.push("<32>"); '.
							'url+="&crit32=lab_journal.person_id&op32=eq&val32="+person_id; '.
						'} '.
						'retval["filter"]="query="+query.join(" AND ")+url; '.
						'retval["dbs"]=getControlValue("dbs"); '.
						'return retval;', 
					
					"getText" => 
						'return rowData["lab_journal_code"];'
				),

					// <2> Ausführener: (zu Vorauswahl passende) alle oder einzelner, spezielle Abfrage auf reaction, um Dubletten zu vermeiden "updateFunction" => "reaction_carried_out_by_updated();", 
				array("item" => "hidden", "int_name" => "crit2", "value" => "reaction.reaction_carried_out_by"),
				array("item" => "hidden", "int_name" => "op2", "value" => "ex"),
				array(
					"item" => "pk_select", 
					"text" => s("reaction_carried_out_by"), 
					"int_name" => "val2", 
					"pkName" => "reaction_carried_out_by", 
					"nameField" => "reaction_carried_out_by", 
					"table" => "reaction_reaction_carried_out_by", 
					"order_by" => "reaction_carried_out_by", 
					"allowAuto" => true, 
					"autoText" => s("any"), 
					"order_by" => "reaction_carried_out_by", 
					"clearbutton" => true, 
					"onChange" => "sidenavToRxn(&quot;val2&quot;); ", 
					"updateFunction" => "if (!initDone) { setInputValue(\"val2\",-1); initDone=true; } ", 
					"dynamic" => true, 
					
					"getFilter" => 
						'var query=[],retval=[],url="",person_id=getControlValue("val32"),project_id=getControlValue("val0"),lab_journal_id=getControlValue("val1"); '.
						'if (person_id!=-1) { '. // person_id
							'query.push("<32>"); '.
							'url+="&crit32=lab_journal.person_id&op32=eq&val32="+person_id; '.
						'} '.
						'if (project_id=="") { '. // project_id
							'query.push("<0>"); '.
							'url+="&crit0=reaction.project_id&op0=nu&val0="; '.
						'} '.
						'else if (project_id!=-1) { '. // project_id
							'query.push("<0>"); '.
							'url+="&crit0=reaction.project_id&op0=eq&val0="+project_id; '.
						'} '.
						'if (lab_journal_id!=-1) { '. // lab_journal_id
							'query.push("<1>"); '.
							'url+="&crit1=reaction.lab_journal_id&op1=eq&val1="+lab_journal_id; '.
						'} '.
						'retval["filter"]="query="+query.join(" AND ")+url; '.
						'retval["dbs"]=getControlValue("dbs"); '.
						'return retval;', 
					
					"getText" => 
						'return rowData["reaction_carried_out_by"];'
				),

				// <3> Status: alle oder bestimmter (statisch)
				array("item" => "hidden", "int_name" => "crit3", "value" => "reaction.status"),
				array("item" => "hidden", "int_name" => "op3", "value" => "eq"),
				array(
					"item" => "select", 
					"int_name" => "val3", 
					"text" => s("status"), 
					"langKeys" => getValueList("reaction","status"), 
					"onChange" => "sidenavToRxn(&quot;val3&quot;); ", 
					"clearbutton" => true, 
					"autoText" => s("any"), 
					"allowAuto" => true, // no effect, but fools JS
				), 


				// <4> Datum (von-bis) (statisch)
				array("item" => "hidden", "int_name" => "crit4", "value" => "reaction.reaction_started_when"),
				array("item" => "hidden", "int_name" => "op4", "value" => "db"),
				array(
					"item" => "select", 
					"int_name" => "reaction_started_when", 
					"langKeys" => $langKeys["reaction_started_when"], 
					"onChange" => "updateDate(); sidenavToRxn(&quot;reaction_started_when&quot;); ", 
					"clearbutton" => true, 
					"autoText" => s("any"), 
					"allowAuto" => true, 
				),
				array("item" => "text", SPLITMODE => true, "text" => "<br id=\"val4_br\">"), 
				array("item" => "input", "int_name" => "val4", SPLITMODE => true, "size" => 10,"maxlength" => 22, "onChange" => "sidenavToRxn(&quot;val4&quot;); "), 

				// Sortierung
				array(
					"item" => "select", 
					"int_name" => "order_by", 
					"text" => s("sort"), 
					"int_names" => $reaction_order_keys, 
					"langUseValues" => true, 
					//~ "texts" => array(s("lab_journal_entry"),s("reaction_started_when")), 
					"onChange" => "sidenavToRxn(&quot;order_by&quot;); ", 
				), 

				//~ array("item" => "hidden", "int_name" => "filter_disabled", "value" => 1, ),
				array("item" => "check", "int_name" => "selected_only", "onChange" => "sidenavToRxn(&quot;selected_only&quot;); ", ),

				getListLogic("form","sidenavToRxn(&quot;list_op&quot;);"),
				array("item" => "text", "int_name" => "ref_reaction", "text" => "<span id=\"ref_reaction\"></span>", ), // display the reaction name which is used for comparison

				"tableEnd",
			)
		);

	// onChange submit, bei Textfeldern 2s warten oder [Enter] oder Verlassen
	echo "<table class=\"hidden\"><tr>".
		getJSButton("search_reaction(); document.searchForm.submit()","lib/search.png","btn_search","link_search").
		getJSButton("document.searchForm.reset()","lib/reset_button.png","btn_get_all","link_reset_button").
		"<td>".
		getViewRadio(array("onChange" => "sidenavToRxn(&quot;view_mode&quot;); ")).
		getHiddenSubmit().
		"</td></tr></table></form>
		<div class=\"text\"><img width=\"220\" height=\"10\" border=\"0\" src=\"lib/link.gif\"></div>";
	
	// additional links
	if ($permissions & _chemical_read) {
		showSideLink(array("url" => "main.php?".$linkParams, "text" => s("change_to_inventory_menu"), "target" => "_top"));
	}
	
	showProjectLinks($linkParams);
	showSideLink(array("url" => "list.php?table=lab_journal&dbs=-1&".$linkParams, "text" => s("edit_lab_journals"), "target" => "mainpage", ));
	//~ showSideLink(array("url" => "list.php?table=reaction_type&dbs=-1&".$linkParams, "text" => s("edit_reaction_types"), "target" => "mainpage"));
	//~ showSideLink(array("url" => "list.php?table=literature&dbs=-1&".$linkParams, "text" => s("edit_literature"), "target" => "mainpage"));
	showSideLink(array("url" => "lj_main.php?desired_action=search&table=literature&".$linkParams, "text" => s("edit_literature"), "target" => "_top"));
	
	// bestellt
	if ($permissions & (_order_order + _admin)) {
		showSideLink(array(
			"url" => "list.php?table=my_chemical_order&dbs=-1&".$linkParams, 
			"text" => s("edit_my_chemical_orders"), 
			"target" => "mainpage", 
		));
	}
	
	// genehmigen
	if ($permissions & (_order_approve + _admin)) {
		showSideLink(array(
			"url" => "list.php?table=confirm_chemical_order&dbs=-1&".$linkParams, 
			"text" => s("edit_confirm_chemical_orders"), 
			"target" => "mainpage", 
		));
	}
	
	showSideLink(array("url" => "sidenav.php?desired_action=settings_lj&".$linkParams,"text" => s("settings_menu")));
	showSideLink(array("url" => "index.php?desired_action=logout&".$linkParams,"text" => s("logout"), "target" => "_top"));

break;

case "mpi_order":
	$sidenav_tables=array("mpi_order");
	
	echo "<form name=\"searchForm\" id=\"searchForm\" onSubmit=\"return doSearch()\" method=\"post\" target=\"mainpage\">
	<a href=\"Javascript:void window.open(&quot;sap_split.php&quot;);\" class=\"imgButtonSm\"><img src=\"lib/sap_sm.png\" border=\"0\"".getTooltip("read_sap_dump")."></a><br clear=\"all\">";
	
	echo "<fieldset id=\"searchCritFS\"><legend>"
	.s("search_crit")
	."</legend>
	<span id=\"searchCrit\"></span>
	<span id=\"searchModeSpan\"></span>
	<br>
	<span id=\"searchSrcInput\"></span></fieldset>";
	
	// in db
	echo "<fieldset id=\"searchWhereFS\">
	<legend id=\"searchWhereLabel\">"
	.s("search_in")." ".getDbsMultiselectA()
	."</legend>"
	.getDbsMultiselectB()
	."</fieldset>";

	// Suchen
	echo "<fieldset id=\"doSearchFS\">
	<legend>".s("btn_search")."</legend>".
	"<table class=\"hidden\"><tr>".
	//~ showHidden(array("int_name" => "filter_disabled", "value" => 1, )).
	showCheck(array("int_name" => "selected_only", )).
	getJSButton("if (doSearch()) document.searchForm.submit()","lib/search.png","btn_search","link_search").
	getJSButton("getAll()","lib/all.png","btn_get_all","link_all").
	getJSButton("document.searchForm.reset();updateSource(); ","lib/reset_button.png","btn_reset","link_reset_button").
	"<td style=\"width:25px\"></td><td>".getViewRadio().getHiddenSubmit()."</td>".
	"</tr></table></fieldset>".
	getListLogic("fieldset").
	showHidden(array("int_name" => "search")).
	showHidden(array("int_name" => "per_page", "value" => ifempty($settings["default_per_page"],default_per_page), )).
	showHidden(array("int_name" => "fields")).
	showHidden(array("int_name" => "view_options")).
	showHidden(array("int_name" => "query", "value" => "<0>")).
	showHidden(array("int_name" => "crit1")).
	showHidden(array("int_name" => "op1")).
	showHidden(array("int_name" => "val1")).
	showHidden(array("int_name" => "prev_cache_id")). // speichert die vorherige cache_id
	showHidden(array("int_name" => "ref_cache_id")). // speichert die aktuelle cache_id
	showHidden(array("int_name" => "table", "value" => "mpi_order", )).
	script."
// preload images
var source,table=\"mpi_order\",currentType,prevType,oldMolfile,loadCount=0,sF=document.searchForm;
buttons=[\"search\",\"reset_button\",\"all\"],sidenav_tables=".json_encode($sidenav_tables).";
".getCritOptionsFunction($sidenav_tables)."
allDBs();
updateListOp();
updateCrit();
updateSelects();
window.setTimeout(function() {focusInput(\"val0\") },200);
"._script."
</form>";

break;

// detailsuche---------------------------------------------------------------------------------------------------------------------------------------------------

case "detail_search":
	$searchTable=$_REQUEST["table"];
	if (!in_array($_REQUEST["table"],array_keys($tables))) {
		$searchTable="molecule";
	}
	echo "<form name=\"searchForm\" id=\"searchForm\" method=\"post\" onSubmit=\"return prepare_submit()\" target=\"mainpage\">";
	
	// links
	showCommonButtons();
	// table_select
	echo "<table class=\"noborder\"><tr><td>
		<fieldset id=\"searchWhatFS\">
			<legend>".s("search_for")."</legend>
			<table class=\"hidden\"><tr>".
				getDetailSearchTableButton("chemical_storage","chemicals_in_stock").
				getDetailSearchTableButton("molecule","info_on_molecules").
			"</tr></table>
		</fieldset>
	</td><td>
		<fieldset id=\"advancedFS\">
			<legend>".s("advanced")."</legend>
			<table class=\"hidden\"><tr>
				<td><a href=".fixStr(getSelfRef()."&desired_action=search")." class=\"imgButton\" id=\"link_chemical\">".s("search_simple")."</a></td>";
	
	if (mayCreate($searchTable,-1)) {
		echo getJSButton("getNew()","lib/new.png","btn_new","link_new");
	}
	
	echo "	</tr></table>
		</fieldset>
	</td></tr></table>";
	
	if ($tables[$searchTable]["readPerm"]>0) {
		echo "<fieldset id=\"searchWhereFS\"><legend id=\"searchWhereLabel\">".
			s("search_in")." ".
			getDbsMultiselectA().
			"</legend>".
			getDbsMultiselectB().
			"</fieldset>";
	}
	
	echo showHidden(array("int_name" => "fields", )).
		showHidden(array("int_name" => "view_options", )).
		showHidden(array("int_name" => "per_page", "value" => ifempty($settings["default_per_page"],default_per_page), )).
		showHidden(array("int_name" => "table", "value" => $searchTable, )).
		showHidden(array("int_name" => "prev_cache_id", )). // speichert die vorherige cache_id
		showHidden(array("int_name" => "ref_cache_id", )). // speichert die aktuelle cache_id
		"<div id=\"subqueries\"></div>
		<fieldset id=\"searchCritFS\">
			<legend class=\"condition\">".s("search_crits")."</legend>
			<table class=\"hidden\"><tr>
			<td class=\"hidden\" colspan=\"4\"><input type=\"text\" name=\"query\" id=\"query\" onKeypress=\"return queryKeypress(event,this)\" onKeyup=\"disableAuto();updateSel();\" onSelect=\"updateSel();\" size=\"30\"></td>
			</tr><tr>
			<td class=\"hidden\">".showCheck(array("int_name" => "auto", "value" => true, "class" => "bg", "onChange" => "autoQuery()"))."</td>
			<td class=\"hidden\"><a href=\"Javascript:wrapSelection(document.searchForm.query,&quot;(&quot;,&quot;)&quot;,1);disableAuto()\" class=\"imgButtonSm\"><img src=\"lib/brackets.png\" height=\"20\" width=\"20\" border=\"0\" align=\"absmiddle\"".getTooltip("btn_brackets")."></a></td>
			<td class=\"hidden\"><a href=\"javascript:addCrit()\" class=\"imgButtonSm\"><nobr><img src=\"lib/filter.png\" height=\"20\" width=\"20\" border=\"0\" align=\"absmiddle\"".getTooltip("add_condition").">+</nobr></a></td><td class=\"hidden\" width=\"100%\">&nbsp;</td>
			</tr></table>
		</fieldset>".
		getHiddenSubmit().
		"<fieldset id=\"doSearchFS\">
			<legend class=\"condition\">".s("btn_search")."</legend>
			<table class=\"hidden\"><tr>".
			//~ showHidden(array("int_name" => "filter_disabled", "value" => $g_settings["dispose_instead_of_delete"], )).
			showCheck(array("int_name" => "selected_only", )).
			getJSButton("if (prepare_submit()) document.searchForm.submit()","lib/search.png","btn_search","link_search").
			getJSButton("getAll()","lib/all.png","btn_get_all","link_all").
			getJSButton("document.searchForm.reset();perform_reset()","lib/reset_button.png","btn_get_all","link_reset_button").
			"<td>".getViewRadio()."</td>
			</tr>
			</table>
		</fieldset>
".getListLogic("fieldset")."
</form>
".script."
var sF=document.searchForm,table=".fixStr($searchTable).";\n";
	if ($searchTable=="molecule") {
		echo "allDBs();\n";
	}
echo "
addCrit();
"._script;
break;

case "search": // Suchformular einfach-------------------------------------------------------------------------------------------------------
	$searchTable=ifempty($_REQUEST["table"],$selectTables[0]);
	switch ($searchTable) {
	case "literature":
		echo loadJS(array("edit.js"),"lib/").
			script."
dependent={\"dbs\":[\"val0\",\"val9\"]};
"._script.
			"<form id=\"searchForm\" onSubmit=\"search_literature(); \" name=\"searchForm\" action=\"\" target=\"mainpage\" method=\"post\">
			<fieldset id=\"advancedFS\">
				<legend>".s("advanced")."</legend>
				<table class=\"hidden\"><tr>";
		
		if (mayCreate($searchTable,-1)) {
			echo getJSButton("getNew()","lib/new.png","btn_new","link_new");
		}
		
		echo "</tr></table>
		</fieldset>";

		$paramHash["int_name"]="literature_search";
		$paramHash["onLoad"]='selectUpdated("dbs"); ';
		$paramHash["queryFields"]=array(
			"val0" => 0, 
			"val1" => 1, 
			"val2" => 2, 
			"val3" => 3, 
			"val5" => 4, 
			"val7" => 7, 
			"val8" => 8, 
			"val9" => 9, 
			"val10" => 10, 
		);
		
		// dbs: Datenbank: alle oder bestimmte, default -1
		echo showHidden(array("int_name" => "fields")).
			showHidden(array("int_name" => "view_options")).
			showHidden(array("int_name" => "view")).
			showHidden(array("int_name" => "per_page", "value" => ifempty($settings["default_per_page"],default_per_page), )).
			// Table: reaction
			showHidden(array("int_name" => "table", "value" => "literature", )).
			showHidden(array("int_name" => "prev_cache_id")). // speichert die vorherige cache_id
			showHidden(array("int_name" => "ref_cache_id")). // speichert die aktuelle cache_id
			


			getFormElements(
				$paramHash,
				array(
					array("item" => "hidden", "int_name" => "query"),
					"tableStart",
					array(
						"item" => "pk_select", 
						"text" => s("database"), 
						"int_name" => "dbs", 
						"pkName" => "other_db_id", 
						"nameField" => "db_beauty_name", 
						"table" => "other_db", 
						"order_obj" => getUserDefOrderObj("other_db"), 
						"filterDisabled" => true, 
						"allowNone" => true, 
						"noneText" => s("any"), 
						"defaultValue" => "-1", 
						"clearbutton" => true, // none is better as value is ""
						"onChange" => "selectUpdated(&quot;dbs&quot;); ", 
					),
					
					// <0> Journal
					array("item" => "hidden", "int_name" => "crit0", "value" => "literature.sci_journal_id"),
					array("item" => "hidden", "int_name" => "op0", "value" => "eq"),
					array(
						"item" => "pk_select", 
						"text" => s("sci_journal"), 
						"int_name" => "val0", 
						"pkName" => "sci_journal_id", 
						//~ "nameField" => "sci_journal_name", 
						"table" => "sci_journal", 
						"order_by" => "sci_journal_name", 
						"dynamic" => true, 
						"allowNone" => true, 
						"allowAuto" => true, 
						"autoText" => s("any"), 
						
						"maxTextLen" => 30, 
						
						"getFilter" => 
							'var retval=[]; '.
							'retval["filter"]=""; '.
							'retval["dbs"]=getControlValue("dbs"); '.
							'return retval;', 
						
						"getText" => 
							'return rowData["sci_journal_name"];', 
					),
					
					// <1> literature_year
					array("item" => "hidden", "int_name" => "crit1", "value" => "literature.literature_year"),
					array("item" => "hidden", "int_name" => "op1", "value" => "bt"),
					array("item" => "input", "int_name" => "val1", "text" => s("literature_year"), "postProc" => "page_range", "size" => 10, "maxlength" => 10, ), 
					
					// <2> literature_volume
					array("item" => "hidden", "int_name" => "crit2", "value" => "literature.literature_volume"),
					array("item" => "hidden", "int_name" => "op2", "value" => "bt"),
					array("item" => "input", "int_name" => "val2", "text" => s("literature_volume"), "size" => 10, "maxlength" => 10), 
					
					// <3> page_high
					array("item" => "hidden", "int_name" => "crit3", "value" => "literature.page_high"),
					array("item" => "hidden", "int_name" => "op3", "value" => "bt"),
					array("item" => "input", "int_name" => "val3", "text" => s("page_high"), "type" => "range", "postProc" => "page_range", "size" => 10, "maxlength" => 20), 
					
					// <4> authors
					array("item" => "hidden", "int_name" => "crit4", "value" => "author.literature_id"),
					array("item" => "hidden", "int_name" => "op4", "value" => "sq"),
					array("item" => "hidden", "int_name" => "val4", "value" => "<5> OR <6>"),
					array("item" => "hidden", "int_name" => "crit5", "value" => "author.author_last"),
					array("item" => "hidden", "int_name" => "op5", "value" => "co"),
					array("item" => "hidden", "int_name" => "crit6", "value" => "author.author_first"),
					array("item" => "hidden", "int_name" => "op6", "value" => "co"),
					array("item" => "input", "int_name" => "val5", "text" => s("authors"), "size" => 10, ), 
					array("item" => "hidden", "int_name" => "val6"), 
					
					// <7> title
					array("item" => "hidden", "int_name" => "crit7", "value" => "literature.literature_title"),
					array("item" => "hidden", "int_name" => "op7", "value" => "ca"),
					array("item" => "input", "int_name" => "val7", "text" => s("literature_title"), "size" => 10, ), 
					
					// <8> keywords
					array("item" => "hidden", "int_name" => "crit8", "value" => "literature.keywords"),
					array("item" => "hidden", "int_name" => "op8", "value" => "ca"),
					array("item" => "input", "int_name" => "val8", "text" => s("keywords"), "size" => 10, ), 
					
					// <10> fulltext
					array("item" => "hidden", "int_name" => "crit10", "value" => "literature.literature_blob_fulltext"),
					array("item" => "hidden", "int_name" => "op10", "value" => "ca"),
					array("item" => "input", "int_name" => "val10", "text" => s("literature_blob_fulltext"), "size" => 10, ), 
					
					// <9> project
					array("item" => "hidden", "int_name" => "crit9", "value" => "project_literature.project_id"),
					array("item" => "hidden", "int_name" => "op9", "value" => "eq"),
					array(
						"item" => "pk_select", 
						"text" => s("project"), 
						"int_name" => "val9", 
						"pkName" => "project_id", 
						"nameField" => "project_name", 
						"table" => "project", 
						"allowNone" => true, 
						"noneText" => s("none"), 
						"allowAuto" => true, 
						"autoText" => s("any"), 
						"order_by" => "project_name", 
						"clearbutton" => true, 
						"dynamic" => true, 
						"maxTextLen" => 30, 
						
						"getFilter" => 
							'var retval=[]; '.
							'retval["filter"]=""; '.
							'retval["dbs"]=getControlValue("dbs"); '.
							'return retval;', 
						
						"getText" => 
							'return rowData["project_name"];'
					),
					
					//~ array("item" => "hidden", "int_name" => "filter_disabled", "value" => 1, ),
					array("item" => "check", "int_name" => "selected_only", ),
					
					getListLogic("form"),
					
					"tableEnd"
				)
			);

		// onChange submit, bei Textfeldern 2s warten oder [Enter] oder Verlassen
		echo "<table class=\"hidden\"><tr>".
			getJSButton("search_literature(); document.searchForm.submit()","lib/search.png","btn_search","link_search").
			getJSButton("document.searchForm.reset()","lib/reset_button.png","btn_get_all","link_reset_button").
			"<td>".getViewRadio().getHiddenSubmit()."</td>".
		"</tr></table></form>";
	
	if (empty($_REQUEST["tableSelect"])) {
		showSideLink(array("url" => "list.php?table=sci_journal&dbs=-1&".$linkParams, "text" => s("edit_sci_journal"), "target" => "mainpage", ));
		showProjectLinks($linkParams);
		showSideLink(array("url" => "lj_main.php?".getSelfRef(array("~script~","ref_cache_id")), "text" => s("back"), "target" => "_top", ));
	}
	
	break;
	//-------------------------------------------------------------------------------------
	case "analytical_data":
		echo "<fieldset id=\"advancedFS\">
				<legend>".s("advanced")."</legend>
				<table class=\"hidden\"><tr>";
		
		if (mayCreate($searchTable,-1)) {
			echo getJSButton("getNew()","lib/new.png","btn_new","link_new");
		}
		
		echo "	</tr></table>
			</fieldset>";
		// unassigned by device
		
		// new
		
		echo loadJS(array("edit.js"),"lib/")."
<form id=\"searchForm\" onSubmit=\"search_analytical_data(); \" name=\"searchForm\" action=\"\" target=\"mainpage\" method=\"post\">";

		$paramHash["int_name"]="analytical_data_search";
		$paramHash["queryFields"]=array(
			"val0" => 0, 
			"val1" => 1, 
		);
		
		// dbs: Datenbank: alle oder bestimmte, default -1
		echo showHidden(array("int_name" => "fields")).
			showHidden(array("int_name" => "view_options")).
			showHidden(array("int_name" => "view")).
			showHidden(array("int_name" => "per_page", "value" => ifempty($settings["default_per_page"],default_per_page), )).
			// Table: reaction
			showHidden(array("int_name" => "table", "value" => "analytical_data", )).
			showHidden(array("int_name" => "prev_cache_id")). // speichert die vorherige cache_id
			showHidden(array("int_name" => "ref_cache_id")). // speichert die aktuelle cache_id
			showHidden(array("int_name" => "crit0", "value" => "analytical_data.analytics_device_id")).
			showHidden(array("int_name" => "op0", "value" => "eq")).
			showHidden(array("int_name" => "crit1", "value" => "analytical_data.reaction_id")).
			showHidden(array("int_name" => "op1", "value" => "nu")).
			getFormElements(
				$paramHash,
				array(
					array("item" => "hidden", "int_name" => "query", "name" => "query[]"),
					array("item" => "hidden", "int_name" => "unassigned_query", "name" => "query[]", "value" => " AND <1>"),
					"tableStart",
						// <0> Projekt: (zu Datenbank passende) alle oder einzelnes
					array(
						"item" => "pk_select", 
						"text" => s("analytics_device"), 
						"int_name" => "val0", 
						"pkName" => "analytics_device_id", 
						"nameField" => "analytics_device_name", 
						"table" => "analytics_device", 
						"dbs" => -1, 
						"allowNone" => true, 
						"noneText" => s("any"), 
					),
					
					//~ array("item" => "hidden", "int_name" => "filter_disabled", "value" => 1, ),
					array("item" => "check", "int_name" => "selected_only", ),

					getListLogic("form"),
					"tableEnd"
				)
			);

		// onChange submit, bei Textfeldern 2s warten oder [Enter] oder Verlassen
		echo "<table class=\"hidden\"><tr>".
			getJSButton("search_analytical_data(); document.searchForm.submit()","lib/search.png","btn_search","link_search").
			getJSButton("document.searchForm.reset()","lib/reset_button.png","btn_get_all","link_reset_button").
			"<td>".getViewRadio().getHiddenSubmit()."</td>".
		"</tr></table></form>";
		
	break;
	case "molecule":
	case "supplier_offer":
	case "chemical_storage":
		$buttons=array(
			"molecule" => array("img" => "lib/molecule.png", "lang_key" => "info_on_molecules", ), 
			"chemical_storage" => array("img" => "lib/chemical_storage.png", "lang_key" => "chemicals_in_stock", ), 
			"supplier_offer" => array("img" => "lib/supplier_offer.png", "lang_key" => "supplier_offer_pl", ), 
			"supplier_search" => array("img" => "lib/supplier.png", "lang_key" => "molecules_at_suppl", ), 
		);
		
		if ($searchTable=="supplier_offer") {
			$sidenav_tables=array("supplier_offer","supplier_search","molecule",);
		}
		else {
			$sidenav_tables=array("chemical_storage","supplier_search","molecule",);
		}
		
		echo "<form name=\"searchForm\" id=\"searchForm\" onSubmit=\"return doSearch()\" method=\"post\" target=\"mainpage\">";
		showCommonButtons();
		showSideLink(array(
			"url" => "export.php?output_type=xls&per_page=-1&dbs=-1&table=chemical_storage&fields=molecule_name%2Cemp_formula_short%2Ccas_nr%2Csafety_sym%2Cowner_person_id%2Csafety_text%2Csafety_r_s%2Csafety_danger%2Csafety_cancer%2Csafety_mutagen%2Csafety_reprod%2Cmigrate_id_mol%2Cmigrate_id_cheminstor%2Camount%2Cchemical_storage_barcode%2Cstorage%2Cborrowed_by%2Csupplier&query=NOT%20(%3C0%3E%20AND%20%3C1%3E%20AND%20%3C2%3E)&crit0=molecule.safety_cancer&op0=nu&crit1=molecule.safety_mutagen&op1=nu&crit2=molecule.safety_reprod&op2=nu", 
			"text" => s("cmr_report"), 
			"target" => "_blank", 
		));
		if (!empty($person_id)) {
			// ausgeliehen
			showSideLink(array(
				"url" =>getCombiButtonURL(array(
					"table" => "chemical_storage", 
					"this_pk_name" => "chemical_storage.borrowed_by_person_id", 
					"db_id" => -1, 
					"pk" => $person_id, 
				)), 
				"text" => s("borrowed"), 
				"target" => "mainpage", 
			));
		}
		
		// bestellt
		if ($permissions & (_order_order + _admin)) {
			showSideLink(array(
				"url" => "list.php?table=my_chemical_order&dbs=-1&".$linkParams, 
				"text" => s("edit_my_chemical_orders"), 
				"target" => "mainpage", 
			));
		}
		
		// genehmigen
		if ($permissions & (_order_approve + _admin)) {
			showSideLink(array(
				"url" => "list.php?table=confirm_chemical_order&dbs=-1&".$linkParams, 
				"text" => s("edit_confirm_chemical_orders"), 
				"target" => "mainpage", 
			));
		}
		
		echo "<table class=\"noborder\"><tbody><tr><td>
				<fieldset id=\"searchWhatFS\">
					<legend>".s("search_for")."</legend>
					<table class=\"hidden\"><tbody><tr>";
		
		foreach ($sidenav_tables as $sidenav_table) {
			echo getJSButton("updateSource(".fixQuot($sidenav_table).")",$buttons[$sidenav_table]["img"],$sidenav_table,"link_".$sidenav_table);
		}
		
		echo "</tr></tbody></table>
				</fieldset>
			</td><td>
				<fieldset id=\"advancedFS\">
					<legend>".s("advanced")."</legend>
				
					<table class=\"hidden\"><tbody><tr>
						<td><a href=\"Javascript:gotoDetailSearch()\" class=\"imgButton\">".s("search_detail")."</a></td>";
		
		if (mayCreate($searchTable,-1)) {
			echo getJSButton("getNew()","lib/new.png","btn_new","link_new");
		}
		
		echo "		</tr></table>
				</fieldset>
			</td></tr>
			</tbody></table>
			<fieldset id=\"searchCritFS\">
				<legend>".s("search_crit")."</legend>
				<span id=\"searchCrit\"></span>
				<span id=\"searchModeSpan\"></span>
				<br>
				<span id=\"searchSrcInput\"></span>
			</fieldset>
			<fieldset id=\"searchWhereFS\">
				<legend id=\"searchWhereLabel\">".s("search_in")." ".getDbsMultiselectA()."</legend>"
				.getDbsMultiselectB()
			."</fieldset>
			<fieldset id=\"searchExtFS\" style=\"display:none\">
				<legend id=\"searchWhereLabel\">".s("search_at")."</legend>
				<select name=\"supplier\" id=\"supplier\">
					<option value=\"all\">".s("all_suppliers")."
					<option value=\"\">".s("db_only");
		
		if (is_array($steps)) foreach ($steps as $code) {
			if (!$suppliers[$code]["noExtSearch"]) {
				echo "<option value=".fixStr($code).">".$suppliers[$code]["name"];
			}
		}
		
		echo "	</select>
				<a href=\"javascript:openStartPage(document.searchForm.supplier.value)\"><img src=\"lib/external.png\"".getTooltip("all_suppliers")." border=\"0\"></a>
			</fieldset>";

		// Suchen
		echo "<fieldset id=\"doSearchFS\">
				<legend>".s("btn_search")."</legend>".
				showCheck(array("int_name" => "selected_only", )).
				"<table class=\"hidden\"><tbody><tr>".
					//~ showHidden(array("int_name" => "filter_disabled", "value" => $g_settings["dispose_instead_of_delete"], )).
					getJSButton("if (doSearch()) document.searchForm.submit()","lib/search.png","btn_search","link_search").
					getJSButton("getAll()","lib/all.png","btn_get_all","link_all").
					getJSButton("document.searchForm.reset();updateSource(); ","lib/reset_button.png","btn_reset","link_reset_button").
					"<td style=\"width:25px\"></td><td>".getViewRadio().getHiddenSubmit()."</td>
				</tr></tbody></table></fieldset>".
				getListLogic("fieldset").
				showHidden(array("int_name" => "search")).
				showHidden(array("int_name" => "per_page", "value" => ifempty($settings["default_per_page"],default_per_page), )).
				showHidden(array("int_name" => "fields")).
				showHidden(array("int_name" => "view_options")).
				showHidden(array("int_name" => "query", "value" => "<0>")).
				showHidden(array("int_name" => "crit1")).
				showHidden(array("int_name" => "op1")).
				showHidden(array("int_name" => "val1")).
				showHidden(array("int_name" => "prev_cache_id")). // speichert die vorherige cache_id
				showHidden(array("int_name" => "ref_cache_id")). // speichert die aktuelle cache_id
				showHidden(array("int_name" => "table")).
				script.<<<END
// preload images
var source,table,currentType,prevType,oldMolfile,loadCount=0,sF=document.searchForm;
buttons=new Array("chemical","molecule","supplier","search","reset_button","all");

END;
		if (is_array($suppliers)) foreach ($suppliers as $code => $supplier) {
			$startPages[$code]=$suppliers[$code]["urls"]["startPage"];
		}
		echo 
"startPages=".json_encode($startPages).",sidenav_tables=".json_encode($sidenav_tables).";
".getCritOptionsFunction($sidenav_tables)."
updateSource(".fixStr($searchTable).");
allDBs();
window.setTimeout(function() {focusInput(\"val0\") },200);
"._script."
</form>";
	}
break;

case "order":
	
	if ($permissions & _order_accept) { // direkteingabe Chemikalienausgabe
		showSideLink(array("url" => "edit.php?table=accepted_order&dbs=&".$linkParams, "text" => s("new_order"), "target" => "mainpage", ));
	}
	elseif (mayWrite("chemical_order",-1)) { // freie Eingabe einer Anforderung
		showSideLink(array("url" => "edit.php?table=chemical_order&dbs=&".$linkParams, "text" => s("new_order"), "target" => "mainpage", ));
	}
	
	if (!empty($person_id) && $permissions & (_order_order + _admin)) {
		showSideLink(array(
			"url" => "list.php?table=my_chemical_order&dbs=-1&".$linkParams, 
			"text" => s("edit_my_chemical_orders"), 
			"target" => "mainpage", 
		));
	}
	showSideLink(array("url" => "list.php?table=open_chemical_order&dbs=".(($permissions & _order_accept)?"":"-1")."&".$linkParams, "text" => s("edit_open_chemical_orders"), "target" => "mainpage", ));
	if ($permissions & (_order_approve + _admin)) {
		showSideLink(array("url" => "list.php?table=confirm_chemical_order&dbs=-1&".$linkParams, "text" => s("edit_confirm_chemical_orders"), "target" => "mainpage", ));
	}
	if ($permissions & (_order_accept + _admin)) {
		showSideLink(array("url" => "list.php?table=central_chemical_order&dbs=-1&".$linkParams, "text" => s("edit_central_chemical_orders"), "target" => "mainpage", ));
		showSideLink(array("url" => "list.php?table=order_comp&dbs=-1&".$linkParams, "text" => s("edit_order_comps"), "target" => "mainpage", ));
		showSideLink(array("url" => "list.php?table=vendor&dbs=-1&".$linkParams, "text" => s("edit_vendors"), "target" => "mainpage", ));
		//~ showSideLink(array("url" => "main.php?desired_action=search&table=vendor&dbs=-1&".$linkParams, "text" => s("edit_vendors"), "target" => "_top", ));
		showSideLink(array("url" => "list.php?table=vendor_with_open&dbs=-1&".$linkParams, "text" => s("edit_vendors_with_open"), "target" => "mainpage", ));
	}
	showSideLink(array("url" => "list.php?table=active_rent&dbs=-1&".$linkParams, "text" => s("edit_active_rents"), "target" => "mainpage", ));
	//~ showSideLink(array("url" => "list.php?table=supplier_offer&".$linkParams, "text" => s("edit_supplier_offers"), "target" => "mainpage", ));
	showSideLink(array("url" => "main.php?desired_action=search&table=supplier_offer&".$linkParams, "text" => s("edit_supplier_offers"), "target" => "_top", ));
	showSideLink(array("url" => "list.php?table=settlement&".$linkParams, "text" => s("edit_settlements"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=completed_chemical_order&".$linkParams, "text" => s("edit_completed_chemical_orders"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=rent&dbs=-1&".$linkParams, "text" => s("edit_rents"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=cost_centre&".$linkParams, "text" => s("edit_cost_centres"), "target" => "mainpage", ));
	
break;

// Einstellungen--------------------------------------------------------------------------------------------------------------------
case "settings":
// showSideLink(array("text" => s("common_settings"),"target" => "\"mainpage\""));
	showSideLink(array("url" => "change_pw.php?".$linkParams, "text" => s("change_pw"), "target" => "mainpage", ));
	if ($permissions & _admin) {
		showSideLink(array("url" => "g_settings.php?".$linkParams, "text" => s("g_settings"), "target" => "mainpage", ));
		showSideLink(array("url" => "perm_settings.php?".$linkParams, "text" => s("perm_settings"), "target" => "mainpage", ));
	}
	if ($db_user!=ROOT) {
		showSideLink(array("url" => "settings.php?".$linkParams, "text" => s("settings"), "target" => "mainpage", ));
	}
	showSideLink(array("url" => "credits.php?".$linkParams, "text" => s("credits"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=person&dbs=-1&".$linkParams, "text" => s("edit_users"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=other_db&dbs=-1&".$linkParams, "text" => s("edit_other_dbs"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=storage&dbs=-1&".$linkParams, "text" => s("edit_storages"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=molecule_type&dbs=-1&".$linkParams, "text" => s("edit_molecule_types"), "target" => "mainpage"));
	showSideLink(array("url" => "list.php?table=chemical_storage_type&dbs=-1&".$linkParams, "text" => s("edit_chemical_storage_types"), "target" => "mainpage"));
	showSideLink(array("url" => "list.php?table=institution&dbs=-1&".$linkParams, "text" => s("edit_institutions"), "target" => "mainpage", ));
	
	if ($g_settings["dispose_instead_of_delete"]) {
		//~ showSideLink(array("url" => "list.php?table=chemical_storage&dbs=-1&query=<0>&crit0=chemical_storage.chemical_storage_disabled&op0=on&order_by=disposed_when&".$linkParams, "text" => s("disposed_chemicals"), "target" => "mainpage", ));
		showSideLink(array("url" => "list.php?table=disposed_chemical_storage&dbs=-1&order_by=-disposed_when&".$linkParams, "text" => s("disposed_chemical_storage_pl"), "target" => "mainpage", ));
	}
	
	showSideLink(array("url" => "printBarcodeList.php?table=helper", "text" => s("print_helper_barcode"), "target" => "_blank", ));
	
	showSideLink(array("url" => "check_scraping.php","text" => s("check_scraping"), "target" => "mainpage", ));
	showSideLink(array("url" => "check_substruct.php","text" => s("check_substruct"), "target" => "mainpage", ));
	showSideLink(array("url" => "check_reaction_mapping.php","text" => s("check_reaction_mapping"), "target" => "mainpage", ));
	showSideLink(array("url" => "check_double.php","text" => s("check_double"), "target" => "mainpage", ));
	
	if ($permissions & _admin) {
		showSideLink(array("url" => "import.php","text" => s("import_tab_sep"), "target" => "mainpage", ));
	}
	
	if ($db_user==ROOT) {
		showSideLink(array("url" => "refresh_user.php","text" => s("refresh_user"), "target" => "mainpage", ));
		showSideLink(array("url" => "reset_blocked.php","text" => s("reset_block_list"), "target" => "mainpage", ));
		showSideLink(array("url" => "reset_locks.php","text" => s("reset_locks"), "target" => "mainpage", ));
		showSideLink(array("url" => "root_db_man.php?desired_action=db_cross","text" => s("db_man"), "target" => "mainpage", ));
		showSideLink(array("url" => "root_db_man.php?desired_action=fix_structures","text" => s("batch_operations"), "target" => "mainpage", ));
	}
	
break;

case "settings_lj":
	showSideLink(array("url" => "lj_main.php?".getSelfRef(array("~script~","ref_cache_id")), "text" => s("back"), "target" => "_top", ));
	showSideLink(array("url" => "change_pw.php?".$linkParams, "text" => s("change_pw"), "target" => "mainpage", ));
	
	if ($permissions & _admin) {
		showSideLink(array("url" => "g_settings.php?".$linkParams, "text" => s("g_settings"), "target" => "mainpage", ));
		showSideLink(array("url" => "perm_settings.php?".$linkParams, "text" => s("perm_settings"), "target" => "mainpage", ));
	}
	
	if ($db_user!=ROOT) {
		showSideLink(array("url" => "settings.php?".$linkParams, "text" => s("settings"), "target" => "mainpage", ));
	}
	
	showSideLink(array("url" => "credits.php?".$linkParams, "text" => s("credits"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=person&dbs=-1&".$linkParams, "text" => s("edit_users"), "target" => "mainpage", ));
	//~ showSideLink(array("url" => "list.php?table=lab_journal&dbs=-1&".$linkParams, "text" => s("edit_lab_journals"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=reaction_type&dbs=-1&".$linkParams, "text" => s("edit_reaction_types"), "target" => "mainpage"));
	showSideLink(array("url" => "list.php?table=analytics_type&dbs=-1&".$linkParams, "text" => s("edit_analytics_types"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=analytics_device&dbs=-1&".$linkParams, "text" => s("edit_analytics_devices"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=analytics_method&dbs=-1&".$linkParams, "text" => s("edit_analytics_methods"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=analytical_data&dbs=-1&".$linkParams, "text" => s("edit_analytical_data"), "target" => "mainpage", ));
	showSideLink(array("url" => "spz_macro.php?".$linkParams, "text" => s("spz_macro"), "target" => "mainpage", ));
	showSideLink(array("url" => "list.php?table=sci_journal&dbs=-1&".$linkParams, "text" => s("edit_sci_journal"), "target" => "mainpage", ));
	
	if ($db_user==ROOT) {
		showSideLink(array("url" => "check_literature.php","text" => s("check_literature"), "target" => "mainpage", ));
		showSideLink(array("url" => "refresh_user.php","text" => s("refresh_user"), "target" => "mainpage", ));
		showSideLink(array("url" => "reset_blocked.php","text" => s("reset_block_list"), "target" => "mainpage", ));
		showSideLink(array("url" => "reset_locks.php","text" => s("reset_locks"), "target" => "mainpage", ));
		showSideLink(array("url" => "root_db_man.php?desired_action=db_cross","text" => s("db_man"), "target" => "mainpage", ));
		showSideLink(array("url" => "root_db_man.php?desired_action=fix_structures","text" => s("batch_operations"), "target" => "mainpage", ));
		showSideLink(array("url" => "root_db_man.php?desired_action=recalc_spectra","text" => s("recalc_spectra"), "target" => "mainpage", ));
		showSideLink(array("url" => "root_db_man.php?desired_action=export_lj_data","text" => s("export_lj_data"), "target" => "mainpage", ));
	}

break;
}

if (!$g_settings["no_advert"] && !endswith(getenv("HTTP_HOST"),".uni-kl.de")) {
	echo "<div id=\"support_project\"><a href=\"http://sciformation.com/sciformation_eln.html\" target=\"_blank\"><img src=\"lib/sciformation_eln.png\" border=\"0\"/></a>
<a class=\"text\" href=\"http://sourceforge.net/project/project_donations.php?group_id=269061\" target=\"_blank\">or support this project with a donation?</a></div>";
}
echo "</div>
<div id=\"bg_layer\" style=\"display:none;position:absolute;top:0;left:0;width:100%;height:100%;background-image:url(".$background_small.");background-repeat:no-repeat\"></div>
<a href=\"Javascript:switchSideframe(true)\" class=\"imgButtonSm\" id=\"expand\" style=\"display:none\"><img src=\"lib/expand.png\"".getTooltip("expand")." border=\"0\"></a>
<a href=\"Javascript:switchSideframe(false)\" class=\"imgButtonSm\" id=\"collapse\"><img src=\"lib/collapse.png\"".getTooltip("collapse")." border=\"0\"></a>".script."
if (self==top) {
	top.location.href=".fixStr((($_REQUEST["style"]=="lj")?"lj_main.php":"main.php")."?".getSelfRef(array("~script~"),array("desired_action"))).";
}
switchSideframe(true);
updateListOp();

"._script."

</body>
</html>";

	completeDoc();
?>