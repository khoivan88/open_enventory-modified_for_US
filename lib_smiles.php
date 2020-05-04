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

require_once "lib_molecule_ranks.php";
require_once "lib_smiles_chiral.php";
require_once "lib_smiles_ez.php";
require_once "lib_array.php";

function fixIsotopes($sym) {
	switch ($sym) {
	case "D":
	case "T":
		$sym="H";
	break;
	}
	return $sym;
}

function SMgetAtom(& $molecule,$atom_no) {
	global $atMasses;
	$retval=array();
	$atom=& $molecule["atoms"][$atom_no];
	$sym=fixIsotopes($atom[ATOMIC_SYMBOL]);
	$molecule["atoms"][$atom_no]["SMdone"]=true;
	//~ $isIso=($atMasses[$atom[ATOMIC_NUMBER]-1]!=$atom[MASS]);
	$isSimple=(
		!$atom[IS_ISOTOPE]
		&& $atom[CHARGE]==0 
		&& in_array($sym,array("B","C","N","O","P","S","F","Cl","Br","I")) 
		&& $atom[RADICAL]==0
		);
	if (empty($atom["SMchirStereo"]) && $isSimple) { // simple Atom CON...
		// aromatic? lowercase
		if ($atom["ar"]) {
			$retval[]=strtolower($sym);
		}
		else {
			$retval[]=$sym;
		}
	}
	else { // "difficult" atom []
		if ($isSimple) { // only because of stereochemistry
			$killNext="_";
		}
		$retval[]=$killNext;
		$retval[]="[";
		// isotope ?
		if ($atom[IS_ISOTOPE]) {
			$retval[]=round($atom[MASS]);
		}
		// symbol
		$retval[]=$sym;
		// SMpost (@ or @@ for chiral)
		if ($atom["SMchirStereo"]==4) {
			$retval[]="@";
		}
		elseif ($atom["SMchirStereo"]==8) {
			$retval[]="@@";
		}
		// H-number
		$iHyd=$atom[IMPLICIT_H]; // o_h irrelevant
		if (is_array($atom[NEIGHBOURS])) foreach ($atom[NEIGHBOURS] as $neighbourNum) {
			if (isExplH($molecule,$neighbourNum)) {
				$molecule["atoms"][$neighbourNum]["SMdone"]=true;
				$iHyd++;
			}
		}
		$iHydText="";
		if ($iHyd>0) {
			$iHydText.="H";
		}
		if ($iHyd>1) {
			$iHydText.=$iHyd;
		}
		$retval[]=$killNext;
		$retval[]=$iHydText;
		// charge ?
		if ($atom[CHARGE]>0) {
			$retval[]=multStr("+",$atom[CHARGE]);
		}
		elseif ($atom[CHARGE]<0) {
			$retval[]=multStr("-",-$atom[CHARGE]);
		}
		$retval[]=$killNext;
		$retval[]="]";
	}
	return $retval;
}

function SMaddBond($order,$arAtom=false) {
	if ($arAtom && $order==1.5) { // "normaler Aromat", keine Bindung
		return "";
	}
	switch($order) {
	case 0:
		return"."; // not connected, according to daylight.com
	break;
	case 1.5: // Allyl-Anion-artige Bindung, mesomer
		return ":";
	break;
	case 2:
		return "=";
	break;
	case 3:
		return "#";
	break;
	}
	return ""; // single (-) / aromatic (:) are omitted
}

