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

function showAnalyticsDeviceEditForm($paramHash) {
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"analytics_device");
	
	$retval=getFormElements($paramHash,array(
		"tableStart",
		array("item" => "check", "int_name" => "analytics_device_disabled"),
		array(
			"item" => "pk_select", 
			"text" => s("analytics_type_name"), 
			"table" => "analytics_type", 
			"int_name" => "analytics_type_id", 
			"nameField" => "analytics_type_name", 
			"allowNone" => false, 
			"order_obj" => getUserDefOrderObj("analytics_type"), 
		), // NMR, GC so on
		array("item" => "input", "int_name" => "analytics_device_name", "size" => 20, "maxlength" => 100), 
		array("item" => "input", "int_name" => "analytics_device_driver", "size" => 5, "maxlength" => 100), // filename
		array("item" => "input", "int_name" => "analytics_device_url", "size" => 40, "maxlength" => 200 ), 
		array("item" => "text", "int_name" => "url_info", "text" => s("url_info"), ), 
		array("item" => "text", "int_name" => "url_info", "text" => s("url_info1").localAnalyticsPath.s("url_info2"), ), 
		//array("item" => "input", "int_name" => "analytics_device_img_ext", "size" => 3, "strPost" => s("no_full_stop") ), 
		array("item" => "input", "int_name" => "analytics_device_username", "size" => 20), 
		array("item" => "input", "int_name" => "analytics_device_password", "size" => 20, "type" => "password", "strPost" => "<br>".s("warning_password")), 
		"tableEnd", 
		array("item" => "input", "int_name" => "analytics_device_text", "type" => "textarea", "rows" => 20, "cols" => 80)
	));
	
	return $retval;
}
?>