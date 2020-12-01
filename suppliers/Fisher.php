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
// Fisher Scientific
$GLOBALS["code"]="Fisher";
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "Fisher";
	public $logo =  "logo_fisher.gif"; 
	public $height =  50; 
	public $vendor =  true; 
	public $hasPriceList =  0; 
	public $excludeFields =  array(); 
	public $urls=array(
		"server" => "https://www.fishersci.com" // startPage
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["search"]=$this->urls["server"]."/us/en/catalog/search/products?keyword=";
	$this->urls["detail"]=$this->urls["server"]."/shop/products/";
	$this->urls["startPage"]=$this->urls["server"];
    }
	
	public function requestResultList($query_obj) {
		return array(
			"method" => "url",
			"action" => $this->urls["search"].urlencode($query_obj["vals"][0][0])
		);
	}
	
	public function getDetailPageURL($catNo) {
		return $this->urls["detail"].$catNo."/?referrer=enventory"; // last number is irrelevant
	}
	
	public function getInfo($catNo) {
		global $noConnection,$default_http_options;
		
		$url=$this->getDetailPageURL($catNo);
		if (empty($url)) {
			return $noConnection;
		}
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($url,$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}
		return $this->procDetail($response,$catNo);
	}
	
	public function getHitlist($searchText,$filter,$mode="ct",$paramHash=array()) {
		global $noConnection,$default_http_options;
		
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($this->urls["search"].urlencode($searchText),$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}
		return $this->procHitlist($response);
	}
	
	public function procDetail(& $response,$catNo="") {
		$body=utf8_encode(@$response->getBody());
		$body=preg_replace(array("/(?ims)<script.*?<\/script>/","/(?ims)<style.*?<\/style>/"),"",$body);

		$result["molecule_names_array"]=array();
		$result["molecule_property"]=array();
		$result["catNo"]=$catNo; // may be overwritten later

		if (preg_match("/(?ims)<h1[^>]*>(.*?)<\/h1>/",$body,$match)) {
			$result["molecule_names_array"][]=fixTags(preg_replace("/(?ims)<span.*?<\/span>/","",$match[2]));
		}

		preg_match_all("/(?ims)<th[^>]*>(.*?)<\/th>\s*<td[^>]*>(.*?)<\/td>/",$body,$manyLines,PREG_SET_ORDER);
		for ($b=0;$b<count($manyLines);$b++) {
			$name=fixTags($manyLines[$b][1]);
			$value=fixTags($manyLines[$b][2]);

			if (startswith($name,"CAS") && !isset($result["cas_nr"])) { // there are other cells starting with CAS
				$result["cas_nr"]=$value;
			} else {
				switch ($name) {
					case "Chemical Name or Material":
						$result["molecule_names_array"][]=$value;
					break;
					case "Molecular Formula":
						$result["emp_formula"]=str_replace(" ","",$value);
					break;
					case "Formula weight":
						$result["mw"]=getNumber($value);
					break;
					case "Density":
						$result["density_20"]=getNumber($value);
					break;
				}
			}
		}

		// read data from hidden html form fields
		$form_data=readInputData($body);

		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($this->urls["server"]."/shop/GetSpecifications?id=".$form_data["productId"],$my_http_options);
		//~ die(@HTTP_Request2_Response::decodeGzip($response->getBody()));
		if ($response!==FALSE) {
			$json=json_decode(@HTTP_Request2_Response::decodeGzip($response->getBody()),true);

			if (is_array($json["specAttributes"])) {
				list($result["bp_low"],$result["bp_high"],$press)=getRange($json["specAttributes"]["Boiling Point: "]);
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

				$result["density_20"]=getNumber($json["specAttributes"]["Sp. gr.: "]);

				$number=getNumber($json["specAttributes"]["Flash Point: "]);
				if (is_numeric($number)) {
					$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => $number, "unit" => "°C");
				}

				list($result["mp_low"],$result["mp_high"])=getRange($json["specAttributes"]["Melting Point: "]);
			}
		}

		$response=oe_http_get($this->urls["server"]."/shop/GetSafety?id=".$form_data["productId"],$my_http_options);
		//~ die(@HTTP_Request2_Response::decodeGzip($response->getBody()));
		if ($response!==FALSE) {
			$json=json_decode(@HTTP_Request2_Response::decodeGzip($response->getBody()),true);

			$result["safety_text"]=ucfirst(fixTags($json["hazard2"]));
		}

		// MSDS, only in English
		if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*msds[^\"]*)\"[^>]*>.*?SDS.*?<\/a>/",$body,$match)) {
			$result["default_safety_sheet"]="";
			$result["default_safety_sheet_url"]="-".$this->urls["server"].html_entity_decode($match[1]);
			$result["default_safety_sheet_by"]=$this->name;
		}

		$result["supplierCode"]=$this->code;
		return $result;
	}
	
	public function procHitlist(& $response) {
		$body=@$response->getBody();
		if (strpos($body,"searchErrorText")!==FALSE) {
			return $noResults;
		}
		else {
			if (preg_match("/(?ims)\"productResults\":(\[.*\]),\s*\"keywordRedirectUrl\"/",$body,$json)) {
				$productList=json_decode($json[1],true);

				$results=array();
				$remove="/shop/products/";
				$removeLen=strlen($remove);
				for ($b=0;$b<count($productList);$b++) {
					$catNo=$productList[$b]["productUrl"];
					$prefixPos=stripos($catNo,$remove);

					if ($prefixPos!==FALSE) {
						$catNo=substr($catNo,$prefixPos+$removeLen);
					}

					if (isEmptyStr($catNo)) {
						continue;
					}

					$beautifulCatNos=$productList[$b]["itemCatalogNo"];
					for ($c=0;$c<count($beautifulCatNos);$c++) {
						$beautifulCatNos[$c]=fixTags($beautifulCatNos[$c]);
					}

					$results[]=array(
						"name" => html_entity_decode(fixTags($productList[$b]["name"])), 
						"catNo" => fixTags($catNo), 
						"beautifulCatNo" => join(", ",$beautifulCatNos), 
						"supplierCode" => $this->code, 
					);
				}
			}
		}
		return $results;
	}
}
?>