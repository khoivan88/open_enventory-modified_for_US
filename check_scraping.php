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

$molfile=<<<END
Cc1ccccc1P(c2ccccc2C)c3ccccc3C
open enventory Sat, 24.05.2008 21:30:53

 22 24  0  0  0  0  0  0  0  0  0 V2000
    0.0208   -0.6417    0.0000 P   0  0  0  0  0  0  0  0  0  0  0  0
    0.0208    0.6167    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.0458   -1.2500    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.0375   -1.2500    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.0458    1.1958    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.0458   -2.4042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -2.0750   -0.6417    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.0375    1.1958    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.0583   -0.6417    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.0375   -2.4042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.0583    0.6292    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.0333   -2.9833    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -2.0750    0.6458    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.0458    2.3958    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -3.1042   -1.2042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.0583   -3.0000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.0375    2.3958    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.1000   -1.2042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -2.0750   -3.0000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.0208    3.0000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.1000   -2.4042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -3.1042   -2.4042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  2  1  1  0  0  0  0
  3  1  1  0  0  0  0
  4  1  1  0  0  0  0
  5  2  2  0  0  0  0
  6  3  1  0  0  0  0
  7  4  2  0  0  0  0
  8  2  1  0  0  0  0
  9  3  2  0  0  0  0
 10  4  1  0  0  0  0
 11  5  1  0  0  0  0
 12  6  1  0  0  0  0
 13  7  1  0  0  0  0
 14  5  1  0  0  0  0
 15  7  1  0  0  0  0
 16  6  2  0  0  0  0
 17  8  2  0  0  0  0
 18  9  1  0  0  0  0
 19 10  2  0  0  0  0
 20 17  1  0  0  0  0
 21 16  1  0  0  0  0
 22 19  1  0  0  0  0
 22 15  2  0  0  0  0
 21 18  2  0  0  0  0
 20 14  2  0  0  0  0
M  END

END;

$molfile_cas="6163-58-2";
$molfile_words=array(
	array("phosphin","tri","o-tolyl"),
	array("phosphin","tri","2-methylphenyl"),
);

$text="aceton"; // contains
//~ $cas="1552-12-1";

$emp_form="C2H3NaO2"; // VWR, ABCR, Acros, Sial, NIST, Chemfinder, Merck, NICHT: Alfa-Aesar, Strem
$emp_form_words=array(
	array("natrium","acetat"),
	array("sodium","acetat"),
	array("sodium","acetic"),
);

function checkResults($results,$or_words,$field="name") {
	if (!is_array($or_words)) {
		$or_words=array(array($or_words));
	}
	for ($c=0;$c<count($or_words);$c++) { // OR
		$word=$or_words[$c];
		if (is_array($results)) for ($a=0;$a<count($results);$a++) {
			for ($b=0;$b<count($word);$b++) { // AND
				if (strpos(strtolower($results[$a][$field]),$word[$b])===FALSE) {
					continue 2; // next result
				}
				return $a;
			}
		}
	}
	return false;
}

function showSupplierMessage($supplierCode,$success) {
	global $suppliers;
	echo getSupplierLogo($suppliers[$supplierCode])." <span style=\"color:".($success!==FALSE?"green\">".s("success"):"red;font-size:24pt\">".s("fail"))."</span><br>";
	flush();
}

function checkStrSearch() {
	global $strSearch,$molfile,$molfile_cas,$suppliers;
	
	echo "<h2>".s( "structure_search")."</h2>";
	
	$oldStrSearch=$strSearch;
	
	for ($a=0;$a<count($oldStrSearch);$a++) {
		$supplierCode=$oldStrSearch[$a];
		if (!$suppliers[$supplierCode]) {
			continue;
		}
		// Struktursuchen
		$strSearch=array($supplierCode);
		$result=strSearch($molfile);
		//~ print_r($result);
		$bestHit=$suppliers[ $supplierCode ]["getBestHit"]($result["hitlist"]);
		//~ var_dump($result["hitlist"][$bestHit]["cas_nr"]);
		showSupplierMessage($supplierCode,$result["hitlist"][$bestHit]["cas_nr"]==$molfile_cas);
	}
	
	$strSearch=$oldStrSearch;
}

function performSingleCheck($type) {
	global $steps,$suppliers,$text,$molfile_cas,$molfile_words,$emp_form,$emp_form_words;
	echo "<h3>".s($type)."</h3>";
	for ($a=0;$a<count($steps);$a++) {
		$supplierCode=$steps[$a];
		
		if ((is_array($suppliers[$supplierCode]["excludeFields"]) && in_array($type,$suppliers[$supplierCode]["excludeFields"]))
			|| (is_array($suppliers[$supplierCode]["excludeTests"]) && in_array($type,$suppliers[$supplierCode]["excludeTests"]))) {
			continue;
		}
		
		switch ($type) {
		case "molecule_name":
		// Text search
			$results=$suppliers[$supplierCode]["getHitlist"]($text,$type);
			$success=checkResults($results,$text);
		break;
		case "cas_nr":
		// CAS search
			$checkarray=$suppliers[$supplierCode]["testCas"];
			if (!$checkarray) {
				$checkarray=array($molfile_cas => $molfile_words);
			}
			
			foreach ($checkarray as $cas => $words) {
				$results=$suppliers[$supplierCode]["getHitlist"]($cas,$type,"ex");
				$success=checkResults($results,$words);
				
				if ($success!==FALSE && !empty($results[$success]["supplierCode"]) && !empty($results[$success]["catNo"])) {
					// check data acquisition
					$data=$suppliers[ $results[$success]["supplierCode"] ]["getInfo"]($results[$success]["catNo"]);
					echo s("detail_page").": ";
					showSupplierMessage($supplierCode,$data["cas_nr"]==$cas);
				}
			}
		break;
		case "emp_formula":
		// emp form search
			$checkarray=$suppliers[$supplierCode]["testEmpFormula"];
			if (!$checkarray) {
				$checkarray=array($emp_form => $emp_form_words);
			}
			
			foreach ($checkarray as $form => $words) {
				$results=$suppliers[$supplierCode]["getHitlist"]($form,$type,"ex");
				$success=checkResults($results,$words);
			}
		break;
		}
		//~ print_r($results);
		showSupplierMessage($supplierCode,$success);
		//~ die();
	}
}

function checkListSearch() {
	echo "<h2>".s( "list_search")."</h2>";
	// balance load on suppliers a bit
	set_time_limit(180);
	performSingleCheck("molecule_name");
	set_time_limit(180);
	performSingleCheck("cas_nr");
	set_time_limit(180);
	performSingleCheck("emp_formula");
}

pageHeader();

require_once "lib_supplier_scraping.php";

//~ print_r($suppliers["Acros"]["getInfo"]("KFPNKUMQNBQNKQNQNMPOLZ"));
//~ print_r($suppliers["NIST"]["getInfo"]("7440-05-3"));
//~ print_r($suppliers["NIST"]["getInfo"]("108-88-3"));
//~ print_r($suppliers["NIST"]["getInfo"]("C110543"));
//~ print_r($suppliers["Strem"]["getInfo"]("1443"));
//~ print_r($suppliers["Strem"]["getInfo"]("1446"));
//~ print_r($suppliers["VWR"]["getInfo"]("1421410"));
//~ print_r($suppliers["Sial"]["getInfo"]("FLUKA/89682"));

checkStrSearch();
checkListSearch();
echo <<<END
<link href="style.css.php" rel="stylesheet" type="text/css">
</head>
<body>
END;


// Listen

echo "<h2>".s("checks_complete").<<<END
</h2>
</body>
</html>
END;

completeDoc();

?>