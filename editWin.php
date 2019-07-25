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
/*
Fenster zum Editieren einer Struktur. Diese wird 100 msec verzögert geladen, weil sonst das Applet manchmal noch nicht da ist
*/
require_once "lib_global_funcs.php";
require_once "lib_constants.php";
require_once "lib_formatting.php";
require_once "lib_db_manip_helper.php";
require_once "lib_form_elements.php";

pageHeader();

//~ print_r($_REQUEST);

switch ($_REQUEST["mode"]) {

case "print_label":
	if (!filenameSafe($_REQUEST["table"])) {
		die("Not allowed");
	}
	$path="forms/dymo/".$_REQUEST["table"].".label";
	if (!pathSafe($path)) {
		die("Not allowed");
	}
	echo loadJS(array("DYMO.Label.Framework.3.0.js","edit.js","safety.js",),"lib/").
"<title>".s("print_dymo_label")."</title></head><body>".s("click_label_to_print")."<br/><img id=\"preview\" onClick=\"printLabel();\"/>".
script."
var printerName,label;
function initPrinters() {
	var labelXml=".json_encode(str_replace("\t","",file_get_contents($path))).";
	printerName=getPrinterName();
	label=dymo.label.framework.openLabelXml(labelXml);

	if (opener) {
		// set label texts
		var dataset=opener.dataCache[opener.a_db_id][opener.a_pk],templateSep=\"$\",placeholder1=\"{\",placeholder2=\"}\",objSep=\"#\",objectNames=label.getObjectNames(),baseURL=location.href;
		var slashPos=baseURL.lastIndexOf(\"/\");
		if (slashPos>=0) {
			baseURL=baseURL.substr(0,slashPos+1);
		}
		namesLoop: for (var i=0,max_i=objectNames.length;i<max_i;i++) {
			var objectName=objectNames[i],templateParts=objectName.split(templateSep),cmd=\"\",symbolIndex;
			if (templateParts.length==1) {
				// simple case, create auto-pattern
				templateParts.unshift(placeholder1+\"1\"+placeholder2);
			} else {
				cmd=templateParts.shift();
			}
			if (cmd==\"safety_sym\") {
				// get index to take
				symbolIndex=parseInt(templateParts.shift());
			}
			for (var j=1,max_j=templateParts.length;j<max_j;j++) {
				if (templateParts[j]==\"##cookie##\") {
					value=getCookie(\"enventory\");
				}
				else {
					var objectNameParts=templateParts[j].split(objSep);
					var value=dataset;
					while (is_array(value) && objectNameParts.length>0) {
						// read recursively
						value=value[objectNameParts.shift()];
						if (value==undefined) {
							// not found
							continue namesLoop;
						}
					}
				}
				templateParts[0]=templateParts[0].replace(placeholder1+j+placeholder2,value);
			}
			if (cmd==\"safety_sym\") {
				if (symbolIndex==undefined) continue namesLoop;
				// transform
				var arrSym=splitSymbols(templateParts[0]);
				if (symbolIndex>=arrSym.length) continue namesLoop;
				templateParts[0]=getSymbolFilename(\"ghs\",arrSym[symbolIndex]);
				cmd=\"img\";
			}
			if (cmd==\"img\") {
				try {
					templateParts[0]=dymo.label.framework.loadImageAsPngBase64(baseURL+templateParts[0]);
				}
				catch (err) {
					continue namesLoop;
				}
			}
			else if (cmd==\"eval\") {
				templateParts[0]=eval(templateParts[0]);
			}
			labelSetObjectText(label,objectName,templateParts[0]);
		}

		// finally render and display
		var pngData=label.render(),labelImage=$(\"preview\");
		labelImage.src=\"data:image/png;base64,\" + pngData;
	}
}
function frameworkInitShim() {
	if (typeof dymo != \"undefined\") {
		if (dymo.label.framework.init) {
			//alert(1); // enable this line for placing breakpoints in the Javascript debugger
			dymo.label.framework.trace=1;
			dymo.label.framework.init(initPrinters); // init, then invoke a callback
		} else {
			// try directly
			initPrinters();
		}
	}
}
function printLabel() {
	if (printerName) {
		label.print(printerName);
		// self.close();
	}
}
window.onload=frameworkInitShim;
"._script;
break;
case "substance_report":
	list($reaction_chemical)=mysql_select_array(array("table" => "reaction_chemical", "dbs" => $_REQUEST["db_id"], "filter" => "reaction_chemical_id=".fixNull($_REQUEST["reaction_chemical_id"])));
	echo loadJS(array("controls.js","jsDatePick.min.1.3.js","forms.js","edit.js"),"lib/").
	loadJS(array("wyzz.js")). // wyzz
		stylesheet."
		</head>
		<body>".
		getFormElements(array(
			"noFieldSet" => true, 
			READONLY => false, 
			"no_db_id_pk" => true, 
			"int_name" => "archive_version", 
		), 
		array(
			// equation
			array("item" => "text", "text" => "<label for=\"checkbox_rxn\"><input type=\"checkbox\" id=\"checkbox_rxn\" checked=\"checked\" class=\"noprint\" onClick=\"visibleObj(&quot;rxn_structure&quot;,this.checked);\">".s("rxn_structure").":</label><br/>
				<div id=\"rxn_structure\"><img src=\"getGif.php?db_id=".$reaction_chemical["db_id"]."&reaction_id=".fixNull($reaction_chemical["reaction_id"])."\"></div>
				<img src=\"getGif.php?db_id=".$reaction_chemical["db_id"]."&reaction_chemical_id=".fixNull($reaction_chemical["reaction_chemical_id"])."\">"), 
			"tableStart",
			array("item" => "input", "int_name" => "standard_name", ),
			array("item" => "input", "int_name" => "emp_formula", ),
			array("item" => "input", "int_name" => "m_brutto", DEFAULTREADONLY => "always", "showAlways" => true, ),
			array("item" => "input", "int_name" => "mass_unit", SPLITMODE => true, DEFAULTREADONLY => "always", "showAlways" => true, ),
			array("item" => "input", "int_name" => "yield", DEFAULTREADONLY => "always", "showAlways" => true, ),
			array("item" => "input", "int_name" => "emp_formula", ),
			array("item" => "input", "int_name" => "colour", ),
			array("item" => "input", "int_name" => "consistency", ),
			array("item" => "input", "int_name" => "description", "type" => "textarea_classic", "text" => s("rc_description"),),
			array("item" => "text", "text" => "<tr><td colspan=\"2\" class=\"noprint\"><a href=\"Javascript:saveChanges();\" class=\"imgButtonSm\"><img src=\"lib/save_sm.png\" border=\"0\"".getTooltip("save_changes")."></a></td></tr>", "skip" => $_REQUEST["readOnly"]!="false"), 
			array("item" => "text", "id" => "reaction_analytics", "text" => "<tr><td id=\"reaction_analytics\" colspan=\"2\" class=\"noprint\"></td></tr>", ), 
			// color, consistency, description, saveable, button to save
			"tableEnd",
		)).
		script.'
function saveChanges() {
	if (!opener) {
		return;
	}
	
	for (var b=0,max=int_names.length;b<max;b++) {
		int_name=int_names[b];
		opener.SILsetValue(getInputValue(int_name),list_int_name,UID,int_name,group);
	}
	
	opener.valChanged();
}

top.document.title='.fixStr(s("substance_report1")).'+opener.getInputValue("lab_journal_code")+opener.getInputValue("nr_in_lab_journal")+'.fixStr(s("substance_report2").getGermanDate(null,true).s("substance_report3")).';

if (opener) {
	var list_int_name='.fixStr($_REQUEST["list_int_name"]).',UID='.fixStr($_REQUEST["UID"]).',int_name,group='.fixStr($_REQUEST["group"]).',int_names=["standard_name","emp_formula","colour","consistency","description"];
	for (var b=0,max=int_names.length;b<max;b++) {
		int_name=int_names[b];
		setInputValue(int_name,opener.SILgetValue(list_int_name,UID,int_name,group));
	}
	
	setiHTML("value_m_brutto",round(opener.SILgetValue(list_int_name,UID,"m_brutto",group),3,4));
	setiHTML("value_mass_unit",opener.SILgetValue(list_int_name,UID,"mass_unit",group));
	setiHTML("value_yield",round(opener.SILgetValue(list_int_name,UID,"yield",group),3,4));
	
	// ask opener to load analytical_data, if not yet done so
	var analytical_data_int_name="analytical_data";
	if (opener.readOnly) {
		var oldView=opener.currentView;
		opener.activateEditView("analytics");
		opener.activateEditView(oldView);
	}
	
	// checkboxes by javascript to show/hide spectra
	var reaction_chemical_id=opener.SILgetValue(list_int_name,UID,"reaction_chemical_id",group),checkboxes="'.s("reaction_analytics").':",analytical_data_list="";
	for (var b=0,max=opener.controlData[analytical_data_int_name]["UIDs"].length;b<max;b++) {
		var analytical_data_UID=opener.controlData[analytical_data_int_name]["UIDs"][b];
		var reaction_chemical_uid=opener.SILgetValue(analytical_data_int_name,analytical_data_UID,"reaction_chemical_uid"),analytical_data_id=opener.SILgetValue(analytical_data_int_name,analytical_data_UID,"analytical_data_id"),analytical_data_text;
		if (reaction_chemical_uid==="" || reaction_chemical_uid==UID || reaction_chemical_uid==reaction_chemical_id) {
			// mixture or assigned to this product
			analytical_data_text=joinIfNotEmpty([opener.SILgetValue(analytical_data_int_name,analytical_data_UID,"analytics_type_name"),opener.SILgetValue(analytical_data_int_name,analytical_data_UID,"analytics_device_name"),opener.SILgetValue(analytical_data_int_name,analytical_data_UID,"analytics_method_name"),opener.SILgetValue(analytical_data_int_name,analytical_data_UID,"analytical_data_identifier")]," / ");
			checkboxes+="</br><label for=\"checkbox_"+b+"\"><input type=\"checkbox\" id=\"checkbox_"+b+"\" checked=\"checked\" onClick=\"visibleObj(&quot;analytical_data_"+b+"&quot;,this.checked);\">"+analytical_data_text+"</label>";
			analytical_data_list+="<div id=\"analytical_data_"+b+"\" style=\"page-break-before:always\">"+analytical_data_text+"<br/><img src=\"getGif.php?db_id="+opener.a_db_id+"&analytical_data_id="+analytical_data_id+"\"><br/>"+opener.SILgetValue(analytical_data_int_name,analytical_data_UID,"analytical_data_interpretation")+"<br/>"+opener.SILgetValue(analytical_data_int_name,analytical_data_UID,"analytical_data_comment")+"<hr/></div>";
		}
	}
	setiHTML("reaction_analytics",checkboxes);
	document.write(analytical_data_list);
}
'._script;
	
break;

case "add_dois":
	switch ($_REQUEST["table"]) {
	case "project":
		$list_int_name="project_literature";
	break;
	case "reaction":
		$list_int_name="reaction_literature";
	break;
	}
	
	echo "<title>".s("add_lit_by_doi")."</title>".
		stylesheet.
		script.
		"
function okClicked() {
	if (!opener) {
		return;
	}
	var url=\"chooseAsync.php?desired_action=add_lit_by_doi&list_int_name=".$list_int_name."&dois=\"+encodeURIComponent(getInputValue(\"dois\"));
	opener.setFrameURL(\"comm\",url);
	opener.showMessage(s(\"please_wait\"));
	self.close();
}
".
		_script.
		"</head><body>";
	showCommFrame(); // to parse uploaded files
	echo "<form name=\"main\" onSubmit=\"okClicked(); return false;\" method=\"get\">
<table class=\"noborder\"><tr><td colspan=\"2\">
".s("enter_dois")."
<br>
<textarea id=\"dois\" name=\"dois\" rows=\"15\" cols=\"30\"></textarea>
<br>
".s("responsible_dois")."
</td></tr>
<tr><td>
<input type=\"button\" value=".fixStr(s("ok"))." onClick=\"okClicked()\">
</td><td>
<input type=\"button\" value=".fixStr(s("cancel"))." onClick=\"self.close()\">
</td></tr></table>
</form>
<form name=\"load_txt_form\" id=\"load_txt_form\" action=\"chooseAsync.php?desired_action=parse_doi_txt\" target=\"comm\" method=\"post\" enctype=\"multipart/form-data\">".
s("parse_doi_txt").
"<input type=\"file\" name=\"load_txt\" id=\"load_txt\" onChange=\"submitForm(&quot;load_txt_form&quot;);\" style=\"width:280px\">
</form>
".
script."
focusInput(\"dois\");
"._script;
	
break;

case "custom_list": // edit custom list of columns for list view, in settings
	list($fields)=getFields($columns[ $_REQUEST["table"] ],"all");
	list($default_visible)=getFields($columns[ $_REQUEST["table"] ],"");
	
	$list_data=array();
	$list_data["fields"]=array();
	for ($a=0;$a<count($fields);$a++) {
		$list_data["fields"][$a]=array("field" => $fields[$a], "localized_field" => s($fields[$a]), );
	}
	
	echo loadJS(array("controls.js","jsDatePick.min.1.3.js","forms.js","edit.js","molecule_edit.js","searchpk.js","subitemlist_helper.js","subitemlist_get.js","subitemlist.js","settings.js",),"lib/").
		"<title>".s("fields_activate")."</title>".
		stylesheet.
		"</head><body>".
		getHelperTop().
		getFormElements(
			array(
				"noFieldSet" => true, 
				READONLY => false, 
				"no_db_id_pk" => true, 
				"int_name" => "fields_activate", 
				//~ "checkSubmit" =>
					//~ 'if (getControlValue("key")=="") { '
						//~ .'alert("'.s("error_key").'");'
						//~ .'focusInput("key"); '
						//~ .'return false;'
					//~ .'} ', 
			), 
			array(
				array("item" => "text", "text" => "<table class=\"subitemlist\"><tr><td>"), 
				array("item" => "input", "int_name" => "key", "onChange" => "updateViewName(); ", ), 
				array(
					"item" => "text", 
					"text" => "</td><td style=\"text-align:right\">".getImageLink(array(
						"url" => "javascript:self.close();", 
						"a_class" => "imgButtonSm", 
						"src" => "lib/save_sm.png", 
						"l" => "save_changes", 
					))."</td></tr></table>", 
				), 
				array(
					"item" => "subitemlist", 
					"int_name" => "fields", 
					"noManualAdd" => true, 
					"noManualDelete" => true, 
					"onBeforeDelete" => "return false;", 
					"fields" => array(
						array("item" => "cell"), 
						array("item" => "input", "int_name" => "localized_field", "text" => s("field"), DEFAULTREADONLY => "always", ),
						array("item" => "hidden", "int_name" => "field", ),
						
						array("item" => "cell"), 
						array("item" => "checkbox", "int_name" => "active", "text" => s("active"), "value" => 1, "onChange" => "updateFieldList", ),
						
						array("item" => "cell", "style" => "display:none", ), 
						array("item" => "links", ), 
					), 
				),
			)
		).
		script."
setControlValues(".json_encode($list_data).",false);

if (opener) {
	var opener_list_int_name=".fixStr($_REQUEST["list_int_name"]).",opener_UID=".fixStr($_REQUEST["UID"]).";
	var fields=opener.SILgetValue(opener_list_int_name,opener_UID,\"fields\");
	if (fields==\"\") {
		fields=".json_encode($default_visible).";
	}
	else {
		fields=fields.split(\",\");
		for (var b=0,max=fields.length;b<max;b++) {
			fields[b]=trim(fields[b]);
		}
	}
	
	var list_int_name=\"fields\",int_name1=\"field\",int_name2=\"active\";
	var fieldIdx1=SILgetFieldIndex(list_int_name,int_name1);
	for (var b=0,max=controlData[list_int_name][\"UIDs\"].length;b<max;b++) {
		var UID=controlData[list_int_name][\"UIDs\"][b];
		if (in_array(SILgetValue(list_int_name,UID,int_name1),fields)) {
			SILsetChecked(true,list_int_name,UID,int_name2);
		}
	}
	
	setInputValue(\"key\",opener.SILgetValue(opener_list_int_name,opener_UID,\"key\"));
	
	updateFieldList(list_int_name);
}"._script;
	
	
break;

case "archive_version": // create new snapshot(s)
	$template_count=count($settings["archive_comments"]);
	$min_size=2;
	$max_size=8;
	$size=constrainVal($template_count,$min_size,$max_size);
	
	echo loadJS(array("controls.js","jsDatePick.min.1.3.js","forms.js","edit.js"),"lib/").
	script.
	"function performVersion() {
	if (opener) {
		opener.performNewVersion(getChecked(\"version_before\"),getInputValue(\"version_comment_before\"),getChecked(\"version_after\"),getInputValue(\"version_comment_after\"));
	}
	self.close();
}".
	_script.
	"<title>".s("save_version")."</title><link href=\"style.css.php\" rel=\"stylesheet\" type=\"text/css\">
</head>
<body>".
	getFormElements(
	array(
		"noFieldSet" => true, 
		READONLY => false, 
		"no_db_id_pk" => true, 
		"int_name" => "archive_version", 
		"onLoad" => 'setChecked("version_before",true); ', 
	), 
	array(
		array("item" => "text", "text" => "<table class=\"hidden\"><tbody><tr><td>"), 
		"tableStart",
		
		array(
			"item" => "text", 
			"int_name" => "button", 
			"text" => getImageLink(array(
				"url" => "javascript:performVersion()", 
				"a_class" => "imgButtonSm", 
				"src" => "lib/version_sm.png", 
				"l" => "save_version", 
			)), 
		), 
		array(
			"item" => "text", 
			"int_name" => "enter_comment", 
		), 
		"tableEnd",
		array("item" => "text", "text" => "</td><td>"), 
		// save version before
		"tableStart",
		array("item" => "check", "int_name" => "version_before", "onChange" => "if (!this.checked) { setChecked(&quot;version_after&quot;,true); }", ),
		array("item" => "input", "int_name" => "version_comment_before", "onChange" => "setChecked(&quot;version_before&quot;,true);", ),
		array("item" => "select", "size" => $size, "skip" => !$template_count, "int_name" => "version_comment_before_list", "onChange" => "setInputValue(&quot;version_comment_before&quot;,this.value); setChecked(&quot;version_before&quot;,true);", "int_names" => $settings["archive_comments"], "texts" => $settings["archive_comments"], ),
		"tableEnd",
		array("item" => "text", "text" => "</td><td>"), 
		// save version after
		"tableStart",
		array("item" => "check", "int_name" => "version_after", "onChange" => "if (!this.checked) { setChecked(&quot;version_before&quot;,true); }", ),
		array("item" => "input", "int_name" => "version_comment_after", "onChange" => "setChecked(&quot;version_after&quot;,true);", ),
		array("item" => "select", "size" => $size, "skip" => !$template_count, "int_name" => "version_comment_after_list", "onChange" => "setInputValue(&quot;version_comment_after&quot;,this.value); setChecked(&quot;version_after&quot;,true);", "int_names" => $settings["archive_comments"], "texts" => $settings["archive_comments"], ),
		"tableEnd",
		array("item" => "text", "text" => "</td></tr></tbody></table>"), 
	
	));
break;

case "edit_rc": // type custom name/CAS for chemical
	// show standard_name,package_name,cas_nr for editing
	echo loadJS(array("controls.js","jsDatePick.min.1.3.js","forms.js","edit.js"),"lib/").
		"<title>".s("edit_rc")."</title>".
		stylesheet."
		</head>
		<body>".
		getFormElements(array(
			"noFieldSet" => true, 
			READONLY => false, 
			"no_db_id_pk" => true, 
			"int_name" => "archive_version", 
		), 
		array(
			"tableStart",
			array("item" => "input", "int_name" => "standard_name", ),
			array("item" => "input", "int_name" => "package_name", "skip" => $_REQUEST["list_int_name"]=="products", ),
			array("item" => "input", "int_name" => "cas_nr", ),
			array("item" => "check", "int_name" => "clear_structure", ),
			
			array(
				"item" => "text", 
				"int_name" => "button", 
				"text" => getImageLink(array(
					"url" => "javascript:updateRc()", 
					"a_class" => "imgButtonSm", 
					"src" => "lib/save_sm.png", 
					"l" => "save", 
				)), 
			), 
			"tableEnd",
		)).
		script."
function updateRc() {
	if (!opener) {
		return;
	}
	
	for (var b=0,max=int_names.length;b<max;b++) {
		int_name=int_names[b];
		opener.SILsetValue(getInputValue(int_name),list_int_name,UID,int_name,group);
	}
	
	// clear away references
	for (var b=0,max=clear.length;b<max;b++) {
		opener.SILsetValue(\"\",list_int_name,UID,clear[b],group);
	}
	
	if (list_int_name==\"copyTable\") {
		opener.SILsetValue(\"\",list_int_name,UID,\"reaction_chemical_id\",group);
	}
	
	for (var b=0,max=clearOptions.length;b<max;b++) {
		opener.SILclearOptions(list_int_name,UID,clearOptions[b],group,true);
	}
	
	// also set spans for display
	var values=[];
	values[\"chemical_storage_barcode\"]=\"\";
	opener.SILsetValueUID(list_int_name,UID,undefined,undefined,\"chemical_storage_barcode\",group,values);
	opener.SILsetSpan(getInputValue(\"standard_name\"),list_int_name,UID,\"info1\",group);
	opener.SILsetSpan(getInputValue(\"package_name\"),list_int_name,UID,\"info2\",group);
	opener.SILsetDesiredAction(list_int_name,UID,\"update\"); // assume something has changed
	
	if (getChecked(\"clear_structure\")) {
		opener.delStruct(list_int_name,UID,\"molfile_blob\",group);
	}
	
	opener.valChanged();
	self.close();
}

if (opener) {
	
	var ";
	if ($_REQUEST["list_int_name"]=="products") {
		echo "int_names=[\"standard_name\",\"cas_nr\"],clearOptions=[\"molecule_id\"],clear=[\"other_db_id\"],";
	}
	else {
		echo "int_names=[\"standard_name\",\"package_name\",\"cas_nr\"],clearOptions=[\"molecule_id\",\"chemical_storage_id\"],clear=[\"other_db_id\",\"from_reaction_id\",\"from_reaction_chemical_id\"],";
	}
	echo "list_int_name=".fixStr($_REQUEST["list_int_name"]).",UID=".fixStr($_REQUEST["UID"]).",int_name,group=".fixStr($_REQUEST["group"]).";
	for (var b=0,max=int_names.length;b<max;b++) {
		int_name=int_names[b];
		setInputValue(int_name,opener.SILgetValue(list_int_name,UID,int_name,group));
	}
}
"._script;
break;

case "response_factor": // helper to calc response factor, not done yet
	// choose device,method

	// Liste der GCs	| Auswahl Produkt	| Auswahl Standard | Verhältnis Einwaagen	| Verhältnis Messung	| Response factor
	// GC 1,2,3		|	Einwaage mg	| Einwaage mg		| 					| 					| 0.x
	
	// Durchschnitt = x
	// Für alle GCs mit device,method in Zeitraum x-y setzen
break;

/* case "spec":
	echo "<title>".s("view_spectrum")."
</title>
<link href=\"style.css.php\" rel=\"stylesheet\" type=\"text/css\">
</head>
<body><applet code=\"jspecview/applet/JSVApplet.class\" archive=\"jspecview.jar\" codebase=\"JSpecView\" name=\"JSpecView\"><param name=\"load\" value=\"BLAAF41h-b.jdx\"></applet>";
break; */

case "sds": // show list of available safety data sheets
	if (count($_FILES["load_sds"]) && $_FILES["load_sds"]["error"]==0) { // upload
		// move to temp dir
		$tmpdir=oe_get_temp_dir();
		$filename=oe_tempnam($tmpdir,"sds");
		@unlink($filename);
		rename($_FILES["load_sds"]["tmp_name"],$filename);
		@chmod($filename,0755);
		
		// save filename (without path) in parent form together with text entered as source and close
		echo script.
		"opener.setControl(".fixStr($_REQUEST["int_name"]).",{".
			fixStr($_REQUEST["int_name"]."_url").":".fixStr("+".cutFilename($filename)).",".
			fixStr($_REQUEST["int_name"]."_by").":".fixStr($_REQUEST["supplier"]).",".
			fixStr($_REQUEST["int_name"]."_mime").":".fixStr($_FILES["load_sds"]["type"]).
		"});
opener.valChanged();
self.close();
".
		_script;
	}
	else {
		if (startswith($_REQUEST["int_name"],"alt_")) {
			$this_lang=ifempty($g_settings["alt_safety_sheet_lang"],$lang);
		}
		else {
			$this_lang=ifempty($g_settings["safety_sheet_lang"],$lang);
		}
		
		echo "<title>".s("choose_SDS")."</title>".
			stylesheet.
			script."
function addToMol(url,supplier) {
	opener.setControl(".fixStr($_REQUEST["int_name"]).",{".fixStr($_REQUEST["int_name"]."_url").":\"-\"+url,".fixStr($_REQUEST["int_name"]."_by").":supplier});
	opener.valChanged();
	self.close();
}

function checkMSDSUpload() {
	if (!$(\"supplier\").value) {
		alert(".fixStr(s("upload_msds_no_supplier")).");
		return false;
	}
	if (!$(\"load_sds\").value) {
		alert(".fixStr(s("upload_msds_no_file")).");
		return false;
	}
	return true;
}
"._script."
</head>
<body>";

		echo "<form name=\"main\" method=\"post\" enctype=\"multipart/form-data\" onSubmit=\"return checkMSDSUpload();\">".
		simpleHidden("mode").
		showInput(array(
			"int_name" => "supplier", "value" => "MSDS",
		)).
		showBr().
		"<input type=\"file\" name=\"load_sds\" id=\"load_sds\">".
		showBr().
		"<input type=\"submit\" value=".fixStr(s("upload"))."></form>";
	}
break;

case "tlc": // javascript tlc assistant, planned
	
break;

case "gc": // gc cross table
	// GC interpretation
	// Daten aus opener lesen, weil ggf. noch nicht geschrieben, Tabelle mit JS bauen
	//~ if (!empty($_REQUEST["molecule_id"]) && !empty($_REQUEST["analytics_method_id"])) {
		//~ $retention_time_data=mysql_select_array(array("table" => "retention_time", "dbs" => "-1", "filter" => "molecule_id IN(".secSQL($_REQUEST["molecule_id"]).") AND analytics_method_id=".fixNull($_REQUEST["analytics_method_id"])));
	//~ }
	echo "<title>".s("gc_cross")."</title>".
		loadJS(array("reaction_analytics.js","gc_cross.js","chem.js"),"lib/").
		stylesheet."
		</head>
		<body>";
	showCommFrame(); // to retrieve similar peaks
	
	echo getHelperTop()."<img id=\"analytical_data_img\" src=\"getGif.php?analytical_data_id=".fixNull($_REQUEST["analytical_data_id"])."\">".<<<END

<form name="main" onSubmit="okClicked(); return false;" method="get">
<div id="crossTable"></div>

<table class="noborder"><tr><td>
END;

echo getImageLink(array("url" => "javascript:void okClicked()", "a_class" => "imgButtonSm", "src" => "lib/ok_sm.png", "l" => "ok")).
		"</td><td>".
		getImageLink(array("url" => "javascript:void window.close()", "a_class" => "imgButtonSm", "src" => "lib/cancel_sm.png", "l" => "cancel")).
		"</td></tr></table>
		</form>".
		script."
		var iHTML=\"<table class=\\\"subitemlist\\\"><thead><tr><td>".s("ret_time")."</td><td>".s("area_perc")."</td>\",UID=".fixStr($_REQUEST["UID"]).",a_db_id=".fixNull($_REQUEST["db_id"]).",rc_gif_x=".fixNull(rc_gif_x*colHeadFactor).",rc_gif_y=".fixNull(rc_gif_y*colHeadFactor).";
		".<<<END
if (opener) {
	var peaks=opener.getGCpeakData(UID),list_int_name,analytics_type_id=opener.SILgetValue("analytical_data",UID,"analytics_type_id"),analytics_device_id=opener.SILgetValue("analytical_data",UID,"analytics_device_id"),analytics_method_id=opener.SILgetValue("analytical_data",UID,"analytics_method_id");
	var gc_rc_list_int_names=new Array();
	var gc_rc_uids=new Array();
	var rc_peak_assign=new Array();
	var gc_texts=new Array();
	var rc_UID,text,hasStd;
	
	// add delLine
	peaks.unshift({"time":0});
	
	// Std, vorerst KEIN dropdown für Std
	rc_UID=opener.getInputValue("gc_peak_std_uid_"+UID+"_");
	if (rc_UID) {
		gc_rc_list_int_names.push("");
		gc_rc_uids.push("");
		gc_texts.push(opener.SILgetValue("reagents",rc_UID,"standard_name"));
		hasStd=true;
	}
	
	for (var c=0,max2=chroma_lists.length;c<max2;c++) { // components that can be in chromatogram
		list_int_name=chroma_lists[c];
		
		// Produkte durchgehen
		for (var b=0,max=opener.controlData[list_int_name]["UIDs"].length;b<max;b++) {
			rc_UID=opener.controlData[list_int_name]["UIDs"][b];
			gc_rc_list_int_names.push(list_int_name);
			gc_rc_uids.push(rc_UID);
			text=opener.SILgetValue(list_int_name,rc_UID,"standard_name");
			if (isEmptyStr(text)) {
				text=getRCname(list_int_name)+" "+(b+1);
			}
			gc_texts.push(text);
		}
	}
	
	
	// Peaks durchgehen und prüfen, ob irgendein comment
	var show_comment=false;
	for (var c=0,max2=peaks.length;c<max2;c++) {
		if (peaks[c]["comment"]) {
			show_comment=true;
			iHTML+="<td>
END
.s("gc_peak_comment").<<<END
</td>";
			break;
		}
	}
	
	// Überschrift
	for (var b=0,max=gc_rc_uids.length;b<max;b++) {
		if (hasStd && b==0) {
			list_int_name="reagents";
		}
		else {
			list_int_name=gc_rc_list_int_names[b];
		}
		iHTML+="<td>"+gc_texts[b]+"<br>
END;

if ($useSvg) {
	echo '<object data=\"getGif.php?db_id="+a_db_id+"&"+opener.SILimgGetParams(list_int_name,gc_rc_uids[b],"molfile_blob")+"\" width="+fixStr(rc_gif_x)+" height="+fixStr(rc_gif_y)+" type=\"image/svg+xml\"></object>';
}
else {
	echo '<img src=\"getGif.php?db_id="+a_db_id+"&"+opener.SILimgGetParams(list_int_name,gc_rc_uids[b],"molfile_blob")+"\" width="+fixStr(rc_gif_x)+" height="+fixStr(rc_gif_y)+">';
}

echo <<<END
</td>";
	}
	
	iHTML+="</tr></thead><tbody>";

	// Std und Produkte durchgehen und passende peaks suchen
	for (var b=0,max=gc_rc_uids.length;b<max;b++) {
		rc_peak_assign[b]=getClosestGCPeak(peaks,opener.getInputValue("gc_peak_retention_time_"+UID+"_"+gc_rc_uids[b]));
	}
	
	for (var c=0,max2=peaks.length;c<max2;c++) {
		if (c==0) { // delLine
			iHTML+="<tr><td colspan=\"2\">"+s("delete")+"</td>";
		}
		else {
			var id="peak"+c
			iHTML+="<tr><td id="+fixStr(id)+" onMouseover=\"initSimilarOverlay("+fixQuot(id)+","+peaks[c]["time"]+");\" onMouseout=\"hideOverlay();\">"+round(peaks[c]["time"],2)+"</td><td>"+round(peaks[c]["rel_area"],2)+"</td>";
		}
		
		if (show_comment) {
			iHTML+="<td>"+defBlank(peaks[c]["comment"])+"</td>";
		}
		
		// Std und Produkte durchgehen
		for (var b=0,max=gc_rc_uids.length;b<max;b++) {
			text=fixStr(getCheckID(c,b));
			iHTML+="<td><input type=\"checkbox\" id="+text+" name="+text+" value=\"1\" onClick=\"updateGCcross("+c+","+b+")\"";
			
			//~ if (peaks[c]["time"]==opener.getInputValue("gc_peak_retention_time_"+UID+"_"+gc_rc_uids[b])) {
			if (rc_peak_assign[b]==c) {
				iHTML+=" checked=\"checked\"";
			}
			iHTML+="></td>";
		}
		iHTML+="</tr>";
	}
	
	iHTML+="</tbody></table>";
	setiHTML("crossTable",iHTML);
}
END
._script.
getHelperBottom();

break;

case "chn": // CHN/NMR form TU Kaiserslautern
	echo stylesheet.style."
input { border:1px solid black }
"._style."
</head>
<body>".
loadJS(array("chem.js"),"lib/")."<form name=\"main\" onSubmit=\"return false;\" method=\"get\">";

	$page_transparent_params=array("mode");
	
	$available=array();
	$selfRef=getSelfRef();
	$dir="forms/analytics";
	$ext=".txt";
	
	if (is_dir($dir)) {
		// get listing of directory forms
		$files=scandir($dir);
		if (is_array($files)) foreach ($files as $file) {
			$path=$dir."/".$file;
			if (!endswith($file,$ext)) {
				continue;
			}
			
			// read 1st line to get text
			$handle=fopen($path,"r");
			
			if ($line1=fgets($handle,16384)) {
				
				// if filename[.txt] is equal to $_REQUEST["type"] or is "default", output rest of the file
				if ($_REQUEST["type"]==$file || (empty($_REQUEST["type"]) && "default".$ext==$file)) {
					fpassthru($handle); // output all to the end
				}
				else {
					// else add name and text to list
					$available[$file]=$line1;
				}
			}
			
			fclose ($handle);
		}
		
		if (is_array($available)) foreach ($available as $type => $text) {
			echo "<a href=".fixStr($selfRef."&type=".$type)." class=\"noprint\">".$text."</a> ";
		}
	}
	
	echo "</form>".
script."
if (opener) {
	var person_name=".fixStr(formatPersonNameNatural($own_data)).",workgroup_name=".fixStr($g_settings["workgroup_name"]).",tel_no=".fixStr($settings["tel_no"]).",email=".fixStr($settings["email"]).";
	var sample_name=opener.getInputValue(\"lab_journal_code\")+opener.getInputValue(\"nr_in_lab_journal\");
	var list_int_name=".fixStr($_REQUEST["list_int_name"]).",UID=".fixStr($_REQUEST["UID"]).";
	var pos=opener.SILgetPos(list_int_name,UID)>0;
	if (pos>0) {
		sample_name+=\"-\"+(pos+1);
	}
	setiHTML(\"person_name\",person_name);
	setiHTML(\"person_name2\",person_name);
	setiHTML(\"workgroup_name\",workgroup_name);
	setiHTML(\"workgroup_name2\",workgroup_name);
	setiHTML(\"tel_no\",tel_no);
	setiHTML(\"email\",email);
	setiHTML(\"sample_name\",sample_name);
	setiHTML(\"sample_name2\",sample_name);
	top.defaultTitle=".fixStr(s("analysenzettel")).";
	setTitle(sample_name);
	var emp_formula=opener.SILgetValue(list_int_name,UID,\"emp_formula\");
	var molData=computeMolecule(emp_formula,1);
	setiHTML(\"emp_formula\",molData[\"prettyPrint\"]);
	setiHTML(\"mw\",round(molData[\"MW\"],2,3));
	setiHTML(\"mw_monoiso\",round(molData[\"mw_monoiso\"],4,3));
	setiHTML(\"c\",round(100*molData[\"C\"],2,3));
	setiHTML(\"h\",round(100*molData[\"H\"],2,3));
	setiHTML(\"n\",round(100*molData[\"N\"],2,3));
	setiHTML(\"s\",round(100*molData[\"S\"],2,3));
	var now=new Date();
	var formatted=now.getDate()+\".\"+(now.getMonth()+1)+\".\"+now.getFullYear();
	setiHTML(\"datum\",formatted);
	setiHTML(\"datum2\",formatted);
	
	// Structure
	var int_name=\"molfile_blob\",group;
	var params=opener.SILimgGetParams(list_int_name,UID,int_name,group);
	setiHTML(int_name,opener.getImgForSrc(opener.getImgURL(params)));
}
"._script;

break;

case "mol": // edit molecule
case "tmpl": // edit template, VectorMol only
case "rxn": // edit reaction
	require_once "lib_molfile.php";
	require_once "lib_applet.php";
	
	$_REQUEST["force"]=strip_tags($_REQUEST["force"]);
	if (!empty($_REQUEST["force"])) {
		$forceParam=",".fixStr($_REQUEST["force"]);
	}
	
	echo "<title>".s("edit_structure").
"</title>".
stylesheet.
loadJS(array("molecule_edit.js",),"lib/").
"</head>
<body>
<div style=\"width:360px;text-align:right\">";
	showCommFrame(array("debug" => false));
	copyPasteAppletHelper(array(
		"mode" => $_REQUEST["mode"], 
	));
	
	echo getAppletHTML(array(
		"appletName" => "JME", 
		"mode" => $_REQUEST["mode"], 
		"width" => "90%", 
		"height" => "70%", 
		"copyPasteButtons" => true, 
		"force" => $_REQUEST["force"], 
	)).
"<form name=\"main\" onSubmit=\"return checkForm()\" method=\"post\" enctype=\"multipart/form-data\">
<table class=\"noborder\"><tbody><tr><td>
".s("upload_molfile")."
</td><td>
<input type=\"file\" name=\"load_molfile\" id=\"load_molfile\">".
simpleHidden("int_name").
simpleHidden("UID").
simpleHidden("field").
simpleHidden("group").
"</td><td>
<input type=\"submit\" value=".fixStr(s("upload")).">
</td></tr>
</tbody></table>
<table class=\"noborder\"><tbody>
<tr><td>
</td><td>
<input type=\"button\" value=".fixStr(s("ok"))." onClick=\"okClicked(); \">
</td><td>
<input type=\"button\" value=".fixStr(s("cancel"))." onClick=\"self.close(); \">
</td>";

	if (!empty($_REQUEST["UID"])) {
		if (!empty($_REQUEST["group"])) {
			echo "
<td>
<a href=\"Javascript:SILmoveHorizontal(-1); \"><img src=\"lib/left.png\" width=\"14\" height=\"16\" border=\"0\"".getTooltip("move_left")."></a>
</td>";
		}
		echo "
<td style=\"text-align:center\">
<a href=\"Javascript:SILmoveVertical(-1); \"><img src=\"lib/up.png\" width=\"16\" height=\"14\" border=\"0\"".getTooltip("move_up")."></a>
<a href=\"Javascript:SILmoveVertical(1); \"><img src=\"lib/down.png\" width=\"16\" height=\"14\" border=\"0\"".getTooltip("move_down")."></a>
</td>";
		if (!empty($_REQUEST["group"])) {
			echo "
<td>
<a href=\"Javascript:SILmoveHorizontal(1); \"><img src=\"lib/right.png\" width=\"14\" height=\"16\" border=\"0\"".getTooltip("move_right")."></a>
</td>";
		}
		echo "<td>".s("arrows_molecule_edit")."</td>";
	}
	echo "</tr></tbody></table>
</form>
</div>
".script."

function SILmoveVertical(delta) { // prev/next line
	if (!opener) {
		return;
	}
	var newUID=opener.SILgetUID(int_name,opener.SILgetPos(int_name,UID)+delta);
	if (newUID && newUID!=UID) {
		// write current structure
		okClicked(true);
		
		// and read new one
		UID=newUID;
		setMolfile(getMolfileFromTarget());
	}
}

function SILmoveHorizontal(delta) { // prev/next group
	if (!opener) {
		return;
	}
	var newGroup=opener.SILgetRelativeGroup(int_name,field,group,delta);
	if (newGroup && newGroup!=group) {
		// write current structure
		okClicked(true);
		
		// and read new one
		group=newGroup;
		setMolfile(getMolfileFromTarget());
	}
}

function checkForm() {
	var obj=$(\"load_molfile\");
	if (obj) {
		if (obj.value!=\"\") {
			return true;
		}
	}
	return false;
}

function getMolfileFromApplet() {
	";
	if ($_REQUEST["mode"]=="rxn") {
		echo "return getRxnfile(\"JME\"".$forceParam.");\n";
	}
	else {
		echo "return getMolfile(\"JME\"".$forceParam.");\n";
	}
	echo "
}

function writeMolfileToTarget(molfile) {
	if (!opener) {
		return;
	}
	opener.valChanged();
	var molfile_obj=opener.MEgetMolfileInput(int_name,UID,field,group);
	molfile_obj.value=molfile;	
}

function getMolfileFromTarget() {
	return opener.MEgetMolfile(int_name,UID,field,group);
}

function okClicked(noClose) {
	if (!opener) {
		return;
	}
	
	var molfile=getMolfileFromApplet();

	opener.valChanged();
	var molfile_obj=opener.MEgetMolfileInput(int_name,UID,field,group);
	molfile_obj.value=molfile;";
	
	if ($_REQUEST["autoUpdate"]) {
		echo "
opener.addMoleculeToUpdateQueue(int_name,UID,field,group,".fixStr($_REQUEST["desired_action"]).");
opener.updateMolecules();";
	}
	
	echo "
	if (molfile_obj.onchange) {
		molfile_obj.onchange.call();
	}
	
	if (!noClose) {
		self.close();
	}
}

var molfile=\"\";

var loadCount=0;
function setMolfile(molfile) {
	var appName=\"JME\",maxLoadCount=40;
	if (molfile!=undefined) {
		if (loadCount<maxLoadCount && isSafari) {
			loadCount=maxLoadCount;
			window.setTimeout(function () { setMolfile(molfile); },700);
		}
		else if (loadCount>=maxLoadCount || isAppletReady(appName,".fixStr($_REQUEST["mode"]).$forceParam.")) {
			window.setTimeout(function () {";
	if ($_REQUEST["mode"]=="rxn") {
		echo "putRxnfile";
	}
	else {
		echo "putMolfile";
	}
	echo "(appName,molfile".$forceParam."); },100);
		}
		else {
			loadCount++;
			window.setTimeout(function () { setMolfile(molfile); },300);
		}
	}
}

if (opener) {
	var int_name=".fixStr($_REQUEST["int_name"]).",UID=".fixStr($_REQUEST["UID"]).",field=".fixStr($_REQUEST["field"]).",group=".fixStr($_REQUEST["group"]).";
";
	if (count($_FILES["load_molfile"])) {
		// print_r($_FILES);
		/*
	    [load_molfile] => Array
		(
		    [name] => Toluene.mol
		    [type] => chemical/x-mdl-molfile
		    [tmp_name] => /var/tmp/phpNhjfSD
		    [error] => 0
		    [size] => 719
		)
	*/
		if ($_FILES["load_molfile"]["error"]==0) {
			$filename=& $_FILES["load_molfile"]["tmp_name"];
			$filesize=& $_FILES["load_molfile"]["size"];
			// datei öffnen
			$handle = fopen($filename, "rb");
			// größe prüfen
			if ($filesize>0 && filesize($filename)==$filesize) {
				// datei einlesen
				$molfile=fread($handle,$filesize);
			}
			// datei schließen
			fclose($handle);
			// datei löschen
			@unlink($filename);
			echo "oldmolfile=".fixStr(addPipes($molfile)).";";
		}
	}
	else {
		echo "oldmolfile=getMolfileFromTarget();";
	}
	
	echo "
	if (isMac) {
		window.setTimeout(function () {setMolfile(oldmolfile);},100);
	}
	else {
		setMolfile(oldmolfile);
	}
}
"._script;
break;
}

echo "</body>
</html>";
completeDoc();
?>
