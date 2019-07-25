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

$valencies=array(
	"H" => 1, "D" => 1, "B" =>3, "C" => 4, "N" => 5, "O" => 6, "F" => 7, 
	"Si" => 4, "P" => 5, "S" => 6, "Cl" => 7, "Br" => 7, "I" => 7, 
	//~ "Li" => 1, "Na" => 1, "K" => 1, "Rb" => 1, "Cs" => 1, 
	//~ "Be" => 2, "Mg" => 2, "Ca" => 2, "Sr" => 2, "Ba" => 2
);

$pse=Array(
	"H"=>1, "D"=>1, "He"=>2, "Li"=>3, "Be"=>4, "B"=>5, "C"=>6, "N"=>7, "O"=>8, "F"=>9, "Ne"=>10, 
	"Na"=>11, "Mg"=>12, "Al"=>13, "Si"=>14, "P"=>15, "S"=>16, "Cl"=>17, "Ar"=>18, 
	"K"=>19, "Ca"=>20, 
		"Sc"=>21, "Ti"=>22, "V"=>23, "Cr"=>24, "Mn"=>25, "Fe"=>26, "Co"=>27, "Ni"=>28, "Cu"=>29, "Zn"=>30, 
	"Ga"=>31, "Ge"=>32, "As"=>33, "Se"=>34, "Br"=>35, "Kr"=>36, 
	"Rb"=>37, "Sr"=>38, 
		"Y"=>39, "Zr"=>40, "Nb"=>41, "Mo"=>42, "Tc"=>43, "Ru"=>44, "Rh"=>45, "Pd"=>46, "Ag"=>47, "Cd"=>48, 
	"In"=>49, "Sn"=>50, "Sb"=>51, "Te"=>52, "I"=>53, "Xe"=>54, 
	"Cs"=>55, "Ba"=>56, 
		"La"=>57, "Ce"=>58, "Pr"=>59, "Nd"=>60, "Pm"=>61, "Sm"=>62, "Eu"=>63, "Gd"=>64, "Tb"=>65, "Dy"=>66, "Ho"=>67, "Er"=>68, "Tm"=>69, "Yb"=>70, "Lu"=>71, 
		"Hf"=>72, "Ta"=>73, "W"=>74, "Re"=>75, "Os"=>76, "Ir"=>77, "Pt"=>78, "Au"=>79, "Hg"=>80, 
	"Tl"=>81, "Pb"=>82, "Bi"=>83, "Po"=>84, "At"=>85, "Rn"=>86, 
	"Fr"=>87, "Ra"=>88, 
		"Ac"=>89, "Th"=>90, "Pa"=>91, "U"=>92, "Np"=>93, "Pu"=>94, "Am"=>95, "Cm"=>96, "Bk"=>97, "Cf"=>98, "Es"=>99, "Fm"=>100, "Md"=>101, "No"=>102, "Lr"=>103, 
	"Rf" => 104, "Db" => 105, "Sg" => 106, "Bh" => 107, "Hs" => 108, "Mt" => 109, "Ds" => 110, "Rg" => 111, "Cn" => 112, 
);

$metals=array(
	"Li","Be","Na","Mg","Al","K","Ca",
	"Sc","Ti","V","Cr","Mn","Fe","Co","Ni","Cu","Zn","Ga","Rb","Sr","Y","Zr","Nb","Mo","Tc","Ru","Rh","Pd","Ag","Cd","In","Sn","Cs","Ba",
	"La","Ce","Pr","Nd","Pm","Sm","Eu","Gd","Tb","Dy","Ho","Er","Tm","Yb","Lu",
	"Hf","Ta","W","Re","Os","Ir","Pt","Au","Hg","Pb","Bi"
);

$group16el=array("O","S");

$atom_wildcards=array("%","M","X","Ln");
$halogens=array("F","Cl","Br","I");
$lanthanides=array("La","Ce","Pr","Nd","Pm","Sm","Eu","Gd","Tb","Dy","Ho","Er","Tm","Yb","Lu");

$sideOnEl=array("C","N","O","P","S"); // side-on an Metalle bindende Elemente
$ionicEl=array("F","Cl","Br","I","O","S","N","P","C"); // ionisch an Metalle bindende Elemente

$func_groups=array(
	"Ph" => "C6H5", 
	"Tr" => "C19H15", 
	"Mes" => "C9H11", 
	"Me" => "CH3", 
	"Et" => "C2H5", 
	"Bu" => "C4H9", 
	"Cy" => "C6H11", 
	"Ad" => "C10H15", 
	"Tos" => "C7H7O2S", 
	"Ts" => "C7H7O2S", 
	"Ms" => "CH3O2S", 
	"Tf" => "CF3O2S", 
	"Tol" => "C7H7", 
	"Bn" => "C7H7", 
	"Bz" => "C7H5O", 
	"Ac" => "C2H3O", 
	"Piv" => "C6H9O", 
	"Pv" => "C6H9O", 
);

