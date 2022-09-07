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
require_once "lib_array.php";

$available_applets=array("VectorMol","ketcher","ketcher2","ChemDoodle","MarvinJS");
$available_rxn_applets=array("VectorMol","ketcher","ketcher2","ChemDoodle");

function getAppletSetting($mode="") {
	if ($mode=="rxn") {
		$key="applet_rxn_code";
	}
	else {
		$key="applet_code";
	}
	return getSetting($key);
}

function fixMode(& $mode) {
	switch ($mode) {
	case "rxn":
	case "tmpl":
	
	break;
	default:
		$mode="mol";
	}
}

function checkAppletDimensions(& $paramHash) {
	if (($paramHash["mode"]??null)=="rxn") {
		//~ $defaultH=315;
		//~ $defaultW=760;
		$defaultH=500;
		$defaultW=1000;
	}
	else {
		$defaultH=315;
		$defaultW=360;
	}
	$paramHash["height"]=ifempty($paramHash["height"]??"",$defaultH);
	$paramHash["width"]=ifempty($paramHash["width"]??"",$defaultW);
}

function getTemplateLoaderJS($settings_list) {
	if (is_array($settings_list)) foreach ($settings_list as $setting) {
		return "frameDoc.addTemplate(".json_encode(base64_encode(gzcompress($setting["molfile_blob"]))).");\n";
	}
}

function getAppletHTML1($paramHash=array()) { // part before name
	global $lang;
	
	if (empty($paramHash["force"]??"")) {
		$paramHash["force"]=getAppletSetting($paramHash["mode"]??null);
	}
	$commonParams=" type=\"application/x-java-applet;version=1.3\" mayscript=\"true\"";
	
	$retval="";
	switch ($paramHash["force"]??null) {
	case "ketcher":
		$retval.="<iframe src=\"ketcher/ketcher.html?mode=".($paramHash["mode"]??"")."\" id=";
	break;
	case "ketcher2":
		$retval.="<iframe src=\"ketcher2/ketcher.html?mode=".($paramHash["mode"]??"")."\" id=";
	break;
	case "ChemDoodle":
		$retval.="<iframe src=\"ChemDoodle/php/sketcher.php?mode=".($paramHash["mode"]??"")."\" id=";
	break;
	case "MarvinJS":
		$retval.="<iframe src=\"marvin4js/editor.html\" id=";
	break;
	case "text":
		$retval="<textarea rows=\"15\" cols=\"100\" id=";
	break;
	default:
	case "VectorMol":
		$retval.="<iframe src=\"VecMol/index.html?mode=".($paramHash["mode"]??"")."&lang=".$lang."&embedded=true\" onLoad=\"loadTemplates(this);\" id=";
	}
	if ($paramHash["percentSize"]??false) {
		$retval=addslashes($retval);
	}
	return $retval;
}

function getAppletHTML2($paramHash=array()) { // part after name
	global $settings,$g_settings,$lang;
	
	checkAppletDimensions($paramHash);
	
	if ($paramHash["percentSize"]??false) {
		$retval=" width=\\\"\"+Math.max(200,Math.floor(getInnerWidth()*".(($paramHash["width"]??10)/100)."))+\"\\\" height=\\\"\"+Math.max(200,Math.floor(getInnerHeight()*".(($paramHash["height"]??10)/100)."))+\"\\\"";
	}
	else {
		$retval=" width=\"".$paramHash["width"]."\" height=\"".$paramHash["height"]."\"";
	}
	
	$copyPasteText="<table class=\"noborder\"><tr>";
	if (($paramHash["copyPasteButtons"]??false) && !empty($paramHash["appletName"]??"")) {
		$copyPasteText.="<td>".getCopyButton($paramHash)."</td><td>".getPasteButton($paramHash)."</td>";
	}
	
	fixForce($paramHash);
	switch ($paramHash["force"]) {
	case "ketcher":
		$appletVal="></iframe>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://ggasoftware.com/opensource/ketcher\" target=\"_blank\">Ketcher</a> &copy; 2010-2011 GGA Software Services LLC, <a href=\"http://www.gnu.org/licenses/agpl.txt\" target=\"_blank\">AGPL v3</a>, GUI size shrunk by FR</span></td></tr></table>";
	break;
	case "ketcher2":
		$appletVal="></iframe>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"https://github.com/epam/ketcher\" target=\"_blank\">Ketcher</a> &copy; 2018 EPAM Systems, Inc, <a href=\"https://www.apache.org/licenses/LICENSE-2.0\" target=\"_blank\">Apache License 2.0</a></span></td></tr></table>";
	break;
	case "ChemDoodle":
		$appletVal="></iframe>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://web.chemdoodle.com/installation/license\" target=\"_blank\">ChemDoodle Sketcher</a> &copy;  2009-2020 iChemLabs, LLC, <a href=\"http://www.gnu.org/licenses/gpl.txt\" target=\"_blank\">GPL v3</a></span></td></tr></table>";
	break;
	case "MarvinJS":
		$appletVal="></iframe>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://www.chemaxon.com\" target=\"_blank\">Marvin</a> &copy; 1999-2015 ChemAxon Ltd. Test use only.</span></td></tr></table>";
	break;
	case "text":
		$appletVal="></textarea>".$copyPasteText."</tr></table>";
	break;
	case "VectorMol":
	default:
		$appletVal="></iframe>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://sciformation.com/vectormol.html?lang=".$lang."\" target=\"_blank\">VectorMol</a> &copy; 2012-2022 Sciformation Consulting GmbH</td></tr></table>";
	}
	
	if ($paramHash["percentSize"]??false) {
		$retval.=addslashes($appletVal);
	}
	else {
		$retval.=$appletVal;
	}
	
	return $retval;
}

