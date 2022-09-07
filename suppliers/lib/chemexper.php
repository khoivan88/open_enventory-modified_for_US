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
abstract class ChemExperSupplier extends Supplier {
	public $searchType = array(1 => "(rn.value=?)", 2 => "(mf.value=?)", 3 => "(catalog.description=~?%20or%20iupac.value=~?%20or%20iupac.value%3C%25%3E?%20or%20keyword.value%3C%25%3E?)", );
	public $searchTypeCode = array("cas_nr" => 1, "emp_formula" => 2, "molecule_name" => 3);
	public $urls = array(
		"server2" => "http://www.chemexper.com",
		
		"search2" => "%20and%20(catalogLine.country%3DDE%20cand%20catalogLine.currency%3DEUR)&currency=EUR&language=&country=NULL&forGroupNames=",
		"search3" => "&action=PowerSearch&format=ccd2013%2Cccd&sort=rn.value,rn._asGroupsID&target=entry&searchValue=",
		
		"detail2" => "&target=entry&action=PowerSearch&from=0&format=ccd2013%2Cccd&country=NULL&currency=EUR&history=off&forGroupNames=",
		"detail3" => "&realQuery=(rn.value%3D%22108-88-3%22)+and+(catalogLine.country%3DDE+cand+catalogLine.currency%3DEUR)",
		"detail4" => "&language=",
	);

	function __construct() {
		$this->code = $GLOBALS["code"];
		$this->urls["search"] = $this->urls["server2"]."/cheminfo/servlet/org.dbcreator.MainServlet?&searchTemplate=";
		$this->urls["detail"] = $this->urls["server2"]."/cheminfo/servlet/org.dbcreator.MainServlet?sort=&query=entry._entryID%3D";
	}
	
	function requestResultList($query_obj) {
		$search_type_code=$this->searchTypeCode[ $query_obj["crits"][0] ];
		return array(
			"method" => "url",
			"action" => $this->getSearchURL($this->searchType[$search_type_code],$query_obj["vals"][0][0])
		);
	}
	function getDetailPageURL($catNo) {
		$search_type_code=substr($catNo,0,1);
		$searchText=substr($catNo,1);
		if ($search_type_code==0) {
			$retval=$this->urls["detail"].urlencode($searchText).$this->urls["detail2"].$this->forGroupNames.$this->urls["detail3"].$this->chemExperServer.$this->urls["detail4"];
		}
		else {
			$retval=$this->getSearchURL($this->searchType[$search_type_code],$searchText);
		}
		return $retval."&referrer=enventory";
	}
	function getSearchURL($search_type,$searchText) {
		return $this->urls["search"].$search_type.$this->urls["search2"].$this->forGroupNames.$this->chemExperServer.$this->urls["search3"].urlencode($searchText);
	}
	function getInfo($catNo) {
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
	function procDetail(& $response, $catNo="") {
		global $default_http_options;

		$body=utf8_encode(@$response->getBody());
		$body=str_replace(array("&nbsp;"),array(" "),$body);
		// remove all html comments
		$body=preg_replace("/(?ims)<script.*?<\/script>/","",$body);
		$body=preg_replace("/(?ims)<style.*?<\/style>/","",$body);
		$body=preg_replace("/(?ims)<!--.*?-->/","",$body);
		//~ die($body);

		$sections=explode("<tr valign=\"middle\">",$body);
		//~ print_r($sections);die();

		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$result["safety_sym_ghs"]=array();
		for ($c=0;$c<count($sections);$c++) {
			$block=$sections[$c];
			if (strpos($block,"^")!==FALSE) {
				list($section_name,$block)=explode("^",$block);
				$section_name=strtolower(fixTags($section_name));
			}
			else { // first one
				// molfile
				$molfile=array();
				if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*olfile)\"[^>]*>/",$block,$molfile)) {
					$molfile_response=oe_http_get($this->urls["server2"].$molfile[1],$my_http_options);
					if ($molfile_response!=false) {
						$result["molfile_blob"]=addPipes(@$molfile_response->getBody());
					}
				}
				$section_name="general";
			}

