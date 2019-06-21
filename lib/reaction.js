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

function getYieldValue(list_int_name,UID,displayValue) { // show mmol in addition
	var retval=round(displayValue,yield_digits,yield_mode),rc_amount=SILgetValue(list_int_name,UID,"rc_amount");
	if (rc_amount>0 && displayValue>0) {
		// get 
		retval+="<br>("+rxnRound(displayValue*rc_amount/100)+" "+SILgetValue(list_int_name,UID,"rc_amount_unit")+")";
	}
	return retval;
}

function gotoProject() {
	var url=get_reference_url("project",a_db_id,getControlValue("project_id"));
	window.open(url);
}

function getNewReaction() {
	if (a_db_id!=-1) {
		return;
	}
	var lab_journal_id=getInputValue("lab_journal_id");
	var url="editAsync.php?"+getSelfRef(["~script~","cached_query","query"])+"&desired_action=new&db_id="+a_db_id+"&lab_journal_id="+lab_journal_id;
	setFrameURL("edit",url);
}

function getSafetyButtons(values) {
	var retval="";
	if (parseInt(values["has_msds"])) {
		retval+="<a class=\"imgButtonSm\" href="+fixStr("getSafetySheet.php?db_id="+values["other_db_id"]+"&molecule_id="+values["molecule_id"]+"&chemical_storage_id="+values["chemical_storage_id"]+"&cas_nr="+values["cas_nr"])+" target=\"_blank\"><img src=\"lib/sdb_sm.png\"/></a>";
	}
	if (parseInt(values["has_molecule_instructions"])) {
		retval+="<a class=\"imgButtonSm\" href="+fixStr("getInstructions.php?db_id="+values["other_db_id"]+"&molecule_id="+values["molecule_id"]+"&chemical_storage_id="+values["chemical_storage_id"]+"&cas_nr="+values["cas_nr"])+" target=\"_blank\"><img src=\"lib/instructions_sm.png\"/></a>";
	}
	return retval;
}

function updateSelectInfos(list_int_name,UID,values,group) {
	var int_name,int_names,tables,text,roText,rwText,span_name,JScommand;
	
	if (list_int_name=="products") {
		int_names=["molecule_id"];
	}
	else {
		int_names=["molecule_id","chemical_storage_id"]; // for info1, info2
	}
	
	// set selected value as single option
	for (var b=0,max=int_names.length;b<max;b++) {
		int_name=int_names[b];
		var obj=SILgetObj(list_int_name,UID,int_name,group);
		if (obj) {
			obj.style.display="none";
			clearChildElementsForObj(obj);
			if (values[int_name]) {
				selAddOption(obj,values[int_name],"",true);
			}
		}
	}
	
	if (!values["chemical_storage_id"] && values["from_reaction_id"]) { // 1st prio
		JScommand="searchReaction";
		int_names=["from_reaction_id","from_reaction_id"]; // for info1, info2
		tables=["reaction","reaction"];
	}
	else if (values["molecule_id"]) { // 2nd prio, unless chemical_storage_id is set
		JScommand="searchMolecule";
		tables=["molecule","chemical_storage_barcode"]; // chemical_storage_barcode is like chemical_storage, but includes disposed ones
	}
	// otherwise show texts, without links
	
	var text_vars=["standard_name","package_name"];
	var span_names=["info1","info2"];
	
	for (var b=0,max=int_names.length;b<max;b++) {
		int_name=int_names[b];
		text=defBlank(values[ text_vars[b] ]);
		
		//~ alert(b+text);
		if (values[int_name]) {
			roText="<a href="+fixStr(get_reference_url((tables?tables[b]:[]),values["other_db_id"],values[int_name]))+" target=\"_blank\" title="+fixStr(text)+" class=\"medium_small\">"+text+"</a>";
			rwText="<a href=\"javascript:"+JScommand+"("+fixQuot(list_int_name)+","+UID+","+fixQuot(int_name)+","+fixQuot(group)+")\" title="+fixStr(text)+" class=\"medium_small\">"+strcut(text,50)+"</a>";
		}
		else {
			roText=text;
			rwText=text;
		}
		SILsetSpan(roText,list_int_name,UID,span_names[b],group,true); // readOnly part
		SILsetSpan(rwText,list_int_name,UID,span_names[b],group);
	}
}

