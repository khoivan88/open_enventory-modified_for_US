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

function SMisHigherThan(& $a1,& $a2,$orEqual=false) { // ist a1 "höher" als a2?
	if (!count($a2)) {
		return true;
	}
	$rc1=count($a1[RANKS])-2;
	$rc2=count($a2[RANKS])-2;
	if ($rc1!=$rc2) { // should not happen
		return ($rc1>$rc2);
	}
	do {
		if ($a1[RANKS][$rc1]>$a2[RANKS][$rc1]) {
			return true; // 1
		}
		elseif ($a1[RANKS][$rc1]<$a2[RANKS][$rc1]) {
			return false; // -1
		}
		$rc1--;
	} while ($rc1>=0);
	return $orEqual; // -1 lower or equal
	
	/*
	if ($a1[RANKS][$rc1]>$a2[RANKS][$rc2]) { // compare prev_ranks
		return true; // 1
	}
	elseif ($a1[RANKS][$rc1]<$a2[RANKS][$rc2]) {
		return false; // -1
	}
	elseif ($a1[RANKS][$rc1+1]>$a2[RANKS][$rc1+1]) { // equal prev_ranks, compare ranks
		return true; // 1
	}
	elseif ($a1[RANKS][$rc1+1]<$a2[RANKS][$rc1+1]) { // equal prev_ranks, compare ranks
		return false; // 1
	}
	return $orEqual; // -1 lower or equal
	*/
}

function SMisEqual(& $a1,& $a2) {
	return ($a1[RANKS]==$a2[RANKS]);
}

function getChargeInvar($charge) {
	// für Atom-Ranking
	$charge+=0;
	if ($charge==0) {
		return "00";
	}
	if ($charge>0) {
		return "1".$charge;
	}
	return "2".(-$charge);
}

function iterateRanks(& $molecule) {
	// schreibt rank auf prev_rank und addiert die prev_ranks der Nachbarn zu neuem rank
	// gibt Anzahl verschiedener ranks zurück (wenn diese ggü vorher abnimmt, ist getAtomRanks (s.u.) am Ende, deshalb sind auch die prev_ranks die "Wahren"
	$ranks=array();
	foreach ($molecule["atoms"] as $a => $atom) {
		if ($atom["SMimplH"]) { // ignore
			continue;
		}
		$newRank=$atom[RANKS][ $molecule["rankLevel"]-1 ];
		$neighbours=& $atom[NEIGHBOURS];
		for ($b=0;$b<count($neighbours);$b++) {
			$c=$neighbours[$b];
			if (!$molecule["atoms"][$c]["SMimplH"]) {
				$newRank+=$molecule["atoms"][$c][RANKS][ $molecule["rankLevel"]-1 ];
			}
		}
		// $molecule["atoms"][$a]["rank"]=$newRank;
		$molecule["atoms"][$a][RANKS][]=$newRank;
		$ranks[]=$newRank;
	}
	$molecule["rankLevel"]++;
	return count(array_unique($ranks));
}

function getMassInvar($mass) {
	return str_pad($mass,3,"0",STR_PAD_LEFT);
}

function getAtomInvar(& $atom) {
	// bbnnaacchmmm
	
	// bb: non-H neighbours
	// nn: non-H bonds*10 (kann x.5 sein)
	// aa: anum
	// cc: Ladung
	// h: hbonds
	// mmm: mass, 000 if natural
	
	return intval(
		$atom[NON_H_NEIGHBOURS]. // 1
		str_pad($atom[NON_H_BONDS]*10,2,"0",STR_PAD_LEFT). // 2
		str_pad($atom[ATOMIC_NUMBER],2,"0",STR_PAD_LEFT). // normally 2
		getChargeInvar($atom[CHARGE]). // 2
		$atom[H_NEIGHBOURS]. // 1
		getMassInvar($atom[MASS]) // 3
	);
}

function performRanking(& $molecule) {
	do {
		$oldRanks=$newRanks;
		$newRanks=iterateRanks($molecule);
		// echo $oldRanks."X".$newRanks."\n";
	} while ($oldRanks<$newRanks); // prev_ranks sind die ECHTEN
	return $newRanks;
}

