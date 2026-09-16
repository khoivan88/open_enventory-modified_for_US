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

$sat_rings=array(
	"CCCCCC" =>"Cy", 
	"CCC" =>"Cyclopropyl", 
	"CCCC" =>"Cyclobutyl", 
	"CCCCC" =>"Cyclopentyl", 
	"CCNCCO" =>"Morpholin", 
	"CCO" =>"Oxiran", 
);


$ar_rings=array(
	"CCCCCC" => "Ph",
	"CCCCC" => "Cp-",
	"CCCCCO" => "Pyrylium",
	"CCCCCN" => "Py",
	"CCCCNN" => "Pyridazin",
	"CCCNCN" => "Pyrimidin",
	"CCCCNN" => "Pyridazin",
	"CCNCCN" => "Pyrazin",
	"CCCNNN" => "123Triazin",
	"CCNCNN" => "124Triazin",
	"CNCNCN" => "135Triazin",
	"CCCCS" => "Thiophen",
	"CCCCO" => "Furan",
	"CCCCN" => "Pyrrol",
	"CCNCN" => "Imidazol",
	"CCOCN" => "Oxazol",
	"CCSCN" => "Thiazol",
	"CCCNN" => "Pyrazol",
	"CCCON" => "Isoxazol",
	"CCNNN" => "123Triazol",
	"CNCNN" => "124Triazol",
);

function transformMoleculeForDisplay(& $molecule) { // aus paintMolecule aufrufen, nur orig_bond_order und orig_charge ändern
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		switch ($molecule["atoms"][$a][ATOMIC_SYMBOL]) {
		case "N":
			// Nitro: 5-bindigen Stickstoff durch ladungsgetrennte Form ersetzen
			if ($molecule["atoms"][$a][ORIG_BONDS]!=5) {
				continue 2;
			}
			// terminalen Nachbarn höchster Bindungsordnung suchen und Bindungsordnung verringern
			unset($reduce_atom);
			unset($reduce_order);
			for ($b=0;$b<count($molecule["atoms"][$a][NEIGHBOURS]);$b++) {
				$neighbour_no=$molecule["atoms"][$a][NEIGHBOURS][$b];
				if ($molecule["atoms"][$neighbour_no][NON_H_NEIGHBOURS]>1) { // terminal?
					continue;
				}
				if ($molecule["atoms"][$neighbour_no][BONDS]>$reduce_order && $molecule["bondsFromNeighbours"][$a][$neighbour_no][ORIG_BOND_ORDER]>1) { // prevent 0 bonds
					$reduce_atom=$neighbour_no;
					$reduce_order=$molecule["atoms"][$neighbour_no][BONDS];
				}
			}
			if (isset($reduce_atom)) {
				// N
				$molecule["atoms"][$a][BONDS]--;
				$molecule["atoms"][$a][NON_H_BONDS]--;
				$molecule["atoms"][$a][ORIG_CHARGE]++;
				$molecule["atoms"][$a][ORIG_BONDS]--;
				// O etc.
				$molecule["atoms"][$reduce_atom][BONDS]--;
				$molecule["atoms"][$reduce_atom][NON_H_BONDS]--;
				$molecule["atoms"][$reduce_atom][ORIG_CHARGE]--;
				$molecule["atoms"][$reduce_atom][ORIG_BONDS]--;
				// Bindung
				for ($b=0;$b<count($molecule[BONDS]);$b++) {
					if (($molecule[BONDS][$b][ATOM1]==$a && $molecule[BONDS][$b][ATOM2]==$reduce_atom) || ($molecule[BONDS][$b][ATOM2]==$a && $molecule[BONDS][$b][ATOM1]==$reduce_atom)) {
						$molecule[BONDS][$b][ORIG_BOND_ORDER]--;
						break;
					}
				}
				//~ $molecule["bondsFromNeighbours"][$a][$reduce_atom][ORIG_BOND_ORDER]--;
			}
		break;
		case "S":
			// Sulfoxide: C2S=O durch C2S[+]-O[-] ersetzen
			// S suchen und Nachbarn checken
			if ($molecule["atoms"][$a][ORIG_BONDS]!=4) {
				continue 2;
			}
			$neighbour_elements=array();
			unset($reduce_atom);
			for ($b=0;$b<count($molecule["atoms"][$a][NEIGHBOURS]);$b++) {
				$neighbour_no=$molecule["atoms"][$a][NEIGHBOURS][$b];
				$neighbour_elements[ $molecule["atoms"][$neighbour_no][ATOMIC_NUMBER] ]++;
				if ($molecule["atoms"][$neighbour_no][ATOMIC_NUMBER]==8 && $molecule["bondsFromNeighbours"][$a][$neighbour_no][ORIG_BOND_ORDER]>1) { // O
					$reduce_atom=$neighbour_no;
				}
			}
			if ($neighbour_elements[6]==2 && $neighbour_elements[8]==1) { // Sulfoxid
				// S
				$molecule["atoms"][$a][BONDS]--;
				$molecule["atoms"][$a][NON_H_BONDS]--;
				$molecule["atoms"][$a][ORIG_CHARGE]++;
				$molecule["atoms"][$a][ORIG_BONDS]--;
				// O
				$molecule["atoms"][$reduce_atom][BONDS]--;
				$molecule["atoms"][$reduce_atom][NON_H_BONDS]--;
				$molecule["atoms"][$reduce_atom][ORIG_CHARGE]--;
				$molecule["atoms"][$reduce_atom][ORIG_BONDS]--;
				// Bindung
				// find bond as using bondsFromNeighbours causes undesired corruption
				for ($b=0;$b<count($molecule[BONDS]);$b++) {
					if (($molecule[BONDS][$b][ATOM1]==$a && $molecule[BONDS][$b][ATOM2]==$reduce_atom) || ($molecule[BONDS][$b][ATOM2]==$a && $molecule[BONDS][$b][ATOM1]==$reduce_atom)) {
						$molecule[BONDS][$b][ORIG_BOND_ORDER]--;
						break;
					}
				}
				//~ $molecule["bondsFromNeighbours"][$a][$reduce_atom][ORIG_BOND_ORDER]--;
			}
		break;
		}
	}
	//~ return $molecule; // byref makes serious trouble
}

