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

/*--------------------------------------------------------------------------------------------------
/ Function: getSubtableSelect
/
/ Purpose: get field name with alias for use in SQL FROM as INNER JOIN, the alias removes ambiguity and thus enables access through PHP
/
/ Parameter:
/ 		$st_name : subtable name
/ 		$fieldname : name of the field
/ 		$isLast : not used, can be removed
/
/ Return : string that can be added to a field list in an SQL SELECT statement
/------------------------------------------------------------
/ History:
/ 2009-07-16 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function getSubtableSelect($st_name,$fieldname,$isLast=false) {
	return ",".$st_name.".".$fieldname." AS ".$st_name."_".$fieldname;
}

/*--------------------------------------------------------------------------------------------------
/ Function: getDbList
/
/ Purpose: returns an array of accessible databases, and also -1 for the own database
/
/ Parameter:
/ 		none
/
/ Return : array of accessible databases, including the own (-1)
/------------------------------------------------------------
/ History:
/ 2009-07-16 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function getDbList() {
	global $other_db_data;
	$db_list=array();
	for ($a=0;$a<count($other_db_data);$a++) {
		$db_list[$a]=$other_db_data[$a]["other_db_id"];
	}
	array_unshift($db_list,-1);
	return $db_list;
}

function getSubqueryNumberFromPattern($pattern) {
	if (empty($pattern)) {
		return array();
	}
	preg_match_all("/(?ims)<(\d+)>/",$pattern,$subquery_number_match,PREG_PATTERN_ORDER);
	return $subquery_number_match[1];
}

/*--------------------------------------------------------------------------------------------------
/ Function: getFilterObject
/
/ Purpose: returns an array of accessible databases, and also -1 for the own database
/
/ Parameter:
/ 		$paramHash : hash array containing (maybe) an old "filter_obj" from the cache, the string list of databases being queried "dbs" (used for selected_only), "db_filter" to update the cache
/
/ Return : filter object containing search criteria (also molecule data structures, if applies), the multiple SQL conditions and the whole filter, i.e. the WHERE clause without the WHERE
/------------------------------------------------------------
/ History:
/ 2009-07-16 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function getFilterObject($paramHash=array()) { // Erstellung der WHERE-Bedingung
	global $tables,$table,$query,$fp_only_smiles,$baseTable,$pk_name,$searchModes,$g_settings,$settings;
	$filter_obj=& $paramHash["filter_obj"];
	$dbs=& $paramHash["dbs"];
	if (empty($dbs)) {
		// get all as comma-separated list
		$db_list=getDbList();
		$dbs=@join(",",$db_list);
	}
	else {
		$db_list=explode(",",$dbs);
	}
	$invalid_cond=array();
	
	if (!empty($_REQUEST["ref_cache_id"]) && $_REQUEST["list_op"]>1) {
		$ref_cache=readCache($_REQUEST["ref_cache_id"]);
		$ref_cache=$ref_cache["results"]["db"];
		if (count($ref_cache)) {
			$cache_dbs=array_keys($ref_cache); // zu welchen Datenbanken gibt es Daten im "alten" Cache
			for ($b=0;$b<count($cache_dbs);$b++) {
				$ref_cache[ $cache_dbs[$b] ]=@join(",",$ref_cache[ $cache_dbs[$b] ]);
			}
		}
	}
	
/*
$filter_obj
	subquery_numbers: Liste der Nummern aus <0> AND <1> OR ...
	der Index 0 steht im folgenden für die subquery_number
	crits: 0 => molecule_names.molecule,...
	ops: 0 => ct (contains),...
	vals: 0 => Array("SMILES","molfile") oder Array("phenol"),...
	subqueries: 0 => "molecule_names.molecule LIKE "%phenol%"
	
	substructure: 0 => molecule-Objekt (aus molfile, spart parsen oder aus Summenformel, spart parsen)
	subreaction: 0 => reaction-Objekt (molecules => array(), reactants => Zahl, products => Zahl), hier Aufbau des Queries über fingerprints (deshalb nur positive UND-Verknüpfung)
	
	query_pattern: <0> AND <1> OR ..., wird auf Validität intensiv geprüft
	query_string: die WHERE Bedingung für die SQL-Abfrage, substructure-Suchen sind molecule_id IN(...), damit alle 
*/
	if (arrCount($filter_obj)==0) { // sonst ist die arbeit schon vorher erledigt
		// Zusammenfügen fragmentierter queries
		
		if (is_array($_REQUEST["query"])) {
			$_REQUEST["query"]=join("",$_REQUEST["query"]);
		}
		
		//~ print_r($_REQUEST);
		$filter_obj["query_pattern"]=secSQL($_REQUEST["query"]);
		$filter_obj["select_pattern"]=secSQL($_REQUEST["select_query"]); // defines which dataset will be visible at the beginning
	
		// 1. aus $_REQUEST["query"] mit regexp alle <\d+> rausholen
		//~ preg_match_all("/(?ims)<(\d+)>/",$filter_obj["query_pattern"],$subquery_number_match,PREG_PATTERN_ORDER);
		//~ $filter_obj["subquery_numbers"]=$subquery_number_match[1];
		$filter_obj["subquery_numbers"]=array_unique(arr_merge(getSubqueryNumberFromPattern($filter_obj["query_pattern"]),getSubqueryNumberFromPattern($filter_obj["select_pattern"])));
		
		for($a=0;$a<count($filter_obj["subquery_numbers"]);$a++) { // Basistabelle für Abfrage wählen (!= subquery)
			$filter_obj["subquery_numbers"][$a]+=0;
			$subquery_number=$filter_obj["subquery_numbers"][$a];
			$filter_obj["forTable"][$subquery_number]=$table;
		}
		
		// get unused subquery
		// problem: op=sq may result in additional <xx> which are NOT in query or select_query!!
		/*
		if (count($filter_obj["subquery_numbers"])) {
			$subquery_number=max(max($filter_obj["subquery_numbers"])+1,1); // not treated by section 3
		}
		else {
			$subquery_number=1;
		}
		*/
		$subquery_number=10000; // must not be used in forms
		
		// 2a. prepare include/exclude conditions
		if (!empty($_REQUEST["ref_cache_id"]) && $_REQUEST["list_op"]>1 && $_REQUEST["list_op"]<5) {
			$filter_obj["forTable"][$subquery_number]=$table;
			$filter_obj["selectTable"][$subquery_number]=$table;
			$filter_obj["crits"][$subquery_number]=$pk_name;
			$filter_obj["ops"][$subquery_number]="in";
			$filter_obj["vals"][$subquery_number][0]=$ref_cache; // hier kommen die Werte aus dem Cache rein, die vorher passend in ein Array gelesen wurden, $ref_cache[$db_id]=array($pk1,$pk2,...)
			
			// $ref_cache
			switch ($_REQUEST["list_op"]) {
			case 2: // within_pks[db_id] AND IN(...) from prev_cache_id
				$filter_obj["query_pattern"]="(".$filter_obj["query_pattern"].") AND <".$subquery_number.">";
			break;
			case 3: // include_pks[db_id] OR IN(...) from prev_cache_id: Nein, include_pks sollen an den Anfang (oder ans Ende??)
				$filter_obj["query_pattern"]="(".$filter_obj["query_pattern"].") OR <".$subquery_number.">";
			break;
			case 4: // exclude_pks[db_id] AND NOT IN(...) from prev_cache_id
				$filter_obj["query_pattern"]="(".$filter_obj["query_pattern"].") AND NOT <".$subquery_number.">";
			break;
			}
			list($filter_obj["subqueries"][$subquery_number],$filter_obj["optimised_order"])=procSubquery(
				$db_list,
				$filter_obj["forTable"][$subquery_number],
				$filter_obj["selectTable"][$subquery_number],
				$filter_obj["crits"][$subquery_number],
				$filter_obj["ops"][$subquery_number],
				$filter_obj["vals"][$subquery_number]
			);
		}
		
		$subquery_number++; // next free
		// 2b. prepare selected_only conditions
		if ($_REQUEST["selected_only"]) {
			$filter_obj["query_pattern"]="(".$filter_obj["query_pattern"].") AND <".$subquery_number.">";
			
			if (count($settings["selection"][$table])) {
				//~ foreach ($settings["selection"][$table] as $db_id => $pkData) {
				$selected_cache=array();
				foreach ($db_list as $db_id) {
					$pkData=& $settings["selection"][$table][$db_id];
					$tempArray=array();
					if (count($pkData)) {
						foreach ($pkData as $pk => $active) {
							if ($active) {
								$tempArray[]=$pk;
							}
						}
					}
					$selected_cache[$db_id]=fixArrayList($tempArray);
				}
				
				$filter_obj["forTable"][$subquery_number]=$table;
				$filter_obj["selectTable"][$subquery_number]=$table;
				$filter_obj["crits"][$subquery_number]=$pk_name;
				$filter_obj["ops"][$subquery_number]="in";
				$filter_obj["vals"][$subquery_number][0]=$selected_cache;
			}
			else {
				$filter_obj["forTable"][$subquery_number]=$table;
				$filter_obj["selectTable"][$subquery_number]=$table;
				$filter_obj["crits"][$subquery_number]=$pk_name;
				$filter_obj["ops"][$subquery_number]="no"; // gives FALSE
				$filter_obj["vals"][$subquery_number]=array();
			}
				
			list($filter_obj["subqueries"][$subquery_number],$filter_obj["optimised_order"])=procSubquery(
				$db_list,
				$filter_obj["forTable"][$subquery_number],
				$filter_obj["selectTable"][$subquery_number],
				$filter_obj["crits"][$subquery_number],
				$filter_obj["ops"][$subquery_number],
				$filter_obj["vals"][$subquery_number]
			);
			
		}
		//~ $filter_obj["filter_disabled"]=($_REQUEST["filter_disabled"]?true:false);
		
		$subquery_number++; // next free
		// 2c. enable filterDisabled
		if ($_REQUEST["filter_disabled"] && $tables[$table]["useDisabled"]) {
			$filter_obj["forTable"][$subquery_number]=$table;
			$filter_obj["selectTable"][$subquery_number]=$table;
			$filter_obj["crits"][$subquery_number]=$table."_disabled";
			$filter_obj["ops"][$subquery_number]="nu";
			$filter_obj["query_pattern"]="(".$filter_obj["query_pattern"].") AND <".$subquery_number.">";
			
			list($filter_obj["subqueries"][$subquery_number],$filter_obj["optimised_order"])=procSubquery(
				$db_list,
				$filter_obj["forTable"][$subquery_number],
				$filter_obj["selectTable"][$subquery_number],
				$filter_obj["crits"][$subquery_number],
				$filter_obj["ops"][$subquery_number],
				$filter_obj["vals"][$subquery_number]
			);
		}

		// 3. für <\d+> Bedingungen auslesen und in Arraystruktur speichern (-> query-cache), leere Bedingungen werden durch TRUE ersetzt
		for ($a=0;$a<count($filter_obj["subquery_numbers"]);$a++) { // hier MUSS count direkt in Schleifenbedingungen stehen, weil die Zahl ggf noch wächst
			$subquery_number=& $filter_obj["subquery_numbers"][$a];
			
			// save query parts
			if (strpos($_REQUEST["crit".$subquery_number],".")===FALSE) { // Tabellenname ist nicht gesetzt
				$filter_obj["selectTable"][$subquery_number]=$table;
				$filter_obj["crits"][$subquery_number]=$_REQUEST["crit".$subquery_number];
			}
			else {
				list($filter_obj["selectTable"][$subquery_number],$filter_obj["crits"][$subquery_number])=explode(".",$_REQUEST["crit".$subquery_number],2);
			}
			
			//~ $filter_obj["crits"][$subquery_number]=$_REQUEST["crit".$subquery_number];
			$filter_obj["selectTable"][$subquery_number]=secSQL($filter_obj["selectTable"][$subquery_number]);
			$filter_obj["crits"][$subquery_number]=secSQL($filter_obj["crits"][$subquery_number]);
			$filter_obj["ops"][$subquery_number]=secSQL($_REQUEST["op".$subquery_number]);
			
			// do not trim molfiles
			if (in_array($filter_obj["ops"][$subquery_number],$searchModes["structure"])) {
				$filter_obj["vals"][$subquery_number]=array(
					$_REQUEST["val".$subquery_number],
					$_REQUEST["val".$subquery_number."a"]
				);
			}
			else {
				$filter_obj["vals"][$subquery_number]=array(
					trim($_REQUEST["val".$subquery_number]),
					trim($_REQUEST["val".$subquery_number."a"])
				);
			}
			
			// Spezialfälle Unterabfrage, Reaktion, Struktur, Summenformel
			if ($filter_obj["ops"][$subquery_number]=="sq") { // Unterabfrage
				// weitere subquery_numbers gewinnen
				//~ preg_match_all("/(?ims)<(\d+)>/",$filter_obj["vals"][$subquery_number][0],$subquery_number_match,PREG_PATTERN_ORDER);
				//~ $subquery_number_match=$subquery_number_match[1];
				$subquery_number_match=getSubqueryNumberFromPattern($filter_obj["vals"][$subquery_number][0]);
				for ($b=0;$b<count($subquery_number_match);$b++) {
					$filter_obj["subquery_numbers"][]=$subquery_number_match[$b];
					$filter_obj["forTable"][$subquery_number_match[$b]]=$filter_obj["selectTable"][$subquery_number];
				}
			}
			elseif (in_array($filter_obj["ops"][$subquery_number],$searchModes["reaction"])) { // reaktionssuche
				//~ die($filter_obj["vals"][$subquery_number][1]);
				$reaction=readRxnfile($filter_obj["vals"][$subquery_number][1],array("forStructureSearch" => true) ); // SMILES wird ignoriert, 1 nur wg einheitlichkeit
				//~ print_r($reaction);die();
				$filter_obj["subreaction"][$subquery_number]=$reaction;
				// rxnfile durchgehen und subquery bauen
				$rxn_conditions=array();
				if (count($reaction["molecules"])) {
					foreach ($reaction["molecules"] as $idx => $molecule) { // rc_$subquery_number_$idx
						if (count($molecule["atoms"])==0) {
							continue;
						}
						$st_name="rc_".$subquery_number."_".$idx;
						if ($idx<$reaction["reactants"]) {
							$roles="1,2";
						}
						else {
							$roles="6";
						}
						// die nötigen felder abfragen lassen
						$filter_obj["selects"][$subquery_number].=getSubtableSelect($st_name,"molecule_serialized").getSubtableSelect($st_name,"smiles_stereo").getSubtableSelect($st_name,"role").getSubtableSelect($st_name,"reaction_chemical_id"); // molfile_blob
						
						// die nötigen Verknüpfungen, unterschiedlich für local/remote
						$join_tail=" AS ".$st_name." ON ".$table.".".$pk_name."=".$st_name.".".$pk_name;
						
						$filter_obj["local_joins"][$subquery_number].=" INNER JOIN ".$tables[$table]["fields"][ $filter_obj["crits"][$subquery_number] ]["local_chemical_table"].$join_tail;
						$filter_obj["remote_joins"][$subquery_number].=" INNER JOIN ".$tables[$table]["fields"][ $filter_obj["crits"][$subquery_number] ]["remote_chemical_table"].$join_tail;
						
						// FP-Bedingungen
						$rxn_conditions[$idx]=getSimilarFilter($molecule,$st_name)." AND ".$st_name.".role IN(".$roles.")";
					}
				}
				if (count($rxn_conditions)) {
					$filter_obj["subqueries"][$subquery_number]=join(" AND ",$rxn_conditions);
				}
				else {
					$filter_obj["subqueries"][$subquery_number]="FALSE";
				}
			}
			elseif (in_array($filter_obj["ops"][$subquery_number],$searchModes["structure"])) { // struktursuche
				$needleParamHash=array("forStructureSearch" => true);
				if (in_array($filter_obj["ops"][$subquery_number],array("ba","ia"))) {
					$needleParamHash["ignoreAtoms"]=true;
				}
				if (in_array($filter_obj["ops"][$subquery_number],array("ba","ib"))) {
					$needleParamHash["ignoreBonds"]=true;
				}
				$filter_obj["substructure"][$subquery_number]=readMolfile($filter_obj["vals"][$subquery_number][1],$needleParamHash); // molfile lesen, smiles generieren
				//~ $filter_obj["vals"][$subquery_number][0]=$filter_obj["substructure"][$subquery_number]["smiles"]; // smiles setzen (das vom applet ist egal)
				$filter_obj["vals"][$subquery_number][0]=addSMILESslashes($filter_obj["substructure"][$subquery_number]["smiles_stereo"]); // smiles setzen (das vom applet ist egal)
				
				if ($filter_obj["ops"][$subquery_number]=="se") {
					$filter_obj["crits"][$subquery_number]="smiles_stereo"; // smiles
					$filter_obj["ops"][$subquery_number]="bn";
				}
				elseif ($filter_obj["ops"][$subquery_number]=="sn") {
					$filter_obj["crits"][$subquery_number]="smiles"; // smiles
					$filter_obj["ops"][$subquery_number]="bn";
					$filter_obj["vals"][$subquery_number][0]=$filter_obj["substructure"][$subquery_number]["smiles"]; // smiles setzen (das vom applet ist egal)
				}
				elseif ($filter_obj["ops"][$subquery_number]=="su" && in_array($filter_obj["vals"][$subquery_number][0],$fp_only_smiles) && !$filter_obj["substructure"][$subquery_number]["has_explicit_h"]) { // keine expl Hs
				       // auto-switch to similarity
				       // für spezielle SMILES gibt Substructure keinen Zusatznutzen -> Zeit sparen und nur Ähnlichkeitssuche machen
					$filter_obj["ops"][$subquery_number]="si";
			       }
			}
			
			// auto virtual fields, rest will be handled "closer to the SQL"
			if (
				is_array($tables[ $filter_obj["selectTable"][$subquery_number] ]["virtualFields"]) && 
				@array_key_exists($filter_obj["crits"][$subquery_number],$tables[ $filter_obj["selectTable"][$subquery_number] ]["virtualFields"])
			) { // virtual fields-------------------------------------------------------------------------------------------------------
				$virtualField_data=& $tables[ $filter_obj["selectTable"][$subquery_number] ]["virtualFields"][$filter_obj["crits"][$subquery_number]];
				switch ($virtualField_data["fieldType"]) {
				case "auto":
					switch ($filter_obj["crits"][$subquery_number]) {
					case "molecule_auto":
						// BESSI
						if (!empty($g_settings["name_migrate_id_mol"]) && isBESSI($filter_obj["vals"][$subquery_number][0])) {
							$filter_obj["selectTable"][$subquery_number]="molecule";
							$filter_obj["crits"][$subquery_number]="migrate_id_mol";
						}
						elseif (isCAS($filter_obj["vals"][$subquery_number][0])) { // is it a cas_nr?
							$filter_obj["selectTable"][$subquery_number]="molecule";
							$filter_obj["crits"][$subquery_number]="cas_nr";
						}
						elseif (isEmpFormula($filter_obj["vals"][$subquery_number][0])) { // is it a sum formula?
							$filter_obj["selectTable"][$subquery_number]="molecule";
							$filter_obj["crits"][$subquery_number]="emp_formula";
							//~ if ($filter_obj["ops"][$subquery_number]=="ex") {
								//~ $filter_obj["ops"][$subquery_number]="ef";
							//~ }
							if ($filter_obj["ops"][$subquery_number]=="ct") {
								$filter_obj["ops"][$subquery_number]="sf";
							}
							elseif ($filter_obj["ops"][$subquery_number]=="nu") {
								// keep
							}
							else {
								//~ $filter_obj["ops"][$subquery_number]="sf";
								$filter_obj["ops"][$subquery_number]="ef";
								$filter_obj["vals"][$subquery_number][0]=getEmpFormulaHill($filter_obj["vals"][$subquery_number][0]);
							}
							//~ $filter_obj["vals"][$subquery_number][0]=getEmpFormulaHill($filter_obj["vals"][$subquery_number][0]);
						}
						else { // must be name
							$filter_obj["selectTable"][$subquery_number]="molecule_names";
							$filter_obj["crits"][$subquery_number]="molecule_name";
						}
					break;
					case "reaction_chemical_auto":
						if (isCAS($filter_obj["vals"][$subquery_number][0])) { // is it a cas_nr?
							$filter_obj["selectTable"][$subquery_number]="reaction_chemical";
							$filter_obj["crits"][$subquery_number]="cas_nr";
						}
						elseif (isEmpFormula($filter_obj["vals"][$subquery_number][0])) { // is it a sum formula?
							$filter_obj["selectTable"][$subquery_number]="reaction_chemical";
							$filter_obj["crits"][$subquery_number]="emp_formula";
							//~ if ($filter_obj["ops"][$subquery_number]=="ex") {
								//~ $filter_obj["ops"][$subquery_number]="ef";
							//~ }
							//~ elseif ($filter_obj["ops"][$subquery_number]=="nu") {
								//~ // keep
							//~ }
							//~ else {
								//~ $filter_obj["ops"][$subquery_number]="sf";
							//~ }
							$filter_obj["ops"][$subquery_number]="bn";
							$filter_obj["vals"][$subquery_number][0]=getEmpFormulaHill($filter_obj["vals"][$subquery_number][0]);
						}
						else { // must be name
							$filter_obj["selectTable"][$subquery_number]="reaction_chemical";
							$filter_obj["crits"][$subquery_number]="standard_name";
						}
					break;
					}
				break;
				}
			}
			// ende auto
			
		       $defaultHandling=false;
			// spezielle filter für substruktur etc
			switch ($filter_obj["ops"][$subquery_number]) {
			case "dn":
			case "sq":
			case "sr":
			// do nothing
			break;
			// structure
			/* case "es": // über canSmiles, molfile ist a, smiles ist b
				$filter_obj["subqueries"][$subquery_number].=" LIKE BINARY ".fixStrSQL($_REQUEST["val".$subquery_number]);
			break; use bn instead*/
			case "ia":
			case "ib":
			case "ba":
			case "su": // über molfile
				if (empty($filter_obj["vals"][$subquery_number][1])) {
					$filter_obj["subqueries"][$subquery_number]="FALSE";
					break;
				}
			break;
			case "si": // über molfile
				if (empty($filter_obj["vals"][$subquery_number][1])) {
					$filter_obj["subqueries"][$subquery_number]="FALSE";
				}
				else {
					// $molecule=readMolfile($filter_obj["vals"][$subquery_number][1]);
					$filter_obj["subqueries"][$subquery_number]=getSimilarFilter($filter_obj["substructure"][$subquery_number]);
					$filter_obj["optimised_order"]=array(
						array("field" => "smiles_stereo LIKE BINARY ".fixStrSQL($filter_obj["substructure"][$subquery_number]["smiles_stereo"]), "order" => "DESC", "no_hints" => true),
					);
				}
			break;
			case "ef":
				if (!empFormulaHasWildcard($filter_obj["vals"][$subquery_number][0])) {
					$filter_obj["ops"][$subquery_number]="bn";
					$defaultHandling=true;
					break; // re-evaluate
				}
			// no break
			case "sf":
				$filter_obj["substructure"][$subquery_number]=readSumFormula($filter_obj["vals"][$subquery_number][0]);
			break;
			default:
				$defaultHandling=true;
			}
			
			if ($defaultHandling) {
				list($filter_obj["subqueries"][$subquery_number],$filter_obj["optimised_order"])=procSubquery(
					$db_list,
					$filter_obj["forTable"][$subquery_number],
					$filter_obj["selectTable"][$subquery_number],
					$filter_obj["crits"][$subquery_number],
					$filter_obj["ops"][$subquery_number],
					$filter_obj["vals"][$subquery_number]
				);
				//~ echo $subquery_number.":".$filter_obj["subqueries"][$subquery_number]."<br>";
			}
			
			if ($filter_obj["subqueries"][$subquery_number]=="FALSE") { // ungültige Bedingungen markieren
				$invalid_cond[]=$subquery_number;
			}
		}
	}
	//~ print_r($invalid_cond);
	//~ print_r($filter_obj);
	// 4. Substruktursuchen durchführen
	if (is_array($filter_obj["substructure"])) foreach ($filter_obj["substructure"] as $subquery_number => $molecule) {
		if (in_array($filter_obj["ops"][$subquery_number],array("su","ia","ib","ba","sf","ef"))) {
			if (empty($filter_obj["substructure"][$subquery_number]) || $filter_obj["substructure"][$subquery_number]["emp_formula"]=="") {
				$filter_obj["subqueries"][$subquery_number]="FALSE";
				$invalid_cond[]=$subquery_number;
				continue;
			}
			
			$filter_obj["subqueries"][$subquery_number]=getSubstructureFilter(
				$db_list,
				array(
					"table" => $table, 
					"dbs" => $dbs, 
					"db_filter" => $paramHash["db_filter"], 
					"selectTable" => $filter_obj["selectTable"][$subquery_number], 
				), 
				$molecule, 
				$filter_obj["ops"][$subquery_number]
			); // check only changed structures if possible 
			
			$filter_obj["optimised_order"]=array(
				array("field" => "smiles_stereo LIKE BINARY ".fixStrSQL($molecule["smiles_stereo"]), "order" => "DESC", "no_hints" => true),
			);
			// must be $table, not $filter_obj["selectTable"][$subquery_number], much slower otherwise
		}
	}
		/* foreach ($filter_obj["subformula"] as $subquery_number => $molecule) {
			$filter_obj["subqueries"][$subquery_number]=getSubstructureFilter(array("table" => $table, "dbs" => $dbs, "db_filter" => $paramHash["db_filter"] ),$molecule,"formula"); // check only changed structures if possible
		}*/
	// 5. sq behandeln
	for ($b=0;$b<count($filter_obj["subquery_numbers"]);$b++) {
		$subquery_number=& $filter_obj["subquery_numbers"][$b];
		if ($filter_obj["ops"][$subquery_number]=="sq") {
			// <9> AND <17> prüfen
			$filter_obj["vals"][$subquery_number][0]=checkQueryPattern($filter_obj["vals"][$subquery_number][0],$invalid_cond);
			// Unterabfrage einbauen
			for ($a=0;$a<count($db_list);$a++) {
				$db_id=$db_list[$a];
				$this_subquery=$table.".".$pk_name." IN( SELECT ".$filter_obj["crits"][$subquery_number]." FROM ".getTableFrom($filter_obj["selectTable"][$subquery_number],$db_id);
				//~ if ($db_id=="-1") {
					//~ $this_subquery.=$query[ $filter_obj["selectTable"][$subquery_number] ]["local_from"];
				//~ }
				//~ else {
					//~ $this_subquery.=$query[ $filter_obj["selectTable"][$subquery_number] ]["remote_from"];
				//~ }
				$this_subquery.=" WHERE NOT ".$filter_obj["crits"][$subquery_number]." IS NULL AND (".replaceQueryPlaceholders($filter_obj["vals"][$subquery_number][0],$filter_obj["subqueries"])."))";
				$filter_obj["subqueries"][$subquery_number][$db_id]=$this_subquery;
			}
		}
	}
	//~ print_r($filter_obj);
	
	// 6. aktuelle Queries bauen (ggf. für jede db einzeln)
	$filter_obj["query_pattern"]=checkQueryPattern($filter_obj["query_pattern"],$invalid_cond);
	$filter_obj["select_pattern"]=checkQueryPattern($filter_obj["select_pattern"],$invalid_cond);
	$filter_obj["query_string"]=replaceQueryPlaceholders($filter_obj["query_pattern"],$filter_obj["subqueries"]);
	$filter_obj["select_string"]=replaceQueryPlaceholders($filter_obj["select_pattern"],$filter_obj["subqueries"]);
	//~ print_r($filter_obj);die();
	
	// 7. bei Reaktionssuche: Abfrage ausführen und Reaktionsstruktur-Filter, IN(...)-Abfrage als query-string setzen
	//~ print_r($filter_obj);
	if (arrCount($filter_obj["selects"]) && arrCount($filter_obj["subreaction"]) && arrCount($filter_obj["local_joins"]) && arrCount($filter_obj["remote_joins"])) {
		// , "db_filter" => $paramHash["db_filter"]
		
		$results=mysql_select_array(array(
			"dbs" => $dbs, 
			"table" => $table, 
			"quick" => true, //1, 
			"distinct" => DISTINCT, // important to check all candidates!!!
			"filter" => $filter_obj["query_string"], 
			"db_filter" => $paramHash["db_filter"], // check only changed stuff
			"selects" => join($filter_obj["selects"]), 
			"local_joins" => join($filter_obj["local_joins"]), 
			"remote_joins" => join($filter_obj["remote_joins"]),
			//~ "order_obj" => array(),
		));
		
		// save a lot of time, because we use the cached stuff later
		//~ unset($filter_obj["selects"]);
		//~ unset($filter_obj["local_joins"]);
		//~ unset($filter_obj["remote_joins"]);
		
		//~ echo "X".$filter_obj["query_string"]."X";
		//~ print_r($results);die();
		
		$good_pks=array();
		$good_smiles=array();
		$bad_smiles=array();
		$haystackParamHash=array("quickMode" => true);
		
		// leere arrays vorbereiten
		$subreactions=array_keys($filter_obj["subreaction"]);
		for ($a=0;$a<count($subreactions);$a++) {
			$subquery_number=& $subreactions[$a];
			$reaction=& $filter_obj["subreaction"][$subquery_number];
			$good_smiles[$subquery_number]=array();
			$bad_smiles[$subquery_number]=array();
			for ($idx=0;$idx<count($reaction["molecules"]);$idx++) {
				$good_smiles[$subquery_number][$idx]=array();
				$bad_smiles[$subquery_number][$idx]=array();
			}
		}
		
		// Ergebnisse durchgehen
		for ($a=0;$a<count($results);$a++) { // 3
			if (!is_array($good_pks[ $results[$a]["db_id"] ])) {
				$good_pks[ $results[$a]["db_id"] ]=array();
			}
			if (in_array($results[$a]["pk"],$good_pks[ $results[$a]["db_id"] ])) {
				continue;
			}
			// Reaktionskandidaten durchgehen
			foreach ($filter_obj["subreaction"] as $subquery_number => $reaction) { // 2
				// chemikalien der suche durchgehen
				if (is_array($reaction["molecules"])) foreach ($reaction["molecules"] as $idx => $molecule) { // 1
					// teile des kandidaten durchgehen
					if ($molecule["fp_only_smiles"]) { // für suchstruktur reicht fingerprint, bereits markiert, keine weitere prüfung nötig
						continue;
					}
					if (!isset($molecule["fp_only_smiles"])) { // prüfen, ob für diese suchstruktur der Fingerprint reicht
						$molref=& $filter_obj["subreaction"][$subquery_number]["molecules"][$idx];
						if (in_array($molref["smiles_stereo"],$fp_only_smiles)) {
							$molref["fp_only_smiles"]=true; // reicht, markieren
							continue;
						}
						else {
							$molref["fp_only_smiles"]=false; // reicht nicht, markieren
						}
					}
					// reicht SMILES für Entscheidung?
					$st_name="rc_".$subquery_number."_".$idx;
					$smiles_name=$st_name."_smiles_stereo";
					if (in_array($results[$a][$smiles_name],$good_smiles[$subquery_number][$idx])) { // der kandidat wurde bereits früher geprüft und für GUT befunden
						continue; // nächste komponente
					}
					elseif (in_array($results[$a][$smiles_name],$bad_smiles[$subquery_number][$idx])) { // der kandidat wurde bereits früher geprüft und für SCHLECHT befunden
						continue 3; // raus, nicht zu good_pks, nächstes $result
					}
					
					// Substruktursuche machen
					//~ $molfile_name=$st_name."_molfile_blob";
					$molfile_name=$st_name."_molecule_serialized";
					//~ $haystackMolecule=readMolfile($results[$a][$molfile_name],$haystackParamHash); // Ranks and Rings sufficient, standard conform mode!! 128+32+4+2
					$haystackMolecule=unserialize(@gzuncompress($results[$a][$molfile_name]));
					//~ renewBondsFromNeighbours($haystackMolecule);
					
					if (getSubstMatch($molecule,$haystackMolecule)) { // quick
						// "Erfahrungen" in SMILES-Liste speichern
						$good_smiles[$subquery_number][$idx][]=$results[$a][$smiles_name];
					}
					else {
						$bad_smiles[$subquery_number][$idx][]=$results[$a][$smiles_name];
						continue 3; // raus, nicht zu good_pks, nächstes $result
					}
				}
			}
			// aufnehmen in good_pks
			$good_pks[ $results[$a]["db_id"] ][]=$results[$a]["pk"]; // enthält möglicherweise dubletten, nicht so schlimm
		}
		//~ print_r($good_smiles);
		//~ print_r($bad_smiles);die();
		if (is_array($db_list)) foreach ($db_list as $db_id) {
			if (count($good_pks[$db_id])) {
				$good_pks[$db_id]=$table.".".$pk_name." IN(".join(",",$good_pks[$db_id]).")";
			}
			else {
				$good_pks[$db_id]="FALSE";
			}
		}
		unset($filter_obj["query_string"]);
		multiConcat($filter_obj["query_string"],$good_pks);
	}
	unset($filter_obj["optimised_order"]);
	
	// get pk for select_query
	if (!empty($filter_obj["select_string"])) {
		list($selected_result)=mysql_select_array(array(
			"dbs" => $dbs, 
			"table" => $table, 
			"quick" => true, //1, 
			"filter" => joinIfNotEmpty(array($filter_obj["query_string"],$filter_obj["select_string"])," AND "), 
			"limit" => 1,
		));
		$filter_obj["goto"]=$selected_result;
	}
	
	//~ print_r($good_pks);
	//~ print_r($filter_obj);die();
	//~ die($table);
        //~ print_r($filter_obj["query_string"]);
	return $filter_obj;
	/*
	filter_obj["query_pattern"] (<1> AND <2>) OR <3>
	["subqueries"]
	["subquery_numbers"]
	["substructure"]
	*/
}

