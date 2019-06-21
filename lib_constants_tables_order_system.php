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

// specific for mpi organisation
$tables["mpi_order"]=array(
	"readPerm" => _order_accept+_chemical_read, 
	"writePerm" => _order_accept, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"mpi_order_item" => array("condition" => "mpi_order.mpi_order_id=mpi_order_item.mpi_order_id", ),
		"other_db" => array("condition" => "mpi_order.other_db_id=other_db.other_db_id", ),
	),
	
	"recordCreationChange" => true, // "noDelete" => true, 
	"fields" => array(
		"other_db_id" => array("type" => "INT", "search" => "auto", "fk" => "other_db", ), // must be signed to make -1 possible
		//~ "order_group" => array("type" => "TINYTEXT", "search" => "auto"), 
		"order_person" => array("type" => "TINYTEXT", "search" => "auto"), 
		"order_account" => array("type" => "TINYTEXT", "search" => "auto"), 
		"order_date" => array("type" => "DATE", "search" => "auto"), 
		"delivery_date" => array("type" => "DATE", "search" => "auto"), 
		"molecule_name" => array("type" => "TINYTEXT", "search" => "auto"), 
		"cas_nr" => array("type" => "TINYTEXT", "search" => "auto"), 
		"chemical_storage_conc" => array("type" => "DOUBLE"), 
		"chemical_storage_conc_unit" => array("type" => "TINYTEXT"), 
		"chemical_storage_solvent" => array("type" => "TINYTEXT", "search" => "auto"), 
		"supplier" => array("type" => "TINYTEXT", "search" => "auto"), 
		"sap_bestell_nr" => array("type" => "TINYTEXT", "search" => "auto"), 
		"sap_stamm_nr" => array("type" => "TINYTEXT", "search" => "auto", "index" => "(10)", ), 
		"bessi" => array("type" => "TINYTEXT", "search" => "auto"), 
		"mpi_order_status" => array("type" => "ENUM", "values" => array("ordered","delivered"), ),
//>>>MST00
                "total_amount" => array("type" => "DOUBLE", "search" => "auto"),
		"amount_unit" => array("type" => "TINYTEXT"),
                "pos_liste" => array("type" => "INT", "search" => "auto" )
//<<<MST00
	), 
);

$tables["mpi_order_item"]=array(
	"readPerm" => _order_accept+_chemical_read, 
	"writePerm" => _order_accept, 
	"readPermRemote" => _remote_read, 
	
	"joins" => array( // list of *possible* JOINS
		"mpi_order" => array("condition" => "mpi_order.mpi_order_id=mpi_order_item.mpi_order_id", ),
		"other_db" => array("condition" => "mpi_order.other_db_id=other_db.other_db_id", ),
	),
	
	"recordCreationChange" => true, // "noDelete" => true, 
	"fields" => array(
		"mpi_order_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "mpi_order", ), 
		"chemical_storage_barcode" => array("type" => "TINYTEXT", "search" => "auto"), 
		"amount" => array("type" => "DOUBLE", "search" => "auto"), // NO unit calculations here
		"amount_unit" => array("type" => "TINYTEXT"),
//>>>MST00
                "order_status" => array("type" => "ENUM", "values" => array("ordered","issued"), ),
//<<<MST00
	), 
);

$tables["mat_stamm_nr"]=array(
	"readPerm" => _order_accept, 
	"writePerm" => _order_accept, 
	
	"joins" => array( // list of *possible* JOINS
		"molecule" => array("condition" => "molecule.molecule_id=mat_stamm_nr.molecule_id", ),
	),
	
	"fields" => array(
		"molecule_id" => array("type" => "INT UNSIGNED", "search" => "auto", "fk" => "molecule", ),
		"sap_stamm_nr" => array("type" => "VARCHAR(32) UNIQUE", "search" => "auto", ), 
		"comment_stamm_nr" => array("type" => "TINYTEXT", "search" => "auto"), 
	), 
);
// END of mpi specific part

