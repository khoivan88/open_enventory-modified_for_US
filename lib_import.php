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

function getMoleculeFromOwnDB($cas_nr) {
	global $db;
	if ($cas_nr=="") {
		return;
	}
	$res_link=mysqli_query($db,"SELECT molecule.molecule_id FROM (molecule INNER JOIN molecule_names ON molecule.molecule_id=molecule_names.molecule_id) WHERE cas_nr LIKE ".fixStrSQL($cas_nr).";") or die(mysqli_error($db));
	if (mysqli_num_rows($res_link)>0) {
		$result=mysqli_fetch_assoc($res_link);
		return $result["molecule_id"];
	}
}


//Khoi: to get chemical_storage_id from chemical_storage_barcode
function getChemicalStorageFromOwnDB($chemical_storage_barcode) {
    global $db, $g_settings;
    // If this is none, return NULL
    if ($chemical_storage_barcode == "") {
        return;
    }

    //Khoi: check if the barcode is the OE generated barcode
    if (strlen($chemical_storage_barcode) == 8 && startswith($chemical_storage_barcode, '2') && checkEAN($chemical_storage_barcode)) {
        $chemical_storage_id = intval(substr($chemical_storage_barcode, 1, 6));
    }

    // Find the result of the container in "chemical_storage" table but NOT disposed (if "mark as disposed" setting turned ON)
	$res_link=mysqli_query($db,"SELECT chemical_storage.chemical_storage_id FROM chemical_storage WHERE chemical_storage_barcode LIKE ".fixStrSQL($chemical_storage_barcode)." AND chemical_storage_disabled is NULL;") or die(mysqli_error($db));    //Khoi: only check non-disposed chemicals. If the chemical_storage with $barcode was deleted, $barcode can be reused.
	if (mysqli_num_rows($res_link)>0) {
		$result=mysqli_fetch_assoc($res_link);
    } 
    else {
        $res_link=mysqli_query($db,"SELECT chemical_storage.chemical_storage_id FROM chemical_storage WHERE chemical_storage_id LIKE ".fixStrSQL($chemical_storage_id)." AND chemical_storage_disabled is NULL;") or die(mysqli_error($db));    //Khoi: only check non-disposed chemicals. If the chemical_storage with $barcode was deleted, $barcode can be reused.
		$result=mysqli_fetch_assoc($res_link);
    }
    return $result["chemical_storage_id"];
}


// Khoi: to get mol file from local folder
function getMolFileFromLocal($cas_nr, $molecule_id) {
	global $db;
	if ($cas_nr=="") {
		return;
    }
    $mol_file = "/var/lib/mysql/missing_mol_files/".$cas_nr.".mol";
    // var_dump($mol_file);
    if (file_exists($mol_file)) {
        $res_link=mysqli_query($db, "UPDATE molecule SET molfile_blob=LOAD_FILE('".
        $mol_file.
        "') WHERE molecule_id=".fixStrSQL($molecule_id).";")
        or die(mysqli_error($db));
        return true;
    }
    else {
        return;
    }
}


function createStorageIfNotExist($name) {
	global $db;
	$name=trim($name);
	if ($name=="") {
		return;
	}
	$res_link=mysqli_query($db,"SELECT storage_id FROM storage WHERE storage_name LIKE ".fixStr($name).";") or die(mysqli_error($db));
	if (mysqli_num_rows($res_link)==0) { // neues erstellen
		mysqli_query($db,"INSERT INTO storage (storage_id,storage_name) VALUES (NULL,".fixStr($name).");");
		return mysqli_insert_id($db);
	}
	$result=mysqli_fetch_assoc($res_link);
	return $result["storage_id"];
}

// Khoi: create person if not exist, used in import tab-separated text file
function createPersonIfNotExist($name) {
	global $db;
	$name=trim($name);
	if ($name=="") {
		return;
	}
	$res_link=mysqli_query($db,"SELECT person_id FROM person WHERE username LIKE ".fixStr($name).";") or die(mysqli_error($db));
	if (mysqli_num_rows($res_link)==0) { // create a new one
		mysqli_query($db,"INSERT INTO person (person_id,username) VALUES (NULL,".fixStr($name).");");
		return mysqli_insert_id($db);
	}
	$result=mysqli_fetch_assoc($res_link);
	return $result["person_id"];
}


function createMoleculeTypeIfNotExist($name) {
	global $db;
	$name=trim($name);
	if ($name=="") {
		return;
	}
	$res_link=mysqli_query($db,"SELECT molecule_type_id FROM molecule_type WHERE molecule_type_name LIKE ".fixStr($name).";") or die(mysqli_error($db));
	if (mysqli_num_rows($res_link)==0) { // neues erstellen
		mysqli_query($db,"INSERT INTO molecule_type (molecule_type_id,molecule_type_name) VALUES (NULL,".fixStr($name).");");
		return mysqli_insert_id($db);
	}
	$result=mysqli_fetch_assoc($res_link);
	return $result["molecule_type_id"];
}


function createChemicalStorageTypeIfNotExist($name) {
	global $db;
	$name=trim($name);
	if ($name=="") {
		return;
	}
	$res_link=mysqli_query($db,"SELECT chemical_storage_type_id FROM chemical_storage_type WHERE chemical_storage_type_name LIKE ".fixStr($name).";") or die(mysqli_error($db));
	if (mysqli_num_rows($res_link)==0) { // neues erstellen
		mysqli_query($db,"INSERT INTO chemical_storage_type (chemical_storage_type_id,chemical_storage_type_name) VALUES (NULL,".fixStr($name).");");
		return mysqli_insert_id($db);
	}
	$result=mysqli_fetch_assoc($res_link);
	return $result["chemical_storage_type_id"];
}


function repairUnit($unit) {
	$unit=str_replace(
		array("M", ), 
		array("mol/l", ), 
		$unit
	);
	return str_replace(
		array("litros", "litro", "liters", "liter", "gr", "G", "umol", "ML" ), 
		array("l", "l", "l", "l", "g", "g", "µmol", "ml"), 
		strtolower($unit)
	);
}


function getValue($key,$cells) {
	$idx=$_REQUEST["col_".$key];
	if (!isEmptyStr($idx)) {
		return $cells[$idx];
	}
	return $_REQUEST["fixed_".$key];
}


