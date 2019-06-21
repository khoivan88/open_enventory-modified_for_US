<?php
/*
Copyright 2006-2009 Felix Rudolphi and Lukas Goossen
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

// Testing
//~ $ljs=array();
//~ $ljs[]="DOE-DA"; // alt
//~ $ljs[]="LAN-PA"; // neu

// "COS-IA"

//~ /*
$ljs=array(
//~ "ARI-AA","ARI-AB",
//~ "ARN-NA","ARN-NB",
//~ "BLA-AA","BLA-AB","BLA-AC","BLA-AD","BLA-AE","BLA-AF","BLA-AG",
//~ "BRI-BA","BRI-BB",
//~ "DEZ-DA","DEZ-DB",
//~ "DGG-DA","DGG-DB","DGG-DC","DGG-DD","DGG-DE","DGG-DF","DGG-DG","DGG-DH",
//~ "DOE-DA","DOE-DB","DOE-DC","DOE-DD",
//~ "FRO-CA","FRO-CB","FRO-CC",
//~ "GHH-GA","GHH-GC","GHH-GD","GHH-GE","GHH-GF",
//~ "GOZ-GA","GOZ-GB","GOZ-GC","GOZ-GD","GOZ-GE","GOZ-GF","GOZ-GG","GOZ-GH","GOZ-GI","GOZ-GJ","GOZ-GK","GOZ-GL","GOZ-GM","GOZ-GN","GOZ-GO","GOZ-GP",
//~ "JOV-JA",
//~ "KAR-KA","KAR-KB",
//~ "KNA-NA","KNA-NB","KNA-NC","KNA-ND",
//~ "KOY-KA","KOY-KB","KOY-KC",
//~ "LAN-PA","LAN-PB",
//~ "LEV-LA","LEV-VB",
//~ "LIN-LA","LIN-LB","LIN-LC","LIN-LD",
//~ "MAM-XA",
//~ "MEL-MA","MEL-MB","MEL-MC",

//~ "MEL-MD","MEL-ME","MEL-MF","MEL-MG","MEL-MH",

//~ "OHL-HA",
//~ "OPP-OA","OPP-OB",
//~ "PTD-PA","PTD-PB","PTD-PC","PTD-PD","PTD-PE","PTD-PF","PTD-PG","PTD-PH","PTD-PI","PTD-PJ","PTD-PK",
//~ "RHS-RA","RHS-RB","RHS-RC",
//~ "RUD-UA",
//~ "RZG-RA","RZG-RB","RZG-RC","RZG-RD",
//~ "SAL-SA","SAL-SB","SAL-SC","SAL-SD",
//~ "SAL-SA",
//~ "GHH-GB","LIN-LE","LIN-LF","LIN-LG","LIN-LH","BLA-AH","BLA-AI","LAN-PC","RUD-UB","RZG-RE","RZG-RF","RZG-RG","RZG-RH",

//~ "AZU-BI","MAM-XB","OPP-OC",
//~ "FRO-CD", 
//~ "GC1-1A", "GC2-2A", "GC3-3A", 
"OHL-HB",
);
//~ */
//~ $add=true;

require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
require_once "lib_db_manip.php";
require_once "lib_db_query.php";
require_once "lib_io.php";
require_once "lib_analytics.php";
function getFilename($path) {
	$start=strrpos($path,"/")+1;
	return substr($path,$start);
}
function getLJFile($lj_name,$num,$post) {
	return str_replace(array("-"),"",$lj_name.$num.$post);
}

function getGCDir($lj_name,$num) {
	return getLJFile($lj_name,$num,".d");
}

function getACDHNMR($lj_name,$num) {
	return getLJFile($lj_name,$num,"h.esp");
}

function getACDCNMR($lj_name,$num) {
	return getLJFile($lj_name,$num,"c.esp");
}

function getGCMS($lj_name,$num) {
	return getLJFile($lj_name,$num,".sms");
}

function full_copy($source,$target) {
	//~ echo $source."X".$target."<br>";
	$copied=0;
	if (!file_exists($source)) {
		//~ echo $source."<br>";
		return;
	}
	@mkdir($target);
	if (is_dir($source)) {
		$d=dir($source);
		while ( FALSE!==($entry=$d->read())) {
			if ($entry=='.' || $entry=='..') {
				continue;
			}
		       
			$Entry=$source.'/'.$entry;           
			if (is_dir($Entry)) {
				$copied+=full_copy($Entry,$target.'/'.$entry);
				continue;
			}
			copy($Entry,$target.'/'.$entry);
			$copied++;
		}
		$d->close();
        }
	else {
		$target.="/".getFilename($source);
		copy($source,$target);
		$copied++;
        }
	return $copied;
}

function deltree($f) {
	if (!file_exists($f) || is_link($f)) {
		// do nothing
	}
	elseif (is_dir($f)) {
		foreach(glob($f.'/*') as $sf) {
			if (is_dir($sf) && !is_link($sf)) {
				deltree($sf);
			} else {
				unlink($sf);
			}  
		}  
		rmdir($f);
	}
	else {
		unlink($f);
	}
}

