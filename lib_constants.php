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
Konstanten wie Berechtigungs-Bitmasken, Aufzählungen von Gefahrsymbolen und SET-Namen sowie die Reihenfolge und Anzeigeeigenschaften von 
Tabellenspalten
*/

require_once "lib_global_funcs.php";
require_once "lib_db_query_helper.php";
require_once "lib_constants_permissions.php";

define("db_type","enventory");
define("currentVersion",0.813);
define("showUpdateInfoUntil",1272638817);
//~ echo strtotime("+1 week")."";

// locking
define("UNLOCK",1);
define("LOCK",2);
define("RENEW",3);

// handle desired action
define("NO_ACTION",0);
define("SUCCESS",1);
define("FAILURE",2);
define("QUESTION",3);
define("SELECT_SUCCESS",4);
define("ABORT_PROCESS",5);
define("INFORMATION",6);

// list column settings
define("DEFAULT_ON",0);
define("DEFAULT_OFF",1);
define("NO_OFF",2); // do not allow user to switch off
define("NO_ON",4); // do not allow user to switch on

// SQL data preparation
define("SQL_NUM",2);
define("SQL_TEXT",3);
define("SQL_BLOB",4);
define("SQL_SET",5);
define("SQL_DATE",6);
define("SQL_DATETIME",7);
define("SQL_URLENCODE",8);

// distinct-like things
define("NONE",0);
define("GROUP_BY",1);
define("DISTINCT",2);

// result caching
define("CACHE_COMMON",0);
define("CACHE_INDIVIDUAL",1);
define("CACHE_OFF",2);

// result format
define("RESULTS_FLAT",0);
define("RESULTS_PK_ONLY",1);
define("RESULTS_HIERARCHICAL",2);

// Query flags
define("QUERY_SIMPLE",0);
define("QUERY_EDIT",1);
define("QUERY_LIST",2);
define("QUERY_SKIP_UID_JOIN",4); // prevent loops
define("QUERY_SUBQUERY_FLAT_PRIORITY",8); // give flattened values from subquery priority to values from "main query"
define("QUERY_CREATE",16);
define("QUERY_PK_SEARCH",32);
define("QUERY_CUSTOM",64);

// field flags for query
define("FIELD_FINGERPRINT",1);
define("FIELD_MSDS",2);
define("FIELD_IMAGE",4);
define("FIELD_MOLECULE",8);
define("FIELD_MD5",16);
define("FIELD_RESERVE",32); // not queried
define("FIELD_FULLTEXT",64); // allows searching within PDFs, HTML with<b>out</b> tags

// flags for positioning overlay divs
define("OVERLAY_CENTER",1);
define("OVERLAY_RIGHT",2);
define("OVERLAY_MIDDLE",4);
define("OVERLAY_BOTTOM",8);
define("OVERLAY_LIMIT_TOP",16);
define("OVERLAY_LIMIT_BOTTOM",32);
define("OVERLAY_LIMIT_LEFT",64);
define("OVERLAY_LIMIT_RIGHT",128);
define("OVERLAY_SCROLL_X",256);
define("OVERLAY_SCROLL_Y",512);
define("OVERLAY_HIDE_SHORT_X",1024);
define("OVERLAY_HIDE_SHORT_Y",2048);
define("OVERLAY_CONT_UPDATE",OVERLAY_SCROLL_X+OVERLAY_SCROLL_Y+OVERLAY_HIDE_SHORT_X+OVERLAY_HIDE_SHORT_Y);


// flags for positioning overlay divs
define("SP_DIR_ONLY",1);
define("SP_ZIP",2);
define("SP_FTP",4);

// stereochemistry relationships
define("STEREO_UNDEFINED",0);
define("STEREO_IDENTICAL",1);
define("STEREO_ENANTIOMERS",2);
define("STEREO_DIASTEREOMERS",3);
define("STEREO_DIFFERENT",4);

// bar diagram style
define("DIAGRAM_BAR_HIDDEN",0);
define("DIAGRAM_BAR_SINGLE",1);
define("DIAGRAM_BAR_STACKED",2);
define("DIAGRAM_BAR_STACKED_HIGHLIGHTED",3);

