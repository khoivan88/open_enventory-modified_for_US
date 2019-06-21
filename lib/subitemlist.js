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

// combo box

function SILtoggleSelect(list_int_name,UID,int_name,group,visible) { // show/hide <select for combo
	// visible?
	if (visible==undefined) {
		visible=a(controlData,list_int_name,"toggleVisible",UID,int_name,group);
	}
	var id=SILgetObjName(list_int_name,UID,int_name,group,false,"div");
	if (visible) {
		visible=false;
		//~ hideObj(id);
	}
	else {
		visible=true;
		// div positionieren
		adjustComboSize(SILgetObj(list_int_name,UID,int_name,group,false,"rounded"),SILgetObj(list_int_name,UID,int_name,group,false,"select"),SILgetObj(list_int_name,UID,int_name,group,false,"div"));
	}
	visibleObj(id,visible);
	as("controlData",visible,list_int_name,"toggleVisible",UID,int_name,group);
}

function SILclickCombo(list_int_name,UID,int_name,group) { // chosen value from the list
	// get Text and value
	var id=SILgetObjName(list_int_name,UID,int_name,group,false,"select");
	var text=getSelectSelectedText(id),value=getInputValue(id);
	// set <inputs
	setInputValue(SILgetObjName(list_int_name,UID,int_name,group,false,"rounded"),text);
	setInputValue(SILgetObjName(list_int_name,UID,int_name,group),value);
	// hide div
	var visible=false;
	visibleObj(SILgetObjName(list_int_name,UID,int_name,group,false,"div"),visible);
	as("controlData",visible,list_int_name,"toggleVisible",UID,int_name,group);
}

// subitem list

function SILsetUnitSelect(list_int_name,UID,int_name,group,className,oldVal) {
	var classType=getClassType(className);
	var unit_list=getUnitsList(classType),fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	as("controls",unit_list,list_int_name,"fields",fieldIdx,"texts");
	as("controls",unit_list,list_int_name,"fields",fieldIdx,"int_names");
	SILPkSelectSetOptions(list_int_name,UID,int_name,group,oldVal);
	setiHTML("ro_"+SILgetObjName(list_int_name,UID,int_name,group),oldVal);
}

function SILlockElement(list_int_name,UID,pos,fieldIdx,int_name,group,locked) {
	if ((!UID && pos==undefined) || (!int_name && fieldIdx==undefined)) {
		return;
	}
	if (!UID) {
		UID=SILgetUID(list_int_name,pos);
	}
	if (pos==undefined) {
		pos=SILgetPos(list_int_name,UID);
	}
	if (!int_name) {
		int_name=SILgetFieldName(list_int_name,fieldIdx);
		group=SILgetGroupName(list_int_name,fieldIdx);
	}
	if (fieldIdx==undefined) {
		fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	}
	
	if (!int_name) {
		return;
	}
	
	var defaultLocked=a(controls,list_int_name,"fields",fieldIdx,DEFAULTLOCKED),item=a(controls,list_int_name,"fields",fieldIdx,"item");
	switch (defaultLocked) {
	case "always":
		thisLocked=true;
	break;
	case "never":
		thisLocked=false;
	break;
	default:
		thisLocked=locked;
	}
	switch (item) {
	case "check":
	case "checkbox":
	case "radio":
	case "pk_select":
	case "select":
		var obj=SILgetObj(list_int_name,UID,int_name,group);
		if (obj) {
			obj.disabled=(thisLocked?"disabled":"");
		}
	break;
	case "checkset":
		var int_names=a(controls,list_int_name,"fields",fieldIdx,"int_names");
		for (var c=0,max2=int_names.length;c<max2;c++) {
			var obj=SILgetObj(list_int_name,UID,int_name,group,false,int_names[c]);
			if (obj) {
				obj.disabled=(thisLocked?"disabled":"");
			}
		}
	break;
	case "input":
		var obj=SILgetObj(list_int_name,UID,int_name,group),type=a(controls,list_int_name,"fields",fieldIdx,"type");
		if (obj) {
			obj.disabled=(thisLocked?"disabled":"");
		}
		if (in_array(type,["round","combo"])) {
			var obj=SILgetObj(list_int_name,UID,int_name,group,false,"rounded");
			if (obj) {
				obj.disabled=(thisLocked?"disabled":"");
			}
		}
		if (type=="combo") {
			var obj=SILgetObj(list_int_name,UID,int_name,group,false,"select");
			if (obj) {
				obj.disabled=(thisLocked?"disabled":"");
			}
			visibleObj(SILgetObjName(list_int_name,UID,int_name,group,false,"button"),!thisLocked);
		}
	break;
	case "links":
		visibleObj(SILgetObjName(list_int_name,UID,"up"),!thisLocked);
		visibleObj(SILgetObjName(list_int_name,UID,"add_line"),!thisLocked);
		visibleObj(SILgetObjName(list_int_name,UID,"del"),!thisLocked);
		visibleObj(SILgetObjName(list_int_name,UID,"down"),!thisLocked);
	break;
	}
}

function SILlockLine(list_int_name,UID,locked) { // SIL
	var fields=SILgetFields(list_int_name);
	for (var b=0,max=fields.length;b<max;b++) {
		SILlockElement(list_int_name,UID,undefined,b,undefined,undefined,locked);
	}
}

function SILlockField(list_int_name,fieldIdx,int_name,group,thisLocked) { // SIL
	// hole alle UIDs für subitemlist
	if (!int_name) {
		int_name=SILgetFieldName(list_int_name,fieldIdx);
		group=SILgetGroupName(list_int_name,fieldIdx);
	}
	if (fieldIdx==undefined) {
		fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	}
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		SILlockElement(list_int_name,controlData[list_int_name]["UIDs"][b],b,fieldIdx,int_name,group,thisLocked);
	}
}

function SILlockSubitemlist(list_int_name,thisLocked) { // SIL
	// hole alle UIDs für subitemlist
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		SILlockLine(list_int_name,controlData[list_int_name]["UIDs"][b],thisLocked);
	}
}

function SILmayAddLine(list_int_name) {
	if (a(controls,list_int_name,"noManualAdd")) {
		return false;
	}
	var maxLines=a(controls,list_int_name,"maxLines");
	
	// check if maxLines is exceeded
	if (maxLines!=="" && controlData[list_int_name]["UIDs"].length>=maxLines) {
		return false;
	}
	return true;
}

