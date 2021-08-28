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

// funktionen für steuerelemente
var a_timestamp;
formulare=[];
page_forms=[];
controls=[];
controlData=[];

singleModeControls=["radio","applet","structure","js","group"]; // make obsolete"check","checkset",
noRwControls=["checkset","subitemlist"];

function getControlElementType(int_name) {
	return a(controls,int_name,"item");
}

function getControlObj(int_name,thisReadOnly) {
	var obj_name="";
	if (thisReadOnly) {
		obj_name+="value_";
	}
	obj_name+=int_name;
	return $(obj_name);
}

function updateClear(int_name) {
	var elementType=getControlElementType(int_name),control_name="clear_"+int_name;
	switch (elementType) {
	case "pk_select":
	case "select":
		visibleObj(control_name,getSelectedIndex(int_name)!=0);
	break;
	default:
		visibleObj(control_name,getControlValue(int_name)!="");
	}
}

function clearControl(int_name) {
	var elementType=getControlElementType(int_name);
	switch (elementType) {
	case "check":
	case "checkbox":
		if (getControlValue(int_name)!=false) {
			valChanged();
			setControl(int_name); // Aufruf mit leeren Werten
		}
	break;
	case "pk_select":
	case "select":
		if (getSelectedIndex(int_name)!=0) {
			valChanged();
			setSelectedIndex(int_name,0); // Aufruf mit leeren Werten
			touchOnChange(int_name);
		}
	break;
	default:
		if (getControlValue(int_name)!="") {
			valChanged();
			setControl(int_name); // Aufruf mit leeren Werten
		}
	}
}

function setControlValue(int_name,value) {
	var values={};
	values[int_name]=value;
	valChanged();
	setControl(int_name,values,false);
}

function getControlValue(int_name) {
	var elementType=getControlElementType(int_name),obj=$(int_name);
	if (!obj && !in_array(elementType,noRwControls)) {
		return "";
	}
	switch (elementType) {
	case "input":
		var type=a(controls,int_name,"type");
		if (type=="textarea") { // wyzz
			updateTextArea(int_name);
		}
	case "hidden":
	case "structure":
	case "pk":
	case "language":
		return obj.value;
	break;
	case "select":
	case "pk_select":
		if (!a(controls,int_name,"multiMode")) {
			return obj.value;
		}
		// schleife über options für multiselect
		var int_names=a(controls,int_name,"int_names"),value=[];
		for (var b=0;b<int_names.length;b++) {
			var obj=$(int_name+"_"+int_names[b]);
			if (obj) {
				if (obj.selected) {
					value.push(obj.value);
				}
			}
		}
		return value;
	break;
	case "applet":
		var appletName=a(controls,int_name,"appletName");
		if (a(controls,int_name,"mode")=="rxn") {
			return getRxnfile(appletName);
		}
		else {
			return getMolfile(appletName);
		}
	break;
	case "check":
	case "checkbox":
		return (obj.checked?obj.value:"");
	break;
	case "checkset":
		var int_names=a(controls,int_name,"int_names"),shift=def0(a(controls,int_name,"shift")),value=0;
		for (var b=0,max=int_names.length;b<max;b++) {
			var obj=$(int_names[b]),mask=Math.pow(2,b+shift);
			if (obj) {
				if (obj.checked) {
					value+=mask;
				}
			}
		}
		return value;
	break;
	case "radio":
	break;
	case "sds":
		obj=$(int_name+"_by");
		if (obj) {
			return obj.value;
		}
	break;
	}
}

function getNameValuePair(int_name) {
	return "&"+int_name+"="+getControlValue(int_name);
}

function controlChanged(int_name) {
	var type=a(controls,int_name,"type");
	if (type=="round" || type=="combo") { // round has different input
		rw=$(int_name+"_rounded");
		if (!rw) {
			return;
		}
		var obj=$(int_name);
		if (obj) {
			obj.value=rw.value;
		}
	}
}

function adjustComboSize(master,select,div) {
	adjustElementSize(master,div,8+4096,2);
	adjustElementSize(master,select,4096+16384,0,0,20);
}

