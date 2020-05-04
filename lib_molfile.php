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

/*
zur Minimierung des Speicherbedarfs für die serialisierten Moleküle wurden die Indizes stark verkürzt:
anum			=>	a
aromatic			=>	ar
bonds			=>	b
charge 			=> 	c
iHyd				=>	h
mass			=>	m
neighbours 		=> 	n
nonHbonds		=>	nb
nonHneighbours	=>	nnb
order			=>	o
orig_charge		=>	o_c
orig_order		=>	o_o
radical			=>	r
original radical	=>	o_r
part				=>	p
part_pairs		=>	p_p
rings			=>	ri
sym				=> 	s
stereo			=>	st

NEW by 08.01.09
hide (bond/atom)	=> hi
te (for collapsed groups) => te
*/

define("AROMATIC","ar");
define("NO_TRUE_AROMAT","nr_ar"); // HCp
//~ define("RING_MEMBER","rm");
define("RINGS","ri");
define("RANKS","ra");
define("INVARIANT","iv");
define("PART","p");
define("ORIG_PART","o_p");
define("BOND_NO","#"); // to find entry when using the bondsFromNeighbours reference
define("BONDS","b");
define("ORIG_BONDS","o_b");
define("VALENCY","v");
define("ATOMIC_NUMBER","an");
define("ATOMIC_SYMBOL","s");
define("NEIGHBOURS","n");
define("H_NEIGHBOURS","hb");
define("NON_H_BONDS","nb");
define("NON_H_NEIGHBOURS","nnb");
define("IS_CUMULENE","cum"); // draw C
define("IS_ISOTOPE","iso");
define("MASS","m");
define("BOND_ORDER","o");
define("ORIG_BOND_ORDER","o_o");
define("SMILES_BOND_ORDER","sm_o");
define("IMPLICIT_H","h");
define("ORIG_IMPLICIT_H","o_h");
define("CHARGE","c");
define("ORIG_CHARGE","o_c");
define("SMILES_CHARGE","sm_c");
define("RADICAL","r");
define("ORIG_RADICAL","o_r");
define("OXIDATION_STATE","ox");
define("HYBRIDISATION_STATE","hy");
define("BOND_POLARITY","pol");
define("STEREO","st");
define("EXPAND","exp");
define("HIDE","hi");
define("GROUPS","gr");
define("GROUP_TYPE","gt");
define("GROUP_TEXT","te");
define("GROUP_TEXT2","te2");
define("BRACKETS","bra");
define("ATOM1","a1");
define("ATOM2","a2");
define("MAX_AROMAT_SIZE",12);
define("FINGERPRINT_MAX_RING_SIZE",12);

// replace atoms,parts,orig_parts,bondsFromNeighbours,noSubst,SM*,iProt,eProt,maxatom,minatom,assign

define("newHeader","  0  0  0  0  0  0  0  0999 V2000");
define("oldHeader","  0  0  0  0  0  0  0  0  0 V2000");
define("bottomLine","M  END\n");

require_once "lib_formatting.php";
//~ require_once "lib_global_funcs.php";
//~ require_once "lib_global_settings.php";
require_once "lib_smiles.php";
require_once "lib_molecule_geometry.php";
require_once "lib_draw_molecule.php";
require_once "lib_molecule_substructure.php";
//~ require_once "lib_reaction_mapping.php";
require_once "lib_fingerprint.php";
require_once "lib_molecule_metalorg.php";
require_once "lib_molfile_rings.php";
require_once "lib_atom_data.php";
require_once "lib_array.php";

function getImplHyd($valency,$bonds,$charge=0,$radical=0) {
	//~ global $valencies;
	//~ $valency=$valencies[$sym];
	//~ if (!isset($valency)) { // transition metal
		//~ return 0;
	//~ }
	switch ($radical) {
	case 1: // :
		$bonds+=2;
	break;
	case 2: // .
		$bonds++;
	break;
	}
	if ($valency-$charge<4) {
	//~ if ($valency<4) {
		$retval=$valency-$bonds-abs($charge); // charge is positive in most cases here, except hydride, BH4- etc. 
	}
	else {
		$retval=8-$valency-$bonds+$charge; // charge can be either positive or negative
	}
	if ($retval<0 && $valency>0) {
		$retval+=2;
	}
	if ($retval<0 || $retval>4 || $retval>$valency+1) {
		return 0;
	}
	return $retval;
}

function isMoleculeInFormula($molecule,$emp_formulaStr) { // mw zu klein filtert schon db raus
	// testet, ob molecule im Hinblick auf die Summenformel in $emp_formulaStr (aus der DB) enthalten sein kann
	foreach ($molecule["emp_formula"] as $sym => $number) { // very quick check, if Nb is in and we search for N, it does not get kicked
		if (strpos($sym,$emp_formulaStr)===FALSE) {
			return false;
		}
	}
	$molecule2=readSumFormula($emp_formulaStr);
	foreach ($molecule["emp_formula"] as $sym => $number) {
		if ($molecule2["emp_formula"][$sym]<$number) {
			return false;
		}
	}
	return true;
}

function getAtomMass($sym) {
	// nimmt Symbol, gibt Masse zurück
	global $pse,$atMasses;
	if ($sym=="D") {
		return 2.01402;
	}
	else {
		return $atMasses[ $pse[$sym]-1 ];
	}
}

function combineMolecules(& $molecule, & $addMolecule) {
	$inc=count($molecule["atoms"]);
	$inc_bonds=count($molecule[BONDS]);
	unset($molecule["parts"]); // wird neu gemacht
	unset($molecule["fingerprints"]); // wird neu gemacht
	unset($molecule[RINGS]); // wird neu gemacht
	unset($molecule["ringtypes"]); // wird neu gemacht
	
	// parts wird eh gelöscht
	/* for ($a=0;$a<count($molecule["parts"]);$a++) {
		// parts löschen
		unset($molecule["parts"][$a]["maxatom"]);
	} */
	
	// Bindungen von addMolecule umnumerieren
	for ($a=0;$a<count($addMolecule[BONDS]);$a++) {
		$addMolecule[BONDS][$a][ATOM1]+=$inc;
		$addMolecule[BONDS][$a][ATOM2]+=$inc;
		$addMolecule[BONDS][$a][BOND_NO]+=$inc_bonds;
	}
	
	// Neighbours umnumerieren
	for ($a=0;$a<count($addMolecule["atoms"]);$a++) {
		// parts löschen
		unset($addMolecule["atoms"][$a][PART]);
		for ($b=0;$b<count($addMolecule["atoms"][$a][NEIGHBOURS]);$b++) {
			$addMolecule["atoms"][$a][NEIGHBOURS][$b]+=$inc;
		}
	}
	
	// atome zusammenhängen
	$molecule["atoms"]=arr_merge($molecule["atoms"],$addMolecule["atoms"]);
	
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		$molecule["atoms"][$a][CHARGE]=$molecule["atoms"][$a][ORIG_CHARGE]; // reset completely
		// parts löschen
		unset($molecule["atoms"][$a][PART]);
		unset($molecule["atoms"][$a]["SMcharge_atoms"]);
		unset($molecule["atoms"][$a][SMILES_CHARGE]);
	}
	
	// bindungen zusammenhängen
	$molecule[BONDS]=arr_merge($molecule[BONDS],$addMolecule[BONDS]);
	
	for ($a=0;$a<count($molecule[BONDS]);$a++) {
		$molecule[BONDS][$a][BOND_ORDER]=$molecule[BONDS][$a][ORIG_BOND_ORDER]; // reset completely
		unset($molecule[BONDS][$a][RINGS]);
	}
	
	// build new references
	renewBondsFromNeighbours($molecule);
	
	// gesamtladung addieren
	$molecule["total_charge"]+=$addMolecule["total_charge"];
	$molecule["iProt"]+=$addMolecule["iProt"];
	//~ $molecule["emp_formula_string"]=getEmpFormula($molecule); // überflüssig
	
	// smiles etc hinterher neu generieren!!! (leichte zeitverschwendung, aber viel weniger code)
	
}

