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
require_once "lib_simple_forms.php";
require_once "lib_applet.php";

pageHeader();

echo stylesheet.
style.
getFixedStyleBlock().
_style.
loadJS(array("edit.js"),"lib/").
script."
var readOnly=false;
activateSearch(false);
"._script."
</head>
<body>
<div id=\"browsenav\">".
showGroup("spz_macro",1).
"</div>
<div id=\"browsemain\">
<p>".s("spz_macro_is")."</p>".
"<p>".s("spz_macro_windows1")."</p>".
"<ul>
<li>HP Chemstation</li>
<li>ACD/NMR</li>
<li>Bruker Topspin</li>
<li>Bruker WinNMR (1D)</li>
<li>MestreNova</li>
<li>SpinWorks (3.1.6+)</li>
<li>AMDIS</li>
<li>".s("spz_macro_general")."</li>
</ul>

<p>".s("spz_macro_download1")." <a href=\"macro_installer.exe\">".s("spz_macro_download1a")."</a></p>
<p>".s("spz_macro_download3")."</p>
<small>".s("lic_no_liab")."</small>

<hr>

".s("spz_macro_linux1").
"<ul>
<li>MestreNova</li>
<li>".s("spz_macro_general")."</li></ul>
<p>".s("spz_macro_linux2")."</p>
<ul>
<li>Tcl8.5</li>
<li>Tk</li>
<li>libtk-img</li>
</ul>

<p><a href=\"spz_macro.tcl\">spz_macro.tcl</a></p>

<small>".s("lic_no_liab")."</small>

</div>
</body>
</html>";

completeDoc();
?>