function toggleSelect(int_name,visible) { // show/hide <select for combo
	// visible?
	if (visible==undefined) {
		visible=a(controlData,int_name,"toggleVisible");
	}
	if (visible) {
		visible=false;
	}
	else {
		visible=true;
		adjustComboSize($(int_name+"_rounded"),$(int_name+"_select"),$(int_name+"_div"));
	}
	visibleObj(int_name+"_div",visible);
	as("controlData",visible,int_name,"toggleVisible");
}

function clickCombo(int_name) { // chosen value from the list
	// get Text and value
	var value=getInputValue(int_name+"_select");
	var text=getSelectSelectedText(int_name+"_select",true); // also deselect
	// set <inputs
	setInputValue(int_name+"_rounded",text);
	setInputValue(int_name,value);
	// hide div
	var visible=false;
	visibleObj(int_name+"_div",visible);
	as("controlData",visible,int_name,"toggleVisible");
}

function controlEval(int_name) {
	var rw=$(int_name),element=a(controls,int_name,"item");
	switch (element) {
	/* case "select":
		var value=getControlValue(int_name),texts=a(controls,int_name,"texts"),int_names=a(controls,int_name,"int_names");
		setiHTML("display_"+int_name,getSelectText(int_names,texts,value));
	break; */
	case "input":
		var type=a(controls,int_name,"type"),doEval=a(controls,int_name,"doEval");
		if (type=="round") { // round has different input
			rw=$(int_name+"_rounded");
		}
		if (!rw) {
			return;
		}
		// hack for "actual_amount"
		if (int_name=="actual_amount") {
			var value=rw.value;
			if (value && value.indexOf("%")!=-1) {
				var totalAmount=getInputValue("amount");
				rw.value=value.replace(/%/g,"%*"+totalAmount);
			}
		}
		// doEval
		if (doEval) {
			// replace , by . only simple calculations
			var rw_value=evalNum(rw.value);
			if (rw_value!=undefined) {
				rw.value=rw_value;
			}
		}
		// round
		if (type=="round") {
			var obj=$(int_name);
			if (obj) {
				obj.value=rw.value;
			}
		}
	break;
	}
}

