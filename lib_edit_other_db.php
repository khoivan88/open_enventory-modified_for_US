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
function showOtherdbEditForm($paramHash) { // ergänzen: [capabilities]
	global $checkErrors,$db_server,$db_name,$editMode;
	
	$paramHash["int_name"]="other_db";
	$paramHash["checkSubmit"]=
		'if (getControlValue("host")=="") { '
			.'alert("'.s("error_host").'");'
			.'return false;'
		.'} '
		.'if (getControlValue("host")=="'.$db_server.'" && getControlValue("db_name")=="'.$db_name.'") { '
			.'alert("'.s("error_main_db").'");'
			.'return false;'
		.'} '
		.'if (getControlValue("db_name")=="") { '
			.'alert("'.s("error_db_name").'");'
			.'return false;'
		.'} '
		.'if (getControlValue("db_user")=="") { '
			.'alert("'.s("error_user").'");'
			.'return false;'
		.'} '
		.'if (getControlValue("db_user")=='.fixStr(ROOT).') { '
			.'alert("'.s("error_root").'");'
			.'return false;'
		.'} ';
	
	if (!$editMode) {
		$paramHash["checkSubmit"].=
			'if (getControlValue("db_pass")=="") { '
				.'alert("'.s("password_none").'");'
				.'return false;'
			.'} ';
	}
	
	$retval=getFormElements($paramHash,array(
		"tableStart", 
		array("item" => "check", "int_name" => "other_db_disabled"),
		array("item" => "input", "int_name" => "db_beauty_name", "size" => 20,"maxlength" => 50), 
		array("item" => "input", "int_name" => "host", "size" => 20,"maxlength" => 50, "checkErrors" => array(array("conditions" => array("","==\"\""), "message" => s("error_host"))) ), 
		array("item" => "input", "int_name" => "db_name", "size" => 20,"maxlength" => 50, "checkErrors" => array(array("conditions" => array("","==\"\""), "message" => s("error_db_name"))) ), 
		array("item" => "input", "int_name" => "db_user", "size" => 20,"maxlength" => 50), 
		array("item" => "input", "int_name" => "db_pass", "size" => 10,"maxlength" => 20,"type" => "password"), 
		array(
			"item" => "checkset", 
			"int_name" => "capabilities", 
			"int_names" => getValueList("other_db","capabilities"), 
			"roList" => true, 
			"breakAfter" => 2, 
		), 
		"tableEnd", 
	));
	
	return $retval;
}
?>