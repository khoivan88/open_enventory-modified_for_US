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
require_once "lib_reaction_mapping.php";

function getAtomInfoText(& $molecule,$atom_no) {
	$atom=& $molecule["atoms"][$atom_no];
	// Num, symbol, charge, isotope
	$retval="";
	if ($atom["iso"]) {
		$retval.="<sup>".round($atom["m"])."</sup>";
	}
	$retval.=$atom["s"]."<sup>".getChargeText($atom["o_c"])."</sup>(".$atom_no.")";
	return $retval;
}

function getAtomInfo(& $molecule,$atom_no) {
	$atom=& $molecule["atoms"][$atom_no];
	$retval=getAtomInfoText($molecule,$atom_no)."[";
	
	// Neighbours (bond, num, symbol, charge, isotope)
	$neighbour_info=array();
	for ($a=0;$a<count($atom[NEIGHBOURS]);$a++) {
		$neighbour_atom_no=$atom[NEIGHBOURS][$a];
		$neighbour_info[]=SMaddBond($molecule["bondsFromNeighbours"][$atom_no][$neighbour_atom_no][ORIG_BOND_ORDER]).getAtomInfoText($molecule,$neighbour_atom_no);
	}
	$retval.=join(",",$neighbour_info)."]<br>";
	return $retval;
}

