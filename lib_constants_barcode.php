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

require_once "lib_constants_queries.php";

//---Barcodes
$barcodePrefixes=array(
	// chemical_storage_barcode filtert entsorgte/ABGEGEBENE Gebinde nicht aus
	"1" => array("table" => "chemical_storage", "field" => "field", "search" => "auto", "searchPriority" => 99), // barcodes von der "rolle"
	"2" => array("table" => "chemical_storage", "field" => "pk"), // einzelne gebinde über primärschlüssel (label-druck)
	"3" => array("table" => "reaction", "view" => "ergebnis", "field" => "pk"), // aus reaktionen
	//~ "3" => array("table" => "chemical_storage", "field" => "foreign_key", "fieldName" => "from_reaction_id"), // aus reaktionen
	"91" => array("table" => "person_quick", "field" => "pk"), // personen über primärschlüssel
	"92" => array("table" => "storage", "field" => "pk"), // lager über primärschlüssel
	"93" => array("table" => "person_quick", "field" => "field", "search" => "auto", "searchPriority" => 99), 
	"94" => array("table" => "storage", "field" => "field", "search" => "auto", ), 
	"99" => array("field" => "JSMacro"), // eingabemakros für Javascript
);

function getJSbarcodeHandling($barcode) {
	global $chemical_storage_sizes,$chemical_storage_levels;
	$retval=<<<END
	barcode=trim(barcode);
	
	// barcodes für JS-Aktionen
	// handle prefix
	// alert("X"+barcode+"X");
	var balanceMatch,balanceRE=/(\-?[\d\.]+)\s*([A-Za-z]\w*)/;
	if (balanceMatch=balanceRE.exec(barcode)) {
		// weight from balance
		// alert(balanceMatch[1],balanceMatch[2]);
		var values={"total_mass":balanceMatch[1],"total_mass_unit":balanceMatch[2].toLowerCase()};
		setControlValues(values,true);
		if (is_function(update_mass_calc)) {
			total_mass_focus();
			update_mass_calc("total");
		}
		return;
	}
	else if (startswith(barcode,"99")) { // 99aa123c aa: action, c: checksum
		// ggf datensatz öffnen
		var barcode=barcode.substring(2,barcode.length-1); // aa123
		var action=Number(barcode.substr(0,2));
		if (action!=4 && readOnly) {
			startEditMode(true,barcode);
			return;
		}
		var value=Number(barcode.substr(2,3));
		var amounts=new Array(
END
.join(",",$chemical_storage_sizes).<<<END
);
		switch(action) {
		case 1: // gebindegröße g, 9901xxxy, xxx=0-11
			var values={"amount":amounts[value],"amount_unit":"g"};
		break;
		case 2: // gebindegröße ml, 9902xxxy, xxx=0-11
			var values={"amount":amounts[value],"amount_unit":"ml"};
		break;
		case 3: // füllhöhe, 9903xxxy, xxx=0-5
			var fill_levels=new Array(
END
.join(",",$chemical_storage_levels).<<<END
),amount=getControlValue("amount");
			var values={"actual_amount":fill_levels[value]*amount/100};
		break;
		case 4: // actions, 9904xxxy, xxx=1 => delete
			switch (value) {
			case 0:
END;
	
	if ($barcode) {
		$retval.="
				if (!chemical_storage_id) {
					return;
				}
";
	}
	else { // irrelevant on barcode terminal
		$retval.="
				if (!readOnly || mayWrite()==false) {
					return;
				}
";
	}
	
	$retval.="
				// show warning, delete in 3 sec
				var delay=3;
				showMessage(".fixStr(s("delete_in_sec1"))."+delay+".fixStr(s("delete_in_sec2")).");";
	
	if ($barcode) { // which command?
		$retval.="
				delTimeout=window.setTimeout(function () {delChemicalStorage(true); },(delay+0.5)*1000);";
	}
	else {
		$retval.="
				delTimeout=window.setTimeout(function () {del(true); },(delay+0.5)*1000);";
	}
	
	$retval.="
			break;
			case 1:
				if (delTimeout) {
					window.clearTimeout(delTimeout);
					showMessage(".fixStr(s("cancelled")).");
				}
			break;
			case 2:
				setChecked(\"inventarisation_mode\",true);
				touchOnChange(\"inventarisation_mode\");
			break;
			case 3:
				setChecked(\"inventarisation_mode\",false);
				touchOnChange(\"inventarisation_mode\");
			break;
			}
			return;
		break;
		}
";
	
	if ($barcode) { // barcode terminal
		$retval.="
		setControlValues(values,true);
		doInventar();
		return;
	}
";
	}
	else {
		$retval.="
		var obj=$(\"new_chemical_storage\");
		if (obj) {
			obj.checked=true;
			if (obj.onclick) {
				obj.onclick.call();
			}
		}
		setControlValues(values,true);
		if (values[\"amount\"] && is_function(amount_changed)) {
			amount_changed();
		}
		else if (values[\"actual_amount\"] && is_function(actual_amount_changed)) {
			actual_amount_changed();
		}
		valChanged();
		return;
	}
";
	}
	
	return $retval;
}

function getBarcodeFieldName($tabname) {
	return $tabname."_barcode";
}

function findBarcodePrefixForPk($table) {
	global $barcodePrefixes;
	if (is_array($barcodePrefixes)) foreach ($barcodePrefixes as $prefix => $barcodeData) {
		$base_table=getBaseTable($barcodeData["table"]);
		if ($barcodeData["field"]=="pk" && $base_table==$table) { // richtige tabelle und barcodefeld und durchsuchbar
			return $prefix;
		}
	}
}

function findBarcodePrefix($table,$fieldName=null) {
	global $barcodePrefixes;
	if (is_array($barcodePrefixes)) foreach ($barcodePrefixes as $prefix => $barcodeData) {
		$base_table=getBaseTable($barcodeData["table"]);
		if ($barcodeData["field"]=="field" && $base_table==$table && (is_null($fieldName) || getBarcodeFieldName($base_table)==$fieldName)) { // richtige tabelle und barcodefeld und durchsuchbar
			return $prefix;
		}
	}
}

function getBarcodeFieldSearchType($prefix) {
	global $barcodePrefixes;
	if (isset($barcodePrefixes[$prefix]["type"])) {
		return $barcodePrefixes[$prefix]["type"];
	}
	return "text";
}

function interpretBarcode($barcode,$flags=0) {
	global $barcodePrefixes,$g_settings;
	if (!$g_settings["barcode_allow_any"] && !checkEAN($barcode)) {
		return array();
	}
	if (is_array($barcodePrefixes)) foreach ($barcodePrefixes as $prefix => $data) {
		// barcode_ignore_prefix=>for existing barcode systems, all barcodes must be assigned (no pk barcodes) and all potential fields will be checked until match found, therefore a bit slower
		if ($g_settings["barcode_ignore_prefix"]?$data["field"]=="field":startswith($barcode,$prefix)) {
			// prefix found
			$baseTable=getBaseTable($data["table"]);
			$retval["table"]=$baseTable;
			$retval["variable"]=($data["field"]=="field");
			
			if ($retval["variable"]) { // suchen nach ges barcode
				$stripped_barcode=$barcode;
				if (!is_numeric($stripped_barcode)) {
					$stripped_barcode=fixStr($stripped_barcode);
				}
			}
			else { // suchen nach rest ohne prefix, wenn nicht numerisch, ungültig
				$prefixLen=strlen($prefix);
				$stripped_barcode=substr($barcode,$prefixLen,strlen($barcode)-$prefixLen-1); // remove checksum as well
				if (!is_numeric($stripped_barcode)) {
					return $retval;
				}
				else {
					$stripped_barcode+=0; // remove trailing zeros
				}
			}
			
			// what type?
			if ($retval["variable"]) {
				$retval["fieldName"]=getBarcodeFieldName($baseTable);
			}
			elseif ($data["field"]=="pk") {
				$retval["fieldName"]=getPrimary($baseTable,true);
			}
			elseif ($data["field"]=="foreign_key") {
				$retval["fieldName"]=$data["fieldName"];
			}
			else {
				return $retval;
			}
			
			$filter=$retval["fieldName"]."=".$stripped_barcode;
			
			list($retval["result"])=mysql_select_array(array(
				"table" => $data["table"], // hier steht wirklich table
				"dbs" => ($g_settings["global_barcodes"]?"":"-1"), // search barcodes locally or globally?
				"filter" => $filter, 
				//~ "filterDisabled" => true, // no, we should also find things that were disposed of
				"flags" => $flags, 
				"limit" => 1, 
			));
			
			// in Archiv suchen
			if (empty($retval["result"]) && hasTableArchive($data["table"])) {
				
			}
			
			// MPI specific
			if (empty($retval["result"]) && $data["table"]=="chemical_storage") {
				// search for barcode in mpi_order
				list($retval["result"])=mysql_select_array(array(
					"table" => "mpi_order_item", // hier steht wirklich table
					"dbs" => ($g_settings["global_barcodes"]?"":"-1"), // search barcodes locally or globally?
					"filter" => $filter, 
					"flags" => $flags, 
					"limit" => 1, 
				));
				
				if (count($retval["result"])) {
					$baseTable="mpi_order_item";
					$retval["table"]=getBaseTable($baseTable);
				}
			}
			
			//~ echo "/*".$filter."*/";
			//~ print_r($result);
			if (!empty($retval["result"])) {
				$retval["pk"]=$retval["result"][ getPrimary($baseTable) ];
				//~ $retval["db_id"]="-1";
				$retval["db_id"]=$retval["result"]["db_id"];
			}
			elseif ($g_settings["barcode_ignore_prefix"]) {
				continue;
			}
			
			break; // end loop
		}
	}
	$retval=arr_merge($data,$retval);
	return $retval;
}

?>