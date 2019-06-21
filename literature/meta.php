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

$GLOBALS["driver_code"]="meta";
$GLOBALS["publisher"][ $GLOBALS["driver_code"] ]=array(
"driver" => $GLOBALS["driver_code"], 
"init" => create_function('',getLiteratureFunctionHeader().'
	$self["urls"]["bjoc"]="http://www.beilstein-journals.org";
	$self["urls"]["csiro"]="http://www.csiro.au";
	$self["urls"]["iop"]="http://iopscience.iop.org";
	$self["urls"]["iucr"]="http://scripts.iucr.org";
	$self["urls"]["jjap"]="http://jjap.jsap.jp";
	$self["urls"]["npg"]="http://www.nature.com";
	$self["urls"]["pnas"]="http://www.pnas.org";
	$self["urls"]["science"]="http://www.sciencemag.org";
	$self["urls"]["springer"]="http://www.springer.com";
	$self["urls"]["thieme"]="https://www.thieme.de";
	$self["urls"]["vch"]="http://onlinelibrary.wiley.com";
'), 
"readPage" => create_function('$body,$cookies,$eff_url',getLiteratureFunctionHeader().'
$retval=$noResults;
foreach ($self["urls"] as $type => $url) {
	if (strpos($body,$url)!==FALSE) {
		$found=true;
		break;
	}
}
if (!$found) {
	return $retval;
}

$body=html_entity_decode($body,ENT_QUOTES,"UTF-8");

// find authors, journal, year, volume, issue (if any), page-range
/* read meta tags
<link rel="schema.PRISM" href="http://prismstandard.org/namespaces/1.2/basic/" />
    <meta name="PRISM.publicationName" content="Japanese Journal of Applied Physics" />
    <meta name="PRISM.issn" content="0021-4922" />

    <meta name="PRISM.eIssn" content="1347-4065" />
    <meta name="PRISM.publicationDate" content="2008-02-15" />
    <meta name="PRISM.volume" content="47" />
    <meta name="PRISM.number" content="2" />
    <meta name="PRISM.startingPage" content="1279">
    <meta name="PRISM.endingPage" content="1283" />

<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/" />
    <meta name="DC.title" content="Phosphorescent Organic Light Emitting Diode Using Vinyl Derivatives of Hole Transport and Dopant Materials">
    <meta name="DC.date" content="2008-02-15" />

    <meta name="DC.creator" content="Akira Kawakami">
    <meta name="DC.creator" content="Eiji Otsuki">
    <meta name="DC.creator" content="Masashi Fujieda">
    <meta name="DC.creator" content="Hiroshi Kita">
    <meta name="DC.creator" content="Hideo Taka">
    <meta name="DC.creator" content="Hisaya Sato">
    <meta name="DC.creator" content="Hiroaki Usui">
    <meta name="DC.source" content="Japanese Journal of Applied Physics 47 (2008)" />
    <meta name="DC.rights" content="Copyright (c) 2008 The Japan Society of Applied Physics" />

    <meta name="DC.identifier" content="http://jjap.jsap.jp/link?JJAP/47/1279">
    <meta name="DC.identifier" content="info:doi/10.1143/JJAP.47.1279">
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
		// search for this in table sci_journal
		case "prism.publicationname":
		case "citation_journal_title":
			$retval["sci_journal_name"]=$value;
		break;
		case "citation_journal_abbrev":
			$retval["sci_journal_abbrev"]=$value;
		break;
		case "citation_authors":
			$retval["authors"]=$value;
		break;
		case "citation_author":
			$authors[]=$value;
		break;
		case "dc.creator":
			if ($type=="jjap") {
				$authors[]=$value;
			}
		break;
		case "prism.publicationdate":
		case "citation_publication_date":
		case "citation_date":
			$date=date_parse($value);
			$retval["literature_year"]=$date["year"];
		break;
		case "prism.volume":
		case "citation_volume":
			$retval["literature_volume"]=$value;
		break;
		case "prism.number":
		case "prism.issue":
		case "citation_issue":
			$retval["issue"]=$value;
		break;
		case "prism.startingpage":
		case "citation_firstpage":
			$retval["page_low"]=$value;
		break;
		case "prism.endingpage":
		case "citation_lastpage":
			$retval["page_high"]=$value;
		break;
		case "dc.title":
		case "citation_title":
			$retval["literature_title"]=$value;
		break;
		case "citation_pdf_url":
			$pdf_url=$value;
		break;
		case "dc.relation": // Thieme
			if (endswith($value,".pdf")) {
				$pdf_url=$value;
			}
		break;
		case "citation_doi":
		case "dc.identifier":
			$retval["doi"]=$value;
		break;
		}
	}
	
	$doiPrefixes=array("info:doi/", "doi:");
	foreach ($doiPrefixes as $doiPrefix) {
		if (startswith($retval["doi"],$doiPrefix)) {
			$retval["doi"]=substr($retval["doi"],strlen($doiPrefix));
		}
	}
	
	if (count($authors)) {
		$retval["authors"]=implode("; ",$authors);
	}
	
	// find PDF URL
	if (empty($pdf_url)) {
		if ($type=="jjap") {
			preg_match("/(?ims)<a[^>]*href\=\"([^\"]*)\"[^>]*>Full Text PDF<\/a>/",$body,$preg_data);
		}
		elseif ($type=="npg") {
			preg_match("/(?ims)<a[^>]*href\=\"([^\"]*)\"[^>]*>Download PDF<\/a>/",$body,$preg_data);
		}
		$pdf_url=$self["urls"][$type].$preg_data[1];
	}
	
	addPDFToLiterature($retval,$pdf_url,$cookies);
}
return $retval;
'), 
);
?>