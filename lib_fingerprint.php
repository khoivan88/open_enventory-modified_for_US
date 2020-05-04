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

$max31=4294967295;

function addAtomToGroup(& $group,$symbol,$attachedToAtom,$bond,$noFurtherSubstitution=false) {
	// fügt ein Atom zu einer byRef übergebenen Gruppe hinzu oder füngt eine neue Gruppe an
	// fürs Fingerprinting
	global $pse;
	if ($attachedToAtom>=0) { // 1st atom attached to -1, zu gruppe hinzufügen
		$newIdx=count($group["atoms"]);
		$group["atoms"][$newIdx]=array(
			ATOMIC_SYMBOL => $symbol, 
			ATOMIC_NUMBER => $pse[$symbol], 
			NEIGHBOURS => array($attachedToAtom), 
			"noSubst" => $noFurtherSubstitution, 
			PART => 0, 
		);
		$group["atoms"][$attachedToAtom][NEIGHBOURS][]=$newIdx;
		$bond_count=is_array($group[BONDS]) ? count($group[BONDS]) : 0;
		$group[BONDS][$bond_count]=array(
			BOND_ORDER => $bond, 
			ORIG_BOND_ORDER => $bond, 
		);
		$group["bondsFromNeighbours"][$newIdx][$attachedToAtom]=& $group[BONDS][$bond_count]; // $bond
		$group["bondsFromNeighbours"][$attachedToAtom][$newIdx]=& $group[BONDS][$bond_count];
	}
	else { // neue Gruppe
		$group["atoms"][]=Array(
			ATOMIC_SYMBOL => $symbol, 
			ATOMIC_NUMBER => $pse[$symbol], 
			NEIGHBOURS => array(), 
			"noSubst" => $noFurtherSubstitution, 
			PART => 0, 
		);
	}
}

function addBondToGroup(&$group,$atom1,$atom2,$bond) {
	// fügt eine Bindung zu einer byRef übergebenen Gruppe hinzu (nur für Ringe erforderlich)
	$group["atoms"][$atom1][NEIGHBOURS][]=$atom2;
	$group["atoms"][$atom2][NEIGHBOURS][]=$atom1;
	$bond_count=count($group[BONDS]);
	$group[BONDS][$bond_count]=array(
		BOND_ORDER => $bond, 
		ORIG_BOND_ORDER => $bond, 
	);
	$group["bondsFromNeighbours"][$newIdx][$attachedToAtom]=& $group[BONDS][$bond_count]; // $bond
	$group["bondsFromNeighbours"][$attachedToAtom][$newIdx]=& $group[BONDS][$bond_count];
}

$bondPatterns=array( // rare at the end, overlapping with long-chain-FPs
	0 => array(1,1,1),
	1 => array(1,1,1.5),
	2 => array(1,1,2),
	3 => array(1,1,3),
	4 => array(1,1.5,1),
	5 => array(1,1.5,1.5),
	6 => array(1,2,1),
	13 => array(1,2,2), // quite rare
	7 => array(1,3,1),
	8 => array(1.5,1,1.5),
	9=> array(1.5,1,2),
	10=> array(1.5,1,3),
	11=> array(2,1,2),
	12=> array(2,1,3),
	14=> array(2,2,2), // very rare
	15=> array(3,1,3), // quite rare
// may contain 1.5 only once, results in bug otherwise!!
/*
	0 => array(1,1,1), // 9Alkyl
	1 => array(1,1,1.5), // 7Ethylbenzene
	2 => array(1,1,2), // 5Alkene, very specific??
	3 => array(1,1,3), // 4Alkyne

	//~ 4 => array(1,2,1), // 4intAlkene
	4 => array(1,1.5,1), // 8oXylene

	5 => array(1.5,1,3), // 7Phenylacetylene

	6 => array(2,1,2), // 9Michael
	7 => array(2,1,3), // 5enyne
*/
);

define("FP3_BOND_ATOMS",4); // with bond pattern
//~ define("FP4_BOND_ATOMS",5); // has multiple bond??
//~ define("FP5_BOND_ATOMS",7); // atom sequence only
define("BITS_PER_BLOCK",31);