// ausgehende bestellung zu anbieter, wird durch chemikalienausgabe erstellt
$tables["order_comp"]=array(
	"readPerm" => _chemical_read, 
	"writePerm" => _order_accept,  
	"deletePerm" => 0,	
	
	"joins" => array( // list of *possible* JOINS
		"institution" => array("condition" => "institution.institution_id=order_comp.institution_id", ),
	),
	
	"noDelete" => true, 
	
	"recordCreationChange" => true, 
	"fields" => array(
		"institution_id" => array("type" => "INT UNSIGNED", "fk" => "institution", ),
		"comp_order_date" => array("type" => "DATE"),
		"fixed_costs" => array("type" => "DOUBLE"), // total costs should be fixed_costs + prices*numbers_of_packages
		"fixed_costs_vat_rate" => array("type" => "DOUBLE"), 
		"lagerpauschale" => array("type" => "DOUBLE"), 
		"order_comp_status" => array("type" => "ENUM", "values" => array("central_prepared","central_ordered"), ), // ignore for now
		"currency" => array("type" => "TINYTEXT"), // all accepted_order must be the same
		"order_way" => array("type" => "TINYTEXT"), // eMail, Fax, online, letter,...
		"order_identifier" => array("type" => "TINYTEXT"), 
		"kleinauftrag_nrn" => array("type" => "TEXT"), 
		"central_cost_centre" => array("type" => "TINYTEXT"), 
	), 
);

// EInzelbestellung mit mehreren Alternativen, wird zur Chemikalienausgabe kopiert
$tables["accepted_order"]=array(
	"readPerm" => _chemical_read+_order_accept, 
	"writePerm" => _order_accept, 
	"readPermRemote" => _remote_read,
	
	"recordCreationChange" => true, 
	
	"joins" => array( // list of *possible* JOINS
		//~ "institution_code" => array("condition" => "accepted_order.supplier=institution_code.supplier_code", ), // links various texts to institution_id with address
		//~ "institution" => array("condition" => "institution.institution_id=institution_code.institution_id", ), // address for order
		//~ "institution" => array("condition" => "institution.institution_id=accepted_order.vendor_id", ), // address for order
		"vendor" => array("base_table" => "institution", "alias" => "vendor", "condition" => "vendor.institution_id=accepted_order.vendor_id", ), // address for order
	), 
	
	"fields" => array( // _cp stands for copy, as these values are only copied as proof, deviations result in warnings
		"ordered_by_username_cp" => array("type" => "TINYTEXT", ), // the username, better for remote so that perhaps we do not need the complicated join
		"selected_alternative_id" => array("type" => "INT UNSIGNED", "fk" => "order_alternative", ), // NULL if not yet chosen
		"customer_order_date_cp" => array("type" => "DATETIME"), // wann hat der Besteller den Status auf customer_confirmed gesetzt
		"order_cost_centre_cp" => array("type" => "TINYTEXT", "index" => "(10)", ), // copy for proof
		"order_acc_no_cp" => array("type" => "TINYTEXT"), // copy for proof
		"central_comment" => array("type" => "TEXT"), // put stuff like "chose xyz to save shipping" here
		"order_uid_cp" => array("type" => "VARBINARY(128)", ), 
		"central_order_date" => array("type" => "DATETIME"), // wann hat der Lagermensch bestellt
		"supplier_delivery_date" => array("type" => "DATETIME"), // wann wird die Ware geliefert/ist geliefert worden?
		"customer_delivery_date" => array("type" => "DATETIME"), // wann ist die Ware durch den Kunden abgeholt worden?
		"central_order_status" => array("type" => "ENUM", "values" => array("customer_confirmed","central_ordered","supplier_delivered","central_delivered"), ),
		"order_comp_id" => array("type" => "INT UNSIGNED", "fk" => "order_comp", ), // mit welcher Bestellung ging der Auftrag raus

		"history" => array("type" => "TEXT NOT NULL", "default" => "''"), // Eintragen von Entnahmen, nachträglich nicht mehr änderbar

		"settlement_id" => array("type" => "INT UNSIGNED", "fk" => "settlement", ), // mit welcher Abrechnung wurde die Kostenstelle belastet
		"fixed_costs_share" => array("type" => "DOUBLE"), // amount of money in the same currency, netto
		"fixed_costs_share_vat_rate" => array("type" => "DOUBLE"), 
		"billing_date" => array("type" => "DATE"), // redundant
		
		// stuff from order_alternative, this is what counts!!
		"vendor_id" => array("type" => "INT UNSIGNED", "fk" => "institution", ), // assign to avoid ugly joins
		"supplier" => array("type" => "TINYTEXT"), // use the code like "Sial"
		"supplier_offer_id" => array("type" => "INT UNSIGNED", "fk" => "supplier_offer", ), // to update catalog prices, not sure yet if this will be realised, and it will ONLY work for own DB
		"catNo" => array("type" => "TINYTEXT"), // internal catNo
		"beautifulCatNo" => array("type" => "TINYTEXT"), // catNo for order
		"name" => array("type" => "TEXT"),
		"cas_nr" => array("type" => "TINYTEXT"),
		"package_amount" => array("type" => "DOUBLE"),
		"package_amount_unit" => array("type" => "TINYTEXT"),
		"number_packages_text" => array("type" => "TINYTEXT"), // e.g. 100g will be turned into 0.1 for a 1kg package
		"density_20" => array("type" => "DOUBLE", ), // to calculate mass from volume or vice versa
		"number_packages" => array("type" => "DOUBLE"),
		"so_price" => array("type" => "DOUBLE"), // hauspreis
		"price" => array("type" => "DOUBLE"), // tatsächlich gezahlter preis, inkl auftragswertrabatt, skonto, etc.
		"price_currency" => array("type" => "TINYTEXT"), 
		"vat_rate" => array("type" => "DOUBLE"), // MwSt-Satz, default 19%
	), 
);

