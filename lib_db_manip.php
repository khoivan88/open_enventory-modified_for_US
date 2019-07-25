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
/*
Bibliothek zum Ändern von Werten in der Datenbank. Erklärungen zu den einzelnen Befehlen am Ort
*/
require_once "lib_constants.php";
require_once "lib_constants_default_dataset.php";
require_once "lib_molfile.php";
require_once "lib_person.php";
require_once "lib_locking.php";
require_once "lib_db_manip_helper.php";
require_once "lib_db_manip_del.php";
require_once "lib_db_manip_edit.php";
require_once "lib_db_manip_version.php";
// require_once "lib_language.php"; // dont load here because otherwise all langs are loaded
// require_once "lib_io.php";
	
function handleDesiredAction() { // return array(success,message_text,message_data) success: 0=nothing to do, 1=successful, 2=error, 3=interaction required, message_text is in local language
	global $error,$db,$db_name,$db_user,$person_id,$permissions,$own_data,$table,$baseTable,$pk_name,$pk,$selectTables,$unit_result,$page_type,$settings,$reaction_chemical_lists;
	
	$action=$_REQUEST["desired_action"];
	if (empty($action)) { // fix wrong if no action is done
		//~ $_REQUEST["sess_proof"]=$_SESSION["sess_proof"];
		return array(NO_ACTION,"");
	}
	
	if (empty($_REQUEST["sess_proof"]) || $_REQUEST["sess_proof"]!=$_SESSION["sess_proof"]) {
		return array(FAILURE,s("no_session_data")." ".$_REQUEST["sess_proof"]."/".$_SESSION["sess_proof"]);
	}
	
	$db_id=intval($_REQUEST["db_id"]);
	
	// fine permissions check
	$permission_denied=false;
	switch ($action) {
	case "add":
		if (!mayCreate($baseTable,$db_id)) {
			$permission_denied=true;
		}
	break;
	case "update":
		if (!mayWrite($baseTable,$db_id)) {
			$permission_denied=true;
		}
	break;
	case "del":
		if (!mayDelete($baseTable,$db_id)) {
			$permission_denied=true;
		}
	break;
	}
	if ($permission_denied) {
		return array(FAILURE,s("permission_denied"));
	}
	
	if ($_REQUEST["updateTopFrame"]=="true") {
		updateTopnav();
	}
	$now=time();
	$sql_query=array();
	
	// open db connection if necessary
	if ($db_id>0) {
		$dbObj=getForeignDbObjFromDBid($db_id);
		if (!$dbObj) {
			return array(FAILURE,s("error_no_access"));
		}
	}
	else {
		$dbObj=$db;
	}
	
	//~ if($action!="") { // && isEmptyStr($_REQUEST["db_id"])) { // set db_id always to own
		//~ $_REQUEST["db_id"]=-1;
	//~ }
	// special operations
	// print_r($_REQUEST);
	switch ($action) {
	
	case "confirm_order":
		if ($baseTable=="chemical_order") {
			if (empty($pk)) {
				return array(FAILURE,s("")); // fixme
			}
			elseif ($permissions & _order_approve) {
				$sql_query[]="UPDATE chemical_order SET customer_order_status=\"customer_confirmed\" WHERE chemical_order_id=".fixNull($pk).";";
				
				$result=performQueries($sql_query,$dbObj);
				addChangeNotify($db_id,$dbObj,$baseTable,$pk);
				return array(SUCCESS,s("order_confirmed"));
			}
			else {
				return array(FAILURE,s("permission_denied"));
			}
		}
	break;
	case "return_rent":
		if ($baseTable=="rent") {
			if (empty($pk)) {
				return array(FAILURE,s("")); // fixme
			}
			elseif ($permissions & _order_approve) {
				list($rent)=mysql_select_array(array(
					"table" => "rent", 
					"filter" => "rent.rent_id=".fixNull($pk), 
					"dbs" => -1, 
					"limit" => 1, 
				)); // get existing values
				
				if ($rent["end_date"]==invalidSQLDate) {
					$sql_query[]="UPDATE rent SET end_date=FROM_UNIXTIME(".$now.") WHERE rent_id=".fixNull($pk).";";
					
					$result=performQueries($sql_query,$dbObj);
					addChangeNotify($db_id,$dbObj,$baseTable,$pk);
					return array(SUCCESS,s("data_set_updated"));
				}
				else {
					return array(FAILURE,s("already_returned"));
				}
			}
			else {
				return array(FAILURE,s("permission_denied"));
			}
		}
	break;
	case "set_order_status": // orderAvail/pickupOrder
		if ($baseTable=="accepted_order") {
			if (empty($pk)) {
				return array(FAILURE,s("")); // fixme
			}
			elseif (empty($_REQUEST["central_order_status"])) {
				return array(FAILURE,s("")); // fixme
			}
			elseif ($permissions & _order_accept) {
				list($accepted_order)=mysql_select_array(array(
					"table" => "accepted_order", 
					"filter" => "accepted_order.accepted_order_id=".fixNull($pk), 
					"dbs" => -1, 
					"limit" => 1, 
				)); // get existing values
				
				if ($accepted_order["central_order_status"]==$_REQUEST["central_order_status"]) {
					// do nothing
					return array(NO_ACTION,"");
				}
				elseif ($accepted_order["central_order_status"]>$_REQUEST["central_order_status"]) {
					return array(FAILURE,s("permission_denied"));
				}
				
				$dateText="";
				if ($_REQUEST["central_order_status"]>=3) {
					$dateText.="supplier_delivery_date=FROM_UNIXTIME(".$now."),";
				}
				if ($_REQUEST["central_order_status"]>=4) {
					$dateText.="customer_delivery_date=FROM_UNIXTIME(".$now."),";
				}
				
				$sql_query[]="UPDATE accepted_order SET ".
					$dateText.
					nvp("central_order_status",SQL_NUM,true).
					" WHERE accepted_order_id=".fixNull($pk).";";
				
				$result=performQueries($sql_query,$dbObj);
				addChangeNotify($db_id,$dbObj,$baseTable,$pk);
				
				if ($_REQUEST["central_order_status"]==3 && strpos($accepted_order["email"],"@")) {
					// get email address of person who ordered and send email
					$user_lang=$accepted_order["preferred_language"]; // wurm muss dem fisch schmecken
					mail(
						$accepted_order["email"],
						l($user_lang,"order_arrived1").$accepted_order["name"].l($user_lang,"order_arrived2"),
						l($user_lang,"dear1").formatPersonNameNatural($accepted_order).l($user_lang,"dear2")."\n\n".
							l($user_lang,"order_arrived1").$accepted_order["name"]." [".
							joinIfNotEmpty(array(
								ifNotEmpty(s("cas_nr").": ",$accepted_order["cas_nr"]), 
								ifNotEmpty("",$accepted_order["amount"],$accepted_order["amount_unit"]), 
								$accepted_order["supplier"], 
							),", ").
							"]".l($user_lang,"order_arrived2"),
						"From: ".$own_data["email"]
					);
				}
				
				if ($_REQUEST["central_order_status"]==3) {
					return array(SUCCESS,s("ready_for_collection"));
				}
				elseif ($_REQUEST["central_order_status"]==4) {
					return array(SUCCESS,s("order_collected"));
				}
				else {
					return array(SUCCESS,s("data_set_updated"));
				}
			}
			else {
				return array(FAILURE,s("permission_denied"));
			}
			// status auf verfügbar setzen
			// status auf abgeholt setzen
		}
	break;
	
	case "set_reaction_printed":
		// status auf gedruckt setzen
		/*
		$reaction_ids=fixNumberLists($_REQUEST["reaction_ids"]);
		if (!empty($reaction_ids)) {
			$sql_query[]="UPDATE reaction SET status=\"printed\" WHERE reaction_id IN(".$reaction_ids.");";
			$result=performQueries($sql_query,$dbObj);
		}
		*/
		// lab_journal_id,print_nr_in_lab_journal
		$nr_in_lab_journal_text="";
		if (!empty($_REQUEST["nr_in_lab_journal"])) {
			if ($result=mysqli_query($db,"SELECT MIN(nr_in_lab_journal) AS min,MAX(nr_in_lab_journal) AS max FROM reaction WHERE lab_journal_id=".fixNull($_REQUEST["lab_journal_id"]).";")) {
				$temp=mysqli_fetch_array($result,MYSQLI_ASSOC);
				$nr_in_lab_journal_text=" AND nr_in_lab_journal IN(".join(",",splitDatasetRange($temp["min"],$temp["max"],$_REQUEST["nr_in_lab_journal"],0)).")";
			}
			else {
				return array(FAILURE,"bogus");
			}
		}
		$sql_query[]="UPDATE reaction SET status=\"printed\" WHERE lab_journal_id=".fixNull($_REQUEST["lab_journal_id"]).$nr_in_lab_journal_text.";";
		$result=performQueries($sql_query,$dbObj);
	break;
	
	case "transferGCs":
		if ($baseTable=="reaction") {
			if (empty($_REQUEST["lab_journal_id"])) {
				return array(FAILURE,s("error_no_lab_journal_reaction"));
			}
			else {
				list($lab_journal)=mysql_select_array(array(
					"table" => "lab_journal", 
					"dbs" => -1, 
					"filter" => "lab_journal.lab_journal_id=".fixNull($_REQUEST["lab_journal_id"]), 
					"limit" => 1, 
				));
				if ($lab_journal["lab_journal_status"]>1) {
					return array(FAILURE,s("error_no_lab_journal_reaction"));
				}
				else {
					$oldrequest=$_REQUEST; // preserve status
					
					// load these only if really needed
					require_once "lib_io.php";
					require_once "lib_analytics.php";
					// maybe slow and error-prone
					
					$transfer_count=0;
					$successfully_checked=array();
					$failure_count=0;
					$last_nr_in_lab_journal=0;
					$refresh_data=array(); // nur db_id=-1
					$transfer_settings=intval($_REQUEST["settings"]);
					
					if (count($settings["include_in_auto_transfer"][$transfer_settings])) {
					
						$gc_result=getDeviceResult($transfer_settings);
						
						$lab_journal_codes=array($lab_journal["lab_journal_code"],fixLJcode($lab_journal["lab_journal_code"]));
						/*
						// get list of existing data
						$existing_identifiers=mysql_select_array(array(
							"table" => "analytical_data_simple", 
							"order_obj" => array(), 
							"dbs" => -1, 
							"filter" => getDoubleFindFilter($lab_journal_codes), 
						));
						for ($a=0;$a<count($existing_identifiers);$a++) {
							$existing_identifiers[$a]=$existing_identifiers[$a]["analytical_data_identifier"];
						} */
						
						//~ $ad_double=mysql_select_array(array( "table" => "analytical_data_double", "dbs" => -1, "filter" => "analytical_data.analytics_type_id=".fixNull($gc_result["analytics_type_id"]) ));
						for ($a=0;$a<count($gc_result);$a++) { // device by device
							if (empty($gc_result[$a]["analytics_device_url"])) {
								continue;
							}
							
							// get list of existing data, now per device
							$existing_identifiers=mysql_select_array(array(
								"table" => "analytical_data_simple", 
								"order_obj" => array(), 
								"dbs" => -1, 
								"filter" => "analytical_data.analytics_device_id=".fixNull($gc_result[$a]["analytics_device_id"])." AND ".getDoubleFindFilter($lab_journal_codes), 
							));
							for ($b=0;$b<count($existing_identifiers);$b++) {
								$existing_identifiers[$b]=$existing_identifiers[$b]["analytical_data_identifier"];
							}
							
							// Listing holen: beginnen Einträge mit RUD-UA oder RUDUA?
							$paramHash["path"]=fixPath($gc_result[$a]["analytics_device_url"]);
							$paramHash["username"]=$gc_result[$a]["analytics_device_username"];
							$paramHash["password"]=$gc_result[$a]["analytics_device_password"];
							
							//~ print_r($paramHash);
							$dirList=getPathListing($paramHash);
							//~ var_dump(count($dirList["data"]));
							//~ print_r($lab_journal_codes);
							if (is_array($dirList) && is_array($dirList["data"])) for ($b=0;$b<count($dirList["data"]);$b++) {
								// dirs only // NO
								$file=& $dirList["data"][$b];
								//~ if (!$file["dir"]) {
									//~ continue;
								//~ }
								// beginnt mit $lab_journal_codes?
								for ($c=0;$c<count($lab_journal_codes);$c++) {
									$this_filename=cutFilename($file["filename"]); // remove path if there is any, normally not, but Biotage crap
									if (startswith($this_filename,$lab_journal_codes[$c])) {
										// found something!
										// check if double
										//~ list($double_check)=mysql_select_array(array( "table" => "analytical_data", "dbs" => -1, "filter" => "analytical_data_identifier=".fixStrSQL($this_filename)." AND analytical_data.reaction_id IN(SELECT reaction_id FROM reaction WHERE lab_journal_id=".fixNull($_REQUEST["lab_journal_id"]).")", "limit" => 1 )); //  AND analytical_data.reaction_id IN(SELECT reaction_id FROM reaction WHERE reaction.lab_journal_id=".fixNull($_REQUEST["pk"])."
										//~ if (!$double_check) {
										if (!in_array($this_filename,$existing_identifiers)) { // ignore path for this
											if ($gc_result[$a]["analytics_device_driver"]=="agilent" && !file_exists($paramHash["path"]."/".$file["filename"]."/Report.TXT")) { // CASE-sensitive, agilent only
												continue 2; // incomplete, next list entry
											}
											// insert new and assign to reaction with no #
											$nr_in_lab_journal=intval(substr($this_filename,strlen($lab_journal_codes[$c]))); // code abschneiden und als Zahl interpretierbaren Teil nehmen
											//~ echo $nr_in_lab_journal;
											list($reaction)=mysql_select_array(array(
												"table" => "reaction", 
												"dbs" => -1, 
												"filter" => "reaction.status<5 AND lab_journal.lab_journal_id=".fixNull($_REQUEST["lab_journal_id"])." AND nr_in_lab_journal=".fixNull($nr_in_lab_journal), 
												"limit" => 1, 
											));
											//~ print_r($reaction);
											if ($reaction && $reaction["lab_journal_status"]<=1 && $reaction["status"]<5) {
												$_REQUEST["desired_action"]="add";
												$_REQUEST["analytical_data_id"]="";
												$_REQUEST["reaction_id"]=$reaction["reaction_id"];
												$_REQUEST["analytics_type_id"]=$gc_result[$a]["analytics_type_id"];
												$_REQUEST["analytics_device_id"]=$gc_result[$a]["analytics_device_id"];
												$_REQUEST["spzfile"]=$gc_result[$a]["analytics_device_url"]."/".$file["filename"];
												
												//~ print_r($_REQUEST);
												list($success,$message)=performEdit("analytical_data",$db_id,$dbObj);
												if ($success==1) {
													$transfer_count++;
													$refresh_data[]=$_REQUEST["reaction_id"];
													if ($_REQUEST["pk"]==$_REQUEST["reaction_id"]) {
														$oldrequest["refresh"]="true";
													}
													//~ $last_nr_in_lab_journal=max($last_nr_in_lab_journal,$nr_in_lab_journal);
													// add reaction_id to refresh_data
												}
												elseif ($success==1) {
													$failure_count++;
												}
												$_REQUEST=$oldrequest;
											}
										}
										break; // Eintrag schon vorhanden, weitere Suche zwecklos
									}
								}
							}
							if (!$dirList["error"]) {
								$successfully_checked[]=$gc_result[$a]["analytics_device_name"];
							}
						}
					}
					refreshActiveData(-1,$refresh_data);
					
					$retText=$transfer_count.s("transfer_complete2");
					if (count($successfully_checked)) {
						$retText.=" ".s("transfer_complete2b")." ".join(", ",$successfully_checked);
					}
					if ($failure_count) { // show error only if
						$retText.=s("transfer_complete3").$failure_count.s("transfer_complete4");
					}
					return array(SUCCESS,$retText);
				}
			}
		}
	break;
	
	case "new": // neue Reaktion
		if ($baseTable=="reaction") { // add empty entry for lab_j
			if (empty($_REQUEST["lab_journal_id"])) {
				return array(FAILURE,s("error_no_lab_journal_reaction"));
			}
			list($success,$message)=getNewReactionPermit();
			if ($success!=1) {
				return array($success,$message);
			}
			$_REQUEST=array_merge($_REQUEST,getDefaultDataset($table));
			
			// have product of other reaction as starting material
			/*
			if (!empty($_REQUEST["reaction_chemical_id"])) {
				list($this_reaction_chemical)=mysql_select_array(array(
					"dbs" => -1, 
					"table" => "reaction_chemical", 
					"filter" => "reaction_chemical.reaction_chemical_id=".fixNull($_REQUEST["reaction_chemical_id"]), 
					"limit" => 1, 
				));
				$newUID=uniqid();
				$int_name="reactants";
				$_REQUEST[$int_name][]=$newUID;
				$_REQUEST["desired_action_".$int_name."_".$newUID]="add";
				
				// kopieren
				if (is_array($this_reaction_chemical)) foreach ($this_reaction_chemical as $name => $value) {
					if (in_array($name,array("reaction_chemical_id","gif_file","gc_yield","yield","rc_amount","stoch_coeff"))) { // diese Werte NICHT kopieren
						continue;
					}
					$_REQUEST[$int_name."_".$newUID."_".$name]=$value;
				}

				$_REQUEST[$int_name."_".$newUID."_package_name"]=s("product_from1")." ".$this_reaction_chemical["lab_journal_code"].$this_reaction_chemical["nr_in_lab_journal"]." ".s("product_from2");
			}*/
			
			performEdit($table,$db_id,$dbObj);
			$_REQUEST["desired_action"]="";
			$_REQUEST["db_id"]=-1;
			$_REQUEST["pk"]=$_REQUEST["reaction_id"];
			if ($page_type=="async") {
				echo "parent";
			}
			else {
				echo "self";
				unset($_REQUEST["desired_action"]);
			}
			echo ".location.href=".fixStr("edit.php?".getSelfRef(array("~script~","cached_query"),array("db_id","pk"))."&edit=true&dbs=-1&query=<0>&crit0=reaction.lab_journal_id&op0=eq&val0=".$_REQUEST["lab_journal_id"]."&message=".$message).";\n";
		}
	break;
	
	case "close": // close lab_journal, only setting status to printed is still possible
		
		if ($baseTable=="lab_journal") { // add empty entry for lab_j
			if (empty($pk)) {
				return array(FAILURE,s("error_no_lab_journal_reaction"));
			}
			else {
				$sql_query=array(
				"UPDATE reaction SET status=\"approved\" WHERE status<4 AND lab_journal_id=".$pk,
				"UPDATE lab_journal SET lab_journal_status=\"closed\" ".getPkCondition($baseTable,$pk),
				);
				$result=performQueries($sql_query,$dbObj);
				return array(SUCCESS,s("lab_journal_closed"));
			}
		}
		
	break;
	
	case "inventory": // inventarisierung
		if ($baseTable=="chemical_storage") {
			list($borrow_result)=mysql_select_array(array(
				"table" => "chemical_storage_for_storage", 
				"dbs" => "-1", 
				"filter" => "chemical_storage_id=".fixNull($pk), 
				"limit" => 1, 
			));
			
			if (($permissions & (_chemical_edit+_chemical_inventarise))==0 && (($permissions & _chemical_edit_own)==0 || $borrow_result["owner_person_id"]!=$person_id)) {
				return array(FAILURE,s("permission_denied"));
			}
			
			if (empty($_REQUEST["amount"])) {
				$_REQUEST["amount"]=$borrow_result["amount"];
			}
			if (empty($_REQUEST["amount_unit"])) {
				$_REQUEST["amount_unit"]=$borrow_result["amount_unit"];
			}
			
			$logText=$borrow_result["molecule_name"]." ".
				$borrow_result["amount"]." (".
				$borrow_result["actual_amount"]." => ".
				$_REQUEST["actual_amount"].") ".
				$borrow_result["amount_unit"];
			
			if (!empty($_REQUEST["history_entry"])) {
				$logText.="; ".$_REQUEST["history_entry"];
			}
			
			$sql_query="UPDATE chemical_storage SET ".
				nvpUnit("chemical_storage_conc","chemical_storage_conc_unit").
				nvpUnit("tmd","tmd_unit").
				nvpUnit("amount","amount_unit").
				"actual_amount=(".fixNull($_REQUEST["actual_amount"])." * (SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQL($_REQUEST["amount_unit"])." LIMIT 1)),".
				"amount_is_volume=(SELECT unit_type=\"v\" FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch($_REQUEST["amount_unit"])."),";
			
			if (isset($_REQUEST["chemical_storage_barcode"])) {
				$sql_query.=nvp("chemical_storage_barcode",SQL_TEXT);
			}
			
			if (isset($_REQUEST["chemical_storage_bilancing"])) {
				$sql_query.=nvp("chemical_storage_bilancing",SQL_NUM);
			}
				
			if (isset($_REQUEST["storage_id"]) || isset($_REQUEST["compartment"])) { // ggf lagerort anpassen
				$sql_query.=nvp("storage_id",SQL_NUM).
					nvp("compartment",SQL_TEXT);
			}
			
			$sql_query.=getHistorySQL($now,$logText).
				SQLgetRecord("inventory_check","",$now).
				getPkCondition($table,$pk);
			
			$result=performQueries($sql_query,$dbObj);
			addChangeNotify($db_id,$dbObj,$baseTable,$pk);
			
			return array(SUCCESS,s("amount_updated1")." ".$borrow_result["molecule_name"]." ".s("amount_updated2"));
		}
	break;
	
	case "unlink": // remove link from analytical_data, literature unlink is only deleting the line in the subitemlist
		switch ($baseTable) {
		case "analytical_data":
			if (!empty($pk)) {
				$sql_query.="UPDATE analytical_data SET reaction_id=NULL,reaction_chemical_id=NULL".getPkCondition($table,$pk);
				$result=performQueries($sql_query,$dbObj);
				addChangeNotify($db_id,$dbObj,$baseTable,$pk);
				return array(SUCCESS,s("analytical_data_unlinked"));
			}
		break;
		}
	break;
	
	case "insert_copies": // copy reaction
		//~ print_r($_REQUEST);die("X");
		if ($baseTable=="reaction") { // jetzt haben wir zwar 2 speicher-funktionen für reaktionen, aber eine Zusammenfassung wäre zu kompliziert
			if ($pk=="") {
				return array(FAILURE,s("error_no_reaction"));
			}
			else {
				$oldRequest=$_REQUEST;
				
				// get reactions to overwrite
				$overwrite_reactions=array();
				if ($_REQUEST["overwrite_entries"]>0) {
					$overwrite_reactions=mysql_select_array(array(
						"table" => "reaction_copy", 
						"dbs" => -1, // hardcoded for the moment, we need from_db_id and db_id (which is then the target)
						"filter" => "reaction.lab_journal_id=".fixNull($_REQUEST["lab_journal_id"]), 
						"quick" => true, //1, 
						"limit" => min($_REQUEST["overwrite_entries"],count($_REQUEST["copyTable"])), 
						"order_obj" => array(
							array("field" => "nr_in_lab_journal", "order" => "DESC"), 
						), 
					));
					// remove closed ones
					for ($a=count($overwrite_reactions)-1;$a>=0;$a--) { // from end
						if ($overwrite_reactions[$a]["status"]>reaction_open) {
							array_splice($overwrite_reactions,$a,1); // remove
						}
					}
					// start overwrite-operation from the end to get the correct order
					//~ print_r($overwrite_reactions);die();
				}
				
				// only from own db
				list($prototype)=mysql_select_array(array(
					"table" => "reaction_copy", 
					"dbs" => $_REQUEST["db_id"], 
					"filter" => getLongPrimary($table)."=".$pk, 
					"flags" => QUERY_EDIT, 
					"limit" => 1, 
				));
				unset($_REQUEST["archive_entity"]);
				$prototype["reaction_prototype"]=$pk;
				$prototype["reaction_prototype_db_id"]=$_REQUEST["db_id"];
				
				if ($prototype["lab_journal_status"]>lab_journal_open) {
					return array(FAILURE,s("error_no_lab_journal_closed"));
				}
				
				if ($_REQUEST["db_id"]!=-1) { // from foreign db
					unset($prototype["project_id"]);
				}
				else {
					// check if person of target-LJ belongs to project
					list($person_on_project)=mysql_select_array(array(
						"table" => "person_project", 
						"dbs" => $_REQUEST["db_id"], 
						"filter" => "project_person.project_id=".fixNull($prototype["project_id"])." AND project_person.person_id=".fixNull($person_id), 
						"limit" => 1, 
					));
					
					// set NULL otherwise
					if (empty($person_on_project)) {
						unset($prototype["project_id"]);
					}
				}
				
				$molecules=array();
				
				// molecules parsen
				//~ print_r($prototype);echo "BB";
				foreach ($reaction_chemical_lists as $c => $int_name) {
					for ($d=0;$d<count($prototype[$int_name]);$d++) {
						// read info from db, place or borrow status may have changed meanwhile
						$molecules[ $prototype[$int_name][$d]["reaction_chemical_id"] ]=load_reaction_chemical($prototype[$int_name][$d],array(),$int_name);
					}
				}
				//~ print_r($prototype);die("X");
				
				// copyTable durchgehen
				$list_int_name="copyTable";
				unset($_REQUEST["analytical_data"]); // make sure no analytics is being copied
				
				if (count($_REQUEST[$list_int_name])) {
					list($success,$message,$left)=getNewReactionPermit();
					if ($success!=1) {
						return array($success,$message);
					}
					foreach ($_REQUEST[$list_int_name] as $idx => $UID) {
						// darf Reaktion angelegt werden?
						if ($left>0 && $idx>=$left) {
							return array(FAILURE,s("error_too_many_open"));
						}
						
						$newReaction=array();
						$newRXN=array();
						$global_factor=fixNumber(getValueUID($list_int_name,$UID,"global_factor"));
						
						// additional fields
						$newReaction["additionalFields"]=$_REQUEST["additionalFields"];
						if (is_array($_REQUEST["additionalFields"])) foreach ($_REQUEST["additionalFields"] as $additionalField) {
							$newReaction[$additionalField]=getValueUID($list_int_name,$UID,$additionalField);
						}
						
						// have unit settings copied as well
						$newReaction["additionalFields"]=arr_merge(
							$newReaction["additionalFields"],
							array(
								"reactants_rc_amount_unit", 
								"reactants_mass_unit", 
								"reactants_volume_unit", 
								"reactants_rc_conc_unit", 
								"products_rc_amount_unit", 
								"products_mass_unit", 
							)
						);
						
						//~ print_r($newReaction);die("C");
						
						// realization_text,reaction_carried_out_by,lab_journal_id,project_id
						$newReaction=array_merge(
							$newReaction,
							array_key_filter(
								$prototype,
								array(
									"project_id", 
									"reaction_prototype", 
									"reaction_prototype_db_id", 
									//~ "reaction_type_id", // do no longer simply copy
									"reactants_rc_amount_unit", 
									"reactants_mass_unit", 
									"reactants_volume_unit", 
									"reactants_rc_conc_unit", 
									"products_rc_amount_unit", 
									"products_mass_unit", 
									"ref_amount", 
									"ref_amount_unit", 
								)
							)
						); // "lab_journal_id",
						
						$newReaction["lab_journal_id"]=ifempty($_REQUEST["lab_journal_id"],$prototype["lab_journal_id"]);
						$newReaction["reaction_type_id"]=$_REQUEST["reaction_type_id"];
						$newReaction["reaction_carried_out_by"]=$_REQUEST["reaction_carried_out_by"];
						$newReaction["reaction_started_when"]=getGermanDate();
						$newReaction["status"]=1;
						// do not scale stoch_coeff, scale ref_amount instead
						multiplyIfNotEmpty($newReaction["ref_amount"],$global_factor);
						
						if (getValueUID($list_int_name,$UID,"copy_realization_text")) {
							$newReaction["realization_text"]=$prototype["realization_text"];
						}
						
						if ($global_factor<=0) {
							continue;
						}
						
						// reactants_0... usw durchgehen und fake UIDs setzen
						foreach ($reaction_chemical_lists as $c => $int_name) {
							$newReaction[$int_name]=array();
							$deleted=0;
							
							for ($d=0;$d<count($prototype[$int_name]);$d++) {
								if ($idx==0 && $_REQUEST["db_id"]!=-1) { // only first pass
									unset($prototype[$int_name][$d]["molecule_id"]);
									unset($prototype[$int_name][$d]["chemical_storage_id"]);
									unset($prototype[$int_name][$d]["other_db_id"]);
								}
								
								$reaction_chemical_id=$prototype[$int_name][$d]["reaction_chemical_id"];
								$struc_changed=(getValueUID($list_int_name,$UID,"reaction_chemical_id",$reaction_chemical_id)=="");
								
								// wenn Faktor >0, sonst nächsten
								$factor=fixNumber(getValueUID($list_int_name,$UID,"factor",$reaction_chemical_id)); // allow commas
								if ($factor<=0) {
									$deleted++;
									continue;
								}
								
								if (!$struc_changed) {
									// Objekt
									$reaction_chemical=$prototype[$int_name][$d];
									
									// Faktoren einrechnen: rc_amount, m_brutto, volume
									if ($c<2) {
										multiplyIfNotEmpty($reaction_chemical["rc_amount"],$factor*$global_factor);
										multiplyIfNotEmpty($reaction_chemical["m_brutto"],$factor*$global_factor);
										multiplyIfNotEmpty($reaction_chemical["volume"],$factor*$global_factor);
										multiplyIfNotEmpty($reaction_chemical["stoch_coeff"],$factor);
									}
									else { // product
										multiplyIfNotEmpty($reaction_chemical["rc_amount"],$global_factor);
										//~ multiplyIfNotEmpty($reaction_chemical["stoch_coeff"],$global_factor);
										$reaction_chemical["m_brutto"]="";
										$reaction_chemical["volume"]="";
									}
								}
								else {
									$reaction_chemical=build_reaction_chemical($list_int_name,$UID,$reaction_chemical_id,$int_name);
									$molObj=load_reaction_chemical($reaction_chemical,$prototype[$int_name][$d],$int_name);
									
									if ($molObj===false) {
										$deleted++;
										continue;
									}
									
									$copyFields=array("rc_amount","rc_amount_unit","stoch_coeff","measured",);
									if ($c<2) {
										$copyFields[]="m_brutto";
										$copyFields[]="mass_unit";
										$copyFields[]="volume";
										$copyFields[]="volume_unit";
									}
									
									// calc n,m,V
									$reaction_chemical=array_merge(
										$reaction_chemical,
										array_key_filter(
											$prototype[$int_name][$d],
											$copyFields
										)
									);
									
									complete_reaction_chemical($reaction_chemical,$newReaction["ref_amount"],$newReaction["ref_amount_unit"],$factor*$global_factor);
								}
								$reaction_chemical["nr_in_reaction"]=$d+1-$deleted;
								
								// kopieren
								transfer_reaction_chemical($reaction_chemical,$newReaction,$int_name);
								
								/*
								// neues RXN vorbereiten
								if (!$addToRXN) { // reagents
									continue;
								}
								else {
									$newRXN[$int_name]++;
									if ($struc_changed) {
										// custom
										$newRXN["molecules"][]=$molObj;
									}
									else {
										$newRXN["molecules"][]=$molecules[ $prototype[$int_name][$d]["reaction_chemical_id"] ];
									}
								}
								*/
							}
							
							// additional components
							// conditions met?
							$e=0;
							
							$reaction_chemical=array();
							$prefix=$int_name."_".$e;
							$fields=array("molecule_id","other_db_id","molfile_blob","standard_name","package_name","cas_nr","amount","amount_unit", );
							if ($int_name!="products") {
								array_push($fields,
									"from_reaction_id", 
									"from_reaction_chemical_id", 
									"chemical_storage_id"
								);
							}
							
							for ($a=0;$a<count($fields);$a++) {
								$reaction_chemical[ $fields[$a] ]=getValueUID($list_int_name,$UID,$fields[$a],$prefix);
							}
							//~ print_r($reaction_chemical);die();
							
							$newMolObj=load_reaction_chemical($reaction_chemical,array(),$int_name);
							
							// something: "molecule_id","from_reaction_id","standard_name","package_name","cas_nr","molfile_blob"
							// and amount>0,amount_unit
							if ($reaction_chemical["amount"]>0 && (
								!empty($reaction_chemical["molecule_id"]) || 
								!empty($reaction_chemical["from_reaction_id"]) || 
								!empty($reaction_chemical["standard_name"]) || 
								!empty($reaction_chemical["package_name"]) || 
								!empty($reaction_chemical["cas_nr"]) || 
								count($newMolObj["atoms"])
							)) {
								/*
								// add to equation
								$newRXN[$int_name]++;
								$newRXN["molecules"][]=$newMolObj;
								*/
								$ref_int_name=$int_name;
								if ($c==1) {
									$ref_int_name="reactants";
								}
								
								$reaction_chemical["measured"]=0;
								$reaction_chemical["nr_in_reaction"]=$d+$e+1-$deleted;
								$reaction_chemical["mass_unit"]=$prototype[ $ref_int_name."_mass_unit" ];
								$reaction_chemical["volume_unit"]=$prototype[ $ref_int_name."_volume_unit" ];
								$reaction_chemical["rc_amount_unit"]=$prototype[ $ref_int_name."_rc_amount_unit" ];
								
								// calc stuff, incl stoch coeff
								complete_reaction_chemical($reaction_chemical,$newReaction["ref_amount"],$newReaction["ref_amount_unit"]);
								
								// kopieren
								transfer_reaction_chemical($reaction_chemical,$newReaction,$int_name);
							}
							
						}
						
						// neues RXN erzeugen
						/*
						$newReaction["rxnfile_blob"]=writeRxnfile($newRXN);
						*/
						//~ print_r($newReaction);die();
						
						// start overwrite-operation from the end to get the correct order
						if (count($overwrite_reactions)) {
							$overwrite_this=array_pop($overwrite_reactions);
							// delete (backup old)
							$_REQUEST["pk"]=$overwrite_this["pk"];
							performDel($table,$db_id,$dbObj);
							// overwrite
							$_REQUEST["reaction_id"]=$overwrite_this["pk"];
							
							// Jetzt: leerer Datensatz mit reaction_id zum Überschreiben
						}
						else { // new reaction
							unset($_REQUEST["reaction_id"]);
						}
						
						// schreibroutine aufrufen
						$_REQUEST=array_merge($_REQUEST,$newReaction);
						
						performEdit($table,$db_id,$dbObj,array("ignoreLock" => true, ));
						
						if ($idx==0) {
							$goto_reaction_id=$_REQUEST["reaction_id"];
						}
						
						$_REQUEST=$oldRequest;
					}
					// zum 1. neuen Datensatz gehen
					echo "
if (opener) {
	opener.gotoNewReaction(-1,".fixNull($goto_reaction_id).",".fixNull($newReaction["lab_journal_id"]).");
}";
				}
				// fenster schließen
				echo "
window.close();
";
			}
		}
	break;
	
	case "borrow": // gebinde ausleihen
		if ($baseTable=="chemical_storage") {
			if ($pk=="") {
				return array(FAILURE,s("error_no_cheminstor"));
			}
			elseif ($_REQUEST["borrowed_by_person_id"]!="" && $person_id!=$_REQUEST["borrowed_by_person_id"]) {
				return array(FAILURE,s("error_no_borrow_for_someone_else")); // falsch
			}
			else {
				// Abfragen, wer Gebinde ausgeliehen hat
				list($borrow_result)=mysql_select_array(array(
					"table" => "chemical_storage_for_storage", 
					"dbs" => "-1", 
					"filter" => "chemical_storage_id=".fixNull($pk), 
					"filterDisabled" => true, 
					"limit" => 1, 
				));
				
				if (empty($borrow_result["chemical_storage_id"])) {
					return array(INFORMATION,s("error_borrow_not_found"));
				}
				elseif (
					($permissions & (_chemical_edit+_chemical_borrow))==0 
					&& (($permissions & _chemical_edit_own)==0 || $borrow_result["owner_person_id"]!=$person_id)
				) {
					return array(FAILURE,s("permission_denied"));
				}
			
				if (empty($_REQUEST["borrowed_by_person_id"]) && empty($borrow_result["borrowed_by_person_id"])) { // nicht ausgeliehenes zurückgeben
					return array(FAILURE,s("trm_not_borrowed1").$borrow_result["molecule_name"].s("trm_not_borrowed2"));
				}
				elseif (!empty($_REQUEST["borrowed_by_person_id"]) && !empty($borrow_result["borrowed_by_person_id"]) && $borrow_result["borrowed_by_person_id"]!=$person_id) { // nicht nochmal ausleihen
					return array(FAILURE,s("error_borrowed_by_someone_else"));
				}
				else {
					if (empty($_REQUEST["borrowed_by_person_id"])) {
						$successText=s("package_returned1").
							$borrow_result["molecule_name"].
							s("package_returned2");
						
						$logText=s("package_returned1").
							$borrow_result["molecule_name"].
							ifnotempty(" ",$borrow_result["amount"].ifnotempty(" (",$borrow_result["actual_amount"],")")," ".$borrow_result["amount_unit"]).
							s("package_returned2");
						
						$borrowed_when_text="borrowed_when=NULL,";
					}
					else {
						$successText=s("package_borrowed1").
							$borrow_result["molecule_name"].
							s("package_borrowed2");
						
						$logText=s("package_borrowed1").
							$borrow_result["molecule_name"].
							ifnotempty(" ",$borrow_result["amount"].ifnotempty(" (",$borrow_result["actual_amount"],")")," ".$borrow_result["amount_unit"]).
							s("package_borrowed2");
						
						$borrowed_when_text="borrowed_when=FROM_UNIXTIME(".fixNull($now)."),";
					}
					
					if (!empty($_REQUEST["history_entry"])) {
						$logText.="; ".$_REQUEST["history_entry"];
					}
					
					$result=mysqli_query($dbObj,"UPDATE chemical_storage SET ".
						getHistorySQL($now,$logText).
						$borrowed_when_text.
						nvp("borrowed_by_db_id",2).
						nvp("borrowed_by_person_id",2,true).
						" WHERE chemical_storage_id=".$pk.";");
					
					addChangeNotify($db_id,$dbObj,$baseTable,$pk);
				
					if (!$result) {
						return array(FAILURE,s("borrow_error"));
					}
					else {
						return array(SUCCESS,$successText);
					}
				}
			}
		}
	break;
	
	case "message_status": // Nachrichtenstatus setzen
		switch ($table) {
		case "message_in": // recipient
			$sql_query[]="UPDATE message_person SET ".
				nvp("completion_status",2,true).
				" WHERE message_id=".fixNull($pk). " AND person_id=".fixNull($person_id).";";
		break;
		case "message": // sender
		case "message_out":
			if ($_REQUEST["completion_status"]==5) { // refuse
				$modifier=" AND completion_status>5";
			}
			$sql_query[]="UPDATE message_person SET ".
				nvp("completion_status",2,true).
				" WHERE message_id=".fixNull($pk).$modifier.";";
		break;
		}
		$result=performQueries($sql_query,$dbObj);
		addChangeNotify($baseTable,$pk,$db_id,$dbObj);
		return array(SUCCESS,"");
	break;
	
	case "unlock":
		$retval=unlock($db_id,$dbObj,$baseTable,$pk,$_REQUEST["force"]=="true");
	break;
	
	case "lock":
		$retval=lock($db_id,$dbObj,$baseTable,$pk,$_REQUEST["force"]=="true");
	break;
	
	case "renew":
		$retval=renew_lock($db_id,$dbObj,$baseTable,$pk);
	break;
	
	case "merge":
		$retval=performMerge($baseTable,$db_id,$dbObj);
	break;
	
	case "recover":
		//~ print_r($_REQUEST);die();
		if (hasTableArchive($baseTable) && !empty($_REQUEST["archive_entity"])) {
			// delete and save old data as new version
			$retval=performDel($baseTable,$db_id,$dbObj,true); // do we have to disable creation of blank dataset? YES
			
			// NO dataset there, otherwise we would need REPLACE instead of INSERT in lib_db_manip_version
			
			if ($retval[0]==SUCCESS) {
				// recover old dataset
				$retval=performRecover($baseTable,$db_id,$dbObj);
				$_REQUEST["archive_entity"]=""; // leads to reload
			}
		}
	break;
	
	case "autosave":
		performVersion($baseTable,$db_id,$dbObj,s("autosave"));
	break;
	
	case "update":
		$_REQUEST["refresh"]="true";
		
		// create new Version before saving changes
		if (hasTableArchive($baseTable) && $_REQUEST["version_before"]=="true") {
			performVersion($baseTable,$db_id,$dbObj,$_REQUEST["version_comment_before"]); // only -1
		}
	// no break;
	case "add":
		if ($baseTable=="chemical_order" && function_exists("performOrder")) {
			$retval=performOrder();
			if ($retval[0]==ABORT_PROCESS) {
				return $retval;
			}
		}
		
		$retval=performEdit($baseTable,$db_id,$dbObj);
		
		// create new Version
		if (hasTableArchive($baseTable) && $_REQUEST["version_after"]=="true") {
			performVersion($baseTable,$db_id,$dbObj,$_REQUEST["version_comment_after"]); // only -1
		}
		
		if (in_array($table,$selectTables)) { // successfully added
			if ($retval[0]==SUCCESS) {
				$pkName=getShortPrimary($table);
				// auto select
				echo "
	var table=".fixStr($table).",a_db_id=-1,a_pk=".fixNull($_REQUEST[$pkName]).";
	transferThisPkToUID(".fixStr($_REQUEST["list_int_name"]).",".fixStr($_REQUEST["UID"]).",".fixStr($_REQUEST["field"]).");";
				$retval[0]=SELECT_SUCCESS; // avoid redir
			}
		}
	break;
	
	case "del":
		$retval=performDel($baseTable,$db_id,$dbObj);
	break;
	
	case "undel":
		// restore state of a deleted chemical
		// reset disposed_by, chemical_storage_disabled, disposed_when
		$sql_query[]="UPDATE chemical_storage SET chemical_storage_disabled=NULL,disposed_by=NULL,disposed_when=NULL WHERE chemical_storage.chemical_storage_id=".fixNull($pk). ";";
		$result=performQueries($sql_query,$dbObj);
		addChangeNotify($baseTable,$pk,$db_id,$dbObj);
		return array(SUCCESS,"");
	break;
	}
	
	return $retval;
}