function setControl(int_name,values,init,noOverwrite) { // init teilt setFunction für pk mit, daß initialisierung im gang ist, vermeidet endlosschleife, noOverwrite for readExt
	var value;
	if (int_name) {
		value=a(values,int_name);
		if (controls[int_name]) {
			if (typeof controls[int_name]["getValue"]=="function") {
				value=controls[int_name]["getValue"](int_name,values); // limit potential damage
			}
		}
	}
	if (int_name=="allowDelete") { // show / hide delete button
		allowDelete=(value!=0);
		return;
	}
	if (int_name=="allowEdit") { // show / hide delete button
		allowEdit=(value!=0);
		return;
	}
	if (int_name=="allowAdd") { // show / hide delete button
		allowAdd=(value!=0);
		return;
	}
	if (int_name=="sel") { // show / hide delete button
		setChecked(int_name,value!=0);
		return;
	}
	// alert(setControl.caller);
	var ro=$("value_"+int_name),rw=$(int_name),elementType=getControlElementType(int_name);
	if (!rw && !in_array(elementType,noRwControls)) {
		return;
	}
	if (noOverwrite && getControlValue(int_name)) {
		return;
	}
	if (a(controls,"value")!="") { // dont overwrite fixed values
		return;
	}
	if (value==undefined || value==null) {
		value="";
	}
	var contArr=a(controls,int_name);
	switch (elementType) {
	case "applet":
		var appletName=a(contArr,"appletName");
		if (a(contArr,"mode")=="rxn") {
			setRxnfile(appletName,value);
		}
		else {
			putMolfile(appletName,value);
		}
	break;
	case "check":
	case "checkbox":
		var elValue=rw.value;
		if (elValue && value==elValue) {
			value=true;
		}
		ro.checked=(value==true);
		rw.checked=(value==true);
	break;
	case "checkset":
		var int_names=a(contArr,"int_names"),shift=def0(a(contArr,"shift")),roList=a(contArr,"roList"),texts=a(contArr,"texts"),images=a(contArr,"images"),width=a(contArr,"width"),height=a(contArr,"height"),value_text="",value_arr;
		if (!is_numeric(value)) {
			// comma-separated list of texts
			value_arr=value.split(",");
		}
		for (var b=0,max=int_names.length;b<max;b++) {
			var obj=$(int_names[b]),value_obj=$("value_"+int_names[b]),mask=Math.pow(2,b+shift),isChecked;
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
			ro.innerHTML=value_text;
		}
	break;
	case "db_name":
		if (a_db_id==-1) {
			value="";
		}
		rw.innerHTML=value;
	break;
	case "hidden":
		rw.value=value;
	break;
	case "input":
		if (!ro) {
			return;
		}
		if (is_object(value)) {
			value="";
		}
		var type=a(contArr,"type"),displayValue;
		switch (type) {
		case "combo":
			// get Text for value
			var int_names=a(contArr,"int_names"),displayValue=value; // in case nothing is found
			for (var b=0,max=int_names.length;b<max;b++) {
				if (int_names[b]==value) {
					displayValue=a(contArr,"texts",b);
					break;
				}
			}
			setInputValue(int_name+"_rounded",displayValue);
		break;
		case "date":
			value=toGerDate(value);
		break;
		case "password":
			value="";
		break;
		case "percent":
			displayValue=value+"%";
		break;
		case "range":
			value=formatRangeForSQL(a(values,int_name+"_low"),a(values,int_name+"_high"));
		break;
		case "round":
			// genauen Wert in hidden setzen (normales Verhalten), außerdem gerundeten in _rounded
			var rounded=$(int_name+"_rounded"),decimals=a(contArr,"decimals"),roundMode=a(contArr,"roundMode");
			displayValue=round(value,decimals,roundMode); // nice HTML exponents
			setInputValue(int_name+"_rounded",round(value,decimals,roundMode,true));
		break;
		}
		
		if (int_name=="molecule_names_edit") { // not very clean
			displayValue=a(values,"molecule_names");
		}
		else if (type=="textarea") {
			displayValue=nl2br(value,true);
		}
		else if (type=="textarea_classic") {
			displayValue=nl2br(value);
		}
		
		// write
		if (displayValue==undefined) {
			displayValue=value;
		}
		
		// handle displayValue
		if (is_function(controls[int_name]["handleDisplay"])) {
			displayValue=controls[int_name]["handleDisplay"](int_name,displayValue);
		}
		var softLineBreakAfter=a(contArr,"softLineBreakAfter");
		if (softLineBreakAfter) {
			displayValue=softLineBreaks(displayValue,softLineBreakAfter);
		}
		// write values
		ro.innerHTML=displayValue;
		rw.value=value;
		if (type=="textarea") { // wyzz
			updateIframe(int_name);
		}
	break;
	case "js":
		var functionBody=a(contArr,"functionBody");
		var displayValue=eval(functionBody);
		if (displayValue!=undefined) {
			rw.innerHTML=displayValue;
		}
	break;
	case "pk":
		rw.value=value;
		var text,text_obj=$("text_"+int_name);
		if (is_function(controls[int_name]["setValues"])) {
			var selected_values=cloneObject(values);
			text=controls[int_name]["setValues"](selected_values,init);
		}
		if (typeof text!="string" || text=="") {
			text=controls[int_name]["noneText"];
		}
		if (ro) {
			ro.innerHTML=text;
		}
		if (text_obj) {
			text_obj.innerHTML=text;
		}
	break;
	case "pk_select":
		if (!a(contArr,"multiMode")) {
			if (a(contArr,"dynamic")) {
				as("controlData",value,int_name,"value");
				if (a(contArr,READONLY)) { // single (fake) value
					// create single option with name-value-pair
					controls[int_name]["texts"]=[controls[int_name]["getText"](int_name, values)];
					controls[int_name]["int_names"]=[value];
					// write options
					PkSelectSetOptions(int_name);
				}
			}
			
			var texts=a(contArr,"texts"),int_names=a(contArr,"int_names");
			
			rw.value=value;
			
			if (ro) {
				name_search: for (var b=0;b<int_names.length;b++) {
					if (value==int_names[b]) {
						ro.innerHTML=texts[b];
						break name_search;
					}
				}
			}
		}
		else { // multimode (currently not dynamic)
			var texts=a(contArr,"texts"),int_names=a(contArr,"int_names"),value_text="",pkName=a(contArr,"pkName"),separator=ifempty(a(contArr,"separator"),", "); // Unterliste
			for (var b=0,max=int_names.length;b<max;b++) { // reset all
				var obj=$(int_name+"_"+int_names[b]);
				if (obj) {
					obj.selected=false;
				}
			}
			for (var c=0,max=value.length;c<max;c++) { // set selected
				var obj=$(int_name+"_"+value[c][pkName]);
				if (obj) {
					obj.selected=true;
				}
				// pk_exclude is only for rw!!
				for (var b=0,max2=int_names.length;b<max2;b++) { // find text for value
					if (int_names[b]!=value[c][pkName]) { // wrong one
						continue;
					}
					if (value_text!="") { // found
						value_text+=separator;
					}
					value_text+=texts[b];
					break;
				}
			}
			//~ alert(value_text);
			ro.innerHTML=value_text;
		}
	break;
	case "radio":

	break;
	case "sds":
		var url_obj=$(int_name+"_url"),by_obj=$(int_name+"_by"),ro_new=$("ro_"+int_name+"_new"),rw_new=$("rw_"+int_name+"_new"),rw_del=$("rw_"+int_name+"_del");
		by_obj.value=values[int_name+"_by"];
		url_obj.value=values[int_name+"_url"];
		if (values[int_name+"_by"] && values[int_name+"_by"]!="") {
			// ro.innerHTML=values[int_name+"_by"];
			ro.value=values[int_name+"_by"];
			rw.value=values[int_name+"_by"];
			ro.style.display="inline";
			ro_new.style.display="inline";
			rw.style.display="inline";
			rw_new.style.display="inline";
			rw_del.style.display="inline";
		}
		else {
			ro.style.display="none";
			ro_new.style.display="none";
			rw.style.display="none";
			rw_new.style.display="none";
			rw_del.style.display="none";
			ro.value="";
			rw.value="";
		}
	break;
	case "select":
	case "language":
		rw.value=value;
		if (isMSIE) { // fix IE bug
			if (rw.selectedIndex==-1) {
				rw.selectedIndex=0;
			}
		}
		if (ro) {
			var texts=a(contArr,"texts"),int_names=a(contArr,"int_names");
			var selText=getSelectText(int_names,texts,value);
			ro.innerHTML=selText;
			// setiHTML("display_"+int_name,selText);
		}
	break;
	case "structure":
		rw.value=addPipes(value);
		var pkName=a(contArr,"pkName"),nameField=a(contArr,"nameField");
		if (pkName!="" && values[pkName]) { // nicht neu generieren, sondern aus db
			var params=getImgParams(pkName,a_db_id,values[pkName],a(values,nameField),a_timestamp);
			imgSetParams(int_name,params);
			updateImg(int_name);
		}
		else {
			addMoleculeToUpdateQueue(int_name);
			updateMolecules("init"); // dont set SMILES and so on
		}
	break;
	case "subitemlist":
		var desired_action="";
		if (noOverwrite==true || editMode==false) {
			desired_action="add";
		}
		//~ alert("A"+JSON.stringify(value));
		//~ alert(init+" "+noOverwrite+"Y"+setControl.caller);
		SILsetAllValues(int_name,desired_action,value,noOverwrite);
	break;
	}
	
	// trigger onchange event (which does not work automatically)
	/* if (!in_array(elementType,noRwControls) && is_function(rw.onchange)) {
		rw.onchange.call();
	}*/
	updateControl(int_name);
}