function SILfocusControl(list_int_name,UID,int_name,group,part) {
	focusInput(SILgetObjName(list_int_name,UID,int_name,group,false,part));
}

function SILfocusLine(list_int_name,UID) { // geht irgendwie nicht
	location.hash=list_int_name+"_"+UID;
}

function SILmanualAddLine(list_int_name,beforeUID,desired_action) { // SIL
	var UID=SILgetFreeUID(list_int_name),retval;
	
	if (SILmayAddLine(list_int_name)==false) {
		return false;
	}
	
	// onBeforeAddLine, return false cancels
	if (is_function(controls[list_int_name]["onBeforeAddLine"])) {
		if (controls[list_int_name]["onBeforeAddLine"](list_int_name,UID,beforeUID)==false) {
			// cancel
			return false;
		}
	}

	retval=SILaddLineBackend(list_int_name,UID,beforeUID,desired_action);
	
	var pos=SILgetPos(list_int_name,UID);
	
	// manual addition of line
	if (is_function(controls[list_int_name]["onAddLine"])) {
		controls[list_int_name]["onAddLine"](list_int_name,UID,pos,desired_action);
	}
	
	return retval;
}

function SILmanualAddLineMultiple(num,list_int_name,beforeUID,desired_action) {
	for (var b=0;b<num;b++) {
		SILmanualAddLine(list_int_name,beforeUID,desired_action);
	}
}

function SILaddLine(list_int_name,beforeUID,desired_action,quick) { // SIL
	var UID=SILgetFreeUID(list_int_name);
	return SILaddLineBackend(list_int_name,UID,beforeUID,desired_action,quick);
}

function SILaddLineBackend(list_int_name,UID,beforeUID,desired_action,quick) { // SIL
	var beforeObj,beforeObjReadOnly,pos;
	
	if (desired_action==undefined) {
		desired_action="add";
	}
	
	// alert(desired_action);
	// update Array
	pos=SILgetPos(list_int_name,beforeUID);
	if (pos==undefined) {
		pos=controlData[list_int_name]["UIDs"].length;
		beforeObj=null;
	}
	else {
		beforeObj=SILgetLineObj(list_int_name,beforeUID,0);
		beforeObjReadOnly=SILgetLineObj(list_int_name,beforeUID,0,true);
	}
	controlData[list_int_name]["UIDs"].splice(pos,0,UID);
	controlData[list_int_name]["hidden"].splice(pos,0,false);
	// Ersatz für oben
	// IE sh** workaround
	// folgendes probieren: 1. Eine Tabelle mit nur dieser einen Zeile erstellen, 2. klon=cloneNode(true) für Zeile 3. insertBefore(klon,beforeObj)
	
	addNodesFromHTMLToObj($("tbody_"+list_int_name),beforeObj,getLineIdArray(list_int_name,UID,false),"<table><tbody><tr id=\"tr_"+list_int_name+"_"+UID+"_0\">"+controls[list_int_name]["getLine"](UID)+"</tr></tbody></table>");
	addNodesFromHTMLToObj($("tbody_readOnly_"+list_int_name),beforeObjReadOnly,getLineIdArray(list_int_name,UID,true),"<table><tbody><tr id=\"tr_readOnly_"+list_int_name+"_"+UID+"_0\">"+controls[list_int_name]["getLineReadOnly"](UID)+"</tr></tbody></table>");
	
	if (desired_action!="") { // performance
		SILsetDesiredAction(list_int_name,UID,desired_action,true); // force, otherwise subitems of new datasets are missing
	}
	
	if (!quick) {
		if (a(controls,list_int_name,"allowReorder")) {
			SILupdateOrderButtons(list_int_name); // disable for setAllValues!
		}
		SILupdateAddButtons(list_int_name);
	}

	// init
	if (is_function(controls[list_int_name]["lineInitFunction"])) {
		controls[list_int_name]["lineInitFunction"](list_int_name,UID,pos,desired_action);
	}
	
	if (desired_action=="add") {
		valChanged();
	}
	
	return UID;
}

function SILupdateVisible(list_int_name,UID) {
	var fields=SILgetFields(list_int_name),int_name,group;
	for (var b=0,max=fields.length;b<max;b++) {
		var visible=a(controls,list_int_name,"fields",b,VISIBLE);
		if (visible || visible===false) {
			int_name=SILgetFieldName(list_int_name,b);
			group=SILgetGroupName(list_int_name,b);
			//~ var int_name=a(controls,list_int_name,"fields",b,"int_name");
			visibleObj(SILgetObjName(list_int_name,UID,int_name,group),visible);
			visibleObj(SILgetObjName(list_int_name,UID,int_name,group,true),visible);
		}
	}
}

function SILsort(list_int_name,int_name,group,mode) { // 1: desc (else asc), 2: num (else alpha)
	if (mode==undefined) {
		mode=0;
	}
	var fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	
	for (var b=0;b<controlData[list_int_name]["UIDs"].length;b++) { // bubble-sort, enough for here
		for (var c=b+1;c<controlData[list_int_name]["UIDs"].length;c++) {
			var UID_oben=SILgetUID(list_int_name,b),UID_unten=SILgetUID(list_int_name,c),item=a(controls,list_int_name,"fields",fieldIdx,"item");
			if (in_array(item,selectControls)) {
				var wert_oben=SILgetSelectTextValue(list_int_name,UID_oben,int_name,group),wert_unten=SILgetSelectTextValue(list_int_name,UID_unten,int_name,group);
				//~ alert("X"+wert_oben+"Y"+wert_unten+"Z");
			}
			else {
				var wert_oben=SILgetValue(list_int_name,UID_oben,int_name,group),wert_unten=SILgetValue(list_int_name,UID_unten,int_name,group);
			}
			if ((mode & 2)==2) {
				// numerical comparison
				wert_oben=parseFloat(wert_oben),wert_unten=parseFloat(wert_unten);
			}
			if ( ((wert_oben>wert_unten) && ((mode & 1)==0)) || ((wert_oben<wert_unten) && ((mode & 1)==1)) ) {
				SILswapLines(list_int_name,UID_oben,UID_unten,true);
			}
		}
	}
	SILupdateOrderButtons(list_int_name);
}

