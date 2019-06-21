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

// SDS

function getSafetySheet(int_name) {
	var thisReadOnly=a(controls,int_name,READONLY);
	var cas_nr=getControlValue("cas_nr"),molName=getControlValue("molecule_names_edit"),url="editWin.php?mode=sds&int_name="+int_name+(thisReadOnly?"&readOnly=true":"")+"&search=";
	if (cas_nr!="") {
		window.open(url+cas_nr);
		return;
	}
	brkPos=molName.indexOf("\\n");
	if (brkPos>=0) {
		molName=molName.substr(0,brkPos);
	}
	brkPos=molName.lastIndexOf("#");
	if (brkPos>=0) {
		molName=molName.substr(0,brkPos);
	}
	window.open(url+molName);
}

function getSavedSDS(int_name,inline) {
	var url_obj=$(int_name+"_url"),url;
	if (!url_obj) {
		return;
	}
	if (url_obj.value.charAt(0)=="-") {
		url=url_obj.value.substr(1);
	}
	else if (url_obj.value.charAt(0)=="+") {
		// file name in temp folder
		url="getSafetySheet.php?temp_file="+url_obj.value.substr(1);
	}
	else {
		var pkName=a(controls,int_name,"pkName");
		url="getSafetySheet.php?int_name="+int_name+"&"+pkName+"="+getControlValue(pkName)+"&db_id="+getControlValue("db_id")+(inline?"&inline=true":"");
		if (archive_entity) {
			url+="&archive_entity="+archive_entity;
		}
	}
	window.open(url);
}

