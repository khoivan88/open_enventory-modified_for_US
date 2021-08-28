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

function isMetal($sym) {
	global $metals;
	return in_array($sym,$metals);
}

function isHalogen($sym) {
	global $halogens;
	return in_array($sym,$halogens);
}

function isLanthanide($sym) {
	global $lanthanides;
	return in_array($sym,$lanthanides);
}

function matchAtoms($needleAtom,$haystackAtom) {
	// prüft, ob needleAtom (ggf. Wildcard etc) zu haystackAtom paßt
	$match=false;
	
	// check symbol
	if (in_array($needleAtom[ATOMIC_SYMBOL],array("*","A"))) {
		$match=true;
	}
	elseif ($needleAtom[ATOMIC_SYMBOL]==$haystackAtom[ATOMIC_SYMBOL]) {
		$match=true;
	}
	elseif ($needleAtom[ATOMIC_SYMBOL]=="Q" && $haystackAtom[ATOMIC_SYMBOL]!="C" && $haystackAtom[ATOMIC_SYMBOL]!="H") {
		$match=true;
	}
	elseif ($needleAtom[ATOMIC_SYMBOL]=="X" && isHalogen($haystackAtom[ATOMIC_SYMBOL])) {
		$match=true;
	}
	elseif ($needleAtom[ATOMIC_SYMBOL]=="M" && isMetal($haystackAtom[ATOMIC_SYMBOL])) {
		$match=true;
	}
	elseif ($needleAtom[ATOMIC_SYMBOL]=="Ln" && isLanthanide($haystackAtom[ATOMIC_SYMBOL])) {
		$match=true;
	}
	if (!$match) {
		return false;
	}
	
	// check charge and radical state
	if (
		$needleAtom[ORIG_CHARGE]!=0 
		&& 
		$needleAtom[ORIG_CHARGE]!=$haystackAtom[ORIG_CHARGE] 
		&& 
		$needleAtom[ORIG_CHARGE]!=$haystackAtom[CHARGE] 
		&& 
		$needleAtom[CHARGE]!=$haystackAtom[CHARGE]
	) {
		return false;
	}
	if (
		$needleAtom[ORIG_CHARGE]==0 
		&& 
		$needleAtom[CHARGE]!=0 // "artificially" created charge
		&& 
		$haystackAtom[ORIG_CHARGE]==0 
		&& 
		$haystackAtom[CHARGE]==0 
	) {
		return false;
	}
	
	if (
		$needleAtom[ORIG_RADICAL]!=0 
		&& 
		$needleAtom[ORIG_RADICAL]!=$haystackAtom[ORIG_RADICAL] 
		&& 
		$needleAtom[ORIG_RADICAL]!=$haystackAtom[RADICAL] 
		&& 
		$needleAtom[RADICAL]!=$haystackAtom[RADICAL]
	) {
		return false;
	}
	
	// check isotope situation
	if ($needleAtom[IS_ISOTOPE] && $needleAtom[MASS]!=$haystackAtom[MASS]) {
		return false;
	}
	
	return $match;
}

/* function matchBonds($needleBond,$haystackBond,$paramHash=array()) { // $paramHash is obsolete for bond assignment in reaction mapping
	if (floatval($needleBond[BOND_ORDER])==floatval($haystackBond[BOND_ORDER]) || $needleBond[ORIG_BOND_ORDER]==$haystackBond[ORIG_BOND_ORDER]) { // gefunden
		return true;
	}
	// groups which are searched while making the fingerprints are not aromatized, therefore more tolerant matching
	if ($paramHash["fp"]) { // needle ist funk gruppe, haystack ist molekül
		if ($haystackBond[BOND_ORDER]==1.5 && ($needleBond[BOND_ORDER]==1 || $needleBond[BOND_ORDER]==2) ) { // accept aromatic bond in candidate as single or double
			return true;
		}
	}
	return false;
}*/