/*--------------------------------------------------------------------------------------------------
/ Function: getRangeBorders
/
/ Purpose: get lower and upper limit for numeric and date searches out of string $val, considering <,>,=,-; using $tolerance if only one value given
/
/ Parameter:
/ 		$type : text defining field type, (date or numeric)
/ 		$val : user entered condition
/ 		$tolerance : custom set tolerance factor
/
/ Return : array of lower and upper limit
/------------------------------------------------------------
/ History:
/ 2009-07-14 RUD01 Created
/ 2009-07-15 RUD02 added =, added , as . by using getNumber
..--------------------------------------------------------------------------------------------------*/
function getRangeBorders($type,$val,$tolerance=0.05) { // $tolerance is irrelevant for date
	// von-bis oder 5% Toleranz
	if (strpos($val,"<")!==FALSE) {
		list($low,$high)=explode("<",$val);
	}
	elseif (strpos($val,">")!==FALSE) {
		list($high,$low)=explode(">",$val);
	}
	elseif (strpos($val,"=")!==FALSE) {
		$low=$val;
		$high=$val;
	}
	elseif (strpos($val,"-")!==FALSE) {
		preg_match("/^\(?(\-?[\d\.\,]*)\)?\-\(?(\-?[\d\.\,]*)\)?\$/",$val,$range_match);
		$low=$range_match[1];
		$high=$range_match[2];
	}
	elseif ($type=="date" || strpos($val,"=")!==FALSE) {
		$low=$val;
		$high=$val;
	}
	elseif ($type=="number") {
		$val=getNumber($val);
		$low=$val*(1-$tolerance);
		$high=$val*(1+$tolerance);
	}
	
	switch ($type) {
	case "number";
		$low=getNumber($low);
		$high=getNumber($high);
		if ($low==="") {
			$low=-2147483648;
		}
		if ($high==="") {
			$high=2147483648;
		}
		if ($low>$high) {
			swap($low,$high);
		}
	break;
	case "date":
		if ($low==="") {
			$low="01.01.1900";
		}
		if ($high==="") {
			$high="31.12.2100";
		}
	break;
	}
	return array($low,$high);
}

