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

function getSafetyGif($strSym,$size=30) {
	if (empty($strSym)) {
		return "&nbsp;";
	}
	$strSym=trimNbsp($strSym);
	// split
	if (strpos($strSym," ")!==FALSE) {
		$arrSym=explode(" ",$strSym);
	}
	else {
		$arrSym=explode(",",$strSym);
	}
	for ($a=0;$a<count($arrSym);$a++) {
		// handle GHS
		$text=trim($arrSym[$a],"\xc2\a0");
		$title=$text.": ".s("safety_".$text);
		$retVal.="<img src=\"lib/".getSafetyFilename($text)."\" height=".fixStr($size)." width=".fixStr($size).getTooltipP($title)."><wbr>";
	}
	return $retVal;
}

function getSafetyFilename($text) {
	global $arrSymURL;
	$text=trim($text,"\xc2\a0");
	$number=intval(getNumber($text));
	if ($number) {
		// GHS
		return $arrSymURL[$number];
	} else {
		return $arrSymURL[substr($text,0,1)];
	}
}

function computeVbf($fp,$waterMix) { // $fp in °C, gibt VbF-Klasse zurück
	if ($waterMix) {
		if ($fp<=21) {
			return "B";
		}
	}
	else {
		if ($fp<=21) {
			return "AI";
		}
		elseif ($fp<=55) {
			return "AII";
		}
		elseif ($fp<=100) {
			return "AIII";
		}
	}
}

function computeWirkfaktor($safety_r,$safety_cancer,$safety_mutagen,$safety_mak,$pH,$safety_skinres) {
	$safety_r=str_replace("/","-",$safety_r); // deal with combinations
	$safety_r=explode("-",$safety_r);
	if ($safety_mak<100 && $safety_mak>0.1) {
		$W_mak=100/$safety_mak;
	}
	if (multi_in_array(array("45","46","49"),$safety_r) || $safety_mutagen==1 || $safety_mutagen==2 || $safety_cancer==1 || $safety_cancer==2) {
		$retval=50000;
	}
	elseif (multi_in_array(array("26","27","28","32","60","61"),$safety_r) || $safety_mak<0.1) { // RE1,2, RF1,2
		$retval=1000;
	}
	elseif (multi_in_array(array("35","48/23","48/24","48/25","42","43"),$safety_r)) {
		$retval=500;
	}
	elseif (multi_in_array(array("23","24","25","29","31","34","41","33","40","68"),$safety_r) || $safety_mutagen==3 || $safety_cancer==3 || (!empty($pH) && $pH<2 || $pH>11.5) || ($safety_skinres && !multi_in_array(array("20","21","22"),$safety_r))) {
		$retval=100;
	}
	elseif (multi_in_array(array("48/20","48/21","48/22","62","63"),$safety_r) || $MAK<0.1) { // RE3,RF3
		$retval=50;
	}
	elseif (multi_in_array(array("20","21","22"),$safety_r)) {
		$retval=10;
	}
	elseif (multi_in_array(array("36","37","38","65","67"),$safety_r)) {
		$retval=5;
	}
	elseif (count($safety_r)) {
		$retval=1;
	}
	return max($retval,$W_mak);
}

function getSchutzklasseKL($safety_sym,$safety_r) {
	$arrR=explode("-",$safety_r);
	$retval=0;
	if (strpos($safety_sym,",")!==FALSE) {
		$arrSym=explode(",",$safety_sym);
	}
	else {
		$arrSym=explode(" ",$safety_sym);
	}
	// search for cancerogenic, etc
	for ($b=0;$b<count($arrR);$b++) {
		switch ($arrR[$b]) {
		case "39":
		case "40":
		case "45":
		case "46":
		case "49":
		case "60":
		case "61":
		case "62":
		case "63":
			return 4;
		break;
		case "29":
		case "31":
		case "32":
		case "23": // giftig
		case "24":
		case "25":
		case "26": // sehr giftig
		case "27":
		case "28":
			$retval=3;
		break;
		}
	}
	if ($retval==3 || in_array("T",$arrSym) || in_array("T+",$arrSym)) {
		return 3;
	}
	return 1;
}

