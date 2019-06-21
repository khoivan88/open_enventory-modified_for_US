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
require_once "lib_applet.php";
require_once "lib_molfile.php";
require_once "lib_save_molfile.php";

$page_type="async";
$barcodeTerminal=true;
pageHeader(true,true,false);

$imgUID=uniqid();

$clipboard_index=0;

// desired_action copy(POST), copyFromDb(timestamp or db_id,molecule_id) or paste
// store molfile in $settings, persistent of time
// copy: molfile_blob=blabla
// paste: applet_name
switch ($_REQUEST["desired_action"]) {
	case "searchReaction":
		$molfile=getMolfileRequest(false);
		$structure=readMolfile($molfile["molfile"]); // we can close session
		if (!empty($structure["smiles_stereo"])) {
			$url="list.php?table=reaction&dbs=-1&query=<0> AND <1>&crit0=reaction_chemical.role&op0=in&val0=".$_REQUEST["role"]."&crit1=reaction_chemical.smiles_stereo&op1=bn&val1=".urlencode(addSMILESslashes($structure["smiles_stereo"]));
			echo script.
				"window.open(".fixStr($url).");".
				_script;
		}
	break;
	case "copyFromDb": // from DB or timestamp
		//~ $_REQUEST["molfile_blob"]=getMolfileRequest(false); // dont close session yet
		$settings["clipboard"][$clipboard_index]=getMolfileRequest(false); // dont close session yet
		saveUserSettings();
	break;
	case "copy": // from applet via form
		$settings["clipboard"][$clipboard_index]=array("molfile" => $_REQUEST["molfile_blob"], );
		saveUserSettings();
	break;
	
	case "pasteUID": // into structure control
		echo script."
if (parent) {
";
		// generate img
		switch ($_REQUEST["mode"]) {
		case "rxn":
			//~ $structure=readRxnfile($settings["molfile_clipboard"]); // standard conform mode!! 128+16+4+2
			$structure=readRxnfile($settings["clipboard"][$clipboard_index]["molfile"]); // standard conform mode!! 128+16+4+2
			list($_SESSION["gifFile"][$imgUID],$_SESSION["svgFile"][$imgUID])=getReactionGif($structure,$_REQUEST["width"],$_REQUEST["height"],0,1,3,array("png","svg"));
			$molfile=writeRxnfile($structure);
			$_SESSION["molFile"][$imgUID]=$molfile;
		break;
		case "mol":
		default:
			//~ $structure=readMolfile($settings["molfile_clipboard"]); // standard conform mode!! 128+16+4+2
			$structure=readMolfile($settings["clipboard"][$clipboard_index]["molfile"]); // standard conform mode!! 128+16+4+2
			list($_SESSION["gifFile"][$imgUID],$_SESSION["svgFile"][$imgUID])=getMoleculeGif($structure,$_REQUEST["width"],$_REQUEST["height"],0,1,true,array("png","svg"));
			$molfile=writeMolfile($structure);
			$_SESSION["molFile"][$imgUID]=$molfile;
		}
		
		$molfile=addPipes($molfile);
		
		// update input
		// touch onchange
		if (empty($_REQUEST["field"])) {
			echo "parent.setInputValue(".fixStr($_REQUEST["int_name"]).",".fixStr($molfile).");
parent.touchOnChange(".fixStr($_REQUEST["int_name"]).");\n";
		}
		else { // SIL
			//~ if (!empty($settings["clipboard"][$clipboard_index]["table"])) {
			if ($settings["clipboard"][$clipboard_index]["table"]=="reaction_chemical" && $_REQUEST["int_name"]!="products") {
				// load data according to metadata
				echo "parent.acceptPkSelection(".json_encode(array(
					"list_int_name" => $_REQUEST["int_name"], 
					"UID" => $_REQUEST["UID"], 
					"field" => $_REQUEST["field"], 
					"group" => $_REQUEST["group"], 
				)).",".fixStr($settings["clipboard"][$clipboard_index]["table"]).",".fixNull($settings["clipboard"][$clipboard_index]["db_id"]).",".fixNull($settings["clipboard"][$clipboard_index]["pk"]).");\n";
			}
			else {
				echo "parent.SILsetValue(".fixStr($molfile).",".fixStr($_REQUEST["int_name"]).",".fixStr($_REQUEST["UID"]).",".fixStr($_REQUEST["field"]).",".fixStr($_REQUEST["group"]).");
parent.SILObjTouchOnchange(".fixStr($_REQUEST["int_name"]).",".fixStr($_REQUEST["UID"]).",".fixStr($_REQUEST["field"]).",".fixStr($_REQUEST["group"]).");\n";
			}
		}
		
		// update graphics
		echo "parent.moleculeUpdated(".fixStr($imgUID).",".fixStr($_REQUEST["int_name"]).",".fixStr($_REQUEST["UID"]).",".fixStr($_REQUEST["field"]).",".fixStr($_REQUEST["group"]).");
parent.valChanged();
}
"._script;
		
	break;
	
	case "paste": // directly into applet
		echo script."
if (parent && parent!=self) {
";
		switch ($_REQUEST["mode"]) {
		case "rxn":
			echo "parent.putRxnfile";
		break;
		case "mol":
		default:
			echo "parent.putMolfile";
		}
		//~ echo "(".fixStr($_REQUEST["applet_name"]).",".fixStr(addPipes($settings["molfile_clipboard"])).",".fixStr($_REQUEST["force"]).");
		echo "(".fixStr($_REQUEST["applet_name"]).",".fixStr(addPipes($settings["clipboard"][$clipboard_index]["molfile"])).",".fixStr($_REQUEST["force"]).");
}
"._script;
	break;
}

echo "</head>
<body></body>
</html>";

completeDoc();
?>