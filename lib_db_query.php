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
require_once "lib_formatting.php";
require_once "lib_constants.php";
require_once "lib_global_settings.php";
require_once "lib_molfile.php";
require_once "lib_output.php";
require_once "lib_db_manip.php";
require_once "lib_db_filter.php";
require_once "lib_server_cache.php";
require_once "lib_array.php";
require_once "lib_db_order_by.php";
require_once "lib_db_query_helper.php";

function switchDB($this_db_name,$dbObj) {
	if (mysqli_query($dbObj,"USE ".secSQL($this_db_name))) {
		if (function_exists("mysqli_set_charset")) {
			mysqli_set_charset($dbObj,CHARSET_TEXT);
		}
		else {
			mysqli_query($dbObj,"SET CHARACTER SET ".CHARSET_TEXT.";");
			mysqli_query($dbObj,"SET NAMES ".CHARSET_TEXT.";");
		}
		return true;
	}
	else {
		return false;
	}
}

function autoRepair($dbObj,$noErrors,$query,$errCode,$errMsg) {
	global $tables,$db,$permissions;
	// only own db
	if ($db!=$dbObj) {
		return false;
	}
	// get table name out of error msg
	switch ($errCode) {
	case 1194:
		$table=sscanf($errMsg,"Table '%s' is marked as crashed and should be repaired");
	break;
	case 1195:
		$table=sscanf($errMsg,"Table '%s' is marked as crashed and last (automatic?) repair failed");
	break;
	default:
		if ($noErrors) {
			return false;
		}
		dieAsync($query." ".$errMsg.print_r(debug_backtrace(),true));
	}
	
	// check if table name is valid by checking the write permissions
	if ($tables[$table]["writePerm"] & $permissions) {
		$result=mysqli_query($dbObj,"REPAIR TABLE ".$table.";");
		return $result;
	}
	return false;
}

function mysql_select_array_from_dbObj($query,$dbObj,$paramHash=array()) {
	/*
	holt daten als 2dim Array aus einer Tabelle: 
	fields:Felder, die geholt werden
	query: FROM table JOIN...
	assoc: MYSQLI_ASSOC, MYSQLI_NUM oder MYSQLI_BOTH
	dbObj: Handler der Datenbank
	db_id: other_db_id, wird in array unter db_id und/oder am Ende eingefügt
	distinct: verhindert Dubletten (bei mehreren Namen für eine Substanz), kostet Power
	*/
	global $noRequests;
	if (empty($dbObj)) {
		return array();
	}
	$noRequests++;
	//~ $starttime=microtime();
	$sql="";
	if (!$paramHash["noAutoSelect"]) {
		$sql.="SELECT ";
	}
	$sql.=($paramHash["distinct"]==DISTINCT?"DISTINCT ":"").$query.";";
	//~ echo microtime(true).";\n<br>";
	//~ print_r($_REQUEST);
	//~ echo "<!--".$sql."-->\n";
	switch (db_system) {
	case "MySQL":
		$result=mysqli_query($dbObj,$sql);
		if (!$result) { // error
			// try to repair
			if (autoRepair($dbObj,$paramHash["noErrors"],$query,mysqli_errno($dbObj),mysqli_error($dbObj))) {
				$result=mysqli_query($dbObj,$sql);
			}
			if (!$result && !$paramHash["noErrors"]) {
				dieAsync($query." ".mysqli_error($dbObj).print_r(debug_backtrace(),true));
			}
		}
		if (empty($result)) {
			return array();
		}
		$totalCount=mysqli_num_rows($result);
		for($a=0;$a<$totalCount;$a++) {
			$ret_val[$a]=mysqli_fetch_array($result,MYSQLI_ASSOC);
		}
		mysqli_free_result($result);
	break;
	case "Oracle": // have custom efficient code for Oracle here
		
	break;
	}
	// echo $query."X".count($ret_val);
	// print_r($ret_val);
	return $ret_val;
}

function getDbFilterStr($filter,$db_id,$pk,$db_filter,$query_filter) { // return filter for primary keys in array $db_filter_pks merged with standard filter
	// Unterscheidung zw stat Filter und Spezialfilter für jede DB
	$nothing=" WHERE FALSE";
	if (is_array($filter)) {
		$this_filter=$filter[$db_id];
		if (empty($this_filter)) {
			return $nothing;
		}
	}
	else {
		$this_filter=$filter;
	}
	if (is_array($db_filter) && isEmptyStr($db_filter[$db_id])) { // cached and nothing changed
		return $nothing;
	}
	return ifnotempty(" WHERE (",
		joinIfNotEmpty(
			array(
				$this_filter, 
				ifnotempty($pk." IN(",
					secSQL(@join(",",$db_filter[$db_id]))
				,")"),
				$query_filter
			)
			,") AND ("
		)
	,") ");
}

function getSubqueryFilter($row,$criteria,$variables,$conjunction) { // erzeugt filterbedingung für unterabfragen (zB gebinde zu molekül)
	$crit_count=count($criteria);
	if ($crit_count==0) {
		return "TRUE";
	}
	if ($crit_count!=count($variables)) {
		return "TRUE";
	}
	$retval="";
	$conditions=array();
	for ($a=0;$a<$crit_count;$a++) {
		$conditions[]=$criteria[$a].fixNull($row[ $variables[$a] ]);
	}
	$retval.=join(" ".$conjunction." ",$conditions);
	return $retval;
}

