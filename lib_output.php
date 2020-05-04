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
require_once "lib_constants.php";
require_once "lib_safety.php";
require_once "lib_simple_forms.php";
require_once "lib_db_order_by.php";
require_once "lib_array.php";
require_once "lib_borrow.php";

function getYieldBar($paramHash,$value,$title,$color,$style) {
	$retval="";
	
	// defaults
	$borderWidth=1;
	$borderColor="black";
	$borderStyle="solid";
	$tagName="td";
	$CSSstyle="";
	
	switch ($style) {
	case DIAGRAM_BAR_HIDDEN:
		$retval.="<tr><td>".$title."</td>";
		$tagName="";
	break;
	case DIAGRAM_BAR_SINGLE:
		$retval.="<tr><td>".$title."</td><td>";
		if (is_numeric($value)) {
			$tagName="div";
		}
		else {
			$tagName="";
		}
	break;
	case DIAGRAM_BAR_STACKED:
		if ($value<=0) {
			return;
		}
		$borderStyle="dotted";
	break;
	case DIAGRAM_BAR_STACKED_HIGHLIGHTED:
		if ($value<=0) {
			return;
		}
		$borderWidth=2;
	break;
	}
	
	if (!empty($tagName)) {
		$width=constrainVal($paramHash["width"]*$value*0.01-$borderWidth*2,0,$paramHash["width"]);
		$retval.="<".$tagName." class=\"diagram\" title=".fixStr($title)." style=\"".$CSSstyle."width:".$width."px;min-width:".$width."px;height:".($paramHash["bar_height"])."px;background-color:".$color.";border:".$borderWidth."px ".$borderStyle." ".$borderColor."\">&nbsp;</".$tagName.">";
	}
	
	switch ($style) {
	case DIAGRAM_BAR_HIDDEN:
		$retval.="</tr>";
	break;
	case DIAGRAM_BAR_SINGLE:
		$retval.="</td></tr>";
	break;
	}
	
	return $retval;
}

function getGraphicalYield($products,$paramHash=array()) {
	global $diagram_colors;
	
	if (!isset($paramHash["width"])) {
		$paramHash["width"]=bar_width;
	}
	if (!isset($paramHash["bar_height"])) {
		$paramHash["bar_height"]=bar_height;
	}
	
	$retval="";
	switch ($paramHash["display"]) {
	case "gc_yield": // one big table
	case "yield":
		$retval.="<table cellspacing=0 class=\"diagram\"><tbody>";
	break;
	default:
		$yield_text=" (".s("diagram_yield").") : ";
		$gc_yield_text=" (".s("diagram_gc_yield").") : ";
		$product_text=s("product");
	}
	
	if (is_array($products)) foreach ($products as $a => $product) {
		if (is_array($paramHash["show_idx"]) && !in_array($a,$paramHash["show_idx"])) {
			continue;
		}
		
		switch ($paramHash["display"]) {
		case "gc_yield":
		case "yield":
			$value=$product[ $paramHash["display"] ];
			$retval.=
				getYieldBar($paramHash,$value,$paramHash["texts"][$a].yieldFmt($value),$diagram_colors[$a],$paramHash["style"]);
		break;
		default: // one table with stacked bars
			$retval.="<table cellspacing=0 class=\"diagram\"><tbody><tr>";
			$product_name=ifempty($product["standard_name"],$product_text." ".($a+1));
			// one bar in color with yield and gc_yield, the yield gets a "2px solid black" border, the  gc_yieldgets a "1px dotted black" border
			if ($product["yield"]>$product["gc_yield"]) {
				$retval.=
					getYieldBar($paramHash,$product["gc_yield"],$product_name.$gc_yield_text.yieldFmt($product["gc_yield"]),$diagram_colors[$a],DIAGRAM_BAR_STACKED).
					getYieldBar($paramHash,$product["yield"]-$product["gc_yield"],$product_name.$yield_text.yieldFmt($product["yield"]),$diagram_colors[$a],DIAGRAM_BAR_STACKED_HIGHLIGHTED);
			}
			else {
				$retval.=
					getYieldBar($paramHash,$product["yield"],$product_name.$yield_text.yieldFmt($product["yield"]),$diagram_colors[$a],DIAGRAM_BAR_STACKED_HIGHLIGHTED).
					getYieldBar($paramHash,$product["gc_yield"]-$product["yield"],$product_name.$gc_yield_text.yieldFmt($product["gc_yield"]),$diagram_colors[$a],DIAGRAM_BAR_STACKED);
			}
			$retval.="</tr></tbody></table>";
		}
	}
	switch ($paramHash["display"]) {
	case "gc_yield":
	case "yield":
		$retval.="</tbody></table>";
	break;
	}
	return $retval;
}

/*
$test=array(
	array("yield" => 30, "gc_yield" => 10, ),
	array("gc_yield" => 30, ),
	array("yield" => 10, ),
	array("yield" => 30, ),
	array("gc_yield" => 100, "yield" => 100),
);
echo "<html><body>".getGraphicalYield($test);
*/

function stereoStructureRelationship($smiles1,$stereo1,$smiles2,$stereo2) { // return values: 0=undefined, 1=identical, 2=enantiomers, 3=diastereomers, 4=completely different
	if (empty($stereo1) || empty($stereo2)) {
		return STEREO_UNDEFINED;
	}
	if ($smiles1!=$smiles2) {
		return STEREO_DIFFERENT;
	}
	elseif ($stereo1==$stereo2) {
		return STEREO_IDENTICAL;
	}
	$frags1=preg_split("/@{1,2}/",$stereo1,-1,PREG_SPLIT_DELIM_CAPTURE);
	$frags2=preg_split("/@{1,2}/",$stereo2,-1,PREG_SPLIT_DELIM_CAPTURE);
	if (count($frags1)!=count($frags2)) {
		return STEREO_UNDEFINED;
	}
	$stereocenter=0;
	$diff_stereo=0;
	for ($a=0;$a<count($frags1);$a++) {
		switch ($frags1[$a]) {
		case "@":
		case "@@":
			if (!in_array($frags2[$a],array("@","@@"))) {
				return STEREO_UNDEFINED;
			}
			elseif ($frags1[$a]!=$frags2[$a]) {
				$diff_stereo++;
			}
			$stereocenter++;
		break;
		default:
			if ($frags1[$a]!=$frags2[$a]) {
				return STEREO_UNDEFINED;
			}
		}
	}
	if ($stereocenter==$diff_stereo) {
		return STEREO_ENANTIOMERS;
	}
	else {
		return STEREO_DIASTEREOMERS;
	}
}

function getSafetyOverlay(& $row,$type) {
	return "<div onMouseover=\"showRSTooltip(this,".fixQuot($type).",".fixQuot($row["safety_".$type]).")\" onMouseout=\"hideOverlay()\">".strtoupper($type).":&nbsp;".fixBr($row["safety_".$type])."</div>";
}

function showImageOverlay($paramHash) { // $pkName,$db_id,$pk,$w,$h,$mode,$linkTable,$linkPk,$filename=""
// getStructureOverlay($idx,$row,$url,$pkName,$filename,$width,$height) { // 
	global $db_name,$table,$useSvg,$settings; // avoid browser cache problems
	$paramHash["linkParams"]=getSelfRef(array("~script~","db_id","pk","table"));
	
	if (isSelectTable($table)) {
		$paramHash["selectButton"]=true;
	}
	
	$paramHash["useSvg"]=$useSvg;
	$url=fixStr("getGif.php?db_id=".$paramHash["db_id"].
		"&".$paramHash["pkName"]."=".$paramHash["pk"].
		ifNotEmpty("&archive_entity=",$paramHash["archive_entity"]).
		"&db_name=".$paramHash["db_name"]
	);
	
	$paramHash["noOverlay"]=($paramHash["mode"]=="rxn" ? $settings["disable_reaction_mouseover"] : $settings["disable_molecule_mouseover"]);
	$commonParams=makeHTMLParams($paramHash,array("id","width","height")).
		" onMouseover=\"showImageOverlaySelect(event,this,".htmlspecialchars(json_encode($paramHash)).")\"".
		" onMouseout=\"hideOverlay()\"";
	
	if (!$paramHash["noOverlay"] && ($paramHash["posFlags"] & OVERLAY_CONT_UPDATE)) {
		$commonParams.=" onMousemove=\"alignOverlay(event,".$paramHash["posFlags"].")\"";
	}
	
	if ($useSvg) {
		$retval="<object data=".$url.$commonParams." type=\"image/svg+xml\"></object>"; // <param name=\"src\" value=".$url.">
	}
	else {
		$retval="<img src=".$url.$commonParams.">";
	}
	return $retval;
}

function isSelectTable($table) {
	global $selectTables;
	return (count($selectTables) && in_array($table,$selectTables));
}

function getTHeadText($col,$column_data,$index="") {
	global $g_settings;
	$retval="";
	switch ($col) {
	case "oa_amount":
		$retval.=s("amount");
	break;
	case "oa_cat_no":
		$retval.=s("cat_no");
	break;
	case "safety_sym":
		$retval.=s("safety_sym_short");
	break;
	case "safety_r_s":
		if ($g_settings["use_rs"]) {
			$retval.=s("safety_r_s")." ";
		}
		if ($g_settings["use_ghs"]) {
			$retval.=s("safety_h_p")." ";
		}
	break;
	case "reaction_conditions":
	break;
	default:
		$retval.=s($col);
	}
	if (!isEmptyStr($index)) {
		if (isset($column_data["int_names"])) {
			$int_names_keys=array_keys($column_data["int_names"]);
			$retval.=s($int_names_keys[$index]);
		}
		else { // reactant, etc
			$retval.=" ".$column_data["prefix"]; // R
			if ($column_data["useLetter"]) {
				$retval.=numToLett($index+1);
			}
			else {
				$retval.=($index+1);
			}
		}
	}
	return $retval;
}

function getHiddenColsOverlay($table,$hidden) {
	global $columns;
	if (count($hidden)) {
		// control for additional columns
		$retval.=" <div id=\"showColumnOverlay\" style=\"display:none\" onMouseover=\"cancelOverlayTimeout()\" onMouseout=\"hideOverlayId(&quot;showColumnOverlay&quot;);\">";
		foreach ($hidden as $fullCol) {
			if (strpos($fullCol,".")!==FALSE) { // indexed column, like reactant.#
				list($col,$index)=explode(".",$fullCol,2);
			}
			else {
				$col=$fullCol;
				unset($index);
			}
			$column_data=$columns[$table][$col];
			if (!is_array($column_data)) {
				$column_data=array("display" => $column_data);
			}
			$retval.="<a href=\"javascript:void showCol(".fixQuot($fullCol).")\">".
				getTHeadText($col,$column_data,$index).
				"</a><br>";
		}
		$retval.="</div>";
	}
	return $retval;
}

function getReferenceLink($paramHash) {
	global $pk_name;
	$table=$paramHash["table"];
	$db_id=$paramHash["db_id"];
	$pk=$paramHash["pk"];
	switch ($table) {
	default:
		$title=s($table);
	}
	return "<a href=".fixStr("edit.php?query=&table=".$table."&db_id=".$db_id."&pk=".$pk."&".getSelfRef(array("~script~","table","query","cached_query","order_by","no_cache",$pk_name,"fields","page","per_page","dbs")))." class=\"imgButtonSm\" style=\"margin-right:0px\"><img src=\"lib/".$table."_sm.png\" border=\"0\"".getTooltipP($title)."></a>";	
}

