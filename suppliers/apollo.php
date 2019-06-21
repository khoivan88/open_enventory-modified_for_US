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
$GLOBALS["code"]="apollo";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "Apollo", 
	"logo" => "logo_apollo.png", 
	"height" => 85, 
	"vendor" => true, 
	"hasPriceList" => 2, 
	"testCas" => array("67-64-1" => array(
			array("acetone"),
		)
	),
	"excludeTests" => array("emp_formula"), 
	
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="http://www.apolloscientific.co.uk"; // startPage
	$suppliers[$code]["urls"]["search"]=$urls["server"]."/chemical_results.php";
	$suppliers[$code]["urls"]["detail"]=$urls["server"]."/display_item.php?id=";
	$suppliers[$code]["urls"]["startPage"]=$urls["server"];
'),
"getPostParams" => create_function('$searchText','
	return array(
		"product_group" => "%", 
		"qstext" => $searchText, 
		"search_fieldname" => "header_search"
	);
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="post";
	$retval["action"]=$urls["search"];
	$fields=$self["getPostParams"]($query_obj["vals"][0][0]);
	
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
	
	$body=@$response->getBody();
	return $self["procDetail"]($response,$catNo);
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	$fields=$self["getPostParams"]($searchText);
	$my_http_options=$default_http_options;
	$response=@oe_http_post_fields($urls["search"],$fields,array(),$my_http_options);
	
	if ($response==FALSE) {
		return $noConnection;
	}
	return $self["procHitlist"]($response);
'),
"procDetail" => create_function('& $response,$catNo=""',getFunctionHeader().'
	$body=@$response->getBody();
	
	$result=array();
	$result["molecule_names_array"]=array();
	$result["molecule_property"]=array();
	$result["catNo"]=$catNo; // may be overwritten later
	
	if (preg_match("/(?ims)<td[^>]+class=\"textHeadLargeBlue\"[^>]*>(.*?)<\/td>/",$body,$match)) {
		$result["molecule_names_array"][]=fixTags($match[1]);
	}
	
	if (preg_match("/(?ims)<td[^>]+class=\"textHeadGreen\"[^>]+colspan=\"\d+\"[^>]*>(.*?)<\/td>/",$body,$match)) {
		$result["catNo"]=$catNo=fixTags($match[1]);
	}
	
	if (preg_match_all("/(?ims)<form[^>]+action=\"add_item_new.php\"[^>]*>.*?<\/form>/",$body,$entries,PREG_PATTERN_ORDER)) {
		$entries=$entries[0];
		foreach ($entries as $entry) {
			$lines=preg_split("/(?ims)<tr[^>]*>/",$entry);
			//~ print_r($lines);
			
			if (count($lines)<5) {
				continue;
			}
			
			list($amountText,$priceText)=explode("-",fixTags($lines[2]));
			if (isEmptyStr($amountText)) {
				continue;
			}
			
			list(,$amount,$amount_unit)=getRange($amountText);
			preg_match("/(?ims)([^\d]*)\(?(\-?[\d\.,]+)\)?/",fixTags($priceText),$price_data);
			
			$result["price"][]=array(
				"supplier" => $code, 
				"amount" => $amount, 
				"amount_unit" => strtolower($amount_unit), 
				"price" => $price_data[2]+0.0, 
				"currency" => fixCurrency($price_data[1]), 
				"catNo" => $catNo, 
				"beautifulCatNo" => $catNo, 
				"addInfo" => fixTags($lines[4])
			);
		}
	}
	
	if (preg_match_all("/(?ims)<span[^>]* class=\"textMainBlue\"[^>]*>(.*?)<\/span>(.*?)<\/td>/",$body,$match_cells,PREG_SET_ORDER)) foreach ($match_cells as $match_cell) {
		$name=fixTags($match_cell[1]);
		$rawValue=$match_cell[2];
		$value=fixTags($rawValue);
		
		if (isEmptyStr($name) || isEmptyStr($value)) {
			continue;
		}
		
		switch ($name) {
		case "Synonym(s):":
			$names=explode(", ",$value);
			if (count($names)) foreach($names as $name) {
				$result["molecule_names_array"][]=$name;
			}
		break;
		case "Boiling point (oC):":
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
		case "Material Safety Data Sheet:":
			if (preg_match("/(?ims)<a [^>]*href=\"([^\"]+)\"[^>]*>/",$rawValue,$href_match)) {
				$result["default_safety_sheet"]="";
				$result["default_safety_sheet_url"]="-".$self["urls"]["server"]."/".htmlspecialchars_decode($href_match[1]);
				$result["default_safety_sheet_by"]=$self["name"];
			}
		break;
		case "Formula weight:":
			$result["cas_nr"]=getNumber($value);
		break;
		case "Melting point (oC):":
			list($result["mp_low"],$result["mp_high"])=getRange($value);
		break;
		case "Flash point (oC):":
			$result["molecule_property"][]=array("class" => "FP", "source" => $code, "value_high" => getNumber($value), "unit" => "°C");
		break;
		default:
			// handle more complicated cases
			if (strpos($name,"CAS")!==FALSE) {
				$result["cas_nr"]=$value;
			}
		}
	}
	
	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	
	//~ var_dump($result);
	
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=utf8_decode(@$response->getBody());
	cutRange($body,"Now showing Page","BASKET");
	
	$results=array();
	if (preg_match_all("/(?ims)<td rowspan.*?<\/table>/",$body,$manyLines,PREG_PATTERN_ORDER)) {
		$manyLines=$manyLines[0];
		foreach ($manyLines as $line) {
			list($info, $amounts)=explode("<table",$line,2);
			preg_match_all("/(?ims)<td.*?<\/td>/",$info,$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			
			if (count($cells)>=2) {
				list($name, $casNr)=explode("</b>",$info,2);
				list($catNo, $name)=explode("<br>",$name,2);
				
				if (preg_match("/(?ims)<a [^>]*href=\"[^\"]+[&\?]id=(.*?)[&\"][^>]*>/",$name,$href_match)) {
					$catNo=fixTags($line_match[2]);
					$results[]=array(
						"name" => fixTags($name), 
						"beautifulCatNo" => $catNo, 
						"catNo" => $href_match[1], 
						"supplierCode" => $code, 
					);
				}
			}
		}
	}
	
	return $results;
'),
);
$GLOBALS["suppliers"][$code]["init"]();
?>