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
// Seite zum Ändern des Kennworts
require_once "lib_global_funcs.php";
require_once "lib_db_manip.php";
require_once "lib_simple_forms.php";
pageHeader();
echo "<title>".s("change_pw")."</title>".
stylesheet.
style.
getFixedStyleBlock().
_style.
loadJS(array("controls.js","jsDatePick.min.1.3.js","forms.js","edit.js"),"lib/").
script."
function prepareSubmit() {
	var obj=$(\"new_password\"),obj2=$(\"new_password_repeat\");
	if (!obj || !obj2) {
		return false;
	}
	if (obj.value!=obj2.value) {
		alert(s(\"password_dont_match\"));
		return false;
	}
	if (obj.value==\"\") {
		alert(s(\"password_none\"));
		return false;
	}
	return checkPass(obj.value,".fixStr($db_user).",\"new_password\",false);
}
</script>
</head>
<body>
<div id=\"browsenav\">";
handleNewPassword();
echo "</div>
<div id=\"browsemain\">
<form name=\"main\" id=\"main\" method=\"post\" onSubmit=\"return prepareSubmit();\">";

echo showHidden(array("int_name" => "person_id", "value" => $person_id))
.showInput(array("int_name" => "new_password","type" => "password", "size" => 20,"maxlength" => 50, "noAutoComp" => true));

echo "<input type=\"submit\" name=\"setNewPw\" value=\"".s("change_pw")."\">".
script."
	focusInput(\"new_password\");
</script>
</form>
</div>
</body>
</html>";
completeDoc();
?>