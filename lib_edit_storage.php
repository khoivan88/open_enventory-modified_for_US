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

function showStorageEditForm($paramHash) {
	global $editMode;
	$paramHash["int_name"]="storage";
	$paramHash["checkSubmit"]=
		'if (getControlValue("storage_name")=="") { '
			.'alert("'.s("error_no_storage_name").'");'
			.'return false;'
		.'} ';

	$paramHash["setControlValues"]=
		'var db_id=a(values,"db_id"),own_db=(db_id=="-1"); '.
		'if (db_id) { '.
			'showControl("storage_secret",own_db); '.
		'} '.
		'if (readOnly==true) { '.
			'setTitle(strcut(a(values,"storage_name"),30)); '.
		'} ';
	
	$retval=getFormElements($paramHash,array(
		"tableStart",
		array("item" => "input", "int_name" => "storage_name", "size" => 20,"maxlength" => 80, ), 
		array("item" => "input", "int_name" => "storage_barcode", "size" => 20, ), 
		array("item" => "check", "int_name" => "poison_cabinet", ), 
		array(
			"item" => "pk_select", 
			"text" => s("at_storage"), 
			"int_name" => "institution_id", 
			"nameField" => "institution_name", 
			"table" => "institution", 
			"allowNone" => true, 
			//~ "noneText" => s("not_set"), 
			//~ "setNoneText" => s("none"), 
			"setValues" => "return a(selected_values,\"institution_name\");", 
		),
		"tableEnd", 
		array("item" => "check", "int_name" => "storage_secret", DEFAULTREADONLY => $defaultReadOnly), 
	));
	
	return $retval;
}
?>