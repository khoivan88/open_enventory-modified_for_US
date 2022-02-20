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
	public $hasPriceList = 0; // price on request too complicated with materialIds...
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
		$this->urls["api"]=$this->urls["startPage"]."/api";
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
	
	public function getHeader($cookies, $opName) {
		$header = array(
			"x-gql-country" => $this->country_cookies["country"],
			"x-gql-language" => "en",
			"x-gql-operation-name" => $opName,
			"x-gql-store" => "sial",
			"x-gql-access-token" => $cookies["accessToken"]
		);
		return $header;
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
		
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($this->urls["search"], $my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}
		$cookies= oe_get_cookies($response);
		
		$postBody="{\"operationName\":\"ProductSearch\",\"variables\":{\"searchTerm\":".fixStr($searchText).",\"page\":1,\"group\":\"substance\",\"selectedFacets\":[],\"sort\":\"relevance\",\"type\":\"";
		if ($filter=="cas_nr") {
			$postBody.="CAS_NUMBER";
		}
		elseif ($filter=="emp_formula") {
			$postBody.="MOL_FORM";
		}
		else {
			$postBody.="PRODUCT";
		}
		$postBody.='"},"query":"query ProductSearch($searchTerm: String, $page: Int!, $sort: Sort, $group: ProductSearchGroup, $selectedFacets: [FacetInput!], $type: ProductSearchType, $catalogType: CatalogType, $orgId: String, $region: String, $facetSet: [String]) {\\n  getProductSearchResults(input: {searchTerm: $searchTerm, pagination: {page: $page}, sort: $sort, group: $group, facets: $selectedFacets, type: $type, catalogType: $catalogType, orgId: $orgId, region: $region, facetSet: $facetSet}) {\\n    ...ProductSearchFields\\n    __typename\\n  }\\n}\\n\\nfragment ProductSearchFields on ProductSearchResults {\\n  metadata {\\n    itemCount\\n    setsCount\\n    page\\n    perPage\\n    numPages\\n    redirect\\n    __typename\\n  }\\n  items {\\n    ... on Substance {\\n      ...SubstanceFields\\n      __typename\\n    }\\n    ... on Product {\\n      ...SubstanceProductFields\\n      __typename\\n    }\\n    __typename\\n  }\\n  facets {\\n    key\\n    numToDisplay\\n    isHidden\\n    isCollapsed\\n    multiSelect\\n    prefix\\n    options {\\n      value\\n      count\\n      __typename\\n    }\\n    __typename\\n  }\\n  didYouMeanTerms {\\n    term\\n    count\\n    __typename\\n  }\\n  __typename\\n}\\n\\nfragment SubstanceFields on Substance {\\n  _id\\n  id\\n  name\\n  synonyms\\n  empiricalFormula\\n  linearFormula\\n  molecularWeight\\n  aliases {\\n    key\\n    label\\n    value\\n    __typename\\n  }\\n  images {\\n    sequence\\n    altText\\n    smallUrl\\n    mediumUrl\\n    largeUrl\\n    __typename\\n  }\\n  casNumber\\n  products {\\n    ...SubstanceProductFields\\n    __typename\\n  }\\n  match_fields\\n  __typename\\n}\\n\\nfragment SubstanceProductFields on Product {\\n  name\\n  productNumber\\n  productKey\\n  cardCategory\\n  cardAttribute {\\n    citationCount\\n    application\\n    __typename\\n  }\\n  attributes {\\n    key\\n    label\\n    values\\n    __typename\\n  }\\n  speciesReactivity\\n  brand {\\n    key\\n    erpKey\\n    name\\n    color\\n    __typename\\n  }\\n  images {\\n    altText\\n    smallUrl\\n    mediumUrl\\n    largeUrl\\n    __typename\\n  }\\n  description\\n  sdsLanguages\\n  sdsPnoKey\\n  similarity\\n  paMessage\\n  features\\n  catalogId\\n  materialIds\\n  __typename\\n}\\n"}';
		
		$my_http_options["header"]= $this->getHeader($cookies, "ProductSearch");
		$response=oe_http_post_fields($this->urls["api"],$postBody,null,$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}

		return $this->procHitlist($response);
    }
	
	public function procDetail(& $response,$catNo="") {
		global $lang,$default_http_options;

		$body=html_entity_decode($response->getBody(),ENT_QUOTES,"UTF-8");
//		if (preg_match("/(?ims)<script id=\"__NEXT_DATA__\" type=\"application\/json\">(.*?)<\/script>/",$body,$json_data)) {
//			$data=json_decode($json_data[1], true);
//			if ($data) { // only for MSDS, which is too complicated
//			}
//		}
		cutRange($body,"</header>","<nav");
		
		$result=array();
		$result["price"]=array();
		$result["molecule_property"]=array();

		// name
		if (preg_match("/(?ims)<h1.*?>(.*?)<\/h1>/",$body,$name_data)) {
			$result["molecule_names_array"]=array(fixTags($name_data[1]));
		}
		
		// catNo
		if (preg_match("/(?ims)<[^>]+id=\"product-number\"[^>]*>(.*?)<[^>]*>/",$body,$name_data)) {
			$catNo=fixTags($name_data[1]);
		}

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
					$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => getNumber($value), "unit" => "°C");
				}
			}
			elseif (strpos($name,"vapor pressure")!==FALSE) {
				$value=str_replace(array("&#x00b0;"),array("°"),$value);
				$vap_press_data=explode(" ",$value,3);
				if (!isEmptyStr($vap_press_data[0]) && !isEmptyStr($vap_press_data[1])) {
					$result["molecule_property"][]=array("class" => "Vap_press", "source" => $this->code, "value_high" => getNumber($vap_press_data[0]), "unit" => $vap_press_data[1], "conditions" => $vap_press_data[2]);
				}
			}
			elseif (strpos($name,"expl. lim.")!==FALSE) { // nur obere Grenze
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "Ex_limits", "source" => $this->code, "value_high" => getNumber($value), "unit" => "Vol.-%");
				}
			}
		}

		$result["supplierCode"]=$this->code;
		$result["catNo"]=$catNo;
		return $result;
    }
	
	public function procHitlist(& $response) {
		$body=$response->getBody();
		//die($body);
		
		$results=array();
		$data=json_decode($body, true);
		$subData=$data["data"]["getProductSearchResults"]["items"];
		//print_r($subData);
		if (is_array($subData)) foreach ($subData as $key => $subDataEntry) {
			$products=$subDataEntry["products"];
			
			foreach ($products as $product) {
				$brandKey=$product["brand"]["key"];

				$results[]=array(
					"name" => fixTags($product["name"]), 
					"addInfo" => fixTags($product["description"]), 
					"beautifulCatNo" => fixTags($product["productNumber"]), 
					"catNo" => fixTags($brandKey."/".$product["productKey"]), 
					"supplierCode" => $this->code, 
				);
			}
		}
		
		return $results;
	}
}
?>