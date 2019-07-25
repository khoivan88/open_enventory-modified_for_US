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
require_once "lib_io.php";
require_once "lib_convert.php";
require_once "lib_array.php";
require_once "lib_jcamp.php";
require_once "lib_molfile.php"; // required by some modules, easier here
require_once "lib_analytics_common.php";
require_once "File/Archive.php";
require_once_r(installPath."analytics");

/* 
$analytics[$type_code]
Funktionen:
getImgFromData($format,$width,$height,...)
Einheitliche Funktion für die Grafikausgabe einer Analytikart (zB 1D-NMR)

$analytics[$type_code][$device_driver]
Funktionen:
getImg($format,$width,$height,& $data_blob,$paramHash) gibt array("img_data" =>, "mime" => ) zurück, in $paramHash werden gewünschte (!) Größe etc angegeben
Der Treiber macht folgendes:
a) wenn Grafik da ist, wird diese genommen und zurückgegeben
b) wenn Daten da sind, werden diese gelesen, zur Grafik gemacht und zurückgegeben

procData(& $data_blob) gibt Daten in standardisiertem Format zurück, zB für 1D-NMR: array("graph_data" => $graph_data, "imag_data" => $imag_data, "dataHash" => $dataHash), die Ausgabe erfolgt über einheitliches Modul (damit Spektren einheitlich aussehen)

*/

// check endian-ness of system
$GLOBALS["littleEndian"]=(pack("S*", 256)=="\x00\x01");

require_once_r(installPath."analytics");

function getBinhex($data) {
	$retval="";
	for ($a=0;$a<strlen($data);$a++) {
		$retval.=str_pad(dechex(ord($data{$a})),2,"0",STR_PAD_LEFT)." ";
	}
	return $retval;
}

function isDefaultAnalyticsIdentifier($analytical_data_identifier,$analytics_type_code,$analytics_method_name,$lab_journal_code,$nr_in_lab_journal) { // nicht case-sensitive
	$analytical_data_identifier=strtolower($analytical_data_identifier);
	
	$analytics_type_code=strtolower($analytics_type_code);
	$analytics_method_name=strtolower($analytics_method_name);
	$lab_journal_code=strtolower($lab_journal_code);
	
	switch ($analytics_type_code) {
	case "gc":
	// GC: XYZ123.D
		return ($analytical_data_identifier==$lab_journal_code.$nr_in_lab_journal.".d" || $analytical_data_identifier==$lab_journal_code.$nr_in_lab_journal);
	break;
	case "nmr":
	switch ($analytics_method_name) {
		case "1h":
		// H-NMR: xyz123-h
			return ($analytical_data_identifier==$lab_journal_code.$nr_in_lab_journal."-h");
		break;
		case "13c":
		// C-NMR: xyz123-c
			return ($analytical_data_identifier==$lab_journal_code.$nr_in_lab_journal."-c");
		break;
	}
	break;
	case "gc-ms":
	// GC-MS: xyz123.sms
		return ($analytical_data_identifier==$lab_journal_code.$nr_in_lab_journal.".sms");
	break;
	default:
		return ($analytical_data_identifier==$lab_journal_code.$nr_in_lab_journal);
	}
}

function whichZip(& $data) {
	$two_char=substr($data,0,2);
	switch ($two_char) {
	case "PK":
		return "zip";
	break;
	case "\037\213":
		return "tgz";
	break;
	}	
}

function isZip(& $data) {
	$format=whichZip($data);
	if (!empty($format)) {
		return true;
	}
	//~ switch (compressFormat) {
	//~ case "zip":
		//~ return (substr($data,0,2)=="PK");
	//~ break;
	//~ case "tgz":
		//~ return (substr($data,0,2)=="\037\213"); // tgz
	//~ break;
	//~ }
	die("Unknown compression format");
}

function getZipObj(& $zip_blob) { // autodetects format
	//~ return File_Archive::readArchive("zip",File_Archive::readMemory($zip_blob,""));
	$format=whichZip($zip_blob);
	return @File_Archive::readArchive($format,@File_Archive::readMemory($zip_blob,""));
}