function FP3single(& $molecule,$atoms_arr,$loose_order=false) { // gibt 0-127 zurück oder -1 für Fehler/ungültig/...
	global $bondPatterns;
	
	$atoms_arr_count=count($atoms_arr);
	if ($atoms_arr_count!=FP3_BOND_ATOMS) {
		return array();
	}
	
	$thisBondPattern=array(
		$molecule["bondsFromNeighbours"][ $atoms_arr[0] ][ $atoms_arr[1] ][BOND_ORDER],
		$molecule["bondsFromNeighbours"][ $atoms_arr[1] ][ $atoms_arr[2] ][BOND_ORDER],
		$molecule["bondsFromNeighbours"][ $atoms_arr[2] ][ $atoms_arr[3] ][BOND_ORDER],
	);
	
	if ($loose_order) {
		$thisOrigBondPattern=array(
			$molecule["bondsFromNeighbours"][ $atoms_arr[0] ][ $atoms_arr[1] ][ORIG_BOND_ORDER],
			$molecule["bondsFromNeighbours"][ $atoms_arr[1] ][ $atoms_arr[2] ][ORIG_BOND_ORDER],
			$molecule["bondsFromNeighbours"][ $atoms_arr[2] ][ $atoms_arr[3] ][ORIG_BOND_ORDER],
		);
	}
	
	//~ print_r($thisBondPattern);
	//~ print_r($thisOrigBondPattern);
	// symmetrische Bindungsmuster wie 2,1,2 werden ggf doppelt erfaßt
	
	if ($thisBondPattern[0]>$thisBondPattern[2] && $thisOrigBondPattern[0]>$thisOrigBondPattern[2]) {
		return array(); // 1st bond higher than 3rd
	}
	
	$retval=array();
	if ($loose_order) {
		for ($a=0;$a<count($bondPatterns);$a++) {
			for ($b=0;$b<count($bondPatterns[$a]);$b++) {
				if ($bondPatterns[$a][$b]!=$thisBondPattern[$b] && $bondPatterns[$a][$b]!=$thisOrigBondPattern[$b]) {
					continue 2;
				}
			}
			$retval[]=$a;
		}
		//~ print_r($retval);
	}
	else {
		$a=array_search($thisBondPattern,$bondPatterns);
		if ($a!==FALSE) {
			$retval[]=$a;
		}
	}
	
	if (!count($retval)) {
		return array(); // Bond pattern not found, either super exotic or super common
	}
	$shift=3;
	for ($a=0;$a<$atoms_arr_count;$a++) { // 4x
		if ($molecule["atoms"][ $atoms_arr[$a] ][ATOMIC_NUMBER]==6) {
			for ($b=0;$b<count($retval);$b++) {
				$retval[$b]|=(1 << $shift);
			}
		}
		else {
		}
		$shift++;
	}
	return $retval;
}

/*function FP4single(& $molecule,$atoms_arr) { // gibt 0-31 zurück oder -1 für Fehler/ungültig/...
	// 5 bit durch C/nicht-C
	
	$atoms_arr_count=count($atoms_arr);
	if ($atoms_arr_count!=FP4_BOND_ATOMS) {
		return -1;
	}
	
	$order_key=BOND_ORDER;
	$shift=0;
	//~ $multi_bond=false;
	//~ $multi_bond12=false;
	//~ $all_carbon=true;
	for ($a=0;$a<$atoms_arr_count;$a++) {
		//~ if (!$multi_bond && $molecule["bondsFromNeighbours"][ $atoms_arr[$a] ][ $atoms_arr[$a+1] ][$order_key]>1) {
			// find multiple
			//~ $multi_bond=true;
			//~ if ($a<2) {
				//~ $multi_bond12=true;
			//~ }
		//~ }
		if ($molecule["atoms"][ $atoms_arr[$a] ][ATOMIC_NUMBER]==6) {
			$retval|=(1 << $shift);
		}
		//~ else {
			//~ $all_carbon=false;
		//~ }
		$shift++;
	}
	
	// (enthält irgendwo Mehrfachbindung und nur Kohlenstoff) oder (Mehrfachbindung in 1/2 und min ein Nicht-Kohlenstoff)
	//~ if (($all_carbon && $multi_bond) || (!$all_carbon && $multi_bond12)) {
	//~ if (($all_carbon && $multi_bond) || (!$all_carbon && $multi_bond12)) {
		//~ $retval|=(1 << $shift);
	//~ }
	
	return $retval;
}

function FP5single(& $molecule,$atoms_arr) { // gibt 0-127 zurück oder -1 für Fehler/ungültig/...
	// 7 bit durch C/nicht-C, 1x falten
	
	$atoms_arr_count=count($atoms_arr);
	if ($atoms_arr_count!=FP5_BOND_ATOMS) {
		return -1;
	}
	
	$shift=0;
	for ($a=0;$a<$atoms_arr_count;$a++) { // beim 2. Atom starten
		if ($molecule["atoms"][ $atoms_arr[$a] ][ATOMIC_NUMBER]==6) {
			$retval|=(1 << $shift);
		}
		$shift++;
	}
	
	// falten, XOR über erstes Atom in Reihe
	//~ if ($molecule["atoms"][ $atoms_arr[0] ][ATOMIC_NUMBER]==6) {
		//~ $retval=$retval^63;
	//~ }
	
	return $retval;
}*/

