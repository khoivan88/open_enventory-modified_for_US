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
// Acros
$GLOBALS["code"]="Activate";
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "Activate Scientific";
	public $logo = "aslogo.png";
	public $height = 40;
	public $vendor = true;
	public $hasPriceList = 2; 
	public $urls=array(
		"server" => "http://shop.activate-scientific.com", // startPage
		"chemicalize_server_url" => "https://catalog.chemicalize.com"
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["search"]=$this->urls["chemicalize_server_url"]."/v1/48cb00fd27694c7c8824bbd4c566177e/search/";
		$this->urls["search_referer"]=$this->urls["chemicalize_server_url"]."/v1/48cb00fd27694c7c8824bbd4c566177e/editor.html";
		$this->urls["detail"]=$this->urls["server"]."/code/";
		$this->urls["startPage"]=$this->urls["server"];
    }
	
	public function requestResultList($query_obj) {
		return array(
			"method" => "url",
			"action" => $this->urls["search"].urlencode($query_obj["vals"][0][0])
		);
	}
	
	public function getDetailPageURL($catNo) {
		return $this->urls["detail"].$catNo."?referrer=enventory"; // last number is irrelevant
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
		$my_http_options["referer"]=$this->urls["search_referer"];
		$my_http_options["mime"]="application/json; charset=utf-8";
		$response=@oe_http_post_fields($this->urls["search"],json_encode(array(
			"hitColor" => "#ff8000", 
			"hitColoring" => "OFF", 
			"limit" => "50", 
			"searchType" => "FULL", 
			"similarityThreshold" => "0.5", 
			"structure" => $searchText
		)),array(),$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}
		return $this->procHitlist($response);
	}
	
	public function procDetail(& $response,$catNo="") {
		$body=utf8_encode(@$response->getBody());
		cutRange($body,"<h1","class=\"footer-main\"");
		$result=array();
		$result["catNo"]=$catNo;

		// name
		$name_data=array();
		preg_match("/(?ims)<h1.*?>(.*?)<\/h1>/",$body,$name_data);
		$result["molecule_names_array"]=array(fixTags($name_data[1]));

		$manyLines=array();
		preg_match_all("/(?ims)<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/",$body,$manyLines,PREG_SET_ORDER);
		//~ print_r($manyLines);die();

		for ($b=0;$b<count($manyLines);$b++) {
			$name=fixTags($manyLines[$b][1]);
			$rawValue=$manyLines[$b][2];
			$value=fixTags($rawValue);

			switch ($name) {
				case "Catalog #":
					$result["catNo"]=$value;
				break;
				case "C.A.S.:":
					$value=trim($value,"[]");
					if (strpos($value,", ")!==FALSE) {
						$value=getBestCAS(explode(", ",$value));
					}
					$result["cas_nr"]=$value;
				break;
				case "Formula:":
					$result["emp_formula"]=$value;
				break;
				case "Mass:":
					$result["mw"]=getNumber($value);
				break;
				case "Material Safety Data Sheet:":
					$href_match=array();
					if (preg_match("/(?ims)<a [^>]*href=\"([^\"]+)\"[^>]*>/",$rawValue,$href_match)) {
						$result["default_safety_sheet"]="";
						$result["default_safety_sheet_url"]="-".$this->urls["server"].htmlspecialchars_decode($href_match[1]);
						$result["default_safety_sheet_by"]=$this->name;
					}
				break;
			}
		}

		// prices
		preg_match_all("/(?ims)<form[^>]+class=\"buybox--form\"[^>]*>(.*?)<\/form>/",$body,$manyLines,PREG_PATTERN_ORDER);
		$manyLines=$manyLines[0];

		for ($b=0;$b<count($manyLines);$b++) {
			$cells=array();
			preg_match_all("/(?ims)<div[^>]*>(.*?)<\/div>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];

			if (count($cells)<4) {
				continue;
			}

			list(,$amount,$amount_unit)=getRange(fixTags($cells[1]));
			list(,$price,$currency)=getRange(trim(fixTags($cells[2])," *"));

			$result["price"][]=array(
				"supplier" => $this->code, 
				"amount" => $amount, 
				"amount_unit" => strtolower($amount_unit), 
				"price" => $price, 
				"currency" => fixCurrency($currency), 
				"catNo" => $catNo, 
				"beautifulCatNo" => fixTags($cells[0]), 
			);
		}

		$result["supplierCode"]=$this->code;
		return $result;
	}
	
	public function procHitlist(& $response) {
		$body=@$response->getBody();
		$json=json_decode($body,true);
//~ 	print_r($json);die();
		$results=array();
		$catNos=array();
		if (is_array($json)) foreach ($json["results"] as $result) {
			if (!in_array($result["productId"],$catNos)) {
				$catNos[]=$result["productId"];
				$results[]=array(
					"name" => fixTags($result["properties"]["bezeichnung"]), 
					"beautifulCatNo" => $result["productId"], 
					"catNo" => $result["productId"], 
					"supplierCode" => $this->code, 
				);
			}
		}

		return $results;
	}
};
?>