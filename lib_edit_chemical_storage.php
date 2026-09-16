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
function showChemicalStorageEditForm($paramHash) {
	global $defaultCurrency,$price_currency_list,$editMode,$g_settings,$settings,$person_id,$permissions;
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"chemical_storage");
	
	$paramHash["roundMode"]=getRoundMode($settings["lj_round_type"]);
	$paramHash["decimals"]=getDecimals($settings["digits_count"]);
	
	if ($paramHash["barcodeTerminal"]) {
		$defaultReadOnly="always"; // , DEFAULTREADONLY => $defaultReadOnly
		$bigClass="barcodeBig"; //  ,"class" => $bigClass
		$paramHash["setControlValues"]=
			'tmd_unit_changed(); '; // update total_mass_unit and total_mass
	}
	
	if ($editMode) {
		$paramHash["setControlValues"]=
			'var db_id=a(values,"db_id"); '.
			'if (a(values,"total_mass")==="") {'.
				'tmd_unit_changed(); '. // update total_mass_unit and total_mass
			'}'.
			'if (db_id) { '.
				'var borrowed_by_db_id=a(values,"borrowed_by_db_id"),borrowed_by_person_id=a(values,"borrowed_by_person_id"),disabled=a(values,"chemical_storage_disabled");'
				.'var own_db=(db_id=="-1"),not_borrowed=(!borrowed_by_person_id),self_borrowed=(borrowed_by_person_id=='.fixNull($person_id).' && borrowed_by_db_id==-1); '
				.(($permissions & _chemical_edit)==0 && ($permissions & _chemical_edit_own) ? 'visibleObj("btn_edit",a(values,"owner_person_id")=='.fixNull($person_id).'); ':'')
				.'visibleObj("btn_del",own_db && !disabled); '
				.'visibleObj("btn_undelete",own_db && disabled); '
				.'visibleObj("btn_borrow",not_borrowed && own_db); '
				.'visibleObj("btn_return",self_borrowed && own_db); '
				.'showControl("storage_id",own_db); '
				.'showControl("compartment",own_db); '
				.'showControl("chemical_storage_secret",own_db); '
				.'var personName=formatPerson(values); '
				.'setControl("borrowed_by",{"borrowed_by":personName}); '
				.'showControl("borrowed_by",personName!=""); '
				.'showControl("show_db_beauty_name",!own_db); '.
			'} '.
			'if (readOnly==true) { '.
				'setTitle(strcut(a(values,"molecule_name"),30)); '.
			'} ';
		
	}

	$paramHash["checkSubmit"]=
		'if (getControlValue("amount")=="" && getControlValue("actual_amount")=="") { '.
			'alert('.fixStr(s("error_amount")).');'.
			'focusInput("amount"); '.
			'return false;'.
		'} ';
	if ($g_settings["full_logging"]) {
		$paramHash["checkSubmit"].=
			'if (trim(getControlValue("reason"))=="" && parseFloat(getControlValue("actual_amount"))<getCacheValue("actual_amount")) {'.
				'var reason=prompt('.fixStr(s("reason_for_withdrawal")).',"");'.
				'if (reason==null || trim(reason)=="") {'.
					'return false;'.
				'}'.
				'else {'.
					'setControlValue("reason",reason);'.
				'}'.
			'}';
	}
	//~ $paramHash["change"][READONLY]=
		//~ 'showControl("actual_amount_fixed",!thisValue);';
	
	if ($g_settings["force_poison_cabinet"]) {
		$paramHash["change"][READONLY].=
			'if (thisValue==false) { '. // aktiven status rot setzen, vorherige ausblenden, nachfolgende anzeigen
				'PkSelectUpdate("storage_id"); '.
			'} ';
	}
	if ($g_settings["full_logging"]) {
		$paramHash["change"][READONLY].='setControlValue("reason","");'.
			'showControl("reason",false);';
	}

	$retval.=getFormElements($paramHash,array(
		array("item" => "input", "int_name" => "add_multiple", "text" => s("add_multiple1"), "size" => 1,"maxlength" => 3, "defaultValue" => "1", "skip" => ($editMode || $paramHash["barcodeTerminal"]), ), 
		array("item" => "text", "text" => s("add_multiple2"), "skip" => ($editMode || $paramHash["barcodeTerminal"]), ), 
		array("item" => "br", "skip" => ($editMode || $paramHash["barcodeTerminal"]), ), 

		array("item" => "hidden", "int_name" => "action_molecule", "skip" => $paramHash["barcodeTerminal"], ), 
		array("item" => "hidden", "int_name" => "order_uid", ), 
		array("item" => "text", "text" => "<input type=\"submit\" id=\"updateInventory\" value=".fixStr(s("have_checked_it"))."> <input type=\"button\" id=\"btn_del\" onClick=\"delChemicalStorage(); \" value=".fixStr(s("delete"))."> <input type=\"button\" id=\"btn_create\" onClick=\"saveDataset();\" value=".fixStr(s("add_dataset"))." style=\"display:none\"><br>", "skip" => !$paramHash["barcodeTerminal"]), 
		array(
			"item" => "pk", 
			"text" => "", 
			"class" => "formTitle", 
			DEFAULTREADONLY => $defaultReadOnly, 
			"int_name" => "molecule_id", 
			"table" => "molecule", 
			"setNoneText" => s("new_molecule"), 
			"allowNone" => true, 
			"skip" => $paramHash["new_molecule"], // , TABLEMODE => false
			
			"setValues" => 
				'var newMol=(a(selected_values,"molecule_id")==""),otherDb=(a(selected_values,"db_id")!="-1"); '
				.'if (otherDb) { '
					.'selected_values["molecule_id"]="";  '
				.'} '
				.'if (init==true) {'
					.'if (newMol || otherDb) { ' // Möglichkeit für Änderungen
						.'readOnlyForm("molecule",false); '
					.'}'
					.'else { ' // nur PK
						.'readOnlyForm("molecule",true); '
					.'}'
					.'delete selected_values["db_id"]; '
					.'resetAlreadyLoaded(); '
					.'setControlValues(selected_values,false); '
				.'} '
				.'if (newMol) { '
					.'return '.fixStr(s("new_molecule")).'; '
				.'} '
				.'return a(selected_values,"molecule_name");', 
		),

		array("item" => "tableStart", TABLEMODE => "hl", ), 
		array(
			"item" => "input", 
			"int_name" => "chemical_storage_conc", 
			"size" => 5, 
			// DEFAULTREADONLY => $defaultReadOnly, 
			"doEval" => true, 
		), 
		array(
			"item" => "pk_select", 
			SPLITMODE => true, 
			"int_name" => "chemical_storage_conc_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filterDisabled" => true, 
			"filter" => "unit_type COLLATE utf8_bin IN(\"m/m\",\"c\",\"molal\")", 
			"setValues" => 
				'return a(selected_values,"unit_name"); ', 
			"defValue" => "mol/l", 
			// DEFAULTREADONLY => $defaultReadOnly, 
		), 

		array("item" => "input", "int_name" => "chemical_storage_solvent", "size" => 10, DEFAULTREADONLY => $defaultReadOnly, ), 
		array("item" => "input", "int_name" => "chemical_storage_density_20", "size" => 10, DEFAULTREADONLY => $defaultReadOnly, "doEval" => true, "roundMode" => $paramHash["roundMode"], "decimals" => $paramHash["decimals"], ), 
		"tableEnd", 
		array("item" => "text", "text" => "<table class=\"noborder\"><tr><td style=\"vertical-align:top\">"), 
		"tableStart", 

		array(
			"item" => "input", 
			"int_name" => "amount", 
			"size" => 5, 
			"type" => "round", 
			"roundMode" => $paramHash["roundMode"], 
			"decimals" => $paramHash["decimals"], 
			"doEval" => true, 
			"noAutoComp" => true, 
			"class" => $bigClass, 
			"onChange" => "amount_changed(); ", // update calc
		), 

		array(
			"item" => "pk_select", 
			SPLITMODE => true, 
			"int_name" => "amount_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filterDisabled" => true, 
			"filter" => "unit_type IN(\"m\",\"v\")", 
			"class" => $bigClass, 
			
			"setValues" => 
			'return a(selected_values,"unit_name");', 
			
			"onChange" => "actual_amount_changed(this); ", // update calc, unit is also used for actual_amount
			//~ DEFAULTREADONLY => $defaultReadOnly, 
		), 

		array(
			"item" => "input", 
			"int_name" => "actual_amount", 
			"size" => 5, 
			"type" => "round", 
			"roundMode" => $paramHash["roundMode"], 
			"decimals" => $paramHash["decimals"], 
			"doEval" => true, 
			"noAutoComp" => true, 
			"class" => $bigClass, 
			"onFocus" => "actual_amount_focus();",
			"onChange" => "actual_amount_changed(this); ", // if no value is entered, the value is set equal to amount, otherwise the value is left untouched
		), 
		array("item" => "text", "rw" => "<input type=\"radio\" name=\"mass_fixed\" value=\"act\" style=\"float:right\"/>", SPLITMODE => true, ),
		
		// reason for withdrawal, only for full_logging
		array("item" => "input", "int_name" => "reason", "size" => 10, DEFAULTREADONLY => $defaultReadOnly, VISIBLE => false, "skip" => (!$editMode || !$g_settings["full_logging"]), ), 
		
		array(
			"item" => "input", 
			"int_name" => "tmd", 
			"size" => 5, 
			"type" => "round", 
			"roundMode" => $paramHash["roundMode"], 
			"decimals" => $paramHash["decimals"], 
			"doEval" => true, 
			"noAutoComp" => true, 
			"class" => $bigClass, 
			"onFocus" => "tmd_focus();",
			"onChange" => "tmd_changed(); ", // update calc
		), 

		array(
			"item" => "pk_select", 
			SPLITMODE => true, 
			"int_name" => "tmd_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filterDisabled" => true, 
			"filter" => "unit_type LIKE BINARY \"m\" AND unit_factor>0.1", 
			"defValue" => "g", 
			"setValues" => 
				'return a(selected_values,"unit_name");', 
			"class" => $bigClass, 
			"onChange" => "tmd_unit_changed(); ", // update calc, sync with total_mass_unit
		), 
		array("item" => "text", "rw" => "<input type=\"radio\" name=\"mass_fixed\" value=\"tmd\" style=\"float:right\"/>", SPLITMODE => true, ),
		
		array(
			"item" => "input", 
			"int_name" => "total_mass", 
			"size" => 5, 
			"type" => "round", 
			"roundMode" => $paramHash["roundMode"], 
			"decimals" => $paramHash["decimals"], 
			"doEval" => true, 
			"noAutoComp" => true, 
			"class" => $bigClass, 
			"loadBlind" => true, 
			"onFocus" => "total_mass_focus();",
			"onChange" => "total_mass_changed();", // update calc
		), 

		array(
			"item" => "pk_select", 
			SPLITMODE => true, 
			"int_name" => "total_mass_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filterDisabled" => true, 
			"filter" => "unit_type LIKE BINARY \"m\" AND unit_factor>0.1", 
			"defValue" => "g", 
			"class" => $bigClass, 
			"setValues" => 
				'return a(selected_values,"unit_name");', 
			"loadBlind" => true, 
			"onChange" => "total_mass_unit_changed(); ", // update calc, sync with tmd_unit
		), 
		array("item" => "text", "rw" => "<input type=\"radio\" name=\"mass_fixed\" value=\"total\" style=\"float:right\"/>", SPLITMODE => true, ),
		
		getTriSelectForm(array(
			"int_name" => "chemical_storage_bilancing", 
			"class" => $bigClass, 
			"skip" => !$g_settings["general_bilancing"], 
		)), 
		array("item" => "input", "int_name" => "container", "size" => 10, DEFAULTREADONLY => $defaultReadOnly), 
		array("item" => "input", "int_name" => "description", "size" => 20, DEFAULTREADONLY => $defaultReadOnly), // like "on BaSO4"
		array("item" => "input", "int_name" => "protection_gas", "size" => 10, DEFAULTREADONLY => $defaultReadOnly), 
		array("item" => "input", "int_name" => "order_date", "size" => 10, "type" => "date", "noAutoComp" => true, DEFAULTREADONLY => $defaultReadOnly), 
		array("item" => "input", "int_name" => "open_date", "size" => 10, "type" => "date", "noAutoComp" => true, DEFAULTREADONLY => $defaultReadOnly), 
		array("item" => "input", "int_name" => "expiry_date", "size" => 10, "type" => "date", DEFAULTREADONLY => $defaultReadOnly), 
		array("item" => "input", "int_name" => "lot_no", "size" => 10, DEFAULTREADONLY => $defaultReadOnly), 
		array(
			"item" => "pk_select", 
			"int_name" => "chemical_storage_type", 
			"table" => "chemical_storage_type", 
			"dbs" => -1, 
			"pkName" => "chemical_storage_type_id", 
			"nameField" => "chemical_storage_type_name", 
			"separator" => "; ", 
			"multiMode" => true, 
			"size" => 3, 
			"maxTextLen" => 40, 
		),
		array("item" => "input", "int_name" => "supplier", "size" => 10, DEFAULTREADONLY => $defaultReadOnly), 
		array("item" => "input", "int_name" => "cat_no", "size" => 10, DEFAULTREADONLY => $defaultReadOnly), 
		array("item" => "input", "int_name" => "price", "size" => 10, "noAutoComp" => true, DEFAULTREADONLY => $defaultReadOnly), 
		array("item" => "select", SPLITMODE => true, "int_name" => "price_currency", "int_names" => $price_currency_list, "texts" => $price_currency_list, "defVal" => $defaultCurrency, DEFAULTREADONLY => $defaultReadOnly), 

		array("item" => "sds", "int_name" => "safety_sheet", "pkName" => "chemical_storage_id", "skip" => $paramHash["new_molecule"], DEFAULTREADONLY => $defaultReadOnly), 
		array("item" => "sds", "int_name" => "alt_safety_sheet", "pkName" => "chemical_storage_id", "skip" => $paramHash["new_molecule"], DEFAULTREADONLY => $defaultReadOnly), 

		array("item" => "input", "int_name" => "chemical_storage_btm_list", "size" => 2,"maxlength" => 1, DEFAULTREADONLY => $defaultReadOnly), 
		array("item" => "input", "int_name" => "chemical_storage_sprengg_list", "size" => 2,"maxlength" => 2, DEFAULTREADONLY => $defaultReadOnly), 

		"tableEnd", 
		array("item" => "text", "text" => "</td><td style=\"vertical-align:top\">"), 
		"tableStart", 

		// array("item" => "input", "int_name" => "show_db_beauty_name", DEFAULTREADONLY => "always"), 
		array(
			"item" => "pk_select", 
			"text" => s("in_storage"), 
			"int_name" => "storage_id", 
			"dbs" => "-1", 
			"table" => "storage", 
			"nameField" => "storage_name", 
			"allowNone" => true, 
			"noneText" => s("not_set"), 
			"setNoneText" => s("none"), 
			"class" => $bigClass, 
			"setValues" => 'return a(selected_values,"storage_name");', 
			"dynamic" => $g_settings["force_poison_cabinet"], // show only poison cabinets for toxic if enabled
			"getFilter" => 
				'if (isPoison()) { '.
					'return "query=<0>&crit0=poison_cabinet&op0=eq&val0=1"; '.
				'} '.
				'return  "query="; ',
			"getText" => 'return rowData["storage_name"];', 
		), 
		array("item" => "input", "int_name" => "compartment", "size" => 10,"maxlength" => 20, "clearbutton" => true, "class" => $bigClass), 
		array(
			"item" => "pk_select", 
			"table" => "person", 
			"int_name" => "owner_person_id", 
			"dbs" => -1,
			"pkName" => "person_id", 
			"allowNone" => true, 
			"filterDisabled" => true, 
			DEFAULTREADONLY => (($permissions & _chemical_edit)==0 && ($permissions & _chemical_edit_own))?"always":"", 
		), 


		array("item" => "check", "int_name" => "chemical_storage_secret", DEFAULTREADONLY => $defaultReadOnly), 

		array("item" => "input", "int_name" => "borrowed_by", DEFAULTREADONLY => "always"), 

		array(
			"item" => "pk_select", 
			"int_name" => "transferred_to_db_id", 
			"pkName" => "other_db_id", 
			"nameField" => "db_beauty_name", 
			"table" => "other_db", 
			"order_obj" => getUserDefOrderObj("other_db"), 
			"filterDisabled" => true, 
			"allowNone" => true, 
			"noneText" => "-", 
			"clearbutton" => true, // none is better as value is ""
			"skipOwn" => true, 
			"skip" => !$g_settings["global_barcodes"], 
		),

		array(
			"item" => "checkset", 
			"int_name" => "chemical_storage_attrib", 
			"int_names" => getValueList("chemical_storage","chemical_storage_attrib"), 
			DEFAULTREADONLY => $defaultReadOnly, 
			"roList" => true, 
			"breakAfter" => 2, 
		), 

		array(
			"item" => "input", 
			"int_name" => "chemical_storage_barcode", 
			"size" => 10, 
			"maxlength" => 20, 
			//~ DEFAULTREADONLY => $defaultReadOnly, 
			"class" => $bigClass, 
		), 
		array("item" => "js", "int_name" => "generated_barcode","loadBlind" => true, "functionBody" => "getEANWithPrefix(".findBarcodePrefixForPk("chemical_storage").",values[\"chemical_storage_id\"],8)", "skip" => !$editMode), 
		
		array("item" => "input", "int_name" => "comment_cheminstor", "type" => "textarea", "cols" => 40, "rows" => 4, DEFAULTREADONLY => $defaultReadOnly), 
		array("item" => "input", "int_name" => "migrate_id_cheminstor", "text" => $g_settings["name_migrate_id_cheminstor"], "size" => 10, DEFAULTREADONLY => $defaultReadOnly, "skip" => empty($g_settings["name_migrate_id_cheminstor"]), ), 

		// gehe zu reaktion
		array("item" => "js", "int_name" => "lab_journal_id", "functionBody" => "getGotoReactionLink(values[\"lab_journal_id\"],values[\"from_reaction_id\"])"), 
		array("item" => "hidden", "int_name" => "from_reaction_id"), 

		array("item" => "input", "int_name" => "history_entry", "loadBlind" => true, "size" => 35, ), 
		"tableEnd", 
		array("item" => "text", "text" => "</td></tr><tr><td colspan=\"2\">"), 
		array(
			"item" => "input", 
			"int_name" => "history", 
			"type" => "textarea_classic", 
			DEFAULTREADONLY => "always", 
			"onMouseover" => "editHistory", 
			"onMouseout" => "editHideOverlay", 
			"handleDisplay" => 
				'return "<span class=\"print_only\">"+displayValue+"</span><span class=\"noprint\">"+strrcut(displayValue,200,undefined,"<br>")+"</span>";', 
		), 
		"br", 
		array("item" => "input", "int_name" => "chemical_storage_created_by", DEFAULTREADONLY => "always"), 
		array("item" => "input", "int_name" => "chemical_storage_created_when", DEFAULTREADONLY => "always", "type" => "date"), 
		"br", 
		array("item" => "input", "int_name" => "chemical_storage_changed_by", DEFAULTREADONLY => "always"),
		array("item" => "input", "int_name" => "chemical_storage_changed_when", DEFAULTREADONLY => "always", "type" => "date"), 
		"br", 
		array("item" => "input", "int_name" => "inventory_check_by", DEFAULTREADONLY => "always"),
		array("item" => "input", "int_name" => "inventory_check_when", DEFAULTREADONLY => "always", "type" => "date"), 
		"br", 
		array("item" => "input", "int_name" => "disposed_by", DEFAULTREADONLY => "always"),
		array("item" => "input", "int_name" => "disposed_when", DEFAULTREADONLY => "always", "type" => "date"), 

		array("item" => "text", "text" => "</td></tr></table>") 
	));
	return $retval;
}
?>
