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
require_once "lib_tabs.php";
require_once "lib_simple_forms.php";
require_once "lib_form_elements_helper.php";

function getCombiButtonURL($paramHash) {
	global $pk_name;
	
	$op=ifempty($paramHash["op"],"eq");
	$pk=urlencode($paramHash["pk"]);
	
	$url="list.php?table=".$paramHash["table"]."&dbs=".$paramHash["db_id"];
	if (empty($paramHash["filter"])) { // normal case
		$url.="&query=<0>";
	}
	$url.="&crit0=".$paramHash["this_pk_name"]."&op0=".$op."&val0=".$pk.ifNotEmpty("&",$paramHash["filter"])."&".getSelfRef(array("~script~","table","cached_query","order_by","no_cache",$pk_name,"fields","page","per_page","dbs"));
	return $url;
}

function getSelectButton($row) {
	global $table,$pk_name;
	// Knopf übernehmen, setzt die molecule_id/chemical_storage_id und schließt das Fenster
	return "<a href=\"javascript:void transferPkToUID(&quot;".$table."&quot;,&quot;".$row["db_id"]."&quot;,&quot;".$row[$pk_name]."&quot;)\" class=\"imgButtonSm\"><img src=\"lib/select_sm.png\" border=\"0\"".getTooltip("do_select")."></a>";
}

function listGetPrintButton() {
	// Knopf übernehmen, setzt die molecule_id/chemical_storage_id und schließt das Fenster
	return "<a href=\"javascript:void self.print()\" class=\"imgButtonSm\"><img src=\"lib/print_sm.png\" border=\"0\"".getTooltip("print")."></a>";
}

function listGetExportButton() {
	// Knopf übernehmen, setzt die molecule_id/chemical_storage_id und schließt das Fenster
	return "<a href=\"javascript:showExportMenu();\" class=\"imgButtonSm\"><img src=\"lib/export_sm.png\" border=\"0\"".getTooltip("export")."></a>";
}

function getRefreshButton() {
	// Knopf übernehmen, setzt die molecule_id/chemical_storage_id und schließt das Fenster
	return "<a href=\"javascript:void refreshData()\" class=\"imgButtonSm\"><img src=\"lib/refresh_sm.png\" border=\"0\"".getTooltip("refresh")."></a>";
}

function getSDSLink($pkName,$db_id,$pk,$int_name,$safety_sheet_by="") {
	if (empty($safety_sheet_by)) {
		$safety_sheet_by=s("safety_data_sheet");
	}
	return "<a href=\"getSafetySheet.php?int_name=".$int_name."&".$pkName."=".$pk."&db_id=".$db_id."\" target=\"_blank\">".$safety_sheet_by."</a> <a href=\"getSafetySheet.php?int_name=".$int_name."&".$pkName."=".$pk."&db_id=".$db_id."&inline=true\" target=\"_blank\"><img src=\"lib/external.png\"/></a>";
}

function getListLogic($style,$onChange="") {
	global $langKeys;
	if ($style=="form") {
		$retval=array(
			"item" => "select", 
			"int_name" => "list_op", 
			"langKeys" => $langKeys["list_op"],
			"onChange" => "listOpChanged();".$onChange, 
		);
	}
	else {
		$retval="<fieldset id=\"list_logic\" style=\"display:none\"><legend>".s("list_op")."</legend>".
			showSelect(array(
				"int_name" => "list_op", 
				"text" => "", 
				"langKeys" => $langKeys["list_op"],
				"onChange" => "listOpChanged();".$onChange, 
			))."</fieldset>";
	}
	return $retval;
}

function getInventoryButton() {
	global $permissions;
	$retval="
<a href=".fixStr("lj_main.php?".getSelfRef(array("~script~","table","cached_query","dbs","fields","order_by","db_id","pk","per_page","style","ref_cache_id")))." target=\"_blank\" class=\"imgButtonSm\"><img src=\"lib/new_win_lab_journal_sm.png\" border=\"0\"".getTooltip("open_new_lj_menu")."></a>
</td><td>";
	if ($permissions & _chemical_read) {
		$retval.="<a href=".fixStr("main.php?".getSelfRef(array("~script~","table","cached_query","dbs","fields","order_by","db_id","pk","per_page","style","ref_cache_id")))." target=\"_top\" class=\"imgButtonSm\"><img src=\"lib/chemical_storage_sm.png\" border=\"0\"".getTooltip("change_to_inventory_menu")."></a>
</td><td>";
	}
	$retval.="<a href=".fixStr("index.php?desired_action=logout&".getSelfRef(array("~script~")))." target=\"_top\" class=\"imgButtonSm\"><img src=\"lib/exit_sm.png\" border=\"0\"".getTooltip("logout")."></a>";
	return $retval;
// </td></tr></table>
}

