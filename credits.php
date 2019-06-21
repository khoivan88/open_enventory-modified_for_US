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
require_once "lib_simple_forms.php";

pageHeader();
echo stylesheet.
style.
getFixedStyleBlock().
_style."
</head><body>
<div id=\"browsenav\">".
showGroup("credits",1).
"</div>
<div id=\"browsemain\">
<table class=\"noborder\" style=\"width:100%\"><tr><td><img src=\"lib/uni-logo.gif\"></td><td><img src=\"lib/chemielogo.gif\"></td><td><img src=\"lib/fci.jpg\"></td><td><img src=\"lib/stusti_logo.gif\"></td><td><img src=\"lib/agplv3-155x51.png\"></td></tr></table>
<h1><img src=\"lib/open_env_logo.png\" border=\"0\" height=\"58\" width=\"454\"> Credits</h1>
<ul>"
."<li>".s("published_as_agpl")."</li>"
."<li>".s("developed_by")."Prof. Dr. Lukas Gooßen".s("and")."<a href=\"mailto:fr@sciformation.com\">Dr. Felix Rudolphi</a></li>"
."<li>".s("trademark_of")."Felix Rudolphi".s("and")."Lukas Gooßen</li>"
."<li>".s("programmed_by")."Felix Rudolphi".s("and")."Thorsten Bonck</li>"
."<li>".s("in_memory_of1")." Dr. Klaus Angermund".s("in_memory_of2")."</li>"
."<li>".s("sponsored_by")."Fonds der Chemischen Industrie</li>"
."<li>".s("structure_applet_by")."Otmar Ginkel</li>"
."<li>".s("contains_portions_by")."</li>"
."<li>".s("3rd_party_sw")."<ul>
<li>ChemDoodle (GPL)</li>
<li>JChemPaint (LGPL)</li>
<li>Ketcher (AGPL)</li>
<li>SketchEl (GPL)</li>
<li>File_Archive (LGPL)</li>
<li>jodconverter (LGPL)</li>
<li>MIME_Type (LGPL)</li>
<li>Spreadsheet_Excel_Writer (LGPL)</li>
<li>vectorgraphics (LGPL)</li>
<li>wyzz (LGPL)</li>
</ul>".s("3rd_party_sw_terms")."</li>"
."<li>".s("help_by")."</li>"
."<li>".s("french_by")."</li>"
."<li>".s("spanish_by")."</li>"
."<li>".s("italian_by")."</li>"
."<li>".s("portuguese_by")."</li>"
."<li>".s("TU_logos1")."TU Kaiserslautern".s("TU_logos2")."TU Kaiserslautern</li>"
."<li>".s("other_brand")."</li>" // 
."<li>".s("artwork_by1")."Felix Rudolphi</li>" // all icons except print were created by Felix Rudolphi Copyright 2006-2016
."<li>open enventory".s("developed_on1")."Firefox 3.5 <a href=\"http://www.getfirebug.com/?link=3\" title=\"Firebug is a free web development tool for Firefox\"><img src=\"http://www.getfirebug.com/images/firebug3.jpg\" border=\"0\" alt=\"Firebug - Web Development Evolved\"/></a>".s("developed_on2")."Google Chrome".s("and")."Microsoft Internet Explorer 7".s("developed_on3")."</li>"
."<li>".s("developed_using1")."SciTE (FR), Eclipse (OG,TB), Inkscape (FR), The GIMP (FR) ".s("and")." SVN ".s("developed_using2")." Ubuntu 9.10, Apache 2, PHP 5, MySQL 5.0 ".s("and").s("at_the_beginning")." XAMPP/Microsoft Windows 2003 Server".s("developed_using3")."</li>"
."<li>".s("documentation1")."Felix Rudolphi".s("documentation2")."open office 2".s("documentation3")."</li>"
."<li>".s("FR_thanks")."</li>"
."<li>".s("special_thanks1")."Ben ".s("and")." Alfons Höynck".s("special_thanks2")."</li>"
."<li>".s("legal_warning")."</li>
</ul>
<a href=\"getSrc.php\">".s("download_source")."</a><br>
".
s("oe_version").currentVersion."<br>".
s("oe_db_version").getGVar("Version")."<br>";

if (function_exists("apache_get_version")) {
	echo s("apache_version").apache_get_version()."<br>";
}

echo s("php_version").phpversion()."<br>
".s("mysql_version").mysqli_get_server_info($db);

phpcredits(CREDITS_GENERAL+CREDITS_GROUP);

echo "</div></body></html>";
completeDoc();
?>