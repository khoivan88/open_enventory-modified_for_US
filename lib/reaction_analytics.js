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
chroma_lists=["products","reactants"];

function gotoSpectrum(UID,switchView) {
	var list_int_name="analytical_data";
	if (switchView) {
		activateEditView("analytics");
	}
	SILscrollIntoView(list_int_name,UID);
	/* autoScrollInProgress=true;
	if (readOnly) {
		document.location.hash="ro_"+list_int_name+"_"+UID;
	}
	else {
		//~ document.location.hash=list_int_name+"_"+UID;
		makeVisible("tr_"+list_int_name+"_"+UID+"_0");
	}
	autoScrollInProgress=false; */
}

function getSpecNav() { // show list of spectra at fixed position top right with links
	var list_int_name="analytical_data",UID,hidden,retval="<table class=\"rxnlabel\"><tbody>";
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		UID=controlData[list_int_name]["UIDs"][b];
		hidden=controlData[list_int_name]["hidden"][b];
		retval+="<tr><td>"+SILgetValue(list_int_name,UID,"analytics_type_name")+"</td><td>"+SILgetValue(list_int_name,UID,"analytics_device_name")+"</td><td>"+SILgetValue(list_int_name,UID,"analytics_method_name")+"</td><td>";
		if (hidden) {
			retval+="<i>";
		}
		retval+="<a href=\"Javascript:gotoSpectrum(&quot;"+UID+"&quot;,"+hidden+")\">"+SILgetValue(list_int_name,UID,"analytical_data_identifier")+"</a>";
		if (hidden) {
			retval+="</i>";
		}
		retval+="</td></tr>";
	}
	retval+="</tbody></table>";
	return retval;
}

function showSpecNav(obj) {
	var overlayObj=$("overlay");
	if (!overlayObj) {
		return;
	}
	overlayObj.innerHTML=getSpecNav();
	overlayObj.style.display="";
	var dim=getElementSize(overlayObj);
	showOverlayObj(obj,overlayObj,-dim[0],0,0);
}

function isDefault(list_int_name,UID) {
	return SILgetChecked(list_int_name,UID,"default_for_type");
}

function checkDefault(list_int_name,UID,check) {
	SILsetChecked(check,list_int_name,UID,"default_for_type");
}

function addMainAnalytics(list_int_name,UID,desired_action) { // line added
	if (desired_action!="add") {
		return;
	}
	// bin ich der default Eintrag? Ja => check, uncheck other
	var type_method=getTypeMethod(list_int_name,UID);
	var defaultUID=getIdentifierAnalyticsUID(type_method[0],type_method[1]);
	if (defaultUID && UID==defaultUID) {
		checkMainAnalytics(defaultUID,type_method[0],type_method[1]);
	}
	else {
		// gibt es noch anderen Eintrag für diesen Typ? Nein => check
		if (getDefaultAnalyticsUID(type_method[0],type_method[1])==false) {
			checkDefault(list_int_name,UID,true);
		}
	}
}

function updateMainAnalytics(list_int_name,UID,int_name) { // checkbox clicked
	// bin ich gecheckt? Nein => raus
	if (!isDefault(list_int_name,UID)) {
		return;
	}
	// Ja, uncheck other
	var type_method=getTypeMethod(list_int_name,UID);
	checkMainAnalytics(UID,type_method[0],type_method[1]);	
}

function delMainAnalytics(list_int_name,UID) { // Zeile gelöscht
	// war ich gecheckt? Nein => raus
	if (!isDefault(list_int_name,UID)) {
		return;
	}
	// Ja => default checken
	var type_method=getTypeMethod(list_int_name,UID);
	var defaultUID=getIdentifierAnalyticsUID(type_method[0],type_method[1]);
	if (defaultUID) {
		checkMainAnalytics(defaultUID,type_method[0],type_method[1]);
	}
}

function getTypeMethod(list_int_name,UID) {
	var this_analytics_type_code=SILgetValue(list_int_name,UID,"analytics_type_code"),this_analytics_method_name="";
	this_analytics_type_code=this_analytics_type_code.toLowerCase();
	if (in_array(this_analytics_type_code,method_aware_types)) { // NMR unterscheidet nach 1H,13C
		this_analytics_method_name=SILgetValue(list_int_name,UID,"analytics_method_name");
		this_analytics_method_name=this_analytics_method_name.toLowerCase();
	}
	return [this_analytics_type_code,this_analytics_method_name];
}

function setGCYield(gc_yield,list_int_name,UID) { // do not overwrite values
	var int_name="gc_yield";
	var old_val=SILgetValue(list_int_name,UID,int_name);
	if (gc_yield!="" || old_val==="") {
		SILsetValue(gc_yield,list_int_name,UID,int_name);
		if (gc_yield!==old_val) { // updateInProgress==false at startup
			SILsetDesiredAction(list_int_name,UID,"update",true); // needs force
		}
	}
}

function checkMainAnalytics(checkUID,analytics_type_code,analytics_method_name) { // uncheckt alle einträge mit analytics_type_code außer checkUID
	//~ analytics_type_code=analytics_type_code.toLowerCase(),analytics_method_name=analytics_method_name.toLowerCase();
	var list_int_name="analytical_data",int_name="default_for_type",list_int_name2="products",rc_uid,gc_yield;
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b];
		var this_analytics_type_code=SILgetValue(list_int_name,UID,"analytics_type_code");
		if (this_analytics_type_code.toLowerCase()!=analytics_type_code) {
			continue;
		}
		if (analytics_method_name!="") {
			var this_analytics_method_name=SILgetValue(list_int_name,UID,"analytics_method_name");
			if (this_analytics_method_name.toLowerCase()!=analytics_method_name) {
				continue;
			}
		}
		checkDefault(list_int_name,UID,checkUID==UID);
		
		if (checkUID==UID) {
			// Werte in products setzen
			var rc_uid_arr=a(controlData,list_int_name2,"UIDs");
			for (var c=0,max2=rc_uid_arr.length;c<max2;c++) {
				rc_uid=rc_uid_arr[c];
				gc_yield=getInputValue("gc_peak_gc_yield_"+UID+"_"+rc_uid);
				setGCYield(gc_yield,list_int_name2,rc_uid);
			}
		}
	}
}