function copyReaction() {
	//~ var url="copyReaction.php?db_id="+a_db_id+"&pk="+a_pk+"&"+pageParams;
	var url="copyReaction.php?"+getSelfRef(["~script~","db_id","pk"])+"&db_id="+a_db_id+"&pk="+a_pk;
	if (archive_entity!=undefined) {
		url+="&archive_entity="+archive_entity;
	}
	window.open(url);
}

function getReactionBarcode(db_id,prod1,lab_journal_code,nr_in_lab_journal,ak_name,reaction_id) { // give whole label
	var retval="<table class=\"rxnlabel print_only\"><tbody><tr><td><b>"+lab_journal_code+"</b> "+nr_in_lab_journal+"</td><td rowspan=\"5\"><img style=\"display:block;margin: auto\" src=\"getGif.php?db_id="+db_id+"&amp;reaction_chemical_id="+a(prod1,"reaction_chemical_id")+"&amp;no_cache="+a_timestamp+"\" height=\"70\" width=\"70\"></td>";
	
	if (db_id==-1) {
		retval+="<td rowspan=\"6\" style=\"border:0px solid black\"><img src=\"getBarcode.php?text=3"+fillZero(reaction_id,6)+"&width=30&height=81\" style=\"height:2.7cm\"></td>";
	}
	
	retval+="</tr><tr><td>AK "+ak_name+"</td></tr><tr><td>"+getBeautySum(a(prod1,"emp_formula"))+"</td></tr><tr><td>MW= "+round(a(prod1,"mw"),2,0)+"</td></tr><tr><td class=\"big\" style=\"min-width:2cm\">TmD</td></tr><tr><td colspan=\"2\" class=\"small\">Achtung! Nicht vollständig geprüfter Stoff</td></tr>";
	//~ if (db_id==-1) {
		//~ retval+="<tr><td colspan=\"2\"><img src=\"getBarcode.php?text=3"+fillZero(reaction_id,6)+"&horizontal=true&width=162&height=30\"></td>";
	//~ }
	retval+="</tr></table>";
	return retval;
	//~ return "<img class=\"print_only\" src=\"getBarcode.php?text=3"+fillZero(reaction_id,6)+"&horizontal=true&width=162&height=81\">";
}

function scaleReactionChemical(list_int_name,UID,int_name,scale_factor) {
	var val=parseFloat(eval(SILgetValue(list_int_name,UID,int_name)));
	if (!isNaN(val)) {
		val*=scale_factor;
		SILsetValue(val,list_int_name,UID,int_name);
		rxnValueChanged(list_int_name,UID,int_name)
	}
}

function scaleReaction() {
	var scale_factor=prompt(s("enter_scale_factor"));
	if (scale_factor==null || scale_factor<0) {
		return;
	}
	
	if (updateInProgress==true) {
		return;
	}
	updateInProgress=true;
	
	var list_int_names=["reactants","reagents"];
	for (var c=0,max2=list_int_names.length;c<max2;c++) {
		var list_int_name=list_int_names[c];
		for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
			var UID=controlData[list_int_name]["UIDs"][b];
			var measured=parseInt(SILgetValue(list_int_name,UID,"measured"));
			switch (measured) {
			case 1: // m
				scaleReactionChemical(list_int_name,UID,"m_brutto",scale_factor);
			break;
			case 2: // v
				scaleReactionChemical(list_int_name,UID,"volume",scale_factor);
			break;
			case 3: // n
				scaleReactionChemical(list_int_name,UID,"rc_amount",scale_factor);
			break;
			}
		}
	}
	// products
	var list_int_name="products";
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b];
		scaleReactionChemical(list_int_name,UID,"m_brutto",scale_factor);
		scaleReactionChemical(list_int_name,UID,"rc_amount",scale_factor);
	}
	// solvent ml
	var int_name="solvent_amount";
	var val=parseFloat(eval(getInputValue(int_name)));
	if (!isNaN(val)) {
		val*=scale_factor;
		setInputValue(int_name,val);
	}
	
	updateInProgress=false;
	valChanged();
}

function findUID(structureData,smiles_stereo) {
	for (var UID in structureData) {
		//~ alert("Z"+UID);
		if (structureData[UID]["smiles_stereo"]==smiles_stereo) {
			return UID;
		}
	}
}

/*
structureData[UID][$table][0..n]
*/

