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

require_once "lib_update.php";
require_once "lib_constants_tables.php";
require_once "lib_constants_barcode.php";
require_once "lib_constants_default_settings.php";
require_once "lib_constants_root_funcs.php";
require_once "lib_db_manip_helper.php";
require_once "lib_formatting.php";
require_once "lib_person.php";

define("auto_prefix","auto_");

function generateLinkUsername($read_db,$reading_db) { // db names should be unique although it is of course possible to have the same name twice on different database servers
	if ($read_db==$reading_db) {
		return;
	}
	return auto_prefix.sprintf("%u",crc32($read_db."/".$reading_db));
}

function generateLinkPassword() { // random
	return substr(base64_encode(md5(uniqid(mt_rand(), true),true)),0,12);
}

function createDBLink($read_db,$reading_db,$read_db_host=db_server) {
	global $db,$permissions_list_value;
	$username=generateLinkUsername($read_db,$reading_db);
	$password=generateLinkPassword();
	
	// create user
	switchDB($read_db,$db);
	
	$_REQUEST["person_id"]="";
	$_REQUEST["permissions_general"]=array($permissions_list_value["remote_read"]);
	$_REQUEST["permissions_chemical"]=array();
	$_REQUEST["permissions_lab_journal"]=array();
	$_REQUEST["permissions_order"]=array();
	$_REQUEST["username"]=$username;
	$_REQUEST["last_name"]=s("user_for_remote1").$reading_db.s("user_for_remote2");
	$_REQUEST["new_password"]=$password;
	$_REQUEST["new_password_repeat"]=$password;
	$_REQUEST["remote_host"]="%";
	
	performEdit("person",-1,$db);
	mysqli_query($db,"FLUSH PRIVILEGES;");
	
	// wait for 1.5 s
	usleep(1500000);
	
	if (!checkDBLink($read_db,$username,$password)) {
		return false;
	}
	
	// add to other_db
	switchDB($reading_db,$db);
	$_REQUEST["other_db_id"]="";
	$_REQUEST["other_db_disabled"]="";
	$_REQUEST["host"]=$read_db_host;
	$_REQUEST["db_beauty_name"]=$read_db;
	$_REQUEST["db_name"]=$read_db;
	$_REQUEST["db_user"]=$username;
	$_REQUEST["db_pass"]=$password;
	$_REQUEST["db_pass_repeat"]=$password;
	
	performEdit("other_db",-1,$db);
	return true;
}

function getOtherDBInfo() {
	global $db_info,$db;
	
	$other_db_info=array();
	for ($a=0;$a<count($db_info);$a++) {
		$reading_db=$db_info[$a]["name"];
		switchDB($reading_db,$db);
		
		$other_dbs=mysql_select_array(array(
			"table" => "other_db",
			"dbs" => -1,
		));
		//~ var_dump($other_dbs);
		
		if (count($other_dbs)) {
			$pw_map=array();
			foreach ($other_dbs as $other_db_entry) {
				$pw_map[ $other_db_entry["db_name"]."_".$other_db_entry["db_user"] ]=$other_db_entry["db_pass"];
			}
			$other_db_info[$reading_db]=$pw_map;
		}
	}
	return $other_db_info;
}

function checkDBLink($db_name,$username,$password) {
	global $db,$db_server;
	//~ echo $db_name."X".$username."Y".$password;
	
	$dbtest=@mysqli_connect(db_server,$username,$password);
	if (!$dbtest) {
		//~ echo "Could not connect to ".$db_server." using ".$username."/".$password."\n";
		return false;
	}
	if (!switchDB($db_name,$dbtest)) {
		//~ echo "Could not switch to ".$db_name." using ".$username."/".$password."\n";
		return false;
	}
	@mysqli_close($dbtest);
	
	return true;
}

function getLinkUsernames() {
	return mysql_select_array(array(
		"table" => "password_hash", 
		"filter" => "User LIKE ".fixStrSQL(auto_prefix."%"), 
	));
}