function getDefaultAnalyticsUID(analytics_type_code,analytics_method_name) { // tatsächlich default
	//~ analytics_type_code=analytics_type_code.toLowerCase(),analytics_method_name=analytics_method_name.toLowerCase();
	var list_int_name="analytical_data";
	if (a(controlData,list_int_name,"UIDs")=="") {
		return false;
	}
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b];
		var this_analytics_type_code=SILgetValue(list_int_name,UID,"analytics_type_code");
		if (this_analytics_type_code.toLowerCase()!=analytics_type_code) {
			continue;
		}
		if (analytics_method_name!="") {
			var this_analytics_method_name=SILgetValue(list_int_name,UID,"analytics_method_name");
			if (this_analytics_method_name.toLowerCase()!=analytics_method_name) {
				continue;
			}
		}
		if (isDefault(list_int_name,UID)) {
			return UID;
		}
	}
	return false;
}

function getIdentifierAnalyticsUID(analytics_type_code,analytics_method_name) { // per identifier default
	//~ analytics_type_code=analytics_type_code.toLowerCase(),analytics_method_name=analytics_method_name.toLowerCase();
	var list_int_name="analytical_data";
	if (a(controlData,list_int_name,"UIDs")=="") {
		return false;
	}
	var default_identifier=getDefaultAnalyticsIdentifier(analytics_type_code,analytics_method_name);
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b];
		var this_analytics_type_code=SILgetValue(list_int_name,UID,"analytics_type_code");
		if (this_analytics_type_code.toLowerCase()!=analytics_type_code) {
			continue;
		}
		if (analytics_method_name!="") {
			var this_analytics_method_name=SILgetValue(list_int_name,UID,"analytics_method_name");
			if (this_analytics_method_name.toLowerCase()!=analytics_method_name) {
				continue;
			}
		}
		var this_analytical_data_identifier=SILgetValue(list_int_name,UID,"analytical_data_identifier");
		if (this_analytical_data_identifier.toLowerCase()==default_identifier) {
			return UID;
		}
	}
	return false;
}

function getDefaultAnalyticsIdentifier(analytics_type_code,analytics_method_name) { // nicht case-sensitive
	//~ analytics_type_code=analytics_type_code.toLowerCase(),analytics_method_name=analytics_method_name.toLowerCase();
	var lab_journal_code=getInputValue("lab_journal_code"),nr_in_lab_journal=getInputValue("nr_in_lab_journal");
	lab_journal_code=lab_journal_code.toLowerCase();
	switch (analytics_type_code) {
	case "gc":
	// GC: XYZ123.D
		return lab_journal_code+nr_in_lab_journal+".d";
	break;
	case "nmr":
	switch (analytics_method_name) {
		case "1h":
		// H-NMR: xyz123-h
			return lab_journal_code+nr_in_lab_journal+"-h";
		break;
		case "13c":
		// C-NMR: xyz123-c
			return lab_journal_code+nr_in_lab_journal+"-c";
		break;
	}
	break;
	case "gc-ms":
	// GC-MS: xyz123.sms
		return lab_journal_code+nr_in_lab_journal+".sms";
	break;
	}
}

function getInteractiveViewerBtn(db_id,analytical_data_id,text,list_int_name,UID) {
	return "<a class=\"imgButtonSm\" href=\"javascript:displayInteractiveViewer("+db_id+","+analytical_data_id+","+fixQuot(list_int_name)+","+fixQuot(UID)+");\"><img border=\"0\" alt="+fixStr(text)+" title="+fixStr(text)+" src=\"lib/analytical_data_sm.png\"></a>";
}

function displayInteractiveViewer(db_id,analytical_data_id,list_int_name,UID) {
	window.open("graphViewer.php?db_id="+db_id+"&analytical_data_id="+analytical_data_id+"&image_no="+a(controlData,list_int_name,"data",UID,"current_image"), "", "width=850, height=650, toolbar=no, location=no, titlebar=no, menubar=no, resizable=no, scrollbars=no");
}

function refreshAnalyticalDataImgId(list_int_name,UID,int_name) { // leave a_timestamp untouched in general
	var temp=a_timestamp;
	a_timestamp=Number(new Date());
	as("controlData",0,list_int_name,"data",UID,"current_image"); // reset
	SILsetValueUID(list_int_name,UID,undefined,undefined,"analytical_data_graphics_blob",undefined,{db_id:a_db_id,analytical_data_id:SILgetValue(list_int_name,UID,"analytical_data_id")});
	var iHTML=getCurrentImage(0,a(controlData,list_int_name,"data",UID,"image_count"));
	int_name="analytical_data_image";
	SILsetSpan(iHTML,list_int_name,UID,int_name,undefined,true);
	SILsetSpan(iHTML,list_int_name,UID,int_name,undefined,false);
	a_timestamp=temp;
	valChanged();
}

function getAnalyticalDataImgId(analytical_data_id) { // id of the img
	return "analyticalDataImg"+analytical_data_id;
}

function showAnalyticalDataImg(analytical_data_id,show) {
	var id=getAnalyticalDataImgId(analytical_data_id);
	visibleObj(id,show);
	visibleObj("ro_"+id,show);
	visibleObj(id+"_btn",!show);
	visibleObj("ro_"+id+"_btn",!show);
}

