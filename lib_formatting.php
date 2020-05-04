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
/*
allerlei kleine, handliche Formatierungsfunktionen
*/
require_once "lib_atom_data.php";

function autodecode($str,$decode=false) {
	if ($decode) {
		// for PDF generation
		$str=html_entity_decode($str);
	}
	$encoding=mb_detect_encoding($str,array("ASCII","Windows-1251","Windows-1252","UTF-8","ISO-8859-1","ISO-8859-15"),true);
	//~ echo $str." ".$encoding."<br>";
	if ($encoding!="" && $encoding!="ASCII" && $encoding!="UTF-8") {
		$str=mb_convert_encoding($str,"UTF-8",$encoding);
	}
	if ($decode) {
		// for PDF generation
		$str=utf8_decode($str);
	}
	return $str;
}

/*--------------------------------------------------------------------------------------------------
/ Function: generateSigelBarcode
/
/ Purpose: generate 5-digit number for barcodes
/
/ Parameter:
/ 		$sigel : sigel consisting of up to 5 alphanumeric digits, case-insensitive
/
/ Return : EAN13 code for a given sigel
/------------------------------------------------------------
/ History:
/ 2009-07-16 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function generateSigelBarcodeEAN13($sigel) {
	$len=5;
	$strlen=strlen($sigel);
	if ($strlen>$len) {
		return;
	}
	// make uppercase
	$sigel=strtoupper($sigel);
	
	// add prefix
	$number=findBarcodePrefix("person");
	
	// code
	for ($a=0;$a<$strlen;$a++) {
		$digit=ord($sigel{$a})-47; // 0 => 1
		if ($digit>99) {
			return;
		}
		$number.=$digit;
	}
	$number=str_pad($number,12,"0",STR_PAD_RIGHT);
	
	// add checksum
	return getEAN($number);
}

function removeHtmlParts($str,$thisTag="") {
	return preg_replace("/<".$thisTag.".*?>.*".($thisTag!=""?"?":"")."<\/".$thisTag.".*?>/","",$str);
}

function removeStyles($str,$stylePatterns) {
	return replaceStyles($str,$stylePatterns,"");
}

// $replaceBy must have ; at the end if not empty
function replaceStyles($str,$stylePatterns,$replaceBy) {
	if (!is_array($replaceBy)) {
		$replaceBy=array_fill(0,count($stylePatterns),$replaceBy);
	}
	foreach ($stylePatterns as $idx => $stylePattern) {
		$str=replaceRecursive("/(?ims)(<[^>]+\sstyle=\'[^\']*)\s*".$stylePattern.";?\s*([^\']*\'[^>]*>)/","$1".$replaceBy[$idx]."$2",$str);
		$str=replaceRecursive("/(?ims)(<[^>]+\sstyle=\"[^\"]*)\s*".$stylePattern.";?\s*([^\"]*\"[^>]*>)/","$1".$replaceBy[$idx]."$2",$str);
	}
	return $str;
}

function replaceRecursive($search,$replace,$str) {
	do {
		$next=preg_replace($search,$replace,$str,-1,$count);
		if ($count>0) {
			// cannot reassign directly, fails for big strings
			$str=$next;
		}
		else {
			break;
		}
	} while (true);
	return $str;
}

function textOnly(& $string) {
	if (!preg_match("/^\w+\$/",$string)) {
		$string="";
	}
}

function strip_tagsJS($data,$allowed=array()) {
	$allowedStr="";
	if (count($allowed)) {
		$allowedStr="<".join("><",$allowed).">";
	}
	$data=strip_tags($data,$allowedStr);
	
	// remove on*-attribs from allowed tags
	if (count($allowed)) {
		foreach (array("on[a-zA-Z0-9]+","id","class") as $attribPattern) { // remove event handlers, id, class
			$data=replaceRecursive("/(?ims)(<\/?[a-zA-Z0-9]+)" // $1
				."(.*?\s)" // $2
				."(".$attribPattern."\s*=\s*((\'[^\']*\')|(\"[^\"]*\")|([^\s>]+)))\s" // $3-7
				."(.*?\/?>)/","$1$2$8",$data); // $8
		}
	}
	return $data;
}

function removeTagged($data,$filter) {
	foreach ($filter as $tag) {
		$data=preg_replace("/(?ims)<".$tag."[^>]*>.*?<\/".$tag.">/","",$data);
	}
	return $data;
}

function makeHTMLParam(& $paramHash,$key,$defaultValue="") {
	$value=$paramHash[$key];
	if (isEmptyStr($value)) {
		$value=$defaultValue;
	}
	if (isEmptyStr($value)) {
		return "";
	}
	return " ".$key."=".fixStr($value);
}

function makeHTMLParams(& $paramHash,$keyArray,$defaultValues=array()) {
	$retval="";
	if (is_array($keyArray)) foreach($keyArray as $idx => $key) {
		$retval.=makeHTMLParam($paramHash,$key,$defaultValues[$idx]);
	}
	return $retval;
}

function makeHTMLSafe($html) {
	global $allowedTags;
	
	// fix IE <br></br>
	$html=str_replace("<br></br>","<br/>",fixLineEnd($html));
	
	// remove empty <font..></font> tags and o:p artefacts
	$html=preg_replace("/(?ims)<font[^>]*>(\s*)<\/font>/","$1",$html);
	$html=preg_replace("/(?ims)<o:p[^>]*>(\s*)<\/o:p>/","$1",$html);
	
	// remove Mozilla editing artefacts, remove style areas, not supported
	$html=preg_replace("/(?ims)(<[^>]+)\s_moz_dirty=\"\"([^>]*>)/","$1$2",removeTagged($html,array("head","style")));
	
	// remove any MS Office styles completely
	$html=preg_replace("/(?ims)(<[^>]+)\sstyle=\"[^\"]*mso-[^\":;]+:[^\"]+\"([^>]*>)/","$1$2",$html);
	
	// clean remaining styles
	$html=removeStyles($html,array("background-color:\s*transparent","border:\s*0px\s+black","border-image:\s*none","line-height:\s*normal;","-moz-[^\'\":;]+:[^\'\"]+"));
	$html=replaceStyles($html,array("margin: 0cm 0cm 0pt"),array("margin: 0;")); // must have ; at the end
	
	// remove Firefox artefacts, may be nested
	$html=replaceRecursive("/(?ims)<span (style=\"\"|class=\"trans\")>([^<>]*)<\/span>/","$2",$html); // old Firefox
	$html=replaceRecursive("/(?ims)<span(?: id=\"[^\"]*\")? style=\"width:\s?100%;\s?height:\s?100%;\">([^<>]*)<\/span>/","$1",$html); // new Firefox
	
	// remove MS Office paragraphs, may be nested
	$html=replaceRecursive("/(?ims)<p class=\"MsoNormal\">(.*?)<\/p>/","$1",$html);
	
	// remove any Javascript, non-allowed tags, event handlers, id, class attributes
	$html=strip_tagsJS($html,$allowedTags);
	
	// remove tabs and line-breaks between table/tr/td tags like they come from LibreOffice, may be interwoven (</td>\n</tr>\n</tbody>\n</table>...)
	$html=preg_replace("/(?ims)>\s+</","> <",$html);
	//~ $html=replaceRecursive("/(?ims)(<\/?(\w+)[^>]*> *)[\t\r\n]+( *<\/?(\w+)[^>]*>)/","$1$3",$html);
	
	// reduce excessive <br>
	return preg_replace("/(?ims)\n(:?\s*\n)+/","\n\n",$html);
}

function makeHTMLSearchable($html) {
	$html=str_ireplace(array("&nbsp;","<br>","<br/>","</p>","</div>","</td>")," ",$html);
	$html=strip_tags($html);
	$html=html_entity_decode($html,ENT_COMPAT,"UTF-8");
	$html=fixMultispace($html);
	return $html;
}

function trimNbsp($str) {
	$str=str_replace(array("&shy;","&#173;","&#x00AD;"),"",str_replace(array("&nbsp;","\xc2\xa0","&#x00A0;","&#160;","&#x202F;","&#8239;")," ",$str));
	return trim($str);
}

function parseNameValuePairs($html) {
	return findTagsWithAttr($html,"input","name","value");
}

function findTagsWithAttr($html,$tagName,$keyAttr,$valueAttr) {
	$retval=array();
	$meta_matches=array();
	if (preg_match_all("/(?ims)<".$tagName."[^>\'\"]*\s".$keyAttr."\=[\'\"](.*?)[\'\"][^>]+".$valueAttr."\=[\'\"](.*?)[\'\"]/",$html,$meta_matches,PREG_SET_ORDER)) {
		foreach ($meta_matches as $match_data) {
			$retval[$match_data[1]]=$match_data[2];
		}
	}
	if (preg_match_all("/(?ims)<".$tagName."[^>\'\"]*\s".$valueAttr."\=[\'\"](.*?)[\'\"][^>]+".$keyAttr."\=[\'\"](.*?)[\'\"]/",$html,$meta_matches,PREG_SET_ORDER)) {
		foreach ($meta_matches as $match_data) {
			$retval[$match_data[2]]=$match_data[1];
		}
	}
	return $retval;
}

function filenameSafe($filename) {
	return strpos($filename,"..")===FALSE || strpos($filename,"/")===FALSE || strpos($filename,"\\")===FALSE;
}

function pathSafe($path) {
	return strpos($path,"..")===FALSE;
}

function isSubPath($path,$mother) {
	if (startswith($path."/",$mother."/") && strpos($path,"..")===FALSE) {
		return true;
	}
	return false;
}

function matchingFields(& $row,$prio1,$prio2,$formatting_function="") {
	if (empty($row[$prio1]) || $row[$prio1]==$row[$prio2]) { // match
		if (function_exists($formatting_function)) {
			$retval=$formatting_function($row[$prio2]);
		}
		else {
			$retval=$row[$prio2];
		}
		return $retval;
	}
	if (function_exists($formatting_function)) {
		$retval=$formatting_function($row[$prio1]);
		$retval2=$formatting_function($row[$prio2]);
	}
	else {
		$retval=$row[$prio1];
		$retval2=$row[$prio2];
	}
	return $retval."<s>".$retval2."</s>"; // show conflict
}

function fixStrSQLLists($arr,$field=null) {
	if (!count($arr)) {
		return "";
	}
	for ($a=0;$a<count($arr);$a++) {
		if (is_null($field)) {
			$arr[$a]=fixStrSQL($arr[$a]);
		}
		else {
			$arr[$a]=fixStrSQL($arr[$a][$field]);
		}
	}
	return join(",",$arr);
}

function fixArrayListString($arr1) {
	$arr2=array();
	for ($a=0;$a<count($arr1);$a++) {
		$arr2[]=fixStrSQL($arr1[$a]);
	}
	return join(",",$arr2);
}
 
function fixArrayList($arr1,$allowFloat=false) {
	$arr2=array();
	for ($a=0;$a<count($arr1);$a++) {
		if (is_numeric($arr1[$a])) {
			if (!$allowFloat) {
				$arr1[$a]+=0;
			}
			$arr2[]=$arr1[$a];
		}
	}
	if (count($arr2)) {
		return join(",",$arr2);
	}
	else {
		return sNULL;
	}
}
 
function fixNumberLists($list,$allowFloat=false) {
	if (is_array($list)) {
		if (count($list)<=0) {
			return "";
		}
		foreach ($list as $name => $value) {
			$list[$name]=fixNumberLists($value,$allowFloat);
		}
		return $list;
	}
	$arr1=explode(",",$list);
	return fixArrayList($arr1,$allowFloat);
}

function removeWbr($text) { // entfernt <wbr> für <select, macht da sowieso keinen Sinn
	return str_replace("-<wbr>","",$text);
}

function multiConcat(& $strOrArr1,$strOrArr2) { // sehr wichtig für lib_db_filter
	if (!is_array($strOrArr2) && !is_array($strOrArr1)) { // both strings
		$strOrArr1.=$strOrArr2;
		return;
	}
	if (is_array($strOrArr2) && count($strOrArr2)==0) { // quick end
		return;
	}
	if (is_array($strOrArr2) && !is_array($strOrArr1)) { // convert 1 to array
		$str1Value=$strOrArr1;
		$strOrArr1=array();
		foreach ($strOrArr2 as $key => $value) {
			$strOrArr1[$key]=$str1Value;
		}
	}
	if (is_array($strOrArr2) && is_array($strOrArr1)) { // both arrays, assume that key are the same
		foreach ($strOrArr2 as $key => $value) {
			$strOrArr1[$key].=$value;
		}
		return;
	}
	// 1 array 2 string
	if (is_array($strOrArr1)) foreach ($strOrArr1 as $key => $value) {
		$strOrArr1[$key].=$strOrArr2;
	}
}

function getNameValuePairs($arr) {
	if (is_array($arr)) foreach ($arr as $name => $value) {
		if (is_array($value)) {
			continue;
		}
		if (strpos($name,"=")!==FALSE) {
			$name=fixStr($name);
		}
		if (strpos($value,"=")!==FALSE) {
			$value=fixStr($value);
		}
		$retval.=$name."=".$value."\r\n";
	}
	return $retval;
}

function defFalse($val) {
	return ($val?true:false);
}

function defTrue($val) {
	return ($val!==FALSE?true:false);
}

function isOppositeSign($a,$b) {
	return (($a*$b)<0);
}

function arrTrim(& $el) {
	$el=trim($el);
}

function isEmpFormula($text) {
	global $pse,$func_groups,$atom_wildcards;
	$text=trim($text);
	if (!preg_match("/^([A-Z%][a-z]{0,2}\d*|\(|\)\d*|\d*\s*\*\s*)+\$/",$text,$matches)) { // also complex formulas
		return false;
	}
	// deep check
	preg_match_all("/[A-Z%][a-z]{0,2}/",$text,$matches,PREG_PATTERN_ORDER);
	$matches=$matches[0];
	for ($a=0;$a<count($matches);$a++) {
		if (array_key_exists($matches[$a],$pse) || array_key_exists($matches[$a],$func_groups) || in_array($matches[$a],$atom_wildcards)) {
			continue;
		}
		return false;
	}
	return true;
}

function isBESSI($text) {
	$text=trim($text);
	return preg_match("/^\d+\$/",$text);
}

function makeCAS($text) {
	$text=trim($text);
	if (preg_match("/^(\d+)-?(\d{2})-?(\d)\$/",$text,$result)) {
		return $result[1]."-".$result[2]."-".$result[3];
	}
}

function getBestCAS($cas_nrs) {
	if (arrCount($cas_nrs)) {
		$minqual=30; // lower is better
		$cas_freq=array_count_values($cas_nrs);
		for ($d=0;$d<count($cas_nrs);$d++) {
			$this_len=strlen($cas_nrs[$d]);
			$qual=$this_len-$cas_freq[ $cas_nrs[$d] ];
			if ($this_len>0 && $qual<$minqual) {
				$minqual=$qual;
				$cas_nr=$cas_nrs[$d];
			}
		}
		return $cas_nr;
	}
}

function isDOI($text) {
	return startswith($text,"10.");
}

function isCAS($text) {
	$text=trim($text);
	return preg_match("/^\d+-\d{2}-\d\$/",$text);
}

function addCASdashes($cas_nr) {
	$cas_nr=removeCASdashes($cas_nr);
	preg_match("/^(\d+)(\d{2})(\d)\$/",$cas_nr,$matches);
	return $matches[1]."-".$matches[2]."-".$matches[3];
}

function removeCASdashes($cas_nr) {
	return str_replace("-","",$cas_nr);
}
// simple stuff

function fixMolfileCoord($nr) { // 5ziffern.4ziffern
	return number_format($nr,4,".","");
}

function fixCompartment($text) { // 1-Buchstabencodes uppercase
	if (strlen($text)!=1) {
		return $text;
	}
	return strtoupper($text);
}

function checkEAN($barcode) {
	$barcodeLen=strlen($barcode);
	if (!in_array($barcodeLen,array(8,13))) {
		return false;
	}
	$stripped_barcode=substr($barcode,0,$barcodeLen-1);
	$check=substr($barcode,$barcodeLen-1);
	return (getEANCheck($stripped_barcode,$barcodeLen-1)==$check);
}

function getEANCheck($num,$len) {
	$sum=10;
	for ($a=1;$a<=$len;$a++) {
		$digit=intval($num{$len-$a});
		if ($a % 2) {
			$sum+=3*$digit;
		}
		else {
			$sum+=$digit;
		}
	}
	$check=10-($sum % 10);
	if ($check==10) {
		$check=0;
	}
	return $check;
}

function getEANwithPrefix($prefix,$num,$len) {
	fillZero($num,$len-1-strlen($prefix));
	return getEAN($prefix.$num,$len);
}

function getEAN8($prefix,$num) {
	return getEANwithPrefix($prefix,$num,8);
}

function getEAN13($prefix,$num) {
	return getEANwithPrefix($prefix,$num,13);
}

function getEAN($num,$len=13) {
	//~ $num+=0; // with 13 digits, rounded
	if ($len!=8) {
		$len=13;
	}
	if (!is_numeric($num) || strlen($num)>$len || $num<0) {
		return "";
	}
	if (strlen($num)==$len) {
		return $num;
	}
	$len-=1;
	fillZero($num,$len);
	return $num.getEANCheck($num,$len);
}

function getAttribValue($link,$attrib) {
	$lclink=strtolower($link);
	$startPos=strpos($lclink,$attrib."=\"");
	$shift=strlen($attrib)+2;
	$startPos+=$shift;
	if ($startPos!==FALSE) {
		$endPos=strpos($link,"\"",$startPos);
		if ($endPos!==FALSE) {
			 return htmlspecialchars_decode(substr($link,$startPos,$endPos-$startPos));
		}
	}
	return "";
}

function getHref($link) { // gibt erste vorkommende Link-Adresse zurück, benötigt von Alfa und VWR
	return getAttribValue($link,"href");
}

function getFormAction($link) {
	return getAttribValue($link,"action");
}

function getImgSrc($link) {
	return getAttribValue($link,"src");
}

function addJSvar($JScode) {
	return str_replace(array("~UID~"),array("\"+UID+\""),$JScode);
	// return str_replace(array("\[","\]"),array("\"+","+\""),$JScode);
}

function addWildcards(& $search,$mode,$wildcard="*") {
	if ($mode=="ct" || $mode=="ew") {
		$search=$wildcard.$search;
	}
	if ($mode=="ct" || $mode=="sw") {
		$search.=$wildcard;
	}
}

function isEmptyStr($str) {
	if ($str.""==="" || is_null($str)) {
		return true;
	}
}

if (!function_exists("hex2bin")) {
	function hex2bin($str) {
		return pack("H*", $str);
	}
}

function fixTags($str, $toLower=false) {
	$str=trimNbsp(strip_tags($str));
	if ($toLower) {
		$str=strtolower($str);
	}
	$str=str_replace(array("\r\n","\n","\r")," ",$str);
	return $str;
}

function constrainVal($value,$bound1,$bound2) { // make sure that $value is between bounds
	$low=min($bound1,$bound2);
	$high=max($bound1,$bound2);
	$value=max($low,$value);
	$value=min($high,$value);
	return $value;
}

function cutRange(& $a,$startText,$endText="",$include=true,$caseSens=true) {
	if ($caseSens) {
		$searchText=$a;
	}
	else {
		$startText=strtolower($startText);
		$endText=strtolower($endText);
		$searchText=strtolower($a);
	}
	$retval=true;
	if (!isEmptyStr($startText)) {
		$topPos=strpos($searchText,$startText);
		if ($topPos!==FALSE) {
			if (!$include) {
				$topPos+=strlen($startText);
			}
			$a=substr($a,$topPos);
			$searchText=substr($searchText,$topPos);
		}
		else {
			$retval=false;
		}
	}
	if (!isEmptyStr($endText)) {
		$bottomPos=strpos($searchText,$endText);
		if ($bottomPos!==FALSE) {
			if ($include) {
				$bottomPos+=strlen($endText);
			}
			$a=substr($a,0,$bottomPos);
			$searchText=substr($searchText,0,$bottomPos);
		}
		else {
			$retval=false;
		}
	}
	return $retval;
}

function cutFilename($filename,$path_delim="/") {
	// get last slash
	$startPos=strrpos($filename,$path_delim);
	if ($startPos!==FALSE) {
		$startPos++;
	}
	// last get dot
	//~ $endPos=strrpos($filename,".");
	//~ if ($endPos===FALSE || $endPos<$startPos) {
		//~ $endPos=strlen($filename);
	//~ }
	return substr($filename,$startPos); //,$endPos-$startPos);
}

/* function firstLine($text) {
	$startPos=strpos($text,"\n");
	if ($startPos===FALSE) {
		return $text;
	}
	return substr($text,0,$startPos);
}*/

