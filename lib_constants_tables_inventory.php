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

$tables["molecule_names"]=array(
	"readPerm" => _chemical_read, 
	"writePerm" => _chemical_edit+_chemical_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"fields" => array(
		"molecule_id" => array("type" => "INT UNSIGNED", "fk" => "molecule", ),
		"molecule_name" => array("type" => "TEXT", "search" => "auto", "searchPriority" => 102, "index" => " (10)", ),
		"language_id" => array("type" => "TINYTEXT"),
		"is_trivial_name" => array("type" => "SMALLINT UNSIGNED"),
		"is_standard" => array("type" => "BOOL", "index" => true), 
	), 
);
$tables["molecule"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _chemical_edit+_chemical_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"merge" => array("nameField" => "molecule_name"), "recordCreationChange" => true, 
	"index" => array(
		"name" => "mw_2", 
		"type" => "index", 
		"fields" => array(
			"mw", 
			"fingerprint1", 
			"fingerprint2", 
			"fingerprint3", 
			"fingerprint4", 
			"fingerprint5", 
			"fingerprint6", 
			"fingerprint7", 
			"fingerprint8", 
			"fingerprint9", 
			"fingerprint10", 
			"fingerprint11", 
			"fingerprint12", 
			"fingerprint13", 
			"fingerprint14", 
			"fingerprint15", 
		), 
	), 
	
	"joins" => array( // list of *possible* JOINS
		"molecule_names" => array("condition" => "molecule.molecule_id=molecule_names.molecule_id", ),
		"molecule_names_standard" => array("base_table" => "molecule_names", "alias" => "molecule_names", "condition" => "molecule.molecule_id=molecule_names.molecule_id AND molecule_names.is_standard", ),
		"molecule_property" => array("condition" => "molecule.molecule_id = molecule_property.molecule_id", ),
		
		"molecule_molecule_type" => array("condition" => "molecule.molecule_id=molecule_molecule_type.molecule_id", ), 
		"molecule_type" => array("condition" => "molecule_molecule_type.molecule_type_id=molecule_type.molecule_type_id", ), 
		
		"press_units" => array("base_table" => "units", "condition" => "molecule.press_unit=press_units.unit_name", ),
		
		"chemical_storage" => array("condition" => "molecule.molecule_id=chemical_storage.molecule_id", ),
	),

	"virtualFields" => array(
		"molecule_auto" => array(
			"fieldType" => "auto",
			"type" => "text",
			"searchPriority" => 103,
		),
		"storage_count" => array(
			"fieldType" => "count",
			"table" => "chemical_storage", 
			"condition" => "chemical_storage.molecule_id=molecule.molecule_id AND chemical_storage.chemical_storage_disabled IS NULL", 
			"searchPriority" => 80, 
		),
		"supplier_offer_count" => array(
			"fieldType" => "count",
			"table" => "supplier_offer", 
			"condition" => "supplier_offer.molecule_id=molecule.molecule_id", 
			"searchPriority" => 79, 
		),
		"reaction_count" => array(
			"fieldType" => "count",
			"table" => "reaction_count_chemical", 
			"condition" => "reaction_chemical.molecule_id=molecule.molecule_id", 
		),
		"molecule_property_flat" => array(
			"fieldType" => "flat",
			"fieldListTable" => "class", 
			"fieldListCol" => "class_name", 
			"fieldTypeCol" => "class_format", 
			"fieldTypeUnitTypeCol" => "class_type", // für den Aufbau des <select zur Suche
			
			"table" => "molecule_property", 
			"fk" => "molecule_property.molecule_id", 
			"fieldCol" => "class", 
			"valueCol" => "value_high", 
			"value_lowCol" => "value_low", 
			"textCol" => "conditions", // für die eigentliche Suche
		), 
	),
	"fields" => array(
		"cas_nr" => array("type" => "TINYTEXT", "search" => "auto", "searchPriority" => 101, "index" => " (10)", ),
		"smiles" => array("type" => "TEXT", "index" => " (10)", "collate" => COLLATE_BIN, ),
		"smiles_stereo" => array("type" => "TEXT", "index" => " (10)", "collate" => COLLATE_BIN, ),
		"inchi" => array("type" => "TEXT"),
		"molfile_blob" => array("type" => "MEDIUMBLOB", "search" => "structure", "searchPriority" => 99, ),
		"molecule_serialized" => array("type" => "MEDIUMBLOB", "flags" => FIELD_MOLECULE, ),
		"molfile_blob_source" => array("type" => "TINYTEXT"),
		"emp_formula" => array("type" => "TINYTEXT", "search" => "emp_formula", "searchPriority" => 98, "collate" => COLLATE_BIN, ),
		"emp_formula_sort" => array("type" => "TINYTEXT", "collate" => COLLATE_BIN, ),
		"emp_formula_source" => array("type" => "TINYTEXT"),
		"mw" => array("type" => "DOUBLE UNSIGNED", "search" => "auto", "searchPriority" => 97, ), // "index" => true, 
		"mw_monoiso" => array("type" => "DOUBLE UNSIGNED", "search" => "auto", ), 
		"rdb" => array("type" => "DOUBLE UNSIGNED", "search" => "auto", ), 
		"mw_source" => array("type" => "TINYTEXT"),
		"density_20" => array("type" => "DOUBLE", "search" => "auto"),
		"density_20_source" => array("type" => "TINYTEXT"),
		"molecule_bilancing" => array("type" => "INT", ), 
		"default_warn_level" => array("type" => "DOUBLE", ), 
		"n_20" => array("type" => "DOUBLE", "search" => "auto"),
		"n_20_source" => array("type" => "TINYTEXT"),
		"mp_low" => array("type" => "DOUBLE"),
		"mp_high" => array("type" => "DOUBLE", "search" => "range", "low_name" => "mp_low"),
		"mp_source" => array("type" => "TINYTEXT"),
		"bp_low" => array("type" => "DOUBLE"),
		"bp_high" => array("type" => "DOUBLE", "search" => "range", "low_name" => "bp_low"),
		"bp_press" => array("type" => "DOUBLE", "unitCol" => "press_unit", ),
		"press_unit" => array("type" => "TINYTEXT"),
		"bp_source" => array("type" => "TINYTEXT"),
		"safety_r" => array("type" => "TINYTEXT", "search" => "auto", ),
		"safety_h" => array("type" => "TINYTEXT", "search" => "auto", ),
		"safety_s" => array("type" => "TINYTEXT", "search" => "auto", ),
		"safety_p" => array("type" => "TINYTEXT", "search" => "auto", ),
		"safety_text" => array("type" => "TINYTEXT"),
		"safety_sym" => array("type" => "TINYTEXT", "search" => "auto"),
		"safety_sym_ghs" => array("type" => "TINYTEXT", "search" => "auto"),
		"safety_source" => array("type" => "TINYTEXT"),
		"gif_file" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ),
		"svg_file" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ),
		"comment_mol" => array("type" => "TEXT", "search" => "auto", ),
		"migrate_id_mol" => array("type" => "TINYTEXT", "search" => "auto", ), 
		
		"safety_cancer" => array("type" => "TINYTEXT", "search" => "auto", ),
		"safety_mutagen" => array("type" => "TINYTEXT", "search" => "auto", ),
		"safety_reprod" => array("type" => "TINYTEXT", "search" => "auto", ),
		
		"safety_wgk" => array("type" => "TINYTEXT", "search" => "auto", ),
		"safety_danger" => array("type" => "TINYTEXT", "search" => "auto", ),
		
		"molecule_btm_list" => array("type" => "INT", "search" => "auto"), // numerisch
		"molecule_sprengg_list" => array("type" => "TINYTEXT", "search" => "auto"), // auch 1a, 1b
		
		"default_safety_sheet_blob" => array("type" => "MEDIUMBLOB", "flags" => FIELD_MSDS, ),
		"default_safety_sheet_url" => array("type" => "TINYTEXT"),
		"default_safety_sheet_mime" => array("type" => "TINYTEXT"),
		"default_safety_sheet_by" => array("type" => "TINYTEXT", "search" => "auto"),
		
		"alt_default_safety_sheet_blob" => array("type" => "MEDIUMBLOB", "flags" => FIELD_MSDS, ),
		"alt_default_safety_sheet_url" => array("type" => "TINYTEXT"),
		"alt_default_safety_sheet_mime" => array("type" => "TINYTEXT"),
		"alt_default_safety_sheet_by" => array("type" => "TINYTEXT", "search" => "auto"),
		"fingerprint" => array("type" => "INT NOT NULL", "default" => "0", "multiple" => fingerprint_count, "start" => 1, "flags" => FIELD_FINGERPRINT, ), // "index" => true, 
		
		// MPI
		"pos_liste" => array("type" => "INT", "search" => "bool", ), // nur local-field
		"neg_liste" => array("type" => "INT", "search" => "bool", ), // nur local-field
		
		"molecule_user" => array("type" => "TINYTEXT", "multiple" => 8, "start" => 0, "flags" => FIELD_RESERVE, ), // user-specific fields
		"molecule_int" => array("type" => "INT", "multiple" => 2, "start" => 0, "search" => "auto", ), // user-specific fields
		"molecule_dbl" => array("type" => "DOUBLE", "multiple" => 2, "start" => 0, "search" => "auto", ), // user-specific fields
	), 
);

