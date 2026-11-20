<?php
require_once "lib_global_funcs.php";
require_once "lib_formatting.php";
require_once "lib_db_manip.php";
require_once "lib_supplier_scraping.php";

function read_line($text) { // assuming BESSI is always first
	if (startswith($text,"insert")) {
		// Insert into CASNO (BESSI_NUMMER,CASNO) values (1188.'99-10-5');
		list($field,$text)=explode(" values ",$text,2);
		preg_match("/(?ims)\(BESSI_NUMMER,(.*?)\)/",$field,$field_data);
		$field=strtolower(str_replace(array("."),"",$field_data[1]));
		
		preg_match("/(?ims)\((\d+)\.(.*)\)/",$text,$data);
		$first=substr($data[2],0,1);
		$len_value=strlen($data[2]);
		$last=substr($data[2],$len_value-1,1);
		if ($first==$last && ($first=="\"" || $first=="\'")) {
			$data[2]=stripslashes(substr($data[2],1,$len_value-2));
		}
		return array($data[1],$field,$data[2]);
	}
	if (startswith($text,"mol")) {
		// MOL(1):CAS.NO(1) = 208-96-8
		list($bessi,$value)=explode(" = ",$text,2);
		preg_match("/(?ims)MOL\((\d+)\)/",$bessi,$bessi_data);
		preg_match("/(?ims):(.*?)\(/",$bessi,$field_data);
		$field=strtolower(str_replace(array("."),"",$field_data[1]));
		return array($bessi_data[1],$field,trim($value));
	}
}

function lineBlank($text) {
	if (startswith($text,"insert")) {
		return false;
	}
	if (startswith($text,"mol")) {
		return false;
	}
	return true;
}

function getMoleculeFromOwnDB($cas_nr) {
	global $db;
	if ($cas_nr=="") {
		return;
	}
	$res_link=mysqli_query($db,"SELECT molecule.molecule_id FROM (molecule INNER JOIN molecule_names ON molecule.molecule_id=molecule_names.molecule_id) WHERE cas_nr LIKE \"".$cas_nr."\";") or die(mysqli_error($db));
	if (mysqli_num_rows($res_link)>0) {
		$result=mysqli_fetch_assoc($res_link);
		return $result["molecule_id"];
	}
}

function createStorageIfNotExist($name) {
	global $db;
	$name=trim($name);
	if ($name=="") {
		return;
	}
	$res_link=mysqli_query($db,"SELECT storage_id FROM storage WHERE storage_name LIKE \"".$name."\";") or die(mysqli_error($db));
	if (mysqli_num_rows($res_link)==0) { // neues erstellen
		mysqli_query($db,"INSERT INTO storage (storage_id,storage_name) VALUES (NULL,".fixStr($name).");");
		return mysqli_insert_id($db);
	}
	$result=mysqli_fetch_assoc($res_link);
	return $result["storage_id"];
}


pageHeader();
$handle=fopen("bessi.txt","r");
// /home/fr/storage/trunk/inventar_dev/
// /srv/www/htdocs/inventar_dev/
while (!feof($handle)) {
	$buffer=fgets($handle,4096);
	$zeilen[]=utf8_encode($buffer);
}
fclose ($handle);

//~ die(count($zeilen)."X");

$data=array();

$db_sigel=array(
"fuerstner" => "FUE", 
"schueth" => "STF", 
"list" => "LIS", 
"reetz" => "REE", 
);

