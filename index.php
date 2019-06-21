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
// Umleitungsseite zur Vermeidung von POSTDATA-Refreshs
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

require_once "lib_global_funcs.php";
require_once "lib_constants.php";
require_once "lib_formatting.php";
require_once "lib_global_settings.php";
$barcodeTerminal=true;
pageHeader();

if ($_REQUEST["autoclose"]=="true") {
	echo script."
if (opener) {
	opener.location.reload();
}
self.close();
"._script."
</head>
<body>
</body>
</html>";
}
else {
	$redirURL=getLoginURL();
	
	echo script;
	$redir_cmd="self.location.href=".fixStr($redirURL).";";
	
	if (!getSetting("no_win_open_on_start")) {
		echo "
childWin=window.open(".fixStr($redirURL).",\"oeWin\",\"fullscreen=yes,location=no,menubar=no,scrollbars=yes,status=no,toolbar=no,resizable=yes\");
if (!childWin) { // popup-blocker etc
	".$redir_cmd."
}";
	}
	else {
		echo $redir_cmd;
	}
	echo _script.
"<title>".
s("opening").
"</title>
</head>
<body>".
s("redirect1").
"<a href=".fixStr($redirURL).">".$redirURL."</a>".
s("redirect2").
"<br><br>
".s("licence")."
</body>
</html>";
}
completeDoc();
?>