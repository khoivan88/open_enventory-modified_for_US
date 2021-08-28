<?php
/*
Copyright 2006-2009 Felix Rudolphi and Lukas Goossen
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
$GLOBALS["code"]="Sial";
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "Sigma-Aldrich"; 
	public $logo = "logo_SigmaAldrich.gif";
	public $height = 50;
	public $vendor = true; 
	public $hasPriceList = 0; 
	public $country_cookies = array(
		"country" => "US", 
		"language" => "en", 
		"SialLocaleDef" => "CountryCode~US|WebLang~-1|", 
		"cookienotify" => "2",
	);
	public $urls=array(
		"startPage" => "https://www.sigmaaldrich.com", // startPage
		"search_suffix" => "?focus=products&page=1&perPage=100&sort=relevance&term="
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["search"]=$this->urls["startPage"]."/US/en/search/";
		$this->urls["detail"]=$this->urls["startPage"]."/US/en/";
		$this->urls["startPage"]=$this->urls["server"];
    }
	
	public function requestResultList($query_obj) {
		$retval = array(	
			"method" => "url",
			"action" => $this->urls["search"].urlencode($query_obj["vals"][0][0]).$this->urls["search_suffix"].urlencode($query_obj["vals"][0][0])."&type="
		);
		if ($query_obj["crits"][0]=="cas_nr") {
			$retval["action"].="cas_number";
		}
		elseif ($query_obj["crits"][0]=="emp_formula") {
			$retval["action"].="mol_form";
		}
		else {
			$retval["action"].="product_name";
		}

		return $retval;
    }
	
	public function getDetailPageURL($catNo) {
		$splitCatNo=explode("/",$catNo,2);
		if (count($splitCatNo)>2) {
			$splitCatNo[0]=$splitCatNo[0]."/";
		}
		else {
			array_unshift($splitCatNo,"");
		}
		return $this->urls["detail"].$splitCatNo[0]."product/".$splitCatNo[1]."/".$splitCatNo[2]."?lang=en&region=US&referrer=enventory";
    }
	
	public function getInfo($catNo) {
		global $noConnection,$default_http_options;
		
		$url=$this->getDetailPageURL($catNo);
		if (empty($url)) {
			return $noConnection;
		}
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$my_http_options["cookies"]=$this->country_cookies;
		$response=oe_http_get($url,$my_http_options); // set country by cookie directly and read prices
		if ($response==FALSE) {
			return $noConnection;
		}

		return $this->procDetail($response,$catNo);
    }
	
	public function getHitlist($searchText,$filter,$mode="ct",$paramHash=array()) {
		global $noConnection,$default_http_options;
		
		$url=$this->urls["search"].urlencode($searchText).$this->urls["search_suffix"].urlencode($searchText)."&type=";
		if ($filter=="cas_nr") {
			$url.="cas_number";
		}
		elseif ($filter=="emp_formula") {
			$url.="mol_form";
		}
		else {
			$url.="product_name";
		}

		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($url,$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}

		return $this->procHitlist($response);
    }
	
	public function procDetail(& $response,$catNo="") {
		global $lang,$default_http_options;

		$body=html_entity_decode($response->getBody(),ENT_QUOTES,"UTF-8");
//		$data=array();
//		if (preg_match("/(?ims)<script id=\"__NEXT_DATA__\" type=\"application\/json\">(.*?)<\/script>/",$body,$json_data)) {
//			$data=json_decode($json_data[1], true);
//		}
		cutRange($body,"</header>","<nav");
		
		$result=array();
		$result["price"]=array();
		$result["molecule_property"]=array();

		// name
		if (preg_match("/(?ims)<h1.*?>(.*?)<\/h1>/",$body,$name_data)) {
			$result["molecule_names_array"]=array(fixTags($name_data[1]));
		}
		
		// name
		if (preg_match("/(?ims)<[^>]+id=\"product-number\"[^>]*>(.*?)<[^>]*>/",$body,$name_data)) {
			$catNo=fixTags($name_data[1]);
		}

//		if ($data) {
//			$subData=$data["props"]["pageProps"]["data"]["getProductDetail"];
//		}
		
		if (preg_match("/(?ims)Pictograms.*?(GHS\d.*?)<\//",$body,$ghs_match)) {
			$result["safety_sym_ghs"]=fixTags($ghs_match[1]);
		}
		
		//						1: tag																2: name			3: tag			4: value
		preg_match_all("/(?ims)<(div|h3)[^>]+class=\"[^\"]*MuiTypography-(?:caption|body)[^\"]*\"[^>]*>(.*?)<\/\\1>.*?<(p|span|a)[^>]*>(.*?)<\/\\3>/",$body,$manyNVPs,PREG_SET_ORDER);
		//~ print_r($manyNVPs);die();

		for ($b=0;$b<count($manyNVPs);$b++) {
			$name=strtolower(trim(fixTags($manyNVPs[$b][2]),": "));
			$value=fixTags($manyNVPs[$b][4]);

			if (startswith($name,"cas number")) {
				$result["cas_nr"]=$value;
			}
			elseif (startswith($name,"molecular weight")) {
				$result["mw"]=$value;
			}
			elseif (startswith($name,"empirical formula")) {
				$result["emp_formula"]=$value;
			}
			elseif (strpos($name,"synonym")!==FALSE) {
				$synonyms=explode(", ",$value);
				$result["molecule_names_array"]=arr_merge($result["molecule_names_array"],$synonyms);
			}
			elseif (strpos($name,"hazard codes")!==FALSE || strpos($name,"hazard symbols")!==FALSE) {
				$result["safety_sym"]=$value;
			}
			elseif (strpos($name,"symbol")!==FALSE || strpos($name,"pictogram")!==FALSE) {
				$result["safety_sym_ghs"]=$value;
			}
			elseif (strpos($name,"hazard statement")!==FALSE) {
				$result["safety_h"]=str_replace(array("H"," "),"",$value);
			}
			elseif (strpos($name,"precautionary statement")!==FALSE) {
				$result["safety_p"]=str_replace(array("P"," "),"",$value);
			}
			elseif (strpos($name,"risk statement")!==FALSE) {
				$result["safety_r"]=$value;
			}
			elseif (strpos($name,"safety statement")!==FALSE) {
				$result["safety_s"]=$value;
			}
			elseif ($name=="signal word") {
				$result["safety_text"]=$value;
			}
			elseif (strpos($name,"wgk germany")!==FALSE && $value != "nwg") {
				$result["safety_wgk"]= str_replace("WGK ", "", $value);
			}
			elseif (strpos($name,"refractive index")!==FALSE) {
				cutRange($value,"/D","",false);
				//~ $result["n_20"]=$next_text;
				$result["n_20"]=getNumber($value);
			}
			elseif ($name=="density") {
				//~ cutRange($next_text,"","g/mL",false);
				//~ $result["density_20"]=$next_text;
				$result["density_20"]=getNumber($value);
			}
			elseif ($name=="mp") { // too short
				list($result["mp_low"],$result["mp_high"])=getRange($value);
			}
			elseif ($name=="bp") { // too short
				list($result["bp_low"],$result["bp_high"],$press)=getRange($value);
				if (isEmptyStr($result["bp_high"])) {
					// do nothing
				}
				elseif (trim($press)!="") {
					$result["bp_press"]=getNumber($press);
					if (strpos($press,"mm")!==FALSE) {
						$result["press_unit"]="torr";
					}
					elseif (strpos($press,"hPa")!==FALSE) {
						$result["press_unit"]="mbar";
					}
				}
				else {
					$result["bp_press"]="1";
					$result["press_unit"]="bar";			
				}
			}
			elseif (strpos($name,"ec number")!==FALSE) {
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "EG_No", "source" => $this->code, "conditions" => $value);
				}
			}
			elseif (strpos($name,"ridadr")!==FALSE) {
				$result["molecule_property"][]=array("class" => "adr", "source" => $this->code, "conditions" => $value);
				// get packing group, after last comma
				$lastSlash=strrpos($value,"/");
				if ($lastSlash!==FALSE) {
					$result["molecule_property"][]=array("class" => "packing_group", "source" => $this->code, "conditions" => getNumber(substr($value,$lastSlash+1)), );
				}
			}
			elseif (strpos($name,"flash point(c)")!==FALSE) {
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => $value+0.0, "unit" => "°C");
				}
			}
			elseif (strpos($name,"vapor pressure")!==FALSE) {
				$value=str_replace(array("&#x00b0;"),array("°"),$value);
				$vap_press_data=explode(" ",$value,3);
				if (!isEmptyStr($vap_press_data[0]) && !isEmptyStr($vap_press_data[1])) {
					$result["molecule_property"][]=array("class" => "Vap_press", "source" => $this->code, "value_high" => $vap_press_data[0]+0.0, "unit" => $vap_press_data[1], "conditions" => $vap_press_data[2]);
				}
			}
			elseif (strpos($name,"expl. lim.")!==FALSE) { // nur obere Grenze
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "Ex_limits", "source" => $this->code, "value_high" => $value+0.0, "unit" => "Vol.-%");
				}
			}
		}

		$result["supplierCode"]=$this->code;
		$result["catNo"]=$catNo;
		return $result;
    }
	
	public function procHitlist(& $response) {
		$body=@$response->getBody();
		if (stripos($body,">Sorry, no results found")!==FALSE) { // the > is important, it is always in the Javascript "quoted"
			return $noResults;
		}
		
		if (preg_match("/(?ims)<script id=\"__NEXT_DATA__\" type=\"application\/json\">(.*?)<\/script>/",$body,$json_data)) {
			$data=json_decode($json_data[1], true);
			$subData=$data["props"]["apolloState"];
			//print_r($subData);
			if (is_array($subData)) foreach ($subData as $key => $subDataEntry) {
				if (!isEmptyStr($subDataEntry["name"]) && !isEmptyStr($subDataEntry["productKey"])) {
					$brandKey=$subDataEntry["brand"]["id"];

					$results[]=array(
						"name" => fixTags($subDataEntry["name"]), 
						"addInfo" => fixTags($subDataEntry["description"]), 
						"beautifulCatNo" => fixTags($subDataEntry["productNumber"]), 
						"catNo" => fixTags($subData[$brandKey]["key"]."/".$subDataEntry["productKey"]), 
						"supplierCode" => $this->code, 
					);
				}
			}
		}
		
		return $results;
    }
}
?>