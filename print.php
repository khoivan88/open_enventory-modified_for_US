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
require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
require_once "lib_formatting.php";

pageHeader();

$_REQUEST["pages"]+=0;
if ($_REQUEST["pages"]<0) {
	exit();
}

$options=explode(",",$_REQUEST["options"]);

if (in_array("lj_print",$options)) {
	$ana_h=$_REQUEST["heightMM"]/$analytical_data_lines-5;
	$ana_w=$_REQUEST["widthMM"]/$analytical_data_cols-5;
	$page_break="right";
}
else {
	$page_break="always";
}

$static_style="width:".$_REQUEST["widthMM"]."mm;";
if (in_array("multi_page",$options)) {
	$static_style.="min-";
}
else {
	$static_style.="overflow:hidden;max-height:".$_REQUEST["heightMM"]."mm;"; // cut
}
$static_style.="height:".$_REQUEST["heightMM"]."mm;border:0.05mm solid black";

echo "<title>".s("table")." ".s($_REQUEST["table"]).ifNotEmpty(" - ",s($_REQUEST["view"]))." - ".$_REQUEST["pages"]." ".($_REQUEST["pages"]==1?s("total2_sing"):s("total2"))."</title>
".stylesheet.
style."
@media print {
.reaction_eqn { max-width:".($_REQUEST["widthMM"]-8)."mm;max-height:".(($_REQUEST["widthMM"]-8)*rxn_gif_y/rxn_gif_x)."mm }
}

table.printTable { ".$static_style." }
table.printTable td { width:".$ana_w."mm;height:".$ana_h."mm;overflow:hidden }
"._style."
</head>
<body style=\"width:".$_REQUEST["widthMM"]."mm;margin:0;padding:0\">";

function getSubCell($prefix,$dataset,& $num) {
	return "<td id=".fixStr($prefix."_".$dataset."_".($num++))."></td>";
}

function getSubLine($prefix,$cols,$dataset,& $num) {
	$retval="<tr>";
	for ($a=0;$a<$cols;$a++) {
		$retval.=getSubCell($prefix,$dataset,$num);
	}
	$retval.="</tr>";
	return $retval;
}

function getSubBlock($prefix,$cols,$rows,$dataset) {
	$retval="<table class=\"printTable\" style=\"page-break-before:left;\" id=".fixStr($prefix."_".$dataset)."><tbody>";
	$num=0;
	for ($a=0;$a<$rows;$a++) {
		$retval.=getSubLine($prefix,$cols,$dataset,$num);
	}
	$retval.="</tbody></table>";
	return $retval;
}

function getCell($prefix,& $dataset) {
	return "<td id=".fixStr($prefix."_".($dataset++))."></td>";
}

function getLine($prefix,$cols,& $dataset) {
	$retval="<tr>";
	for ($a=0;$a<$cols;$a++) {
		$retval.=getCell($prefix,$dataset);
	}
	$retval.="</tr>";
	return $retval;
}

function getBlock($prefix,$cols,$rows,$dataset) {
	$retval="<table class=\"printTable\"";
	if ($dataset>0) {
		$retval.="style=\"page-break-before:left;\"";
	}
	$retval.="><tbody>";
	$num=0;
	for ($a=0;$a<$rows;$a++) {
		$retval.=getLine($prefix,$cols,$dataset);
	}
	$retval.="</tbody></table>";
	return $retval;
}

$flags=0;
$cols=ifempty($_REQUEST["cols"],1);
$rows=ifempty($_REQUEST["rows"],1);
$per_page=$cols*$rows;

for ($a=0;$a<$_REQUEST["pages"];$a+=$per_page) { // seitenweise
	if ($per_page==1) {
		echo "<div id=\"page_".$a."\" style=".fixStr($static_style.($a>0?";page-break-before:".$page_break:""))."></div>";
		
		if (in_array("lj_print",$options)) { // Analytik, 4 Viertel-Zellen
			echo getSubBlock("ana",$analytical_data_cols,$analytical_data_lines,$a);
		}
		elseif ($_REQUEST["table"]=="order_comp") { // Kleinaufträge, 2 pro Seite, in div
			echo "<div id=".fixStr("kleinauftrag_".$a)."></div>";
		}
	}
	else {
		echo getBlock("page",$cols,$rows,$a);
	}
}

if ($_REQUEST["table"]=="reaction" && ($permissions & _lj_read_all)==0) {
	$flags|=1;
}

echo "<div id=\"additional\" style=\"width:".$_REQUEST["widthMM"]."mm\"></div>
".script."
function go() {
	opener.completePrint(self,".fixNull($_REQUEST["widthMM"]).",".fixNull($_REQUEST["heightMM"]).",".fixStr($_REQUEST["options"]).",".intval($flags).");
}

function check() {
	if (opener.asyncInProgress==true) {
		window.setTimeout(function () { check(); },500);
	}
	else {
		window.setTimeout(function () { go(); },500);
	}
}

if (opener) {
	// check in 500 ms intervals if async is still in progress
	check();
}
"._script."
</body>
</html>";

?>