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
asynchrone Seite, wird im unsichtbaren Iframe "comm" geladen und erzeugt Javascript-Code, der Suchergebnisse für Moleküle, Gebinde, Lager oder Institutionen ausgibt. Außerdem werden GIFs für Rxnfile und Molfile bei reaction erzeugt und angezeigt, indem JS-Code auf parent zugreift.
*/

// Parameter: table, search, id, page, cached_query

require_once "lib_global_funcs.php";
require_once "lib_constants.php";
require_once "lib_db_query.php";
require_once "lib_db_manip_helper.php";
require_once "lib_molfile.php";
require_once "lib_formatting.php";
require_once "lib_form_elements_helper.php";
require_once "lib_chooseAsync.php";

$page_type="async";
$barcodeTerminal=true;

setGlobalVars();

pageHeader(true,true,false);

//~ print_r($_REQUEST);

echo "</head>
<body>".
script."
if (parent && parent!=self) {\n";

switch ($_REQUEST["desired_action"]) {
case "searchPk";
	
	if ($_REQUEST["search"]!="" || $_REQUEST["cached_query"]!="") {
		$int_name=$_REQUEST["int_name"];
		$_REQUEST["per_page"]=async_per_page; // standardmäßig 10
		if ($table=="molecule") {
			$_REQUEST["per_page"]=molecules_per_async_page; // 3, sonst wird die Liste zu lang
		}
		
		switch ($table) { // form query request
		case "molecule":
		case "chemical_storage":
			$_REQUEST["query"]="<0> OR <1>";
			$_REQUEST["crit0"]="molecule_names.molecule_name";
			$_REQUEST["op0"]="ca";
			$_REQUEST["val0"]=$_REQUEST["search"];
			$_REQUEST["crit1"]="cas_nr";
			$_REQUEST["op1"]="ex";
			$_REQUEST["val1"]=$_REQUEST["search"];
			if (isEmpFormula($_REQUEST["search"])) {
				$_REQUEST["crit2"]="emp_formula";
				$_REQUEST["op2"]="bn";
				$_REQUEST["val2"]=getEmpFormulaHill($_REQUEST["search"]);
				$_REQUEST["query"].=" OR <2>";
			}
		break;
		case "storage":
			$_REQUEST["query"]="<0>";
			$_REQUEST["crit0"]="storage_name";
			$_REQUEST["op0"]="ct";
			$_REQUEST["val0"]=$_REQUEST["search"];
		break;
		case "institution":
			$_REQUEST["query"]="<0>";
			$_REQUEST["crit0"]="institution_name";
			$_REQUEST["op0"]="ct";
			$_REQUEST["val0"]=$_REQUEST["search"];
		break;
		case "person":
			$_REQUEST["query"]="<0> OR <1> OR <2> OR <3>";
			$_REQUEST["crit0"]="last_name";
			$_REQUEST["op0"]="ct";
			$_REQUEST["val0"]=$_REQUEST["search"];
			$_REQUEST["crit1"]="first_name";
			$_REQUEST["op1"]="ct";
			$_REQUEST["val1"]=$_REQUEST["search"];
			$_REQUEST["crit2"]="username";
			$_REQUEST["op2"]="ct";
			$_REQUEST["val2"]=$_REQUEST["search"];
			$_REQUEST["crit3"]="nee";
			$_REQUEST["op3"]="ct";
			$_REQUEST["val3"]=$_REQUEST["search"];
		break;
		case "project":
			$_REQUEST["query"]="<0>";
			$_REQUEST["crit0"]="project_name";
			$_REQUEST["op0"]="ct";
			$_REQUEST["val0"]=$_REQUEST["search"];
		break;
		case "analytics_type":
			$_REQUEST["query"]="<0> OR <1>";
			$_REQUEST["crit0"]="analytics_type.analytics_type_name";
			$_REQUEST["op0"]="ct";
			$_REQUEST["val0"]=$_REQUEST["search"];
			$_REQUEST["crit1"]="analytics_device.analytics_device_name";
			$_REQUEST["op1"]="ct";
			$_REQUEST["val1"]=$_REQUEST["search"];
		break;
		case "analytics_method":
			$_REQUEST["query"]="<0> OR <1> OR <2>";
			$_REQUEST["crit0"]="analytics_type.analytics_type_name";
			$_REQUEST["op0"]="ct";
			$_REQUEST["val0"]=$_REQUEST["search"];
			$_REQUEST["crit1"]="analytics_device.analytics_device_name";
			$_REQUEST["op1"]="ct";
			$_REQUEST["val1"]=$_REQUEST["search"];
			$_REQUEST["crit2"]="analytics_method.analytics_method_name";
			$_REQUEST["op2"]="ct";
			$_REQUEST["val2"]=$_REQUEST["search"];
		break;
		case "reaction":
			$_REQUEST["query"]="<0> OR <1> OR <2>";
			$_REQUEST["crit0"]="lab_journal.lab_journal_code";
			$_REQUEST["op0"]="ca";
			$_REQUEST["val0"]=$_REQUEST["search"];
			$_REQUEST["crit1"]="nr_in_lab_journal";
			$_REQUEST["op1"]="eq";
			$_REQUEST["val1"]=$_REQUEST["search"];
			$_REQUEST["crit2"]="reaction_carried_out_by";
			$_REQUEST["op2"]="ct";
			$_REQUEST["val2"]=$_REQUEST["search"];
		break;
		case "sci_journal":
			$_REQUEST["dbs"]="-1"; // gives n times the same journals otherwise
			$_REQUEST["query"]="<0> OR <1>";
			$_REQUEST["crit0"]="sci_journal_name";
			$_REQUEST["op0"]="ca";
			$_REQUEST["val0"]=$_REQUEST["search"];
			$_REQUEST["crit1"]="sci_journal_abbrev";
			$_REQUEST["op1"]="ca";
			$_REQUEST["val1"]=$_REQUEST["search"];
		break;
		case "literature":
			$_REQUEST["dbs"]="-1";
			$_REQUEST["query"]="<0> OR <1> OR <2> OR <3> OR <4> OR <5> OR <6> OR <9> OR <10>";
			$_REQUEST["crit0"]="sci_journal.sci_journal_name";
			$_REQUEST["op0"]="ca";
			$_REQUEST["val0"]=$_REQUEST["search"];
			$_REQUEST["crit1"]="sci_journal.sci_journal_abbrev";
			$_REQUEST["op1"]="ca";
			$_REQUEST["val1"]=$_REQUEST["search"];
			$_REQUEST["crit2"]="literature.literature_year";
			$_REQUEST["op2"]="eq";
			$_REQUEST["val2"]=$_REQUEST["search"];
			$_REQUEST["crit3"]="literature.literature_volume";
			$_REQUEST["op3"]="eq";
			$_REQUEST["val3"]=$_REQUEST["search"];
			$_REQUEST["crit4"]="literature.issue";
			$_REQUEST["op4"]="eq";
			$_REQUEST["val4"]=$_REQUEST["search"];
			$_REQUEST["crit5"]="literature.page_high";
			$_REQUEST["op5"]="bt";
			$_REQUEST["val5"]=$_REQUEST["search"];
			
			$_REQUEST["crit6"]="author.literature_id";
			$_REQUEST["op6"]="sq";
			$_REQUEST["val6"]="<7> OR <8>";
			$_REQUEST["crit7"]="author.author_last";
			$_REQUEST["op7"]="co";
			$_REQUEST["val7"]=$_REQUEST["search"];
			$_REQUEST["crit8"]="author.author_first";
			$_REQUEST["op8"]="co";
			$_REQUEST["val8"]=$_REQUEST["search"];
			
			$_REQUEST["crit9"]="literature.literature_title";
			$_REQUEST["op9"]="ca";
			$_REQUEST["val9"]=$_REQUEST["search"];
			$_REQUEST["crit10"]="literature.keywords";
			$_REQUEST["op10"]="ca";
			$_REQUEST["val10"]=$_REQUEST["search"];
			
		break;
		}
		
		if (is_numeric($_REQUEST["pk_exclude"])) {
			$_REQUEST["query"]="(".$_REQUEST["query"].") AND NOT <100>";
			//~ $_REQUEST["crit100"]=$query[$table]["primary"];
			$_REQUEST["crit100"]=getLongPrimary($table);
			$_REQUEST["op100"]="eq";
			$_REQUEST["val100"]=$_REQUEST["pk_exclude"];
			$_REQUEST["dbs"]="-1"; // merge only with stuff from own db
		}
		//~ list($results,$totalCount,$page,$skip,$per_page,$cache_active)=handleQueryRequest(1);
		list($results,$dataArray,$sort_hints)=handleQueryRequest(1);
		$totalCount=& $dataArray["totalCount"];
		$page=& $dataArray["page"];
		$skip=& $dataArray["skip"];
		$per_page=& $dataArray["per_page"];
		$cache_active=& $dataArray["cache_active"];
		echo "parent.showPkResults(".fixStr($int_name).",".$page.",".$per_page.",".$totalCount.",".fixStr($_REQUEST["cached_query"]).",".fixStr(json_encode($results)).");";
	}
break;

case "transferRxnPDF":
	require_once "File/Archive.php";
	@File_Archive::setOption('tmpDirectory',oe_get_temp_dir());
	require_once "lib_rxn_pdf.php";
	require_once "lib_http.php";

	$results=mysql_select_array(array(
		"table" => $_REQUEST["table"], 
		"dbs" => $_REQUEST["db"], 
		"filter" => getLongPrimary($_REQUEST["table"])."=".fixNull($_REQUEST["pk"]), 
		"limit" => 1, 
		"flags" => QUERY_EDIT, 
	));
	$pdf=new PDF_MemImage("P","mm","A4");
	addReactionToPDF($pdf,$results[0]);
	
	// create TAR archive
	$tar=File_Archive::toArchive(null,File_Archive::toVariable($tar_content),"tar");
	
	$lj_entry=$results[0]["lab_journal_code"].$results[0]["nr_in_lab_journal"];
	$pdf_filename=$lj_entry.".pdf";
	$pdf_content=$pdf->Output($pdf_filename,"S");
	$tar->newFile($pdf_filename);
	$tar->writeData($pdf_content);
	$pdf_hash=hash($belab_options["hashAlgorithm"],$pdf_content);
	
	// create mets
	$date_format_sec="D M d H:i:s T Y";
	$created_when=strtotime($results[0]["reaction_created_when"]);
	$mets='<?xml version="1.0" encoding="utf-8" standalone="no"?>
<mets OBJID="'.$results[0]["reaction_uid"].'" xmlns="http://www.loc.gov/METS/" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.loc.gov/METS/ http://www.loc.gov/standards/mets/mets.xsd">
 <metsHdr CREATEDATE="'.date("c",$created_when).'">'.
 //~ 2013-06-03T11:11:32
'  <agent ROLE="ARCHIVIST" TYPE="INDIVIDUAL">
   <name>'.$db_user.'</name>
  </agent>
  <agent ROLE="ARCHIVIST" TYPE="ORGANIZATION">
   <name>'.$g_settings["organisation_name"].'</name>
  </agent>
  <agent ROLE="CREATOR" TYPE="INDIVIDUAL">
   <name>'.$results[0]["reaction_created_by"].'</name>
  </agent>
 </metsHdr>
 <dmdSec ID="belabinformation">
  <mdWrap MIMETYPE="text/xml" MDTYPE="OTHER" OTHERMDTYPE="BELAB">
   <xmlData><elabid>'.$results[0]["lab_journal_id"].'</elabid>';
	if ($results[0]["project_id"]) {
		$mets.='<projectid>'.$results[0]["project_id"].'</projectid>';
	}
	$mets.='<containerid>'.$results[0]["reaction_uid"].'</containerid>
<duration>'.date($date_format_sec,strtotime("+".$belab_options["durationDays"]." days")).'</duration>'.
//~ Wed Jul 03 11:11:57 CEST 2013
'</xmlData>
  </mdWrap>
 </dmdSec>
 <dmdSec ID="dmd_base_0">
  <mdWrap MIMETYPE="text/xml" MDTYPE="DC">
   <xmlData><title>'.$lj_entry.'</title>
<creator>'.$results[0]["reaction_created_by"].'</creator>
<date>'.date($date_format_sec,$created_when).'</date>'.
//~ Mon Jun 03 11:11:32 CEST 2013
'</xmlData>
  </mdWrap>
 </dmdSec>
 <fileSec>
  <fileGrp ID="base">
   <file ID="base_0" CHECKSUM="'.strtoupper($pdf_hash).'" CHECKSUMTYPE="SHA-256'.
 //~ $belab_options["hashAlgorithm"].
'">
    <FLocat LOCTYPE="URL" xlink:type="simple" xlink:href="'.$lj_entry.'.pdf" xlink:title="simple"/>
   </file>
  </fileGrp>
 </fileSec>
</mets>';
	$tar->newFile("mets.xml");
	$tar->writeData($mets);
	//~ die($mets);
	
	// send to belab
	$tar->close();
	$url=$belab_options["serverURL"]."/".$results[0]["lab_journal_id"];
	// was it archived previously?
	if ($results[0]["reaction_ext_archive_id"]) {
		$url.="/".$results[0]["reaction_ext_archive_id"];
	}
	
	$request=new HTTP_Request2($url,"PUT");
	oe_http_map_option($request,$default_http_options,"proxyhost","proxy");
	oe_http_map_option($request,$default_http_options,"connect_timeout","connect_timeout");
	oe_http_map_option($request,$default_http_options,"timeout","timeout");
	$request->setHeader("user-agent",$default_http_options["useragent"]);
	$request->setAuth($belab_options["username"],$belab_options["password"]);
	$request->setBody($tar_content);
	$response=$request->send();
	//~ file_put_contents("/tmp/uof.tar",$tar_content);
	
	// save belab ID to DB
	if ($response->getStatus()==200) {
		if (!$results[0]["reaction_ext_archive_id"]) {
			$sql_query[]="UPDATE reaction SET reaction_ext_archive_id=".fixStr($response->getBody())." WHERE reaction.reaction_id=".fixNull($_REQUEST["pk"]).";";
			$result=performQueries($sql_query,$db);
		}
		// show success message
		echo "parent.showMessage(".fixStr(s("pdf_archive_success")).");\n";
	}
	else {
		// show error message
		echo "parent.showMessage(".fixStr(s("pdf_archive_failure").": ".$response->getReasonPhrase()).");\n";
	}
break;

case "findSimilarRetentionTimes":
	$retention_time_data=mysql_select_array(array(
		"table" => "retention_time_structure", 
		"dbs" => "-1", 
		"filter" => "analytics_type_id=".fixNull($_REQUEST["analytics_type_id"])." AND 
analytics_device_id=".fixNull($_REQUEST["analytics_device_id"])." AND 
analytics_method_id=".fixNull($_REQUEST["analytics_method_id"]), 
		
		"order_obj" => array(
			array("field" => "ABS(retention_time.retention_time-".fixNull($_REQUEST["retention_time"]).")", "order" => "ASC"), 
		),
		"limit" => 5,
	));
	
	if (count($retention_time_data)) {
		echo "parent.showSimilarOverlay(".fixStr($_REQUEST["id"]).",".json_encode($retention_time_data).");\n";
	}
break;

case "loadReactionRef": // load data for reaction that has been set as reference, for comparison
	echo getRefReaction("parent.")."
if (top.sidenav) {
var iHTML=".fixStr("<a href=\"Javascript:clearRefRxn()\"><img src=\"lib/del.png\" width=\"16\" height=\"17\" border=\"0\" style=\"float:left\"></a>".
showHidden(array("int_name" => "ref_reaction_db_id", "value" => $_REQUEST["ref_reaction_db_id"], )).
showHidden(array("int_name" => "ref_reaction_id", "value" => $_REQUEST["ref_reaction_id"], )).
s("compare_to1")." <b>")."+parent.getReactionName(parent.ref_reaction)+".fixStr("</b>".s("compare_to2")).";

top.sidenav.setiHTML(\"ref_reaction\",iHTML);

if (top.searchBottom && top.searchBottom.showControl) {
	top.searchBottom.setiHTML(\"ref_reaction\",iHTML);
	top.searchBottom.showControl(\"ref_reaction\",true);
}
}";
	
	switch ($_REQUEST["type"]) {
	case "list":
		echo "if (!parent.compare_obj.length) { // data for comparison not loaded yet
			parent.activateView();
		}";
	break;
	}

	echo "if (parent.applyComparisonList) {
parent.applyComparisonList(".fixStr($_REQUEST["type"]).");
}
";
break;

case "generateNewBESSI":
	$max_bessi=mysql_select_array(array(
		"table" => "max_bessi",
	));
	$max_bessi_no=0;
	for ($a=0;$a<count($max_bessi);$a++) {
		if ($max_bessi_no<$max_bessi[$a]["max_bessi"]) {
			$max_bessi_no=$max_bessi[$a]["max_bessi"];
		}
	}
	echo "parent.setInputValue(\"migrate_id_mol\",".fixStr($max_bessi_no+1).");\n";
break;

case "print_selection":
	echo "parent.printSelection(".fixStr($_REQUEST["sizeJoin"]).",".fixStr($_REQUEST["additionalParameters"]).",".json_encode(getSelectionHier($_REQUEST["table"])).");\n";
break;

case "update_custom_view":
	$table=$_REQUEST["table"];
	if (isset($table)) {
		$custom_view=array(
			"visibleControls" => array(),
			"visibleIds" => array(),
			"hiddenControls" => array(),
			"hiddenIds" => array(),
		);
		for ($a=0;$a<count($view_controls[$table]);$a++) {
			if ($_REQUEST[ $view_controls[$table][$a] ]) {
				//~ array_push($custom_view["visibleControls"],$a);
				array_push($custom_view["visibleControls"],$view_controls[$table][$a]);
			}
			else {
				//~ array_push($custom_view["hiddenControls"],$a);
				array_push($custom_view["hiddenControls"],$view_controls[$table][$a]);
			}
		}
		for ($a=0;$a<count($view_ids[$table]);$a++) {
			if ($_REQUEST[ $view_ids[$table][$a] ]) {
				//~ array_push($custom_view["visibleIds"],$a);
				array_push($custom_view["visibleIds"],$view_ids[$table][$a]);
			}
			else {
				//~ array_push($custom_view["hiddenIds"],$a);
				array_push($custom_view["hiddenIds"],$view_ids[$table][$a]);
			}
		}
		$settings["custom_view"][$table]=$custom_view;
		saveUserSettings();
		echo "parent.edit_views[\"custom_view\"]=(".json_encode($custom_view).");
parent.showCustomMenu(false);
parent.activateEditView();\n";
	}
break;

case "addStandard":
	$molecule_data["molecule"]=mysql_select_array(array(
		"table" => "molecule_for_reaction", 
		"dbs" => "-1", 
		"filter" => "molecule.smiles_stereo=".fixStrSQLSearch(ifempty($settings["std_smiles"],stdSMILES)), 
		"flags" => $settings["do_not_use_inventory"] ? QUERY_SIMPLE:QUERY_EDIT, 
	));
	
	// generate package names
	addPackageNames($molecule_data);
	echo "var list_int_name=\"reagents\";
var UID=parent.SILaddLine(list_int_name);
parent.setControlDataMolecule(list_int_name,UID,\"\",undefined,".json_encode($molecule_data).");
parent.SILsetValues(list_int_name,UID,undefined,".json_encode(array("m_brutto" => ifempty($settings["m_standard"],stdmg), "mass_unit" => "mg","measured" => 1)).");
parent.updateMolSelect(list_int_name,UID,\"\",undefined);
parent.valChanged();\n";
	
break;

case "getRetentionTimes":
	if (!empty($_REQUEST["analytics_method_id"]) && (!empty($_REQUEST["molecule_id"]) || !empty($_REQUEST["smiles_stereo"]) || !empty($_REQUEST["smiles"]) ) ) {
		$smiles_stereo=explode(",",$_REQUEST["smiles_stereo"]);
		$smiles=explode(",",$_REQUEST["smiles"]);
		if (count($smiles_stereo)==count($smiles)) {
			for ($a=0;$a<count($smiles_stereo);$a++) {
				list($retention_time_data)=mysql_select_array(array(
					"table" => "retention_time", 
					"dbs" => "-1", 
					"filter" => "(smiles_stereo LIKE BINARY ".fixStrSQLSearch($smiles_stereo[$a])." OR smiles LIKE BINARY ".fixStrSQLSearch($smiles[$a]).") AND 
analytics_type_id=".fixNull($_REQUEST["analytics_type_id"])." AND 
analytics_device_id=".fixNull($_REQUEST["analytics_device_id"])." AND 
analytics_method_id=".fixNull($_REQUEST["analytics_method_id"]), 
					
					"order_obj" => array(
						array("field" => "smiles_stereo LIKE BINARY ".fixStrSQLSearch($smiles_stereo[$a]), "order" => "DESC"), 
						array("field" => "smiles LIKE BINARY ".fixStrSQLSearch($smiles[$a]), "order" => "DESC"), 
					),
					"limit" => 1,
				));
				if (!empty($retention_time_data)) {
					echo "parent.procRetentionTime(".fixStr($_REQUEST["UID"]).",".fixStr($retention_time_data["smiles_stereo"]).",".fixStr($retention_time_data["smiles"]).",".fixNull($retention_time_data["retention_time"]).",".fixNull($retention_time_data["response_factor"]).");\n";
				}
			}
		}
		// suchen, was möglichst gut zu molecule_id, smiles_stereo und smiles passt
		//~ $retention_time_data=mysql_select_array(array(
			//~ "table" => "retention_time", 
			//~ "dbs" => "-1", 
			//~ "filter" => "molecule_id IN(".secSQL($_REQUEST["molecule_id"]).") AND analytics_method_id=".fixNull($_REQUEST["analytics_method_id"])
		//~ ));
		//~ echo "parent.procRetentionTimes(".fixStr($_REQUEST["UID"]).",".json_encode($retention_time_data).");\n";
	}
break;

case "checkMessage":
	$message_results=mysql_select_array(array(
		"table" => "message_new", 
		"dbs" => "-1", 
	));
	echo "parent.updateMessageCount(".fixNull(count($message_results)).");\n";
break;

case "parse_doi_txt":
	if (count($_FILES["load_txt"]) && $_FILES["load_txt"]["error"]==0) { // upload
		// datei öffnen
		$text=@file_get_contents($_FILES["load_txt"]["tmp_name"]);
		
		// DOIs suchen
		require_once "lib_literature.php";
		$dois=getDOIsFromText($text);
		
		// in parent-form anhängen
		if (count($dois)) {
			echo "if (parent!=self) {
	var dois=parent.\$(\"dois\");
	if (dois) {
		dois.value+=\"\\n".join("\\n",$dois)."\";
	}
}";
		}
		
		// datei löschen
		@unlink($_FILES["spzfile_file"]["tmp_name"]);
	}
break;

case "add_lit_by_doi":
	// split
	$dois=explode("\n",$_REQUEST["dois"]);
	$_REQUEST["table"]="literature";
	$_REQUEST["db_id"]=-1;
	
	$doi_not_found=array();
	foreach ($dois as $doi) {
		$doi=trim($doi); // keep < xyz >
		//~ $doi=trim(urlencode($doi)); // keep < xyz >
		
		if (empty($doi)) {
			continue;
		}
		
		// check if doi is already in DB
		$literature=getDOIResult($doi);
		
		if (!empty($literature["literature_id"])) {
			// yes: link
			$_REQUEST["pk"]=$literature["literature_id"];
		}
		else {
			// no: read and add if found
			require_once "lib_literature.php";
			
			set_time_limit(ini_get("max_execution_time"));
			$literature=getDataForDOI($doi);
			
			if (!count($literature)) {
				$doi_not_found[]=$doi;
				continue;
			}
			
			// check again if $doi was an URL instead of a real DOI
			$literature_check=getDOIResult($literature["doi"]);
			if (!empty($literature["literature_id"])) {
				$_REQUEST["pk"]=$literature_check["literature_id"];
			}
			else {			
				// add, design this a bit more beautiful in the future
				$oldReq=$_REQUEST;
				$_REQUEST=array_merge($_REQUEST,$literature);
				performEdit("literature",-1,$db);
				$oldReq["pk"]=$_REQUEST["literature_id"];
				$_REQUEST=$oldReq;
			}
		}
		handleLoadDataForPk();
	}
	
	// tell about not found/recognized
	if (count($doi_not_found)) {
		echo "var infoWin=window.open(\"\",Number(new Date()),\"height=450,width=300,scrollbars=yes\");
infoWin.document.open();
infoWin.document.write(".fixStr("<html><head>".stylesheet."</head><body>").");
infoWin.document.write(".fixStr("<form>".s("doi_not_found").":<br>".join("<br>",$doi_not_found)."<br><input type=\"submit\" value=".fixStr(s("ok"))." onClick=\"self.close();\"></form>").");
infoWin.document.write(\"</body></html>\");
infoWin.document.close();
";
	}
	echo "parent.showMessage(".fixStr(s("doi_complete")).");\n";
	
break;

case "loadDataForSearch":
	$filter_obj=getFilterObject(array(
		"dbs" => $_REQUEST["dbs"], 
	));
	//~ $js_code="";
	
	switch ($_REQUEST["list_int_name"]) {
	case "item_list": // very standard situation
	case "order_alternative": // very standard situation
		$query_table="supplier_offer_for_accepted_order";
		//~ $js_code="updateTotal(".fixStr($_REQUEST["list_int_name"]).",".fixStr($_REQUEST["UID"]).",".fixStr($_REQUEST["int_name"]).");";
	break;
	default:
		$query_table=$_REQUEST["table"];
	}
	
	list($result)=mysql_select_array(array(
		"table" => $query_table, 
		"dbs" => $_REQUEST["db_id"], 
		"filter" => $filter_obj["query_string"], 
		"limit" => 1, 
	));
	
	if (count($result)) {
		echo "parent.SILsetValues(".fixStr($_REQUEST["list_int_name"]).",".fixStr($_REQUEST["UID"]).",undefined,".json_encode($result).");\n";
	}
break;

case "loadDataForPk":
	// load data for primary key
	handleLoadDataForPk();
break;

case "loadDataForPkSelect":
	// update available options for pk_select, params: int_name,filter
	/* $paramHash=array("filter" => $_REQUEST["filter"], "table" => $_REQUEST["table"], "pkName" => fixTags($_REQUEST["pkName"]), "nameField" => fixTags($_REQUEST["nameField"]), "allowNone" => fixTags($_REQUEST["allowNone"]), "noneText" => fixTags($_REQUEST["noneText"]) );
	$controlData=pk_select_getList($paramHash);*/
	$filter_obj=getFilterObject();
	//~ echo "/*".print_r($filter_obj,true)."*/";
	$controlData=mysql_select_array(array(
		"table" => $_REQUEST["table"], 
		"filterDisabled" => $_REQUEST["filterDisabled"], 
		"dbs" => $_REQUEST["dbs"], 
		"order_obj" => getOrderObjFromKey($_REQUEST["order_by"],$baseTable), 
		"filter" => $filter_obj["query_string"], 
		"flags" => intval($_REQUEST["flags"]) | QUERY_PK_SEARCH, 
	));
	// print_r($paramHash);
	/*	parent.as(\"controls\",".fixStr(json_encode($paramHash["int_names"])).",".fixStr($_REQUEST["int_name"]).",\"int_names\");
	parent.as(\"controls\",".fixStr(json_encode($paramHash["texts"])).",".fixStr($_REQUEST["int_name"]).",\"texts\"); */
	
	if (isset($_REQUEST["UID"])) { // pk_select in SIL, nicht fertigprogrammiert
		echo "
parent.SILPkSelectSetControlData(".fixStr($_REQUEST["list_int_name"]).",".fixStr($_REQUEST["UID"]).",".fixStr($_REQUEST["int_name"]).",".fixStr(json_encode($controlData)).");
parent.SILPkSelectUpdated(".fixStr($_REQUEST["list_int_name"]).",".fixStr($_REQUEST["UID"]).",".fixStr($_REQUEST["int_name"]).");
";
	}
	else { // pk_search, complete overwrite seems ok
		// funktion zur entgegennahme der daten bauen, möglichkeit zur mod schaffen
		echo "
parent.as(\"controlData\",".fixStr(json_encode($controlData)).",".fixStr($_REQUEST["int_name"]).",\"data\");
parent.PkSelectUpdated(".fixStr($_REQUEST["int_name"]).");
";
	}
break;
}

$emptyMolecule=array( // make sure no bogus values remain
	array(
		"molfile_blob" => "", 
		"smiles" => "", 
		"smiles_stereo" => "", 
		"mw" => "", 
		"emp_formula" => "", 
		"standard_name" => "",
		"package_name" => "",
	)
);


// create temp GIFs
// print_r($_REQUEST);
if (is_array($_REQUEST["molecule_UID"])) {
	foreach($_REQUEST["molecule_UID"] as $UID) {
		$imgUID=uniqid();
		
		// GIF generieren
		$molfile=& $_REQUEST["molfile_".$UID];
		if ($_REQUEST["mode_".$UID]=="rxn") {
			$molecule=readRxnfile($molfile);
			// identifier einfügen, nur für reaktanten
			for ($a=0;$a<$molecule["reactants"];$a++) {
				$molecule["identifier"][$a]=numToLett($a+1);
			}
			for ($a=0;$a<$molecule["products"];$a++) {
				$molecule["identifier"][ $molecule["reactants"]+$a ]=$a+1;
			}
			//~ print_r($molecule);die();
			list($_SESSION["gifFile"][$imgUID],$_SESSION["svgFile"][$imgUID])=getReactionGif($molecule,$_REQUEST["width_".$UID],$_REQUEST["height_".$UID],0,1,2,array("png","svg"));
		}
		else {
			$molecule=readMolfile($molfile);
			list($_SESSION["gifFile"][$imgUID],$_SESSION["svgFile"][$imgUID])=getMoleculeGif($molecule,$_REQUEST["width_".$UID],$_REQUEST["height_".$UID],0,1,true,array("png","svg"));
		}
		$_SESSION["molFile"][$imgUID]=$molfile;
		
		if ($_REQUEST["desired_action_".$UID]=="loadData") {
			switch ($_REQUEST["int_name_".$UID]) {
			
			case "rxnfile_blob": // alles
				if ($_REQUEST["invocationCommand"]=="full") {
					$offset=0;
					foreach (array("reactants","products") as $list_int_name) {
						for ($a=0;$a<$molecule[$list_int_name];$a++) {
							// UID
							$molUID=uniqid();
							$thisMolecule=& $molecule["molecules"][$offset+$a];
							// gif
							list($_SESSION["gifFile"][$molUID],$_SESSION["svgFile"][$molUID])=getMoleculeGif($thisMolecule,rc_gif_x,rc_gif_y,0,1,true,array("png","svg"));
							$molfile_single=writeMolfile($thisMolecule);
							$_SESSION["molFile"][$molUID]=$molfile_single;
							
							if (!empty($thisMolecule["smiles_stereo"])) {
								// data
								$structure_data["molecule"]=mysql_select_array(array(
									"table" => "molecule_for_reaction", 
									//~ "dbs" => "-1", 
									"filter" => "molecule.smiles_stereo LIKE BINARY ".fixStrSQLSearch(addSMILESslashes($thisMolecule["smiles_stereo"])), 
									"flags" => (($list_int_name!="products" && !$settings["do_not_use_inventory"])  ? QUERY_EDIT:QUERY_SIMPLE), 
								)); // search packages as well for reactants, but not for products
								// smiles LIKE BINARY ".fixStr($thisMolecule["smiles"]
								
								if (!count($structure_data["molecule"])) {
									$structure_data["molecule"][0]=array(
										"molfile_blob" => addPipes($molfile_single), 
										"smiles" => $thisMolecule["smiles"], 
										"smiles_stereo" => $thisMolecule["smiles_stereo"], 
										"mw" => $thisMolecule["mw"], 
										"emp_formula" => $thisMolecule["emp_formula_string"], 
									);
								}
								
								addPackageNames($structure_data);
							}
							
							// update/add
							echo "parent.addMolFromRxn(".fixStr($molUID).",".fixStr($list_int_name).",".$a.",".fixStr($thisMolecule["smiles_stereo"]).",".json_encode($structure_data).",".fixStr(addPipes($molfile_single)).");\n";
						} // smiles
						$offset+=$molecule[$list_int_name];
					}
					$structure_data=array();
				}
				// RXN schreiben und setzen (kann sich durch zusammenfassen geändert haben
				echo "parent.setInputValue(".fixStr($_REQUEST["int_name_".$UID]).",".fixStr(addPipes(writeRxnfile($molecule))).");\n";
				// have arrays for reactants and products SMILES for ordering and assingment of lines, all other will be done by "molfile_blob"
			break;
			
			case "reactants": // einzelnes startmat
			case "products": 
			case "reagents":
			case "copyTable": 
				if ($molecule["smiles_stereo"]) {
					// lookup molecule
					$structure_data["molecule"]=mysql_select_array(array(
						"table" => "molecule_for_reaction", 
						//~ "dbs" => "-1", 
						"filter" => "molecule.smiles_stereo LIKE BINARY ".fixStrSQLSearch(addSMILESslashes($molecule["smiles_stereo"])), 
						"flags" => (($_REQUEST["int_name_".$UID]!="products" && !$settings["do_not_use_inventory"])  ? QUERY_EDIT:QUERY_SIMPLE), 
					)); // search packages as well for reactants, but not for products
					// "molecule.smiles LIKE BINARY ".fixStrSQL($molecule["smiles"])
					
					// if nothing found, try non-stereo SMILES
					if (!count($structure_data["molecule"])) {
						$structure_data["molecule"]=mysql_select_array(array(
							"table" => "molecule_for_reaction", 
							//~ "dbs" => "-1", 
							"filter" => "molecule.smiles LIKE BINARY ".fixStrSQLSearch(addSMILESslashes($molecule["smiles"])), 
							"flags" => (($_REQUEST["int_name_".$UID]!="products" && !$settings["do_not_use_inventory"]) ? QUERY_EDIT:QUERY_SIMPLE), 
						)); // search packages as well for reactants, but not for products
					}
					
					// absolutely nothing found
					if (!count($structure_data["molecule"])) {
						$structure_data["molecule"][0]=array(
							"molfile_blob" => $molfile, 
							"smiles" => $molecule["smiles"], 
							"smiles_stereo" => $molecule["smiles_stereo"], 
							"mw" => $molecule["mw"], 
							"emp_formula" => $molecule["emp_formula_string"], 
							"standard_name" => "",
							"package_name" => "",
						);
					}
					
					// generate package_name within moleculeData
					addPackageNames($structure_data);
				}
				else { // empty
					$structure_data["molecule"]=$emptyMolecule;
				}
				
				echo "parent.setControlDataMolecule(".fixStr($_REQUEST["int_name_".$UID]).",".fixStr($UID).",".fixStr($_REQUEST["field_".$UID]).",".fixStr($_REQUEST["group_".$UID]).",".json_encode($structure_data).");
parent.updateMolSelect(".fixStr($_REQUEST["int_name_".$UID]).",".fixStr($UID).",".fixStr($_REQUEST["field_".$UID]).",".fixStr($_REQUEST["group_".$UID]).",true);\n"; // no update neccessary
			break;
			
			/* case "reagents":
				//~ $structure_data=array("smiles" => $molecule["smiles"], "mw" => $molecule["mw"], "emp_formula" => $molecule["emp_formula_string"]);
				if ($molecule["smiles_stereo"]) {
					// lookup molecule, take only 1st result for now
					$structure_data["molecule"]=mysql_select_array(array(
						"table" => "molecule_for_reaction", 
						//~ "dbs" => "-1", 
						"filter" => "molecule.smiles_stereo LIKE BINARY ".fixStrSQLSearch($molecule["smiles_stereo"]), 
						"flags" => !$settings["do_not_use_inventory"] ? QUERY_EDIT:QUERY_SIMPLE, 
					));
					
					// "molecule.smiles LIKE BINARY ".fixStr($molecule["smiles"])
					if (!count($structure_data["molecule"])) {
						$structure_data["molecule"][0]=array(
							"molfile_blob" => $molfile, 
							"smiles" => $molecule["smiles"], 
							"smiles_stereo" => $molecule["smiles_stereo"], 
							"mw" => $molecule["mw"], 
							"emp_formula" => $molecule["emp_formula_string"], 
							"standard_name" => "",
							"package_name" => "",
						);
					}
					
					// generate package_name within moleculeData
					addPackageNames($structure_data);
				}
				else { // empty
					$structure_data["molecule"]=$emptyMolecule;
				}
				
				echo "parent.setControlDataMolecule(".fixStr($_REQUEST["int_name_".$UID]).",".fixStr($UID).",".fixStr($_REQUEST["field_".$UID].",".fixStr($_REQUEST["group_".$UID]).",".json_encode($structure_data).");
parent.updateMolSelect(".fixStr($_REQUEST["int_name_".$UID]).",".fixStr($UID).",\"\",undefined,true);\n"; // no update neccessary
			break;*/
			
			case "molfile_blob":
			default:
				// SMILES, Summenformel und MW setzen
				$molecule_data[$UID]=array(
					"smiles" => $molecule["smiles"], 
					"smiles_stereo" => $molecule["smiles_stereo"], 
					"mw" => $molecule["mw"], 
					"emp_formula" => $molecule["emp_formula_string"], 
				);
				echo "parent.setControlValues(".json_encode($molecule_data[$UID]).",false);\n";
			}
		}
		// Update auslösen
		echo "parent.moleculeUpdated(".fixStr($imgUID).",".fixStr($_REQUEST["int_name_".$UID]).",".fixStr($UID).",".fixStr($_REQUEST["field_".$UID]).",".fixStr($_REQUEST["group_".$UID]).");\n"; // UID identical for the direct changes
	}
	// handle data if any, callback function
	echo "parent.executeForms(\"structuresUpdated\",".json_encode($molecule_data).");\n"; // setzt controlData im parent
}

echo "
}
"._script."
</body>
</html>";

completeDoc();
?>