function getPrintBarcodesButton($baseTable) {
	return "<a href=\"printBarcodeList.php?table=".$baseTable."\" class=\"imgButtonSm\" target=\"_blank\"><img src=\"lib/".$baseTable."_barcode_sm.png\" border=\"0\"".getTooltip("print_".$baseTable."_barcode")."></a>";
}

function getMessageButton() {
	$message_results=mysql_select_array(array(
		"table" => "message_new", 
		"dbs" => "-1", 
	));
	
	$unread=count($message_results);
	if ($unread>0) {
		$highlight=" style=\"border-color:red\"";
	}
	// return single button that leads to message inbox
	return "<nobr><a id=\"message_notify\"".$highlight." href=".fixStr("list.php?table=message_in&query=&".getSelfRef(array("~script~","table","cached_query","dbs","fields","order_by","db_id","pk","per_page","ref_cache_id")))." class=\"imgButtonSm\"><img src=\"lib/message_in_sm.png\" border=\"0\"".getTooltip("btn_message_in")."><span id=\"message_count\" title=".fixStr(s("unread_messages")).">".$unread."</span></a></nobr>
</td><td>
<nobr><a href=".fixStr("list.php?table=message_out&query=&".getSelfRef(array("~script~","table","cached_query","dbs","fields","order_by","db_id","pk","per_page","ref_cache_id")))." class=\"imgButtonSm\"><img src=\"lib/message_out_sm.png\" border=\"0\"".getTooltip("btn_message_out")."></a></nobr>
</td><td>
<nobr><a href=".fixStr("edit.php?table=message_out&desired_action=new&".getSelfRef(array("~script~","table","cached_query","dbs","fields","order_by","db_id","pk","per_page","style")))." target=\"_blank\" class=\"imgButtonSm\"><img src=\"lib/message_out_sm.png\" border=\"0\"".getTooltip("new_message").">+</a></nobr>";
}

function alignHorizontal($iHTMLarray,$blockAlign="") {
	if (!$iHTMLarray) {
		return "";
	}
	if (!is_array($iHTMLarray)) {
		$iHTMLarray=array($iHTMLarray);
	}
	return "<table class=\"noborder ".$blockAlign."\"><tr><td>".join("</td><td>",$iHTMLarray)."</td></tr></table>";
}

function getAlignTable($left=array(),$center=array(),$right=array()) {
	//~ print_r($left);
	//~ print_r($center);
	//~ print_r($right);
	//~ die();
	$retval="<table class=\"triAlign\"><tr><td class=\"blockAlignLeft\" style=\"text-align:left\">".alignHorizontal($left,"blockAlignLeft")."</td><td class=\"blockAlignCenter\" style=\"text-align:center\">".alignHorizontal($center,"blockAlignCenter")."</td><td class=\"blockAlignRight\" style=\"text-align:right\">".alignHorizontal($right,"blockAlignRight")."</td></tr></table>";
	return $retval;
}

function getTwoAlignTable($left,$right) {
	$retval="<table class=\"twoAlign\"><tr><td class=\"blockAlignLeft\" style=\"text-align:left\">".alignHorizontal($left,"blockAlignLeft")."</td><td class=\"blockAlignRight\" style=\"text-align:right\">".alignHorizontal($right,"blockAlignRight")."</td></tr></table>";
	return $retval;
}

