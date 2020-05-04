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
/*
Konstanten wie Berechtigungs-Bitmasken, Aufzählungen von Gefahrsymbolen und SET-Namen sowie die Reihenfolge und Anzeigeeigenschaften von 
Tabellenspalten
*/

$order_by_keys=array(
	"cost_centre" => array(
		"columns" => array(
			array("field" => "cost_centre"), 
		),
		"for_table" => array("cost_centre"), 
	),
	"person_name" => array(
		"columns" => array(
			array("field" => "last_name"), 
			array("field" => "first_name"), 
		),
		"for_table" => array("person"), // ist table in query[]["join_tables"]??
	),
	"person_name_disabled" => array(
		"columns" => array(
			array("field" => "person_disabled"), 
			array("field" => "last_name"), 
			array("field" => "first_name"), 
		),
		"for_table" => array("person"), // ist table in query[]["join_tables"]??
	),
	"person_institution" => array(
		"columns" => array(
			array("field" => "institution_name"), 
		),
		"for_table" => array("institution"),
	),
	"username" => array(
		"columns" => array(
			array("field" => "username"), 
		),
		"for_table" => array("person"),
	),
	"permissions" => array(
		"columns" => array(
			array("field" => "permissions"), 
		),
		"for_table" => array("person"),
	),
	// Khoi: added for ordering of users according to barcode
	"person_barcode" => array(
		"columns" => array(
			array("field" => "person_barcode"), 
		),
		"for_table" => array("person"),
	),
	
	"storage_name" => array(
		"columns" => array(
			array("field" => "storage_name"), 
		),
		"for_table" => array("storage"),
	),
	"institution" => array(
		"columns" => array(
			array("field" => "institution_name"), 
		),
		"for_table" => array("institution"),
	),
	"v_institution_name" => array(
		"columns" => array(
			array("field" => "v_institution_name"), 
		),
		"for_table" => array("institution"),
	),
	// Khoi: added for ordering of users according to barcode
	"storage_barcode" => array(
		"columns" => array(
			array("field" => "storage_barcode"), 
		),
		"for_table" => array("storage"),
	),

	"from_person" => array(
		"columns" => array(
			array("field" => "from_person"), 
		),
		"for_table" => array("message"),
	),
	"issued" => array(
		"columns" => array(
			array("field" => "issued", "order" => "DESC", ), 
		),
		"for_table" => array("message"),
	),
	"message_subject" => array(
		"columns" => array(
			array("field" => "priority", "order" => "DESC", ), 
			array("field" => "message_subject"), 
		),
		"for_table" => array("message"),
	),
	"do_until" => array(
		"columns" => array(
			array("field" => "do_until"), 
		),
		"for_table" => array("message"),
	),
	
	"lab_journal_entry" => array(
		"columns" => array(
			array("field" => "lab_journal_code"), 
			array("field" => "nr_in_lab_journal"), 
		),
		"for_table" => array("lab_journal"), // also reaction
	),
	// ~"yield" => array(array("field" => "yield") ),
	"reaction_carried_out_by" => array(
		"columns" => array(array("field" => "reaction_carried_out_by") ),
		"for_table" => array("reaction"),
	),
	"reaction_title" => array(
		"columns" => array(array("field" => "reaction_title") ),
		"for_table" => array("reaction"),
	),
	"reaction_type_name" => array(
		"columns" => array(array("field" => "reaction_type_name") ),
		"for_table" => array("reaction_type"),
	),
	"reaction_started_when" => array(
		"columns" => array(
			array("field" => "reaction_started_when"), 
			array("field" => "nr_in_lab_journal"), 
		),
		"for_table" => array("reaction"),
	),
	"reaction_project" => array(
		"columns" => array(array("field" => "reaction.project_id") ),
		"for_table" => array("reaction"),
	),
	"reaction_status" => array(
		"columns" => array(array("field" => "reaction.status") ),
		"for_table" => array("reaction"),
	),
	
	"sci_journal_name" => array(
		"columns" => array(array("field" => "sci_journal_name", ) ),
		"for_table" => array("sci_journal"),
	), 
	
	"molecule_name" => array(
		"columns" => array(array("field" => "molecule_name", "no_hints" => true, ) ),
		"for_table" => array("molecule","mpi_order"),
	),
	"cas_nr" => array(
		"columns" => array(array("field" => "cas_nr") ),
		"for_table" => array("molecule"),
	),
	"emp_formula_short" => array(
		//~ "columns" => array(array("field" => "emp_formula") ),
		"columns" => array(array("field" => "emp_formula_sort") ),
		"for_table" => array("molecule"),
	),
	"mw" => array(
		"columns" => array(array("field" => "mw") ),
		"for_table" => array("molecule"),
	),
	"density_20" => array(
		"columns" => array(array("field" => "density_20") ),
		"for_table" => array("molecule"),
	),
	"migrate_id_mol" => array(
		"columns" => array(array("field" => "CAST(migrate_id_mol AS SIGNED INTEGER)") ),
		"for_table" => array("chemical_storage"),
	),
	"mp_short" => array(
		"columns" => array(
			array("field" => "mp_high"), 
			array("field" => "mp_low"), 
		),
		"for_table" => array("molecule"),
	),
	"bp_short" => array(
		"columns" => array(
			array("field" => "bp_high"), 
			array("field" => "bp_low"), 
		),
		"for_table" => array("molecule"),
	),
	"n_20" => array(
		"columns" => array(array("field" => "n_20") ),
		"for_table" => array("molecule"),
	),
	"comment_mol" => array(
		"columns" => array(array("field" => "comment_mol") ),
		"for_table" => array("molecule"),
	),
	"molecule_bilancing" => array(
		"columns" => array(array("field" => "molecule_bilancing") ),
		"for_table" => array("molecule"),
	),
	
	"amount" => array(
		"columns" => array(array("field" => "amount") ),
		"for_table" => array("chemical_storage","mpi_order"),
	),
	"container" => array(
		"columns" => array(
			array("field" => "container"), 
			array("field" => "protection_gas"), 
		),
		"for_table" => array("chemical_storage"),
	),
	"chemical_storage_conc" => array(
		"columns" => array(array("field" => "chemical_storage_conc") ),
		"for_table" => array("chemical_storage"),
	),
	"purity" => array(
		"columns" => array(array("field" => "purity") ),
		"for_table" => array("chemical_storage"),
	),
	"safety_danger" => array(
		"columns" => array(array("field" => "safety_danger") ),
		"for_table" => array("molecule"),
	),
	"open_date" => array(
		"columns" => array(array("field" => "open_date") ),
		"for_table" => array("chemical_storage"),
	),
	"expiry_date" => array(
		"columns" => array(array("field" => "expiry_date") ),
		"for_table" => array("chemical_storage"),
	),
	"disposed_when" => array(
		// "columns" => array(array("field" => "disposed_when", "order" => "DESC") ),
		"columns" => array(array("field" => "disposed_when", "order" => "ASC") ), // Khoi: date search in asc order: oldest -> newest; desc: newest -> oldest
		"for_table" => array("chemical_storage"),
	),
	"disposed_by" => array(
		"columns" => array(array("field" => "disposed_by") ),
		"for_table" => array("chemical_storage"),
	),
	"storage" => array(
		"columns" => array(
			array("field" => "storage_name"), 
			array("field" => "compartment"), 
		),
		"for_table" => array("storage"), // also chemical_storage
	),
	"inventarisation" => array(
		"columns" => array(array("field" => "inventory_check_when") ),
		"for_table" => array("chemical_storage"),
	),
	"chemical_storage_barcode" => array(
		"columns" => array(array("field" => "chemical_storage_barcode") ),
		"for_table" => array("chemical_storage"),
	),
	"borrowed_by" => array(
		"columns" => array(
			array("field" => "last_name"), 
			array("field" => "first_name"), 
		),
		"for_table" => array("person"),
	),
	"chemical_storage_bilancing" => array(
		"columns" => array(array("field" => "chemical_storage_bilancing") ),
		"for_table" => array("chemical_storage"),
	),
	"comment_cheminstor" => array(
		"columns" => array(array("field" => "comment_cheminstor") ),
		"for_table" => array("chemical_storage"),
	),
	"migrate_id_cheminstor" => array(
		"columns" => array(array("field" => "CAST(migrate_id_cheminstor AS SIGNED INTEGER)") ),
		"for_table" => array("chemical_storage"),
	),
	"supplier" => array(
		"columns" => array(array("field" => "supplier") ),
		"for_table" => array("chemical_storage"),
	),
	"price" => array(
		"columns" => array(
			array("field" => "price_currency"), 
			array("field" => "price"), 
		),
		"for_table" => array("chemical_storage"),
	),
	
	"lab_journal_code" => array(
		"columns" => array(array("field" => "lab_journal_code") ),
		"for_table" => array("lab_journal"),
	),
	"project_created_when" => array(
		"columns" => array(array("field" => "project_created_when") ),
		"for_table" => array("project"),
	),
	
	"yield" => array(
		"columns" => array(array("field" => "prod1.yield") ),
		"for_table" => array("reaction_chemical"),
	),
	"gc_yield" => array(
		"columns" => array(array("field" => "prod1.gc_yield") ),
		"for_table" => array("reaction_chemical"),
	),
	
	"order_date" => array(
		"columns" => array(array("field" => "order_date") ),
		"for_table" => array("chemical_order","mpi_order"),
	),
	"delivery_date" => array(
		"columns" => array(array("field" => "order_date") ),
		"for_table" => array("mpi_order"),
	),
	"comp_order_date" => array(
		"columns" => array(array("field" => "comp_order_date") ),
		"for_table" => array("order_comp"),
	),
	"vendor" => array(
		"columns" => array(array("field" => "v_institution_name") ),
		"for_table" => array("order_comp"),
	),
	
	// MPI specific
	"sap_bestell_nr" => array(
		"columns" => array(array("field" => "sap_bestell_nr") ),
		"for_table" => array("mpi_order"),
	),
	"sap_stamm_nr" => array(
		"columns" => array(array("field" => "sap_stamm_nr") ),
		"for_table" => array("mpi_order"),
	),
	"bessi" => array(
		"columns" => array(array("field" => "CAST(bessi AS SIGNED INTEGER)") ),
		"for_table" => array("mpi_order"),
	),
	"order_person" => array(
		"columns" => array(array("field" => "order_person") ),
		"for_table" => array("mpi_order"),
	),

);

//~ print_r($order_by_keys);die();
?>
