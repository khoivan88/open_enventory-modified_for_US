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

// folder browser

function FBgetFrameName(int_name) {
	return int_name+"_iframe";
}

function FBtextSearch(int_name) {
	var search=getInputValue("FBsearch_"+int_name),frameName=FBgetFrameName(int_name);
	if (search=="" || search==undefined) {
		return true;
	}
	var subframe=window.frames[frameName];
	var anker=subframe.document.anchors;
	// startswith
	for (var b=0,max=anker.length;b<max;b++) {
		if (startswith(anker[b].name,search)) {
			subframe.location.hash=anker[b].name;
			return true;
		}
	}
	// startswith case-insnesitive
	search=search.toLowerCase();
	for (var b=0,max=anker.length;b<max;b++) {
		var test=anker[b].name;
		test=test.toLowerCase()
		if (startswith(test,search)) {
			subframe.location.hash=anker[b].name;
			return true;
		}
	}
	// contains
	for (var b=0,max=anker.length;b<max;b++) {
		var test=anker[b].name;
		test=test.toLowerCase()
		if (test.indexOf(search)!=-1) {
			subframe.location.hash=anker[b].name;
			return true;
		}
	}
	return false;
}

function FBstartSearch(int_name,pk) {
	var url,frameName=FBgetFrameName(int_name);
	if (pk=="" || pk==undefined) {
		url=a(controls,int_name,"start_url");
	}
	else {
		url=a(controls,int_name,"search_url")+pk+"&int_name="+int_name;
		FBshowWaitMsg(int_name);
	}
	setFrameURL(frameName,url);
}

function FBshowWaitMsg(int_name) {
	setiHTML(int_name+"_message",s("please_wait"));
}

function FBclearWaitMsg(int_name) {
	setiHTML(int_name+"_message","");
}

function FBsetPath(int_name,path) {
	as("values",path,int_name);
	setControl(int_name,values);
}

