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
// Alfa
$GLOBALS["code"]="Alfa";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "Alfa Aesar", 
	"logo" => "logo_alfa_aesar.jpg", 
	"height" => 50, 
	"vendor" => true, 
	"excludeFields" => array("emp_formula"), // does not allow this search
	"search_types" => array(
		"cas_nr" => "CAS", 
		"molecule_name" => "PROD",
	),
"safety_sym_ghs_map" => array("Explosive" => "GHS01","Flammable" => "GHS02","Oxidizing" => "GHS03","Corrosive" => "GHS05","Toxic" => "GHS06","Harmful_Irritant" => "GHS07","Health_hazard" => "GHS08","Environment" => "GHS09"),
"search_types" => array("cas_nr" => "SEARCH_CHOICE_CAS", "name" => "SEARCH_CHOICE_ITEM_NUM"),

"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="https://www.alfa.com";
	$suppliers[$code]["urls"]["search"]=$urls["server"]."/en/search/?search-tab=product-search-container&type=";
	$suppliers[$code]["urls"]["detail"]=$urls["server"]."/en/catalog/";
	$suppliers[$code]["urls"]["msds"]=$urls["server"]."/en/catalog/sds/";
	$suppliers[$code]["urls"]["startPage"]=$urls["server"];
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="url";
	$retval["action"]=$suppliers[$code]["urls"]["search"].$suppliers[$code]["search_types"][ $query_obj["crits"][0] ]."&q=".$query_obj["vals"][0][0];
	
	return $retval;
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	return $urls["detail"].$catNo."/";
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
	$response=@oe_http_get($suppliers[$code]["urls"]["search"].$suppliers[$code]["search_types"][ $filter ]."&q=".$searchText,$my_http_options);
	
	if ($response==FALSE) {
		return $noConnection;
	}
	return $self["procHitlist"]($response);
'),
"procDetail" => create_function('& $response,$catNo=""',getFunctionHeader().'
	$body=$response->getBody();
	
	$result=array();
	$result["molecule_names_array"]=array();
	$result["molecule_property"]=array();
	$result["catNo"]=$catNo; // may be overwritten later
	
	if (preg_match("/(?ims)<h1[^>]*>(.*?)<\/span>(.*?)<\/h1>/",$body,$match)) {
		$result["catNo"]=fixTags($match[1]);
		$result["molecule_names_array"][]=fixTags($match[2]);
	}
	
	if (preg_match_all("/(?ims)<strong[^>]*>(.*?)<\/strong>\s*<\/div>\s*<div[^>]*>(.*?)<\/div>/",$body,$match_cells,PREG_SET_ORDER)) foreach ($match_cells as $match_cell) {
		$name=strtolower(fixTags($match_cell[1]));
		$value=fixTags($match_cell[2]);
		
		if (isEmptyStr($name) || isEmptyStr($value)) {
			continue;
		}
		
		switch ($name) {
		case "cas number":
			$result["cas_nr"]=trim($value,"[]");
		break;
		case "synonyms":
			$result["molecule_names_array"][]=$value;
		break;
		case "formula":
			$result["emp_formula"]=$value;
		break;
		case "formula weight":
			$result["mw"]=getNumber($value);
		break;
		case "density":
			$result["density_20"]=getNumber($value);
		break;
		case "refractive index":
			$result["n_20"]=getNumber($value);
		break;
		case "packing group":
			$result["molecule_property"][]=array("class" => "packing_group", "source" => $code, "conditions" => $value, );
		break;
		case "boiling point":
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
		case "melting point":
			list($result["mp_low"],$result["mp_high"])=getRange($value);
		break;
		}
	}
	
	if (preg_match_all("/(?ims)<strong[^>]*>(.*?)<\/strong>(.*?)<\/p>/",$body,$match_cells,PREG_SET_ORDER)) foreach ($match_cells as $match_cell) {
		$name=fixTags($match_cell[1]);
		
		switch ($name) {
		case "Hazard Statements:":
			$result["cas_nr"]=trim($value,"[]");
			$result["safety_h"]=trim(str_replace("H","",fixTags($match_cell[2])));
		break;
		case "Precautionary Statements:":
			$result["cas_nr"]=trim($value,"[]");
			$result["safety_p"]=trim(str_replace("P","",fixTags($match_cell[2])));
		break;
		}
	}
	
	$safety_sym=array();
	$safety_sym_ghs=array();
	preg_match_all("/(?ims)<img\s+src=\"\/static\/+images\/pictogram\/(\w+)\.gif\"/",$body,$match_pictogram,PREG_PATTERN_ORDER);
	$match_pictogram=$match_pictogram[1];
	foreach ($match_pictogram as $title) {
		if ($value=$self["safety_sym_ghs_map"][$title]) {
			$safety_sym_ghs[]=$value;
		}
	}
	$result["safety_sym_ghs"]=join(",",$safety_sym_ghs);
	
	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	
	// MSDS
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$response=oe_http_get($urls["msds"].$catNo,$my_http_options);
	if ($response) {
		$msds_html=$response->getBody();
		if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*\/msds\/[^\"]*USA[^\"]*.pdf)\"[^>]*>/",$msds_html,$match)) {
			$result["default_safety_sheet"]="";
			$result["default_safety_sheet_url"]="-".$self["urls"]["server"].htmlspecialchars_decode($match[1]);
			$result["default_safety_sheet_by"]=$self["name"];
		}
		if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*\/msds\/[^\"]*German[^\"]*.pdf)\"[^>]*>/",$msds_html,$match)) {
			$result["alt_default_safety_sheet"]="";
			$result["alt_default_safety_sheet_url"]="-".$self["urls"]["server"].htmlspecialchars_decode($match[1]);
			$result["alt_default_safety_sheet_by"]=$self["name"];
		}
	}
	
	//~ var_dump($result);
	
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=@$response->getBody();
	cutRange($body,"id=\"products\"","id=\"footer\"");
	//~ die($body);
	
	if (preg_match_all("/(?ims)<li.*?<\/li>/",$body,$manyLines,PREG_PATTERN_ORDER)) {
		$manyLines=$manyLines[0];
		foreach ($manyLines as $line) {
			preg_match_all("/(?ims)<div.*?<\/div>/",$line,$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			
			if (count($cells)>=4) {
				$cat_no=fixTags($cells[2]);
				$results[]=array(
					"name" => fixTags($cells[3]), 
					"beautifulCatNo" => $cat_no, 
					"catNo" => $cat_no, 
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
//~ $suppliers[$code]["init"]();
?>