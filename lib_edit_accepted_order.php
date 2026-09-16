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
function showAcceptedChemicalOrderForm($paramHash) { // editMode=false: direkteingabe der Ausgabe
	global $editMode,$permissions,$price_currency_list;
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"accepted_order");
	
	$mayWrite=mayWrite("accepted_order");
	
	$paramHash[DEFAULTREADONLY]=($mayWrite ?"":"always");
	$paramHash["setControlValues"]=
		'var central_order_status=values["central_order_status"]; '.
		'visibleObj("btn_supplier_delivered",central_order_status<3); '.
		'visibleObj("btn_customer_delivered",central_order_status<4); '.
		'visibleObj("btn_goto_settlement",values["settlement_id"]); '.
		'visibleObj("btn_add_package",!values["chemical_storage_id"]); ';
	
	$link_supplier_institution=(!$paramHash["accepted_order_multi"] && !$paramHash["no_db_id_pk"]);
	
	if ($link_supplier_institution) {
		$paramHash["change"][READONLY]=
			'if (thisValue==false) { '.
				'PkSelectUpdate("vendor_id"); '.
			'} ';
	
		$paramHash["setControlValues"]=
			'if (readOnly) { '.
				'readOnlyForm("v_institution",true); '.
				//~ 'showForm("v_institution",values["vendor_id"]!=undefined); '.
				'visibleObj("v_institution_FS",values["vendor_id"]!=undefined); '.
				'showControl("permanent_assignment",false); '.
			'} ';
	}
	
	$paramHash["checkSubmit"].=
		'if (getControlValue("ordered_by_username_cp")=="") { '.
			'alert("'.s("error_no_ordered_by").'");'.
			'focusInput("ordered_by_username_cp"); '.
			'return false;'.
		'} '.
		'if (getControlValue("order_cost_centre_cp")=="") { '.
			'alert("'.s("error_no_cost_centre").'");'.
			'focusInput("order_cost_centre_cp_rounded"); '.
			'return false;'.
		'} ';
	
	
	$fieldsArray=array(
		"tableStart", 
		array("item" => $paramHash["no_db_id_pk"]?"hidden":"input", "int_name" => "ordered_by_username_cp", "text" => s("ordered_by"), ), // wer
		array("item" => "input", "int_name" => "customer_order_date_cp", "type" => "date", DEFAULTREADONLY => "always", "text" => s("order_date"), ), 
		
		array("item" => "hidden", "int_name" => "order_uid_cp", ), 
		array("item" => "hidden", "int_name" => "selected_alternative_id", ), 
		
		array(
			"item" => "select", 
			"int_name" => "central_order_status", 
			"langKeys" => getValueList("accepted_order","central_order_status"), 
			"text" => s("order_status"), 
		), 
		
		getCostCentreParamHash("order_cost_centre_cp","","order_acc_no_cp",s("order_cost_centre")), 
		//~ array("item" => "input", "int_name" => "order_cost_centre_cp", "size" => 8,"maxlength" => 20, "text" => s("order_cost_centre"), ), // wer
		array("item" => "input", "int_name" => "order_acc_no_cp", "size" => 4,"maxlength" => 20, "text" => s("order_acc_no"), ), // wer
		
		// wo
		//~ array("item" => "select", "int_name" => "btn_lagerchem", "onChange" => "switchLager", "int_names" => array("lager","sonder", ), "langUseValues" => true, ), 
		//~ $vendor_paramHash, // do not mix when entering!!
		//~ array("item" => "input", "int_name" => "vat_rate", "size" => 2, ), 
		
		"tableEnd", 
	);
	
	$itemArray=array(
		 // was, nur bearbeitungsmodus
		array("item" => "cell", ), // must be 1st in list
		"tableStart", 
		array("item" => "hidden", "int_name" => "supplier_offer_id", ), // reference, allows update of prices
		array("item" => "input", "int_name" => "name", "size" => 20, ), 
		array("item" => "cell", ),
		array("item" => "input", "int_name" => "cas_nr", "size" => 10, ), 
		array("item" => "cell", ),
		array("item" => "hidden", "int_name" => "catNo", ),
		array("item" => "input", "int_name" => "beautifulCatNo", "size" => 10, "onChange" => "autoSearch", ),
		array("item" => "cell", ), 
		
		//~ $vendor_paramHash, 
		array("item" => $paramHash["accepted_order_multi"]?"hidden":"input", "int_name" => "supplier", "size" => 4, ), // piece of text coming from customer
		array( // entry in table institution with address etc
			"item" => "pk_select", 
			"text" => s("vendor"), 
			"int_name" => "vendor_id", 
			
			"table" => "vendor_for_accepted_order", // have v_ prefix!!
			"pkName" => "v_institution_id", 
			"getText" => 'return rowData["v_institution_name"];', 
			"nameField" => "v_institution_name", 
			"order_by" => "v_institution_name", 
			"dynamic" => !$paramHash["accepted_order_multi"], 
			//~ "dynamic" => true, 
			//~ "allowNone" => !$paramHash["accepted_order_multi"], // if no match for text field "supplier" is found, set to new and enter value from text field "supplier" as name and code. If changed to some other institution_id, add to institution_code unless checkbox "do_not_assign" is clicked
			"allowNone" => true, 
			//~ "noneText" => s("new_supplier"), 
			"noneText" => $paramHash["accepted_order_multi"]?s("do_select"):s("new_supplier"), 
			"onChange" => $link_supplier_institution?"updateSupplier(); ":"", 
		), 
		array("item" => "check", "int_name" => "permanent_assignment", "skip" => $paramHash["accepted_order_multi"], ), // assign text "supplier" to institution_id?
		
		array("item" => "cell", ), 
		array("item" => "input", "int_name" => "package_amount", "onChange" => "updatePackageAmount", "size" => 4, "doEval" => true, ), 
		array("item" => "input", "int_name" => "package_amount_unit", "onChange" => "updatePackageAmount", "size" => 2, SPLITMODE => true, ), 
		array("item" => "cell", ),
		array("item" => "input", "int_name" => "so_price", "text" => s("normal_price"), "size" => 4, "doEval" => true, ), 
		array("item" => "input", "int_name" => "so_price_currency", DEFAULTREADONLY => "always", SPLITMODE => true, ), // only as info
		array("item" => "cell", ),
		array("item" => "input", "int_name" => "price", "size" => 4, "onChange" => "updateTotal", "doEval" => true, ), 
		array("item" => "select", "int_name" => "price_currency", "int_names" => $price_currency_list, "texts" => $price_currency_list, "onChange" => "updateTotal", SPLITMODE => true, ), 
		array("item" => "cell", ), 
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
		array(
			"item" => "text", 
			"text" => " ", 
			"value" => " ", 
			"headline" => "/", 
			SPLITMODE => true, 
		), 
		array(
			"item" => "input", 
			"int_name" => "number_packages_text", 
			DEFAULTREADONLY => "always", 
			SPLITMODE => true, 
			"handleDisplay" => 'return ifnotempty("(",displayValue,")"); ', 
		), 
		array("item" => "hidden", "int_name" => "density_20", ),
		array("item" => "cell", ),
		array("item" => "input", "int_name" => "vat_rate", "size" => 2, "doEval" => true, ), 
		array("item" => "cell", ),
		array("item" => "js", "int_name" => "total_price", ), // for all packages
		array("item" => "cell", ),
		array("item" => "input", "int_name" => "central_comment","type" => "textarea", "rows" => 2, "cols" => 20, ), 
		array("item" => "cell", ),
		// button to open search window
		array("item" => "button", "onClick" => "searchSupplierOffer", "class" => "imgButtonSm", "img" => "lib/search_sm.png", "hideReadOnly" => true),
		array("item" => "links", ),
		"tableEnd", 
	);
	
	$paramHash["onLoad"]="";
	
	if ($link_supplier_institution) {
		$fieldsArray=arr_merge($fieldsArray,$itemArray);
		$paramHash["setControlValues"].="updateTotal(); setLockSelectAlternative(); updateSupplier(); ";
		// Protocol
		$fieldsArray[]=array("item" => "input", "int_name" => "history_entry", "loadBlind" => true, "size" => 35, );
		$fieldsArray[]="br";
		$fieldsArray[]=array(
			"item" => "input", 
			"int_name" => "history", 
			"type" => "textarea_classic", 
			DEFAULTREADONLY => "always", 
			"onMouseover" => "editHistory", 
			"onMouseout" => "editHideOverlay", 
			"handleDisplay" => 
				'return "<span class=\"print_only\">"+displayValue+"</span><span class=\"noprint\">"+strrcut(displayValue,200,undefined,"<br>")+"</span>";', 
		);
	}
	elseif ($paramHash["accepted_order_multi"]) { // add multiple items
		$fieldsArray[]=array(
			"item" => "subitemlist", 
			"int_name" => "item_list", 
			"addMultipleButtons" => array(5), 
			"fields" => $itemArray, 
			"setFunction" => "updateTotal(list_int_name,UID,\"\"); ", 
		);
		$paramHash["onLoad"].="SILmanualAddLineMultiple(8,\"item_list\"); ";
	}
	
	$retval=loadJS(array("chem_order.js"),"lib/").
		getFormElements($paramHash,$fieldsArray);
	
	return $retval;
}
?>