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

require_once "lib_formatting.php";

function optimiseMoleculeParts(& $molecule) {
	if (count($molecule["orig_parts"])<2) { // would be waste of time
		return;
	}
	// Dimensionen der Teile messen
	$distanceV=1; // 1 Bindungslänge
	$distanceH=1; // 1 Bindungslänge
	$minHeight=1;
	$minWidth=1;
	
	$area=array();
	for ($a=0;$a<count($molecule["orig_parts"]);$a++) {
		$area[$a]=getMoleculePartDimensions($molecule,$a);
		
		// set dimensions at least to min
		$molecule["orig_parts"][$a]["width"]=max($molecule["orig_parts"][$a]["width"],$minWidth);
		$molecule["orig_parts"][$a]["height"]=max($molecule["orig_parts"][$a]["height"],$minHeight);
		//~ echo $a.": ".$area[$a]."\n";
	}
	// Teile nach absteigender Fläche sortieren
	asort($area,SORT_NUMERIC);
	$area=array_reverse($area,true);
	$part_order=array_keys($area);
	
	$part0=$part_order[0];
	for ($a=0;$a<count($part_order)-1;$a++) { // der nächstkleinere Teil wird verschoben
		$c=$part_order[$a];
		//~ for ($b=$a+1;$b<count($part_order);$b++) {
		//~ $b=$a+1;
		//~ $d=$part_order[$b];
		$d=$part_order[$a+1];
		// Berührung in x- oder y-Richtung?, relative ORIGINALAbstände der beiden Teile vergleichen
		//~ if (abs($molecule["orig_parts"][$c]["orig_centerX"]-$molecule["orig_parts"][$d]["orig_centerX"])>abs($molecule["orig_parts"][$c]["orig_centerY"]-$molecule["orig_parts"][$d]["orig_centerY"])) { // in x-Richtung
		if (
			abs($molecule["orig_parts"][$part0]["orig_centerX"]-$molecule["orig_parts"][$d]["orig_centerX"])>abs($molecule["orig_parts"][$part0]["orig_centerY"]-$molecule["orig_parts"][$d]["orig_centerY"])
		) { // in x-Richtung
			// es wird NUR der kleinere Teil $d verschoben
			// Abstand in x-Richtung auf struc_margin bringen
			$delta_x=$molecule["orig_parts"][$c]["centerX"]+0.5*$molecule["orig_parts"][$c]["width"]+0.5*$molecule["orig_parts"][$d]["width"]-$molecule["orig_parts"][$d]["centerX"]+$distanceH;
			// $d in den Korridor $c.top-$c.bottom verschieben
			$minDcenterY=$molecule["orig_parts"][$c]["centerY"]-0.5*$molecule["orig_parts"][$c]["height"]+0.5*$molecule["orig_parts"][$d]["height"];
			$maxDcenterY=$molecule["orig_parts"][$c]["centerY"]+0.5*$molecule["orig_parts"][$c]["height"]-0.5*$molecule["orig_parts"][$d]["height"];
			//~ echo $molecule["orig_parts"][$d]["centerY"]."X".$minDcenterY."Y".$maxDcenterY;
			$newDcenterY=constrainVal($molecule["orig_parts"][$d]["centerY"],$minDcenterY,$maxDcenterY);
			$delta_y=$newDcenterY-$molecule["orig_parts"][$d]["centerY"];
		}
		else { // in y-Richtung
			// es wird NUR der kleinere Teil $d verschoben
			// Abstand in y-Richtung auf struc_margin bringen
			$delta_y=$molecule["orig_parts"][$c]["centerY"]-0.5*$molecule["orig_parts"][$c]["height"]-0.5*$molecule["orig_parts"][$d]["height"]-$molecule["orig_parts"][$d]["centerY"]-$distanceV; // unterhalb
			// $d in den Korridor $c.left-$c.right verschieben
			$minDcenterX=$molecule["orig_parts"][$c]["centerX"]-0.5*$molecule["orig_parts"][$c]["width"]+0.5*$molecule["orig_parts"][$d]["width"];
			$maxDcenterX=$molecule["orig_parts"][$c]["centerX"]+0.5*$molecule["orig_parts"][$c]["width"]-0.5*$molecule["orig_parts"][$d]["width"];
			//~ echo $molecule["orig_parts"][$d]["centerY"]."X".$minDcenterX."Y".$maxDcenterX;
			$newDcenterX=constrainVal($molecule["orig_parts"][$d]["centerX"],$minDcenterX,$maxDcenterX);
			$delta_x=$newDcenterX-$molecule["orig_parts"][$d]["centerX"];
		}
		//~ die($d."X".$delta_x."Y".$delta_y);
		// es wird NUR der kleinere Teil $d verschoben
		shiftMoleculePart($molecule,$d,$delta_x,$delta_y);
		// so verschieben, daß der kleinere Teil $d in einer Dimension x/y zu $c den Abstand struc_margin hat und in der anderen im Korridor steht
		//~ }
	}
}