function getListOptionsMenu($col_options_key) {
	global $view_options,$column_options,$view_options_HTML;
	$retval.="<a href=\"javascript:void(0)\" onClick=\"showOptionsMenu(".fixQuot($col_options_key).",this)\" class=\"imgButtonSm\"><img src=\"lib/list_options_sm.png\" border=\"0\"".getTooltip("list_options")."></a>";
	$view_options_HTML.="<div id=\"options_".$col_options_key."\" class=\"list_options\" style=\"display:none\">";
	
	switch ($col_options_key) {
	case "yield":
	case "reaction_chemical";
	default:
		// set to current or default values!!
		if (empty($view_options[$col_options_key]["fields"])) {
			$view_options[$col_options_key]["fields"]=getDefaultFields($col_options_key);
		}
		if (is_array($column_options[$col_options_key]["fields"])) foreach ($column_options[$col_options_key]["fields"] as $field => $options) {
			$paramHash=array("int_name" => $field, "value" => $default || in_array($field,$view_options[$col_options_key]["fields"]) );
			if (isset($options["langKey"])) {
				$paramHash["text"]=s($options["langKey"]);
			}
			$view_options_HTML.=showCheck($paramHash).showBr();
		}
	}
	$view_options_HTML.="<table class=\"noborder\" id=\"reaction_table\"><tbody><tr>
<td><a href=\"javascript:saveListOptions(".fixQuot($col_options_key).")\" class=\"imgButtonSm\"><img src=\"lib/save_sm.png\" border=\"0\"".getTooltip("save_settings")."></a></td>
<td><a href=\"javascript:hideOptionsMenu(".fixQuot($col_options_key).")\" class=\"imgButtonSm\"><img src=\"lib/cancel_sm.png\" border=\"0\"".getTooltip("cancel")."></a></td>
</tr></tbody></table></form></div>";
	return $retval;
}

// law info
function getLawMenu() {
	$lawURLs=array(
		"ChemG" => "http://www.gesetze-im-internet.de/chemg/index.html", 
		"GefStoffV" => "http://www.gesetze-im-internet.de/gefstoffv_2010/index.html", 
		"ArbSchG" => "http://www.gesetze-im-internet.de/arbschg/index.html", 
		"MuSchG" => "http://www.gesetze-im-internet.de/muschg/index.html", 
		"Arbeitszeitverordnung" => "http://www.gesetze-im-internet.de/azv/index.html", 
	);
	$retval="<div id=\"lawsMenu\" class=\"overlayMenu\" style=\"display:none;\" onMouseover=\"cancelOverlayTimeout(); \" onMouseout=\"hideOverlayId(&quot;lawsMenu&quot;);\">";
	foreach ($lawURLs as $text => $url) {
		$retval.="<a href=".fixStr($url)." target=\"_blank\" class=\"imgButtonSm\" style=\"width:95%\">".$text."</a>";
	}
	$retval.="</div>";
	return $retval;
}

// auto transfer
function getTransferDevices($transfer_settings) {
	$result=getDeviceResult($transfer_settings);
	$retval=array();
	for ($a=0;$a<count($result);$a++) {
		$retval[]=$result[$a]["analytics_device_name"];
	}
	return join(", ",$retval);
}

function getTransferMenu() {
	global $settings;
	$retval="<div id=\"transferMenu\" class=\"overlayMenu\" style=\"display:none;\" onMouseover=\"cancelOverlayTimeout(); \" onMouseout=\"hideOverlayId(&quot;transferMenu&quot;);\">";
	for ($a=1;$a<count($settings["include_in_auto_transfer"]);$a++) {
		if (count($settings["include_in_auto_transfer"][$a])) {
			$retval.="<a href=\"javascript:void transferGCs(".$a.")\" class=\"imgButtonSm\" title=".fixStr(s("transfer_gc1").getTransferDevices($a).s("transfer_gc2"))."><img src=\"lib/auto_trans_sm.png\" border=\"0\"".getTooltip("transfer_gc").">".($a+1)."</a>";
		}
	}
	$retval.="</div>";
	return $retval;
}

function getVersionMenu() {
	$retval="<div id=\"versionMenu\" class=\"overlayMenu\" style=\"display:none;\"><form onSubmit=\"return false;\">
<select id=\"versionSelect\" size=\"5\" onChange=\"loadVersion(this.value)\"></select>
<table class=\"noborder\"><tbody><tr>
<td><a href=\"javascript:recoverVersion(); \" class=\"imgButtonSm\" id=\"btn_recover\"><img src=\"lib/recover_sm.png\" border=\"0\"".getTooltip("recover")."></a></td>
<td><a href=\"javascript:showVersionsList(false); \" class=\"imgButtonSm\"><img src=\"lib/cancel_sm.png\" border=\"0\"".getTooltip("cancel")."></a></td>
</tr></tbody></table></form></div>";
	return $retval;
}