$tables["molecule_molecule_type"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _chemical_edit+_chemical_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"molecule_type" => array("condition" => "molecule_type.molecule_type_id=molecule_molecule_type.molecule_type_id", ),
		"molecule" => array("condition" => "molecule.molecule_id=molecule_type.molecule_id", ),
	),
	
	"createDummy" => true, 
	"fields" => array(
		"molecule_id" => array("type" => "INT UNSIGNED", "fk" => "molecule", ),
		"molecule_type_id" => array("type" => "INT UNSIGNED", "fk" => "molecule_type", ), 
	), 
);

$tables["molecule_type"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _lj_admin, 
	"readPermRemote" => _remote_read, 
	
	"recordCreationChange" => true, 
	"fields" => array(
		"molecule_type_name" => array("type" => "TINYTEXT", "search" => "auto", "searchPriority" => 96, ), 
		"molecule_type_text" => array("type" => "MEDIUMTEXT", "search" => "auto", ), 
	), 
); 

$tables["molecule_property"]=array(
	"readPerm" => _chemical_read, 
	"writePerm" => _chemical_edit+_chemical_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"units" => array("condition" => "molecule_property.unit=units.unit_name", ),
	),
	
	"recordCreationChange" => true, 
	"fields" => array(
		"molecule_id" => array("type" => "INT UNSIGNED", "fk" => "molecule", ),
		"source" => array("type" => "TEXT", ),
		"class" => array("type" => "TINYTEXT", "index" => " (10)", ), // Fp, mp, bp are all of class T (temperature) type ergibt sich über class
		"value_low" => array("type" => "DOUBLE", "index" => true, "unitCol" => "unit", ), // x+-y should also be possible
		"value_high" => array("type" => "DOUBLE", "index" => true, "unitCol" => "unit", ),
		"unit" => array("type" => "TINYTEXT", "index" => " (10)", ),
		"conditions" => array("type" => "TEXT", ),
		"molecule_property_comment" => array("type" => "TEXT", ),
		"data" => array("type" => "LONGBLOB", ),
		"analytical_data_graphics_blob" => array("type" => "MEDIUMBLOB", ),
		"analytical_data_svg_blob" => array("type" => "MEDIUMBLOB", ),
		"acquired" => array("type" => "ENUM", "values" => array("measured","calculated","defined","statistically","other"), ), 
	), 
);