$a=0;
do { // count($zeilen)
	set_time_limit(90);
	list($bessi,$field,$value)=read_line($zeilen[$a]);
	while (lineBlank($zeilen[++$a])) {
		if ($a>=count($zeilen)) {
			break 2;
		}
		$value.=$zeilen[$a]; // collect following lines
	}
	
	switch ($field) {
	case "casno":
		$data[$bessi]["cas_nr"]=$value;
	break;
	case "fort":
	case "frucht":
		if (isset($data[$bessi]["safety_reprod"])) {
			$value=min($data[$bessi]["safety_reprod"],$value);
		}
		$data[$bessi]["safety_reprod"]=$value;
	break;
	case "erbgut":
		$data[$bessi]["safety_mutagen"]=$value;
	break;
	case "krebs":
		$data[$bessi]["safety_cancer"]=$value;
	break;
	case "wgk":
		$data[$bessi]["safety_wgk"]=$value;
	break;
	case "hinweis":
		$data[$bessi]["comment_mol"].=$value; // attach
	break;
	case "name":
		$data[$bessi]["molecule_names_array"][]=$value;
	break;
	case "mgmak":
		$data[$bessi]["molecule_property"][]=array("class" => "MAK", "source" => "BESSI", "value_high" => getNumber($value), "unit" => "mg/m3", );
	break;
	case "mlmak":
		$data[$bessi]["molecule_property"][]=array("class" => "MAK_vol", "source" => "BESSI", "value_high" => getNumber($value), "unit" => "ml/m3", );
	break;
	case "mgtrk":
		$data[$bessi]["molecule_property"][]=array("class" => "TRK", "source" => "BESSI", "value_high" => getNumber($value), "unit" => "mg/m3", );
	break;
	case "mltrk":
		$data[$bessi]["molecule_property"][]=array("class" => "TRK_vol", "source" => "BESSI", "value_high" => getNumber($value), "unit" => "ml/m3", );
	break;
	case "un":
		$data[$bessi]["molecule_property"][]=array("class" => "adr", "source" => "BESSI", "conditions" => $value, );
	break;
	case "vbf":
		$data[$bessi]["molecule_property"][]=array("class" => "VbF", "source" => "BESSI", "conditions" => trim($value), );
	break;
	case "sabotage":
		if (strtolower(trim($value))=="ja") {
			$data[$bessi]["pos_liste"]=1;
		}
	break;
	case "stand_ree":
		if ($db_name=="reetz") {
			$data[$bessi]["storage_name"]=$value;
		}
	break;
	case "stand_fue":
		if ($db_name=="fuerstner") {
			$data[$bessi]["storage_name"]=$value;
		}
	break;
	case "kom_fue":
		if ($db_name=="fuerstner") {
			$data[$bessi]["comment_cheminstor"]=$value;
		}
	break;
	case "stand_stf":
		if ($db_name=="schueth") {
			$data[$bessi]["storage_name"]=$value;
		}
	break;
	case "best":
		list(		$date,	$data[$bessi]["supplier"],,$sap_bestell_nr,	$name_cap,	$name,	$sigel,	$storage_name,$amount,	$comment,$comment2)=colSplit($value,
		array(	9,		21,					4,7,		21,			21,		4,		6,			16,		13));
		if ($db_sigel[$db_name]==$sigel) {
			$data[$bessi]["storage_name"]=ifempty($data[$bessi]["storage_name"],$storage_name);
			list($data[$bessi]["amount"],$data[$bessi]["amount_unit"])=spaceSplit($amount);
			$data[$bessi]["amount_unit"]=strtolower($data[$bessi]["amount_unit"]);
			$data[$bessi]["comment_cheminstor"]=$comment;
		}
	break;
	case "stamm":
		if ($db_name=="lager") {
			$data[$bessi]["mat_stamm_nr"][]=$value;
		}
	break;
	case "lager":
		if ($db_name=="lager") {
			list($data[$bessi]["storage_name"],$amount,$data[$bessi]["supplier"],$purity,$unknown1,$unknown2,$data[$bessi]["comment_cheminstor"])=colSplit($value,array(21,21,21,11,11,7));
			$data[$bessi]["purity"]=getNumber($purity);
			list($data[$bessi]["amount"],$data[$bessi]["amount_unit"])=spaceSplit($amount);
			$data[$bessi]["amount"]=str_replace(array(".",","),array("","."),$data[$bessi]["amount"]); // make engl format
			$data[$bessi]["amount_unit"]=strtolower($data[$bessi]["amount_unit"]);
		}
	break;
	}
} while (true);

