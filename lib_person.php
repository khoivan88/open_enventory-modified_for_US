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

function getSettingsForPerson($person_id) {
	list($person)=mysql_select_array(array(
			"dbs" => -1,
			"table" => "person", 
			"filter" => "person.person_id=".fixNull($person_id), 
	));
	return unserialize($person["preferences"]);
}

function getSelfViewName($username) {
	return "`self_".$username."`";
}

function getSelfViewUpdateFields() {
	global $tables;
	$retval=array();
	if (is_array($tables["person"]["fields"])) foreach ($tables["person"]["fields"] as $field_name => $data) {
		if ($data["allowSelfChange"]) {
			$retval[]=$field_name;
		}
	}
	return $retval;
}

function getSelfView($user,$username,$person_id) {
	$retval=array();
	$selfViewName=getSelfViewName($username);
	$selfViewUpdateFields=getSelfViewUpdateFields();
	$retval[]="CREATE OR REPLACE ALGORITHM = MERGE VIEW ".$selfViewName." AS SELECT * FROM person WHERE person_id=".$person_id.";";
	if (count($selfViewUpdateFields)) {
		$retval[]="GRANT SELECT,UPDATE (".join(",",$selfViewUpdateFields).") ON ".$selfViewName." TO ".$user.";";
	}
	return $retval;
}

function getGrantArray($requested_permissions,$user,$username,$person_id,$db_name) {
	global $tables;
	if ($requested_permissions & _admin) {
		$grant_all="GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, REFERENCES, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE ON ";
		//~ $grant_all="GRANT ALL ON "; // bug in MySQL
		$retval=array(
			$grant_all.$db_name.".* TO ".$user." WITH GRANT OPTION;",
			$grant_all."mysql.* TO ".$user." WITH GRANT OPTION;",
			"GRANT RELOAD ON *.* TO ".$user." WITH GRANT OPTION;", // seems to be necessary to FLUSH PRIVILEGES
			//~ "GRANT CREATE USER ON *.* TO ".$user." WITH GRANT OPTION;",
			//~ "GRANT SELECT ON mysql.* TO ".$user." WITH GRANT OPTION;",
		);
	}
	elseif (count($tables)) {
		foreach ($tables as $table_name => $table) {
			// remote
			if ($requested_permissions && $table["writePermRemote"]) { // remote writing (for order management)
				$retval[]="GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE remote_".$table_name." TO ".$user.";";
			}
			elseif ($requested_permissions && $table["readPermRemote"]) { // remote reading (molecules, packages,...)
				$retval[]="GRANT SELECT ON TABLE remote_".$table_name." TO ".$user.";";
			}
			// dummy, tables do not contain data, grant select to anyone
			if ($table["createDummy"]) {
				$retval[]="GRANT SELECT ON TABLE dummy_".$table_name." TO ".$user.";";
			}
			// local
			if ($requested_permissions & $table["writePerm"]) {
				$retval[]="GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE ".$table_name." TO ".$user.";";
			}
			elseif ($requested_permissions & $table["readPerm"]) {
				$retval[]="GRANT SELECT ON TABLE ".$table_name." TO ".$user.";";
			}
		}
	}
	$retval=arr_merge($retval,getSelfView($user,$username,$person_id));
	//~ $retval[]="FLUSH PRIVILEGES;";
	return $retval;
}

function handleNewPassword() {
// Kennwortänderung vorbereiten
	if (empty($_REQUEST["new_password"])) {
		if (!empty($_REQUEST["setNewPw"])) {
			echo s("password_none");
		}
		return false;
	}
	changeOwnPassword();
}

function checkPassChar($password) {
	$test_array=array("/\d/","/[A-Za-z]/"); // passwords are not case-sensitive
	foreach ($test_array as $test) {
		if (!preg_match($test,$password)) {
			return false;
		}
	}
	return true;
}

function checkPass($password,$repeat,$allowEmpty=false) { // returns array($code,$message)
	global $db_user;
	if ($password=="" && $allowEmpty) {
		return array(SUCCESS,"");
	}
	if (strip_tags($password)!=$password) {
		return array(FAILURE,s("illegal_password"));
	}
	if ($password!=$repeat) {
		return array(FAILURE,s("password_dont_match"));
	}
	if (strlen($password)<7) {
		return array(FAILURE,s("error_password_too_short"));
	}
	if (strpos($password,$db_user)!==FALSE) {
		return array(FAILURE,s("error_password_not_username"));
	}
	if (!checkPassChar($password)) {
		return array(FAILURE,s("error_password_too_simple"));
	}

	return array(SUCCESS,"");
}

function checkUsername($username) { // returns array($code,$message)
	if ($username=="") {
		return array(FAILURE,s("error_user"));
	}
	elseif (strtolower($username)==ROOT) {
		return array(FAILURE,s("error_root"));
	}
	elseif (strlen($username)>16) {
		return array(FAILURE,s("error_long_user"));
	}
	elseif (preg_match("/^\w{1,16}\$/",$username)==0) {
		return array(FAILURE,s("error_invalid_user"));
	}

	return array(SUCCESS,"");
}

function changeOwnPassword() {
// Kennwort ändern
	global $person_id,$db;
	if (empty($_REQUEST["new_password"])) { // no change
		return true;
	}
	list($success,$message)=checkPass($_REQUEST["new_password"],$_REQUEST["new_password_repeat"]); // returns array($code,$message)
	if ($success!=SUCCESS) {
		echo $message;
		return false;
	}
	if ($person_id==$_REQUEST["person_id"]) { // same person
		$sql_query=array(
			"SET PASSWORD = PASSWORD(".fixStrSQL($_REQUEST["new_password"]).");", 
			//~ "FLUSH PRIVILEGES;",
		);
		performQueries($sql_query,$db);
		//~ if (mysqli_query($db,"SET PASSWORD = PASSWORD(".fixStrSQL($_REQUEST["new_password"]).");")) { // FIXME
		$_SESSION["password"]=$_REQUEST["new_password"];
		echo s("password_changed");
		return true;
		//~ }
		//~ else {
			//~ echo mysqli_error($db);
		//~ }
	}
	return false;
}

function usernameExists($username) {
	// fix for MySQL servers 5.7+
	fixPasswordQuery();
	
	return count(mysql_select_array(array(
		"table" => "password_hash", 
		"filter" => "User=".fixStrSQLSearch($username), 
	))
	)?true:false;
}

function usernameAccessExists($reading_db,$username) {
	global $db;
	
	switchDB($reading_db,$db); // to check for entry in other_db
	return count(mysql_select_array(array(
		"table" => "other_db", 
		"dbs" => -1, 
		"filter" => "db_user=".fixStrSQLSearch($username), 
	))
	)?true:false;
}

function fixPerson() {
	$_REQUEST["username"]=strip_tags($_REQUEST["username"]);
	$_REQUEST["new_password"]=strip_tags($_REQUEST["new_password"]);
	$_REQUEST["remote_host"]=getRemoteHost($_REQUEST["permissions"]);
}

function getRemoteHost($permissions) {
	if ($permissions & _remote_read) {
		return "%";
	}
	else {
		return php_server;
	}
}

?>
