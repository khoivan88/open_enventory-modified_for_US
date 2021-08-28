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

// functions for file access and ftp, only for analytics. This system uses the database for storage whenever possible

require_once "lib_global_settings.php";
require_once "lib_formatting.php";
require_once "lib_http.php";
// File/Archive.php is provided by PEAR!
require_once "File/Archive.php";
@File_Archive::setOption('tmpDirectory',oe_get_temp_dir());


$tar_stat=array(2 => 0777, );

/* function makePath($path) {
	$next_slash=strpos($path,"/",1);
	do {
		$sub_path=substr($path,0,$next_slash);
		clearstatcache();
		if (is_dir($sub_path)==false) {
			umask(0);
			mkdir($sub_path,0777);
		}
		$next_slash=strpos($path,"/",$next_slash+1);
	}
	while ($next_slash!=false);
}*/

function makePath($pathname) {
	clearstatcache();
	umask(0);
	is_dir(dirname($pathname)) || makePath(dirname($pathname));
	return is_dir($pathname) || @mkdir($pathname,0777);
}

function getDirLink($path,$text,$int_name="") {
	$retval.="<a";
	if (!empty($int_name)) {
		$retval.=" onClick=\"parent.FBshowWaitMsg(&quot;".$int_name."&quot;)\"";
	}
	$retval.=" href=\"".getSelfRef(array("path"),array("analytics_device_id","int_name"))."&path=".$path."\">".$text."</a>";
	return $retval;
}

function getAnalyticsDevice($analytics_device_id) {
	list($result)=mysql_select_array(array(
		"table" => "analytics_device", 
		"dbs" => -1, 
		"filter" => "analytics_device_id=".fixNull($analytics_device_id), 
		"limit" => 1, 
	));
	return $result;
}

function getDirList($analytics_device_id,$path="",$int_name="") { // Zeichnet Verzeichnisliste in <table
	global $g_settings,$own_data;
	$hideIframe=script.
		"parent.hideObj(".fixStr("FBbrowser_".$int_name).");".
		_script;
	$showIframe=script.
		"parent.showObj(".fixStr("FBbrowser_".$int_name).");".
		_script;
	
// Parameter: $analytics_device_id, $path (aktueller Pfad)
	//~ $result=mysql_select_array(array( "table" => "analytics_device", "dbs" => -1, "filter" => "analytics_device_id=".fixNull($analytics_device_id), "limit" => 1 ));
	$result=getAnalyticsDevice($analytics_device_id);
	$result["analytics_device_url"]=fixPath($result["analytics_device_url"]);
	
	// add sigle to path if rule is set
	if (limit_access_to_sigle || $g_settings["limit_access_to_sigle"]) {
		if (empty($own_data["sigle"])) {
			return $hideIframe;
		}
		$result["analytics_device_url"].="/".$own_data["sigle"];
	}
	
	makeAnalyticsPathSafe($result["analytics_device_url"]);
	if (empty($result["analytics_device_url"])) {
		return $hideIframe;
	}
	$path=fixPath($path); // fix Backslashes and multiple trailing slashes
	$paramHash["path"]=$result["analytics_device_url"];
	
	// change dir
	if (!empty($path) && $path!=$result["analytics_device_url"] && isSubPath($path,$result["analytics_device_url"])) { // allow only proper subdirs
		$paramHash["path"]=$path;
		$last_slash_pos=strrpos($paramHash["path"],"/");
		if ($last_slash_pos!==FALSE) {
			$upDir=substr($paramHash["path"],0,$last_slash_pos);
		}
	}
	
	// hide if path is empty
	if (empty($paramHash["path"])) {
		return $hideIframe;
	}
	
	$paramHash["username"]=$result["analytics_device_username"];
	$paramHash["password"]=$result["analytics_device_password"];
	
	$dirList=getPathListing($paramHash);
	$retval=$paramHash["path"].
	$showIframe.
	"<table><thead><tr><td>".s("file_name")."</td><td>".s("file_size")."</td><td>".s("file_changed")."</td><td>".s("do_select")."</td></tr></thead><tbody>"; // Name Größe Auswählen
	if ($upDir) {
		$retval.="<tr><td colspan=\"4\">".getDirLink($upDir,"..",$int_name)."</td></tr>"; // --> javascript, wg methode
	}
	if (is_array($dirList["data"])) foreach($dirList["data"] as $file) {
		if ($file["dir"]) { // Verzeichnis
			$retval.="
<tr>
<td><a name=".fixStr($file["filename"]).">".getDirLink($paramHash["path"]."/".$file["filename"],$file["filename"],$int_name)."</a></td>
<td>&nbsp;</td>
<td>".getGermanDate($file["timestamp"],true)."</td>";
		}
		else { // Datei
			$retval.="
<tr>
<td><a name=".fixStr($file["filename"]).">".$file["filename"]."</a></td>
<td>".formatSize($file["size"])."</td>
<td>".getGermanDate($file["timestamp"],true)."</td>";
		}
		// common: select
		$retval.="
<td><a href=\"javascript:parent.FBsetPath(".fixQuot($int_name).",".fixQuot($paramHash["path"]."/".$file["filename"]).")\" onClick=\"parent.valChanged()\" class=\"imgButtonSm\"><img src=\"./lib/select_sm.png\" border=\"0\"".getTooltip("do_select")."></a></td>
</tr>";
	}
	$retval.="</tbody></table>";
	return $retval;
}

