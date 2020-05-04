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

// 0= eingeblendet, ausblendbar, 1= standardmäßig ausgeblendet, 2= immer da (nicht ausblendbar), +4=nur programmgesteuert einblendbar

$columns["molecule"]=array(
	"structure" => DEFAULT_ON, 
	"molecule_name" => DEFAULT_ON+NO_OFF, 
	"emp_formula_short" => DEFAULT_ON, 
	"cas_nr" => DEFAULT_ON, 
	"molecule_type" => DEFAULT_OFF, 
	"safety_sym" => DEFAULT_ON, 
	"safety_sym_text" => DEFAULT_OFF, 
	"pos_neg" => DEFAULT_OFF, 
	"molecule_bilancing" => DEFAULT_OFF, 
	"safety_text" => DEFAULT_OFF, 
	"safety_data_sheet" => DEFAULT_OFF, 
	"safety_r_s" => DEFAULT_ON, 
	"safety_class" => DEFAULT_OFF, 
	"safety_danger" => DEFAULT_OFF, 
	"safety_cancer" => DEFAULT_OFF, 
	"safety_mutagen" => DEFAULT_OFF, 
	"safety_reprod" => DEFAULT_OFF, 
	"safety_other" => DEFAULT_OFF, 
	"smiles_stereo" => DEFAULT_OFF, 
	"mw" => DEFAULT_ON, 
	"migrate_id_mol" => DEFAULT_OFF, 
	"density_20" => DEFAULT_OFF, 
	"mp_short" => DEFAULT_OFF, 
	"bp_short" => DEFAULT_OFF, 
	"n_20" => DEFAULT_OFF, 
	"comment_mol" => DEFAULT_OFF, 
	"links_mol" => DEFAULT_ON, 
);

$columns["molecule_double"]=array(
	"structure" => DEFAULT_ON, 
	"molecule_name" => DEFAULT_ON+NO_OFF, 
	"emp_formula_short" => DEFAULT_ON, 
	"cas_nr" => DEFAULT_ON, 
	"molecule_type" => DEFAULT_OFF, 
	"safety_sym" => DEFAULT_ON, 
	"safety_sym_text" => DEFAULT_OFF, 
	"pos_neg" => DEFAULT_OFF, 
	"molecule_bilancing" => DEFAULT_OFF, 
	"safety_text" => DEFAULT_OFF, 
	"safety_data_sheet" => DEFAULT_OFF, 
	"safety_r_s" => DEFAULT_OFF, 
	"safety_class" => DEFAULT_OFF, 
	"safety_danger" => DEFAULT_OFF, 
	"safety_cancer" => DEFAULT_OFF, 
	"safety_mutagen" => DEFAULT_OFF, 
	"safety_reprod" => DEFAULT_OFF, 
	"safety_other" => DEFAULT_OFF, 
	"smiles_stereo" => DEFAULT_OFF, 
	"mw" => DEFAULT_ON, 
	"migrate_id_mol" => DEFAULT_OFF, 
	"density_20" => DEFAULT_OFF, 
	"mp_short" => DEFAULT_OFF, 
	"bp_short" => DEFAULT_OFF, 
	"n_20" => DEFAULT_OFF, 
	"comment_mol" => DEFAULT_OFF, 
);

