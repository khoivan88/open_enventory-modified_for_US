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
$GLOBALS["code"]="biosolve";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "Biosolve", 
	"logo" => "logo_biosolve.gif", 
	"height" => 85, 
	"vendor" => true, 
	"hasPriceList" => 2, 
	"testCas" => array("108-88-3" => array(
			array("toluene"),
		)
	),
	"excludeTests" => array("emp_formula"), 
	
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="https://shop.biosolve-chemicals.eu"; // startPage
	$suppliers[$code]["urls"]["search"]=$urls["server"]."/search.php?search=";
	$suppliers[$code]["urls"]["detail"]=$urls["server"]."/detail.php?id=";
	$suppliers[$code]["urls"]["startPage"]=$urls["server"];
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="url";
	$retval["action"]=$suppliers[$code]["urls"]["search"].$query_obj["vals"][0][0];
	
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
	$my_http_options=$default_http_options;
	$my_http_options["timeout"]=35;
	$response=@oe_http_get($urls["search"].$searchText,$my_http_options);
	
	if ($response==FALSE) {
		return $noConnection;
	}
	return $self["procHitlist"]($response);
'),
"procDetail" => create_function('& $response,$catNo=""',getFunctionHeader().'
	$body=@$response->getBody();
	if (preg_match("/(?ims)id=\"bandeau\".*class=\"row-fluid\"(.*)id=\"footer\"/",$body,$cut)) {
		$body=$cut[1];
	}
	
	$result=array();
	$result["molecule_names_array"]=array();
	$result["molecule_property"]=array();
	$result["catNo"]=$catNo; // may be overwritten later
	
	if (preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$lines,PREG_PATTERN_ORDER)) {
		$lines=$lines[0];
		foreach ($lines as $line) {
			preg_match_all("/(?ims)<t[dh][^>]*>(.*?)<\/t[dh]>/",$line,$cells,PREG_PATTERN_ORDER);
			$cells=$cells[1];
			
			$lenCells=count($cells);
			if ($lenCells==1) {
				list($name,$value)=explode("</em>",$cells[0],2);
				$name=fixTags($name);
				$value=fixTags(trim($value,":"));
			}
			elseif ($lenCells==2) {
				$name=fixTags($cells[0]);
				$value=fixTags($cells[1]);
			}
			elseif ($lenCells==3) {
				// packages, no prices
				$amountText=fixTags($cells[1]);
				$factor=1;
				
				// split away factor if any
				if (strpos($amountText,"x")!==FALSE) {
					list($factor,$amountText)=explode("x",$amountText,2);
				}
				list(,$amount,$amount_unit)=getRange($amountText);
				
				$result["price"][]=array(
					"supplier" => $code, 
					"amount" => $amount, 
					"amount_unit" => strtolower($amount_unit), 
					"beautifulCatNo" => fixTags($cells[0]), 
					"addInfo" => ($factor && $factor!=1?$factor." x ":"").fixTags($cells[2]),
				);
				continue;
			}
			
			switch ($name) {
			case "Synonym":
				$names=explode(", ",$value);
				if (is_array($names)) foreach($names as $name) {
					$result["molecule_names_array"][]=$name;
				}
			break;
			case "Formula":
				$result["emp_formula"]=$value;
			break;
			case "CAS":
				$result["cas_nr"]=$value;
			break;
			case "EC":
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "EG_No", "source" => $code, "conditions" => $value);
				}
			break;
			case "UN":
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "UN_No", "source" => $code, "conditions" => $value);
				}
			break;
			case "M":
				$result["mw"]=getNumber($value);
			break;
			case "D":
				$result["density_20"]=getNumber($value);
			break;
			case "b.p.":
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
			case "m.p.":
				list($result["mp_low"],$result["mp_high"])=getRange($value);
			break;
			}
		}
	}
	
	// http://shop.biosolve-chemicals.eu/upload/produit/cat/0233_IT.pdf
	if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*\/upload\/produit\/cat\/[^\"]*_EU\.pdf)[^\"]*\"[^>]*>/",$body,$msds)) {
		$result["default_safety_sheet"]="";
		$result["default_safety_sheet_url"]="-".$urls["server"].htmlspecialchars_decode($msds[1]);
		$result["default_safety_sheet_by"]=$self["name"];
	}
	
	// H/P
	if (preg_match_all("/(?ims)<li[^>]*>(.*?)<\/li>/",$body,$match,PREG_PATTERN_ORDER)) {
		foreach ($match[1] as $inner) {
			if (strpos($inner,"Danger :")!==FALSE) {
				$inner=fixTags($inner);
				if (strpos($inner,"P:")!==FALSE) {
					list($inner,$result["safety_p"])=explode("P:",$inner,2);
				}
				if (strpos($inner,"H:")!==FALSE) {
					list($inner,$result["safety_h"])=explode("H:",$inner,2);
				}
				break;
			}
		}
	}
	
	// safety sym
	if (preg_match_all("/(?ims)<img [^>]*src=\"[^\"]*\/pictos\/([^\"]*)\.png\"[^>]*>/",$body,$match,PREG_PATTERN_ORDER)) {
		$safety_sym_ghs_dict=array(
			"picto-explosion" => "GHS01",
			"picto-flammable" => "GHS02",
			"picto-oxydant" => "GHS03",
			"picto-pressure_gaz" => "GHS04",
			"picto-corrosive" => "GHS05",
			"picto-highly_toxic" => "GHS06",
			"picto-danger" => "GHS07",
			"picto-respiratory" => "GHS08",
			"picto-environment" => "GHS09",
		);
		$result["safety_sym_ghs"]=array();
		foreach ($match[1] as $key) {
			$value=$safety_sym_ghs_dict[$key];
			if (!isEmptyStr($value)) {
				$result["safety_sym_ghs"][]=$value;
			}
		}
		$result["safety_sym_ghs"]=@join(",",$result["safety_sym_ghs"]);
	}
	
	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	
	//~ var_dump($result);
	
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=utf8_decode(@$response->getBody());
	cutRange($body,"id=\"search\"","id=\"footer\"");
	
	$results=array();
	if (preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$manyLines,PREG_PATTERN_ORDER)) {
		$manyLines=$manyLines[0];
		foreach ($manyLines as $line) {
			preg_match_all("/(?ims)<td.*?<\/td>/",$line,$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			
			if (count($cells)>=2
				&& preg_match("/(?ims)<a [^>]*href=\"[^\"]+[&\?]id=(.*?)[&\"][^>]*>(.*)/",$cells[1],$href_match)) {
				list($href_match[2],$href_match[3])=explode("<small>",$href_match[2],2);
				$catNo=fixTags($cells[0]);
				$results[]=array(
					"name" => fixTags($href_match[2]), 
					"addInfo" => fixTags($href_match[3]), 
					"beautifulCatNo" => fixTags($cells[0]), 
					"catNo" => $href_match[1], 
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