function getCombiButton($paramHash) {
// common: $table
// filter: $number,$this_pk_name=null,$db_id="",$pk="",$op="eq"
// add: $parameter,$lang_key

// Symbol nur 1x zeigen
	global $pk_name;
	$table=$paramHash["table"];
	$number=intval($paramHash["number"]);
	$db_id=$paramHash["db_id"];
	
	$add_button=false;
	if (isset($paramHash["parameter"]) && ($paramHash["add_always"] || mayCreate($table,$paramHash["db_id"]))) {
		$text=$paramHash["lang_key"];
		if (empty($text)) {
			$text=s("add1").s($table).s("add2");
		}
		else {
			$text=s($text);
		}
		$add_button=true;
	}
	
	if ($paramHash["hide_number"]) {
		$title=$text;
	}
	else {
		switch ($table) {
		default:
			$title=$number."&nbsp;".(($number==1)?s($table):s($table."_pl"));
		}
	}
	
	$retval=array();
	$image="<img src=";
	if (isset($paramHash["image"])) {
		$image.=$paramHash["image"];
	}
	else {
		$image.="lib/".$table."_sm.png";
	}
	$image.=" border=\"0\"".getTooltipP($title).">";
	
	if (!$paramHash["hide_number"]) {
		// # button
		if ($number>0 && isset($paramHash["this_pk_name"])) { //"&dbs=".$db_id.
			$url=getCombiButtonURL($paramHash);
		}
		else {
			$url.="Javascript:void(0)";
		}
		$retval[0]="<a href=".fixStr($url)." class=\"imgButtonSm\" style=\"margin-right:0px\"><nobr>".$image."&nbsp;";
		if (!empty($paramHash["number_id"])) {
			$retval[0].="<span id=".fixStr($paramHash["number_id"]).">".$number."</span>";
		}
		else {
			$retval[0].=$number;
		}
		$retval[0].="</nobr></a>";
	}
	
	// + button
	if ($add_button) {
		$retval[1]="<a href=\"edit.php?".getSelfRef(array("~script~","iframe","table","fields","db_id",$pk_name,"cached_query"))."&desired_action=new&table=".$table."&".$paramHash["parameter"]."\" class=\"imgButtonSm\" style=\"margin-left:0px\">";
		if ($paramHash["hide_number"]) { // show image with plus instead
			$retval[1].=$image;
		}
		$retval[1].="<span".getTooltipP($text).">+</span>";
		$retval[1].="</a>";
	}
	return alignHorizontal($retval);
}

function getImgAddButton($table,$parameter,$lang_key) {
	global $pk_name;
	return "<a href=\"edit.php?".getSelfRef(array("~script~","iframe","table","fields","db_id",$pk_name))."&desired_action=new&table=".$table."&".$parameter."\" class=\"imgButtonSm\"><img src=\"lib/".$table."_sm.png\" border=\"0\"".getTooltip($lang_key).">+</a>";
}

function getEditURL(& $row) {
	global $pk_name;
	return "edit.php?".getSelfRef(array("~script~","db_id","pk"))."&db_id=".$row["db_id"]."&pk=".$row[$pk_name];
}

function getEditLink(& $row) { // stays in iframe
	// global $query,$table,$pk_name;
	// gibt link zu details(browsemode) zurück
	return "<a href=".fixStr(getEditURL($row))." class=\"imgButtonSm\"><img src=\"lib/details_sm.png\" border=\"0\"".getTooltip("details")."></a>";
}

function getFields(& $columns,$listvisible="") {
	global $g_settings;
	
	$visible=array();
	$hidden=array();
	
	if ($listvisible=="all") {
		$listvisible=array();
		$visible_count=-1;
	}
	elseif ($listvisible=="") {
		$listvisible=array();
		$visible_count=0;
	}
	else {
		$listvisible=explode(",",$listvisible);
		$visible_count=count($listvisible);
	}
	
	if (!empty($_REQUEST["ref_reaction_db_id"]) && !empty($_REQUEST["ref_reaction_id"])) {
		$listvisible[]="compare_rxn";
	}
	
	if (is_array($columns)) foreach ($columns as $col => $data) {
		if (is_array($data)) {
			$display=$data["display"];
			if (isset($data["int_names"])) {
				$multiple=count($data["int_names"]);
				$int_names=array_keys($data["int_names"]);
			}
			else {
				$multiple=$data["multiple"];
			}
		}
		else {
			$display=$data;
			unset($multiple);
		}
		
		if (isset($multiple)) {
			for ($a=0;$a<$multiple;$a++) {
				$text=$col.".".$a;
				if ($visible_count==-1 || ($visible_count==0 && ($display&1)==0) || in_array($text,$listvisible) ) {
					$visible[]=$text;
				}
				elseif (($display & 4) || ($col=="reaction_conditions" && !$g_settings["reaction_conditions"][ $int_names[$a] ])) {
					
				}
				else {
					$hidden[]=$text;
				}
			}
		}
		else {
			$text=$col;
			if ($visible_count==-1 || ($visible_count==0 && ($display&1)==0) || in_array($text,$listvisible) ) {
				$visible[]=$text;
			}
			elseif ($display & 4) {
				
			}
			else {
				$hidden[]=$text;
			}
		}
	}
	return array($visible,$hidden);
}

function getDelLink(& $row,$idx="") { // stays in frame
	global $tables,$table,$mayDelete;
	if (!$mayDelete[ $row["db_id"] ] || $tables[$baseTable]["noDelete"] || $row["allowDelete"].""=="0") {
		return "";
	}
	$primKey_id=& $row[ getShortPrimary($table) ];
	return "<a href=\"javascript:void del(-1,".$primKey_id.",".fixNull($idx).")\" class=\"imgButtonSm\"><img src=\"lib/del_sm.png\" border=\"0\"".getTooltip("delete")."></a>";
}

function addHeadline(& $output,$fields,$paramHash) {
	$fieldIdx=0;
	if (is_array($fields)) foreach ($fields as $field) { // spaltenköpfe
		addTHeadCell($output,$fieldIdx,$field,$paramHash);
		$fieldIdx++; // function can change fieldIdx to output multiple cols per field
	}
}

function outputList($res,$fields,$paramHash=array()) {
	global $table,$permissions,$person_id;
	
	if ($paramHash["output_type"]!="html" && count($res)==0) {
		closeWin();
		return;
	}
	
	$separatorField=$paramHash["separatorField"];
	$table_id=ifempty($paramHash["table_id"],"table"); // für mehrere Tabellen auf einer Seite, a#name geht dann leider nicht mehr
	$files=array();
	
	$general_name=ifempty(s($table),"table");
	switch ($paramHash["output_type"]) {
	case "json":
		return json_encode($res);
	break;
	// every database into individual csv
	case "zip/csv":
	case "csv":
		$zip_name=$general_name.".zip";
		$csv_name=$general_name.".csv";
		$retval="";
		
		addHeadline($retval,$fields,$paramHash);
		$retval.=csv_line;
	break;
	case "zip/xls":
	case "xls":
		// load Extension
		require_once "Spreadsheet/Excel/Writer.php";
		
		// start new xls
		$zip_name=$general_name.".zip";
		$xls_name=$general_name.".xls";
		
		$tmp_filename=oe_tempnam(@System::tmpdir(), "OLE_PPS_Root"); // use PEAR function like OLE module
		$workbook=new Spreadsheet_Excel_Writer($tmp_filename); // write to temp file, unfortunately, impossible to get as variable content directly
		
		if ($paramHash["output_type"]=="xls") {
			$workbook->send($xls_name); // output xls headers
		}
		
		$paramHash["headline_format"]=& $workbook->addFormat(array("Bold" => 1, ));
		
		if ($separatorField=="") {
			// start new worksheet
			$retval=& $workbook->addWorksheet(strcut(s("table")." 1",31));
		}
	break;
	case "sdf":
		$sdf_name=$general_name.".sdf";
		$retval="";
		// no headline
	break;
	case "html":
		$noResMessage=ifempty($paramHash["noResMessage"],s("no_results"));
		if (count($res)==0 && !$paramHash["showZeroResults"]) {
			return $noResMessage;
		}
		$retval="<table class=\"listtable\" width=\"100%\"><thead><tr>";
		
		// headlines for all, whereas for xls headlines for each worksheet
		addHeadline($retval,$fields,$paramHash);
	break;
	}
	
	
	if ($paramHash["output_type"]=="html") {
		$retval.="</tr></thead><tbody".ifnotempty(" id=\"",$table_id,"\"").">\n";
	}
	
	$prevseparatorField=null; // is always different
	
	$subidx=0;
	if (count($res)) {
		foreach ($res as $idx => $row) { // zeilen
			// Zwischenzeile wie Edukte, Produkte oder Datenbank xyz
			if ($separatorField!="") {
				if ($row[$separatorField]!=$prevseparatorField) {
					// new section
					$subidx=0;
					
					switch ($paramHash["output_type"]) {
					case "zip/csv":
					case "csv":
						$retval.=getSeparatorLine($separatorField,$row).csv_line;
					break;
					
					case "zip/xls":
					case "xls":
						// start new worksheet
						$retval=& $workbook->addWorksheet(strcut(getSeparatorLine($separatorField,$row),31));
						
						// add column heads again
						addHeadline($retval,$fields,$paramHash);
						
					break;
					case "sdf":
						// nothing, have field db_id instead
					break;
					case "html":
						$retval.="<tr><td colspan=\"".count($fields)."\" class=\"separatorLine\">".getSeparatorLine($separatorField,$row)."</td></tr>\n";
					break;
					}
					$prevseparatorField=$row[$separatorField];
				}
			}
			
			// prevent excessive information leakage
			if ($table=="reaction" && $paramHash["output_type"]!="html" && ($permissions & _lj_read_all)==0 && $row["person_id"]!=$person_id) {
				continue;
			}
			
			// eigentliche Datenzeile
			switch ($paramHash["output_type"]) {
			case "zip/csv":
			case "csv":
				$fieldIdx=0;
				if (is_array($fields)) foreach ($fields as $field) { // zellen
					addTBodyCell($retval,$files,$idx,$subidx,$fieldIdx,$row,$field,$paramHash);
					$fieldIdx++; // function can change fieldIdx to output multiple cols per field
				}
				$retval.=csv_line;
			break;
			
			case "zip/xls":
			case "xls":
				$fieldIdx=0;
				if (is_array($fields)) foreach ($fields as $field) { // zellen
					addTBodyCell($retval,$files,$idx,$subidx,$fieldIdx,$row,$field,$paramHash);
					$fieldIdx++; // function can change fieldIdx to output multiple cols per field
				}
			break;
			case "sdf":
				// Molfile, db_id, then fields as chosen by the user
				$retval.=fixLineEndMS($row["molfile_blob"]).
					getSDCol("db_id").
					$row["db_id"]." ".$row["show_db_beauty_name"]."\r\n\r\n";
				if (is_array($fields)) foreach ($fields as $field) { // zellen
					addTBodyCell($retval,$files,$idx,$subidx,$fieldIdx,$row,$field,$paramHash);
					$fieldIdx++; // function can change fieldIdx to output multiple cols per field
				}
				$retval.="\$\$\$\$\r\n";
			break;
			case "html":
				$retval.="<tr".ifnotempty(" id=\"",$table_id,"_".$idx."\"").">";
				$fieldIdx=0;
				if (is_array($fields)) foreach ($fields as $field) { // zellen
					addTBodyCell($retval,$files,$idx,$subidx,$fieldIdx,$row,$field,$paramHash);
					$JScode.=getDatasetJS($idx,$row,$field);
					$fieldIdx++; // function can change fieldIdx to output multiple cols per field
				}
				$retval.="</tr>\n";
			break;
			}
			
			$subidx++;
		}
		
		// Post-JS
		if ($paramHash["output_type"]=="html") {
			if (is_array($fields)) foreach ($fields as $field) {
				$JScode.=getPostJS($field);
			}
		}
	}
	
	switch ($paramHash["output_type"]) {
	case "zip/csv":
	case "zip/xls":
		// create zip with $files
		require_once "lib_io.php";
		require_once "File/Archive.php";
		
		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=".$zip_name);
		getCompressHeader("zip");
		
		$zip=File_Archive::toArchive(null,File_Archive::toOutput(),"zip");
		
		if ($paramHash["output_type"]=="zip/xls") {
			// complete xls
			$workbook->close();
			
			// add file contents to zip
			$zip->newFile($xls_name);
			$zip->writeData(@file_get_contents($tmp_filename));
			
			@unlink($tmp_filename);
		}
		else {
			$zip->newFile($csv_name);
			$zip->writeData($retval);
		}
		
		if (is_array($files)) foreach ($files as $filename => $contents) {
			$zip->newFile($filename);
			$zip->writeData($contents);
		}
		
		$zip->close();
		
	break;
	case "csv":
		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=".$csv_name);
		header(getHeaderFromMime("csv"));
		echo $retval;
	break;
	case "xls":
		// complete xls
		$workbook->close();
		// output file contents directly
		@readfile($tmp_filename);
		@unlink($tmp_filename);
	break;
	case "sdf":
		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=".$sdf_name);
		header(getHeaderFromMime("sdf"));
		echo $retval;
	break;
	case "html":
		$retval.="</tbody></table>".
		script.
		$JScode.
		_script;
		
		return $retval;
	break;
	}
}

