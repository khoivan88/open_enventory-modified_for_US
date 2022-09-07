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

function rxnToSidenav(int_name) {
	if (!parent.sidenav) {
		return;
	}
	if (updateInProgress) {
		return;
	}
	updateInProgress=true;
	//~ parent.sidenav.updateInProgress=true;
	if (int_name=="view_mode") {
		parent.sidenav.selectRadioButton(int_name,radioButtonValue(int_name));
	}
	else if (int_name=="selected_only") {
		parent.sidenav.setChecked(int_name,getChecked(int_name));
	}
	else {
		parent.sidenav.setInputValue(int_name,getInputValue(int_name));
		//~ parent.sidenav.updateClear(int_name);
		//~ parent.sidenav.selectUpdated(int_name);
		parent.sidenav.touchOnChange(int_name);
	}
	updateInProgress=false;
	//~ parent.sidenav.updateInProgress=false;
}

function sidenavToRxn(int_name) {
	if (!parent.sidenav) {
		return;
	}
	if (updateInProgress) {
		//~ alert("skip "+int_name);
		return;
	}
	updateInProgress=true;
	//~ parent.sidenav.updateInProgress=true;
	if (int_name=="view_mode") {
		selectRadioButton(int_name,parent.sidenav.radioButtonValue(int_name));
	}
	else if (int_name=="selected_only") {
		setChecked(int_name,parent.sidenav.getChecked(int_name));
	}
	else {
		setInputValue(int_name,parent.sidenav.getInputValue(int_name));
		//~ updateClear(int_name);
		//~ selectUpdated(int_name);
		touchOnChange(int_name);
	}
	updateInProgress=false;
	//~ parent.sidenav.updateInProgress=false;
}

//								status						date
syncFields=["fields","view","val3","reaction_started_when",	"val4","view_mode","selected_only","list_op"]; // ,"val0","val1","val2"
function setSearchRxn() { // initDone setzen
	if (initDone) {
		return;
	}
	if (parent.sidenav) {
		for (var b=0,max=syncFields.length;b<max;b++) {
			sidenavToRxn(syncFields[b]);
		}
		var iHTML=parent.sidenav.getiHTML("ref_reaction");
		setiHTML("ref_reaction",iHTML);
		showControl("ref_reaction",iHTML!="");
	}
	initDone=true;
}

function prepareRxnSearch() {
	if (!checkSubmitForms()) {
		return false;
	}
	prepareSubmitForms();
	
	// build query by checking if input fields are empty or not
	var query=getQueryString("reaction_search");
	setInputValue("query",query);
	// suchen
	setFormAction();
	
	return true;
}