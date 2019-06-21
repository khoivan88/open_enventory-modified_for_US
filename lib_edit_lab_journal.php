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

function showLabJournalEditForm($paramHash) {
	global $editMode,$permissions;
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"lab_journal");
	
	$paramHash["setControlValues"]=
		'var lab_journal_status=values["lab_journal_status"];'.
		'showControl("btn_close_print",(lab_journal_status<3)); '.
		'visibleObj("btn_close_lj",(lab_journal_status<2)); '.
		'if (readOnly==true) { '.
			'setTitle(a(values,"lab_journal_code")); '.
		'} ';
	
	
	$paramHash["checkSubmit"]=
		'if (getControlValue("lab_journal_code")=="") { '
			.'alert("'.s("error_no_lab_journal_code").'");'
			.'focusInput("lab_journal_code"); '
			.'return false;'
		.'} ';

	$print_what_texts=array(s("print_all"),s("print_range"),);
	
	$retval=getFormElements($paramHash,array(
		"tableStart",
		array(
			"item" => "pk_select", 
			"text" => s("person"), 
			"int_name" => "person_id", 
			"table" => "person", 
			DEFAULTREADONLY => ($editMode?"always":""), 
			"allowNone" => true, 
			"onChange" => "updateLJSigle();", 
			"dbs" => -1, 
			"skip" => $paramHash["new_person"], 
		), 
		array("item" => "input", "int_name" => "lab_journal_code", "size" => 20, "maxlength" => 100, DEFAULTREADONLY => ($editMode?"always":""), "clearbutton" => true, ), 
		array(
			"item" => "select", 
			"int_name" => "lab_journal_status", 
			"langKeys" => getValueList("lab_journal","lab_journal_status"), 
			DEFAULTREADONLY => "always", 
		), 
		array(
			"item" => "pk_select", 
			"int_name" => "default_copy_target", 
			"table" => "lab_journal", 
			"allowNone" => true, 
			"noneText" => s("user_default_lab_journal"), 
			"allowAuto" => !$editMode, 
			"autoText" => s("this_lab_journal"), 
			"dbs" => -1, 
			"order_by" => getOrderObjFromKey("lab_journal_code","lab_journal"), 
			"pkName" => "lab_journal_id", 
			"nameField" => "lab_journal_code", 
		),
		array("item" => "input", "int_name" => "start_nr", "size" => 4, "maxlength" => 6, "skip" => $editMode, "value" => 1, ), 
		array("item" => "check", "int_name" => "create_empty_entries", "skip" => $editMode, ),

		// knopf zum zumachen
		array(
			"item" => "text", 
			"int_name" => "btn_close_print", 
			"skip" => (!$editMode || ($permissions & _admin+_lj_admin)==0), 
			"text" => "<table class=\"noborder\"><tr>
<td><a href=\"javascript:closeLabJournal(".fixQuot($_SESSION["sess_proof"]).")\" id=\"btn_close_lj\" class=\"imgButtonSm\"><img src=\"lib/close_lab_journal_sm.png\" border=\"0\"".getTooltip("close_lab_journal")."></a></td>
<td><a href=\"javascript:showPrintLJ(true)\" id=\"btn_print_lj\" class=\"imgButtonSm\"><img src=\"lib/print_sm.png\" border=\"0\"".getTooltip("print_lab_journal")."></a>
<div id=\"printMenuLJ\" style=\"display:none;position:absolute;background-color:".defBgColor.";border:1px solid black;padding:8px;z-index:5\">".
			s("print").
			showBr().
			showSelect(array(
				"int_name" => "printLJ_what", 
				"radioMode" => true, 
				"int_names" => array("printLJ_all","printLJ_range"), 
				"value" => "printLJ_all", 
				"texts" => $print_what_texts, 
			))."
	<input type=\"text\" id=\"printLJ_range_input\" size=\"8\" onClick=\"$(&quot;printLJ_range&quot;).checked=&quot;checked&quot;\">
	<table class=\"noborder\"><tbody><tr>
	<td><a href=\"javascript:invokePrintLJ()\" class=\"imgButtonSm\"><img src=\"lib/print_sm.png\" border=\"0\"".getTooltip("print")."></a></td>
	<td><a href=\"javascript:showPrintLJ(false)\" class=\"imgButtonSm\"><img src=\"lib/cancel_sm.png\" border=\"0\"".getTooltip("cancel")."></a></td>
	</tr></tbody></table>
</div>

</td></tr></table>"
		),
		//~ array("item" => "pk_select", "text" => s("project"), "int_name" => "project_id", "nameField" => "project_name", "table" => "project", "allowNone" => true, "size" => 1), 
		"tableEnd"
	));

	return $retval;
}
?>