function FPsub(& $fingerprint,& $molecule,$path,$paramHash=array()) {
	$path_count=count($path);
	if ($path_count>FP3_BOND_ATOMS || $path_count<1) { // should not happen
		return;
	}
	elseif ($path_count==FP3_BOND_ATOMS) {
		// order
		$shifts=FP3single($molecule,$path,!$paramHash["forStructureSearch"]);
		for ($a=0;$a<count($shifts);$a++) {
			$shift=$shifts[$a];
			
			$idx=intval(floor($shift/BITS_PER_BLOCK));
			$shift%=BITS_PER_BLOCK;
			$fingerprint[$idx]|=(1 << $shift);
		}
	}
	/* elseif ($path_count==FP4_BOND_ATOMS) {
		$shift=FP4single($molecule,$path);
		if ($shift!=-1) {
			$shift+=128;
			$idx=intval(floor($shift/BITS_PER_BLOCK));
			$shift%=BITS_PER_BLOCK;
			$fingerprint[$idx]|=(1 << $shift);
		}
	}
	elseif ($path_count==FP5_BOND_ATOMS) {
		$shift=FP5single($molecule,$path);
		if ($shift!=-1) {
			//~ $shift+=192;
			$shift+=160;
			$idx=intval(floor($shift/BITS_PER_BLOCK));
			$shift%=BITS_PER_BLOCK;
			$fingerprint[$idx]|=(1 << $shift);
		}
	}*/
	
	if ($path_count<FP3_BOND_ATOMS) {
		// Nachbarn durchgehen
		$last_atom=$path[$path_count-1];
		for ($a=0;$a<count($molecule["atoms"][$last_atom][NEIGHBOURS]);$a++) {
			
			$new_atom=$molecule["atoms"][$last_atom][NEIGHBOURS][$a];
			if ($molecule["atoms"][$new_atom][ATOMIC_NUMBER]==1) { // no expl Hs
				continue;
			}
			if (in_array($new_atom,$path)) {
				continue;
			}
			$new_path=$path;
			array_push($new_path,$new_atom);
			FPsub($fingerprint,$molecule,$new_path,$paramHash);
		}
	}
}

