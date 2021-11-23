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
// Merck
$GLOBALS["code"]="merck";
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "Merck";
	public $logo = "logo_merck.gif";
	public $height = 40;
	public $vendor = true;
	public $urls=array(
		"server" => "https://www.merckmillipore.com"
	);
	public $testCas = array("67-64-1" => array(
			array("acetone"),
		)
	);
	public $safety_sym_dict = array(
		"Corrosive" => "C",
		"Explosive" => "E",
		"Highly flammable " => "F+",
		"Flammable" => "F",
		"Dangerous for the environment " => "N",
		"Oxidising" => "O",
		"Very toxic " => "T+",
		"Toxic" => "T",
		"Irritant" => "Xi",
		"Harmful" => "Xn",
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["search"]=$this->urls["server"]."/DE/en/search/%2B";
		$this->urls["search_suffix"]="/browse?SynchronizerToken=&TrackingSearchType=filter&SearchTerm=%2B";
		$this->urls["search_suffix2"]="&SelectedSearchResult=SFProductSearch&PageSize=20";
		$this->urls["detail"]=$this->urls["server"]."/DE/en/product/";
		$this->urls["startPage"]=$this->urls["server"];
    }
	
	public function requestResultList($query_obj) {
		return array(
			"method" => "url",
			"action" => $this->urls["search"].urlencode($query_obj["vals"][0][0]).$this->urls["search_suffix"].urlencode($query_obj["vals"][0][0]).$this->urls["search_suffix2"]
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
		
		$url=$this->urls["search"].urlencode($searchText).$this->urls["search_suffix"].urlencode($searchText).$this->urls["search_suffix2"];
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$my_http_options["cookies"]=array(
			"CookieAcceptance" => "accepted",
			"SelectedCountry" => "DE",
			"SelectedCountryCode" => "DE",
			"PreferredChannel" => "Merck-DE-Site",
			"PreferredLocale" => "en_US",
		);
		$response=oe_http_get($url,$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}

		return $this->procHitlist($response);
	}
	
	public function procDetail(& $response,$catNo="") {
		$body=@$response->getBody();
		$body=trimNbsp($body);
	//~ 	die($body);
		if (strpos($body,"Fehlermeldung")!==FALSE) {
			return $noConnection;
		}
		if (preg_match("/(?ims)id=\"pdp-sort-description-for-print\"[^>]*>(.*)<[^>]*id=\"relations\"/",$body,$cut)) {
			$body=$cut[1];
		}

		$result=array();
		// MSDS
		if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*)\"[^>]*>[^<>]*SDS[^<>]*<\/a>/",$body,$match)) {
			$my_http_options=$default_http_options;
			$my_http_options["redirect"]=maxRedir;
			$response=@oe_http_get($match[1],$my_http_options);
			if ($response) {
				$msds_html=@$response->getBody();

				if (preg_match("/(?ims)data=\"([^\"]*&Language=EN&[^\"]*)\"\s+/",$msds_html,$match)) {
					$result["default_safety_sheet"]="";
					$result["default_safety_sheet_url"]="-".$match[1];
					$result["default_safety_sheet_by"]=$this->name;
				}
				if (preg_match("/(?ims)data=\"([^\"]*&Language=DE&[^\"]*)\"\s+/",$msds_html,$match)) {
					$result["alt_default_safety_sheet"]="";
					$result["alt_default_safety_sheet_url"]="-".$match[1];
					$result["alt_default_safety_sheet_by"]=$this->name;
				}
			}
		}

		if (preg_match("/(?ims)<h1[^>]*>(.*?)<\/span>(.*?)<\/h1>/",$body,$name_data)) {
			$catNo=fixTags($name_data[1]);
			$result["molecule_names_array"][]=fixTags($name_data[2]);
		}

		// synonyms impossible to use

		$manyLines=explode("<tr",$body);
		//~ print_r($manyLines);die();
		for ($b=0;$b<count($manyLines);$b++) {
			$cells=explode("<td",$manyLines[$b]);
			//~ print_r($cells);

			if (count($cells)!=3) {
				continue;
			}
			array_shift($cells);
			// put <td back for validity
			$cells[0]="<td".$cells[0];
			$cells[1]="<td".$cells[1];

			$name=strtolower(fixTags($cells[0]));
			$value=fixTags($cells[1]);

			//~ echo $name."X".$value."\n";

			if ($name=="hazard pictogram(s)") {
				preg_match_all("/(?ims)<img[^>]*src=\"[^\"]*\/([^\"\/]*)\.gif\"/",$cells[1],$ghs,PREG_PATTERN_ORDER);
				$result["safety_sym_ghs"]=implode(",",$ghs[1]);
			}
			elseif ($value=="") {
				continue;
			}

			if ($name=="hill formula") {
				$result["emp_formula"]=str_replace(array(
				"\x20\x80","\xe2\x82\x80",
				"\x20\x81","\xe2\x82\x81",
				"\x20\x82","\xe2\x82\x82",
				"\x20\x83","\xe2\x82\x83",
				"\x20\x84","\xe2\x82\x84",
				"\x20\x85","\xe2\x82\x85",
				"\x20\x86","\xe2\x82\x86",
				"\x20\x87","\xe2\x82\x87",
				"\x20\x88","\xe2\x82\x88",
				"\x20\x89","\xe2\x82\x89",
				),array(
				"0","0",
				"1","1",
				"2","2",
				"3","3",
				"4","4",
				"5","5",
				"6","6",
				"7","7",
				"8","8",
				"9","9",
				),$value);
			}
			elseif ($name=="molar mass") {
				$result["mw"]=$value;
			}
			elseif ($name=="cas number") {
				$result["cas_nr"]=$value;
			}
			elseif ($name=="melting point") {
				list($result["mp_low"],$result["mp_high"])=getRange($value);
			}
			elseif ($name=="density") {
				$result["density_20"]=getNumber($value);
			}
			elseif ($name=="boiling point") {
				list($result["bp_low"],$result["bp_high"])=getRange($value);
			}
			elseif ($name=="refractive index") {
				$result["n_20"]=getNumber($value);
			}
			elseif ($name=="hazard statement(s)") {
				$result["safety_h"]=$this->getClauses($cells[1], "H");
			}
			elseif ($name=="precautionary statement(s)") {
				$result["safety_p"]=$this->getClauses($cells[1], "P");
			}
			elseif ($name=="signal word") {
				$result["safety_text"]=$value;
			}
			elseif ($name=="wgk") {
				$result["safety_wgk"]=getNumber($value);
			}
			elseif ($name=="r phrase") {
				if (preg_match("/(?ims)R (.*)<br/",$value,$cut)) {
					$result["safety_r"]=$cut[1];
				}
			}
			elseif ($name=="s phrase") {
				if (preg_match("/(?ims)S (.*)<br/",$value,$cut)) {
					$result["safety_s"]=$cut[1];
				}
			}
			elseif ($name=="ec number") {
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "EG_No", "source" => $this->code, "conditions" => $value);
				}
			}
			elseif ($name=="ignition temperature") {
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "Autoign_temp", "source" => $this->code, "value_high" => getNumber($value), "unit" => "°C");
				}
			}
			elseif ($name=="solubility") {
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "Sol_text", "source" => $this->code, "conditions" => $value);
				}
			}
			elseif ($name=="vapor pressure") {
				$value=str_replace(array("&#x00b0;"),array("°"),$value);
				if (preg_match("/(?ims)([\d\.,]+)\s*(\w+)\s*\((.*?)\)/",$value,$vap_press_data)) {
					$result["molecule_property"][]=array("class" => "Vap_press", "source" => $this->code, "value_high" => getNumber($vap_press_data[1]), "unit" => $vap_press_data[2], "conditions" => $vap_press_data[3]);
				}
			}
			elseif ($name=="explosion limit") {
				if (!isEmptyStr($value)) {
					list($low,$high)=getRange($value);
					$result["molecule_property"][]=array("class" => "Ex_limits", "source" => $this->code, "value_low" => getNumber($low), "value_high" => getNumber($high), "unit" => "Vol.-%");
				}
			}
			elseif ($name=="flash point") {
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => getNumber($value), "unit" => "°C");
				}
			}
			elseif ($name=="declaration (railroad and road) adr, rid") {
				$result["molecule_property"][]=array("class" => "adr", "source" => $this->code, "conditions" => $value);
			}
			elseif ($name=="declaration (transport by sea) imdg-code") {
				$result["molecule_property"][]=array("class" => "imdg", "source" => $this->code, "conditions" => $value);
			}
			elseif ($name=="declaration (transport by air) iata-dgr") {
				$result["molecule_property"][]=array("class" => "iata", "source" => $this->code, "conditions" => $value);
			}
			elseif ($name=="ld 50 oral") {
				$result["molecule_property"][]=array("class" => "LD50_or", "source" => $this->code, "conditions" => $value);
			}
			elseif ($name=="ld 50 dermal") {
				$result["molecule_property"][]=array("class" => "LD50_derm", "source" => $this->code, "conditions" => $value);
			}
			elseif (startswith($name,"hazard symbol")) {
				$safety_sym=array();
				foreach ($this->safety_sym_dict as $search => $sym) {
					if (strpos($cells[1],$search)!==FALSE) {
						$safety_sym[]=$sym;
					}
				}
				$result["safety_sym"]=implode(",",$safety_sym);
			}
		}
		//~ var_dump($result);die();

		$result["supplierCode"]=$this->code;
		$result["catNo"]=$catNo;
		return $result;
	}
	
	public function getClauses($html,$type) {
		$clauses=array();
		$rows=explode("<br",$html);
		if (is_array($rows)) foreach($rows as $row) {
			if (preg_match("/(?ims)".$type."(.*?):/",fixTags($row),$cut)) {
				$clauses[]=$cut[1];
			}
		}
		return str_replace(array(" ",$type),"",implode("-",$clauses));
	}
	
	public function procHitlist(& $response) {
		$body=str_replace("<span class=\"ish-searchTerm\"></span>", "",@$response->getBody()); // remove garbage

		// echo $body;
		$results=array();
		if (strpos($body,"esults for")!==FALSE) {
			if (preg_match("/(?ims)id=\"Products\"[^>]*>(.*)<footer/",$body,$cut)) {
				$body=$cut[1];
			}

			preg_match_all("/(?ims)<section[^>]*class=\"product\"[^>]*>(.*?)<\/section>/",$body,$sections,PREG_PATTERN_ORDER);
			$sections=$sections[1];
	//~ 		print_r($sections);
			for ($b=0;$b<count($sections);$b++) {
				preg_match("/(?ims)<div[^>]*class=\"container-serp\"[^>]*>.*?<a[^>]*href=\"([^\"]+)\"[^>]*>(.*?)<\/a>(.*?)<\/span>(.*?)<\/h2>(.*?)<\/span>/",$sections[$b],$data_match);

				$slashpos=strrpos($data_match[1],"/");
				$results[]=array(
					"name" => fixTags($data_match[4]),
					"addInfo" => fixTags($data_match[3])." ".fixTags($data_match[5]),
					"beautifulCatNo" => fixTags($data_match[2]),
					"catNo" => fixTags(substr($data_match[1],$slashpos+1)),
					"supplierCode" => $this->code, 
				);
			}
		}
		elseif (strpos($body,"nclose multi-term phrases")===FALSE && preg_match("/(?ims)<link[^>]+rel=\"canonical\"[^>]+href=\"([^\"]*)\"[^>]*>/",$body,$data_match)) {
			$slashpos=strrpos($data_match[1],"/");
			$results[0]=$this->procDetail($response);
			extendMoleculeNames($results[0]);
			$results[0]=array_merge($results[0],array("name" => $results[0]["molecule_name"], "catNo" => fixTags(substr($data_match[1],$slashpos+1)), "supplierCode" => $this->code, ) );
		}
		else {
			return $noResults;
		}
		// print_r($results);
		return $results;
	}
}
?>