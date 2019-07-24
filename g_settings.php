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

$view_controls["g_settings"]=array(
	"organisation_name", 
	"workgroup_name", 
	"default_language", 
	"border_w_mm", 
	"border_h_mm", 
	//~ "no_processors", 
	"no_win_open_on_start", 
	"highlight_inputs", 
	"links_in_sidenav", 
	"links_in_topnav", 
	"use_rs", 
	"use_ghs", 
	"full_logging", 
	
	"applet_code", 
	"applet_rxn_code", 
	"applet_templates", 
	
	"name_migrate_id_cheminstor", 
	"name_migrate_id_mol", 
//~ 	"safety_sheet_lang", 
//~ 	"alt_safety_sheet_lang", 
	"scrape_alt_safety_sheet", 
	"inventory_default_hidden", 
	"print_barcodes_on_labels", 
	"global_barcodes", 
	"barcode_ignore_prefix", 
	"barcode_allow_any", 
	"barcode_sound", 
	"force_poison_cabinet", 
	"dispose_instead_of_delete", 
	
	"org_unit",
	"instr_responsible",
	"instr_defaults",
	"emergency_call",
	
	"views_molecule", 
	"views_chemical_storage", 
	
	//~ "show_rc_stoch", 
	//~ "show_rc_conc", 
	"auto_create_lj_snapshot", 
	"show_gc_tools", 
	"keep_structures", 
	"general_bilancing", 
	"bilancing_percent", 
	//~ "use_java_upload", 
	
	"customAnalyticsTabs", 
	"spz_backup_dir", 
	"limit_access_to_sigle", 
	
	"default_cost_centre", 
	"default_vat_rate", 
	"maxKleinauftrag", 
	"lagerpauschale", 
	
	"supplier_order", 
);
$view_ids["g_settings"]=array(
	"conditions", 
);
$edit_views["g_settings"]=array(
	"common" => array(
		"visibleControls" => array("organisation_name", "workgroup_name", "default_language", "border_w_mm", "border_h_mm", "no_win_open_on_start", "highlight_inputs", "links_in_sidenav", "links_in_topnav", "use_rs", "use_ghs", "full_logging", ), 
	), 
	"molecule_edit" => array(
		"visibleControls" => array("applet_code", "applet_rxn_code", "applet_templates", ), 
	), 
	"inventory" => array(
		"visibleControls" => array("inventory_default_hidden", "print_barcodes_on_labels", "global_barcodes", "barcode_ignore_prefix",  "barcode_allow_any", "barcode_sound", "views_molecule", "views_chemical_storage", "name_migrate_id_cheminstor", "name_migrate_id_mol", /*"safety_sheet_lang", "alt_safety_sheet_lang",*/"scrape_alt_safety_sheet", "force_poison_cabinet", "dispose_instead_of_delete", ), 
	), 
	"betriebsanweisung" => array(
		"visibleControls" => array("org_unit","instr_responsible","instr_defaults","emergency_call",), 
	), 
	"lab_journal" => array(
		"visibleControls" => array(
			//~ "show_rc_stoch", 
			//~ "show_rc_conc", 
			"auto_create_lj_snapshot", "show_gc_tools", "keep_structures", "general_bilancing", "bilancing_percent", ), 
		"visibleIds" => array("conditions", ), 
	), 
	"analytics" => array(
		"visibleControls" => array("customAnalyticsTabs", "spz_backup_dir", "limit_access_to_sigle", /*"use_java_upload",*/ ), 
	), 
	"order_system" => array(
		"visibleControls" => array("default_cost_centre", "default_vat_rate", "maxKleinauftrag", "lagerpauschale", ), 
	), 
	"data_acquisition" => array(
		"visibleControls" => array("supplier_order", ), 
	), 
);

$table="g_settings";
activateEditViews($table);
//~ checkViews();

echo stylesheet.
style.
getFixedStyleBlock().
_style.
loadJS(array("client_cache.js","controls.js","jsDatePick.min.1.3.js","forms.js","molecule_edit.js","searchpk.js","subitemlist_helper.js","subitemlist_get.js","subitemlist.js","edit.js","settings.js",),"lib/").
script."
readOnly=false;
editMode=false;\n";

getViewHelper($table);

echo "
activateSearch(false);
"._script."
</head>
<body>";

showCommFrame();

