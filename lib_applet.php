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

$available_applets=array("VectorMol","ketcher","ChemDoodle","JME","ACD","Marvin","MarvinNew","MarvinJS","chemWriter","KL_applet","ChemDraw","SketchEl","JChemPaint","FlaME",); // "SymyxDraw",
$available_rxn_applets=array("VectorMol","ketcher","JME","Marvin","MarvinNew","KL_applet","ChemDraw",); // "FlaME","SymyxDraw","MarvinJS",

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
	if ($paramHash["mode"]=="rxn") {
		//~ $defaultH=315;
		//~ $defaultW=760;
		$defaultH=500;
		$defaultW=1000;
	}
	else {
		$defaultH=315;
		$defaultW=360;
	}
	$paramHash["height"]=ifempty($paramHash["height"],$defaultH);
	$paramHash["width"]=ifempty($paramHash["width"],$defaultW);
}

function getTemplateLoaderJS($settings_list) {
	if (is_array($settings_list)) foreach ($settings_list as $setting) {
		return "frameDoc.addTemplate(".fixStr(addPipes($setting["molfile_blob"])).");\n";
	}
}

function getAppletHTML1($paramHash=array()) { // part before name
	global $lang;
	
	if (empty($paramHash["force"])) {
		$paramHash["force"]=getAppletSetting($paramHash["mode"]);
	}
	$commonParams=" type=\"application/x-java-applet;version=1.3\" mayscript=\"true\"";
	
	$retval="";
	switch ($paramHash["force"]) {
	case "ketcher":
		$retval.="<iframe src=\"ketcher/ketcher.html?mode=".$paramHash["mode"]."\" id=";
	break;
	case "ChemDoodle":
		$retval.="<iframe src=\"ChemDoodle/sketcher.php\" id=";
	break;
	case "JME":
		$retval.="<applet code=\"JME.class\" archive=\"JME.jar\"".$commonParams." name=";
	break;
	case "JChemPaint":
		$retval.="<applet CODEBASE=\"JChemPaint\" code=\"org.openscience.jchempaint.applet.JChemPaintEditorApplet\" archive=\"jchempaint-applet-core.jar\"".$commonParams." name=";
	break;
	case "SketchEl":
		 $retval.="<applet code=\"SketchEl.MainApplet\" archive=\"SketchEl.jar\"".$commonParams." name=";
	break;
	case "chemWriter":
		 $retval.="<applet code=\"com/metamolecular/chemwriter/applet/EditorApplet.class\" archive=\"chemwriter.jar\"".$commonParams." name=";
	break;
	case "ACD":
		$retval.="<applet code=\"StructureEditorApplet.class\" codebase=\"ACD\"".$commonParams." name=";
	break;
	case "MarvinJS":
		$retval.="<iframe src=\"marvin4js/editor.html\" id=";
	break;
	case "Marvin":
	case "MarvinNew":
		$retval.="<applet code=\"chemaxon/marvin/applet/JMSketchLaunch\" archive=\"appletlaunch.jar\" codebase=\"marvin\" pluginspage=\"https://java.sun.com/javase/downloads/index.jsp\" type=\"application/x-java-applet;version=1.5\"".$commonParams." name=";
	break;
	case "SymyxDraw":
		$retval.="<applet codebase=\"SymyxJDraw\" ARCHIVE=\"CsInline.jar,jdrawcore.jar,jdrawapplet.jar\" code=\"com.symyx.draw.JDrawEditor\"".$commonParams." name=";
	break;
	case "FlaME":
		if (isMSIE()) {
			$retval="<object codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\" classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" name=";
		}
		else {
			$retval="<embed pluginspage=\"http://www.macromedia.com/go/getflashplayer\" type=\"application/x-shockwave-flash\" allowscriptaccess=\"sameDomain\" bgcolor=\"#ffffff\" quality=\"high\" src=\"flame.swf\" name=";
		}
	break;
	case "ChemDraw":
		if (isMSIE()) {
			$retval="<object classid=\"clsid:45C31980-E065-49A1-A3D7-E69CD40DAF66\" name=";
		}
		else {
			$retval="<embed src=\"Test.cdx\" type=\"chemical/x-cdx\" showtoolswhenvisible=\"1\" name=";
		}
	break;
	case "text":
		$retval="<textarea rows=\"15\" cols=\"100\" id=";
	break;
	case "KL_applet":
		$retval.="<applet ARCHIVE=\"imes-v0-r540.jar\" code=\"main/Main.class\"".$commonParams." name=";
	break;
	default:
	case "VectorMol":
		$retval.="<iframe src=\"VecMol/index.html?mode=".$paramHash["mode"]."&lang=".$lang."&embedded=true\" onLoad=\"loadTemplates(this);\" id=";
	}
	if ($paramHash["percentSize"]) {
		$retval=addslashes($retval);
	}
	return $retval;
}