$columns["chemical_storage"]=array(
	"structure" => DEFAULT_ON, 
	"molecule_name" => DEFAULT_ON+NO_OFF, 
	"emp_formula_short" => DEFAULT_ON, 
	"cas_nr" => DEFAULT_ON, 
	"chemical_storage_conc" => DEFAULT_OFF, 
	"molecule_type" => DEFAULT_OFF, 
	"chemical_storage_type" => DEFAULT_OFF, 
	"safety_sym" => DEFAULT_ON, 
	"safety_sym_text" => DEFAULT_OFF, 
	"pos_neg" => DEFAULT_OFF, 
	"chemical_storage_bilancing" => DEFAULT_OFF, 
	"molecule_bilancing" => DEFAULT_OFF, 
	"owner_person_id" => DEFAULT_ON, 
	"safety_text" => DEFAULT_OFF, 
	"safety_data_sheet" => DEFAULT_OFF, 
	"safety_r_s" => DEFAULT_ON, 
	"safety_class" => DEFAULT_OFF, 
	"safety_danger" => DEFAULT_OFF, 
	"safety_cancer" => DEFAULT_OFF, 
	"safety_mutagen" => DEFAULT_OFF, 
	"safety_reprod" => DEFAULT_OFF, 
	"safety_other" => DEFAULT_OFF, 
	"disposed_when" => DEFAULT_OFF, 
	"disposed_by" => DEFAULT_OFF, 
	"smiles_stereo" => DEFAULT_OFF, 
	"mw" => DEFAULT_OFF, 
	"migrate_id_mol" => DEFAULT_OFF, 
	"migrate_id_cheminstor" => DEFAULT_OFF, 
	"amount" => DEFAULT_ON, 
	"inventarisation" => DEFAULT_OFF, 
	"chemical_storage_barcode" => DEFAULT_OFF, 
	"lot_no" => DEFAULT_OFF, 
	//~ "purity" => DEFAULT_OFF, 
	"container" => DEFAULT_OFF, 
	"storage" => DEFAULT_ON, 
	"borrowed_by" => DEFAULT_OFF, 
	"order_date" => DEFAULT_OFF, 
	"open_date" => DEFAULT_OFF, 
	"expiry_date" => DEFAULT_OFF,
	"supplier" => DEFAULT_OFF, 
	"cat_no" => DEFAULT_OFF, 
	"price" => DEFAULT_OFF, 
	"chemical_storage_properties" => DEFAULT_OFF, 
	"density_20" => DEFAULT_OFF, 
	"mp_short" => DEFAULT_OFF, 
	"bp_short" => DEFAULT_OFF, 
	"n_20" => DEFAULT_OFF, 
	"comment_cheminstor" => DEFAULT_OFF, 
	"links_chem" => DEFAULT_ON, 
);
$columns["disposed_chemical_storage"]=$columns["chemical_storage"];
$columns["disposed_chemical_storage"]["disposed_when"]=DEFAULT_ON;
$columns["disposed_chemical_storage"]["disposed_by"]=DEFAULT_ON;
$columns["disposed_chemical_storage"]["emp_formula_short"]=DEFAULT_OFF;
$columns["disposed_chemical_storage"]["safety_sym"]=DEFAULT_OFF;
$columns["disposed_chemical_storage"]["safety_r_s"]=DEFAULT_OFF;
$columns["disposed_chemical_storage"]["owner_person_id"]=DEFAULT_OFF;
$columns["disposed_chemical_storage"]["comment_cheminstor"]=DEFAULT_ON;
$columns["disposed_chemical_storage"]["chemical_storage_barcode"]=DEFAULT_ON;

$columns["analytical_data"]=array(
	"reaction_name" => DEFAULT_ON, 
	"rxn_structure" => DEFAULT_OFF, 
	"standard_name" => DEFAULT_ON, 
	"rc_structure" => DEFAULT_OFF, 
	"analytical_data_image" => DEFAULT_OFF, 
	"analytical_data_identifier" => DEFAULT_OFF, 
	"analytics_type_name" => DEFAULT_ON, 
	"analytics_device_name" => DEFAULT_OFF, 
	"analytics_method_name" => DEFAULT_ON, 
	"measured_by" => DEFAULT_OFF, 
	"links_analytical_data" => DEFAULT_ON+NO_OFF, 
); // , "analytics_image" => DEFAULT_ON+NO_OFF

//~ $columns["message_person"]=array("person_name","completion_status");

$columns["project"]=array(
	"project_name" => DEFAULT_ON+NO_OFF, 
	"project_created_when" => DEFAULT_OFF, 
	"project_text" => DEFAULT_OFF, 
	"links_project" => DEFAULT_ON+NO_OFF, 
); // links_project zeigt personenzahl und reaktionszahl

$columns["my_projects"]=& $columns["project"];