function getProcData(& $zipdata,$paramHash=array(),$analytics_type_code="generic",$analytics_device_driver="generic") {
	global $analytics;
	$file_contents=array();
	$file_names=array();
	$required_filenames=array();
	$optional_filenames=array();
	$retval=array();
	
	if (empty($zipdata)) {
		return $retval;
	}
	
	if (!array_key_exists($analytics_type_code,$analytics)) {
		$analytics_type_code="generic";
	}
	
	//~ echo "parent.showMessage(".fixStr(str_replace("\n","<br>",print_r($analytics,true))).");}"._script;die();
	
	if (!array_key_exists($analytics_device_driver,$analytics[$analytics_type_code])) {
		$analytics_device_driver="generic";
	}
	if (is_array($analytics)) foreach ($analytics as $type_code => $type_data) {
		if (is_array($type_data)) foreach ($type_data as $device_driver => $device_data) {
			if (!is_array($device_data)) { // not only drivers
				continue;
			}
			// required
			for ($a=0;$a<count($device_data["requiredFiles"]);$a++) {
				$file_contents[ $device_data["requiredFiles"][$a] ]=array();
				$file_names[ $device_data["requiredFiles"][$a] ]=array();
			}
			$required_filenames=arr_merge($required_filenames,$device_data["requiredFiles"]);
			// optional
			for ($a=0;$a<count($device_data["optionalFiles"]);$a++) {
				$file_contents[ $device_data["optionalFiles"][$a] ]=array();
				$file_names[ $device_data["optionalFiles"][$a] ]=array();
			}
			$optional_filenames=arr_merge($optional_filenames,$device_data["optionalFiles"]);
		}
	}
	
	$required_filenames=array_unique($required_filenames);
	$optional_filenames=array_unique($optional_filenames);
	$required_filenames=array_values($required_filenames);
	$optional_filenames=array_values($optional_filenames);
	
	//~ file_put_contents("/tmp/test.tgz",$zipdata);
	$zip=getZipObj($zipdata);
	$completeFileData=array();
	
	if (!is_object($zip)) {
		return $retval;
	}
	
	// get data
	while ($zip->next()===true) {
		$filename=fixZipFilename($zip->getFilename());
		$filename_in_zip=strtolower($filename);
		
		// add . to filename_in_zip if no ending
		$filename_no_path=cutFilename($filename_in_zip);
		if (strpos($filename_no_path,".")===FALSE) {
			$filename_in_zip.=".";
		}
		
		$filecontent=$zip->getData();
		$completeFileData[]=$filecontent;
		//~ echo $filename_in_zip."<br>";
		for ($a=0;$a<count($required_filenames);$a++) {
			if (endswith($filename_in_zip,$required_filenames[$a])) {
				array_push($file_contents[ $required_filenames[$a] ],$filecontent);
				array_push($file_names[ $required_filenames[$a] ],$filename);
				// find all continue 2;
			}
		}
		for ($a=0;$a<count($optional_filenames);$a++) {
			if (endswith($filename_in_zip,$optional_filenames[$a])) {
				array_push($file_contents[ $optional_filenames[$a] ],$filecontent);
				array_push($file_names[ $optional_filenames[$a] ],$filename);
				// find all continue 2;
			}
		}
	}
		
	/*
	 * the following code was edited/ added by Mathias Weklak
	 */
	// next code segment checks, if the choosen converter is right, if not: it searches for the most suitable converter
	$tempConverter = new $analytics_device_driver($file_contents, false);
	$bestFitCounter=array();
	$bestFitCounter[0] = array(0 => "generic", 1 => "generic", 2 => 0);
	// checking if the file is readable with the choosen converter
	if($tempConverter->verifyFileSignature($file_contents)==0 || $tempConverter->verifyFileSignature($file_contents)==2) {
		// if the file is not readable with the choosen converter or it is not verifyable, search a converter that fits
		for($i=0; $i<count($GLOBALS['analytics']); $i++) {
			$device_codes = array_keys($GLOBALS['analytics'][array_keys($GLOBALS['analytics'])[$i]]);
			for($j=0; $j<count($device_codes); $j++) {
				// ignore if it is not a converter
				if(is_array($GLOBALS['analytics'][array_keys($GLOBALS['analytics'])[$i]][$device_codes[$j]])) {
					$device_drivers = $GLOBALS['analytics'][array_keys($GLOBALS['analytics'])[$i]][$device_codes[$j]];
				}
				else {
					continue;
				}
				// create entry if it does not exist already
				if($bestFitCounter[count($bestFitCounter)-1][1]!=array_keys($GLOBALS['analytics'][array_keys($GLOBALS['analytics'])[$i]])[$j]) {
					$bestFitCounter[count($bestFitCounter)][1]=array_keys($GLOBALS['analytics'][array_keys($GLOBALS['analytics'])[$i]])[$j];
					$bestFitCounter[count($bestFitCounter)-1][0]=array_keys($GLOBALS['analytics'])[$i];
					$bestFitCounter[count($bestFitCounter)-1][2]=0;
				}
				$converterFound=true;
				// go through required files: if the uploaded data does not contains one of the required files -> mark as not fitting, else: fitcounter++
				for($k=0; $k<count($device_drivers['requiredFiles']); $k++) {
					if($file_contents[$device_drivers['requiredFiles'][$k]][0]=="") {
						$converterFound=false;
						$bestFitCounter[count($bestFitCounter)-1][2]=-1;
						continue 2;
					}
					elseif($bestFitCounter[count($bestFitCounter)-1][2]!=-1) {
						$bestFitCounter[count($bestFitCounter)-1][2]++;
					}
				}
				// go through excluded files... if uploaded data contains just one of it, mark it as not fitting
				for($k=0; $k<count($device_drivers['excludeFiles']); $k++) {
					if($file_contents[$device_drivers['excludeFiles'][$k]][0]!="") {
						$converterFound=false;
						$bestFitCounter[count($bestFitCounter)-1][2]=-1;
						continue 2;
					}
				}
				// go through optional files and increment fitcounter, if the uploaded data contains an optional file
				for($k=0; $k<count($device_drivers['optionalFiles']); $k++) {
					if($file_contents[$device_drivers['optionalFiles'][$k]][0]!="" && $bestFitCounter[count($bestFitCounter)-1][2]!=-1) {
						$bestFitCounter[count($bestFitCounter)-1][2]++;
					}
				}
			}
		}
		if($bestFitCounter[0][1]=="generic" && $bestFitCounter[0][2]>0) {
			$tempGeneric = array_shift($bestFitCounter);
		}
		// sorting by "best fit"
		usort($bestFitCounter, function($a, $b) {return $b[2] - $a[2];});
		if($tempGeneric != NULL && $bestFitCounter[0][2]<1) {
			array_unshift($bestFitCounter, $tempGeneric);
		}
		// if the most suitable converter is generic, check if one of the other fitting converters can read the file, if so, take the most suitbable of them, if not, take generic
		if($bestFitCounter[0][2]<1) {
			$analytics_type_code = "unknown";
			$analytics_device_driver = "unknown";
			$converter = new $analytics_device_driver($completeFileData, true);
		}
		elseif($bestFitCounter[0][1]=="generic" || $bestFitCounter[0][1]=="jcampIR" || $bestFitCounter[0][1]=="jcampNMR") {
			for($i=1; $i<count($bestFitCounter); $i++) {
				$tempConverter = new $bestFitCounter[$i][1]($file_contents, false);
				if($bestFitCounter[$i][2]<1) {
					break;
				}
				if($tempConverter->verifyFileSignature($file_contents)==1) {
					$analytics_type_code = $bestFitCounter[$i][0];
					$analytics_device_driver = $bestFitCounter[$i][1];
					$converter=new $analytics_device_driver($file_contents, true);
					break;
				}
			}
			if($converter==NULL) {
				$analytics_type_code = $bestFitCounter[0][0];
				$analytics_device_driver = $bestFitCounter[0][1];
				$converter = new $analytics_device_driver($file_contents, true);
			}
		}
		else {
			for($i=0; $i<count($bestFitCounter); $i++) {
				$tempConverter = new $bestFitCounter[$i][1]($file_contents, false);
				if($bestFitCounter[$i][2]<1) {
					break;
				}
				if($tempConverter->verifyFileSignature($file_contents)==1 || $tempConverter->verifyFileSignature($file_contents)==2) {
					$analytics_type_code = $bestFitCounter[$i][0];
					$analytics_device_driver = $bestFitCounter[$i][1];
					$converter=new $analytics_device_driver($file_contents, true);
					break;
				}
			}
			if($converter==NULL) {
				$analytics_type_code = "unknown";
				$analytics_device_driver = "unknown";
				$converter = new $analytics_device_driver($completeFileData, true);
			}
		}
	}
	else {
		$converter = new $analytics_device_driver($file_contents, true);
	}
	// gets the graphData and the configuration to paint the graph and the required information
	$graphData = $converter->getGraphData();
	$config = $converter->getConfig();
	// "paints the graph" or save the generated image
	if($analytics_device_driver=='generic' || $analytics_device_driver=='agilent' || $analytics_device_driver=='unknown') {
		$this_retval['img'][0] = $graphData['image'];
	}
	else {
		$img = new graph($graphData, $config);
		$this_retval['img'][0] = $img->getBinaryData();
	}
	
	// next lines merges it all together
	$this_retval['img_mime'][0] = $graphData['imageMime'];
	for($i=0; $i<count($graphData['ms']); $i++) {
		$config['axisOffset']['y']=50;
		$msImage = new graph($graphData['ms'][$i], $config);
		$this_retval['img'][$i+1]=$msImage->getBinaryData(); 
		$this_retval['analytical_data_csv'][$i+1] = $graphData['csvDataString'][$i+1];
		$this_retval['img_mime'][$i+1]=$graphData['imageMime'];
	}
	$this_retval['analytics_method_name'] = $graphData['method'];
	$this_retval['analytical_data_csv'][0] = $graphData['csvDataString'][0];
	$this_retval['interpretation'] = $graphData['interpretation'];
	$this_retval['analytical_data_properties'] = $graphData['analytical_data_properties'];
	$this_retval['analytics_type_code'] = $analytics_type_code;
	$this_retval['analytics_device_driver'] = $analytics_device_driver;
	$this_retval['analytics_device_name'] = $analytics_type_code." ".$analytics_device_driver;
	$this_retval['analytics_type_name'] = strtoupper($analytics_type_code);
	
	/*
	 * end of added code
	 */
		
	if (is_array($this_retval)) {
		$retval=array_merge($this_retval,$retval);
	}
	
	return $retval;
}

