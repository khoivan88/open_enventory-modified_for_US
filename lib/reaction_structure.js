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

function combineRxnfile() {
	// generate rxnfile
	var reactants=SILgetValueArray("reactants","molfile_blob"),products=SILgetValueArray("products","molfile_blob");
	return createRxnfile(reactants,products);
}

function updateRxnOnly() { // orderChange, deleteLine
// alert("updateRxnOnly");
	// combine molfiles to rxnfile
	// set input value
	var rxnfile=combineRxnfile();
	//~ alert(rxnfile);
	setInputValue("rxnfile_blob",rxnfile);
	// update, dann umsortierung anhand der SMILES (deshalb loadData)
	addMoleculeToUpdateQueue("rxnfile_blob",undefined,undefined,undefined,"loadData");
	updateMolecules();
}

function rxnChanged() { // komplette änderung, alles neu laden
	// aufspaltung rxnfile auf dem server
	
	// dann updateanforderung für einzelne molfiles
	var list_int_name,list,listUID;
	for (var c=0;c<2;c++) {
		switch (c) {
		case 0:
			// Reaktanten setzen
			list_int_name="reactants";
		break;
		case 1:
			// Produkte setzen
			list_int_name="products";
		break;
		}
		
		for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
			listUID=SILgetUID(list_int_name,b);
			controlData[list_int_name]["old"][listUID]=true;
		}
	}

	addMoleculeToUpdateQueue("rxnfile_blob",undefined,undefined,undefined,"loadData");
	updateMolecules("full");
}

function addMolFromRxn(img_UID,list_int_name,pos,SMILES_stereo,data,molfile_blob) {
	// gibt es listeneintrag schon?
	var UID=SILfindValue(list_int_name,"smiles_stereo",undefined,SMILES_stereo,pos);
	
	// nein: hinzufügen
	if (!UID) {
		UID=SILaddLine(list_int_name);
	}
	else if (list_int_name!="products" && SILgetValue(list_int_name,UID,"from_reaction_id")!="") { // ist listeneintrag aus reaktion? raus
		// old zurücksetzen (wird sonst gelöscht)
		controlData[list_int_name]["old"][UID]=false;
		return;
	}
	
	refUpdateInProgress=true;
	
	// move to pos
	SILswapLines(list_int_name,UID,SILgetUID(list_int_name,pos),true);
	
	// Grafik anzeigen über UID
	if (keepStructures==true) {
		SILsetValue(molfile_blob,list_int_name,UID,"molfile_blob");
	}
	moleculeUpdated(img_UID,list_int_name,UID,"molfile_blob");
	
	// Daten setzen
	setControlDataMolecule(list_int_name,UID,"",undefined,data);
	
	// old zurücksetzen (wird sonst gelöscht)
	controlData[list_int_name]["old"][UID]=false;
	
	// <selects setzen
	updateMolSelect(list_int_name,UID,"molecule_id",undefined,true);
	
	refUpdateInProgress=false;
}

function molChanged(list_int_name,UID,int_name,group) { // neue Struktur für ein einzelnes molekül
	// Daten aus molfile setzen (geht auch für unbekannte moleküle
	// Daten kommen vom Server
	
	// molekül MIT loadData zu queue hinzufügen
	addMoleculeToUpdateQueue(list_int_name,UID,int_name,group,"loadData");
	// ansonsten nur grafik updaten
	updateRxnOnly();
}