$columns["reaction"]=array(
	"lab_journal_entry" => DEFAULT_ON+NO_OFF, 
	"rxn_structure" => DEFAULT_ON, 
	"rxn_structure_text" => DEFAULT_OFF, // Name + Name => Name
	"yield" => array(
		"display" => DEFAULT_ON, 
		"col_name" => "products", 
		"column_options" => "yield", 
	), 
	"gc_yield" => array(
		"display" => DEFAULT_ON, 
		"col_name" => "products", 
		"column_options" => "yield", 
	), 
	"ref_amount" => DEFAULT_OFF,
	"remaining_reactants" => array(
		"display" => DEFAULT_OFF, 
		"col_name" => "reactants", 
		"column_options" => "remaining_reactants", 
	), 
	"reaction_conditions" => array(
		"display" => DEFAULT_OFF, 
		"int_names" => $reaction_conditions, // remove the disabled ones
	),
	"reactant" => array(
		"multiple" => 9, 
		"useLetter" => true, 
		"display" => DEFAULT_OFF, 
		"col_name" => "reactants", 
		"column_options" => "reaction_chemical", 
	),
	"reagent" =>  array(
		"multiple" => 2, 
		"prefix" => "R", 
		"display" => DEFAULT_OFF, 
		"col_name" => "reagents", 
		"column_options" => "reaction_chemical", 
	),
	"product" =>  array(
		"multiple" => 3, 
		"display" => DEFAULT_OFF, 
		"col_name" => "products", 
		"column_options" => "reaction_chemical", 
	),
	"realization_text" => DEFAULT_OFF, 
	"realization_observation" => DEFAULT_OFF, 
	"reaction_title" => DEFAULT_OFF, 
	"reaction_carried_out_by" => DEFAULT_OFF, 
	"reaction_started_when" => DEFAULT_OFF, 
	"reaction_project" => DEFAULT_OFF, 
	"reaction_analytics" => DEFAULT_OFF, 
	"reaction_status" => DEFAULT_OFF, 
	"compare_rxn" => DEFAULT_OFF+NO_OFF+NO_ON, 
	"links_reaction" => DEFAULT_ON+NO_OFF, 
);

$column_options["reaction_chemical"]=array(
	"per_col" => true,
	"fields" => array(
		"molfile_blob" => array(), 
		"molfile_blob_full" => array("defaultHide" => true, ), 
		"cas_nr" => array(), 
		"standard_name" => array(), 
		"m_brutto" => array("defaultHide" => true, ), 
		"stoch_coeff" => array(), 
		"rc_amount" => array("langKey" => "rc_amount_text", "defaultHide" => true, ), 
		"volume" => array("defaultHide" => true, ), 
	),
);

$column_options["yield"]=array(
	"per_col" => true,
	"fields" => array(
		"yield.0" => array(), 
		"yield.1" => array(), // "defaultHide" => true, 
		"yield.2" => array(), // "defaultHide" => true, 
		"yield.3" => array("defaultHide" => true, ), 
		"yield.4" => array("defaultHide" => true, ), 
		"diagram" => array(), 
		"ratio" => array("defaultHide" => true, ), 
		"ee-de" => array("defaultHide" => true, ), 
	),
);

$column_options["remaining_reactants"]=array(
	"per_col" => true,
	"fields" => array(
		"remaining.0" => array(), 
		"remaining.1" => array(), 
		"remaining.2" => array(), 
		"diagram" => array(), 
	),
);

//~ $columns["my_reactions"]=& $columns["reaction"];
//~ $columns["my_open_reactions"]=& $columns["reaction"];
//~ $columns["my_persons_reactions"]=$columns["reaction"];
//~ $columns["my_persons_reactions"]["reaction_carried_out_by"]=DEFAULT_ON;
//~ $columns["my_persons_open_reactions"]=$columns["reaction"];
//~ $columns["my_persons_open_reactions"]["reaction_carried_out_by"]=DEFAULT_ON;

$columns["reaction_type"]=array(
	"reaction_type_name" => DEFAULT_ON+NO_OFF, 
	"links_reaction_type" => DEFAULT_ON+NO_OFF
);

$columns["chemical_storage_type"]=array(
	"chemical_storage_type_name" => DEFAULT_ON+NO_OFF, 
	"links_chemical_storage_type" => DEFAULT_ON+NO_OFF
);

$columns["molecule_type"]=array(
	"molecule_type_name" => DEFAULT_ON+NO_OFF, 
	"links_molecule_type" => DEFAULT_ON+NO_OFF
);

$columns["analytics_type"]=array(
	"analytics_type_name" => DEFAULT_ON+NO_OFF, 
	"analytics_type_code" => DEFAULT_OFF, 
	"links_analytics_type" => DEFAULT_ON+NO_OFF
);

$columns["analytics_method"]=array(
	"analytics_method_name" => DEFAULT_ON+NO_OFF, 
	"analytics_type_name" => DEFAULT_ON, 
	"analytics_device_name" => DEFAULT_ON, 
	"analytics_method_text" => DEFAULT_ON, 
	"links_analytics_method" => DEFAULT_ON+NO_OFF, 
);

