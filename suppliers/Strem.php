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
// Strem
$GLOBALS["code"]="Strem";
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "Strem";
	public $logo = "logo_strem.gif";
	public $height = 50;
	public $vendor = true;
	public $hasPriceList = 0; 
	public $urls=array(
		"server" => "https://www.strem.com" // startPage
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["base"]=$this->urls["server"]."/webruntime/api/services/data/v61.0/";
		$this->urls["commerce"]=$this->urls["base"]."commerce/webstores/0ZEVN0000001Mo54AE/";
		$this->urls["search"]=$this->urls["server"]."/global-search/";
		$this->urls["api_search"]=$this->urls["commerce"]."search/products?categoryId=0ZGVN00000000rF4AQ&page=0&fields=Name&includeQuantityRule=false&searchTerm=";
		$this->urls["product"]=$this->urls["commerce"]."products/";
		$this->urls["price"]=$this->urls["commerce"]."pricing/products/";
		$this->urls["detail"]=$this->urls["server"]."/product/";
		$this->urls["startPage"]=$this->urls["server"];
		$this->urls["suffix"]="asGuest=true&htmlEncode=false&language=en-US";
    }
	
	public function requestResultList($query_obj) {
		return array(	
			"method" => "url",
			"action" => $this->urls["search"].urlencode($query_obj["vals"][0][0])
		);
	}
	
	public function getDetailPageURL($catNo) {
		return $this->urls["detail"].$catNo."/?referrer=enventory";
	}
	
	public function getInfo($catNo) {
		global $noConnection,$default_http_options;
		
		$url=$this->urls["product"].$catNo."?".$this->urls["suffix"];
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
		
		$url=$this->urls["api_search"].urlencode($searchText)."&".$this->urls["suffix"];
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($url,$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}
		return $this->procHitlist($response);
	}
	
	public function procDetail(& $response,$catNo="") {
		$body=@$response->getBody();
		$results=array();
		$data=json_decode($body, true);

		$result=array();
		$result["molecule_property"]=array();
		$result["catNo"]=fixTags($data["StockKeepingUnit"]??"");
		$result["molecule_names_array"]=array(fixTags($data["fields"]["Name"]??""));
		$result["cas_nr"]=fixTags($data["fields"]["CAS__c"]??"");
		$result["emp_formula"]=fixTags($data["fields"]["Index_Formula_Text__c"]??"");
		$result["mw"]=getNumber($data["fields"]["MWtext__c"]??"");
		$result["density_20"]=getNumber($data["fields"]["Density__c"]??"");
		
		list($result["mp_low"],$result["mp_high"])=getRange(fixTags($data["fields"]["Melting_point__c"]??""));
		list($result["bp_low"],$result["bp_high"],$press)=getRange(fixTags($data["fields"]["Boiling_point__c"]??""));
		if (isEmptyStr($result["bp_high"])) {
			// do nothing
		}
		elseif (strpos($press, "Hg")!==FALSE){
			$result["bp_press"]=getNumber($press);
			$result["press_unit"]="torr";
			}
		elseif (strpos($press,"mbar")!==FALSE){
			$result["bp_press"]=getNumber($press);
			$result["press_unit"]="mbar";
		}
		elseif (strpos($press,"bar")!==FALSE){
			$result["bp_press"]=getNumber($press);
			$result["press_unit"]="bar";
		}
		else {
			$result["bp_press"]="1";
			$result["press_unit"]="bar";
		}
		
		$val=getNumber($data["fields"]["Flash_point__c"]??"");
		if (!isEmptyStr($val)) {
			$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => $val, "unit" => "°C");
		}
		$val=fixTags($data["fields"]["Vapor_pressure__c"]??"");
		if (!isEmptyStr($val)) {
			if (strpos($val,"Hg")!==FALSE) {
				$unt="torr";
			}
			elseif (strpos($val,"bar")!==FALSE) {
				if (strpos($val,"mbar")!==FALSE) {
					$unt="mbar";
				}
				else {
					$unt="bar";
				}
			}
			else {
				$unt="unknown";
			}
			$tempunt="";
			if (strpos($val,"C")!==FALSE) {
				$tempunt=" °C";
			}
			elseif (strpos($val,"K")!==FALSE) {
				$tempunt=" K";
			}
			$vap_press=explode(" ",$val);
			$result["molecule_property"][]=array("class" => "Vap_press", "source" => $this->code, "value_high" => $vap_press[0], "unit" => $unt, "conditions" => ($vap_press[4]??"").$tempunt);
		}

		$result["supplierCode"]=$this->code;
		return $result;
	}
	
	public function procHitlist(& $response) {
		$body=@$response->getBody();
		$results=array();
		$data=json_decode($body, true);
		$products=$data["productsPage"]["products"]??null;
		if (is_array($products)) foreach ($products as $product) {
			$results[]=array(
				"name" => fixTags($product["fields"]["Name"]["value"]??""), 
				"catNo" => fixTags($product["id"]), 
				"supplierCode" => $this->code, 
			);
		}
		return $results;
	}
}
?>