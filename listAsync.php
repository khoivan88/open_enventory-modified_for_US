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
require_once "lib_db_manip.php";
require_once "lib_formatting.php";
// print_r($_REQUEST);
$page_type="async";
pageHeader();
setGlobalVars();
$page_transparent_params=array("dbs",$pk_name,"fields","view_options","page","per_page","ref_cache_id");

echo script."if (parent && parent!=self && !opener) {
";

// schreiboperation ausführen
$mayWrite=mayWrite($baseTable);
if ($mayWrite[ $_REQUEST["db_id"] ]) { //  || ($baseTable=="message" && $_REQUEST["desired_action"]=="message_status")
	list($success,$message,$pks_added)=handleDesiredAction();
	// rückmeldung geben
}

switch ($_REQUEST["desired_action"]) {
case "confirm_order":
case "del":
	echo "parent.removeListLine(".fixNull($_REQUEST["idx"]).");\n";
break;
//~ case "del": // save time
case "set_order_status": // have to reload
	echo "parent.location.href=".fixStr("list.php?".getSelfRef(array("~script~","db_id","pk"))."&message=".strip_tags($message)).";\n";
break;
case "inventory":
	// update last check date and person
	list($result)=mysql_select_array(array(
		"table" => "chemical_storage_inventory", 
		"filter" => "chemical_storage_id=".fixNull($_REQUEST["pk"]), 
		"dbs" => "-1", 
		"limit" => 1, 
	));
	// assume that it worked, do not make full query with units etc
	$result["actual_amount"]=$_REQUEST["actual_amount"];
	$result["amount_unit"]=$_REQUEST["amount_unit"];
	echo "parent.displayInventory(".fixNull($_REQUEST["idx"]).",".json_encode($result).");";
break;
case "return_rent":
	// update icon
	if ($success==1) {
		echo "parent.hideObj(".fixStr("btn_return_".$_REQUEST["idx"]).");\n";
	}
break;
case "borrow":
	// update icon
	if ($success==1) {
		list($result)=mysql_select_array(array(
			"table" => "person_quick", 
			"filter" => "person_id=".fixNull($_REQUEST["borrowed_by_person_id"]), 
			"dbs" => "-1", 
			"limit" => 1, 
		));
		echo "parent.displayBorrow(".fixNull($_REQUEST["idx"]).",".
			intval(empty($_REQUEST["borrowed_by_person_id"])).",".
			fixStr(formatPersonNameCommas($result)).");\n";
	}
break;
case "undel":
	// update icon
	if ($success==1) {
		echo "parent.removeListLine(".fixNull($_REQUEST["idx"]).");\n";
	}
break;
case "message_status":
	// do nothing in fact
break;
}

echo "parent.showMessage(".fixStr($message).");
}

</script>
</head>
<body>
</body>
</html>";

completeDoc();
?>