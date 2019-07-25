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

$view_controls["reaction"]=array(
	"ansatzzettel", 
	"status",
	"project_id",
	"reaction_type_id",
	"rxnfile_blob",
	"reactants", 
	"reagents",
	//~ "realization_text",
	//~ "realization_observation",
	//~ "reaction_barcode",
	"analytical_data", 
	"products",
	"retained_product",
	"reaction_literature",
	"betr_anw_gefahren",
	"betr_anw_schutzmass",
	"betr_anw_verhalten", 
	"betr_anw_erste_h",
	"betr_anw_entsorgung",
);
$view_ids["reaction"]=array(
	"compare_rxn", 
	"block_conditions", 
	"block_realization",
	"block_observation",
	"block_log",
	"witness",
	"reaction_barcode", 
	"block_response", 
	"specnav", 
);

function activateEditViews($table) {
	global $g_settings,$settings,$edit_views,$view_controls,$view_ids;
	switch ($table) {
	case "reaction":
		$edit_views[$table]=array(
			"ansatzzettel" => array(
				"visibleControls" => array(
					"ansatzzettel", "status", "project_id", "reaction_type_id", "reaction_title", "rxnfile_blob","reactants","reagents", // "realization_text", "realization_observation",
				),
				"visibleIds" => array(
					"block_conditions", "reaction_barcode", "block_realization", "block_observation", 
				),
			),
			"ergebnis" => array(
				"visibleControls" => array(
					"rxnfile_blob", "reactants", "reagents", "products", "retained_product", // "realization_text","realization_observation",
				),
				"visibleIds" => array(
					"block_conditions", "witness", "block_realization", "block_observation", "compare_rxn", 
				),
			),
		);

		if ($settings["usePersonalAnalyticsTabs"]) {
			$data=$settings["customAnalyticsTabs"];
		}
		else {
			$data=$g_settings["customAnalyticsTabs"];
		}

		for ($a=0;$a<count($data);$a++) {
			$key=$data[$a]["key"];
			// Text to display
			if (empty($data[$a]["text"])) {
				$text=s($key);
			}
			else {
				$text=$data[$a]["text"];
			}
			
			$visibleControls=array("analytical_data", );
			if ($data[$a]["showRxn"]) {
				$visibleControls[]="rxnfile_blob";
			}
			$visibleIds=array("specnav", );
			
			$filter=array(
				array("int_name" => "analytical_data", "field" => "analytics_type_name", "op" => "eq", "val" => $data[$a]["analytics_type_name"]), 
			);
			if ($data[$a]["mixtureOnly"]) {
				$filter[]=array("int_name" => "analytical_data", "field" => "reaction_chemical_uid", "op" => "eq", "val" => ""); // mixture only
			}
			
			// basic frame
			$edit_views[$table][$key]=array(
				"visibleControls" =>$visibleControls,
				"visibleIds" => $visibleIds,
				//~ "hiddenControls" => array_values(array_diff($view_controls["reaction"],$visibleControls)),
				//~ "hiddenIds" => array_values(array_diff($view_ids["reaction"],$visibleIds)),
				"filter" => $filter,
				"sort" => array(),
				"text" =>  $text, 
			);
			
		}

		$edit_views[$table]=array_merge($edit_views[$table],array(
			"analytics" => array(
				"visibleControls" => array(
					"analytical_data", 
				),
				"visibleIds" => array(
					"specnav", 
				),
			),
			"komplett" => array(
				// blocks und divs
				"visibleControls" => array(
					"ansatzzettel", "status", "project_id", "reaction_type_id", "reaction_title", "analytical_data", "rxnfile_blob", "reactants", "reagents", "products", "retained_product", "reaction_literature", // "realization_text","realization_observation",
				),
				"visibleIds" => array(
					"block_conditions", "witness", "reaction_barcode", "block_realization", "block_observation", "block_response", "specnav","block_log", 
				),
			),
		));
	break;
	}
	fixViews($table);
	//~ checkViews();
}

function fixViews($table) {
	global $view_controls,$view_ids,$edit_views;
	// go through views
	if (is_array($edit_views[$table])) foreach ($edit_views[$table] as $view_name => $view) {
		$edit_views[$table][$view_name]["hiddenControls"]=array_values(array_diff($view_controls[$table],arr_safe($view["visibleControls"])));
		$edit_views[$table][$view_name]["hiddenIds"]=array_values(array_diff($view_ids[$table],arr_safe($view["visibleIds"])));
		//~ print_r(array_diff($view_controls[$table],arr_safe($view["visibleControls"]),arr_safe($view["hiddenControls"])));
		//~ print_r(array_diff($view_ids[$table],arr_safe($view["visibleIds"]),arr_safe($view["hiddenIds"])));
	}
}

function checkViews() {
	global $view_controls,$view_ids,$edit_views;
	$used_controls=array();
	$used_ids=array();
	// go through tables
	if (is_array($edit_views)) foreach ($edit_views as $table => $edit_view) {
		// go through views
		/* should no longer be a prob
		foreach ($edit_view as $view_name => $view) {
			print_r(array_diff($view_controls[$table],arr_safe($view["visibleControls"]),arr_safe($view["hiddenControls"])));
			print_r(array_diff($view_ids[$table],arr_safe($view["visibleIds"]),arr_safe($view["hiddenIds"])));
		}*/
		foreach ($edit_view as $view_name => $view) {
			$used_controls=arr_merge($used_controls,$view["visibleControls"]);
			$used_ids=arr_merge($used_ids,$view["visibleIds"]);
		}
		$used_controls=array_values(array_unique($used_controls));
		$used_ids=array_values(array_unique($used_ids));
		print_r(array_diff($view_controls[$table],$used_controls));
		print_r(array_diff($view_ids[$table],$used_ids));
	}
	die("X");
}

$edit_links_params="?".getSelfRef(array("~script~"));
$edit_links["reaction"]=array(
	//~ array("int_name" => "search_menu", "url" => "searchRxn.php".$edit_links_params), 
	array("int_name" => "search_menu", "url" => "javascript:activateSearch(true)", "id" => "view_search", "class" => "tab_light", "hide" => true), // per JS 
	//~ array("int_name" => "inventory", "url" => "main.php".$edit_links_params, "target" => "_top"), 
);

?>