function dropAllLinkUsernames($db_info,$keep_usernames=array()) {
	global $db;
	$table="person";
	$table2="other_db";
	
	// keep these to keep project assignments etc.
	$quoted_list=fixStrSQLLists($keep_usernames);
	$person_filter=ifNotEmpty(" AND username NOT IN(",$quoted_list,")");
	$other_db_filter=ifNotEmpty(" AND db_user NOT IN(",$quoted_list,")");
	
	// go through all databases and query for auto_users and delete them FIXME
	for ($a=0;$a<count($db_info);$a++) {
		switchDB($db_info[$a]["name"],$db);
		
		// get auto_users
		$_REQUEST["db"]=-1;
		$auto_users=mysql_select_array(array(
			"table" => "person_quick", // do not read settings
			"dbs" => -1, 
			"filter" => "username LIKE ".fixStrSQL(auto_prefix."%").$person_filter,
		));
		for ($b=0;$b<count($auto_users);$b++) {
			$_REQUEST["pk"]=$auto_users[$b]["person_id"];
			performDel($table,-1,$db);
		}
		
		// get other_dbs with auto_users
		$other_dbs=mysql_select_array(array(
			"table" => $table2,
			"dbs" => -1, 
			"filter" => "db_user LIKE ".fixStrSQL(auto_prefix."%").$other_db_filter,
		));
		for ($b=0;$b<count($other_dbs);$b++) {
			$_REQUEST["pk"]=$other_dbs[$b]["other_db_id"];
			performDel($table2,-1,$db);
		}
	}
	
	$auto_users=getLinkUsernames(); // remaining ones, if any
	for ($a=0;$a<count($auto_users);$a++) {
		if (!in_array($auto_users[$a]["user"],$keep_usernames)) {
			mysqli_query($db,"GRANT USAGE ON *.* TO ".fixStrSQL($auto_users[$a]["user"])."@".fixStrSQL($auto_users[$a]["host"]).";");
			mysqli_query($db,"DROP USER ".fixStrSQL($auto_users[$a]["user"])."@".fixStrSQL($auto_users[$a]["host"]).";");
		}
	}
	mysqli_query($db,"FLUSH PRIVILEGES;");
}

function getDatabases($db,$filter_db_type=null) {
	global $db_user,$forbidden_db_names;
	if ($db_user!=ROOT) {
		return array();
	}
	
	if ($result=mysqli_query($db,"SHOW DATABASES;")) {
		$totalCount=mysqli_num_rows($result);
		$ret_val=array();
		for($a=0;$a<$totalCount;$a++) { // Datenbanken durchgehen
			$temp=mysqli_fetch_array($result,MYSQLI_ASSOC);
			$db_name=$temp["Database"];
			if (!in_array($db_name,$forbidden_db_names)) {
				// get type and version, filter
				switchDB($db_name,$db);
				
				$db_type=getGVar("Database");
				if (!is_null($filter_db_type) && $filter_db_type!=$db_type) {
					continue;
				}
				
				$version=getGVar("Version");
				
				$ret_val[]=array(
					"name" => $db_name, 
					"type" => $db_type, 
					"version" => $version, 
				);
			}
		}
		mysqli_free_result($result);
	}
	
	return $ret_val;
}

function tableExists($tabname,$dbObj) {
	if ($result=mysqli_query($dbObj,"SHOW TABLES LIKE ".fixStrSQL($tabname).";")) {
		$totalCount=mysqli_num_rows($result);
		mysqli_free_result($result);
	}
	return $totalCount;
}

function getSharedViewDefinition($tabname,$tabdata) {
	$retval="CREATE OR REPLACE VIEW ".getRemoteTable($tabname)." AS SELECT ";
	
	if (isset($tabdata["remoteFields"])) { // only some fields, fill rest with NULL values
		$fields=array();
		for ($a=0;$a<count($tabdata["remoteFields"]);$a++) {
			$fields[]=$tabname.".".$tabdata["remoteFields"][$a];
		}
		
		$null_fields=array_values(array_diff(array_keys($tabdata["fields"]),$tabdata["remoteFields"]));
		for ($a=0;$a<count($null_fields);$a++) {
			$fields[]="NULL AS ".$null_fields[$a];
		}
		
		$retval.=join(",",$fields);
	}
	else {
		$retval.=$tabname.".*";
	}
	
	$retval.=" FROM ".$tabname." ";
	
	if (!empty($tabdata["remoteFilter"])) {
		if (is_array($tabdata["remoteFilter"])) {
			$retval.=join($tabname,$tabdata["remoteFilter"]).";";
		}
		else {
			$retval.=$tabdata["remoteFilter"].";";
		}
		return $retval;
	}
	elseif ($tabdata["defaultSecret"]) {
		$suffix="shared";
		$cond="=TRUE";
	}
	else {
		$suffix="secret";
		$cond=" IS NULL";
	}
	$retval.="WHERE ".$tabname.".".$tabname."_".$suffix.$cond.";";
	return $retval;
}

function getDummyViewDefinition($tabname) {
	$retval="CREATE OR REPLACE VIEW dummy_".$tabname." AS SELECT ".$tabname.".* FROM ".$tabname." WHERE FALSE;";
	return $retval;
}

