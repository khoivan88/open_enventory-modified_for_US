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
require_once "lib_simple_forms.php";
require_once "lib_applet.php";
require_once "lib_form_elements_helper.php";
require_once "lib_form_elements_subitemlist.php";

function getNotifyFunc($onChange,$additional=""){ // diese onCHange-Befehle sorgen dafr, da bei nderungen gespeichert wird und sonst nur entsperrt
	global $editMode;
	// automatically add (int_name) if text but no brackets
	$onChange=trim($onChange);
	if (strlen($onChange)>0) {
		if (strpos($onChange,"(")===FALSE && strpos($onChange,")")===FALSE) {
			$onChange.="(this)";
		}
		if (substr($onChange,-1,1)!=";") {
			$onChange.=";";
		}
	}
	
	//~ if (!$editMode) { // not needed
		//~ return $onChange;
	//~ }
	// needed to detect if a real change has happened
	return "if (valChanged(this)) {".$onChange."}".ifnotempty(";",$additional);
}

function getNotifyFuncSelect($onChange,$additional=""){ // diese onCHange-Befehle sorgen dafr, da bei nderungen gespeichert wird und sonst nur entsperrt
	global $editMode;
	if (!$editMode) {
		return $onChange;
	}
	if (strlen($onChange)>0) {
		if (strpos($onChange,"(")===FALSE && strpos($onChange,")")===FALSE) {
			$onChange.="(this)";
		}
		 if (substr($onChange,-1,1)!=";") {
			$onChange.=";";
		}
	}
	return "valChanged(this); ".$onChange.ifnotempty(";",$additional);
}

function showCommFrame($paramHash=array()) {
	$url=ifempty($paramHash["url"],"blank.php");
	if ($paramHash["debug"] || $_REQUEST["debug"]=="true") {
		$sizeText=" width=\"200\" height=\"200\" frameborder=\"2\"";
	}
	else {
		$sizeText=" width=\"0\" height=\"0\" style=\"display:none\" frameborder=\"0\"";
	}
	$name=ifempty($paramHash["name"],"comm");
	echo "<iframe src=".fixStr($url)." name=".fixStr($name)." id=".fixStr($name).$sizeText."></iframe>\n";
}

/*function get_db_id_select($paramHash) { // erstmal deakt
	global $other_db_data;
	if ($paramHash["editMode"]) {
		return array(
			"item" => "hidden", 
			"int_name" => "db_id", 
		);
	}
	else {
		$int_names=array(-1);
		$texts=array(s("own_database"));
		for ($a=0;$a<count($other_db_data);$a++) {
			$db_id=$other_db_data[$a]["other_db_id"];
			if ($paramHash["mayWrite"][$db_id]) {
				continue;
			}
			$int_names[]=$db_id;
			$texts[]=$other_db_data[$a]["db_beauty_name"];
		}
		return array(
			"item" => "select", 
			"int_name" => "db_id", 
			"int_names" => $int_names, 
			"texts" => $texts, 
		);
	}
}*/

function getFormElements($paramHash,$elements) {
	global $g_settings;
	// $paramHash[READONLY]=false;
	// handle params
	// create fieldset, legend
	prepareControl($paramHash);
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"form");
	
	if (!isset($paramHash["noInputHighlight"]) && $g_settings["highlight_inputs"]) {
		$paramHash["noInputHighlight"]=true;
	}
	
	if (!isset($paramHash[READONLY])) {
		$paramHash[READONLY]=true;
	}
	if (!isset($paramHash[VISIBLE])) {
		$paramHash[VISIBLE]=true;
	}
	if (!empty($paramHash["onBeforeLoad"])) {
		$retval.=script.$paramHash["onBeforeLoad"]._script;
	}
	if ($paramHash["noFieldSet"]) {
		$retval.="<div id=\"".$paramHash["int_name"]."_FS\" style=\"display:".($paramHash[VISIBLE]?"":"none")."\" onDblclick=\"dblClickStartEditMode()\">";
	}
	else {
		$retval.="<fieldset id=\"".$paramHash["int_name"]."_FS\" style=\"display:".($paramHash[VISIBLE]?"":"none")."\" onDblclick=\"dblClickStartEditMode()\"><legend class=\"noprint\">".$paramHash["text"]."</legend>";
	}
	
	// fields for db_id and pk
	$pkName=getShortPrimary($paramHash["int_name"]);
	if (!$paramHash["no_db_id_pk"] && $pkName) {
		$elements=array_merge(array(
		array("item" => "hidden", "int_name" => "db_id", ), 
		array("item" => "hidden", "int_name" => $pkName), 
		array("item" => "db_name", "int_name" => "show_db_beauty_name") 
		), $elements);
	}
	
	$paramHashFiltered=array_key_filter($paramHash,array(READONLY,VISIBLE,LOCKED,TABLEMODE,"class","noInputHighlight","prefix")); // das wird vererbt
	$loadBlind=array();
	
	for ($a=0;$a<count($elements);$a++) {
		if (!is_array($elements[$a])) {
			// "br", etc
			$elements[$a]=array("item" => $elements[$a]);
		}
		// set standard parameters, inherit
		if ($elements[$a]["skip"]) {
			continue;
		}
		
		// loadBlind
		if (($elements[$a]["loadBlind"] || $elements[$a]["additionalField"]) && !empty($elements[$a]["int_name"])) {
			$loadBlind[]=$elements[$a]["int_name"];
		}
		
		$elements[$a]=array_merge($paramHashFiltered,$elements[$a]); // Vererbung bestimmter Eigenschaften
		
		if ($elements[$a]["additionalField"]) { // Liste der zusätzlichen Felder, für lib_db_manip
			$additionalFields.=showHidden(array("int_name" => "additionalFields[]", "value" => $elements[$a]["int_name"]));
		}
		
		if ($elements[$a][SPLITMODE]) { // splitMode handled elsewhere
			continue;
		}
		elseif ($elements[$a]["freeMode"]==true) { // HTML für freeControls zwischenlagern
			list($roInput,$rwInput)=getSplitControl($elements[$a]);
			$paramHashFiltered["freeControls"][ $elements[$a]["int_name"] ]["ro"]=$roInput;
			$paramHashFiltered["freeControls"][ $elements[$a]["int_name"] ]["rw"]=$rwInput;
			getRegisterControls($controls,$registerControls,$elements[$a]);
			continue;
		}
		
		// sind die nachfolgenden Elemente splitMode?
		// bis zum ersten nicht-splitMode gehen
		for ($b=$a+1;$b<count($elements);$b++) {
			if (!is_array($elements[$b])) {
				break;
			}
			if ($elements[$b]["skip"]) {
				continue;
			}
			elseif (!$elements[$b][SPLITMODE]) {
				break;
			}
			
			$elements[$b]=array_merge($paramHashFiltered,$elements[$b]); // Vererbung bestimmter Eigenschaften, auch den aktuellen tableMode!!
			
			// get text and add prefix to int_name
			prepareControl($elements[$b]);
			
			if ($elements[$b]["item"]=="subitemlist") { // subitemlists only
				list($roInput,$rwInput)=getSubitemList($elements[$b]);
				$elements[$a]["roInputs"].=$roInput;
				$elements[$a]["rwInputs"].=$rwInput;
				getRegisterControls($controls,$registerControls,$elements[$b]);
			}
			elseif (in_array($elements[$b][TABLEMODE],array("h","hl"))) {
				list($roInput,$rwInput)=getSplitControl($elements[$b]);
				$elements[$a]["roInputs"].=" ".$roInput; // wird dann in Zelle mit ausgegeben
				$elements[$a]["rwInputs"].=$rwInput;
				getRegisterControls($controls,$registerControls,$elements[$b]);
			}
		}
		
		// get text and add prefix to int_name
		prepareControl($elements[$a]);
		
		switch ($elements[$a]["item"]) {
		case "anchor":
			$retval.=getAnchor($elements[$a]);
		break;
		case "applet":
			$retval.=getApplet($elements[$a]);
		break;
		case "br":
			$retval.=getBr();
		break;
		case "input":
			$retval.=getInput($elements[$a]);
		break;
		case "hidden":
			$retval.=getHidden($elements[$a]);
		break;
		case "check":
		case "checkbox":
			$retval.=getCheck($elements[$a]);
		break;
		case "checkset":
			$retval.=getCheckSet($elements[$a]);
		break;
		case "group":
			$retval.=getGroup($elements[$a]);
		break;
		case "js":
			$retval.=getJS($elements[$a]);
		break;
		case "language":
			$retval.=getLanguageSelect($elements[$a]);
		break;
		case "pk":
			$retval.=getPk($elements[$a]);
		break;
		case "pk_select":
			$retval.=getDBSelect($elements[$a]);
		break;
		case "sds":
			$retval.=getSDS($elements[$a]);
		break;
		case "select":
			$retval.=getSelect($elements[$a]);
		break;
		case "structure":
			$retval.=getStructure($elements[$a]);
		break;
		case "subitemlist":
			$retval.=getSubitemList($elements[$a]);
		break;
		case "tableStart":
			$retval.=getTableStart($elements[$a]);
			$paramHashFiltered[TABLEMODE]=ifempty($elements[$a][TABLEMODE],"h");
		break;
		case "tableEnd":
			$retval.=getTableEnd($elements[$a]);
			$paramHashFiltered[TABLEMODE]="";
		break;
		case "tableWrap":
			$retval.=getTableWrap($elements[$a]);
		break;
		case "text":
			$retval.=getFormText($elements[$a]);
		break;
		}
		getRegisterControls($controls,$registerControls,$elements[$a]);
	}
	
	// register form in forms
	// register controls in controls
	$retval.=$additionalFields;
	if ($paramHash["noFieldSet"]) {
		$retval.="</div>";
	}
	else {
		$retval.="</fieldset>";
	}
	
	$retval.=script."
