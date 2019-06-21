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
function showOrderCompForm($paramHash) {
	global $defaultCurrency,$price_currency_list,$permissions;
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"order_comp");
	
	if (($permissions & (_order_accept + _admin))==0) {
		return "";
	}
	
	$paramHash["change"][READONLY]=
		'visibleObj("btn_discount",!thisValue); ';
	
	$paramHash["setControlValues"]=
		'setAdressTable(values); '.
		'updateCurrencyOrder("accepted_order"); ';

	$retval=loadJS(array("chem_order.js"),"lib/").
		getFormElements($paramHash,array(
			array("item" => "hidden", "int_name" => "institution_id", ), // no change allowed at any time!!

			array("item" => "text", "text" => "<span id=\"addresses\"></span>"), // für Fensterumschlag

			array("item" => "input", "int_name" => "customer_id", DEFAULTREADONLY => "always"), 
			"br", 
			array("item" => "input", "int_name" => "comp_order_date", "size" => 10,"maxlength" => 20, "type" => "date"), 
			"br", 
			array("item" => "input", "type" => "combo", "int_name" => "order_way", "size" => 10, "int_names" => array("mail", "fax", "email", "internet", ), ), // save custom later
			"br", 
			array("item" => "input", "int_name" => "central_cost_centre", "size" => 10,"maxlength" => 7, ), 
			"br", 
			array("item" => "input", "int_name" => "kleinauftrag_nrn", "type" => "textarea_classic", "cols" => 40, "rows" => 4, "class" => "noprint", ), 
			"br", 
			array("item" => "input", "int_name" => "order_identifier", "size" => 10, ), 
			"br", 
			array("item" => "text", "text" => "<a id=\"btn_discount\" href=\"javascript:void askGlobalDiscount();\" class=\"imgButtonSm\"><img src=\"lib/discount_sm.png\" border=\"0\"".getTooltip("calc_discount")."></a>", ), 
			array("item" => "tableStart", TABLEMODE => "hl"), 
			array("item" => "input", "int_name" => "fixed_costs", "size" => 4,"maxlength" => 20, "onChange" => "updateTotalOrder(&quot;accepted_order&quot;); ", "doEval" => true, ), 
			array("item" => "select", "int_name" => "currency", SPLITMODE => true, "int_names" => $price_currency_list, "texts" => $price_currency_list, "defVal" => $defaultCurrency, "onChange" => "updateCurrencyOrder(&quot;chemical_order&quot;); "), // wir müssen für alles die gleiche Währung fordern!! Datensätze in einer anderen Währung werden ausgeblendet und DÜRFEN NICHT bestellt werden!!
			array("item" => "input", "int_name" => "fixed_costs_vat_rate", "size" => 4, "maxlength" => 20, "type" => "percent", "doEval" => true, ), 
			"tableEnd", 
			
			array("item" => "subitemlist", "int_name" => "accepted_order", "text" => "", "directDelete" => "true", "noManualAdd" => true,
				// sollen wir es zulassen, daß Zeilen manuell eingefügt werden??
				"fields" => array(
					array("item" => "cell"), 
					array("item" => "hidden", "int_name" => "accepted_order_id", ),
					array("item" => "hidden", "int_name" => "supplier_offer_id", ),
					array("item" => "input", "int_name" => "name", "size" => 20, ),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "cas_nr", "size" => 10, ),
					array("item" => "cell"), 
					array("item" => "hidden", "int_name" => "catNo"),
					array("item" => "input", "int_name" => "beautifulCatNo", "size" => 10, ),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "package_amount", "text" => s("package_amount_short"), "size" => 4, "doEval" => true, ),
					array("item" => "text", "value" => "&nbsp;"), 
					array("item" => "input", "int_name" => "package_amount_unit", "size" => 2, ),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "so_price", "text" => s("normal_price"), "size" => 4, "doEval" => true, ),
					array("item" => "input", "int_name" => "so_price_currency", DEFAULTREADONLY => "always", ), // only as info
					array("item" => "cell", ),
					array("item" => "input", "int_name" => "price", "size" => 4, "onChange" => "updateTotalOrder", "doEval" => true, ),
					array("item" => "text", "value" => "&nbsp;"), 
					array("item" => "select", "int_name" => "price_currency", "int_names" => $price_currency_list, "texts" => $price_currency_list, "onChange" => "updateCurrencyOrder", ), // select
					array("item" => "cell"), 
					array(
						"item" => "input", 
						"int_name" => "number_packages", 
						"size" => 2, 
						"doEval" => true, 
						"evalFunction" => "evalNumberPackages", 
						"type" => "round", 
						"roundMode" => order_system_round_mode, 
						"decimals" => order_system_round_digits, 
						"onChange" => "updateTotal", 
					),
					array("item" => "text", "text" => " ", "headline" => "/", ), 
					array(
						"item" => "input", 
						"int_name" => "number_packages_text", 
						DEFAULTREADONLY => "always", 
						"handleDisplay" => 'return ifnotempty("(",displayValue,")"); ', 
					), 
					array("item" => "hidden", "int_name" => "density_20", ),
					array("item" => "cell"), 
					array("item" => "input", "int_name" => "vat_rate", "text" => s("vat_short"), "size" => 2, "doEval" => true, ),
					array("item" => "cell", "class" => "noprint", ), 
					array("item" => "input", "int_name" => "ordered_by_username_cp", "text" => s("ordered_by"), DEFAULTREADONLY => "always", ), // lokal leer
					
					// Status
					
					array("item" => "cell", "class" => "numeric"), 
					array(
						"item" => "js", 
						"int_name" => "total_price", 
						"functionBody" => "getTotalPrice(values[\"number_packages\"],values[\"price\"],values[\"price_currency\"]);", 
					), 
					array("item" => "cell", "hideReadOnly" => true, ), 
					array("item" => "links", ) 
				),
			), 
			array("item" => "text", "text" => "<hr><div id=\"grand_total\" style=\"text-align:right\"></div>", ), 
		));
	
	return $retval;
}
?>