function molSelectChanged(list_int_name,UID,int_name,group,noUpdate) {
	// andere molecule_id aus liste ausgewählt
	// noUpdate unterdrückt aut. update der Struktur, wenn die ganze Reaktionsgleichung geändert wird
	// geänderte Daten setzen
	var selectedIndex=def0(SILgetSelectedIndex(list_int_name,UID,"molecule_id",group));
	var moleculeData=a(controlData,list_int_name,"data",UID,group,selectedIndex);
	
	if (is_array(moleculeData)) {
		
		var text=defBlank(moleculeData["db_id"]),transferFields,userDef=(!moleculeData["molecule_id"] && !moleculeData["from_reaction_id"] && !SILgetValue(list_int_name,UID,"molecule_id",group) && !SILgetValue(list_int_name,UID,"from_reaction_id",group));
		SILsetValue(text,list_int_name,UID,"other_db_id",group);
		
		if (list_int_name!="copyTable") {
			valChanged(); // set changed, to avoid input onChange problems
			transferFields=["smiles","smiles_stereo","emp_formula","mw"];
			if (!userDef) {
				// otherwise do not overwrite user-entered data
				transferFields.push("safety_r");
				transferFields.push("safety_s");
				transferFields.push("safety_sym");
				transferFields.push("safety_h");
				transferFields.push("safety_p");
				transferFields.push("safety_sym_ghs");
				transferFields.push("density_20");
			}
		}
		else {
			transferFields=[];
		}
		
		if (!userDef) {
			// otherwise do not overwrite user-entered data
			transferFields.push("cas_nr");
			transferFields.push("standard_name");
		}
		
		// should drawn structures be overwritten?
		if (!noUpdate || keepStructures==false || SILgetValue(list_int_name,UID,"molfile_blob",group)=="") {
			transferFields.push("molfile_blob");
			SILsetDesiredAction(list_int_name,UID,"update");
		}
		
		for (var b=0,max=transferFields.length;b<max;b++) {
			var text=defBlank(moleculeData[ transferFields[b] ]);
			SILsetValue(text,list_int_name,UID,transferFields[b],group);
		}
		
		if (list_int_name!="copyTable") {
			SILObjTouchOnchange(list_int_name,UID,"mw",group); // update calculation, set desired_action to update also
		}
		
		if (list_int_name!="products") {
			updateChemSelect(list_int_name,UID,"chemical_storage_id",group,selectedIndex);
		}
		
		if (list_int_name!="copyTable") {
			rxnValueChanged(list_int_name,UID); // Berechnung updaten
			// zuordnung analytik setzen
			if (!noUpdate) { 
				updateRcUID();
			}
		}
	}
	else { // clear list
		updateChemSelect(list_int_name,UID,"chemical_storage_id",group);
	}
	if (!noUpdate) { // do update
		// molekül OHNE loadData zu queue hinzufügen, Daten sind schon da
		addMoleculeToUpdateQueue(list_int_name,UID,"molfile_blob",group);
		if (list_int_name!="copyTable") {
			// ansonsten nur grafik updaten
			updateRxnOnly(); // cis/trans-platin-case
		}
		else {
			SILsetValue("",list_int_name,UID,"reaction_chemical_id",group); // remove reaction_chemical_id only here to disable that original molecule is taken
			updateMolecules();
		}
	}
}