function matchBonds(& $needle,$lastNeedle,$prevNeedle,& $haystackMolecule,$lastHaystack,$prevHaystack,$paramHash=array()) { // $paramHash is obsolete for bond assignment in reaction mapping
	$needleBond=& $needle["bondsFromNeighbours"][ $lastNeedle ][ $prevNeedle ];
	$haystackBond=& $haystackMolecule["bondsFromNeighbours"][ $lastHaystack ][ $prevHaystack ];
	if (
		floatval($needleBond[BOND_ORDER])==floatval($haystackBond[BOND_ORDER]) || 
		$needleBond[ORIG_BOND_ORDER]==$haystackBond[ORIG_BOND_ORDER]
		|| 
		(
			$needle["atoms"][$lastNeedle][AROMATIC] && 
			$haystackMolecule["atoms"][$lastHaystack][AROMATIC] && 
			$needle["atoms"][$prevNeedle][AROMATIC] && 
			$haystackMolecule["atoms"][$prevHaystack][AROMATIC]
		)
	) { // gefunden
		return true;
	}
	// groups which are searched while making the fingerprints are not aromatized, therefore more tolerant matching
	if ($paramHash["fp"]) { // needle ist funk gruppe, haystack ist molekül
		if ($haystackBond[BOND_ORDER]==1.5 && ($needleBond[BOND_ORDER]==1 || $needleBond[BOND_ORDER]==2) ) { // accept aromatic bond in candidate as single or double
			return true;
		}
	}
	return false;
}