function getPostJS($field) {
	switch ($field) {
	case "compare_rxn":
		return "applyComparisonList(\"list\");\n";
	break;
	}
}

function getDatasetJS($idx,$row,$field) {
	switch ($field) {
	case "compare_rxn":
		return "compare_obj[".$idx."]=".json_encode($row).";\n";
	break;
	case "literature_citation":
		return "displayCitation(".$idx.",".json_encode($row).");";
	break;
	case "inventarisation":
		$row=array_key_filter($row,array("inventory_check_by","inventory_check_when","actual_amount","amount_unit"));
		$row["actual_amount"]=roundLJ($row["actual_amount"]);
		return "displayInventory(".$idx.",".json_encode($row).");\n";
	break;
	}
}

function getSeparatorLine($field,$row) {
	switch ($field) {
	case "db_id": // für Moleküle oder Gebinde
		return s("results_from1").$row["show_db_beauty_name"].s("results_from2");
	break;
	}
}

function addTHeadCell(& $output,& $fieldIdx,$fullCol,$paramHash=array()) { // gibt für die spalte col die überschrift aus, bei nobuttons wird nur der text ausgespuckt
	global $columns,$table,$view_options;
	if (strpos($fullCol,".")!==FALSE) { // indexed column, like reactant.#
		list($col,$index)=explode(".",$fullCol,2);
	}
	else {
		$col=$fullCol;
		unset($index);
	}
	
	if (startswith($col,"links_")) {
		$link_col=true;
	}
	
	$column_data=$columns[$table][$col];
	if (!is_array($column_data)) {
		$column_data=array();
	}
	
	$retval=getTHeadText($col,$column_data,$index);
	
	switch ($paramHash["output_type"]) {
	case "zip/csv":
	case "csv":
		if ($fieldIdx>0) {
			$output.=csv_sep;
		}
		$output.=fixCSV(strip_tags(html_entity_decode($retval)));
	break;
	case "zip/xls":
	case "xls":
		if (!$link_col) {
			switch ($col) {
			case "yield":
			case "gc_yield":
			case "remaining_reactants":
				$col_options_key=$columns[$table][$col]["column_options"];
				if (empty($view_options[$col_options_key]["fields"])) {
					$view_options[$col_options_key]["fields"]=getDefaultFields($col_options_key);
				}
			
				$main_text=$retval;
				$retval=array();
				if (is_array($view_options[$col_options_key]["fields"])) foreach ($view_options[$col_options_key]["fields"] as $field) {
					switch ($field) {
					case "diagram":
					break;
					default;
						$retval[]=$main_text." ".s($field);
					}
				}
			break;
			case "reactant":
			case "reagent":
			case "product":
				$col_options_key=$columns[$table][$col]["column_options"];
				if (empty($view_options[$col_options_key]["fields"])) {
					$view_options[$col_options_key]["fields"]=getDefaultFields($col_options_key);
				}
				
				$main_text=$retval;
				$retval=array();
				if (is_array($view_options[$col_options_key]["fields"])) foreach ($view_options[$col_options_key]["fields"] as $field) {
					switch ($field) {
					case "molfile_blob":
					break;
					default;
						$retval[]=$main_text." ".s($field);
					}
				}
			break;
			default:
				$retval=array($retval);
			}
			
			foreach ($retval as $retval_entry) {
				// write cell(s)
				$output->write(0,$fieldIdx,strip_tags(html_entity_decode(utf8_decode(trimNbsp($retval_entry)))),$paramHash["headline_format"]);
				$fieldIdx++;
			}
			$output->repeatRows(0);
			// one added too much
			$fieldIdx--;
		}
	break;
	case "html":
		$column_data=$columns[$table][$col];
		if (!is_array($column_data)) {
			$column_data=array("display" => $column_data);
		}
		
		$td="<td>";
		if ($link_col) {
			$td="<td class=\"noprint\">";
		}
	
		if (!$paramHash["noButtons"]) {
			switch ($col) {
			// reaction_property
			case "yield":
			case "gc_yield":
				// Sortier-Links (nur 1. Produkt)
				$buttons=getSortLinks($fullCol);
			// kein break
			case "remaining_reactants":
			case "reactant":
			case "reagent":
			case "product":
				// option-button
				$buttons.=getListOptionsMenu( $column_data["column_options"] );
			break;
			
			case "temperature":
			case "duration":
				// zZt nicht sortierbar
			break;
			
			case "inventarisation":
				$buttons=" ".s("last_check_by").getSortLinks($fullCol);
			break;

			case "safety_danger":
			case "safety_cancer":
			case "safety_mutagen":
			case "safety_reprod":
			case "person_name":
			case "person_institution":
			case "username":
			case "permissions":
			case "person_barcode";
			case "storage_name":
			case "institution":
			case "storage_barcode";
			case "from_person":
			case "issued":
			case "message_subject":
			case "do_until":
			case "lab_journal_entry":
			case "reaction_title":
			case "reaction_carried_out_by":
			case "reaction_started_when":
			case "reaction_project":
			case "reaction_status":
			case "molecule_name":
			case "cas_nr":
			case "chemical_storage_conc":
			case "emp_formula_short":
			case "mw":
			case "density_20":
			case "mp_short":
			case "bp_short":
			case "n_20":
			case "comment_mol":
			case "amount":
			case "lot_no":
			case "container":
			//~ case "purity":
			case "open_date":
			case "expiry_date":
			case "storage":
			case "chemical_storage_barcode":
			case "borrowed_by":
			case "comment_cheminstor":
			case "disposed_when":
			case "disposed_by":
			case "migrate_id_mol":
			case "migrate_id_cheminstor":
			case "supplier":
			case "cat_no":
			case "price":
			case "vendor":
			case "order_date":
			case "delivery_date":
			case "comp_order_date":
			case "sap_bestell_nr":
			case "sap_stamm_nr":
			case "bessi":
			case "order_person":
			case "chemical_storage_bilancing":
			case "molecule_bilancing":
				$buttons=getSortLinks($fullCol);
			break;
			//~ case "links_chem":
				//~ $retval.=s("do_select")."<br><a href=\"javascript:void setAll(1)\">".s("select_all")."</a> <a href=\"javascript:void setAll(0)\">".s("select_none")."</a>";
			//~ break;
			}
			
			if (($column_data["display"]&2)==0) {
				$buttons.=getHideColLink($fullCol);
			}
		}
	
		if ($link_col) {
			if ($paramHash["order_alternative"]) {
				$buttons.=s("possible_choice");
			}
			else {
				$buttons.="<input type=\"checkbox\" id=\"sel_all\" onClick=\"setSelect()\">";
			}
		}
		
		$output.=$td.$retval.$buttons."</td>";
	break;
	}
	
}

function getHideColLink($fullCol) {
	return " <a href=\"javascript:void hideCol(&quot;".$fullCol."&quot;)\" class=\"noprint\"><img src=\"lib/hide_column.png\" border=\"0\" height=\"16\" width=\"16\" style=\"vertical-align:middle\"".getTooltip("hide_column")."></a>";
}

function getDefaultFields($col_options_key) {
	global $column_options;
	$retval=array();
	if (is_array($column_options[$col_options_key]["fields"])) foreach ($column_options[$col_options_key]["fields"] as $field_name => $data) {
		if (!$data["defaultHide"]) {
			$retval[]=$field_name;
		}
	}
	return $retval;
}

function getSDCol($col) {
	return ">  <".$col.">\r\n";
}