function getPrintMenu($baseTable="") {
	global $paperFormats,$rand,$g_settings,$settings;
	//~ $print_what_texts=array(s("print_all"),s("print_current"),s("print_selection"),s("print_pages"),);
	if ($settings["custom_border"]) {
		$rand["w"]=$settings["border_w_mm"];
		$rand["h"]=$settings["border_h_mm"];
	}
	elseif (!empty($g_settings["border_w_mm"]) && !empty($g_settings["border_h_mm"])) { // use defaults otherwise
		$rand["w"]=$g_settings["border_w_mm"];
		$rand["h"]=$g_settings["border_h_mm"];
	}
	// Papierformate
	if (is_array($paperFormats)) foreach ($paperFormats as $name => $data) {
		$paperNames[]=$name;
		$paperSizes[]=($data["w"]-$rand["w"]).",".($data["h"]-$rand["h"]);
	}
	$retval.="<div id=\"printMenu\" style=\"display:none\"><form onSubmit=\"return false;\">".
		s("print").
		showBr().
		showSelect(array(
			"int_name" => "print_what", 
			"radioMode" => true, 
			"int_names" => array("print_all","print_current","print_from_here","print_selection","print_range"), 
			"value" => "print_current", 
			//~ "langUseValues" => true, // obsolete
			//~ "texts" => $print_what_texts, 
		)).
		"<input type=\"text\" id=\"print_range_input\" size=\"8\" onClick=\"$(&quot;print_range&quot;).checked=&quot;checked&quot; \" onKeyUp=\"printMenuKeyUp(event); \">";
	
	$retval.=showBr().
		showCheck(array(
			"int_name" => "multi_page", 
			"value" => ($baseTable=="settlement")?"1":"", 
		)); // 1 page per dataset
	
	switch ($baseTable) {
	case "reaction";
		$retval.=showBr().
			showCheck(array("int_name" => "print_chem_list")).
			showCheck(array("int_name" => "print_labels"));
	break;
	case "settlement": // print in landscape by default
		//~ $defaultPaperFormat=$paperSizes[ array_search("A4 Landsc.",$paperNames) ];
	break;
	}
	$retval.=showBr().
		showBr().
		showSelect(array(
			"int_name" => "print_size", 
			"int_names" => $paperSizes, 
			"texts" => $paperNames, 
			"value" => $defaultPaperFormat, 
		)).
		showBr().
		getHiddenSubmit().
		"<table class=\"noborder\">
<tbody><tr>
<td><a href=\"javascript:printDetail(); \" class=\"imgButtonSm\"><img src=\"lib/print_sm.png\" border=\"0\"".getTooltip("print")."></a></td>
<td><a href=\"javascript:showPrintMenu(false); \" class=\"imgButtonSm\"><img src=\"lib/cancel_sm.png\" border=\"0\"".getTooltip("cancel")."></a></td>
</tr></tbody>
</table></form></div>";
	return $retval;
}

function getExportMenu() {
	global $export_formats;
	$retval.="<div id=\"exportMenu\" style=\"display:none\"><form onSubmit=\"return false;\">".
		s("export").
		showBr().
		showSelect(array(
			"int_name" => "export_what", 
			"radioMode" => true, 
			"int_names" => array("export_all","export_current","export_selection"), 
			"value" => "export_all", 
			//~ "langUseValues" => true, // obsolete
		)).
		showCheck(array("int_name" => "export_visible")).
		showBr().
		showSelect(array(
			"int_name" => "output_type", 
			"int_names" => $export_formats, 
			"value" => "xls", 
			//~ "langUseValues" => true, // obsolete
		)).
		showBr().
		getHiddenSubmit().
		"<table class=\"noborder\">
<tbody><tr>
<td><a href=\"javascript:startExport(); \" class=\"imgButtonSm\"><img src=\"lib/export_sm.png\" border=\"0\"".getTooltip("export")."></a></td>
<td><a href=\"javascript:showExportMenu(false); \" class=\"imgButtonSm\"><img src=\"lib/cancel_sm.png\" border=\"0\"".getTooltip("cancel")."></a></td>
</tr></tbody>
</table></form></div>";
	return $retval;
}

function getViewRadio($paramHash=array()) {
	return "<table id=\"radio_view\" class=\"hidden condition\"><tr><td><input type=\"radio\" onClick=".fixStr($paramHash["onChange"])." name=\"view_mode\" id=\"view_list\" value=\"list\" checked=\"checked\"><label for=\"view_list\">".s("view_list")."</label></td></tr><tr><td><input type=\"radio\" onClick=".fixStr($paramHash["onChange"])." name=\"view_mode\" id=\"view_edit\" value=\"edit\"><label for=\"view_edit\">".s("view_edit")."</label></td></tr></table>";
}

