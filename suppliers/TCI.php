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
// TCI
$GLOBALS["code"]="TCI";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "TCI", 
	"logo" => "tci.gif", 
	"height" => 44, 
	"vendor" => true, 
	"hasPriceList" => 3, 
	"alwaysProcDetail" => true, 
	"searchTypeCode" => array("cas_nr" => 0, "emp_formula" => 4, "molecule_name" => 2),
	
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="http://www.tcichemicals.com"; // startPage
	$suppliers[$code]["urls"]["search"]=$urls["server"]."/eshop/en/eu/catalog/list/search?searchCategory=";
	$suppliers[$code]["urls"]["search2"]="&searchWord=";
	$suppliers[$code]["urls"]["search3"]="&PRODUCT-MAIN-BTN.x=18&PRODUCT-MAIN-BTN.y=22&mode=0";
	$suppliers[$code]["urls"]["detail"]=$urls["server"]."/eshop/en/eu/commodity/";
	$suppliers[$code]["urls"]["sds"]=$urls["server"]."/en/msds/search?item=";
	$suppliers[$code]["urls"]["sds2"]="&lang=";
	$suppliers[$code]["urls"]["startPage"]=$urls["server"];
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="url";
	$search_type_code=$self["searchTypeCode"][$query_obj["crits"][0]];
	$retval["action"]=$urls["search"].$search_type_code.$urls["search2"].urlencode($query_obj["vals"][0][0]).$urls["search3"];
	return $retval;
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	return $urls["detail"].$catNo.$urls["detail2"]."?referrer=enventory";
'),
"getInfo" => create_function('$catNo',getFunctionHeader().'
	$url=$self["getDetailPageURL"]($catNo);
	if (empty($url)) {
		return $noConnection;
	}
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$response=oe_http_get($url,$my_http_options);
	if ($response===FALSE) {
		return $noConnection;
	}
	return $self["procDetail"]($response,$catNo);
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	$search_type_code=$self["searchTypeCode"][$filter];
	$url=$urls["search"].$search_type_code.$urls["search2"].urlencode($searchText).$urls["search3"];
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$response=oe_http_get($url,$my_http_options);
	if ($response===FALSE) {
		return $noConnection;
	}
	
	return $self["procHitlist"]($response,$search_type_code.$searchText);
'),
"procDetail" => create_function('& $response, $catNo=""',getFunctionHeader().'
	$body=@$response->getBody();
	$body=str_replace(array("&nbsp;","&middot;"),array(" ","*"),$body);
	cutRange($body,"<h1 id=\"page-title\"","id=\"side\"");
	
	$result=array();
	$result["catNo"]=$catNo; // may be overwritten later
	$result["price"]=array();
	$result["molecule_property"]=array();
	
	preg_match("/(?ims)<h1[^>]*>(.*?)<br/",$body,$name_data);
	$result["molecule_names_array"]=array(fixTags($name_data[1]));
	
	preg_match_all("/(?ims)<img\s+src=\"\/eshop\/image\/pictgram\/G(\d+)\.gif\"/",$body,$ghs_syms,PREG_PATTERN_ORDER);
	if (count($ghs_syms) && count($ghs_syms[1])) {
		$ghs_syms=join(",GHS0",$ghs_syms[1]);
		if (!isEmptyStr($ghs_syms)) {
			$result["safety_sym_ghs"]="GHS0".$ghs_syms; // add prefix while joining
		}
	}

	$safety=array();
	preg_match_all("/(?ims)<[^>]+class\=\"[^\"]*code[^\"]*\"[^>]*>(.*?):/",$body,$phrases,PREG_PATTERN_ORDER);
	$phrases=$phrases[1];
	if (is_array($phrases)) foreach ($phrases as $phrase) {
		if (preg_match("/(?ims)(R|S|H|P)\s*(\d[\d\/\+\sHPRS]*)/",$phrase,$phrase_data)) {
			$safety[ $phrase_data[1] ][]=str_replace(array($phrase_data[1]," "),"",$phrase_data[2]);
		}
	}
	$result["safety_r"]=@join("-",$safety["R"]);
	$result["safety_s"]=@join("-",$safety["S"]);
	$result["safety_h"]=@join("-",$safety["H"]);
	$result["safety_p"]=@join("-",$safety["P"]);

	preg_match_all("/(?ims)<tr[^>]*>(.*?)<\/tr>/",$body,$lines,PREG_PATTERN_ORDER);
	$lines=$lines[1];
	if (is_array($lines)) foreach ($lines as $line) {
		if (preg_match("/(?ims)<th[^>]*>(.*?)<\/th>\s*<td[^>]*>(.*?)<\/td>/",$line,$property)) {
			$name=fixHtml($property[1]);
			$value=fixHtml($property[2]);
			
			switch ($name) {
			case "Product Number":
				$result["catNo"]=$value;
			break;
			case "CAS Number":
			case "CAS RN":
				$result["cas_nr"]=$value;
			break;
			case "UN Number":
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "UN_No", "source" => $code, "conditions" => $value);
				}
			break;
			case "EC Number":
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "EG_No", "source" => $code, "conditions" => $value);
				}
			break;
			case "SG":
			case "Specific gravity (20/20)":
				$result["density_20"]=getNumber($value);
			break;
			case "Refractive index n20/D":
				$result["n_20"]=getNumber($value);
			break;
			case "mp":
				list($result["mp_low"],$result["mp_high"])=getRange($value);
			break;
			case "bp":
				list($result["bp_low"],$result["bp_high"])=getRange($value);
			break;
			case "flp":
				$result["molecule_property"][]=array("class" => "FP", "source" => $code, "value_high" => getNumber($value), "unit" => "°C", );
			break;
			case "Packing Group":
				$result["molecule_property"][]=array("class" => "packing_group", "source" => $code, "conditions" => $value, );
			break;
			case "Signal Word":
				$result["safety_text"]=$value;
			break;
			case "M.F. / M.W.":
				list($result["emp_formula"],$result["mw"])=explode("=",$value);
			break;
			}
		}
	}
	
	// read prices
	$result["price"]=$self["parsePriceList"]($body);

	$result["supplierCode"]=$code;
	return $result;
