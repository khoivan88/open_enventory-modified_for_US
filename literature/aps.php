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

$GLOBALS["driver_code"]="aps";
$GLOBALS["publisher"][ $GLOBALS["driver_code"] ]=array(
"driver" => $GLOBALS["driver_code"],
"init" => create_function('',getLiteratureFunctionHeader().'
$self["urls"]["server"]="http://www.aps.org";
'),
"readPage" => create_function('$body,$cookies,$eff_url',getLiteratureFunctionHeader().'
$retval=$noResults;
if (strpos($body,$self["urls"]["server"])===FALSE) {
	return $retval;
}
cutRange($body,"id=\'content\'");

// Title is in h1
preg_match("/(?ims)<h1[^>]*>(.*?)<\/h1>/",$body,$title);
$retval["literature_title"]=fixHtml($title[1],"UTF-8");

// get citation in h2
preg_match("/(?ims)<h2[^>]*>(.*?)<\/h2>/",$body,$preg_data);
$lines=explode("\n",trim(fixLineEnd($preg_data[1])));

$retval["sci_journal_abbrev"]=fixHtml($lines[0]);
$retval["literature_volume"]=getNumber($lines[1]);
$retval["page_low"]=getNumber($lines[2]);
$retval["literature_year"]=getNumber($lines[3]);
$retval["page_high"]=$retval["page_low"]+getNumber($lines[4])-1;

// get authors
preg_match("/(?ims)<div[^>]+id\=\'aps-authors\'[^>]*>(.*?)<br/",$body,$authors);
$retval["authors"]=fixHtml(removeHtmlParts($authors[1],"sup"),"UTF-8");

// get doi
preg_match("/(?ims)<div[^>]*>DOI:<\/div>.*?<div[^>]*>([^>]*)<\/div>/",$body,$preg_data);
$retval["doi"]=$preg_data[1];

// get link for PDF
preg_match("/(?ims)<a[^>]*href\=\"([^\"]*)\"[^>]*>[^<]*PDF[^<]*<\/a>/",$body,$preg_data);
$pageURL = new Net_URL2($eff_url);
$url=$pageURL->resolve($preg_data[1]);
//~ print_r($cookies);
addPDFToLiterature($retval,$url,$cookies);

return $retval;
'),
);
?>