/*--------------------------------------------------------------------------------------------------
/ Function: procSubquery
/
/ Purpose: create SQL WHERE condition for a single criterium depending on the operation ($op), also handling special functions like molecule_auto
/
/ Parameter:
/ 		$db_list : array of db_id's that is being queried
/ 		$table : table being queried
/ 		$crit_table : table where the field is located that is compared to the condition defined by $op and $vals
/ 		$crit : field to be compared, may be "virtual_field"
/ 		$op : string of two characters that defines the comparison operation
/ 		$vals : array that contains user-entered data which will be compared, [0] is the search string, [1] is the unit (if applies)
/
/ Return : array of SQL string that can be used for WHERE clause and string for ORDER BY clause for automatic sorting of results to present best hit at top, currently not used
/------------------------------------------------------------
/ History:
/ 2009-07-14 RUD01 Created
/ 2009-07-15 RUD02 added =, added , as . by using getNumber
..--------------------------------------------------------------------------------------------------*/
function procSubquery($db_list,$table,$crit_table,$crit,$op,$vals) { // gibt eine Bedingung für WHERE zurück
	global $searchModes,$price_currency_list,$tables,$query,$g_settings;
	//~ echo $crit_table."X".$crit."X".$op."X".$val."<br>";
	
	$field_type=$tables[$crit_table]["fields"][$crit]["search"];
	if ($field_type=="range") {
		$low_name=$tables[$crit_table]["fields"][$crit]["low_name"];
	}
	
	// get unit factor, how about m/v with density
	
	if (strpos($crit,"/")!==FALSE) { // split at / if exists
		list($crit,$crit2)=explode("/",$crit,2);
	}
	
	// special handling for certain columns
	if ($crit=="chemical_storage_barcode" && startswith($vals[0],findBarcodePrefixForPk("chemical_storage"))) {
		$crit="chemical_storage_id";
		$vals[0]=intval(substr($vals[0],1,strlen($vals[0])-2));
	}
	
	// handling for special TYPES of columns and normal columns
	if (isset($tables[$crit_table]["fields"][$crit]["isVolumeCol"])) { // amount, actual_amount
		$amount_data=& $tables[$crit_table]["fields"][$crit];
		
		$density="IF(ISNULL(".$amount_data["densityCol"].") OR ".$amount_data["densityCol"]."<0,1,".$amount_data["densityCol"].")";
		$crit_table="";
		
		// 																				unit is mass											unit is volume
		$crit="IF((SELECT unit_type FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch($vals[1])." LIMIT 1)=\"m\", IF(".$amount_data["isVolumeCol"].",".$crit."*".$density.",".$crit."), IF(".$amount_data["isVolumeCol"].",".$crit.",".$crit."/".$density."))";
		
		$unitFactor="*(SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch($vals[1])." LIMIT 1)";
	}
	elseif (in_array($op,$searchModes["money"]) && in_array($vals[1],$price_currency_list)) { // Währung
		//~ $unitFactor=" AND ".$crit."_currency LIKE ".fixStrSQL($vals[1]);
	}
	elseif (in_array($op,$searchModes["num_unit"]) && !empty($vals[1])) { // normale Einheiten
		// floatval($vals[0])."*(SELECT unit_factor FROM units WHERE  unit_name LIKE BINARY ".fixStrSQL($vals[1]).")"
		$unitFactor="*(SELECT unit_factor FROM units WHERE unit_name LIKE BINARY ".fixStrSQLSearch($vals[1])." LIMIT 1)";
	}
	//~ elseif (strpos($crit,"/")!==FALSE) { // split at / if exists
		//~ list($crit,$crit2)=explode("/",$crit,2);
	//~ }
	
	if (is_array($tables[$crit_table]["virtualFields"]) && @array_key_exists($crit,$tables[$crit_table]["virtualFields"])) { // virtual fields-------------------------------------------------------------------------------------------------------
		$virtualField_data=& $tables[$crit_table]["virtualFields"][$crit];
		switch ($virtualField_data["fieldType"]) {
		case "count":
			$crit=array();
			if (is_array($db_list)) foreach ($db_list as $db_id) {
				//~ $thisTable=getTableFrom($virtualField_data["table"],$db_id);
				$thisTable=getTableFrom($virtualField_data["table"],$db_id,true); // joins obsolete
				$crit[$db_id]="(SELECT COUNT(*) FROM ".$thisTable." WHERE ".$virtualField_data["condition"].")";
			}
		break;
		case "flat":
			if (is_array($db_list)) foreach ($db_list as $db_id) {
				$thisTable=getTableFrom($virtualField_data["table"],$db_id);
				$subquery[$db_id]=getLongPrimary($crit_table)." IN(SELECT ".$virtualField_data["fk"]." FROM ".$thisTable." WHERE NOT ".$virtualField_data["fk"]." IS NULL AND ".$virtualField_data["fieldCol"]." LIKE BINARY ".fixStrSQL($crit2)." AND (";
			}
			if (in_array($op,$searchModes["num"])) {
				$crit=$virtualField_data["valueCol"];
				if (isset($virtualField_data["value_lowCol"])) {
					$low_name=$virtualField_data["value_lowCol"];
					$field_type="range";
				}
			}
			else {
				$crit=$virtualField_data["textCol"];
			}
			$closeBracket=true;
		break;
		}
	}
	elseif (!empty($crit_table)) { // otherwise $crit is only the col name---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// 1:n
		if (is_array($query[$table]["join_1n"]) && @array_key_exists($query[$crit_table]["base_table"],$query[$table]["join_1n"])) {
			$fk_sub=$query[$table]["join_1n"][$crit_table]["fk_sub"];
			if (empty($fk_sub)) {
				$fk_sub=getShortPrimary($table);
			}
			$fk=$query[$table]["join_1n"][$crit_table]["fk"];
			if (empty($fk)) {
				$fk=getLongPrimary($table);
			}
			
			if (is_array($db_list)) foreach ($db_list as $db_id) {
				$thisTable=getTableFrom($crit_table,$db_id);
				$subquery[$db_id]=$fk." IN( SELECT ".$fk_sub." FROM ".$thisTable." WHERE NOT ".$fk_sub." IS NULL AND ( ";
			}
			
			$closeBracket=true;
		}
		//~ else {
		$crit=$query[$crit_table]["base_table"].".".$crit;
		//~ }
	}
	
	if (in_array($op,$searchModes["date"])) {
		$crit="DATE(".$crit.")";
	}
	
	// übertrieben
	//~ if ($op!="ca" && $op!="co") {
		//~ $vals[0]=secSQL($vals[0]);
	//~ }

	switch ($op) {
	// any
	case "an":
		multiConcat($subquery,"TRUE");
	break;
	// numeric
	case "ba": // not in list, only for printing persons' barcodes
		multiConcat($subquery,"CAST(".$crit." & ".intval($vals[0])." AS SIGNED INTEGER)=".intval($vals[0]));
	break;
	case "eq":
		if ($field_type=="range") {
			multiConcat($subquery,"(".floatval($vals[0]).$unitFactor." BETWEEN ".$low_name." AND ");
			multiConcat($subquery,$crit);
			multiConcat($subquery," OR (".$low_name." IS NULL AND ");
			multiConcat($subquery,$crit);
			multiConcat($subquery,"=".floatval($vals[0]).$unitFactor."))");
		}
		else {
			multiConcat($subquery,$crit);
			multiConcat($subquery,"=".floatval($vals[0]).$unitFactor);
		}
	break;
	case "gt":
		if ($field_type=="range") {
			multiConcat($subquery,"(".$low_name.">".floatval($vals[0]).$unitFactor." OR ");
			multiConcat($subquery,$crit);
			multiConcat($subquery,">".floatval($vals[0]).$unitFactor.")");
		}
		else {
			multiConcat($subquery,$crit);
			multiConcat($subquery,">".floatval($vals[0]).$unitFactor);
		}
	break;
	case "lt":
		if ($field_type=="range") {
			multiConcat($subquery,"(".$low_name."<".floatval($vals[0]).$unitFactor." OR ");
			multiConcat($subquery,$crit);
			multiConcat($subquery,"<".floatval($vals[0]).$unitFactor.")");
		}
		else {
			multiConcat($subquery,$crit);
			multiConcat($subquery,"<".floatval($vals[0]).$unitFactor);
		}
	break;
	case "bt":
		switch ($crit) {
		case "literature.literature_year":
			$tolerance=0;
		break;
		case "literature.page_high":
			$tolerance=0.01;
		break;
		default:
			$tolerance=0.05;
		}
		list($low,$high)=getRangeBorders("number",$vals[0],$tolerance);
		$cond=" BETWEEN ".floatval($low).$unitFactor." AND ".floatval($high).$unitFactor;
		if ($field_type=="range") {
			multiConcat($subquery,"(".$low_name.$cond." OR ");
			multiConcat($subquery,$crit);
			multiConcat($subquery,$cond.")");
		}
		else {
			multiConcat($subquery,$crit);
			multiConcat($subquery,$cond);
		}
	break;
	case "in":
		$vals[0]=fixNumberLists($vals[0]);
		if (empty($vals[0]) || $field_type=="range") {
			multiConcat($subquery,"FALSE");
		}
		else {
			multiConcat($subquery,$crit);
			multiConcat($subquery," IN(");
			multiConcat($subquery,$vals[0]); // may be array for db_ids
			multiConcat($subquery,")");
		}
	break;
	case "nu":
		multiConcat($subquery,"(");
		multiConcat($subquery,$crit);
		multiConcat($subquery," IS NULL OR ");
		multiConcat($subquery,$crit);
		multiConcat($subquery,"=\"\")");
	break;
	// bool
	case "on":
		multiConcat($subquery,$crit);
		multiConcat($subquery,"=1");
	break;
	case "of":
		multiConcat($subquery,"NOT ");
		multiConcat($subquery,$crit);
		multiConcat($subquery,"<=>1");
	break;
	// date
	case "de":
		multiConcat($subquery,$crit."=".getSQLdate($vals[0]));
	break;
	case "af":
		multiConcat($subquery,$crit.">".getSQLdate($vals[0]));
	break;
	case "bf":
		multiConcat($subquery,"(".$crit." <=> NULL OR ".$crit."<".getSQLdate($vals[0]).")");
	break;
	case "db":
		list($low,$high)=getRangeBorders("date",$vals[0]);
		
		//~ list($low,$high)=explode("-",$vals[0]);
		if (isEmptyStr($high)) {
			multiConcat($subquery,$crit."=".getSQLdate($low));
		}
		else {
			multiConcat($subquery,$crit." BETWEEN ".getSQLdate($low)." AND ".getSQLdate($high));
		}
	break;
	case "du":
		multiConcat($subquery,"(".$crit." <=> NULL OR ".$crit."=\"0000-00-00\")");
	break;
	// text
	case "ca":
	case "co":
		// 1. parse quot marks, only "
		$vals[0]=trim($vals[0]);
		$fragments=explode("\"",$vals[0]);
		for ($b=0;$b<count($fragments);$b++) {
			if ($b%2) { // ungerade, in den quot-marks
				$words=array($fragments[$b]);
			}
			else { // gerade, außerhalb der quot-marks
				$words=explode(" ",$fragments[$b]);
			}
			for ($a=0;$a<count($words);$a++) {
				//~ $words[$a]=trim($words[$a]);
				if ($words[$a]=="") {
					continue;
				}
				if ($retval!="") {
					if ($op=="co") { // ein Wort
						$retval.=" OR ";
					}
					else { // alle Wörter
						$retval.=" AND ";
					}
				}
				else {
					if ($subquery_number==0) {
						$optimised_order=getOptOrder($crit,$words[$a]);
					}
				}
				$retval.=$crit." LIKE ".fixStrSQL("%".SQLSearch($words[$a])."%");
			}
		}
		if ($retval=="" || count($fragments)==0) {
			$retval="FALSE";
		}
		multiConcat($subquery,$retval);
	break;
	case "ct":
		if ($subquery_number==0) {
			$optimised_order=getOptOrder($crit,$vals[0]);
		}
		multiConcat($subquery,$crit." LIKE ".fixStrSQL("%".SQLSearch($vals[0])."%"));
	break;
	case "ex":
		multiConcat($subquery,$crit." LIKE ".fixStrSQLSearch($vals[0]));
	break;
	case "sw":
		if ($subquery_number==0) {
			$optimised_order=getOptOrder($crit,$vals[0]);
		}
		multiConcat($subquery,$crit." LIKE ".fixStrSQL(SQLSearch($vals[0])."%"));
	break;
	case "ew":
		multiConcat($subquery,$crit." LIKE ".fixStrSQL("%".SQLSearch($vals[0])));
	break;
	case "bn":
		multiConcat($subquery,$crit." LIKE BINARY ".fixStrSQLSearch($vals[0]));
	break;
	case "no":
	default:
		multiConcat($subquery,"FALSE");
	}
	
	// subquery
	if ($closeBracket) {
		multiConcat($subquery,"))");
	}
	return array($subquery,$optimised_order);
}