			if ($section_name=="safety") {
				preg_match_all("/(?ims)<td.*?<\/td>/",$block,$cells,PREG_PATTERN_ORDER);
				$cells=$cells[0];
				for ($d=0;$d<count($cells);$d++) {
					$cell=$cells[$d];
					$name=strtolower(fixTags($cell));
					if (in_array($name,array("hazard", "ghs pictogram", "risk", "safety", "ghs h statement", "ghs p statement", "material safety data sheet"))) {
						$current_section=$name;
						continue;
					}

					$ghs_matches=array();
					switch ($current_section) {
					case "hazard":
						if (preg_match_all("/(?ims)(\w+):/",$cell,$ghs_matches,PREG_PATTERN_ORDER)) {
							$ghs_matches=$ghs_matches[1]; // take only matches
							for ($e=0;$e<count($ghs_matches);$e++) {
								$result["hazard"][]=ucwords($ghs_matches[$e]);
							}
						}
					break;
					case "ghs pictogram":
						// match all #.png by RegExp and prepend GHS0
						preg_match_all("/(?ims)<img [^>]*src=\"\D+(\d+)\.png\"[^>]*>/",$cell,$ghs_matches,PREG_PATTERN_ORDER);
						$ghs_matches=$ghs_matches[1]; // take only matches
						for ($e=0;$e<count($ghs_matches);$e++) {
							$result["safety_sym_ghs"][]="GHS0".$ghs_matches[$e];
						}
					break;
					case "risk":
					case "safety":
					case "ghs h statement":
					case "ghs p statement":
						$preg_data=array();
						if (preg_match("/(?ims)(^.*?\d.*?):/",$cell,$preg_data)) {
							$preg_data=trim(str_replace(array("H", "P", " "),"",$preg_data[1]));
							if (!isEmptyStr($preg_data)) {
								$result[$current_section][]=fixTags($preg_data);
							}
						}
					break;
					/* broken 20200630
					 case "material safety data sheet":
						if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*Language=en[^\"]*)\"[^>]*>/",$cell,$msds)) {
							$msds_response=oe_http_get($msds[1],$my_http_options);
							$result["default_safety_sheet"]="";
							$result["default_safety_sheet_url"]="-".htmlspecialchars_decode($msds[1]);
							$result["default_safety_sheet_by"]=$this->name"];
						}
					break;*/
					}
				}
			}
			else {
				$cells=preg_split("/(?ims)<td[^>]+class[^>]+>/",$block);

				for ($d=0;$d<count($cells)-1;$d++) {
					$name=strtolower(fixTags($cells[$d]));
					$rawValue=$cells[$d+1];
					$value=fixTags($rawValue);

					if (isEmptyStr($name) || isEmptyStr($value)) {
						continue;
					}

					switch ($name) {
					case "iupac name":
						$nameCells=array();
						preg_match_all("/(?ims)<td.*?<\/td>/",$rawValue,$nameCells,PREG_PATTERN_ORDER);
						$nameCells=$nameCells[0];
						for ($e=0;$e<count($nameCells);$e++) {
							$result["molecule_names_array"][]=$nameCells[$e];
						}
					break;
					case "registry number":
						$result["cas_nr"]=$value;
					break;
					case "molecular formula":
						$result["emp_formula"]=str_replace(" ","",$value);
					break;
					case "molecular weight":
						$result["mw"]=$value;
					break;
					case "pack size": // 12
						$currency=array();
						preg_match("/(?ims)Price\((.*?)\)/",$value,$currency);
						$currency=$currency[1];

						for ($e=$d+2;$e<count($cells)-2;$e+=5) {
							// Bestellno 14,19,24
							// Amount 15,20,25
							// Price 16,21,26
							$amount_parts=explode(" ",$cells[$e+1]);
							$amount_unit=array_pop($amount_parts);
							$amount=join(" ",$amount_parts);
							$result["price"][]=array(
								"supplier" => $this->code, 
								"amount" => $amount, 
								"amount_unit" => strtolower($amount_unit), 
								"price" => $cells[$e+2], 
								"currency" => $currency, 
								"beautifulCatNo" => $cells[$e], 
							);
						}
					break;
					case "density":
					case "density (g/cm&#179;)":
						$result["density_20"]=$value;
					break;
					case "refractive index":
						$result["n_20"]=getNumber($value);
					break;
					case "boiling point (&#176;c)":
						list($result["bp_low"],$result["bp_high"],$press)=getRange(str_replace("&deg;","",$value));
						$bp_split=array();
						preg_match("/(?ims)C(?:\s\((.*?)(mmHg).*?)?/",$press,$bp_split);
						if (isEmptyStr($result["bp_high"])) {
							// do nothing
						}
						elseif (empty($bp_split[1]) && empty($bp_split[2])) { // fix no press given
							$result["bp_press"]=1;
							$result["press_unit"]="bar";
						}
						elseif (strpos($bp_split[2],"mm")!==FALSE && strpos($bp_split[2],"Hg")!==FALSE) {
							$result["bp_press"]=$bp_split[1];
							$result["press_unit"]="torr";
						}
					break;
					case "melting point (&#176;c)":
						list($result["mp_low"],$result["mp_high"])=getRange($value);
					break;
					case "flash point (&#176;c)":
						if (!isEmptyStr($value)) {
							$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => getNumber($value), "unit" => "°C");
						}
					break;
					}
				}
			}
		}

		$result["safety_r"]=joinIfNotEmpty($result["risk"]??"","-");
		$result["safety_s"]=joinIfNotEmpty($result["safety"]??"","-");
		$result["safety_h"]=joinIfNotEmpty($result["ghs h statement"]??"","-");
		$result["safety_p"]=joinIfNotEmpty($result["ghs p statement"]??"","-");
		$result["safety_sym"]=joinIfNotEmpty($result["hazard"]??"",",");
		$result["safety_sym_ghs"]=joinIfNotEmpty($result["safety_sym_ghs"]??"",",");
		unset($result["risk"]);
		unset($result["safety"]);
		unset($result["hazard"]);
		unset($result["ghs h statement"]);
		unset($result["ghs p statement"]);
		$result["supplierCode"]=$this->code;

		return $result;
	}
	function getHitlist($searchText,$filter,$mode="ct",$paramHash=array()) {
		global $noConnection,$default_http_options;

		$search_type_code=$this->searchTypeCode[ $filter ];
		$search_type=$this->searchType[$search_type_code];
		$url=$this->getSearchURL($search_type,$searchText);
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($url,$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}

		return $this->procHitlist($response,$search_type_code.$searchText);
	}
	function procHitlist(& $response,$catNo) {
		global $noResults;

		$body=@$response->getBody();
		$body=str_replace(array("&nbsp;"),array(" "),$body);
		//~ die($body);

		if (strpos($body,"No product found")!==FALSE) {
			return $noResults;
		}

		$showFwPos = strpos($body,"Reference</a>");
		$results=array();
		if ($showFwPos===FALSE) {
			// only one
			$results[0]=$this->procDetail($response);
			extendMoleculeNames($results[0]);
			$results[0]["name"]=$results[0]["molecule_name"];
			$results[0]["supplierCode"]=$this->code;
			$results[0]["catNo"]=$catNo;
			$results[0]["beautifulCatNo"]=" "; // hide cheat
			//~ var_dump($results);die();
		}
		else {
			$body=substr($body,$showFwPos);
			$manyLines=array();
			$cells=array();
			$priceLines=array();
			$priceCells=array();
			preg_match_all("/(?ims)<tr.*?>.*?<\/table>/",$body,$manyLines,PREG_PATTERN_ORDER);
			$manyLines=$manyLines[0];
			//~ var_dump($manyLines);

			for ($c=0;$c<count($manyLines);$c++) {
				list($info,$prices)=explodeSafe("<table",$manyLines[$c],2);
				preg_match_all("/(?ims)<td.*?<\/td>/",$info,$cells,PREG_PATTERN_ORDER);
				$cells=$cells[0];

				if (count($cells)<2) {
					continue;
				}

				list($name)=explode("</a>",$cells[1],2);

				if (!preg_match("/(?ims)_entryID%3D(.*?)&/",$name,$catNo)) {
					continue;
				}
				$catNo="0".$catNo[1];

				preg_match_all("/(?ims)<tr.*?<\/tr>/",$prices,$priceLines,PREG_PATTERN_ORDER);
				$priceLines=$priceLines[0];
				$prices=array();
				for ($i=0;$i<count($priceLines);$i++) {
					preg_match_all("/(?ims)<td.*?<\/td>/",$priceLines[$i],$priceCells,PREG_PATTERN_ORDER);
					$priceCells=$priceCells[0];
					if (count($priceCells)<4) {
						continue;
					}
					list(,$price,$currency)=getRange(fixTags($priceCells[2]));
					$prices[]=array("price" => fixTags($price), "currency" =>  fixTags($currency), "beautifulCatNo" => fixTags($priceCells[0]), "amount" => fixTags($priceCells[1]), );
				}
				$results[]=array("name" => fixTags($name), "supplierCode" => $this->code, "catNo" => $catNo, "price" => $prices, );
			}
		}
		return $results;
	}
}
?>