function removeNeighbourFrom1(& $molecule,$a1,$a2) {
	for ($d=0;$d<count($molecule["atoms"][$a1][NEIGHBOURS]);$d++) {
		if ($molecule["atoms"][$a1][NEIGHBOURS][$d]==$a2) {
			array_splice($molecule["atoms"][$a1][NEIGHBOURS],$d,1);
			break;
		}
	}
}

function removeNeighboursFromAtoms(& $molecule,$a1,$a2) {
	removeNeighbourFrom1($molecule,$a1,$a2);
	removeNeighbourFrom1($molecule,$a2,$a1);
}

function removeNeighboursFromBond(& $molecule,$bond_no) {
	$a1=$molecule[BONDS][$bond_no][ATOM1];
	$a2=$molecule[BONDS][$bond_no][ATOM2];
	removeNeighboursFromAtoms($molecule,$a1,$a2);
}

function removeZeroBonds(& $molecule) {
	for ($a=count($molecule[BONDS])-1;$a>=0;$a--) {
		if ($molecule[BONDS][$a][BOND_ORDER]==0) {
			array_splice($molecule[BONDS],$a,1);
		}
	}	
}

function renewBondsFromNeighbours(& $molecule) {
	$molecule["bondsFromNeighbours"]=array();
	for ($a=0;$a<count($molecule[BONDS]);$a++) {
		$a1=$molecule[BONDS][$a][ATOM1];
		$a2=$molecule[BONDS][$a][ATOM2];
		$molecule["bondsFromNeighbours"][$a1][$a2]=& $molecule[BONDS][$a];
		$molecule["bondsFromNeighbours"][$a2][$a1]=& $molecule[BONDS][$a];
	}	
}

function isRxnfile($molfile) {
	return startswith($molfile,"\$RXN");
}

function readRxnfile($rxnfileStr,$paramHash=array()) { // liest rxnFile und gibt $reaction zurück, reaction[molecules][n], reaction[reactants], reaction[products]
	checkSettings($paramHash,"rxn");
	
	$rxnfileStr=removePipes($rxnfileStr);
	
	// does it start with $RXN ??
	if (!isRxnfile($rxnfileStr)) {
		// No => Molfile, take molecule as 1st reactant with no products
		$molecule=readMolfile($rxnfileStr,$paramHash);
		if (count($molecule["atoms"])) {
			$reaction["reactants"]=1;
			$reaction["products"]=0;
			$reaction["molecules"][0]=$molecule;
		}
	}
	else {

		$molecules=explode("\$MOL",$rxnfileStr);
		
		// Anzahl Edukte Produkte
		$headerLines=explode("\n",trim($molecules[0]));
		$countsLine=array_pop($headerLines);
		
		$reaction=array(
			"reactants" => 0, 
			"products" => 0, 
			"molecules" => array(), 
			//~ "data" => json_decode(array_pop($headerLines),true), 
		);
		
		list($reaction["reactants"],$reaction["products"],$reagents)=colSplit($countsLine,array(3,3));
		
		if ($reagents>0) {
			// reorder, add to reactants
			$reagent_molecules=array_splice($molecules,1+$reaction["reactants"]+$reaction["products"]);
			array_splice($molecules,1+$reaction["reactants"],0,$reagent_molecules);
			$reaction["reactants"]+=$reagents;
		}
		
		//~ preg_match("/(?im)^([\s\d]{3})([\s\d]{3})$/",$molecules[0],$assignment);
		$reaction["reactants"]+=0;
		$reaction["products"]+=0;
		
		// einzelne Molfiles verarbeiten
		for ($a=1;$a<count($molecules);$a++) {
			$molecule=readMolfile($molecules[$a],$paramHash);
			if ($molecule["atoms"]) {
				// atoms present
				$reaction["molecules"][]=$molecule;
			}
			elseif ($a<=$reaction["reactants"]) {
				$reaction["reactants"]--;
			}
			else {
				$reaction["products"]--;
			}
		}
		//~ var_dump($molecules);var_dump($reaction);//die();
		
		// combine Molfiles
		
		if ($paramHash["combineParts"]) { // 64
			for ($c=0;$c<2;$c++) {
				if ($c==0) { // Reactants
					$start=0;
					$end=$reaction["reactants"];
				}
				elseif ($c==1) { // Products
					$start=$reaction["reactants"];
					$end=$reaction["reactants"]+$reaction["products"];
				}
				// is charge balance possible?
				$total_charge=0;
				for ($a=$start;$a<$end;$a++) {
					$total_charge+=$reaction["molecules"][$a]["total_charge"];
				}
				if ($total_charge!=0) {
					continue;
				}
				
				
				// combine consecutive fragments to neutral salts
				$ion_list=array();
				$ion_chg=0;
				for ($a=$start;$a<$end;$a++) {
					if ($reaction["molecules"][$a]["total_charge"]!=0) {
						$ion_chg+=$reaction["molecules"][$a]["total_charge"];
						$ion_list[]=$a; // nachfolgende moleküle mit ladung anfügen, bis ladung wieder 0
					}
					if ($ion_chg==0) {
						if (count($ion_list)>1) { // teile kombinieren
							for ($b=1;$b<count($ion_list);$b++) {
								combineMolecules( $reaction["molecules"][ $ion_list[0] ], $reaction["molecules"][ $ion_list[$b] ]);
								unset($reaction["molecules"][ $ion_list[$b] ]);
								if ($c==0) { // Reactants
									$reaction["reactants"]--;
								}
								elseif ($c==1) { // Products
									$reaction["products"]--;
								}
							}
							procMolecule($reaction["molecules"][ $ion_list[0] ],$paramHash);
						}
						$ion_list=array();
					}
				}
				if (count($reaction["molecules"])) {
					$reaction["molecules"]=array_values($reaction["molecules"]); // compact indices
				}
			}
		}
		
		// have avarage bond lengths equal here already
		normaliseReaction($reaction);
	}
	
	for ($a=0;$a<count($reaction["molecules"]);$a++) {
		if ($a==0) {
			// do nothing
		}
		elseif ($a==$reaction["reactants"]) {
			$reaction["smiles"].=">>";
			$reaction["smiles_stereo"].=">>";
		}
		else {
			$reaction["smiles"].=".";
			$reaction["smiles_stereo"].=".";
		}
		$reaction["smiles"].=$reaction["molecules"][$a]["smiles"];
		$reaction["smiles_stereo"].=$reaction["molecules"][$a]["smiles_stereo"];
	}
	//~ print_r($reaction);die();
	return $reaction;
}

