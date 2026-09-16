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
Einstellungsseite, zZt nicht fertig und nicht eingebaut
a) global default_language, default_currency,Bessi_name
b) per User preferred_language, change_passwd
ich denke, wir sollten die Tabelle global_settings umbauen zu serialisiertem BLOB, nur die Text-Spalte bleibt
*/
require_once "lib_global_funcs.php";
require_once "lib_constants_default_settings.php";
require_once "lib_global_settings.php";
require_once "lib_simple_forms.php";
require_once "lib_applet.php";
require_once "lib_settings.php";

pageHeader();

require_once "lib_supplier_scraping.php";

$no_access_to_labj=!($permissions&_lj_read); // avoid permission denied on accessing tables lab_journal, project
$view_controls["settings"]=array(
	"default_per_page", 
	"no_win_open_on_start", 
	"default_login_target", 
	"clear_on_logout", 
	"other_db_order", 
	
	"preferred_language", 
	"email", 
	"tel_no", 
	"custom_border", 
	"border_w_mm", 
	"border_h_mm", 
	
	"disable_reaction_mouseover", 
	"disable_molecule_mouseover", 
	"applet_code", 
	"applet_rxn_code", 
	"applet_templates", 
	
	"views_molecule", 
	"views_chemical_storage", 
	
	"default_lj", 
	"default_project", 
	"digits_count", 
	"hide_safety", 
	"std_smiles", 
	"m_standard", 
	"realisation_templates", 
	"observation_templates", 
	"do_not_use_inventory", 
	"keep_structures", 
	"general_bilancing", 
	
	"disable_analytical_data_mouseover", 
	"usePersonalAnalyticsTabs", 
	"customAnalyticsTabs", 
	//~ "use_java_upload", 
	"analytics_type_order", 
	"analytics_device_order", 
);
$view_ids["settings"]=array(
	
);
$edit_views["settings"]=array(
	"customisation" => array(
		"visibleControls" => array("default_per_page", "no_win_open_on_start", "default_login_target", "clear_on_logout", "other_db_order", ), 
	), 
	"common" => array(
		"visibleControls" => array("preferred_language", "email", "tel_no", "custom_border", "border_w_mm", "border_h_mm", ), 
	), 
	"molecule_edit" => array(
		"visibleControls" => array("disable_reaction_mouseover", "disable_molecule_mouseover", "applet_code", "applet_rxn_code", "applet_templates", ), 
	), 
	"inventory" => array(
		"visibleControls" => array("views_molecule", "views_chemical_storage", ), 
	), 
	"lab_journal" => array(
		"visibleControls" => array("default_lj", "default_project", "digits_count", "hide_safety", "std_smiles", "m_standard", "realisation_templates", "observation_templates", "keep_structures", "general_bilancing", "do_not_use_inventory", ), 
	), 
	"analytics" => array(
		"visibleControls" => array(/*"use_java_upload", */"disable_analytical_data_mouseover", "usePersonalAnalyticsTabs", "customAnalyticsTabs", "analytics_type_order", "analytics_device_order", ), 
	), 
);
$table="settings";
activateEditViews($table);

echo stylesheet.
style.
getFixedStyleBlock().
_style.
loadJS(array("client_cache.js","controls.js","jsDatePick.min.1.3.js","forms.js","molecule_edit.js","searchpk.js","subitemlist_helper.js","subitemlist_get.js","subitemlist.js","edit.js","settings.js",),"lib/").
loadJS(array("wyzz.js")). // wyzz
script."
readOnly=false;
editMode=false;\n";

getViewHelper($table);

echo "activateSearch(false);
"._script."
</head>
<body>";

showCommFrame();

$own_data_settings=array("preferred_language", "email", );

