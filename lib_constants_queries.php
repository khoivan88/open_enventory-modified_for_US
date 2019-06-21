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

$fields["reaction"]="(reaction.ref_amount / units_ref_amount.unit_factor) AS ref_amount";
$fields["reaction_chemical"]="(reaction_chemical.m_brutto / units_mass_rc.unit_factor) AS m_brutto,
(reaction_chemical.m_tara / units_mass_rc.unit_factor) AS m_tara,
(reaction_chemical.volume / units_volume_rc.unit_factor) AS volume,
(reaction_chemical.rc_amount / units_amount_rc.unit_factor) AS rc_amount,
(reaction_chemical.rc_conc / units_amount_conc.unit_factor) AS rc_conc";

$fields["molecule"]="(molecule.bp_press / press_units.unit_factor) AS bp_press"; // ohne sdb, fingerprints, molecule_serialized
$fields["molecule_for_reaction"]="molecule_names.molecule_name AS standard_name,molecule.molecule_id,cas_nr,smiles,smiles_stereo,molfile_blob,emp_formula,mw,density_20,safety_r,safety_h,safety_s,safety_p,safety_sym,safety_sym_ghs";

$fields["molecule_property"]="molecule_property.molecule_id,molecule_property_id,source,molecule_property.class,
(molecule_property.value_low / units.unit_factor) AS value_low,(molecule_property.value_high / units.unit_factor) AS value_high,
molecule_property.unit,conditions,molecule_property_comment,acquired,molecule_property_created_by,molecule_property_created_when,molecule_property_changed_by,molecule_property_changed_when";

$fields["molecule_instructions"]="molecule_instructions.molecule_id,molecule_instructions_id,lang,betr_anw_gefahren,betr_anw_schutzmass,betr_anw_schutzmass_sym,betr_anw_verhalten,betr_anw_verhalten_sym,
betr_anw_erste_h,betr_anw_erste_h_sym,betr_anw_entsorgung,molecule_instructions_comment,
molecule_instructions_created_by,molecule_instructions_created_when,molecule_instructions_changed_by,molecule_instructions_changed_when";

$fields["chemical_storage_for_reaction"]="(chemical_storage.amount / units_amount.unit_factor) AS amount,
(chemical_storage.actual_amount / units_amount.unit_factor) AS actual_amount,
units_amount.unit_type AS amount_unit_type,
(chemical_storage.chemical_storage_conc / units_conc.unit_factor) AS rc_conc,
chemical_storage_conc_unit AS rc_conc_unit,amount_unit";

$fields["chemical_storage"]="(chemical_storage.tmd / units_tmd.unit_factor) AS tmd,
(chemical_storage.amount / units_amount.unit_factor) AS amount,
(chemical_storage.actual_amount / units_amount.unit_factor) AS actual_amount,
units_amount.unit_type AS amount_unit_type,
(chemical_storage.chemical_storage_conc / units_conc.unit_factor) AS chemical_storage_conc";

// literature.literature_id,literature.sci_journal_id,literature_year,literature_volume,issue,page_low,page_high,doi,isbn,keywords,literature_group,literature_created_by,literature_created_when,literature_changed_by,literature_changed_when,
$fields["literature"]="(literature_blob IS NOT NULL) AS has_literature_blob";

// subqueries
$subqueries["analytical_data"]=array(
	"name" => "analytical_data", 
	"table" => "analytical_data", 
	"criteria" => array("analytical_data.reaction_id="), 
	"variables" => array("reaction_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_EDIT+QUERY_LIST, 
	"action" => "recursive", 
); // gc_peak mitholen

$subqueries["analytical_data_image"]=array(
	"name" => "analytical_data_image", 
	"table" => "analytical_data_image", 
	"criteria" => array("analytical_data_image.analytical_data_id="), 
	"variables" => array("analytical_data_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_EDIT, 
);

$subqueries["gc_peak"]=array(
	"name" => "gc_peak", 
	"table" => "gc_peak", 
	"criteria" => array("gc_peak.analytical_data_id="), 
	"variables" => array("analytical_data_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_EDIT, 
);

$subqueries["literature"]=array(
	"name" => "reaction_literature", 
	"table" => "reaction_literature", 
	"criteria" => array("reaction_literature.reaction_id="), 
	"variables" => array("reaction_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_EDIT, 
	"action" => "recursive", 
	"order_obj" => array(
		array("field" => "literature_year", "order" => "DESC"),
	),
);

$subqueries["author"]=array(
	"name" => "authors", 
	"table" => "author", 
	"criteria" => array("author.literature_id="), 
	"variables" => array("literature_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_EDIT+QUERY_LIST, 
);

$subqueries["reaction_property"]=array(
	"name" => "reaction_property", 
	"table" => "reaction_property", 
	"criteria" => array("reaction_property.reaction_id="), 
	"variables" => array("reaction_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_EDIT+QUERY_LIST, 
	"action" => "flat", 
	"nameField" => "reaction_property_name", 
	"valueField" => "reaction_property_value", 
); 

$subqueries["reactants"]=array(
	"name" => "reactants", 
	"table" => "reaction_chemical", 
	"criteria" => array("reaction_chemical.role=1 AND reaction_chemical.reaction_id="), 
	"variables" => array("reaction_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_EDIT+QUERY_LIST, 
	//~ "ignore_archive" => true, 
	"order_obj" => array(
		array("field" => "nr_in_reaction"),
	),
);

$subqueries["reagents"]=array(
	"name" => "reagents", 
	"table" => "reaction_chemical", 
	"criteria" => array("reaction_chemical.role=2 AND reaction_chemical.reaction_id="), 
	"variables" => array("reaction_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_EDIT+QUERY_LIST, 
	//~ "ignore_archive" => true, 
	"order_obj" => array(
		array("field" => "nr_in_reaction"),
	),
);

$subqueries["products"]=array(
	"name" => "products", 
	"table" => "reaction_chemical", 
	"criteria" => array("reaction_chemical.role=6 AND reaction_chemical.reaction_id="), 
	"variables" => array("reaction_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_EDIT+QUERY_LIST, 
	//~ "ignore_archive" => true, 
	"order_obj" => array(
		array("field" => "nr_in_reaction"),
	),
);

// avoid trouble with archive_entity
$subqueries["reactants_copy"]=$subqueries["reactants"];
$subqueries["reactants_copy"]["table"]="reaction_chemical_copy";
$subqueries["reagents_copy"]=$subqueries["reagents"];
$subqueries["reagents_copy"]["table"]="reaction_chemical_copy";
$subqueries["products_copy"]=$subqueries["products"];
$subqueries["products_copy"]["table"]="reaction_chemical_copy";

require_once "lib_constants_queries_admin.php";
require_once "lib_constants_queries_inventory.php";
require_once "lib_constants_queries_lab_journal.php";
require_once "lib_constants_queries_analytics.php";
require_once "lib_constants_queries_literature.php";
require_once "lib_constants_queries_order_system.php";

?>