function getFileIdentifier($type_code,$device_driver,$index,$opt) {
	global $analytics;
	if ($opt) {
		$hash="optionalFiles";
	}
	else {
		$hash="requiredFiles";
	}
	return $analytics[$type_code][$device_driver][$hash][$index];
}

function findFileName($type_code,$device_driver,$index,$opt,$file_names,$filename) {
	global $analytics;
	if ($opt) {
		$hash="optionalFiles";
	}
	else {
		$hash="requiredFiles";
	}
	$this_filenames=& $file_names[ $analytics[$type_code][$device_driver][$hash][$index] ];
	for ($a=0;$a<count($this_filenames);$a++) {
		$this_filename=$this_filenames[$a];
		if (endswith($this_filename,$filename)) {
			return $a;
		}
	}
	//~ return array_search($filename,$file_names[ $analytics[$type_code][$device_driver][$hash][$index] ]);
}

function getPeakList($values,$paramHash=array()) {
	$peak_data=array();
	
	if (!count($values)) {
		return $peak_data;
	}
	
	if ($paramHash["top_down"]) {
		foreach ($values as $xval => $yval) {
			$values[$xval]=-$yval;
		}
	}
	
	$x_values=array_keys($values);
	$minX=min($x_values);
	$maxX=max($x_values);
	$minY=min($values);
	$maxY=max($values);
	$delta_y=$maxY-$minY;
	$delta_x=$maxX-$minX;
	
	if ($delta_y<=0 || $delta_x<=0) {
		return $peak_data;
	}
	
	// fine-tuning
	$aver_exc=ifempty($paramHash["aver_exc"],1.3); // Faktor für Peak-Detektion über gleitenden Durchschnitt
	$aver_range=ifempty($paramHash["aver_range"],500); // Faktor für gleitenden Durchschnitt
	$min_rel_peak=ifempty($paramHash["min_rel_peak"],0.07); // min rel H für Peak
	$max_peaks=ifempty($paramHash["max_peaks"],100);
	$min_peak_rad=ifempty($paramHash["min_peak_rad"],3); // Mindestzahl Punkte zwischen Peaks
	
	if ($aver_exc>1) {
		$range_sums=array();
		$range_count=array();
		foreach ($values as $xval => $yval) {
			$idx=round($aver_range*($xval-$minX)/$delta_x);
			$norm_val=($yval-$minY)/$delta_y;
			$range_sums[$idx]+=$norm_val;
			$range_count[$idx]++;
		}
		
		for ($idx=0;$idx<=$aver_range;$idx++) {
			if ($range_count[$idx]>0) {
				$range_sums[$idx]/=$range_count[$idx];
			}
		}
		
		$block_no=0;
		foreach ($values as $xval => $yval) {
			$idx=round($aver_range*($xval-$minX)/$delta_x);
			$norm_val=($yval-$minY)/$delta_y;
			if (
				$norm_val>$min_rel_peak // abs height
				&&
				$norm_val>$range_sums[$idx]*$aver_exc // über gleitendem Durchschnitt
				&&
				$yval==max(array_slice($values,$block_no-$min_peak_rad,2*$min_peak_rad))
			) {
				$peak_data[$xval]=$yval;
			}
			$block_no++;
		}
	}
	else {
		$peak_data=$values;
	}
	
	if (count($peak_data)>$max_peaks) { // keep the x highest
		arsort($peak_data); // x => y
		$peak_data=array_slice($peak_data,0,$max_peaks,true);
	}
	$peak_data=array_keys($peak_data);
	
	switch ($paramHash["peak_sort"]) {
	case "reverse":
		rsort($peak_data);
	break;
	default:
		sort($peak_data);
	}
	
	return $peak_data; // only list of x-values for peaks
}