function handle_subqueries_for_dbObj($dbObj,$db_id,$db_beauty_name,& $results, $table, $flags) {
							// Datenbankzugriff				Ergliste	Tabelle	Optionen
	//~ echo($table."X".$flags."X");
	global $query,$person_id,$lang_id;
	
	// echo "<pre>";
	if (is_array($results)) for ($a=0;$a<count($results);$a++) { // each row
		$baseTable=& $query[$table]["base_table"];
		$results[$a]["db_id"]=$db_id;
		
		// molecule names
		if ( $flags!=0 && in_array($baseTable, array("molecule","chemical_storage","supplier_offer") ) ) { // get molecule names
		
			if (molecule_names_by_lang) {
				$lang_id=$lang;
			}
			if (!empty($results[$a]["molecule_id"])) do { // wenn eigene Sprache keine Resultate liefert, wird Einschränkung aufgehoben
				$subtable_name="molecule_names";
				$subquery_base=getBaseTable($subtable_name);
				$subtable=& $query[$subtable_name];
				
				// use archive_entity_id condition only for versioned subtables
				if (archiveRequest($subquery_base)) {
					$archiveQuery=" AND ".$subquery_base.".archive_entity_id=".$_REQUEST["archive_entity"];
				}
				else {
					$archiveQuery="";
				}
				
				$fields=array();
				addFieldListForQuery($fields,$subtable_name,($db_id==-1));
				
				$query_str=joinIfNotEmpty($fields,",").
					" FROM ".
					getTableFrom($subtable_name,$db_id).
					" WHERE molecule_id=".$results[$a]["molecule_id"].
					ifnotempty(" AND language_id=\"",$lang_id,"\"").
					$archiveQuery.
					" ORDER BY is_standard DESC";
				
				$subresult=mysql_select_array_from_dbObj($query_str, $dbObj);
				if ($lang_id=="") {
					break; // sehr wichtig
				}
				$lang_id="";
			} while (count($subresult)==0);
			if (count($subresult)) {
				for ($b=0;$b<count($subresult);$b++) {
					$results[$a]["molecule_names_array"][]=$subresult[$b]["molecule_name"];
					// molecule_name (-> [molecule_names][0]), molecule_names (alt) und molecule_names_edit müssen in den jew. Funktionen verankert werden
					$results[$a]["is_trivial_name"][]=$subresult[$b]["is_trivial_name"];
				}
				extendMoleculeNames($results[$a]);
			}
		}
		
		// standard subqueries
		if (is_array($query[$table]["subqueries"])) for ($b=0;$b<count($query[$table]["subqueries"]);$b++) { // each subquery
			$subquery=& $query[$table]["subqueries"][$b]; // Eintrag speziell für Subquery
			if ($subquery["skip"]) { // allow dynamic skipping of subqueries
				continue;
			}
			if ($subquery["name"]=="versions" && !empty($_REQUEST["archive_entity"])) { // waste of time, already in form from up-to-date entry
				continue;
			}
			$subtable_name=$subquery["table"];
			$subquery_base=getBaseTable($subtable_name);
			
			// do we have read permission? If not, set skip to true to avoid check each and every time
			if (!mayRead($subquery_base)) {
				$query[$table]["subqueries"][$b]["skip"]=true;
				continue;
			}
			
			$subtable=& $query[$subtable_name]; // Eintrag, auf den sich die Unterabfrage bezieht
			$pkName=getLongPrimary($subtable["base_table"]);
			
			// use archive_entity_id condition only for versioned subtables
			if (!$subquery["ignore_archive"] && archiveRequest($subquery_base)) {
				$archiveQuery=" AND ".$subquery_base.".archive_entity_id=".$_REQUEST["archive_entity"];
			}
			else {
				$archiveQuery="";
				
				//~ if (!empty($subquery["field_db_id"]) && $orig_db_id==-1) { // nicht kreuz und quer
					//~ // query other_db defined by field
					//~ $db_id=$results[$a][ $subquery["field_db_id"] ];
					//~ if (empty($db_id) || $db_id=-1) {
						//~ $dbObj=$orig_db;
					//~ }
					//~ else {
						//~ $dbObj=getForeignDbObj($db_id); // connection will be kept open
					//~ }
				//~ }
			}
			
			switch ($subquery["action"]) {
			case "local_join": // join into local database
			case "uid_join": // fake joins on other databases using UIDs, bidirectional, but slower
				if (($flags & QUERY_SKIP_UID_JOIN)==0) { // prevent loops
					list($subresult)=mysql_select_array(array(
						"table" => $subtable_name, 
						"dbs" => (($subquery["action"]=="local_join")?-1:""), // ALL, UID must be unique, like a global pk
						"filter" => $subquery["uid_search"].ifempty($subquery["uid_op"],"=").fixStrSQLSearch($results[$a][ $subquery["uid_value"] ]), // do not use getSubqueryFilter, unsuitable for non-numeric
						"flags" => $subquery["flags"] | QUERY_SKIP_UID_JOIN, // make subqueries also, like for order_alternative
						"limit" => 1, 
					));
					
					if ($flags & QUERY_SUBQUERY_FLAT_PRIORITY) {
						$results[$a]=arr_merge($results[$a],$subresult); // give priority to values from subquery
					}
					else {
						$results[$a]=arr_merge($subresult,$results[$a]); // give priority to values from original database query
					}
				}
			break;
			
			case "any_join": // fake joins on other databases using db_id, unidirectional, faster
				// only for own stuff
				if ($db_id!=-1) {
					continue;
				}
				
				$join_db_id=$results[$a][ $subquery["field_db_id"] ];
				if (empty($join_db_id) || $join_db_id==-1) {
					$extDb=$dbObj;
				}
				else {
					$extDb=getForeignDbObjFromDBid($join_db_id); // connection will be kept open
					if (!$extDb) {
						continue;
					}
				}
				
				$fields=array();
				addFieldListForQuery($fields,$subtable_name,($db_id==-1));
				$query_str=($subtable["distinct"]==DISTINCT?"DISTINCT ":"").joinIfNotEmpty($fields,",");
				
				// default filter
				$filterText=getSubqueryFilter($results[$a], $subquery["criteria"], $subquery["variables"], $subquery["conjunction"] );
				if (!empty($subtable["filter"])) {
					$filterText="(".$filterText.") AND ".$subtable["filter"];
				}
				
				// ORDER BY
				if (count($subquery["order_obj"])) { // Sortierung speziell für Unterabfrage gesetzt
					$order_obj=$subquery["order_obj"];
				}
				else { // normale Sortierung der Tabelle nehmen
					$order_obj=$subtable["order_obj"];
				}
				
				$order_by=getOrderStr($order_obj);
				
				$query_str.=" FROM ".
					getTableFrom($subtable_name,$join_db_id). // may be remote or dummy
					" WHERE ".
					$filterText;
				
				// Distinct => group by
				if ($subtable["distinct"]==GROUP_BY) {
					$query_str.=getGroupBy($subtable_name);
				}
				
				$query_str.=ifnotempty(" ORDER BY ",$order_by ).
					" LIMIT 1";
				
				list($subresult)=mysql_select_array_from_dbObj($query_str,$extDb); // only one
				
				if ($flags & QUERY_SUBQUERY_FLAT_PRIORITY) {
					$results[$a]=arr_merge($results[$a],$subresult); // give priority to values from original database query
				}
				else {
					$results[$a]=arr_merge($subresult,$results[$a]); // give priority to values from original database query
				}
			break;
			
			case "flat": // merge name-value-pairs directly in results, IF there is no collision (I.E. the original results have higher prio)
				// nameField must be unique under the conditions given, otherwise the results may not be reproducible
				$query_str=$subquery["nameField"].",".$subquery["valueField"].
					" FROM ".
					getTableFrom($subtable_name,$db_id).
					" WHERE ".
					getSubqueryFilter($results[$a], $subquery["criteria"], $subquery["variables"], $subquery["conjunction"] ).
					$archiveQuery;
				
				$subresults=mysql_select_array_from_dbObj($query_str,$dbObj);
				$results[$a][ $subquery["name"] ]=array();
				for ($c=0;$c<count($subresults);$c++) {
					if (!isset($results[$a][ $subresults[$c][ $subquery["nameField"] ] ]) || ($flags & QUERY_SUBQUERY_FLAT_PRIORITY)) { // do not overwrite
						$this_name=$subresults[$c][ $subquery["nameField"] ];
						$results[$a][ $this_name ]=$subresults[$c][ $subquery["valueField"] ];
						$results[$a][ $subquery["name"] ][]=$this_name;
					}
				}
			break;
			
			default:
				if (($subquery["forflags"] & $flags) && ($db_id==-1 || hasTableRemote($subtable["base_table"]) )) { // only when flags match
					if (count($subtable)==0) {
						die($subquery["table"]." is empty.");
					}
					
					$group_by_str="";
					if ($subquery["action"]=="count") {
						$query_str="SQL_CACHE COUNT(".($subtable["distinct"]?"DISTINCT ":"").$pkName.") AS count";
					}
					else {
						// Distinct => group by
						if ($subtable["distinct"]==GROUP_BY && $subquery["action"]!="count") {
							$group_by_str=getGroupBy($subtable_name);
						}
						
						$fields=array();
						addFieldListForQuery($fields,$subtable_name,($db_id==-1));
						$query_str=joinIfNotEmpty($fields,",");
					}
					
					// default filter
					$filterText=getSubqueryFilter($results[$a], $subquery["criteria"], $subquery["variables"], $subquery["conjunction"] );
					if (!empty($subtable["filter"])) {
						$filterText="(".$filterText.") AND ".$subtable["filter"];
					}
					
					// ORDER BY
					if ($subquery["action"]=="count") { // keine Sortierung nötig
						$order_obj=array();
					}
					elseif (arrCount($subquery["order_obj"])) { // Sortierung speziell für Unterabfrage gesetzt
						$order_obj=$subquery["order_obj"];
					}
					else { // normale Sortierung der Tabelle nehmen
						$order_obj=$subtable["order_obj"];
					}
					
					$order_by=getOrderStr($order_obj);
					
					$query_str.=" FROM ".
						getTableFrom($subtable_name,$db_id).
						" WHERE ".
						$filterText.
						$archiveQuery.
						$group_by_str.
						ifnotempty(" ORDER BY ",$order_by);
				
					//~ echo $query_str."\n";
					$subresult=mysql_select_array_from_dbObj($query_str,$dbObj);
					
					// print_r($subresult);
					if ($subquery["action"]=="count") {
						$results[$a][ $subquery["name"] ]=$subresult[0]["count"];
					}
					else {
						// Rekursion (max_level einbauen)
						if ($subquery["action"]=="recursive") {
							//~ print_r($subquery);
							handle_subqueries_for_dbObj($dbObj,$db_id,$db_beauty_name,$subresult,$subquery["table"],$flags);
							//~ $subresult=handle_subqueries_for_dbObj($dbObj,$db_id,$db_beauty_name,$subresult,$subquery["table"],$flags);
							//~ print_r($subresult);
						}
						
						// procFunction
						if (function_exists($subtable["procFunction"])) {
							$subtable["procFunction"]($subresult); // call by ref
						}
						
						$results[$a][ $subquery["name"] ]=$subresult;
						// set db_beauty_name
						setDbBeautyName($results[$a][ $subquery["name"] ],$db_id,$db_beauty_name,0);
					}
				}
			}
		}
	}
}