function getMoleculePartDimensions(& $molecule,$part_no) {
	if (!count($molecule["orig_parts"][$part_no]["atoms"])) {
		return 0;
	}
	$atom0=$molecule["orig_parts"][$part_no]["atoms"][0];
	$minX=$molecule["atoms"][$atom0]["x"];
	$maxX=$minX;
	$minY=$molecule["atoms"][$atom0]["y"];
	$maxY=$minY;
	for ($a=1;$a<count($molecule["orig_parts"][$part_no]["atoms"]);$a++) {
		$atom_no=$molecule["orig_parts"][$part_no]["atoms"][$a];
		$minX=min($minX,$molecule["atoms"][$atom_no]["x"]);
		$maxX=max($maxX,$molecule["atoms"][$atom_no]["x"]);
		$minY=min($minY,$molecule["atoms"][$atom_no]["y"]);
		$maxY=max($maxY,$molecule["atoms"][$atom_no]["y"]);
	}
	//~ echo $part_no."A".$minX."B".$maxX."C".$minY."D".$maxY."<br>";
	$width=$maxX-$minX;
	$height=$maxY-$minY;
	$molecule["orig_parts"][$part_no]["width"]=$width;
	$molecule["orig_parts"][$part_no]["height"]=$height;
	$centerX=$minX+0.5*$width;
	$centerY=$minY+0.5*$height;
	$molecule["orig_parts"][$part_no]["orig_centerX"]=$centerX;
	$molecule["orig_parts"][$part_no]["centerX"]=$centerX;
	$molecule["orig_parts"][$part_no]["orig_centerY"]=$centerY;
	$molecule["orig_parts"][$part_no]["centerY"]=$centerY;
	return $width*$height;
}

function shiftMoleculePart(& $molecule,$part_no,$delta_x,$delta_y,$delta_z=0) {
	if (!count($molecule["orig_parts"][$part_no])) {
		return;
	}
	if (isset($molecule["orig_parts"][$part_no]["centerX"])) {
		$molecule["orig_parts"][$part_no]["centerX"]+=$delta_x;
	}
	if (isset($molecule["orig_parts"][$part_no]["centerY"])) {
		$molecule["orig_parts"][$part_no]["centerY"]+=$delta_y;
	}
	for ($a=0;$a<count($molecule["orig_parts"][$part_no]["atoms"]);$a++) {
		$atom_no=$molecule["orig_parts"][$part_no]["atoms"][$a];
		$molecule["atoms"][$atom_no]["x"]+=$delta_x;
		$molecule["atoms"][$atom_no]["y"]+=$delta_y;
		$molecule["atoms"][$atom_no]["z"]+=$delta_z;
	}
	for ($a=0;$a<count($molecule[RINGS]);$a++) {
		if ($part_no==$molecule[RINGS][$a][PART]) {
			$molecule[RINGS][$a]["x"]+=$delta_x;
			$molecule[RINGS][$a]["y"]+=$delta_y;
		}
	}
}

