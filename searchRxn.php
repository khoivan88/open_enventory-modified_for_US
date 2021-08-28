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
require_once "lib_simple_forms.php";
require_once "lib_navigation.php";
require_once "lib_global_settings.php";
require_once "lib_constants.php";
require_once "lib_formatting.php";
require_once "lib_applet.php";
require_once "lib_searchRxn.php";

pageHeader();

echo "<title>Hauptmenü</title>
<link href=\"style.css.php\" rel=\"stylesheet\" type=\"text/css\">".
//~ loadJS("dynamic.js.php").
loadJS(array("chem.js","sidenav.js","controls.js","jsDatePick.min.1.3.js","forms.js","searchpk.js","edit.js","searchrxn.js","compare_rxn.js"),"lib/").
script."
var readOnly=false,editMode,".addParamsJS().",default_per_page=".default_per_page.";
dependent={\"dbs\":[\"val32\"],\"val32\":[\"val0\"],\"val0\":[\"val1\"],\"val1\":[\"val2\"]};";

echo _script."</head><body style=\"background-image:url(".$background.");background-repeat:no-repeat\">";
showCommFrame(array("debug" => $_REQUEST["debug"]=="true"));
copyPasteAppletHelper(array("mode" => "rxn", ));
echo "<form id=\"searchForm\" name=\"searchForm\" method=\"post\" onSubmit=\"prepareRxnSearch()\" target=\"mainpage\">";

// activates query parts if an input value is not empty
$queryFields=array(
		"val32" => 32, 
		"val0" => 0, 
		"val1" => 1, 
		"val2" => 2, 
		"val3" => 3, 
		"val4" => 4, 
		"val5" => 5, 
		"val6" => 6, 
		"val7" => 7, 
		"val33" => 33, 
		"val8" => 8, 
		"val11" => 11, 
		//~ "val14" => 16, // ref_amount
		//~ "val18" => 20, 
		"val31a" => 31, 
		"val34" => 34, 
		"val35" => 35, 
		"val12" => 12, 
		"val13" => 13, 
		"val14" => 14, 
);

$fieldsArray=array(
	array("item" => "hidden", "int_name" => "query", "name" => "query[]"),

	// Applet direkt einbinden

	array("item" => "hidden", "int_name" => "crit31", "value" => "reaction.rxnfile_blob"),
	array("item" => "hidden", "int_name" => "op31", "value" => "sr"),
	array(
		"item" => "applet", 
		"int_name" => "val31a", 
		"mode" => "rxn", 
		"searchMode" => true, 
		"copyPasteButtons" => true, 
		"width" => "95%", 
		"height" => "60%", 
	), 

	// Namensteile
	array("item" => "hidden", "int_name" => "crit34", "value" => "reaction_chemical.reaction_chemical_auto", ),
	array("item" => "hidden", "int_name" => "op34", "value" => "ca", ),
	array("item" => "input", "int_name" => "val34", "text" => s("molecule_auto"), "size" => 50, ), 

	// Anfang Tabelle
	array("item" => "text", "text" => "<table class=\"searchRxnTable\"><tr><td>"), 

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
		"onChange" => "selectUpdated(&quot;dbs&quot;); rxnToSidenav(&quot;dbs&quot;); ", 
		"filterDisabled" => true, 
		"allowNone" => true, 
		"noneText" => s("any"), 
		"defaultValue" => "-1", 
		"clearbutton" => true, 
	),

	array("item" => "hidden", "int_name" => "crit32", "value" => "lab_journal.person_id"),
	array("item" => "hidden", "int_name" => "op32", "value" => "eq"),

	// <32> person_id
	array(
		"item" => "pk_select", 
		"text" => s("person_id"), 
		"int_name" => "val32", 
		"pkName" => "person_id", 
		"table" => "person", 
		"allowAuto" => true, 
		"autoText" => s("any"), 
		"order_by" => "person_name_disabled", // disabled at the end
		"clearbutton" => true, 
		"onChange" => "rxnToSidenav(&quot;val32&quot;);", 
		"updateFunction" => "if (!initDone) { sidenavToRxn(\"val32\"); } selectUpdated(int_name); ", 
		"dynamic" => true, 
		//~ "filterDisabled" => true, 
		"maxTextLen" => 20, 
		
		"getFilter" => 
			'var retval=new Array(); '.
			'retval["filter"]="query="; '.
			'retval["dbs"]=getControlValue("dbs"); '.
			'return retval;', 
		
		"getText" => 
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
		"allowAuto" => true, 
		"autoText" => s("any"), 
		"allowNone" => true, 
		"noneText" => s("none"), 
		//~ "order_by" => "project_created_when", 
		"order_by" => "project_name", 
		"clearbutton" => true, 
		"onChange" => "rxnToSidenav(&quot;val0&quot;); ", 
		"updateFunction" => "if (!initDone) { sidenavToRxn(\"val0\"); } selectUpdated(int_name); ", 
		"dynamic" => true, 
		"maxTextLen" => 20, 
		
		"getFilter" => 
			'var query=new Array(),retval=new Array(),url="",person_id=getControlValue("val32"); '.
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

	array("item" => "hidden", "int_name" => "crit7", "value" => "reaction.reaction_type_id"),
	array("item" => "hidden", "int_name" => "op7", "value" => "eq"),
	array(
		"item" => "pk_select", 
		"text" => s("reaction_type"), 
		"int_name" => "val7", 
		"pkName" => "reaction_type_id", 
		"nameField" => "reaction_type_name", 
		"table" => "reaction_type", 
		"allowAuto" => true, 
		"autoText" => s("any"), 
		"allowNone" => true, 
		"noneText" => s("none"), 
		"order_by" => "reaction_type_name", 
		"clearbutton" => true, 
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
		"dynamic" => true, 
		"order_by" => "lab_journal_code", 
		"clearbutton" => true, 
		"onChange" => "rxnToSidenav(&quot;val1&quot;); ", 
		"updateFunction" => "if (!initDone) { sidenavToRxn(\"val1\"); } selectUpdated(int_name); ", 
		"dynamic" => true, 
		"filterDisabled" => true, 
		"getFilter" => 
			'var query=new Array(),retval=new Array(),url="",person_id=getControlValue("val32"); '.
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

	array("item" => "hidden", "int_name" => "crit6", "value" => "reaction.nr_in_lab_journal"),
	array("item" => "hidden", "int_name" => "op6", "value" => "bt"),
	array("item" => "input", "int_name" => "val6", "text" => s("lab_journal_entry"), "size" => 10,"maxlength" => 22, ), 

	// <2> Ausführender: (zu Vorauswahl passende) alle oder einzelner, spezielle Abfrage auf reaction, um Dubletten zu vermeiden "updateFunction" => "reaction_carried_out_by_updated();", 
	array("item" => "hidden", "int_name" => "crit2", "value" => "reaction.reaction_carried_out_by"),
	array("item" => "hidden", "int_name" => "op2", "value" => "ex"),
	array(
		"item" => "pk_select", 
		"text" => s("reaction_carried_out_by"), 
		"int_name" => "val2", 
		"pkName" => "reaction_carried_out_by", 
		"nameField" => "reaction_carried_out_by", 
		"table" => "reaction_reaction_carried_out_by", 
		"allowAuto" => true, 
		"autoText" => s("any"), 
		"dynamic" => true, 
		"order_by" => "reaction_carried_out_by", 
		"clearbutton" => true, 
		"onChange" => "rxnToSidenav(&quot;val2&quot;); ", 
		"updateFunction" => "if (!initDone) { sidenavToRxn(\"val2\"); initDone=true; } ", 
		"dynamic" => true, 
		"getFilter" => 
			'var query=new Array(),retval=new Array(),url="",person_id=getControlValue("val32"),project_id=getControlValue("val0"),lab_journal_id=getControlValue("val1"); '.
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
		"onChange" => "rxnToSidenav(&quot;val3&quot;); ", 
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
		"onChange" => "updateDate();rxnToSidenav(&quot;reaction_started_when&quot;); ", 
		"clearbutton" => true, 
		"autoText" => s("any"), 
		"allowAuto" => true, 
	),
	array("item" => "text", SPLITMODE => true, "text" => "<br id=\"val4_br\">"), 
	array("item" => "input", "int_name" => "val4", SPLITMODE => true, "size" => 10,"maxlength" => 22, "onChange" => "rxnToSidenav(&quot;val4&quot;); "), 

	"tableEnd",

	array("item" => "text", "text" => "</td><td>"), 

	"tableStart",
	// reaction_property
	array("item" => "hidden", "int_name" => "crit35", "value" => "reaction.reaction_title"),
	array("item" => "hidden", "int_name" => "op35", "value" => "ct"),
	array("item" => "input", "int_name" => "val35", "text" => s("reaction_title"), "size" => 15, "maxlength" => 100), 

	array("item" => "hidden", "int_name" => "crit5", "value" => "reaction.realization_text_fulltext"),
	array("item" => "hidden", "int_name" => "op5", "value" => "ct"),
	array("item" => "input", "int_name" => "val5", "text" => s("realization_text"), "size" => 15, "maxlength" => 100), 

	array("item" => "hidden", "int_name" => "crit33", "value" => "reaction.realization_observation_fulltext"),
	array("item" => "hidden", "int_name" => "op33", "value" => "ct"),
	array("item" => "input", "int_name" => "val33", "text" => s("realization_observation"), "size" => 15, "maxlength" => 100), 

	array("item" => "hidden", "int_name" => "crit8", "value" => "reaction_chemical.yield"),
	array("item" => "hidden", "int_name" => "op8", "value" => "bt"),
	array("item" => "input", "text" => s("yield"), "int_name" => "val8", "size" => 5,"maxlength" => 10),

	array("item" => "hidden", "int_name" => "crit11", "value" => "reaction_chemical.gc_yield"),
	array("item" => "hidden", "int_name" => "op11", "value" => "bt"),
	array("item" => "input", "text" => s("gc_yield"), "int_name" => "val11", "size" => 5,"maxlength" => 10),

	array("item" => "hidden", "int_name" => "crit14", "value" => "reaction.ref_amount", "skip" => !$g_settings["show_rc_stoch"], ),
	array("item" => "hidden", "int_name" => "op14", "value" => "bt", "skip" => !$g_settings["show_rc_stoch"], ),
	array("item" => "input", "text" => s("ref_amount"), "int_name" => "val14", "size" => 5,"maxlength" => 10, "skip" => !$g_settings["show_rc_stoch"], ),
	array(
		"item" => "pk_select", 
		"int_name" => "val14a", 
		"skip" => !$g_settings["show_rc_stoch"], 
		SPLITMODE => true, 
		"pkName" => "unit_name", 
		"dbs" => "-1", 
		"table" => "units", 
		"nameField" => "unit_name", 
		"filterDisabled" => true, 
		"filter" => "unit_type LIKE BINARY \"n\"", 
		"setValues" => 
			'return a(selected_values,"unit_name"); ', 
		"defValue" => "mmol", 
	), 
	/* array("item" => "hidden", "int_name" => "crit14", "value" => "reaction_property.reaction_property_value"), 
	array("item" => "hidden", "int_name" => "op14", "value" => "bt"), 
	array("item" => "input", "text" => s("ref_amount"), "int_name" => "val14", "size" => 5,"maxlength" => 30, "skip" => !$g_settings["show_rc_stoch"], ), 


	array("item" => "hidden", "int_name" => "crit15", "value" => "reaction_property.reaction_property_name"), 
	array("item" => "hidden", "int_name" => "op15", "value" => "ex"), 
	array("item" => "hidden", "int_name" => "val15", "value" => "ref_amount"), 

	array("item" => "hidden", "int_name" => "crit16", "value" => "reaction_property.reaction_id"), 
	array("item" => "hidden", "int_name" => "op16", "value" => "sq"), 
	array("item" => "hidden", "int_name" => "val16", "value" => "<14> AND <15>"), */

);

$number=40;
if (is_array($reaction_conditions)) foreach ($reaction_conditions as $condition => $data) {
	if ($g_settings["reaction_conditions"][$condition]) {
		$fieldsArray[]=array("item" => "hidden", "int_name" => "crit".$number, "value" => "reaction_property.reaction_property_value", );
		$fieldsArray[]=array("item" => "hidden", "int_name" => "op".$number, "value" => ifempty($data["search_op"],"bt"), );
		$fieldsArray[]=array("item" => "input", "text" => s($condition), "int_name" => "val".$number, "size" => ifempty($data["search_size"],5),"maxlength" => 30, );

		$fieldsArray[]=array("item" => "hidden", "int_name" => "crit".($number+1), "value" => "reaction_property.reaction_property_name", );
		$fieldsArray[]=array("item" => "hidden", "int_name" => "op".($number+1), "value" => "ex", );
		$fieldsArray[]=array("item" => "hidden", "int_name" => "val".($number+1), "value" => $condition, );

		$fieldsArray[]=array("item" => "hidden", "int_name" => "crit".($number+2), "value" => "reaction_property.reaction_id", ); // if input is empty, simply do not include this in query
		$fieldsArray[]=array("item" => "hidden", "int_name" => "op".($number+2), "value" => "sq", );
		$fieldsArray[]=array("item" => "hidden", "int_name" => "val".($number+2), "value" => "<".$number."> AND <".($number+1).">", );
		
		$queryFields["val".$number]=$number+2;
		
		$number+=3;
	}
}

$fieldsArray[]=array("item" => "hidden", "int_name" => "crit".$number, "value" => "reaction_property.reaction_property_value", );
$fieldsArray[]=array("item" => "hidden", "int_name" => "op".$number, "value" => "ex", );
$fieldsArray[]=array("item" => "check", "text" => s("retained_product"), "int_name" => "val".$number, "value" => "1", );

$fieldsArray[]=array("item" => "hidden", "int_name" => "crit".($number+1), "value" => "reaction_property.reaction_property_name", );
$fieldsArray[]=array("item" => "hidden", "int_name" => "op".($number+1), "value" => "ex", );
$fieldsArray[]=array("item" => "hidden", "int_name" => "val".($number+1), "value" => "retained_product", );

$fieldsArray[]=array("item" => "hidden", "int_name" => "crit".($number+2), "value" => "reaction_property.reaction_id", ); // if input is empty, simply do not include this in query
$fieldsArray[]=array("item" => "hidden", "int_name" => "op".($number+2), "value" => "sq", );
$fieldsArray[]=array("item" => "hidden", "int_name" => "val".($number+2), "value" => "<".$number."> AND <".($number+1).">", );
		
$queryFields["val".$number]=$number+2;

$number+=3;

$fieldsArray=array_merge($fieldsArray,array(
	"tableEnd",

	array("item" => "text", "text" => "</td><td>"), 
	// analytical_data availability

	"tableStart", 
	array("item" => "text", "text" => "<thead><tr><td>".s("analytics")."</td><td>".s("has_has_not")."</td></tr></thead>"), 
	array("item" => "text", "int_name" => "text_has_ad"), 
	array("item" => "check", "int_name" => "has_ad", "name" => "query[]", SPLITMODE => true, "onChange" => "uncheckObj(&quot;has_no_ad&quot;)", "value" => " AND <".$number.">"), 
	array("item" => "check", "int_name" => "has_no_ad", "name" => "query[]", SPLITMODE => true, "onChange" => "uncheckObj(&quot;has_ad&quot;)", "value" => " AND NOT <".$number.">"), 
	array("item" => "hidden", "int_name" => "crit".$number, "value" => "analytical_data_simple.analytics_type_code"),
	array("item" => "hidden", "int_name" => "op".$number, "value" => "an"), // any
	array("item" => "hidden", "int_name" => "val".$number, "value" => ""),
));
$number++;

addAnalyticsQuery($fieldsArray,$number,"analytics_type_code","gc");
addAnalyticsQuery($fieldsArray,$number,"analytics_type_name","HPLC");
addAnalyticsQuery($fieldsArray,$number,"analytics_type_name","MPLC");
addAnalyticsQuery($fieldsArray,$number,"analytics_type_code","nmr","1H");
addAnalyticsQuery($fieldsArray,$number,"analytics_type_code","nmr","13C");
addAnalyticsQuery($fieldsArray,$number,"analytics_type_name","GC-MS");
addAnalyticsQuery($fieldsArray,$number,"analytics_type_name","MS");
addAnalyticsQuery($fieldsArray,$number,"analytics_type_name","CHN");
addAnalyticsQuery($fieldsArray,$number,"analytics_type_name","IR");

$fieldsArray=array_merge($fieldsArray,array(
	array("item" => "hidden", "int_name" => "crit12", "value" => "analytical_data_simple.analytical_data_comment"),
	array("item" => "hidden", "int_name" => "op12", "value" => "ct"),
	array("item" => "input", "text" => s("analytical_data_comment"), "int_name" => "val12", "size" => 10,),

	array("item" => "hidden", "int_name" => "crit13", "value" => "analytical_data_simple.analytical_data_interpretation"),
	array("item" => "hidden", "int_name" => "op13", "value" => "ct"),
	array("item" => "input", "text" => s("analytical_data_interpretation"), "int_name" => "val13", "size" => 10,),
	"tableEnd",

	array("item" => "text", "text" => "</td><td>"), 

	"tableStart",

	array(
		"item" => "select", 
		"int_name" => "order_by", 
		"text" => s("sort"), 
		"int_names" => $reaction_order_keys, 
		"langUseValues" => true, 
		//~ "texts" => array(s("lab_journal_entry"),s("reaction_started_when")), 
		"onChange" => "rxnToSidenav(&quot;order_by&quot;); ", 
	), 

	array("item" => "check", "int_name" => "selected_only", "onChange" => "rxnToSidenav(&quot;selected_only&quot;); ", ),

	getListLogic("form","rxnToSidenav(&quot;list_op&quot;);"),
	array("item" => "text", "int_name" => "ref_reaction", "text" => "<span id=\"ref_reaction\"></span>", ), // display the reaction name which is used for comparison

	"tableEnd",
));

$paramHash=array(
	"noFieldSet" => true, 
	READONLY => false, 
	"no_db_id_pk" => true, 
	"int_name" => "reaction_search", 
	"noInputHighlight" => true, 
	"onLoad" => 'setSearchRxn(); selectUpdated("dbs"); updateDate(); var initDone=false; ', 
	"queryFields" => $queryFields, 
);

// dbs: Datenbank: alle oder bestimmte, default -1
echo 
	showHidden(array("int_name" => "fields")).
	showHidden(array("int_name" => "view")).
	// Table: reaction
	showHidden(array("int_name" => "style", "value" => "lj")).
	showHidden(array("int_name" => "prev_cache_id")). // speichert die vorherige cache_id
	showHidden(array("int_name" => "ref_cache_id")). // speichert die aktuelle cache_id
	showHidden(array("int_name" => "table", "value" => "reaction")).

	getFormElements($paramHash,$fieldsArray).
	// onChange submit, bei Textfeldern 2s warten oder [Enter] oder Verlassen
	"<table class=\"noborder\"><tr><td>".
	getImageLink(array("url" => "javascript:if (prepareRxnSearch()) { submitForm(&quot;searchForm&quot;); }", "a_class" => "imgButton", "src" => "lib/search.png", "l" => "btn_search")).
	"</td><td>".
	getImageLink(array("url" => "javascript:void document.searchForm.reset();", "a_class" => "imgButton", "src" => "lib/reset_button.png", "l" => "btn_reset")).
	"</td></tr><tr><td colspan=\"3\">".
	getViewRadio(array("onChange" => "rxnToSidenav(&quot;view_mode&quot;); ")).
	getHiddenSubmit().
	"</td></tr></table>
</td></tr></table>
</form>
".script."
updateListOp();
"._script."
</body>
</html>";

completeDoc();
?>