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
require_once "lib_edit_reaction_subitemlist.php";
//~ require_once "lib_edit_literature.php";
require_once "lib_formatting.php";


function showReactionEditForm($paramHash) { // gibt es nur im editMode. Beim Neuerstellen wird erst gespeichert und dann die leere Reaktion geöffnet
	global $lang,$person_id,$permissions,$g_settings,$settings,$reaction_conditions;
	
	$paramHash["roundMode"]=getRoundMode($settings["lj_round_type"]);
	$paramHash["decimals"]=getDecimals($settings["digits_count"]);
	
	
	$list_int_name="realisation_templates";
	if (is_array($settings[$list_int_name])) foreach($settings[$list_int_name] as $template) {
		$realisation_templates_names[]=strip_tags($template["template_name"]);
		$realisation_templates[]=rawurlencode(utf8_decode(makeHTMLSafe($template["template_text"])));
	}
	
	$list_int_name="observation_templates";
	if (is_array($settings[$list_int_name])) foreach($settings[$list_int_name] as $template) {
		$observation_templates_names[]=strip_tags($template["template_name"]);
		$observation_templates[]=rawurlencode(utf8_decode(makeHTMLSafe($template["template_text"])));
	}
	
	$paramHash["int_name"]="reaction";
	$paramHash["noFieldSet"]=true;
	$paramHash["onLoad"]=
		'updateTh("reactants"); '.
		'updateTh("products"); ';

	$paramHash["onPrint"]=
		'var values=dataCache[a_db_id][a_pk]; '.
		'if (values) { '.
			'setiHTML("reaction_barcode",getReactionBarcode(values["db_id"],a(values,"products",0),values["lab_journal_code"],values["nr_in_lab_journal"],'.fixStr($g_settings["workgroup_name"]).',values["reaction_id"])); '.
		'} ';
	
	$statusButtons="<table class=\"noborder\"><tr>";
	$max_status=5;
	if ($permissions & _admin) {
		$max_status=6;
	}
	$status_int_names=getValueList("reaction","status");
	for ($a=1;$a<$max_status;$a++) {
		
		$statusButtons.="<td id=".fixStr("td_status_".$a).
			"><a id=".fixStr("a_status_".$a)." href=\"javascript:void setReactionStatus(".$a.")\"><img src=\"lib/status_".$a."_sm.png\" border=\"0\"".getTooltip($status_int_names[$a-1])."></a></td>";
	}
	$statusButtons.="</tr></table>";
	
	$literature_paramHash=getLiteratureParamHash();
	$literature_paramHash["int_name"]="reaction_literature";
	$literature_paramHash["fields"][]=array("item" => "hidden", "int_name" => "reaction_literature_id");

	$paramHash["onActivateView"]='activateSearch(false); ';
	$paramHash["change"][READONLY]=
		'updateInProgress=true; '.
		'updateTh("reactants"); '.
		'updateTh("products"); '.
		'showControl("btn_close_lj",!thisValue); '.
		'showControl("fix_stoch",!thisValue); '.
		'visibleObj("btn_add_standard",!thisValue); '.
		'visibleObj("btn_calc_response",!thisValue); '.
		'if (thisValue==false) { '. // aktiven status rot setzen, vorherige ausblenden, nachfolgende anzeigen
			'PkSelectUpdate("project_id"); '.
			'initialStatus=getControlValue("status"); '.
			'updateStatusButtons(); '.
		'} '.
		'updateInProgress=false; ';
	
	$paramHash["structuresUpdated"]='handleStructureData(structureData); ';
	
	if (($permissions & _lj_edit)==0 && ($permissions & _lj_edit_own)!=0) { // brauchen wir das noch???
		$studentMode="formulare[\"reaction\"][\"disableEdit\"]=(getCacheValue(\"person_id\")!=".fixNull($person_id)."); updateButtons(); ";
	}
	
	$paramHash["setControlValues"]=
		'if (readOnly==false) { '.
			'updateRcUID(); '. // Selects setzen
			'updateGCyield(); '.
		'} '.
		'else { '.
			'var info_text=a(values,"lab_journal_code")+" "+a(values,"nr_in_lab_journal"),project_id=a(values,"project_id"); '.
			'showInfo("<b>"+info_text+"</b>"); '.
			'setTitle(info_text); '.
			'visibleObj("btn_show_project",project_id!=""); '. // filter auf Projekt beschränken
			'visibleObj("btn_goto_project",project_id!=""); '.
			'if (ref_reaction) {'.
				'applyComparisonList("edit"); '.
			'}'.
			$studentMode.
		'} ';
	
	$subitemlists=getSubitemlists($paramHash);
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
	
	$fieldsArray=array(
		// Einheiten Spaltenköpfe
		// reactants
		array("item" => "check", "class" => "small_input", "freeMode" => true, "additionalField" => true, "int_name" => "fix_stoch", "text" => "<img src=\"lib/lock_stoich.png\" class=\"noprint\" border=\"0\"/>", VISIBLE => false, ), 

		array(
			"item" => "pk_select", 
			"class" => "small_input", 
			"freeMode" => true, 
			"additionalField" => true, 
			"int_name" => "reactants_rc_amount_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filter" => "unit_type LIKE BINARY \"n\"", 
			"allowNone" => true, 
			"noneText" => s("custom"), 
			"filterDisabled" => true, 
			"setValues" => 
				'return a(selected_values,"unit_name");',
			"onChange" => "thSelectChange(".fixQuot("reactants").",".fixQuot("rc_amount_unit").",[".fixQuot("reagents")."])", 
		), 

		array(
			"item" => "pk_select", 
			"class" => "small_input", 
			"freeMode" => true, 
			"additionalField" => true, 
			"int_name" => "reactants_mass_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filter" => "unit_type LIKE BINARY \"m\"", 
			"allowNone" => true, 
			"noneText" => s("custom"), 
			"filterDisabled" => true, 
			"setValues" => 
				'return a(selected_values,"unit_name");',
			"onChange" => "thSelectChange(".fixQuot("reactants").",".fixQuot("mass_unit").",[".fixQuot("reagents")."])", 
		), 

		array(
			"item" => "pk_select", 
			"class" => "small_input", 
			"freeMode" => true, 
			"additionalField" => true, 
			"int_name" => "reactants_rc_conc_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filter" => "unit_type COLLATE utf8_bin IN(\"m/m\",\"c\",\"molal\")", 
			"allowNone" => true, 
			"noneText" => s("custom"), 
			"filterDisabled" => true, 
			"setValues" => 
				'return a(selected_values,"unit_name");',
			"onChange" => "thSelectChange(".fixQuot("reactants").",".fixQuot("rc_conc_unit").",[".fixQuot("reagents")."])", 
		), 

		array(
			"item" => "pk_select", 
			"class" => "small_input", 
			"freeMode" => true, 
			"additionalField" => true, 
			"int_name" => "reactants_volume_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filter" => "unit_type LIKE BINARY \"v\"", 
			"allowNone" => true, 
			"noneText" => s("custom"), 
			"filterDisabled" => true, 
			"setValues" => 
				'return a(selected_values,"unit_name");',
			"onChange" => "thSelectChange(".fixQuot("reactants").",".fixQuot("volume_unit").",[".fixQuot("reagents")."])", 
		), 

		// products
		array(
			"item" => "pk_select", 
			"class" => "small_input", 
			"freeMode" => true, 
			"additionalField" => true, 
			"int_name" => "products_mass_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filter" => "unit_type LIKE BINARY \"m\"", 
			"allowNone" => true, 
			"noneText" => s("custom"), 
			"filterDisabled" => true, 
			"setValues" => 
				'return a(selected_values,"unit_name");',
			"onChange" => "thSelectChange(".fixQuot("products").",".fixQuot("mass_unit").")", 
		), 

		array(
			"item" => "pk_select", 
			"class" => "small_input", 
			"freeMode" => true, 
			"additionalField" => true, 
			"int_name" => "products_rc_amount_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filter" => "unit_type LIKE BINARY \"n\"", 
			"allowNone" => true, 
			"noneText" => s("custom"), 
			"filterDisabled" => true, 
			"setValues" => 
				'return a(selected_values,"unit_name");',
			"onChange" => "thSelectChange(".fixQuot("products").",".fixQuot("rc_amount_unit").")", 
		), 


		// hier gehts los-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		array("item" => "hidden", "int_name" => "lab_journal_id"),
		array("item" => "hidden", "int_name" => "person_id"), // person the LJ belongs to
		array("item" => "text", "text" => "<table class=\"noborder\" id=\"reaction_table\" style=\"width:100%;max-width:100%;border-collapse:collapse;margin-bottom:1cm\">
			<colgroup>
				<col style=\"width:40%\"/>
				<col style=\"width:30%\"/>
				<col style=\"width:30%\"/>
			</colgroup>
			<tbody><tr class=\"block_head\"><td>"), 

		array("item" => "tableStart", "class" => "noborder"), 
		// ARN-NA
		array("item" => "input", "text" => "", "int_name" => "lab_journal_code", DEFAULTREADONLY => "always", "class" => "lj_code", ), // nur info
		array("item" => "input", SPLITMODE => true, "text" => "", "int_name" => "nr_in_lab_journal", DEFAULTREADONLY => "always", "class" => "lj_code", ), // nur info

		// Projekt
		array(
			"item" => "pk_select", 
			"int_name" => "project_id", 
			"table" => "project", 
			"dbs" => "-1", 
			"nameField" => "project_name", 
			"allowNone" => true, 
			"noneText" => s("none"), 
			"dynamic" => true, 
			"filterDisabled" => true, 
			"getFilter" => 'return "query=<0>&crit0=project_person.person_id&op0=eq&val0="+getControlValue("person_id");', 
			"getText" => 'return rowData["project_name"];', 
		),
		array("item" => "text", SPLITMODE => true, "ro" => "<a href=\"Javascript:gotoProject();\" id=\"btn_goto_project\" class=\"noprint\"><img src=\"lib/external.png\" border=\"0\"".getTooltip("goto_project")."></a>", ), 

		// Reaktionstyp
		array(
			"item" => "pk_select", 
			"int_name" => "reaction_type_id", 
			"table" => "reaction_type", 
			"dbs" => "-1", 
			"nameField" => "reaction_type_name", 
			"allowNone" => true, 
			"noneText" => s("none"), 
		),
		// änderbar // , "filter" => "project_person.person_id=".fixNull($person_id)

		// Status
		array("item" => "select", "int_name" => "status", "int_names" => range(1,6), "langKeys" => $status_int_names, DEFAULTREADONLY => "always"), 
		array("item" => "text", "int_name" => "btn_close_lj", "text" => $statusButtons),
		"tableEnd",

		array("item" => "text", "text" => "</td><td>"), 

		array("item" => "tableStart", "class" => "noborder"), 

		// Ansatzzettel
		array("item" => "text", "int_name" => "ansatzzettel", "class" => "text_ansatzzettel"), 

		// Titel
		array("item" => "input", "int_name" => "reaction_title", ), 

		// Datum
		array("item" => "input", "int_name" => "reaction_started_when", "type" => "date", ), //, DEFAULTREADONLY => "always"

		// Ausführender
		array("item" => "input", "int_name" => "reaction_carried_out_by"), 

		"tableEnd",

		array("item" => "text", "text" => "</td><td>"), 

		array("item" => "tableStart", "class" => "noborder"), 

		//~ array("item" => "js", "int_name" => "reaction_barcode", "functionBody" => "getReactionBarcode(values[\"db_id\"],a(values,\"products\",0),values[\"lab_journal_code\"],values[\"nr_in_lab_journal\"],".fixStr($g_settings["workgroup_name"]).",values[\"reaction_id\"]);"), 
		array("item" => "text", "text" => "<tr><td id=\"reaction_barcode\" colspan=\"2\"></td></tr>"), 
		array("item" => "text", "text" => "<tr class=\"print_only\"><td id=\"witness\" colspan=\"2\" style=\"font-size:6pt;width:6cm;height:1.3cm\">".s("witness1")."<hr noshade=\"noshade\">".s("witness2")."<hr noshade=\"noshade\"></td></tr>"), 

		"tableEnd",

		array("item" => "text", "text" => "</td></tr><tr id=\"block_equation\"><td colspan=\"3\">"), 

		// Reaktionsgleichung, start Tabelle
		array(
			"item" => "structure", 
			"int_name" => "rxnfile_blob", 
			"width" => rxn_gif_x, 
			"height" => rxn_gif_y, 
			"pkName" => "reaction_id", 
			"nameField" => "lab_journal_code", 
			"showDelButton" => true, 
			"showMolfileButton" => true, 
			"showGifButton" => true, 
			"posFlags" => OVERLAY_LIMIT_TOP+OVERLAY_SCROLL_X+OVERLAY_HIDE_SHORT_Y, // horizontal only
			"showCopyPasteButton" => true, 
			"mode" => "rxn", 
			"onChange" => "rxnChanged();", 
			"class" => "reaction_eqn", 
		), // split rxnfile and invoke update
		array("item" => "hidden", "int_name" => "rxn_smiles"), 

		array("item" => "text", "text" => "</td></tr><tr id=\"block_response\"><td colspan=\"3\" id=\"btn_calc_response\"><a class=\"imgButtonSm\" href=\"javascript:void openCalcResponse()\"><img src=\"lib/response_factor_sm.png\" border=\"0\"".getTooltip("calc_response_factor")."></a>", "skip" => !$g_settings["show_gc_tools"], ), 

		//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		array("item" => "text", "text" => "</td></tr><tr id=\"compare_rxn\"><td id=\"compare_rxn_td\" colspan=\"3\">"), 
		array("item" => "text", "text" => "</td></tr><tr id=\"block_conditions\"><td style=\"text-align:center\" colspan=\"3\">"), 

		// Eigenschaften der Reaktion, aus Subquery action=flat
		array("item" => "tableStart", TABLEMODE => "hl", ), 
		);
			$additionalProps=[];
			if (is_array($reaction_conditions)) foreach ($reaction_conditions as $condition => $data) { // save all properties even if not shown
				$prop=array("item" => $g_settings["reaction_conditions"][$condition]?"input":"hidden", "int_name" => $condition, "size" => ifempty($data["size"],5), "additionalField" => true, );
				if ($data["bottom"]) {
					$additionalProps[]=$prop;
				} else {
					$fieldsArray[]=$prop;
				}
			}
			if (count($additionalProps)) {
				array_unshift($additionalProps,array("item" => "text", "text" => "</td><td rowspan=\"2\" style=\"vertical-align:top\">"),"tableStart");
				$additionalProps[]="tableEnd";
			}
			

			$fieldsArray=array_merge($fieldsArray,array(
		array(
			"item" => "input", 
			"int_name" => "ref_amount", 
			"size" => 5, 
			"onChange" => "refValueChanged(); ", 
			"doEval" => true, 
			"type" => "round", 
			"roundMode" => $paramHash["roundMode"], 
			"decimals" => $paramHash["decimals"], 
		), 
		array(
			"item" => "pk_select", 
			"int_name" => "ref_amount_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			SPLITMODE => true, 
			"filterDisabled" => true, 
			"filter" => "unit_type LIKE BINARY \"n\"", 
			"setValues" => 
				'return a(selected_values,"unit_name"); ', 
			"defValue" => "mmol", 
			"onChange" => "refValueChanged(); ", 
		), 

		array("item" => "text", "text" => "<td id=\"btn_add_standard\"><a class=\"imgButtonSm\" href=\"javascript:void addStandard()\"><img src=\"lib/add_standard_sm.png\" border=\"0\"".getTooltip("add_standard")."></a></td>", "skip" => !$g_settings["show_gc_tools"], ), 
		array("item" => "tableEnd", TABLEMODE => "hl"), 


		//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		array("item" => "text", "text" => "</td></tr><tr id=\"block_educts\"><td colspan=\"3\">"), 

		// Reaktanten
		$subitemlists["reactants"], 

		// Reagenzien
		$subitemlists["reagents"], 


		//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		array("item" => "text", "text" => "</td></tr><tr id=\"block_realization\"><td colspan=\"3\"><table style=\"width:100%\"><tbody><tr>"), 
	),
	$additionalProps,
	array(
		array("item" => "text", "text" => "<td>"), 

		// Durchführung
		array("item" => "input", "int_name" => "realization_text", "type" => "textarea", "rows" => 10,"cols" => 80, "br" => false, ), 
		
		array("item" => "text", "text" => "</td><td>"), 
		
		// Textbausteine
		array(
			"item" => "select", 
			"int_name" => "realisation_templates", 
			"int_names" => $realisation_templates, 
			"texts" => $realisation_templates_names, 
			"size" => 10, 
			"text" => "", 
			"onChange" => "updateSel();", 
			"onDblClick" => "addTemplateToInput(&quot;realisation_templates&quot;,&quot;realization_text&quot;); ", 
			"skip" => (count($realisation_templates_names)==0), 
		), 
		
		array("item" => "text", "text" => "</td></tr></tbody></table></td></tr><tr id=\"block_observation\"><td colspan=\"3\"><table style=\"width:100%\"><tbody><tr><td>"), 

		// Beobachtung
		array("item" => "input", "int_name" => "realization_observation", "type" => "textarea", "rows" => 7,"cols" => 80, "br" => false, ), 

		array("item" => "text", "text" => "</td><td>"), 
		
		// Textbausteine
		array(
			"item" => "select", 
			"int_name" => "observation_templates", 
			"int_names" => $observation_templates, 
			"texts" => $observation_templates_names, 
			"size" => 7, 
			"text" => "", 
			"onChange" => "updateSel();", 
			"onDblClick" => "addTemplateToInput(&quot;observation_templates&quot;,&quot;realization_observation&quot;); ", 
			"skip" => (arrCount($observation_templates_names)==0), 
		), 


		//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		array("item" => "text", "text" => "</td></tr></tbody></table></td></tr><tr id=\"block_safety\"><td colspan=\"3\">"), 

		// Sicherheit
		array("item" => "input", "int_name" => "betr_anw_gefahren", "type" => "textarea", "additionalField" => true, "rows" => 5,"cols" => 80, ), 
		array("item" => "input", "int_name" => "betr_anw_schutzmass", "type" => "textarea", "additionalField" => true, "rows" => 8,"cols" => 80, ), 
		array("item" => "input", "int_name" => "betr_anw_verhalten", "type" => "textarea", "additionalField" => true, "rows" => 4,"cols" => 80, ), 
		array("item" => "input", "int_name" => "betr_anw_erste_h", "type" => "textarea", "additionalField" => true, "rows" => 6,"cols" => 80, ), 
		array("item" => "input", "int_name" => "betr_anw_entsorgung", "type" => "textarea", "additionalField" => true, "rows" => 3,"cols" => 80, ), 

		//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		array("item" => "text", "text" => "</td></tr><tr id=\"block_products\"><td colspan=\"3\">"), 

		// Produkte: mmol, Produkt, Formel, MW, GC, mg, Reinheit, %isol
		$subitemlists["products"], 
		array("item" => "check", "int_name" => "retained_product", "additionalField" => true, ),

		array("item" => "text", "text" => "</td></tr><tr id=\"block_log\"><td colspan=\"3\">"), 
		array("item" => "input", "int_name" => "reaction_created_by", DEFAULTREADONLY => "always"), 
		array("item" => "input", "int_name" => "reaction_created_when", DEFAULTREADONLY => "always", "type" => "date"), 
		"br",
		array("item" => "input", "int_name" => "reaction_changed_by", DEFAULTREADONLY => "always"), 
		array("item" => "input", "int_name" => "reaction_changed_when", DEFAULTREADONLY => "always", "type" => "date"), 

		//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		array("item" => "text", "text" => "</td></tr><tr id=\"block_analytics\"><td colspan=\"3\">"), 

		// Analytik
		$subitemlists["analytical_data"], 

		// Literatur
		array("item" => "text", "text" => "</td></tr><tr id=\"block_literature\"><td colspan=\"3\">"), 

		$literature_paramHash,
		array("item" => "text", "text" => "</td></tr><tbody></table>"),

	));
	
	$retval=loadJS(array("safety_".$lang.".js","reaction.js","reaction_calc.js","reaction_structure.js","reaction_analytics.js","compare_rxn.js"),"lib/").
	getFormElements($paramHash,$fieldsArray);
	
	return $retval;
}

?>