/*--------------------------------------------------------------------------------------------------
/ Function: FPall
/
/ Purpose: analyze all sequences of 4 atoms and 3 connecting bonds to create fingerprints of 256 bit, which are added to the molecule automatically
/
/ Parameter:
/ 		&$molecule : Array which describes the molecule
/ 		$paramHash : optional control parameters, in particular the information if the structure will be used for search or storage
/ Return : $fingerprint
/------------------------------------------------------------
/ History:
/ 2009-07.15 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function FPall(& $molecule,$paramHash=array()) {
	// alle 4Atome-3Bindungen-Konstellationen finden
	$fingerprint=array_fill(0,8,0);
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		if ($molecule["atoms"][$a][ATOMIC_NUMBER]==1) { // no expl Hs
			continue;
		}
		FPsub($fingerprint,$molecule,array($a),$paramHash);
	}
	return $fingerprint;
}

/*--------------------------------------------------------------------------------------------------
/ Function: addToFingerprint
/
/ Purpose: build fingerprint bitmask by setting a bit shifted by $shift if a structural feature is present a $number times
/
/ Parameter:
/ 		&$fingerprint : 32bit integer, where bits may be set
/ 		&$shift : number of bits shift to the left, may be changed by the function
/ 		$atom : type of feature, may also be ring type
/ 		$number : how many times does the structural feature occur in the molecule
/ Return : nothing
/------------------------------------------------------------
/ History:
/ 2009-07.14 RUD01 Created
..--------------------------------------------------------------------------------------------------*/
function addToFingerprint(& $fingerprint,& $shift,$atom,$number) {
	// fügt eines oder mehrere Bit zu einem fingerprint hinzu, um shift nach links verschoben
	// dazu wird das Atomsymbol (ggf. auch Ringname) atom und die Anzahl number verwendet
	$number+=0;
	if ($number==0) {
		$retval=0;
	}
	else {
		$retval=1;
		//~ echo $atom."<br>";
	}
	
	switch ($atom) {
	case "H":
		$inc=4;
		if ($number>=7) {
			$retval+=2;
		}
		if ($number>=15) {
			$retval+=4;
		}
		if ($number>=127) {
			$retval+=8;
		}
	break;
	case "C":
		$inc=4;
		if ($number>=7) {
			$retval+=2;
		}
		if ($number>=15) {
			$retval+=4;
		}
		if ($number>=63) {
			$retval+=8;
		}
	break;
	case "Ph":
		$inc=5;
		if ($number>=2) {
			$retval+=2;
		}
		if ($number>=3) {
			$retval+=4;
		}
		if ($number>=6) {
			$retval+=8;
		}
		if ($number>=9) {
			$retval+=16;
		}
	break;
	case "O":
	case "Cy":
	case "Cp-":
	case "Cyclopentyl":
	// case "Ar5":
	case 5:
	case 6:
	case 12: // also Rings above 12
		$inc=3;
		if ($number>=3) {
			$retval+=2;
		}
		if ($number>=7) {
			$retval+=4;
		}
	break;
	case "N":
	case "F":
	case "Cl":
	case "Cyclobutyl": // Cp,Py,Ep,Cycloprop,Thienyl
	case "Cyclopropyl":
	//~ case "Cp":
	case "Py":
	case "Oxiran":
	case "Thiophen":
	case "Pyrrol":
	case "Imidazol":
	case "Pyrazol":
	case 3:
	case 4:
		$inc=2;
		if ($number>=3) {
			$retval+=2;
		}
	break;
	case "+":
	case 7:
	case 8:
	case 9:
	case 10:
	case 11:
	case "Furan":
	case "Pyrylium":
	case "Pyridazin":
	case "Pyrimidin":
	case "Pyrazin":
	case "123Triazin":
	case "124Triazin":
	case "135Triazin":
	case "Oxazol":
	case "Thiazol":
	case "Isoxazol":
	case "123Triazol":
	case "124Triazol":
	case "Morpholin":
		$inc=1;
	break;
	}
	$fingerprint+=($retval << $shift);
	$shift+=$inc;
}

