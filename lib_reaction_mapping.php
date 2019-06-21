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

require_once "lib_molecule_substructure.php";

function qualityDown(& $match_quality,$mismatch_price,$path_length,$paramHash) { // Qualität verschlechtern, Auswirkungen geringer, wenn Pfad länger (Abweichung weiter weg)
	$match_quality=$match_quality-(FIXED_ERROR_MALUS+HYPERBOLIC_DISTANCE_MALUS/$path_length)*$mismatch_price;
	return ($match_quality<QUALITY_THRESHOLD);
}

function matchPathsRecursiveLoose(& $needle,& $haystackMolecule,$needlePath,$haystackPath,$match_quality,& $paramHash) { // & $part_matchmat,
	//~ $show_debug=($needlePath[0]==2 && in_array($haystackPath[0],array(1,5)));
	
	// prüft rekursiv zwei Pfade in needle und haystack gegeneinander, mit Matrixprüfung
	$path_length=count($needlePath);
	if ($path_length!=count($haystackPath)) {
		return false;
	}
	$lastNeedle=end($needlePath);
	$lastHaystack=end($haystackPath);
	
	$full_match=true;
	
	// Atom
	if (!matchAtoms($needle["atoms"][ $lastNeedle ],$haystackMolecule["atoms"][ $lastHaystack ])) { // Atome passen nicht oder Haystack hat zuwenige Nachbarn, um für needle zu passen
		//~ return false;
		//~ if ($show_debug) echo __LINE__.": ".$haystackPath[0]."/".$lastHaystack."=>".$match_quality."\n";
		if (qualityDown($match_quality,ATOM_MISMATCH_PRICE,$path_length,$paramHash)) return false;
		$full_match=false;
	}
	
	// Member in ring(s), both the same?
	
	
	// Bindung
	if ($path_length>1) {
		$prevNeedle=prev($needlePath);
		$prevHaystack=prev($haystackPath);
		$oldNeighbours=1; // Zahl der bereits besuchten Nachbarn
		
		//~ if (!matchBonds($needle["bondsFromNeighbours"][ $lastNeedle ][ $prevNeedle ],$haystackMolecule["bondsFromNeighbours"][ $lastHaystack ][ $prevHaystack ],$paramHash)) {
		if (!matchBonds($needle,$lastNeedle,$prevNeedle,$haystackMolecule,$lastHaystack,$prevHaystack)) {
			//~ return false; // Letzte Bindungen passen nicht
			//~ if ($show_debug) echo __LINE__.": ".$haystackPath[0]."/".$lastHaystack."=>".$match_quality."\n";
			qualityDown($match_quality,BOND_MISMATCH_PRICE,$path_length,$paramHash);
			//~ if (qualityDown($match_quality,BOND_MISMATCH_PRICE,$path_length,$paramHash)) return false;
			$full_match=false;
		}
		elseif (floatval($needle["bondsFromNeighbours"][ $lastNeedle ][ $prevNeedle ][ORIG_BOND_ORDER])!=floatval($haystackMolecule["bondsFromNeighbours"][ $lastHaystack ][ $prevHaystack ][ORIG_BOND_ORDER])) { // not exactly matching
			qualityDown($match_quality,STRICT_BOND_MISMATCH_PRICE,$path_length,$paramHash);
			//~ if (qualityDown($match_quality,STRICT_BOND_MISMATCH_PRICE,$path_length,$paramHash)) return false;
			$full_match=false;
		}
	}
	else {
		$prevNeedle=-1;
		$prevHaystack=-1;
		$oldNeighbours=0; // Zahl der bereits besuchten Nachbarn
		//~ $part_matchmat=array(); // reset
	}
	
	// Hs+Neighbours
	if ($needle["atoms"][ $lastNeedle ][IMPLICIT_H]+$needle["atoms"][ $lastNeedle ][NON_H_NEIGHBOURS]!=$haystackMolecule["atoms"][ $lastHaystack ][IMPLICIT_H]+$haystackMolecule["atoms"][ $lastHaystack ][NON_H_NEIGHBOURS]) {
		//~ if ($show_debug) echo __LINE__.": ".$haystackPath[0]."/".$lastHaystack."=>".$match_quality."\n";
		qualityDown($match_quality,NEIGHBOUR_MISMATCH_PRICE,$path_length,$paramHash);
		//~ if (qualityDown($match_quality,NEIGHBOUR_MISMATCH_PRICE,$path_length,$paramHash)) return false;
		$full_match=false;
	}
	
	// Ar
	if ($needle["atoms"][ $lastNeedle ]["ar"]!=$haystackMolecule["atoms"][ $lastHaystack ]["ar"]) {
		//~ if ($show_debug) echo __LINE__.": ".$haystackPath[0]."/".$lastHaystack."=>".$match_quality."\n";
		qualityDown($match_quality,AR_MISMATCH_PRICE,$path_length,$paramHash);
		//~ if (qualityDown($match_quality,AR_MISMATCH_PRICE,$path_length,$paramHash)) return false;
		$full_match=false;
	}
	
	// Nachbarn
	if ($needle["atoms"][ $lastNeedle ][NON_H_NEIGHBOURS]!=$haystackMolecule["atoms"][ $lastHaystack ][NON_H_NEIGHBOURS]) {
		//~ if ($show_debug) echo __LINE__.": ".$haystackPath[0]."/".$lastHaystack."=>".$match_quality."\n";
		qualityDown($match_quality,NON_H_NEIGHBOUR_MISMATCH_PRICE,$path_length,$paramHash);
		//~ if (qualityDown($match_quality,NON_H_NEIGHBOUR_MISMATCH_PRICE,$path_length,$paramHash)) return false;
	}
	
	// Ringprüfung, wenn ja return true
	$haystackRingPos=false;
	$needleRingPos=false;
	for ($a=$path_length-2;$a>=0;$a--) {
		if ($haystackPath[$a]==$lastHaystack) {
			$haystackRingPos=$a;
			break;
		}
	}
	for ($a=$path_length-2;$a>=0;$a--) {
		if ($needlePath[$a]==$lastNeedle) {
			$needleRingPos=$a;
			break;
		}
	}
	if ($haystackRingPos!==FALSE && $haystackRingPos===$needleRingPos) {
		//~ $part_matchmat[$lastNeedle][$lastHaystack]=true;
		//~ if ($show_debug) echo __LINE__.": ".$haystackPath[0]."/".$lastHaystack."=>".$match_quality."\n";
		return $match_quality;
	}
	elseif ($haystackRingPos!==$needleRingPos) {
		//~ return false;
		//~ if ($show_debug) echo __LINE__.": ".$haystackPath[0]."/".$lastHaystack."=>".$match_quality."\n";
		if (qualityDown($match_quality,RING_MISMATCH_PRICE,$path_length,$paramHash)) return false;
	}
	
	// Ende erreicht
	if (count($needle["atoms"][ $lastNeedle ][NEIGHBOURS])==$oldNeighbours) { // nur das atom wo wir herkommen
		//~ $part_matchmat[$lastNeedle][$lastHaystack]=true;
		if (count($haystackMolecule["atoms"][ $lastHaystack ][NEIGHBOURS])!=$oldNeighbours) {
			qualityDown($match_quality,END_MISMATCH_PRICE,$path_length,$paramHash);
			//~ if (qualityDown($match_quality,END_MISMATCH_PRICE,$path_length,$paramHash)) return false;
		}
		elseif (
			$needle["bondsFromNeighbours"][ $lastNeedle ][ $prevNeedle ][ORIG_BOND_ORDER]==1 
			&& 
			$needle["atoms"][ $lastNeedle ][ATOMIC_SYMBOL]=="C" 
			&& 
			$full_match==true
		) { // C-C only
			$match_quality+=CHAIN_END_MATCH_BONUS;
		}
		if ($full_match) {
			$match_quality+=FULL_MATCH_BONUS;
		}
		//~ if ($show_debug) echo __LINE__.": ".$haystackPath[0]."/".$lastHaystack."=>".$match_quality."\n";
		return $match_quality;
	}
	
	if ($full_match) {
		$match_quality+=FULL_MATCH_BONUS;
	}
	
	// rekursiv alle jeweiligen Nachbarn miteinander vergleichen
	$ndlMatSub=0;
	$matchmat=array();
	for ($a=0;$a<count($needle["atoms"][ $lastNeedle ][NEIGHBOURS]);$a++) { // Needle neighbours
		// echo "A".$a." pn".$prevNeedle."\n";
		$thisNeedleAtom=$needle["atoms"][ $lastNeedle ][NEIGHBOURS][$a];
		if ($thisNeedleAtom!=$prevNeedle) { // ist es NICHT das atom, von dem wir kommen
   			$needlePath[$path_length]=$thisNeedleAtom; // ans Ende des Pfads setzen
   			$somethingFound=false;
			$hstMatSub=0;
			for ($b=0;$b<count($haystackMolecule["atoms"][ $lastHaystack ][NEIGHBOURS]);$b++) { // Haystack neighbours
				// echo "B".$b.": ".join(",",$haystackPath)."\n";
				$thisHaystackAtom=$haystackMolecule["atoms"][ $lastHaystack ][NEIGHBOURS][$b];
				if ($thisHaystackAtom!=$prevHaystack) { // ist es NICHT das atom, von dem wir kommen
					// echo "matching ".$thisNeedleAtom."x".$thisHaystackAtom."\n";
					$haystackPath[$path_length]=$thisHaystackAtom; // ans Ende des Pfads setzen
					$match_result=matchPathsRecursiveLoose($needle,$haystackMolecule,$needlePath,$haystackPath,$match_quality,$paramHash);
					if ($match_result) { // $part_matchmat,
						//~ $matchmat[$a-$ndlMatSub][$b-$hstMatSub]=$match_quality*(1-MATCH_RESULT_WEIGHT)+$match_result*MATCH_RESULT_WEIGHT;
						$matchmat[$a-$ndlMatSub][$b-$hstMatSub]=$match_result;
						$somethingFound=true;
					}
				}
				else { // spalte überspringen
					$hstMatSub++;
				}
			}
		}
		 else { // spalte überspringen
			$ndlMatSub++;
		}
	}
	$matrix_result=matrixCheckLoose($matchmat,count($needle["atoms"][ $lastNeedle ][NEIGHBOURS])-$oldNeighbours,count($haystackMolecule["atoms"][ $lastHaystack ][NEIGHBOURS])-$oldNeighbours);
	if ($matrix_result) { // durchgekommen, ok
		//~ if ($show_debug) echo __LINE__.": ".$haystackPath[0]."/".$lastHaystack."=>".$match_quality."\n";
		return $matrix_result; // wie verrechnen??
	}
	//~ return false;
	//~ if ($show_debug) echo __LINE__.": ".$haystackPath[0]."/".$lastHaystack."=>".$match_quality."\n";
	if (qualityDown($match_quality,MATRIX_MISMATCH_PRICE,$path_length,$paramHash)) return false;
	return $match_quality;
}