function correctBaseline($values,$paramHash=array()) { // only simple linear for now, assuming start and end is baseline
	if (!is_array($values)) {
		return $values;
	}
	
	$new_values=array();
	foreach($values as $idx => $this_values) {
		$y0=reset($this_values);
		$y1=end($this_values);
		
		if ($y0===FALSE || $y1===FALSE || $y0==$y1) {
			$new_values[$idx]=$this_values;
			continue;
		}
		
		$x_values=array_keys($this_values);
		$delta_y=$y1-$y0;
		$delta_x=end($x_values)-reset($x_values);
		if ($delta_x==0) {
			$new_values[$idx]=$this_values;
			continue;
		}
		
		$slope=$delta_y/$delta_x;
		
		foreach ($this_values as $xval => $yval) {
			$new_values[$idx][$xval]=$yval-$xval*$slope;
		}
	}
	return $new_values;
}

function getXrange($values) {
	$minX=array();
	$maxX=array();
	if (is_array($values)) foreach($values as $idx => $this_values) {
		$x_values=array_keys($this_values);
		$minX[]=min($x_values);
		$maxX[]=max($x_values);
	}
	
	if (count($minX) && count($maxX)) {
		return array(min($minX),max($maxX));
	}
}

function getYrange($values) {
	$minY=array();
	$maxY=array();
	if (is_array($values)) foreach ($values as $trace_values) {
		$minY[]=min($trace_values);
		$maxY[]=max($trace_values);
	}
	
	if (count($minY) && count($maxY)) {
		return array(min($minY),max($maxY));
	}
}