// substructure: inverting aromatic systems
define("SUBST_INVERT_ANY",0); // inverted mode may be activated
define("SUBST_INVERT_OFF",1); // inverted mode may NOT be activated
define("SUBST_INVERT_ON",2); // inverted mode is on

// form constants
$form_constants=array(
	"READONLY" => "rO", 
	"DEFAULTREADONLY" => "drO", 
	"VISIBLE" => "vi", 
	"TABLEMODE" => "tM", 
	"SPLITMODE" => "sM", 
	"LOCKED" => "Lo", 
	"DEFAULTLOCKED" => "dLo", 
);
foreach ($form_constants as $constant => $value) define($constant,$value);

define("CHARSET_TEXT","utf8");
define("COLLATE_TEXT","utf8_unicode_ci");
define("CHARSET_BIN","utf8");
define("COLLATE_BIN","utf8_bin");

$reaction_chemical_lists=array("reactants","reagents","products");

// diagram
define("bar_height",10);
define("bar_width",100);
$diagram_colors=array(
	"#7FFF00", // "chartreuse", grasgrün
	"#00BFFF", // "deepskyblue"
	"#FFD700", // "gold"
	"#8FBC8F", // "darkseagreen"
	"#FF0000", // "red"
); // for each product

// available languages
$localizedString=array(
	"de" => array("language_name" => "Deutsch"), 
	"en" => array("language_name" => "English"), 
	"fr" => array("language_name" => "Français"), 
	"es" => array("language_name" => "Español"), 
	"it" => array("language_name" => "Italiano"),  
	"pt" => array("language_name" => "Português"),  
);

define("fingerprint_count",16);
define("fingerprint_bit",31); // avoid PHP unsigned long problems
define("emp_formula_sort_fill",3); // fill atom numbers to this many digits

$allowedTags=array("div","span","ol","ul","li","p","pre","h1","h2","h3","h4","h5","h6","font","table","colgroup","col","thead","tbody","tfoot","tr","th","td","hr","br","u","b","i","em","sub","sup","strike","strong",); // div for justify
$analyticsAllowedProtocols=array("ftp","biotage");
$forbidden_db_names=array("mysql","information_schema","wikidb", "phpmyadmin", "performance_schema", );

//~ die(strip_tagsJS("<font color=\"red\">Bla</font>",$allowedTags));

define("daySec",24*60*60);
define("sNULL","null"); // make compatible with Javascript
define("ROOT","root");

$common_libs=array("json2.js","variables.js","formatting.js","units.js","misc.js","array.js","safe_dom.js","barcode.js","rxnfile.js");

$langKeys=array();
$langKeys["list_op"]=array("new_search","search_in_results","attach_to_results","exclude_results","goto_previous_results",);
$langKeys["reaction_started_when"]=array("today","yesterday","last_7_days","last_30_days","from_to",);

// can be activated/deactivated in global settings
$reaction_conditions=array(
	"solvent" => array("size" => 30, "search_size" => 10, "search_op" => "ct", ), 
	"solvent_amount" => array(), 
	"temperature" => array(), 
	"press" => array(), 
	"duration" => array(), 
	"h2press" => array(), 
	"grounding_time" => array(), 
	"lambda" => array("bottom" => true, "size" => 30, ), 
	"rho_bulk" => array("bottom" => true, "size" => 30, ), 
	"panel_weight_before" => array("bottom" => true, "size" => 30, ), 
	"panel_weight_after" => array("bottom" => true, "size" => 30, ), 
	"len" => array("bottom" => true, "size" => 30, ), 
	"width" => array("bottom" => true, "size" => 30, ), 
	"height" => array("bottom" => true, "size" => 30, ), 
	"dryness" => array("bottom" => true, "size" => 30, ), 
	"location" => array("bottom" => true, "size" => 30, "search_size" => 10, "search_op" => "ct", ), 
);

$excludedNames=array("-","(none)","{Error}",); // Namen, die ausgefiltert werden

$chemical_storage_sizes=array(1,5,10,25,50,100,250,500,750,1000,2500,5000);
$chemical_storage_levels=array(100,75,50,25,10,0);

$iso_protection_symbols=array("M001","M002","M004","M005","M008","M009","M010","M011","M013","M016","M017","M022","M026",);
$iso_no_symbols=array("P003","P011",);
$iso_emerg_symbols=array("F006",);
$iso_first_aid_symbols=array("E003","E004","E009","E011","E012",);

