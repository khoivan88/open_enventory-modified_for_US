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
require_once "lib_person.php";

function performDel($table,$db_id,$dbObj,$forRecover=false) {
	global $db,$query,$db_name,$db_user,$person_id,$permissions,$lang,$g_settings;
	$now=time();
	$pkName=getShortPrimary($table);
	$pk=& $_REQUEST["pk"]; // link, changes global value automatically
	$locked_by=islockedby($db_id,$dbObj,$table,$pk);
	
	$sql_query=array();
	if ($pk=="") {
		return array(FAILURE,s("del_no_pk"));
	}
	elseif ($locked_by["protected"]) {
		return array(FAILURE,s("inform_about_locked1").$locked_by["locked_by"].s("inform_about_locked2"));
	}
	else {
		if (hasTableArchive($table)) { // autosave before delete
			$_REQUEST[$pkName]=$_REQUEST["pk"];
			performVersion($table,$db_id,$dbObj,s("deleted_version"));
		}
		switch ($table) {
		case "analytical_data":
			// check permissions
			
			list($analytical_data)=mysql_select_array(array(
				"table" => "analytical_data_check", 
				"dbs" => -1, 
				"filter" => "analytical_data.analytical_data_id=".fixNull($pk), 
				"limit" => 1, 
			));
			
			if ($analytical_data["lab_journal_status"]>lab_journal_open || $analytical_data["status"]>reaction_open) { // no removal of modification of closed
				return array(FAILURE,s("error_no_lab_journal_closed"));
			}
			
			// ist die Person Student und will fremdes LJ bearbeiten?
			if (($permissions & _lj_edit)==0 && !empty($analytical_data["person_id"]) && $analytical_data["person_id"]!=$person_id) {
				return array(FAILURE,s("permission_denied"));
			}
			
			$sql_query=array(
				"DELETE analytical_data,analytical_data_image,gc_peak 
FROM analytical_data 
LEFT OUTER JOIN analytical_data_image ON analytical_data_image.analytical_data_id=analytical_data.analytical_data_id 
LEFT OUTER JOIN gc_peak ON gc_peak.analytical_data_id=analytical_data.analytical_data_id 
LEFT OUTER JOIN reaction ON analytical_data.reaction_id=reaction.reaction_id 
LEFT OUTER JOIN lab_journal ON reaction.lab_journal_id=lab_journal.lab_journal_id 
WHERE (lab_journal.lab_journal_status IS NULL OR lab_journal.lab_journal_status=1) AND analytical_data.analytical_data_id=".$pk.";", // no LIMIT possible because of multi-table syntax
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "analytics_device":
			$sql_query=array("UPDATE analytics_method SET analytics_device_id=NULL WHERE analytics_device_id=".$pk.";");
			addConditionalUpdate($sql_query,"analytical_data",$db_id,"analytics_device_id=NULL","analytics_device_id=".$pk);
				//~ "UPDATE analytical_data SET analytics_device_id=NULL WHERE analytics_device_id=".$pk.";",
			$sql_query[]="DELETE FROM analytics_device WHERE analytics_device_id=".$pk." LIMIT 1;";
			$result=performQueries($sql_query,$dbObj);
		break;
		case "analytics_method": // auch analytics_method löschen (?)
			$sql_query=array();
			addConditionalUpdate($sql_query,"analytical_data",$db_id,"analytics_method_id=NULL","analytics_method_id=".$pk);
				//~ "UPDATE analytical_data SET analytics_method_id=NULL WHERE analytics_method_id=".$pk.";",
			$sql_query[]="DELETE FROM retention_time WHERE analytics_method_id=".$pk.";";
			$sql_query[]="DELETE FROM analytics_method WHERE analytics_method_id=".$pk." LIMIT 1;";
			$result=performQueries($sql_query,$dbObj);
		break;
		case "analytics_type": // auch analytics_device und analytics_method löschen (?)
			$sql_query=array();
			addConditionalUpdate($sql_query,"analytical_data",$db_id,"analytics_type_id=NULL","analytics_type_id=".$pk);
				//~ "UPDATE analytical_data SET analytics_type_id=NULL WHERE analytics_type_id=".$pk.";",
			array_push($sql_query,
				"UPDATE analytics_method SET analytics_type_id=NULL WHERE analytics_type_id=".$pk.";",
				"UPDATE analytics_device SET analytics_type_id=NULL WHERE analytics_type_id=".$pk.";",
				"DELETE FROM analytics_type WHERE analytics_type_id=".$pk." LIMIT 1;"
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "accepted_order":
			list($accepted_order)=mysql_select_array(array(
				"table" => "accepted_order", 
				"filter" => "accepted_order.accepted_order_id=".fixNull($pk), 
				"limit" => 1, 
				"dbs" => -1, 
			)); // get existing values
			
			if (!empty($chemical_order["order_comp_id"]) || !empty($chemical_order["settlement_id"])) {
				return array(FAILURE,s("permission_denied"));
			}
			$sql_query=array(
				"DELETE FROM accepted_order WHERE accepted_order_id=".$pk." LIMIT 1;",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "chemical_order":
			list($chemical_order)=mysql_select_array(array(
				"table" => "chemical_order", 
				"filter" => "chemical_order.chemical_order_id=".fixNull($pk), 
				"limit" => 1, 
				"dbs" => -1, 
			)); // get existing values
			
			if (!empty($chemical_order["central_order_status"])) {
				return array(FAILURE,s("permission_denied"));
			}
			$sql_query=array(
				"DELETE FROM order_alternative WHERE chemical_order_id=".$pk.";",
				"DELETE FROM chemical_order WHERE chemical_order_id=".$pk." LIMIT 1;",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "chemical_storage":
			if (($permissions & _chemical_edit)==0 && ($permissions & _chemical_delete)==0) {
				return array(FAILURE,s("permission_denied"));
			}
			
			$sql_query=array();
			if ($g_settings["dispose_instead_of_delete"]) {
				$sql_query[]="UPDATE chemical_storage SET disposed_by=".fixStrSQL($db_user).",disposed_when=FROM_UNIXTIME(".fixNull($now)."),chemical_storage_disabled=1 WHERE chemical_storage_id=".$pk." LIMIT 1;";
			}
			else {
				addConditionalUpdate($sql_query,"reaction_chemical",$db_id,"chemical_storage_id=NULL","chemical_storage_id=".$pk);
					//~ "UPDATE reaction_chemical SET chemical_storage_id=NULL WHERE chemical_storage_id=".$pk.";",
				$sql_query[]="DELETE FROM chemical_storage_chemical_storage_type WHERE chemical_storage_id=".$pk.";";
				$sql_query[]="DELETE FROM chemical_storage WHERE chemical_storage_id=".$pk." LIMIT 1;";
			}
			$result=performQueries($sql_query,$dbObj);
		break;
		case "chemical_storage_type":
			$sql_query=array();
			array_push($sql_query,
				"DELETE FROM chemical_storage_chemical_storage_type WHERE chemical_storage_type_id=".$pk.";", 
				"DELETE FROM chemical_storage_type WHERE chemical_storage_type_id=".$pk." LIMIT 1;"
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "cost_centre":
			$sql_query=array(
				"DELETE FROM cost_centre WHERE cost_centre_id=".$pk." LIMIT 1;",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "institution":
			$sql_query=array(
				"UPDATE storage SET institution_id=NULL WHERE institution_id=".$pk.";",
			);
			addConditionalUpdate($sql_query,"order_comp",$db_id,"institution_id=NULL","institution_id=".$pk);
			$sql_query[]="DELETE FROM institution WHERE institution_id=".$pk." LIMIT 1;";
			$sql_query[]="DELETE FROM institution_code WHERE institution_id=".$pk.";";
			$result=performQueries($sql_query,$dbObj);
		break;
		case "literature":
			$sql_query=array(
				"DELETE FROM author WHERE literature_id=".$pk.";",
				"DELETE FROM project_literature WHERE literature_id=".$pk.";",
				"DELETE FROM reaction_literature WHERE literature_id=".$pk.";",
				"DELETE FROM literature WHERE literature_id=".$pk." LIMIT 1;",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "message":
		 	$sql_query=array(
				"DELETE FROM message_person WHERE message_id=".$pk.";",
				"DELETE FROM message WHERE message_id=".$pk." LIMIT 1;",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "molecule":
		 	$sql_query=array();
			
			addConditionalUpdate($sql_query,"reaction_chemical",$db_id,"molecule_id=NULL","molecule_id=".$pk);
				//~ "UPDATE reaction_chemical SET molecule_id=NULL WHERE molecule_id=".$pk.";",
			$sql_query[]="DELETE FROM chemical_storage WHERE molecule_id=".$pk.";";
			addConditionalDelete($sql_query,"supplier_offer",$db_id,"molecule_id=".$pk);
			addConditionalDelete($sql_query,"mat_stamm_nr",$db_id,"molecule_id=".$pk);
				//~ "DELETE FROM mat_stamm_nr WHERE molecule_id=".$pk.";",
			array_push($sql_query,
				"DELETE FROM molecule_molecule_type WHERE molecule_id=".$pk.";", 
				"DELETE FROM molecule_property WHERE molecule_id=".$pk.";",
				"DELETE FROM molecule_instructions WHERE molecule_id=".$pk.";",
				"DELETE FROM molecule_names WHERE molecule_id=".$pk.";",
				"DELETE FROM molecule WHERE molecule_id=".$pk." LIMIT 1;"
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "molecule_type":
			$sql_query=array();
			array_push($sql_query,
				"DELETE FROM molecule_molecule_type WHERE molecule_type_id=".$pk.";", 
				"DELETE FROM molecule_type WHERE molecule_type_id=".$pk." LIMIT 1;"
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "mpi_order":
		 	$sql_query=array(
				"DELETE FROM mpi_order_item WHERE mpi_order_id=".$pk.";",
				"DELETE FROM mpi_order WHERE mpi_order_id=".$pk.";",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "other_db": // restricted to local
			$sql_query=array(
				"DELETE FROM other_db WHERE other_db_id=".$pk." LIMIT 1;", 
			);
			$result=performQueries($sql_query,$db);
			$_REQUEST["updateTopFrame"]="true";
  		break;
		case "person": // restricted to local
			list($username,$remote_host)=get_username_from_person_id($pk);
			if (empty($username)) {
			
			}
			/* elseif ($username==$db_user) {
			// man kann sich selber löschen, wird aber gewarnt
			} */
			else {
				if (empty($remote_host)) {
					$remote_host="%";
				}
				$current_user=fixStrSQL($username)."@".fixStrSQL($remote_host);
				// remove this person from all borrowed items (return them all)
				$sql_query=array(
					"UPDATE chemical_storage SET borrowed_by_person_id=NULL WHERE borrowed_by_person_id=".$pk.";",
					"UPDATE lab_journal SET person_id=NULL WHERE person_id=".$pk.";",
					//~ "DELETE FROM cost_centre WHERE person_id=".$pk.";",
					"DELETE FROM project_person WHERE person_id=".$pk.";",
					"DELETE FROM message_person WHERE person_id=".$pk.";",
					"DELETE FROM person WHERE person_id=".$pk." LIMIT 1;",
					"DROP VIEW IF EXISTS ".getSelfViewName($username).";",
				);
				if ($username!=$db_user) { // unfortunately we cannot remove own privileges and then drop user
					$sql_query[]="REVOKE ALL PRIVILEGES, GRANT OPTION FROM ".$current_user.";";
				}
				$sql_query[]="GRANT USAGE ON *.* TO ".$current_user.";";  # CHKN added back compatibility for MySQL < 5.7 that has no DROP USER IF EXISTS
				$sql_query[]="DROP USER ".$current_user.";";
				$result=performQueries($sql_query,$db);
			}
		break;
		case "project": // restricted to local
			$sql_query=array();
			addConditionalUpdate($sql_query,"reaction",$db_id,"project_id=NULL","project_id=".$pk);
			addConditionalUpdate($sql_query,"reaction_chemical",$db_id,"project_id=NULL","project_id=".$pk);
			addConditionalUpdate($sql_query,"reaction_property",$db_id,"project_id=NULL","project_id=".$pk);
			addConditionalUpdate($sql_query,"reaction_chemical_property",$db_id,"project_id=NULL","project_id=".$pk);
			addConditionalUpdate($sql_query,"gc_peak",$db_id,"project_id=NULL","project_id=".$pk);
			addConditionalUpdate($sql_query,"analytical_data",$db_id,"project_id=NULL","project_id=".$pk);
			array_push($sql_query,
				"DELETE FROM project_person WHERE project_id=".$pk.";",
				"DELETE FROM project_literature WHERE project_id=".$pk.";",
				"DELETE FROM project WHERE project_id=".$pk." LIMIT 1;"
			);
			$result=performQueries($sql_query,$db);
		break;
		case "reaction":
			// verifiy that it is the last reaction in a lab journal by getting the last reaction_id of the journal the reaction to be deleted belongs to
			list($reaction)=mysql_select_array(array(
				"dbs" => $db_id, 
				"table" => "reaction", 
				"filter" => "reaction.reaction_id=".fixNull($pk), 
				"limit" => 1, 
			)); // bestehende Daten abfragen
			//~ mysql_select_array_from_dbObj("reaction_id FROM reaction LEFT OUTER JOIN lab_journal ON reaction.lab_journal_id=lab_journal.lab_journal_id WHERE nr_in_lab_journal=(
					//~ SELECT IFNULL(MAX(nr_in_lab_journal),0) FROM (
						//~ SELECT nr_in_lab_journal,lab_journal_id FROM reaction
					//~ ) AS x 
					//~ WHERE lab_journal_id=reaction.lab_journal_id
				//~ ) AND lab_journal.lab_journal_status=1 AND reaction.reaction_id=".$pk." LIMIT 1;",$dbObj,array("noErrors" => true));
			
			// check permissions
			// ist die Person Student und will fremdes LJ bearbeiten?
			if ($reaction["lab_journal_status"]>lab_journal_open || $reaction["status"]>reaction_open || (($permissions & _lj_edit)==0 && $reaction["person_id"]!=$person_id)) {
				return array(FAILURE,s("permission_denied"));
			}
			
			$pk=$reaction["reaction_id"];
			$sql_query=array(
				//~ "UPDATE analytical_data SET reaction_id=NULL,reaction_chemical_id=NULL WHERE reaction_id=".$pk.";",
				"DELETE analytical_data,analytical_data_image,gc_peak FROM analytical_data 
				LEFT OUTER JOIN analytical_data_image ON analytical_data_image.analytical_data_id=analytical_data.analytical_data_id 
				LEFT OUTER JOIN gc_peak ON gc_peak.analytical_data_id=analytical_data.analytical_data_id 
				WHERE analytical_data.reaction_id=".$pk.";", 
				
				"DELETE FROM reaction_literature WHERE reaction_id=".$pk.";", 
				
				"DELETE FROM reaction_property WHERE reaction_id=".$pk.";", 
				
				"DELETE FROM gc_peak WHERE reaction_id=".$pk.";", 
				
				"DELETE reaction_chemical_property,reaction_chemical FROM reaction_chemical 
				LEFT OUTER JOIN reaction_chemical_property ON reaction_chemical_property.reaction_chemical_id=reaction_chemical.reaction_chemical_id 
				WHERE reaction_id=".$pk.";", 
				
				"DELETE FROM reaction WHERE reaction_id=".$pk." LIMIT 1;", 
			);
			
			if (!$forRecover) {
				// create (almost) blank entry
				$createArr=SQLgetChangeRecord($table,$now,true);
				$createArr["status"]=1;
				$createArr["reaction_created_when"]=fixStrSQL($reaction["reaction_created_when"]);
				$createArr["reaction_created_by"]=fixStrSQL($reaction["reaction_created_by"]);
				//~ $createArr["locked_by"]=fixStrSQL($db_user); // to make edit possible // lock remains after delete with the new locking system
				$createArr=array_merge(
					$createArr,array_key_filter($reaction,array("reaction_id","nr_in_lab_journal","lab_journal_id")
				));
				//~ print_r($reaction);print_r($createArr);die();
				$sql_query[]="INSERT INTO reaction (".join(",",array_keys($createArr)).") VALUES (".join(",",$createArr).");";
			}
			
			//~ print_r($sql_query);die();
			$result=performQueries($sql_query,$dbObj);
			
			if (!$forRecover) {
				// make default values
				$_REQUEST=array_merge($_REQUEST,getDefaultDataset($table));
				$_REQUEST["reaction_id"]=$pk;
				performEdit($table,$db_id,$dbObj);
			}
		break;
		case "reaction_type":
			$sql_query=array();
			addConditionalUpdate($sql_query,"reaction",$db_id,"reaction_type_id=NULL","reaction_type_id=".$pk);
			array_push($sql_query,
				"DELETE FROM reaction_type WHERE reaction_type_id=".$pk." LIMIT 1;"
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "rent":
			list($rent)=mysql_select_array(array(
				"table" => "rent", 
				"filter" => "rent.rent_id=".fixNull($pk), 
				"limit" => 1, 
				"dbs" => -1, 
			)); // get existing values
			
			if (!empty($rent["settlement_id"])) {
				return array(FAILURE,s("permission_denied"));
			}
			$sql_query=array(
				"DELETE FROM rent WHERE rent_id=".$pk." LIMIT 1;",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "sci_journal":
			$sql_query=array(
				"UPDATE literature SET sci_journal_id=NULL WHERE sci_journal_id=".$pk.";",
				"DELETE FROM sci_journal WHERE sci_journal_id=".$pk." LIMIT 1;",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "storage":
			$sql_query=array();
			addConditionalUpdate($sql_query,"chemical_storage",$db_id,"storage_id=NULL","storage_id=".$pk);
				//~ "UPDATE chemical_storage SET storage_id=NULL WHERE storage_id=".$pk.";",
			$sql_query[]="DELETE FROM storage WHERE storage_id=".$pk." LIMIT 1;";
			$result=performQueries($sql_query,$dbObj);
		break;
		case "supplier_offer":
			$sql_query=array(
				"DELETE FROM supplier_offer WHERE supplier_offer_id=".$pk." LIMIT 1;",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		}
	}
	addChangeNotify($db_id,$dbObj,$table,$pk);
	if ($result) {
		return array(SUCCESS,s("data_set_deleted"));
	}
	else {
		return array(FAILURE,s("data_set_not_deleted"),mysqli_error($dbObj));
	}
}

?>