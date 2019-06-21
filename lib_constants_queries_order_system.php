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
$query["vendor"]=$query["institution"];
$query["vendor"]["filter"]="FIND_IN_SET(\"vendor\",institution_type)>0";

$query["vendor_with_open"]=$query["institution"];
$query["vendor_with_open"]["joins"][]="accepted_order";
$query["vendor_with_open"]["filter"]="FIND_IN_SET(\"vendor\",institution_type)>0 AND accepted_order.accepted_order_id IS NOT NULL AND accepted_order.central_order_status=1 AND accepted_order.order_comp_id IS NULL AND accepted_order.settlement_id IS NULL";

$query["vendor_for_accepted_order"]=$query["vendor"];
$query["vendor_for_accepted_order"]["alias"]="vendor";
// avoid problems make more elegant in the future
unset($query["vendor_for_accepted_order"]["joins"]);
//~ $query["vendor_for_accepted_order"]["joins"]=array("institution_code");
unset($query["vendor_for_accepted_order"]["distinct"]);
// /avoid problems make more elegant in the future
$query["vendor_for_accepted_order"]["field_data"]=array(
	array("table" => "institution", "prefix" => "v_", "alias" => "vendor", ), 
);
$query["vendor_for_accepted_order"]["subqueries"]=
	array( 
		array(
			"name" => "institution_codes", 
			"table" => "institution_code", 
			"criteria" => array("institution_code.institution_id="), 
			"variables" => array("v_institution_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_PK_SEARCH, 
		), 
	);

$query["vendor"]["fields"]="institution.institution_id AS vendor_id";
$query["vendor"]["subqueries"][]=array(
	"name" => "accepted_order", 
	"table" => "accepted_order", 
	"criteria" => array("accepted_order.central_order_status=1 AND vendor_id="), 
	"variables" => array("institution_id"), 
	"conjunction" => "AND", 
	"forflags" => QUERY_CREATE, 
);

$query["accepted_order"]=array(
	"base_table" => "accepted_order", 
	
	"joins" => array(
		//~ "institution", 
		"vendor", 
	),
	
	"quickfields" => "accepted_order.accepted_order_id AS pk", 
	
	"field_data" => array(
		array("table" => "accepted_order", ), 
		array("table" => "institution", "prefix" => "v_", "alias" => "vendor", ), 
	),
	"subqueries" => array( // hier kommen alle Alternativen
		array( // should also give order_alternative and person
			"name" => "chemical_order", 
			"table" => "chemical_order", 
			"action" => "uid_join", 
			"uid_search" => "order_uid", 
			"uid_value" => "order_uid_cp", 
			"forflags" => QUERY_EDIT+QUERY_LIST, 
		), 
	), 
	"distinct" => GROUP_BY, 
);

$query["chemical_order"]=array(
	"base_table" => "chemical_order", 
	//~ "join_tables" => array("chemical_order","person","order_alternative"),
	"join_1n" => array(
		"order_alternative" => array(
			"fk" => "chemical_order.chemical_order_id", 
			"fk_sub" => "chemical_order_id", 
		), 
	),
	
	"joins" => array(
		"person", "order_alternative", 
	),
	
	"quickfields" => "chemical_order.chemical_order_id AS pk", 
	//~ "fields" => "order_alternative.*,person.*,chemical_order.*,customer_order_status+0 AS customer_order_status,may_change_supplier+0 AS may_change_supplier", // hier kommt die ausgewählte Alternative, so es eine gibt, optional fields at the beginning, otherwise chemical_order_id is overwritten by empty data if there is no alternative selected
	"fields" => "order_alternative.price AS so_price", 
	"field_data" => array(
		array("table" => "order_alternative", ), 
		array("table" => "person", ), 
		array("table" => "chemical_order", ), 
	),
	"subqueries" => array( // hier kommen alle Alternativen
		array(
			"name" => "order_alternative", 
			"table" => "order_alternative", 
			"criteria" => array("chemical_order_id="), 
			"variables" => array("chemical_order_id"), 
			"conjunction" => "AND", 
			"forflags" => -1, 
			"order_obj" => array(
				array("field" => "price * number_packages", "order" => "ASC"), 
			),
		), 
		array(
			"name" => "accepted_order", 
			"table" => "accepted_order", 
			"action" => "uid_join", 
			"uid_search" => "order_uid_cp", 
			"uid_value" => "order_uid", 
			"forflags" => QUERY_EDIT+QUERY_LIST+QUERY_SUBQUERY_FLAT_PRIORITY, // values from order manager have prio
		), 
	), 
	"distinct" => GROUP_BY, 
	"procFilter" => "removeOtherCostCentres", 
);

$query["my_chemical_order"]=$query["chemical_order"]; // everything I ordered, filter will be set in lib_global_funcs
$query["my_chemical_order"]["cache_mode"]=CACHE_INDIVIDUAL;

$query["confirm_chemical_order"]=$query["chemical_order"]; // that needs confirmation
$query["confirm_chemical_order"]["filter"]="customer_order_status=2";

$query["open_chemical_order"]=$query["chemical_order"]; // everything not yet taken by central/Linder/..
$query["open_chemical_order"]["filter"]="customer_order_status=3"; // we must filter the ones already accepted
$query["open_chemical_order"]["quickfields"].=",order_uid"; // needed for filtering

 // visible to all groups at this point
$query["open_chemical_order"]["procFilter"]="removeAccepted"; // we must filter the ones already accepted
$query["open_chemical_order"]["cache_mode"]=CACHE_OFF; // time consuming, but most accurate

function removeAccepted(& $resultset) { // visible to all groups
	for ($a=count($resultset)-1;$a>=0;$a--) { // reverse order
		// search for matching entry in accepted_order
		list($accepted_order)=mysql_select_array(array(
			"table" => "accepted_order", 
			"filter" => "accepted_order.order_uid_cp LIKE BINARY ".fixBlob($resultset[$a]["order_uid"]), 
			"dbs" => "", 
			"limit" => 1, 
			"quick" => true, 
		)); // get existing values
		if ($accepted_order) {
			array_splice($resultset,$a,1);
		}
	}
}

function removeOtherCostCentres(& $resultset) {
	$cost_centres=mysql_select_array(array(
		"table" => "cost_centre", 
		"dbs" => -1, 
	));
	$cost_centres=array_get_col($cost_centres,"cost_centre");
	
	for ($a=count($resultset)-1;$a>=0;$a--) { // reverse order
		// eigene DB ODER eigene Kst: ok
		if ($resultset[$a]["db_id"]==-1 || in_array($resultset[$a]["order_cost_centre_cp"],$cost_centres)) {
			continue;
		}
		if ($accepted_order) { // raus
			array_splice($resultset,$a,1);
		}
	}
}

$query["central_chemical_order"]=$query["accepted_order"]; // everything taken by central/Linder/..
$query["central_chemical_order"]["filter"]="central_order_status<4"; // only the ones which require action
$query["completed_chemical_order"]=$query["accepted_order"]; // abgerechnet
$query["completed_chemical_order"]["filter"]="central_order_status=4";

$query["cost_centre"]=array(
	"base_table" => "cost_centre", 
	"quickfields" => "cost_centre_id AS pk", 
	"field_data" => array(
		array("table" => "cost_centre", ), 
	),
	//~ "fields" => "cost_centre.*", 
);

$query["order_alternative"]=array(
	"base_table" => "order_alternative", 
	"quickfields" => "order_alternative.order_alternative_id", 
	"field_data" => array(
		array("table" => "order_alternative", ), 
	),
	//~ "fields" => "order_alternative.*", 
);

function procOrderComp(& $resultset) { // berechnet die Gesamtsumme
	//~ print_r($resultset);die();
	$list_int_name="accepted_order";
	for ($a=0;$a<count($resultset);$a++) {
		$sum=$resultset[$a]["fixed_costs"];
		$items=0;
		$packages=0;
		$currency=$resultset[$a]["currency"];
		for ($b=0;$b<count($resultset[$a][$list_int_name]);$b++) {
			if ($currency!=$resultset[$a][$list_int_name][$b]["price_currency"]) {
				continue;
			}
			$sum+=$resultset[$a][$list_int_name][$b]["price"]*$resultset[$a][$list_int_name][$b]["number_packages"];
			$items++;
			$packages+=$resultset[$a]["chemical_order"][$b]["number_packages"];
		}
		$resultset[$a]["grand_total"]=$sum;
		$resultset[$a]["grand_total_currency"]=$currency;
		$resultset[$a]["items"]=$items;
		$resultset[$a]["packages"]=$packages;
	}
}

$query["order_comp"]=array(
	"base_table" => "order_comp", 
	
	"joins" => array(
		"institution", 
	),
	
	"quickfields" => "order_comp.order_comp_id AS pk", 
	//~ "fields" => "order_comp.*,".$fields["vendor"], 
	"field_data" => array(
		array("table" => "order_comp", ), 
		array("table" => "institution", ), // "prefix" => "v_", "alias" => "vendor", 
	),
	"procFunction" => "procOrderComp",
	"subqueries" => array( // hier kommen alle Alternativen
		array(
			"name" => "accepted_order", 
			"table" => "accepted_order", 
			"criteria" => array("order_comp_id="), 
			"variables" => array("order_comp_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT+QUERY_LIST, 
		), 
	), 
	"distinct" => GROUP_BY, 
);

$query["rent"]=array(
	"base_table" => "rent", 
	"quickfields" => "rent.rent_id AS pk", 
	"fields" => "UNIX_TIMESTAMP(start_date) AS ts_start_date,UNIX_TIMESTAMP(end_date) AS ts_end_date,DATEDIFF(IF(end_date=DATE(\"0000-00-00\"),CURDATE(),end_date),start_date)+1 AS days_count,price_per_day*(DATEDIFF(IF(end_date=DATE(\"0000-00-00\"),CURDATE(),end_date),start_date)+1) AS grand_total_rent", // rent.*,
	"field_data" => array(
		array("table" => "rent", ), 
	),
	"procFilter" => "removeOtherCostCentres", 
);

$query["active_rent"]=$query["rent"];
$query["active_rent"]["filter"]="(CURDATE() BETWEEN start_date AND IF(end_date=DATE(\"0000-00-00\"),CURDATE(),end_date))";

$query["settlement"]=array(
	"base_table" => "settlement", 
	"quickfields" => "settlement_id AS pk", 
	"fields" => "FALSE AS allowDelete,FALSE AS allowEdit", // settlement.*,
	"field_data" => array(
		array("table" => "settlement", ), 
	),
	"subqueries" => array( 
	// subquery items of chemical_order, rent
		array(
			"name" => "accepted_order", 
			"table" => "accepted_order", 
			"criteria" => array("accepted_order.settlement_id="), 
			"variables" => array("settlement_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
			"order_obj" => array(
				array("field" => "order_cost_centre_cp"), 
				array("field" => "order_acc_no_cp"), 
				//~ array("field" => "supplier NOT LIKE ".fixStr(ausgabe_name), ), 
			), 
		), 
		array(
			"name" => "accepted_order_count", 
			"table" => "accepted_order", 
			"action" => "count", 
			"criteria" => array("accepted_order.settlement_id="), 
			"variables" => array("settlement_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		),
		array(
			"name" => "rent", 
			"table" => "rent", 
			"criteria" => array("rent.settlement_id="), 
			"variables" => array("settlement_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT, 
			"order_obj" => array(
				array("field" => "order_cost_centre_cp"), 
				array("field" => "order_acc_no_cp"), 
			), 
		), 
		array(
			"name" => "rent_count", 
			"table" => "rent", 
			"action" => "count", 
			"criteria" => array("rent.settlement_id="), 
			"variables" => array("settlement_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_LIST, 
		),
	), 
);

// MPI specific
$query["mpi_order"]=array(
	"base_table" => "mpi_order", 
	//~ "join_tables" => array("mpi_order", "other_db", ), 
	"quickfields" => "mpi_order.mpi_order_id AS pk", 
	"fields" => "molecule_name AS molecule_names", // mpi_order.*,,mpi_order_status+0 AS mpi_order_status
	"field_data" => array(
		array("table" => "mpi_order", ), 
	),
	"local_fields" => "other_db.*", 
	
	"joins" => array(
		"mpi_order_item", "other_db", 
	),
	
	"subqueries" => array( 
		array(
			"name" => "mpi_order_item", 
			"table" => "mpi_order_item", 
			"criteria" => array("mpi_order_item.mpi_order_id="), 
			"variables" => array("mpi_order_id"), 
			"conjunction" => "AND", 
			"forflags" => QUERY_EDIT+QUERY_LIST, 
		), 
	), 
	"distinct" => GROUP_BY, 
);

$query["mpi_order_item"]=array(
	"base_table" => "mpi_order_item", 
	"quickfields" => "mpi_order_item.mpi_order_item_id AS pk", 
	"fields" => "molecule_name AS molecule_names", // *,,mpi_order_status+0 AS mpi_order_status
	"field_data" => array(
		array("table" => "mpi_order_item", ), 
		array("table" => "mpi_order", ), 
		array("table" => "other_db", ), 
	),
	
	"joins" => array(
		"mpi_order", "other_db", 
	),
);

?>