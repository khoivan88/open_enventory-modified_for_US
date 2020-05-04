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
// Acros
$GLOBALS["code"]="Activate";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "Activate Scientific", 
	"logo" => "aslogo.png", 
	"height" => 40, 
	"vendor" => true, 
	"hasPriceList" => 2, 
	
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="http://shop.activate-scientific.com"; // startPage
	$suppliers[$code]["urls"]["chemicalize_server_url"]="https://catalog.chemicalize.com";
	$suppliers[$code]["urls"]["search"]=$urls["chemicalize_server_url"]."/v1/48cb00fd27694c7c8824bbd4c566177e/search";
	$suppliers[$code]["urls"]["search_referer"]=$urls["chemicalize_server_url"]."/v1/48cb00fd27694c7c8824bbd4c566177e/editor.html";
	$suppliers[$code]["urls"]["detail"]=$urls["server"]."/code/";
	$suppliers[$code]["urls"]["startPage"]=$urls["server"];
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="url";
	$retval["action"]=$urls["search"].urlencode($query_obj["vals"][0][0]);
	
	return $retval;
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	return $urls["detail"].$catNo."?referrer=enventory"; // last number is irrelevant
'),
"getInfo" => create_function('$catNo',getFunctionHeader().'
	$url=$self["getDetailPageURL"]($catNo);
	if (empty($url)) {
		return $noConnection;
	}
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$response=oe_http_get($url,$my_http_options);
	if ($response==FALSE) {
		return $noConnection;
	}
	return $self["procDetail"]($response,$catNo);
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$my_http_options["referer"]=$urls["search_referer"];
	$my_http_options["mime"]="application/json; charset=utf-8";
	$response=@oe_http_post_fields($urls["search"],json_encode(array(
		"hitColor" => "#ff8000", 
		"hitColoring" => "OFF", 
		"limit" => "50", 
		"searchType" => "FULL", 
		"similarityThreshold" => "0.5", 
		"structure" => $searchText
	)),array(),$my_http_options);
	if ($response==FALSE) {
		return $noConnection;
	}
	return $self["procHitlist"]($response);
'),
"procDetail" => create_function('& $response,$catNo=""',getFunctionHeader().'
	$body=utf8_encode(@$response->getBody());
	cutRange($body,"<h1","class=\"footer-main\"");
	$result=array();
	$result["catNo"]=$catNo;
	
	// name
	preg_match("/(?ims)<h1.*?>(.*?)<\/h1>/",$body,$name_data);
	$result["molecule_names_array"]=array(fixTags($name_data[1]));
	
	preg_match_all("/(?ims)<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/",$body,$manyLines,PREG_SET_ORDER);
	//~ print_r($manyLines);die();
	
	for ($b=0;$b<count($manyLines);$b++) {
		$name=fixTags($manyLines[$b][1]);
		$rawValue=$manyLines[$b][2];
		$value=fixTags($rawValue);
		
		switch ($name) {
			case "Catalog #":
				$result["catNo"]=$value;
			break;
			case "C.A.S.:":
				$value=trim($value,"[]");
				if (strpos($value,", ")!==FALSE) {
					$value=getBestCAS(explode(", ",$value));
				}
				$result["cas_nr"]=$value;
			break;
			case "Formula:":
				$result["emp_formula"]=$value;
			break;
			case "Mass:":
				$result["mw"]=getNumber($value);
			break;
			case "Material Safety Data Sheet:":
				if (preg_match("/(?ims)<a [^>]*href=\"([^\"]+)\"[^>]*>/",$rawValue,$href_match)) {
					$result["default_safety_sheet"]="";
					$result["default_safety_sheet_url"]="-".$self["urls"]["server"].htmlspecialchars_decode($href_match[1]);
					$result["default_safety_sheet_by"]=$self["name"];
				}
			break;
		}
	}
	
	// prices
	preg_match_all("/(?ims)<form[^>]+class=\"buybox--form\"[^>]*>(.*?)<\/form>/",$body,$manyLines,PREG_PATTERN_ORDER);
	$manyLines=$manyLines[0];

	for ($b=0;$b<count($manyLines);$b++) {
		preg_match_all("/(?ims)<div[^>]*>(.*?)<\/div>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
		$cells=$cells[0];
		
		if (count($cells)<4) {
			continue;
		}
		
		list(,$amount,$amount_unit)=getRange(fixTags($cells[1]));
		list(,$price,$currency)=getRange(trim(fixTags($cells[2])," *"));
		
		$result["price"][]=array(
			"supplier" => $code, 
			"amount" => $amount, 
			"amount_unit" => strtolower($amount_unit), 
			"price" => $price+0.0, 
			"currency" => fixCurrency($currency), 
			"catNo" => $catNo, 
			"beautifulCatNo" => fixTags($cells[0]), 
		);
	}
	
	$result["supplierCode"]=$code;
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=@$response->getBody();
	$json=json_decode($body,true);
//~ 	print_r($json);die();
	$results=array();
	$catNos=array();
	if (is_array($json)) foreach ($json["results"] as $result) {
		if (!in_array($result["productId"],$catNos)) {
			$catNos[]=$result["productId"];
			$results[]=array(
				"name" => fixTags($result["properties"]["bezeichnung"]), 
				"beautifulCatNo" => $result["productId"], 
				"catNo" => $result["productId"], 
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
'),
);
$GLOBALS["suppliers"][$code]["init"]();
?>