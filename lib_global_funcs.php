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

$analytics=array();

require_once "lib_permissions_check.php";
require_once "lib_gd_common.php";
require_once "lib_browser_caps.php";
require_once "lib_brute_block.php";
require_once "lib_url.php";
require_once "lib_array.php";
require_once "lib_db_query.php";
require_once "lib_db_manip.php";
require_once "lib_simple_forms.php";
require_once "lib_selection.php";
require_once "lib_form_elements.php";
require_once "lib_formatting.php";
require_once "lib_constants.php";
require_once "lib_global_settings.php";
require_once "lib_person.php";

require_once "lib_constants_default_settings.php";
$g_settings=getDefaultGlobalSettings();
$default_db_name="storage";
if (is_file("lib_customization".customization.".php")) {
	include_once "lib_customization".customization.".php";
}

require_once "lib_language.php";
require_once "File/Archive/Reader/MimeList.php";

if (@$_REQUEST["debug"]=="true") {
	$debug=true;
}

function getSetting($key,$default="-1") {
	global $settings,$g_settings;
	if (!empty($settings[$key]) && $settings[$key]!=$default) {
		$retval=$settings[$key];
	}
	else {
		$retval=$g_settings[$key];
	}
	return $retval;
}

function getDecimals($setting) {
	if (is_numeric($setting)) {
		return $setting;
	}
	else {
		return 3; // default value
	}
}

function getRoundMode($setting) {
	if ($setting=="fixed") {
		return 0; // fixed
	}
	else {
		return 4; // sign
	}
}

function getHeaderFromMime($mime) {
	return "Content-Type: ".$mime;
}

function getMimeFromExt($ext) {
	return File_Archive_Reader_GetMime(".".$ext);
}

function dieAsync($message) {
	global $page_type;
	if ($page_type=="async") {
		die("alert(".fixAlert($message).");\n}\n"._script); // close check for parent
	}
	else {
		die($message);
	}
}

function require_once_r($dir) { // include path recursively, order of load is arbitrary!!!
	global $require_path; // $suppliers,$code,
	if (!is_array($require_path)) {
		$require_path=array();
	}
	$files=scandir($dir);
	if ($files===FALSE) {
		return false;
	}
	foreach ($files as $file) {
		$path=$dir."/".$file;
		if (startswith($file,".")) {
			// do nothing with . .. and hidden
		}
		elseif (is_dir($path)) {
			array_push($require_path,$file);
			require_once_r($path);
			array_pop($require_path);
		}
		elseif (endswith($file,".php")) {
			require_once $path;
		}
	}
	return true;
}

// begin language


function dump_lang_stats() {
	global $langStats;
	$filename="/tmp/lang_stats.txt";
	// load old stats
	$oldLangStats=unserialize(@file_get_contents($filename));
	// add new
	$langStats=arr_merge($langStats,$oldLangStats);
	// write out
	file_put_contents($filename,serialize($langStats));
	//~ file_put_contents($filename,print_r($langStats,true));
}

function getBrowserLang($chosen_lang="") {
	global $localizedString;
	$langs=$_SERVER["HTTP_ACCEPT_LANGUAGE"];
	$lang_parts=explode(",",$langs);
	$avail_langs=array_keys($localizedString);
	
	if (in_array($chosen_lang,$avail_langs)) {
		return $chosen_lang;
	}
	
	if (count($lang_parts)) foreach ($lang_parts as $lang_part) {
		list($this_lang,)=explode(";",$lang_part,2);
		list($this_lang,)=explode("-",$lang_part,2);
		if (in_array($this_lang,$avail_langs)) {
			return $this_lang;
		}
	}
	
	return default_language;
}

function l($langToUse,$key,$index=null) {
	global $lang,$localizedString,$globalString,$langStats;
	
	$key=fixSp($key);
	if (langStat) {
		$langStats[$key]=true; // used
	}
	elseif (!empty($_REQUEST["lang_debug"])) {
		$diag=" ".$key.":".$index;
	}
	else {
		$diag="";
	}
	
	if (empty($langToUse)) {
		$retval="Warning: no language set."; // erleichtert Debug
	}
	elseif (isset($globalString[$key])) { // gibt es globale Einstellung?
		if (is_null($index)) { // kein Unterarray?
			$retval=$globalString[$key];
		}
		else {
			$retval=$globalString[$key][$index];
		}
	}
	/* if (!isset($localizedString[$lang][$key])) {
		echo "Warning: ".$key." is not set for language ".$lang.".";
	} */
	else {
		if ($langToUse!=$lang) {
			// load foreign language, to generate some PDFs
			loadLanguage($langToUse);
		}
		if (is_null($index)) { // kein Unterarray?
			$retval=$localizedString[$langToUse][$key];
		}
		else {
			$retval=$localizedString[$langToUse][$key][$index];
		}
	}
	if (is_array($retval)) {
		return $retval;
	}
	return $retval.$diag;
}

function s($key,$index=null) {
	global $lang;
	return l($lang,$key,$index);
}

function a($key,$mask) { // gibt array für Bitmaske zurück
	global $lang,$localizedString;
	
	$retval=array();
	$strArray=$localizedString[$lang][$key];
	for ($a=0;$a<count($strArray);$a++) {
		if ($mask & pow(2,$a)) {
			$retval[]=$strArray[$a];
		}
	}
	return $retval;
}

function m($strArray,$mask) { // gibt array für Bitmaske zurück
	$retval=array();
	for ($a=0;$a<count($strArray);$a++) {
		if ($mask & pow(2,$a)) {
			$retval[]=s($strArray[$a]);
		}
	}
	return $retval;
}
// end language

