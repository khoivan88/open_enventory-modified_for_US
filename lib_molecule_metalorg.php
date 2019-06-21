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

/* function fixMetalAllyl(& $molecule,$atom_no) { // Allylbindungen aromatisieren (auch wenn es eigentlich KEIN Aromat ist, Ladung wird weggenommen, um Symmetrie zu erreichen
	$retval=false;
	
	for ($a=0;$a<count($molecule["atoms"][$atom_no][NEIGHBOURS]);$a++) { // Nachbarn durchgehen
		$neighbour_atom=$molecule["atoms"][$atom_no][NEIGHBOURS][$a];
		if ($molecule["atoms"][$neighbour_atom]["hasMetalNeighbour"] && $molecule["bondsFromNeighbours"][$atom_no][$neighbour_atom][BOND_ORDER]==1) {
			// Allyl-Konstellation gefunden
			$retval=true;
			$molecule["bondsFromNeighbours"][$atom_no][$neighbour_atom][BOND_ORDER]=1.5;
			// Ladung wegnehmen für Symmetrie
			$molecule["atoms"][$neighbour_atom][SMILES_CHARGE]=$molecule["atoms"][$neighbour_atom][CHARGE]; // Ladung für SMILES zwischenlagern, geht im 1. Pass nicht in canonisation ein
			$molecule["atoms"][$neighbour_atom][CHARGE]=0; // o_c MUSS bleiben für Zeichnung und schreiben von Molfile
		}
	}
	return $retval;
} */

/* function fixAllylConjugation(& $molecule,$path) {
/*
[X-]			[$neighbour_atom] = [$new_atom_no]...
	\			/
	[Y]=[$atom_no]	
* /
	$atom_no=end($path);
	
	for ($a=0;$a<count($molecule["atoms"][$atom_no][NEIGHBOURS]);$a++) { // Nachbarn durchgehen
		$neighbour_atom=$molecule["atoms"][$atom_no][NEIGHBOURS][$a];
		// prüfen, ob weitere Doppelbindung an $neighbour_atom hängt (=> Konjugation)
		for ($b=0;$b<count($molecule["atoms"][$neighbour_atom][NEIGHBOURS]);$b++) {
			$new_atom_no=$molecule["atoms"][$neighbour_atom][NEIGHBOURS][$b];
			if ($molecule["bondsFromNeighbours"][$new_atom_no][$neighbour_atom][BOND_ORDER]!=2 || in_array($new_atom_no,$path)) { // prevent endless loop
				continue;
			}
			$molecule["bondsFromNeighbours"][$atom_no][$neighbour_atom][BOND_ORDER]=1.5;
			$molecule["bondsFromNeighbours"][$new_atom_no][$neighbour_atom][BOND_ORDER]=1.5;
			// Nachbarn von new $atom_no durchgehen
			$new_path=$path;
			$new_path[]=$neighbour_atom;
			$new_path[]=$new_atom_no;
			fixAllylConjugation($molecule,$new_path);
		}
	}
} */

function getHybridisation(& $molecule,$atom_no) { // spx
	if (
		$atom_no<0 || 
		$atom_no>=count($molecule["atoms"]) || 
		$molecule["atoms"][$atom_no][ATOMIC_SYMBOL]=="H" || 
		$molecule["atoms"][$atom_no][NON_H_BONDS]>5 || // Sulfate, Phosphate
		isMetal($molecule["atoms"][$atom_no][ATOMIC_SYMBOL]) 
	) {
		return;
	}
	if (isset($molecule["atoms"][$atom_no][HYBRIDISATION_STATE])) {
		return $molecule["atoms"][$atom_no][HYBRIDISATION_STATE];
	}
	$nitro_fix=0;
	if ($molecule["atoms"][$atom_no][ATOMIC_SYMBOL]=="N" && $molecule["atoms"][$atom_no][NON_H_BONDS]==5) {
		$nitro_fix=1;
	}
	$hybridisation=constrainVal(
		3
		+$molecule["atoms"][$atom_no][NON_H_NEIGHBOURS]
		-$molecule["atoms"][$atom_no][NON_H_BONDS]
		+$nitro_fix
		+abs($molecule["atoms"][$atom_no][CHARGE]),
		0,3
	);
	
	$molecule["atoms"][$atom_no][HYBRIDISATION_STATE]=$hybridisation;
	return $hybridisation;
}

