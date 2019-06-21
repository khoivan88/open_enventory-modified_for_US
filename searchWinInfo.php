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
require_once "lib_simple_forms.php";
pageHeader();

echo <<<END
<link href="style.css.php" rel="stylesheet" type="text/css">
</head><body>
END;
switch ($_REQUEST["tableSelect"]) {
case "chemical_storage,molecule":
case "molecule,chemical_storage":
	$text1=s("info_search_molecule1");
	$text2=s("info_search_molecule2");
break;
case "reaction,reaction_chemical":
	$text1=s("info_search_reaction1");
	$text2=s("info_search_reaction2");
break;
case "literature":
	$text1=s("info_search_literature1");
	$text2=s("info_search_literature2");
break;
case "supplier_offer,molecule":
	$text1=s("info_search_supplier_offer1");
	$text2=s("info_search_supplier_offer2");
break;
}
echo $text1."<img src=\"lib/select_sm.png\" border=\"0\" class=\"imgButtonSm\"".getTooltip("do_select").">".$text2."</body></html>";
completeDoc();
?>