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

function getMolfileRequest($closeSession) {
	global $permissions,$db;
	
	$db_id=intval($_REQUEST["db_id"]);
	if (empty($db_id)) {
		$db_id=-1;
	}
	
	$retval=array();
	
	if (!empty($_REQUEST["molecule_id"]) && loginToDB(false)) {
		list($retval)=mysql_select_array(array(
			"table" => "molecule_mol", 
			"filter" => "molecule_id=".fixNull($_REQUEST["molecule_id"]), 
			"dbs" => $db_id, 
			"limit" => 1, 
		));
		$retval["table"]="molecule";
	}
	elseif (!empty($_REQUEST["reaction_chemical_id"]) && loginToDB(false)) {
		list($retval)=mysql_select_array(array(
			"table" => "reaction_chemical_mol", 
			"filter" => "reaction_chemical_id=".fixNull($_REQUEST["reaction_chemical_id"]), 
			"dbs" => $db_id, 
			"limit" => 1, 
		));
		if ($retval["role"]==6) {
			$retval["table"]="reaction_chemical";
		}
	}
	elseif (!empty($_REQUEST["reaction_id"]) && loginToDB(false)) {
		list($retval)=mysql_select_array(array(
			"table" => "reaction_mol", 
			"filter" => "reaction_id=".fixNull($_REQUEST["reaction_id"]), 
			"dbs" => $db_id, 
			"limit" => 1, 
		));
		$retval["table"]="reaction";
	}
	elseif (!empty($_REQUEST["timestamp"])) {
		$retval=array("molfile" => $_SESSION["molFile"][$_REQUEST["timestamp"]], );
	}
	
	if ($closeSession) {
		@session_write_close(); // avoid deadlocks
		mysqli_close($db);
	}
	
	$retval["molfile"]=removePipes($retval["molfile"]);
	
	return $retval;
}
?>