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
$_REQUEST["style"]="lj";
$_REQUEST["desired_action"]=ifempty($_REQUEST["desired_action"],"lab_journal");
$_REQUEST["table"]=ifempty($_REQUEST["table"],"lab_journal");

$sidenavCols="275,*,0";


switch ($_REQUEST["desired_action"]) {
case "edit":
	$main_url="edit.php?table=".$_REQUEST["table"]."&".keepAllParams(array("cached_query","~script~","table"));
	$_REQUEST["desired_action"]="lab_journal";
break;
case "list":
	$main_url="list.php?table=".$_REQUEST["table"]."&".keepAllParams(array("cached_query","~script~","table"));
	$_REQUEST["desired_action"]="lab_journal";
break;
case "search":
	$main_url="list.php?table=".$_REQUEST["table"]."&query=&dbs=-1&".getSelfRef(array("cached_query","dbs","~script~","table"));
break;
case "lab_journal":
default:
	$main_url=getLJstart();
}

echo "<title>".s("lab_journal_title")." ".$g_settings["organisation_name"]."
</title>".
script."
var fs_obj_orig=".fixStr($sidenavCols).";
"._script.
"</head>
<frameset cols=".fixStr($sidenavCols)." border=\"0\" id=\"sideframe\">
<frame src=\"sidenav.php?".getSelfRef(array("~script~")).
	"&desired_action=".$_REQUEST["desired_action"].
	"&table=".$_REQUEST["table"].
	"&person_id=".$person_id.
	"\" name=\"sidenav\" id=\"sidenav\" marginwidth=\"0\" marginheight=\"0\" frameborder=\"0\" noresize=\"noresize\">
<frameset rows=\"*,0\" id=\"lj\" border=\"1\"  bordercolor=\"".defBgColor."\">
<frame src=".fixStr($main_url)." name=\"mainpage\" id=\"mainpage\" marginwidth=\"0\" marginheight=\"0\" frameborder=\"0\">
<frame src=\"blank.php\" name=\"searchBottom\" marginwidth=\"0\" marginheight=\"0\" frameborder=\"0\">
</frameset>
<frame src=\"blank.php\" name=\"comm\" frameborder=\"0\" noresize=\"noresize\">
</frameset>
<noframes>
Ihr Browser unterstützt keine Frames. Your browser does not support frames.
</noframes></html>";
completeDoc();
?>