function checkSettings(& $paramHash, $mode="mol") {
	// setDefaults
	switch ($mode) {
	case "sf":
		return;
	break;
	case "su":
		$paramHash["ignoreBonds"]=defFalse($paramHash["ignoreBonds"]);
		return;
	break;
	case "rxn":
		$paramHash["combineParts"]=defTrue($paramHash["combineParts"]);
	break;
	}
	if ($paramHash["quickMode"]) {
		// strip expl Hs
		$paramHash["stripHs"]=defFalse($paramHash["stripHs"]);
		
		// quirks mode
		$paramHash["quirks"]=defFalse($paramHash["quirks"]);
		
		// for structure search: disconnect metal bonds and put charges
		$paramHash["forStructureSearch"]=defFalse($paramHash["forStructureSearch"]);
		
		// alle Bindungen Einfachbindungen
		$paramHash["ignoreBonds"]=defFalse($paramHash["ignoreBonds"]);
		
		// alle Atome any (*)
		$paramHash["ignoreAtoms"]=defFalse($paramHash["ignoreAtoms"]);
		
		// do not reduce size
		$paramHash["debug"]=defFalse($paramHash["debug"]);
	}
	else {
		// strip expl Hs
		$paramHash["stripHs"]=defFalse($paramHash["stripHs"]);
		
		// quirks mode
		$paramHash["quirks"]=defFalse($paramHash["quirks"]);
		
		// for structure search: disconnect metal bonds and put charges
		$paramHash["forStructureSearch"]=defFalse($paramHash["forStructureSearch"]);
		
		// alle Bindungen Einfachbindungen
		$paramHash["ignoreBonds"]=defFalse($paramHash["ignoreBonds"]);
		
		// alle Atome any (*)
		$paramHash["ignoreAtoms"]=defFalse($paramHash["ignoreAtoms"]);
		
		// do not reduce size
		$paramHash["debug"]=defFalse($paramHash["debug"]);
	}
	//~ if ($paramHash["ignoreBonds"] || $paramHash["ignoreAtoms"]) {
		//~ $paramHash["smiles"]=false;
	//~ }
	//~ if ($paramHash["smiles"]) {
		//~ $paramHash["atomRanks"]=true;
		//~ $paramHash[RINGS]=true;
	//~ }
}

function getTemplateLine(& $molecule,$template_name,$template_shortcuts,$oldLine="") {
	$attAtom=0;
	if ($oldLine!=="") {
		list(,,,$attAtom)=spaceSplit($oldLine);
	}
	$molecule["att_atom"]=$attAtom;
	return "M  ATT  ".leftSpace(3,3)." ".leftSpace($attAtom,3)." ".$template_shortcuts."   ".$template_name;
}

function moleculeAddTemplateLine(& $molecule,$template_name,$template_shortcuts) {
	$found=false;
	for ($a=count($molecule["endlines"])-1;$a>=0;$a--) {
		$addline=spaceSplit($molecule["endlines"][$a]);
		if ($addline[1]=="ATT") {
			if ($found) {
				array_splice($molecule["endlines"],$a,1);
			}
			else {
				// update
				$molecule["endlines"][$a]=getTemplateLine($molecule,$template_name,$template_shortcuts,$molecule["endlines"][$a]);
				$found=true;
			}
		}
	}
	if (!$found) {
		$molecule["endlines"][]=getTemplateLine($molecule,$template_name,$template_shortcuts);
	}
	
}

