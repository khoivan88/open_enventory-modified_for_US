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
// VWR
$GLOBALS["code"]="VWR";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "VWR", 
	"logo" => "vwr_intl_logo2.gif", 
	"height" => 40, 
	"vendor" => true, 
	"hasPriceList" => 1, 
	"hasPurity" => true, 
	"excludeTests" => array("emp_formula"), 

"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="https://de.vwr.com"; // startPage
	$suppliers[$code]["urls"]["init"]=$urls["server"]."/store/"; // get cookies and _dynSessConf
	$suppliers[$code]["urls"]["search_form"]=$urls["server"]."/store/search/searchAdv.jsp?tabId=advSearch";
	$suppliers[$code]["urls"]["search"]=$urls["server"]."/store/product?view=list&pageSize=64&";
	$suppliers[$code]["urls"]["detail"]=$urls["server"]."/store/catalog/product.jsp?catalog_number=";
	$suppliers[$code]["urls"]["startPage"]=$urls["server"];
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="post";
	$retval["action"]=$urls["search"];
	$fields=array(
		"/vwr/search/SearchFormHandler.CASNumber" => "", 
		"/vwr/search/SearchFormHandler.advSearch" => "Suchen", 
		"/vwr/search/SearchFormHandler.catalogNumber" => "", 
		"/vwr/search/SearchFormHandler.chemicalFormula" => "", 
		"/vwr/search/SearchFormHandler.chemicalName" => "", 
		"/vwr/search/SearchFormHandler.currentView" => "ADV", 
		"/vwr/search/SearchFormHandler.keyword" => "", 
		"/vwr/search/SearchFormHandler.mDLNumber" => "", 
		"/vwr/search/SearchFormHandler.merckIndexNumber" => "", 
		"/vwr/search/SearchFormHandler.molecularWeight" => "",
		"/vwr/search/SearchFormHandler.supplierName" => "",
		"_D:/vwr/search/SearchFormHandler.CASNumber" => "",
		"_D:/vwr/search/SearchFormHandler.advSearch" => "",
		"_D:/vwr/search/SearchFormHandler.catalogNumber" => "",
		"_D:/vwr/search/SearchFormHandler.chemicalFormula" => "",
		"_D:/vwr/search/SearchFormHandler.chemicalName" => "",
		"_D:/vwr/search/SearchFormHandler.currentView" => "",
		"_D:/vwr/search/SearchFormHandler.keyword" => "",
		"_D:/vwr/search/SearchFormHandler.mDLNumber" => "",
		"_D:/vwr/search/SearchFormHandler.merckIndexNumber" => "",
		"_D:/vwr/search/SearchFormHandler.molecularWeight" => "",
		"_D:/vwr/search/SearchFormHandler.supplierName" => "",
		"_DARGS" => "/store/search/searchAdvGW.jsp.form2",
		"_dynSessConf" => $_SESSION["supplier_settings"][$code]["_dynSessConf"],
		"_dyncharset" => "UTF-8"
	);
	if ($query_obj["crits"][0]=="cas_nr") {
		$fields["/vwr/search/SearchFormHandler.CASNumber"]=$query_obj["vals"][0][0];
	}
	elseif ($query_obj["crits"][0]=="emp_formula") {
		$fields["/vwr/search/SearchFormHandler.chemicalFormula"]=$query_obj["vals"][0][0];
	}
	else {
		$fields["/vwr/search/SearchFormHandler.chemicalName"]=$query_obj["vals"][0][0];
	}
	
	$retval["forms"][]=array(
		"action" => $urls["search"], 
		"fields" => $fields
	);
	return $retval;
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	if (empty($catNo)) {
		return;
	}
	return $urls["detail"].$catNo."&referrer=enventory";