function ftp_fetch(& $retval,$stream,$remote_file) {
	ob_start();
	$out=fopen("php://output","w");
	//~ ftp_fget($stream,$out,$remote_file, FTP_BINARY) or die("Unable to get file: ".$remote_file);
	if (!@ftp_fget($stream,$out,$remote_file, FTP_BINARY)) {
		$retval["error"]=true;
	}
	fclose($out);
	$data=ob_get_clean();
	return $data;
}

function file_fetch(& $retval,$filename) {
	if ($handle=@fopen($filename,"rb")) { // suppress error msg
		$filesize=@filesize($filename);
		$contents="";
		if ($filesize>0) {
			$contents=fread($handle,$filesize);
		}
		fclose($handle);
		return $contents;
	}
	$retval["error"]=true;
}

// behandelt rekursiv ein FTP-Verzeichnis
function procFTPdir(& $retval,$stream,& $zip,& $paramHash,$basedir,$dir="") { // recursive
	global $tar_stat;
	// flags: SP_DIR_ONLY(1)=dir only, SP_ZIP(2)=$zip-Objekt verwenden, SP_FTP(4)=ftp
	
	$flags=$paramHash["flags"];
	if (!is_array($paramHash["skippaths"])) {
		$paramHash["skippaths"]=array();
	}
	if (!is_array($paramHash["skipfiles"])) {
		$paramHash["skipfiles"]=array();
	}
	
	if ($dir==="") {
		$last_slash_pos=strrpos($basedir,"/");
		$dir=substr($basedir,$last_slash_pos+1);
		$basedir=substr($basedir,0,$last_slash_pos+1);
	}
	
	//~ $filelist=ftp_nlist($stream,"-lA /".$basedir.$dir);
	//~ $total=array_shift($filelist);
	
	$filelist=ftp_rawlist($stream,"/".$basedir.$dir);
	/* $filelist=array( // Novell
		"d [-----FM-] TITAN5_SYS 512 Jul 03 14:50 /SYS",
		"d [RWCEAFMS] krp05135 512 Dec 18 2008 ./Handbuch Galaxie",
		"- [RWCEAFMS] krp05135 3317 Dec 08 1994 ./A6262.SKC",
	);*/
	/* $filelist=array( // Unix
		"drwxr-xr-x    3 ocgo     user          19 Jun 24 17:08 su shen",
		"-rw-r--r--    1 ocgo     user      262144 Jun  5 14:59 fid",
	);*/
	
	if (is_array($filelist)) foreach($filelist as $line) {
		$isdir=($line{0}=="d"); // seems to be standard
		
		if (startswith($line,"total")) {
			continue;
		}
		
		if (!isset($server_type)) { // autodetect server type
			if (strpos($line,"/")!==FALSE) { // ./ prepending filenames, / prepending dirs
				$server_type="novell";
			}
			elseif (strpos($line," ")>8) { // longer permissions
				$server_type="unix";
			}
		}
		switch ($server_type) {
		case "unix":
			$split=preg_split("/[\s]+/",$line,9,PREG_SPLIT_NO_EMPTY);
			$filename=$split[8];
			//~ list($filename)=explode("->",substr($line,55));
			list($filename)=explode("->",$filename); // fix symlinks
			$timestamp=strtotime($split[5]." ".$split[6]." ".$split[7]);
			$size=$split[4]; 
		break;
		case "novell":
			$split=preg_split("/[\s]+/",$line,8,PREG_SPLIT_NO_EMPTY);
			$filename=$split[7];
			cutRange($filename,"/","",false);
			$timestamp=strtotime($split[4]." ".$split[5]." ".$split[6]);
			$size=$split[3]; 
		break;
		}
		
		$filename=trim($filename);

		if (in_array($dir."/".$filename,$paramHash["skippaths"]) || in_array($filename,$paramHash["skipfiles"])) {
			continue;
		}
		
		if ($flags & SP_ZIP) { // zip
			if ($isdir) {
				procFTPdir($retval,$stream,$zip,$paramHash,$basedir,$dir."/".$filename);					
			}
			else {
				ftp_chdir($stream,"/".$basedir.$dir);
				$contents=ftp_fetch($retval,$stream,$filename);
				if (!empty($paramHash["imageExt"]) && empty($retval["imgdata"]) && endswith($filename,".".$paramHash["imageExt"])) { // take 1st file with given extension
					$retval["imgdata"]=$contents;
				}
				// $zip->appendFile($dir."/".$filename,$contents);
				$zip->newFile($dir."/".$filename,$tar_stat);
				$zip->writeData($contents);
			}
		}
		elseif (($flags & SP_DIR_ONLY)==0 || $isdir) { // listing
			//~ $timestamp=strtotime(trim(substr($line,42,13)));
			$retval["data"][]=array(
				"dir" => $isdir, 
				//~ "size" => trim(substr($line,33,9)), 
				"size" => $size,
				"date" => strftime("%c",$timestamp), 
				"timestamp" => $timestamp, 
				"filename" => $filename,
			); // "raw" => $line, 
		}
	}
}

