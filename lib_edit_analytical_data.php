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

function showAnalyticalDataEditForm($paramHash) {
	global $editMode,$permissions,$settings;
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"analytical_data");
	
	$paramHash["change"][READONLY]=
'if (thisValue==false) { '.
	'touchOnChange("analytics_type_id"); '.
	//~ 'PkSelectUpdate("analytics_device_id"); '.
'}';	
	
	if (($permissions & _lj_edit)==0 && ($permissions & _lj_edit_own)!=0) {
		$studentMode="formulare[\"analytical_data\"][\"disableEdit\"]=(getCacheValue(\"person_id\")!=".fixNull($person_id)."); updateButtons(); ";
	}
	
	// bei readOnly nur eine <option im <select Parameter für pk_select bei dynamic=true
	$paramHash["setControlValues"]=
		'if (values["analytical_data_id"]!=undefined) { '. // kompletter Datensatz
			'clearControl("spzfile"); '.
			'updateAnalyticalDataImg(values["db_id"],values["analytical_data_id"],0,values["timestamp"]'.($settings["disable_analytical_data_mouseover"]?",true":"").'); '.
		'} '.
		'if (readOnly!=false) { '.
			$studentMode.
		'} ';
	
	$paramHash["loadDatasetIntoCache"]=
		'if (values["analytical_data_id"]!=undefined) { '.
			'preloadImg(getAnalyticalDataImgURL(values["db_id"],values["analytical_data_id"],0,values["timestamp"]));'.
		'} ';
	
	$retval=getFormElements($paramHash,array(
		array(
			"item" => "pk", 
			"text" => s("reaction"), 
			"table" => "reaction", 
			"int_name" => "reaction_id", 
			"allowNone" => true, 
			"onChange" => "", 
			"skip" => $paramHash["reducedMode"], 
			"setValues" =>
				'PkSelectUpdate("reaction_chemical_id"); '.
				'if (a(selected_values,"reaction_id")!="") { '.
					'return a(selected_values,"lab_journal_code")+" "+a(selected_values,"nr_in_lab_journal"); '.
				'} '.
				'return '.fixStr(s("none")).'; '
		), // update reaction_chemical_id

		"tableStart",

		// nur Komponenten der gewählten RXN
		array(
			"item" => "pk_select", 
			"text" => s("reaction_chemical_uid"), 
			"nameField" => "standard_name", 
			"table" => "reaction_chemical", 
			"int_name" => "reaction_chemical_id", 
			"allowNone" => true, 
			"noneText" => s("rxn_mixture"), 
			"skip" => $paramHash["reducedMode"],
			"dynamic" => true, 
			
			"getFilter" => 
				'var query=new Array(),retval=new Array(),url="",reaction_id=getControlValue("reaction_id"); '.
				'if (reaction_id=="") { '. // person_id
					'return false; '.
				'} '.
				'query.push("<0>"); '.
				'url+="&crit0=reaction_chemical.reaction_id&op0=eq&val0="+reaction_id; '.
				'retval["filter"]="query="+query.join(" AND ")+url; '.
				'retval["dbs"]="-1"; '.
				'return retval;', 

			"getText" => 
				'if (!rowData["reaction_chemical_id"]) { '.
					'return s("rxn_mixture"); '.
				'} '.
				'var retval=rowData["standard_name"]; '.
				'if (!retval) { '.
					'switch (rowData["role"]) { '.
					'case "1": '.
						'retval=s("reactant"); '.
					'break; '.
					'case "2": '.
						'retval=s("reagent"); '.
					'break; '.
					'case "6": '.
						'retval=s("product"); '.
					'break; '.
					'} '.
					'retval+=" "+rowData["nr_in_reaction"]; '.
				'} '.
				'return retval;'
		), // => dynamic

		array("item" => "input", "int_name" => "fraction_no"), 
		array("item" => "input", "int_name" => "analytical_data_identifier", DEFAULTREADONLY => "always"), 
		array("item" => "input", "int_name" => "measured_by", "size" => 10, "maxlength" => 100),
		array("item" => "input", "int_name" => "analytical_data_comment", "type" => "textarea", ),

		array(
			"item" => "pk_select", 
			"int_name" => "analytics_type_id", 
			"table" => "analytics_type", 
			"nameField" => "analytics_type_name", 
			"onChange" => "analytics_type_updated()", 
			"order_obj" => getUserDefOrderObj("analytics_type"), 
		), 
		array("item" => "input", "int_name" => "analytics_type_name", DEFAULTREADONLY => "always"), 
		array("item" => "input", "int_name" => "analytics_type_code", DEFAULTREADONLY => "always"), 
		array("item" => "input", "int_name" => "analytics_type_text", DEFAULTREADONLY => "always", "type" => "textarea", ), 

		array(
			"item" => "pk_select", 
			"int_name" => "analytics_device_id", 
			"table" => "analytics_device", 
			"updateFunction" => "analytics_device_updated();", 
			"dynamic" => true, 
			"filterDisabled" => true, 
			"getFilter" => 
				'return "query=<0>&crit0=analytics_type.analytics_type_id&op0=eq&val0="+getControlValue("analytics_type_id");', 
			"getText" => 
				'return rowData["analytics_device_name"];', 
			"order_by" => "analytics_device_order", 
		),
		array("item" => "input", "int_name" => "analytics_device_name", DEFAULTREADONLY => "always"), 
		array("item" => "input", "int_name" => "analytics_device_driver", DEFAULTREADONLY => "always"), 

		array(
			"item" => "pk_select", 
			"int_name" => "analytics_method_id", 
			"table" => "analytics_method", 
			"updateFunction" => "analytics_method_updated();", 
			"dynamic" => true, 
			"filterDisabled" => true, 
			"allowNone" => true, 
			"noneText" => s("autodetect"), 
			"getFilter" => 
				'var query="query=<0>",cond0="&crit0=analytics_type.analytics_type_id&op0=eq&val0="+getControlValue("analytics_type_id"),cond1="",analytics_device_id=getControlValue("analytics_device_id"); '.
				'if (analytics_device_id!="") { '.
					'query+=" AND (<1> OR <2>)";'.
					'cond1="&crit1=analytics_device.analytics_device_id&op1=eq&val1="+analytics_device_id+"&crit2=analytics_device.analytics_device_id&op2=nu"; '.
				'} '.
				'return query+cond0+cond1;', 
			"getText" => 
				'return rowData["analytics_method_name"];', 
		), 
		array("item" => "input", "int_name" => "analytics_method_name", DEFAULTREADONLY => "always"), 
		array("item" => "input", "int_name" => "analytics_method_text", DEFAULTREADONLY => "always", "type" => "textarea", ),

		"tableEnd",
		array("item" => "input", "int_name" => "spzfile", "type" => "folder_browser", "search_url" => "searchAnalyticsFrame.php?analytics_device_id="),
		array("item" => "text", "text" => "<div id=\"analytical_data_img\"></div>"), 

		array("item" => "input", "int_name" => "analytical_data_interpretation", "type" => "textarea", "skip" => !$editMode),
	));
	
	return $retval;
}
?>