function chemSelectChanged(list_int_name,UID,int_name,group) { // andere chemical_storage_id aus liste ausgewählt
	// purity suchen
	var selectedMolIndex=SILgetSelectedIndex(list_int_name,UID,"molecule_id",group),selectedIndex=SILgetSelectedIndex(list_int_name,UID,"chemical_storage_id",group);
	var moleculeData=a(controlData,list_int_name,"data",UID,group,selectedMolIndex);
	var this_chemical_storage_data=a(moleculeData,"chemical_storage",selectedIndex);
	if (this_chemical_storage_data=="") {
		this_chemical_storage_data={};
	}
	
	// Barcode setzen
	int_name="chemical_storage_barcode";
	var barcode=defBlank(this_chemical_storage_data[int_name]);
	SILsetValue(barcode,list_int_name,UID,int_name,group);
	SILsetSpan(barcode,"value_"+list_int_name,UID,int_name,group);
	
	// package_name setzen
	if (this_chemical_storage_data["chemical_storage_id"] || selectedIndex>-1) {
		// otherwise do not overwrite user-entered data
		SILsetValue(defBlank(this_chemical_storage_data["package_name"]),list_int_name,UID,"package_name",group);
	}
	
	if (list_int_name=="copyTable") {
		return;
	}
	
	// conc
	int_name="rc_conc_unit";
	var rc_conc=defBlank(this_chemical_storage_data["rc_conc"]),rc_conc_unit=defBlank(this_chemical_storage_data[int_name]),target_conc_unit=SILgetValue(list_int_name,UID,int_name);
	if (rc_conc==="") {
		rc_conc=100;
		rc_conc_unit="%";
	}
	
	// prüfen ob eingestellter Typ paßt, wenn nein auf indiv umstellen
	if (getUnitType(rc_conc_unit)==getUnitType(target_conc_unit)) {
		// an Zieleinheit anpassen
		rc_conc=parseFloat(rc_conc*getUnitFactor(rc_conc_unit)/getUnitFactor(target_conc_unit) );
	}
	else {
		setInputValue(list_int_name+"_rc_conc_unit","");
		touchOnChange(list_int_name+"_rc_conc_unit");
		SILsetValue(rc_conc_unit,list_int_name,UID,"rc_conc_unit",group);
	}
	
	SILsetValue(rc_conc,list_int_name,UID,"rc_conc",group);
	
	// set specific or std density
	var chemical_storage_density_20=def0(this_chemical_storage_data["chemical_storage_density_20"]);
	if (chemical_storage_density_20<=0) {
		chemical_storage_density_20=defBlank(moleculeData["density_20"]);
	}
	SILsetValue(chemical_storage_density_20,list_int_name,UID,"density_20",group);
	
	rxnValueChanged(list_int_name,UID); // Berechnung updaten
}

function updateMolSelect(list_int_name,UID,int_name,group,noUpdate) { // liste updaten auf basis controlData
	// alte werte für molecule_id merken (ggf. keine wirkliche änderung)
	var oldVal=SILgetValue(list_int_name,UID,"molecule_id",group),haveSelected;
	if (oldVal) {
		SILsetValue("",list_int_name,UID,"standard_name",group);
	}
	
	// liste leeren
	var obj=SILgetObj(list_int_name,UID,"molecule_id",group),moleculeData=a(controlData,list_int_name,"data",UID,group),info_text="",hide=true;
	if (obj) {
		clearChildElementsForObj(obj);
		SILsetValue("",list_int_name,UID,"from_reaction_id",group);
		SILsetValue("",list_int_name,UID,"from_reaction_chemical_id",group);
		
		if (list_int_name=="copyTable") {
			SILsetSpan("&gt;&gt;",list_int_name,UID,"indicator",group);
		}
			
		// liste füllen und "beste" werte setzen ( a) vorher ausgewählt, b) gebinde>0)
		for (var b=0,max=moleculeData.length;b<max;b++) {
			var val=moleculeData[b]["molecule_id"],text=strcut(moleculeData[b]["standard_name"],30);
			if (list_int_name!="products") {
				text+=" (";
				// add db name if from other
				if (moleculeData[b]["db_id"] && moleculeData[b]["db_id"]!=-1) {
					text+=moleculeData[b]["show_db_beauty_name"]+": ";
				}
				var chemical_storage_count=0;
				if (is_array(moleculeData[b]["chemical_storage"])) {
					chemical_storage_count=moleculeData[b]["chemical_storage"].length;
				}
				text+=chemical_storage_count+")";
			}
			else if (moleculeData[b]["db_id"] && moleculeData[b]["db_id"]!=-1) {
				text+="("+moleculeData[b]["show_db_beauty_name"]+")";
			}
			
			var selected=(val==oldVal || (!haveSelected && chemical_storage_count>0));
			
			if (val) { // has molecule_id
				selAddOption(obj,val,text,selected);
				hide=false;
			}
			
			if (selected) {
				haveSelected=true;
			}
		}
		
		// show <select
		obj.style.display=hide?"none":"";
	}
	if (hide) {
		// no list, just show text that may have been entered
		info_text+=SILgetValue(list_int_name,UID,"standard_name",group);
	}
	SILsetSpan(info_text,list_int_name,UID,"info1",group);
	molSelectChanged(list_int_name,UID,int_name,group,noUpdate);
	
	if (list_int_name=="copyTable" && group.indexOf("_")!=-1) { // for additional components
		updateAddComp(list_int_name,UID,group);
	}
}