function getColumn($name,$data) { // Array
	$retval=array();
	// Spaltendefinition
	$dataType=$data["type"];
	
	if (isset($data["default"])) {
		$dataType.=" DEFAULT ".$data["default"];
	}
	
	if (!empty($data["collate"])) {
		$dataType.=" COLLATE ".$data["collate"];
	}
	
	if (!empty($data["fk"])) {
		$dataType.=" REFERENCES ".$data["fk"]."(".getShortPrimary($data["fk"]).")";
	}
	
	$field_def=array(
		"name" => $name, 
		"def" => $dataType, 
		"type" => "field", 
		"collate" => ifempty($data["collate"],COLLATE_TEXT), 
		"default" => $data["default"], 
	);
	
	if (is_array($data["values"])) { // ENUM/SET
		$field_def["def"].="(".join(",",array_map("fixStr",$data["values"])).")";
	}
	
	if (strpos($data["type"],"UNIQUE")!==FALSE) {
		$field_def["auto_index"]=true;
	}
	$retval[]=$field_def;

	// Index auf Anforderung
	if (isset($data["index"])) {
		if ($data["index"]===TRUE) {
			$data["index"]="";
		}
		if ($data["index"]!==FALSE) {
			$retval[]=array("name" => $name, "def" => "(".$name.$data["index"].")", "type" => "index", );
		}
	}
	elseif (!empty($data["fk"])) { // automatische Indizierung von Fremdschlüsseln
		// auto index
		$retval[]=array("name" => $name, "def" => "(".$name.")", "type" => "index", );
	}
	return $retval;
}

function getFieldDefinition(& $field_data,$force_update_collation=false) {
	$retval="";
	switch ($field_data["type"]) {
	// typ field,pk
	case "field":
		$retval=$field_data["name"]." ".$field_data["def"];
		if ($force_update_collation && $field_data["collate"]==COLLATE_TEXT) { // update normal columns with wrong collation
			$retval.=" COLLATE ".$field_data["collate"];
		}
	break;
	// typ index
	case "index":
		$retval="INDEX ".$field_data["name"]." ".$field_data["def"];
	break;
	// typ UNIQUE
	case "unique":
		$retval="UNIQUE ".$field_data["name"]." ".$field_data["def"];
	break;
	// typ custom
	case "custom":
		$retval=$field_data["def"];
	break;
	}
	return $retval;
}

function getSQLFromFieldArray($fieldArray) {
	$retval=array();
	// durchgehen
	for ($a=0;$a<count($fieldArray);$a++) {
		$retval[]=getFieldDefinition($fieldArray[$a]);
	}
	return joinIfNotEmpty($retval,", ");
}

function getCustomIndex($tabdata) { // Array
	if (!empty($tabdata["index"])) {
		return array(
			array("name" => $tabdata["index"]["name"], "def" => "(".join(",",$tabdata["index"]["fields"]).")", "type" => $tabdata["index"]["type"]),
		);
	}
	return array();
}

function getFieldArray($tabname) {
	global $tables;
	$tabdata=& $tables[$tabname];
	$fieldArray=array();
	
	if (is_array($tabdata["fields"])) foreach ($tabdata["fields"] as $name => $data) {
		$fieldArray=array_merge($fieldArray,getColumn($name,$data));
	}
	
	// custom index
	$fieldArray=array_merge($fieldArray,getCustomIndex($tabdata));
	
	return $fieldArray;
}

// funktionen für root, zum einrichten der datenbank
function createTable($tabname) {
	global $db,$tables;
	$create_query="CREATE TABLE IF NOT EXISTS ".$tabname.ifNotEmpty("(",getSQLFromFieldArray(getFieldArray($tabname)),")");
	
	// set db engine
	$engine=ifempty($tables[$tabname]["engine"],storage_engine);
	if (!empty($engine)) {
		$create_query.=" ENGINE=".fixStrSQL($engine);
	}
	$create_query.=";";
	$sql_query[]=$create_query;
	performQueries($sql_query,$db);
	createDefaultTableEntries($tabname);
}