function updateControl(int_name) {
	var isEmpty=false,elementType=getControlElementType(int_name),contArr=a(controls,int_name),thisReadOnly=a(contArr,READONLY),visible=a(contArr,VISIBLE),thisLocked=a(contArr,LOCKED),rw=$("rw_"+int_name),ro=$("ro_"+int_name);
	
	if (a(contArr,"clearbutton")) {
		updateClear(int_name);
	}
	
	switch (elementType) { // prüfen ob leer
	case "input":
	case "select":
	case "language":
	case "structure":
		var value=getControlValue(int_name);
		if ((value=="" || value==undefined)) { // showAlways deaktiviert das Ausblenden leerer controls
			isEmpty=true;
		}
	break;
	case "pk_select":
		// wie stellen wir sicher, daß immer nur 1 Aufruf erfolgt???
		//~ if (!thisReadOnly) {
			//~ alert("X"+int_name);
			//~ PkSelectCallUpdate(int_name);
		//~ }
		var int_names=a(contArr,"int_names");
		if (!is_array(int_names)) {
			visible=false;
		}
		else if (int_names.length==0) {
			visible=false;
		}
	break;
	case "sds":
		/* var value=getControlValue(int_name);
		if (!value) {
			isEmpty=true;
		} */
	break;
	case "subitemlist":
		// Zeilenzahl==0 ?
		isEmpty=(SILgetLineCount(int_name)==0);
		SILupdateAddButtons(int_name);
	break;
	}
	
	switch (elementType) {
	case "checkset":
		var int_names=a(contArr,"int_names"),roList=a(contArr,"roList");
		if (roList) {
			isEmpty=(getControlValue(int_name)==0);
		}
		else {
			for (var b=0,max=int_names.length;b<max;b++) {
				var obj=$(int_names[b]);
				obj.disabled=(thisReadOnly?"disabled":"");
			}
		}
	break;
	case "check":
	case "checkbox":
		var obj=$(int_name);
		if (obj) {
			obj.disabled=(thisReadOnly?"disabled":"");
		}
	break;
	case "pk":
		if (thisLocked) {
			thisReadOnly=true;
		}
	break;
	case "structure":
		if (thisLocked) {
			thisReadOnly=true;
		}
		var img_obj=$(int_name+"_img");
		if (img_obj) {
			img_obj.style.display=(visible?"":"none");
			if (isEmpty && !a(contArr,"noBorderForEmpty"))  {
				img_obj.style.border="1px solid black";
			}
			else {
				img_obj.style.border="0px solid black";
			}
		}
	break;
	case "text":
		if (!a(contArr,"tableLabel")) { // label for splitMode-controls
			thisReadOnly=true;
		}
	break;
	}
	if (ro) {
		// visibility and readOnly
		ro.style.display=(visible && (thisReadOnly || in_array(elementType,singleModeControls)) && (!isEmpty || a(contArr,"showAlways")))?"":"none";
	}
	if (rw) {
		// visibility and readOnly
		rw.style.display=(visible && (thisReadOnly!=true))?"":"none";
	}
	
	// locked is more complicated, applies only for readOnly=false
	var label=$("label_"+int_name),input=$(int_name);
	switch (elementType) {
	case "check":
	case "checkbox":
	case "input":
	case "language":
	case "pk_select":
	case "select":
	// label.color, input.disabled
		if (label) {
			label.style.color=(thisLocked?"gray":"");
		}
		if (input) {
			input.disabled=(thisLocked?"disabled":"");
		}
	break;
	case "checkset":
	// multiple label.color, input.disabled
		if (label) {
			label.style.color=(thisLocked?"gray":"");
		}
		var int_names=a(contArr,"int_names");
		for (var b=0,max=int_names.length;b<max;b++) {
			var input=$(int_names[b]),partLabel=$("label_"+int_names[b]);
			if (partLabel) {
				partLabel.style.color=(thisLocked?"gray":"");
			}
			if (input) {
				input.disabled=(thisLocked?"disabled":"");
			}
		}
	break;
	case "sds":
	//buttons?
		var value_obj=$("value_"+int_name);
		if (value_obj) { // suchknopf
			value_obj.disabled=(thisLocked?"disabled":"");
		}
		if (input) { // suchknopf
			input.disabled=(thisLocked?"disabled":"");
		}
	break;
	case "pk":
	case "structure":
	// like readOnly=true, see earlier
	break;
	//~ case "subitemlist":
	//~ // call special function
		//~ SILlockSubitemlist(int_name,thisLocked);
	//~ break;
	}
}

