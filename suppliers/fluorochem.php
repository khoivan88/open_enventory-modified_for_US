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
$GLOBALS["code"]="fluorochem";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "Fluorochem", 
	"logo" => "logo_fluorochem.png", 
	"height" => 85, 
	"vendor" => true, 
	"hasPriceList" => 2, 
	"testCas" => array("1489-53-8" => array(
			array("trifluorobenzene"),
		)
	),
	"excludeTests" => array("emp_formula"), 
	"search_type_codes" => array(
		"cas_nr" => "C",
		"molecule_name" => "N",
	),
	
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="https://www.fluorochem.co.uk"; // startPage
	$suppliers[$code]["urls"]["search"]=$urls["server"]."/Products/Search";
	$suppliers[$code]["urls"]["detail"]=$urls["server"]."/Products/Product?code=";
	$suppliers[$code]["urls"]["startPage"]=$urls["server"];
'),
"getPostParams" => create_function('$searchText,$crit',getFunctionHeader().'
	return array(
		//~ "chkShowPrices" => "true", 
		"lstSearchType" => $self["search_type_codes"][$crit],
		"txtSearchText" => $searchText, 
	);
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="post";
	$retval["action"]=$urls["search"];
	$fields=$self["getPostParams"]($query_obj["vals"][0][0],$query_obj["crits"][0]);
	
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
"getClauses" => create_function('$html,$type','
	$clauses=array();
	$rows=explode("<br",$html);
	if (is_array($rows)) foreach ($rows as $row) {
		$row=fixTags($row);
		if (cutRange($row,$type,":",false)
			&& !isEmptyStr($row)) {
			$clauses[]=$row;
		}
	}
	
	return str_replace(array(" ",$type,),"",implode("-",$clauses));
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
	$fields=$self["getPostParams"]($searchText,$filter);
	$my_http_options=$default_http_options;
	$response=@oe_http_post_fields($urls["search"],$fields,array(),$my_http_options);
	
	if ($response==FALSE) {
		return $noConnection;
	}
	return $self["procHitlist"]($response);
'),
"procDetail" => create_function('& $response,$catNo=""',getFunctionHeader().'
	$body=@$response->getBody();
	cutRange($body,"<span[^>]+class=\"pageHeader\"","class=\"clearer\"");
	
	$result=array();
	$result["molecule_names_array"]=array();
	$result["molecule_property"]=array();
	$result["catNo"]=$catNo; // may be overwritten later
	
	if (preg_match("/(?ims)<span[^>]+class=\"pageHeader\"[^>]*>(.*?)-(.*?)<\/span>/",$body,$match)) {
		$result["molecule_names_array"][]=fixTags($match[2]);
		$result["catNo"]=$catNo=fixTags($match[1]);
	}
	
	if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*\/DownloadSDS[^\"]*)\"[^>]*>/",$body,$msds)) {
		$result["default_safety_sheet"]="";
		$result["default_safety_sheet_url"]="-".$urls["server"].htmlspecialchars_decode($msds[1]);
		$result["default_safety_sheet_by"]=$self["name"];
	}
	
	if (preg_match("/(?ims)molfile:\s*\'\"([^\"]*)\"\'/",$body,$match)) {
		$result["molfile_blob"]=addPipes(str_replace("\\\n","\\n",$match[1]));
	}
	
	if (preg_match_all("/(?ims)<[^>]*src=\"\/Content\/CSS\/TransportPics\/(GHS[^\"\/]*)\.gif\"/",$body,$match,PREG_PATTERN_ORDER)) {
		$result["safety_sym_ghs"]=implode(",",$match[1]);
	}
	
	if (preg_match("/(?ims)<h2>Hazard Statements<\/h2>(.*?)<\/div>/",$body,$match)) {
		$result["safety_h"]=$self["getClauses"]($match[1],"H");
	}
	
	if (preg_match("/(?ims)<h2>Precautionary Statements<\/h2>(.*?)<\/div>/",$body,$match)) {
		$result["safety_p"]=$self["getClauses"]($match[1],"P");
	}
	
	if (preg_match("/(?ims)<table[^>]* id=\"tblPricing\"[^>]*>(.*?)<\/table>/",$body,$price_table_data)
		&& preg_match_all("/(?ims)<tr.*?<\/tr>/",$price_table_data[1],$lines,PREG_PATTERN_ORDER)) {
		$lines=$lines[0];
		foreach ($lines as $line) {
			preg_match_all("/(?ims)<td.*?<\/td>/",$line,$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			
			if (count($cells)<4) {
				continue;
			}
			
			list(,$amount,$amount_unit)=getRange(fixTags($cells[0]));
			list($currency,$price)=explode(" ",fixTags($cells[2]),2);
			
			$inStock=fixTags($cells[3]);
			if (is_numeric($inStock)) {
				$inStock.=" in stock";
			}
			
			$result["price"][]=array(
				"supplier" => $code, 
				"amount" => $amount, 
				"amount_unit" => strtolower($amount_unit), 
				"price" => $price+0.0, 
				"currency" => fixCurrency($currency), 
				"catNo" => $catNo, 
				"beautifulCatNo" => $catNo, 
				"addInfo" => $inStock,
			);
		}
	}
	
	if (preg_match_all("/(?ims)<th[^>]*>(.*?)<\/th>\s*<td[^>]*>(.*?)<\/td>/",$body,$match_cells,PREG_SET_ORDER)) foreach ($match_cells as $match_cell) {
		$name=fixTags($match_cell[1]);
		$value=fixTags($match_cell[2]);
		
		if (isEmptyStr($name) || isEmptyStr($value)) {
			continue;
		}
		
		switch ($name) {
		case "Molecular Formula":
			$result["emp_formula"]=$value;
		break;
		case "CAS Number":
			$result["cas_nr"]=$value;
		break;
		case "UN Number":
			$result["molecule_property"][]=array("class" => "UN_No", "source" => $code, "conditions" => $value);
		break;
		case "Packing Group":
			$result["molecule_property"][]=array("class" => "packing_group", "source" => $code, "conditions" => $value, );
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
		}
	}
	
	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	
	//~ var_dump($result);
	
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=utf8_decode(@$response->getBody());
	cutRange($body,"id=\"searchResults\"","id=\"loadingMessage\"");
	
	$results=array();
	if (preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$manyLines,PREG_PATTERN_ORDER)) {
		$manyLines=$manyLines[0];
		foreach ($manyLines as $line) {
			list($info, $amounts)=explode("<table",$line,2);
			preg_match_all("/(?ims)<td.*?<\/td>/",$info,$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			
			if (count($cells)>=3) {
				$catNo=fixTags($cells[0]);
				$results[]=array(
					"name" => fixTags($cells[1]), 
					"beautifulCatNo" => $catNo, 
					"catNo" => $catNo, 
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
