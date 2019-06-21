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

function getChiral(& $a1,& $a2,& $a3,& $a4,& $a5=array()) {
	// pseudo-3D stereo Bindungen müssen VORHER eine z-Komponente erhalten, die der Stereobindung entspricht
	
	// chirales C o.ä.
	// im SMILES X[2@](Y)(Z)A

	// chirales CH oder P o.ä.
	// im SMILES X[1@](Y)Z
/*
		n/iH/5
	      /
	2-1 < 4
	     \
		3
*/
	$c1=getCoordsFromAtom($a1);
	$c2=getCoordsFromAtom($a2);
	$c3=getCoordsFromAtom($a3);
	$c4=getCoordsFromAtom($a4);
	
	$d12=array_subtract($c1,$c2);
	$d13=array_subtract($c1,$c3);
	$d14=array_subtract($c1,$c4);
	
	$retval=getTripleProd($d12,$d13,$d14);
	//~ echo "D".$retval."D";
	
	if ($retval==0 || count($a5)==0 || $a1==$a5) {
		return $retval;
	}
	
	// check if atom 5 is opposite direction to 134
	$c5=getCoordsFromAtom($a5);
	$d15=array_subtract($c1,$c5);
	$d1_234=array_add($d12,$d13,$d14);
	
	$angle=getVecAngle($d15,$d1_234);
	//~ echo "Z".$angle."Z";
	if (abs($angle)>pi()/2) {
		return $retval;
	}
}

function getChiral2(& $atoms,$atom_no,$highest) {
	return getChiral(
		$atoms[$atom_no],
		$atoms[ $highest[0] ],
		$atoms[ $highest[1] ],
		$atoms[ $highest[2] ],
		$atoms[ $highest[3] ] // evtl
	);
}

function getFake3DAtoms(& $molecule,$atom_no,$highest) {
	// try stereo bonds in 2d
	
	// ist atom1 außen, Effekt umkehren
	
	// für stereo=1 (Up), $atom2["z"] erhöhen oder alle anderen erniedrigen
	// für stereo=6 (Down), $atom2["z"] erniedrigen oder alle anderen erhöhen
	// die zweite option trifft zu, wenn $a>1 ist (Konstell)
	
	// für stereo=4 (Either), Operation abbrechen
	// make partial copy of molecule
	$atoms=$molecule["atoms"];
	$tempMolAtoms=array();
	$tempMolAtoms[$atom_no]=$atoms[$atom_no];
	for ($a=0;$a<count($highest);$a++) {
		$tempMolAtoms[ $highest[$a] ]=$atoms[ $highest[$a] ];
	}
	
	for ($a=0;$a<count($highest);$a++) {
		$neighboursAtom=$highest[$a];
		$stereo=$molecule["bondsFromNeighbours"][$atom_no][$neighboursAtom][STEREO];
		if ($stereo==4) { // undefined stereo
			return array();
		}
		elseif ($stereo==1) {
			$direction=1;
		}
		elseif ($stereo==6) {
			$direction=-1;
		}
		else {
			continue;
		}
		$orientation=($molecule["bondsFromNeighbours"][$atom_no][$neighboursAtom][ATOM2]==$atom_no?-1:1);
		$tempMolAtoms[ $highest[$a] ]["z"]+=$direction*$orientation;
		for ($b=0;$b<count($highest);$b++) {
			if ($a==$b) {
				continue;
			}
			$tempMolAtoms[ $highest[$b] ]["z"]-=$direction*$orientation/(count($highest)); // -1
		}
	}
	return $tempMolAtoms;
}

