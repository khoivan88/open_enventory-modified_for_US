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
function getReaFields($paramHash) {
	global $g_settings,$settings;
	
	$show_gc_tools=$g_settings["show_gc_tools"]??false;
	$use_rs=$g_settings["use_rs"]??false;
	$use_ghs=$g_settings["use_ghs"]??false;
	$hide_safety=$settings["hide_safety"]??false;
	
	return array(
		array("item" => "hidden", "int_name" => "reaction_chemical_id"),
		array("item" => "cell", ), // "skip" => !$g_settings["show_rc_stoch"], 
		array("item" => "text", "freemodeHeadline" => "fix_stoch", ), // "skip" => !$g_settings["show_rc_stoch"], 
		array("item" => "input", "int_name" => "stoch_coeff", "size" => 1, "class" => "small_input", "doEval" => true, "onChange" => "stochCoeffChanged", "type" => "round", "roundMode" => $paramHash["roundMode"], "decimals" => $paramHash["decimals"], ), // "skip" => !$g_settings["show_rc_stoch"], 
		
		array("item" => "cell", "class" => "noprint"),
		array(
			"item" => "structure", 
			"int_name" => "molfile_blob", 
			"pkName" => "reaction_chemical_id", 
			"showEditButton" => true, 
			"showDelButton" => false, 
			"showGifButton" => false, 
			"showMolfileButton" => false, 
			"showCopyPasteButton" => true, 
			"onChange" => "molChanged", 
			"height" => rc_gif_y, 
			"width" => rc_gif_x, 
		), 
		array("item" => "cell"),
		
		array("item" => "select", "onChange" => "molSelectChanged", "int_name" => "molecule_id", "class" => "small_input"),
		array("item" => "hidden", "int_name" => "from_reaction_id", ),
		
		array("item" => "span", "int_name" => "info1"),
		
		array("item" => "hidden", "int_name" => "other_db_id"),
		array("item" => "text", "value" => "<br/>"),
		
		array("item" => "select", "onChange" => "chemSelectChanged", "int_name" => "chemical_storage_id", "class" => "small_input"), 
		array("item" => "hidden", "int_name" => "from_reaction_chemical_id", ),
		
		array("item" => "span", "int_name" => "info2"),
		
		array("item" => "text", "value" => " "),
		array("item" => "input", DEFAULTREADONLY => "always", "int_name" => "chemical_storage_barcode", "class" => "small"),
		array("item" => "text", "rw" => "<br/>"),
		array("item" => "button", "onClick" => "searchMolecule", "img" => "lib/chemical_storage_sm.png", "hideReadOnly" => true, "class" => "button_very_small", ), // Suchknopf für Molekül oder Gebinde
		array("item" => "button", "onClick" => "searchReaction", "img" => "lib/reaction_sm.png", "hideReadOnly" => true, "class" => "button_very_small", ), // Suchknopf für Reaktion
		array("item" => "button", "onClick" => "editRc", "img" => "lib/edit_rc_sm.png", "hideReadOnly" => true, "class" => "button_very_small", ), // manuell eintragen
		
		array("item" => "hidden", "int_name" => "standard_name"),
		array("item" => "hidden", "int_name" => "package_name"),
		//~ array("item" => "hidden", "int_name" => "cas_nr"),
		array("item" => "hidden", "int_name" => "smiles"),
		array("item" => "hidden", "int_name" => "smiles_stereo"),
		array("item" => "cell", "class" => "noprint"),
		array(
			"item" => "input", 
			"int_name" => "emp_formula", 
			"text" => s("rc_emp_formula"), 
			"postProc" => "emp_formula", 
			"size" => 9, 
			"class" => "small_input", 
			"onChange" => "rcEmpFormulaChanged", 
			"handleDisplay" => "return getBeautySum(displayValue);", 
			"onMouseover" => "SILmouseoverCHN", 
			"onMouseout" => "SILhideOverlay", 
		),
		array("item" => "text", "value" => "<br/>"), 
		array("item" => "input", "int_name" => "cas_nr", "size" => 9, ),
		array("item" => "cell"),
		array("item" => "input", "int_name" => "mw", "size" => 5, "onChange" => "rxnValueChanged", "class" => "small_input", "doEval" => true, "type" => "round", "decimals" => 2),
		array("item" => "cell"),
		array("item" => "input", "int_name" => "rc_amount", "size" => 5, "onChange" => "rxnValueChanged", "class" => "small_input", "doEval" => true, "type" => "round", "roundMode" => $paramHash["roundMode"], "decimals" => $paramHash["decimals"], ),

		array("item" => "text", "freemodeHeadline" => "reactants_rc_amount_unit", "headline" => " <nobr>["), 
		array("item" => "text", "headline" => "]</nobr>", "value" => " "), 
		
		array(
			"item" => "pk_select", 
			"int_name" => "rc_amount_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filterDisabled" => true, 
			"filter" => "unit_type LIKE BINARY \"n\"", 
			"setValues" => 
'return a(selected_values,"unit_name");',
			"onChange" => "rxnValueChanged", 
			"class" => "small_input", 
		),
		
		// conc start
		array("item" => "cell", "class" => "noprint", ), // "skip" => !$g_settings["show_rc_conc"], 
		array("item" => "input", "int_name" => "rc_conc", "size" => 3, "onChange" => "rxnValueChanged", "class" => "small_input", "doEval" => true, ), 
		array("item" => "text", "freemodeHeadline" => "reactants_rc_conc_unit", "headline" => " <nobr>[", ), 
		array("item" => "text", "headline" => "]</nobr>", "value" => " ", ), 
		array(
			"item" => "pk_select", 
			"int_name" => "rc_conc_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filterDisabled" => true, 
			"filter" => "unit_type COLLATE utf8_bin IN(\"m/m\",\"c\",\"molal\")", 
			"setValues" => 
'return a(selected_values,"unit_name");',
			"onChange" => "rxnValueChanged", 
			"class" => "small_input", 
		),
		// conc end
		
		array("item" => "cell"),
		array("item" => "input", "int_name" => "m_brutto", "size" => 5, "onChange" => "rxnValueChanged", "class" => "small_input", "doEval" => true, "type" => "round", "roundMode" => $paramHash["roundMode"], "decimals" => $paramHash["decimals"], ),

		array("item" => "text", "freemodeHeadline" => "reactants_mass_unit", "headline" => " <nobr>["), 
		array("item" => "text", "headline" => "]</nobr>", "value" => " "), 
		
		array(
			"item" => "pk_select", 
			"int_name" => "mass_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filterDisabled" => true, 
			"filter" => "unit_type LIKE BINARY \"m\"", 
			"setValues" => 
'return a(selected_values,"unit_name");',
			"onChange" => "rxnValueChanged", 
			"class" => "small_input", 
		),
		
		array("item" => "cell", "class" => "noprint", ),
		array("item" => "input", "int_name" => "density_20", "text" => s("rc_density_20"), "size" => 3, "onChange" => "rxnValueChanged", "class" => "small_input", "type" => "round", "decimals" => 3),
		
		array("item" => "cell"),
		array("item" => "input", "int_name" => "volume", "size" => 5, "onChange" => "rxnValueChanged", "class" => "small_input", "doEval" => true, "type" => "round", "roundMode" => $paramHash["roundMode"], "decimals" => $paramHash["decimals"], ),
		
		array("item" => "text", "freemodeHeadline" => "reactants_volume_unit", "headline" => " <nobr>["), 
		array("item" => "text", "headline" => "]</nobr>", "value" => " ", ), 
		
		array(
			"item" => "pk_select", 
			"int_name" => "volume_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filterDisabled" => true, 
			"filter" => "unit_type LIKE BINARY \"v\"", 
			"setValues" => 
'return a(selected_values,"unit_name");',
			"onChange" => "rxnValueChanged", 
			"class" => "small_input", 
		),

		array("item" => "hidden", "int_name" => "measured"), 
		
		array("item" => "cell", "class" => "noprint", "skip" => !$show_gc_tools, ), 
		array("item" => "input", "int_name" => "gc_yield", "text" => s("remaining_reactants_short"), "size" => 5, "class" => "small_input", "type" => "round", "decimals" => yield_digits, "roundMode" => yield_mode, "skip" => !$show_gc_tools, ),
		array("item" => "hidden", "int_name" => "gc_yield", "skip" => $show_gc_tools, ),
		
		array("item" => "cell", "skip" => $hide_safety, ),
		array(
			"item" => ($hide_safety || !$use_rs?"hidden":"input"), 
			"int_name" => "safety_sym", 
			"text" => s("rc_safety_sym"), 
			"size" => 1, 
			"class" => "small_input", 
			"handleDisplay" => "return getSymbols(\"rs\",displayValue,31,31);", 
		),
		array(
			"item" => ($hide_safety || !$use_ghs?"hidden":"input"), 
			"int_name" => "safety_sym_ghs", 
			"text" => s("rc_safety_sym_ghs"), 
			"size" => 1, 
			"class" => "small_input", 
			"handleDisplay" => "return getSymbols(\"ghs\",displayValue,31,31);", 
		),
		// RS
		array("item" => "cell", "class" => "noprint", "skip" => $hide_safety || !$use_rs, ),
		array(
			"item" => ($hide_safety || !$use_rs?"hidden":"input"), 
			"int_name" => "safety_r", 
			"text" => s("rc_safety_r"), 
			"size" => 4, 
			"class" => "small_input", 
			"onMouseover" => "SILmouseoverRS", 
			"onMouseout" => "SILhideOverlay", 
		),
		array("item" => "text", "value" => "<br/>", "skip" => $hide_safety || !$use_rs, ), 
		array(
			"item" => ($hide_safety || !$use_rs?"hidden":"input"), 
			"int_name" => "safety_s", 
			"text" => s("rc_safety_s"), 
			"size" => 4, 
			"class" => "small_input", 
			"onMouseover" => "SILmouseoverRS", 
			"onMouseout" => "SILhideOverlay", 
		),
		// GHS
		array("item" => "cell", "class" => "noprint", "skip" => $hide_safety || !$use_ghs, ),
		array(
			"item" => ($hide_safety || !$use_ghs?"hidden":"input"), 
			"int_name" => "safety_h", 
			"text" => s("rc_safety_h"), 
			"size" => 4, 
			"class" => "small_input", 
			"onMouseover" => "SILmouseoverRS", 
			"onMouseout" => "SILhideOverlay", 
		),
		array("item" => "text", "value" => "<br/>", "skip" => $hide_safety || !$use_ghs, ), 
		array(
			"item" => ($hide_safety || !$use_ghs?"hidden":"input"), 
			"int_name" => "safety_p", 
			"text" => s("rc_safety_p"), 
			"size" => 4, 
			"class" => "small_input", 
			"onMouseover" => "SILmouseoverRS", 
			"onMouseout" => "SILhideOverlay", 
		),
		
		// SDB-Button über JS
		array("item" => "cell", "class" => "noprint", "skip" => $hide_safety, ),
		array("item" => "js", "int_name" => "safety_buttons", "functionBody" => "getSafetyButtons(values); ", ), 
		
		array("item" => "cell", "hideReadOnly" => true, "class" => "noprint"),
		array("item" => "links", "style" => "Vsm"), 
	);
}

