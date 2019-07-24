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

require_once "lib_db_query.php";
require_once "lib_form_elements_helper.php";

function getTooltipP($text) {
	return " title=\"".$text."\" alt=\"".$text."\"";
}

function getTooltip($lang_key,$slashes=false) {
	$text=s($lang_key);
	return addSc(getTooltipP($text),$slashes);
}

function simpleHidden($name) {
	return "<input type=\"hidden\" name=".fixStr($name)." value=".fixStr($_REQUEST[$name]).">";
}

function getSimpleNotifyFunc($onChange,$additional=""){ // diese onCHange-Befehle sorgen dafr, da bei nderungen gespeichert wird und sonst nur entsperrt
	if (strlen($onChange)>0 && substr($onChange,-1,1)!=";") {
		$onChange.=";";
	}
	return "if (valChanged(this)) {".$onChange."}".ifnotempty(";",$additional);
}

function showBr() {
	return "<br>";
}

function getHiddenSubmit() {
	//~ return "<input type=\"submit\" style=\"display:none\">";
	return "<input type=\"submit\" style=\"position:absolute;top:-1000px;left:-1000px\">"; // fixing a chrome bug (not accepting display:none)
}

function transParams($names) {
	if (is_array($names)) foreach ($names as $name) {
		$retval.=transParam($name);
	}
	return $retval;
}

function transParam($name) {
	return showHidden(array(
		"int_name" => $name, 
		"value" => $_REQUEST[$name], 
	));
}

function showHidden($paramHash) { // onChange geht nicht
	if (is_string($paramHash)) {
		$paramHash=array("int_name" => $paramHash);
	}
	$int_name=& $paramHash["int_name"];
	$allowLock=($paramHash["allowLock"]!==FALSE?true:false);

	$retval.="<input type=\"hidden\" ".getNameId($int_name)." value=".fixStr($paramHash["value"]).($allowLock?"":" allowLock=\"false\"").">";
	return $retval;
}

function showGroup($langKey,$hierarchy) {
	return "<h".$hierarchy.">".s($langKey)."</h".$hierarchy.">";
}

function showInput($paramHash) {
	$int_name=& $paramHash["int_name"];
	$allowLock=($paramHash["allowLock"]!==FALSE?true:false);
	$type=ifempty($paramHash["type"],"text");
	
	$onChange=$paramHash["onChange"];
	$noChangeEffect=($paramHash["noChangeEffect"]?true:false);
	if (!$noChangeEffect) {
		$onChange=getSimpleNotifyFunc($onChange);
	}
	
	$clearbutton=($paramHash["clearbutton"]?true:false);

	$text=getControlText($paramHash);
	
	if (!$allowLock) {
		$allowLockText=" allowLock=\"false\"";
	}
	$classText=getClass($paramHash);
	if ($paramHash["noAutoComp"]) {
		$noAutoCompText=" autocomplete=\"off\"";
	}
	
	$typeText="text";
	if ($type=="password") {
		$typeText="password";
	}

	$retval="<label id=".fixStr("rw_".$int_name)." for=".fixStr($int_name).$allowLockText.$classText.">".$text."&nbsp;<input type=".fixStr($typeText).
		getNameId($int_name).
		makeHTMLParams($paramHash,array("size","maxlength","value"),array(40)).
		$allowLockText.
		$classText.
		$noAutoCompText.">".$paramHash["editHelp"]."</label>";
	if ($type=="password") {
		$retval.="<label for=\"".$int_name."_repeat\"".$allowLockText.">".s("repeat")."&nbsp;<input type=\"password\" id=".fixStr($int_name."_repeat")." name=".fixStr($int_name."_repeat").$allowLockText.$classText.$noAutoCompText."></label>\n";
	}
	if ($clearbutton) {
		$retval.="<a href=\"Javascript:clearControl(&quot;".$int_name."&quot;)\"><img src=\"lib/del.png\" width=\"16\" height=\"17\" border=\"0\" style=\"vertical-align:middle\"></a>";
	}
	return $retval;
}