'),
"getInfo" => create_function('$catNo',getFunctionHeader().'
	$url=$self["getDetailPageURL"]($catNo);
	if (empty($url)) {
		return $noConnection;
	}
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$response=@oe_http_get($url,$my_http_options);
	if ($response==FALSE) {
		return $noConnection;
	}
	return $self["procDetail"]($response,$catNo);
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	$url=$urls["search"];
	if ($filter=="cas_nr") {
		$url.="casNum=";
	}
	elseif ($filter=="emp_formula") {
		$url.="chemFormula=";
	} else {
		$url.="keyword=";
	}
	$url.=urlencode($searchText);
	$my_http_options=$default_http_options;
	$my_http_options["referer"]=$urls["search_form"];
	
	$response=oe_http_get($url,$my_http_options);
	
	if ($response==FALSE) {
		return $noConnection;
	}
	
	return $self["procHitlist"]($response);
'),
"procDetail" => create_function('& $response,$catNo=""',getFunctionHeader().'
	$body=@$response->getBody();
	$body=str_replace(array("\t","\n","\r"),"",$body);
	if (strpos($body,"Fehlermeldung")!==FALSE) {
		return $noConnection;
	}
	cutRange($body,"class=\"main-content\"","id=\"writereviews\"");
	
	if (preg_match("/(?ims)<h1[^>]*>(.*?)<\/h1>/",$body,$name_data)) {
		$result["molecule_names_array"][]=fixTags($name_data[1]);
	}
	
	// synonyms impossible to use
	
	preg_match_all("/(?ims)<strong[^>]*>(.*?)<\/strong>(.*?)<br/",$body,$chem_data,PREG_SET_ORDER);
	//print_r($chem_data);die("XX");
	for ($b=0;$b<count($chem_data);$b++) {
		$name=trim(fixTags($chem_data[$b][1]),":");
		$value=fixTags($chem_data[$b][2]);

		switch ($name) {
		case "CAS":
		case "CAS-Nummer":
			$result["cas_nr"]=$value;
		break;
		case "Boiling Pt":
		case "Siedepunkt":
			list($result["bp_low"],$result["bp_high"],$press_info)=getRange($value);

			if (preg_match("/(?ims)\(([\d,\.]+)\s*(\w+)\)/",$press_info,$press_data)) {
				$result["bp_press"]=$press_data[1];
				$result["press_unit"]=$press_data[2];
				if ($result["press_unit"]=="hPa") {
					$result["press_unit"]="mbar";
				}
			}
			else {
				$result["bp_press"]=1;
				$result["press_unit"]="bar";
			}
		break;
		case "Melting Pt":
		case "Schmelzpunkt":
			list($result["mp_low"],$result["mp_high"])=getRange($value);
		break;
		case "Density":
		case "Dichte":
			$result["density_20"]=getNumber($value);
		break;
		case "Flash Pt":
		case "Flash-Pt":
		case "Flammpunkt":
			$result["molecule_property"][]=array("class" => "FP", "source" => $code, "value_high" => $value+0.0, "unit" => "°C");
		break;
		case "UN":
			$result["molecule_property"][]=array("class" => "UN_No", "source" => $code, "conditions" => $value);
		break;
		case "ADR":
			$result["molecule_property"][]=array("class" => "adr", "source" => $code, "conditions" => $value);
		break;
		}
	}
	
	preg_match("/(?ims)<div[^>]*class=\"substanceSpecifications\"[^>]*>(.*?)<\/div>/",$body,$safety_data);
	$safety_entries=explode("<br>",$safety_data[1]);
	for ($b=0;$b<count($safety_entries);$b++) {
		list($name,$value)=explode(": ",fixTags($safety_entries[$b]),2);
		
		switch ($name) {
		case "R":
			$result["safety_r"]=$value;
		break;
		case "S":
			$result["safety_s"]=$value;
		break;
		case "H":
			$value=str_replace(array("H"," "),array("","-"),$value);
			$result["safety_h"]=$value;
		break;
		case "P":
			$value=str_replace(array("P"," "),array("","-"),$value);
			$result["safety_p"]=$value;
		break;
		}
	}
	
	// match safety logos
	preg_match_all("/(?ims)<img[^>]*src=\"[^\"]*\/stibo\/thumb\/(\w+)\.[^\"]*\"[^>]*>/",$body,$safety_images,PREG_PATTERN_ORDER);
	$safety_images=$safety_images[1];
	$safety_sym=array();
	$safety_sym_ghs=array();
	$safety_sym_dict=array(
		"66024" => "C", 
		"66025" => "E", 
		"66026" => "F+", 
		"66027" => "F", 
		"66028" => "F", 
		"66029" => "N", 
		"66030" => "O", 
		"66031" => "T+", 
		"66032" => "T+", 
		"66033" => "T", 
		"66034" => "Xi", 
		"66035" => "Xn", 
	);
	$safety_sym_ghs_dict=array(
		"4760627" => "GHS01", 
		"4760628" => "GHS02", 
		"4760629" => "GHS03", 
		"4760630" => "GHS04", 
		"4760631" => "GHS05", 
		"4760632" => "GHS06", 
		"4760633" => "GHS07", 
		"4760634" => "GHS08", 
		"4760635" => "GHS09", 
	);
	for ($b=0;$b<count($safety_images);$b++) {
		$temp=$safety_sym_dict[ $safety_images[$b] ];
		if (!empty($temp)) {
			$safety_sym[]=$temp;
		}
		$temp=$safety_sym_ghs_dict[ $safety_images[$b] ];
		if (!empty($temp)) {
			$safety_sym_ghs[]=$temp;
		}
	}
	$result["safety_sym"]=join(",",$safety_sym);
	$result["safety_sym_ghs"]=join(",",$safety_sym_ghs);
	
	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=$response->getBody();
	if (strpos($body,"did not match any products")===FALSE && strpos($body,"hrte zu keinem Ergebnis")===FALSE) {
		
		$body=str_replace("&nbsp;"," ",$body);
		cutRange($body,"class=\"clearfix\"","<footer");
		$body=str_replace(array("\t","\n","\r"),"",$body);
		
		$result=array();
		preg_match_all("/(?ims)<div[^>]+class=\"search-item row\"[^>]*>.*?<div[^>]+class=\"row\"[^>]*>.*?<a[^>]+href=\"\/store\/catalog\/product\.jsp\?catalog_number=([^\"]+)\"[^>]*>(.*?)<\/a>(.*?)<div[^>]+class=\"form-group\"[^>]*>/",$body,$manyLines,PREG_SET_ORDER);
		for ($b=0;$b<count($manyLines);$b++) {
			$result[$b]=array("supplierCode" => $code, "name" => fixTags($manyLines[$b][2]), "catNo" => fixTags($manyLines[$b][1]));
		}
		// print_r($result);
		return $result;
	}
'),
"getBestHit" => create_function('& $hitlist,$name=NULL','
	if (count($hitlist)>0) {
		return 0;
	}
'),
// numbers formatted in an idiotic way
"getNumber" => create_function('$str','
	$retval=trim(str_replace(array(".",","),array("","."),strip_tags($str)));
	if ($retval==="") {
		return "";
	}
	if (strpos($str,"-")!==FALSE) {
		$retval*=-1.0;
	}
	else {
		$retval+=0.0;
	}
	return $retval;
')
);
$GLOBALS["suppliers"][$code]["init"]();
?>