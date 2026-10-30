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
Khoi: command-line version of root -> Batch operations -> "Recalculate structures in databases"
(root_db_man.php?desired_action=fix_structures), using the same code (lib_root_funcs.php).
Runs without the web server, so Apache/PHP-FPM timeouts (504 Gateway Timeout) do not apply.
Only needs to read the OE files and to reach MySQL/MariaDB as root; any OS user will do.
*/
if (PHP_SAPI!=="cli") { http_response_code(404); exit; } // command-line only; this folder is reachable over the web

$cli_usage="Recalculate SMILES and fingerprints of molecules and reaction components (open enventory).

Usage: php fix_structures_cli.php --db=NAME[,NAME...] | --all [options]

  --db=a,b          databases to process (comma list)
  --all             all open enventory databases on the server
  --tables=LIST     default molecule,reactants,reagents,products
  --fields=LIST     default smiles,fingerprint; also: emp_formula,mw,rdb,image,molfile
  --recalc-rxnfile  also redraw the reaction equations (images)
  --defaults-file=F MySQL option file with password= in [client] (e.g. a root-only ~/.my.cnf)
  -h, --help        this text

The MySQL root password is read from the environment variable OE_DB_ROOT_PASSWORD,
else from --defaults-file, else asked for (only when run in a terminal).
Exit code: 0 done, 1 errors while recalculating, 2 wrong arguments or no database access.
";

function cliFail($message,$code=2) {
	fwrite(STDERR,$message."\n");
	exit($code);
}

function getCliList($value,$allowed,$name) {
	$list=array_values(array_filter(array_map("trim",explode(",",$value))));
	$unknown=array_diff($list,$allowed);
	if (count($unknown)) {
		cliFail("Unknown ".$name.": ".join(",",$unknown)." (allowed: ".join(",",$allowed).")");
	}
	return $list;
}

function getCliPassword($defaults_file) {
	$password=getenv("OE_DB_ROOT_PASSWORD");
	if ($password!==false && $password!=="") {
		return $password;
	}
	if (!empty($defaults_file)) {
		$ini=@parse_ini_file($defaults_file,true,INI_SCANNER_RAW);
		if (!is_array($ini)) {
			cliFail("Cannot read ".$defaults_file);
		}
		foreach (array("client","mysql") as $section) {
			if (isset($ini[$section]["password"])) {
				return trim($ini[$section]["password"],"\"'");
			}
		}
		cliFail("No password= in [client] of ".$defaults_file);
	}
	if (function_exists("stream_isatty") && stream_isatty(STDIN)) {
		fwrite(STDERR,"MySQL root password: ");
		@shell_exec("stty -echo");
		$password=rtrim(fgets(STDIN),"\r\n");
		@shell_exec("stty echo");
		fwrite(STDERR,"\n");
		return $password;
	}
	cliFail("No password: set OE_DB_ROOT_PASSWORD, use --defaults-file or run in a terminal.");
}

$cli_opts=getopt("h",array("db:","all","tables:","fields:","recalc-rxnfile","defaults-file:","help"));
if (isset($cli_opts["h"]) || isset($cli_opts["help"]) || (!isset($cli_opts["db"]) && !isset($cli_opts["all"]))) {
	fwrite(isset($cli_opts["h"]) || isset($cli_opts["help"])?STDOUT:STDERR,$cli_usage);
	exit(isset($cli_opts["h"]) || isset($cli_opts["help"])?0:2);
}

$cli_tables=getCliList($cli_opts["tables"]??"molecule,reactants,reagents,products",array("molecule","reactants","reagents","products"),"table");
$cli_fields=getCliList($cli_opts["fields"]??"smiles,fingerprint",array("smiles","fingerprint","emp_formula","mw","rdb","image","molfile"),"field");