// für das fingerprinting dürfen Hs nicht berücksichtigt werden
function getGroupFingerprint($molecule,$paramHash=array()) {
	$mask=-1;
	$mask2=-1;
	// C-C-Bindungen dürfen nicht enthalten sein, wenn ein Metallacyclopropan möglich ist!!
	$retval=array();
	
	$groupParamHash=array("fp" => true);
	
	// prüft auf Gruppen im Molekül und addiert 2 Fingerprints zusammen
	$fingerprint=0;
	$shift=0;
	// N-containing
	// function isNitro($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"N",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isNitroso($haystackMolecule,$fingerprint=true) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"N",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isCarboxamide($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"N",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isCarboximide($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"N",0,1);
		addAtomToGroup($group,"C",2,1);
		addAtomToGroup($group,"O",3,2);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isTertAmine($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"N",-1,0);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) { // 5
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isNitrile($haystackMolecule,$fingerprint=true) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"N",0,3); // maybe metals coord
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isIsonitrile($haystackMolecule,$fingerprint=true) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"N",-1,0);
		addAtomToGroup($group,"C",0,3); // maybe metals coord
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isNitrileOxide($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"N",0,3);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"O",1,2);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isIsoCyanate($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"N",0,2);
		addAtomToGroup($group,"C",1,1);
		addAtomToGroup($group,"O",0,2);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isUrea($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"N",0,1);
		addAtomToGroup($group,"N",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) { // 10
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isCarbamate($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"N",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isHydroxamic($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"N",0,1);
		addAtomToGroup($group,"O",2,1);
		// tautomer
		addAtomToGroup($group2,"C",-1,0);
		addAtomToGroup($group2,"O",0,1);
		addAtomToGroup($group2,"N",0,2);
		addAtomToGroup($group2,"O",2,1);
		if (getSubstMatch($group,$molecule,$groupParamHash) || getSubstMatch($group2,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);
	unset($group2);

	// function isGuanidin($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"N",0,2);
		addAtomToGroup($group,"N",0,1);
		addAtomToGroup($group,"N",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isImin($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"N",-1,0);
		addAtomToGroup($group,"C",0,2);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isOxim($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"N",-1,0);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"C",0,2);
		if (getSubstMatch($group,$molecule,$groupParamHash)) { // 15
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isHydroxylamine($haystackMolecule) {
	// makes problems with polar nitro group, minor importance, therefore removed
	//~ if ($mask & (1 << $shift)) {
		//~ addAtomToGroup($group,"N",-1,0);
		//~ addAtomToGroup($group,"O",0,1);
		//~ addAtomToGroup($group,"C",0,1);
		//~ if (getSubstMatch($group,$molecule,$groupParamHash)) {
			//~ $fingerprint|=(1 << $shift);
		//~ }
	//~ }
	$shift++;
	unset($group);

	// function isNOxide($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		// for quinuclidine-n-oxide
		addAtomToGroup($group,"N",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);

		// for py-n-oxide like stuff
		addAtomToGroup($group3,"N",-1,0);
		addAtomToGroup($group3,"O",0,2);
		addAtomToGroup($group3,"C",0,2);
		addAtomToGroup($group3,"C",0,1);

		if (getSubstMatch($group,$molecule,$groupParamHash) || getSubstMatch($group3,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);
	unset($group3);

	// function isAminal($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"N",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isAzo($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"N",-1,0);
		addAtomToGroup($group,"N",0,2);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isAzide($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"N",-1,0);
		addAtomToGroup($group,"N",0,2);
		addAtomToGroup($group,"N",0,3);
		if (getSubstMatch($group,$molecule,$groupParamHash)) { // 20
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// oxo-species
	// function isEsterCarbox($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"C",2,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isAcidHalogenideCarbox($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"X",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isAnhydrideCarbox($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"C",2,1);
		addAtomToGroup($group,"O",3,2);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isMalonicAcid($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",3,1);
		addAtomToGroup($group,"O",4,2);
		addAtomToGroup($group,"O",4,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isCarbonate($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"O",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) { // 25
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isOrthoEster($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"C",1,1);
		addAtomToGroup($group,"C",2,1);
		addAtomToGroup($group,"C",3,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isPeroxide($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"O",-1,0);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isEther($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"O",-1,0);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isCarbaldehyde($haystackMolecule,$fingerprint=true) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);
	
	// function isKeton($haystackMolecule) { // also enolate
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);

		addAtomToGroup($group2,"C",-1,0);
		addAtomToGroup($group2,"O",0,1);
		addAtomToGroup($group2,"C",0,2);
		addAtomToGroup($group2,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash) || getSubstMatch($group2,$molecule,$groupParamHash)) { // 30
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);
	unset($group2);

	// function isEnon($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"O",-1,0);
		addAtomToGroup($group,"C",0,2);
		addAtomToGroup($group,"C",1,1);
		addAtomToGroup($group,"C",2,2);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	// 31 bit voll, Wechsel----------------------------------------------------------------------------------------------------------------
	unset($group);

	$retval[]=$fingerprint;
	$fingerprint=0;
	$shift=0;
	$mask=$mask2;

	// function isAcetal($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"C",1,1);
		addAtomToGroup($group,"O",3,1);
		addAtomToGroup($group,"C",4,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// sulfur-containing
	// function isThioether($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"S",-1,0);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isDisulfide($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"S",-1,0);
		addAtomToGroup($group,"S",0,1);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",1,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isThioketon($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"S",0,2);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		
		addAtomToGroup($group2,"C",-1,0);
		addAtomToGroup($group2,"S",0,1);
		addAtomToGroup($group2,"C",0,2);
		addAtomToGroup($group2,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash) || getSubstMatch($group2,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isSulfoxide($haystackMolecule,$fingerprint=true) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"S",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isSulfon($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"S",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) { // 6
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isSulfonicAcid($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"S",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isSulfonicAmide($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"S",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"N",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isSulfonicHalogenide($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"S",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"X",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isSulfate($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"S",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"O",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// phosphorus-containing
	// function isPhosphane($haystackMolecule) { // trialkyl/aryl
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"P",-1,0);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) { // 11
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isPhosphone($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"P",-1,0);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"O",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isPhosphonate($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"P",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isPhosphate($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"P",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"O",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isPhosphorusHalogenide($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"P",-1,0);
		addAtomToGroup($group,"X",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// boron-containing
	// function isBorane($haystackMolecule) { // trialkyl/aryl
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"B",-1,0);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) { // 16
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isBoronicAcid($haystackMolecule) { // or ester
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"B",-1,0);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"O",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isBoroHalogenide($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"B",-1,0);
		addAtomToGroup($group,"X",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// heteroatoms
	// function isTinOrganic($haystackMolecule) { // assuming at least one organo and one halogen substituent
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"Sn",-1,0);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"X",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isTrialkylsilyl($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"Si",-1,0);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isTrifluoromethyl($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"F",0,1);
		addAtomToGroup($group,"F",0,1);
		addAtomToGroup($group,"F",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) { // 21
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// c-c-bonds
	
	// muß sein, wenn zB jemand nach Ethylen oder Acetylen sucht und die Multibondfingerprints nicht greifen
	
	// function isAlkene($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"C",0,2);
		// also accept aromatic
		addAtomToGroup($group2,"C",-1,0);
		addAtomToGroup($group2,"C",0,1.5);
		if (getSubstMatch($group,$molecule,$groupParamHash) || getSubstMatch($group2,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);
	unset($group2);
	
	// function isAlkyne($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"C",0,3);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isTertbutyl($haystackMolecule,$fingerprint=true) {
 	if ($mask & (1 << $shift)) {
 		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
 			$fingerprint|=(1 << $shift);
		}
 	}
 	$shift++;
 	unset($group);
	
	// function isCarboxylicDeriv($haystackMolecule) {
	if ($mask & (1 << $shift)) {
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"O",0,2);
		addAtomToGroup($group,"O",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);

	// function isPhenylHalogenide($haystackMolecule,$fingerprint=true) {
	if ($mask & (1 << $shift)) { // 26
		addAtomToGroup($group,"C",-1,0);
		addAtomToGroup($group,"C",0,1.5);
		addAtomToGroup($group,"C",1,1.5);
		addAtomToGroup($group,"C",2,1.5);
		addAtomToGroup($group,"C",3,1.5);
		addAtomToGroup($group,"C",4,1.5);
		addBondToGroup($group,0,5,1.5);
		addAtomToGroup($group,"X",0,1);
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);
	
	$protons=$molecule["eProt"];
	if (!$paramHash["forStructureSearch"]) {
		$protons+=$molecule["iProt"];
	}
	
	if ($mask & (1 << $shift)) { // 27
		if ($protons>0) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	
	if ($mask & (1 << $shift)) { // 28
		if ($protons>1) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	
	//~ $groupParamHash["fp"]=false; // have these only for real olefins? But what happens if someone searches for perylene or similar, and by accident, there is a "saturated ring" ring in the middle of the query structure
	if ($mask & (1 << $shift)) { // 29
		addAtomToGroup($group,"C",-1,0); // 0
		addAtomToGroup($group,"C",0,1);
		addAtomToGroup($group,"C",1,1);
		addAtomToGroup($group,"C",2,1);
		addAtomToGroup($group,"C",3,1); // 4
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	// do not unset($group);
	
	if ($mask & (1 << $shift)) { // 30
		addAtomToGroup($group,"C",4,1); // 5
		addAtomToGroup($group,"C",5,1);
		addAtomToGroup($group,"C",6,1);
		addAtomToGroup($group,"C",7,1);
		addAtomToGroup($group,"C",8,1); // 9
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	// do not unset($group);
	
	if ($mask & (1 << $shift)) { // 31
		addAtomToGroup($group,"C",9,1); // 10
		addAtomToGroup($group,"C",10,1);
		addAtomToGroup($group,"C",11,1);
		addAtomToGroup($group,"C",12,1);
		addAtomToGroup($group,"C",13,1); // 14
		if (getSubstMatch($group,$molecule,$groupParamHash)) {
			$fingerprint|=(1 << $shift);
		}
	}
	$shift++;
	unset($group);
	
	$retval[]=$fingerprint;
	
	//~ echo $fingerprint1."E".$fingerprint;
	return $retval;
}

function getSumFingerprint($molecule) {
	// berechnet Fingerprint aufgrund der Summenformel
	$fingerprint=0;
	$shift=0;
	// "+" schiebt ein Bit weiter, am Anfang sind Atome bis Cl, die in addToFingerprint gesondert behandelt werden und kein + brauchen
	$symbols=array("H","C","O","N","F","Cl","Br","+","I","+","B","+","Si","+","S","+","P","+","Li","Na","K","Rb","Cs","+","Be","Mg","Ca","Sr","Ba","+","Al","Ga","In","Tl","Sn","Pb","Bi","+","Sc","Ti","V","Cr","Mn","Fe","Co","Ni","Cu","Zn","+","Y","Zr","Nb","Mo","Tc","Ag","Cd","Hf","Ta","W","Re","Au","Hg","+","Ru","Rh","Pd","Os","Ir","Pt","+","La","Ce","Pr","Nd","Pm","Sm","Eu","Gd","Tb","Dy","Ho","Er","Tm","Yb","Lu","+","Ge","As","Sb","Se","Te","He","Ne","Ar","Kr","Xe","Rn","Po","At","Fr","Ra","Ac","Th","Pa","U","Np","Pu","Am","Cm","Bk","Cf","Es","Fm","Md","No","Lr","Unq","Unp","Unh","Uns","+");
	foreach ($symbols as $sym) {
		addToFingerprint($fingerprint,$shift,$sym,$molecule["emp_formula"][$sym]);
	}
	return $fingerprint;
}

function calculateFingerprint(& $molecule,$paramHash=array()) {
	global $max31;
	//~ $max=-1; // 0xFFFFFFFF; // all 32 bit set
	
	// summenformel	
	$molecule["fingerprints"][0]=intval(getSumFingerprint($molecule)); // für substruktursuche die ersten 4 bit wegmaskieren (implizite Hs zählen nicht)
	// 31 bit
	
	if (empty($molecule["bondsFromNeighbours"])) { // no bonds
		$molecule["fingerprints"]=array_merge($molecule["fingerprints"],array_fill(0,12,0));
		return;
	}
	
	// Ringe
	if ($molecule["ringOverflow"]) { // no fingerprint for rings possible
		$molecule["fingerprints"][1]=$max31;
		$molecule["fingerprints"][2]=$max31;
	}
	else {
		$fingerprint=0;
		$shift=0;
		$symbols=array(
			3, // 2
			4, // 2
			5, // 3
			6, // 3
			7, // 1
			8, // 1
			9, // 1
			10, // 1
			11, // 1
			12, // 3
			"Ph", // 5
			"Cy", // 3
			"Cp-", // 3
			"Cyclopropyl", // 2
		);
		foreach ($symbols as $sym) {
			addToFingerprint($fingerprint,$shift,$sym,$molecule["ringtypes"][$sym]);
		}
		$molecule["fingerprints"][1]=intval($fingerprint);
		// 31 bit
		$fingerprint=0;
		$shift=0;
		$symbols=array(
			"Cyclobutyl", // 2
			"Cyclopentyl", // 3
			"Py", // 2
			"Oxiran", // 2
			"Thiophen", // 2
			"Pyrrol", // 2
			"Furan", // 1
			"Pyrylium", // 1
			"Pyridazin", // 1
			"Pyrimidin", // 1
			"Pyrazin", // 1
			"123Triazin", // 1
			"124Triazin", // 1
			"135Triazin", // 1
			"Imidazol", // 2
			"Oxazol", // 1
			"Thiazol", // 1
			"Pyrazol", // 2
			"Isoxazol", // 1
			"123Triazol", // 1
			"124Triazol", // 1
			"Morpholin", // 1
		);
		foreach ($symbols as $sym) {
			addToFingerprint($fingerprint,$shift,$sym,$molecule["ringtypes"][$sym]);
		}
		// 31 bit
		$molecule["fingerprints"][2]=intval($fingerprint);
	}

	// Gruppen
	list($molecule["fingerprints"][3],$molecule["fingerprints"][4])=getGroupFingerprint($molecule,$paramHash);
	//~ $molecule["fingerprints"]=array_merge($molecule["fingerprints"],getGroupFingerprint($molecule));
	// 31 + 26 bit
	
	// 8x32bit
	// neu: 8x31bit+1x8bit
	$molecule["fingerprints"]=array_merge($molecule["fingerprints"],FPall($molecule,$paramHash));
}


?>