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

$barcodeTerminal=true;
$page_type="plain";
pageHeader(true,false,false,true);

$inputBg=($g_settings["highlight_inputs"]?"white":"transparent");

header(getHeaderFromMime("text/css"));
if (false && $_SERVER["HTTP_CACHE_CONTROL"]=="max-age=0") { // gain some speed
	header("HTTP/1.x 304 Not Modified");
	exit();
}

function getImgButtonStyle($style) { // Sm, Vsm
	$height=20;
	if ($style=="Vsm") {
		$height=10;
		$additional="font-size:5pt; ";
	}
	$retval="
a.imgButton".$style.":link,a.imgButton".$style.":visited { display:block; width:auto; text-align:center; border:1px solid black; color:black; text-decoration:none; padding:2px; margin:1px; background-color:white; height:".$height."px; ".$additional."float:left; clear:both }
a.imgButton".$style.":hover { border:2px solid black; padding:1px }
a.imgButton".$style." img { display:inline; height:".$height."px }\n";
	return $retval;
}

// common stuff
// allgemein
echo getImgButtonStyle("Sm").getImgButtonStyle("Vsm")."
.very_small { font-size:8pt }
.bigger { font-size:14pt }
form { padding:0px !important;margin:0px !important }
fieldset { padding:2px;margin:0px }
.noprint {}
.print_only {}

@media handheld, screen, projection, tv {
.print_only { display:none !important }

.mainbody { position:absolute;overflow:hidden;padding:0px;width:100%;height:100%;margin:0px }
#browsemain { position:absolute;overflow:auto;bottom:0px;right:0px;padding:1px 20px 0px 5px }
}

@media print {
.noprint { display:none !important }
.listtable { font-size:8pt }

.mainbody { padding:0px;margin:0px }
#browsenav,#overlay { display:none }
}

". // Ausrichtung von block-Elementen
" .blockAlignLeft {margin-left:0;margin-right:auto}
.blockAlignCenter {margin-left:auto;margin-right:auto}
.blockAlignRight {margin-left:auto;margin-right:0}

". // Knöpfe in list/edit
"table.triAlign { width:100%;padding:0px }
table.triAlign td {border:0px solid black; width:33%;padding:0px }
table.noborder { padding-top:0px;padding-bottom:0px;margin-top:0px;margin-bottom:0px; }
table.noborder > * > tr > td { border:0px solid black;padding:0px;margin:0px;width:auto;border-collapse:separate }
/* table.line_20 td { height:26px;overflow:hidden }*/

". // Knöpfe in list/edit
"table.twoAlign { width:100% }
table.twoAlign td {border:0px solid black; width:50% }

". // subitemlist
"table.subitemlist { margin:1px 5px;width:100%;border-collapse:collapse }
table.subitemlist > * > tr > td { border:1px solid black;margin:5px;padding:5px }
table.subitemlist td.firstCol { border-left-width:2px }
table.subitemlist input,table.subitemlist textarea { vertical-align:middle; margin:3px; background-color:".$inputBg.";border:0px solid black }

". // Liste mit Chemikalien zum Zusammensuchen
"table.chemlist { margin:1px 5px;width:100%;border-collapse:collapse }
table.chemlist > * > tr > td { border:1px solid black;font-size:8pt }

a.buttonActive { border:2px solid red !important; padding:1px !important; background-color:white !important }

a.imgButton:link,a.imgButton:visited { border:1px solid black; padding:2px; margin:3px; display:block; height:27px;line-height:25px;text-align:center; color:black;background-color:white;font-size:9pt }
a.imgButton:hover { border:2px solid black; padding:1px; background-color:white }

.list_options { position:absolute;background-color:".defBgColor.";border:1px solid black }

table.kleinauftrag { margin:0 }
table.kleinauftrag td { border:0.05mm solid black;font-size:7pt;line-height:7.5pt }
table.kleinauftrag .normal { font-size:8pt;line-height:8.5pt }
table.kleinauftrag .big { font-size:13pt;line-height:13.5pt }

";

//~ a.imgButtonA { border:2px solid red; padding:1px; margin:4px; background-color:white; display:block; height:32px;text-align:center; background-color:white }