// same names as the form fields in root_db_man.php
$cli_options=array("recalcRxnfile" => isset($cli_opts["recalc-rxnfile"]), );
foreach ($cli_fields as $cli_field) {
	$cli_options[ ($cli_field=="image"?"molfile_blob":$cli_field) ]=true;
}
$cli_list_int_names=array_values(array_diff($cli_tables,array("molecule")));

$cli_password=getCliPassword($cli_opts["defaults-file"]??null);

chdir(__DIR__);
set_include_path(__DIR__.PATH_SEPARATOR.get_include_path());
require_once "lib_global_funcs.php";
require_once "lib_root_funcs.php";

// check the password first, loginToDB() would answer with a login page
$db_user=ROOT;
$db_pw=$cli_password;
try {
	$db=@mysqli_connect(db_server,$db_user,$db_pw);
} catch (Exception $e) {
	$db=false;
}
if (!$db) {
	cliFail("Cannot connect to ".db_server." as ".ROOT.": ".mysqli_connect_error());
}

$db_info=getDatabases($db,db_type);
$cli_available=array();
foreach ($db_info as $cli_info) {
	$cli_available[]=$cli_info["name"];
}
if (isset($cli_opts["all"])) {
	$cli_db_names=$cli_available;
}
else {
	$cli_db_names=array_values(array_filter(array_map("trim",explode(",",$cli_opts["db"]))));
	$cli_unknown=array_diff($cli_db_names,$cli_available);
	if (count($cli_unknown)) {
		cliFail("Not an open enventory database: ".join(",",$cli_unknown)." (found: ".join(",",$cli_available).")");
	}
}
if (!count($cli_db_names)) {
	cliFail("No open enventory database found.");
}
mysqli_close($db);

// regular login as root, like the batch page
$db_name=$cli_db_names[0];
if (!loginToDB(false)) {
	cliFail("Login as ".ROOT." to ".$db_name." failed.");
}

$cli_progress=function ($table,$done,$total) {
	echo "  ".$table.": ".$done."/".$total."\n";
};

echo "Databases: ".join(",",$cli_db_names)."\nTables: ".join(",",$cli_tables).($cli_options["recalcRxnfile"]?",reaction images":"")."\nFields: ".join(",",$cli_fields)."\n";
$cli_failed=false;
$cli_start=microtime(true);
foreach ($cli_db_names as $cli_db_name) {
	if (!switchDB($cli_db_name,$db)) {
		fwrite(STDERR,"Cannot open ".$cli_db_name."\n");
		$cli_failed=true;
		continue;
	}
	echo $cli_db_name.":\n";

	if (in_array("molecule",$cli_tables)) {
		$cli_time=microtime(true);
		$cli_stats=fixMoleculeStructures($cli_options,array(),$cli_progress);
		echo "  molecule: ".$cli_stats["updated"]." of ".$cli_stats["total"]." updated, ".$cli_stats["errors"]." errors, ".round(microtime(true)-$cli_time,1)." s\n";
		if ($cli_stats["errors"] || !empty($cli_stats["error"])) {
			if (!empty($cli_stats["error"])) {
				fwrite(STDERR,$cli_stats["error"]."\n");
			}
			$cli_failed=true;
		}
	}

	if ($cli_options["recalcRxnfile"] || count($cli_list_int_names)) {
		$cli_time=microtime(true);
		$cli_stats=fixReactionStructures($cli_options,$cli_list_int_names,$cli_progress);
		echo "  reaction: ".$cli_stats["done"]." of ".$cli_stats["total"]." reactions, ".$cli_stats["updated"]." components updated, ".$cli_stats["errors"]." errors, ".round(microtime(true)-$cli_time,1)." s\n";
		if ($cli_stats["errors"] || !empty($cli_stats["error"])) {
			if (!empty($cli_stats["error"])) {
				fwrite(STDERR,$cli_stats["error"]."\n");
			}
			$cli_failed=true;
		}
	}
}
echo ($cli_failed?"Finished with errors":"Done")." in ".round(microtime(true)-$cli_start,1)." s\n";
exit($cli_failed?1:0);
?>