function showCheck($paramHash) {
	$int_name=& $paramHash["int_name"];
	$allowLock=($paramHash["allowLock"]!==FALSE?true:false);
	
	$onChange=$paramHash["onChange"];
	$noChangeEffect=($paramHash["noChangeEffect"]?true:false);
	if (!$noChangeEffect) {
		$onChange=getSimpleNotifyFunc($onChange);
	}

	$text=getControlText($paramHash);
	
	if (!$allowLock) {
		$allowLockText=" allowLock=\"false\"";
	}
	$classText=getClass($paramHash);
	$onChangeText=" onChange=\"".$onChange."\"";

	$retval.="<label id=".fixStr("rw_".$int_name)." for=".fixStr($int_name).$allowLockText.$classText."><nobr><input type=\"checkbox\" ".getNameId($int_name)." value=\"1\"".($paramHash["value"]?" checked=\"checked\"":"").$onChangeText.$allowLockText.$classText.">&nbsp;".$text."</nobr></label>\n";
	return $retval;
}

function showSelect($paramHash) {
	$int_name=& $paramHash["int_name"];
	$texts=& $paramHash["texts"]; // Array
	$allowLock=($paramHash["allowLock"]!==FALSE?true:false);
	
	$onChange=$paramHash["onChange"];
	$noChangeEffect=($paramHash["noChangeEffect"]?true:false);
	if (!$noChangeEffect) {
		$onChange=getSimpleNotifyFunc($onChange);
	}
	
	// calc int_names from langKeys
	if (!isset($paramHash["int_names"])) { // mit Zahlen fr ENUM fllen
		$paramHash["int_names"]=range(1,count($paramHash["langKeys"]));
	}
	
	if (!isset($paramHash["texts"])) {
		$paramHash["texts"]=array();
		foreach ($paramHash["int_names"] as $a) { // allow holes while texts are still correctly assigned
			if (is_array($paramHash["langKeys"])) {
				$paramHash["texts"][]=s($paramHash["langKeys"][$a-1]);
			}
			else {
				$paramHash["texts"][]=s($a);
			}
		}
	}

	$texts=& $paramHash["texts"]; // Array
	$int_names=& $paramHash["int_names"];
	
	if ($paramHash["allowDefault"]) {
		array_unshift($int_names,"-1");
		array_unshift($texts,s("default"));
	}

	$text=getControlText($paramHash);

	if (!$allowLock) {
		$allowLockText=" allowLock=\"false\"";
	}
	$classText=getClass($paramHash);
	$onChangeText=" onChange=\"".$onChange."\"";
	
	// rw-teil
	if ($paramHash["radioMode"]) {
		for ($a=0;$a<count($int_names);$a++) {
			$retval.="<input type=\"radio\" name=".fixStr($int_name)." id=".fixStr($int_names[$a])." value=".fixStr($int_names[$a]).$onChangeText.$allowLockText.$classText.($int_names[$a]==$paramHash["value"]?" checked=\"checked\"":"")."><label for=".fixStr($int_names[$a]).$allowLockText.$classText.">".removeWbr($texts[$a])."</label><br>";
		}
	}
	else {
		$retval="<label id=".fixStr("rw_".$int_name)." for=".fixStr($int_name).$allowLockText.$classText.">".$text."&nbsp;<select ".getNameId($int_name).$onChangeText.$allowLockText.$classText.">";
		for ($a=0;$a<count($int_names);$a++) {
			$retval.="<option value=".fixStr($int_names[$a]).($int_names[$a]==$paramHash["value"]?" selected=\"selected\"":"")." title=".fixStr(removeWbr($texts[$a])).">".removeWbr($texts[$a]);
		}
		$retval.="</select></label>";
	}
	return $retval;
}

function showAnchor($paramHash) {
	return "<a name=".$paramHash["int_name"]."></a>";
}

function getDataCheckbox($name,$data) {
	return "<input type=\"checkbox\" name=".fixStr($name)." value=\"".htmlspecialchars(serialize($data))."\">";
}