//~ function setDbBeautyName(& $resultset,$db_id,$db_beauty_name,$table="") { // fügt zu jedem datensatz die datenbank-nr und den "schönen" namen hinzu
function setDbBeautyName(& $resultset,$db_id,$db_beauty_name,$capabilities) { // fügt zu jedem datensatz die datenbank-nr und den "schönen" namen hinzu
	//~ global $db_user;
	//~ if ($db_user=="rudolphi") {
		//~ file_put_contents("/tmp/".time(),print_r(debug_backtrace(),true));
	//~ }
	if (is_array($resultset)) for ($a=0;$a<count($resultset);$a++) {
		$resultset[$a]["db_id"]=$db_id;
		$resultset[$a]["show_db_beauty_name"]=$db_beauty_name;
		//~ $resultset[$a]["show_capabilities"]=$capabilities;
	}
}

// $fields,$query,$assoc,$dbs="-1",$limitFrom=0,$limitCount=-1,$primary_key="NULL",$distinct=false
function mysql_select_array($paramHash) { 
	/* holt die ergebnise von den verschiedenen datenbanken und gibt alls zurück
	dbs: kommagetrennte Liste von other_db_id
	fields: zu holende spalten
	order_by,filter: selbsterklärend
	limit: wird iA nicht genutzt, weil MySQL-caching nicht genutzt wird
	distinct: verhindert Dubletten (bei mehreren Namen für eine Substanz), kostet Power
	quick: 0: nein, 1: quick, 2: Liste von Primärschlüsseln, sonst nix
	*/
	global $db,$other_db_data,$permissions,$query,$err_msg,$tables;
	//~ print_r($paramHash);
	$table=& $paramHash["table"];
	if (!is_array($query[$table])) {
		return array();
	}
	$baseTable=& $query[$table]["base_table"];
	
	if (!isset($query[$table])) {
		return array();
	}
	
	$retval=array();
	
	$flags=& $paramHash["flags"];
	$quick=& $paramHash["quick"];
	
	if ($paramHash["hierarchicalResults"]!=RESULTS_FLAT) {
		$retval["count"]=0;
		if ($paramHash["sortHints"]) {
			$retval["sort_hints"]=array();
		}
		$quick=true;
	}
	
	
	$pk=getLongPrimary($table);
	$fields=array();
	
	if ($quick) {
		$fields[]=$query[$table]["quickfields"];
	}
	else { 
		addFieldListForQuery($fields,$table);
		//~ $fields[]=getFieldListForTables($query[$table]["field_data"]);
		//~ $fields[]=$query[$table]["fields"]; // give this priority
		
		$local_fields=$query[$table]["local_fields"];
		if ($paramHash["export"] && !empty($query[$table]["export_fields"])) {
			$fields[]=$query[$table]["export_fields"];
		}
	}
	
	$db_filter=& $paramHash["db_filter"];
	
	// alles Objekte!!
	if (is_array($paramHash["order_obj"]) && count($paramHash["order_obj"])) {
		$order_obj=$paramHash["order_obj"];
	}
	else {
		$order_obj=$query[$table]["order_obj"];
	}
	
	// List-Hints, take 1st sort key
	if ($paramHash["sortHints"] && is_array($order_obj)) {
		if (!empty($order_obj[0]["field"])) {
			addSortHintField($fields,$order_obj);
			//~ $fields.=getSortHintField($order_obj);
		}
	}
	else {
		$paramHash["sortHints"]=false;
	}
	
	$fields=joinIfNotEmpty($fields,",");
	$order_by=getOrderStr($order_obj);
	
	if (isset($paramHash["distinct"])) {
		$distinct=$paramHash["distinct"];
	}
	else {
		$distinct=$query[$table]["distinct"];
	}
	
	if (!empty($paramHash["dbs"])) {
		$dbs=explode(",",$paramHash["dbs"]);
	}
	
	$limit=$paramHash["limit"];
	
	// common filter for all databases
	$commonFilters=array($query[$table]["filter"]);
	
	if (archiveRequest($baseTable)) { // other_dbs not relevant
		// IFNULL(xyz.archive_entity_id,xyz.xyz_archive_id)=abc.archive_entity_id
		$archiveTable=getArchiveTable($baseTable);
		$archivePkName=getPkName($archiveTable);
		$commonFilters[]="IFNULL(".$baseTable.".archive_entity_id,".$baseTable.".".$archivePkName.")=".fixNull($_REQUEST["archive_entity"]);
		$archiveLimits=",FALSE AS allowDelete,FALSE AS allowEdit,".$baseTable.".version_comment,".$baseTable.".is_autosave";
		if ($tables[$baseTable]["recordCreationChange"]) {
			$action="changed";
			$archiveLimits.=",".getActionBy($baseTable,$action)." AS version_by,".getActionWhen($baseTable,$action)." AS version_when";
		}
	}
	
	if ($paramHash["filterDisabled"] && $tables[$baseTable]["useDisabled"]) {
		$commonFilters[]=$baseTable.".".$baseTable."_disabled IS NULL"; // FR100414 (hope that this is reliable
	}
	$commonFilterText=joinIfNotEmpty($commonFilters," AND ");

	if (!arrCount($dbs) || in_array("-1",$dbs)) {
		// the filter is composed by three parts: a) the filter defined in the $query scheme (for things like my_messages) - always a string, b) the filter defined by the search task ($paramHash["filter"]) which may be a string or an array[db_id] where substructure tasks were replaced by pk IN(1,3,4,..) constructs and c) a $db_filter which is always an array[db_id]=array(1,3,5,...) (or null for new searches) defining the pks to be refreshed whereas the rest comes from the cache 
		// $paramHash["selects"] muß mit comma beginnen
		$sql=$fields.
			ifnotempty(",",$local_fields).
			$archiveLimits.
			$paramHash["selects"]
			." FROM ".getTableFrom($table).$paramHash["local_joins"]
			.getDbFilterStr($paramHash["filter"],-1,$pk,$db_filter,$commonFilterText)
			.($distinct==GROUP_BY?getGroupBy($table):"")
			.ifnotempty(" ORDER BY ",$order_by)
			.ifnotempty(" LIMIT ",$limit);
		
		$retval2=mysql_select_array_from_dbObj($sql,$db,array(
			"distinct" => $distinct, 
			"noErrors" => $paramHash["noErrors"]
		));
		
		// procFilter
		if ($quick && function_exists($query[$table]["procFilter"])) {
			$query[$table]["procFilter"]($retval2); // call by ref
		}
		
		// handle subqueries, only for the results displayed
		if (!$quick) { // saves a lot of time
			handle_subqueries_for_dbObj($db,-1,s("own_database"),$retval2,$table,$flags);
		}
		
		if (!$quick && function_exists($query[$table]["procFunction"])) {
			$query[$table]["procFunction"]($retval2); // call by ref
		}
		
		if ($paramHash["hierarchicalResults"]==RESULTS_FLAT) {
			// set db_beauty_name
			if (arrCount($retval2)>0) {
				setDbBeautyName($retval2,-1,s("own_database"),-1);
				$retval=array_merge($retval,$retval2);
			}
		}
		elseif (is_array($retval2)) {
			if ($paramHash["sortHints"]) for ($b=0;$b<count($retval2);$b++) {
				$retval["sort_hints"][]=$retval2[$b]["sort_hint"];
			}
			if ($paramHash["hierarchicalResults"]==RESULTS_PK_ONLY) for ($b=0;$b<count($retval2);$b++) {
				$retval2[$b]=$retval2[$b]["pk"];
			}
			$retval["db"][-1]=$retval2;
			$retval["count"]+=count($retval2);
		}
	}
	if (hasTableRemoteAccess($baseTable) && $other_db_data) {
		for ($a=0;$a<count($other_db_data);$a++) {
			if ($other_db_data[$a]["other_db_disabled"]) {
				continue;
			}
			
			$db_id=& $other_db_data[$a]["other_db_id"];
			// skip if not in dbs
			if (is_array($dbs) && count($dbs) && !in_array($db_id,$dbs)) {
				continue;
			}
			
			$extDb=getForeignDbObj($a); // connection will be kept open
			if (!$extDb) {
				continue;
			}
			
			$sql=$fields.
				$paramHash["selects"]
				." FROM ".getTableFrom($table,$db_id).$paramHash["remote_joins"]
				.getDbFilterStr($paramHash["filter"],$db_id,$pk,$db_filter,$commonFilterText)
				.($distinct==GROUP_BY?getGroupBy($table):"")
				.ifnotempty(" ORDER BY ",$order_by)
				.ifnotempty(" LIMIT ",$limit);
			
			$retval2=mysql_select_array_from_dbObj($sql,$extDb,array(
				"distinct" => $distinct, 
				"noErrors" => $paramHash["noErrors"]
			));
			
			// procFilter
			if ($quick && function_exists($query[$table]["procFilter"])) {
				$query[$table]["procFilter"]($retval2); // call by ref
			}
		
			// handle subqueries, only for the results displayed
			if (!$quick) { // saves a lot of time
				handle_subqueries_for_dbObj($extDb,$db_id,$other_db_data[$a]["db_beauty_name"],$retval2,$table,$flags);
			}
			
			if (!$quick && function_exists($query[$table]["procFunction"])) {
				$query[$table]["procFunction"]($retval2); // call by ref
			}
			
			if ($paramHash["hierarchicalResults"]==RESULTS_FLAT) {
				// set db_beauty_name
				if (arrCount($retval2)>0) {
					setDbBeautyName($retval2,$db_id,$other_db_data[$a]["db_beauty_name"],$other_db_data[$a]["capabilities"]);
					$retval=array_merge($retval,$retval2);
				}
			}
			elseif (is_array($retval2)) {
				if ($paramHash["sortHints"]) for ($b=0;$b<count($retval2);$b++) {
					$retval["sort_hints"][]=$retval2[$b]["sort_hint"];
				}
				if ($paramHash["hierarchicalResults"]==RESULTS_PK_ONLY) for ($b=0;$b<count($retval2);$b++) {
					$retval2[$b]=$retval2[$b]["pk"];
				}
				$retval["db"][$db_id]=$retval2;
				$retval["count"]+=count($retval2);
			}
		}
	}
	return $retval;
}

