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

$query["fingerprint_reaction_chemical"]=array(
	"base_table" => "reaction_chemical", 
	"field_data" => array(
		array("table" => "reaction_chemical", "flags" => FIELD_FINGERPRINT, ), 
	),
	//~ "fields" => $fingerprint_fields, 
);

$query["lab_journal"]=array(
	"base_table" => "lab_journal", 
	//~ "join_tables" => array("lab_journal","person"),
	
	"joins" => array(
		"person", 
		"copy_target", 
	),
	
	"quickfields" => "lab_journal.lab_journal_id AS pk", 
	"field_data" => array(
		array("table" => "lab_journal", ), 
		array("table" => "person", "skip_types" => array("BLOB"), ), 
	),
	//~ "fields" => $fields["lab_journal"].",".$fields["person"], // FALSE AS allowEdit,
	"order_obj" => array(
		array("field" => "lab_journal.lab_journal_code"),
	),
	"subqueries" => array( 
		array(
			"name" => "reaction_count", 
			"table" => "reaction", 
			"action" => "count", 
			"criteria" => array("reaction.lab_journal_id="), 
			"variables" => array("lab_journal_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
	), 
);

$query["reaction_chemical_for_reaction"]=array( // product from a reaction as starting material for another reaction
	"base_table" => "reaction", 
	
	// AND reaction_chemical.role=6 seems to be obsolete in JOIN
	
	"joins" => array(
		"lab_journal", "reaction_chemical", "units_ref_amount", "units_mass_rc", "units_volume_rc", "units_amount_rc", "units_amount_conc", 
	),
	
	"fields" => $fields["reaction_chemical"].",".$fields["reaction"], // .",".$fields["lab_journal"], // reaction_id must find way through
	"field_data" => array(
		array("table" => "reaction_chemical", ), 
		array("table" => "reaction", ), 
		array("table" => "lab_journal", ), 
	),
	"distinct" => GROUP_BY,
);
$query["person_cmr"]=array(
	"base_table" => "reaction_chemical", 
	"joins" => array(
		"units_amount_conc", "reaction", "lab_journal", "molecule", 
	),
	"fields" => "m_brutto,volume,rc_amount,(reaction_chemical.rc_conc / units_amount_conc.unit_factor) AS rc_conc,IF(rc_conc IS NULL, '', CONCAT(rc_conc, ' ', rc_conc_unit)) AS rc_conc_text,".
		"CONCAT(lab_journal_code, ' ', reaction.nr_in_lab_journal) AS lab_journal_entry,reaction.status,reaction_started_when,reaction_carried_out_by",
	"field_data" => array(
		array("table" => "reaction_chemical", ), 
		array("table" => "molecule", ), 
	),
);

$query["person_project"]=array(
	"base_table" => "project_person", 
	
	"joins" => array(
		"project", 
	),
	
	"quickfields" => "project_id", 
	//~ "fields" => "project.*,project.project_status+0 AS project_status", 
	"field_data" => array(
		array("table" => "project", ), 
	),
	"primary" => "project.project_id", 
	"short_primary" => "project_id",
	"distinct" => GROUP_BY, 
);

$query["project"]=array(
	"base_table" => "project", 
	
	"joins" => array(
		"project_person", 
		"project_literature", 
	),
	
	"quickfields" => "project.project_id AS pk", 
	//~ "fields" => $fields["project"], 
	"field_data" => array(
		array("table" => "project", ), 
	),
	"distinct" => GROUP_BY,
	"order_obj" => array(
		array("field" => "project_name"),
	),
);

$query["project_count"]=$query["project"];
$query["project_count"]["joins"]=array("project_person_inner");
//~ $query["project_count"]["local_from"]="INNER JOIN project_person ON project.project_id=project_person.project_id";

// erst hier subqueries definieren
$query["project"]["subqueries"]=array( 
	array(
		"name" => "person", 
		"table" => "project_person", 
		"criteria" => array("project_person.project_id="), 
		"variables" => array("project_id"), 
		"conjunction" => "AND", 
		"forflags" => QUERY_EDIT, 
	), 
	array(
		"name" => "project_literature", 
		"table" => "project_literature", 
		"criteria" => array("project_literature.project_id="), 
		"variables" => array("project_id"), 
		"conjunction" => "AND", 
		"forflags" => QUERY_EDIT, 
		"action" => "recursive", 
		"order_obj" => array(
			array("field" => "literature_year", "order" => "DESC"),
		),
	), 
	array(
		"name" => "reaction_literature_for_project", 
		"table" => "reaction_literature_for_project", 
		"criteria" => array("reaction.project_id="), 
		"variables" => array("project_id"), 
		"conjunction" => "AND", 
		"forflags" => QUERY_EDIT, 
		"action" => "recursive", 
	), 
	array(
		"name" => "person_count", 
		"table" => "project_person", 
		"action" => "count", 
		"criteria" => array("project_person.project_id="), 
		"variables" => array("project_id"), 
		"conjunction" => "AND", 
		"forflags" => QUERY_LIST, 
	), 
	array(
		"name" => "reaction_count", 
		"table" => "reaction", 
		"action" => "count", 
		"criteria" => array("reaction.project_id="), 
		"variables" => array("project_id"), 
		"conjunction" => "AND", 
		"forflags" => QUERY_LIST, 
	), 
	array(
		"name" => "project_literature_count", 
		"table" => "project_literature", 
		"action" => "count", 
		"criteria" => array("project_literature.project_id="), 
		"variables" => array("project_id"), 
		"conjunction" => "AND", 
		"forflags" => QUERY_LIST, 
	), 
);

//~ $query["my_projects"]=$query["project"];
$query["project_literature"]=array(
	"base_table" => "project_literature", 
	
	"joins" => array(
		"literature", "sci_journal", 
	),
	
	//~ "quickfields" => "project_literature.project_literature_id", 
	"fields" => $fields["literature"], // "project_literature.*,sci_journal.*,".
	"field_data" => array(
		array("table" => "project_literature", ), 
		array("table" => "sci_journal", ), 
		array("table" => "literature", ), 
	),
	"order_obj" => array(
		array("field" => "literature_year", "order" => "DESC", ),
		array("field" => "sci_journal_name", ),
		array("field" => "literature_volume", ),
		array("field" => "page_low", ),
	),
	"subqueries" => array(
		$subqueries["author"], 
	), 
);

$query["reaction_literature_for_project"]=array(
	"base_table" => "literature", 
	
	"joins" => array(
		"reaction_literature", "reaction", "sci_journal", 
	),
	
	//~ "quickfields" => "project_literature.project_literature_id", 
	"fields" => $fields["literature"], // reaction.reaction_id, wg DISTINCT "sci_journal.*,".
	"field_data" => array(
		array("table" => "sci_journal", ), 
		array("table" => "literature", ), 
	),
	"order_obj" => array(
		array("field" => "literature_year", "order" => "DESC", ),
		array("field" => "sci_journal_name", ),
		array("field" => "literature_volume", ),
		array("field" => "page_low", ),
	),
	"distinct" => GROUP_BY,
	"subqueries" => array( 
		$subqueries["author"], 
	), 
);

$query["project_person"]=array(
	"base_table" => "project_person",
	
	"joins" => array(
		"person", "institution", 
	),
	
	"quickfields" => "person_id", 
	//~ "fields" => "institution.*,".$fields["person"], 
	"field_data" => array(
		array("table" => "institution", ), 
		array("table" => "person", "skip_types" => array("BLOB"), ), 
	),
	"primary" => "person.person_id", 
	"short_primary" => "person_id",
	"distinct" => GROUP_BY,
	"order_obj" => array(
		array("field" => "last_name"),
		array("field" => "first_name"),
	),
);

$query["reaction"]=array( // add ENTITY
	"base_table" => "reaction", 
	//~ "join_tables" => array("reaction","lab_journal","reaction_chemical","analytical_data","reaction_property"),
	"join_1n" => array(
		"reaction_chemical" => array(
			"fk_sub" => "reaction_chemical.reaction_id", 
		), 
		"reaction_property" => array(), 
		"analytical_data" => array(), 
	),
	
	"joins" => array(
		"lab_journal", "project", "project_person", "reaction_chemical", "units_ref_amount", "prod1", "reaction_literature", // "reaction_type", 
	),
	
	"distinct" => GROUP_BY, // do not make probs with project join
	"cache_mode" => CACHE_INDIVIDUAL, // due to filtering of project_members_only
	"quickfields" => "reaction.reaction_id AS pk", 
	"fields" => $fields["reaction"].",UNIX_TIMESTAMP(reaction_changed_when) AS reaction_archive_last,(lab_journal.lab_journal_status<=".lab_journal_open." AND reaction.status<".reaction_open.") AS allowEdit,(lab_journal.lab_journal_status<=".lab_journal_open.") AS allowAdd", 
	"field_data" => array(
		array("table" => "reaction", ), 
		array("table" => "lab_journal", ), 
		array("table" => "project", ), 
	),
	
	"clientCache" => array(
		"detail_cache_range" => 4, 
		"fast_cache_range" => 10, 
		"min_reload" => 3, 
		"max_reload" => 15, 
		"force_distance" => 2, 
		"fastmodeWait" => 300, 
		"fastmodeInt" => 600, 
		"initLoadDelay" => 1300, 
		"maxDatasets" => 80, 
	),
	
	"order_obj" => array(
		//~ array("field" => "reaction.reaction_started_when"),
		array("field" => "lab_journal.lab_journal_code"),
		array("field" => "reaction.nr_in_lab_journal"),
	),
	"showPerPageSelect" => true,
);

/* $query["reaction_archive_last"]=array( // add ENTITY
	"base_table" => "reaction_archive", 
	"fields" => "UNIX_TIMESTAMP(reaction_changed_when) AS reaction_archive_last",
); */

$query["reaction_copy"]=array(
	"base_table" => "reaction", 
	
	"joins" => array(
		"lab_journal", "project", "project_person", "units_ref_amount", 
	),
	
	"distinct" => GROUP_BY, // do not make probs with project join
	"cache_mode" => CACHE_INDIVIDUAL, // due to filtering of project_members_only
	"quickfields" => "reaction.reaction_id AS pk,status+0 AS status", 
	"fields" => $fields["reaction"], 
	"field_data" => array(
		array("table" => "reaction", ), 
		array("table" => "lab_journal", ), 
	),
	"order_obj" => array(
		array("field" => "reaction.lab_journal_id"),
		array("field" => "nr_in_lab_journal"),
	),
	"subqueries" => array(
		$subqueries["reaction_property"],
		$subqueries["reactants_copy"], // no JOIN to storage, which makes problems with versioning otherwise
		$subqueries["reagents_copy"], // no JOIN to storage, which makes problems with versioning otherwise
		$subqueries["products_copy"], // no JOIN to storage, which makes problems with versioning otherwise
	), 
);

$query["reaction_reaction_carried_out_by"]=array(
	"base_table" => "reaction", 
	"joins" => array(
		"lab_journal", 
	),
	"quickfields" => "reaction.reaction_id AS pk", 
	"fields" => "reaction_carried_out_by", // must be really DISTINCT to remove duplicates, GROUP BY is obsolete
	"distinct" => DISTINCT,
);

$query["reaction_count"]=array( // no filtering of secret stuff here
	"base_table" => "reaction", 
	"joins" => array(
		"lab_journal", // get lab_journal_id,person_id for filtering
	),
	"fields" => "COUNT(reaction_id) AS count",
);

// "subqueries" => array( array("name" => name, "table" => table, "criteria" => array(criteria1, criteria2), "variables" => array(variable1, variable2), "conjunction" => "AND", "forflags" => forflags, "order_obj" => order_obj) ));
$query["reaction_count_chemical"]=$query["reaction"];
$query["reaction_count_chemical"]["joins"]=array("reaction_chemical");
//~ $query["reaction_count_chemical"]["local_from"]="INNER JOIN reaction_chemical ON reaction.reaction_id=reaction_chemical.reaction_id"; // add ENTITY

$query["reaction_count_literature"]=$query["reaction"];
$query["reaction_count_literature"]["joins"]=array("reaction_literature");
//~ $query["reaction_count_literature"]["local_from"]="LEFT OUTER JOIN reaction_literature ON reaction.reaction_id=reaction_literature.reaction_id"; // add ENTITY

$query["reaction"]["subqueries"]=array(
	$subqueries["reaction_property"], 
	$subqueries["reactants"], 
	$subqueries["reagents"], 
	$subqueries["products"], 
	$subqueries["analytical_data"], 
	$subqueries["literature"], 
	array(
		"name" => "chemical_storage_count", 
		"table" => "chemical_storage_for_molecule", 
		"action" => "count", 
		"criteria" => array("from_reaction_id="), 
		"variables" => array("reaction_id"), 
		"conjunction" => "AND", 
		"forflags" => QUERY_EDIT+QUERY_LIST, 
	), 
	array(
		"name" => "reaction_literature_count", 
		"table" => "reaction_literature", 
		"action" => "count", 
		"criteria" => array("reaction_literature.reaction_id="), 
		"variables" => array("reaction_id"), 
		"conjunction" => "AND", 
		"forflags" => QUERY_LIST, 
	), 
);

// Spezialabfrage für SMILES-Reparatur
$query["reaction_fix_smiles"]=array( // no filtering of secret stuff here // add ENTITY
	"base_table" => "reaction", 
	"fields" => "reaction_id,rxnfile_blob", 
);

$query["reaction_fix_html"]=array( // no filtering of secret stuff here // add ENTITY
	"base_table" => "reaction", 
	"fields" => "reaction_id,realization_text,realization_observation", 
);

$query["reaction_fix_smiles"]["subqueries"]=array( // no filtering of secret stuff here
	$subqueries["reactants"],
	$subqueries["reagents"],
	$subqueries["products"],
);

$query["reaction_literature"]=array(
	"base_table" => "reaction_literature", 
	//~ "join_tables" => array("reaction_literature","literature","sci_journal"), 
	
	"joins" => array(
		"literature", "sci_journal", 
	),
	
	"quickfields" => "reaction_literature.reaction_literature_id", 
	"fields" => $fields["literature"], //"reaction_literature.*,sci_journal.*,".
	"field_data" => array(
		array("table" => "reaction_literature", ), 
		array("table" => "sci_journal", ), 
		array("table" => "literature", ), 
	),
	"subqueries" => array( 
		array(
			"name" => "authors", 
			"table" => "author", 
			"criteria" => array("author.literature_id="), 
			"variables" => array("literature_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT+QUERY_LIST, 
		), 
	), 
);

// Unterabfrage
$query["reaction_chemical"]=array(
	"base_table" => "reaction_chemical", 
	
	// FIXME molecule, chemical_storage herausnehmen, macht mit other_db_id keinen Sinn
	"joins" => array(
		"units_mass_rc", "units_volume_rc", "units_amount_rc", "units_amount_conc", "reaction", "lab_journal", "chemical_storage", "storage", "person", "molecule", "molecule_instructions",
		//~ "chemical_storage", "storage", "person", // obsolete?
	),
	"distinct" => GROUP_BY,

	"quickfields" => "reaction_chemical.reaction_chemical_id AS pk", 
	"fields" => $fields["reaction_chemical"].",chemical_storage.storage_id,chemical_storage.compartment,storage.storage_name"
		// performance-critical, may have to remove it
		.",(chemical_storage.safety_sheet_blob IS NOT NULL OR molecule.default_safety_sheet_blob IS NOT NULL) AS has_msds,(molecule_instructions.file_blob IS NOT NULL) AS has_molecule_instructions", //~ .",lab_journal.lab_journal_code,reaction.nr_in_lab_journal,
	"field_data" => array(
		array("table" => "reaction_chemical", ), 
		array("table" => "person", "skip_types" => array("BLOB"), ), 
	),
	"order_obj" => array(
		array("field" => "nr_in_reaction"),
	),
	/* // no info on storage for other groups
	"subqueries" => array( 
		array(
			"table" => "chemical_storage", // change to more limited FIXME
			"field_db_id" => "other_db_id", 
			"criteria" => array("chemical_storage.chemical_storage_id="), 
			"variables" => array("chemical_storage_id"), 
			"conjunction" => "AND", 
			"action" => "any_join", // get name etc from other db
		), 
	), 
	*/
);

$query["reaction_chemical_copy"]=array(
	"base_table" => "reaction_chemical", 
	
	// molecule, chemical_storage herausnehmen, macht mit other_db_id keinen Sinn
	
	"joins" => array(
		"units_mass_rc", "units_volume_rc", "units_amount_rc", "units_amount_conc", "reaction", "lab_journal", 
	),
	
	"quickfields" => "reaction_chemical.reaction_chemical_id AS pk", 
	"fields" => $fields["reaction_chemical"].",lab_journal.lab_journal_code,reaction.nr_in_lab_journal", 
	"field_data" => array(
		array("table" => "reaction_chemical", ), 
	),
	"order_obj" => array(
		array("field" => "nr_in_reaction"),
	),
);

// Spezialabfrage für SMILES-Reparatur
$query["reaction_chemical_fix_smiles"]=array(
	"base_table" => "reaction_chemical", 
	"fields" => "reaction_chemical_id,molfile_blob,smiles,smiles_stereo", 
);

// Spezialabfrage für Download
$query["reaction_chemical_gif"]=array(
	"base_table" => "reaction_chemical", 
	"fields" => "gif_file AS image,UNIX_TIMESTAMP(reaction_chemical_changed_when) AS last_changed", 
	
	"joins" => array(
		"project", "project_person", 
	),
);

// Spezialabfrage für Download
$query["reaction_chemical_svg"]=array(
	"base_table" => "reaction_chemical", 
	"fields" => "svg_file AS image,UNIX_TIMESTAMP(reaction_chemical_changed_when) AS last_changed", 
	
	"joins" => array(
		"project", "project_person", 
	),
);

// Spezialabfrage für Download
$query["reaction_chemical_mol"]=array(
	"base_table" => "reaction_chemical", 
	"fields" => "molfile_blob AS molfile,reaction_chemical_id AS pk,role+0 AS role", 
	
	"joins" => array(
		"project", "project_person", 
	),
);

// Spezialabfrage für Download
$query["reaction_gif"]=array( // add ENTITY
	"base_table" => "reaction", 
	"fields" => "rxn_gif_file AS image,UNIX_TIMESTAMP(reaction_changed_when) AS last_changed", 
	
	"joins" => array(
		"project", "project_person", 
	),
);

// Spezialabfrage für Download
$query["reaction_svg"]=array( // add ENTITY
	"base_table" => "reaction", 
	"fields" => "rxn_svg_file AS image,UNIX_TIMESTAMP(reaction_changed_when) AS last_changed", 
	
	"joins" => array(
		"project", "project_person", 
	),
);

// Spezialabfrage für Download
$query["reaction_mol"]=array( // add ENTITY
	"base_table" => "reaction", 
	"fields" => "rxnfile_blob AS molfile,reaction_id AS pk", 
	
	"joins" => array(
		"project", "project_person", 
	),
);

// Unterabfrage flat, d.h. name-value-pairs werden in Hauptergebnis integriert
$query["reaction_property"]=array(
	"base_table" => "reaction_property", 
	"quickfields" => "reaction_property.reaction_property_id", 
	"fields" => "reaction_property_name,reaction_property_value", 
);

$query["reaction_type"]=array(
	"base_table" => "reaction_type", 
	"quickfields" => "reaction_type_id AS pk", 
	//~ "fields" => "reaction_type.*", 
	"field_data" => array(
		array("table" => "reaction_type", ), 
	),
	"order_obj" => array(
		array("field" => "reaction_type_name"),
	),
);

$query["retention_time"]=array(
	"base_table" => "retention_time", 
	"quickfields" => "retention_time_id AS pk", 
	//~ "fields" => "retention_time.*", 
	"field_data" => array(
		array("table" => "retention_time", ), 
	),
);

$query["retention_time_structure"]=array(
	"base_table" => "retention_time", 
	"quickfields" => "retention_time_id AS pk", 
	"fields" => "bp_low,bp_high,(molecule.bp_press / press_units.unit_factor) AS bp_press,press_unit,
	IFNULL(molecule_names.molecule_name,reaction_chemical.standard_name) AS molecule_name,
	IFNULL(molecule.cas_nr,reaction_chemical.cas_nr) AS cas_nr,
	IFNULL(molecule.emp_formula,reaction_chemical.emp_formula) AS emp_formula,
	IFNULL(molecule.mw,reaction_chemical.mw) AS mw,
	reaction_chemical.reaction_chemical_id", 
	"field_data" => array(
		array("table" => "retention_time", ), 
	),
	"joins" => array(
		"molecule", "molecule_names", "press_units", "reaction_chemical", 
	),
	"distinct" => GROUP_BY, 
);


?>