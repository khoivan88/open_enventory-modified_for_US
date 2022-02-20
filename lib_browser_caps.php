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

function isUaSvgCapable() {
	$ua=strtolower(getenv("HTTP_USER_AGENT"));
	if (strpos($ua,"gecko")!==false || strpos($ua,"firefox")!==false) {
		return true;
	}
	return false;
}

function checkBrowserToken($token) {
	global $browserCheck;
	if (isset($browserCheck[$token])) {
		return $browserCheck[$token];
	}
	if (preg_match("/(?ims)".$token."/",getenv("HTTP_USER_AGENT"))>0) {
		$browserCheck[$token]=true;
		return true;
	}
	$browserCheck[$token]=false;
	return false;	
}

function isMSIE() {
	return checkBrowserToken("MSIE");
}

function isChrome() {
	return checkBrowserToken("Chrome");
}

function isSafari() {
	return checkBrowserToken("Safari");
}

function isOpera() {
	return checkBrowserToken("Opera");
}

function isFF3x() {
	return (checkBrowserToken("Firefox\/(:?[3-9]|\d{2,})"));
}

function isFF1x() {
	return (checkBrowserToken("Firefox\/\d{2,}"));
}
?>