/*--------------------------------------------------------------------------------------------------
/ Function: checkReactionMapping
/
/ Purpose: applies comparison functions like similarity (=> fingerprints), substructure and identity (stereo and non-stereo) on molecules passed as data structures and compares with predefined results
/
/ Parameter:
/ 		& $reaction : reaction data structure to be mapped
/ 		$assignment_table : assignment should be like this
/ 		$paramHash
/
/ Return : no return value, direct output
/------------------------------------------------------------
/ History:
/ 2010-01-10 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function checkReactionMapping(& $reaction,$assignment_tables,$paramHash) {
	if (isset($_REQUEST["identifier"])){
		if ($_REQUEST["identifier"]!=$paramHash["identifier"]) {
			return;
		}
		// return image
		$paramHash["drawAssignment"]=true;
		mapReaction($reaction,$paramHash);
		header(getHeaderFromMime(getMimeFromExt("gif")));
		echo getReactionGif($reaction,rxn_gif_x,rxn_gif_y,0,1,6);
		return;
	}
	
	mapReaction($reaction,$paramHash);
	
	if (!in_array($reaction["assignment_table"],$assignment_tables)) {
		echo "Problem with ".$paramHash["identifier"].":".
		"<img src=\"check_reaction_mapping.php?identifier=".$paramHash["identifier"]."&no_cache=".time()."\">".
		"<br clear=\"all\">";
		// costs twice the cpu time, but ok
		
		// build $atom_map_inverted
		$atom_map_inverted=array();
		$idx=0;
		for ($mol_reactant=0;$mol_reactant<$reaction["reactants"];$mol_reactant++) {
			$reactant=& $reaction["molecules"][$mol_reactant];
			
			for ($reac_atom_no=0;$reac_atom_no<count($reactant["atoms"]);$reac_atom_no++) {
				$atom_map_inverted[$idx]=array($mol_reactant,$reac_atom_no);
				$idx++;
			}
		}
		
		// show differences
		foreach ($assignment_tables[0] as $mol_product => $subtable) {
			foreach ($subtable as $prod_atom_no => $matching_atom_no) {
				$exp_match=$reaction["assignment_table"][$mol_product][$prod_atom_no];
				if ($exp_match==$matching_atom_no) {
					continue;
				}
				
				// Info on atom $prod_atom_no and $matching_atom_no
				list($mol_reactant,$reac_atom_no)=$atom_map_inverted[$matching_atom_no];
				$prod_atom_info=getAtomInfo($reaction["molecules"][ $mol_product+$reaction["reactants"] ],$prod_atom_no);
				$true_atom_info=getAtomInfo($reaction["molecules"][$mol_reactant],$reac_atom_no);
				if (empty($exp_match)) { // not matched
					echo "Did not assign ".$prod_atom_info."Should have been ".$true_atom_info;
				}
				else { // matched wrong
					list($wrong_mol_reactant,$wrong_reac_atom_no)=$atom_map_inverted[$exp_match];
					echo $prod_atom_info." was matched (".$reaction["assignment_quality"][$mol_product][$prod_atom_no].") to ".getAtomInfo($reaction["molecules"][$wrong_mol_reactant],$wrong_reac_atom_no)."Should have been ".$true_atom_info;
				}
			}
		}
		echo "If this is false alarm (check image carefully), you can add <br>";
		var_export($reaction["assignment_table"]);
		echo "<hr>";
	}
	else {
		echo $paramHash["identifier"]." is correct<hr>";
	}
}

if (isset($_REQUEST["identifier"])) {
	pageHeader(true,false,true,false);
}
else {
	pageHeader();
	echo stylesheet."</head>
	<body>".s("check_for_errors").":<br>";
}

$malonate='$RXN


open enventory Fri, 12.06.2009 10:28:01
  5  1
$MOL
Cc1cccc(C)c1Br
open enventory Sat, 24.05.2008 21:31:17

  9  9  0  0  0  0  0  0  0  0999 V2000
    5.2578   -2.6398    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.5435   -2.2290    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.2562   -3.4636    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.9778   -2.2226    0.0000 Br  0  0  0  0  0  0  0  0  0  0  0  0
    3.8280   -3.4664    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.8277   -2.6379    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.5423   -3.8771    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.5450   -1.4010    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.9751   -3.8892    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  2  1  2  0  0  0  0
  3  1  1  0  0  0  0
  4  1  1  0  0  0  0
  5  7  1  0  0  0  0
  6  2  1  0  0  0  0
  7  3  2  0  0  0  0
  8  2  1  0  0  0  0
  9  3  1  0  0  0  0
  5  6  2  0  0  0  0
M  END
$MOL
CCOC(=O)CC(=O)OCC
open enventory Sat, 24.05.2008 21:32:31

 11 10  0  0  0  0  0  0  0  0999 V2000
    5.1292   -2.4375    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.4125   -2.0250    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.8417   -2.0250    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.4125   -1.1958    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    5.8417   -1.1958    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    6.5583   -2.4375    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    3.7000   -2.4375    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    2.9833   -2.0250    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.2667   -2.4375    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.2725   -2.0245    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.9873   -2.4366    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  3  1  1  0  0  0  0
  4  2  2  0  0  0  0
  5  3  2  0  0  0  0
  6  3  1  0  0  0  0
  7  2  1  0  0  0  0
  8  7  1  0  0  0  0
  9  8  1  0  0  0  0
  6 10  1  0  0  0  0
  2  1  1  0  0  0  0
 10 11  1  0  0  0  0
M  END
$MOL
[Pd@@]234789([C@H](c1ccccc1)[C@@H]2C(=O)[C@H]3[C@@H]4c5ccccc5)[C@H](c6ccccc6)[C@@H]7C(=O)[C@H]8[C@@H]9c%10ccccc%10
open enventory Sat, 24.05.2008 21:31:33

 37 46  0  0  0  0  0  0  0  0999 V2000
    4.9042   -2.6167    0.0000 Pd  0  0  0  0  0  0  0  0  0  0  0  0
    5.1542   -0.6917    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.1542   -0.4500    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.7542   -4.8375    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.5750   -4.5542    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.0167   -1.0125    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.7125   -0.5292    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.9250   -4.7417    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.9167   -4.1625    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.7292   -0.2917    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.0875   -5.0250    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.8667    0.2875    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    4.9667   -5.4167    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    6.3750   -0.8292    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.4625   -1.1667    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.4375   -4.2542    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.5625   -3.9792    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.3167   -1.7292    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.2375   -1.3917    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.9292   -0.6542    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.0417   -0.7667    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.1000   -4.1000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.6000   -3.9792    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.6792   -3.6542    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.8625   -4.1125    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.7625   -1.9042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.6375   -1.7917    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.3542   -1.0542    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.4917   -0.9250    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.7750   -3.8875    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.3917   -3.6042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.1125   -3.4667    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.3292   -3.4500    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.3292   -1.4792    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.2042   -1.6375    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.4792   -3.2292    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.9125   -3.5875    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  2  1  1  6  0  0  0
  3  1  1  6  0  0  0
  4  1  1  6  0  0  0
  5  1  1  6  0  0  0
  6  1  1  6  0  0  0
  7  1  1  6  0  0  0
  8  1  1  6  0  0  0
  9  1  1  6  0  0  0
 10  3  1  0  0  0  0
 11  5  1  0  0  0  0
 12 10  2  0  0  0  0
 13 11  2  0  0  0  0
 14  7  1  0  0  0  0
 15  6  1  0  0  0  0
 16  8  1  0  0  0  0
 17  9  1  0  0  0  0
 18 15  1  0  0  0  0
 19 14  2  0  0  0  0
 20 14  1  0  0  0  0
 21 15  2  0  0  0  0
 22 17  1  0  0  0  0
 23 16  1  0  0  0  0
 24 17  2  0  0  0  0
 25 16  2  0  0  0  0
 26 18  2  0  0  0  0
 27 19  1  0  0  0  0
 28 20  2  0  0  0  0
 29 21  1  0  0  0  0
 30 22  2  0  0  0  0
 31 25  1  0  0  0  0
 32 23  2  0  0  0  0
 33 24  1  0  0  0  0
 34 29  2  0  0  0  0
 35 28  1  0  0  0  0
 36 31  2  0  0  0  0
 37 33  2  0  0  0  0
  9  4  1  0  0  0  0
  8  5  1  0  0  0  0
  7  2  1  0  0  0  0
  6  3  1  0  0  0  0
  4 11  1  0  0  0  0
  2 10  1  0  0  0  0
 37 30  1  0  0  0  0
 36 32  1  0  0  0  0
 35 27  2  0  0  0  0
 26 34  1  0  0  0  0
M  END
$MOL
CC(C)(C)P(C(C)(C)C)C(C)(C)C
open enventory Sat, 24.05.2008 21:30:29

 13 12  0  0  0  0  0  0  0  0999 V2000
    5.1250   -2.8292    0.0000 P   0  0  0  0  0  0  0  0  0  0  0  0
    5.1250   -2.0000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.4125   -3.2417    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.8375   -3.2417    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.9958   -2.5208    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.8292   -3.9625    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.6917   -3.6583    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.2542   -2.5208    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.4208   -3.9625    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.5583   -3.6583    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.3000   -2.0000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.9500   -2.0000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.1250   -1.1750    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  2  1  1  0  0  0  0
  3  1  1  0  0  0  0
  4  1  1  0  0  0  0
  5  3  1  0  0  0  0
  6  3  1  0  0  0  0
  7  3  1  0  0  0  0
  8  4  1  0  0  0  0
  9  4  1  0  0  0  0
 10  4  1  0  0  0  0
 11  2  1  0  0  0  0
 12  2  1  0  0  0  0
 13  2  1  0  0  0  0
M  END
$MOL
[K]OP(=O)(O[K])O[K]
  Marvin  07210810312D          

  8  4  0  0  0  0            999 V2000
    1.9012   -0.6135    0.0000 P   0  0  0  0  0  0  0  0  0  0  0  0
    1.1625   -0.9809    0.0000 O   0  5  0  0  0  0  0  0  0  0  0  0
    2.6399   -0.9809    0.0000 O   0  5  0  0  0  0  0  0  0  0  0  0
    1.9012    0.1252    0.0000 O   0  5  0  0  0  0  0  0  0  0  0  0
    1.9012   -1.3522    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    2.7696   -0.4420    0.0000 K   0  3  0  0  0  0  0  0  0  0  0  0
    1.0018   -0.4125    0.0000 K   0  3  0  0  0  0  0  0  0  0  0  0
    1.8857    0.7366    0.0000 K   0  3  0  0  0  0  0  0  0  0  0  0
  2  1  1  0  0  0  0
  3  1  1  0  0  0  0
  4  1  1  0  0  0  0
  5  1  2  0  0  0  0
M  CHG  6   2  -1   3  -1   4  -1   6   1   7   1   8   1
M  END
$MOL
CCOC(=O)C(C(=O)OCC)c1c(C)cccc1C
open enventory Fri, 17.10.2008 11:18:41

 19 19  0  0  0  0  0  0  0  0999 V2000
    5.0972    0.2063    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.3827   -0.2062    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.3827   -1.0312    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.0972   -1.4438    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.8117   -1.0312    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.8117   -0.2062    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.0972    1.0313    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.8117    1.4438    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.3827    1.4438    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.8117    2.2688    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    6.5261    1.0313    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    3.6683    1.0313    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    4.3827    2.2688    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    2.9538    1.4438    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.2393    1.0313    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.5261    0.2063    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.2406   -0.2062    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.6682    0.2063    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.2242    0.5083    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  1  2  1  0  0  0  0
  1  6  2  0  0  0  0
  1  7  1  0  0  0  0
  2  3  2  0  0  0  0
  2 18  1  0  0  0  0
  3  4  1  0  0  0  0
  4  5  2  0  0  0  0
  5  6  1  0  0  0  0
  6 19  1  0  0  0  0
  7  8  1  0  0  0  0
  7  9  1  0  0  0  0
  8 10  2  0  0  0  0
  8 11  1  0  0  0  0
  9 12  1  0  0  0  0
  9 13  2  0  0  0  0
 11 16  1  0  0  0  0
 12 14  1  0  0  0  0
 14 15  1  0  0  0  0
 16 17  1  0  0  0  0
M  END';

$ass_malonate=array(
array( 0 => array( 10 => 14, 7 => 11, 6 => 9, 0 => 10, 9 => 13, 15 => 18, 16 => 19, 11 => 15, 13 => 16, 14 => 17, 2 => 45, 1 => 37, 3 => 53, 4 => 48, 5 => 40, 17 => 61, 18 => 62, 12 => 12, 8 => 10, ), ), 
array( 0 => array( 13 => 16, 11 => 15, 8 => 10, 6 => 9, 7 => 11, 9 => 13, 10 => 14, 15 => 18, 16 => 19, 12 => 12, 14 => 17, 3 => 4, 2 => 6, 1 => 2, 0 => 0, 5 => 1, 4 => 5, 18 => 7, 17 => 8, ), ), 
array( 0 => array( 13 => 16, 11 => 15, 8 => 10, 6 => 9, 7 => 11, 9 => 13, 10 => 14, 15 => 18, 16 => 19, 12 => 12, 14 => 17, 0 => 0, 1 => 1, 2 => 5, 3 => 4, 4 => 6, 5 => 2, 18 => 8, 17 => 7, ), ), 
);

$rxn_malonate=readRxnfile($malonate);
$paramHash["identifier"]="Malonate";
checkReactionMapping($rxn_malonate,$ass_malonate,$paramHash);

$hydroamidation='$RXN


open enventory Mon, 15.06.2009 15:01:37
  5  1
$MOL
O=C1CCCCCN1
open enventory Sat, 24.05.2008 21:30:32

  8  8  0  0  0  0  0  0  0  0999 V2000
    3.7958   -2.6125    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.4412   -2.1042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.2449   -2.2875    0.0000 N   0  0  0  0  0  0  0  0  0  0  0  0
    5.5990   -3.0250    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.7958   -3.4417    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.2454   -3.7708    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.4412   -3.9542    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    6.4240   -3.0230    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
  1  5  1  0  0  0  0
  4  6  1  0  0  0  0
  2  3  1  0  0  0  0
  5  7  1  0  0  0  0
  1  2  1  0  0  0  0
  6  7  1  0  0  0  0
  3  4  1  0  0  0  0
  4  8  2  0  0  0  0
M  END
$MOL
CCCCC#C
open enventory Sat, 24.05.2008 21:30:35

  6  5  0  0  0  0  0  0  0  0999 V2000
    6.5668   -3.9048    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    7.2897   -4.3230    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.8559   -3.4957    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.1438   -3.9078    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.4287   -3.4946    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.7165   -3.9067    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  2  1  3  0  0  0  0
  3  1  1  0  0  0  0
  4  3  1  0  0  0  0
  5  4  1  0  0  0  0
  6  5  1  0  0  0  0
M  END
$MOL
[Cl-].[Cl-].[Cl-].[Ru+++]
  Marvin  05280812482D          

  7  0  0  0  0  0            999 V2000
    4.4500   -2.9750    0.0000 Cl  0  5  0  0  0  0  0  0  0  0  0  0
    4.4500   -1.9750    0.0000 Cl  0  5  0  0  0  0  0  0  0  0  0  0
    4.4500   -3.9750    0.0000 Cl  0  5  0  0  0  0  0  0  0  0  0  0
    3.1875   -2.9625    0.0000 Ru  0  1  0  0  0  0  0  0  0  0  0  0
    1.2964   -1.9446    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    1.4437   -2.8580    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
    1.6795   -3.7714    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
M  CHG  4   1  -1   2  -1   3  -1   4   3
M  END
$MOL
CCCCP(CCCC)CCCC
open enventory Sat, 24.05.2008 21:30:33

 13 12  0  0  0  0  0  0  0  0999 V2000
    0.0000   -1.4042    0.0000 P   0  0  0  0  0  0  0  0  0  0  0  0
   -1.1250   -1.9667    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.0000   -0.2792    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.1250   -1.9667    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -2.2500   -1.4042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.1250    0.2833    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    2.2458   -1.4042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -3.3750   -1.9667    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.1250    1.4083    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    3.3750   -1.9667    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -4.5000   -1.4042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -2.2500    1.9708    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.5000   -1.4042    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  2  1  1  0  0  0  0
  3  1  1  0  0  0  0
  4  1  1  0  0  0  0
  5  2  1  0  0  0  0
  6  3  1  0  0  0  0
  7  4  1  0  0  0  0
  8  5  1  0  0  0  0
  9  6  1  0  0  0  0
 10  7  1  0  0  0  0
 11  8  1  0  0  0  0
 12  9  1  0  0  0  0
 13 10  1  0  0  0  0
M  END
$MOL
CN(C)c1ccncc1
open enventory Sat, 24.05.2008 21:30:29

  9  9  0  0  0  0  0  0  0  0999 V2000
    5.1417   -1.8458    0.0000 N   0  0  0  0  0  0  0  0  0  0  0  0
    5.1417   -2.6708    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.1417   -4.3250    0.0000 N   0  0  0  0  0  0  0  0  0  0  0  0
    5.8583   -3.0833    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.4292   -3.0833    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.4292   -3.9125    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.8583   -3.9125    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    4.4250   -1.4333    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    5.8542   -1.4333    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  2  1  1  0  0  0  0
  3  6  1  0  0  0  0
  4  2  2  0  0  0  0
  5  2  1  0  0  0  0
  6  5  2  0  0  0  0
  7  4  1  0  0  0  0
  8  1  1  0  0  0  0
  9  1  1  0  0  0  0
  7  3  2  0  0  0  0
M  END
$MOL
CCCC/C=C/N1CCCCCC1=O
open enventory Fri, 20.06.2008 12:23:56

 14 14  0  0  0  0  0  0  0  0999 V2000
   23.1680   -9.2890    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   22.3818   -9.5358    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   21.7013   -9.0778    0.0000 N   0  0  0  0  0  0  0  0  0  0  0  0
   21.6314   -8.2609    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   23.4640   -8.5189    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   22.2330   -7.6931    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   23.0455   -7.8090    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   20.8613   -7.9738    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
   20.9927   -9.5018    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   21.0054  -10.3281    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   20.2938  -10.7509    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   20.3066  -11.5772    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   19.5950  -12.0000    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   19.6077  -12.8262    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
  2  3  1  0  0  0  0
  4  8  2  0  0  0  0
  1  5  1  0  0  0  0
  3  9  1  0  0  0  0
  4  6  1  0  0  0  0
  9 10  2  0  0  0  0
  1  2  1  0  0  0  0
 10 11  1  0  0  0  0
  5  7  1  0  0  0  0
 11 12  1  0  0  0  0
  3  4  1  0  0  0  0
 12 13  1  0  0  0  0
  6  7  1  0  0  0  0
 13 14  1  0  0  0  0
M  END';

$ass_hydroamidation=array(
array( 0 => array( 7 => 7, 3 => 3, 5 => 5, 6 => 6, 4 => 4, 0 => 0, 1 => 1, 2 => 2, 10 => 10, 9 => 8, 8 => 9, 11 => 11, 12 => 12, 13 => 13, ), ),
);

$rxn_hydroamidation=readRxnfile($hydroamidation);
$paramHash["identifier"]="Hydroamidation";
checkReactionMapping($rxn_hydroamidation,$ass_hydroamidation,$paramHash);

$hydrolysis='$RXN



  1  2
$MOL
imes v0.1 pre alpha


  6  5  0  0  0  0            999 V2000
  178.3495 -186.2968    0.0000 C   0  0  0  0  0  0  0               
  208.3495 -186.2968    0.0000 C   0  0  0  0  0  0  0               
  223.3495 -212.2776    0.0000 O   0  0  0  0  0  0  0               
  223.3495 -160.3161    0.0000 O   0  0  0  0  0  0  0               
  253.3495 -160.3160    0.0000 C   0  0  0  0  0  0  0               
  268.3495 -134.3353    0.0000 C   0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
  2  4  1  0  0  0  0
  4  5  1  0  0  0  0
  5  6  1  0  0  0  0
  2  3  2  0  0  0  0
M  END
$MOL
imes v0.1 pre alpha


  3  2  0  0  0  0            999 V2000
  384.3495 -162.8760    0.0000 C   0  0  0  0  0  0  0               
  414.2811 -160.8517    0.0000 C   0  0  0  0  0  0  0               
  431.0000 -185.7611    0.0000 O   0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
  2  3  1  0  0  0  0
M  END
$MOL
imes v0.1 pre alpha


  4  3  0  0  0  0            999 V2000
  579.1149 -148.2483    0.0000 O   0  0  0  0  0  0  0               
  575.6324 -178.0455    0.0000 C   0  0  0  0  0  0  0               
  547.0000 -187.0000    0.0000 C   0  0  0  0  0  0  0               
  597.7035 -198.3646    0.0000 O   0  0  0  0  0  0  0               
  3  2  1  0  0  0  0
  2  4  1  0  0  0  0
  2  1  2  0  0  0  0
M  END';

$ass_hydrolysis=array(
array( 1 => array( 1 => 1, 2 => 0, 0 => 3, 3 => 2, ), 0 => array( 1 => 4, 0 => 5, 2 => 3, ), ), 
array( 1 => array( 1 => 1, 2 => 0, 0 => 2, 3 => 3, ), 0 => array( 1 => 4, 0 => 5, 2 => 3, ), ), 
);

$rxn_hydrolysis=readRxnfile($hydrolysis);
$paramHash["identifier"]="Hydrolysis";
checkReactionMapping($rxn_hydrolysis,$ass_hydrolysis,$paramHash);

$elimination='$RXN



  1  1
$MOL
imes v0.1 pre alpha


  8  7  0  0  0  0            999 V2000
   32.3077  -49.3732    0.0000 C   0  0  0  0  0  0  0               
   58.1163  -34.0791    0.0000 C   0  0  0  0  0  0  0               
   84.2658  -48.7829    0.0000 C   0  0  0  0  0  0  0               
  110.0744  -33.4886    0.0000 C   0  0  0  0  0  0  0               
  136.2240  -48.1925    0.0000 C   0  0  0  0  0  0  0               
  162.0326  -32.8982    0.0000 C   0  0  0  0  0  0  0               
  188.1821  -47.6020    0.0000 C   0  0  0  0  0  0  0               
  213.9907  -32.3077    0.0000 Br  0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
  2  3  1  0  0  0  0
  3  4  1  0  0  0  0
  4  5  1  0  0  0  0
  5  6  1  0  0  0  0
  6  7  1  0  0  0  0
  7  8  1  0  0  0  0
M  END
$MOL
imes v0.1 pre alpha


  7  6  0  0  0  0            999 V2000
  381.9489  -48.4877    0.0000 C   0  0  0  0  0  0  0               
  407.7575  -33.1934    0.0000 C   0  0  0  0  0  0  0               
  459.7157  -32.6029    0.0000 C   0  0  0  0  0  0  0               
  433.9070  -47.8971    0.0000 C   0  0  0  0  0  0  0               
  485.8651  -47.3068    0.0000 C   0  0  0  0  0  0  0               
  355.7992  -33.7837    0.0000 C   0  0  0  0  0  0  0               
  329.9907  -49.0780    0.0000 C   0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
  6  1  1  0  0  0  0
  7  6  1  0  0  0  0
  2  4  1  0  0  0  0
  4  3  1  0  0  0  0
  3  5  2  0  0  0  0
M  END
';

$ass_elimination=array(
array( 0 => array( 0 => 2, 1 => 3, 3 => 4, 2 => 5, 4 => 6, 5 => 1, 6 => 0, ), ), 
);

$rxn_elimination=readRxnfile($elimination);
$paramHash["identifier"]="Elimination";
checkReactionMapping($rxn_elimination,$ass_elimination,$paramHash);

$permethylation='$RXN



  2  1
$MOL
imes v0.1 pre alpha


 11 11  0  0  0  0            999 V2000
  144.0000 -272.9519    0.0000 C   0  0  0  0  0  0  0               
  174.0000 -220.9904    0.0000 C   0  0  0  0  0  0  0               
  174.0000 -272.9519    0.0000 C   0  0  0  0  0  0  0               
  189.0000 -246.9712    0.0000 C   0  0  0  0  0  0  0               
  129.0000 -246.9712    0.0000 C   0  0  0  0  0  0  0               
  144.0000 -220.9904    0.0000 C   0  0  0  0  0  0  0               
  219.0000 -246.9712    0.0000 O   0  0  0  0  0  0  0               
  129.0000 -298.9326    0.0000 C   0  0  0  0  0  0  0               
   99.0000 -298.9327    0.0000 O   0  0  0  0  0  0  0               
  144.0000 -324.9134    0.0000 O   0  0  0  0  0  0  0               
  129.0000 -195.0096    0.0000 O   0  0  0  0  0  0  0               
  4  3  2  0  0  0  0
  4  2  1  0  0  0  0
  6  5  1  0  0  0  0
  1  5  2  0  0  0  0
  4  7  1  0  0  0  0
  1  8  1  0  0  0  0
  8 10  1  0  0  0  0
  8  9  2  0  0  0  0
  6  2  2  0  0  0  0
  1  3  1  0  0  0  0
  6 11  1  0  0  0  0
M  END
$MOL
imes v0.1 pre alpha


  2  1  0  0  0  0            999 V2000
  335.0000 -259.9615    0.0000 C   0  0  0  0  0  0  0               
  365.0000 -259.9615    0.0000 I   0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
M  END
$MOL
imes v0.1 pre alpha


 14 14  0  0  0  0            999 V2000
  556.0000 -220.9903    0.0000 C   0  0  0  0  0  0  0               
  526.0000 -272.9518    0.0000 C   0  0  0  0  0  0  0               
  556.0000 -272.9518    0.0000 C   0  0  0  0  0  0  0               
  571.0000 -246.9712    0.0000 C   0  0  0  0  0  0  0               
  526.0000 -220.9903    0.0000 C   0  0  0  0  0  0  0               
  511.0000 -246.9712    0.0000 C   0  0  0  0  0  0  0               
  601.0001 -246.9712    0.0000 O   0  0  0  0  0  0  0               
  616.0001 -272.9519    0.0000 C   0  0  0  0  0  0  0               
  511.0000 -298.9326    0.0000 C   0  0  0  0  0  0  0               
  481.0000 -298.9326    0.0000 O   0  0  0  0  0  0  0               
  526.0000 -324.9134    0.0000 O   0  0  0  0  0  0  0               
  511.0000 -350.8942    0.0000 C   0  0  0  0  0  0  0               
  511.0000 -195.0096    0.0000 O   0  0  0  0  0  0  0               
  526.0000 -169.0288    0.0000 C   0  0  0  0  0  0  0               
  4  7  1  0  0  0  0
  5  6  1  0  0  0  0
  4  3  2  0  0  0  0
  2  3  1  0  0  0  0
  4  1  1  0  0  0  0
  7  8  1  0  0  0  0
  2  9  1  0  0  0  0
  9 11  1  0  0  0  0
  9 10  2  0  0  0  0
 11 12  1  0  0  0  0
  5  1  2  0  0  0  0
  5 13  1  0  0  0  0
 13 14  1  0  0  0  0
  2  6  2  0  0  0  0
M  END';

$ass_permethylation=array(
array ( 0 => array ( 0 => 1, 3 => 3, 6 => 6, 2 => 2, 1 => 0, 8 => 7, 10 => 9, 9 => 8, 5 => 4, 4 => 5, 12 => 10, 7 => 11, 11 => 11, 13 => 11, ), ), 
array ( 0 => array ( 8 => 7, 1 => 0, 2 => 4, 3 => 5, 6 => 10, 0 => 1, 4 => 3, 5 => 2, 12 => 6, 10 => 9, 9 => 8, 7 => 11, 11 => 11, 13 => 11, ), ), 
);

$rxn_permethylation=readRxnfile($permethylation);
$paramHash["identifier"]="Permethylation";
checkReactionMapping($rxn_permethylation,$ass_permethylation,$paramHash);

$benzylic_chlorination_with_challenge='$RXN



  2  1
$MOL
imes v0.1 pre alpha


  2  1  0  0  0  0            999 V2000
  122.0000 -277.9615    0.0000 Cl  0  0  0  0  0  0  0               
  152.0000 -277.9615    0.0000 Cl  0  0  0  0  0  0  0               
  1  2  1  0  0  0  0
M  END
$MOL
imes v0.1 pre alpha


  9  9  0  0  0  0            999 V2000
  313.0000 -290.9520    0.0000 C   0  0  0  0  0  0  0               
  268.0000 -264.9712    0.0000 C   0  0  0  0  0  0  0               
  328.0000 -264.9712    0.0000 C   0  0  0  0  0  0  0               
  283.0000 -290.9520    0.0000 C   0  0  0  0  0  0  0               
  313.0000 -238.9903    0.0000 C   0  0  0  0  0  0  0               
  283.0000 -238.9903    0.0000 N   0  0  0  0  0  0  0               
  328.0000 -213.0096    0.0000 C   0  0  0  0  0  0  0               
  328.0000 -316.9327    0.0000 C   0  0  0  0  0  0  0               
  313.0000 -342.9135    0.0000 Cl  0  0  0  0  0  0  0               
  4  2  2  0  0  0  0
  3  5  1  0  0  0  0
  5  7  1  0  0  0  0
  6  5  2  0  0  0  0
  6  2  1  0  0  0  0
  4  1  1  0  0  0  0
  3  1  2  0  0  0  0
  1  8  1  0  0  0  0
  8  9  1  0  0  0  0
M  END
$MOL
imes v0.1 pre alpha


 10 10  0  0  0  0            999 V2000
  489.0000 -238.9904    0.0000 C   0  0  0  0  0  0  0               
  489.0000 -290.9519    0.0000 C   0  0  0  0  0  0  0               
  459.0000 -290.9519    0.0000 C   0  0  0  0  0  0  0               
  459.0000 -238.9904    0.0000 N   0  0  0  0  0  0  0               
  504.0000 -264.9713    0.0000 C   0  0  0  0  0  0  0               
  444.0000 -264.9713    0.0000 C   0  0  0  0  0  0  0               
  504.0000 -213.0097    0.0000 C   0  0  0  0  0  0  0               
  534.0000 -213.0097    0.0000 Cl  0  0  0  0  0  0  0               
  504.0000 -316.9327    0.0000 C   0  0  0  0  0  0  0               
  488.9999 -342.9134    0.0000 Cl  0  0  0  0  0  0  0               
  3  6  2  0  0  0  0
  4  6  1  0  0  0  0
  1  7  1  0  0  0  0
  7  8  1  0  0  0  0
  5  1  1  0  0  0  0
  4  1  2  0  0  0  0
  3  2  1  0  0  0  0
  5  2  2  0  0  0  0
  2  9  1  0  0  0  0
  9 10  1  0  0  0  0
M  END';

$ass_benzylic_chlorination_with_challenge=array(
array ( 0 => array ( 3 => 7, 5 => 3, 2 => 5, 1 => 2, 4 => 4, 0 => 6, 6 => 8, 8 => 9, 9 => 10, 7 => 0, ), ), 
);

$rxn_benzylic_chlorination_with_challenge=readRxnfile($benzylic_chlorination_with_challenge);
$paramHash["identifier"]="Benzylic chlorination with challenge";
checkReactionMapping($rxn_benzylic_chlorination_with_challenge,$ass_benzylic_chlorination_with_challenge,$paramHash);

/* copy this for new ones
$hydrolysis='';

$ass_hydrolysis=array(

);

$rxn_hydrolysis=readRxnfile($hydrolysis);
$paramHash["identifier"]="Hydrolysis";
checkReactionMapping($rxn_hydrolysis,$ass_hydrolysis,$paramHash);
*/

if (!isset($_REQUEST["identifier"])) {
	echo "<h2>".s("checks_complete").<<<END
</h2>
</body>
</html>
END;
}

completeDoc();
?>