if ($_REQUEST["style"]=="sidenav") {
	echo "
body {color:white;font-size:9pt;font-family:Verdana,Arial,Helvetica,sans-serif;font-weight:bold}
@media handheld, screen, projection, tv {
	body {background-color:".defBgColor."}
}
@media print {

}
a { text-decoration:none;font-weight:bold }
input,select { font-size:9pt }
table.hidden { padding:0px; margin:0px; border-collapse:collapse }
td.hidden { padding:0px; margin:0px; border-collapse:collapse }
p {padding-left:10px}
#sideDiv {position:absolute;left:0px;top:87px; }
#uni_logo { position:absolute;top:10px;left:10px }
label.bg { color:black;background-color:white;font-size:11pt;font-weight:normal }
label {font-size:9pt;font-family:Verdana,Arial,Helvetica,sans-serif;font-weight:bold}
legend {color:white;font-size:9pt;font-family:Verdana,Arial,Helvetica,sans-serif;font-weight:bold}

#collapse,#expand { position:fixed;bottom:5px;right:5px }
#support_project {margin:5px}

.condition { color:white }
/* .condition { color:black;background-color:".defBgColor." }*/

". // Links in sidenav eleganter//~ .linkDiv {text-align:center;padding:0px;position:relative;left:-4px;cursor:pointer}
"a.text,div.text { color:white;display:block;padding:0px;width:220px;position:relative;left:-4px;text-align:center;margin:0 auto 0 0;line-height:7pt }
a.text div.active { display:none }
a.text div.inactive { display:block }
a.text:hover div.inactive { display:none }
a.text:hover div.active { display:block }

option { padding:0px 1px }

";
}
else { // Standard---------------------------------------------------------------------------------------------------------------------------------------------------------------------------
	echo "
body { font-size:9pt;font-family:Verdana,Arial,Helvetica,sans-serif;padding:4px }
@media handheld, screen, projection, tv {
	body {background-color:".defBgColor."}
}
@media print {

}
p { font-size:11pt;margin-left:0px; padding-left:0px; margin-top:8px; margin-right:24px }
#overlay { background-color:".defBgColor.";position:absolute;left:0px;top:0px;width:auto;height:auto }
#perPageOverlay,#showColumnOverlay { background-color:#0e3292;position:absolute;left:0px;top:0px;width:auto;height:auto;padding:5px }
#perPageOverlay a,#showColumnOverlay a { text-decoration:none;color:white;font-weight:bold }
#grand_total {width:100%;text-align:right}
.structureOverlay { position:relative;top:-1px;left:-1px;border:1px solid black;display:block }
.structureButtons { position:absolute;border:0px solid black;display:block;width:auto;z-index:3 }

.searchRxnTable > * > tr > td {vertical-align:top }

.printTable { background-color:white;table-layout:fixed }
.printTable td { vertical-align:top }

/* Tabelle login */
table.blind input { margin:5px }

/* tabMode!=\"\" */
table.formAlign { border-collapse:collapse }
table.formAlign > * > tr > td { vertical-align:middle; padding:5px }

/* tabMode=\"h\" Spalten Name und Wert */
table.formAlignH > * > tr > td { margin:0px;border:1px solid black }
td.formAlignName {  }
td.formAlignValue { cursor:text }

/* Table in list.php // clear:left; */
table.listtable { width:100%;border-collapse:collapse }
table.listtable > * > tr > td { padding:0px 5px; border:1px solid black }
table.listtable > thead > tr > td { padding:5px; border-top-width:0px }
td.literature { vertical-align:top }

/* Table in searchExtAsync.php */
table.exttable { width:100%;border-collapse:collapse }
table.exttable > * > tr > td { padding:3px; border:1px solid black }

/* Trennzeile in list.php */
td.separatorLine { font-weight:bold }

/* Tabs */
#tab_bar,#tab_bar tbody,#tab_bar td { border:0px solid black;padding:0px;margin:0px }

/* interner Link:margin-right:6px; */
a.tab_light { background:transparent url('lib/tab_blue_right.png') no-repeat scroll top right;display:block;float:left;height:18px;margin-right:2px;
margin-bottom:2px;
padding-right:11px;text-decoration:none}
a.tab_light span {background:transparent url('lib/tab_blue.png') no-repeat;display:block;line-height:14px;padding: 3px 0px 1px 11px;color:black}

a.tab_light:hover,a.tab_ext:hover { background:transparent url('lib/tab_blue_over_right.png') no-repeat scroll top right; }
a.tab_light:hover span,a.tab_ext:hover span { background:transparent url('lib/tab_blue_over.png') no-repeat;color:white }

/* externer Link: margin-right:6px; */
a.tab_ext { background:transparent url('lib/tab_red_right.png') no-repeat scroll top right;display:block;float:left;height:18px;margin-right:2px;
margin-bottom:2px;
padding-right:11px;text-decoration:none}
a.tab_ext span {background:transparent url('lib/tab_red.png') no-repeat;display:block;line-height:14px;padding: 3px 0px 1px 11px;color:black}

a.tab_ext:hover,a.tab_ext:hover { background:transparent url('lib/tab_red_over_right.png') no-repeat scroll top right; }
a.tab_ext:hover span,a.tab_ext:hover span { background:transparent url('lib/tab_red_over.png') no-repeat;color:white }

/* aktiver Link: margin-right:6px; */
a.tab_selected { background:transparent url('lib/tab_blue_selected_right.png') no-repeat scroll top right;display:block;float:left;height: 20px;margin-right: 2px;padding-right: 11px;text-decoration: none}
a.tab_selected span {background:transparent url('lib/tab_blue_selected.png') no-repeat;font-weight:bold;display:block;line-height:14px;padding: 3px 0px 3px 11px;color:black}

ul.compare_rxn { padding-left:12px;margin:0px }

a.button_very_small { border:1px solid black;background-color:white;margin:1px;padding:1px } /* mal noch etwas schöner machen */
a.button_very_small:hover { border:2px solid black;margin:0px }

/* edit_reaction */
.text_ansatzzettel { font-size:18pt;text-align:center }
.lj_code { font-size:18pt }
tr.block_head > td {vertical-align:top;border-bottom:1px solid black !important;padding:2px;margin:0px }

/* #reaction_table { position:relative }  ;left:0mm;top:15mm;width:176mm */

.overlayMenu { position:absolute;background-color:".defBgColor.";border:1px solid black;padding:1px;overflow:auto;z-index:5 }
#printMenu,#exportMenu { position:absolute;top:38px;right:16px;width:auto;background-color:".defBgColor.";border:1px solid black;padding:8px;overflow:auto;z-index:5 }
#listOptions { position:absolute;width:auto;background-color:".defBgColor.";border:1px solid black;padding:8px;overflow:auto;z-index:5 }
".
"table.hidden td { padding:0px; margin:0px; border-collapse:collapse }
thead { font-weight:bold }

". // groß, wie Name bei Gebinde
" .formTitle { font-size:24pt }
". // groß im Barcode-Terminal
" .barcodeBig { font-size:18pt }
";
	echo "
label.trans { margin:1px;padding:1px }
input.trans,select.trans,textarea.trans { margin:1px;padding:1px;background-color:".$inputBg.";border:0px solid black }
input.trans,select.trans { vertical-align:middle; }

input.small_input,select.small_input,textarea.small_input { vertical-align:middle; margin:3px; font-size:9pt; padding:0px; margin:0px }
#idx { border:0px solid black }
td.numeric { text-align:right }
td.measured { font-weight:bold }
img { vertical-align:middle }

table.rxnlabel { border-collapse:collapse }
table.rxnlabel > * > tr > td { border:1px solid black; padding:1px;font-size:7pt}
table.rxnlabel > * > tr > td.big { font-size:8pt}
.medium_small { font-size:8pt;line-height:8.5pt;}
.small { font-size:6pt;line-height:6.5pt;}

span.analytical_data_interpretation { display:block;max-width:100%;max-height:5cm;overflow:auto }

table.diagram_table { table-layout:fixed;border-collapse:collapse }
table.diagram_table td { border:1px solid black;vertical-align:top }

table.diagram { table-layout:fixed }
td.diagram { padding:0;margin:0;font-size:1px;line-height:1px }

/* HTML-Diff */
table.diff { width:100% }
table.diff > * > tr > td { vertical-align:top;width:50% }
.diff em { background-color:lightblue;font-style:normal }
.diff em * { background-color:lightblue }
";
}
?>
