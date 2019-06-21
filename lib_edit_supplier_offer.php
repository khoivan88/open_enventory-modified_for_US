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

function showSupplierOfferEditForm($paramHash) {
	global $defaultCurrency,$price_currency_list;
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"supplier_offer");
	
	$paramHash["checkSubmit"]=
	'if (getControlValue("so_package_amount")=="") { '
		.'alert("'.s("error_amount").'");'
		.'focusInput("so_package_amount"); '
		.'return false;'
	.'} ';
	
	$retval.=getFormElements($paramHash,array(
		array("item" => "hidden", "int_name" => "action_molecule"), 
		array(
			"item" => "pk", 
			"text" => "", 
			"class" => "formTitle", 
			"int_name" => "molecule_id", 
			"table" => "molecule", 
			"setNoneText" => s("new_molecule"), 
			"allowNone" => true, 
			"setValues" => 
				'var newMol=(a(selected_values,"molecule_id")==""),otherDb=(a(selected_values,"db_id")!="-1"); '
				.'if (otherDb) { '
					.'selected_values["molecule_id"]="";  '
				.'} '
				.'if (init==true) {'
					.'if (newMol || otherDb) { ' // Möglichkeit für Änderungen
						.'readOnlyForm("molecule",false); '
					.'}'
					.'else { ' // nur PK
						.'readOnlyForm("molecule",true); '
					.'}'
					.'delete selected_values["db_id"]; '
					.'resetAlreadyLoaded(); '
					.'setControlValues(selected_values,false); '
				.'} '
				.'if (newMol) { '
					.'return '.fixStr(s("new_molecule")).'; '
				.'} '
				.'return a(selected_values,"molecule_name");'
		),

		"tableStart", 
		array("item" => "input", "int_name" => "supplier", "size" => 10,"maxlength" => 20, ), 
		array("item" => "input", "int_name" => "beautifulCatNo", "size" => 10,"maxlength" => 20, ), 
		array("item" => "input", "int_name" => "catNo", "size" => 10,"maxlength" => 20, ), 

		array("item" => "input", "int_name" => "so_package_amount", "size" => 5,"maxlength" => 20, "doEval" => true, "noAutoComp" => true, ), 
		array(
			"item" => "pk_select", 
			SPLITMODE => true, 
			"int_name" => "so_package_amount_unit", 
			"pkName" => "unit_name", 
			"dbs" => "-1", 
			"table" => "units", 
			"nameField" => "unit_name", 
			"filterDisabled" => true, 
			"filter" => "unit_type IN(\"m\",\"v\")", 
			"setValues" => 
				'return a(selected_values,"unit_name");', 
			"defValue" => "g", 
		), 

		array("item" => "input", "int_name" => "so_purity", "size" => 6, "maxlength" => 10, "type" => "percent", ), 

		array("item" => "input", "int_name" => "so_price", "size" => 10,"maxlength" => 8, "doEval" => true, "noAutoComp" => true, ), 
		array("item" => "select", SPLITMODE => true, "int_name" => "so_price_currency", "int_names" => $price_currency_list, "texts" => $price_currency_list, "defVal" => $defaultCurrency, ), 

		array("item" => "input", "int_name" => "so_vat_rate", "size" => 2, "doEval" => true, "type" => "percent", ), 
		array("item" => "input", "int_name" => "so_date", "size" => 10,"maxlength" => 10,"type" => "date", ), 

		"tableEnd", 
		
		array("item" => "input", "int_name" => "comment_supplier_offer", "type" => "textarea", "cols" => 40, "rows" => 4, ), 
	));
	
	return $retval;
}
?>