function procPath(& $retval,$stream,& $zip,& $paramHash,$basedir,$dir="") { // recursive
	global $tar_stat;
	$flags=$paramHash["flags"];
	
	if ($dir==="") {
		$last_slash_pos=strrpos($basedir,"/");
		$dir=substr($basedir,$last_slash_pos+1);
		$basedir=substr($basedir,0,$last_slash_pos+1);
	}
	$dir.="/";
	
	if (!is_dir($basedir.$dir)) {
		$retval["error"]=true;
		return array();
	}
	
	$filelist=@scandir($basedir.$dir);
	if (is_array($filelist)) foreach($filelist as $filename) {
		if ((is_array($paramHash["skippaths"]) && in_array($dir.$filename,$paramHash["skippaths"])) || (is_array($paramHash["skipfiles"]) && in_array($filename,$paramHash["skipfiles"]))) {
			continue;
		}
		$isdir=is_dir($basedir.$dir.$filename);
		if ($filename=="." || $filename=="..") {
			// do nothing
		}
		elseif ($flags & SP_ZIP) { // make ZIP recursively
			if ($isdir) {
				procPath($retval,$stream,$zip,$paramHash,$basedir,$dir.$filename);					
			}
			else {
				// echo $basedir.$dir.$filename."\n";
				$contents=file_fetch($retval,$basedir.$dir.$filename);
				if (!empty($paramHash["imageExt"]) && empty($retval["imgdata"]) && endswith($filename,".".$paramHash["imageExt"])) { // take 1st file with given extension
					$retval["imgdata"]=$contents;
				}
				// $zip->appendFile($dir.$filename,$contents);
				$zip->newFile($dir.$filename,$tar_stat);
				$zip->writeData($contents);
			}
		}
		elseif (($flags & SP_DIR_ONLY)==0 || $isdir) {
			$timestamp=@filemtime($basedir.$dir.$filename);
			$retval["data"][]=array(
				"dir" => $isdir, 
				"size" => @filesize($basedir.$dir.$filename), 
				"date" => strftime("%c",$timestamp), 
				"timestamp" => $timestamp, 
				"filename" =>  $filename, 
			);
		}
	}
}

