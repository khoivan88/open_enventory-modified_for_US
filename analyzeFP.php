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
require_once "lib_db_query.php";
require_once "lib_gd_common.php";

// login
pageHeader(true,false,true,false);

// create image in the desired/required size
$offset=1;
$scale=2;
$rect=$scale-1;
$bit_count=fingerprint_count*fingerprint_bit; // one line frequency
$pixel=($bit_count+$offset)*$scale; // one line frequency
$format="png";
$im=imgcreate($pixel,$pixel,$format);
$blue=colorAlloc($im,0,0,255,$format);
$green=colorAlloc($im,0,255,0,$format);

// create sum array
$freq=array(); // probabilty for $a
$corr_sums=array_fill(0,$bit_count,array()); // probabilty if a than b
$item_count=0;

// what table, dbs?
switch ($_REQUEST["table"]) {
	case "molecule":
	case "reaction_chemical":
		$table="fingerprint_".$_REQUEST["table"];
	break;
	default:
		exit();
}

// query, add correlation sums
$start=0;
$inc=5000;
do {
	set_time_limit(60);
	
	$results=mysql_select_array(array(
		"table" => $table, 
		"dbs" => $_REQUEST["dbs"], 
		"limit" => $start.",".$inc, 
	));
	
	for ($a=0;$a<count($results);$a++) {
		for ($b=0;$b<fingerprint_count;$b++) {
			$val=$results[$a]["fingerprint".($b+1)];
			$coord=$b*fingerprint_bit; // $coord ist immer >= $coord2
			for ($c=0;$c<fingerprint_bit;$c++) {
				$mask=(1 << $c);
				if (($val & $mask)==$mask) {
					$freq[$coord]++;
					for ($b2=0;$b2<=$b;$b2++) {
						$val2=$results[$a]["fingerprint".($b2+1)];
						$coord2=$b2*fingerprint_bit;
						for ($c2=0;$c2<=$c;$c2++) {
							$mask2=(1 << $c2);
							if (($val2 & $mask2)==$mask2) {
								$corr_sums[$coord][$coord2]++;
							}
							$coord2++;
						}
					}
				}
				$coord++;
			}
		}
	}
	$item_count+=count($results);
	
	if (count($results)<$inc) {
		break;
	}
	
	$start+=$inc;
} while (true);

// output image
for ($a=0;$a<$bit_count;$a++) { // a ist immer >=b
	// freq
	$a1=$scale*$a;
	$a2=$a1+$rect;
	if (isset($corr_sums[$a][$b])) {
		$color=getGrey($im,$freq[$a]/$item_count,$format);
		$color2=$color;
	}
	else {
		$color=$blue;
		$color2=$green;
	}
	imgfilledrectangle($im,$a1,0,$a2,$rect,$color,$format);
	imgfilledrectangle($im,0,$a1,$rect,$a2,$color2,$format);
	
	// corr
	for ($b=0;$b<=$a;$b++) {
		$a1=$scale*($a+$offset);
		$b1=$scale*($b+$offset);
		$a2=$a1+$rect;
		$b2=$b1+$rect;
		if (isset($corr_sums[$a][$b])) {
			$color=getGrey($im,$corr_sums[$a][$b]/$freq[$a],$format);
			$color2=getGrey($im,$corr_sums[$a][$b]/$freq[$b],$format);
		}
		else {
			$color=$blue;
			$color2=$green;
		}
		imgfilledrectangle($im,$a1,$b1,$a2,$b2,$color,$format);
		imgfilledrectangle($im,$b1,$a1,$b2,$a2,$color2,$format);
	}
}

$mime=getMimeFromExt($format);
header(getHeaderFromMime($mime));
ImagePng($im);

?>