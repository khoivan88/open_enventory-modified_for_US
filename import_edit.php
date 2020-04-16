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

// import neu
require_once "lib_global_funcs.php";
require_once "lib_simple_forms.php";
require_once "lib_formatting.php";
require_once "lib_db_manip.php";

pageHeader();

require_once "lib_supplier_scraping.php";
require_once "lib_import.php";
include "SimpleXLSX.php";    // Khoi: for handling Excel file.
include "SimpleXLS.php";    // Khoi: for handling Excel file.

// admins only
if ($permissions & _admin) {
	echo stylesheet.
style.
getFixedStyleBlock().
_style.
loadJS(array("client_cache.js","controls.js","forms.js","searchpk.js","subitemlist_helper.js","subitemlist_get.js","subitemlist.js","edit.js"),"lib/").
script."
readOnly=false;
editMode=false;
activateSearch(false);
"._script."
</head>
<body>";

	showCommFrame();

	// show n lines, fileupload

	// Name1 COL A-Z
	// Name2 COL A-Z
	// CAS COL A-Z
	// etc. autodetect

	$cols_molecule=array(
		"molecule_name",
		"alt_molecule_name",
		"alt_molecule_name2",
		"alt_molecule_name3",
		"cas_nr",
		"emp_formula",
		"migrate_id_mol",
		
		"safety_sym_ghs",
		"safety_h",
		"safety_p",
		"safety_text",
		"safety_wgk",
		"safety_danger",
		"safety_cancer",
		"safety_mutagen",
		"safety_reprod",
		"molecule_btm_list",
		"molecule_sprengg_list",
		
		"safety_sym",
		"safety_r",
		"safety_s",
		
		"density_20",
		"n_20",
		"mp_high",
		"bp_high",
	);
	$cols_chemical_storage=array(
		"amount", // incl unit
		"actual_amount", // in %
		"tmd",
		"chemical_storage_conc", // incl unit and perhaps solvent
		"chemical_storage_density_20",
		"storage_name",
		"compartment",
		"chemical_storage_barcode",
		"description",
		"supplier",
		"cat_no",
		"lot_no",
		"price",
		"price_currency",
		"order_date",
		"open_date",
		"add_multiple",
		"migrate_id_cheminstor",
		"comment_cheminstor",
	);
	$cols_supplier_offer=array(
		"amount", // incl unit
		"so_purity",
		"supplier",
		"beautifulCatNo",
		"catNo",
		"so_price",
		"so_price_currency",
		"so_date",
		"comment_supplier_offer",
	);
	$cols_storage=array(
		"storage_name",
		"storage_barcode",
	);
	$cols_person=array(
		"title",
		"last_name",
		"first_name",
		"username",
		"email",
		"person_barcode",
		"permissions",
		"new_password",
	);

	$autodetect_re=array(
		"molecule_name" => array("(?is)(?:phenyl|brom|chlor|methyl|ethyl|propyl|acetat|acid|oxid|amino)" => 0.3),
		"cas_nr" => array("(?is)^\d+\-\d{2}\-\d\$" => 0.9),
		"emp_formula" => array("^(?:(?:[A-Z][a-z]?\d*)|\(|\)){3,}\$" => 0.9),
		"amount" => array("(?is)^[\d\.\,]+\s*(?:mg|g|kg|ml|l|L|mL|ML|G)\$" => 0.9),
		"storage_name" => array("(?is)(?:fridge|freezer|cabinet|schrank|lager|truhe)" => 0.2),
		"supplier" => array("(?is)(?:merck|aldrich|sigma|abcr|alfa|acros|vwr|merck)" => 0.2),
		// "order_date" => array("(?is)^\d\d?\.\d\d?.\d{2,4}\$" => 0.2),
		// "open_date" => array("(?is)^\d\d?\.\d\d?.\d{2,4}\$" => 0.2),
		// Khoi: for matching American date and dash (-) or slash(/) as well
		"order_date" => array("(?is)^\d\d?[.\/-]\d\d?[.\/-]\d{2,4}\$" => 0.2),
		"open_date" => array("(?is)^\d\d?[.\/-]\d\d?[.\/-]\d{2,4}\$" => 0.2),
	);
	$autodetect_re["so_package_amount"]=$autodetect_re["amount"];

	echo getHelperTop().
		"<div id=\"browsenav\">".
		getAlignTable(
			array("<table class=\"noborder\"><tbody><tr><td><a href=\"Javascript:void submitForm(&quot;main&quot;);\" class=\"imgButtonSm\"><img src=\"lib/save_sm.png\" border=\"0\"".getTooltip("save_changes")."></a></td></tr></tbody></table>"), 
			array("<h1>".s("import")."</h1>")
		).
		"</div>
		<div id=\"browsemain\">
		<form name=\"main\" id=\"main\" method=\"POST\" enctype=\"multipart/form-data\"><span id=\"temp\" style=\"display:none\"></span>".
		getHiddenSubmit();

	if (empty($_REQUEST["table"])) {
		$_REQUEST["table"]="chemical_storage";
	}
	
	$for_chemical_storage=($_REQUEST["table"]=="chemical_storage");
	$for_supplier_offer=($_REQUEST["table"]=="supplier_offer");

	// Khoi: added import tab-separated text file for storages and their barcodes
	$for_storage = ($_REQUEST["table"]=="storage");
	$for_person = ($_REQUEST["table"] == "person");

	$trimchars=" \t\n\r\0\x0B\"";

	switch ($_REQUEST["desired_action"]) {
        case "import":
            // show message to wait
            echo s("import_wait")."<br>";
            // var_dump($_REQUEST);

            // Get the uploaded file:
            $importedFile = $_REQUEST["import_file_upload"];
            
            // Get the uploaded file extension:
            $extension = $_REQUEST['file_extension'];
            echo("Uploaded file extension is: $extension <br>");
            
            // https://stackoverflow.com/a/53962466/6596203
            if ($extension == 'xlsx') {
                $uploadedFile = SimpleXLSX::parse($importedFile);
            } elseif ($extension == 'xls') {
                $uploadedFile = SimpleXLS::parse($importedFile);
            } else {
                $handle=fopen($importedFile,"r");
            }
            
            // Khoi explain: result array
            $zeilen=array();
            
            // read file
            if ($uploadedFile) {
                foreach ($uploadedFile->rows() as $line => $cells) {
                    if ($line >= $_REQUEST["skip_lines"]) {
                        $zeilen[]=$cells;
                    }
                }

            }
            elseif ($handle) {
                // number_lines_preview (simple html table)
            
                // Khoi: get file delimiters based on content of the file
                $delimiter = getFileDelimiter($file=$importedFile, $chechkLines=10, $startLine=$_REQUEST["skip_lines"]);
                // print_r("Import file delimiter is: '$delimiter'");  echo "<br>";

                $line = -1;
                while (!feof($handle)) {
                    $buffer = fgets($handle, 16384);
                    $line++;
                    if ($line >= $_REQUEST["skip_lines"]) {
                        // Khoi: using str_getcsv() because it is superior to explode()
                        // Ref: https://stackoverflow.com/questions/15444358/what-is-the-advantage-of-using-str-getcsv
                        // if ($delimiter) {    //Not needed anymore because it has been checked in 'case "load_file"'
                            $zeilen[]=str_getcsv($buffer, $delimiter);
                        // }
                    }
                }
                fclose($handle);
            }
            // echo '<pre>'; var_dump($zeilen); echo '</pre>'; die();

            // Check if result ($zeilen) exists and import line by line from result
            if ($zeilen) {
                $start = \microtime(true);

                $a = 0;
                foreach ($zeilen as $row) {
                    importAndEditEachEntry($a, $row, $cols_molecule, $for_chemical_storage, $for_supplier_offer, $for_storage, $for_person);
                    $a++;
                };

                print '<br>Finished importing.<br> Took ' . (\microtime(true) - $start) . ' seconds.' . \PHP_EOL;
                            
                // clean up
                @unlink($_REQUEST["import_file_upload"]);
            } else {
                echo s("file_not_found");
            }
        break;
        
        // Khoi explain: after clicking on the green checkmark to Upload File
        case "load_file":
            // file there?
            if (count($_FILES["import_file_upload"]) && $_FILES["import_file_upload"]["error"]==0) {
                $tmpdir=oe_get_temp_dir();
                $tmpname=oe_tempnam($tmpdir,"oe");
                @unlink($tmpname);
                rename($_FILES["import_file_upload"]["tmp_name"],$tmpname);
                @chmod($tmpname,0755);
                
                // Khoi: Get the uploaded file extension:
                $extension = pathinfo($_FILES['import_file_upload']['name'], PATHINFO_EXTENSION);
                echo("Uploaded file extension is: $extension <br>");
                        
                // https://stackoverflow.com/a/53962466/6596203
                if ($extension == 'xlsx') {
                    $uploadedFile = SimpleXLSX::parse($tmpname);
                } elseif ($extension == 'xls') {
                    $uploadedFile = SimpleXLS::parse($tmpname);
                } else {
                    $handle=fopen($tmpname,"r");
                }
                
                if ($uploadedFile || $handle) {
                    $line=-1;
                    $preview=array();
                    $line_sizes=array();
                    $max_cells=0;

                    if ($uploadedFile) {
                        /* Ref for excel file handling: https://github.com/shuchkin/simplexlsx */
                        // echo $uploadedFile->toHTML();    // for debugging

                        foreach ($uploadedFile->rows() as $line => $cells) {
                            $size=count($cells);
                            $max_cells=max($max_cells,$size);
                            $line_sizes[]=$size;
                            if ($line>=$_REQUEST["skip_lines"] && count($preview) < $_REQUEST["number_lines_preview"]) {
                                for ($b=0;$b<count($cells);$b++) {
                                    $cells[$b]=trim(autodecode($cells[$b]),$trimchars);
                                }
                                $preview[]=$cells;
                                // break;
                            }
                            // go to the end to check for mismatching line sizes
                        }
                    }
                    elseif ($handle){
                        // Khoi: get file info (such as extension) to parse info correct (e.g. csv vs tsv))
                        $delimiter = getFileDelimiter($file=$tmpname, $chechkLines=10, $startLine=$_REQUEST["skip_lines"]);
                        // echo "Import file delimiter is: ".stripslashes($delimiter)."<br>";

                        while (!feof($handle)) {
                            $buffer=fgets($handle,16384);
                            $line++;

                            // Khoi: using str_getcsv() because it is superior to explode()
                            // Ref: https://stackoverflow.com/questions/15444358/what-is-the-advantage-of-using-str-getcsv
                            if ($delimiter) {
                                $cells=str_getcsv($buffer, $delimiter);
                            }

                            $size=count($cells);
                            $max_cells=max($max_cells,$size);
                            $line_sizes[]=$size;
                            if ($line>=$_REQUEST["skip_lines"] && count($preview)<$_REQUEST["number_lines_preview"]) {
                                for ($b=0;$b<count($cells);$b++) {
                                    $cells[$b]=trim(autodecode($cells[$b]),$trimchars);
                                }
                                $preview[]=$cells;
                            }
                            // go to the end to check for mismatching line sizes
                        }
                        fclose ($handle);
                        // ~ var_dump($preview);die();
                    }
                    else {
                        echo 'xlsx error: '.$uploadedFile->error();
                    }
                    
                    if ($max_cells==0) {
                        // die(s("must_be_tab_sep"));
                        die(s("must_be_txt"));
                    }
                    
                    $error_lines=array();
                    for ($a=0;$a<count($line_sizes);$a++) { // leave heading alone
                        if ($line_sizes[$a]!=$max_cells) {
                            $error_lines[]=array($a+1,$line_sizes[$a]);
                        }
                    }
                    if (count($error_lines)) {
                        echo s("error_line_size1").getTable($error_lines,array(s("line"),s("number_columns"))).s("error_line_size2").$max_cells.s("error_line_size3");
                    }
                    
                    // autodetect columns
                    $guessed_cols=array();
                    foreach ($autodetect_re as $col => $re_data) { // categories
                        foreach ($re_data as $re => $min_hits) {
                            $col_hits=array();
                            $col_lines_with_content=array();
                            for ($col_no=0;$col_no<$max_cells;$col_no++) { // in the preview column by column
                                for ($line=0;$line<count($preview);$line++) { // line by line
                                    if (!isEmptyStr($preview[$line][$col_no])) {
                                        if (preg_match("/".$re."/",$preview[$line][$col_no])) {
                                            $col_hits[$col_no]++;
                                        }
                                        $col_lines_with_content[$col_no]++;
                                    }
                                }
                            }
                            if (count($col_hits)) {
                                for ($col_no=0;$col_no<$max_cells;$col_no++) {
                                    if ($col_lines_with_content[$col_no]>0) {
                                        $col_hits[$col_no]/=$col_lines_with_content[$col_no];
                                    }
                                }
                                $max_hits=max($col_hits);
                                if ($max_hits>=$min_hits) {
                                    $guessed_cols[$col]=array_search($max_hits,$col_hits);
                                    break;
                                }
                            }
                        }
                    }
                    
                    $default_values=array(
                        // "price_currency" => "EUR",
                        // "so_price_currency" => "EUR",
                        // "so_date" => getGermanDate(),
                        
                        // Khoi, modify for US system
                        "price_currency" => "USD",
                        "so_price_currency" => "USD",
                        "so_date" => getAmericanDate(),
                    );
                    
                    // prepare select prototype
                    $cell_texts=array();
                    for ($a=0;$a<$max_cells;$a++) {
                        $cell_texts[]=s("column")." ".numToLett($a+1);
                    }
                    $select_proto=array(
                        "item" => "select", 
                        "allowNone" => true, 
                        "int_names" => range(0,$max_cells-1),
                        "texts" => $cell_texts,
                    );
                    
                    $fieldsArray=array(
                        array("item" => "hidden", "int_name" => "table", "value" => $_REQUEST["table"], ), 
                        array("item" => "hidden", "int_name" => "desired_action", "value" => "import", ), 
                        array("item" => "hidden", "int_name" => "import_file_upload", "value" => $tmpname, ), 
                        array("item" => "hidden", "int_name" => "file_extension", "value" => $extension, ),   // Khoi: pass on the extension variable
                        array("item" => "text", "text" => "<table><tbody><tr><td>", ),
                        "tableStart",
                        // headings
                        array("item" => "input", "int_name" => "skip_lines", "size" => 10, "maxlength" => 6, "value" => $_REQUEST["skip_lines"], ), 
                    );
                    
                    // selects for categories or fixed value (like EUR)
                    if ($for_supplier_offer) {
                        $cols=array_merge($cols_molecule,$cols_supplier_offer);
                    }
                    elseif ($for_chemical_storage) {
                        $cols=array_merge($cols_molecule,$cols_chemical_storage);
                    }
                    elseif ($for_storage) { 
                        $cols = $cols_storage;
                    }
                    elseif ($for_person) {
                        $cols = $cols_person;
                    }
                    
                    $idx=0;
                    foreach ($cols as $col) {
                        if ($idx%10==0) {
                            if ($idx>0) {
                                $fieldsArray[]="tableEnd";
                                $fieldsArray[]=array("item" => "text", "text" => "</td><td>", );
                                $fieldsArray[]="tableStart";
                            }
                            $fieldsArray[]=array("item" => "text", "text" => "<tr><td><b>".s("property")."</b></td><td><b>".s("column")." | ".s("fixed_value")."</b></td></tr>", );
                        }
                        $select_proto["text"]=s($col);
                        $select_proto["int_name"]="col_".$col;
                        $select_proto["value"]=$guessed_cols[$col];
                        $fieldsArray[]=$select_proto;
                        $fieldsArray[]=array("item" => "input", "int_name" => "fixed_".$col, "size" => 10, SPLITMODE => true, "value" => $default_values[$col], );
                        $idx++;
                    }
                    $fieldsArray[]="tableEnd";
                    $fieldsArray[]=array("item" => "text", "text" => "</td></tr></tbody></table>".s("missing_physical_data"), );
                    
                    echo getFormElements(
                        array(
                            READONLY => false, 
                            "noFieldSet" => true, 
                        ),
                        $fieldsArray
                    );
                    
                    // build table of sample data
                    // echo '<pre>'; var_dump($preview); echo '</pre>'; die();
                    echo s("number_lines").": ".count($line_sizes)."<br>".getTable($preview,$cell_texts);
                }
            }
        break;
        
        // Khoi explain: first load of Import page
        default:
            echo getFormElements(
                array(
                    READONLY => false, 
                    "noFieldSet" => true, 
                ),
                array(
                array("item" => "hidden", "int_name" => "desired_action", "value" => "load_file", ), 
                "tableStart",
                // array("item" => "select", "int_name" => "table", "int_names" => array("chemical_storage", "supplier_offer", ), ), 
                // Khoi: adding function to upload storage location and its barcode
                array("item" => "select", "int_name" => "table", "int_names" => array("chemical_storage", "storage", "person", "supplier_offer", ), ), 
                array("item" => "input", "int_name" => "import_file_upload", "type" => "file", ), 
                array("item" => "input", "int_name" => "number_lines_preview", "size" => 10, "maxlength" => 6, "value" => 10, ), 
                array("item" => "input", "int_name" => "skip_lines", "size" => 10, "maxlength" => 6, "value" => 1, ), 
                "tableEnd",
                )
            );
            echo <<<EOL
            <br>
            <h2>Instructions:</h2>
            <h3> WARNING: </h3>
            <p style="color:red">
            For chemical containers, it checks if the barcode exists in the current database,
            and not disposed. If <b>Yes</b>, it will edit the info of that container. 
            If <b>No</b> (or no barcode in the data entry), it will add the entry as a new container.
            </p>
            <ul>
                <li>
                To import info into Open Enventory, you can use tab-separated or comma-separated (csv) text files.
                Excel files (*.xls or *.xlsx) are also accepted.
                Options include: 
                        <ul style="list-style-type:none;">
                        <li><b>package</b> (chemical containers)</li>
                        <li><b>storage</b> (location)</li>
                        <li><b>user</b></li>
                        <li><b>commercial offer</b></li>
                        </ul> 			
                </li>
                <br>
                <li>
                    Click <a href="https://open-enventory.gitbook.io/user-guides/user-guides/chemical-inventory/chemicals/import-a-list-of-chemicals" target="_blank">here</a> 
                    to see an instruction on how to import chemical container function.
                </li>
                <br>
                <li>
                    You can download the <b><i>storage</i></b> or <b><i>user</i></b> 
                    templates below to prepare your import file. 
                    This function can be used to add new or update existing locations or users. 
                    The instruction to fill out the form is included in each file. 
                    See the instruction on chemical containers importing above for more details on importing guide.
                </li>
                <br>
                <li>
                    To <b>update</b> info on <b><i>storage locations</i></b> or <b><i>users</i></b>, 
                    it is recommended that 
                    you get the exact name of storages and users from:
                    <a href="list.php?table=storage&dbs=-1">Storages</a> or 
                    <a href="list.php?table=person&dbs=-1">Users</a>  Menus.
                </li>
            </ul>
            <head>
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <!-- Add icon library -->
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
                <style>
                .btn {
                background-color: DodgerBlue;
                border: none;
                color: white;
                padding: 6px 10px;
                cursor: pointer;
                font-size: 13px;
                text-decoration: none;
                }

                /* Darker background on mouse-over */
                .btn:hover {
                background-color: RoyalBlue;
                }
                </style>
            </head>
            <body>
                <p><a href="lib/storage_import_template.xlsx" download target="_blank" class="btn">
                    <i class="fa fa-download"></i> Download <b>Storage</b> import template
                </a></p>
                <p><a href="lib/user_import_template.xlsx" download target="_blank" class="btn">
                    <i class="fa fa-download"></i> Download <b>User</b> import template
                </a></p>
            </body>
EOL;
	}

	echo "</form>
</div>".
getHelperBottom().
"</body>
</html>";

	completeDoc();
}

?>
