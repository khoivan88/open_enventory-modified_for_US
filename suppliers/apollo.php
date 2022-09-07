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
$GLOBALS["code"]="apollo";
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "Apollo";
	public $logo = "logo_apollo.png";
	public $height = 85;
	public $vendor = true; 
	public $hasPriceList = 2;
	public $testCas = array("67-64-1" => array(
			array("acetone"),
		)
	);
	public $excludeTests = array("emp_formula");
		
	public $urls=array(
		"server" => "https://store.apolloscientific.co.uk" // startPage
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["search"]=$this->urls["server"]."/search?limit=96&search=";
		$this->urls["detail"]=$this->urls["server"]."/product/";
		$this->urls["startPage"]=$this->urls["server"];
    }
	
	public function requestResultList($query_obj) {
		return array(
			"method" => "url",
			"action" => $this->urls["search"].urlencode($query_obj["vals"][0][0])
		);
	}
	
	public function getDetailPageURL($catNo) {
		if (empty($catNo)) {
			return;
		}
		return $this->urls["detail"].$catNo."?referrer=enventory";
	}
	
	public function getInfo($catNo) {
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

		$body=@$response->getBody();
		return $this->procDetail($response,$catNo);
	}
	
	public function getHitlist($searchText,$filter,$mode="ct",$paramHash=array()) {
		global $noConnection,$default_http_options;
		
		$my_http_options=$default_http_options;
		$response=@oe_http_get($this->urls["search"].urlencode($searchText),$my_http_options);

		if ($response==FALSE) {
			return $noConnection;
		}
		return $this->procHitlist($response);
	}
	
	public function procDetail(& $response,$catNo="") {
		$body=@$response->getBody();
		cutRange($body,"class=\"product-details\"","</section>");
		$body=str_replace("&dash;", "-",$body);

		$result=array();
		$result["molecule_names_array"]=array();
		$result["molecule_property"]=array();
		$result["catNo"]=$catNo; // may be overwritten later

		$match=array();
		if (preg_match("/(?ims)<h1[^>]*>(.*?)<\/h1>/",$body,$match)) {
			$result["molecule_names_array"][]=fixTags($match[1]);
		}

		if (preg_match("/(?ims)<h3[^>]*>.*?<span[^>]*>(.*?)<\/h3>/",$body,$match)) {
			$result["catNo"]=$catNo=fixTags($match[1]);
		}

		if (preg_match("/(?ims)<h2[^>]*>.*?Synonym.*?<small[^>]*>(.*?)<\/h2>/",$body,$match)) {
			$result["molecule_names_array"]=array_merge($result["molecule_names_array"],explode(", ",$match[1]));
		}

		//  prices in dark boxes
		$entries=array();
		if (preg_match_all("/(?ims)<div[^>]+class=\"product-pack\"[^>]*>.*?<h2[^>]*>(.*?)\s*-\s*(.*?)<\/h2>.*?<\/div>(.*?)<div[^>]*>/",$body,$entries,PREG_SET_ORDER)) {
			foreach ($entries as $entry) {
				list(,$amount,$amount_unit)=getRange($entry[1]);
				$price_data=array();
				preg_match("/(?ims)([^\d]*)\(?(\-?[\d\.,]+)\)?/",fixTags($entry[2]),$price_data);

				$result["price"][]=array(
					"supplier" => $this->code, 
					"amount" => $amount, 
					"amount_unit" => strtolower($amount_unit), 
					"price" => $price_data[2]+0.0, 
					"currency" => fixCurrency($price_data[1]), 
					"catNo" => $catNo, 
					"beautifulCatNo" => $catNo, 
					"addInfo" => fixTags($entry[3])
				);
			}
		}

		$match_cells=array();
		$match_ghs=array();
		if (preg_match_all("/(?ims)<dt[^>]*>(.*?)<\/dt>\s*<dd[^>]*>(.*?)<\/dd>/",$body,$match_cells,PREG_SET_ORDER)) foreach ($match_cells as $match_cell) {
			$name=fixTags($match_cell[1]);
			$rawValue=$match_cell[2];
			$value=fixTags($rawValue);

			if (isEmptyStr($name) || isEmptyStr($value)) {
				continue;
			}

			switch ($name) {
			case "Formula weight":
				$result["cas_nr"]=getNumber($value);
			break;
			case "GHS":
				if (preg_match_all("/(?ims)<img [^>]*src=\"[^\"]*\/ghs\/([^\"]*)\.png\"[^>]*>/",$rawValue,$match_ghs,PREG_PATTERN_ORDER)) {
					$result["safety_sym_ghs"]=@join(",",$match_ghs[1]);
				}
			break;
			case "Hazard Statements":
				if (preg_match_all("/(?ims)([H\d+]+):/",$rawValue,$match_ghs,PREG_PATTERN_ORDER)) {
					$result["safety_h"]=@join("-",array_unique(str_replace("H","",$match_ghs[1])));
				}
			break;
			case "Precautionary Statements": // contain a lot of bogus chars
				if (preg_match_all("/(?ims)([P\d+]+):/",$rawValue,$match_ghs,PREG_PATTERN_ORDER)) {
					$result["safety_p"]=@join("-",array_unique(str_replace("P","",$match_ghs[1])));
				}
			break;
			default:
				// handle more complicated cases
				if (strpos($name,"Boiling point")!==FALSE) {
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
				}
				elseif (strpos($name,"Melting point")!==FALSE) {
					list($result["mp_low"],$result["mp_high"])=getRange($value);
				}
				elseif (strpos($name,"Flash point")!==FALSE) {
					$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => getNumber($value), "unit" => "°C");
				}
				elseif (strpos($name,"CAS")!==FALSE) {
					$result["cas_nr"]=$value;
				}
			}
		}

		$result["supplierCode"]=$this->code;
		$result["catNo"]=$catNo;

		//~ var_dump($result);

		return $result;
	}
	
	public function procHitlist(& $response) {
		$body=utf8_decode(@$response->getBody());
		cutRange($body,"class=\"product-list\"","</h5>");

		$results=array();
		$manyLines=array();
		if (preg_match_all("/(?ims)<h2[^>]*>.*?<a[^>]+href=\"[^\"]*\/product\/(.*?)\"[^>]*>(.*?)<\/a>(.*?)<\/div>/",$body,$manyLines,PREG_SET_ORDER)) {
			foreach ($manyLines as $line) {
				$result=array(
					"name" => fixTags($line[2]), 
					"catNo" => $line[1], 
					"supplierCode" => $this->code, 
				);
				$lines=explode("</span>",$line[3]);
				foreach ($lines as $entry) {
					list($name, $value)=explodeSafe(":",$entry,2);
					$name=trim(fixTags($name),":");
					$value=fixTags($value);

					if ($name=="CAS Number") {
						$result["cas_nr"]=$value;
					}
					elseif ($name=="Catalogue No") {
						$result["beautifulCatNo"]=$value;
					}
				}
				$results[]=$result;
			}
		}

		return $results;
	}
}
?>