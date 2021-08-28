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
Seite zum Etikettendruck. Die Daten werden geholt wie in list.php?selected=true. Alle Positionsangaben sind in mm, label_size=xyz ist 
der Skalierungsfaktor. per_row und per_col geben an, wieviele Labels in einer Zeile/Spalte gedruckt werden sollen. Mit absolut 
positionierten DIVs wird in FF nur die 1. Seite gedruckt, deshalb wird jede Seite eine Tabelle. Am Ende wird die letzte Zeile mit 
leeren Zellen aufgefüllt
*/
require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
require_once "lib_db_query.php";
require_once "lib_formatting.php";
require_once "lib_constants.php";
pageHeader();

$table=$_REQUEST["table"];
$pageTitle=s("print_".$table."_barcode");

/* function showDiv($left,$top,$width,$height,$text,$fontSize=2,$borderWidth=0.2,$style="",$className="") {
	global $size_factor;
	echo "<div style=\"position:absolute;left:".($left*$size_factor)."mm;top:".($top*$size_factor)."mm;width:".($width*$size_factor)."mm;height:".($height*$size_factor)."mm;border:".($borderWidth*$size_factor)."mm solid black;font-size:".($fontSize*$size_factor)."mm;overflow:hidden;".$style."\"".($className!=""?" class=\"".$className."\"":"").">".$text."</div>\n";

}*/

$size_factor=ifempty($_REQUEST["label_size"],0.65);

$cell_height_factor=32.4;
$barcode_width=min(2.5*$size_factor,5)*80;
$barcode_img_height=$barcode_width/2.5;
	

switch ($table) {
case "person":
	$res=mysql_select_array(array(
		"table" => "person_quick", 
		"dbs" => "-1", 
		"filter" => "(permissions & ".(_chemical_read).")>0 AND (permissions & ".(_remote_read+_barcode_user).")=0", 
		"filterDisabled" => true, 
	));
break;
case "storage":
	$res=mysql_select_array(array(
		"table" => "storage", 
		"dbs" => "-1", 
		"filterDisabled" => true, 
	));
break;
case "helper": // no real table, the fill level commands instead
	$_REQUEST["per_col"]=12;
	$cell_height_factor=23.0;
	//~ $barcode_img_height=60;
	
	// Masse, Volumen, %
	$dataset_commands=array(0 => "delete", 1 => "cancel", 2 => "inventory_on", 3 => "inventory_off", );
	
	$max=max(count($chemical_storage_sizes),count($chemical_storage_levels));
	$res=array();
	for ($a=0;$a<$max;$a++) {
		$num=$a;
		fillZero($num,3);
		if ($a<count($chemical_storage_sizes)) {
			$res[]=array("num" => "01".$num, "text" => $chemical_storage_sizes[$a]." g");
			$res[]=array("num" => "02".$num, "text" => $chemical_storage_sizes[$a]." ml");
		}
		else {
			$res[]=array();
			$res[]=array();
		}
		if ($a<count($chemical_storage_levels)) {
			$res[]=array("num" => "03".$num, "text" => $chemical_storage_levels[$a]."%");
		}
		else {
			$num=$a-count($chemical_storage_levels);
			if ($num<count($dataset_commands)) {
				fillZero($num,3);
				$res[]=array("num" => "04".$num, "text" => s($dataset_commands[intval($num)]), );
			}
			else {
				$res[]=array();
			}
		}
	}
break;
}

//~ print_r($res);die();
$offsetTop=5;
$offsetLeft=5;
$hspace=2;
$vspace=2;
$labels_per_row=ifempty($_REQUEST["per_row"],3);
if ($labels_per_row<1) { // sonst Endlosschleife (DOS-Attacke)
	$labels_per_row=1;
}
$labels_per_col=ifempty($_REQUEST["per_col"],8);
if ($labels_per_col<1) { // sonst Endlosschleife (DOS-Attacke)
	$labels_per_col=1;
}
$c=0;
$fontsize1=2;
$fontsize2=1.8;
$border1=0.2;
$tabAttrib=" cellspacing=\"0\" cellpadding=\"0\"";

echo "<title>".$pageTitle."</title>
".style."
body { font-family:Verdana,Arial,Helvetica,sans-serif;font-size:".(2*$fontsize1*$size_factor)."mm }
td { vertical-align:middle;padding:".(0.15*$size_factor)."mm;margin:0mm;overflow:hidden }
img { vertical-align:middle }
"._style."
</head>
<body>
";

if ($table=="helper") {
	$prefix="99";
	$pkName="num";
}
else {
	$prefix=findBarcodePrefixForPk($table);
	$pkName=getShortPrimary($table);
}

for ($a=0;$a<count($res);$a++){
	if ($c%($labels_per_row*$labels_per_col)==0) { // neue Seite
		echo "<table cellspacing=\"30\" class=\"label\" style=\"table-layout:fixed;".($c>0?"page-break-before:always":"")."\">"; // cellspacing is to avoid ambigous scans
	}
	if ($c%$labels_per_row==0) { // neue Zeile
		echo "<tr>";
	}
	
	echo "<td style=\"width:".(50.4*$size_factor)."mm;height:".($cell_height_factor*$size_factor)."mm;border:".(0.2*$size_factor)."mm solid black\">";
	
	echo "<table".$tabAttrib." style=\"width:100%\"><tbody>".
"<tr><td>";
	
	switch ($table) {
	case "person":
		echo formatPersonNameNatural($res[$a]);
	break;
	case "storage":
		echo $res[$a]["storage_name"];
	break;
	case "helper":
		echo $res[$a]["text"];
	break;
	}
	
	$format="ean8";
	$value=$res[$a][$pkName];
	$len=8;
	
	// postproc
	switch ($table) {
	case "person":
		if (!empty($res[$a]["person_barcode"])) {
			$barcode=$res[$a]["person_barcode"];
			$format="ean13";
			break;
		}
	// no break;
	default:
		$barcode=getEANwithPrefix($prefix,$value,$len);
	}
	
	if (!empty($value)) {
		echo "<br><img src=\"getBarcode.php?text=".$barcode."&format=".$format."&horizontal=true&preform=true&width=".$barcode_width."&height=".$barcode_img_height."\">";
	}
	else {
		echo "&nbsp;";
	}
	
	echo "</td></tr>".
"</table>";
	echo "</td>\n";
	
	if ($c%$labels_per_row==$labels_per_row-1) { // Ende Zeile
		echo "</tr>";
	}
	
	if ($c%($labels_per_row*$labels_per_col)==($labels_per_row*$labels_per_col)-1) { // Ende Seite
		echo "</tbody></table>";
	}
	$c++;
}
$c--;
if ($c%$labels_per_row!=$labels_per_row-1) { // Zeile noch mit leeren Zellen füllen
	echo str_repeat("<td style=\"width:".(50.4*$size_factor)."mm;height:".(32.4*$size_factor)."mm;border:".(0.2*$size_factor)."mm solid black\"></td>",$labels_per_row-($c%$labels_per_row)-1);
	echo "</tr>";
}
if ($c%($labels_per_row*$labels_per_col)!=($labels_per_row*$labels_per_col)-1) { // Seite abschließen
	echo "</tbody></table>";
}

echo script."
window.print();
"._script."
</body>
</html>";
completeDoc();
?>