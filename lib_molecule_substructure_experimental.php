<?php
/*
Copyright 2006-2009 Felix Rudolphi and Lukas Goossen
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

function matchAtoms($needleAtom,$haystackAtom) {
	// prüft, ob needleAtom (ggf. Wildcard etc) zu haystackAtom paßt
	if ($needleAtom=="*") {
		return true;
	}
	if ($needleAtom==$haystackAtom) {
		return true;
	}
	if ($needleAtom=="X" && strpos(",".$haystackAtom.",",",F,Cl,Br,I,")!==FALSE) {
		return true;
	}
	if ($needleAtom=="M" && isMetal($haystackAtom)) {
		return true;
	}
	if ($needleAtom=="Ln" && strpos(",".$haystackAtom.",",",La,Ce,Pr,Nd,Pm,Sm,Eu,Gd,Tb,Dy,Ho,Er,Tm,Yb,Lu,")!==FALSE) {
		return true;
	}
	return false;
}

function matchBonds($needleBond,$haystackBond,& $paramHash) {
	if ($needleBond["o"]==$haystackBond["o"] || $needleBond["o_o"]==$haystackBond["o_o"]) { // gefunden
		return true;
	}
	if ($paramHash["fp"]) { // needle ist funk gruppe, haystack ist molekül
		if ($haystackBond["o"]==1.5 && ($needleBond["o"]==1 || $needleBond["o"]==2) ) { // accept aromatic bond in candidate as single or double
			return true;
		}
	}
	return false;
}

function matchPathsRecursive(& $needle,& $haystackMolecule,$needlePath,$haystackPath,$checkpart,& $paramHash) {
	//~ if ($startTime && microtime(true)-$startTime>maxStructureTime) {
		//~ return true;
	//~ }
	// prüft rekursiv zwei Pfade in needle und haystack gegeneinander, mit Matrixprüfung
	// **Fehler mit langen Ketten, die in Ringe passen**
	//~ echo "P".count($needlePath)."<br>";
	// echo "N".join(",",$needlePath)."H".join(",",$haystackPath)."\n";
	if (count($needlePath)!=count($haystackPath)) {
		return false;
	}
	$lastNeedle=end($needlePath);
	$lastHaystack=end($haystackPath);
	$path_length=count($needlePath);
	if (!matchAtoms($needle["atoms"][ $lastNeedle ]["s"],$haystackMolecule["atoms"][ $lastHaystack ]["s"])
	 || count($needle["atoms"][$lastNeedle]["n"])>count($haystackMolecule["atoms"][$lastHaystack]["n"])) { // Atome passen nicht oder Haystack hat zuwenige Nachbarn, um für needle zu passen
		// echo "(".$lastNeedle.")(".$lastHaystack."): Atome passen nicht\n";		
		return false;
	}
	if ($path_length>1) {
		$prevNeedle=prev($needlePath);
		$prevHaystack=prev($haystackPath);
		$oldNeighbours=1; // Zahl der bereits besuchten Nachbarn
		
		//~ if ($needle["bondsFromNeighbours"][ $lastNeedle ][ $prevNeedle ]["o"]!=$haystackMolecule["bondsFromNeighbours"][ $lastHaystack ][ $prevHaystack ]["o"]) {
		if (!matchBonds($needle["bondsFromNeighbours"][ $lastNeedle ][ $prevNeedle ],$haystackMolecule["bondsFromNeighbours"][ $lastHaystack ][ $prevHaystack ],$paramHash)) {
			// echo "(".$lastNeedle."/".$prevNeedle.")(".$lastHaystack."/".$prevHaystack."): Letzte Bindungen passen nicht\n";
			return false; // Letzte Bindungen passen nicht
		}
	}
	else {
		$prevNeedle=-1;
		$prevHaystack=-1;
		$oldNeighbours=0; // Zahl der bereits besuchten Nachbarn
	}
	// noSubst
	if ($needle["atoms"][ $lastNeedle ]["noSubst"] && count($needle["atoms"][ $lastNeedle ]["n"])+$needle["atoms"][ $lastNeedle ]["h"]!=count($haystackMolecule["atoms"][ $lastHaystack ]["n"])) {
		return false;
	}
	// Ende erreicht
	if (count($needle["atoms"][ $lastNeedle ]["n"])==$oldNeighbours) { // nur das atom wo wir herkommen
		return true;
	}
	// Ringprüfung, wenn ja return true
	for ($a=count($haystackPath)-2;$a>=0;$a--) {
		if ($haystackPath[$a]==$lastHaystack) {
			$haystackRingPos=$a;
		}
	}
	for ($a=count($needlePath)-2;$a>=0;$a--) {
		if ($needlePath[$a]==$lastNeedle) {
			$needleRingPos=$a;
		}
	}
	if (!empty($haystackRingPos) && $haystackRingPos==$needleRingPos) {
		return true;
	}
	elseif ($haystackRingPos!=$needleRingPos) {
		return false;
	}
	
	// rekursiv alle jeweiligen Nachbarn miteinander vergleichen
	$ndlMatSub=0;
	for ($a=0;$a<count($needle["atoms"][ $lastNeedle ]["n"]);$a++) { // Needle neighbours
		// echo "A".$a." pn".$prevNeedle."\n";
		$thisNeedleAtom=$needle["atoms"][ $lastNeedle ]["n"][$a];
		if ($thisNeedleAtom!=$prevNeedle) { // ist es NICHT das atom, von dem wir kommen
   			$needlePath[$path_length]=$thisNeedleAtom; // ans Ende des Pfads setzen
   			$somethingFound=false;
			$hstMatSub=0;
			for ($b=0;$b<count($haystackMolecule["atoms"][ $lastHaystack ]["n"]);$b++) { // Haystack neighbours
					// echo "B".$b.": ".join(",",$haystackPath)."\n";
				$thisHaystackAtom=$haystackMolecule["atoms"][ $lastHaystack ]["n"][$b];
				if ($thisHaystackAtom!=$prevHaystack) { // ist es NICHT das atom, von dem wir kommen
					// echo "matching ".$thisNeedleAtom."x".$thisHaystackAtom."\n";
					$haystackPath[$path_length]=$thisHaystackAtom; // ans Ende des Pfads setzen
					if (matchPathsRecursive($needle,$haystackMolecule,$needlePath,$haystackPath,$checkpart,$paramHash)) {
						// indices sind die Nachbar-Nummern
						$matchmat[$a-$ndlMatSub][$b-$hstMatSub]=true; // Array(true,$thisNeedleAtom,$thisHaystackAtom);
						$somethingFound=true;
						// echo "cont\n";
						// continue 2;
					}
				}
				else { // spalte überspringen
					$hstMatSub=1;
				}
			}
			if (!$somethingFound) {
					// echo "Hier N".$a."\n";
				return false; // irgendein Zweig von needle konnte nicht gematcht werden
			}
		 }
		 else { // spalte überspringen
			$ndlMatSub=1;
		}
	}
	// echo "MC\n";
	// echo "N".join(",",$needlePath)."H".join(",",$haystackPath)."\n";
	if (!matrixCheck($matchmat,count($needle["atoms"][ $lastNeedle ]["n"])-$oldNeighbours,count($haystackMolecule["atoms"][ $lastHaystack ]["n"])-$oldNeighbours)) {
		return false;
	}
	
	// nächsten Teil anfangen
	$checkpart++;
	if ($checkpart>=count($needle["parts"])) {
		return true;
	}
	//~ echo "Part".$checkpart."<br>";
	//~ print_r($needlePath);
	//~ print_r($haystackPath);
	$needle_maxatom=$needle["parts"][$checkpart]["maxatom"];
	$needlePath[]=$needle_maxatom; // nächstes maxatom
	$haystackPathLen=count($haystackPath);
	for ($a=0;$a<count($haystackMolecule["atoms"]);$a++) { // $a may be higher than atom number in original molecule due to explicit hydrogens
		if (in_array($a,$haystackPath)) {
			continue;
		}
		if (count($needle["atoms"][ $needle_maxatom ]["n"])<=count($haystackMolecule["atoms"][$a]["n"]) && $needle["atoms"][ $needle_maxatom ]["b"]<=$haystackMolecule["atoms"][$a]["b"]) {
			$haystackPath[$haystackPathLen]=$a;
			if (matchPathsRecursive($needle,$haystackMolecule,$needlePath,$haystackPath,$checkpart,$paramHash)) {
				return true;
			}
		}
	}
	return false;
}

function matrixCheck($matrix,$dimNeedle,$dimHaystack) {
	// prüft anhand einer Matrix, ob Pfade wirklich ok sind
	// echo "Mat".$dimNeedle."x".$dimHaystack."\n";
	// print_r($matrix);
	if ($dimNeedle>$dimHaystack) {
		return false;
	}
	if ($dimNeedle==1) {
		/* if ($dimHaystack!=count($matrix[0])) {
			echo $dimHaystack."A".count($matrix[0])."<br>";
		}
		else {
			echo "B";
		}
		if (@in_array(true,$matrix[0])) {
			return true;
		} */
		for ($a=0;$a<$dimHaystack;$a++) {
			if ($matrix[0][$a]){ // hier // [0]
				// echo $matrix[0][$a][1]."->".$matrix[0][$a][2]."\n";
				return true;
			}
		}
	}
	for ($a=0;$a<$dimHaystack;$a++) {
		if ($matrix[0][$a]) { // hier // [0]
			for ($b=1;$b<$dimNeedle;$b++) {
				$d=0;
				for ($c=0;$c<$dimHaystack;$c++) {
					if ($a!=$c) {
						$newMatrix[$b-1][$d++]=$matrix[$b][$c];
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

function getSubstMatch($needle,$haystackMolecule,$paramHash=array()) {
	checkSettings($paramHash,"su");
	
	// gibt true zurück, wenn needle eine Substruktur von haystackMolecule ist, auch Multipart mit Matrixprüfung
	if (count($needle["atoms"])==0 || count($haystackMolecule["atoms"])==0) {
		return false;
	}
	
	if ($needle["smiles"]!="" && $needle["smiles"]==$haystackMolecule["smiles"]) { // identical
		return true;
	}
	
	// add implicit hydrogens in haystack explicitly
	for ($a=0;$a<count($haystackMolecule["atoms"]);$a++) {
		for ($b=0;$b<$haystackMolecule["atoms"][$a]["h"];$b++) {
			addAtomToGroup($haystackMolecule,"H",$a,1);
		}
		$haystackMolecule["atoms"][$a]["h"]=0;
	}
	
	if (!isset($needle["parts"])) { // fake maxatom for fingerprinting
		$needle["parts"][0]["maxatom"]=0;
	}
	
	if (!isset($haystackMolecule["parts"])) { // fake maxatom should not occur
		$haystackMolecule["parts"][0]["maxatom"]=0;
	}
	
	$matchmat=array();
	
	// mit höchstem Atom anfangen, weitere Teile werden ggf. von der Unterroutine aufgerufen
	$checkpart=0;
	$needle_maxatom=$needle["parts"][$checkpart]["maxatom"];
	$needle_matchpath[0]=$needle_maxatom; // am maxatom wird angefangen. Wenn das nicht gefunden wird - tschüß
	for ($a=0;$a<count($haystackMolecule["atoms"]);$a++) { // $a may be higher than atom number in original molecule due to explicit hydrogens
		if (count($needle["atoms"][ $needle_maxatom ]["n"])<=count($haystackMolecule["atoms"][$a]["n"]) && $needle["atoms"][ $needle_maxatom ]["b"]<=$haystackMolecule["atoms"][$a]["b"]) {
			$haystack_matchpath[0]=$a;
			if (matchPathsRecursive($needle,$haystackMolecule,$needle_matchpath,$haystack_matchpath,$checkpart,$paramHash)) {
				return true;
			}
		}
	}
	return false;
}

function getSubEmpFormulaMatch($needle,$haystackMolecule,$paramHash=array()) {
	checkSettings($paramHash,"sf");
	// prüft, ob needle im Hinblick auf die Summenformel eine Substruktur von haystackMolecule ist
	if (count($needle["emp_formula"])==0) { // no formula given
		return false;
	}
	foreach($needle["emp_formula"] as $sym => $number) {
		if ($haystackMolecule["emp_formula"][$sym]<$number)
			return false;
	}
	return true;
}


?>