$columns["analytics_device"]=array(
	"analytics_device_name" => DEFAULT_ON+NO_OFF, 
	"analytics_device_driver" => DEFAULT_OFF, 
	"analytics_device_url" => DEFAULT_ON, 
	"links_analytics_device" => DEFAULT_ON+NO_OFF, 
);

$columns["lab_journal"]=array(
	"lab_journal_code" => DEFAULT_ON+NO_OFF, 
	"lab_journal_status" => DEFAULT_ON, 
	"person_name" => DEFAULT_OFF, 
	"links_lab_journal" => DEFAULT_ON+NO_OFF, 
); // "project_name" => DEFAULT_ON, 

$columns["my_lab_journals"]=& $columns["lab_journal"];

$columns["message"]=array(
	"from_person" => DEFAULT_ON+NO_OFF, 
	"issued" => DEFAULT_ON, 
	"message_subject" => DEFAULT_ON, 
	"message_text" => DEFAULT_OFF, 
	"do_until" => DEFAULT_ON, 
	"completion_status_out" => DEFAULT_ON, 
	"links_message" => DEFAULT_ON+NO_OFF, 
);

$columns["message_in"]=array(
	"from_person" => DEFAULT_ON+NO_OFF, 
	"to_persons" => DEFAULT_ON, 
	"issued" => DEFAULT_ON, 
	"message_subject" => DEFAULT_ON, 
	"message_text" => DEFAULT_OFF, 
	"do_until" => DEFAULT_ON, 
	"completion_status_in" => DEFAULT_ON+NO_OFF, 
	"links_message_in" => DEFAULT_ON+NO_OFF, 
);

$columns["message_out"]=array(
	"to_persons" => DEFAULT_ON+NO_OFF, 
	"issued" => DEFAULT_ON, 
	"message_subject" => DEFAULT_ON, 
	"message_text" => DEFAULT_OFF, 
	"do_until" => DEFAULT_ON, 
	"completion_status_out" => DEFAULT_ON+NO_OFF, 
	"links_message_out" => DEFAULT_ON+NO_OFF, 
);

$columns["person"]=array(
	"person_name" => DEFAULT_ON+NO_OFF, 
	// "person_institution" => DEFAULT_ON+NO_OFF, 
	"person_institution" => DEFAULT_ON, 
	"username" => DEFAULT_ON+NO_OFF, 
	"permissions" => DEFAULT_ON+NO_OFF, 
	"person_barcode" => DEFAULT_ON, 
	"links_person" => DEFAULT_ON+NO_OFF, 
);

$columns["other_db"]=array(
	"db_beauty_name" => DEFAULT_ON+NO_OFF, 
	"host" => DEFAULT_ON+NO_OFF, 
	"db_name" => DEFAULT_ON+NO_OFF, 
	"db_user" => DEFAULT_ON+NO_OFF, 
	"links_other_db" => DEFAULT_ON+NO_OFF, 
);

$columns["institution"]=array(
	"institution_name" => DEFAULT_ON+NO_OFF, 
	"street" => DEFAULT_ON+NO_OFF, 
	"city" => DEFAULT_ON+NO_OFF, 
	"links_institution" => DEFAULT_ON+NO_OFF, 
);
$columns["vendor"]=$columns["institution"];
$columns["vendor_with_open"]=$columns["vendor"];
//~ $columns["vendor"]["links_vendor"]=DEFAULT_ON+NO_OFF;
//~ $columns["institution"]["links_institution"]=DEFAULT_ON+NO_OFF;

$columns["storage"]=array(
	"storage_name" => DEFAULT_ON+NO_OFF, 
	// "institution" => DEFAULT_ON+NO_OFF, 
	"institution" => DEFAULT_ON,  // Khoi: make option to turn this column off
	"storage_barcode" => DEFAULT_ON, // Khoi: turn on storage barcode column
	"links_storage" => DEFAULT_ON+NO_OFF, 
);

$columns["sci_journal"]=array(
	"sci_journal_name" => DEFAULT_ON+NO_OFF, 
	"sci_journal_abbrev" => DEFAULT_ON+NO_OFF, 
	"links_sci_journal" => DEFAULT_ON+NO_OFF, 
);

$columns["literature"]=array(
	"literature_citation" => DEFAULT_ON+NO_OFF, 
	"literature_title" => DEFAULT_ON, 
	"keywords" => DEFAULT_OFF, 
	"doi" => DEFAULT_ON, 
	"links_literature" => DEFAULT_ON+NO_OFF, 
);

