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

// Table name

function hasTableRemote($base_table) {
	global $tables;
	return ($tables[$base_table]["readPermRemote"] || $tables[$base_table]["writePermRemote"]);
}

function hasTableRemoteAccess($base_table) {
	global $tables;
	return (hasTableRemote($base_table) || ($tables[$base_table]["readPerm"] & _remote_read));
}

function hasTableDummy($base_table) {
	global $tables;
	return $tables[$base_table]["createDummy"];
}

function hasTableArchive($base_table) {
	global $tables;
	return $tables[$base_table]["versioning"];
}

function getArchiveTable($base_table) {
	if (hasTableArchive($base_table)) {
		return $base_table."_archive";
	}
}

function getRemoteTable($base_table) {
	global $tables;
	if ($tables[$base_table]["readPerm"]&_remote_read) { // no filtering
		return $base_table;
	}
	if (hasTableRemote($base_table)) {
		return "remote_".$base_table;
	}
	if (hasTableDummy($base_table)) {
		return "dummy_".$base_table;
	}
}

function getBaseTable($table) {
	global $query;
	$retval=$query[$table]["base_table"];
	if (empty($retval)) { // no default query defined
		return $table;
	}
	return $retval;
}

function getActionBy($table,$action) {
	return $table."_".$action."_by";
}

function getActionWhen($table,$action) {
	return $table."_".$action."_when";
}

// fields
function getFieldsForTable($table) {
	global $db;
	$result=mysql_select_array_from_dbObj("DESCRIBE ".$table.";",$db,array("noAutoSelect" => true));
	$retval=array();
	for ($a=0;$a<count($result);$a++) {
		$retval[]=$result[$a]["Field"];
	}
	return $retval;
}

function getValueList($table,$field) {
	global $tables;
	return $tables[$table]["fields"][$field]["values"];
}

function getFieldsForTableDesign($table,$paramHash=array()) {
	global $tables;
	$paramHash["skip_types"]=ifempty($paramHash["skip_types"],array());
	$paramHash["skip_fields"]=ifempty($paramHash["skip_fields"],array());
	$retval=array();
	
	if (is_array($tables[$table]["fields"])) foreach ($tables[$table]["fields"] as $name => $data) {
		$field_type=strtoupper($data["type"]);
		if (
			!empty($data["unitCol"])
			|| ($data["flags"] && ($paramHash["flags"] & $data["flags"])==0) 
			|| in_array($field_type,$paramHash["skip_types"]) 
			|| in_array($name,$paramHash["skip_fields"])
		) {
			continue;
		}
		$retval[]=$name;
	}
	return $retval;
}

function getQueryFieldList($paramHash) { // make auto+0 for set and enum
	global $tables;
	$table=$paramHash["table"];
	$alias=ifempty($paramHash["alias"],$table);
	$paramHash["skip_types"]=ifempty($paramHash["skip_types"],array());
	$paramHash["skip_fields"]=ifempty($paramHash["skip_fields"],array());
	$retval=array();
	
	if (is_array($tables[$table]["fields"])) foreach ($tables[$table]["fields"] as $name => $data) {
		$field_type=strtoupper($data["type"]);
		if (
			!empty($data["unitCol"])
			|| ($data["flags"] && ($paramHash["flags"] & $data["flags"])==0)
			|| in_array($field_type,$paramHash["skip_types"]) 
			|| in_array($name,$paramHash["skip_fields"])
		) {
			continue;
		}
		
		$fieldText=$alias.".".$name;
		$force_alias=false;
		
		if (in_array($field_type,array("SET","ENUM"))) {
			$fieldText.="+0";
			$force_alias=true;
		}
		
		if ($force_alias || !empty($paramHash["prefix"])) {
			$fieldText.=" AS ".$paramHash["prefix"].$name;
		}
		$retval[]=$fieldText;
	}
	return join(",",$retval);
}

function getFieldListForTables($table_data_list) {
	$retval=array();
	if (is_array($table_data_list)) foreach ($table_data_list as $table_data) {
		$retval[]=getQueryFieldList($table_data);
	}
	return join(",",$retval);
}

function addFieldListForQuery(& $fields,$table,$alsoLocal=false) {
	global $query;
	
	$fields[]=getFieldListForTables($query[$table]["field_data"]);
	$fields[]=$query[$table]["fields"]; // give this priority
	if ($alsoLocal) {
		$fields[]=$query[$table]["local_fields"];
	}
}

function checkGetFieldsForTable() {
	global $tables;
	foreach ($tables as $table => $data) {
		$describe=getFieldsForTable($table);
		$design=getFieldsForTableDesign($table);
		if ($describe!=$design) {
			echo $table.":<br>";
			print_r(array_diff($describe,$design));
			print_r(array_diff($design,$describe));
		}
	}
}

// pk related

function getPkName($base_table) {
	global $tables;
	//~ return ifempty($tables[$base_table]["pk"],$base_table."_id");
	if (!@$tables[$base_table]["noPk"]) {
		return $base_table."_id";
	}
}

function getShortPrimary($table) {
	global $query;
	if (!empty($query[$table]["short_primary"])) {
		return $query[$table]["short_primary"];
	}
	$base_table=getBaseTable($table);
	//~ return $tables[$base_table]["pk"];
	return getPkName($base_table);
	//~ return $query[$table]["short_primary"];
}