function searchMolecule(list_int_name,UID,int_name,group) {
	var tables="molecule,chemical_storage";
	if (int_name!="molecule_id" && list_int_name!="products") {
		tables="chemical_storage,molecule";
	}
	var edit_pk=SILgetValue(list_int_name,UID,int_name,group);
	if (edit_pk==undefined) {
		edit_pk="";
	}
	var edit_db_id=SILgetValue(list_int_name,UID,"other_db_id",group);
	if (edit_db_id==undefined || edit_db_id=="") {
		edit_db_id="-1";
	}
	var params="&editDbId="+edit_db_id+"&editPk="+edit_pk;
	openSearchWin(list_int_name,UID,int_name,group,tables,params);
}

function searchReaction(list_int_name,UID,int_name,group) {
	var tables="reaction,reaction_chemical";
	var edit_pk=defBlank(SILgetValue(list_int_name,UID,"from_reaction_id",group));
	var edit_db_id=SILgetValue(list_int_name,UID,"other_db_id",group);
	if (edit_db_id==undefined || edit_db_id=="") {
		edit_db_id="-1";
	}
	var params="&desired_action=lab_journal&editDbId="+edit_db_id+"&editPk="+edit_pk;
	openSearchWin(list_int_name,UID,int_name,group,tables,params);
}

function editRc(list_int_name,UID,int_name,group) {
	var url="editWin.php?mode=edit_rc&list_int_name="+list_int_name+"&UID="+UID+"&int_name="+int_name+"&group="+defBlank(group);
	window.open(url);
}

function handleStructureData(structureData) { // Daten von chooseAsny entgegennehmen und verarbeiten
	// get SMILES arrays from rxnfile_blob
	// update A,B,C,...
	var list_int_name,listUID;
	for (var c=0;c<2;c++) {
		switch (c) {
		case 0:
			list_int_name="reactants";
		break;
		case 1:
			list_int_name="products";
		break;
		}
		for (var b=SILgetLineCount(list_int_name)-1;b>=0;b--) { // von hinten nach vorn durcharbeiten
			listUID=SILgetUID(list_int_name,b);
			if (controlData[list_int_name]["old"][listUID]==true) {
				if (SILgetValue(list_int_name,listUID,"molfile_blob")!="") {
					SILdelLine(list_int_name,listUID); // we must really delete the lines to remove reaction_chemical
				}
			}
		}
		SILupdateDynamicFields(list_int_name);
		SILupdateOrderButtons(list_int_name);
	}
	// update dropdown-lists for analytical data
	updateRcUID();
	return true;
}

function updateIsolyield(list_int_name,UID,int_name,group) {
	// mmol / ( mg * % / g/mol)
	var this_value="",th_amount=parseFloat(SILgetValue(list_int_name,UID,"rc_amount",group)),rc_amount_unit=SILgetValue(list_int_name,UID,"rc_amount_unit",group),m_brutto=SILgetValue(list_int_name,UID,"m_brutto",group);
	var mass_unit=SILgetValue(list_int_name,UID,"mass_unit",group),rc_purity=parseFloat(SILgetValue(list_int_name,UID,"rc_conc",group)),mw=parseFloat(SILgetValue(list_int_name,UID,"mw",group));
	
	if (m_brutto!=="") {
		m_brutto=parseFloat(m_brutto);
		var iso_amount=get_amount(rc_amount_unit,m_brutto,mass_unit,mw);
		if (isNaN(rc_purity) || rc_purity<0) {
			rc_purity=100;
		}
		//~ if (!isNaN(iso_amount) && !isNaN(th_amount)) {
			//~ this_value=defBlank(round(iso_amount*rc_purity/th_amount,1));
		//~ }
		this_value=defBlank(iso_amount*rc_purity/th_amount);
	}
	SILsetValueUID(list_int_name,UID,undefined,undefined,"yield",undefined,{"yield":this_value});
}

function setControlDataMolecule(list_int_name,UID,int_name,group,data) {
	//~ controlData[list_int_name]["data"][UID]=data;
	if (data["molecule"]) {
		as("controlData",data["molecule"],list_int_name,"data",UID,group);
	}
	else {
		as("controlData","",list_int_name,"data",UID,group);
	}
}