function getAppletHTML($paramHash=array()) {
	global $settings,$g_settings;
	
	checkAppletDimensions($paramHash);
	$appletName=fixStr($paramHash["appletName"]??"");
	fixForce($paramHash);
	$retval="";
	if (endswith($paramHash["width"]??"","%") || endswith($paramHash["height"]??"","%")) {
		$paramHash["percentSize"]=true;
		$paramHash["width"]= getNumber($paramHash["width"]);
		$paramHash["height"]= getNumber($paramHash["height"]);
		$appletName=addslashes($appletName);
		$retval.=script."
document.write(\"";
	}
	$retval.=getAppletHTML1($paramHash).$appletName.getAppletHTML2($paramHash);
	if ($paramHash["percentSize"]) {
		$retval.="\");";
	} else {
		$retval.=script;
	}
	$retval.="\nfunction loadTemplates(domObj) {\nvar frameDoc=getApplet(domObj.id,\"VectorMol\");\n".getTemplateLoaderJS($g_settings["applet_templates"]??null).getTemplateLoaderJS($settings["applet_templates"]??null)."\n}"._script;
	return $retval;
}

function fixForce(& $paramHash) {
	if (empty($paramHash["force"]??"")) {
		$paramHash["force"]=getAppletSetting($paramHash["mode"]??null);
	}
}

function copyPasteAppletHelper($paramHash=array()) { // requires comm-frame, dont use this within other <form
	if (empty($paramHash["mode"]??"")) {
		$paramHash["mode"]="mol";
	}
	$clip_url=fixStr("clipAsync.php?".getSelfRef(array("~script~","db_id","molecule_id","reaction_id","reaction_chemical_id","timestamp")));
	echo "<form action=".$clip_url." target=\"comm\" name=\"copy\" method=\"post\" id=\"form_copy_molfile\">
<input type=\"hidden\" name=\"desired_action\" value=\"copy\">
<input type=\"hidden\" name=\"molfile_blob\" id=\"copy_molfile\">
<input type=\"hidden\" name=\"mode\" value=".fixStr($paramHash["mode"]).">
".getHiddenSubmit()."
</form>
<form action=".$clip_url." target=\"comm\" name=\"paste\" method=\"get\" id=\"form_paste_molfile\">
<input type=\"hidden\" name=\"desired_action\" value=\"paste\">
<input type=\"hidden\" name=\"force\" id=\"force\">
<input type=\"hidden\" name=\"mode\" value=".fixStr($paramHash["mode"]).">
<input type=\"hidden\" name=\"applet_name\" id=\"paste_molfile\">
".getHiddenSubmit()."
</form>
".script."
function copyMolfile(appletName,force) {
	$(\"copy_molfile\").value=";
	switch ($paramHash["mode"]) {
	case "rxn":
		echo "getRxnfile(appletName,force);";
	break;
	case "mol":
	default:
		echo "getMolfile(appletName,force);";
	}
	echo "
	$(\"form_copy_molfile\").submit();
}

function pasteMolfile(appletName,force) {
	$(\"paste_molfile\").value=appletName;
	$(\"force\").value=force;
	$(\"form_paste_molfile\").submit();
}
"._script;
}

function getCopyButton1() {
	return "<a href=\"javascript:copyMolfile(&quot;";
}

function getCopyButton2($paramHash=array()) {
	fixForce($paramHash);
	return "&quot;,".fixQuot($paramHash["force"]).")\" class=\"imgButtonSm\"><img src=\"lib/copy_sm.png\"".getTooltip("copy_structure")." border=\"0\"></a>";
}

function getCopyButton($paramHash=array()) {
	fixForce($paramHash);
	return getCopyButton1().$paramHash["appletName"].getCopyButton2($paramHash);
}

function getPasteButton1() {
	return "<a href=\"javascript:pasteMolfile(&quot;";
}

function getPasteButton2($paramHash=array()) {
	fixForce($paramHash);
	return "&quot;,".fixQuot($paramHash["force"]).")\" class=\"imgButtonSm\"><img src=\"lib/paste_sm.png\"".getTooltip("paste_structure")." border=\"0\"></a>";
}

function getPasteButton($paramHash=array()) {
	fixForce($paramHash);
	return getPasteButton1().$paramHash["appletName"].getPasteButton2($paramHash);
}

?>
