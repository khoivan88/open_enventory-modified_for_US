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

// Hilfsfunktionen für subitemlist

selectControls=["select","pk_select"];

function SILhandleInputKey(e,list_int_name,UID,int_name,group) { // move cursor up and down in column
	var key=getKey(e),delta;
	//~ alert(key+" "+list_int_name+" "+UID+" "+int_name);
	switch (key) {
	case 38: // up
		delta=-1;
	break;
	case 40: // down
		delta=1;
	break;
	default:
		return;
	}
	var newUID=SILgetUID(list_int_name,SILgetPos(list_int_name,UID)+delta);
	if (newUID) {
		var fieldIdx=SILgetFieldIndex(list_int_name,int_name,group),part;
		if (a(controls,list_int_name,"fields",fieldIdx,"type")=="round") {
			part="rounded";
		}
		SILfocusControl(list_int_name,newUID,int_name,group,part);
	}
}

function SILgetJSitemParams(list_int_name,UID,int_name,group) {
	var retval=fixQuot(list_int_name)+","+fixQuot(UID)+","+fixQuot(int_name);
	if (group) {
		retval+=","+fixQuot(group);
	}
	return retval;
}

/*function getNodeFromHTML(id,innerHTML) { // helper function for IE table innerHTML bug
	var temp_obj=$("temp");
	temp_obj.innerHTML=innerHTML;
	var El=$(id);
	if (!El) {
		return null;
	}
	var newEl=El.cloneNode(true);
	temp_obj.innerHTML=""; // avoid double ids
	return newEl;	
}*/

// adds the objects within "innerHTML" by a list of id's "addIds" to the DOM object "addToObj", before the object "beforeObj"
function addNodesFromHTMLToObj(addToObj,beforeObj,addIds,innerHTML) {
	//~ var starttime=Number(new Date());
	if (!addToObj) {
		return;
	}
	
	var temp_obj=$("temp"),nodeArr=[];
	temp_obj.innerHTML=innerHTML;
	//~ alert("inner"+(Number(new Date())-starttime));
	//~ var starttime=Number(new Date());
	for (var b=0,max=addIds.length;b<max;b++) {
		var El=$(addIds[b]);
		if (El) {
			nodeArr[nodeArr.length]=El.cloneNode(true);
		}
	}
	temp_obj.innerHTML=""; // avoid double ids
	//~ alert(addIds);
	//~ alert("create"+(Number(new Date())-starttime));
	//~ var starttime=Number(new Date());
	for (var b=0,max=nodeArr.length;b<max;b++) {
		if (beforeObj) {
			addToObj.insertBefore(nodeArr[b],beforeObj);
		}
		else {
			addToObj.appendChild(nodeArr[b]);
		}
	}

	//~ alert("add"+(Number(new Date())-starttime));
}

function getLineIdArray(list_int_name,UID,thisReadOnly) {
	var retval=[];
	for (var b=0,max=a(controls,list_int_name,"lines");b<max;b++) {
		if (thisReadOnly==undefined || thisReadOnly==false) {
			retval[retval.length]="tr_"+list_int_name+"_"+UID+"_"+b;
		}
		if (thisReadOnly==undefined || thisReadOnly==true) {
			retval[retval.length]="tr_readOnly_"+list_int_name+"_"+UID+"_"+b;
		}
	}
	return retval;
}

function getVolatileValues(obj) {
	var retval=[];
	// go through childElements and push value
	for (var b=0,max=obj.childNodes.length;b<max;b++) {
		var node=obj.childNodes[b],tagName=node.tagName;
		if (!tagName) {
			continue;
		}
		// recursive
		retval=retval.concat(getVolatileValues(node));
		tagName=tagName.toLowerCase();
		if (tagName=="select") {
			retval.push(node.value);
		}
		else if (tagName=="textarea") {
			var id=node.id;
			if (document.getElementById("wysiwyg"+id)) {
				// wyzzed
				updateTextArea(id);
			}
			retval.push(node.value);
		}
	}
	return retval;
}

function setVolatileValues(obj,myValues) {
	// go through childElements and push value
	for (var b=0,max=obj.childNodes.length;b<max;b++) {
		var node=obj.childNodes[b],tagName=node.tagName;
		if (!tagName) {
			continue;
		}
		// recursive
		myValues=setVolatileValues(node,myValues);
		tagName=tagName.toLowerCase();
		if (tagName=="select") {
			node.value=myValues.shift();
		}
		else if (tagName=="textarea") {
			node.value=myValues.shift();
			var id=node.id;
			if (document.getElementById("wysiwyg"+id)) {
				// wyzzed
				updateIframe(id);
			}
		}
	}
	return myValues; // inform higher level about current select
}

function swapObjById(parentObj_id,obj1_id,obj2_id) { // does NOT check if the object stand in the correct relationship to each other
	var parentObj=$(parentObj_id),obj1=$(obj1_id),obj2=$(obj2_id);
	if (!parentObj || !obj1 || !obj2) {
		return false;
	}
	
	// sh*t workaround here, selected values of <select are not copied automatically
	
	// get values of selects
	var obj1_select=getVolatileValues(obj1);
	var obj2_select=getVolatileValues(obj2);

	parentObj.insertBefore(obj1.cloneNode(true),obj2);
	parentObj.replaceChild(obj2,obj1);
	
	// get new references
	var obj1=$(obj1_id),obj2=$(obj2_id);
	
	// write values of selects
	setVolatileValues(obj1,obj1_select);
	setVolatileValues(obj2,obj2_select);
	
	return true;
}

/* function swapObjById(parentObj_id,obj1_id,obj2_id) {
	return swapObj($(parentObj_id),$(obj1_id),$(obj2_id));
}*/

function SILgetSelectTextValue(list_int_name,UID,int_name,group,thisValue) {
	var fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	if (!fieldIdx) {
		return "";
	}
	if (!in_array(a(controls,list_int_name,"fields",fieldIdx,"item"),selectControls)) {
		return;
	}
	var int_names=a(controls,list_int_name,"fields",fieldIdx,"int_names"),texts=a(controls,list_int_name,"fields",fieldIdx,"texts");
	if (thisValue==undefined) {
		thisValue=SILgetValue(list_int_name,UID,int_name,group);
	}
	return getSelectText(int_names,texts,thisValue);
}