function highlightObjBackend(list_int_name,UID,int_name,fontWeight) { // no group
	for (var b=0,max=int_name.length;b<max;b++) {
		var obj=SILgetObj(list_int_name,UID,int_name[b]);
		if (obj) {
			obj.style.fontWeight=fontWeight;
		}
		var obj=SILgetObj(list_int_name,UID,int_name[b],undefined,true);
		if (obj) {
			obj.style.fontWeight=fontWeight;
		}
		var obj=SILgetObj(list_int_name,UID,int_name[b],undefined,false,"rounded");
		if (obj) {
			obj.style.fontWeight=fontWeight;
		}
	}
}

function highlightObj(list_int_name,UID,int_name) { // hervorheben
	highlightObjBackend(list_int_name,UID,int_name,"bold");
}

function unhighlightObj(list_int_name,UID,int_name) { // hervorhebung entfernen
	highlightObjBackend(list_int_name,UID,int_name,"");
}

function rcEmpFormulaChanged(list_int_name,UID,int_name) {
	if (updateInProgress) {
		return;
	}
	updateInProgress=true;
	molData=computeMolecule(SILgetValue(list_int_name,UID,int_name),1);
	SILsetValue(molData["MW"],list_int_name,UID,"mw");
	updateInProgress=false;
}

function rxnProductChanged(list_int_name,UID,int_name) {
	updateGCyield("",list_int_name,UID); // update all for this product
	if (list_int_name=="products") {
		updateIsolyield(list_int_name,UID,int_name);
	}
	if (int_name=="rc_amount") {
		if (refUpdateInProgress) {
			return;
		}
		
		var fix_stoch=getChecked("fix_stoch"),rc_amount=parseFloat(SILgetValue(list_int_name,UID,"rc_amount")),rc_amount_unit=SILgetValue(list_int_name,UID,"rc_amount_unit"),ref_amount_unit=getInputValue("ref_amount_unit");
		if (fix_stoch===false) {
			var ref_amount=parseFloat(getInputValue("ref_amount"));
			var stoch_coeff=get_stoch_coeff_from_amount(rc_amount,rc_amount_unit,ref_amount,ref_amount_unit);
			if (isNaN(stoch_coeff)) {
				stoch_coeff="";
			}
			SILsetValue(stoch_coeff,list_int_name,UID,"stoch_coeff");
		}
		else {
			var stoch_coeff=parseFloat(SILgetValue(list_int_name,UID,"stoch_coeff"));
			if (!isNaN(stoch_coeff)) { // Ansatz anpassen
				// berechnen
				//~ var ref_amount=get_ref_amount_from_amount(ref_amount_unit,rc_amount,rc_amount_unit,stoch_coeff);
				var ref_amount=get_ref_amount_from_amount(rc_amount,stoch_coeff);
				var values={"ref_amount":ref_amount,"ref_amount_unit":rc_amount_unit};
				setControlValues(values);
				refValueChanged(list_int_name,UID);
			}
		}
	}
}

