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
$GLOBALS["code"]="cayman";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "Cayman Chemical", 
	"logo" => "logo_cayman.png", 
	"height" => 85, 
	"vendor" => true, 
	//~ "hasPriceList" => 2, 
	"testCas" => array("168968-01-2" => array(
			array("mdma"),
		)
	),
	"excludeTests" => array("emp_formula"), 
	
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="https://www.caymanchem.com"; // startPage
	$suppliers[$code]["urls"]["search"]=$urls["server"]."/solr/cchProduct/select?qf=catalogNum%5E2000%20exactname%5E5000%20exactSynonyms%5E4000%20edgename%5E4000%20synonymsPlain%5E2000%20formalNameDelimited%5E1500%20vendorItemNumber%5E4000%20casNumber%5E10000%20name%5E1500%20ngram_name%5E1000%20delimited_name%5E1500%20tagline%5E0.01%20keyInformation%5E0.01%20keywords%5E200%20inchi%5E20000%20inchiKey%5E20000%20smiles%5E20000%20ngram_synonym%5E400%20ngram_general%5E0.01&rows=100&defType=edismax&q.op=AND&enableElevation=true&facet=true&facet.field=newProduct&facet.field=raptas&facet.limit=100000&facet.mincount=1&wt=json&&start=0&bust=uhrdtm2owmh&version=2.2&q=";
	$suppliers[$code]["urls"]["detail"]=$urls["server"]."/product/";
	$suppliers[$code]["urls"]["detail_data"]=$urls["server"]."/solr/cchProduct/select?wt=json&fq=europeOnly:false&q=catalogNum:\"";
	$suppliers[$code]["urls"]["price"]=$urls["server"]."/solr/cchProductVariant/select?wt=json&rows=100000&sort=amount%20asc&q=catalogNum:(";
	$suppliers[$code]["urls"]["startPage"]=$urls["server"];
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="url";
	$retval["action"]=$suppliers[$code]["urls"]["search"].$query_obj["vals"][0][0]."&qf=";
	if ($query_obj["crits"][0]=="cas_nr") {
		$retval["action"].="cas_no";
	}
	else {
		$retval["action"].=$suppliers[$code]["urls"]["search_suffix"];
	}
	
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
	
	$body=@$response->getBody();
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
	$body=@$response->getBody();
	//~ die($body);
	
	$result=array();
	$result["molecule_names_array"]=array();
	$result["molecule_property"]=array();
	
	if (preg_match("/(?ims)<div[^>]* id=\"productHeaderName\"[^>]*>(.*?)<\/div>/",$body,$match)) {
		$result["molecule_names_array"][]=fixTags($match[1]);
	}
	
	if (preg_match("/(?ims)<p[^>]* class=\"[^\"]*productHeaderDetail[^\"]*\"[^>]*>.*?Item &#x2116;\s*(.*?)<\/p>/",$body,$match)) {
		$catNo=fixTags($match[1]);
	}
	
	// MSDS
	if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*msdss[^\"]*)\"[^>]*>/",$body,$match)) {
		$result["default_safety_sheet"]="";
		$result["default_safety_sheet_url"]="-".htmlspecialchars_decode($match[1]);
		$result["default_safety_sheet_by"]=$self["name"];
	}
	
	if (preg_match_all("/(?ims)<tr[^>]*>\s*<t[hd][^>]*>(.*?)<\/t[hd]>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/",$body,$matches,PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$name=fixTags($match[1]);
			$raw_value=$match[2];
			$value=fixTags($raw_value);
			
			if (isEmptyStr($name) || isEmptyStr($value)) {
				continue;
			}
			
			switch ($name) {
			case "Synonyms":
				preg_match_all("/(?ims)<li[^>]*>(.*?)<\/li>/",$raw_value,$li_data,PREG_PATTERN_ORDER);
				foreach ($li_data[1] as $raw_value) {
					$result["molecule_names_array"][]=fixTags($raw_value);
				}
			break;
			case "Formal Name":
				$result["molecule_names_array"][]=$value;
			break;
			case "CAS Number":
				$result["cas_nr"]=$value;
			break;
			case "Molecular Formula":
				$result["emp_formula"]=$value;
			break;
			case "Formula Weight":
				$result["mw"]=getNumber($value);
			break;
			}
		}
	}
	
	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	
	//~ var_dump($result);
	
	return $result;
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
	//$body=iconv("UTF-8","UTF-8//IGNORE",utf8_encode($response->getBody()));
	//$body=utf8_decode($response->getBody());
	$body=preg_replace("/(?ims)\"formalNameDelimited\".*?\"isForensic\"/","\"isForensic\"",$response->getBody()); // get rid of Unicode characters with "
	$json=json_decode_nice($body);
	//var_dump($json);var_dump($body);die(json_last_error_msg());
	
	$results=array();
	if (is_array($json)) foreach ($json["response"]["docs"] as $doc) {
		$results[]=array(
			"name" => fixTags($doc["name"]), 
			"cas_nr" => $doc["casNumber"], 
			"beautifulCatNo" => $doc["catalog_num"], 
			"catNo" => $doc["catalog_num"], 
			"supplierCode" => $code, 
		);
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