function unlinkAnalyticalData(list_int_name,UID,int_name) {
	if (!confirm(s("delWarning"))) {
		return;
	}
	var pk=SILgetValue(list_int_name,UID,"analytical_data_id");
	if (pk=="") {
		return;
	}
	//~ var url="editAsync.php?desired_action=unlink&"+linkParams+"&table=analytical_data&db_id=-1&pk="+pk;
	var url="editAsync.php?"+getSelfRef(["~script~","table"])+"&desired_action=unlink&table=analytical_data&db_id=-1&pk="+pk;
	setFrameURL("comm",url);
	// remove from list
	SILdelLine(list_int_name,UID);
	return false;
}

function delAnalyticalData(list_int_name,UID,int_name) {
	var pk=SILgetValue(list_int_name,UID,"analytical_data_id");
	if (pk=="") {
		return;
	}
	if (confirm(s("delWarning"))) { // löschen?
		//~ var url="editAsync.php?desired_action=del&"+linkParams+"&table=analytical_data&db_id=-1&pk="+pk;
		var url="editAsync.php?"+getSelfRef(["~script~","table"])+"&desired_action=del&table=analytical_data&db_id=-1&pk="+pk;
		setFrameURL("comm",url);
		return true;
	}
	return false;
}

function editAnalyticalData(list_int_name,UID,int_name) {
	var analytical_data_id=SILgetValue(list_int_name,UID,"analytical_data_id");
	if (analytical_data_id==undefined) {
		analytical_data_id="";
	}
	//~ var params="&"+linkParams+"&autoNew=true&edit=true&editDbId=-1&editPk="+analytical_data_id+"&db_id="+a_db_id+"&pk="+a_pk+"&analytics_type_name="+currentView;
	var params="&"+getSelfRef(["~script~","table","editDbId","editPk","cached_query"])+"&autoNew=true&edit=true&editDbId=-1&editPk="+analytical_data_id+"&db_id="+a_db_id+"&pk="+a_pk+"&analytics_type_name="+currentView;
	openSearchWin(list_int_name,UID,int_name,undefined,"analytical_data",params);
}

// Zuordnung analytik reaction_chemical

function getSubstanceReport(list_int_name,UID,int_name) {
	var url="editWin.php?mode=substance_report&list_int_name="+list_int_name+"&UID="+UID+"&db_id="+a_db_id+"&readOnly="+readOnly+"&reaction_id="+a_pk+"&reaction_chemical_id="+SILgetValue(list_int_name,UID,"reaction_chemical_id");
	window.open(url);
}

function getCHNForm(list_int_name,UID,int_name) {
	var url="editWin.php?mode=chn&list_int_name="+list_int_name+"&UID="+UID;
	window.open(url);
}

function getRcUID(reaction_chemical_id) {
	var rc_UID,list_int_names=["reactants","reagents","products"];
	if (readOnly) { // Liste nicht geladen, zuordnung über reaction_chemical_id
		return reaction_chemical_id;
	}
	for (var b=0,max=list_int_names.length;b<max && !rc_UID;b++) {
		// alert(list_int_names[b]);
		rc_UID=SILfindValue(list_int_names[b],"reaction_chemical_id",undefined,reaction_chemical_id);
		if (rc_UID!=false) {
			return rc_UID;
		}
	}
}

function updateGCyield(UID,rc_list_int_name,rc_uid) { // UID: analytical_data, rc_uid: reaction_chemical
	// Y=m(Std)	*	(F(Std)/	F(Prod)) * (a(Prod)/	a(Std)) / 	(n(Prod) *		mw(Prod))
	//	reac_prop	line0	linex	linex	line0	prod (def 1mmol)	prod
	var rc_uid_arr=[],rc_list_int_names_arr=[],UIDs=[],list_int_name="analytical_data",std_list_int_name="reagents",ref_amount=parseFloat(getInputValue("ref_amount")),ref_amount_unit=getInputValue("ref_amount_unit");
	
	if (rc_uid=="" || rc_uid==undefined) {
		// Standard line, update all
		for (var b=0,max=chroma_lists.length;b<max;b++) { // products,reactants
			var tempArray=a(controlData,chroma_lists[b],"UIDs");
			rc_list_int_names_arr=rc_list_int_names_arr.concat(array_fill(0,tempArray.length,chroma_lists[b]));
			rc_uid_arr=rc_uid_arr.concat(tempArray);
		}
	}
	else {
		// speed tuning
		if (updateInProgress) {
			return;
		}
		
		rc_list_int_names_arr.push(rc_list_int_name);
		rc_uid_arr.push(rc_uid);
	}
	
	if (UID=="" || UID==undefined) {
		// Standard line, update all
		UIDs=a(controlData,list_int_name,"UIDs");
	}
	else {
		UIDs.push(UID);
	}
	
	for (var c=0,max2=UIDs.length;c<max2;c++) { // analytical_data durchgehen
		UID=UIDs[c];
		var stdUID=getInputValue("gc_peak_std_uid_"+UID+"_");
		var mStd=ifempty(SILgetValue(std_list_int_name,stdUID,"m_brutto")*getUnitFactor(SILgetValue(std_list_int_name,stdUID,"mass_unit")),40); // 40 mg
		var fStd=ifempty(getInputValue("gc_peak_response_factor_"+UID+"_"),1),fProd,aProd,nProd,mwProd,aStd=getInputValue("gc_peak_area_percent_"+UID+"_");
		
		for (var b=0,max=rc_uid_arr.length;b<max;b++) {
			list_int_name=rc_list_int_names_arr[b];
			rc_uid=rc_uid_arr[b];
			fProd=getInputValue("gc_peak_response_factor_"+UID+"_"+rc_uid);
			
			setiHTML("rw_gc_peak_approx_resp_"+UID+"_"+rc_uid,"("+s("approx")+" "+getApproxF(SILgetValue(list_int_name,rc_uid,"emp_formula"))+")" );
			if (isEmptyStr(fProd)) {
				fProd=1.0;
			}
			aProd=getInputValue("gc_peak_area_percent_"+UID+"_"+rc_uid);
			
			if (isEmptyStr(aProd)) {
				if (list_int_name=="products") {
					gc_yield=0;
					if (isDefault("analytical_data",UID)) { 
						// set Value in List
						setGCYield(gc_yield,list_int_name,rc_uid);
					}
				}
				else {
					gc_yield="";
				}
			}
			else {
				nProd=SILgetValue(list_int_name,rc_uid,"rc_amount");
				if (!isEmptyStr(nProd)) {
					nProd*=getUnitFactor(SILgetValue(list_int_name,rc_uid,"rc_amount_unit"));
				}
				else if (!isNaN(ref_amount)) {
					nProd=ref_amount*getUnitFactor(ref_amount_unit);
				}
				else {
					nProd=0.001; // 1 mmol
				}
				mwProd=SILgetValue(list_int_name,rc_uid,"mw");
				
				var gc_yield,divisor=fProd*aStd*nProd*mwProd;
				//~ alert(updateGCyield.caller+"~"+list_int_name+"_"+rc_uid+"/"+fProd+"X"+aStd+"Y"+nProd+"Z"+mwProd);
				
				if (divisor>0) {
					gc_yield=round(100*mStd*fStd*aProd/divisor,2);
					if (isDefault("analytical_data",UID)) { 
						// set Value in List
						setGCYield(gc_yield,list_int_name,rc_uid);
					}
					setInputValue("gc_peak_gc_yield_"+UID+"_"+rc_uid,gc_yield);
				}
				else {
					gc_yield="?";
				}
			}
			
			setiHTML("rw_gc_peak_gc_yield_"+UID+"_"+rc_uid,gc_yield);
		}
	}
}

