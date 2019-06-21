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
	
require_once "lib_supplier_scraping.php";
require_once "lib_global_funcs.php";
require_once "lib_constants.php";

pageHeader();

echo "</head>
	<body>".
	script;
// GetInfo
// $step=getStepFromSupplierCode($_REQUEST["supplier"]);
// print_r($suppliers);
// print_r($_REQUEST);
$supplier_obj=& $suppliers[$_REQUEST["supplier"]];

if (function_exists($supplier_obj["getPrices"])) {
	$result=$supplier_obj["getPrices"]($_REQUEST["extCatNo"]);
}
elseif (function_exists($supplier_obj["getInfo"])) {
	$result=$supplier_obj["getInfo"]($_REQUEST["extCatNo"]);
}
// create info text/list
if (count($result["price"])>0) {
	// menge,description (if applies), price+currency
	$prices_text.=displayPrice($result,$supplier_obj["catalogHierarchy"],$supplier_obj["hasPriceList"]);
	
	// write it to the correct span in parent
	echo "var parentEl=parent.$(".fixStr($_REQUEST["supplier"]."_".$_REQUEST["id"]).");
if (parentEl) {
	parentEl.innerHTML=".fixStr($prices_text).";
}";
}
echo _script."</body>
</html>";
?>