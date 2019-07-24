<?php
set_time_limit(90);
	require_once "lib_molfile.php";

//~ $molfile1=readRxnfile($_REQUEST["molfile1"]);

	//~ echo $_REQUEST["molfile1"]."-----------------------------------\n";
//~ echo "<html><body>";
echo "<html><body><pre>";
//~ ob_start();
/*
$_REQUEST["molfile1"]=<<<END
imes v0.1 pre alpha


 14 16  0  0  0  0            999 V2000
  337.7124 -441.9795    0.0000 N   0  0  0  0  0  0  0               
  337.6647 -472.3458    0.0000 C   0  0  0  0  0  0  0               
  363.7275 -487.5786    0.0000 C   0  0  0  0  0  0  0               
  390.0206 -472.3260    0.0000 C   0  0  0  0  0  0  0               
  363.6561 -426.9175    0.0000 C   0  0  0  0  0  0  0               
  389.7983 -441.8882    0.0000 C   0  0  0  0  0  0  0               
  415.6984 -426.8897    0.0000 C   0  0  0  0  0  0  0               
  415.6587 -396.7219    0.0000 C   0  0  0  0  0  0  0               
  363.5608 -396.9006    0.0000 C   0  0  0  0  0  0  0               
  389.3219 -382.0768    0.0000 C   0  0  0  0  0  0  0               
  389.2227 -352.2464    0.0000 C   0  0  0  0  0  0  0               
  363.3782 -337.5972    0.0000 C   0  0  0  0  0  0  0               
  337.5972 -352.7784    0.0000 C   0  0  0  0  0  0  0               
  337.7997 -382.1522    0.0000 N   0  0  0  0  0  0  0               
  4  6  1  0  0  0  0
  7  8  1  0  0  0  0
  8 10  2  0  0  0  0
  9  5  2  0  0  0  0
  5  1  1  0  0  0  0
  2  3  1  0  0  0  0
  9 10  1  0  0  0  0
  1  2  2  0  0  0  0
 10 11  1  0  0  0  0
  5  6  1  0  0  0  0
 11 12  2  0  0  0  0
  3  4  2  0  0  0  0
 12 13  1  0  0  0  0
  6  7  2  0  0  0  0
 13 14  2  0  0  0  0
 14  9  1  0  0  0  0
M  END

END;
$_REQUEST["molfile2"]=<<<END
  Marvin  10140814332D          

  7 11  0  0  0  0            999 V2000
   -1.7089    0.4955    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -2.3764    0.0106    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -2.1214   -0.7740    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.2964   -0.7740    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -1.0415    0.0106    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -2.6064   -1.4414    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.0281    1.1805    0.0000 Fe  0  3  0  0  0  0  0  0  0  0  0  0
  1  2  1  0  0  0  0
  1  5  1  0  0  0  0
  2  3  1  0  0  0  0
  3  4  1  0  0  0  0
  4  5  1  0  0  0  0
  3  6  1  0  0  0  0
  1  7  1  0  0  0  0
  5  7  1  0  0  0  0
  2  7  1  0  0  0  0
  7  4  1  0  0  0  0
  7  3  1  0  0  0  0
M  CHG  1   7   1
M  END

END;*/
//~ echo $_REQUEST["molfile1"];
//~ echo $_REQUEST["molfile2"];
//~ die();
//~ $molfile1=readMolfile($_REQUEST["molfile1"],array("debug" => true)); // 
//~ echo "<table valign=top><tr><td valign=top><pre>";
$molfile1=readMolfile($_REQUEST["molfile1"],array("debug" => true)); // 
//~ echo "<td valign=top><pre>";
//~ $molfile1=readRxnfile($_REQUEST["molfile1"]);
//~ print_r($molfile1);
//~ header(getHeaderFromMime(getMimeFromExt("gif")));

//~ echo getMoleculeGif($molfile1,300,300,0,4,true);
exit();


//~ echo getReactionGif($rxn,rxn_gif_x,rxn_gif_y,0,1,6);
//~ getReactionGif($rxn,rxn_gif_x,rxn_gif_y,0,1,6);

//~ echo getMoleculeGif($molfile1,300,300,0,1,true);

function dumpBin($fingerprints) {
	if (is_array($fingerprints)) foreach ($fingerprints as $idx => $fingerprint) {
		echo $idx." ".decbin($fingerprint)."<br>";
	}
}

//~ echo $molfile1["maxChainSize"]."<br>";
//~ print_r($molfile1["ri"]);
//~ print_r($molfile1["molecules"][0]["ringtypes"]);
//~ print_r($molfile1["ringtypes"]);
//~ print_r($molfile1["iProt"]);
print_r($molfile1["fingerprints"]);
 //~ dumpBin($molfile1["fingerprints"]);
//~ print_r($molfile1["emp_formula_string"]);
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
//~ echo "</tr></table><pre>";
//~ $molfile2=readRxnfile($_REQUEST["molfile2"],array("debug" => true, "forStructureSearch" => true));
//~ echo $molfile2["maxChainSize"]."<br>";
//~ print_r($molfile2["ri"]);
//~ print_r($molfile2["molecules"][0]["ringtypes"]);
//~ print_r($molfile2["ringtypes"]);
//~ print_r($molfile2["iProt"]);
print_r($molfile2["fingerprints"]);
 //~ dumpBin($molfile2["fingerprints"]);
//~ echo "</pre></td><td valign=\"top\"><pre>";
//~ print_r($molfile2["emp_formula_string"]);
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
//~ $bk_molfile1=unserialize(serialize($molfile1));
//~ $bk_molfile2=unserialize(serialize($molfile2));
//~ prepareForSubstMatch($bk_molfile1);
/* if ($bk_molfile1!=$molfile1) {
	echo "<table><tbody><tr><td><pre>";
	print_r($molfile1);
	echo "</pre></td><td><pre>";
	print_r($bk_molfile1);
	echo "</pre></td></tr></tbody></table><br>";
	die();
} */
//~ prepareForSubstMatch($bk_molfile2);
$result1=getSubstMatch($molfile1,$molfile2,array("mode" => "assign"));
$result2=getSubstMatch($molfile2,$molfile1,array("mode" => "assign"));
echo 
"<br>1 in 2: ".$result1[0].
"<br>2 in 1: ".$result2[0].
"<br>";
//~ print_r($result1[1]);

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