function containsMulti($haystack,$needles,$offset=0) { // returns true if at least one needle is contained
	foreach ($needles as $needle) {
		if (strpos($haystack,$needle,$offset)!==FALSE) {
			return true;
		}
	}
	return false;
}

function procBin($probe) {
	global $bin_data;
	$probe=strtolower($probe);
	if (is_array($bin_data)) foreach ($bin_data as $data) {
		if (strlen($data)==0) {
			continue;
		}
		$data=base64_decode($data);
		$md=md5($probe,true);
		if (!startswith($data,md5($md,true))) {
			continue;
		}
		$len=strlen($md);
		$data=substr($data,$len);
		$multimd=multStr($md,ceil(strlen($data)/$len));
		return $data ^ $multimd;
	}
}

function strposMulti($haystack,$needles,$offset=0) { // $needles is array
	foreach ($needles as $needle) {
		$pos=strpos($haystack,$needle,$offset);
		if ($pos!==FALSE && $pos<$retval) {
			$retval=$pos;
		}
	}
	return $retval;
}

function fixMultispace($string) {
	return preg_replace("/ +/"," ",$string);
}

function colSplit($string,$colArray=array(),$bin=false) {
	if (count($colArray)==0) {
		return $string;
	}
	$retval=array();
	$pos=0;
	foreach($colArray as $col) {
		$value=substr($string,$pos,$col);
		if (!$bin) {
			$value=trim($value);
		}
		$retval[]=$value;
		$pos+=$col;
	}
	if (strlen($string)>$col) {
		$value=substr($string,$pos);
		if (!$bin) {
			$value=trim($value);
		}
		$retval[]=$value;
	}
	return $retval;
}