function showControl(int_name,visible) {
	changeControlProperty(int_name,visible,VISIBLE,"defaultVisible");
}

// Funktionen für ALLE steuerelemente
function setControlValues(values,init,noOverwrite) {
	// dynamische Werte (zB reaction_property) umsetzen, wie?
	//~ alert(init+"X"+setControlValues.caller);
	//~ alert(JSON.stringify(values));
	if (!values) {
		return;
	}
	if (values["timestamp"]) {
		a_timestamp=values["timestamp"];
	}
	updateInProgress=true;
	autoScrollInProgress=true;
	//~ alert("D"+autoScrollInProgress);
	//~ alert(setControlValues.caller+" "+init);
	//~ var starttime=Number(new Date());
	// values=executeFormsGetValues("prepareData",values);
	// setValues
	for (var int_name in values) {
		var real_int_name=a(controls,int_name,"real_int_name");
		if (real_int_name!="") {
			int_name=real_int_name;
		}
		setControl(int_name,values,init,noOverwrite);
	}
	if (init==false) {
		for (var key in formulare) {
			for (var b=0,max=formulare[key]["loadBlind"].length;b<max;b++) {
				var int_name=formulare[key]["loadBlind"][b];
				var real_int_name=a(controls,int_name,"real_int_name");
				if (real_int_name!="") {
					int_name=real_int_name;
				}
				setControl(int_name,values,init,noOverwrite);
			}
		}
	}
	//~ alert(Number(new Date())-starttime);
	// readOnlyForms();
	// execute custom initForms
	//~ var starttime=Number(new Date());
	executeForms("setControlValues",values);
	updateInProgress=false;
	window.setTimeout(function () { autoScrollInProgress=false; updateScrollPos(); },200);
	//~ alert(Number(new Date())-starttime);
}

