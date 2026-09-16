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
// Alfa
$GLOBALS["code"]="Alfa";
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "Alfa Aesar";
	public $logo = "logo_alfa_aesar.jpg";
	public $height = 50;
	public $vendor = true; 
	public $excludeFields = array("emp_formula"); // does not allow this search
	public $safety_sym_ghs_map = array("Explosive" => "GHS01","Flammable" => "GHS02","Oxidizing" => "GHS03","Corrosive" => "GHS05","Toxic" => "GHS06","Harmful_Irritant" => "GHS07","Health_hazard" => "GHS08","Environment" => "GHS09");
	public $search_types = array("cas_nr" => "SEARCH_CHOICE_CAS", "name" => "SEARCH_CHOICE_ITEM_NUM");
	
	public $urls=array(
		"server" => "https://www.alfa.com" // startPage
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["search"]=$this->urls["server"]."/en/search/?search-tab=product-search-container&type=";
		$this->urls["detail"]=$this->urls["server"]."/en/catalog/";
		$this->urls["msds"]=$this->urls["server"]."/en/catalog/sds/";
		$this->urls["startPage"]=$this->urls["server"];
    }
	
	public function requestResultList($query_obj) {
		return array(
			"method" => "url",
			"action" => $this->urls["search"].$this->search_types[ $query_obj["crits"][0] ]."&q=".urlencode($query_obj["vals"][0][0])
		);
	}
	
	public function getDetailPageURL($catNo) {
		return $this->urls["detail"].$catNo."/";
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
		$response=@oe_http_get($this->urls["search"].$this->search_types[ $filter ]."&q=".$searchText,$my_http_options);

		if ($response==FALSE) {
			return $noConnection;
		}
		return $this->procHitlist($response);
	}
	
	public function procDetail(& $response,$catNo="") {
		global $default_http_options;
		
		$body=$response->getBody();

		$result=array();
		$result["molecule_names_array"]=array();
		$result["molecule_property"]=array();
		$result["catNo"]=$catNo; // may be overwritten later

		$match=array();
		if (preg_match("/(?ims)<h1[^>]*>(.*?)<\/span>(.*?)<\/h1>/",$body,$match)) {
			$result["catNo"]=fixTags($match[1]);
			$result["molecule_names_array"][]=fixTags($match[2]);
		}

		$match_cells=array();
		if (preg_match_all("/(?ims)<strong[^>]*>(.*?)<\/strong>\s*<\/div>\s*<div[^>]*>(.*?)<\/div>/",$body,$match_cells,PREG_SET_ORDER)) foreach ($match_cells as $match_cell) {
			$name=strtolower(fixTags($match_cell[1]));
			$value=fixTags($match_cell[2]);

			if (isEmptyStr($name) || isEmptyStr($value)) {
				continue;
			}

			switch ($name) {
			case "cas number":
				$result["cas_nr"]=trim($value,"[]");
			break;
			case "synonyms":
				$result["molecule_names_array"][]=$value;
			break;
			case "formula":
				$result["emp_formula"]=$value;
			break;
			case "formula weight":
				$result["mw"]=getNumber($value);
			break;
			case "density":
				$result["density_20"]=getNumber($value);
			break;
			case "refractive index":
				$result["n_20"]=getNumber($value);
			break;
			case "packing group":
				$result["molecule_property"][]=array("class" => "packing_group", "source" => $this->code, "conditions" => $value, );
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
			}
		}

		if (preg_match_all("/(?ims)<strong[^>]*>(.*?)<\/strong>(.*?)<\/p>/",$body,$match_cells,PREG_SET_ORDER)) foreach ($match_cells as $match_cell) {
			$name=fixTags($match_cell[1]);

			switch ($name) {
			case "Hazard Statements:":
				$result["safety_h"]=trim(str_replace("H","",fixTags($match_cell[2])));
			break;
			case "Precautionary Statements:":
				$result["safety_p"]=trim(str_replace("P","",fixTags($match_cell[2])));
			break;
			}
		}

		$safety_sym=array();
		$safety_sym_ghs=array();
		$match_pictogram=array();
		preg_match_all("/(?ims)<img\s+src=\"\/static\/+images\/pictogram\/(\w+)\.gif\"/",$body,$match_pictogram,PREG_PATTERN_ORDER);
		$match_pictogram=$match_pictogram[1];
		foreach ($match_pictogram as $title) {
			if ($value=($this->safety_sym_ghs_map[$title]??false)) {
				$safety_sym_ghs[]=$value;
			}
		}
		$result["safety_sym_ghs"]=join(",",$safety_sym_ghs);

		$result["supplierCode"]=$this->code;
		$result["catNo"]=$catNo;

		// MSDS
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($this->urls["msds"].$catNo,$my_http_options);
		if ($response) {
			$msds_html=$response->getBody();
			if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*\/msds\/[^\"]*USA[^\"]*.pdf)\"[^>]*>/",$msds_html,$match)) {
				$result["default_safety_sheet"]="";
				$result["default_safety_sheet_url"]="-".$this->urls["server"].htmlspecialchars_decode($match[1]);
				$result["default_safety_sheet_by"]=$this->name;
			}
			if (preg_match("/(?ims)<a[^>]*href=\"([^\"]*\/msds\/[^\"]*German[^\"]*.pdf)\"[^>]*>/",$msds_html,$match)) {
				$result["alt_default_safety_sheet"]="";
				$result["alt_default_safety_sheet_url"]="-".$this->urls["server"].htmlspecialchars_decode($match[1]);
				$result["alt_default_safety_sheet_by"]=$this->name;
			}
		}

		//~ var_dump($result);

		return $result;
	}
	
	public function procHitlist(& $response) {
		$body=@$response->getBody();
		cutRange($body,"id=\"products\"","id=\"footer\"");
		//~ die($body);

		if (stripos($body,"Keine Ergebnisse gefunden")===FALSE
			&& stripos($body,"No results found")===FALSE) {
			$preg_data=array();
			$manyLines=array();
			$cells=array();
			if (preg_match("/(?ims)".preg_quote($this->urls["detail"],"/")."(.*)\$/",$response->getEffectiveUrl(),$preg_data)) {
				$results[0]=$this->procDetail($response);
				extendMoleculeNames($results[0]);
				//~ var_dump($results[0]);die();
				$results[0]=array_merge($results[0],array("supplierCode" => $this->code, "beautifulCatNo" => " ", "catNo" => trim($preg_data[1],"/"), ) );
			} elseif (preg_match_all("/(?ims)<li.*?<\/li>/",$body,$manyLines,PREG_PATTERN_ORDER)) {
				$manyLines=$manyLines[0];
				foreach ($manyLines as $line) {
					preg_match_all("/(?ims)<div.*?<\/div>/",$line,$cells,PREG_PATTERN_ORDER);
					$cells=$cells[0];

					if (count($cells)>=4) {
						$cat_no=fixTags($cells[2]);
						$results[]=array(
							"name" => fixTags($cells[3]), 
							"beautifulCatNo" => $cat_no, 
							"catNo" => $cat_no, 
							"supplierCode" => $this->code, 
						);
					}
				}
			}
		}

		return $results;
	}
}
?>