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

// workaround for PHP < 5.2.1
if (!function_exists("sys_get_temp_dir")) {
	function sys_get_temp_dir() {
		if (!empty($_ENV["TMP"])) {
			return realpath($_ENV["TMP"]);
		}
		if (!empty($_ENV["TMPDIR"])) {	
			return realpath( $_ENV["TMPDIR"]);
		}
		if (!empty($_ENV["TEMP"])) {
			return realpath( $_ENV["TEMP"]);
		}
		$tempfile=oe_tempnam(uniqid(rand(),TRUE),"");
		if (file_exists($tempfile)) {
			unlink($tempfile);
			return realpath(dirname($tempfile));
		}
	}
}

function oe_get_temp_dir() {
	if (defined("tempDirPath")) {
		return tempDirPath;
	}
	return sys_get_temp_dir();
}

function oe_tempnam($tmpdir,$prefix) {
	// always unixify
	return str_replace(DIRECTORY_SEPARATOR,"/",tempnam($tmpdir,$prefix));
}

function getProtocolFilename() {
	return fixPath(oe_get_temp_dir())."/openenv.log";
}

function errorProtocol($filename) {
	loadLanguage();
	debug_print_backtrace();
	die(s("failed_to_log1").$filename.s("failed_to_log2"));
}

function addToProtocol($ip,$user) {
	if (empty($user)) { // take out later
		debug_print_backtrace();
		die();
	}
	$filename=getProtocolFilename();
	$handle=fopen($filename,"a");
	if ($handle) {
		flock($handle,LOCK_EX);
		$ip=secLog($ip);
		$user=secLog($user);
		if (empty($user)) { // take out later
			print_debug_backtrace();
			die();
		}
		fputs($handle,$ip."\t".$user."\t".time()."\t".getenv("SCRIPT_NAME")."\n"); // ."\t".getenv("SCRIPT_FILENAME")
		//~ fputs($handle,$ip."\t".$user."\t".time()."\n"); // ."\t".getenv("SCRIPT_FILENAME")
		flock($handle,LOCK_UN);
		fclose($handle);
	}
	else {
		errorProtocol($filename);
	}
}

function secLog($text) {
	$text=str_replace(array("\t"),"",$text);
	return trim($text);
}

function resetProtocol() {
	global $db_user;
	if ($db_user==ROOT) {
		$filename=getProtocolFilename();
		@unlink($filename);
	}
}

function checkProtocol($ip,$user,$correct=false) {
	if (empty($user)) { // take out later
		print_debug_backtrace();
		die();
	}
	$filename=getProtocolFilename();
	if (!is_file($filename)) {
		$handle=fopen($filename,"a");
		fclose($handle);
		// change owner & permissions
		
	}
	$handle=@fopen($filename,"r");
	if ($handle) {
		flock($handle,LOCK_SH);
		$ip_count=0;
		$user_count=0;
		$ip=secLog($ip);
		$user=secLog($user);
		$now=time();
		$ban_duration=30*60;
		if (defined("ban_duration")) {
			$ban_duration=ban_duration;
		}
		$login_max_retries=4;
		if (defined("login_max_retries")) {
			$login_max_retries=login_max_retries;
		}
		while (!feof($handle)) {
			$buffer=fgets($handle);
			list($test_ip,$test_user,$time)=explode("\t",$buffer);
			if ($time+$ban_duration<$now) {
				continue;
			}
			if (loginHeals && $correct && $test_user==$user) { // korrektes Login von IP bringt nix
				continue;
			}
			$data.=$buffer;
			if ($test_ip==$ip) {
				$ip_count++;
			}
			if ($test_user==$user) {
				$user_count++;
			}
			if ($ip_count>$login_max_retries) {
				$retval=1;
			}
			elseif ($user_count>$login_max_retries) {
				$retval=2;
			}
			if (!empty($retval)) {
				fclose($handle);
				return $retval;
			}
		}
		flock($handle,LOCK_UN);
		fclose($handle);
		// write new
		if (file_put_contents($filename,$data)===FALSE) {
			errorProtocol($filename);
		}
		return -1;
	}
	else {
		errorProtocol($filename);
	}
}

?>