function rxnValueChanged(list_int_name,UID,int_name) {
	if (updateInProgress==true) {
		return;
	}
	updateInProgress=true;
	var mw=parseFloat(SILgetValue(list_int_name,UID,"mw")),density_20=parseFloat(SILgetValue(list_int_name,UID,"density_20")),rc_conc=parseFloat(SILgetValue(list_int_name,UID,"rc_conc"));
	var fix_stoch=getChecked("fix_stoch"),measured,old_measured=parseInt(SILgetValue(list_int_name,UID,"measured"));
	var mass_unit=SILgetValue(list_int_name,UID,"mass_unit"),volume_unit=SILgetValue(list_int_name,UID,"volume_unit"),rc_amount_unit=SILgetValue(list_int_name,UID,"rc_amount_unit"),rc_conc_unit=SILgetValue(list_int_name,UID,"rc_conc_unit");
	measured_names=["","m_brutto","volume","rc_amount"];
	
	measured=array_search(int_name,measured_names);
	if (!measured) { // nur Aktualisierung, measured bleibt beim Alten
		measured=old_measured;
		int_name=a(measured_names,measured);
	}
	
	switch (measured) { // was wurde eingegeben
	case 1:
		unhighlight=["volume","volume_unit","rc_amount","rc_amount_unit"];
		highlight=["m_brutto","mass_unit"];
	break;
	case 2:
		unhighlight=["m_brutto","m_tara","mass_unit","rc_amount","rc_amount_unit"];
		highlight=["volume","volume_unit"];
	break;
	case 3:
		unhighlight=["volume","volume_unit","m_brutto","m_tara","mass_unit"];
		highlight=["rc_amount","rc_amount_unit"];
	break;
	default:
		// should not happen, but somehow seems to
		unhighlight=[];
		highlight=[];
	}
	
	if (old_measured!=measured) {
		SILsetValue(measured,list_int_name,UID,"measured");
	}
	
	if (highlight && unhighlight) {
		unhighlightObj(list_int_name,UID,unhighlight);
		highlightObj(list_int_name,UID,highlight);
	}
	
	if (rc_conc=="" || isNaN(rc_conc)) { // fixPurity must stay in place
		rc_conc=100;
		rc_conc_unit="%";
	}
	
	switch (measured) { // was wurde eingegeben
	case 1: // m
		var mass=parseFloat(SILgetValue(list_int_name,UID,"m_brutto"));
		
		// über die Dichte das Volumen berechnen
		var volume=get_volume(volume_unit,mass,mass_unit,density_20);
			
		switch (getUnitType(rc_conc_unit)) {
		case "m/m":
			// über die Molmasse die Stoffmenge berechnen
			var rc_amount=get_amount(rc_amount_unit,mass*fixPurity(rc_conc*getUnitFactor(rc_conc_unit)),mass_unit,mw);
		break;
		case "molal":
			// über die Masse und die Molalität die Stoffmenge
			var rc_amount=get_amount_from_mass_molal(rc_amount_unit,mass,mass_unit,rc_conc,rc_conc_unit);
		break;
		case "c":
			// über das Volumen und die Konz die Stoffmenge
			var rc_amount=get_amount_from_volume(rc_amount_unit,volume,volume_unit,rc_conc,rc_conc_unit);
		break;
		}
		
		SILsetValue(volume,list_int_name,UID,"volume");
		SILsetValue(rc_amount,list_int_name,UID,"rc_amount");
	break;
	case 2: // v
		var volume=parseFloat(SILgetValue(list_int_name,UID,"volume"));
		
		// über die Dichte die Masse berechnen
		var mass=get_mass_from_volume(mass_unit,volume,volume_unit,density_20);
		
		switch (getUnitType(rc_conc_unit)) {
		case "m/m":
			// über die Molmasse die Stoffmenge berechnen
			var rc_amount=get_amount(rc_amount_unit,mass*fixPurity(rc_conc*getUnitFactor(rc_conc_unit)),mass_unit,mw);
		break;
		case "molal":
			// über die Masse und die Molalität die Stoffmenge
			var rc_amount=get_amount_from_mass_molal(rc_amount_unit,mass,mass_unit,rc_conc,rc_conc_unit);
		break;
		case "c":
			// über das Volumen und die Konz die Stoffmenge berechnen
			var rc_amount=get_amount_from_volume(rc_amount_unit,volume,volume_unit,rc_conc,rc_conc_unit);
		break;
		}
		
		SILsetValue(mass,list_int_name,UID,"m_brutto");
		SILsetValue(rc_amount,list_int_name,UID,"rc_amount");
	break;
	case 3: // n
		var rc_amount=parseFloat(SILgetValue(list_int_name,UID,"rc_amount"));
		
		switch (getUnitType(rc_conc_unit)) {
		case "m/m":
			// über die Molmasse die Masse berechnen
			var mass=get_mass_from_amount(mass_unit,rc_amount/fixPurity(rc_conc*getUnitFactor(rc_conc_unit)),rc_amount_unit,mw);
		
			// über die Dichte das Volumen berechnen
			var volume=get_volume(volume_unit,mass,mass_unit,density_20);
		break;
		case "molal":
			// aus Stoffmenge und Molalität das Masse berechnen
			var mass=get_mass_from_amount_molal(mass_unit,rc_amount,rc_amount_unit,rc_conc,rc_conc_unit);
			
			// über die Dichte das Volumen berechnen
			var volume=get_volume(volume_unit,mass,mass_unit,density_20);
		break;
		case "c":
			// über das Volumen und die Konz das Volumen berechnen
			var volume=get_volume_from_amount(volume_unit,rc_amount,rc_amount_unit,rc_conc,rc_conc_unit);
			
			// über die Dichte die Masse berechnen
			var mass=get_mass_from_volume(mass_unit,volume,volume_unit,density_20);
		break;
		}
		
		SILsetValue(mass,list_int_name,UID,"m_brutto");
		SILsetValue(volume,list_int_name,UID,"volume");
	break;
	}
	
	if (!refUpdateInProgress && fix_stoch===false) {
		var ref_amount=parseFloat(getInputValue("ref_amount")),ref_amount_unit=getInputValue("ref_amount_unit");
		var stoch_coeff=get_stoch_coeff_from_amount(rc_amount,rc_amount_unit,ref_amount,ref_amount_unit);
		if (isNaN(stoch_coeff)) {
			stoch_coeff="";
		}
		SILsetValue(stoch_coeff,list_int_name,UID,"stoch_coeff");
	}
	
	if (list_int_name=="reagents") {
		updateGCyield(); // all
	}
	else {
		rxnProductChanged(list_int_name,UID,int_name); // single line
	}
	
	updateInProgress=false;
	
	// wenn stoch_coeff gesetzt, update der Ansatzgröße auslösen, updateInProgress muß false sein
	if (!refUpdateInProgress && fix_stoch) {
		var stoch_coeff=parseFloat(SILgetValue(list_int_name,UID,"stoch_coeff"));
		if (!isNaN(stoch_coeff)) { // Ansatz anpassen
			var ref_amount_unit=getInputValue("ref_amount_unit");
			// berechnen
			//~ var ref_amount=get_ref_amount_from_amount(ref_amount_unit,rc_amount,rc_amount_unit,stoch_coeff);
			var ref_amount=get_ref_amount_from_amount(rc_amount,stoch_coeff);
			var values={"ref_amount":ref_amount,"ref_amount_unit":rc_amount_unit};
			setControlValues(values);
			refValueChanged(list_int_name,UID);
		}
	}
}

