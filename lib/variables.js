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

function is_empty(obj) {
	for(var prop in obj) {
		if(obj.hasOwnProperty(prop)) return false;
	}
	return true;
}

function is_function(variable) {
	if (variable==undefined) {
		return false;
	}
	if (typeof variable=="function") {
		return true;
	}
	return false;
}

function is_function_or_object(variable) { // fix IE BS
	var func_types=["function","object"];
	if (variable==undefined) {
		return false;
	}
	if (in_array(typeof variable,func_types)) {
		return true;
	}
	return false;
}

function is_object(variable) {
	if (variable==undefined) {
		return false;
	}
	if (typeof variable!="object") {
		return false;
	}
	return true;
}

function is_array(variable) {
	if (variable==undefined) {
		return false;
	}
	if (typeof variable!="object") {
		return false;
	}
	if (variable.length==0) {
		return false;
	}
	return true;
}

function is_string(variable) {
	if (variable==undefined) {
		return false;
	}
	if (typeof variable!="string") {
		return false;
	}
	return true;
}

function is_numeric(num) {
	num=Number(num);
	return !isNaN(num);
}

function safeAdd(b,c) { // add 2 values if both are numeric, otherwise preserve value of either numeric parameter given
	b=parseFloat(b);
	c=parseFloat(c);
	if (isNaN(b) && isNaN(c)) {
		return "";
	}
	if (isNaN(b)) {
		return c;
	}
	if (isNaN(c)) {
		return b;
	}
	return b+c;
}

function as(arrayName,value) { // error tolerant array writing
	var idx,firstChar="";
	if (eval("typeof "+arrayName)=="undefined") {
		eval(arrayName+"=[]");
	}
	for (var b=2,max=as.arguments.length;b<max;b++) {
		if (eval(arrayName)==undefined) {
			eval(arrayName+"={}");
		}
		//~ if (!as.arguments[b] && as.arguments[b]!==0) {
		if (as.arguments[b]==undefined || as.arguments[b]==="" || as.arguments[b]==null) { // just ignore, for example group which is not set
			continue;
		}
		if (typeof as.arguments[b]=="string") {
			idx=fixStr(as.arguments[b]);
		}
		else {
			idx=as.arguments[b];
		}
		arrayName+="["+idx+"]";
	}
	switch (typeof value) {
	case "string":
		firstChar=value.charAt(0);
		if (firstChar!="[" && firstChar!="{") {
			value=fixStr(value);
		}
	break;
	case "function":
	case "object":
		value=JSON.stringify(value);
	break;
	}
	eval(arrayName+"="+value+"");
}

function a(thisArray) { // error tolerant array reading
	if (thisArray==undefined) {
		return "";
		// return undefined;
	}
	for (var b=1,max=a.arguments.length;b<max;b++) {
		//~ if (!a.arguments[b] && a.arguments[b]!==0) {
		if (a.arguments[b]==undefined || a.arguments[b]==="" || a.arguments[b]==null) { // just ignore, for example group which is not set
			continue;
		}
		if (thisArray[ a.arguments[b] ]!=undefined) {
			thisArray=thisArray[ a.arguments[b] ];
		}
		else {
			return "";
			// return undefined;
		}
	}
	return thisArray;
}

function ar(thisArray) { // error tolerant array reading
	if (thisArray==undefined) {
		return undefined;
	}
	for (var b=1,max=ar.arguments.length;b<max;b++) {
		//~ if (!ar.arguments[b] && ar.arguments[b]!==0) {
		if (ar.arguments[b]==undefined || ar.arguments[b]==="" || ar.arguments[b]==null) { // just ignore, for example group which is not set
			continue;
		}
		if (thisArray[ ar.arguments[b] ]!=undefined) {
			thisArray=thisArray[ ar.arguments[b] ];
		}
		else {
			return undefined;
		}
	}
	return thisArray;
}

function ac(thisArray) { // count
	if (thisArray==undefined) {
		return 0;
	}
	for (var b=1,max=ac.arguments.length;b<max;b++) {
		//~ if (!ac.arguments[b] && ac.arguments[b]!==0) {
		if (ac.arguments[b]==undefined || ac.arguments[b]==="" || ac.arguments[b]==null) { // just ignore, for example group which is not set
			continue;
		}
		if (thisArray[ ac.arguments[b] ]!=undefined) {
			thisArray=thisArray[ ac.arguments[b] ];
		}
		else {
			return 0;
		}
	}
	return thisArray.length;	
}