function getAppletHTML2($paramHash=array()) { // part after name
	global $settings,$g_settings,$lang;
	
	checkAppletDimensions($paramHash);
	
	if ($paramHash["percentSize"]) {
		$retval=" width=\\\"\"+Math.max(200,Math.floor(getInnerWidth()*".($paramHash["width"]/100)."))+\"\\\" height=\\\"\"+Math.max(200,Math.floor(getInnerHeight()*".($paramHash["height"]/100)."))+\"\\\"";
	}
	else {
		$retval=" width=\"".$paramHash["width"]."\" height=\"".$paramHash["height"]."\"";
	}
	
	$copyPasteText="<table class=\"noborder\"><tr>";
	if ($paramHash["copyPasteButtons"] && !empty($paramHash["appletName"])) {
		$copyPasteText.="<td>".getCopyButton($paramHash)."</td><td>".getPasteButton($paramHash)."</td>";
	}
	
	fixForce($paramHash);
	switch ($paramHash["force"]) {
	case "ketcher":
		$appletVal="></iframe>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://ggasoftware.com/opensource/ketcher\" target=\"_blank\">Ketcher</a> Copyright &copy; 2010-2011 GGA Software Services LLC, <a href=\"http://www.gnu.org/licenses/agpl.txt\" target=\"_blank\">AGPL v3</a>, GUI size shrunk by FR</span></td></tr></table>";
	break;
	case "ChemDoodle":
		$appletVal="></iframe>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://web.chemdoodle.com/installation/license\" target=\"_blank\">ChemDoodle Sketcher</a> Copyright &copy; 2008 iChemLabs, LLC, <a href=\"http://www.gnu.org/licenses/gpl.txt\" target=\"_blank\">GPL v3</a></span></td></tr></table>";
	break;
	case "JME":
		$appletVal="><param name=\"options\" value=\"".($paramHash["mode"]=="rxn"?"reaction,":"")."multipart\"></applet>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://www.molinspiration.com/jme/index.html\" target=\"_blank\">JME Editor</a> courtesy of Peter Ertl, Novartis</span></td></tr></table>"; // ,nostereo
	break;
	case "JChemPaint":
		$appletVal="></applet>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://sourceforge.net/projects/cdk/files/JChemPaint/\" target=\"_blank\">JChemPaint</a></span></td></tr></table>";
	break;
	case "SketchEl":
		$appletVal="></applet>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://sketchel.sourceforge.net\" target=\"_blank\">SketchEl</a> was created by Dr. Alex M. Clark</span></td></tr></table>";
	break;
	case "chemWriter":
		$appletVal="></applet>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://metamolecular.com\" target=\"_blank\">ChemWriter</a> &copy; 2007,2008 Metamolecular LLC. Test use only.</span></td></tr></table>"; // ,nostereo
	break;
	case "ACD":
		$appletVal="><param name=\"toolbarImg\" value=\"images/toolbars.gif\"><param name=\"templates\" value=\"data/templates\"></applet>".$copyPasteText."</tr></table>";
	break;
	case "MarvinJS":
		$appletVal="></iframe>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://www.chemaxon.com\" target=\"_blank\">Marvin</a> &copy; 1999-2015 ChemAxon Ltd. Test use only.</span></td></tr></table>";
	break;
	case "Marvin":
		// no extra template parameters here
		$appletVal="></applet>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://www.chemaxon.com\" target=\"_blank\">Marvin</a> &copy; 1998-2015 Chemaxon Ltd. Test use only.</span></td></tr></table>";
	break;
	case "MarvinNew":
		$appletVal="><param name=\"molbg\" value=\"#ffffff\"><param name=\"isMyTemplatesEnabled\" value=\"false\"><param name=\"preload\" value=\"sketchbackground,reactionquerydrawing,icons\"><param name=\"ttmpls0\" value=\"Genericchemaxon/marvin/templates/generic.t\"><param name=\"ttmpls1\" value=\"Ringschemaxon/marvin/templates/rings.t\"><param name=\"customizationEnabled\" value=\"false\"><param name=\"menubar\" value=\"true\"></applet>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://www.chemaxon.com\" target=\"_blank\">Marvin</a> &copy; 1998-2015 Chemaxon Ltd. Test use only.</span></td></tr></table>";
	break;
	case "SymyxDraw":
		//  <param name=\"OnStructureChangedJS\" value=\"alert('OnStructureChanged event fired: javascript executed');\">
		// <param value=\"-Xmx256m -Dsun.java2d.noddraw=true\" name=\"java_arguments\">
		$appletVal="></applet>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://www.chemaxon.com\" target=\"_blank\">JDrawApplet</a> &copy; 2008-2009 Symyx Solutions Inc. Test use only.</span></td></tr></table>";
	break;
	case "FlaME":
		if (isMSIE()) {
			$appletVal="><param value=\"sameDomain\" name=\"allowScriptAccess\"><param value=\"flame.swf\" name=\"movie\"><param value=\"high\" name=\"quality\"><param value=\"#ffffff\" name=\"bgcolor\"></object>".$copyPasteText."</tr></table>";
		}
		else {
			$appletVal="><noembed>Cannot display plugin.</noembed>";
		}
		$appletVal.=$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://synthon.pch.univie.ac.at/flame/\" target=\"_blank\">FlaME</a> &copy; 2011 Pavel Dallakian and Norbert Haider</span></td></tr></table>";
	break;
	case "ChemDraw":
		if (isMSIE()) {
			$appletVal="><param name=\"SourceURL\" value=\"Test.cdx\"><param name=\"Showtoolswhenvisible\" value=\"1\"></object>";
		}
		else {
			$appletVal="><noembed>Cannot display plugin.</noembed>";
		}
		$appletVal.=$copyPasteText."</tr></table>";
	break;
	case "text":
		$appletVal="></textarea>".$copyPasteText."</tr></table>";
	break;
	case "KL_applet":
		$appletVal="><param name=\"templateCreationMode\" value=\"".($paramHash["mode"]=="template"?"1":"0")."\"><param name=\"rxnMode\" value=\"".($paramHash["mode"]=="rxn"?"1":"0")."\"><param name=\"restActivated\" value=\"".($paramHash["searchMode"]?"1":"0")."\"><param name=\"compactMode\" value=\"".($paramHash["compactMode"]?"1":"0")."\">";
		// templates
		$templates=arr_merge($g_settings["applet_templates"],$settings["applet_templates"]);
		for ($a=0;$a<count($templates);$a++) {
			$appletVal.="<param name=\"template".$a."\" value=".fixStr(addPipes($templates[$a]["molfile_blob"])).">";
		}
		$appletVal.="</applet>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\">Copyright 2007-2010 Otmar Ginkel, TU Kaiserslautern</span><a href=\"imes_".$lang.".pdf\" target=\"_blank\"><img src=\"lib/help_sm.png\"".getTooltip("help")." border=\"0\"></a></td></tr></table>";
	break;
	case "VectorMol":
	default:
		$appletVal="></iframe>".$copyPasteText."<td style=\"background-color:white;color:black\"><span class=\"very_small\"><a href=\"http://sciformation.com/vectormol.html?lang=".$lang."\" target=\"_blank\">VectorMol</a> Copyright &copy; 2012-2013 Sciformation Consulting GmbH</td></tr></table>";
	}
	
	if ($paramHash["percentSize"]) {
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
	$appletName=fixStr($paramHash["appletName"]);
	fixForce($paramHash);
	if (endswith($paramHash["width"],"%") || endswith($paramHash["height"],"%")) {
		$paramHash["percentSize"]=true;
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
	$retval.="\nfunction loadTemplates(domObj) {\nvar frameDoc=getApplet(domObj.id,\"VectorMol\");\n".getTemplateLoaderJS($g_settings["applet_templates"]).getTemplateLoaderJS($settings["applet_templates"])."\n}"._script;
	return $retval;
}

function fixForce(& $paramHash) {
	if (empty($paramHash["force"])) {
		$paramHash["force"]=getAppletSetting($paramHash["mode"]);
	}
}

function copyPasteAppletHelper($paramHash=array()) { // requires comm-frame, dont use this within other <form
	if (empty($paramHash["mode"])) {
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