function getLongPrimary($table) {
	global $query;
	if (!empty($query[$table]["primary"])) {
		return $query[$table]["primary"];
	}
	$base_table=getBaseTable($table);
	//~ return $base_table.".".$tables[$base_table]["pk"];
	return ifNotEmpty($base_table.".",getPkName($base_table));
	//~ return $query[$table]["primary"];
}

function getGroupBy($table) {
	$pkName=getLongPrimary($table);
	if (!empty($pkName)) {
		return " GROUP BY ".$pkName;
	}
}

function getPrimary($table,$long=false) {
	global $query;
	return $long?getLongPrimary($table):getShortPrimary($table);
}

//~ function specificArchiveRequest($base_table) { // -1 means all
	//~ if (!hasTableArchive($base_table)) {
		//~ return false;
	//~ }
	//~ return ($_REQUEST["archive_entity"]>0);
//~ }

function archiveRequest($base_table) {
	if (!hasTableArchive($base_table)) {
		return false;
	}
	return !empty($_REQUEST["archive_entity"]);
}

function getJoins($base_table,$join_key,$type) {
	global $tables;
	
	$join_data=& $tables[$base_table]["joins"][$join_key];
	
	if (empty($join_data)) {
		debug_print_backtrace();
		die("Join data ".$join_key." for table ".$base_table." missing.");
	}
	
	$retval="";
	if ($join_data["inner_join"]) {
		$retval.="INNER";
	}
	else {
		$retval.="LEFT OUTER";
	}
	
	$retval.=" JOIN ";
	$condition=$join_data["condition"]." ";
	$join_base_table=ifempty($join_data["base_table"],$join_key);
	$join_alias=ifempty($join_data["alias"],$join_key);
	
	if ($type=="archive" && empty($join_data["archive_condition"])) {
		$type="local";
	}
	
	switch ($type) {
	case "local":
		if ($join_base_table!=$join_key) {
			$retval.=$join_base_table." AS ";
		}
	break;
	case "archive":
		$retval.=getArchiveTable($join_base_table)." AS ";
		$condition.="AND ".$join_data["archive_condition"]." ";
	break;
	case "remote":
		$retval.=getRemoteTable($join_base_table)." AS ";
	break;
	}
	
	$retval.=$join_alias." ON ".$condition;
	return $retval;
}

function getTableFrom($table,$db_id=-1,$skipJoins=false) {
	global $query,$tables,$permissions;
	
	if ($query[$table]["forceTable"]) {
		return $query[$table]["local_from"];
	}
	
	$base_table=getBaseTable($table);
	$alias=ifempty($query[$table]["alias"],$base_table);
	
	if ($db_id==-1 || ($tables[$base_table]["readPerm"] & _remote_read)) { // some tables like change_notify can be read directly
		if (archiveRequest($base_table)) {
			$retval=getArchiveTable($base_table)." AS ".$alias." ";
			if (!$skipJoins) for ($a=0;$a<count($query[$table]["joins"]);$a++) { // list of texts
				$join_key=& $query[$table]["joins"][$a];
				$retval.=getJoins($base_table,$join_key,"archive");
			}
			return $retval;
		}
		else {
			$retval=$base_table." ";
			if ($base_table!=$alias) {
				$retval.="AS ".$alias." ";
			}
			if (!$skipJoins && $query[$table]["joins"]) for ($a=0;$a<count($query[$table]["joins"]);$a++) { // list of texts
				$join_key=& $query[$table]["joins"][$a];
				$retval.=getJoins($base_table,$join_key,"local");
			}
			return $retval;
		}
	}
	else {
		$retval=getRemoteTable($base_table)." AS ".$alias." ";
	}
	
	if (!$skipJoins && is_array($query[$table]["joins"])) for ($a=0;$a<count($query[$table]["joins"]);$a++) { // list of texts
		$join_key=& $query[$table]["joins"][$a];
		$retval.=getJoins($base_table,$join_key,"remote");
	}
	return $retval;
}

function getDeviceResult($transfer_settings) {
	global $settings;
	if (count($settings["include_in_auto_transfer"][$transfer_settings])) {
		return mysql_select_array(array(
			"table" => "analytics_device", 
			"dbs" => -1, 
			//~ "filter" => "analytics_type.analytics_type_code=\"gc\"", 
			"filter" => "analytics_type.analytics_type_id IN(".fixArrayList($settings["include_in_auto_transfer"][$transfer_settings]).")", 
			"filterDisabled" => true, 
		));
	}
	return array();
}

function getDefaultCostCentre() {
	list($cost_centre)=mysql_select_array(array(
		"table" => "cost_centre",
		"filter" => "cost_centre_id=".fixNull(getSetting("default_cost_centre")), 
		"limit" => 1,
	));
	return $cost_centre;
}

function getDOIResult($doi) {
	list($literature)=mysql_select_array(array(
		"table" => "literature", 
		"dbs" => -1, 
		"filter" => "doi LIKE ".fixStrSQLSearch($doi), 
		"limit" => 1, 
	));
	return $literature;
}
?>