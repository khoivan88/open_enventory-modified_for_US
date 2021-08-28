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
require_once "lib_db_query.php";
require_once "lib_db_manip.php";
require_once "lib_simple_forms.php";
require_once "lib_output.php";

pageHeader();

function getChemHeadline(& $row) {
	global $useSvg,$settings;
	$retval=array($row["standard_name"]);
	
	switch ($row["measured"]) {
	case 1:
		if (!empty($row["m_brutto"])) {
			$retval[]=roundLJ($row["m_brutto"])."&nbsp;".$row["mass_unit"];
		}
	break;
	case 2:
		if (!empty($row["volume"])) {
			$retval[]=roundLJ($row["volume"])."&nbsp;".$row["volume_unit"];
		}
	break;
	case 3:
	default:
		if (!empty($row["rc_amount"])) {
			$retval[]=roundLJ($row["rc_amount"])."&nbsp;".$row["rc_amount_unit"];
		}
		
		if (!empty($row["stoch_coeff"])) {
			$retval[]=roundLJ($row["stoch_coeff"])."&nbsp;eq";
		}
	}
	
	$retval[]=showImageOverlay(array(
		"pkName" => "reaction_chemical_id", 
		"db_id" => $row["db_id"], 
		"pk" => $row["reaction_chemical_id"], 
		"archive_entity" => $_REQUEST["archive_entity"], 
		"width" => rc_gif_x, 
		"height" => rc_gif_y, 
		"mode" => "mol", 
		"posFlags" => 128+1024+2048, 
		"noLink" => true, 
		"showMolfileButton" => false, 
		"showGifButton" => false, 
	));
	
	return join("<br>",$retval);
}

function addMolCol(& $copyTableFields,$c) {
	if ($c<2) { // not for product
		array_push($copyTableFields,
			array("item" => "text", "value" => "<br>"),
			array(
				"item" => "select", 
				"text" => s("chemical_storage_id"), 
				"onChange" => "chemSelectChanged", 
				"int_name" => "chemical_storage_id", 
				"class" => "small_input", 
			), // "setFunction" => "setChemicalStorageData(list_int_name,UID,pos,values); ", 
			
			array("item" => "hidden", "int_name" => "standard_name", ),
			array("item" => "hidden", "int_name" => "package_name", ),
			array("item" => "hidden", "int_name" => "cas_nr", ),
			
			array("item" => "hidden", "int_name" => "from_reaction_id", ),
			array("item" => "hidden", "int_name" => "from_reaction_chemical_id", ),
			
			array("item" => "span", "int_name" => "info2", ),
			array("item" => "text", "value" => " "),
			array("item" => "input", "text" => s("chemical_storage_barcode"), DEFAULTREADONLY => "always", "int_name" => "chemical_storage_barcode", "class" => "small", ),
			
			array("item" => "text", "rw" => "<br>"),
			array("item" => "button", "int_name" => "search_molecule", "onClick" => "searchMolecule", "img" => "lib/chemical_storage_sm.png", "hideReadOnly" => true, "class" => "button_very_small", ), // Suchknopf für Molekül oder Gebinde
			array("item" => "button", "int_name" => "search_reaction", "onClick" => "searchReaction", "img" => "lib/reaction_sm.png", "hideReadOnly" => true, "class" => "button_very_small", ), // Suchknopf für Reaktion
			array("item" => "button", "int_name" => "edit_rc", "onClick" => "editRc", "img" => "lib/edit_rc_sm.png", "hideReadOnly" => true, "class" => "button_very_small", ), // manuell eintragen
			array("item" => "groupEnd")
		);
	}
	else {
		array_push($copyTableFields,
			array("item" => "text", "rw" => "<br>"),
			array("item" => "button", "int_name" => "search_molecule", "onClick" => "searchMolecule", "img" => "lib/molecule_sm.png", "hideReadOnly" => true, "class" => "button_very_small", ), // Suchknopf für Molekül
			array("item" => "button", "int_name" => "edit_rc", "onClick" => "editRc", "img" => "lib/edit_rc_sm.png", "hideReadOnly" => true, "class" => "button_very_small", ), // manuell eintragen
			array("item" => "groupEnd")
			// NO hidden for mw, purity, emp_formula, ...
			
		);
	}
}