if ($permissions & _admin) {
	$rc_keys=array_keys($reaction_conditions);
	$defaults=getDefaultGlobalSettings();
	
	$languages=array_keys($localizedString);
	$links_in_topnav_keys=array("uni_logo","fb_logo");
	$instr_defaults_keys=array("betr_anw_gefahren","betr_anw_schutzmass","betr_anw_verhalten","betr_anw_erste_h","betr_anw_entsorgung");
	
	switch ($_REQUEST["save_settings"]) {
	case "reset":
		$g_settings=$defaults;
		setGVar("settings",$g_settings);
		$message=s("settings_reset");
	break;
	case "true":
		//~ print_r($_REQUEST);die();
		//~ "no_processors",
		arr_trans($g_settings,$_REQUEST,array(
			"organisation_name", 
			"spz_backup_dir", 
			"workgroup_name", 
			"auto_create_lj_snapshot", 
			"show_gc_tools", 
			"keep_structures", 
			"general_bilancing", 
			"bilancing_percent", 
			"name_migrate_id_cheminstor", 
			"name_migrate_id_mol", 
			"default_cost_centre", 
			"default_vat_rate", 
			"default_language", 
			"border_w_mm", 
			"border_h_mm", 
			//~ "no_processors", 
			"no_win_open_on_start", 
			"applet_code", 
			"applet_rxn_code", 
			"inventory_default_hidden", 
			"print_barcodes_on_labels", 
			"global_barcodes", 
			"barcode_ignore_prefix", 
			"barcode_allow_any", 
			"barcode_sound", 
			"org_unit",
			"instr_responsible",
			"emergency_call",
			//~ "show_rc_stoch", 
			//~ "show_rc_conc", 
			"maxKleinauftrag", 
			"lagerpauschale", 
			"force_poison_cabinet", 
			"dispose_instead_of_delete", 
			"limit_access_to_sigle", 
			//~ "use_java_upload", 
			"highlight_inputs", 
			"use_rs", 
			"use_ghs", 
			"full_logging", 
//~ 			"safety_sheet_lang", 
//~ 			"alt_safety_sheet_lang", 
			"scrape_alt_safety_sheet", 
		),true);
		
		$list_int_name="links_in_sidenav";
		$g_settings[$list_int_name]=getSubitemlistObject($list_int_name,array("text","url","target"));
		
		$list_int_name="links_in_topnav";
		$g_settings[$list_int_name]=getSubitemlistObject($list_int_name,array("name","src","url","target","w","h"),"name");
		
		$list_int_name="customAnalyticsTabs";
		$g_settings[$list_int_name]=getSubitemlistObject($list_int_name,array("key","analytics_type_name","text","showRxn","mixtureOnly"));
		
		$list_int_name="views_molecule";
		$g_settings["views"]["molecule"]=unbreakArray(getSubitemlistObject($list_int_name,"fields","key"));
		
		$list_int_name="views_chemical_storage";
		$g_settings["views"]["chemical_storage"]=unbreakArray(getSubitemlistObject($list_int_name,"fields","key"));
		
		$list_int_name="instr_defaults";
		$g_settings[$list_int_name]=getSubitemlistObject($list_int_name,$languages,"name");
		
		$list_int_name="supplier_order";
		$g_settings[$list_int_name]=unbreakArray(getSubitemlistObject($list_int_name,array("code","disabled")));
		
		$list_int_name="applet_templates";
		$g_settings[$list_int_name]=getSubitemlistObject($list_int_name,array("molfile_blob","template_name","template_shortcuts"));
		
		// add name, shortcuts into molfile	
		for ($a=0;$a<count($g_settings[$list_int_name]);$a++) { // vorerst 0 0 einfügen
			$molecule=readMolfile($g_settings[$list_int_name][$a]["molfile_blob"]);
			moleculeAddTemplateLine($molecule,$g_settings[$list_int_name][$a]["template_name"],$g_settings[$list_int_name][$a]["template_shortcuts"]);
			$g_settings[$list_int_name][$a]["molfile_blob"]=writeMolfile($molecule);
			//~ echo "<pre>".$g_settings[$list_int_name][$a]["molfile_blob"];
		}
		
		if (is_array($rc_keys)) foreach ($rc_keys as $condition) {
			$g_settings["reaction_conditions"][$condition]=$_REQUEST[$condition];
		}
		unset($g_settings["order_system"]); // crazy bug
		unset($g_settings["use_java_upload"]);
		
		applyLinkSetting(array("sidenav_links"));
		applyImgSetting($links_in_topnav_keys);
		setGVar("settings",$g_settings);
		$message=s("settings_saved");
	}

	echo getHelperTop().
		"<div id=\"browsenav\">".
		getAlignTable(
			array("<table class=\"noborder\"><tbody><tr><td><a href=\"Javascript:void submitForm(&quot;main&quot;);\" class=\"imgButtonSm\"><img src=\"lib/save_sm.png\" border=\"0\"".getTooltip("save_changes")."></a></td><td><a href=\"Javascript:void resetSettings();\" class=\"imgButtonSm\"><img src=\"lib/del_sm.png\" border=\"0\"".getTooltip("reset_settings")."></a></td><td>".$message."</td></tr></tbody></table>"), 
			array("<h1><nobr>".s("g_settings")."</nobr></h1>")
		).
		"<table id=\"tab_bar\" cellspacing=\"0\"><tr>".
		getEditViewTabs($table).
		"</tr></table>
		</div>
		<div id=\"browsemain\">
		<form name=\"main\" id=\"main\" method=\"POST\"><span id=\"temp\" style=\"display:none\"></span>".
		showHidden(array("int_name" => "save_settings", "value" => "true", )).
		getHiddenSubmit();
	
	if (is_array($rc_keys)) foreach ($rc_keys as $condition) {
		$g_settings[$condition]=$g_settings["reaction_conditions"][$condition];
	}
	
	$g_settings["views_molecule"]=array();
	$table="molecule";
	if (is_array($g_settings["views"][$table])) foreach ($g_settings["views"][$table] as $key => $fields) {
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
		
		$g_settings["views_molecule"][]=array("key" => $key, "fields" => $fields, "localized_fields" => @join(", ",$text_arr), );
	}
	
	$g_settings["views_chemical_storage"]=array();
	$table="chemical_storage";
	if (is_array($g_settings["views"][$table])) foreach ($g_settings["views"][$table] as $key => $fields) {
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
		
		$g_settings["views_chemical_storage"][]=array("key" => $key, "fields" => $fields, "localized_fields" => @join(", ",$text_arr), );
	}
	
	$instr_default_fields=array(
		array("item" => "cell"), 
		array("item" => "hidden", "int_name" => "name", ),
		array("item" => "input", "int_name" => "localizedName", DEFAULTREADONLY => "always", )
	);
	foreach ($instr_defaults_keys as $instr_defaults_key) {
		$g_settings["instr_defaults"][$instr_defaults_key]["localizedName"]=s($instr_defaults_key);
	}
	foreach ($languages as $language) {
		$instr_default_fields[]=array("item" => "cell");
		$instr_default_fields[]=array("item" => "text", "headline" => $localizedString[$language]["language_name"]);
		$instr_default_fields[]=array("item" => "input", "int_name" => $language, "type" => "textarea_classic", "cols" => 30, "rows" => 3, "text" => "");
	}
	
	if (!isset($g_settings["supplier_order"])) {
		$g_settings["supplier_order"]=$defaults["supplier_order"];
	}
	
	for ($a=count($g_settings["supplier_order"])-1;$a>=0;$a--) {
		$code=$g_settings["supplier_order"][$a]["code"];
		if (!isset($suppliers[$code])) {
			// remove, file no longer exists
			array_splice($g_settings["supplier_order"],$a,1);
		}
		else {
			// set name for list
			$g_settings["supplier_order"][$a]["name"]=$suppliers[$code]["name"];
		}
	}
	
	//~ autoAddSteps();

	// Common, Molecule editing, Inventory, Lab journal, Analytics, Order system, 
	
	$fieldsArray=array(
		"tableStart",

		//~ array("item" => "group", "int_name" => "common", "hierarchy" => 2),
		array("item" => "input", "int_name" => "organisation_name", ), 
		array("item" => "input", "int_name" => "workgroup_name", ), 
		array("item" => "language", "int_name" => "default_language", "text" => s("default_language_long"), ), 
		array("item" => "input", "int_name" => "border_w_mm", "size" => 5, ),
		array("item" => "input", "int_name" => "border_h_mm", "size" => 5, ),
		//~ array("item" => "input", "int_name" => "no_processors", "strPost" => "<br>".s("no_processors_help"), ),
		array("item" => "check", "int_name" => "highlight_inputs", ), 
		//~ getTriSelect(array("int_name" => "no_win_open_on_start", )), 
		array("item" => "check", "int_name" => "no_win_open_on_start", ), 

		array("item" => "check", "int_name" => "use_rs", "onChange" => "if (!this.checked) { setChecked(&quot;use_ghs&quot;,true); }", ), 
		array("item" => "check", "int_name" => "use_ghs", "onChange" => "if (!this.checked) { setChecked(&quot;use_rs&quot;,true); }", ), 
		
		array("item" => "check", "int_name" => "full_logging", ), 

		"tableEnd", 

		array(
			"item" => "subitemlist", 
			"int_name" => "links_in_sidenav", 
			"directDelete" => true, 
			"allowReorder" => true, 
			"fields" => array(
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "text", "size" => 20, ),
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "url", "text" => s("link_href"), "size" => 24, ),
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "target", "size" => 12, ),
			), 
		),

		array(
			"item" => "subitemlist", 
			"int_name" => "links_in_topnav", 
			"noManualAdd" => true, 
			"noManualDelete" => true, 
			"onBeforeDelete" => "return false;", 
			"fields" => array(
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "name", "text" => s("custom_image"), DEFAULTREADONLY => "always", ),
				
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "url", "text" => s("href"), "size" => 24, ),
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "target", "size" => 12, ),
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "src", "text" => s("img_src"), "size" => 20, ),
				
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "w", "text" => s("width_px"), "size" => 5, ),
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "h", "text" => s("height_px"), "size" => 5, ),
				array("item" => "hidden", "int_name" => "b", ),
				array("item" => "cell", "style" => "display:none", ), 
				array("item" => "links", ), 
			), 
		),
		//~ "<table class=\"listtable\"><tr><td>".s("custom_image")."</td><td>".s("href")."</td><td>".s("target")."</td><td>".s("img_src")."</td><td>".s("width_px")."</td><td>".s("height_px")."</td></tr>".
		//~ getImageLinkManip("uni_logo").
		//~ getImageLinkManip("fb_logo").
		//~ "</table>".

		"tableStart",

		//~ array("item" => "group", "int_name" => "molecule_edit", "hierarchy" => 2),
		array("item" => "select", "int_name" => "applet_code", "int_names" => $available_applets, ), 
		array("item" => "select", "int_name" => "applet_rxn_code", "int_names" => $available_rxn_applets, ), 
		"tableEnd",
		$appletTemplates, 

		"tableStart",
		//~ array("item" => "group", "int_name" => "inventory", "hierarchy" => 2),
		array("item" => "input", "int_name" => "name_migrate_id_cheminstor", ), 
		array("item" => "input", "int_name" => "name_migrate_id_mol", ), 
		//~ array("item" => "input", "int_name" => "safety_sheet_lang", ), 
