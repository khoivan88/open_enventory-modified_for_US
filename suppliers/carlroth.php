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
		"server" => "https://www.carlroth.com" // startPage
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["base_url"]=$this->urls["server"]."/de/en/";
		$this->urls["search"]=$this->urls["base_url"]."search?text=";
		$this->urls["detail"]=$this->urls["base_url"];
		$this->urls["startPage"]=$this->urls["server"];
    }
	
	public function getPrices($catNo) {
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

		return $this->parsePriceList(utf8_decode(@$response->getBody())); 
	}
	
	public function parsePriceList($body) {
		$result=array(
			"price" => array()
		);

		if (preg_match("/(?ims)<table[^>]*id=\"orderFormTable\"[^>]*>(.*)<\/table>/",$body,$match)) {
			preg_match_all("/(?ims)<tr.*?<\/tr>/",$match[1],$manyLines,PREG_PATTERN_ORDER);
			$manyLines=$manyLines[0];
			//~ var_dump($manyLines);die();

			$headlines=array();
			$num_idx=3;
			$price_idx=5;
			$product_idx=-1;
			$purity_idx=-1;

			for ($b=0;$b<count($manyLines);$b++) {
				preg_match_all("/(?ims)<t[dh].*?<\/t[dh]>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
				$cells=$cells[0];

				if (count($cells)<6) {
					continue;
				}
				$beautifulCatNo=fixTags($cells[2]);
				if ($beautifulCatNo=="Article" || $beautifulCatNo=="Art. No." || $beautifulCatNo=="Order No." || $beautifulCatNo=="Item number") {
					foreach ($cells as $i => $cell) {
						$cell=fixTags($cell);
						if (startswith($cell,"Pack Qty.")) {
							$num_idx=$i;
						}
						elseif ($cell=="Price") {
							$price_idx=$i;
						}
						/* elseif ($cell=="Product") {
							$product_idx=$i;
						}
						elseif ($cell=="Purity") {
							$purity_idx=$i;
						}*/
						$headlines[]=$cell;
					}
				}
				else {
					list(,$amount,$amount_unit)=getRange(fixTags($cells[ $num_idx ]));
					preg_match("/(?ims)([^\d]*)\(?(\-?[\d\.,]+)\)?/",fixTags($cells[ $price_idx ]),$price_match);

					$infoTexts=array();
					if ($product_idx>=0) {
						for ($i=$product_idx;$i<$price_idx;$i++) {
							$value=fixTags($cells[$i]);
							if (!isEmptyStr($value)) {
								$infoTexts[]=$headlines[$i].": ".$value;
							}
						}
					}

					$result["price"][]=array(
						"supplier" => $this->code, 
						"catNo" => $catNo,
						"beautifulCatNo" => $beautifulCatNo, 
						"amount" => $amount, 
						"amount_unit" => $amount_unit, 
						"price" => getNumber($price_match[2]), 
						"currency" => fixCurrency($price_match[1]), 
						"addInfo" => join("; ",$infoTexts),
					);
					//~ var_dump($result["price"]);die();
				}
			}
		}

		return $result;	
	}
	
	public function requestResultList($query_obj) {
		return array(
			"method" => "url",
			"action" => $this->urls["search"].$query_obj["vals"][0][0]
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

		return $this->procDetail($response,$catNo);
	}
	
	public function getHitlist($searchText,$filter,$mode="ct",$paramHash=array()) {
		global $noConnection,$default_http_options;
		
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$my_http_options["referer"]=$this->urls["server"];
		$response=@oe_http_get($this->urls["search"].urlencode($searchText),$my_http_options);

		if ($response==FALSE) {
			return $noConnection;
		}
		return $this->procHitlist($response);
	}
	
	public function procDetail(& $response,$catNo="") {
		$body=utf8_decode(@$response->getBody());
		cutRange($body,"<div id=\"content\"","<footer class=\"wrap\">");
		$body=str_replace(array("\t","\n","\r"),"",$body);

		$result=array();
		$result["molecule_names_array"]=array();
		$result["molecule_property"]=array();
		$result["catNo"]=$catNo; // may be overwritten later

		// MSDS
		if (preg_match("/(?ims)<a[^>]*href=\"(\/medias\/SDB-[^\"]+-EN\.pdf[^\"]*)\"[^>]*>/",$body,$match)) {
			$result["default_safety_sheet"]="";
			$result["default_safety_sheet_url"]="-".$this->urls["server"].htmlspecialchars_decode($match[1]);
			$result["default_safety_sheet_by"]=$this->name;
		}
		if (preg_match("/(?ims)<a[^>]*href=\"(\/medias\/SDB-[^\"]+-DE\.pdf[^\"]*)\"[^>]*>/",$body,$match)) {
			$result["alt_default_safety_sheet"]="";
			$result["alt_default_safety_sheet_url"]="-".$this->urls["server"].htmlspecialchars_decode($match[1]);
			$result["alt_default_safety_sheet_by"]=$this->name;
		}

		if (preg_match("/(?ims)<h1[^>]*>(.*?)(,.*?)?<\/h1>/",$body,$match)) {
			$result["molecule_names_array"][]=fixTags($match[1]);
		}

		$safety_sym_ghs=array();
		$safety_sym_ghs_dict=array(
			"lammable" => "GHS02",
			"intensify" => "GHS03",
			"metals" => "GHS05",
			"death" => "GHS06",
			"irritation" => "GHS07",
			"carcinogenic" => "GHS08",
			"aquatic" => "GHS09"
		);

		if (preg_match("/(?ims)<div[^>]+class=\"hazard-icons-container\"[^>]*>(.*?)<span[^>]+class=\"description\"[^>]*>(.*?)<\/span>.*?<div[^>]*>\s*(H.*?)<div[^>]*>.*?<div[^>]*>\s*(P.*?)<div[^>]*>/",$body,$matches_safety)) {
			// match symbols
			foreach ($safety_sym_ghs_dict as $text => $ghs_sym) {
				if (stripos($matches_safety[1],$text)!==FALSE) {
					$safety_sym_ghs[]=$ghs_sym;
				}
			}

			// match H clauses
			$result["safety_text"]=fixTags($matches_safety[2]);
			$result["safety_h"]=fixTags(str_replace("H","",str_replace(array(" H",",",";","--"),"-",$matches_safety[3])));
			$result["safety_p"]=fixTags(str_replace("P","",str_replace(array(" P",",",";","--"),"-",$matches_safety[4])));
		}
		$result["safety_sym_ghs"]=@join(",",$safety_sym_ghs);

		if (preg_match("/(?ims)Empirical formula (.*?)<br/",$body,$preg_data)) {
			$result["emp_formula"]=fixTags($preg_data[1]);
		}

		if (preg_match("/(?ims)Molar mass \(M\)\s*(.*?)</",$body,$preg_data)) {
			$result["mw"]=getNumber(fixTags($preg_data[1]));
		}

		if (preg_match("/(?ims)Density \(D\)\s*(.*?)</",$body,$preg_data)) {
			$result["density_20"]=getNumber(fixTags($preg_data[1]));
		}

		if (preg_match("/(?ims)Melting point \(mp\)\s*(.*?)\D*C</",$body,$preg_data)) {
			list($result["mp_low"],$result["mp_high"])=getRange(fixTags($preg_data[1]));
		}

		if (preg_match("/(?ims)Boiling point \(bp\)\s*(.*?)\D*C</",$body,$preg_data)) {
			list($result["bp_low"],$result["bp_high"])=getRange(fixTags($preg_data[1]));
			$result["bp_press"]=1;
			$result["press_unit"]="bar";
		}

		if (preg_match("/(?ims)Flash point \(flp\)\s*(.*?)\D*C/",$body,$preg_data)) {
			$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => getNumber(fixTags($preg_data[1])), "unit" => "°C");
		}

		if (preg_match("/(?ims)UN-Nr\. (.*?)</",$body,$preg_data)) {
			$result["molecule_property"][]=array("class" => "UN_No", "source" => $this->code, "conditions" => fixTags($preg_data[1]));
		}

		if (preg_match("/(?ims)WGK (.*?)</",$body,$preg_data)) {
			$result["safety_wgk"]=fixTags($preg_data[1]);
		}

		if (preg_match("/(?ms)CAS.N[ro]\.\s*\[?(.*?)\]?\s*</",$body,$preg_data)) { // be case-sensitive
			$result["cas_nr"]=fixTags($preg_data[1]);
		}

		$result["supplierCode"]=$this->code;
		$result["catNo"]=$catNo;

		//~ var_dump($result);

		return $result;
	}
	
	public function procHitlist(& $response) {
		//~ var_dump($response);die();
		$body=@$response->getBody(); // utf8_decode(
		cutRange($body,"<div class=\"results\"","<footer");
		$body=str_replace(array("\t","\n","\r"),"",$body);

		$results=array();
		if (stripos($body,"You searched for")!==FALSE) {
			if (preg_match_all("/(?ims)<a[^>]+class=\"name\"[^>]+href=\"\/.*?\/en\/([^\"]*)\".*?>(.*?)<\/a>.*?<div[^>]* class=\"purityLevel\"[^>]*>(.*?)<\/div>.*?<div[^>]* class=\"stockstatus\"[^>]*>(.*?)<\/div>/",$body,$manyLines,PREG_SET_ORDER)) {
				foreach ($manyLines as $line) {
					$results[]=array(
						"name" => fixTags($line[2]), 
						"beautifulCatNo" => str_replace("Art. No. ","",fixTags($line[1])), 
						"catNo" => fixTags($line[1]), 
						"supplierCode" => $this->code, 
					);
				}
			}
		} elseif (stripos($body,"No results found")===FALSE
			&& stripos($body,"find any results for your search")===FALSE
			&& preg_match("/(?ims)".preg_quote($this->urls["base_url"],"/")."(.*)\$/",$response->getEffectiveUrl(),$preg_data)) {
			$results[0]=$this->procDetail($response);
			extendMoleculeNames($results[0]);
			//~ var_dump($results[0]);die();
			$results[0]=array_merge($results[0],array("supplierCode" => $this->code, "beautifulCatNo" => " ", "catNo" => $preg_data[1], ) );
		}

		return $results;
	}
}
?>