function createDefaultTableEntries($tabname) {
	global $db,$default_table_data;
	// einheiten füllen
	if (is_array($default_table_data[$tabname])) {
		foreach ($default_table_data[$tabname] as $dataset) {
			switch ($tabname) {
			case "units":
				$sql_query[]="INSERT IGNORE INTO ".$tabname." SET ".
					"unit_name=".fixStrSQL($dataset["name"]).",".
					"unit_factor=".fixNull($dataset["factor"]).",".
					//~ "unit_factor=".fixNull($dataset["factorText"]).",". // workaround until #45117 is fixed
					"unit_type=".fixStrSQL($dataset["type"]).",".
					"unit_is_standard=".fixNull($dataset["standard"]).",".
					"units_disabled=".fixNull($dataset["disabled"]).
					";";
			break;
			case "sci_journal":
				$sql_query[]="INSERT INTO ".$tabname." SET ".
					"sci_journal_name=".fixStrSQL($dataset["name"]).",".
					"sci_journal_abbrev=".fixStrSQL($dataset["abbrev"]).",".
					"sci_journal_driver=".fixStrSQL($dataset["driver"]).
					";";
			break;
			case "class":
				$sql_query[]="INSERT INTO ".$tabname." SET ".
					"class_name=".fixStrSQL($dataset["name"]).",".
					"class_type=".fixStrSQL($dataset["type"]).
					";";
			break;
			case "analytics_type":
				$sql_query[]="INSERT INTO ".$tabname." SET ".
					"analytics_type_name=".fixStrSQL($dataset["name"]).",".
					"analytics_type_code=".fixStrSQL($dataset["code"]).
					";";
			break;
			}
		}
	}
	performQueries($sql_query,$db);
}

function createTables() {
	global $tables;
	$tabnames=array_keys($tables);
	foreach ($tabnames as $tabname) {
		createTable($tabname);
	}
}

function createView($tabname) {
	global $db,$tables;
	$tabdata=& $tables[$tabname];
	if (count($tabdata)==0 || !tableExists($tabname,$db)) {
		return;
	}
	if (hasTableRemote($tabname)) {
		$sql_query=getSharedViewDefinition($tabname,$tabdata);
		mysqli_query($db,$sql_query) or die($sql_query." ".mysqli_error($db));
	}
	if ($tabdata["createDummy"]) { // create dummy view of own table with no data (WHERE FALSE) for remote queries on tables that are not public
		$sql_query=getDummyViewDefinition($tabname);
		mysqli_query($db,$sql_query) or die($sql_query." ".mysqli_error($db));
	}
}

function createViews() {
	global $tables;
	foreach (array_keys($tables) as $tabname) {
		createView($tabname);
	}
}

function containsInvalidChars($text) {
	if (preg_match("/^[a-zA-Z0-9_]+\$/",$text)) {
		return false;
	}
	return true;
}

function isReservedWord($text) {
	$reserved_words=array("ADD","ALL","ALTER","ANALYZE","AND","AS","ASC","ASENSITIVE","BEFORE","BETWEEN","BIGINT","BINARY","BLOB","BOTH","BY","CALL","CASCADE","CASE","CHANGE","CHAR","CHARACTER","CHECK","COLLATE","COLUMN","CONDITION","CONNECTION","CONSTRAINT","CONTINUE","CONVERT","CREATE","CROSS","CURRENT_DATE","CURRENT_TIME","CURRENT_TIMESTAMP","CURRENT_USER","CURSOR","DATABASE","DATABASES","DAY_HOUR","DAY_MICROSECOND","DAY_MINUTE","DAY_SECOND","DEC","DECIMAL","DECLARE","DEFAULT","DELAYED","DELETE","DESC","DESCRIBE","DETERMINISTIC","DISTINCT","DISTINCTROW","DIV","DOUBLE","DROP","DUAL","EACH","ELSE","ELSEIF","ENCLOSED","ESCAPED","EXISTS","EXIT","EXPLAIN","FALSE","FETCH","FLOAT","FLOAT4","FLOAT8","FOR","FORCE","FOREIGN","FROM","FULLTEXT","GRANT","GROUP","HAVING","HIGH_PRIORITY","HOUR_MICROSECOND","HOUR_MINUTE","HOUR_SECOND","IF","IGNORE","IN","INDEX","INFILE","INNER","INOUT","INSENSITIVE","INSERT","INT","INT1","INT2","INT3","INT4","INT8","INTEGER","INTERVAL","INTO","IS","ITERATE","JOIN","KEY","KEYS","KILL","LEADING","LEAVE","LEFT","LIKE","LIMIT","LINES","LOAD","LOCALTIME","LOCALTIMESTAMP","LOCK","LONG","LONGBLOB","LONGTEXT","LOOP","LOW_PRIORITY","MATCH","MEDIUMBLOB","MEDIUMINT","MEDIUMTEXT","MIDDLEINT","MINUTE_MICROSECOND","MINUTE_SECOND","MOD","MODIFIES","NATURAL","NOT","NO_WRITE_TO_BINLOG","NULL","NUMERIC","ON","OPTIMIZE","OPTION","OPTIONALLY","OR","ORDER","OUT","OUTER","OUTFILE","PRECISION","PRIMARY","PROCEDURE","PURGE","RAID0","READ","READS","REAL","REFERENCES","REGEXP","RELEASE","RENAME","REPEAT","REPLACE","REQUIRE","RESTRICT","RETURN","REVOKE","RIGHT","RLIKE","SCHEMA","SCHEMAS","SECOND_MICROSECOND","SELECT","SENSITIVE","SEPARATOR","SET","SHOW","SMALLINT","SONAME","SPATIAL","SPECIFIC","SQL","SQLEXCEPTION","SQLSTATE","SQLWARNING","SQL_BIG_RESULT","SQL_CALC_FOUND_ROWS","SQL_SMALL_RESULT","SSL","STARTING","STRAIGHT_JOIN","TABLE","TERMINATED","THEN","TINYBLOB","TINYINT","TINYTEXT","TO","TRAILING","TRIGGER","TRUE","UNDO","UNION","UNIQUE","UNLOCK","UNSIGNED","UPDATE","USAGE","USE","USING","UTC_DATE","UTC_TIME","UTC_TIMESTAMP","VALUES","VARBINARY","VARCHAR","VARCHARACTER","VARYING","WHEN","WHERE","WHILE","WITH","WRITE","X509","XOR","YEAR_MONTH","ZEROFILL",);
	$text=strtoupper($text);
	return in_array($text,$reserved_words);
}