function checkQueryPattern($pattern,$invalid_cond=array()) { 
	// go through pattern and remove everything but AND OR NOT XOR <d> ( )
	// count ( and ) and add ) if needed
	// space 
	$pattern=str_replace(array("AND","OR","X OR ","NOT","(",")","<",">"),array(" AND "," OR "," XOR "," NOT "," ( "," ) "," <","> "),strtoupper($pattern));
	$elements=explode(" ",$pattern); // may contain many empty
	$level=0;
	for ($a=0;$a<count($elements);$a++) {
		if ($binary_allowed) {
			switch ($elements[$a]) {
			case "AND":
			case "OR":
			case "XOR":
				$binary_allowed=false;
				$new_pattern.=" ".$elements[$a];
			break;
			case ")":
				if ($level<=0) {
					$new_pattern="(".$new_pattern;
				}
				else {
					$level--;
				}
				$new_pattern.=")";
			break;
			// the following are formally invalid but will be fixed silently
			case "NOT":
				$binary_allowed=false;
				$new_pattern.=" AND NOT";
			break;
			case "(":
				$binary_allowed=false;
				$level++;
				$new_pattern.=" AND (";
			break;
			default:
			// check for <numeric>
				preg_match("/<(\d+)>/",$elements[$a],$num);
				if (!isEmptyStr($num[1]) && !in_array($num[1],$invalid_cond)) {
					$new_pattern.=" AND <".$num[1].">";
				}
			}		
		}
		else {
			switch ($elements[$a]) {
			case "(":
				$level++;
				$new_pattern.=" (";
			break;
			case "NOT":
				$new_pattern.=" NOT";
			break;
			default:
			// check for <numeric>
				preg_match("/<(\d+)>/",$elements[$a],$num);
				if (!isEmptyStr($num[1])) { // number found
					$binary_allowed=true;
					if (in_array($num[1],$invalid_cond)) {
						$new_pattern.=" <x>";
					}
					else {
						$new_pattern.=" <".$num[1].">";
					}
				}
			}
		}
	}
	if ($level>0) {
		$new_pattern.=multStr(")",$level);
	}
	do {
	$new_pattern=preg_replace(array(
	"/\s(AND|OR|XOR)(\sNOT)*\s<x>/",
	"/<x>\s(AND|OR|XOR)\s/",
	"/<x>/",
	"/\s(AND|OR|XOR)(\sNOT)*\s\(\s*\)/",
	"/\(\s*\)\s(AND|OR|XOR)\s/",
	"/^\s*(AND|OR|XOR)\s*/", // NOT may be at the front
	"/\s*(AND|OR|XOR|NOT)\s*\$/",
	"/\sNOT\sNOT/",
	"/\(\s*(AND|OR|XOR|NOT)*\s*\)/"
	),"",$new_pattern,-1,$number);
	} while ($number>0);
	
	return $new_pattern;
}

