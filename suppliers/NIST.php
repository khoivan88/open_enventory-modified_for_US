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
// NIST
$GLOBALS["code"]="NIST";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "NIST Webbook", 
	"logo" => "logo_nist.gif", 
	"height" => 36, 
	"strSearchFormat" => "Molfile",

"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="http://webbook.nist.gov";
	$suppliers[$code]["urls"]["base"]=$urls["server"]."/cgi/cbook.cgi";
	//$suppliers[$code]["urls"]["base"]="http://localhost/storage/dump.php";
	$suppliers[$code]["urls"]["startPage"]=$urls["server"]."/chemistry"; // startPage
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	if ($query_obj["crits"][0]=="molfile_blob") {
		$retval["method"]="scrape";
		$retval["supplier"]=$code;
		$retval["results"]=$self["strSearch"]($query_obj["crits"][0][1],$query_obj["ops"][0]);
	}
	else {
		$retval["method"]="url";
		addWildcards($query_obj["vals"][0][0],$query_obj["ops"][0]);
		$searchType="&Name=";
		if ($query_obj["crits"][0]=="cas_nr") {
			$searchType="&ID=";
		}
		elseif ($query_obj["crits"][0]=="emp_formula") {
			$searchType="&NoIon=true&Formula=";
		}
		$retval["action"]=$urls["base"]."?Units=SI".$searchType.$query_obj["vals"][0][0];
	}
	return $retval;
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	return $urls["base"]."?Units=SI&ID=".$catNo."&referrer=enventory";
'),
"getInfo" => create_function('$catNo',getFunctionHeader().'
	$url=$self["getDetailPageURL"]($catNo);
	if (empty($url)) {
		return $noConnection;
	}
	$url.="&Mask=4";
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$my_http_options["referer"]=$url;
	$response=oe_http_get($url,$my_http_options);
	if ($response==FALSE) {
		return $noConnection;
	}

	return $self["procDetail"]($response,$catNo);
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	$baseurl=$urls["base"]."?Units=SI";
	switch($filter) {
	case "cas_nr":
	case "structure":
		$url=$baseurl."&ID=".urlencode($searchText);
	break;
	case "molecule.emp_formula": // außerdem AllowOther und AllowExtra ? Kommt drauf an
		$url=$baseurl."&NoIon=true&Formula=".urlencode($searchText);
	break;
	default:
		addWildcards($searchText,$mode);
		$url=$baseurl."&Name=".urlencode($searchText);
	}

	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$response=oe_http_get($url,$my_http_options);
	if ($response==FALSE) {
		return $noConnection;
	}

	return $self["procHitlist"]($response);
'),
"procDetail" => create_function('& $response,$catNo=""',getFunctionHeader().'
	$body=utf8_encode(@$response->getBody());
	
	$body=str_replace("</li>","",$body);
	$manyLines=explode("<li>",$body);
	$result["molecule_names_array"]=array();
	if (is_array($manyLines)) foreach($manyLines as $line) {
		list($name,$raw_value)=explode("</strong>",$line,2);
		$name=fixTags($name,true);
		$value=fixTags($raw_value);
		switch($name) {
		case "formula:":
			$result["emp_formula"]=$value;
		break;
		case "molecular weight:":
			$result["mw"]=$value;
		break;
		case "cas registry number:":
			$result["cas_nr"]=$value;
		break;
		case "other names:":
			$value=html_entity_decode(str_replace(array("\n","\r"),"",$value),ENT_QUOTES,"UTF-8");
			$result["molecule_names_array"]=explode(";",$value);
		break;
		case "chemical structure:": // molfile
			preg_match("/(?ims)<a href=\"\/cgi\/cbook\.cgi(.*?)\"[^>]*>2d Mol file<\/a>/",$raw_value,$urlpart);
			if (!empty($urlpart[1])) {
				$my_http_options=$default_http_options;
				$my_http_options["redirect"]=maxRedir;
				$response2=oe_http_get($urls["base"].$urlpart[1],$my_http_options);
				$result["molfile_blob"]=@$response2->getBody();
			}
		break;
		}
	}
	// get main name
	preg_match("/(?ims)<h1[^>]+id=[^>]+>(.*?)<\/h1>/",$body,$main_name);
	array_unshift($result["molecule_names_array"],fixTags($main_name[1]) );
	
	// get catNo
	preg_match("/(?ims)GetInChI=(.*?)\"/",$body,$catNo);
	$result["catNo"]=$catNo[1];
	
	// get mp,bp
	cutRange($body,"Phase change data","</table>",false); // get only table with mp,bp
	
	preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$manyLines,PREG_PATTERN_ORDER);
	$manyLines=$manyLines[0];
	if (is_array($manyLines)) foreach($manyLines as $line) {
		preg_match_all("/(?ims)<td.*?<\/td>/",$line,$cells,PREG_PATTERN_ORDER);
		$cells=$cells[0];
		$quantity=strtolower(fixTags($cells[0],true));
		switch ($quantity) {
		case "tboil": // bp
			$result["bp_high"]=$self["parseTableVal"]($cells[1],$cells[2]);
			if (!isEmptyStr($result["bp_high"])) {
				$result["bp_press"]=1;
				$result["press_unit"]="mbar";
			}
		break;
		case "cells[1]": // mp
			$result["mp_high"]=$self["parseTableVal"]($cells[1],$cells[2]);
		break;
		}
	}
	
	// No SDS available
	
	$result["supplierCode"]=$code;
	//~ $result["catNo"]=$catNo;
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=@$response->getBody();
	
	$baseurl=$urls["base"]."?Units=SI";
	if (strpos($body,"Not Found")!==FALSE || strpos($body,"No matching structures found")!==FALSE || strpos($body,"No structures matching")!==FALSE) {
		return $noResults;
	}
	if (strpos($body,"Search Results")===FALSE) {
		// if only one result, directly to detail page
		$result[0]=$self["procDetail"]($response);
		extendMoleculeNames($result[0]);
		$result[0]=array_merge($result[0],array("name" => $result[0]["molecule_name"], "supplierCode" => $code, "catNo" => $result[0]["cas_nr"]) );
	}
	else {
		cutRange($body,"<ol>","</ol>");
		$body=str_replace("</li>","",$body);
		$manyLines=explode("<li>",$body);
		$result=array();
		if (is_array($manyLines)) foreach ($manyLines as $line) {
			preg_match("/(?ims)<a href=\".*?ID=(.+?)[&|\"].*?>(.*?)<\/a>(.*?\((.*?)\))?/",$line,$items);
			$catNo=$items[1];
			if (!empty($catNo)) {
				$result[]=array("catNo" => $catNo, "name" => fixTags($items[2]), "supplierCode" => $code, "addInfo" => fixTags($items[4]) );
			}
		}
	}
	return $result;
'),
"getBestHit" => create_function('& $hitlist,$name=NULL','
	if (!is_null($name)) {
		$a=count($hitlist)-1;
		while ($a>=0) {
			if ($name==strtolower($hitlist[$a]["name"])) {
				return $a;
			}
			$a--;
		}
	}
	$a=count($hitlist)-1;
	while ($a>=0 && (strpos($hitlist[$a]["name"],"radical")!==FALSE || strpos($hitlist[$a]["name"],"&quot;")!==FALSE)) {
		$a--;
	}
	return $a;
'),
"parseTableVal" => create_function('$value,$unit','
	list($value,)=explode("&",fixTags($value),2);
	$value=trim($value);
	$unit=fixTags($unit);
	$retval="";
	switch ($unit) {
	case "K":
		$retval=$value-zeroC;
	break;
	case "C":
		$retval=$value;
	break;
	}
	return $retval;
'),
"strSearch" => create_function('$molfile,$mode="se"',getFunctionHeader().'
	if ($mode=="se") {
		$type="Struct";
	}
	else {
		$type="Sub";
	}
	$tempname=tempnam("/tmp","NIST_CAS"); // , I personally dont like tempfiles, but in this case it is necessary
	$handle = fopen($tempname, "w");
	fwrite($handle,$molfile);
	fclose($handle);
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$my_http_options["referer"]="http://webbook.nist.gov/chemistry/str-file.html";
	$response=oe_http_post_fields($urls["base"], array("StrSave" => "File", "Type" => $type, "Units" => "SI"), array( array("name" => "MolFile", "type" => "chemical/x-mdl-molfile", "file" => $tempname) ),$my_http_options); // text/plain or chemical/x-mdl-molfile
	unlink($tempname);
	if ($response==FALSE) {
		return $noConnection;
	}
	$body=@$response->getBody();
	
	// cover case when no automatic redir is done
	if (strpos($body,"<ol>"===FALSE)) {
		preg_match("/(?ims)<a href=\"([^\"]*StrSearch[^\"]*)\"[^>]*>here<\/a>/",$body,$redir_data);
		$url=$urls["server"].$redir_data[1];
		$response=oe_http_get($url,$my_http_options);
	}
	
	return $self["procHitlist"]($response);
'),
"getCAS" => create_function('& $molfile',getFunctionHeader().'
	$result=$self["strSearch"]($molfile,"se");
	if (count($result)>1) {
		$a=$self["getBestHit"]($result);
		$result[0]=$self["getInfo"]($result[$a]["catNo"]);
		$result[0]["supplierCode"]=$code;
	}
	return $result[0];
'),
"getCASfromName" => create_function('$name',getFunctionHeader().'
	$name=strtolower($name);
	$hitlist=$self["getHitlist"]($name,"molecule_name","ex");
	if (!empty($hitlist[0]["cas_nr"])) {
		return $hitlist[0]["cas_nr"];
	}
	$info=$self["getInfo"]($hitlist[ $self["getBestHit"]($hitlist,$name) ]["catNo"]);
	return $info["cas_nr"];
')
);
$GLOBALS["suppliers"][$code]["init"]();
?>
