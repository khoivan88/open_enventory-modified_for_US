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
// Carl Roth
$GLOBALS["code"]="carlroth";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "Carl Roth", 
	"logo" => "carl_roth.jpg", 
	"height" => 85, 
	"vendor" => true, 
	"hasPriceList" => 2, 
	"catalogHierarchy" => 1,
	"testCas" => array("67-64-1" => array(
			array("acetone"),
		)
	),
	"testEmpFormula" => array("C7H8" => array(
			array("toluene"),
		)
	),
	
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="https://www.carlroth.com"; // startPage
	$suppliers[$code]["urls"]["base_url"]=$urls["server"]."/de/en/";
	$suppliers[$code]["urls"]["search"]=$urls["base_url"]."search?text=";
	$suppliers[$code]["urls"]["detail"]=$urls["base_url"];
	$suppliers[$code]["urls"]["startPage"]=$urls["server"];
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
	$body=utf8_decode(@$response->getBody());
	
	$result=array(
		"price" => array()
	);
	
	if (preg_match("/(?ims)<table[^>]*class=\"bestelltabelle\"[^>]*>(.*)<\/table>/",$body,$match)) {
		preg_match_all("/(?ims)<tr.*?<\/tr>/",$match[1],$manyLines,PREG_PATTERN_ORDER);
		$manyLines=$manyLines[0];
		//~ var_dump($manyLines);die();
		
		$headlines=array();
		$num_idx=2;
		$price_idx=4;
		$product_idx=-1;
		$purity_idx=-1;
		
		for ($b=0;$b<count($manyLines);$b++) {
			preg_match_all("/(?ims)<t[dh].*?<\/t[dh]>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			
			if (count($cells)<5) {
				continue;
			}
			$beautifulCatNo=fixTags($cells[1]);
			if ($beautifulCatNo=="Article" || $beautifulCatNo=="Item number") {
				foreach ($cells as $i => $cell) {
					$cell=fixTags($cell);
					if (startswith($cell,"Pack Qty.")) {
						$num_idx=$i;
					}
					elseif ($cell=="Price") {
						$price_idx=$i;
					}
					elseif ($cell=="Product") {
						$product_idx=$i;
					}
					elseif ($cell=="Purity") {
						$purity_idx=$i;
					}
					$headlines[]=$cell;
				}
			}
			else {
				list(,$amount,$amount_unit)=getRange(fixTags($cells[ $num_idx ]));
				preg_match("/(?ims)([^\d]*)\(?(\-?[\d\.,]+)\)?/",fixTags($cells[ $price_idx ]),$price_match);
				
				$infoTexts=array();
				if ($product_idx>=0) {
					for ($i=$product_idx;$i<$price_idx;$i++) {
						$value=fixTags($cells[$i]);
						if (!isEmptyStr($value)) {
							$infoTexts[]=$headlines[$i].": ".$value;
						}
					}
				}
				
				$result["price"][]=array(
					"supplier" => $code, 
					"catNo" => $catNo,
					"beautifulCatNo" => $beautifulCatNo, 
					"amount" => $amount, 
					"amount_unit" => $amount_unit, 
					"price" => getNumber($price_match[2]), 
					"currency" => fixCurrency($price_match[1]), 
					"addInfo" => join("; ",$infoTexts),
				);
				//~ var_dump($result["price"]);die();
			}
		}
	}
	
	return $result;
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
	return $urls["detail"].$catNo."?referrer=enventory";
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
	
	return $self["procDetail"]($response,$catNo);
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$response=@oe_http_get($urls["search"].urlencode($searchText),$my_http_options);
	
	if ($response==FALSE) {
		return $noConnection;
	}
	return $self["procHitlist"]($response);
'),
"procDetail" => create_function('& $response,$catNo=""',getFunctionHeader().'
	$body=utf8_decode(@$response->getBody());
	cutRange($body,"<div id=\"content\"","<footer class=\"wrap\">");
	$body=str_replace(array("\t","\n","\r"),"",$body);
	
	$result=array();
	$result["molecule_names_array"]=array();
	$result["molecule_property"]=array();
	$result["catNo"]=$catNo; // may be overwritten later
	
	// MSDS
	if (preg_match("/(?ims)<a[^>]*href=\"(\/downloads\/sdb\/en\/[^\"]*)\"[^>]*>/",$body,$match)) {
		$result["default_safety_sheet"]="";
		$result["default_safety_sheet_url"]="-".$self["urls"]["server"].htmlspecialchars_decode($match[1]);
		$result["default_safety_sheet_by"]=$self["name"];
	}
	if (preg_match("/(?ims)<a[^>]*href=\"(\/downloads\/sdb\/de\/[^\"]*)\"[^>]*>/",$body,$match)) {
		$result["alt_default_safety_sheet"]="";
		$result["alt_default_safety_sheet_url"]="-".$self["urls"]["server"].htmlspecialchars_decode($match[1]);
		$result["alt_default_safety_sheet_by"]=$self["name"];
	}
	
	if (preg_match("/(?ims)<h2[^>]*>(.*?)<\/h2>/",$body,$match)) {
		$result["molecule_names_array"][]=fixTags($match[1]);
	}
	
	$safety_sym_ghs=array();
	$safety_sym_ghs_dict=array("h4d/h8f/10452954972190" => "GHS02","h31/ha6/10452955168798" => "GHS03","h0b/hae/8808430993438" => "GHS05","hc7/hc5/10452955299870" => "GHS06",
		"h03/h1a/10452955234334" => "GHS07","h9e/h49/10452954906654" => "GHS08","hf0/h95/8808431517726" => "GHS09");
	
	// match symbols
	if (preg_match_all("/(?ims)\/medias\/sys_master\/root\/(.*?).png/",$body,$matches_ghs_sym,PREG_SET_ORDER)) {
		foreach ($matches_ghs_sym as $match_ghs_sym) {
			$temp=$safety_sym_ghs_dict[ $match_ghs_sym[1] ];
			if ($temp) {
				$safety_sym_ghs[]=$temp;
			}
		}
	}
	$result["safety_sym_ghs"]=@join(",",$safety_sym_ghs);
	
	// match H clauses
	if (preg_match("/(?ims)<div[^>]*class=\"icons-gefahren\"[^>]*>.*?<h\d[^>]*>(Danger|Warning|)\s*(\S*)<\/h\d>.*?<\/div>/",$body,$preg_data)) {
		$result["safety_text"]=fixTags($preg_data[1]);
		$result["safety_h"]=fixTags(str_replace("H","",$preg_data[2]));
	}
	
	if (preg_match("/(?ims)Empirical formula (.*?)<br/",$body,$preg_data)) {
		$result["emp_formula"]=fixTags($preg_data[1]);
	}
	
	if (preg_match("/(?ims)Molar mass \(M\)\s*(.*?)<br/",$body,$preg_data)) {
		$result["mw"]=getNumber(fixTags($preg_data[1]));
	}
	
	if (preg_match("/(?ims)Density \(D\)\s*(.*?)<br/",$body,$preg_data)) {
		$result["density_20"]=getNumber(fixTags($preg_data[1]));
	}
	
	if (preg_match("/(?ims)Melting point \(mp\)\s*(.*?)\D*C<br/",$body,$preg_data)) {
		list($result["mp_low"],$result["mp_high"])=getRange(fixTags($preg_data[1]));
	}
	
	if (preg_match("/(?ims)Boiling point \(bp\)\s*(.*?)\D*C<br/",$body,$preg_data)) {
		list($result["bp_low"],$result["bp_high"])=getRange(fixTags($preg_data[1]));
		$result["bp_press"]=1;
		$result["press_unit"]="bar";
	}
	
	if (preg_match("/(?ims)Flash point \(flp\)\s*(.*?)\D*C/",$body,$preg_data)) {
		$result["molecule_property"][]=array("class" => "FP", "source" => $code, "value_high" => getNumber(fixTags($preg_data[1])), "unit" => "°C");
	}
	
	if (preg_match("/(?ims)UN-Nr\. (.*?)<br/",$body,$preg_data)) {
		$result["molecule_property"][]=array("class" => "UN_No", "source" => $code, "conditions" => fixTags($preg_data[1]));
	}
	
	if (preg_match("/(?ims)WGK (.*?)<br/",$body,$preg_data)) {
		$result["safety_wgk"]=fixTags($preg_data[1]);
	}
	
	if (preg_match("/(?ims)CAS.N[ro]\.\s*\[?(.*?)\]?<br/",$body,$preg_data)) {
		$result["cas_nr"]=fixTags($preg_data[1]);
	}
	
	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	
	//~ var_dump($result);
	
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	//~ var_dump($response);die();
	$body=@$response->getBody(); // utf8_decode(
	cutRange($body,"<div id=\"content\"","<footer class=\"wrap\">");
	$body=str_replace(array("\t","\n","\r"),"",$body);
	
	$results=array();
	if (stripos($body,"You searched for")!==FALSE) {
		if (preg_match_all("/(?ims)<article.*?<a [^>]*href=\".*?\/en\/([^\"]*)\".*?>(.*?)<\/h\d>(.*?)<\/article>/",$body,$manyLines,PREG_SET_ORDER)) {
			foreach ($manyLines as $line) {
				$results[]=array(
					"name" => fixTags($line[2]), 
					"beautifulCatNo" => " ", 
					"catNo" => fixTags($line[1]), 
					"supplierCode" => $code, 
				);
			}
		}
	} elseif (preg_match("/(?ims)".preg_quote($urls["base_url"],"/")."(.*)\$/",$response->getEffectiveUrl(),$preg_data)) {
		$results[0]=$self["procDetail"]($response);
		extendMoleculeNames($results[0]);
		//~ var_dump($results[0]);die();
		$results[0]=array_merge($results[0],array("supplierCode" => $code, "beautifulCatNo" => " ", "catNo" => $preg_data[1], ) );
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