function stochCoeffChanged(list_int_name,UID,int_name) { // einzelner geändert
	var stoch_coeff=parseFloat(SILgetValue(list_int_name,UID,"stoch_coeff")),ref_amount=parseFloat(getInputValue("ref_amount")),ref_amount_unit=getInputValue("ref_amount_unit"),rc_amount_unit=SILgetValue(list_int_name,UID,"rc_amount_unit");
	
	if (isNaN(stoch_coeff)) {
		return;
	}
	
	if (refUpdateInProgress) {
		return;
	}
	refUpdateInProgress=true;
	
	// berechnen
	var rc_amount=get_amount_from_stoch_coeff(rc_amount_unit,ref_amount,ref_amount_unit,stoch_coeff);
	SILsetValue(rc_amount,list_int_name,UID,"rc_amount");
	if (list_int_name=="products") {
		rxnProductChanged(list_int_name,UID,"rc_amount");
	}
	else {
		rxnValueChanged(list_int_name,UID,"rc_amount");
	}
	//~ SILObjTouchOnchange(list_int_name,UID,"rc_amount");
	refUpdateInProgress=false;
}

function refValueChanged(skip_list_int_name,skipUID) { // do not update skipUID again, otherwise endless loop
	if (refUpdateInProgress) {
		return;
	}
	refUpdateInProgress=true;
	
	var ref_amount=getInputValue("ref_amount"),ref_amount_unit=getInputValue("ref_amount_unit"); // Ansatzgröße
	var list_int_name,UID,int_name="rc_amount",stoch_coeff,rc_amount,rc_amount_unit;
	
	// Listen durchgehen
	var list_int_names=["reactants","reagents","products"];
	for (var c=0,max2=list_int_names.length;c<max2;c++) {
		list_int_name=list_int_names[c];
		for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
			UID=controlData[list_int_name]["UIDs"][b];
			if (list_int_name==skip_list_int_name && UID==skipUID) {
				continue;
			}
			// wenn stoch_coeff gesetzt, n darüber setzen und update auslösen
			stoch_coeff=parseFloat(SILgetValue(list_int_name,UID,"stoch_coeff"));
			if (isNaN(stoch_coeff)) {
				// ansonsten stoch_coeff über n berechnen (verwirrend, deaktiviert)
				/*
				var rc_amount=parseFloat(SILgetValue(list_int_name,UID,"rc_amount")),rc_amount_unit=SILgetValue(list_int_name,UID,"rc_amount_unit");
				var stoch_coeff=get_stoch_coeff_from_amount(rc_amount,rc_amount_unit,ref_amount,ref_amount_unit);
				SILsetValue(stoch_coeff,list_int_name,UID,"stoch_coeff");
				*/
			}
			else {
				// stoffmenge setzen
				rc_amount_unit=SILgetValue(list_int_name,UID,"rc_amount_unit");
				rc_amount=get_amount_from_stoch_coeff(rc_amount_unit,ref_amount,ref_amount_unit,stoch_coeff);
				SILsetValue(rc_amount,list_int_name,UID,int_name);
				
				// update der restlichen Werte auslösen
				rxnValueChanged(list_int_name,UID,int_name);
			}
			
			// tell system about change
			SILsetDesiredAction(list_int_name,UID,"update");
		}
	}
	
	refUpdateInProgress=false;
}

