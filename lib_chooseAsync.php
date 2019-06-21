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

function handleLoadDataForPk() {
	global $db,$molecule_data,$g_settings,$settings;
	
	$filter=getLongPrimary($_REQUEST["table"])."=".fixNull($_REQUEST["pk"]);
	$now=time();
	//~ $js_code="";
	$standard_behaviour=false;
	
	switch ($_REQUEST["list_int_name"]) { // what we edit
	case "reactants":
	case "reagents":
	case "products":
	case "copyTable": 
		switch ($_REQUEST["table"]) { // what we search
		case "reaction": // select product of a reaction
		case "reaction_chemical":
			list($reaction)=mysql_select_array(array(
				"table" => "reaction_chemical_for_reaction", 
				"dbs" => $_REQUEST["db_id"], 
				"filter" => $filter, 
				"limit" => 1, 
				"flags" => QUERY_EDIT, 
			));
			
			if (count($reaction)) {
				procReactionProduct($reaction,$_REQUEST["table"]=="reaction_chemical");
				// load and set visibility
				echo "parent.updateFromReaction(".fixStr($_REQUEST["list_int_name"]).",".fixStr($_REQUEST["UID"]).",".fixStr($_REQUEST["field"]).",".fixStr($_REQUEST["group"]).",".json_encode($reaction).");\n";
			}
		break;
		
		case "chemical_storage":
		case "molecule":
			$molecule_data["molecule"]=mysql_select_array(array(
				"table" => "molecule_for_reaction", 
				"dbs" => $_REQUEST["db_id"], 
				"filter" => $filter, 
				"flags" => (($_REQUEST["list_int_name"]!="products" && !$settings["do_not_use_inventory"]) ? QUERY_EDIT:QUERY_SIMPLE), 
			));
			
			//~ print_r($molecule_data["molecule"]);die();
			
			// generate package names
			addPackageNames($molecule_data);
			echo "parent.setControlDataMolecule(".fixStr($_REQUEST["list_int_name"]).",".fixStr($_REQUEST["UID"]).",".fixStr($_REQUEST["field"]).",".fixStr($_REQUEST["group"]).",".json_encode($molecule_data).");
parent.updateMolSelect(".fixStr($_REQUEST["list_int_name"]).",".fixStr($_REQUEST["UID"]).",".fixStr($_REQUEST["field"]).",".fixStr($_REQUEST["group"]).");\n";
			
			if ($_REQUEST["table"]=="chemical_storage") {
				// fixme
				//~ echo "parent.SILsetValue(".fixNull($_REQUEST["pk"]).",".fixStr($_REQUEST["list_int_name"]).",\"chemical_storage_id\",".fixStr($_REQUEST["UID"]).");\n";
				echo "parent.setChemSelect(".fixStr($_REQUEST["list_int_name"]).",".fixStr($_REQUEST["UID"]).",".fixStr($_REQUEST["field"]).",".fixStr($_REQUEST["group"]).",".fixNull($_REQUEST["pk"]).");
parent.updateChemSelect(".fixStr($_REQUEST["list_int_name"]).",".fixStr($_REQUEST["UID"]).",".fixStr($_REQUEST["field"]).",".fixStr($_REQUEST["group"]).");\n";
			}
		break;
		}
	break;
	case "project_literature":
	case "reaction_literature":
		
		if (is_numeric($_REQUEST["editPk"])) { // saving is done when reaction/project is saved
			echo "var UID=".fixNull($_REQUEST["UID"]).";\n";
		}
		else {
			// zeile hinzufügen
			echo "var UID=parent.SILaddLine(".fixStr($_REQUEST["list_int_name"]).",".fixNull($_REQUEST["beforeUID"]).");\n";
		}
		
		// abfragen
		list($literature_data)=mysql_select_array(array(
			"table" => "literature", 
			"dbs" => $_REQUEST["db_id"], 
			"filter" => $filter, 
			"limit" => 1, 
			"flags" => QUERY_EDIT, 
		));
		
		// zeile setzen
		echo "parent.SILsetValues(".fixStr($_REQUEST["list_int_name"]).",UID,undefined,".json_encode($literature_data).");\n";
		
	break;
	case "analytical_data":
		$sql_query=array();
		if (is_numeric($_REQUEST["editPk"])) {
			// alte reaction_id zurücksetzen auf NULL
			$sql_query[]="UPDATE analytical_data SET ".
			"reaction_id=NULL,".
			SQLgetChangeRecord("analytical_data",$now).
			" WHERE analytical_data_id=".fixNull($_REQUEST["editPk"]).";";
			echo "var UID=".fixNull($_REQUEST["UID"]).";\n";
			$desired_action="update";
		}
		else {
			// zeile hinzufügen
			echo "var UID=parent.SILaddLine(".fixStr($_REQUEST["list_int_name"]).",".fixNull($_REQUEST["beforeUID"]).");\n";
			$desired_action="add";
		}
		
		// status umstellen
		echo "parent.alterStatusAnalytics();\n";
		
		// reaction_id setzen
		$sql_query[]="UPDATE analytical_data SET ".
			"reaction_id=".fixNull($_REQUEST["selectForPk"]).",".
			SQLgetChangeRecord("analytical_data",$now).
			" WHERE analytical_data_id=".fixNull($_REQUEST["pk"]).";";
		performQueries($sql_query,$db);
		
		// abfragen
		list($analytical_data)=mysql_select_array(array(
			"table" => "analytical_data", 
			"dbs" => $_REQUEST["db_id"], 
			"filter" => $filter, 
			"limit" => 1, 
			"flags" => QUERY_EDIT, // for images
		));
		
		// zeile setzen
		echo "parent.SILsetValues(".fixStr($_REQUEST["list_int_name"]).",UID,undefined,".json_encode($analytical_data).");
parent.addMainAnalytics(".fixStr($_REQUEST["list_int_name"]).",UID,".fixStr($desired_action).");
parent.SILfocusControl(".fixStr($_REQUEST["list_int_name"]).",UID,\"analytical_data_interpretation\");\n";
	break;
	
	case "item_list": // very standard situation
	case "order_alternative": // very standard situation
		$query_table="supplier_offer_for_accepted_order";
		$standard_behaviour=true;
	break;
	default:
		$query_table=$_REQUEST["table"];
		$standard_behaviour=true;
	}
	
	if ($standard_behaviour) {
		list($result)=mysql_select_array(array(
			"table" => $query_table, 
			"dbs" => $_REQUEST["db_id"], 
			"filter" => $filter, 
			"limit" => 1, 
			"flags" => QUERY_PK_SEARCH, 
		));
		
		if (count($result)) {
			echo "parent.SILsetValues(".fixStr($_REQUEST["list_int_name"]).",".fixStr($_REQUEST["UID"]).",undefined,".json_encode($result).");\n";
		}
	}
	
	echo "parent.valChanged();\n";
	flush(); // show partial results
}

?>