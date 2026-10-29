<?php
// usage: php fp_repro.php <oe_root>   -> prints JSON {name: {stored:[...], query:[...]}}
error_reporting(E_ERROR|E_PARSE);
$root=$argv[1];
chdir($root); set_include_path($root.PATH_SEPARATOR.get_include_path());
require_once "lib_global_funcs.php";
require_once "lib_molfile.php";

// atoms: [sym,x,y], bonds: [a,b,order] (1-based)
function mol($atoms,$bonds) {
	$s="\n  test\n\n".sprintf("%3d%3d  0  0  0  0  0  0  0  0999 V2000\n",count($atoms),count($bonds));
	foreach ($atoms as $a) $s.=sprintf("%10.4f%10.4f%10.4f %-3s 0  0  0  0  0  0  0  0  0  0  0  0\n",$a[1],$a[2],0,$a[0]);
	foreach ($bonds as $b) $s.=sprintf("%3d%3d%3d  0  0  0  0\n",$b[0],$b[1],$b[2]);
	return $s."M  END\n";
}
function benzene($subst) { // $subst: [ringpos(0-5) => [sym, ...chain]]
	$atoms=[];$bonds=[];
	for ($i=0;$i<6;$i++) $atoms[]=["C",cos($i*M_PI/3)*1.4,sin($i*M_PI/3)*1.4];
	for ($i=0;$i<6;$i++) $bonds[]=[$i+1,($i+1)%6+1,($i%2)?1:2];
	foreach ($subst as $pos=>$chain) {
		$prev=$pos+1; $r=2.8;
		foreach ($chain as $sym) { $atoms[]=[$sym,cos($pos*M_PI/3)*$r,sin($pos*M_PI/3)*$r]; $bonds[]=[$prev,count($atoms),1]; $prev=count($atoms); $r+=1.4; }
	}
	return mol($atoms,$bonds);
}
function chain($syms) { $atoms=[];$bonds=[]; foreach ($syms as $i=>$s) { $atoms[]=[$s,$i*1.3,($i%2)*0.75]; if ($i) $bonds[]=[$i,$i+1,1]; } return mol($atoms,$bonds); }
$mols=[
	"chlorobenzene"=>benzene([0=>["Cl"]]),
	"bromobenzene"=>benzene([0=>["Br"]]),
	"fluorobenzene"=>benzene([0=>["F"]]),
	"iodobenzene"=>benzene([0=>["I"]]),
	"4-chlorotoluene"=>benzene([0=>["Cl"],3=>["C"]]),
	"anisole"=>benzene([0=>["O","C"]]),
	"1-chlorobutane"=>chain(["C","C","C","C","Cl"]),
];
$out=[];
foreach ($mols as $name=>$mf) {
	$stored=readMolfile($mf,array());                              // as lib_db_manip_edit.php:1441 saves
	$query=readMolfile($mf,array("forStructureSearch"=>true));     // as lib_db_filter.php:341-348 builds the needle
	$out[$name]=["stored"=>array_map("intval",$stored["fingerprints"]),"query"=>array_map("intval",$query["fingerprints"]),"smiles"=>$stored["smiles"]];
}
echo json_encode($out);