function setupInitTables($db_name) { // requires root
	global $db,$db_uid,$db_server,$db_user,$g_settings,$default_g_settings,$forbidden_db_names;
	if ($db_user!=ROOT || !$db) { // only root is allowed to setup the tables, connection required
		return false;
	}
	
	if (containsInvalidChars($db_name)) {
		return s("error_db_invalid_chars");
	}
	
	if (isReservedWord($db_name) || in_array(strtolower($db_name),$forbidden_db_names)) {
		return s("error_db_name_reserved");
	}
	
	// silently remove problematic users
	mysqli_query($db,"GRANT USAGE ON *.* TO ''@'".php_server.";");  # CHKN added back compatibility for MySQL < 5.7 that has no DROP USER IF EXISTS
	mysqli_query($db,"DROP USER ''@'".php_server."';");
	mysqli_query($db,"GRANT USAGE ON *.* TO ''@'%';");  # CHKN added back compatibility for MySQL < 5.7 that has no DROP USER IF EXISTS
	mysqli_query($db,"DROP USER ''@'%';");
	
	mysqli_query($db,"CREATE DATABASE IF NOT EXISTS ".$db_name." CHARACTER SET ".CHARSET_TEXT." COLLATE ".COLLATE_TEXT.";") or die("Error creating database ".mysqli_error($db));
	// CHARACTER SET utf8 COLLATE utf8_unicode_ci
	// CHARACTER SET latin1 COLLATE latin1_german1_ci
	switchDB($db_name,$db) or die(mysqli_error($db));
	$version=getGVar("Version");
	if (empty($version)) {
		// Tabellen erstellen
		createTables();
		createViews();
		// Views erstellen (rely on tables)
		setGVar("Version",currentVersion);
		$version=currentVersion;
		setDBtype();
		$g_settings=getDefaultGlobalSettings();
		setGVar("settings",$g_settings);
	}
	
	$db_uid=getGVar("UID");
	// make sure that uid exists
	if (empty($db_uid)) {
		$db_uid=uniqid();
		setGVar("UID",$db_uid);
	}
	
	if ($version!=currentVersion) {
		// redirect to update.php
		echo script.
		"self.location.href=".fixStr("update.php?".getSelfRef(array("~script~"))).";".
		_script;
		//~ updateFrom($version);
	}
	return true;
}

function getFullUsername($username,$remote_host) {
	return fixStrSQL($username)."@".fixStrSQL($remote_host);
}

