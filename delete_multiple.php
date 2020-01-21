<?php
/*
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
Khoi: added function to be able to delete multiple chemical containers
*/

// import neu
require_once "lib_global_funcs.php";
require_once "lib_simple_forms.php";
require_once "lib_formatting.php";
require_once "lib_db_manip.php";

pageHeader();

require_once "lib_import.php";

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

		$cols_chemical_storage=array(
			"chemical_storage_barcode",
		);

		echo getHelperTop().
			"<div id=\"browsenav\">".
			getAlignTable(
				array("	<table class=\"noborder\">
							<tbody><tr><td>
								<a href=\"Javascript:void submitForm(&quot;main&quot;);\" onClick=\"return confirmDelete();\" class=\"imgButtonSm\">
									<img src=\"lib/save_sm.png\" border=\"0\"".getTooltip("save_changes").">
								</a>
							</td></tr></tbody>
						</table>"), 
				array("<h1>".s("delete_multiple")."</h1>")
			).
			"</div>
			<div id=\"browsemain\">
				<form name=\"main\" id=\"main\" method=\"POST\" enctype=\"multipart/form-data\" ;><span id=\"temp\" style=\"display:none\"></span>".
				getHiddenSubmit();

		if (empty($_REQUEST["table"])) {
			$_REQUEST["table"]="chemical_storage";
		}
		
		$for_chemical_storage=($_REQUEST["table"]=="chemical_storage");

		$trimchars=" \t\n\r\0\x0B\"";

	switch ($_REQUEST["desired_action"]) {
	case "delete_multiple":		
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

			$start = \microtime(true);

			$a = 1;
			foreach ($zeilen as $row) {
				$chemical_storage=array();
				
				$cells=$row;
				for ($b=0;$b<count($cells);$b++) {
					$cells[$b]=trim(autodecode($cells[$b]),$trimchars);
				}
				
				if ($for_chemical_storage) {
					$input["chemical_storage_barcode"] = rtrim(getValue("chemical_storage_barcode",$cells));    // Khoi: fixed so that if this column is the last column in the text file, it will not add whitespace or \n character
				}
				
				echo "<br>".ucfirst(s("line"))." ".($_REQUEST["skip_lines"]+$a).": ".$input["chemical_storage_barcode"]."<br>";
				flush();
				ob_flush();

				// Find chemical_storage_id
				$chemical_storage["chemical_storage_id"] = getChemicalStorageFromOwnDB($input["chemical_storage_barcode"]);   
				
				if (!$chemical_storage["chemical_storage_id"]) {   // Khoi: check if the chemical_storage does not exist by chemical_storage_barcode
					echo "Warning: ".$input["chemical_storage_barcode"]." does NOT exist in this database.<br>";
					$a++;
					continue;
				}
				
				// Set up variables and delete the chemical_storage
				if ($chemical_storage["chemical_storage_id"]) {	
					global $db;	
					$_REQUEST["db_id"] = -1; // only in your own database
					$db_id = intval($_REQUEST["db_id"]);
					$baseTable = $_REQUEST["table"];
					$_REQUEST["pk"] = $chemical_storage["chemical_storage_id"];
					// open db connection if necessary
					if ($db_id>0) {
						$dbObj=getForeignDbObjFromDBid($db_id);
						if (!$dbObj) {
							return array(FAILURE,s("error_no_access"));
						}
					}
					else {
						$dbObj=$db;
					}

					list($success,$message,$pks_added)=performDel($baseTable,$db_id,$dbObj);
					echo $message;

				}
				echo "<br>";
                $a++;
            };
			
			print '<br>Finished deleting.<br> Took ' . (\microtime(true) - $start) . ' seconds.' . \PHP_EOL;
            
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
				
				if ($max_cells==0) {
					die(s("must_be_tab_sep"));
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
					array("item" => "hidden", "int_name" => "desired_action", "value" => "delete_multiple", ), 
					array("item" => "hidden", "int_name" => "import_file_upload", "value" => $tmpname, ), 
					array("item" => "text", "text" => "<table><tbody><tr><td>", ),
					"tableStart",
					// headings
					array("item" => "input", "int_name" => "skip_lines", "size" => 10, "maxlength" => 6, "value" => $_REQUEST["skip_lines"], ), 
				);
				
				if ($for_chemical_storage) {
					$cols = $cols_chemical_storage;
				}
				
				$idx=0;
				foreach ($cols as $col) {
					if ($idx%10==0) {
						if ($idx>0) {
							$fieldsArray[]="tableEnd";
							$fieldsArray[]=array("item" => "text", "text" => "</td><td>", );
							$fieldsArray[]="tableStart";
						}
						$fieldsArray[]=array("item" => "text", "text" => "<tr><td><b>".s("property")."</b></td><td><b>".s("column")."</b></td></tr>", );
					}
					$select_proto["text"]=s($col);
					$select_proto["int_name"]="col_".$col;
					$select_proto["value"]=$guessed_cols[$col];
					$fieldsArray[]=$select_proto;
					$idx++;
				}
				$fieldsArray[]="tableEnd";
				$fieldsArray[]=array("item" => "text", "text" => "</td></tr></tbody></table>", );
				
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

		// Asking user to confirm before import for deletion
		echo "
			<script>
				function confirmDelete() {
					if (confirm('Do you really want to delete uploaded containers?')) {
						return true;
					} else {
						// if user picks no, return to delete_multiple.php page
						self.location = 'delete_multiple.php';
						return false;
					}
				}
			</script>
		";
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
			// Khoi: adding function to upload storage location and its barcode
			array("item" => "select", "int_name" => "table", "int_names" => array("chemical_storage", ), ), 
			array("item" => "input", "int_name" => "import_file_upload", "type" => "file", ), 
			array("item" => "input", "int_name" => "number_lines_preview", "size" => 10, "maxlength" => 6, "value" => 10, ), 
			array("item" => "input", "int_name" => "skip_lines", "size" => 10, "maxlength" => 6, "value" => 1, ), 
			"tableEnd",
		));
		echo <<<EOL
		<br>
		<h1 style='color:red'>WARNING: This function is used to delete multiple chemical containers, only use it if you are absolutely sure!</h1>
		<h2>Instructions:</h2>
		<ul>
			<li>
				To delete chemical container, please import a text file (.txt) with one column containing 
				chemical container barcodes on each row.
			</li>
			<br>
			<li>
				Example:
			</li>
			<p>
				<table id="to-be-deleted">
				<tr>
				<th>Container barcode to be deleted/disposed</th>
				</tr>
				<tr>
				<td>12345678</td>
				</tr>
				<tr>
				<td>24681357</td>
				</tr>
				<tr>
				<td>...</td>
				</tr>
			</table>
			</p>
			<br>
		</ul>
		<head>
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<style>
			/* For example table */
			table#to-be-deleted, th, td {
				border: 1px solid black;
				border-collapse: collapse;
				padding: 10px;
  				text-align: left;
			}

			table#to-be-deleted th {
				background-color: #dddddd;
			}
			</style>
		</head>
EOL;
	
	}

	echo "</form></div>";

	echo getHelperBottom();
	echo "	</body>
			</html>";

	completeDoc();
}

?>
