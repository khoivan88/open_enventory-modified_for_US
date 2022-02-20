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
Zeigt ein GIF an, das a) in der Datenbank (molecule_id=xyz) oder in der Session[gifFile12345678] (timestamp=12345678) abgelegt wurde
oder (save=true) läßt es den Browser herunterladen und unter dem Namen filename=xyz speichern
*/

require_once "lib_global_funcs.php";
require_once "lib_db_query.php";
require_once "lib_formatting.php";
set_time_limit(10);

// hack for DYMO, allow session id via get parameter, only for this php
if (empty($_COOKIE[db_type]) && !empty($_REQUEST[db_type])) {
	$_COOKIE[db_type]=$_REQUEST[db_type];
}

$barcodeTerminal=true;
pageHeader(true,false,true,false);

$db_id=$_REQUEST["db_id"]+0;
if (empty($db_id)) {
	$db_id=-1;
}

if (!empty($_REQUEST["timestamp"])) {
	if ($_REQUEST["format"]=="svg" || (empty($_REQUEST["format"]) && $useSvg) ) {
		$output=$_SESSION["svgFile"][$_REQUEST["timestamp"]];
		$_REQUEST["format"]="svg";
	}
	else {
		$output=$_SESSION["gifFile"][$_REQUEST["timestamp"]];
		$_REQUEST["format"]=$analytics_img_params["format"];
	}
}
else {
	if (!empty($_REQUEST["molecule_id"]) && loginToDB(false)) {
		if ($_REQUEST["format"]=="svg" || (empty($_REQUEST["format"]) && $useSvg) ) {
			list($result)=mysql_select_array(array(
				"table" => "molecule_svg", 
				"filter" => "molecule_id=".fixNull($_REQUEST["molecule_id"]), 
				"dbs" => $db_id, 
				"limit" => 1, 
			));
			$_REQUEST["format"]="svg";
		}
		else {
			list($result)=mysql_select_array(array(
				"table" => "molecule_gif", 
				"filter" => "molecule_id=".fixNull($_REQUEST["molecule_id"]), 
				"dbs" => $db_id, 
				"limit" => 1, 
			));
			$_REQUEST["format"]=$analytics_img_params["format"];
		}
		mysqli_close($db);
	}
	elseif (!empty($_REQUEST["reaction_chemical_id"]) && loginToDB(false)) {
		if ($_REQUEST["format"]=="svg" || (empty($_REQUEST["format"]) && $useSvg) ) {
			list($result)=mysql_select_array(array(
				"table" => "reaction_chemical_svg", 
				"filter" => "reaction_chemical_id=".fixNull($_REQUEST["reaction_chemical_id"]), 
				"dbs" => $db_id, 
				"limit" => 1, 
			));
			$_REQUEST["format"]="svg";
		}
		else {
			list($result)=mysql_select_array(array(
				"table" => "reaction_chemical_gif", 
				"filter" => "reaction_chemical_id=".fixNull($_REQUEST["reaction_chemical_id"]), 
				"dbs" => $db_id, 
				"limit" => 1, 
			));
			$_REQUEST["format"]=$analytics_img_params["format"];
		}
		mysqli_close($db);
	}
	elseif (!empty($_REQUEST["reaction_id"]) && loginToDB(false)) {
		if ($_REQUEST["format"]=="svg" || (empty($_REQUEST["format"]) && $useSvg) ) {
			list($result)=mysql_select_array(array(
				"table" => "reaction_svg", 
				"filter" => "reaction_id=".fixNull($_REQUEST["reaction_id"]), 
				"dbs" => $db_id, 
				"limit" => 1, 
			));
			$_REQUEST["format"]="svg";
		}
		else {
			list($result)=mysql_select_array(array(
				"table" => "reaction_gif", 
				"filter" => "reaction_id=".fixNull($_REQUEST["reaction_id"]), 
				"dbs" => $db_id, 
				"limit" => 1, 
			));
			$_REQUEST["format"]=$analytics_img_params["format"];
		}
		mysqli_close($db);
	}
	elseif (!empty($_REQUEST["analytical_data_id"]) && loginToDB(false)) {
		if (empty($_REQUEST["image_no"])) {
			list($result)=mysql_select_array(array(
				"table" => "analytical_data_gif", 
				"filter" => "analytical_data_id=".fixNull($_REQUEST["analytical_data_id"]), 
				"dbs" => $db_id, 
				"limit" => 1, 
			));
		}
		else {
			list($result)=mysql_select_array(array(
				"table" => "analytical_data_image_gif", 
				"filter" => "analytical_data_id=".fixNull($_REQUEST["analytical_data_id"])." AND image_no=".fixNull($_REQUEST["image_no"]), 
				"dbs" => $db_id, 
				"limit" => 1, 
			));
		}
		
		$mime=$result["analytical_data_graphics_type"];
		mysqli_close($db);
	}
	elseif (!empty($_REQUEST["literature_id"]) && loginToDB(false)) {
		list($result)=mysql_select_array(array(
			"table" => "literature_gif", 
			"filter" => "literature_id=".fixNull($_REQUEST["literature_id"]), 
			"dbs" => $db_id, 
			"limit" => 1, 
		));
		$mime=$result["literature_graphics_type"];
		mysqli_close($db);
	}
	$output=$result["image"];
	$lastchanged=$result["last_changed"];
}

if (empty($output)) {
	$_REQUEST["format"]=$analytics_img_params["format"];
	$output=getEmptyImage($_REQUEST["format"]);
}

if (empty($mime)) {
	$mime=getMimeFromExt($_REQUEST["format"]);
}

if (empty($_REQUEST["save"])) { // display image in browser
	header(getHeaderFromMime($mime));
	
	// use cache if possible
	if (isset($lastchanged)) {
		header("Last-Modified: ".gmdate("D, d M Y H:i:s",$lastchanged)." GMT");
	}
}
else { // download
	if (!empty($_REQUEST["filename"])) {
		$filename=$_REQUEST["filename"];
	}
	elseif (!empty($_REQUEST["molecule_id"])) {
		$filename=$_REQUEST["molecule_id"];
	}
	elseif (!empty($_REQUEST["timestamp"])) {
		$filename=$_REQUEST["timestamp"];
	}
	else {
		$filename="molecule";
	}
	$filename=fixSp(strip_tags($filename).ifNotEmpty(".",$_REQUEST["format"]) );

	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	if (!strpos(getenv("HTTP_USER_AGENT"),"MSIE")) { // IE bug, always the same sh*t
		header(getHeaderFromMime($mime));
	}
}

echo $output;
?>