function getCompressHeader($compressFormat=null) {
	if (is_null($compressFormat)) {
		$compressFormat=compressFormat;
	}
	switch ($compressFormat) {
	case "zip":
		header("Content-Type: application/zip");
	break;
	case "tgz":
		header("Content-Type: application/x-gzip");
	break;
	}
}

function getPathListing($paramHash) {
	global $tar_stat;
	
	$flags=& $paramHash["flags"]; // SP_DIR_ONLY(1): dir only, SP_ZIP(2): zip, SP_FTP(4): ftp
	$flags+=0;
	$path=$paramHash["path"];
	// skiplist
	$retval=array();
	
	if (!pathSafe($path)) { // not allowed
		return false;
	}
	
	if (startswith($path,"ftp://")) {
		$flags|=SP_FTP; // autodetect ftp
	}
	
	//~ $imageExt=$paramHash["imageExt"]; // "gif", "wmf",... make sure that there is only 1 matching file, otherwise results may not be reproducible 
	if ($flags & SP_ZIP) {
		//~ $zip=File_Archive::toArchive(null,File_Archive::toVariable($retval["zipdata"]),"zip");
		$zip=File_Archive::toArchive(null,File_Archive::toVariable($retval["zipdata"]),compressFormat);
	}
	
	if (startswith($path,"biotage://")) { // special handling for this JSP/MSSQL crap
		$parsed_url=parse_url($path);
		
		if (empty($parsed_url["host"])) {
			return;
		}
		$parsed_url["host"]="http://".$parsed_url["host"];
		
		list(,$user,$identifier)=explode("/",$parsed_url["path"],3);
		if (empty($user) && empty($identifier)) {
			$parsed_url["path"]="";
		}
		else {
			$retval["data"]=array();
			$parsed_url["path"]=substr($parsed_url["path"],1); // remove trailing /
		}
		
		// get session
		$response=oe_http_get($parsed_url["host"],array("redirect" => maxRedir, "useragent" => uA));
		if ($response===FALSE) {
			return;
		}
		$cookies=oe_get_cookies($response);
		$body=@$response->getBody();
		preg_match_all("/(?ims)<td.*?<\/td>/",$body,$cells,PREG_PATTERN_ORDER);
		$cells=$cells[0];
		for ($b=0;$b<count($cells);$b++) {
			if (strpos($cells[$b],"Results")!==FALSE) {
				$url=getHref($cells[$b]);
				break;
			}
		}
		
		if (empty($url)) {
			return;
		}
		
		$response=oe_http_get($parsed_url["host"].$url,array("redirect" => maxRedir, "cookies" => $cookies, "useragent" => uA));
		$body=$response->getBody();
		$action=getFormAction($body);
		
		preg_match_all("/(?ims)(<select[^>]*?>)(.*?)<\/select>/",$body,$selects,PREG_SET_ORDER);
		
		for ($b=0;$b<count($selects);$b++) {
			if (strpos($selects[$b][1],"user")!==FALSE) {
				preg_match_all("/(?ims)<option[^>]*value=\"([^\"]+)\"[^>]*>([^<]*)/",$selects[$b][2],$users,PREG_SET_ORDER);
				break;
			}
		}
		//~ var_dump($users);die();
		
		for ($b=0;$b<count($users);$b++) {
			if (!empty($user) && $user!=$users[$b][2]) {
				continue;
			}
			
			$response=oe_http_post_fields($parsed_url["host"].$action,array("user" => $users[$b][1], "after" => "", "before" => "", "search" => "Search", ),array("redirect" => maxRedir, "useragent" => uA, "cookies" => $cookies));
			$body=@$response->getBody();
			cutRange($body,"</form>"); // remove all shit
			
			// search for desired file or build list
			$entries=BiotageGetEntries($body,$users[$b][2]);
			if (empty($parsed_url["path"])) { // get listing
				$retval["data"]=arr_merge($retval["data"],$entries);
			}
			else { // single exp chosen, always make zip
				$exp_search=BiotageFindExp($zip,$parsed_url["host"],$cookies,$entries,$parsed_url["path"]);
				if ($exp_search==true) { // found
					break; // finish operation
				}
			}
			
			// get links to other pages
			cutRange($body,"</table>"); // remove all shit
			preg_match_all("/(?ims)(<a[^>]*?>)(.*?)<\/a>/",$body,$other_pages,PREG_SET_ORDER);
			for ($c=0;$c<count($other_pages);$c++) {
				$text=trim(strip_tags($other_pages[$c][2]));
				if (!is_numeric($text)) {
					continue;
				}
				$url=getHref($other_pages[$c][1]);
				$response=oe_http_get($parsed_url["host"].$url,array("redirect" => maxRedir, "cookies" => $cookies, "useragent" => uA));
				$body=$response->getBody();
				cutRange($body,"</form>"); // remove all shit
				
				// search for desired file or build list
				$entries=BiotageGetEntries($body,$users[$b][2]);
				if (empty($parsed_url["path"])) { // get listing
					$retval["data"]=arr_merge($retval["data"],$entries);
				}
				else { // single exp chosen, always make zip
					$exp_search=BiotageFindExp($zip,$parsed_url["host"],$cookies,$entries,$parsed_url["path"]);
					if ($exp_search==true) { // found
						break 2; // finish operation
					}
				}
			}
		}
	}
	elseif ($flags & SP_FTP) {
		$username=$paramHash["username"];
		$password=$paramHash["password"];
		
		list($host,$basedir)=explode("/",fixFtp($path),2);
		if ($stream=ftp_connect($host)) {
			$last_slash_pos=strrpos($basedir,"/");
			$retval["filename"]=substr($basedir,$last_slash_pos+1);
			@ftp_login($stream,$username,$password); // some servers do not recognize this command, ignore errors in this case
			
			//~ ftp_raw($stream,"TYPE I");
			//~ ftp_pwd($stream);
			
			ftp_pasv($stream,true);
			if (@ftp_chdir($stream,"/".$basedir)) { // path, zipping or dir list
				procFTPdir($retval,$stream,$zip,$paramHash,$basedir,"");
			}
			else { // single file
				$retval["data"]=ftp_fetch($retval,$stream,$basedir);
				$last_slash_pos=strrpos($basedir,"/");
				$retval["filename"]=substr($basedir,$last_slash_pos+1);
				if ($flags & SP_ZIP) {
					//~ $zip->appendFile($retval["filename"],$retval["data"]);
					$zip->newFile($retval["filename"],$tar_stat);
					$zip->writeData($retval["data"]);
				}
			}
			ftp_quit($stream);
		}
		else {
			$retval["error"]=true;
		}
	}
	else {
		if (!file_exists($path)) {
			$retval["error"]=true;
			// do nothing
		}
		elseif (is_dir($path)) { // path: zipping or dir list
			$basedir=fixPath($path); // entfernt slashes am Ende
			$last_slash_pos=strrpos($basedir,"/");
			$retval["filename"]=substr($basedir,$last_slash_pos+1);
			procPath($retval,$stream,$zip,$paramHash,$basedir,"");
		}
		else { // single file
			$retval["data"]=file_fetch($retval,$path);
			$last_slash_pos=strrpos($path,"/");
			$retval["filename"]=substr($path,$last_slash_pos+1);
			if ($flags & SP_ZIP) {
				// $zip->appendFile($retval["filename"],$retval["data"]);
				$zip->newFile($retval["filename"],$tar_stat);
				$zip->writeData($retval["data"]);
			}
		}
	}
	if ($flags & SP_ZIP) {
		$zip->close();
	}
	return $retval; // data, filename
}

