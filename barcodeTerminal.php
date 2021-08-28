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
require_once "lib_form_elements.php";
require_once "lib_constants_barcode.php";
$barcodeTerminal=true;

setGlobalVars();
pageHeader();

function getAsyncField($id) {
	return "<input type=\"hidden\" name=".fixStr($id)." id=".fixStr("async_".$id).">";
}

echo "<title>".s("list_of_chemicals_title")." ".$g_settings["organisation_name"]."</title>".
stylesheet.
loadJs(array("wyzz.js")). // "dynamic.js.php",
loadJS(array("edit.js","chem.js","safety.js","safety_".$lang.".js","controls.js","jsDatePick.min.1.3.js","forms.js","folder_browser.js","literature.js","sds.js","molecule_edit.js","searchpk.js","subitemlist_helper.js","subitemlist_get.js","subitemlist.js","client_cache.js","barcode_terminal.js"),"lib/").
script."

var table=\"chemical_storage\",inventarisation_mode=false,delTimeout,normalInterval=".ifempty($barcode_terminal_options["normalDelay"], 60).",inventarisationInterval=".ifempty($barcode_terminal_options["inventarisationInterval"], 600).";

function closeTerminal() {
	self.location.href=\"index.php?".getSelfRef(array("~script~"))."&desired_action=logout\";
}

function loginActivePerson() {
	var password=prompt(s(\"enter_password1\")+username+s(\"enter_password2\"));
	if (password==null) {
		return;
	}
	$(\"db_name\").value=".fixStr($db_name).";
	setInputValue(\"user\",username);
	setInputValue(\"password\",password);
	$(\"loginForm\").submit();
	setInputValue(\"password\",\"\");
}
 
// make msecs
refreshInterval*=1000;
// personInterval*=1000;
doRefresh();
</script>
</head>
<body onKeypress=\"resetPersonInterval(); \">
<span id=\"temp\" style=\"display:none\"></span>";

showCommFrame(array("debug" => $debug));
showCommFrame(array("name" => "edit", "debug" => $debug, ));

echo getHelperTop()."
<form id=\"barcodeAsyncForm\" action=\"barcodeTerminalAsync.php\" target=\"edit\" method=\"post\">";
$async_fields=array("person_db_id","person_id","username","pk","desired_action","chemical_storage_conc","chemical_storage_conc_unit","actual_amount","amount","amount_unit","tmd","tmd_unit","storage_id","compartment","storage_permanent","chemical_storage_barcode","chemical_storage_bilancing","barcode","history_entry");
foreach ($async_fields as $async_field) {
	echo getAsyncField($async_field);
}

echo simpleHidden("sess_proof").<<<END
</form>

<form onSubmit="barcodeRead(getInputValue(&quot;barcode&quot;));return false">
<input id="barcode"> <span id="status"></span> 
<input type="button" id="btn_logout" onClick="setActivePerson()" onMouseup="focusInput(&quot;barcode&quot;)" value=
END
.fixStr(s("logout")).<<<END
> <span id="countdown"></span> <input type="button" id="btn_login" onClick="loginActivePerson()" value=
END
.fixStr(s("login")).<<<END
> <input type="button" id="btn_close" onClick="closeTerminal()" value=
END
.fixStr(s("close_terminal")).<<<END
>
</form>

<div id="message"></div>
<form id="loginForm" name="loginForm" method="post" action="main.php" target=_blank>
<input type="hidden" id="desired_action" name="desired_action" value="sub_login">
<input type="hidden" id="db_name" name="db_name" value="">
<input type="hidden" id="user" name="user" value="">
<input type="hidden" id="password" name="password" value="">
END
.simpleHidden("sess_proof").<<<END
</form>
<form name="main" id="main" action="barcodeTerminalAsync.php" method="post" target="edit" onSubmit="doInventar();return false">
END;

require_once "lib_edit_molecule.php";
require_once "lib_edit_chemical_storage.php";

echo showCheck(array(
		"int_name" => "inventarisation_mode", 
		"onChange" => "inventarisationMode(); ", 
		"noChangeEffect" => true, 
	)). // onClick: Timeout auf 600 sec, nicht ausleihen/zurückgeben
	showCheck(array(
		"int_name" => "storage_permanent", 
		"noChangeEffect" => true, 
	)). // onClick: Timeout auf 600 sec, nicht ausleihen/zurückgeben
	showChemicalStorageEditForm(array("text" => s("inventarisation"), "barcodeTerminal" => true, READONLY => false)).
	showMoleculeEditForm(array("text" => s("information_molecule"), DEFAULTREADONLY => "always", "no_db_id_pk" => true)).
	"</form>".
	getHelperBottom().
"<div id=\"message_log\">
</div>
<div style=\"height:1px;overflow:hidden\">";

if ($g_settings["barcode_sound"]) {
	$sounds=array("login","ausleihen","zurueckgeben","error",);
	foreach ($sounds as $sound) {
		echo "<embed id=\"snd_".$sound."\" src=\"lib/".$sound.".wav\" width=\"140\" height=\"60\" autoplay=\"0\" enablejavascript=\"true\" onFocus=\"focusInput(&quot;barcode&quot;);\">";
	}
}

echo "</div>".
script."

function barcodeRead(barcode) {
".getJSbarcodeHandling(true)."
	barcodeReadToServer(barcode);
}
inventarisationMode();
setActivePerson(new Array(),true);
window.setTimeout(function () { focusInput(\"barcode\"); },800); // take away focus from embedded media player
"._script."
</body>
</html>";

completeDoc();
?>
