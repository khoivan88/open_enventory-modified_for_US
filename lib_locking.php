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

/*--------------------------------------------------------------------------------------------------
/ Function: clearLocks
/
/ Purpose: remove all locks for the current database session
/
/ Parameter:
/ 		$db_id : number of the database, not used, only for compat
/ 		$dbObj : database handle, currently always $db
/
/ Return : nothing
/------------------------------------------------------------
/ History:
/ 2009-07-27 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function clearLocks($db_id,$dbObj) {
	if (!mayWrite("lock_table",$db_id)) {
		return;
	}
	$sql_query=array(
		"DELETE FROM lock_table WHERE locked_sess_id LIKE BINARY ".fixStr(getSessidHash())." OR locked_when<FROM_UNIXTIME(".(time()-db_lock_max).");",
	);
	performQueries($sql_query,$dbObj);
}

/*--------------------------------------------------------------------------------------------------
/ Function: islockedby
/
/ Purpose: check if a dataset is locked for the current session
/
/ Parameter:
/ 		$db_id : number of the database, not used, only for compat
/ 		$dbObj : database handle, currently always $db
/ 		$table : table for which the lock is set
/ 		$primary : primary key for which the lock is set
/
/ Return : dataset belonging to lock
/------------------------------------------------------------
/ History:
/ 2009-07-27 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function islockedby($db_id,$dbObj,$table,$primary) {
	if (empty($table) || $primary=="") {
		return;
	}
	list($result)=mysql_select_array_from_dbObj("locked_by,locked_sess_id,locked_when,locked_type,locked_when>FROM_UNIXTIME(".(time()-db_lock_protect).") AS protected FROM lock_table WHERE for_table=".fixStrSQL($table)." AND pk=".fixNull($primary)." LIMIT 1;",$dbObj);
	return $result;
}

function unlock($db_id,$dbObj,$table,$primary,$force=false) {
// entsperrt den Datensatz mit dem Primärschlüssel primary in Tabelle table, sofern der User ihn gelockt hat oder force aktiv ist
	return handleLock(UNLOCK,$db_id,$dbObj,$table,$primary,$force);
}

function lock($db_id,$dbObj,$table,$primary,$force=false) {
	// sperrt den Datensatz mit dem Primärschlüssel primary in Tabelle table, sofern force aktiv ist oder niemand anders den Datensatz gelockt hat
	return handleLock(LOCK,$db_id,$dbObj,$table,$primary,$force);
}

function renew_lock($db_id,$dbObj,$table,$primary) {
	// erneuert die Sperre für den Datensatz mit dem Primärschlüssel primary in Tabelle table
	return handleLock(RENEW,$db_id,$dbObj,$table,$primary);
}

function getSessidHash() {
	return md5(session_id());
}

function handleLock($action,$db_id,$dbObj,$table,$primary,$force=false) { // $_REQUEST["db_id"]!=-1 behandeln
	global $db_user;
	if (empty($table) || $primary=="") {
		return false;
	}
	
	$mayWrite=mayWrite($table);
	if (!$mayWrite[$db_id]) {
		return "";
	}
	
	$locked_by=islockedby($db_id,$dbObj,$table,$primary);
	
	// Zeitgrenze prüfen
	if ($locked_by["protected"] && !empty($locked_by["locked_sess_id"]) && $locked_by["locked_by"]!=$db_user && $locked_by["locked_sess_id"]!=getSessidHash()) {
		return array(FAILURE,s("inform_about_locked1").$locked_by["locked_by"].s("inform_about_locked2"));
	}
	
	switch ($action) {
	case UNLOCK:
		if ($locked_by["locked_sess_id"]=="") {
			return array(SUCCESS,"");
		}
		if ($force || $locked_by["locked_sess_id"]==getSessidHash()) {
			$result=mysqli_query($dbObj,"DELETE FROM lock_table WHERE for_table=".fixStrSQL($table)." AND pk=".fixNull($primary).";");
			return array(($result?SUCCESS:FAILURE),mysqli_error($dbObj));
		}
	break;
	case LOCK:
		//~ if ($locked_by["locked_sess_id"]==getSessidHash()) { // should we warn about this?
			//~ return array(SUCCESS,"");
		//~ }
		if ($force || $locked_by["locked_sess_id"]=="") {
			$result=mysqli_query($dbObj,"REPLACE INTO lock_table (for_table,pk,locked_by,locked_when,locked_sess_id) 
									VALUES (".fixStrSQL($table).",".fixNull($primary).",".fixStrSQL($db_user).",NOW(),".fixStrSQL(getSessidHash()).");");
			return array(($result?SUCCESS:FAILURE),mysqli_error($dbObj));
		}
	break;
	case RENEW:
		$result=mysqli_query($dbObj,"UPDATE lock_table SET locked_when=NOW() WHERE for_table=".fixStrSQL($table)." AND pk=".fixNull($primary)." AND locked_sess_id=".fixStrSQL(getSessidHash()).";") or dieAsync(mysqli_error($dbObj));
		if ($result && mysqli_affected_rows($dbObj)==1) {
			return array(SUCCESS,"");
		}
		else {
			// if the username is ok and the lock is not renewed properly, take it to new session
			$result=mysqli_query($dbObj,"UPDATE lock_table SET locked_when=NOW(),locked_sess_id=".fixStrSQL(getSessidHash())." WHERE for_table=".fixStrSQL($table)." AND pk=".fixNull($primary)." AND locked_when<=FROM_UNIXTIME(".(time()-db_lock_protect).");") or dieAsync(mysqli_error($dbObj));
			if ($result && mysqli_affected_rows($dbObj)==1) {
				return array(SUCCESS,"");
			}
			else {
				return array(FAILURE,s("error_renew_lock"));
			}
		}
	break;
	}
	return array(QUESTION,s("warn_about_locked1").$locked_by["locked_by"].s("warn_about_locked2"));
}

?>