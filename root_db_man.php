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

require_once "lib_global_funcs.php";
require_once "lib_root_funcs.php";

function addOneToMany(& $pkLibrary,$queryTable,$queryTableFk,$refTable) {
	global $db;
	if (count($pkLibrary[$refTable])) {
		$shortPk=getShortPrimary($queryTable);
		$temp_arr=mysql_select_array_from_dbObj($shortPk." FROM ".$queryTable." WHERE ".$queryTableFk." IN(".join(",",$pkLibrary[$refTable]).");",$db);
		for ($a=0;$a<count($temp_arr);$a++) {
			$pkLibrary[$queryTable][]=$temp_arr[$a][$shortPk];
		}
	}
}
function addManyToOne(& $pkLibrary,$queryTable,$refTableFk,$refTable) {
	global $db;
	if (count($pkLibrary[$refTable])) {
		$shortPk=getShortPrimary($refTable);
		$temp_arr=mysql_select_array_from_dbObj($refTableFk." FROM ".$refTable." WHERE (NOT ".$refTableFk." IS NULL) AND ".$shortPk." IN(".join(",",$pkLibrary[$refTable]).");",$db);
		for ($a=0;$a<count($temp_arr);$a++) {
			$value=$temp_arr[$a][$refTableFk];
			if (!is_array($pkLibrary[$queryTable]) || !in_array($value,$pkLibrary[$queryTable])) {
				$pkLibrary[$queryTable][]=$value;
			}
		}
	}
}
function rrmdir($base_dir,$dir) {
	if (empty($base_dir) || strlen($base_dir)<6 || !startswith($dir,$base_dir)) {
		// too dangerous
		return;
	}
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") {
					rrmdir($dir."/".$object);
				}
				else {
					unlink($dir."/".$object);
				}
			}
		}
		reset($objects);
		rmdir($dir);
	}
}
function getNullCrit($crit) {
	return $crit." IS NULL OR ".$crit."=\"\"";
}
if ($_REQUEST["desired_action"]=="export_lj_data" && $_REQUEST["save_settings"]=="true") {
	pageHeader(true,false,true,false);
	
	if ($db_user==ROOT) {
		// get the pks of entities to export
		$export_pks=array();
		$export_pks["person"]=array($_REQUEST["person_id"]);
		addOneToMany($export_pks,"lab_journal","person_id","person");
		addOneToMany($export_pks,"reaction","lab_journal_id","lab_journal");
		addOneToMany($export_pks,"reaction_chemical","reaction_id","reaction");
		addOneToMany($export_pks,"reaction_property","reaction_id","reaction");
		addOneToMany($export_pks,"analytical_data","reaction_id","reaction");
		addOneToMany($export_pks,"analytical_data_image","analytical_data_id","analytical_data");
		addOneToMany($export_pks,"gc_peak","analytical_data_id","analytical_data");
		addManyToOne($export_pks,"project","project_id","reaction");
		addManyToOne($export_pks,"reaction_type","reaction_type_id","reaction");
		addOneToMany($export_pks,"reaction_literature","reaction_id","reaction");
		addManyToOne($export_pks,"literature","literature_id","reaction_literature");
		addOneToMany($export_pks,"project_literature","project_id","project");
		addManyToOne($export_pks,"literature","literature_id","project_literature");
		addOneToMany($export_pks,"author","literature_id","literature");
		addManyToOne($export_pks,"sci_journal","sci_journal_id","literature");
		//~ print_r($export_pks);
		
		// export
		$tmpdir=oe_get_temp_dir()."/".$_REQUEST["person_id"]."/";
		@mkdir($tmpdir);
		@chmod($tmpdir,0777);
		foreach ($export_pks as $table => $pks) {
			if (!count($pks)) {
				continue;
			}
			$shortPk=getShortPrimary($table);
			$filename=$tmpdir.$table;
			$sql="SELECT * FROM ".$table." WHERE ".$shortPk." IN(".join(",",$pks).") INTO OUTFILE ".fixStr($filename).";";
			mysqli_query($db,$sql);
		}
		
		// compress folder and send
		require_once "File/Archive.php";
		File_Archive::setOption('tmpDirectory',oe_get_temp_dir());
		File_Archive::extract(
			File_Archive::read($tmpdir),
			File_Archive::toArchive($_REQUEST["person_id"].".zip", File_Archive::toOutput())
		);
		
		// cleanup
		rrmdir($tmpdir,$tmpdir);
	}
	exit();
}
else {
	pageHeader();
}

//~ $fields=getFieldsForTable("chemical_storage");
//~ $fields_join=join(",",$fields);die($fields_join);

echo stylesheet.
style.
getFixedStyleBlock().
_style.
loadJS(array("client_cache.js","controls.js","jsDatePick.min.1.3.js","forms.js","molecule_edit.js","searchpk.js","subitemlist_helper.js","subitemlist_get.js","subitemlist.js","edit.js","settings.js",),"lib/").
script."
readOnly=false;
editMode=false;

