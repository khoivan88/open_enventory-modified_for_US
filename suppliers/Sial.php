<?php
/*
Copyright 2006-2009 Felix Rudolphi and Lukas Goossen
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
// Sial
$GLOBALS["code"]="Sial";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "Sigma-Aldrich", 
	"logo" => "logo_SigmaAldrich.gif", 
	"height" => 50, 
	"vendor" => true, 
	//~ "hasPriceList" => 1, 
	"alwaysProcDetail" => true, 
	"country_cookies" => array(
		"country" => "DE", 
		"SialLocaleDef" => "CountryCode~DE|WebLang~-3|", 
		//~ "cmTPSet" => "Y", 
		//~ "fsr.s" => "{\"cp\":{\"COUNTRY\":\"DE\",\"REGION\":\"Europe\",\"ClientId\":\"Unknown\",\"MemberId\":\"Unknown\",\"SiteId\":\"SA\"},\"v\":1,\"rid\":\"1310627507085_527391\",\"pv\":7,\"to\":5,\"c\":\"http://www.sigmaaldrich.com/catalog/ProductDetail.do\",\"lc\":{\"d0\":{\"v\":7,\"s\":true}},\"cd\":0,\"sd\":0,\"f\":1310633027906,\"l\":\"en\",\"i\":-1}", 
		//~ "foresee.session" => "%7B%22alive%22%3A0%2C%22previous%22%3Anull%2C%22finish%22%3A1260376567205%2C%22cpps%22%3A%7B%22COUNTRY%22%3A%22NONE%22%2C%22REGION%22%3A%22NONE%22%2C%22ClientId%22%3A%22Unknown%22%2C%22MemberId%22%3A%22Unknown%22%7D%7D", 
		//~ "SialSiteDef" => "AnonymousClientId~Y|WebLang~-1|CountryCode~DE|", 
	),
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["startPage"]="https://www.sigmaaldrich.com"; // startPage
	$suppliers[$code]["urls"]["search"]=$urls["startPage"]."/catalog/search/SearchResultsPage?Query=";
	$suppliers[$code]["urls"]["detail"]=$urls["startPage"]."/catalog/product/";
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="url";
	$retval["action"]=$suppliers[$code]["urls"]["search"].$query_obj["vals"][0][0]."&Scope=";
	if ($query_obj["crits"][0]=="cas_nr") {
		$retval["action"].="CASSearch";
	}
	elseif ($query_obj["crits"][0]=="emp_formula") {
		$retval["action"].="MolecularFormulaSearch";
	}
	else {
		$retval["action"].="NameSearch";
	}
	
	return $retval;
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	list($brand,$productNumber)=explode("/",$catNo,2);
	return $urls["detail"].$brand."/".$productNumber."?lang=en&region=US&referrer=enventory";
'),
"getInfo" => create_function('$catNo',getFunctionHeader().'
	$url=$self["getDetailPageURL"]($catNo);
	if (empty($url)) {
		return $noConnection;
	}
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$my_http_options["cookies"]=$self["country_cookies"];
	$response=oe_http_get($url,$my_http_options); // set country by cookie directly and read prices
	if ($response==FALSE) {
		return $noConnection;
	}

	return $self["procDetail"]($response,$catNo);
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	$url=$urls["search"].urlencode($searchText)."&Scope=";
	if ($filter=="cas_nr") {
		$url.="CASSearch";
	}
	elseif ($filter=="emp_formula") {
		$url.="MolecularFormulaSearch";
	}
	else {
		$url.="NameSearch";
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
	global $lang,$default_http_options;
	
	$body=html_entity_decode(@$response->getBody(),ENT_QUOTES,"UTF-8");
	$cookies=oe_get_cookies($response);
	$cookies=array_merge($cookies,$self["country_cookies"]);
	
	$result=array();
	$result["price"]=array();
	$result["molecule_property"]=array();
	
	preg_match("/(?ims)id=\"productDetailHero\"(.*?)id=\"productDetailTabContainer\"/",$body,$top_data);
	// name
	preg_match("/(?ims)<h1.*?>(.*?)<\/h1>/",$top_data[1],$name_data);
	$result["molecule_names_array"]=array(fixTags($name_data[1]));

	preg_match_all("/(?ims)<li.*?>.*?<p.*?>(.*?)<span.*?>(.*?)<\/span>.*?<\/p>.*?<\/li>/",$top_data[1],$manyNVPs,PREG_SET_ORDER);
	//~ print_r($manyNVPs);die();
	
	for ($b=0;$b<count($manyNVPs);$b++) {
		$name=fixTags($manyNVPs[$b][1]);
		$value=fixTags($manyNVPs[$b][2]);
		
		if (startswith($name,"CAS Number")) {
			$result["cas_nr"]=$value;
		}
		elseif (startswith($name,"Molecular Weight")) {
			$result["mw"]=$value;
		}
		elseif (startswith($name,"Empirical Formula")) {
			$result["emp_formula"]=$value;
		}
	}

	cutRange($body,"id=\"contentWrapper\"","id=\"productDetailBlockContainer\"");
	
	$body=preg_replace(array("/(?ims)<script.*?<\/script>/","/(?ims)<style.*?<\/style>/"),"",$body);
	$body=str_ireplace(array("\t","<sub>","</sub>","<sup>","</sup>","<i>","</i>"),"",$body);
	$body=str_ireplace(array("&#160;","&nbsp;")," ",$body);
	
	preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$manyLines,PREG_PATTERN_ORDER);
	$manyLines=$manyLines[0];

	for ($b=0;$b<count($manyLines);$b++) {
		preg_match_all("/(?ims)<td.*?<\/td>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
		$cells=$cells[0];
		
		if (count($cells)>=2) {
			$self["handleCells"]($result,$cells);
		}
	}
	
	preg_match_all("/(?ims)<div[^>]* class=\"safetyRow\"[^>]*>\s*<div[^>]*>(.*?)<\/div>\s*<div[^>]*>(.*?)<\/div>\s*<\/div>/",$body,$specialLines,PREG_SET_ORDER);
	for ($b=0;$b<count($specialLines);$b++) {
		$self["handleCells"]($result,array_slice($specialLines[$b],1));
	}
	
	/* tries get pricing
	$cookies["fsr.s"]="{\"f\":1310634858411,\"cp\":{\"COUNTRY\":\"NONE\",\"REGION\":\"NONE\",\"ClientId\":\"Unknown\",\"MemberId\":\"Unknown\",\"SiteId\":\"SA\"}}";
	$cookies["fsr.a"]="1310634860821";
	
	$my_http_options=$default_http_options;
	$my_http_options["cookies"]=$cookies;
	$my_http_options["referer"]=$self["getDetailPageURL"]($catNo);
	$my_http_options["headers"]=array(
		"Accept" => "text/html, *\/*; q=0.01",
		"Accept-Language" => "de-de,de;q=0.8,en-us;q=0.5,en;q=0.3",
		"Accept-Encoding" => "gzip, deflate",
		"Accept-Charset" => "ISO-8859-1,utf-8;q=0.7,*;q=0.7",
		"Connection" => "keep-alive",
		"Content-Type" => "application/x-www-form-urlencoded; charset=UTF-8",
		"X-Requested-With" => "XMLHttpRequest",
	);
	
	print_r($my_http_options);
	list($brand,$productNumber)=explode("/",$catNo,2);
	$url="http://www.sigmaaldrich.com/catalog/PricingAvailability.do?productNumber=".$productNumber."&brandKey=".$brand."&divId=pricingContainer";
	echo $url;
	$prices=oe_http_post_fields($url, array("loadFor" => "PRD_RS", ), array(),$my_http_options);
	die($prices); */
	
	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	return $result;
