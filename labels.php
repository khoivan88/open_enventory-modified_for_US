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
require_once "lib_constants_barcode.php";

// everything concerning the data retrieval quite similar to list.php
$_REQUEST["table"]="chemical_storage";
$_REQUEST["query"]="";
$_REQUEST["selected_only"]=1;
$_REQUEST["filter_disabled"]=1;
$_REQUEST["per_page"]=-1;

pageHeader();
setGlobalVars();

list($res,$dataArray,$sort_hints)=handleQueryRequest(2);

$totalCount=& $dataArray["totalCount"];
$page=& $dataArray["page"];
$skip=& $dataArray["skip"];
$per_page=& $dataArray["per_page"];
$cache_active=& $dataArray["cache_active"];

$size_factor=ifempty($_REQUEST["label_size"],1);
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
$labels_per_page=$labels_per_row*$labels_per_col;
$cellWidthMM=170/$labels_per_row; // assume A4 with 20 mm margins everywhere
$cellHeightMM=257/$labels_per_col;
$hpfull=($labels_per_page<=10); // print full H/P clauses
$c=0;
$fontsize=1.7;
$scaled_fontsize=$fontsize*$size_factor;
$border1=0.2;

if ($_REQUEST["barcode"]=="true") {
	$barcode_height=min(2.5*$size_factor,5);
	$barcode_width=$barcode_height/2;
}

echo "<title>".s("print_labels")."</title>".
loadJS(array("safety_".$lang.".js","safety.js",),"lib/").
style."
body { font-family:Verdana,Arial,Helvetica,sans-serif;font-size:".$scaled_fontsize."mm }
td {padding:0;margin:0;overflow:hidden }

table.border { width:100%;height:100%;border-collapse:collapse }
table.border > * > tr > td { border:0.5px solid black;padding:1px; }
".
_style."
</head>
<body>
";


