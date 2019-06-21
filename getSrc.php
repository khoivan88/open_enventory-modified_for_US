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
require_once "lib_formatting.php";
require_once "lib_io.php";

//~ pageHeader(true,false); // no longer force login
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

header("Pragma: public");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);
header("Content-Transfer-Encoding: binary");
header("Content-Disposition: attachment; filename=open_enventory_src_".getGermanDate().".tgz");
getCompressHeader();
$data=getPathListing(array(
	"path" => ".", 
	"flags" => 2, 
	"skipfiles" => array(
		".svn"
	),
	"skippaths" => array(
		"/ACD",
		"/intern",
		"/marvin",
		"/marvin4js",
		"/SymyxJDraw",
		"/VecMol",
		"/JME.jar",
		"/chemwriter.jar",
	), 
));
echo $data["zipdata"];

?>