function getSubitemlists($paramHash=array()) {
	global $g_settings,$settings,$selectTables;
	
	$use_rs=$g_settings["use_rs"]??false;
	$use_ghs=$g_settings["use_ghs"]??false;
	$hide_safety=$settings["hide_safety"]??false;
	
	$reactantsFields=array(
		array("item" => "cell"),
		array("item" => "line_number", "int_name" => "nr_in_reaction", "useLetter" => true)
	);
	
	$reagentsFields=array(
		array("item" => "cell"),
		array("item" => "text", "headline" => "", "value" => "R"),
		array("item" => "line_number", "int_name" => "nr_in_reaction")
	);
	
	$reaParamHash=array(
		"item" => "subitemlist", 
		"allowReorder" => true, 
		"showAlways" => true, 
		"buttonstyle" => "Sm", 
		// Einstellung Einheiten übernehmen
		"lineInitFunction" => 
			'updateTh("reactants"); '. // ersetzen durch zeilenweises update FIXME
			'updateRcUID(); '.
			'updateSelectInfos(list_int_name,UID,values,""); ',

		"setFunction" => 
			'updateSelectInfos(list_int_name,UID,values,""); '.
			'var measured=a(values,"measured"); '.
			'switch (measured) { '.
			'case "3": '. // amount
				'highlightObj(list_int_name,UID,["rc_amount","rc_amount_unit"]); '.
			'break; '.
			'case "2": '. // volume
				'highlightObj(list_int_name,UID,["volume","volume_unit"]); '.
			'break; '.
			'case "1": '. // mass
				'highlightObj(list_int_name,UID,["m_brutto","mass_unit"]); '.
			'break; '.
			'} '
	);
	
	$reacParamHash=array_merge($paramHash,array("list_int_name" => "reactants", ));
	$reagParamHash=array_merge($paramHash,array("list_int_name" => "reagents", ));
	
	$subitemlists["reactants"]=array_merge($reaParamHash, array("int_name" => "reactants", 
		"addText" => s("add_reactant"),
		// bei Änderungen Reaktionsgleichung updaten
		"onListReordered" => "updateRxnOnly();", 
		"lineDelFunction" => "updateRxnOnly();", 
		"fields" => array_merge($reactantsFields,getReaFields($reacParamHash)) 
	)); // subStart
	
	$subitemlists["reagents"]=array_merge($reaParamHash, array(
		SPLITMODE => true, 
		"int_name" => "reagents", 
		"addText" => s("add_reagent"), 
		"onListReordered" =>"updateRcUID();", 
		"lineDelFunction" => "updateRcUID();",
		"fields" => array_merge($reagentsFields,getReaFields($reagParamHash)) 
	)); // subEnd
	
	$subitemlists["products"]=array(
		"item" => "subitemlist", 
		"int_name" => "products", 
		"allowReorder" => true, 
		"addText" => s("add_product"), 
		"buttonstyle" => "Sm", 

		// Einstellung Einheiten übernehmen
		"lineInitFunction" => 
			'updateTh("products");'. // ersetzen durch zeilenweises update FIXME
			'updateRcUID(); '.
			'updateSelectInfos(list_int_name,UID,values,""); ',
		
		// bei Änderungen Reaktionsgleichung updaten
		"onListReordered" => "updateRxnOnly();", 
		"lineDelFunction" => "updateRxnOnly();", 
		"setFunction" => 
			'updateSelectInfos(list_int_name,UID,values,""); ',

		"fields" => array(
			array("item" => "cell"),
			array("item" => "hidden", "int_name" => "reaction_chemical_id"),
			array("item" => "line_number", "int_name" => "nr_in_reaction"),
			array("item" => "cell"), 
			array(
				"item" => "input", 
				"int_name" => "rc_amount", 
				"text" => s("rc_amount_th"),
				"size" => 5, 
				"onChange" => "rxnProductChanged", 
				"class" => "small_input", 
				"doEval" => true, 
				"type" => "round", 
				"roundMode" => $paramHash["roundMode"], 
				"decimals" => $paramHash["decimals"], 
			),
			
			array("item" => "text", "freemodeHeadline" => "products_rc_amount_unit", "headline" => " <nobr>["), 
			array("item" => "text", "headline" => "]</nobr>", "value" => " "), 
			
			array(
				"item" => "pk_select", 
				"int_name" => "rc_amount_unit", 
				"pkName" => "unit_name", 
				"dbs" => "-1", 
				"table" => "units", 
				"nameField" => "unit_name", 
				"filterDisabled" => true, 
				"filter" => "unit_type LIKE BINARY \"n\"", 
				"setValues" => 
	'return a(selected_values,"unit_name");',
				"onChange" => "rxnValueChanged", 
				"class" => "small_input", 
			),
			
			array("item" => "cell", ), 
			array(
				"item" => "input", 
				"int_name" => "stoch_coeff", 
				"size" => 1, 
				"class" => "small_input", 
				"doEval" => true, 
				"onChange" => "stochCoeffChanged", 
				"type" => "round", 
				"roundMode" => $paramHash["roundMode"], 
				"decimals" => $paramHash["decimals"], 
			), 
			array("item" => "cell", "class" => "noprint"), 
			array(
				"item" => "structure", 
				"int_name" => "molfile_blob", 
				"pkName" => "reaction_chemical_id", 
				"showEditButton" => true, 
				"showDelButton" => false, 
				"showGifButton" => false, 
				"showMolfileButton" => false, 
				"showCopyPasteButton" => true, 
				"onChange" => "molChanged", 
				"height" => rc_gif_y, 
				"width" => rc_gif_x, 
				VISIBLE => true, 
			), 
			
			array("item" => "cell"), 
			array("item" => "select", "int_name" => "molecule_id", "onChange" => "molSelectChanged", "class" => "small_input"),
			array("item" => "hidden", "int_name" => "other_db_id"),
			array("item" => "span", "int_name" => "info1"),
			array("item" => "text", "value" => "<br/>"), 
			array("item" => "button", "onClick" => "searchMolecule", "img" => "lib/molecule_sm.png", "hideReadOnly" => true, "class" => "button_very_small", ), // Suchknopf für Molekül
			array("item" => "button", "onClick" => "editRc", "img" => "lib/edit_rc_sm.png", "hideReadOnly" => true, "class" => "button_very_small", ), // manuell eintragen
			
			array("item" => "hidden", "int_name" => "standard_name"), 
			array("item" => "hidden", "int_name" => "chemical_storage_id"), 
			array("item" => "hidden", "int_name" => "package_name"), 
			array("item" => "hidden", "int_name" => "colour"), 
			array("item" => "hidden", "int_name" => "consistency"), 
			array("item" => "hidden", "int_name" => "description"), 
			array("item" => "hidden", "int_name" => "chemical_storage_barcode"),
			//~ array("item" => "hidden", "int_name" => "cas_nr"), 
			array("item" => "hidden", "int_name" => "smiles"), 
			array("item" => "hidden", "int_name" => "smiles_stereo"), 
			array("item" => "cell"), 
			array(
				"item" => "input", 
				"int_name" => "emp_formula", 
				"text" => s("rc_emp_formula"), 
				"postProc" => "emp_formula", 
				"size" => 9, 
				"class" => "small_input", 
				"onChange" => "rcEmpFormulaChanged", 
				"handleDisplay" => "return getBeautySum(displayValue);", 
				"onMouseover" => "SILmouseoverCHN", 
				"onMouseout" => "SILhideOverlay", 
			),
			array("item" => "text", "value" => "<br/>"), 
			array("item" => "input", "int_name" => "cas_nr", "size" => 9, ),
			array("item" => "cell"), 
			array("item" => "input", "int_name" => "mw", "size" => 5, "onChange" => "rxnProductChanged", "class" => "small_input", "type" => "round", "decimals" => 2),
			array("item" => "cell"), 
			array("item" => "input", "int_name" => "gc_yield", "size" => 5, "class" => "small_input", "type" => "round", "decimals" => yield_digits, "roundMode" => yield_mode, ),
			array("item" => "cell"), 
			array("item" => "input", "int_name" => "m_brutto", "size" => 5, "onChange" => "rxnProductChanged", "class" => "small_input", "doEval" => true, "type" => "round", "roundMode" => $paramHash["roundMode"], "decimals" => $paramHash["decimals"], ),
			
			array("item" => "text", "freemodeHeadline" => "products_mass_unit", "headline" => " <nobr>["), 
			array("item" => "text", "headline" => "]</nobr>", "value" => " "), 
			
			array(
				"item" => "pk_select", 
				"int_name" => "mass_unit", 
				"pkName" => "unit_name", 
				"dbs" => "-1", 
				"table" => "units", 
				"nameField" => "unit_name", 
				"filterDisabled" => true, 
				"filter" => "unit_type LIKE BINARY \"m\"", 
				"setValues" => 'return a(selected_values,"unit_name");',
				"onChange" => "rxnValueChanged", 
				"class" => "small_input", 
			),

			array("item" => "cell"), 
			
			array("item" => "input", "int_name" => "rc_conc", "text" => "%", "size" => 3, "onChange" => "rxnProductChanged", "class" => "small_input"),
			array("item" => "hidden", "int_name" => "rc_conc_unit", "defaultValue" =>"%", ),
			array("item" => "cell"), 
			array(
				"item" => "input", 
				"int_name" => "yield", 
				DEFAULTREADONLY => "always", 
				"handleDisplay" => "return getYieldValue(list_int_name,UID,displayValue);", 
			), 
			
			array("item" => "cell", "skip" => $hide_safety, ), 
			array(
				"item" => ($hide_safety || !$use_rs?"hidden":"input"), 
				"int_name" => "safety_sym", 
				"text" => s("rc_safety_sym"), 
				"size" => 1, 
				"class" => "small_input", 
				"handleDisplay" => "return getSymbols(\"rs\",displayValue,31,31);", 
			), 
			array(
				"item" => ($hide_safety || !$use_ghs?"hidden":"input"), 
				"int_name" => "safety_sym_ghs", 
				"text" => s("rc_safety_sym_ghs"), 
				"size" => 1, 
				"class" => "small_input", 
				"handleDisplay" => "return getSymbols(\"ghs\",displayValue,31,31);", 
			), 
			// RS
			array("item" => "cell", "class" => "noprint", "skip" => $hide_safety || !$use_rs, ),
			array(
				"item" => ($hide_safety || !$use_rs?"hidden":"input"), 
				"int_name" => "safety_r", 
				"text" => s("rc_safety_r"), 
				"size" => 4, 
				"class" => "small_input", 
				"onMouseover" => "SILmouseoverRS", 
				"onMouseout" => "SILhideOverlay", 
			), 
			array("item" => "text", "value" => "<br/>", "skip" => $hide_safety || !$use_rs, ), 
			array(
				"item" => ($hide_safety || !$use_rs?"hidden":"input"), 
				"int_name" => "safety_s", 
				"text" => s("rc_safety_s"), 
				"size" => 4, 
				"class" => "small_input", 
				"onMouseover" => "SILmouseoverRS", 
				"onMouseout" => "SILhideOverlay", 
			), 
			// GHS
			array("item" => "cell", "class" => "noprint", "skip" => $hide_safety || !$use_ghs, ),
			array(
				"item" => ($hide_safety || !$use_ghs?"hidden":"input"), 
				"int_name" => "safety_h", 
				"text" => s("rc_safety_h"), 
				"size" => 4, 
				"class" => "small_input", 
				"onMouseover" => "SILmouseoverRS", 
				"onMouseout" => "SILhideOverlay", 
			), 
			array("item" => "text", "value" => "<br/>", "skip" => $hide_safety || !$use_ghs, ), 
			array(
				"item" => ($hide_safety || !$use_ghs?"hidden":"input"), 
				"int_name" => "safety_h", 
				"text" => s("rc_safety_p"), 
				"size" => 4, 
				"class" => "small_input", 
				"onMouseover" => "SILmouseoverRS", 
				"onMouseout" => "SILhideOverlay", 
			), 
			
			array("item" => "cell", "class" => "noprint", ),
			// SDB-Button über JS
			array("item" => "button", "onClick" => "transferThisEntryToUID", "class" => "imgButtonSm", "img" => "lib/select_sm.png", "int_name" => "do_select", "skip" => !in_array("reaction_chemical",$selectTables), ), 
			
			array("item" => "text", "value" => "<table><tbody><tr><td>", ), 
			array("item" => "js", "int_name" => "safety_buttons", "functionBody" => "getSafetyButtons(values); ", ), 
			array("item" => "text", "value" => "</td><td>", ), 
			array("item" => "button", "onClick" => "void getSubstanceReport", "int_name" => "substance_report", "class" => "imgButtonSm", "img" => "lib/details_sm.png"),
			array("item" => "text", "value" => "</td><td rowspan=\"2\">", ), 
			array("item" => "links", "style" => "Vsm"),
			array("item" => "text", "value" => "</td></tr><tr><td>", ), 
			array("item" => "button", "onClick" => "void getCHNForm", "int_name" => "analysenzettel", "class" => "imgButtonSm", "img" => "lib/chn_sm.png"),
			array("item" => "text", "value" => "</td><td>", ), 
			array("item" => "button", "onClick" => "void prepareMakeChemicalStorage", "int_name" => "rc_to_chemical_storage", "class" => "imgButtonSm", "img" => "lib/rc_to_storage_sm.png"),
			array("item" => "text", "value" => "</td></tr></tbody></table>", ), 
		)
	); 

	$subitemlists["analytical_data"]=getAnalyticalDataParamHash("reaction");
	
	return $subitemlists;
}

?>