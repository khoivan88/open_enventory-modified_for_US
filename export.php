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
require_once "lib_output.php";

// table,cache_id,fields,output_type

// alles,selected, page
pageHeader(true,false); // kein login Fenster
setGlobalVars();

list($fields,$hidden)=getFields($columns[$table],$_REQUEST["fields"]);
$view_options=json_decode($_REQUEST["view_options"],true);

$paramHash=array(
	"output_type" => $_REQUEST["output_type"], 
	//~ "output_type" => "html", // debugging
	"separatorField" => "db_id", 
);
	
// query
list($res,$dataArray,$sort_hints)=handleQueryRequest(2,array("export" => $_REQUEST["output_type"]!="json", ));
$totalCount=& $dataArray["totalCount"];
$page=& $dataArray["page"];
$skip=& $dataArray["skip"];
$per_page=& $dataArray["per_page"];
$from_cache=& $dataArray["cache_active"];

/* send correct header
switch ($_REQUEST["output_type"]) {
case "xls":
	header("Content-type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
	header("Pragma: public");
break;
}*/

// build csv/xls
echo outputList($res,$fields,$paramHash);

// build tgz if necessary

?>