function showDBSelect($paramHash) {
	$int_name=& $paramHash["int_name"];
	$texts=& $paramHash["texts"]; // Array
	$allowLock=($paramHash["allowLock"]!==FALSE?true:false);
	
	$onChange=$paramHash["onChange"];
	$noChangeEffect=($paramHash["noChangeEffect"]?true:false);
	if (!$noChangeEffect) {
		$onChange=getSimpleNotifyFunc($onChange);
	}
	
	pk_select_getList($paramHash);
	
	$text=getControlText($paramHash);

	if (!$allowLock) {
		$allowLockText=" allowLock=\"false\"";
	}
	$classText=getClass($paramHash);
	$onChangeText=" onChange=\"".$onChange."\"";
	
	$retval="<label id=".fixStr("rw_".$int_name)." for=".fixStr($int_name).$allowLockText.$classText.">".$text."&nbsp;<select ".getNameId($int_name).$onChangeText.$allowLockText.$classText.">";
	
	for ($a=0;$a<count($paramHash["int_names"]);$a++) {
		if (isset($paramHash["value"])) {
			$checkText=($paramHash["int_names"][$a]==$paramHash["value"]?" selected=\"selected\"":"");
		}
		if (!isset($paramHash["pk_exclude"]) || $paramHash["int_names"][$a]!=$paramHash["pk_exclude"]) {
			$retval.="<option value=".fixStr($paramHash["int_names"][$a]).$checkText.">".removeWbr($paramHash["texts"][$a]);
		}
	}
	$retval.="</select></label>";
	
	return $retval;
}

function showLanguageSelect($paramHash) { // nur für login
	global $localizedString;
	$int_name=& $paramHash["int_name"];

	$text=getControlText($paramHash);

	$int_names=array_keys($localizedString);
	$texts=array();
	
	for ($a=0;$a<count($int_names);$a++) {
		array_push($texts,$localizedString[$int_names[$a]]["language_name"]);
	}
	if ($paramHash["allowDefault"]) {
		array_unshift($int_names,"-1");
		array_unshift($texts,s("standard_language"));
	}
	$classText=getClass($paramHash);
	
	$retval=$text."&nbsp;<select ".getNameId($int_name).$classText.">";
	for ($a=0;$a<count($int_names);$a++) {
		$retval.="<option value=\"".$int_names[$a]."\"".($int_names[$a]==$paramHash["value"]?"selected=\"selected\"":"").">".$texts[$a];
	}
	$retval.="</select>";
	return $retval;
}

function loadJS($filenames,$prefix="") {
	if (!is_array($filenames)) {
		$filenames=array($filenames);
	}
	$retval="";
	for ($a=0;$a<count($filenames);$a++) {
		if (empty($filenames[$a])) {
			continue;
		}
		if ($filenames[$a]=="json2.js" && isFF3x()) { 
			continue;
		}
		$filename=$prefix.$filenames[$a];
		//~ if (endswith($filename,".js")) {
			//~ $filename.=".gz";
		//~ }
		$retval.="<script language=\"JavaScript\" src=".fixStr($filename)." type=\"text/javascript\"></script>\n";
	}
	return $retval;
}

function getTableCell($value) {
	return "<td>".$value."</td>";
}

function getTableLine($line) {
	if (!count($line)) {
		return;
	}
	$retval="<tr>";
	foreach ($line as $value) {
		$retval.=getTableCell($value);
	}
	$retval.="</tr>";
	return $retval;
}

function getTable($table,$headline=array()) {
	if (!count($table)) {
		return;
	}
	// sync sizes
	$max=count($headline);
	foreach ($table as $line) {
		$max=max($max,count($line));
	}
	foreach ($table as $idx => $line) {
		for ($a=count($line);$a<$max;$a++) {
			$table[$idx][$a]="";
		}
	}
	
	$retval="<table class=\"listtable\">";
	if (count($headline)) {
		$retval.="<thead><tr>";
		for ($a=0;$a<$max;$a++) { // if too short, cells will be empty
			$retval.=getTableCell($headline[$a]);
		}
		$retval.="</tr></thead>";
	}
	$retval.="<tbody>";
	foreach ($table as $line) {
		$retval.=getTableLine($line);
	}
	$retval.="</tbody></table>";
	return $retval;
}
?>