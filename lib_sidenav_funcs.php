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

function showProjectLinks($linkParams) {
	global $person_id;
	if (!empty($person_id)) {
		// ausgeliehen
		showSideLink(array(
			"url" =>getCombiButtonURL(array(
				"table" => "project", 
				"this_pk_name" => "project_person.person_id", 
				"db_id" => -1, 
				"pk" => $person_id, 
			)), 
			"text" => s("my_projects"), 
			"target" => "mainpage", 
		));
	}
	showSideLink(array("url" => "list.php?table=project&dbs=-1&".$linkParams, "text" => s("edit_projects"), "target" => "mainpage"));
}

function getDbsMultiselectA() {
	return "<a href=\"javascript:allDBs()\" style=\"color:white;text-decoration:none\"><small>".s("select_all_items")."</small></a>";
}

function getDbsMultiselectB() {
	global $other_db_data;
	$retval="<select name=\"dbs[]\" id=\"dbs\" size=\"5\" multiple=\"multiple\"><option value=\"-1\" selected=\"selected\" style=\"font-weight:bold\">".s("own_database");
	if (is_array($other_db_data)) foreach ($other_db_data as $other_db) {
		if (in_array($other_db["other_db_id"],$_SESSION["other_db_disabled"])) {
			continue;
		}
		$retval.="<option value=".fixStr($other_db["other_db_id"]).">".$other_db["db_beauty_name"];
	}
	$retval.="</select>";
	return $retval;
}

function getJSButton($JScommand,$imgSrc,$lang_key,$link_id) {
	$retval="<td><a href=\"Javascript:".$JScommand."\" class=\"imgButton\" id=".fixStr($link_id)."><img src=".fixStr($imgSrc)." height=\"25\" width=\"25\" border=\"0\" align=\"absmiddle\"".getTooltip($lang_key)."></a></td>"; //  style=\"background-color:white\"
	return $retval;
}

function getDetailSearchTableButton($table,$lang_key=null) {
	if (is_null($lang_key)) {
		$lang_key=$table;
	}
	$retval="<td><a href=\"".getSelfRef(array("table"))."&table=".$table."&desired_action=detail_search\" class=\"imgButton".($_REQUEST["table"]==$table?" buttonActive":"")."\" id=\"link_chemical\"><img src=\"lib/".$table.".png\" height=\"25\" width=\"25\" border=\"0\"".getTooltip($lang_key)."></a></td>";
	return $retval;
}

function showCommonButtons() {
	global $db_name,$g_settings;
	showSideLink(array("url" => "javascript:searchExt(0)","text" => s("src_emolecules")));
	showSideLink(array("url" => "javascript:searchExt(1)","text" => s("src_chemie_de")));
	showSideLink(array("url" => "http://riodb01.ibase.aist.go.jp/sdbs/cgi-bin/cre_index.cgi?lang=eng","text" => "SDBS", "target" => "_blank"));
	if (is_array($g_settings["links_in_sidenav"])) foreach ($g_settings["links_in_sidenav"] as $link) { // custom buttons from global settings
		showSideLink($link);
	}
}

function showSideLink($paramHash) {
	// Anzeige eines Links an der Seite
	global $links;
	$text=$paramHash["text"];
	if (!empty($paramHash["target"])) {
		$targetText=" target=".fixStr($paramHash["target"]);
	}
	echo "<a class=\"text\" href=".fixStr($paramHash["url"]).$targetText.">".$text."<div class=\"inactive\"><img src=\"lib/link.gif\" width=\"220\" height=\"8\" border=\"0\"></div><div class=\"active\"><img src=\"lib/link_act.gif\" width=\"220\" height=\"8\" border=\"0\"></div></a>\n";
}

//-----------------------functions for detail search------------------------------------------------
function getFieldTypeFromDef($col_def) {
	global $SQLtypes;
	$col_def=strtoupper($col_def);
	cutRange($col_def,"","("); // dont get confused by enums or sets
	foreach($SQLtypes as $name => $identifiers) {
		for ($a=0;$a<count($identifiers);$a++) {
			if (strpos($col_def,$identifiers[$a])!==FALSE) { // have it
				return $name;
			}
		}
	}
}

function getOpSelect($type) { // generiert JS-Code
	global $searchModes;
	$retval=<<<END
return "<select name=\"op"+element+"\" id=\"op"+element+"\">
END;
	for ($a=0;$a<count($searchModes[$type]);$a++) {
		$retval.=addslashes("<option value=\"".$searchModes[$type][$a]."\">").s($searchModes[$type][$a]);
	}
	$retval.=<<<END
</select>";

END;
	return $retval;
}