function addStandard() {
	setFrameURL("comm","chooseAsync.php?desired_action=addStandard");
}

function openCalcResponse() {
	window.open("editWin.php?mode=response_factor");
}

function transferGCs(settings) {
	// get LabJ id
	var lab_journal_id=getInputValue("lab_journal_id");
	if (lab_journal_id==false) {
		return;
	}
	// show info
	showMessage(s("please_wait"));
	// init transfer
	prepareUpdate(a_db_id,a_pk,0);
	//~ var url="editAsync.php?"+linkParams+"&desired_action=transferGCs&table=reaction&db_id="+a_db_id+"&pk="+a_pk+"&lab_journal_id="+lab_journal_id+"&settings="+def0(settings)+prepareAsync(); // "&age_seconds="+getAgeSeconds();
	var url="editAsync.php?"+getSelfRef(["~script~","table"])+"&desired_action=transferGCs&table=reaction&db_id="+a_db_id+"&pk="+a_pk+"&lab_journal_id="+lab_journal_id+"&settings="+def0(settings)+prepareAsync(); // "&age_seconds="+getAgeSeconds();
	setFrameURL("edit",url);
}

function getAutoGCParams(UID) {
	var analytics_type_id=SILgetValue("analytical_data",UID,"analytics_type_id"),analytics_device_id=SILgetValue("analytical_data",UID,"analytics_device_id"),analytics_method_id=SILgetValue("analytical_data",UID,"analytics_method_id");
	if (isEmptyStr(analytics_method_id)) {
		return "";
	}
	//~ var molecule_id_arr=SILgetValueArray(list_int_name,"molecule_id",true);
	var smiles_stereo_arr=[];
	var smiles_id_arr=[];
	for (c=0,max2=chroma_lists.length;c<max2;c++) {
		var list_int_name=chroma_lists[c];
		smiles_stereo_arr=smiles_stereo_arr.concat(SILgetValueArray(list_int_name,"smiles_stereo",undefined,true));
		smiles_id_arr=smiles_id_arr.concat(SILgetValueArray(list_int_name,"smiles",undefined,true));
	}
	
	// get molecule_id for selected standard
	var std_UID=getInputValue("gc_peak_std_uid_"+UID+"_");
	if (std_UID) {
		//~ var std_molecule_id=SILgetValue("reagents",std_UID,"molecule_id");
		smiles_stereo_arr.push(SILgetValue("reagents",std_UID,"smiles_stereo"));
		smiles_id_arr.push(SILgetValue("reagents",std_UID,"smiles"));
	}
	if (smiles_stereo_arr.length>0 || smiles_id_arr.length>0) {
		// &molecule_id="+molecule_id_arr.join(",")+"
		return "UID="+UID+"&smiles_stereo="+encodeURIComponent(smiles_stereo_arr.join(","))+"&smiles="+encodeURIComponent(smiles_id_arr.join(","))+"&analytics_type_id="+analytics_type_id+"&analytics_device_id="+analytics_device_id+"&analytics_method_id="+analytics_method_id;
	}
	return "";
}

function crossGC(UID) {
	if (readOnly) {
		return;
	}
	var url="editWin.php?mode=gc&UID="+UID+"&db_id="+a_db_id+"&analytical_data_id="+SILgetValue("analytical_data",UID,"analytical_data_id");
	window.open(url);
}

function procRetentionTime(UID,smiles_stereo,smiles,retention_time,response_factor) {
	var list_int_names=["reagents"],something_found=false,int_names=["smiles_stereo","smiles"];
	list_int_names=list_int_names.concat(chroma_lists);
	
	for (d=0,max3=int_names.length;d<max3;d++) {
		var int_name=int_names[d];
		for (var c=0,max2=list_int_names.length;c<max2;c++) {
			list_int_name=list_int_names[c];
			for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
				if (c==0) {
					if (!SILfindValue(list_int_name,int_name,undefined,smiles_stereo)) {
						continue;
					}
					var rc_uid="";
				}
				else {
					var rc_uid=controlData[list_int_name]["UIDs"][b];
				}
				if (c==0 || smiles_stereo==SILgetValue(list_int_name,rc_uid,int_name)) {
					something_found=true;
					setInputValue("gc_peak_retention_time_"+UID+"_"+rc_uid,retention_time);
					if (getInputValue("gc_peak_response_factor_"+UID+"_"+rc_uid)=="") { // default response factor, do not overwrite resp fac
						setInputValue("gc_peak_response_factor_"+UID+"_"+rc_uid,response_factor);
					}
					updateRetentionTime(UID,list_int_name,rc_uid);
				}
			}
		}
		if (something_found) {
			return;
		}
		else {
			smiles_stereo=smiles;
		}
	}
}