function addStereoToRank(& $molecule) {
	$atoms=$molecule["atoms"];
	/*
	$stereo_tick=array();
	for ($a=0;$a<count($atoms);$a++) {
		$atom=$atoms[$a];
		
		if (count($atom[RINGS]) && count($atom[NEIGHBOURS])>2) { // maybe pseudo-chiral, but handle only once
			$ringAtoms=array();
			$nonRingAtoms=array();
			// get neighbours which are members of THE SAME ring and which are not
			for ($d=0;$d<count($atom[NEIGHBOURS]);$d++) {
				$neighbourAtomNo=$atom[NEIGHBOURS][$d];
				if (array_intersect($atom[RINGS],$atoms[$neighbourAtomNo][RINGS])) {
					$ringAtoms[]=$neighbourAtomNo;
				}
				else {
					$nonRingAtoms[]=$neighbourAtomNo;
				}
			}
			sort($ringAtoms);
			
			// are the ring neighbours identical, if no, out
			if (
				count($ringAtoms)==2 && 
				SMisEqual($atoms[ $ringAtoms[0] ],$atoms[ $ringAtoms[1] ]) && 
				!SMisEqual($atoms[ $nonRingAtoms[0] ],$atoms[ $nonRingAtoms[1] ])
			) {
				$highestNeighbours=array($nonRingAtoms[0]);
				// add $nonRingAtoms by decreasing rank
				for ($b=1;$b<count($nonRingAtoms);$b++) {
					$neighbourAtomNo=$nonRingAtoms[$b];
					for ($c=0;$c<=count($highestNeighbours);$c++) { // push if no lower is found
						if (!isset($highestNeighbours[$c]) || SMisHigherThan($molecule["atoms"][$neighbourAtomNo][RINGS],$atoms[$highestNeighbours[$c]])) {
							array_splice($highestNeighbours,$c,0,array($neighbourAtomNo));
							break;
						}
					}
				}
				$highestNeighbours=array_merge($ringAtoms,$highestNeighbours);
				
				// try 3D coords 1st
				$tripleProd=getChiral2($atoms,$a,$highestNeighbours);
				if ($tripleProd==0) {
					$tempMolAtoms=getFake3DAtoms($molecule,$a,$highestNeighbours);
					//~ print_r($tempMolAtoms);
					if (count($tempMolAtoms)) {
						$tripleProd=getChiral2($tempMolAtoms,$a,$highestNeighbours);
					}
				}
			
				if ($tripleProd>0) {
					$stereo_tick[ $ringAtoms[0] ]=16;
				}
				elseif ($tripleProd<0) {
					$stereo_tick[ $ringAtoms[0] ]=32;
				}
			}
		}
	}*/
	
	for ($a=0;$a<count($atoms);$a++) {
		$atom=$atoms[$a];
		$rankCount=count($atom[RANKS]);
		if ($rankCount==0 || $atom["SMimplH"]) {
			continue;
		}
		
		$lastRank=$atom[RANKS][ $rankCount-1 ];
		$lastRank|=($atom["SMdblStereo"]+intval($atom["SMchirStereo"])); // dbl: 1,2, chiral: 4,8,pseudo: 16
		$molecule["atoms"][$a][RANKS][]=$lastRank;
		//~ $molecule["atoms"][$a][RANKS][$rankCount-1]=$lastRank;
	}
	$molecule["rankLevel"]++;
}

function markExHs(& $molecule) {
	// expl Hs entfernen
	$molecule["nonHatoms"]=0;
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		if (isExplH($molecule,$a)) {
			continue;
		}
		//~ $molecule["maxatom"]=$a;
		$molecule["atoms"][$a][H_NEIGHBOURS]=$molecule["atoms"][$a][IMPLICIT_H];
		//~ $molecule["atoms"][$a][NON_H_BONDS]=$molecule["atoms"][$a][BONDS];
		$molecule["atoms"][$a][NON_H_NEIGHBOURS]=count($molecule["atoms"][$a][NEIGHBOURS]);
		$bonds=0;
		for ($b=0;$b<count($molecule["atoms"][$a][NEIGHBOURS]);$b++) { // otherwise zero-bonds are not correctly handled
			$neighbour_atom=$molecule["atoms"][$a][NEIGHBOURS][$b];
			$bonds+=$molecule["bondsFromNeighbours"][$a][$neighbour_atom][BOND_ORDER];
		}
		$molecule["atoms"][$a][BONDS]=$bonds;
		$molecule["atoms"][$a][NON_H_BONDS]=$bonds;
		$molecule["nonHatoms"]++;
	}
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		if (!isExplH($molecule,$a)) {
			continue;
		}
		for ($b=0;$b<count($molecule["atoms"][$a][NEIGHBOURS]);$b++) {
			$c=$molecule["atoms"][$a][NEIGHBOURS][$b];
			$molecule["atoms"][$c][H_NEIGHBOURS]++;
			$molecule["atoms"][$c][NON_H_BONDS]--;
			$molecule["atoms"][$c][NON_H_NEIGHBOURS]--;
		}
	}
}

