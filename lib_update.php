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
require_once "lib_bit_op.php";

function refreshUnitsClasses($db) {
	mysqli_query($db,"TRUNCATE units;"); // ignore errors
	createDefaultTableEntries("units");
	mysqli_query($db,"TRUNCATE class;"); // ignore errors
	createDefaultTableEntries("class");
}

function getUpdateSQL($oldVersion) {
	$sql_query=array();
	switch ($oldVersion) {
	case "0.1":
		$sql_query[]="ALTER TABLE `reaction_chemical` CHANGE `other_db_id` `other_db_id` INT( 10 ) NULL DEFAULT NULL;";
		$sql_query[]="ALTER TABLE `person` CHANGE `permissions` `permissions` INT NULL DEFAULT NULL;";
	break;
	case "0.4":
		//~ $sql_query[]="UPDATE chemical_storage SET tmd_unit=\"g\" WHERE tmd_unit IS NULL;";
		//~ $sql_query[]="UPDATE reaction_chemical SET rc_conc_unit=\"%\" WHERE role=6;";
		//~ $sql_query[]="UPDATE retention_time SET reaction_chemical_id=(SELECT reaction_chemical_id FROM reaction_chemical WHERE reaction_chemical.smiles_stereo=retention_time.smiles_stereo LIMIT 1);";
	break;
	case "0.5":
		$sql_query[]="INSERT IGNORE INTO db_info (name,value) SELECT name,value FROM global_settings WHERE name IN(\"Database\",\"UID\",\"Version\");";
		$sql_query[]="UPDATE storage SET storage_secret=1;"; // by default, disabled
		$sql_query[]="UPDATE chemical_storage SET borrowed_by_db_id=-1 WHERE borrowed_by_db_id IS NULL AND NOT borrowed_by_person_id IS NULL;";
	break;
	case "0.6":
		// add uuids for analytical_data,reaction,lab_journal_uid
		$sql_query[]="UPDATE analytical_data SET analytical_data_uid=UUID() WHERE analytical_data_uid IS NULL;";
		$sql_query[]="UPDATE lab_journal SET lab_journal_uid=UUID() WHERE lab_journal_uid IS NULL;";
		$sql_query[]="UPDATE reaction SET reaction_uid=UUID() WHERE reaction_uid IS NULL;";
	break;
	}
	return $sql_query;
}