function matrixCheckLoose(& $matrix,$dimNeedle,$dimHaystack) {
	// prüft anhand einer Matrix, ob Pfade wirklich ok sind
	if ($dimNeedle>$dimHaystack) {
		return 0;
	}
	if ($dimNeedle==1) { // only 1 atom to match
		if (!is_array($matrix[0])) {
			return 0;
		}
		return max($matrix[0]);
	}
	$retval=0;
	for ($a=0;$a<$dimHaystack;$a++) {
		if ($matrix[0][$a]>0) { // hier // [0]
			$newMatrix=array();
			for ($b=1;$b<$dimNeedle;$b++) {
				//~ $d=0;
				for ($c=0;$c<$dimHaystack;$c++) {
					if ($a!=$c) {
						$newMatrix[$b-1][]=$matrix[$b][$c];
						//~ $newMatrix[$b-1][$d++]=$matrix[$b][$c];
					}
				}
			}
			$retval+=$matrix[0][$a]*matrixCheckLoose($newMatrix,$dimNeedle-1,$dimHaystack-1);
		}
	}
	return $retval;
}

function mergeMatrixLoose(& $global_matchmat,& $matchmat) {
	foreach ($matchmat as $a => $line) {
		if (is_array($line)) {
			foreach ($line as $b => $float) {
				if ($float) {
					$global_matchmat[$a][$b]+=$float;
				}
			}
		}
	}
}