function getPiElectrons(& $molecule,$atom_no) {
	// assume it was done
	$hybridisation=$molecule["atoms"][$atom_no][HYBRIDISATION_STATE];
	$charge=$molecule["atoms"][$atom_no][CHARGE];
	$valency=$molecule["atoms"][$atom_no][VALENCY];
	
	if ($hybridisation<=2) {
		if ($molecule["atoms"][$atom_no][NON_H_BONDS]<$valency && $charge>0) { // C+, tropylium
			return 0;
		}
		// accept that COT2- will not be recognized, rather theoretical problem
		return 1;
	}
	if ($hybridisation==3) {
		if ($valency>=5) { // N, P, O, S, like in pyrrol, furane, thiophene
			return 2;
		}
		if ($charge<0) { // C-, like in Cp-
			return 2;
		}
		elseif ($charge==0 && $valency==4 && $molecule["atoms"][$atom_no][H_NEIGHBOURS]>0) { // CH, could be HCp
			return -2;
		}
	}
	return -1;
}

function getBondStability(& $molecule,$a1,$a2) {
	$score=0;
	$polarity=abs(getEneg($molecule["atoms"][$a1][ATOMIC_NUMBER])-getEneg($molecule["atoms"][$a2][ATOMIC_NUMBER]));
	if (
		$molecule["bondsFromNeighbours"][$a1][$a2][ORIG_BOND_ORDER]!=1 
		&& 
		$polarity<0.6
	) { // metathesis/wittig, ok
		$score++;
	}
	if (
		$molecule["atoms"][$a1][ATOMIC_SYMBOL]=="C" 
		&& 
		$molecule["atoms"][$a2][ATOMIC_SYMBOL]=="C"
	) {
		$score++;
	}
	if ($molecule["bondsFromNeighbours"][$a1][$a2][BOND_ORDER]==1.5) {
		$score+=6;
	}
	//~ $score-=$polarity;
	return sqrt($score);
}

function getBondPolarity(& $molecule,$bond_no) {
	if (isset($molecule[BONDS][$bond_no][BOND_POLARITY])) {
		return $molecule[BONDS][$bond_no][BOND_POLARITY];
	}
	if ($bond_no<0 || $bond_no>=count($molecule[BONDS])) {
		return;
	}
	$atom_no1=$molecule[BONDS][$bond_no][ATOM1];
	$atom_no2=$molecule[BONDS][$bond_no][ATOM2];
	$eneg1=getEneg($molecule["atoms"][$atom_no1][ATOMIC_NUMBER]);
	$eneg2=getEneg($molecule["atoms"][$atom_no2][ATOMIC_NUMBER]);
	if ($eneg1==0.0 || $eneg2==0.0) {
		return false;
	}
	$retval=$eneg1-$eneg2;
	$molecule[BONDS][$bond_no][BOND_POLARITY]=$retval;
	return $retval;
}

function getOxidationState(& $molecule,$atom_no) {
	if ($atom_no<0 || $atom_no>=count($molecule["atoms"])) {
		return;
	}
	if (isset($molecule["atoms"][$atom_no][OXIDATION_STATE])) {
		return $molecule["atoms"][$atom_no][OXIDATION_STATE];
	}
	$ox_state=$molecule["atoms"][$atom_no][CHARGE]; // Ladung
	$eneg=getEneg($molecule["atoms"][$atom_no][ATOMIC_NUMBER]);
	if ($eneg==0.0) {
		return;
	}
	$tol=0.2; // if the diff is smaller, considered equal
	for ($a=0;$a<count($molecule["atoms"][$atom_no][NEIGHBOURS]);$a++) {
		$neighbour_atom=$molecule["atoms"][$atom_no][NEIGHBOURS][$a];
		$neighbour_eneg=getEneg($molecule["atoms"][$neighbour_atom][ATOMIC_NUMBER]);
		if ($neighbour_eneg>0.0) {
			$bond_order=SMgetOrder($molecule,$atom_no,$neighbour_atom);
			if ($neighbour_eneg-$eneg>$tol) {
				$ox_state+=$bond_order;
			}
			elseif ($eneg-$neighbour_eneg>$tol) {
				$ox_state-=$bond_order;
			}
		}
	}
	// impl Hyd
	$bond_order=$molecule["atoms"][$atom_no][IMPLICIT_H];
	if ($bond_order>0) {
		$neighbour_eneg=getEneg(1);
		if ($neighbour_eneg-$eneg>$tol) {
			$ox_state+=$bond_order;
		}
		elseif ($eneg-$neighbour_eneg>$tol) {
			$ox_state-=$bond_order;
		}
	}
	$molecule["atoms"][$atom_no][OXIDATION_STATE]=$ox_state;
	return $ox_state;
}