function SILresetControlData(list_int_name) { // SIL
	controlData[list_int_name]=[];
	controlData[list_int_name]["UIDs"]=[];
	controlData[list_int_name]["hidden"]=[];
	controlData[list_int_name]["data"]=[];
	controlData[list_int_name]["old"]=[]; // mark dummy lines
}

function SILclear(list_int_name) { // SIL
	// IE sh*t
	clearChildElements("tbody_"+list_int_name);
	clearChildElements("tbody_readOnly_"+list_int_name);
	SILresetControlData(list_int_name);
	valChanged();
}

function SILsetDesiredAction(list_int_name,UID,desired_action,force) { // SIL
	if (updateInProgress==true && force!=true) { // avoid unnecessary updates
		return false;
	}
	var obj=$("desired_action_"+list_int_name+"_"+UID);
	if (!obj) {
		return false;
	}
	obj.value=desired_action;
	return true;
}

function SILprepareSubmit(list_int_name) { // SIL, über UIDs und fields iterieren und postProc(int_id,action) aufrufen
	var fields=SILgetFields(list_int_name);
	for (var c=0,max2=controlData[list_int_name]["UIDs"].length;c<max2;c++) {
		var UID=controlData[list_int_name]["UIDs"][c];
		for (var b=0,max=fields.length;b<max;b++) {
			var int_id=SILgetObjNameField(list_int_name,UID,b),elementType=a(fields,b,"item");
			
			switch (elementType) { // enable disabled elements
			case "check":
			case "checkbox":
			case "pk_select":
			case "radio":
			case "select":
				postProc(int_id,"");
			break;
			case "input":
				var type=a(fields,b,"type"),elPostProc=a(fields,b,"postProc");
				switch (type) {
				case "date":
				case "percent":
				case "range":
					postProc(int_id,type);
				break;
				case "textarea": // wyzz
					updateTextArea(int_id);
				break;
				}
				postProc(int_id,elPostProc);
			break;
			case "checkset":
				var int_name=a(fields,b,"int_name"),group=a(fields,b,"group"),int_names=a(controls,list_int_name,"fields",b,"int_names");
				for (var c=0,max2=int_names.length;c<max2;c++) {
					var obj=SILgetObj(list_int_name,UID,int_name,group,false,int_names[c]);
					if (obj) {
						obj.disabled="";
					}
				}
			break;
			}
		}
	}
}

function SILremoveLineData(list_int_name,UID) { // SIL, entfernt daten zu line
	var pos=SILgetPos(list_int_name,UID);
	if (pos==undefined) {
		return false;
	}

	controlData[list_int_name]["UIDs"].splice(pos,1);
	controlData[list_int_name]["hidden"].splice(pos,1);
	
	delete controlData[list_int_name]["data"][UID];
	delete controlData[list_int_name]["old"][UID];
}

function SILremoveLine(list_int_name,UID) { // SIL, entfernt dummy line spurlos
	SILremoveLineData(list_int_name,UID);
	var lineIds=getLineIdArray(list_int_name,UID);
	for (var b=0;b<lineIds.length;b++) {
		removeId(lineIds[b]);
	}
	/* var obj=SILgetLineObj(list_int_name,UID);
	if (!obj) {
		return false;
	}
	obj.parentNode.removeChild(obj); */
	if (a(controls,list_int_name,"allowReorder")) {
		SILupdateOrderButtons(list_int_name);
	}
	SILupdateAddButtons(list_int_name);
}

function SILmanualDelLine(list_int_name,UID) { // SIL
	var pos=SILgetPos(list_int_name,UID);
	if (is_function(controls[list_int_name]["onBeforeDelete"])) {
		if (controls[list_int_name]["onBeforeDelete"](list_int_name,UID,pos)==false) {
			return false;
		}
	}
	SILdelLine(list_int_name,UID);
	if (is_function(controls[list_int_name]["lineDelFunction"])) {
		controls[list_int_name]["lineDelFunction"](list_int_name,UID,pos);
	}
	SILupdateAddButtons(list_int_name);
}

function SILsetData(list_int_name,UID,name,value) { // store values
	as("controlData",value,list_int_name,"data",UID,name);
}

function SILgetData(list_int_name,UID,name) { // retrieve stored values
	return a(controlData,list_int_name,"data",UID,name);
}

function SILdispLine(list_int_name,UID,thisReadOnly,visible) { // SIL
	var max,lineIds=getLineIdArray(list_int_name,UID,thisReadOnly); // thisReadOnly=undefined: alles
	if (SILgetData(list_int_name,UID,"img_visible")===false) { // show only one
		max=2; // ro,rw
	}
	else {
		max=lineIds.length;
	}
	for (var b=0;b<max;b++) {
		visibleObj(lineIds[b],visible);
	}
	SILupdateDynamicFields(list_int_name);
}

function SILhideLine(list_int_name,UID,thisReadOnly) { // SIL
	SILdispLine(list_int_name,UID,thisReadOnly,false);
}

function SILshowLine(list_int_name,UID,thisReadOnly) { // SIL
	SILdispLine(list_int_name,UID,thisReadOnly,true);
}

function SILdelLine(list_int_name,UID) { // SIL, versteckt die Zeile und setzt desired_action auf del
	SILremoveLineData(list_int_name,UID);
	// del item
	if (a(controls,list_int_name,"directDelete")) {
		SILremoveLine(list_int_name,UID);
		SILupdateDynamicFields(list_int_name);
	}
	else {
		SILhideLine(list_int_name,UID);
		SILsetDesiredAction(list_int_name,UID,"del");
		if (a(controls,list_int_name,"allowReorder")) {
			SILupdateOrderButtons(list_int_name);
		}
		else {
			SILupdateDynamicFields(list_int_name);
		}
		SILupdateAddButtons(list_int_name);
	}
	valChanged();
	//~ alert("C"+controlData[list_int_name]["UIDs"].join(";"));
	return true;
}

function SILprepareSetAll(list_int_name,int_name,group) {
	var newVal=prompt(s("enter_value"));
	if (newVal==null) {
		return;
	}
	SILsetValuesField(list_int_name,int_name,group,newVal); // desired_action auf update setzen, dürfte zwar egal sein, aber systematisch richtig
}

function SILsetValuesField(list_int_name,int_name,group,value) {
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b];
		if (SILgetValue(list_int_name,UID,int_name,group)!=value) { // is change necessary
			SILsetValue(value,list_int_name,UID,int_name,group);
			if (SILgetDesiredAction(list_int_name,UID)=="") { // "del" should not be changed to "update"
				SILsetDesiredAction(list_int_name,UID,"update");
			}
			SILObjTouchOnchange(list_int_name,UID,int_name,group);
		}
	}
}