switch ($_REQUEST["save_settings"]) {
case "reset":
	$settings=getDefaultUserSettings();
	saveUserSettings();
	setOtherDbData(); // re-read to apply new settings
	$message=s("settings_reset");
break;
case "true":
	// save preferred_language, which is field in table person
	
	arr_trans($own_data,$_REQUEST,$own_data_settings,true);
	
	arr_trans($settings,$_REQUEST,array(
		"m_standard", 
		"std_smiles", 
		"lj_round_type", 
		"digits_count", 
		"hide_safety", 
		"tel_no", 
		"custom_border", 
		"border_w_mm", 
		"border_h_mm", 
		
		"disable_reaction_mouseover", 
		"disable_molecule_mouseover", 
		"applet_code", 
		"applet_rxn_code", 
		"no_win_open_on_start", 
		"default_login_target", 
		"default_per_page", 
		"clear_on_logout", 
		
		"disable_analytical_data_mouseover", 
		"usePersonalAnalyticsTabs", 
		//~ "use_java_upload", 
		"do_not_use_inventory", 
		"keep_structures", 
		"general_bilancing", 
	),true);
	
	if (!$no_access_to_labj) {
		// otherwise keep unchanged
		arr_trans($settings,$_REQUEST,array(
			"default_lj", 
			"default_project", 
		),true);
	}
	
	// save text modules
	$list_int_name="realisation_templates";
	$settings[$list_int_name]=getSubitemlistObjectRaw($list_int_name,array("template_name","template_text"));
	
	$list_int_name="observation_templates";
	$settings[$list_int_name]=getSubitemlistObjectRaw($list_int_name,array("template_name","template_text"));
	
	$list_int_name="applet_templates";
	$settings[$list_int_name]=getSubitemlistObject($list_int_name,array("molfile_blob","template_name","template_shortcuts"));
	// add name, shortcuts into molfile	
	for ($a=0;$a<count($settings[$list_int_name]);$a++) { // vorerst 0 0 einfügen
		$molecule=readMolfile($settings[$list_int_name][$a]["molfile_blob"]);
		moleculeAddTemplateLine($molecule,$settings[$list_int_name][$a]["template_name"],$settings[$list_int_name][$a]["template_shortcuts"]);
		$settings[$list_int_name][$a]["molfile_blob"]=writeMolfile($molecule);
		//~ echo "<pre>".$settings[$list_int_name][$a]["molfile_blob"];
	}
	
	$list_int_name="customAnalyticsTabs";
	$settings[$list_int_name]=getSubitemlistObject($list_int_name,array("key","analytics_type_name","text","showRxn","mixtureOnly"));
		
	$list_int_name="views_molecule";
	$settings["views"]["molecule"]=unbreakArray(getSubitemlistObject($list_int_name,"fields","key"));
	
	$list_int_name="views_chemical_storage";
	$settings["views"]["chemical_storage"]=unbreakArray(getSubitemlistObject($list_int_name,"fields","key"));
	
	$list_int_name="other_db_order";
	$settings[$list_int_name]=getSubitemlistObject($list_int_name,"other_db_id");
	
	if (!$no_access_to_labj) {
		// otherwise keep unchanged
		$list_int_name="analytics_type_order";
		$settings[$list_int_name]=getSubitemlistObject($list_int_name,"analytics_type_id");
		
		$auto_trans_fields=array("analytics_type_id");
		for ($a=0;$a<analytics_transfer_profiles;$a++) {
			$auto_trans_fields[]="include_in_auto_transfer_".$a;
		}
		$auto_trans=getSubitemlistObject($list_int_name,$auto_trans_fields);
		$settings["include_in_auto_transfer"]=array();
	
		for ($a=0;$a<analytics_transfer_profiles;$a++) {
			$settings["include_in_auto_transfer"][$a]=array();
			for ($b=0;$b<count($auto_trans);$b++) {
				if ($auto_trans[$b]["include_in_auto_transfer_".$a]) {
					$settings["include_in_auto_transfer"][$a][]=$auto_trans[$b]["analytics_type_id"];
				}
			}
		}
	
		$list_int_name="analytics_device_order";
		$settings[$list_int_name]=getSubitemlistObject($list_int_name,"analytics_device_id");
	}
	
	unset($own_data_settings["use_java_upload"]);
	
	saveUserSettings($own_data_settings);
	setOtherDbData(); // re-read to apply new settings
	$message=s("settings_saved");
}

