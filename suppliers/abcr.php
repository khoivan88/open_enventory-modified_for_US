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
$GLOBALS["code"]="abcr";
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "abcr"; 
	public $logo = "logo_abcr.gif";
	public $height = 85;
	public $vendor = true;
	public $hasPriceList = 3;
	public $urls=array(
		"server" => "https://www.abcr.com" // startPage
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["search"]=$this->urls["server"]."/de_en/catalogsearch/advanced/result/?limit=50&mode=extendedlist&";
		$this->urls["detail"]=$this->urls["server"]."/de_en/";
		$this->urls["startPage"]=$this->urls["server"];
    }
	
	public function requestResultList($query_obj) {
		return array(
			"method" => "url",
			"action" => $this->getSearchURL($query_obj["vals"][0][0],$query_obj["crits"][0])
		);
	}
	
	public function getSearchURL($searchText,$filter) {
		$retval=$this->urls["search"];
		if ($filter=="cas_nr") {
			$retval.="cas=";
		}
		elseif ($filter=="emp_formula") {
			$retval.="sum_formula=";
		}
		else {
			$retval.="sname=";
		}
		return $retval.urlencode($searchText)."&referrer=enventory";
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
		$my_http_options["redirect"]=maxRedir;
		$url=$this->getSearchURL($searchText,$filter);
		$response=@oe_http_get($url,$my_http_options);

		if ($response==FALSE) {
			return $noConnection;
		}
		return $this->procHitlist($response);
	}
	
	public function procDetail(& $response,$catNo="") {
		$body=@$response->getBody();
		cutRange($body,"id=\"maincontent\"","class=\"page-footer\"");

		$result=array();
		$result["molecule_names_array"]=array();
		$result["molecule_property"]=array();
		$result["catNo"]=$catNo; // may be overwritten later
		
		$match=array();
		if (preg_match("/(?ims)<h1[^>]*>(.*?)<\/h1>/",$body,$match)) {
			list($catNo,$result["cas_nr"])=explode("|",fixTags($match[1]),2);
			$result["cas_nr"]=fixTags(str_replace("CAS","",$result["cas_nr"]));
		}

		if (preg_match("/(?ims)<h2[^>]*>(.*?)<\/h2>/",$body,$match)) {
			$result["molecule_names_array"][]=fixTags($match[1]);
		}

		if (preg_match_all("/(?ims)<span[^>]+class=\"[^\"]*icon-ghs-(\d+)[^\"]*\"[^>]*>/",$body,$match,PREG_PATTERN_ORDER)) {
			for ($b=0;$b<arrCount($match[1]);$b++) {
				$match[1][$b]="GHS0".$match[1][$b];
			}
			$result["safety_sym_ghs"]=strtoupper(implode(",",$match[1]));
		}

		$match_cells=array();
		if (preg_match_all("/(?ims)<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/",$body,$match_cells,PREG_SET_ORDER)) foreach ($match_cells as $match_cell) {
			$name=strtolower(fixTags($match_cell[1]));
			$value=fixTags($match_cell[2]);

			if (isEmptyStr($name) || isEmptyStr($value)) {
				continue;
			}

			switch ($name) {
			case "sum formula":
				$result["emp_formula"]=$value;
			break;
			case "cas":
				$result["cas_nr"]=$value;
			break;
			case "product no.":
				$catNo=$value;
			break;
			case "flash point":
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => getNumber($value), "unit" => "°C");
				}
			break;
			case "molecular weight":
				$result["mw"]=getNumber($value);
			break;
			case "density":
				$result["density_20"]=getNumber($value);
			break;
			case "boiling point":
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
			break;
			case "melting point":
				list($result["mp_low"],$result["mp_high"])=getRange($value);
			break;
			case "hazard statements":
				$result["safety_h"]=str_replace(array("H"," ",","),array("","","-"),$value);
			break;
			case "precautionary statements":
				$result["safety_p"]=str_replace(array("P"," ",","),array("","","-"),$value);
			break;
			}
		}

		$price_table_data=array();
		$lines=array();
		if (preg_match("/(?ims)<table[^>]+id=\"super-product-table\"[^>]*>(.*?)<\/table>/",$body,$price_table_data)
			&& preg_match_all("/(?ims)<tr.*?<\/tr>/",$price_table_data[1],$lines,PREG_PATTERN_ORDER)) {
			$lines=$lines[0];
			foreach ($lines as $line) {
				$cells=array();
				preg_match_all("/(?ims)<td.*?<\/td>/",$line,$cells,PREG_PATTERN_ORDER);
				$cells=$cells[0];

				if (count($cells)<5) {
					continue;
				}

				$amountText=fixTags($cells[0]);
				if (isEmptyStr($amountText)) {
					continue;
				}

				list(,$amount,$amount_unit)=getRange($amountText);
				$price_data=array();
				preg_match("/(?ims)([^\d]*)\(?(\-?[\d\.,]+)\)?/",fixTags($cells[1]),$price_data);

				$result["price"][]=array(
					"supplier" => $this->code, 
					"amount" => $amount, 
					"amount_unit" => strtolower($amount_unit), 
					"price" => $price_data[2]+0.0, 
					"currency" => fixCurrency($price_data[1]), 
					"beautifulCatNo" => $catNo, 
				);
			}
		}

		$result["supplierCode"]=$this->code;
		$result["catNo"]=$catNo;

		//~ var_dump($result);

		return $result;
	}
	
	public function procHitlist(& $response) {
		$body=utf8_decode(@$response->getBody());
		cutRange($body," products-list\""," block-static-block\"");

		$results=array();
		$manyLines=array();
		if (preg_match_all("/(?ims)<div[^>]+class=\"[^\"]*product-item-details[^\"]*\"[^>]*>(.*?)<\/h3>(.*?)<div[^>]+class=\"[^\"]*product-item-inner[^\"]*\"[^>]*>/",$body,$manyLines,PREG_SET_ORDER)) {
			foreach ($manyLines as $line) {
				$preg_data=array();
				$link_match=array();
				if (preg_match("/(?ims)<h3[^>]*>(.*?)<\/h3>/",$line[2],$preg_data)
					&& preg_match("/(?ims)<a[^>]+href=[\'\"].*?\/([^\/\'\"]+)[\'\"][^>]*>/",$line[1],$link_match)) {

					// products
					$prod_matches=array();
					if (preg_match_all("/(?ims)<div[^>]*>(.*?)<\/table>\s*<\/div>/",$line[2],$prod_matches,PREG_PATTERN_ORDER)) foreach ($prod_matches[0] as $prod_match) {
						$nvp_match=array();
						preg_match_all("/(?ims)<p[^>]*>(.*?)<\/p>\s*<p[^>]*>(.*?)<\/p>\s*<\/div>/",$prod_match,$nvp_match,PREG_SET_ORDER);
						$catNo="";
						$casNo="";
						foreach ($nvp_match as $nvp) {
							$name=strtolower(fixTags($nvp[1]));
							$value=fixTags($nvp[2]);
							switch ($name) {
							case "article id":
							case "artikel id":
								$catNo=$value;
								break;
							case "cas":
								$casNo=$value;
								break;
							}
						}

						// prices
						$price_lines=array();
						if (preg_match_all("/(?ims)<tr.*?<\\/tr>/",$prod_match,$price_lines,PREG_PATTERN_ORDER)) foreach ($price_lines[0] as $price_line) {
							$price_cells=array();
							preg_match_all("/(?ims)<td.*?<\/td>/",$price_line,$price_cells,PREG_PATTERN_ORDER);
							$price_cells=$price_cells[0];
							if (count($price_cells)<2) {
								continue;
							}

							list(,$amount,$amount_unit)=getRange(fixTags($price_cells[0]));
							$price_data=array();
							preg_match("/(?ims)([^\d]*)\(?(\-?[\d\.,]+)\)?/",fixTags($price_cells[1]),$price_data);
							$price[]=array(
								"supplier" => $this->code, 
								"cas_nr" => $casNo, 
								"amount" => $amount, 
								"amount_unit" => strtolower($amount_unit), 
								"price" => getNumber($price_data[2]??null), 
								"currency" => fixCurrency($price_data[1]??null), 
								"beautifulCatNo" => $catNo, 
							);
						}

						$results[]=array(
							"name" => rtrim(fixTags($preg_data[1]),"; .:"), 
							"beautifulCatNo" => $catNo, 
							"catNo" => $link_match[1], 
							"supplierCode" => $this->code, 
							"price" => $price, 
						);
					}
				}
			}
		}

		return $results;
	}
}
?>