function readMolfile($molfileStr,$paramHash=array()) {
	global $pse,$valencies,$atMasses,$specMasses,$transition_metals;
	if (strlen($molfileStr)==0) {
		return array();
	}
	checkSettings($paramHash,"mol");
	
	// options: 1=compute mw 2=getAtomRanks 4=getRings 8=calculate fingerprints, 16+4+2 SMILES, 32=fast mode (not fully implemented) 64=combine parts (rxnfile only), 128=standard conform mode (otherwise JCAMP quirks!!), 256=nur Gerüst (alle Bindungen werden zu Einfachbindungen, für Gerüstsuche)

	// liest Molfile und gibt Molekül-"Objekt" zurück
	// options zum Ausschalten von Features
	$molfileStr=removePipes($molfileStr); // schneidet auch CR/LF weg!! kein Trim, sonst stimmt ggf die Zahl der Kopfzeilen nicht mehr
	
	if (startswith($molfileStr,"\$RXN\n")) { // in case of reaction return 1st molecule
		$molecule=readRxnfile($molfileStr,$paramHash);
		return $molecule["molecules"][0];
	}
	
	$lines=explode("\n",$molfileStr);
	if (count($lines)<5) {
		return array();
	}
	
	// skip header
	for ($b=3;$b<count($lines);$b++) { // skip 1st 3 lines
		if (endswith($lines[$b],"V2000")) {
			break;
		}
	}
	if ($b==count($lines)) { // crippled ACD format
		$b=2;
		// die($lines[0]."X".$lines[1]."X".$lines[2]."X".$lines[3]);
	}
	
	$molecule=array(
		"atoms" => array(), 
		BONDS => array(), 
		"iProt" => 0, 
		"eProt" => 0, 
		//~ "data" => json_decode($lines[$b-1],true), 
	);
	
	// Atome einlesen
	// $molinfo=spaceSplit($lines[$b]);
	$molinfo=colSplit($lines[$b],array(3,3));
	$cumuleneStat=array(); // Index=atomnummer (0..n-1), wert: zahl der doppelbindungen
	for ($a=1;$a<=$molinfo[0];$a++) {
		if ($paramHash["quirks"]) { // 128
			$atoms=spaceSplit($lines[$a+$b]);
		}
		else {
			$atoms=colSplit($lines[$a+$b],array(10,	10,	10,	1,	3,	2,	3,	3,	3,	3,	3,	3,	3,	3,	3,	3));
			//							x	y	z	spc	sym	iso	chg	ster	H	ster	val	H0	typ	num	inv	ex
		}
		$charge=intval($atoms[6]);
		if ($charge!=0) {
			$charge=4-$charge;
		}
		$atoms[4]=ucfirst(strtolower($atoms[4]));
		$newAtom=array(
			"x" => floatval($atoms[0]), 
			"y" => floatval($atoms[1]), 
			"z" => floatval($atoms[2]), 
			CHARGE => $charge, 
			ORIG_CHARGE => $charge, 
			BONDS => 0, 
			NEIGHBOURS => array(), 
			RINGS => array(), 
			//~ "e_h" => 0, 
		);
		if (!$paramHash["ignoreAtoms"]) {
			if ($atoms[10]>=15) { // valency
				$newAtom[VALENCY]=0;
			}
			elseif ($atoms[10]>0) {
				$newAtom[VALENCY]=$atoms[10];
			}
			else { // auto
				$newAtom[VALENCY]=$valencies[ $atoms[4] ];
			}
			
			$newAtom[ATOMIC_SYMBOL]=$atoms[4];
			$newAtom[ATOMIC_NUMBER]=$pse[$atoms[4]];
			if (isset($specMasses[ $newAtom[ATOMIC_SYMBOL] ])) { // special settings for D,T
				$newAtom[MASS]=$specMasses[ $newAtom[ATOMIC_SYMBOL] ];
				$newAtom[IS_ISOTOPE]=true;
				$newAtom["hideMass"]=true;
			}
			else {
				$newAtom[MASS]=$atMasses[$newAtom[ATOMIC_NUMBER]-1];
				if ($atoms[5]!=0) { // overridden by ISO lines
					$newAtom[MASS]=round($newAtom[MASS])+$atoms[5];
					$newAtom[IS_ISOTOPE]=true;
				}
			}
			// has_transition_metal disables automatic arrangement of fragments
			if ($newAtom[ATOMIC_SYMBOL]=="H") {
				$molecule["has_explicit_h"]=true;
			}
			elseif (!$molecule["has_transition_metal"] && in_array($newAtom[ATOMIC_NUMBER],$transition_metals)) {
				$molecule["has_transition_metal"]=true;
			}
		}
		else {
			$newAtom[ATOMIC_SYMBOL]="*";
		}
		$molecule["atoms"][]=$newAtom;
		// $molecule["emp_formula"][ $atoms[3] ]++;
		$cumuleneStat[]=0; // show C in cumulene structures which would look like a long double bond otherwise
	}
	$b+=$a-1;
	// Bindungen einlesen
	for ($a=1;$a<=$molinfo[1];$a++) {
		if ($paramHash["quirks"]) { // 128
			$bonds=spaceSplit($lines[$a+$b]);
		}
		else {
			$bonds=colSplit($lines[$a+$b],array(3,	3,	3,	3,	3,	3,	3));
			//							a1	a2	typ	ster	unu	top	reac
		}
		$a1=$bonds[0]-1;
		$a2=$bonds[1]-1;
		if ($a1==$a2) { // no bonds with two times the same atom
			continue;
		}
		if (!$paramHash["ignoreBonds"]) {
			switch ($bonds[2]) {
			case 1:
				$bOrder=1;
			break;
			case 2:
				$bOrder=2;
				$cumuleneStat[$a1]++;
				$cumuleneStat[$a2]++;
				//~ if ($cumuleneStat[$a1]>=2) {
					//~ $molecule["atoms"][$a1][IS_CUMULENE]=true; // C will be drawed
				//~ }
				//~ if ($cumuleneStat[$a2]>=2) {
					//~ $molecule["atoms"][$a2][IS_CUMULENE]=true; // C will be drawed
				//~ }
			break;
			case 3:
				$bOrder=3;
				$cumuleneStat[$a1]++;
				$cumuleneStat[$a2]++;
				//~ if ($cumuleneStat[$a1]>=2) {
					//~ $molecule["atoms"][$a1][IS_CUMULENE]=true; // C will be drawed
				//~ }
				//~ if ($cumuleneStat[$a2]>=2) {
					//~ $molecule["atoms"][$a2][IS_CUMULENE]=true; // C will be drawed
				//~ }
			break;
			case 4:
				$bOrder=1.5;
			break;
			case 8:
				$bOrder=0;
			break;
			default:
				$bOrder=0;
			}
			
			if ($bOrder==1) {
				$bStereo=$bonds[3]; // 0: kein Stereo, 1: Up, 4: Schlange, 6: Down
			}
			else {
				$bStereo=0;
			}
		}
		else {
			$bOrder=1;
			$bStereo=0;
		}
    	// if ($molecule["atoms"][$bonds[$a][1]-1][ATOMIC_SYMBOL]=="H" || $molecule["atoms"][$bonds[$a][2]-1][ATOMIC_SYMBOL]=="H") // no explicit Hs
    	// 	continue;
		$bond_count=count($molecule[BONDS]);
		$newBond=array(
			BOND_NO => $bond_count, // idx
			ATOM1 => $a1, 
			ATOM2 => $a2, 
			BOND_ORDER => $bOrder, 
			ORIG_BOND_ORDER => $bOrder, 
			STEREO => $bStereo, 
		);
		$molecule[BONDS][$bond_count]=$newBond;
		if ($bOrder>0) {
			$molecule["atoms"][$a1][NEIGHBOURS][]=$a2;
			$molecule["atoms"][$a2][NEIGHBOURS][]=$a1;
		}
		$molecule["atoms"][$a1][BONDS]+=$bOrder; // Bindungen berücksichtigen
		$molecule["atoms"][$a1][ORIG_BONDS]+=$bOrder; // Bindungen berücksichtigen
		$molecule["atoms"][$a2][BONDS]+=$bOrder;
		$molecule["atoms"][$a2][ORIG_BONDS]+=$bOrder;
		/* $molecule["atoms"][$a1][IMPLICIT_H]-=$bOrder; // Bindungen berücksichtigen
		$molecule["atoms"][$a2][IMPLICIT_H]-=$bOrder; */
		$molecule["bondsFromNeighbours"][$a1][$a2]=& $molecule[BONDS][$bond_count]; // save cpu time, waste a bit of mem
		$molecule["bondsFromNeighbours"][$a2][$a1]=& $molecule[BONDS][$bond_count];
	}
	$b+=$a-1;
	
	$groups=array();
	
	for ($a=1;$a<count($lines);$a++) {
		$addline=spaceSplit($lines[$a+$b]);
		switch ($addline[1]) {
		case "CHG":
			// Ladungszeile(n) am Ende einlesen
			if ($addline[2]>0) for ($c=0;$c<$addline[2];$c++) {
				$charge=intval($addline[2*$c+4]);
				$atom_no=$addline[2*$c+3]-1;
				$molecule["atoms"][$atom_no][CHARGE]=$charge;
				$molecule["atoms"][$atom_no][ORIG_CHARGE]=$charge;
			}
		break;
		case "ISO":
			// Ladungszeile(n) am Ende einlesen
			if ($addline[2]>0) for ($c=0;$c<$addline[2];$c++) {
				$atom_no=$addline[2*$c+3]-1;
				$mass=intval($addline[2*$c+4]);
				$molecule["atoms"][$atom_no][MASS]=$mass;
				$molecule["atoms"][$atom_no][IS_ISOTOPE]=true;
			}
		break;
		case "RAD":
			// Ladungszeile(n) am Ende einlesen
			if ($addline[2]>0) for ($c=0;$c<$addline[2];$c++) {
				$radical=intval($addline[2*$c+4]);
				$atom_no=$addline[2*$c+3]-1;
				$molecule["atoms"][$atom_no][ORIG_RADICAL]=$radical;
				$molecule["atoms"][$atom_no]["r"]=$radical;
			}
		break;
		case "ZZC":
			// Assignmentzeile(n) am Ende einlesen
			$molecule["atoms"][ $addline[2]-1 ]["assign"]=$addline[3];
		break;
		case "ATT":
			// Anknüpfungspunkt für Template
			$molecule["att_atom"]=$addline[3];
			$molecule["endlines"][]=$lines[$a+$b];
		break;
		case "STY":
			// $addline[2] is no of entries
			$max=3+2*$addline[2];
			if ($addline[2]>0) for ($c=3;$c<$max;$c+=2) {
				$group_no=$addline[$c];
				$type=strtoupper($addline[$c+1]);
				$groups[$group_no][GROUP_TYPE]=$type;
				$molecule[GROUPS][$group_no][GROUP_TYPE]=$type;
				switch ($type) {
				case "SUP":
					
				break;
				case "MUL": // multi, hide additional atoms
					
				break;
				case "SRU": // polymer, no sum formula or mw
					$molecule["has_polymer"]=true;
					$groups[$group_no][EXPAND]=true;
				break;
				}
			}
			// was gibt es?
			$molecule["endlines"][]=$lines[$a+$b];
		break;
		case "SCN": // ht etc
			// $addline[2] is no of entries
			$max=3+2*$addline[2];
			if ($addline[2]>0) for ($c=3;$c<$max;$c+=2) {
				$group_no=$addline[$c];
				$molecule[GROUPS][$group_no][GROUP_TEXT2]=$addline[$c+1];
			}
			$molecule["endlines"][]=$lines[$a+$b];
		break;
		case "SDI":
			// corners of brackets
			$group_no=$addline[2];
			$molecule[GROUPS][$group_no][BRACKETS][]=array_slice($addline,4,4);
			$molecule["endlines"][]=$lines[$a+$b];
		break;
		case "SAL": // atom list
			$group_no=$addline[2];
			for ($d=4;$d<count($addline);$d++) {
				$atom_no=$addline[$d]-1;
				$groups[$group_no]["atoms"][]=$atom_no;
			}
			$molecule["endlines"][]=$lines[$a+$b];
		break;
		case "SPA": // atoms to display
			$group_no=$addline[2];
			for ($d=4;$d<count($addline);$d++) {
				$atom_no=$addline[$d]-1;
				$groups[$group_no]["repres_atoms"][]=$atom_no;
			}
			$molecule["endlines"][]=$lines[$a+$b];
		break;
		case "SBL": // Bindung(en) der Anknüpfung, zZt nur 1 unterstützt, sonst EXP
			$group_no=$addline[2];
			for ($d=4;$d<count($addline);$d++) {
				$bond_no=$addline[$d]-1;
				$groups[$group_no]["repres_atoms"][]=$molecule[BONDS][ $bond_no ][ATOM1];
				$groups[$group_no]["repres_atoms"][]=$molecule[BONDS][ $bond_no ][ATOM2];
			}
			$molecule["endlines"][]=$lines[$a+$b];
		break;
		case "SDS": // show expanded, i.e. draw normally
			$data=substr($lines[$a+$b],13);
			$data=str_split($data,3);
			for ($d=0;$d<count($data);$d++) {
				$group_no=trim($data[$d]);
				$groups[$group_no][EXPAND]=true;
			}
			$molecule["endlines"][]=$lines[$a+$b];
		break;
		case "SMT": // group label
			$group_no=$addline[2];
			$groups[$group_no][GROUP_TEXT]=$addline[3];
			$molecule[GROUPS][$group_no][GROUP_TEXT]=$addline[3];
			$molecule["endlines"][]=$lines[$a+$b];
		break;
		case "END":
		break 2;
		default:
			// preserve unknown data
			$molecule["endlines"][]=$lines[$a+$b];
		}
	}
	//~ print_r($groups);
	//~ print_r($molecule[GROUPS]);
	// handle groups
	if (is_array($groups)) foreach ($groups as $group_no => $group) { // indices usually start from 1...
		if ($group[EXPAND]) {
			continue;
		}
		
		// reduce repres_atoms to the ones in the group
		$group["repres_atoms"]=arr_intersect($group["repres_atoms"],$group["atoms"]);
		$molecule[GROUPS][$group_no]["repres_atoms"]=$group["repres_atoms"];
		
		// calc middle of atoms
		$repres_atoms_count=count($group["repres_atoms"]);
		if ($group[GROUP_TYPE]=="SUP") {
			if ($repres_atoms_count) {
				$x_sum=0;
				$y_sum=0;
				for ($e=0;$e<$repres_atoms_count;$e++) {
					$atom_no=$group["repres_atoms"][$e];
					$x_sum+=$molecule["atoms"][$atom_no]["x"];
					$y_sum+=$molecule["atoms"][$atom_no]["y"];
				}
				$x_sum/=$repres_atoms_count;
				$y_sum/=$repres_atoms_count;
			} else {
				$atoms_count=count($group["atoms"]);
				if ($atoms_count) {
					// no repres_atoms, calc center
					$x_sum=0;
					$y_sum=0;
					for ($e=0;$e<$atoms_count;$e++) {
						$atom_no=$group["atoms"][$e];
						$x_sum+=$molecule["atoms"][$atom_no]["x"];
						$y_sum+=$molecule["atoms"][$atom_no]["y"];
					}
					$x_sum/=$atoms_count;
					$y_sum/=$atoms_count;
					$molecule[GROUPS][$group_no]["cx"]=$x_sum;
					$molecule[GROUPS][$group_no]["cy"]=$y_sum;
				}
			}
		}
		
		for ($e=0;$e<count($group["atoms"]);$e++) {
			$atom_no=$group["atoms"][$e];
			
			if (in_array($atom_no,$group["repres_atoms"])) {
				// show abbrev
				if ($group[GROUP_TYPE]=="SUP") {
					$molecule["atoms"][$atom_no][GROUP_TEXT]=$group[GROUP_TEXT];
					
					// only one representing atom
					unset($group[GROUP_TEXT]);
					
					// transform position of atoms for bond backbone
					$molecule["atoms"][$atom_no]["t_x"]=$x_sum-$molecule["atoms"][$atom_no]["x"];
					$molecule["atoms"][$atom_no]["t_y"]=$y_sum-$molecule["atoms"][$atom_no]["y"];
				}
				
				$molecule["atoms"][$atom_no][HIDE]=false;
			}
			elseif (!isset($molecule["atoms"][$atom_no][HIDE])) {
				// hide atoms
				$molecule["atoms"][$atom_no][HIDE]=true;
			}
		}
	}
	//~ print_r($groups);
	//~ print_r($molecule[GROUPS]);die();
	
	// make polar nitro etc better searchable by making double bonds
	for ($a=0;$a<count($molecule[BONDS]);$a++) {
		$bond=& $molecule[BONDS][$a];
		$a1=$bond[ATOM1];
		$a2=$bond[ATOM2];
		
		/* expl H
		if ($molecule["atoms"][$a1][ATOMIC_SYMBOL]=="H") {
			$molecule["atoms"][$a2]["e_h"]++;
		}
		if ($molecule["atoms"][$a2][ATOMIC_SYMBOL]=="H") {
			$molecule["atoms"][$a1]["e_h"]++;
		}*/
		switch ($bond[ORIG_BOND_ORDER]) {
		case 1:
			if (($molecule["atoms"][$a1][CHARGE]==1 && $molecule["atoms"][$a2][CHARGE]==-1) || ($molecule["atoms"][$a1][CHARGE]==-1 && $molecule["atoms"][$a2][CHARGE]==1)) {
				$molecule[BONDS][$a][BOND_ORDER]=2;
				$molecule["atoms"][$a1][CHARGE]=0;
				$molecule["atoms"][$a2][CHARGE]=0;
				$molecule["atoms"][$a1][BONDS]++;
				$molecule["atoms"][$a2][BONDS]++;
			}
		break;
		case 2:
			if (($molecule["atoms"][$a1][CHARGE]==1 && $molecule["atoms"][$a2][CHARGE]==-1) || ($molecule["atoms"][$a1][CHARGE]==-1 && $molecule["atoms"][$a2][CHARGE]==1)) {
				$molecule[BONDS][$a][BOND_ORDER]=3;
				$molecule["atoms"][$a1][CHARGE]=0;
				$molecule["atoms"][$a2][CHARGE]=0;
				$molecule["atoms"][$a1][BONDS]++;
				$molecule["atoms"][$a2][BONDS]++;
			}
		break;
		}
	}
	
	$total_charge=0;
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		// iHyd nehmen,Bindungen abziehen
		$molecule["atoms"][$a][ORIG_IMPLICIT_H]=getImplHyd(
			$molecule["atoms"][$a][VALENCY], 
			$molecule["atoms"][$a][ORIG_BONDS], // used to be BONDS, gives errors on H-NO2
			$molecule["atoms"][$a][ORIG_CHARGE], 
			$molecule["atoms"][$a][ORIG_RADICAL]
		);
		$molecule["atoms"][$a][IMPLICIT_H]=$molecule["atoms"][$a][ORIG_IMPLICIT_H];
		$molecule["emp_formula"]["H"]+=$molecule["atoms"][$a][IMPLICIT_H];
		
		// calc total charge
		$total_charge+=$molecule["atoms"][$a][CHARGE];
		
		// detect cumulenes
		if ($cumuleneStat[$a]>=2) {
			$molecule["atoms"][$a][IS_CUMULENE]=true; // C will be drawed
		}
	}
	$molecule["total_charge"]=$total_charge;
	
	procMolecule($molecule,$paramHash);
	
	if (!$paramHash["debug"]) {
		condenseMolecule($molecule);
	}
	
	return $molecule;
}