function SILsetFieldVisible(list_int_name,int_name,group,visible) {
	var fieldIndex=SILgetFieldIndex(list_int_name,int_name,group);
	as("controls",visible,list_int_name,"fields",fieldIndex,VISIBLE);
	// thead
	visibleObj(SILgetObjName(list_int_name,"headline",int_name,group),visible);
	visibleObj(SILgetObjName(list_int_name,"headline",int_name,group,true),visible);
	// tbody
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b];
		visibleObj(SILgetObjName(list_int_name,UID,int_name,group),visible);
		visibleObj(SILgetObjName(list_int_name,UID,int_name,group,true),visible);
	}
}

function SILObjTouchOnchange(list_int_name,UID,int_name,group,part) {
	touchOnChange(SILgetObjName(list_int_name,UID,int_name,group,false,part));
}

function SILfieldTouchOnchange(list_int_name,int_name,group) {
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		touchOnChange(SILgetObjName(list_int_name,controlData[list_int_name]["UIDs"][b],int_name,group));
	}
}

function SILsetChecked(checked,list_int_name,UID,int_name,group) {
	var objRw=SILgetObj(list_int_name,UID,int_name,group),objRo=SILgetObj(list_int_name,UID,int_name,group,true);
	if (objRw) {
		objRw.checked=checked;
	}
	if (objRo) {
		objRo.checked=checked;
	}
}

function SILsetValueUID(list_int_name,UID,pos,fieldIdx,int_name,group,values) { // SIL
	if ((!UID && pos==undefined) || (!int_name && fieldIdx==undefined)) {
		return;
	}
	if (!UID) {
		UID=SILgetUID(list_int_name,pos);
	}
	if (pos==undefined) {
		pos=SILgetPos(list_int_name,UID);
	}
	if (!int_name) {
		int_name=SILgetFieldName(list_int_name,fieldIdx);
		group=SILgetGroupName(list_int_name,fieldIdx);
	}
	if (fieldIdx==undefined) {
		fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	}
	var fieldArr=a(controls,list_int_name,"fields",fieldIdx);
	if (!fieldArr) {
		return;
	}
	
	// Alle Daten da
	var value=a(values,group,int_name),db_id=a(values,"db_id"),displayValue="",nameRw=SILgetObjName(list_int_name,UID,int_name,group),nameRo=SILgetObjName(list_int_name,UID,int_name,group,true),item=a(fieldArr,"item");
	// SILsetValue(list_int_name,idx,pos,values);
	switch (item) {
	case "check":
	case "checkbox":
		setChecked(nameRw,value);
		setChecked(nameRo,value);
	break;
	case "checkset":
		var int_names=a(fieldArr,"int_names"),shift=def0(a(fieldArr,"shift")),roList=a(fieldArr,"roList"),texts=a(fieldArr,"texts"),images=a(fieldArr,"images"),width=a(fieldArr,"width"),height=a(fieldArr,"height"),value_text="",value_arr;
		if (!is_numeric(value)) {
			// comma-separated list of texts
			value_arr=value.split(",");
		}
		for (var b=0,max=int_names.length;b<max;b++) {
			var obj=$(nameRw+"_"+int_names[b]),value_obj=$("value_"+nameRw+"_"+int_names[b]),isChecked;
			if (value_arr) {
				isChecked=in_array(int_names[b],value_arr);
			}
			else {
				var mask=Math.pow(2,b+shift);
				isChecked=((value&mask)==mask);
			}
			
			if (obj) {
				obj.checked=isChecked;
			}
			if (roList && isChecked) {
				if (images) {
					// series of images
					value_text+="<img src="+fixStr("lib/"+images[b])+getHTMLAttrib("width",width)+getHTMLAttrib("height",height)+getTooltipP(texts[b])+"/>";
				}
				else {
					// separate by commas
					if (value_text!="") {
						value_text+=", ";
					}
					value_text+=texts[b];
				}
			}
			else if (value_obj) {
				value_obj.checked=isChecked;
			}
		}
		if (roList) {
			setiHTML(nameRo,value_text);
		}
		
	break;
	case "hidden":
		setInputValue(nameRw,value);
	break;
	case "input":
		var type=a(fieldArr,"type");
		switch (type) {
		case "range":
			value=formatRangeForSQL(a(values,group,int_name+"_low"),a(values,group,int_name+"_high"));
		break;
		case "date":
			value=toGerDate(value);
		break;
		case "percent":
			displayValue=value+"%";
		break;
		}
		
		if (displayValue=="") {
			displayValue=value;
		}
		if (is_function(controls[list_int_name]["fields"][fieldIdx]["handleDisplay"])) {
			displayValue=controls[list_int_name]["fields"][fieldIdx]["handleDisplay"](list_int_name,UID,pos,fieldIdx,int_name,group,displayValue);
		}
		if (controls[list_int_name]["fields"][fieldIdx][DEFAULTREADONLY]) {
			setiHTML("value_"+nameRw,displayValue);
		}
		setInputValue(nameRw,value);
		if (type=="textarea") {
			updateIframe(nameRw);
		}
		else if (type=="combo") {
			// get Text for value
			var int_names=a(fieldArr,"int_names"),displayValue=value; // in case nothing is found
			for (var b=0,max=int_names.length;b<max;b++) {
				if (int_names[b]==value) {
					displayValue=a(fieldArr,"texts",b);
					break;
				}
			}
			setInputValue(SILgetObjName(list_int_name,UID,int_name,group,false,"rounded"),displayValue);
		}
		if (type=="round") {
			var dec=a(fieldArr,"decimals"),roundMode=a(fieldArr,"roundMode");
			setiHTML(nameRo,round(displayValue,dec,roundMode));
			setInputValue(SILgetObjName(list_int_name,UID,int_name,group,false,"rounded"),round(value,dec,roundMode,true));
		}
		else {
			setiHTML(nameRo,nl2br(displayValue));
		}
	break;
	case "js":
		var functionBody=a(fieldArr,"functionBody");
		value=eval(functionBody);
		if (value!=undefined) {
			setiHTML(nameRo,value);
		}
		if (value!=undefined) {
			setiHTML(nameRw,value);
		}
	break;
	case "line_number":
		var lineNum=pos,summand=a(fieldArr,"summand");
		lineNum+=summand;
		if (a(fieldArr,"useLetter")) {
			lineNum=numToLett(lineNum);
		}
		setInputValue(nameRw,pos+summand);
		setiHTML("span_"+nameRw,lineNum);
		setiHTML(nameRo,lineNum); 
	break;
	case "pk_select":
	case "select":
		setInputValue(nameRw,value);
		setiHTML(nameRo,SILgetSelectTextValue(list_int_name,UID,int_name,group,value));
	break;
	case "structure":
		setInputValue(nameRw,addPipes(value));
		var pkName=a(fieldArr,"pkName"); // ,pkField=a(fieldArr,"pkField");
		var params=getImgParams(pkName,db_id,a(values,group,pkName),"",a_timestamp);
		SILimgSetParams(list_int_name,UID,int_name,group,params);
		var gif=getImgURL(params),useSvg=a(fieldArr,"useSvg");
		setImgSrc(nameRw+"_img",gif,useSvg);
		setImgSrc(nameRo+"_img",gif,useSvg);
	break;
	default:
		if (is_function(controls[list_int_name]["fields"][fieldIdx]["setFunction"])) {
			controls[list_int_name]["fields"][fieldIdx]["setFunction"](list_int_name,UID,pos,fieldIdx,int_name,group,values);
		}
	}
}