function getRefReaction($prefix="") {
	if (!empty($_REQUEST["ref_reaction_db_id"]) && !empty($_REQUEST["ref_reaction_id"])) {
		list($res)=mysql_select_array(array(
			"table" => "reaction", 
			"dbs" => $_REQUEST["ref_reaction_db_id"], 
			"filter" => "reaction.reaction_id=".fixNull($_REQUEST["ref_reaction_id"]), 
			"flags" => QUERY_EDIT, 
			"limit" => 1, 
		));
	}
	return $prefix."ref_reaction=".json_encode($res).";
".$prefix."ref_reaction_diagram=".fixStr(getGraphicalYield($res["products"])).";\n";
}

function get_username_from_person_id($person_id) {
	/*
	nimmt person_id, gibt Benutzername zurück
	*/
	if (empty($person_id)) {
		return false;
	}
	list($result)=mysql_select_array(array(
		"table" => "person_quick", 
		"filter" => "person.person_id=".fixNull($person_id), 
		"dbs" => "-1", 
		"limit" => 1, 
	));
	return array($result["username"],$result["remote_host"]);
}

function getPersonString($person_id,$natural=false) {
	list($result)=mysql_select_array(array(
		"table" => "person_quick", 
		"filter" => "person.person_id=".fixNull($person_id), 
		"dbs" => "-1", 
		"limit" => 1, 
		"noErrors" => true, 
	));
	if ($natural) {
		return formatPersonNameNatural($result);
	}
	return formatPersonNameCommas($result);
}

