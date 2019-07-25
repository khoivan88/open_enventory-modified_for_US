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
require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
require_once "lib_http.php";
require_once "lib_constants.php";
require_once "lib_formatting.php";

$analytics=array();
require_once_r("literature");

function getLiteratureFunctionHeader() {
	return '
global $publisher,$noResults,$noConnection,$default_http_options;
$driver_code='.fixStr($GLOBALS["driver_code"]).';
$self=& $publisher[$driver_code];
';
}

function cleanCASEntries($entries) {
	if (is_array($entries)) foreach ($entries as $idx => $entry) {
		$entries[$idx]=cleanCASEntry($entry);
	}
	return $entries;
}
function cleanCASEntry($entry) {
	$entry=trim($entry);
	cutRange($entry,"","&nbsp;",false,false);
	cutRange($entry,""," (",false,false);
	return $entry;
}

function findInCASEntries(& $entries,$text,$type) {
	$text=strtolower($text);
	foreach ($entries as $idx => $entry) {
		$parts=preg_split("/(?ims)<br\/?>/",$entry);
		
		if (count($parts)<2) {
			continue;
		}
		
		$parts=cleanCASEntries($parts);
		
		if ($type==1) { // abbrev
			if ($text==strtolower($parts[1])) {
				return $parts[0];
			}
		}
		else if ($type==2) { // name
			if ($text==strtolower($parts[0])) {
				return $parts[1];
			}
		}
		else {
			if ($text==strtolower($parts[1])) {
				return $parts[0];
			}
			elseif ($text==strtolower($parts[0])) {
				return $parts[1];
			}
		}
	}
}

function getInfoFromCAS($text,$type) { // type=: 0=auto, 1=name given, 2=abbrev given
	global $default_http_options;
	
	$url="http://www.cas.org/expertise/cascontent/caplus/corejournals.html";
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=5;
	$response=oe_http_get($url,$my_http_options);
	if ($response==false) {
		return;
	}
	$body=@$response->getBody();
	
	// clean
	cutRange($body,"<p><strong>Title", "<div class=\"content_right\">",false,false);
	
	// split
	// $entries=explode("<BR>",$body);
	$entries=preg_split("/(?ims)<\\/p>[^<]*<p>/",$body);
	
	// search strict
	$retval=findInCASEntries($entries,$text,$type);
	
	//~ if (empty($retval)) {
		//~ // search tolerant
		//~ $retval=findInCASEntries($entries,$text,$type,true);
	//~ }
	
	return $retval;
}

function getAbbrevFromCAS($sci_journal_name) {
	return getInfoFromCAS($sci_journal_name,1);
}

function getNameFromCAS($sci_journal_abbrev) {
	return getInfoFromCAS($sci_journal_abbrev,2);
}

function addPDFToLiterature(& $literature,$url,$cookies) {
	global $default_http_options;
	
	if (empty($url)) return;
	$my_http_options=$default_http_options;
	$my_http_options["cookies"]=$cookies;
	$my_http_options["Accept"]="text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
	$my_http_options["Accept-Encoding"]="gzip, deflate";
	$my_http_options["Accept-Language"]="de-de,de;q=0.8,en-us;q=0.5,en;q=0.3";
	$response=oe_http_get($url,$my_http_options);
	if ($response!=false) {
		$literature["literature_blob"]=@$response->getBody();
		if (isPDF($literature["literature_blob"])) {
			$literature["literature_mime"]=@$response->getHeader("Content-Type");
		}
		else {
			unset($literature["literature_blob"]);
		}
	}
}

function getDataForDOI($doi) {
	global $publisher,$default_http_options;
	if (startswith($doi,doi)) {
		$url=$doi;
		$doi=substr($doi,strlen(doi));
	}
	elseif (isDOI($doi)) {
		$url=doi.str_replace(array("%2F","%2f"),"/",urlencode($doi));
	}
	elseif (startswith($doi,http)) {
		// try url directly
		$url=$doi;
		unset($doi);
	}
	else {
		return array();
	}
	
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=5;
	$response=oe_http_get($url,$my_http_options);
	$body=@$response->getBody();
	$eff_url=@$response->getEffectiveUrl();
	$cookies=oe_get_cookies($response);
	$body=str_replace(array("&nbsp;","&#160;")," ",$body);
	$body=str_replace(array("&#8224;","&#8225;"),"",$body);
	//~ die($body);
	
	if (is_array($publisher)) foreach (array_keys($publisher) as $code) {
		$result=$publisher[$code]["init"]();
		$result=$publisher[$code]["readPage"]($body,$cookies,$eff_url);
		if (count($result)) {
			// clean $result
			$retval["authors"]=trim(str_replace("*","",removeHtmlParts($retval["authors"],"sup"))); // get rid of stars etc
			$retval["literature_title"]=strip_tags($retval["literature_title"]);
			if (empty($retval["doi"])) {
				$retval["doi"]=$doi;
			}
			break;
		}
	}
	// find sci_journal_id for title or abbrev
	$filter=array();
	if (!empty($result["sci_journal_name"])) {
		$filter[]="sci_journal_name LIKE ".fixStrSQLSearch($result["sci_journal_name"]);
		if (empty($result["sci_journal_abbrev"])) {
			// get abbrev from CAS
			$result["sci_journal_abbrev"]=getAbbrevFromCAS($result["sci_journal_name"]);
		}
	}
	if (!empty($result["sci_journal_abbrev"])) {
		$filter[]="sci_journal_abbrev LIKE ".fixStrSQLSearch($result["sci_journal_abbrev"]);
		if (empty($result["sci_journal_name"])) {
			// get name from CAS
			$result["sci_journal_name"]=getNameFromCAS($result["sci_journal_abbrev"]);
		}
	}
	if (count($filter)) {
		list($sci_journal)=mysql_select_array(array(
			"table" => "sci_journal", 
			"dbs" => -1, 
			"filter" => join(" OR ",$filter), 
			"limit" => 1, 
		));
		if (!empty($sci_journal["sci_journal_id"])) {
			$result["sci_journal_id"]=$sci_journal["sci_journal_id"];
		}
		// else new one is created automatically
	}
	
	return $result;
}

