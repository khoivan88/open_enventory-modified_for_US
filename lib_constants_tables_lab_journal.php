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

$tables["project"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _lj_admin+_lj_project, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"project_person" => array("condition" => "project.project_id=project_person.project_id", ), 
		"project_person_inner" => array("base_table" => "project_person", "alias" => "project_person", "condition" => "project.project_id=project_person.project_id", "inner_join" => true, ), 
		"project_literature" => array("condition" => "project.project_id=project_literature.project_id", ),
	),
	
	"merge" => array("nameField" => "project_name"), "remoteFilter" => $reactionFilter, "defaultSecret" => true, 
	"fields" => array(
		"project_name" => array("type" => "TINYTEXT", "search" => "auto", "index" => " (10)", ),
		"project_text" => array("type" => "TEXT", "search" => "auto"),
		"project_created_by" => array("type" => "TINYTEXT", "search" => "auto"),
		"project_created_when" => array("type" => "DATE", "search" => "auto"),
		"project_status" => array("type" => "ENUM", "values" => array("in_progress","completed"), "search" => "auto", ), 
		"project_members_only" => array("type" => "BOOL"),
	), 
);

$tables["lab_journal"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _lj_admin, 
	"deletePerm" => 0,	
	
	"index" => array(
		"name" => "lab_journal_code", 
		"type" => "unique", 
		"fields" => array(
			"lab_journal_code(32)", 
		), 
	), 
	"joins" => array( // list of *possible* JOINS
		"person" => array("condition" => "lab_journal.person_id=person.person_id", ),
		"copy_target" => array("condition" => "copy_target.lab_journal_id=lab_journal.lab_journal_id", "base_table" => "lab_journal", "alias" => "copy_target", ), // 
	),
	
	"noDelete" => true, 
	"createDummy" => true, "useDisabled" => true, "recordCreationChange" => true, 
	"fields" => array(
		"person_id" => array("type" =>  "INT UNSIGNED", "fk" => "person", "search" => "auto", ),
		"lab_journal_code" => array("type" => "VARCHAR(32)", "search" => "auto", ),
		"lab_journal_status" => array("type" => "ENUM", "values" => array("open","closed","printed"), "search" => "auto", ), 
		"default_copy_target" => array("type" => "INT UNSIGNED", "fk" => "lab_journal", ),
		"default_permissions" => array("type" => "INT", ), 
		"lab_journal_uid" => array("type" => "VARBINARY(128)", ), 
	), 
);

$tables["lab_journal_person"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _lj_admin, 
	
	"joins" => array( // list of *possible* JOINS
		"person" => array("condition" => "person.person_id=lab_journal_person.person_id", ),
		"lab_journal" => array("condition" => "lab_journal.lab_journal_id=lab_journal_person.lab_journal_id", ),
		"institution" => array("condition" => "person.institution_id=institution.institution_id", ),
	),
	
	"createDummy" => true, 
	"fields" => array(
		"lab_journal_id" => array("type" => "INT UNSIGNED", "fk" => "lab_journal", ),
		"person_id" => array("type" => "INT UNSIGNED", "fk" => "person", ), 
		"permissions" => array("type" => "INT", ), 
	), 
);

$tables["project_person"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _lj_admin+_lj_project, 
	
	"joins" => array( // list of *possible* JOINS
		"person" => array("condition" => "person.person_id=project_person.person_id", ),
		"project" => array("condition" => "project.project_id=project_person.project_id", ),
		"institution" => array("condition" => "person.institution_id=institution.institution_id", ),
	),
	
	"createDummy" => true, 
	"fields" => array(
		"project_id" => array("type" => "INT UNSIGNED", "fk" => "project", ),
		"person_id" => array("type" => "INT UNSIGNED", "fk" => "person", ), 
	), 
);

