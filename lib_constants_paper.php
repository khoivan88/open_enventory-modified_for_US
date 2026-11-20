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
Konstanten wie Berechtigungs-Bitmasken, Aufzählungen von Gefahrsymbolen und SET-Namen sowie die Reihenfolge und Anzeigeeigenschaften von 
Tabellenspalten
*/

$rand["w"]=30; // 10
$rand["h"]=60;
$paperFormats=array( // alles mm
	"A4" => array("w" => 210, "h" => 297, ),
	"A4 Landsc." => array("w" => 297, "h" => 210, ),
);

$label_dimensions=array(
	"no_barcode" => array(
		"types" => array(
			"small" => array("size" => 1, "per_row" => 3, "per_col" => 7, "img" => "labels3x7_sm.png"), 
			"medium" => array("size" => 1.4, "per_row" => 2, "per_col" => 5, "img" => "labels2x5_sm.png"), 
			"large" => array("size" => 1.8, "per_row" => 1, "per_col" => 4, "img" => "labels1x4_sm.png") 
		)
	), 
	"barcode" => array(
		"lang_key" => "with_barcode",
		"types" => array(
			"small" => array("lang_key" => "with_barcode", "size" => 0.95, "per_row" => 2, "per_col" => 7, "parameter" => "barcode=true", "img" => "labels2x7_bar_sm.png"), 
			"medium" => array("lang_key" => "with_barcode", "size" => 1.4, "per_row" => 2, "per_col" => 5, "parameter" => "barcode=true", "img" => "labels2x5_bar_sm.png"), 
			"large" => array("lang_key" => "with_barcode", "size" => 1.7, "per_row" => 1, "per_col" => 4, "parameter" => "barcode=true", "img" => "labels1x4_bar_sm.png") 
		)
	)
);


?>