function SMgetLowestUnusedNeighbourRingbond(& $molecule,$path) { // returns bond no or false (no more unused neighbours)
// suchen: unbenutzte bindung zum benutzten nachbaratom im pfad (wenn es im pfad ist, muß es auch benutzt sein, sicherheitshalber trotzdem prüfen) niedrigster prio
	$atom_no=$path[count($path)-1];
	// $min_rank=1e10;
	$neighbours=& $molecule["atoms"][$atom_no][NEIGHBOURS]; // array von atomnummern
	if (!count($neighbours)) {
		return false;
	}
	$lowest_atom=SMgetFirstRingbondAtomFromList($molecule,$neighbours,$atom_no);
	if ($lowest_atom===FALSE) {
		return false;
	}
	// $lowest_atom=false;
	for ($b=1;$b<count($neighbours);$b++) {
		$a=$neighbours[$b];
		// $rank=& $molecule["atoms"][$a]["rank"];
		
		if (in_array($a,array_slice($path,0,-2)) && $molecule["atoms"][$a]["SMdone"] && ($molecule["atoms"][$a][ATOMIC_SYMBOL]!="H" || $molecule["SMexplH"]) && !$molecule["bondsFromNeighbours"][$a][$atom_no]["SMdone"] && SMisHigherThan($molecule["atoms"][$lowest_atom],$molecule["atoms"][$a])) {
			// $min_rank=$rank;
			$lowest_atom=$a;
		}
	}
	return $lowest_atom;
}

function SMgetLowestUnusedNeighbourAtom(& $molecule,$atom_no) { // returns atom no or false (no more unused neighbours)
	return SMgetLowestAtomNoFromList($molecule,$molecule["atoms"][$atom_no][NEIGHBOURS]);
}

function SMgetFirstUnusedAtomFromList(& $molecule,& $list) { // returns atom no or false (no more unused neighbours)
	for ($b=0;$b<count($list);$b++) {
		$a=$list[$b];
		if (!$molecule["atoms"][$a]["SMdone"] && !isExplH($molecule,$a)) {
			return $a;
		}
	}
	return false;
}

function SMgetFirstRingbondAtomFromList(& $molecule,& $list,$atom_no) { // returns atom no or false (no more unused neighbours)
	for ($b=0;$b<count($list);$b++) {
		$a=$list[$b];
		if ($molecule["atoms"][$a]["SMdone"] && !isExplH($molecule,$a) && !$molecule["bondsFromNeighbours"][$a][$atom_no]["SMdone"]) {
			return $a;
		}
	}
	return false;
}

function SMgetLowestAtomNoFromList(& $molecule,& $list) { // returns atom no or false (no more unused neighbours)
	$lowest_atom=SMgetFirstUnusedAtomFromList($molecule,$list);
	if ($lowest_atom===FALSE) {
		return false;
	}
	// $lowest_atom=false;
	for ($b=1;$b<count($list);$b++) {
		$a=$list[$b];
		// $rank=& $molecule["atoms"][$a]["rank"];
		
		if (!$molecule["atoms"][$a]["SMdone"] && !isExplH($molecule,$a) && SMisHigherThan($molecule["atoms"][$lowest_atom],$molecule["atoms"][$a])) {
			// $min_rank=$rank;
			$lowest_atom=$a;
		}
	}
	return $lowest_atom;	
}

function SMgetFragmentStartAtom(& $molecule) {
	// suche das atom niedrigster prio
	$atomCount=count($molecule["atoms"]);
	if (!$atomCount) {
		return false;
	}
	$list=range(0,$atomCount-1);
	return SMgetLowestAtomNoFromList($molecule,$list);
}

function SMgetRingNr($nr) {
	$nr+=0;
	if ($nr<1) {
		return "";
	}
	if ($nr<10) {
		return $nr;
	}
	return "%".$nr;
}

function SMgetOrder(& $molecule,$a1,$a2) {
	if (isset($molecule["bondsFromNeighbours"][$a1][$a2][SMILES_BOND_ORDER])) {
		return $molecule["bondsFromNeighbours"][$a1][$a2][SMILES_BOND_ORDER];
	}
	return $molecule["bondsFromNeighbours"][$a1][$a2][BOND_ORDER];
}

function isExplH(& $molecule,$atom_no) { // only single bonded Hs without charge
	global $atMasses;
	$atom=& $molecule["atoms"][$atom_no];
	$retval=$atom["SMimplH"] || (
		!$molecule["SMexplH"] // ausgeschriebenes H, das expl sein MUSS zB in H2
		&& !$atom["SMexplH"]
		&& $atom[ATOMIC_SYMBOL]=="H" 
		&& $atom[BONDS]==1 
		&& $atom[CHARGE]==0 
		//~ && $atMasses[$atom[ATOMIC_NUMBER]-1]==$atom[MASS]
		&& !$atom[IS_ISOTOPE]
		);
	if ($retval) {
		$atom["SMimplH"]=true; // ausgeschriebenes H, KÖNNTE genausogut impl sein
	}
	return $retval;
}

