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
require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
require_once "lib_db_query.php";
require_once "lib_analytics.php";
require_once "File/Archive.php";

// SPZ= SPectrum Zipped

// add a file to the ZIP which contains all required information (especially PK) as serialised object and offer file for download

// Filename is (LabJ_code)_(nr)_(type_code)_(device_driver)_(nr)

pageHeader(true,false);


if (!empty($_REQUEST["analytical_data_id"])) {
	if ($_REQUEST["original"]) {
		$query_table="analytical_data_spz_orig";
	}
	else {
		$query_table="analytical_data_spz";
	}
	
	list($result)=mysql_select_array(array(
		"table" => $query_table, 
		"filter" => "analytical_data_id=".fixNull($_REQUEST["analytical_data_id"]), 
		"dbs" => -1, 
		"limit" => 1, 
	));
	
	if (!empty($result["analytical_data_link_url"])) {
		// redir to url and exit
		header("Location: ".$result["analytical_data_link_url"]);
		exit();
	}
	
	$metafile=".openenv";
	$metadata=array(
		"db_name" => $db_name, 
		"username" => $db_user, 
		"sessionId" => session_id(), 
		"userAgent" => getenv("HTTP_USER_AGENT"),
		"db_server" => $db_server, "uploadURL" => getSelfPath()."/uploadSpz.php",
		"analytical_data_id" => $result["analytical_data_id"], 
		"analytical_data_identifier" => $result["analytical_data_identifier"], 
		"analytics_type_code" => $result["analytics_type_code"], 
		"analytics_device_driver" => $result["analytics_device_driver"]); // data required for editing and upload
	
	if (defined("disableSPZ") && disableSPZ) {
		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=".$result["analytical_data_identifier"].".".compressFormat);
		getCompressHeader();
		
		echo $result["analytical_data_blob"];
	}
	else {
		$filename=$result["analytical_data_identifier"].".spz";
		
		// create ZIP for output
		//~ $dst_zip=File_Archive::toArchive(null,File_Archive::toOutput(),"zip");
		$dst_zip=File_Archive::toArchive(null,File_Archive::toOutput(),compressFormat);
		
		// read ZIP data
		$src_zip=getZipObj($result["analytical_data_blob"]);
		
		// add info file
		// $dst_zip->appendFile($metafile,$metadata);
		$dst_zip->newFile($metafile);
		$dst_zip->writeData("[Spectrum parameters]\r\n".getNameValuePairs($metadata)."\r\n[Object data]\r\n".serialize($metadata));
		
		if (strlen($_REQUEST["molfile_blob"])) { // live-Molfile
			$molfile=$_REQUEST["molfile_blob"];
		}
		elseif (strlen($result["molfile_blob"])) { // aus DB
			$molfile=$result["molfile_blob"];
		}
		elseif (!empty($result["reaction_id"])) { // take 1st product structure
			list($reaction_chemical)=mysql_select_array(array(
				"table" => "reaction_chemical_mol", 
				"filter" => "reaction_id=".fixNull($result["reaction_id"])." AND nr_in_reaction=1 AND role=\"product\"", 
				"dbs" => -1, 
			));
			$molfile=$reaction_chemical["molfile"];
		}
		
		if (isset($molfile)) {
			$molfile=removePipes($molfile);
			$dst_zip->newFile("molecule.mol");
			$dst_zip->writeData($molfile);
		}
		
		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=".$filename);
		getCompressHeader();
		
		// filter file $metafile and put rest of zip from db to output zip
		//~ echo $result["analytical_data_blob"];
		//~ File_Archive::extract(File_Archive::filter(File_Archive::predCustom('$name!='.fixStr($metafile)),$src_zip),$dst_zip);
		File_Archive::extract(File_Archive::filter(File_Archive::predCustom('!in_array($name,array('.fixStr($metafile).',"molecule.mol"))'),$src_zip),$dst_zip);
		// $src_zip->extract($dst_zip);
	}
}

?>