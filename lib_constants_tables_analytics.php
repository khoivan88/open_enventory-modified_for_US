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

$tables["analytical_data_image"]=array(
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin+_lj_edit+_lj_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"reaction" => array("condition" => "analytical_data_image.reaction_id=reaction.reaction_id", "archive_condition" => "reaction.reaction_archive_id=analytical_data_image.archive_entity_id", ),
		"project" => array("condition" => "project.project_id=reaction.project_id", ),
		"project_person" => array("condition" => "project.project_id=project_person.project_id", ),
	),
	
	"remoteFilter" => $reactionFilter, "defaultSecret" => true, "recordCreationChange" => true, 
	"versioning" => true, "recordCreationChange" => true, 
	
	"fields" => array(
		"analytical_data_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "analytical_data_id", ),
		"project_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "project", ),
		"reaction_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction", ),
		"image_no" => array("type" => "INT", "search" => "auto", "index" => true, ), // 1,2,...
		"image_comment" => array("type" => "TEXT", "search" => "auto", ),
		"analytical_data_graphics_blob" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ),
		"analytical_data_svg_blob" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ),
		"analytical_data_csv" => array("type" => "MEDIUMTEXT"), // interactive viewer
		"analytical_data_graphics_type" => array("type" => "TINYTEXT"), // mime
		"analytical_data_display_settings"=> array("type" => "INT"), // for printing the lab journal, also marks default property
	), 
);

$tables["analytical_data"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read+_barcode_user, 
	"writePerm" => _lj_admin+_lj_edit+_lj_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"reaction" => array("condition" => "analytical_data.reaction_id=reaction.reaction_id", "archive_condition" => "reaction.reaction_archive_id=analytical_data.archive_entity_id", ),
		"project" => array("condition" => "project.project_id=reaction.project_id", ),
		"project_person" => array("condition" => "project.project_id=project_person.project_id", ),
		"lab_journal" => array("condition" => "reaction.lab_journal_id=lab_journal.lab_journal_id", ),
		"reaction_chemical" => array("condition" => "analytical_data.reaction_chemical_id=reaction_chemical.reaction_chemical_id", "archive_condition" => "analytical_data.archive_entity_id=reaction_chemical.archive_entity_id", ),
	),
	
	"remoteFilter" => $reactionFilter, "defaultSecret" => true, "recordCreationChange" => true, 
	"versioning" => true, 
	"fields" => array(
		"nr_in_reaction" => array("type" => "TINYINT"),
		"project_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "project", ),
		"molecule_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "molecule", ), 
		"chemical_storage_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "chemical_storage", ), 
		"reaction_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction", ), // ???
		"reaction_chemical_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction_chemical", ), // wird beim speichern der reaktion gesetzt
		"fraction_no" => array("type" => "TINYTEXT", "search" => "auto", "index" => "(10)"),
		"analytical_data_identifier" => array("type" => "TINYTEXT", "index" => "(10)"), // (LabJ_code)_(nr)_(type_code)_(device_driver)_(nr) textliche Identifikation
		"analytical_data_raw_blob" => array("type" => "MEDIUMBLOB"), // rohdaten, zip
		"analytical_data_blob" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ), // bearbeitete daten, zip
		"measured_by" => array("type" => "TINYTEXT", "search" => "auto"), 
		"solvent" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "solvent", ), // (in particular for NMR), use this perhaps also for solvents used in reations
		"analytical_data_interpretation" => array("type" => "TEXT", "search" => "auto"),
		"analytical_data_comment" => array("type" => "TEXT", "search" => "auto"),
		"analytical_data_properties_blob" => array("type" => "MEDIUMBLOB"),
		"analytical_data_graphics_blob" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ),
		"analytical_data_svg_blob" => array("type" => "MEDIUMBLOB", "flags" => FIELD_IMAGE, ),
		"analytical_data_csv" => array("type" => "MEDIUMTEXT"), // interactive viewer
		"analytical_data_graphics_type" => array("type" => "TINYTEXT"), // mime
		"analytical_data_link_url" => array("type" => "TINYTEXT"), // mime
		"analytical_data_display_settings"=> array("type" => "INT"), // for printing the lab journal, also marks default property
		"analytics_method_id" => array("type" => "INT UNSIGNED", "fk" => "analytics_method", ),
		"analytics_method_name" => array("type" => "TINYTEXT", "search" => "auto"),
		"analytics_method_text" => array("type" => "TEXT", "search" => "auto"),
		"analytics_type_id" => array("type" => "INT UNSIGNED", "fk" => "analytics_type", ),
		"analytics_type_name" => array("type" => "TINYTEXT", "search" => "auto", "index" => "(10)", ),
		"analytics_type_code" => array("type" => "TINYTEXT", "index" => "(5)", ),
		"analytics_type_text" => array("type" => "TEXT", "search" => "auto"), 
		"analytics_device_id" => array("type" => "INT UNSIGNED", "fk" => "analytics_device", ),
		"analytics_device_name" => array("type" => "TINYTEXT", "search" => "auto"),
		"analytics_device_driver" => array("type" => "TINYTEXT"), 
		"analytical_data_uid" => array("type" => "VARBINARY(128)", ), 
		"analytical_data_ext_archive_id" => array("type" => "TINYTEXT", ), 
	), 
); // Daten zu Produkt/Zwischenanalytik; Methode, etc. werden einkopiert, weil sonst eine nachträgliche Änderung dort Probleme macht
		// analytical_data_comment: zB Lömi CDCl3, T=0°C,...
		// analytical_data_identifier: FR003.h => automatisierte Übertragung