//~ print_r($data);die("X");
//~ print_r($data[685]);die("X");

//~ $bessi=685;
//~ $molecule=$data[$bessi];
foreach ($data as $bessi => $molecule) {
	//~ if ($bessi<=7883) {
		//~ continue;
	//~ }
	set_time_limit(90);
	// find cas
	echo $bessi."X".$molecule["cas_nr"]."<br/>";
	/*
	flush();
	ob_flush(); */
	$chemical_storage=array();
	$molecule["migrate_id_mol"]=$bessi;
	$chemical_storage["migrate_id_cheminstor"]=$bessi;
	$chemical_storage["molecule_id"]=getMoleculeFromOwnDB($molecule["cas_nr"]);
	if ($chemical_storage["molecule_id"]=="") { // neues Molekül
		continue;
		// no: create molecule
		getAddInfo($molecule); // Daten von suppliern holen, kann dauern
		extendMoleculeNames($molecule);
		//~ print_r($molecule);die("X");
		$oldReq=$_REQUEST;
		$_REQUEST=array_merge($_REQUEST,$molecule);
		$list_int_name="molecule_property";
		if (is_array($molecule[$list_int_name])) foreach ($molecule[$list_int_name] as $UID => $property) {
			$_REQUEST[$list_int_name][]=$UID;
			$_REQUEST["desired_action_".$list_int_name."_".$UID]="add";
			$_REQUEST[$list_int_name."_".$UID."_class"]=$property["class"];
			$_REQUEST[$list_int_name."_".$UID."_source"]=$property["source"];
			$_REQUEST[$list_int_name."_".$UID."_conditions"]=$property["conditions"];
			$_REQUEST[$list_int_name."_".$UID."_value_low"]=$property["value_low"];
			$_REQUEST[$list_int_name."_".$UID."_value_high"]=$property["value_high"];
			$_REQUEST[$list_int_name."_".$UID."_unit"]=$property["unit"];
		}
		
		performEdit("molecule",-1,$db);
		$chemical_storage["molecule_id"]=$_REQUEST["molecule_id"];
		$_REQUEST=$oldReq;
	}
	//~ if ($molecule["pos_liste"]) {
		//~ $sql="UPDATE molecule SET pos_liste=".fixNull($molecule["pos_liste"])." WHERE migrate_id_mol LIKE BINARY ".fixStrSQL($bessi).";";
		//~ mysqli_query($db,$sql) or die(mysqli_error($db).$sql);
	//~ }
	if (!empty($chemical_storage["molecule_id"]) && $molecule["mat_stamm_nr"]) {
		$molecule["mat_stamm_nr"]=array_unique($molecule["mat_stamm_nr"]);
		for ($a=0;$a<count($molecule["mat_stamm_nr"]);$a++) {
			if (empty($molecule["mat_stamm_nr"][$a]) || $molecule["mat_stamm_nr"][$a]=="-") {
				continue;
			}
			$sql[]="INSERT INTO mat_stamm_nr (molecule_id,sap_stamm_nr) VALUES (".fixNull($chemical_storage["molecule_id"]).",".fixStrSQL($molecule["mat_stamm_nr"][$a]).");";
		}
		performQueries($sql,$db);
	}
	continue;
	// do we have to create chemical_storage?
	if ($molecule["storage_name"]!="") {
		$chemical_storage["storage_id"]=createStorageIfNotExist($molecule["storage_name"]);
		$chemical_storage=array_merge(
			$chemical_storage,
			array_key_filter(
				$molecule,
				array(
					"supplier", 
					"comment_cheminstor", 
					"purity", 
					"amount", 
					"amount_unit", 
				)
			)
		);
		// do we have to create storage first?
		$oldReq=$_REQUEST;
		$_REQUEST=array_merge($_REQUEST,$chemical_storage);
		//~ print_r($_REQUEST);die("X");
		performEdit("chemical_storage",-1,$db);
		$_REQUEST=$oldReq;
	}
}
?>