function connect_dbs() {
	// go through list and get selected
	var combine=getControlValue(\"db_group\"),list_int_name=\"db_cross\";
	var max=combine.length;
	
	// combine all with all
	for (var b=0;b<max;b++) {
		var UID=SILfindValue(list_int_name,\"name\",undefined,combine[b]);
		for (var c=0;c<max;c++) {
			if (b!=c) {
				// check
				SILsetChecked(true,list_int_name,UID,combine[c],\"link\")
			}
		}
	}
}
";

getViewHelper($table);

echo "
activateSearch(false);
"._script."
</head>
<body>";

showCommFrame();

if (!in_array($_REQUEST["desired_action"],array("recalc_spectra","export_lj_data"))) {
	// get databases
	$db_info=getDatabases($db,db_type);
}

switch ($_REQUEST["desired_action"]) {
	case "export_lj_data":
	if ($db_user==ROOT) {
		// allow to select user who all lab_journals will be backed up
		$field_list=array(
			// one one lab_journal_id
			array(
				"item" => "text", 
				"text" => s("export_lj"), 
			), 
			"br", 
			array(
				"item" => "pk_select", 
				"int_name" => "person_id", 
				"text" => s("person_id"), 
				"table" => "person", 
				"dbs" => -1, 
				"order_by" => "person_name_disabled", 
				"pkName" => "person_id", 
			),
		);
	}
	break;
	case "recalc_spectra":
	if ($db_user==ROOT) {
		// only this db
		$field_list=array(
			// one one lab_journal_id
			array(
				"item" => "text", 
				"text" => s("recalc_spectra"), 
			), 
			"br", 
			array(
				"item" => "pk_select", 
				"int_name" => "lab_journal_id", 
				"text" => s("lab_journal"), 
				"table" => "lab_journal", 
				"dbs" => -1, 
				"order_by" => getOrderObjFromKey("lab_journal_code","lab_journal"), 
				"pkName" => "lab_journal_id", 
				"nameField" => "lab_journal_code", 
			),
			// only one analytics_type_id
			array(
				"item" => "pk_select", 
				"int_name" => "analytics_type_id", 
				"table" => "analytics_type", 
				"dbs" => -1, 
				"pkName" => "analytics_type_id", 
				"nameField" => "analytics_type_name", 
			),
		);
		
		switch ($_REQUEST["save_settings"]) {
		case "true":
			// load spectra one by one, process and replace analytical_data_interpretation, analytical_data_properties_blob and image(s)
			
			require_once "lib_analytics.php";
			$now=time();
			$table="analytical_data";
			
			$idx=0;
			do {
				set_time_limit(40);
				// read
				list($result)=mysql_select_array(array(
					"table" => "analytical_data_spz", 
					"filter" => "reaction.lab_journal_id=".fixNull($_REQUEST["lab_journal_id"])." AND analytical_data.analytics_type_id=".fixNull($_REQUEST["analytics_type_id"]), 
					"dbs" => -1, 
					"limit" => $idx.",1",  
				));
				$idx++;
				
				if (empty($result["analytical_data_blob"])) {
					continue;
				}
				
				// proc
				$spectrum_data=getProcData($result["analytical_data_blob"],$analytics_img_params,$result["analytics_type_code"],$result["analytics_device_driver"]);
				
				// write, ignore limitations
				if (count($spectrum_data)) {
					$sql_query=array();
					
					// insert additional images (if any)
					$sql_query[]="DELETE FROM analytical_data_image WHERE analytical_data_image.analytical_data_id=".fixNull($result["analytical_data_id"]).";";
					for ($a=1;$a<count($spectrum_data["img"]);$a++) {
						$sql_query[]="INSERT INTO analytical_data_image (analytical_data_id,reaction_id,project_id,image_no,analytical_data_graphics_blob,analytical_data_graphics_type) 
							VALUES (".fixNull($result["analytical_data_id"]).",".fixNull($result["reaction_id"]).",".fixNull($result["project_id"]).",".$a.",".fixBlob($spectrum_data["img"][$a]).",".fixStrSQL($spectrum_data["img_mime"][$a]).");";
					}
					
					$sql_query[]="UPDATE analytical_data SET ".
						"analytical_data_interpretation=".fixBlob($spectrum_data["interpretation"]). // overwrite
						",analytical_data_graphics_blob=".fixBlob($spectrum_data["img"][0]).
						",analytical_data_properties_blob=".fixBlob(json_encode($spectrum_data["analytical_data_properties"])). // GC-Peaks,NMR-Peaks, usw.
						",analytical_data_graphics_type=".fixStrSQL($spectrum_data["img_mime"][0]).",".
						SQLgetChangeRecord($table,$now).
						" WHERE analytical_data.analytical_data_id=".fixNull($result["analytical_data_id"]).";";
					
					performQueries($sql_query,$db); // singleUpdate
				}				
				
				
			} while (!empty($result["analytical_data_id"]));
			
		break;
		}
	}
	break;
	case "merge":
	if ($db_user==ROOT) {
		$db_names=array();
		for ($a=0;$a<count($db_info);$a++) {
			$db_names[$a]=$db_info[$a]["name"];
		}
		$table_names=array_keys($tables);
		sort($table_names);
		
		// warn strongly about backup necessity for source and target db
		$field_list=array(
			array(
				"item" => "text", 
				"text" => s("warning_transfer"), 
			), 
			"br", 
			array(
				"item" => "select", 
				"int_name" => "source_db", 
				"int_names" => $db_names, 
				"texts" => $db_names, 
			), 
			"br", 
			array(
				"item" => "select", 
				"int_name" => "target_db", 
				"int_names" => $db_names, 
				"texts" => $db_names, 
			), 
			"br", 
			array(
				"item" => "select", 
				"int_name" => "selected_tables", 
				"int_names" => $table_names, 
				"texts" => $table_names, 
				"size" => 40, 
				"multiMode" => true, 
			), 
		);
		
		switch ($_REQUEST["save_settings"]) {
			case "true":
			// security check
			$non_foreign_keys=array(
				"analytical_data_ext_archive_id",
				"archive_entity_id",
				"cache_sess_id","locked_sess_id",
				"language_id",
				"customer_id",
				"orig_chemical_order_id", // aus anderer DB
				"reaction_ext_archive_id", 
			);
			$errors=false;
			foreach ($tables as $table => $data) {
				if (!@in_array($table,$_REQUEST["selected_tables"])) {
					continue;
				}
				foreach ($data["fields"] as $field_name => $field_data) {
					if (!$field_data["pk"] && !$field_data["fk"] && !in_array($field_name,$non_foreign_keys) && endswith($field_name,"_id")) {
						echo "In table ".$table.", ".$field_name." is not marked as foreign key field, although it should be.<br>";
						$errors=true;
					}
				}
			}
			if ($_REQUEST["source_db"]==$_REQUEST["target_db"]) {
				echo "The database names must not be identical.<br>";
				$errors=true;
			}
			if (empty($_REQUEST["source_db"]) || empty($_REQUEST["target_db"])) {
				echo "The database names must not be empty.<br>";
				$errors=true;
			}
			if ($errors) {
				die();
			}
			
			set_time_limit(0);
			
			// go through tables of target db and get max for pk
			switchDB($_REQUEST["target_db"],$db);
			
			$max_pk=array();
			foreach ($tables as $table => $data) {
				if (!@in_array($table,$_REQUEST["selected_tables"])) {
					continue;
				}
				$sql="MAX(".getShortPrimary($table).") AS max FROM ".$table.";";
				list($result)=mysql_select_array_from_dbObj($sql,$db);
				$max_pk[$table]=$result["max"];
			}
			
			switchDB($_REQUEST["source_db"],$db);
			
			// get lower bounds in source
			foreach ($tables as $table => $data) {
				if (!@in_array($table,$_REQUEST["selected_tables"])) {
					continue;
				}
				$sql="MIN(".getShortPrimary($table).") AS min FROM ".$table.";";
				list($result)=mysql_select_array_from_dbObj($sql,$db);
				$max_pk[$table]+=1-$result["min"];
				if ($max_pk[$table]<0) {
					$max_pk[$table]=0;
				}
			}
			
			// go through tables of source db and increase pks and fks
			foreach ($tables as $table => $data) {
				// pk
				if ($max_pk[$table]) { // not set for skip_tables
					$pkName=getShortPrimary($table);
					$sql="UPDATE ".$table." SET ".$pkName."=".$pkName."+".$max_pk[$table]." ORDER BY ".$pkName." DESC;";
					mysqli_query($db,$sql) or die($sql.mysqli_error($db));
				}
				
				// fks
				foreach ($data["fields"] as $field_name => $field_data) {
					if ($field_data["fk"] && $max_pk[ $field_data["fk"] ]) { // not set for skip_tables
						$sql="UPDATE ".$table." SET ".$field_name."=".$field_name."+".$max_pk[ $field_data["fk"] ]." ORDER BY ".$field_name." DESC;";
						mysqli_query($db,$sql) or die($sql.mysqli_error($db));
					}
				}
			}
			
			// transfer: select insert?
			foreach ($tables as $table => $data) {
				if (!@in_array($table,$_REQUEST["selected_tables"])) {
					continue;
				}
				$fields=getFieldsForTable($table);
				$fields_join=join(",",$fields);
				$sql="INSERT INTO ".$_REQUEST["target_db"].".".$table." (".$fields_join.") SELECT ".$fields_join." FROM ".$_REQUEST["source_db"].".".$table.";";
				mysqli_query($db,$sql) or die($sql.mysqli_error($db));
			}
			break;
		}
	}
	break;
	
	case "fix_structures":
	if ($db_user==ROOT) {
		
		$db_names=array();
		for ($a=0;$a<count($db_info);$a++) {
			$db_names[$a]=$db_info[$a]["name"];
		}
		
		$list_int_names=array("reactants","reagents","products");
		for ($a=count($list_int_names)-1;$a>=0;$a--) {
			if (!$_REQUEST[ $list_int_names[$a] ]) {
				array_splice($list_int_names,$a,1);
			}
		}
		
		$languages=array_keys($localizedString);
		$read_db=$db_info[$a]["name"];
		switch ($_REQUEST["save_settings"]) {
			case "true":
			// Update-Routine
			
			// preparative tasks
			if ($_REQUEST["read_ext"]) {
				require_once "lib_db_manip.php";
				require_once "lib_supplier_scraping.php";
			}
			
			$safety_fields=array(
				"safety_r","safety_h","safety_s","safety_p","safety_text","safety_sym","safety_sym_ghs",
				"safety_cancer","safety_mutagen","safety_reprod","safety_wgk","safety_danger",
				"default_safety_sheet_url","default_safety_sheet_mime","default_safety_sheet_by",
			);
			
			// Datenbanken durchgehen
			if (is_array($_REQUEST["db_names"])) foreach ($_REQUEST["db_names"] as $this_db_name) {
				// switch db
				switchDB($this_db_name,$db);
				
				// molecule
				if ($_REQUEST["molecule"]) {
					set_time_limit(0);

					$block_length=1000;
					
					// Zählen
					$res=mysqli_query($db,"SELECT COUNT(*) FROM molecule;") or die(mysqli_error($db));
					list($row_count)=mysqli_fetch_array($res,MYSQLI_NUM);
					
					// anything to do for working instructions?
					$doWorkingInstr=false;
					foreach ($languages as $language) {
						switch ($_REQUEST["betr_anw_".$language]) {
						case "create_missing":
						case "create_or_replace":
						case "append":
							require_once "lib_instructions_pdf.php";
							$doWorkingInstr=true;
							
							break 2;
						}
					}
					
					$query_filter=array();
					if ($_REQUEST["read_ext"] || $doWorkingInstr) {
						$query_table="molecule";
						if ($doWorkingInstr) {
							$fieldsWithDefaults=array("betr_anw_gefahren","betr_anw_schutzmass","betr_anw_verhalten","betr_anw_erste_h","betr_anw_entsorgung");
						}
						else {
							// save time as it will not work without CAS
							$query_filter[]="NOT (".getNullCrit("cas_nr").")";
						}
					}
					else {
						$query_table="molecule_fix_smiles";
					}
					
					if ($_REQUEST["before_date"]) {
						$crit="DATE(molecule.molecule_changed_when)";
						$query_filter[]="(".$crit." <=> NULL OR ".$crit."<".getSQLdate($_REQUEST["before_date"]).")";
					}
					if ($_REQUEST["missing_msds_only"]) {
						$crit=getNullCrit("default_safety_sheet_by");
						if ($g_settings["scrape_alt_safety_sheet"] ) {
							// also force alternative language
							$crit.=" OR ".getNullCrit("alt_default_safety_sheet_by");
						}
						$query_filter[]="(".$crit.")";
					}
					
					for ($c=0;$c<$row_count;$c+=$block_length) { // count back
						$results=mysql_select_array(array(
							"table" => $query_table, 
							"dbs" => "-1", 
							"flags" => QUERY_EDIT, 
							"filter" => join(" AND ",$query_filter), 
							"limit" => $c.",".$block_length, 
						));
						
						if (is_array($results)) foreach ($results as $result) {
							set_time_limit(180);
							if ($_REQUEST["read_ext"]) {
								if (!empty($result["cas_nr"])) {
									$molecule=array_clean($result);
									if ($_REQUEST["overwrite_msds"]) {
										// copy object with old values
										$old_molecule=$molecule;
										
										// clean safety data
										foreach ($safety_fields as $safety_field) {
											unset($molecule[$safety_field]);
										}
									}
									getAddInfo($molecule,true); // Daten von suppliern holen, kann dauern
									extendMoleculeNames($molecule);
									
									if ($_REQUEST["overwrite_msds"]) {
										// put back safety data if still empty
										foreach ($safety_fields as $safety_field) {
											if (isEmptyStr($molecule[$safety_field])) {
												$molecule[$safety_field]=$old_molecule[$safety_field];
											}
										}
									}
									
									$oldReq=$_REQUEST;
									$_REQUEST=array_merge($_REQUEST,$molecule);
									$list_int_name="molecule_property";
									$_REQUEST[$list_int_name]=array();
									if (is_array($molecule[$list_int_name])) foreach ($molecule[$list_int_name] as $UID => $property) {
										$_REQUEST[$list_int_name][]=$UID;
										$_REQUEST["desired_action_".$list_int_name."_".$UID]="add";
										$_REQUEST[$list_int_name."_".$UID."_class"]=$property["class"];
										$_REQUEST[$list_int_name."_".$UID."_source"]=$property["source"];
										$_REQUEST[$list_int_name."_".$UID."_conditions"]=$property["conditions"];
										$_REQUEST[$list_int_name."_".$UID."_value_low"]=$property["value_low"];
										$_REQUEST[$list_int_name."_".$UID."_value_high"]=$property["value_high"];
										$_REQUEST[$list_int_name."_".$UID."_unit"]=$property["unit"];
									}
									
									// using the regular update procedure
									prepareWorkingInstructions($result,
										$languages,$fieldsWithDefaults);
									/*$list_int_name="molecule_instructions";
									//~ var_dump($result[$list_int_name]);die();
									//~ var_dump($_REQUEST);die();
									$_REQUEST[$list_int_name]=array();
									foreach ($languages as $language) {
										switch ($_REQUEST["betr_anw_".$language]) {
										case "create_missing":
											// check if there is one
											foreach ($result[$list_int_name] as $entry) {
												if ($entry["lang"]==$language) {
													// something is there
													break 2;
												}
											}
										case "create_or_replace":
											// DELETE anything present for this $language
											mysqli_query($db,"DELETE FROM molecule_instructions WHERE molecule_id=".$result["molecule_id"]." AND lang LIKE ".fixStrSQL($language).";");
										case "append":
											// auto-generate array of symbols for protective equipment from substance data, like regularly done in Javascript
											$protEquip=getProtEquip($result["safety_s"],$result["safety_p"],$result["safety_h"]);
											
											// get any texts from previous entries, append default unless already present
											$defaults=array();
											foreach ($fieldsWithDefaults as $fieldWithDefaults) {
												$defaults[$fieldWithDefaults]=$g_settings["instr_defaults"][$fieldWithDefaults][$language];
											}
											foreach ($result[$list_int_name] as $entry) {
												if ($entry["lang"]==$language) {
													foreach ($fieldsWithDefaults as $fieldWithDefaults) {
														$oldValue=$entry[$fieldWithDefaults];
														if (!endswith($oldValue,$defaults[$fieldWithDefaults])) {
															// append default text if not yet present
															$oldValue.=" ".$defaults[$fieldWithDefaults];
														}
														// set new value
														$defaults[$fieldWithDefaults]=$oldValue;
													}
													break; // use only the newest entry
												}
											}
											
											// create new betr_anw
											$UID=uniqid();
											$_REQUEST[$list_int_name][]=$UID;
											$_REQUEST["desired_action_".$list_int_name."_".$UID]="add";
											$_REQUEST[$list_int_name."_".$UID."_lang"]=$language;
											$_REQUEST[$list_int_name."_".$UID."_betr_anw_schutzmass_sym"]=$protEquip;
											$_REQUEST[$list_int_name."_".$UID."_betr_anw_verhalten_sym"]=$protEquip;
											$_REQUEST[$list_int_name."_".$UID."_betr_anw_erste_h_sym"]=array("E003");
											foreach ($fieldsWithDefaults as $fieldWithDefaults) {
												$_REQUEST[$list_int_name."_".$UID."_".$fieldWithDefaults]=$defaults[$fieldWithDefaults];
											}
											//~ var_dump($_REQUEST);die();
										break;
										}
									}*/
									
									$_REQUEST["desired_action"]="edit";
									//~ print_r($molecule);print_r($_REQUEST);die();
								
									performEdit("molecule",-1,$db,array("ignoreLock" => true, ));
									$_REQUEST=$oldReq;
								}
								
								// skip the rest
								continue;
							}
							
							if (!empty($result["molfile_blob"])) {
								$molecule_search=readMolfile($result["molfile_blob"],array() ); // for  fingerprinting and serialisation
							}
							elseif (!empty($result["emp_formula"])) {
								$molecule_search=readSumFormula($result["emp_formula"],array() );
							}
							elseif (!$doWorkingInstr) {
								continue;
							}
							// updaten: smiles, summenformel, mw, fingerprints, gif, AND molfile (MAKE BACKUP!!!)
							
							$sql_parts=array();
							
							if ($_REQUEST["molfile_blob"] && !empty($result["molfile_blob"])) {
								list($gif,$svg)=getMoleculeGif($molecule_search,gif_x,gif_y,0,1,true,array("png","svg"));
								$sql_parts[]="gif_file=".fixBlob($gif);
								$sql_parts[]="svg_file=".fixBlob($svg);
							}
							
							if ($_REQUEST["emp_formula"]) {
								$sql_parts[]="emp_formula=".fixStr($molecule_search["emp_formula_string"]);
								$sql_parts[]="emp_formula_sort=".fixStr($molecule_search["emp_formula_string_sort"]);
							}
							
							if ($_REQUEST["mw"]) {
								$sql_parts[]="mw=".fixNull($molecule_search["mw"]);
							}
							
							if ($_REQUEST["rdb"]) {
								$sql_parts[]="rdb=".fixStr($molecule_search["rdb"]);
							}
							
							if ($_REQUEST["smiles"] && !empty($result["molfile_blob"])) {
								$sql_parts[]="smiles_stereo=".fixStrSQL($molecule_search["smiles_stereo"]);
								$sql_parts[]="smiles=".fixStrSQL($molecule_search["smiles"]);
							}
							
							if ($_REQUEST["molfile"] && !empty($result["molfile_blob"])) {
								$sql_parts[]="molfile_blob=".fixBlob(writeMolfile($molecule));
							}
							
							if ($_REQUEST["fingerprint"]) {
								$sql_parts[]="molecule_serialized=".fixBlob(serializeMolecule($molecule_search));
								$sql_parts[]=getFingerprintSQL($molecule_search,true);
							}
							
							if ($doWorkingInstr) {
								$oldReq=$_REQUEST;
								$_REQUEST=array_merge($_REQUEST,$result);
								
								// using a slim update procedure
								prepareWorkingInstructions($result,
									$languages,$fieldsWithDefaults);
								
								// adapted from lib_db_manip_edit.php
								$list_int_name="molecule_instructions";
								if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
									$now=time();
									$createArr=SQLgetCreateRecord($list_int_name,$now,true);
									addNvp($createArr,"molecule_id",SQL_NUM);
									$pk2=getInsertPk($list_int_name,$createArr,$db); // cmdINSERTsub
									
									// create fake data for PDF
									$_REQUEST[$list_int_name."_".$UID."_molecule_instructions_created_when"]=getSQLFormatDate();
									$_REQUEST[$list_int_name."_".$UID."_molecule_instructions_created_by"]=$db_user;
									
									// generate PDF according to lang & data entered
									$pdf=getWorkingInstructionsPDF($_REQUEST,$list_int_name,$UID,explode("\n",fixLineEnd($result["molecule_names_edit"])));
									
									// ignore any errors
									mysqli_query($db,"UPDATE ".$list_int_name." SET ".
										"file_blob=".fixBlob($pdf->Output("test.pdf","S")).",". // no image for now, maybe later
										nvpUID($list_int_name,$UID,"lang",SQL_TEXT).
										nvpUID($list_int_name,$UID,"betr_anw_gefahren",SQL_TEXT).
										nvpUID($list_int_name,$UID,"betr_anw_schutzmass",SQL_TEXT).
										nvpUID($list_int_name,$UID,"betr_anw_schutzmass_sym",SQL_SET). // according to DIN EN ISO 7010
										nvpUID($list_int_name,$UID,"betr_anw_verhalten",SQL_TEXT).
										nvpUID($list_int_name,$UID,"betr_anw_verhalten_sym",SQL_SET). // according to DIN EN ISO 7010
										nvpUID($list_int_name,$UID,"betr_anw_erste_h",SQL_TEXT).
										nvpUID($list_int_name,$UID,"betr_anw_erste_h_sym",SQL_SET). // according to DIN EN ISO 7010
										nvpUID($list_int_name,$UID,"betr_anw_entsorgung",SQL_TEXT).
										nvpUID($list_int_name,$UID,"molecule_instructions_comment",SQL_TEXT).
										SQLgetChangeRecord($list_int_name,$now).
										getPkCondition($list_int_name,$pk2));
								}
								$_REQUEST=$oldReq;
							}
							
							if (count($sql_parts)) {
								$sql="UPDATE molecule SET ".join(",",$sql_parts)." WHERE molecule_id=".fixNull($result["molecule_id"]).";";
								mysqli_query($db,$sql) or die($sql.mysqli_error($db));
							}
						}
					}
				}
				
				// reaction components
				
				if ($_REQUEST["recalcRxnfile"] || count($list_int_names)) {
					$block_length=500;
					
					// Zählen
					$res=mysqli_query($db,"SELECT COUNT(*) FROM reaction;") or die(mysqli_error($db));
					list($row_count)=mysqli_fetch_array($res,MYSQLI_NUM);
					
					for ($c=0;$c<$row_count;$c+=$block_length) {
						$results=mysql_select_array(array(
							"table" => "reaction_fix_smiles", 
							"dbs" => "-1", 
							"flags" => QUERY_EDIT, 
							"limit" => $c.",".$block_length, 
						));
						if (is_array($results)) foreach ($results as $num => $result) {
						
							if (empty($result["rxnfile_blob"])) {
								continue;
							}
							set_time_limit(180);
							
							foreach ($list_int_names as $list_int_name) { // listen durchgehen
								if (is_array($result[ $list_int_name ])) foreach($result[ $list_int_name ] as $num2 => $result2) { // einzelne liste durchgehen
									if (empty($result2["molfile_blob"])) {
										continue;
									}
									
									$molecule_search=readMolfile($result2["molfile_blob"],array() );
									
									$sql_parts=array();
									
									if ($_REQUEST["molfile_blob"]) {
										list($gif,$svg)=getMoleculeGif($molecule_search,gif_x,gif_y,0,1,true,array("png","svg"));
										$sql_parts[]="gif_file=".fixBlob($gif);
										$sql_parts[]="svg_file=".fixBlob($svg);
									}
									
									if ($_REQUEST["emp_formula"]) {
										$sql_parts[]="emp_formula=".fixStr($molecule_search["emp_formula_string"]);
										// hier kein emp_formula_sort
									}
									
									if ($_REQUEST["mw"]) {
										$sql_parts[]="mw=".fixNull($molecule_search["mw"]);
									}
									
									if ($_REQUEST["smiles"]) {
										$sql_parts[]="smiles_stereo=".fixStrSQL($molecule_search["smiles_stereo"]);
										$sql_parts[]="smiles=".fixStrSQL($molecule_search["smiles"]);
									}
									
									if ($_REQUEST["molfile"]) {
										$sql_parts[]="molfile_blob=".fixBlob(writeMolfile($molecule));
									}
									
									if ($_REQUEST["fingerprint"]) {
										$sql_parts[]="molecule_serialized=".fixBlob(serializeMolecule($molecule_search));
										$sql_parts[]=getFingerprintSQL($molecule_search,true);
									}
									
									if (count($sql_parts)) {
										$sql="UPDATE reaction_chemical SET ".join(",",$sql_parts)." WHERE reaction_chemical_id=".fixNull($result2["reaction_chemical_id"]).";";
										mysqli_query($db,$sql) or die($sql.mysqli_error($db));
									}
								}
							}
							
							if ($_REQUEST["recalcRxnfile"]) {
								$reaction=array(
									"reactants" => 0,
									"products" => 0,
									"molecules" => array(),
								);
								$identifier=array();
								$stoch_coefficients=array();
								
								// Buchstaben einfügen
								for ($a=0;$a<2;$a++) {
									$b=1;
									switch($a) {
									case 0:
										// Edukte
										$list_int_name="reactants";
									break;
									case 1:
										// Produkte
										$list_int_name="products";
									break;
									}
									if (is_array($result[$list_int_name])) foreach ($result[$list_int_name] as $num2 => $result2) {
										$text=$b;
										if ($a==0) {
											$text=numToLett($text);
										}
										
										$reaction[$list_int_name]++;
										$reaction["molecules"][]=$result2["molfile_blob"];
										$identifier[]=$text;
										$stoch_coeff=$result2["stoch_coeff"];
										if ($stoch_coeff==1) { // 1 is obsolete
											$stoch_coeff="";
										}
										$stoch_coefficients[]=$stoch_coeff;
										$b++;
									}
								}
								
								$rxndata=writeRxnfile($reaction); // combine molfiles
								$reaction=readRxnfile($rxndata);
								normaliseReaction($reaction); // create data structure
								$reaction["identifier"]=$identifier;
								$reaction["stoch_coeff"]=$stoch_coefficients;
								
								list($gif,$svg)=getReactionGif($reaction,rxn_gif_x,rxn_gif_y,0,1,6,array("png","svg"));
								$sql="UPDATE reaction SET rxn_gif_file=".fixBlob($gif).",rxn_svg_file=".fixBlob($svg)." WHERE reaction_id=".fixNull($result["reaction_id"]).";";
								mysqli_query($db,$sql) or die($sql.mysqli_error($db));
							}
						}
					}
				}
				
			}
			break;
		}
		$field_list=array(
			// select-multi mit Datenbanken
			array(
				"item" => "select", 
				"int_name" => "db_names", 
				"int_names" => $db_names, 
				"texts" => $db_names, 
				"multiMode" => true, 
			), 
			"br", 
			
			// molecule, reactant,...
			array("item" => "check", "int_name" => "molecule", ), 
			array("item" => "check", "int_name" => "recalcRxnfile", ), 
			array("item" => "check", "int_name" => "reactants", ), 
			array("item" => "check", "int_name" => "reagents", ), 
			array("item" => "check", "int_name" => "products", ), 
			"br", 
			
			// which fields
			array("item" => "text", "text" => "<table><tr class=\"block_head\"><td>"), 
			array("item" => "check", "int_name" => "emp_formula", ), 
			array("item" => "check", "int_name" => "mw", ), 
			array("item" => "check", "int_name" => "rdb", ), 
			array("item" => "check", "int_name" => "smiles", ), 
			"br", 
			array("item" => "check", "int_name" => "molfile_blob", ), // image
			array("item" => "check", "int_name" => "molfile", ), 
			array("item" => "check", "int_name" => "fingerprint", ), 
			
			array("item" => "text", "text" => "</td><td>"), 
			
			"tableStart", 
			array("item" => "check", "int_name" => "read_ext", ), 
			array("item" => "input", "int_name" => "before_date", "size" => 10, "type" => "date", "noAutoComp" => true, ), 
			array("item" => "check", "int_name" => "missing_msds_only", ), 
			array("item" => "check", "int_name" => "overwrite_msds", ), 
			"tableEnd", 
			array("item" => "text", "text" => "</td><td>"), 
			"tableStart", 
			array("item" => "text", "int_name" => "betriebsanweisung"), 
		);
		
		// auto-creation of working instructions
		foreach ($languages as $language) {
			$field_list[]=array(
				"item" => "select", 
				"int_name" => "betr_anw_".$language, 
				"int_names" => array("do_nothing","create_missing","create_or_replace","append"), 
				"size" => 1, 
				"text" => $localizedString[$language]["language_name"], 
			);
		}
		$field_list[]="tableEnd";
		$field_list[]=array("item" => "text", "text" => "</td></tr></table>");
	}
	break;
	
	case "db_cross":
	default:
	// get contents of all other_db tables for deep access checking
	$other_db_info=getOtherDBInfo();
	//~ var_dump($other_db_info);die();
	
	if ($db_user==ROOT) {
		switch ($_REQUEST["save_settings"]) {
		case "true":
			// silently remove problematic users
			mysqli_query($db,"GRANT USAGE ON *.* TO ''@'".php_server.";");  # CHKN added back compatibility for MySQL < 5.7 that has no DROP USER IF EXISTS
			mysqli_query($db,"DROP USER ''@'".php_server."';");
			mysqli_query($db,"GRANT USAGE ON *.* TO ''@'%';");  # CHKN added back compatibility for MySQL < 5.7 that has no DROP USER IF EXISTS
			mysqli_query($db,"DROP USER ''@'%';");
			
			// Schreibroutine
			$list_int_name="db_cross";
			
			// get list of dbs
			$dbs=array();
			if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) {
				$dbs[]=getValueUID($list_int_name,$UID,"name");
			}
			
			set_time_limit(0);
			// drop old usernames
			$keep_usernames=array();
			if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) { // gelesene DB
				$read_db=getValueUID($list_int_name,$UID,"name");
				foreach ($dbs as $reading_db) { // lesende DB
					$pw_map=$other_db_info[$reading_db];
					if (getValueUID($list_int_name,$UID,$reading_db."_link")) { // checked
						$this_username=generateLinkUsername($read_db,$reading_db);
						if (usernameExists($this_username) 
							&& usernameAccessExists($reading_db,$this_username) 
							&& checkDBLink($read_db,$this_username,$pw_map[ $read_db."_".$this_username ]) ) {
							// everything fine already
							$keep_usernames[]=$this_username;
						}
					}
				}
			}
			
			//~ print_r($keep_usernames);die();
			
			// get and drop all auto_users
			dropAllLinkUsernames($db_info,$keep_usernames);
			
			$failed_queue=array();
			// write new users if needed, keep unchanged to preserve project assignments etc.
			if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $UID) { // gelesene DB
				$read_db=getValueUID($list_int_name,$UID,"name");
				foreach ($dbs as $reading_db) { // lesende DB
					$pw_map=$other_db_info[$reading_db];
					if (getValueUID($list_int_name,$UID,$reading_db."_link")) { // checked
						$this_username=generateLinkUsername($read_db,$reading_db);
						if (!usernameExists($this_username) 
							|| !usernameAccessExists($reading_db,$this_username) 
							|| !checkDBLink($read_db,$this_username,$pw_map[ $read_db."_".$this_username ]) ) {
							// must be created/fixed
							if (!createDBLink($read_db,$reading_db)) {
								$failed_queue[]=array($read_db,$reading_db);
							}
						}
					}
				}
			}
			
			// give 2nd chance
			foreach ($failed_queue as $entry) {
				createDBLink($entry[0],$entry[1]);
			}
			
			$other_db_info=getOtherDBInfo(); // read updated info from DB
			
			$message=s("settings_saved");
		break;
		}
	}

	$db_man=array("db_cross" => array(), );

	$db_link_fields=array(
		array("item" => "cell"), 
		array("item" => "input", "int_name" => "name", DEFAULTREADONLY => "always", ), 
		array("item" => "cell"), 
		array("item" => "input", "int_name" => "host", DEFAULTREADONLY => "always", ), 
		array("item" => "cell"), 
		array("item" => "input", "int_name" => "oe_version", DEFAULTREADONLY => "always", ), 
	);
	
	$db_list=array();
	
	for ($a=0;$a<count($db_info);$a++) {
		$db_link_fields[]=array("item" => "cell");
		$db_link_fields[]=array("item" => "checkbox", "group" => "link", "int_name" => $db_info[$a]["name"], "text" => $db_info[$a]["name"], );
		$db_man["db_cross"][$a]=array("name" => $db_info[$a]["name"], "host" => db_server, "oe_version" => $db_info[$a]["version"], );
		
		$read_db=$db_info[$a]["name"];
		$db_list[]=$db_info[$a]["name"];
		//~ switchDB($read_db,$db); // obsolete, global check
		
		for ($b=0;$b<count($db_info);$b++) {
			if ($a==$b) {
				// disabled
				continue;
			}
			$reading_db=$db_info[$b]["name"];
			$pw_map=$other_db_info[$reading_db];
			
			$this_username=generateLinkUsername($read_db,$reading_db);
			//~ echo $this_username."<br>";
			if (usernameExists($this_username) 
				&& usernameAccessExists($reading_db,$this_username) 
				&& checkDBLink($read_db,$this_username,$pw_map[ $read_db."_".$this_username ])) {
				//~ $db_man["db_cross"][$a][$reading_db."_link"]=1;
				$db_man["db_cross"][$a]["link"][$reading_db]=1;
			}
		}
	}

	$field_list=array(
		array(
			"item" => "select", 
			"int_name" => "db_group", 
			"int_names" => $db_list, 
			"texts" => $db_list, 
			"multiMode" => true, 
		), 
		"br", 
		array(
			"item" => "text", 
			"text" => "<input type=\"button\" onClick=\"connect_dbs();\" value=".fixStr(s("connect_dbs")).">", 
		), 
		"br", 
		array(
			"item" => "subitemlist", 
			"int_name" => "db_cross", 
			"noManualAdd" => true, 
			"noManualDelete" => true, 
			"noAutoLinks" => true, 
			"fields" => $db_link_fields
		), 
	);
}

