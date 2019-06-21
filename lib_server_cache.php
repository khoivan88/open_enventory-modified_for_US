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

function getAllQueryMD5(& $data) {
	//~ print_r($data);
	return md5($data["dbs"]."_".$data["table"]."_".$data["order_by"],true); // get binary md5
}

function formatQueryMD5($cache_id) {
	return fixBlob(hex2bin(substr($cache_id,4))); // cut off all_
}

function readCache($cache_id="") {
	global $person_id;
	if (isemptystr($person_id) || empty($cache_id)) {
		return array();
	}
	if (startswith($cache_id,"all_")) { // all_ Methode, kein Filter, nur table, dbs, order_by
		$filter="query_md5 LIKE BINARY ".formatQueryMD5($cache_id);
		//~ echo $filter;
	}
	elseif (!is_numeric($cache_id)) { // ungültig
		// error
		return array();
	}
	else { // numerisch für Person (wo ist der Filter??): Jetzt hier (vorher: loginToDB)
		$filter="cache_id=".$cache_id." AND person_id=".$person_id;
	}
	$result=mysql_select_array(array(
		"table" => "cache", 
		"dbs" => -1, 
		"filter" => $filter, 
		"limit" => 1, 
	));
	//~ print_r($result);
	$retval=array();
	
	if (count($result)>0) {
		$retval=unserialize($result[0]["cache_blob"]);
		$retval["created"]=$result[0]["created"];
		$retval["last_update"]=$result[0]["last_update"];
	}

	// verify cache settings
	/* if ((isset($_REQUEST["dbs"]) && $retval["dbs"].""!=$_REQUEST["dbs"]) || (isset($_REQUEST["table"]) && $retval["table"]!=$_REQUEST["table"]) || (isset($_REQUEST["order_by"]) && $retval["order_by"]!=$_REQUEST["order_by"])) {
		echo "X";
		return array();
	}*/
	return $retval;
}

function getPersonCond($person_id) {
	return "person_id=".fixNull($person_id)." AND cache_sess_id=".fixStrSQL(getSessidHash());
}

// getSessidHash()
function writeCache($data,$cache_id="") {
	global $db,$person_id,$query;
	if (isemptystr($person_id)) {
		return false;
	}
	//~ $data["table"]=$query[ $data["table"] ]["base_table"]; // make sure that
	$now=time();
	if (isEmptyStr($cache_id)) {
		$sql_query="INSERT INTO cache SET created=".fixNull($now).",";
	}
	else {
		$sql_query="UPDATE cache SET ";
	}
	// echo "A".$data["dbs"]."Y";
	$sql_query.="last_update=".fixNull($now).",cache_blob=".fixBlob(serialize($data)); // person_id=".$person_id.",
	// detect "all" situation
	//~ print_r($data["filter_obj"]["query_string"]); die();
	// individual_cache für Tabellen/Abfragen, die nicht für alle Benutzer gleich sind
	if (isEmptyStr($data["filter_obj"]["query_string"]) && $query[ $data["table"] ]["cache_mode"]==CACHE_COMMON) { // kein Filter, nur table,dbs,order_by
		if (isEmptyStr($cache_id)) { // INSERT
			$cache_id=getAllQueryMD5($data);
			$sql_query.=",query_md5=".fixBlob($cache_id).",person_id=NULL";
			$cache_id="all_".bin2hex($cache_id);
		}
		else { // UPDATE
			$sql_query.=" WHERE query_md5 LIKE BINARY ".formatQueryMD5($cache_id); // ." AND person_id=NULL"; // .$person_id;
		}
	}
	else {
		if (!isEmptyStr($cache_id)) {
			$sql_query.=" WHERE cache_id=".fixNull($cache_id)." AND person_id=".fixNull($person_id); // set person_id only for special queries
		}
		else {
			$sql_query.=",cache_sess_id=".fixStrSQL(getSessidHash()).",person_id=".fixNull($person_id);
		}
	}
	$sql_query.=";";
	//~ echo $sql_query;
	//~ print_r($data);
	//~ die("X");
	mysqli_query($db,$sql_query) or die($sql_query.mysqli_error($db));
	if (isEmptyStr($cache_id)) {
		$cache_id=mysqli_insert_id($db);
	}
	//~ $_REQUEST["cached_query"]=$cache_id;
	return $cache_id;
}

function gcCache() { // garbage collection
	global $db,$person_id;
	if (empty($person_id)) {
		return false;
	}
	$now=time();
	$sql_query=array("DELETE FROM cache WHERE last_update<".($now-all_cache_time).";","DELETE FROM cache WHERE person_id IS NOT NULL AND last_update<".($now-result_cache_time).";");
	return performQueries($sql_query,$db);
}

function clearCache() {
	global $db,$person_id;
	if (empty($person_id)) {
		return false;
	}
	return mysqli_query($db,"DELETE FROM cache WHERE ".getPersonCond($person_id).";");
}

function truncateCache() {
	global $db;
	return mysqli_query($db,"TRUNCATE TABLE cache;");
}

?>