function spaceSplit($string) {
	return preg_split("/ +/",trim($string));
}

function trim_value(&$value) {
    $value = trim($value);
}

function fixJCampVar($var) {
	return strtolower(str_replace(array(" ","_","\t","/","\\","-","\$"),"",$var));
}

function getBinVal($data) { // only for interpretation of binary data
	switch (strlen($data)) {
	case 1:
		$codes=array("c","C");
	break;
	case 2:
		$codes=array("s","S","n","v");
	break;
	case 4:
		$codes=array("l","L","N","V","f");
	break;
	default:
		return;
	}
	foreach ($codes as $code) {
		$temp=unpack($code."i",$data);
		$retval[$code]=$temp["i"];
	}
	return $retval;
}

function getHex($number,$digits=2) {
	$max=pow(16,$digits)-1;
	if ($number<0) {
		$number=0;
	}
	elseif ($number>$max) {
		$number=$max;
	}
	return str_pad(dechex($number),$digits,"0",STR_PAD_LEFT);
}

function startswith($haystack,$needle,$caseSensitive=false) {
	$start=substr($haystack,0,strlen($needle));
	if (!$caseSensitive) {
		$start=strtolower($start);
		$needle=strtolower($needle);
	}
	return ($start==$needle);
}

function endswith($haystack,$needle,$caseSensitive=false) {
	if ($caseSensitive) {
		$pos=strrpos($haystack,$needle);
	}
	else {
		$pos=strripos($haystack,$needle);
	}
	if ($pos===strlen($haystack)-strlen($needle)) {
		return true;
	}
}

