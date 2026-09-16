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

function showDataPublicationForm($paramHash) {
	global $editMode,$permissions;
	$paramHash["int_name"] = ifempty($paramHash["int_name"], "data_publication");

	$paramHash["change"][READONLY]='visibleObj("search_assignments",!thisValue); ';
	$paramHash["setControlValues"]='visibleObj("literature_FS",!readOnly||a(values,"literature_id")); visibleObj("submitDataPublication",a(values,"publication_status")==1);'; // 1=prepared
	
	$licenses=array("CC-BY 4.0", "CC-BY 3.0-DE", "CC-BY 3.0-AT", "CC-BY 3.0-CH"); // names must be identical to Sciflection
	
	$retval = getFormElements($paramHash, array(
		array("item" => "text", "text" => "<table class=\"noborder\"><tr><td style=\"vertical-align:top;min-width:530px;width:40%\">"),
		"tableStart",
		array("item" => "input", "int_name" => "publication_name",),
		array("item" => "select", "int_name" => "publication_license", "int_names" => $licenses, "texts" => $licenses, ),
		array("item" => "input", "int_name" => "publication_doi",),
		array(
			"item" => "pk_select",
			"int_name" => "publication_db_id",
			"pkName" => "other_db_id",
			"nameField" => "db_beauty_name",
			"table" => "other_db",
			"skipOwn" => true,
			"filter" => "capabilities='sciflection'",
			"filterDisabled" => true,
		// only those with "sciflection" capability
		),
		array("item" => "js", "int_name" => "data_publication_uid", "skip" => !$editMode, "text" => s("afterUploadHyperlink"), "functionBody" => '"<a href=\"'.SCIFLECTION_URL.'/startUseCase?useCase=performSearch&viewMode=1&table=ElnReaction&UUID="+values["data_publication_uid"]+"\" target=\"_blank\">'.SCIFLECTION_URL.'/startUseCase?useCase=performSearch&viewMode=1&table=ElnReaction&UUID="+values["data_publication_uid"]+"</a>"'),
		array(
			"item" => "pk",
			"text" => s("literature"),
			"int_name" => "literature_id",
			"table" => "literature",
			"setNoneText" => s("create_new_literature"),
			"allowNone" => true,
			"skip" => $paramHash["new_literature"], // , TABLEMODE => false
			"setValues" =>
			'if (readOnly && selected_values["literature_id"]==null) {'
			. 'return "";'
			. '}'
			. 'var newMol=(a(selected_values,"literature_id")==""),otherDb=(a(selected_values,"db_id")!="-1"); '
			. 'if (otherDb) { '
			. 'selected_values["literature_id"]="";  '
			. '} '
			. 'if (init==true) {'
			. 'if (newMol || otherDb) { ' // Möglichkeit für Änderungen
			. 'readOnlyForm("literature",false); '
			. '}'
			. 'else { ' // nur PK
			. 'readOnlyForm("literature",true); '
			. '}'
			. 'delete selected_values["db_id"]; '
			. 'resetAlreadyLoaded(); '
			. 'setControlValues(selected_values,false); '
			. '} '
			. 'if (newMol) { '
			. 'return ' . fixStr(s("create_new_literature")) . '; '
			. '} '
			. 'return a(selected_values,"sci_journal_abbrev")+", "+a(selected_values,"literature_year");',
		),
		"tableEnd",
		array("item" => "text", "text" => "<div id=\"search_assignments\"><input type=\"text\" onKeyUp=\"updateResults(&quot;results&quot;,this.value);\" placeholder=".fixStr(s("add_dataset"))."/><br/><div id=\"results\"style=\"height:100px;overflow:auto;\"></div></div>"),
		
		array("item" => "text", "text" => "</td><td colspan=\"2\">"),
		array("item" => "input", "int_name" => "publication_text", "type" => "textarea", "cols" => 40, "rows" => 4,),
		array("item" => "text", "text" => "</td></tr></table>"),
		array("item" => "text", "text" => "<table class=\"noborder\"><tr style=\"vertical-align:top\"><td>"),
			// search field to add lab notebook entry, project or lab notebook (incl. ana data)
		// reactions
		array("item" => "subitemlist", 
			"int_name" => "publication_reaction", 
			"unique_fields" => array("reaction_id"), // applies to noOverwrite
			"noManualAdd" => true,
			"fields" => array(
				array("item" => "cell"),
				array("item" => "hidden", "int_name" => "publication_reaction_id",),
				array("item" => "hidden", "int_name" => "reaction_id",),
				// Lab notebook entry
				array("item" => "input", "int_name" => "lab_journal_code", DEFAULTREADONLY => "always",),
				array("item" => "input", "int_name" => "nr_in_lab_journal", DEFAULTREADONLY => "always",),
				// Carried out by
				array("item" => "cell"),
				array("item" => "input", "int_name" => "reaction_carried_out_by", DEFAULTREADONLY => "always",),
				// Date exp start
				array("item" => "cell"),
				array("item" => "input", "int_name" => "reaction_started_when", DEFAULTREADONLY => "always",),
				array("item" => "cell"),
				array("item" => "input", "int_name" => "publication_reaction_text", "text" => s("comment"),),
				array("item" => "cell"),
				array(
					"item" => "js",
					"int_name" => "detailbutton",
					"functionBody" => "get_reference_link(\"reaction\",values[\"db_id\"],values[\"reaction_id\"]);",
					"class" => "noprint",
				),
				array("item" => "links",)
			),
		),
		array("item" => "text", "text" => "</td><td>"),
		// ana data
		array("item" => "subitemlist", 
			"int_name" => "publication_analytical_data", 
			"unique_fields" => array("analytical_data_id"), // applies to noOverwrite
			"noManualAdd" => true,
			"fields" => array(
				array("item" => "cell"),
				array("item" => "hidden", "int_name" => "publication_analytical_data_id",),
				array("item" => "hidden", "int_name" => "analytical_data_id",),
				array("item" => "input", "int_name" => "analytical_data_identifier", DEFAULTREADONLY => "always",),
				array("item" => "cell"),
				array("item" => "input", "int_name" => "analytics_type_name", DEFAULTREADONLY => "always",),
				array("item" => "cell"),
				array("item" => "input", "int_name" => "analytics_device_name", DEFAULTREADONLY => "always",),
				array("item" => "cell"),
				array("item" => "input", "int_name" => "analytics_method_name", DEFAULTREADONLY => "always",),
				// Lab notebook entry
				array("item" => "cell"),
				array("item" => "input", "int_name" => "lab_journal_code", DEFAULTREADONLY => "always",),
				array("item" => "input", "int_name" => "nr_in_lab_journal", DEFAULTREADONLY => "always",),
				// Carried out by
				array("item" => "cell"),
				array("item" => "input", "int_name" => "measured_by", DEFAULTREADONLY => "always",),
				// Date exp start
				array("item" => "cell"),
				array("item" => "input", "int_name" => "analytical_data_created_when", DEFAULTREADONLY => "always",),
				array("item" => "cell"),
				array("item" => "input", "int_name" => "publication_analytical_data_text", "text" => s("comment"),),
				array("item" => "cell",),
				array(
					"item" => "js",
					"int_name" => "detailbutton",
					"functionBody" => "get_reference_link(\"analytical_data\",values[\"db_id\"],values[\"analytical_data_id\"]);",
					"class" => "noprint",
				),
				array("item" => "links",)
			),
		),
		array("item" => "text", "text" => "</td></tr></table>"),
		array("item" => "input", "int_name" => "data_publication_created_by", "text" => s("created_by"), DEFAULTREADONLY => "always"),
		array("item" => "input", "int_name" => "data_publication_created_when", DEFAULTREADONLY => "always", "type" => "date"),
		"br",
		array("item" => "input", "int_name" => "data_publication_changed_by", "text" => s("changed_by"), DEFAULTREADONLY => "always"),
		array("item" => "input", "int_name" => "data_publication_changed_when", DEFAULTREADONLY => "always", "type" => "date"),
	));

	return $retval;
}

?>