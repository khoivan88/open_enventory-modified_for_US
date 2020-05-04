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
// carbolution
$GLOBALS["code"]="carbolution";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "carbolution", 
	"logo" => "logo_carbolution.png", 
	"height" => 40, 
	"vendor" => true, 
	"hasPriceList" => 3, 
	"stripChars" => "\t\r\n \0\x09", 
	"testCas" => array("18162-48-6" => array(
			array("butyldimethylchlorsilan"),
		)
	),
	"testEmpFormula" => array("C6H15ClSi" => array(
			array("butyldimethylchlorsilan"),
		)
	),

"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="https://www.carbolution.de"; // startPage
	$suppliers[$code]["urls"]["search"]=$urls["server"]."/advanced_search_result.php?keywords=";
	$suppliers[$code]["urls"]["detail"]=$urls["server"]."/product_info.php?products_id=";
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
	return $self["procDetail"]($response,$catNo);
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	$url=$urls["search"];
	if ($filter=="cas_nr" || $mode=="ex" || $mode=="sw") {
		$url.=">";
	}
	$url.=urlencode($searchText);
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$response=oe_http_get($url,$my_http_options);
	if ($response==FALSE) {
		return $noConnection;
	}

	return $self["procHitlist"]($response);
'),
"getClauses" => create_function('$html,$type','
	$clauses=array();
	$rows=explode("</div>",$html);
	if (is_array($rows)) foreach ($rows as $row) {
		$row=fixTags($row);
		if (cutRange($row,$type," ",false)
			&& !isEmptyStr($row)) {
			$clauses[]=$row;
		}
	}
	
	return str_replace(array(" ",$type,),"",implode("-",$clauses));
'),
"procDetail" => create_function('& $response,$catNo=""',getFunctionHeader().'
	$body=utf8_decode(@$response->getBody());
	if (preg_match("/(?ims)<div [^>]*class=\"[^\"]*shop-items[^\"]*\".*<footer/",$body,$cut)) {
		$body=$cut[0];
	}
	$body=str_replace(array("&nbsp;","&ndash;","&#8211;"),array(" ","-","-"),$body);
	
	$result=array();
	$result["molecule_names_array"]=array();
	$result["molecule_property"]=array();
	$result["catNo"]=$catNo; // may be overwritten later
	
	preg_match("/(?ims)<h3[^>]*>(.*?)<\/h3>/",$body,$name_data);
	$result["molecule_names_array"]=array(fixTags($name_data[1]));
	
	if (preg_match("/(?ims)<p>Art\.Nr\.:\s*(.*?)<\/p>/",$body,$match)) {
		$result["catNo"]=fixTags($match[1]);
	}
	
	preg_match_all("/(?ims)<tr[^>]*>(.*?)<\/tr>/",$body,$lines,PREG_PATTERN_ORDER);
	$lines=$lines[1];
	if (is_array($lines)) foreach ($lines as $line) {
		preg_match_all("/(?ims)<td.*?<\/td>/",$line,$cells,PREG_PATTERN_ORDER);
		$cells=$cells[0];
		
		if (count($cells)<2) {
			continue;
		}
		
		$name=fixTags($cells[0]);
		$value=fixTags($cells[1]);
		
		if ($name=="CAS") {
			$result["cas_nr"]=$value;
		}
		elseif ($name=="Summenformel") {
			$result["emp_formula"]=$value;
		}
		elseif (strpos($name,"lmasse")!==FALSE) {
			$result["mw"]=$value;
		}
		elseif ($name=="Dichte") {
			$result["density_20"]=getNumber($value);
		}
		elseif ($name=="Schmelzpunkt") {
			list($result["mp_low"],$result["mp_high"])=getRange($value);
		}
		elseif ($name=="Siedepunkt") {
			list($result["bp_low"],$result["bp_high"])=getRange($value);
		}
		elseif ($name=="Signalwort") {
			$result["safety_text"]=$value;
		}
		elseif ($name=="Sicherheitsdatenblatt") {
			// only in German
			if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*)\"[^>]*>/",$cells[1],$msds)) {
				$result["alt_default_safety_sheet"]="";
				$result["alt_default_safety_sheet_url"]="-".htmlspecialchars_decode($msds[1]);
				$result["alt_default_safety_sheet_by"]=$self["name"];
			}
		}
		elseif ($name=="Piktogramm") {
			preg_match_all("/(?ims)alt=\"([^\"]*)\"/",$cells[1],$htmlEntries,PREG_PATTERN_ORDER);
			$result["safety_sym_ghs"]=@join(",",$htmlEntries[1]);
		}
		elseif ($name=="Gefahrenhinweise") {
			$result["safety_h"]=$self["getClauses"]($cells[1], "H");
		}
		elseif ($name=="Sicherheitshinweise") {
			$result["safety_p"]=$self["getClauses"]($cells[1], "P");
		}
	}
	
	// prices
	
	
	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	$body=utf8_decode(@$response->getBody());
	if (strpos($body,"Leider haben wir das Produkt")!==FALSE) {
		return $noResults;
	}
	cutRange($body,"<div class=\"shop-items","<footer");
//~ 	die($body);
	
	$result=array();
	preg_match_all("/(?ims)<a[^>]*href=\".*?products_id=([^&\"]*).*?\"[^>]*>.*?<img[^>]+alt=\"([^\"]*)\".*?<h(\d)[^>]*>(.*?)<\/h\\\\3>(.*?)<a/",$body,$htmlEntries,PREG_SET_ORDER);
//~ 	print_r($htmlEntries);die();
	for ($b=0;$b<count($htmlEntries);$b++) {
		preg_match_all("/(?ims)>Produktnummer: (.*?) Menge: (.*?) Preis:  (.*?) Lieferzeit: (.*?)</",$htmlEntries[$b][5],$price_matches,PREG_SET_ORDER);
		$prices=array();
		$catNo=fixTags($price_match[1]);
		for ($c=0;$c<count($price_matches);$c++) {
			$price_match=$price_matches[$c];
			list(,$amount,$amount_unit)=getRange(fixTags($price_match[2]));
			list(,$price,$currency)=getRange(fixTags($price_match[3]));
			$prices[]=array(
				"supplierCode" => $code, 
				"amount" => $amount, 
				"amount_unit" => strtolower($amount_unit), 
				"price" => $price+0.0, 
				"currency" => fixCurrency($currency), 
				"beautifulCatNo" => $catNo, 
				"addInfo" => fixTags($price_match[4]), 
			);
		}
		$result[$b]=array(
			"supplierCode" => $code, 
			"name" => fixTags($htmlEntries[$b][2]),
			"catNo" => fixTags($htmlEntries[$b][1]),
			"beautifulCatNo" => $catNo, 
			"price" => $prices
		);
	}
//~ 	print_r($result);
	return $result;
'),
"getBestHit" => create_function('& $hitlist,$name=NULL','
	if (count($hitlist)>0) {
		return 0;
	}
')
);
$GLOBALS["suppliers"][$code]["init"]();
?>