function getTickScale($delta,$no_ticks) {
	$tick_scale=pow(10,floor(log(2.5*$delta/$no_ticks,10))); // increment between ticks, either 10.. or 20.. or 50..
	for ($a=0;$a<2;$a++) {
		if ($delta>$tick_scale*$no_ticks) {
			$tick_scale*=2;
		}
	}
	return $tick_scale;
}

function prepareChromaGraph($values,& $paramHash) {
	list($paramHash["min_key"],$paramHash["max_key"])=getXrange($values);
	list($paramHash["min_y"],$paramHash["max_y"])=getYrange($values);
}

function getChromaGraphImg($values,$paramHash=array(),$peak_block_list=array()) {
	global $ttffontsize,$ttffontname;
		
	if (!count($values)) {
		return getEmptyImage($paramHash["format"]);
	}
	
	// process graph
	//~ list($minY,$maxY)=getYrange($values);
	$delta_y=$paramHash["max_y"]-$paramHash["min_y"];
	
	if ($delta_y<=0) {
		return getEmptyImage($paramHash["format"]);
	}
	
	// prepare image
	$width=& $paramHash["width"];
	$height=& $paramHash["height"];
	
	if (!isset($paramHash["min_x"]) && !isset($paramHash["max_x"])) { // autoscale
		$paramHash["min_x"]=$paramHash["min_key"];
		$paramHash["max_x"]=$paramHash["max_key"];
	}
	$delta_x=$paramHash["max_x"]-$paramHash["min_x"];
	
	if ($delta_x<=0) {
		return getEmptyImage($paramHash["format"]);
	}
	
	// validate parameters
	if ($width<100) {
		$width=800;
	}
	if ($height<100) {
		$height=600;
	}
	
	$im=imagecreate($width,$height);
	$white=imagecolorallocate($im,255,255,255); // bg
	$black=imagecolorallocate($im,0,0,0); // axis & text
	$green=imagecolorallocate($im,0,255,0); // peaks
	$color=array(
		imagecolorallocate($im,0,0,255),
		imagecolorallocate($im,0,255,0),
		imagecolorallocate($im,255,0,0),
		imagecolorallocate($im,0,255,255),
		imagecolorallocate($im,255,0,255),
		imagecolorallocate($im,255,255,0),
	);
	
	// draw scale
	$no_big_ticks=15; // approximately
	$smalltick_size=2;
	$bigtick_size=6;
	$ms_threshold=0.4;
	
	$x0=10;
	$x1=$width-$x0;
	$shift_x=1;
	if ($paramHash["right_to_left"]) {
		$x0=$x1;
		$shift_x=-1;
	}
	
	$y1=25;
	$y0=$height-$y1; // always at the bottom
	imageline($im,0,$y0,$width,$y0,$black); // x waagerecht
	
	// Skalen auf x
	$tick_scale=getTickScale($delta_x,$no_big_ticks);
	for ($a=0;$a<=$delta_x;$a+=$tick_scale) {
		$xpos=$x0+$width*$shift_x*$a/$delta_x;
		imagettftext($im,$ttffontsize,0,$xpos-12+6*$shift_x,$y0+17,$black,$ttffontname,roundSign($paramHash["min_x"]+$a,3)); // text
		imageline($im,$xpos,$y0,$xpos,$y0+$bigtick_size,$black); // big
		$xpos=$x0+$width*$shift_x*($a-0.5*$tick_scale)/$delta_x;
		imageline($im,$xpos,$y0,$xpos,$y0+$smalltick_size,$black); // small
	}
	imagettftext($im,$ttffontsize,0,$x0+$width*$shift_x/2,$y0+22,$black,$ttffontname,$paramHash["unit"]);
	
	if (!empty($paramHash["text"])) {
		imagettftext($im,$ttffontsize,0,5,25,$black,$ttffontname,$paramHash["text"]);
	}
	
	// Skalen auf y
	if (isset($paramHash["unit_y"])) {
		imageline($im,$x0,0,$x0,$height,$black); // y senkrecht
		$tick_scale=getTickScale($delta_y,$no_big_ticks);
		for ($a=0;$a<=$delta_x;$a+=$tick_scale) {
			$ypos=$y0*($paramHash["max_y"]-$a)/$delta_y;
			imagettftext($im,$ttffontsize,0,$x0-20+10*$shift_x,$ypos+8,$black,$ttffontname,$a); // text
			imageline($im,$x0,$ypos,$x0+$bigtick_size,$ypos,$black); // big
			$ypos=$y0*($paramHash["max_y"]-$a-0.5*$tick_scale)/$delta_y;
			imageline($im,$x0,$ypos,$x0+$smalltick_size,$ypos,$black); // small
		}
		imagettftext($im,$ttffontsize,0,$x0+12*$shift_x,$height/2,$black,$ttffontname,$paramHash["unit_y"]);
	}
	
	// draw graph
	$fac=1;
	if ($paramHash["max_y"]>$paramHash["min_y"]) {
		$fac=1/$delta_y;
	}
	$colornum=0;
	$legend_x=0;
	$legend_y=10;
	
	switch ($paramHash["style"]) {
	case "ms":
		foreach($values as $idx => $this_values) {
			$x_fac=($shift_x*$x1)/$delta_x;
			foreach ($this_values as $xval => $yval) {
				$xpos=$x0+($xval-$paramHash["min_x"])*$x_fac;
				$ypos=$y0*(($paramHash["min_y"]-$yval)*$fac+1);
				imageline($im,$xpos+$colornum,$ypos,$xpos+$colornum,$y0,$color[$colornum]); // have 1px distance between multiple +$colornum
				if ($yval*$fac>$ms_threshold) { // label
					imagettftext($im,$ttffontsize,0,$xpos,$ypos+10,$black,$ttffontname,$xval); // text
				}
			}
			$colornum++;
		}
	break;
	case "xy":
		foreach($values as $xy_pairs) {
			unset($prevxpos);
			unset($prevypos);
			$x_fac=($shift_x*$x1)/$delta_x;
			foreach ($xy_pairs as $xy_pair) {
				$xpos=$x0+($xy_pair["x"]-$paramHash["min_x"])*$x_fac;
				$ypos=$y0*(($paramHash["min_y"]-$xy_pair["y"])*$fac+1);
				if (isset($prevxpos)) { // && ($idx+$xpos)%2
					imageline($im,$xpos,$ypos,$prevxpos,$prevypos,$color[$colornum]);
				}
				
				$prevxpos=$xpos;
				$prevypos=$ypos;
			}
			
			// draw legend
			if (isset($paramHash["trace_names"][$colornum])) {
				$fontbox=imagettftext($im,$ttffontsize,0,$legend_x,$legend_y,$color[$colornum],$ttffontname,$paramHash["trace_names"][$colornum]);
				$legend_y+=abs($fontbox[1]-$fontbox[7]);
			}
			$colornum++;
		}
	break;
	case "chroma":
	default:
		foreach($values as $idx => $this_values) {
			unset($prevxpos);
			unset($prevypos);
			$xpos=$x0;
			$x_fac=($shift_x*$x1)/count($this_values);
			foreach ($this_values as $yval) {
				$xpos+=$x_fac;
				$ypos=$y0*(($paramHash["min_y"]-$yval)*$fac+1);
				if (isset($prevxpos)) { // && ($idx+$xpos)%2
					imageline($im,$xpos,$ypos,$prevxpos,$prevypos,$color[$colornum]);
				}
				
				$prevxpos=$xpos;
				$prevypos=$ypos;
			}
			
			// draw legend
			if (isset($paramHash["trace_names"][$colornum])) {
				$fontbox=imagettftext($im,$ttffontsize,0,$legend_x,$legend_y,$color[$colornum],$ttffontname,$paramHash["trace_names"][$colornum]);
				$legend_y+=abs($fontbox[1]-$fontbox[7]);
			}
			$colornum++;
		}
		// ticks
		foreach ($peak_block_list as $idx => $this_peak_block_list) {
			$block_count=count($values[$idx]);
			if (is_array($this_peak_block_list)) foreach ($this_peak_block_list as $block_no) {
				// get ypos for this block
				$xpos=$x0+$shift_x*$x1*$block_no/$block_count;
				$yval=$values[$idx][$block_no];
				$ypos=constrainVal($y0*(($paramHash["min_y"]-$yval)*$fac+1),$y1,$y0);
				imageline($im,$xpos,$ypos+5,$xpos,$ypos+15,$green);
				
				// labels
				if (isset($paramHash["label_distance"]) && (abs($xpos-$last_label_x)>$paramHash["label_distance"] || abs($ypos-$last_label_y)>15)) {
					$last_label_x=$xpos;
					$last_label_y=$ypos;
					$xval=round($paramHash["min_x"]+$delta_x*$block_no/$block_count,0);
					$text_xpos=constrainVal($xpos,$x0+10,$x1-10);
					$text_ypos=constrainVal($ypos+10,$y1,$y0);
					imagettftext($im,$ttffontsize,0,$xpos,$text_ypos,$green,$ttffontname,$xval); // text
				}
			}
		}
	}
	imagecolortransparent($im,$white);
	ob_start();
	switch (strtolower($paramHash["format"])) {
	case "":
	case "gif":
		imagegif($im); 
	break;
	case "jpg":
	case "jpeg":
		imagejpeg($im); 
	break;
	case "png":
		imagepng($im); 
	}
	return ob_get_clean();
}

