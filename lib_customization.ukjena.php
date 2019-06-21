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

$default_g_settings=array();
$default_g_settings["organisation_name"]="Universitätsklinikum Jena";
$default_g_settings["disable_login_lab_journal"]=true;
$default_g_settings["links_in_topnav"]=array(
	"uni_logo" => array(
		"url" => "http://www.uni-kl.de", 
		"target" => "_blank", 
		"src" => "lib/uni-logo.png", 
		"w" => "206", 
		"h" => "56", 
		"b" => "0", 
	), 
	"fb_logo" => array(
		"url" => "http://www.chemie.uni-kl.de/fachrichtungen/oc", 
		"target" => "_blank", 
		"src" => "lib/chemielogo.gif", 
		"w" => "192", 
		"h" => "64", 
		"b" => "0", 
	), 
);

$g_settings=array_merge($g_settings,$default_g_settings);

?>