function gutCustomMenu($table) {
	global $edit_views,$view_controls,$view_ids;
	$retval="";
	if (isset($edit_views[$table])) { //  && !isMSIE() customization does not work in MSIE 7 due to $ bug. I WILL NOT reprogram this to fit MSIE BS.
		// customize button
		$retval.="<div id=\"customize_view\" class=\"no_print\"><a href=\"Javascript:showCustomMenu()\" class=\"imgButtonSm\"><img src=\"lib/list_options_sm.png\" border=\"0\"".getTooltip("customize_view")."></a><br clear=\"all\"><div id=\"customize_view_menu\" style=\"display:none;border:1px solid black\"><form id=\"customize_view_form\" method=\"get\" target=\"comm\" action=\"chooseAsync.php\"><input type=\"hidden\" name=\"desired_action\" value=\"update_custom_view\"><input type=\"hidden\" name=\"table\" value=".fixStr($table).">";
		for ($a=0;$a<count($view_controls[$table]);$a++) {
			$id=$view_controls[$table][$a];
			$text=s($view_controls[$table][$a]);
			if ($text) {
				$cid="custom_".$id;
				$retval.="<label for=".fixStr($cid)."><input type=\"checkbox\" id=".fixStr($cid)." name=".fixStr($id)." value=\"1\"".(in_array($id,$edit_views[$table]["custom_view"]["visibleControls"])?" checked=\"checked\"":"").">".$text."</label><br>";
			}
		}
		for ($a=0;$a<count($view_ids[$table]);$a++) {
			$id=$view_ids[$table][$a];
			$text=s($view_ids[$table][$a]);
			if ($text) {
				$cid="custom_".$id;
				$retval.="<label for=".fixStr($cid)."><input type=\"checkbox\" id=".fixStr($cid)." name=".fixStr($id)." value=\"1\"".(in_array($id,$edit_views[$table]["custom_view"]["visibleIds"])?" checked=\"checked\"":"").">".$text."</label><br>";
			}
		}
		$retval.="<table class=\"noprint\"><tr><td><a href=\"Javascript:updateCustomView()\" class=\"imgButtonSm\"><img src=\"lib/save_sm.png\"".getTooltip("save_settings")." border=\"0\"></a></td><td><a href=\"Javascript:showCustomMenu(false)\" class=\"imgButtonSm\"><img src=\"lib/cancel_sm.png\"".getTooltip("cancel")." border=\"0\"></a></td></tr></table></form></div></div>";
	}
	return $retval;
}

function getFixedStyleBlock() {
	$browsenav_min_width=825; // ;min-width:".$browsenav_min_width."px
	switch ($_REQUEST["style"]) {
	case "lj":
		$browseMain_top=82;
		$retval.="
	#browsenav { position:absolute;top:0px;left:0px;height:".$browseMain_top."px;background-image:url('lib/redline_bg.png');background-repeat:repeat-x;width:100%;z-index:4 }";
	break;
	default:
		$browseMain_top=82;
		$retval.="
	#browsenav { position:absolute;top:0px;left:0px;height:".$browseMain_top."px;background-image:url('lib/blueline_bg.png');background-repeat:repeat-x;width:100%;z-index:4 }";
	}
	// common
	$retval.="
	#customize_view  { position:absolute;top:".$browseMain_top."px;left:0px;background-color:".defBgColor." }
	#browsemain { top:".$browseMain_top."px;left:0px; }
	#tab_bar { position:absolute;left:5px;bottom:0px }	";
	return $retval;
}

function getNavigationLink($baseURL,$targetPage,$per_page,$text,$target="") {
	return "<a href=\"".$baseURL."&page=".$targetPage."&per_page=".$per_page."\"".($target!="self"?" target=".$target:"")." class=\"noprint\">".$text."</a>";
}