function matchRingAtoms($needle,$haystack) {
	// Frage: Ist CCNOC in NOCCC enthalten?
	// NOCCCNOCCC auf CCNOC und CONCC (umgek Reihenf) prüfen
	if (strlen($needle)!=strlen($haystack)) {
		return false;
	}
	$bigHaystack=$haystack.$haystack;
	if (strpos($bigHaystack,$needle)!==FALSE) {
		return true;
	}
	if (strpos($bigHaystack,strrev($needle))!==FALSE) {
		return true;
	}
	return false;
}

function isRingInMolecule(& $molecule,$members) { // check if already found
	// alle Ringe durchgehen
	$membersCount=count($members);
	sort($members,SORT_NUMERIC);
	for ($b=0;$b<count($molecule[RINGS]);$b++) {
		// stimmt die Anzahl?, ist atom 1 jew nicht enthalten?
		if (count($molecule[RINGS][$b]["atoms"])!=$membersCount || !in_array($molecule[RINGS][$b]["atoms"][0],$members) || !in_array($members[0],$molecule[RINGS][$b]["atoms"])) {
			continue;
		}
		// könnte identisch sein
		// Idee: beide sortieren und auf ident prüfen
		$tempRing=$molecule[RINGS][$b]["atoms"];
		sort($tempRing,SORT_NUMERIC);
		if ($tempRing==$members) {
			return true;
		}
	}
	return false;
}

function findRings(& $molecule,$path) { // get all possible rings, for naphthalene it should be 3 (6,6,10)
	$path_length=count($path);
	if ($path_length>MAX_AROMAT_SIZE) {
		return;
	}
	$lastAtom=end($path);

	// mark part no
	if (!isset($molecule["atoms"][$lastAtom][PART])) {
		$part=count($molecule["parts"])-1;
		$molecule["atoms"][$lastAtom][PART]=$part;
		$molecule["parts"][$part]["atoms"][]=$lastAtom;
	}

	// avoid going back directly
	if ($path_length>1) {
		$prevAtom=prev($path);
	}
	else {
		$prevAtom=-1;
	}

	// go through neighbours, recurse and check if one has been visited already IN THIS PATH
	for ($a=0;$a<count($molecule["atoms"][$lastAtom][NEIGHBOURS]);$a++) {
		$nextAtom=$molecule["atoms"][$lastAtom][NEIGHBOURS][$a];
		if ($nextAtom==$prevAtom) {
			continue;
		}

		// find closure in path
		$foundAt=array_search($nextAtom,$path);
		if ($foundAt!==FALSE) {
			// ring found with the subPath starting at found_at, to the end of the ArrayList
			$members=array_slice($path,$foundAt);

			// check if we had this ring already, maybe with other direction or starting point
			$membersCount=count($members);
			// check if already found
			if ($membersCount<3 || isRingInMolecule($molecule,$members)) { // sinnlos bzw kein neuer Ring
				continue;
			}
			if ($membersCount>$molecule["maxRingSize"]) {
				$molecule["maxRingSize"]=$membersCount;
			}
			
			$ringCount=count($molecule[RINGS]);
			$molecule[RINGS][$ringCount]=array(
				"atoms" => $members, 
				"size" => $membersCount, 
				PART => $molecule["atoms"][$lastAtom][PART], 
			);
			
			// mark members
			for ($b=0;$b<$membersCount;$b++) {
				$molecule["atoms"][ $members[$b] ][RINGS][]=$ringCount;
			}
		}
		else {
			// recurse into
			$path[]=$nextAtom;
			findRings($molecule,$path);
			array_pop($path);
		}
	}
}