function getValInput($type) { // generiert JS-Code
	global $searchModes;

	switch ($type) {
	case "structure":
		$appletParams=array(
			"width"=> 300, 
			"height" => 315, 
			"searchMode" => true, 
			"compactMode" => true, 
		);
		$retval.="return \"".addslashes(getAppletHTML1($appletParams))."\\\"JME\"+element+\"\\\"".addslashes(getAppletHTML2($appletParams))."<input type=\\\"hidden\\\" name=\\\"val\"+element+\"\\\" id=\\\"val\"+element+\"\\\"><input type=\\\"hidden\\\" name=\\\"val\"+element+\"a\\\" id=\\\"val\"+element+\"a\\\"><table class=\\\"noborder\\\"><tr><td>"
.addslashes(getCopyButton1())."JME\"+element+\"".addslashes(getCopyButton2($appletParams))."</td><td>"
.addslashes(getPasteButton1())."JME\"+element+\"".addslashes(getPasteButton2($appletParams))."</td></tr></table><span id=\\\"unit\"+element+\"\\\"></span>\";\n";
	break;
	case "bool":
		$retval.=<<<END
return "<input type=\"hidden\" name=\"val"+element+"\" id=\"val"+element+"\"><span id=\"unit"+element+"\"></span>";
END;
	break;
	default: // textfeld
		$retval.=<<<END
return "<input type=\"text\" name=\"val"+element+"\" id=\"val"+element+"\" size=\"26\" maxlength=\"80\"><span id=\"unit"+element+"\"></span>";
END;
	// <span id=\"unit_span"+element+"\"></span>
	}
	return $retval;
}

function sortSearchFieldsPrio($a,$b) {
	return $a["priority"]-$b["priority"];
}

function addSearchField(& $searchFields,& $default_priority,$join_table,$name,$data) {
	if (isset($data["search"])) {
		$default_priority++;
		if (isset($data["searchPriority"])) {
			$priority=-$data["searchPriority"]; // negativ, damit es an den Anfang kommt
		}
		else {
			$priority=$default_priority;
		}
		$type=$data["search"];
		if ($type=="auto") {
			$type=getFieldTypeFromDef($data["type"]);
		}
		$searchFields[]=array("tableName" => $join_table, "fieldName" => $name, "priority" => $priority, "type" => $type, "allowedClasses" => $data["allowedClasses"] );
	}	
}

function getSearchFields($table) {
	global $tables,$virtual_tables,$query,$barcodePrefixes;
	//~ $join_tables=ifempty($query[$table]["join_tables"],array($table));
	$join_tables=arr_merge(array($table),$query[$table]["joins"]);
	$default_priority=0;
	$searchFields=array();
	// tabellen nach suchfeldern scannen
	if (is_array($join_tables)) foreach ($join_tables as $join_table) {
		if (count($tables[$join_table]["fields"])) { // gibt es die Tabelle?
			foreach ($tables[$join_table]["fields"] as $name => $data) {
				addSearchField($searchFields,$default_priority,$join_table,$name,$data);
			}
			if (is_array($tables[$join_table]["virtualFields"])) foreach ($tables[$join_table]["virtualFields"] as $name => $data) {
				$default_priority++;
				if (isset($data["searchPriority"])) {
					$priority=-$data["searchPriority"]; // negativ, damit es an den Anfang kommt
				}
				else {
					$priority=$default_priority;
				}
				
				// Was wird gesucht? Anzahl, Text, mit Einheit, etc.
				if ($data["fieldType"]=="count") {
					$data_type="num";
				}
				else {
					$data_type=$data["type"];
				}
				
				if ($data["fieldType"]=="flat") {
					// make query to build list
					$entry_data=mysql_select_array(array("dbs" => "-1", "table" => $data["fieldListTable"]));
					//~ print_r($entry_data);die();
					for ($b=0;$b<count($entry_data);$b++) {
						// go through result list and add to $searchFields
						$default_priority++;
						$searchFields[]=array("tableName" => $join_table, "fieldNamePrefix" => $name."/", "fieldName" => $entry_data[$b][ $data["fieldListCol"] ], "priority" => $default_priority, "type" => $entry_data[$b][ $data["fieldTypeCol"] ], "allowedClasses" => array($entry_data[$b][ $data["fieldTypeUnitTypeCol"] ]) );
					}
				}
				else {
					$searchFields[]=array("tableName" => $join_table, "fieldName" => $name, "priority" => $priority, "type" => $data_type);
				}
			}
		}
		elseif (count($virtual_tables[$join_table]["fields"])) { // zB Suche bei Anbietern
			foreach ($virtual_tables[$join_table]["fields"] as $name => $data) {
			       $thisTable=$virtual_tables[$join_table]["forTable"];
			       if (strpos($name,".")!==FALSE) {
					list($thisTable,$name)=explode(".",$name,2);
			       }
				addSearchField($searchFields,$default_priority,$thisTable,$name,$data);
			}
		}
	}
	 
	// barcode-felder scannen
	foreach ($barcodePrefixes as $prefix => $barcodePrefix) {
		$baseTable=getBaseTable($barcodePrefix["table"]);
		if (in_array($baseTable,$join_tables) && $barcodePrefix["field"]=="field" && isset($barcodePrefix["search"])) { // richtige tabelle und barcodefeld und durchsuchbar
			$default_priority++;
			if (isset($barcodePrefix["searchPriority"])) {
				$priority=-$barcodePrefix["searchPriority"]; // negativ, damit es an den Anfang kommt
			}
			else {
				$priority=$default_priority;
			}
			$type=getBarcodeFieldSearchType($prefix);
			$searchFields[]=array(
				"tableName" => $barcodePrefix["table"], 
				"fieldName" => getBarcodeFieldName($baseTable), 
				"priority" => $priority, 
				"type" => $type, 
			);
		}
	}
	// sortieren
	usort($searchFields,"sortSearchFieldsPrio");
	return $searchFields;
}