$tables["molecule_instructions"]=array(
	"readPerm" => _chemical_read, 
	"writePerm" => _chemical_edit+_chemical_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"recordCreationChange" => true, 
	"fields" => array(
		"molecule_id" => array("type" => "INT UNSIGNED", "fk" => "molecule", ),
		"lang" => array("type" => "TINYTEXT", ),
		"file_blob" => array("type" => "MEDIUMBLOB", ),
		"file_image" => array("type" => "MEDIUMBLOB", ),
		"betr_anw_gefahren" => array("type" => "TEXT", ),
		"betr_anw_schutzmass" => array("type" => "TEXT", ),
		"betr_anw_schutzmass_sym" => array("type" => "TINYTEXT", ), // according to DIN EN ISO 7010
		"betr_anw_verhalten" => array("type" => "MEDIUMTEXT", ),
		"betr_anw_verhalten_sym" => array("type" => "TINYTEXT", ), // according to DIN EN ISO 7010
		"betr_anw_erste_h" => array("type" => "MEDIUMTEXT", ),
		"betr_anw_erste_h_sym" => array("type" => "TINYTEXT", ), // according to DIN EN ISO 7010
		"betr_anw_entsorgung" => array("type" => "MEDIUMTEXT", ),
		"molecule_instructions_comment" => array("type" => "TEXT", ),
	), 
);

