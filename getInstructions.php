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

@session_write_close();

$db_id=ifempty($_REQUEST["db_id"],-1);

$result=array();

if (!empty($_REQUEST["molecule_instructions_id"])) {
	// direct
	list($result)=mysql_select_array(array(
		"table" => "molecule_instructions_download", 
		"filter" => "molecule_instructions_id=".fixNull($_REQUEST["molecule_instructions_id"]), 
		"dbs" => $db_id, 
		"limit" => 1,
	));
}
elseif (!empty($_REQUEST["molecule_id"])) {
	// for molecule and (possibly) lang
	$langToUse=ifempty($_REQUEST["lang"],$lang);
	list($result)=mysql_select_array(array(
		"table" => "molecule_instructions_download", 
		"filter" => "molecule_id=".fixNull($_REQUEST["molecule_id"])." AND NOT (lang IS NULL OR lang LIKE '')", 
		"dbs" => $db_id, 
		"limit" => 1,
		"order_obj" => array("field" => "lang LIKE ".fixStrSQL($langToUse), "order" => "DESC", "no_hints" => true), // always return at least something, even if the right language is not there
	));
}

if ($_REQUEST["inline"]) {
	header(getHeaderFromMime($mime));
}
else {
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=document.pdf"); // Chrome comma fix
	header("Content-Type: application/pdf");
}

echo $result["file_blob"];
?>