function folderNotEmpty($dir) {
	if (!file_exists($dir) || is_link($dir)) {
		return false;
	}
	if (!is_dir($dir)) {
		return false;
	}
	if (count(scandir($dir))>2) {
		return true;
	}
}

function getMoleculeResult(& $molecule) {
	global $db;
	list($molecule_result)=mysql_select_array(array("table" => "molecule_for_reaction", "dbs" => "-1", "filter" => "smiles_stereo LIKE BINARY ".fixStrSQLSearch($molecule["smiles_stereo"]), "limit" => 1),$db);
	
	if (!count($molecule_result)) {
		list($molecule_result)=mysql_select_array(array("table" => "molecule_for_reaction", "dbs" => "-1", "filter" => "smiles LIKE BINARY ".fixStrSQLSearch($molecule["smiles"]), "limit" => 1),$db);
	}
	
	if ($molecule["has_transition_metal"] && !count($molecule_result)) { // helps with coordinative structures
		list($molecule_result)=mysql_select_array(array("table" => "molecule_for_reaction", "dbs" => "-1", "filter" => "emp_formula LIKE BINARY ".fixStrSQLSearch($molecule["emp_formula_string"]), "limit" => 1),$db);
	}
	
	if (!count($molecule_result)) {
		$molecule_result["mw"]=$molecule["mw"];
		$molecule_result["emp_formula"]=$molecule["emp_formula_string"];
	}
	
	return $molecule_result;
}

function fixAmounts($list_int_name,$UID) {
	// cleanup
	$_REQUEST[$list_int_name."_rc_amount_".$UID]=fixNumber($_REQUEST[$list_int_name."_rc_amount_".$UID]);
	$_REQUEST[$list_int_name."_m_brutto_".$UID]=fixNumber($_REQUEST[$list_int_name."_m_brutto_".$UID]);
	$_REQUEST[$list_int_name."_volume_".$UID]=fixNumber($_REQUEST[$list_int_name."_volume_".$UID]);
	$_REQUEST[$list_int_name."_mw_".$UID]=fixNumber($_REQUEST[$list_int_name."_mw_".$UID]);
	$_REQUEST[$list_int_name."_density_20_".$UID]=fixNumber($_REQUEST[$list_int_name."_density_20_".$UID]);
	
	// immer: mmol, mg, ml
	// ist amount gesetzt?
	if (!isEmptyStr($_REQUEST[$list_int_name."_rc_amount_".$UID]) && $_REQUEST[$list_int_name."_mw_".$UID]!=0) {
		$_REQUEST[$list_int_name."_m_brutto_".$UID]=$_REQUEST[$list_int_name."_mw_".$UID]*$_REQUEST[$list_int_name."_rc_amount_".$UID];
		if ($_REQUEST[$list_int_name."_density_20_".$UID]!=0) {
			$_REQUEST[$list_int_name."_volume_".$UID]=$_REQUEST[$list_int_name."_m_brutto_".$UID]/1000/$_REQUEST[$list_int_name."_density_20_".$UID];
		}
	}
	elseif (!isEmptyStr($_REQUEST[$list_int_name."_m_brutto_".$UID])) { // ist masse gesetzt?
		if ($_REQUEST[$list_int_name."_mw_".$UID]!=0) {
			$_REQUEST[$list_int_name."_rc_amount_".$UID]=$_REQUEST[$list_int_name."_m_brutto_".$UID]/$_REQUEST[$list_int_name."_mw_".$UID];
		}
		if ($_REQUEST[$list_int_name."_density_20_".$UID]!=0) {
			$_REQUEST[$list_int_name."_volume_".$UID]=$_REQUEST[$list_int_name."_m_brutto_".$UID]/1000/$_REQUEST[$list_int_name."_density_20_".$UID];
		}
	}
	elseif (!isEmptyStr($_REQUEST[$list_int_name."_volume_".$UID])) { // ist volumen gesetzt?
		if ($_REQUEST[$list_int_name."_density_20_".$UID]!=0) {
			$_REQUEST[$list_int_name."_m_brutto_".$UID]=$_REQUEST[$list_int_name."_volume_".$UID]*$_REQUEST[$list_int_name."_density_20_".$UID]*1000;
		}
		if ($_REQUEST[$list_int_name."_mw_".$UID]!=0) {
			$_REQUEST[$list_int_name."_rc_amount_".$UID]=$_REQUEST[$list_int_name."_m_brutto_".$UID]/$_REQUEST[$list_int_name."_mw_".$UID];
		}
	}
}

// Daten in _REQUEST schreiben und lib_db_manip benutzen

// LJ
//~ $skip_lines=786;

pageHeader();
// mount -t cifs -o ip=131.246.60.175,dom=GOOSSEN,user=rudolphi //goossen01/Laborjournal /mnt/y
$importActive=true;
$oldrequest=$_REQUEST;
$id_GC=2; // gc
$id_6890=6; // agilent