function getAtomRanks(& $molecule,$paramHash=array() ) {
// ranking wird in kopie gemacht, bei der weil expl Hs restlos entfernt werden

// after JChemInfComputSci, 1989, 29(2), 97ff.
// (1) number of connections
// (2) number of non-hydrogen bonds
// (3) atomic number
// (4) sign of charge
// (5) absolute charge
// (6) number of attached-hydrogens
// dann Morgan algo
	if (count($molecule["atoms"])==0) {
		return;
	}
	
	$Honly=true;
	$invars=array();
	
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		unset($molecule["atoms"][$a][RANKS]); // ggf alte ranks entfernen, komplett neu
		//~ unset($molecule["atoms"][$a]["SMdone"]);
		unset($molecule["atoms"][$a]["SMchirStereo"]);
		unset($molecule["atoms"][$a]["SMdblStereo"]);
		unset($molecule["atoms"][$a]["SMdblPartner"]);
		unset($molecule["atoms"][$a]["SMdblHighAtom"]);
		if (!isExplH($molecule,$a)) {
			$new_invar=getAtomInvar($molecule["atoms"][$a]); // new
			$molecule["atoms"][$a][INVARIANT]=$new_invar;
			$invars[]=$new_invar;
			$Honly=false;
		}
	}
	
	$molecule["rankLevel"]=1;
	$invars=array_unique($invars);
	sort($invars,SORT_NUMERIC);
	// durch index+1 ersetzen
	for  ($a=0;$a<count($molecule["atoms"]);$a++) {
		$molecule["atoms"][$a][RANKS]=array( (1+array_search($molecule["atoms"][$a][INVARIANT],$invars))*16 );
		//~ $molecule["atoms"][$a][RANKS]=array( (1+array_search($molecule["atoms"][$a][INVARIANT],$invars))*64 );
	}
	
	if ($Honly) { // dont use molcopy as it is empty
		for ($a=0;$a<count($molecule["atoms"]);$a++) { // H or H2
			// $molecule["atoms"][$a]["prev_rank"]=1;
			$molecule["atoms"][$a][RANKS]=array(1);
			
			// set all atoms to explH (otherwise only Hs needed to determine chirality)
			$molecule["atoms"][$a]["SMimplH"]=false;
			$molecule["atoms"][$a]["SMexplH"]=true;
		}
		$molecule["SMexplH"]=true;
		//~ $molecule["maxatom"]=0;
	}
	else {
		if (count($molecule["atoms"])>1) {
			// achirale ranks
			$newRanks=performRanking($molecule);
			do {
				// stereoranks
				
				// Doppelbindungen E/Z
				markStereoDoubleBonds($molecule);
				
				// Chiralität
				markChiralAtoms($molecule);
				
				// chirale ranks
				$oldRanks=$newRanks;
				$newRanks=performRanking($molecule);
				//~ print_r($molecule["atoms"][0][RANKS]);
				
			} while ($oldRanks<$newRanks && $newRanks<$molecule["nonHatoms"]); // prev_ranks sind die ECHTEN
		}
		
		//~ for  ($a=0;$a<count($molecule["atoms"]);$a++) {
			//~ if (!isset($molecule["maxatom"]) || SMisHigherThan($molecule["atoms"][$a], $molecule["atoms"][ $molecule["maxatom"] ])) {
				//~ $molecule["maxatom"]=$a;
			//~ }
		//~ }
	}
	//~ $molecule["maxatoms"][]=$molecule["maxatom"];
}


?>