function getFingerprintFilter(& $molecule,$table="",$ignoreMask=array()) {
	for ($a=0;$a<13;$a++) {
		if (isset($ignoreMask[$a])) {
			$check=(intval($molecule["fingerprints"][$a]) & $ignoreMask[$a]);
		}
		else {
			$check=intval($molecule["fingerprints"][$a]);
		}
		
		if ($check!=0) { // save a lot of time
			$retval.=" AND ".$table."fingerprint".($a+1)." >= ".$check." AND (".$table."fingerprint".($a+1)." & ".$check.")=".$check;
		}
		//~ $retval.=" AND CAST(".$table."fingerprint".($a+1)." & ".$check." AS SIGNED INTEGER)=".$check;
	}
	return $retval;
}

function getSimilarFilter(& $molecule,$table="") { // byref is faster as no copy is needed
	if (!empty($table)) {
		$table.=".";
	}
	return "((".$table."mw >= ".($molecule["mw_noH"]-0.5)." OR ".$table."mw IS NULL)".getFingerprintFilter($molecule,$table,array(-16)).")";
}

/* function getSimilarFilterNoBonds(& $molecule) { // byref is faster as no copy is needed
	return "((mw >= ".intval($molecule["mw_noH"])." OR mw IS NULL)".
	" AND CAST(fingerprint1 & ".(intval($molecule["fingerprints"][0]) & -16)." AS SIGNED INTEGER)=".(intval($molecule["fingerprints"][0]) & -16).")"; // Summenformel
	// " AND CAST(fingerprint2 & ".(intval($molecule["fingerprints"][1]) & 262143)." AS SIGNED INTEGER)=".(intval($molecule["fingerprints"][1]) & 262143).")"; // RingGRÖßEN
}

function getSimilarFilterNoAtoms(& $molecule) { // byref is faster as no copy is needed
	return "CAST(fingerprint2 & ".(intval($molecule["fingerprints"][1]) & 262143)." AS SIGNED INTEGER)=".(intval($molecule["fingerprints"][1]) & 262143); // RingGRÖßEN
}*/

