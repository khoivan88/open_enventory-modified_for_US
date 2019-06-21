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
$GLOBALS["code"]="Oakwood";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "Oakwood", 
	"logo" => "oakwood.jpg", 
	"height" => 40, 
	"vendor" => true, 
	"hasPriceList" => 2, 
	
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="http://www.oakwoodchemical.com";
	$suppliers[$code]["urls"]["search"]=$urls["server"]."/ProductsList.aspx?txtSearch=";
	$suppliers[$code]["urls"]["detail"]=$urls["server"]."/ProductsList.aspx?CategoryID=";
	$suppliers[$code]["urls"]["startPage"]=$urls["server"];
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="url";
	$retval["action"]=$suppliers[$code]["urls"]["search"].$query_obj["vals"][0][0];
	
	return $retval;
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	list($catID,$productNumber)=explode("/",$catNo,2);
	return $urls["detail"].$catID."&txtSearch=".$productNumber."&referrer=enventory";
'),
"getInfo" => create_function('$catNo',getFunctionHeader().'
	$url=$self["getDetailPageURL"]($catNo);
	if (empty($url)) {
		return $noConnection;
	}
	$my_http_options=$default_http_options;
	$response=oe_http_get($url,$my_http_options);
	if ($response==FALSE) {
		return $noConnection;
	}

	return $self["procDetail"]($response,$catNo);
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$response=oe_http_get($urls["search"].urlencode($searchText),$my_http_options);
	if ($response==FALSE) {
		return $noConnection;
	}

	return $self["procHitlist"]($response);
'),
"procDetail" => create_function('& $response,$catNo=""',getFunctionHeader().'
	$body=@$response->getBody();
	cutRange($body,"id=\"content\"","class=\"pageLinks\"");
	$body=preg_replace(array("/(?ims)<!--.*?-->/","/(?ims)<script.*?<\/script>/","/(?ims)<style.*?<\/style>/"),"",$body);
	
	$result=array();
	$result["molecule_names_array"]=array();
	$result["molecule_property"]=array();
	$result["catNo"]=$catNo; // may be overwritten later
	
	// name is very difficult to get, font-size 18pt is really bad selector
	
	if (preg_match_all("/(?ims)<td[^>]*>\s*<b>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>/",$body,$match_cells,PREG_SET_ORDER)) foreach ($match_cells as $match_cell) {
		$name=fixTags($match_cell[1]);
		if (strpos($match_cell[2],"</label>")!==FALSE) {
			// cut away invisible label
			list(,$match_cell[2])=explode("</label>",$match_cell[2],2);
		}
		$value=fixTags($match_cell[2]);
		
		if (isEmptyStr($name)) {
			continue;
		}
		
		// now some special ones
		switch ($name) {
		case "Pictograms:":
			if (preg_match_all("/(?ims)<img[^>]+src=.*?(GHS\d+)\./",$match_cell[2],$match_pictogram,PREG_PATTERN_ORDER)) {
				$result["safety_sym_ghs"]=join(",",$match_pictogram[1]);
			}
		break;
		}
		
		if (isEmptyStr($value)) {
			continue;
		}
		
		switch ($name) {
		case "CAS Number:":
			$result["cas_nr"]=$value;
		break;
		case "Molecular Formula:":
			$result["emp_formula"]=$value;
		break;
		case "Molecular Weight:":
			$result["mw"]=getNumber($value);
		break;
		case "Mp:":
			list($result["mp_low"],$result["mp_high"])=getRange($value);
		break;
		case "Bp:":
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
		case "Density:":
			$result["density_20"]=getNumber($value);
		break;
		case "Refractive Index:":
			$result["n_20"]=getNumber($value);
		break;
		case "Signal Word:":
			$result["safety_text"]=$value;
		break;
		case "Hazard Statements:":
			$result["safety_h"]=str_replace(array("H",", "," "),array("","-",""),$value);
		break;
		case "Precautionary Statements:":
			$result["safety_p"]=str_replace(array("P",", "," "),array("","-",""),$value);
		break;
		case "UN#:":
			$result["molecule_property"][]=array("class" => "UN_No", "source" => $code, "conditions" => $value);
		break;
		case "Packing Group:":
			$result["molecule_property"][]=array("class" => "packing_group", "source" => $code, "conditions" => $value, );
		break;
		case "Flash point:":
			$value=getNumber($value);
			if (!isEmptyStr($value)) {
				$result["molecule_property"][]=array("class" => "FP", "source" => $code, "value_high" => $value+0.0, "unit" => "°C");
			}
		break;
		case "Risk Statements:":
			$result["safety_r"]=$value;
		break;
		case "Safety Statements:":
			$result["safety_s"]=$value;
		break;
		case "MSDS:":
			if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*)\"[^>]*>/",$match_cell[2],$msds)) {
				$result["default_safety_sheet"]="";
				$result["default_safety_sheet_url"]="-".$urls["server"]."/".htmlspecialchars_decode($msds[1]);
				$result["default_safety_sheet_by"]=$self["name"];
			}
		break;
		}
	}
	
	// prices
	if (preg_match("/(?ims)Item #(.*)<\/table>/",$body,$match)) {
		// parse lines
		preg_match_all("/(?ims)<tr.*?<\/tr>/",$match[1],$manyLines,PREG_PATTERN_ORDER);
		$manyLines=$manyLines[0];
		//~ var_dump($manyLines);die();
		for ($b=0;$b<count($manyLines);$b++) {
			preg_match_all("/(?ims)<td.*?<\/td>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			
			if (count($cells)<3) {
				continue;
			}
			
			$entryCatNo=fixTags($cells[1]);
			list(,$amount)=explode("-",$entryCatNo,2);
			list(,$amount,$amount_unit)=getRange($amount);
			
			list($normalPrice,$discountPrice)=explode("</div>",$cells[2],2);
			list(,$currency,$price)=$self["procPrice"]($discountPrice);
			if (!($price+0.0)) {
				list(,$currency,$price)=$self["procPrice"]($normalPrice);
			}
			
			$result["price"][]=array(
				"supplier" => $code, 
				"catNo" => $entryCatNo,
				"amount" => $amount, 
				"amount_unit" => $amount_unit, 
				"price" => $price, 
				"currency" => fixCurrency($currency), 
			);
		}
	}
	
	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	
	//~ var_dump($result);
	
	return $result;
'),
"procPrice" => create_function('$priceText','
	$priceText=fixTags($priceText);
	if (strpos($priceText,":")!==FALSE) {
		list(,$priceText)=explode(":",$priceText,2);
	}
	preg_match("/(?ims)^(.*?)([\d\.\,\+\-]+)/",trim($priceText),$match);
	//var_dump($match);die($priceText);
	return $match;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=@$response->getBody();
	if (stripos($body,"0 items found")!==FALSE) { // no results at all
		return $noResults;
	}
	cutRange($body,"class=\"hLinks2\"","class=\"pageLinks\"");
	$body=preg_replace(array("/(?ims)<!--.*?-->/","/(?ims)<script.*?<\/script>/","/(?ims)<style.*?<\/style>/"),"",$body);
	//~ die($body);
	
	if (preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$manyLines,PREG_PATTERN_ORDER)) {
		$manyLines=$manyLines[0];
		foreach ($manyLines as $line) {
			preg_match_all("/(?ims)<td.*?<\/td>/",$line,$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			//~ var_dump($cells);
			
			if (count($cells)>=3 && preg_match("/(?ims)<a[^>]+href=[\'\"].*?ProductsList\.aspx\?CategoryID=([^\'\"&]+)&.*?txtSearch=([^\'\"&]+)[^\'\"]*[\'\"][^>]*>/",$cells[1],$match)) {
				$results[]=array(
					"name" => fixTags($cells[2]), 
					"beautifulCatNo" => fixTags($cells[1]), 
					"catNo" => $match[1]."/".$match[2], 
					"supplierCode" => $code, 
				);
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