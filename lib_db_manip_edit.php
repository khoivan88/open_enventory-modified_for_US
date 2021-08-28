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
/*------------------------------------------------------------------------------
 * History:
 * 2009-10-19 MST00 Add additional fields for MPI orders
 *----------------------------------------------------------------------------*/
require_once "lib_convert.php";
require_once "lib_array.php";
require_once "lib_molfile.php";

function performEdit($table,$db_id,$dbObj,$paramHash=array()) {
	global $db,$query,$db_name,$db_user,$person_id,$own_data,$permissions,$lang,$analytics_img_params,$analytics,$importActive,$method_aware_types,$g_settings,$settings,$reaction_chemical_lists;
	
	$pkName=getShortPrimary($table);
	$pk=& $_REQUEST[$paramHash["prefix"].$pkName]; // global wird pk=xy verwendet, zum Schreiben molecule_id=xy etc.
	$now=time();
	$locked_by=islockedby($db_id,$dbObj,$table,$pk);
	
	if (!$paramHash["ignoreLock"] && !empty($pk) && $locked_by["locked_sess_id"]!=getSessidHash()) { // locking only for own DB
		return array(FAILURE,s("inform_about_locked1").$locked_by["locked_by"].s("inform_about_locked2"));
	}
	
	$createArr=array();
	$sql_query=array();
	
	$paramHashLast=$paramHash;
	$paramHashLast["isLast"]=true;
	// check conditions
	// edit, if no pk add
	// if desired_action is add, the new datasets are selected
	switch($table) {
	
	case "analytical_data":
		// do not allow addition or manipulation of analytical_data belonging to closed lab_journals
		 if (!empty($_REQUEST["analytical_data_id"])) { // get all information on method, maybe there is none (is ok), get also info on the reaction where the spectrum currently belongs to
			list($analytical_data)=mysql_select_array(array(
				"table" => "analytical_data_check", 
				"dbs" => -1, 
				"filter" => "analytical_data.analytical_data_id=".fixNull($_REQUEST["analytical_data_id"]), 
				"limit" => 1, 
			));
			
			if ($analytical_data["lab_journal_status"]>lab_journal_open || $analytical_data["status"]>reaction_open) { // no removal of modification of closed
				return array(FAILURE,s("error_no_lab_journal_closed"));
			}
			
			// ist die Person Student und will fremdes LJ bearbeiten?
			if (($permissions & _lj_edit)==0 && !empty($analytical_data["person_id"]) && $analytical_data["person_id"]!=$person_id) {
				return array(FAILURE,s("permission_denied"));
			}
			
		}
		else {
			$analytical_data=array();
		}
		
		// get info on (new) reaction where spectrum belongs to
		// always make this query, to get project_id
		if ($analytical_data["reaction_id"]!=$_REQUEST["reaction_id"]) { // adding to reaction or change of reaction_id
			list($reaction)=mysql_select_array(array(
				"table" => "reaction", 
				"dbs" => -1, 
				"filter" => "reaction.reaction_id=".fixNull($_REQUEST["reaction_id"]), 
				"limit" => 1, 
			));
			
			if ($reaction["lab_journal_status"]>lab_journal_open || $reaction["status"]>reaction_open) { // no attachment to closed
				return array(FAILURE,s("error_no_lab_journal_closed"));
			}
			
			// overwrite old data with new
			$analytical_data=array_merge($analytical_data,array_key_filter($reaction,array("reaction_id","lab_journal_code","nr_in_lab_journal","status")));
		}
		
		// load these only if really needed
		require_once "lib_io.php";
		require_once "lib_analytics.php";
		// maybe slow and error-prone
		
		if (!empty($_REQUEST["analytics_device_id"])) { // get all information on device AND type
			$device=getAnalyticsDevice($_REQUEST["analytics_device_id"]);
		}
		elseif (!empty($_REQUEST["analytics_type_id"])) {
			list($device)=mysql_select_array(array(
				"table" => "analytics_type", 
				"dbs" => -1, 
				"filter" => "analytics_type_id=".fixNull($_REQUEST["analytics_type_id"]), 
				"limit" => 1, 
			));
		}
		//var_dump($device);die("X");
		
		 if (!empty($_REQUEST["analytics_method_id"])) { // get all information on method, maybe there is none (is ok)
			list($method)=mysql_select_array(array(
				"table" => "analytics_method", 
				"dbs" => -1, 
				"filter" => "analytics_method_id=".fixNull($_REQUEST["analytics_method_id"]), 
				"limit" => 1, 
			));
		}
		
		if (count($_FILES["spzfile_file"]) && $_FILES["spzfile_file"]["error"]==0) { // upload
			/*
			[load_molfile] => Array
			(
			    [name] => Toluene.mol
			    [type] => chemical/x-mdl-molfile
			    [tmp_name] => /var/tmp/phpNhjfSD
			    [error] => 0
			    [size] => 719
			)
			*/
			$raw_data["filename"]=$_FILES["spzfile_file"]["tmp_name"];
			$filename=& $raw_data["filename"];
			$filesize=& $_FILES["spzfile_file"]["size"];
			// datei öffnen
			$handle=fopen($filename, "rb");
			// größe prüfen
			if ($filesize>0 && filesize($filename)==$filesize) {
				// datei einlesen
				$raw_data["zipdata"]=fread($handle,$filesize);
				
				// check if it is a zip, zip otherwise
				$extension=cutFilename($_FILES["spzfile_file"]["name"],".");
				if (in_array($extension,array("odt", "ods", "odp", "odg", "odc", "odf", "odi", "odm",
					"ott", "ots", "otp", "otg",
					"docx", "xlsx", "pptx", // OOXML documents are also ZIP files
					"docm", "xlsm", "pptm")) 
					|| isEmptyStr($format=whichZip($raw_data["zipdata"]))) { // Einzeldatei, packen
					$zip=File_Archive::toArchive(null,File_Archive::toVariable($zipdata),compressFormat);
					$zip->newFile($_FILES["spzfile_file"]["name"]);
					$zip->writeData($raw_data["zipdata"]);
					$zip->close();
					$raw_data["zipdata"]=$zipdata;
					unset($zipdata);
				}
				elseif ($format!=compressFormat) { // "umpacken"
					$zip_obj=getZipObj($raw_data["zipdata"]);
					File_Archive::extract(
						$zip_obj,
						File_Archive::toArchive(
							"",
							File_Archive::toVariable($zipdata),
							compressFormat
						)
					);
					$raw_data["zipdata"]=$zipdata;
					unset($zipdata);
					unset($zip_obj);
 				}
			}
			// datei schließen
			fclose($handle);
			// datei löschen
			@unlink($filename);
			
			$analytical_data_identifier=$_FILES["spzfile_file"]["name"];
		}
		elseif (!empty($_REQUEST["spzfile"])) { // folder_browser
			// check if path is ok
			$device["analytics_device_url"]=fixPath($device["analytics_device_url"]);
			$path=fixPath($_REQUEST["spzfile"]); // fix Backslashes and multiple trailing slashes
			
			// add sigle to path if rule is set
			if (limit_access_to_sigle || $g_settings["limit_access_to_sigle"]) {
				if (empty($own_data["sigle"])) {
					return array(FAILURE,s("permission_denied"));
				}
				$device["analytics_device_url"].="/".$own_data["sigle"];
			}
			
			// safety
			makeAnalyticsPathSafe($path);
			
			$analytical_data_identifier=$path;
			
			if ($importActive || isSubPath($path,$device["analytics_device_url"])) {
				// download file/dir as zip
				$raw_data=getPathListing(array(
					"path" => $path, 
					"flags" => 2, 
					"username" => $device["analytics_device_username"], 
					"password" => $device["analytics_device_password"], 
				));
			}
		}
		elseif (!empty($pk)) { // existing dataset, get edited data to regenerate img (maybe code or driver changed)
			$edited_data_results=mysql_select_array(array(
				"table" => "analytical_data_spz", 
				"dbs" => -1, 
				"filter" => "analytical_data.analytical_data_id=".fixNull($pk), 
				"limit" => 1, 
			));
			$raw_data["zipdata"]=$edited_data_results[0]["analytical_data_blob"];
		}
		
		$analytical_data_identifier=cutFilename($analytical_data_identifier); // take only part between last slash and last dot
		if (!isEmptyStr($analytical_data_identifier)) {
			$analytical_data_identifier_text="analytical_data_identifier=".fixStrSQL($analytical_data_identifier).",";
		}
		
		// create dataset
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$createArr["analytical_data_raw_blob"]=fixBlob($raw_data["zipdata"]);
			$createArr["analytical_data_uid"]="UUID()";
			// Datensatz anlegen
			$pk=getInsertPk("analytical_data",$createArr,$db); // cmdINSERT
			$_REQUEST["analytical_data_id"]=$pk;
		}
		
		if (!empty($reaction["reaction_id"]) && $_REQUEST["desired_action"]=="add") { // neu hinzugefügt
			$result=backupAnalyticalDataBackend($raw_data["zipdata"],$analytical_data["lab_journal_code"],$analytical_data["nr_in_lab_journal"],$device["analytics_type_name"],$method["analytics_method_name"],$analytical_data_identifier);
			if (!$result) {
				return array(FAILURE,s("error_spz_backup"));
			}
		}
		
		// generate image
		$graphics_text="";
		if (!empty($raw_data["zipdata"])) {
			$spectrum_data=getProcData($raw_data["zipdata"],$analytics_img_params,$device["analytics_type_code"],$device["analytics_device_driver"]);
			//~ $image_mime=$analytics_img_params["mime"]; // "image/gif";
			$device["analytics_type_code"]=$spectrum_data["analytics_type_code"];
			$device["analytics_device_driver"]=$spectrum_data["analytics_device_driver"];
			$device["analytics_device_name"]=$spectrum_data["analytics_device_name"];
			$device["analytics_type_name"]=$spectrum_data["analytics_type_name"];
			//print_r($spectrum_data);die("X".$spectrum_data["interpretation"]."X");
			
			if (count($spectrum_data)) {
				//~ $spectrum_data=$analytics[ $device["analytics_type_code"] ][ $device["analytics_device_driver"] ]["getProcData"]($raw_data["zipdata"],$analytics_img_params);
				$graphics_text=
					",analytical_data_graphics_blob=".fixBlob($spectrum_data["img"][0]).
					",analytical_data_properties_blob=".fixBlob(json_encode($spectrum_data["analytical_data_properties"])). // GC-Peaks,NMR-Peaks, usw.
					",analytical_data_graphics_type=".fixStrSQL($spectrum_data["img_mime"][0]); // update image only if generated
				
				if (empty($analytical_data["analytical_data_interpretation"])) {
					$_REQUEST["analytical_data_interpretation"]=$spectrum_data["interpretation"];
				}
				if (empty($analytical_data["analytical_data_csv"])) {
					$_REQUEST["analytical_data_csv"]=$spectrum_data["analytical_data_csv"][0];
				}
				if (!empty($spectrum_data["analytics_method_name"]) && empty($method["analytics_method_id"])) {
					// try to find matching method
					list($method)=mysql_select_array(array(
						"table" => "analytics_method", 
						"dbs" => -1, 
						"filter" => "analytics_method_name LIKE ".fixStrSQL($spectrum_data["analytics_method_name"]), 
						"limit" => 1, 
					));
					//~ var_dump($method);die();
					// create new one
					if (empty($method)) {
						$_REQUEST["analytics_type_id"]=$device["analytics_type_id"];
						$_REQUEST["analytics_device_id"]=$device["analytics_device_id"];
						$_REQUEST["analytics_method_name"]=$spectrum_data["analytics_method_name"];
						performEdit("analytics_method",$db_id,$dbObj);
						
						$method["analytics_method_id"]=$_REQUEST["analytics_method_id"];
						$method["analytics_method_name"]=$spectrum_data["analytics_method_name"];
					}
				}
				
				// insert additional images (if any)
				$sql_query[]="DELETE FROM analytical_data_image WHERE analytical_data_image.analytical_data_id=".fixNull($pk).";";
				$imagesUpdated=true;
				for ($a=1;$a<count($spectrum_data["img"]);$a++) {
					$sql_query[]="INSERT INTO analytical_data_image (analytical_data_id,reaction_id,project_id,image_no,analytical_data_graphics_blob,analytical_data_csv,analytical_data_graphics_type) 
						VALUES (".fixNull($pk).",".fixNull($_REQUEST["reaction_id"]).",".fixNull($_REQUEST["project_id"]).",".$a.",".fixBlob($spectrum_data["img"][$a]).",".fixStrSQL($spectrum_data["analytical_data_csv"][$a]).",".fixStrSQL($spectrum_data["img_mime"][$a]).");";
				}
			}
		}
		
		if ($_REQUEST["desired_action"]=="add" && isDefaultAnalyticsIdentifier($analytical_data_identifier,$device["analytics_type_code"],$method["analytics_method_name"],$analytical_data["lab_journal_code"],$analytical_data["nr_in_lab_journal"])) { // ist es ein default-Spektrum?
			if (in_array(strtolower($device["analytics_type_code"]),$method_aware_types)) { // Methode einbeziehen (1H, 13C)?
				$analytics_method_condition=" AND ".nvpArray($method,"analytics_method_id",SQL_NUM,true);
			}
			else {
				$analytics_method_condition="";
			}
			
			// set default and take away from others of type gc for that reaction
			$sql_query[]="UPDATE analytical_data SET ".
				"analytical_data_display_settings=analytical_data_display_settings&~1 ".
				"WHERE ".
				nvp("reaction_id",SQL_NUM,true)." AND ".nvpArray($device,"analytics_type_id",SQL_NUM,true).$analytics_method_condition.";";
			$analytical_data_display_settings=1;
			$analytical_data_display_settings_text="analytical_data_display_settings=".fixNull($analytical_data_display_settings).",";
		}
		
		// write dataset
		// $analytical_data_identifier=$_REQUEST["lab_journal_code"]."_".$_REQUEST["nr_in_lab_journal"]."_".$device["analytics_type_code"]."_".$device["analytics_device_driver"];
		$sql_query[]="UPDATE analytical_data SET ".
			nvp("reaction_id",SQL_NUM).
			"project_id=".fixNull($reaction["project_id"]).",".
			nvp("reaction_chemical_id",SQL_NUM).
			$analytical_data_identifier_text.
			$analytical_data_display_settings_text.
			"analytical_data_blob=".fixBlob($raw_data["zipdata"]).
			$graphics_text.",".
			nvp("fraction_no",SQL_TEXT).
			nvp("measured_by",SQL_TEXT).
			nvp("analytical_data_interpretation",SQL_TEXT).
			nvp("analytical_data_comment",SQL_TEXT).
			nvp("analytical_data_csv",SQL_TEXT).
			nvpArray($method,"analytics_method_id",SQL_NUM).
			nvpArray($method,"analytics_method_name",SQL_TEXT).
			nvpArray($method,"analytics_method_text",SQL_TEXT).
			nvpArray($device,"analytics_type_id",SQL_NUM).
			nvpArray($device,"analytics_type_name",SQL_TEXT).
			nvpArray($device,"analytics_type_code",SQL_TEXT).
			nvpArray($device,"analytics_type_text",SQL_TEXT).
			nvpArray($device,"analytics_device_id",SQL_NUM).
			nvpArray($device,"analytics_device_name",SQL_TEXT).
			nvpArray($device,"analytics_device_driver",SQL_TEXT).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		if (!$imagesUpdated) {
			// also being updated if no change to images occurs
			$sql_query[]="UPDATE analytical_data_image SET ".
				nvp("reaction_id",SQL_NUM).
				"project_id=".fixNull($reaction["project_id"]).
				" WHERE analytical_data_image.analytical_data_id=".fixNull($pk).";";
		}
		//~ print_r($sql_query);die();
		// project unverändert lassen!!
		
		if (!empty($_REQUEST["reaction_id"])) {
			if ($analytical_data["status"]==1) {
				$sql_query=arr_merge(
					$sql_query,
					performReactionOnInventory($db_id,$dbObj,$_REQUEST["reaction_id"],2)
				);
			}
			addChangeNotify($db_id,$dbObj,"reaction",$_REQUEST["reaction_id"]);
		}
		$result=performQueries($sql_query,$db); // singleUpdate
	break;
	
	case "analytics_device":
		if (empty($_REQUEST["analytics_device_name"])) {
			return array(FAILURE,s("error_no_analytics_device_name"));
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$db); // cmdINSERT
		}
		
		// check if URL is empty, ftp:// or within localAnalyticsPath
		makeAnalyticsPathSafe($_REQUEST["analytics_device_url"]); // beim Eintragen des generellen Pfads muß Sigel-Regel nicht beachtet werden
		
		$password_text="";
		if (!empty($_REQUEST["analytics_device_password"]) && $_REQUEST["analytics_device_password"]==$_REQUEST["analytics_device_password_repeat"]) {
			$password_text=nvp("analytics_device_password",SQL_TEXT);
		}
		
		$sql_query[]="UPDATE analytics_device SET ".
			nvp("analytics_device_disabled",SQL_NUM).
			nvp("analytics_device_name",SQL_TEXT).
			nvp("analytics_device_driver",SQL_TEXT).
			nvp("analytics_device_username",SQL_TEXT).
			$password_text.
			//~ nvp("analytics_device_img_ext",SQL_TEXT).
			nvp("analytics_device_url",SQL_TEXT).
			nvp("analytics_type_id",SQL_NUM).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$db); // singleUpdate
	break;
	
	case "analytics_method":
		if (empty($_REQUEST["analytics_method_name"])) {
			return array(FAILURE,s("error_no_method_name"));
		}
		elseif ($_REQUEST["analytics_type_id"]=="") {
			return array(FAILURE,s("error_no_analytics_type"));
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$db); // cmdINSERT
			$_REQUEST["analytics_method_id"]=$pk;
		}
		$sql_query[]="UPDATE analytics_method SET ".
			nvp("analytics_method_disabled",SQL_NUM).
			nvp("analytics_method_name",SQL_TEXT).
			nvp("analytics_method_text",SQL_TEXT).
			nvp("analytics_device_id",SQL_NUM).
			nvp("analytics_type_id",SQL_NUM).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$db); // singleUpdate
	break;
	
	case "analytics_type":
		if (empty($_REQUEST["analytics_type_name"])) {
			return array(FAILURE,s("error_no_analytics_type_name"));
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$db); // cmdINSERT
		}
		$sql_query[]="UPDATE analytics_type SET ".
			nvp("analytics_type_name",SQL_TEXT).
			nvp("analytics_type_code",SQL_TEXT).
			nvp("analytics_type_text",SQL_TEXT).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$db); // singleUpdate
	break;
	
	case "accepted_order":
		// order manager goes through list of open chemical_orders, selects supplier and makes further modifiactions if neccessary
		
		$add_multiple=1;
		$list_int_name="item_list";
		if (is_array($_REQUEST[$list_int_name])) {
			$add_multiple=count($_REQUEST[$list_int_name]);
		}
		elseif (!empty($_REQUEST["order_uid_cp"])) {
			// always based on chemical_order from either the own or a foreign database
			list($chemical_order)=mysql_select_array(array(
				"table" => "chemical_order", 
				"filter" => "chemical_order.order_uid LIKE BINARY ".fixBlob($_REQUEST["order_uid_cp"]), 
				"limit" => 1, 
			)); // get existing values
			//~ $_REQUEST=arr_merge($_REQUEST,array_key_filter($chemical_order,array()));
		}
		
		$pks_added=array();
		for ($a=0;$a<$add_multiple;$a++) {
			if (empty($pk) || $add_multiple>1) {
				if (is_array($_REQUEST[$list_int_name])) {
					$UID=$_REQUEST[$list_int_name][$a];
					// skip empty lines
					if (
						getValueUID($list_int_name,$UID,"catNo")=="" && 
						getValueUID($list_int_name,$UID,"beautifulCatNo")=="" && 
						getValueUID($list_int_name,$UID,"number_packages_text")==""
					) {
						continue;
					}
				}
				
				$createArr=SQLgetCreateRecord($table,$now,true);
				$createArr["order_uid_cp"]=fixBlob($_REQUEST["order_uid"]);
				$pk=getInsertPk($table,$createArr,$db);
				$pks_added[]=$pk;
				addChangeNotify($db_id,$dbObj,$table,$pk);
			}
			else {
				list($accepted_order)=mysql_select_array(array(
					"table" => "accepted_order", 
					"filter" => "accepted_order.accepted_order_id=".fixNull($pk), 
					"dbs" => -1, 
					"limit" => 1, 
				)); // get existing values
				
				// additional checks if change is allowed
				
			}
			
			if (is_array($_REQUEST[$list_int_name])) {
				$UID=$_REQUEST[$list_int_name][$a];
				
				if (getValueUID($list_int_name,$UID,"supplier")=="") {
					// auto set supplier according to vendor_id
					list($supplier)=mysql_select_array(array(
						"table" => "vendor", 
						"filter" => "institution.institution_id=".fixNull(getValueUID($list_int_name,$UID,"vendor_id")), 
						"dbs" => -1, 
						"limit" => 1, 
						"flags" => QUERY_EDIT, 
					)); // get existing values
					$_REQUEST[$list_int_name."_".$UID."_"."supplier"]=$supplier["institution_codes"][0]["supplier_code"];
				}
				
				$itemText=nvpUID($list_int_name,$UID,"name",SQL_TEXT).
					nvpUID($list_int_name,$UID,"cas_nr",SQL_TEXT).
					nvpUID($list_int_name,$UID,"supplier",SQL_TEXT).
					nvpUID($list_int_name,$UID,"supplier_offer_id",SQL_NUM).
					nvpUID($list_int_name,$UID,"vendor_id",SQL_NUM). // hier bereits gesetzt
					nvpUID($list_int_name,$UID,"catNo",SQL_TEXT).
					nvpUID($list_int_name,$UID,"beautifulCatNo",SQL_TEXT).
					nvpUID($list_int_name,$UID,"package_amount",SQL_NUM).
					nvpUID($list_int_name,$UID,"package_amount_unit",SQL_TEXT).
					nvpUID($list_int_name,$UID,"so_price",SQL_NUM).
					nvpUID($list_int_name,$UID,"price",SQL_NUM).
					nvpUID($list_int_name,$UID,"price_currency",SQL_TEXT).
					nvpUID($list_int_name,$UID,"number_packages_text",SQL_TEXT).
					nvpUID($list_int_name,$UID,"density_20",SQL_NUM).
					nvpUID($list_int_name,$UID,"number_packages",SQL_NUM).
					nvpUID($list_int_name,$UID,"vat_rate",SQL_NUM).
					nvpUID($list_int_name,$UID,"central_comment",SQL_TEXT);
				
				$historyText="";
			}
			else {
				$_REQUEST["supplier"]=trim($_REQUEST["supplier"]);
				if (empty($_REQUEST["vendor_id"]) ) {
					$_REQUEST["v_institution_code"]=$_REQUEST["supplier"]; // assign
					performEdit("institution",$db_id,$dbObj,array("prefix" => "v_", ));
					$_REQUEST["vendor_id"]=$_REQUEST["v_institution_id"];
				}
				elseif ($_REQUEST["permanent_assignment"] && !empty($_REQUEST["supplier"])) {
					// save supplier to institution_id
					//~ $sql_query[]="DELETE FROM institution_code WHERE supplier_code LIKE BINARY ".fixStrSQL($_REQUEST["supplier"]).";"; // delete other links
					$sql_query[]="DELETE FROM institution_code WHERE supplier_code=".fixStrSQL($_REQUEST["supplier"]).";"; // delete other links
					$sql_query[]="INSERT INTO institution_code SET ". // cmdINSERTsub
						"institution_id=".fixNull($_REQUEST["vendor_id"]).",".
						"supplier_code=".fixStrSQL($_REQUEST["supplier"]).";";
				}
				//~ else {
					//~ // save supplier to institution_id
					//~ $sql_query[]="DELETE FROM institution_code WHERE supplier_code LIKE BINARY ".fixStrSQLSearch($_REQUEST["supplier"]).";";
				//~ }
			
				// if so_price is changed and supplier_offer_id is set, update price
				if (
					!empty($_REQUEST["supplier_offer_id"]) // set
					&& ($accepted_order["supplier_offer_id"]==$_REQUEST["supplier_offer_id"]) // not changed
					&& ($accepted_order["so_price"]!=$_REQUEST["so_price"] || $accepted_order["price_currency"]!=$_REQUEST["price_currency"]) // changed
				) {
					$sql_query[]="UPDATE supplier_offer SET ".
						nvp("so_price",SQL_NUM).
						"so_price_currency=".fixStrSQL($_REQUEST["price_currency"]).
						getPkCondition("supplier_offer",$_REQUEST["supplier_offer_id"]);
					addChangeNotify($db_id,$dbObj,"supplier_offer",$_REQUEST["supplier_offer_id"]);
				}
				
				// if price is changed compared to $accepted_order, update history_entry
				$changes=array(
					compareChanges($accepted_order,$_REQUEST,array("price","price_currency")), 
					compareChanges($accepted_order,$_REQUEST,array("package_amount","package_amount_unit")), 
					compareChanges($accepted_order,$_REQUEST,"number_packages"), 
					$_REQUEST["history_entry"], 
				);
				
				$historyText=getHistorySQL($now,joinIfNotEmpty($changes,"\n"));
			
				$itemText=nvp("name",SQL_TEXT).
					nvp("cas_nr",SQL_TEXT).
					nvp("supplier",SQL_TEXT).
					nvp("supplier_offer_id",SQL_NUM).
					nvp("vendor_id",SQL_NUM).
					nvp("catNo",SQL_TEXT).
					nvp("beautifulCatNo",SQL_TEXT).
					nvp("package_amount",SQL_NUM).
					nvp("package_amount_unit",SQL_TEXT).
					nvp("so_price",SQL_NUM).
					nvp("price",SQL_NUM).
					nvp("price_currency",SQL_TEXT).
					nvp("number_packages_text",SQL_TEXT).
					nvp("density_20",SQL_NUM).
					nvp("number_packages",SQL_NUM).
					nvp("vat_rate",SQL_NUM).
					nvp("central_comment",SQL_TEXT);
			}
			
			//~ if ($_REQUEST["btn_lagerchem"]=="lager") { // KL chemical shop
				//~ $_REQUEST["supplier"]=ausgabe_name;
			//~ }
			
			$sql_query[]="UPDATE accepted_order SET ".
				$itemText.
				nvp("order_cost_centre_cp",SQL_TEXT).
				nvp("order_acc_no_cp",SQL_TEXT).
				nvp("ordered_by_username_cp",SQL_TEXT).
				nvp("customer_order_date_cp",SQL_DATETIME).
				nvp("central_order_status",SQL_NUM).
				nvp("selected_alternative_id",SQL_NUM).
				$historyText.
				//~ nvp("vat_rate",SQL_NUM).
				SQLgetChangeRecord($table,$now).
				getPkCondition($table,$pk);
			
			$result=performQueries($sql_query,$dbObj);
		}
	break;
	
	case "chemical_order":
		if (isEmptyStr($_REQUEST["ordered_by_person"]) || isEmptyStr($_REQUEST["ordered_by_username"])) {
			return array(FAILURE,s("error_no_order_person"));
		}
		if ($_REQUEST["order_status"]>2 && !($permissions & _order_approve)) {
			return array(FAILURE,s("permission_denied"));
		}
		
		// Daten lesen
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$createArr["order_uid"]=fixBlob(uniqid("",true));
			$pk=getInsertPk($table,$createArr,$db);
		}
		else {
			list($chemical_order)=mysql_select_array(array(
				"table" => "chemical_order", 
				"filter" => "chemical_order.chemical_order_id=".fixNull($pk), 
				"dbs" => -1, 
				"limit" => 1, 
			)); // get existing values, also accepted_order if available
			
			// additional checks if change is allowed
			
		}
		
		$order_dateSQL="";
		if ($_REQUEST["customer_order_status"]>1 && $chemical_order["customer_order_status"]<=1) { // new or from planned
			$order_dateSQL="customer_order_date=FROM_UNIXTIME(".$now."),";
		}
		
		$list_int_name="order_alternative";
		$customer_selected_alternative_id="";
		if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) { // add alternatives
			$pk2=getValueUID($list_int_name,$UID,"order_alternative_id");
			switch(getDesiredAction($list_int_name,$UID)) {
			case "del":
				$sql_query[]="DELETE FROM ".$list_int_name." WHERE ".
					nvpUID($list_int_name,$UID,"order_alternative_id",SQL_NUM,true).";";
			break;
			case "add":
			case "update":
				if (empty($pk2)) {
					$createArr=array();
					addNvp($createArr,"order_alternative_id",SQL_NUM);
					$pk2=getInsertPk("order_alternative",$createArr,$dbObj); // cmdINSERTsub
				}
				
				if ($UID==$_REQUEST["order_alternative_customer_selected_alternative_id"] || count($_REQUEST[$list_int_name])==1) {
					$customer_selected_alternative_id=$pk2;
					$value=getValueUID($list_int_name,$UID,"price")*getValueUID($list_int_name,$UID,"number_packages");
					$value_unit=getValueUID($list_int_name,$UID,"price_unit");
				}
					
				$sql_query[]="UPDATE ".$list_int_name." SET ".
					nvpUID($list_int_name,$UID,"supplier",SQL_TEXT).
					nvpUID($list_int_name,$UID,"catNo",SQL_TEXT).
					nvpUID($list_int_name,$UID,"beautifulCatNo",SQL_TEXT).
					nvpUID($list_int_name,$UID,"name",SQL_TEXT).
					nvpUID($list_int_name,$UID,"cas_nr",SQL_TEXT).
					nvpUID($list_int_name,$UID,"package_amount",SQL_NUM).
					nvpUID($list_int_name,$UID,"package_amount_unit",SQL_TEXT).
					nvpUID($list_int_name,$UID,"number_packages_text",SQL_TEXT).
					nvpUID($list_int_name,$UID,"density_20",SQL_NUM).
					nvpUID($list_int_name,$UID,"number_packages",SQL_NUM).
					nvpUID($list_int_name,$UID,"vat_rate",SQL_NUM).
					nvpUID($list_int_name,$UID,"price",SQL_NUM).
					nvpUID($list_int_name,$UID,"price_currency",SQL_TEXT).
					"chemical_order_id=".fixNull($pk).
					" WHERE order_alternative_id=".fixNull($pk2).";";
			break;
			}
		}
		
		if (
			$_REQUEST["customer_order_status"]==2 
			&& 
			$_REQUEST["ordered_by_person"]==$person_id 
			//~ $_REQUEST["ordered_by_username"]==$db_user 
			&& (
				($permissions & _order_approve) 
				|| (
					$value<=$own_data["cost_limit"] && $value_unit==$own_data["cost_limit_amount_unit"]
				)
			)
		) { // automatisch genehmigen, wenn der User entsprechende Rechte hat
			$_REQUEST["customer_order_status"]=3;
		}
		
		$sql_query[]="UPDATE chemical_order SET ".
			nvp("ordered_by_person",SQL_NUM).
			nvp("ordered_by_username",SQL_TEXT).
			"customer_selected_alternative_id=".fixNull($customer_selected_alternative_id).",". // radio-button aus liste
			$order_dateSQL.
			nvp("order_cost_centre",SQL_TEXT).
			nvp("order_acc_no",SQL_TEXT).
			nvp("customer_comment",SQL_TEXT).
			nvp("customer_order_status",SQL_NUM).
			nvp("may_change_supplier",SQL_NUM).
			nvp("chemical_order_secret",SQL_NUM).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$dbObj);
	break;
	
	case "chemical_storage":
		list($chemical_storage)=mysql_select_array(array(
			"table" => "chemical_storage", 
			"dbs" => -1, 
			"filter" => "chemical_storage.chemical_storage_id=".fixNull($pk), 
			"limit" => 1, 
		));
		
		if (($permissions & _chemical_edit)==0) { // no general permission
			// is it new and _chemical_create ?
			if (empty($pk) && ($permissions & _chemical_create)) {
				// ok
			}
			elseif (empty($pk) && ($permissions & _chemical_edit_own) && $_REQUEST["owner_person_id"]==$person_id) {
				// ok
			}
			elseif ($permissions & _chemical_edit_own) {
				// check if it is one's own
				if ($chemical_storage["owner_person_id"]!=$person_id) {
					return array(FAILURE,s("permission_denied"));
				}
			}
			else {
				return array(FAILURE,s("permission_denied"));
			}
		}
		
		// handle molecule
		if ($_REQUEST["action_molecule"]=="add") {
			$_REQUEST["molecule_id"]=""; // molecule_id comes from foreign db, clear to create new dataset
		}
		if (empty($_REQUEST["molecule_id"]) || $_REQUEST["action_molecule"]=="update") { // add or edit molecule if necessary
			performEdit("molecule",$db_id,$dbObj);
		}
		
		$add_multiple=intval($_REQUEST["add_multiple"]); // make number
		if ($add_multiple<1) {
			$add_multiple=1;
		}
		
		$pks_added=array();
		
		// only once
		$sdsSQL=getSDSSQL("safety_sheet").
			getSDSSQL("alt_safety_sheet");
		
		if (defined("QDBS") && $_REQUEST["desired_action"]=="update") {
			$conn=mysqli_connect(db_server,$_SESSION["user"],$_SESSION["password"],$_SESSION["db_name"]);
			// $person replaced by $own_data, which is already there
			
			$ktsql="SELECT storage_id FROM chemical_storage where chemical_storage_id = ".fixNull($_REQUEST["chemical_storage_id"]).";";
			$ktsqlres=mysqli_query($conn,$ktsql);
			$resold=mysqli_fetch_array($ktsqlres,MYSQLI_ASSOC);
			if ($resold) {
				if (!$resold["storage_id"]) {
					$oldstorage=array();
					$oldstorage["storage_name"]="";
				}
				else {			
					$ktsql2="SELECT storage_id,storage_name FROM storage WHERE storage_id =".fixNull($resold["storage_id"]).";";
					$ktsqlres2=mysqli_query($conn,$ktsql2);
					$oldstorage=mysqli_fetch_array($ktsqlres2,MYSQLI_ASSOC);
				}
			}
			else {
				$oldstorage=array();
				$oldstorage["storage_id"]="";
			}
			if (!$_REQUEST["storage_id"]) {
				$newstorage=array();
				$newstorage["storage_name"]="";
			}
			else {
				$ktsql3="SELECT storage_id,storage_name FROM storage WHERE storage_id =".fixNull($_REQUEST["storage_id"]).";";
				$ktsqlres3=mysqli_query($conn,$ktsql3);
				$newstorage=mysqli_fetch_array($ktsqlres3,MYSQLI_ASSOC);
			}
			if ($oldstorage["storage_name"] and $newstorage["storage_name"]) {
				//assigned,assigned
				if ($newstorage["storage_name"]!=$oldstorage["storage_name"]) {
					//assigned->assigned
					$history_new=" ".$own_data["first_name"]." ".$own_data["last_name"].": STORAGE (".$oldstorage["storage_name"]." => ".$newstorage["storage_name"].")";	
					$ktsql4="UPDATE chemical_storage SET history=CONCAT(history,'\n',NOW(),".fixStr($history_new).") WHERE chemical_storage_id=".fixNull($_REQUEST["chemical_storage_id"]).";";
					$ktres=mysqli_query($conn,$ktsql4);
					mysqli_close($conn);
				}
				else {
					//no changes needed
					mysqli_close($conn);
				}
			}
			else {
				//one of storages is NOT set
				if ($oldstorage["storage_name"]) {
					$history_new=" ".$own_data["first_name"]." ".$own_data["last_name"].": STORAGE (".$oldstorage["storage_name"]." => NOT SET)";
					$ktsql4="UPDATE chemical_storage SET history=CONCAT(history,'\n',NOW(),".fixStr($history_new).") WHERE chemical_storage_id=".fixNull($_REQUEST["chemical_storage_id"]).";";
					$ktres=mysqli_query($conn,$ktsql4);
					mysqli_close($conn);
				}
				else {
					if ($newstorage["storage_name"]) {
						$history_new=" ".$own_data["first_name"]." ".$own_data["last_name"].": STORAGE (NOT SET => ".$newstorage["storage_name"].")";
						$ktsql4="UPDATE chemical_storage SET history=CONCAT(history,'\n',NOW(),".fixStr($history_new).") WHERE chemical_storage_id=".fixNull($_REQUEST["chemical_storage_id"]).";";
						$ktres=mysqli_query($conn,$ktsql4);
						mysqli_close($conn);
					}
					else {
						mysqli_close($conn);
					}
				}
			}
		}
			
		for ($a=0;$a<$add_multiple;$a++) {
			$historyText=$_REQUEST["history_entry"];
			if (empty($pk) || $add_multiple>1) {
				$createArr=SQLgetCreateRecord($table,$now,true);
				$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
				$pks_added[]=$pk;
				addChangeNotify($db_id,$dbObj,$table,$pk);
				$_REQUEST["chemical_storage_id"]=$pk;
			}
			elseif ($g_settings["full_logging"]) {
				// compare change and log it
				list($borrow_result)=mysql_select_array(array(
					"table" => "chemical_storage_for_storage", 
					"dbs" => "-1", 
					"filter" => "chemical_storage_id=".fixNull($pk), 
					"limit" => 1, 
				));
				$historyText=joinIfNotEmpty(
					array(
						ifNotEmpty(
							"",
							getChemicalStorageLogText(
								$borrow_result,
								$_REQUEST
							),
							" ".$_REQUEST["reason"]
							),
						$historyText
					),
					", ");
			}
			$_REQUEST["compartment"]=fixCompartment($_REQUEST["compartment"]); // make one letter codes uppercase
			
			// remove price arrays
			if (is_array($_REQUEST["price"])) {
				$_REQUEST["price"]="";
			}
			
			// if a date for disposed is set or a target database, set disabled to true
			$disabledSQL="";
			if (!empty($_REQUEST["transferred_to_db_id"]) || !empty($chemical_storage["disposed_by"])) {
				$disabledSQL.="chemical_storage_disabled=1,";
			}
			
			$sql_query=array(
				"UPDATE chemical_storage SET ".
				nvp("molecule_id",SQL_NUM).
				nvp("from_reaction_id",SQL_NUM).
				nvp("chemical_storage_secret",SQL_NUM).
				nvp("order_date",SQL_DATE).
				nvp("open_date",SQL_DATE).
				nvpUnit("tmd","tmd_unit").
				nvpUnit("amount","amount_unit").
				nvpUnit("chemical_storage_conc","chemical_storage_conc_unit").
				"actual_amount=(".fixNull($_REQUEST["actual_amount"])." * (SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch($_REQUEST["amount_unit"]).")),".
				"amount_is_volume=(SELECT unit_type=\"v\" FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch($_REQUEST["amount_unit"])."),".
				nvp("chemical_storage_attrib",SQL_SET).
				nvp("chemical_storage_solvent",SQL_TEXT).
				nvp("chemical_storage_density_20",SQL_NUM).
				nvp("expiry_date",SQL_DATE).
				nvp("description",SQL_TEXT).
				nvp("container",SQL_TEXT).
				nvp("protection_gas",SQL_TEXT).
				nvp("chemical_storage_bilancing",SQL_NUM).
				nvp("storage_id",SQL_NUM).
				nvp("transferred_to_db_id",SQL_NUM).
				$sdsSQL.
				$disabledSQL.
				nvp("owner_person_id",SQL_NUM).
				nvp("compartment",SQL_TEXT).
				//~ nvp("borrowed_by_person_id",SQL_NUM). // do not change this!!
				nvp("comment_cheminstor",SQL_TEXT).
				nvp("lot_no",SQL_TEXT).
				nvp("supplier",SQL_TEXT).
				nvp("cat_no",SQL_TEXT).
				nvp("price",SQL_NUM).
				nvp("price_currency",SQL_TEXT).
				nvp("migrate_id_cheminstor",SQL_TEXT).
				nvp("chemical_storage_btm_list",SQL_NUM). // added 081030 FR
				nvp("chemical_storage_sprengg_list",SQL_TEXT). // added 081030 FR
				nvp("chemical_storage_barcode",SQL_TEXT).
				"inventory_check_by=".fixStrSQL($db_user).
				",inventory_check_when=FROM_UNIXTIME(".$now."),".
				getHistorySQL($now,$historyText).
				SQLgetChangeRecord($table,$now).
				getPkCondition($table,$pk), 
			);
			
			// assign chemical_storage_type
			$sql_query[]="DELETE FROM chemical_storage_chemical_storage_type WHERE chemical_storage_id=".$pk.";";
			if ($_REQUEST["chemical_storage_type"]) {
				foreach ($_REQUEST["chemical_storage_type"] as $chemical_storage_type_id) {
					if (is_numeric($chemical_storage_type_id)) {
						$sql_query[]="INSERT INTO chemical_storage_chemical_storage_type (chemical_storage_type_id,chemical_storage_id) ".
							"VALUES (".fixNull($chemical_storage_type_id).",".fixNull($pk).");";
					}
				}
			}
			
			// split chemical, only db_id=-1
			if (!empty($_REQUEST["split_chemical_storage_id"])) {
				$filter="chemical_storage.chemical_storage_id=".fixNull($_REQUEST["split_chemical_storage_id"]);
				// alte Menge abfragen
				//~ list($chemical_storage_result)=mysql_select_array(array(
					//~ "dbs" => -1, 
					//~ "table" => "chemical_storage", 
					//~ "filter" => $filter, 
					//~ "limit" => 1, 
				//~ )); // bestehende Daten abfragen
				//~ // Welche Einheit?
				//~ switch(strtolower($chemical_storage_result["amount_unit_type"])) {
				//~ case "m":
					//~ $cmdText="(".fixNull(getValueUID($list_int_name,$UID,"m_brutto"))." * (SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch(getValueUID($list_int_name,$UID,"mass_unit"))."))";
				//~ break;
				//~ case "v":
					//~ $cmdText="(".fixNull(getValueUID($list_int_name,$UID,"volume"))." * (SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch(getValueUID($list_int_name,$UID,"volume_unit"))."))";
				//~ break;
				//~ }
				// neue Menge setzen
				// vorerst davon ausgehen, daß kein Wechsel zwischen Massen- und Volumeneinheit stattfindet
				$sql_query[]="UPDATE chemical_storage SET 
actual_amount=actual_amount-(".fixNull($_REQUEST["actual_amount"])." * (SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch($_REQUEST["amount_unit"]).")) WHERE ".$filter.";";
			}
			
			// set reference to order
			if (!empty($_REQUEST["order_uid"])) {
				$sql_query[]="UPDATE chemical_order SET ".
					nvp("chemical_storage_id",SQL_NUM,true).
					" WHERE order_uid LIKE BINARY ".fixBlob($_REQUEST["order_uid"]).";";
			}
			
			if ($a==0 && !empty($sdsSQL) ) { // transfer sds to molecule, only once
				$sql_query[]="UPDATE chemical_storage LEFT OUTER JOIN molecule ON chemical_storage.molecule_id=molecule.molecule_id SET 
molecule.default_safety_sheet_url=chemical_storage.safety_sheet_url, 
molecule.default_safety_sheet_by=chemical_storage.safety_sheet_by, 
molecule.default_safety_sheet_blob=chemical_storage.safety_sheet_blob, 
molecule.default_safety_sheet_mime=chemical_storage.safety_sheet_mime,
molecule.alt_default_safety_sheet_url=chemical_storage.alt_safety_sheet_url, 
molecule.alt_default_safety_sheet_by=chemical_storage.alt_safety_sheet_by, 
molecule.alt_default_safety_sheet_blob=chemical_storage.alt_safety_sheet_blob, 
molecule.alt_default_safety_sheet_mime=chemical_storage.alt_safety_sheet_mime
WHERE chemical_storage_id=".fixNull($pk).";";
			}
			
			$result=performQueries($sql_query,$dbObj); // singleUpdate
		}
	break;
	
	case "chemical_storage_type":
		if (empty($_REQUEST["chemical_storage_type_name"])) {
			return array(FAILURE,s("error_no_chemical_storage_type_name"));
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$db); // cmdINSERT
		}
		$sql_query[]="UPDATE chemical_storage_type SET ".
			nvp("chemical_storage_type_name",SQL_TEXT).
			nvp("chemical_storage_type_text",SQL_TEXT).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$db); // singleUpdate
	break;
	
	case "cost_centre":
		if (empty($_REQUEST["cost_centre"])) {
			return array(FAILURE,s("error_no_cost_centre"));
		}
		if (empty($pk)) {
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
		}
		$sql_query[]="UPDATE cost_centre SET ".
			nvp("cost_centre",SQL_TEXT).
			nvp("acc_no",SQL_TEXT).
			nvp("cost_centre_secret",SQL_NUM).
			nvp("cost_centre_name",SQL_TEXT,true).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$dbObj); // singleUpdate
	break;
	
	case "data_publication":
		if (empty($_REQUEST["publication_name"])) {
			return array(FAILURE,s("error_no_publication_name"));
		}
		if (empty($_REQUEST["literature_id"]) && (!empty($_REQUEST["authors"]) || !empty($_REQUEST["literature_year"]) || !empty($_REQUEST["literature_title"]))) { // add or edit molecule if necessary
			performEdit("literature",$db_id,$dbObj);
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$createArr["data_publication_uid"]="UUID()";
			$createArr["publication_status"]=1;
			$pk=getInsertPk($table,$createArr,$db); // cmdINSERT
			$_REQUEST["data_publication_id"]=$pk;
		}
		
		// Zuordnungen aktualisieren
		$ref_table_names=array("reaction","analytical_data");
		foreach ($ref_table_names as $ref_table_name) {
			$list_int_name="publication_".$ref_table_name;
			if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
				// remove duplicate lines automatically
				$desired_action=getDesiredAction($list_int_name,$UID);
				switch($desired_action) {
				case "del":
					$sql_query[]="DELETE FROM ".$list_int_name." WHERE ".nvpUID($list_int_name,$UID,$list_int_name."_id",SQL_NUM,true).";";
				break;
				case "add":
				case "update":
					$pk2=getValueUID($list_int_name,$UID,$list_int_name."_id");

					if (empty($pk2)) {
						$createArr=SQLgetCreateRecord($list_int_name,$now,true);
						$createArr[$list_int_name."_uid"]="UUID()";
						$createArr["data_publication_id"]=$pk;
						$pk2=getInsertPk($list_int_name,$createArr,$dbObj); // cmdINSERTsub
					}

					$sql_query[]="UPDATE ".$list_int_name." SET ".
						nvpUID($list_int_name,$UID,$ref_table_name."_id",SQL_NUM).
						nvpUID($list_int_name,$UID,$list_int_name."_text",SQL_TEXT).
						SQLgetChangeRecord($list_int_name,$now).
						getPkCondition($list_int_name,$pk2);
				break;
				}
			}
		}
		
		$sql_query[]="UPDATE data_publication SET ".
			nvp("publication_name",SQL_TEXT).
			nvp("publication_license",SQL_TEXT).
			nvp("publication_doi",SQL_TEXT).
			nvp("publication_text",SQL_TEXT).
			nvp("publication_db_id",SQL_NUM).
			nvp("literature_id",SQL_NUM).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$db); // singleUpdate
	break;
	
	case "institution":
		if (empty($_REQUEST[ $paramHash["prefix"]."institution_name" ])) {
			return array(FAILURE,s("error_no_institution_name"));
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
			$_REQUEST[ $paramHash["prefix"]."institution_id" ]=$pk;
		}
		
		$sql_query[]="DELETE FROM institution_code WHERE ".
			nvp("institution_id",SQL_NUM,$paramHashLast);
		
		$sql_query[]="UPDATE institution SET  ".
			nvp("institution_name",SQL_TEXT,$paramHash).
			nvp("person_name",SQL_TEXT,$paramHash).
			nvp("department_name",SQL_TEXT,$paramHash).
			nvp("city",SQL_TEXT,$paramHash).
			nvp("postcode",SQL_TEXT,$paramHash).
			nvp("country",SQL_TEXT,$paramHash).
			nvp("street",SQL_TEXT,$paramHash).
			nvp("street_number",SQL_NUM,$paramHash).
			nvp("tel_no",SQL_TEXT,$paramHash).
			nvp("fax_no",SQL_TEXT,$paramHash).
			nvp("customer_id",SQL_TEXT,$paramHash).
			nvp("comment_institution",SQL_TEXT,$paramHash).
			nvp("institution_type",SQL_SET,$paramHash).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$supplier_codes=explode("\n",fixLineEnd($_REQUEST[$paramHash["prefix"]."institution_codes"]));
		foreach ($supplier_codes as $supplier_code) {
			$supplier_code=trim($supplier_code);
			if (empty($supplier_code)) {
				continue;
			}
			// delete other links
			//~ $sql_query[]="DELETE FROM institution_code WHERE supplier_code LIKE BINARY ".fixStrSQL($supplier_code).";";
			$sql_query[]="DELETE FROM institution_code WHERE supplier_code=".fixStrSQL($supplier_code).";";
			$sql_query[]="INSERT INTO institution_code SET ".
				"institution_id=".fixNull($pk).",".
				"supplier_code=".fixStrSQL($supplier_code).";"; // cmdINSERTsub
		}
		
		$result=performQueries($sql_query,$dbObj); // singleUpdate
	break;
	
	case "lab_journal":
		if (empty($_REQUEST["lab_journal_code"])) {
			return array(FAILURE,s("error_no_lab_journal_code"));
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$createArr["lab_journal_status"]=1;
			$createArr["lab_journal_uid"]="UUID()";
			addNvp($createArr,"lab_journal_code",SQL_TEXT);
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
			
			if (!$importActive) {
				if ($_REQUEST["start_nr"]<1) {
					$_REQUEST["start_nr"]=1;
				}
				$end=$_REQUEST["start_nr"];
				if ($_REQUEST["create_empty_entries"]) {
					$start=1;
				}
				else {
					$start=$_REQUEST["start_nr"];
				}
				for ($a=$start;$a<=$end;$a++) {
					$_REQUEST["start_nr"]=$a;
					// create 1st entry/entries
					$_REQUEST=array_merge($_REQUEST,getDefaultDataset("reaction"));
					$_REQUEST["reaction_id"]="";
					$this_settings=getSettingsForPerson($_REQUEST["person_id"]);
					$_REQUEST["project_id"]=$this_settings["default_project"];
					
					$_REQUEST["lab_journal_id"]=$pk;
					$_REQUEST["reaction_carried_out_by"]=getPersonString($_REQUEST["person_id"],true); // owner of the LJ
					$_REQUEST["status"]=1; // status in terms of reaction
					performEdit("reaction",$db_id,$dbObj);
				}
			}
		}
		
		if ($_REQUEST["default_copy_target"]==-1) {
			$_REQUEST["default_copy_target"]=$pk;
		}
		
		$sql_query[]="UPDATE lab_journal SET  ".
			nvp("person_id",SQL_NUM).
			nvp("default_copy_target",SQL_NUM).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$dbObj); // singleUpdate
		// update permissions
		
	break;
	
	case "literature":
		if (empty($_REQUEST["sci_journal_id"])) { // create sci_journal if not id given
			performEdit("sci_journal",$db_id,$dbObj);
		}
		
		// move DOI scraper here (in the future)
		
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$createArr["literature_uid"]="UUID()";
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
			$_REQUEST["literature_id"]=$pk;
		}
		
		if (!empty($_REQUEST["literature_blob"]) && !empty($_REQUEST["literature_mime"])) {
			$literature_blob_upload=& $_REQUEST["literature_blob"];
			$literature_mime=& $_REQUEST["literature_mime"];
		}
		elseif (count($_FILES["literature_blob_upload"]) && $_FILES["literature_blob_upload"]["error"]==0) { // upload
			/*
		    [load_molfile] => Array
			(
			    [name] => Toluene.mol
			    [type] => chemical/x-mdl-molfile
			    [tmp_name] => /var/tmp/phpNhjfSD
			    [error] => 0
			    [size] => 719
			)
			*/
			$filename=$_FILES["literature_blob_upload"]["tmp_name"];
			$filesize=& $_FILES["literature_blob_upload"]["size"];
			// datei öffnen
			$handle = fopen($filename, "rb");
			// größe prüfen
			if ($filesize>0 && filesize($filename)==$filesize) {
				// datei einlesen
				$literature_blob_upload=fread($handle,$filesize);
			}
			$literature_mime=$_FILES["literature_blob_upload"]["type"];
			// datei schließen
			fclose($handle);
			// datei löschen
			@unlink($filename);
		}
		
		// split authors
		// 2 Formate:
		$authors=str_replace(array("*"),"",$_REQUEST["authors"]); // remove stars etc
		$andText=" and "; // sometimes in JACS citations
		
		// count commata, spaces and semicola
		// JACS: semicola>0 OR (commata==1 AND spaces<3)
		if (substr_count($authors,";")>0 || (substr_count($authors,",")==1 && substr_count($authors," ")<3)) {
			$separator=";";
			$authors=str_replace(array($andText,$separator.$separator),$separator,$authors); // remove and put separator if necessary
			// Nachname, Vornamen;...
			$author_list=explode($separator,$authors);
			foreach ($author_list as $author) {
				list($last,$first)=explode(",",$author,2);
				$author_data[]=array(trim($last),trim($first));
			}
		}
		else {
			$separator=",";
			$authors=str_replace(array($andText,$separator.$separator),$separator,$authors); // remove and put separator if necessary
			// Vorname Initial Nachname, ...
			$author_list=explode($separator,$authors);
			foreach ($author_list as $author) {
				$author=trim($author);
				if (strpos($author," ")===FALSE) {
					$first="";
					$last=$author;
				}
				else {
					$splitpos=0;
					do {
						$splitpos=strrpos($author," ",-$splitpos); // last space
						$last=substr($author,$splitpos+1);
						if (!isNameSuffix($last)) { // name parts separated by [Space]
							break;
						}
					} while ($splitpos!==FALSE);
					$first=substr($author,0,$splitpos);
				}
				$author_data[]=array(trim($last),trim($first));
			}
		}
		
		// fix 1234-1234
		if ($_REQUEST["page_low"]>$_REQUEST["page_high"]) {
			swap($_REQUEST["page_low"],$_REQUEST["page_high"]);
		}
		elseif ($_REQUEST["page_low"]==$_REQUEST["page_high"]) {
			unset($_REQUEST["page_low"]);
		}
		
		// alle autoren löschen
		$sql_query[]="DELETE FROM author WHERE literature_id=".$pk;
		if (is_array($author_data)) foreach ($author_data as $idx => $author) {
			$sql_query[]="INSERT INTO author SET literature_id=".fixNull($pk).
				",nr_in_literature=".fixNull($idx).
				",author_last=".fixStrSQL($author[0]).
				",author_first=".fixStrSQL($author[1]).
				";"; // cmdINSERTsub
		}
		
		$update_query="UPDATE literature SET ".
			nvp("sci_journal_id",SQL_NUM).
			nvp("literature_year",SQL_NUM).
			nvp("literature_volume",SQL_NUM).
			nvp("issue",SQL_NUM).
			nvp("page_low",SQL_NUM).
			nvp("page_high",SQL_NUM).
			nvp("doi",SQL_URLENCODE). // may contain < >
			nvp("isbn",SQL_TEXT).
			nvp("literature_title",SQL_TEXT).
			nvp("keywords",SQL_TEXT);
		
		if (!empty($literature_blob_upload)) {
			$update_query.="literature_blob=".fixBlob($literature_blob_upload).",literature_mime=".fixStrSQL($literature_mime).",";
			// Grafik erstellen aus PDF
			if (isPDF($literature_blob_upload)) {
				list($png,$txt)=data_convert($literature_blob_upload,"pdf",array("png","txt"));
				$update_query.="literature_blob_fulltext=".fixStrSQL($txt).",literature_graphics_blob=".fixBlob($png).",literature_graphics_type=\"image/png\",";
				unset($png);
				unset($txt);
			}
			else {
				$update_query.="literature_blob_fulltext=NULL,literature_graphics_blob=NULL,literature_graphics_type=NULL,";
			}
		}
		
		$update_query.=SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$sql_query[]=$update_query;
		$result=performQueries($sql_query,$dbObj); // singleUpdate

	break;
	
	case "message":
		if ($_REQUEST["from_person"]==$person_id) {
			if (count($_REQUEST["recipients"])==0) {
				return array(FAILURE,s("error_no_to_person"));
			}
			if (empty($pk)) {
				$createArr=SQLgetCreateRecord($table,$now,true);
				addNvp($createArr,"from_person",SQL_NUM);
				$createArr["issued"]="FROM_UNIXTIME(".$now.")";
				$pk=getInsertPk($table,$createArr,$dbObj); // unveränderliche Werte hier
			}
			$sql_query[]="DELETE FROM message_person WHERE message_id=".$pk;
			foreach ($_REQUEST["recipients"] as $this_person_id) {
				$sql_query[]="INSERT INTO message_person SET ".
					"message_id=".$pk.",".
					"person_id=".fixNull($this_person_id).",".
					"completion_status=1,".
					SQLgetCreateRecord("message_person",$now).";"; // cmdINSERTsub
			}
			$sql_query[]="UPDATE message SET ".
				nvp("message_subject",SQL_TEXT).
				nvp("message_text",SQL_TEXT).
				nvp("do_until",SQL_DATE).
				nvp("priority",SQL_NUM).
				SQLgetChangeRecord($table,$now).
				getPkCondition($table,$pk);
		}
		else {
			$sql_query[]="UPDATE message_person SET ".
				nvp("completion_status",SQL_NUM).
				nvp("p_comment",SQL_TEXT).
				SQLgetChangeRecord("message_person",$now).
				" WHERE message_id=".fixNull($pk). " AND person_id=".fixNull($person_id).";";
		}
		$result=performQueries($sql_query,$dbObj); // singleUpdate
	break;
	
	case "molecule":
		if (($permissions & _chemical_edit)==0) { // no general permission
			// is it new and _chemical_create ?
			if ($permissions & _chemical_edit_own) {
				// assume it is ok
			}
			elseif (empty($pk) && ($permissions & _chemical_create)) {
				// ok
			}
			else {
				return array(FAILURE,s("permission_denied"));
			}
		}
		$_REQUEST["cas_nr"]=makeCAS($_REQUEST["cas_nr"]);
		
		// Analyze molfile
		$moldata=removePipes($_REQUEST["molfile_blob"]); // store only valid molfiles
		$molecule_search=readMolfile($moldata,array() ); // for  fingerprinting and serialisation
		$_REQUEST["smiles"]=$molecule_search["smiles"];
		$_REQUEST["smiles_stereo"]=$molecule_search["smiles_stereo"];
		
		if (empty($molecule_search["atoms"])) { // no structure (mw does not help, can be polymeric), read sum formula
			$molecule_search=readSumFormula($_REQUEST["emp_formula"],array());
		}
		
		$names=explode("\n",fixLineEnd($_REQUEST["molecule_names_edit"]));
		array_walk($names,"arrTrim");
		$names=array_values(array_unique($names));
		
		// create new dataset
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
			$_REQUEST["molecule_id"]=$pk; // for adding chemical_storage afterwards
			
			// assign reactions to new molecule, NO FURTHER changes!!
			if (!empty($molecule_search["smiles_stereo"]) && ($permissions & _lj_edit)) {
				// do not overwrite name, only put if text is empty
				$sql_query[]="UPDATE reaction_chemical SET ".
					"standard_name=".fixStrSQL($names[0]).
					" WHERE molecule_id IS NULL AND smiles_stereo LIKE BINARY ".fixStrSQL($molecule_search["smiles_stereo"])." AND (standard_name IS NULL OR standard_name LIKE \"\");";
				$sql_query[]="UPDATE reaction_chemical SET ".
					nvp("molecule_id",SQL_NUM).
					nvp("cas_nr",SQL_TEXT).
					nvp("safety_r",SQL_TEXT).
					nvp("safety_h",SQL_TEXT).
					nvp("safety_s",SQL_TEXT).
					nvp("safety_p",SQL_TEXT).
					nvp("safety_sym",SQL_TEXT).
					nvp("safety_sym_ghs",SQL_TEXT,true).
					" WHERE molecule_id IS NULL AND smiles_stereo LIKE BINARY ".fixStrSQL($molecule_search["smiles_stereo"]).";";
			}
		}
		
		// auto sum formula
		$emp_formula_sort=$molecule_search["emp_formula_string_sort"];
		if (empty($_REQUEST["emp_formula"])) {
			$_REQUEST["emp_formula"]=$molecule_search["emp_formula_string"];
		}
		
		// auto MW
		if (empty($_REQUEST["mw"])) {
			$_REQUEST["mw"]=$molecule_search["mw"];
		}
		
		if (defined("staticMolImg")) {
			$patt1="/V/";    
			$patt2="/M(.*?)END/";
			if (preg_match($patt1,$_REQUEST["molfile_blob"]) && preg_match($patt2,$_REQUEST["molfile_blob"])) {
				list($gif,$svg)=getMoleculeGif($molecule_search,gif_x,gif_y,0,1,true,array("png","svg"));
			}
			elseif ($_REQUEST["gif_file"]) {
				$gif=$_REQUEST["gif_file"];
			}
		}
		else {
			list($gif,$svg)=getMoleculeGif($molecule_search,gif_x,gif_y,0,1,true,array("png","svg"));
		}
		
		$sdsSQL=getSDSSQL("default_safety_sheet").
			getSDSSQL("alt_default_safety_sheet");
		
		if ($permissions & _chemical_edit) {
			$negListeSQL=nvp("pos_liste",SQL_NUM).
				nvp("neg_liste",SQL_NUM);
		}
		
		$update_query="UPDATE molecule SET ".
			nvp("molecule_secret",SQL_NUM).
			$negListeSQL.
			nvp("cas_nr",SQL_TEXT).
			nvp("smiles",SQL_TEXT).
			nvp("smiles_stereo",SQL_TEXT).
			"molfile_blob=".fixBlob($moldata).",".
			"molecule_serialized=".fixBlob(serializeMolecule($molecule_search)).",".
			"emp_formula_sort=".fixStrSQL($emp_formula_sort).",".
			nvp("emp_formula",SQL_TEXT).
			nvp("mw",SQL_NUM).
			nvp("density_20",SQL_NUM).
			nvp("n_20",SQL_NUM).
			nvp("molecule_bilancing",SQL_NUM).
			nvp("mp_low",SQL_NUM).
			nvp("mp_high",SQL_NUM).
			nvp("bp_low",SQL_NUM).
			nvp("bp_high",SQL_NUM).
			nvpUnit("bp_press","press_unit").
			nvp("safety_r",SQL_TEXT).
			nvp("safety_h",SQL_TEXT).
			nvp("safety_s",SQL_TEXT).
			nvp("safety_p",SQL_TEXT).
			nvp("safety_text",SQL_TEXT).
			nvp("safety_sym",SQL_TEXT).
			nvp("safety_sym_ghs",SQL_TEXT).
			nvp("safety_cancer",SQL_TEXT).
			nvp("safety_mutagen",SQL_TEXT).
			nvp("safety_reprod",SQL_TEXT).
			nvp("safety_wgk",SQL_TEXT).
			$sdsSQL.
			nvp("safety_danger",SQL_TEXT).
			"gif_file=".fixBlob($gif).",".
			"svg_file=".fixBlob($svg).",".
			nvp("migrate_id_mol",SQL_TEXT).
			nvp("molecule_btm_list",SQL_NUM). // added 081030 FR
			nvp("molecule_sprengg_list",SQL_TEXT). // added 081030 FR
			getFingerprintSQL($molecule_search).
			nvp("comment_mol",SQL_TEXT).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$sql_query[]=$update_query;
		$sql_query[]="DELETE FROM molecule_names WHERE molecule_id=".$pk.";";
		if (count($names)) {
			foreach ($names as $idx => $name) {
				$is_trivial_name=endswith($name,"#");
				if ($is_trivial_name) {
					$name=substr($name,0,-1);
				}
				if (empty($name)) {
					continue;
				}
				$sql_query[]="INSERT INTO molecule_names(molecule_id,molecule_name,language_id,is_trivial_name,is_standard,molecule_names_secret) ".
					"VALUES (".$pk.",".fixStrSQL(trim($name)).",".fixStrSQL(strip_tags($lang)).",".intval($is_trivial_name).",".fixNull($idx==0).",".fixNull($_REQUEST["molecule_secret"]).");"; // cmdINSERTsub
			}
		}
		//~ $result=performQueries($sql_query,$db); // FIX-ME brauchen wir das hier???

		// assign molecule_type
		$sql_query[]="DELETE FROM molecule_molecule_type WHERE molecule_id=".$pk.";";
		if ($_REQUEST["molecule_type"]) {
			foreach ($_REQUEST["molecule_type"] as $molecule_type_id) {
				if (is_numeric($molecule_type_id)) {
					$sql_query[]="INSERT INTO molecule_molecule_type (molecule_type_id,molecule_id) ".
						"VALUES (".fixNull($molecule_type_id).",".fixNull($pk).");";
				}
			}
		}
		
		// Hier beginnt der Code zur Behandlung der Moleküleigenschaften.
		$list_int_name="molecule_property";
		$unique_fields=array("source","class","value","unit");
		$duplicate_check=array();
		$duplicate_actions=array("add" => "","update" => "del",);
		if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
			// remove duplicate lines automatically
			$desired_action=getDesiredAction($list_int_name,$UID);
			switch($desired_action) {
			case "":
			case "add":
			case "update":
				// extract values from REQUEST
				$new_entry=array();
				foreach ($unique_fields as $field_idx => $field_name) {
					$new_entry[$field_idx]=getValueUID($list_int_name,$UID,$field_name);
				}
				if (in_array($new_entry,$duplicate_check)) {
					// duplicate found, change add to "" and update to del
					if ($desired_action=="add") {
						$desired_action="";
					}
					else {
						$desired_action="del";
					}
				}
				else {
					$duplicate_check[]=$new_entry;
				}
			break;
			}
			
			switch($desired_action) {
			case "del":
				$sql_query[]="DELETE FROM ".$list_int_name." WHERE ".nvpUID($list_int_name,$UID,"molecule_property_id",SQL_NUM,true).";";
			break;
			case "add":
			case "update":
				$pk2=getValueUID($list_int_name,$UID,"molecule_property_id");
				
				if (empty($pk2)) {
					$createArr=SQLgetCreateRecord($list_int_name,$now,true);
					addNvp($createArr,"molecule_id",SQL_NUM);
					$pk2=getInsertPk($list_int_name,$createArr,$dbObj); // cmdINSERTsub
				}
				$sql_query[]="UPDATE ".$list_int_name." SET ".
					nvpUID($list_int_name,$UID,"source",SQL_TEXT).
					nvpUID($list_int_name,$UID,"class",SQL_TEXT).
					nvpUIDUnit($list_int_name,$UID,"value_high","unit").
					"value_low=(".fixNull(getValueUID($list_int_name,$UID,"value_low"))." * (SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch(getValueUID($list_int_name,$UID,"unit")).")),".
					nvpUID($list_int_name,$UID,"conditions",SQL_TEXT).
					nvpUID($list_int_name,$UID,"molecule_property_comment",SQL_TEXT).
					SQLgetChangeRecord($list_int_name,$now).
					getPkCondition($list_int_name,$pk2);
			break;
			}
		}
		
		// Betriebsanweisungen
		$list_int_name="molecule_instructions";
		if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
			// remove duplicate lines automatically
			$desired_action=getDesiredAction($list_int_name,$UID);
			switch($desired_action) {
			case "del":
				$sql_query[]="DELETE FROM ".$list_int_name." WHERE ".nvpUID($list_int_name,$UID,"molecule_instructions_id",SQL_NUM,true).";";
			break;
			case "add":
			case "update":
				$pk2=getValueUID($list_int_name,$UID,"molecule_instructions_id");
				
				if (empty($pk2)) {
					$createArr=SQLgetCreateRecord($list_int_name,$now,true);
					addNvp($createArr,"molecule_id",SQL_NUM);
					$pk2=getInsertPk($list_int_name,$createArr,$dbObj); // cmdINSERTsub
					
					// create fake data for PDF
					$_REQUEST[$list_int_name."_".$UID."_molecule_instructions_created_when"]=getSQLFormatDate();
					$_REQUEST[$list_int_name."_".$UID."_molecule_instructions_created_by"]=$db_user;
				}
				
				// generate PDF according to lang & data entered
				require_once "lib_instructions_pdf.php";
				$pdf=getWorkingInstructionsPDF($_REQUEST,$list_int_name,$UID,$names);
				
				$sql_query[]="UPDATE ".$list_int_name." SET ".
					"file_blob=".fixBlob($pdf->Output("test.pdf","S")).",". // no image for now, maybe later
					nvpUID($list_int_name,$UID,"lang",SQL_TEXT).
					nvpUID($list_int_name,$UID,"betr_anw_gefahren",SQL_TEXT).
					nvpUID($list_int_name,$UID,"betr_anw_schutzmass",SQL_TEXT).
					nvpUID($list_int_name,$UID,"betr_anw_schutzmass_sym",SQL_SET). // according to DIN EN ISO 7010
					nvpUID($list_int_name,$UID,"betr_anw_verhalten",SQL_TEXT).
					nvpUID($list_int_name,$UID,"betr_anw_verhalten_sym",SQL_SET). // according to DIN EN ISO 7010
					nvpUID($list_int_name,$UID,"betr_anw_erste_h",SQL_TEXT).
					nvpUID($list_int_name,$UID,"betr_anw_erste_h_sym",SQL_SET). // according to DIN EN ISO 7010
					nvpUID($list_int_name,$UID,"betr_anw_entsorgung",SQL_TEXT).
					nvpUID($list_int_name,$UID,"molecule_instructions_comment",SQL_TEXT).
					SQLgetChangeRecord($list_int_name,$now).
					getPkCondition($list_int_name,$pk2);
			break;
			}
		}
		
		
		if ($permissions & _order_accept) { // MPI
			$list_int_name="mat_stamm_nr";
			if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
				$pk2=getValueUID($list_int_name,$UID,"mat_stamm_nr_id");
				switch(getDesiredAction($list_int_name,$UID)) {
				case "del":
					$sql_query[]="DELETE FROM ".$list_int_name." WHERE ".nvpUID($list_int_name,$UID,"mat_stamm_nr_id",SQL_NUM,true).";";
				break;
				case "add":
				case "update":
					if (empty($pk2)) {
						$createArr=array();
						addNvp($createArr,"molecule_id",SQL_NUM);
						$pk2=getInsertPk($list_int_name,$createArr,$dbObj); // cmdINSERTsub
					}
					$sql_query[]="UPDATE ".$list_int_name." SET ".
						nvpUID($list_int_name,$UID,"sap_stamm_nr",SQL_TEXT).
						nvpUID($list_int_name,$UID,"comment_stamm_nr",SQL_TEXT,true).
						getPkCondition($list_int_name,$pk2);
				break;
				}
			}
		}
		
		// Later have signature function here
			
		$result=performQueries($sql_query,$dbObj); // singleUpdate
		
		if ($result && !empty($_REQUEST["new_chemical_storage"])) {
			$result=performEdit("chemical_storage",$db_id,$dbObj);
		}	
	break;
	
	case "molecule_type":
		if (empty($_REQUEST["molecule_type_name"])) {
			return array(FAILURE,s("error_no_molecule_type_name"));
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$db); // cmdINSERT
		}
		$sql_query[]="UPDATE molecule_type SET ".
			nvp("molecule_type_name",SQL_TEXT).
			nvp("molecule_type_text",SQL_TEXT).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$db); // singleUpdate
	break;
	
	case "mpi_order":
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
		}
		$sql_query[]="UPDATE mpi_order SET ".
			nvp("other_db_id",SQL_NUM).
			nvp("order_person",SQL_TEXT).
			nvp("order_account",SQL_TEXT).
			nvp("order_date",SQL_DATE).
			nvp("delivery_date",SQL_DATE).
			nvp("molecule_name",SQL_TEXT).
			nvp("cas_nr",SQL_TEXT).
			nvp("chemical_storage_conc",SQL_NUM).
			nvp("chemical_storage_conc_unit",SQL_TEXT).
			nvp("chemical_storage_solvent",SQL_TEXT).
			nvp("supplier",SQL_TEXT).
			nvp("sap_bestell_nr",SQL_TEXT).
			nvp("sap_stamm_nr",SQL_TEXT).
			nvp("bessi",SQL_TEXT).
