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
// Sial
$GLOBALS["code"]="itw";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "Applichem / PanReac / ITW", 
	"logo" => "logo_itw.png", 
	"height" => 94, 
	"vendor" => true, 
	"hasPriceList" => 2, 
	"catalogHierarchy" => 1,
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["startPage"]="https://www.itwreagents.com"; // startPage
	$suppliers[$code]["urls"]["search"]=$urls["startPage"]."/germany/en/product/search?term=";
	$suppliers[$code]["urls"]["detail"]=$urls["startPage"]."/germany/en/product/_/";
	$suppliers[$code]["urls"]["msds"]=$urls["startPage"]."/germany/en/sds_ajax?productId=";
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="url";
	$retval["action"]=$suppliers[$code]["urls"]["search"].$query_obj["vals"][0][0];
	
	return $retval;
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	return $urls["detail"].$catNo."?referrer=enventory";
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
	$url=$urls["search"].urlencode($searchText);
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
	
	$result=array();
	$result["price"]=array();
	$result["molecule_property"]=array();
	
	// name
	if (preg_match("/(?ims)<h1.*?>(.*?)<\/h1>/",$body,$name_data)) {
		$result["molecule_names_array"]=array(fixTags($name_data[1]));
	}
	if (preg_match("/(?ims)<h2[^>]+class=\"subtit\"[^>]*>(.*?)<\/h2>/",$body,$name_data)) {
		$synonyms=explode(", ",fixTags($name_data[1]));
		foreach ($synonyms as $synonym) {
			$result["molecule_names_array"][]=$synonym;
		}
	}

	preg_match_all("/(?ims)<dt[^>]*>(.*?)<\/dt>\s*<dd[^>]*>(.*?)<\/dd>/",$body,$manyNVPs,PREG_SET_ORDER);
	//~ print_r($manyNVPs);die();
	
	for ($b=0;$b<count($manyNVPs);$b++) {
		$rawValue=$manyNVPs[$b][2];
		$value=fixTags($rawValue);
		if (isEmptyStr($value)) {
			continue;
		}
		$name=strtolower(trim(fixTags($manyNVPs[$b][1]),":"));
		
		if ($name=="cas") {
			$result["cas_nr"]=$value;
		} elseif ($name=="code") {
			$catNo=$value;
		} elseif ($name=="molecular formula") {
			$result["emp_formula"]=$value;
		} elseif ($name=="molar mass") {
			$result["mw"]=$value;
		} elseif ($name=="melting point") {
			list($result["mp_low"],$result["mp_high"])=getRange($value);
		} elseif ($name=="boiling point") {
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
		} elseif ($name=="density") {
			$result["density_20"]=getNumber($value);
		} elseif ($name=="refractive index") {
			$result["n_20"]=getNumber(str_replace("20/D", "",$value));
		} elseif ($name=="wgk") {
			$result["safety_wgk"]=getNumber($value);
		} elseif ($name=="un") {
			$result["molecule_property"][]=array("class" => "UN_No", "source" => $code, "conditions" => $value);
		} elseif ($name=="class/pg") {
			$result["molecule_property"][]=array("class" => "packing_group", "source" => $code, "conditions" => $value);
		} elseif ($name=="adr") {
			$result["molecule_property"][]=array("class" => "adr", "source" => $code, "conditions" => $value);
		} elseif ($name=="imdg") {
			$result["molecule_property"][]=array("class" => "imdg", "source" => $code, "conditions" => $value);
		} elseif ($name=="iata") {
			$result["molecule_property"][]=array("class" => "iata", "source" => $code, "conditions" => $value);
		} elseif ($name=="signal word") {
			$result["safety_text"]=$value;
		} elseif ($name=="ghs symbols") {
			$result["safety_sym_ghs"]=fixTags(str_replace(array(" ","<br>"),array("",","),$rawValue));
		} elseif ($name=="h phrases") {
			$result["safety_h"]=fixTags(str_replace(array("H"," ","<br>"),array("","","-"),$rawValue));
		} elseif ($name=="p phrases") {
			$result["safety_p"]=fixTags(str_replace(array("P"," ","<br>"),array("","","-"),$rawValue));
		}
	}
	
	// MSDS
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$response=oe_http_get($urls["msds"].urlencode($catNo),$my_http_options);
	$msds_html=html_entity_decode(@$response->getBody(),ENT_QUOTES,"UTF-8");
	if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*_en.pdf)\"[^>]*>/",$msds_html,$match)) {
		$result["default_safety_sheet"]="";
		$result["default_safety_sheet_url"]="-".$self["urls"]["server"].htmlspecialchars_decode($match[1]);
		$result["default_safety_sheet_by"]=$self["name"];
	}
	if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*_de.pdf)\"[^>]*>/",$msds_html,$match)) {
		$result["alt_default_safety_sheet"]="";
		$result["alt_default_safety_sheet_url"]="-".$self["urls"]["server"].htmlspecialchars_decode($match[1]);
		$result["alt_default_safety_sheet_by"]=$self["name"];
	}
	
	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	return $result;