'),
"procHitlist" => create_function('& $response,$catNo',getFunctionHeader().'
	$body=@$response->getBody();
	$body=str_replace(array("&nbsp;"),array(" "),$body);
	cutRange($body,"id=\"main\"","id=\"tabPanel02\"");
	if (stripos($body,"The result is over")!==FALSE) { // too many results
		return $noConnection;
	}
	if (stripos($body,"No match found")!==FALSE) { // no results at all
		return $noResults;
	}

	$results=array();
	if (stripos($body,"earch result of")===FALSE) {
		$results[0]=$self["procDetail"]($response);
		extendMoleculeNames($results[0]);
		$results[0]=array_merge($results[0],array("name" => $results[0]["molecule_name"], "supplierCode" => $code, ) );
	}
	else {
		preg_match_all("/(?ims)<div[^>]*class=\"bobW\".*?<\/form>/",$body,$manyLines,PREG_SET_ORDER);
		//~ print_r($manyLines);
		for ($b=0;$b<count($manyLines);$b++) {
			if (preg_match_all("/(?ims)(<th.*?<\/th>)\s*(<td.*?<\/td>)/",$manyLines[$b][0],$nameValuePairs,PREG_SET_ORDER)) {
				$result=array("supplierCode" => $code,);
				foreach ($nameValuePairs as $nameValuePair) {
					$name=fixTags($nameValuePair[1]);
					if ($name=="Product Number") {
						$result["catNo"]=fixTags($nameValuePair[2]);
					}
					elseif ($name=="Product Name") {
						$result["name"]=fixTags($nameValuePair[2]);
					}
				}
				
				// read prices
				$result["price"]=$self["parsePriceList"]($manyLines[$b][0]);
				$results[]=$result;
			}
		}
	}

	return $results;
'),
"parsePriceList" => create_function('$html','
$retval=array();
cutRange($html,"<table class=\"price-tbl\">","</table>");
if (preg_match_all("/(?ims)<tr.*?(<td.*?<\/td>)\s*(<td.*?<\/td>)/",$html,$nameValuePairs,PREG_SET_ORDER)) {
	foreach ($nameValuePairs as $nameValuePair) {
		list(,$amount,$amount_unit)=getRange(fixTags($nameValuePair[1]));
		list(,$price,$currency)=getRange(fixTags($nameValuePair[2]));
		$retval[]=array(
			"supplier" => $code, 
			"amount" => $amount, 
			"amount_unit" => strtolower($amount_unit), 
			"price" => $price, 
			"currency" => fixCurrency($currency), 
		);
	}
}
return $retval;
'),
"getBestHit" => create_function('& $hitlist,$name=NULL','
	if (count($hitlist)>0) {
		return 0;
	}
'),
);
$GLOBALS["suppliers"][$code]["init"]();
?>