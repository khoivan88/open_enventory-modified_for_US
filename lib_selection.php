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

function getSelectionCount($table) {
	global $settings;
	if (!is_array($settings["selection"][$table])) {
		return 0;
	}
	$dbs=array_keys($settings["selection"][$table]);
	$retval=0;
	for ($a=0;$a<count($dbs);$a++) {
		$db_id=$dbs[$a];
		$pks=@array_keys($settings["selection"][$table][$db_id]);
		for ($b=0;$b<count($pks);$b++) {
			$pk=$pks[$b];
			if ($settings["selection"][$table][$db_id][$pk]) {
				$retval++;
			}
		}
	}
	return $retval;
}

function clearSelection($table="") {
	global $settings;
	if (empty($table)) {
		$settings["selection"]=array();
		return;
	}
	elseif (!is_array($settings["selection"][$table])) {
		return;
	}
	$dbs=array_keys($settings["selection"][$table]);
	for ($a=0;$a<count($dbs);$a++) {
		$db_id=$dbs[$a];
		$pks=array_keys($settings["selection"][$table][$db_id]);
		if (!count($pks)) {
			unset($settings["selection"][$table][$db_id]);
			continue;
		}
		// quick selection of datasets
		$result=mysql_select_array(array(
			"table" => $table, 
			"dbs" => $db_id, 
			"filter" => getLongPrimary($table)." IN(".join(",",$pks).")", 
			"quick" => true, //2, 
			"hierarchicalResults" => RESULTS_PK_ONLY, 
		));
		if (!count($result["db"][$db_id])) { // garnix mehr da
			unset($settings["selection"][$table][$db_id]);
			continue;
		}
		// rest is eliminated
		$remove_pks=array_diff($pks,$result["db"][$db_id]);
		for ($b=0;$b<count($remove_pks);$b++) {
			$pk=$remove_pks[$b];
			unset($settings["selection"][$table][$db_id][$pk]);
		}
	}
}

function getSelectionFlat($table) {
	global $settings;
	$retval=array();
	if (!is_array($settings["selection"][$table])) {
		return $retval;
	}
	$dbs=array_keys($settings["selection"][$table]);
	for ($a=0;$a<count($dbs);$a++) {
		$db_id=$dbs[$a];
		$pks=array_keys($settings["selection"][$table][$db_id]);
		for ($b=0;$b<count($pks);$b++) {
			$pk=$pks[$b];
			if ($settings["selection"][$table][$db_id][$pk]) {
				$retval[]=array("db_id" => $db_id, "pk" => $pk);
			}
		}
	}
	return $retval;
}

function getSelectionHier($table) {
	global $settings;
	$retval=array();
	if (!is_array($settings["selection"][$table])) {
		return $retval;
	}
	$dbs=array_keys($settings["selection"][$table]);
	for ($a=0;$a<count($dbs);$a++) {
		$db_id=$dbs[$a];
		$pks=array_keys($settings["selection"][$table][$db_id]);
		for ($b=0;$b<count($pks);$b++) {
			$pk=$pks[$b];
			if (!$settings["selection"][$table][$db_id][$pk]) {
				unset($settings["selection"][$table][$db_id][$pk]);
			}
		}
		$retval[$db_id]=array_keys($settings["selection"][$table][$db_id]);
	}
	return $retval;
}

?>