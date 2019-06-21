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

//~ function getModifier($second) {
//~ 	switch ($second) {
//~ 	case "0301":
//~ 		return "acute";
//~ 	case "0302":
//~ 		return "grave";
//~ 	case "0303":
//~ 		return "tilde";
//~ 	case "0304":
//~ 		return "grave";
//~ 	case "0308":
//~ 		return "uml";
//~ 	}
//~ }

//~ function fixBogusChars($html) {
//~ 	$html=preg_replace(
//~ 		"/(?ims)<img[^>]*src\=\"\/entityImage\/\?text\=([0-9a-fA-F]+),([0-9a-fA-F]+)\"[^>]*>/e",
//~ 		"'&'.chr(hexdec('\\1')).getModifier('\\2').';'",
//~ 		$html
//~ 	);
//~ 	$html=fixHtml(
//~ 		$html,"UTF-8"
//~ 	);
//~ 	return $html;
//~ }

$GLOBALS["driver_code"]="acs";
$GLOBALS["publisher"][ $GLOBALS["driver_code"] ]=array(
"driver" => $GLOBALS["driver_code"],
"init" => create_function('',getLiteratureFunctionHeader().'
	$self["urls"]["server"]="http://pubs.acs.org";
'),
"readPage" => create_function('$body,$cookies,$eff_url',getLiteratureFunctionHeader().'
$retval=$noResults;
if (strpos($body,$self["urls"]["server"])===FALSE) {
	return $retval;
}

if (strpos($body,"ERROR: The requested article is not currently available on this site.")!==FALSE) {
	return $noConnection;
}
//~ die($body);
//~ $body=html_entity_decode($body,ENT_QUOTES,"UTF-8");
$body=preg_replace("/\xE2\x80./","",$body); // array("//","//",

$cookies["I2KBRCK"]="1"; // set by hand

// find authors, journal, year, volume, issue (if any), page-range
/* read meta tags
<meta name="dc.Title" content="Palladium-Catalyzed Synthesis of Aryl Ketones by Coupling of Aryl Bromides with an Acyl Anion Equivalent"></meta>
<meta name="dc.Creator" content="Akihiro Takemiya and"></meta>
<meta name="dc.Creator" content="John F. Hartwig*"></meta>
<meta name="dc.Description" content="Palladium-catalyzed couplings of aryl bromides with N-tert-butylhydrazones as acyl anion equivalents to form aryl ketones are reported. The coupling process occurs at the C-position of hydrazones to form N-tert-butyl azo compounds. Isomerization of these azo compounds to the corresponding hydrazones, followed by hydrolysis, gave the desired mixed alkyl aryl ketones. The selectivity of C- versus N-arylation was strongly influenced by the substituent on nitrogen. Arylation at carbon occurred with N-tert-butylhydrazones, whereas N-arylation occurred with N-arylhydrazones. The arylation of hydrazones containing primary and secondary alkyl groups, as well as aryl groups, gave the desired ketones in good yields after hydrolysis. Functional groups on the aromatic ring, such as alkoxy, cyano, trifluoromethyl, carboalkoxy, carbamoyl, and keto groups, were tolerated. This reaction likely occurs by C&#8722;C bond-forming reductive elimination from an intermediate containing an &#951;1-diazaallyl ligand."></meta>
<meta name="dc.Description" content=""></meta>
<meta name="dc.Publisher" content=" American Chemical Society "></meta>
<meta name="dc.Date" scheme="WTN8601" content="November 2, 2006"></meta>
<meta name="dc.Type" content="rapid-communication"></meta>
<meta name="dc.Format" content="text/HTML"></meta>
<meta name="dc.Identifier" scheme="doi" content="10.1021/ja064782t"></meta>
<meta name="dc.Identifier" scheme="crossover-key" content="JACSAt-128-46-14800"></meta>
<meta name="dc.Identifier" scheme="pii" content="S0002-7863(06)04782-2"></meta>
<meta name="dc.Language" content="en"></meta>
<meta name="dc.Coverage" content="world"></meta>
==> incomplete

read html
<title>











    
    
        Palladium-Catalyzed Synthesis of Aryl Ketones by Coupling of Aryl Bromides with an Acyl Anion Equivalent - Journal of the American Chemical Society (ACS Publications)
    


</title>

<div id="authors">Akihiro Takemiya and John F. Hartwig<a class="ref" href="#ja064782tAF1">*</a></div>

<div id="citation"><cite>J. Am. Chem. Soc.</cite>, 
<span class="citation_year">2006</span>, 
<span class="citation_volume">128</span> (46), pp 14800&ndash;14801</div>

<div id="doi"><strong>DOI: </strong>10.1021/ja064782t</div>

<a href="/doi/pdf/10.1021/ja064782t">Hi-Res PDF</a>
*/

preg_match_all("/(?ims)<meta[^>\'\"]*\sname\=[\'\"](.*?)[\'\"][^>]+content\=[\'\"](.*?)[\'\"]/",$body,$meta_matches,PREG_SET_ORDER);
if (preg_match_all("/(?ims)<meta[^>\'\"]*\scontent\=[\'\"](.*?)[\'\"][^>]+name\=[\'\"](.*?)[\'\"]/",$body,$meta_matches2,PREG_SET_ORDER)) {
	foreach ($meta_matches2 as $match_data) {
		// unify order
		$meta_matches[]=array($match_data[0],$match_data[2],$match_data[1]);
	}
}

if (count($meta_matches)) {
	//~ print_r($meta_matches);
	
	$authors=array();
	$pdf_url="";
	foreach ($meta_matches as $match_data) {
		$name=strtolower($match_data[1]);
		$value=fixHtml($match_data[2],"UTF-8");
		
		switch ($name) {
		case "citation_author":
		case "dc.creator":
			$authors[]=$value;
		break;
		case "dc.identifier":
			if (isDOI($value)) {
				$retval["doi"]=$value;
			}
		break;
		}
	}
	
	if (count($authors)) {
		$retval["authors"]=implode("; ",$authors);
	}
}

preg_match("/(?ims)<title>\s*(.*?) \- (.*?) \(ACS Publications\)\s*<\/title>/",$body,$preg_data);

//~ $retval["keywords"]=fixHtml($preg_data[1]);
$retval["literature_title"]=fixHtml($preg_data[1]);
$retval["sci_journal_name"]=fixHtml($preg_data[2]);

//~ preg_match("/(?ims)<div id\=\"authors\">(.*?)<\/div>/",$body,$preg_data);
//~ $retval["authors"]=fixBogusChars(removeHtmlParts($preg_data[1],"sup"));

if (preg_match("/(?ims)<div[^>]+id=\"citation\"[^>]*>\s*<cite>(.*?)<\/cite>\s*,\s*<span class=\"citation_year\">(\d+)<\/span>\s*,\s*<span class=\"citation_volume\">(.*?)<\/span>\s*\((\d+)\),\s+pp\s+(\d+)\D+(\d+)\s*<\/div>/",$body,$preg_data)) {
//~ var_dump($preg_data);die();
	$retval["sci_journal_abbrev"]=fixHtml($preg_data[1]);
	$retval["literature_year"]=fixHtml($preg_data[2]);
	$retval["literature_volume"]=fixHtml($preg_data[3]);
	$retval["issue"]=fixHtml($preg_data[4]);
	$retval["page_low"]=fixHtml($preg_data[5]);
	$retval["page_high"]=fixHtml($preg_data[6]);
}
elseif (preg_match("/(?ims)<div[^>]+id=\"citation\"[^>]*><cite>(.*?)<\/cite>, Article ASAP/",$body,$preg_data)) {
	$retval["sci_journal_abbrev"]=fixHtml($preg_data[1]);
	$retval["literature_year"]=date("Y"); // assume current year
}

//~ preg_match("/(?ims)<div id\=\"doi\"><strong>DOI: <\/strong>(.*?)<\/div>/",$body,$preg_data);
//~ $retval["doi"]=fixHtml($preg_data[1]);

// get PDF
//~ preg_match("/(?ims)<a [^>]*href\=\"([^\"]*)\"[^>]*>.*?Hi\-Res PDF.*?<\/a>/",$body,$preg_data);
preg_match("/(?ims)<a title\=\"(Download the PDF Full Text|View the Full Text PDF)\" href\=\"([^\"]*)\"[^>]*>/",$body,$preg_data);
$url=$self["urls"]["server"].$preg_data[2];
// set DOI
//~ $slash_split=explode("/",$preg_data[2]);
cutRange($preg_data[2],"pdf/","",false);
$retval["doi"]=fixHtml($preg_data[2]);

//~ print_r($cookies);
addPDFToLiterature($retval,$url,$cookies);

return $retval;
'),
/*
"init" => create_function('',getLiteratureFunctionHeader().'
	$self["urls"]["server"]="http://pubs3.acs.org";
	$self["urls"]["search"]=$self["urls"]["server"]."/wls/journals/query/subscriberResults.html";
	$self["urls"]["catNo1"]="http://pubs.acs.org/cgi-bin/article.cgi/";
	$self["urls"]["catNo2"]=".pdf";
	$self["urls"]["doi"]="http://dx.doi.org/";
'),
"getJournalCodes" => create_function('$text',getLiteratureFunctionHeader().' // from /wls/journals/query/subscriberSearch.html
	$codes=array(
		"achre4" => "Accounts of Chemical Research",
		"acbcct" => "ACS Chemical Biology",
		"ancac3" => "ACS Nano",
		"ancham" => "Analytical Chemistry",
		"anchama" => "Analytical Chemistry A-Pages",
		"iecac0" => "I&EC Analytical Edition",
		"bichaw" => "Biochemistry",
		"bcches" => "Bioconjugate Chemistry",
		"bomaf6" => "Biomacromolecules",
		"bipret" => "Biotechnology Progress",
		"cmatex" => "Chemistry of Materials",
		"crtoec" => "Chemical Research in Toxicology",
		"chreay" => "Chemical Reviews",
		"cgdefu" => "Crystal Growth & Design",
		"enfuem" => "Energy & Fuels",
		"esthag" => "Environmental Science & Technology",
		"esthaga" => "Environmental Science & Technology A-Pages",
		"iecred" => "Industrial & Engineering Chemistry Research",
		"iechad" => "Industrial & Engineering Chemistry",
		"iecfa7" => "I&EC Fundamentals",
		"iepdaw" => "I&EC Process Design and Development",
		"iepra6" => "I&EC Product Research and Development",
		"inocaj" => "Inorganic Chemistry",
		"jafcau" => "Journal of Agricultural and Food Chemistry",
		"jacsat" => "Journal of the American Chemical Society",
		"jceaax" => "Journal of Chemical and Engineering Data",
		"iecjc0" => "I&EC Chemical & Engineering Data Series",
		"jcisd8" => "Journal of Chemical Information and Modeling",
		"jci001" => "Journal of Chemical Documentation",
		"jcisd8" => "Journal of Chemical Information and Computer Sciences",
		"jctcce" => "Journal of Chemical Theory and Computation",
		"jcchff" => "Journal of Combinatorial Chemistry",
		"jmcmar" => "Journal of Medicinal Chemistry",
		"jnprdf" => "Journal of Natural Products",
		"joceah" => "Journal of Organic Chemistry",
		"jpchax" => "Journal of Physical Chemistry",
		"jpcafh" => "Journal of Physical Chemistry A",
		"jpcbfk" => "Journal of Physical Chemistry B",
		"jpccck" => "Journal of Physical Chemistry C",
		"jprobs" => "Journal of Proteome Research",
		"langd5" => "Langmuir",
		"mamobx" => "Macromolecules",
		"mpohbp" => "Molecular Pharmaceutics",
		"nalefd" => "Nano Letters",
		"orlef7" => "Organic Letters",
		"oprdfk" => "Organic Process Research and Development",
		"orgnd7" => "Organometallics"
	);
	$retval=array(array(),array(),array());
	foreach ($codes as $code => $full_name) {
		if ($full_name==$text) {
			$retval[0][]=$code;
		}
		elseif (startswith($full_name,$text)) {
			$retval[1][]=$code;
		}
		elseif (strpos($full_name,$text)!==FALSE) {
			$retval[2][]=$code;
		}
	}
	return array_merge($retval[0],$retval[1],$retval[2]);
'),
"getHitlist" => create_function('$citationSearch',getLiteratureFunctionHeader().' // open search and proc <select
	// take only 1st for now
	$journalCode=$self["getJournalCodes"]($citationSearch["sci_journal_name"]);
	if (!count($journalCode)) {
		return $noResults;
	}
	
	// POST
	$url=$self["urls"]["search"];
	if (!empty($citationSearch["volume"]) && (!empty($citationSearch["page_low"] || !empty($citationSearch["page_high"]) ) ) { // vol and 1st page?
		if ($citationSearch["page_low"]==0) {
			$citationSearch["page_low"]=1e7;
		}
		$common=array("op" => "findCitation", "cit_qjrn" => $journalCode[0], "vol" => $citationSearch["volume"], "spn" => min($citationSearch["page_low"],$citationSearch["page_high"]), "mscid" => "");
	}
	elseif (!empty($citationSearch["year"]) ) { // author/keyword/year
		$url.="?op=searchJournals";
		$common=array("field1" => "au,aul", "line1" => );
	}
	
	
'),
"getPDF" => create_function('$text,$type',getLiteratureFunctionHeader().'
	if ($type=="doi") {
		$url=$self["urls"]["doi"].$text;
		$a=@http_get($url,array("redirect" => maxRedir, "useragent" => uA));
		if ($a==FALSE) {
			return $noConnection;
		}
		$a=@http_parse_message($a)->body;
		// get "catNo"
		preg_match("/(?ims)<a href=\"\/cgi-bin\/article\.cgi\/([^\"]*)\.pdf\"[^>]*>PDF<\/a>/",$a,$pdf_link);
		$text=$pdf_link[1];
	}
	$url=$self["urls"]["catNo1"].$text.$self["urls"]["catNo2"];
	$a=@http_get($url,array("redirect" => maxRedir, "useragent" => uA));
	if ($a==FALSE) {
		return $noConnection;
	}
	$a=@http_parse_message($a)->body;
	return $a;
')
*/
);
?>