function refreshUsers($createNew=true) {
	global $db,$db_name,$db_user,$permissions,$query;
	if (($permissions & _admin)==0) {
		return false;
	}
	
	if ($createNew) {
		// fix for MySQL servers 5.7+
		fixPasswordQuery();
		// passwort-hashes sichern
		$mysql_data=mysql_select_array(array("table" => "password_hash"));
	}
	
	// personen lesen
	$personen=mysql_select_array(array(
		"table" => "person_quick", 
		"dbs" => "-1", 
	));

	// print_r($personen);

	// benutzerrechte neu schreiben, kennwort = benutzername, falls user nicht bekannt
	if (is_array($personen)) foreach ($personen as $this_person) {
		if (empty($this_person["username"]) || $db_user==$this_person["username"]) {
			continue;
		}
		// create user
		$remote_host=getRemoteHost($this_person["permissions"]);
		$user=getFullUsername($this_person["username"],$remote_host);
		
		list($oldusername,$oldremote_host)=get_username_from_person_id($this_person["person_id"]);  // CHKN - if we want to update, we have to drop useres on old remote_host, not on new, as they should still be inexistant on the latter
		if (empty($oldremote_host)) {
			$oldremote_host="%";
		}
		$olduser=getFullUsername($oldusername,$oldremote_host);
		
		for ($a=0;$a<count($mysql_data);$a++) {
			if ($mysql_data[$a]["user"]==$oldusername && $mysql_data[$a]["host"]==$oldremote_host) {
				$password=$mysql_data[$a]["password"];
				break;
			}
		}
		createViews();
		mysqli_query($db,"REVOKE ALL PRIVILEGES, GRANT OPTION FROM ".$olduser.";");
		$sql_query=array(
			"FLUSH PRIVILEGES;", 
		);
		if ($createNew) {
            mysqli_query($db,"GRANT USAGE ON *.* TO ".$olduser.";");  // CHKN added back compatibility for MySQL < 5.7 that has no DROP USER IF EXISTS
			mysqli_query($db,"DROP USER ".$olduser.";"); // result unimportant	
			mysqli_query($db,"DROP VIEW IF EXISTS ".getSelfViewName($oldusername).";"); // result unimportant	
			if (empty($password)) {
				$sql_query[]="CREATE USER ".$user." IDENTIFIED BY ".fixStrSQL($this_person["username"]).";"; // username is pwd,MUST be changed
			}
			else {
				$sql_query[]="CREATE USER ".$user." IDENTIFIED BY PASSWORD ".fixStrSQL($password).";";
			}
			$sql_query[]="UPDATE person SET remote_host = '".$remote_host."' WHERE username = '".$this_person["username"]."';";  // CHKN - Updating the internal person table to have correct remote_host (as it sets the current remote_host as such)
		}
		// give permissions
		$sql_query[]="REVOKE ALL PRIVILEGES, GRANT OPTION FROM ".$user.";";
		if (!$this_person["person_disabled"]) {
			$sql_query=array_merge($sql_query,getGrantArray($this_person["permissions"],$user,$this_person["username"],$this_person["person_id"],$db_name));
		}
		$result=performQueries($sql_query,$db);
	}
	return true;
}

function setDBtype() {
	setGVar("Database",db_type);
}

