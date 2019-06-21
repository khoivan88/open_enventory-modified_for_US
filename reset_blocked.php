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
Einstellungsseite, zZt nicht fertig und nicht eingebaut
a) global default_language, default_currency,Bessi_name
b) per User preferred_language, change_passwd
ich denke, wir sollten die Tabelle global_settings umbauen zu serialisiertem BLOB, nur die Text-Spalte bleibt
*/
require_once "lib_global_funcs.php";
require_once "lib_global_settings.php";
require_once "lib_brute_block.php";

pageHeader();

echo stylesheet.
style.
getFixedStyleBlock().
_style.
loadJS(array("client_cache.js","controls.js","jsDatePick.min.1.3.js","forms.js","molecule_edit.js","searchpk.js","subitemlist_helper.js","subitemlist_get.js","subitemlist.js","edit.js"),"lib/").
script."
readOnly=false;
editMode=false;

activateSearch(false);
"._script."
</head>
<body>
<div id=\"browsenav\"></div>
<div id=\"browsemain\">";

if ($db_user==ROOT) {
	resetProtocol();
	echo s("block_list_reset");
}
else {
	echo s("permission_denied");
}

echo "
</div>
</body>
</html>";

completeDoc();
?>