$tables["reaction"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read+_barcode_user, 
	"writePerm" => _lj_admin+_lj_edit+_lj_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"lab_journal" => array("condition" => "reaction.lab_journal_id=lab_journal.lab_journal_id", ), 
		"reaction_type" => array("condition" => "reaction.reaction_type_id=reaction_type.reaction_type_id", ), 
		"project" => array("condition" => "project.project_id=reaction.project_id", ), 
		"project_person" => array("condition" => "project.project_id=project_person.project_id", ), 
		"reaction_chemical" => array("condition" => "reaction.reaction_id=reaction_chemical.reaction_id", "archive_condition" => "reaction.reaction_archive_id=reaction_chemical.archive_entity_id", ), 
		"units_ref_amount" => array("base_table" => "units", "condition" => "reaction.ref_amount_unit=units_ref_amount.unit_name", ), 
		"prod1" => array("base_table" => "reaction_chemical", "condition" => "reaction.reaction_id=prod1.reaction_id AND prod1.role=6 AND prod1.nr_in_reaction=1", "archive_condition" => "reaction.reaction_archive_id=prod1.archive_entity_id", ), 
		
		// used for $query["reaction_chemical_for_reaction"]
		"units_mass_rc" => array("base_table" => "units", "condition" => "reaction_chemical.mass_unit=units_mass_rc.unit_name", ),
		"units_volume_rc" => array("base_table" => "units", "condition" => "reaction_chemical.mass_unit=units_volume_rc.unit_name", ),
		"units_amount_rc" => array("base_table" => "units", "condition" => "reaction_chemical.mass_unit=units_amount_rc.unit_name", ),
		"units_amount_conc" => array("base_table" => "units", "condition" => "reaction_chemical.mass_unit=units_amount_conc.unit_name", ),
		
		// used for $query["reaction_literature_for_project"]
		"reaction_literature" => array("condition" => "reaction.reaction_id=reaction_literature.reaction_id", ),
		"reaction_literature_inner" => array("base_table" => "reaction_literature", "alias" => "reaction_literature", "condition" => "reaction.reaction_id=reaction_literature.reaction_id", "inner_join" => true, ),
		"literature" => array("condition" => "reaction_literature.literature_id=literature.literature_id", ),
		"sci_journal" => array("condition" => " literature.sci_journal_id=sci_journal.sci_journal_id", ),
	),
	
	"remoteFilter" => $reactionFilter, "defaultSecret" => true, 
	//~ "index" => "UNIQUE(lab_journal_id,nr_in_lab_journal)", // remove for versioning
	"index" => array(
		"name" => "lab_journal_id_2", 
		"type" => "unique", 
		"fields" => array(
			"lab_journal_id", 
			"nr_in_lab_journal", 
		), 
	), 
	"useDisabled" => true, "recordCreationChange" => true, 
	"versioning" => true, "versionAnchor" => true, 
	"fields" => array(
		"project_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "project", ),
		"reaction_type_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction_type", ),
		"reaction_prototype" => array("type" => "INT UNSIGNED", "fk" => "reaction", ), // where does this copy come from
		"reaction_prototype_db_id" => array("type" => "INT", "fk" => "other_db", ), // where does this copy come from
		"lab_journal_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "lab_journal", ),
		"nr_in_lab_journal" => array("type" => "SMALLINT", "index" => true), // must be continous to (at least try to) fulfill patent requirements!!
		"realization_text" => array("type" => "MEDIUMTEXT", ),
		"realization_text_fulltext" => array("type" => "MEDIUMTEXT", "search" => "auto", "flags" => FIELD_FULLTEXT, ),
		"realization_observation" => array("type" => "MEDIUMTEXT", ),
		"realization_observation_fulltext" => array("type" => "MEDIUMTEXT", "search" => "auto", "flags" => FIELD_FULLTEXT, ),
		"rxn_smiles" => array("type" => "TEXT", "search" => "auto"),
		"rxnfile_blob" => array("type" => "MEDIUMBLOB", "search" => "r_structure", "local_chemical_table" => "reaction_chemical", "remote_chemical_table" => "remote_reaction_chemical"),
		"rxn_gif_file" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ),
		"rxn_svg_file" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ),
		"reaction_carried_out_by" => array("type" => "TINYTEXT", "search" => "auto"),
		"reaction_title" => array("type" => "TINYTEXT", "search" => "auto"),
		"reaction_quality" => array("type" => "DOUBLE", "search" => "auto"),
		"reaction_started_when" => array("type" => "DATETIME", "search" => "auto", "index" => true, ),
		"status" => array("type" => "ENUM", "values" => array("planned","started","performed","completed","approved","printed"), ),
		"ref_amount" => array("type" => "DOUBLE", "search" => "num_unit", "allowedClasses" => array("n"), "unitCol" => "ref_amount_unit", ),
		"ref_amount_unit" => array("type" => "TINYTEXT"),
		"reaction_uid" => array("type" => "VARBINARY(128)", ), 
		"reaction_ext_archive_id" => array("type" => "TINYTEXT", ), 
	), 
); // Ausbeute wird vorgeschlagen, man kann aber frei etwas anderes eintippen. Für die Suche nach guten Reaktionen; , "project_id" => "type" => "INT UNSIGNED REFERENCES project(project_id)"

