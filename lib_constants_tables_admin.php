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

// Very general stuff, also units etc

$tables["institution"]=array(
	"readPerm" => _lj_read+_lj_read_all+_chemical_read, 
	"writePerm" => _admin+_storage_modify, 
	
	"joins" => array( // list of *possible* JOINS
		"institution_code" => array("condition" => "institution.institution_id=institution_code.institution_id", ), 
		"order_comp" => array("condition" => "institution.institution_id=order_comp.institution_id", ), 
		"accepted_order" => array("condition" => "institution.institution_id=accepted_order.vendor_id", ), 
	),
	
	"merge" => array("nameField" => "institution_name"), "createDummy" => true, "recordCreationChange" => true, 
	"fields" => array(
		"institution_name" => array("type" => "TINYTEXT", "search" => "auto", "index" => " (10)", ),
		"person_name" => array("type" => "TINYTEXT", "search" => "auto", ),
		"department_name" => array("type" => "TINYTEXT", "search" => "auto", ),
		"city" => array("type" => "TINYTEXT", "search" => "auto"),
		"postcode" => array("type" => "TINYTEXT", "search" => "auto"),
		"country" => array("type" => "TINYTEXT", "search" => "auto"),
		"street" => array("type" => "TINYTEXT", "search" => "auto"),
		"street_number" => array("type" => "SMALLINT UNSIGNED", "search" => "auto"),
		"tel_no" => array("type" => "TINYTEXT", "search" => "auto"),
		"fax_no" => array("type" => "TINYTEXT", "search" => "auto"),
		"customer_id" => array("type" => "TINYTEXT", "search" => "auto", ),
		"institution_type" => array("type" => "SET", "values" => array("vendor","buyer","research","commercial"), "search" => "auto"), 
		"comment_institution" => array("type" => "TEXT"), 
	), 
);

$tables["institution_code"]=array(
	"readPerm" => _chemical_read, 
	"writePerm" => _admin+_storage_modify, 
	
	"createDummy" => true, 
	"fields" => array(
		"institution_id" => array("type" => "INT UNSIGNED", "fk" => "institution", ),
		"supplier_code" => array("type" => "TINYTEXT", "index" => " (6)", ), //  "VARCHAR(32) UNIQUE" ??
	), 
);

$tables["global_settings"]=array(
	"readPerm" => _chemical_read+_lj_read+_lj_read_all, 
	"writePerm" => _admin, 
	
	"noPk" => true, 
	"fields" => array(
		"name" => array("type" => "VARCHAR(255) NOT NULL PRIMARY KEY"),
		"value" => array("type" => "BLOB"), 
	), 
);

$tables["db_info"]=array( // public info like db_name, UID
	"readPerm" => _chemical_read+_lj_read+_lj_read_all, 
	"writePerm" => _admin, 
	"readPermRemote" => _remote_read, 
	
	"noPk" => true, 
	"fields" => array(
		"name" => array("type" => "VARCHAR(255) NOT NULL PRIMARY KEY"),
		"value" => array("type" => "BLOB"), 
	), 
);

$tables["other_db"]=array(
	"readPerm" => _chemical_read+_lj_read+_lj_read_all, 
	"writePerm" => _admin, 
	"readPermRemote" => _remote_read, 
	
	"remoteFields" => array("other_db_id", "db_beauty_name"), // do not expose any sensitive data like host, user or pw. Use UUID for mapping.
	
	"createDummy" => true, "useDisabled" => true, "recordCreationChange" => true, 
	"fields" => array(
		"host" => array("type" => "TINYTEXT"),
		"db_name" => array("type" => "TINYTEXT"),
		"db_user" => array("type" => "TINYTEXT"),
		"db_pass" => array("type" => "TINYTEXT"),
		"db_beauty_name" => array("type" => "TINYTEXT"),
		"capabilities" => array("type" => "SET", "values" => array("storage","order","elj"), ),
		"priority" => array("type" => "SMALLINT UNSIGNED"), 
	), 
);

$tables["predefined_permissions"]=array(
	"readPerm" => _chemical_read+_lj_read+_lj_read_all, 
	"writePerm" => _admin, 
	"recordCreationChange" => true, 
	
	"fields" => array(
		"permission_level_name" => array("type" => "TINYTEXT", "search" => "auto", ), 
		"permissions" => array("type" => "INT", ), 
	), 
);

