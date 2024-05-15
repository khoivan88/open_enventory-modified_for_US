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
		$this->urls["base"]=$this->urls["server"]."/catalog/index.php";
		$this->urls["detail"]=$this->urls["server"]."/catalog/v/";
		$this->urls["startPage"]=$this->urls["server"];
    }
	
	public function requestResultList($query_obj) {
		$retval = array(	
			"method" => "url",
			"action" => $this->urls["base"]."?focus="
		);
		if ($query_obj["crits"][0]=="cas_nr") {
			$retval["action"].="cas";
		}
		else {
			$retval["action"].="product";
		}
		$retval["action"].="&keyword=".urlencode($query_obj["vals"][0][0])."&x=10&y=10&page_function=keyword_search&display_products=list";

		return $retval;
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
		
		// http://www.strem.com/catalog/index.php?focus=product&keyword=nacnac&x=9&y=20&page_function=keyword_search&display_products=list
		$url=$this->urls["base"]."?focus=";
		if ($filter=="cas_nr") {
			$url.="cas";
		}
		else {
			$url.="product";
		}
		$url.="&keyword=".urlencode($searchText)."&x=10&y=10&page_function=keyword_search&display_products=list";
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($url,$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}
		return $this->procHitlist($response);
	}
	
	public function procDetail(& $response,$catNo="") {
		$body=utf8_encode(@$response->getBody());
		cutRange($body,"<div id=\"page_header_catalog\">","Enter a lot number",false);
		$result=array();
		$result["catNo"]=$catNo;

		// MSDS, take the first one
		$match=array();
		if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*\/sds\/[^\"]*)\"[^>]*>/",$body,$match)) {
			$result["default_safety_sheet"]="";
			$result["default_safety_sheet_url"]="-".$this->urls["server"].htmlspecialchars_decode($match[1]);
			$result["default_safety_sheet_by"]=$this->name;
		}

		$manyLines=array();
		preg_match_all("/(?ims)<div.*?<\/div>/",$body,$manyLines,PREG_PATTERN_ORDER);
		$manyLines=$manyLines[0];
		for ($b=0;$b<count($manyLines);$b++) {
			// <div class="product_list_header"><div class="catalog_number">07-1655</div>
			if (strpos($manyLines[$b],"catalog_number")!==FALSE) {
				$result["catNo"]=fixTags($manyLines[$b]);
				break;
			}
		}

		preg_match_all("/(?ims)<span.*?<\/span>/",$body,$manyLines,PREG_PATTERN_ORDER);
		$manyLines=$manyLines[0];
		//~ print_r($manyLines);die();

		for ($b=0;$b<count($manyLines);$b++) {
			// <span id="header_description">4-(Phenylamino)-2-(phenylimino)-3-pentene, min. 98% NacNac</span>
			if (strpos($manyLines[$b],"header_description")!==FALSE) {
				cutRange($manyLines[$b],"header_description");
				$result["molecule_names_array"][]=fixTags("<\"".$manyLines[$b]); // fake complete tag
			}
		}
		preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$manyLines,PREG_PATTERN_ORDER);
		$manyLines=$manyLines[0];
		//~ print_r($manyLines);die();

		for ($b=0;$b<count($manyLines);$b++) {
			$cells=array();
			preg_match_all("/(?ims)<td.*?<\/td>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
			$cells=$cells[0];
			//~ print_r($cells);die();
			$name=fixTags($cells[0]);
			$value=fixTags($cells[1]);
			switch ($name) {
				case "Chemical Name:":
					$result["molecule_names_array"][]=$value;
				break;
				case "Synonym:":
					$synonyms=explode(", ",$value);
					$result["molecule_names_array"]=arr_merge($result["molecule_names_array"],$synonyms);
				break;
				case "Product Number:":
					if (empty($catNo)) {
						$result["catNo"]=$value;
					}
				break;
				case "CAS Number:":
				case "CAS Registry Number:":
					$result["cas_nr"]=$value;
				break;
				case "Molecular Formula:":
					$result["emp_formula"]=$value;
				break;
				case "Specific Gravity:":
					$result["density_20"]=getNumber($value);
				break;
				case "Molecular Weight:":
					$result["mw"]=getNumber($value);
				break;
				case "Melting Point:":
					if (strpos($value,"C")!==FALSE) { // exclude Fahrenheit
						$result["mp_high"]=getNumber($value);
					}
				break;
				case "Boiling Point:":
					if (strpos($value,"C")!==FALSE) { // exclude Fahrenheit
						list($temp,$press)=explode("/",$value,2);
						$result["bp_high"]=getNumber($temp);
						if (trim($press)!="") {
							$result["bp_press"]=getNumber($press);
							if (strpos($press,"mm")!==FALSE) {
								$result["press_unit"]="torr";
							}
						}
						else {
							$result["bp_press"]=1;
							$result["press_unit"]="bar";
						}
					}
				break;
				case "Flash Point:":
					if (strpos($value,"C")!==FALSE) { // exclude Fahrenheit
						$number=getNumber($value);
						if (is_numeric($number)) {
							$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => $number, "unit" => "°C");
						}
					}
				break;
				case "Autoignition Temperature:":
					if (strpos($value,"C")!==FALSE) { // exclude Fahrenheit
						$number=getNumber($value);
						if (is_numeric($number)) {
							$result["molecule_property"][]=array("class" => "Autoign_temp", "source" => $this->code, "value_high" => $number, "unit" => "°C");
						}
					}
				break;
				case "Extinguishing Medium:":
					$result["molecule_property"][]=array("class" => "extinguishant", "source" => $this->code, "conditions" => $value);
				break;
			}
		}

		$result["supplierCode"]=$this->code;
		return $result;
	}
	
	public function procHitlist(& $response) {
		global $noResults;

		$body=@$response->getBody();

		if (strpos($body,"returned 0 results.")!==FALSE) {
			return $noResults;
		}
		elseif (strpos($body,"You searched for")===FALSE) { // 1 hit
			$results[0]=$this->procDetail($response);
			extendMoleculeNames($results[0]);
			$results[0]["name"]=$results[0]["molecule_name"];
			$results[0]["supplierCode"]=$this->code;
		}
		else {
			cutRange($body,"<div class=\"search_feedback\">","<div id=\"footer\">");

			$manyLines=array();
			preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$manyLines,PREG_PATTERN_ORDER);
			$manyLines=$manyLines[0];
			//~ print_r($manyLines);die();
			$results=array();

			$cells=array();
			for ($b=0;$b<count($manyLines);$b++) {
				if (stripos($manyLines[$b],"class=\"structure\"")===FALSE) {
					continue;
				}
				preg_match_all("/(?ims)<td.*?<\/td>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
				$cells=$cells[0];
				$cellCount=count($cells);
				if ($cellCount < 3) { // column heads use <th ...>
					continue;
				}
				//~ print_r($cells);die();
				$result=array("name" => fixTags($cells[2]), "catNo" =>fixTags($cells[0]), "supplierCode" => $this->code, );
				if ($cellCount > 4 && strpos($cells[4],"class=\"cas\"")!==FALSE) {
					$result["addInfo"]=fixTags($cells[3]);
					$result["cas_nr"]=fixTags($cells[4]);
				}
				elseif ($cellCount > 3 && strpos($cells[3],"class=\"cas\"")!==FALSE) {
					$result["cas_nr"]=fixTags($cells[3]);
				}
				$results[]=$result;
			}
		}
		return $results;
	}
	
	public function getData(& $pageStr,$preStr) {
		$result=array();
		preg_match("/(?ims)<tr>[\s|\n|\r]*<td[^>]*>[\s|\n|\r]*<b>".$preStr."<\/b>[\s|\n|\r]*<\/td>[\s|\n|\r]*<td[^>]*>[\s|\n|\r]*([^>]+)[\s|\n|\r]*<\/td>[\s|\n|\r]*<\/tr>/",$pageStr,$result);
		return fixHtml($result[1]);
	}
}
?>