function getUserForUsername($username,$readSettings=false) {
	$retval=mysql_select_array(array(
		"table" => "person".($readSettings?"":"_quick"), 
		"filterDisabled" => true, 
		"filter" => "person.username=".fixStrSQL($username), 
		"dbs" => "-1", 
		"limit" => 1, 
		"noErrors" => true, 
	));
	return $retval[0];
}

function setUserInformation($readSettings=true) {
	global $db,$db_user,$permissions,$person_id,$lang,$settings,$g_settings,$own_data,$globalString,$columns,$order_by_keys;
	/*
	nimmt Benutzernamen, gibt Berechtigungen zurück
	*/
	if (empty($db_user)) {
		return false;
	}
	if ($db_user==ROOT) {
		$permissions=(-1 & ~(_remote_read+_remote_read_all+_remote_write+_barcode_user) ); // everything
		$own_data=array("username" => $db_user, );
		$person_id=0; // was null
		$preferred_lang=default_language;
	}
	else {
		$own_data=getUserForUsername($db_user,$readSettings);
		
		if (!is_array($own_data)) {
			return false;
		}
		$permissions=$own_data["permissions"];
		$person_id=$own_data["person_id"];
		$preferred_lang=$own_data["preferred_language"];
		if ($readSettings) {
			$settings=unserialize($own_data["preferences"]);
			$order_by_keys["analytics_type_order"]=array(
				"columns" => getUserDefOrderObj("analytics_type"),
				"for_table" => array("analytics_type"), 
			);
			$order_by_keys["analytics_device_order"]=array(
				"columns" => getUserDefOrderObj("analytics_device"),
				"for_table" => array("analytics_device"), 
			);
		}
	}
	if ($readSettings) {
		$g_settings=arr_merge($g_settings,getGVar("settings")); // overwrite presets
		if ($db_user==ROOT && isset($g_settings["default_language"])) {
			$preferred_lang=$g_settings["default_language"];
		}
		if (!empty($g_settings["name_migrate_id_mol"])) {
			$globalString["migrate_id_mol"]=$g_settings["name_migrate_id_mol"];
		}
		if (!empty($g_settings["name_migrate_id_cheminstor"])) {
			$globalString["migrate_id_cheminstor"]=$g_settings["name_migrate_id_cheminstor"];
		}
		if (!empty($g_settings["name_migrate_id_mol"])) { // auto-show column for BESSI
			$columns["molecule"]["migrate_id_mol"]=0;
			$columns["chemical_storage"]["migrate_id_mol"]=0;
		}
		//~ if (!empty($g_settings["name_migrate_id_cheminstor"])) { // auto-show column for ID-No
			//~ $columns["chemical_storage"]["migrate_id_cheminstor"]=0;
		//~ }
	}
	// load avail other_db
	setOtherDbData();
	if ($lang=="-1") {
		$lang=$preferred_lang;
	}
	
	// make sure settings are valid
	// at least r/s OR ghs must be
	if (!$g_settings["use_rs"] && !$g_settings["use_ghs"]) {
		$g_settings["use_ghs"]=1;
	}
}

