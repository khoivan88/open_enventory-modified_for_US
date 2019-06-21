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

function getMoleculeFromOwnDB($cas_nr) {
	global $db;
	if ($cas_nr=="") {
		return;
	}
	$res_link=mysqli_query($db,"SELECT molecule.molecule_id FROM (molecule INNER JOIN molecule_names ON molecule.molecule_id=molecule_names.molecule_id) WHERE cas_nr LIKE ".fixStrSQL($cas_nr).";") or die(mysqli_error($db));
	if (mysqli_num_rows($res_link)>0) {
		$result=mysqli_fetch_assoc($res_link);
		return $result["molecule_id"];
	}
}

function createStorageIfNotExist($name) {
	global $db;
	$name=trim($name);
	if ($name=="") {
		return;
	}
	$res_link=mysqli_query($db,"SELECT storage_id FROM storage WHERE storage_name LIKE ".fixStr($name).";") or die(mysqli_error($db));
	if (mysqli_num_rows($res_link)==0) { // neues erstellen
		mysqli_query($db,"INSERT INTO storage (storage_id,storage_name) VALUES (NULL,".fixStr($name).");");
		return mysqli_insert_id($db);
	}
	$result=mysqli_fetch_assoc($res_link);
	return $result["storage_id"];
}

function createMoleculeTypeIfNotExist($name) {
	global $db;
	$name=trim($name);
	if ($name=="") {
		return;
	}
	$res_link=mysqli_query($db,"SELECT molecule_type_id FROM molecule_type WHERE molecule_type_name LIKE ".fixStr($name).";") or die(mysqli_error($db));
	if (mysqli_num_rows($res_link)==0) { // neues erstellen
		mysqli_query($db,"INSERT INTO molecule_type (molecule_type_id,molecule_type_name) VALUES (NULL,".fixStr($name).");");
		return mysqli_insert_id($db);
	}
	$result=mysqli_fetch_assoc($res_link);
	return $result["molecule_type_id"];
}

function createChemicalStorageTypeIfNotExist($name) {
	global $db;
	$name=trim($name);
	if ($name=="") {
		return;
	}
	$res_link=mysqli_query($db,"SELECT chemical_storage_type_id FROM chemical_storage_type WHERE chemical_storage_type_name LIKE ".fixStr($name).";") or die(mysqli_error($db));
	if (mysqli_num_rows($res_link)==0) { // neues erstellen
		mysqli_query($db,"INSERT INTO chemical_storage_type (chemical_storage_type_id,chemical_storage_type_name) VALUES (NULL,".fixStr($name).");");
		return mysqli_insert_id($db);
	}
	$result=mysqli_fetch_assoc($res_link);
	return $result["chemical_storage_type_id"];
}

function repairUnit($unit) {
	$unit=str_replace(
		array("M", ), 
		array("mol/l", ), 
		$unit
	);
	return str_replace(
		array("litros", "litro", "gr", "umol", ), 
		array("l", "l", "g", "µmol", ), 
		strtolower($unit)
	);
}

function getValue($key,$cells) {
	$idx=$_REQUEST["col_".$key];
	if (!isEmptyStr($idx)) {
		return $cells[$idx];
	}
	return $_REQUEST["fixed_".$key];
}
?>