$tables["chemical_storage"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _chemical_create+_chemical_delete+_chemical_edit+_chemical_edit_own+_chemical_borrow+_chemical_inventarise, 
	"createPerm" => _chemical_create+_chemical_edit+_chemical_edit_own, 
	"deletePerm" => _chemical_delete+_chemical_edit, // also applies to dispose
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"storage" => array("condition" => "chemical_storage.storage_id=storage.storage_id", ),
		"person" => array("condition" => "borrowed_by_person_id=person.person_id", ),
		"owner_person" => array("base_table" => "person", "condition" => "owner_person_id=owner_person.person_id", ), // FIXME, create $query with basetable or put alias here
		
		"chemical_storage_chemical_storage_type" => array("condition" => "chemical_storage.chemical_storage_id=chemical_storage_chemical_storage_type.chemical_storage_id", ), 
		"chemical_storage_type" => array("condition" => "chemical_storage_chemical_storage_type.chemical_storage_type_id=chemical_storage_type.chemical_storage_type_id", ), 
		
		"molecule" => array("condition" => "chemical_storage.molecule_id=molecule.molecule_id", ),
		"molecule_names" => array("condition" => "molecule.molecule_id=molecule_names.molecule_id", ),
		"molecule_property" => array("condition" => "molecule_property.molecule_id=molecule.molecule_id", ),
		
		"molecule_molecule_type" => array("condition" => "molecule.molecule_id=molecule_molecule_type.molecule_id", ), 
		"molecule_type" => array("condition" => "molecule_molecule_type.molecule_type_id=molecule_type.molecule_type_id", ), 
		
		"units_tmd" => array("base_table" => "units", "condition" => "chemical_storage.tmd_unit=units_tmd.unit_name", ), 
		"units_amount" => array("base_table" => "units", "condition" => "chemical_storage.amount_unit=units_amount.unit_name", ), // FIXME, create $query with basetable or put alias here
		"units_conc" => array("base_table" => "units", "condition" => "chemical_storage.chemical_storage_conc_unit=units_conc.unit_name", ), // FIXME, create $query with basetable or put alias here
		"press_units" => array("base_table" => "units", "condition" => "molecule.press_unit=press_units.unit_name", ), // FIXME, create $query with basetable or put alias here
		"reaction" => array("condition" => "chemical_storage.from_reaction_id=reaction.reaction_id", ),
	),
	
	"recordCreationChange" => true, "versioning" => true, "versionAnchor" => true, "useDisabled" => true, 
	"fields" => array(
		"molecule_id" => array("type" => "INT UNSIGNED", "fk" => "molecule", ),
		"from_reaction_id" => array("type" => "INT UNSIGNED", "fk" => "reaction", ), 
		"from_reaction_chemical_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction_chemical", ),
		"chemical_order_id" => array("type" => "INT UNSIGNED", "fk" => "chemical_order", ),
		"purity" => array("type" => "DOUBLE", "search" => "auto"),
		"description" => array("type" => "TEXT", "search" => "auto"),
		"order_date" => array("type" => "DATE", "search" => "auto"),
		"open_date" => array("type" => "DATE", "search" => "auto"),
		"amount" => array("type" => "DOUBLE", "search" => "num_unit", "allowedClasses" => array("m", "v"), "densityCol" => "density_20", "unitCol" => "amount_unit", "isVolumeCol" => "amount_is_volume", ),
		"actual_amount" => array("type" => "DOUBLE", "search" => "num_unit", "allowedClasses" => array("m", "v"), "densityCol" => "density_20", "unitCol" => "amount_unit", "isVolumeCol" => "amount_is_volume", ),
		"amount_unit" => array("type" => "TINYTEXT"), 
		"amount_is_volume" => array("type" => "BOOLEAN"), 
		"warn_level" => array("type" => "DOUBLE", ), 
		"tmd" => array("type" => "DOUBLE", "search" => "num_unit", "allowedClasses" => array("m"), "unitCol" => "tmd_unit", ), // tara mit deckel
		"tmd_unit" => array("type" => "TINYTEXT"), 
		"chemical_storage_bilancing" => array("type" => "INT", ), 
		"chemical_storage_attrib" => array("type" => "SET", "values" => array("light_sensitive","air_sensitive","moisture_sensitive","refridgerate","hygroscopic","stabilized","denaturated"), ),
		"chemical_storage_conc" => array("type" => "DOUBLE", "search" => "num_unit", "allowedClasses" => array("c","m/m","molal",), "unitCol" => "chemical_storage_conc_unit", ), 
		"chemical_storage_conc_unit" => array("type" => "TINYTEXT"), 
		"chemical_storage_solvent" => array("type" => "TINYTEXT", "search" => "auto"), 
		"chemical_storage_density_20" => array("type" => "DOUBLE"), 
		"expiry_date" => array("type" => "DATE", "search" => "auto"), 
		"owner_person_id" => array("type" => "INT UNSIGNED", "fk" => "person", ), 
		"container" => array("type" => "TINYTEXT", "search" => "auto"), 
		"cat_no" => array("type" => "TINYTEXT", "search" => "auto"), 
		"lot_no" => array("type" => "TINYTEXT", "search" => "auto"), 
		"protection_gas" => array("type" => "TINYTEXT", "search" => "auto"), 
		"disposed_when" => array("type" => "DATE", "search" => "auto"), 
		"disposed_by" => array("type" => "TINYTEXT", "search" => "auto"), 
		"storage_id" => array("type" => "INT UNSIGNED", "fk" => "storage", ), // , "search" => "auto"
		"compartment" => array("type" => "TINYTEXT", "search" => "auto", "searchPriority" => 70, ),
		"transferred_to_db_id" => array("type" => "INT", "fk" => "other_db", ), // must be signed to make -1 possible
		"borrowed_by_db_id" => array("type" => "INT", "fk" => "other_db", ), // must be signed to make -1 possible
		"borrowed_by_person_id" => array("type" => "INT UNSIGNED", "fk" => "person", ), 
		"borrowed_when" => array("type" => "DATETIME", ), 
		"comment_cheminstor" => array("type" => "TEXT", "search" => "auto"), 
		"history" => array("type" => "TEXT NOT NULL", "default" => "''", ), // Eintragen von Entnahmen, nachträglich nicht mehr änderbar
		"migrate_id_cheminstor" => array("type" => "TINYTEXT", "search" => "auto", "searchPriority" => 100), 
		
		"chemical_storage_btm_list" => array("type" => "INT", "search" => "auto"), // numerisch
		"chemical_storage_sprengg_list" => array("type" => "TINYTEXT", "search" => "auto"), // auch 1a, 1b
		
		"safety_sheet_blob" => array("type" => "MEDIUMBLOB", "flags" => FIELD_MSDS, ), 
		"safety_sheet_url" => array("type" => "TINYTEXT"), 
		"safety_sheet_mime" => array("type" => "TINYTEXT"), 
		"safety_sheet_by" => array("type" => "TINYTEXT", "search" => "auto"), 
		
		"alt_safety_sheet_blob" => array("type" => "MEDIUMBLOB", "flags" => FIELD_MSDS, ), 
		"alt_safety_sheet_url" => array("type" => "TINYTEXT"), 
		"alt_safety_sheet_mime" => array("type" => "TINYTEXT"), 
		"alt_safety_sheet_by" => array("type" => "TINYTEXT", "search" => "auto"), 
		
		"inventory_check_by" => array("type" => "TINYTEXT", "search" => "auto", ), 
		"inventory_check_when" => array("type" => "DATETIME", "search" => "auto", ), 
		"supplier" => array("type" => "TEXT", "search" => "auto", ), 
		"price" => array("type" => "DOUBLE", "search" => "money", ), 
		"price_currency" => array("type" => "TINYTEXT"), 
		
		"chemical_storage_user" => array("type" => "TINYTEXT", "multiple" => 4, "start" => 0, "flags" => FIELD_RESERVE, ), // user-specific fields
		"chemical_storage_int" => array("type" => "INT", "multiple" => 2, "start" => 0, "search" => "auto", ), // user-specific fields
		"chemical_storage_dbl" => array("type" => "DOUBLE", "multiple" => 2, "start" => 0, "search" => "auto", ), // user-specific fields
	), 
);

