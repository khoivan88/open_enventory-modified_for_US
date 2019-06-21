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

$query["author"]=array(
	"base_table" => "author", 
	"quickfields" => "author_id", 
	//~ "fields" => "author.*", 
	"field_data" => array(
		array("table" => "author", ), 
	),
	"order_obj" => array(
		array("field" => "nr_in_literature"),
	),
);

$query["literature"]=array(
	"base_table" => "literature", 
	//~ "join_tables" => array("sci_journal","literature"), // FIXME change order
	
	"joins" => array(
		"sci_journal", 
		"project_literature", 
		"reaction_literature", 
	),
	
	"quickfields" => "literature.literature_id AS pk", 
	"fields" => $fields["literature"], //"sci_journal.*,".
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
	"distinct" => GROUP_BY,
);

$query["literature_project"]=array(
	"base_table" => "project_literature", 
	
	"joins" => array(
		"project", 
	),
	
	//~ "fields" => $fields["project"], 
	"field_data" => array(
		array("table" => "project", ), 
	),
);

$query["reaction_for_literature"]=array(
	"base_table" => "reaction_literature", 
	
	"joins" => array(
		"reaction","lab_journal", 
	),
	
	"fields" => "reaction.nr_in_lab_journal,reaction.reaction_id", // .$fields["lab_journal"], 
	"field_data" => array(
		array("table" => "lab_journal", ), 
	),
);

// Spezialabfrage für Download
$query["literature_pdf"]=$query["literature"];
$query["literature_pdf"]["fields"].=",literature_blob";

// erst jetzt Unterabfragen hinzufügen
$query["literature"]["subqueries"][]=array(
	"name" => "project_count", 
	"table" => "project_literature", 
	"action" => "count", 
	"criteria" => array("project_literature.literature_id="), 
	"variables" => array("literature_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_LIST, 
);

$query["literature"]["subqueries"][]=array(
	"name" => "reaction_count", 
	"table" => "reaction_count_literature", 
	"action" => "count", 
	"criteria" => array("reaction_literature.literature_id="), 
	"variables" => array("literature_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_LIST, 
);

$query["literature"]["subqueries"][]=array(
	"name" => "project", 
	"table" => "literature_project", 
	"criteria" => array("project_literature.literature_id="), 
	"variables" => array("literature_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_EDIT, 
);
$query["literature"]["subqueries"][]=array(
	"name" => "reaction", 
	"table" => "reaction_for_literature", 
	"criteria" => array("reaction_literature.literature_id="), 
	"variables" => array("literature_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_EDIT, 
);

// Spezialabfrage für Download
$query["literature_gif"]=array(
	"base_table" => "literature", 
	"fields" => "literature_graphics_blob AS image,literature_graphics_type,UNIX_TIMESTAMP(literature_changed_when) AS last_changed", 
);

$query["sci_journal"]=array(
	"base_table" => "sci_journal", 
	"quickfields" => "sci_journal_id AS pk", 
	//~ "fields" => "sci_journal.*", 
	"field_data" => array(
		array("table" => "sci_journal", ), 
	),
	"subqueries" => array( 
		array(
			"name" => "literature_count", 
			"table" => "literature", 
			"action" => "count", 
			"criteria" => array("literature.sci_journal_id="), 
			"variables" => array("sci_journal_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		),
		array(
			"name" => "literature", 
			"table" => "literature", 
			"action" => "recursive", 
			"criteria" => array("literature.sci_journal_id="), 
			"variables" => array("sci_journal_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
		),
	), 
	"order_obj" => array(
		array("field" => "sci_journal_name"), 
	),
);


?>