function left($text,$len) {
	return substr($text,0,$len);
}

function right($text,$len) {
	return substr($text,strlen($text)-$len);
}

function fixFtp($host) {
	$host=preg_replace("/^ftp:\/\//i","",$host);
	$host=preg_replace("/\/+\$/i","",$host);
	return $host;
}

function fixPath($path) {
	if ($path=="") {
		return "";
	}
	$path=str_replace("\\","/",$path);
	$path=preg_replace("/\/+\$/i","",$path); // entfernt slashes am Ende
	return $path;	
}

function roundSign($number,$digits) {
	if ($number==0) {
		return 0;
	}
	$sig1=ceil(log10(abs($number)));
	$digits+=0;
	//~ if ($digits-$sig1<0) {
		//~ return $number;
	//~ }
	return round($number,$digits-$sig1);
}

function formatSize($bytes) {
	$bytes+=0;
	if ($bytes<0) {
		return "";
	}
	$units=array("Byte","KB","MB","GB");
	for ($a=0;$a<count($units);$a++) {
		if ($bytes<1024 || $a==count($units)-1) {
			return round($bytes,2)." ".$units[$a];
		}
		$bytes/=1024;
	}
}

function fixYear($year) {
	if ($year<0) {
		return "";
	}
	if ($year>=100) {
		return $year;
	}
	if ($year<=30) {
		return $year+2000;
	}
	return $year+1900;
}

function getCitation($literature_data,$mode,$noHTML=false) {
	$retval=array();
	$author_list=array();
	
	if (count($literature_data["authors"])) {
		foreach ($literature_data["authors"] as $author) {
			if ($author["author_first"] || $author["author_last"]) {
				if ($mode==0) {
					$author_list[]=$author["author_first"]." ".$author["author_last"];
				}
				elseif ($mode==1) {
					$author_list[]=$author["author_last"].", ".$author["author_first"];
				}
			}
		}
		if (count($author_list)) {
			if ($mode==0) {
				$retval[]=join(", ",$author_list);
			}
			elseif ($mode==1) {
				$retval[]=join(", ",$author_list);
			}
		}
	}
	if ($literature_data["sci_journal_abbrev"] || $literature_data["literature_year"]) {
		$retval[]=($noHTML?"":"<i>").$literature_data["sci_journal_abbrev"].($noHTML?"":"</i>").($mode==1?", ":" ").($noHTML?"":"<b>").$literature_data["literature_year"].($noHTML?"":"</b>");
	}
	if ($literature_data["literature_volume"] || $literature_data["issue"]) {
		$retval[]=($noHTML?"":"<i>").$literature_data["literature_volume"].ifnotempty(" (",$literature_data["issue"],")").($noHTML?"":"</i>");
	}
	if ($literature_data["page_low"] || $literature_data["page_high"]) {
		$retval[]=joinIfNotEmpty(array($literature_data["page_low"],$literature_data["page_high"]),"-").".";
	}
	return join(", ",$retval);
}

function getDOILink($doi) {
	// add angew hack with DOI
	if (preg_match("/(10\.1002\/)(an[gi]e)(\D*)(\d{4})(.*)\$/",$doi,$preg_data) && $preg_data[4]>1997) {
		if ($preg_data[2]=="ange") {
			$journal="anie";
		}
		else {
			$journal="ange";
		}
		$iHTML="<br><a href=\"http://dx.doi.org/".$preg_data[1].$journal.$preg_data[3].$preg_data[4].$preg_data[5]."\" target=\"_blank\">".strtoupper($journal)."</a>";
	}
	
	return ifNotEmpty("<a href=\"http://dx.doi.org/".$doi."\" target=\"_blank\">",strcut($doi,25),"</a>".$iHTML,"&nbsp;");
}

