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

function reIndex(& $arr,$keys,$nameField) {
	foreach ($keys as $idx => $key) {
		$arr[$idx]=$arr[$key];
		unset($arr[$key]);
		$arr[$idx][$nameField]=$key;
	}
}

function getSubitemlistObject($list_int_name,$fields,$index=null) { // für settings und global settings
	return getSubitemlistObject0($list_int_name,$fields,$index,false);
}

function getSubitemlistObjectRaw($list_int_name,$fields,$index=null) {
	return getSubitemlistObject0($list_int_name,$fields,$index,true);
}

function selectiveStrip($html,$allowSomeHtml) {
	if ($allowSomeHtml) {
		return makeHTMLSafe($html);
	}
	else {
		return strip_tags($html);
	}
}

function getSubitemlistObject0($list_int_name,$fields,$index,$allowSomeHtml) {
	$retval=array();
	if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $no => $UID) {
		$item=array();
		if (is_array($fields) && count($fields)) {
			foreach ($fields as $field) {
				$item[$field]=selectiveStrip(getValueUID($list_int_name,$UID,$field),$allowSomeHtml);
			}
		}
		else { // string
			$item=selectiveStrip(getValueUID($list_int_name,$UID,$fields),$allowSomeHtml);
		}
		if (is_null($index)) {
			$useIdx=$no;
		}
		else {
			$useIdx=strip_tags(getValueUID($list_int_name,$UID,$index));
		}
		$retval[$useIdx]=$item;
	}
	return $retval;
}

function breakArray($arr) {
	return str_replace(array(",","\n","\r"),array(","),$arr);
}

function unbreakArray($arr) {
	return str_replace(array(" ","\n","\r"),"",$arr);
}

// global-------------------------------------------------------------------------------------------
function applyImgSetting($names) {
	global $g_settings;
	applySettings($g_settings,$names,array("url","target","src","w","h","b"));
}

function applySettings(& $arr,$names,$keys) {
	foreach ($names as $name) {
		foreach ($keys as $key) {
			$arr[$name][$key]=$_REQUEST[$name."_".$key];
		}
	}
}

function applyLinkSetting($names) {
	global $g_settings;
	$g_settings["links"]=array();
	foreach ($names as $name) {
		$a=0;
		while (!empty($_REQUEST["link_".$name."_".$a."_url"])) {
			$g_settings["links"][$name][$a]["text"]=$_REQUEST["link_".$name."_".$a."_text"];
			$g_settings["links"][$name][$a]["url"]=$_REQUEST["link_".$name."_".$a."_url"];
			$g_settings["links"][$name][$a]["target"]=$_REQUEST["link_".$name."_".$a."_target"];
			$a++;
		}
	}
}

$customAnalyticsTabs=array(
	"item" => "subitemlist", 
	"int_name" => "customAnalyticsTabs", 
	"directDelete" => true, 
	"allowReorder" => true, 
	"fields" => array(
		array("item" => "cell"), 
		array("item" => "input", "int_name" => "key", ),
		array("item" => "cell"), 
		array("item" => "input", "int_name" => "analytics_type_name", ),
		array("item" => "cell"), 
		array("item" => "input", "int_name" => "text", "size" => 24, ),
		
		array("item" => "cell"), 
		array("item" => "check", "int_name" => "showRxn", "value" => 1, ),
		array("item" => "cell"), 
		array("item" => "check", "int_name" => "mixtureOnly", "value" => 1, ),

		array("item" => "cell"), 
		array("item" => "links", ), 
	), 
);

$appletTemplates=array(
	"item" => "subitemlist", 
	"int_name" => "applet_templates", 
	"directDelete" => true, 
	"allowReorder" => true, 
	"lineInitFunction" => "addMoleculeToUpdateQueue(list_int_name,UID,\"molfile_blob\"); ", 
	"fields" => array(
		array("item" => "cell"), 
		array(
			"item" => "structure", 
			"int_name" => "molfile_blob", 
			"mode" => "tmpl", 
			"force" => "VectorMol", // only IMES and VectorMol can handle ATT lines
			"showEditButton" => true, 
			"showDelButton" => false, 
			"showGifButton" => false, 
			"showMolfileButton" => false, 
			"showCopyPasteButton" => true, 
			"height" => rc_gif_y, 
			"width" => rc_gif_x, 
			"autoUpdate" => true, 
		), 
		array("item" => "cell"), 
		array("item" => "input", "int_name" => "template_name", "size" => 12, ),
		array("item" => "cell"), 
		array("item" => "input", "int_name" => "template_shortcuts", "size" => 24, ),
	), 
);

$viewsTemplate=array(
	"item" => "subitemlist", 
	"directDelete" => true, 
	"allowReorder" => true, 
	"onAddLine" => "editCustomList(list_int_name,UID); ", 
	"fields" => array(
		array("item" => "cell"), 
		array("item" => "input", "int_name" => "key", DEFAULTREADONLY => "always", ),
		array("item" => "cell"), 
		array("item" => "input", "int_name" => "localized_fields", DEFAULTREADONLY => "always", ),
		array("item" => "hidden", "int_name" => "fields", ),
		//~ array("item" => "input", "int_name" => "fields", "type" => "textarea", "cols" => 40, ),
		array("item" => "cell"), 
		array("item" => "links", ), 
		array("item" => "button", "onClick" => "editCustomList", "class" => "imgButtonSm", "img" => "lib/details_sm.png", ),
	), 
);
$views_molecule=$viewsTemplate;
$views_molecule["int_name"]="views_molecule";
$views_molecule["table"]="molecule";
$views_chemical_storage=$viewsTemplate;
$views_chemical_storage["int_name"]="views_chemical_storage";
$views_chemical_storage["table"]="chemical_storage";
?>