$tables["chemical_storage_chemical_storage_type"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _chemical_create+_chemical_delete+_chemical_edit+_chemical_edit_own+_chemical_borrow+_chemical_inventarise, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"chemical_storage_type" => array("condition" => "chemical_storage_type.chemical_storage_type_id=chemical_storage_chemical_storage_type.chemical_storage_type_id", ),
		"chemical_storage" => array("condition" => "chemical_storage.chemical_storage_id=chemical_storage_type.chemical_storage_id", ),
	),
	
	"createDummy" => true, 
	"fields" => array(
		"chemical_storage_id" => array("type" => "INT UNSIGNED", "fk" => "chemical_storage", ),
		"chemical_storage_type_id" => array("type" => "INT UNSIGNED", "fk" => "chemical_storage_type", ), 
	), 
);

$tables["chemical_storage_type"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _lj_admin, 
	"readPermRemote" => _remote_read, 
	
	"recordCreationChange" => true, 
	"fields" => array(
		"chemical_storage_type_name" => array("type" => "TINYTEXT", "search" => "auto", ), 
		"chemical_storage_type_text" => array("type" => "MEDIUMTEXT", "search" => "auto", ), 
	), 
); 

$tables["chemical_storage_property"]=array(
	"readPerm" => _chemical_read, 
	"writePerm" => _chemical_create+_chemical_edit+_chemical_edit_own+_chemical_borrow+_chemical_inventarise, 
	"readPermRemote" => _remote_read, 
	
	"fields" => array(
		"chemical_storage_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "chemical_storage", ), // BLEIBT
		"chemical_storage_property_name" => array("type" => "TINYTEXT", "search" => "auto"), 
		"chemical_storage_property_value" => array("type" => "MEDIUMTEXT", "search" => "auto"), 
		"chemical_storage_property_number" => array("type" => "DOUBLE"), 
	), 
);