function getCoordsFromAtom(& $a) {
	return array($a["x"],$a["y"],$a["z"]);
}

function getSMILESforBranch(& $molecule,$path) { // returns part of SMILES starting from atom_no
	$atom_no=$path[count($path)-1];
	$from_atom_no=$path[count($path)-2];
	
	// remove expl Hs unless H-only
	if (isExplH($molecule,$atom_no)) {
		if (!isset($from_atom_no)) {
			$from_atom_no=$molecule["atoms"][$atom_no][NEIGHBOURS][0];
		}
		$molecule["bondsFromNeighbours"][$from_atom_no][$atom_no]["SMdone"]=true;
		$molecule["atoms"][$atom_no]["SMdone"]=true;
		return array("");
	}
	
	// remove protons and increase eProt
	if (
		$molecule["atoms"][$atom_no][ATOMIC_SYMBOL]=="H"
		&&
		$molecule["atoms"][$atom_no][CHARGE]==1
		&&
		$molecule["atoms"][$atom_no][BONDS]==0
		&&
		!$molecule["atoms"][$atom_no][IS_ISOTOPE]
	) {
		$molecule["eProt"]++;
		$molecule["atoms"][$atom_no]["SMdone"]=true;
		return array("");
	}
	
	$retval=array();
	// bindung hinzufügen
	if (isset($from_atom_no)) {
		$retval[]=SMaddBond(SMgetOrder($molecule,$from_atom_no,$atom_no),$molecule["atoms"][$atom_no]["ar"]);
		$molecule["bondsFromNeighbours"][$from_atom_no][$atom_no]["SMdone"]=true;
	}
	
	// Doppelbindung Konfiguration, nicht für Ringe (zu fehlerträchtig am Ringschluß)
	if (isset($from_atom_no) && !empty($molecule["atoms"][$atom_no]["SMdblStereo"])) { // erstes Atom NICHT
		// SMdblPartner
		$fromHigh=($from_atom_no==$molecule["atoms"][$atom_no]["SMdblHighAtom"]); // wir kommen vom höheren Teil VOR der Doppelbindung
		$z_conf=($molecule["atoms"][$atom_no]["SMdblStereo"]==1); // E=2
		$dblAtom=$molecule["atoms"][$atom_no]["SMdblPartner"];
		if (!$molecule["atoms"][$dblAtom]["SMdone"] && SMgetOrder($molecule,$dblAtom,$atom_no)==2 && !count($molecule["bondsFromNeighbours"][$dblAtom][$atom_no][RINGS])) { // könnte inzwischen aromatisch geworden sein
		//  && !count($molecule["bondsFromNeighbours"][$dblAtom][$atom_no][RINGS]) // Doppelbindungen in Ringen raus (macht ggf arge Probleme)
		
			if ($molecule["atoms"][$atom_no]["SMpre"]=="\\") { // bereits da durch vorangehende Doppelbindung, Konjugation, nächsten Deskriptor invertieren
				$conjInvert=true;
			}
			else {
				$conjInvert=false;
				$molecule["atoms"][$atom_no]["SMpre"]="/";
			}
			$high2=$molecule["atoms"][$dblAtom]["SMdblHighAtom"]; // Atom höchster Priorität von der Doppelbindung weg
			$molecule["atoms"][$high2]["SMpre"]=(($fromHigh xor $z_conf xor $conjInvert)?"/":"\\");
		}
	}
	
	// SMpre für Atom anfügen
	$retval[]=$molecule["atoms"][$atom_no]["SMpre"];
	unset($molecule["atoms"][$atom_no]["SMpre"]); // avoid ghost slashes at ring closures
	
	// atom hinzufügen
	$retval=array_merge($retval,SMgetAtom($molecule,$atom_no));
	//~ $molecule["atoms"][$atom_no]["SMdone"]=true; // in SMgetAtom verlegen
	
	// branches? verzweigungen suchen
	do {
		$next_atom=SMgetLowestUnusedNeighbourAtom($molecule,$atom_no);
		if ($next_atom===FALSE) {
			break;
		}
		$newBranch=getSMILESforBranch($molecule,array_merge($path,array($next_atom)));
		if (count($newBranch)) {
			$branch_SMILES[]=$newBranch;
		}
	} while (true);
	
	// ringe markieren, ringnumerierung ist determiniert durch Ranks
	do {
		$next_atom=SMgetLowestUnusedNeighbourRingbond($molecule,$path);
		if ($next_atom===FALSE) {
			break;
		}
		$molecule["bondsFromNeighbours"][$atom_no][$next_atom]["SMdone"]=true;
		$molecule["bondsFromNeighbours"][$atom_no][$next_atom]["ring"]= ++$molecule["ringNo"]; // vorläufige Ringnummer generieren
	} while (true);
	
	// ringe schreiben, sortiert wird später
	$atomRings=array();
	for ($a=0;$a<count($molecule["atoms"][$atom_no][NEIGHBOURS]);$a++) {
		$neighbour=& $molecule["atoms"][$atom_no][NEIGHBOURS][$a];
		$ring=$molecule["bondsFromNeighbours"][$neighbour][$atom_no]["ring"]; // vorläufige Ringnummer
		if ($ring) {
			// Array Teil Anfang
			if ($molecule["bondsFromNeighbours"][$neighbour][$atom_no]["SMbondSkipped"]) { // Bindung über Ring vor ERSTES Erscheinen der Zahl
				$bond=SMaddBond(SMgetOrder($molecule,$neighbour,$atom_no),$molecule["atoms"][$atom_no]["ar"]);
			}
			else { // rekursive Funktion kommt zuerst hier vorbei (Richtung Ende des SMILES-Strings)
				$bond=$molecule["atoms"][$neighbour]["SMpre"]; // usually "", only "/" for stereo at ring closures // .$molecule["atoms"][$atom_no]["SMpre"]
				$molecule["bondsFromNeighbours"][$neighbour][$atom_no]["SMbondSkipped"]=true;
			}
			// Array Teil Ende
			// Array Teil ZAHL Anfang
			$atomRings[]=array($bond,$ring,$stereo); // SMgetRingNr($ring);
			// Array Teil ZAHL Ende
		}
	}
	
	if (count($atomRings)) {
		$retval[]=$atomRings;
	}

	// branches? start with ( and lowest prio branch
	if (is_array($branch_SMILES)) for ($a=0;$a<count($branch_SMILES);$a++) {
		// Array Teil Anfang
		$brackets=($a<count($branch_SMILES)-1);
		if ($brackets) {
			$retval[]="(";
		}
		$retval=array_merge($retval,$branch_SMILES[$a]);
		if ($brackets) {
			$retval[]=")";
		}
		// Array Teil Ende
	}
	// fragment komplettieren
	return $retval;
}

