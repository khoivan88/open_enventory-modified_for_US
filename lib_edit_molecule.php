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
function showMoleculeEditForm($paramHash) { // requires chemJs
	global $lang,$editMode,$g_settings,$permissions,$selectTables,$localizedString,$iso_protection_symbols,$iso_no_symbols,$iso_first_aid_symbols;
	
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"molecule");
	
	$paramHash["setControlValues"]=
		'updateSearchCommercial(values["cas_nr"],values["smiles"]); '.
		'updateSafety(); '.
		'readExtFeedback(); '.
		'if (readOnly==true) { '.
			'setTitle(strcut(a(values,"molecule_name"),30)); '.
		'} ';
	
	$paramHash["roundMode"]=getRoundMode($settings["lj_round_type"]);
	$paramHash["decimals"]=getDecimals($settings["digits_count"]);
	
	if ($paramHash["barcodeTerminal"]) {
		$bigClass="barcodeBig";
	}
	
	$paramHash["checkSubmit"]=
		'var molData=computeMolecule(getControlValue("molfile_blob"),0); '.
		'if (getControlValue("molecule_names_edit")=="" && molData["chemFormula"]=="") { '
			.'alert("'.s("error_molecule_name").'");'
			.'focusInput("molecule_names_edit"); '
			.'return false;'
		.'} '
		.'if (molData["chemFormula"]=="" && !confirm('.fixStr(s("warning_no_structure")).')) { '
			.'return false;'
		.'} ';

	if ($paramHash["no_db_id_pk"]) { // "nebenformular" für gebinde
		$paramHash["change"][READONLY].=
			'var molecule_id=getControlValue("molecule_id"),action_molecule=getControlValue("action_molecule"); '
			.'if (molecule_id && action_molecule=="" && !thisValue) { '
				.'return false; '
			.'}';
	}
	
	$paramHash["structuresUpdated"]=
		'if (structureData) {'.
			'UID=getControlUID("molfile_blob"); '.
			'setControlValues(structureData[UID]); '.
		'}';
	
	$symbols_text="</td></tr>