function matrixPrintLoose($matrix,$dimNeedle,$dimHaystack) {
	$retval="";
	for ($b=0;$b<$dimNeedle;$b++) { // eine Zeile gehört zu einem Atom von needle
		for ($a=0;$a<$dimHaystack;$a++) {
			//~ $retval.=($matrix[$b][$a]?"X":"O");
			if ($matrix[$b][$a]) {
				$retval.=right(ceil($matrix[$b][$a]*10),1);
			}
			else {
				$retval.="-";
			}
		}
		$retval.="\n";
	}
	return $retval;
}

// tuning of reaction mapping

// identify matches that are clear, give bonus to neighbours
define("GOOD_NETWORK_MATCH",0.8);
define("SINGLE_MATCH_BONUS",0.5); // Bonus atoms that must match
define("MAX_EXCESS_THRESHOLD",0.1); // um diesen Wert muß eine Zuordnung herausstechen, damit die Nachbarn profitieren
define("NEIGHBOUR_BONUS_FACTOR",0.95);
define("NEIGHBOUR_MALUS",0.1);

// chain assigning
define("STABLE_BOND_BONUS",0.16); // bonus for stable bonds in starting material, like CC or nonpolar multiple bonds
define("ASSIGNED_NEIGHBOUR_THRESHOLD",0.3); // lower values stay more likely in existing nets instead of trying a different starting point
define("ASSIGNED_MALUS",0.6); // decrease this value if overstoichiometric reactants are not recognized properly (MeI multiply assigned for permethylation)
// only ONCE

//~ define("PART_COMPLETE_THRESHOLD",0.7);
//~ define("PART_COMPLETE_BONUS_FACTOR",0.08);

// network matching constants
define("FIXED_ERROR_MALUS",0.1); // do not change
define("HYPERBOLIC_DISTANCE_MALUS",0.22); // do not change
define("QUALITY_THRESHOLD",0.12); // decrease this if they right match is not even candidate

define("ATOM_MISMATCH_PRICE",1.1); // 1.1
define("BOND_MISMATCH_PRICE",0.9); // 0.9
define("STRICT_BOND_MISMATCH_PRICE",0.2); // give slight preference for bond orders like drawn
define("NEIGHBOUR_MISMATCH_PRICE",0.9); // too high => error on elimination
define("AR_MISMATCH_PRICE",1.25); // 1.1
define("NON_H_NEIGHBOUR_MISMATCH_PRICE",0.5);
define("RING_MISMATCH_PRICE",1);
define("END_MISMATCH_PRICE",1.6); // 1.1
define("CHAIN_END_MATCH_BONUS",0.7); // min 0.65
define("FULL_MATCH_BONUS",0.05); // do not increase
define("MATRIX_MISMATCH_PRICE",1.05); // 1

// weighing of partial values
define("NETWORK_WEIGHT",0.85);
define("OX_STATE_WEIGHT",0.15);

