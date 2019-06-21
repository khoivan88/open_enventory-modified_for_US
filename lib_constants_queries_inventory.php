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

$query["chemical_storage"]=array( // add ENTITY
	"base_table" => "chemical_storage", 
	//~ "join_tables" => array("molecule_names","molecule","chemical_storage","storage"), // FIXME have different order
	"join_1n" => array("molecule_names" => array("fk" => "chemical_storage.molecule_id","fk_sub" => "molecule_id")),
	"showPerPageSelect" => true,
	
	"joins" => array(
		"storage", 
		//~ "person", 
		"owner_person", 
		
		"chemical_storage_chemical_storage_type", 
		"chemical_storage_type", 
		
		"molecule", 
		"molecule_names", 
		"molecule_molecule_type", 
		"molecule_type", 
		"molecule_property", 
		
		"units_amount", 
		"units_tmd", 
		"units_conc", 
		"press_units", 
		"reaction", 
	),

	"quickfields" => "chemical_storage.chemical_storage_id AS pk", // molecule_serialized,molfile_blob
	"fields" => $fields["chemical_storage"].",".$fields["molecule"].",reaction.lab_journal_id", // ,storage.*", 
	"field_data" => array(
		array("table" => "chemical_storage", ), 
		array("table" => "molecule", ), 
		array("table" => "storage", ), 
	),
	"export_fields" => "safety_sheet_blob,alt_safety_sheet_blob,default_safety_sheet_blob,alt_default_safety_sheet_blob",
	
	"filter" => "chemical_storage.chemical_storage_disabled IS NULL", 
	
	// person.last_name,person.first_name,person.title,person.username,
	"local_fields" => "compartment,
	owner_person.last_name AS owner_last_name,owner_person.first_name AS owner_first_name,owner_person.title AS owner_title,owner_person.username AS owner_username", 
	"distinct" => GROUP_BY,
	"allowSimpleExtLinks" => true,
	"subqueries" => array( 
		array(
			"name" => "chemical_storage_type", 
			"table" => "chemical_storage_chemical_storage_type", 
			"criteria" => array("chemical_storage_chemical_storage_type.chemical_storage_id="), 
			"variables" => array("chemical_storage_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT+QUERY_LIST, 
		), 
		array(
			"name" => "molecule_type", 
			"table" => "molecule_molecule_type", 
			"criteria" => array("molecule_molecule_type.molecule_id="), 
			"variables" => array("molecule_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT+QUERY_LIST, 
		), 
		array(
			"name" => "molecule_property", 
			"table" => "molecule_property", 
			"criteria" => array("molecule_property.molecule_id="), 
			"variables" => array("molecule_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
		), 
		array(
			"name" => "molecule_instructions", 
			"table" => "molecule_instructions", 
			"criteria" => array("molecule_instructions.molecule_id="), 
			"variables" => array("molecule_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
		), 
		array(
			"table" => "person_quick", // change to more limited FIXME
			"field_db_id" => "borrowed_by_db_id", 
			"criteria" => array("person_id="), 
			"variables" => array("borrowed_by_person_id"), 
			"conjunction" => "AND", 
			"action" => "any_join", // get name etc from other db
		), 
		array(
			"name" => "chemical_storage_count", 
			"table" => "chemical_storage_for_molecule", 
			"action" => "count", 
			"criteria" => array("chemical_storage.molecule_id="), 
			"variables" => array("molecule_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
	), 
);

$query["chemical_storage_barcode"]=$query["chemical_storage"];
$query["chemical_storage_barcode"]["filter"]="";

$query["disposed_chemical_storage"]=$query["chemical_storage"];
$query["disposed_chemical_storage"]["filter"]="chemical_storage.chemical_storage_disabled=true";

function procBarcode(& $resultset) { // zeigt entweder den gesetzten Barcode oder den aus dem Primärschlüssel erzeugten
	$prefix=findBarcodePrefixForPk("chemical_storage");
	for ($a=0;$a<count($resultset);$a++) {
		if (empty($resultset[$a]["chemical_storage_barcode"])) {
			$resultset[$a]["chemical_storage_barcode"]=getEAN8($prefix,$resultset[$a]["chemical_storage_id"]);
		}
	}
}

// Abfrage der Daten zu Gebinde, das für Reaktion eingesetzt wird
$query["chemical_storage_for_reaction"]=array(
	"base_table" => "chemical_storage", 
	//~ "join_tables" => array("chemical_storage","storage"),
	
	"joins" => array(
		"molecule", 
		"storage", 
		"person", 
		"units_amount", 
		"units_conc", 
	),
	
	"filter" => $query["chemical_storage"]["filter"],
	"fields" => $fields["chemical_storage_for_reaction"], 
	"field_data" => array(
		array("table" => "chemical_storage", ), 
		array("table" => "molecule", ), 
		array("table" => "storage", ), 
	),
	"local_fields" => "compartment,person.last_name,person.first_name,person.title,person.username", 
	"procFunction" => "procBarcode",
	"distinct" => GROUP_BY, 
);

// Spezialabfrage für listAsync
$query["chemical_storage_inventory"]=array( // add ENTITY
	"base_table" => "chemical_storage", 
	"quickfields" => "chemical_storage.chemical_storage_id", 
	"fields" => "inventory_check_by,inventory_check_when", 
);

$query["chemical_storage_count"]=array( // add ENTITY
	"base_table" => "chemical_storage", 
	"quickfields" => "chemical_storage.chemical_storage_id", 
	"filter" => $query["chemical_storage"]["filter"],
);

// Unterabfrage
$query["chemical_storage_for_molecule"]=array( // add ENTITY
	"base_table" => "chemical_storage", 
	
	"joins" => array(
		"storage", 
		"units_amount", 
		"units_tmd", 
		"units_conc", 
	),
	
	"filter" => $query["chemical_storage"]["filter"], 
	"quickfields" => "chemical_storage.chemical_storage_id", 
	"fields" => $fields["chemical_storage"], 
	"field_data" => array(
		array("table" => "chemical_storage", ), 
	),
	"local_fields" => "chemical_storage.storage_id,compartment,borrowed_by_person_id,borrowed_by_db_id,storage.*", 
);

// Unterabfrage
$query["chemical_storage_for_person"]=array( // add ENTITY
	"base_table" => "chemical_storage", 
	//~ "join_tables" => array("molecule_names","molecule","chemical_storage","storage"), // FIXME change order
	"join_1n" => array("molecule_names" => array()),
	
	"joins" => array(
		"storage", "molecule", "molecule_names", "units_amount", "units_tmd", "units_conc", "press_units", 
	),
	
	"distinct" => GROUP_BY, 
	"filter" => $query["chemical_storage"]["filter"], 	
	"quickfields" => "chemical_storage.chemical_storage_id", 
	"fields" => $fields["chemical_storage"].",".$fields["molecule"], //.",molecule_name", 
	"field_data" => array(
		array("table" => "chemical_storage", ), 
		array("table" => "molecule", ), 
	),
	"local_fields" => "chemical_storage.storage_id,compartment,borrowed_by_person_id,borrowed_by_db_id", 
);

// Unterabfrage
$query["chemical_storage_for_storage"]=array( // add ENTITY
	"base_table" => "chemical_storage", 
	//~ "join_tables" => array("molecule_names","molecule","chemical_storage","storage"), // FIXME change order
	"join_1n" => array("molecule_names" => array()),
	
	"joins" => array(
		"storage", 
		"molecule", 
		"molecule_names", 
		"units_amount", 
		"units_tmd", 
		"units_conc", 
		"press_units", 
	),
	
	"distinct" => GROUP_BY, 
	"filter" => $query["chemical_storage"]["filter"], 	
	"quickfields" => "chemical_storage.chemical_storage_id", 
	"fields" => $fields["chemical_storage"].",".$fields["molecule"].",molecule_names.molecule_name", 
	"field_data" => array(
		array("table" => "chemical_storage", ), 
		array("table" => "molecule", ), 
	),
	"local_fields" => "chemical_storage.storage_id,compartment,borrowed_by_person_id,borrowed_by_db_id", 
);

// kurze Abfrage für File-Download
$query["chemical_storage_safety_sheet"]=array( // add ENTITY
	"base_table" => "chemical_storage", 
	
	"joins" => array(
		"molecule", "molecule_names", 
	),
	
	"fields" => "safety_sheet_blob,safety_sheet_url,safety_sheet_mime,
alt_safety_sheet_blob,alt_safety_sheet_url,alt_safety_sheet_mime", 
);

$query["fingerprint_molecule"]=array(
	"base_table" => "molecule", 
	//~ "fields" => $fingerprint_fields, 
	"field_data" => array(
		array("table" => "molecule", "flags" => FIELD_FINGERPRINT, ), 
	),
);

$query["molecule"]=array(
	"base_table" => "molecule", 
	//~ "join_tables" => array("molecule_names","molecule"), // FIXME change order
	"join_1n" => array("molecule_names" => array()),
	"showPerPageSelect" => true,
	
	"joins" => array(
		"molecule_names", 
		"molecule_property", 
		"press_units", 
		"molecule_molecule_type", 
		"molecule_type", 
	),
	
	"quickfields" => "molecule.molecule_id AS pk", 
	"fields" => $fields["molecule"], 
	"field_data" => array(
		array("table" => "molecule", ), 
	),
	"export_fields" => "default_safety_sheet_blob,alt_default_safety_sheet_blob",
	//~ "local_fields" => "pos_liste,neg_liste", 
	"distinct" => GROUP_BY,
	"allowSimpleExtLinks" => true,
	"subqueries" => array( 
		array(
			"name" => "molecule_type", 
			"table" => "molecule_molecule_type", 
			"criteria" => array("molecule_molecule_type.molecule_id="), 
			"variables" => array("molecule_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT+QUERY_LIST, 
		), 
		array("name" => "chemical_storage", 
			"table" => "chemical_storage_for_molecule", 
			"criteria" => array("chemical_storage.molecule_id="), 
			"variables" => array("molecule_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
		), 
		array(
			"name" => "molecule_property", 
			"table" => "molecule_property", 
			"criteria" => array("molecule_property.molecule_id="), 
			"variables" => array("molecule_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT+QUERY_CUSTOM, 
		), 
		array(
			"name" => "molecule_instructions", 
			"table" => "molecule_instructions", 
			"criteria" => array("molecule_instructions.molecule_id="), 
			"variables" => array("molecule_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
		), 
		array(
			"name" => "supplier_offer", 
			"table" => "supplier_offer", 
			"criteria" => array("supplier_offer.molecule_id="), 
			"variables" => array("molecule_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
		), 
		array(
			"name" => "chemical_storage_count", 
			"table" => "chemical_storage_for_molecule", 
			"action" => "count", 
			"criteria" => array("chemical_storage.molecule_id="), 
			"variables" => array("molecule_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
		array(
			"name" => "reaction_count", 
			"table" => "reaction_count_chemical", 
			"action" => "count", 
			"criteria" => array("reaction_chemical.molecule_id=","reaction_chemical.other_db_id="), 
			"variables" => array("molecule_id","db_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
		array(
			"name" => "supplier_offer_count", 
			"table" => "supplier_offer", 
			"action" => "count", 
			"criteria" => array("supplier_offer.molecule_id="), 
			"variables" => array("molecule_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
	), 
);

$query["molecule_count"]=array(
	"base_table" => "molecule", 
	"fields" => "COUNT(molecule_id) AS count",
);

// Abfrage der Daten zu Molekül, das für Reaktion eingesetzt wird
$query["molecule_for_reaction"]=array(
	"base_table" => "molecule", 
	//~ "join_tables" => array("molecule_names","molecule"), // FIXME change order, take base_table instead of the other name
	"join_1n" => array("molecule_names" => array()),
	
	"joins" => array(
		"molecule_names_standard", "chemical_storage", 
	),
	
	"fields" => $fields["molecule_for_reaction"], 
	"distinct" => GROUP_BY,
	"subqueries" => array( 
		array(
			"name" => "chemical_storage", 
			"table" => "chemical_storage_for_reaction", 
			"criteria" => array("chemical_storage.molecule_id="), 
			"variables" => array("molecule_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
			"ignore_archive" => true, 
		), 
	), 
);

// Spezialabfrage für Download
$query["molecule_gif"]=array(
	"base_table" => "molecule", 
	"fields" => "gif_file AS image,UNIX_TIMESTAMP(molecule_changed_when) AS last_changed", 
);

// Spezialabfrage für Download
$query["molecule_svg"]=array(
	"base_table" => "molecule", 
	"fields" => "svg_file AS image,UNIX_TIMESTAMP(molecule_changed_when) AS last_changed", 
);

// Spezialabfrage für Download
$query["molecule_mol"]=array(
	"base_table" => "molecule", 
	"fields" => "molfile_blob AS molfile,molecule_id AS pk", 
);

// Unterabfrage
$query["molecule_names"]=array(
	"base_table" => "molecule_names", 
	"quickfields" => "molecule_names.molecule_names_id", 
	"fields" => "molecule_names.*", 
);
$query["chemical_storage_chemical_storage_type"]=array(
	"base_table" => "chemical_storage_chemical_storage_type",
	
	"joins" => array(
		"chemical_storage_type", 
	),
	
	"field_data" => array(
		array("table" => "chemical_storage_type", ), 
	),
	"primary" => "chemical_storage_type.chemical_storage_type_id", 
	"short_primary" => "chemical_storage_type_id",
	"distinct" => GROUP_BY,
	"order_obj" => array(
		array("field" => "chemical_storage_type_name"),
	),
);

$query["chemical_storage_type"]=array(
	"base_table" => "chemical_storage_type", 
	"quickfields" => "chemical_storage_type_id AS pk", 
	
	"field_data" => array(
		array("table" => "chemical_storage_type", ), 
	),
	"order_obj" => array(
		array("field" => "chemical_storage_type_name"),
	),
);

$query["molecule_molecule_type"]=array(
	"base_table" => "molecule_molecule_type",
	
	"joins" => array(
		"molecule_type", 
	),
	
	"field_data" => array(
		array("table" => "molecule_type", ), 
	),
	"primary" => "molecule_type.molecule_type_id", 
	"short_primary" => "molecule_type_id",
	"distinct" => GROUP_BY,
	"order_obj" => array(
		array("field" => "molecule_type_name"),
	),
);

$query["molecule_type"]=array(
	"base_table" => "molecule_type", 
	"quickfields" => "molecule_type_id AS pk", 
	
	"field_data" => array(
		array("table" => "molecule_type", ), 
	),
	"order_obj" => array(
		array("field" => "molecule_type_name"),
	),
);

// Unterabfragen
$query["molecule_property"]=array(
	"base_table" => "molecule_property", 
	
	"joins" => array(
		"units", 
	),
	
	"quickfields" => "molecule_property_id", 
	"fields" => $fields["molecule_property"], 
);

$query["molecule_instructions"]=array(
	"base_table" => "molecule_instructions", 
	
	"quickfields" => "molecule_instructions_id", 
	"fields" => $fields["molecule_instructions"], 
	"order_obj" => array(
		array("field" => "molecule_instructions_changed_when", "order" => "DESC", ),
	),
);

// Spezialabfragen für Download
$query["molecule_safety_sheet"]=array(
	"base_table" => "molecule", 
	"fields" => "default_safety_sheet_blob,default_safety_sheet_url,default_safety_sheet_mime,
alt_default_safety_sheet_blob,alt_default_safety_sheet_url,alt_default_safety_sheet_mime", 
);
$query["molecule_instructions_download"]=array(
	"base_table" => "molecule_instructions", 
	"fields" => "file_blob", 
);

// Spezialabfrage für SMILES-Reparatur
$query["molecule_fix_smiles"]=array(
	"base_table" => "molecule", 
	"fields" => "molecule_id,molfile_blob,smiles,smiles_stereo,emp_formula", 
);

$query["storage"]=array(
	"base_table" => "storage", 
	//~ "join_tables" => array("storage","institution"),
	
	"joins" => array(
		"institution", 
	),
	
	"quickfields" => "storage_id AS pk", 
	"field_data" => array(
		array("table" => "storage", ), 
		array("table" => "institution", ), 
	),
	"order_obj" => array(
		array("field" => "storage_name"),
	),
	"subqueries" => array( 
		array(
			"name" => "chemical_storage_count", 
			"table" => "chemical_storage_for_storage", 
			"action" => "count", 
			"criteria" => array("chemical_storage.storage_id="), 
			"variables" => array("storage_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
	), 
);

$query["supplier_offer"]=array(
	"base_table" => "supplier_offer", 
	//~ "join_tables" => array("supplier_offer","molecule_names","molecule"), // FIXME change order
	"join_1n" => array("molecule_names" => array("fk" => "supplier_offer.molecule_id","fk_sub" => "molecule_id")),
	
	"joins" => array(
		"molecule", 
		"molecule_names", 
		"press_units", 
	),
	
	"quickfields" => "supplier_offer_id AS pk", 
	"fields" => $fields["molecule"], 
	"field_data" => array(
		array("table" => "supplier_offer", ), 
		array("table" => "molecule", ), 
	),
	"order_obj" => array(
		array("field" => "supplier"),
	),
	"subqueries" => array( 
		array(
			"name" => "institution", 
			"table" => "institution", 
			"criteria" => array("institution_code.supplier_code LIKE BINARY "), 
			"variables" => array("supplier"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
		), 
	), 
	"distinct" => GROUP_BY, 
);

$query["supplier_offer_for_accepted_order"]=array(
	"base_table" => "supplier_offer", 
	
	"joins" => array(
		"molecule", 
		"molecule_names", 
		"press_units", 
		//~ "institution_code", 
		//~ "institution", 
	),
	
	"quickfields" => "supplier_offer_id AS pk", 
	"fields" => "supplier_offer_id,1 AS number_packages,catNo,beautifulCatNo,supplier,so_package_amount AS package_amount,so_package_amount_unit AS package_amount_unit,so_price,so_price_currency,density_20,so_price AS price,so_price_currency AS price_currency,so_vat_rate AS vat_rate,molecule_name AS name,cas_nr", // ,institution_id AS vendor_id", 
	"subqueries" => array(
		array(
			"table" => "vendor", 
			"action" => "local_join", 
			"uid_search" => "institution_code.supplier_code",
			"uid_op" => " LIKE BINARY ", 
			"uid_value" => "supplier", 
		), 
	), 
	"distinct" => GROUP_BY, 
);

// MPI specific
$query["mat_stamm_nr"]=array(
	"base_table" => "mat_stamm_nr", 
	"quickfields" => "mat_stamm_nr.mat_stamm_nr_id AS pk", 
	//~ "fields" => "*", 
	"field_data" => array(
		array("table" => "mat_stamm_nr", ), 
	),
);

$query["mat_stamm_nr_for_mpi_order"]=array(
	"base_table" => "mat_stamm_nr", 
	"quickfields" => "mat_stamm_nr.mat_stamm_nr_id AS pk", 
	"fields" => "cas_nr,comment_stamm_nr", 
	
	"joins" => array(
		"molecule", 
	),
);

$query["max_bessi"]=array(
	"base_table" => "molecule", 
	"quickfields" => "molecule.molecule_id AS pk", 
	"fields" => "MAX(CAST(migrate_id_mol AS SIGNED INTEGER)) AS max_bessi", 
);

?>