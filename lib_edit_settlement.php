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
function showSettlementEditForm($paramHash) {
	global $editMode,$price_currency_list;
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"settlement");
	
	if ($editMode) {
		$defaultReadOnly="always";
	}
	else {
		$paramHash["onLoad"]="selectAllOptions(\"cost_centre\");";
	}
	
	$paramHash["checkSubmit"].=
		'if (getSelectSelectedCount("cost_centre")==0) { '.
			'alert("'.s("error_no_cost_centre_selected").'");'.
			'return false;'.
		'} '.
		'return true;';
	
	$retval=loadJS(array("chem_order.js"),"lib/").
	getFormElements($paramHash,array(
		"tableStart",
		array("item" => "input", "int_name" => "billing_date", "type" => "date", DEFAULTREADONLY =>  $defaultReadOnly, ), 
		array("item" => "input", "int_name" => "from_date", "type" => "date", DEFAULTREADONLY =>  $defaultReadOnly, ), 
		array("item" => "input", "int_name" => "to_date", "type" => "date", DEFAULTREADONLY =>  $defaultReadOnly, ), 
		array("item" => "select", "int_name" => "currency", "int_names" => $price_currency_list, "texts" => $price_currency_list, "defaultValue" => $defaultCurrency, DEFAULTREADONLY =>  $defaultReadOnly, ), // wir müssen für alles die gleiche Währung fordern!! Datensätze in einer anderen Währung werden ausgeblendet und DÜRFEN NICHT bestellt werden!!
		
		// die nachfolgenden Optionen stehen nur bei Neuerstellung zur Verfügung
		array("item" => "check", "int_name" => "sonderchemikalien", "skip" => $editMode, ), 
		array("item" => "check", "int_name" => "lagerchemikalien", "skip" => $editMode, ), 
		array("item" => "check", "int_name" => "rent_pl", "skip" => $editMode, ), 
		
		array("item" => "input", "int_name" => "lagerpauschale", "type" => "percent", ), // select
		
		array("item" => "pk_select", "int_name" => "cost_centre", 
			"table" => "cost_centre", 
			"multiMode" => true, 
			//~ "dbs" => -1, 
			"dbs" => "", 
			"order_by" => getOrderObjFromKey("cost_centre","cost_centre"), 
			"pkName" => "cost_centre", 
			"nameField" => "cost_centre", 
			"skip" => $editMode, 
		), 
		
		"tableEnd",
		
		// Liste der Abrechnungsposten, readOnly mit Link zum entsprechenden Datensatz
		array(
			"item" => "subitemlist", 
			"int_name" => "accepted_order", 
			"text" => s("item_list"), 
			"skip" => !$editMode, DEFAULTREADONLY => "always", 
			"fields" => array(
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "name", ), 
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "cas_nr", ), 
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "beautifulCatNo", ), 
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "v_institution_name", "text" => s("supplier"), ), 
				array("item" => "hidden", "int_name" => "supplier", ), 
				array("item" => "cell", ), 
				array("item" => "input", "int_name" => "package_amount", ),
				array("item" => "text", "value" => "&nbsp;"), 
				array("item" => "input", "int_name" => "package_amount_unit", ),
				
				array("item" => "cell", ), 
				array("item" => "input", "int_name" => "price", ),
				array("item" => "text", "value" => "&nbsp;"), 
				array("item" => "select", "int_name" => "price_currency", "int_names" => $price_currency_list, "texts" => $price_currency_list, ), // select
				array("item" => "cell", ), 
				array("item" => "input", "int_name" => "number_packages", ),
				array("item" => "cell", ), 
				array("item" => "input", "int_name" => "vat_rate", ),
				array("item" => "cell", "class" => "numeric"), 
				array("item" => "js", "int_name" => "total_price", "functionBody" => "getTotalPrice(values[\"number_packages\"],values[\"price\"],values[\"price_currency\"]);", ), 
				
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "order_date", "type" => "date", ), 
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "order_cost_centre_cp", "text" => s("order_cost_centre"), ), 
				array("item" => "text", "value" => "-"), 
				array("item" => "input", "int_name" => "order_acc_no_cp", ), 
				
				array("item" => "cell"), 
				array("item" => "js", "int_name" => "detailbutton", "functionBody" => "get_reference_link(\"chemical_order\",values[\"db_id\"],values[\"chemical_order_id\"]);", "class" => "noprint", ), 
			) 
		), 
		
		// Liste der Mietzeiträume
		array("item" => "subitemlist", "int_name" => "rent", "skip" => !$editMode, DEFAULTREADONLY => "always", 
			"fields" => array(
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "item_identifier", ), 
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "start_date", "type" => "date", ), 
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "end_date", "type" => "date", ), 
				
				array("item" => "cell", ), 
				array("item" => "input", "int_name" => "price_per_day", ),
				array("item" => "text", "value" => "&nbsp;"), 
				array("item" => "select", "int_name" => "price_per_day_currency", "int_names" => $price_currency_list, "texts" => $price_currency_list, ), // select
				array("item" => "cell", ), 
				array("item" => "input", "int_name" => "vat_rate", ),
				array("item" => "cell", "class" => "numeric"), 
				array("item" => "input", "int_name" => "grand_total_rent", "type" => "round", "roundMode" => money_round_mode, "decimals" => money_round_digits, ), 
				array("item" => "text", "value" => "&nbsp;"), 
				array("item" => "js", "int_name" => "price_per_day_currency_view", "functionBody" => "values[\"price_per_day_currency\"]; " ), // select
				
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "order_cost_centre_cp", "text" => s("order_cost_centre"), ), 
				array("item" => "text", "value" => "-"), 
				array("item" => "input", "int_name" => "order_acc_no_cp", ), 
				
				array("item" => "cell"), 
				array("item" => "js", "int_name" => "detailbutton", "functionBody" => "get_reference_link(\"rent\",values[\"db_id\"],values[\"rent_id\"]); ", "class" => "noprint", ), 
			) 
		), 

	));
// showMultiselect for assigned Persons
	return $retval;
}
?>