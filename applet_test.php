<?php
require_once "lib_global_funcs.php";
require_once "lib_db_query.php";
require_once "lib_applet.php";
require_once "lib_form_elements.php";

pageHeader();

$paramHash=array("int_name" => "testform");

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
copyPasteAppletHelper();

echo getHelperTop()."<form name=\"appletTest\" action=\"applet_test_async.php\" target=\"edit\" method=\"post\">".
getFormElements($paramHash,array(
	array("item" => "select", "int_name" => "modus", "text" => "Modus", "int_names" => array("draw","compare"), "texts" => array("Zeichnen","Vergleichen")), 
	//~ array("item" => "hidden", "int_name" => "active_modus", ), 
	array("item" => "applet", "int_name" => "appl", "mode" => $_REQUEST["mode"], "searchMode" => false, "copyPasteButtons" => true, "width" => "95%", "height" => "60%"), 
	array("item" => "text", "text" => "<a href=\"javascript:transfer();\">Nach Aussehen prüfen</a> <a href=\"javascript:done();\">Fertig</a><br>"), 
	array("item" => "input", "int_name" => "molecule_id", "text" => "Bei Fehlern diese Nummer aufschreiben.", DEFAULTREADONLY => "always"), 
	array("item" => "input", "int_name" => "smiles_stereo", "size" => 20,"maxlength" => 80, "softLineBreakAfter" => 20, DEFAULTREADONLY => "always"), 
	array("item" => "input", "int_name" => "smiles", "size" => 20,"maxlength" => 80, "softLineBreakAfter" => 20, DEFAULTREADONLY => "always"), 
	"br", 
	array("item" => "structure", "int_name" => "molfile_blob_before", "width" => rxn_gif_x, "height" => rxn_gif_y, "showMolfileButton" => true, "showGifButton" => true, "showCopyPasteButton" => true, "mode" => $_REQUEST["mode"], DEFAULTREADONLY => "always", ), // split rxnfile and invoke update
	"br", 
	array("item" => "structure", "int_name" => "molfile_blob_after", "width" => rxn_gif_x, "height" => rxn_gif_y, "showMolfileButton" => true, "showGifButton" => true, "showCopyPasteButton" => true, "mode" => $_REQUEST["mode"], DEFAULTREADONLY => "always", ), // split rxnfile and invoke update
));

echo "</form>".
script.
"
function transfer() {
	setInputValue(\"molfile_blob_after\",getControlValue(\"appl\"));
	addMoleculeToUpdateQueue(\"molfile_blob_after\");
	updateMolecules();
}

function done() {
	prepareSubmitForms();
	document.forms.appletTest.submit();
}

readOnlyForms(false);
done();

".
_script.
getHelperBottom()."
</body></html>";
?>