function getScaledImg($img_data,$paramHash=array()) { // if one is <1 preserve scale, if both return src
	$width=ifempty($paramHash["width"],0);
	$height=ifempty($paramHash["height"],0);
	$format=$paramHash["format"];
	
	$im2=@imagecreatefromstring($img_data);
	if ($im2===FALSE) {
		//~ return $img_data;
		return getEmptyImage();
	}
	// clipping of original img, clipLeft,clipTop,clipWidth,clipHeight
	
	
	$old_width=imagesx($im2);
	$old_height=imagesy($im2);
	
	if (isset($paramHash["clipWidth"])) {
		$old_width=$paramHash["clipWidth"];
	}
	
	if (isset($paramHash["clipHeight"])) {
		$old_height=$paramHash["clipHeight"];
	}
	
	if ($old_width<1 || $old_height<1) { // nonsense
		return;
	}
	if ($width<1 && $height<1) {
		$width=$old_width;
		$height=$old_height;
	}
	elseif ($width<1) {
		$width=$old_width/$old_height*$height;
	}
	//~ elseif ($height<1) {
	else { // maintain aspect ratio
		$height=$old_height/$old_width*$width;
	}
	
	switch (strtolower($format)) {
	case "gif":
		$im=imagecreate($width, $height);
	break;
	case "jpg":
	case "jpeg":
	case "png":
	default:
		$im=imagecreatetruecolor($width, $height);
	}
	
	imagecopyresampled($im,$im2,0,0,max(0,intval($paramHash["clipLeft"])),max(0,intval($paramHash["clipTop"])),$width, $height,$old_width,$old_height);
	ob_start();
	switch (strtolower($format)) {
	case "gif":
		imagegif($im); 
	break;
	case "jpg":
	case "jpeg":
		imagejpeg($im); 
	break;
	case "png":
	default:
		imagepng($im); 
	}
	return ob_get_clean();
}