<tr><td><div id=\"symbols\" style=\"height:62px\">&nbsp;</div></td></tr>
<tr><td id=\"Rclause\">&nbsp;</td><td id=\"Sclause\">&nbsp;</td></tr>
<tr><td id=\"Hclause\">&nbsp;</td><td id=\"Pclause\">&nbsp;</td></tr>
";
	
	// print_r($paramHash);
	
	$rs_visible=($g_settings["use_rs"]?"input":"hidden");
	$ghs_visible=($g_settings["use_ghs"]?"input":"hidden");
	
	$lang_int_names=array_keys($localizedString);
	$lang_texts=array();
	foreach ($lang_int_names as $lang_int_name) {
		$lang_texts[]=$localizedString[$lang_int_name]["language_name"];
	}
	$iso_protection_no_symbols=array_merge($iso_protection_symbols,$iso_no_symbols);
	$iso_protection_no_files=array();
	foreach ($iso_protection_no_symbols as $symbol) {
		$iso_protection_no_files[]=$symbol.".png";
	}
	$iso_first_aid_files=array();
	foreach ($iso_first_aid_symbols as $symbol) {
		$iso_first_aid_files[]=$symbol.".png";
	}
	
	$retval=loadJS(array("safety_".$lang.".js"),"lib/").
		getFormElements($paramHash,array(
			array("item" => "text", "text" => "<table><tr><td rowspan=\"2\">"), 

			array("item" => "input", "int_name" => "molecule_names_edit", "editHelp" => showBr().s("molecule_names_info"), "type" => "textarea_classic", "cols" => 60,"rows" => 4, ), // no rich-edit
			 
			"tableStart", 
			array("item" => "input", "int_name" => "cas_nr", "size" => 10,"maxlength" => 20, "class" => $bigClass, ), 
			array("item" => "text", SPLITMODE => true, "rw" => "<span id=\"readExt\"><input type=\"button\" onClick=\"readExt()\" value=".fixStr(s("read_ext"))."><br><span id=\"readExtFeedback\"></span></span>"), 
			array("item" => "input", "int_name" => "smiles_stereo", "size" => 20,"maxlength" => 80, "softLineBreakAfter" => 20, DEFAULTREADONLY => "always"), 
			array("item" => "input", "int_name" => "emp_formula", "size" => 10,"maxlength" => 30, "postProc" => "emp_formula", "handleDisplay" => "return getBeautySum(displayValue);", "onMouseover" => "editMouseoverCHN", "onMouseout" => "editHideOverlay"), 
			array("item" => "input", "int_name" => "mw", "size" => 7,"maxlength" => 20, "type" => "round", "decimals" => 2),
			array("item" => "input", "int_name" => "density_20", "size" => 10,"maxlength" => 20), 
			array(
				"item" => "pk_select", 
				"int_name" => "molecule_type", 
				"table" => "molecule_type", 
				"dbs" => -1, 
				"pkName" => "molecule_type_id", 
				"nameField" => "molecule_type_name", 
				"separator" => "; ", 
				"multiMode" => true, 
				"size" => 3, 
				"maxTextLen" => 40, 
			),

			array("item" => $rs_visible, "int_name" => "safety_sym", "onChange" => "updateSafety(&quot;sym&quot;);updateInstructions()", "size" => 10, ), 
			array("item" => $ghs_visible, "int_name" => "safety_sym_ghs", "onChange" => "updateSafety(&quot;sym&quot;);updateInstructions()", "size" => 10, ), 
			array("item" => $rs_visible, "int_name" => "safety_r", "onChange" => "updateSafety(&quot;r&quot;);updateInstructions()", "size" => 30, "onMouseover" => "showRSTooltipEdit", "onMouseout" => "hideOverlay", ), 
			array("item" => $rs_visible, "int_name" => "safety_s", "onChange" => "updateSafety(&quot;s&quot;);updateInstructions()", "size" => 30, "onMouseover" => "showRSTooltipEdit", "onMouseout" => "hideOverlay",  ), 
			array("item" => $ghs_visible, "int_name" => "safety_h", "onChange" => "updateSafety(&quot;h&quot;);updateInstructions()", "size" => 30, "onMouseover" => "showRSTooltipEdit", "onMouseout" => "hideOverlay",  ), 
			array("item" => $ghs_visible, "int_name" => "safety_p", "onChange" => "updateSafety(&quot;p&quot;);updateInstructions()", "size" => 30, "onMouseover" => "showRSTooltipEdit", "onMouseout" => "hideOverlay",  ), 

			array("item" => "input", "int_name" => "safety_text", "onChange" => "updateInstructions()", "size" => 40, ), 
			array("item" => "input", "int_name" => "safety_wgk", "onChange" => "updateInstructions()", "size" => 3, "maxlength" => 3), 
			array("item" => "input", "int_name" => "safety_danger", "size" => 3, "maxlength" => 5), 
			array("item" => "input", "int_name" => "safety_cancer", "onChange" => "updateInstructions()", "size" => 3, "maxlength" => 5), 
			array("item" => "input", "int_name" => "safety_mutagen", "onChange" => "updateInstructions()", "size" => 3, "maxlength" => 5), 
			array("item" => "input", "int_name" => "safety_reprod", "onChange" => "updateInstructions()", "size" => 3, "maxlength" => 5), 

			array("item" => "sds", "int_name" => "default_safety_sheet", "text" => s("safety_sheet"), "pkName" => "molecule_id"), 
			array("item" => "sds", "int_name" => "alt_default_safety_sheet", "text" => s("alt_safety_sheet"), "pkName" => "molecule_id"), 
			
			getTriSelectForm(array("int_name" => "molecule_bilancing", "skip" => !$g_settings["general_bilancing"], )), 
			
			array("item" => "input", "int_name" => "molecule_btm_list", "size" => 2,"maxlength" => 1, ), 
			array("item" => "input", "int_name" => "molecule_sprengg_list", "size" => 2,"maxlength" => 2, ), 
			array("item" => "check", "int_name" => "pos_liste", "skip" => !$g_settings["force_poison_cabinet"], DEFAULTREADONLY => ($permissions & _chemical_edit?"":"always"), ), 
			array("item" => "check", SPLITMODE => true, "int_name" => "neg_liste", "skip" => !$g_settings["force_poison_cabinet"], DEFAULTREADONLY => ($permissions & _chemical_edit?"":"always"), ), 

			array("item" => "check", "int_name" => "molecule_secret"), 
			array("item" => "input", "int_name" => "n_20", "size" => 10,"maxlength" => 20), 
			array("item" => "input", "int_name" => "mp", "size" => 10,"maxlength" => 20,"type" => "range", "strPost" => "°C "), 

			array("item" => "input", "int_name" => "bp", "size" => 10,"maxlength" => 20,"type" => "range", "strPost" => "°C "), 
			array("item" => "text", SPLITMODE => true, "text" => s("bp_press")), 
			array("item" => "input", SPLITMODE => true, "int_name" => "bp_press", "size" => 5,"maxlength" => 10), 
			array(
				"item" => "pk_select", 
				SPLITMODE => true, 
				"int_name" => "press_unit", 
				"pkName" => "unit_name", 
				"dbs" => "-1", 
				"table" => "units", 
				"nameField" => "unit_name", 
				"filter" => "unit_type LIKE BINARY \"p\"", 
				"setValues" => 'return a(selected_values,"unit_name");', 
				"defValue" => "mbar", 
			), 


			array("item" => "input", "int_name" => "comment_mol", "type" => "textarea", "cols" => 40, "rows" => 4, ), 
			array("item" => "input", "int_name" => "migrate_id_mol", "text" => $g_settings["name_migrate_id_mol"], "size" => 10, "skip" => empty($g_settings["name_migrate_id_mol"]), ), 
			array("item" => "text", SPLITMODE => true, "rw" => "<input type=\"button\" onClick=\"createBESSI()\" value=".fixStr(s("createBESSI")).">", "skip" => (empty($g_settings["name_migrate_id_mol"]) || !($permissions & _order_accept)), ), 
			"tableEnd", 

			array("item" => "input", "int_name" => "molecule_created_by", DEFAULTREADONLY => "always"), 
			array("item" => "input", "int_name" => "molecule_created_when", DEFAULTREADONLY => "always", "type" => "date"), 
			"br",
			array("item" => "input", "int_name" => "molecule_changed_by", DEFAULTREADONLY => "always"), 
			array("item" => "input", "int_name" => "molecule_changed_when", DEFAULTREADONLY => "always", "type" => "date"), 

			array("item" => "text", "text" => "</td><td>"), 

			array(
				"item" => "structure", 
				"int_name" => "molfile_blob", 
				"height" => gif_y, 
				"width" => gif_x, 
				"pkName" => "molecule_id", 
				"nameField" => "molecule_name", 
				"posFlags" => OVERLAY_LIMIT_BOTTOM+OVERLAY_LIMIT_RIGHT, 
				"showDelButton" => true, 
				"showMolfileButton" => true, 
				"showGifButton" => true, 
				"showCopyPasteButton" => true, 
				"autoUpdate" => true, 
				"desired_action" => "loadData", // directly have sum formula and MW
			), 
			array("item" => "text", "text" => $symbols_text), 

			// verfügbare Gebinde
			array("item" => "text", "text" =>"<tr><td colspan=\"2\">", "skip" => $paramHash["no_db_id_pk"] || !$editMode), 
			array(
				"item" => "subitemlist", 
				"int_name" => "chemical_storage", 
				"skip" => $paramHash["no_db_id_pk"] || !$editMode, 
				DEFAULTREADONLY => "always", 
				"fields" => array(
					array("item" => "cell"), 
					array("item" => "hidden", "int_name" => "chemical_storage_id", ),
					array("item" => "input", "roundMode" => $paramHash["roundMode"], "decimals" => $paramHash["decimals"], "int_name" => "amount", "size" => 4),
					array("item" => "text", "value" => "&nbsp;/ "), 
					array("item" => "input", "type" => "round", "roundMode" => $paramHash["roundMode"], "decimals" => $paramHash["decimals"], "int_name" => "actual_amount", "size" => 4),
					array("item" => "text", "value" => "&nbsp;"), 
					
					array(
						"item" => "pk_select", 
						"int_name" => "amount_unit", 
						"pkName" => "unit_name", 
						"dbs" => "-1", 
						"table" => "units", 
						"nameField" => "unit_name", 
						"filter" => "unit_type IN(\"m\",\"v\")", 
						"setValues" => 'return a(selected_values,"unit_name");',
						"onChange" => "rxnValueChanged", 
					),
					
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "container", "size" => 8),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "storage_name", "size" => 10),
					array("item" => "text", "value" => "&nbsp;"),
					array("item" => "input", "int_name" => "compartment", "size" => 3),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "open_date", "type" => "date", "size" => 10),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "expiry_date", "type" => "date", "size" => 10),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "comment_cheminstor", "type" => "textarea_classic", "cols" => 30, "rows" => 2),
					array("item" => "cell"), 
					array(
						"item" => "button", 
						"onClick" => "transferThisEntryToUID", 
						"class" => "imgButtonSm", 
						"img" => "lib/select_sm.png", 
						"int_name" => "do_select", 
						"skip" => !in_array("chemical_storage",$selectTables), 
					), 
					array(
						"item" => "js", 
						"int_name" => "detailbutton", 
						"functionBody" => "get_reference_link(\"chemical_storage\",values[\"db_id\"],values[\"chemical_storage_id\"]);", 
						"class" => "noprint", 
					), 
				) 
			), 
			array("item" => "text", "text" =>"</td></tr>", "skip" => $paramHash["no_db_id_pk"] || !$editMode), 

			// Angebote in DB
			array("item" => "text", "text" =>"<tr><td colspan=\"2\">", "skip" => $paramHash["no_db_id_pk"] || !$editMode), 
			array(
				"item" => "subitemlist", 
				"int_name" => "supplier_offer", 
				"skip" => $paramHash["no_db_id_pk"] || !$editMode, 
				DEFAULTREADONLY => "always", 
				"fields" => array(
					array("item" => "cell"), 
					array("item" => "hidden", "int_name" => "supplier_offer_id", ),
					array("item" => "input", "int_name" => "so_package_amount", "size" => 4),
					array("item" => "text", "value" => "&nbsp;"), 
					
					array(
						"item" => "pk_select", 
						"int_name" => "so_package_amount_unit", 
						"pkName" => "unit_name", 
						"dbs" => "-1", 
						"table" => "units", 
						"nameField" => "unit_name", 
						"filter" => "unit_type IN(\"m\",\"v\")", 
						"setValues" => 'return a(selected_values,"unit_name");',
						"onChange" => "rxnValueChanged", 
					),
					
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "so_purity", "size" => 8),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "beautifulCatNo", "size" => 8),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "supplier", "size" => 10),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "so_price", "size" => 4),
					array("item" => "text", "value" => "&nbsp;"),
					array("item" => "input", "int_name" => "so_price_currency", "size" => 4),
					array("item" => "cell"), 
					array(
						"item" => "button", 
						"onClick" => "transferThisEntryToUID", 
						"class" => "imgButtonSm", 
						"img" => "lib/select_sm.png", 
						"int_name" => "do_select", 
						"skip" => !in_array("supplier_offer",$selectTables), 
					), 
					array(
						"item" => "js", 
						"int_name" => "detailbutton", 
						"functionBody" => "get_reference_link(\"supplier_offer\",values[\"db_id\"],values[\"supplier_offer_id\"]);", 
						"class" => "noprint", 
					), 
				) 
			), 
			array("item" => "text", "text" =>"</td></tr>", "skip" => $paramHash["no_db_id_pk"] || !$editMode), 

			// Moleküleigenschaften
			array("item" => "text", "text" => "<tr><td colspan=\"2\">"), 
			array(
				"item" => "subitemlist", 
				"int_name" => "molecule_property", 
				"unique_fields" => array("source","class","value","unit"), // applies to noOverwrite
				"lineInitFunction" => 
					'moleculePropertyClassChanged(list_int_name,UID,"class"); ',
				"setFunction" => 
					// have 1 int_names and 1 text for molecule,ch_st
					'var int_name="unit"; '.
					'var val=a(values,int_name); '.
					'SILsetUnitSelect(list_int_name,UID,int_name,undefined,a(values,"class"),val); ',

				"fields" => array(
					array("item" => "cell"), 
					//~ array("item" => "input", "int_name" => "class", "size" => 10),
					array("item" => "pk_select", "int_name" => "class", "pkName" => "class_name", "dbs" => "-1", "table" => "class", "nameField" => "class_name_local", "onChange" => "moleculePropertyClassChanged"),
					
					array("item" => "cell"), 
					array("item" => "input", "type" => "range", "int_name" => "value", "size" => 10), // Zahl(enbereich)
					array("item" => "text", "value" => "&nbsp;", "headline" => "/"), 
					//~ array("item" => "input", "int_name" => "unit", "size" => 5),
					array("item" => "select", "int_name" => "unit", "size" => 5), // EInheit
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "conditions", "type" => "textarea_classic", "cols" => 30, "rows" => 2), // oder Textwerte
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "source", "size" => 10),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "molecule_property_comment", "size" => 10),
					array("item" => "hidden", "int_name" => "molecule_property_id"),
					array("item" => "hidden", "int_name" => "molecule_property_created_by"),
					array("item" => "hidden", "int_name" => "molecule_property_created_when"),
					array("item" => "hidden", "int_name" => "molecule_property_changed_by"),
					array("item" => "hidden", "int_name" => "molecule_property_changed_when"),
				) 
			), 
			
			// Betriebsanweisung
			array("item" => "text", "text" => "<tr><td colspan=\"2\">"), 
			array(
				"item" => "subitemlist", 
				"int_name" => "molecule_instructions", 
				"noManualAdd" => true, 
				"lineInitFunction" => 
					'var protEquip=getProtEquip(getControlValue("safety_s"),getControlValue("safety_p"),getControlValue("safety_h")),langVal=getControlValue("lang"),instr_defaults='.json_encode(arr_safe($g_settings["instr_defaults"])).';'.
					'SILsetValuesUID(list_int_name,UID,pos,{lang:langVal,'. // set lang to current one
					'betr_anw_schutzmass_sym:protEquip,'.
					'betr_anw_verhalten_sym:protEquip,'.
					'betr_anw_erste_h_sym:"E003",'.
					'betr_anw_gefahren:a(instr_defaults,"betr_anw_gefahren",langVal),'. // set language-specific default values as defined in global settings
					'betr_anw_schutzmass:a(instr_defaults,"betr_anw_schutzmass",langVal),'.
					'betr_anw_verhalten:a(instr_defaults,"betr_anw_verhalten",langVal),'.
					'betr_anw_erste_h:a(instr_defaults,"betr_anw_erste_h",langVal),'.
					'betr_anw_entsorgung:a(instr_defaults,"betr_anw_entsorgung",langVal)'.
					'}); ',

				"fields" => array(
					array("item" => "cell"), 
					array(
						"headline" => s("user_lang"),
						"item" => "select", 
						"int_name" => "lang", 
						"int_names" => $lang_int_names, 
						"texts" => $lang_texts, 
						"size" => 1, 
						"text" => "", 
					), 
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "betr_anw_gefahren", "type" => "textarea_classic", "cols" => 15, "rows" => 4),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "betr_anw_schutzmass", "type" => "textarea_classic", "cols" => 30, "rows" => 3),
					array("item" => "text", "value" => "<hr/>", "headline" => ""), 
					array(
						"item" => "checkset", 
						"int_name" => "betr_anw_schutzmass_sym", 
						"int_names" => $iso_protection_no_symbols, 
						"images" => $iso_protection_no_files,
						"width" => 30,
						"roList" => true, 
						"breakAfter" => 5, 
					), 
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "betr_anw_verhalten", "type" => "textarea_classic", "cols" => 30, "rows" => 3),
					array("item" => "text", "value" => "<hr/>", "headline" => ""), 
					array(
						"item" => "checkset", 
						"int_name" => "betr_anw_verhalten_sym", 
						"int_names" => $iso_protection_no_symbols, 
						"images" => $iso_protection_no_files, 
						"width" => 30,
						"roList" => true, 
						"breakAfter" => 5, 
					), 
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "betr_anw_erste_h", "type" => "textarea_classic", "cols" => 30, "rows" => 3),
					array("item" => "text", "value" => "<hr/>", "headline" => ""), 
					array(
						"item" => "checkset", 
						"int_name" => "betr_anw_erste_h_sym", 
						"int_names" => $iso_first_aid_symbols, 
						"images" => $iso_first_aid_files, 
						"width" => 30,
						"roList" => true, 
						"breakAfter" => 1, 
					), 
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "betr_anw_entsorgung", "type" => "textarea_classic", "cols" => 20, "rows" => 5),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "molecule_instructions_comment", "type" => "textarea_classic", "cols" => 15, "rows" => 4),
					array("item" => "hidden", "int_name" => "molecule_instructions_id"),
					array("item" => "hidden", "int_name" => "molecule_instructions_created_by"),
					array("item" => "hidden", "int_name" => "molecule_instructions_created_when"),
					array("item" => "hidden", "int_name" => "molecule_instructions_changed_by"),
					array("item" => "hidden", "int_name" => "molecule_instructions_changed_when"),
					
					array("item" => "cell", "colspan" => 1, "class" => "noprint", ),
					array("item" => "js", "int_name" => "btn_download", "functionBody" => '"<a href=\"getInstructions.php?db_id="+values["db_id"]+"&molecule_instructions_id="+values["molecule_instructions_id"]+"\" class=\"imgButtonSm\" target=\"_blank\"><img src=\"lib/edit_sm.png\" border=\"0\"'.addslashes(getTooltip("downloadPDF")).'></a>"'),
					array("item" => "links"), 
				) 
			), 
			
			array("item" => "text", "text" => "<table><tr><td>"), 
			array(
				"headline" => s("user_lang"),
				"item" => "select", 
				"int_name" => "lang", 
				"int_names" => $lang_int_names, 
				"texts" => $lang_texts, 
				"value" => $lang,
				"size" => 1, 
				"text" => "", 
			),
			array("item" => "text", "text" => "</td><td>"), 
			array("item" => "text", "int_name" => "molecule_instructions_add_line", "rwInputs" => "<a class=\"imgButtonSm\" href=\"javascript:void SILaddLine(&quot;molecule_instructions&quot;)\"><img border=\"0\"".getTooltip("add_line")." src=\"lib/add_line_sm.png\"></a>", ),
			array("item" => "text", "text" => "</td></tr></table>"), 
			
			// MPI
			array(
				"item" => "subitemlist", 
				"int_name" => "mat_stamm_nr", 
				"skip" => !($permissions & _order_accept), 
				"fields" => array(
					array("item" => "cell"), 
					array("item" => "hidden", "int_name" => "mat_stamm_nr_id", ), 
					array("item" => "input", "int_name" => "sap_stamm_nr", "size" => 15, ), 
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "comment_stamm_nr", "size" => 15, ), 
				) 
			), 
			array("item" => "text", "text" => "</td></tr></table>") 
		));
	
	return $retval;
}
?>