/* function procRetentionTimes(UID,retention_time_data) {
	// durchgehen und Werte in Felder eintragen, aktualisieren
	for (var b=0,max=retention_time_data.length;b<max;b++) {
		var molecule_id=retention_time_data[b]["molecule_id"];
		var rc_uid=SILfindValue("products","molecule_id",molecule_id);
		if (rc_uid) {
			setInputValue("gc_peak_retention_time_"+UID+"_"+rc_uid,retention_time_data[b]["retention_time"]);
			updateRetentionTime(UID,rc_uid);
		}
	}
}*/

function autoGC(UID) {
	if (readOnly) {
		return;
	}
	// für analytics_method_id und alle molecule_ids retention_time suchen, in peak-Tabelle wiederfinden
	var params=getAutoGCParams(UID);
	if (params!="") {
		var url="chooseAsync.php?desired_action=getRetentionTimes&"+params;
		setFrameURL("comm",url);
	}
}

function getGCpeakData(UID) {
	var properties_blob=SILgetValue("analytical_data",UID,"analytical_data_properties_blob");
	if (!properties_blob) {
		return;
	}
	//~ var properties_obj=eval("("+properties_blob+")");
	var properties_obj=JSON.parse(properties_blob);
	// Eintrag in retention_time schreiben/updaten für molecule_id/analytics_method_id
	if (!properties_obj) {
		return;
	}
	return properties_obj["peaks"];
}

function getClosestGCPeak(peaks,ret_time) {
	ret_time=parseFloat(ret_time);
	if (isNaN(ret_time) || ret_time==0) {
		return;
	}
	var deviation=Number.MAX_VALUE,peakNo,thisDeviation;
	for (var b=0,max=peaks.length;b<max;b++) {
		var time=parseFloat(peaks[b]["time"]),width=parseFloat(peaks[b]["width"])+0.05;
		if (isNaN(width)) {
			width=0.05;
		}
		thisDeviation=Math.abs(ret_time-time);
		if (thisDeviation<=width && thisDeviation<deviation) {
			// peak is better
			deviation=thisDeviation;
			peakNo=b;
			if (thisDeviation==0.0) {
				break;
			}
		}
	}
	return peakNo;
}

function updateRetentionTime(UID,rc_list_int_name,rc_uid) {
	//~ alert(updateRetentionTime.caller);
	// Flächen% raussuchen und eintragen
	var ret_time=fixNull(getInputValue("gc_peak_retention_time_"+UID+"_"+rc_uid));
	if (isNaN(ret_time)) {
		// reset area%
		setInputValue("gc_peak_area_percent_"+UID+"_"+rc_uid,"");
		updateGCyield(UID,rc_list_int_name,rc_uid);
		return;
	}
	var peaks=getGCpeakData(UID);
	if (!peaks) {
		return;
	}
	peakNo=getClosestGCPeak(peaks,ret_time);
	if (peakNo!=undefined) { // peak found
		setInputValue("gc_peak_retention_time_"+UID+"_"+rc_uid,peaks[peakNo]["time"]);
		setInputValue("gc_peak_area_percent_"+UID+"_"+rc_uid,peaks[peakNo]["rel_area"]);
		if (!getInputValue("gc_peak_gc_peak_comment_"+UID+"_"+rc_uid)) {
			setInputValue("gc_peak_gc_peak_comment_"+UID+"_"+rc_uid,peaks[peakNo]["comment"]);
		}
		updateGCyield(UID,rc_list_int_name,rc_uid);
	}
	valChanged();
}

function getGcInput(fieldName,uid,rc_list_int_name,rc_uid,value,size) {
	var type="text",name=fixStr("gc_peak_"+fieldName+"_"+uid+"_"+rc_uid),retval="",onChange="";
	if (size==0) {
		type="hidden";
	}
	else {
		retval+="</td><td onClick=\"f1(event,this)\">";
	}
	switch (fieldName) {
	case "retention_time":
		onChange="updateRetentionTime("+fixQuot(uid)+","+fixQuot(rc_list_int_name)+","+fixQuot(rc_uid)+")"; // use JSON-encoded data to find peak area
	break;
	case "area_percent":
	case "response_factor":
		onChange="updateGCyield("+fixQuot(uid)+","+fixQuot(rc_list_int_name)+","+fixQuot(rc_uid)+");";
	break;
	}
	retval+="<input type="+fixStr(type)+" name="+name+" id="+name+" value="+fixStr(value)+" size="+fixStr(size)+" onChange=\""+onChange+"\" onKeyUp=\"valChanged(this);\" onFocus=\"hi(this,true);fC(event,this);\" onBlur=\"hi(this,false);valChanged(this);\">";
	return retval;
}