function updateCurrentDatabaseFormat($perform=false) {
	global $db,$tables;
	// get list of tables and add columns/remove columns/alter columns
	$create_tables=array();
	$remove_tables=array();
	$check_tables=array();
	$existing_tables=array();
	
	// fix tables that are not utf8_unicode_ci
	if ($result=mysqli_query($db,"SHOW TABLE STATUS WHERE NOT Collation LIKE ".fixStr(COLLATE_TEXT).";")) {
		$totalCount=mysqli_num_rows($result);
		for($a=0;$a<$totalCount;$a++) {
			$temp=mysqli_fetch_array($result,MYSQLI_NUM); // Col name is dynamic
			$sql="ALTER TABLE ".$temp[0]." DEFAULT CHARACTER SET ".CHARSET_TEXT." COLLATE ".COLLATE_TEXT.";";
			echo $sql."<br>";
			if ($perform) {
				mysqli_query($db,$sql) or die($sql.mysqli_error($db));
			}
		}
		mysqli_free_result($result);
	}
	
	
	// Tabellen, die da sind
	if ($result=mysqli_query($db,"SHOW FULL TABLES WHERE Table_type LIKE \"BASE TABLE\";")) {
		$totalCount=mysqli_num_rows($result);
		for($a=0;$a<$totalCount;$a++) {
			$temp=mysqli_fetch_array($result,MYSQLI_NUM); // Col name is dynamic
			if (array_key_exists($temp[0],$tables)) { // lib_constants_tables
				$existing_tables[]=$temp[0];
			}
			else {
				$remove_tables[]=$temp[0];
			}
		}
		mysqli_free_result($result);
	}
	
	// Tabellen, die da sein sollten
	if (is_array($tables)) foreach ($tables as $table_name => $data) {
		if (in_array($table_name,$existing_tables)) {
			$check_tables[]=$table_name;
		}
		else {
			$create_tables[]=$table_name;
		}
	}
	unset($existing_tables);
	
	echo "<b>-- Remove tables</b><br>";
	//~ print_r($remove_tables);
	if (is_array($remove_tables)) foreach ($remove_tables as $table_name) {
		echo "<b>  -- Remove table '".$table_name."'</b><br>";
		$sql="DROP TABLE ".$table_name.";";
		echo $sql."<br>";
		if ($perform) {
			mysqli_query($db,$sql) or die($sql.mysqli_error($db));
		}
	}
	unset($remove_tables);
	
	echo "<b>-- Create tables</b><br>";
	//~ print_r($create_tables);
	if (is_array($create_tables)) foreach ($create_tables as $table_name) {
		echo "<b>  -- Create table '".$table_name."'</b><br>";
		if ($perform) {
			createTable($table_name);
		}
	}
	unset($create_tables);
	
	echo "<b>-- Check tables</b><br>";
	//~ print_r($check_tables);
	if (is_array($check_tables)) foreach ($check_tables as $table_name) {
		echo "<b>  -- Check table '".$table_name."'</b><br>";
		// compare DESCRIBE with $table[$table_name]
		$create_fields=array();
		$remove_fields=array();
		$check_fields=array();
		$existing_fields=array();
		$existing_indices=array();
		$remove_indices=array();
		
		$field_list=getFieldArray($table_name);
		//~ print_r($field_list);die();
		
		$alter_commands=array();
		
		// Fields
		if ($result=mysqli_query($db,"SHOW FULL COLUMNS FROM ".$table_name.";")) {
			$totalCount=mysqli_num_rows($result);
			for($a=0;$a<$totalCount;$a++) {
				$temp=mysqli_fetch_array($result,MYSQLI_ASSOC);
				$found=false;
				for ($b=0;$b<count($field_list);$b++) {
					if ($field_list[$b]["type"]!="field" && $field_list[$b]["type"]!="pk") {
						continue;
					}
					if ($field_list[$b]["name"]==$temp["Field"]) {
						$found=true;
						// check collate or default value, if any
						if (empty($temp["Key"]) && 
							((!empty($temp["Collation"]) && ($field_list[$b]["collate"]!=$temp["Collation"]))
							|| ($temp["Default"]=="NULL"?isset($field_list[$b]["default"]):$temp["Default"]!==$field_list[$b]["default"]))
						) {
							$alter_commands[]="CHANGE ".$field_list[$b]["name"]." ".getFieldDefinition($field_list[$b],true);
						}
						break;
					}
				}
				if ($found) {
					$existing_fields[]=$temp["Field"];
				}
				else {
					$remove_fields[]=$temp["Field"];
				}
			}
			mysqli_free_result($result);
		}
		
		$more_indices=array();
		
		if (is_array($field_list)) foreach ($field_list as $idx => $field_data) {
			if ($field_data["type"]!="field" && $field_data["type"]!="pk") {
				continue;
			}
			if ($field_data["auto_index"]) {
				$more_indices[]=$field_data["name"];
			}
			if (in_array($field_data["name"],$existing_fields)) {
				$check_fields[]=$idx;
			}
			else {
				$create_fields[]=$idx;
			}
		}
		unset($existing_fields);
		
		// Indices
		// Aktionen können bei Fields mitlaufen
		if ($result=mysqli_query($db,"SHOW INDEX FROM ".$table_name.";")) {
			$totalCount=mysqli_num_rows($result);
			for($a=0;$a<$totalCount;$a++) {
				$temp=mysqli_fetch_array($result,MYSQLI_ASSOC);
				if ($temp["Key_name"]=="PRIMARY") {
					continue;
				}
				$found=false;
				if (in_array($temp["Key_name"],$more_indices)) {
					$found=true;
				}
				if (!$found) for ($b=0;$b<count($field_list);$b++) {
					if (!in_array($field_list[$b]["type"],array("index","unique"))) {
						continue;
					}
					if ($field_list[$b]["name"]==$temp["Key_name"]) {
						$found=true;
						break;
					}
				}
				if ($found) {
					$existing_indices[]=$temp["Key_name"];
				}
				else {
					$remove_indices[]=$temp["Key_name"];
				}
			}
			mysqli_free_result($result);
		}
		
		if (is_array($field_list)) foreach ($field_list as $idx => $field_data) {
			if (!in_array($field_data["type"],array("index","unique"))) {
				continue;
			}
			if (in_array($field_data["name"],$existing_indices)) {
				$check_fields[]=$idx;
			}
			else {
				$create_fields[]=$idx;
			}
		}
		unset($existing_indices);
		
		echo "<b>    -- Remove fields</b><br>";
		//~ print_r($remove_fields);
		if (is_array($remove_fields)) foreach ($remove_fields as $field_name) {
			//~ $sql="ALTER TABLE ".$table_name." DROP ".$field_name.";";
			$alter_commands[]="DROP ".$field_name;
			//~ echo $sql."<br>";
			//~ if ($perform) {
				//~ mysqli_query($db,$sql) or die($sql.mysqli_error($db));
			//~ }
		}
		unset($remove_fields);
		
		echo "<b>    -- Remove indices</b><br>";
		//~ print_r($remove_indices);
		$remove_indices=array_unique($remove_indices);
		if (is_array($remove_indices)) foreach ($remove_indices as $field_name) {
			//~ $sql="ALTER TABLE ".$table_name." DROP INDEX ".$field_name.";";
			$alter_commands[]="DROP INDEX ".$field_name;
		}
		unset($remove_indices);
		
		// create fields
		echo "<b>    -- Create fields, indices</b><br>";
		//~ print_r($create_fields);
		if (is_array($create_fields)) foreach ($create_fields as $idx) {
			switch ($field_list[$idx]["type"]) {
			case "field":
			case "pk":
				//~ $sql="ALTER TABLE ".$table_name." ADD COLUMN ".getFieldDefinition($field_list[$idx]).";";
				$alter_commands[]="ADD COLUMN ".getFieldDefinition($field_list[$idx]);
			break;
			case "unique":
			case "index":
				//~ $sql="ALTER TABLE ".$table_name." ADD ".getFieldDefinition($field_list[$idx]).";"; // keyword INDEX comes from function
				$alter_commands[]="ADD ".getFieldDefinition($field_list[$idx]);
			break;
			default:
				continue 2;
			}
		}
		unset($create_fields);
		
		if (count($alter_commands)) {
			$sql="ALTER TABLE ".$table_name." ".join(", ",$alter_commands).";";
			echo $sql."<br>";
			if ($perform) {
				// build ALTER TABLE command
				mysqli_query($db,$sql) or die($sql.mysqli_error($db));
			}
		}
	}
	
	if ($perform) {
		createViews();
	}	
}