function condenseMolecule(& $molecule) {
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		
		// save as strings to avoid long numbers
		$molecule["atoms"][$a]["x"].="";
		$molecule["atoms"][$a]["y"].="";
		$molecule["atoms"][$a]["z"].="";
		$molecule["atoms"][$a][MASS].="";
		
		// strip ranks
		unset($molecule["atoms"][$a]["t_x"]);
		unset($molecule["atoms"][$a]["t_y"]);
		unset($molecule["atoms"][$a]["hasMetalNeighbour"]);
		unset($molecule["atoms"][$a][INVARIANT]);
		unset($molecule["atoms"][$a][RANKS]);
		unset($molecule["atoms"][$a]["SMimplH"]);
		unset($molecule["atoms"][$a]["SMchirStereo"]);
		unset($molecule["atoms"][$a]["SMdblStereo"]);
		unset($molecule["atoms"][$a]["SMdblPartner"]);
		unset($molecule["atoms"][$a]["SMdblHighAtom"]);
		//~ unset($molecule["atoms"][$a][HIDE]);
		//~ unset($molecule["atoms"][$a][GROUP_TEXT]);
	}
	for ($a=0;$a<count($molecule[BONDS]);$a++) {
		unset($molecule[BONDS][$a]["SMdone"]);
		unset($molecule[BONDS][$a]["SMbondSkipped"]);
		unset($molecule[BONDS][$a]["ring"]);
		//~ unset($molecule[BONDS][$a][HIDE]);
	}
	for ($a=0;$a<count($molecule[RINGS]);$a++) {
		unset($molecule[RINGS][$a]["dontReplaceBonds"]);
	}
}

