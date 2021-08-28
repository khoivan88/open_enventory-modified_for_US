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
require_once "lib_settings.php";
require_once "lib_root_funcs.php";

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
<div id=\"browsenav\">".
getAlignTable(
	array("<a href=\"Javascript:void submitForm(&quot;main&quot;);\" class=\"imgButtonSm\"><img src=\"lib/save_sm.png\" border=\"0\"".getTooltip("save_changes")."></a>"), 
	array("<h1><nobr>".s("predefined_permissions")."</nobr></h1>")
).
"</div>
<div id=\"browsemain\">
<form name=\"main\" id=\"main\" method=\"POST\"><span id=\"temp\" style=\"display:none\"></span>".
showHidden(array("int_name" => "save_settings", "value" => "true", )).
getHiddenSubmit();

if ($permissions & _admin) {
	$rc_keys=array_keys($reaction_conditions);
	
	if (!empty($_REQUEST["save_settings"])) {
		$perm_settings=array();
		$list_int_name="predefined_permissions";
		if (is_array($_REQUEST[$list_int_name])) foreach ($_REQUEST[$list_int_name] as $no => $UID) {
			$description=getValueUID($list_int_name,$UID,"permission_level_name");
			if (empty($description)) {
				continue;
			}
			$perm_settings[$description]=
				@array_sum(getValueUID($list_int_name,$UID,"permissions_general"))+
				@array_sum(getValueUID($list_int_name,$UID,"permissions_chemical"))+
				@array_sum(getValueUID($list_int_name,$UID,"permissions_lab_journal"))+
				@array_sum(getValueUID($list_int_name,$UID,"permissions_order"));
			
			// update existing permissions
			/* if (getValueUID($list_int_name,$UID,"update_permissions") && getValueUID($list_int_name,$UID,"old_permissions")!=$perm_settings[$description]) {
				mysqli_query($db,"UPDATE person SET permissions=".fixNull($perm_settings[$description])." WHERE permissions=".fixNull(getValueUID($list_int_name,$UID,"old_permissions"))." AND NOT username LIKE BINARY ".fixStrSQL($db_user).";");
				refreshUsers(false);
			}*/
		}
		setGVar("perm_settings",$perm_settings);
		echo s("settings_saved").showBr();
	}
	$perm_settings=getGVar("perm_settings");
	$loadArray=array();
	$loadArray["predefined_permissions"]=array();
	if (is_array($perm_settings)) foreach ($perm_settings as $permission_level_name => $permission_level) {
		$loadArray["predefined_permissions"][]=array(
			"permission_level_name" => $permission_level_name, 
			//~ "permissions_general" => getMaskSlice($permissions_groups,$permission_level,0), 
			//~ "permissions_chemical" => getMaskSlice($permissions_groups,$permission_level,1), 
			//~ "permissions_lab_journal" => getMaskSlice($permissions_groups,$permission_level,2), 
			//~ "permissions_order" => getMaskSlice($permissions_groups,$permission_level,3), 
			"permissions_general" => $permission_level, 
			"permissions_chemical" => $permission_level, 
			"permissions_lab_journal" => $permission_level, 
			"permissions_order" => $permission_level, 
		);
	}
	
	$perm_texts=s("permissions_list");
	
	// Common, Molecule editing, Inventory, Lab journal, Analytics, Order system, 
	$fieldsArray=array(

array("item" => "subitemlist", "int_name" => "predefined_permissions", "directDelete" => true, "allowReorder" => true, 
	"fields" => array(
		array("item" => "cell"), 
		array("item" => "input", "int_name" => "permission_level_name", "size" => 20, ), 
		//~ array("item" => "hidden", "int_name" => "old_permissions", ), 
		//~ array("item" => "checkbox", "textInLine" => true, VISIBLE => false, "int_name" => "update_permissions", ), 
		array("item" => "cell"), 
		array("item" => "checkset", "int_name" => "permissions_general", "int_names" => array(_admin), "texts" => array(s("permissions_list",0)), "shift" => cumSum($permissions_groups,0), "breakAfter" => 1, ),
		array("item" => "cell"), 
		array("item" => "checkset", "int_name" => "permissions_chemical", "int_names" => getIntNames($permissions_groups,1), "texts" => getTexts($permissions_groups,$perm_texts,1), "shift" => cumSum($permissions_groups,1), "breakAfter" => 1, ),
		array("item" => "cell"), 
		array("item" => "checkset", "int_name" => "permissions_lab_journal", "int_names" => getIntNames($permissions_groups,2), "texts" => getTexts($permissions_groups,$perm_texts,2), "shift" => cumSum($permissions_groups,2), "breakAfter" => 1, ),
		array("item" => "cell"), 
		array("item" => "checkset", "int_name" => "permissions_order", "int_names" => getIntNames($permissions_groups,3), "texts" => getTexts($permissions_groups,$perm_texts,3), "shift" => cumSum($permissions_groups,3), "breakAfter" => 1, ),
	), 
),
);

	echo getFormElements(array(
		READONLY => false, 
		"noFieldSet" => true, 
	),
	$fieldsArray);
}
else {
	echo s("permission_denied");
}

echo "
</form>
</div>
".
script."
setControlValues(".json_encode($loadArray).",false);
"._script."
</body>
</html>";

completeDoc();
?>