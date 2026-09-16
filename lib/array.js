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

function arr_safe(arr) {
	if (!is_array(arr)) {
		return [];
	}
	return arr;
}

function array_fill(start_index,num,value) {
	var retval=[];
	for (var b=start_index;b<num;b++) {
		if (is_array(value)) { // dereference
			value=value.concat();
		}
		retval.push(value);
	}
	return retval;
}

function in_array(value,arr) {
	if (!is_array(arr)) {
		return false;
	}
	//~ var str_value=String(value);
	for (var b=0,max=arr.length;b<max;b++) {
		//~ if (arr[b]==value || String(arr[b])==str_value) {
		if (arr[b]==value) {
			return true;
		}
	}
	return false;
}

function findInArray(arr,name,value) { // Unterschied zu array_search: name als Spaltenangabe
	// arr[0..n][name]==value
	if (!is_array(arr)) {
		return;
	}
	for (var b=0,max=arr.length;b<max;b++) {
		if (arr[b][name]==value) {
			return b;
		}
	}
}

function array_search(value,arr) { // returnj undefined
	if (!is_array(arr)) {
		return undefined;
	}
	for (var b=0,max=arr.length;b<max;b++) {
		if (arr[b]==value) {
			return b;
		}
	}
}

function array_unique(arr) {
	if (!is_array(arr)) {
		return [];
	}
	for (var b=arr.length-1;b>=0;b--) {
		for (var c=b-1;c>=0;c--) {
			if (arr[b]==arr[c]) {
				arr.splice(b,1);
				break; // wenn es weitere gleich gibt: irgendwann kommen wir noch mal vorbei, dann ist das aktuelle c b 
			}
		}
	}
	return arr;
}

function array_keys(arr) {
	if (!is_object(arr)) {
		return [];
	}
	var retval=[];
	for (var key in arr) {
		retval.push(key);
	}
	return retval;
}

function ksort(arr) { // like in PHP
	var temp_arr=[],keys=[];
	for (var key in arr) {
		keys.push(key);
	}
	keys.sort();
	for (var b=0,max=keys.length;b<max;b++) {
		key=keys[b];
		temp_arr[key]=arr[key];
		delete arr[key];
	}
	for (var b=0,max=keys.length;b<max;b++) {
		key=keys[b];
		arr[key]=temp_arr[key];
	}
}

function arr_merge(arr) { // further arguments!!
	arr=arr_safe(arr);
	for (var b=1,max=arr_merge.arguments.length;b<max;b++) {
		arr=arr.concat(arr_safe(arr_merge.arguments[b]));
	}
	return arr;
}

function array_diff(arr,arr1) {
	if (!is_array(arr)) {
		return [];
	}
	if (!is_array(arr1)) {
		return arr;
	}
	for (b=arr.length-1;b>=0;b--) {
		for (c=0,max2=arr1.length;c<max2;c++) {
			if (arr[b]==arr1[c]) {
				arr.splice(b,1);
				break;
			}
		}
	}
	return arr;
}