$tables["chemical_order"]=array(
	"readPerm" => _chemical_read, 
	"writePerm" => _order_order+_order_approve+_order_accept, 
	"readPermRemote" => _order_accept, 
	//~ "remoteFilter" => $chemical_orderFilter,  // put suitable filter here later FIXME
	"recordCreationChange" => true, 
	
	"joins" => array( // list of *possible* JOINS
		"order_alternative" => array("condition" => "order_alternative.chemical_order_id=chemical_order.chemical_order_id AND order_alternative.order_alternative_id=chemical_order.customer_selected_alternative_id", ),
		"person" => array("condition" => "person.person_id=chemical_order.ordered_by_person", ),
	),
	
	//~ "remoteFilter" => $chemical_orderFilter, 
	"fields" => array(
		"ordered_by_person" => array("type" => "INT UNSIGNED", "fk" => "person", ), // the guy who wants the stuff
		"ordered_by_username" => array("type" => "TINYTEXT", ), // the username, better for remote so that perhaps we do not need the complicated join
		"customer_selected_alternative_id" => array("type" => "INT UNSIGNED", "fk" => "order_alternative", ), // NULL if not yet chosen
		"customer_order_date" => array("type" => "DATETIME"), // wann hat der Besteller den Status auf customer_confirmed gesetzt
		"order_cost_centre" => array("type" => "TINYTEXT", "index" => "(10)", ),
		"order_acc_no" => array("type" => "TINYTEXT"),
		"customer_comment" => array("type" => "TEXT"), // put stuff like "urgent" here
		"customer_order_status" => array("type" => "ENUM", "values" => array("customer_planning","customer_ordered","customer_confirmed"), ), 
		"may_change_supplier" => array("type" => "ENUM", "values" => array("yes","important_reason","never"), ), 
		"order_uid" => array("type" => "VARBINARY(128)", ), 
		"chemical_storage_id" => array("type" => "INT UNSIGNED", "fk" => "chemical_storage", ), // übernehmen ins inventar
	), 
);