function BiotageGetEntries($body,$user) { // these bast** cannot get ftp to run
	preg_match_all("/(?ims)<tr.*?<\/tr>/",$body,$manyLines,PREG_PATTERN_ORDER);
	$manyLines=$manyLines[0];
	$retval=array();
	
	for ($b=0;$b<count($manyLines);$b++) {
		if (preg_match_all("/(?ims)<td.*?<\/td>/",$manyLines[$b],$cells,PREG_PATTERN_ORDER)) {
			$cells=$cells[0];
			//~ $date_time=strptime(strip_tags($cells[1]),"%Y.%m.%d %H:%M:%S");
			$timestamp=strtotime(strip_tags($cells[1]));
			$retval[]=array("filename" => $user."/".strip_tags($cells[0]), "size" => -1, "date" => strftime("%c",$timestamp), "timestamp" => $timestamp, "link" => getHref($cells[2]));
		}
	}
	return $retval;
}

function BiotageFindExp(& $zip,$host,$cookies,& $entries,$path) {
	global $tar_stat;
	
	for ($b=0;$b<count($entries);$b++) {
		if ($entries[$b]["filename"]!=$path) {
			continue;
		}
		
		$response=oe_http_get($host.$entries[$b]["link"],array("redirect" => maxRedir, "cookies" => $cookies, "useragent" => uA));
		$body=@$response->getBody();
		cutRange($body,"<div class=\"content\">"); // remove all shit
		$body=str_replace(array("\n","\r"),"",$body); // remove bogus line-breaks in report
		
		// found, get data (text, images, csv)
		$zip->newFile("report.html",$tar_stat);
		$zip->writeData("<html><body>".$body);

		preg_match_all("/(?ims)<h3>(.*?)<\/h3>.*?(<img[^>]*?>)/",$body,$images,PREG_SET_ORDER);
		
		for ($c=0;$c<count($images);$c++) {
			list($img_text)=explode(" ",strip_tags($images[$c][1]));
			$img_link=getImgSrc($images[$c][2]);
			$img_response=oe_http_get($host.$img_link,array("redirect" => maxRedir, "cookies" => $cookies, "useragent" => uA));
			$img_data=@$img_response->getBody();
			$zip->newFile($img_text.".png",$tar_stat);
			$zip->writeData($img_data);
		}
		
		// CSV
		preg_match_all("/(?ims)(<a[^>]*?>)(.*?)<\/a>/",$body,$links,PREG_SET_ORDER);
		for ($c=0;$c<count($links);$c++) {
			$text=strip_tags($links[$c][2]);
			if (strpos($text,"CSV")!==FALSE) {
				$csv_response=oe_http_get($host.getHref($links[$c][1]),array("redirect" => maxRedir, "cookies" => $cookies, "useragent" => uA));
				$csv_data=@$csv_response->getBody();
				$zip->newFile("csv.zip",$tar_stat);
				$zip->writeData($csv_data);
				break;
			}
		}
		
		return true;
	}
	return false;
}

?>