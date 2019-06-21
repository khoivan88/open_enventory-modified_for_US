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

$GLOBALS["driver_code"]="elsevier";
$GLOBALS["publisher"][ $GLOBALS["driver_code"] ]=array(
"driver" => $GLOBALS["driver_code"],
"init" => create_function('',getLiteratureFunctionHeader().'
$self["urls"]["server"]="http://www.sciencedirect.com";
'),
"readPage" => create_function('$body,$cookies,$eff_url',getLiteratureFunctionHeader().'
$retval=$noResults;
if (strpos($body,$self["urls"]["server"])===FALSE) {
	return $retval;
}

$body=preg_replace("/(?ims)<!--.*?-->/","",$body);

cutRange($body,"Export citation","class=\"permissions\"");

$body=str_replace("&#x2013;","-",$body);

// get journal name
preg_match("/(?ims)<div class\=\"title\".*?>(.*?)<\/div>(.*?)<\/p>/",$body,$preg_data);
$retval["sci_journal_name"]=fixHtml($preg_data[1],"UTF-8");

preg_match("/(?ims)Volume\s+(\d+),\s*Issue\s+(\d+),\s*(?:\d+\s+[a-zA-Z]+\s+)?(\d+),\s*Pages?\s+(\d*)\-?(\d+)/",strip_tags($preg_data[2]),$preg_data);
$retval["literature_volume"]=fixHtml($preg_data[1]);
$retval["issue"]=fixHtml($preg_data[2]);
$retval["literature_year"]=fixHtml($preg_data[3]);
$retval["page_low"]=fixHtml($preg_data[4]);
$retval["page_high"]=fixHtml($preg_data[5]);

// get title
preg_match("/(?ims)<h1.*?>(.*?)<\/h1>/",$body,$preg_data);
$retval["literature_title"]=fixHtml($preg_data[1],"UTF-8");

// get authors
preg_match("/(?ims)class=\"authorGroup.*?>(.*?)<\/ul>/",$body,$preg_data);
$retval["authors"]=str_replace("[Author Vitae]", "",fixHtml(removeHtmlParts($preg_data[1],"sup"),"UTF-8")); // get rid of stars etc

// get doi
preg_match("/(?ims)<a[^>]*href\=\"http:\/\/dx\.doi\.org\/([^\"]*)\"[^>]*>/",$body,$preg_data);
$retval["doi"]=$preg_data[1];

// get link for PDF
preg_match("/(?ims)<a[^>]*pdfurl\=\"([^\"]*)\"[^>]*>[^<]*PDF[^<]*<\/a>/",$body,$preg_data);
$url=$preg_data[1];
//~ print_r($cookies);
addPDFToLiterature($retval,$url,$cookies);

return $retval;
'),
);
?>