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
Sicherheitsdatenblatt anzeigen/herunterladen, das in der Datenbank (molecule_id=xyz oder 
chemical_storage_id=xyz) abgelegt wurde. Es wird der MIME-Typ verwendet, den die ursprüngliche Seite gesendet hat und der in der 
Datenbank hinterlegt ist. Als Dateiname wird der Teil nach dem letzten / in der URL vorgeschlagen
*/
require_once "lib_global_funcs.php";
require_once "lib_db_query.php";
require_once "lib_formatting.php";
$barcodeTerminal=true;
pageHeader(true,false);

require_once "MIME/Type/Extension.php";

@session_write_close();

$db_id=ifempty($_REQUEST["db_id"],-1);

$result=array();

if (!empty($_REQUEST["temp_file"]) && pathSafe($_REQUEST["temp_file"],"..")) { // file in temp directory
	$int_name="safety_sheet";
	$tmpdir=oe_get_temp_dir();
	$result[$int_name."_blob"]=file_get_contents($tmpdir."/".$_REQUEST["temp_file"]);
	if (isPDF($result[$int_name."_blob"])) {
		$result[$int_name."_mime"]="application/pdf";
	}
} else {
	if (!empty($_REQUEST["chemical_storage_id"])) {
		list($result)=mysql_select_array(array(
			"table" => "chemical_storage_safety_sheet", 
			"filter" => "chemical_storage_id=".fixNull($_REQUEST["chemical_storage_id"]), 
			"dbs" => $db_id, 
			"limit" => 1,
		));
		$int_name=ifempty($_REQUEST["int_name"],"safety_sheet");
	}

	if (empty($result[$int_name."_blob"]) && !empty($_REQUEST["molecule_id"])) {
		list($result)=mysql_select_array(array(
			"table" => "molecule_safety_sheet", 
			"filter" => "molecule_id=".fixNull($_REQUEST["molecule_id"]), 
			"dbs" => $db_id, 
			"limit" => 1,
		));
		$int_name=ifempty($_REQUEST["int_name"],"default_safety_sheet");
	}

	if (empty($result[$int_name."_blob"]) && !empty($_REQUEST["cas_nr"])) { // search all databases
		list($result)=mysql_select_array(array(
			"table" => "molecule_safety_sheet", 
			"filter" => "cas_nr=".fixStrSQL($_REQUEST["cas_nr"]), 
			//~ "dbs" => $db_id, 
			"limit" => 1,
		));
		$int_name=ifempty($_REQUEST["int_name"],"default_safety_sheet");
		if (empty($result[$int_name."_blob"])) {
			list($result)=mysql_select_array(array(
				"table" => "chemical_storage_safety_sheet", 
				"filter" => "cas_nr=".fixStrSQL($_REQUEST["cas_nr"]), 
				//~ "dbs" => $db_id, 
				"limit" => 1,
			));
			$int_name=ifempty($_REQUEST["int_name"],"safety_sheet");
			if (empty($result[$int_name."_blob"])) {
				// absolutely nothing found, redir to editWin
				//~ header("Location: ".getSelfPath()."/editWin.php?".getSelfRef(array("~script~"))."&mode=sds&readOnly=true&search=".$_REQUEST["cas_nr"]);
				header("Location: ".getSelfPath()."/searchExt.php?".getSelfRef(array("~script~"))."&supplier=all&query=<0>&crit0=molecule.cas_nr&op0=ex&val0=".$_REQUEST["cas_nr"]);
				exit();
			}
		}
	}
}


if (empty($result[$int_name."_blob"])) {
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Strict//EN\">
<html>
<head>
<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">
<link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"favicon.ico\" />
<link href=\"style.css.php\" rel=\"stylesheet\" type=\"text/css\">
</head>
<body>
".s("no_results")."
</body>
</html>
";
	exit();
}
else {
	$output=$result[$int_name."_blob"];
	$mime=$result[$int_name."_mime"];
}

completeDoc();

if ($_REQUEST["inline"]) {
	header(getHeaderFromMime($mime));
}
else {
	$filename=cutFilename($result[$int_name."_url"]);
	if (isEmptyStr($filename)) {
		$filename="MSDS";
	}
	
	// check if filename ends with proper extension
	$mime_type_lookup=new MIME_Type_Extension();
	$ext=$mime_type_lookup->getExtension($mime);
	if ($ext && is_string($ext) && !endswith($filename,".".$ext)) {
		// append
		$filename.=".".$ext;
	}
	$filename=str_replace(","," ",$filename); // Chrome comma fix
	
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Content-Type: ".$mime);
}

echo $output;
?>