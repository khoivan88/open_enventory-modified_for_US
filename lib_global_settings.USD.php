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
/* -----------------------------------------------------------------------------
 * History:
 * 2009-10-01 RUD01 Created
 * 2009-10-21 MST00 Add var to determine start of transaction and commit for MPI
 *            usage
 *----------------------------------------------------------------------------*/
// globale EInstellungen
define("maxRedir",4); // maximale Weiterleitungen, geht nicht mit Cookies
define("uA","Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0"); // als welcher Useragent soll sich der Server ausgeben

$default_http_options=array(
	"useragent" => "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0", 
	//~ "proxyhost" => "http://httproxy.mpi-muelheim.mpg.de:3128",
	"timeout" => 20,
	"connect_timeout" => 20,
);

$belab_options=array(
	"archivistOrganization" => "MPI für Kohlenforschung",
	"durationDays" => 30,
	"hashonly" =>false,
	"serverURL" => "http://scc-belab.scc.kit.edu:8080/BeLabREST/services/rest",
	"hashAlgorithm" => "sha256",
	"username" => "test",
	"password" => "belabtest",
);

define("installPath",__DIR__."/");
define("localAnalyticsPath","/mnt"); // limit analytics download to sub paths of this one
define("limit_access_to_sigle",0); // limit analytics download to sub paths of this sigle
define("gif_x",158); // Mindest-Breite GIF in pix
define("gif_y",138); // Mindest-Höhe GIF in pix
define("rc_gif_x",35); // Mindest-Breite GIF in pix
define("rc_gif_y",35); // Mindest-Höhe GIF in pix
define("colHeadFactor",2); // Mindest-Höhe GIF in pix
define("rxn_gif_x",800); // Mindest-Breite GIF in pix
define("rxn_gif_y",180); // Mindest-Höhe GIF in pix
define("bond_scale",0.75); // Skalierungsfaktor Bindungslängen
define("font_scale",0.75); // Skalierungsfaktor Schriftgröße
define("struc_margin",8); // px
/* define("rxn_small_gif_x",400); // Breite GIF in pix
define("rxn_small_gif_y",200); // Höhe GIF in pix */
define("analytics_gif_x",600); // Breite GIF in pix
define("analytics_gif_y",400); // Höhe GIF in pix
define("defBgColor","#EDF0FF");
define("default_per_page",100); // Standardwert Ergebnisse pro Seite
define("async_per_page",10); // Standardwert Ergebnisse pro Seite - asynchrone
define("molecules_per_async_page",3); // Standardwert Ergebnisse pro Seite - Moleküle asynchron
define("molecule_names_by_lang",false); // Trennung der Molekülnamen nach Sprachen
define("db_server","localhost");
define("php_server","localhost"); // For IP-address use: getenv('SERVER_ADDR'))
define("db_system","MySQL");
define("storage_engine","InnoDB"); // MyISAM or InnoDB
define("archive_storage_engine","InnoDB"); // MyISAM or InnoDB
define("autoTransaction",true);

define("hash_algo","sha1");

define("ghostscript_command","gs"); // for PDF=>PNG,txt conversion
define("pdftotext_command","pdftotext"); // for PDF=>TXT conversion, better than gs
define("imagemagick_command","convert"); // for TIF=>PNG conversion
define("java_command","java"); // for DOC/XLS/...=>PDF conversion
define("url_prefix","http://anonymouse.org/cgi-bin/anon-www_de.cgi/");

define("loginHeals",true); // a correct login by a user/ip resets fail counter
define("capabilities",1); // (1= order_system)
// true: Sprache für Molekülnamen wird gespeichert, Standardname für jede Sprache einzeln, Suchen nur in eigener Sprache, Änderungen wirken nur auf eigene Sprache
// bei Löschen eines Moleküls werden ALLE Namen gelöscht
// false: Sprache für Molekülnamen wird gespeichert, spielt aber keine Rolle; Spracheinstellung wird nur für Oberfläche genutzt
define("default_language","en"); // Standardsprache
define("result_cache_time",36000); // 600 mins (result_sets may be very big)
define("all_cache_time",345600); // 4 days (result_sets may be very big)
define("db_lock_max",86400); // 1 day
define("db_lock_renew",600); // 10 min
define("db_lock_protect",1200); // 20 min

/*define("result_cache_limit",6); // max 10 // deprecated
define("query_cache_time",86400); // 1 day (only for long-time stability) // deprecated */
//~ $views=array("molecule" => array(
	//~ "view_standard" => "", 
	//~ "view_physical" => "structure,molecule_name,emp_formula_short,mw,density_20,mp_short,bp_short,n_20,links_mol", 
	//~ "view_safety" => "structure,molecule_name,safety_sym,safety_text,safety_data_sheet,safety_r_s,safety_class,safety_danger,safety_other,bp_short,links_mol"
	//~ ), 
	
//~ "chemical_storage" => array(
	//~ "view_standard" => "", 
	//~ "view_inventory" => "structure,molecule_name,safety_sym_short,cas_nr,migrate_id_cheminstor,amount,inventarisation,chemical_storage_barcode,storage,expiry_date,links_chem", 
	//~ "view_safety" => "structure,molecule_name,safety_sym,safety_text,safety_data_sheet,safety_r_s,safety_class,safety_danger,safety_other,bp_short,links_chem",
	//~ "view_physical" => "structure,molecule_name,emp_formula_short,mw,density_20,mp_short,bp_short,n_20,amount,storage,links_chem", 
//~ )); // Definition von vordefinierten Ansichten
$allowed_per_page=array(10,25,50,100,-1);
$defaultCurrency="USD";

$clientCache=array(
	"detail_cache_range" => 20, 
	"fast_cache_range" => 50, 
	"min_reload" => 10, 
	"max_reload" => 45, 
	"force_distance" => 2, 
	"fastmodeWait" => 200, 
	"fastmodeInt" => 150, 
	"initLoadDelay" => 500, 
	"maxDatasets" => 1000, 
);

define("maxStructureTime",1000); // 1000 ms, accept false positive if above
define("compressFormat","tgz"); // zip makes probs on 64 bit systems
define("messageCheckInterval",6e6); // 10 min
define("messageReadTime",5000); // 5s

define("allowSvg",false);

define("stdSMILES","CCCCCCCCCCCCCC"); // Tetradecane
define("stdmg",40); // 40 mg

//~ define("maxLJnotPrinted",250);
define("maxLJnotPrinted",0);
define("maxLJwarningExcess",20);
define("maxLJblockExcess",40);

define("ausgabe_name","ausgabe");

$analytical_data_lines=2;
$analytical_data_cols=2;
$analytical_data_priority=array( // std-analytik in dieser reihenfolge zum drucken nehmen, die 4 Spektren höchster Prio werden gedruckt. Freie Plätze werden durch Nicht-Std-Analytik aufgefüllt
	array("type_name" => "nmr", "method_name" => "1h",),
	array("type_name" => "nmr", "method_name" => "13c",),
	array("type_name" => "gc-ms",),
	array("type_name" => "chn",),
	array("type_name" => "ir",),
	array("type_name" => "gc",),
	array("type_name" => "nmr", "method_name" => "31p",),
	array("type_name" => "nmr", "method_name" => "10b",),
	array("type_name" => "nmr", "method_name" => "19f",),
);

define("analytics_transfer_profiles",3);
define("langStat",0); // MUST be set to 0

define("order_system_round_mode",0);
define("order_system_round_digits",3);

define("money_round_mode",3);
define("money_round_digits",2);

//>>>MST00
$MPI_TRANSACTION_AND_COMMIT = true; //for use in performQueries
//<<<MST00
?>
