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

function SMstereoDoublePossible(& $molecule,$atom_no) {
	if (!empty($molecule["atoms"][$atom_no]["SMdblStereo"])) { // already detected
		return array();
	}
	if (count($molecule["atoms"][$atom_no][NEIGHBOURS])<2) { // no neighbours
		return array();
	}
	$dblFollows=false;
	$iHyd=$molecule["atoms"][$atom_no][IMPLICIT_H];
	unset($lowestNeighbour);
	$diffNeighbours=false;
	$nonHNeighbours=false;
	
	for ($a=0;$a<count($molecule["atoms"][$atom_no][NEIGHBOURS]);$a++) {
		$neighbourAtomNo=$molecule["atoms"][$atom_no][NEIGHBOURS][$a];
		$order=SMgetOrder($molecule,$neighbourAtomNo,$atom_no);
		if (!$molecule["atoms"][$neighbourAtomNo]["SMdone"] && $order==2) {
			// $molecule["bondsFromNeighbours"][$neighbourAtomNo][$atom_no][BOND_ORDER]
			// ja, Doppelbindung
			if ($dblFollows) { // Kumulen oder H2SO4 oder ...
				return array();
			}
			$dblFollows=true; // 1 Doppelbindung gefunden
			$dblAtom=$neighbourAtomNo;
		}
		elseif ($order==1) { // ggf auch expl H, bei =N[H]
			if ($molecule["bondsFromNeighbours"][$neighbourAtomNo][$atom_no][STEREO]==4) {
				return array();
			}
			if (isExplH($molecule,$neighbourAtomNo)) {
				// nicht =CH[H] und auch nicht C[H][H] und auch nicht O[H]
				if ($iHyd==0 && count($molecule["atoms"][$atom_no][NEIGHBOURS])==2 && $molecule["atoms"][$atom_no][BONDS]==3 ) {
					// =N[H]
					$diffNeighbours=true;
					// mark H atom
					$molecule["atoms"][$neighbourAtomNo]["SMimplH"]=false;
					$molecule["atoms"][$neighbourAtomNo]["SMexplH"]=true;
					//~ echo $neighbourAtomNo."X".$atom_no."<br>";
					//~ break; // Nein, wir brauchen dblAtom
				}
				else {
					continue;
				}
				$iHyd++;
			}
			// stereo=4 führt zu Abbruch, stereochemie undefiniert
			if (!isset($lowestNeighbour) || SMisHigherThan($molecule["atoms"][$lowestNeighbour],$molecule["atoms"][$neighbourAtomNo])) {
				$lowestNeighbour=$neighbourAtomNo;
				if (count($molecule["atoms"][$atom_no][NEIGHBOURS])==2) { // nur 1 Neighbour, zB C=NOH
					$diffNeighbours=true;
					// break; // Nein, wir brauchen dblAtom
				}
			}
			if (!$diffNeighbours) {
				for ($b=$a+1;$b<count($molecule["atoms"][$atom_no][NEIGHBOURS]);$b++) {
					$neighbourAtomNo2=$molecule["atoms"][$atom_no][NEIGHBOURS][$b];
					$order2=SMgetOrder($molecule,$neighbourAtomNo2,$atom_no);
					if ($order2==1 && !SMisEqual($molecule["atoms"][$neighbourAtomNo],$molecule["atoms"][$neighbourAtomNo2])) { // 2 unterschiedliche Nachbarn
						$diffNeighbours=true;
						//~ break 2; // Nein, wir brauchen lowest neighbour
					}
				}
			}
		}
	}
	
	if (!$dblFollows) {
		return array();
	}
	
	if ($iHyd>0 && $nonHNeighbours) {
		$diffNeighbours=true;
	}
	
	if ($diffNeighbours) {
		return array($dblAtom,$lowestNeighbour);
	}
	return array();
}

function invertDesc($descriptor) {
	if ($descriptor=="/") {
		return "\\";
	}
	if ($descriptor=="\\") {
		return "/";
	}
	return "";
}

function getDihedralAngle(& $a1,& $a2,& $a3,& $a4) {
/*
 1       4
   \     /
   2=3
*/
	$c1=getCoordsFromAtom($a1);
	$c2=getCoordsFromAtom($a2);
	$c3=getCoordsFromAtom($a3);
	$c4=getCoordsFromAtom($a4);
	$d12=array_subtract($c1,$c2);
	$d23=array_subtract($c2,$c3);
	$d34=array_subtract($c3,$c4);
	
	$cr123=getCrossProd($d12,$d23);
	$cr234=getCrossProd($d23,$d34);
	$s1=array_mult($c1,getVecLen($c2));
	$sp1=getScalarProd($s1,$cr234);
	$sp2=getScalarProd($cr123,$cr234);
	return atan2($sp1,$sp2);
}

function markStereoDoubleBonds(& $molecule) {
	// 1. alle Atome durchgehen und Stereo-Doppelbindungen suchen, rel. Orientierung der jew highAtoms speichern
	for ($atom_no=0;$atom_no<count($molecule["atoms"]);$atom_no++) {
		// hat Atom eine stereo-Doppelbindung?
		// 1. kommt als nächstes Doppelbindung?
		// 2. hat Atom eine ungerade Zahl WEITERER Substituenten, zwei unterschiedliche Substituenten oder nur einen!=H ?
		list($dblAtom,$highestNeighbour)=SMstereoDoublePossible($molecule,$atom_no);
		if (isset($dblAtom) && isset($highestNeighbour)) {
			// 3. hat doppelgebundenes Atom zwei unterschiedliche einfach gebundene Substituenten?
			list($dblAtom2,$highestNeighbour2)=SMstereoDoublePossible($molecule,$dblAtom);
			if ($atom_no==$dblAtom2 && isset($highestNeighbour2)) {
				// 4. / hinzufügen und Diederwinkel berechnen
				$dihedralAng=getDihedralAngle(
					$molecule["atoms"][$highestNeighbour],
					$molecule["atoms"][$atom_no],
					$molecule["atoms"][$dblAtom],
					$molecule["atoms"][$highestNeighbour2]
				);
				//~ echo $from_atom_no."-".$atom_no."=".$dblAtom."-".$highestNeighbour2."X".$dihedralAng/pi();
				// 5. <=90 \ bei einfach gebundenem Substituenten höherer Priorität als SMpre setzen
				if (abs($dihedralAng)<pi()/2) { // Z
					$dblStereo=1;
				}
				else {
					$dblStereo=2; // E
				}
				$molecule["atoms"][$atom_no]["SMdblStereo"]=$dblStereo;
				$molecule["atoms"][$atom_no]["SMdblPartner"]=$dblAtom;
				$molecule["atoms"][$atom_no]["SMdblHighAtom"]=$highestNeighbour;
				$molecule["atoms"][$dblAtom]["SMdblStereo"]=$dblStereo;
				$molecule["atoms"][$dblAtom]["SMdblPartner"]=$atom_no;
				$molecule["atoms"][$dblAtom]["SMdblHighAtom"]=$highestNeighbour2;
			}
			elseif (isset($highestNeighbour)) { // Allen/Cumulen (C=C=C)
			
			}
		}
	}
	// 2. Ranks anpassen
	addStereoToRank($molecule);
}


?>