page_forms.push(".fixStr($paramHash["int_name"]).");
formulare[".fixStr($paramHash["int_name"])."]=".json_encode($paramHash).";\n"
.getFormFunctions($paramHash)."
formulare[".fixStr($paramHash["int_name"])."][\"change\"]={};";
	
	if (is_array($paramHash["afterChange"])) foreach($paramHash["afterChange"] as $propertyName => $functionBody) {
		$retval.="\nformulare[".fixStr($paramHash["int_name"])."][\"afterChange\"][".fixStr($propertyName)."]=function(thisValue) {".$functionBody." return true;};";
	}
	
	if (is_array($paramHash["change"])) foreach($paramHash["change"] as $propertyName => $functionBody) {
		$retval.="\nformulare[".fixStr($paramHash["int_name"])."][\"change\"][".fixStr($propertyName)."]=function(thisValue) {".$functionBody." return true;};";
	}
	
	$retval.="
formulare[".fixStr($paramHash["int_name"])."][\"controls\"]=".json_encode($controls).";
formulare[".fixStr($paramHash["int_name"])."][\"loadBlind\"]=".json_encode($loadBlind).";
".$registerControls."
readOnlyForm(".fixStr($paramHash["int_name"]).");
".$paramHash["onLoad"]."
"._script;
	return $retval;
}

function getGroup($paramHash) { // not table capable yet
	$int_name=& $paramHash["int_name"];
	$tableMode=& $paramHash[TABLEMODE];
	if (!empty($tableMode)) {
		$marginText="margin:0px;";
	}
	return startEl($tableMode,"ro_".$int_name,array("colspan" => 2)).
		"<h".$paramHash["hierarchy"]." style=".fixStr($marginText).">".s($int_name)."</h".$paramHash["hierarchy"].">".
		endEl($tableMode,"ro_".$int_name);
}

function getJs($paramHash) {
	$int_name=& $paramHash["int_name"];
	$tableMode=& $paramHash[TABLEMODE];
	$noLabel=($paramHash["text"]==="");
	
	if ($noLabel) {
		$retval.=startEl($tableMode,"ro_".$int_name,array("colspan" => 2));
	}
	else {
		$retval.=startEl($tableMode,"ro_".$int_name)
		."<b>".$paramHash["text"].":</b>"
		.middleEl($tableMode);
	}
	$retval.="<span id=".fixStr($int_name)."></span>".endEl($tableMode,"ro_".$int_name);
	
	return $retval;
}

function getAnchor($paramHash) {
	return "<a name=".$paramHash["int_name"]."></a>";
}

function getTableStart($paramHash) {
	global $singleLineTableModes;
	if (in_array($paramHash[TABLEMODE],$singleLineTableModes)) { // wie Lömi etc
		$paramHash["class"]=ifnotset($paramHash["class"],"formAlign");
		return "<table id=".fixStr($paramHash["int_name"]).getClass($paramHash)." style=\"margin:auto;display:table,inline-block\"><tbody><tr>";
	}
	else {
		$paramHash["class"]=ifnotset($paramHash["class"],"formAlign formAlignH");
		return "<table id=".fixStr($paramHash["int_name"]).getClass($paramHash)."><colgroup><col class=\"formAlignName\"><col class=\"formAlignValue\"></colgroup><tbody>";
	}
}

function getTableEnd($paramHash=array()) {
	global $singleLineTableModes;
	if (in_array($paramHash[TABLEMODE],$singleLineTableModes)) {
		return "</tr></tbody></table>";
	}
	else {
		return "</tbody></table>";
	}
}

function getTableWrap($paramHash=array()) {
	global $singleLineTableModes;
	if (in_array($paramHash[TABLEMODE],$singleLineTableModes)) {
		return "</tr><tr>";
	}
	else {
		return "";
	}
}

function getSDS(& $paramHash) { // onChange geht nicht
	$int_name=& $paramHash["int_name"];
	$tableMode=& $paramHash[TABLEMODE];
	$paramHash1=array("int_name" => $int_name."_url");
	$paramHash2=array("int_name" => $int_name."_by");
	$paramHash3=array("int_name" => $int_name."_mime");
	$paramHash["registerControls"].="controls[".fixStr($paramHash["int_name"]."_by")."]=".json_encode(array("real_int_name" => $paramHash["int_name"])).";\n";

	$retval=startEl($tableMode,"rw_".$int_name, array("hide" => true)).
		$paramHash["text"].
		getHidden($paramHash1).
		getHidden($paramHash2).
		getHidden($paramHash3).
		middleEl($tableMode).
		"<input id=\"".$int_name."\" type=\"button\" onClick=\"getSavedSDS(&quot;".$int_name."&quot;)\" value=\"\" style=\"display:none\"/>".
		"<button id=\"rw_".$int_name."_new\" onClick=\"getSavedSDS(&quot;".$int_name."&quot;,true)\" style=\"display:none\"><img src=\"lib/external.png\"/></button>".
		"<input type=\"button\" onClick=\"getSafetySheet(&quot;".$int_name."&quot;)\" value=".fixStr(s("upload_safety_data_sheet"))."/>".
		"<button id=\"rw_".$int_name."_del\" onClick=\"setControl(&quot;".$int_name."&quot;,{".fixQuot($int_name."_url").":&quot;+&quot;,".fixQuot($int_name."_by").":&quot;&quot;});valChanged();\" style=\"display:none\"><img src=\"lib/del.png\"/></button>".
		endEl($tableMode).
		startEl($tableMode,"ro_".$int_name).
		"<b>".
		$paramHash["text"].
		":</b>".
		middleEl($tableMode).
		"<input id=".fixStr("value_".$int_name)." type=\"button\" onClick=\"getSavedSDS(&quot;".$int_name."&quot;)\" value=\"\" style=\"display:none\">".
		"<button id=\"ro_".$int_name."_new\" onClick=\"getSavedSDS(&quot;".$int_name."&quot;,true)\" style=\"display:none\"><img src=\"lib/external.png\"/></button>".
		endEl($tableMode);
	
	return $retval;
}

function getFormText(& $paramHash) {
	$int_name=& $paramHash["int_name"];
	$tableMode=& $paramHash[TABLEMODE];
	$text=$paramHash["text"];
	
	if (isset($paramHash["class"])) {
		$text="<span class=".fixStr($paramHash["class"]).">".$text."</span>";
	}
	// text for splitMode-controls
	if (!empty($paramHash["roInputs"]) || !empty($paramHash["rwInputs"])) {
		$retval.=startEl($tableMode,"rw_".$int_name,array("hide" => true)).
			$text.
			middleEl($tableMode).
			$paramHash["rwInputs"].
			endEl($tableMode).
			startEl($tableMode,"ro_".$int_name).
			$text.
			middleEl($tableMode).
			$paramHash["roInputs"].
			endEl($tableMode);
		
		$paramHash["showAlways"]=true;
		$paramHash["tableLabel"]=true;
	}
	else {
		// text spanning both cols
		if (isset($int_name)) {
			$retval.=startEl($tableMode,"ro_".$int_name,array("colspan" => 2));
		}
		$retval.=$text;
		if (isset($int_name)) {
			$retval.=endEl($tableMode,"ro_".$int_name);
		}
	}
	return $retval;
}

function getInput(& $paramHash) { // tableMode done
	global $lang,$g_settings;
	
	$tableMode=& $paramHash[TABLEMODE];
	$splitMode=& $paramHash[SPLITMODE]; // nur Eingabefeld, kein Text, für Einheit zu Zahl etc. 
	
	if ($splitMode) {
		$tooltipText=" title=".fixStr($paramHash["text"]);
	}
	else { // otherwise no text is shown
		$text=$paramHash["text"];
	}
	$int_name=& $paramHash["int_name"];
	
	$type=ifempty($paramHash["type"],"text");
	if ($type=="folder_browser") {
		$paramHash[SPLITMODE]=false;
		$paramHash[TABLEMODE]="";
		$paramHash["text"]=s("folder_path");
	}
	
	if (isset($paramHash["handleDisplay"])) {
		$paramHash["registerControls"].="controls[".fixStr($paramHash["int_name"])."][\"handleDisplay\"]=function(int_name,displayValue) {".$paramHash["handleDisplay"]."};\n";
	}
	
	$onChange=$paramHash["onChange"];
	$noChangeEffect=($paramHash["noChangeEffect"]?true:false);
	if (!$noChangeEffect) {
		$onChange=getNotifyFunc($onChange);
	}
	if (isset($paramHash["onMouseover"])) { // bei vielen anderen events spielt sowieso nur rw eine rolle
		$onMouseoverText=" onMouseover=\"".$paramHash["onMouseover"]."(this,".fixQuot($int_name).",false)\"";
		$onMouseoverText_readOnly=" onMouseover=\"".$paramHash["onMouseover"]."(this,".fixQuot($int_name).",true)\"";
	}
	if (isset($paramHash["onMouseout"])) {
		$onMouseoutText=" onMouseout=\"".$paramHash["onMouseout"]."(this,".fixQuot($int_name).",false)\"";
		$onMouseoutText_readOnly=" onMouseout=\"".$paramHash["onMouseout"]."(this,".fixQuot($int_name).",true)\"";
	}
	
	if ($paramHash["noAutoComp"]) {
		$noAutoCompText=" autocomplete=\"off\"";
	}
	$clearbutton=($paramHash["clearbutton"]?true:false);

	if (isset($paramHash["value"])) { // rarely used, e.g. for from_person
		$valueText=" value=".fixStr($paramHash["value"]);
	}
	
	$paramHash["class"]=ifempty($paramHash["class"],"trans");
	
	$classTextRw=getClass($paramHash,false);
	$classTextRo=getClass($paramHash,true);
	
	$tabText=getTab($paramHash["tab"]);
	
	if (in_array($type,array("round","combo"))) {
		$onChange="controlChanged(&quot;".$int_name."&quot;); ".$onChange;
	}
	
	$onFocusText=$paramHash["onFocus"];
	$onFocusText.="fC(event,this); ";
	if (!$paramHash["noInputHighlight"]) {
		$onFocusText.="hi(this,true);";
		$onBlurText.="hi(this,false);";
	}
	
	$onBlurText.=$onChange;
	
	$onChangeText=" onFocus=".fixStr($onFocusText)." onBlur=".fixStr($onBlurText)." onKeyUp=".fixStr($onChange);

	if ($paramHash["doEval"]) {
		$onChangeText.=" onChange=\"".ifempty($paramHash["evalFunction"],"controlEval")."(&quot;".$int_name."&quot;)\"";
	}
	
	if ($type=="password")  {
		$inpType="password";
		if (is_numeric($paramHash["tab"])) {
			$tabText2=getTab($paramHash["tab"]+1);
		}
	}
	elseif (in_array($type,array("round","combo"))) {
		$inpType="hidden";
	}
	elseif ($type=="file") {
		$inpType="file";
	}
	else {
		$inpType="text";
	}
	
	// multi-change
	if ($paramHash["multiChange"]) {
		$multiCheckText=getMultiCheck($int_name);
	}
	
	// roInput
	$roText="<span id=".fixStr("value_".$int_name).
		$classTextRo.
		$onMouseoverText_readOnly.
		$onMouseoutText_readOnly.">".
		$paramHash["value"].
		"</span>";
	
	if ($type=="folder_browser") {
		$rwInput.=getHidden($paramHash); // hidden
	}
	else {
		$roInput.=$roText;
	}
	
	// rwInput
	if ($type=="textarea" || $type=="textarea_classic") {
		//~ $classTextRw=" class=\"wymeditor\"";
		if ($type=="textarea") {
			$paramHash["registerControls"].="make_wyzz(".fixStr($int_name).");\n";
		}
		$rwInput.="<textarea wrap=\"off\"".
			getNameId($paramHash).
			makeHTMLParams($paramHash,array("cols","rows"),array(80,6)).
			$valueText.
			$onChangeText.
			$classTextRw.
			$tabText.
			$noAutoCompText.
			$onMouseoverText.
			$onMouseoutText.
			"></textarea>".
			$multiCheckText;
		
		if ($paramHash["br"]!==FALSE) {
			$rwInput.="<br>";
		}
	}
	elseif ($type=="folder_browser") {
		$rwInput.=$roText; // readOnly text to display path
	}
	else {
		$inputTail=makeHTMLParams($paramHash,array("size","maxlength"),array(40)).
			$tooltipText.
			$valueText.
			$onChangeText.
			$classTextRw.
			$noAutoCompText.
			$onMouseoverText.
			$onMouseoutText.
			">";
		$rwInput.="&nbsp;<input type=".fixStr($inpType).
			getNameId($paramHash).
			$tabText.
			$inputTail.
			$multiCheckText;
	}
	
	switch ($type) {
	case "date":
		$paramHash["registerControls"].="new JsDatePick({useMode:2,target:".fixStr($int_name).",dateFormat:\"%d.%m.%Y\"});\n";
	break;
	case "combo":
	case "round": // $paramHash["decimals"] steuert Zahl der Dezimalstellen
		$rwInput.="<input type=\"text\"".
			getNameId($int_name."_rounded").
			$tabText.
			$inputTail; // hier wird der gerundete Wert angezeigt, Eingaben werden in hidden übertragen
	break;
	case "percent":
		$rwInput.="%";
	break;
	case "range":
		$rwInput.="<input type=\"hidden\"".getNameId($int_name."_low")."><input type=\"hidden\"".getNameId($int_name."_high").">";
		$paramHash["registerControls"].="controls[".fixStr($paramHash["int_name"]."_high")."]=".json_encode(array("real_int_name" => $paramHash["int_name"])).";\n";
	break;
	case "password":
		$rwInput.="<label for=\"".$int_name."_repeat\">".
			s("repeat")."&nbsp;</label><input type=\"password\"".
			getNameId($int_name."_repeat").$tabText2.$inputTail;
	break;
	case "folder_browser":
	
		$paramHash["start_url"]=ifempty($paramHash["start_url"],"blank.php");
		
		// iframe searchAnalyticsFrame
		// upload int_name_file
		$rwInput.="<div id=\"FBbrowser_".$int_name."\" style=\"display:none\"><div".
			getNameId($int_name."_message").
			"></div><div>".s("btn_search")."<input id=\"FBsearch_".$int_name."\" onKeyUp=\"FBtextSearch(".fixQuot($int_name).")\"></div><iframe src=".fixStr($paramHash["start_url"]).
			getNameId($int_name."_iframe").
			makeHTMLParams($paramHash,array("width","height"),array(600,300)).
			" frameborder=\"2\"></iframe>".getBr()."</div>";
		
		if (getSetting("use_java_upload")) {
			$rwInput.="<applet".getNameId($int_name."_file")." width=\"640\" height=\"300\" mayscript=\"\" name=\"jupload\" archive=\"wjhk.jupload.jar, jakarta-commons-oro.jar, jakarta-commons-net.jar\" code=\"wjhk.jupload2.JUploadApplet\"><param value=\"main\" name=\"formdata\"/><param value=".fixStr($lang)." name=\"lang\"/><param value=\"DefaultUploadPolicy\" name=\"uploadPolicy\"/><param value=\"-1\" name=\"nbFilesPerRequest\"/><param value=\"8000000\" name=\"maxChunkSize\"/><param value=\"8000000\" name=\"maxFileSize\"/><param value=\"false\" name=\"showLogWindow\"/>Java 1.5 or higher plugin required.</applet>";
		}
		else {
			$rwInput.="<input type=\"file\"".
				getNameId($int_name."_file").
				">";
		}
	
	break;
	}
	
	if ($clearbutton && in_array($type,array("text","date","percent"))) {
		$rwInput.="<a href=\"Javascript:clearControl(".fixQuot($int_name).")\"><img src=\"lib/del.png\" width=\"16\" height=\"17\" border=\"0\" style=\"vertical-align:middle\"></a>";
	}
	elseif ($type=="combo") {
		$rwInput.="<a id=".fixStr($int_name."_button")." href=\"Javascript:toggleSelect(".fixQuot($int_name).")\">".
			"<img src=\"lib/dropdown.png\" width=\"19\" height=\"19\" border=\"0\" style=\"vertical-align:middle\">".
			"</a>".
			"<div id=".fixStr($int_name."_div")." style=\"position:absolute;display:none\">".
			"<select size=\"5\" id=".fixStr($int_name."_select")." onChange=\"clickCombo(".fixQuot($int_name).");".$onChange."\">";
		
		if (isset($paramHash["rawResults"])) {
			$paramHash["registerControls"].="controlData[".fixStr($int_name)."]={};controlData[".fixStr($int_name)."][\"rawResults\"]=".json_encode($paramHash["rawResults"]).";\n";
		}
		
		for ($b=0;$b<count($paramHash["int_names"]);$b++) {
			if (!isset($paramHash["texts"][$b])) {
				$paramHash["texts"][$b]=s($paramHash["int_names"][$b]);
			}
			$rwInput.="<option value=".fixStr($paramHash["int_names"][$b]).">".
				removeWbr($paramHash["texts"][$b]);
		}
		
		$rwInput.="</select></div>";
	}
	
	if ($splitMode) {
		return array($roInput,($g_settings["highlight_inputs"]?"":"|").$rwInput); // separator
	}
	
	$noLabel=($text==="");
	
	if ($noLabel) {
		$retval.=startEl($tableMode,"rw_".$int_name,array("hide" => true, "colspan" => 2));
	}
	else {
		$retval.=startEl($tableMode,"rw_".$int_name,array("hide" => true)).
			"<label id=\"label_".$int_name."\" for=\"".$int_name."\"".$classTextRw.">".
			$text.
			"</label>".
			middleEl($tableMode,array("br" => ($type=="textarea" || $type=="textarea_classic") ));
	}
	
	$retval.=$rwInput.
		$paramHash["strPost"].
		$paramHash["editHelp"].
		$paramHash["rwInputs"].
		endEl($tableMode);

	if ($noLabel) {
		$retval.=startEl($tableMode,"ro_".$int_name,array("colspan" => 2));
	}
	else {
		$retval.=startEl($tableMode,"ro_".$int_name).
			"<b>".$text.":</b>".
			middleEl($tableMode,array("br" => ($type=="textarea" || $type=="textarea_classic")) );
	}
	
	$retval.=$roInput.
		$paramHash["strPost"].
		$paramHash["roInputs"].
		endEl($tableMode);

	return $retval;
}

function getCheck(& $paramHash) {
	$int_name=& $paramHash["int_name"];

	$onChange=& $paramHash["onChange"];
	$noChangeEffect=($paramHash["noChangeEffect"]?true:false);
	if (!$noChangeEffect) {
		$onChange=getNotifyFunc($onChange);
	}
	
	// $allowLockText=getLock($paramHash["allowLock"]);
	$classTextRw=getClass($paramHash,false);
	$classTextRo=getClass($paramHash,true);
	
	$tabText=getTab($paramHash["tab"]);
	$tableMode=& $paramHash[TABLEMODE];
	$splitMode=& $paramHash[SPLITMODE]; // nur Eingabefeld, kein Text, für Einheit zu Zahl etc. 
	
	if ($paramHash["type"]=="radio") {
		$typeText="radio";
	}
	else {
		$typeText="checkbox";
	}
	
	$onChangeText=" onChange=\"".$onChange."\"";
	
	// multi-change
	if ($paramHash["multiChange"]) {
		$multiCheckText=getMultiCheck($int_name);
	}
	
	$rwControl="<input type=".fixStr($typeText).
		getNameId($paramHash).
		makeHTMLParams($paramHash,array("value"),array(1)).
		$onChangeText.
		$classTextRw.
		$tabText.
		">";
	$roControl="<input type=".fixStr($typeText).
		" id=".fixStr("value_".$int_name).
		" disabled=\"disabled\"".
		$classTextRo.
		">";
	
	$rwInput="<nobr>".
		$rwControl.
		"&nbsp;<label id=\"label_".$int_name."\" for=".fixStr($int_name).
		$classTextRo.">".
		$paramHash["text"].
		"</label>".
		$multiCheckText.
		"</nobr>";
	$roInput="<nobr>".
		$roControl.
		"&nbsp;<label for=".fixStr($int_name).
		$classTextRo.">".
		$paramHash["text"].
		"</label></nobr>";
	
	if ($splitMode) {
		return array($roInput,$rwInput);
	}
	
	$retval.=startEl($tableMode,"rw_".$int_name,array("colspan" => 2, "hide" => true)).
		$rwInput.
		$paramHash["rwInputs"].
		endEl($tableMode).
		startEl($tableMode,"ro_".$int_name,array("colspan" => 2)).
		$roInput.
		$paramHash["roInputs"].
		endEl($tableMode);
	
	return $retval;
}

function getCheckSet(& $paramHash) { // rw: list_multiselect-mode, ro: list-mode (unabhängig), kein Splitmode
	$int_name=& $paramHash["int_name"];
	$int_names=& $paramHash["int_names"]; // Array

	$onChange=$paramHash["onChange"];
	$noChangeEffect=($paramHash["noChangeEffect"]?true:false);
	if (!$noChangeEffect) {
		$onChange=getNotifyFunc($onChange);
	}
	
	// $allowLockText=getLock($paramHash["allowLock"]);
	$classTextRw=getClass($paramHash,false);
	$classTextRo=getClass($paramHash,true);
	
	// layout in table, looks far better
	$tabText=getTab($paramHash["tab"]);
	$tableMode=& $paramHash[TABLEMODE];
	$breakAfter=& $paramHash["breakAfter"];
	if ($breakAfter) {
		$tableStart="<table class=\"noborder\"><tbody>";
		$tableEnd="</tbody></table>";
		$itemStart="<td>";
		$itemEnd="</td>";
		$lineStart="<tr>";
		$lineEnd="</tr>";
	}

	$roList=& $paramHash["roList"];
	// $rwMultiselect=& $paramHash["rwMultiselect"];
	
	if (!isset($paramHash["texts"])) {
		$paramHash["texts"]=array();
		for ($a=0;$a<count($paramHash["int_names"]);$a++) {
			$paramHash["texts"][$a]=s($paramHash["int_names"][$a]);
		}
	}
	$texts=& $paramHash["texts"]; // Array

	$onChangeText=" onChange=".fixStr($onChange);
	
	if ($roList) {
		$roInput.="<span id=".fixStr("value_".$int_name)."></span>";
	}
	else {
		$roInput.=$tableStart;
	}
	$rwInput.=$tableStart;
	
	// multi-change
	if ($paramHash["multiChange"]) {
		$multiCheckText=getMultiCheck($int_name);
	}
	
	for ($a=0;$a<count($int_names);$a++) {
		if ($breakAfter>0 && $a % $breakAfter==0) {
			if (!$roList) {
				$roInput.=$lineStart;
			}
			$rwInput.=$lineStart;
		}
		
		if (!$roList) {
			$roInput.=$itemStart.
				"<nobr><input type=\"checkbox\" id=".fixStr("value_".$int_names[$a]).
				" disabled=\"disabled\"".
				$classTextRo.">&nbsp;".
				$texts[$a].
				"</nobr>".
				$itemEnd;
		}
		$rwInput.=$itemStart.
			"<label id=".fixStr("label_".$int_names[$a]).
			" for=".fixStr($int_names[$a])."><nobr><input type=\"checkbox\" id=".fixStr($int_names[$a]).
			" name=".fixStr($int_name."[]").
			" value=".fixStr($int_names[$a]).
			$onChangeText.
			$classTextRw.
			$tabText.">&nbsp;".
			$texts[$a].
			"</nobr></label>".
			$itemEnd;
		
		if ($breakAfter>0 && $b % $breakAfter==$breakAfter-1) {
			if (!$roList) {
				$roInput.=$lineEnd;
			}
			$rwInput.=$lineEnd;
		}
	}
	
	if ($breakAfter>0) {
		$closer=str_repeat($itemStart.$itemEnd,$a % $breakAfter).$lineEnd;
	}
	
	if (!$roList) {
		$roInput.=$closer.$tableEnd;
	}
	$rwInput.=$closer.
		$tableEnd.
		$multiCheckText;
	
	$noLabel=($paramHash["text"]==="");
	
	if ($noLabel) {
		$retval.=startEl($tableMode,"rw_".$int_name,array("hide" => true, "colspan" => 2));
	}
	else {
		$retval.=startEl($tableMode,"rw_".$int_name,array("hide" => true)).
			"<label id=\"label_".$int_name."\">".
			$paramHash["text"].
			"</label>".
			middleEl($tableMode);
	}
	
	$retval.=$rwInput.
		$paramHash["rwInputs"].
		endEl($tableMode);

	if ($noLabel) {
		$retval.=startEl($tableMode,"ro_".$int_name,array("colspan" => 2));
	}
	else {
		$retval.=startEl($tableMode,"ro_".$int_name).
			"<b>".$paramHash["text"].":</b>".
			middleEl($tableMode);
	}

	$retval.=$roInput.
		$paramHash["roInputs"].
		endEl($tableMode);
	
	return $retval;
}

function getSelect(& $paramHash) {
	$tableMode=& $paramHash[TABLEMODE];
	$splitMode=& $paramHash[SPLITMODE]; // nur Eingabefeld, kein Text, für Einheit zu Zahl etc. 
	if (!$splitMode) {
		$text=$paramHash["text"];
	}
	$int_name=& $paramHash["int_name"];
	
	$texts=& $paramHash["texts"]; // Array
	
	$onChange=$paramHash["onChange"];
	$noChangeEffect=($paramHash["noChangeEffect"]?true:false);
	if (!$noChangeEffect) {
		$onChange=getNotifyFunc($onChange);
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
	
	if ($paramHash["allowAuto"]) {
		array_unshift($paramHash["int_names"],"-1");
		array_unshift($paramHash["texts"],ifempty(
			$paramHash["autoText"],
			s("autodetect")
		));
	}
	if ($paramHash["allowNone"]) {
		array_unshift($paramHash["int_names"],"");
		array_unshift($paramHash["texts"],ifempty(
			$paramHash["noneText"],
			s("none")
		));
	}
	
	$int_names=& $paramHash["int_names"];
	$texts=& $paramHash["texts"]; // Array

	// $allowLockText=getLock($paramHash["allowLock"]);
	$classTextRw=getClass($paramHash,false);
	$classTextRo=getClass($paramHash,true);
	
	$tabText=getTab($paramHash["tab"]);
	
	if (($paramHash["allowAuto"] || $paramHash["allowNone"]) && $paramHash["clearbutton"]) {
		$onChange.="updateClear(".fixQuot($int_name).");";
		$showClear=true;
	}
	
	$onChange.="controlEval(".fixQuot($int_name)."); ";
	$onChangeText=" onKeyup=\"".$onChange."\" onChange=\"".$onChange."\"";
	$onDblClickText=ifNotEmpty(" onDblClick=\"",$paramHash["onDblClick"],"\"");
	
	// multi-change
	if ($paramHash["multiChange"]) {
		$multiCheckText=getMultiCheck($int_name);
	}
	
	if ($showClear) {
		$rwInput.="<nobr>";
	}
	
	$size=ifempty($paramHash["size"],($paramHash["multiMode"]?10:1));
		
	if ($paramHash["multiMode"]) { // button to select all
		$multiText=" multiple=\"multiple\"";
		$rwInput.="<a href=\"javascript:selectAllOptions(".fixQuot($int_name).")\" style=\"text-decoration:none\"><small>".s("select_all_items")."</small></a><br>";
	}
	
	// <span id=\"display_".$int_name."\"".$classTextRw." onMouseover=\"showOverlayId(this,".fixQuot($int_name).")\" onMouseout=\"hideOverlay()\"></span>
	$rwInput.="<select".getNameId($paramHash).$onChangeText.$onDblClickText.$classTextRw.$tabText." size=".fixStr($size).$multiText.">"; //  style=\"position:absolute;left:0px;top:0px;display:none;z-index:2\"
	// $paramHash["registerControls"].="addHideOverlayIdHandler(".fixStr($int_name).");\n";

	for ($a=0;$a<count($int_names);$a++) {
		$newText=removeWbr($texts[$a]);
		$rwInput.="<option id=".fixStr($int_name."_".$int_names[$a])." value=".fixStr($int_names[$a])." title=".fixStr($newText);
		if (isset($paramHash["value"]) && $int_names[$a]==$paramHash["value"]) {
			$rwInput.=" selected=\"selected\"";
		}
		$rwInput.=">";
		if ($paramHash["maxTextLen"]>0) {
			$newText=strcut($newText,$paramHash["maxTextLen"]);
		}
		$rwInput.=$newText;
	}
	$rwInput.="</select>".$multiCheckText;
	
	// clear button
	if ($showClear) {
		$rwInput.="<a href=\"Javascript:clearControl(".fixQuot($int_name).")\" id=\"clear_".$int_name."\"><img src=\"lib/del.png\" width=\"16\" height=\"17\" border=\"0\" style=\"vertical-align:middle\"></a></nobr>";
	}
	
	$rwInput.=$paramHash["strPost"];
	
	$roInput="<span id=".fixStr("value_".$int_name).
		$classTextRo.
		"></span>".
		$paramHash["strPost"];
	
	if ($splitMode) {
		return array($roInput,$rwInput);
	}
	
	$noLabel=($paramHash["text"]==="");
	
	if ($noLabel) {
		$retval.=startEl($tableMode,"rw_".$int_name,array("hide" => true, "colspan" => 2));
	}
	else {
		$retval.=startEl($tableMode,"rw_".$int_name,array("hide" => true)).
			"<label id=".fixStr("label_".$int_name).
				" for=".fixStr($int_name).
				$classTextRw.">".
			$paramHash["text"].
			"</label>".
			middleEl($tableMode);
	}
	$retval.=$rwInput.
		$paramHash["rwInputs"].
		endEl($tableMode);

	
	if ($noLabel) {
		$retval.=startEl($tableMode,"ro_".$int_name,array("colspan" => 2));
	}
	else {
		$retval.=startEl($tableMode,"ro_".$int_name).
			"<b>".$paramHash["text"].":&nbsp;</b>".
			middleEl($tableMode);
	}
	$retval.=$roInput.
		$paramHash["roInputs"].
		endEl($tableMode);
	
	return $retval;
}

function getLanguageSelect(& $paramHash) {
	global $localizedString;
	$int_name=& $paramHash["int_name"];
	$allowDefault=($paramHash["allowDefault"]?true:false);
	
	$int_names=array_keys($localizedString);
	$texts=array();
	
	for ($a=0;$a<count($int_names);$a++) {
		array_push($texts,$localizedString[$int_names[$a]]["language_name"]);
	}
	if ($allowDefault) {
		array_unshift($int_names,"-1");
		array_unshift($texts,s("standard_language"));
	}
	
	$paramHash["int_names"]=$int_names;
	$paramHash["texts"]=$texts;
	
	return getSelect($paramHash);
}

function getHidden(& $paramHash) { // onChange muß durch die ändernde Funktion aufgerufen werden, z.B. bei Structure
	$int_name=& $paramHash["int_name"];

	$onChange=$paramHash["onChange"];
	$noChangeEffect=($paramHash["noChangeEffect"]?true:false);
	if (!$noChangeEffect) {
		$onChange=getNotifyFunc($onChange);
	}
	
	$onChangeText=" onChange=\"".$onChange."\"";
	
	$retval.="<input type=\"hidden\"".
		getNameId($paramHash).
		makeHTMLParams($paramHash,array("value")).
		$onChangeText.">";
	
	return $retval;
}

function getApplet(& $paramHash) { // $paramHash["force"] leads to specific applet and overrides preset
	$int_name=& $paramHash["int_name"]; // molfile_blob
	$paramHash["appletName"]="applet_".$paramHash["int_name"];
	fixMode($paramHash["mode"]);

	$retval.=getAppletHTML($paramHash).getHidden($paramHash);
	
	return $retval;
}

function getStructure(& $paramHash) {
	global $useSvg,$settings;
	
	$paramHash["useSvg"]=$useSvg; // have info on client
	
	$int_name=& $paramHash["int_name"]; // molfile_blob
	if ($paramHash["mode"]!="rxn") {
		$paramHash["mode"]="mol";
	}

	$paramHash["noOverlay"]=($paramHash["mode"]=="rxn" ? $settings["disable_reaction_mouseover"] : $settings["disable_molecule_mouseover"]);
	$classText=getClass($paramHash);
	
	$width=& $paramHash["width"];
	$height=& $paramHash["height"];
	
	if ($width<=0) {
		$width="";
	}
	if ($height<=0) {
		$height="";
	}
	
	$commonParams=$classText." id=\"".$int_name."_img\" width=\"".$width."\" height=\"".$height."\" onMouseover=\"showStructureTooltip(event,this,".fixQuot($int_name).")\" onMouseout=\"hideOverlay()\"";
	if (!$paramHash["noOverlay"] && ($paramHash["posFlags"] & OVERLAY_CONT_UPDATE)) {
		$commonParams.=" onMousemove=\"alignOverlay(event,".$paramHash["posFlags"].")\"";
	}
	
	if ($useSvg) {
		$retval.="<object data=\"lib/1x1.svg\"".$commonParams."></object>"; // <param name=\"src\" value=\"lib/1x1.svg\">
	}
	else {
		$retval.="<img src=\"lib/1x1.gif\"".$commonParams.">";
	}
	
	$retval.=getHidden($paramHash);
	
	return $retval;
}

function getBr() {
	return "<br>";
}

function getPk(& $paramHash) {
	$int_name=& $paramHash["int_name"];
	
	$onChange=$paramHash["onChange"];
	$noChangeEffect=($paramHash["noChangeEffect"]?true:false);
	if (!$noChangeEffect) {
		$onChange=getNotifyFunc($onChange);
	}
	
	// $allowLockText=getLock($paramHash["allowLock"]);
	$classTextRw=getClass($paramHash,false);
	$classTextRo=getClass($paramHash,true);
	
	$tabText=getTab($paramHash["tab"]);
	$tableMode=& $paramHash[TABLEMODE];

	$onChangeText=" onChange=\"".$onChange."\"";
	
	$forMerge=& $paramHash["forMerge"]; // spezieller Modus für merge funktion, kein getFormElements erforderlich
	if ($forMerge) {
		$paramHash["hideToggle"]=true;
	}
	$allowNone=($paramHash["allowNone"]?true:false);
	if (!isset($paramHash["noneText"])) {
		$paramHash["noneText"]=s("none");
	}
	if (!isset($paramHash["setNoneText"])) {
		$paramHash["setNoneText"]=s("none");
	}
	//~ $setNoneText=$paramHash["setNoneText"]; // Neues Molekül / Kein / ...
	$hideToggle=($paramHash["hideToggle"]?true:false);
	
	// multi-change
	if ($paramHash["multiChange"]) {
		$multiCheckText=getMultiCheck($int_name);
	}
	
	// Funktionen ["setValues"] und ["prepareSearch"] werden über getFormElements gesetzt
	if (!$hideToggle) {
		$retval.=startEl($tableMode,"ro_".$int_name).
			ifnotempty("<span style=\"font-weight:bold\"".$classTextRo.">",$paramHash["text"],":&nbsp;</span>").
			middleEl($tableMode).
			"<span id=".fixStr("value_".$int_name).
			$classTextRo.
			"></span>".
			endEl($tableMode);
	}

	$retval.=startEl($tableMode,"rw_".$int_name,array("hide" => true));
	
	if (!$hideToggle) {
		$retval.="<a href=\"javascript:togglePkSearch(&quot;".$int_name."&quot;)\"".$tabText.">".
			ifempty(ifnotempty("",$paramHash["text"],":"),s("do_select").":")."</a>".
			middleEl($tableMode).
			"<a href=\"javascript:togglePkSearch(&quot;".$int_name."&quot;)\" id=".fixStr("text_".$int_name).
			$classTextRo.
			"></a>".
			$multiCheckText; // span text_.. removed
	}
	$retval.=getHidden($paramHash)."<div style=\"display:none;position:relative;border:1px solid black;margin:12px;padding:8px\" id=\"edit_".$int_name."\">
<label for=\"srcInput_".$int_name."\">".s("search_for")." <input type=\"text\" name=\"srcInput_".$int_name."\" id=\"srcInput_".$int_name."\" size=\"40\" maxlength=\"80\" value=\"\" onKeypress=\"return keyUpPk(event,&quot;".$int_name."&quot;)\">
</label> 
<input type=\"button\" value=\"".s("btn_search")."\" onClick=\"searchPk(&quot;".$int_name."&quot;)\">";
	
	if ($allowNone) {
		$retval.=" <a href=\"javascript:setNonePk(&quot;".$int_name."&quot;)\">".$paramHash["setNoneText"]."</a>";
	}
	$retval.="<div id=\"srcResults_".$int_name."\" style=\"overflow:auto;padding:8px\"></div></div>".
		endEl($tableMode);
	
	if (!$hideToggle) {
		$retval.=endEl($tableMode);
	}
	
	if ($forMerge) {
		$paramHash["item"]="pk";
		$paramHash[DEFAULTREADONLY]="never";
		$paramHash[READONLY]=false;
		$paramHash[VISIBLE]=true;
		$retval.=script."
controls[".fixStr($paramHash["int_name"])."]=".json_encode($paramHash).";\n";
//~ controls[".fixStr($paramHash["int_name"])."][\"setValues\"]=function (values,init) { ".$paramHash["setValues"]." };\n";
		
		//~ if (!empty($paramHash["prepareSearch"])) {
			//~ $retval.="controls[".fixStr($paramHash["int_name"])."][\"prepareSearch\"]=function (searchText) { ".$paramHash["prepareSearch"]." };\n";
		//~ }
		$retval.="updateControl(".fixStr($paramHash["int_name"]).");
"._script;
	}
	
	//~ $paramHash["registerControls"].="controls[".fixStr($paramHash["int_name"])."][\"setValues\"]=function (selected_values,init) { ".$paramHash["setValues"]." };\n";
	//~ if (!empty($paramHash["prepareSearch"])) {
		//~ $paramHash["registerControls"].="controls[".fixStr($paramHash["int_name"])."][\"prepareSearch\"]=function (searchText) { ".$paramHash["prepareSearch"]." };\n";
	//~ }
	
	return $retval;
}

function getDBSelect(& $paramHash) {
	$tabText=getTab($paramHash["tab"]);
	$tableMode=& $paramHash[TABLEMODE];
	$splitMode=& $paramHash[SPLITMODE]; // nur Eingabefeld, kein Text, für Einheit zu Zahl etc. 
	if (!$splitMode) {
		$text=$paramHash["text"];
	}
	$int_name=& $paramHash["int_name"];
	
	$classTextRw=getClass($paramHash,false);
	$classTextRo=getClass($paramHash,true);
	
	if ($paramHash["dynamic"]) {
		// no multiMode
		$paramHash["multiMode"]=false;
		$onChange.="trackDynValue(".fixQuot($int_name)."); ";
	}

	$size=ifempty($paramHash["size"],($paramHash["multiMode"]?10:1));
	
	$sizeText=" size=".fixStr($size);
	if ($paramHash["multiMode"]) {
		$multiText=" multiple=\"multiple\"";
	}
	elseif (!isset($paramHash["pkName"])) {
		$paramHash["pkName"]=$paramHash["int_name"]; // normalen int_name
	}
	
	if (!isset($paramHash["autoText"])) {
		$paramHash["autoText"]=s("autodetect");
	}
	
	if (!isset($paramHash["noneText"])) {
		$paramHash["noneText"]=s("none");
	}
	
	// multi-change
	if ($paramHash["multiChange"] && !$paramHash["dynamic"]) {
		$multiCheckText=getMultiCheck($int_name);
	}
	
	// possibility to manipulate list (add special entries,...)
	$paramHash["registerControls"].="
controls[".fixStr($int_name)."][\"beforePkSelectUpdate\"]=function(int_name) {".$paramHash["beforePkSelectUpdate"]."};";

	if ($paramHash["dynamic"]) {
		$paramHash["registerControls"].="
controls[".fixStr($int_name)."][\"getFilter\"]=function(int_name) { ".$paramHash["getFilter"]."};
controls[".fixStr($int_name)."][\"getText\"]=function(int_name,rowData) { ".$paramHash["getText"]."};
controls[".fixStr($int_name)."][\"updateFunction\"]=function(int_name) { ".$paramHash["updateFunction"]."};
controlData[".fixStr($int_name)."]={};\n";
		$paramHash["onChange"]="PkSelectCallUpdate(".fixQuot($paramHash["int_name"])."); ".$paramHash["onChange"];
	}
	else {
		// do filtering on client, only for rw
		$static_data=pk_select_getList($paramHash);
		$paramHash["registerControls"].="
controlData[".fixStr($int_name)."]=".json_encode(array("data" => $static_data, )).";\n";
	}
	
	$onChange.=$paramHash["onChange"];
	$noChangeEffect=($paramHash["noChangeEffect"]?true:false);
	if (!$noChangeEffect) {
		$onChange=getNotifyFunc($onChange);
	}
	
	if (($paramHash["allowAuto"] || $paramHash["allowNone"]) && $paramHash["clearbutton"]) {
		$onChange.="updateClear(".fixQuot($int_name).");";
	}
	
	$onChangeText=" onChange=\"".$onChange."\"";
	
	if (($paramHash["allowAuto"] || $paramHash["allowNone"]) && $paramHash["clearbutton"]) {
		$rwInput.="<nobr>";
		$showClear=true;
	}
	
	if ($paramHash["multiMode"]) { // button to select all
		$rwInput.="<a href=\"javascript:selectAllOptions(".fixQuot($int_name).")\" style=\"text-decoration:none\"><small>".s("select_all_items")."</small></a><br>";
	}
	
	$rwInput.="<select".getNameId($paramHash).$onChangeText.$classTextRw.$tabText.$sizeText.$multiText.">";
	
	if (is_array($paramHash["int_names"])) for ($a=0;$a<count($paramHash["int_names"]);$a++) {
		if (isset($paramHash["defaultValue"])) {
			$checkText=($paramHash["int_names"][$a]==$paramHash["defaultValue"]?" selected=\"selected\"":"");
		}
		if (!isset($paramHash["pk_exclude"]) || $paramHash["int_names"][$a]!=$paramHash["pk_exclude"]) {
			$newText=removeWbr($paramHash["texts"][$a]);
			$rwInput.="<option id=".fixStr($int_name."_".$paramHash["int_names"][$a])." value=".fixStr($paramHash["int_names"][$a]).$checkText." title=".fixStr($newText).">";
			if ($paramHash["maxTextLen"]>0) {
				$newText=strcut($newText,$paramHash["maxTextLen"]);
			}
			$rwInput.=$newText;
		}
	}
	$rwInput.="</select>".$multiCheckText;
	
	// clear button
	if ($showClear) {
		$rwInput.="<a href=\"Javascript:clearControl(".fixQuot($int_name).")\" id=\"clear_".$int_name."\"><img src=\"lib/del.png\" width=\"16\" height=\"17\" border=\"0\" style=\"vertical-align:middle\"></a></nobr>";
	}
	$rwInput.=$paramHash["strPost"];
	
	$roInput="<span id=".fixStr("value_".$int_name).
		"></span>".
		$paramHash["strPost"];
	
	if ($splitMode) {
		return array($roInput,$rwInput);
	}
	
	$retval=startEl($tableMode,"rw_".$int_name,array("hide" => true)).
		"<label id=".fixStr("label_".$int_name).
		" for=".fixStr($int_name).
		$classTextRw.
		">".$text."</label>".
		middleEl($tableMode,array("br" => $size!=1)).
		$rwInput.
		$paramHash["rwInputs"].
		endEl($tableMode).
		startEl($tableMode,"ro_".$int_name).
		"<b>".ifnotempty("",$text,":")."&nbsp;</b>".
		middleEl($tableMode).
		$roInput.
		$paramHash["roInputs"].
		endEl($tableMode);
	
	return $retval;	
}

?>