function serializeMolecule($molecule) { // nicht byRef, sonst hängt es von der Reihenfolge ab, ob SMILES und Fingerprints gespeichert werden
	unset($molecule["orig_parts"]);
	unset($molecule["fingerprints"]);
	unset($molecule["smiles_stereo"]);
	unset($molecule["smiles"]);
	return gzcompress(serialize($molecule));
}

/* function combineOrigParts(& $molecule,$p1,$p2) {
	if (!isset($molecule["orig_parts"][$p1]) || !isset($molecule["orig_parts"][$p2])) { // schon wegkombiniert
		return;
	}
	$molecule["orig_parts"][$p1]["atoms"]=array_merge($molecule["orig_parts"][$p1]["atoms"],$molecule["orig_parts"][$p2]["atoms"]);
	unset($molecule["orig_parts"][$p2]);
}*/

function setOrigParts(& $molecule,$part=0,$atom_no=0) {
	if (isset($molecule["atoms"][$atom_no][ORIG_PART])) {
		return;
	}
	$molecule["atoms"][$atom_no][ORIG_PART]=$part;
	$molecule["orig_parts"][$part]["atoms"][]=$atom_no;
	for ($b=0;$b<count($molecule["atoms"][$atom_no][NEIGHBOURS]);$b++) {
		setOrigParts($molecule,$part,$molecule["atoms"][$atom_no][NEIGHBOURS][$b]);
	}
}