function getGCRwLine(name,retention_time,area_percent,gc_yield,response_factor,gc_peak_comment,uid,rc_list_int_name,rc_uid) {
	var retval="<tr><td>"+name+getGcInput("retention_time",uid,rc_list_int_name,rc_uid,round(retention_time,2),3);
	if (rc_uid!="") {
		retval+=getGcInput("gc_yield",uid,rc_list_int_name,rc_uid,round(gc_yield,2),0); // hidden
	}
	retval+=getGcInput("area_percent",uid,rc_list_int_name,rc_uid,round(area_percent,2),2)+"</td><td><span id=\"rw_gc_peak_gc_yield_"+uid+"_"+rc_uid+"\"></span>"+getGcInput("response_factor",uid,rc_list_int_name,rc_uid,round(response_factor,3),2)+"<span id=\"rw_gc_peak_approx_resp_"+uid+"_"+rc_uid+"\"></span>"+getGcInput("gc_peak_comment",uid,rc_list_int_name,rc_uid,defBlank(gc_peak_comment),5)+"</td></tr>";
	return retval;
}

function getGCRoLine(name,retention_time,area_percent,gc_yield,response_factor,gc_peak_comment) {
	return "<tr><td>"+name+"</td><td>"+round(retention_time,2)+"</td><td>"+round(area_percent,2)+"</td><td>"+round(gc_yield,1)+"</td><td>"+round(response_factor,3)+"</td><td>"+defBlank(gc_peak_comment)+"</td></tr>";
}

function getStdSelect(UID) {
	var std_id=fixStr("gc_peak_std_uid_"+UID+"_");
	return "<select id="+std_id+" name="+std_id+" onChange=\"updateGCyield("+fixQuot(UID)+",&quot;&quot;,&quot;&quot;);valChanged(this)\"></select>";
}

/* function setAllStd(rc_uid) {
	// Tetradecan bei reagents suchen
	//~ var rc_uid=SILfindValue("reagents","smiles_stereo",std_smiles);
	// nicht gefunden, raus
	if (rc_uid==false) {
		return;
	}
	var list_int_name="analytical_data",uid;
	UIDs=a(controlData,list_int_name,"UIDs");
	for (var b=0,max=UIDs.length;b<max;b++) {
		uid=UIDs[b];
		var analytics_type_name=SILgetValue(list_int_name,uid,"analytics_type_name");
		if (analytics_type_name && analytics_type_name.toLowerCase()=="gc") {
			var obj=$("gc_peak_std_uid_"+uid+"_");
			if (!obj) {
				continue;
			}
			selAddOption(obj,rc_uid,"",true);
			alert(uid+"A"+rc_uid);
			//~ setInputValue("gc_peak_std_uid_"+uid+"_",rc_uid);
		}
	}
} */

function getCurrentImage(current,image_count) {
	return (current+1)+"/"+image_count;
}

function gotoAnalyticalDataImage(list_int_name,UID,delta) {
	// save scroll position
	saveScrollPos();
	autoScrollInProgress=true;
	
	// get total
	var int_name="analytical_data_graphics_blob",dataHash="current_image",image_count=a(controlData,list_int_name,"data",UID,"image_count"),analytical_data_id=SILgetValue(list_int_name,UID,"analytical_data_id");
	
	// get current
	var current=(Number(a(controlData,list_int_name,"data",UID,dataHash))+delta+image_count)%image_count;
	
	// set image
	var iHTML=getAnalyticalDataImg(list_int_name,UID,int_name,a_db_id,analytical_data_id,current,a_timestamp);
	SILsetSpan(iHTML,list_int_name,UID,int_name,undefined,true);
	SILsetSpan(iHTML,list_int_name,UID,int_name,undefined,false);
	
	// set display of current img
	iHTML=getCurrentImage(current,image_count);
	int_name="analytical_data_image";
	SILsetSpan(iHTML,list_int_name,UID,int_name,undefined,true);
	SILsetSpan(iHTML,list_int_name,UID,int_name,undefined,false);
	
	// save current
	as("controlData",current,list_int_name,"data",UID,dataHash);
	
	// restore scroll position
	window.setTimeout(function () { updateScrollPos(); },300);
}

function downAnalyticalData(list_int_name,UID) {
	gotoAnalyticalDataImage(list_int_name,UID,1);
}

function upAnalyticalData(list_int_name,UID) {
	gotoAnalyticalDataImage(list_int_name,UID,-1);
}

function updateAnalyticalDataImage(list_int_name,UID,image_count) { // image_count is number of analytical_data_image, main image is not included
	var showThis=(image_count>0);
	SILvisibleObj(list_int_name,UID,"btn_image",undefined,showThis);
	if (showThis) {
		image_count++;
		as("controlData",image_count,list_int_name,"data",UID,"image_count");
		return getCurrentImage(0,image_count);
	}
}