$_REQUEST["table"]="reaction";
setGlobalVars();

$mayWrite=mayWrite($baseTable);
if ($_REQUEST["desired_action"]=="insert_copies") {
	if ($mayWrite) {
		echo script;
		handleDesiredAction();
		echo _script;
	}
	
	echo "
</head>
<body>
</body>
</html>";

}
else {

	// erst hier wg handleDesiredAction
	$page_transparent_params=array("dbs","db_id","pk","fields","page","per_page");

	$filter=getLongPrimary($table)."=".fixNull($pk);
	list($result)=mysql_select_array(array(
		"dbs" => $_REQUEST["db_id"], 
		"table" => "reaction_copy", 
		"filter" => $filter, 
		"limit" => 1, 
		"flags" => QUERY_EDIT, 
	));
	
	$list_int_name="copyTable";
	//~ print_r($result);die();
	
	$copyTableFields=array(
		array("item" => "cell"),
		array("item" => "line_number"),
		array("item" => "cell"),
		array(
			"item" => "input", 
			"int_name" => "global_factor", 
			//~ "defaultValue" => "1", 
			"size" => 3, 
			"doEval" => true, 
			"set_all_button" => true, 
		),
	);
	
	$loadArray=array(); //NEW
	
	for ($c=0;$c<3;$c++) {
		$colClass="firstCol"; // bewirkt breitere Linie links
		
		switch ($c) {
		case 0:
			$int_name="reactants";
		break;
		case 1:
			$int_name="reagents";
		break;
		case 2:
			array_push($copyTableFields,
				array("item" => "cell", "class" => $colClass),
				array(
					"item" => "checkbox", 
					"int_name" => "copy_realization_text", 
					//~ "defaultValue" => true, 
				)
			);
			
			// attach reaction_properties which are active
			if (is_array($reaction_conditions)) foreach ($reaction_conditions as $condition => $data) { // save all properties even if not shown
				if (!$g_settings["reaction_conditions"][$condition]) {
					continue;
				}
				array_push($copyTableFields,
					array("item" => "cell"),
					array(
						"item" => "input", 
						"int_name" => $condition, 
						//~ "defaultValue" => $result[$condition], 
						"size" => ifempty($data["size"],3), 
						"additionalField" => true, 
						"set_all_button" => true, 
					)
				);
				$loadArray[$condition]=$result[$condition];
			}
			
			$int_name="products";
		break;
		}
		
		// Einträge in Vorlage durchgehen
		for ($a=0;$a<count($result[$int_name]);$a++) {
			$groupName=$result[$int_name][$a]["reaction_chemical_id"];
			$loadArray["global_factor"]=1;
			$loadArray["copy_realization_text"]=true;
			
			array_push($copyTableFields,
				array("item" => "groupStart", "group" => $groupName, ), 
				array("item" => "cell", "class" => $colClass, ) 
			);
			
			if ($c<2) { // Startmat
				array_push($copyTableFields,
					array(
						"item" => "input", 
						"text" => getChemHeadline($result[$int_name][$a]), 
						"int_name" => "factor", 
						//~ "defaultValue" => "1", 
						"size" => 3, 
						"doEval" => true, 
						"set_all_button" => true, 
					)
				);
			}
			else { // product
				array_push($copyTableFields,
					array(
						"item" => "checkbox", 
						"text" => getChemHeadline($result[$int_name][$a]), 
						"int_name" => "factor", 
						//~ "defaultValue" => "1", 
						"value" => 1, 
					) // defaultValue makes checked, value is value of the checkbox
				);
			}
			
			// Auswahlmöglichkeit andere Struktur/anderes Molekül
			array_push($copyTableFields,
				// toggle-button in Headline
				array(
					"item" => "text", 
					"headline" => "<a id=\"link_show_".$groupName."\" href=\"Javascript:toggleColVisible(".fixQuot($list_int_name).",".fixNull($groupName).",true)\">&gt;&gt;</a><a id=\"link_hide_".$groupName."\" href=\"Javascript:toggleColVisible(".fixQuot($list_int_name).",".fixNull($groupName).",false)\" style=\"display:none\">&lt;&lt;</a>", 
				),
				// info area showing hidden changes
				array("item" => "span", "int_name" => "indicator", ),
				
				// cell
				array("item" => "cell", "int_name" => "col0", VISIBLE => false, ),
				// structure
				array(
					"item" => "structure", 
					"text" => s("structure"), 
					"int_name" => "molfile_blob", 
					"pkName" => "reaction_chemical_id", 
					//~ "pkField" => "reaction_chemical_id", 
					"showEditButton" => true, 
					"showDelButton" => false, 
					"showGifButton" => false, 
					"showMolfileButton" => false, 
					"showCopyPasteButton" => true, 
					"height" => rc_gif_y, 
					"width" => rc_gif_x, 
					"onChange" => "strucChanged", 
				), 
				
				// cell
				array("item" => "cell", "int_name" => "col1", VISIBLE => false, ),
				// buttons/selects
				array(
					"item" => "select", 
					"text" => s("molecule_id"), 
					"onChange" => "molSelectChanged", 
					"int_name" => "molecule_id", 
					"class" => "small_input", 
				),
				array(
					"item" => "hidden", 
					"int_name" => "reaction_chemical_id", 
					//~ "defaultValue" => $groupName, 
				), 
				array("item" => "hidden", "int_name" => "other_db_id", ), 
				array("item" => "span", "int_name" => "info1", )
			);
			addMolCol($copyTableFields,$c);
			
			//~ $loadArray=array_key_filter($result[$int_name][$a],array("reaction_chemical_id","molfile_blob","molecule_id","chemical_storage_id","from_reaction_id","from_reaction_chemical_id","standard_name","package_name","cas_nr"));
			$loadArray[$groupName]=array_key_filter($result[$int_name][$a],array("reaction_chemical_id","molfile_blob","molecule_id","chemical_storage_id","from_reaction_id","from_reaction_chemical_id","standard_name","package_name","cas_nr"));
			$loadArray[$groupName]["factor"]=1;
			
			//~ $copyTableLineInit.=
//~ 'SILsetValuesUID(list_int_name,UID,pos,'.json_encode($loadArray).','.fixStr($groupName).'); ';
			
			$copyTableLineInit.='updateSelectInfos(list_int_name,UID,'.json_encode($result[$int_name][$a]).','.fixStr($groupName).');';
			// hier evtl etwas entschlacken
			
			$colClass="";
		}
		
		// additional component
		$a=0; // maybe loop here later
		$groupName=$int_name."_".$a;
		array_push($copyTableFields,
			array("item" => "groupStart", "group" => $groupName, ), 
			array("item" => "cell", ), 
			
			// amount
			array(
				"item" => "input", 
				"int_name" => "amount", 
				"text" => "", 
				"size" => 5, 
				"class" => "small_input", 
				"doEval" => true, 
				"type" => "round", 
				"roundMode" => $paramHash["roundMode"], 
				"decimals" => $paramHash["decimals"], 
			),
			array(
				"item" => "pk_select", 
				"int_name" => "amount_unit", 
				"text" => "", 
				"pkName" => "unit_name", 
				"dbs" => "-1", 
				"table" => "units", 
				"nameField" => "unit_name", 
				"allowAuto" => true, 
				"autoText" => s("stoch_coeff"), 
				"filterDisabled" => true, 
				//~ "defaultValue" => "mg", 
				"filter" => ($c<2?"unit_type COLLATE utf8_bin IN(\"m\",\"n\",\"v\")":"unit_type LIKE BINARY \"n\""), 
				"setValues" => 
					'return a(selected_values,"unit_name");',
				"class" => "small_input", 
			),
			
			// toggle-button in Headline
			array(
				"item" => "text", 
				"headline" => "[+]<br><a id=\"link_show_".$groupName."\" href=\"Javascript:toggleColVisible(".fixQuot($list_int_name).",".fixQuot($groupName).",true)\">&gt;&gt;</a><a id=\"link_hide_".$groupName."\" href=\"Javascript:toggleColVisible(".fixQuot($list_int_name).",".fixQuot($groupName).",false)\" style=\"display:none\">&lt;&lt;</a>", 
			),
			// info area showing hidden changes
			array("item" => "span", "int_name" => "indicator", ),
			
			// cell
			array("item" => "cell", "int_name" => "col0", VISIBLE => false, "style" => "min-width:60px;", ),
			// structure
			array(
				"item" => "structure", 
				"text" => s("structure"), 
				"int_name" => "molfile_blob", 
				"pkName" => "reaction_chemical_id", 
				"showEditButton" => true, 
				"showDelButton" => true, 
				"showGifButton" => false, 
				"showMolfileButton" => false, 
				"showCopyPasteButton" => true, 
				"height" => rc_gif_y, 
				"width" => rc_gif_x, 
				"onChange" => "addStrucChanged", 
			), 
			
			// cell
			array("item" => "cell", "int_name" => "col1", VISIBLE => false, ),
			// buttons/selects
			array(
				"item" => "select", 
				"text" => s("molecule_id"), 
				"onChange" => "molSelectChanged", 
				"int_name" => "molecule_id", 
				"class" => "small_input", 
			),
			array("item" => "hidden", "int_name" => "other_db_id", ), 
			array("item" => "span", "int_name" => "info1", ) 
		);
		
		addMolCol($copyTableFields,$c);
		$copyTableLineInit.='updateSelectInfos(list_int_name,UID,{},'.fixStr($groupName).');';
		$copyTableLineInit.='updateAddComp(list_int_name,UID,'.fixStr($groupName).');'; // disable amount fields unless something is in there
	}
	
	if (!empty($result["default_copy_target"])) { // set for this LJ
		$target_lab_journal_id=$result["default_copy_target"];
	}
	else {
		$default_person_id=fixNull($person_id);
		
		if (!empty($settings["default_lj"])) { // set for this person
			$target_lab_journal_id=$settings["default_lj"];
		}
		else { // same lj
			$target_lab_journal_id=$result["lab_journal_id"];
		}
	}
	$update_LJ="if (!initDone) { setInputValue(\"lab_journal_id\",".fixNull($target_lab_journal_id)."); trackDynValue(\"lab_journal_id\"); initDone=true; } ";
	
	echo "<title>".s("copyReaction1")." ".$result["lab_journal_code"]." ".$result["nr_in_lab_journal"]." ".s("copyReaction2")."</title>
<link href=\"style.css.php\" rel=\"stylesheet\" type=\"text/css\">".
//~ loadJs("dynamic.js.php").
loadJS(array("controls.js","jsDatePick.min.1.3.js","client_cache.js","forms.js","searchpk.js","subitemlist_helper.js","subitemlist_get.js","subitemlist.js","molecule_edit.js","reaction.js","reaction_structure.js","edit.js","copy_rxn.js"),"lib/").
script."
var a_db_id=".fixNull($_REQUEST["db_id"]).",table=\"\",".addParamsJS().";
"._script."
</head>
<body>".getHelperTop();
	
	$reactions_left=getReactionsLeft();
	if ($reactions_left===TRUE) {
		unset($reactions_left);
	}

	showCommFrame(array("debug" => $_REQUEST["debug"]=="true"));
	echo "<form id=\"copyForm\" name=\"copyForm\" action=".fixStr(getSelfRef(array("db_id","pk","dbs","cached_query","order_by")))." method=\"post\">";

	$paramHash=array(
		"noFieldSet" => true, 
		READONLY => false, 
		"no_db_id_pk" => true, 
		"int_name" => "copy_reaction", 
		"onLoad" => "void SILmanualAddLineMultiple(1,\"copyTable\"); PkSelectUpdate(\"lab_journal_id\"); var initDone=false; ", 
	);
	
	echo transParams(array("table", "db_id", "pk", "archive_entity", )).
		showHidden(array("int_name" => "desired_action", "value" => "insert_copies")).
		getFormElements($paramHash,array(
			// Auswahl Ziel-LJ
			array(
				"item" => "text", 
				"text" => "<table class=\"subitemlist\"><tbody><tr><td>".s("source_entry").": ".$result["lab_journal_code"]." ".$result["nr_in_lab_journal"].
				ifNotEmpty(", <br>".s("version")." ".getGermanDate(getTimestampFromSQL($result["version_when"]),true)."<br>(",$result["version_by"],") ".$result["version_comment"]).
				ifNotEmpty(", <br>".s("ref_amount").": ",$result["ref_amount"]," ".$result["ref_amount_unit"]).
				"</td><td>", 
			), 


			array(
				"item" => "pk_select", 
				"int_name" => "person_id", 
				"pkName" => "person_id", 
				"dbs" => -1,
				"table" => "person", 
				"allowAuto" => true, 
				"autoText" => s("any"), 
				"order_by" => "person_name", 
				"clearbutton" => true, 
				"onChange" => "personUpdated(); setPersonName();", 
				"defaultValue" => $default_person_id, 
				"filterDisabled" => true, 
			),

			array("item" => "text", "text" => "</td><td>"), 

			array(
				"item" => "pk_select", 
				"text" => s("target_lj"), 
				"int_name" => "lab_journal_id", 
				"pkName" => "lab_journal_id", 
				"nameField" => "lab_journal_code", 
				"table" => "lab_journal", 
				"dynamic" => true, 
				"filterDisabled" => true, 
				"updateFunction" => $update_LJ, 
				
				"getFilter" => 
					'var query=new Array("<0>"),retval=new Array(),url="&crit0=lab_journal.lab_journal_status&op0=eq&val0=1",person_id=getControlValue("person_id"); '.
					'if (person_id!=-1) { '. // lab_journal
						'query.push("<32>"); '.
						'url+="&crit32=lab_journal.person_id&op32=eq&val32="+person_id; '.
					'} '.
					'retval["filter"]="query="+query.join(" AND ")+url; '.
					'retval["dbs"]=getControlValue("dbs"); '.
					'return retval;', 
				
				"getText" => 
					'return rowData["lab_journal_code"];', 
			), 
			array("item" => "text", "text" => "</td><td>"), 

			//~ array("item" => "input", "int_name" => "overwrite_entries", "strPost" => s("overwrite_entries2"), "value" => "0", "size" => 3, ), 
			array("item" => "select", "int_name" => "overwrite_entries", "strPost" => s("overwrite_entries2"), "int_names" => range(0,20), "texts" => range(0,20), ), 

			array("item" => "text", "text" => "</td><td>"), 
			
			// Reaktionstyp
			array(
				"item" => "pk_select", 
				"int_name" => "reaction_type_id", 
				"table" => "reaction_type", 
				"dbs" => "-1", 
				"nameField" => "reaction_type_name", 
				"allowNone" => true, 
				"noneText" => s("none"), 
				"defaultValue" => $result["reaction_type_id"], 
			),
			
			array("item" => "text", "text" => "</td><td>"), 

			array("item" => "input", "int_name" => "reaction_carried_out_by", "value" => formatPersonNameNatural($own_data), "size" => 25, ), // $result["reaction_carried_out_by"]

			array(
				"item" => "text", 
				"text" => "</td><td style=\"text-align:right\">".getImageLink(array(
					"url" => "javascript:document.copyForm.submit()", 
					"a_class" => "imgButtonSm", 
					"src" => "lib/copy_reaction_sm.png", 
					"l" => "copyReaction", 
				))."</td></tr></tbody></table>", 
			), 

			// Reaktionsgleichung, start Tabelle
			// array("item" => "structure", "int_name" => "rxnfile_blob", "width" => rxn_gif_x, "height" => rxn_gif_y, "pkName" => "reaction_id", "nameField" => "lab_journal_code", "smiles_id" => "smiles", "showMolfileButton" => true, "showGifButton" => true, "showCopyPasteButton" => true, "mode" => "rxn", "onChange" => "rxnChanged();", DEFAULTREADONLY => "always"), // split rxnfile and invoke update
			array(
				"item" => "subitemlist", 
				"int_name" => $list_int_name, 
				"addMultipleButtons" => array(5,10), 
				"directDelete" => true, 
				"maxLines" => $reactions_left, 
				"fields" => $copyTableFields, 
				//~ "lineInitFunction" => "SILupdateVisible(list_int_name,UID); ".$copyTableLineInit, // take care of visible/invisible parts
				"lineInitFunction" => "SILupdateVisible(list_int_name,UID); SILsetValues(list_int_name,UID,undefined,".json_encode($loadArray).");".$copyTableLineInit, // take care of visible/invisible parts, NEW

			), 
		)).
		getHiddenSubmit().
"
</form>".
getHelperBottom()."
</body>
</html>";

}
completeDoc();
?>