function procMolecule(& $molecule,$paramHash=array()) {
	global $atMasses;
	
	checkSettings($paramHash,"mol");
	$part=0;
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		if (!isset($molecule["atoms"][$a][ORIG_PART])) {
			setOrigParts($molecule,$part,$a);
			$part++;
		}
	}
	
	if ($molecule["has_polymer"]) {
		return;
	}
	
	// get orig parts
	// metallorganische Bindungen auftrennen
	transformForSearchDisconnect($molecule);
	//~ print_r($molecule);die();
	
	$molecule["emp_formula"]=array();
	$molecule["mw"]=0;
	$molecule["mw_noH"]=0;
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		$sym=$molecule["atoms"][$a][ATOMIC_SYMBOL];
		$molecule["emp_formula"][$sym]++;
		$molecule["emp_formula"]["H"]+=$molecule["atoms"][$a][ORIG_IMPLICIT_H];
		$molecule["mw"]+=$molecule["atoms"][$a][MASS]+$molecule["atoms"][$a][ORIG_IMPLICIT_H]*$atMasses[0];
		if ($sym!="H") {
			$molecule["mw_noH"]+=$molecule["atoms"][$a][MASS];
		}
	}
	
	$molecule["emp_formula_string"]=getEmpFormula($molecule);
	$molecule["emp_formula_string_sort"]=getEmpFormula($molecule,emp_formula_sort_fill); // for sorting by emp_formula
	
	if (count($molecule["atoms"])) {

		// expl Hs markieren
		markExHs($molecule);
		
		// Ringe
		$molecule[RINGS]=array();
		// Atome durchgehen und höchstes unbenutztes Atom suchen
		for ($a=0;$a<count($molecule["atoms"]);$a++) { // maxatom eines Parts suchen, der noch nicht durch getRings erfaßt ist
			if (!isset($molecule["atoms"][$a][PART])) {
				// neuer Part
				$molecule["parts"][]=array();
				findRings($molecule,array($a));
			}
		}
		
		for ($a=0;$a<count($molecule["atoms"]);$a++) {
			getHybridisation($molecule,$a);
		}
		
		for ($a=0;$a<count($molecule[RINGS]);$a++) {
			procRing($molecule,$a);
		}
		
		// Allylsysteme (nur Anion) mit 1.5-Bindungen versehen
		//~ print_r($molecule);die();
		aromatizeAllyl($molecule);
		//~ print_r($molecule);die();
		
		// Reihenfolge
		getAtomRanks($molecule,array("pass" => 1));
		
		// Ladung und 2/1-Bindungen bei Allylsystemen wiederherstellen, dadurch werden diese Systeme unique
		dearomatizeAllyl($molecule);
		
		// noch eine Runde Ranks rechnen, um echte Allylsysteme zu berücksichtigen
		markExHs($molecule); // nb may have changed meanwhile
		getAtomRanks($molecule,array("pass" => 2));
		
		// Maxatom berechnen
		for ($a=0;$a<count($molecule["parts"]);$a++) {
			unset($maxatom);
			unset($minatom);
			for ($b=0;$b<count($molecule["parts"][$a]["atoms"]);$b++) {
				$test_atom=$molecule["parts"][$a]["atoms"][$b];
				//~ if ($molecule["atoms"][$test_atom][ATOMIC_SYMBOL]=="H") {
					//~ continue;
				//~ }
				if (!isset($maxatom) || SMisHigherThan($molecule["atoms"][$test_atom],$molecule["atoms"][$maxatom])) {
					$maxatom=$test_atom;
				}
				if (!isset($minatom) || SMisHigherThan($molecule["atoms"][$minatom],$molecule["atoms"][$test_atom])) {
					$minatom=$test_atom;
				}
			}
			$molecule["parts"][$a]["maxatom"]=$maxatom;
			$molecule["parts"][$a]["minatom"]=$minatom;
		}
		
		// generate SMILES, benötigt vorhandene explizite Hs ggf für chirale Atome
		list($molecule["smiles"],$molecule["smiles_stereo"])=moleculeGetSMILES($molecule);
		
		// Fingerprints, acide Protonen werden vorher bei der SMILES-Erstellung erfaßt, deshalb ERST HIER
		calculateFingerprint($molecule,$paramHash);
		
		// strip explicit Hs, erst hier, damit wir die atom ranks haben für chirale Hs
		if ($paramHash["stripHs"]) {
			for ($a=count($molecule["atoms"])-1;$a>=0;$a--) { // count down to avoid probs with removed atoms
				if (isExplH($molecule,$a) && !$molecule["atoms"][$a]["stereoH"]) {
					$anything_removed=true;
					for ($b=0;$b<count($molecule["atoms"][$a][NEIGHBOURS]);$b++) {
						// increase impl Hs by one
						$neighbour_no=& $molecule["atoms"][$a][NEIGHBOURS][$b];
						$molecule["atoms"][$neighbour_no][ORIG_IMPLICIT_H]++;
						$molecule["atoms"][$neighbour_no][IMPLICIT_H]++;
						$molecule["atoms"][$neighbour_no][H_NEIGHBOURS]--;
					}
					
					// remove atom
					array_splice($molecule["atoms"],$a,1);
					
					// remove bonds
					for ($c=count($molecule[BONDS])-1;$c>=0;$c--) {
						if ($molecule[BONDS][$c][ATOM1]==$a || $molecule[BONDS][$c][ATOM2]==$a) { // bindung löschen
							array_splice($molecule[BONDS],$c,1);
						}
						else { // nachfolgende nummern anpassen
							if ($molecule[BONDS][$c][ATOM1]>$a) {
								$molecule[BONDS][$c][ATOM1]--;
							}
							if ($molecule[BONDS][$c][ATOM2]>$a) {
								$molecule[BONDS][$c][ATOM2]--;
							}
						}
					}
					
					// fix max_atom
					for ($b=0;$b<count($molecule["parts"]);$b++) {
						if ($molecule["parts"][$b]["maxatom"]>$a) {
							$molecule["parts"][$b]["maxatom"]--;
						}
					}
					
					// fix neighbours, remove H and fix numbers of following atoms
					for ($b=0;$b<count($molecule["atoms"]);$b++) {
						for ($d=count($molecule["atoms"][$b][NEIGHBOURS])-1;$d>=0;$d--) {
							$test_atom=& $molecule["atoms"][$b][NEIGHBOURS][$d];
							if ($test_atom==$a) { // remove from neighbours
								array_splice($molecule["atoms"][$b][NEIGHBOURS],$d,1);
							}
							elseif ($test_atom>$a) { // nachfolgende nummern anpassen
								$molecule["atoms"][$b][NEIGHBOURS][$d]--;
							}
						}
					}
					
					// fix ring members, $a should never be in a ring itself
					for ($b=0;$b<count($molecule[RINGS]);$b++) {
						for ($c=0;$c<count($molecule[RINGS][$b]["atoms"]);$c++) {
							$test_atom=$molecule[RINGS][$b]["atoms"][$c];
							if ($test_atom>$a) {
								$molecule[RINGS][$b]["atoms"][$c]--;
							}
						}
					}
				}
			}
			if ($anything_removed) {
				// rebuild bondsFromNeighbours
				renewBondsFromNeighbours($molecule);
			}
		}
		
		/*
		// durch 0-Bindungen getrennte Teile erfassen, um abzugleichen und Geometrieoptimierung zu verhindern
		for ($a=0;$a<count($molecule[BONDS]);$a++) {
			// ist es echte Doppelbgd
			if ($molecule[BONDS][$a][BOND_ORDER]!=0) {
				continue;
			}
			
			$a1=$molecule[BONDS][$a][ATOM1];
			$a2=$molecule[BONDS][$a][ATOM2];
			
			$p1=$molecule["atoms"][$a1][PART];
			$p2=$molecule["atoms"][$a2][PART];
		}
		*/
	}
}

