<?php
require_once "lib_formatting.php";
require_once "lib_analytics.php";
echo "<html><body><pre>";
$ole2=file_get_contents("/home/fr/inventar_neu/inventar/HPLC-samples/blaaf205");

function readSECT($file,$SECT,$secShift=9) {
	$blockSize=1<<$secShift;
	$SECT++;
	return substr($file,$SECT*$blockSize,$blockSize);
}

function parseHEAD($data_str) {
	$header=colSplit($data_str,array(8,16,2,2,2,2,2,2,4,4,4,4,4,4,4,4,4,4,436),true); // 512
	if ($header[0]!="\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1") {
		return;
	}
	$data["secShift"]=up("S",$header[5]);
	$data["miniSecShift"]=up("S",$header[6]);
	$data["secNum"]=up("L",$header[10]);
	$data["firstSec"]=up("L",$header[11]);
	$data["firstMiniSec"]=up("L",$header[14]);
	$data["miniSecNum"]=up("L",$header[15]);
	$data["firstDIFSec"]=up("L",$header[16]);
	$data["DIFSecNum"]=up("L",$header[17]);
	return $data;
}

function parseDirSECT($data_str) {
	$data_arr=colSplit($data_str,array(64,2,1,1,4,4,4,16,4,8,8,4,4,2),true); // 126+2
	$data["root_name"]=substr($data_arr[0],0,up("S",$data_arr[1])-2); // cut off NULL termination, MS documentation is BS
	$data["left_sib"]=up("L",$data_arr[4]);
	$data["right_sib"]=up("L",$data_arr[5]);
	$data["child"]=up("L",$data_arr[6]);
	$data["clsid"]=$data_arr[7];
	// create/modify 9
	$data["createTime"]=$data_arr[9];
	$data["modifyTime"]=$data_arr[10];
	$data["streamFirst"]=up("L",$data_arr[11]);
	$data["streamNum"]=up("L",$data_arr[12]);
	return $data;
}

$header_str=substr($ole2,0,512);
$header=parseHEAD($header_str);
print_r($header);

$root_sect_str=readSECT($ole2,$header["firstSec"],$header["secShift"]);
$root_sect=parseDirSECT($root_sect_str);
print_r($root_sect);

//~ if ($root_sect["streamNum"]>(1<<$header["miniSecShift"])) {
	//~ $shift=$header["secShift"];
//~ }
//~ else {
	//~ $shift=$header["miniSecShift"];
//~ }

//~ $root_stream=readSECT($ole2,$root_sect["streamFirst"],$shift);
//~ echo $root_stream;

for ($a=1;$a<11;$a++) {
	$child=readSECT($ole2,$root_sect["child"],$a); // $header["secShift"]);
	//~ echo $child;
	print_r(parseDirSECT($child));
}

//~ $first_mini=readSECT($ole2,$header["firstMiniSec"],$header["miniSecShift"]);
//~ echo $first_mini;
?>