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

function resetSettings() {
	if (confirm(s("reset_settings"))) {
		setInputValue("save_settings","reset"); 
		submitForm("main");
	}
}

function updateViewName() {
	if (opener) {
		var values=[];
		values["key"]=getInputValue("key");
		// SILsetValueUID(list_int_name,UID,pos,fieldIdx,int_name,group,values)
		opener.SILsetValueUID(opener_list_int_name,opener_UID,undefined,undefined,"key",undefined,values);
	}
}

function editCustomList(list_int_name,UID,int_name) {
	var url="editWin.php?mode=custom_list&table="+a(controls,list_int_name,"table")+"&list_int_name="+list_int_name+"&UID="+UID;
	//~ +"&fields="+SILgetValue(list_int_name,UID,"fields");
	window.open(url,Number(new Date()),"height=400,width=700,scrollbars=yes");
}

function updateFieldList(list_int_name,UID,int_name) {
	if (opener) {
		var values=[],texts=[],int_names=[];
		for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
			UID=controlData[list_int_name]["UIDs"][b];
			if (SILgetChecked(list_int_name,UID,"active")) {
				texts.push(SILgetValue(list_int_name,UID,"localized_field"));
				int_names.push(SILgetValue(list_int_name,UID,"field"));
			}
		}
		values["localized_fields"]=texts.join(", ");
		values["fields"]=int_names.join(", ");
		opener.SILsetValueUID(opener_list_int_name,opener_UID,undefined,undefined,"localized_fields",undefined,values);
		opener.SILsetValueUID(opener_list_int_name,opener_UID,undefined,undefined,"fields",undefined,values);
	}
}
