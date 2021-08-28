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

function getFullSelfRef($alsoParams=false,$suppress=array(),$call_transparent_params=array()) { // gibt den vollen namen des skripts mit http(s)://server/pfad/datei.php zurück
	/* $retval=getenv("SCRIPT_URI");
	if (!empty($retval)) {
		return $retval;
	} */
	$retval=getServerName();
	if ($alsoParams) {
		return $retval.getSelfRef($suppress,$call_transparent_params);
	}
	else {
		return $retval.getenv("SCRIPT_NAME");
	}
}

function getSelfPath() {
	$retval=getServerName();
	$last_slash_pos=strrpos(getenv("SCRIPT_NAME"),"/");
	if ($last_slash_pos!==FALSE) {
		$retval.=substr(getenv("SCRIPT_NAME"),0,$last_slash_pos);
	}
	return $retval;
}

function getParamsArray($call_transparent_params=array()) {
	global $global_transparent_params,$page_transparent_params;
	$keepParams=arr_merge($global_transparent_params,$page_transparent_params);
	$keepParams=array_unique(arr_merge($keepParams,$call_transparent_params));
	return $keepParams;
}

function getSelfRef($suppress=array(),$call_transparent_params=array()) {
	// gibt referenz auf eigene url zurück, die parameter in suppress werden ausgefiltert, die parameter table,dbs,.. bleiben standardmäßig erhalten
// Ist "~script~" in $suppress, so werden nur die Parameter übergeben
	//~ global $global_transparent_params,$page_transparent_params,$db_name,$db_user;
	global $db_name,$db_user;
	//~ if (!is_array($page_transparent_params)) {
		//~ $page_transparent_params=array();
	//~ }
	$retval="";
	if (!in_array("~script~",$suppress)) {
		if (getenv("DOCKERIZED")) {
			$retval.=ltrim(getenv("SCRIPT_NAME"), '/')."?";
		}
		else {
			$retval.=getenv("SCRIPT_NAME")."?";
		}
	}
	//~ if (!empty($_SESSION["sess_proof"]) && $_REQUEST["sess_proof"]!=$_SESSION["sess_proof"]) { // auto fix sess proof
		//~ $_REQUEST["sess_proof"]=$_SESSION["sess_proof"];
	//~ }
	//~ $keepParams=arr_merge($global_transparent_params,$page_transparent_params);
	//~ $keepParams=array_unique(arr_merge($keepParams,$call_transparent_params));
	$keepParams=getParamsArray($call_transparent_params);
	$retval.=keepParams($keepParams,$suppress);
	if (!isset($_REQUEST["db_name"]) && !in_array("db_name",$suppress)) {
		$retval.="&db_name=".$db_name;
	}
	if (!isset($_REQUEST["user"]) && !in_array("user",$suppress)) {
		$retval.="&user=".$db_user;
	}
	if (!in_array("sess_proof",$suppress)) {
		$retval.="&sess_proof=".$_SESSION["sess_proof"];
	}
	return $retval;
}

function keepParams($paramsArray,$suppress=array()) {
	// gibt &-getrennt name=value-Paare aller in paramsArray enthaltenen Parameter zurück, wenn diese nicht den Wert "" besitzen oder in suppress sind
	if (count($paramsArray)==0) {
		return "";
	}
	foreach ($paramsArray as $name) {
		if (!in_array($name,$suppress)) {
			$value=& $_REQUEST[$name];
			if (!isEmptyStr($value)) {
				$retval[]=$name."=".urlencode($value);
			}
		}
	}
	return @join("&",$retval);
}

function keepAllParams($suppress=array()) {
	// gibt &-getrennt name=value-Paare aller in paramsArray enthaltenen Parameter zurück, wenn diese nicht den Wert "" besitzen oder in suppress sind
	foreach ($_REQUEST as $name => $value) {
		if (!in_array($name,$suppress)) {
			$retval[]=$name."=".urlencode($value);
		}
	}
	return @join("&",$retval);
}

function addParamsJS() {
	$self_ref=array_key_filter($_REQUEST,getParamsArray());
	//~ $self_ref["~script~"]=getenv("SCRIPT_NAME");
	$self_ref["sess_proof"]=$_SESSION["sess_proof"]; // auto set sess_proof for JS
	return "self_ref=".json_encode($self_ref);
}

?>
