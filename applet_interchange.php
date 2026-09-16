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

// show all applets and try loading and acquiring of mol/rxn files

require_once "lib_global_funcs.php";
require_once "lib_db_query.php";
require_once "lib_applet.php";
require_once "lib_form_elements.php";

pageHeader();

$paramHash=array(
	"int_name" => "testform", 
	READONLY => false, 
);

echo stylesheet.
style.
getFixedStyleBlock().
_style.
loadJS(array("chem.js","safety.js","controls.js","jsDatePick.min.1.3.js","forms.js","folder_browser.js","literature.js","sds.js","molecule_edit.js","searchpk.js","subitemlist_helper.js","subitemlist_get.js","subitemlist.js","client_cache.js","edit.js"),"lib/").
script."
var readOnly,selectActive,editMode;
".
_script.
"<body>";

showCommFrame(); // for molecule and pk_search
showCommFrame(array("name" => "edit")); // for async ops (reload data, save data)
copyPasteAppletHelper(array("mode" => $_REQUEST["mode"], ));

echo getHelperTop()."<form name=\"appletTest\" onSubmit=\"return false;\">".
getFormElements($paramHash,array(
	array("item" => "applet", "force" => "JME", "int_name" => "JME", "mode" => $_REQUEST["mode"], "searchMode" => false, "copyPasteButtons" => true, "width" => "95%", "height" => "60%"), 
	
	array("item" => "applet", "skip" => $_REQUEST["mode"]=="rxn", "force" => "chemWriter", "int_name" => "chemWriter", "mode" => $_REQUEST["mode"], "searchMode" => false, "copyPasteButtons" => true, "width" => "95%", "height" => "60%"), 
	array("item" => "applet", "skip" => $_REQUEST["mode"]=="rxn", "force" => "SketchEl", "int_name" => "SketchEl", "mode" => $_REQUEST["mode"], "searchMode" => false, "copyPasteButtons" => true, "width" => "95%", "height" => "60%"), 
	array("item" => "applet", "skip" => $_REQUEST["mode"]=="rxn", "force" => "ACD", "int_name" => "ACD", "mode" => $_REQUEST["mode"], "searchMode" => false, "copyPasteButtons" => true, "width" => "95%", "height" => "60%"), 
	
	array("item" => "applet", "skip" => $_REQUEST["mode"]=="rxn", "force" => "FlaME", "int_name" => "FlaME", "mode" => $_REQUEST["mode"], "searchMode" => false, "copyPasteButtons" => true, "width" => "95%", "height" => "60%"), 
	array("item" => "applet", "skip" => $_REQUEST["mode"]=="rxn", "force" => "ketcher", "int_name" => "ketcher", "mode" => $_REQUEST["mode"], "searchMode" => false, "copyPasteButtons" => true, "width" => "95%", "height" => "60%"), 
	array("item" => "applet", "skip" => $_REQUEST["mode"]=="rxn", "force" => "ChemDoodle", "int_name" => "ChemDoodle", "mode" => $_REQUEST["mode"], "searchMode" => false, "copyPasteButtons" => true, "width" => "95%", "height" => "60%"), 
	
	array("item" => "applet", "force" => "Marvin", "int_name" => "Marvin", "mode" => $_REQUEST["mode"], "searchMode" => false, "copyPasteButtons" => true, "width" => "95%", "height" => "60%"), 
	array("item" => "applet", "force" => "KL_applet", "int_name" => "KL_applet", "mode" => $_REQUEST["mode"], "searchMode" => false, "copyPasteButtons" => true, "width" => "95%", "height" => "60%"), 
	array("item" => "applet", "force" => "ChemDraw", "int_name" => "ChemDraw", "mode" => $_REQUEST["mode"], "searchMode" => false, "copyPasteButtons" => true, "width" => "95%", "height" => "60%"), 
	
	array("item" => "applet", "force" => "text", "int_name" => "molfile_blob", "mode" => $_REQUEST["mode"], "searchMode" => false, "copyPasteButtons" => true, "width" => "95%", "height" => "60%"), 
)).
"</form></body></html>";

?>