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
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "Carl Roth";
	public $logo = "carl_roth.jpg";
	public $height = 85;
	public $vendor = true; 
	public $hasPriceList = 2;
	public $catalogHierarchy = 1;
	public $testCas = array("67-64-1" => array(
			array("acetone"),
		)
	);
	public $testEmpFormula = array("C7H8" => array(
			array("toluene"),
		)
	);
	public $urls=array(
		"server" => "https://www.carlroth.com", // startPage
		"api" => "https://api.carlroth.com/"
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["base_url"]=$this->urls["server"]."/de/en/";
		$this->urls["detail"]=$this->urls["base_url"];
		$this->urls["api_base"]=$this->urls["api"]."occ/v2/carlroth_DE_SPA/products/";
		$this->urls["search"]=$this->urls["api_base"]."search?lang=en&query=";
		$this->urls["api_detail"]=$this->urls["api_base"];
		$this->urls["api_suffix"]="?fields=FULL&lang=en";
		$this->urls["startPage"]=$this->urls["server"];
    }
	
	public function getPrices($catNo) {
		return $this->getInfo($catNo,false);
	}
	
	public function requestResultList($query_obj) {
		return array(
			"method" => "url",
			"action" => $this->urls["base_url"]."search/".urlencode($query_obj["vals"][0][0])
		);
	}
	
	public function getDetailPageURL($catNo) {
		if (empty($catNo)) {
			return;
		}
		$splitCatNo=explode("~",$catNo,2); // We store it in format: internalCatNo~url
		return $this->urls["detail"].$splitCatNo[1]."?referrer=enventory";
	}
	
	public function getInfo($catNo,$loadData=true) {
		global $noConnection,$default_http_options;
		
		$splitCatNo=explode("~",$catNo,2);
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$my_http_options["header"]=array("Accept" => "application/json");
		$response=oe_http_get($this->urls["api_detail"].$splitCatNo[0].$this->urls["api_suffix"],$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}

		return $this->procDetail($response,$catNo,$loadData);
	}
	
	public function getHitlist($searchText,$filter,$mode="ct",$paramHash=array()) {
		global $noConnection,$default_http_options;
		
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$my_http_options["referer"]=$this->urls["server"];
		$my_http_options["header"]=array("Accept" => "application/json");
		$response=oe_http_get($this->urls["search"].urlencode($searchText),$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}
		return $this->procHitlist($response);
	}
	
	public function procDetail(& $response,$catNo="",$loadData=true) {
		$body=$response->getBody();
//		die($body);
		
		$result=array();
		$result["price"]=array();
		$result["molecule_names_array"]=array();
		$result["molecule_property"]=array();
		$result["catNo"]=$catNo; // may be overwritten later
		$result["supplierCode"]=$this->code;
		
		$data=json_decode($body, true);
		//var_dump($data);die();
		if ($loadData && is_array($data["securityDatasheets"]??null)) {
			foreach ($data["securityDatasheets"] as $subData) {
				$lang=$subData["language"]["isocode"]??"";
				if ($lang=="en") {
					$result["default_safety_sheet"]="";
					$result["default_safety_sheet_url"]="-".$this->urls["server"].$subData["url"];
					$result["default_safety_sheet_by"]=$this->name;
				} elseif ($lang=="de") {
					$result["alt_default_safety_sheet"]="";
					$result["alt_default_safety_sheet_url"]="-".$this->urls["server"].$subData["url"];
					$result["alt_default_safety_sheet_by"]=$this->name;
				}
			}
		}
		
		$result["molecule_names_array"][]=fixTags($data["name"]??"");
		
		if ($loadData) {
			$hazardImages=$data["hazardImages"]??null;
			if(is_array($hazardImages)) {
				$safety_sym_ghs=array();
				foreach ($hazardImages as $hazardImage) {
					$safety_sym_ghs[]= strtoupper($hazardImage["code"]??"");

				}
				$result["safety_sym_ghs"]=@join(",",$safety_sym_ghs);
			}

			// match H clauses
			$result["safety_text"]=fixTags($data["dangerWord"]??"");
			$result["safety_h"]=fixTags(str_replace("H","",str_replace(array(" H",",",";","--"),"-",$data["hstatements"]??"")));
			$result["safety_p"]=fixTags(str_replace("P","",str_replace(array(" P",",",";","--"),"-",$data["pstatements"]??"")));

			$split=preg_split("/(?ims)<br\s*\/?>/", $data["characteristics"]??"");
			foreach ($split as $line) {
				$value = $this->substringAfter($line, "Empirical formula");
				if (!is_null($value)) {
					$result["emp_formula"]=$value;
				}
				$value = $this->substringAfter($line, "Molar mass (M)");
				if (!is_null($value)) {
					$result["mw"]=getNumber($value);
				}
				$value = $this->substringAfter($line, "Density (D)");
				if (!is_null($value)) {
					$result["density_20"]=getNumber($value);
				}
				$value = $this->substringAfter($line, "Melting point (mp)");
				if (!is_null($value)) {
					list($result["mp_low"],$result["mp_high"])=getRange($value);
				}
				$value = $this->substringAfter($line, "Boiling point (bp)");
				if (!is_null($value)) {
					list($result["bp_low"],$result["bp_high"])=getRange($value);
					$result["bp_press"]=1;
					$result["press_unit"]="bar";
				}
				$value = $this->substringAfter($line, "Flash point (flp)");
				if (!is_null($value)) {
					$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => getNumber($value), "unit" => "°C");
				}
				$value = $this->substringAfter($line, "UN-Nr.");
				if (!is_null($value)) {
					$result["molecule_property"][]=array("class" => "UN_No", "source" => $this->code, "conditions" => $value);
				}
				$value = $this->substringAfter($line, "ADR");
				if (!is_null($value)) {
					$result["molecule_property"][]=array("class" => "adr", "source" => $this->code, "conditions" => $value);
				}
				$value = $this->substringAfter($line, "WGK");
				if (!is_null($value)) {
					$result["safety_wgk"]=$value;
				}
				$value = $this->substringAfter($line, "CAS No.");
				if (!is_null($value)) {
					$result["cas_nr"]=$value;
				}
			}
		}
		
		$this->applyPriceData($result, $data["price"]??null);
		
		$variantOptions=$data["variantOptions"]??array();
		foreach ($variantOptions as $variantOption) {
			list(,$amount,$amount_unit)=getRange($variantOption["formattedContentUnit"]??"");
			$entry=array(
				"supplier" => $this->code, 
				"catNo" => fixTags($variantOption["code"]??""),
				"amount" => $amount, 
				"amount_unit" => $amount_unit, 
			);
			$this->applyPriceData($entry,$variantOption["priceData"]??null);
			$result["price"][]=$entry;
		}

		//~ var_dump($result);

		return $result;
	}
	
	public function substringAfter($text, $prefix) {
		if (startswith($text, $prefix)) {
			return fixTags(substr($text, strlen($prefix)));
		}
	}
	
	public function applyPriceData(& $retval, $price_data) {
		if (!is_null($price_data) && ($price_data["priceType"]??"")=="BUY") {
			$retval["price"]=getNumber($price_data["value"]);
			$retval["currency"]=fixTags($price_data["currencyIso"]);
		}
	}
	
	public function procHitlist(& $response) {
		$body=$response->getBody();
//		die($body);
		
		$results=array();
		$data=json_decode($body, true);
		$subData=$data["products"]??null;
		//print_r($subData);
		if (is_array($subData)) foreach ($subData as $key => $subDataEntry) {
			$catNo=fixTags($subDataEntry["baseProduct"]);
			$results[]=array(
				"name" => fixTags($subDataEntry["name"]), 
				"addInfo" => fixTags($subDataEntry["purityLevel"]), 
				"beautifulCatNo" => $catNo, 
				"catNo" => $catNo."~".fixTags($subDataEntry["url"]), 
				"supplierCode" => $this->code, 
			);
		}
		
		return $results;
	}
}
?>