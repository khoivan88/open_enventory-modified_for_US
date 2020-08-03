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

setGlobalVars();

pageHeader();

echo stylesheet.
style.
getFixedStyleBlock().
_style.
loadJS(array("controls.js","jsDatePick.min.1.3.js","forms.js","searchpk.js","subitemlist_helper.js","subitemlist_get.js","subitemlist.js","edit.js",),"lib/").
script."
readOnly=false;
editMode=false;\n";

$institution_name=$g_settings["organisation_name"];
if (isEmptyStr($institution_name)) {
	$institution_name=ucwords($db_name);
} elseif ($db_name!="storage" && similar_text($institution_name, $db_name, $percent)<7 && $percent <70) {
	$institution_name.="-".ucwords($db_name);
}

$presets=array(
	"institution" => $institution_name,
	"email" => $own_data["email"],
);

getViewHelper($table);

echo "activateSearch(false);
"._script."
</head>
<body>";

showCommFrame();

$success=FAILURE;

switch ($_REQUEST["desired_action"]) {
case "create_account":
	require_once "lib_http.php";
	
	// send data to Sciflection.com
	$post=array(
		"user" => $_REQUEST["username"],
		"department" => $_REQUEST["institution"],
	);
	arr_trans($post, $_REQUEST, array("email","now","nonce","captchaText","compoundSecret"));
	
	$response=oe_http_post_fields(SCIFLECTION_URL."/receiveSciflectionConnect", $post);
	// retrieve password
	if ($response) {
		$passwordOrError=$response->getBody();
		$http_code=$response->getStatus();
		if ($http_code==200) {
			// create entry in other_db
			require_once "lib_db_manip.php";
			
			$_REQUEST["desired_action"]="add";
			$_REQUEST["db_id"]="-1";
			$_REQUEST["host"]=SCIFLECTION_URL;
			$_REQUEST["db_beauty_name"]=SCIFLECTION;
			$_REQUEST["capabilities"]=array(8); // constant value
			$_REQUEST["db_user"]=$_REQUEST["username"];
			$_REQUEST["db_pass"]=$_REQUEST["db_pass_repeat"]=$passwordOrError;
			
			list($success,$message,$pks_added)=handleDesiredAction();
			if ($success==SUCCESS) {
				$message=s("settings_saved");
			} else {
				$message=s("error").": ".$message;
			}
		} else {
			if ($http_code==409) {
				switch ($passwordOrError) {
					case "wrong captcha":
						$passwordOrError=s("sciflectionWrongCaptcha");
						break;
					case "token expired":
						$passwordOrError=s("sciflectionTokenExpired");
						break;
					case "department exists":
						$passwordOrError=s("sciflectionDepartmentExists");
						break;
					case "user exists":
						$passwordOrError=s("sciflectionUsernameExists");
						break;
				}
			} elseif ($http_code==403) {
				$passwordOrError=s("sciflectionBlocked");
			}
			$message=s("error").": ".strip_tags($passwordOrError);
		}
	} else {
		$message=s("error");
	}
	arr_trans($presets, $_REQUEST, array("institution", "username", "email", ), true);
	break;
}

echo getHelperTop().
	"<div id=\"browsenav\"><h1>".s("sciflectionSignup")."</h1></div>
	<div id=\"browsemain\" style=\"bottom:0px;overflow:auto;\">";
	
if ($success==SUCCESS) {
	echo "<b>".$message."</b>";
	// TODO: guide to 1st data publication
} else {
	echo "<form name=\"main\" id=\"main\" method=\"POST\" style=\"height:100%;\"><span id=\"temp\" style=\"display:none\"></span>".
	showHidden(array("int_name" => "desired_action", "value" => "create_account", )).
	showHidden(array("int_name" => "table", "value" => "other_db", )).
	showHidden(array("int_name" => "sess_proof", "value" => $_SESSION["sess_proof"] )).
	getHiddenSubmit();

echo getFormElements(
	array(
		READONLY => false, 
		"noFieldSet" => true, 
		"checkSubmit" => 'if (getControlValue("captchaText")=="") { '
			.'alert('.fixStr(s("sciflectionNoCaptcha")).');'
			.'focusInput("captchaText"); '
			.'return false;'
		.'}',
	),
	array(
		// TOS
		array("item" => "text", "text" => "<iframe id=\"terms\" width=\"100%\" height=\"600\" src=\"https://sciflection.com/static/terms.html?language=".$lang."\"></iframe>"),
		"tableStart",
		// Info text
		// institution name (from settings)
		array("item" => "input", "int_name" => "institution", "onChange" => "setInputValue(&quot;username&quot;,makeUsername(this.value));"),
		// JS-generated user name (only lowercase and non-alphanum => underscore)
		array("item" => "input", "int_name" => "username", ),
		array("item" => "input", "int_name" => "email", ),
		array("item" => "text", "int_name" => "captchaContainer", "text" => "<img id=\"captcha\" src=\"lib/1x1.png\" border=\"0\">"),
		array("item" => "input", "int_name" => "captchaText",),
		array("item" => "text", SPLITMODE => true, "rw" => "<a href=\"javascript:void(0);\" onClick=\"loadCaptcha(this);\" class=\"imgButtonSm\" style=\"float:right;\"><img src=\"lib/refresh_sm.png\" border=\"0\"".getTooltip("refresh")."></a>"),
		"tableEnd",
		array("item" => "hidden", "int_name" => "now",),
		array("item" => "hidden", "int_name" => "nonce",),
		array("item" => "hidden", "int_name" => "compoundSecret",),
		array("item" => "text", "text" => "<a href=\"Javascript: if (checkSubmitForms()) { prepareSubmitForms(); submitForm(&quot;main&quot;); }\" class=\"imgButtonSm\" style=\"float:none\"><img src=\"lib/save_sm.png\" border=\"0\"".getTooltip("sciflectionYes").">".s("sciflectionYes")."</a>"
			. "<p><b>".$message."</b></p>"),
)).
"</form>";
}
echo "</div>".
getHelperBottom().
script."
setControlValues(".json_encode($presets).",false);
setInputValue(\"username\",makeUsername(getInputValue(\"institution\")));
activateEditView();
loadCaptcha();
setInterval(function () { loadCaptcha(); }, 11*60*1000); // prevent rejection
"._script."
</body>
</html>";

completeDoc();
?>