// Zuordnung ist endgültig
function atomAssign(& $temp_global_matchmat,& $reaction,
	$mol_product,$prod_atom_no,
	$mol_reactant,$reac_atom_no
	) {
	$reac=& $reaction["molecules"][$mol_reactant];
	$prod=& $reaction["molecules"][ $mol_product+$reaction["reactants"] ];
	$reac_atom=& $reac["atoms"][$reac_atom_no];
	$prod_atom=& $prod["atoms"][$prod_atom_no];
	$reac_idx=$reac_atom["idx"];
	
	if (!in_array($reac_idx,$reaction["assignment_table"][$mol_product])) { // if it is in the array, the malus has been subtracted before already
		$assign_malus=true;
	}
	
	// assign
	$prod_atom["RMdone"]=true;
	$reaction["assignment_table"][$mol_product][$prod_atom_no]=$reac_idx;
	$reaction["assignment_quality"][$mol_product][$prod_atom_no]=$temp_global_matchmat[$mol_product][$prod_atom_no][$reac_idx];
	
	// Ring, formed/opened/changed?
	// FIXME
	
	// add bond assignment
	for ($a=0;$a<count($prod_atom[NEIGHBOURS]);$a++) {
		$neighbour_atom_no=$prod_atom[NEIGHBOURS][$a];
		$neighbour_reac_idx=$reaction["assignment_table"][$mol_product][$neighbour_atom_no];
		
		if (!isset($neighbour_reac_idx)) { // not assigned yet
		//~ $neighbour_atom=& $reaction["molecules"][ $mol_product+$reaction["reactants"] ]["atoms"][$neighbour_atom_no];
		//~ if (!$neighbour_atom["RMdone"]) { // not assigned yet
			continue;
		}
		
		// check neighbours of the just-assigned matching partner
		$somethingFound=false;
		$prod_bond=& $prod["bondsFromNeighbours"][$prod_atom_no][$neighbour_atom_no];
		
		for ($b=0;$b<count($reac_atom[NEIGHBOURS]);$b++) {
			$neighbour_reac_atom_no=$reac_atom[NEIGHBOURS][$b];
			
			if ($reaction["molecules"][$mol_reactant]["atoms"][$neighbour_reac_atom_no]["idx"]!=$neighbour_reac_idx) { // probably no relationship
				continue;
			}
			
			$reac_bond=& $reac["bondsFromNeighbours"][$reac_atom_no][$neighbour_reac_atom_no];
			
			//~ if (matchBonds($prod_bond,$reac_bond)) { // be generous
			if (matchBonds($prod,$prod_atom_no,$neighbour_atom_no,$reac,$reac_atom_no,$neighbour_reac_atom_no)) { // be generous
				$somethingFound=true;
				break; // this bond is found and unchanged
			}
			
			$delta=$prod_bond[ORIG_BOND_ORDER]-$reac_bond[ORIG_BOND_ORDER];
			if ($delta>0) {
				$reaction["bond_order_increased"][$mol_product][$delta][]=$prod_bond[BOND_NO];
				$somethingFound=true;
				break; // this bond is found
			}
			else {
				$reaction["bond_order_decreased"][$mol_product][ -$delta ][]=$prod_bond[BOND_NO];
				$somethingFound=true;
				break; // this bond is found
			}
		}
		
		if ($somethingFound) {
			// compare polarities
			$before_pol=getBondPolarity($reac,$reac_bond[BOND_NO]);
			$after_pol=getBondPolarity($prod,$prod_bond[BOND_NO]);
			$tol=0.2; // if the diff is smaller, considered equal
			
			if (abs($before_pol)<$tol && abs($after_pol)>=$tol) {
				// before nonpolar, afterwards polar
				$reaction["bond_polarised"][]=$prod_bond[BOND_NO];
			}
			elseif (abs($after_pol)<$tol && abs($before_pol)>=$tol) {
				// before polar, afterwards nonpolar
				$reaction["bond_unpolarised"][]=$prod_bond[BOND_NO];
			}
			elseif ($before_pol*$after_pol<0) {
				// before polar, afterwards inverted
				$reaction["bond_inverted"][]=$prod_bond[BOND_NO];
			}
			// unchanged(do not record)
		}
		else {
			$reaction["bond_formed"][$mol_product][]=$prod_bond[BOND_NO];
		}
		// $reaction["bond_broken"]=array(Bindungsnummern *reactants*) (zugeordnete(s) Atom(e) vorher mit Bindung, hinterher fehlend) => at the end, we do not know yet
	}
	
	// stats on which parts have much in common
	//~ $reaction["assignment_parts"][$mol_product][$mol_reactant]++;
	//~ $part_complete=$reaction["assignment_parts"][$mol_product][$mol_reactant]/count($reaction["molecules"][$mol_reactant]["atoms"]);
	
	if (is_array($temp_global_matchmat[$mol_product])) foreach ($temp_global_matchmat[$mol_product] as $this_prod_atom_no => $sub_mat) { // go through match matrix for this product
		// decrease match quality for $reac_idx
		if ($assign_malus && isset($temp_global_matchmat[$mol_product][$this_prod_atom_no][$reac_idx])) {
			$temp_global_matchmat[$mol_product][$this_prod_atom_no][$reac_idx]-=ASSIGNED_MALUS;
		}
		
		/*
		// give bonus to other candidates in the same $mol_reactant
		if ($part_complete>PART_COMPLETE_THRESHOLD) {
			for ($this_reac_atom_no=0;$this_reac_atom_no<count($reaction["molecules"][$mol_reactant]["atoms"]);$this_reac_atom_no++) {
				$this_reac_idx=$reaction["molecules"][$mol_reactant]["atoms"][$this_reac_atom_no]["idx"];
				if (
					!in_array($this_reac_idx,$reaction["assignment_table"][$mol_product]) // reactant not assigned in this prod
					&& 
					$temp_global_matchmat[$mol_product][$this_prod_atom_no][$this_reac_idx]>0 // some similarity
				) {
					//~ $temp_global_matchmat[$mol_product][$this_prod_atom_no][$this_reac_idx]+=$part_complete*PART_COMPLETE_BONUS_FACTOR;
					$temp_global_matchmat[$mol_product][$this_prod_atom_no][$this_reac_idx]+=PART_COMPLETE_BONUS_FACTOR/count($reaction["molecules"][$mol_reactant]["atoms"]);
				}
			}
		}
		*/
	}
}