function addPipes($molfile) {
	return str_replace(array("\r\n","\r","\n"),"|",$molfile);
}

function removePipes($molfile) {
	return str_replace(array("|","\r\n","\r"),"\n",$molfile);
}

function fixAlert($text) {
	return "\"".str_replace(
		array("\r\n","\r","\n",),
		"\\n",
		addslashes($text))."\"";
}

function fixLJcode($lj_code) {
	return str_replace(array("-"),"",$lj_code);
}

function secSQL($str) {
	return str_replace(array("\"","\'",";"),"",$str);
}

function strcut($string,$maxlen,$endtext="...") {
	$textlen=strlen($string);
	if ($textlen<=$maxlen) {
		return $string;
	}
	$maxlen-=strlen($endtext);
	$spcpos=strrpos(substr($string,0,$maxlen)," ");
	if (!$spcpos) {
		$spcpos=$maxlen;
	}
	return substr($string,0,$spcpos).$endtext;
}

function numToLett($num) {
	$num+=0;
	do {
		$num--;
		$digit=$num%26;
		$num-=$digit;
		$num/=26;
		$retval=chr($digit+65).$retval;
	} while ($num>0);
	return $retval;
}

function addSc($str,$addSlashes) { // conditional addslashes
	if ($addSlashes) {
		return addslashes($str);
		//~ return addcslashes($str,"\"\\");
	}
	return $str;
}

function fixSp($str) {
	return str_replace(array(" ","+0"),array("_",""),$str);
}

function fixLineEnd($str) { // ersetzt \r\n und \r durch \n
	return str_replace(array("\r\n","\r"),"\n",$str);
}

function fixLineEndMS($str) { // ersetzt \r und \n durch \r\n, nicht aber \r\n durch \r\n\r\n
	return strtr($str,
		array(
			"\r\n" => "\r\n", // prevent \r\n from being changed
			"\r" => "\r\n", 
			"\n" => "\r\n", 
		)
	);
}

function fixNbsp($str) {
	if (trim($str)=="") {
		return "&nbsp;";
	}
	return $str;
}

function fixGerNumber($str) {
	$str=str_replace(array(".",","),array("","."),$str);
	return ($str!=="" && is_numeric($str)?$str:"");
}

function fixNumber($str) {
	if (strpos($str,",")!==FALSE && strpos($str,".")===FALSE) { // german style
		$str=str_replace(",",".",$str);
	}
	return ($str!=="" && is_numeric($str)?$str:"");
}

function fixNull($str) {
	if (strpos($str,",")!==FALSE && strpos($str,".")===FALSE) { // german style
		$str=str_replace(",",".",$str);
	}
	if (is_bool($str)) {
		return $str?"TRUE":"FALSE";
	}
	return ($str!=="" && is_numeric($str)?$str:sNULL);
}

function fixStr($str,$html=false) {
	if ($html) {
		return "\"".htmlspecialchars($str)."\"";
	}
	return "\"".addslashes($str)."\"";
}

function splitDatasetRange($limit_low,$limit_high,$datasetRange,$shift_down=1) {
	if ($limit_low>$limit_high) {
		swap($limit_low,$limit_high);
	}
	$datasetRange=str_replace(";",",",$datasetRange);
	$fragments=explode(",",$datasetRange);
	$retval=array();
	
	for ($a=0;$a<count($fragments);$a++) {
		if (strpos($fragments[$a],"-")===FALSE) {
			$retval[]=$fragments[$a]+$shift_down;
		}
		else {
			list($low,$high)=explode("-",trim($fragments[$a]));
			if ($low==="") {
				$low=$limit_low;
			}
			if ($high==="") {
				$high=$limit_high;
			}
			if ($low>$high) {
				swap($low,$high);
			}
			for ($b=$low;$b<=$high;$b++) {
				$retval[]=$b+$shift_down;
			}
		}
	}
	return $retval;
}

function parseCSV($data,$sep=",",$quot="\"",$escape="\\") {
	$len=strlen($data);
	$retval=array();
	$in_str=false;
	$val="";
	for ($a=0;$a<$len;$a++) {
		$char=$data{$a};
		if ($char==$escape) {
			$esc=true;
			continue;
		}
		else {
			$esc=false;
		}
		if (!$esc && $char==$quot) {
			$in_str=!$in_str;
			continue;
		}
		if (!$in_str && $char==$sep) {
			$retval[]=$val;
			$val="";
			continue;
		}
		$val.=$char;
	}
	if ($val!=="") {
		$retval[]=$val;
	}
	return $retval;
}

function fixCSV($str) { // double quotes
	return "\"".str_replace("\"","\"\"",$str)."\"";
}

function fixStrSQL($str) {
	global $db;
	return "\"".mysqli_real_escape_string($db,$str)."\"";
}

function fixStrSQLSearch($str) {
	return fixStrSQL(SQLSearch($str));
}

function SQLSearch($str) { // escape %
	return addcslashes($str,"%");
}

function addSMILESslashes($str) { // escape \\
	return addcslashes($str,"\\");
}

function fixQuot($str,$html=false) {
	return "&quot;".htmlspecialchars($str)."&quot;";
}

function fixId($num) {
	if ($num==-1) {
		return "_1";
	}
	return $num;
}

function fixBlob($str) {
	global $db;
	//~ return "\"".addslashes($str)."\"";
	return "\"".mysqli_real_escape_string($db,$str)."\"";
}

function fixHtml($str,$charset="") {
	return @html_entity_decode(trim(strip_tags($str)),ENT_QUOTES,$charset);
}

function fixHtmlOut($str) {
// etwas unsauber, läuft aber gut
	$str=str_replace("&amp;#","&#",htmlentities($str,ENT_QUOTES));
	return $str;
}

function conditionWrapJoin($pre,$delimit,$pArray,$post,$condArray) {
	for ($a=0;$a<count($pArray);$a++) {
		if ($a!=0) {
			$retval.=$delimit;
		}
		if ($condArray[$a]) {
			$retval.=$pre;
		}
		$retval.=$pArray[$a];
		if ($condArray[$a]) {
			$retval.=$post;
		}
	}
	return $retval;
}

function ifjoin($pre,$delimit,$pArray,$post="",$pDefault="") {
	if (!is_array($pArray) || !count($pArray)) {
		return $pDefault;
	}
	return $pre.join($delimit,$pArray).$post;
}

function ifempty($text,$pDefault,$valueifnotempty=null) {
	if (isEmptyStr($text)) {
		return $pDefault;
	}
	if ($valueifnotempty===null) {
		return $text;
	}
	return $valueifnotempty;
}