echo getHelperTop()."
<div id=\"browsenav\">".
getAlignTable(
	array("<table class=\"noborder\"><tbody><tr><td><a href=\"Javascript:void submitForm(&quot;main&quot;);\" class=\"imgButtonSm\"><img src=\"lib/save_sm.png\" border=\"0\"".getTooltip("save_changes")."></a></td><td>".$message."</td></tr></tbody></table>"), 
	array("<h1><nobr>".s("db_man")."</nobr></h1>")
).
"<table id=\"tab_bar\" cellspacing=\"0\"><tr>".
"</tr></table>
</div>
<div id=\"browsemain\">
<form name=\"main\" id=\"main\" method=\"POST\"><span id=\"temp\" style=\"display:none\"></span>".
simpleHidden("desired_action").
showHidden(array("int_name" => "save_settings", "value" => "true", )).
getHiddenSubmit().
getFormElements(array(
		READONLY => false, 
		"noFieldSet" => true, 
		"no_db_id_pk" => true, 
	),
	$field_list
)."</form>
</div>".
getHelperBottom().
script;

switch ($_REQUEST["desired_action"]) {
case "db_cross":
default:
	echo "
setControlValues(".json_encode($db_man).",false);

// lock diagonal
var list_int_name=\"db_cross\";
if (controlData[list_int_name]) {
	for (var b=0,max=controlData[list_int_name][\"UIDs\"].length;b<max;b++) {
		var UID=controlData[list_int_name][\"UIDs\"][b];
		SILlockElement(list_int_name,UID,b,undefined,SILgetValue(list_int_name,UID,\"name\"),\"link\",true);
	}
}
";
break;
}

echo _script."
</body>
</html>";

completeDoc();
?>