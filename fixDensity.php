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
require_once "lib_formatting.php";
require_once "lib_db_manip.php";
require_once "lib_supplier_scraping.php";
require_once "lib_import.php";

pageHeader();

$results=mysql_select_array(array(
	"table" => "molecule", 
	"dbs" => "-1", 
	"flags" => 1, 
	"filter" => "density_20>10", 
));

for ($a=0;$a<count($results);$a++) {
	unset($results[$a]["density_20"]);
	getAddInfo($results[$a],false,array("min_number" => 2)); // Daten von suppliern holen, kann dauern
	//~ print_r($results[$a]);die();
	
	$oldReq=$_REQUEST;
	$_REQUEST=array_merge($_REQUEST,$results[$a]);
	$list_int_name="molecule_property";
	if (is_array($results[$a][$list_int_name])) foreach ($results[$a][$list_int_name] as $UID => $property) {
		$_REQUEST[$list_int_name][]=$UID;
		$_REQUEST["desired_action_".$list_int_name."_".$UID]="add";
		$_REQUEST[$list_int_name."_class_".$UID]=$property["class"];
		$_REQUEST[$list_int_name."_source_".$UID]=$property["source"];
		$_REQUEST[$list_int_name."_conditions_".$UID]=$property["conditions"];
		$_REQUEST[$list_int_name."_value_low_".$UID]=$property["value_low"];
		$_REQUEST[$list_int_name."_value_high_".$UID]=$property["value_high"];
		$_REQUEST[$list_int_name."_unit_".$UID]=$property["unit"];
	}
	
	performEdit("molecule",-1,$db,array("ignoreLock" => true, ));
	$_REQUEST=$oldReq;
}
?>