//~ 		array("item" => "language", "int_name" => "safety_sheet_lang", ), 
		//~ array("item" => "input", "int_name" => "alt_safety_sheet_lang", ), 
//~ 		array("item" => "language", "int_name" => "alt_safety_sheet_lang", ), 
		array("item" => "check", "int_name" => "scrape_alt_safety_sheet", ), 
		array("item" => "check", "int_name" => "inventory_default_hidden", ), 
		array("item" => "check", "int_name" => "print_barcodes_on_labels", "onChange" => "if (this.checked) { setChecked(&quot;global_barcodes&quot;,false); setChecked(&quot;barcode_ignore_prefix&quot;,false); }", ), 
		array("item" => "check", "int_name" => "global_barcodes", "onChange" => "if (this.checked) { setChecked(&quot;print_barcodes_on_labels&quot;,false); }", ), 
		array("item" => "check", "int_name" => "barcode_ignore_prefix", "onChange" => "if (this.checked) { setChecked(&quot;print_barcodes_on_labels&quot;,false); }", ), 
		array("item" => "check", "int_name" => "barcode_allow_any", ), 
		array("item" => "check", "int_name" => "barcode_sound", ), 
		array("item" => "check", "int_name" => "force_poison_cabinet", ), 
		array("item" => "check", "int_name" => "dispose_instead_of_delete", ), 
		"tableEnd",

		$views_molecule, 
		$views_chemical_storage, 
		
		"tableStart",
		array("item" => "input", "int_name" => "org_unit", ), 
		array("item" => "input", "int_name" => "instr_responsible", ), 
		array("item" => "input", "int_name" => "emergency_call", "size" => 3, ),
		"tableEnd",
		array(
			"item" => "subitemlist", 
			"int_name" => "instr_defaults", 
			"noManualAdd" => true, 
			"noManualDelete" => true, 
			"noAutoLinks" => true, 
			"fields" => $instr_default_fields
		), 

		"tableStart",
		//~ array("item" => "group", "int_name" => "lab_journal", "hierarchy" => 2),
		//~ array("item" => "check", "int_name" => "show_rc_stoch", ),
		//~ array("item" => "check", "int_name" => "show_rc_conc", ),
		array("item" => "input", "int_name" => "auto_create_lj_snapshot", "size" => 2, "strPost" => s("auto_create_lj_snapshot2"), ), 
		array("item" => "check", "int_name" => "show_gc_tools", ),
		array("item" => "check", "int_name" => "keep_structures", ),
		array("item" => "check", "int_name" => "general_bilancing", ),
		array("item" => "input", "int_name" => "bilancing_percent", "size" => 3, ),
		"tableEnd", 

		array("item" => "text", "text" => "<div id=\"conditions\">"), 
		"tableStart",
		array("item" => "group", "int_name" => "block_conditions", "hierarchy" => 3),
	);
	
	if (is_array($rc_keys)) foreach ($rc_keys as $condition) {
		$fieldsArray[]=array("item" => "check", "int_name" => $condition, );
	}

	$fieldsArray=array_merge($fieldsArray,array(
		"tableEnd", 
		array("item" => "text", "text" => "</div>"), 

		// Subitemlist analytik-Tabs
		// analytics_type_name | Text | showRxn | mixtureOnly
		$customAnalyticsTabs,

		"tableStart", 
		//~ array("item" => "group", "int_name" => "analytics", "hierarchy" => 2),
		array("item" => "input", "int_name" => "spz_backup_dir", ), 
		array("item" => "check", "int_name" => "limit_access_to_sigle", ), 
		//~ array("item" => "check", "int_name" => "use_java_upload", ), 

		//~ array("item" => "group", "int_name" => "order_system", "hierarchy" => 2),
		array(
			"item" => "pk_select", 
			"int_name" => "default_cost_centre", 
			"table" => "cost_centre", 
			"allowNone" => true, 
			"dbs" => -1, 
			"order_by" => getOrderObjFromKey("cost_centre","cost_centre"), 
			"pkName" => "cost_centre_id", 
			"nameField" => "cost_centre", 
		),
		array("item" => "input", "int_name" => "default_vat_rate", "size" => 3, "maxlength" => 5, ), 
		array("item" => "input", "int_name" => "maxKleinauftrag", "size" => 4, ), 
		array("item" => "input", "int_name" => "lagerpauschale", "size" => 4, "type" => "percent", ), 
		"tableEnd", 

		array("item" => "subitemlist", "int_name" => "supplier_order", "noManualAdd" => true, "noManualDelete" => true, "allowReorder" => true, 
			"fields" => array(
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "name", DEFAULTREADONLY => "always", ),
				array("item" => "hidden", "int_name" => "code", ),
				array("item" => "cell"), 
				array("item" => "checkbox", "int_name" => "disabled", "value" => 1, ),
				array("item" => "cell"), 
				array("item" => "links"), 
			), 
		), 
		array("item" => "hidden", "int_name" => "view", ), 
	));

	echo getFormElements(array(
		READONLY => false, 
		"noFieldSet" => true, 
	),
	$fieldsArray);
}
else {
	displayFatalError("permission_denied");
}

//~ print_r($g_settings);

// Umsetzen auf numerische Indices
reIndex($g_settings["links_in_topnav"],$links_in_topnav_keys,"name");
reIndex($g_settings["instr_defaults"],$instr_defaults_keys,"name");

echo "</form>
</div>".
getHelperBottom().
script."
setControlValues(".json_encode($g_settings).",false);
activateEditView();
"._script."
</body>
</html>";

completeDoc();
?>