function walkAllylConjugation(& $molecule,$path=array()) {
	$path_count=count($path);
	if ($path_count<1) {
		return;
	}
	$atom_no=$path[$path_count-1];
	for ($a=0;$a<count($molecule["atoms"][$atom_no][NEIGHBOURS]);$a++) {
		$new_atom=$molecule["atoms"][$atom_no][NEIGHBOURS][$a];
		if (in_array($new_atom,$path)) { // no loops, too dangerous
			continue;
		}
		$new_path=$path;
		$new_path[]=$new_atom;
		if ($path_count%2) { // ungerade, Einfachbdg durchgehen
			if ($molecule["bondsFromNeighbours"][$atom_no][$new_atom][ORIG_BOND_ORDER]!=1 && $molecule["bondsFromNeighbours"][$atom_no][$new_atom][BOND_ORDER]!=1) {
				continue;
			}
			// nix tun
			walkAllylConjugation($molecule,$new_path);
		}
		else { // gerade, Doppelbdg durchgehen
			if ($molecule["bondsFromNeighbours"][$atom_no][$new_atom][ORIG_BOND_ORDER]!=2 && $molecule["bondsFromNeighbours"][$atom_no][$new_atom][BOND_ORDER]!=2) { // may have been created by disconnecting metallacyclopropane
				continue;
			}
			// allyl-Konstellation gefunden!!
			$prev_atom=$path[$path_count-2];
			// aromatisieren
			//~ echo "AE".$molecule["atoms"][$prev_atom][BONDS]."F".$molecule["atoms"][$atom_no][BONDS]."G".$molecule["atoms"][$new_atom][BONDS];
			
			$delta=1.5-$molecule["bondsFromNeighbours"][$atom_no][$prev_atom][BOND_ORDER];
			$molecule["atoms"][$prev_atom][BONDS]+=$delta;
			$molecule["atoms"][$prev_atom][NON_H_BONDS]+=$delta;
			
			$delta=
				1.5-$molecule["bondsFromNeighbours"][$atom_no][$prev_atom][BOND_ORDER]
				+1.5-$molecule["bondsFromNeighbours"][$atom_no][$new_atom][BOND_ORDER];
			$molecule["atoms"][$atom_no][BONDS]+=$delta;
			$molecule["atoms"][$atom_no][NON_H_BONDS]+=$delta;
			
			$delta=1.5-$molecule["bondsFromNeighbours"][$atom_no][$new_atom][BOND_ORDER];
			$molecule["atoms"][$new_atom][BONDS]+=$delta;
			$molecule["atoms"][$new_atom][NON_H_BONDS]+=$delta;
			//~ echo "BE".$molecule["atoms"][$prev_atom][BONDS]."F".$molecule["atoms"][$atom_no][BONDS]."G".$molecule["atoms"][$new_atom][BONDS];
			
			$molecule["bondsFromNeighbours"][$atom_no][$new_atom][BOND_ORDER]=1.5;
			$molecule["bondsFromNeighbours"][$atom_no][$prev_atom][BOND_ORDER]=1.5;
			// am ladungstragenden Atom ($path[0]) das letzte Kettenglied $new_atom zur Liste möglicher Ladungsträger hinzufügen
			$molecule["atoms"][ $path[0] ]["SMcharge_atoms"][]=$new_atom;
			$molecule["atoms"][ $path[0] ]["SMpivot_atoms"][]=$atom_no;
			if ($path_count==2) {
				array_unshift($molecule["atoms"][ $path[0] ]["SMcharge_atoms"],$path[0]);
				$molecule["atoms"][ $path[0] ][SMILES_CHARGE]+=$molecule["atoms"][ $path[0] ][CHARGE];
				$molecule["atoms"][ $path[0] ][CHARGE]=0;
			}
			walkAllylConjugation($molecule,$new_path);
		}
	}
}