$id_GCMS=3; // generic
$id_Varian=7;

$id_MPLC=4;
$id_ISCO=8; // generic

$id_NMR=1; // nmr
$id_200er=2; // bruker
$id_300er=4; // bruker
$id_400er=1; // bruker
$id_600er=3; // bruker

$id_1H=1; // method
$id_13C=2; // method

foreach ($ljs as $lj_name) {
	// leeres LJ erzeugen
	if ($add) {
		list($lab_journal_data)=mysql_select_array(array("table" => "lab_journal", "filter" => "lab_journal_code=".fixStrSQLSearch($lj_name), "limit" => 1 ));
		if (empty($lab_journal_data["lab_journal_id"])) {
			die("LJ ".$lj_name." not found");
		}
		$_REQUEST["lab_journal_id"]=$lab_journal_data["lab_journal_id"];
	}
	else {
		$_REQUEST["lab_journal_id"]="";
		$_REQUEST["lab_journal_code"]=$lj_name;
		performEdit("lab_journal",-1,$db);
	}
	$oldrequest["lab_journal_id"]=$_REQUEST["lab_journal_id"];
	// kill old stuff
	if (!$add) {
		mysqli_query($db,"DELETE FROM reaction WHERE lab_journal_id=".fixNull($_REQUEST["lab_journal_id"]).";");
	}
	
	$folder="/mnt/y/LabJ/".$lj_name."/";
	
	$handle=fopen($folder.$lj_name.".csv","r") or die($folder.$lj_name.".csv not found");
	// /home/fr/storage/trunk/inventar_dev/
	// /srv/www/htdocs/inventar_dev/
	$zeilen=array();
	unset($reaction_no);
	while (!feof($handle)) {
		$buffer = fgets($handle, 65336);
		$zeilen[]=$buffer;
	}
	fclose ($handle);
	
	// detect type
	$line0fields=explode("\t",$zeilen[0]);
	if (trim($line0fields[0])=="ID") {
		$import_type="old";
	}
	elseif (trim($line0fields[0])=="RXNREGNO") {
		$import_type="new";
	}

	// skip 1st line
	for ($a=1+$skip_lines;$a<count($zeilen);$a++) {// count($zeilen)
		set_time_limit(60);
		$fields=explode("\t",$zeilen[$a]);
		for ($b=0;$b<count($fields);$b++) {
			$fields[$b]=trim($fields[$b]);
		}
		
		if ($fields[0]!=$reaction_no || $a+1==count($zeilen)) {
			if (isset($reaction_no)) {
				// $_REQUEST zum Einfügen von GC, HNMR, CNMR, GCMS zusammenbauen
				// Y als /mnt/y mounten
				
				// reaction_id ist automatisch gesetzt
				
				// GC
				// /mnt/y/LabJ/$lj_name/gc/RHSBS1.d
				$dirname=$folder."gc/".getGCDir($lj_name,$reaction_no);
				$_REQUEST["spzfile"]=$dirname;
				$std_uid="";
				if (folderNotEmpty($dirname)) {
					// report.txt untersuchen auf Methode
					$_REQUEST["analytical_data_analytical_data_interpretation_1"]=fixLineEnd(utf8_encode(@file_get_contents($dirname."/report.txt")));
					$lines=explode("\n",$_REQUEST["analytical_data_analytical_data_interpretation_1"]);
					
					for ($b=0;$b<count($lines);$b++) {
						if (startswith($lines[$b],"Method")) {
							$method_name=$lines[$b];
							$start=strrpos($method_name,"\\")+1;
							$end=strrpos($method_name,".");
							$method_name=substr($method_name,$start,$end-$start);
							list($method_result)=mysql_select_array(array( "table" => "analytics_method", "dbs" => -1, "filter" => "analytics_method_name LIKE ".fixStrSQL($method_name), "limit" => 1 ));
							if (count($method_result)) {
								$_REQUEST["analytics_method_id"]=$method_result["analytics_method_id"];
							}
							else {
								// Method anlegen
								$_REQUEST["analytics_type_id"]=$id_GC;
								$_REQUEST["analytics_device_id"]="";
								$_REQUEST["method_name"]=$method_name;
								performEdit("analytics_method",-1,$db);
							}
							break;
						}
					}
					
					// Methode suchen und ggf erstellen
					
					$_REQUEST["analytics_type_id"]=$id_GC;
					$_REQUEST["analytical_data_analytics_type_name_1"]="gc";
					
					$b=50;
					// Std hinzufügen
					$_REQUEST["reagents"][]=$b;
					$_REQUEST["desired_action_reagents_".$b]="add";
					
					$molecule=array("smiles_stereo" => "CCCCCCCCCCCCCC","smiles" => "CCCCCCCCCCCCCC");
					$molecule_result=getMoleculeResult($molecule);
					
					$_REQUEST["reagents_molfile_blob_".$b]=$molecule_result["molfile_blob"];
					
					$_REQUEST["reagents_molecule_id_".$b]=$molecule_result["molecule_id"];
					$_REQUEST["reagents_standard_name_".$b]=$molecule_result["standard_name"];
					$_REQUEST["reagents_safety_r_".$b]=$molecule_result["safety_r"];
					$_REQUEST["reagents_safety_s_".$b]=$molecule_result["safety_s"];
					$_REQUEST["reagents_safety_sym_".$b]=$molecule_result["safety_sym"];
					$_REQUEST["reagents_cas_nr_".$b]=$molecule_result["cas_nr"];
					$_REQUEST["reagents_mw_".$b]=$molecule_result["mw"];
					$_REQUEST["reagents_emp_formula_".$b]=$molecule_result["emp_formula"];
					
					$_REQUEST["reagents_nr_in_reaction_".$b]=$b+1;
					
					// data from export
					$_REQUEST["reagents_measured_".$b]=1;
					$_REQUEST["reagents_rc_purity_".$b]=100;
					$_REQUEST["reagents_m_brutto_".$b]=40;
					$_REQUEST["reagents_mass_unit_".$b]="mg";
					$_REQUEST["reagents_density_20_".$b]=$molecule_result["density_20"];
					
					$_REQUEST["reagents_rc_amount_unit_".$b]="mmol";
					$_REQUEST["reagents_volume_unit_".$b]="ml";
					
					$std_uid=$b;
					
					fixAmounts("reagents",$b);
					
					$_REQUEST["analytics_device_id"]=$id_6890;
					$_REQUEST["analytical_data_id"]="";
					
					performEdit("analytical_data",-1,$db);
					$_REQUEST["analytical_data"][]=1;
					$_REQUEST["analytical_data_reaction_chemical_uid_1"]="";
					$_REQUEST["desired_action_analytical_data_1"]="add";
					
					$_REQUEST["analytical_data_analytical_data_id_1"]=$_REQUEST["analytical_data_id"];
				}
				
				// HNMR
				// /mnt/y/LabJ/$lj_name/exp/nmr/$num.gif
				// /mnt/y/LabJ/$lj_name/nmr/$num/H
				// /mnt/y/LabJ/$lj_name/nmr/*.esp
				// Rohdaten z.T. auch /mnt/y/LabJ/$lj_name/nmr/$num[1,2]
				$_REQUEST["analytics_type_id"]=$id_NMR;
				
				// über MHz checken, für C-NMR gleiches Gerät annehmen
				$espfile=@file_get_contents($folder."/nmr/".getACDHNMR($lj_name,$reaction_no));
				if (empty($espfile)) {
					$espfile=@file_get_contents($folder."/nmr/".strtolower(getACDHNMR($lj_name,$reaction_no)) );
				}
				if ($espfile) {
					$_REQUEST["analytics_method_id"]=$id_1H;
					$nmr_data=$analytics["nmr"]["acd"]["procFile"]($espfile);
					$mhz=intval($nmr_data["dataHash"]["freq_mhz"]);
					$mhz=100*round($mhz/100);
					switch ($mhz) {
					case 200:
						$_REQUEST["analytics_device_id"]=$id_200er;
					break;
					case 300: // MPI
						$_REQUEST["analytics_device_id"]=$id_300er;
					break;
					case 400:
						$_REQUEST["analytics_device_id"]=$id_400er;
					break;
					case 600:
						$_REQUEST["analytics_device_id"]=$id_600er;
					break;
					default:
						$_REQUEST["analytics_device_id"]="";
					}
				}
				else {
					$_REQUEST["analytics_device_id"]="";
				}
				
				// Zusammenkopieren in Temp-Ordner, hinterher löschen
				$tempdir=sys_get_temp_dir()."/".uniqid();
				
				$hnmr_files=0;
				$hnmr_files+=full_copy($folder."nmr/".$zerofilled_rxn_no."/H",$tempdir);
				$hnmr_files+=full_copy($folder."nmr/".$zerofilled_rxn_no."1",$tempdir);
				$hnmr_files+=full_copy($folder."nmr/".getACDHNMR($lj_name,$reaction_no),$tempdir);
				//~ $hnmr_files+=full_copy($folder."nmr/".strtolower(getACDHNMR($lj_name,$reaction_no)),$tempdir);
				$hnmr_files+=full_copy($folder."exp/nmr/".$reaction_no.".gif",$tempdir);
				
				if ($hnmr_files>0) { // folderNotEmpty($tempdir)
					$_REQUEST["spzfile"]=$tempdir;
					$_REQUEST["analytical_data_id"]="";
					
					performEdit("analytical_data",-1,$db);
					
					$_REQUEST["analytical_data"][]=2;
					$_REQUEST["desired_action_analytical_data_2"]="add";
					$_REQUEST["analytical_data_reaction_chemical_uid_2"]=$prod1uid;
					
					$_REQUEST["analytical_data_analytical_data_id_2"]=$_REQUEST["analytical_data_id"];
				}
				deltree($tempdir);
				
				// CNMR
				// /mnt/y/LabJ/$lj_name/exp/cnmr/$num.gif
				// /mnt/y/LabJ/$lj_name/nmr/$num/C
				// /mnt/y/LabJ/$lj_name/nmr/*.esp
				// Rohdaten z.T. auch /mnt/y/LabJ/$lj_name/nmr/$num[1,2]
				
				// Zusammenkopieren in Temp-Ordner, hinterher löschen
				$_REQUEST["analytics_method_id"]=$id_13C;
				$tempdir=sys_get_temp_dir()."/".uniqid();
				$cnmr_files=0;
				$cnmr_files+=full_copy($folder."nmr/".$zerofilled_rxn_no."/C",$tempdir);
				$cnmr_files+=full_copy($folder."nmr/".$zerofilled_rxn_no."2",$tempdir);
				$cnmr_files+=full_copy($folder."nmr/".getACDCNMR($lj_name,$reaction_no),$tempdir);
				//~ $cnmr_files+=full_copy($folder."nmr/".strtolower(getACDCNMR($lj_name,$reaction_no)),$tempdir);
				$cnmr_files+=full_copy($folder."exp/cnmr/".$reaction_no.".gif",$tempdir);
				
				// sind dateien da?
				if ($cnmr_files>0) { // folderNotEmpty($tempdir)
					$_REQUEST["spzfile"]=$tempdir;
					$_REQUEST["analytical_data_id"]="";
					
					performEdit("analytical_data",-1,$db);
					
					$_REQUEST["analytical_data"][]=3;
					$_REQUEST["desired_action_analytical_data_3"]="add";
					$_REQUEST["analytical_data_reaction_chemical_uid_3"]=$prod1uid;
					
					$_REQUEST["analytical_data_analytical_data_id_3"]=$_REQUEST["analytical_data_id"];
				}
				deltree($tempdir);
				
				// MS
				// /mnt/y/LabJ/$lj_name/exp/gc_ms/$num.gif
				// /mnt/y/LabJ/$lj_name/gcms/*.sms
				// Zusammenkopieren in Temp-Ordner, hinterher löschen
				$tempdir=sys_get_temp_dir()."/".uniqid();
				@mkdir($tempdir);
				full_copy($folder."gcms/".getGCMS($lj_name,$reaction_no),$tempdir);
				full_copy($folder."exp/gc_ms/".$reaction_no.".gif",$tempdir);
				
				// sind dateien da?
				if (folderNotEmpty($tempdir)) {
					$_REQUEST["spzfile"]=$tempdir;
					$_REQUEST["analytics_type_id"]=$id_GCMS;
					$_REQUEST["analytics_device_id"]=$id_Varian;
					$_REQUEST["analytics_method_id"]="";
					$_REQUEST["analytical_data_id"]="";
					
					performEdit("analytical_data",-1,$db);
					
					$_REQUEST["analytical_data"][]=4;
					$_REQUEST["desired_action_analytical_data_4"]="add";
					$_REQUEST["analytical_data_reaction_chemical_uid_4"]=$prod1uid;
					
					$_REQUEST["analytical_data_analytical_data_id_4"]=$_REQUEST["analytical_data_id"];
				}
				deltree($tempdir);
				
				
				// ISCO
				// /mnt/y/LabJ/$lj_name/exp/isco/$num.gif
				$dirname=$folder."exp/isco/".$reaction_no.".gif";
				if (file_exists($dirname)) {
					$_REQUEST["spzfile"]=$dirname;
					$_REQUEST["analytics_type_id"]=$id_MPLC;
					$_REQUEST["analytics_device_id"]=$id_ISCO;
					$_REQUEST["analytics_method_id"]="";
					$_REQUEST["analytical_data_id"]="";
					
					performEdit("analytical_data",-1,$db);
					
					$_REQUEST["analytical_data"][]=5;
					$_REQUEST["desired_action_analytical_data_5"]="add";
					
					$_REQUEST["analytical_data_analytical_data_id_5"]=$_REQUEST["analytical_data_id"];
				}
				
				if ($import_type=="new" && is_array($_REQUEST["analytical_data"]) && in_array(1,$_REQUEST["analytical_data"])) { // gibt es GC?
					// GC-Integrale
					// UID für GC ist 1
					// Std
					$_REQUEST["gc_peak_std_uid_1_"]=$std_uid;
					$_REQUEST["gc_peak_area_percent_1_"]=$std_int;
					$_REQUEST["gc_peak_response_factor_1_"]=1;
					// Prod
					$_REQUEST["gc_peak_area_percent_1_".$main_prod_UID]=$main_prod_int;
					$_REQUEST["gc_peak_response_factor_1_".$main_prod_UID]=1;
					$_REQUEST["gc_peak_gc_yield_1_".$main_prod_UID]=$_REQUEST["products_gc_yield_".$main_prod_UID];
					//~ print_r($_REQUEST);die();
				}
				
				// Reaktion speichern
				//~ print_r($_REQUEST);die();
				performEdit("reaction",-1,$db);
			}
			
			$reaction_no=$fields[0];
			$zerofilled_rxn_no=$reaction_no;
			fillZero($zerofilled_rxn_no,3);
			// neue Reaktion anfangen
			$_REQUEST=$oldrequest;
			
			// RXNfile öffnen und parsen
			// /mnt/y/LabJ/$lj_name/$reaction_no.rxn
			$_REQUEST["rxnfile_blob"]=@file_get_contents($folder."exp/".$reaction_no.".rxn");
			$reaction=readRxnfile($_REQUEST["rxnfile_blob"]);
			
			$_REQUEST["realization_text"]=fixLineEnd(utf8_encode(@file_get_contents($folder."exp/durch".$reaction_no.".txt")));
			
			switch ($import_type) {
			case "new":
				$_REQUEST["reaction_carried_out_by"]=$fields[18];
				$_REQUEST["reaction_started_when"]=$fields[17];
				$_REQUEST["solvent"]=$fields[19];
				$_REQUEST["temperature"]=$fields[20];
				$std_int=$fields[35];
				$main_prod_int=$fields[34];
			break;
			case "old":
				$_REQUEST["reaction_carried_out_by"]=$fields[21];
				$_REQUEST["reaction_started_when"]=$fields[27];
				$_REQUEST["solvent"]=$fields[24];
				$_REQUEST["temperature"]=$fields[23];
				
			break;
			}
			$_REQUEST["status"]=5;
			
			$_REQUEST["additionalFields"]=array("solvent","temperature","reactants_rc_amount_unit","reactants_mass_unit","reactants_volume_unit","products_mass_unit","products_rc_amount_unit");
			
			$_REQUEST["reactants_rc_amount_unit"]="mmol";
			$_REQUEST["reactants_mass_unit"]="mg";
			$_REQUEST["reactants_volume_unit"]="ml";
			
			$_REQUEST["products_mass_unit"]="mg";
			$_REQUEST["products_rc_amount_unit"]="mmol";
			
			if ($import_type=="new") {
				// Reagents, mergen nach Reaktanden
				for ($b=0;$b<2;$b++) {
					$molfile=@file_get_contents($folder."exp/re".($b+1)."_".$reaction_no.".mol");
					if (!strlen($molfile)) {
						continue;
					}
					$_REQUEST["reagents"][]=$b;
					$_REQUEST["desired_action_reagents_".$b]="add";
					
					$_REQUEST["reagents_molfile_blob_".$b]=$molfile;
					$molecule=readMolfile($_REQUEST["reagents_molfile_blob_".$b]); // über SMILES Datensatz finden
					$molecule_result=getMoleculeResult($molecule);
					
					$_REQUEST["reagents_molecule_id_".$b]=$molecule_result["molecule_id"];
					$_REQUEST["reagents_standard_name_".$b]=$molecule_result["standard_name"];
					$_REQUEST["reagents_safety_r_".$b]=$molecule_result["safety_r"];
					$_REQUEST["reagents_safety_s_".$b]=$molecule_result["safety_s"];
					$_REQUEST["reagents_safety_sym_".$b]=$molecule_result["safety_sym"];
					$_REQUEST["reagents_cas_nr_".$b]=$molecule_result["cas_nr"];
					$_REQUEST["reagents_mw_".$b]=ifempty($molecule_result["mw"],$molecule["mw"]);
					$_REQUEST["reagents_emp_formula_".$b]=ifempty($molecule_result["emp_formula"],$molecule["emp_formula_string"]);
					
					$_REQUEST["reagents_nr_in_reaction_".$b]=$b+1;
					
					// data from export
					$_REQUEST["reagents_rc_amount_".$b]=$fields[21+$b*5];
					$_REQUEST["reagents_rc_amount_unit_".$b]="mmol";
					$_REQUEST["reagents_measured_".$b]=3;
					$_REQUEST["reagents_rc_purity_".$b]=$fields[22+$b*5];
					$_REQUEST["reagents_m_brutto_".$b]=$fields[23+$b*5];
					$_REQUEST["reagents_mass_unit_".$b]="mg";
					$_REQUEST["reagents_density_20_".$b]=ifempty($fields[24+$b*5],$molecule_result["density_20"]);
					$_REQUEST["reagents_volume_".$b]=$fields[25+$b*5];
					$_REQUEST["reagents_volume_unit_".$b]="ml";
					
					fixAmounts("reagents",$b);
				}
				
				// Analytik, 1. Prod hat UID $a
				$main_prod_UID="100".$a;
			}
			elseif ($import_type=="old") {
				// A-F
				$mmol=array();
				$names=array();
				$pure=array();
				for ($c=0;$c<4;$c++) { // reactants only
					if (!isEmptyStr($fields[1+$c])) {
						$mmol[]=$fields[1+$c];
						$names[]=$fields[9+$c];
						$pure[]=$fields[15+$c];
					}
				}
				for ($b=0;$b<count($reaction["molecules"]);$b++) {
					if ($b<$reaction["reactants"]) {
						$list_int_name="reactants";
					}
					else {
						$list_int_name="products";
					}
					$_REQUEST[$list_int_name][]=$b;
					$_REQUEST["desired_action_".$list_int_name."_".$b]="add";
					
					$molecule=& $reaction["molecules"][$b];
					$_REQUEST[$list_int_name."_molfile_blob_".$b]=writeMolfile($molecule);
					$molecule_result=getMoleculeResult($molecule);
					
					$_REQUEST[$list_int_name."_molecule_id_".$b]=$molecule_result["molecule_id"];
					$_REQUEST[$list_int_name."_standard_name_".$b]=ifempty($names[$b],$molecule_result["standard_name"]);
					$_REQUEST[$list_int_name."_safety_r_".$b]=$molecule_result["safety_r"];
					$_REQUEST[$list_int_name."_safety_s_".$b]=$molecule_result["safety_s"];
					$_REQUEST[$list_int_name."_safety_sym_".$b]=$molecule_result["safety_sym"];
					$_REQUEST[$list_int_name."_cas_nr_".$b]=$molecule_result["cas_nr"];
					$_REQUEST[$list_int_name."_mw_".$b]=ifempty($molecule_result["mw"],$molecule["mw"]);
					$_REQUEST[$list_int_name."_emp_formula_".$b]=ifempty($molecule_result["emp_formula"],$molecule["emp_formula_string"]);
					
					$_REQUEST[$list_int_name."_nr_in_reaction_".$b]=$b+1;
					
					if ($list_int_name=="reactants") {
						// data from export
						$_REQUEST[$list_int_name."_rc_amount_".$b]=$mmol[$b];
						$_REQUEST[$list_int_name."_rc_amount_unit_".$b]="mmol";
						$_REQUEST[$list_int_name."_measured_".$b]=3;
						$_REQUEST[$list_int_name."_rc_purity_".$b]=$pure[$b];
						$_REQUEST[$list_int_name."_mass_unit_".$b]="mg";
						$_REQUEST[$list_int_name."_density_20_".$b]=$molecule_result["density_20"];
						$_REQUEST[$list_int_name."_volume_unit_".$b]="ml";
						fixAmounts($list_int_name,$b);
					}
					elseif ($b==$reaction["reactants"]) { // 1. Prod
						$_REQUEST[$list_int_name."_gc_yield_".$b]=$fields[26];
						$_REQUEST[$list_int_name."_yield_".$b]=$fields[25];
					}
				}
				// reagents
				$mmol=array();
				$names=array();
				$pure=array();
				$list_int_name="reagents";
				for ($c=4;$c<6;$c++) { // reagents only
					if (!isEmptyStr($fields[1+$c]) && !isEmptyStr($fields[9+$c])) {
						$_REQUEST[$list_int_name][]=$c;
						$_REQUEST["desired_action_".$list_int_name."_".$c]="add";
						$_REQUEST[$list_int_name."_standard_name_".$c]=$fields[9+$c];
						$_REQUEST[$list_int_name."_rc_purity_".$c]=$fields[15+$c];
						$_REQUEST[$list_int_name."_measured_".$c]=3;
						$_REQUEST[$list_int_name."_rc_amount_".$c]=$fields[1+$c];
						$_REQUEST[$list_int_name."_rc_amount_unit_".$c]="mmol";
						$_REQUEST[$list_int_name."_mass_unit_".$c]="mg";
						$_REQUEST[$list_int_name."_mw_".$c]=$fields[ ($c==4?8:7) ];
						fixAmounts($list_int_name,$c);
					}
				}
				
			}
		}
		
		if ($import_type=="new") {
			// Reaktanten
			$line=count($_REQUEST["reactants"]);
			if ($line<$reaction["reactants"]) {
				$_REQUEST["reactants"][]=$a;
				$_REQUEST["desired_action_reactants_".$a]="add";
				
				$molecule=& $reaction["molecules"][$line];
				$_REQUEST["reactants_molfile_blob_".$a]=writeMolfile($molecule);
				$molecule_result=getMoleculeResult($molecule);
				
				$_REQUEST["reactants_molecule_id_".$a]=$molecule_result["molecule_id"];
				$_REQUEST["reactants_standard_name_".$a]=$molecule_result["standard_name"];
				$_REQUEST["reactants_safety_r_".$a]=$molecule_result["safety_r"];
				$_REQUEST["reactants_safety_s_".$a]=$molecule_result["safety_s"];
				$_REQUEST["reactants_safety_sym_".$a]=$molecule_result["safety_sym"];
				$_REQUEST["reactants_cas_nr_".$a]=$molecule_result["cas_nr"];
				$_REQUEST["reactants_mw_".$a]=ifempty($molecule_result["mw"],$molecule["mw"]);
				$_REQUEST["reactants_emp_formula_".$a]=ifempty($molecule_result["emp_formula"],$molecule["emp_formula_string"]);
				
				$_REQUEST["reactants_nr_in_reaction_".$a]=$line+1;
				
				// data from export
				$_REQUEST["reactants_rc_amount_".$a]=$fields[2];
				$_REQUEST["reactants_rc_amount_unit_".$a]="mmol";
				$_REQUEST["reactants_measured_".$a]=3;
				$_REQUEST["reactants_rc_purity_".$a]=$fields[4];
				$_REQUEST["reactants_m_brutto_".$a]=$fields[5];
				$_REQUEST["reactants_mass_unit_".$a]="mg";
				$_REQUEST["reactants_density_20_".$a]=ifempty($fields[6],$molecule_result["density_20"]);
				$_REQUEST["reactants_volume_".$a]=$fields[7];
				$_REQUEST["reactants_volume_unit_".$a]="ml";
				fixAmounts("reactants",$a);
			}
			
			// Produkte
			$line=count($_REQUEST["products"]);
			if ($reaction["products"]==0) {
				unset($prod1uid);
			}
			if ($line<$reaction["products"]) {
				$prod_uid="100".$a;
				if ($line==0) {
					$prod1uid=$prod_uid;
				}
				
				$_REQUEST["products"][]=$prod_uid;
				$_REQUEST["desired_action_products_".$prod_uid]="add";
				
				$molecule=& $reaction["molecules"][$reaction["reactants"]+$line];
				$_REQUEST["products_molfile_blob_".$prod_uid]=writeMolfile($molecule);
				
				$molecule_result=getMoleculeResult($molecule);
				
				$_REQUEST["products_molecule_id_".$prod_uid]=$molecule_result["molecule_id"];
				$_REQUEST["products_standard_name_".$prod_uid]=$molecule_result["standard_name"];
				$_REQUEST["products_safety_r_".$prod_uid]=$molecule_result["safety_r"];
				$_REQUEST["products_safety_s_".$prod_uid]=$molecule_result["safety_s"];
				$_REQUEST["products_safety_sym_".$prod_uid]=$molecule_result["safety_sym"];
				$_REQUEST["products_cas_nr_".$prod_uid]=$molecule_result["cas_nr"];
				$_REQUEST["products_mw_".$prod_uid]=ifempty($molecule_result["mw"],$molecule["mw"]);
				$_REQUEST["products_emp_formula_".$prod_uid]=ifempty($molecule_result["emp_formula"],$molecule["emp_formula_string"]);
				$_REQUEST["products_density_20_".$prod_uid]=$molecule_result["density_20"]; // Prod only
				
				$_REQUEST["products_nr_in_reaction_".$prod_uid]=$line+1;
				
				// data from export
				$_REQUEST["products_rc_amount_".$prod_uid]=$fields[11];
				$_REQUEST["products_rc_amount_unit_".$prod_uid]="mmol";
				$_REQUEST["products_measured_".$prod_uid]=3;
				$_REQUEST["products_rc_purity_".$prod_uid]=$fields[16];
				$_REQUEST["products_m_brutto_".$prod_uid]=$fields[10];
				$_REQUEST["products_mass_unit_".$prod_uid]="mg";
				
				$_REQUEST["products_gc_yield_".$prod_uid]=$fields[13];
				
				if (empty($fields[14]) && !empty($fields[10]) && !empty($fields[11]) && !empty($_REQUEST["products_mw_".$prod_uid])) {
					$fields[14]=100*$fields[10]/$_REQUEST["products_mw_".$prod_uid]/$fields[11]; // calc from given values
				}
				$_REQUEST["products_yield_".$prod_uid]=$fields[14];
					
				//~ fixAmounts("products",$prod_uid); // nicht für Prod, sonst überall 100%
			}
			
			// RXNREGNO	REACTANT LINK>MOL>*fmla MOLSTRUCTURE	amol	aname	arein	amenge	adichte	aml	aid	PRODUCT LINK>MOL>*fmla MOLSTRUCTURE	Masse
			// 0	X		1	emp_fo								2 amount	3		4		5		6		7 X	8 X	9	emp_fo								10
			// Molmenge	Ausb	gc	Proz	Name	Reinh	Date	ausf	Loemi	Temp	reag1mol	reag1rein	reag1m	reag1d	reag1ml	reag2mol	reag2rein	reag2m	reag2d	reag2ml
			// 11	amount	12 X		13 X	14 X	15		16	X	17 X	18 X	19 X		20 X		21 X		22 X		23 X		24 X		25 X		26 X		27 X		28 X		29 X		30 X
			// reag1nam	reag2nam	hilf1	hilf2			hilf3
			// 31			32			33	34	prod_int	35	std_int
			
			// 1. Produkt für Spektrenzuordnung zwischenspeichern
			//~ if ($a>20) die();
		}
	}
}
?>