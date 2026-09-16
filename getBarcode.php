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
require_once "lib_formatting.php";
require_once "lib_gd_common.php";

// Parameter: text (ohne Prüfziffer), width (px), height (px, wird auf Vielfaches von 81 gebracht)
$border_ratio_h=0.05;
$font_ratio_h=0.25;
$offset_v=8;

$ean13Mods=array(
	0,
	52,
	44,
	28,
	50,
	38,
	14,
	42,
	26,
	22
);

//~ 0 (UPC-A)  	Odd  	Odd  	Odd  	Odd  	Odd  	Odd
//~ 1 	Odd 	Odd 	Even4 	Odd 	Even16 	Even32
//~ 2 	Odd 	Odd 	Even4 	Even8 	Odd 	Even32
//~ 3 	Odd 	Odd 	Even4 	Even8 	Even16 	Odd
//~ 4 	Odd 	Even2 	Odd 	Odd 	Even16 	Even32
//~ 5 	Odd 	Even2 	Even4 	Odd 	Odd 	Even32
//~ 6 	Odd 	Even2 	Even4 	Even8 	Odd 	Odd
//~ 7 	Odd 	Even2 	Odd 	Even8 	Odd 	Even32
//~ 8 	Odd 	Even2 	Odd 	Even8 	Even16 	Odd
//~ 9 	Odd 	Even2 	Even4 	Odd 	Even16 	Odd

$eanCodes=array(
	"0001101", 
	"0011001", 
	"0010011", 
	"0111101", 
	"0100011", 
	"0110001", 
	"0101111", 
	"0111011", 
	"0110111", 
	"0001011"
);

$guard="101";
$center="01010";

function getEANdigit($digit,$parity) { // parity: 0: EAN8 left/EAN13 left odd; 1: EAN8 right/EAN13 right; 2: EAN13 even
	global $eanCodes;
	if ($parity==0) {
		return $eanCodes[$digit];
	}
	$retval=str_replace(array("1","0","2"),array("2","1","0"),$eanCodes[$digit]); // invert
	if ($parity==1) {
		return $retval;
	}
	elseif ($parity==2) {
		return strrev($retval);
	}
}

function getEAN8Bitstream(& $num) { // gibt Zeichenkette von 0 und 1, 56+6+5=67 Zeichen lang, wir lassen nach jeder Seite 7 Rand, macht 81 Einheiten
	global $guard,$center;
	if ($_REQUEST["preform"]!="true") {
		$num=getEAN($num,8).""; // Prüfziffer
	}
	$retval=$guard;
	for ($a=0;$a<4;$a++) {
		$retval.=getEANdigit($num{$a},0);
	}
	$retval.=$center;
	for ($a=4;$a<8;$a++) {
		$retval.=getEANdigit($num{$a},1);
	}
	$retval.=$guard;
	return $retval;
}

function getEAN13Bitstream(& $num) { // gibt Zeichenkette von 0 und 1, 84+6+5=95 Zeichen lang, wir lassen nach jeder Seite 7 Rand, macht 109 Einheiten
	global $ean13Mods,$guard,$center;
	if ($_REQUEST["preform"]!="true") {
		$num=getEAN($num,13).""; // Prüfziffer
	}
	$retval=$guard;
	$ean13Mod=$ean13Mods[$num{0}];
	for ($a=1;$a<7;$a++) {
		$mask=1<<($a-1);
		//~ echo $mask."-".($ean13Mod & $mask)."=>".$num{$a}." ";
		$retval.=getEANdigit($num{$a},(($ean13Mod & $mask)==0?0:2));
	}
	$retval.=$center;
	for ($a=7;$a<13;$a++) {
		$retval.=getEANdigit($num{$a},1);
	}
	$retval.=$guard;
	return $retval;
}

if ($_REQUEST["horizontal"]=="true") {
	$horizontal=true;
	swap($_REQUEST["width"],$_REQUEST["height"]);
}

$num=$_REQUEST["text"]; // Prüfsumme hinzufügen
$width=constrainVal($_REQUEST["width"],20,500);
$left=$width*$border_ratio_h;
$right=$width*(1-$border_ratio_h);

$height=constrainVal($_REQUEST["height"],20,500);

$length=81;
$format=strtolower($_REQUEST["format"]);
if ($format=="ean13") {
	$length=109;
}

$height+=$length-($height%$length);
$line_width=$height/$length;

// create img
if ($horizontal) {
	swap($width,$height);
}
$im=imagecreate($width,$height);
$white=ImageColorAllocate($im,255,255,255);
//~ $red = ImageColorAllocate ($im,255,0,0);
$black=ImageColorAllocate($im,0,0,0); 
//~ die($width." ".$height);
//~ imagefilledrectangle($im,0,0,$width,$height,$red);

// create bitstream 1=black
if ($format=="ean13") {
	$bits=getEAN13Bitstream($num);
	$half_block_width=45;
	$start=1;
	$middle=7;
	$end=13;
	$text_offset=-3;
}
else {
	$bits=getEAN8Bitstream($num);
	$half_block_width=31;
	$start=0;
	$middle=4;
	$end=8;
	$text_offset=3+3;
}
//~ die($line_width."X".$bits.strlen($bits));

if ($horizontal) { // 0.5 works, is 0.4 better, at least for vertical?
	$extend=0.5;
}
else {
	$extend=0.4;
}

// go through lines and draw black lines
for ($a=0;$a<strlen($bits);$a++) {
	if ($bits{$a}=="1") {
		$x1=($a+$offset_v)*$line_width;
		$x2=($a+$offset_v+$extend*$line_width)*$line_width;
		if ($horizontal) {
			imagefilledrectangle($im,$x1,$left,$x2,$right,$black);
		}
		else {
			imagefilledrectangle($im,$left,$x1,$right,$x2,$black);
		}
	}
}

// write digits
// 1-4
$x1=(3+$offset_v)*$line_width;
$x2=($half_block_width+$offset_v)*$line_width;
if ($horizontal) {
	imagefilledrectangle($im,$x1,$height,$x2,$height*(1.0-$font_ratio_h),$white);
}
else {
	imagefilledrectangle($im,0,$x1,$width*$font_ratio_h,$x2,$white);
}

if ($format=="ean13") {
	$a=0;
	$x1=(7*$a+$text_offset+$offset_v)*$line_width;
	if ($horizontal) {
		drawText($im,$x1,$height,$black,$num{$a});
	}
	else {
		drawTextDown($im,0,$x1,$black,$num{$a});
	}
}

for ($a=$start;$a<$middle;$a++) {
	$x1=(7*$a+$text_offset+$offset_v)*$line_width;
	if ($horizontal) {
		drawText($im,$x1,$height,$black,$num{$a});
	}
	else {
		drawTextDown($im,0,$x1,$black,$num{$a});
	}
}

$x1=($half_block_width+6+$offset_v)*$line_width;
$x2=($half_block_width*2+3+$offset_v)*$line_width;
// 5-8
if ($horizontal) {
	imagefilledrectangle($im,$x1,$height,$x2,$height*(1.0-$font_ratio_h),$white);
}
else {
	imagefilledrectangle($im,0,$x1,$width*$font_ratio_h,$x2,$white);
}

for ($a=$middle;$a<$end;$a++) {
	$x1=(7*$a+$text_offset+6+$offset_v)*$line_width;
	if ($horizontal) {
		drawText($im,$x1,$height,$black,$num{$a});
	}
	else {
		drawTextDown($im,0,$x1,$black,$num{$a});
	}
}

// output
header("Content-Type: image/gif");
ImageGif($im);
?>