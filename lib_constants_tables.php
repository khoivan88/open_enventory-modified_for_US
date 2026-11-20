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
/*------------------------------------------------------------------------------
 * History:
 * 2009-10-21 MST00 Add additional fields for MPI orders
 *----------------------------------------------------------------------------*/
// zwischen die Array-Elemente wird der Tabellenname eingefügt, siehe lib_roots_funcs.php, getSharedViewDefinition
$reactionFilter=array(
	"INNER JOIN project_person AS proj_assign ON ",
	".project_id=proj_assign.project_id INNER JOIN person AS perm ON perm.person_id=proj_assign.person_id WHERE perm.username=SUBSTRING_INDEX(USER(),'@',1) OR ",
	"_shared=TRUE");

$literatureFilter=array(
	"INNER JOIN project_literature AS lit_assign ON lit_assign.literature_id=",
	".literature_id INNER JOIN project_person AS proj_assign ON lit_assign.project_id=proj_assign.project_id INNER JOIN person AS perm ON perm.person_id=proj_assign.person_id WHERE perm.username=SUBSTRING_INDEX(USER(),'@',1) OR ",
	"_shared=TRUE");

// accepted_by_user darf entweder leer sein oder mit dem Benutzernamen identisch
//~ $chemical_orderFilter="WHERE order_status>=2 AND (accepted_by_user IS NULL OR accepted_by_user=\"\" OR accepted_by_user=SUBSTRING_INDEX(USER(),'@',1))";
//~ $order_alternativeFilter="INNER JOIN chemical_order ON order_alternative.chemical_order_id=chemical_order.chemical_order_id WHERE order_status>=2 AND (accepted_by_user IS NULL OR accepted_by_user=\"\" OR accepted_by_user=SUBSTRING_INDEX(USER(),'@',1))";

// CONCAT(perm.username,\"@\",perm.remote_host)=USER()

//---------------------Lager------------------------------------------
$virtual_tables["supplier_search"]=array(
	"forTable" => "molecule", 
	"fields" => array(
		"molecule_auto" => array(
			"fieldType" => "auto",
			"search" => "text",
			"searchPriority" => 103,
		),
		"molecule_names.molecule_name" => array("search" => "text"),
		"cas_nr" => array("search" => "text"),
		"emp_formula" => array("search" => "emp_formula"),
		"molfile_blob" => array("search" => "structure"),
	), 
);

require_once "lib_constants_tables_admin.php";
require_once "lib_constants_tables_inventory.php";
require_once "lib_constants_tables_lab_journal.php";
require_once "lib_constants_tables_analytics.php";
require_once "lib_constants_tables_literature.php";
require_once "lib_constants_tables_order_system.php";
?>