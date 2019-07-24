<?php
set_time_limit(20);
	require_once "lib_molfile.php";

//~ $_REQUEST["molfile1"]=file_get_contents("RUD368.rxn");
//~ $_REQUEST["molfile1"]=file_get_contents("RUD30.rxn");
echo "<html><body><pre>";
echo $_REQUEST["molfile1"];
//~ echo $_REQUEST["molfile2"];
//~ die();
//~ $molfile1=readMolfile($_REQUEST["molfile1"],array("debug" => true)); // 
//~ $molfile1=readMolfile($_REQUEST["molfile1"],array("debug" => true)); // 
$molfile1=readRxnfile($_REQUEST["molfile1"]);
//~ print_r($molfile1);
//~ echo "<html><body><pre>";
mapReaction($molfile1,array("drawAssignment" => true, ));
//~ echo "</pre>";
//~ var_export($molfile1["assignment_table"]);
//~ header(getHeaderFromMime(getMimeFromExt("gif")));
//~ echo getReactionGif($molfile1,rxn_gif_x,rxn_gif_y,0,1,6);

//~ echo getMoleculeGif($molfile1,300,300,0,4,true);
exit();


//~ getReactionGif($rxn,rxn_gif_x,rxn_gif_y,0,1,6);

//~ echo getMoleculeGif($molfile1,300,300,0,1,true);

function dumpBin($fingerprints) {
	if (is_array($fingerprints)) foreach ($fingerprints as $idx => $fingerprint) {
		echo $idx." ".decbin($fingerprint)."<br>";
	}
}

//~ print_r($molfile1["ri"]);
//~ print_r($molfile1["molecules"][0]["ringtypes"]);
//~ print_r($molfile1["ringtypes"]);
//~ print_r($molfile1["iProt"]);
print_r($molfile1["fingerprints"]);
 //~ dumpBin($molfile1["fingerprints"]);
print_r($molfile1["emp_formula_string"]);
echo "<br>";
print_r($molfile1["smiles_stereo"]);
//~ echo "<table><tr><td valign=\"top\"><pre>";
//~ print_r($molfile1);
//~ var_dump($molfile1);
//~ die(serialize($molfile1));
//~ echo "<hr>";
//~ echo "<br>";
//~ die();
//~ $molfile2=readMolfile($_REQUEST["molfile2"],array("debug" => true, "forStructureSearch" => true)); // 
$molfile2=readMolfile($_REQUEST["molfile2"],array("debug" => true, "forStructureSearch" => true));
//~ $molfile2=readRxnfile($_REQUEST["molfile2"],array("debug" => true, "forStructureSearch" => true));
//~ print_r($molfile2["ri"]);
//~ print_r($molfile2["molecules"][0]["ringtypes"]);
//~ print_r($molfile2["ringtypes"]);
//~ print_r($molfile2["iProt"]);
print_r($molfile2["fingerprints"]);
 //~ dumpBin($molfile2["fingerprints"]);
//~ echo "</pre></td><td valign=\"top\"><pre>";
print_r($molfile2["emp_formula_string"]);
echo "<br>";
print_r($molfile2["smiles_stereo"]);
//~ print_r($molfile2);
//~ echo "</pre></td></tr></table></body></html>";
//~ die();
//~ var_dump($molfile2);
for ($a=0;$a<count($molfile1["fingerprints"]);$a++) {
	$test[$a]=$molfile1["fingerprints"][$a] & $molfile2["fingerprints"][$a];
}
print_r($test);
//~ ob_end_clean();
$start=microtime(true);
$result1=getSubstMatch($molfile1,$molfile2,array("mode" => "assign"));
echo (microtime(true)-$start)." s";
$result2=getSubstMatch($molfile2,$molfile1,array("mode" => "assign"));
echo "<br>1 in 2: ".$result1[0].
"<br>2 in 1: ".$result2[0].
"<br>";
print_r($result1[1]);

function outputOxStates(& $molecule) {
	for ($a=0;$a<count($molecule["atoms"]);$a++) {
		echo $molecule["atoms"][$a]["s"].getOxidationState($molecule,$a)."<br>";
	}
}
//~ outputOxStates($molfile1);
//~ echo "<hr>";
//~ outputOxStates($molfile2);

//~ echo "<br>Y".getSubstMatch($molfile1,$molfile2)."X";
	//~ // echo writeMolfile($molecule);
	//~ // echo $molecule["smiles"];
	//~ echo "</pre></body></html>";
?>