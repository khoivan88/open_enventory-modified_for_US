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

/*
//From Khoi: for asynchronous importing of text file (function importEachEntry)
require __DIR__ . '/vendor/autoload.php';
use Amp\Parallel\Worker\DefaultPool;
use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;
//using Spatie
use Spatie\Async\Pool;
*/

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
            // array("<h1>".s("import_tab_sep")."</h1>")
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

        $importedFile = $_REQUEST["import_file_upload"];
        // Khoi: get file delimiters based on content of the file
        $delimiter = getFileDelimiter($file=$importedFile, $chechkLines=10, $startLine=$_REQUEST["skip_lines"]);
        // var_dump("Import file delimiter is: $delimiter");  echo "<br>";
        
		// read file
		$zeilen=array();
		if ($handle=fopen($importedFile,"r")) {
            // number_lines_preview (simple html table)
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
            //~ var_dump($zeilen);die();

		$start = \microtime(true);

			$a = 0;
			foreach ($zeilen as $row) {
                importEachEntry($a, $row, $cols_molecule, $for_chemical_storage, $for_supplier_offer, $for_storage, $for_person);
                $a++;
            };

   //         //Using parallel-functions of amphp
   //         try {
   //         	echo "Work till here in import.txt!\n";
   //         	$response = wait(parallelMap($zeilen, 
   //         		function($a, $row, $cols_molecule, $for_chemical_storage, $for_supplier_offer) {
   //         			require "lib_import.php";
			// 			return importEachEntry($a, $row, $cols_molecule, $for_chemical_storage, $for_supplier_offer);
   //         		}));
   //         } catch (Exception $exception) {
			//     var_dump ($exception->getReasons());
			//     // continue;
			// }
			
			// Using multiple pool worker of amphp
			// for ($i = 0; $i < 1; $i++) {
			//     $pool = new DefaultPool();
			//     $promises = parallelMap(\range(1, 2), function($a, $row, $cols_molecule, $for_chemical_storage, $for_supplier_offer) {
			// 					            			require "lib_import.php";
			// 											importEachEntry($a, $row, $cols_molecule, $for_chemical_storage, $for_supplier_offer);
			// 						    			}, $pool);
			//     $result = wait($promises);
			// }

   //         //Testing Spatie
   //         $pool = Pool::create();
			// // Pool::isSupported();
			// echo ($pool->isSupported());
			// foreach ($zeilen as $row) {
			//     $pool->add(function($a, $row, $cols_molecule, $for_chemical_storage, $for_supplier_offer) {
   //         			require "lib_import.php";
			// 			return importEachEntry($a, $row, $cols_molecule, $for_chemical_storage, $for_supplier_offer);
			//     // })->then(function ($output) {
			//     //     // Handle success
			//     })->catch(function (Throwable $exception) {
			//         var_dump ($exception->getReasons());
			//     });
			// }
			// $pool->wait();
			
		print '<br>Finished importing.<br> Took ' . (\microtime(true) - $start) . ' seconds.' . \PHP_EOL;
            
			// for ($a=0;$a<count($zeilen);$a++) {
			// 	$molecule=array();
			// 	$chemical_storage=array();
			// 	$supplier_offer=array();

			// 	$cells=$zeilen[$a];
			// 	// echo var_dump($cells);
			// 	for ($b=0;$b<count($cells);$b++) {
			// 		$cells[$b]=trim(autodecode($cells[$b]),$trimchars);
			// 	}
			// 	if (empty($cells[$_REQUEST["col_molecule_name"]]) && empty($cells[$_REQUEST["col_cas_nr"]])) {
			// 		echo "Missing molecule name and CAS number.";
			// 	    continue;
			// 	}

			// 	$molecule["molecule_names_array"]=array();
			// 	foreach ($cols_molecule as $col_molecule) {
			// 		switch ($col_molecule) {
			// 		case "molecule_name":
			// 		case "alt_molecule_name":
			// 		case "alt_molecule_name2":
			// 		case "alt_molecule_name3":
			// 			$molecule["molecule_names_array"][]=getValue($col_molecule,$cells);
			// 		break;
			// 		case "mp_high":
			// 			list($molecule["mp_low"],$molecule["mp_high"])=getRange(getValue($col_molecule,$cells));
			// 		break;
			// 		case "bp_high":
			// 			list($molecule["bp_low"],$molecule["bp_high"],$press)=getRange(getValue($col_molecule,$cells));
			// 			if (isEmptyStr($molecule["bp_high"])) {
			// 				// do nothing
			// 			}
			// 			elseif (trim($press)!="") {
			// 				$molecule["bp_press"]=getNumber($press);
			// 				if (strpos($press,"mm")!==FALSE) {
			// 					$molecule["press_unit"]="torr";
			// 				}
			// 			}
			// 			else {
			// 				$molecule["bp_press"]="1";
			// 				$molecule["press_unit"]="bar";
			// 			}
			// 		break;
			// 		default:
			// 			$molecule[$col_molecule]=getValue($col_molecule,$cells);
			// 		}
			// 	}

			// 	if ($for_chemical_storage) {
			// 		$molecule["storage_name"]=getValue("storage_name",$cells);
			// 		$molecule["order_date"]=getSQLFormatDate(getTimestampFromDate(getValue("order_date",$cells)));
			// 		$molecule["open_date"]=getSQLFormatDate(getTimestampFromDate(getValue("open_date",$cells)));
			// 		$chemical_storage["migrate_id_cheminstor"]=getValue("migrate_id_cheminstor",$cells);
			// 		$chemical_storage["comment_cheminstor"]=getValue("comment_cheminstor",$cells);
			// 		$chemical_storage["compartment"]=getValue("compartment",$cells);
			// 		$chemical_storage["description"]=getValue("description",$cells);
			// 		$chemical_storage["cat_no"]=getValue("cat_no",$cells);
			// 		$chemical_storage["lot_no"]=getValue("lot_no",$cells);
			// 		$chemical_storage["chemical_storage_barcode"]=getValue("chemical_storage_barcode",$cells);
			// 		$molecule["supplier"]=getValue("supplier",$cells);
			// 		$molecule["price"]=getNumber(getValue("price",$cells));
			// 		$molecule["price_currency"]=getValue("price_currency",$cells);
			// 	}

			// 	$amount=str_replace(array("(", ")", ),"",getValue("amount",$cells)); // G
			// 	if (preg_match("/(?ims)([\d\.\,]+)\s*[x\*]\s*(.*)/",$amount,$amount_data)) { // de Mendoza-Fix
			// 		$molecule["add_multiple"]=$amount_data[1];
			// 		$amount=$amount_data[2];
			// 	} else {
			// 		$molecule["add_multiple"]=ifempty(getNumber(getValue("add_multiple",$cells)),1); // J
			// 		if ($molecule["add_multiple"]>10) { // probably an error
			// 			$molecule["add_multiple"]=1;
			// 		}
			// 	}
			// 	preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ]+)/",$amount,$amount_data);
			// 	$molecule["amount"]=fixNumber($amount_data[1]);
			// 	$amount_data[2]=repairUnit($amount_data[2]);
			// 	$molecule["amount_unit"]=$amount_data[2];

			// 	// tmd
			// 	$tmd=getValue("tmd",$cells); // G
			// 	preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ]+)/",$tmd,$tmd_data);
			// 	$molecule["tmd"]=fixNumber($tmd_data[1]);
			// 	$tmd_data[2]=repairUnit($tmd_data[2]);
			// 	$molecule["tmd_unit"]=$tmd_data[2];

			// 	$molecule["migrate_id_mol"]=getValue("migrate_id_mol",$cells); // K

			// 	if ($for_supplier_offer) {
			// 		$supplier_offer["so_package_amount"]=$molecule["amount"];
			// 		if ($molecule["add_multiple"]) {
			// 			$supplier_offer["so_package_amount"]*=$molecule["add_multiple"];
			// 		}
			// 		$supplier_offer["so_package_amount_unit"]=$molecule["amount_unit"];
			// 		$supplier_offer["supplier"]=getValue("supplier",$cells);
			// 		$supplier_offer["so_price"]=getNumber(getValue("so_price",$cells));
			// 		$supplier_offer["so_price_currency"]=getValue("so_price_currency",$cells);
			// 		$supplier_offer["catNo"]=getValue("catNo",$cells);
			// 		$supplier_offer["beautifulCatNo"]=getValue("beautifulCatNo",$cells);
			// 	}
			// 	elseif ($for_chemical_storage) {
			// 		$text_actual_amount=getValue("actual_amount",$cells);
			// 		$number_actual_amount=getNumber($text_actual_amount);
			// 		if ($number_actual_amount==="") {
			// 			$chemical_storage["actual_amount"]="";
			// 		}
			// 		else {
			// 			// does it contain any letter(s)?
			// 			if (preg_match("/(?ims)([A-Za-zµ]+)/",$text_actual_amount,$actual_amount_unit)) {
			// 				$actual_amount_unit=repairUnit($actual_amount_unit[1]);
			// 				if ($actual_amount_unit==$molecule["amount_unit"]) {
			// 					// same unit like the nominal amount
			// 					$chemical_storage["actual_amount"]=$number_actual_amount; // P
			// 				}
			// 				else {
			// 					// different unit, try to calculate value
			// 					$act_factor=getUnitFactor($actual_amount_unit);
			// 					$factor=getUnitFactor($molecule["amount_unit"]);
			// 					if ($act_factor && $factor) { // skip if anything not found
			// 						if ($act_factor < $factor) { // number_actual_amount in mg (0.001), amount in g (1)
			// 							$chemical_storage["actual_amount"]=$number_actual_amount;
			// 							$molecule["amount"]*=$factor/$act_factor; // => 1000 mg
			// 							$molecule["amount_unit"]=$actual_amount_unit;
			// 						}
			// 						else {
			// 							$chemical_storage["actual_amount"]=$number_actual_amount*$act_factor/$factor;
			// 						}
			// 					}
			// 					//~ var_dump($molecule);
			// 					//~ var_dump($chemical_storage);
			// 					//~ die($actual_amount_unit."X".$molecule["amount_unit"]."Y".$act_factor."Z".$factor);
			// 				}
			// 			}
			// 			else { // %
			// 				$chemical_storage["actual_amount"]=$molecule["amount"]*$number_actual_amount/100; // P
			// 			}
			// 		}

			// 		// purity concentration/ solvent
			// 		if (preg_match("/(?ims)([\d\.\,]+)\s*([a-zA-Zµ\/%]+)(\sin\s)?(.*)?/",getValue("chemical_storage_conc",$cells),$concentration_data)) { // Q
			// 			$chemical_storage["chemical_storage_conc"]=fixNumber($concentration_data[1]);
			// 			$chemical_storage["chemical_storage_conc_unit"]=repairUnit($concentration_data[2]);
			// 			// solvent, empty if not provided
			// 			$chemical_storage["chemical_storage_solvent"]=$concentration_data[4];

			// 			$chemical_storage_density_20=getValue("chemical_storage_density_20",$cells);
			// 			if (!empty($chemical_storage_density_20)) {
			// 				$chemical_storage["chemical_storage_density_20"]=fixNumber($chemical_storage_density_20); // R
			// 			}
			// 		}
			// 	}

			// 	set_time_limit(180);

			// 	// find cas
			// 	echo "<br>".ucfirst(s("line"))." ".($_REQUEST["skip_lines"]+$a).": ".$molecule["cas_nr"]."<br>";
			// 	flush();
			// 	ob_flush();
			// 	$chemical_storage["molecule_id"]=getMoleculeFromOwnDB($molecule["cas_nr"]);
			// 	$supplier_offer["molecule_id"]=$chemical_storage["molecule_id"];
			// 	if ($chemical_storage["molecule_id"]=="") { // neues Molekül
			// 		if (!empty($molecule["cas_nr"])) {
			// 			// print warning if CAS No is not valid
			// 			if (!isCAS($molecule["cas_nr"])) {
			// 				echo "Warning: ".$molecule["cas_nr"]." is not valid<br>";
			// 			}
			// 			getAddInfo($molecule); // Daten von suppliern holen, kann dauern
			// 		}
			// 		extendMoleculeNames($molecule);
			// 		$oldReq=$_REQUEST;
			// 		$_REQUEST=array_merge($_REQUEST,$molecule);
			// 		$list_int_name="molecule_property";
			// 		$_REQUEST[$list_int_name]=array();
			// 		if (count($molecule[$list_int_name])) foreach ($molecule[$list_int_name] as $UID => $property) {
			// 			$_REQUEST[$list_int_name][]=$UID;
			// 			$_REQUEST["desired_action_".$list_int_name."_".$UID]="add";
			// 			$_REQUEST[$list_int_name."_".$UID."_class"]=$property["class"];
			// 			$_REQUEST[$list_int_name."_".$UID."_source"]=$property["source"];
			// 			$_REQUEST[$list_int_name."_".$UID."_conditions"]=$property["conditions"];
			// 			$_REQUEST[$list_int_name."_".$UID."_value_low"]=$property["value_low"];
			// 			$_REQUEST[$list_int_name."_".$UID."_value_high"]=$property["value_high"];
			// 			$_REQUEST[$list_int_name."_".$UID."_unit"]=$property["unit"];
			// 		}

			// 		performEdit("molecule",-1,$db);
			// 		$chemical_storage["molecule_id"]=$_REQUEST["molecule_id"];
			// 		$supplier_offer["molecule_id"]=$_REQUEST["molecule_id"];
			// 		$_REQUEST=$oldReq;
			// 	}

			// 	if ($for_supplier_offer) {
			// 		$oldReq=$_REQUEST;
			// 		$_REQUEST=array_merge($_REQUEST,$supplier_offer);
			// 		performEdit("supplier_offer",-1,$db);
			// 		$_REQUEST=$oldReq;
			// 	}
			// 	elseif ($for_chemical_storage) {
			// 		// make mass out of moles, fix for Ligon
			// 		if (getUnitType($molecule["amount_unit"])=="n") {
			// 			// get mw
			// 			list($result)=mysql_select_array(array(
			// 				"table" => "molecule",
			// 				"filter" => "molecule.molecule_id=".fixNull($chemical_storage["molecule_id"]),
			// 				"dbs" => -1,
			// 				"flags" => QUERY_CUSTOM,
			// 			));

			// 			// get suitable mass unit
			// 			$mass_unit=getComparableUnit($molecule["amount_unit"],"m",$molecule["amount"]*$result["mw"]);

			// 			// calc mass
			// 			$molecule["amount"]=get_mass_from_amount($mass_unit,$molecule["amount"],$molecule["amount_unit"],$result["mw"]);
			// 			$molecule["amount_unit"]=$mass_unit;
			// 		}

			// 		// do we have to create chemical_storage?
			// 		if ($molecule["storage_name"]!="") {
			// 			$chemical_storage["storage_id"]=createStorageIfNotExist($molecule["storage_name"]);
			// 		}
			// 		else {
			// 			$chemical_storage["storage_id"]="";
			// 		}
			// 		$chemical_storage=array_merge(
			// 			$chemical_storage,
			// 			array_key_filter(
			// 				$molecule,
			// 				array(
			// 					"supplier",
			// 					"price",
			// 					"price_currency",
			// 					"comment_cheminstor",
			// 					"purity",
			// 					"amount",
			// 					"amount_unit",
			// 					"add_multiple"
			// 				)
			// 			)
			// 		);
			// 		// do we have to create storage first?
			// 		$oldReq=$_REQUEST;
			// 		$_REQUEST=array_merge($_REQUEST,$chemical_storage);
			// 		performEdit("chemical_storage",-1,$db);
			// 		$_REQUEST=$oldReq;
			// 	}
			// }
			
			// clean up
			@unlink($_REQUEST["import_file_upload"]);
		} else {
			echo s("file_not_found");
		}
		
	break;
	case "load_file":
		// file there?
		if (count($_FILES["import_file_upload"]) && $_FILES["import_file_upload"]["error"]==0) {
            
            $tmpdir=oe_get_temp_dir();
			$tmpname=oe_tempnam($tmpdir,"oe");
			@unlink($tmpname);
			rename($_FILES["import_file_upload"]["tmp_name"],$tmpname);
			@chmod($tmpname,0755);
            
            // Khoi: get file info (such as extension) to parse info correct (e.g. csv vs tsv))
            $delimiter = getFileDelimiter($file=$tmpname, $chechkLines=10, $startLine=$_REQUEST["skip_lines"]);
            // var_dump("Import file delimiter is: $delimiter");  echo "<br>";

            // open file, skip_lines
            if ($handle=fopen($tmpname,"r")) {
                // number_lines_preview (simple html table)
                $line=-1;
                $preview=array();
                $line_sizes=array();
                $max_cells=0;
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
                //~ var_dump($preview);die();
                
                if ($max_cells==0 or !$delimiter) {
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
                //~ var_dump($preview);die();
                echo s("number_lines").": ".count($line_sizes)."<br>".getTable($preview,$cell_texts);
            }
		}
	break;
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
		<ul>
			<li>
				To import info into Open Enventory, you can use tab-separated or comma-separated (csv) text file.
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
				Click <a href="lib/import_chemicals_from_text_instruction.pdf" target="_blank">here</a>
				 to see an instruction on how to import chemical container.
			</li>
			<br>
			<li>
				You can download the <b><i>storage</i></b> or <b><i>user</i></b> templates below to prepare your import file. 
				This function can be used to add new or update existing locations or users. 
				The instruction to fill out the form is included in each file. See the instruction on chemical containers importing above for more details on importing guide.
			</li>
			<br>
			<li>
				To <b>update</b> info on <b><i>storage locations</i></b> or <b><i>users</i></b>, it is recommended that 
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
