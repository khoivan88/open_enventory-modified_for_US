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

$query["password_hash"]=array(
	"forceTable" => true,
	"local_from" => "mysql.user", // allowed because of forceTable
	"fields" => "user,host,password", 
);

function fixPasswordQuery() {
    global $db,$query;
   
    // fix for MySQL servers 5.7+
    $result=mysql_select_array_from_dbObj("CAST(version() AS DECIMAL(10,2)) AS version, version() AS versionstring;",$db);
    
    $versionarray=explode('-', $result[0]["versionstring"]);

    if ($result[0]["version"]>=5.7 && $versionarray[1]!="MariaDB") {
        $query["password_hash"]["fields"]="user,host,authentication_string AS password";
    }
}

$query["cache"]=array(
	"base_table" => "cache", 
	"quickfields" => "cache.cache_id AS pk,created,last_update", 
	//~ "fields" => "cache.*", 
	"field_data" => array(
		array("table" => "cache", ), 
	),
);

$query["change_notify"]=array(
	"base_table" => "change_notify", 
	"quickfields" => "pk", 
	"fields" => "pk",
	"distinct" => GROUP_BY, 
);

function procClass(& $resultset) { // stellt die lokalisierten Texte für class bereit
	for ($a=0;$a<count($resultset);$a++) {
		$resultset[$a]["class_name_local"]=s($resultset[$a]["class_name"]);
	}
}

$query["class"]=array(
	"base_table" => "class", 
	"quickfields" => "class_id AS pk", 
	"fields" => "class.*,IF(class.class_type LIKE \"Text\",\"text\",\"num_unit\") AS class_format", 
	"procFunction" => "procClass",
);

$query["db_beauty_name"]=array(
	"base_table" => "other_db", 
	"quickfields" => "other_db.other_db_id", 
	"fields" => "db_beauty_name", 
);

$query["global_settings"]=array(
	"base_table" => "global_settings", 
	"fields" => "value", 
	"primary" => "global_settings.name", 
	"short_primary" => "name", 
);