function walkAllylAlternate($molecule,$orig_atom,$path=array()) {
	$path_count=count($path);
	if ($path_count<1) {
		return;
	}
	if (!count($molecule["atoms"][$orig_atom]["SMpivot_atoms"]) || !count($molecule["atoms"][$orig_atom]["SMcharge_atoms"])) {
		return;
	}
	$atom_no=$path[$path_count-1];
	for ($a=0;$a<count($molecule["atoms"][$atom_no][NEIGHBOURS]);$a++) {
		$new_atom=$molecule["atoms"][$atom_no][NEIGHBOURS][$a];
		if ($molecule["bondsFromNeighbours"][$atom_no][$new_atom][BOND_ORDER]!=1.5 || $molecule["atoms"][$new_atom]["ar"] || in_array($new_atom,$path)) {
		// 	not aromatized, 										in real aromatic ring, 				in loop (too dangerous)
			continue;
		}
		$new_path=$path;
		$new_path[]=$new_atom;
		if ($path_count%2) { // ungerade, Einfachbdg durchgehen
			if (in_array($new_atom,$molecule["atoms"][$orig_atom]["SMpivot_atoms"])) {
				$molecule["bondsFromNeighbours"][$atom_no][$new_atom][SMILES_BOND_ORDER]=1;
				walkAllylAlternate($molecule,$orig_atom,$new_path);
			}
		}
		else {
			if (in_array($new_atom,$molecule["atoms"][$orig_atom]["SMcharge_atoms"])) {
				$molecule["bondsFromNeighbours"][$atom_no][$new_atom][SMILES_BOND_ORDER]=2;
				walkAllylAlternate($molecule,$orig_atom,$new_path);
			}
		}
	}
}

function aromatizeAllyl(& $molecule) {
	global $sideOnEl;
	
	// atome durchgehen und neg ladung suchen
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		if ($molecule["atoms"][$a][CHARGE]<0) {
			walkAllylConjugation($molecule,array($a) );
		}
	}
	
/*	// Einfachbindungen neben ECHTEN Doppelbindungen zu 1.5 machen (original 1 bleibt), wenn ein Metall benachbart ist (Allyl, Enolat,...)
	// vorerst KEIN Propargyl (- und Dreifach)
	for ($a=0;$a<count($molecule[BONDS]);$a++) {
		// ist es echte Doppelbgd
		if ($molecule[BONDS][$a][BOND_ORDER]!=2) { //  && $molecule[BONDS][$a][BOND_ORDER]!=3
			continue;
		}
		
		// sind beide Bindungspartner fähig, side-on an Metalle zu binden?
		// A1,A2 => neighbours
		$a1=$molecule[BONDS][$a][ATOM1];
		$a2=$molecule[BONDS][$a][ATOM2];
		if (!in_array($molecule["atoms"][$a1][ATOMIC_SYMBOL],$sideOnEl) || !in_array($molecule["atoms"][$a2][ATOMIC_SYMBOL],$sideOnEl)) {
			continue;
		}
		
		// Nachbarn von a1 und a2 prüfen
		$a1_allyl=fixMetalAllyl($molecule,$a1); // echte Einfachbindung UND Metall als Nachbarn?
		$a2_allyl=fixMetalAllyl($molecule,$a2);
		
		if ($a1_allyl) {
			fixAllylConjugation($molecule,array($a1,$a2));
		}
		if ($a2_allyl) {
			fixAllylConjugation($molecule,array($a2,$a1));
		}
		
		if (($a1_allyl || $a2_allyl) && $molecule[BONDS][$a][BOND_ORDER]==2) {
			// 1.5 und 1.5
			$molecule[BONDS][$a][BOND_ORDER]=1.5;
		}
		
	} */
}

function getHighestAtomNoFromList(& $molecule,$list) {
	if (!count($list)) {
		return false;
	}
	$highest_atom=$list[0];
	for ($b=1;$b<count($list);$b++) {
		$a=$list[$b];
		
		if (SMisHigherThan($molecule["atoms"][$a],$molecule["atoms"][$highest_atom])) {
			$highest_atom=$a;
		}
	}
	return $highest_atom;	
}

function getLowestAtomNoFromList(& $molecule,$list) {
	if (!count($list)) {
		return false;
	}
	$lowest_atom=$list[0];
	for ($b=1;$b<count($list);$b++) {
		$a=$list[$b];
		
		if (SMisHigherThan($molecule["atoms"][$lowest_atom],$molecule["atoms"][$a])) {
			$lowest_atom=$a;
		}
	}
	return $lowest_atom;	
}