function SMchiral(& $molecule,$atom_no) { // ,$from_atom_no
	if (!empty($molecule["atoms"][$atom_no]["SMchirStereo"])) { // already detected
		return array();
	}
	// prüfen, ob
	
	// 4 unterschiedliche Reste (from_atom und 3 andere)
	// 3 unterschiedliche Reste und (iHyd oder P,S mit lone pair) (from_atom und 2 andere)
	
	$atoms=$molecule["atoms"];
	$neighbours=$atoms[$atom_no][NEIGHBOURS];
	$neighboursCount=count($neighbours);
	if ($neighboursCount<3) { // quick exit
		return array();
	}
	
	// wieviele unterschiedliche Reste?
	$diffNeighboursCount=$neighboursCount;
	$iHyd=$atoms[$atom_no][IMPLICIT_H];
	
	// Annahme: alle Reste sind unterschiedlich, gleiche suchen
	if ($iHyd>0) { // implizite Hs können nur 1 weiteren Nachbarn beisteuern
		$diffNeighboursCount++;
	}
	
	$highestNeighbours=array();
	for ($a=0;$a<$neighboursCount;$a++) { // go through neighbours
		$neighbourAtomNo=$neighbours[$a];
		$atom1=$atoms[$neighbourAtomNo];
		
		if ($iHyd>0 && isExplH($molecule,$neighbourAtomNo)) { // expl H, das impl sein könnte (2 Hs sind identisch, implizit oder explizit)
			$iHyd++;
			$diffNeighboursCount--;
			continue;
		}
		
		for ($c=0;$c<=count($highestNeighbours);$c++) { // push if no lower is found
			if (!isset($highestNeighbours[$c]) || SMisHigherThan($atom1,$atoms[$highestNeighbours[$c]])) {
				array_splice($highestNeighbours,$c,0,array($neighbourAtomNo));
				break;
			}
		}
		for ($b=$a+1;$b<$neighboursCount;$b++) { // compare to remaining neighbours
			// 1 Paar => -1
			if (SMisEqual($atom1,$atoms[ $neighbours[$b] ])) {
				$diffNeighboursCount--;
				continue 2;
			}
		}
	}
	
	//~ echo "B".$atom_no."C".$diffNeighboursCount."\n";
	if (
		$diffNeighboursCount==4 
		|| (
			$diffNeighboursCount==3 && 
			$neighboursCount==3 && 
			in_array($atoms[$atom_no][ATOMIC_SYMBOL],array("P","S"))
		) 
	) { // chiral
		//~ print_r($highestNeighbours);
		// mark expl Hs as chiral
		// 2. höchsten und höchsten zurückgeben
		return $highestNeighbours;
	}
	return array();
}

function markStereoHs(& $molecule,$atom_no) {
	for ($a=0;$a<count($molecule["atoms"][$atom_no][NEIGHBOURS]);$a++) {
		$neighbour_atom=$molecule["atoms"][$atom_no][NEIGHBOURS][$a];
		if ($molecule["atoms"][$neighbour_atom]["SMimplH"]) {
			$molecule["atoms"][$neighbour_atom]["stereoH"]=true;
		}
	}
}

function markChiralAtoms(& $molecule) {
	// 1. alle Atome durchgehen und Chiralität suchen, markieren mit Deskriptor
	$atoms=$molecule["atoms"];
	for ($atom_no=0;$atom_no<count($molecule["atoms"]);$atom_no++) {
		$highest=SMchiral($molecule,$atom_no);
		//~ print_r($highest);
		if (count($highest)) {
			//~ print_r($highest);
			// try 3D coords 1st
			$tripleProd=getChiral2($atoms,$atom_no,$highest);
			if ($tripleProd==0) {
				$tempMolAtoms=getFake3DAtoms($molecule,$atom_no,$highest);
				//~ print_r($tempMolAtoms);
				if (count($tempMolAtoms)) {
					$tripleProd=getChiral2($tempMolAtoms,$atom_no,$highest);
				}
			}
			
			//~ echo "Y".$tripleProd."Y";
			if ($tripleProd<0) {
				$molecule["atoms"][$atom_no]["SMchirStereo"]=4;
				markStereoHs($molecule,$atom_no);
			}
			elseif ($tripleProd>0) {
				$molecule["atoms"][$atom_no]["SMchirStereo"]=8;
				markStereoHs($molecule,$atom_no);
			}
		}
	}
	// 2. Ranks anpassen
	addStereoToRank($molecule);
}


?>