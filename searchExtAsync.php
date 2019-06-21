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
require_once "lib_constants.php";
require_once "lib_db_query.php";
require_once "lib_formatting.php";

$page_type="async";
pageHeader(true,true,false);

require_once "lib_supplier_scraping.php";

echo "
</head>
<body>
".script."
var res=parent.$(\"results\");\n";

// print_r($_REQUEST);
$cache=readCache($_REQUEST["cached_query"]);
// print_r($cache);
if ($cache["supplier"]=="all") {
	$step=intval($_REQUEST["step"]);
}
else {
	$step=getStepFromSupplierCode($cache["supplier"]);
}
$code=$steps[$step];

if (in_array($cache["filter_obj"]["crits"][0],$ext_crits) && !isEmptyStr($cache["filter_obj"]["vals"][0][0])) {
	if (!is_array($cache["external_results"][$step])) { // daten holen
		$search=& $cache["filter_obj"]["vals"][0][0];
		$filter=& $cache["filter_obj"]["crits"][0];
		$mode=& $cache["filter_obj"]["ops"][0];
		$cache["external_results"][$step]=$suppliers[$code]["getHitlist"]($search,$filter,$mode);
		gcCache();
		$_REQUEST["cached_query"]=writeCache($cache,$_REQUEST["cached_query"]);
	}
	echo "var newElement=parent.document.createElement(\"div\");
newElement.innerHTML+=\"".addslashes( getExtResultList($cache["external_results"][$step],$step,array("step" => $step)) )."\";
res.appendChild(newElement);";
	if ($cache["supplier"]=="all") {
		do {
			$step++;
		} while ($suppliers[$code]["noExtSearch"] && $step<count($steps));
		if ($step<count($steps)) {
			echo "self.location.href=\"searchExtAsync.php?".keepParams(array("cached_query"))."&step=".$step."\";\n";
		}
	}
}
else {
	echo "res.innerHTML+=\"".s("no_search_term")."\\n\";\n";	
}
echo _script."
</body>
</html>";
?>