function readASCII($asc,$paramHash=array()) {
	$sep_re=ifempty($paramHash["sep_re"],"/[\s\t]+/");
	// search for x  y-Pairs
	$asc=fixLineEnd($asc);
	$lines=explode("\n",$asc);
	// cut comment
	for ($a=0;$a<count($lines);$a++) {
		list($x,$y)=preg_split($sep_re,trim($lines[$a]),2);
		$x=trim($x);
		$y=trim($y);
		if (is_numeric($x) && is_numeric($y)) { // Datenpunkt gefunden
			$x_values[]=$x;
			$graph_data[]=$y;
		}
	}
	
	if (count($graph_data)) {
		//~ $x_values=array_keys($graph_data);
		
		$dataHash["x_max"]=max($x_values);
		$dataHash["x_min"]=min($x_values);
		$dataHash["y_max"]=max($graph_data);
		$dataHash["y_min"]=min($graph_data);
	}
	
	return array("graph_data" => $graph_data, "dataHash" => $dataHash);
}

function colUnpack($string,$codeArray) {
	$colArray=array();
	foreach ($codeArray as $code) {
		$colArray[]=get_up_len($code);
	}
	$parts=colSplit($string,$colArray,true);
	foreach ($codeArray as $idx => $code) {
		$parts[$idx]=up($code,$parts[$idx]);
	}
	return $parts;
}

