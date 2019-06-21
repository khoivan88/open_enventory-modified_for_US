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
Läßt es den Browser ein Molfile herunterladen, das a) in der Datenbank (molecule_id=xyz) oder in der Session[molFile12345678] (timestamp=12345678) abgelegt wurde, um es unter dem Namen filename=xyz zu speichern
*/

require_once "lib_global_funcs.php";
require_once "lib_db_query.php";

pageHeader(true,false);

if (!empty($_REQUEST["literature_id"])) {
	$db_id=intval($_REQUEST["db_id"]);
	if (empty($db_id)) {
		$db_id=-1;
	}

	list($result)=mysql_select_array(array(
		"table" => "literature_pdf", 
		"filter" => "literature.literature_id=".fixNull($_REQUEST["literature_id"]), 
		"dbs" => $db_id, 
		"limit" => 1, 
		"flags" => QUERY_EDIT, 
	));
	
	if (!empty($_REQUEST["filename"])) {
		$filename=$_REQUEST["filename"];
	}
	else {
		$filename=getCitation($result,0,true);
	}
	$filename=fixSp(strip_tags($filename).".pdf");

	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Content-Type: ".$result["literature_mime"]);

	echo $result["literature_blob"];
}
?>