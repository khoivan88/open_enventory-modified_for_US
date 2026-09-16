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
Seite für dummy-Frame, 
*/
require_once "lib_global_funcs.php";
$page_type="async";
pageHeader();

//~ $settings["selection"][$table][$db_id]=array();
$table=$_REQUEST["table"];
$count_db_id=count($_REQUEST["db_id"]);

//~ session_start();
if ($_REQUEST["desired_action"]=="reset") {
	$settings["selection"][$table]=array();
}
elseif ($count_db_id && $count_db_id==count($_REQUEST["pk"])) {
	for ($a=0;$a<$count_db_id;$a++) {
		$db_id=$_REQUEST["db_id"][$a];
		switch ($_REQUEST["desired_action"]) {
			case "select":
				$settings["selection"][$table][$db_id][ $_REQUEST["pk"][$a] ]=true;
			break;
			case "unselect":
				unset($settings["selection"][$table][$db_id][ $_REQUEST["pk"][$a] ]);
			break;
		}
	}
}

echo "</head><body>";
//~ print_r($settings["selection"]);
echo "</body></html>";
saveUserSettings();
completeDoc();
?>