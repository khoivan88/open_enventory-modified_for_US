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
	if (isset($unit)) {
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
}


function getValue($key,$cells) { // value of the column mapped to $key, or the fixed value given in the form, or ""
    $idx=$_REQUEST["col_".$key]??"";
    if (!isEmptyStr($idx)) {
        return $cells[$idx]??"";
    }
    return $_REQUEST["fixed_".$key]??"";
}


/*
Khoi (2026-09): ONE function for one row of an import file. It replaces importEachEntry() (import.php),
importAndEditEachEntry() (import_edit.php) and importNoEditEachEntry() (import_only.php), which were
three ~450-line copies of the same code that had to be fixed three times for every change.

$mode:
  "add"      import.php:      every row creates a new container / supplier offer / storage / user (Felix's original behaviour)
  "edit"     import_edit.php: a container whose barcode already exists is UPDATED (storage, compartment, comment, supplier ...);
                              rows may identify a container by barcode alone; otherwise the row is added
  "add_only" import_only.php: containers only; when the "baylor" customization is active a row whose barcode already
                              exists is skipped, otherwise every row is added (as before)
Returns true when something was written, false when the row was skipped.
*/
function importRow($a, $row, $cols_molecule, $for_chemical_storage, $for_supplier_offer=false, $for_storage=false, $for_person=false, $mode="add") {
    global $db, $g_settings;
    $trimchars=" \t\n\r\0\x0B\"";
    $line_no=intval($_REQUEST["skip_lines"]??0)+$a;

    $molecule=array();
    $chemical_storage=array();
    $supplier_offer=array();
    $storage=array();
    $person=array();

    $cells=$row;
    for ($b=0;$b<count($cells);$b++) {
        $cells[$b]=trim(autodecode($cells[$b]??""),$trimchars);
    }
    if (trim(implode("",$cells))==="") { // blank line (typically the last one of a text file)
        return false;
    }

    if ($for_chemical_storage) {
        // rtrim(): if the barcode is the last column of a text file it may carry a trailing \n or \t
        $chemical_storage["chemical_storage_barcode"]=rtrim(getValue("chemical_storage_barcode",$cells));
    }
    $has_barcode=!empty($chemical_storage["chemical_storage_barcode"]??"");

    // a molecule row needs a name or a CAS No. - or, when editing, a barcode; storages and users need neither
    if (!$for_storage && !$for_person
        && empty($cells[$_REQUEST["col_molecule_name"]??""]??null)
        && empty($cells[$_REQUEST["col_cas_nr"]??""]??null)
        && !($mode=="edit" && $has_barcode)) {
        echo "<br>".ucfirst(s("line"))." ".$line_no.": missing molecule name and CAS No.".($mode=="edit"?" and barcode":"").", skipped<br>";
        return false;
    }

    // --- molecule columns -------------------------------------------------------------------------
    $molecule["molecule_names_array"]=array();
    foreach ($cols_molecule as $col_molecule) {
        switch ($col_molecule) {
        case "molecule_name":
        case "alt_molecule_name":
        case "alt_molecule_name2":
        case "alt_molecule_name3":
            $molecule["molecule_names_array"][]=getValue($col_molecule,$cells);
        break;
        case "mp_high":
            list($molecule["mp_low"],$molecule["mp_high"])=getRange(getValue($col_molecule,$cells));
        break;
        case "bp_high":
            list($molecule["bp_low"],$molecule["bp_high"],$press)=getRange(getValue($col_molecule,$cells));
            if (isEmptyStr($molecule["bp_high"])) {
                // do nothing
            }
            elseif (trim($press??"")!="") {
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
        case "default_safety_sheet_url":
        case "alt_default_safety_sheet_url": // Felix 2023-06-21: MSDS via URL
            $val=getValue($col_molecule,$cells);
            if (isUrl($val)) {
                $molecule[$col_molecule]="-".$val;
            }
        break;
        default:
            $molecule[$col_molecule]=getValue($col_molecule,$cells);
        }
    }

    // --- container columns ------------------------------------------------------------------------
    if ($for_chemical_storage) {
        $molecule["storage_name"]=getValue("storage_name",$cells);
        $molecule["order_date"]=getSQLFormatDate(getTimestampFromDate(getValue("order_date",$cells)));
        $molecule["open_date"]=getSQLFormatDate(getTimestampFromDate(getValue("open_date",$cells)));
        $chemical_storage["order_date"]=$molecule["order_date"];
        $chemical_storage["open_date"]=$molecule["open_date"];
        $chemical_storage["migrate_id_cheminstor"]=getValue("migrate_id_cheminstor",$cells);
        $chemical_storage["comment_cheminstor"]=getValue("comment_cheminstor",$cells);
        $chemical_storage["compartment"]=getValue("compartment",$cells);
        $chemical_storage["description"]=getValue("description",$cells);
        $chemical_storage["cat_no"]=getValue("cat_no",$cells);
        $chemical_storage["lot_no"]=getValue("lot_no",$cells);
        foreach (array("safety_sheet_url","alt_safety_sheet_url") as $key) { // Felix 2023-06-21: MSDS via URL
            $val=getValue($key,$cells);
            if (isUrl($val)) {
                $chemical_storage[$key]="-".$val;
            }
        }
        $molecule["supplier"]=getValue("supplier",$cells);
        $molecule["price"]=getNumber(getValue("price",$cells));
        $molecule["price_currency"]=getValue("price_currency",$cells);
    }

    // --- amount, incl. "3 x 500 ml" (de Mendoza-Fix) ---------------------------------------------
    $amount=str_replace(array("(", ")", ),"",getValue("amount",$cells));
    $amount_data=array();
    if (preg_match("/(?ims)([\d\.\,]+)\s*[x\*]\s*(.*)/",$amount,$amount_data)) {
        $molecule["add_multiple"]=$amount_data[1];
        $amount=$amount_data[2];
    } else {
        $molecule["add_multiple"]=ifempty(getNumber(getValue("add_multiple",$cells)),1);
        if ($molecule["add_multiple"]>10) { // probably an error
            $molecule["add_multiple"]=1;
        }
    }
    preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ]+)/",$amount,$amount_data);
    $molecule["amount"]=fixNumber($amount_data[1]??null);
    $molecule["amount_unit"]=repairUnit($amount_data[2]??"");

    // tmd
    $tmd_data=array();
    preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ]+)/",getValue("tmd",$cells),$tmd_data);
    $molecule["tmd"]=fixNumber($tmd_data[1]??null);
    $molecule["tmd_unit"]=repairUnit($tmd_data[2]??"");

    $molecule["migrate_id_mol"]=getValue("migrate_id_mol",$cells);

    // --- target-specific columns ------------------------------------------------------------------
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
        // actual amount: same unit, other unit or percent
        $text_actual_amount=getValue("actual_amount",$cells);
        $number_actual_amount=getNumber($text_actual_amount);
        if ($number_actual_amount==="") {
            $chemical_storage["actual_amount"]="";
        }
        else {
            $actual_amount_unit=array();
            if (preg_match("/(?ims)([A-Za-zµ]+)/",$text_actual_amount,$actual_amount_unit)) { // contains a unit
                $actual_amount_unit=repairUnit($actual_amount_unit[1]);
                if ($actual_amount_unit==$molecule["amount_unit"]) {
                    $chemical_storage["actual_amount"]=$number_actual_amount;
                }
                else {
                    $act_factor=getUnitFactor($actual_amount_unit);
                    $factor=getUnitFactor($molecule["amount_unit"]);
                    if ($act_factor && $factor) { // skip if anything not found
                        if ($act_factor < $factor) { // actual in mg (0.001), nominal in g (1) => express both in mg
                            $chemical_storage["actual_amount"]=$number_actual_amount;
                            $molecule["amount"]*=$factor/$act_factor;
                            $molecule["amount_unit"]=$actual_amount_unit;
                        }
                        else {
                            $chemical_storage["actual_amount"]=$number_actual_amount*$act_factor/$factor;
                        }
                    }
                }
            }
            else { // percent
                $chemical_storage["actual_amount"]=$molecule["amount"]*$number_actual_amount/100;
            }
        }

        // purity / concentration, optionally "in <solvent>"
        $concentration_data=array();
        if (preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ\/%]+)(\sin\s)?(.*)?/",getValue("chemical_storage_conc",$cells),$concentration_data)) {
            $chemical_storage["chemical_storage_conc"]=fixNumber($concentration_data[1]);
            $chemical_storage["chemical_storage_conc_unit"]=repairUnit($concentration_data[2]);
            $chemical_storage["chemical_storage_solvent"]=$concentration_data[4]??"";
            $chemical_storage_density_20=getValue("chemical_storage_density_20",$cells);
            if (!empty($chemical_storage_density_20)) {
                $chemical_storage["chemical_storage_density_20"]=fixNumber($chemical_storage_density_20);
            }
        }
    }
    elseif ($for_storage) {
        $storage["storage_name"]=rtrim(getValue("storage_name",$cells));
        $storage["storage_barcode"]=rtrim(getValue("storage_barcode",$cells));
    }
    elseif ($for_person) {
        foreach (array("title","last_name","first_name","username","email","person_barcode","new_password") as $key) {
            $person[$key]=rtrim(getValue($key,$cells));
        }
        $person["new_password_repeat"]=$person["new_password"];
        $person["new_permission"]=rtrim(getValue("permissions",$cells));
        if ($person["new_permission"]=='admin') {
            $person["permissions_general"]=array(_admin);
            $person["permissions_chemical"]=array(_storage_modify, _chemical_create, _chemical_edit, _chemical_edit_own, _chemical_borrow, _chemical_inventarise, _chemical_delete, _chemical_read);
            $person["permissions_lab_journal"]=array(_lj_read); // limited lab journal search by default
        }
        elseif (empty($person["new_permission"]) || $person["new_permission"]=='read') {
            $person["permissions_chemical"]=array(_chemical_read, _chemical_borrow); // search and borrow by default
            $person["permissions_lab_journal"]=array(_lj_read);
        }
    }

    set_time_limit(90);
    echo "<br>".ucfirst(s("line"))." ".$line_no.": ".($molecule["cas_nr"]??"")."<br>";
    flush();
    if (ob_get_level()) {
        ob_flush();
    }

    // --- existing molecule / container? -----------------------------------------------------------
    $chemical_storage["molecule_id"]=getMoleculeFromOwnDB($molecule["cas_nr"]??"");
    $chemical_storage["chemical_storage_id"]="";
    $check_barcode=($mode=="edit") || ($mode=="add_only" && in_array($g_settings["customization"]??"", array("baylor"), true));
    if ($for_chemical_storage && $check_barcode && $has_barcode) { // (an empty barcode must never match a container)
        $chemical_storage["chemical_storage_id"]=getChemicalStorageFromOwnDB($chemical_storage["chemical_storage_barcode"]);
    }
    $supplier_offer["molecule_id"]=$chemical_storage["molecule_id"];

    // --- new molecule -----------------------------------------------------------------------------
    if (!$for_storage && !$for_person && $chemical_storage["molecule_id"]=="" && empty($chemical_storage["chemical_storage_id"])) {
        if (!empty($molecule["cas_nr"])) {
            if (!isCAS($molecule["cas_nr"])) {
                echo "Warning: ".$molecule["cas_nr"]." is not valid<br>";
            }
            getAddInfo($molecule); // query suppliers, may take a while
        }
        extendMoleculeNames($molecule); // the user's name is the first name (2020-08-18)
        $oldReq=$_REQUEST;
        $_REQUEST=array_merge($_REQUEST,$molecule);
        $list_int_name="molecule_property";
        $_REQUEST[$list_int_name]=array();
        if (is_array($molecule[$list_int_name]??null)) foreach ($molecule[$list_int_name] as $UID => $property) {
            $_REQUEST[$list_int_name][]=$UID;
            $_REQUEST["desired_action_".$list_int_name."_".$UID]="add";
            foreach (array("class","source","conditions","value_low","value_high","unit") as $key) {
                $_REQUEST[$list_int_name."_".$UID."_".$key]=$property[$key]??"";
            }
        }
        performEdit("molecule",-1,$db);
        $chemical_storage["molecule_id"]=$_REQUEST["molecule_id"];
        $supplier_offer["molecule_id"]=$_REQUEST["molecule_id"];
        $_REQUEST=$oldReq;
    }

    // structure from a local molfile collection when the molecule has none (MIT)
    if (!$for_storage && !$for_person && !empty($molecule["cas_nr"]) && !empty($chemical_storage["molecule_id"])) {
        importFixStructureFromLocal($molecule["cas_nr"],$chemical_storage["molecule_id"]);
    }

    // --- write ------------------------------------------------------------------------------------
    $oldReq=$_REQUEST;
    $written=false;
    if ($for_supplier_offer) {
        $_REQUEST=array_merge($_REQUEST,$supplier_offer);
        performEdit("supplier_offer",-1,$db);
        $written=true;
    }
    elseif ($for_chemical_storage) {
        // moles -> mass (fix for Ligon)
        if (getUnitType($molecule["amount_unit"])=="n") {
            list($result)=array_pad(mysql_select_array(array(
                "table" => "molecule",
                "filter" => "molecule.molecule_id=".fixNull($chemical_storage["molecule_id"]),
                "dbs" => -1,
                "flags" => QUERY_CUSTOM,
            )),1,null);
            $mass_unit=getComparableUnit($molecule["amount_unit"],"m",$molecule["amount"]*($result["mw"]??0));
            $molecule["amount"]=get_mass_from_amount($mass_unit,$molecule["amount"],$molecule["amount_unit"],$result["mw"]??0);
            $molecule["amount_unit"]=$mass_unit;
        }
        $chemical_storage["storage_id"]=($molecule["storage_name"]!="") ? createStorageIfNotExist($molecule["storage_name"]) : "";

        if (!empty($chemical_storage["chemical_storage_id"])) {
            if ($mode=="edit") {
                // update an existing container: keep its dates, description, cat/lot No., molecule and actual amount
                list($existing)=array_pad(mysql_select_array(array(
                    "table" => "chemical_storage",
                    "filter" => "chemical_storage.chemical_storage_id=".fixNull($chemical_storage["chemical_storage_id"]),
                    "dbs" => -1,
                    "limit" => 1,
                    "flags" => QUERY_CUSTOM,
                )),1,array());
                unset($chemical_storage["order_date"], $chemical_storage["open_date"], $chemical_storage["description"],
                      $chemical_storage["cat_no"], $chemical_storage["lot_no"], $chemical_storage["molecule_id"], $chemical_storage["actual_amount"]);
                $_REQUEST["desired_action"]="update";
                $_REQUEST=array_merge($_REQUEST,$existing,$chemical_storage);
                performEdit("chemical_storage",-1,$db,array("ignoreLock" => true)); // without ignoreLock the edit does not proceed
                echo "Container ".fixHtmlOut($chemical_storage["chemical_storage_barcode"])." updated<br>";
                $written=true;
            }
            else { // add_only: never touch an existing container
                echo "Container ".fixHtmlOut($chemical_storage["chemical_storage_barcode"])." exists already, skipped<br>";
            }
        }
        else {
            $chemical_storage=array_merge($chemical_storage, array_key_filter($molecule, array(
                "supplier", "price", "price_currency", "comment_cheminstor", "purity", "amount", "amount_unit", "add_multiple", "order_date", "open_date",
            )));
            $_REQUEST=array_merge($_REQUEST,$chemical_storage);
            performEdit("chemical_storage",-1,$db);
            $written=true;
        }
    }
    elseif ($for_storage) {
        $storage["storage_id"]=($storage["storage_name"]!="") ? createStorageIfNotExist($storage["storage_name"]) : "";
        $_REQUEST=array_merge($_REQUEST,$storage);
        performEdit("storage",-1,$db,array("ignoreLock" => true));
        $written=true;
    }
    elseif ($for_person) {
        $person["person_id"]=($person["username"]!="") ? createPersonIfNotExist($person["username"]) : "";
        $_REQUEST=array_merge($_REQUEST,$person);
        performEdit("person",-1,$db,array("ignoreLock" => true));
        $written=true;
    }
    $_REQUEST=$oldReq;
    return $written;
}