'),
"handleCells" => create_function('& $result,$cells',getFunctionHeader().'
	$text=fixTags($cells[0]);
	$next_text=fixTags($cells[1]);
	if (strpos($text,"Synonym")!==FALSE) {
		$synonyms=explode(", ",$next_text);
		$result["molecule_names_array"]=arr_merge($result["molecule_names_array"],$synonyms);
	}
	elseif (strpos($text,"EC Number")!==FALSE) {
		if (!isEmptyStr($next_text)) {
			$result["molecule_property"][]=array("class" => "EG_No", "source" => $code, "conditions" => $next_text);
		}
	}
	elseif (strpos($text,"Hazard Codes")!==FALSE) {
		$result["safety_sym"]=$next_text;
	}
	elseif (strpos($text,"Symbol")!==FALSE) {
		$result["safety_sym_ghs"]=$next_text;
	}
	elseif (strpos($text,"Hazard statements")!==FALSE) {
		$result["safety_h"]=str_replace(array("H"," "),"",$next_text);
	}
	elseif (strpos($text,"Precautionary statements")!==FALSE) {
		$result["safety_p"]=str_replace(array("P"," "),"",$next_text);
	}
	elseif (strpos($text,"Risk Statements")!==FALSE) {
		$result["safety_r"]=$next_text;
	}
	elseif (strpos($text,"Safety Statements")!==FALSE) {
		$result["safety_s"]=$next_text;
	}
	elseif (strpos($text,"WGK Germany")!==FALSE) {
		$result["safety_wgk"]=$next_text;
	}
	elseif (strpos($text,"refractive index")!==FALSE) {
		cutRange($next_text,"/D","",false);
		//~ $result["n_20"]=$next_text;
		$result["n_20"]=getNumber($next_text);
	}
	elseif ($text=="bp") { // too short
		list($result["bp_low"],$result["bp_high"],$press)=getRange($next_text);
		if (isEmptyStr($result["bp_high"])) {
			// do nothing
		}
		elseif (trim($press)!="") {
			$result["bp_press"]=getNumber($press);
			if (strpos($press,"mm")!==FALSE) {
				$result["press_unit"]="torr";
			}
		}
		else {
			$result["bp_press"]="1";
			$result["press_unit"]="bar";			
		}
	}
	elseif ($text=="mp") { // too short
		list($result["mp_low"],$result["mp_high"])=getRange($next_text);
	}
	elseif (strpos($text,"density")!==FALSE) {
		//~ cutRange($next_text,"","g/mL",false);
		//~ $result["density_20"]=$next_text;
		$result["density_20"]=getNumber($next_text);
	}
	elseif (strpos($text,"RIDADR")!==FALSE) {
		$result["molecule_property"][]=array("class" => "adr", "source" => $code, "conditions" => $next_text);
		// get packing group, after last comma
		$lastSlash=strrpos($next_text,"/");
		if ($lastSlash!==FALSE) {
			$result["molecule_property"][]=array("class" => "packing_group", "source" => $code, "conditions" => getNumber(substr($next_text,$lastSlash+1)), );
		}
	}
	elseif (strpos($text,"Flash Point(C)")!==FALSE) {
		if (!isEmptyStr($next_text)) {
			$result["molecule_property"][]=array("class" => "FP", "source" => $code, "value_high" => $next_text+0.0, "unit" => "°C");
		}
	}
	elseif (strpos($text,"vapor pressure")!==FALSE) {
		$next_text=str_replace(array("&#x00b0;"),array("°"),$next_text);
		$vap_press_data=explode(" ",$next_text,3);
		if (!isEmptyStr($vap_press_data[0]) && !isEmptyStr($vap_press_data[1])) {
			$result["molecule_property"][]=array("class" => "Vap_press", "source" => $code, "value_high" => $vap_press_data[0]+0.0, "unit" => $vap_press_data[1], "conditions" => $vap_press_data[2]);
		}
	}
	elseif (strpos($text,"expl. lim.")!==FALSE) { // nur obere Grenze
		if (!isEmptyStr($next_text)) {
			$result["molecule_property"][]=array("class" => "Ex_limits", "source" => $code, "value_high" => $next_text+0.0, "unit" => "Vol.-%");
		}
	}
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=@$response->getBody();
	if (stripos($body,"No Results Found")!==FALSE) { // no results at all
		return $noResults;
	}
	cutRange($body,"<div id=\"searchResultContainer-inner\"","id=\"searchResultsPagination\">");
	//~ die($body);
	
	//~ preg_match_all("/(?ims)<div class=\"productContainer-inner\">.*?<div class=\"productContainer clearfix\">/",$body,$manyBlocks,PREG_PATTERN_ORDER);
	$manyBlocks=preg_split("/(?ims)<div [^>]*class=\"productContainer clearfix\"[^>]*>/",$body);
	//~ print_r($manyBlocks);die();
	$results=array();
	
	for ($c=1;$c<count($manyBlocks);$c++) { // 1st elemnt is bogus
		// get name
		preg_match("/(?ims)<h2 [^>]*class=\"name\"[^>]*>(.*?)<\/h2>/",$manyBlocks[$c],$name_data);
		$molecule_name=fixTags($name_data[1]);
	
		preg_match_all("/(?ims)name=\"compareCheckbox\".*?<\/ul>/",$manyBlocks[$c],$manyLines,PREG_PATTERN_ORDER);
		$manyLines=$manyLines[0];
		//~ print_r($manyLines);die();
		
		for ($b=0;$b<count($manyLines);$b++) {
			preg_match_all("/(?ims)<li.*?<\/li>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			// real list entry
			//~ print_r($cells);die();
			
			// http://www.sigmaaldrich.com/catalog/ProductDetail.do?lang=en&N4=658804|ALDRICH&N5=SEARCH_CONCAT_PNO|BRAND_KEY&F=SPEC
			
			preg_match("/(?ims)<a href=\"\/catalog\/product\/([^\/]*)\/([^\?\"]*)/",$manyLines[$b],$catNo_data);
			$results[]=array(
				"name" => $molecule_name, 
				"addInfo" => fixTags($cells[1]), 
				"beautifulCatNo" => fixTags($cells[0]), 
				"catNo" => $catNo_data[1]."/".$catNo_data[2], 
				"supplierCode" => $code, 
			);
		}
	}

	return $results;
'),
"getBestHit" => create_function('& $hitlist,$name=NULL','
	if (count($hitlist)>0) {
		return 0;
	}
')
);
$GLOBALS["suppliers"][$code]["init"]();
?>
