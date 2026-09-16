/*
Copyright 2006-2009 Felix Rudolphi and Lukas Goossen
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
	addMoleculeToUpdateQueue("rxnfile_blob",null,null,"loadData");
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

	addMoleculeToUpdateQueue("rxnfile_blob",null,null,"loadData");
	updateMolecules("full");
}

function addMolFromRxn(list_int_name,UID,pos,SMILES_stereo,data) {
	refUpdateInProgress=true;
	
	// gibt es listeneintrag schon?
	var listUID=SILfindValue(list_int_name,"smiles_stereo",SMILES_stereo,pos);
	
	// nein: hinzufügen
	if (!listUID) {
		listUID=SILaddLine(list_int_name);
	}
	
	// move to pos
	SILswapLines(list_int_name,listUID,SILgetUID(list_int_name,pos),true);
	
	// Grafik anzeigen über UID
	moleculeUpdated(list_int_name,UID,listUID,"molfile_blob");
	
	// Daten setzen
	setControlDataMolecule(list_int_name,listUID,data);
	
	// old zurücksetzen (wird sonst gelöscht)
	controlData[list_int_name]["old"][listUID]=false;
	
	// <selects setzen
	updateMolSelect(list_int_name,listUID,"molecule_id",true);
	
	refUpdateInProgress=false;
}

function molChanged(list_int_name,UID,int_name) { // neue Struktur für ein einzelnes molekül
// alert("molChanged");
	// Daten aus molfile setzen (geht auch für unbekannte moleküle
	molData=computeMolecule(SILgetValue(list_int_name,int_name,UID),0);
	SILsetValue(molData["chemFormula"],list_int_name,"emp_formula",UID);
	SILsetValue(molData["MW"],list_int_name,"mw",UID);

	// molekül MIT loadData zu queue hinzufügen
	addMoleculeToUpdateQueue(list_int_name,UID,int_name,"loadData");
	// ansonsten nur grafik updaten
	updateRxnOnly();
}

function molSelectChanged(list_int_name,UID,int_name,noUpdate) { // andere molecule_id aus liste ausgewählt
//~ alert("molSelectChanged");
	// geänderte Daten setzen
	var suffix="";
	if (list_int_name=="copyTable") {
		suffix="_"+a(controls,list_int_name,int_name,"reaction_chemical_id");
	}
	
	var moleculeData=a(controlData,list_int_name,"data",UID,"molecule"),molecule_id=SILgetValue(list_int_name,"molecule_id",UID);
	if (is_array(moleculeData) && moleculeData.length) {
		var selectedIndex=findInArray(moleculeData,"molecule_id",molecule_id);
		if (selectedIndex==undefined) {
			selectedIndex=0;
		}
		
		var text=defBlank(moleculeData[selectedIndex]["molfile_blob"]);
		SILsetValue(text,list_int_name,"molfile_blob"+suffix,UID); // bei unbekannten Molekülen werden MW, smiles und emp_formula trotzdem übertragen
		
		if (list_int_name=="copyTable") {
			return;
		}
		
		valChanged(); // set changed, to avoid input onChange problems
		var transferFields=new Array("smiles","smiles_stereo","emp_formula","cas_nr","mw","standard_name","safety_r","safety_s","safety_sym");
		for (var b=0,max=transferFields.length;b<max;b++) {
			var text=defBlank(moleculeData[selectedIndex][ transferFields[b] ]);
			SILsetValue(text,list_int_name,transferFields[b],UID); // bei unbekannten Molekülen werden MW, smiles und emp_formula trotzdem übertragen
		}
		if (list_int_name!="products") {
			SILsetValue(moleculeData[selectedIndex]["density_20"],list_int_name,"density_20",UID);
			updateChemSelect(list_int_name,UID,"chemical_storage_id",selectedIndex);
		}
		rxnValueChanged(list_int_name,UID); // Berechnung updaten
		// zuordnung analytik setzen
		if (!noUpdate) { 
			updateRcUID();
		}
	}
	else { // clear list
		updateChemSelect(list_int_name,UID,"chemical_storage_id"+suffix);
		
		if (list_int_name=="copyTable") {
			return;
		}
	}
	if (!noUpdate) { // do update
		// molekül OHNE loadData zu queue hinzufügen, Daten sind schon da
		addMoleculeToUpdateQueue(list_int_name,UID,"molfile_blob");
		// ansonsten nur grafik updaten
		updateRxnOnly();
	}
}

function chemSelectChanged(list_int_name,UID,int_name) { // andere chemical_storage_id aus liste ausgewählt
//~ alert("chemSelectChanged");
	var suffix="";
	if (list_int_name=="copyTable") {
		suffix="_"+a(controls,list_int_name,int_name,"reaction_chemical_id");
	}
	
	var selectedMolIndex,selectedIndex,moleculeData=a(controlData,list_int_name,"data",UID,"molecule"),molecule_id=SILgetValue(list_int_name,"molecule_id",UID),chemical_storage_id=SILgetValue(list_int_name,"chemical_storage_id",UID);
	selectedMolIndex=findInArray(moleculeData,"molecule_id",molecule_id);
	if (selectedMolIndex==undefined) {
		var chemical_storage_data=new Array();
	}
	else {
		var chemical_storage_data=moleculeData[selectedMolIndex]["chemical_storage"];
	}
	selectedIndex=findInArray(chemical_storage_data,"chemical_storage_id",chemical_storage_id);
	if (selectedIndex==undefined) {
		var this_chemical_storage_data=new Array();
	}
	else {
		var this_chemical_storage_data=chemical_storage_data[selectedIndex];
	}
	
	// Barcode setzen
	int_name="chemical_storage_barcode";
	var barcode=defBlank(this_chemical_storage_data[int_name]);
	SILsetValue(barcode,list_int_name,int_name,UID);
	SILsetSpan(barcode,"value_"+list_int_name,int_name,UID);
	
	if (list_int_name=="copyTable") {
		return;
	}
	
	// purity
	int_name="rc_purity";
	SILsetValue(def(this_chemical_storage_data[int_name],100),list_int_name,int_name,UID);
	SILObjTouchOnchange(list_int_name,UID,int_name);
	
	// conc
	int_name="rc_conc_unit";
	var rc_conc=defBlank(this_chemical_storage_data["rc_conc"]),rc_conc_unit=defBlank(this_chemical_storage_data[int_name]),target_conc_unit=SILgetValue(list_int_name,int_name,UID);
	
	// an Zieleinheit anpassen
	SILsetValue(defBlank(parseFloat(rc_conc*getUnitFactor(rc_conc_unit)/getUnitFactor(target_conc_unit) )),list_int_name,"rc_conc",UID);
	
	// package_name setzen
	SILsetValue(defBlank(this_chemical_storage_data["package_name"]),list_int_name,"package_name",UID);
}

function updateMolSelect(list_int_name,UID,int_name,noUpdate) { // liste updaten auf basis controlData
//~ alert("updateMolSelect");
	// alte werte für molecule_id merken (ggf. keine wirkliche änderung)
	var oldVal=SILgetValue(list_int_name,int_name,UID);
	// liste leeren
	var obj=SILgetObj(list_int_name,int_name,UID),moleculeData=a(controlData,list_int_name,"data",UID,"molecule");
	clearChildElementsForObj(obj);
	// liste füllen und "beste" werte setzen ( a) vorher ausgewählt, b) gebinde>0)
	for (var b=0,max=moleculeData.length;b<max;b++) {
		var text=strcut(moleculeData[b]["standard_name"],30);
		var val=moleculeData[b][int_name],chemical_storage_count=0;
		if (is_array(moleculeData[b]["chemical_storage"])) {
			chemical_storage_count=moleculeData[b]["chemical_storage"].length;
		}
		text+=" ("+chemical_storage_count+")";
		var selected=(val==oldVal),haveSelected=false;
		selAddOption(obj,val,text,selected);
		if (selected) {
			haveSelected=true;
		}
	}
	// show <select
	obj.style.display="";
	SILsetSpan("",list_int_name,int_name+"_span",UID);
	molSelectChanged(list_int_name,UID,int_name,noUpdate);
}

function updateChemSelect(list_int_name,UID,int_name) { // liste updaten auf basis controlData
//~ alert("updateChemSelect");
	// alte werte für chemical_storage_id merken (ggf. keine wirkliche änderung)
	var oldVal=SILgetValue(list_int_name,int_name,UID);
	// liste leeren
	var obj=SILgetObj(list_int_name,int_name,UID),moleculeData=a(controlData,list_int_name,"data",UID,"molecule"),molecule_id=SILgetValue(list_int_name,"molecule_id",UID);
	var selectedMolIndex=findInArray(moleculeData,"molecule_id",molecule_id);
	clearChildElementsForObj(obj);
	// liste füllen und "beste" werte setzen ( a) vorher ausgewählt)
	if (selectedMolIndex!=undefined) {
		if (is_array(moleculeData[selectedMolIndex]["chemical_storage"])) {
			for (var b=0,max=moleculeData[selectedMolIndex]["chemical_storage"].length;b<max;b++) {
				var chemicalStorageData=a(controlData,list_int_name,"data",UID,"molecule",selectedMolIndex,"chemical_storage",b);
				var text=chemicalStorageData["package_name"];
				//~ var text=strcut(joinIfNotEmpty(new Array( chemicalStorageData["container"], chemicalStorageData["storage_name"], chemicalStorageData["compartment"])," "),30);
				var val=chemicalStorageData[int_name];
				var selected=(val==oldVal),haveSelected=false;
				selAddOption(obj,val,text,selected);
				if (selected) {
					haveSelected=true;
				}
			}
		}
	}
	// show <select
	obj.style.display="";
	SILsetSpan("",list_int_name,int_name+"_span",UID);
	chemSelectChanged(list_int_name,UID,int_name);
}