function updateTopnav() {
// lädt topnav neu, damit die Aufzählung der Fremddatenbanken aktualisiert wird
	echo script."
top.topnav.location.reload();
"._script;
}

function addChangeNotify($db_id,$dbObj,$table,$pk) {
	$mayWriteChangeNot=mayWrite("change_notify");
	if ($mayWriteChangeNot[$db_id]) {
		mysqli_query($dbObj,"INSERT INTO change_notify (for_table,pk,made_when) VALUES (".fixStrSQL($table).",".fixNull($pk).",FROM_UNIXTIME(".time()."));");
	}
}

function cleanupChangeNotify($db_id,$dbObj) {
	$mayWriteChangeNot=mayWrite("change_notify");
	if ($mayWriteChangeNot[$db_id]) {
		mysqli_query($dbObj,"DELETE FROM change_notify WHERE made_when<SUBTIME(FROM_UNIXTIME(".time()."),SEC_TO_TIME(".all_cache_time."));"); // keep changes for longer
	}
}

function performMerge($table,$db_id,$dbObj) {
	global $db_name,$db_user,$person_id,$lang;
	$pk=& $_REQUEST["pk"]; // link, changes global value automatically
	$new_pk=$_REQUEST["new_pk"];
	$locked_by=islockedby($db_id,$dbObj,$table,$pk);
	if ($locked_by["protected"]) {
		return array(FAILURE,s("inform_about_locked1").$locked_by["locked_by"].s("inform_about_locked2"));
	}
	
	$sql_query=array();
	if ($pk=="") {
		return array(FAILURE,s("error_merge_no_pk"));
	}
	elseif ($new_pk=="") {
		return array(FAILURE,s("error_merge_no_new_pk"));
	}
	elseif ($pk==$new_pk) {
		return array(FAILURE,s("error_merge_pks_identical"));
	}
	else {
		switch ($table) { // hier nur "umstellen" auf neuen pk, löschen später
		case "literature":
			$sql_query=array(
				"UPDATE chemical_storage_literature SET literature_id=".$new_pk." WHERE literature_id=".$pk.";",
				"UPDATE project_literature SET literature_id=".$new_pk." WHERE literature_id=".$pk.";",
				"UPDATE reaction_literature SET literature_id=".$new_pk." WHERE literature_id=".$pk.";",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "molecule":
			$sql_query=array(
				"UPDATE chemical_storage SET molecule_id=".$new_pk." WHERE molecule_id=".$pk.";",
				"UPDATE supplier_offer SET molecule_id=".$new_pk." WHERE molecule_id=".$pk.";",
				"DELETE FROM molecule_names WHERE molecule_id=".$pk.";",
				"UPDATE molecule_property SET molecule_id=".$new_pk." WHERE molecule_id=".$pk.";",
				"UPDATE molecule_instructions SET molecule_id=".$new_pk." WHERE molecule_id=".$pk.";",
				"UPDATE reaction_chemical SET molecule_id=".$new_pk." WHERE molecule_id=".$pk.";",
				"UPDATE retention_time SET molecule_id=".$new_pk." WHERE molecule_id=".$pk.";",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "project":
			$sql_query=array(
				"UPDATE reaction SET project_id=".$new_pk." WHERE project_id=".$pk.";",
				"UPDATE reaction_property SET project_id=".$new_pk." WHERE project_id=".$pk.";",
				"UPDATE reaction_chemical SET project_id=".$new_pk." WHERE project_id=".$pk.";",
				"UPDATE reaction_chemical_property SET project_id=".$new_pk." WHERE project_id=".$pk.";",
				"UPDATE analytical_data SET project_id=".$new_pk." WHERE project_id=".$pk.";",
				"UPDATE analytical_data_image SET project_id=".$new_pk." WHERE project_id=".$pk.";",
				"UPDATE gc_peak SET project_id=".$new_pk." WHERE project_id=".$pk.";",
				"UPDATE project_literature SET project_id=".$new_pk." WHERE project_id=".$pk.";",
				"UPDATE project_person SET project_id=".$new_pk." WHERE project_id=".$pk.";",
				
				// remove possible double entries
				"DELETE project_literature FROM project_literature LEFT OUTER JOIN project_literature AS project_literature2 ON project_literature.project_id=project_literature2.project_id AND project_literature.literature_id=project_literature2.literature_id WHERE project_literature.project_literature_id>project_literature2.project_literature_id;",
				"DELETE project_person FROM project_person LEFT OUTER JOIN project_person AS project_person2 ON project_person.project_id=project_person2.project_id AND project_person.person_id=project_person2.person_id WHERE project_person.project_person_id>project_person2.project_person_id;",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "institution":
			$sql_query=array(
				"UPDATE storage SET institution_id=".$new_pk." WHERE institution_id=".$pk.";",
				"UPDATE person SET institution_id=".$new_pk." WHERE institution_id=".$pk.";",
				"UPDATE order_comp SET vendor_id=".$new_pk." WHERE vendor_id=".$pk.";",
				//~ "UPDATE order_comp SET ordered_by_id=".$new_pk." WHERE ordered_by_id=".$pk.";",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "sci_journal":
			$sql_query=array(
				"UPDATE literature SET sci_journal_id=".$new_pk." WHERE sci_journal_id=".$pk.";",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		case "storage":
			$sql_query=array(
				"UPDATE chemical_storage SET storage_id=".$new_pk." WHERE storage_id=".$pk.";",
				//~ "UPDATE reaction_chemical SET storage_id=".$new_pk." WHERE storage_id=".$pk.";",
			);
			$result=performQueries($sql_query,$dbObj);
		break;
		default:
			return array(FAILURE,s("permission_denied"));
		}
	}
	addChangeNotify($db_id,$dbObj,$table,$new_pk);
	return performDel($table,$db_id,$dbObj);
}

?>
