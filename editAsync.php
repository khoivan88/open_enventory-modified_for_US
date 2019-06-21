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
require_once "lib_global_settings.php";
require_once "lib_db_query.php";
require_once "lib_db_manip.php";
require_once "lib_formatting.php";

setGlobalVars();
$page_transparent_params=array("desired_action");
$page_type="async";
pageHeader();
//~ echo "<!--";
//~ print_r($_REQUEST);
//~ echo "-->";

echo script."
if (parent && parent!=self && !opener) {
";

// schreiboperation ausführen
$mayWrite=mayWrite($baseTable);
if ($mayWrite[ $_REQUEST["db_id"] ]) { //  || ($baseTable=="message" && $_REQUEST["desired_action"]=="message_status")
	
	if (empty($_REQUEST["version_save"]) && empty($_REQUEST["ignore"]) && $_REQUEST["desired_action"]=="add" && $baseTable=="molecule") { // check for doubl entries before saving, do not integrate check into handleDesiredAction as it is only a warning
		// check for duplicate CAS or smiles_stereo
		list($code,$text)=checkDuplicateCAS($_REQUEST["cas_nr"],$_REQUEST["molecule_id"]);
		if ($code==FAILURE) {
			$messagebox.=$text;
			$cancelAction=true;
		}
		list($code,$text)=checkDuplicateSMILES($_REQUEST["smiles_stereo"],$_REQUEST["molecule_id"]);
		if ($code==FAILURE) {
			$messagebox.=$text;
			$cancelAction=true;
		}
		// Ende Dublettenprüfung
	}
	
	if (!empty($messagebox)) {
		echo "if (confirm(".fixStr(strip_tags($messagebox).s("continue_anyway")).")) {
	parent.setInputValue(\"ignore\",1);
	parent.submitForm(\"main\");
}
else {
	parent.setiHTML(\"feedback_message\",".fixStr($messagebox).");
}
";
	}
	
	if (!$cancelAction) {
		list($success,$message,$pks_added)=handleDesiredAction(); // success 1: successful 2: failure 3: interaction (unlock dataset?)
	}
	
	switch ($success) {
	case SELECT_SUCCESS: // selected successfully
		echo "}
"._script."</head><body></body></html>";
		exit();
	break;
	case ABORT_PROCESS:
		echo "alert(".fixStr($message).");
parent.close();
}
"._script."</head><body></body></html>";
	break;
	}
	
	// rückmeldung geben
	switch ($_REQUEST["desired_action"]) {
	case "add":
		if ($success==NO_ACTION) {
			// do nothing
		}
		elseif ($success==SUCCESS) {
			echo "parent.location.href=".fixStr("list.php?".getSelfRef(array("~script~","desired_action","dbs"))."&dbs=".$_REQUEST["db_id"]."&query=<0>&crit0=".getLongPrimary($baseTable)."&op0=in&val0=".@join(",",$pks_added)."&message=".strip_tags($message)).";
}
"._script."</head><body></body></html>";
			exit();
		}
		else {
			echo "parent.setiHTML(\"feedback_message\",".fixStr($message).");
";
		}
	break;
	case "update":
		if ($baseTable=="molecule") {
			// check for duplicate CAS or smiles_stereo
			list($code,$text)=checkDuplicateCAS($_REQUEST["cas_nr"],$_REQUEST["molecule_id"]);
			$message2.=$text;
			list($code,$text)=checkDuplicateSMILES($_REQUEST["smiles_stereo"],$_REQUEST["molecule_id"]);
			$message2.=$text;
			// Ende Dublettenprüfung
		}
	break;
	
	case "lock":
		if ($success==QUESTION) { // interaction
			echo "if (confirm(".fixStr($message).")) {
	// force unlock
	self.location.href=\"?".getSelfRef(array("~script~","refresh_data","goto_page"),array("db_id","pk"))."&force=true\";
}\n";
		}
		elseif ($success==SUCCESS) { // Datensatzsperre erfolgreich geholt
			// Datensatz neu aus der DB laden
			//~ list($result)=mysql_select_array(array("table" => $_REQUEST["table"], "dbs" => "-1", "filter" => $query[ $_REQUEST["table"] ]["primary"]."=".fixNull($pk), "limit" => 1));
			$_REQUEST["refresh_data"][]="-1,".$pk; // duplicate entry for database is not a problem
			$unlock_entry=true;
		}
	break;
	}
}

// load special version of current dataset
if (isset($_REQUEST["archive_entity"])) {
	list($result)=getFulldataFromPrimaryKeys(
		array(
			"table" => $table, 
			"flags" => QUERY_EDIT, 
			"dbs" => -1, 
		), 
		array(intval($_REQUEST["db_id"]) => array(intval($_REQUEST["pk"])) )
	);
	$result["timestamp"]=time();
	//~ print_r($result);die();
	echo "parent.resetPolicies();
parent.setControlValues(".json_encode($result).",false);
parent.updateButtons();\n";
	unset($_REQUEST["archive_entity"]);
}

