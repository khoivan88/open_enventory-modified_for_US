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
// Frameset anzeigen mit sidenav und main, suchen und auswählen von molekül und/oder gebinde

// transparenter parameter tableSelect=molecule,chemical_storage&forUID=xyz

require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
$page_type="frame";
pageHeader();
$selectTables=explode(",",$_REQUEST["tableSelect"]);

$sidenavCols="340,*,0";

if ($_REQUEST["desired_action"]=="lab_journal") {
	$editParam="&view=ergebnis";
}

if (!empty($_REQUEST["editDbId"]) && !empty($_REQUEST["editPk"])) { // Datensatz ist definiert, bearbeiten
	$query_string="";
	if ($_REQUEST["desired_action"]=="lab_journal") {
		// get lab_journal
		list($result)=mysql_select_array(array(
			"table" => "reaction", 
			"dbs" => $_REQUEST["editDbId"], 
			"filter" => "reaction.reaction_id=".fixNull($_REQUEST["editPk"]), 
			"limit" => 1, 
		));
		$query_string="<0>&crit0=lab_journal.lab_journal_id&op0=eq&val0=".$result["lab_journal_id"];
	}
	$url="edit.php?table=".$selectTables[0]."&edit=".$_REQUEST["edit"]."&query=".$query_string."&db_id=".$_REQUEST["editDbId"]."&pk=".$_REQUEST["editPk"]."&".getSelfRef(array("~script~","table")).$editParam;
}
elseif ($_REQUEST["desired_action"]=="lab_journal") { // Formular für neuen Datensatz anbieten
	$url=getLJstart().$editParam;
}
elseif ($_REQUEST["autoNew"]=="true") { // Formular für neuen Datensatz anbieten
	$url="edit.php?desired_action=new&table=".$selectTables[0]."&".getSelfRef(array("~script~","table"));
	switch ($selectTables[0]) {
	case "analytical_data":
		if (isset($_REQUEST["analytics_type_name"])) {
			$url.="&analytics_type_name=".$_REQUEST["analytics_type_name"];
		}
	break;
	}
}
else { // blabla
	$url="searchWinInfo.php?".getSelfRef(array("~script~"));
}

$sidenavParam="&desired_action=".ifempty($_REQUEST["desired_action"],"search")."&table=".ifempty($_REQUEST["table"],$selectTables[0]);

switch ($_REQUEST["tableSelect"]) {
case "reaction,reaction_chemical":
	$sidenavParam.="&person_id=".$person_id;
	$showSearchRxn=true;
break;
}

echo "<title>".s("list_of_chemicals_title")." ".$g_settings["organisation_name"]."
</title>".
script."
var fs_obj_orig=".fixStr($sidenavCols).";
"._script.
"</head>
<frameset cols=".fixStr($sidenavCols)." border=\"0\" id=\"sideframe\">
<frame src=\"sidenav.php?".getSelfRef(array("~script~","table")).$sidenavParam."\" name=\"sidenav\" id=\"sidenav\" marginwidth=\"0\" marginheight=\"0\" noresize frameborder=\"0\">";

if ($showSearchRxn) {
	echo "<frameset rows=\"*,0\" id=\"lj\" border=\"1\"  bordercolor=\"".defBgColor."\">";
}

echo "<frame src=".fixStr($url)." name=\"mainpage\" id=\"mainpage\" marginwidth=\"0\" marginheight=\"0\" frameborder=\"0\">";

if ($showSearchRxn) {
	echo "<frame src=\"blank.php\" name=\"searchBottom\" marginwidth=\"0\" marginheight=\"0\" frameborder=\"0\">
</frameset>";
}

echo "<frame src=\"blank.php\" name=\"comm\" marginwidth=\"0\" marginheight=\"0\" noresize frameborder=\"0\">
</frameset>
<noframes>
Ihr Browser unterstützt keine Frames. Your browser does not support frames.
</noframes></html>";
completeDoc();
?>