for ($a=0;$a<$totalCount;$a++){
	for ($b=0;$b<$settings["selection"][$table][ $res[$a]["db_id"] ][ $res[$a]["chemical_storage_id"] ];$b++){
		if ($c%$labels_per_page==0) { // neue Seite
			echo "<table class=\"label\" style=\"table-layout:fixed;".($c>0?"page-break-before:always":"")."\">";
		}
		if ($c%$labels_per_row==0) { // neue Zeile
			echo "<tr>";
		}
		if ($_REQUEST["barcode"]=="true") {
			echo "<td style=\"width:".(70.4*$size_factor)."mm;height:".(32.4*$size_factor)."mm;border:".(0.2*$size_factor)."mm solid black\">";
		}
		else {
			echo "<td style=\"width:".(50.4*$size_factor)."mm;height:".(32.4*$size_factor)."mm;border:".(0.2*$size_factor)."mm solid black\">";
		}
		
		// single label
		echo "<table cellspacing=\"0\" cellpadding=\"0\" style=\"table-layout:fixed;border-collapse:collapse;width:100%;height:100%;padding:0\">";
		if ($_REQUEST["barcode"]=="true") {
			echo "<colgroup><col width=\"48%\"/><col width=\"*\"/><col width=\"8%\"/></colgroup>";
		} else {
			echo "<colgroup><col width=\"60%\"/><col width=\"*\"/></colgroup>";
		}
		
		echo "<tbody><tr><td><table class=\"border\"><tbody><tr><td colspan=\"2\"><b>".fixNbsp($res[$a]["molecule_name"])."</b></td></tr>
<tr><td>".fixNbsp(getBeautySum($res[$a]["emp_formula"]))."</td>
<td>MW: ".round($res[$a]["mw"],2)."</td></tr>";
		
		if ($res[$a]["chemical_storage_conc"]>0) {
			echo "<tr><td colspan=\"2\">".getSolutionFmt($res[$a]["chemical_storage_conc"],$res[$a]["chemical_storage_conc_unit"],$res[$a]["chemical_storage_solvent"])."</td></tr>";
		}
		if (!isEmptyStr($res[$a]["storage_name"]) || !isEmptyStr($res[$a]["compartment"])) {
			echo "<tr><td colspan=\"2\">".$res[$a]["storage_name"]." ".$res[$a]["compartment"]."</td></tr>";
		}
		echo "<tr><td colspan=\"2\"><img src=\"getGif.php?molecule_id=".$res[$a]["molecule_id"]."&db_id=".$res[$a]["db_id"]."\" style=\"width:".(18.2*$size_factor)."mm;height:".(16*$size_factor)."mm\"/></td></tr></tbody></table>
</td><td><table class=\"border\"><tbody>";
		if ($g_settings["use_ghs"]) {
			$safetySymHtml=getSafetyGif($res[$a]["safety_sym_ghs"],15*$size_factor);
		}
		else {
			//~ echo $res[$a]["safety_sym"];
			$safetySymHtml= getSafetyGif($res[$a]["safety_sym"],15*$size_factor);
		}
		if (!isEmptyStr($safetySymHtml)) {
			echo "<tr valign=\"top\"><td>".$safetySymHtml."</td></tr>";
		}
		if (!isEmptyStr($res[$a]["safety_text"])) {
			echo "<tr><td><b>".$res[$a]["safety_text"]."</b></td></tr>";
		}
		echo "<tr>";
		if ($hpfull) {
			echo script."var clauses=procClauses(\"H\",".fixStr($res[$a]["safety_h"]).");document.write(\"<td style=\\\"\"+getFontSizeCSS(".$scaled_fontsize.", \"mm\", clauses, 110)+\"\\\">\"+clauses+\"</td>\");"._script;
		}
		else {
			echo "<td>H: ".fixNbsp(fixBr($res[$a]["safety_h"]))."</td>";
		}
		echo "</tr><tr>";
		if ($hpfull) {
			echo script."var clauses=procClauses(\"P\",".fixStr($res[$a]["safety_p"]).");document.write(\"<td style=\\\"\"+getFontSizeCSS(".$scaled_fontsize.", \"mm\", clauses, 110)+\"\\\">\"+clauses+\"</td>\");"._script;
		}
		else {
			echo "<td>P: ".fixNbsp(fixBr($res[$a]["safety_p"]))."</td>";
		}
		echo "</tr>";
		
		$cmrText=joinIfNotEmpty(array(
			ifNotEmpty(s("safety_cancer_short")." ",$res[$a]["safety_cancer"]),
			ifNotEmpty(s("safety_mutagen_short")." ",$res[$a]["safety_mutagen"]),
			ifNotEmpty(s("safety_reprod_short")." ",$res[$a]["safety_reprod"]),
			//ifNotEmpty(s("safety_danger_short")." ",$res[$a]["safety_text"])
		), ", ");
		if (!isEmptyStr($cmrText)) {
			echo "<tr><td>".$cmrText."</td></tr>";
		}
		
		echo "<tr><td>CAS: ".fixNbsp($res[$a]["cas_nr"])."</td></tr></tbody></table></td>";
		
		if ($_REQUEST["barcode"]=="true") {
			echo "<td><img style=\"width:".$barcode_width."cm;height:".$barcode_height."cm\" src=\"getBarcode.php?text=".getEAN8(findBarcodePrefixForPk("chemical_storage"),$res[$a]["chemical_storage_id"])."&preform=true&width=81&height=162\"/></td>";
		}
		echo "</tr></tbody></table>";
		// end single label
		
		echo "</td>\n";
		if ($c%$labels_per_row==$labels_per_row-1) { // Ende Zeile
			echo "</tr>";
		}
		if ($c%($labels_per_row*$labels_per_col)==($labels_per_row*$labels_per_col)-1) { // Ende Seite
			echo "</table>";
		}
		$c++;
	}
}
$c--;
if ($c%$labels_per_row!=$labels_per_row-1) { // Zeile noch mit leeren Zellen füllen
	echo str_repeat("<td style=\"width:".(50.4*$size_factor)."mm;height:".(32.4*$size_factor)."mm;border:".(0.2*$size_factor)."mm solid black\"></td>",$labels_per_row-($c%$labels_per_row)-1);
	echo "</tr>";
}
if ($c%($labels_per_row*$labels_per_col)!=($labels_per_row*$labels_per_col)-1) { // Seite abschließen
	echo "</table>";
}

echo script."
window.print();
"._script."
</body>
</html>";
completeDoc();
?>