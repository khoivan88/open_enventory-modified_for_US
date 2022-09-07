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
var readOnly;

function strucChanged(list_int_name,UID,int_name,group) { // neue Struktur für ein einzelnes molekül
	SILsetValue("",list_int_name,UID,"reaction_chemical_id",group); // remove reaction_chemical_id
	// molekül MIT loadData zu queue hinzufügen
	addMoleculeToUpdateQueue(list_int_name,UID,int_name,group,"loadData");
	updateMolecules();
}

function addStrucChanged(list_int_name,UID,int_name,group) {
	updateAddComp(list_int_name,UID,group);
	// molekül MIT loadData zu queue hinzufügen
	addMoleculeToUpdateQueue(list_int_name,UID,int_name,group,"loadData");
	updateMolecules();
}

function toggleColVisible(list_int_name,reaction_chemical_id,visible) {
	visibleObj("link_show_"+reaction_chemical_id,!visible);
	visibleObj("link_hide_"+reaction_chemical_id,visible);
	SILsetFieldVisible(list_int_name,"col0",reaction_chemical_id,visible);
	SILsetFieldVisible(list_int_name,"col1",reaction_chemical_id,visible);
}

function updateAddComp(list_int_name,UID,group) {
	// if there is something set, show amount inputs, otherwise hide them
	var visible=false,this_value;
	var checkFields=["molecule_id","from_reaction_id","standard_name","package_name","cas_nr"];
	for (var b=0,max=checkFields.length;b<max;b++) {
		this_value=defBlank(SILgetValue(list_int_name,UID,checkFields[b],group));
		if (this_value!="") {
			visible=true;
			//~ alert(group+"Y"+checkFields[b]+"Z"+this_value);
			break;
		}
	}
	if (visible==false && isEmptyMolfile(SILgetValue(list_int_name,UID,"molfile_blob",group))==false) {
		//~ alert("X");
		visible=true;
	}
	//~ alert(visible);
	visibleObj(SILgetObjName(list_int_name,UID,"amount",group,false,"rounded"),visible);
	visibleObj(SILgetObjName(list_int_name,UID,"amount_unit",group),visible);
}

function personUpdated() {
	PkSelectUpdate("lab_journal_id");
}

function setPersonName() {
	var idx=getSelectedIndex("person_id");
	if (idx>0) {
		setInputValue("reaction_carried_out_by",a(controls,"person_id","texts",idx));
	}
}