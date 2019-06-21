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
function showInstEditForm($paramHash) {
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"institution");
	
	$paramHash["checkSubmit"]=
		'if (getControlValue("'.$paramHash["prefix"].'institution_name")=="" && getControlValue("vendor_id")=="") { ' // if an existing institution is selected from the list, irrelevant for normal form
			.'alert("'.s("error_institution_name").'");'
			.'return false;'
		.'} ';
	
	
	
	$retval=getFormElements($paramHash,array(
		"tableStart", 
		array("item" => "input", "int_name" => "institution_name", "size" => 20,"maxlength" => 50, ), 
		array("item" => "input", "text" => s("person_name_institution"), "int_name" => "person_name", "size" => 20,"maxlength" => 50, ), 
		array("item" => "input", "int_name" => "department_name", "size" => 20,"maxlength" => 50, ), 
		array("item" => "input", "int_name" => "street", "size" => 20,"maxlength" => 50), 
		array("item" => "input", SPLITMODE => true, "int_name" => "street_number", "size" => 3,"maxlength" => 10), 
		array("item" => "input", "int_name" => "postcode", "text" => s("city"), "size" => 5,"maxlength" => 10), 
		array("item" => "input", SPLITMODE => true, "int_name" => "city", "size" => 20,"maxlength" => 50), 
		array("item" => "input", "int_name" => "country", "size" => 20,"maxlength" => 50), 
		array("item" => "input", "int_name" => "tel_no", "size" => 20,"maxlength" => 50), 
		array("item" => "input", "int_name" => "fax_no", "size" => 20,"maxlength" => 50), 
		array(
			"item" => "checkset", 
			"int_name" => "institution_type", 
			"int_names" => getValueList("institution","institution_type"), 
		), 
		
		array("item" => "input", "int_name" => "comment_institution", "type" => "textarea", "rows" => 2, "cols" => 20, ), 
		
		array(
			"item" => "input", 
			"int_name" => "institution_codes", 
			"type" => "textarea_classic", 
			"cols" => 20, 
			"rows" => 2, 
			"skip" => $paramHash["no_db_id_pk"], 
			"getValue" => "return getSupplierCodes(values); ", 
		), 
		array("item" => "input", "int_name" => "customer_id", "size" => 20,"maxlength" => 50), 
		"tableEnd", 

		// Liste der Bestellungen
		array(
			"item" => "subitemlist", 
			"int_name" => "order_comp", 
			DEFAULTREADONLY => "always", 
			"fields" => array(
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "comp_order_date", ),
				
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "purity", "type" => "percent", "size" => 3),
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "container", "size" => 8),
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "storage_name", "size" => 10),
				array("item" => "text", "value" => "&nbsp;"),
				array("item" => "input", "int_name" => "compartment", "size" => 3),
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "open_date", "type" => "date", "size" => 10),
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "expiry_date", "type" => "date", "size" => 10),
				array("item" => "cell"), 
				array("item" => "input", "int_name" => "comment_cheminstor", "type" => "textarea", "cols" => 30, "rows" => 2),
				array("item" => "cell"), 
				array(
					"item" => "js", 
					"int_name" => "detailbutton", 
					"functionBody" => "get_reference_link(\"order_comp\",values[\"db_id\"],values[\"order_comp_id\"]);", 
				), 
			) 
		), 
	));
	
	return $retval;
}
?>