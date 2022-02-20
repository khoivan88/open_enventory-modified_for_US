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

function getDefaultDataset($table,$skip_recursion=false) {
	global $person_id,$db_user,$own_data,$g_settings,$settings,$permissions,$permissions_list_value,$defaultCurrency;
	$retval=array();
	$retval["db_id"]=-1;
	
	switch ($table) {
	case "accepted_order":
		$retval["customer_order_date_cp"]=getSQLFormatDate();
		$retval["permanent_assignment"]=true;
		$retval["vat_rate"]=$g_settings["default_vat_rate"];
	break;
	case "chemical_order":
	case "my_chemical_order":
		$cost_centre=getDefaultCostCentre();
		$retval["ordered_by_person"]=$person_id;
		$retval["ordered_by_username"]=$db_user;
		$retval["customer_order_status"]=2;
		$retval["order_cost_centre"]=$cost_centre["cost_centre"];
		$retval["order_acc_no"]=$cost_centre["acc_no"];
	break;
	
	case "chemical_storage":
		if (!$skip_recursion) {
			$retval=getDefaultDataset("molecule",true);
		}
		$retval["molecule_id"]="";
		$retval["chemical_storage_conc_unit"]="%";
		$retval["amount_unit"]="g";
		$retval["tmd_unit"]="g";
		$retval["total_mass_unit"]="g";
		$retval["add_multiple"]="1";
		$retval["chemical_storage_secret"]=$g_settings["inventory_default_hidden"];
		if (($permissions & _chemical_edit)==0 && ($permissions & _chemical_edit_own)) { // no general permission
			$retval["owner_person_id"]=$person_id;
		}
	break;
	
	case "lab_journal":
		$retval["default_copy_target"]="";
		$retval["lab_journal_status"]=1;
	break; // set statically instead */
	
	case "literature":
		$retval["sci_journal_id"]="";
	break;
	
	case "message":
	case "message_out":
		$retval["from_person"]=$person_id;
		$retval["from_person_text"]=formatPersonNameCommas($own_data);
	break;
	
	case "molecule":
		if (!$skip_recursion) {
			$retval=getDefaultDataset("chemical_storage",true);
		}
		$retval["molecule_secret"]=$g_settings["inventory_default_hidden"];
	break;
	
	case "order_comp":
		$retval["central_cost_centre"]=$own_data["cost_centre"];
		$retval["fixed_costs_vat_rate"]=$g_settings["default_vat_rate"];
		$retval["comp_order_date"]=getSQLFormatDate();
	break;
	
	case "person":
		$retval["permissions"]=$permissions_list_value["write"];
		$retval["preferred_language"]=$g_settings["default_language"];
		$retval["cost_limit_currency"]=$defaultCurrency;
	break;
	
	case "reaction":
		$retval["additionalFields"]=array("reactants_rc_amount_unit","reactants_mass_unit","reactants_volume_unit","reactants_rc_conc_unit","products_rc_amount_unit","products_mass_unit");
		$retval["status"]="1";
		$retval["project_id"]=$settings["default_project"];
		$retval["reactants_rc_amount_unit"]="mmol";
		$retval["reactants_mass_unit"]="mg";
		$retval["reactants_volume_unit"]="ml";
		$retval["reactants_rc_conc_unit"]="%";
		$retval["products_rc_amount_unit"]="mmol";
		$retval["products_mass_unit"]="mg";
		$retval["reaction_carried_out_by"]=formatPersonNameNatural($own_data);
		$retval["reaction_started_when"]=getGermanDate();
	break;
	
	case "rent":
		$retval["vat_rate"]=$g_settings["default_vat_rate"];
		$retval["start_date"]=getSQLFormatDate();
	break;
	
	case "settlement":
		$retval["lagerpauschale"]=$g_settings["lagerpauschale"];
		$retval["sonderchemikalien"]=1;
		$retval["lagerchemikalien"]=1;
		$retval["rent_pl"]=1;
		$retval["billing_date"]=getSQLFormatDate();
	break;
	
	case "storage":
		$retval["storage_secret"]=1;
	break;
	
	case "supplier_offer":
		if (!$skip_recursion) {
			$retval=getDefaultDataset("molecule",true);
		}
		$retval["molecule_id"]="";
		$retval["so_vat_rate"]=$g_settings["default_vat_rate"];
		$retval["so_date"]=getSQLFormatDate();
	break;
	}
	return $retval;
}

?>