'),
"getPrices" => create_function('$catNo',getFunctionHeader().'
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
	$body=html_entity_decode(@$response->getBody(),ENT_QUOTES,"UTF-8");
	$result=array(
		"price" => array()
	);
	
	if (preg_match("/(?ims)<section[^>]*id=\"packs\"[^>]*>(.*)<\/section>/",$body,$match)) {
		preg_match_all("/(?ims)<tr.*?<\/tr>/",$match[1],$manyLines,PREG_PATTERN_ORDER);
		$manyLines=$manyLines[0];
		//var_dump($manyLines);die();
		
		$headlines=array();
		$col_catNo=1;
		$col_amount=2;
		$col_price=3;
		$col_discount_price=4;
		$min_cells=4;
		for ($b=0;$b<count($manyLines);$b++) {
			preg_match_all("/(?ims)<t[dh].*?<\/t[dh]>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			
			if (strpos($manyLines[$b],"</th>")!==FALSE) {
				foreach ($cells as $i => $cell) {
					$cell=strtolower(fixTags($cell));
					if ($cell=="code") {
						$col_catNo=$i;
					}
					elseif ($cell=="packaging size") {
						$col_amount=$i;
					}
					elseif ($cell=="price per unit") {
						$col_price=$i;
					}
					elseif ($cell=="box price per unit") {
						$col_discount_price=$i;
					} else {
						continue;
					}
					$min_cells=max($min_cells,$i);
				}
				continue;
			}
			if (count($cells)<=$min_cells) {
				continue;
			}
			
			$catNo=fixTags(array_pop(explode("</span>",$cells[ $col_catNo ])));
			list(,$amount,$amount_unit)=getRange(fixTags(array_pop(explode("</span>",$cells[ $col_amount ]))));
			list(,$price,$currency)=getRange(fixTags(array_pop(explode("</span>",$cells[ $col_price ]))));
			$amount=getNumber($amount);
			
			$result["price"][]=array(
				"supplier" => $code, 
				"catNo" => $catNo,
				"beautifulCatNo" => $catNo, 
				"amount" => $amount, 
				"amount_unit" => $amount_unit, 
				"price" => getNumber($price), 
				"currency" => fixCurrency($currency),
			);
			
			$discount_info=array_pop(explode("</span>",$cells[ $col_discount_price ],2));
			list($discount_price, $numberPackages)=explode("<span",$discount_info,2);
			$numberPackages=getNumber($numberPackages);
			if ($numberPackages) {
				list(,$price,$currency)=getRange(fixTags($discount_price));
				$result["price"][]=array(
					"supplier" => $code, 
					"catNo" => $catNo,
					"beautifulCatNo" => $catNo, 
					"amount" => $numberPackages*$amount, 
					"amount_unit" => $amount_unit, 
					"price" => $numberPackages*getNumber($price), 
					"currency" => fixCurrency($currency),
				);
			}
			//~ var_dump($result["price"]);die();
		}
	}
	
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=@$response->getBody();
	
	$results=array();
	if (strpos($body,"results for")!==FALSE) {
		if (preg_match("/(?ims)<section [^>]*class=\"results-list\".*<\/section>/",$body,$cut)) {
			$body=$cut[0];
		}
		preg_match_all("/(?ims)<h(\d) [^>]*class=\"prod-name\"[^>]*>(.*?)<\/span>(.*?)<\/a>.*?<\/h\\\\1>/",$body,$data_matches,PREG_SET_ORDER);
		for ($b=0;$b<count($data_matches);$b++) {
			$catNo=fixTags($data_matches[$b][2]);
			$results[]=array(
				"name" => fixTags($data_matches[$b][3]),
				"beautifulCatNo" => $catNo,
				"catNo" => $catNo,
				"supplierCode" => $code, 
			);
		}
	}
	else {
		$results[0]=$self["procDetail"]($response);
		extendMoleculeNames($results[0]);
		$results[0]["name"]=$results[0]["molecule_name"];
		$results[0]["beautifulCatNo"]=$results[0]["catNo"];
		$results[0]["supplierCode"]=$code;
	}
	//print_r($results);
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