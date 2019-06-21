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
// NIST
$GLOBALS["code"]="pubchem";
$code=$GLOBALS["code"];

$GLOBALS["suppliers"][$code]=array(
	"code" => $code, 
	"name" => "PubChem", 
	"logo" => "pubchemlogo_2015.gif", 
	"height" => 36, 
	"noExtSearch" => true, 
	"strSearchFormat" => "SMILES",

"init" => create_function('',getFunctionHeader().'
	$suppliers[$code]["urls"]["server"]="https://pubchem.ncbi.nlm.nih.gov";
	$suppliers[$code]["urls"]["base"]=$urls["server"]."/rest/pug/compound/";
	$suppliers[$code]["urls"]["name_suffix"]="name/";
	$suppliers[$code]["urls"]["smiles_suffix"]="smiles/";
'),
"getDetailPageURL" => create_function('$catNo',getFunctionHeader().'
	return $urls["base"].$urls["smiles_suffix"].urlencode($catNo)."/xrefs/RN/TXT?referrer=enventory";
'),
"getHitlist" => create_function('$searchText,$filter,$mode="ct",$paramHash=array()',getFunctionHeader().' // searchText is either CAS No or SMILES, always one result only
	$result=array();
	$my_http_options=$default_http_options;
	$my_http_options["redirect"]=maxRedir;
	
	if ($filter=="se") {
		// SMILES => CAS
		$url=$urls["base"].$urls["smiles_suffix"].urlencode($searchText)."/xrefs/RN/TXT";
		
		$response=oe_http_get($url,$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}
		$body=@$response->getBody();
		
		if ($self["isReplyValid"]($body)) {
			$cas_nrs=explode("\n",trim(strip_tags($body)));
			$best_cas=getBestCAS($cas_nrs);
			if (!isEmptyStr($best_cas)) {
				$result[]=array("cas_nr" => $best_cas, "catNo" => $best_cas, "supplierCode" => $code);
			}
		}
	} elseif (isCAS($searchText)) {
		// MOLfile for CAS
		$url=$urls["base"].$urls["name_suffix"].urlencode($searchText)."/SDF";
		
		$response=oe_http_get($url,$my_http_options);
		if ($response==FALSE) {
			return $noConnection;
		}
		$body=@$response->getBody();
		$lastIdx=strrpos($body,"\$\$\$\$");
		if ($lastIdx!==FALSE) {
			$body=substr($body,0,$lastIdx);
		}
		if (strpos($body,"\$\$\$\$")===FALSE) {
			// otherwise multiple, better be careful
			$result[]=array("molfile_blob" => $body, "supplierCode" => $code);
		}
	}
//~ 	var_dump($result);die($body);
	return $result;
'),
"isReplyValid" => create_function('$data','
	return $data && !startswith($data,"<h1") && !startswith($data,"<!DOCTYPE") && !startswith($data,"Status: ") && strpos($data,"error:")===FALSE && strpos($data,"NotFound")===FALSE;
'),
"getBestHit" => create_function('& $hitlist,$name=NULL','
	if (count($hitlist)>0) {
		return 0;
	}
'),
"strSearch" => create_function('$smiles,$mode="se"',getFunctionHeader().'
	return $self["getHitlist"]($smiles,$mode);
')
);
$GLOBALS["suppliers"][$code]["init"]();
?>