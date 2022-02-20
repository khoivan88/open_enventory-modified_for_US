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
function showCostCentreEditForm($paramHash) {
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"cost_centre");
	
	$paramHash["checkSubmit"]=
		'if (getControlValue("cost_centre")=="") { '
			.'alert("'.s("error_no_cost_centre").'");'
			.'focusInput("cost_centre"); '
			.'return false;'
		.'} ';
	
	$retval=getFormElements($paramHash,array(
		"tableStart",
		array("item" => "input", "int_name" => "cost_centre", "size" => 10, "maxlength" => 20, ), 
		array("item" => "input", "int_name" => "acc_no", "size" => 5, "maxlength" => 20, ), 
		array("item" => "input", "int_name" => "cost_centre_name", "size" => 20, "maxlength" => 50, ), 
		array("item" => "check", "int_name" => "cost_centre_secret"), 
		"tableEnd",
	));
	return $retval;
}
?>