define("lab_journal_open",1);
define("reaction_open",4);
define("yield_digits",3);
define("yield_mode",4); // sign
define("scrollInt",50);

define("invalidSQLDate","0000-00-00");
define("invalidSQLDateTime","0000-00-00 00:00:00");

require_once "lib_constants_tables.php";
require_once "lib_constants_queries.php";
require_once "lib_constants_columns.php";
require_once "lib_constants_barcode.php";
require_once "lib_constants_order_by.php";
require_once "lib_constants_paper.php";
require_once "lib_constants_form.php";
require_once "lib_constants_view.php";

define("zeroC",273.15);
define("csv_sep",",");
define("csv_line","\n");
define("http","http://");
define("doi",http."dx.doi.org/");

define("script","<script language=\"Javascript\" type=\"text/javascript\">");
define("_script","</script>");
define("style","<style type=\"text/css\">");
define("_style","</style>");
define("stylesheet","<link href=\"style.css.php\" rel=\"stylesheet\" type=\"text/css\"><link href=\"lib/jsDatePick_ltr.min.css\" rel=\"stylesheet\" type=\"text/css\">");

define("svg_header","<?xml version=\"1.0\" ?><!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" 
  \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\">");

$requiredExtensions=array("ftp","gd","hash","json","mbstring","mysqli","session","zlib");
$requiredSettings=array(
	array("name" => "safe_mode", "value" => 0, "type" => "bool", ),
	array("name" => "register_globals", "value" => 0, "type" => "bool", ),
	array("name" => "magic_quotes_gpc", "value" => 0, "type" => "bool", ),
	array("name" => "magic_quotes_runtime", "value" => 0, "type" => "bool", ),
	array("name" => "magic_quotes_sybase ", "value" => 0, "type" => "bool", ),
	//~ array("name" => "register_long_arrays", "value" => 0, "type" => "bool", "class" => "advice", ),
	//~ array("name" => "register_argc_argv", "value" => 0, "type" => "bool", "class" => "advice", ),
);
// advice not implemented yet, leads to unwanted loading of standard language

$method_aware_types=array("nmr"); // Analytikarten, wo die Methode als eigene Art gilt (GC/LukasX==GC/LukasN, 1H-NMR!=13C-NMR)

$analytics_img_params=array(
	"mime" => "image/png", 
	"format" => "png",
	"width" => 800,  
	"height" => 600, 
);

$loginTargets=array(
	"inventory" => "main.php?desired_action=search", 
	"lab_journal" => "lj_main.php?", 
	"order_system" => "main.php?desired_action=order", 
	"edit_supplier_offers" => "main.php?desired_action=search&table=supplier_offer", 
	"edit_literature" => "lj_main.php?desired_action=search&table=literature", 
	"barcode_terminal" => "barcodeTerminal.php?", 
); // Sicherheit

$export_formats=array(
	"xls", 
	"zip/xls", 
	"csv", 
	"zip/csv", 
	"sdf", 
);

$reaction_order_keys=array("lab_journal_entry", "reaction_started_when", "-reaction_started_when", );

// parameter subtableN=molecule_names => aus critN
$numSearchModes=array("bt","gt","lt","eq","nu"); // bt: between (von-bis, bei einem Wert Toleranz), gt: greater than, lt: lower than, eq: equals, nu: IS NULL
$searchModes=array(
	"text" => array("ca","co","ct","ex","sw","ew","nu"), // ca: contains ALL (Liste von Wörtern, ggf. quot), co: contains ONE ct: contains (kompletter Text), ex: exakt, sw: starts with, ew: ends with, nu: IS NULL
	"num" => $numSearchModes, // density_20
	"num_unit" => $numSearchModes, // m_brutto, volume, rc_amount, amount, actual_amount
	"money" => $numSearchModes, 
	"range" => $numSearchModes, // mp, bp
	"bool" => array("on","of",), 
	"date" => array("db","af","bf","de","du"), // db: date between, af: after, bf: before, de: date equals, du: IS NULL OR =0
	"structure" => array("si","su","se","sn"), // ,"ib","ia","ba" // si: similar (Fingerprints), su: substruct (Fingerprints und Molfile), se: entspricht (cSMILES), sn: structure no stereo
	"structure_ex" => array("se"), // für externe Suche
	"emp_formula" => array("sf","ef"), // sf: enthält (Fingerprint1, Summenformeln), bn: entspricht (Hill, CHNAB..)
	"reaction" => array("sr"), // enthält Reaktion, zZt nur pos. UND Verknüpfung, arbeitet mit cSMILES-Unterstützung
);