$columns["literature_for_reaction"]=array(
	"literature_citation" => DEFAULT_ON+NO_OFF, 
	"keywords" => DEFAULT_ON+NO_OFF, 
	"doi" => DEFAULT_ON+NO_OFF, 
	"links_literature_for_reaction" => DEFAULT_ON+NO_OFF, 
);

$columns["literature_for_project"]=array(
	"literature_citation" => DEFAULT_ON+NO_OFF, 
	"keywords" => DEFAULT_ON+NO_OFF, 
	"doi" => DEFAULT_ON+NO_OFF, 
	"links_literature_for_project" => DEFAULT_ON+NO_OFF, 
);

// order system
$columns["chemical_order"]=array( // alle Bestellungen (filtern nach offen/abholbereit/abgeholt/abgerechnet) für Kontrolle
	"customer_order_date" => DEFAULT_ON, 
	"ordered_by" => DEFAULT_ON, 
	"order_alternative" => DEFAULT_ON+NO_OFF, 
	"cas_nr" => DEFAULT_OFF, 
	"package_amount" => DEFAULT_OFF, 
	"supplier" => DEFAULT_OFF, 
	"beautifulCatNo" => DEFAULT_OFF, 
	"price" => DEFAULT_OFF, 
	"order_cost_centre" => DEFAULT_OFF, 
	"customer_comment" => DEFAULT_OFF, 
	"accepted_order_created_by" => DEFAULT_ON, 
	"central_comment" => DEFAULT_ON, 
	"order_status" => DEFAULT_ON, 
	"supplier_delivery_date" => DEFAULT_OFF, 
	"customer_delivery_date" => DEFAULT_OFF, 
	"billing_date" => DEFAULT_OFF, 
	//~ "links_chemical_order" => DEFAULT_ON+NO_OFF, // later
);

$columns["my_chemical_order"]=$columns["chemical_order"]; // meine Bestellungen (filtern nach offen/abholbereit)
unset($columns["my_chemical_order"]["ordered_by"]); // me
$columns["my_chemical_order"]["links_my_chemical_order"]=DEFAULT_ON+NO_OFF;

$columns["confirm_chemical_order"]=$columns["chemical_order"]; // zu genehmigende Bestellungen
$columns["confirm_chemical_order"]["customer_comment"]=DEFAULT_ON; // info
unset($columns["confirm_chemical_order"]["accepted_order_created_by"]); // not yet avail
unset($columns["confirm_chemical_order"]["central_comment"]); // not yet avail
unset($columns["confirm_chemical_order"]["supplier_delivery_date"]); // not yet avail
unset($columns["confirm_chemical_order"]["customer_delivery_date"]); // not yet avail
unset($columns["confirm_chemical_order"]["billing_date"]); // not yet avail
$columns["confirm_chemical_order"]["links_confirm_chemical_order"]=DEFAULT_ON+NO_OFF;

$columns["open_chemical_order"]=$columns["chemical_order"]; // offene Bestellungen, zur Bearbeitung Ausgabe
$columns["open_chemical_order"]["customer_comment"]=DEFAULT_ON; // info
unset($columns["open_chemical_order"]["accepted_order_created_by"]); // not yet avail
unset($columns["open_chemical_order"]["central_comment"]); // not yet avail
unset($columns["open_chemical_order"]["supplier_delivery_date"]); // not yet avail
unset($columns["open_chemical_order"]["customer_delivery_date"]); // not yet avail
unset($columns["open_chemical_order"]["billing_date"]); // not yet avail
$columns["open_chemical_order"]["order_cost_centre"]=DEFAULT_ON; // info
$columns["open_chemical_order"]["links_open_chemical_order"]=DEFAULT_ON+NO_OFF;

$columns["accepted_order"]=$columns["chemical_order"]; // angenommene Bestellungen, in Bearbeitung Ausgabe
$columns["accepted_order"]["order_cost_centre"]=DEFAULT_ON; // who gets the bill
$columns["accepted_order"]["accepted_order_created_by"]=DEFAULT_OFF; // not so important
$columns["accepted_order"]["central_comment"]=DEFAULT_OFF; // not so important
$columns["accepted_order"]["customer_comment"]=DEFAULT_ON; // info

