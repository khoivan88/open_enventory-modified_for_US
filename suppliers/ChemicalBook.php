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
$code=$GLOBALS["code"];
$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "ChemicalBook", 
	"logo" => "chembook.gif", 
	"height" => 36, 
"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="http://www.chemicalbook.com";
	$suppliers[$code]["urls"]["base"]=$urls["server"]."/Search_EN.aspx?keyword=";
	$suppliers[$code]["urls"]["startPage"]=$urls["server"]; // startPage
'),
"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="url";
	$retval["action"]=$urls["base"].$query_obj["vals"][0][0];
	return $retval;
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	$baseurl=$urls["base"];
	return $baseurl.$catNo;
'),
"getInfo" => create_function('$catNo',getFunctionHeader().'
	$url=$urls["server"]."/ProductChemicalProperties".$catNo."_EN.htm";
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
	return $self["procDetail"]($body);
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	$baseurl=$urls["base"];
	$srch=$searchText; //process the value to other functions. Needed to filter out erroneusly found entries sometimes returned by ChemicalBook
	$url=$baseurl.urlencode($searchText);	
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$response=oe_http_get($url,$my_http_options);
	if ($response==FALSE) {
		return $noConnection;
	}
	$body=utf8_encode(@$response->getBody());
	return $self["procHitlist"]($response,$srch,$filter);
'),
"getBestHit" => create_function('& $hitlist,$name=NULL','
	$a=0;
	for($i=0;$i<count($hitlist);$i++) {
		if (count(array_filter($hitlist[$i])) > count(array_filter($hitlist[$a]))) {
			$a=$i;
		}
	}
	return $a;
'),
"procDetail" => create_function('$body,$catNo=""',getFunctionHeader().'
	$body=utf8_encode(str_replace("&nbsp;"," ",$body));
	preg_match("/(?ims)Mol\s?File:?(.*?)<a href=.*?\.mol/",$body,$lk);
	$lk2=preg_replace("/(?ims)Mol\s?File:?(.*?)<a href=\.\./","",$lk[0]);
	$molurl=str_replace("\\\","/",$lk2);
	preg_match_all("/(?ims)<td.*?<\/td>/",$body,$cells,PREG_PATTERN_ORDER);
	$cells=$cells[0];
	//var_dump($cells);
	$newEntry=array("supplierCode" => $code);
	$result=array();
	if (is_array($cells)) foreach ($cells as $cell) {
		$current=fixTags($cell).trim("\0\x09");
		if ($current!="") {
			switch ($previous) {
			case "cbnumber":
				$result["beautifulCatNo"]=$current;
			break;
			case "Product Name:":
				$result["molecule_names_array"][0]=$current;
			break;
			case "CAS:":
				$result["cas_nr"]=$current;
			break;
			case "MF:":
				$result["emp_formula"]=$current;
			break;
			case "MW:":
				if ($current!="0") { # chemical book may have this bogus value
					$result["mw"]=$current+0.0;
				}
			break;
			case "Hazard Codes":
				$result["safety_sym"]=$current;
			break;
			case "Risk Statements":
				$result["safety_r"]=$current;
			break;
			case "Safety Statements":
				$result["safety_s"]=$current;
			break;
			case "RIDADR":
				$result["molecule_property"][]=array("class" => "adr", "source" => $code, "conditions" => $current);
			break;
			case "WGK Germany":
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
			case "Fp":
				$val=getNumber($current);
				$unt="°C";
				$result["molecule_property"][]=array("class" => "FP", "source" => $code, "value_high" => $val, "unit" => $unt);
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
				$result["molecule_property"][]=array("class" => "Vap_press", "source" => $code, "value_high" => $vap_press[0], "unit" => $unt, "conditions" => $vap_press[4].$tempunt);
			break;
			}
		}
		$previous=$current;
	}
	$response2=oe_http_get($urls["server"].$molurl,$my_http_options);
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

	$result["supplierCode"]=$code;
	return $result;
'),

"procHitlist" => create_function('& $response,$srch,$filter',getFunctionHeader().'
	if ($filter!=="molecule_name" && $filter!=="emp_formula"){ //check what is the topic of search
		$patt="/[0-9]+\-[0-9][0-9]\-[0-9]/";
   	 	if (!preg_match($patt,$srch)){ //If neither name nor empirical formula, check whether the search text is a CAS number, proceed if true 
			return $noResults;
		}
	}
	$body=utf8_encode(@$response->getBody());
	$baseurl=$urls["base"];
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
			$newEntry=array("supplierCode" => $code);
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
						$response2=oe_http_get($urls["server"]."/ProductChemicalProperties".$catNo."_EN.htm",$my_http_options);  //get the detailed page
						$body=@$response2->getBody();
						$res=$self["procDetail"]($body); //get the Name from the detailed page
						$newEntry["name"]=$res["molecule_names_array"][0];
					}
				}
				$result[]=$newEntry;
			}
		}
		if (count($result)==1) {
			$catNo=$result[0]["catNo"];
			if($catNo) {
				$response2=oe_http_get($urls["server"]."/ProductChemicalProperties".$catNo."_EN.htm",$my_http_options);  //get the detailed page
				$body=@$response2->getBody();
				$result=array();
				$result[0]=$self["procDetail"]($body); //process the detailed page to procDetail
				$result[0]["catNo"]=$catNo;
				$result[0]["addInfo"]=$result[0]["cas_nr"];
				extendMoleculeNames($result[0]);
			}
		}
		return $result;
	}
'),
"getCASfromName" => create_function('$name',getFunctionHeader().'
	$name=strtolower($name);
	$hitlist=$self["getHitlist"]($name,"molecule_name");
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
')
);
$GLOBALS["suppliers"][$code]["init"]();
?>
