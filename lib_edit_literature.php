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
require_once "lib_edit_sci_journal.php";

function showLiteratureEditForm($paramHash) {
	global $editMode;
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"literature");

	$paramHash["setControlValues"]=
		'if (values["literature_id"]!=undefined) { '. // kompletter Datensatz
			'updateLiteratureImg(values["db_id"],values["literature_id"],values["timestamp"]); '.
		'} ';
	
	$retval=getFormElements($paramHash,array(
		"tableStart", 
		array("item" => "input", "int_name" => "authors", "size" => 70, "getValue" => "return getAuthorNames(values,0); ", ), // change to settings later
		array("item" => "hidden", "int_name" => "action_sci_journal"), 
		array(
			"item" => "pk", 
			"int_name" => "sci_journal_id", 
			"text" => s("sci_journal"), 
			"table" => "sci_journal", 
			"allowNone" => true, 
			"noneText" => s("new_sci_journal"), 
			"setNoneText" => s("create_new_sci_journal"), 
			
			"setValues" => 
				'var newSciJournal=(a(selected_values,"sci_journal_id")==""); '
				.'if (init) {'
					.'if (newSciJournal) { '
						.'showForm("sci_journal",true); '
						.'readOnlyForm("sci_journal",false); '
					.'}'
					.'else if (editMode) { '
						.'readOnlyForm("sci_journal",true); '
					.'}'
					.'else { '
						.'showForm("sci_journal",false); '
					.'} '
					.'delete selected_values["db_id"]; '
					.'setControlValues(selected_values,false); '
				.'} '
				.'if (newSciJournal) { '
					.'return '.fixStr(s("new_sci_journal")).'; '
				.'} '
				.'return a(selected_values,"sci_journal_name");', 
		),
		array("item" => "input", "int_name" => "literature_year", "size" => 10, "maxlength" => 6, ), 
		array("item" => "input", "int_name" => "literature_volume", "size" => 10, "maxlength" => 6, ), 
		array("item" => "input", "int_name" => "issue", "size" => 10, "maxlength" => 6, ), 
		array("item" => "input", "int_name" => "page", "type" => "range", "postProc" => "page_range", "size" => 10, "maxlength" => 15, ), 
		array("item" => "input", "int_name" => "doi", "size" => 35, "maxlength" => 60, ), 
		array("item" => "input", "int_name" => "isbn", "size" => 8, "maxlength" => 20, ), 
		array("item" => "input", "int_name" => "literature_title", "size" => 25, ), 
		array("item" => "input", "int_name" => "literature_blob_upload", "type" => "file"), // allow URL here as well
		"tableEnd", 
		//~ array("item" => "js", "int_name" => "btn_download", "loadBlind" => true, "functionBody" => "literature_getDownload(values);"), // download button
		array("item" => "input", "int_name" => "keywords", "type" => "textarea_classic", "rows" => 5, "cols" => 80, ), 
		"br", 
		array("item" => "check", "int_name" => "literature_secret"), 
		"br", 
		// >>> FR 091025
		array("item" => "input", "int_name" => "literature_created_by", DEFAULTREADONLY => "always", ), 
		array("item" => "input", "int_name" => "literature_created_when", DEFAULTREADONLY => "always", "type" => "date", ), 
		"br",
		array("item" => "input", "int_name" => "literature_changed_by", DEFAULTREADONLY => "always", ), 
		array("item" => "input", "int_name" => "literature_changed_when", DEFAULTREADONLY => "always", "type" => "date", ), 
		"br",
		// <<< FR 091025
		array("item" => "text", "text" => "<div id=\"analytical_data_img\"></div>", ), 
		// >>> FR 091025
		// Projekte
		array(
			"item" => "subitemlist", 
			"int_name" => "project", 
			"skip" => $paramHash["no_db_id_pk"] || !$editMode, 
			DEFAULTREADONLY => "always", 
			"fields" => array(
				array("item" => "cell"), 
				array("item" => "hidden", "int_name" => "project_id", ),
				array("item" => "input", "int_name" => "project_name", ),
				array("item" => "cell"), 
				array(
					"item" => "js", 
					"int_name" => "detailbutton", 
					"functionBody" => "get_reference_link(\"project\",values[\"db_id\"],values[\"project_id\"]);", 
					"class" => "noprint", 
				), 
			) 
		), 
		// Reaktionen
		array(
			"item" => "subitemlist", 
			"int_name" => "reaction", 
			"skip" => $paramHash["no_db_id_pk"] || !$editMode, 
			DEFAULTREADONLY => "always", 
			"fields" => array(
				array("item" => "cell"), 
				array("item" => "hidden", "int_name" => "reaction_id", ),
				array("item" => "input", "int_name" => "lab_journal_code", ),
				array("item" => "text", "value" => " "), 
				array("item" => "input", "int_name" => "nr_in_lab_journal", ),
				
				
				array("item" => "cell"), 
				array(
					"item" => "js", 
					"int_name" => "detailbutton", 
					"functionBody" => "get_reaction_link(values[\"db_id\"],values[\"reaction_id\"],values[\"lab_journal_id\"]);", 
					"class" => "noprint", 
				), 
			) 
		), 
		// <<< FR 091025
	));
	
	return $retval;
}
?>