function d($var=null,$die=false) {
	global $debug,$debug_time,$debug_times;
	if (!$debug) {
		return;
	}
	$now=microtime(true);
	$bt=debug_backtrace();
	$debug_times[ $bt[0]["file"] ][ $bt[0]["line"] ]+=($now-$debug_time);
	if (!is_null($var)) {
		echo "<pre>";
		 echo $bt[0]["file"].":".$bt[0]["line"].": ".($now-$debug_time)." -: ".$now."\n";
		print_r($var);
		//~ var_dump($var);
		echo "</pre>";
		flush();
		ob_flush();
	}
	$debug_time=$now;
	if ($die) {
		die();
	}
}

function getLJstart() {
	global $person_id,$settings;
	$query_parts=array(
		"crit0=lab_journal.person_id&op0=eq&val0=".$person_id, 
		"crit1=lab_journal.lab_journal_status&op1=eq&val1=1", 
	);

	if (!empty($settings["default_lj"])) {
		$query_url="&query=<0> AND <1> AND <2>";
		$query_parts[]="crit2=lab_journal.lab_journal_id&op2=eq&val2=".$settings["default_lj"];
	}
	else {
		$query_url="&query=<0> AND <1>";
	}

	return "edit.php?goto_page=-1&table=reaction&order_by=lab_journal_entry&dbs=-1".
		$query_url.
		"&".join("&",$query_parts).
		"&".getSelfRef(array("cached_query","dbs","~script~","table"));
}

function showSpacer() {
	global $color;
	// Schrägstrich
	echo "<td class=\"spacer\"><img src=\"lib/spacer_".$color.".gif\" border=\"0\" width=\"8\"></td>";
}

function getIniSuggestion($value,$type) {
	switch ($type) {
	case "bool":
		return "=".($value?"on":"off");
	break;
	}
}

function checkExtensions() {
	global $requiredExtensions,$requiredSettings;
	$messages="<html><body>";
	for ($a=0;$a<count($requiredExtensions);$a++) {
		$extension=& $requiredExtensions[$a];
		if (!extension_loaded($extension)) {
			if (!$problem) {
				loadLanguage();
			}
			$problem=true;
			$messages.=s("ext_not_inst1").$extension.s("ext_not_inst2")."<br>";
		}
	}
	for ($a=0;$a<count($requiredSettings);$a++) {
		$requiredSetting=& $requiredSettings[$a];
		if (ini_get($requiredSetting["name"])!=$requiredSetting["value"]) {
			if (!$problem) {
				loadLanguage();
			}
			switch ($requiredSetting["class"]) {
			case "advice":
				//~ echo s("ini_set1").$requiredSetting["name"].getIniSuggestion($requiredSetting["value"],$requiredSetting["type"]).s("ini_set2")."<br>";
			break;
			default:
				$problem=true;
				$messages.=s("ini_set1").$requiredSetting["name"].getIniSuggestion($requiredSetting["value"],$requiredSetting["type"]).s("ini_set2")."<br>";
			}
		}
	}
	if ($problem) {
		$messages.="</body></html>";
		die($messages);
	}
}

function showTopLink($paramHash) { // link in topnav
	echo "<td class=\"link\"".
	ifnotempty(" style=\"width:",$paramHash["width"],"px\"")."><a".
	ifnotempty(" class=\"",$paramHash["class"],"\"")."".
	ifnotempty(" id=\"",$paramHash["id"],"\"")." href=\"".$paramHash["url"]."\"".
	ifnotempty(" target=\"",$paramHash["target"],"\"").">".$paramHash["text"]."</a></td>\n";
}

function getSupplierLogo(& $supplier_obj,$paramHash=array()) {
	$border=& $paramHash["border"];
	return "<img src=\"lib/".$supplier_obj["logo"]."\"".getTooltipP($supplier_obj["name"])." height=\"".$supplier_obj["height"]."\"".(isEmptyStr($border)?"":" border=\"".$border."\"").">";
}

function swap(&$v1,&$v2) {
	$temp=$v1;
	$v1=$v2;
	$v2=$temp;
}

function computeMD5() {
// hängt alle Parameter kommagetrennt zusammen und gibt den MD5 zurück
// we must add something secret to the data to get secure hashes, we must put this function on a seperate web server or at least compile it to binary code
	return md5(join(",",func_get_args()),true);
	// returns 16Byte binary code
}

function getFunctionParameters($data,$fields) { // erzeugt JS-Array aus einem Datenarray und einem Feldarray (Feldarray besteht aus Schlüsseln für Datenarray)
	foreach($fields as $idx => $field) {
		if ($idx!=0) {
			$retval.=",";
		}
		else {
			$retval.="new Array(";
		}
		$retval.=fixStr($data[$field]);
	}
	return $retval.")";
}

function getVarIdx($variables) { // erzeugt JS-Code, der JS-Variablen mit aufsteigenden ganzzahligen Werten erzeugt, $variables enthält die Variablennamen 
	$retval="var a=0";
	if (count($variables)==0) {
		return "";
	}
	foreach ($variables as $field) { // change to reaction_chemical_data_fields
		$retval.=",".fixSp($field)."=a++";
	}
	return $retval.";\n";
}

function getVarArray($name,$variables) { // erzeugt JS-Array mit abwechselnd "Text" aus $variables und einer aufsteigenden Ganzzahl
	$retval="var a=0;\n".$name."=new Array(";
	if (count($variables)) foreach ($variables as $idx => $param) {
		if ($idx!=0)
			$retval.=",";
		$retval.="\"".fixSp($param)."\",a++";
	}
	$retval.=");\n";
	return $retval;
}

