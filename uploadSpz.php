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
require_once "lib_analytics.php";
require_once "File/Archive.php";

// SPZ= SPectrum Zipped

// parameter: file/post spzfile, daraus wird die sessionId extrahiert usw

// go through zip and search for .openenv
$spzfile_name="spzfile";

if (count($_FILES[$spzfile_name]) && $_FILES[$spzfile_name]["error"]==0) {
	$filename=& $_FILES[$spzfile_name]["tmp_name"];
	$filesize=& $_FILES[$spzfile_name]["size"];
	// datei öffnen
	$handle=fopen($filename, "rb");
	// größe prüfen
	if ($filesize>0 && filesize($filename)==$filesize) {
		// datei einlesen
		$file_contents=fread($handle,$filesize);
		
		// check if it is a zip, zip otherwise
		if (isZip($file_contents)) {
			$zip=getZipObj($file_contents);
		}
	}
	// datei schließen
	fclose($handle);
	// datei löschen
	@unlink($filename);
}
elseif (!empty($_REQUEST[$spzfile_name])) {
	$zip=getZipObj($_REQUEST[$spzfile_name]);
}

if (isset($zip)) {
	// get data
	while ($zip->next()=== true) {
		$filename=fixZipFilename($zip->getFilename());
		//~ echo $filename."<br>";
		// getBrukerData($acqus,$procs,$real_part_bin,$imag_part_bin="",$peak_txt="",$integrals_txt="")
		if (endswith($filename,".openenv")) {
			$openenv=$zip->getData();
			cutRange($openenv,"[Object data]\r\n","",false);
			$data=unserialize($openenv);
			break; // nothing more to do
		}
	}
}

if ($_REQUEST["mode"]=="plain") {
	$page_type="plain";
}

pageHeader(true,false);

if ($page_type=="plain" && (!$db || empty($data["analytical_data_id"]) ) ) { // do not output extensive error msgs
	die("fail");
}

// check if LJ and reaction are still open
list($analytical_data)=mysql_select_array(array(
	"table" => "analytical_data_check", 
	"dbs" => -1, 
	"filter" => "analytical_data.analytical_data_id=".fixNull($data["analytical_data_id"]), 
	"limit" => 1, 
));

if ($analytical_data["lab_journal_status"]>1 || $analytical_data["status"]>=5) {
	$fail=true;
}


$now=time();
$table="analytical_data";

if (!$fail && isset($zip)) {
	// generate GIF
	$spectrum_data=getProcData($file_contents,$analytics_img_params,$data["analytics_type_code"],$data["analytics_device_driver"]);
	if (count($spectrum_data)) { // generieren
		$graphics_text=
		",analytical_data_graphics_blob=".fixBlob($spectrum_data["img"][0]).
		",analytical_data_graphics_type=".fixStrSQL($spectrum_data["img_mime"][0]); // update image only if generated
	}

	// insert additional images (if any)
	$sql_query[]="DELETE FROM analytical_data_image WHERE analytical_data_image.analytical_data_id=".fixNull($data["analytical_data_id"]).";";
	$imagesUpdated=true;
	for ($a=1;$a<count($spectrum_data["img"]);$a++) {
		$sql_query[]="INSERT INTO analytical_data_image (analytical_data_id,reaction_id,project_id,image_no,analytical_data_graphics_blob,analytical_data_graphics_type) 
			VALUES (".fixNull($data["analytical_data_id"]).",".fixNull($analytical_data["reaction_id"]).",".fixNull($analytical_data["project_id"]).",".$a.",".fixBlob($spectrum_data["img"][$a]).",".fixStrSQL($spectrum_data["img_mime"][$a]).");";
	}
	
	// in die DB schreiben
	$sql_query[]="UPDATE analytical_data SET ".
	"analytical_data_blob=".fixBlob($file_contents).
	$graphics_text.",".
	SQLgetChangeRecord($table,$now).
	getPkCondition($table,$data["analytical_data_id"]);
	
	$result=performQueries($sql_query,$db);
	
	if ($page_type!="plain") {
		echo script."
self.close();
"._script."
</head>
<body>";
	}
}
else { // showUpload
	if ($page_type!="plain") {
		echo "
</head>
<body>
<form name=\"main\" method=\"post\" enctype=\"multipart/form-data\">
<input type=\"file\" name=\"spzfile\" id=\"spzfile\">
<input type=\"submit\" value=".fixStr(s("upload")).">
</form>
";
	}
}

if ($page_type=="plain") {
	echo ($result===TRUE?"success":"fail");
}
else {
	echo "
</body>
</html>";
}

?>