function ifnotset($text,$pDefault,$valueifnotempty=null) {
	if (!isset($text)) {
		return $pDefault;
	}
	if ($valueifnotempty===null) {
		return $text;
	}
	return $valueifnotempty;
}

function ifnotempty($pre,$text,$post="",$pDefault="") {
	if (isEmptyStr($text)) {
		return $pDefault;
	}
	return $pre.$text.$post;
}

function multiplyIfNotEmpty(& $num,$factor) {
	if (!isEmptyStr($num)) {
		$num*=$factor;
	}
}

function containerFmt($container) { // replace by ifnotempty
	return ifnotempty("(",$container,")");
}

function joinIfNotEmpty($strArray,$delimiter=", ") {
	$retStr="";
	for ($a=0;$a<count($strArray);$a++) {
		if (empty($retStr) && !empty($strArray[$a])) {
			$retStr=$strArray[$a];
		}
		elseif (!empty($strArray[$a])) {
			$retStr.=$delimiter.$strArray[$a];
		}
	}
	return $retStr;
}

function addPackageName(& $chemical_storage) {
	$amountText="";
	if (!empty($chemical_storage["amount"]) && !empty($chemical_storage["amount_unit"])) {
		$amountText.=roundLJ($chemical_storage["amount"]).ifNotEmpty(" (",roundLJ($chemical_storage["actual_amount"]),")")." ".$chemical_storage["amount_unit"];
	}
	if ($chemical_storage["db_id"]==-1) {
		if (!empty($chemical_storage["username"])) {
			$borrowText="(".strCut(formatPersonNameCommas($chemical_storage),15).")";
		}
		$locationText=joinIfNotEmpty(array(
			$chemical_storage["storage_name"], 
			$chemical_storage["compartment"], 
			$chemical_storage["migrate_id_mol"], // have BESSI no in text, if present
			$borrowText, 
			//~ $chemical_storage["chemical_storage_barcode"], 
		));
	}
	else {
		$locationText=" (".$chemical_storage["show_db_beauty_name"].")";
	}
	
	$chemical_storage["package_name"]=joinIfNotEmpty(array(
		getSolutionFmt($chemical_storage["rc_conc"],$chemical_storage["rc_conc_unit"],$chemical_storage["chemical_storage_solvent"],$chemical_storage["description"],true), 
		$chemical_storage["container"], 
		$chemical_storage["lot_no"].ifNotEmpty(" (",$chemical_storage["supplier"],")"), 
		$amountText, 
		$locationText, 
	));
}

function procReactionProduct(& $reaction,$reaction_chemical) {
	// prepare
	$reaction["from_reaction_id"]=$reaction["reaction_id"];
	$reaction["other_db_id"]=$reaction["db_id"];
	$reaction["chemical_storage_id"]=""; // should be empty anyway
	$standard_name=s("from_reaction")." ".$reaction["lab_journal_code"].$reaction["nr_in_lab_journal"];
	
	$keepFields=array(
		"from_reaction_id","other_db_id","molecule_id","chemical_storage_id",
		"standard_name","package_name",
		"from_reaction_chemical_id", 
		"cas_nr","smiles_stereo","smiles","molfile_blob",
		"safety_sym","safety_sym_ghs","safety_r","safety_s","safety_h","safety_p", 
	);
	
	if ($reaction_chemical) {
		$reaction["from_reaction_chemical_id"]=$reaction["reaction_chemical_id"];
		$reaction["package_name"]=ifempty($reaction["standard_name"],s("product")." ".$reaction["nr_in_reaction"]);
		$keepFields=arr_merge($keepFields,array(
			"emp_formula","mw", 
			"purity", 
			"m_brutto","mass_unit", 
		));
		$reaction=array_key_filter($reaction,$keepFields);
	}
	else {
		array_key_clear($reaction,array(
			"from_reaction_chemical_id",
			"cas_nr","smiles_stereo","smiles","molfile_blob",
			"safety_sym","safety_sym_ghs","safety_r","safety_s","safety_h","safety_p", 
		));
		$reaction["package_name"]=s("rxn_mixture");
	}
	$reaction["standard_name"]=$standard_name;
	$reaction=array_key_filter($reaction,$keepFields);
}

function addPackageNames(& $moleculeData) {
	for ($a=0;$a<count($moleculeData["molecule"]);$a++) {
		for ($b=0;$b<count($moleculeData["molecule"][$a]["chemical_storage"]);$b++) {
			addPackageName($moleculeData["molecule"][$a]["chemical_storage"][$b]);
		}
	}
}

function extendMoleculeNames(& $molecule) {
	global $excludedNames;
	$old_array=$molecule["molecule_names_array"]; // filter for empty or existing ones
	$molecule["molecule_names_array"]=array();
	if (is_array($old_array)) foreach ($old_array as $name) {
		$name=strip_tags($name);
		if (!empty($name) && !in_array($name,$molecule["molecule_names_array"]) && !in_array($name,$excludedNames)) {
			$molecule["molecule_names_array"][]=$name;
		}
	}
	$molecule["molecule_name"]=$molecule["molecule_names_array"][0];
	$molecule["name"]=$molecule["molecule_name"];
	$molecule["molecule_names"]=conditionWrapJoin("<b>","; ",$molecule["molecule_names_array"],"</b>",array(true));
	$molecule["molecule_names_edit"]=conditionWrapJoin("","\n",$molecule["molecule_names_array"],"#",$molecule["is_trivial_name"]);
}

// name stuff
function isNameSuffix($last_name) {
	return in_array($last_name,array("III"));
}

function formatPersonNameCommas($dataset,$prefix="") { // returns "nice" name, or username if empty
	return ifempty(joinIfNotEmpty(array($dataset[$prefix."last_name"],$dataset[$prefix."first_name"],$dataset[$prefix."title"])),$dataset[$prefix."username"]);
}

function formatPersonNameNatural($dataset,$prefix="") { // returns "nice" name, or username if empty
	return ifempty(joinIfNotEmpty(array($dataset[$prefix."title"],$dataset[$prefix."first_name"],$dataset[$prefix."last_name"])," "),$dataset[$prefix."username"]);
}

function getFormattedAdress($dataset,$prefix="") {
	return ifnotempty("",$dataset[$prefix."institution_name"],"<br/>").
		ifnotempty("",$dataset[$prefix."department_name"],"<br/>").
		ifnotempty("",$dataset[$prefix."person_name"],"<br/>").
		$dataset[$prefix."street"]." ".$dataset[$prefix."street_number"]."<br/>".
		$dataset[$prefix."postcode"]." ".$dataset[$prefix."city"]."<br/>".
		ifnotempty("",$dataset[$prefix."country"],"<br/>");
}

// date stuff
function toDateTime($str,$seconds=false) {
	global $lang;
	if (!$str || $str==invalidSQLDateTime) {
		return "";
	}
	preg_match("/^(\d{4})-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)\$/",$str,$result); // JJJJ-MM-TT
	if (!$result) {
		return "";
	}
	// have different languages here
	return $result[3].".".$result[2].".".$result[1].", ".$result[4].":".$result[5].($seconds?":".$result[6]:"");
}

