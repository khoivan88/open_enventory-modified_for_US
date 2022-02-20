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
require_once "lib_molfile.php";

$sub_time=0;
$sub_count=0;

/*--------------------------------------------------------------------------------------------------
/ Function: checkSubstructure
/
/ Purpose: applies comparison functions like similarity (=> fingerprints), substructure and identity (stereo and non-stereo) on molecules passed as data structures and compares with predefined results
/
/ Parameter:
/ 		& $needle : molecule data structure to be found/not found in haystack, depending on the desired results defined in $correct
/ 		& $haystack : haystack data structure to be matched to needle
/ 		$correct : desired results, tests will only be performed if a certain desired result is set
/
/ Return : no return value, direct output
/------------------------------------------------------------
/ History:
/ 2009-07-17 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function checkSubstructure(& $needle,& $haystack,$correct=array()) {
	global $sub_time,$sub_count;
	// FP
	if (isset($correct["FP"]) && getFPmatch($needle,$haystack)!=$correct["FP"]) {
		echo "Problem with ".$correct["identifier"]." on FP.<table><tbody><tr><td><pre>";
		print_r($haystack["fingerprints"]);
		echo "</pre></td><td><pre>";
		print_r($needle["fingerprints"]);
		echo "</pre></td><td><pre>";
		$test=array();
		for ($a=0;$a<count($needle["fingerprints"]);$a++) {
			$test[$a]=$haystack["fingerprints"][$a] & $needle["fingerprints"][$a];
		}
		print_r($test);
		echo "</pre></td></tr></tbody></table><br>";
	}
	
	// Sub
	if (isset($correct["substruct"])) {
		$start_time=microtime(true);
		if (getSubstMatch($needle,$haystack,array("mode" => "test", ))!=$correct["substruct"]) { // mode => test prevents structures to pass due to identical SMILES
			echo "Problem with ".$correct["identifier"]." on substruct.<br>";
		}
		$sub_time+=microtime(true)-$start_time;
		$sub_count++;
	}
	
	// SMILES
	if (isset($correct["smiles_stereo"]) && ($needle["smiles_stereo"]==$haystack["smiles_stereo"])!=$correct["smiles_stereo"]) {
		echo "Problem with ".$correct["identifier"]." on smiles_stereo (".$needle["smiles_stereo"]." vs. ".$haystack["smiles_stereo"].").<br>";
	}
	
	// SMILES non-stereo
	if (isset($correct["smiles"]) && ($needle["smiles"]==$haystack["smiles"])!=$correct["smiles"]) {
		echo "Problem with ".$correct["identifier"]." on smiles (".$needle["smiles"]." vs. ".$haystack["smiles"].").<br>";
	}
}

pageHeader();
echo stylesheet."</head>
<body>".s("check_for_errors").":<br>";

// Cp-Tests

$fe_metallacyc1=<<<END

  Marvin  10140814332D          

  7 11  0  0  0  0            999 V2000
   -1.7089    0.4955    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -2.3764    0.0106    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -2.1214   -0.7740    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.2964   -0.7740    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.0415    0.0106    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -2.6064   -1.4414    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.0281    1.1805    0.0000 Fe  0  3  0  0  0  0  0  0  0  0  0  0
  1  2  1  0  0  0  0
  1  5  1  0  0  0  0
  2  3  1  0  0  0  0
  3  4  1  0  0  0  0
  4  5  1  0  0  0  0
  3  6  1  0  0  0  0
  1  7  1  0  0  0  0
  5  7  1  0  0  0  0
  2  7  1  0  0  0  0
  7  4  1  0  0  0  0
  7  3  1  0  0  0  0
M  CHG  1   7   1
M  END

END;

$fe_regioiso1=<<<END

  Marvin  10140814352D          

  7  7  0  0  0  0            999 V2000
   -1.2080    0.6134    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.8755    0.1285    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.6205   -0.6561    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.7955   -0.6561    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.5406    0.1285    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.2080    1.4384    0.0000 Fe  0  3  0  0  0  0  0  0  0  0  0  0
   -2.1055   -1.3236    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  1  2  1  0  0  0  0
  1  5  1  0  0  0  0
  2  3  2  0  0  0  0
  3  4  1  0  0  0  0
  4  5  2  0  0  0  0
  1  6  1  0  0  0  0
  3  7  1  0  0  0  0
M  CHG  1   6   1
M  END

END;

$fe_regioiso2=<<<END

  Marvin  10140814352D          

  7  7  0  0  0  0            999 V2000
   -1.2080    0.6134    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.8755    0.1285    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.6205   -0.6561    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.7955   -0.6561    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.5406    0.1285    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.2080    1.4384    0.0000 Fe  0  3  0  0  0  0  0  0  0  0  0  0
   -2.6601    0.3834    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  1  2  1  0  0  0  0
  1  5  1  0  0  0  0
  2  3  2  0  0  0  0
  3  4  1  0  0  0  0
  4  5  2  0  0  0  0
  1  6  1  0  0  0  0
  2  7  1  0  0  0  0
M  CHG  1   6   1
M  END

END;

$mol_fe_metallacyc1=readMolfile($fe_metallacyc1,array("forStructureSearch" => true, ));
$mol_fe_regioiso1=readMolfile($fe_regioiso1,array("forStructureSearch" => true, ));
$mol_fe_regioiso2=readMolfile($fe_regioiso2,array("forStructureSearch" => true, ));
checkSubstructure($mol_fe_regioiso1,$mol_fe_metallacyc1,array("identifier" => "Fe1", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($mol_fe_regioiso2,$mol_fe_metallacyc1,array("identifier" => "Fe2", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($mol_fe_regioiso1,$mol_fe_regioiso2,array("identifier" => "Fe3", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($mol_fe_regioiso2,$mol_fe_regioiso1,array("identifier" => "Fe4", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));


// Allyl-System
$ru_metallacyc=<<<END
CC12C[Ru]134568(C2)(CC3(C)C4)[C@H]7CC[C@@H]5[C@@H]6CC[C@@H]78
open enventory Sat, 24.05.2008 21:30:59

 17 24  0  0  0  0  0  0  0  0  0 V2000
    6.0750   -5.9167    0.0000 Ru  0  0  0  0  0  0  0  0  0  0  0  0
    6.6375   -7.2125    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.8042   -4.8167    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.9292   -5.7792    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.9292   -5.1792    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.9292   -6.3292    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.9250   -6.8000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.0667   -6.8417    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.9250   -5.4917    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.0875   -4.9000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.9292   -6.6625    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.3750   -6.2792    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.9542   -5.9875    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.9542   -6.6167    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.3750   -5.3417    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.7667   -7.7000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.1667   -4.4542    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  2  1  1  6  0  0  0
  3  1  1  6  0  0  0
  4  1  1  6  0  0  0
  5  1  1  6  0  0  0
  6  1  1  6  0  0  0
  7  1  1  6  0  0  0
  8  1  1  0  0  0  0
  1  9  1  6  0  0  0
 10  1  1  0  0  0  0
  1 11  1  1  0  0  0
 12  6  1  0  0  0  0
 13  4  1  0  0  0  0
 14  7  1  0  0  0  0
 15  5  1  0  0  0  0
 16  2  1  0  0  0  0
 17  3  1  0  0  0  0
 11  2  1  0  0  0  0
  3 10  1  0  0  0  0
  9  3  1  0  0  0  0
  2  8  1  0  0  0  0
  7  6  1  0  0  0  0
  4  5  1  0  0  0  0
 14 13  1  0  0  0  0
 12 15  1  0  0  0  0
M  END

END;

$ru_covalent=<<<END
CC12C[Ru]134568(C2)(CC3(C)C4)[C@H]7CC[C@@H]5[C@@H]6CC[C@@H]78
  Marvin  10140815362D          

  5  4  0  0  0  0            999 V2000
    8.0450   -8.7419    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.3531   -8.2925    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    8.3985   -8.0753    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    8.2016   -9.3328    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.4545   -6.3348    0.0000 Ru  0  3  0  0  0  0  0  0  0  0  0  0
  4  1  1  0  0  0  0
  3  1  1  0  0  0  0
  1  2  2  0  0  0  0
  5  3  1  0  0  0  0
M  CHG  1   5   1
M  END

END;

$ru_ionic=<<<END
CC12C[Ru]134568(C2)(CC3(C)C4)[C@H]7CC[C@@H]5[C@@H]6CC[C@@H]78
  Marvin  10140815352D          

  5  3  0  0  0  0            999 V2000
    8.0450   -8.7419    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.3531   -8.2925    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    8.3985   -8.0753    0.0000 C   0  5  0  0  0  0  0  0  0  0  0  0
    8.2016   -9.3328    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.4545   -6.3348    0.0000 Ru  0  2  0  0  0  0  0  0  0  0  0  0
  4  1  1  0  0  0  0
  3  1  1  0  0  0  0
  1  2  2  0  0  0  0
M  CHG  2   3  -1   5   2
M  END

END;

$mol_ru_metallacyc=readMolfile($ru_metallacyc,array("forStructureSearch" => true, ));
$mol_ru_covalent=readMolfile($ru_covalent,array("forStructureSearch" => true, ));
$mol_ru_ionic=readMolfile($ru_ionic,array("forStructureSearch" => true, ));
checkSubstructure($mol_ru_covalent,$mol_ru_metallacyc,array("identifier" => "Ru1", "FP" => true, "substruct" => true, "smiles_stereo" => false, "smiles" => false, ));
checkSubstructure($mol_ru_ionic,$mol_ru_metallacyc,array("identifier" => "Ru2", "FP" => true, "substruct" => true, "smiles_stereo" => false, "smiles" => false, ));
checkSubstructure($mol_ru_ionic,$mol_ru_covalent,array("identifier" => "Ru3", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($mol_ru_covalent,$mol_ru_ionic,array("identifier" => "Ru4", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));

// acac
$pd_enol=<<<END
FC(F)(F)C(=O)/C=C(O[Pd]O/C(=C\C(=O)C(F)(F)F)C(F)(F)F)/C(F)(F)F
open enventory Sat, 24.05.2008 21:31:08

 27 26  0  0  0  0  0  0  0  0  0 V2000
    3.4560    4.9339    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    4.8543    2.5313    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    0.0000    5.7903    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    2.6299    6.7492    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    0.8792    7.4767    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    8.3103    1.6749    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    5.6919    0.7238    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    7.4426    0.0000    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    0.0000    1.6749    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    0.8792    0.0000    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    2.6299    0.7238    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    7.4426    7.4767    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    5.6919    6.7492    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    8.3103    5.7903    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    1.3528    3.7250    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.9538    3.7250    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.4560    2.5313    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    4.8543    4.9339    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    4.1533    3.7250    0.0000 Pd  0  0  0  0  0  0  0  0  0  0  0  0
    2.0690    4.9339    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.2527    2.5313    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.0690    2.5313    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.2527    4.9339    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.3528    6.1542    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.9538    1.3225    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.3528    1.3225    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.9538    6.1542    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  1 20  2  0  0  0  0
  2 21  2  0  0  0  0
  3 24  1  0  0  0  0
  4 24  1  0  0  0  0
  5 24  1  0  0  0  0
  6 25  1  0  0  0  0
  7 25  1  0  0  0  0
  8 25  1  0  0  0  0
  9 26  1  0  0  0  0
 10 26  1  0  0  0  0
 11 26  1  0  0  0  0
 12 27  1  0  0  0  0
 13 27  1  0  0  0  0
 14 27  1  0  0  0  0
 15 20  1  0  0  0  0
 15 22  2  0  0  0  0
 16 21  1  0  0  0  0
 16 23  2  0  0  0  0
 17 19  1  0  0  0  0
 17 22  1  0  0  0  0
 18 19  1  0  0  0  0
 18 23  1  0  0  0  0
 20 24  1  0  0  0  0
 21 25  1  0  0  0  0
 22 26  1  0  0  0  0
 23 27  1  0  0  0  0
M  END

END;

$pd_alpha=<<<END
FC(F)(F)C(=O)/C=C(O[Pd]O/C(=C\C(=O)C(F)(F)F)C(F)(F)F)/C(F)(F)F
  Marvin  10140814452D          

 27 26  0  0  0  0            999 V2000
    2.0371    2.9082    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    2.8613    1.4920    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    0.0000    3.4130    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    1.5502    3.9782    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    0.5182    4.4070    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    4.8984    0.9872    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    3.3550    0.4266    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    4.3869    0.0000    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    0.0000    0.9872    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    0.5182    0.0000    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    1.5502    0.4266    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    4.3869    4.4070    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    3.3550    3.9782    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    4.8984    3.4130    0.0000 F   0  0  0  0  0  0  0  0  0  0  0  0
    0.7974    2.1956    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.0988    2.1956    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.0371    1.4920    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    2.8613    2.9082    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    2.4481    2.1956    0.0000 Pd  0  0  0  0  0  0  0  0  0  0  0  0
    1.2195    2.9082    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.6856    1.4920    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.2195    1.4920    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.6856    2.9082    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.7974    3.6275    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.0988    0.7795    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.7974    0.7795    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.0988    3.6275    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  1 20  2  0  0  0  0
  2 21  2  0  0  0  0
  3 24  1  0  0  0  0
  4 24  1  0  0  0  0
  5 24  1  0  0  0  0
  6 25  1  0  0  0  0
  7 25  1  0  0  0  0
  8 25  1  0  0  0  0
  9 26  1  0  0  0  0
 10 26  1  0  0  0  0
 11 26  1  0  0  0  0
 12 27  1  0  0  0  0
 13 27  1  0  0  0  0
 14 27  1  0  0  0  0
 15 20  1  0  0  0  0
 15 22  1  0  0  0  0
 16 21  1  0  0  0  0
 16 23  1  0  0  0  0
 17 22  2  0  0  0  0
 18 23  2  0  0  0  0
 20 24  1  0  0  0  0
 21 25  1  0  0  0  0
 22 26  1  0  0  0  0
 23 27  1  0  0  0  0
 19 16  1  0  0  0  0
 19 15  1  0  0  0  0
M  END

END;

$mol_pd_enol=readMolfile($pd_enol,array("forStructureSearch" => true, ));
$mol_pd_alpha=readMolfile($pd_alpha,array("forStructureSearch" => true, ));
checkSubstructure($mol_pd_enol,$mol_pd_alpha,array("identifier" => "Pd1", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($mol_pd_alpha,$mol_pd_enol,array("identifier" => "Pd2", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));

// aromatisation of annelated rings

$me_anthracen=<<<END

  Marvin  10140815082D          

 15 17  0  0  0  0            999 V2000
    2.7107    3.3589    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.9963    3.7714    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.2817    3.3589    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.2817    2.5339    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.9963    2.1214    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.7107    2.5339    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.4252    2.1214    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.1397    2.5339    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.1397    3.3589    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.4252    3.7714    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.5673    3.7714    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.1472    3.3590    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.8617    3.7715    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.1472    2.5340    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.5672    2.1214    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  1  2  1  0  0  0  0
  2  3  2  0  0  0  0
  3  4  1  0  0  0  0
  4  5  2  0  0  0  0
  5  6  1  0  0  0  0
  7  8  1  0  0  0  0
  8  9  2  0  0  0  0
  9 10  1  0  0  0  0
  1  6  1  0  0  0  0
  1 10  2  0  0  0  0
  6  7  2  0  0  0  0
  3 11  1  0  0  0  0
 11 12  2  0  0  0  0
 12 13  1  0  0  0  0
 12 14  1  0  0  0  0
 14 15  2  0  0  0  0
  4 15  1  0  0  0  0
M  END

END;

$tol=<<<END

  Marvin  10140815092D          

  7  7  0  0  0  0            999 V2000
    1.2817    3.3589    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.2817    2.5339    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.5673    3.7714    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.1472    3.3590    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.8617    3.7715    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.1472    2.5340    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.5672    2.1214    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  1  2  2  0  0  0  0
  1  3  1  0  0  0  0
  3  4  2  0  0  0  0
  4  5  1  0  0  0  0
  4  6  1  0  0  0  0
  6  7  2  0  0  0  0
  2  7  1  0  0  0  0
M  END

END;

$mol_me_anthracen=readMolfile($me_anthracen,array("forStructureSearch" => true, ));
$mol_tol=readMolfile($tol,array("forStructureSearch" => true, ));
checkSubstructure($mol_tol,$mol_me_anthracen,array("identifier" => "Cyc1", "FP" => true, "substruct" => true, "smiles_stereo" => false, "smiles" => false, ));

$rh_carbonyl_triple=<<<END

  Marvin  07170911072D          

  3  2  0  0  0  0            999 V2000
   -1.5027   -0.2652    0.0000 Rh  0  0  0  0  0  0  0  0  0  0  0  0
   -0.7058   -0.0517    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.0911    0.1619    0.0000 O   0  3  0  0  0  0  0  0  0  0  0  0
  1  2  1  0  0  0  0
  2  3  3  0  0  0  0
M  CHG  1   3   1
M  END

END;

$rh_carbonyl_cumulene=<<<END

  Marvin  07170911122D          

  3  2  0  0  0  0            999 V2000
   -1.5027   -0.2652    0.0000 Rh  0  3  0  0  0  0  0  0  0  0  0  0
   -0.7058   -0.0517    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.0911    0.1619    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
  1  2  2  0  0  0  0
  2  3  2  0  0  0  0
M  CHG  1   1   1
M  END

END;

$rh_carbonyl_separate=<<<END
[Rh+].[C]=O
  Marvin  07170911072D               5.58000

  3  1  0  0  0  0            999 V2000
   -0.7058   -0.0517    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.0911    0.1619    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
   -2.5634   -0.5893    0.0000 Rh  0  3  0  0  0  0  0  0  0  0  0  0
  1  2  2  0  0  0  0
M  CHG  1   3   1
M  RAD  1   1   1
M  END

END;

$mol_rh_carbonyl_triple=readMolfile($rh_carbonyl_triple,array("forStructureSearch" => true, ));
$mol_rh_carbonyl_cumulene=readMolfile($rh_carbonyl_cumulene,array("forStructureSearch" => true, ));
$mol_rh_carbonyl_separate=readMolfile($rh_carbonyl_separate,array("forStructureSearch" => true, ));
checkSubstructure($mol_rh_carbonyl_triple,$mol_rh_carbonyl_cumulene,array("identifier" => "Rh1", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($mol_rh_carbonyl_triple,$mol_rh_carbonyl_separate,array("identifier" => "Rh2", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($mol_rh_carbonyl_separate,$mol_rh_carbonyl_cumulene,array("identifier" => "Rh3", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
// Listen

// handling of protons
$am_acetate_ionic=<<<END
N.CC(=O)[O-].[H+]
  Marvin  12050914112D               8.39000

  5  3  0  0  0  0            999 V2000
   -1.5027    0.6187    0.0000 N   0  3  0  0  0  0  0  0  0  0  0  0
    0.9134   -1.2154    0.0000 O   0  5  0  0  0  0  0  0  0  0  0  0
    2.1509   -0.5009    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.3259   -0.5009    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.9134    0.2136    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
  4  3  1  0  0  0  0
  2  4  1  0  0  0  0
  4  5  2  0  0  0  0
M  CHG  2   1   1   2  -1
M  END

END;

$am_acetate_separate=<<<END

  Marvin  12050914112D          

  5  3  0  0  0  0            999 V2000
   -1.5027    0.6187    0.0000 N   0  0  0  0  0  0  0  0  0  0  0  0
    0.9134   -1.2154    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    2.1509   -0.5009    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.3259   -0.5009    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.9134    0.2136    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
  4  3  1  0  0  0  0
  2  4  1  0  0  0  0
  4  5  2  0  0  0  0
M  END

END;

// Proton intentionally moved into the middle
$am_acetate_w_proton=<<<END
N.CC(=O)[O-].[H+]
  Marvin  12050914532D               8.39000

  6  3  0  0  0  0            999 V2000
   -1.5027    0.6187    0.0000 N   0  0  0  0  0  0  0  0  0  0  0  0
    0.9134   -1.2154    0.0000 O   0  5  0  0  0  0  0  0  0  0  0  0
    2.1509   -0.5009    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -2.0625   -1.5027    0.0000 H   0  3  0  0  0  0  0  0  0  0  0  0
    1.3259   -0.5009    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.9134    0.2136    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
  5  3  1  0  0  0  0
  2  5  1  0  0  0  0
  6  5  2  0  0  0  0
M  CHG  2   2  -1   4   1
M  END

END;

$mol_am_acetate_ionic=readMolfile($am_acetate_ionic,array("forStructureSearch" => true, ));
$mol_am_acetate_separate=readMolfile($am_acetate_separate,array("forStructureSearch" => true, ));
$mol_am_acetate_w_proton=readMolfile($am_acetate_w_proton,array("forStructureSearch" => true, ));
checkSubstructure($mol_am_acetate_ionic,$mol_am_acetate_separate,array("identifier" => "Ammonium acetate1", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($mol_am_acetate_ionic,$mol_am_acetate_w_proton,array("identifier" => "Ammonium acetate2", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($mol_am_acetate_separate,$mol_am_acetate_w_proton,array("identifier" => "Ammonium acetate3", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));

// Multiple parts in one part
$fe_cl2_dihyd=<<<END

  Marvin  01031017302D              29.43000

  5  0  0  0  0  0            999 V2000
    0.3241    1.5321    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    0.3536   -0.6482    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    1.5911    0.2946    0.0000 Fe  0  2  0  0  0  0  0  0  0  0  0  0
    3.0643    1.2670    0.0000 Cl  0  5  0  0  0  0  0  0  0  0  0  0
    2.9170   -0.7366    0.0000 Cl  0  5  0  0  0  0  0  0  0  0  0  0
M  CHG  3   3   2   4  -1   5  -1
M  END

END;

$fe_cl2_dme=<<<END

  Marvin  01031017302D          

  9  7  0  0  0  0            999 V2000
    0.0589    0.6777    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.7734    1.0902    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    0.0589   -0.1473    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.7734   -0.5598    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    0.7734    1.9152    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.7734   -1.3848    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.2964    0.1768    0.0000 Fe  0  0  0  0  0  0  0  0  0  0  0  0
    1.8798   -0.4066    0.0000 Cl  0  0  0  0  0  0  0  0  0  0  0  0
    2.0109    0.5893    0.0000 Cl  0  0  0  0  0  0  0  0  0  0  0  0
  1  2  1  0  0  0  0
  1  3  1  0  0  0  0
  3  4  1  0  0  0  0
  2  5  1  0  0  0  0
  4  6  1  0  0  0  0
  7  8  1  0  0  0  0
  7  9  1  0  0  0  0
M  END

END;

$mol_fe_cl2_dihyd=readMolfile($fe_cl2_dihyd,array("forStructureSearch" => true, ));
$mol_fe_cl2_dme=readMolfile($fe_cl2_dme,array("forStructureSearch" => true, ));
checkSubstructure($mol_fe_cl2_dihyd,$mol_fe_cl2_dme,array("identifier" => "FeCl2 multipart", "FP" => true, "substruct" => true, ));

// Chains and rings
$dimethylcyclohexane=<<<END
CC1CCC(C)CC1
open enventory Sat, 24.05.2008 21:43:30

  8  8  0  0  0  0  0  0  0  0999 V2000
   -0.3125   -1.5583    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.0958   -2.2708    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.9125   -2.2734    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.3270   -1.5652    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.9187   -0.8527    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.0958   -0.8484    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.1500   -1.5625    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.1375   -1.5542    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  1  6  1  0  0  0  0
  2  3  1  0  0  0  0
  3  4  1  0  0  0  0
  4  5  1  0  0  0  0
  5  6  1  0  0  0  0
  4  7  1  0  0  0  0
  1  2  1  0  0  0  0
  1  8  1  0  0  0  0
M  END

END;

$camphor=<<<END
O=C1CC2CCC1(C)C2(C)C
open enventory Sat, 24.05.2008 21:30:26

 11 12  0  0  0  0  0  0  0  0999 V2000
    4.8833   -3.2083    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.8833   -1.7417    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.7083   -3.4292    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.3333   -2.4708    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.1542   -2.6958    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.0958   -3.4292    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.5208   -2.6958    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.0167   -4.2167    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    4.6083   -3.6917    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.3708   -1.4583    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.4083   -1.4583    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  7  4  1  0  0  0  0
  3  1  1  0  0  0  0
  4  2  1  0  0  0  0
  5  3  1  0  0  0  0
  6  1  1  0  0  0  0
  7  6  1  0  0  0  0
  8  3  2  0  0  0  0
  9  1  1  0  0  0  0
 10  2  1  0  0  0  0
 11  2  1  0  0  0  0
  5  4  1  0  0  0  0
  1  2  1  0  0  0  0
M  END

END;

$octane=<<<END
CCCCCCCC
open enventory Sat, 24.05.2008 21:31:28

  8  7  0  0  0  0  0  0  0  0999 V2000
   -3.2167    0.3250    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.2125   -0.3208    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.9250    0.3250    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.9292   -0.3208    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.6458    0.3250    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.6417   -0.3208    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -4.5000   -0.3208    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.5000    0.3250    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  2  3  1  0  0  0  0
  3  6  1  0  0  0  0
  4  1  1  0  0  0  0
  5  4  1  0  0  0  0
  6  5  1  0  0  0  0
  7  1  1  0  0  0  0
  8  2  1  0  0  0  0
M  END

END;

$nonane=<<<END
CCCCCCCCC
open enventory Sat, 24.05.2008 21:33:23

  9  8  0  0  0  0  0  0  0  0999 V2000
    1.2167   -4.2750    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.9292   -3.8625    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.6417   -4.2708    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.3542   -3.8583    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.0667   -4.2667    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.7792   -3.8542    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.4917   -4.2625    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.2042   -3.8500    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.9167   -4.2583    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  4  5  1  0  0  0  0
  2  3  1  0  0  0  0
  5  6  1  0  0  0  0
  1  2  1  0  0  0  0
  6  7  1  0  0  0  0
  3  4  1  0  0  0  0
  7  8  1  0  0  0  0
  8  9  1  0  0  0  0
M  END

END;

$mol_dimethylcyclohexane=readMolfile($dimethylcyclohexane,array("forStructureSearch" => true, ));
$mol_camphor=readMolfile($camphor,array("forStructureSearch" => true, ));
$mol_octane=readMolfile($octane,array("forStructureSearch" => true, ));
$mol_nonane=readMolfile($nonane,array("forStructureSearch" => true, ));

checkSubstructure($mol_octane,$mol_dimethylcyclohexane,array("identifier" => "Octane in 1,4-dimethylcyclohexane", "substruct" => false, ));
checkSubstructure($mol_octane,$mol_camphor,array("identifier" => "Octane in camphor", "substruct" => true, ));
checkSubstructure($mol_nonane,$mol_camphor,array("identifier" => "Nonane in camphor", "substruct" => false, ));
checkSubstructure($mol_dimethylcyclohexane,$mol_camphor,array("identifier" => "1,4-Dimethylcyclohexane in camphor", "substruct" => true, ));

// fingerprinting, single/double/aromatic
$vinyl_pyridine=<<<END
imes v0.1 pre alpha


  8  8  0  0  0  0            999 V2000
  129.0000 -139.9808    0.0000 C   0  0  0  0  0  0  0               
   84.0000 -165.9616    0.0000 C   0  0  0  0  0  0  0               
   69.0000 -139.9809    0.0000 C   0  0  0  0  0  0  0               
  114.0000 -114.0001    0.0000 N   0  0  0  0  0  0  0               
   83.9999 -114.0001    0.0000 C   0  0  0  0  0  0  0               
  114.0000 -165.9616    0.0000 C   0  0  0  0  0  0  0               
  159.0000 -139.9808    0.0000 C   0  0  0  0  0  0  0               
  174.0000 -165.9615    0.0000 C   0  0  0  0  0  0  0               
  1  7  1  0  0  0  0
  8  7  2  0  0  0  0
  4  1  1  0  0  0  0
  6  1  2  0  0  0  0
  6  2  1  0  0  0  0
  3  2  2  0  0  0  0
  3  5  1  0  0  0  0
  4  5  2  0  0  0  0
M  END

END;

$bipy=<<<END
imes v0.1 pre alpha


 12 13  0  0  0  0            999 V2000
  129.0000 -139.9808    0.0000 C   0  0  0  0  0  0  0               
   84.0000 -165.9616    0.0000 C   0  0  0  0  0  0  0               
   69.0000 -139.9809    0.0000 C   0  0  0  0  0  0  0               
  114.0000 -114.0001    0.0000 N   0  0  0  0  0  0  0               
   83.9999 -114.0001    0.0000 C   0  0  0  0  0  0  0               
  114.0000 -165.9616    0.0000 C   0  0  0  0  0  0  0               
  159.0000 -139.9808    0.0000 C   0  0  0  0  0  0  0               
  204.0000 -114.0000    0.0000 C   0  0  0  0  0  0  0               
  174.0000 -114.0000    0.0000 N   0  0  0  0  0  0  0               
  219.0000 -139.9808    0.0000 C   0  0  0  0  0  0  0               
  174.0000 -165.9615    0.0000 C   0  0  0  0  0  0  0               
  204.0000 -165.9615    0.0000 C   0  0  0  0  0  0  0               
  4  1  2  0  0  0  0
  3  2  1  0  0  0  0
  3  5  2  0  0  0  0
  4  5  1  0  0  0  0
  6  2  2  0  0  0  0
  6  1  1  0  0  0  0
  9  7  1  0  0  0  0
 10  8  1  0  0  0  0
  9  8  2  0  0  0  0
  1  7  1  0  0  0  0
 10 12  2  0  0  0  0
 11 12  1  0  0  0  0
 11  7  2  0  0  0  0
M  END

END;

$phen=<<<END
imes v0.1 pre alpha


 14 16  0  0  0  0            999 V2000
   65.0000  -98.0000    0.0000 C   0  0  0  0  0  0  0               
   90.5072  -82.2081    0.0000 C   0  0  0  0  0  0  0               
  116.9370  -96.4019    0.0000 C   0  0  0  0  0  0  0               
  117.1110 -126.4014    0.0000 N   0  0  0  0  0  0  0               
   91.2177 -141.5519    0.0000 C   0  0  0  0  0  0  0               
   65.2369 -126.5519    0.0000 C   0  0  0  0  0  0  0               
   89.5845  -52.2223    0.0000 C   0  0  0  0  0  0  0               
  142.6392  -80.9295    0.0000 C   0  0  0  0  0  0  0               
  115.0917  -36.4303    0.0000 C   0  0  0  0  0  0  0               
  168.8898  -95.4521    0.0000 N   0  0  0  0  0  0  0               
  141.0724  -51.4303    0.0000 C   0  0  0  0  0  0  0               
  167.0532  -36.4303    0.0000 C   0  0  0  0  0  0  0               
  193.0339  -51.4303    0.0000 C   0  0  0  0  0  0  0               
  193.0339  -81.4303    0.0000 C   0  0  0  0  0  0  0               
  9 11  1  0  0  0  0
  2  3  1  0  0  0  0
  3  4  2  0  0  0  0
  4  5  1  0  0  0  0
  5  6  2  0  0  0  0
  6  1  1  0  0  0  0
  1  2  2  0  0  0  0
  2  7  1  0  0  0  0
  7  9  2  0  0  0  0
  8 10  2  0  0  0  0
 14 10  1  0  0  0  0
 13 14  2  0  0  0  0
 12 13  1  0  0  0  0
 11 12  2  0  0  0  0
  3  8  1  0  0  0  0
 11  8  1  0  0  0  0
M  END

END;

$semi_strange_phen=<<<END
imes v0.1 pre alpha


 14 16  0  0  0  0            999 V2000
   65.0000  -98.0000    0.0000 C   0  0  0  0  0  0  0               
   90.5072  -82.2081    0.0000 C   0  0  0  0  0  0  0               
  116.9370  -96.4019    0.0000 C   0  0  0  0  0  0  0               
  117.1110 -126.4014    0.0000 N   0  0  0  0  0  0  0               
   91.2177 -141.5519    0.0000 C   0  0  0  0  0  0  0               
   65.2369 -126.5519    0.0000 C   0  0  0  0  0  0  0               
   89.5845  -52.2223    0.0000 C   0  0  0  0  0  0  0               
  142.6392  -80.9295    0.0000 C   0  0  0  0  0  0  0               
  115.0917  -36.4303    0.0000 C   0  0  0  0  0  0  0               
  168.8898  -95.4521    0.0000 N   0  0  0  0  0  0  0               
  141.0724  -51.4303    0.0000 C   0  0  0  0  0  0  0               
  167.0532  -36.4303    0.0000 C   0  0  0  0  0  0  0               
  193.0339  -51.4303    0.0000 C   0  0  0  0  0  0  0               
  193.0339  -81.4303    0.0000 C   0  0  0  0  0  0  0               
  9 11  1  0  0  0  0
  2  3  1  0  0  0  0
  3  4  2  0  0  0  0
  4  5  1  0  0  0  0
  5  6  2  0  0  0  0
  6  1  1  0  0  0  0
  1  2  2  0  0  0  0
  7  9  2  0  0  0  0
  2  7  1  0  0  0  0
  3  8  1  0  0  0  0
  8 10  1  0  0  0  0
 11 12  1  0  0  0  0
 11  8  2  0  0  0  0
 12 13  2  0  0  0  0
 13 14  1  0  0  0  0
 14 10  2  0  0  0  0
M  END

END;

$strange_phen=<<<END
imes v0.1 pre alpha


 14 16  0  0  0  0            999 V2000
  337.7124 -441.9795    0.0000 N   0  0  0  0  0  0  0               
  337.6647 -472.3458    0.0000 C   0  0  0  0  0  0  0               
  363.7275 -487.5786    0.0000 C   0  0  0  0  0  0  0               
  390.0206 -472.3260    0.0000 C   0  0  0  0  0  0  0               
  363.6561 -426.9175    0.0000 C   0  0  0  0  0  0  0               
  389.7983 -441.8882    0.0000 C   0  0  0  0  0  0  0               
  415.6984 -426.8897    0.0000 C   0  0  0  0  0  0  0               
  415.6587 -396.7219    0.0000 C   0  0  0  0  0  0  0               
  363.5608 -396.9006    0.0000 C   0  0  0  0  0  0  0               
  389.3219 -382.0768    0.0000 C   0  0  0  0  0  0  0               
  389.2227 -352.2464    0.0000 C   0  0  0  0  0  0  0               
  363.3782 -337.5972    0.0000 C   0  0  0  0  0  0  0               
  337.5972 -352.7784    0.0000 C   0  0  0  0  0  0  0               
  337.7997 -382.1522    0.0000 N   0  0  0  0  0  0  0               
  4  6  1  0  0  0  0
  7  8  1  0  0  0  0
  8 10  2  0  0  0  0
  9  5  2  0  0  0  0
  5  1  1  0  0  0  0
  2  3  1  0  0  0  0
  9 10  1  0  0  0  0
  1  2  2  0  0  0  0
 10 11  1  0  0  0  0
  5  6  1  0  0  0  0
 11 12  2  0  0  0  0
  3  4  2  0  0  0  0
 12 13  1  0  0  0  0
  6  7  2  0  0  0  0
 13 14  2  0  0  0  0
 14  9  1  0  0  0  0
M  END

END;

$dime_py_dbl_betw_me=<<<END
imes v0.1 pre alpha


  8  8  0  0  0  0            999 V2000
   65.0000  -98.0000    0.0000 C   0  0  0  0  0  0  0               
   90.5072  -82.2081    0.0000 C   0  0  0  0  0  0  0               
  116.9370  -96.4019    0.0000 C   0  0  0  0  0  0  0               
  117.1110 -126.4014    0.0000 N   0  0  0  0  0  0  0               
   91.2177 -141.5519    0.0000 C   0  0  0  0  0  0  0               
   65.2369 -126.5519    0.0000 C   0  0  0  0  0  0  0               
   89.5845  -52.2223    0.0000 C   0  0  0  0  0  0  0               
  142.6392  -80.9295    0.0000 C   0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
  5  6  1  0  0  0  0
  6  1  2  0  0  0  0
  3  4  1  0  0  0  0
  2  7  1  0  0  0  0
  4  5  2  0  0  0  0
  2  3  2  0  0  0  0
  3  8  1  0  0  0  0
M  END

END;

$dime_py_sng_betw_me=<<<END
imes v0.1 pre alpha


  8  8  0  0  0  0            999 V2000
   65.0000  -98.0000    0.0000 C   0  0  0  0  0  0  0               
   90.5072  -82.2081    0.0000 C   0  0  0  0  0  0  0               
  116.9370  -96.4019    0.0000 C   0  0  0  0  0  0  0               
  117.1110 -126.4014    0.0000 N   0  0  0  0  0  0  0               
   91.2177 -141.5519    0.0000 C   0  0  0  0  0  0  0               
   65.2369 -126.5519    0.0000 C   0  0  0  0  0  0  0               
   89.5845  -52.2223    0.0000 C   0  0  0  0  0  0  0               
  142.6392  -80.9295    0.0000 C   0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
  5  6  1  0  0  0  0
  6  1  2  0  0  0  0
  3  4  1  0  0  0  0
  2  7  1  0  0  0  0
  4  5  2  0  0  0  0
  2  3  2  0  0  0  0
  3  8  1  0  0  0  0
M  END

END;

$butane=<<<END
imes v0.1 pre alpha


  4  3  0  0  0  0            999 V2000
   89.3388  -75.6380    0.0000 C   0  0  0  0  0  0  0               
  114.9461  -91.2670    0.0000 C   0  0  0  0  0  0  0               
  141.2849  -76.9049    0.0000 C   0  0  0  0  0  0  0               
  142.0163  -46.9138    0.0000 C   0  0  0  0  0  0  0               
  3  4  1  0  0  0  0
  1  2  1  0  0  0  0
  2  3  1  0  0  0  0
M  END

END;

$sear_vinyl_pyridine=readMolfile($vinyl_pyridine,array("forStructureSearch" => true, ));
$sear_dime_py_dbl_betw_me=readMolfile($dime_py_dbl_betw_me,array("forStructureSearch" => true, ));
$sear_dime_py_sng_betw_me=readMolfile($dime_py_sng_betw_me,array("forStructureSearch" => true, ));
$sear_bipy=readMolfile($bipy,array("forStructureSearch" => true, ));
$sear_phen=readMolfile($phen,array("forStructureSearch" => true, ));
$sear_semi_strange_phen=readMolfile($semi_strange_phen,array("forStructureSearch" => true, ));
$sear_strange_phen=readMolfile($strange_phen,array("forStructureSearch" => true, ));
$sear_butane=readMolfile($butane,array("forStructureSearch" => true, ));

$mol_vinyl_pyridine=readMolfile($vinyl_pyridine,array("forStructureSearch" => false, ));
$mol_dime_py_dbl_betw_me=readMolfile($dime_py_dbl_betw_me,array("forStructureSearch" => false, ));
$mol_dime_py_sng_betw_me=readMolfile($dime_py_sng_betw_me,array("forStructureSearch" => false, ));
$mol_bipy=readMolfile($bipy,array("forStructureSearch" => false, ));
$mol_phen=readMolfile($phen,array("forStructureSearch" => false, ));
$mol_semi_strange_phen=readMolfile($semi_strange_phen,array("forStructureSearch" => false, ));
$mol_strange_phen=readMolfile($strange_phen,array("forStructureSearch" => false, ));
//~ $mol_butane=readMolfile($butane,array("forStructureSearch" => false, ));

checkSubstructure($sear_vinyl_pyridine,$mol_bipy,array("identifier" => "2-Vinylpyridine in 2,2'-bipyridine", "FP" => true, "substruct" => true, ));
//~ checkSubstructure($sear_vinyl_pyridine,$mol_phen,array("identifier" => "2-Vinylpyridine in 1,10-phenanthroline", "FP" => true, "substruct" => true, )); // does not work, acceptable
checkSubstructure($sear_vinyl_pyridine,$mol_semi_strange_phen,array("identifier" => "2-Vinylpyridine in 1,10-phenanthroline", "FP" => true, "substruct" => true, ));
checkSubstructure($sear_vinyl_pyridine,$mol_strange_phen,array("identifier" => "2-Vinylpyridine in 1,10-phenanthroline with middle ring aromatic", "FP" => true, "substruct" => true, ));

checkSubstructure($sear_bipy,$mol_phen,array("identifier" => "2,2'-Bipyridine in 1,10-phenanthroline", "FP" => true, "substruct" => true, ));
checkSubstructure($sear_bipy,$mol_semi_strange_phen,array("identifier" => "2,2'-Bipyridine in 1,10-phenanthroline with middle ring 1,3-diene", "FP" => true, "substruct" => true, ));
checkSubstructure($sear_bipy,$mol_strange_phen,array("identifier" => "2,2'-Bipyridine in 1,10-phenanthroline with middle ring aromatic", "FP" => true, "substruct" => true, ));

checkSubstructure($sear_dime_py_dbl_betw_me,$mol_phen,array("identifier" => "2,3-Dimethylpyridine (double between methyl groups) in 1,10-phenanthroline", "FP" => true, "substruct" => true, ));
checkSubstructure($sear_dime_py_sng_betw_me,$mol_phen,array("identifier" => "2,3-Dimethylpyridine (single between methyl groups) in 1,10-phenanthroline", "FP" => true, "substruct" => true, ));
checkSubstructure($sear_dime_py_dbl_betw_me,$mol_strange_phen,array("identifier" => "2,3-Dimethylpyridine (double between methyl groups) in 1,10-phenanthroline with middle ring aromatic", "FP" => true, "substruct" => true, ));
checkSubstructure($sear_dime_py_sng_betw_me,$mol_strange_phen,array("identifier" => "2,3-Dimethylpyridine (single between methyl groups) in 1,10-phenanthroline with middle ring aromatic", "FP" => true, "substruct" => true, ));

checkSubstructure($sear_butane,$mol_dime_py_dbl_betw_me,array("identifier" => "Butane in 2,3-dimethylpyridine (double between methyl groups)", "FP" => true, "substruct" => true, ));
checkSubstructure($sear_butane,$mol_dime_py_sng_betw_me,array("identifier" => "Butane in 2,3-dimethylpyridine (single between methyl groups)", "FP" => true, "substruct" => true, ));
checkSubstructure($sear_butane,$mol_phen,array("identifier" => "Butane in 1,10-phenanthroline", "substruct" => true, ));
checkSubstructure($sear_butane,$mol_semi_strange_phen,array("identifier" => "Butane in 1,10-phenanthroline with middle ring 1,3-diene", "substruct" => true, ));
checkSubstructure($sear_butane,$mol_strange_phen,array("identifier" => "Butane in 1,10-phenanthroline with middle ring aromatic", "substruct" => false, ));

checkSubstructure($sear_phen,$mol_semi_strange_phen,array("identifier" => "1,10-phenanthroline1", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($sear_phen,$mol_strange_phen,array("identifier" => "1,10-phenanthroline2", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));

checkSubstructure($sear_semi_strange_phen,$mol_phen,array("identifier" => "1,10-phenanthroline3", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($sear_semi_strange_phen,$mol_strange_phen,array("identifier" => "1,10-phenanthroline4", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));

checkSubstructure($sear_strange_phen,$mol_phen,array("identifier" => "1,10-phenanthroline5", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($sear_strange_phen,$mol_semi_strange_phen,array("identifier" => "1,10-phenanthroline6", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));


$cyclohexane=<<<END
imes v0.1 pre alpha


  6  6  0  0  0  0            999 V2000
  156.0000  -94.0000    0.0000 C   0  0  0  0  0  0  0               
  186.0000  -94.0000    0.0000 C   0  0  0  0  0  0  0               
  201.0000 -119.9808    0.0000 C   0  0  0  0  0  0  0               
  186.0000 -145.9615    0.0000 C   0  0  0  0  0  0  0               
  156.0000 -145.9615    0.0000 C   0  0  0  0  0  0  0               
  141.0000 -119.9808    0.0000 C   0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
  2  3  1  0  0  0  0
  3  4  1  0  0  0  0
  4  5  1  0  0  0  0
  5  6  1  0  0  0  0
  6  1  1  0  0  0  0
M  END

END;

$benzene=<<<END
imes v0.1 pre alpha


  6  6  0  0  0  0            999 V2000
  156.0000  -94.0000    0.0000 C   0  0  0  0  0  0  0               
  186.0000  -94.0000    0.0000 C   0  0  0  0  0  0  0               
  201.0000 -119.9808    0.0000 C   0  0  0  0  0  0  0               
  186.0000 -145.9615    0.0000 C   0  0  0  0  0  0  0               
  156.0000 -145.9615    0.0000 C   0  0  0  0  0  0  0               
  141.0000 -119.9808    0.0000 C   0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
  5  6  1  0  0  0  0
  6  1  2  0  0  0  0
  2  3  2  0  0  0  0
  3  4  1  0  0  0  0
  4  5  2  0  0  0  0
M  END

END;

$butene=<<<END
imes v0.1 pre alpha


  4  3  0  0  0  0            999 V2000
   63.0000  -90.0000    0.0000 C   0  0  0  0  0  0  0               
   89.3388  -75.6380    0.0000 C   0  0  0  0  0  0  0               
  114.9461  -91.2670    0.0000 C   0  0  0  0  0  0  0               
  141.2849  -76.9049    0.0000 C   0  0  0  0  0  0  0               
  3  4  1  0  0  0  0
  2  3  1  0  0  0  0
  1  2  2  0  0  0  0
M  END

END;

$allene=<<<END
imes v0.1 pre alpha


  3  2  0  0  0  0            999 V2000
   99.0000 -128.0000    0.0000 C   0  0  0  0  0  0  0               
  129.0000 -128.0000    0.0000 C   0  0  0  0  0  0  0               
  157.9778 -135.7646    0.0000 C   0  0  0  0  0  0  0               
  1  2  2  0  0  0  0
  2  3  2  0  0  0  0
M  END

END;

$o_diethylbenzene_dbl=<<<END
imes v0.1 pre alpha


 10 10  0  0  0  0            999 V2000
  151.0000 -119.0000    0.0000 C   0  0  0  0  0  0  0               
  151.0000 -170.9615    0.0000 C   0  0  0  0  0  0  0               
  121.0000 -170.9615    0.0000 C   0  0  0  0  0  0  0               
  121.0000 -119.0000    0.0000 C   0  0  0  0  0  0  0               
  166.0000 -144.9808    0.0000 C   0  0  0  0  0  0  0               
  106.0000 -144.9808    0.0000 C   0  0  0  0  0  0  0               
  196.0000 -144.9808    0.0000 C   0  0  0  0  0  0  0               
  211.0000 -170.9616    0.0000 C   0  0  0  0  0  0  0               
  166.0000 -196.9423    0.0000 C   0  0  0  0  0  0  0               
  151.0000 -222.9230    0.0000 C   0  0  0  0  0  0  0               
  5  1  1  0  0  0  0
  5  2  2  0  0  0  0
  4  1  2  0  0  0  0
  3  6  2  0  0  0  0
  5  7  1  0  0  0  0
  7  8  1  0  0  0  0
  3  2  1  0  0  0  0
  9 10  1  0  0  0  0
  2  9  1  0  0  0  0
  4  6  1  0  0  0  0
M  END

END;

$o_diethylbenzene_sng=<<<END
imes v0.1 pre alpha


 10 10  0  0  0  0            999 V2000
  151.0000 -119.0000    0.0000 C   0  0  0  0  0  0  0               
  151.0000 -170.9615    0.0000 C   0  0  0  0  0  0  0               
  121.0000 -170.9615    0.0000 C   0  0  0  0  0  0  0               
  121.0000 -119.0000    0.0000 C   0  0  0  0  0  0  0               
  166.0000 -144.9808    0.0000 C   0  0  0  0  0  0  0               
  106.0000 -144.9808    0.0000 C   0  0  0  0  0  0  0               
  196.0000 -144.9808    0.0000 C   0  0  0  0  0  0  0               
  211.0000 -170.9616    0.0000 C   0  0  0  0  0  0  0               
  166.0000 -196.9423    0.0000 C   0  0  0  0  0  0  0               
  151.0000 -222.9230    0.0000 C   0  0  0  0  0  0  0               
  5  7  1  0  0  0  0
  7  8  1  0  0  0  0
  9 10  1  0  0  0  0
  2  9  1  0  0  0  0
  4  6  2  0  0  0  0
  3  2  2  0  0  0  0
  5  1  2  0  0  0  0
  4  1  1  0  0  0  0
  5  2  1  0  0  0  0
  3  6  1  0  0  0  0
M  END

END;

$n_hexane=<<<END
imes v0.1 pre alpha


  6  5  0  0  0  0            999 V2000
  121.0000 -144.0000    0.0000 C   0  0  0  0  0  0  0               
  141.2745 -121.8879    0.0000 C   0  0  0  0  0  0  0               
  170.5614 -128.3901    0.0000 C   0  0  0  0  0  0  0               
  190.8359 -106.2780    0.0000 C   0  0  0  0  0  0  0               
  220.1228 -112.7802    0.0000 C   0  0  0  0  0  0  0               
  240.3973  -90.6681    0.0000 C   0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
  2  3  1  0  0  0  0
  3  4  1  0  0  0  0
  4  5  1  0  0  0  0
  5  6  1  0  0  0  0
M  END

END;

$sear_cyclohexane=readMolfile($cyclohexane,array("forStructureSearch" => true, ));
$sear_butene=readMolfile($butene,array("forStructureSearch" => true, ));
$sear_allene=readMolfile($allene,array("forStructureSearch" => true, ));
$sear_n_hexane=readMolfile($n_hexane,array("forStructureSearch" => true, ));

$mol_benzene=readMolfile($benzene,array("forStructureSearch" => false, ));
$mol_o_diethylbenzene_dbl=readMolfile($o_diethylbenzene_dbl,array("forStructureSearch" => false, ));
$mol_o_diethylbenzene_sng=readMolfile($o_diethylbenzene_sng,array("forStructureSearch" => false, ));

checkSubstructure($sear_cyclohexane,$mol_benzene,array("identifier" => "Cyclohexane in benzene (should not match)", "FP" => false, "substruct" => false, ));
checkSubstructure($sear_butene,$mol_benzene,array("identifier" => "1-Butene in benzene (should not match)", "FP" => false, "substruct" => false, ));
checkSubstructure($sear_allene,$mol_benzene,array("identifier" => "Allene in benzene (should not match)", "substruct" => false, ));
checkSubstructure($sear_butane,$mol_benzene,array("identifier" => "Butane in benzene (should not match)", "FP" => false, "substruct" => false, ));

checkSubstructure($sear_n_hexane,$mol_o_diethylbenzene_dbl,array("identifier" => "n-Hexane in o-diethylbenzene (double bond between substituents)", "FP" => true, "substruct" => true, ));
checkSubstructure($sear_n_hexane,$mol_o_diethylbenzene_sng,array("identifier" => "n-Hexane in o-diethylbenzene (single bond between substituents)", "FP" => true, "substruct" => true, ));

// expl Hs
$cyclohexanol_ex=<<<END
imes v0.1 pre alpha


  8  8  0  0  0  0            999 V2000
   94.0000  -64.0000    0.0000 C   0  0  0  0  0  0  0               
  124.0000  -64.0000    0.0000 O   0  0  0  0  0  0  0               
   79.0000  -38.0192    0.0000 C   0  0  0  0  0  0  0               
   79.0000  -89.9808    0.0000 C   0  0  0  0  0  0  0               
   49.0000  -38.0192    0.0000 C   0  0  0  0  0  0  0               
   49.0000  -89.9808    0.0000 C   0  0  0  0  0  0  0               
   34.0000  -64.0000    0.0000 C   0  0  0  0  0  0  0               
  139.0000  -89.9808    0.0000 H   0  0  0  0  0  0  0               
  1  4  1  0  0  0  0
  4  6  1  0  0  0  0
  5  7  1  0  0  0  0
  6  7  1  0  0  0  0
  3  5  1  0  0  0  0
  1  3  1  0  0  0  0
  1  2  1  0  0  0  0
  2  8  1  0  0  0  0
M  END

END;

$cyclohexanol=<<<END
imes v0.1 pre alpha


  7  7  0  0  0  0            999 V2000
   76.0000  -67.0000    0.0000 C   0  0  0  0  0  0  0               
  102.4044  -52.7590    0.0000 O   0  0  0  0  0  0  0               
   50.4647  -51.2536    0.0000 C   0  0  0  0  0  0  0               
   75.1308  -96.9874    0.0000 C   0  0  0  0  0  0  0               
   24.4840  -66.2536    0.0000 C   0  0  0  0  0  0  0               
   48.7263 -111.2284    0.0000 C   0  0  0  0  0  0  0               
   16.7194  -95.2313    0.0000 C   0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
  1  3  1  0  0  0  0
  1  4  1  0  0  0  0
  3  5  1  0  0  0  0
  4  6  1  0  0  0  0
  5  7  1  0  0  0  0
  6  7  1  0  0  0  0
M  END

END;

$methoxycyclohexane=<<<END
imes v0.1 pre alpha


  8  8  0  0  0  0            999 V2000
   79.0000  -80.0000    0.0000 C   0  0  0  0  0  0  0               
  109.0000  -80.0000    0.0000 C   0  0  0  0  0  0  0               
  124.0000 -105.9808    0.0000 C   0  0  0  0  0  0  0               
   64.0000 -105.9808    0.0000 C   0  0  0  0  0  0  0               
   34.0000 -105.9808    0.0000 O   0  0  0  0  0  0  0               
   79.0000 -131.9615    0.0000 C   0  0  0  0  0  0  0               
  109.0000 -131.9615    0.0000 C   0  0  0  0  0  0  0               
   19.0000  -80.0000    0.0000 C   0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
  2  3  1  0  0  0  0
  1  4  1  0  0  0  0
  4  6  1  0  0  0  0
  6  7  1  0  0  0  0
  3  7  1  0  0  0  0
  4  5  1  0  0  0  0
  5  8  1  0  0  0  0
M  END

END;

$mol_cyclohexanol_ex=readMolfile($cyclohexanol_ex,array("forStructureSearch" => false, ));
$mol_cyclohexanol=readMolfile($cyclohexanol,array("forStructureSearch" => false, ));
$mol_methoxycyclohexane=readMolfile($methoxycyclohexane,array("forStructureSearch" => false, ));

checkSubstructure($mol_cyclohexanol_ex,$mol_cyclohexanol,array("identifier" => "Cyclohexanol1 (expl Hs)", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));
checkSubstructure($mol_cyclohexanol_ex,$mol_methoxycyclohexane,array("identifier" => "Cyclohexanol2 (expl Hs)", "FP" => true, "substruct" => false, ));
checkSubstructure($mol_cyclohexanol,$mol_methoxycyclohexane,array("identifier" => "Cyclohexanol3 (expl Hs)", "FP" => true, "substruct" => true, ));

$pyridine_carboxylate=<<<END

  Marvin  08241117022D          

  9  9  0  0  0  0            999 V2000
   -0.3241    1.7679    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.0386    1.3554    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.0386    0.5303    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.3241    0.1178    0.0000 N   0  0  0  0  0  0  0  0  0  0  0  0
    0.3904    0.5303    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.3904    1.3554    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.1048    0.1179    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.8193    0.5304    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    1.1048   -0.7071    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
  1  2  1  0  0  0  0
  1  6  2  0  0  0  0
  2  3  2  0  0  0  0
  3  4  1  0  0  0  0
  4  5  2  0  0  0  0
  5  6  1  0  0  0  0
  5  7  1  0  0  0  0
  7  8  2  0  0  0  0
  7  9  1  0  0  0  0
M  END

END;

$pyridine_carbaldehyde=<<<END

  Marvin  08241117012D          

  8  8  0  0  0  0            999 V2000
   -0.3241    1.7679    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.0386    1.3554    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.0386    0.5303    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.3241    0.1178    0.0000 N   0  0  0  0  0  0  0  0  0  0  0  0
    0.3904    0.5303    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.3904    1.3554    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.1048    0.1179    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.8193    0.5304    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
  1  2  1  0  0  0  0
  1  6  2  0  0  0  0
  2  3  2  0  0  0  0
  3  4  1  0  0  0  0
  4  5  2  0  0  0  0
  5  6  1  0  0  0  0
  5  7  1  0  0  0  0
  7  8  2  0  0  0  0
M  END

END;

$sear_pyridine_carbaldehyde=readMolfile($pyridine_carbaldehyde,array("forStructureSearch" => true, ));
$mol_pyridine_carboxylate=readMolfile($pyridine_carboxylate,array("forStructureSearch" => false, ));

checkSubstructure($sear_pyridine_carbaldehyde,$mol_pyridine_carboxylate,array("identifier" => "Pyridine carboxylic acid vs. carbaldehyde", "FP" => true, "substruct" => true, ));

$ru_cymene_ordered=<<<END

  Ketcher 12111122272D 1   1.00000     0.00000     0

 26 36  0     0  0            999 V2000
    1.4885    2.0245    0.0000 Ru  0  0  0  0  0  0  0        0  0  0
    1.4537    3.8256    0.0000 Ru  0  0  0  0  0  0  0        0  0  0
    0.9080    0.5210    0.0000 C   0  0  0  0  0  0  0        0  0  0
    1.9897    5.3439    0.0000 C   0  0  0  0  0  0  0        0  0  0
    2.1087    0.5210    0.0000 C   0  0  0  0  0  0  0        0  0  0
    0.8732    5.3439    0.0000 C   0  0  0  0  0  0  0        0  0  0
    1.2057    1.0420    0.0000 C   0  0  0  0  0  0  0        0  0  0
    1.2057    0.0000    0.0000 C   0  0  0  0  0  0  0        0  0  0
    1.7119    5.8202    0.0000 C   0  0  0  0  0  0  0        0  0  0
    1.8110    0.0000    0.0000 C   0  0  0  0  0  0  0        0  0  0
    1.8110    1.0420    0.0000 C   0  0  0  0  0  0  0        0  0  0
    1.1560    5.8202    0.0000 C   0  0  0  0  0  0  0        0  0  0
    1.1560    4.8577    0.0000 C   0  0  0  0  0  0  0        0  0  0
    2.5255    1.0718    0.0000 Cl  0  0  0  0  0  0  0        0  0  0
    0.5656    2.9473    0.0000 Cl  0  0  0  0  0  0  0        0  0  0
    0.1637    4.8577    0.0000 Cl  0  0  0  0  0  0  0        0  0  0
    2.4115    3.0069    0.0000 Cl  0  0  0  0  0  0  0        0  0  0
    0.3126    0.5210    0.0000 C   0  0  0  0  0  0  0        0  0  0
    2.5454    5.3439    0.0000 C   0  0  0  0  0  0  0        0  0  0
    2.7092    0.5260    0.0000 C   0  0  0  0  0  0  0        0  0  0
    0.3126    5.3439    0.0000 C   0  0  0  0  0  0  0        0  0  0
    0.0099    1.0420    0.0000 C   0  0  0  0  0  0  0        0  0  0
    0.0000    0.0000    0.0000 C   0  0  0  0  0  0  0        0  0  0
    2.8232    4.8577    0.0000 C   0  0  0  0  0  0  0        0  0  0
    2.8232    5.8202    0.0000 C   0  0  0  0  0  0  0        0  0  0
    1.7119    4.8577    0.0000 C   0  0  0  0  0  0  0        0  0  0
 14  1  1  0     0  0
 15  1  1  0     0  0
 16  2  1  0     0  0
 17  2  1  0     0  0
 18  3  1  0     0  0
 19  4  1  0     0  0
 20  5  1  0     0  0
 21  6  1  0     0  0
 22 18  1  0     0  0
 23 18  1  0     0  0
 24 19  1  0     0  0
 25 19  1  0     0  0
  5 10  1  0     0  0
  8 10  1  0     0  0
  5 11  1  0     0  0
  7 11  1  0     0  0
  7  3  1  0     0  0
  3  8  1  0     0  0
  6 13  1  0     0  0
  6 12  1  0     0  0
 12  9  1  0     0  0
  9  4  1  0     0  0
 26 13  1  0     0  0
  4 26  1  0     0  0
 13  2  1  0     0  0
 26  2  1  0     0  0
  4  2  1  0     0  0
  9  2  1  0     0  0
 12  2  1  0     0  0
  6  2  1  0     0  0
  3  1  1  0     0  0
  7  1  1  0     0  0
 11  1  1  0     0  0
  5  1  1  0     0  0
 10  1  1  0     0  0
  8  1  1  0     0  0
M  END

END;

$ru_cymene_disordered=<<<END
[Cl-].[Cl-].[Cl-].[Cl-].[Ru++].[Ru++].CC(C)c1ccc(C)cc1.CC(C)c1ccc(C)cc1
open enventory Sun, 11.12.2011 22:22:01

 26 36  0  0  0  0  0  0  0  0999 V2000
    1.4895    2.0258    0.0000 Ru  0  0  0  0  0  0  0  0  0  0  0  0
    1.4547    3.8281    0.0000 Ru  0  0  0  0  0  0  0  0  0  0  0  0
    0.9086    0.5213    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.9910    5.3474    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.1101    0.5213    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.8738    5.3474    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.2065    1.0427    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.2065    0.0000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.7130    5.8241    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.7130    4.8609    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.8122    0.0000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.8122    1.0427    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.1568    5.8241    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.1568    4.8609    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.5272    1.0725    0.0000 Cl  0  0  0  0  0  0  0  0  0  0  0  0
    0.5660    2.9493    0.0000 Cl  0  0  0  0  0  0  0  0  0  0  0  0
    0.1638    4.8609    0.0000 Cl  0  0  0  0  0  0  0  0  0  0  0  0
    2.4131    3.0089    0.0000 Cl  0  0  0  0  0  0  0  0  0  0  0  0
    0.3128    0.5213    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.5471    5.3474    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.7110    0.5263    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.3128    5.3474    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.0099    1.0427    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.0000    0.0000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.8251    4.8609    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.8251    5.8241    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  3  1  1  0  0  0  0
  4  2  1  0  0  0  0
  5  1  1  0  0  0  0
  6  2  1  0  0  0  0
  7  1  1  0  0  0  0
  8  1  1  0  0  0  0
  9  2  1  0  0  0  0
 10  2  1  0  0  0  0
 11  1  1  0  0  0  0
 12  1  1  0  0  0  0
 13  2  1  0  0  0  0
 14  2  1  0  0  0  0
 15  1  1  0  0  0  0
 16  1  1  0  0  0  0
 17  2  1  0  0  0  0
 18  2  1  0  0  0  0
 19  3  1  0  0  0  0
 20  4  1  0  0  0  0
 21  5  1  0  0  0  0
 22  6  1  0  0  0  0
 23 19  1  0  0  0  0
 24 19  1  0  0  0  0
 25 20  1  0  0  0  0
 26 20  1  0  0  0  0
  5 11  1  0  0  0  0
  8 11  1  0  0  0  0
  5 12  1  0  0  0  0
  7 12  1  0  0  0  0
  7  3  1  0  0  0  0
  3  8  1  0  0  0  0
 10 14  1  0  0  0  0
  6 14  1  0  0  0  0
  6 13  1  0  0  0  0
 13  9  1  0  0  0  0
  4 10  1  0  0  0  0
  9  4  1  0  0  0  0
M  END

END;

$mol_ru_cymene_ordered=readMolfile($ru_cymene_ordered,array("forStructureSearch" => false, ));
$mol_ru_cymene_disordered=readMolfile($ru_cymene_disordered,array("forStructureSearch" => false, ));

checkSubstructure($mol_ru_cymene_ordered,$mol_ru_cymene_disordered,array("identifier" => "Ru arene complex", "FP" => true, "substruct" => true, "smiles_stereo" => true, "smiles" => true, ));

echo "<h2>".s("checks_complete")."<br>".$sub_count." substructure searches, ".($sub_time/$sub_count)."&nbsp;s per search, ".($sub_count/$sub_time)." per s".<<<END
</h2>
</body>
</html>
END;

completeDoc();
?>