function SILsetValuesUID(list_int_name,UID,pos,values,group) { // SIL
	if (!UID && pos==undefined) {
		return;
	}
	if (!UID) {
		UID=SILgetUID(list_int_name,pos);
	}
	if (pos==undefined) {
		pos=SILgetPos(list_int_name,UID);
	}
	for (var key in values) {
		SILsetValueUID(list_int_name,UID,pos,undefined,key,group,values);
	}
}

function SILimgGetParams(list_int_name,UID,int_name,group) {
	return a(controlData,list_int_name,"data",UID,int_name,group,"params");
}

function SILimgSetParams(list_int_name,UID,int_name,group,params) {
	as("controlData",params,list_int_name,"data",UID,int_name,group,"params");
}

function SILsetValues(list_int_name,UID,pos,values) { // SIL, setzt die Zeile an pos auf die Werte values
	if (!values || (!UID && pos==undefined)) {
		return;
	}
	if (!UID) {
		UID=SILgetUID(list_int_name,pos);
	}
	if (pos==undefined) {
		pos=SILgetPos(list_int_name,UID);
	}
	//~ if (is_function(controls[list_int_name]["prepareData"])) {
		//~ if (controls[list_int_name]["prepareData"].length==4) { // prepareData for ordinary control has 2 arguments, is handled separately by controls
			//~ values=controls[list_int_name]["prepareData"](list_int_name,UID,pos,values);
		//~ }
	//~ }
	var fields=SILgetFields(list_int_name);
	for (var b=0,max=fields.length;b<max;b++) {
		if (!fields[b]["int_name"]) {
			continue;
		}
		SILsetValueUID(list_int_name,UID,pos,b,fields[b]["int_name"],fields[b]["group"],values);
	}
	// controls[list_int_name]["setFunction"](list_int_name,UID,values);
	if (is_function(controls[list_int_name]["setFunction"])) {
		controls[list_int_name]["setFunction"](list_int_name,UID,pos,values);
	}
	SILlockLine(list_int_name,UID,a(values,READONLY));
	as("controlData",a(values,READONLY),list_int_name,LOCKED,UID);
	valChanged();
}

function SILvisibleObj(list_int_name,UID,int_name,group,visible) {
	visibleObj(SILgetObjName(list_int_name,UID,int_name,group),visible);
	visibleObj(SILgetObjName(list_int_name,UID,int_name,group,true),visible);
}

function performToggle(list_int_name,UID,int_name) { // no group
	var name="img_visible",UIDs,visible,defaultVisible;
	
	visible=(int_name=="expand");
	
	if (UID=="" || UID==undefined) {
		UIDs=a(controlData,list_int_name,"UIDs");
		defaultVisible=true;
		// show buttons in headline
		UID="";
		SILvisibleObj(list_int_name,UID,"expand",!visible);
		SILvisibleObj(list_int_name,UID,"collapse",visible);
	}
	else {
		UIDs=[UID];
	}
	
	for (var c=0,max2=UIDs.length;c<max2;c++) {
		UID=UIDs[c];
		
		SILsetData(list_int_name,UID,name,visible); // required to keep setting when filters are applied/reset
		
		SILvisibleObj(list_int_name,UID,"expand",undefined,!visible);
		SILvisibleObj(list_int_name,UID,"collapse",undefined,visible);
		
		if (defaultVisible) {
			if (controlData[list_int_name]["hidden"][c]) {
				continue;
			}
		}
		else { // toggle
			// show buttons in headline
			if (visible) {
				SILvisibleObj(list_int_name,"headline","collapse",undefined,true);
			}
			else {
				SILvisibleObj(list_int_name,"headline","expand",undefined,true);
			}
		}
		
		var lineIds=getLineIdArray(list_int_name,UID,false);
		for (var b=1,max=lineIds.length;b<max;b++) { // 2. und folgende Zeilen
			visibleObj(lineIds[b],visible);
		}
		
		var lineIds=getLineIdArray(list_int_name,UID,true);
		for (var b=1,max=lineIds.length;b<max;b++) { // 2. und folgende Zeilen
			visibleObj(lineIds[b],visible);
		}
	}
}