function updateRcUID(anaUID) { // if UID is undefined, the whole list will be updated
	var int_names=[],texts=[],UIDs=[],gc_texts=[""],gc_rc_list_int_names=["reagents"],gc_rc_uids=[""],gc_rc_ids=[""],emp_formula=[""],std_uids=[],std_ids=[],std_names=[];
	int_names[0]="";
	texts[0]=s("rxn_mixture");
	// go through products and reactants and build list
	var name,list_int_name,int_name,UID,rc_id,UIDs=[],list_int_names=["reagents"];
	list_int_names=list_int_names.concat(chroma_lists);
	
	if (readOnly==false) { // im rw-Modus erst aufbauen, wenn alles geladen ist!! Dann wird diese Fkt über setControlValues aufgerufen. Die Event-Handler für die subitemlists sind NUR für Änderungen durch den User
		for (var c=0,max2=list_int_names.length;c<max2;c++) {
			if (!a(controls,list_int_names[c],"alreadyLoaded")) {
				return;
			}
		}
	}
	
	// Daten über Molekül sammeln
	for (var c=0,max2=list_int_names.length;c<max2;c++) {
		list_int_name=list_int_names[c];
		
		if (readOnly==true) { // nicht UID, sondern reaction_chemical_id
			// dataCache durchgehen
			var list=dataCache[a_db_id][a_pk][list_int_name];
			if (list) {
				for (var b=0,max=list.length;b<max;b++) {
					var rc_id=list[b]["reaction_chemical_id"],text=list[b]["standard_name"]; // A Toluol
					if (text=="") {
						text=getRCname(list_int_name)+" ";
						if (list_int_name=="reactants") {
							text+=numToLett(b+1);
						}
						else {
							text+=(b+1);
						}
						text+=" ["+list[b]["emp_formula"]+"]";
					}
					int_names.push(rc_id);
					texts.push(text);
					if (c==0) {
						std_names.push(text);
						std_ids.push(rc_id);
					}
					else {
						gc_texts.push(text);
						gc_rc_list_int_names.push(list_int_name);
						gc_rc_uids.push(rc_id);
						gc_rc_ids.push(rc_id);
						emp_formula.push(list[b]["emp_formula"]);
					}
				}
			}
		}
		else {
			for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
				UID=controlData[list_int_name]["UIDs"][b];
				var text=SILgetValue(list_int_name,UID,"standard_name"),rc_id=SILgetValue(list_int_name,UID,"reaction_chemical_id"); // A Toluol
				if (text=="") {
					text=getRCname(list_int_name)+" ";
					if (list_int_name=="reactants") {
						text+=numToLett(b+1);
					}
					else {
						text+=(b+1);
					}
					//~ text+=" ["+SILgetValue(list_int_name,UID,"emp_formula")+"]"; // reduces performance
				}
				int_names.push(UID); // the UID will be translated into reaction_chemical_id when saving
				texts.push(text);
				// Produkte für GC-Zuordnung
				if (c==0) {
					std_names.push(text);
					std_uids.push(UID);
					std_ids.push(rc_id);
				}
				else {
					gc_texts.push(text);
					gc_rc_list_int_names.push(list_int_name);
					gc_rc_uids.push(UID);
					gc_rc_ids.push(rc_id);
					emp_formula.push(SILgetValue(list_int_name,UID,"emp_formula"));
				}
			}
		}
	}
	
	// Zuordnung Komponente
	//~ alert(int_names);
	//~ alert(texts);
	
	// Liste der Moleküle für Spektren setzen
	list_int_name="analytical_data";
	int_name="reaction_chemical_uid";
	var fieldIdx=SILgetFieldIndex(list_int_name,int_name);
	as("controls",int_names,list_int_name,"fields",fieldIdx,"int_names");
	as("controls",texts,list_int_name,"fields",fieldIdx,"texts");
	
	// welche Spektren updaten?
	if (anaUID==undefined) { // alles
		UIDs=a(controlData,list_int_name,"UIDs");
		//~ for (var b=0;b<controlData[list_int_name]["UIDs"].length;b++) {
			//~ UIDs.push(controlData[list_int_name]["UIDs"][b]);
		//~ }
	}
	else { // einzelnes
		UIDs.push(anaUID);
	}
	
	var gc_peak_no,gc_table_bottom="</tbody></table>";
	
	for (var b=0,max=UIDs.length;b<max;b++) { // Spektren durchgehen
		var uid=UIDs[b];
		var pos=SILgetPos(list_int_name,uid);
		if (readOnly==true) {
			var reaction_chemical_id=a(dataCache,a_db_id,a_pk,"analytical_data",pos,"reaction_chemical_id");
			SILPkSelectSetOptions(list_int_name,uid,int_name,undefined,reaction_chemical_id);
		}
		else {
			var ad_rc_id=a(dataCache,a_db_id,a_pk,"analytical_data",pos,"reaction_chemical_id"),reaction_chemical_uid="";
			if (ad_rc_id!="") {
				reaction_chemical_uid=getRcUID(ad_rc_id);
			}
			SILPkSelectSetOptions(list_int_name,uid,int_name,undefined,reaction_chemical_uid);
		}
		
		// GC Tabelle updaten
		var analytics_type_code=SILgetValue(list_int_name,uid,"analytics_type_code");
		if (analytics_type_code && in_array(analytics_type_code.toLowerCase(),["gc","hplc"])) {
		
			// GC Interpretations-Listen einbauen
			
			var gc_peaks=a(dataCache,a_db_id,a_pk,"analytical_data",pos,"gc_peak"),gc_table="<table class=\"subitemlist\" style=\"width:auto\"><thead><tr><td></td><td>"+s("ret_time")+"</td><td>"+s("area_perc")+"</td><td>"+s("gc_yield")+"</td><td>"+s("resp_fac")+"</td><td>"+s("gc_peak_comment")+"</td></tr></thead><tbody>",analytics_method_id=SILgetValue(list_int_name,uid,"analytics_method_id");
			
			// Tabelle mit Eingabefeldern und ggf Werten
			for (var c=0,max2=gc_rc_uids.length;c<max2;c++) { // Standard (0) + Produkte durchgehen
				var rc_list_int_name=gc_rc_list_int_names[c],rc_uid=gc_rc_uids[c],rc_id=gc_rc_ids[c],retention_time="",area_percent="",gc_yield="",response_factor="",gc_peak_comment="";
				
				// Werte erfassen
				if (readOnly==true || !a(controls,list_int_name,"alreadyLoadedRw")) { // nicht UID, sondern reaction_chemical_id
					if (gc_peaks) {
						for (var d=0,max3=gc_peaks.length;d<max3;d++) { // GC Peaks durchgehen
							var peak_rc_id=gc_peaks[d]["reaction_chemical_id"];
							if (c==0) { // Standard
								// ist die rc_id des Peaks bei den Reagenzien, wenn ja, Nr wieviel?
								gc_peak_no=array_search(peak_rc_id,std_ids);
								if (gc_peak_no!=undefined) { // Standard gefunden
									as("controlData",peak_rc_id,"analytical_data","std_rc_id",uid);
									gc_texts[c]=defBlank(std_names[gc_peak_no]);
								}
							}
							else { // Produkt
								gc_peak_no=undefined;
							}
							if (peak_rc_id==rc_id || gc_peak_no!=undefined) {
								retention_time=gc_peaks[d]["retention_time"];
								area_percent=gc_peaks[d]["area_percent"];
								gc_yield=gc_peaks[d]["gc_yield"];
								response_factor=gc_peaks[d]["response_factor"];
								gc_peak_comment=gc_peaks[d]["gc_peak_comment"];
								break;
							}
						}
					}
				}
				else if (a(controls,list_int_name,"alreadyLoaded")) {
					retention_time=getInputValue("gc_peak_retention_time_"+uid+"_"+rc_uid);
					area_percent=getInputValue("gc_peak_area_percent_"+uid+"_"+rc_uid);
					gc_yield=getInputValue("gc_peak_gc_yield_"+uid+"_"+rc_uid);
					response_factor=getInputValue("gc_peak_response_factor_"+uid+"_"+rc_uid);
					gc_peak_comment=getInputValue("gc_peak_gc_peak_comment_"+uid+"_"+rc_uid);
					//~ if (response_factor==undefined) {
						//~ response_factor=getApproxF(emp_formula[c]);
					//~ }
				}
				
				// Werte schreiben
				if (readOnly==true) {
					gc_table+=getGCRoLine(gc_texts[c],retention_time,area_percent,gc_yield,response_factor,gc_peak_comment);
				}
				else {
					if (c==0) {
						// Auswahl Standard
						gc_table+=getGCRwLine(getStdSelect(uid),retention_time,area_percent,gc_yield,response_factor,gc_peak_comment,uid,rc_list_int_name,rc_uid);
					}
					else {
						gc_table+=getGCRwLine(gc_texts[c],retention_time,area_percent,gc_yield,response_factor,gc_peak_comment,uid,rc_list_int_name,rc_uid);
					}
				}
			}
			gc_table+=gc_table_bottom;
			if (readOnly==true) {
				if (gc_peaks!="") { // ist über haupt etwas da?
					SILsetSpan(gc_table,list_int_name,uid,"gc_peak",undefined,true);
				}
			}
			else {
				gc_table+="<table class=\"noborder\"><tr><td>";
				if (analytics_method_id>0) {
					gc_table+="<a class=\"imgButtonSm\" href=\"javascript:void autoGC("+fixQuot(uid)+")\"><img src=\"lib/auto_gc_sm.png\" border=\"0\""+getTooltip("auto_gc")+"></a>";
				}
				else {
					gc_table+="<a class=\"imgButtonSm\" href=\"javascript:void(0)\"><img src=\"lib/auto_gc_sm_deac.png\" border=\"0\""+getTooltip("no_method")+"></a>";
				}
				gc_table+="</td><td><a class=\"imgButtonSm\" href=\"javascript:void crossGC("+fixQuot(uid)+")\"><img src=\"lib/gc_cross_sm.png\" border=\"0\""+getTooltip("gc_cross")+"></a></td></tr></table>";
				SILsetSpan(gc_table,list_int_name,uid,"gc_peak");
				
				// Auswahlfeld für Standard füllen
				var obj=$("gc_peak_std_uid_"+uid+"_");
				var oldVal=obj.value;
				if (isEmptyStr(oldVal)) { // not set yet
					oldVal=getRcUID(a(controlData,"analytical_data","std_rc_id",uid));
				}
				clearChildElementsForObj(obj);
				// write list
				for (var c=0,max2=std_uids.length;c<max2;c++) {
					selAddOption(obj,std_uids[c],std_names[c],(oldVal==std_uids[c]));
				}
				
				updateGCyield(uid);
			}
		}
	}
}

