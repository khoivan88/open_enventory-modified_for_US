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
require_once "lib_navigation.php";
require_once "lib_global_settings.php";
require_once "lib_constants.php";
require_once "lib_formatting.php";
require_once "lib_sidenav_funcs.php";
require_once "lib_applet.php";

pageHeader();

$sidenav_tables=array("chemical_storage","disposed_chemical_storage","molecule",);

echo "
<link href=\"style.css.php\" rel=\"stylesheet\" type=\"text/css\">".
//~ loadJS("dynamic.js.php").
loadJS(array("chem.js","sidenav.js","controls.js","jsDatePick.min.1.3.js","forms.js","searchpk.js","edit.js"),"lib/").
script."

function search_cdb() {
	prepareSubmitForms();
	setInputValue(\"query\",getQueryString(\"cdb_search\"));
	setFormAction();
	
	return true;
}

var readOnly=false,editMode,".addParamsJS().",default_per_page=".default_per_page.";
dependent={};
sidenav_tables=".json_encode($sidenav_tables).";
".getCritOptionsFunction($sidenav_tables);

echo _script."</head><body style=\"background-image:url(".$background.");background-repeat:no-repeat\">";
showCommFrame(array("debug" => $_REQUEST["debug"]=="true"));
copyPasteAppletHelper(array("mode" => "rxn", ));
echo "<form id=\"searchForm\" name=\"searchForm\" method=\"post\" onSubmit=\"return search_cdb();\" target=\"mainpage\">";

// activates query parts if an input value is not empty
$queryFields=array(
		"val0" => 0, 
		"val1" => 1, 
		"val2" => 2, 
		"val3" => 3, 
		"val4a" => 4, 
		"val5" => 5, 
		"val6" => 6, 
		"val7" => 7, 
);

$fieldsArray=array(
	array("item" => "hidden", "int_name" => "query"),

	// Applet direkt einbinden

	array("item" => "hidden", "int_name" => "crit4", "value" => "molecule.molfile_blob"),
	array("item" => "hidden", "int_name" => "op4", "value" => "su"),
	array(
		"item" => "applet", 
		"int_name" => "val4a", 
		"searchMode" => true, 
		"copyPasteButtons" => true, 
		"width" => "300px", 
		"height" => "300px", 
	), 

	// Anfang Tabelle
	array("item" => "text", "text" => "<table class=\"searchRxnTable\"><tr><td>"), 

	"tableStart",
	// dbs
	array(
		"item" => "pk_select", 
		"text" => s("database"), 
		"int_name" => "dbs", 
		"pkName" => "other_db_id", 
		"nameField" => "db_beauty_name", 
		"table" => "other_db", 
		"order_obj" => getUserDefOrderObj("other_db"), 
		"onChange" => "selectUpdated(&quot;dbs&quot;); rxnToSidenav(&quot;dbs&quot;); ", 
		"filterDisabled" => true, 
		"allowNone" => true, 
		"noneText" => s("any"), 
		"defaultValue" => "-1", 
		"clearbutton" => true, 
	),

	array("item" => "hidden", "int_name" => "crit0", "value" => "molecule_names.molecule_name"),
	array("item" => "hidden", "int_name" => "op0", "value" => "ct"),
	array("item" => "input", "int_name" => "val0", "text" => s("molecule_name"), "size" => 15, "maxlength" => 100), 

	array("item" => "hidden", "int_name" => "crit1", "value" => "molecule.cas_nr"),
	array("item" => "hidden", "int_name" => "op1", "value" => "ct"),
	array("item" => "input", "int_name" => "val1", "text" => s("cas_nr"), "size" => 15, "maxlength" => 100), 

	array("item" => "hidden", "int_name" => "crit2", "value" => "molecule.emp_formula"),
	array("item" => "hidden", "int_name" => "op2", "value" => "sf"),
	array("item" => "input", "int_name" => "val2", "text" => s("emp_formula"), "size" => 15, "maxlength" => 100), 

	array("item" => "hidden", "int_name" => "crit3", "value" => "molecule.mw"),
	array("item" => "hidden", "int_name" => "op3", "value" => "bt"),
	array("item" => "input", "int_name" => "val3", "text" => s("mw"), "size" => 10,"maxlength" => 22, ), 

	array("item" => "check", "int_name" => "selected_only", "onChange" => "rxnToSidenav(&quot;selected_only&quot;); ", ),
	
	array("item" => "select", "int_name" => "table", "int_names" => $sidenav_tables, ), 

	getListLogic("form","rxnToSidenav(&quot;list_op&quot;);"),
	array("item" => "text", "int_name" => "ref_reaction", "text" => "<span id=\"ref_reaction\"></span>", ), // display the reaction name which is used for comparison

	"tableEnd",
);

$paramHash=array(
	"noFieldSet" => true, 
	READ_ONLY => false, 
	"no_db_id_pk" => true, 
	"int_name" => "cdb_search", 
	"noInputHighlight" => true, 
	"onLoad" => 'selectUpdated("dbs"); var initDone=false; ', 
	"queryFields" => $queryFields, 
);

// dbs: Datenbank: alle oder bestimmte, default -1
echo 
	showHidden(array("int_name" => "fields")).
	showHidden(array("int_name" => "view")).
	// Table: reaction
//~ 	showHidden(array("int_name" => "style", "value" => "lj")).
	showHidden(array("int_name" => "prev_cache_id")). // speichert die vorherige cache_id
	showHidden(array("int_name" => "ref_cache_id")). // speichert die aktuelle cache_id

	getFormElements($paramHash,$fieldsArray).
	// onChange submit, bei Textfeldern 2s warten oder [Enter] oder Verlassen
	"<table class=\"noborder\"><tr><td>".
	getImageLink(array("url" => "javascript: if (search_cdb()) { submitForm(&quot;searchForm&quot;); }", "a_class" => "imgButton", "src" => "lib/search.png", "l" => "btn_search")).
	"</td><td>".
	getImageLink(array("url" => "javascript:void document.searchForm.reset();", "a_class" => "imgButton", "src" => "lib/reset_button.png", "l" => "btn_reset")).
	"</td></tr><tr><td colspan=\"3\">".
	getViewRadio(array()).
	getHiddenSubmit().
	"</td></tr></table>
</td></tr></table>
</form>
".script."
var sF=document.searchForm;
updateListOp();
"._script."
</body>
</html>";

completeDoc();
?>