// alle Preis NETTO, MwSt erst am Ende dazurechnen

$tables["order_alternative"]=array(
	"readPerm" => _chemical_read, 
	"writePerm" => _order_order+_order_approve+_order_accept, 
	"readPermRemote" => _order_accept, 
	//~ "remoteFilter" => $chemical_orderFilter,  // put suitable filter here later FIXME
	"recordCreationChange" => true, 
	
	//~ "remoteFilter" => $order_alternativeFilter, 
	"fields" => array(
		"chemical_order_id" => array("type" => "INT UNSIGNED", "fk" => "chemical_order", ),
		"supplier" => array("type" => "TINYTEXT", "index" => "(10)", ), // use the code like "Sial"
		"catNo" => array("type" => "TINYTEXT", ), // internal catNo
		"beautifulCatNo" => array("type" => "TINYTEXT", ), // catNo for order
		"name" => array("type" => "TEXT", ),
		"cas_nr" => array("type" => "TINYTEXT", ),
		"package_amount" => array("type" => "DOUBLE", ),
		"package_amount_unit" => array("type" => "TINYTEXT", ),
		"number_packages_text" => array("type" => "TINYTEXT"), // e.g. 100g will be turned into 0.1 for a 1kg package
		"density_20" => array("type" => "DOUBLE", ), // to calculate mass from volume or vice versa
		"number_packages" => array("type" => "DOUBLE", ),
		"price" => array("type" => "DOUBLE", ), // einzelpreis, Gesamtpreis = price*number_packages, netto
		"price_currency" => array("type" => "TINYTEXT", ), 
		"vat_rate" => array("type" => "DOUBLE", ), // MwSt-Satz, default 19%
	), 
); // mehrere Alternativen zum Kaufen

$tables["rent"]=array(
	"readPerm" => _chemical_read, 
	"writePerm" => _order_accept, 
	"readPermRemote" => _remote_read,
	
	"fields" => array(
		"item_identifier" => array("type" => "TINYTEXT"), 
		"comment" => array("type" => "TEXT"), 
		"order_cost_centre_cp" => array("type" => "TINYTEXT", "index" => "(10)", ), 
		"order_acc_no_cp" => array("type" => "TINYTEXT"), 
		"price_per_day" => array("type" => "DOUBLE"), 
		"price_per_day_currency" => array("type" => "TINYTEXT"), 
		"vat_rate" => array("type" => "DOUBLE"), 
		"start_date" => array("type" => "DATE"), 
		"end_date" => array("type" => "DATE"), 
		"billing_date" => array("type" => "DATE"), // redundant
		"settlement_id" => array("type" => "INT UNSIGNED", "fk" => "settlement", ), 
	), 
);

$tables["settlement"]=array( // Zusammenfassung einer Abrechnung zur Nachvollziehbarkeit
	"readPerm" => _order_accept, 
	"writePerm" => _order_accept, 
	
	"fields" => array(
		"billing_date" => array("type" => "DATE"),
		"from_date" => array("type" => "DATE"),
		"to_date" => array("type" => "DATE"),
		"currency" => array("type" => "TINYTEXT"), 
		"lagerpauschale" => array("type" => "DOUBLE"), 
	), 
);

$tables["cost_centre"]=array(
	"readPerm" => _admin+_order_accept+_order_order, 
	"writePerm" => _admin+_order_accept, 
	"readPermRemote" => _order_accept, // people enter the cost_centres in their own databases, order manager can read them
	
	"fields" => array(
		"cost_centre" => array("type" => "VARCHAR(32) UNIQUE"), // cost_centre identification
		"acc_no" => array("type" => "TINYTEXT"), 
		"cost_centre_name" => array("type" => "TINYTEXT"), 
	), 
);


?>