function dearomatizeAllyl(& $molecule) { // Ladung und 2/1-Bindungen bei Allylsystemen wiederherstellen, dadurch werden diese Systeme unique, NUR sm_o für SMILES-Generierung ändern!!
	// Cp-Ringe behandeln (relativ einfach und definiert)
	for ($a=0;$a<count($molecule[RINGS]);$a++) {
		if ($molecule[RINGS][$a]["type"]!="Cp-") {
			continue;
		}
		unset($SMc_atom);
		$membersCount=count($molecule[RINGS][$a]["atoms"]);
		for ($b=0;$b<$membersCount;$b++) { // gibt es SMc (Originalladung)?
			$atom_no=$molecule[RINGS][$a]["atoms"][$b];
			$SMc=$molecule["atoms"][$atom_no][SMILES_CHARGE];
			if ($SMc!=0) {
				$SMc_atom=$atom_no;
				break;
			}
		}
		$highest=getLowestAtomNoFromList($molecule,$molecule[RINGS][$a]["atoms"]);
		if (isset($SMc_atom)) { // echtes Cp-
			// Ladung auf höchstes Ringatom setzen
			$molecule["atoms"][$highest][CHARGE]=$SMc; // leave o_c unchanged
			unset($molecule["atoms"][$SMc_atom][SMILES_CHARGE]);
			// Bonds remain aromatic!!
		}
		else { // eigentlich HCp
			// entaromatisieren, highest wird sp3
			$highest_pos=array_search($highest,$molecule[RINGS][$a]["atoms"]); // 0-4
			for ($b=0;$b<$membersCount;$b++) {
				$a1=$molecule[RINGS][$a]["atoms"][$b];
				$a2=$molecule[RINGS][$a]["atoms"][ ($b+1+$membersCount)%$membersCount ];
				if ($b==$highest_pos || ($b+1+$membersCount)%$membersCount==$highest_pos || ($b+3+$membersCount)%$membersCount==$highest_pos) {
					$molecule["bondsFromNeighbours"][$a1][$a2][SMILES_BOND_ORDER]=1;
				}
				else {
					$molecule["bondsFromNeighbours"][$a1][$a2][SMILES_BOND_ORDER]=2;
				}
			}
		}
	}
	
	// Allylsysteme durchgehen
	// atome durchgehen und neg ladung suchen
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		$SMc=$molecule["atoms"][$a][SMILES_CHARGE];
		if (isset($SMc)) { // Cp-artiges ist schon weg
			$highest=getLowestAtomNoFromList($molecule,$molecule["atoms"][$a]["SMcharge_atoms"]); // MUST be lowest as otherwise we may get vinyl anions
			//~ echo $highest;
			$molecule["atoms"][$highest][CHARGE]+=$SMc; // leave o_c unchanged
			// Bindungen wieder alternierend machen
			walkAllylAlternate($molecule,$a,array($highest));
			//~ unset($molecule["atoms"][$a][SMILES_CHARGE]);
			//~ unset($molecule["atoms"][$a]["SMcharge_atoms"]);
			//~ unset($molecule["atoms"][$a]["SMpivot_atoms"]);
		}
	}
}