function getSumSimilarFilter(& $molecule,$table="") { // byref is faster as no copy is needed
	if (!empty($table)) {
		$table.=".";
	}
	return "(".$table."mw >= ".($molecule["mw"]-0.5).
	" AND ".$table."fingerprint1 >= ".intval($molecule["fingerprints"][0])." AND (".$table."fingerprint1 & ".intval($molecule["fingerprints"][0]).")=".intval($molecule["fingerprints"][0]).")";
}

function getSubstructureFilter($db_list,$paramHash,& $molecule,$mode) { // returns array[db_id]="molecule_id IN(1,4,7,...)"
	global $g_settings,$tables,$query,$searchModes;
	set_time_limit(90);
// byref is faster as no copy is needed
	// Parameter: molecule (parsed), alles für mysql_select_array
	$table=& $paramHash["table"];
	//~ $pk=& $query[$table]["primary"];
	$pk=getLongPrimary($table);
	// zusätzliche Filterbedingungen sind zZt nicht zulässig und werden ÜBERSCHRIEBEN
	// Fingerprint zu Abfrage hinzufügen
	$paramHash["quick"]=true; //2;
	$paramHash["hierarchicalResults"]=RESULTS_HIERARCHICAL; //2;
	
	$haystackParamHash=array("quickMode" => true);
	switch ($mode) {
	/*case "ia": // ignore atoms
		$paramHash["filter"]=getSimilarFilterNoAtoms($molecule);
	case "ba": // ignore bonds and atoms
		$haystackParamHash["ignoreBonds"]=true;
		$paramHash["filter"]="TRUE"; // we must do Ph,Cy,... => 6 and so on
	break;
	case "ib": // ignore bonds
		$haystackParamHash["ignoreBonds"]=true;
		$paramHash["filter"]=getSimilarFilterNoBonds($molecule);
	break;*/
	case "su":
		$paramHash["filter"]=getSimilarFilter($molecule,$paramHash["selectTable"]);
		$paramHash["selects"]=",".$paramHash["selectTable"].".molecule_serialized";
	break;
	case "ef":
	case "sf":
		$paramHash["filter"]=getSumSimilarFilter($molecule,$paramHash["selectTable"]);
		$paramHash["selects"]=",".$paramHash["selectTable"].".emp_formula";
	break;
	}
	
	// Abfrage nach fingerprint
	$db_results=mysql_select_array($paramHash);
	
	// get data for fine filtering
	/*switch ($mode) {
	case "su":
		$paramHash["selects"]=",molecule_serialized";
	// no break;
	case "sf":
		$paramHash["selects"].=",emp_formula";
		$paramHash["distinct"]=NONE;
		$paramHash["filter"]=$pk." IN(";
		$db_results=fixNumberLists($db_results);
		multiConcat($paramHash["filter"],$db_results);
		multiConcat($paramHash["filter"],")");
		$db_results=mysql_select_array($paramHash);
	break;
	}*/
	//~ print_r($db_results);
	//~ die(count($db_results)."X");
	$results=array();
	
	if (in_array($mode,array("ia","ba","ib","su"))) { // Substruktursuche
		
		$no_proc=intval($g_settings["no_processors"]); // make int
		// min 500 Strukturen/Prozessor
		//~ $no_proc=min($no_proc,ceil(count($db_results)/500));
		$no_proc=min($no_proc,ceil($db_results["count"]/500));
		
		//~ if (true || $no_proc<=1) {
			// single proc only, saves serialize/unserialize
		if (is_array($db_results["db"])) foreach ($db_results["db"] as $db_id => $db_data) {
			if (is_array($db_data)) foreach ($db_data as $data) {
				$haystackMolecule=unserialize(@gzuncompress($data["molecule_serialized"]));
				if (getSubstMatch($molecule,$haystackMolecule)) {
					$results[$db_id][]=$data["pk"];
				}
			}
		}
		//~ for ($a=0;$a<count($db_results);$a++) {
			//~ $haystackMolecule=unserialize(@gzuncompress($db_results[$a]["molecule_serialized"]));
			//~ if (getSubstMatch($molecule,$haystackMolecule)) {
				//~ $results[$db_results[$a]["db_id"]][]=$db_results[$a]["pk"];
			//~ }
		//~ }
		/* }
		else {
			// Prozesse erstellen
			$process=array();
			$pipes=array();
			$buffer=array();
			$struc_per_proc=ceil(count($db_results)/$no_proc);
			//~ die($struc_per_proc);
			
			//~ for ($b=0;$b<$no_proc;$b++) {
			for ($b=$no_proc-1;$b>=0;$b--) {
				$process[$b]=proc_open("php",array(array("pipe", "r"),array("pipe", "w"),array("pipe", "w")),$pipes[$b]);
				// $db_results=unserialize(\''.serialize(array_slice($db_results,$b*$struc_per_proc,$struc_per_proc)).'\'); 
				//~ die(serialize(array_splice($db_results,$b*$struc_per_proc,$struc_per_proc)));
				//~ file_put_contents("/tmp/dummy.php",'
				
				//~ fwrite($pipes[$b][0],'< ?php sleep(2); echo time(); ? >');
				fwrite($pipes[$b][0],'
<?php
require_once "lib_molfile.php";
$molecule=unserialize(\''.serialize($molecule).'\');
$db_results=unserialize(\''.str_replace(array("\\","'"),array("\\\\","\'"),serialize(array_splice($db_results,$b*$struc_per_proc,$struc_per_proc))).'\');
$results=array();
for ($a=0;$a<count($db_results);$a++) {
	$haystackMolecule=unserialize(@gzuncompress($db_results[$a]["molecule_serialized"]));
	
	if (getSubstMatch($molecule,$haystackMolecule)) {
		$results[ $db_results[$a]["db_id"] ][]=$db_results[$a]["pk"];
	}
}
echo serialize($results);
?>
');
				fclose($pipes[$b][0]);
			}
			
			// Prozeßstatus abfragen, warten bis alle fertig
			for ($c=0;$c<160;$c++) { // max 80 sec, zZt hardcoded
				usleep(500000);
				for ($b=0;$b<$no_proc;$b++) {
					$status=proc_get_status($process[$b]);
					$buffer[$b].=stream_get_contents($pipes[$b][1]);
					//~ print_r($status);die();
					if ($status["running"]) {
						continue 2;
					}
				}
				break;
			}
			
			//~ print_r($buffer);die();
			// Rückgabewerte aufbauen
			for ($b=0;$b<$no_proc;$b++) {
				//~ die(stream_get_contents($pipes[$b][1]));
				$this_results=unserialize(trim($buffer[$b]));
				if (!empty($_REQUEST["debug"])) {
					$this_error=stream_get_contents($pipes[$b][2]);
					if (!empty($this_error)) {
						die($this_error);
					}
				}
				// cleanup
				fclose($pipes[$b][1]);
				fclose($pipes[$b][2]);
				proc_close($process[$b]);
				
				//~ var_dump($this_results);
				// merge results
				if (is_array($this_results)) foreach ($this_results as $db_id => $pks) {
					if (!is_array($results[$db_id])) {
						$results[$db_id]=array();
					}
					$results[$db_id]=array_merge($results[$db_id],$pks);
				}
			}
			unset($process);
			unset($pipes);
			unset($buffer);
			//~ die();
		}*/
	}
	elseif (in_array($mode,$searchModes["emp_formula"])) { // Subformelsuche
		if (is_array($db_results["db"])) foreach ($db_results["db"] as $db_id => $db_data) {
			if (is_array($db_data)) foreach ($db_data as $data) {
				//~ $haystackMolecule=unserialize(@gzuncompress($data["molecule_serialized"]));
				//~ if (getSubstMatch($molecule,$haystackMolecule)) {
				$haystackMolecule=readSumFormula($data["emp_formula"],$haystackParamHash);
				if (
					getSubEmpFormulaMatch(
						$molecule,
						$haystackMolecule,
						array("exact" => $mode=="ef", )
					)
				) {
					$results[$db_id][]=$data["pk"];
				}
			}
		}
		//~ // Subst-Prüfung
		//~ for ($a=0;$a<count($db_results);$a++) {
			//~ $haystackMolecule=readSumFormula($db_results[$a]["emp_formula"],$haystackParamHash);
			//~ if (getSubEmpFormulaMatch($molecule,$haystackMolecule)) {
				//~ $results[ $db_results[$a]["db_id"] ][]=$db_results[$a]["pk"];
			//~ }
		//~ }
	}
	
	// Rückgabewerte für SQL aufbauen
	if (is_array($db_list)) foreach ($db_list as $db_id) {
		if (count($results[$db_id])) {
			$retval[$db_id]=$pk." IN(".join(",",$results[$db_id]).")";
		}
		else {
			$retval[$db_id]="FALSE"; // no matches
		}
	}
	// print_r($retval);
	return $retval;
}