function SILsetAllValues(list_int_name,desired_action,allValues,noOverwrite) { // SIL
	// boost performance in LJ
	var contArr=a(controls,list_int_name);
	if (noOverwrite!=true && archive_entity==undefined && ((readOnly==true && !a(contArr,VISIBLE)) || a(contArr,"alreadyLoaded"))) {
		return;
	}
	// end boost
	if (noOverwrite!=true) {
		SILclear(list_int_name);
	}
	//~ alert("B"+JSON.stringify(allValues));
	if (allValues) {
		// avoid reflows
		hideObj("ro_"+list_int_name);
		hideObj("rw_"+list_int_name);
		
		for (var b=0,max=allValues.length;b<max;b++) {
			var skipDataset=false;
			if (noOverwrite==true) {
				// check using unique_fields if already there
				// goto through existing entries and check if there is one with all unique fields identical, may be slow!!
				var unique_fields=a(contArr,"unique_fields"),values=a(allValues,b);
				if (unique_fields!="") {
					// go through list
					for (var c=0,max2=controlData[list_int_name]["UIDs"].length;c<max2;c++) {
						var skipLine=false,UID=controlData[list_int_name]["UIDs"][c];
						
						// go through unique_fields
						for (var d=0,max3=unique_fields.length;d<max3;d++) {
							var fieldName=unique_fields[d],value1;
							var fieldIdx=SILgetFieldIndex(list_int_name,fieldName),value2=SILgetValue(list_int_name,UID,fieldName);
							var type=a(contArr,"fields",fieldIdx,"type");
							if (type=="range") {
								value1=formatRangeForSQL(a(values,fieldName+"_low"),a(values,fieldName+"_high"));
							}
							else {
								value1=defBlank(a(values,fieldName))
							}
							if (value1!=value2) {
								skipLine=true;
								break;
							}
						}
						// dublette gefunden
						if (skipLine==false) {
							skipDataset=true;
							break;
						}
					}
				}
			}
			if (skipDataset==false) {
				var newUID=SILaddLine(list_int_name,null,desired_action,true); // quick
				SILsetValues(list_int_name,newUID,undefined,allValues[b]);
			}
		}
		
		if (a(contArr,"allowReorder")) {
			SILupdateOrderButtons(list_int_name); // update at the end, saves time
		}
		if (a(contArr,"allowCollapse")) {
			SILvisibleObj(list_int_name,"headline","collapse",undefined,true);
			SILvisibleObj(list_int_name,"headline","expand",undefined,false);
		}
		SILupdateAddButtons(list_int_name);
	}
	as("controls",true,list_int_name,"alreadyLoaded");
	if (!a(contArr,READONLY)) {
		as("controls",true,list_int_name,"alreadyLoadedRw");
	}
	updateMolecules();
}

function SILupdateAddButtons(list_int_name) { // SIL
	var mayAddLine=SILmayAddLine(list_int_name);
	visibleObj("add_button_"+list_int_name,mayAddLine);
}

function SILupdateOrderButtons(list_int_name) { // SIL
	var mayAddLine=SILmayAddLine(list_int_name);
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b];
		visibleObj(SILgetObjName(list_int_name,UID,"up"),(b>0));
		visibleObj(SILgetObjName(list_int_name,UID,"down"),(b<max-1));
		visibleObj(SILgetObjName(list_int_name,UID,"add_line"),mayAddLine);
	}
	SILupdateDynamicFields(list_int_name);
}

function SILswapLines(list_int_name,UID1,UID2,quick) { // SIL
	if (UID1==UID2) { // unnecessary
		return true;
	}
	var lineObj1=getLineIdArray(list_int_name,UID1,false),lineObj2=getLineIdArray(list_int_name,UID2,false);
	if (lineObj1.length!=lineObj2.length) {
		return false;
	}
	for (var b=0,max=lineObj1.length;b<max;b++) {
		swapObjById("tbody_"+list_int_name,lineObj1[b],lineObj2[b]);
	}
	var lineObj1=getLineIdArray(list_int_name,UID1,true),lineObj2=getLineIdArray(list_int_name,UID2,true);
	if (lineObj1.length!=lineObj2.length) {
		return false;
	}
	for (var b=0,max=lineObj1.length;b<max;b++) {
		swapObjById("tbody_readOnly_"+list_int_name,lineObj1[b],lineObj2[b]);
	}
	var pos1=SILgetPos(list_int_name,UID1),pos2=SILgetPos(list_int_name,UID2);
	controlData[list_int_name]["UIDs"][pos1]=UID2;
	controlData[list_int_name]["UIDs"][pos2]=UID1;
	
	// swap hidden BOOLS
	var temp=controlData[list_int_name]["hidden"][pos2];
	controlData[list_int_name]["hidden"][pos2]=controlData[list_int_name]["hidden"][pos1];
	controlData[list_int_name]["hidden"][pos1]=temp;
	if (quick!=true)  { // dont update while sorting
		SILupdateOrderButtons(list_int_name);
	}
	
	// set update in both lines
	SILsetDesiredAction(list_int_name,UID1,"update");
	SILsetDesiredAction(list_int_name,UID2,"update");
	
	valChanged();
	return true;
}

function SILmove(list_int_name,UID,delta) { // SIL
	// get Pos
	var pos=SILgetPos(list_int_name,UID);
	// get 2nd UID
	var UID2=controlData[list_int_name]["UIDs"][pos+delta];
	// swap
	SILswapLines(list_int_name,UID,UID2);
}

function SILupdateField(list_int_name,fieldIdx) { // SIL, currently used for line_number
	if (fieldIdx==undefined) {
		return;
	}
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		SILsetValueUID(list_int_name,undefined,b,fieldIdx);
	}
}

function SILupdateDynamicFields(list_int_name) {
	var fields=SILgetFields(list_int_name);
	for (var b=0,max=fields.length;b<max;b++) {
		switch (fields[b]["item"]) {
		case "line_number":
			SILupdateField(list_int_name,b);
		break;
		}
	}
}

function SILupdateOnListReordered(list_int_name) {
	if (is_function(controls[list_int_name]["onListReordered"])) {
		controls[list_int_name]["onListReordered"](list_int_name);
	}
}

function SILmoveUp(list_int_name,UID) { // SIL
	SILmove(list_int_name,UID,-1);
	SILupdateOnListReordered(list_int_name);
}

function SILmoveDown(list_int_name,UID) { // SIL
	SILmove(list_int_name,UID,1);
	SILupdateOnListReordered(list_int_name);
}

function SILsetValue(value,list_int_name,UID,int_name,group) { // SIL
	var obj=SILgetObj(list_int_name,UID,int_name,group),fieldIndex=SILgetFieldIndex(list_int_name,int_name,group);
	if (!obj) {
		return;
	}
	obj.value=value;
	if (a(controls,list_int_name,"fields",fieldIndex,"type")=="round") {
		var rounded=SILgetObj(list_int_name,UID,int_name,group,false,"rounded");
		if (!rounded) {
			return;
		}
		var decimals=a(controls,list_int_name,"fields",fieldIndex,"decimals"),roundMode=a(controls,list_int_name,"fields",fieldIndex,"roundMode");
		var rounded_value=round(value,decimals,roundMode);
		if (rounded_value==undefined) {
			rounded_value=value;
		}
		rounded.value=rounded_value;
	}
}

