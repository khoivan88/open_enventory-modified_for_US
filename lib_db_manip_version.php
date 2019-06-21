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

function saveVersionComment($version_comment) {
	global $settings;
	if (!is_array($settings["archive_comments"])) {
		$settings["archive_comments"]=array();
	}
	array_unshift($settings["archive_comments"],$version_comment);
	$settings["archive_comments"]=array_slice(array_unique($settings["archive_comments"]),0,15);
	saveUserSettings();
}

function insertVersionCopy($table,$dbObj,$copyCond,$version_comment,$archive_entity_id=null) {
	// Felder über DESCRIBE holen, pk ersetzen, changed_by,_when setzen
	$fields=getFieldsForTable($table);
	$selectFields=array();
	$archive_table=getArchiveTable($table);
	$archive_pkName=getPkName($archive_table);
	
	for ($a=0;$a<count($fields);$a++) {
		$field=$fields[$a];
		$selectFields[]=$table.".".$field;
	}
	
	// 
	$fields[]="version_comment";
	//~ $selectFields[]=fixStr($_REQUEST["version_comment"]);
	$selectFields[]=fixStr($version_comment);
	
	if (!is_null($archive_entity_id)) {
		$fields[]="archive_entity_id";
		$selectFields[]=fixNull($archive_entity_id);
	}
	
	$result=mysqli_query($dbObj,"INSERT INTO ".$archive_table." (".join(",",$fields).") SELECT ".join(",",$selectFields)." FROM ".$table." WHERE ".$copyCond.";");
	return mysqli_insert_id($dbObj);
}

// ZUR Abfrage archive_from in lib_constants_queries, Zusammenhalt über archive_entity_id, Referenzen untereinander bleiben wie Original (reaction_id,analytical_data_id,...)

function performVersion($table,$db_id,$dbObj,$version_comment) {
	global $db,$db_user;
	$now=time();
	$pkName=getShortPrimary($table);
	$pk=& $_REQUEST[$pkName]; // global wird pk=xy verwendet, zum Schreiben molecule_id=xy etc.
	//~ $longPkName=getLongPrimary($table);
	
	//~ $standardCond=$longPkName."=".$pk;
	$standardCond=$pkName."=".$pk;
	
	for ($a=0;$a<count($fields);$a++) {
		$selectFields[]=$table.".".$fields[$a];
	}
	
	saveVersionComment($version_comment);
	
	switch ($table) {
	case "chemical_storage":
		// Datensatz kopieren
		startTransaction($dbObj);
		$archive_entity_id=insertVersionCopy($table,$dbObj,$standardCond,$version_comment);
		mysqli_query($dbObj,"UPDATE chemical_storage SET history=\"\" WHERE ".$standardCond.";") or die(mysqli_error($dbObj));
		endTransaction($dbObj); // probably obsolete
	break;
	case "reaction":
		startTransaction($dbObj);
		// Datensatz kopieren
		$archive_entity_id=insertVersionCopy($table,$dbObj,$standardCond,$version_comment);
		// reaction_chemical => reaction_id => reaction_archive_id
		insertVersionCopy("reaction_chemical",$dbObj,$standardCond,$version_comment,$archive_entity_id);
		// reaction_property => reaction_id => reaction_archive_id
		insertVersionCopy("reaction_property",$dbObj,$standardCond,$version_comment,$archive_entity_id);
		// analytical_data => reaction_id => reaction_archive_id
		insertVersionCopy("analytical_data",$dbObj,$standardCond,$version_comment,$archive_entity_id);
		// analytical_data_image => reaction_id => reaction_archive_id
		insertVersionCopy("analytical_data_image",$dbObj,$standardCond,$version_comment,$archive_entity_id);
		// gc_peak => reaction_id => reaction_archive_id [analytical_data_id => analytical_data_archive_id]
		insertVersionCopy("gc_peak",$dbObj,$standardCond,$version_comment,$archive_entity_id);
		// reaction_literature => reaction_id => reaction_archive_id
		insertVersionCopy("reaction_literature",$dbObj,$standardCond,$version_comment,$archive_entity_id);
		endTransaction($dbObj);
	break;
	}
}

function recoverOldVersion($table,$dbObj,$recoverCond) { // recoverCond ist archive_entity_id=xyz
	// Felder über DESCRIBE holen, pk ersetzen, changed_by,_when setzen
	$fields=getFieldsForTable($table);
	$selectFields=array();
	$archive_table=getArchiveTable($table);
	$archive_pkName=getPkName($archive_table);
	
	for ($a=0;$a<count($fields);$a++) {
		$field=$fields[$a];
		$selectFields[]=$archive_table.".".$field;
	}
	
	mysqli_query($dbObj,"INSERT INTO ".$table." (".join(",",$fields).") SELECT ".join(",",$selectFields)." FROM ".$archive_table." WHERE ".$recoverCond.";");
	// mysqli_query benutzen, da der AUTO_INCREMENT-Wert festgelegt ist
}

function performRecover($table,$db_id,$dbObj) { // old dataset MUST be properly deleted before!!
	$standardCond="archive_entity_id=".fixNull($_REQUEST["archive_entity"]);
	
	switch ($table) {
	case "chemical_storage":
		startTransaction($dbObj);
		recoverOldVersion($table,$dbObj,"chemical_storage_archive_id=".fixNull($_REQUEST["archive_entity"]));
		endTransaction($dbObj);
	break;
	case "reaction":
		startTransaction($dbObj);
		recoverOldVersion($table,$dbObj,"reaction_archive_id=".fixNull($_REQUEST["archive_entity"]));
		// reaction_chemical => reaction_id => reaction_archive_id
		recoverOldVersion("reaction_chemical",$dbObj,$standardCond);
		// reaction_property => reaction_id => reaction_archive_id
		recoverOldVersion("reaction_property",$dbObj,$standardCond);
		// analytical_data => reaction_id => reaction_archive_id
		recoverOldVersion("analytical_data",$dbObj,$standardCond);
		// analytical_data_image => reaction_id => reaction_archive_id
		recoverOldVersion("analytical_data_image",$dbObj,$standardCond);
		// gc_peak => reaction_id => reaction_archive_id [analytical_data_id => analytical_data_archive_id]
		recoverOldVersion("gc_peak",$dbObj,$standardCond);
		// reaction_literature => reaction_id => reaction_archive_id
		recoverOldVersion("reaction_literature",$dbObj,$standardCond);
		endTransaction($dbObj);
	break;
	}	
}

?>