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

function getDefaultGlobalSettings() {
	global $default_g_settings;
	$retval=array(
		"organisation_name" => "TU Kaiserslautern",
		"no_win_open_on_start" => true,
		"border_w_mm" => 30,
		"border_h_mm" => 60,

		"links_in_topnav" => array(
			"uni_logo" => array(
				"url" => "https://sourceforge.net/projects/enventory/", 
				"target" => "_blank", 
				"src" => "lib/open_env_logo.png", 
				"w" => "240", 
				"h" => "", 
				"b" => "0", 
			), 
			"fb_logo" => array(
				"url" => "http://www.chemie.uni-kl.de/fachrichtungen/oc", 
				"target" => "_blank", 
				"src" => "lib/chemielogo.gif", 
				"w" => "192", 
				"h" => "64", 
				"b" => "0", 
			), 
		),
		"views" => array(
			"molecule" => array(
				"view_standard" => "", 
				"view_physical" => "structure,molecule_name,emp_formula_short,mw,density_20,mp_short,bp_short,n_20,links_mol", 
				"view_safety" => "structure,molecule_name,safety_sym,safety_text,safety_data_sheet,safety_r_s,safety_class,safety_danger,safety_other,bp_short,links_mol"
			), 
			"chemical_storage" => array(
				"view_standard" => "", 
				"view_inventory" => "structure,molecule_name,safety_sym_short,cas_nr,migrate_id_cheminstor,amount,inventarisation,chemical_storage_barcode,storage,expiry_date,links_chem", 
				"view_safety" => "structure,molecule_name,safety_sym,safety_text,safety_data_sheet,safety_r_s,safety_class,safety_danger,safety_other,bp_short,links_chem",
				"view_physical" => "structure,molecule_name,emp_formula_short,mw,density_20,mp_short,bp_short,n_20,amount,storage,links_chem", 
			),
		), 
		"customAnalyticsTabs" => array(
			array("key" => "gc", "analytics_type_name" => "gc", "text" => "GC", "showRxn" => true, "mixtureOnly" => true, ), 
			array("key" => "nmr", "analytics_type_name" => "nmr", "text" => "NMR", "showRxn" => false, "mixtureOnly" => false, ), 
		), 
		
		"applet_code" => "VectorMol",
		"applet_rxn_code" => "VectorMol",
		
		"safety_sheet_lang" => "de", 
		"alt_safety_sheet_lang" => "en", 
		
		"show_rc_stoch" => 1, 
		"supplier_order" => array(
			array("code" => "VWR"), 
			array("code" => "TCI", ), 
			array("code" => "Acros", ), 
			array("code" => "Sial", ), 
			array("code" => "Strem", ), 
			array("code" => "NIST", ), 
		), 
	);
	if (isset($default_g_settings)) {
		$retval=array_merge($retval,$default_g_settings);
	}
	return $retval;
}

function getDefaultUserSettings() {
	return array(
		"no_win_open_on_start" => 1, 
		"default_login_target" => "inventory", 
		
	);
}
?>
