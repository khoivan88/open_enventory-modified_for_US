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
function showChemicalOrderForm($paramHash) {
	global $editMode,$result,$permissions,$person_id,$price_currency_list,$db_user,$g_settings;
	$paramHash["int_name"]=ifempty($paramHash["int_name"],"chemical_order");
	$packageAmountUnits=array("g","ml","kg","l","Packungen","mg",);
	$paramHash["checkSubmit"]=
		'if (getControlValue("order_cost_centre")=="") { '.
			'alert("'.s("error_no_cost_centre").'");'.
			'focusInput("order_cost_centre_rounded"); '.
			'return false;'.
		'} '.
		'if (getControlValue("may_change_supplier")==3 && radioButtonValue("order_alternative_customer_selected_alternative_id")==undefined) { '
			.'alert("'.s("error_must_make_choice").'");'
			.'return false;'
		.'} ';
	
	if ($g_settings["order_system"]=="fundp") {
		$paramHash["checkSubmit"].=
			'if (getControlValue("stock_verifie")=="") { '.
				'alert("'.s("error_stock_verifie").'");'.
				'return false;'.
			'} ';
	}
	
	if (!$editMode) {
		$paramHash["onLoad"]="SILmanualAddLine(\"order_alternative\"); ";
	}
	
	$paramHash[DEFAULTREADONLY]=(($permissions & _order_order+_admin) && !$paramHash["no_db_id_pk"]?"":"always"); // also disable dynamically if !_order_approve or ordered_by_person!=$person_id
	
	$paramHash["setControlValues"]=
		'var customer_order_status=values["customer_order_status"]; '.
		'visibleObj("btn_confirm_order",customer_order_status==2); '.
		'visibleObj("accept_order",customer_order_status==3); '.
		'visibleObj("btn_goto_chemical_storage",values["chemical_storage_id"]); '.
		'var int_name="selected_alternative_id"; '. // chosen by order manager
		'if (values[int_name]==undefined) { '.
			'var int_name="customer_selected_alternative_id"; '. // chosen by customer
		'} '.
		'if (values[int_name]!=undefined) { '.
			'var selected_alternative_UID=SILfindValue("order_alternative","order_alternative_id",undefined,values[int_name]); '.
			'selectRadioButton("ro_order_alternative_customer_selected_alternative_id",selected_alternative_UID); '.
			'selectRadioButton("order_alternative_customer_selected_alternative_id",selected_alternative_UID); '.
		'} ';
	
	// get supplier list
	$vendor_paramHash=getVendors();
	$vendor_paramHash["item"]="input";
	$vendor_paramHash["type"]="combo";
	$vendor_paramHash["int_name"]="supplier";
	$vendor_paramHash["size"]=10;
	
	if ($permissions & _order_approve) {
		$avail_order_status=range(1,3);
	}
	elseif ($permissions & _order_order) {
		$avail_order_status=range(1,2);
	}
	else {
		$avail_order_status=range(1,1);
	}
	
	if (!$editMode && $g_settings["order_system"]=="fundp") {
		if (is_array($result[0]["order_alternative"])) foreach ($result[0]["order_alternative"] as $order_alternative) {
			$cas_nr=$order_alternative["cas_nr"];
			if (!empty($cas_nr)) {
				break;
			}
		}
		
		// not found, try with catNo
		if (empty($cas_nr) && is_array($result[0]["order_alternative"])) foreach ($result[0]["order_alternative"] as $order_alternative) {
			if (function_exists($suppliers[$supplier]["getInfo"])) {
				$supplier_data=$suppliers[$supplier]["getInfo"]($catNo);
				$cas_nr=$supplier_data["cas_nr"];
			}
		}
		
		if (!empty($cas_nr)) {
			// check if cas_nr is available
			list($db_result)=mysql_select_array(array(
				"table" => "chemical_storage",
				"filter" => "cas_nr=".fixStrSQL($cas_nr)."", 
			));
			if (count($db_result)) {
				$result[0]["customer_comment"]="We still have ".count($db_result)." container(s) of this chemical, but I want to order it because [please specify]";
			}
		}
	}
	
	$customer_edit_default_lock=($paramHash["no_db_id_pk"]?"":"never");
	
	$retval=loadJS(array("chem_order.js"),"lib/").
		getFormElements($paramHash,array(
			"tableStart", 
			array("item" => "hidden", "int_name" => "ordered_by_person", ), 
			array("item" => $editMode?"input":"hidden", "int_name" => "ordered_by_username", "text" => s("ordered_by"), DEFAULTREADONLY => "always", ), 
			// show person as text Name, Vorname, Titel in own DB and username in others
			
			// show fields for name and CAS like in FUNDP paper form
			array("item" => "input", "int_name" => "molecule_name", "value" => $result[0]["order_alternative"][0]["name"], "skip" => $g_settings["order_system"]!="fundp"), 
			array("item" => "input", "int_name" => "emp_formula", "size" => 4, "value" => $result[0]["order_alternative"][0]["emp_formula"], "skip" => $g_settings["order_system"]!="fundp"), 
			array("item" => "input", "int_name" => "cas_nr", "size" => 4, "value" => $result[0]["order_alternative"][0]["cas_nr"], "skip" => $g_settings["order_system"]!="fundp"), 
			array("item" => "check", "int_name" => "stock_verifie", "skip" => $g_settings["order_system"]!="fundp"), 
			
			array("item" => "hidden", "int_name" => "order_uid", ), 
			
			array("item" => "input", "int_name" => "customer_order_date", "type" => "date", DEFAULTREADONLY => "always", ), 
			array(
				"item" => "select", 
				"int_name" => "customer_order_status", 
				"int_names" => $avail_order_status, 
				"langKeys" => getValueList("chemical_order","customer_order_status"), 
				"text" => s("order_status"), 
				"skip" => $g_settings["order_system"]=="fundp", 
			), 

			getCostCentreParamHash("order_cost_centre",-1,"order_acc_no"), 
			array("item" => "input", "int_name" => "order_acc_no", "size" => 4,"maxlength" => 20, "skip" => $g_settings["order_system"]=="fundp", ), 
			"tableEnd", 

			array(
				"item" => "subitemlist", 
				"int_name" => "order_alternative", 
				"noManualAdd" => $paramHash["no_db_id_pk"], // only select from alternatives or enter values directly
				"fields" => array(
					array("item" => "cell", ), 
					array(
						"item" => "radio", 
						"int_name" => "customer_selected_alternative_id", 
						DEFAULTLOCKED => $customer_edit_default_lock, // if form is opened by user or if may_change_supplier!=3 (but then affects database of order manager
						"skip" => $g_settings["order_system"]=="mpi_kofo", 
						"onChange" => ($paramHash["no_db_id_pk"]?"updateSelectedAlternative":""), 
					),
					array("item" => "hidden", "int_name" => "order_alternative_id", ),
					array("item" => "cell", "skip" => $g_settings["order_system"]=="mpi_kofo", ), 
					array("item" => "input", "int_name" => "name", "size" => 20, "skip" => $g_settings["order_system"]=="fundp", ),
					array("item" => "cell", "skip" => $g_settings["order_system"]=="fundp", ), 
					array("item" => "input", "int_name" => "cas_nr", "size" => 10, "skip" => $g_settings["order_system"]=="fundp", ),
					array("item" => "cell", "skip" => $g_settings["order_system"]=="fundp", ), 
					array("item" => "hidden", "int_name" => "catNo", ),
					array("item" => "input", "int_name" => "beautifulCatNo", "size" => 10, "onChange" => "autoSearch", ),
					array("item" => "cell", ), 
					
					$vendor_paramHash, 
					//~ array("item" => "input", "type" => "combo", "int_name" => "supplier", "int_names" => $supplierCodes, "texts" => $supplierNames, ), // select
					
					array("item" => "cell", ), 
					array("item" => "text", "value" => "<nobr>"), 
					array("item" => "input", "int_name" => "package_amount", "onChange" => "updatePackageAmount", "size" => 4, "doEval" => true, ),
					array("item" => "text", "value" => "&nbsp;"), 
					//~ array("item" => "input", "int_name" => "package_amount_unit", "onChange" => "updatePackageAmount", "size" => 2, ),
					array("item" => "input", "type" => "combo", "int_name" => "package_amount_unit", "int_names" => $packageAmountUnits, "texts" => $packageAmountUnits, "onChange" => "updatePackageAmount", "size" => 2, ),
					array("item" => "text", "value" => "</nobr>"), 
					array("item" => "cell", ), 
					array("item" => "input", "int_name" => "price", "size" => 4, "onChange" => "updateTotal", "doEval" => true, ),
					array("item" => "text", "value" => "&nbsp;"), 
					array("item" => "select", "int_name" => "price_currency", "int_names" => $price_currency_list, "texts" => $price_currency_list, "onChange" => "updateTotal", ), // select
					array("item" => "cell", ), 
					array("item" => "input", "int_name" => "number_packages", "size" => 2, "doEval" => true, "evalFunction" => "evalNumberPackages", "onChange" => "updateTotal", DEFAULTLOCKED => $customer_edit_default_lock, ),
					array("item" => "text", "text" => " ", "headline" => "/", ), 
					array(
						"item" => "input", 
						"int_name" => "number_packages_text", 
						DEFAULTREADONLY => "always", 
						"handleDisplay" => 'return ifnotempty("(",displayValue,")"); ', 
					), 
					array("item" => "hidden", "int_name" => "density_20", ),
					array("item" => "cell", ), 
					array("item" => "input", "int_name" => "vat_rate", "size" => 2, DEFAULTLOCKED => $customer_edit_default_lock, "defaultValue" => $g_settings["default_vat_rate"], "doEval" => true, ),
					array("item" => "cell", "class" => "numeric"), 
					array("item" => "js", "int_name" => "total_price","functionBody" => "getTotalPrice(values[\"number_packages\"],values[\"price\"],values[\"price_currency\"]);", ), 
					array("item" => "cell", ), 
					array("item" => "button", "onClick" => "searchSupplierOffer", "class" => "imgButtonSm", "img" => "lib/search_sm.png", "hideReadOnly" => true),
					array("item" => "links", ), 
				),
			), 
			"br", 
			array(
				"item" => "select", 
				"int_name" => "may_change_supplier", 
				"langKeys" => getValueList("chemical_order","may_change_supplier"), 
			), 
			"br", 
			array("item" => "check", "int_name" => "chemical_order_secret", "skip" => $g_settings["order_system"]=="fundp", ), 
			"br", 
			array("item" => "input", "int_name" => "customer_comment", "type" => "textarea", ), 
			// _order_order: only customer_planning and customer_ordered, 
			// _order_approve: auch customer_confirmed
		));
	
	return $retval;	
}
?>