function get_up_len($code) {
	switch ($code) {
	case "C":
		return 1;
	break;
	case "s":
	case "S":
	case "n":
	case "v":
	case "b": // fake
	case "g": // fake
		return 2;
	break;
	case "f":
	case "F": // fake
	case "I":
	case "m": // fake
	case "w": // fake
	case "L":
	case "N":
	case "V":
		return 4;
	break;
	case "d":
	case "D": // fake
		return 8;
	break;
	}
}

function up($code,$data) {
	$len=strlen($data);
	if ($len==0) {
		return;
	}
	$des_len=get_up_len($code);
	if ($des_len && $des_len!=$len) {
		return;
	}
	
	// swap F and D
	switch ($code) {
	case "b":
		if ($GLOBALS["littleEndian"]) {
			$data=strrev($data);
		}
		$code="s";
	break;
	case "g":
		if (!$GLOBALS["littleEndian"]) {
			$data=strrev($data);
		}
		$code="s";
	break;
	case "m":
		if ($GLOBALS["littleEndian"]) {
			$data=strrev($data);
		}
		$code="l";
	break;
	case "w":
		if (!$GLOBALS["littleEndian"]) {
			$data=strrev($data);
		}
		$code="l";
	break;
	case "F":
		$data=strrev($data);
		$code="f";
	break;
	case "D":
		$data=strrev($data);
		$code="d";
	break;
	}
	
	$temp=unpack($code."i",$data);
	
	// debug
	//~ echo $code.$temp["i"]."\n";
	
	return $temp["i"];
}

function pk($code,$data) {
	$real_code=$code;
	switch ($code) {
	case "b":
	case "g":
		$real_code="s";
	break;
	case "m":
	case "w":
		$real_code="l";
	break;
	case "F":
		$real_code="f";
	break;
	case "D":
		$real_code="d";
	break;
	}
	$retval=pack($real_code,$data);
	switch ($code) {
	case "b":
		if ($GLOBALS["littleEndian"]) {
			$retval=strrev($retval);
		}
	break;
	case "g":
		if (!$GLOBALS["littleEndian"]) {
			$retval=strrev($retval);
		}
	break;
	case "m":
		if ($GLOBALS["littleEndian"]) {
			$retval=strrev($retval);
		}
	break;
	case "w":
		if (!$GLOBALS["littleEndian"]) {
			$retval=strrev($retval);
		}
	break;
	case "F":
		$retval=strrev($retval);
	break;
	case "D":
		$retval=strrev($retval);
	break;
	}
	return $retval;
}

// OLE helper
function getOLEchild(& $ole,$path) {
	$list=$ole->_list;
	$ole_branch=$list[0];
	for ($a=0;$a<count($path);$a++) {
		$children=$ole_branch->children;
		if (is_array($children)) foreach ($children as $child) {
			$child_name=$child->Name;
			if ($child_name==$path[$a]) {
				$ole_branch=$child;
				continue 2;
			}
		}
		
		// not found
		return;
	}
	return $ole_branch;
}

?>