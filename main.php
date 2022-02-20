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
Hauptseite mit Frameset
*/
require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
$page_type="frame";
pageHeader();
$_REQUEST["style"]="";
	
$sidenavCols="340,*";

echo "<title>".s("list_of_chemicals_title")." ".$g_settings["organisation_name"]."
</title>".
script."
var fs_obj_orig=".fixStr($sidenavCols).";
"._script.
"</head>
<frameset cols=".fixStr($sidenavCols)." border=\"0\" id=\"sideframe\">
	<frame src=\"sidenav.php?".getSelfRef(array("~script~")).
		"&desired_action=".ifempty($_REQUEST["desired_action"],"search").
		"&table=".ifempty($_REQUEST["table"],"chemical_storage").
		"\" name=\"sidenav\" id=\"sidenav\" marginwidth=\"0\" marginheight=\"0\" noresize frameborder=\"0\">
	<frameset rows=\"155,0,*\" border=\"0\">
		<frame src=\"topnav.php?".getSelfRef(array("~script~"))."\" name=\"topnav\" marginwidth=\"0\" marginheight=\"0\" noresize frameborder=\"0\">
		<frame src=\"blank.php\" name=\"comm\" marginwidth=\"0\" marginheight=\"0\" noresize frameborder=\"0\">
		<frame src=\"list.php?table=message_in&query=&dbs=-1&".getSelfRef(array("cached_query","dbs","~script~","table"))."\" name=\"mainpage\" id=\"mainpage\" marginwidth=\"0\" marginheight=\"0\" frameborder=\"0\">
	</frameset> 
</frameset>
<noframes>
Ihr Browser unterstützt keine Frames. Your browser does not support frames.
</noframes></html>";
completeDoc();
?>