// called always
function setOtherDbData() {
	global $other_db_data,$db,$settings;
	// re-order other_db_data
	if (is_array($settings["other_db_order"]) && count($settings["other_db_order"])) {
		$orderSQL=" ORDER BY FIELD(other_db_id,".fixArrayListString(array_values($settings["other_db_order"])).") ASC";
	}
	$other_db_data=mysql_select_array_from_dbObj("* FROM other_db WHERE other_db_disabled IS NULL".$orderSQL,$db);
}

function getSimpleQuery($query_pattern) {
	$query_pattern=preg_replace("/[^\d<>]/","",$query_pattern);
	preg_match("/<(\d+)>/",$query_pattern,$num);
	if (isEmptyStr($num[1]) || substr_count($query_pattern,"<")!=1 || substr_count($query_pattern,">")!=1) {
		return false;
	}
	return $num[1];
}

function getFulldataFromPrimaryKeys($paramHash,$resultset_array) {// ,$table,$flags,$order_by="",$distinct=false
	global $settings; //$query,
	$table=& $paramHash["table"];
	/*
	Aufbau $resultset_array: array(db_id => array(primärschlüssel1,primärschlüssel2,..),..)
	*/
	$results=array();
	if (!count($resultset_array)) {
		return array();
	}
	$pk_name=getShortPrimary($table);
	foreach($resultset_array as $db_id => $primary_ids) {
		if (is_array($primary_ids) && count($primary_ids)) {
			//~ $order_obj=array(
				//~ array("field" => "FIELD(".$query[$table]["primary"].",".join(",",$primary_ids).")"),
			//~ );
			$db_results=mysql_select_array(array(
				"dbs" => $db_id, 
				"table" => $table, 
				"order_obj" => $paramHash["order_obj"], 
				"filter" => getLongPrimary($table)." IN(".secSQL(join(",",$primary_ids)).")", 
				"distinct" => $paramHash["distinct"], 
				"flags" => $paramHash["flags"], 
				"export" => $paramHash["export"], 
			));
			
			for($b=0;$b<count($db_results);$b++) {
				$pk=$db_results[$b][$pk_name]; // to avoid nonexisting entry problems
				$db_results[$b]["sel"]=$settings["selection"][$table][$db_id][$pk];
			}
			$results=array_merge($results,$db_results);
			//~ $db_results
			//~ if ($paramHash["cacheMode"]) {
				//~ for ($b=0;$b<count($db_results);$b++) {
					//~ $db_id=$db_results[$b]["db_id"];
					//~ $pk=$db_results[$b]["pk"];
					//~ $db_results[$b]["sel"]=$settings["selection"][$table][$db_id][$pk];
					//~ $results[$db_id][$pk]=$db_results[$b];
				//~ }
			//~ }
			//~ else {
				// add in the order according to $primary_ids
				
				//~ for ($a=0;$a<count($primary_ids);$a++) {
					//~ for($b=0;$b<count($db_results);$b++) {
						//~ $pk=$db_results[$b][$pk_name];
						//~ if ($pk==$primary_ids[$a]) {
							//~ $db_results[$b]["sel"]=$settings["selection"][$table][$db_id][$pk];
							//~ array_push($results,$db_results[$b]);
							//~ continue 2;
						//~ }
					//~ }
				//~ }
				
			//~ }
		}
	}
	return $results;
}

function createCustomView($table) {
	global $settings,$view_controls,$view_ids;
	$settings["custom_view"][$table]=array(
		"visibleControls" => range(0,count($view_controls[$table]) ),
		"visibleIds" => range(0,count($view_ids[$table]) ),
		"hiddenControls" => array(),
		"hiddenIds" => array(),
	);
	saveUserSettings();
}