$transition_metals=array(
	21,22,23,24,25,26,27,28,29,30,
	39,40,41,42,43,44,45,46,47,48,
	57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,
	89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107
);
$atMasses=array(
	1.00794,4.002602,
	6.941,9.012182,10.811,12.011,14.00674,15.9994,18.9984032,20.1797,
	22.989768,24.305,26.9811539,28.0855,30.973762,32.066,35.4527,39.948,
	39.0983,40.078,
		44.95591,47.867,50.9415,51.9961,54.93805,55.845,58.9332,58.6934,63.546,65.39,
	69.723,72.61,74.92159,78.96,79.904,83.8,
	85.4678,87.62,
		88.90585,91.224,92.90638,95.94,97.9072,101.07,102.9055,106.42,107.8682,112.411,
	114.818,118.71,121.76,127.6,126.90447,131.29,
	132.90543,137.327,
		138.9055,140.115,140.90765,144.24,144.9127,150.36,151.965,157.25,158.92534,162.5,164.93032,167.26,168.9342,173.04,174.967,
		178.49,180.9479,183.84,186.207,190.23,192.217,195.08,196.96654,200.59,
	204.3833,207.2,208.98037,208.9824,209.9871,222.0176,
	223.0197,226.0254,
		227.0278,232.0381,231.03588,238.0289,237.0482,244.0642,243.0614,247.0703,247.0703,251.0796,252.083,257.0915,256.094,259.1009,262.11,261.11,262.114,263.118,262.12
);

$specMasses=array("D" => 2.014101778, "T" => 3.016049268, );

$eneg=array(
	2.10,false, // He
	0.97,1.47,2.01,2.50,3.07,3.50,4.17,false, // Ne
	1.01,1.23,1.47,1.74,2.06,2.44,2.83,false, // Ar
	0.91,1.04,
		1.20,1.32,1.45,1.56,1.60,1.64,1.70,1.75,1.75,1.66,
	1.82,2.02,2.20,2.48,2.74,false, // Kr
	0.89,0.99,
		1.11,1.22,1.23,1.30,1.36,1.42,1.45,1.30,1.42,1.46,
	1.49,1.72,1.82,2.01,2.21,false, // Xe
	0.86,0.97,
		1.10, // Ln
		1.10, 1.10, 1.10, 1.10, 1.10, 1.10, 1.10, 1.10, 1.10, 1.10, 1.10, 1.10, 1.10, 1.10, 
		1.23,1.33,1.40,1.46,1.52,1.55,1.44,1.42,1.44,
	1.44,1.55,1.67,1.76,1.96,false, // Rn
	0.86,0.97, // Fr,Ra
);

function getEneg($z) {
	global $eneg;
	return $eneg[$z-1];
}

function getEmpFormulaSort($formula) { // do not use any more!!
	$molecule=readSumFormula($formula,array("noFingerprint" => true));
	//~ return getEmpFormulaStrsort($molecule);
	return getEmpFormula($molecule,emp_formula_sort_fill);
}

function getEmpFormulaHill($formula) {
	$molecule=readSumFormula($formula,array("noFingerprint" => true));
	return getEmpFormula($molecule);
}

function readSumFormulaPart($emp_formulaStr) { // only real atoms, no groups
	$emp_formula=array();
	preg_match_all("/([A-Z%][a-z]*)(\d*)/",$emp_formulaStr,$formula,PREG_SET_ORDER);
	// Formel lesen
	for ($a=0;$a<count($formula);$a++) {
		if (empty($formula[$a][2])) { // Anzahl
			$formula[$a][2]=1;
		}
		$emp_formula[ $formula[$a][1] ]+=$formula[$a][2];
	}
	return $emp_formula;
}

