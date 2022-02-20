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
require_once "lib_root_funcs.php";

pageHeader();

$version=getGVar("Version");
$continue_URL=$loginTargets["inventory"]."&".getSelfRef(array("~script~"));

if ($db_user!=ROOT) {
	displayFatalError("permission_denied");
}

echo "<link href=\"style.css.php\" rel=\"stylesheet\" type=\"text/css\"></head>
<body><h1>".s("update")."</h1>";

$sql_query=getUpdateSQL($version);

if (empty($_REQUEST["update"])) { // preview
	updateCurrentDatabaseFormat();
	echo "<pre>".print_r($sql_query,true)."</pre>";
	
	echo s("update_info1").$version.s("update_info2").currentVersion.s("update_info3")."<br><a href=".fixStr(getSelfRef()."&update=true").">".s("perform_update")."</a> <a href=".fixStr($continue_URL).">".s("skip_update")."</a>";
}
else {
	updateCurrentDatabaseFormat(true);
	
	echo "<pre>".print_r($sql_query,true)."</pre>";
	$result=performQueries($sql_query,$db);
	
	updateFrom($version);
	refreshUsers();
	echo "<a href=".fixStr($continue_URL).">".s("continue")."</a>";
}

echo "</body></html>";
?>