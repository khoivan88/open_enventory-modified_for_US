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

// sehr komplex

function getSubitemList(& $paramHash) {
	global $useSvg,$settings;
	$int_name=& $paramHash["int_name"]; // $result["int_name"][1..x]["sub_int_name"], im JS als list_int_name bezeichnet
	
	$splitMode=& $paramHash[SPLITMODE];
	$paramHash["class"]=ifnotset($paramHash["class"],"subitemlist");

	$allowReorder=($paramHash["allowReorder"]?true:false);
	$editLinks=($paramHash["editLinks"]?true:false);
	$editLinksFunction=& $paramHash["editLinksFunction"];
	
	$paramHash["addButtonFree"]=false;
	
	$fields=& $paramHash["fields"]; // array(array("item" => "cell"),array("item" => "input", "int_name" => ...,),array("item" => "info")),
	$noChangeEffect=($paramHash["noChangeEffect"]?true:false);
	$lineInitFunction=ifnotempty("",$paramHash["lineInitFunction"],"; ");
	$lineDelFunction=$paramHash["lineDelFunction"]; // make cleanup possible
	
	$headline="<tr>";
	$headline_readOnly="<tr>";
	$paramHash["lines"]=1;
	
	if ($paramHash["allowCollapse"]) {
		// insert cell and buttons before 1st "line"
		for ($a=0;$a<count($fields);$a++) {
			if ($fields[$a]["item"]=="line") {
				array_splice(
					$fields,
					$a,
					0,
					array(
						array("item" => "cell", "class" => "noprint"), 
						array("item" => "button", "int_name" => "expand", "showInHeadline" => true, VISIBLE => false, "img" => "lib/plus.gif", "class" => "imgButtonSm", "onClick" => "performToggle", ), 
						array("item" => "button", "int_name" => "collapse", "showInHeadline" => true, "img" => "lib/minus.gif", "class" => "imgButtonSm", "onClick" => "performToggle", ), 
					)
				);
				break;
			}
		}
	}
	
	for ($a=0;$a<count($fields);$a++) {
		if (!is_array($fields[$a])) {
			// "br", etc
			$fields[$a]=array("item" => $fields[$a]);
		}
	}
	
	if (!$paramHash["noAutoLinks"]) {
		// zeilenweise readOnly wird ber JS/Lockname realisiert
		for ($a=0;$a<count($fields);$a++) {
			if ($fields[$a]["item"]=="links") {
				$hasLinks=true;
				break;
			}
		}
		if (!$hasLinks) {
			$fields[]=array("item" => "cell", "hideReadOnly" => true, ); // "int_name" => "links", 
			$fields[]=array("item" => "links", "int_name" => "links", );
		}
	}
	
	// spaltenzahl bei mehreren Lines mit colspan ausgleichen
	handleColumnCount($paramHash);
	
	// zZt deaktiviert, weil das noprint über colgroup nicht interpretiert wird
	if (false && $paramHash["lineCount"]==1 && !$splitMode) { // format cells using colgroup
		$colgroup=true;
		$colgroupRo="<colgroup>";
		$colgroupRw="<colgroup>";
	}
	
	$quot_list_int_name=htmlspecialchars(fixStr($int_name));
	
	for ($a=0;$a<count($fields);$a++) {
		if ($fields[$a]["skip"]) {
			continue;
		}
		
		$group=ifempty($fields[$a]["group"],$currentGroup); // $currentGroup is on/off
		if (isset($group)) {
			$fields[$a]["group"]=$group; // do not have all values for group set to "null"
			$groupIdText="_".$group;
			$groupParamText=",".fixStr($group);
			$groupParamQuot=",".fixQuot($group);
		}
		else {
			$groupIdText="";
			$groupParamText=",undefined"; // always have this param
			$groupParamQuot=",undefined"; // always have this param
		}
		
		$unmasked_name=$int_name."_~UID~_".$fields[$a]["int_name"].$groupIdText;
		$unmasked_name_readOnly="ro_".$unmasked_name;
		$name=fixStr($unmasked_name);
		$name_readOnly=fixStr($unmasked_name_readOnly);
		$setFunction=$fields[$a]["setFunction"];
		
		if ($fields[$a][VISIBLE]===FALSE) {
			$fields[$a]["style"]="display:none;".$fields[$a]["style"];
		}
		
		if (isset($fields[$a]["style"])) {
			$styleText=" style=".fixStr($fields[$a]["style"]);
			if ($colgroup) {
				$colgroupRo.=$styleText;
				$colgroupRw.=$styleText;
				$styleText="";
			}
		}
		else {
			$styleText="";
		}
			
		// additional Fields
		if ($fields[$a]["additionalField"]) {
			$headline.=showHidden(array("int_name" => "additionalFields[]", "value" => $fields[$a]["int_name"]));
		}
		
		// helpful for many items
		$JSitemParams=fixStr($int_name).",~UID~,".fixStr($fields[$a]["int_name"]).$groupParamText; // tell JS function from where they were called
		
		// onChange handling
		if (empty($fields[$a]["onChange"])) {
			$onChange="";
		}
		else {
			$onChange=$fields[$a]["onChange"]."(".$JSitemParams."); ";
		}
		
		// special handling for pk_select/dynamic
		if ($fields[$a]["item"]=="pk_select" && $fields[$a]["dynamic"]) {
			$onChange="SILPkSelectCallUpdate(".$JSitemParams."); ".$onChange;
		}
		if ($fields[$a]["item"]=="input" && ($fields[$a]["type"]=="round" || $fields[$a]["type"]=="combo")) { // put the rounded value
			$onChange="SILchanged(".$JSitemParams.");".$onChange;
		}
		
		if (!$noChangeEffect) {
			if (in_array($fields[$a]["item"],array("select","pk_select"))) {
				$onChange=getNotifyFuncSelect($onChange,"SILsetDesiredAction(".fixStr($int_name).",~UID~,\"update\"); "); // the value may remain but the index is changed
			}
			else {
				$onChange=getNotifyFunc($onChange,"SILsetDesiredAction(".fixStr($int_name).",~UID~,\"update\"); ");
			}
		}
		$onChange=htmlspecialchars($onChange); // mask " as &quot;
		
		$JSitemParams=htmlspecialchars($JSitemParams); // ERST HIER QUOTEN!!
		
		$elReadOnly=$readOnly || $fields[$a][READONLY]; // either subitemlist or control it self may be readOnly
		
		// helpful for many items
		$onChangeText=" onChange=\"".$onChange."\"";
		
		// onMouseover/out
		if (isset($fields[$a]["onMouseover"])) { // bei vielen anderen events spielt sowieso nur rw eine rolle
			$onMouseoverText=" onMouseover=\"".$fields[$a]["onMouseover"]."(this,".$JSitemParams.",false)\"";
			$onMouseoverText_readOnly=" onMouseover=\"".$fields[$a]["onMouseover"]."(this,".$JSitemParams.",true)\"";
		}
		else {
			$onMouseoverText="";
			$onMouseoverText_readOnly="";
		}
		if (isset($fields[$a]["onMouseout"])) {
			$onMouseoutText=" onMouseout=\"".$fields[$a]["onMouseout"]."(this,".$JSitemParams.",false)\"";
			$onMouseoutText_readOnly=" onMouseout=\"".$fields[$a]["onMouseout"]."(this,".$JSitemParams.",true)\"";
		}
		else {
			$onMouseoutText="";
			$onMouseoutText_readOnly="";
		}
		
		$classText=getClass($fields[$a]);
		$classTextRw=getClass($fields[$a],false);
		$classTextRo=getClass($fields[$a],true);
		
		switch($fields[$a]["item"]) {
		case "button":
			$element_prototype1="<a href=\"javascript:".$fields[$a]["onClick"]."(";
			$element_prototype2=")\"".$visibleText;
			$element_prototype3=$styleText."><img src=".fixStr($fields[$a]["img"])." border=\"0\"".getTooltipP(getControlText($fields[$a]))."></a>"; // <nobr>".$fields[$a]["text"]."</nobr>
			
			// headline, used for expand/collapse
			if ($fields[$a]["showInHeadline"]) {
				$JSheadlineParams=htmlspecialchars(fixStr($int_name).",\"\",".fixStr($fields[$a]["int_name"]).$groupParamText);
				$unmasked_headline_name=$int_name."_headline_".$fields[$a]["int_name"].$groupIdText; // UID="headline"
				
				$headline_name=fixStr($unmasked_headline_name);
				$headline_name_readOnly=fixStr("ro_".$unmasked_headline_name);
			}
			
			if (!$fields[$a]["hideReadWrite"]) {
				$line_prototype.=$element_prototype1.$JSitemParams.$element_prototype2.$classTextRw." id=".$name.$element_prototype3;
				if ($fields[$a]["showInHeadline"]) {
					$headline.=$element_prototype1.$JSheadlineParams.$element_prototype2.$classTextRw." id=".$headline_name.$element_prototype3;
				}
			}
			if (!$fields[$a]["hideReadOnly"]) {
				$line_prototype_readOnly.=$element_prototype1.$JSitemParams.$element_prototype2.$classTextRo." id=".$name_readOnly.$element_prototype3;
				if ($fields[$a]["showInHeadline"]) {
					$headline_readOnly.=$element_prototype1.$JSheadlineParams.$element_prototype2.$classTextRo." id=".$headline_name_readOnly.$element_prototype3;
				}
			}
		break;
		
		case "cell":
			if ($a>0) {
				$headline_prototype="</td>";
				$element_prototype="</td>";
			}
			else {
				$headline_prototype="";
				$element_prototype="";
			}
			
			if ($colgroup) {
				$colgroupRo.="<col".$classText;
				$colgroupRw.="<col".$classText;
				$classText="";
			}
			
			if (isset($fields[$a]["colspan"])) {
				$colspanText=" colspan=".fixStr($fields[$a]["colspan"]);
				if ($colgroup) {
					$colgroupRo.=$colspanText;
					$colgroupRw.=$colspanText;
					$colspanText="";
				}
			}
			
			if (isset($fields[$a]["rowspan"])) {
				$rowspanText=" rowspan=".fixStr($fields[$a]["rowspan"]);
				if ($colgroup) {
					$colgroupRo.=$rowspanText;
					$colgroupRw.=$rowspanText;
					$rowspanText="";
				}
			}
			
			if ($colgroup) {
				$colgroupRo.=">";
				$colgroupRw.=">";
			}
			
			if (isset($fields[$a]["int_name"])) {
				$nameHeadlineText=" id=".fixStr($int_name."_headline_".$fields[$a]["int_name"].$groupIdText);
				$nameText=" id=".$name;
			}
			else {
				$nameHeadlineText="";
				$nameText="";
			}
			
			$headline_prototype.="<td".
				$nameHeadlineText.
				$classText.
				$visibleText.
				$colspanText.
				$rowspanText.
				$styleText.
				">";
			
			$line_prototype.="<td".
				$nameText.
				$classText.
				$visibleText.
				$colspanText.
				$rowspanText.
				$styleText.
				$onMouseoverText.
				$onMouseoutText.
				" onClick=\"f1(event,this)\">";
			
			if ($a==0) { // only for rw-version
				$line_prototype.="<a name=\"".$int_name."_~UID~\"><input type=\"hidden\" id=\"".$int_name."_~UID~\" name=\"".$int_name."[]\" value=\"~UID~\"><input type=\"hidden\" id=\"desired_action_".$int_name."_~UID~\" name=\"desired_action_".$int_name."_~UID~\" value=\"\"> </a>"; //hidden
			}
			
			if ($paramHash["lines"]==1) { // nur 1. Zeile
				$headline.=$headline_prototype;
			}
			if (!$fields[$a]["hideReadOnly"]) {
				$line_prototype_readOnly.="<td".
					$classText.
					$visibleText.
					$colspanText.
					$rowspanText.
					$styleText.
					$onMouseoverText_readOnly.
					$onMouseoutText_readOnly.
					" onClick=\"f1(event,this)\">";
				
				if ($a==0) {
					$line_prototype_readOnly.="<a name=\"ro_".$int_name."_~UID~\"></a>";
				}
				if ($paramHash["lines"]==1) { // nur 1. Zeile
					$headline_readOnly.=$headline_prototype;
				}
			}
		break;
		case "check":
		case "checkbox":
			$textInLine="";
			if ($fields[$a]["textInLine"]) {
				$textInLine=getControlText($fields[$a]);
			}
			elseif ($paramHash["lines"]==1) { // nur 1. Zeile
				$headline_prototype=getControlText($fields[$a]);
				$headline.=$headline_prototype;
				$headline_readOnly.=$headline_prototype;
			}
			
			if ($fields[$a]["defaultValue"]) {
				$checkText=" checked=\"checked\"";
			}
			else {
				$checkText="";
			}
			
			if (isset($fields[$a]["value"])) {
				$valueText=$fields[$a]["value"];
			}
			else {
				$valueText="true";
			}
			
			$element_prototype="<input type=\"checkbox\" value=".fixStr($valueText).$onChangeText.$classTextRw.$visibleText;
			$line_prototype.=$element_prototype.
				" name=".$name.
				" id=".$name.
				$checkText.
				$onMouseoverText.
				$onMouseoutText.
				">".
				$textInLine;
			
			$line_prototype_readOnly.=$element_prototype.
				" name=".$name_readOnly.
				" id=".$name_readOnly.
				$checkText.
				$onMouseoverText_readOnly.
				$onMouseoutText_readOnly.
				" disabled=\"disabled\">".
				$textInLine;
			
		break;
		case "checkset":
			
			if ($paramHash["lines"]==1) { // nur 1. Zeile
				$headline_prototype=getControlText($fields[$a]);
				$headline.=$headline_prototype;
				$headline_readOnly.=$headline_prototype;
			}
			$breakAfter=$fields[$a]["breakAfter"];
			if ($breakAfter) {
				$tableStart="<table class=\"noborder\"><tbody>";
				$tableEnd="</tbody></table>";
				$itemStart="<td>";
				$itemEnd="</td>";
				$lineStart="<tr>";
				$lineEnd="</tr>";
			}
			else {
				unset($tableStart);
				unset($tableEnd);
				unset($itemStart);
				unset($itemEnd);
				unset($lineStart);
				unset($lineEnd);
			}
			
			// display as list in ro mode
			$roList=$fields[$a]["roList"];
			if ($roList) {
				$line_prototype_readOnly.="<span id=".$name_readOnly.
					$classTextRo.
					$visibleText.
					$onMouseoverText_readOnly.
					$onMouseoutText_readOnly.
					"></span>";
			}
			else {
				$line_prototype_readOnly.=$tableStart;
			}
			$line_prototype.=$tableStart;
			
			if ($fields[$a]["images"]) {
				$imgAttribs=makeHTMLParams($fields[$a],array("width","height",));
			}
			
			for ($b=0;$b<count($fields[$a]["int_names"]);$b++) {
				if ($breakAfter>0 && $b % $breakAfter==0) {
					if (!$roList) {
						$line_prototype_readOnly.=$lineStart;
					}
					$line_prototype.=$lineStart;
				}
				
				if (!isset($fields[$a]["texts"][$b])) {
					$fields[$a]["texts"][$b]=s($fields[$a]["int_names"][$b]);
				}
				$text=$fields[$a]["texts"][$b];
				$image=$fields[$a]["images"][$b];
				
				$line_prototype.=$itemStart."<label id=\"label_".$unmasked_name."_".$fields[$a]["int_names"][$b]."\" for=\"".$unmasked_name."_".$fields[$a]["int_names"][$b]."\"><nobr><input type=\"checkbox\" id=\"".$unmasked_name."_".$fields[$a]["int_names"][$b]."\" name=\"".$unmasked_name."[]\" value=\"".$fields[$a]["int_names"][$b]."\"".$onChangeText.$visibleText.$onMouseoverText.$onMouseoutText.$classTextRw.$tabText.">&nbsp;";
				if (!isEmptyStr($image)) {
					$line_prototype.="<img src=".fixStr("lib/".$image).$imgAttribs.getTooltipP($text)."/>";
				}
				else {
					$line_prototype.=$text;
				}
				$line_prototype.="</nobr></label>".$itemEnd;
				
				if (!$roList) {
					$line_prototype_readOnly.=$itemStart."<nobr><input type=\"checkbox\" id=\"value_".$unmasked_name_readOnly."_".$fields[$a]["int_names"][$b]."\" disabled=\"disabled\"".$classTextRo.">&nbsp;";
					if (!isEmptyStr($image)) {
						$line_prototype_readOnly.="<img src=".fixStr("lib/".$image).$imgAttribs.getTooltipP($text)."/>";
					}
					else {
						$line_prototype_readOnly.=$text;
					}
					$line_prototype_readOnly.="</nobr>".$itemEnd;
				}
				
				if ($breakAfter>0 && $b % $breakAfter==$breakAfter-1) {
					if (!$roList) {
						$line_prototype_readOnly.=$lineEnd;
					}
					$line_prototype.=$lineEnd;
				}
			}
			
			if ($breakAfter>0) {
				$closer=str_repeat($itemStart.$itemEnd,$b % $breakAfter).$lineEnd;
			}
			else {
				unset($closer);
			}
			
			if (!$roList) {
				$line_prototype_readOnly.=$closer.$tableEnd;
			}
			$line_prototype.=$closer.$tableEnd;
			
		break;
		case "groupStart":
			$currentGroup=$fields[$a]["group"];
		break;
		case "groupEnd":
			unset($currentGroup);
		break;
		case "hidden": // onChange geht nicht
			
			if (isset($fields[$a]["defaultValue"])) {
				$valText=" value=".fixStr($fields[$a]["defaultValue"]);
			}
			else {
				$valText="";
			}
			
			$line_prototype.="<input type=\"hidden\" name=".$name." id=".$name.$valText.">";
			
		break;
		case "input":
			$text=getControlText($fields[$a]);
			$titleText=" title=".fixStr(strip_tags(str_replace(array("<br>")," ",$text)));
			if ($paramHash["lines"]==1) { // nur 1. Zeile
				$headline_prototype=$text;
				$headline.=$headline_prototype;
				$headline_readOnly.=$headline_prototype;
				
				if ($fields[$a]["set_all_button"]) {
					$headline.="<a class=\"imgButtonSm\" href=\"javascript:void SILprepareSetAll(".htmlspecialchars(fixStr($int_name).",".fixStr($fields[$a]["int_name"]).$groupParamText).")\"".$visibleText.$styleText."><img src=\"lib/edit_col_sm.png\" border=\"0\"".getTooltipP("edit_col")."></a>";
				}
			}
			
			$type=& $fields[$a]["type"];
			
			switch ($type) {
			case "range":
				$fields[$a]["postProc"]="range";
			break;
			case "date":
				$fields[$a]["postProc"]="date";
			break;
			case "textarea":
				$lineInitFunction.="make_wyzz(".fixStr($unmasked_name).");";
			break;
			case "combo":
				// onFocus: hide <select
				
			break;
			}
			
			//~ $onChangeText=" onFocus=\"hi(this,true);fC(event,this)\" onBlur=\"hi(this,false);".$onChange."\" onKeyUp=\"".$onChange."\"";
			$onChangeText=" onFocus=\"hi(this,true);fC(event,this)\" onBlur=\"hi(this,false);\" onKeyUp=\"".$onChange.
				(($type=="textarea" || $type=="textarea_classic")?"":" SILhandleInputKey(event,".$JSitemParams.");").
				" \"";
			
			if ($fields[$a]["doEval"]) { // eval(value) when changed, may be dangerous
				$onChangeText.=" onChange=\"".ifempty($fields[$a]["evalFunction"],"SILeval")."(".$JSitemParams.");\"";
			}
			
			if (isset($fields[$a]["defaultValue"])) {
				$valText=" value=".fixStr($fields[$a]["defaultValue"]);
			}
			else {
				$valText="";
			}
			
			$line_prototype_readOnly.="<span id=".
				$name_readOnly.
				$classTextRo.
				$titleText.
				$visibleText.
				$onMouseoverText_readOnly.
				$onMouseoutText_readOnly.
				"></span>";
			
			if ($fields[$a][DEFAULTREADONLY]=="always") {
				$value_name=fixStr("value_".$unmasked_name);
				$line_prototype.="<span id=".
					$value_name.
					$classTextRw.
					$visibleText.
					$onMouseoverText.
					$onMouseoutText.
					"></span><input type=\"hidden\" name=".$name." id=".$name.">";
			}
			elseif ($type=="textarea" || $type=="textarea_classic") {
				$line_prototype.="<textarea id=".
					$name.
					$classTextRw.
					$titleText.
					$visibleText.
					$onMouseoverText.
					$onMouseoutText.
					" name=".$name.
					makeHTMLParams($fields[$a],array("cols","rows"),array(80,6)).
					$onChangeText.
					"></textarea>";
			}
			else {
				$typeText="text";
				$sizeText=makeHTMLParams($fields[$a],array("size","maxlength"),array());
				
				if ($type=="range") {
					$low_name=$unmasked_name."_low";
					$high_name=$unmasked_name."_high";
					$line_prototype.="<input type=\"hidden\" ".getNameId($low_name).
						$valText.
						"><input type=\"hidden\"".
						getNameId($high_name).
						$valText
						.">";
				}
				elseif ($type=="password") {
					$typeText="password";
				}
				elseif (in_array($type,array("round","combo"))) {
					//~ $typeText="text";
					$typeText="hidden"; // das eigentliche Feld wird versteckt und ein int_name_rounded angezeigt
				}
				
				$line_prototype.="<input type=\"".$typeText."\" name=".$name.
					" id=".$name.
					$classTextRw.
					$titleText.
					$visibleText.
					$sizeText.
					$onChangeText.
					$valText.
					$onMouseoverText.
					$onMouseoutText.
					">";
				
				unset($second_name);
				if ($type=="percent") {
					$line_prototype.="%";
				}
				elseif ($type=="password") {
					$second_name=$unmasked_name."_repeat";
				}
				elseif (in_array($type,array("round","combo"))) {
					$second_name=$unmasked_name."_rounded";
					$typeText="text";
					if ($type=="combo") {
						$line_prototype.="<nobr>";
					}
				}
				if (isset($second_name)) { // Hilfs-Eingabefeld für Password und Round
					$line_prototype.="<input type=".fixStr($typeText).
						getNameId($second_name).
						$classTextRw.
						$titleText.
						$visibleText.
						$sizeText.
						$onChangeText.
						$valText.
						$onMouseoverText.
						$onMouseoutText.
						">";
				}
			}
			
			if ($fields[$a]["clearbutton"] && in_array($type,array("","date","percent"))) {
				$line_prototype.="<a id=".fixStr($unmasked_name."_button")." href=\"Javascript:clearInput(&quot;".$unmasked_name."&quot;)\"".$visibleText."><img src=\"lib/del.png\" width=\"16\" height=\"17\" border=\"0\" style=\"vertical-align:middle\"></a>";
			}
			elseif ($type=="combo") { // button, div, <select
				// select kriegt namen_select, onChange: Text => namen_rounded, value => namen
				// int_names und texts wie bei "select"
				$line_prototype.="<a id=".fixStr($unmasked_name."_button")." href=\"Javascript:SILtoggleSelect(".$JSitemParams.")\"".$visibleText.">".
					"<img src=\"lib/dropdown.png\" width=\"19\" height=\"19\" border=\"0\" style=\"vertical-align:middle\">".
					"</a></nobr>".
					"<div id=".fixStr($unmasked_name."_div")." style=\"position:absolute;display:none\">".
					"<select size=\"5\" id=".fixStr($unmasked_name."_select")." onChange=\"".$onChange." SILclickCombo(".$JSitemParams.");\">";
				
				for ($b=0;$b<count($fields[$a]["int_names"]);$b++) {
					if (!isset($fields[$a]["texts"][$b])) {
						$fields[$a]["texts"][$b]=s($fields[$a]["int_names"][$b]);
					}
					$line_prototype.="<option value=".fixStr($fields[$a]["int_names"][$b]).">".removeWbr($fields[$a]["texts"][$b]);
				}
				
				$line_prototype.="</select></div>";
			}
			
			// Anzeigewert noch durch Funktion jagen
			if (isset($fields[$a]["handleDisplay"])) {
				$paramHash["registerControls"].="controls[".fixStr($int_name)."][\"fields\"][".$a."][\"handleDisplay\"]=function(list_int_name,UID,pos,fieldIdx,int_name,group,displayValue) {".addJSvar($fields[$a]["handleDisplay"])."};\n";
			}
			
		break;
		
		case "js": // Javascript function to determine text, readOnly
		// if the function returns false, => no change
			if ($paramHash["lines"]==1) { // nur 1. Zeile
				$headline_prototype=getControlText($fields[$a]);
				$headline.=$headline_prototype;
				$headline_readOnly.=$headline_prototype;
			}
			
			$line_prototype.="<span id=".$name.
				$classTextRw.
				$styleText.
				$visibleText.
				$onMouseoverText.
				$onMouseoutText.
				"></span>";
			$line_prototype_readOnly.="<span id=".$name_readOnly.
				$classTextRo.
				$styleText.
				$visibleText.
				$onMouseoverText_readOnly.
				$onMouseoutText_readOnly.
				"></span>";
			
			$lineInitFunction.="SILsetValueUID(".fixStr($int_name).",UID,pos,".$a.",".fixStr($fields[$a]["int_name"]).$groupParamText.",[]);";
		break;
		
		case "line": // starts 2nd, 3rd,.. line for dataset tr_list_int_name_UID_(0..n)
			
			$line_prototype.="</tr><tr id=\"tr_".$int_name."_~UID~_".$paramHash["lines"]."\">";
			$line_prototype_readOnly.="</tr><tr id=\"tr_readOnly_".$int_name."_~UID~_".$paramHash["lines"]."\">";
			$paramHash["lines"]++;
			
		break;
		case "line_number": // summand, useLetter
			$fields[$a]["summand"]=ifempty($fields[$a]["summand"],1);
			if ($paramHash["lines"]==1) { // nur 1. Zeile
				$headline_prototype=getControlText($fields[$a]);
				$headline.=$headline_prototype;
				$headline_readOnly.=$headline_prototype;
			}
			
			if (!empty($fields[$a]["int_name"])) {
				$line_prototype.="<input type=\"hidden\" name=".$name." id=".$name.">";
			}
			$line_prototype.="<span id=\"span_".$unmasked_name."\"".
				$classTextRw.
				$visibleText.
				$onMouseoverText.
				$onMouseoutText.
				"></span>";
			
			$line_prototype_readOnly.="<span id=".$name_readOnly.
				$classTextRo.
				$visibleText.
				$onMouseoverText_readOnly.
				$onMouseoutText_readOnly.
				"></span>";
			
			$lineInitFunction.="SILsetValueUID(".fixStr($int_name).",UID,pos,".$a.",".fixStr($fields[$a]["int_name"]).$groupParamText.",[]);";
		break;
		
		case "links": // show nothing if elReadOnly
			if ($paramHash["lines"]==1) { // nur 1. Zeile
				$headline_prototype="&nbsp;";
				$headline.=$headline_prototype;
				$headline_readOnly.=$headline_prototype;
			}
			
			if ($editLinks) { // aufruf einer JS-funktion
				$line_prototype.="<a href=\"javascript:".$editLinksFunction."(".$JSitemParams.")\" class=\"imgButtonSm\" id=".fixStr($int_name."_~UID~_btn_edit")."><img src=\"lib/details_sm.png\" border=\"0\"".getTooltip("details")."></a>";
			}
			
			$buttonstyle=ifempty($fields[$a]["style"],"Sm");
			
			if ($allowReorder) { // ausblenden wenn 1.
				$line_prototype.=SILgetButton(array(
					"type" => "up", 
					"style" => $buttonstyle, 
					"quot_list_int_name" => $quot_list_int_name, 
					"int_name" => $int_name, 
				));
				if (!$paramHash["noManualAdd"]) {
					$line_prototype.=SILgetButton(array(
						"type" => "add_line", 
						"style" => $buttonstyle, 
						"quot_list_int_name" => $quot_list_int_name, 
						"int_name" => $int_name, 
					));
				}
			}
			
			if (!$paramHash["noManualDelete"]) {
				$line_prototype.=SILgetButton(array(
					"type" => "del", 
					"style" => $buttonstyle, 
					"quot_list_int_name" => $quot_list_int_name, 
					"int_name" => $int_name, 
				));
			}
			
			if ($allowReorder) { // ausblenden wenn Ende
				$line_prototype.=SILgetButton(array(
					"type" => "down", 
					"style" => $buttonstyle, 
					"quot_list_int_name" => $quot_list_int_name, 
					"int_name" => $int_name, 
				));
			}
		break;
		
		case "radio":
			if ($paramHash["lines"]==1) { // nur 1. Zeile
				$headline_prototype=getControlText($fields[$a]);
				$headline.=$headline_prototype;
				$headline_readOnly.=$headline_prototype;
			}
			
			$radio_name_unmasked=$int_name."_".$fields[$a]["int_name"].$groupIdText; // no UID, will be the selected value instead
			$radio_name_unmasked_readOnly="ro_".$radio_name_unmasked;
			$radio_name=fixStr($radio_name_unmasked);
			$radio_name_readOnly=fixStr($radio_name_unmasked_readOnly);
			$line_prototype.="<input type=\"radio\" name=".$radio_name." id=".$name.$classTextRw.$onChangeText." value=\"~UID~\">";
			$line_prototype_readOnly.="<input type=\"radio\" name=".$radio_name_readOnly." id=".$name_readOnly.$classTextRo.$onChangeText." value=\"~UID~\" disabled=\"disabled\">";
			
		break;
		
		case "pk_select": // abfrage durchführen und in select einflechten, kein multiMode wie im einzelnen Steuerelement
			// Nicht fertig, nicht getestet!!
			
			if ($paramHash["lines"]==1) { // nur 1. Zeile
				$headline_prototype=getControlText($fields[$a]);
				$headline.=$headline_prototype;
				$headline_readOnly.=$headline_prototype;
			}
			
			if (!isset($fields[$a]["noneText"])) {
				$fields[$a]["noneText"]=s("none");
			}

			//~ if ($fields[$a]["dynamic"]) {
				//~ $paramHash["registerControls"].="
//~ controls[".fixStr($paramHash["int_name"])."][\"fields\"][".fixNull($a)."][\"getFilter\"]=new Function(\"list_int_name,UID,int_name\",".fixStr($paramHash["getFilter"]).");
//~ controls[".fixStr($paramHash["int_name"])."][\"fields\"][".fixNull($a)."][\"getText\"]=new Function(\"list_int_name,UID,int_name,rowData\",".fixStr($paramHash["getText"]).");
//~ controls[".fixStr($paramHash["int_name"])."][\"fields\"][".fixNull($a)."][\"updateFunction\"]=new Function(\"list_int_name,UID,int_name\",".fixStr($paramHash["updateFunction"]).");\n";
//~ // controlData[".fixStr($paramHash["int_name"][\"data\"][])."]=new Array();\n";
				//~ $lineInitFunction.="controlData[".fixStr($paramHash["int_name"])."][\"data\"][UID]=new Array(); ";
			//~ }
			//~ else {
				if (isset($fields[$a]["pk_exclude"])) {
					if (!empty($fields[$a]["filter"])) {
						$fields[$a]["filter"].=" AND ";
					}
					//~ $fields[$a]["filter"].=$query[ $fields[$a]["table"] ]["primary"]."!=".fixNull($fields[$a]["pk_exclude"]);
					$fields[$a]["filter"].=getLongPrimary($fields[$a]["table"])."!=".fixNull($fields[$a]["pk_exclude"]);
				}
				
				$paramHash["registerControls"].="controls[".fixStr($paramHash["int_name"])."][\"fields\"][".fixNull($a)."][\"data\"]=".json_encode(pk_select_getList($fields[$a])).";\n";
			//~ }
			
			$onChangeText.=" onKeyup=\"".$onChange."\"";
			
			$line_prototype_readOnly.="<span id=".$name_readOnly.
				$classTextRo.
				$visibleText.
				$onMouseoverText_readOnly.
				$onMouseoutText_readOnly.
				"></span>";
			
			$line_prototype.="<select name=".$name.
				" id=".$name.
				$classTextRw.
				$onChangeText.
				$visibleText.
				$onMouseoverText.
				$onMouseoutText.
				">";
			
			for ($b=0;$b<count($fields[$a]["int_names"]);$b++) {
				if (!isset($fields[$a]["texts"][$b])) {
					$fields[$a]["texts"][$b]=s($fields[$a]["int_names"][$b]);
				}
				$line_prototype.="<option value=".fixStr($fields[$a]["int_names"][$b]).($fields[$a]["defaultValue"]===$fields[$a]["int_names"][$b]?" selected=\"selected\"":"").">".removeWbr($fields[$a]["texts"][$b]);
			}
			$line_prototype.="</select>";
			
		break;
		
		case "select":
			if ($paramHash["lines"]==1) { // nur 1. Zeile
				$headline_prototype=getControlText($fields[$a]);
				$headline.=$headline_prototype;
				$headline_readOnly.=$headline_prototype;
			}
			
			$onChangeText.=" onKeyup=\"".$onChange."\"";
			
			$line_prototype_readOnly.="<span id=".$name_readOnly.
				$classTextRo.
				$visibleText.
				$onMouseoverText_readOnly.
				$onMouseoutText_readOnly.
				"></span>";
			
			$line_prototype.="<select name=".$name.
				" id=".$name.
				$classTextRw.
				$onChangeText.
				$visibleText.
				$onMouseoverText.
				$onMouseoutText.
				">";
			
			if (is_array($fields[$a]["int_names"])) for ($b=0;$b<count($fields[$a]["int_names"]);$b++) {
				if (!isset($fields[$a]["texts"][$b])) {
					$fields[$a]["texts"][$b]=s($fields[$a]["int_names"][$b]);
				}
				$line_prototype.="<option value=".fixStr($fields[$a]["int_names"][$b]).($fields[$a]["defaultValue"]===$fields[$a]["int_names"][$b]?" selected=\"selected\"":"").">".removeWbr($fields[$a]["texts"][$b]);
			}
			$line_prototype.="</select>";
			
		break;
		
		case "span":
			$line_prototype_readOnly.="<span id=".$name_readOnly.
				$classTextRo.
				$visibleText.
				$onMouseoverText_readOnly.
				$onMouseoutText_readOnly.
				"></span>";
			
			$line_prototype.="<span id=".$name.
				$classTextRw.
				$visibleText.
				$onMouseoverText_readOnly.
				$onMouseoutText_readOnly.
				"></span>";
			
		break;
		
		case "structure":
			if ($paramHash["lines"]==1) { // nur 1. Zeile
				$headline_prototype=getControlText($fields[$a]);
				$headline.=$headline_prototype;
				$headline_readOnly.=$headline_prototype;
			}
			$width=& $fields[$a]["width"];
			$height=& $fields[$a]["height"];
			fixMode($fields[$a]["mode"]);
			
			if (!isset($fields[$a]["pkField"])) {
				$fields[$a]["pkField"]=$fields[$a]["pkName"];
			}
			
			$fields[$a]["noOverlay"]=$settings["disable_molecule_mouseover"];
			$fields[$a]["useSvg"]=$useSvg;
			
			$line_prototype.="<input type=\"hidden\" name=".$name." id=".$name.$onChangeText.">";
			$commonParams=" width=\"".$width."\" height=\"".$height."\" onMouseover=\"showStructureTooltip(event,this,".$JSitemParams.")\" onMouseout=\"hideOverlay()\"";
			
			if (!$fields[$a]["noOverlay"] && ($fields[$a]["posFlags"] & OVERLAY_CONT_UPDATE)) {
				$commonParams.=" onMousemove=\"alignOverlay(event,".$fields[$a]["posFlags"].")\"";
			}
			
			$params=" id=\"".$unmasked_name."_img\"".$commonParams;
			$params_readOnly=" id=\"".$unmasked_name_readOnly."_img\"".$commonParams;
			if ($useSvg) {
				$line_prototype.="<object data=\"lib/1x1.svg\"".$params."></object>";
				$line_prototype_readOnly.="<object data=\"lib/1x1.svg\"".$params_readOnly."></object>";
			}
			else {
				$line_prototype.="<img src=\"lib/1x1.gif\"".$params.">";
				$line_prototype_readOnly.="<img src=\"lib/1x1.gif\"".$params_readOnly.">";
			}
			
		break;
		case "text":
			if ($paramHash["lines"]==1) { // nur 1. Zeile
				$headline_prototype_rw="";
				$headline_prototype_ro="";
				$headline_prototype="";
				if (isset($fields[$a]["freemodeHeadline"])) {
					$headline_prototype_rw=$paramHash["freeControls"][ $fields[$a]["freemodeHeadline"] ]["rw"];
					$headline_prototype_ro=$paramHash["freeControls"][ $fields[$a]["freemodeHeadline"] ]["ro"];
					unset($paramHash["freeControls"][ $fields[$a]["freemodeHeadline"] ]);
				}
				if (isset($fields[$a]["headline"])) {
					$headline_prototype=$fields[$a]["headline"];
				}
				else {
					$headline_prototype=$fields[$a]["value"];
				}
				$headline.=$headline_prototype.
					$headline_prototype_rw.
					$fields[$a]["headlineRw"];
				
				$headline_readOnly.=$headline_prototype.
					$headline_prototype_ro.
					$fields[$a]["headlineRo"];
			}
			
			$line_prototype.=$fields[$a]["rw"].$fields[$a]["value"];
			$line_prototype_readOnly.=$fields[$a]["ro"].$fields[$a]["value"];
		break;
		}
		// add set functions
		if (!empty($setFunction)) { // setFunction für Element hier NICHT mit setFunction für ganze Zeile (unten) verwechseln
			$paramHash["registerControls"].="controls[".fixStr($int_name)."][\"fields\"][".$a."][\"setFunction\"]=function(list_int_name,UID,pos,fieldIdx,int_name,group,values) {".addJSvar($setFunction)."};\n";
		}
		
		// sort buttons
		if ($fields[$a]["sortButtons"]) {
			$headline_prototype=" <table class=\"noborder\"><tr><td>".
				"<a href=\"javascript:void SILsort(".fixQuot($int_name).",".fixQuot($fields[$a]["int_name"]).$groupParamQuot.",0)\" class=\"noprint\"><img src=\"lib/up.png\" width=\"16\" height=\"14\" border=\"0\"".getTooltip("sort_up")."></a>".
				"</td><td>".
				"<a href=\"javascript:void SILsort(".fixQuot($int_name).",".fixQuot($fields[$a]["int_name"]).$groupParamQuot.",1)\" class=\"noprint\"><img src=\"lib/down.png\" width=\"16\" height=\"14\" border=\"0\"".getTooltip("sort_down")."></a>".
				"</td></tr></table>";
			$headline.=$headline_prototype;
			$headline_readOnly.=$headline_prototype;
		}
	}
	
	$headline_prototype="</td></tr>";
	$headline.=$headline_prototype;
	$headline_readOnly.=$headline_prototype;

	$line_prototype.="</td>";
	$line_prototype=addJSvar(fixStr($line_prototype));
	$line_prototype_readOnly.="</td>";
	$line_prototype_readOnly=addJSvar(fixStr($line_prototype_readOnly));
	
	$title=s("add_line");

	$paramHash["registerControls"].=
"controls[".fixStr($int_name)."][\"UIDs\"]=[];
controls[".fixStr($int_name)."][\"spareUIDs\"]=[];

SILresetControlData(".fixStr($int_name).");
controls[".fixStr($int_name)."][\"getLine\"]=function(UID) { return ".$line_prototype.";};
controls[".fixStr($int_name)."][\"getLineReadOnly\"]=function(UID) { return ".$line_prototype_readOnly.";};
controls[".fixStr($int_name)."][\"setFunction\"]=function(list_int_name,UID,pos,values) {".$paramHash["setFunction"]."};\n". // setFunction für ganze Zeile hier NICHT mit setFunction für Element (oben) verwechseln
"controls[".fixStr($int_name)."][\"lineInitFunction\"]=function(list_int_name,UID,pos,desired_action) { var values=new Array(); ".addJSvar($lineInitFunction)."};
controls[".fixStr($int_name)."][\"onBeforeAddLine\"]=function(list_int_name,UID,beforeUID) { var values=new Array(); ".addJSvar($paramHash["onBeforeAddLine"])."};
controls[".fixStr($int_name)."][\"onAddLine\"]=function(list_int_name,UID,pos) { var values=new Array(); ".addJSvar($paramHash["onAddLine"])."};
controls[".fixStr($int_name)."][\"lineDelFunction\"]=function(list_int_name,UID,pos) {".addJSvar($lineDelFunction)."};
controls[".fixStr($int_name)."][\"onBeforeDelete\"]=function(list_int_name,UID,pos) {".addJSvar($paramHash["onBeforeDelete"])."};\n";

	if (isset($paramHash["prepareData"])) {
		$paramHash["registerControls"].=
"controls[".fixStr($int_name)."][\"prepareData\"]=function(list_int_name,UID,pos,values) {".$paramHash["prepareData"]." return values; };\n";
	}
	
	if ($allowReorder && $paramHash["onListReordered"]) {
		$paramHash["registerControls"].="controls[".fixStr($int_name)."][\"onListReordered\"]=function(list_int_name) {".$paramHash["onListReordered"]."};\n";
	}
	
	// ------------------------------------------------------------rw-------------------------------------------------------------------------------------------
	
	if ($splitMode) {
		if ($paramHash["showHeadline"]) {
			$rwInput.="<tbody>".$headline."</tbody>";
		}
	}
	else {
		$rwInput.="<thead>".$headline."</thead>";
	}
	$rwInput.="\n<tbody id=\"tbody_".$int_name."\"></tbody>\n";
	if (!$paramHash["addButtonFree"]) {
		$rwInput.="<tbody id=\"add_button_".$int_name."\"><tr><td colspan=".fixStr($paramHash["cols"]).">"; // eigener tbody, damit button nicht gelöscht wird
	}
	
	if (!$paramHash["noManualAdd"]) {
		// add button
		$rwInput.="<table class=\"noborder\"><tr><td>".
			SILgetButton(array(
				"type" => "add_line", 
				"style" => ifempty($paramHash["buttonstyle"],$buttonstyle), 
				"quot_list_int_name" => $quot_list_int_name, 
				"int_name" => $int_name, 
				"noUID" => true, 
				"buttonText" => ifnotempty(" ",$paramHash["addText"]), 
			));
		
		// add buttons for multiple lines
		if ($paramHash["addMultipleButtons"]) {
			for ($a=0;$a<count($paramHash["addMultipleButtons"]);$a++) {
				$rwInput.="</td><td>".
					SILgetButton(array(
						"type" => "add_line", 
						"multiple" => $paramHash["addMultipleButtons"][$a], 
						"style" => ifempty($paramHash["buttonstyle"],$buttonstyle), 
						"quot_list_int_name" => $quot_list_int_name, 
						"int_name" => $int_name, 
						"noUID" => true, 
					));
			}
		}
		$rwInput.="</td></tr></table>";
	}
	
	if (!$paramHash["addButtonFree"]) {
		$rwInput.="</td></tr></tbody>";
	}
	
	if ($colgroup) {
		$colgroupRo.="</colgroup>";
		$colgroupRw.="</colgroup>";
	}
	// ------------------------------------------------------------ro-------------------------------------------------------------------------------------------
	
	if ($splitMode) {
		if ($paramHash["showHeadline"]) {
			$roInput.="<tbody>".$headline_readOnly."</tbody>";
		}
	}
	else {
		$roInput.="<thead>".$headline_readOnly."</thead>";
	}
	$roInput.="\n<tbody id=\"tbody_readOnly_".$int_name."\"></tbody>\n";
	
	if ($splitMode) {
		return array($roInput,$rwInput);
	}
	
	// ------------------------------------------------------------rw-------------------------------------------------------------------------------------------
	
	$retval.="<span id=\"rw_".$int_name."\" style=\"display:none\">"; // XX
	if (!empty($paramHash["text"])) {
		$retval.="<b>".$paramHash["text"]."</b><br>";
	}
	$retval.="<table id=\"".$int_name."\"".getClass($paramHash,false).">".$colgroupRw; // XX
	
	$retval.=$rwInput.$paramHash["rwInputs"];
	
	$retval.="\n</table><br clear=\"all\"></span>\n";
	
	// ------------------------------------------------------------ro-------------------------------------------------------------------------------------------
	
	$retval.="<span id=\"ro_".$int_name."\">"; // XX
	if (!empty($paramHash["text"])) {
		$retval.="<b>".$paramHash["text"]."</b><br>";
	}
	$retval.="<table".getClass($paramHash,true).">".$colgroupRo;
	
	$retval.=$roInput.$paramHash["roInputs"];
	
	$retval.="\n</table></span>\n";
	return $retval;
}

?>