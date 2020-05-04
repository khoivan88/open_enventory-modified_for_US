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
// Apollo
$GLOBALS["code"]="abcr";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "abcr", 
	"logo" => "logo_abcr.gif", 
	"height" => 85, 
	"vendor" => true, 
	"hasPriceList" => 3, 
	
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="https://www.abcr.de"; // startPage
	$suppliers[$code]["urls"]["search"]=$urls["server"]."/shop/en/catalogsearch/advanced/result/?limit=50&mode=extendedlist&";
	$suppliers[$code]["urls"]["detail"]=$urls["server"]."/shop/en/";
	$suppliers[$code]["urls"]["startPage"]=$urls["server"];
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="url";
	$retval["action"]=$self["getSearchURL"]($query_obj["vals"][0][0],$query_obj["crits"][0]);
	
	return $retval;
'),
"getSearchURL" => create_function('$searchText,$filter',getFunctionHeader().'
	$retval=$urls["search"];
	if ($filter=="cas_nr") {
		$retval.="cas=";
	}
	elseif ($filter=="emp_formula") {
		$retval.="sum_formula=";
	}
	else {
		$retval.="name=";
	}
	return $retval.$searchText."&referrer=enventory";
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	if (empty($catNo)) {
		return;
	}
	return $urls["detail"].$catNo."/?referrer=enventory";
'),
"getInfo" => create_function('$catNo',getFunctionHeader().'
	$url=$self["getDetailPageURL"]($catNo);
	if (empty($url)) {
		return $noConnection;
	}
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$my_http_options["cookies"]=array("abcrLang" => "en");
	$response=@oe_http_get($url,$my_http_options);
	if ($response==FALSE) {
		return $noConnection;
	}
	
	$body=@$response->getBody();
	return $self["procDetail"]($response,$catNo);
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	$my_http_options=$default_http_options;
	$my_http_options["cookies"]=array("abcrLang" => "en");
	$url=$self["getSearchURL"]($searchText,$filter);
	$response=@oe_http_get($url,$my_http_options);
	
	if ($response==FALSE) {
		return $noConnection;
	}
	return $self["procHitlist"]($response);
'),
"procDetail" => create_function('& $response,$catNo=""',getFunctionHeader().'
	$body=@$response->getBody();
	cutRange($body,"id=\"product_addtocart_form\"","class=\"contact\"");
	
	$result=array();
	$result["molecule_names_array"]=array();
	$result["molecule_property"]=array();
	$result["catNo"]=$catNo; // may be overwritten later
	
	if (preg_match("/(?ims)<h2[^>]*>(.*?)<\/h2>/",$body,$match)) {
		$result["molecule_names_array"][]=fixTags($match[1]);
	}
	
	if (preg_match_all("/(?ims)<a[^>]+class=\"hazard hazard-v2 (ghs\d+)\"[^>]*>/",$body,$match,PREG_PATTERN_ORDER)) {
		$result["safety_sym_ghs"]=strtoupper(implode(",",$match[1]));
	}
	
	if (preg_match_all("/(?ims)<li[^>]*>\s*<span[^>]*>(.*?)<\/span>\s*<span[^>]*>(.*?)<\/span>\s*<\/li>/",$body,$match_cells,PREG_SET_ORDER)) foreach ($match_cells as $match_cell) {
		$name=fixTags($match_cell[1]);
		$value=fixTags($match_cell[2]);
		
		if (isEmptyStr($name) || isEmptyStr($value)) {
			continue;
		}
		
		switch ($name) {
		case "Sum Formula":
			$result["emp_formula"]=$value;
		break;
		case "CAS":
			$result["cas_nr"]=$value;
		break;
		case "Product No.":
			$catNo=$value;
		break;
		case "Flash Point":
			if (!isEmptyStr($value)) {
				$result["molecule_property"][]=array("class" => "FP", "source" => $code, "value_high" => getNumber($value), "unit" => "°C");
			}
		break;
		case "Molecular Weight":
			$result["mw"]=getNumber($value);
		break;
		case "Density":
			$result["density_20"]=getNumber($value);
		break;
		case "Boiling Point":
			list($result["bp_low"],$result["bp_high"],$press)=getRange($value);
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
		break;
		case "Melting Point":
			list($result["mp_low"],$result["mp_high"])=getRange($value);
		break;
		case "Hazardous Statements":
			$result["safety_h"]=str_replace(array("H"," ",","),array("","","-"),$value);
		break;
		case "Precautionary Statements":
			$result["safety_p"]=str_replace(array("P"," ",","),array("","","-"),$value);
		break;
		}
	}
	
	if (preg_match("/(?ims)<table[^>]+id=\"product-type-data\"[^>]*>(.*?)<\/table>/",$body,$price_table_data)
		&& preg_match_all("/(?ims)<tr.*?<\/tr>/",$price_table_data[1],$lines,PREG_PATTERN_ORDER)) {
		$lines=$lines[0];
		foreach ($lines as $line) {
			preg_match_all("/(?ims)<td.*?<\/td>/",$line,$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			
			if (count($cells)<5) {
				continue;
			}
			
			$amountText=fixTags($cells[0]);
			if (isEmptyStr($amountText)) {
				continue;
			}
			
			list(,$amount,$amount_unit)=getRange($amountText);
			preg_match("/(?ims)([^\d]*)\(?(\-?[\d\.,]+)\)?/",fixTags($cells[1]),$price_data);
			
			$result["price"][]=array(
				"supplier" => $code, 
				"amount" => $amount, 
				"amount_unit" => strtolower($amount_unit), 
				"price" => $price_data[2]+0.0, 
				"currency" => fixCurrency($price_data[1]), 
				"beautifulCatNo" => $catNo, 
			);
		}
	}
	
	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	
	//~ var_dump($result);
	
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=utf8_decode(@$response->getBody());
	cutRange($body,"id=\"search-result-categories\"","class=\"footer-wrap\"");
	
	$results=array();
	if (preg_match_all("/(?ims)<div[^>]* class=\"list-item-content\"[^>]*>(.*?)<div[^>]* class=\"product-links\"[^>]*>/",$body,$manyLines,PREG_PATTERN_ORDER)) {
		$manyLines=$manyLines[1];
		foreach ($manyLines as $line) {
			if (preg_match("/(?ims)<h3[^>]*>(.*?)<\/h3>/",$line,$preg_data)
				&& preg_match("/(?ims)<a[^>]+href=[\'\"].*\/([^\/]+)\/[\'\"][^>]*>/",$preg_data[1],$link_match)) {
				
				// products
				if (preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$prod_matches,PREG_PATTERN_ORDER)) foreach ($prod_matches[0] as $prod_match) {
					preg_match_all("/(?ims)<td.*?<\/td>/",$prod_match,$cells,PREG_PATTERN_ORDER);
					$cells=$cells[0];
					if (count($cells)<4) {
						continue;
					}
					
					$catNo=fixTags($cells[0]);
					
					// prices
					$price=array();
					if (preg_match_all("/(?ims)<li[^>]*>(.*?)<\/li>/",$cells[3],$price_lines,PREG_PATTERN_ORDER)) foreach ($price_lines[0] as $price_line) {
						preg_match_all("/(?ims)<span[^>]*>(.*?)<\/span>/",$price_line,$price_cells,PREG_PATTERN_ORDER);
						$price_cells=$price_cells[0];
						if (count($price_cells)<3) {
							continue;
						}
						
						list(,$amount,$amount_unit)=getRange(fixTags($price_cells[2]));
						preg_match("/(?ims)([^\d]*)\(?(\-?[\d\.,]+)\)?/",fixTags($price_cells[1]),$price_data);
						$price[]=array(
							"supplier" => $code, 
							"amount" => $amount, 
							"amount_unit" => strtolower($amount_unit), 
							"price" => $price_data[2]+0.0, 
							"currency" => fixCurrency($price_data[1]), 
							"beautifulCatNo" => $catNo, 
						);
					}
					
					$results[]=array(
						"name" => fixTags($preg_data[1]), 
						"beautifulCatNo" => $catNo, 
						"catNo" => $link_match[1], 
						"supplierCode" => $code, 
						"price" => $price, 
					);
				}
			}
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