function resetAlreadyLoaded() {
	for (var int_name in controls) {
		if (a(controls,int_name,"item")=="subitemlist") {
			as("controls",false,int_name,"alreadyLoaded");
			as("controls",false,int_name,"alreadyLoadedRw");
			SILclear(int_name);
		}
	}
}

function readOnlyControls(thisReadOnly) {
	for (var int_name in controls) {
		readOnlyControl(int_name,thisReadOnly);
	}
	updateMolecules();
	executeForms("changeReadOnly",thisReadOnly);
	readOnly=thisReadOnly;
}

function readOnlyControl(int_name,thisReadOnly) {
	changeControlProperty(int_name,thisReadOnly,READONLY,DEFAULTREADONLY);
}

function lockControl(int_name,thisLocked) {
	changeControlProperty(int_name,thisLocked,LOCKED,DEFAULTLOCKED);
}

function defaultReadOnlyControl(int_name,thisDefaultReadOnly) {
	changeControlProperty(int_name,thisDefaultReadOnly,DEFAULTREADONLY);
}

function changeControlProperty(int_name,thisValue,propertyName,defaultBehaviour) {
	var elementType=getControlElementType(int_name);
	switch (a(controls,int_name,defaultBehaviour)) {
	case "always":
		thisValue=true;
	break;
	case "never":
		thisValue=false;
	break;
	}
	if (thisValue==undefined) {
		thisValue=a(controls,int_name,propertyName);
	}
	else {
		as("controls",thisValue,int_name,propertyName);
	}
	//~ alert(int_name+" "+propertyName+" "+thisValue);
	updateControl(int_name);
}

function checkSubmitControl(int_name) {
	var elementType=getControlElementType(int_name),obj=$(int_name);
	if (!obj) {
		return true;
	}
	switch (elementType) {
	case "input":
		var type=a(controls,int_name,"type");
		switch (type) {
		case "password":
			var rep_obj=$(int_name+"_repeat");
			if (!rep_obj) {
				return;
			}
			if (obj.value!=rep_obj.value) {
				alert(s("password_dont_match"));
				return false;
			}
		break;
		}
	break;
	}
	return true;
}