function handleQueryRequest($flags=0,$paramHash=array()) { // 0: quick 1: alle unterabfragen 2: list
	global $db,$filterOff,$query,$tables,$cache,$permissions,$searchModes,$settings,$edit_views;
	// query -> neuer query-cache-Eintrag, Abfrage (ohne Limit), Eintrag aller Ergebnisse in Cache, Ausgabe der gewünschten Ergebnisse
	// db_id & primKey -> immer Abfrage, Ausgabe der gewünschten Ergebnisse (muß aktuell sein)
	// cached_query=xyz, refresh=true oder Alter>5 min -> Abfrage (ohne Limit), Eintrag aller Ergebnisse in Cache, Ausgabe der gewünschten Ergebnisse
	// cached_query=xyz, Ausgabe der gewünschten Ergebnisse
	// selected=true wird auch hier berücksichtigt
	// garbage collection für alte daten (>5 min)
	// $_REQUEST["no_cache"]="true"; // switch off cache for testing
	// session_write_close(); geht nicht mehr
	
	// preparation tasks
	// individual_cache für Tabellen/Abfragen, die nicht für alle Benutzer gleich sind
	if ($_REQUEST["list_op"]==0 
		&& isEmptyStr($_REQUEST["selected_only"]) 
		&& isEmptyStr($_REQUEST["filter_disabled"]) 
		&& isEmptyStr($_REQUEST["query"]) 
		&& isEmptyStr($_REQUEST["cached_query"]) 
		&& $query[ $_REQUEST["table"] ]["cache_mode"]!=CACHE_COMMON
		//~ && !$query[ $_REQUEST["table"] ]["individual_cache"]
	) { // save much time on getAll
		$_REQUEST["cached_query"]="all_".bin2hex(getAllQueryMD5($_REQUEST));
		//~ echo $_REQUEST["cached_query"];
	}
	$cache_id=& $_REQUEST["cached_query"];
	$cache=readCache($cache_id);
	//~ print_r($cache);
	if (count($cache)==0) {
		$cache_id="";
	}
	$dbs=& $cache["dbs"];
	$table=& $cache["table"]; // molecule,..
	$ref_cache_id=& $cache["ref_cache_id"];
	if (!empty($table)) { // table an cached_query anpassen
		$_REQUEST["table"]=$table;
		setGlobalVars();
	}

	$cache_disabled=($_REQUEST["no_cache"]=="true" || $query[$table]["cache_mode"]==CACHE_OFF);
	$refresh_data=array();

	// Limits verarbeiten
	list($page,$skip,$per_page)=getLimits();
	
	if (empty($table)) {
		$dbs=$_REQUEST["dbs"];
		$table=$_REQUEST["table"]; // molecule,..
		$ref_cache_id=$_REQUEST["ref_cache_id"];
	}
	elseif (count($cache["results"]) && $_REQUEST["refresh"]!="all" && !$cache_disabled) { // Ergebnisse aus dem Cache, gilt auch für refresh=1234
		cleanupChangeNotify("-1",$db); // kill all old stuff
		$baseTable=getBaseTable($table);
		$refresh_data=mysql_select_array(array(
			"table" => "change_notify", 
			"filter" => "for_table=".fixStrSQL($baseTable)." AND made_when>=FROM_UNIXTIME(".$cache["last_update"]."-5)", 
			"quick" => true, //2, 
			"dbs" => $dbs, 
			"hierarchicalResults" => RESULTS_PK_ONLY, 
		)); // get only datasets changed since last cache fix
		
		$cache_active=true; // 
		// print_r($refresh_data);
	}
	
	// check if dbs is reasonable
	$baseTable=getBaseTable($table);
	if (!mayReadRemote($baseTable)) {
		$dbs=-1;
	}

	if (!empty($_REQUEST["order_by"]) && $cache["order_by"]!=$_REQUEST["order_by"]) { // cached query mit neuer Ordnung: gibt neues Cached-Query
		$cache["order_by"]=$_REQUEST["order_by"];
		$cache_id=""; // generate new cache_id at the end
		$cache["results"]=array();
	}
	
	$cache["order_obj"]=getOrderObjFromKey($cache["order_by"],$baseTable);
	$filter_obj=$cache["filter_obj"];
	$order_obj=& $cache["order_obj"]; // xyz DESC
	
	$distinct=$query[$table]["distinct"];
	
	// include custom view
	if (isset($edit_views[$table])) {
		if (!isset($settings["custom_view"][$table])) {
			createCustomView($table);
		}
		$edit_views[$table]["custom_view"]=$settings["custom_view"][$table];
	}

	// Abfrage
	// Filter verarbeiten
	$filter_obj=getFilterObject(array(
		"dbs" => $dbs, 
		"filter_obj" => $filter_obj, 
		"db_filter" => $refresh_data["db"], 
	)); // hier drin wird die Substruktursuche usw. abgehandelt und in einer IN(...) Bedingung verwandelt
	
	$filterOff=isEmptyStr($filter_obj["query_string"]); // globale Variable setzen
	//~ $_REQUEST["order_by"]=$order_by;
	
	// bei Textsuchen Elemente, die mit dem Sucbegriff beginnen, an den Anfang setzen
	if (empty($order_obj) && arrCount($filter_obj["optimised_order"])) {
		$order_obj=$filter_obj["optimised_order"];
	}
	
	//~ print_r($filter_obj);die("X");
	
	
	// die ABFRAGE-----------------------------------------------------------------------------------------
	//~ echo $dbs."X".$table;
	//~ print_r($filter_obj["query_string"]);
	//~ print_r($cache);
	//~ var_dump($refresh_data);
	//~ print_r($order_obj);
	$results=mysql_select_array(array(
		"dbs" => $dbs, 
		"table" => $table, 
		"quick" => true, //2, 
		"order_obj" => $order_obj, 
		"filter" => $filter_obj["query_string"], 
		//~ "filterDisabled" => $filter_obj["filter_disabled"], 
		"distinct" => $distinct, 
		"db_filter" => $refresh_data["db"], 
		"sortHints" => true, 
		"hierarchicalResults" => RESULTS_PK_ONLY, 
	)); // order by wird wohl zweimal benötigt, hier und später wieder
	
	// Ergebnisse cachen
	if (!$cache_disabled) { // Ergebniscache updaten
		// Molfiles und summenformel nicht cachen, werden nur für substruktursuche etc benötigt, gecached wird: db_id und primary key
		if ($refresh_data["count"]>0) { // cache updaten
			foreach ($refresh_data["db"] as $db_id => $pks) {
				if (count($pks) && count($cache["results"]["db"][$db_id])) {
					// 1. alle PKs aus cached result entfernen, die früher drin waren und jetzt nicht mehr (aus welchen Gründen auch immer)
					if (!count($results["db"][$db_id])) {
						$results["db"][$db_id]=array();
					}
					$remove_results=array_diff($pks,$results["db"][$db_id]);
					$cache["results"]["db"][$db_id]=array_values(array_diff($cache["results"]["db"][$db_id],$remove_results));
				}
			}
			foreach ($results["db"] as $db_id => $pks) {
				if (count($pks)) {
					// 2. alle PKs zu cached result hinzufügen, die früher nicht drin waren, aber jetzt
					if (!count($cache["results"]["db"][$db_id])) { 
						$cache["results"]["db"][$db_id]=array();
					}
					$add_results=array_diff($pks,$cache["results"]["db"][$db_id]);
					$cache["results"]["db"][$db_id]=array_values(array_merge($cache["results"]["db"][$db_id],$add_results));
				}
			}
			$cache["results"]["count"]=count($cache["results"]["db"],1)-count($cache["results"]["db"]); // substract 1st level
		}
		if ($cache_active) { // Ergebnisse vom Cache lesen
			$results=$cache["results"];
		}
		else {
			// komplette Ergebnisse cachen
			$cache["results"]=$results;
			$cache["filter_obj"]=$filter_obj;
		}
	}
	$totalCount=$results["count"];
	
	//~ print_r($results); // die();
	//~ print_r($cache); // die();
	
	// Garbage-collection
	gcCache();
	$cache_id=writeCache($cache,$cache_id);
	// Ergebnisse zurückgeben
	
	if (($skip<0 || $skip>=$totalCount) && $per_page>0) { // zB nach dem Löschen des letzten Datensatzes auf einer Seite
		$page=floor(($totalCount-1)/$per_page);
		$skip=$page*$per_page;
		$_REQUEST["page"]=$page;
	}
	$_REQUEST["ref_cache_id"]=$ref_cache_id;
	
	if ($totalCount==0) {
		$results=array();
	}
	elseif ($flags==0) { // edit.php, hierarchisch, wird später "flach" gemacht
		// nichts tun
	}
	elseif ($totalCount>0) { // für selected haben wir schon alles
		//~ print_r($results);die();
		$sort_hints=$results["sort_hints"];
		
		// pick dataset according to db_id/pk
		if (count($results["db"]) && !empty($_REQUEST["db_id"]) && !empty($_REQUEST["pk"])) {
			$skip=0;
			foreach ($results["db"] as $db_id => $pks) {
				if ($db_id==$_REQUEST["db_id"]) {
					$pos=array_search($_REQUEST["pk"],$pks);
					if ($pos===FALSE) {
						$skip=0;
					}
					else {
						$skip+=$pos;
					}
					break;
				}
				else {
					$skip+=count($pks);
				}
			}
			if ($per_page>0) {
				$page=floor($skip/$per_page);
			}
		}
		
		// Abfrage der tatsächlich benötigten Datensätze aufgrund von db_id und Primärschlüssel
		if ($per_page>0) {
			$results=array_slice_r($results["db"],$page*$per_page,$per_page);
		}
		else { // all
			$results=$results["db"];
		}
		$results=getFulldataFromPrimaryKeys(array(
			"table" => $table, 
			"flags" => $flags, 
			"order_obj" => $order_obj, 
			"distinct" => $distinct, 
			"export" => $paramHash["export"], 
			), $results);
	}
	// totalCount ersetzt durch quick_res
	//~ return array($results,$totalCount,$page,$skip,$per_page,$cache_active,$sort_hints);
	return array(
		$results, 
		array("totalCount" => $totalCount, "page" => $page, "skip" => $skip, "per_page" => $per_page, "cache_active" => $cache_active, "goto" => $filter_obj["goto"], ), 
	$sort_hints
	);
}