function getDOIsFromText($text) {
	if (preg_match_all("/(?ims)(10\\.\d{4,}\\/.*?)(,|\}|\s|\$)/",$text,$results,PREG_PATTERN_ORDER)) {
		return array_unique($results[1]);
	}
	return array();
}

//~ print_r(getDataForDOI("10.1002/(SICI)1521-4133(199812)100:12<524::AID-LIPI524>3.0.CO;2-6")); // Lipid/Fett, tricky html entities in DOI
//~ print_r(getDataForDOI("10.1002/lipi.19870890609")); // Lipid/Fett
//~ print_r(getDataForDOI("10.1002/ange.200906750")); // angew
//~ print_r(getDataForDOI("10.1002/ange.200705127")); // angew
//~ print_r(getDataForDOI("http://dx.doi.org/10.1002/anie.199216421")); // angew
//~ print_r(getDataForDOI("http://dx.doi.org/10.1002/1521-3773(20010917)40:18%3C3458::AID-ANIE3458%3E3.0.CO;2-0")); // angew
//~ print_r(getDataForDOI("10.1021/ol901129v")); // JACS
//~ print_r(getDataForDOI("10.1021/om901079n")); // JACS
//~ print_r(getDataForDOI("10.1021/ja064782t")); // JACS
//~ print_r(getDataForDOI("http://dx.doi.org/10.1002/adsc.200303040")); // JACS
//~ print_r(getDataForDOI("http://pubs.acs.org/doi/full/10.1021/ol1012857")); // JACS
//~ print_r(getDataForDOI("10.1016/S0022-328X(97)00434-8")); // Elsevier
//~ print_r(getDataForDOI("10.1016/S0040-4020(01)86099-3")); // Elsevier
//~ print_r(getDataForDOI("10.1016/S0040-4039(98)01716-X")); // Elsevier
//~ print_r(getDataForDOI("http://www.sciencedirect.com/science/article/pii/S0040403998014658")); // Elsevier
//~ print_r(getDataForDOI("10.1039/P19850002167")); // RSC
//~ print_r(getDataForDOI("10.1039/b926087k")); // RSC asap
//~ print_r(getDataForDOI("10.1039/b916853b")); // RSC Green CHem
//~ print_r(getDataForDOI("10.1039/b106779f")); // RSC
//~ print_r(getDataForDOI("10.1039/b201577n")); // RSC
//~ print_r(getDataForDOI("10.1039/JR9630001855")); // RSC
//~ print_r(getDataForDOI("10.1055/s-2006-956484")); // Thieme
//~ print_r(getDataForDOI("10.1055/s-2005-862372")); // Thieme
//~ print_r(getDataForDOI("10.1007/BF00519789")); // Springer
//~ print_r(getDataForDOI("10.1007/s10562-008-9774-0")); // Springer
//~ print_r(getDataForDOI("10.1103/PhysRevLett.89.167401")); // APS
//~ print_r(getDataForDOI("10.1143/JJAP.50.120201")); // JJAP
//~ print_r(getDataForDOI("10.1143/JJAP.47.1279")); // JJAP
//~ print_r(getDataForDOI("10.1126/science.1128684")); // Science
//~ print_r(getDataForDOI("10.1038/nchem.1208")); // Nature Chemistry
//~ print_r(getDataForDOI("10.3762/bjoc.6.43")); // Beilstein
//~ print_r(getDataForDOI("10.1071/CH08480")); // Csiro
//~ print_r(getDataForDOI("10.1016/0031-9422(96)00125-2")); // Phytochem (Elsevier)
//~ print_r(getDataForDOI("10.1073/pnas.0804348105")); // Nat Acad Sci
//~ print_r(getDataForDOI("http://www.beilstein-journals.org/bjoc/single/articleFullText.htm?publicId=1860-5397-6-43")); // Beilstein
//~ echo fixHtml("&ouml;","ISO-8859-1");
//~ echo fixBogusChars("<div class=\"articleAuthors\">Marisol Reyes-Lezama, Herbert H<img class=\"entityD\" alt=\"\" src=\"/entityImage/?text=006f,0308\">pfl and No<img class=\"entityD\" alt=\"\" src=\"/entityImage/?text=0065,0301\"> Z<img class=\"entityD\" alt=\"\" src=\"/entityImage/?text=0075,0301\"><img class=\"entityD\" alt=\"\" src=\"/entityImage/?text=006e,0303\">iga-Villarreal </div>");

?>