$columns["central_chemical_order"]=$columns["accepted_order"];
$columns["completed_chemical_order"]=$columns["accepted_order"];

// add now
$columns["accepted_order"]["links_accepted_order"]=DEFAULT_ON+NO_OFF;
$columns["central_chemical_order"]["links_central_chemical_order"]=DEFAULT_ON+NO_OFF;
$columns["completed_chemical_order"]["links_central_chemical_order"]=DEFAULT_ON+NO_OFF;
$columns["chemical_order"]["links_chemical_order"]=DEFAULT_ON+NO_OFF;

$columns["mpi_order"]=array(
	"db_beauty_name" => DEFAULT_ON, 
	"order_person" => DEFAULT_ON, 
	"order_date" => DEFAULT_ON, 
	"delivery_date" => DEFAULT_ON, 
	"supplier" => DEFAULT_ON, 
	"molecule_name" => DEFAULT_ON, 
	"cas_nr" => DEFAULT_ON, 
	"mpi_order_item" => DEFAULT_ON, 
	"bessi" => DEFAULT_ON, 
	"sap_bestell_nr" => DEFAULT_ON, 
	"sap_stamm_nr" => DEFAULT_ON, 
	"mpi_order_status" => DEFAULT_OFF, 
	"links_mpi_order" => DEFAULT_ON+NO_OFF, 
);

$columns["order_comp"]=array(
	"comp_order_date" => DEFAULT_ON, 
	"vendor" => DEFAULT_ON, 
	"items" => DEFAULT_ON, 
	"packages" => DEFAULT_OFF, 
	"grand_total" => DEFAULT_ON, 
	"links_order_comp" => DEFAULT_ON+NO_OFF, 
);

$columns["cost_centre"]=array(
	"cost_centre" => DEFAULT_ON, 
	"cost_centre_name" => DEFAULT_ON, 
	"acc_no" => DEFAULT_ON, 
	"links_cost_centre" => DEFAULT_ON+NO_OFF, 
);

$columns["rent"]=array(
	"item_identifier" => DEFAULT_ON, 
	"comment" => DEFAULT_OFF, 
	"order_cost_centre" => DEFAULT_ON, 
	"price_per_day" => DEFAULT_ON, 
	"start_date" => DEFAULT_OFF, 
	"end_date" => DEFAULT_OFF, 
	"days_count" => DEFAULT_ON, 
	"grand_total_rent" => DEFAULT_ON, 
	"billing_date" => DEFAULT_OFF, 
	"links_rent" => DEFAULT_ON+NO_OFF, 
);
$columns["active_rent"]=$columns["rent"];

$columns["settlement"]=array(
	"billing_date" => DEFAULT_ON, 
	"from_date" => DEFAULT_ON, 
	"to_date" => DEFAULT_ON, 
	"links_settlement" => DEFAULT_ON+NO_OFF, 
);

$columns["supplier_offer"]=array(
	// molecule
	"structure" => DEFAULT_ON, 
	"molecule_name" => DEFAULT_ON+NO_OFF, 
	"emp_formula_short" => DEFAULT_ON, 
	"cas_nr" => DEFAULT_ON, 
	"safety_sym" => DEFAULT_OFF, 
	"safety_text" => DEFAULT_OFF, 
	"safety_r_s" => DEFAULT_OFF, 
	"safety_class" => DEFAULT_OFF, 
	"safety_danger" => DEFAULT_OFF, 
	"safety_cancer" => DEFAULT_OFF, 
	"safety_mutagen" => DEFAULT_OFF, 
	"safety_reprod" => DEFAULT_OFF, 
	"safety_other" => DEFAULT_OFF, 
	"smiles_stereo" => DEFAULT_OFF, 
	"mw" => DEFAULT_OFF, 
	"density_20" => DEFAULT_OFF, 
	"mp_short" => DEFAULT_OFF, 
	"bp_short" => DEFAULT_OFF, 
	"comment_supplier_offer" => DEFAULT_OFF, 
	
	// price,...
	"supplier" => DEFAULT_ON, 
	"beautifulCatNo" => DEFAULT_ON, 
	"catNo" => DEFAULT_OFF, 
	"so_purity" => DEFAULT_ON, 
	"so_package_amount" => DEFAULT_ON, 
	"so_price" => DEFAULT_ON, 
	"so_date" => DEFAULT_OFF, 
	"links_supplier_offer" => DEFAULT_ON+NO_OFF, 
);
?>