$triStateMap=array("1" => "yes", "2" => "no");
function addTBodyCell(& $output,& $files,$idx,$subidx,& $fieldIdx,$row,$col,$paramHash=array()) { // gibt für die spalte col und zeile idx die ergebniszelle aus
	global $permissions,$person_id,$db_name,$db_user,$mayCreate,$mayWrite,$priority_colors,$tables,$table,$pk_name,$selectTables,$columns,$view_options,$column_fields,$column_options,$g_settings,$settings,$suppliers,$triStateMap; // ,$query
	$noButtons=$paramHash["noButtons"];
	if (strpos($col,".")!==FALSE) { // indexed column, like reactant.#
		list($col,$index)=explode(".",$col,2);
		
		if (is_array($columns[$table][$col]) && is_array($columns[$table][$col]["int_names"])) {
			$int_names_keys=array_keys($columns[$table][$col]["int_names"]);
			$index=$int_names_keys[$index];
			/* $col=$int_names_keys[$index];
			unset($index); */
		}
	}
	
	$idText=" id=".fixStr($col.ifNotEmpty("_",$index)."_".$idx);
	$td="<td".$idText.">"; // standard
	
	if (startswith($col,"links_")) {
		$link_col=true;
		$td="<td".$idText." class=\"noprint\">"; // standard
	}
	
	$edit=array();
	$special=array();
	
	$sdf_prefix="";
	if ($paramHash["output_type"]=="sdf") {
		if ($link_col || in_array($col,array("molfile_blob","structure",))) {
			return;
		}
		$sdf_prefix.=getSDCol($col);
	}
	
	switch ($col) {
	
	// standard group------------------------------------------------------------------------------------------------------------------
	// ordering
	case "cost_centre":
	case "cost_centre_name":
	case "acc_no":
	case "days_count":
	case "comment":
	case "item_identifier":
	case "beautifulCatNo":
	case "catNo":
	case "items":
	case "packages":
	case "accepted_order_created_by":
	// lab_journal
	case "project_name":
	case "lab_journal_code":
	case "project_name":
	case "reaction_title":
	case "chemical_storage_type_name":
	case "molecule_type_name":
	case "reaction_type_name":
	case "reaction_carried_out_by":
	// analytics
	case "standard_name":
	case "analytics_method_name":
	case "analytical_data_identifier":
	case "measured_by":
	case "analytics_device_name":
	case "analytics_device_driver":
	case "analytics_device_url":
	case "analytics_type_name":
	case "analytics_type_code":
	// literature
	case "sci_journal_name":
	case "sci_journal_abbrev":
	case "sci_journal_impact_factor":
	case "sci_journal_publisher":
	case "keywords":
	// other db
	case "db_beauty_name":
	case "username":
	case "host":
	case "db_name":
	case "db_user":
	// inventory
	case "storage_name":
	case "institution_name":
	case "smiles_stereo":
	case "safety_text":
	case "safety_danger":
	case "safety_cancer":
	case "safety_mutagen":
	case "safety_reprod":
	case "migrate_id_mol":
	case "migrate_id_cheminstor":
	case "sigle":
	case "sap_bestell_nr":
	case "sap_stamm_nr":
	case "bessi":
	case "order_person":
	case "disposed_by":
	case "cat_no":
	case "lot_no":
		$retval=$row[$col];
	break;
	
	case "comment_mol":
	case "comment_cheminstor":
	case "comment_supplier_offer":
		$raw=true;
		$retval=$row[$col];
	break;
	
	// cut at 100 group------------------------------------------------------------------------------------------------------------------
	// ordering
	case "customer_comment":
	case "central_comment":
	// lab journal
	case "realization_text":
	case "realization_observation":
	case "analytics_method_text":
	case "project_text":
	// message
	case "message_text":
		$raw=true;
		$retval=strcut(strip_tagsJS($row[$col]),100); // cutting makes html invalid
	break;
	
	// cut at 50 with tooltip group------------------------------------------------------------------------------------------------------------------
	case "literature_title":
		$raw=true;
		$retval="<span title=".fixStr($row[$col]).">".strcut(strip_tagsJS($row[$col]),50)."</span>"; // cutting makes html invalid
	break;
	
	// numeric group------------------------------------------------------------------------------------------------------------------
	// inventory
	case "reaction_conditions": // multiple
		switch ($index) {
		case "solvent":
			$retval=$row[$index];
		break;
		default:
			$td="<td".$idText." class=\"numeric\">";
			$retval=$row[$index];
		}
	break;
	
	case "density_20":
	case "n_20":
	/* case "temperature":
	case "grounding_time":
	case "duration":
	case "solvent_amount":
	case "h2press": */
		$td="<td".$idText." class=\"numeric\">";
		$retval=$row[$col];
	break;
	
	// date group------------------------------------------------------------------------------------------------------------------
	// ordering
	case "supplier_delivery_date":
	case "customer_delivery_date":
	case "billing_date":
	case "from_date":
	case "to_date":
	case "start_date":
	case "end_date":
	case "so_date":
	case "comp_order_date":
	// lab journal
	case "reaction_started_when":
	case "project_created_when":
	// message
	case "issued":
	case "do_until":
	// inventory
	case "order_date":
	case "open_date":
	case "expiry_date":
	case "disposed_when":
		$retval=toDate($row[$col]);
	break;
	
	// price group------------------------------------------------------------------------------------------------------------------
	// ordering
	case "so_price":
	case "grand_total":
		$retval=formatPrice($row,$col);
	break;
	
	// person group------------------------------------------------------------------------------------------------------------------
	case "from_person":
	case "person_name":
		$retval=formatPersonNameCommas($row);
	break;
	case "borrowed_by":
		$retval="";
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			$retval.="<span id=".fixStr("borrowed_by_".$idx).">";
		}
		$retval.=htmlspecialchars(formatPersonNameCommas($row));
		if ($paramHash["output_type"]=="html") {
			$retval.="</span>";
		}
	break;
	case "owner_person_id":
		$retval=formatPersonNameCommas($row,"owner_");
	break;
	
	// ordering system, show conflicts between chemical_order and accepted_order
	
	
	// edit del group------------------------------------------------------------------------------------------------------------------
	// lab journal
	case "links_analytical_data":
	case "links_analytics_method":
	case "links_chemical_storage_type":
	case "links_molecule_type":
	case "links_reaction_type":
	// message
	case "links_message":
	case "links_message_in":
	case "links_message_out":
	// inventory
	case "links_other_db":
	case "links_mpi_order":
		$edit[]=getEditLink($row);
		$edit[]=getDelLink($row,$idx);
	break;
	
	// custom stuff------------------------------------------------------------------------------------------------------------------
	case "links_analytics_device":
		$edit[]=getEditLink($row);
		$edit[]=getDelLink($row,$idx);
		
		// neue Methode hinzufügen
		$retval=getCombiButton(array(
			"table" => "analytics_method", 
			"number" => $row["analytics_method_count"], 
			"this_pk_name" => "analytics_device.analytics_device_id", 
			"db_id" => $row["db_id"], 
			"pk" => $row["analytics_device_id"], 
			"parameter" => "db_id=".$row["db_id"]."&analytics_device_id=".$row["analytics_device_id"], 
		));
	break;
	
	case "links_analytics_type":
		$edit[]=getEditLink($row);
		$edit[]=getDelLink($row,$idx);
		
		// neues Gerät hinzufügen
		$retval=getCombiButton(array(
			"table" => "analytics_device", 
			"number" => $row["analytics_device_count"], 
			"this_pk_name" => "analytics_type.analytics_type_id", 
			"db_id" => $row["db_id"], 
			"pk" => $row["analytics_type_id"], 
			"parameter" => "db_id=".$row["db_id"]."&analytics_type_id=".$row["analytics_type_id"], 
		));
	break;
	
	case "supplier":
		if (is_array($suppliers[ $row[$col] ])) {
			$retval=$suppliers[ $row[$col] ]["name"];
		}
		else {
			$retval=$row[$col];
		}
	break;
	case "chemical_storage_barcode":
		if (!empty($row[$col])) {
			$retval=$row[$col];
		}
		else {
			$retval=getEAN8(findBarcodePrefixForPk("chemical_storage"),$row["chemical_storage_id"]);
		}
	break;
	case "mpi_order_item":
		for ($a=0;$a<min(5,count($row["mpi_order_item"]));$a++) {
			$mpi_order_item=& $row["mpi_order_item"][$a];
			$retval.=ifnotempty("",$mpi_order_item["amount"]," ".$mpi_order_item["amount_unit"]);
			if ($paramHash["output_type"]=="html") {
				$retval.="<br>";
			}
		}
		if (count($row["mpi_order_item"])>$a) {
			$retval.=s("more1").(count($row["mpi_order_item"])-$a).s("more2");
		}
	break;
	
	case "links_settlement":
		$edit[]=getEditLink($row);
		
		if ($paramHash["output_type"]=="html") {
			$retval=getCombiButton(array(
				"table" => "accepted_order", 
				"number" => $row["accepted_order_count"], 
				"this_pk_name" => "accepted_order.settlement_id", 
				"db_id" => $row["db_id"], 
				"pk" => $row["settlement_id"], 
			)).
			getCombiButton(array(
				"table" => "rent", 
				"number" => $row["rent_count"], 
				"this_pk_name" => "rent.settlement_id", 
				"db_id" => $row["db_id"], 
				"pk" => $row["settlement_id"], 
			));
		}
	break;
	// rent
	case "grand_total_rent":
	case "price_per_day":
		$retval=number_format($row[$col],2)."&nbsp;".$row["price_per_day_currency"];
	break;
	case "links_rent":
		if ($paramHash["output_type"]=="html") {
			$edit[]=getEditLink($row);
			$edit[]=getDelLink($row,$idx);
			
			// link jetzt zurück
			if ($row["end_date"]==invalidSQLDate) {
				$special[]="<a target=\"comm\" class=\"imgButtonSm\" href=\"listAsync.php?".getSelfRef(array("~script~","rent_id"))."&db_id=-1&pk=".$row["rent_id"]."&desired_action=return_rent&idx=".$idx."\" id=\"btn_return_".$idx."\"><img src=\"lib/bring_back_sm.png\" border=\"0\"".getTooltip("return_rent")."></a>";
			}
			
			// link zur abrechnung
			if (!empty($row["settlement_id"])) {
				$special[]=getReferenceLink(array(
					"table" => "settlement", 
					//~ "this_pk_name" => "settlement.settlement_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["settlement_id"], 
				));
			}
		}
	break;
	// supplier offer
	case "supplier":
		$beautifulName=$suppliers[ $row[$col] ]["name"];
		if (empty($beautifulName)) {
			$retval=$row[$col];
		}
		else {
			$retval=$beautifulName." (".$row[$col].")";
		}
	break;
	case "so_purity":
		$td="<td".$idText." class=\"numeric\">";
		$retval=purityFmt($row[$col]);
	break;
	case "so_package_amount":
		$td="<td".$idText." class=\"numeric\">";
		$retval=$row["so_package_amount"]."&nbsp;".$row["so_package_amount_unit"];
	break;
	case "links_supplier_offer":
		if ($paramHash["output_type"]=="html") {
			$edit[]=getEditLink($row);
			$edit[]=getDelLink($row,$idx);
			
			// buy this directly
			$retval=getCombiButton(array(
				"table" => "chemical_order", 
				"parameter" => "db_id=".$row["db_id"]."&supplier_offer_id=".$row["supplier_offer_id"], 
				"hide_number" => true, 
			));
			
			// searchExt
			if ($paramHash["order_alternative"]) {
				$data=array(
					"name" => utf8_encode($row["molecule_name"]), 
					"cas_nr" => utf8_encode($row["cas_nr"]), 
					"supplier" => utf8_encode($row["supplier"]), 
					"catNo" => utf8_encode($row["catNo"]), 
					"beautifulCatNo" => utf8_encode($row["beautifulCatNo"]), 
					"price" => $row["so_price"], 
					"price_currency" => utf8_encode($row["so_price_currency"]), 
					"addInfo" => utf8_encode($row["so_purity"]), 
					"amount" => utf8_encode($row["so_package_amount"]), 
					"amount_unit" => utf8_encode($row["so_package_amount_unit"]), 
				);
				
				$special[]=getDataCheckbox("order_alternative[]",$data);
			}
		}
	break;
	// chemical_order
	case "vendor":
		$retval=getFormattedAdress($row);
		$raw=true;
	break;
	case "package_amount":
		$retval=roundLJ($row["package_amount"])."&nbsp;".$row["package_amount_unit"];
	break;
	case "order_alternative":
		if ($idx==0) { // only once
			// load supplier_scraping
			require_once "lib_supplier_scraping.php";
			require_once_r(installPath."suppliers");
		}
		$raw=true;
		
		$ret_array=array();
		
		if (count($row[$col])) {
			foreach ($row[$col] as $order_alternative) {
				$selected=($order_alternative["order_alternative_id"]==ifempty($row["selected_alternative_id"],$row["customer_selected_alternative_id"]));
				$highlight_start="";
				$highlight_end="";
				$link_start="";
				$link_end="";
				
				if ($selected) {
					if ($paramHash["output_type"]=="html") {
						$highlight_start="<b>";
						$highlight_end="</b>";
					}
					else {
						$highlight_start="[";
						$highlight_end="]";
					}
				}
				elseif (!empty($row["central_order_status"])) { // ordered already
					if ($paramHash["output_type"]=="html") {
						$highlight_start="<small>";
						$highlight_end="</small>";
					}
					else {
						continue; // only selected
					}
				}
				
				// Link
				if (function_exists($suppliers[ $order_alternative["supplier"] ]["getDetailPageURL"])) {
					$url=$suppliers[ $order_alternative["supplier"] ]["getDetailPageURL"]($order_alternative["catNo"]);
				}
				if (!empty($url) && $paramHash["output_type"]=="html") {
					$link_start="<a href=".fixStr($url)." target=\"_blank\">";
					$link_end="</a>";
				}
				
				$ret_array[]=
					$highlight_start.
					$link_start.
					htmlspecialchars($order_alternative["name"]).
					$link_end.
					ifnotempty(" (",
						htmlspecialchars(
							ifempty(
								$suppliers[ $order_alternative["supplier"] ]["name"],
								$order_alternative["supplier"]
							)
						)
					,")").
					$highlight_end;
			}
		}
		else { // only accepted_order, no alternatives
			$ret_array[]=htmlspecialchars($row["name"]).
				ifnotempty(" (",
					htmlspecialchars(
						ifempty(
							$suppliers[ $row["supplier"] ]["name"],
							$row["supplier"]
						)
					)
				,")");
	}
		
		if ($paramHash["output_type"]=="html") {
			$retval=join("<br>",$ret_array);
		}
		else {
			$retval=join("; ",$ret_array);
		}
	break;
	case "order_status":
		// customer_order_status central_order_status
		if (!empty($row["central_order_status"])) {
			$langKeys=getValueList("accepted_order","central_order_status");
			$retval=s($langKeys[ $row["central_order_status"]-1 ]);
		}
		else {
			$langKeys=getValueList("chemical_order","customer_order_status");
			$retval=s($langKeys[ $row["customer_order_status"]-1 ]);
		}
	break;
	case "ordered_by":
		$username=matchingFields($row,"ordered_by_username_cp","ordered_by_username");
		if (!empty($row["ordered_by_person"])) {
			$retval=formatPersonNameCommas($row). // coming from chemical_order
				" <small>".
				$username.
				"</small>";
		}
		else {
			$retval=$username;
		}
		$raw=true;
	break;
	
	case "order_cost_centre": // name of other_db
		$retval=matchingFields($row,"order_cost_centre_cp","order_cost_centre")." ".
			matchingFields($row,"order_acc_no_cp","order_acc_no");
		$raw=true;
	break;
	
	case "customer_order_date":
		$retval=matchingFields($row,"customer_order_date_cp","customer_order_date","toDate");
		$raw=true;
	break;
	
	case "links_my_chemical_order":
	case "links_chemical_order":
		$edit[]=getEditLink($row);
		
		// if not yet accepted, can delete it
		if (empty($row["central_order_status"])) {
			$edit[]=getDelLink($row,$idx);
		}
		//~ else {
			// knopf zum Übernehmen
			if ($paramHash["output_type"]=="html") {
				$retval=getCombiButton(array(
					"table" => "chemical_storage", 
					"lang_key" => "co_to_chemical_storage", 
					"parameter" => "order_uid=".$row["order_uid"], 
					"hide_number" => true, 
				));
			}
		//~ }
	break;
	
	case "links_confirm_chemical_order":
		$edit[]=getEditLink($row);
		$edit[]=getDelLink($row,$idx);
		
		// link to confirm by one click
		$special[]="<a target=\"comm\" class=\"imgButtonSm\" href=\"listAsync.php?".getSelfRef(array("~script~","chemical_order_id"))."&db_id=-1&pk=".$row["chemical_order_id"]."&desired_action=confirm_order&idx=".$idx."\"><img src=\"lib/save_sm.png\" border=\"0\"".getTooltip("confirm_order")."></a>";
	break;
	
	case "links_open_chemical_order":
		$edit[]=getEditLink($row);
		
		// link to accept by one click
		if ($paramHash["output_type"]=="html") {
			$retval=getCombiButton(array(
				"table" => "accepted_order", 
				"lang_key" => "accept_order", 
				"parameter" => "order_uid=".$row["order_uid"], 
				"hide_number" => true, 
			));
		}
		
	break;
	
	case "links_central_chemical_order":
	case "links_accepted_order":
		$edit[]=getEditLink($row);
		$edit[]=getDelLink($row,$idx); // chemical_order will be open again when accepted order is deleted
		
		$statusLink1="<a target=\"comm\" class=\"imgButtonSm\" href=\"listAsync.php?".getSelfRef(array("~script~","accepted_order_id"))."&db_id=-1&pk=".$row["accepted_order_id"]."&desired_action=set_order_status&idx=".$idx."&central_order_status=";
		if ($row["central_order_status"]<3) {
			$special[]=$statusLink1."3\"><img src=\"lib/supplier_delivered_sm.png\" border=\"0\"".getTooltip("ready_for_collection")."></a>"; // abholbereit // fixme image
		}
		if ($row["central_order_status"]<4) {
			$special[]=$statusLink1."4\"><img src=\"lib/customer_delivered_sm.png\" border=\"0\"".getTooltip("order_collected")."></a>"; // abgeholt // fixme image
		}
	break;
	
	case "links_order_comp":
		$edit[]=getEditLink($row);
	break;
	
	case "links_central_chemical_order":
		if ($paramHash["output_type"]=="html") {
			$edit[]=getEditLink($row);
			// link zur abrechnung
			if (!empty($row["settlement_id"])) {
				$special[]=getReferenceLink(array(
					"table" => "settlement", 
					//~ "this_pk_name" => "settlement.settlement_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["settlement_id"], 
				));
			}
		}
	break;
	
	case "links_cost_centre":
		$edit[]=getEditLink($row);
		$edit[]=getDelLink($row,$idx);
		
		// create settelment for this cost_centre
		
	break;
	
	// Message
	case "to_persons":
		$ret_array=array();
		$raw=true;
		if (is_array($row["recipients"])) foreach ($row["recipients"] as $idx => $person) {
			$ret_array[]=formatPersonNameCommas($person);
		}
		if ($paramHash["output_type"]=="html") {
			$retval=nl2br(htmlspecialchars(join("\n",$ret_array)));
		}
		else {
			$retval=join("; ",$ret_array);
		}
	break;
	case "completion_status": // eigentlich unnötig
		$raw=true;
		if ($row["person_id"]==$person_id && $paramHash["output_type"]=="html") {
			$retval=showHidden(array("int_name" => "person_id")).
			showSelect(array(
				"int_name" => "completion_status", 
				"text" => "", 
				"onChange" => "setCompletion()", 
				"int_names" => range(2,6), 
				"langKeys" => getValueList("message_person","completion_status"), 
				"value" => $row["completion_status"], 
			)); // hier änderung ermöglichen
		}
		else {
			$langKeys=getValueList("message_person","completion_status");
			$retval=s($langKeys[ $row["completion_status"]-1 ]);
		}
	break;
	case "completion_status_in": // $row["person_id"]==$person_id
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			if (is_array($row["recipients"])) foreach ($row["recipients"] as $person) {
				if ($person["person_id"]==$person_id) {
					$own_completion_status=$person["completion_status"];
				}
			}
			$retval=showSelect(array(
				"int_name" => "completion_status".$idx, 
				"text" => "", 
				"onChange" => "changeMessageCompletionList(".$row["db_id"].",".$row["message_id"].",this.value)", 
				"int_names" => range(1,6), 
				"langKeys" => getValueList("message_person","completion_status"), 
				"value" => $own_completion_status, 
				"noChangeEffect" => true, 
			)); // allow direct change of level
		}
	break;
	case "completion_status_out": // wenn einer den job gemacht hat, dann ist er auch gemacht
		$status_rank=array(1 => 0, 2 => 1, 5 => 2, 3 => 3, 4 => 4, 6 => 5, 7 => 6); // show "highest" level of completion: completed7,done6,inprog4,accept3,reject5,read2,unread1
		$status_flip=array_flip($status_rank);
		$max_rank=0;
		if (is_array($row["recipients"])) foreach ($row["recipients"] as $person) {
			$max_rank=max($max_rank,$status_rank[ $person["completion_status"] ]);
			if ($max_rank>=6) {
				break;
			}
		}
		$langKeys=getValueList("message_person","completion_status");
		$retval=s($langKeys[ $status_flip[$max_rank]-1 ]);
	break;
	case "message_subject":
		$retval="";
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			$retval="<a href=".fixStr(getEditURL($row))." style=\"color:".$priority_colors[ $row["priority"] ]."\">";
		}
		$retval.=htmlspecialchars($row[$col]);
		if ($paramHash["output_type"]=="html") {
			$retval.="</a>";
		}
	break;
	
	// LabJournal
	case "ref_amount":
		$retval=ifnotempty("",$row["ref_amount"]," ".$row["ref_amount_unit"]);
	break;
	case "lab_journal_status":
		$langKeys=getValueList("lab_journal","lab_journal_status");
		$retval=s($langKeys[ $row["lab_journal_status"]-1 ]);
	break;
	case "links_lab_journal":
		if ($paramHash["output_type"]=="html") {
			$combiParamHash=array(
				"table" => "reaction", 
				"number" => $row["reaction_count"], 
				"this_pk_name" => "reaction.lab_journal_id", 
				"db_id" => $row["db_id"], 
				"pk" => $row["lab_journal_id"], 
			);
			if (($permissions & $tables["reaction"]["writePerm"]) && $person_id==$row["person_id"] && $row["lab_journal_status"]==1) { // neue reaction
				$combiParamHash["parameter"]="db_id=".$row["db_id"]."&lab_journal_id=".$row["lab_journal_id"];
				$combiParamHash["lang_key"]="new_reaction_for_lab_journal";
			}
			$retval=getCombiButton($combiParamHash);
			$edit[]=getEditLink($row);
		}
	break;
	
	// Analytikdaten
	case "reaction_name":
		$retval=$row["lab_journal_code"].$row["nr_in_lab_journal"];
	break;
	case "rc_structure":
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			$retval=showImageOverlay(array(
				"pkName" => "reaction_chemical_id", 
				"db_id" => $row["db_id"], 
				"pk" => $row["reaction_chemical_id"], 
				"width" => rc_gif_x, 
				"height" => rc_gif_y, 
				"posFlags" => OVERLAY_LIMIT_BOTTOM+OVERLAY_LIMIT_RIGHT, 
				"mode" => "mol", 
				"linkTable" => $table, 
				"linkPk" => $row[$pk_name], 
			));
		}
		else {
			$filename=$idx."_rc_structure.mol";
			$retval=$filename;
			$files[$filename]=$row["molfile_blob"];
		}
	break;
	case "analytical_data_image":
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			$retval="<img src=\"getGif.php?db_id=".$row["db_id"]."&analytical_data_id=".$row["analytical_data_id"]."\">";
		}
		else { // wir müssen dafür sorgen, daß bei export weitere felder abgefragt werden
			$filename=$idx."_analytical_data.".compressFormat;
			$retval=$filename;
			$files[$filename]=$row["analytical_data_blob"];
		}
	break;
	
	// Literatur
	case "literature_citation":
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			$td="<td".$idText.">"; //  class=\"literature\"
			$retval="<div id=\"citation_".$idx."\" onMouseover=\"showOtherCitationsList(".$idx."); \" onMouseout=\"hideOtherCitationsList(".$idx."); \"></div><div id=\"other_citations_".$idx."\" style=\"display:none\" onMouseover=\"showOtherCitationsList(".$idx."); \" onMouseout=\"hideOtherCitationsList(".$idx."); \"></div>";
		}
		else {
			$retval=getCitation($row,0,true); // suppress html for xls etc. beautiful formatting in xls currently impossible
		}
	break;
	case "doi":
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			$retval=getDOILink($row[$col]);
		}
		else {
			$retval=$row[$col];
		}
	break;
	case "links_literature":
		if ($paramHash["output_type"]=="html") {
			$retval=alignHorizontal(array(
				getCombiButton(array(
					"table" => "project", 
					"number" => $row["project_count"], 
					"this_pk_name" => "project_literature.literature_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["literature_id"], 
				)), 
				getCombiButton(array(
					"table" => "reaction", 
					"number" => $row["reaction_count"], 
					"this_pk_name" => "reaction_literature.literature_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["literature_id"], 
				)), 
			));
			$edit[]=getEditLink($row);
			$edit[]=getDelLink($row,$idx);
			if ($row["has_literature_blob"]) {
				$edit[]="<a href=\"getLiterature.php?db_id=".fixNull($row["db_id"])."&literature_id=".fixNull($row["literature_id"])."\" class=\"imgButtonSm\" target=\"_blank\"><img src=\"lib/edit_sm.png\" border=\"0\"".getTooltip("get_literature").">";
			}
		}
	break;
	
	// Projekt
	case "links_project":
		if ($paramHash["output_type"]=="html") {
			$retval=alignHorizontal(array(
				getCombiButton(array(
					"table" => "person", 
					"number" => $row["person_count"], 
					"this_pk_name" => "project_person.project_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["project_id"], 
				)), 
				getCombiButton(array(
					"table" => "literature", 
					"number" => $row["project_literature_count"], 
					"this_pk_name" => "project_literature.project_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["project_id"], 
				)), 
				getCombiButton(array(
					"table" => "reaction", 
					"number" => $row["reaction_count"], 
					"this_pk_name" => "reaction.project_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["project_id"], 
				)), 
			));
			$edit[]=getEditLink($row);
			$edit[]=getDelLink($row,$idx);
		}
	break;
	
	// Reaktion
	case "lab_journal_entry":
		$retval=$row["lab_journal_code"]." ".$row["nr_in_lab_journal"];
	break;
	case "rxn_structure_text":
		$int_names=array("reactants","products");
		$main_texts=array();
		foreach ($int_names as $int_name) {
			$texts=array();
			if (is_array($row[$int_name])) foreach ($row[$int_name] as $item) {
				$item_text=$item["standard_name"];
				if (empty($item_text)) {
					$item_text=$item["cas_nr"];
				}
				if (empty($item_text)) {
					if ($int_name=="reactants") {
						$item_text=s("reactant")." ".($item["no_in_reaction"]+1);
					}
					elseif ($int_name=="products") {
						$item_text=s("product")." ".numToLett($item["no_in_reaction"]+1);
					}
				}
				$texts[]=$item_text;
			}
			$main_texts[]=join(" + ",$texts);
		}
		$retval.=joinIfNotEmpty($main_texts," ---> ");
	break;
	case "rxn_structure":
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			$url=getEditURL($row);
			if ($table=="analytical_data") { // go to reaction
				$url="edit.php?".getSelfRef(array("~script~","db_id","pk","table","cached_query"))."&query=<0>&crit0=reaction.lab_journal_id&op0=ex&val0=".$row["lab_journal_id"]."&table=reaction&db_id=".$row["db_id"]."&pk=".$row["reaction_id"];
			}

			$retval=showImageOverlay(array(
				"pkName" => "reaction_id", 
				"db_id" => $row["db_id"], 
				"pk" => $row["reaction_id"], 
				"width" => rxn_gif_x, 
				"height" => rxn_gif_y, 
				"mode" =>"rxn", 
				"posFlags" => OVERLAY_LIMIT_BOTTOM+OVERLAY_LIMIT_RIGHT+OVERLAY_SCROLL_X+OVERLAY_HIDE_SHORT_Y, 
				"linkTable" => "reaction", 
				"linkPk" => $row["reaction_id"], 
				"filename" => $row["lab_journal_code"].$row["nr_in_lab_journal"], 
			));
		}
		else {
			$filename=$idx."_rxn_structure.rxn";
			$retval=$filename;
			$files[$filename]=$row["rxnfile_blob"];
		}
	break;
	case "yield":
	case "gc_yield":
	case "remaining_reactants":
		$col_name=$columns[$table][$col]["col_name"];
		$col_options_key=$columns[$table][$col]["column_options"];
		
		if ($col=="remaining_reactants") {
			$col="gc_yield";
		}
		
		$ret_array=array();
		$raw=true;
		$excel_format=in_array($paramHash["output_type"],array("zip/xls","xls"));
		
		if (empty($view_options[$col_options_key]["fields"])) {
			$view_options[$col_options_key]["fields"]=getDefaultFields($col_options_key);
		}
		
		$diagramParamHash=array(
			"texts" => array(), 
			"show_idx" => array(), 
			"display" => $col, 
			"style" => (in_array("diagram",$view_options[$col_options_key]["fields"])?DIAGRAM_BAR_SINGLE:DIAGRAM_BAR_HIDDEN), 
		);
		
		if (is_array($view_options[$col_options_key]["fields"])) foreach ($view_options[$col_options_key]["fields"] as $field) { // yield.0
			list($field,$idx)=explode(".",$field);
			
			$addEmptyColumn=true;
			switch ($field) {
			case "remaining":
				if ($row[$col_name][$idx][$col]!=="") {
					if ($paramHash["output_type"]=="html") {
						$diagramParamHash["texts"][]=s($field.".".$idx).": ";
						$diagramParamHash["show_idx"][]=$idx;
					}
					else {
						$ret_array[]=ifNotEmpty(s($field.".".$idx).": ",yieldFmt($row[$col_name][$idx][$col]));
						$addEmptyColumn=false;
					}
				}
			break;
			case "yield":
				if (is_array($row[$col_name][$idx])) {
					//					products		#	(gc_)yield
					if ($paramHash["output_type"]=="html") {
						$diagramParamHash["show_idx"][]=$idx;
					}
					else {
						$ret_array[]=yieldFmt($row[$col_name][$idx][$col]);
						$addEmptyColumn=false;
					}
				}
			break;
			case "ratio":
				// Produkte durchgehen
				if (count($row[$col_name])>1) {
					$yields=array();
					unset($min_yield);
					for ($idx=0;$idx<count($row[$col_name]);$idx++) {
						$this_yield=$row[$col_name][$idx][$col]; // yield or gc_yield
						$yields[]=$this_yield;
						if ($this_yield>0 && (!isset($min_yield) || $min_yield>$this_yield)) {
							$min_yield=$this_yield;
						}
					}
					if ($min_yield>0) {
						$ret_array[]=join(":",array_round(array_mult($yields,1/$min_yield),2));
						$addEmptyColumn=false;
					}
				}
			break;
			case "ee-de":
				// assume 1st and 2nd prod are enantio/diastereomers, 3rd... are side prods
				if (count($row[$col_name])>=2) {
					$min_yield=min($row[$col_name][0][$col],$row[$col_name][1][$col]); // yield or gc_yield
					$max_yield=max($row[$col_name][0][$col],$row[$col_name][1][$col]); // yield or gc_yield
					$total_yield=$max_yield+$min_yield;
					if ($total_yield>0) {
						$ret_array[]="(".round(100*($max_yield-$min_yield)/$total_yield,1)."% ".
							s("ee-de_codes",stereoStructureRelationship(
								$row[$col_name][0]["smiles"],
								$row[$col_name][0]["smiles_stereo"],
								$row[$col_name][1]["smiles"],
								$row[$col_name][1]["smiles_stereo"]
							)).
							")";
						$addEmptyColumn=false;
					}
				}
			break;
			case "diagram":
				if ($excel_format) {
					$addEmptyColumn=false;
				}
			break;
			}
			
			if ($addEmptyColumn) {
				$ret_array[]="";
			}
		}
		
		if ($paramHash["output_type"]=="html") {
			$retval=getGraphicalYield($row[$col_name],$diagramParamHash);
			$retval.=nl2br(htmlspecialchars(join("\n",$ret_array)));
		}
		elseif ($excel_format) {
			$retval=$ret_array;
		}
		else {
			$retval=join("; ",$ret_array);
		}
	break;
	case "reactant":
	case "reagent":
	case "product":
		$col_name=$columns[$table][$col]["col_name"];
		$col_options_key=$columns[$table][$col]["column_options"];
		$ret_array=array();
		$raw=true;
		$excel_format=in_array($paramHash["output_type"],array("zip/xls","xls"));
		
		if (empty($view_options[$col_options_key]["fields"])) {
			$view_options[$col_options_key]["fields"]=getDefaultFields($col_options_key);
		}
		if (is_array($view_options[$col_options_key]["fields"])) foreach ($view_options[$col_options_key]["fields"] as $field) {
			if ($excel_format || !empty($row[$col_name][$index][$field])) {
				if ($paramHash["output_type"]=="html") {
					$span="<span id=".fixStr($col_name."_".$index."_".$field."_".$idx).">";
					$_span="</span>";
				}
				
				switch ($field) {
				case "molfile_blob":
					if ($paramHash["output_type"]=="html") {
						$full_size=in_array("molfile_blob_full",$view_options[$col_options_key]["fields"]);
						
						$ret_array[]=showImageOverlay(
							array(
								"id" => $col_name."_".$index."_".$field."_".$idx, 
								"pkName" => "reaction_chemical_id", 
								"db_id" => $row[$col_name][$index]["db_id"], 
								"pk" => $row[$col_name][$index]["reaction_chemical_id"], 
								"width" => ($full_size?"":rc_gif_x), 
								"height" => ($full_size?"":rc_gif_y), 
								"mode" =>"mol", 
								"posFlags" => OVERLAY_LIMIT_BOTTOM+OVERLAY_LIMIT_RIGHT, 
								"linkTable" => $table, 
								"linkPk" => $row[$pk_name], 
								"filename" => $row[$col_name][$index]["standard_name"]
							)
						);
					}
					else {
						$filename=$idx."_".$col."_".$index.".mol";
						$retval=$filename;
						$files[$filename]=$row["molfile_blob"];
					}
				break;
				case "m_brutto":
					$ret_array[]=round($row[$col_name][$index][$field],3)."&nbsp;".$row[$col_name][$index]["mass_unit"];
				break;
				case "stoch_coeff":
					$ret_array[]=$span.round($row[$col_name][$index][$field],3)."&nbsp;eq".$_span;
				break;
				case "rc_amount":
					$ret_array[]=$span.round($row[$col_name][$index][$field],3)."&nbsp;".$row[$col_name][$index]["rc_amount_unit"].$_span;
				break;
				case "volume":
					$ret_array[]=round($row[$col_name][$index][$field],3)."&nbsp;".$row[$col_name][$index]["volume_unit"];
				break;
				default:
					$ret_array[]=$row[$col_name][$index][$field];
				}
			}
		}
		
		$idText=" id=".fixStr($col_name."_".$index."_".$idx);
		$td="<td".$idText.">"; // standard
		
		if ($paramHash["output_type"]=="html") {
			$retval=join("<br>",$ret_array);
		}
		elseif ($excel_format) {
			$retval=$ret_array;
		}
		else {
			$retval=join("; ",$ret_array);
		}
	break;
	case "reaction_project":
		$retval=$row["project_name"];
	break;
	case "reaction_analytics":
		$raw=true;
		$totalSpectra=count($row["analytical_data"]);
		$ret_array=array();
		
		if ($totalSpectra) {
			foreach ($row["analytical_data"] as $analytics_quick) {
				$text=$analytics_quick["analytics_method_name"].ifnotempty(" (",$analytics_quick["analytics_type_name"],")");
				if (!empty($text)) {
					$ret_array[]=$text;
					$totalSpectra--;
				}
			}
			if ($totalSpectra==1) {
				$ret_array[]=s("other_spectra1").$totalSpectra.s("other_spectra2");
			}
			elseif ($totalSpectra) {
				$ret_array[]=s("other_spectra_pl1").$totalSpectra.s("other_spectra_pl2");
			}
		}
		
		if ($paramHash["output_type"]=="html") {
			$retval=nl2br(htmlspecialchars(join("\n",$ret_array)));
		}
		else {
			$retval=join("; ",$ret_array);
		}
	break;
	case "reaction_status":
		$langKeys=getValueList("reaction","status");
		$retval=s($langKeys[ $row["status"]-1 ]);
	break;
	case "compare_rxn":
		//~ $raw=true;
		//~ $retval=getGraphicalYield($row["products"]);
		$retval="";
	break;
	case "links_reaction":
		if ($paramHash["output_type"]=="html") {
			if ($permissions & _chemical_read) {
				$retval=getCombiButton(array(
					"table" => "chemical_storage", 
					"number" => $row["chemical_storage_count"], 
					"this_pk_name" => "chemical_storage.from_reaction_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["reaction_id"], 
				)).
				getCombiButton(array(
					"table" => "literature", 
					"number" => $row["reaction_literature_count"], 
					"this_pk_name" => "reaction_literature.reaction_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["reaction_id"], 
				));
			}
			//~ getImgFilterLink($row["chemical_storage_count"],"chemical_storage","chemical_storage.from_reaction_id",$row["db_id"],$row["reaction_id"]);	
			$edit[]=getEditLink($row);
			$edit[]=getDelLink($row,$idx);
			$special[]="<a href=\"Javascript:setRefRxn(&quot;set&quot;,&quot;list&quot;,".fixNull($row["db_id"]).",".fixNull($row["reaction_id"]).")\" class=\"imgButtonSm\"><img src=\"lib/compare_rxn_sm.png\" border=\"0\"".getTooltip("compare_rxn_ref")."></a>";
			$special[]="<a href=\"copyReaction.php?".getSelfRef(array("~script~","db_id","pk"))."&db_id=".fixNull($row["db_id"])."&pk=".fixNull($row["reaction_id"])."\" class=\"imgButtonSm\" target=\"_blank\"><nobr><img src=\"lib/reaction_sm.png\" border=\"0\"".getTooltip("copyReaction").">x</nobr></a>";
		}
	break;
	// Lager
	//~ case "poison_cabinet":
		//~ $retval=$row[$col];
	//~ break;
	case "chemical_storage_bilancing":
	case "molecule_bilancing":
		$retval=s(ifempty($triStateMap[ $row[$col] ],"default"));
	break;
	case "pos_neg":
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			if ($row["pos_liste"]) {
				$retval.="<img src=\"lib/positiv.png\"".getTooltip("pos_liste").">";
			}
			if ($row["neg_liste"]) {
				$retval.="<img src=\"lib/negativ.png\"".getTooltip("neg_liste").">";
			}
		}
		else {
			$retval=($row["pos_liste"]?s("pos_liste"):"")." ".($row["neg_liste"]?s("neg_liste"):"");
		}
	break;	

	// Khoi: Storage --------------------------------------------------------------------------
	case "institution":
		$retval=joinIfNotEmpty(array($row["institution_name"],$row["city"]),", ");
	break;
	case "storage_barcode":
		if (!empty($row[$col])) {
			$retval=$row[$col];
		}
		else {
			$retval=getEAN8(findBarcodePrefixForPk("storage"),$row["storage_id"]);
		}
	break;
	case "links_storage":
		if ($paramHash["output_type"]=="html") {
			$retval=getCombiButton(array(
				"table" => "chemical_storage", 
				"number" => $row["chemical_storage_count"], 
				"this_pk_name" => "chemical_storage.storage_id", 
				"db_id" => $row["db_id"], 
				"pk" => $row["storage_id"], 
			));
			$edit[]=getEditLink($row);
			$edit[]=getDelLink($row,$idx);
		}
	break;
	//--------------------------------------------------------------------------------------

	// Institution
	case "street":
		$retval=$row["street"]." ".$row["street_number"];
	break;
	case "city":
		$retval=$row["postcode"]." ".$row["city"];
	break;
	//~ case "links_vendor": // perhaps we want to change this in the future
	case "links_institution":
		if ($paramHash["output_type"]=="html") {
			$edit[]=getEditLink($row);
			$edit[]=getDelLink($row,$idx);
			if ($row["institution_type"] & _vendor) {
				$retval.=getCombiButton(array(
					"table" => "accepted_order", 
					"number" => $row["open_accepted_order_count"], 
					"this_pk_name" => "accepted_order.vendor_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["institution_id"], 
				)).
				getCombiButton(array(
					"table" => "order_comp", 
					"number" => $row["order_comp_count"], 
					"this_pk_name" => "order_comp.institution_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["institution_id"], 
					"parameter" => "institution_id=".$row["institution_id"], 
				));
			}
		}
	break;
	// Person
	case "person_institution":
		$retval=$row["institution_name"];
	break;
	case "permissions":
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			$retval=nl2br(htmlspecialchars(@join("\n",a("permissions_list",$row["permissions"]))));
		}
		else {
			$retval=@join("; ",a("permissions_list",$row["permissions"]));
		}
	break;
	// Khoi: add person barcode as a view column
	case "person_barcode":
		if (!empty($row[$col])) {
			$retval=$row[$col];
		}
		else {
			$retval=getEAN8(findBarcodePrefixForPk("person"),$row["person_id"]);
		}
	break;
	case "links_person":
		if ($paramHash["output_type"]=="html") {
			if ($permissions & (_lj_read+_lj_read_all)) {
				$retval.=getCombiButton(array(
					"table" => "project", 
					"number" => $row["project_count"], 
					"this_pk_name" => "project_person.person_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["person_id"], 
				));
			}
			if ($permissions & _chemical_read) {
				$retval.=getCombiButton(array(
					"table" => "chemical_storage", 
					"number" => $row["owner_person_count"], 
					"this_pk_name" => "chemical_storage.owner_person_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["person_id"], 
				)).
				getCombiButton(array(
					"image" => "lib/borrow_sm.png",
					"table" => "chemical_storage", 
					"number" => $row["borrowed_count"], 
					"this_pk_name" => "chemical_storage.borrowed_by_person_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["person_id"], 
				));
			}
			if ($permissions & (_lj_read+_lj_read_all)) {
				$retval.=getCombiButton(array(
					"table" => "lab_journal", 
					"number" => $row["lab_journal_count"], 
					"this_pk_name" => "lab_journal.person_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["person_id"], 
					// >>> FR 091025
					"parameter" => "db_id=".$row["db_id"]."&person_id=".$row["person_id"], 
					// <<< FR 091025
				)).
				getCombiButton(array(
					"table" => "reaction", 
					"number" => 1, 
					"hide_number" => true, 
					"this_pk_name" => "lab_journal.person_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["person_id"], 
				));
				if ($row["db_id"]==-1) {
					$retval.="<a class=\"imgButtonSm\" href=\"getPersonCMRReport.php?person_id=".$row["person_id"]."\" target=\"_blank\"><img border=\"0\"".getTooltip("cmrReport")." src=\"lib/GHS-pictogram-silhouete.png\"></a>";
				}
			}
			if ($permissions & _chemical_read) {
				$retval.=getCombiButton(array(
					"table" => "chemical_order", 
					"number" => $row["chemical_order_count"], 
					"this_pk_name" => "chemical_order.ordered_by_person", 
					"db_id" => $row["db_id"], 
					"pk" => $row["person_id"], 
				));
			}
			$edit[]=getEditLink($row);
			$edit[]=getDelLink($row,$idx);
		}
	break;
	// Molekül
	case "molecule_name":
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			$retval=fixBr(strcut($row["molecule_names"],180),20,"<wbr>",true).
				ifNotEmpty(" (",joinIfNotEmpty(array(getSolutionFmt($row["chemical_storage_conc"],$row["chemical_storage_conc_unit"],$row["chemical_storage_solvent"]),$row["description"]),"; "),")"); // 3 mol/l in toluene; on activated charcoal
		}
		else {
			$retval=@join("; ",$row["molecule_names_array"]).
				ifNotEmpty(" (",joinIfNotEmpty(array(getSolutionFmt($row["chemical_storage_conc"],$row["chemical_storage_conc_unit"],$row["chemical_storage_solvent"]),$row["description"]),"; "),")"); // 3 mol/l in toluene; on activated charcoal
		}
	break;
	case "chemical_storage_conc":
		$retval=getSolutionFmt($row["chemical_storage_conc"],$row["chemical_storage_conc_unit"],$row["chemical_storage_solvent"]);
	break;
	case "structure":
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			$retval=showImageOverlay(array(
				"pkName" => "molecule_id", 
				"db_id" => $row["db_id"], 
				"pk" => $row["molecule_id"], 
				"width" => gif_x, 
				"height" => gif_y, 
				"posFlags" => OVERLAY_LIMIT_BOTTOM+OVERLAY_LIMIT_RIGHT, 
				"mode" => "mol", 
				"linkTable" => $table, 
				"linkPk" => $row[$pk_name], 
				"filename" => $row["molecule_name"]
			));
		}
		else {
			$filename=$idx."_structure.mol";
			$retval=$filename;
			$files[$filename]=$row["molfile_blob"];
		}
	break;
	case "molecule_type":
		$ret_array=array();
		if (is_array($row[$col])) foreach ($row[$col] as $row_item) {
			$ret_array[]=$row_item["molecule_type_name"];
		}
		
		if ($paramHash["output_type"]=="html") {
			$retval=join(", ",$ret_array);
		}
		else {
			$retval=join("; ",$ret_array);
		}
	break;
	case "chemical_storage_type":
		$ret_array=array();
		if (is_array($row[$col])) foreach ($row[$col] as $row_item) {
			$ret_array[]=$row_item["chemical_storage_type_name"];
		}
		
		if ($paramHash["output_type"]=="html") {
			$retval=join(", ",$ret_array);
		}
		else {
			$retval=join("; ",$ret_array);
		}
	break;
	case "cas_nr":
		$raw=true;
		$retval="";
		if ($paramHash["output_type"]=="html") {
			$retval.="<nobr>";
		}
		$retval.=htmlspecialchars($row[$col]);
		if ($paramHash["output_type"]=="html") {
			$retval.="</nobr>";
		}
	break;
	case "emp_formula_short":
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			$retval="<div onMouseover=\"showCHNTooltip(this,&quot;".$row["emp_formula"]."&quot;)\" onMouseout=\"hideOverlay()\">".fixNbsp(getBeautySum($row["emp_formula"]))."</div>";
		}
		else {
			$retval=$row["emp_formula"];
		}
	break;
	case "mw":
		$td="<td".$idText." class=\"numeric\">";
		$retval=round($row[$col],2);
	break;
	case "safety_r_s":
		$raw=true;
		if ($paramHash["output_type"]=="html") {
			$retval="";
			if ($g_settings["use_rs"]) {
				$retval.=getSafetyOverlay($row,"r").getSafetyOverlay($row,"s");
			}
			if ($g_settings["use_ghs"]) {
				$retval.=getSafetyOverlay($row,"h").getSafetyOverlay($row,"p");
			}
		}
		else {
			$ret_array=array();
			if ($g_settings["use_rs"]) {
				$ret_array[]=ifNotEmpty("R: ",$row["safety_r"]);
				$ret_array[]=ifNotEmpty("S: ",$row["safety_s"]);
			}
			if ($g_settings["use_ghs"]) {
				$ret_array[]=ifNotEmpty("H: ",$row["safety_h"]);
				$ret_array[]=ifNotEmpty("P: ",$row["safety_p"]);
			}
			$retval=joinIfNotEmpty($ret_array,"; ");
		}
	break;
	case "safety_data_sheet":
		$ret_array=array();
		$raw=true;
		
		if (!empty($row["safety_sheet_by"])) { // chemical_storage
			$pkName="chemical_storage_id";
			$int_name="safety_sheet";
		}
		elseif (!empty($row["default_safety_sheet_by"])) { // molecule or chemical_storage
			$pkName="molecule_id";
			$int_name="default_safety_sheet";
		}
		
		if (!empty($pkName)) {
			if ($paramHash["output_type"]=="html") {
				$keyName=$int_name."_by";
				$ret_array[]=getSDSLink($pkName,$row["db_id"],$row[$pkName],$int_name,$row[$keyName]);
			}
			else {
				$filename=$idx."_sds_".$g_settings["safety_sheet_lang"]."_".cutFilename($row[$int_name."_url"]);
				$ret_array[]=$filename;
				$files[$filename]=$row[$int_name."_blob"];
			}
			unset($pkName);
		}
		
		// alternative
		if (!empty($row["alt_safety_sheet_by"])) { // chemical_storage
			$pkName="chemical_storage_id";
			$int_name="alt_safety_sheet";
		}
		elseif (!empty($row["alt_default_safety_sheet_by"])) { // molecule or chemical_storage
			$pkName="molecule_id";
			$int_name="alt_default_safety_sheet";
		}
		
		if (!empty($pkName)) {
			if ($paramHash["output_type"]=="html") {
				$keyName=$int_name."_by";
				$ret_array[]=getSDSLink($pkName,$row["db_id"],$row[$pkName],$int_name,$row[$keyName]);
			}
			else {
				$filename=$idx."_sds_".$g_settings["alt_safety_sheet_lang"]."_".cutFilename($row[$int_name."_url"]);
				$ret_array[]=$filename;
				$files[$filename]=$row[$int_name."_blob"];
			}
		}
		
		if ($paramHash["output_type"]=="html") {
			$retval=join("",$ret_array);
		}
		else {
			$retval=join("; ",$ret_array);
		}
	break;
	case "safety_class":
		$retval=getSchutzklasseKL($row["safety_sym"],$row["safety_r"]);
	break;
	case "safety_sym":
		$raw=true;
		$retval="";
		if ($paramHash["output_type"]=="html") {
			if ($g_settings["use_rs"]) {
				$retval.=getSafetyGif($row[$col]);
			}
			if ($g_settings["use_ghs"]) {
				$retval.=getSafetyGif($row["safety_sym_ghs"]);
			}
		}
		else {
			if ($g_settings["use_rs"]) {
				$retval=$row[$col];
			}
			if ($g_settings["use_ghs"]) {
				$retval=$row["safety_sym_ghs"];
			}
		}
	break;
	case "safety_sym_text":
		$retval=$row["safety_sym"];
	break;
	case "safety_other":
		$raw=true;
		$ret_array=array(
			ifnotempty(s("safety_cancer_short").": ",$row["safety_cancer"]), 
			ifnotempty(s("safety_mutagen_short").": ",$row["safety_mutagen"]), 
			ifnotempty(s("safety_reprod_short").": ",$row["safety_reprod"]), 
			ifnotempty(s("safety_wgk").": ",$row["safety_wgk"]), 
		);
		
		if ($paramHash["output_type"]=="html") {
			$retval=joinIfNotEmpty($ret_array,"<br>");
		}
		else {
			$retval=joinIfNotEmpty($ret_array,"; ");
		}
	break;
	case "mp_short":
		$td="<td".$idText." class=\"numeric\">";
		if ($paramHash["output_type"]=="html") {
			$retval=formatRange($row["mp_low"],$row["mp_high"]);
		}
		else {
			$retval=formatRange($row["mp_low"],$row["mp_high"],"");
		}
	break;
	case "bp_short":
		$td="<td".$idText." class=\"numeric\">";
		if ($paramHash["output_type"]=="html") {
			$retval=formatBoilingPoint($row["bp_low"],$row["bp_high"],$row["bp_press"],$row["press_unit"]);
		}
		else {
			$retval=formatBoilingPoint($row["bp_low"],$row["bp_high"],$row["bp_press"],$row["press_unit"],"");
		}
	break;
	case "links_double_smiles":
		if ($paramHash["output_type"]=="html") {
			$retval=getCombiButton(array( // Gebindeanzahl / neu
				"table" => "molecule", 
				"number" => $row["double_count"], 
				"this_pk_name" => "smiles_stereo", 
				"db_id" => $row["db_id"], 
				"op" => "bn", 
				"pk" => $row["smiles_stereo"], 
			));
		}
	break;
	case "links_double_cas":
		if ($paramHash["output_type"]=="html") {
			$retval=getCombiButton(array( // Gebindeanzahl / neu
				"table" => "molecule", 
				"number" => $row["double_count"], 
				"this_pk_name" => "cas_nr", 
				"db_id" => $row["db_id"], 
				"op" => "bn", 
				"pk" => $row["cas_nr"], 
			));
		}
	break;
	case "links_mol":
		if ($paramHash["output_type"]=="html") {
			$retval=getCombiButton(array( // Gebindeanzahl / neu
				"table" => "chemical_storage", 
				"number" => $row["chemical_storage_count"], 
				"this_pk_name" => "chemical_storage.molecule_id", 
				"db_id" => $row["db_id"], 
				"pk" => $row["molecule_id"], 
				"parameter" => "db_id=".$row["db_id"]."&molecule_id=".$row["molecule_id"], 
				"add_always" => true, // add foreign to own
			));
			// Knopf zum Bestellen
			
			if ($permissions & _admin) {
				$retval.=getCombiButton(array(
					"table" => "supplier_offer", 
					"number" => $row["supplier_offer_count"], 
					"this_pk_name" => "supplier_offer.molecule_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["molecule_id"], 
					"parameter" => "db_id=".$row["db_id"]."&molecule_id=".$row["molecule_id"], 
				));
			}
			
			// Anzahl Reaktionen
			if (($permissions & _lj_read) && $row["db_id"]==-1) {
				$retval.=getCombiButton(array(
					"table" => "reaction", 
					"number" => $row["reaction_count"], 
					"this_pk_name" => "reaction_chemical.molecule_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["molecule_id"], 
					"filter" => "query=<0> AND <1>&crit1=reaction_chemical.other_db_id&op1=eq&val1=".$row["db_id"], 
				));
			}
			
			// Kaufen
			
			
			$edit[]=getEditLink($row);
			if ($permissions & _chemical_edit) {
				$edit[]=getDelLink($row,$idx);
			}
		}
	break;
	
	// Chemikalie
	case "amount":
		$raw=true;
		$td="<td".$idText." class=\"numeric\">";
		$retval="";
		if ($paramHash["output_type"]=="html") {
			$retval.="<span id=".fixStr("actual_amount_".$idx).">";
		}
		$retval.=ifnotempty("",roundLJ($row["actual_amount"]),"&nbsp;".$row["amount_unit"]." / ");
		if ($paramHash["output_type"]=="html") {
			$retval.="</span>";
		}
		$retval.=roundLJ($row["amount"])."&nbsp;".$row["amount_unit"];
		
	break;
	case "container":
		$retval=$row["container"].ifnotempty(" (",$row["protection_gas"],")");
	break;
	//~ case "purity":
		//~ $td="<td".$idText." class=\"numeric\">";
		//~ $retval=purityFmt($row[$col]);
	//~ break;
	case "chemical_storage_properties":
		$retval=join(", ",m(getValueList("chemical_storage","chemical_storage_attrib"),$row["chemical_storage_attrib"]));
	break;
	case "storage":
		if ($row["db_id"]==-1
			|| $g_settings["order_system"]=="fundp" // fundp wants all to see the exact location
			|| $db_user == ROOT) {
			$retval=joinifnotempty(
				array($row["storage_name"],
				ifnotempty(s("compartment_short")."&nbsp;",$row["compartment"]) // Fach X
				)
			);
		}
		else {
			$retval=$row["show_db_beauty_name"];
		}
	break;
	case "inventarisation":
		if ($paramHash["output_type"]!="html") {
			$retval=$row["inventory_check_by"].toDate($row["inventory_check_when"]);
		}
		elseif ($mayWrite[ $row["db_id"] ]) {
			$raw=true;
			$retval="<span id=\"inventory_".$idx."\"></span><nobr><input type=\"text\" name=\"input_actual_amount_".$idx."\" id=\"input_actual_amount_".$idx."\" size=\"3\" maxlength=\"20\" onKeyup=\"keyUpInventory(event,".$row["chemical_storage_id"].",".$idx.")\">&nbsp;".$row["amount_unit"]."</nobr> <input type=\"button\" value=\"".s("have_checked_it")."\" onClick=\"updateInventory(".$row["chemical_storage_id"].",".$idx.")\">";
		}
		else {
			$retval=$row["show_db_beauty_name"];
		}
	break;
	case "price":
		$td="<td".$idText." class=\"numeric\">";
		$retval=ifnotempty("",$row["price"]," ".$row["price_currency"]);
	break;
	// literature
	case "links_sci_journal":
		$edit[]=getEditLink($row);
		$edit[]=getDelLink($row,$idx);
		if ($paramHash["output_type"]=="html") {
			$retval=getCombiButton(array(
					"table" => "literature", 
					"number" => $row["literature_count"], 
					"this_pk_name" => "literature.sci_journal_id", 
					"db_id" => $row["db_id"], 
					"pk" => $row["sci_journal_id"], 
				));
		}
	break;
	case "links_chem":
		//~ $noSelected=intval($_SESSION["selection"][$row["db_id"]][$row["chemical_storage_id"]]);
		
		if ($paramHash["output_type"]=="html") {
			$retval=getCombiButton(array(
				"table" => "chemical_storage", 
				"number" => $row["chemical_storage_count"], 
				"this_pk_name" => "molecule.molecule_id", 
				"db_id" => $row["db_id"], 
				"pk" => $row["molecule_id"], 
				"parameter" => "db_id=".$row["db_id"]."&chemical_storage_id=".$row["chemical_storage_id"], 
				"add_always" => true, // add foreign to own
			));
			
			$edit[]=getEditLink($row);
			
			// Ausleihfunktion
			//~ $retval.="<br clear=\"all\">";
			if ($row["db_id"]!=-1) {
			 // do nothing
			}
			else {
				// undelete
				if ($row["chemical_storage_disabled"]) {
					$special[]="<a target=\"comm\" class=\"imgButtonSm\" href=\"listAsync.php?".getSelfRef(array("~script~"))."&db_id=-1&pk=".$row["chemical_storage_id"]."&desired_action=undel&idx=".$idx."\" id=\"btn_undelete_".$idx."\"><img src=\"lib/undelete_sm.png\" border=\"0\"".getTooltip("undelete_chemical")."></a>";
				} else {
					$edit[]=getDelLink($row,$idx);
				}
				
				$borrowStatus=borrowStatus($row["borrowed_by_person_id"],$row["borrowed_by_db_id"],$row["owner_person_id"]);
				
				if (($borrowStatus&_anyone_borrowed) && !($borrowStatus&_borrowed)) { // jemand anders
					$special[]=s("borrowed_by1")." ".formatPersonNameCommas($row)." ".s("borrowed_by2");
				}
				elseif ($borrowStatus==_not_borrowable) {
					$special[]=s("in_place")."."; // am platz
				}
				else { // borrowing by external people only through barcode terminal
					$borrowLink1="<a target=\"comm\" class=\"imgButtonSm\" href=\"listAsync.php?".getSelfRef(array("~script~","chemical_storage_id"))."&db_id=-1&pk=".$row["chemical_storage_id"]."&desired_action=borrow&idx=".$idx."&borrowed_by_db_id=-1&borrowed_by_person_id=";
					$special[]=$borrowLink1.$person_id."\" style=\"display:".(($borrowStatus&_borrowed)?"none":"")."\" id=\"btn_borrow_".$idx."\"><img src=\"lib/borrow_sm.png\" border=\"0\"".getTooltip("borrow")."></a>"; // ausleihen
					$special[]=$borrowLink1."\" style=\"display:".(($borrowStatus&_borrowed)?"":"none")."\" id=\"btn_bring_back_".$idx."\"><img src=\"lib/bring_back_sm.png\" border=\"0\"".getTooltip("return")."></a>"; // zurückgeben
				}
			}	
			
			//~ if ($mayCreate[ $row["db_id"] ]) { // split
				//~ $special[]="<a href=\"edit.php?".getSelfRef(array("~script~","cached_query","query","chemical_storage_id"))."&db_id=".$row["db_id"]."&chemical_storage_id=".$row["chemical_storage_id"]."&split_chemical_storage_id=".$row["chemical_storage_id"]."\" class=\"imgButtonSm\"><img src=\"lib/split_chemical_sm.png\" border=\"0\"".getTooltip("split_chemical_storage")."></a>";
			//~ }
		}
	break;
	}
	
	//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
	switch ($paramHash["output_type"]) {
	case "zip/csv":
	case "csv":
		if (!$link_col) {
			if ($fieldIdx>0) {
				$output.=csv_sep;
			}
			$output.=fixCSV(html_entity_decode($retval));
		}
	break;
	case "zip/xls":
	case "xls":
		if (!$link_col) {
			if (!is_array($retval)) {
				$retval=array($retval);
			}
			foreach ($retval as $retval_entry) {
				// write cell(s)
				$output->write($subidx+1,$fieldIdx,html_entity_decode(utf8_decode(trimNbsp($retval_entry)))); // col heads need 1st line
				$fieldIdx++;
			}
			// one added too much
			$fieldIdx--;
		}
	break;
	case "sdf":
		$output.=$sdf_prefix.
			html_entity_decode(utf8_decode($retval)).
			"\r\n\r\n"; // extra line
	break;
	case "html":
		// make links col
		if ($link_col) {
			if ($row["db_id"]!=-1) {
				$special[]=$row["show_db_beauty_name"];
			}
			if (isSelectTable($table)) { // grüner "Auswahl"-Button
				$special[]=getSelectButton($row);
			}
		}
		$retval.=alignHorizontal($edit).alignHorizontal($special);
		if ($link_col) {
			$raw=true;
			$selected=$settings["selection"][$table][ $row["db_id"] ][ $row[$pk_name] ];
			if (!$paramHash["order_alternative"]) {
				$retval.="<input type=\"checkbox\" id=\"sel_".$idx."\" onClick=\"setSelect(".fixNull($idx).")\"".($selected?" checked=\"checked\"":"").">";
			}
		}
		
		if (!$raw) {
			$retval=str_replace("&amp;nbsp;","&nbsp;",htmlspecialchars($retval));
		}
		
		// do output
		$output.=$td;
		if ($fieldIdx==0) {
			$output.="<a name=\"item".$idx."\">";
		}
		$output.=fixNbsp($retval);
		if ($fieldIdx==0) {
			$output.="</a>";
		}
		$output.="</td>";
	break;
	}
}
?>