function getCritOptionsFunction($avail_tables) {
	global $searchModes,$settings,$g_settings;
	// element,type => op_iHTML
	$funcTerm="}\n}\n";
	$opFunc="
function getOpSelect(element,type) {
switch (type) {
";
	
	// element,type => val_iHTML
	$valFunc="
function getValInput(element,type) {
switch (type) {
";
	
	// table => <options
	$critFunc="
function getCritOptions(thisTable) {
switch (thisTable) {
";
	
	if (is_array($avail_tables)) foreach ($avail_tables as $table) {
		$critFunc.="case ".fixStr($table).":\n";
		$searchFields=getSearchFields($table);
		$options="";
		if (count($searchFields)) {
			for ($b=0;$b<count($searchFields);$b++) { // search_fields: fieldName tableName priority
				$searchField=& $searchFields[$b];
				$searchText=s($searchField["fieldName"]);
				if (empty($searchText)) {
					continue;
				}
				
				if ($searchField["fieldName"]=="molecule_auto" && !empty($g_settings["name_migrate_id_mol"])) { // MPI/BESSI special
					$searchText=$g_settings["name_migrate_id_mol"].", ".$searchText;
				}
				
				// aufbauen <select
				$options.="<option value=".fixStr($searchField["tableName"].".".$searchField["fieldNamePrefix"].$searchField["fieldName"]).">".$searchText;
				$typeDict[ $searchField["tableName"].".".$searchField["fieldNamePrefix"].$searchField["fieldName"] ]=$searchField["type"];
				$allowedClassesDict[ $searchField["tableName"].".".$searchField["fieldNamePrefix"].$searchField["fieldName"] ]=$searchField["allowedClasses"];
				//~ $field_types_unique[]=$searchField["type"];
			}
		}
		$critFunc.="return ".fixStr($options).";
break;\n";
	}
	$critFunc.=$funcTerm;
	
	//~ $field_types_unique=array_unique($field_types_unique);
	$searchModesKeys=array_keys($searchModes);
	if (is_array($searchModesKeys)) foreach ($searchModesKeys as $type) {
		$opFunc.="case ".fixStr($type).":\n".getOpSelect($type)."\nbreak;\n";
		$valFunc.="case ".fixStr($type).":\n".getValInput($type)."\nbreak;\n";
	}
	$opFunc.=$funcTerm;
	$valFunc.=$funcTerm;
	
	// crit => type
	$typeFunc="
function getCritType(thisCrit) {
	var typeDict=".json_encode($typeDict).";
	return a(typeDict,thisCrit);
}

function getAllowedClasses(thisCrit) {
	var allowedClassesDict=".json_encode($allowedClassesDict).";
	return a(allowedClassesDict,thisCrit);
}

function loadTemplates(domObj) {\nvar frameDoc=getApplet(domObj.id,\"VectorMol\");\n".getTemplateLoaderJS($g_settings["applet_templates"]).getTemplateLoaderJS($settings["applet_templates"])."\n}\n";
	return $critFunc.$typeFunc.$opFunc.$valFunc;
}
	
function getQueryPartInputs($table) { // PHP
	
	return getCritOptionsFunction(array($table));
}

?>