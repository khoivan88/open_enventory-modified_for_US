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
// Daten von Anbietern lesen
require_once "lib_global_settings.php";
require_once "lib_supplier_scraping.php";
$page_type="async";
$barcodeTerminal=true;

pageHeader();

echo "</head>
<body>
".script."
if (parent && parent!=self) {
";

if ($_REQUEST["editMode"]=="false" && empty($_REQUEST["ignore"])) {
	list($code,$text)=checkDuplicateCAS($_REQUEST["cas_nr"],$_REQUEST["pk_exclude"]);
	if ($code==2) {
		echo "parent.setiHTML(\"feedback_message\",".fixStr($text).");
	if (confirm(".fixStr(strip_tags($text).s("continue_anyway")).")) {
		self.location.href+=\"&ignore=true\";
	}
	else {
		parent.readExtFeedback();
	}
}
"
._script."</body></html>";
		exit();
	}
}

$molecule=array();
$molecule["cas_nr"]=$_REQUEST["cas_nr"];
$paramHash=array("pk_exclude" => $_REQUEST["pk_exclude"]);
getAddInfo($molecule,true,$paramHash);
$molecule["molfile_blob"]=addPipes($molecule["molfile_blob"]);
unset($molecule["price"]); // do not transfer price

// print_r($molecule);

foreach($molecule as $key=>$value) {
	switch ($key) {
	case "molecule_name":
	case "molecule_names":
	case "molecule_names_array":
	continue 2;
	case "molecule_names_edit":
		// $key="molecule_names";
		$value=addcslashes($value,"\"\'\n\r\t");
	break;
	case "mp_high":
		$value=joinIfNotEmpty(array($molecule["mp_low"],$molecule["mp_high"]),"-");
	break;
	case "bp_high":
		$value=joinIfNotEmpty(array($molecule["bp_low"],$molecule["bp_high"]),"-");
	break;
	}
}

//~ print_r($molecule);die();
echo "parent.setControlValues(".json_encode($molecule).",false,true);
	parent.updateSafety(0);
	parent.valChanged();
	parent.showObj(\"btn_create\");
	parent.readExtFeedback(".fixStr(s("readExtComplete")).");
	if (parent.readExtTimeout) {
		parent.clearTimeout(parent.readExtTimeout);
	}
}
"._script."
</body>
</html>";
?>