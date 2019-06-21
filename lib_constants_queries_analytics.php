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

$query["analytical_data"]=array(
	"base_table" => "analytical_data", 
	//~ "join_tables" => array("analytical_data","reaction","lab_journal","reaction_chemical"),
	
	"joins" => array(
		"reaction", 
		"project", 
		"project_person", 
		"lab_journal", 
		"reaction_chemical", 
	),
	
	"quickfields" => "analytical_data_id AS pk", 
	/*
	"fields" => "analytical_data_id,analytical_data_identifier,analytical_data_link_url,analytical_data_comment,measured_by,analytical_data_properties_blob,analytical_data_interpretation,
analytics_type_id,analytics_type_name,analytics_type_code,
analytics_device_id,analytics_device_name,analytics_device_driver,
analytics_method_id,analytics_method_name,
(analytical_data_display_settings & 1)=1 AS default_for_type,
reaction.reaction_id,reaction.lab_journal_id,nr_in_lab_journal,status+0 AS status,
standard_name,role+0 AS role,reaction_chemical.nr_in_reaction,reaction_chemical.reaction_chemical_id,fraction_no,".$fields["lab_journal"], // hier ist person_id enthalten, wichtig
	*/
	"fields" => "analytical_data_properties_blob,(analytical_data_display_settings & 1)=1 AS default_for_type,reaction.reaction_id,reaction.lab_journal_id,nr_in_lab_journal,status+0 AS status,
standard_name,role+0 AS role,reaction_chemical.nr_in_reaction,reaction_chemical.reaction_chemical_id,LENGTH(analytical_data_csv)>0 AS has_interactive", 
	"field_data" => array(
		array("table" => "analytical_data", "skip_types" => array("BLOB","MEDIUMBLOB"), ), 
		//~ array("table" => "analytics_type", ), 
		//~ array("table" => "analytics_device", ), 
		//~ array("table" => "analytics_method", ), 
		array("table" => "lab_journal", ), 
	),
	"export_fields" => "analytical_data_blob",
	
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
	
	"distinct" => GROUP_BY, // do not make probs with project join
	"cache_mode" => CACHE_INDIVIDUAL, // due to filtering of project_members_only
	"order_obj" => array(
		array("field" => "analytics_type_name"),
		array("field" => "fraction_no"),
	),
	"subqueries" => array(
		$subqueries["gc_peak"], 
		$subqueries["analytical_data_image"], 
	),
);

$query["analytical_data_image"]=array(
	"base_table" => "analytical_data_image", 
	"fields" => "analytical_data_image_id,analytical_data_id,project_id,reaction_id,image_no,image_comment,analytical_data_display_settings",
);

$query["analytical_data_image_gif"]=array(
	"base_table" => "analytical_data_image", 
	"fields" => "analytical_data_graphics_blob AS image,analytical_data_graphics_type,UNIX_TIMESTAMP(analytical_data_image_changed_when) AS last_changed",
	
	"joins" => array(
		"reaction", "project", "project_person", 
	),
);

$query["analytical_data_image_csv"]=array(
	"base_table" => "analytical_data_image", 
	"fields" => "analytical_data_csv,UNIX_TIMESTAMP(analytical_data_image_changed_when) AS last_changed",
	
	"joins" => array(
		"reaction", "project", "project_person", 
	),
);

$query["analytical_data_check"]=array(
	"base_table" => "analytical_data", 
	//~ "join_tables" => array("analytical_data","reaction","lab_journal","reaction_chemical"),
	
	"joins" => array(
		"reaction", "project", "project_person", "lab_journal", 
	),
	
	"distinct" => GROUP_BY, // do not make probs with project join
	"cache_mode" => CACHE_INDIVIDUAL, // due to filtering of project_members_only
	"quickfields" => "analytical_data_id AS pk", 
	"fields" => "analytical_data_id,analytical_data_identifier,analytical_data_link_url,analytical_data_interpretation,
reaction.reaction_id,reaction.lab_journal_id,nr_in_lab_journal,status+0 AS status,reaction.project_id", 
	"field_data" => array(
		array("table" => "lab_journal", ), 
	),
); // sorgt für die Reihenfolge 1H,13C,GC

$query["analytical_data_simple"]=array(
	"base_table" => "analytical_data", 
	"quickfields" => "analytical_data_id AS pk", 
	"fields" => "analytical_data_identifier",
	"order_obj" => array(
		array("field" => "analytics_type_name"),
	),
); // sorgt für die Reihenfolge 1H,13C,GC

