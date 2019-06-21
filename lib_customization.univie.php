<?php
/*
Copyright 2006-2016 Felix Rudolphi and Lukas Goossen
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

$default_g_settings=array();
$default_g_settings["organisation_name"]="Universität Wien";
//~ $default_g_settings["disable_login_lab_journal"]=true;
$default_g_settings["links_in_topnav"]=array(
	"uni_logo" => array(
		"url" => "http://www.univie.ac.at", 
		"target" => "_blank", 
		"src" => "lib/univie_logo.png", 
		"w" => "200", 
		"h" => "60", 
		"b" => "0", 
	), 
	"fb_logo" => array(
		"url" => "https://organicsynthesis.univie.ac.at/", 
		"target" => "_blank", 
		"src" => "lib/univie_maulide_logo.png", 
		"w" => "200", 
		"h" => "60", 
		"b" => "0", 
	), 
);

$default_db_name="numalab";

$g_settings=array_merge($g_settings,$default_g_settings);

?>