function SMsortAtomRings($a,$b) { // Ringfragmente nach Nummer sortieren
	return $a[1]-$b[1];
}

function SMjoinFragment($fragmentArray) { // array durchgehen ** Anzahl der Ringe ** nach unten zählen
// "C",array(array("",1)),"CC",array(array("",1),array("=",2)),...
	// $fragmentArray für stereoSMILES, für normales SMILES einfach / \ und @ weglassen
	if (!is_array($fragmentArray) || count($fragmentArray)==0) {
		return $fragmentArray;
	}
	//~ print_r($fragmentArray);
	// Ersetzungstabelle für Zahlen aufbauen
	$reversedNumbers=array();
	// hinten anfangen mit auflistung
	$reversedFragmentArray=array_reverse($fragmentArray);
	foreach ($reversedFragmentArray as $fragment) {
		if (is_array($fragment) && count($fragment)) { // Ringanknüpfungen/Nummern für EIN Atom
			usort($fragment,"SMsortAtomRings"); // hier auch schon sortieren, sonst ist Zufall im SMILES
			foreach ($fragment as $atomRing) { // Ringanknüpfungen an Atom durchgehen
				if (!empty($atomRing[1]) && !in_array($atomRing[1],$reversedNumbers)) { // Zahl  noch nicht erfaßt
					$reversedNumbers[]=$atomRing[1];
				}
			}
		}
	}
	unset($reversedFragmentArray);
	// Länge von $reversedNumbers ist Zahl der numerierten Ringe
	$dictionary=array_flip(array_reverse($reversedNumbers)); // $dictionary[oldnum]+1 ist neue Nummer
	//~ print_r($dictionary);
	for ($a=0;$a<count($fragmentArray);$a++) {
		$fragment=& $fragmentArray[$a];
		// Zahlen ersetzen
		if (is_array($fragment) && count($fragment)) { // Ringanknüpfungen/Nummern für EIN Atom
			for ($b=0;$b<count($fragment);$b++) {
				$fragment[$b][1]=$dictionary[$fragment[$b][1]]+1;
			}
			// an Brückenkopfatomen die Nummern sortieren
			usort($fragment,"SMsortAtomRings");
			for ($b=0;$b<count($fragment);$b++) { // join bond and number (SMILES formatted)
				$fragment[$b]=$fragment[$b][0].SMgetRingNr($fragment[$b][1]).$fragment[$b][2];
			}
			// join numbers
			$fragmentArray[$a]=join($fragment);
		}
	}
	// make stereo and nostereo
	$fragmentArray_nostereo=array();
	for ($a=0;$a<count($fragmentArray);$a++) {
		if ($fragmentArray[$a]=="_") {
			$a++;
		}
		else {
			$fragmentArray_nostereo[$a]=$fragmentArray[$a];
		}
	}
	
	$stereo=str_replace(array("_"),"",join("",$fragmentArray));
	$no_stereo=str_replace(array("/","\\","@"),"",join("",$fragmentArray_nostereo));
	// join the whole rest
	return array($no_stereo,$stereo);
}