function scaleMolecule(& $molecule, $scale) {
	// Koordinaten mit $scale multiplizieren
	if ($scale<=0) {
		return;
	}
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		$molecule["atoms"][$a]["x"]*=$scale;
		$molecule["atoms"][$a]["y"]*=$scale;
		$molecule["atoms"][$a]["z"]*=$scale;
	}
	for ($a=0;$a<count($molecule[RINGS]);$a++) {
		$molecule[RINGS][$a]["x"]*=$scale;
		$molecule[RINGS][$a]["y"]*=$scale;
	}
}

function normaliseReaction(& $reaction) {
	// Größe der Moleküle angleichen
	for ($a=0;$a<count($reaction["molecules"]);$a++) {
		$molecule=& $reaction["molecules"][$a];
		// get Molscale
		$scale=getMolscale($molecule);
		// skalieren
		scaleMolecule($reaction["molecules"][$a], $scale);
	}
}

function getMolscale(& $molecule) {
	// mittlere Bindungslänge bestimmen, dann Skalierungsfaktor anpassen
	$sumLength=0;
	if (count($molecule[BONDS])==0) {
		$atomCount=count($molecule["atoms"]);
		for ($atom1=0;$atom1<$atomCount;$atom1++) {
			for ($atom2=$atom1+1;$atom2<$atomCount;$atom2++) {
				$sumLength+=sqrt(
					pow($molecule["atoms"][$atom1]["x"]-$molecule["atoms"][$atom2]["x"],2)+
					pow($molecule["atoms"][$atom1]["y"]-$molecule["atoms"][$atom2]["y"],2)+
					pow($molecule["atoms"][$atom1]["z"]-$molecule["atoms"][$atom2]["z"],2)
				);
			}
		}
		$noBonds=$atomCount*($atomCount-1);
	}
	else {
		for ($a=0;$a<count($molecule[BONDS]);$a++) {
			$atom1=$molecule[BONDS][$a][ATOM1];
			$atom2=$molecule[BONDS][$a][ATOM2];
			$sumLength+=sqrt(
				pow($molecule["atoms"][$atom1]["x"]-$molecule["atoms"][$atom2]["x"],2)+
				pow($molecule["atoms"][$atom1]["y"]-$molecule["atoms"][$atom2]["y"],2)+
				pow($molecule["atoms"][$atom1]["z"]-$molecule["atoms"][$atom2]["z"],2)
			);
		}
		$noBonds+=count($molecule[BONDS]);
	}
	// }
	//~ echo $sumLength/$noBonds;
	if ($sumLength<=0 || $noBonds<=0) {
		return 1;
	}
	//~ $molecule["avBondLen"]=$noBonds/$sumLength;
	return 26*bond_scale*$noBonds/$sumLength; // 1.4*18.571
}

function getMoleculeDimensions($molecule,$scale,$margin) { // Vorsicht, für scale scale*molscale verwenden
	$xMin=pow(2,31);
	$xMax=-$xMin;
	$yMin=$xMin;
	$yMax=-$xMin;


	// xMin, xMax, yMin, yMax bestimmen
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		$thisX=$molecule["atoms"][$a]["x"];
		$thisY=$molecule["atoms"][$a]["y"];
		if (is_numeric($thisX) && is_numeric($thisY)) { // hole xMin, xMax, yMin,yMax
			if ($thisX<$xMin) {
				$xMin=$thisX;
			}
			if ($thisX>$xMax) {
				$xMax=$thisX;
			}
			if ($thisY<$yMin) {
				$yMin=$thisY;
			}
			if ($thisY>$yMax) {
				$yMax=$thisY;
			}
		}
	}

	// bildhöhe und breite berechnen
	// Höhe = (yMax-yMin)/1.4*26px*scale+margin, mindestens minY
	// Breite analog
	$iHeight=($yMax-$yMin)*$scale+2*$margin;
	$iWidth=($xMax-$xMin)*$scale+2*$margin;
	if ($iHeight<5)
		$iHeight=5;
	if ($iWidth<5)
		$iWidth=5;
	return array($xMin,$yMin,$xMax,$yMax,$iWidth,$iHeight);
}

?>