$tables["storage"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _admin+_storage_modify, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"institution" => array("condition" => "storage.institution_id=institution.institution_id", ),
	),
	
	"merge" => array("nameField" => "storage_name"), "createDummy" => true, "recordCreationChange" => true, 
	"fields" => array(
		"storage_name" => array("type" => "TINYTEXT", "search" => "auto", "index" => " (10)", "searchPriority" => 70, ),
		"institution_id" => array("type" => "INT UNSIGNED", "fk" => "institution", ), // , "search" => "auto"
		"poison_cabinet" => array("type" => "INT", ),
	), 
);

$tables["supplier_offer"]=array(
	"readPerm" => _chemical_read, 
	"writePerm" => _order_accept, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"molecule" => array("condition" => "molecule.molecule_id=supplier_offer.molecule_id", ), // INNER JOIN became OUTER JOIN
		"molecule_names" => array("condition" => "molecule.molecule_id=molecule_names.molecule_id", ),
		//~ "molecule_property" => array("condition" => "molecule.molecule_id = molecule_property.molecule_id", ),
		"press_units" => array("base_table" => "units", "condition" => "molecule.press_unit=press_units.unit_name", ),
		//~ "institution_code" => array("condition" => "supplier_offer.supplier LIKE BINARY institution_code.supplier_code", ),
		
		// replaced by any_join
		//~ "institution_code" => array("condition" => "supplier_offer.supplier=institution_code.supplier_code", ),
		//~ "institution" => array("condition" => "institution.institution_id=institution_code.institution_id", ),
	),
	
	"recordCreationChange" => true, 
	"fields" => array(
		"molecule_id" => array("type" => "INT UNSIGNED", "fk" => "molecule", ), // erlaubt mehr suchoptionen, weniger redundanz
		"supplier" => array("type" => "TINYTEXT", "index" => " (10)", "search" => "auto", ), // use the code like "Sial"
		"catNo" => array("type" => "TINYTEXT", "index" => " (10)", ), // internal catNo
		"beautifulCatNo" => array("type" => "TINYTEXT", "index" => " (10)", "search" => "auto", ), // catNo for order
		"so_purity" => array("type" => "TEXT", "search" => "auto", ),
		"so_package_amount" => array("type" => "DOUBLE", "search" => "num_unit", "allowedClasses" => array("m", "v"), ), // "unitCol" => "amount_unit", // FIXME switch to standard behavior (amount in standard unit)
		"so_package_amount_unit" => array("type" => "TINYTEXT"),
		"so_price" => array("type" => "DOUBLE", "search" => "money", ), // einzelpreis, Gesamtpreis = price*number_packages
		"so_price_currency" => array("type" => "TINYTEXT") , 
		"so_vat_rate" => array("type" => "DOUBLE", "search" => "auto", ), // MwSt-Satz, default 19%
		"so_date" => array("type" => "DATETIME", "search" => "auto", ), 
		"comment_supplier_offer" => array("type" => "TEXT", "search" => "auto", ),
	), 
); // mehrere Alternativen zum Kaufen


?>