function makeResultsFlat($results,$table="") {
	$flat_results=array();
	if (is_array($results["db"])) foreach ($results["db"] as $db_id => $pks) {
		for ($a=0;$a<count($pks);$a++) {
			$flat_results[]=array(
				"db_id" => $db_id, 
				"pk" => $pks[$a], 
				//~ "sel" => $settings["selection"][$table][$db_id][ $pks[$a] ]
			);
		}
	}
	return $flat_results;
}

function checkDuplicateCAS($cas_nr,$pkExclude="") {
	if (!empty($cas_nr)) {
		list($cas_count)=mysql_select_array(array(
			"table" => "molecule_count", 
			"dbs" => -1, 
			"filter" => "cas_nr LIKE ".fixStrSQL($cas_nr).ifNotEmpty(" AND molecule.molecule_id!=",$pkExclude), 
		));
		$cas_count=$cas_count["count"];
		if ($cas_count>0) {
			$cas_text="<a href=\"list.php?dbs=-1&table=molecule&query=<0>&crit0=molecule.cas_nr&op0=ex&val0=".$cas_nr."\">".$cas_nr."</a>";
			if ($cas_count==1) {
				$retval=s("multi_cas1").$cas_text.s("multi_cas2");
			}
			else {
				$retval=s("multi_cas1pl").$cas_count.s("multi_cas2pl").$cas_text.s("multi_cas3pl");
			}
			return array(FAILURE,$retval);
		}
	}
	return array(SUCCESS,"");
}

function checkDuplicateSMILES($smiles_stereo,$pkExclude="") {
	if (!empty($smiles_stereo)) {
		list($smiles_count)=mysql_select_array(array(
			"table" => "molecule_count", 
			"dbs" => -1, 
			"filter" => "smiles_stereo LIKE BINARY ".fixStrSQL($smiles_stereo).ifNotEmpty(" AND molecule.molecule_id!=",$pkExclude), 
		));
		$smiles_count=$smiles_count["count"];
		if ($smiles_count>0) {
			$smiles_text="<a href=\"list.php?dbs=-1&table=molecule&query=<0>&crit0=molecule.smiles_stereo&op0=bn&val0=".$smiles_stereo."\">".s("this_smiles")."</a>";
			if ($smiles_count==1) {
				$retval=s("multi_smiles1").$smiles_text.s("multi_smiles2");
			}
			else {
				$retval=s("multi_smiles1pl").$smiles_count.s("multi_smiles2pl").$smiles_text.s("multi_smiles3pl");
			}
			return array(FAILURE,$retval);
		}
	}
	return array(SUCCESS,"");
}
?>