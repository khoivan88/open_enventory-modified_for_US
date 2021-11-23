<?php
/*
Copyright 2006-2009 Felix Rudolphi and Lukas Goossen
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
// Sial

/**
 * ---- Khoi's code ----
 */
/*
$GLOBALS["code"]="Sial";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code,
	"name" => "Sigma-Aldrich",
	"logo" => "logo_SigmaAldrich.gif",
	"height" => 50,
	"vendor" => true,
	//~ "hasPriceList" => 1,
	"alwaysProcDetail" => true,
	"country_cookies" => array(
		"country" => "US",
		"SialLocaleDef" => "CountryCode~US|WebLang~-1|",
		// "country" => "GER",
		// "SialLocaleDef" => "CountryCode~DE|WebLang~-1|",
		//~ "cmTPSet" => "Y",
		// "fsr.s" => "{\"cp\":{\"REGION\":\"GER\",\"ClientId\":\"Unknown\",\"MemberId\":\"Unknown\",\"SiteId\":\"SA\"}}",
		// "cookienotify" => "2",
		//~ "foresee.session" => "%7B%22alive%22%3A0%2C%22previous%22%3Anull%2C%22finish%22%3A1260376567205%2C%22cpps%22%3A%7B%22COUNTRY%22%3A%22NONE%22%2C%22REGION%22%3A%22NONE%22%2C%22ClientId%22%3A%22Unknown%22%2C%22MemberId%22%3A%22Unknown%22%7D%7D",
		//~ "SialSiteDef" => "AnonymousClientId~Y|WebLang~-1|CountryCode~DE|",
	),
	"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["startPage"]="https://www.sigmaaldrich.com"; // startPage
	$suppliers[$code]["urls"]["search"]=$urls["startPage"]."/catalog/search?term=";
	$suppliers[$code]["urls"]["detail"]=$urls["startPage"]."/catalog/product/";
	// $suppliers[$code]["urls"]["detail"]=$urls["startPage"]."/catalog/";
	// $suppliers[$code]["urls"]["search_suffix"]="&N=0&mode=partialmax&lang=en&region=US&focus=product";
	'),
	"requestResultList" => create_function('$query_obj',getFunctionHeader().'
	$retval["method"]="url";
	$retval["action"]=$suppliers[$code]["urls"]["search"].$query_obj["vals"][0][0]."&interface=";
	if ($query_obj["crits"][0]=="cas_nr") {
		$retval["action"].="CAS%20No.";
	}
	elseif ($query_obj["crits"][0]=="emp_formula") {
		$retval["action"].="Molecular%20Formula";
	}
	else {
		$retval["action"].="Product%20Name";
	}

	// $retval["action"].=$suppliers[$code]["urls"]["search_suffix"];
	return $retval;
	'),
	"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	list($brand,$productNumber)=explode("/",$catNo,2);
	return $urls["detail"].$brand."/".$productNumber."?lang=en&region=US&referrer=enventory";
	// 	$splitCatNo=explode("/",$catNo,2);
	// 	if (count($splitCatNo)>2) {
	// 		$splitCatNo[0]=$splitCatNo[0]."/";
	// 	}
	// 	else {
	// 		array_unshift($splitCatNo,"");
	// 	}
	// 	return $urls["detail"].$splitCatNo[0]."product/".$splitCatNo[1]."/".$splitCatNo[2]."?lang=en&region=US&referrer=enventory";
	'),
	"getInfo" => create_function('$catNo',getFunctionHeader().'
	$url=$self["getDetailPageURL"]($catNo);
	if (empty($url)) {
		return $noConnection;
	}
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$my_http_options["cookies"]=$self["country_cookies"];
	$my_http_options["useragent"] = "HTTP_Request2/2.1.1 (http://pear.php.net/package/http_request2) PHP/7.3.7";    // Khoi: fixed for A2 hosting server, for some reason, with the default useragent, Sigma does not work. Solution: use this default useragent by Request2 module of PHP
	$response=oe_http_get($url,$my_http_options); // set country by cookie directly and read prices
	if ($response==FALSE) {
		return $noConnection;
	}

	return $self["procDetail"]($response,$catNo);
	'),
	"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().'
	echo "\t\t".$url;
	$url=$urls["search"].urlencode($searchText)."&interface=";
	if ($filter=="cas_nr") {
		$url.="CAS%20No.";
	}
	elseif ($filter=="emp_formula") {
		$url.="Molecular%20Formula";
	}
	else {
		// $url.="Product%20Name";
		$url.="All";
	}
	// $url.=$suppliers[$code]["urls"]["search_suffix"];
	$url .= "&N=0&mode=match%20partialmax&lang=en&region=US&focus=product";  //Khoi: for US region
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	$my_http_options["useragent"] = "HTTP_Request2/2.1.1 (http://pear.php.net/package/http_request2) PHP/7.3.7";    // Khoi: fixed for A2 hosting server, for some reason, with the default useragent, Sigma does not work. Solution: use this default useragent by Request2 module of PHP
	$response=oe_http_get($url,$my_http_options);
	if ($response==FALSE) {
		return $noConnection;
	}

	return $self["procHitlist"]($response);
	'),
	"procDetail" => create_function('& $response,$catNo=""',getFunctionHeader().'
	global $lang,$default_http_options;

	$body=html_entity_decode(@$response->getBody(),ENT_QUOTES,"UTF-8");
	// $body=html_entity_decode($response->getBody(),ENT_QUOTES,"UTF-8");
	$cookies=oe_get_cookies($response);
	$cookies=array_merge($cookies,$self["country_cookies"]);

	$result=array();
	$result["price"]=array();
	$result["molecule_property"]=array();

	preg_match("/(?ims)id=\"productDetailHero\"(.*?)id=\"productDetailTabContainer\"/",$body,$top_data);
	// name
	preg_match("/(?ims)<h1.*?>(.*?)<\/h1>/",$top_data[1],$name_data);
	$result["molecule_names_array"]=array(fixTags($name_data[1]));

	preg_match_all("/(?ims)<li.*?>.*?<p.*?>(.*?)<span.*?>(.*?)<\/span>.*?<\/p>.*?<\/li>/",$top_data[1],$manyNVPs,PREG_SET_ORDER);
	//~ print_r($manyNVPs);die();

	for ($b=0;$b<count($manyNVPs);$b++) {
		$name=fixTags($manyNVPs[$b][1]);
		$value=fixTags($manyNVPs[$b][2]);

		if (startswith($name,"CAS Number")) {
			$result["cas_nr"]=$value;
		}
		elseif ($filter=="emp_formula") {
			$url.="mol_form";
		}
		else {
			$url.="product_name";
		}

	cutRange($body,"id=\"contentWrapper\"","id=\"productDetailBlockContainer\"");

	$body=preg_replace(array("/(?ims)<script.*?<\/script>/","/(?ims)<style.*?<\/style>/"),"",$body);
	$body=str_ireplace(array("\t","<sub>","</sub>","<sup>","</sup>","<i>","</i>"),"",$body);
	$body=str_ireplace(array("&#160;","&nbsp;")," ",$body);

	preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$manyLines,PREG_PATTERN_ORDER);
	$manyLines=$manyLines[0];

	for ($b=0;$b<count($manyLines);$b++) {
		preg_match_all("/(?ims)<td.*?<\/td>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
		$cells=$cells[0];

		if (count($cells)>=2) {
			$self["handleCells"]($result,$cells);
		}
	}

	preg_match_all("/(?ims)<div[^>]* class=\"safetyRow\"[^>]*>\s*<div[^>]*>(.*?)<\/div>\s*<div[^>]*>(.*?)<\/div>\s*<\/div>/",$body,$specialLines,PREG_SET_ORDER);
	for ($b=0;$b<count($specialLines);$b++) {
		$self["handleCells"]($result,array_slice($specialLines[$b],1));
	}

	/* tries get pricing
	$cookies["fsr.s"]="{\"f\":1310634858411,\"cp\":{\"COUNTRY\":\"NONE\",\"REGION\":\"NONE\",\"ClientId\":\"Unknown\",\"MemberId\":\"Unknown\",\"SiteId\":\"SA\"}}";
	$cookies["fsr.a"]="1310634860821";

	$my_http_options=$default_http_options;
	$my_http_options["cookies"]=$cookies;
	$my_http_options["referer"]=$self["getDetailPageURL"]($catNo);
	$my_http_options["headers"]=array(
		"Accept" => "text/html, *\/*; q=0.01",
		"Accept-Language" => "de-de,de;q=0.8,en-us;q=0.5,en;q=0.3",
		"Accept-Encoding" => "gzip, deflate",
		"Accept-Charset" => "ISO-8859-1,utf-8;q=0.7,*;q=0.7",
		"Connection" => "keep-alive",
		"Content-Type" => "application/x-www-form-urlencoded; charset=UTF-8",
		"X-Requested-With" => "XMLHttpRequest",
	);

	print_r($my_http_options);
	list($brand,$productNumber)=explode("/",$catNo,2);
	$url="http://www.sigmaaldrich.com/catalog/PricingAvailability.do?productNumber=".$productNumber."&brandKey=".$brand."&divId=pricingContainer";
	echo $url;
	$prices=oe_http_post_fields($url, array("loadFor" => "PRD_RS", ), array(),$my_http_options);
	die($prices);

	$result["supplierCode"]=$code;
	$result["catNo"]=$catNo;
	return $result;
	'),
	"handleCells" => create_function('& $result,$cells',getFunctionHeader().'
	$text=fixTags($cells[0]);
	$next_text=fixTags($cells[1]);
	if (strpos($text,"Synonym")!==FALSE) {
		$synonyms=explode(", ",$next_text);
		$result["molecule_names_array"]=arr_merge($result["molecule_names_array"],$synonyms);
	}
	elseif (strpos($text,"EC Number")!==FALSE) {
		if (!isEmptyStr($next_text)) {
			$result["molecule_property"][]=array("class" => "EG_No", "source" => $code, "conditions" => $next_text);
		}

//		if ($data) {
//			$subData=$data["props"]["pageProps"]["data"]["getProductDetail"];
//		}

		if (preg_match("/(?ims)Pictograms.*?(GHS\d.*?)<\//",$body,$ghs_match)) {
			$result["safety_sym_ghs"]=fixTags($ghs_match[1]);
		}

		//						1: tag																2: name			3: tag			4: value
		preg_match_all("/(?ims)<(div|h3)[^>]+class=\"[^\"]*MuiTypography-(?:caption|body)[^\"]*\"[^>]*>(.*?)<\/\\1>.*?<(p|span|a)[^>]*>(.*?)<\/\\3>/",$body,$manyNVPs,PREG_SET_ORDER);
		//~ print_r($manyNVPs);die();

		for ($b=0;$b<count($manyNVPs);$b++) {
			$name=strtolower(trim(fixTags($manyNVPs[$b][2]),": "));
			$value=fixTags($manyNVPs[$b][4]);

			if (startswith($name,"cas number")) {
				$result["cas_nr"]=$value;
			}
			elseif (startswith($name,"molecular weight")) {
				$result["mw"]=$value;
			}
			elseif (startswith($name,"empirical formula")) {
				$result["emp_formula"]=$value;
			}
			elseif (strpos($name,"synonym")!==FALSE) {
				$synonyms=explode(", ",$value);
				$result["molecule_names_array"]=arr_merge($result["molecule_names_array"],$synonyms);
			}
			elseif (strpos($name,"hazard codes")!==FALSE || strpos($name,"hazard symbols")!==FALSE) {
				$result["safety_sym"]=$value;
			}
			elseif (strpos($name,"symbol")!==FALSE || strpos($name,"pictogram")!==FALSE) {
				$result["safety_sym_ghs"]=$value;
			}
			elseif (strpos($name,"hazard statement")!==FALSE) {
				$result["safety_h"]=str_replace(array("H"," "),"",$value);
			}
			elseif (strpos($name,"precautionary statement")!==FALSE) {
				$result["safety_p"]=str_replace(array("P"," "),"",$value);
			}
			elseif (strpos($name,"risk statement")!==FALSE) {
				$result["safety_r"]=$value;
			}
			elseif (strpos($name,"safety statement")!==FALSE) {
				$result["safety_s"]=$value;
			}
			elseif ($name=="signal word") {
				$result["safety_text"]=$value;
			}
			elseif (strpos($name,"wgk germany")!==FALSE && $value != "nwg") {
				$result["safety_wgk"]= str_replace("WGK ", "", $value);
			}
			elseif (strpos($name,"refractive index")!==FALSE) {
				cutRange($value,"/D","",false);
				//~ $result["n_20"]=$next_text;
				$result["n_20"]=getNumber($value);
			}
			elseif ($name=="density") {
				//~ cutRange($next_text,"","g/mL",false);
				//~ $result["density_20"]=$next_text;
				$result["density_20"]=getNumber($value);
			}
			elseif ($name=="mp") { // too short
				list($result["mp_low"],$result["mp_high"])=getRange($value);
			}
			elseif ($name=="bp") { // too short
				list($result["bp_low"],$result["bp_high"],$press)=getRange($value);
				if (isEmptyStr($result["bp_high"])) {
					// do nothing
				}
				elseif (trim($press)!="") {
					$result["bp_press"]=getNumber($press);
					if (strpos($press,"mm")!==FALSE) {
						$result["press_unit"]="torr";
					}
					elseif (strpos($press,"hPa")!==FALSE) {
						$result["press_unit"]="mbar";
					}
				}
				else {
					$result["bp_press"]="1";
					$result["press_unit"]="bar";
				}
			}
			elseif (strpos($name,"ec number")!==FALSE) {
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "EG_No", "source" => $this->code, "conditions" => $value);
				}
			}
			elseif (strpos($name,"ridadr")!==FALSE) {
				$result["molecule_property"][]=array("class" => "adr", "source" => $this->code, "conditions" => $value);
				// get packing group, after last comma
				$lastSlash=strrpos($value,"/");
				if ($lastSlash!==FALSE) {
					$result["molecule_property"][]=array("class" => "packing_group", "source" => $this->code, "conditions" => getNumber(substr($value,$lastSlash+1)), );
				}
			}
			elseif (strpos($name,"flash point(c)")!==FALSE) {
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => $value+0.0, "unit" => "°C");
				}
			}
			elseif (strpos($name,"vapor pressure")!==FALSE) {
				$value=str_replace(array("&#x00b0;"),array("°"),$value);
				$vap_press_data=explode(" ",$value,3);
				if (!isEmptyStr($vap_press_data[0]) && !isEmptyStr($vap_press_data[1])) {
					$result["molecule_property"][]=array("class" => "Vap_press", "source" => $this->code, "value_high" => $vap_press_data[0]+0.0, "unit" => $vap_press_data[1], "conditions" => $vap_press_data[2]);
				}
			}
			elseif (strpos($name,"expl. lim.")!==FALSE) { // nur obere Grenze
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "Ex_limits", "source" => $this->code, "value_high" => $value+0.0, "unit" => "Vol.-%");
				}
			}
		}
		else {
			$result["bp_press"]="1";
			$result["press_unit"]="bar";
		}
	}
	elseif ($text=="mp") { // too short
		list($result["mp_low"],$result["mp_high"])=getRange($next_text);
	}
	elseif (strpos($text,"density")!==FALSE) {
		//~ cutRange($next_text,"","g/mL",false);
		//~ $result["density_20"]=$next_text;
		$result["density_20"]=getNumber($next_text);
	}
	elseif (strpos($text,"RIDADR")!==FALSE) {
		$result["molecule_property"][]=array("class" => "adr", "source" => $code, "conditions" => $next_text);
		// get packing group, after last comma
		$lastSlash=strrpos($next_text,"/");
		if ($lastSlash!==FALSE) {
			$result["molecule_property"][]=array("class" => "packing_group", "source" => $code, "conditions" => getNumber(substr($next_text,$lastSlash+1)), );
		}
	}
	elseif (strpos($text,"Flash Point(C)")!==FALSE) {
		if (!isEmptyStr($next_text)) {
			$result["molecule_property"][]=array("class" => "FP", "source" => $code, "value_high" => $next_text+0.0, "unit" => "°C");
		}
	}
	elseif (strpos($text,"vapor pressure")!==FALSE) {
		$next_text=str_replace(array("&#x00b0;"),array("°"),$next_text);
		$vap_press_data=explode(" ",$next_text,3);
		if (!isEmptyStr($vap_press_data[0]) && !isEmptyStr($vap_press_data[1])) {
			$result["molecule_property"][]=array("class" => "Vap_press", "source" => $code, "value_high" => $vap_press_data[0]+0.0, "unit" => $vap_press_data[1], "conditions" => $vap_press_data[2]);
		}
	}
	elseif (strpos($text,"expl. lim.")!==FALSE) { // nur obere Grenze
		if (!isEmptyStr($next_text)) {
			$result["molecule_property"][]=array("class" => "Ex_limits", "source" => $code, "value_high" => $next_text+0.0, "unit" => "Vol.-%");
		}
	}
'),
"procHitlist" => create_function('& $response',getFunctionHeader().'
$body=@$response->getBody();
if (stripos($body,"No Results Found")!==FALSE) { // no results at all
	return $noResults;
}
cutRange($body,"<div id=\"searchResultContainer-inner\"","id=\"searchResultsPagination\">");
//~ die($body);

//~ preg_match_all("/(?ims)<div class=\"productContainer-inner\">.*?<div class=\"productContainer clearfix\">/",$body,$manyBlocks,PREG_PATTERN_ORDER);
$manyBlocks=preg_split("/(?ims)<div [^>]*class=\"productContainer clearfix\"[^>]*>/",$body);
//~ print_r($manyBlocks);die();
$results=array();

for ($c=1;$c<count($manyBlocks);$c++) { // 1st elemnt is bogus
	// get name
	preg_match("/(?ims)<h2 [^>]*class=\"name\"[^>]*>(.*?)<\/h2>/",$manyBlocks[$c],$name_data);
	$molecule_name=fixTags($name_data[1]);

	preg_match_all("/(?ims)name=\"compareCheckbox\".*?<\/ul>/",$manyBlocks[$c],$manyLines,PREG_PATTERN_ORDER);
	$manyLines=$manyLines[0];
	//~ print_r($manyLines);die();

	for ($b=0;$b<count($manyLines);$b++) {
		preg_match_all("/(?ims)<li.*?<\/li>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER);
		$cells=$cells[0];
		// real list entry
		//~ print_r($cells);die();

		// http://www.sigmaaldrich.com/catalog/ProductDetail.do?lang=en&N4=658804|ALDRICH&N5=SEARCH_CONCAT_PNO|BRAND_KEY&F=SPEC

		preg_match("/(?ims)<a href=\"\/catalog\/product\/([^\/]*)\/([^\?\"]*)/",$manyLines[$b],$catNo_data);
		$results[]=array(
			"name" => $molecule_name,
			"addInfo" => fixTags($cells[1]),
			"beautifulCatNo" => fixTags($cells[0]),
			"catNo" => $catNo_data[1]."/".$catNo_data[2],
			"supplierCode" => $code,
		);
	}
}

return $results;
'),
"getBestHit" => create_function('& $hitlist,$name=NULL','
if (count($hitlist)>0) {
	return 0;
}
')
);
$GLOBALS["suppliers"][$code]["init"]();
*/
/**
 * ---- End Khoi's code ----
 */



 /**
 * ---- Felix's code 2021-05-06 ----
 */