//>>>MST00
                        nvp("total_amount",SQL_NUM).
                        nvp("amount_unit",SQL_TEXT).
//<<<MST00
			nvp("mpi_order_status",SQL_NUM).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$list_int_name="mpi_order_item";
		if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
		
			$pk2=getValueUID($list_int_name,$UID,"mpi_order_item_id");
			
			switch(getDesiredAction($list_int_name,$UID)) {
			case "del":
				$sql_query[]="DELETE FROM mpi_order_item WHERE mpi_order_item_id=".fixNull($pk2).";";
			break;
			case "add":
			case "update":
				if (empty($pk2)) {
					addNvp($createArr,"mpi_order_id",SQL_NUM);
					$pk2=getInsertPk("mpi_order_item",$createArr,$dbObj); // cmdINSERTsub
				}
				$sql_query[]="UPDATE mpi_order_item SET ".
					nvpUID($list_int_name,$UID,"amount",SQL_NUM).
					nvpUID($list_int_name,$UID,"amount_unit",SQL_TEXT).
					nvpUID($list_int_name,$UID,"chemical_storage_barcode",SQL_TEXT,true).
					" WHERE mpi_order_item_id=".fixNull($pk2).";";
			break;
			}
		}
		$result=performQueries($sql_query,$dbObj); // singleUpdate
	break;
	
	case "order_comp":
		if (empty($_REQUEST["institution_id"])) {
			return array(FAILURE,s("error_no_vendor_id"));
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
			$_REQUEST["order_comp_id"]=$pk;
		}
		$sql_query[]="UPDATE order_comp SET ".
			nvp("institution_id",SQL_NUM).
			nvp("comp_order_date",SQL_DATE).
			nvp("lagerpauschale",SQL_NUM).
			nvp("fixed_costs",SQL_NUM).
			nvp("fixed_costs_vat_rate",SQL_NUM).
			nvp("currency",SQL_TEXT).
			nvp("order_way",SQL_TEXT).
			nvp("order_identifier",SQL_TEXT).
			nvp("kleinauftrag_nrn",SQL_TEXT).
			nvp("central_cost_centre",SQL_TEXT).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		// Liste
		$list_int_name="accepted_order";
		$grand_total=0;
		if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) { // Gesamtsumme berechnen, um Fixkosten aufzuteilen
			if ($_REQUEST["currency"]!=getValueUID($list_int_name,$UID,"price_currency")) {
				continue;
			}
			$grand_total+=getValueUID($list_int_name,$UID,"price");
		}
		
		//~ $sql_query[]="UPDATE ".$list_int_name." SET order_comp_id=NULL WHERE order_comp_id=".fixNull($pk).";"; // take old ones out
		if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
			$pk2=getValueUID($list_int_name,$UID,"accepted_order_id");
			
			if ($_REQUEST["currency"]!=getValueUID($list_int_name,$UID,"price_currency")) {
				continue;
			}
			
			if ($grand_total>0) { // Aufteilung nach Anteil an der Gesamtsumme
				$factor=getValueUID($list_int_name,$UID,"price")/$grand_total;
			}
			else { // gleichmäßige Aufteilung
				$factor=1/count($_REQUEST[$list_int_name]);
			}
			
			list($accepted_order)=mysql_select_array(array(
				"table" => "accepted_order", 
				"filter" => "accepted_order.accepted_order_id=".fixNull($pk2), 
				"dbs" => -1, 
				"limit" => 1, 
			)); // get existing values
			
			$changes=array(
				compareChangesUID($accepted_order,$_REQUEST,$list_int_name,$UID,array("price","price_currency")), 
				compareChangesUID($accepted_order,$_REQUEST,$list_int_name,$UID,array("package_amount","package_amount_unit")), 
				compareChangesUID($accepted_order,$_REQUEST,$list_int_name,$UID,"number_packages"), 
			);
			
			$historyText=getHistorySQL($now,joinIfNotEmpty($changes,"\n"));
			
			// bei den verbleibenden Einträgen mit der gleichen Währung order_comp_id setzen und Werte in order_alternative updaten (falls Fehler korrigiert wurden)
			$sql_query[]="UPDATE ".$list_int_name." SET ". 
				nvpUID($list_int_name,$UID,"name",SQL_TEXT).
				nvpUID($list_int_name,$UID,"cas_nr",SQL_TEXT).
				nvpUID($list_int_name,$UID,"beautifulCatNo",SQL_TEXT).
				nvpUID($list_int_name,$UID,"package_amount",SQL_NUM).
				nvpUID($list_int_name,$UID,"package_amount_unit",SQL_TEXT).
				nvpUID($list_int_name,$UID,"so_price",SQL_NUM).
				nvpUID($list_int_name,$UID,"price",SQL_NUM).
				nvpUID($list_int_name,$UID,"price_currency",SQL_TEXT).
				nvpUID($list_int_name,$UID,"number_packages_text",SQL_TEXT).
				nvpUID($list_int_name,$UID,"density_20",SQL_NUM).
				nvpUID($list_int_name,$UID,"number_packages",SQL_NUM).
				nvpUID($list_int_name,$UID,"vat_rate",SQL_NUM).
				"fixed_costs_share=".fixNull($_REQUEST["fixed_costs"]*$factor).",".
				"fixed_costs_share_vat_rate=".fixNull($_REQUEST["fixed_costs_vat_rate"]).",".
				"central_order_date=FROM_UNIXTIME(".$now."),".
				"central_order_status=\"central_ordered\",".
				"order_comp_id=".fixNull($pk).",".
				$historyText.
				SQLgetChangeRecord($list_int_name,$now).
				getPkCondition($list_int_name,$pk2);
			
		}
		
		$result=performQueries($sql_query,$dbObj); // singleUpdate
	break;
	
	case "other_db":
		if ($_REQUEST["db_pass"]!=$_REQUEST["db_pass_repeat"]) {
			return array(FAILURE,s("password_dont_match"));
		}
		if (empty($pk)) {
			if ($_REQUEST["db_pass"]=="") { // Kennwort muß sein
				return array(FAILURE,s("password_none"));
			}
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$db); // cmdINSERT
		}
		if ($_REQUEST["host"]=="localhost" && !defined("allowLocalhostLink")) { // otherwise problems with piping
			$_REQUEST["host"]="127.0.0.1";
		}
		if ($_REQUEST["db_pass"]!="") { // sonst wird das Passwort gelassen
			$password_nvp=nvp("db_pass",SQL_TEXT);
		}
		if ($_REQUEST["db_beauty_name"]=="") { // sonst wird das Passwort gelassen
			$_REQUEST["db_beauty_name"]=$_REQUEST["db_name"];
		}
		
		$sql_query[]="UPDATE other_db SET ".
			nvp("other_db_disabled",SQL_NUM).
			nvp("db_beauty_name",SQL_TEXT).
			nvp("host",SQL_TEXT).
			nvp("db_name",SQL_TEXT).
			nvp("capabilities",SQL_SET).
			$password_nvp.
			nvp("db_user",SQL_TEXT).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$db); // singleUpdate
		$_REQUEST["updateTopFrame"]="true";
	break;
	
	case "person":
		$_REQUEST["permissions"]=
			@array_sum($_REQUEST["permissions_general"])+
			@array_sum($_REQUEST["permissions_chemical"])+
			@array_sum($_REQUEST["permissions_lab_journal"])+
			@array_sum($_REQUEST["permissions_order"]);
		
		//~ print_r($_REQUEST);die();
		
		fixPerson(); // check request data for hacking attempts
		$pass_result=checkPass($_REQUEST["new_password"],$_REQUEST["new_password_repeat"],!empty($pk)); // returns array($code,$message)
		$username_result=checkUsername($_REQUEST["username"]);
		if ($pass_result[0]!=SUCCESS) {
			return $pass_result;
		}
		elseif ($username_result[0]!=SUCCESS) {
			return $username_result;
		}
		// initital checks complete
		
		if (!empty($person_id)) { // is the change done by the person itself? No change of permissions allowed then
			$same_person=($person_id==$pk); // if changing own stuff
		}
		
		$current_user=fixStrSQL($_REQUEST["username"])."@".fixStrSQL($_REQUEST["remote_host"]);  // CHKN - should this not be 'php_server' to be consistent?
		
		if (empty($pk)) { // create new user
			
			// check if user already exists
			if (usernameExists($_REQUEST["username"])) { // mysql.user
				return array(FAILURE,s("person_exists"));
			}
			elseif ($_REQUEST["new_password"]=="") { // new user requires password
				return array(FAILURE,s("password_none"));
			}
			
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$db); // cmdINSERT
			$_REQUEST["person_id"]=$pk;
			
			// insert welcome message
			$createArr=array(
				"message_subject" => fixStrSQL(s("welcome_all_subject")),
				"message_text" => fixStrSQL(s("welcome_all")),
			);
			$message_id=getInsertPk("message",$createArr,$db); // cmdINSERT
			addChangeNotify($db_id,$dbObj,"message",$message_id);
			
			// delete remainders of an old user with this name
			//~ $user=fixStrSQL($_REQUEST["username"])."@".fixStrSQL($_REQUEST["remote_host"]);  // CHKN - should this not be 'php_server' to be consistent?
			mysqli_query($db,"GRANT USAGE ON *.* TO ".$current_user.";");  # CHKN added back compatibility for MySQL < 5.7 that has no DROP USER IF EXISTS
			mysqli_query($db,"DROP USER ".$current_user.";"); // result unimportant
			// FIXME
			
			$sql_query=array(
				// Benutzer erstellen
				"CREATE USER ".$current_user." IDENTIFIED BY ".fixStrSQL($_REQUEST["new_password"]).";",
				// Begrüßungsnachricht
				"INSERT INTO message_person (person_id,message_id) VALUES (".fixNull($pk).",".fixNull($message_id).");", // cmdINSERT
			);
			//~ $result=performQueries($sql_query,$db); // FIX-ME do we really need this here??
			
			$newPerson=true;
		}
		else {
			// ggf umbenennung des users
			// alten username holen
			list($oldusername,$oldremote_host)=get_username_from_person_id($pk);
			if (empty($oldremote_host)) {
				$oldremote_host="%";
			}
			
			$userExists=usernameExists($oldusername); // if user table is not consistent
			$sql_query=array();
			// wenn anders, umbenennen
			if ($oldusername!=$_REQUEST["username"] || strtolower($oldremote_host)!=strtolower($_REQUEST["remote_host"]) || !$userExists) { // Änderung  // CHKN - should this not be 'php_server' to be consistent?
				if ($userExists) {
					$sql_query[]="RENAME USER ".fixStrSQL($oldusername)."@".fixStrSQL($oldremote_host)." TO ".$current_user.";";
					$sql_query[]="DROP VIEW IF EXISTS".getSelfViewName($oldusername).";";
				}
				else {
					$sql_query[]="CREATE USER ".$current_user." IDENTIFIED BY ".fixStrSQL($_REQUEST["new_password"]).";";
				}
				$sql_query=arr_merge($sql_query,getSelfView($current_user,$_REQUEST["username"],$pk));
			}
		}
		
		// set project membership
		$sql_query[]="DELETE FROM project_person WHERE person_id=".$pk.";";
		if (count($_REQUEST["project"])) {
			foreach ($_REQUEST["project"] as $project_id) {
				if (is_numeric($project_id)) {
					$sql_query[]="INSERT INTO project_person (project_id,person_id) VALUES (".fixNull($project_id).",".fixNull($pk).")"; // cmdINSERT
				}
			}
		}
		/* $sql_query[]="DELETE FROM cost_centre WHERE person_id=".$pk.";";
		$cost_centres=explode(",",$_REQUEST["owns_cost_centres"]);
		if (count($cost_centres)) {
			foreach ($cost_centres as $cost_centre) {
				if (!empty($cost_centre)) {
					$sql_query[]="INSERT INTO cost_centre (project_id,person_id) VALUES (".fixStr($cost_centre).",".fixNull($pk).")";
				}
			}
		} */
		
		// generate person_barcode from sigel
		//~ $barcodeText="";
		//~ if (!empty($_REQUEST["sigle"])) {
			//~ $barcodeText="person_barcode=".fixStrSQL(generateSigelBarcodeEAN13($_REQUEST["sigle"])).",";
		//~ }
		
		$sql_query[]="UPDATE person SET ".
			nvp("person_disabled",SQL_NUM).
			nvp("last_name",SQL_TEXT).
			nvp("first_name",SQL_TEXT).
			nvp("title",SQL_TEXT).
			nvp("nee",SQL_TEXT).
			nvp("sigle",SQL_TEXT).
			nvp("person_barcode",SQL_TEXT).
			nvp("cost_centre",SQL_TEXT).
			nvp("acc_no",SQL_TEXT).
			nvp("email",SQL_TEXT).
			nvp("remote_host",SQL_TEXT).
			nvp("institution_id",SQL_NUM).
			($same_person?"":(nvp("permissions",SQL_NUM))).
			//~ $barcodeText.
			nvp("username",SQL_TEXT).
			nvp("cost_limit",SQL_NUM).
			nvp("cost_limit_currency",SQL_TEXT).
			nvp("email_chemical_supply",SQL_TEXT).
			nvp("preferred_language",SQL_TEXT).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		//~ $result=performQueries($sql_query,$db); // FIX-ME
		
		// perform changes in PASSWORD or grants
		if ($same_person) { // cannot change own privileges
			if (!empty($_REQUEST["new_password"])) { // otherwise no change
				$sql_query[]="SET PASSWORD = PASSWORD(".fixStrSQL($_REQUEST["new_password"]).");";
				$sql_query[]="FLUSH PRIVILEGES;";
				$_SESSION["password"]=$_REQUEST["new_password"];
			}
		}
		else {
			if (!empty($_REQUEST["new_password"])) { // otherwise no change
				$sql_query[]="SET PASSWORD FOR ".$current_user." = PASSWORD(".fixStrSQL($_REQUEST["new_password"]).");";
			}
			mysqli_query($db,"REVOKE ALL PRIVILEGES, GRANT OPTION FROM ".$current_user.";"); // ignore errors
			if (!$_REQUEST["person_disabled"]) { // no privileges otherwise
				$sql_query=array_merge($sql_query,getGrantArray($_REQUEST["permissions"],$current_user,$_REQUEST["username"],$pk,$db_name));
			}
			$sql_query[]="FLUSH PRIVILEGES;";
		}
		
		//~ print_r($sql_query);die();
		
		$result=performQueries($sql_query,$db); // singleUpdate
		
		// also create lab journal
		if ($result && !empty($_REQUEST["new_lab_journal"])) {
			$result=performEdit("lab_journal",$db_id,$dbObj);
		}	
		
		// create person's own private project
		if ($newPerson) {
			// eigenes Privatprojekt
			$_REQUEST["person"]=array($pk);
			$_REQUEST["project_name"]=formatPersonNameNatural($_REQUEST)." ".s("confidential"); // change to language of the user
			$_REQUEST["project_text"]=s("confidential1").formatPersonNameNatural($_REQUEST).s("confidential2");
			$_REQUEST["project_members_only"]=1;
			performEdit("project",$db_id,$dbObj);
		}
	break;
	
	case "project":
		if (empty($_REQUEST["project_name"])) {
			return array(FAILURE,s("error_no_project_name"));
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$createArr["project_uid"]="UUID()";
			$pk=getInsertPk($table,$createArr,$db); // cmdINSERT
		}
		
		// Personen und Literaturbezüge löschen
		$sql_query=array(
			"DELETE FROM project_person WHERE project_id=".$pk.";",
			"DELETE FROM project_literature WHERE project_id=".$pk.";",
		);
		// Personen neu setzen
		if (is_array($_REQUEST["person"])) foreach ($_REQUEST["person"] as $this_person_id) {
			if (is_numeric($this_person_id)) {
				$sql_query[]="INSERT INTO project_person SET project_id=".$pk.",person_id=".fixNull($this_person_id).";"; // cmdINSERTsub
			}
		}
		
		// Literatur neu setzen
		$list_int_name="project_literature";
		if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
			switch(getDesiredAction($list_int_name,$UID)) {
			case "del":
				// do nothing
			break;
			case "add":
			case "update":
			default: // all links are deleted
				$sql_query[]="INSERT INTO ".$list_int_name." SET project_id=".fixNull($pk).",".
					nvpUID($list_int_name,$UID,"literature_id",SQL_NUM,true).";"; // cmdINSERTsub
			}
		}
		
		$sql_query[]="UPDATE project SET ".
			nvp("project_name",SQL_TEXT).
			nvp("project_text",SQL_TEXT).
			nvp("project_members_only",SQL_NUM).
			nvp("project_status",SQL_NUM,true).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$db); // singleUpdate
	break;
	
	case "reaction":
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$createArr["status"]=1;
			$createArr["nr_in_lab_journal"]="(
					SELECT IFNULL(MAX(nr_in_lab_journal)+1,".ifempty($_REQUEST["start_nr"],1).") FROM (
						SELECT nr_in_lab_journal,lab_journal_id FROM reaction
					) AS x 
					WHERE lab_journal_id=".fixNull($_REQUEST["lab_journal_id"])."
				)";
			$createArr["reaction_uid"]="UUID()";
			addNvp($createArr,"lab_journal_id",SQL_NUM);
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
			$_REQUEST["reaction_id"]=$pk;
		}
		else {
			list($reaction_result)=mysql_select_array(array(
				"dbs" => -1, 
				"table" => "reaction", 
				"filter" => "reaction.reaction_id=".fixNull($pk), 
				"limit" => 1, 
			)); // bestehende Daten abfragen
			
			// ist das LJ offen?
			if ($reaction_result["lab_journal_status"]>lab_journal_open || $reaction_result["status"]>reaction_open) {
				return array(FAILURE,s("error_no_lab_journal_closed"));
			}
			
			// ist die Person Student und will fremdes LJ bearbeiten?
			if (($permissions & _lj_edit)==0 && $reaction_result["person_id"]!=$person_id) {
				return array(FAILURE,s("permission_denied"));
			}
			
			// "auto_create_lj_snapshot", BEFORE saving changes
			if ($g_settings["auto_create_lj_snapshot"]>0) {
				if ($now-$reaction_result["reaction_archive_last"]>daySec*$g_settings["auto_create_lj_snapshot"]) {
					performVersion($table,$db_id,$dbObj,s("auto_version"));
				}

/*				// get date of last snapshot
				list($reaction_archive_result)=mysql_select_array(array(
					"dbs" => -1, 
					"table" => "reaction_archive_last", 
					"filter" => "reaction_archive.reaction_id=".fixNull($pk), 
					"limit" => 1, 
					"order_obj" => array(
						array("field" => "reaction_archive_last", "order" => "DESC", "no_hints" => true),
					), 
				)); // bestehende Daten abfragen
				$_REQUEST["version_comment"]="auto";
				if (empty($reaction_archive_result["reaction_archive_last"])) {
					// if N/A, take date of creation and $g_settings["auto_create_lj_snapshot"]/2
					if ($now-$reaction_result["reaction_archive_last"]>daySec*$g_settings["auto_create_lj_snapshot"]/2) {
						performVersion($table,$db_id,$dbObj);
					}
				}
				elseif ($now-$reaction_archive_result["reaction_archive_last"]>daySec*$g_settings["auto_create_lj_snapshot"]) {
					performVersion($table,$db_id,$dbObj);
				}*/
			}
			
			// ist der neue Status >= der alte ? admin darf zurückstellen
			if (($permissions & _lj_admin)==0 && $reaction_result["status"]>$_REQUEST["status"]) {
				$_REQUEST["status"]=$reaction_result["status"]; // nicht verändern
			}
		}
		
		//~ $rxndata=removePipes($_REQUEST["rxnfile_blob"]);
		//~ $reaction=readRxnfile($rxndata); // standard conform!!
		
		$reaction=array(
			"reactants" => 0,
			"products" => 0,
			"molecules" => array(),
		);
		$identifier=array();
		$stoch_coefficients=array();
		
		// Buchstaben einfügen
		for ($a=0;$a<2;$a++) {
			$b=1;
			switch($a) {
			case 0:
				// Edukte
				$list_int_name="reactants";
			break;
			case 1:
				// Produkte
				$list_int_name="products";
			break;
			}
			if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
				if (getDesiredAction($list_int_name,$UID)=="del") {
					continue;
				}
				$molfile_blob=getValueUID($list_int_name,$UID,"molfile_blob");
				$text=$b;
				if ($a==0) {
					$text=numToLett($text);
				}
				
				$reaction[$list_int_name]++;
				$reaction["molecules"][]=$molfile_blob;
				$identifier[]=$text;
				$stoch_coeff=getValueUID($list_int_name,$UID,"stoch_coeff");
				if ($stoch_coeff==1) { // 1 is obsolete
					$stoch_coeff="";
				}
				$stoch_coefficients[]=$stoch_coeff;
				$b++;
			}
		}
		
		$rxndata=writeRxnfile($reaction); // combine molfiles
		$reaction=readRxnfile($rxndata);
		normaliseReaction($reaction); // create data structure
		$rxndata=writeRxnfile($reaction); // write clean
		$reaction["identifier"]=$identifier;
		$reaction["stoch_coeff"]=$stoch_coefficients;
		
		list($gif,$svg)=getReactionGif($reaction,rxn_gif_x,rxn_gif_y,0,1,6,array("png","svg"));
		
		$reaction_prototype_text="";
		if (!empty($_REQUEST["reaction_prototype"]) && !empty($_REQUEST["reaction_prototype_db_id"])) {
			$reaction_prototype_text=nvp("reaction_prototype",SQL_NUM). // only for automatically created copies
				nvp("reaction_prototype_db_id",SQL_NUM); // only for automatically created copies
		}
					
		//~ var_export($reaction);die();
		//~ die($_REQUEST["realization_text"]."XXX".makeHTMLSafe($_REQUEST["realization_text"]));
		$sql_query=array(
			// delete before possible status change
			"DELETE gc_peak FROM reaction LEFT OUTER JOIN gc_peak ON gc_peak.reaction_id=reaction.reaction_id WHERE reaction.status<=".reaction_open." AND gc_peak.reaction_id=".$pk.";", 
			//~ "DELETE FROM reaction_property USING reaction,reaction_property WHERE reaction_property.reaction_id=reaction.reaction_id AND reaction.status<=".reaction_open." AND reaction_property.reaction_id=".$pk.";", 
			"DELETE reaction_property FROM reaction LEFT OUTER JOIN reaction_property ON reaction_property.reaction_id=reaction.reaction_id WHERE reaction.status<=".reaction_open." AND reaction_property.reaction_id=".$pk.";", 
			//~ "DELETE FROM reaction_literature USING reaction,reaction_literature WHERE reaction_literature.reaction_id=reaction.reaction_id AND reaction.status<=".reaction_open." AND reaction_literature.reaction_id=".$pk.";", 
			"DELETE reaction_literature FROM reaction LEFT OUTER JOIN reaction_literature ON reaction_literature.reaction_id=reaction.reaction_id WHERE reaction.status<=".reaction_open." AND reaction_literature.reaction_id=".$pk.";", 
			
			"UPDATE reaction SET ".
				nvp("realization_text",SQL_TEXT).
				"realization_text_fulltext=".fixStrSQL(makeHTMLSearchable($_REQUEST["realization_text"])).",".
				nvp("realization_observation",SQL_TEXT).
				"realization_observation_fulltext=".fixStrSQL(makeHTMLSearchable($_REQUEST["realization_observation"])).",".
				nvp("reaction_title",SQL_TEXT).
				nvp("rxn_smiles",SQL_TEXT).
				nvpUnit("ref_amount","ref_amount_unit").
				"rxnfile_blob=".fixBlob($rxndata).",".
				"rxn_gif_file=".fixBlob($gif).",".
				"rxn_svg_file=".fixBlob($svg).",".
				//~ nvp("limit_reaction_chemical_id",SQL_NUM).
				nvp("reaction_carried_out_by",SQL_TEXT).
				nvp("reaction_started_when",SQL_DATETIME).
				//~ nvp("status",SQL_NUM). // update later, update chemical_storage if required
				nvp("project_id",SQL_NUM).
				$reaction_prototype_text.
				nvp("reaction_type_id",SQL_NUM).
				SQLgetChangeRecord($table,$now).
				getPkCondition($table,$pk,false)." AND (reaction.status<=".reaction_open." OR reaction.status IS NULL);", 
		);
		//~ print_r($sql_query);die();
		
		// perform 1st part, how about the transaction??
		
		$reaction_chemical_ids=array();
		foreach ($reaction_chemical_lists as $a => $list_int_name) {
			switch($a) {
			case 0:
				// Edukte
				$role=1;
			break;
			case 1:
				// Reagenzien
				$role=2;
			break;
			case 2:
				// Produkte
				$role=6;
			break;
			}
			
			if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
				$pk2=getValueUID($list_int_name,$UID,"reaction_chemical_id");
				
				// handle molfile, always needed for retention times
				$moldata=removePipes(getValueUID($list_int_name,$UID,"molfile_blob")); // store only valid molfiles
				$molecule_search=readMolfile($moldata,array() ); // for  fingerprinting and serialisation
					
				switch(getDesiredAction($list_int_name,$UID)) {
				case "del":
					$sql_query[]="DELETE FROM reaction_chemical USING reaction,reaction_chemical WHERE reaction_chemical.reaction_id=reaction.reaction_id AND reaction.status<=".reaction_open." AND ".nvpUID($list_int_name,$UID,"reaction_chemical_id",SQL_NUM,true).";";
				break;
				case "add":
				case "update":
					
					if (empty($pk2)) {
						$createArr=array();
						$createArr["reaction_chemical_uid"]="UUID()";
						addNvp($createArr,"reaction_id",SQL_NUM);
						$pk2=getInsertPk("reaction_chemical",$createArr,$dbObj); // cmdINSERTsub
					}
					
					// take yield for 1st product
					
					list($gif,$svg)=getMoleculeGif($molecule_search,rc_gif_x,rc_gif_y,0,1,false,array("png","svg"));
					
					//~ $_REQUEST["additionalFields"][]="yield";
					//~ $_REQUEST["yield"]=
					
					$sql_query[]="UPDATE reaction_chemical SET ".
						// "reaction_id=".fixNull($pk).",".
						"role=".fixNull($role).",".
						nvpUID($list_int_name,$UID,"molecule_id",SQL_NUM).
						nvpUID($list_int_name,$UID,"other_db_id",SQL_NUM).
						nvpUID($list_int_name,$UID,"chemical_storage_id",SQL_NUM).
						nvpUID($list_int_name,$UID,"from_reaction_id",SQL_NUM).
						nvpUID($list_int_name,$UID,"from_reaction_chemical_id",SQL_NUM).
						nvpUID($list_int_name,$UID,"standard_name",SQL_TEXT).
						nvpUID($list_int_name,$UID,"package_name",SQL_TEXT).
						nvpUID($list_int_name,$UID,"chemical_storage_barcode",SQL_TEXT).
						nvpUID($list_int_name,$UID,"safety_r",SQL_TEXT).
						nvpUID($list_int_name,$UID,"safety_h",SQL_TEXT).
						nvpUID($list_int_name,$UID,"safety_s",SQL_TEXT).
						nvpUID($list_int_name,$UID,"safety_p",SQL_TEXT).
						nvpUID($list_int_name,$UID,"safety_sym",SQL_TEXT).
						nvpUID($list_int_name,$UID,"safety_sym_ghs",SQL_TEXT).
						nvpUID($list_int_name,$UID,"cas_nr",SQL_TEXT).
						nvpUID($list_int_name,$UID,"mw",SQL_NUM).
						nvpUID($list_int_name,$UID,"density_20",SQL_NUM).
						nvpUIDUnit($list_int_name,$UID,"rc_conc","rc_conc_unit").
						nvpUID($list_int_name,$UID,"nr_in_reaction",SQL_NUM).
						nvpUID($list_int_name,$UID,"stoch_coeff",SQL_NUM).
						nvpUIDUnit($list_int_name,$UID,"m_brutto","mass_unit").
						nvpUIDUnit($list_int_name,$UID,"volume","volume_unit").
						nvpUIDUnit($list_int_name,$UID,"rc_amount","rc_amount_unit").
						nvpUID($list_int_name,$UID,"gc_yield",SQL_NUM).
						nvpUID($list_int_name,$UID,"yield",SQL_NUM).
						nvpUID($list_int_name,$UID,"measured",SQL_NUM).
						nvpUID($list_int_name,$UID,"colour",SQL_TEXT).
						nvpUID($list_int_name,$UID,"consistency",SQL_TEXT).
						nvpUID($list_int_name,$UID,"description",SQL_TEXT).
						nvpUID($list_int_name,$UID,"emp_formula",SQL_TEXT). // clean
						getFingerprintSQL($molecule_search).
						"project_id=".fixNull($_REQUEST["project_id"]).
						",molfile_blob=".fixBlob($moldata).
						",molecule_serialized=".fixBlob(serializeMolecule($molecule_search)).
						",gif_file=".fixBlob($gif). // no color
						",svg_file=".fixBlob($svg). // no color
						",smiles=".fixStrSQL($molecule_search["smiles"]).
						",smiles_stereo=".fixStrSQL($molecule_search["smiles_stereo"]).
						" WHERE reaction_chemical_id=".fixNull($pk2).";";
				break;
				}
				
				// array for matching the analytical_data
				$reaction_chemical_ids[$UID]=$pk2;
				$molecule_ids[$UID]=getValueUID($list_int_name,$UID,"molecule_id");
				$molecule_smiles_stereo[$UID]=$molecule_search["smiles_stereo"];
				$molecule_smiles[$UID]=$molecule_search["smiles"];
			}
			
		}
		//~ print_r($molecule_ids);
		//~ print_r($molecule_smiles_stereo);
		//~ print_r($molecule_smiles);die();
		// reaction_property (woher kriegen wir die eigenschaften, die im system gespeichert werden
		if (is_array($_REQUEST["additionalFields"])) foreach ($_REQUEST["additionalFields"] as $int_name) {
			if (empty($int_name)) {
				continue;
			}
			$sql_query[]="INSERT INTO reaction_property (reaction_id,reaction_property_name,reaction_property_value,reaction_property_number,project_id) 
VALUES (".fixNull($pk).",".fixStrSQL($int_name).",".fixStrSQL(makeHTMLSafe($_REQUEST[$int_name])).",".fixNull($_REQUEST[$int_name]).",".fixNull($_REQUEST["project_id"]).");";
		}
		
		// Analytik: Zuordnung zu Chemikalien über UID
		$list_int_name="analytical_data";
		// Standard (UID="") und Produkte durchgehen
		$gc_peak_UID_list=arr_merge(array(""),$_REQUEST["products"]);
		$gc_peak_UID_list=arr_merge($gc_peak_UID_list,$_REQUEST["reactants"]);
		
		if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) { // analytik durchgehen
			// handle default_for_type and so on
			$analytical_data_display_settings=0+(getValueUID($list_int_name,$UID,"default_for_type")?1:0);
			
			$sql_query[]="UPDATE analytical_data SET ".
				nvpUID($list_int_name,$UID,"measured_by",SQL_TEXT).
				nvpUID($list_int_name,$UID,"fraction_no",SQL_TEXT).
				nvpUID($list_int_name,$UID,"analytical_data_interpretation",SQL_TEXT).
				nvpUID($list_int_name,$UID,"analytical_data_comment",SQL_TEXT).
				//~ nvpUID($list_int_name,$UID,"nr_in_reaction",SQL_NUM).
				"analytical_data_display_settings=".fixNull($analytical_data_display_settings).",". // for printing the lab journal, also marks default property
				nvp("reaction_id",SQL_NUM). // eigentlich überflüssig, aber sicher ist sicher
				nvp("project_id",SQL_NUM).
				"reaction_chemical_id=".fixNull($reaction_chemical_ids[ getValueUID($list_int_name,$UID,"reaction_chemical_uid") ]).",".
				SQLgetChangeRecord($list_int_name,$now).
				" WHERE ".nvpUID($list_int_name,$UID,"analytical_data_id",SQL_NUM,true).";";
			
			// assign images also to reaction and project to enforce policies
			$sql_query[]="UPDATE analytical_data_image SET ".
				nvp("reaction_id",SQL_NUM).
				nvp("project_id",SQL_NUM,true).
				" WHERE ".nvpUID($list_int_name,$UID,"analytical_data_id",SQL_NUM,true).";";
			
			// add GC area information
			if (is_array($gc_peak_UID_list)) foreach ($gc_peak_UID_list as $rc_UID) {
				$rc_rc_UID=$rc_UID;
				if ($rc_UID=="") { // erster Eintrag
					$rc_rc_UID=$_REQUEST["gc_peak_std_uid_".$UID."_"];
				}
				if (isEmptyStr($_REQUEST["gc_peak_retention_time_".$UID."_".$rc_UID]) && $_REQUEST["gc_peak_area_percent_".$UID."_".$rc_UID]==="") { // nichts eingetragen
					continue;
				}
				$sql_query[]="INSERT INTO gc_peak SET ".
					nvpUID($list_int_name,$UID,"analytical_data_id",SQL_NUM).
					nvp("reaction_id",SQL_NUM).
					nvp("project_id",SQL_NUM).
					"retention_time=".fixNull($_REQUEST["gc_peak_retention_time_".$UID."_".$rc_UID]).
					",area_percent=".fixNull($_REQUEST["gc_peak_area_percent_".$UID."_".$rc_UID]).
					",gc_yield=".fixNull($_REQUEST["gc_peak_gc_yield_".$UID."_".$rc_UID]).
					",gc_peak_comment=".fixStrSQL($_REQUEST["gc_peak_gc_peak_comment_".$UID."_".$rc_UID]).
					",response_factor=".fixNull($_REQUEST["gc_peak_response_factor_".$UID."_".$rc_UID]).
					",reaction_chemical_id=".fixNull($reaction_chemical_ids[ $rc_rc_UID ]).";"; // cmdINSERTsub
				
				// REPLACE retention_times, UNIQUE(analytical_data_id,molecule_id)
				if (!isEmptyStr($_REQUEST["gc_peak_retention_time_".$UID."_".$rc_UID]) && 
					(!empty($molecule_smiles_stereo[ $rc_rc_UID ]) || !empty($molecule_smiles[ $rc_rc_UID ]) || !empty($molecule_ids[ $rc_rc_UID ]))
				) {
					$sql_query[]="DELETE FROM retention_time WHERE ".
						nvpUID($list_int_name,$UID,"analytics_type_id",SQL_NUM,true)." AND ".
						nvpUID($list_int_name,$UID,"analytics_device_id",SQL_NUM,true)." AND ".
						nvpUID($list_int_name,$UID,"analytics_method_id",SQL_NUM,true)." AND ".
						"smiles_stereo=".fixStrSQL($molecule_smiles_stereo[ $rc_rc_UID ]).";";
					
					//~ $sql_query[]="DELETE FROM retention_time WHERE ".
					//~ nvpUID($list_int_name,$UID,"analytics_method_id",SQL_NUM,true)." AND ".
					//~ nvpUID($list_int_name,$UID,"analytics_device_id",SQL_NUM,true)." AND ".
					//~ "smiles IS NOT NULL AND smiles=".fixStrSQL($molecule_smiles[ $rc_rc_UID ]).";";
					
					// the combination of analytics_method_id and molecule_id is set unique, no delete neccessary
					
					if (!empty($_REQUEST["gc_peak_retention_time_".$UID."_".$rc_UID])) { // retention_time=="0" deletes stored time, like for false entries
						// updaten, um Drift Rechnung zu tragen
						$sql_query[]="REPLACE retention_time SET ".
							nvpUID($list_int_name,$UID,"analytics_type_id",SQL_NUM).
							nvpUID($list_int_name,$UID,"analytics_device_id",SQL_NUM).
							nvpUID($list_int_name,$UID,"analytics_method_id",SQL_NUM).
							SQLgetChangeRecord("retention_time",$now).
							",retention_time=".fixNull($_REQUEST["gc_peak_retention_time_".$UID."_".$rc_UID]).
							",response_factor=".fixNull($_REQUEST["gc_peak_response_factor_".$UID."_".$rc_UID]).
							// hier rc_rc_UID nutzen, um Std mitzukriegen
							",molecule_id=".fixNull($molecule_ids[ $rc_rc_UID ]).
							",reaction_chemical_id=".fixNull($reaction_chemical_ids[ $rc_rc_UID ]).
							",smiles_stereo=".fixStrSQL($molecule_smiles_stereo[ $rc_rc_UID ]).
							",smiles=".fixStrSQL($molecule_smiles[ $rc_rc_UID ]).";";
						//~ print_r($sql_query);
					}
				}
			}
		}
		
		// Literatur
		$list_int_name="reaction_literature";
		if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
			switch(getDesiredAction($list_int_name,$UID)) {
			case "del":
				// do nothing
			break;
			case "add":
			case "update":
			default: // always insert references new, as all are deleted prior
				$sql_query[]="INSERT INTO ".$list_int_name." SET ".
					"reaction_id=".fixNull($pk).",".
					nvpUID($list_int_name,$UID,"literature_id",SQL_NUM,true).";"; // cmdINSERTsub
			}
		}
		
		$result=performQueries($sql_query,$dbObj);
		
		if ($result) {
			// update chemical_storage if technically possible
			$sql_query=performReactionOnInventory($db_id,$dbObj,$_REQUEST["reaction_id"],$_REQUEST["status"]);
			$result=performQueries($sql_query,$dbObj);
		}
		
		// Backup erstellen
		$list_int_name="analytical_data";
		if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
			if (getDesiredAction($list_int_name,$UID)=="add") {
				backupAnalyticalData(getValueUID($list_int_name,$UID,"analytical_data_id"));
			}
		}
	break;
	
	case "reaction_type":
		if (empty($_REQUEST["reaction_type_name"])) {
			return array(FAILURE,s("error_no_reaction_type_name"));
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$db); // cmdINSERT
		}
		$sql_query[]="UPDATE reaction_type SET ".
			nvp("reaction_type_name",SQL_TEXT).
			nvp("reaction_type_text",SQL_TEXT).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$db); // singleUpdate
	break;
	
	case "rent":
		if (empty($_REQUEST["item_identifier"])) {
			return array(FAILURE,s("error_no_item_identifier"));
		}
		
		if (empty($pk)) {
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
		}
		$sql_query[]="UPDATE rent SET ".
			nvp("item_identifier",SQL_TEXT).
			nvp("comment",SQL_TEXT).
			nvp("order_cost_centre_cp",SQL_TEXT).
			nvp("order_acc_no_cp",SQL_TEXT).
			nvp("price_per_day",SQL_NUM).
			nvp("price_per_day_currency",SQL_TEXT).
			nvp("vat_rate",SQL_NUM).
			nvp("start_date",SQL_DATE).
			nvp("end_date",SQL_DATE). // darf nicht vor start_date liegen
			nvp("billing_date",SQL_DATE,true).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$dbObj); // singleUpdate
	break;
	
	case "sci_journal":
		if (empty($_REQUEST["sci_journal_name"]) && empty($_REQUEST["sci_journal_abbrev"])) {
			return array(FAILURE,s("error_no_sci_journal_name"));
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
			$_REQUEST["sci_journal_id"]=$pk; // for adding literature afterwards
		}
		
		if (empty($_REQUEST["sci_journal_name"])) { // take the name as abbrev and viceversa if the other is empty
			$_REQUEST["sci_journal_name"]=$_REQUEST["sci_journal_abbrev"];
		}
		elseif (empty($_REQUEST["sci_journal_abbrev"])) {
			$_REQUEST["sci_journal_abbrev"]=$_REQUEST["sci_journal_name"];
		}
		
		$sql_query[]="UPDATE sci_journal SET ".
			nvp("sci_journal_name",SQL_TEXT).
			nvp("sci_journal_abbrev",SQL_TEXT).
			nvp("sci_journal_impact_factor",SQL_NUM).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$dbObj); // singleUpdate
	break;
	
	case "settlement":
		if (empty($pk)) {
			
			if (empty($_REQUEST["billing_date"]) || $_REQUEST["billing_date"]==invalidSQLDate) {
				$_REQUEST["billing_date"]=getSQLFormatDate($now);
			}
			
			if (empty($_REQUEST["to_date"]) || $_REQUEST["to_date"]==invalidSQLDate) {
				$_REQUEST["to_date"]=getSQLFormatDate($now);
			}
			
			addNvp($createArr,"currency",SQL_TEXT);
			addNvp($createArr,"billing_date",SQL_DATE);
			addNvp($createArr,"from_date",SQL_DATE);
			addNvp($createArr,"to_date",SQL_DATE);
			
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
			
			// prepare cost_centre limitation
			$cost_centre_text=" AND order_cost_centre_cp IN(".fixArrayListString($_REQUEST["cost_centre"]).")";
			
			$chemical_order_filter=
				" accepted_order.price_currency LIKE BINARY ".fixStrSQL($_REQUEST["currency"]).
				" AND accepted_order.central_order_status=\"central_delivered\"".
				" AND accepted_order.settlement_id IS NULL".
				$cost_centre_text;
			
			if (!empty($_REQUEST["lagerchemikalien"]) && !empty($own_data["institution_id"])) {
				// hier alle gelieferten chemikalien (central_delivered) für die Kostenstelle holen, die noch nicht abgerechnet sind
				$sql_query[]="UPDATE accepted_order SET ".
					"billing_date=FROM_UNIXTIME(".$now."),".
					"settlement_id=".fixNull($pk).
					" WHERE".
					" vendor_id=".fixNull($own_data["institution_id"]).
					" AND".$chemical_order_filter.
					";";
			}
			
			if (!empty($_REQUEST["sonderchemikalien"])) {
				// hier alle gelieferten chemikalien (central_delivered) für die Kostenstelle holen, die noch nicht abgerechnet sind
				if (!empty($own_data["institution_id"])) {
					$chemical_order_filter=" vendor_id!=".fixNull($own_data["institution_id"]).
						" AND".$chemical_order_filter;
				}
				
				$sql_query[]="UPDATE accepted_order SET ".
					"billing_date=FROM_UNIXTIME(".$now."),".
					"settlement_id=".fixNull($pk).
					" WHERE".
					$chemical_order_filter.
					";";
			}
			
			if (!empty($_REQUEST["rent_pl"])) {
				// hier alle Mietzeiträume holen, die in den Abrechnungszeitraum hineinreichen und noch nicht abgerechnet sind, ggf. in Teil-Zeiträume aufteilen
				$rent=mysql_select_array(array(
					"table" => "rent", 
					"filter" => "rent.settlement_id IS NULL 
						AND 
					rent.start_date<=".fixDateSQL($_REQUEST["to_date"])."  
						AND 
					rent.price_per_day_currency LIKE BINARY ".fixStrSQL($_REQUEST["currency"])."
						AND 
					(rent.end_date IS NULL OR rent.end_date=".fixDateSQL(invalidSQLDate)." OR rent.end_date>=".fixDateSQL($_REQUEST["from_date"]).")".
					$cost_centre_text, 
					"dbs" => -1, 
				));
				
				$day_start=getTimestampFromSQL($_REQUEST["from_date"]);
				$day_before_start=$day_start-daySec;
				$day_end=getTimestampFromSQL($_REQUEST["to_date"]);
				$day_after_end=$day_end+daySec;
				
				for ($a=0;$a<count($rent);$a++) {
					
					$setSQL="";

					
					// Zeitraum vorher?
					if ($rent[$a]["ts_start_date"]<$day_start) {
						$table2="rent";
						
						addNvpArray($rent[$a],$createArr,"item_identifier",SQL_TEXT);
						addNvpArray($rent[$a],$createArr,"comment",SQL_TEXT);
						addNvpArray($rent[$a],$createArr,"order_cost_centre_cp",SQL_TEXT);
						addNvpArray($rent[$a],$createArr,"order_acc_no_cp",SQL_TEXT);
						addNvpArray($rent[$a],$createArr,"price_per_day",SQL_NUM);
						addNvpArray($rent[$a],$createArr,"price_per_day_currency",SQL_TEXT);
						addNvpArray($rent[$a],$createArr,"vat_rate",SQL_NUM);
						addNvpArray($rent[$a],$createArr,"start_date",SQL_DATE);
						$createArr["end_date"]="FROM_UNIXTIME(".$day_before_start.")";
						
						$pk2=getInsertPk($table2,$createArr,$dbObj); // cmdINSERT
						addChangeNotify($db_id,$dbObj,$table2,$pk2);
						
						$setSQL.="start_date=FROM_UNIXTIME(".$day_start."),";
					}
					
					// Zeitraum danach?
					if ($rent[$a]["ts_end_date"]>$day_end || empty($rent[$a]["ts_end_date"]) || $rent[$a]["ts_end_date"]==invalidSQLDate) {
						$table2="rent";
						
						addNvpArray($rent[$a],$createArr,"item_identifier",SQL_TEXT);
						addNvpArray($rent[$a],$createArr,"comment",SQL_TEXT);
						addNvpArray($rent[$a],$createArr,"order_cost_centre_cp",SQL_TEXT);
						addNvpArray($rent[$a],$createArr,"order_acc_no_cp",SQL_TEXT);
						addNvpArray($rent[$a],$createArr,"price_per_day",SQL_NUM);
						addNvpArray($rent[$a],$createArr,"price_per_day_currency",SQL_TEXT);
						addNvpArray($rent[$a],$createArr,"vat_rate",SQL_NUM);
						addNvpArray($rent[$a],$createArr,"end_date",SQL_DATE);
						$createArr["start_date"]="FROM_UNIXTIME(".$day_after_end.")";
						
						$pk2=getInsertPk($table2,$createArr,$dbObj); // cmdINSERT
						addChangeNotify($db_id,$dbObj,$table2,$pk2);
						
						$setSQL.="end_date=FROM_UNIXTIME(".$day_end."),";
					}
					
					$sql_query[]="UPDATE rent SET ".
						$setSQL.
						"billing_date=FROM_UNIXTIME(".$now."),".
						"settlement_id=".fixNull($pk).
						getPkCondition("rent",$rent[$a]["rent_id"]).
						";"; // cmdINSERTsub
				}
			}
			
		}
		
		$sql_query[]="UPDATE settlement SET ".
			nvp("lagerpauschale",SQL_NUM,true).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$dbObj); // singleUpdate
		
	break;
	
	case "storage":
		if (empty($_REQUEST["storage_name"])) {
			return array(FAILURE,s("error_no_storage_name"));
		}
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
		}
		$sql_query[]="UPDATE storage SET ".
			nvp("storage_name",SQL_TEXT).
			nvp("storage_barcode",SQL_TEXT).
			nvp("poison_cabinet",SQL_NUM).
			nvp("institution_id",SQL_NUM).
			nvp("storage_secret",SQL_NUM).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$dbObj); // singleUpdate
	break;
	
	case "supplier_offer":
		if (empty($_REQUEST["supplier"])) {
			return array(FAILURE,s("error_no_supplier"));
		}
		if (empty($_REQUEST["catNo"]) && empty($_REQUEST["beautifulCatNo"])) {
			return array(FAILURE,s("error_no_catNo"));
		}
		elseif (empty($_REQUEST["catNo"])) {
			$_REQUEST["catNo"]=$_REQUEST["beautifulCatNo"];
		}
		elseif (empty($_REQUEST["beautifulCatNo"])) {
			$_REQUEST["beautifulCatNo"]=$_REQUEST["catNo"];
		}
		
		// handle molecule
		if ($_REQUEST["action_molecule"]=="add") {
			$_REQUEST["molecule_id"]=""; // molecule_id comes from foreign db, clear to create new dataset
		}
		if (empty($_REQUEST["molecule_id"]) || $_REQUEST["action_molecule"]=="update") { // add or edit molecule if necessary
			performEdit("molecule",$db_id,$dbObj);
		}
		
		if (empty($pk)) {
			$createArr=SQLgetCreateRecord($table,$now,true);
			$pk=getInsertPk($table,$createArr,$dbObj); // cmdINSERT
		}
		
		$sql_query[]="UPDATE supplier_offer SET ".
			nvp("molecule_id",SQL_NUM).
			nvp("supplier",SQL_TEXT).
			nvp("catNo",SQL_TEXT).
			nvp("beautifulCatNo",SQL_TEXT).
			nvp("so_package_amount",SQL_NUM).
			nvp("so_package_amount_unit",SQL_TEXT).
			nvp("so_purity",SQL_NUM). // remains in place here
			nvp("so_price",SQL_NUM).
			nvp("so_price_currency",SQL_TEXT).
			nvp("so_vat_rate",SQL_NUM).
			nvp("so_date",SQL_DATE).
			nvp("comment_supplier_offer",SQL_TEXT).
			SQLgetChangeRecord($table,$now).
			getPkCondition($table,$pk);
		
		$result=performQueries($sql_query,$dbObj); // singleUpdate
	break;
	
	}
	
	if ($_REQUEST["desired_action"]=="update") {
		$result=unlock($db_id,$dbObj,$table,$pk);
	}
	
	if (!is_array($pks_added)) {
		addChangeNotify($db_id,$dbObj,$table,$pk);
		if ($_REQUEST["desired_action"]=="add") {
			$pks_added=array($pk);
		}
	}
	
	if ($result) {
		if ($_REQUEST["desired_action"]=="add") {
			return array(SUCCESS,s("data_set_added"),$pks_added);
		}
		else {
			return array(SUCCESS,s("data_set_updated"));
		}
	}
	else {
		if ($_REQUEST["desired_action"]=="add") {
			return array(FAILURE,s("data_set_not_added"));
		}
		else {
			return array(FAILURE,s("data_set_not_updated"));
		}
	}
}

?>