function procRing(& $molecule,$ring_no) {
	global $sat_rings,$ar_rings;
	
	$membersCount=$molecule[RINGS][$ring_no]["size"];
	if ($membersCount>MAX_AROMAT_SIZE) {
		return;
	}
	
	// determine isAro
	$members=$molecule[RINGS][$ring_no]["atoms"];
	for ($a=0;$a<count($members);$a++) {
		$atomNo0=$members[$a];
		$atomPiElectrons=getPiElectrons($molecule,$atomNo0);

		// allow once sp3 for HCp
		if (!$hadHcpRule && $atomPiElectrons==-2 && $membersCount==5) {
			$hadHcpRule=true;
			$atomPiElectrons=2;
		}
		if ($atomPiElectrons<0) { // not suitable for aromat
			$thisPiElectrons=0;
			break;
		}
		$thisPiElectrons+=$atomPiElectrons;
	}
	
	$isAro=($thisPiElectrons>0 && (($thisPiElectrons-2)%4)==0);
	$molecule[RINGS][$ring_no][AROMATIC]=$isAro;
	
	for ($a=0;$a<count($members);$a++) {
		$atomNo0=$members[$a];
		$atomNo1=$members[ ($a+1)%$membersCount ];
		$atom0=&$molecule["atoms"][$atomNo0];
		$atom1=&$molecule["atoms"][$atomNo1];
		$molecule["bondsFromNeighbours"][$atomNo0][$atomNo1][RINGS][]=$ring_no;
		
		$centerX+=$atom0["x"]/$membersCount;
		$centerY+=$atom0["y"]/$membersCount;
		$ringAtoms.=$atom0[ATOMIC_SYMBOL];
		
		$bondOOrder=$molecule["bondsFromNeighbours"][$atomNo0][$atomNo1][ORIG_BOND_ORDER];
		if ($bondOOrder==1) {
			$singleBonds++;
		}
		if ($isAro) {
			// aromatize bond
			if ($bondOOrder==3) { // aryne
				$molecule["bondsFromNeighbours"][$atomNo0][$atomNo1][BOND_ORDER]=2.5;
			}
			else {
				$molecule["bondsFromNeighbours"][$atomNo0][$atomNo1][BOND_ORDER]=1.5;
			}
			if ($atom0[ATOMIC_SYMBOL]=="C" && $atom0[CHARGE]==-1) {
				$atom0[SMILES_CHARGE]=$atom0[CHARGE]; // Ladung für SMILES zwischenlagern, geht nicht in canonisation ein
				$atom0[CHARGE]=0;
			}
			if (!$hadHcpRule) { // prevent HCp from being marked as ar
				$molecule["atoms"][$atomNo0][AROMATIC]=true;
			}
		}
	}

	// do aliphatic rings BEFORE aromatic
	if ($membersCount==$singleBonds) { // all single bonds
		// consider sequence of atoms
		foreach ($sat_rings as $atoms => $key) {
			if (matchRingAtoms($atoms,$ringAtoms)) {
				$type=$key;
				break;
			}
		}

		// consider ring size, additional
		$type=min($membersCount, FINGERPRINT_MAX_RING_SIZE);
	}
	
	// now comes the aromatic
	if ($isAro) {
		// consider sequence of atoms
		foreach ($ar_rings as $atoms => $key) {
			if (matchRingAtoms($atoms,$ringAtoms)) {
				$type=$key;
				$somethingFound=true;
				break;
			}
		}

		if (!$somethingFound) { // only recorded if no standard aromat matches
			$type=min($membersCount, FINGERPRINT_MAX_RING_SIZE);
		}
	}
	
	$molecule[RINGS][$ring_no]["x"]=$centerX;
	$molecule[RINGS][$ring_no]["y"]=$centerY;
	if (!empty($type)) {
		$molecule[RINGS][$ring_no]["type"]=$type;
		$molecule["ringtypes"][$type]++;
	}
}

?>