function assignRecursive(& $temp_global_matchmat,& $reaction,& $reac_assigned_in_chain,$mol_product,$prod_atom_no,$mol_reactant,$reac_atom_no,$prev_reac_atom_no=-1) {
	// prepare
	$mol_prod_idx=$mol_product+$reaction["reactants"];
	$prod_atoms=& $reaction["molecules"][$mol_prod_idx]["atoms"];
	if ($prod_atoms[$prod_atom_no]["RMdone"]) {
		return;
	}
	$reac_atoms=& $reaction["molecules"][$mol_reactant]["atoms"];
	
	// is the atom matching one of the neighbours	of $reac_atom_no
	$best_fit=-1;
	$best_neighbour_no=-1;
	for ($a=0;$a<count($reac_atoms[$reac_atom_no][NEIGHBOURS]);$a++) {
		$neighbour_atom_reac_no=$reac_atoms[$reac_atom_no][NEIGHBOURS][$a];
		$neighbour_atom_reac_idx=$reac_atoms[$neighbour_atom_reac_no]["idx"];
		if (in_array($neighbour_atom_reac_idx,$reac_assigned_in_chain)) { // do not go back and forth
			continue;
		}
		$fit=$temp_global_matchmat[$mol_product][$prod_atom_no][$neighbour_atom_reac_idx];
		if ($fit<=0) { // there must be something
			continue;
		}
		
		$fit+=getBondStability($reaction["molecules"][$mol_reactant],$reac_atom_no,$neighbour_atom_reac_no)*STABLE_BOND_BONUS;
		
		if ($fit>$best_fit) {
			$best_neighbour_no=$neighbour_atom_reac_no;
			$best_fit=$fit;
		}
	}
	if ($best_fit<ASSIGNED_NEIGHBOUR_THRESHOLD) {
		return;
	}
	
	// assign
	atomAssign(
		$temp_global_matchmat,$reaction,
		$mol_product,$prod_atom_no,
		$mol_reactant,$best_neighbour_no
	);
	$reac_assigned_in_chain[]=$reac_atoms[$best_neighbour_no]["idx"]; // prevent another assignment in the same chain
	
	// assign neighbours recursively
	for ($a=0;$a<count($prod_atoms[$prod_atom_no][NEIGHBOURS]);$a++) {
		$neighbour_atom_no=$prod_atoms[$prod_atom_no][NEIGHBOURS][$a];
		assignRecursive($temp_global_matchmat,$reaction,$reac_assigned_in_chain,$mol_product,$neighbour_atom_no,$mol_reactant,$best_neighbour_no,$reac_atom_no);
	}
}

// fundamental
define("RXN_SUBST",1);

define("RXN_ADD",2);
define("RXN_ELIM",4); // also decarboxylation (off the C-O bond)

define("RXN_REDUC",8); // also hydrogenation
define("RXN_OXID",16);

define("RXN_CYCLOADD",32);
define("RXN_CYCLOREV",64);

define("RXN_INSERTION",128);
define("RXN_EXTRUSION",256); // also decarbonylation

define("RXN_PERICYCL",512);

define("RXN_ISOMERIS",1024);

define("RXN_METATHESIS",2048); // olefins, salt-metathesis is not covered

