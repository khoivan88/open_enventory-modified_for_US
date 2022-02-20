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

// Funktionen für forms
function executeForms(fnName,values) {
	for (var key in formulare) {
		if (is_function(formulare[key])) { // skip toJSON...
			continue;
		}
		if (is_function(formulare[key][fnName])) {
			if (formulare[key][fnName](values)==false) {
				return false;
			}
		}
	}
	return true;
}

function executeFormsGetValues(fnName,values) { // if one function kills the values, they are GONE
	for (var key in formulare) {
		if (is_function(formulare[key])) { // skip toJSON...
			continue;
		}
		if (is_function(formulare[key][fnName])) {
			values=formulare[key][fnName](values);
		}
	}
	return values;
}

// Form properties

function changeFormProperty(int_name,thisValue,propertyName,defaultBehaviour) {
	if (thisValue==undefined) {
		thisValue=a(formulare,int_name,propertyName);
	}
	if (is_function(formulare[int_name]["change"][propertyName])) {
		if (formulare[int_name]["change"][propertyName](thisValue)==false) { // cancel action
			return;
		}
	}
	as("formulare",thisValue,int_name,propertyName);
	for (var b=0,max=formulare[int_name]["controls"].length;b<max;b++) {
		changeControlProperty(formulare[int_name]["controls"][b],thisValue,propertyName,defaultBehaviour);
	}
	//~ if (is_function(formulare[int_name]["afterChange"][propertyName])) {
		//~ formulare[int_name]["afterChange"][propertyName](thisValue);
	//~ }
}

function showForm(int_name,visible) {
	changeFormProperty(int_name,visible,VISIBLE,"defaultVisible");
	var fieldset_name=int_name+"_FS";
	if (a(formulare,int_name,VISIBLE)) {
		showObj(fieldset_name);
	}
	else {
		hideObj(fieldset_name);
	}
}

function lockForm(int_name,thisIsLocked) {
	changeFormProperty(int_name,thisIsLocked,LOCKED,"allowLock");
}

function defaultReadOnlyForm(int_name,thisDefaultReadOnly) {
	changeFormProperty(int_name,thisDefaultReadOnly,DEFAULTREADONLY);
}

function readOnlyForm(int_name,thisReadOnly) {
	changeFormProperty(int_name,thisReadOnly,READONLY,DEFAULTREADONLY);
}

function changeFormsProperty(thisValue,propertyName,defaultBehaviour) { // true, READONLY, DEFAULTREADONLY
	var defaultValue;
	for (var key in formulare) {
		if (is_function(formulare[key])) {
			continue;
		}
		switch (a(formulare,key,defaultBehaviour)) {
		case "always":
			defaultValue=true;
		break;
		case "never":
			defaultValue=false;
		break;
		default:
			defaultValue=thisValue;
		}
		changeFormProperty(key,defaultValue,propertyName,defaultBehaviour);
	}
}

function readOnlyMainForm(thisReadOnly) {
	// assume 1st form
	var form_name=a(page_forms,0);
	if (form_name!="") {
		readOnlyForm(form_name,thisReadOnly);
	}
}

function readOnlyForms(thisReadOnly) {
	changeFormsProperty(thisReadOnly,READONLY,DEFAULTREADONLY);
	updateMolecules();
	if (thisReadOnly!=undefined) {
		readOnly=thisReadOnly;
	}
}

function checkSubmitForm(int_name) {
	if (a(formulare,int_name,READONLY) || a(formulare,int_name,LOCKED) || !a(formulare,int_name,VISIBLE)) { // dont check readOnly or locked Forms
		return true;
	}
	for (var b=0,max=formulare[int_name]["controls"].length;b<max;b++) {
		if (!checkSubmitControl(formulare[int_name]["controls"][b])) {
			return false;
		}
	}
	if (is_function(formulare[int_name]["checkSubmit"])) {
		return formulare[int_name]["checkSubmit"]();
	}
	return true;
}

function checkSubmitForms() {
	for (var key in formulare) {
		if (is_function(formulare[key])) {
			continue;
		}
		if (!checkSubmitForm(key)) {
			return false;
		}
	}
	return true;
}

function prepareSubmitForm(int_name) {
	for (var b=0,max=formulare[int_name]["controls"].length;b<max;b++) {
		prepareSubmitControl(formulare[int_name]["controls"][b]);
	}
	if (is_function(formulare[int_name]["prepareSubmit"])) {
		formulare[int_name]["prepareSubmit"]();
	}
}

function prepareSubmitForms() {
	for (var key in formulare) {
		if (is_function(formulare[key])) {
			continue;
		}
		prepareSubmitForm(key);
	}
}

function onActivateViewForms(view,oldView) {
	for (var key in formulare) {
		if (is_function(formulare[key])) {
			continue;
		}
		if (is_function(formulare[key]["onActivateView"])) {
			formulare[key]["onActivateView"](view,oldView);
		}
	}
}