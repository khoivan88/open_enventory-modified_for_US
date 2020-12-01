<?php
/*
This module was written by Konstantin Troshin@UCB inspired by the module made by Felix Rudolphi
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
// ChemicalBook
$GLOBALS["code"]="ChemicalBook";
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "ChemicalBook";
	public $logo = "chembook.gif";
	public $height = 36; 
	public $urls=array(
		"server" => "https://www.chemicalbook.com" // startPage
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["base"]=$this->urls["server"]."/Search_EN.aspx?keyword=";
		$this->urls["startPage"]=$this->urls["server"]; // startPage
    }
	
	public function requestResultList($query_obj) {
		return array(
			"method" => "url",
			"action" => $this->urls["base"].$query_obj["vals"][0][0]
		);
	}
	
	public function getDetailPageURL($catNo) {
		return $this->urls["base"].$catNo;
	}
	
	public function getInfo($catNo) {
		global $noConnection,$default_http_options;
		
		$url=$this->urls["server"]."/ProductChemicalProperties".$catNo."_EN.htm";
		if (empty($url)) {
			return $noConnection;
		}
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($url,$my_http_options);
		$body=utf8_encode(@$response->getBody());
		if ($response==FALSE) {
			return $noConnection;
		}
		return $this->procDetail($body);
	}
	
	public function getHitlist($searchText,$filter,$mode="ct",$paramHash=array()) {
		global $noConnection,$default_http_options;
		
		$baseurl=$this->urls["base"];
		$srch=$searchText; //process the value to other functions. Needed to filter out erroneusly found entries sometimes returned by ChemicalBook
		$url=$baseurl.urlencode($searchText);	
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($url,$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}
		$body=utf8_encode(@$response->getBody());
		return $this->procHitlist($response,$srch,$filter);
	}
	
	public function getBestHit(& $hitlist,$name=NULL) {
		$a=0;
		for($i=0;$i<count($hitlist);$i++) {
			if (count(array_filter($hitlist[$i])) > count(array_filter($hitlist[$a]))) {
				$a=$i;
			}
		}
		return $a;
	}
	
	public function procDetail($body,$catNo="") {
		$body=utf8_encode(str_replace("&nbsp;"," ",$body));

		preg_match("/(?ims)Mol\s?file:.*?<a href=\'?(.*?\.mol)/",$body,$lk);
		$molurl=preg_replace("/(?ims)\.\./","",$lk[1]);

		preg_match_all("/(?ims)<td.*?<\/td>/",$body,$cells,PREG_PATTERN_ORDER);
		$cells=$cells[0];
		//var_dump($cells);
		$newEntry=array("supplierCode" => $this->code);
		$result=array();
		if (is_array($cells)) foreach ($cells as $cell) {
			$current=fixTags($cell).trim("\0\x09");
			if ($current!="") {
				switch ($previous) {
				case "cbnumber":
					$result["beautifulCatNo"]=$current;
				break;
				case "product name:":
					$result["molecule_names_array"][0]=$current;
				break;
				case "cas:":
					$result["cas_nr"]=$current;
				break;
				case "mf:":
					$result["emp_formula"]=$current;
				break;
				case "mw:":
					if ($current!="0") { # chemical book may have this bogus value
						$result["mw"]=$current+0.0;
					}
				break;
				case "hazard codes":
					$result["safety_sym"]=$current;
				break;
				case "risk statements":
					$result["safety_r"]=$current;
				break;
				case "safety statements":
					$result["safety_s"]=$current;
				break;
				case "ridadr":
					$result["molecule_property"][]=array("class" => "adr", "source" => $this->code, "conditions" => $current);
				break;
				case "wgk germany":
					$result["safety_wgk"]=$current;
				break;
				case "mp":
					list($result["mp_low"],$result["mp_high"])=getRange($current);
				break;
				case "density":
					$result["density_20"]=getNumber($current);
				break;
				case "bp":
					list($result["bp_low"],$result["bp_high"],$press)=getRange($current);
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
				break;
				case "refractive index":
					$result["n_20"]=getNumber($current);
				break;
				case "fp":
					$val=getNumber($current);
					$unt="°C";
					$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => $val, "unit" => $unt);
				break;
				case "vapor pressure":
					if (strpos($current,"Hg")!==FALSE) {
						$unt="torr";
					}
					elseif (strpos($current,"bar")!==FALSE) {
						if (strpos($current,"mbar")!==FALSE) {
							$unt="mbar";
						}
						else {
							$unt="bar";
						}
					}
					else {
						$unt="unknown";
					}
					if (strpos($current,"C")!==FALSE) {
						$tempunt=" °C";
					}
					elseif (strpos($current,"K")!==FALSE) {
						$tempunt=" K";
					}
					$vap_press=explode(" ",$current);
					$result["molecule_property"][]=array("class" => "Vap_press", "source" => $this->code, "value_high" => $vap_press[0], "unit" => $unt, "conditions" => $vap_press[4].$tempunt);
				break;
				}
			}
			$previous=strtolower($current);
		}
		$response2=oe_http_get($this->urls["server"].$molurl,$my_http_options);
		if ($response2!=FALSE && ($status=$response2->getStatus())>=200 && $status<300) {
			$test=@$response2->getBody();
		}
		$patt1="/V/";
		$patt2="/M(.*?)END/";
		$patt3="/GIF89a/";
		if(preg_match($patt1,$test) && preg_match($patt2,$test)) {
			$result["molfile_blob"]=$test;
		}
		else {
			if(preg_match($patt3,$test)) {
				$result["gif_file"]=$test;
			}
		}

		$result["supplierCode"]=$this->code;
		return $result;
	}
	
	public function procHitlist(& $response,$srch,$filter) {
		if ($filter!=="molecule_name" && $filter!=="emp_formula"){ //check what is the topic of search
			$patt="/[0-9]+\-[0-9][0-9]\-[0-9]/";
			if (!preg_match($patt,$srch)){ //If neither name nor empirical formula, check whether the search text is a CAS number, proceed if true 
				return $noResults;
			}
		}
		$body=utf8_encode(@$response->getBody());
		$baseurl=$this->urls["base"];
		if (strpos($body,"No results found")!==FALSE) {
			return $noResults;
		}
		else {
			cutRange($body,"<table width=\"100%\">","</html>");
			$manyLines=explode("<table class=\"mid\">",$body);
			// remove first element
			array_shift($manyLines);
			$result=array();
			if (is_array($manyLines)) foreach ($manyLines as $line) {
				if ($filter!=="molecule_name" && $filter!=="emp_formula") {
					if (strpos($line,$srch)===FALSE) {
						continue;
					}
				}
				preg_match_all("/(?ims)<td.*?<\/td>/",$line,$cells,PREG_PATTERN_ORDER);
				$cells=$cells[0];
				$newEntry=array("supplierCode" => $this->code);
				$previous=0; //initialize $previous for switch
				if (count($cells)) {
					foreach ($cells as $cell) {
						$current=fixTags($cell);
						switch($previous) {
						case "Chemical Name:":
							$newEntry["name"]=$current;
						break;
						case "CBNumber:":
							$newEntry["catNo"]=$current;
						break;
						case "CAS No.:":
							$newEntry["addInfo"]=$current;
						break;
						case "Molecular Formula:":
							$newEntry["emp_formula"]=$current;
						break;
						case "Formula Weight:":
							$newEntry["MW"]=$current;
						break;
						}
						$previous=$current;
					}
					if  (!preg_match("/(?ims)[a-z]/",$newEntry["name"])) { //check whether there is a chemical name, proceed to detail page if the name is missing (sometimes CB has CAS numbers instead of names)
						$catNo=$newEntry["catNo"];
						if($catNo) {
							$response2=oe_http_get($this->urls["server"]."/ProductChemicalProperties".$catNo."_EN.htm",$my_http_options);  //get the detailed page
							$body=@$response2->getBody();
							$res=$this->procDetail($body); //get the Name from the detailed page
							$newEntry["name"]=$res["molecule_names_array"][0];
						}
					}
					$result[]=$newEntry;
				}
			}
			if (count($result)==1) {
				$catNo=$result[0]["catNo"];
				if($catNo) {
					$response2=oe_http_get($this->urls["server"]."/ProductChemicalProperties".$catNo."_EN.htm",$my_http_options);  //get the detailed page
					$body=@$response2->getBody();
					$result=array();
					$result[0]=$this->procDetail($body); //process the detailed page to procDetail
					$result[0]["catNo"]=$catNo;
					$result[0]["addInfo"]=$result[0]["cas_nr"];
					extendMoleculeNames($result[0]);
				}
			}
			return $result;
		}
	}
	
	public function getCASfromName($name) {
		$name=strtolower($name);
		$hitlist=$this->getHitlist($name,"molecule_name");
		if (count($hitlist)==1) {
			return $hitlist[0]["cas_nr"];
		}
		else {
			for($i=0;$i<count($hitlist);$i++) {
				extendMoleculeNames($hitlist[$i]);
				if (preg_match("/".$name."/",strtolower($hitlist[$i]["name"]))) {
					$res=$hitlist[$i]["addInfo"];
					break;
				}
			}
			if(!$res) {
				return $hitlist[0]["addInfo"];
			}
			else {
				return $res;
			}
		}
	}
}
?>
