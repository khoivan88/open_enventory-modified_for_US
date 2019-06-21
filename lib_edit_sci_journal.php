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
function showSciJournalEditForm($paramHash) {
	global $editMode;
	
	$paramHash["int_name"]="sci_journal";
	$paramHash["checkSubmit"]=
		'if (getControlValue("sci_journal_name")=="" && getControlValue("sci_journal_abbrev")=="") { '
			.'alert("'.s("error_no_sci_journal_name").'"); '
			.'focusInput("sci_journal_name"); '
			.'return false; '
		.'} ';
	
	$paramHash["setControlValues"]=
		'if (readOnly==true) { '.
			'setTitle(strcut(a(values,"sci_journal_name"),30)); '.
		'} ';
	
	$literature_paramHash=getLiteratureParamHash();
	$literature_paramHash["skip"]=($paramHash["no_db_id_pk"] || !$editMode);
	$literature_paramHash[DEFAULTREADONLY]="always";
	
	$retval=getFormElements($paramHash,array(
		"tableStart", 
		array("item" => "input", "int_name" => "sci_journal_name", "size" => 50, "maxlength" => 300), 
		array("item" => "input", "int_name" => "sci_journal_abbrev", "size" => 20, "maxlength" => 60), 
		"tableEnd", 
		$literature_paramHash, // list of literature for this journal
	)); 

	return $retval;
}
?>