$GLOBALS["code"]="Sial";
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "Sigma-Aldrich";
	public $logo = "logo_SigmaAldrich.gif";
	public $height = 50;
	public $vendor = true;
	public $hasPriceList = 0;
	public $country_cookies = array(
		"country" => "US",
		"language" => "en",
		"SialLocaleDef" => "CountryCode~US|WebLang~-1|",
		"cookienotify" => "2",
	);
	public $urls=array(
		"startPage" => "https://www.sigmaaldrich.com", // startPage
		"search_suffix" => "?focus=products&page=1&perPage=100&sort=relevance&term="
	);

	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["search"]=$this->urls["startPage"]."/US/en/search/";
		$this->urls["detail"]=$this->urls["startPage"]."/US/en/";
		$this->urls["startPage"]=$this->urls["server"];
    }

	public function requestResultList($query_obj) {
		$retval = array(
			"method" => "url",
			"action" => $this->urls["search"].urlencode($query_obj["vals"][0][0]).$this->urls["search_suffix"].urlencode($query_obj["vals"][0][0])."&type="
		);
		if ($query_obj["crits"][0]=="cas_nr") {
			$retval["action"].="cas_number";
		}
		elseif ($query_obj["crits"][0]=="emp_formula") {
			$retval["action"].="mol_form";
		}
		else {
			$retval["action"].="product_name";
		}

		return $retval;
    }

	public function getDetailPageURL($catNo) {
		$splitCatNo=explode("/",$catNo,2);
		if (count($splitCatNo)>2) {
			$splitCatNo[0]=$splitCatNo[0]."/";
		}
		else {
			array_unshift($splitCatNo,"");
		}
		return $this->urls["detail"].$splitCatNo[0]."product/".$splitCatNo[1]."/".$splitCatNo[2]."?lang=en&region=US&referrer=enventory";
    }

	public function getInfo($catNo) {
		global $noConnection,$default_http_options;

		$url=$this->getDetailPageURL($catNo);
		if (empty($url)) {
			return $noConnection;
		}
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$my_http_options["cookies"]=$this->country_cookies;
		$response=oe_http_get($url,$my_http_options); // set country by cookie directly and read prices
		if ($response==FALSE) {
			return $noConnection;
		}

		return $this->procDetail($response,$catNo);
    }

	public function getHitlist($searchText,$filter,$mode="ct",$paramHash=array()) {
		global $noConnection,$default_http_options;

		$url=$this->urls["search"].urlencode($searchText).$this->urls["search_suffix"].urlencode($searchText)."&type=";
		if ($filter=="cas_nr") {
			$url.="cas_number";
		}
		elseif ($filter=="emp_formula") {
			$url.="mol_form";
		}
		else {
			$url.="product_name";
		}

		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$response=oe_http_get($url,$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}

		return $this->procHitlist($response);
    }

	public function procDetail(& $response,$catNo="") {
		global $lang,$default_http_options;

		$body=html_entity_decode($response->getBody(),ENT_QUOTES,"UTF-8");
//		$data=array();
//		if (preg_match("/(?ims)<script id=\"__NEXT_DATA__\" type=\"application\/json\">(.*?)<\/script>/",$body,$json_data)) {
//			$data=json_decode($json_data[1], true);
//		}
		cutRange($body,"</header>","<nav");

		$result=array();
		$result["price"]=array();
		$result["molecule_property"]=array();

		// name
		if (preg_match("/(?ims)<h1.*?>(.*?)<\/h1>/",$body,$name_data)) {
			$result["molecule_names_array"]=array(fixTags($name_data[1]));
		}

		// name
		if (preg_match("/(?ims)<[^>]+id=\"product-number\"[^>]*>(.*?)<[^>]*>/",$body,$name_data)) {
			$catNo=fixTags($name_data[1]);
		}

//		if ($data) {
//			$subData=$data["props"]["pageProps"]["data"]["getProductDetail"];
//		}

		if (preg_match("/(?ims)Pictograms.*?(GHS\d.*?)<\//",$body,$ghs_match)) {
			$result["safety_sym_ghs"]=fixTags($ghs_match[1]);
		}

		//						1: tag																2: name			3: tag			4: value
		preg_match_all("/(?ims)<(div|h3)[^>]+class=\"[^\"]*MuiTypography-(?:caption|body)[^\"]*\"[^>]*>(.*?)<\/\\1>.*?<(p|span|a)[^>]*>(.*?)<\/\\3>/",$body,$manyNVPs,PREG_SET_ORDER);
		//~ print_r($manyNVPs);die();

		for ($b=0;$b<count($manyNVPs);$b++) {
			$name=strtolower(trim(fixTags($manyNVPs[$b][2]),": "));
			$value=fixTags($manyNVPs[$b][4]);

			if (startswith($name,"cas number")) {
				$result["cas_nr"]=$value;
			}
			elseif (startswith($name,"molecular weight")) {
				$result["mw"]=$value;
			}
			elseif (startswith($name,"empirical formula")) {
				$result["emp_formula"]=$value;
			}
			elseif (strpos($name,"synonym")!==FALSE) {
				$synonyms=explode(", ",$value);
				$result["molecule_names_array"]=arr_merge($result["molecule_names_array"],$synonyms);
			}
			elseif (strpos($name,"hazard codes")!==FALSE || strpos($name,"hazard symbols")!==FALSE) {
				$result["safety_sym"]=$value;
			}
			elseif (strpos($name,"symbol")!==FALSE || strpos($name,"pictogram")!==FALSE) {
				$result["safety_sym_ghs"]=$value;
			}
			elseif (strpos($name,"hazard statement")!==FALSE) {
				$result["safety_h"]=str_replace(array("H"," "),"",$value);
			}
			elseif (strpos($name,"precautionary statement")!==FALSE) {
				$result["safety_p"]=str_replace(array("P"," "),"",$value);
			}
			elseif (strpos($name,"risk statement")!==FALSE) {
				$result["safety_r"]=$value;
			}
			elseif (strpos($name,"safety statement")!==FALSE) {
				$result["safety_s"]=$value;
			}
			elseif ($name=="signal word") {
				$result["safety_text"]=$value;
			}
			elseif (strpos($name,"wgk germany")!==FALSE && $value != "nwg") {
				$result["safety_wgk"]= str_replace("WGK ", "", $value);
			}
			elseif (strpos($name,"refractive index")!==FALSE) {
				cutRange($value,"/D","",false);
				//~ $result["n_20"]=$next_text;
				$result["n_20"]=getNumber($value);
			}
			elseif ($name=="density") {
				//~ cutRange($next_text,"","g/mL",false);
				//~ $result["density_20"]=$next_text;
				$result["density_20"]=getNumber($value);
			}
			elseif ($name=="mp") { // too short
				list($result["mp_low"],$result["mp_high"])=getRange($value);
			}
			elseif ($name=="bp") { // too short
				list($result["bp_low"],$result["bp_high"],$press)=getRange($value);
				if (isEmptyStr($result["bp_high"])) {
					// do nothing
				}
				elseif (trim($press)!="") {
					$result["bp_press"]=getNumber($press);
					if (strpos($press,"mm")!==FALSE) {
						$result["press_unit"]="torr";
					}
					elseif (strpos($press,"hPa")!==FALSE) {
						$result["press_unit"]="mbar";
					}
				}
				else {
					$result["bp_press"]="1";
					$result["press_unit"]="bar";
				}
			}
			elseif (strpos($name,"ec number")!==FALSE) {
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "EG_No", "source" => $this->code, "conditions" => $value);
				}
			}
			elseif (strpos($name,"ridadr")!==FALSE) {
				$result["molecule_property"][]=array("class" => "adr", "source" => $this->code, "conditions" => $value);
				// get packing group, after last comma
				$lastSlash=strrpos($value,"/");
				if ($lastSlash!==FALSE) {
					$result["molecule_property"][]=array("class" => "packing_group", "source" => $this->code, "conditions" => getNumber(substr($value,$lastSlash+1)), );
				}
			}
			elseif (strpos($name,"flash point(c)")!==FALSE) {
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "FP", "source" => $this->code, "value_high" => $value+0.0, "unit" => "°C");
				}
			}
			elseif (strpos($name,"vapor pressure")!==FALSE) {
				$value=str_replace(array("&#x00b0;"),array("°"),$value);
				$vap_press_data=explode(" ",$value,3);
				if (!isEmptyStr($vap_press_data[0]) && !isEmptyStr($vap_press_data[1])) {
					$result["molecule_property"][]=array("class" => "Vap_press", "source" => $this->code, "value_high" => $vap_press_data[0]+0.0, "unit" => $vap_press_data[1], "conditions" => $vap_press_data[2]);
				}
			}
			elseif (strpos($name,"expl. lim.")!==FALSE) { // nur obere Grenze
				if (!isEmptyStr($value)) {
					$result["molecule_property"][]=array("class" => "Ex_limits", "source" => $this->code, "value_high" => $value+0.0, "unit" => "Vol.-%");
				}
			}
		}

		$result["supplierCode"]=$this->code;
		$result["catNo"]=$catNo;
		return $result;
    }

	public function procHitlist(& $response) {
		$body=@$response->getBody();
		if (stripos($body,">Sorry, no results found")!==FALSE) { // the > is important, it is always in the Javascript "quoted"
			return $noResults;
		}

		if (preg_match("/(?ims)<script id=\"__NEXT_DATA__\" type=\"application\/json\">(.*?)<\/script>/",$body,$json_data)) {
			$data=json_decode($json_data[1], true);
			$subData=$data["props"]["apolloState"];
			//print_r($subData);
			if (is_array($subData)) foreach ($subData as $key => $subDataEntry) {
				if (!isEmptyStr($subDataEntry["name"]) && !isEmptyStr($subDataEntry["productKey"])) {
					$brandKey=$subDataEntry["brand"]["id"];

					$results[]=array(
						"name" => fixTags($subDataEntry["name"]),
						"addInfo" => fixTags($subDataEntry["description"]),
						"beautifulCatNo" => fixTags($subDataEntry["productNumber"]),
						"catNo" => fixTags($subData[$brandKey]["key"]."/".$subDataEntry["productKey"]),
						"supplierCode" => $this->code,
					);
				}
			}
		}

		return $results;
    }
}
// ---- End Felix's code 2021-05-06 ----

?>