$tables["person"]=array(
	"readPerm" => _chemical_read+_lj_read+_lj_read_all, 
	"writePerm" => _admin, 
	"readPermRemote" => _remote_read, 
	
	"remoteFields" => array("person_id", "last_name", "first_name", "title", "nee", "sigle", "permissions", "username", "person_barcode", "email"), 
	
	"joins" => array( // list of *possible* JOINS
		"institution" => array("condition" => "person.institution_id=institution.institution_id", ),
		"project_person" => array("condition" => "person.person_id=project_person.person_id", ),
	),
	
	"useDisabled" => true,  "createDummy" => true, "recordCreationChange" => true, 
	"fields" => array(
		"last_name" => array("type" => "TINYTEXT", "search" => "auto", ), 
		"first_name" => array("type" => "TINYTEXT", "search" => "auto", ), 
		"title" => array("type" => "TINYTEXT", "search" => "auto", ), 
		"nee" => array("type" => "TINYTEXT", "search" => "auto", ), 
		"sigle" => array("type" => "TINYTEXT", "search" => "auto", ), 
		"remote_host" => array("type" => "TINYTEXT", ), 
		"supervisor_id" => array("type" => "INT UNSIGNED", "fk" => "person", ), 
		"institution_id" => array("type" => "INT UNSIGNED", "fk" => "institution", ), 
		"permissions" => array("type" => "INT", ), 
		"predefined_permissions_id" => array("type" => "INT UNSIGNED", "fk" => "predefined_permissions", ), 
		"username" => array("type" => "VARCHAR(32) UNIQUE", "search" => "auto", ), 
		"preferred_language" => array("type" => "TINYTEXT", "allowSelfChange" => true, ), 
		"cost_centre" => array("type" => "TINYTEXT", ), 
		"acc_no" => array("type" => "TINYTEXT", ), 
		"cost_limit" => array("type" => "DOUBLE", ), 
		"cost_limit_currency" => array("type" => "TINYTEXT", ), 
		"preferences" => array("type" => "BLOB", "allowSelfChange" => true, ), 
		"email" => array("type" => "TINYTEXT", "allowSelfChange" => true, ), 
		"email_chemical_supply" => array("type" => "TINYTEXT", ), 
	), 
); // remote_host wird nicht angezeigt, ist aber nötig (normale Benutzer = localhost, remote-Benutzer= *) preferences blob eingefügt Daten serialisieren (ist ja nur ein Speicher), email-Adresse eingefügt

$tables["cache"]=array(
	"readPerm" => _chemical_read+_lj_read+_lj_read_all, 
	"writePerm" => _chemical_read+_lj_read+_lj_read_all, 
	
	"fields" => array(
		"person_id" => array("type" =>  "INT UNSIGNED", "fk" => "person", ),
		"cache_sess_id"=> array("type" => "TINYTEXT", "index" => "(10)", ), 
		"query_md5" => array("type" => "BINARY(16)"), // to speed up get_all, contains table, dbs, order_by
		"created" => array("type" => "INT"),
		"last_update" => array("type" => "INT"),
		"cache_blob" => array("type" => "LONGBLOB"), 
	), 
);

$tables["change_notify"]=array(
	"readPerm" => _chemical_read+_lj_read+_lj_read_all+_remote_read, 
	"writePerm" => _admin+_storage_modify+
		_chemical_create+_chemical_edit+_chemical_edit_own+_chemical_borrow+_chemical_inventarise+_chemical_delete+
		_lj_admin+_lj_project+_lj_edit+_lj_edit_own+
		_order_order+_order_approve+_order_accept, 
	
	"noPk" => true, 
	"fields" => array( // readPerm 8 is ok
		"for_table" => array("type" => "TINYTEXT", "index" => "(10)", ),
		"pk" => array("type" => "INT UNSIGNED"),
		"made_when" => array("type" => "DATETIME"), 
	), 
);