echo getHelperTop().
	"<div id=\"browsenav\">".
	getAlignTable(
		array("<table class=\"noborder\"><tbody><tr><td><a href=\"Javascript:prepareSubmitForms(); submitForm(&quot;main&quot;);\" class=\"imgButtonSm\"><img src=\"lib/save_sm.png\" border=\"0\"".getTooltip("save_changes")."></a></td><td><a href=\"Javascript:void resetSettings();\" class=\"imgButtonSm\"><img src=\"lib/del_sm.png\" border=\"0\"".getTooltip("reset_settings")."></a></td><td>".$message."</td></tr></tbody></table>"), 
		array("<h1>".s("settings")."</h1>")
	).
	"<table id=\"tab_bar\" cellspacing=\"0\"><tr>".
	getEditViewTabs($table).
	"</tr></table>
	</div>
	<div id=\"browsemain\">
	<form name=\"main\" id=\"main\" method=\"POST\"><span id=\"temp\" style=\"display:none\"></span>".
	showHidden(array("int_name" => "save_settings", "value" => "true", )).
	getHiddenSubmit();

// views aus hierarchischem in subitemlist-Format bringen
$settings["views_molecule"]=array();
$table="molecule";
if (is_array($settings["views"][$table])) foreach ($settings["views"][$table] as $key => $fields) {
	$fields=unbreakArray($fields);
	if (empty($fields)) {
		list($field_arr)=getFields($columns[$table],"");
	}
	else {
		$field_arr=explode(",",$fields);
	}
	
	$text_arr=array();
	if (is_array($field_arr)) foreach ($field_arr as $field) {
		$text_arr[]=s($field);
	}
	
	$settings["views_molecule"][]=array("key" => $key, "fields" => $fields, "localized_fields" => @join(", ",$text_arr), );
}

$settings["views_chemical_storage"]=array();
$table="chemical_storage";
if (is_array($settings["views"][$table])) foreach ($settings["views"][$table] as $key => $fields) {
	$fields=unbreakArray($fields);
	if (empty($fields)) {
		list($field_arr)=getFields($columns[$table],"");
	}
	else {
		$field_arr=explode(",",$fields);
	}
	
	$text_arr=array();
	if (is_array($field_arr)) foreach ($field_arr as $field) {
		$text_arr[]=s($field);
	}
	
	$settings["views_molecule"][]=array("key" => $key, "fields" => $fields, "localized_fields" => @join(", ",$text_arr), );
}

// Reihenfolge der Fremddatenbanken aus hierarchischem in subitemlist-Format bringen
$settings["other_db_order"]=$other_db_data;
if (!$no_access_to_labj) {
	$settings["analytics_type_order"]=mysql_select_array(array(
		"table" => "analytics_type", 
		"dbs" => -1, 
		"order_obj" => getUserDefOrderObj("analytics_type"), 
	));

	// auto-transfer listen in subitemlist-Format bringen
	if (is_array($settings["include_in_auto_transfer"])) {
		for ($a=0;$a<analytics_transfer_profiles;$a++) {
			if (!is_array($settings["include_in_auto_transfer"][$a])) {
				continue;
			}
			for ($b=0;$b<count($settings["analytics_type_order"]);$b++) {
				if (in_array($settings["analytics_type_order"][$b]["analytics_type_id"],$settings["include_in_auto_transfer"][$a])) {
					$settings["analytics_type_order"][$b][$a]["include_in_auto_transfer"]=1;
				}
			}
		}
	}

	$analyticsTypeOrderFields=array(
		array("item" => "cell"), 
		array("item" => "input", "int_name" => "analytics_type_name", DEFAULTREADONLY => "always", ),
		array("item" => "hidden", "int_name" => "analytics_type_id", ),
		array("item" => "cell"), 
		array("item" => "text", "headline" => s("include_in_auto_transfer"), ), 
	);

	for ($a=0;$a<analytics_transfer_profiles;$a++) { // standardmäßig 3 Profile
		$analyticsTypeOrderFields[]=array("item" => "check", "int_name" => "include_in_auto_transfer", "text" => "", "value" => 1, "group" => $a, );
	}
	$analyticsTypeOrderFields[]=array("item" => "cell");
	$analyticsTypeOrderFields[]=array("item" => "links");

	$settings["analytics_device_order"]=mysql_select_array(array(
		"table" => "analytics_device", 
		"dbs" => -1, 
		"order_obj" => getUserDefOrderObj("analytics_device"), 
	));
}

