<?php
require_once "lib_global_funcs.php";
require_once "lib_db_query.php";
require_once "lib_applet.php";
require_once "lib_form_elements.php";

pageHeader();

// get random structure
list($result)=mysql_select_array(array(
	"table" => "molecule_fix_smiles", 
	"dbs" => -1, 
	"limit" => 1, 
	"filter" => "smiles NOT LIKE \"\"", 
	"order_obj" => array(
		array(
			"field" => "RAND()", 
			"no_hints" => true, 
		), 
	), 
));

echo script;

// prüfen
if (!empty($_REQUEST["appl"])) {
	$molecule=readMolfile($_REQUEST["appl"],array() ); // for  fingerprinting and serialisation
	if ($molecule["smiles_stereo"]!=$_REQUEST["smiles_stereo"] || $molecule["smiles"]!=$_REQUEST["smiles"]) {
		echo "alert(".fixStr(
			"molecule_id:".
			$_REQUEST["molecule_id"].
			"\\nOriginal stereoSMILES: ".
			$_REQUEST["smiles_stereo"].
			"\\nNeues: ".
			$molecule["smiles_stereo"].
			"\\nOriginal SMILES: ".
			$_REQUEST["smiles"].
			"\\nNeues: ".
			$molecule["smiles"]
		).");";
	}
}

$newData=array();
// neue Aufgabe
if ($_REQUEST["modus"]=="compare") {
	$newData["appl"]=$result["molfile_blob"];
}
else {
	$newData["appl"]="";
}

$newData["molfile_blob_before"]=$result["molfile_blob"];
$newData["smiles_stereo"]=$result["smiles_stereo"];
$newData["smiles"]=$result["smiles"];
$newData["molecule_id"]=$result["molecule_id"];
$newData["active_modus"]=$result["modus"];
$newData["molfile_blob_after"]="";

echo "parent.setControlValues(".json_encode($newData).");
parent.addMoleculeToUpdateQueue(\"molfile_blob_before\");
parent.addMoleculeToUpdateQueue(\"molfile_blob_after\");
parent.updateMolecules();
".
_script.
"<body>
</body>
</html>";

?>