function getMolfileBody(& $molecule,$paramHash=array()) { // alles nach V2000
	global $atMasses;
	// atome
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		$charge=$molecule["atoms"][$a][ORIG_CHARGE];
		if ($charge==0 || $charge>3 && $charge<-3) {
			$charge=0;
		}
		else {
			$charge=4-$charge;
		}
		$delta=0;
		if ($molecule["atoms"][$a][IS_ISOTOPE]) {
			$delta=$molecule["atoms"][$a][MASS]-round($atMasses[$molecule["atoms"][$a][ATOMIC_NUMBER]-1]);
			if ($delta<-3 || $delta>4) {
				$delta=0;
			}
		}
		$retval.=leftSpace(fixMolfileCoord($molecule["atoms"][$a]["x"]),10).
			leftSpace(fixMolfileCoord($molecule["atoms"][$a]["y"]),10).
			leftSpace(fixMolfileCoord($molecule["atoms"][$a]["z"]),10)." ".
			rightSpace($molecule["atoms"][$a][ATOMIC_SYMBOL],3).
			leftSpace(round($delta),2).
			leftSpace(round($charge),3).
			multStr(leftSpace("0",3),10).
			"\n"; // and the rest here
		// sss hhh bbb vvv HHH rrr iii mmm nnn eee
	}
	// bindungen
	for ($a=0;$a<count($molecule[BONDS]);$a++) {
		if ($molecule[BONDS][$a][ATOM1]!=$molecule[BONDS][$a][ATOM2]) { // filter false entries
			$retval.=leftSpace($molecule[BONDS][$a][ATOM1]+1,3).
				leftSpace($molecule[BONDS][$a][ATOM2]+1,3).
				leftSpace($molecule[BONDS][$a][ORIG_BOND_ORDER]+0,3).
				leftSpace($molecule[BONDS][$a][STEREO]+0,3).
				multStr(leftSpace("0",3),3).
				"\n";
		}
	}
	// additional stuff here like M ZZG, M ISO, M RAD
	for ($a=0;$a<count($molecule["atoms"]);$a++) { // Koordinaten zu Ganzzahlen machen
		// M CHG
		$charge=$molecule["atoms"][$a][ORIG_CHARGE];
		if ($charge!=0) {
			$retval.="M  CHG".leftSpace("1",3)." ".leftSpace($a+1,3)." ".leftSpace(round($charge),3)."\n";
		}
		$radical=$molecule["atoms"][$a][ORIG_RADICAL];
		if ($radical!=0) {
			$retval.="M  RAD".leftSpace("1",3)." ".leftSpace($a+1,3)." ".leftSpace(round($radical),3)."\n";
		}
		if ($molecule["atoms"][$a][IS_ISOTOPE]) {
			$retval.="M  ISO".leftSpace("1",3)." ".leftSpace($a+1,3)." ".leftSpace(round($molecule["atoms"][$a][MASS]),3)."\n";
		}
	}
	if (is_array($molecule["endlines"])) for ($a=0;$a<count($molecule["endlines"]);$a++) {
		$retval.=$molecule["endlines"][$a]."\n";
	}
	// ende
	$retval.="M  END\n";
	//~ if ($paramHash["mode"]!="rxn") {
		//~ $retval.="\n";
	//~ }
	return $retval;
}

function writeMolfile(& $molecule,$paramHash=array()) { // alles ins V2000-Format bringen
	// header
	$retval=$molecule["smiles_stereo"]."\n". // strcut($molecule["smiles"],77)."\n".
		"open enventory ".strftime("%a, %d.%m.%Y %T")."\n".
		"\n".
		//~ json_encode($molecule["data"])."\n". // store origin in header: table, db_id, pk
		leftSpace(count($molecule["atoms"]),3).leftSpace(count($molecule[BONDS]),3);
	
	if ($paramHash["mode"]!="rxn") { // required for ChemDraw
		$retval.=newHeader."\n"; // should be 0999 V2000\n";, but ACD cant deal with 999
	}
	else { // old format, compatible with ACD
		$retval.=oldHeader."\n"; // should be 0999 V2000\n";, but ACD cant deal with 999
	}
	$retval.=getMolfileBody($molecule,$paramHash);
	return $retval;
}

function writeRxnfile(& $reaction) { // nimmt molecule als Objekt oder als Molfile
	// header
	$retval="\$RXN\n\n".
		"open enventory ".strftime("%a, %d.%m.%Y %T")."\n".
		"\n".
		//~ json_encode($reaction["data"])."\n". // store origin in header: table, db_id, pk
		leftSpace($reaction["reactants"]+0,3).leftSpace($reaction["products"]+0,3)."\n";
	
	for ($a=0;$a<count($reaction["molecules"]);$a++) {
		$retval.="\$MOL\n";
		
		if (is_array($reaction["molecules"][$a])) {
			$retval.=writeMolfile($reaction["molecules"][$a],array("mode" => "rxn", ));
		}
		elseif (is_string($reaction["molecules"][$a]) && !empty($reaction["molecules"][$a]) ) {
			$retval.=$reaction["molecules"][$a];
		}
		else {
			$empty_array=array(); // byref
			$retval.=writeMolfile($empty_array,array("mode" => "rxn", ));
		}
	}
	return $retval;
}

function makeOldStyle($molfile) {
	return preg_replace("/(?ims)".newHeader."/",oldHeader,$molfile);
}

function makeNewStyle($molfile) {
	return preg_replace(array("/(?ims)".oldHeader."/","/(?ims)".bottomLine."\n/"),array(newHeader,bottomLine),$molfile);
}

?>