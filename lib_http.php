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

require_once "HTTP/Request2.php";

// http request wrapper
function oe_http_get($url,$options=array()) {
	return oe_http_backend(HTTP_Request2::METHOD_GET,$url,array(),array(),$options);
}

function oe_http_post_fields($url,$data=array(),$files=array(),$options=array()) {
	return oe_http_backend(HTTP_Request2::METHOD_POST,$url,$data,$files,$options);
}

function oe_http_backend($method,$url,$data=array(),$files=array(),$options=array()) {
	$request=new HTTP_Request2($url,$method);
	if (is_array($options["cookies"])) foreach ($options["cookies"] as $key => $value) {
		$request->addCookie($key,$value);
	}
	if (is_array($data)) foreach ($data as $key => $value) {
		$request->addPostParameter($key,$value);
	} elseif ($data) {
		$request->setBody($data);
	}
	if (is_array($files)) foreach ($files as $file_info) {
		$request->addUpload($file_info["name"],$file_info["file"],$file_info["file"],$file_info["type"]);
	}
	if ($options["mime"]) {
		$request->setHeader("Content-type",$options["mime"]);
	}
	if ($options["redirect"]) {
		$request->setConfig("follow_redirects",true);
		$request->setConfig("max_redirects",$options["redirect"]);
	}
	oe_http_map_option($request,$options,"proxyhost","proxy");
	oe_http_map_option($request,$options,"connect_timeout","connect_timeout");
	oe_http_map_option($request,$options,"timeout","timeout");
	$request->setHeader("User-Agent",$options["useragent"]);
	if ($options["referer"]) {
		$request->setHeader("referer",$options["referer"]);
	}
	$request->setHeader("Accept","text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8");
	$request->setHeader("Accept-Encoding","gzip, deflate, br");
	if ($options["accept-language"]) {
		$request->setHeader("Accept-Language",$options["accept-language"]);
	} else {
		// needed for merck
		$request->setHeader("Accept-Language","en-US");
	}
	
	// unsecure, but not critical for this application
	$request->setConfig("ssl_verify_peer",false);
	$request->setConfig("ssl_verify_host",false);
	
	// test to avoid Elsevier problems
	$request->setConfig("buffer_size",32768);
	
	// maintain cookies across redirects
	$request->setCookieJar();
	
	try {
		$response=$request->send();
		return $response;
	} catch (Exception $e) {
		error_log("Error: ".$e->getMessage()."\n".$e->getTraceAsString());
	}
	return false;
}

function oe_get_cookies($response) {
	$cookies=$response->getCookies();
	$retval=array();
	if (is_array($cookies)) foreach ($cookies as $cookie) {
		$retval[$cookie["name"]]=$cookie["value"];
	}
	return $retval;
}

function oe_http_map_option($request,$options,$old_name,$new_name) {
	if ($options[$old_name]) {
		$request->setConfig($new_name,$options[$old_name]);
	}
}
?>