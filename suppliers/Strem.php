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
// Strem
$GLOBALS["code"]="Strem";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "Strem", 
	"logo" => "logo_strem.gif", 
	"height" => 50, 
	"vendor" => true, 
	"hasPriceList" => 0, 
	"excludeFields" => array(), 

"init" => create_function('',getFunctionHeader().'
	$urls["server"]="http://www.strem.com"; // startPage
	$urls["base"]=$urls["server"]."/catalog/index.php";
	$urls["detail"]=$urls["server"]."/catalog/v/";
	$urls["startPage"]=$urls["server"];
'),
/*"getPrices" => create_function('$catNo',getFunctionHeader().'
	// prepare cookies
	$fields=array(
		"country" => "de",
		"page_function" => "select_country",
	);
	$my_http_options=$default_http_options;
	$response=@oe_http_post_fields($urls["base"],$fields,array(),$my_http_options);
	@$response->getBody();
	$cookies=oe_get_cookies($response);
	
	$url=$self["getDetailPageURL"]($catNo);
	if (empty($url)) {
		return $noConnection;
	}
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$my_http_options["cookies"]=$cookies;
	$response=oe_http_get($url,$my_http_options);
	if ($response==FALSE) {
		return $noConnection;
	}
	$body=utf8_encode(@$response->getBody());
	die($body);
	$result=array(
		"price" => array()
	);
	
	if (preg_match("/(?ims)<table[^>]*class=\"price_table\"[^>]*>(.*?)<\/table>/",$body,$match)) {
		preg_match_all("/(?ims)<tr.*?<\/tr>/",$match[1],$manyLines,PREG_PATTERN_ORDER);
		$manyLines=$manyLines[0];
		//~ var_dump($manyLines);die();
		for ($b=0;$b<count($manyLines);$b++) {
			preg_match_all("/(?ims)<td.*?<\/td>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			
			if (count($cells)<3) {
				continue;
			}
			
			list(,$amount,$amount_unit)=getRange(fixTags($cells[0]));
			list($price,$currency)=explode("</span>", $cells[0],2);
			
			$result["price"][]=array(
				"supplier" => $code, 
				"amount" => $amount, 
				"amount_unit" => $amount_unit, 
				"price" => $price, 
				"currency" => $currency, 
			);
		}
	}
	
	return $result;
'),*/
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="url";
	$retval["action"]=$urls["base"]."?focus=";
	if ($query_obj["crits"][0]=="cas_nr") {
		$retval["action"].="cas";
	}
	elseif ($query_obj["crits"][0]=="emp_formula") {
		$retval["action"].="formula";
	}
	else {
		$fields["action"].="product";
	}
	$retval["action"].="&keyword=".urlencode($query_obj["vals"][0][0])."&x=10&y=10&page_function=keyword_search&display_products=list";
	
	return $retval;
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	return $urls["detail"].$catNo."/?referrer=enventory"; // last number is irrelevant
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
	// http://www.strem.com/catalog/index.php?focus=product&keyword=nacnac&x=9&y=20&page_function=keyword_search&display_products=list
	$url=$urls["base"]."?focus=";
	if ($filter=="cas_nr") {
		$url.="cas";
	}
	elseif ($filter=="emp_formula") {
		$url.="formula";
	}
	else {
		$url.="product";
	}
	$url.="&keyword=".urlencode($searchText)."&x=10&y=10&page_function=keyword_search&display_products=list";
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
	cutRange($body,"<div id=\"page_header_catalog\">","Enter a lot number",false);
	$result=array();
	$result["catNo"]=$catNo;
	
	// MSDS, take the first one
	if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*\/sds\/[^\"]*)\"[^>]*>/",$body,$match)) {
		$result["default_safety_sheet"]="";
		$result["default_safety_sheet_url"]="-".$self["urls"]["server"].htmlspecialchars_decode($match[1]);
		$result["default_safety_sheet_by"]=$self["name"];
	}
	
	preg_match_all("/(?ims)<div.*?<\/div>/",$body,$manyLines,PREG_PATTERN_ORDER);
	$manyLines=$manyLines[0];
	for ($b=0;$b<count($manyLines);$b++) {
		// <div class="product_list_header"><div class="catalog_number">07-1655</div>
		if (strpos($manyLines[$b],"catalog_number")!==FALSE) {
			$result["catNo"]=fixTags($manyLines[$b]);
			break;
		}
	}
	
	preg_match_all("/(?ims)<span.*?<\/span>/",$body,$manyLines,PREG_PATTERN_ORDER);
	$manyLines=$manyLines[0];
	//~ print_r($manyLines);die();
	
	for ($b=0;$b<count($manyLines);$b++) {
		// <span id="header_description">4-(Phenylamino)-2-(phenylimino)-3-pentene, min. 98% NacNac</span>
		if (strpos($manyLines[$b],"header_description")!==FALSE) {
			cutRange($manyLines[$b],"header_description");
			$result["molecule_names_array"][]=fixTags("<\"".$manyLines[$b]); // fake complete tag
		}
	}
	preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$manyLines,PREG_PATTERN_ORDER);
	$manyLines=$manyLines[0];
	//~ print_r($manyLines);die();
	
	for ($b=0;$b<count($manyLines);$b++) {
		preg_match_all("/(?ims)<td.*?<\/td>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
		$cells=$cells[0];
		//~ print_r($cells);die();
		$name=fixTags($cells[0]);
		$value=fixTags($cells[1]);
		switch ($name) {
			case "Chemical Name:":
				$result["molecule_names_array"][]=$value;
			break;
			case "Synonym:":
				$synonyms=explode(", ",$value);
				$result["molecule_names_array"]=arr_merge($result["molecule_names_array"],$synonyms);
			break;
			case "Product Number:":
				if (empty($catNo)) {
					$result["catNo"]=$value;
				}
			break;
			case "CAS Number:":
			case "CAS Registry Number:":
				$result["cas_nr"]=$value;
			break;
			case "Molecular Formula:":
				$result["emp_formula"]=$value;
			break;
			case "Specific Gravity:":
				$result["density_20"]=getNumber($value);
			break;
			case "Molecular Weight:":
				$result["mw"]=getNumber($value);
			break;
			case "Melting Point:":
				if (strpos($value,"C")!==FALSE) { // exclude Fahrenheit
					$result["mp_high"]=getNumber($value);
				}
			break;
			case "Boiling Point:":
				if (strpos($value,"C")!==FALSE) { // exclude Fahrenheit
					list($temp,$press)=explode("/",$value,2);
					$result["bp_high"]=getNumber($temp);
					if (trim($press)!="") {
						$result["bp_press"]=getNumber($press);
						if (strpos($press,"mm")!==FALSE) {
							$result["press_unit"]="torr";
						}
					}
					else {
						$result["bp_press"]=1;
						$result["press_unit"]="bar";
					}
				}
			break;
			case "Flash Point:":
				if (strpos($value,"C")!==FALSE) { // exclude Fahrenheit
					$number=getNumber($value);
					if (is_numeric($number)) {
						$result["molecule_property"][]=array("class" => "FP", "source" => $code, "value_high" => $number, "unit" => "°C");
					}
				}
			break;
			case "Autoignition Temperature:":
				if (strpos($value,"C")!==FALSE) { // exclude Fahrenheit
					$number=getNumber($value);
					if (is_numeric($number)) {
						$result["molecule_property"][]=array("class" => "Autoign_temp", "source" => $code, "value_high" => $number, "unit" => "°C");
					}
				}
			break;
			case "Extinguishing Medium:":
				$result["molecule_property"][]=array("class" => "extinguishant", "source" => $code, "conditions" => $value);
			break;
		}
	}
	
	$result["supplierCode"]=$code;
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=@$response->getBody();
	
	if (strpos($body,"returned 0 results.")!==FALSE) {
		return $noResults;
	}
	elseif (strpos($body,"You searched for")===FALSE) { // 1 hit
		$results[0]=$self["procDetail"]($response);
		extendMoleculeNames($results[0]);
		$results[0]["name"]=$results[0]["molecule_name"];
		$results[0]["supplierCode"]=$code;
	}
	else {
		cutRange($body,"<div class=\"search_feedback\">","<div id=\"footer\">");
		
		preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$manyLines,PREG_PATTERN_ORDER);
		$manyLines=$manyLines[0];
		//~ print_r($manyLines);die();
		$results=array();
		
		for ($b=0;$b<count($manyLines);$b++) {
			if (stripos($manyLines[$b],"class=\"structure\"")===FALSE) {
				continue;
			}
			preg_match_all("/(?ims)<td.*?<\/td>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			if (!count($cells)) { // column heads use <th ...>
				continue;
			}
			//~ print_r($cells);die();
			$results[]=array("name" => fixTags($cells[2]), "catNo" =>fixTags($cells[0]), "supplierCode" => $code, );
		}
	}
	return $results;
'),
"getBestHit" => create_function('& $hitlist,$name=NULL','
	if (count($hitlist)>0) {
		return 0;
	}
'),
// custom
"getData" => create_function('& $pageStr,$preStr','
	preg_match("/(?ims)<tr>[\s|\n|\r]*<td[^>]*>[\s|\n|\r]*<b>".$preStr."<\/b>[\s|\n|\r]*<\/td>[\s|\n|\r]*<td[^>]*>[\s|\n|\r]*([^>]+)[\s|\n|\r]*<\/td>[\s|\n|\r]*<\/tr>/",$pageStr,$result);
	return fixHtml($result[1]);
') 
);
$GLOBALS["suppliers"][$code]["init"]();
//~ $suppliers[$code]["init"]();
?>