$tables["analytics_method"]=array(
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin+_lj_edit+_lj_edit_own, 
	
	"joins" => array( // list of *possible* JOINS
		"analytics_type" => array("condition" => "analytics_method.analytics_type_id=analytics_type.analytics_type_id", ),
		"analytics_device" => array("condition" => "analytics_method.analytics_device_id=analytics_device.analytics_device_id", ),
	),
	
	"createDummy" => true, "useDisabled" => true, "recordCreationChange" => true, 
	"fields" => array(
		"analytics_method_name" => array("type" => "TINYTEXT", "search" => "auto"),
		"analytics_method_text" => array("type" => "TEXT", "search" => "auto"),
		"analytics_type_id" => array("type" => "INT UNSIGNED", "fk" => "analytics_type", ),
		"analytics_device_id" => array("type" => "INT UNSIGNED", "fk" => "analytics_device", ),
		"analytics_device_options" => array("type" => "BLOB"), 
	), 
); // zB GC-Methode, 1H, DEPT135,...

$tables["retention_time"]=array( // also hplc, etc
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin+_lj_edit+_lj_edit_own, 
	
	"recordCreationChange" => true,  "createDummy" => true, 
	//~ "index" => "UNIQUE(analytics_device_id,analytics_method_id,molecule_id)", 
	
	"joins" => array( // list of *possible* JOINS
		"molecule" => array("condition" => "molecule.molecule_id=retention_time.molecule_id", ), // normally better quality of data
		"molecule_names" => array("condition" => "molecule.molecule_id=molecule_names.molecule_id AND molecule_names.is_standard", ),
		"press_units" => array("base_table" => "units", "condition" => "molecule.press_unit=press_units.unit_name", ),
		"reaction_chemical" => array("condition" => "reaction_chemical.reaction_chemical_id=retention_time.reaction_chemical_id", ),
	),
	
	"index" => array(
		"name" => "analytics_type_id_2", 
		"type" => "unique", 
		"fields" => array(
			"analytics_type_id", 
			"analytics_device_id", 
			"analytics_method_id", 
			"molecule_id", 
		), 
	), 
	"fields" => array(
		"analytics_type_id" => array("type" => "INT UNSIGNED", "index" => false, "fk" => "analytics_type", ), 
		"analytics_device_id" => array("type" => "INT UNSIGNED", "index" => false, "fk" => "analytics_device", ), 
		"analytics_method_id" => array("type" => "INT UNSIGNED", "index" => false, "fk" => "analytics_method", ), 
		"molecule_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "molecule", ), 
		"reaction_chemical_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction_chemical", ), 
		"smiles_stereo" => array("type" => "TEXT", "index" => " (10)"), 
		"smiles" => array("type" => "TEXT", "index" => " (10)"), 
		"retention_time" => array("type" => "DOUBLE"), 
		"response_factor" => array("type" => "DOUBLE"), 
	), 
);