function SILchanged(list_int_name,UID,int_name,group) { // SIL
	var fieldIndex=SILgetFieldIndex(list_int_name,int_name,group);
	var type=a(controls,list_int_name,"fields",fieldIndex,"type");
	if (type=="round" || type=="combo") { // round has different input
		rw=SILgetObj(list_int_name,UID,int_name,group,false,"rounded");
		if (!rw) {
			return;
		}
		var obj=SILgetObj(list_int_name,UID,int_name,group);
		if (obj) {
			obj.value=rw.value;
		}
	}
}

function SILeval(list_int_name,UID,int_name,group) { // SIL
	// not compatible with combo, makes no sense to me
	var rw=SILgetObj(list_int_name,UID,int_name,group),fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	var type=a(controls,list_int_name,"fields",fieldIdx,"type"),doEval=a(controls,list_int_name,"fields",fieldIdx,"doEval");
	if (type=="round") { // round has different input
		rw=SILgetObj(list_int_name,UID,int_name,group,false,"rounded");
	}
	if (!rw) {
		return;
	}
	// doEval
	if (doEval) {
		// replace , by . only simple calculations
		var rw_value=evalNum(rw.value);
		if (rw_value!=undefined) {
			rw.value=rw_value;
			if (is_function(rw.onkeyup)) { // NICHT onchange!!
				rw.onkeyup.call();
			}
		}
	}
	// round
	if (type=="round") {
		var obj=SILgetObj(list_int_name,UID,int_name,group);
		if (obj) {
			obj.value=rw.value;
		}
	}
}

//~ function SILupdateField(list_int_name,int_name,values) { // SIL
	//~ var idx=SILgetFieldIndex(list_int_name,int_name);
	//~ // alert(int_name+" "+idx);
	//~ if (idx==undefined) {
		//~ return;
	//~ }
	//~ // go through all UIDs which are still visible
	//~ if (is_function(controls[list_int_name]["fields"][idx]["setFunction"])) {
		//~ for (var b=0;b<controlData[list_int_name]["UIDs"].length;b++) {
			//~ var UID=controlData[list_int_name]["UIDs"][b];
			//~ controls[list_int_name]["fields"][idx]["setFunction"](list_int_name,UID,int_name,b,values);
		//~ }
	//~ }
//~ }

function SILupdateControl(list_int_name,UID,int_name,group,values) { // SIL
	var fieldIdx=SILgetFieldIndex(list_int_name,int_name,group),pos=SILgetPos(list_int_name,UID);
	if (is_function(controls[list_int_name]["fields"][fieldIdx]["setFunction"])) {
		controls[list_int_name]["fields"][fieldIdx]["setFunction"](list_int_name,UID,pos,fieldIdx,int_name,group,values);
	}
}

// filter
// nur readOnly, nur bestehende Datensätze

function prepareVal(op,val,isNeedle) { // => helper
	switch (op) {
		// string
		case "ct":
		case "sw":
		case "ew":
		case "eq":
			val=val.toLowerCase();
		break;
		// num
		case "bt":
			if (isNeedle) { // splitValue and return array
				return splitRange(val);
				break;
			}
		// else: kein break, ganz normal numerisch
		case "gt":
		case "lt":
		case "ex":
			val=parseFloat(val);
		break;
	}
	return val;
}

function SILaddFilter(list_int_name,int_name,group,op,val) { // SIL, Datensätze ausblenden, die dem Filterkrit nicht entsprechen. UID BLEIBT (sonst kommt der Datensatz nicht wieder
	if (!val) {
		return;
	}
	var allowReorder=a(controls,list_int_name,"allowReorder"),hiddenArray=[];
	val=prepareVal(op,val,true);
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b],match;
		var checkVal=SILgetValue(list_int_name,UID,int_name,group);
		if (checkVal!=undefined) { // undefined values are filtered out
			checkVal=prepareVal(op,checkVal);
			switch (op) {
			// string
			case "ct":
				match=(checkVal.indexOf(val)!=-1);
			break;
			case "sw":
				match=(startswith(checkVal,val));
			break;
			case "ew":
				match=(endswith(checkVal,val));
			break;
			case "eq":
				match=(val==checkVal);
			break;
			// num
			case "bt":
				// check if either val[0] or val[1] is set
				if (val[0]=="" && val[1]=="") {
					return;
				}
				if ((val[0]=="" || val[0]<=checkVal) && (val[1]=="" || checkVal<=val[1])) {
					match=true;
				}
			break;
			case "gt":
				match=(checkVal>val);
			break;
			case "lt":
				match=(checkVal<val);
			break;
			case "ex":
				match=(val==checkVal);
			break;
			default: // unknown op
				return;
			}
		}
		if (!match) {
			hiddenArray[b]=true;
			if (allowReorder==true) {
				SILhideLine(list_int_name,UID,true); // nur readOnly
			}
			else {
				SILhideLine(list_int_name,UID); // auch rw
			}
		}
	}
	controlData[list_int_name]["hidden"]=hiddenArray;
}

function SILclearFilter(list_int_name) { // SIL, alle Datensätze anzeigen, für die eine UID existiert
	var allowReorder=a(controls,list_int_name,"allowReorder");
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b];
		if (allowReorder==true) {
			SILshowLine(list_int_name,UID,true); // nur readOnly
		}
		else {
			SILshowLine(list_int_name,UID); // auch rw
		}
	}
	controlData[list_int_name]["hidden"]=[];
}

function SILsetSpan(text,list_int_name,UID,int_name,group,thisReadOnly) {
	setiHTML(SILgetObjName(list_int_name,UID,int_name,group,thisReadOnly),text);
}

// select

function SILselectSetSingleValue(list_int_name,UID,int_name,group,text0,int_name0) { // besch** Quick-Hack, auf pk_select dyn umstellen
	var fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	as("controls","["+fixStr(text0)+"]",list_int_name,"fields",fieldIdx,"texts"); // set int_names, texts
	as("controls","["+fixStr(int_name0)+"]",list_int_name,"fields",fieldIdx,"int_names"); // update <select
	SILPkSelectSetOptions(list_int_name,UID,int_name,group); // works also for ordinary <select
	// show Text
	setiHTML("ro_"+SILgetObjName(list_int_name,UID,int_name,group),text0);
}

