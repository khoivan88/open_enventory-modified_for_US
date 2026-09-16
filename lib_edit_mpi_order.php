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
function showMPIOrderForm($paramHash) {
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"mpi_order");
	
	if (!isOrderManager()) {
		return "";
	}
	
	$paramHash["change"][READONLY]=
		'showControl("btn_split",!thisValue); ';
	
	$retval=getFormElements($paramHash,array(
		"tableStart", 
		array(
			"item" => "pk_select", 
			"text" => s("workgroup_name"), 
			"int_name" => "other_db_id", 
			"dbs" => "-1", 
			"table" => "other_db", 
			"nameField" => "db_beauty_name", 
			"setValues" => 'return a(selected_values,"db_beauty_name");', 
		),  // select other_db
		array("item" => "input", "int_name" => "order_person", "size" => 10,), 
		array("item" => "input", "int_name" => "order_account", "size" => 10,), 
		array("item" => "input", "int_name" => "order_date", "type" => "date", "size" => 10,), 
		array("item" => "input", "int_name" => "delivery_date", "type" => "date", "size" => 10,), 
		array("item" => "input", "int_name" => "molecule_name", "size" => 10,), 
		array("item" => "input", "int_name" => "cas_nr", "size" => 10,), 
		array("item" => "input", "int_name" => "chemical_storage_conc", "size" => 5,), 
		array(
			"item" => "pk_select", 
			"int_name" => "chemical_storage_conc_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			SPLITMODE => true, 
			"filterDisabled" => true, 
			"filter" => "unit_type LIKE BINARY \"c\"", 
			"setValues" => 
				'return a(selected_values,"unit_name"); ', 
			"defValue" => "mol/l", 
		), 
		array("item" => "input", "int_name" => "chemical_storage_solvent", "size" => 10,), 
		array("item" => "input", "int_name" => "supplier", "size" => 10,), 
		array("item" => "input", "int_name" => "sap_bestell_nr", "size" => 8,), 
		array("item" => "input", "int_name" => "sap_stamm_nr", "size" => 8,), 
		array("item" => "input", "int_name" => "bessi", "size" => 5,), 
		array(
			"item" => "select", 
			"int_name" => "mpi_order_status", 
			"langKeys" => getValueList("mpi_order","mpi_order_status"), 
		), 
		array(
			"item" => "text", 
			"int_name" => "btn_split", 
			"text" => "<a href=\"Javascript:splitDelivery();\" class=\"imgButtonSm\"><img src=\"lib/split_chemical_sm.png\" border=\"0\"".getTooltip("split_chemical_storage")."></a>", 
		), 
		"tableEnd", 
		
		array("item" => "subitemlist", "int_name" => "mpi_order_item", 
			"fields" => array(
				// Liste Aufteilung
				array("item" => "cell", ), 
				array("item" => "hidden", "int_name" => "mpi_order_item_id", ), 
				array("item" => "input", "int_name" => "amount", "size" => 5, ), 
				array("item" => "text", "headline" => "/", "value" => " ", ), 
		
				array(
					"item" => "pk_select", 
					"int_name" => "amount_unit", 
					"pkName" => "unit_name", 
					"dbs" => "-1", 
					"table" => "units", 
					"nameField" => "unit_name", 
					"filterDisabled" => true, 
					"filter" => "unit_type IN(\"m\",\"v\")", 
					"setValues" => 
						'return a(selected_values,"unit_name");',
					"onChange" => "rxnValueChanged", 
					"class" => "small_input", 
				),
				//~ array("item" => "input", "int_name" => "amount_unit", "size" => 3, SPLITMODE => true, ), 
				array("item" => "cell", ), 
				array("item" => "input", "int_name" => "chemical_storage_barcode", "size" => 8,), 
				array("item" => "cell", "hideReadOnly" => true, ), 
				array("item" => "links", ), 
			), 
		), 
	));
	
	return $retval;
}
?>