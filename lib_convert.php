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
require_once "lib_analytics_common.php";
require_once "lib_formatting.php";
$require_path=array("generic");
require_once installPath."analytics/converter.php";
require_once installPath."analytics/generic/generic.php";
unset($require_path); // reset $require_path !!

function make_tempfile($data,$format=null) {
	$tmpdir=oe_get_temp_dir();
	if (is_null($format)) {
		$src_name=oe_tempnam($tmpdir,"oe");
	}
	else {
		$tempname=oe_tempnam($tmpdir,$format);
		$src_name=$tempname.".".$format;
		rename($tempname,$src_name);
	}
	@chmod($src_name,0755); // needed for OO
	
	if (strlen($data)) {
		$handle=fopen($src_name,"w");
		fwrite($handle,$data);
		fclose($handle);
	}
	
	return $src_name;
}

function data_convert($data,$format_in,$format_out=array("png")) { // gives back PNG-data and text for searching
	$format_count=count($format_out);
	if ($format_count==0) {
		return;
	}
	
	$format_in=strtolower($format_in);
	$dotext=".".$format_in;
	$tmpdir=oe_get_temp_dir();
	
	// do using tempfiles as some programs have problems with pipes or need the filename to determine the file type
	// create tempfile
	$src_name=make_tempfile($data,$format_in);
	/*
	$tempname=oe_tempnam($tmpdir,$format_in);
	$src_name=$tempname.".".$format_in;
	rename($tempname,$src_name);
	@chmod($src_name,0755); // needed for OO
	
	$handle=fopen($src_name,"w");
	fwrite($handle,$data);
	fclose($handle);
	*/
	
	// identify
	if (is_array($GLOBALS["generic_file_types"])) foreach ($GLOBALS["generic_file_types"] as $type => $extensions) {
		if (in_array($dotext,$extensions)) {
			break;
		}
	}
	
	// get filename for target file
	$trg_name=array();
	foreach($format_out as $idx => $ext_out) {
		switch ($ext_out) {
		case "png":
		case "txt":
			$trg_name[$idx]=make_tempfile("",$ext_out);
		break;
		}
	}
	
	if ($type=="soffice") {
		$pdf_name=oe_tempnam($tmpdir,"PDF");
		unlink($pdf_name); // delete as java does not overwrite
		$pdf_name.=".pdf";
	}
	
	// convert
	switch ($type) {
	case "soffice":
		$java=java_command;
		if (!empty($java)) {
			$files=array("jodconverter-cli-4.4.2.jar","jodconverter-local-4.4.2.jar","jodconverter-remote-4.4.2.jar","jodconverter-core-4.4.2.jar","commons-cli-1.4.jar","commons-io-2.8.0.jar","spring-context-5.3.2.jar","spring-aop-5.3.2.jar","spring-beans-5.3.2.jar","spring-expression-5.3.2.jar","spring-core-5.3.2.jar","slf4j-log4j12-1.7.30.jar","spring-jcl-5.3.2.jar","httpmime-4.5.13.jar","fluent-hc-4.5.13.jar","httpclient-4.5.13.jar","commons-codec-1.15.jar","gson-2.8.6.jar","httpcore-4.4.14.jar","slf4j-api-1.7.30.jar","juh-4.1.2.jar","jurt-4.1.2.jar","unoil-4.1.2.jar","ridl-4.1.2.jar","log4j-1.2.17.jar","commons-logging-1.2.jar");
			for ($a=0;$a<count($files);$a++) {
				$files[$a]="jodconverter-cli-4.4.2/lib/".$files[$a];
			}
			$cmd=escapeshellarg($java)." -classpath ".join(":",$files)." org.jodconverter.cli.Convert ".$src_name." ".$pdf_name;
			shell_exec($cmd);
			@unlink($src_name);
			$src_name=$pdf_name;
		}
	// kein break;
	case "gs":
		$gs=ghostscript_command;
		if (!empty($gs)) {
			foreach($format_out as $idx => $ext_out) {
				switch ($ext_out) {
				case "txt":
					$pdftotext=pdftotext_command;
					if (!empty($pdftotext)) {
						$cmd=escapeshellarg($pdftotext)." ".$src_name." ".$trg_name[$idx];
					}
					else { // fallback
						$cmd=escapeshellarg($gs)." -q -dNODISPLAY -dNOBIND -dWRITESYSTEMDICT -dSIMPLE ps2ascii.ps ".$src_name." -c quit >".$trg_name[$idx];
					}
					shell_exec($cmd);
				break;
				case "png":
					$cmd=escapeshellarg($gs)." -q -dBatch -dNOPAUSE -sDEVICE=png16m -r100 -dMaxBitmap=300000000 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dFirstPage=1 -dLastPage=1 -sOutputFile=".$trg_name[$idx]." ".$src_name." -c quit";
					shell_exec($cmd);
				break;
				}
			}
		}
	break;
	case "magick":
		$convert=imagemagick_command;
		if (!empty($convert)) {
			foreach($format_out as $idx => $ext_out) {
				switch ($ext_out) {
				case "png":
					$cmd=escapeshellarg($convert)." ".$src_name."[0] ".$trg_name[$idx]; // only 1st page, avoid some trash files
					shell_exec($cmd);
					
					if (!file_exists($trg_name[$idx]) || filesize($trg_name[$idx])==0) {
						// check if multipage files are there
						$dotpos=strrpos($trg_name[$idx],".");
						if ($dotpos!==FALSE) {
							$page1_name=substr($trg_name[$idx],0,$dotpos)."-0".substr($trg_name[$idx],$dotpos);
							if (file_exists($page1_name)) {
								$trg_name[$idx]=$page1_name;
							}
						}
					}
				break;
				}
			}
		}
	break;
	case "vectorEMF":
		$java=java_command;
		if (!empty($java)) {
			foreach($format_out as $idx => $ext_out) {
				switch ($ext_out) {
				case "png":
					$cmd=escapeshellarg($java)." -jar vectorgraphics-2.1.1/lib/freehep-graphicsio-emf-2.1.1.jar  ".$src_name." ".$trg_name[$idx];
					shell_exec($cmd);
				break;
				}
			}
		}
	break;
	}
	
	// cleanup
	@unlink($src_name);
	$retval=array();
	foreach($format_out as $idx => $ext_out) {
		switch ($ext_out) {
		case "png":
		case "txt":
			// read file
			$retval[$idx]=file_get_contents($trg_name[$idx]);
			
			unlink($trg_name[$idx]);
		break;
		}
	}
	
	// return
	if ($format_count==1) {
		return $retval[0];
	}
	return $retval;	
}

function isPDF($pdf_data) {
	return (substr($pdf_data,0,4)=="%PDF");
}

?>