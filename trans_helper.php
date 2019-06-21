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
pageHeader();

// load all langs
$langs=array_keys($localizedString);
foreach($langs as $key) {
	require_once "lib_language_".$key.".php";
}

$langKeys=array();
$allLangKeys=array();
foreach ($langs as $key) {
	// get keys
	$langKeys[$key]=array_keys($localizedString[$key]);
	// get all merged
	$allLangKeys=arr_merge($allLangKeys,$langKeys[$key]);
}
$allLangKeys=array_unique($allLangKeys);

echo "<link href=\"style.css.php\" rel=\"stylesheet\" type=\"text/css\">
</head><body><table class=\"listtable\"><thead><tr><td>key</td>";

foreach ($langs as $lang_key) {
	echo "<td>".$lang_key."</td>";
}

echo "</tr></thead><tbody>";

foreach ($allLangKeys as $key) {
	echo "<tr><td>".$key."</td>";
	foreach ($langs as $lang_key) {
		if (!isset($localizedString[$lang_key][$key])) {
			echo "<td style=\"background-color:red\"></td>";
		}
		elseif (is_array($localizedString[$lang_key][$key])) {
			echo "<td><pre>".print_r($localizedString[$lang_key][$key],true)."</pre></td>";
		}
		else {
			echo "<td>".$localizedString[$lang_key][$key]."</td>";
		}
	}
	echo "</tr>";
}
echo "</tbody></table>";

?>