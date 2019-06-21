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

var outputCache="",timeStart,barcodeMode=false,resetModeTimeout;

function searchFrameRecursive(frameObj,frameName,pathText) {
	var retval="";
	if (frameObj.name==frameName) {
		return pathText;
	}
	for (var b=0,max=frameObj.frames.length;b<max;b++) {
		retval=searchFrameRecursive(frameObj[b],frameName,pathText+"."+frameObj[b].name);
		if (retval) {
			return retval;
		}
	}
	return false;
}

function barcodeRead(barcode) {
	// overwrite function to use
	var commFrameObj,is_self;
	if (window.frames["comm"]) {
		commFrameObj=window.frames["comm"];
		is_self=true;
	}
	else if (top.frames["comm"]) {
		commFrameObj=top.frames["comm"];
	}
	else {
		return;
	}
	barcode=trim(barcode);
	var target="";
	if (!is_self || self.name!="mainpage") {
		// find browsemain
		target=searchFrameRecursive(top,"mainpage","top");
		if (!target) {
			return;
		}
	}
	var url="checkBarcodeAsync.php?"+getSelfRef()+"&barcode="+barcode+"&target="+target;
	// window.open(url);
	commFrameObj.location.href=url;
}

function down(e) {
	var key=getKey(e);
	// alert(key);
	timeStart=Number(new Date());
	if (key==27 || key==45) { // barcode, 27=ESC, 45=INS
		if (typeof table=="string" && table=="mpi_order") { // simple input of data, no specific action
			return true;
		}
		barcodeMode=true;
		outputCache="";
		resetModeTimeout=window.setTimeout(function () { cancelBarcodeMode(); },700); // nach 1s barcode-mode abbrechen
		if (e) e.preventDefault();
		return false;
	}
	else if (barcodeMode) { // barcode nicht eintippen lassen in felder, sondern abfangen
		if (e) e.preventDefault();
		return false;
	}
}

function cancelBarcodeMode() {
	if (resetModeTimeout) {
		window.clearTimeout(resetModeTimeout);
	}
	outputCache="";
	barcodeMode=false;
}

function up(e) {
	var key=getKey(e),timeDown=(Number(new Date())-timeStart);
	// alert(key);
	if (timeDown<40 && barcodeMode) { // barcode
		if (key==13 || key==10) { // barcode abschließen
			barcodeRead(outputCache);
			cancelBarcodeMode();
		}
		else if (key==27 || key==45) {
			// do nothing
		}
		else if (key==190) {
			outputCache+=".";
		}
		else if (key==16) {
			// skip
		}
		else {
			outputCache+=String.fromCharCode(key).toLowerCase();
			//~ outputCache+=key+" ";
		}
		if (e) e.preventDefault();
		return false;
	}
}

document.onkeydown=down;
document.onkeyup=up;

if (navigator.appName=="Microsoft Internet Explorer") {
	// IE sh*t
	document.write("<script for=document event=\"onkeydown()\" language=\"JScript\"> { return down(); }</scri"+"pt><script for=document event=\"onkeyup()\" language=\"JScript\"> { return up(); }</scri"+"pt>");
}