// Daten für $owndata ins Formular holen
arr_trans($settings,$own_data,$own_data_settings);

unset($loginTargets["barcode_terminal"]); // do not offer to users

// Customisation, Common, Molecule editing, Inventory, Lab journal, Analytics, Order system
echo getFormElements(
	array(
		READONLY => false, 
		"noFieldSet" => true, 
		"setControlValues" => "updateMolecules(); ", 
	),
	array(

	"tableStart",

	//~ array("item" => "group", "int_name" => "customisation", "hierarchy" => 2),
	array("item" => "input", "int_name" => "default_per_page", "size" => 3, ), 
	getTriSelectSettings(array("int_name" => "no_win_open_on_start", )), 
	array("item" => "select", "int_name" => "default_login_target", "int_names" => array_keys($loginTargets), ), 
	array("item" => "check", "int_name" => "clear_on_logout", ), 
	"tableEnd", 
	array("item" => "subitemlist", "int_name" => "other_db_order", "noManualAdd" => true, "noManualDelete" => true, "allowReorder" => true, 
		"fields" => array(
			array("item" => "cell"), 
			array("item" => "input", "int_name" => "db_beauty_name", DEFAULTREADONLY => "always", ),
			array("item" => "hidden", "int_name" => "other_db_id", ),
			array("item" => "cell"), 
			array("item" => "links"), 
		), 
	), 

	//~ array("item" => "group", "int_name" => "common", "hierarchy" => 2),
	"tableStart",
	array("item" => "language", "int_name" => "preferred_language", ),
	array("item" => "input", "int_name" => "email", "size" => 10, ),
	array("item" => "input", "int_name" => "tel_no", "size" => 10, ),
	array("item" => "check", "int_name" => "custom_border", ),
	array("item" => "input", "int_name" => "border_w_mm", "size" => 5, ),
	array("item" => "input", "int_name" => "border_h_mm", "size" => 5, ),

	//~ array("item" => "group", "int_name" => "molecule_edit", "hierarchy" => 2),
	array("item" => "check", "int_name" => "disable_molecule_mouseover", ), 
	array("item" => "check", "int_name" => "disable_reaction_mouseover", ), 
	array("item" => "select", "int_name" => "applet_code", "int_names" => $available_applets, "allowAuto" => true, "autoText" => s("default"), ), 
	array("item" => "select", "int_name" => "applet_rxn_code", "int_names" => $available_rxn_applets, "allowAuto" => true, "autoText" => s("default"), ), 
	"tableEnd", 
	$appletTemplates, 
	//~ array("item" => "group", "int_name" => "inventory", "hierarchy" => 2),
	$views_molecule, 
	$views_chemical_storage, 

	"tableStart",
	//~ array("item" => "group", "int_name" => "lab_journal", "hierarchy" => 2),
	array(
		"item" => "pk_select", 
		"int_name" => "default_lj", 
		"table" => "lab_journal", 
		"allowNone" => true, 
		"dbs" => -1, 
		"filter" => "lab_journal.person_id=".fixNull($person_id), 
		"order_by" => getOrderObjFromKey("lab_journal_code","lab_journal"), 
		"pkName" => "lab_journal_id", 
		"nameField" => "lab_journal_code", 
		"skip" => $no_access_to_labj,
	),
	array(
		"item" => "pk_select", 
		"int_name" => "default_project", 
		"table" => "project", 
		"allowNone" => true, 
		"dbs" => -1, 
		"filter" => "project_person.person_id=".fixNull($person_id), 
		"order_by" => getOrderObjFromKey("project_name","project"), 
		"pkName" => "project_id", 
		"nameField" => "project_name", 
		"skip" => $no_access_to_labj,
	),
	// Anzahl
	array("item" => "input", "int_name" => "digits_count", "size" => 3, ), 
	// feste/signifikante Kommastellen
	array(
		"item" => "select", 
		"int_name" => "lj_round_type", 
		"int_names" => array("significant","fixed"), 
		SPLITMODE => true, 
	), 
	array("item" => "check", "int_name" => "hide_safety", "value" => 1, ), 

	array("item" => "input", "int_name" => "std_smiles", "size" => 14, ), 
	array("item" => "input", "int_name" => "m_standard", "size" => 3, ), 

	// use custom settings for analytik-tabs?
	getTriSelectSettings(array("int_name" => "keep_structures", )), 
	getTriSelectSettings(array("int_name" => "general_bilancing", )), 
	array("item" => "check", "int_name" => "do_not_use_inventory", ), 
	array("item" => "check", "int_name" => "disable_analytical_data_mouseover", ), 
	array("item" => "check", "int_name" => "usePersonalAnalyticsTabs", ), 
	//~ getTriSelectSettings(array("int_name" => "use_java_upload", )), 

	"tableEnd", 
	// Subitemlist analytik-Tabs
	// filterbed | Text | show Rxn | mixture only
	$customAnalyticsTabs,

	array(
		"item" => "subitemlist", 
		"int_name" => "analytics_type_order", 
		"noManualAdd" => true, 
		"noManualDelete" => true, 
		"allowReorder" => true, 
		"fields" => $analyticsTypeOrderFields, 
	), 
	array("item" => "subitemlist", "int_name" => "analytics_device_order", "noManualAdd" => true, "noManualDelete" => true, "allowReorder" => true, 
		"fields" => array(
			array("item" => "cell"), 
			array("item" => "input", "int_name" => "analytics_device_name", DEFAULTREADONLY => "always", ),
			array("item" => "hidden", "int_name" => "analytics_device_id", ),
			array("item" => "cell"), 
			array("item" => "input", "int_name" => "analytics_type_name", DEFAULTREADONLY => "always", ),
			array("item" => "cell"), 
			array("item" => "links"), 
		), 
	), 

	array("item" => "subitemlist", "int_name" => "realisation_templates", "directDelete" => true, "allowReorder" => true, 
		"fields" => array(
			array("item" => "cell"), 
			array("item" => "input", "int_name" => "template_name", "size" => 12, ),
			array("item" => "cell"), 
			array("item" => "input", "int_name" => "template_text", "type" => "textarea", ),
		), 
	),
	array("item" => "subitemlist", "int_name" => "observation_templates", "directDelete" => true, "allowReorder" => true, 
		"fields" => array(
			array("item" => "cell"), 
			array("item" => "input", "int_name" => "template_name", "size" => 12, ),
			array("item" => "cell"), 
			array("item" => "input", "int_name" => "template_text", "type" => "textarea", ),
		), 
	),
	array("item" => "hidden", "int_name" => "view", ), 
)).
"</form>
</div>".
getHelperBottom().
script."
setControlValues(".json_encode($settings).",false);
activateEditView();
"._script."
</body>
</html>";

completeDoc();
?>