/*--------------------------------------------------------------------------------------------------
/ Function: matchPathsRecursive
/
/ Purpose: starting from the given paths within needle and haystack, walk one more atom if matching and call self recursively
/
/ Parameter:
/ 		&$needle : data structure defining the needle molecule
/ 		&$haystackMolecule : data structure defining the haystack molecule
/ 		$needlePath : list of consecutive atoms in needle, defined by the atom numbers
/ 		$haystackPath : list of consecutive atoms in haystack, defined by the atom numbers
/ 		&$paramHash : control parameters as associative array, currently only fp to reduce strictness for fingerprinting
/ Return : boolean
/------------------------------------------------------------
/ History:
/ 2009-07.15 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function matchPathsRecursive(& $part_matchmat,& $needle,& $haystackMolecule,$needlePath,$haystackPath,$invert_aromatic,& $paramHash) {
	// prüft rekursiv zwei Pfade in needle und haystack gegeneinander, mit Matrixprüfung
	$path_length=count($needlePath);
	if ($path_length!=count($haystackPath)) {
		return false;
	}
	$lastNeedle=end($needlePath);
	$lastHaystack=end($haystackPath);
	
	if (
		$needle["atoms"][$lastNeedle][NON_H_NEIGHBOURS]>$haystackMolecule["atoms"][$lastHaystack][NON_H_NEIGHBOURS]
		||
		!matchAtoms($needle["atoms"][ $lastNeedle ],$haystackMolecule["atoms"][ $lastHaystack ])
	 ) { // Atome passen nicht oder Haystack hat zuwenige Nachbarn, um für needle zu passen
		return false;
	}
	
	if ($path_length>1) {
		$prevNeedle=prev($needlePath);
		$prevHaystack=prev($haystackPath);
		$oldNeighbours=1; // Zahl der bereits besuchten Nachbarn
		
		$needleBond=& $needle["bondsFromNeighbours"][ $lastNeedle ][ $prevNeedle ];
		$haystackBond=& $haystackMolecule["bondsFromNeighbours"][ $lastHaystack ][ $prevHaystack ];
		
		if (floatval($needleBond[BOND_ORDER])!=1.5 && floatval($haystackBond[BOND_ORDER])!=1.5) { // no aromatic stuff
			$invert_aromatic=SUBST_INVERT_OFF;
		}
		else {
			$inverted_match=(
				floatval($haystackBond[BOND_ORDER])==1.5 && 
				(floatval($needleBond[BOND_ORDER])==1 || floatval($needleBond[BOND_ORDER])==2) && // then all must be inverted
				$needleBond[ORIG_BOND_ORDER]!=$haystackBond[ORIG_BOND_ORDER]
			);
		}
		
		if ($invert_aromatic==SUBST_INVERT_ON) { // inverted mode is active
			if (!$inverted_match && floatval($needleBond[BOND_ORDER])!=1.5) { // keeping inverted or going back to aromatic
				return false; // chance missed
			}
		}
		elseif (!matchBonds($needle,$lastNeedle,$prevNeedle,$haystackMolecule,$lastHaystack,$prevHaystack,$paramHash)) {
			// can we activate $invert_aromatic?
			if ($inverted_match && $invert_aromatic!=SUBST_INVERT_OFF) { // activate inverted mode
				$invert_aromatic=SUBST_INVERT_ON;
			}
			else {
				return false; // Letzte Bindungen passen nicht
			}
		}
		elseif (
			floatval($needleBond[BOND_ORDER])==1.5 || // going through aromatic area in needle
			(floatval($haystackBond[BOND_ORDER])!=1.5 && $needleBond[ORIG_BOND_ORDER]==$haystackBond[ORIG_BOND_ORDER]) // exactly matching non-aromatic parts
		) { // allow inverted mode
			$invert_aromatic=SUBST_INVERT_ANY;
		}
		else { // disallow inverted mode
			$invert_aromatic=SUBST_INVERT_OFF;
		}
	}
	else {
		$prevNeedle=-1;
		$prevHaystack=-1;
		$oldNeighbours=0; // Zahl der bereits besuchten Nachbarn
		$part_matchmat=array(); // reset
	}
	
	// noSubst
	if ($needle["atoms"][ $lastNeedle ]["noSubst"] && count($needle["atoms"][ $lastNeedle ][NEIGHBOURS])+$needle["atoms"][ $lastNeedle ][IMPLICIT_H]!=count($haystackMolecule["atoms"][ $lastHaystack ][NEIGHBOURS])) {
		return false;
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
		$part_matchmat[$lastNeedle][$lastHaystack]=true;
		return true;
	}
	elseif ($haystackRingPos!==$needleRingPos) {
		return false;
	}
	
	// Ende erreicht
	if (count($needle["atoms"][ $lastNeedle ][NEIGHBOURS])==$oldNeighbours) { // nur das atom wo wir herkommen
		$part_matchmat[$lastNeedle][$lastHaystack]=true;
		return true;
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
					if (matchPathsRecursive($part_matchmat,$needle,$haystackMolecule,$needlePath,$haystackPath,$invert_aromatic,$paramHash)) {
						$matchmat[$a-$ndlMatSub][$b-$hstMatSub]=true;
						$somethingFound=true;
					}
				}
				else { // spalte überspringen
					$hstMatSub++;
				}
			}
			if (!$somethingFound) {
				return false; // irgendein Zweig von needle konnte nicht gematcht werden
			}
		}
		 else { // spalte überspringen
			$ndlMatSub++;
		}
	}
	//~ return matrixCheck($matchmat,count($needle["atoms"][ $lastNeedle ][NEIGHBOURS])-$oldNeighbours,count($haystackMolecule["atoms"][ $lastHaystack ][NEIGHBOURS])-$oldNeighbours); // durchgekommen, ok
	if (matrixCheck($matchmat,count($needle["atoms"][ $lastNeedle ][NEIGHBOURS])-$oldNeighbours,count($haystackMolecule["atoms"][ $lastHaystack ][NEIGHBOURS])-$oldNeighbours)) { // durchgekommen, ok
		$part_matchmat[$lastNeedle][$lastHaystack]=true;
		return true;
	}
	return false;
}

function matrixCheck(& $matrix,$dimNeedle,$dimHaystack) {
	// prüft anhand einer Matrix, ob Pfade wirklich ok sind
	if ($dimNeedle>$dimHaystack) {
		return false;
	}
	if ($dimNeedle==1) { // only 1 atom to match
		return @in_array(true,$matrix[0]);
	}
	for ($a=0;$a<$dimHaystack;$a++) {
		if ($matrix[0][$a]) { // hier // [0]
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
			if (matrixCheck($newMatrix,$dimNeedle-1,$dimHaystack-1)) {
				return true;
			}
		}
	}
	return false;
}

function mergeMatrix(& $global_matchmat,& $matchmat) {
	foreach ($matchmat as $a => $line) {
		if (is_array($line)) {
			foreach ($line as $b => $bool) {
				if ($bool) {
					$global_matchmat[$a][$b]=true;
				}
			}
		}
	}
}

function matrixPrint($matrix,$dimNeedle,$dimHaystack) {
	$retval="";
	for ($b=0;$b<$dimNeedle;$b++) { // eine Zeile gehört zu einem Atom von needle
		for ($a=0;$a<$dimHaystack;$a++) {
			$retval.=($matrix[$b][$a]?"X":"O");
		}
		$retval.="\n";
	}
	return $retval;
}

function getSubstMatch($needle,$haystackMolecule,$paramHash=array()) {
	checkSettings($paramHash,"su");
	
	switch ($paramHash["mode"]) {
	case "assign":
		$not_found=array(false);
	break;
	default:
		$not_found=false;
	}
	
	// gibt true zurück, wenn needle eine Substruktur von haystackMolecule ist, auch Multipart mit Matrixprüfung
	if (count($needle["atoms"])==0 || count($haystackMolecule["atoms"])==0) {
		return $not_found;
	}
	
	if (!$paramHash["fp"]) {
		if ($needle["smiles"]!="" && $needle["smiles"]==$haystackMolecule["smiles"] && empty($paramHash["mode"])) { // identical
			return true;
		}
		
		if (!getSubEmpFormulaMatch($needle,$haystackMolecule,array("ignoreH" => true, ))) { // emp_formula does not fit
			return $not_found;
		}
		//~ echo $needle["eProt"]." ".$haystackMolecule["iProt"]."+".$haystackMolecule["eProt"]."\n";
		if ($needle["eProt"]>$haystackMolecule["iProt"]+$haystackMolecule["eProt"]) {
			return $not_found;
		}
	}
	
	// add implicit hydrogens in haystack explicitly
	if ($needle["has_explicit_h"]) {
		for ($a=0,$max=count($haystackMolecule["atoms"]);$a<$max;$a++) {
			for ($b=0;$b<$haystackMolecule["atoms"][$a][IMPLICIT_H];$b++) {
				addAtomToGroup($haystackMolecule,"H",$a,1);
			}
			$haystackMolecule["atoms"][$a][IMPLICIT_H]=0;
		}
	}
	
	if (!isset($needle["parts"])) { // fake maxatom for fingerprinting
		$needle["parts"][0]["maxatom"]=0;
		$needle["parts"][0]["minatom"]=0;
	}
	
	if (!isset($haystackMolecule["parts"])) { // fake maxatom should not occur
		$haystackMolecule["parts"][0]["maxatom"]=0;
		$haystackMolecule["parts"][0]["minatom"]=0;
	}
	
	$global_matchmat=array();
	$part_matchmat=array();
	$skip_atoms=array();
	$match_anything=array_fill(0,count($haystackMolecule["atoms"]),true);
	
	for ($b=0;$b<count($needle["parts"]);$b++) { // alle teile prüfen
		//~ $needle_maxatom=$needle["parts"][$b]["maxatom"];
		$needle_maxatom=$needle["parts"][$b]["minatom"];
		$needle_matchpath[0]=$needle_maxatom; // am maxatom wird angefangen. Wenn das nicht gefunden wird - tschüß
		
		// Protons already compared earlier
		if (
			$needle["atoms"][$needle_maxatom][ATOMIC_SYMBOL]=="H"
			&&
			$needle["atoms"][$needle_maxatom][CHARGE]==1
			&&
			count($needle["atoms"][$needle_maxatom][NEIGHBOURS])==0
		) {
			$global_matchmat[$needle_maxatom]=$match_anything;
			$skip_atoms[]=$needle_maxatom;
			continue;
		}
		
		$somethingFound=false;
		for ($a=0;$a<count($haystackMolecule["atoms"]);$a++) { // $a may be higher than atom number in original molecule due to explicit hydrogens
			if (
				$needle["atoms"][ $needle_maxatom ][NON_H_NEIGHBOURS]<=$haystackMolecule["atoms"][$a][NON_H_NEIGHBOURS] +0.5 // fix carboxylate bug
				&& 
				$needle["atoms"][ $needle_maxatom ][NON_H_BONDS]<=$haystackMolecule["atoms"][$a][NON_H_BONDS] +0.5 // fix carboxylate bug
			) {
				$haystack_matchpath[0]=$a;
				if (matchPathsRecursive($part_matchmat,$needle,$haystackMolecule,$needle_matchpath,$haystack_matchpath,SUBST_INVERT_ANY,$paramHash)) {
					$somethingFound=true;
					mergeMatrix($global_matchmat,$part_matchmat);
				}
			}
		}
		
		if (!$somethingFound) {
			return $not_found;
		}
	}
	
	//~ if (!$paramHash["fp"]) echo matrixPrint($global_matchmat,count($needle["atoms"]),count($haystackMolecule["atoms"]))."<hr>";
	$result=matrixCheck($global_matchmat,count($needle["atoms"]),count($haystackMolecule["atoms"]));
	
	switch ($paramHash["mode"]) {
	case "assign":
		$assignmentTable=array();
		if ($result) {
			// do assignment
			for ($b=0;$b<count($needle["atoms"]);$b++) {
				if  (in_array($b,$skip_atoms)) {
					continue;
				}
				for ($a=0;$a<count($haystackMolecule["atoms"]);$a++) {
					if ($global_matchmat[$b][$a] && !in_array($a,$assignmentTable)) {
						$assignmentTable[$b]=$a;
						break;
					}
				}
			}
		}
		return array($result,$assignmentTable);
	break;
	default:
		return $result;
	}
}

function getFPmatch(& $needle,& $haystack,$paramHash=array()) { // only for testing purposes, real check is done in database
	for ($a=0;$a<count($needle["fingerprints"]);$a++) {
		if (($haystack["fingerprints"][$a] & $needle["fingerprints"][$a])!=$needle["fingerprints"][$a]) {
			return false;
		}
	}
	return true;
}

function removeAtoms(& $haystackMolecule,$elements,$number) {
	foreach ($elements as $sym) {
		$sym_num=$haystackMolecule["emp_formula"][$sym];
		if ($sym_num<=0) {
			continue;
		}
		// bring to 0 until $number is used up
		if ($sym_num>=$number) {
			$haystackMolecule["emp_formula"][$sym]-=$number;
			return;
		}
		else {
			$haystackMolecule["emp_formula"][$sym]=0;
			$number-=$sym_num;
		}
	}
}

function getSubEmpFormulaMatch($needle,$haystackMolecule,$paramHash=array()) {
	global $halogens,$metals,$lanthanides;
	checkSettings($paramHash,"sf");
	// prüft, ob needle im Hinblick auf die Summenformel eine Substruktur von haystackMolecule ist
	if (count($needle["emp_formula"])==0 || count($haystackMolecule["emp_formula"])==0) { // no formula given
		return false;
	}
	if ($paramHash["exact"] && array_sum($haystackMolecule["emp_formula"])!=array_sum($needle["emp_formula"])) { // save a lot of time
		return false;
	}
	foreach($needle["emp_formula"] as $sym => $number) {
		switch ($sym) {
		case "X":
		case "Ln":
		case "M":
		case "*":
		case "%":
			continue 2;
		// no break
		
		case "H":
			if ($paramHash["ignoreH"]) {
				continue 2;
			}
		// no break
		
		default:
			if ($haystackMolecule["emp_formula"][$sym]<$number) {
				return false;
			}
			$haystackMolecule["emp_formula"][$sym]-=$number; // no longer available for wildcards
		}
	}
	// handle wildcards
	foreach($needle["emp_formula"] as $sym => $number) {
		switch ($sym) {
		case "X":
			if (array_key_sum($haystackMolecule["emp_formula"],$halogens)<$number) {
				return false;
			}
			if ($paramHash["exact"]) {
				removeAtoms($haystackMolecule,$halogens,$number);
			}
			else {
				$haystackMolecule["emp_formula"]["F"]-=$number; // in the end, only the sum counts
			}
		break;
		case "Ln":
			if (array_key_sum($haystackMolecule["emp_formula"],$lanthanides)<$number) {
				return false;
			}
			if ($paramHash["exact"]) {
				removeAtoms($haystackMolecule,$lanthanides,$number);
			}
			else {
				$haystackMolecule["emp_formula"]["La"]-=$number; // in the end, only the sum counts
			}
		break;
		case "M":
			if (array_key_sum($haystackMolecule["emp_formula"],$metals)<$number) {
				return false;
			}
			if ($paramHash["exact"]) {
				removeAtoms($haystackMolecule,$metals,$number);
			}
			else {
				$haystackMolecule["emp_formula"]["Li"]-=$number; // in the end, only the sum counts
			}
		break;
		}
	}
	$remaining=array_sum($haystackMolecule["emp_formula"]);
	if ($paramHash["exact"]) {
		return ($remaining==$needle["emp_formula"]["%"]);
	}
	if ($remaining<$needle["emp_formula"]["%"]) { // *
		return false;
	}
	return true;
}

function empFormulaHasWildcard($emp_formula) {
	global $atom_wildcards;
	foreach ($atom_wildcards as $atom_wildcard) {
		if (preg_match("/".$atom_wildcard."[A-Z\d]/",$emp_formula) || preg_match("/".$atom_wildcard."\$/",$emp_formula)) {
			return true;
		}
	}
	return false;
}

?>