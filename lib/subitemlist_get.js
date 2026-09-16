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

// Abfragefunktionen für subitemlist

function SILgetRelativeGroup(list_int_name,int_name,group,delta) { // get previous or next group for a given one
	if (group==undefined || group==="") {
		return "";
	}
	if (delta==0) {
		return group;
	}
	var inc,lim,act_group=group,fields=SILgetFields(list_int_name),fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	if (delta>0) {
		inc=1;
		lim=fields.length;
	}
	else {
		inc=-1;
		lim=1;
	}
	for (var b=fieldIdx+inc;b*inc<lim && delta*inc>0;b+=inc) {
		act_group=SILgetGroupName(list_int_name,b);
		if (act_group!="" && act_group!=group) {
			group=act_group;
			delta-=inc;
		}
	}
	return group;
}

function SILgetDesiredAction(list_int_name,UID) { // SIL
	var obj=$("desired_action_"+list_int_name+"_"+UID);
	if (!obj) {
		return false;
	}
	return obj.value;
}

function SILgetFields(list_int_name,fieldIdx) { // fieldIdx is optional
	return a(controls,list_int_name,"fields",fieldIdx);
}

function SILgetField(list_int_name,int_name,group) { // SIL
	var fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	return a(controls,list_int_name,"fields",fieldIdx);
}

function SILgetFreeUID(list_int_name) { // SIL
	var UID=Number(new Date);
	while (SILgetPos(list_int_name,UID)!=undefined) { // make sure UID is unique
		UID++;
	}
	return UID;
}

function SILgetLineCount(list_int_name) {
	return controlData[list_int_name]["UIDs"].length;
}

// list_int_name_UID_int_name_group_part
function SILgetObjName(list_int_name,UID,int_name,group,thisReadOnly,part) { // SIL
	var retval="";
	if (thisReadOnly) {
		retval+="ro_";
	}
	retval+=list_int_name+"_"+UID+"_"+int_name;
	if (group || group===0) {
		retval+="_"+group;
	}
	if (part) {
		retval+="_"+part;
	}
	return retval;
}

function SILgetObjNameField(list_int_name,UID,fieldIdx) { // SIL
	var field=SILgetFields(list_int_name,fieldIdx);
	return SILgetObjName(list_int_name,UID,field["int_name"],field["group"]);
}

function SILgetObj(list_int_name,UID,int_name,group,thisReadOnly,part) { // SIL
	return $(SILgetObjName(list_int_name,UID,int_name,group,thisReadOnly,part));
}

function SILgetLineObj(list_int_name,UID,line,thisReadOnly) { // SIL, returns ONLY the 1st line of a dataset for inserting operations!!! not that easy anymore (multiple lines possible)
	var readOnlyText="";
	if (thisReadOnly) {
		readOnlyText="readOnly_";
	}
	if (line==undefined) {
		line=0;
	}
	return $("tr_"+readOnlyText+list_int_name+"_"+UID+"_"+line);
}

function SILgetPos(list_int_name,UID) { // SIL, macht aus UID pos
	if (UID==undefined) {
		return;
	}
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		if (controlData[list_int_name]["UIDs"][b]==UID) {
			return b;
		}
	}
}

function SILgetUID(list_int_name,pos) { // SIL, macht aus pos UID
	return controlData[list_int_name]["UIDs"][pos];
}

function SILgetChecked(list_int_name,UID,int_name,group) {
	var obj=SILgetObj(list_int_name,UID,int_name,group);
	if (obj) {
		return obj.checked;
	}
}

function SILgetValue(list_int_name,UID,int_name,group) { // SIL, zZt nur input/hidden, textarea und select, anderes vielleicht später ergänzen
	var obj=SILgetObj(list_int_name,UID,int_name,group);
	if (obj) {
		return obj.value;
	}
}

function SILgetSelectedIndex(list_int_name,UID,int_name,group) { // SIL, zZt nur input/hidden, textarea und select, anderes vielleicht später ergänzen
	var obj=SILgetObj(list_int_name,UID,int_name,group);
	if (obj) {
		var retval=obj.selectedIndex;
		if (retval>=0) { // -1 becomes undefined
			return retval;
		}
	}
}

function SILgetValueArray(list_int_name,int_name,group,skipEmpty) { // SIL
	var retval=[];
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var value=SILgetValue(list_int_name,controlData[list_int_name]["UIDs"][b],int_name,group);
		if (skipEmpty!=true || value!="") {
			retval.push(value);
		}
	}
	return retval;
}

function SILfindValue(list_int_name,int_name,group,value,skip,caseInsens) { // SIL
	if (!skip) {
		skip=0;
	}
	value=String(value);
	if (a(controlData,list_int_name,"UIDs")=="") {
		return false;
	}
	if (caseInsens==true) {
		value=value.toLowerCase();
	}
	for (var b=skip,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b],thisValue=SILgetValue(list_int_name,UID,int_name,group);
		if (typeof thisValue=="string") {
			if (caseInsens==true) {
				if (thisValue.toLowerCase()==value) {
					return UID;
				}
			}
			else if (thisValue==value) {
				return UID;
			}
		}
	}
	return false;
}

function SILgetFieldName(list_int_name,fieldIdx) {
	return a(controls,list_int_name,"fields",fieldIdx,"int_name");
}

function SILgetGroupName(list_int_name,fieldIdx) {
	return a(controls,list_int_name,"fields",fieldIdx,"group");
}

function SILgetFieldIndex(list_int_name,int_name,group) { // SIL
	var fields=SILgetFields(list_int_name);
	if (group==undefined || group==="" || group==null) {
		group=false;
	}
	for (var b=0,max=fields.length;b<max;b++) {
		if (fields[b]["int_name"]==int_name && (group===false || fields[b]["group"]==group)) {
			return b;
		}
	}
}