function transformForSearchDisconnect(& $molecule) { // muß recht früh durchgeführt werden, vor ringbehandlung, fingerprinting, smiles, usw. DIREKT nach dem Einlesen des Molfiles, weil ggf. neue parts entstehen
	global $valencies,$sideOnEl,$ionicEl,$group16el,$halogens;
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		
		//~ if ($molecule["atoms"][$a][CHARGE]<0) { // treat anion like metal-bound
			//~ $molecule["atoms"][$a]["hasMetalNeighbour"]=true;
		//~ }
		
		if ($molecule["atoms"][$a][ATOMIC_SYMBOL]=="O" && count($molecule["atoms"][$a][NEIGHBOURS])==1) { // fix ionic CO
			$neighbour_b=$molecule["atoms"][$a][NEIGHBOURS][0]; // Carbon
			if ($molecule["atoms"][$neighbour_b][ATOMIC_SYMBOL]=="C" && count($molecule["atoms"][$neighbour_b][NEIGHBOURS])==1 && (
				$molecule["bondsFromNeighbours"][$neighbour_b][$a][BOND_ORDER]==2 || $molecule["bondsFromNeighbours"][$neighbour_b][$a][BOND_ORDER]==3 // C=O bond
			)) {
				// make CO-Bond double with no charges
				$molecule["atoms"][$a][CHARGE]=0;
				$molecule["atoms"][$neighbour_b][CHARGE]=0;
				$molecule["bondsFromNeighbours"][$neighbour_b][$a][BOND_ORDER]=2;
				$molecule["atoms"][$neighbour_b][RADICAL]=1;
				continue;
			}
		}
		
		$hasImplicitHneighbour=($molecule["atoms"][$a][IMPLICIT_H]>0);
		$explicitHneighbour=false;
		if (!$hasImplicitHneighbour) { // try to find explicit H
			for ($b=count($molecule["atoms"][$a][NEIGHBOURS])-1;$b>=0;$b--) {
				$neighbour_b=$molecule["atoms"][$a][NEIGHBOURS][$b];
				if ($molecule["atoms"][$neighbour_b][ATOMIC_SYMBOL]=="H") {
					$explicitHneighbour=$neighbour_b;
					break;
				}
			}
		}
		
		// disconnect acidic protons from
		if (
			($hasImplicitHneighbour || $explicitHneighbour!==FALSE) // has H
			&& (
				(
					in_array($molecule["atoms"][$a][ATOMIC_SYMBOL],$halogens) // HX, X=F,Cl,Br,I => X mit impl oder expl H
					&&
					$molecule["atoms"][$a][NON_H_BONDS]==0
				)
				||
				(
					in_array($molecule["atoms"][$a][ATOMIC_SYMBOL],$group16el) // O/S müssen weiter geprüft werden
					&&
					$molecule["atoms"][$a][NON_H_BONDS]<=1
					&&
					$molecule["atoms"][$a][CHARGE]>=0 // no OH- etc
				)
				||
				(
					in_array($molecule["atoms"][$a][ATOMIC_SYMBOL],array("N","O","P")) // R3NH+, R3PH+, H3O+ => El mit pos Ladung und impl oder expl H
					&&
					$molecule["atoms"][$a][CHARGE]==1
				)
			)
		) {
			
			// separate acidic protons
			if (in_array($molecule["atoms"][$a][ATOMIC_SYMBOL],$group16el) && $molecule["atoms"][$a][CHARGE]==0) { // O,S
				for ($b=count($molecule["atoms"][$a][NEIGHBOURS])-1;$b>=0;$b--) { // Nachbarn von Atom $a durchgehen
					$neighbour_b=$molecule["atoms"][$a][NEIGHBOURS][$b];
					if (
						in_array($molecule["atoms"][$neighbour_b][ATOMIC_SYMBOL],array("C","N","Si","P","S")) // COOH, SO3H, PO3H2, HNO3, HX(O)n => C,S,P, N, X mit entsprechenden O's, die impl oder expl H's haben
						|| 
						in_array($molecule["atoms"][$neighbour_b][ATOMIC_SYMBOL],$halogens)
					) {
						/* remove, ar is not set yet, importance: very low
						// phenol
						if ($molecule["atoms"][$neighbour_b][ATOMIC_SYMBOL]=="C" && $molecule["atoms"][$neighbour_b]["ar"]) { // phenol, thiophenol, etc.
							echo "XXX";
							$disconnect=true;
							break;
						}
						*/
						
						// find another $group16el with double bond
						for ($c=count($molecule["atoms"][$neighbour_b][NEIGHBOURS])-1;$c>=0;$c--) { // Nachbarn von Atom $neighbour_b durchgehen
							$neighbour_c=$molecule["atoms"][$neighbour_b][NEIGHBOURS][$c];
							if  (
								$neighbour_c!=$a
								&&
								in_array($molecule["atoms"][$neighbour_c][ATOMIC_SYMBOL],$group16el) // O,S
								&&
								$molecule["bondsFromNeighbours"][$neighbour_b][$neighbour_c][ORIG_BOND_ORDER]==2
							) {
								$disconnect=true;
								break 2;
							}
						}
					}
				}
				
			}
			else {
				$disconnect=true;
			}
			
			if ($disconnect) {
				// make El- and H+, mark as implicit/explicit (implicit H+ may be omitted)
				
				// reduce charge
				$molecule["atoms"][$a][CHARGE]--;
				
				if ($explicitHneighbour!==FALSE) {
					$molecule["atoms"][$explicitHneighbour][CHARGE]++;
					$molecule["bondsFromNeighbours"][$a][$explicitHneighbour][BOND_ORDER]=0;
					removeNeighboursFromAtoms($molecule,$a,$explicitHneighbour);
				}
				else {
					// reduce number
					$molecule["atoms"][$a][IMPLICIT_H]--; // o_h remains at original
					
					// create implicit H+ (just a number in the molecule data structure
					$molecule["iProt"]++;
				}
			}
		}
		
		if (!isMetal($molecule["atoms"][$a][ATOMIC_SYMBOL])) {
			continue;
		}
		
		// Nachbarn markieren für ggf Allyl,Enolat,...-Erkennung
		for ($b=count($molecule["atoms"][$a][NEIGHBOURS])-1;$b>=0;$b--) {
			$atom_no=$molecule["atoms"][$a][NEIGHBOURS][$b];
			//~ $molecule["atoms"][$atom_no]["hasMetalNeighbour"]=true;
			//~ $molecule["atoms"][$atom_no]["metalNeighbours"][]=$a;
		}
		
		// Was machen wir mit Mg(0)*Butadien vs Mg(II)CH2-CH=CHCH2- ???
		
		if ($molecule["atoms"][$a][BONDS]>=2) {
			$metallacyclopropanes=array();
			$metallacyclopropaneStats=array();
			// metallacyclopropan => olefin + M
			// 2 Bindungfolgen durchgehen, Ringbindung suchen
			for ($b=count($molecule["atoms"][$a][NEIGHBOURS])-1;$b>=0;$b--) { // von oben nach unten (wg Löschungen)
				$neighbour_b=$molecule["atoms"][$a][NEIGHBOURS][$b]; // 1. Nachbar
				if ($molecule["bondsFromNeighbours"][$a][$neighbour_b][BOND_ORDER]!=1 || !in_array($molecule["atoms"][$neighbour_b][ATOMIC_SYMBOL],$sideOnEl)) {
					continue;
				}
				for ($c=$b-1;$c>=0;$c--) { // von oben nach unten (wg Löschungen)
					$neighbour_c=$molecule["atoms"][$a][NEIGHBOURS][$c]; // 2. Nachbar
					if ($molecule["bondsFromNeighbours"][$a][$neighbour_c][BOND_ORDER]!=1 || !in_array($molecule["atoms"][$neighbour_c][ATOMIC_SYMBOL],$sideOnEl)) {
						continue;
					}
					// gibt es eine Bindung zwischen Nachbar b und c?
					if ($molecule["bondsFromNeighbours"][$neighbour_b][$neighbour_c][BOND_ORDER]>0) {
						$metallacyclopropanes[]=array($neighbour_b,$neighbour_c);
						$metallacyclopropaneStats[$neighbour_b]++;
						$metallacyclopropaneStats[$neighbour_c]++;
					}
				}
			}
			while (count($metallacyclopropanes)) {
				asort($metallacyclopropaneStats,SORT_NUMERIC);
				foreach ($metallacyclopropaneStats as $neighbour => $freq) {
					if ($freq<1) {
						continue;
					}
					// take the member with the fewest participations and find a metallacyclopropane with it
					for ($d=count($metallacyclopropanes)-1;$d>=0;$d--) {
						$metallacyclopropane=$metallacyclopropanes[$d];
						if (in_array($neighbour,$metallacyclopropane)) {
							$neighbour_b=$metallacyclopropane[0];
							$neighbour_c=$metallacyclopropane[1];
							if ($molecule["bondsFromNeighbours"][$a][$neighbour_b][BOND_ORDER]>0 && $molecule["bondsFromNeighbours"][$a][$neighbour_c][BOND_ORDER]>0) { // not yet handled, do it now
								// bindungen zu a weg, bindungsordnung bc + 1
								$molecule["bondsFromNeighbours"][$neighbour_b][$neighbour_c][BOND_ORDER]++; // orig order zum Darstellen lassen!
								$molecule["bondsFromNeighbours"][$a][$neighbour_b][BOND_ORDER]=0; // werden später gelöscht
								$molecule["bondsFromNeighbours"][$a][$neighbour_c][BOND_ORDER]=0;
								// neighbours löschen
								removeNeighboursFromAtoms($molecule,$a,$neighbour_b);
								removeNeighboursFromAtoms($molecule,$a,$neighbour_c);
							}
							// update stats
							$metallacyclopropaneStats[$neighbour_b]--;
							$metallacyclopropaneStats[$neighbour_c]--;
							array_splice($metallacyclopropanes,$d,1);
						}
					}
					break; // get only 1st element
				}
			}
		}
		// Metallacyclopropane sind weg
		//~ print_r($molecule);die();
		
		// ionische und dative Bindungen auflösen
		if ($molecule["atoms"][$a][BONDS]>=1) {
			// M-X => M[+] + X[-]
			for ($b=count($molecule["atoms"][$a][NEIGHBOURS])-1;$b>=0;$b--) { // von oben nach unten (wg Löschungen)
				$neighbour_b=$molecule["atoms"][$a][NEIGHBOURS][$b];
				$sym=$molecule["atoms"][$neighbour_b][ATOMIC_SYMBOL];
				
				// nur einfachbindungen auflösen								ist es das richtige Element?
				// C=[M] Carbenkomplexe sollen C: + [M] werden
				//~ if ($molecule["bondsFromNeighbours"][$a][$neighbour_b][BOND_ORDER]!=1 || !in_array($sym,$ionicEl)) {
				if (!in_array($sym,$ionicEl)) {
					continue;
				}
				$bond_order=$molecule["bondsFromNeighbours"][$a][$neighbour_b][BOND_ORDER];
				$prevent_metal_charge=false;
				
				// fix CO
				if (
					$sym=="C" 
					&& ($bond_order==1 || $bond_order==2) // M-C
					&& $molecule["atoms"][$neighbour_b][RADICAL]==0 
					&& $molecule["atoms"][$neighbour_b][ORIG_IMPLICIT_H]==0 // Carbene complexes defined by valency
					&& $molecule["atoms"][$neighbour_b][IMPLICIT_H]==0 // Carbene complexes defined by valency
					&& count($molecule["atoms"][$neighbour_b][NEIGHBOURS])<4
				) { // Carben, single or double
					// check for double or triple bound O as single neighbour
					if (count($molecule["atoms"][$neighbour_b][NEIGHBOURS])==2) for ($c=0;$c<2;$c++) {
						$neighbour_c=$molecule["atoms"][$neighbour_b][NEIGHBOURS][$c];
						if ($neighbour_c==$a) { // bond to metal
							continue;
						}
						
						if (
							$molecule["atoms"][$neighbour_c][ATOMIC_SYMBOL]=="O" 
							&& $molecule["bondsFromNeighbours"][$neighbour_b][$neighbour_c][BOND_ORDER]==3
						) { // CO
							// make CO-Bond double with no charges
							if ($molecule["atoms"][$neighbour_c][CHARGE]==0) { // non-charged O
								$prevent_metal_charge=true;
							}
							else {
								$molecule["atoms"][$neighbour_c][CHARGE]=0;
							}
							$molecule["atoms"][$neighbour_b][CHARGE]=0;
							$molecule["bondsFromNeighbours"][$neighbour_b][$neighbour_c][BOND_ORDER]=2;
						}
					}
					
					// bullshit single bonds give increase of 1, M=C bonds => no change
					if ($bond_order==1 && !$prevent_metal_charge) {
						$molecule["atoms"][$a][CHARGE]++; // M
					}
					
					// make carbon to carbene
					$molecule["atoms"][$neighbour_b][RADICAL]=1;
				}
				elseif ($bond_order>=1 && ($molecule["atoms"][$neighbour_b][BONDS]<=$valencies[$sym] || $molecule["atoms"][$neighbour_b][CHARGE]!=0)) { // sonst falsche dative Bindung
					// make X(n-) and M(n+)
					$molecule["atoms"][$a][CHARGE]+=$bond_order;
					$molecule["atoms"][$neighbour_b][CHARGE]-=$bond_order;
				}
				
				$molecule["bondsFromNeighbours"][$a][$neighbour_b][BOND_ORDER]=0;
				removeNeighboursFromAtoms($molecule,$a,$neighbour_b);
			}
		}
	}
	//~ removeZeroBonds($molecule);
	//~ renewBondsFromNeighbours($molecule);
}

?>