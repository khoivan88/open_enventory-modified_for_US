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
function showPersonEditForm($paramHash) { // ergänzen: Kostenstelle, Kontonummer[, Kostenlimit, Hostname]
	global $permissions_list_value,$person_id,$editMode,$permissions_groups;
	$paramHash["int_name"]="person";
	
	$pre_perm_int_names=array_values($permissions_list_value);
	$pre_perm_texts=array_keys($permissions_list_value);
	$pre_perm_int_names[]="";
	$pre_perm_texts[]="user_def";
	for ($a=0;$a<count($pre_perm_texts);$a++) {
		$pre_perm_texts[$a]=s($pre_perm_texts[$a]);
	}
	
	$perm_settings=getGVar("perm_settings");
	if (is_array($perm_settings)) foreach ($perm_settings as $permission_level_name => $permission_level) {
		$pre_perm_int_names[]=$permission_level;
		$pre_perm_texts[]=$permission_level_name;
	}
	$perm_texts=s("permissions_list");
	
	$paramHash["setControlValues"]=
		'if (readOnly==true) { '.
			'setTitle(strcut(formatPersonNameNatural(values),30)); '.
		'} ';
	
	if ($editMode) {
		$paramHash["change"][READONLY]=
			'var db_id=dbIdx[actIdx]["db_id"],pk=dbIdx[actIdx]["pk"]; '.
			'var is_self_rw=!thisValue && (a(dataCache,db_id,pk,"person_id")=='.fixStr($person_id).'); '.
			'showControl("person_disabled",!is_self_rw); '.
			'showControl("predefined_permissions",!is_self_rw); '.
			'showControl("permissions_general",!is_self_rw); '.
			'showControl("permissions_chemical",!is_self_rw); '.
			'showControl("permissions_lab_journal",!is_self_rw); '.
			'showControl("permissions_order",!is_self_rw); '.
			'showControl("permissions_info",is_self_rw); '.
			'if (!thisValue) { '.
				'updatePermissions(); '.
			'} ';
		
	}
	else {
		$paramHash["onLoad"]='updatePermissions(); ';
	}
	
	$paramHash["checkSubmit"].=
		'var username=getControlValue("username"),pattern=/^\w{1,16}$/; '.
		'if (username=="") { '
			.'alert("'.s("error_user").'");'
			.'focusInput("username"); '
			.'return false;'
		.'} '.
		'if (username=='.fixStr(ROOT).') { '
			.'alert("'.s("error_root").'");'
			.'focusInput("username"); '
			.'return false;'
		.'} '.
		'if (username.length>16) { '
			.'alert("'.s("error_long_user").'");'
			.'focusInput("username"); '
			.'return false;'
		.'} '.
		'if (!pattern.test(username)) { '
			.'alert("'.s("error_invalid_user").'");'
			.'focusInput("username"); '
			.'return false;'
		.'} '
		.'var new_password=getControlValue("new_password"); '
		.'return checkPass(getControlValue("new_password"),username,"new_password",'.($editMode?"true":"false").');';
	
	$retval=getFormElements($paramHash,array(
		"tableStart", 
		array("item" => "check", "int_name" => "person_disabled"),
		array("item" => "input", "int_name" => "username", "size" => 16,"maxlength" => 16), 
		array("item" => "input", "int_name" => "new_password","size" => 20,"maxlength" => 50,"type" => "password"), 

		array("item" => "text", "int_name" => "permissions_info", "skip" => !$editMode), 
		array("item" => "language", "int_name" => "preferred_language", "allowDefault" => false), 

		array("item" => "input", "int_name" => "title", "size" => 5,"maxlength" => 20, "showAlways" => true, "text" => s("title")." ".s("first_name")." ".s("last_name") ), 
		array("item" => "text", SPLITMODE => true, "text" => " "), 
		array("item" => "input", SPLITMODE => true, "int_name" => "first_name","size" => 20,"maxlength" => 50), 
		array("item" => "text", SPLITMODE => true, "text" => " "), 
		array("item" => "input", SPLITMODE => true, "int_name" => "last_name", "size" => 20,"maxlength" => 50), 
		array("item" => "input", "int_name" => "nee", "size" => 20,"maxlength" => 50), 
		array("item" => "input", "int_name" => "sigle", "size" => 3, ), 
		array("item" => "input", "int_name" => "person_barcode", "size" => 20, ), 
		
		// clean this
		array("item" => "select", "int_name" => "predefined_permissions", "loadBlind" => true, "int_names" => $pre_perm_int_names, "texts" => $pre_perm_texts, "onChange" => "updatePermissions();", "getValue" => "return (in_array(values[\"permissions\"],".json_encode($pre_perm_int_names).")?values[\"permissions\"]:\"\");", ), 
		array("item" => "checkset", "int_name" => "permissions_general", "loadBlind" => true, "int_names" => getIntNames($permissions_groups,0), "texts" => getTexts($permissions_groups,$perm_texts,0), "shift" => cumSum($permissions_groups,0), "breakAfter" => 2, "getValue" => "return values[\"permissions\"];", ),
		array("item" => "checkset", "int_name" => "permissions_chemical", "loadBlind" => true, "int_names" => getIntNames($permissions_groups,1), "texts" => getTexts($permissions_groups,$perm_texts,1), "shift" => cumSum($permissions_groups,1), "breakAfter" => 2, "getValue" => "return values[\"permissions\"];", ),
		array("item" => "checkset", "int_name" => "permissions_lab_journal", "loadBlind" => true, "int_names" => getIntNames($permissions_groups,2), "texts" => getTexts($permissions_groups,$perm_texts,2), "shift" => cumSum($permissions_groups,2), "breakAfter" => 2, "getValue" => "return values[\"permissions\"];", ),
		array("item" => "checkset", "int_name" => "permissions_order", "loadBlind" => true, "int_names" => getIntNames($permissions_groups,3), "texts" => getTexts($permissions_groups,$perm_texts,3), "shift" => cumSum($permissions_groups,3), "breakAfter" => 2, "getValue" => "return values[\"permissions\"];", ),

		array("item" => "pk", "text" => s("institution"),"int_name" => "institution_id", "setValues" => "return a(selected_values,\"institution_name\");", "table" => "institution", "allowNone" => true, "noneText" => s("not_set"), "setNoneText" => s("none")), 

		array("item" => "input", "int_name" => "cost_centre", "size" => 10,"maxlength" => 20, ), 
		array("item" => "input", "int_name" => "acc_no", "size" => 5, "maxlength" => 20, ), 
		array("item" => "input", "int_name" => "cost_limit", "size" => 5, "maxlength" => 20, ), 
		array("item" => "input", "int_name" => "cost_limit_currency", "size" => 5, "maxlength" => 20, SPLITMODE => true, ), 
		array("item" => "input", "int_name" => "email_chemical_supply", "size" => 20, ), // MPI
		
		//~ array("item" => "input", "int_name" => "owns_cost_centres", "type" => "textarea", "cols" => 40, "rows" => 4, "getValue" => "return getCostCentres(values); ", ), 
		array("item" => "input", "int_name" => "email", "size" => 20,"maxlength" => 100), 
		array("item" => "pk_select", "table" => "project", "int_name" => "project", "pkName" => "project_id", "nameField" => "project_name", "text" => s("working_on_projects"), "multiMode" => true), 
		"tableEnd", 

		// Laborjournale
		array("item" => "subitemlist", "int_name" => "lab_journal", "skip" => !$editMode, DEFAULTREADONLY => "always", 
			"fields" => array(
				array("item" => "cell"), 
				array("item" => "hidden", "int_name" => "lab_journal_id", ),
				array("item" => "input", "int_name" => "lab_journal_code", ),
				array("item" => "cell"), 
				array("item" => "select", "int_name" => "lab_journal_status", ),
				array("item" => "cell"), 
				array("item" => "js", "int_name" => "detailbutton", "functionBody" => "get_reference_link(\"lab_journal\",values[\"db_id\"],values[\"lab_journal_id\"]);", "class" => "noprint", ) 
			),
		), 

		// ausgeliehene Gebinde
		array("item" => "subitemlist", "int_name" => "borrowed", "skip" => !$editMode, DEFAULTREADONLY => "always", 
			"fields" => array(
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "molecule_name", "size" => 15),
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "amount", "size" => 4),
				array("item" => "text", "value" => "&nbsp;/ "), 
				array("item" => "input", "int_name" => "actual_amount", "size" => 4),
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
				array("item" => "input", "int_name" => "comment_cheminstor", "type" => "textarea", "cols" => 30, "rows" => 2),
				array("item" => "cell"), 
				array("item" => "js", "int_name" => "detailbutton", "functionBody" => "get_reference_link(\"chemical_storage\",values[\"db_id\"],values[\"chemical_storage_id\"]);", "class" => "noprint", ) 
			),
		), 
	));
	
	return $retval;
}
?>