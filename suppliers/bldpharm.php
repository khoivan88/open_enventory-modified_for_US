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
$GLOBALS["code"]="bldpharm";
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "BLDpharm"; 
	public $logo = "BLD_logo.png";
	public $height = 50;
	public $vendor = true; 
	public $hasPriceList = 2; 
	public $alwaysProcDetail = true;
	public $testSearch = "acetone";
	public $testCas = array("395-23-3" => array(
			array("phenyl(4-(trifluoromethyl)phenyl)methanol"),
		)
	);
	public $testEmpFormula = array("C14H11F3O" => array(
			array("phenyl(4-(trifluoromethyl)phenyl)methanol"),
		)
	);
	public $country_cookies = array(
		"country" => "US", 
		"language" => "en", 
		"SialLocaleDef" => "CountryCode~US|WebLang~-1|", 
		"cookienotify" => "2",
	);
	public $urls=array(
		"startPage" => "https://www.bldpharm.com", // startPage
		"search_suffix" => "?focus=products&page=1&perPage=100&sort=relevance&term="
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["search"]=$this->urls["startPage"]."/search/Search.html?keyword=";
		$this->urls["search_api"]=$this->urls["startPage"]."/webapi/v1/searchquery?params=";
		$this->urls["detail"]=$this->urls["startPage"]."/products/";
		$this->urls["price"]=$this->urls["startPage"]."/webapi/v1/product/productPriceInfo/";
    }
	
	public function requestResultList($query_obj) {
		$retval = array(	
			"method" => "url",
			"action" => $this->urls["search"].urlencode($query_obj["vals"][0][0])
		);

		return $retval;
    }
	
	public function getDetailPageURL($catNo) {
		if (empty($catNo)) {
			return;
		}
		return $this->urls["detail"].$catNo."?referrer=enventory";
	}
	public function getHistlistURL($searchText) {
		return $this->urls["search_api"].base64_encode(json_encode(array("keyword" => $searchText, "_xsrf" => "")));
	}
	
	public function getInfo($catNo,$loadData=true) {
		global $noConnection,$default_http_options;
		
		$url=$this->getDetailPageURL($catNo);
		if (empty($url)) {
			return $noConnection;
		}
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=@oe_http_get($url,$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}
		return $this->procDetail($response,$catNo,$loadData);
	}
	
	public function getHitlist($searchText,$filter,$mode="ct",$paramHash=array()) {
		global $noConnection,$default_http_options;
		
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($this->getHistlistURL($searchText), $my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}

		return $this->procHitlist($response);
    }
	
	public function procDetail(& $response,$catNo="",$loadData=true) {
		global $default_http_options;
		
		$body=@$response->getBody();
		cutRange($body,"<h2","<div class=\"foot_top_bg\"");

		$result=array(
			"price" => array()
		);

		$name_data=array();
		if (preg_match("/(?ims)<span[^>]+id=\"akname\"[^>]*>(.*?)<\/span>/",$body,$name_data)) {
			$result["molecule_names_array"][]=fixTags($name_data[1]);
		}

		// synonyms impossible to use

		if ($loadData) {
			$chem_data=array();
			preg_match_all("/(?ims)<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>/",$body,$chem_data,PREG_SET_ORDER);
			//print_r($chem_data);die("XX");
			for ($b=0;$b<count($chem_data);$b++) {
				$name= strtolower(trim(fixTags($chem_data[$b][1]),": ."));
				if ($name=="") {
					continue;
				}
				$value=fixTags($chem_data[$b][2]);
				if ($value=="N/A"||$value=="-") {
					continue;
				}

				switch ($name) {
				case "cas no":
					$result["cas_nr"]=$value;
				break;
				case "boiling point":
					list($result["bp_low"],$result["bp_high"],$press_info)=getRange($value);

					$press_data=array();
					if (preg_match("/(?ims)\(([\d,\.]+)\s*(\w+)\)/",$press_info,$press_data)) {
						$result["bp_press"]=$press_data[1];
						$result["press_unit"]=$press_data[2];
						if (strpos($result["press_unit"], "mm")!==FALSE){
							$result["press_unit"]="torr";
						}
					}
					else {
						$result["bp_press"]=1;
						$result["press_unit"]="bar";
					}
				break;
				case "molecular formula":
					$result["emp_formula"]=$value;
				break;
				// overcautious
				// case "ghs pictogram":
				// case "signal word":
				// case "hazard statements":
				// case "precautionary statements":
				case "un#":
					$result["molecule_property"][]=array("class" => "UN_No", "source" => $this->code, "conditions" => $value);
				break;
				case "class": // Lagerklasse
					$result["safety_danger"]=$value;
				break;
				case "packing group":
					$result["molecule_property"][]=array("class" => "packing_group", "source" => $this->code, "conditions" => $value);
				break;
				}
			}

			// MSDS, always en
			$msds=array();
			if (preg_match("/(?ims)<input[^>]+id=\"nowBD\"[^>]+value=\"(.*?)\"[^>]*>/",$body,$msds)) {
				$catNo=fixTags($msds[1]);
				$roundThousand=ceil(floatval($catNo) * 0.001);
				$result["default_safety_sheet"]="";
				$result["default_safety_sheet_url"]="-https://file.bldpharm.com/static/upload/prosds/bldsds-ger/".$roundThousand."/SDS-".$catNo.".pdf";
				$result["default_safety_sheet_by"]=$this->name;
			}
		}
		
		// prices
		$match=array();
		if (preg_match("/(?ims)<input[^>]+id=\"proid\"[^>]+value=\"(.*?)\"[^>]*>/",$body,$match)) {
			$url=$this->urls["price"].fixTags($match[1])."?params=e30%3D&_xsrf=";
			$my_http_options=$default_http_options;
			$my_http_options["redirect"]=maxRedir;
			$response=oe_http_get($url,$my_http_options);
			if ($response!==FALSE) {
				$price_data=json_decode($response->getBody(), true);
				$currencyKey = "pr_usd";
				
				$value=$price_data["value"]??array();
				$proInfo=$value["proInfo"]??null;
				if (is_array($proInfo))	foreach ($proInfo as $key => $proInfoData) {
					$entryInfo=$value[$key]??null;
					if (is_array($entryInfo)) foreach ($entryInfo as $entry) {
						list(,$amount,$amount_unit)=getRange($entry["pr_size"]??"");
						$discountFactor=getNumber($entry["pr_rate"]??1.0);
						$price=getNumber($entry["price_dict"][$currencyKey]??null);
						
						$result["price"][]=array(
							"supplier" => $this->code, 
							"catNo" => fixTags(($entry["pr_bd"]??"")." ".($entry["p_purity"]??"")),
							"amount" => $amount, 
							"amount_unit" => $amount_unit, 
							"price" => $price*$discountFactor, 
							"currency" => "USD",
						);
					}
				}
			}
		}
		
		$result["supplierCode"]=$this->code;
		$result["catNo"]=$catNo;
		return $result;
    }
	
	public function getPrices($catNo) {
		return $this->getInfo($catNo,false);
	}
	
	public function procHitlist(& $response) {
		$body=$response->getBody();
		//die($body);
		
		$results=array();
		$data=json_decode($body, true);
		$subData=$data["value"]??null;
		//print_r($subData);
		if (is_array($subData)) foreach ($subData as $key => $subDataEntry) {
			$results[]=array(
				"name" => fixTags($subDataEntry["nameEN"]), 
				"casNr" => fixTags($subDataEntry["cas"]), 
				"beautifulCatNo" => fixTags($subDataEntry["p_bd"]), 
				"catNo" => fixTags($subDataEntry["url"]), 
				"supplierCode" => $this->code, 
			);
		}
		
		return $results;
	}
}
?>