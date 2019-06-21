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
/*
Zeigt ein GIF an, das a) in der Datenbank (molecule_id=xyz) oder in der Session[gifFile12345678] (timestamp=12345678) abgelegt wurde
oder (save=true) läßt es den Browser herunterladen und unter dem Namen filename=xyz speichern
*/

require_once "lib_global_funcs.php";
require_once "lib_db_query.php";
require_once "lib_formatting.php";
require_once "Spreadsheet/Excel/Writer.php";

set_time_limit(10);

pageHeader(true,false,true,false);

if (!empty($_REQUEST["from_date"])) {
	$categories=array(
		"Carc. Cat 1" => array("filter" => "molecule.safety_cancer IN(\"1\",\"I\")"),
		"Mutagen Cat 1" => array("filter" => "molecule.safety_mutagen IN(\"1\",\"I\")"),
		"Teratogen Cat 1" => array("filter" => "molecule.safety_reprod IN(\"1\",\"I\")"),
	);
	
	$tmp_filename=tempnam(@System::tmpdir(), "OLE_PPS_Root"); // use PEAR function like OLE module
	$workbook=new Spreadsheet_Excel_Writer($tmp_filename); // write to temp file, unfortunately, impossible to get as variable content directly
	$headline_format=& $workbook->addFormat(array("Bold" => 1, ));
	
	$columns=array(
		// event
		array("headline" => s("reaction_started_when"), "field" => "reaction_started_when"),
		array("headline" => s("lab_journal_entry"), "field" => "lab_journal_entry"),
		array("headline" => s("reaction_carried_out_by"), "field" => "reaction_carried_out_by"),
		// identification
		array("headline" => s("molecule_name"), "field" => "standard_name"),
		array("headline" => s("cas_nr"), "field" => "cas_nr"),
		array("headline" => s("emp_formula"), "field" => "emp_formula"),
		// amount
		array("headline" => s("m_brutto")." (g)", "field" => "m_brutto"),
		array("headline" => s("volume")." (ml)", "field" => "volume"),
		array("headline" => s("rc_conc"), "field" => "rc_conc_text"),
		// GHS
		array("headline" => s("safety_sym_ghs"), "field" => "safety_sym_ghs"),
		array("headline" => s("safety_h"), "field" => "safety_h"),
		array("headline" => s("safety_p"), "field" => "safety_p"),
		array("headline" => s("safety_text"), "field" => "safety_text"),
		// old system
		array("headline" => s("safety_sym"), "field" => "safety_sym"),
		array("headline" => s("safety_r"), "field" => "safety_r"),
		array("headline" => s("safety_s"), "field" => "safety_s"),
		// CMR cats
		array("headline" => s("safety_cancer"), "field" => "safety_cancer"),
		array("headline" => s("safety_mutagen"), "field" => "safety_mutagen"),
		array("headline" => s("safety_reprod"), "field" => "safety_reprod"),
	);
	
	$has_entries=false;
	$filename=$_REQUEST["from_date"]."-";
	$from_date=getSQLdate($_REQUEST["from_date"]);
	$date_filter="DATE(reaction_started_when) ";
	if (!empty($_REQUEST["to_date"])) {
		$date_filter.="BETWEEN ".$from_date." AND ".getSQLdate($_REQUEST["to_date"]);
		$filename.=$_REQUEST["to_date"];
	}
	else {
		$date_filter.=">=".$from_date;
		$filename.="now";
	}
	foreach ($categories as $name => $category) {
		$results=mysql_select_array(array(
			"table" => "person_cmr", 
			"filter" => $date_filter." AND (".$category["filter"].")", 
			"dbs" => -1, 
		));
		//~ var_dump($results);die();
		
		if (!count($results)) {
			continue;
		}
		$has_entries=true;
		
		// create worksheet
		$worksheet=& $workbook->addWorksheet(strcut($name,31));
		
		// headlines
		foreach ($columns as $idx => $column) {
			$worksheet->write(0,$idx,html_entity_decode(utf8_decode(trimNbsp($column["headline"]))),$headline_format);
		}
		$worksheet->repeatRows(0);
		
		// data
		foreach ($results as $rowIdx => $result) {
			foreach ($columns as $idx => $column) {
				$worksheet->write($rowIdx+1,$idx,html_entity_decode(utf8_decode(trimNbsp($result[ $column["field"] ]))));
			}
		}
	}
	mysqli_close($db);
	$workbook->close();
	
	if ($has_entries) {
		$workbook->send($filename.".xls"); // output xls headers
		@readfile($tmp_filename);
	} else {
		echo "<html><body>".s("no_results")."</body></html>";
	}
	@unlink($tmp_filename);
}
?>