$tables["reaction_property"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read+_barcode_user, 
	"writePerm" => _lj_admin+_lj_edit+_lj_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"remoteFilter" => $reactionFilter, "defaultSecret" => true, 
	"versioning" => true, "recordCreationChange" => true, 
	"fields" => array(
		"project_id" => array("type" => "INT UNSIGNED", "fk" => "project", ),
		"reaction_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction", ), // BLEIBT
		"reaction_property_name" => array("type" => "TINYTEXT", "search" => "auto"),
		"reaction_property_value" => array("type" => "MEDIUMTEXT", "search" => "auto"),
		"reaction_property_number" => array("type" => "DOUBLE", "unitCol" => "reaction_property_unit", ), 
		"reaction_property_unit" => array("type" => "TINYTEXT", ),
	), 
); 

$tables["reaction_type"]=array(
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin, 
	"readPermRemote" => _remote_read, 
	
	"recordCreationChange" => true, 
	"fields" => array(
		"project_id" => array("type" => "INT UNSIGNED", "fk" => "project", ),
		"reaction_type_name" => array("type" => "TINYTEXT", "search" => "auto", ), 
		"reaction_type_text" => array("type" => "MEDIUMTEXT", "search" => "auto", ), 
	), 
); 

$tables["reaction_chemical"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _lj_admin+_lj_edit+_lj_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"units_mass_rc" => array("base_table" => "units", "condition" => "reaction_chemical.mass_unit=units_mass_rc.unit_name", ),
		"units_volume_rc" => array("base_table" => "units", "condition" => "reaction_chemical.volume_unit=units_volume_rc.unit_name", ),
		"units_amount_rc" => array("base_table" => "units", "condition" => "reaction_chemical.rc_amount_unit=units_amount_rc.unit_name", ),
		"units_amount_conc" => array("base_table" => "units", "condition" => "reaction_chemical.rc_conc_unit=units_amount_conc.unit_name", ),
		
		"reaction" => array("condition" => "reaction_chemical.reaction_id=reaction.reaction_id", "archive_condition" => "reaction.reaction_archive_id=reaction_chemical.archive_entity_id", ),
		"lab_journal" => array("condition" => "reaction.lab_journal_id=lab_journal.lab_journal_id", ),
		
		"chemical_storage" => array("condition" => "chemical_storage.chemical_storage_id=reaction_chemical.chemical_storage_id AND reaction_chemical.other_db_id=-1", ),
		"molecule" => array("condition" => "molecule.molecule_id=reaction_chemical.molecule_id AND reaction_chemical.other_db_id=-1", ),
		"press_units" => array("base_table" => "units", "condition" => "molecule.press_unit=press_units.unit_name", ),
		"molecule_instructions" => array("condition" => "molecule_instructions.molecule_id=molecule.molecule_id", ),
		"storage" => array("condition" => "storage.storage_id=chemical_storage.storage_id", ),
		"person" => array("condition" => "chemical_storage.borrowed_by_person_id=person.person_id AND chemical_storage.borrowed_by_db_id=-1", ),
		
		"project" => array("condition" => "project.project_id=reaction_chemical.project_id", ),
		"project_person" => array("condition" => "project.project_id=project_person.project_id", ),
	),
	
	"remoteFilter" => $reactionFilter, "defaultSecret" => true, 
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
	"versioning" => true, "recordCreationChange" => true, 
	
	"virtualFields" => array(
		"reaction_chemical_auto" => array(
			"fieldType" => "auto",
			"type" => "text",
			"searchPriority" => 103,
		), 
	), 
	"fields" => array(
		"project_id" => array("type" => "INT UNSIGNED", "fk" => "project", ),
		"reaction_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction", ),
		"from_reaction_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction", ),
		"from_reaction_chemical_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction_chemical", ),
		"other_db_id" => array("type" => "INT", "search" => "auto", "fk" => "other_db", ), // must be signed to make -1 possible
		"molecule_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "molecule", ),
		"chemical_storage_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "chemical_storage", ), 
		"chemical_storage_barcode" => array("type" => "varbinary(20)", "search" => "auto"),

		"mixture_with" => array("type" => "INT UNSIGNED", "fk" => "reaction_chemical", ), 
		"standard_name" => array("type" => "TINYTEXT", "search" => "auto"),
		"package_name" => array("type" => "TINYTEXT", "search" => "auto"),

		"cas_nr" => array("type" => "TINYTEXT", "search" => "auto", "index" => " (10)", ),
		"smiles" => array("type" => "TEXT", "search" => "auto", "index" => " (10)", "collate" => COLLATE_BIN, ),
		"smiles_stereo" => array("type" => "TEXT", "search" => "auto", "index" => " (10)", "collate" => COLLATE_BIN, ),
		"inchi" => array("type" => "TEXT", "search" => "auto"),
		"molfile_blob" => array("type" => "MEDIUMBLOB", "search" => "rc_structure"),
		"molecule_serialized" => array("type" => "MEDIUMBLOB", "search" => "structure", "flags" => FIELD_MOLECULE, ),
		"gif_file" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ),
		"svg_file" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ),
		"emp_formula" => array("type" => "TINYTEXT", "search" => "rc_emp_formula", "collate" => COLLATE_BIN, ),
		"mw" => array("type" => "DOUBLE UNSIGNED", "search" => "auto"),
		
		"density_20" => array("type" => "DOUBLE", "search" => "auto"),
		"rc_conc" => array("type" => "DOUBLE", "unitCol" => "rc_conc_unit", ),
		"rc_conc_unit" => array("type" => "TINYTEXT", ),
		
		"safety_r" => array("type" => "TINYTEXT", "search" => "auto"),
		"safety_h" => array("type" => "TINYTEXT", "search" => "auto"),
		"safety_s" => array("type" => "TINYTEXT", "search" => "auto"),
		"safety_p" => array("type" => "TINYTEXT", "search" => "auto"),
		"safety_sym" => array("type" => "TINYTEXT", "search" => "auto"),
		"safety_sym_ghs" => array("type" => "TINYTEXT", "search" => "auto"),

		"nr_in_reaction" => array("type" => "TINYINT", "index" => true),
		"addition_delay" => array("type" => "TIME"),
		"addition_duration" => array("type" => "TIME"),
		"role" => array("type" => "ENUM", "values" => array("reactant","reagent","solvent","catalyst","intermediate","product","other"), "search" => "auto", "index" => true),
		"stoch_coeff" => array("type" => "DOUBLE", "search" => "auto"),
		"rc_purity" => array("type" => "DOUBLE", "search" => "auto"),
		
		"m_brutto" => array("type" => "DOUBLE", "search" => "num_unit", "allowedClasses" => array("m"), "unitCol" => "mass_unit", ),
		"m_tara" => array("type" => "DOUBLE", "unitCol" => "mass_unit", ), // nicht genutzt
		"mass_unit" => array("type" => "TINYTEXT"),
		
		"volume" => array("type" => "DOUBLE", "search" => "num_unit", "allowedClasses" => array("v"), "unitCol" => "volume_unit", ),
		"volume_unit" => array("type" => "TINYTEXT"),
		
		"rc_amount" => array("type" => "DOUBLE", "search" => "num_unit", "allowedClasses" => array("n"), "unitCol" => "rc_amount_unit", ),
		"rc_amount_unit" => array("type" => "TINYTEXT"),
		
		"gc_yield" => array("type" => "DOUBLE", "search" => "auto"),
		"yield" => array("type" => "DOUBLE", "search" => "auto"),
		
		"measured" => array("type" => "ENUM", "values" => array("mass","volume","amount"), ), // stoch_coeff zählt als Stoffmenge gesetzt
		
		"colour" => array("type" => "TINYTEXT", "search" => "auto"), 
		"consistency" => array("type" => "TINYTEXT", "search" => "auto"), 
		"description" => array("type" => "TEXT", "search" => "auto"), 
		// yield aus stoch_coeff and amount
		"fingerprint" => array("type" => "INT NOT NULL", "default" => "0", "multiple" => fingerprint_count, "start" => 1, "flags" => FIELD_FINGERPRINT, ), // "index" => true, 
	), 
); // die Molekül-ID dient nur Info-Zwecken, was gilt wird hier reinkopiert; feld: autosave_for_reaction_chemical_id, package_name (Zusammengesetzter Freitext)

$tables["reaction_chemical_property"]=array(
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin+_lj_edit+_lj_edit_own, 
	"readPermRemote" => _remote_read, 

	"remoteFilter" => $reactionFilter, "defaultSecret" => true, 
	"versioning" => true, "recordCreationChange" => true, 
	
	"fields" => array(
		"project_id" => array("type" => "INT UNSIGNED", "fk" => "project", ),
		"reaction_chemical_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction_chemical", ), 
		"reaction_chemical_property_name" => array("type" => "TINYTEXT", "search" => "auto"),
		"reaction_chemical_property_value" => array("type" => "MEDIUMTEXT", "search" => "auto"),
		"reaction_chemical_property_number" => array("type" => "DOUBLE"), 
	), 
); 

?>