function prepareWorkingInstructions($result,
	$languages,$fieldsWithDefaults) {
	
	global $db,$g_settings;
	
	$list_int_name="molecule_instructions";
	//~ var_dump($result[$list_int_name]);die();
	//~ var_dump($_REQUEST);die();
	$_REQUEST[$list_int_name]=array();
	foreach ($languages as $language) {
		switch ($_REQUEST["betr_anw_".$language]) {
		case "create_missing":
			// check if there is one
			if (is_array($result[$list_int_name])) foreach ($result[$list_int_name] as $entry) {
				if ($entry["lang"]==$language) {
					// something is there
					break 2;
				}
			}
		case "create_or_replace":
			// DELETE anything present for this $language
			mysqli_query($db,"DELETE FROM molecule_instructions WHERE molecule_id=".$result["molecule_id"]." AND lang LIKE ".fixStrSQL($language).";");
		case "append":
			// auto-generate array of symbols for protective equipment from substance data, like regularly done in Javascript
			$protEquip=getProtEquip($result["safety_s"],$result["safety_p"],$result["safety_h"]);
			
			// get any texts from previous entries, append default unless already present
			$defaults=array();
			foreach ($fieldsWithDefaults as $fieldWithDefaults) {
				$defaults[$fieldWithDefaults]=$g_settings["instr_defaults"][$fieldWithDefaults][$language];
			}
			if (is_array($result[$list_int_name])) foreach ($result[$list_int_name] as $entry) {
				if ($entry["lang"]==$language) {
					foreach ($fieldsWithDefaults as $fieldWithDefaults) {
						$oldValue=$entry[$fieldWithDefaults];
						if (!endswith($oldValue,$defaults[$fieldWithDefaults])) {
							// append default text if not yet present
							$oldValue.=" ".$defaults[$fieldWithDefaults];
						}
						// set new value
						$defaults[$fieldWithDefaults]=$oldValue;
					}
					break; // use only the newest entry
				}
			}
			
			// create new betr_anw
			$UID=uniqid();
			$_REQUEST[$list_int_name][]=$UID;
			$_REQUEST["desired_action_".$list_int_name."_".$UID]="add";
			$_REQUEST[$list_int_name."_".$UID."_lang"]=$language;
			$_REQUEST[$list_int_name."_".$UID."_betr_anw_schutzmass_sym"]=$protEquip;
			$_REQUEST[$list_int_name."_".$UID."_betr_anw_verhalten_sym"]=$protEquip;
			$_REQUEST[$list_int_name."_".$UID."_betr_anw_erste_h_sym"]=array("E003");
			foreach ($fieldsWithDefaults as $fieldWithDefaults) {
				$_REQUEST[$list_int_name."_".$UID."_".$fieldWithDefaults]=$defaults[$fieldWithDefaults];
			}
			//~ var_dump($_REQUEST);die();
		break;
		}
	}
}
?>