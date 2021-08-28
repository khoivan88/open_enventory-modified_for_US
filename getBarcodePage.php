<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Strict//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
</head>
<body>
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

$scale=0.95;
$prefix=$_REQUEST["prefix"];
$start=intval($_REQUEST["start"]);
$per_row=intval($_REQUEST["per_row"]);
if ($per_row<1) {
	$per_row=7;
}
$per_col=intval($_REQUEST["per_col"]);
if ($per_col<1) {
	$per_col=27;
}

// 1st label
$top_mm=floatval($_REQUEST["top_mm"]);
$left_mm=floatval($_REQUEST["left_mm"]);

// each label
$down_mm=floatval($_REQUEST["down_mm"]);
if ($down_mm<0) {
	$down_mm=3;
}

$right_mm=floatval($_REQUEST["right_mm"]);
if ($right_mm<0) {
	$right_mm=27;
}

$barcode_height_mm=floatval($_REQUEST["barcode_height_mm"]);
if ($barcode_height_mm<=0) {
	$barcode_height_mm=8.5; // 9.5;
}

$barcode_width_mm=floatval($_REQUEST["barcode_width_mm"]);
if ($barcode_width_mm<=0) {
	$barcode_width_mm=25;
}

$format=$_REQUEST["format"];
$down=($_REQUEST["down"]?true:false);
// $xscale=floatval($_REQUEST["xscale"]);
// $yscale=floatval($_REQUEST["yscale"]);

echo "<table cellspacing=\"0\" cellpadding=\"0\" style=\"table-layout:fixed;border-collapse:collapse;border-spacing:0mm;top:".$top_mm."mm;left:".$left_mm."mm;border-width:0px\">";
$num=$start;
for ($a=0;$a<$per_col;$a++) {
	echo "<tr>";
	for ($b=0;$b<$per_row;$b++) {
		echo "<td style=\"padding:".($barcode_height_mm*(1-$scale))."mm ".($barcode_width_mm*(1-$scale))."mm;margin:0px\"><img src=\"getBarcode.php?text=".$prefix.$num."&format=".$format."&down=".$down."\" style=\"width:".($barcode_width_mm*$scale)."mm;height:".($barcode_height_mm*$scale)."mm\"></td><td style=\"width:".$right_mm."mm\"></td>";
		$num++;
	}
	echo "</tr><tr><td colspan=\"\" style=\"height:".$down_mm."mm\"></td></tr>";
}
echo "</table>";
?>
</body>
</html>