/*
Khoi (MIT): a molecule imported without structure gets its molfile from /var/lib/mysql/missing_mol_files/<CAS>.mol
(see getMolFileFromLocal()) and the derived properties (image, formula, MW, SMILES, fingerprint) are recalculated.
Does nothing when the molecule already has a structure or no local molfile exists.
*/
function importFixStructureFromLocal($cas_nr,$molecule_id) {
    global $db;
    $select=array(
        "table" => "molecule",
        "filter" => "molecule.molecule_id=".fixNull($molecule_id),
        "dbs" => -1,
        "flags" => QUERY_CUSTOM,
    );
    list($result)=array_pad(mysql_select_array($select),1,null);
    if (empty($result) || (!empty($result["smiles"]) && !empty($result["molfile_blob"]))) {
        return false;
    }
    if (!getMolFileFromLocal($cas_nr,$molecule_id)) {
        return false;
    }
    list($result)=array_pad(mysql_select_array($select),1,null);
    $molecule_search=array();
    if (!empty($result["molfile_blob"])) {
        $molecule_search=readMolfile($result["molfile_blob"],array()); // for fingerprinting and serialisation
    }
    elseif (!empty($result["emp_formula"])) {
        $molecule_search=readSumFormula($result["emp_formula"],array());
    }
    $sql_parts=array();
    if (!empty($result["molfile_blob"])) {
        list($gif,$svg)=getMoleculeGif($molecule_search,gif_x,gif_y,0,1,true,array("png","svg"));
        $sql_parts[]="gif_file=".fixBlob($gif);
        $sql_parts[]="svg_file=".fixBlob($svg);
        $sql_parts[]="smiles_stereo=".fixStrSQL($molecule_search["smiles_stereo"]??"");
        $sql_parts[]="smiles=".fixStrSQL($molecule_search["smiles"]??"");
    }
    $sql_parts[]="emp_formula=".fixStr($molecule_search["emp_formula_string"]??"");
    $sql_parts[]="emp_formula_sort=".fixStr($molecule_search["emp_formula_string_sort"]??"");
    $sql_parts[]="mw=".fixNull($molecule_search["mw"]??null);
    $sql_parts[]="rdb=".fixStr($molecule_search["rdb"]??"");
    $sql_parts[]="molecule_serialized=".fixBlob(serializeMolecule($molecule_search));
    $sql_parts[]=getFingerprintSQL($molecule_search,true);
    $sql="UPDATE molecule SET ".join(",",$sql_parts)." WHERE molecule_id=".fixNull($result["molecule_id"]).";";
    mysqli_query($db,$sql) or die($sql.mysqli_error($db));
    return true;
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