function multi_in_array($needle,$haystack,$all=false) { // prüft, ob ein Wert aus $needle ($all=false) oder alle Werte aus needle in $haystack enthalten sind
	if ($all) {
		foreach ($needle as $ndlval) {
			if (!in_array($ndlval,$haystack))
				return false;
		}
		return true;
	}
	else {
		foreach ($needle as $ndlval) {
			if (in_array($ndlval,$haystack))
				return true;
		}
		return false;
	}
}

function getGVar($name) {
	// gibt alle globalen Einstellung aus der DB zurück
	list($result)=mysql_select_array(array(
		"table" => "global_settings", 
		"filter" => "name=".fixStrSQL($name), 
		"dbs" => "-1", 
		"limit" => 1, 
		"noErrors" => true, 
	));
	if ($result) {
		return unserialize($result["value"]);
	}
}

function getServerName() {
	$retval="http://";
	$default_port=80;
	if ($_SERVER["HTTPS"]=="on" || $_SERVER["HTTPS"]=="yes" || $_SERVER["HTTPS"]=="true" || $_SERVER["HTTPS"]=="1" || $_SERVER["HTTPS"]===1) {
		$retval="https://";
		$default_port=443;
	}
	$retval.=$_SERVER["SERVER_NAME"];
	if (!empty($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"]!=$default_port) {
		$retval.=":".$_SERVER["SERVER_PORT"];
	}
	return $retval;
}

function setGlobalVars() {
	global $table,$baseTable,$pk_name,$pk,$query,$selectTables;
	$table=$_REQUEST["table"]; // "pseudo"-tabelle
	$baseTable=$query[$table]["base_table"]; // zugrundeliegende tabelle
	//~ $pk_name=$query[$table]["short_primary"];
	$pk_name=getShortPrimary($table);
	$pk=$_REQUEST["pk"];
	$selectTables=explode(",",$_REQUEST["tableSelect"]);
}

function setGVar($name,$value) {
	global $db;
	// setzt die globalen Einstellung in der DB
	$sql_query="INSERT INTO global_settings SET name=".fixStr($name).",value=".fixBlob(serialize($value))." ON DUPLICATE KEY UPDATE value=".fixBlob(serialize($value)).";";
	mysqli_query($db,$sql_query) or die($sql_query." ".mysqli_error($db));
}

function saveUserSettings($own_data_settings=array()) {
	global $db,$person_id,$db_user,$settings,$own_data;
	if (!$person_id || $_SESSION["barcodeTerminal"]) {
		return false;
	}
	// setzt die globalen Einstellung in der DB
	$sql_query="UPDATE ".getSelfViewName($db_user)." SET ";
	for ($a=0;$a<count($own_data_settings);$a++) { // Texts only
		$name=$own_data_settings[$a];
		$sql_query.= secSQL($name)."=".fixStrSQL($own_data[$name]).",";
	}
	$sql_query.="preferences=".fixBlob(serialize($settings))." WHERE person_id=".fixNull($person_id)." LIMIT 1;";
	mysqli_query($db,$sql_query) or die($sql_query." ".mysqli_error($db));
	return true;
}

function subLogin() {
	$_REQUEST["desired_action"]="";
	$_SESSION["old_user"]=$_SESSION["user"]; // save old login data
	$_SESSION["old_password"]=$_SESSION["password"];
	$_SESSION["old_barcodeTerminal"]=$_SESSION["barcodeTerminal"];
	$_SESSION["user"]=$_REQUEST["user"]; // set new login data
	$_SESSION["password"]=$_REQUEST["password"];
	$_SESSION["barcodeTerminal"]=($_REQUEST["loginTarget"]=="barcode_terminal");
	$_REQUEST["db_server"]=$_SESSION["db_server"]; // only on same server
	$_REQUEST["db_name"]=$_SESSION["db_name"]; // only on same db
}

function subLogout() {
	// switch back to old credentials
	$_REQUEST["desired_action"]="";
	$_SESSION["user"]=$_SESSION["old_user"]; // save old login data
	$_SESSION["password"]=$_SESSION["old_password"];
	$_SESSION["barcodeTerminal"]=$_SESSION["old_barcodeTerminal"];
	$_SESSION["old_user"]="";
	$_SESSION["old_password"]="";
	$_SESSION["old_barcodeTerminal"]="";
	$_REQUEST["autoclose"]="true";
	setDbVarsFromSess();
}

function hasParentLogin() {
	return !empty($_SESSION["old_user"]) && !empty($_SESSION["old_password"]);
}

function checkSubLogout($allowLoginForm=true) {
	if ($_REQUEST["desired_action"]=="logout" && $allowLoginForm && hasParentLogin()) {
		subLogout();
	}
}

function performLogout() {
	global $db,$settings,$db_name,$db_user;
	loadLanguage();
	loginToDB(false,true); // required to delete cache
	clearCache();
	clearLocks("-1",$db);
	if ($settings["clear_on_logout"]) {
		clearSelection();
	}
	saveUserSettings();
	session_unset(); // delete session data
	showLogin($db_name,$db_user,s("session_closed"));
}

function setDbVarsFromSess() {
	global $db_server,$db_name,$db_user,$db_pw,$lang;
	$db_server=ifempty($_SESSION["db_server"],db_server);
	$db_name=$_SESSION["db_name"];
	$db_user=$_SESSION["user"];
	$db_pw=$_SESSION["password"];
	$lang=$_SESSION["user_lang"];
}

function setDbVarsFromRequ() {
	global $db_server,$db_name,$db_user,$db_pw,$lang;
	$db_server=$_REQUEST["db_server"];
	$db_name=$_REQUEST["db_name"];
	$db_user=$_REQUEST["user"];
	$db_pw=$_REQUEST["password"];
	$lang=$_REQUEST["user_lang"];
}

function pageHeader($connectDB=true,$allowLoginForm=true,$autoCloseSession=true,$readSettings=true) {
	/*
	bei desired_action=login wird geprüft, ob user/password auf db_server/db_name passen
	bei desired_action=logout wird der cache in der DB und die session gelöscht
	loggt sich in DB ein (wenn connectDB=true), bei Fehlschlag wird Login-Formular angezeigt, wenn nicht allowLoginForm=false (zB bei Bildern)
	lädt die Session-Variablen, (und schließt sie gleich wieder zum Schreiben, wenn startSession=false ist)
	*/
	global $db,$other_db_data,$other_db_disabled,$db_server,$db_user,$db_pw,$permissions,$db_name,$lang,$person_id,$page_type,$useSvg,$settings;
	if (allowSvg && isUaSvgCapable()) {
		$useSvg=true;
	}
	
	if (langStat) {
		register_shutdown_function("dump_lang_stats");
	}
	
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
	if (!ini_get("date.timezone")) {
		date_default_timezone_set("Europe/Berlin");
	}
	
	if (!$connectDB) {
		$readSettings=false;
	}
	// Allow only https
	/*
	if (empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]!="on") {
		header("Location: https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
		return false;
	}
	*/
	if (is_array($_REQUEST["dbs"]) && count($_REQUEST["dbs"])) { // transform array of dbs into comma-separated list
		$_REQUEST["dbs"]=@join(",",$_REQUEST["dbs"]);
	}
	// session is always started to get session variables
	session_name(db_type);
	session_start();
	if ($_REQUEST["desired_action"]=="sub_login") { // start barcode-terminal after logout
		subLogin();
	}
	
	/*
	print_r($_REQUEST);
	print_r($_SESSION);
	die("Y");
	*/
	
	checkSubLogout($allowLoginForm);

	if ($_REQUEST["desired_action"]=="login") { // login and password given, verify and then create session
		setDbVarsFromRequ();
		if (empty($db_name) || empty($db_user) || empty($db_pw)) {
			if ($allowLoginForm) {
				loadLanguage();
				showLogin($db_name,$db_user,$err_msg);
			}
			return false;
		}
		if ($connectDB) {
			$blockReason=checkProtocol($_SERVER["REMOTE_ADDR"],$db_user);
			if ($blockReason!=-1) {
				loadLanguage();
				switch ($blockReason) {
				case 1:
					$err_msg=s("ip_blocked");
				break;
				case 2:
					$err_msg=s("user_blocked");
				break;
				}
				if ($allowLoginForm) {
					showLogin($db_name,$db_user,$err_msg);
				}
				return false;
			}
			
			if (!loginToDB($allowLoginForm)) {
				return false;
			}
			
			$openingBarcodeTerminal=false;
			if ($permissions & _barcode_user) { // force barcode terminal
				$_REQUEST["loginTarget"]="barcode_terminal";
				$openingBarcodeTerminal=true;
			}
			elseif ($db_user!=ROOT && $_REQUEST["loginTarget"]=="barcode_terminal") {
				$_REQUEST["loginTarget"]="inventory";
			}
			
			// connection successful, create session
			session_unset(); // kill all data if no prior logoff was performed
			$_SESSION["user"]=$db_user;
			$_SESSION["password"]=$db_pw;
			$_SESSION["user_lang"]=$lang;
			
			// compare Session and Request, can be kept the same for sub_logins
			$_SESSION["client_ip"]=$_SERVER["REMOTE_ADDR"]; // may be used for same ip policy check
			if ($_REQUEST["autoclose"]=="true" && empty($_GET["password"]) && !empty($_REQUEST["sess_proof"])) { // do fix for logins via post
				// fix sess proof
				$_SESSION["sess_proof"]=$_REQUEST["sess_proof"];
			}
			else {
				$_SESSION["sess_proof"]=uniqid();
			}
			
			// make abuse of barcode more difficult
			$_SESSION["barcodeTerminal"]=$openingBarcodeTerminal;
			
			$_SESSION["db_server"]=db_server;
			$_SESSION["db_name"]=$db_name;
			// $_SESSION["permissions"]=$permissions;
			// $_SESSION["person_id"]=$person_id;
			// $_SESSION["language"]=$lang;
			
			loadLanguage();
			clearCache();
			
			$_SESSION["db_permissions"]=array();
			$_SESSION["other_db_disabled"]=array();
			
			// determine remote permissions $other_db_data
			for ($a=0;$a<count($other_db_data);$a++) {
				$dbObj=getForeignDbObjFromData($other_db_data[$a]);
				if (!$dbObj) {
					$_SESSION["other_db_disabled"][]=$other_db_data[$a]["other_db_id"];
					continue;
				}
				list($db_person_data)=mysql_select_array_from_dbObj("* FROM ".getSelfViewName($other_db_data[$a]["db_user"])." LIMIT 1;",$dbObj,array("noErrors" => true, ));
				$_SESSION["db_permissions"][ $other_db_data[$a]["other_db_id"] ]=$db_person_data["permissions"];
				mysqli_close($dbObj);
			}
		}
		$retval=true;
	}
	elseif (empty($_SESSION["user"])) { // keine Sitzung, aber ggf. Username
		loadLanguage();
		if ($allowLoginForm) {
			showLogin($_REQUEST["db_name"],$_REQUEST["user"],s("logon"));
		}
	}
	else { // use existing session
		$retval=pageHeader2($connectDB,$allowLoginForm,$autoCloseSession,$readSettings);
	}
	pageHeader3($connectDB,$allowLoginForm,$autoCloseSession,$readSettings);
	return $retval;
}

function pageHeader2($connectDB=true,$allowLoginForm=true,$autoCloseSession=true,$readSettings=true) { // stage 2
	setDbVarsFromSess();
	if ($_REQUEST["desired_action"]=="logout" && $allowLoginForm) { // logout, dont allow logout using gif, etc
		performLogout();
	}
	if ($connectDB) {
		$retval=loginToDB($allowLoginForm,$readSettings);
	}
	else {
		$retval=true;
	}
	loadLanguage();
	return $retval;
}

function pageHeader3($connectDB=true,$allowLoginForm=true,$autoCloseSession=true,$readSettings=true) { // stage 3
	global $page_type,$common_libs;
	if ($autoCloseSession) { // immediately close session to prevent blocking
		@session_write_close(); // suppress nasty error message
	}
	if ($allowLoginForm) {
		// ob_start();
		if ($page_type=="plain") {
			// do nothing
		}
		elseif ($page_type=="frame") { // $frame_page
			echo <<<END
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
   "http://www.w3.org/TR/html4/frameset.dtd">
END;
		}
		else {
			echo <<<END
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Strict//EN">
END;
		}
		
		$transferParameters=array_key_filter($_REQUEST,array("list_int_name","UID","field","group","beforeUID","editDbId","editPk","sess_proof"));
		if ($page_type!="plain") {
			echo "
<html>
<head>
<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">".
loadJS(arr_merge($common_libs,array("message.js")),"lib/").
loadJS(array("static.js.php","dynamic.js.php"));
			
			if ($page_type!="async") { // performance tuning
				echo "<link rel=\"stylesheet\" href=\"ChemDoodle/ChemDoodleWeb.css\" type=\"text/css\">";
				echo "<link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"favicon.ico\" />";
			}
			
			echo script."
var transferParameters=".json_encode($transferParameters).";".
_script;
		}
	}
}

function addRecordDefinition(& $fields,$tabname,$action) { // Array
	$fields[getActionBy($tabname,$action)]=array("type" => "TINYTEXT", "search" => "auto", );
	$fields[getActionWhen($tabname,$action)]=array("type" => "DATETIME", "search" => "auto", );
	$fields[$tabname."_".$action."_hashver"]=array("type" => "INT", "flags" => FIELD_MD5, );
	$fields[$tabname."_".$action."_md5"]=array("type" => "VARBINARY(128)", "flags" => FIELD_MD5, );
}

function addSuffixColumn(& $fields,$tabname,$suffix,$priority=null) {
	$fields[$tabname."_".$suffix]=array("type" => "BOOL", "search" => "bool", "index" => true, );
	if (!is_null($priority)) {
		$fields[$tabname."_".$suffix]["searchPriority"]=$priority;
	}
}

function addDisabledColumn(& $fields,$tabname) {
	return addSuffixColumn($fields,$tabname,"disabled");
}

function addSharedColumn(& $fields,$tabname,$tabdata,$priority=null) { // Array
	if ($tabdata["defaultSecret"]) {
		addSuffixColumn($fields,$tabname,"shared",$priority);
	}
	else {
		addSuffixColumn($fields,$tabname,"secret",$priority);
	}
}

function getBarcodeFieldType($table) {
	global $barcodePrefixes;
	if (count($barcodePrefixes)) foreach ($barcodePrefixes as $prefix => $data) {
		$baseTable=getBaseTable($data["table"]);
		if ($baseTable!=$table) {
			continue;
		}
		if ($data["field"]=="field") {
			$field=$data["type"];
			if ($field=="") {
				//~ $field="BIGINT"; // suitable for EAN13 etc.
				$field="VARBINARY(20)";
			}
			return $field;
		}
	}
}

function addBarcodeColumn($tabname) { // Array
	global $tables;
	
	$fieldType=getBarcodeFieldType($tabname);
	if ($fieldType) {
		$tables[$tabname]["fields"][ getBarcodeFieldName($tabname) ]=array(
			"type" => $fieldType, 
		);
	}
}

function addPkColumn($tabname) {
	global $tables;
	$tables[$tabname]["fields"][ getPkName($tabname) ]=array(
		"type" => "INT NOT NULL AUTO_INCREMENT PRIMARY KEY", 
		"pk" => true,
	);
}

function prepareTables() {
	global $tables,$query;
	
	foreach ($tables as $tabname => $tabdata) {
		if (!$tabdata["noPk"]) {
			addPkColumn($tabname);
		}
		
		// expand multiple
		if (count($tabdata["fields"])) foreach ($tabdata["fields"] as $name => $data) {
			if ($data["multiple"]>0) {
				for ($a=$data["start"];$a<$data["start"]+$data["multiple"];$a++) {
					$tables[$tabname]["fields"][$name.$a]=$data;
					unset($tables[$tabname]["fields"][$name.$a]["multiple"]);
				}
				unset($tables[$tabname]["fields"][$name]); // remove origin
			}
		}
		
		if (hasTableRemote($tabname)) {
			addSharedColumn($tables[$tabname]["fields"],$tabname,$tabdata,90);
		}
		
		if ($tabdata["useDisabled"]) {
			addDisabledColumn($tables[$tabname]["fields"],$tabname);
		}
		
		if ($tabdata["recordCreationChange"]) {
			addRecordDefinition($tables[$tabname]["fields"],$tabname,"created");
			addRecordDefinition($tables[$tabname]["fields"],$tabname,"changed");
		}
		
		addBarcodeColumn($tabname);
		
		if ($tables[$tabname]["versioning"]) {
			$shortPkName=getShortPrimary($tabname);

			$archive_table=getArchiveTable($tabname);
			$tables[$archive_table]=$tables[$tabname]; // copy everything
			unset($tables[$archive_table]["index"]); // certain unique conditions make probs
			unset($tables[$archive_table]["useDisabled"]);
			unset($tables[$archive_table]["recordCreationChange"]);
			
			if (!$tables[$archive_table]["noPk"]) {
				addPkColumn($archive_table);
			}
			
			$tables[$archive_table]["versioning"]=false;
			$tables[$archive_table]["readPermRemote"]=0;
			$tables[$archive_table]["writePermRemote"]=0;
			$tables[$archive_table]["archiveFor"]=$tabname;
			$tables[$archive_table]["engine"]=archive_storage_engine; // use MyISAM for Archive
			$tables[$archive_table]["fields"][ getPkName($tabname) ]=array("type" => "INT UNSIGNED", "fk" => $tabname);
			$tables[$archive_table]["fields"]["archive_entity_id"]=array("type" => "INT UNSIGNED", );
			$tables[$archive_table]["fields"]["version_comment"]=array("type" => "TINYTEXT", );
			$tables[$archive_table]["fields"]["is_autosave"]=array("type" => "BOOL", );
			
			$query[$archive_table]=array(
				"base_table" => $archive_table, 
				"distinct" => true, 
			);
			
			// sparsame Abfrage über andere existierende Versionen
			$query[$archive_table]["fields"]=($tables[$archive_table]["versionAnchor"]?getLongPrimary($archive_table)." AS ":"")."archive_entity_id,version_comment,is_autosave";
			
			if ($tabdata["recordCreationChange"]) {
				$action="changed";
				$query[$archive_table]["fields"].=",".getActionBy($tabname,$action)." AS version_by,".getActionWhen($tabname,$action)." AS version_when";
			}
			
			// add subquery to $tabname to get all versions
			$query[$tabname]["subqueries"][]=array(
				"name" => "versions", 
				"table" => $archive_table, 
				"criteria" => array($archive_table.".".$shortPkName."="), 
				"variables" => array($shortPkName), 
				"conjunction" => "AND", 
				"forflags" => QUERY_EDIT, 
				"order_obj" => array(
					array("field" => "archive_entity_id", "order" => "DESC"), // neueste oben
				),
			);
		}
	}
	//~ print_r($query);die();
}

function handleDatabaseAccessError($allowLoginForm,$permissionError=false) {
	global $db,$db_user,$db_name;
	loadLanguage();
	if ($permissionError) {
		$_REQUEST["desired_action"]="logout";
		$err_msg=s("login_acces_denied1").strip_tags($db_name).s("login_acces_denied2");
		
		// add to protocol, ban IP after 4 attempts, block account for 30 min after 4 attempts
		addToProtocol($_SERVER["REMOTE_ADDR"],$db_user);
	}
	else {
		$err_no=mysqli_connect_errno();
		switch ($err_no) {
		case 1045:
			// falsches passwort
			$err_msg=s("login_wrong_pass");
			
			// add to protocol, ban IP after 4 attempts, block account for 30 min after 4 attempts
			addToProtocol($_SERVER["REMOTE_ADDR"],$db_user);
		break;
		case 2002:
			// db läuft net
			$err_msg=s("login_db_outoforder");
		break;
		case 1044:
			// keine zugriffsberecht
			$err_msg=s("login_acces_denied1").strip_tags($db_name).s("login_acces_denied2");
			
			// add to protocol, ban IP after 4 attempts, block account for 30 min after 4 attempts
			addToProtocol($_SERVER["REMOTE_ADDR"],$db_user);
		break;
		case 1049:
			// datenbank existiert nicht
			$err_msg=s("login_db_not_exist1").strip_tags($db_name).s("login_db_not_exist2");
			
			// add to protocol, ban IP after 4 attempts, block account for 30 min after 4 attempts
			addToProtocol($_SERVER["REMOTE_ADDR"],$db_user);
		break;
		default:
			$err_msg=mysqli_connect_error().$err_no;
		}
	}
	if ($allowLoginForm) {
		showLogin($db_name,$db_user,$err_msg);
	}
}

function loginToDB($allowLoginForm=true,$readSettings=true) {
	/*
	Anmeldung an Datenbank mit globalen Login-Daten, db wird globaler Handler für eigene Datenbank
	setzen von globalen Permissions und Sprache für Person (geht ohne DB nicht)
	*/
	global $db,$db_uid,$db_server,$db_user,$db_pw,$permissions,$db_name,$person_id,$query,$barcodeTerminal;
	checkExtensions();
	$db=@mysqli_connect(db_server,$db_user,$db_pw);
	if (!$db) {
		handleDatabaseAccessError($allowLoginForm);
		return false;
	}
	if (!$barcodeTerminal && $_SESSION["barcodeTerminal"]) {
		return false;
	}
	prepareTables();
	if ($db_user==ROOT && $_REQUEST["desired_action"]=="login") { // Tabellen nur bei Login erstellen, Login in DB ist bereits erfolgt
		require_once "lib_root_funcs.php";
		loadLanguage();
		$err_msg=setupInitTables($db_name);
		if ($err_msg!==TRUE) {
			if ($allowLoginForm) {
				showLogin($db_name,$db_user,$err_msg);
			}
			return false;
		}
	}
	//~ if (!mysqli_query($db,"USE ".secSQL($db_name))) {
	if (!switchDB($db_name,$db)) {
		handleDatabaseAccessError($allowLoginForm);
		return false;
	}
	if (loginHeals && $_REQUEST["desired_action"]=="login") {
		checkProtocol($_SERVER["REMOTE_ADDR"],$db_user,true);
	}
	
	setUserInformation($readSettings); // Permissions, etc. setzen
	if ($permissions==0) {
		handleDatabaseAccessError($allowLoginForm,true);
		return false;
	}
	$db_uid=getGVar("UID");
	if (!empty($person_id)) { // set filters for messaging tables
		// $query["cache"]["filter"]="person_id=".$person_id; 
		// dont be so strict, all_ queries are common for all
		$query["message_new"]["filter"]="message_person.completion_status=1 AND message_person.person_id=".fixNull($person_id);
		$query["message_in"]["filter"]="message_person.person_id=".fixNull($person_id);
		$query["message_out"]["filter"]="message.from_person=".fixNull($person_id);
		$query["my_chemical_order"]["filter"]="ordered_by_person=".fixNull($person_id);
		//~ $query["central_chemical_order"]["filter"]="accepted_by_user=".fixStrSQL($db_user); // everything taken by central/Linder/..
		
		if ($permissions & _order_accept) { // MPI
			$query["molecule"]["subqueries"][]=array(
				"name" => "mat_stamm_nr", 
				"table" => "mat_stamm_nr", 
				"criteria" => array("molecule_id="), 
				"variables" => array("molecule_id"), 
				"conjunction" => "AND", 
				"forflags" => QUERY_EDIT, 
			);
		}
		
		// project_members_only
		if ($permissions & _lj_read_all) {
		
		}
		else {
			$member_only_filter="(project_members_only IS NULL OR project_person.person_id=".fixNull($person_id).")";
			$query["reaction"]["filter"]=$member_only_filter;
			$query["reaction_copy"]["filter"]=$member_only_filter;
			$query["analytical_data"]["filter"]=$member_only_filter;
			$query["analytical_data_check"]["filter"]=$member_only_filter;
			$query["analytical_data_spz"]["filter"]=$member_only_filter;
			
			$query["analytical_data_gif"]["filter"]=$member_only_filter;
			$query["analytical_data_image_gif"]["filter"]=$member_only_filter;
			
			$query["reaction_gif"]["filter"]=$member_only_filter;
			$query["reaction_svg"]["filter"]=$member_only_filter;
			$query["reaction_mol"]["filter"]=$member_only_filter;
			
			$query["reaction_chemical_gif"]["filter"]=$member_only_filter;
			$query["reaction_chemical_svg"]["filter"]=$member_only_filter;
			$query["reaction_chemical_mol"]["filter"]=$member_only_filter;
		}
	}
	return true;
}

function closeWin() {
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Strict//EN\"> 
<html>
<head>
<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">".
script."
	self.close();
"._script."
</head>
<body>
</body>
</html>";
}

function getLoginURL() {
	global $permissions,$settings,$loginTargets;
	if (empty($_REQUEST["loginTarget"])) {
		$_REQUEST["loginTarget"]=$settings["default_login_target"];
	}
	if (empty($_REQUEST["loginTarget"])) {
		$default_settings=getDefaultUserSettings();
		$_REQUEST["loginTarget"]=$default_settings["default_login_target"];
	}
	return $loginTargets[ $_REQUEST["loginTarget"] ]."&".getSelfRef(array("~script~"));
}

function showLogin($db_name,$user,$err_msg) {
	global $page_type,$lang,$g_settings,$common_libs,$default_db_name;
	if ($db_name=="") {
		$db_name=$default_db_name;
	}
		
	if ($page_type=="async") {
		// zeigt popup zum login
		// autoclose: 
		// 1. reload von opener
		// 2. schließen des fensters
		echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Strict//EN\"> 
<html>
<head>
<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">".loadJS(array("misc.js"),"lib/").
script."
window.open(\"index.php?autoclose=true&db_name=".strip_tags($db_name)."&user=".strip_tags($user)."&sess_proof=".fixTags($_REQUEST["sess_proof"])."\");
"._script."
</head>
<body>
</body>
</html>";
	}
	else {
		if ($_REQUEST["autoclose"]=="true") {
			$err_msg.="<br>".s("autoclose_note");
		}
		
		// zeigt Login-Seite mit Msg $err_msg und ggf. Benutzervorschlag $user an
		echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Strict//EN\"> 
<html>
<head>
<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">
<link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"favicon.ico\" />
<title>".
s("list_of_chemicals_title")." ".$g_settings["organisation_name"]." - ".s("logon").
"</title>".
loadJS(array("static.js.php","dynamic.js.php")). // for browser detection and language
loadJS($common_libs,"lib/").
script."
if (top.location.href!=self.location.href) {
	top.location.href=self.location.href;
}
function prepareLogin(loginTarget) {
	setInputValue(\"loginTarget\",loginTarget);
	//~ submitForm(\"login\");
}
"._script.
stylesheet.
style."
html, body { position:absolute;top:0px;left:0px;height:100%;width:100%;margin:0px;padding:0px;text-align:center;background-image:url(lib/sidenav_new_down.png);background-repeat:repeat-y }
#wrapper { position:absolute;left:0px;top:0px }
#login { text-align:left }
#bg {position:absolute;left:0px;top:0px;width:100%;height:100%;text-align:left;background-image:url(lib/top_blue.png);background-repeat:repeat-x }
#header {position:absolute;left:0px;top:0px;width:100%;vertical-align:middle;z-index:1 }
#middle {border-width:0px;text-align:center;font-size:12pt;font-family:Arial;color:#132F90 }
#middle a {font-size:11pt;font-family:Arial;color:#132F90;line-height:100%;text-align:center;text-decoration:none}"
._style."
</head>
<body>
<div id=\"bg\"><img src=\"lib/sidenav_new.png\"></div>
<table id=\"header\" class=\"noborder\"><tr>
	<td style=\"width:206px\">
		".getImageLink($g_settings["links_in_topnav"]["uni_logo"])."
	</td>
	<td id=\"middle\">
		<img src=\"lib/open_env_logo.png\" border=\"0\" height=\"58\" width=\"454\"><br>".s("list_of_chemicals_title").s("copy_short")."
	</td>
	<td style=\"width:200px\">
		".getImageLink($g_settings["links_in_topnav"]["fb_logo"])."
	</td>
</tr></table>
<table id=\"wrapper\" class=\"noborder\" height=\"70%\" style=\"margin:150px 10px 10px 340px;width:750px\">
<tr>
<td align=\"center\" valign=\"middle\">

<b>".
script."
var no_cookies=false;
if (typeof document.cookie==\"undefined\") {
	no_cookies=true;
}
else if (document.cookie.indexOf(".fixStr(db_type).")==-1) {
	// Test further
	document.cookie=\"cookietest=true\";
	if (document.cookie.indexOf(\"cookietest\")==-1) {
		no_cookies=true;
	}
}

if (no_cookies==true) {
	document.write(".fixStr(s("cookies_required")).");
}

if (isFF3x) {
	// perfect: FF 3.5
	
	// do nothing
}
else if (navigator.userAgent.indexOf(\"MSIE 7.0\")>=0 || navigator.userAgent.indexOf(\"MSIE 8.0\")>=0 || isChrome || isSafari || isOpera) {
	// almost perfect: IE7,8, Chrome, Opera 10, Safari
	
	// do nothing
}
else if (navigator.userAgent.indexOf(\"Firefox/3.0\")>=0) {
	// other recommended: FF 3.0
	document.write(".fixStr(s("other_browser_recommended")).");
}
else {
	// not working: IE6, FF 2.0, other stuff
	document.write(".fixStr(s("other_browser_required")).");
}

function changeLang() {
	var url=".fixStr(getSelfRef(array("user_lang"))."&user_lang=")."+getInputValue(\"user_lang\");
	self.location.href=url;
}
"._script.
"<noscript>".s("javascript_required")."</noscript><br>".
$err_msg."
</b>

<br><form id=\"login\" name=\"login\" method=\"post\" action=\"index.php?".getSelfRef(array("~script~","table"))."\">
<table id=\"login\" class=\"noborder blind\">
	<tr><td colspan=\"2\">".s("please_logon_to1")."</td></tr>
	<tr><td>".s("database")."</td><td><input type=\"text\" name=\"db_name\" id=\"db_name\" value=".fixStr(strip_tags($db_name),true)." size=\"16\"></td></tr>
	<tr><td colspan=\"2\">".s("please_logon_to2")."</td></tr>
	<tr><td>".s("db_user").":</td><td><input type=\"text\" name=\"user\" id=\"user\" value=".fixStr(strip_tags($user),true)." size=\"16\" maxlength=\"16\"></td></tr>
	<tr><td>".s("db_pass").":</td><td><input type=\"password\" name=\"password\" id=\"password\" value=\"\" size=\"16\"></td></tr>
	<tr><td>".s("user_lang").":</td><td>".
	showLanguageSelect(array(
		"int_name" => "user_lang", 
		"text" => "", 
		"allowDefault" => true, 
		"value" => $_REQUEST["user_lang"], 
	)).
	"<a href=\"Javascript:changeLang();\"><img src=\"lib/next.png\" border=\"0\"></a></td></tr>
	<tr><td colspan=\"2\"><input type=\"hidden\" name=\"desired_action\" value=\"login\">
		<input type=\"hidden\" name=\"autoclose\" id=\"autoclose\" value=".fixStr($_REQUEST["autoclose"]=="true"?"true":"").">
		<input type=\"hidden\" name=\"loginTarget\" id=\"loginTarget\" value=\"\">";
		
		if ($_REQUEST["autoclose"]=="true") {
			echo simpleHidden("sess_proof"). // conserve old sess_proof
				"<input type=\"submit\" value=".fixStr(s("continue"))." onClick=\"prepareLogin(&quot;&quot;)\">";
		}
		else {
			echo getHiddenSubmit()."
<input type=\"submit\" value=".fixStr(s("login_inventar"))." onClick=\"prepareLogin(&quot;inventory&quot;)\">";
			if (!$g_settings["disable_login_lab_journal"]) {
				echo "<input type=\"submit\" value=".fixStr(s("login_lj"))." onClick=\"prepareLogin(&quot;lab_journal&quot;)\">";
			}
			if (time()<showUpdateInfoUntil) {
				echo "<br><b>".s("update_info")."<b/>";
			}
		}
		
		echo "</td></tr>
</table>
</form>

<div style=\"color:gray;text-align:justify\"><small>".s("licence")."</small></div>
</td></tr></table>".
script;
		if (hasParentLogin()) {
			subLogout(); // recover old session data
			echo "self.close();\n";
		}
		elseif ($_REQUEST["autoclose"]=="true") {
			echo "$(\"password\").focus();\n";
		}
		else {
			if (!empty($db_name) && !empty($user)) {
				echo "$(\"password\").focus();\n";
			}
			else {
				echo "$(\"user\").focus();\n";
			}
			echo "
if (opener) {
	try { // IE crap
		opener.location.href=".fixStr(getSelfRef()).";
	}
	catch (e) {
		// ignore
	}
	self.close();
}
";
		}
		echo _script."
</body>
</html>";
	}
	exit();
}

function displayFatalError($msg_key) {
	echo stylesheet."</head>
<body>".s("fatal_error").s($msg_key)."</body>
</html>";
	exit();
}

function completeDoc() {
	// schreibt Debug-Informationen ans Ende, schließt DB und Session (eigentlich überflüssig, passiert am Ende sowieso
	global $db,$other_db_data;
	// close open connections to other dbs
	for ($a=0;$a<count($other_db_data);$a++) {
		$conn=& $other_db_data[$a]["connection"];
		if ($conn) {
			mysqli_close($conn);
		}
	}
	mysqli_close($db);
	// @session_write_close();
}

?>
