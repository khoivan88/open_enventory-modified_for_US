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
function showProjectEditForm($paramHash) {
	$literature_paramHash=getLiteratureParamHash();
	$literature_paramHash["int_name"]="project_literature";
	$literature_paramHash["fields"][]=array("item" => "hidden", "int_name" => "project_literature_id");
	
	$reac_literature_paramHash=$literature_paramHash;
	$reac_literature_paramHash["int_name"]="reaction_literature_for_project";
	$reac_literature_paramHash[DEFAULTREADONLY]="always";
	
	$paramHash["int_name"]="project";
	
	$paramHash["setControlValues"]=
		'if (readOnly==true) { '.
			'setTitle(strcut(a(values,"project_name"),30)); '.
		'} ';
	
	$paramHash["checkSubmit"]=
		'if (getControlValue("project_name")=="") { '
			.'alert("'.s("error_no_project_name").'");'
			.'focusInput("project_name"); '
			.'return false;'
		.'} ';
	
	$retval=getFormElements($paramHash,array(
		"tableStart",
		array("item" => "input", "int_name" => "project_name", "size" => 60, "maxlength" => 200, ), 
		array("item" => "input", "int_name" => "project_created_when", DEFAULTREADONLY => "always", "type" => "date", ), 
		array(
			"item" => "select", 
			"int_name" => "project_status", 
			"langKeys" => getValueList("project","project_status"), 
		), 
		array("item" => "input", "int_name" => "project_text", "type" => "textarea", "rows" => 20, "cols" => 80, ), 
		"tableEnd",
		array(
			"item" => "pk_select", 
			"table" => "person", 
			"int_name" => "person", 
			"dbs" => -1,
			"pkName" => "person_id", 
			"text" => s("assigned_persons"), 
			"separator" => "; ", 
			"filterDisabled" => true, 
			"multiMode" => true, 
		), 
		"br", 
		array("item" => "check", "int_name" => "project_members_only", ), 
		"br", 
		$literature_paramHash, 
		$reac_literature_paramHash, 
	));
	
	return $retval;
}
?>