$tables["lock_table"]=array(
	"readPerm" => _chemical_read+_lj_read+_lj_read_all+_remote_read, 
	"writePerm" => _admin+_storage_modify+
		_chemical_create+_chemical_edit+_chemical_edit_own+_chemical_borrow+_chemical_inventarise+_chemical_delete+
		_lj_admin+_lj_project+_lj_edit+_lj_edit_own+
		_order_order+_order_approve+_order_accept, 

	"noPk" => true, 
	"index" => array("type" => "unique", "name" => "for_table_pk", "fields" => array("for_table(10)","pk", ), ),
	"fields" => array( // readPerm 8 is ok
		"for_table" => array("type" => "TINYTEXT", ),
		"pk" => array("type" => "INT UNSIGNED", ),
		"locked_by" => array("type" => "TINYTEXT", ), 
		"locked_sess_id"=> array("type" => "TINYTEXT", "index" => "(10)", ), 
		"locked_when" => array("type" => "DATETIME", ), 
		"locked_type" => array("type" => "INT", ), // maybe we allow checkout with permanent locking one day
	), 
);

$tables["message"]=array(
	"readPerm" => _chemical_read+_lj_read+_lj_read_all, 
	"writePerm" => _chemical_edit+_lj_edit, 
	
	"joins" => array( // list of *possible* JOINS
		"person" => array("condition" => "message.from_person=person.person_id", ),
		"message_person" => array("condition" => "message.message_id=message_person.message_id", ),
	),
	
	"recordCreationChange" => true, // "noDelete" => true, 
	"fields" => array(
		"from_person" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "person", ),
		"issued" => array("type" => "DATETIME", "search" => "auto"),
		"message_subject" => array("type" => "TINYTEXT", "search" => "auto"),
		"message_text" => array("type" => "TEXT", "search" => "auto"),
		"priority" => array("type" => "ENUM", "values" => array("low","medium","high"), "search" => "auto", ),
		"do_until" => array("type" => "DATE", "search" => "auto"), 
		"every_xx_interval" => array("type" => "INT UNSIGNED"), // es wird immer nur die nächste Message ausgelöst, diese löst dann die wiedrum nächste aus !!!
		"interval_unit" => array("type" => "ENUM", "values" => array("none","day","week","month","year"), ), 
	), 
);

$tables["message_person"]=array(
	"readPerm" => _chemical_read+_lj_read+_lj_read_all, 
	"writePerm" => _chemical_edit+_chemical_edit_own+_lj_edit+_lj_edit_own, 
	
	"joins" => array( // list of *possible* JOINS
		"person" => array("condition" => "message_person.person_id=person.person_id", ),
	),
	
	"recordCreationChange" => true, 
	"fields" => array(
		"message_id" => array("type" => "INT UNSIGNED", "fk" => "message", ),
		"person_id" => array("type" => "INT UNSIGNED", "fk" => "person", ),
		"completion_status" => array("type" => "ENUM", "values" => array("unread","read","accepted","rejected","in_progress","done","completed"), ),
		"p_comment" => array("type" => "TEXT"), 
	), 
);

$tables["units"]=array(
	//~ "pk" => "unit_id", 
	"readPerm" => _chemical_read+_lj_read+_lj_read_all+_remote_read, 
	"writePerm" => _admin, 
	
	"useDisabled" => true, // "readPermRemote" => 8+16+64, 
	"fields" => array(
		"unit_name" => array("type" => "VARCHAR(10) UNIQUE", "collate" => COLLATE_BIN, ),
		"unit_factor" => array("type" => "DOUBLE"),
		"unit_type" => array("type" => "TINYTEXT", "collate" => COLLATE_BIN, ),
		"unit_is_standard" => array("type" => "BOOL"),
	), 
);

$tables["class"]=array(
	"readPerm" => _chemical_read+_lj_read+_lj_read_all+_remote_read, 
	"writePerm" => _admin, 
	//~ "readPermRemote" => _remote_read, // direct access already, unnecessary
	
	"fields" => array(
		"class_name" => array("type" => "TINYTEXT", "collate" => COLLATE_BIN, ),
		"default_unit" => array("type" => "TINYTEXT", "collate" => COLLATE_BIN, ),
		"class_type" => array("type" => "TINYTEXT", "collate" => COLLATE_BIN, ), // == unit_type
	), 
);


?>