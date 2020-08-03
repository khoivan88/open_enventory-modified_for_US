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
require_once "lib_brute_block.php";
require_once "lib_formatting.php";

$page_type = "async";

// include in sidenav.php via iframe, use Javascript date to execute this only every 5th?
header("Connection: close");
ob_start();

setGlobalVars();

pageHeader(true, true, false);

echo "</head>
<body></body>
</html>";

$size = ob_get_length();
header("Content-Length: $size");
ob_end_flush();
flush();

// the database connection should still be open

$mutex = fopen(fixPath(oe_get_temp_dir()) . "/sciflection", "a"); // create file if required, we will not change it
if ($mutex && flock($mutex, LOCK_EX | LOCK_NB, $eWouldBlock) && !$eWouldBlock) {
	require_once "lib_upload_sciflection.php";
	set_time_limit(0); // never die
	// anything to upload?
	list($publicationToUpload) = mysql_select_array(array(
		"table" => "data_publication",
		"dbs" => "-1",
		"filter" => "data_publication.publication_status='confirmed'",
		"limit" => 1,
		"flags" => QUERY_EDIT,
	));
	if ($publicationToUpload) {
		// serialize and upload in blocks of 20
		define("DEFAULT_BLOCK_SIZE", 20);

		// get login data
		list($sciflectionCredentials) = mysql_select_array(array(
			"table" => "other_db",
			"dbs" => "-1",
			"filter" => "other_db_id=" . $publicationToUpload["publication_db_id"],
			"limit" => 1,
			"flags" => QUERY_EDIT,
		));

		if (uploadAssignmentBlock($sciflectionCredentials, $publicationToUpload, "publication_reaction", "publication_reaction_id") && uploadAssignmentBlock($sciflectionCredentials, $publicationToUpload, "publication_analytical_data", "publication_analytical_data_id")) {
			// update publication_status
			mysqli_query($db, "UPDATE data_publication SET publication_status='published'" .
					" WHERE data_publication_id=" . $publicationToUpload["data_publication_id"] . ";");
		}
	}
	flock($mutex, LOCK_UN);
//			time_sleep_until();
}
fclose($mutex);

// close database connection
completeDoc();
?>