function getAnalyticalDataIdsPrint() {
	var next_priority_pos=0,retval=[],list_int_name="analytical_data",analytical_data_count=analytical_data_lines*analytical_data_cols;
	if (!analytical_data_count) {
		return retval;
	}
	// prepare arrays for type_name,method_name and id
	//~ var ids=SILgetValueArray(list_int_name,"analytical_data_id"),disp=SILgetValueArray(list_int_name,"analytical_data_display_settings"),type_names=SILgetValueArray(list_int_name,"analytics_type_name"),method_names=SILgetValueArray(list_int_name,"analytics_method_name");
	var analytical_data=getCacheValue(list_int_name),ids=[],type_names=[],method_names=[],disp=[];
	if (!is_array(analytical_data)) {
		return retval;
	}
	for (var b=0,max=analytical_data.length;b<max;b++) {
		ids[b]=analytical_data[b]["analytical_data_id"];
		type_names[b]=analytical_data[b]["analytics_type_name"].toLowerCase();
		method_names[b]=analytical_data[b]["analytics_method_name"].toLowerCase();
		disp[b]=(analytical_data[b]["analytical_data_display_settings"]&1==1);
	}
	for (var b=0,max=analytical_data_priority.length;b<max;b++) {
		// go down priority/increase index
		var type_name=analytical_data_priority[b]["type_name"],method_name=analytical_data_priority[b]["method_name"];
		// find suitable spectrum
		for (var c=0,max2=type_names.length;c<max2;c++) {
			if (disp[c] && type_name==type_names[b] && (!method_name || method_name==method_names[b])) {
				retval.push(ids[c]);
				break; // should happen only once
			}
		}
		if (retval.length>=analytical_data_count) {
			return retval;
		}
	}
	// fill remaining places if possible
	// take default entries first
	for (var c=0,max2=type_names.length;c<max2;c++) {
		if (disp[c] && !in_array(ids[c],retval)) {
			retval.push(ids[c]);
		}
		if (retval.length>=analytical_data_count) {
			return retval;
		}
	}
	// fill remaining places if possible, take anything
	for (var c=0,max2=type_names.length;c<max2;c++) {
		if (!in_array(ids[c],retval)) {
			retval.push(ids[c]);
		}
		if (retval.length>=analytical_data_count) {
			return retval;
		}
	}
	return retval;
}