function prepareSubmitControl(int_name) {
	var elementType=getControlElementType(int_name);
	switch (elementType) {
	case "applet":
		setInputValue(int_name,getControlValue(int_name));
	break;
	case "checkset":
		lockControl(int_name,false);
	break;
	case "input":
		var type=a(controls,int_name,"type"),elPostProc=a(controls,int_name,"postProc");
		
		postProc(int_name,elPostProc);
		
		switch (type) {
		case "date":
		//~ case "percent":
		case "range":
			postProc(int_name,type);
		break;
		case "textarea": // wyzz
			updateTextArea(int_name);
		break;
		}
	break;
	case "subitemlist":
		SILprepareSubmit(int_name);
	break;
	}
}

function postProc(id,action) { // behandelt subitemlists und normale elemente gleichermaßen, objekt wird über id angesprochen
	var checkelement=$(id);
	if (!checkelement) {
		return true; // ignore missing
	}
	
	// unlock to have value
	checkelement.disabled="";
	if (action=="") {
		return true;
	}
	switch (action) {
	case "page_range":
		var expr=/([\d\.]+)\-([\d\.]+)/;
		if (expr.exec(checkelement.value)) {
			var low=parseFloat(RegExp.$1),high=parseFloat(RegExp.$2);
			if (low>high) { // 3245-8
				if (high==0) {
					high=10;
				}
				var mult=Math.pow(10,Math.ceil(Math.log(high)/Math.LN10));
				var newHigh=Math.floor(low/mult)*mult+high;
				checkelement.value=low+"-"+newHigh;
			}
		}
	
	break;
	case "range":
		var fnVal=splitRange(checkelement.value);
		setInputValue(id+"_low",fnVal[0]);
		setInputValue(id+"_high",fnVal[1]);
	break;
	case "date":
		checkelement.value=toSQLDate(checkelement.value);
	break;
	case "emp_formula":
		if (checkelement.value!="") {
			checkvalue=computeMolecule(checkelement.value,1);
			if (checkvalue) { // we should make this better FIXME
				checkelement.value=checkvalue["chemFormula"];
			}
		}
	break;
	}
	return true;
}

function getControlUID(int_name) {
	return a(controls,int_name,"UID");
}

function getQueryString(form_name) {
	var query="";
	for (var int_name in formulare[form_name]["queryFields"]) { // controls MUST be val0..valn
		var notEmpty=false,queryIdx=formulare[form_name]["queryFields"][int_name];
		switch (getControlElementType(int_name)) {
		case "pk_select":
		case "select":
			var sel_index=getSelectedIndex(int_name),autoIndex=-1,noneIndex=-1,orig_value=a(controls,"op"+queryIdx,"value");
			//~ alert(queryIdx+"F"+orig_value);
			if (orig_value=="") {
				orig_value="ex";
			}
			setInputValue("op"+queryIdx,orig_value); // "ex"
			// allowAuto  => notEmpty=false
			if (a(controls,int_name,"allowAuto")) {
				autoIndex++;
				if (sel_index==autoIndex) {
					notEmpty=false;
				}
				noneIndex++;
			}
			// allowNone => op => nu
			if (a(controls,int_name,"allowNone")) {
				noneIndex++;
				if (sel_index==noneIndex) {
					notEmpty=true;
					// set op
					setInputValue("op"+queryIdx,"nu");
				}
			}
			if (sel_index>noneIndex && sel_index>autoIndex) { // real value selected
				notEmpty=true;
			}
		break;
		//~ case "hidden": // handle this in a different way
		//~ break;
		default:
			if (getControlValue(int_name)!="") {
				notEmpty=true;
			}
		}
		if (notEmpty==true) {
			if (query!="") {
				query+=" AND ";
			}
			query+="<"+queryIdx+">";
		}
	}
	return query;
}