$query["institution"]=array(
	"base_table" => "institution", 
	
	"joins" => array(
		"institution_code", 
	),
	
	"quickfields" => "institution.institution_id AS pk", 
	"field_data" => array(
		array("table" => "institution", ), 
	),
	"order_obj" => array(
		array("field" => "institution_name"),
	),
	"subqueries" => array( 
		array(
			"name" => "institution_codes", 
			"table" => "institution_code", 
			"criteria" => array("institution_code.institution_id="), 
			"variables" => array("institution_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT+QUERY_PK_SEARCH, 
		), 
		array(
			"name" => "order_comp", 
			"table" => "order_comp", 
			"criteria" => array("order_comp.institution_id="), 
			"variables" => array("institution_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
		), 
		array(
			"name" => "order_comp_count", 
			"table" => "order_comp", 
			"action" => "count", 
			"criteria" => array("order_comp.institution_id="), 
			"variables" => array("institution_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
		array(
			"name" => "open_accepted_order_count", 
			"table" => "accepted_order", 
			"action" => "count", 
			"criteria" => array("accepted_order.central_order_status=1 AND accepted_order.vendor_id="), // not yet sent out
			"variables" => array("institution_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
	), 
	"distinct" => GROUP_BY, 
);

$query["institution_code"]=array(
	"base_table" => "institution_code", 
	"quickfields" => "institution_code_id AS pk", 
	"fields" => "institution_code.*", 
);

$query["message"]=array(
	"base_table" => "message", 
	//~ "join_tables" => array("message","person"),
	// join_1n
	
	"joins" => array(
		"person", 
	),
	
	"quickfields" => "message.message_id AS pk", 
	//~ "fields" => "message.*,priority+0 AS priority,".$fields["person"], 
	"field_data" => array(
		array("table" => "message", ), 
		array("table" => "person", "skip_types" => array("BLOB"), ), 
	),
	"order_obj" => array(
		array("field" => "issued", "order" => "DESC"),
	),
	"subqueries" => array( 
		array(
			"name" => "recipients", 
			"table" => "message_person", 
			"criteria" => array("message_person.message_id="), 
			"variables" => array("message_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT+QUERY_LIST, 
		), 
	), 
);

$query["message_new"]=$query["message"];
$query["message_new"]["joins"][]="message_person";
$query["message_new"]["cache_mode"]=CACHE_INDIVIDUAL;
$query["message_in"]=$query["message"];
$query["message_in"]["joins"][]="message_person";
$query["message_in"]["cache_mode"]=CACHE_INDIVIDUAL;
$query["message_out"]=$query["message"];
$query["message_out"]["cache_mode"]=CACHE_INDIVIDUAL;

// the rest is set in pageHeader() to make sure that person_id is set prior to definition

$query["message_person"]=array(
	"base_table" => "message_person", 
	//~ "join_tables" => array("message_person","person"),
	
	"joins" => array(
		"person", 
	),
	
	"quickfields" => "message_person.message_person_id", 
	//~ "fields" => "p_comment,completion_status+0 AS completion_status,".$fields["person"], 
	"field_data" => array(
		array("table" => "message_person", ), 
		array("table" => "person", "skip_types" => array("BLOB"), ), 
	),
);

$query["other_db"]=array(
	"base_table" => "other_db", 
	"quickfields" => "other_db.other_db_id AS pk", 
	//~ "fields" => "other_db.*,capabilities+0 AS capabilities", 
	"field_data" => array(
		array("table" => "other_db", ), 
	),
);

$query["person"]=array(
	"base_table" => "person", 
	//~ "join_tables" => array("person","institution"),
	
	"joins" => array(
		"institution", "project_person", 
	),
	
	"quickfields" => "person.person_id AS pk", 
	"fields" => "preferences", 
	"field_data" => array(
		array("table" => "institution", ), 
		array("table" => "person", "skip_types" => array("BLOB"), ), 
	),
	"distinct" => GROUP_BY,
	"order_obj" => array(
		array("field" => "last_name"),
		array("field" => "first_name"),
	),
	"subqueries" => array( 
		array(
			"name" => "project", 
			"table" => "person_project", 
			"criteria" => array("person_id="), 
			"variables" => array("person_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
		), 
		array(
			"name" => "project_count", 
			"table" => "project_count", 
			"action" => "count", 
			"criteria" => array("project_person.person_id="), 
			"variables" => array("person_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
		array(
			"name" => "borrowed", 
			"table" => "chemical_storage_for_person", 
			"criteria" => array("(borrowed_by_db_id=-1 OR borrowed_by_db_id IS NULL) AND borrowed_by_person_id="), // only own DB
			"variables" => array("person_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
		), 
		array(
			"name" => "owner_person_count", 
			"table" => "chemical_storage_for_person", 
			"action" => "count", 
			"criteria" => array("owner_person_id="), 
			"variables" => array("person_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
		array(
			"name" => "borrowed_count", 
			"table" => "chemical_storage_for_person", 
			"action" => "count", 
			"criteria" => array("(borrowed_by_db_id=-1 OR borrowed_by_db_id IS NULL) AND borrowed_by_person_id="),  // only own DB
			"variables" => array("person_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
		array(
			"name" => "lab_journal", 
			"table" => "lab_journal", 
			"criteria" => array("lab_journal.person_id="), 
			"variables" => array("person_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
		), 
		array(
			"name" => "lab_journal_count", 
			"table" => "lab_journal", 
			"action" => "count", 
			"criteria" => array("lab_journal.person_id="), 
			"variables" => array("person_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		),
		//~ array( // performance killer
			//~ "name" => "reaction_count", 
			//~ "table" => "reaction", 
			//~ "action" => "count", 
			//~ "criteria" => array("lab_journal.person_id="), 
			//~ "variables" => array("person_id"), 
			//~ "conjunction" => "AND", 
			//~ "forflags" => QUERY_LIST, 
		//~ ),
		array( // not in form yet
			"name" => "chemical_order", 
			"table" => "chemical_order", 
			"criteria" => array("chemical_order.ordered_by_person="), 
			"variables" => array("person_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
		), 
		array( // not in form yet
			"name" => "chemical_order_count", 
			"table" => "chemical_order", 
			"action" => "count", 
			"criteria" => array("chemical_order.ordered_by_person="), 
			"variables" => array("person_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		), 
	), 
);

$query["person_quick"]=array(
	"base_table" => "person", 
	"quickfields" => "person.person_id AS pk", 
	"fields" => "person_id,preferred_language,permissions+0 AS permissions,last_name,first_name,title,username,remote_host,person_disabled,person_barcode", 
);

$query["units"]=array(
	"base_table" => "units", 
	"quickfields" => "units_id AS pk", 
	"fields" => "*", 
);


?>