function replaceQueryPlaceholders($query_pattern,$subqueries) {
	$gt_pos=-1;
	$newquery=""; // anhand von $query_pattern und $subqueries aufbauen
	do {
		// a simple replacement would likely cause errors if <num> is inserted somewhere
		// < lt
		// > gt
		$lt_pos=strpos($query_pattern,"<",$gt_pos+1);
		if ($lt_pos===FALSE) {
			// add Rest of query string
			multiConcat($newquery,substr($query_pattern,$gt_pos+1));
			break;
		}
		// add everything from >...<
		multiConcat($newquery,substr($query_pattern,$gt_pos+1,$lt_pos-$gt_pos-1));
		$gt_pos=strpos($query_pattern,">",$lt_pos+1);
		// replace by subquery
		$idx=substr($query_pattern,$lt_pos+1,$gt_pos-$lt_pos-1);
		multiConcat($newquery,$subqueries[$idx]);
	} while (true);
	if (is_array($newquery)) {
		array_walk($newquery,"trim_value");
	}
	else {
		$newquery=trim($newquery);
	}
	return $newquery;
}

function getLimits() {
	global $settings;
	/*
	verarbeitet $_REQUEST["page"] und $_REQUEST["per_page"]
	*/
	if ($_REQUEST["per_page"]==-1) { //  || (!empty($_REQUEST["db_id"]) && !empty($_REQUEST["pk"]))
		$page=0;
		$skip=0;
		$per_page=-1;
	}
	else {
		if (isEmptyStr($_REQUEST["per_page"])) {
			$per_page=ifempty($settings["default_per_page"],default_per_page);
			$_REQUEST["per_page"]=$per_page;
		}
		else {
			$_REQUEST["per_page"]+=0;
			if ($_REQUEST["per_page"]<=0) {
				$_REQUEST["per_page"]=ifempty($settings["default_per_page"],default_per_page);
			}
			$per_page=$_REQUEST["per_page"];
		}
		if (!empty($_REQUEST["page"])) { // page beginnt bei 0
			$page=$_REQUEST["page"];
			$skip=$_REQUEST["page"]*$per_page;
		}
		else {
			$page=0;
			$skip=0;
		}
	}
	return array($page,$skip,$per_page);
}


?>