function updateChemSelect(list_int_name,UID,int_name,group) { // liste updaten auf basis controlData
	// alte werte für chemical_storage_id merken (ggf. keine wirkliche änderung)
	var oldVal=SILgetValue(list_int_name,UID,"chemical_storage_id",group);
	if (oldVal) {
		SILsetValue("",list_int_name,UID,"package_name",group);
	}
	
	// liste leeren
	var obj=SILgetObj(list_int_name,UID,"chemical_storage_id",group),selectedMolIndex=SILgetSelectedIndex(list_int_name,UID,"molecule_id",group),moleculeData=a(controlData,list_int_name,"data",UID,group),info_text="",hide;
	if (obj) {
		clearChildElementsForObj(obj);
		// liste füllen und "beste" werte setzen ( a) vorher ausgewählt)
		if (selectedMolIndex!=undefined && is_array(moleculeData[selectedMolIndex])) {
			if (is_array(moleculeData[selectedMolIndex]["chemical_storage"])) {
				for (var b=0,max=moleculeData[selectedMolIndex]["chemical_storage"].length;b<max;b++) {
					var chemicalStorageData=a(moleculeData,selectedMolIndex,"chemical_storage",b);
					var text=chemicalStorageData["package_name"];
					//~ var text=strcut(joinIfNotEmpty(new Array( chemicalStorageData["container"], chemicalStorageData["storage_name"], chemicalStorageData["compartment"])," "),30);
					var val=chemicalStorageData["chemical_storage_id"];
					var selected=(val==oldVal);
					selAddOption(obj,val,text,selected);
				}
			}
			else {
				// molecule yes, package no
				hide=true;
			}
		}
		else {
			hide=true;
		}
		if (hide) {
			info_text+=SILgetValue(list_int_name,UID,"package_name",group);
		}
		// show <select
		obj.style.display=hide?"none":"";
		SILsetSpan(info_text,list_int_name,UID,"info2",group);
		chemSelectChanged(list_int_name,UID,"chemical_storage_id",group);
	}
}

function setChemSelect(list_int_name,UID,int_name,group,value) {
	SILsetValue(value,list_int_name,UID,"chemical_storage_id",group);
}

// from_reaction_id
function updateFromReaction(list_int_name,UID,int_name,group,data) {
	// recalc mass fitting to set unit
	if (data["m_brutto"]) {
		data["m_brutto"]=data["m_brutto"]*getUnitFactor(data["mass_unit"])/getUnitFactor(SILgetValue(list_int_name,UID,"mass_unit",group));
	}
	delete data["mass_unit"];

	var pos=SILgetPos(list_int_name,UID);
	var transferFields=["smiles","smiles_stereo","emp_formula","cas_nr","mw","standard_name","safety_r","safety_s","safety_sym","safety_h","safety_p","safety_sym_ghs","density_20","molfile_blob","package_name","molecule_id","chemical_storage_id","from_reaction_id","from_reaction_chemical_id","other_db_id"];
	
	for (var b=0,max=transferFields.length;b<max;b++) {
		var text=defBlank(data[ transferFields[b] ]);
		SILsetValue(text,list_int_name,UID,transferFields[b],group);
	}
	
	updateSelectInfos(list_int_name,UID,data,group);
	SILsetValueUID(list_int_name,UID,pos,undefined,"chemical_storage_barcode",group,{"chemical_storage_barcode":""});
	addMoleculeToUpdateQueue(list_int_name,UID,"molfile_blob",group);
	
	if (list_int_name=="copyTable") {
		SILsetValueUID(list_int_name,UID,pos,undefined,"reaction_chemical_id",group,{"reaction_chemical_id":""});
		updateMolecules();
	}
	else {
		SILsetDesiredAction(list_int_name,UID,"update");
		rxnValueChanged(list_int_name,UID);
		updateRxnOnly();
	}
}