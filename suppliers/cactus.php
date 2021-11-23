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
$GLOBALS["code"]="cactus";
$GLOBALS["suppliers"][$GLOBALS["code"]]=new class extends Supplier {
	public $code;
	public $name = "Cactus";
	public $logo =  "nci_logo2.gif"; 
	public $height =  36; 
	public $noExtSearch =  true; 
	public $strSearchFormat =  "SMILES";
	public $urls=array(
		"server" => "https://cactus.nci.nih.gov" // startPage
	);
	
	function __construct() {
        $this->code = $GLOBALS["code"];
		$this->urls["base"]=$this->urls["server"]."/chemical/structure/";
		$this->urls["startPage"]=$this->urls["server"];
    }
	
	public function getDetailPageURL($catNo) {
		return $this->urls["base"].urlencode($catNo)."/cas?referrer=enventory";
	}
	
	public function getHitlist($searchText,$filter,$mode="ct",$paramHash=array()) {
		global $noConnection,$default_http_options;
		
		$my_http_options=$default_http_options;
		$my_http_options["redirect"]=maxRedir;
		$url=$this->urls["base"].urlencode($searchText);
		if ($filter=="se") {
			// SMILES => CAS
			$url.="/cas";

			$response=oe_http_get($url,$my_http_options);
			if ($response==FALSE) {
				return $noConnection;
			}
			$body=@$response->getBody();

			if ($this->isReplyValid($body)) {
				$cas_nrs=explode("\n",trim(strip_tags($body)));
				$best_cas=getBestCAS($cas_nrs);
				if (!isEmptyStr($best_cas)) {
					$result[]=array("cas_nr" => $best_cas, "catNo" => $best_cas, "supplierCode" => $this->code);
				}
			}
		} elseif (isCAS($searchText)) {
			// MOLfile for CAS
			$url.="/file?format=mol";

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
				$result[]=array("molfile_blob" => $body, "supplierCode" => $this->code);
			}
		}
//~ 	var_dump($result);die($body);
		return $result;
	}
	
	public function strSearch($smiles,$mode="se") {
		return $this->getHitlist($smiles,$mode);
	}
	
	protected function isReplyValid($data) {
		return $data && !startswith($data,"<h1") && !startswith($data,"<!DOCTYPE") && !startswith($data,"Status: ");
	}
}
?>