$query["analytical_data_gif"]=array(
	"base_table" => "analytical_data", 
	"fields" => "analytical_data_graphics_blob AS image,analytical_data_graphics_type,UNIX_TIMESTAMP(analytical_data_changed_when) AS last_changed", 
	
	"joins" => array(
		"reaction", "project", "project_person", 
	),
);

$query["analytical_data_csv"]=array(
	"base_table" => "analytical_data", 
	"fields" => "analytical_data_csv,UNIX_TIMESTAMP(analytical_data_changed_when) AS last_changed", 
	
	"joins" => array(
		"reaction", "project", "project_person", 
	),
);

$query["analytical_data_spz"]=array(
	"base_table" => "analytical_data", 
	
	"joins" => $query["analytical_data"]["joins"],
	
	"distinct" => GROUP_BY, // do not make probs with project join
	"cache_mode" => CACHE_INDIVIDUAL, // due to filtering of project_members_only
	"fields" => "analytical_data.analytical_data_id,analytical_data_identifier,analytical_data_link_url,analytics_type_code,analytics_device_driver,analytical_data_blob,analytical_data.reaction_id,analytical_data.project_id,reaction_chemical.molfile_blob,
lab_journal_code,nr_in_lab_journal,analytics_type_name,analytics_method_name", // analytical_data_raw_blob wird beim erstellen gespeichert , direkt auf analytical_data_blob kopiert und dann nicht mehr angetastet!!
);

$query["analytical_data_spz_orig"]=$query["analytical_data_spz"];
$query["analytical_data_spz_orig"]["fields"]="analytical_data_id,analytical_data_identifier,analytical_data_link_url,analytics_type_code,analytics_device_driver,analytical_data_raw_blob AS analytical_data_blob,analytical_data.reaction_id,reaction_chemical.molfile_blob,
lab_journal_code,nr_in_lab_journal,analytics_type_name,analytics_method_name";

$query["analytical_data_fix"]=array(
	"base_table" => "analytical_data", 
	"fields" => "analytical_data_id,analytical_data_identifier,analytical_data_link_url,analytics_type_code,analytics_device_driver,analytical_data_blob,analytical_data_graphics_blob,analytical_data_graphics_type", // analytical_data_raw_blob wird beim erstellen gespeichert , direkt auf analytical_data_blob kopiert und dann nicht mehr angetastet!!
);

$query["analytics_method"]=array(
	"base_table" => "analytics_method",
	//~ "join_tables" => array("analytics_method","analytics_device","analytics_type"),
	
	"joins" => array(
		"analytics_device", "analytics_type", 
	),
	
	"quickfields" => "analytics_method_id AS pk", 
	"field_data" => array( // must be this order, otherwise strange behaviour, values overwritten with NULL in certain cases
		array("table" => "analytics_method", ), 
		array("table" => "analytics_device", ), 
		array("table" => "analytics_type", ), 
	), 
);

$query["analytics_device"]=array(
	"base_table" => "analytics_device", 
	//~ "join_tables" => array("analytics_device","analytics_type"),
	
	"joins" => array(
		"analytics_type", 
	),
	
	"quickfields" => "analytics_device_id AS pk", 
	"field_data" => array(
		array("table" => "analytics_device", ), 
		array("table" => "analytics_type", ), 
	),
	"subqueries" => array( 
		array(
			"name" => "analytics_method_count", 
			"table" => "analytics_method", 
			"action" => "count", 
			"criteria" => array("analytics_method.analytics_device_id="), 
			"variables" => array("analytics_device_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
	), 
);

$query["analytics_type"]=array(
	"base_table" => "analytics_type", 
	"quickfields" => "analytics_type_id AS pk", 
	//~ "fields" => "analytics_type.*", 
	"field_data" => array(
		array("table" => "analytics_type", ), 
	),
	"subqueries" => array( 
		array(
			"name" => "analytics_device_count", 
			"table" => "analytics_device", 
			"action" => "count", 
			"criteria" => array("analytics_device.analytics_type_id="), 
			"variables" => array("analytics_type_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
	), 
);

$query["gc_peak"]=array(
	"base_table" => "gc_peak", 
	"quickfields" => "gc_peak_id AS pk", 
	//~ "fields" => "gc_peak.*", 
	"field_data" => array(
		array("table" => "gc_peak", ), 
	),
);


?>