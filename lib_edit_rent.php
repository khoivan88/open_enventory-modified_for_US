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
function showRentEditForm($paramHash) {
	global $price_currency_list,$defaultCurrency;
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"rent");
	
	$paramHash["setControlValues"]=
		'visibleObj("btn_return_rent",values["end_date"]=='.fixStr(invalidSQLDate).'); '.
		'visibleObj("btn_goto_settlement",values["settlement_id"]); ';
	
	$paramHash["checkSubmit"]=
		'if (getControlValue("item_identifier")=="") { '
			.'alert("'.s("error_no_item_identifier").'");'
			.'focusInput("item_identifier"); '
			.'return false;'
		.'} '.
		'if (getControlValue("start_date")=="") { '
			.'alert("'.s("error_no_start_date").'");'
			.'focusInput("start_date"); '
			.'return false;'
		.'} '.
		'if (getControlValue("order_cost_centre_cp")=="") { '
			.'alert("'.s("error_no_cost_centre").'");'
			.'focusInput("cost_centre"); '
			.'return false;'
		.'} ';
	
	$retval=getFormElements($paramHash,array(
		"tableStart",
		array("item" => "input", "int_name" => "item_identifier", "size" => 20, "maxlength" => 100, ), 
		array("item" => "input", "int_name" => "start_date", "type" => "date", ), 
		array("item" => "input", "int_name" => "end_date", "type" => "date", ), 

		getCostCentreParamHash("order_cost_centre_cp","","order_acc_no_cp",s("order_cost_centre")), 
		array("item" => "input", "int_name" => "order_acc_no_cp", "text" => s("order_acc_no"), "size" => 5,"maxlength" => 20), 

		array("item" => "input", "int_name" => "price_per_day", "size" => 10,"maxlength" => 8, "noAutoComp" => true, "doEval" => true, ), 
		array("item" => "select", SPLITMODE => true, "int_name" => "price_per_day_currency", "int_names" => $price_currency_list, "texts" => $price_currency_list, "defVal" => $defaultCurrency, ), 
		array("item" => "input", "int_name" => "vat_rate", "size" => 2, "doEval" => true, ), 
		array("item" => "input", "int_name" => "billing_date", DEFAULTREADONLY => "always", "type" => "date", ), 

		array("item" => "input", "int_name" => "comment", "type" => "textarea", "rows" => 20, "cols" => 80, ), 
		"tableEnd",
	));
	// showMultiselect for assigned Persons
	return $retval;
}
?>