// daten laden
if (is_array($_REQUEST["refresh_data"]) && count($_REQUEST["refresh_data"])) {
	$result=array();
	foreach ($_REQUEST["refresh_data"] as $db_id_pk_chain) {
		$pks=explode(",",$db_id_pk_chain);
		$db_id=array_shift($pks);
		$result[$db_id]=$pks;
	}
	
	if ($_REQUEST["for_print"] && $baseTable=="reaction" && !empty($person_id) && ($permissions & _lj_read_all)==0) { // make leeching a bit more difficult
		// filter out all foreign, does not give full safety as datasets may have been loaded before
		$result=array("-1" => $result["-1"]); // remove all but db_id==-1
		if (count($result["-1"])) {
			$allowed=mysql_select_array(array(
				"dbs" => -1,
				//~ "table" => $baseTable, 
				"table" => $table, 
				"filter" => getLongPrimary($baseTable)." IN(".fixArrayList($result["-1"]).") AND lab_journal.person_id=".$person_id,
			));
			$result["-1"]=array(); // remove also db_id==-1
			for ($a=0;$a<count($allowed);$a++) { // add allowed again
				$result["-1"][]=$allowed[$a][$pk_name];
			}
		}
	}
	
	//~ echo "alert(".fixStr($db_id_pk_chain).");\n";
	$result=getFulldataFromPrimaryKeys(
		array(
			"table" => $table, 
			"flags" => QUERY_EDIT, 
		),
		$result
	);
	
	// tell script which datasets have changed since last update
	echo "parent.clearQueue();\n";
	
	// daten ausgeben
	for ($a=0;$a<count($result);$a++) {
		echo "parent.cacheDataset(".$result[$a]["db_id"].",".$result[$a][$pk_name].",(".safe_json_encode($result[$a])."));\n";
	}

	if (is_numeric($_REQUEST["goto_page"])) {
		echo "parent.gotoDataset(".$_REQUEST["goto_page"].");\n";
	}
	elseif ($_REQUEST["refresh"]=="true") {
		echo "parent.gotoDataset();\n";
	}

	// die gecachete Abfrage wird NICHT zur Aktualisierung verwendet, die Liste bleibt fest (ggf. gelöschte entfernen). Würde sonst Benutzer zu sehr verwirren
	if (is_numeric($_REQUEST["age_seconds"])) { // timestamp-Number(new Date()), gleicht unterschiedliche Client/Server-Zeit aus, es gilt die Zeit des php-Servers
		$filter="for_table=".fixStrSQL($_REQUEST["table"]);

		$now=time();
		$filter.=" AND made_when>=FROM_UNIXTIME(".$now.")+".$_REQUEST["age_seconds"]."-2";
		
		$changed_datasets=mysql_select_array(array(
			"table" => "change_notify", 
			"dbs" => $_REQUEST["dbs"], 
			"filter" => $filter, 
			"quick" => true, //2, 
		));
		//~ echo "alert(".fixStr($_REQUEST["age_seconds"]).");\n";
		for ($a=0;$a<count($changed_datasets);$a++) {
			echo "parent.prepareUpdate(".$changed_datasets[$a]["db_id"].",".$changed_datasets[$a]["pk"].",0);\n"; // was 2
		}
	}
}


if ($unlock_entry) { // after data refresh
	echo "
// parent.readOnlyForms(false);
parent.readOnlyMainForm(false);
parent.readOnly=false;
parent.resetAlreadyLoaded();
parent.loadCurrentDataset(); // full
if (parent.loadValues) {
	parent.setControlValues(parent.loadValues,true);
	parent.loadValues=undefined;
}
parent.refreshListFilters();
parent.hideOverlay();
parent.valChanged(null,".($_REQUEST["valuesChanged"]=="true"?"true":"false").");
parent.updateButtons();
";
}

if (!empty($_REQUEST["transferBarcode"])) {
	echo "parent.barcodeRead(".fixStr($_REQUEST["transferBarcode"]).");\n";
}

echo "parent.asyncComplete();\n";

// desired_action zurücksetzen
echo "parent.setInputValue(\"desired_action\",\"\");\n";

if ($_REQUEST["desired_action"]=="recover") {
	echo "parent.archive_entity=undefined;
parent.initVersionsList();
";
}

if ($success!=QUESTION) { // message about success /failure
	echo "parent.showMessage(".fixStr($message).");\n";
}
echo "parent.showMessage2(".fixStr($message2).");\n"; // general info message

if ($success==SUCCESS) { // keeping lock in place
	switch ($_REQUEST["desired_action"]) {
	case "lock":
		echo "parent.renewInterval=parent.setInterval(\"renewLock();\",".(db_lock_renew*1000).");\n";
	break;
	case "unlock":
	case "update":
		echo "parent.clearInterval(parent.renewInterval);\n";
	break;
	}
}

echo "
}
else if (opener || parent==self) {
	self.close();
}

</script>
</head>
<body>
</body>
</html>";

completeDoc();
?>