$global_transparent_params=array("style","table","order_by","cached_query","no_cache","db_name","user",
//~ "sess_proof",
"debug","lang_debug",
"tableSelect", // tabellen, in denen ausgewählt wird
"list_int_name","beforeUID","UID","field","group", // Feld in subitemlist
"editDbId","editPk" // welcher Datensatz wird bearbeitet
// "selectForTable","selectForDbId","selectForPk" // Tabelle und Primärschlüssel, für die gesucht wird, db_id dürfte immer -1 sein (aber wer weiß)
); // diese Parameter werden bei getSelfRef() immer standardmäßig übertragen (sofern nicht in $suppress)
$fp_only_smiles=array("c1ccccc1","c1ccncc1", "c1ccnnc1","c1cncnc1", 

"c1cnccn1", "n1ccncc1",

"c1cnnnc1", 

"c1cnncn1", "n1ccnnc1", 

"c1ncncn1", "n1cncnc1", 

"c1ccsc1", "s1cccc1", 

"c1ccoc1", "o1cccc1", 

"c1cc[nH]c1", "n1cccc1", 

"c1c[nH]cn1", "n1cncc1", 

"c1cocn1", "o1cncc1", 

"c1cscn1", "s1cncc1", 

"c1cn[nH]c1", "n1cccn1", 

"c1cnoc1", "o1cccn1", 

"c1c[nH]nn1", "c1cnnn1", 

"c1nc[nH]n1", "n1ncnc1", 

"C1CCCCC1", "C1CCCC1", "C1CCC1", "C1CC1", 

"C1COCCN1", "N1CCOCC1", 

"C1CO1", "O1CC1", 

"C1=CCC=C1", "C1C=CC=C1","C1/C=C\\C=C/1",

//~ "CCCCC","CCCCCCCCCC","CCCCCCCCCCCCCCC", // unfortunately not, think of Perylene

"O","C","N","F","Cl","Br","I","B","Si","S","P"); // Smiles für die Substruktursuche nix zusätzlich bringt (zB einzelner Benzolring)
// die jeweils 2. sind unsere, bei Abweichungen v. Ertl

$SQLtypes=array(
	"foreign_key" => array("REFERENCES"), // outdated
	"set" => array("SET"),
	"enum" => array("ENUM"), 
	"text" => array("TEXT","CHAR"),
	"num" => array("FLOAT","INT","DOUBLE","DEC","FIXED","NUMERIC"),
	"date" => array("DATE","TIME")
);

$priority_colors=array(1 => "green", 2 => "orange", 3 => "red");
$hazardSymbols=array(
	"C","E","F","F+","N","O","T","T+","Xi","Xn", 
	"GHS01","GHS02","GHS03","GHS04","GHS05","GHS06","GHS07","GHS08","GHS09",
);

$arrSymURL=array(
	"E" => "exposive.gif", 
	"O" => "oxidizing.gif", 
	"C" => "corrosive.gif", 
	"F" => "flamm.gif", 
	"T" => "toxic.gif", 
	"X" => "harmful.gif", 
	"N" => "danger.gif", 
	1 => "GHS-pictogram-explos.png", 
	2 => "GHS-pictogram-flamme.png", 
	3 => "GHS-pictogram-rondflam.png", 
	4 => "GHS-pictogram-bottle.png", 
	5 => "GHS-pictogram-acid.png", 
	6 => "GHS-pictogram-skull.png", 
	7 => "GHS-pictogram-exclam.png", 
	8 => "GHS-pictogram-silhouete.png", 
	9 => "GHS-pictogram-pollu.png", 
);
$permissions_list=array("admin","write","read_other","remote_read");

$price_currency_list=array("EUR","CHF","USD");
define("_vendor",1);
define("_buyer",2);
define("_research",4);
define("_commercial",8);

$bin_data=array("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");

define("default_mass_unit","mg");
define("default_amount_unit","mmol");
define("default_volume_unit","ml");

?>
