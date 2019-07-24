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

/* function isOrderManager() {
	global $permissions;
	return (capabilities & 1) && (($permissions & _admin) || ($permissions & _order_accept));
}*/

function mayReadRemote($table) {
	global $tables;
	return ($tables[$table]["readPermRemote"]!=0 || $tables[$table]["writePermRemote"]!=0);
}

function mayWrite($table,$only_db_id=null) {
	global $permissions,$tables,$person_id,$result;
	if (is_null($only_db_id)) {
		$retval=array();
		$retval[-1]=(bool)($permissions & $tables[$table]["writePerm"]);
		if (is_array($_SESSION["db_permissions"])) foreach ($_SESSION["db_permissions"] as $db_id => $db_perm) {
			$retval[$db_id]=(bool)($db_perm & $tables[$table]["writePerm"]);
		}
		return $retval;
	}
	elseif ($only_db_id==-1) {
		return (bool)($permissions & $tables[$table]["writePerm"]);
	}
	else {
		return (bool)($_SESSION["db_permissions"][$only_db_id] & $tables[$table]["writePerm"]);
	}
}

function mayCreate($table,$only_db_id=null) {
	global $permissions,$tables;
	if (isset($tables[$table]["createPerm"])) {
		if (is_null($only_db_id)) { // array for all DBs
			$retval=array();
			$retval[-1]=(bool)($permissions & $tables[$table]["createPerm"]);
			if (is_array($_SESSION["db_permissions"])) foreach ($_SESSION["db_permissions"] as $db_id => $db_perm) {
				$retval[$db_id]=(bool)($db_perm & $tables[$table]["createPerm"]);
			}
			return $retval;
		}
		elseif ($only_db_id==-1) {
			return (bool)($permissions & $tables[$table]["createPerm"]);
		}
		else {
			return (bool)($_SESSION["db_permissions"][$only_db_id] & $tables[$table]["createPerm"]);
		}
	}
	return mayWrite($table,$only_db_id);
}

function mayDelete($table,$only_db_id=null) {
	global $permissions,$tables;
	if (isset($tables[$table]["deletePerm"])) {
		if (is_null($only_db_id)) { // array for all DBs
			$retval=array();
			$retval[-1]=(bool)($permissions & $tables[$table]["deletePerm"]);
			if (is_array($_SESSION["db_permissions"])) foreach ($_SESSION["db_permissions"] as $db_id => $db_perm) {
				$retval[$db_id]=(bool)($db_perm & $tables[$table]["deletePerm"]);
			}
			return $retval;
		}
		elseif ($only_db_id==-1) {
			return (bool)($permissions & $tables[$table]["deletePerm"]);
		}
		else {
			return (bool)($_SESSION["db_permissions"][$only_db_id] & $tables[$table]["deletePerm"]);
		}
	}
	return mayWrite($table,$only_db_id);
}

function mayRead($table) {
	global $permissions,$tables;
	if (!isset($permissions)) {
		return true; // not set yet, assume true
	}
	if ($permissions & $tables[$table]["readPerm"]) {
		return true;
	}
}


?>