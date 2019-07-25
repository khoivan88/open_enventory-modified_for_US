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

function getViewHelper($table) {
	global $edit_views,$view_controls,$view_ids;
	if (is_array($edit_views[$table])) {
		$view_names=array_keys($edit_views[$table]);
		if (!in_array($_REQUEST["view"],$view_names)) {
			$_REQUEST["view"]=$view_names[0];
		}
	}
	echo "var currentView=".fixStr($_REQUEST["view"]).";
edit_views=".json_encode($edit_views[$table]).";
view_controls=".json_encode($view_controls[$table]).";
view_ids=".json_encode($view_ids[$table]).";\n";
}

function getViews($table,$listMode=true) { //  Listen- und Detailansicht => Listenansichten
	// switch to tabs
	//~ global $views;
	global $g_settings,$settings;
	$views=arr_merge($g_settings["views"][$table],$settings["views"][$table]);
	//~ $retval.="<div class=\"tab_container\">";
	// custom
	if (count($views)) {
		if (!$listMode && !empty($_REQUEST["fields"])) {
			$retval.=getTabLink(array(
				"url" => "javascript:activateView()", 
				"text" => s("view_list"), 
				"class" => "tab_ext", 
			));
		}
		foreach ($views as $name => $col) {
			// control for views
			$class="tab_light";
			$url="javascript:activateView(".fixQuot($col).")";
			$text=s($name);
			if (empty($text)) {
				$text=$name;
			}
			unset($id);
			if (!$listMode) {
				$class="tab_ext";
			}
			elseif ($col==$_REQUEST["fields"]) {
				$class="tab_selected";
				$url="javascript:activateSelfView()";
				$id="activeView";
			}
			$retval.=getTabLink(array(
				"url" => $url, 
				"text" => $text, 
				"class" => $class, 
				"id" => $id, 
			));
			/*$retval.=" <select name=\"selectView\" id=\"selectView\" onChange=\"activateView(this.value)\" class=\"noprint\"><option value=\"\">".s("select_view");
			foreach ($views[$table] as $name=>$col) {
				$retval.="<option value=\"".$col."\">".s($name);
			}
			$retval.="</select>";*/
		}
	}
	elseif (!$listMode) { // dummy entry for design
		$retval.=getTabLink(array(
			"url" => "javascript:activateView()", 
			"text" => s("view_list"), 
			"class" => "tab_ext", 
		));
	}
	else { // dummy entry for design
		$retval.=getTabLink(array(
			"url" => "javascript:activateSelfView()", 
			"text" => s("view_list"), 
			"class" => "tab_selected", 
			"id" => "activeView", 
		));
	}
	//~ $retval.="<br clear=\"all\">";
	//~ $retval.="</div>";
	return $retval;
}

function getEditViewTabs($table) { // Detailansicht => Detailansichten
	global $edit_views;
	if (is_array($edit_views[$table])) foreach ($edit_views[$table] as $name => $data) {
		if (empty($data["text"])) {
			$text=s($name);
		}
		else {
			$text=$data["text"];
		}
		$retval.=getTabLink(array("id" => "view_".$name, "url" => "javascript:activateEditView(".fixQuot($name).")", "text" => $text, "class" => "tab_light"));
	}
	else {
		$retval.=getTabLink(array("id" => "view_default", "url" => "javascript:void (0)", "text" => s("view_edit"), "class" => "tab_selected"));
	}
	return $retval;
}

function getExtTabs($table) {
	global $edit_links;
	if (is_array($edit_links[$table])) foreach ($edit_links[$table] as $link) { // ersten oder aktive highlighten
		if (!isset($link["class"])) {
			$link["class"]="tab_ext";
		}
		$link["text"]=getControlText($link);
		$retval.=getTabLink($link);
	}
	return $retval;
}

function getListEditViewTabs($table,$db_id=null,$pk=null) { // Listenansicht => Detailansichten
	global $edit_views;
	$params=getSelfRef(array("~script~","db_id","pk"));
	if (!is_null($db_id) && !is_null($pk)) {
		$params.="&db_id=".$db_id."&pk=".$pk;
	}
	if (is_array($edit_views[$table])) foreach (array_keys($edit_views[$table]) as $name) {
		if (empty($edit_views[$table][$name]["text"])) {
			$text=s($name);
		}
		else {
			$text=$edit_views[$table][$name]["text"];
		}
		$retval.=getTabLink(array("id" => "view_".$name, "url" => "edit.php?".$params."&view=".$name, "text" => $text, "class" => "tab_ext"));
	}
	else {
		$retval.=getTabLink(array("id" => "view_default", "url" => "edit.php?".$params, "text" => s("view_edit"), "class" => "tab_ext"));
	}
	return $retval;
}

function getTabLink($paramHash) { // available classes: tab_light,tab_ext,tab_selected
	// Anzeige eines Links an der Seite
	$text=$paramHash["text"];
	if (isset($paramHash["target"])) {
		$targetText=" target=".fixStr($paramHash["target"]);
	}
	if (isset($paramHash["id"])) {
		$idText=" id=".fixStr($paramHash["id"]);
	}
	if (isset($paramHash["onMouseover"])) {
		$mouseoverText=" onMouseover=".fixStr($paramHash["onMouseover"]);
	}
	if (isset($paramHash["onMouseout"])) {
		$mouseoutText=" onMouseout=".fixStr($paramHash["onMouseout"]);
	}
	return "<td><a class=".fixStr($paramHash["class"]).($paramHash["hide"]?" style=\"display:none\"":"")." href=".fixStr($paramHash["url"]).$idText.$mouseoverText.$mouseoutText.$targetText."><span>".$text."</span></a></td>";
}


?>