function getImageLink($paramHash) {
	if (!empty($paramHash["url"])) {
		$retval.="<a href=".fixStr($paramHash["url"]).ifnotempty(" target=\"",$paramHash["target"],"\"").ifnotempty(" class=\"",$paramHash["a_class"],"\"").ifnotempty(" id=\"",$paramHash["a_id"],"\"").">";
	}
	$retval.=$paramHash["text1"]."<img src=".fixStr($paramHash["src"]).ifnotempty(" width=\"",$paramHash["w"],"\"").ifnotempty(" height=\"",$paramHash["h"],"\"")." border=\"".intval($paramHash["b"])."\"";
	if (isset($paramHash["l"])) {
		$retval.=getTooltip($paramHash["l"]);
	}
	$retval.=">".$paramHash["text2"];
	if (!empty($paramHash["url"])) {
		$retval.="</a>";
	}
	return $retval;
}

function getNavigationSelect($baseURL,$currentPage,$per_page,$total_count,& $sort_hints,$target="self") { // return HTML-Code, no echo possible for async
	//~ & $quick_res
	if ($total_count<=$per_page || $per_page==0 || $per_page==-1) {	
		return "";
	}
	$totalpages=ceil($total_count/$per_page);
	$retval.="<nobr><span class=\"print_only\">".s("print_page").":&nbsp;".($currentPage+1)." ".s("page_of")." ".$totalpages."</span><span class=\"noprint\">".s("current_page").":&nbsp;</span>";
	if ($currentPage==0) {
		$retval.="<img src=\"lib/prev_deac.png\" width=\"16\" height=\"18\" border=\"0\" class=\"noprint\">";
	}
	else {
		$retval.=getNavigationLink($baseURL,$currentPage-1,$per_page,"<img src=\"lib/prev.png\" width=\"16\" height=\"18\" border=\"0\">",$target); // <
	}
	$retval.="<select size=\"1\" name=\"page\" id=\"page\" onChange=\"".$target.".location.href=this.value\" class=\"noprint\">";
	for ($a=0;$a<$totalpages;$a++) {
		if (isset($sort_hints[$a*$per_page])) {
			$hint=" (".strcut($sort_hints[$a*$per_page],10)."-".strcut($sort_hints[min(($a+1)*$per_page-1,$total_count)],10).")";
		}
   		$retval.="<option value=\"".$baseURL."&per_page=".$per_page."&page=".$a."\"".($a==$currentPage?" selected=\"selected\"":"")." title=\"".$hint."\">".($a+1);
	}
	$retval.="</select>";
	if ($currentPage==$totalpages-1) {
		$retval.="<img src=\"lib/next_deac.png\" width=\"16\" height=\"18\" border=\"0\" class=\"noprint\">";
	}
	else {
		$retval.=getNavigationLink($baseURL,$currentPage+1,$per_page,"<img src=\"lib/next.png\" width=\"16\" height=\"18\" border=\"0\">",$target); // >
	}
	$retval.="</nobr>";	
	return $retval;
}

function getPerPageSelectInput($paramHash=array()) {
	global $allowed_per_page;
	$onChange=& $paramHash["onChange"];
	$retval.="<select id=\"per_page\" name=\"per_page\"".($onChange==""?"":" onChange=\"".$onChange."\"")."  class=\"noprint\">";
	foreach($allowed_per_page as $number) {
		if ($number==-1) {
			$retval.="<option value=\"-1\">".s("all_results");			
		}
		else {
			$retval.="<option value=\"".$number."\">".$number." ".s("results_per_page");
		}
	}
	$retval.="</select>\n";
	return $retval;
}

function getPerPageOverlay($skip,$per_page) {
	global $allowed_per_page;
	$retval.="<div id=\"perPageOverlay\" style=\"display:none\" onMouseover=\"cancelOverlayTimeout()\" onMouseout=\"hideOverlayId(&quot;perPageOverlay&quot;);\">";
	$url=getSelfRef(array("page","per_page"));
	$results_per_page=s("results_per_page");
	foreach($allowed_per_page as $number) {
		if ($per_page==$number) {
		
		}
		elseif ($number==-1) {
			$retval.="<a href=".fixStr($url."&page=0&per_page=-1")." onClick=\"setSidenavValue(&quot;per_page&quot;,-1);\">".s("all_results")."</a><br>";
		}
		else {
			$retval.="<a href=".fixStr($url."&page=".floor($skip/$number)."&per_page=".$number)." onClick=\"setSidenavValue(&quot;per_page&quot;,".fixNull($number).");\">".$number." ".$results_per_page."</a><br>";
		}
	}
	$retval.="</div>\n";
	return $retval;
}

?>