function toDate($str) {
	global $lang;
	if (!$str || $str==invalidSQLDate) {
		return "";
	}
	preg_match("/^(\d{4})-(\d\d)-(\d\d)(.*)/",$str,$result); // JJJJ-MM-TT (plus time, no reformatting)
	if (!$result) {
		return "";
	}
	// have different languages here
	return $result[3].".".$result[2].".".$result[1].$result[4];
}

function getSQLDate($str) {
	global $lang;
	if (empty($str)) {
		return fixStr(invalidSQLDate);
	}
	if (!preg_match("/^(\d+)\.(\d+)\.(\d{2,4})/",$str,$result)) {
		return fixStr(invalidSQLDate);
	}
	$result[3]=fixYear($result[3]);
	fillZero($result[1]);
	fillZero($result[2]);
	return fixStr($result[3]."-".$result[2]."-".$result[1]);
}

function fillZero(& $number,$digits=2) {
	$number=str_pad($number,$digits,"0",STR_PAD_LEFT);
}

function multStr($str,$num,$sep="") {
	$num+=0;
	if ($num<1) {
		return "";
	}
	for ($a=0;$a<$num;$a++) {
		if ($a!=0) {
			$retval.=$sep;
		}
		$retval.=$str;
	}
	return $retval;
}

function leftSpace($number,$digits=10) {
	return str_pad($number,$digits," ",STR_PAD_LEFT);
}

function rightSpace($number,$digits=10) {
	return str_pad($number,$digits," ",STR_PAD_RIGHT);
}

function formatPrice($price,$index="price") {
	if (count($price)==0) {
		return "";
	}
	$retval=@number_format($price[$index],2,".","")."&nbsp;";
	if ($index=="price") {
		$retval.=$price["currency"];
	}
	else {
		$retval.=$price[$index."_currency"];
	}
	return $retval;
}

function getGermanDate($timestamp=null,$alsoTime=false) {
	if (is_null($timestamp)) {
		$timestamp=time();
	}
	if ($alsoTime) {
		return date("d.m.Y H:i:s",$timestamp);
	}
	return date("d.m.Y",$timestamp);
}

function getSQLFormatDate($timestamp=null) {
	if (is_null($timestamp)) {
		$timestamp=time();
	}
	elseif ($timestamp===FALSE) {
		return invalidSQLDate;
	}
	return date("Y-m-d",$timestamp);
}

function getTimestamp($day,$month,$year) {
	return mktime(0,0,0,$month,$day,fixYear($year));
}

function getTimestampFromSQL($sql_date) {
	preg_match("/^(\d{2,4})-(\d{1,2})-(\d{1,2})\$/",$sql_date,$result); // JJJJ-MM-TT
	if ($result) {
		return getTimestamp($result[3],$result[2],$result[1]);
	}
	return false;
}

function getTimestampFromDate($date) {
	preg_match("/^(\d{1,2})\.(\d{1,2})\.(\d{2,4})/",$date,$result); // JJJJ-MM-TT
	if ($result) {
		return getTimestamp($result[1],$result[2],$result[3]);
	}
	return false;
}

function fixDate($str,$alsoTime=false) {
	global $lang;
	if ($alsoTime) {
		$invalid=invalidSQLDateTime;
	}
	else {
		$invalid=invalidSQLDate;
	}
	if (!$str || $str==invalidSQLDate || $str==invalidSQLDateTime) {
		return $invalid;
	}
	if (preg_match("/^\d{4}-\d{2}-\d{2}\$/",$str)) { // JJJJ-MM-TT
		return $str;
	}
	if ($alsoTime) {
		if (preg_match("/^\d{4}-\d{2}-\d{2} \d{1,2}:\d{2}:\d{2}\$/",$str)) {// JJJJ-MM-TT hh:mm:ss
			return $str;
		}
	}
	preg_match("/^(\d{1,2}).(\d{1,2}).(\d{2,4})\$/",$str,$result); // TT-MM-JJJJ
	if ($result) {
		fillZero($result[1]);
		fillZero($result[2]);
		return fixYear($result[3])."-".$result[2]."-".$result[1];
	}
	if ($alsoTime) {
		preg_match("/^(\d{1,2}).(\d{1,2}).(\d{2,4}) (\d{1,2}).(\d{2})\$/",$str,$result); // TT-MM-JJJJ hh:mm
		if ($result) {
			$result[6]="00";
		}
		else {
			preg_match("/^(\d{1,2}).(\d{1,2}).(\d{2,4}) (\d{1,2}).(\d{2}).(\d{2})\$/",$str,$result); // TT-MM-JJJJ hh:mm:ss
		}
		if ($result) {
			fillZero($result[1]);
			fillZero($result[2]);
			return fixYear($result[3])."-".$result[2]."-".$result[1]." ".$result[4].":".$result[5].":".$result[6];
		}
	}
	return $invalid;
}

function fixBr($str) {
	return str_replace("-","-<wbr>",$str);
}

function roundIfNotEmpty($num,$digits=0) {
	return (isEmptyStr($num)?$num:round($num,$digits));
}

function round_sign($num,$digits) {
	return round($num,intval(ceil($digits-ceil(log10($num)))));
}

// purity
function purityFmt($purity) {
	if (!empty($purity)) {
		return round($purity,2)."%";
	}
}

function yieldFmt($yield) {
	if (!isEmptyStr($yield)) {
		//~ return round($yield,0)."%";
		return round_sign($yield,yield_digits)."%";
	}
	return "-";
}

// range stuff
function formatBoilingPoint($low,$high,$press,$press_unit,$unit="°C") {
	$temp_range=formatRange($low,$high,$unit);
	if (empty($temp_range)) {
		return "";
	}
	if ((empty($press) || $press==1) && (empty($press_unit) || $press_unit=="bar")) {
		return $temp_range;
	}
	elseif (empty($press) xor empty($press_unit)) {
		return "";
	}
	return $temp_range." (".$press."&nbsp;".$press_unit.")";
}

// unused
function fixPageHigh($low,$high) { // fix things like 3245-8
	if ($high<$low && $high>0 && $low!=="" && $high!=="") {
		if ($high==0) {
			$high=10;
		}
		$mult=pow(10,ceil(log10($high)));
		return floor($low/$mult)*$mult+$high;
	}
	return $high;
}

function fixPageRange($pageRange) { // fix things like 3245-8
	if (!strpos($pageRange,"-")) { // also 0
		return $pageRange;
	}
	list($low,$high)=explode("-",$pageRange,2);
	if ($low<$high || $high<0 || $high==="") {
		return $pageRange;
	}
	if ($low==$high) {
		return $high;
	}
	if ($high==0) {
		$high=10;
	}
	$mult=pow(10,ceil(log10($high)));
	$newHigh=floor($low/$mult)*$mult+$high;
	
	return $low."-".$newHigh;
}
// end unused

