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
	public $logo = "logo_fisher.gif"; 
	public $height = 50; 
	public $vendor = true; 
	public $hasPriceList = 0; 
	public $excludeFields = array(); 
	public $urls=array(
		"server" => "https://www.fishersci.com" // startPage
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["search"]=$this->urls["server"]."/us/en/catalog/search/products?keyword=";
		$this->urls["detail_path"]="/shop/products/";
		$this->urls["detail"]=$this->urls["server"].$this->urls["detail_path"];
		$this->urls["price"]=$this->urls["server"]."/fs-quickview-ui/items?language=en&countryCode=US&legacyFamilyId=";
		$this->urls["startPage"]=$this->urls["server"];
    }
	
	public function requestResultList($query_obj) {
		return array(
			"method" => "url",
			"action" => $this->urls["search"].urlencode($query_obj["vals"][0][0])
		);
	}
	
	public function getDetailPageURL($catNo) {
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

		$match=array();
		if (preg_match("/(?ims)<h1[^>]*>(.*?)<\/h1>/",$body,$match)) {
			$result["molecule_names_array"][]=fixTags(preg_replace("/(?ims)<span.*?<\/span>/","",$match[1]));
		}

		$manyLines=array();
		preg_match_all("/(?ims)<t[hd][^>]*>(.*?)<\/t[hd]>\s*<td[^>]*>(.*?)<\/td>/",$body,$manyLines,PREG_SET_ORDER);
		for ($b=0;$b<count($manyLines);$b++) {
			$name=strtolower(fixTags($manyLines[$b][1]));
			$value=fixTags($manyLines[$b][2]);

			if (startswith($name,"cas") && !isset($result["cas_nr"])) { // there are other cells starting with CAS
				$result["cas_nr"]=$value;
			} else {
				switch ($name) {
					case "chemical name or material":
						$result["molecule_names_array"][]=$value;
					break;
					case "molecular formula":
						$result["emp_formula"]=str_replace(" ","",$value);
					break;
					case "formula weight":
					case "molecular weight (g/mol)":
						$result["mw"]=getNumber($value);
					break;
					case "density":
						$result["density_20"]=getNumber($value);
					break;
					case "refractive index":
						$result["n_20"]=getNumber($value);
					break;
				}
			}
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
		global $noResults;

		$body=@$response->getBody();
		if (strpos($body,"find any items matching")!==FALSE) {
			return $noResults;
		}
		elseif (strpos($body,"searchResultsHeading")===FALSE) { // only one
			$catNo=$response->getEffectiveUrl();
			cutRange($catNo, $this->urls["detail_path"], "?", false);
			
			$results[0]=$this->procDetail($response);
			extendMoleculeNames($results[0]);
			//~ var_dump($results[0]);die();
			$results[0]=array_merge($results[0],array("supplierCode" => $this->code, "catNo" => $catNo, ) );
		} else {
			cutRange($body,"id=\"searchResultsHeading\"","<footer");
			$manyLines=array();
			if (preg_match_all("/(?ims)<a[^>]+data-part-no=\"([^\"]+)\"[^>]+href=\"\/shop\/products\/([^\"]+)\"[^>]*>(.*?)<\/a>(.*?)<\/p>/",$body,$manyLines,PREG_SET_ORDER)) {
				foreach ($manyLines as $line) {
					$catNo=fixTags($line[2]);
					$hashpos=strpos($catNo, "#");
					if ($hashpos!==FALSE) {
						$catNo=substr($catNo,0,$hashpos);
					}
					$results[]=array(
						"name" => html_entity_decode(fixTags($line[3])), 
						"beautifulCatNo" => fixTags($line[1]), 
						"catNo" => $catNo, 
						"supplierCode" => $this->code, 
					);
				}
			}
		}
		return $results;
	}
	
	public function getBestHit(& $hitlist,$name=NULL) {
		if (!is_null($name)) {
			$a=count($hitlist)-1;
			while ($a>=0) {
				if ($name==strtolower($hitlist[$a]["name"])) {
					return $a;
				}
				$a--;
			}
		}
		$a=count($hitlist)-1;
		while ($a>=0 && (strpos($hitlist[$a]["name"],"SPEX")!==FALSE || strpos($hitlist[$a]["name"],"CertiPrep")!==FALSE)) {
			$a--;
		}
		return $a;
	}
}
?>