/*--------------------------------------------------------------------------------------------------
/ Function: updateFrom
/
/ Purpose: perform special update procedures to correctly transform exisitng data to future database formats, calls itself recursively until the database has the current format
/
/ Parameter:
/ 		$oldVersion : current version of the database, on which the update is applied
/
/ Return : none
/------------------------------------------------------------
/ History:
/ 2009-07-27 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function updateFrom($oldVersion) {
	global $permissions_list_value,$db,$query;
	
	if ($oldVersion==currentVersion) {
		return;
	}
	
	switch ($oldVersion) {
	case 0.1:
		// change permissions system
		// get all persons
		$persons=mysql_select_array(array(
			"dbs" => -1,
			"table" => "person_quick", 
		));
		$sql_query=array();
		$pks=array();
		for ($a=0;$a<count($persons);$a++) {
			if (($persons[$a]["permissions"] & 1)==1) { // & _admin (1) => $permissions_list_value["admin"]
				$pks["admin"][]=$persons[$a]["person_id"];
			}
			elseif (($persons[$a]["permissions"] & 2)==2) { // & _write (2) => $permissions_list_value["write"]
				$pks["write"][]=$persons[$a]["person_id"];
			}
			elseif (($persons[$a]["permissions"] & 4)==4) { // & _read_other (4) => $permissions_list_value["read_other"]
				$pks["read_other"][]=$persons[$a]["person_id"];
			}
			elseif (($persons[$a]["permissions"] & 8)==8) { // & _read (8) => $permissions_list_value["remote_read"]
				$pks["remote_read"][]=$persons[$a]["person_id"];
			}
			elseif (($persons[$a]["permissions"] & 16)==16) { // & _write_own (16) => $permissions_list_value["limited_write"]
				$pks["limited_write"][]=$persons[$a]["person_id"];
			}
			else { // take away anything left
				$pks["none"][]=$persons[$a]["person_id"];
			}
		}
		//~ var_dump($pks);die();
		if (is_array($pks)) foreach ($pks as $key => $pk_arr) {
			$sql_query[]="UPDATE person SET permissions=".fixNull($permissions_list_value[$key])." WHERE person_id IN(".fixArrayList($pk_arr).");";
		}
		//~ print_r($sql_query);die();
		$result=performQueries($sql_query,$db);
		// 
		//~ refreshUsers();
		// ansichten standardmäßig setzen
		$g_settings=getGVar("settings");
		$default_settings=getDefaultGlobalSettings();
		$g_settings["views"]=$default_settings["views"];
		// keep GC and NMR
		$g_settings["customAnalyticsTabs"]=$default_settings["customAnalyticsTabs"];
		// keep logos
		$logos=array("uni_logo","fb_logo");
		foreach ($logos as $logo_id) {
			convertLogos($logo_id);
		}
		setGVar("settings",$g_settings);
		
		if ($result) {
			$newVersion=0.2;
		}
	break;
	case 0.2:
		//~ die("Test this first!!");
		// write units and classes new
		refreshUnitsClasses($db);
		
		// ref_amount verschieben
		// get values
		$query["reaction_property"]["fields"].=",reaction_id";
		$ref_amounts=mysql_select_array(array(
			"dbs" => -1,
			"table" => "reaction_property", 
			"filter" => "reaction_property_name=\"ref_amount\"", 
		));
		
		$ref_amount_units=mysql_select_array(array(
			"dbs" => -1,
			"table" => "reaction_property", 
			"filter" => "reaction_property_name=\"ref_amount_unit\"", 
		));
		
		$ref_amount_units_reaction=array();
		for ($a=0;$a<count($ref_amount_units);$a++) {
			$ref_amount_units_reaction[ $ref_amount_units[$a]["reaction_id"] ]=$ref_amount_units[$a]["reaction_property_value"];
		}
		unset($ref_amount_units);
		
		for ($a=0;$a<count($ref_amounts);$a++) {
			// write values
			$sql_query="UPDATE reaction SET ref_amount=(".fixNull($ref_amounts[$a]["reaction_property_value"])." * (SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQL($ref_amount_units_reaction[ $ref_amounts[$a]["reaction_id"] ])." LIMIT 1)),".
				"ref_amount_unit=".fixStrSQL($ref_amount_units_reaction[ $ref_amounts[$a]["reaction_id"] ])." WHERE reaction_id=".fixNull($ref_amounts[$a]["reaction_id"]).";";
			mysqli_query($db,$sql_query) or die($sql_query." ".mysqli_error($db));
		}
		// delete old values
		$sql_query="DELETE FROM reaction_property WHERE reaction_property_name IN(\"ref_amount\",\"ref_amount_unit\");";
		mysqli_query($db,$sql_query) or die($sql_query." ".mysqli_error($db));
		
		// include_in_auto_transfer verschieben für JEDEN user
		$persons=mysql_select_array(array(
			"dbs" => -1,
			"table" => "person", 
		));
		
		for ($a=0;$a<count($persons);$a++) {
			$person_settings=unserialize($persons[$a]["preferences"]);
			
			if (is_array($person_settings["include_in_auto_transfer"]) && !is_array($person_settings["include_in_auto_transfer"][0])) {
				$person_settings["include_in_auto_transfer"]=array(0 => $person_settings["include_in_auto_transfer"]);
				
				$sql_query="UPDATE person SET preferences=".fixBlob(serialize($person_settings))." WHERE person_id=".fixNull($persons[$a]["person_id"])." LIMIT 1;";
				mysqli_query($db,$sql_query) or die($sql_query." ".mysqli_error($db));
			}
		}
		
		$newVersion=0.3;
	break;
	case 0.3:
		//~ die("test first");
		
		$sql_query=array();
		// move purity to conc in chemical_storage
		$sql_query[]="UPDATE chemical_storage SET chemical_storage_conc=purity/100,chemical_storage_conc_unit=\"%\" WHERE chemical_storage_conc IS NULL;";
		$sql_query[]="UPDATE chemical_storage_archive SET chemical_storage_conc=purity/100,chemical_storage_conc_unit=\"%\" WHERE chemical_storage_conc IS NULL;";
		
		// move purity to conc in reaction_chemical
		$sql_query[]="UPDATE reaction_chemical SET rc_conc=rc_purity/100,rc_conc_unit=\"%\" WHERE rc_conc IS NULL;";
		$sql_query[]="UPDATE reaction_chemical_archive SET rc_conc=rc_purity/100,rc_conc_unit=\"%\" WHERE rc_conc IS NULL;";
		$result=performQueries($sql_query,$db); // needed to identify the ones to fix to indiv
		
		// find reactions and reaction _archives with mixed % and mol/l and set reactants_rc_conc_unit to indiv
		$fix_reactions=mysql_select_array_from_dbObj("DISTINCT reaction.reaction_id FROM reaction LEFT OUTER JOIN reaction_chemical AS a ON reaction.reaction_id=a.reaction_id AND a.role IN(1,2) LEFT OUTER JOIN reaction_chemical AS b ON reaction.reaction_id=b.reaction_id AND b.role IN(1,2) WHERE a.rc_conc_unit!=b.rc_conc_unit;",$db);
		for ($a=0;$a<count($fix_reactions);$a++) {
			$sql_query[]="DELETE FROM reaction_property WHERE reaction_id=".fixNull($fix_reactions[$a]["reaction_id"])." AND reaction_property_name LIKE \"reactants_rc_conc_unit\";";
			$sql_query[]="INSERT INTO reaction_property (reaction_property_name,reaction_property_value,reaction_id) VALUES (\"reactants_rc_conc_unit\",\"\",".fixNull($fix_reactions[$a]["reaction_id"]).");";
		}
		$fix_reactions=mysql_select_array_from_dbObj("DISTINCT reaction.reaction_id,reaction.reaction_archive_id AS archive_entity_id FROM reaction_archive AS reaction LEFT OUTER JOIN reaction_chemical_archive AS a ON reaction.reaction_id=a.reaction_id AND reaction.reaction_archive_id=a.archive_entity_id AND a.role IN(1,2) LEFT OUTER JOIN reaction_chemical_archive AS b ON reaction.reaction_id=b.reaction_id AND reaction.reaction_archive_id=b.archive_entity_id AND b.role IN(1,2) WHERE a.rc_conc_unit!=b.rc_conc_unit;",$db);
		for ($a=0;$a<count($fix_reactions);$a++) {
			$sql_query[]="DELETE FROM reaction_property_archive WHERE reaction_id=".fixNull($fix_reactions[$a]["reaction_id"])." AND archive_entity_id=".fixNull($fix_reactions[$a]["archive_entity_id"])." AND reaction_property_name LIKE \"reactants_rc_conc_unit\";"; //  OR reaction_property_value=\"\"
			$sql_query[]="INSERT INTO reaction_property_archive (reaction_property_name,reaction_property_value,reaction_id,archive_entity_id) 
				VALUES (\"reactants_rc_conc_unit\",\"\",".fixNull($fix_reactions[$a]["reaction_id"]).",".fixNull($fix_reactions[$a]["archive_entity_id"]).");";
		}
		
		// insert reactants_rc_conc_unit=% for all reactions that do not have reactants_rc_conc_unit set
		$fix_reactions=mysql_select_array_from_dbObj("DISTINCT reaction.reaction_id FROM reaction LEFT OUTER JOIN reaction_property ON reaction.reaction_id=reaction_property.reaction_id AND reaction_property_name LIKE \"reactants_rc_conc_unit\" WHERE (reaction_property_value IS NULL);",$db); //  OR reaction_property_value=\"\" // indiv should remain
		for ($a=0;$a<count($fix_reactions);$a++) {
			$sql_query[]="DELETE FROM reaction_property WHERE reaction_id=".fixNull($fix_reactions[$a]["reaction_id"])." AND reaction_property_name LIKE \"reactants_rc_conc_unit\" AND (reaction_property_value IS NULL);"; //  OR reaction_property_value=\"\" // indiv should remain
			$sql_query[]="INSERT INTO reaction_property (reaction_property_name,reaction_property_value,reaction_id) VALUES (\"reactants_rc_conc_unit\",\"%\",".fixNull($fix_reactions[$a]["reaction_id"]).");";
		}
		$fix_reactions=mysql_select_array_from_dbObj("DISTINCT reaction.reaction_id,reaction.reaction_archive_id AS archive_entity_id FROM reaction_archive AS reaction LEFT OUTER JOIN reaction_property_archive AS reaction_property ON reaction.reaction_id=reaction_property.reaction_id AND reaction.reaction_archive_id=reaction_property.archive_entity_id AND reaction_property_name LIKE \"reactants_rc_conc_unit\" WHERE (reaction_property_value IS NULL);",$db); //  OR reaction_property_value=\"\"
		for ($a=0;$a<count($fix_reactions);$a++) {
			$sql_query[]="DELETE FROM reaction_property_archive WHERE reaction_id=".fixNull($fix_reactions[$a]["reaction_id"])." AND archive_entity_id=".fixNull($fix_reactions[$a]["archive_entity_id"])." AND reaction_property_name LIKE \"reactants_rc_conc_unit\" AND (reaction_property_value IS NULL);"; //  OR reaction_property_value=\"\"
			$sql_query[]="INSERT INTO reaction_property_archive (reaction_property_name,reaction_property_value,reaction_id,archive_entity_id) 
				VALUES (\"reactants_rc_conc_unit\",\"%\",".fixNull($fix_reactions[$a]["reaction_id"]).",".fixNull($fix_reactions[$a]["archive_entity_id"]).");";
		}
				
		// insert mayDel in permissions
		$persons=mysql_select_array(array(
			"dbs" => -1,
			"table" => "person_quick", 
		));
		
		for ($a=0;$a<count($persons);$a++) {
			$new_permissions=insertBit($persons[$a]["permissions"],11);
			$sql_query[]="UPDATE person SET permissions=".fixNull($new_permissions)." WHERE person_id=".fixNull($persons[$a]["person_id"]).";";
		}
		
		$result=performQueries($sql_query,$db);
		
		if ($result) {
			$newVersion=0.4;
		}
	break;
	case 0.4:
		// get all reactions and build searcheable fields
		$idx=0;
		$block_size=100;
		do {
			set_time_limit(40);
			// read
			$result=mysql_select_array(array(
				"table" => "reaction_fix_html", 
				"dbs" => -1, 
				"limit" => $idx.",".$block_size,  
			));
			$idx+=$block_size;
			
			// proc
			$sql_query=array();
			for ($a=0;$a<count($result);$a++) {
				$sql_query[]="UPDATE reaction SET ".
					"realization_text_fulltext=".fixStrSQL(makeHTMLSearchable($result[$a]["realization_text"])).",".
					"realization_observation_fulltext=".fixStrSQL(makeHTMLSearchable($result[$a]["realization_observation"])).
					" WHERE reaction_id=".$result[$a]["reaction_id"]." LIMIT 1;";				
			}				
			performQueries($sql_query,$db);
			//~ print_r($sql_query);
			
		} while (count($result));
		
		
		// index PDFs
		$idx=0;
		$block_size=10;
		do {
			set_time_limit(40);
			// read
			$result=mysql_select_array(array(
				"table" => "literature_pdf", 
				"dbs" => -1, 
				"limit" => $idx.",".$block_size,  
			));
			$idx+=$block_size;
			
			// proc
			$sql_query=array();
			for ($a=0;$a<count($result);$a++) {
				if (isPDF($result[$a]["literature_blob"])) {
					$txt=data_convert($result[$a]["literature_blob"],"pdf",array("txt"));
					$sql_query[]="UPDATE literature SET ".
						"literature_blob_fulltext=".fixStrSQL($txt).
						" WHERE literature_id=".$result[$a]["literature_id"]." LIMIT 1;";
				}
			}
			performQueries($sql_query,$db);
			//~ print_r($sql_query);
			
		} while (count($result));
		
		// SQL only
		$newVersion=0.5;
	break;
	default:
		if ($oldVersion>=0.5 && $newVersion<currentVersion) {
			// SQL only
			$newVersion=currentVersion;
		}
	}
	if (!empty($newVersion)) {
		setGVar("Version",$newVersion);
		updateFrom($newVersion);
	}
}

function convertLogos($logo_id) {
	if (!empty($g_settings[$logo_id]) && empty($g_settings["links_in_topnav"][$logo_id])) {
		$g_settings["links_in_topnav"][$logo_id]=$g_settings[$logo_id];
		$g_settings["links_in_topnav"][$logo_id]["name"]=$logo_id;
		unset($g_settings[$logo_id]);
	}
}

?>