function getSafetyClauseText(& $json,$type,$clause) {
	if (in_array($type,array("R","S")) && strpos($clause,"/")===FALSE && strpos($clause,".")===FALSE && strpos($clause,"EU")===FALSE) {
		// old simple cases
		$retval=$json[$type."simple"][$clause-1];
		if (!isEmptyStr($retval)) {
			return $retval;
		}
	}
	return $json[$type."complex"][$clause];
}

function getSafetyClausesText(& $json,$type,$clauses) {
	// clean
	$clauses=trim($clauses);
	$retval=array();
	if (!isEmptyStr($clauses)) {
		$clauses=explode("-",str_replace(array(",",";"),"-",$clauses));
		
		foreach ($clauses as $clause) {
			$result=getSafetyClauseText($json,$type,$clause);
			
			$html=$type.$clause.": ";
			if (!isEmptyStr($result)) {
				$html.=$result;
			}
			elseif (strpos($clause,"+")!==FALSE) {
				// not found, split at pluses and built part by part
				$parts=explode("+",$clause);
				$found_parts=array();
				foreach ($parts as $part) {
					$part=getSafetyClauseText($json,$type,$part);
					if (!isEmptyStr($part)) {
						$found_parts[]=$part;
					}
				}
				$html.=join(" ",$found_parts);
			}
			$retval[]=$html;
		}
	}
	
	return $retval;
}

function getSafetyHtml(& $hash,$langToUse,$types) {
	global $g_settings;
	
	// open js file of lang and parse
	$js_file=file_get_contents("lib/safety_".$langToUse.".js");
	$json=json_decode(substr($js_file,strpos($js_file,"=")+1,-1),true);
	//var_dump($json);die();
	
	$retval=array();
	foreach ($types as $type) {
		$type=strtolower($type);
		switch($type) {
		case "r":
		case "s":
			if ($g_settings["use_rs"]) {
				$retval=array_merge($retval,getSafetyClausesText($json,strtoupper($type),$hash["safety_".$type]));
			}
		break;
		case "h":
		case "p":
			if ($g_settings["use_ghs"]) {
				$retval=array_merge($retval,getSafetyClausesText($json,strtoupper($type),$hash["safety_".$type]));
			}
		break;
		}
	}
	
	return $retval;
}

// give recommendations for working instructions, same function also exists in Javascript
$isoMandMap=array(
"M002" => array("p" => array("103","201","202"),"s" => array("61")),
"M004" => array("p" => array("280","282"), "s" => array("39")),
"M005" => array("s" => array("33")),
"M009" => array("p" => array("280","282"), "s" => array("37")),
"M010" => array("p" => array("280"), "s" => array("36")),
"M013" => array("p" => array("280","282"), "s" => array("39")),
"M017" => array("p" => array("284"), "s" => array("38","42")),
"P003" => array("p" => array("210")),
"P011" => array("r" => array("14","29"), "h" => array("260","261"), "p" => array("223")),
);
// TODO: add $safety_r
function getProtEquip($safety_s,$safety_p,$safety_h) {
	global $isoMandMap;
	$retval=array();
	foreach ($isoMandMap as $mandAc => $data) {
		$pictos=$isoMandMap[$mandAc]["p"];
		$found=false;
		if ($pictos && $safety_p) foreach ($pictos as $picto) {
			if (strripos($safety_p,$picto)!==FALSE) {
				// found
				$found=true;
				break;
			}
		}
		$pictos=$isoMandMap[$mandAc]["s"];
		if (!$found && $pictos && $safety_s) foreach ($pictos as $picto) {
			if (strripos($safety_s,$picto)!==FALSE) {
				// found
				$found=true;
				break;
			}
		}
		
		$pictos=$isoMandMap[$mandAc]["h"];
		if (!$found && $pictos && $safety_h) foreach ($pictos as $picto) {
			if (strripos($safety_h,$picto)!==FALSE) {
				// found
				$found=true;
				break;
			}
		}
		
		if ($found) {
			$retval[]=$mandAc;
		}
	}
	return $retval;
}
?>