function mapReaction(& $reaction,$paramHash=array()) {
	$mol_count=count($reaction["molecules"]);
	if ($reaction["reactants"]<=0 || $reaction["products"]<=0 || $mol_count!=$reaction["reactants"]+$reaction["products"]) {
		return;
	}
	// try to find candidates for atoms of products in starting material
	// assume that every single product may consist of all starting materials
	
	// go through product molecules
	$global_matchmat=array();
	$atom_map_inverted=array();
	$bond_map_inverted=array();
	$result=array();
	$skip_atoms=array();
	$match_quality=1;
	
	$needle_matchpath=array();
	$haystack_matchpath=array();
	
	$reaction["assignment_table"]=array();
	$reaction["bond_order_increased"]=array();
	$reaction["bond_order_decreased"]=array();
	$reaction["bond_broken"]=array();
	$reaction["bond_formed"]=array();
	
	// before nonpolar, afterwards polar
	$reaction["bond_polarised"]=array();
	// before polar, afterwards nonpolar
	$reaction["bond_unpolarised"]=array();
	// before polar, afterwards inverted
	$reaction["bond_inverted"]=array();
	
	// calc oxidation states in reactant here
	$idx=0;
	$bond_idx=0;
	for ($mol_reactant=0;$mol_reactant<$reaction["reactants"];$mol_reactant++) {
		$reactant=& $reaction["molecules"][$mol_reactant];
		
		for ($reac_atom_no=0;$reac_atom_no<count($reactant["atoms"]);$reac_atom_no++) {
			// prepare oxidation states
			$reaction["molecules"][$mol_reactant]["atoms"][$reac_atom_no][OXIDATION_STATE]=getOxidationState($reactant,$reac_atom_no);
			
			$reaction["molecules"][$mol_reactant]["atoms"][$reac_atom_no]["idx"]=$idx;
			$atom_map_inverted[$idx]=array($mol_reactant,$reac_atom_no);
			$idx++;
		}
		
		for ($reac_bond_no=0;$reac_bond_no<count($reactant[BONDS]);$reac_bond_no++) {
			$reaction["molecules"][$mol_reactant][BONDS][$reac_bond_no]["idx"]=$bond_idx;
			$bond_map_inverted[$bond_idx]=array($mol_reactant,$reac_bond_no);
			$bond_idx++;
		}
	}
	$candidate_atom_count=$idx;
	
	for ($mol_product=0;$mol_product<$reaction["products"];$mol_product++) {
		$mol_prod_idx=$mol_product+$reaction["reactants"];
		$product=& $reaction["molecules"][$mol_prod_idx];
		
		// prepare stuff
		$reaction["assignment_table"][$mol_product]=array();
		$reaction["bond_order_increased"][$mol_product]=array();
		$reaction["bond_order_decreased"][$mol_product]=array();
		$reaction["bond_broken"][$mol_product]=array();
		$reaction["bond_formed"][$mol_product]=array();
		
		// go through atoms
		for ($prod_atom_no=0;$prod_atom_no<count($product["atoms"]);$prod_atom_no++) { // difference to substructure search: we test every single atom where it matches best
			$prod_atom=& $product["atoms"][$prod_atom_no];
			$needle_matchpath[0]=$prod_atom_no;
			
			// Protons??
			// FIXME
			
			// calc oxidation states in product
			$reaction["molecules"][$mol_prod_idx]["atoms"][$prod_atom_no][OXIDATION_STATE]=getOxidationState($product,$prod_atom_no);
			
			// go through starting materials
			for ($mol_reactant=0;$mol_reactant<$reaction["reactants"];$mol_reactant++) {
				$reactant=& $reaction["molecules"][$mol_reactant];
				
				// go through atoms
				for ($reac_atom_no=0;$reac_atom_no<count($reactant["atoms"]);$reac_atom_no++) {
					$reac_atom=& $reactant["atoms"][$reac_atom_no];
					
					// different symbol or isotope: out
					if ($reac_atom[ATOMIC_SYMBOL]!=$prod_atom[ATOMIC_SYMBOL] || $reac_atom[MASS]!=$prod_atom[MASS]) { // !empty($prod_atom[MASS]), no
						continue;
					}
					// consider: connectivity
					$haystack_matchpath[0]=$reac_atom_no;
					$network_quality=matchPathsRecursiveLoose($product,$reactant,$needle_matchpath,$haystack_matchpath,$match_quality,$paramHash); // $part_matchmat,
					// $network_quality: 0-1, 1 is best match
					//~ echo $mol_product."-".$prod_atom_no.$prod_atom[ATOMIC_SYMBOL]." => ".$mol_reactant."-".$reac_atom_no.$reac_atom[ATOMIC_SYMBOL]." (".$reaction["molecules"][$mol_reactant]["atoms"][$reac_atom_no]["idx"] ."): ".$network_quality."\n";
					
					// consider: change of oxidation state
					$ox_state_quality=max(0,1-abs($prod_atom[OXIDATION_STATE]-$reac_atom[OXIDATION_STATE])*0.33);
					
					//~ $global_matchmat[$mol_product][$prod_atom_no][ $atom_map[$mol_reactant][$reac_atom_no] ]=$network_quality; // expand later
					$global_matchmat[$mol_product][$prod_atom_no][ $reac_atom["idx"] ]=$network_quality*NETWORK_WEIGHT+$ox_state_quality*OX_STATE_WEIGHT; // expand later
				}
			}
		}
	}
	
	$temp_global_matchmat=$global_matchmat;
	/* save cpu time, perhaps we need to reactivate this at some point
	// give bonus for combinations whose neighbours fits especially well ($match>0.8 and $1st_match-$2nd_match>0.1): +=($1st_match-$2nd_match)*0.8
	for ($mol_product=0;$mol_product<$reaction["products"];$mol_product++) {
		$mol_prod_idx=$mol_product+$reaction["reactants"];
		$product=& $reaction["molecules"][$mol_prod_idx];
		
		// go through atoms
		for ($prod_atom_no=0;$prod_atom_no<count($product["atoms"]);$prod_atom_no++) { // difference to substructure search: we test every single atom where it matches best
			$prod_atom=& $product["atoms"][$prod_atom_no];
			
			// get highest and 2nd highest match(es)
			$values=$global_matchmat[$mol_product][$prod_atom_no];
			if (!count($values)) {
				continue;
			}
			rsort($values,SORT_NUMERIC);
			if ($values[0]<GOOD_NETWORK_MATCH) {
				continue;
			}
			array_push($values,-SINGLE_MATCH_BONUS); // for atoms with single possibility, extra bonus
			for ($b=1;$b<count($values);$b++) {
				if ($values[$b]!=$values[0]) {
					$delta=$values[0]-$values[$b];
					if ($delta>MAX_EXCESS_THRESHOLD) {
						// condition met
						
						// which atoms (can be symmetry equivalent etc.) are so clearly matching?
						$good_matches=array();
						foreach ($global_matchmat[$mol_product][$prod_atom_no] as $a => $value) { // may have holes
							if ($value==$values[0]) { // only the highest, but may be multiple
								$good_matches[]=$a;
							}
						}
						
						for ($a=0;$a<count($prod_atom[NEIGHBOURS]);$a++) { // neighbours of $prod_atom_no
							$neighbour_atom_no=$prod_atom[NEIGHBOURS][$a];
							
							// find "partner" for $neighbour_atom_no
							foreach ($good_matches as $matching_atom_no) { // matching partners of $prod_atom_no
								list($mol_reactant,$reac_atom_no)=$atom_map_inverted[$matching_atom_no];
								$reac_atom=& $reaction["molecules"][$mol_reactant]["atoms"][$reac_atom_no];
								
								for ($c=0;$c<count($reac_atom[NEIGHBOURS]);$c++) { // neighbours of the matching partners of $prod_atom_no, is it a partner of the $neighbour_atom_no
									$neighbour_atom_reac_no=$reac_atom[NEIGHBOURS][$c];
									//~ $neighbour_atom_mapped=$atom_map[$mol_reactant][$neighbour_atom_reac_no];
									$neighbour_atom_mapped=$reaction["molecules"][$mol_reactant]["atoms"][$neighbour_atom_reac_no]["idx"];
									
									if ($temp_global_matchmat[$mol_product][$neighbour_atom_no][$neighbour_atom_mapped]) { // only increase value, if zero, it is zero
										$temp_global_matchmat[$mol_product][$neighbour_atom_no][$neighbour_atom_mapped]+=$delta*NEIGHBOUR_BONUS_FACTOR;
										
										// slightly decrease the other matches of $neighbour_atom_reac_no
										for ($prod_atom_no2=0;$prod_atom_no2<count($product["atoms"]);$prod_atom_no2++) {
											$match=$temp_global_matchmat[$mol_product][$prod_atom_no2][$neighbour_atom_mapped];
											if ($prod_atom_no2!=$neighbour_atom_no && $match) {
												$temp_global_matchmat[$mol_product][$prod_atom_no2][$neighbour_atom_mapped]=max(0,$match-NEIGHBOUR_MALUS);
											}
										}
									}
								}
							}
						}
					}
					continue 2; // next atom
				}
			}
		}
	}
	*/
	
	// find unassigned atom with highest correlation compared to alternatives => assign
	do {
		$start_product=-1;
		$start_atom=-1;
		$max_delta=0;
		
		for ($mol_product=0;$mol_product<$reaction["products"];$mol_product++) {
			$mol_prod_idx=$mol_product+$reaction["reactants"];
			$product=& $reaction["molecules"][$mol_prod_idx];
			
			// go through atoms
			for ($prod_atom_no=0;$prod_atom_no<count($product["atoms"]);$prod_atom_no++) { // difference to substructure search: we test every single atom where it matches best
				if ($product["atoms"][$prod_atom_no]["RMdone"]) {
					continue;
				}
						
				// get highest and 2nd highest match(es)
				$values=$temp_global_matchmat[$mol_product][$prod_atom_no];
				if (!count($values)) {
					continue;
				}
				rsort($values,SORT_NUMERIC);
				array_push($values,-SINGLE_MATCH_BONUS); // for atoms with single possibility, extra bonus
				
				for ($b=1;$b<count($values);$b++) {
					if ($values[$b]!=$values[0]) {
						$delta=$values[0]-$values[$b];
						
						if ($delta>$max_delta) {
							$max_delta=$delta;
							$start_product=$mol_product;
							$start_atom=$prod_atom_no;
							$start_assign=array_search($values[0],$temp_global_matchmat[$mol_product][$prod_atom_no]);
						}
						break;
					}
				}
			}
		}
		if ($start_atom>-1 && $start_product>-1) {
			$mol_prod_idx=$start_product+$reaction["reactants"];
			list($mol_reactant,$reac_atom_no)=$atom_map_inverted[$start_assign];
			
			// assign
			atomAssign(
				$temp_global_matchmat,$reaction,
				$start_product,$start_atom,
				$mol_reactant,$reac_atom_no
			);
	
			//~ echo $start_product."-".$start_atom.": ".$max_delta."\n";

			// assign neighbours recursively
			$reac_assigned_in_chain=array();
			for ($a=0;$a<count($reaction["molecules"][$mol_prod_idx]["atoms"][$start_atom][NEIGHBOURS]);$a++) {
				$neighbour_atom_no=$reaction["molecules"][$mol_prod_idx]["atoms"][$start_atom][NEIGHBOURS][$a];
				assignRecursive(
					$temp_global_matchmat,$reaction,$reac_assigned_in_chain,
					$start_product,$neighbour_atom_no,
					$mol_reactant,$reac_atom_no
				); // assign with neighbours of $start_assign
			}
		}
		else {
			break;
		}
	} while (true);
	
	// add bonds which were broken (NOT: simply the ones which are missing)
	foreach ($reaction["assignment_table"] as $mol_product => $data) {
		$mol_prod_idx=$mol_product+$reaction["reactants"];
		$prod=& $reaction["molecules"][$mol_prod_idx];
		
		foreach ($data as $prod_atom_no => $matching_atom_no) {
			list($mol_reactant,$reac_atom_no)=$atom_map_inverted[$matching_atom_no];
			$reac=& $reaction["molecules"][$mol_reactant];
			$reac_atom=& $reac["atoms"][$reac_atom_no];
			$prod_atom=& $prod["atoms"][$prod_atom_no];
			
			// Nachbarn (d.h. Bindungen) durchgehen
			for ($a=0;$a<count($reac_atom[NEIGHBOURS]);$a++) {
				$neighbour_reac_atom_no=$reac_atom[NEIGHBOURS][$a];
				
				for ($b=0;$b<count($prod_atom[NEIGHBOURS]);$b++) {
					$neighbour_atom_no=$prod_atom[NEIGHBOURS][$b];
					$neighbour_reac_idx=$reaction["assignment_table"][$mol_product][$neighbour_atom_no];
					
					if ($reaction["molecules"][$mol_reactant]["atoms"][$neighbour_reac_atom_no]["idx"]==$neighbour_reac_idx) { // probably no relationship
						continue 2; // next neighbour of the $reac_atom to check
					}
				}
				
				$reaction["bond_broken"][$mol_product][]=$reac["bondsFromNeighbours"][$reac_atom_no][$neighbour_reac_atom_no]["idx"]; // idx instead of #
			}
		}
		$reaction["bond_broken"][$mol_product]=array_unique($reaction["bond_broken"][$mol_product]); // eliminate doubles
	}
	// $reaction["bond_broken"]=array(Bindungsnummern *reactants*) (zugeordnete(s) Atom(e) vorher mit Bindung, hinterher fehlend)
	
	//~ echo "<pre>";
	//~ print_r($temp_global_matchmat);
	//~ print_r($reaction["assignment_table"]);
	//~ print_r($reaction["assignment_quality"]);
	//~ print_r($reaction["bond_order_increased"]);
	//~ print_r($reaction["bond_order_decreased"]);
	//~ print_r($reaction["bond_formed"]);
	//~ print_r($reaction["bond_broken"]);
	
	// find "systems" of bond changes, ??
	// $atom["changes"]["bond_formed"]
	// $atom["changes"]["bond_broken"]
	// $atom["changes"]["bond_changed"]
	
	// add reaction classification
	
	/*
	define("RXN_SUBST",1);

	define("RXN_ADD",2);
	define("RXN_ELIM",4); // also decarboxylation (off the C-O bond)

	define("RXN_REDUC",8); // also hydrogenation
	define("RXN_OXID",16);

	define("RXN_CYCLOADD",32);
	define("RXN_CYCLOREV",64);

	define("RXN_INSERTION",128);
	define("RXN_EXTRUSION",256); // also decarbonylation

	define("RXN_PERICYCL",512);

	define("RXN_ISOMERIS",1024);

	define("RXN_METATHESIS",2048); // olefins, salt-metathesis is not covered
	*/
	
	// which bonds were formed/broken/changed??
	// go through assignments and compare bonds
	
	
	// classification for each product: bitmask??
	
	// standard cases
	// substitution (one neighbour changed, polarity not inverted => oxidation),how about multiple
	// addition (1 bond order decreased, 2 adjacent bonds/Hs new, but not 2H => hydrogenation),
	// elimination (1 bond order increased, 2 adjacent bonds/Hs of inverted polarity missing, but not 2H => dehydrogenation, how about 1,4-eliminations?)
	// oxidation, reduction (check oxidation numbers, perhaps identify oxidizing/reducing agent)
	
	// special cases
	// double bonds
		// Wittig-type (carbonyl => olefin, normally detected as ylide is in reactant list)
		// olefin metathesis (double bond => different/missing [=> -C2H4] / addtional [=> +C2H4] double bond)
		// ozonolysis (olefin => 1 or 2 carbonyl/carboxyl)
		// McMurry (1 or 2 carbonyl => olefin)
	// decarboxylation (CO2 missing)
	// CA
		// diels-alder (3 double/triple bonds missing, 1 (or 2 for alkynes) double and 2 single bonds new)
		// 3+2-CA (normally detected by heteroatoms, [+/-]-charges reduced, 1 double and 2 single bonds new)
	
	// add asignment to image
	$idx=1;
	if ($paramHash["drawAssignment"]) {
		foreach ($reaction["assignment_table"] as $mol_product => $data) {
			$mol_prod_idx=$mol_product+$reaction["reactants"];
			
			foreach ($data as $prod_atom_no => $matching_atom_no) {
				list($mol_reactant,$reac_atom_no)=$atom_map_inverted[$matching_atom_no];
				$reaction["molecules"][$mol_prod_idx]["atoms"][$prod_atom_no]["assign"]=$idx;
				if (!empty($reaction["molecules"][$mol_reactant]["atoms"][$reac_atom_no]["assign"])) {
					$reaction["molecules"][$mol_reactant]["atoms"][$reac_atom_no]["assign"].=",";
				}
				$reaction["molecules"][$mol_reactant]["atoms"][$reac_atom_no]["assign"].=$idx;
				$idx++;
			}
		}
	}
	
	// remove crap
	for ($mol_reactant=0;$mol_reactant<$reaction["reactants"];$mol_reactant++) {
		$reactant=& $reaction["molecules"][$mol_reactant];
		for ($reac_atom_no=0;$reac_atom_no<count($reactant["atoms"]);$reac_atom_no++) {
			unset($reaction["molecules"][$mol_reactant]["atoms"][$reac_atom_no]["idx"]);
		}
	}
	for ($mol_product=0;$mol_product<$reaction["products"];$mol_product++) {
		$mol_prod_idx=$mol_product+$reaction["reactants"];
		$product=& $reaction["molecules"][$mol_prod_idx];
		
		// go through atoms
		for ($prod_atom_no=0;$prod_atom_no<count($product["atoms"]);$prod_atom_no++) { // difference to substructure search: we test every single atom where it matches best
			$prod_atom=& $product["atoms"][$prod_atom_no];
			
			unset($reaction["molecules"][$mol_prod_idx]["atoms"][$prod_atom_no]["RMdone"]);
		}
	}
}

?>