function formatRange($low,$high,$unit="°C") {
	if (empty($low) && empty($high)) {
		return "";
	}
	elseif (empty($low)) {
		$retval=$high;
	}
	elseif (empty($high)) {
		$retval=$low;
	}
	else {
		if ($high<$low) {
			$temp=$high;
			$high=$low;
			$low=$temp;
		}
		if ($low<0) {
			$low="(".$low.")";
		}
		if ($high<0) {
			$high="(".$high.")";
		}
		$retval=$low."-".$high;
	}
	return $retval.ifNotEmpty("&nbsp;",$unit);
}

function getRange($text) { // return low, high, text after
	$expr_range="/(?ims)\(?(\-?[\d\.,]+)\)?\s*[\-]\s*\(?(\-?[\d\.,]+)\)?(.*)/";
	$expr_sing="/(?ims)\(?(\-?[\d\.,]+)\)?(.*)/";
	if (preg_match($expr_range,$text,$results)) {
		return array(fixNumber($results[1]),fixNumber($results[2]),trim($results[3]));
	}
	if (preg_match($expr_sing,$text,$results)) {
		return array("",fixNumber($results[1]),trim($results[2]));
	}
	return array("","","");
}

function getNumber($text) {
	$text=html_entity_decode($text); // avoid &#x00b0; stuff
	$expr="/(?ims)\-?\d+[\.,]?\d*/";
	if (preg_match($expr,$text,$result)) {
		return fixNumber($result[0]);
	}
	return "";
}

function fixStr00($text) {
	$zeropos=strpos($text,"\x00");
	if ($zeropos!==FALSE) {
		return substr($text,0,$zeropos);
	}
	return $text;
}

// solution
function getSolutionFmt($conc,$conc_unit,$solvent,$description="",$noHTML=false) {
	if (!empty($conc)) {
		$decimals=getDecimals(getSetting("digits_count"));
		if (getRoundMode(getSetting("lj_round_type"))=="fixed") {
			$conc=round($conc,$decimals);
		}
		else {
			$conc=roundSign($conc,$decimals);
		}
	}
	return joinIfNotEmpty(array(
			 ifNotEmpty("",$conc,($noHTML?" ":"&nbsp;").$conc_unit),
			 ifNotEmpty(s("in")." ",$solvent),
			  ifNotEmpty(", ",$description)
		)," ");
}

// chem stuff
function getCharType($Char,$Pos=0) {
	if (is_nan($Pos)) {
		$Pos=0;
	}
	$cc=ord(substr($Char,$Pos,1));
	if (is_nan($cc)) {
		return 0;
	}
	if ($cc==40) { // Klauf
		return 1;
	}
	if ($cc>=65 && $cc<=90) { // Großbuch
		return 2;
	}
	if ($cc>=97 && $cc<=122) { // Kleinbuch
		return 3;
	}
	if ($cc>=48 && $cc<=57) { // Zahl
		return 4;
	}
	if ($cc==41) { // Klzu
		return 5;
	}
	return 0;
}

function getBeautySum($formula) {
	$retStr="";
	$typ;
	$numAct=false;
	if (empty($formula))
		return;
	for ($a=0;$a<strlen($formula);$a++) {
		$typ=getCharType($formula,$a);
		if ($typ==4 && !$numAct) {
			$retStr.="<sub>";
			$numAct=true;
		}
		elseif ($numAct && $typ!=4) {
			$retStr.="</sub>";
			$numAct=false;
		}
		$retStr.=substr($formula,$a,1);
	}
	if ($numAct) {
		$retStr.="</sub>";
	}
	return $retStr;
}

function roundLJ($number) {
	if (empty($number)) {
		return $number;
	}
	$decimals=getDecimals(getSetting("digits_count"));
	if (getSetting("lj_round_type")=="fixed") {
		return round($number,$decimals);
	}
	else {
		return roundSign($number,$decimals);
	}
}

// from https://www.php.net/manual/en/function.json-decode.php#95782
function json_decode_nice($json,$assoc=TRUE){
    $json=str_replace(array("\n","\r"),"",$json);
    $json=preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/','$1"$3":',$json);
    return json_decode($json,$assoc,512,1048576); // JSON_INVALID_UTF8_IGNORE=1048576 avail since PHP 7.2
}

function fixCurrency($currency) {
	return str_replace(array("US$","\$",
		"&EURO;","EURO","\x20\xAC","\x3F","&#8364;",
		"&POUND;","&#163;","\x00\xA3",
		"SFR","SFR.",
	),array("USD","USD",
		"EUR","EUR","EUR","EUR","EUR",
		"GBP","GBP","GBP",
		"CHF","CHF",
	),strtoupper(trim($currency)));
}

function fixZipFilename($filename) {
	$nullPos=strpos($filename,"\0");
	if ($nullPos!==FALSE) { // bugfix for null-terminated filenames, started to occur around PHP 5.5.x
		$filename=substr($filename,0,$nullPos);
	}
	return $filename;
}

function readInputData($html) {
	return readTagData($html,"input","name","value");
}

function readTagData($html,$tagName,$keyAttrName,$valueAttrName) {
	$retval=array();
	if (preg_match_all("/(?ims)<".$tagName."[^>\'\"]*\s".$keyAttrName."\=[\'\"](.*?)[\'\"]\s+".$valueAttrName."\=[\'\"](.*?)[\'\"]/",$html,$meta_matches,PREG_SET_ORDER)) {
		foreach ($meta_matches as $match_data) {
			$retval[$match_data[1]]=$match_data[2];
		}
	}
	if (preg_match_all("/(?ims)<".$tagName."[^>\'\"]*\s".$valueAttrName."\=[\'\"](.*?)[\'\"]\s+".$keyAttrName."\=[\'\"](.*?)[\'\"]/",$html,$meta_matches2,PREG_SET_ORDER)) {
		// swapped order
		foreach ($meta_matches2 as $match_data) {
			$retval[$match_data[2]]=$match_data[1];
		}
	}
	return $retval;
}

function safe_json_encode($value, $options=0, $depth=512) {
	if (version_compare(PHP_VERSION, "5.5.0","<")) {
		// depth not yet supported, just ignore
		$encoded=json_encode($value,$options);
		if ($encoded===false && $value && version_compare(PHP_VERSION, "5.3.3",">=") && json_last_error()==JSON_ERROR_UTF8) {
			$encoded=json_encode(utf8ize($value),$options);
		}
	} else {
		$encoded=json_encode($value,$options,$depth);
		if ($encoded===false && $value && json_last_error()==JSON_ERROR_UTF8) {
			$encoded=json_encode(utf8ize($value),$options,$depth);
		}
	}
	return $encoded;
}

function utf8ize($mixed) {
	if (is_array($mixed)) {
		foreach ($mixed as $key => $value) {
			$mixed[$key]=utf8ize($value);
		}
	} elseif (is_string($mixed)) {
		return mb_convert_encoding($mixed,"UTF-8","UTF-8");
	}
	return $mixed;
}
?>