function moleculeGetSMILES(& $molecule) { // byRef, damit eProt gesetzt werden kann, d.h. explizit gezeichnete Protonen
	// braucht atom-ranks, sonst wird es nicht canonical
	// gibt es atome
	if (count($molecule["atoms"])==0) {
		return "";
	}

	// "abgehakt" zurücksetzen
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		/* if (!$molecule["SMexplH"] && $molecule["atoms"][$a][ATOMIC_SYMBOL]=="H") {
			$molecule["atoms"][$a]["SMdone"]=true;
			if ($molecule["atoms"][$a][BONDS]) {
				$b=$molecule["atoms"][$a][NEIGHBOURS][0];
				$molecule["bondsFromNeighbours"][$b][$a]["SMdone"]=true;
			}
		}
		else {*/
			$molecule["atoms"][$a]["SMdone"]=false;
		//}
	}
	for ($a=0;$a<count($molecule[BONDS]);$a++) {
		$molecule[BONDS][$a]["SMdone"]=false;
		$molecule[BONDS][$a]["SMbondSkipped"]=false;
	}
	$molecule["ringNo"]=0;
	$no_stereo=array();
	$stereo=array();
	do {
		// beim niedrigsten rank anfangen
		// nächstniedrigeren rank suchen, der noch nicht belegt ist
		$atom=SMgetFragmentStartAtom($molecule);
		//~ echo "X".$atom."X";
		if ($atom===FALSE) {
			break;
		}
		//~ if ($stereo!="") { // multipart
			//~ $no_stereo.=".";
			//~ $stereo.=".";
		//~ }
		list($no_stereo_frag,$stereo_frag)=SMjoinFragment(getSMILESforBranch($molecule,array($atom)));
		if (!empty($stereo_frag)) {
			$no_stereo[]=$no_stereo_frag;
			$stereo[]=$stereo_frag;
		}
	} while (true);
	
	// add protons
	$proton_count=$molecule["iProt"]+$molecule["eProt"];
	if ($proton_count>0) {
		$proton="[H+]";
		$proton_array=array_fill(0,$proton_count,$proton);
		$no_stereo=array_merge($no_stereo,$proton_array);
		$stereo=array_merge($stereo,$proton_array);
	}
	
	return array(join(".",$no_stereo),join(".",$stereo));
	// leave away stereo at the moment
}

?>