/*
This function is the original of OE
this takes in a row of data from tab-separated text file and import each entry
as a new container (chemical_storage), or supplier_offer, or storage, or person
*/
function importEachEntry($a, $row, $cols_molecule, $for_chemical_storage, $for_supplier_offer, $for_storage, $for_person) {
    /* 
    $a: number: to keep track of which line is being imported
    $row: array(): row of data from the text file to import
    $cols_molecule: array(): array of column name and info to be imported
    $for_chemical_storage: array(): array of info for importing of chemical containers
    $for_supplier_offer: array(): array of info for importing of supplier offer
    $for_storage: array(): array of info for importing of storage locations
    $for_person: array(): array of info for importing of users
    */

    global $db, $_REQUEST, $g_settings;
    $trimchars=" \t\n\r\0\x0B\"";
    
    $molecule=array();
    $chemical_storage=array();
    $supplier_offer=array();
    // Khoi: added for importing tab-separated text file for storage locations and users
    $storage = array();
    $person = array();
    
    $cells=$row;
    //    echo var_dump($cells);
    for ($b=0;$b<count($cells);$b++) {
        $cells[$b]=trim(autodecode($cells[$b]),$trimchars);
    }
    if ((!$for_storage && !$for_person)  // Khoi: check if it is not importing storage location or person. Storage or Users do not need CAS
        && empty($cells[$_REQUEST["col_molecule_name"]]) 
        && empty($cells[$_REQUEST["col_cas_nr"]])) {
        //		continue;
        //        echo "Missing molecule's name and CAS no!";
        return false;
    }
    
    $molecule["molecule_names_array"]=array();
    foreach ($cols_molecule as $col_molecule) {
        switch ($col_molecule) {
            case "molecule_name":
                $molecule["molecule_names_array"][]=getValue($col_molecule,$cells);
                break;
            case "alt_molecule_name":
            case "alt_molecule_name2":
            case "alt_molecule_name3":
            case "mp_high":
                list($molecule["mp_low"],$molecule["mp_high"])=getRange(getValue($col_molecule,$cells));
                break;
            case "bp_high":
                list($molecule["bp_low"],$molecule["bp_high"],$press)=getRange(getValue($col_molecule,$cells));
                if (isEmptyStr($molecule["bp_high"])) {
                    // do nothing
                }
                elseif (trim($press)!="") {
                    $molecule["bp_press"]=getNumber($press);
                    if (strpos($press,"mm")!==FALSE) {
                        $molecule["press_unit"]="torr";
                    }
                }
                else {
                    $molecule["bp_press"]="1";
                    $molecule["press_unit"]="bar";
                }
                break;
            default:
                $molecule[$col_molecule]=getValue($col_molecule,$cells);
        }
    }

    if ($for_chemical_storage) {
        $molecule["storage_name"]=getValue("storage_name",$cells);
        $molecule["order_date"]=getSQLFormatDate(getTimestampFromDate(getValue("order_date",$cells)));
        // echo "{$molecule["order_date"]}";
        $molecule["open_date"]=getSQLFormatDate(getTimestampFromDate(getValue("open_date",$cells)));
        $chemical_storage["order_date"]=getSQLFormatDate(getTimestampFromDate(getValue("order_date",$cells)));
        // echo "{$molecule["order_date"]}";
        $chemical_storage["open_date"]=getSQLFormatDate(getTimestampFromDate(getValue("open_date",$cells)));
        $chemical_storage["migrate_id_cheminstor"]=getValue("migrate_id_cheminstor",$cells);
        $chemical_storage["comment_cheminstor"]=getValue("comment_cheminstor",$cells);
        $chemical_storage["compartment"]=getValue("compartment",$cells);
        $chemical_storage["description"]=getValue("description",$cells);
        $chemical_storage["cat_no"]=getValue("cat_no",$cells);
        $chemical_storage["lot_no"]=getValue("lot_no",$cells);
        // $chemical_storage["chemical_storage_barcode"]=getValue("chemical_storage_barcode",$cells);
        $chemical_storage["chemical_storage_barcode"]=rtrim(getValue("chemical_storage_barcode",$cells));    // Khoi: fixed so that if this column is the last column in the text file, it will not add whitespace or \n character
        $molecule["supplier"]=getValue("supplier",$cells);
        $molecule["price"]=getNumber(getValue("price",$cells));
        $molecule["price_currency"]=getValue("price_currency",$cells);
    }

    $amount=str_replace(array("(", ")", ),"",getValue("amount",$cells)); // G
    if (preg_match("/(?ims)([\d\.\,]+)\s*[x\*]\s*(.*)/",$amount,$amount_data)) { // de Mendoza-Fix
        $molecule["add_multiple"]=$amount_data[1];
        $amount=$amount_data[2];
    } else {
        $molecule["add_multiple"]=ifempty(getNumber(getValue("add_multiple",$cells)),1); // J
        if ($molecule["add_multiple"]>10) { // probably an error
            $molecule["add_multiple"]=1;
        }
    }
    preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ]+)/",$amount,$amount_data);
    $molecule["amount"]=fixNumber($amount_data[1]);
    $amount_data[2]=repairUnit($amount_data[2]);
    $molecule["amount_unit"]=$amount_data[2];

    // tmd
    $tmd=getValue("tmd",$cells); // G
    preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ]+)/",$tmd,$tmd_data);
    $molecule["tmd"]=fixNumber($tmd_data[1]);
    $tmd_data[2]=repairUnit($tmd_data[2]);
    $molecule["tmd_unit"]=$tmd_data[2];

    $molecule["migrate_id_mol"]=getValue("migrate_id_mol",$cells); // K

    if ($for_supplier_offer) {
        $supplier_offer["so_package_amount"]=$molecule["amount"];
        if ($molecule["add_multiple"]) {
            $supplier_offer["so_package_amount"]*=$molecule["add_multiple"];
        }
        $supplier_offer["so_package_amount_unit"]=$molecule["amount_unit"];
        $supplier_offer["supplier"]=getValue("supplier",$cells);
        $supplier_offer["so_price"]=getNumber(getValue("so_price",$cells));
        $supplier_offer["so_price_currency"]=getValue("so_price_currency",$cells);
        $supplier_offer["catNo"]=getValue("catNo",$cells);
        $supplier_offer["beautifulCatNo"]=getValue("beautifulCatNo",$cells);
    }
    elseif ($for_chemical_storage) {
        $text_actual_amount=getValue("actual_amount",$cells);
        $number_actual_amount=getNumber($text_actual_amount);
        if ($number_actual_amount==="") {
            $chemical_storage["actual_amount"]="";
        }
        else {
            // does it contain any letter(s)?
            if (preg_match("/(?ims)([A-Za-zµ]+)/",$text_actual_amount,$actual_amount_unit)) {
                $actual_amount_unit=repairUnit($actual_amount_unit[1]);
                if ($actual_amount_unit==$molecule["amount_unit"]) {
                    // same unit like the nominal amount
                    $chemical_storage["actual_amount"]=$number_actual_amount; // P
                }
                else {
                    // different unit, try to calculate value
                    $act_factor=getUnitFactor($actual_amount_unit);
                    $factor=getUnitFactor($molecule["amount_unit"]);
                    if ($act_factor && $factor) { // skip if anything not found
                        if ($act_factor < $factor) { // number_actual_amount in mg (0.001), amount in g (1)
                            $chemical_storage["actual_amount"]=$number_actual_amount;
                            $molecule["amount"]*=$factor/$act_factor; // => 1000 mg
                            $molecule["amount_unit"]=$actual_amount_unit;
                        }
                        else {
                            $chemical_storage["actual_amount"]=$number_actual_amount*$act_factor/$factor;
                        }
                    }
                    //~ var_dump($molecule);
                    //~ var_dump($chemical_storage);
                    //~ die($actual_amount_unit."X".$molecule["amount_unit"]."Y".$act_factor."Z".$factor);
                }
            }
            else { // %
                $chemical_storage["actual_amount"]=$molecule["amount"]*$number_actual_amount/100; // P
            }
        }

        // purity concentration/ solvent
        if (preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ\/%]+)(\sin\s)?(.*)?/",getValue("chemical_storage_conc",$cells),$concentration_data)) { // Q
            $chemical_storage["chemical_storage_conc"]=fixNumber($concentration_data[1]);
            $chemical_storage["chemical_storage_conc_unit"]=repairUnit($concentration_data[2]);
            // solvent, empty if not provided
            $chemical_storage["chemical_storage_solvent"]=$concentration_data[4];

            $chemical_storage_density_20=getValue("chemical_storage_density_20",$cells);
            if (!empty($chemical_storage_density_20)) {
                $chemical_storage["chemical_storage_density_20"]=fixNumber($chemical_storage_density_20); // R
            }
        }
    }

    // Khoi: for import tab-separated text file import of storage locations
    elseif ($for_storage) {
        $storage["storage_name"] = rtrim(getValue("storage_name",$cells));
        $storage["storage_barcode"] = rtrim(getValue("storage_barcode",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        // echo "<br>lib_import, line 269 ".$storage["storage_name"];
    }
    // var_dump($storage);
    // Khoi: for import text-separated text file import of user
    elseif ($for_person) {
        $person["title"] = rtrim(getValue("title",$cells));
        $person["last_name"] = rtrim(getValue("last_name",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["first_name"] = rtrim(getValue("first_name",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["username"] = rtrim(getValue("username",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["email"] = rtrim(getValue("email",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["person_barcode"] = rtrim(getValue("person_barcode",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["new_password"] = rtrim(getValue("new_password",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["new_password_repeat"] = $person["new_password"];    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["new_permission"] = rtrim(getValue("permissions",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        if($person["new_permission"] == 'admin') {
            $person["permissions_general"] = array(_admin);    // 
            $person["permissions_chemical"] = array(_storage_modify, _chemical_create, _chemical_edit, _chemical_edit_own, _chemical_borrow, _chemical_inventarise, _chemical_delete, _chemical_read);    
            $person["permissions_lab_journal"] = array(_lj_read);    // allow limited search in lab journal on default
        }
        elseif (empty($person["new_permission"]) || $person["new_permission"] == 'read') {
            $person["permissions_chemical"] = array(_chemical_read, _chemical_borrow);    // allow borrowing and searching chemicals on default
            $person["permissions_lab_journal"] = array(_lj_read);    // allow limited search in lab journal on default
        }
    }

    // set_time_limit(180);
    set_time_limit(90);

    // find cas
    echo "<br>".ucfirst(s("line"))." ".($_REQUEST["skip_lines"]+$a).": ".$molecule["cas_nr"]."<br>";
    flush();
    ob_flush();
    $chemical_storage["molecule_id"]=getMoleculeFromOwnDB($molecule["cas_nr"]);
    $supplier_offer["molecule_id"]=$chemical_storage["molecule_id"];
    if ((!$for_storage && !$for_person)  // Khoi: check if it is not importing storage location or person
        && $chemical_storage["molecule_id"]==""   // neues Molekül
        ) {
        if (!empty($molecule["cas_nr"])) {
            // print warning if CAS No is not valid
            if (!isCAS($molecule["cas_nr"])) {
                echo "Warning: ".$molecule["cas_nr"]." is not valid<br>";
            }
            // echo "Molecule value is ".var_dump($molecule);
            getAddInfo($molecule); // Daten von suppliern holen, kann dauern
        }
        extendMoleculeNames($molecule);
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
        performEdit("molecule",-1,$db);
        $chemical_storage["molecule_id"]=$_REQUEST["molecule_id"];
        $supplier_offer["molecule_id"]=$_REQUEST["molecule_id"];
        $_REQUEST=$oldReq;
    }

    if ($for_supplier_offer) {
        $oldReq=$_REQUEST;
        $_REQUEST=array_merge($_REQUEST,$supplier_offer);
        performEdit("supplier_offer",-1,$db);
        $_REQUEST=$oldReq;
    }
    elseif ($for_chemical_storage) {
        // make mass out of moles, fix for Ligon
        if (getUnitType($molecule["amount_unit"])=="n") {
            // get mw
            list($result)=mysql_select_array(array(
                "table" => "molecule",
                "filter" => "molecule.molecule_id=".fixNull($chemical_storage["molecule_id"]),
                "dbs" => -1,
                "flags" => QUERY_CUSTOM,
            ));

            // get suitable mass unit
            $mass_unit=getComparableUnit($molecule["amount_unit"],"m",$molecule["amount"]*$result["mw"]);

            // calc mass
            $molecule["amount"]=get_mass_from_amount($mass_unit,$molecule["amount"],$molecule["amount_unit"],$result["mw"]);
            $molecule["amount_unit"]=$mass_unit;
        }

        // do we have to create chemical_storage?
        if ($molecule["storage_name"]!="") {
            $chemical_storage["storage_id"]=createStorageIfNotExist($molecule["storage_name"]);
        }
        else {
            $chemical_storage["storage_id"]="";
        }
        
        $oldReq=$_REQUEST;
        
        $chemical_storage=array_merge(
            $chemical_storage,
            array_key_filter(
                $molecule,
                array(
                    "supplier",
                    "price",
                    "price_currency",
                    "comment_cheminstor",
                    "purity",
                    "amount",
                    "amount_unit",
                    "add_multiple",
                    "order_date",
                    "open_date",
                )
            )
        );

        $_REQUEST=array_merge($_REQUEST,$chemical_storage);

        performEdit("chemical_storage",-1,$db);

        $_REQUEST=$oldReq;
    }
    // Khoi: for import text-separated text file import of storage locations and user
    elseif ($for_storage) {
        // Create storage if it does not exist, 
        // return $storage["storage_id"] of the newly created storage or of the existing one
        if ($storage["storage_name"] != "") {
            $storage["storage_id"] = createStorageIfNotExist($storage["storage_name"]);
        }
        else {
            $storage["storage_id"] = "";
        }

        $oldReq=$_REQUEST;
        $_REQUEST=array_merge($_REQUEST,$storage);
        // var_dump($_REQUEST);
        $paramHash = array( "ignoreLock" => true,);
        performEdit("storage",-1,$db, $paramHash);
        $_REQUEST=$oldReq;
    }
    elseif ($for_person) {
        // Khoi: create person if not exist
        // echo "<br> lib_import, line 425<br>";
        if ($person["username"] != "") {
            $person["person_id"] = createPersonIfNotExist($person["username"]);
        }
        else {
            $person["person_id"] = "";
        }

        $oldReq=$_REQUEST;
        $_REQUEST=array_merge($_REQUEST,$person);
        // var_dump($_REQUEST);
        $paramHash = array( "ignoreLock" => true,);
        performEdit("person",-1,$db, $paramHash);
        $_REQUEST=$oldReq;
    }
}


/*
Developed for Baylor University
This function is similar to importEachEntry. However, for chemical containers
(chemical_storage), it checks if the barcode exists in the current database,
and not disposed. If Yes, it will edit the info of that container. If No, it 
will add the entry as a new container
See also: 
    function importEachEntry(),
    function importNoEditEachEntry() 
*/
function importAndEditEachEntry($a, $row, $cols_molecule, $for_chemical_storage, $for_supplier_offer, $for_storage, $for_person) {
    /* 
    $a: number: to keep track of which line is being imported
    $row: array(): row of data from the text file to import
    $cols_molecule: array(): array of column name and info to be imported
    $for_chemical_storage: array(): array of info for importing of chemical containers
    $for_supplier_offer: array(): array of info for importing of supplier offer
    $for_storage: array(): array of info for importing of storage locations
    $for_person: array(): array of info for importing of users
    */

    global $db, $_REQUEST, $g_settings;
    $trimchars=" \t\n\r\0\x0B\"";
    
    $molecule=array();
    $chemical_storage=array();
    $supplier_offer=array();
    // Khoi: added for importing tab-separated text file for storage locations and users
    $storage = array();
    $person = array();
    
    $cells=$row;
    //    echo var_dump($cells);
    for ($b=0;$b<count($cells);$b++) {
        $cells[$b]=trim(autodecode($cells[$b]),$trimchars);
    }
    if ((!$for_storage && !$for_person)  // Khoi: check if it is not importing storage location or person. Storage or Users do not need CAS
        && empty($cells[$_REQUEST["col_molecule_name"]]) 
        && empty($cells[$_REQUEST["col_cas_nr"]])) {
        //		continue;
        //        echo "Missing molecule's name and CAS no!";
        return false;
    }
    
    $molecule["molecule_names_array"]=array();
    foreach ($cols_molecule as $col_molecule) {
        switch ($col_molecule) {
            case "molecule_name":
                $molecule["molecule_names_array"][]=getValue($col_molecule,$cells);
                break;
            case "alt_molecule_name":
            case "alt_molecule_name2":
            case "alt_molecule_name3":
            case "mp_high":
                list($molecule["mp_low"],$molecule["mp_high"])=getRange(getValue($col_molecule,$cells));
                break;
            case "bp_high":
                list($molecule["bp_low"],$molecule["bp_high"],$press)=getRange(getValue($col_molecule,$cells));
                if (isEmptyStr($molecule["bp_high"])) {
                    // do nothing
                }
                elseif (trim($press)!="") {
                    $molecule["bp_press"]=getNumber($press);
                    if (strpos($press,"mm")!==FALSE) {
                        $molecule["press_unit"]="torr";
                    }
                }
                else {
                    $molecule["bp_press"]="1";
                    $molecule["press_unit"]="bar";
                }
                break;
            default:
                $molecule[$col_molecule]=getValue($col_molecule,$cells);
        }
    }

    if ($for_chemical_storage) {
        $molecule["storage_name"]=getValue("storage_name",$cells);
        $molecule["order_date"]=getSQLFormatDate(getTimestampFromDate(getValue("order_date",$cells)));
        // echo "{$molecule["order_date"]}";
        $molecule["open_date"]=getSQLFormatDate(getTimestampFromDate(getValue("open_date",$cells)));
        $chemical_storage["order_date"]=getSQLFormatDate(getTimestampFromDate(getValue("order_date",$cells)));
        // echo "{$molecule["order_date"]}";
        $chemical_storage["open_date"]=getSQLFormatDate(getTimestampFromDate(getValue("open_date",$cells)));
        $chemical_storage["migrate_id_cheminstor"]=getValue("migrate_id_cheminstor",$cells);
        $chemical_storage["comment_cheminstor"]=getValue("comment_cheminstor",$cells);
        $chemical_storage["compartment"]=getValue("compartment",$cells);
        $chemical_storage["description"]=getValue("description",$cells);
        $chemical_storage["cat_no"]=getValue("cat_no",$cells);
        $chemical_storage["lot_no"]=getValue("lot_no",$cells);
        // $chemical_storage["chemical_storage_barcode"]=getValue("chemical_storage_barcode",$cells);
        $chemical_storage["chemical_storage_barcode"]=rtrim(getValue("chemical_storage_barcode",$cells));    // Khoi: fixed so that if this column is the last column in the text file, it will not add whitespace or \n character
        $molecule["supplier"]=getValue("supplier",$cells);
        $molecule["price"]=getNumber(getValue("price",$cells));
        $molecule["price_currency"]=getValue("price_currency",$cells);
    }

    $amount=str_replace(array("(", ")", ),"",getValue("amount",$cells)); // G
    if (preg_match("/(?ims)([\d\.\,]+)\s*[x\*]\s*(.*)/",$amount,$amount_data)) { // de Mendoza-Fix
        $molecule["add_multiple"]=$amount_data[1];
        $amount=$amount_data[2];
    } else {
        $molecule["add_multiple"]=ifempty(getNumber(getValue("add_multiple",$cells)),1); // J
        if ($molecule["add_multiple"]>10) { // probably an error
            $molecule["add_multiple"]=1;
        }
    }
    preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ]+)/",$amount,$amount_data);
    $molecule["amount"]=fixNumber($amount_data[1]);
    $amount_data[2]=repairUnit($amount_data[2]);
    $molecule["amount_unit"]=$amount_data[2];

    // tmd
    $tmd=getValue("tmd",$cells); // G
    preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ]+)/",$tmd,$tmd_data);
    $molecule["tmd"]=fixNumber($tmd_data[1]);
    $tmd_data[2]=repairUnit($tmd_data[2]);
    $molecule["tmd_unit"]=$tmd_data[2];

    $molecule["migrate_id_mol"]=getValue("migrate_id_mol",$cells); // K

    if ($for_supplier_offer) {
        $supplier_offer["so_package_amount"]=$molecule["amount"];
        if ($molecule["add_multiple"]) {
            $supplier_offer["so_package_amount"]*=$molecule["add_multiple"];
        }
        $supplier_offer["so_package_amount_unit"]=$molecule["amount_unit"];
        $supplier_offer["supplier"]=getValue("supplier",$cells);
        $supplier_offer["so_price"]=getNumber(getValue("so_price",$cells));
        $supplier_offer["so_price_currency"]=getValue("so_price_currency",$cells);
        $supplier_offer["catNo"]=getValue("catNo",$cells);
        $supplier_offer["beautifulCatNo"]=getValue("beautifulCatNo",$cells);
    }
    elseif ($for_chemical_storage) {
        $text_actual_amount=getValue("actual_amount",$cells);
        $number_actual_amount=getNumber($text_actual_amount);
        if ($number_actual_amount==="") {
            $chemical_storage["actual_amount"]="";
        }
        else {
            // does it contain any letter(s)?
            if (preg_match("/(?ims)([A-Za-zµ]+)/",$text_actual_amount,$actual_amount_unit)) {
                $actual_amount_unit=repairUnit($actual_amount_unit[1]);
                if ($actual_amount_unit==$molecule["amount_unit"]) {
                    // same unit like the nominal amount
                    $chemical_storage["actual_amount"]=$number_actual_amount; // P
                }
                else {
                    // different unit, try to calculate value
                    $act_factor=getUnitFactor($actual_amount_unit);
                    $factor=getUnitFactor($molecule["amount_unit"]);
                    if ($act_factor && $factor) { // skip if anything not found
                        if ($act_factor < $factor) { // number_actual_amount in mg (0.001), amount in g (1)
                            $chemical_storage["actual_amount"]=$number_actual_amount;
                            $molecule["amount"]*=$factor/$act_factor; // => 1000 mg
                            $molecule["amount_unit"]=$actual_amount_unit;
                        }
                        else {
                            $chemical_storage["actual_amount"]=$number_actual_amount*$act_factor/$factor;
                        }
                    }
                    //~ var_dump($molecule);
                    //~ var_dump($chemical_storage);
                    //~ die($actual_amount_unit."X".$molecule["amount_unit"]."Y".$act_factor."Z".$factor);
                }
            }
            else { // %
                $chemical_storage["actual_amount"]=$molecule["amount"]*$number_actual_amount/100; // P
            }
        }

        // purity concentration/ solvent
        if (preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ\/%]+)(\sin\s)?(.*)?/",getValue("chemical_storage_conc",$cells),$concentration_data)) { // Q
            $chemical_storage["chemical_storage_conc"]=fixNumber($concentration_data[1]);
            $chemical_storage["chemical_storage_conc_unit"]=repairUnit($concentration_data[2]);
            // solvent, empty if not provided
            $chemical_storage["chemical_storage_solvent"]=$concentration_data[4];

            $chemical_storage_density_20=getValue("chemical_storage_density_20",$cells);
            if (!empty($chemical_storage_density_20)) {
                $chemical_storage["chemical_storage_density_20"]=fixNumber($chemical_storage_density_20); // R
            }
        }
    }

    // Khoi: for import tab-separated text file import of storage locations
    elseif ($for_storage) {
        $storage["storage_name"] = rtrim(getValue("storage_name",$cells));
        $storage["storage_barcode"] = rtrim(getValue("storage_barcode",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        // echo "<br>lib_import, line 269 ".$storage["storage_name"];
    }
    // var_dump($storage);
    // Khoi: for import text-separated text file import of user
    elseif ($for_person) {
        $person["title"] = rtrim(getValue("title",$cells));
        $person["last_name"] = rtrim(getValue("last_name",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["first_name"] = rtrim(getValue("first_name",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["username"] = rtrim(getValue("username",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["email"] = rtrim(getValue("email",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["person_barcode"] = rtrim(getValue("person_barcode",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["new_password"] = rtrim(getValue("new_password",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["new_password_repeat"] = $person["new_password"];    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        $person["new_permission"] = rtrim(getValue("permissions",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
        if($person["new_permission"] == 'admin') {
            $person["permissions_general"] = array(_admin);    // 
            $person["permissions_chemical"] = array(_storage_modify, _chemical_create, _chemical_edit, _chemical_edit_own, _chemical_borrow, _chemical_inventarise, _chemical_delete, _chemical_read);    
            $person["permissions_lab_journal"] = array(_lj_read);    // allow limited search in lab journal on default
        }
        elseif (empty($person["new_permission"]) || $person["new_permission"] == 'read') {
            $person["permissions_chemical"] = array(_chemical_read, _chemical_borrow);    // allow borrowing and searching chemicals on default
            $person["permissions_lab_journal"] = array(_lj_read);    // allow limited search in lab journal on default
        }
    }

    // set_time_limit(180);
    set_time_limit(90);

    // find cas
    echo "<br>".ucfirst(s("line"))." ".($_REQUEST["skip_lines"]+$a).": ".$molecule["cas_nr"]."<br>";
    flush();
    ob_flush();
    $chemical_storage["molecule_id"]=getMoleculeFromOwnDB($molecule["cas_nr"]);
    
    // Khoi: This only affect some institution with the customization turned ON.
    if (in_array($g_settings["customization"], array("baylor",), true)) {
        //Khoi: find chemical_storage_id to edit own chemicals
        $chemical_storage["chemical_storage_id"] = getChemicalStorageFromOwnDB($chemical_storage["chemical_storage_barcode"]);   
        // var_dump($chemical_storage["chemical_storage_id"]);
    }
    
    $supplier_offer["molecule_id"]=$chemical_storage["molecule_id"];
    if ((!$for_storage && !$for_person)  // Khoi: check if it is not importing storage location or person
        && $chemical_storage["molecule_id"]==""   // neues Molekül
        && !$chemical_storage["chemical_storage_id"]) {   // Khoi: check if the chemical_storage does not exist by chemical_storage_barcode
        if (!empty($molecule["cas_nr"])) {
            // print warning if CAS No is not valid
            if (!isCAS($molecule["cas_nr"])) {
                echo "Warning: ".$molecule["cas_nr"]." is not valid<br>";
            }
            // echo "Molecule value is ".var_dump($molecule);
            getAddInfo($molecule); // Daten von suppliern holen, kann dauern
        }
        extendMoleculeNames($molecule);
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
        performEdit("molecule",-1,$db);
        $chemical_storage["molecule_id"]=$_REQUEST["molecule_id"];
        $supplier_offer["molecule_id"]=$_REQUEST["molecule_id"];
        $_REQUEST=$oldReq;
    }
    
    /*-------------------------------------------------------------------------------------------------------
    Checking if the molecule has structure. 
    If not, use existing mol file inside /var/lib/mysql/missing_mol_files/
    */
    list($result)=mysql_select_array(array(
        "table" => "molecule",
        "filter" => "molecule.molecule_id=".fixNull($chemical_storage["molecule_id"]),
        "dbs" => -1,
        "flags" => QUERY_CUSTOM,
    ));
    // echo 'Khoi: before<br>';
    // var_dump($result);

    // Check if molecule has structure by checking smiles string mol_file_blob if they are empty
    if (!empty($molecule["cas_nr"] && (empty($result['smiles']) || empty($result["mol_file_blob"])))) {
        // Update mol_file_blob
        getMolFileFromLocal($molecule["cas_nr"], $chemical_storage["molecule_id"]);

        // Check the molecule info result again
        list($result)=mysql_select_array(array(
            "table" => "molecule",
            "filter" => "molecule.molecule_id=".fixNull($chemical_storage["molecule_id"]),
            "dbs" => -1,
            "flags" => QUERY_CUSTOM,
        ));
        // echo 'Khoi: after<br>';
        // var_dump($result);

        /* Set these info to 1 (true) to have OE fix them: 
        "molfile_blob" : structure
        emp_formula : molecular formular
        mw : molecular weight 
        fingerprint : structure fingerprint 
        rdb: degree of unsaturation */
        $_REQUEST["molfile_blob"] = 1;
        $_REQUEST["emp_formula"] = 1;
        $_REQUEST["mw"] = 1;
        $_REQUEST["rdb"] = 1;
        $_REQUEST["smiles"] = 1;
        $_REQUEST["fingerprint"] = 1;
    
        $sql_parts=array();
        if (!empty($result["molfile_blob"])) {
            $molecule_search=readMolfile($result["molfile_blob"],array() ); // for  fingerprinting and serialisation
        }
        // Set up sql command:
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
        if ($_REQUEST["fingerprint"]) {
            $sql_parts[]="molecule_serialized=".fixBlob(serializeMolecule($molecule_search));
            $sql_parts[]=getFingerprintSQL($molecule_search,true);
        }
        // update sql database 
        if (count($sql_parts)) {
            $sql="UPDATE molecule SET ".join(",",$sql_parts)." WHERE molecule_id=".fixNull($result["molecule_id"]).";";
            mysqli_query($db,$sql) or die($sql.mysqli_error($db));
        }
    }
    /*-------------------------------------------------------------------------------------------------------
    End Checking if the molecule has structure. 
    */

    if ($for_supplier_offer) {
        $oldReq=$_REQUEST;
        $_REQUEST=array_merge($_REQUEST,$supplier_offer);
        performEdit("supplier_offer",-1,$db);
        $_REQUEST=$oldReq;
    }
    elseif ($for_chemical_storage) {
        // make mass out of moles, fix for Ligon
        if (getUnitType($molecule["amount_unit"])=="n") {
            // get mw
            list($result)=mysql_select_array(array(
                "table" => "molecule",
                "filter" => "molecule.molecule_id=".fixNull($chemical_storage["molecule_id"]),
                "dbs" => -1,
                "flags" => QUERY_CUSTOM,
            ));

            // get suitable mass unit
            $mass_unit=getComparableUnit($molecule["amount_unit"],"m",$molecule["amount"]*$result["mw"]);

            // calc mass
            $molecule["amount"]=get_mass_from_amount($mass_unit,$molecule["amount"],$molecule["amount_unit"],$result["mw"]);
            $molecule["amount_unit"]=$mass_unit;
        }

        // do we have to create chemical_storage?
        if ($molecule["storage_name"]!="") {
            $chemical_storage["storage_id"]=createStorageIfNotExist($molecule["storage_name"]);
        }
        else {
            $chemical_storage["storage_id"]="";
        }

        $oldReq=$_REQUEST;
        
        // Khoi: specifically designed for Baylor University, this will edit container "storage", "comment" and "migrate_id" only
        if ($chemical_storage["chemical_storage_id"]) {
            // $chemical_storage=array_merge(
            //     $chemical_storage,
            //     array_key_filter(
            //         $molecule,
            //         array(
            //             "supplier",
            //             // "price",
            //             // "price_currency",
            //             // "comment_cheminstor",
            //             // "purity",
            //             // "amount",
            //             // "amount_unit",
            //             // "add_multiple",
            //             // "order_date",
            //             // "open_date",
            //         )
            //     )
            // );

            $_REQUEST["desired_action"] = "update";
            // $_REQUEST["chemical_storage"] = $chemical_storage["chemical_storage_id"];
            $paramHash = array( "ignoreLock" => true,);  //Khoi: if not set "ignoreLock" to true, edit will not proceed
            // Khoi: get current info of the container ("chemical_storage")
            list($chemical_storage_existing_info)=mysql_select_array(array(
                "table" => "chemical_storage","molecule",
                "filter" => "chemical_storage.chemical_storage_id=".fixNull($chemical_storage["chemical_storage_id"]),
                "dbs" => -1,
                "limit" => 1, 
                "flags" => QUERY_CUSTOM,
            ));
            // $_REQUEST=array_merge($_REQUEST,$chemical_storage);

            // Khoi: if not remove these key, value pair, when import/edit, it will remove existing info in the following keys
            unset(  $chemical_storage["order_date"], 
                    $chemical_storage["open_date"], 
                    // $chemical_storage["compartment"], 
                    $chemical_storage["description"], 
                    $chemical_storage["cat_no"], 
                    $chemical_storage["lot_no"],
                    $chemical_storage["molecule_id"],
                    $chemical_storage["actual_amount"],
                );

            $_REQUEST=array_merge($_REQUEST,$chemical_storage_existing_info, $chemical_storage);
            // var_dump($chemical_storage_existing_info);
            // var_dump($chemical_storage);
            // var_dump($_REQUEST);

            performEdit("chemical_storage",-1,$db, $paramHash);
        }
        else {
            $chemical_storage=array_merge(
                $chemical_storage,
                array_key_filter(
                    $molecule,
                    array(
                        "supplier",
                        "price",
                        "price_currency",
                        "comment_cheminstor",
                        "purity",
                        "amount",
                        "amount_unit",
                        "add_multiple",
                        "order_date",
                        "open_date",
                    )
                )
            );
    
            $_REQUEST=array_merge($_REQUEST,$chemical_storage);
            
            // var_dump($_REQUEST("chemical_storage_barcode"));
            // var_dump($_REQUEST);
            // var_dump($chemical_storage_id);
            // var_dump($result);
            // var_dump($chemical_storage["chemical_storage_barcode"]);
            // performEdit("chemical_storage",-1,$db, $paramHash);
            performEdit("chemical_storage",-1,$db);
        }

        $_REQUEST=$oldReq;
    }
    // Khoi: for import text-separated text file import of storage locations and user
    elseif ($for_storage) {
        // Create storage if it does not exist, 
        // return $storage["storage_id"] of the newly created storage or of the existing one
        if ($storage["storage_name"] != "") {
            $storage["storage_id"] = createStorageIfNotExist($storage["storage_name"]);
        }
        else {
            $storage["storage_id"] = "";
        }

        $oldReq=$_REQUEST;
        $_REQUEST=array_merge($_REQUEST,$storage);
        // var_dump($_REQUEST);
        $paramHash = array( "ignoreLock" => true,);
        performEdit("storage",-1,$db, $paramHash);
        $_REQUEST=$oldReq;
    }
    elseif ($for_person) {
        // Khoi: create person if not exist
        // echo "<br> lib_import, line 425<br>";
        if ($person["username"] != "") {
            $person["person_id"] = createPersonIfNotExist($person["username"]);
        }
        else {
            $person["person_id"] = "";
        }

        $oldReq=$_REQUEST;
        $_REQUEST=array_merge($_REQUEST,$person);
        // var_dump($_REQUEST);
        $paramHash = array( "ignoreLock" => true,);
        performEdit("person",-1,$db, $paramHash);
        $_REQUEST=$oldReq;
    }
}


/*
Developed for Baylor University
This function is designed for importing chemical container (chemical_storage) only.
This function is similar to importAndEditEachEntry for chemical_storage. 
However, for chemical containers (chemical_storage), it checks if the
barcode exists in the current database,and not disposed. 
    If Yes, it will ignore this data entry. 
    If No, it will add the entry as a new container
See also: 
    function importEachEntry(),
    function importAndEditEachEntry() 
*/
function importNoEditEachEntry($a, $row, $cols_molecule, $for_chemical_storage) {
    /* 
    $a: number: to keep track of which line is being imported
    $row: array(): row of data from the text file to import
    $cols_molecule: array(): array of column name and info to be imported
    $for_chemical_storage: array(): array of info for importing of chemical containers
    $for_supplier_offer: array(): array of info for importing of supplier offer
    $for_storage: array(): array of info for importing of storage locations
    $for_person: array(): array of info for importing of users
    */

    global $db, $_REQUEST, $g_settings;
    $trimchars=" \t\n\r\0\x0B\"";
    
    $molecule=array();
    $chemical_storage=array();
    $supplier_offer=array();
    // Khoi: added for importing tab-separated text file for storage locations and users
    $storage = array();
    $person = array();
    
    $cells=$row;
    //    echo var_dump($cells);
    for ($b=0;$b<count($cells);$b++) {
        $cells[$b]=trim(autodecode($cells[$b]),$trimchars);
    }
    if ((!$for_storage && !$for_person)  // Khoi: check if it is not importing storage location or person. Storage or Users do not need CAS
        && empty($cells[$_REQUEST["col_molecule_name"]]) 
        && empty($cells[$_REQUEST["col_cas_nr"]])) {
        //		continue;
        //        echo "Missing molecule's name and CAS no!";
        return false;
    }
    
    $molecule["molecule_names_array"]=array();
    foreach ($cols_molecule as $col_molecule) {
        switch ($col_molecule) {
            case "molecule_name":
                $molecule["molecule_names_array"][]=getValue($col_molecule,$cells);
                break;
            case "alt_molecule_name":
            case "alt_molecule_name2":
            case "alt_molecule_name3":
            case "mp_high":
                list($molecule["mp_low"],$molecule["mp_high"])=getRange(getValue($col_molecule,$cells));
                break;
            case "bp_high":
                list($molecule["bp_low"],$molecule["bp_high"],$press)=getRange(getValue($col_molecule,$cells));
                if (isEmptyStr($molecule["bp_high"])) {
                    // do nothing
                }
                elseif (trim($press)!="") {
                    $molecule["bp_press"]=getNumber($press);
                    if (strpos($press,"mm")!==FALSE) {
                        $molecule["press_unit"]="torr";
                    }
                }
                else {
                    $molecule["bp_press"]="1";
                    $molecule["press_unit"]="bar";
                }
                break;
            default:
                $molecule[$col_molecule]=getValue($col_molecule,$cells);
        }
    }

    if ($for_chemical_storage) {
        $molecule["storage_name"]=getValue("storage_name",$cells);
        $molecule["order_date"]=getSQLFormatDate(getTimestampFromDate(getValue("order_date",$cells)));
        // echo "{$molecule["order_date"]}";
        $molecule["open_date"]=getSQLFormatDate(getTimestampFromDate(getValue("open_date",$cells)));
        $chemical_storage["order_date"]=getSQLFormatDate(getTimestampFromDate(getValue("order_date",$cells)));
        // echo "{$molecule["order_date"]}";
        $chemical_storage["open_date"]=getSQLFormatDate(getTimestampFromDate(getValue("open_date",$cells)));
        $chemical_storage["migrate_id_cheminstor"]=getValue("migrate_id_cheminstor",$cells);
        $chemical_storage["comment_cheminstor"]=getValue("comment_cheminstor",$cells);
        $chemical_storage["compartment"]=getValue("compartment",$cells);
        $chemical_storage["description"]=getValue("description",$cells);
        $chemical_storage["cat_no"]=getValue("cat_no",$cells);
        $chemical_storage["lot_no"]=getValue("lot_no",$cells);
        // $chemical_storage["chemical_storage_barcode"]=getValue("chemical_storage_barcode",$cells);
        $chemical_storage["chemical_storage_barcode"]=rtrim(getValue("chemical_storage_barcode",$cells));    // Khoi: fixed so that if this column is the last column in the text file, it will not add whitespace or \n character
        $molecule["supplier"]=getValue("supplier",$cells);
        $molecule["price"]=getNumber(getValue("price",$cells));
        $molecule["price_currency"]=getValue("price_currency",$cells);
    }

    $amount=str_replace(array("(", ")", ),"",getValue("amount",$cells)); // G
    if (preg_match("/(?ims)([\d\.\,]+)\s*[x\*]\s*(.*)/",$amount,$amount_data)) { // de Mendoza-Fix
        $molecule["add_multiple"]=$amount_data[1];
        $amount=$amount_data[2];
    } else {
        $molecule["add_multiple"]=ifempty(getNumber(getValue("add_multiple",$cells)),1); // J
        if ($molecule["add_multiple"]>10) { // probably an error
            $molecule["add_multiple"]=1;
        }
    }
    preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ]+)/",$amount,$amount_data);
    $molecule["amount"]=fixNumber($amount_data[1]);
    $amount_data[2]=repairUnit($amount_data[2]);
    $molecule["amount_unit"]=$amount_data[2];

    // tmd
    $tmd=getValue("tmd",$cells); // G
    preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ]+)/",$tmd,$tmd_data);
    $molecule["tmd"]=fixNumber($tmd_data[1]);
    $tmd_data[2]=repairUnit($tmd_data[2]);
    $molecule["tmd_unit"]=$tmd_data[2];

    $molecule["migrate_id_mol"]=getValue("migrate_id_mol",$cells); // K

    // if ($for_supplier_offer) {
    //     $supplier_offer["so_package_amount"]=$molecule["amount"];
    //     if ($molecule["add_multiple"]) {
    //         $supplier_offer["so_package_amount"]*=$molecule["add_multiple"];
    //     }
    //     $supplier_offer["so_package_amount_unit"]=$molecule["amount_unit"];
    //     $supplier_offer["supplier"]=getValue("supplier",$cells);
    //     $supplier_offer["so_price"]=getNumber(getValue("so_price",$cells));
    //     $supplier_offer["so_price_currency"]=getValue("so_price_currency",$cells);
    //     $supplier_offer["catNo"]=getValue("catNo",$cells);
    //     $supplier_offer["beautifulCatNo"]=getValue("beautifulCatNo",$cells);
    // }
    if ($for_chemical_storage) {
        $text_actual_amount=getValue("actual_amount",$cells);
        $number_actual_amount=getNumber($text_actual_amount);
        if ($number_actual_amount==="") {
            $chemical_storage["actual_amount"]="";
        }
        else {
            // does it contain any letter(s)?
            if (preg_match("/(?ims)([A-Za-zµ]+)/",$text_actual_amount,$actual_amount_unit)) {
                $actual_amount_unit=repairUnit($actual_amount_unit[1]);
                if ($actual_amount_unit==$molecule["amount_unit"]) {
                    // same unit like the nominal amount
                    $chemical_storage["actual_amount"]=$number_actual_amount; // P
                }
                else {
                    // different unit, try to calculate value
                    $act_factor=getUnitFactor($actual_amount_unit);
                    $factor=getUnitFactor($molecule["amount_unit"]);
                    if ($act_factor && $factor) { // skip if anything not found
                        if ($act_factor < $factor) { // number_actual_amount in mg (0.001), amount in g (1)
                            $chemical_storage["actual_amount"]=$number_actual_amount;
                            $molecule["amount"]*=$factor/$act_factor; // => 1000 mg
                            $molecule["amount_unit"]=$actual_amount_unit;
                        }
                        else {
                            $chemical_storage["actual_amount"]=$number_actual_amount*$act_factor/$factor;
                        }
                    }
                    //~ var_dump($molecule);
                    //~ var_dump($chemical_storage);
                    //~ die($actual_amount_unit."X".$molecule["amount_unit"]."Y".$act_factor."Z".$factor);
                }
            }
            else { // %
                $chemical_storage["actual_amount"]=$molecule["amount"]*$number_actual_amount/100; // P
            }
        }

        // purity concentration/ solvent
        if (preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ\/%]+)(\sin\s)?(.*)?/",getValue("chemical_storage_conc",$cells),$concentration_data)) { // Q
            $chemical_storage["chemical_storage_conc"]=fixNumber($concentration_data[1]);
            $chemical_storage["chemical_storage_conc_unit"]=repairUnit($concentration_data[2]);
            // solvent, empty if not provided
            $chemical_storage["chemical_storage_solvent"]=$concentration_data[4];

            $chemical_storage_density_20=getValue("chemical_storage_density_20",$cells);
            if (!empty($chemical_storage_density_20)) {
                $chemical_storage["chemical_storage_density_20"]=fixNumber($chemical_storage_density_20); // R
            }
        }
    }

    // // Khoi: for import tab-separated text file import of storage locations
    // elseif ($for_storage) {
    //     $storage["storage_name"] = rtrim(getValue("storage_name",$cells));
    //     $storage["storage_barcode"] = rtrim(getValue("storage_barcode",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
    //     // echo "<br>lib_import, line 269 ".$storage["storage_name"];
    // }
    // // var_dump($storage);
    // // Khoi: for import text-separated text file import of user
    // elseif ($for_person) {
    //     $person["title"] = rtrim(getValue("title",$cells));
    //     $person["last_name"] = rtrim(getValue("last_name",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
    //     $person["first_name"] = rtrim(getValue("first_name",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
    //     $person["username"] = rtrim(getValue("username",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
    //     $person["email"] = rtrim(getValue("email",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
    //     $person["person_barcode"] = rtrim(getValue("person_barcode",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
    //     $person["new_password"] = rtrim(getValue("new_password",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
    //     $person["new_password_repeat"] = $person["new_password"];    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
    //     $person["new_permission"] = rtrim(getValue("permissions",$cells));    // Khoi: rtrim() to get rid of whitespace or \n or \t at the end of the string. This happens if this is the last column in the text file
    //     if($person["new_permission"] == 'admin') {
    //         $person["permissions_general"] = array(_admin);    // 
    //         $person["permissions_chemical"] = array(_storage_modify, _chemical_create, _chemical_edit, _chemical_edit_own, _chemical_borrow, _chemical_inventarise, _chemical_delete, _chemical_read);    
    //         $person["permissions_lab_journal"] = array(_lj_read);    // allow limited search in lab journal on default
    //     }
    //     elseif (empty($person["new_permission"]) || $person["new_permission"] == 'read') {
    //         $person["permissions_chemical"] = array(_chemical_read, _chemical_borrow);    // allow borrowing and searching chemicals on default
    //         $person["permissions_lab_journal"] = array(_lj_read);    // allow limited search in lab journal on default
    //     }
    // }

    // set_time_limit(180);
    set_time_limit(90);

    // find cas
    echo "<br>".ucfirst(s("line"))." ".($_REQUEST["skip_lines"]+$a).": ".$molecule["cas_nr"]."<br>";
    flush();
    ob_flush();
    $chemical_storage["molecule_id"]=getMoleculeFromOwnDB($molecule["cas_nr"]);
    
    // Khoi: This only affect some institution with the customization turned ON.
    if (in_array($g_settings["customization"], array("baylor",), true)) {
        //Khoi: find chemical_storage_id to edit own chemicals
        $chemical_storage["chemical_storage_id"] = getChemicalStorageFromOwnDB($chemical_storage["chemical_storage_barcode"]);   
        // var_dump($chemical_storage["chemical_storage_id"]);
    }
    
    $supplier_offer["molecule_id"]=$chemical_storage["molecule_id"];
    if ((!$for_storage && !$for_person)  // Khoi: check if it is not importing storage location or person
        && $chemical_storage["molecule_id"]==""   // neues Molekül
        && !$chemical_storage["chemical_storage_id"]) {   // Khoi: check if the chemical_storage does not exist by chemical_storage_barcode
        if (!empty($molecule["cas_nr"])) {
            // print warning if CAS No is not valid
            if (!isCAS($molecule["cas_nr"])) {
                echo "Warning: ".$molecule["cas_nr"]." is not valid<br>";
            }
            // echo "Molecule value is ".var_dump($molecule);
            getAddInfo($molecule); // Daten von suppliern holen, kann dauern
        }
        extendMoleculeNames($molecule);
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
        performEdit("molecule",-1,$db);
        $chemical_storage["molecule_id"]=$_REQUEST["molecule_id"];
        $supplier_offer["molecule_id"]=$_REQUEST["molecule_id"];
        $_REQUEST=$oldReq;
    }

    /*-------------------------------------------------------------------------------------------------------
    Checking if the molecule has structure. 
    If not, use existing mol file inside /var/lib/mysql/missing_mol_files/
    */
    list($result)=mysql_select_array(array(
        "table" => "molecule",
        "filter" => "molecule.molecule_id=".fixNull($chemical_storage["molecule_id"]),
        "dbs" => -1,
        "flags" => QUERY_CUSTOM,
    ));
    // echo 'Khoi: before<br>';
    // var_dump($result);

    // Check if molecule has structure by checking smiles string mol_file_blob if they are empty
    if (!empty($molecule["cas_nr"] && (empty($result['smiles']) || empty($result["mol_file_blob"])))) {
        // Update mol_file_blob
        $molfileExist = getMolFileFromLocal($molecule["cas_nr"], $chemical_storage["molecule_id"]);

        if ($molfileExist) {
            // Check the molecule info result again
            list($result)=mysql_select_array(array(
                "table" => "molecule",
                "filter" => "molecule.molecule_id=".fixNull($chemical_storage["molecule_id"]),
                "dbs" => -1,
                "flags" => QUERY_CUSTOM,
            ));
            // echo 'Khoi: after<br>';
            // var_dump($result);

            /* Set these info to 1 (true) to have OE fix them: 
            "molfile_blob" : structure
            emp_formula : molecular formular
            mw : molecular weight 
            fingerprint : structure fingerprint 
            rdb: degree of unsaturation */
            $_REQUEST["molfile_blob"] = 1;
            $_REQUEST["emp_formula"] = 1;
            $_REQUEST["mw"] = 1;
            $_REQUEST["rdb"] = 1;
            $_REQUEST["smiles"] = 1;
            $_REQUEST["fingerprint"] = 1;
        
            $sql_parts=array();
            if (!empty($result["molfile_blob"])) {
                $molecule_search=readMolfile($result["molfile_blob"],array() ); // for  fingerprinting and serialisation
            }
            elseif (!empty($result["emp_formula"])) {
                $molecule_search=readSumFormula($result["emp_formula"],array() );
            }

            // Set up sql command:
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
            if ($_REQUEST["fingerprint"]) {
                $sql_parts[]="molecule_serialized=".fixBlob(serializeMolecule($molecule_search));
                $sql_parts[]=getFingerprintSQL($molecule_search,true);
            }
            // update sql database 
            if (count($sql_parts)) {
                $sql="UPDATE molecule SET ".join(",",$sql_parts)." WHERE molecule_id=".fixNull($result["molecule_id"]).";";
                mysqli_query($db,$sql) or die($sql.mysqli_error($db));
            }
        }
    }
    /*-------------------------------------------------------------------------------------------------------
    End Checking if the molecule has structure. 
    */
    
    // if ($for_supplier_offer) {
    //     $oldReq=$_REQUEST;
    //     $_REQUEST=array_merge($_REQUEST,$supplier_offer);
    //     performEdit("supplier_offer",-1,$db);
    //     $_REQUEST=$oldReq;
    // }
    if ($for_chemical_storage) {
        // make mass out of moles, fix for Ligon
        if (getUnitType($molecule["amount_unit"])=="n") {
            // get mw
            list($result)=mysql_select_array(array(
                "table" => "molecule",
                "filter" => "molecule.molecule_id=".fixNull($chemical_storage["molecule_id"]),
                "dbs" => -1,
                "flags" => QUERY_CUSTOM,
            ));

            // get suitable mass unit
            $mass_unit=getComparableUnit($molecule["amount_unit"],"m",$molecule["amount"]*$result["mw"]);

            // calc mass
            $molecule["amount"]=get_mass_from_amount($mass_unit,$molecule["amount"],$molecule["amount_unit"],$result["mw"]);
            $molecule["amount_unit"]=$mass_unit;
        }

        // do we have to create chemical_storage?
        if ($molecule["storage_name"]!="") {
            $chemical_storage["storage_id"]=createStorageIfNotExist($molecule["storage_name"]);
        }
        else {
            $chemical_storage["storage_id"]="";
        }

        $oldReq=$_REQUEST;
        
        // Khoi: specifically designed for Baylor University, 
        // For row with barcode that match some in current database,
        // this function will ignore this row of data and do nothing
        if ($chemical_storage["chemical_storage_id"]) {
            // return None;  
        }
        else {
            $chemical_storage=array_merge(
                $chemical_storage,
                array_key_filter(
                    $molecule,
                    array(
                        "supplier",
                        "price",
                        "price_currency",
                        "comment_cheminstor",
                        "purity",
                        "amount",
                        "amount_unit",
                        "add_multiple",
                        "order_date",
                        "open_date",
                    )
                )
            );
    
            $_REQUEST=array_merge($_REQUEST,$chemical_storage);
            
            // var_dump($_REQUEST("chemical_storage_barcode"));
            // var_dump($_REQUEST);
            // var_dump($chemical_storage_id);
            // var_dump($result);
            // var_dump($chemical_storage["chemical_storage_barcode"]);
            // performEdit("chemical_storage",-1,$db, $paramHash);
            performEdit("chemical_storage",-1,$db);
        }

        $_REQUEST=$oldReq;
    }
    // // Khoi: for import text-separated text file import of storage locations and user
    // elseif ($for_storage) {
    //     // Create storage if it does not exist, 
    //     // return $storage["storage_id"] of the newly created storage or of the existing one
    //     if ($storage["storage_name"] != "") {
    //         $storage["storage_id"] = createStorageIfNotExist($storage["storage_name"]);
    //     }
    //     else {
    //         $storage["storage_id"] = "";
    //     }

    //     $oldReq=$_REQUEST;
    //     $_REQUEST=array_merge($_REQUEST,$storage);
    //     // var_dump($_REQUEST);
    //     $paramHash = array( "ignoreLock" => true,);
    //     performEdit("storage",-1,$db, $paramHash);
    //     $_REQUEST=$oldReq;
    // }
    // elseif ($for_person) {
    //     // Khoi: create person if not exist
    //     // echo "<br> lib_import, line 425<br>";
    //     if ($person["username"] != "") {
    //         $person["person_id"] = createPersonIfNotExist($person["username"]);
    //     }
    //     else {
    //         $person["person_id"] = "";
    //     }

    //     $oldReq=$_REQUEST;
    //     $_REQUEST=array_merge($_REQUEST,$person);
    //     // var_dump($_REQUEST);
    //     $paramHash = array( "ignoreLock" => true,);
    //     performEdit("person",-1,$db, $paramHash);
    //     $_REQUEST=$oldReq;
    // }
}


/*
Khoi: function to determine the delimiter of a text file:
ref: https://stackoverflow.com/a/23608388/6596203
$delimiter = getFileDelimiter('abc.csv'); //Check 2 lines to determine the delimiter
$delimiter = getFileDelimiter('abc.csv', 5); //Check 5 lines to determine the delimiter
*/
function getFileDelimiter($file, $checkLines = 10, $startLine = 0){
    $file = new SplFileObject($file);
    $delimiters = array(
      ",",
      "\t",
      ";",
      "|",
      ":"
    );
    $results = array();
    $i = $startLine;
     while($file->valid() && $i <= ($checkLines + $startLine)){
        $line = $file->fgets();
        foreach ($delimiters as $delimiter){
            $regExp = '/['.$delimiter.']/';
            $fields = preg_split($regExp, $line);
            if(count($fields) > 1){
                if(!empty($results[$delimiter])){
                    $results[$delimiter]++;
                } else {
                    $results[$delimiter] = 1;
                }   
            }
        }
       $i++;
    }
    $results = array_keys($results, max($results));
    return $results[0];
}

?>
