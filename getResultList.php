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
require_once "lib_constants.php";
require_once "lib_global_funcs.php";
require_once "lib_form_elements.php";
require_once "lib_db_query.php";
require_once "lib_supplier_scraping.php";

pageHeader();

if (!empty( $_REQUEST["cached_query"] )) {
	// get query_obj
	$cache=readCache($_REQUEST["cached_query"]);
	$query_obj=$cache["filter_obj"];
	unset($cache);
}
else {
	$query_obj=getFilterObject(array());
}

// check if simple query
$query_num=getSimpleQuery($query_obj["query_pattern"]);
$request=array();
if ($query_num===FALSE) {
	$request["plain"]=s("simple_query_only");
}
else {
	$request=$suppliers[ $_REQUEST["supplier"] ]["requestResultList"]($query_obj);
}

switch ($request["method"]) {
case "url":
	echo "<head><meta http-equiv=\"refresh\" content=\"0; URL=".$request["action"]."\"></head><body>";
break;
case "post":
	echo "<body>".showCommFrame(); // allows to overcome cookie/language/etc. problems
	foreach ($request["forms"] as $num => $form_data) {
		$targetText="";
		if (isset($form_data["target"])) {
			$targetText=" target=".fixStr($form_data["target"]);
		}
		elseif ($num+1<count($request["forms"])) { // auto-activate comm
			$targetText=" target=\"comm\"";
		}
		echo "<form name=\"autosubmit".$num."\" action=".fixStr($form_data["action"])." method=\"post\"".$targetText.">";
		if (is_array($form_data["fields"])) foreach ($form_data["fields"] as $name => $value) {
			echo "<input type=\"hidden\" name=\"".$name."\" value=\"".$value."\">\n";
		}
		echo "</form>
".script."
window.setTimeout(\"document.autosubmit".$num.".submit();\",".(1500*$num).");
"._script;
	}
break;
case "scrape": // display list of results (NIST only due to special problems)
	echo "<head>
		<title>".s("scrape_tech_reasons")."</title>
		".stylesheet."</head>
		<body>".
		getExtResultList($request["results"],$request["supplier"],array("noAddButtons" => true));
break;
case "plain": // display list of results (NIST only due to special problems)
	echo "<body>".$request["plain"];
break;
}

echo "</body>
</html>";
completeDoc();
?>