// zum Eintragen der Flächenprozente
$tables["gc_peak"]=array( // also hplc, etc
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin+_lj_edit+_lj_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"remoteFilter" => $reactionFilter, "defaultSecret" => true, 
	"versioning" => true, 
	"fields" => array(
		"analytical_data_id" => array("type" => "INT UNSIGNED", "fk" => "analytical_data", ),
		"reaction_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction", ), // ???
		"reaction_chemical_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "reaction_chemical", ),
		"retention_time" => array("type" => "DOUBLE"), 
		"area_percent" => array("type" => "DOUBLE"), 
		"gc_peak_comment" => array("type" => "TEXT"), 
		"gc_yield" => array("type" => "DOUBLE"), 
		"response_factor" => array("type" => "DOUBLE"), 
		"project_id" => array("type" => "INT UNSIGNED", "fk" => "project", ),
	), 
);

$tables["xy_data"]=array( // also hplc, etc
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _lj_admin+_lj_edit+_lj_edit_own+_chemical_edit+_chemical_edit_own, 
	"readPermRemote" => _remote_read, 
	
	"remoteFilter" => $reactionFilter, "defaultSecret" => true, 
	"versioning" => true, 
	"fields" => array(
		"analytical_data_id" => array("type" => "INT UNSIGNED", "fk" => "analytical_data", ),
		"retention_time" => array("type" => "DOUBLE"), // in min
		"val_x" => array("type" => "DOUBLE"), 
		"val_y" => array("type" => "DOUBLE"), 
		"project_id" => array("type" => "INT UNSIGNED", "fk" => "project", ), // access control
	), 
);

$tables["analytics_device"]=array(
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin, 
	
	"joins" => array( // list of *possible* JOINS
		"analytics_type" => array("condition" => "analytics_device.analytics_type_id=analytics_type.analytics_type_id", ),
		"analytics_device_permission" => array("condition" => "analytics_device_permission.analytics_device_id=analytics_device.analytics_device_id", ),
	),
	
	"createDummy" => true, "useDisabled" => true, "recordCreationChange" => true, 
	"fields" => array(
		"analytics_device_name" => array("type" => "TINYTEXT", "search" => "auto", "index" => " (10)",),
		"analytics_device_driver" => array("type" => "TINYTEXT"),
		"analytics_type_id" => array("type" => "INT UNSIGNED", "fk" => "analytics_type", ), 
		"analytics_device_url" => array("type" => "TEXT"),
		"analytics_device_username" => array("type" => "TINYTEXT"),
		"analytics_device_password" => array("type" => "TINYTEXT"),
		"analytics_device_default_permission" => array("type" => "ENUM", "values" => array("blacklist","whitelist"), ), 
		"analytics_device_options" => array("type" => "BLOB"), 
	), 
); // zB 400er, 600er,GC-MS1

$tables["analytics_device_permission"]=array(
	"readPerm" => _lj_read+_lj_read_all, 
	"writePerm" => _lj_admin, 
	
	"fields" => array(
		"analytics_device_id" => array("type" => "INT UNSIGNED", "fk" => "analytics_device", ), 
		"person_id" => array("type" => "INT UNSIGNED", "fk" => "person", ), 
		"permission" => array("type" => "TINYINT", ), 
	), 
);

$tables["analytics_type"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _lj_admin, 
	
	"createDummy" => true, "recordCreationChange" => true, 
	"fields" => array(
		"analytics_type_name" => array("type" => "TINYTEXT", "search" => "auto", "index" => " (10)", ),
		"analytics_type_code" => array("type" => "TINYTEXT"),
		"analytics_type_text" => array("type" => "TEXT", "search" => "auto"),
		"priority" => array("type" => "SMALLINT UNSIGNED"), 
	), 
); // zB NMR, GC, GC-MS


?>