function fixSumFormulaBrackets($emp_formulaStr) {
	global $func_groups;
	
	// replace all groups by bracketed sums
	foreach ($func_groups as $sym => $sum) {
		$emp_formulaStr=str_replace($sym,"(".$sum.")",$emp_formulaStr);
	}
	
	// handle expressions like *3H2O
	while (preg_match("/(?ims)([^\*]*)\s*\*\s*(\d*)\s*([^\*]+)(.*)/",$emp_formulaStr,$formula_data)) {
		// read parts
		$emp_formula=readSumFormulaPart($formula_data[3]);
		if ($formula_data[2]==="") {
			$formula_data[2]=1;
		}
		$emp_formula_text="";
		// multiply by factor
		if (is_array($emp_formula)) foreach ($emp_formula as $atom => $count) {
			$emp_formula_text.=$atom.($count*$formula_data[2]);
		}
		$emp_formulaStr=$formula_data[1].$emp_formula_text.$formula_data[4];
	}
	
	// replace innermost brackets by sum formulae until there is none found any more
	while (preg_match("/(?ims)(.*)\(([^\(^\)]*)\)(\d*)(.*)/",$emp_formulaStr,$formula_data)) {
		// read parts
		$emp_formula=readSumFormulaPart($formula_data[2]);
		if ($formula_data[3]==="") {
			$formula_data[3]=1;
		}
		$emp_formula_text="";
		// multiply by factor
		if (is_array($emp_formula)) foreach ($emp_formula as $atom => $count) {
			$emp_formula_text.=$atom.($count*$formula_data[3]);
		}
		$emp_formulaStr=$formula_data[1].$emp_formula_text.$formula_data[4];
	}
	return $emp_formulaStr;
}

function readSumFormula($emp_formulaStr,$paramHash=array()) { // keine Klammern, keine Ph,Me,..
	checkSettings($paramHash,"sum");
	$emp_formulaStr=fixSumFormulaBrackets($emp_formulaStr); // handle brackets and functional groups
	// returns $molecule only with "emp_formula"
	// options zum Ausschalten von Features
	preg_match_all("/([A-Z%][a-z]*)(\d*)/",$emp_formulaStr,$formula,PREG_SET_ORDER);
	
	$molecule=array();
	// Formel lesen
	for ($a=0;$a<count($formula);$a++) {
		if (empty($formula[$a][2])) { // Anzahl
			$formula[$a][2]=1;
		}
		$molecule["emp_formula"][ $formula[$a][1] ]+=$formula[$a][2];
	}
	if (!count($molecule["emp_formula"])) {
		return $molecule;
	}
	// Massenberechnung
	foreach ($molecule["emp_formula"] as $sym => $number) {
		$molecule["mw"]+=$number*getAtomMass($sym);
		if ($sym!="H") {
			$molecule["mw_noH"]+=$number*getAtomMass($sym);
		}
	}
	if (!$paramHash["noFingerprint"]) {
		calculateFingerprint($molecule);
	}
	$molecule["emp_formula_string"]=getEmpFormula($molecule);
	$molecule["emp_formula_string_sort"]=getEmpFormula($molecule,emp_formula_sort_fill); // for sorting by emp_formula
	return $molecule;
}

function getEmpFormularPart(& $molecule,$sym,$fill=0) {
	$retval="";
	//~ if ($molecule["emp_formula"][$sym]>0) {
	if ($molecule["emp_formula"][$sym]>0 || ($fill>0 && in_array($sym,array("C","H"))) ) { // always have CxxxHyyy at the beginning
		$retval.=$sym;
		if ($fill>0) {
			return $retval.str_pad($molecule["emp_formula"][$sym],$fill,"0",STR_PAD_LEFT);
		}
		if ($molecule["emp_formula"][$sym]>1) {
			$retval.=$molecule["emp_formula"][$sym];
		}
	}
	return $retval;
}

function getEmpFormula(& $molecule,$fill=0) { // Hill, CHABCDEFG
	if (!count($molecule["emp_formula"])) {
		return;
	}
	ksort($molecule["emp_formula"]);
	$retval.=getEmpFormularPart($molecule,"C",$fill).getEmpFormularPart($molecule,"H",$fill);
	foreach(array_keys($molecule["emp_formula"]) as $sym) {
		if ($sym=="*") { // do not include polymer links
			continue;
		}
		if (!in_array($sym,array("C","H"))) {
			$retval.=getEmpFormularPart($molecule,$sym,$fill);
		}
	}
	return $retval;
}

/* function getEmpFormulaStrsort(& $molecule) { // number for whole periodic table, only zerofilled three digit numbers
	global $pse;
	if (!count($molecule["emp_formula"])) {
		return;
	}
	$sort_pse=$pse;
	unset($sort_pse["C"]);
	unset($sort_pse["H"]);
	$sort_pse=array_keys($sort_pse);
	sort($sort_pse);
	array_unshift($sort_pse,"H");
	array_unshift($sort_pse,"C");
	$retval="";
	for ($a=0;$a<count($sort_pse);$a++) {
		$retval.=str_pad($molecule["emp_formula"][ $sort_pse[$a] ],3,"0",STR_PAD_LEFT);
	}
	return $retval;
}*/

?>