function SILclearOptions(list_int_name,UID,int_name,group,hide) {
	var obj=SILgetObj(list_int_name,UID,int_name,group);
	clearChildElementsForObj(obj);
	if (hide) {
		hideObj(obj);
	}
}

function SILsetUnitOptions(list_int_name,UID,int_name,group,class_name) {
	var fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	var data=getUnitListForClass(class_name);
	SILclearOptions(list_int_name,UID,int_name,group);
}

function SILPkSelectSetOptions(list_int_name,UID,int_name,group,oldVal) { // SIL, works also for ordinary <select
	var fieldIdx=SILgetFieldIndex(list_int_name,int_name,group),obj=SILgetObj(list_int_name,UID,int_name,group);
	if (!obj) {
		return;
	}
	var texts=a(controls,list_int_name,"fields",fieldIdx,"texts"),int_names=a(controls,list_int_name,"fields",fieldIdx,"int_names"),selected;
	// save old value
	if (oldVal==undefined) {
		oldVal=obj.value;
	}
	// liste leeren
	clearChildElementsForObj(obj);
	// write list
	for (var b=0,max=int_names.length;b<max;b++) {
		selected=(oldVal==int_names[b]);
		selAddOption(obj,int_names[b],texts[b],selected);
		if (selected==true) { // ro setzen
			SILsetSpan(texts[b],list_int_name,UID,int_name,group,true);
		}
	}
}

// pk_select / dynamic NOT COMPLETED YET!!! Problem: Wir müssen beim Wechsel nach rw alle Listen mit EINER Abfrage bei chooseAsync aufbauen

function SILPkSelectSetControlData(list_int_name,UID,int_name,group,data) {
	as("controlData",data,list_int_name,"data",UID,int_name,group);
}

function SILPkSelectCallUpdate(list_int_name,UID,int_name,group) { // SIL
	var fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	// ruft die Update-Funktion eines dynamisch gefilterten <select auf, ohne direkt valuesChanged auszulösen
	if (a(controls,list_int_name,"fields",fieldIdx,"dynamic") && is_function(controls[list_int_name]["fields"][fieldIdx]["updateFunction"])) {
		// get list if there is no controlData for current dataset
		controls[list_int_name]["fields"][fieldIdx]["updateFunction"](list_int_name,UID,int_name,group);
	}
}

function SILPkSelectSetData(list_int_name,UID,int_name,group,set_int_names) { // SIL
	// setzt weitere Felder in der SIL anhand der Daten zur ausgewählten Option
	var idx=SILgetObj(list_int_name,UID,int_name,group).selectedIndex,json="{",fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	for (var b=0,max=set_int_names.length;b<max;b++) {
		if (b>0) {
			json+=",";
		}
		json+=fixStr(set_int_names[b])+":"+fixStr(a(controlData,list_int_name,"data",UID,set_int_names[b]));
	}
	json+="}";
	values=[];
	as("values",json);
	SILsetValues(list_int_name,UID,undefined,values);
}

function SILPkSelectGetRefreshURL(list_int_name,UID,int_name,group) { // SIL
	var fieldIdx=SILgetFieldIndex(list_int_name,int_name,group);
	return "chooseAsync.php?desired_action=loadDataForPkSelect&list_int_name="+list_int_name+"&UID="+UID+"&int_name="+int_name+"&group="+group;
}

function SILPkSelectUpdate(list_int_name,UID,int_name,group) { // SIL
	// aktualisiert ein dynamisch gefiltertes <select
	var fieldIdx=SILgetFieldIndex(list_int_name,int_name);
	if (!a(controls,list_int_name,"fields",fieldIdx,"dynamic")) {
		return;
	}
	var filter=controls[list_int_name]["fields"][fieldIdx]["getFilter"](list_int_name,UID,int_name,group);
	if (filter==false) { // allow cancel
		return;
	}
	// requires comm-frame
	var url=SILPkSelectGetRefreshURL(list_int_name,UID,int_name,group)+"&table="+a(controls,list_int_name,"fields",fieldIdx,"table")+"&flags="+a(controls,list_int_name,"fields",fieldIdx,"flags")+"&"+filter;
	// var url="chooseAsync.php?desired_action=loadDataForPkSelect&table="+a(controls,int_name,"table")+"&int_name="+int_name+"&filter="+filter+"&pkName="+a(controls,int_name,"pkName")+"&nameField="+a(controls,int_name,"nameField")+"&allowNone="+a(controls,int_name,"allowNone")+"&noneText="+a(controls,int_name,"noneText");
	window.frames["comm"].location.href=url;
}

function SILPkSelectUpdated(list_int_name,UID,int_name,group) { // SIL
	var fieldIdx=SILgetFieldIndex(list_int_name,int_name,group),fieldArr=a(controls,list_int_name,"fields",fieldIdx);
	// setzt Texte und Werte eines dynamisch gefilterten <select
	if (!a(fieldArr,"dynamic")) {
		return;
	}
	// get int_names and texts
	var selectData=a(controlData,list_int_name,"data",UID),pkName=a(fieldArr,"pkName");
	controls[list_int_name]["fields"][fieldIdx]["texts"]=[];
	controls[list_int_name]["fields"][fieldIdx]["int_names"]=[];
	// allowNone
	if (a(fieldArr,"allowNone")) {
		controls[list_int_name]["fields"][fieldIdx]["int_names"].push("");
		controls[list_int_name]["fields"][fieldIdx]["texts"].push( a(controls,int_name,"noneText") );
	}
	// update control array
	for (var b=0,max=selectData.length;b<max;b++) {
		controls[list_int_name]["fields"][fieldIdx]["int_names"].push( selectData[b][pkName] );
		controls[list_int_name]["fields"][fieldIdx]["texts"].push( controls[list_int_name]["fields"][fieldIdx]["getText"](int_name, selectData[b]) );
	}
	// write list
	SILPkSelectSetOptions(list_int_name,UID,int_name,group);
	// updateControl(int_name);
}

function SILscrollIntoView(list_int_name,UID) {
	autoScrollInProgress=true;
	makeVisible("tr_readOnly_"+list_int_name+"_"+UID+"_0");
	makeVisible("tr_"+list_int_name+"_"+UID+"_0");
	autoScrollInProgress=false;
}