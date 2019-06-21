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

$GLOBALS["driver_code"]="rsc";
$GLOBALS["publisher"][ $GLOBALS["driver_code"] ]=array(
"driver" => $GLOBALS["driver_code"],
"init" => create_function('',getLiteratureFunctionHeader().'
$self["urls"]["server"]="http://www.rsc.org";
'),
"readPage" => create_function('$body,$cookies,$eff_url',getLiteratureFunctionHeader().'
$retval=$noResults;
if (strpos($body,$self["urls"]["server"])===FALSE) {
	return $retval;
}

//~ $body=utf8_encode($body);
if (preg_match_all("/(?ims)<meta name\=\"(.*?)\" content\=\"([^\"]*)\"/",$body,$meta_matches,PREG_SET_ORDER)) {
	//~ print_r($meta_matches);
	$earlyView=false;
	$authors=array();
	
	foreach ($meta_matches as $match_data) {
		$value=fixHtml($match_data[2],"UTF-8");
		switch ($match_data[1]) {
		case "citation_journal_title": // search for this in table sci_journal
			$retval["sci_journal_name"]=$value;
		break;
		case "DC.Creator":
			$authors[]=$value;
		break;
		case "citation_date":
		case "citation_publication_date":
			$date=date_parse($value);
			$retval["literature_year"]=$date["year"];
		break;
		case "citation_volume":
			$retval["literature_volume"]=$value;
		break;
		case "citation_issue":
			$retval["issue"]=$value;
		break;
		case "citation_firstpage":
			if ($value=="NA") {
				$earlyView=true;
			}
			else {
				$retval["page_low"]=$value;
			}
		break;
		case "citation_lastpage":
			if ($value=="NA") {
				$earlyView=true;
			}
			else {
				$retval["page_high"]=$value;
			}
		break;
		case "citation_doi":
			$retval["doi"]=$match_data[2];
		break;
		case "citation_title":
			$retval["literature_title"]=$value;
		break;
		case "citation_pdf_url":
			$pdf_url=$value;
		break;
		}
	}
	
	$retval["authors"]=join(", ",$authors);
	
	if ($earlyView) {
		unset($retval["literature_volume"]);
		unset($retval["issue"]);
	}
	
	addPDFToLiterature($retval,$pdf_url,$cookies);
}

return $retval;
'),
);
?>