function thSelectChange(list_int_name,int_name,additional_list_int_names) { // <select im spaltenkopf geändert
	var value=getInputValue(list_int_name+"_"+int_name);
	
	// make list
	if (additional_list_int_names==undefined) {
		var additional_list_int_names=[];
	}
	
	// add 1st element
	additional_list_int_names.unshift(list_int_name);
	
	// go thru list
	for (var b=0,max=additional_list_int_names.length;b<max;b++) { // additional_list_int_names[b]
		//~ alert(additional_list_int_names[b]+" "+int_name);
		if (value=="") { // custom
			// show respective field
			SILsetFieldVisible(additional_list_int_names[b],int_name,undefined,true);
		}
		else {
			// set respective field to selected value
			SILsetValuesField(additional_list_int_names[b],int_name,undefined,value); // includes touch if change occurs
			//~ SILfieldTouchOnchange(additional_list_int_names[b],int_name);
			// hide respective fields
			SILsetFieldVisible(additional_list_int_names[b],int_name,undefined,false);
		}
	}
}

function updateTh(list_int_name) { // bei Laden <selects in spaltenköpfen auslösen, bei onChange wird direkt thSelectChange aufgerufen
	touchOnChange(list_int_name+"_rc_amount_unit");
	touchOnChange(list_int_name+"_mass_unit");
	touchOnChange(list_int_name+"_volume_unit");
	touchOnChange(list_int_name+"_rc_conc_unit");
}

function SILmouseoverCHN(obj,list_int_name,UID,int_name,thisReadOnly) {
	if (obj) {
		showCHNTooltip(obj,SILgetValue(list_int_name,UID,int_name));
	}
}

function SILmouseoverRS(obj,list_int_name,UID,int_name,thisReadOnly) {
	var type;
	if (obj) {
		switch (int_name) {
		case "safety_r":
			type="R";
		break;
		case "safety_s":
			type="S";
		break;
		case "safety_h":
			type="H";
		break;
		case "safety_p":
			type="P";
		break;
		default:
			return;
		}
		showRSTooltip(obj,type,SILgetValue(list_int_name,UID,int_name));
	}
}

function SILhideOverlay(obj,list_int_name,UID,int_name,thisReadOnly) {
	hideOverlay();
}

function prepareMakeChemicalStorage(list_int_name,UID,int_name) {
	// open editWin.php and get the rest using opener, works even without saving
	//~ var url="editWin.php?mode=rc_to_chemical_storage&list_int_name="+list_int_name+"&UID="+UID;
	var url="edit.php?table=chemical_storage&list_int_name="+list_int_name+"&UID="+UID+"&lab_journal_id="+getControlValue("lab_journal_id")+"&from_reaction_id="+getControlValue("reaction_id");
	var molecule_id=SILgetValue(list_int_name,UID,"molecule_id");
	if (molecule_id!=undefined) {
		url+="&molecule_id="+molecule_id;
	}
	var smiles_stereo=SILgetValue(list_int_name,UID,"smiles_stereo");
	if (smiles_stereo!=undefined) {
		url+="&smiles_stereo="+smiles_stereo;
	}
	window.open(url);
}

function gotoProducts() {
	//~ var url="list.php?table=chemical_storage&query=<0>&crit0=chemical_storage.from_reaction_id&op0=eq&val0="+a_pk+"&dbs="+a_db_id+"&"+pageParams;
	var url="list.php?"+getSelfRef(["~script~","table","query","dbs","cached_query"])+"&table=chemical_storage&query=<0>&crit0=chemical_storage.from_reaction_id&op0=eq&val0="+a_pk+"&dbs="+a_db_id;
	window.location.href=url;
}