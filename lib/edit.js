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

// have global variables here to avoid changes in edit.php, settings.php, sidenav.php, etc
var readOnly,editMode,archive_entity,loadValues,keydownTimeout;

function getEANCheck(num,len) {
	var sum=10;
	for (var b=1;b<=len;b++) {
		var digit=parseInt(num.charAt(len-b));
		if ((b % 2)!=0) {
			sum+=3*digit;
		}
		else {
			sum+=digit;
		}
	}
	var check=10-(sum % 10);
	if (check==10) {
		check=0;
	}
	return check;
}

function getEAN(num,len) {
	//~ $num+=0; // with 13 digits, rounded
	if (len!=8) {
		len=13;
	}
	if (num.length>len) {
		return "";
	}
	num=parseInt(num);
	if (isNaN(num) || num<0) {
		return "";
	}
	if (num.length==len) {
		return num;
	}
	len-=1;
	num=leftPad(num,len,"0");
	return num+getEANCheck(num,len);
}

function getEANWithPrefix(prefix,num,len) {
	prefix=String(prefix);
	return getEAN(prefix+leftPad(num,len-1-prefix.length,"0"),len);
}

function downloadRxnPDF() {
	var url="getPDF.php?table=reaction&db_id="+a_db_id+"&pk="+a_pk;
	window.open(url);	
}

function transferRxnPDF() {
	var url="chooseAsync.php?desired_action=transferRxnPDF&table=reaction&db_id="+a_db_id+"&pk="+a_pk;
	setFrameURL("comm",url);	
}

function printDymoLabel() {
	var url="editWin.php?mode=print_label&table="+table;
	window.open(url,Number(new Date()),"height=450,width=300,scrollbars=yes");
}

function getPrinterName() {
	if (typeof dymo=="undefined") return "";
	var printers=dymo.label.framework.getPrinters();
	for (var i=0;i<printers.length;i++) {
		var printer=printers[i];
		if (printer.printerType=="LabelWriterPrinter") {
			return printer.name;
		}
	}
	return "";
}

function tryToShowDymoButton() {
	if (getPrinterName()) {
		showObj("dymo");
	}
}

function frameworkInitShim() {
	if (dymo.label.framework.init) {
		dymo.label.framework.init(tryToShowDymoButton);
	} else  {
		// try directly
		tryToShowDymoButton();
	}
}

function labelSetObjectText(label,name,value) {
	try {
		label.setObjectText(name,value);
	}
	catch (err) {
		// ignore
	}
}

function addPackage() {
	//~ var url="edit.php?"+pageParams+"&table=chemical_storage&desired_action=new&db_id="+a_db_id;
	var url="edit.php?"+getSelfRef(["~script~","table"])+"&table=chemical_storage&desired_action=new&db_id="+a_db_id;
	if (table=="molecule") {
		url+="&molecule_id="+a_pk;
	}
	else if (table=="chemical_storage") {
		url+="&chemical_storage_id="+a_pk;
	}
	else if (table=="chemical_order") {
		url+="&order_uid="+getInputValue("order_uid");
	}
	else if (table=="accepted_order") {
		url+="&order_uid="+getInputValue("order_uid_cp");
	}
	else {
		return;
	}
	self.location.href=url;
}

function undeleteChemical() {
	if (table!="chemical_storage") {
		return;
	}
	var url="editAsync.php?"+getSelfRef(["~script~","table"])+"&table=chemical_storage&desired_action=undel&db_id="+a_db_id+"&pk="+a_pk;
	setFrameURL("edit",url);
	
	// aus cache löschen
	deleteDatasetFromCache(actIdx);
}

function acceptOrder() {
	if (table!="chemical_order") {
		return;
	}
	var url="edit.php?"+getSelfRef(["~script~","table"])+"&table=accepted_order&desired_action=new&db_id="+a_db_id+"&order_uid="+getInputValue("order_uid");
	self.location.href=url;
}

function approveOrder() {
	if (a_db_id==-1) {
		prepareUpdate(a_db_id,a_pk,0);
		var url="editAsync.php?"+getSelfRef(["~script~","table"])+"&desired_action=confirm_order&table="+table+"&db_id=-1&pk="+a_pk+prepareAsync();
		setFrameURL("edit",url);
		
		if (table=="confirm_chemical_order") {
			deleteDatasetFromCache(actIdx);
		}
	}
}

function returnNow() {
	if (a_db_id==-1) {
		prepareUpdate(a_db_id,a_pk,0);
		//~ var url="editAsync.php?"+pageParams+"&desired_action=return_rent&table="+table+"&db_id=-1&pk="+a_pk+prepareAsync();
		var url="editAsync.php?"+getSelfRef(["~script~","table"])+"&desired_action=return_rent&table="+table+"&db_id=-1&pk="+a_pk+prepareAsync();
		setFrameURL("edit",url);
	}
}

function setOrderStatus(central_order_status) {
	if (a_db_id==-1 && central_order_status) {
		prepareUpdate(a_db_id,a_pk,0);
		//~ var url="editAsync.php?"+pageParams+"&desired_action=set_order_status&table="+table+"&central_order_status="+central_order_status+"&db_id=-1&pk="+a_pk+prepareAsync();
		var url="editAsync.php?"+getSelfRef(["~script~","table"])+"&desired_action=set_order_status&table="+table+"&central_order_status="+central_order_status+"&db_id=-1&pk="+a_pk+prepareAsync();
		setFrameURL("edit",url);
		
		if (central_order_status==4) {
			deleteDatasetFromCache(actIdx);
		}
	}
}

function orderThis() {
	if (table!="supplier_offer") {
		return;
	}
	//~ var url="edit.php?"+pageParams+"&table=chemical_order&db_id="+a_db_id+"&supplier_offer_id="+a_pk;
	var url="edit.php?"+getSelfRef(["~script~","table","db_id"])+"&table=chemical_order&db_id="+a_db_id+"&supplier_offer_id="+a_pk;
	self.location.href=url;
}

function addLiteratureByDOI() {
	var url="editWin.php?mode=add_dois&table="+table;
	window.open(url,Number(new Date()),"height=500,width=350,scrollbars=yes");
}

function updatePermissions() {
	var value=getInputValue("predefined_permissions");
	lockControl("permissions_general",value!=="");
	lockControl("permissions_chemical",value!=="");
	lockControl("permissions_lab_journal",value!=="");
	lockControl("permissions_order",value!=="");
	if (value!=="") {
		setControlValues({"permissions":value,"permissions_general":value,"permissions_chemical":value,"permissions_lab_journal":value,"permissions_order":value},true);
	}
}

function splitDelivery() {
	if (typeof table!="string" || table!="mpi_order") {
		return;
	}
	var list_int_name="mpi_order_item";
	// gibt es !=1 zeile => raus
	if (SILgetLineCount(list_int_name)!=1) {
		return;
	}
	// hole menge aus zeile 1
	var UID=SILgetUID(list_int_name,0);
	var amount=parseFloat(SILgetValue(list_int_name,UID,"amount")),amount_unit=SILgetValue(list_int_name,UID,"amount_unit");
	if (isNaN(amount)) {
		return;
	}
	// wieviele
	var split_count=parseInt(prompt(s("split_count_question"),""));
	if (split_count<2) {
		return;
	}
	// füge n-1 zeilen hinzu
	SILmanualAddLineMultiple(split_count-1,list_int_name);
	// setze amount, unit
	amount/=split_count;
	SILsetValuesField(list_int_name,"amount",amount);
	SILsetValuesField(list_int_name,"amount_unit",amount_unit);
}

function createBESSI() {
	var url="chooseAsync.php?desired_action=generateNewBESSI";
	setFrameURL("comm",url);	
}

function isPoison() {
	if (typeof table!="string") {
		return;
	}
	else if (table=="molecule" && !editMode) {
		// do nothing
	}
	else if (table!="chemical_storage") {
		return;
	}
	
	// Negliste?
	var neg_liste=getChecked("neg_liste");
	if (neg_liste) {
		return false;
	}
	
	// Posliste?
	var pos_liste=getChecked("pos_liste");
	if (pos_liste) {
		return true;
	}
	
	// T+ ?
	var safety_sym=getInputValue("safety_sym");
	if (safety_sym) {
		//~ if (safety_sym.indexOf("T+")!=-1) {
		if (safety_sym.indexOf("T")!=-1) {
			return true;
		}
	}
	
	// PG 1 ?
	var list_int_name="molecule_property";
	var UID=SILfindValue(list_int_name,"class_name","packing_group");
	if (UID) {
		var packing_group=SILgetValue(list_int_name,UID,"conditions");
		if (in_array(packing_group,["I","1"])) {
			return true;
		}
	}
	return false;
}

function loadVersion(archive_entity_id,recover) {
	archive_entity=archive_entity_id;
	//~ var url="editAsync.php?table="+table+"&db_id="+a_db_id+"&pk="+a_pk+"&archive_entity="+archive_entity+"&age_seconds="+getAgeSeconds()+"&refresh_data[]="+a_db_id+","+a_pk+"&"+pageParams;
	var url="editAsync.php?"+getSelfRef(["~script~","table"])+"&table="+table+"&db_id="+a_db_id+"&pk="+a_pk+"&archive_entity="+archive_entity+"&age_seconds="+getAgeSeconds()+"&refresh_data[]="+a_db_id+","+a_pk;
	if (archive_entity_id && recover) {
		url+="&desired_action=recover";
	}
	visibleObj("btn_recover",archive_entity_id);
	//~ alert(url);
	setFrameURL("edit",url);
}

function recoverVersion() {
	if (confirm(s("recover_warning"))) {
		loadVersion(getInputValue("versionSelect"),true);
	}
}

function showVersionsList(show) {
	var versionMenuVisible=visibleObj("versionMenu",show);
	if (!versionMenuVisible) {
		return;
	}
	if (!versionsInited) {
		initVersionsList();
	}
	var objDiv=$("versionMenu"),btn_obj=$("btn_version");
	if (btn_obj && objDiv) {
		showOverlayObj(btn_obj,objDiv,0,5,8);
	}
}

function initVersionsList() {
	var text,versions=getCacheValue("versions"),obj=$("versionSelect");
	if (!obj) {
		return;
	}
	// current version
	clearChildElementsForObj(obj);
	hideObj("btn_recover");
	selAddOption(obj,"",s("current_version"),true);
	for (var b=0,max=versions.length;b<max;b++) {
		text=(max-b)+": "+toGerDate(a(versions,b,"version_when"))+" ("+a(versions,b,"version_by")+") "+versions[b]["version_comment"];
		selAddOption(obj,versions[b]["archive_entity_id"],text);
	}
	versionsInited=true;
}

function addTemplateToInput(srcSelectId,targetInputId) {
	var text=unescape(getInputValue(srcSelectId));
	insertHTML(nl2br(text),targetInputId);
	//~ var obj=$(targetInputId);
	//~ if (obj) {
		//~ wrapSelection(obj,"",text,2);
	//~ }
}

function getCostCentres(values) {
	var cost_centres=a(values,"cost_centres"),retval="",separator="";
	for (var b=0,max=cost_centres.length;b<max;b++) {
		retval+=separator; // is "" in first round
		separator=",";
		retval+=cost_centres[b]["cost_centre"];
	}
	return retval;
}

function getSupplierCodes(values) {
	var institution_code=a(values,"institution_codes"),retval="",separator="";
	for (var b=0,max=institution_code.length;b<max;b++) {
		retval+=separator; // is "" in first round
		separator="\n";
		retval+=institution_code[b]["supplier_code"];
	}
	return retval;
}

function setSingleSelect() {
	var totalChange=0,url="table="+table+"&db_id[]="+a_db_id+"&pk[]="+a_pk+"&desired_action=",toValue=getChecked("sel");
	url+=(toValue?"select":"unselect");
	dataCache[a_db_id][a_pk]["sel"]=toValue;
	totalChange+=(toValue?1:-1);
	setFrameURL("comm","manageSelect.php?"+url);
	if (parent.topnav && table=="chemical_storage") {
		parent.topnav.changeTotalSelect(totalChange);
	}
}

function checkPassChar(password) {
	var test_array=[/\d/,/[A-Za-z]/]; // passwords are not case-sensitive
	for (var b=0,max=test_array.length;b<max;b++) {
		if (test_array[b].test(password)==false) {
			return false;
		}
	}
	return true;
}

function checkPass(password,username,focusInputId,allowNone) {
	if (allowNone==true && password=="") {
		return true;
	}
	if (password=="") {
		alert(s("password_none"));
		focusInput(focusInputId);
		return false;
	}
	if (password.length<7) {
		alert(s("error_password_too_short"));
		focusInput(focusInputId);
		return false;
	}
	if (password.indexOf(username)!=-1) {
		alert(s("error_password_not_username"));
		focusInput(focusInputId);
		return false;
	}
	if (checkPassChar(password)==false) {
		alert(s("error_password_too_simple"));
		focusInput(focusInputId);
		return false;
	}
	return true;
}

var initialStatus;
function updateStatusButtons() {
	var current_reaction_status=getControlValue("status");
	for (var b=1;b<6;b++) {
		var td_id="td_status_"+b,a_id="a_status_"+b;
		if (b<initialStatus) {
			hideObj(td_id);
		}
		else if (b==current_reaction_status) {
			showObj(td_id);
			setObjClass(a_id,"imgButtonSm buttonActive");
		}
		else {
			showObj(td_id);
			setObjClass(a_id,"imgButtonSm");
		}
	}

}

function setReactionStatus(reaction_status) {
	var current_reaction_status=getControlValue("status");
	if (reaction_status==current_reaction_status) {
		return;
	}
	setControl("status",{"status":reaction_status},true);
	valChanged();
}

function alterStatusAnalytics() {
	if (getInputValue("status")==1) {
		setReactionStatus(2);
	}
}

function closeLabJournal(sess_proof) {
	if (confirm(s("warning_close_lj"))) { // POST to avoid accidental executions
		var nvps=["sess_proof",sess_proof,"desired_action","close","table",table,"db_id",a_db_id,"pk",a_pk,"age_seconds",getAgeSeconds(),"refresh_data[]",a_db_id+","+a_pk,"goto_page",actIdx];
		var url="editAsync.php";
		asyncRequest(url,nvps);
	}
}

function borrowEdit(person_id) { // borrowing by external people only through barcode terminal
	if (person_id==undefined) {
		person_id="";
	}
	//~ var url="editAsync.php?desired_action=borrow&table="+table+"&db_id="+a_db_id+"&pk="+a_pk+"&borrowed_by_db_id=-1&borrowed_by_person_id="+person_id+"&age_seconds="+getAgeSeconds()+"&refresh_data[]="+a_db_id+","+a_pk+"&goto_page="+actIdx+"&"+pageParams;
	var url="editAsync.php?"+getSelfRef(["~script~","table"])+"&desired_action=borrow&table="+table+"&db_id="+a_db_id+"&pk="+a_pk+"&borrowed_by_db_id=-1&borrowed_by_person_id="+person_id+"&age_seconds="+getAgeSeconds()+"&refresh_data[]="+a_db_id+","+a_pk+"&goto_page="+actIdx;
	//~ alert(url);
	setFrameURL("edit",url);	
}

function changeMessageCompletion(status) {
	//~ var url="editAsync.php?desired_action=message_status&table="+table+"&db_id="+a_db_id+"&pk="+a_pk+"&age_seconds="+getAgeSeconds()+"&refresh_data[]="+a_db_id+","+a_pk+"&goto_page="+actIdx+"&completion_status="+status+"&"+pageParams;
	var url="editAsync.php?"+getSelfRef(["~script~","table"])+"&desired_action=message_status&table="+table+"&db_id="+a_db_id+"&pk="+a_pk+"&age_seconds="+getAgeSeconds()+"&refresh_data[]="+a_db_id+","+a_pk+"&goto_page="+actIdx+"&completion_status="+status;
	//~ alert(url);
	setFrameURL("edit",url);
}

// start functions for printing

function printSelection(sizeJoin,additionalParameters,selectedDatasets) {
	var datasetRange=[];
	for (var b=0,max=dbIdx.length;b<max;b++) {
		if (in_array(dbIdx[b]["pk"],a(selectedDatasets,dbIdx[b]["db_id"]) )) {
			datasetRange.push(b+1);
		}
	}
	//~ alert(datasetRange);
	if (datasetRange.length) {
		var sizeArray=sizeJoin.split(",");
		printRange(sizeArray[0],sizeArray[1],datasetRange.join(","),additionalParameters);
	}
	else {
		showPrintMenu(false);
	}
}

function printDetail() {
	var datasetRange,sizeJoin=getInputValue("print_size"),additionalParameters=[],cols,rows;
	
	switch (table) {
	case "reaction":
		if (getChecked("print_labels")==true) {
			additionalParameters.push("print_labels");
			cols=3;
			rows=7;
		}
		else if (getChecked("print_chem_list")==true) {
			additionalParameters.push("print_chem_list");
		}
	break;
	case "settlement":
		additionalParameters.push("no_table");
	break;
	}
	
	// multipage
	if (getChecked("multi_page")==true) {
		additionalParameters.push("multi_page");
	}
	
	switch (radioButtonValue("print_what")) {
	case "print_all":
	break;
	case "print_range":
		datasetRange=getInputValue("print_range_input");
	break;
	case "print_from_here":
		datasetRange=(actIdx+1)+"-";
	break;
	case "print_selection":
		var url="chooseAsync.php?desired_action=print_selection&table="+table+"&sizeJoin="+sizeJoin+"&additionalParameters="+additionalParameters.join(",");
		setFrameURL("comm",url);
		return;
	break;
	case "print_current":
	default:
		datasetRange=actIdx+1;
	}
	var sizeArray=sizeJoin.split(","); // paper size chosen
	printRange(sizeArray[0],sizeArray[1],datasetRange,additionalParameters.join(","),cols,rows);
}

function showCustomMenu(show) {
	visibleObj("customize_view_menu",show);
}

function updateCustomView() {
	submitForm("customize_view_form");
}

function showPrintMenu(show) {
	visibleObj("printMenu",show);
}

function invokePrintLJ() {
	var url="edit.php?table=reaction&style=lj&dbs="+a_db_id+"&query=<0>&crit0=lab_journal_id&op0=eq&val0="+a_pk+"&print_nr_in_lab_journal=";
	switch (radioButtonValue("printLJ_what")) {
	case "printLJ_range":
		url+=getInputValue("printLJ_range_input");
	break;
	}
	self.location.href=url;
}

function showPrintLJ(visible) {
	visibleObj("printMenuLJ",visible);
}

function writePrintWin(pages,widthMM,heightMM,additionalParameters,cols,rows) {
	var url="print.php?pages="+pages+"&widthMM="+widthMM+"&heightMM="+heightMM+"&table="+table+"&view="+currentView+ifnotempty("&cols=",cols)+ifnotempty("&rows=",rows)+ifnotempty("&options=",additionalParameters);
	window.open(url);
}

datasetArray=[];
function printRange(widthMM,heightMM,datasetRange,additionalParameters,cols,rows) {
	// load all datasets
	if (datasetRange==undefined || datasetRange==="") {
		datasetRange="1-"+dbIdx.length;
	}
	datasetArray=splitDatasetRange(datasetRange);
	if (!is_array(datasetArray)) { // nothing
		datasetArray=[];
		datasetArray[0]=actIdx+1;
	}
	loadAll(datasetArray); // put range here later
	writePrintWin(datasetArray.length,widthMM,heightMM,additionalParameters,cols,rows)
}

function sort_reactant_data(b,c) {
	if (b["storage_id"]<c["storage_id"]) {
		return -1;
	}
	else if (b["storage_id"]>c["storage_id"]) {
		return 1;
	}
	
	// equal
	if (b["compartment"]<c["compartment"]) {
		return -1;
	}
	else if (b["compartment"]>c["compartment"]) {
		return 1;
	}
	
	// equal
	if (b["molecule_id"]<c["molecule_id"]) {
		return -1;
	}
	else if (b["molecule_id"]>c["molecule_id"]) {
		return 1;
	}
	
	// equal
	if (b["chemical_storage_id"]<c["chemical_storage_id"]) {
		return -1;
	}
	else if (b["chemical_storage_id"]>c["chemical_storage_id"]) {
		return 1;
	}
	return 0;
}

var safariPrintWin,safariLoadCount;
function completePrint(printWin,widthMM,heightMM,additionalParameters,flags) { // this is called when the window for printing is ready
	var clone_id="browsemain"; // ,reaction_ids=[];
	if (is_string(additionalParameters)) {
		additionalParameters=additionalParameters.split(",");
	}
	switch (table) {
	case "settlement": // do not insert copy of form, make custom list
		clone_id="reaction_barcode";
	break;
	case "reaction":
		if (in_array("print_labels",additionalParameters)) { // print labels only
			clone_id="reaction_barcode";
		}
	break;
	}
	completePrint2(printWin,widthMM,heightMM,additionalParameters,flags,0,clone_id,"",[],{});
}

function completePrint2(printWin,widthMM,heightMM,additionalParameters,flags,c,clone_id,additional,reactant_data,solvent_data) {
	var list_int_names=["reactants","reagents"],max=datasetArray.length,doContinue=false;
	if (c<max) { // goto through dbIdx, showDataset(..) (view is applied there)
		showDataset(datasetArray[c],false);
		switch (table) {
		
		case "settlement": // do not insert copy of form, make custom list
			printWin.setiHTML("page_"+c,getSettlementPrintout() );
		break;
		
		case "reaction":
			if ((flags & 1) && getCacheValue("person_id")!=person_id) { // impede unprivileged data leakage
				// remove div
				printWin.removeId("page_"+c);
				if (in_array("lj_print",additionalParameters)) {
					printWin.removeId("ana_"+c);
				}
				doContinue=true;
			}
		break;
		}
		
		 if (!doContinue) {
			// event auslösen
			executeForms("onPrint");
			// copy contents
			insertClone(clone_id,"page_"+c,printWin);
			
			if (in_array("lj_print",additionalParameters)) {
				// fill cells with appropriate img
				//~ reaction_ids.push(a_pk);
				var print_ids=getAnalyticalDataIdsPrint(),wMM=widthMM/analytical_data_cols-5,hMM=heightMM/analytical_data_lines-5,iHTML;
				for (var b=0,max2=print_ids.length;b<max2;b++) {
					printWin.setiHTML("ana_"+c+"_"+b,"<img src=\"getGif.php?db_id="+a_db_id+"&analytical_data_id="+print_ids[b]+"\" style=\"max-width:"+wMM+"mm;max-height:"+hMM+"mm;width:auto; height:auto;\">");
				}
			}
			else if (in_array("print_chem_list",additionalParameters)) { // generate chem_list
				// Daten NICHT aus DOM holen, viel zu langsam
				for (var b=0,max2=list_int_names.length;b<max2;b++) {
					var join_list=dataCache[a_db_id][a_pk][ list_int_names[b] ];
					if (join_list!=null && join_list.length>0) {
						reactant_data=reactant_data.concat(join_list);
					}
				}
				var solvent_name=dataCache[a_db_id][a_pk]["solvent"];
				if (solvent_name!="") {
					var solvent_ml=parseFloat(dataCache[a_db_id][a_pk]["solvent_amount"]);
					if (!isNaN(solvent_ml)) {
						if (!solvent_data[solvent_name]) {
							solvent_data[solvent_name]=0;
						}
						solvent_data[solvent_name]+=solvent_ml;
					}
				}
			}
			else if (table=="order_comp") {
				// attach kleinauftrag
				var iHTML="",chem_order=dataCache[a_db_id][a_pk]["accepted_order"],klein_str=getCacheValue("kleinauftrag_nrn"),firma=getFormattedAdress(dataCache[a_db_id][a_pk]);
				var kostenstelle=getCacheValue("order_cost_centre_cp"),central_cost_centre=getCacheValue("central_cost_centre"),kdNr=getCacheValue("customer_id"),fixedCosts=getCacheValue("fixed_costs");
				
				var blocks=0;
				var klein_arr=klein_str.split("\n");
				
				// nach Preis sortieren, auffüllen bis zur Grenze
				//~ chem_order.sort(sort_order_comp);
				for (var d=0;d<2;d++) { // drittes Blatt kann entfallen
					blocks++;
					var cumAmount=fixedCosts,klein_pos=0,klein_idx=0;
					iHTML+=getKleinauftragH(own_address,central_cost_centre,firma,klein_arr[klein_idx],kdNr,d,blocks%2);
					
					if (fixedCosts>0) {
						// Fixkosten
						klein_pos++;
						iHTML+=getKleinauftragL(klein_pos,1,s("fixed_costs"),fixedCosts);
					}
					
					if (is_array(chem_order)) for (var b=0,max2=chem_order.length;b<max2;b++) {
						if (klein_pos>8 || (chem_order[b]["total_price"]+cumAmount>maxKleinauftrag && cumAmount>0)) { // start new one
							// auffüllen
							for (var e=klein_pos;e<8;e++) {
								iHTML+=getKleinauftragL();
							}
							iHTML+=getKleinauftragF();
							blocks++;
							klein_idx++;
							cumAmount=0;
							klein_pos=0;
							iHTML+=getKleinauftragH(own_address,central_cost_centre,firma,klein_arr[klein_idx],kdNr,d,blocks%2);
						}
						cumAmount+=chem_order[b]["total_price"];
						klein_pos++;
						iHTML+=getKleinauftragL(klein_pos,chem_order[b]["number_packages"],chem_order[b]["name"]+"/"+chem_order[b]["beautifulCatNo"],chem_order[b]["number_packages"]*chem_order[b]["price"]);
					}
					// auffüllen
					for (var e=klein_pos;e<8;e++) {
						iHTML+=getKleinauftragL();
					}
					iHTML+=getKleinauftragF();
				}
				printWin.setiHTML("kleinauftrag_"+c,iHTML);
			}
		}
		window.setTimeout(function () { completePrint2(printWin,widthMM,heightMM,additionalParameters,flags,c+1,clone_id,additional,reactant_data,solvent_data); },10);
	} else {
		// do additional tasks afterwards
		// data amount exceeds limitations for GET
		/* if (in_array("lj_print",additionalParameters)) {
			if (reaction_ids.length) {
				// set status to printed
				var url="editAsync.php?desired_action=set_reaction_printed&table=reaction&db_id=-1&reaction_ids="+reaction_ids.join(",")+"&"+pageParams;
				//~ alert(url);
				setFrameURL("edit",url);
			}
		}
		else */
		if (in_array("print_chem_list",additionalParameters)) {
			// sortieren nach storage_id,compartment,molecule_id
			reactant_data.sort(sort_reactant_data);
			
			// SUM UP: m_brutto,mass_unit,volume,volume_unit
			var mass_unit=getDefaultUnitForClass("m"),volume_unit=getDefaultUnitForClass("v"); // Faktor ist 1 per Def.
			for (var d=0,max3=reactant_data.length;d<max3;d++) {
				// Zahlen sind NICHT in Standardeinheit, auf Standard bringen
				reactant_data[d]["m_brutto"]=reactant_data[d]["m_brutto"]*getUnitFactor(reactant_data[d]["mass_unit"]);
				//~ reactant_data[d]["rc_amount"]=reactant_data[d]["rc_amount"]*getUnitFactor(reactant_data[d]["rc_amount_unit"]);
				reactant_data[d]["volume"]=reactant_data[d]["volume"]*getUnitFactor(reactant_data[d]["volume_unit"]);
			}
			for (var d=reactant_data.length-1;d>0;d--) {
				if (reactant_data[d]["chemical_storage_id"]!=null && reactant_data[d]["chemical_storage_id"]==reactant_data[d-1]["chemical_storage_id"]) {
					// merge on d-1 an splice out d
					reactant_data[d-1]["m_brutto"]=safeAdd(reactant_data[d]["m_brutto"],reactant_data[d-1]["m_brutto"]);
					//~ reactant_data[d-1]["rc_amount"]=safeAdd(reactant_data[d]["rc_amount"],reactant_data[d-1]["rc_amount"]);
					reactant_data[d-1]["volume"]=safeAdd(reactant_data[d]["volume"],reactant_data[d-1]["volume"]);
					reactant_data.splice(d,1); // delete d
				}
				else if (reactant_data[d]["chemical_storage_id"]==null && reactant_data[d]["molecule_id"]!=null && reactant_data[d]["molecule_id"]==reactant_data[d-1]["molecule_id"]) {
					// merge on d-1 an splice out d
					reactant_data[d-1]["m_brutto"]=safeAdd(reactant_data[d]["m_brutto"],reactant_data[d-1]["m_brutto"]);
					//~ reactant_data[d-1]["rc_amount"]=safeAdd(reactant_data[d]["rc_amount"],reactant_data[d-1]["rc_amount"]);
					reactant_data[d-1]["volume"]=safeAdd(reactant_data[d]["volume"],reactant_data[d-1]["volume"]);
					reactant_data.splice(d,1); // delete d
				}
			}
			
			if (!is_empty(solvent_data)) {
				// output solvent list
				additional+="<table class=\"chemlist\" style=\"page-break-before:always\"><colgroup><col><col class=\"formAlignValue\"></colgroup>";
				additional+="<thead><tr><td>"+s("solvent")+"</td><td>"+s("solvent_amount")+"</td></tr></thead><tbody>";
				for (var solvent_name in solvent_data) {
					additional+="<tr><td>"+solvent_name+"</td><td>"+solvent_data[solvent_name]+"</td></tr>";
				}
				additional+="</tbody></table>";
			}
			
			var storage_id=-1,r_text="",s_text="",h_text="",p_text="";
			additional+="<table class=\"chemlist\" style=\"page-break-before:always\"><colgroup><col><col><col><col><col><col class=\"formAlignValue\"><col class=\"formAlignValue\"><col><col><col></colgroup>";
			additional+="<thead><tr><td>"+s("structure")+"</td><td>"+s("standard_name")+"<br>"+s("package_name")+"</td><td>"+s("compartment")+"<br>"+s("borrowed_by")+"<br>"+s("chemical_storage_barcode")+"</td><td colspan=\"2\">"+s("required_amount")+"</td><td>"+s("safety_sym_short")+"</td><td>";
			if (use_rs) {
				additional+=s("safety_r")+" ";
			}
			if (use_ghs) {
				additional+=s("safety_h");
			}
			additional+="</td><td>";
			if (use_rs) {
				additional+=s("safety_s")+" ";
			}
			if (use_ghs) {
				additional+=s("safety_p");
			}
			additional+="</td></tr></thead><tbody>";
			// storage_id,storage_name,compartment,standard_name,package_name
			for (var d=0,max3=reactant_data.length;d<max3;d++) {
				if (reactant_data[d]["storage_id"]!=storage_id) { // Überschrift für Lager
					storage_id=reactant_data[d]["storage_id"];
					additional+="<tr><td colspan=\"9\"><h2>"+defBlank(reactant_data[d]["storage_name"])+"</h2></td></tr>";
				}
				additional+="<tr><td>";
				if (reactant_data[d]["molecule_id"]) {
					additional+="<img src=\"getGif.php?db_id=-1&amp;molecule_id="+reactant_data[d]["molecule_id"]+"\" height=\"35\" width=\"35\">";
				}
				else if (reactant_data[d]["reaction_chemical_id"]) {
					additional+="<img src=\"getGif.php?db_id=-1&amp;reaction_chemical_id="+reactant_data[d]["reaction_chemical_id"]+"\" height=\"35\" width=\"35\">";
				}
				else {
					additional+="&nbsp;";
				}
				additional+="</td><td>"+defBlank(reactant_data[d]["standard_name"])+"<br>"+defBlank(reactant_data[d]["package_name"])+"</td><td>"+defBlank(reactant_data[d]["compartment"])+"<br>"+formatPerson(reactant_data[d])+"<br>"+defBlank(reactant_data[d]["chemical_storage_barcode"])+"</td><td>";
				if (reactant_data[d]["m_brutto"]!=undefined) {
					additional+=round(reactant_data[d]["m_brutto"],3,4)+" "+mass_unit;
				}
				additional+="</td><td>";
				if (reactant_data[d]["m_brutto"]!=undefined) {
					additional+=round(reactant_data[d]["volume"],3,4)+" "+volume_unit;
				}
				additional+="</td><td>";
				if (use_rs) {
					additional+=getSymbols("rs",reactant_data[d]["safety_sym"],31,31);
				}
				if (use_ghs) {
					additional+=getSymbols("ghs",reactant_data[d]["safety_sym_ghs"],31,31);
				}
				additional+="</td><td>";
				if (use_rs) {
					additional+=defBlank(reactant_data[d]["safety_r"])+" ";
				}
				if (use_ghs) {
					additional+=defBlank(reactant_data[d]["safety_h"]);
				}
				additional+="</td><td>";
				if (use_rs) {
					additional+=defBlank(reactant_data[d]["safety_s"])+" ";
				}
				if (use_ghs) {
					additional+=defBlank(reactant_data[d]["safety_p"]);
				}
				additional+="</td></tr>";
				
				
				// R/S-Sätze erfassen
				if (use_rs) {
					r_text+="-"+reactant_data[d]["safety_r"];
					s_text+="-"+reactant_data[d]["safety_s"];
				}
				if (use_ghs) {
					h_text+="-"+reactant_data[d]["safety_h"];
					p_text+="-"+reactant_data[d]["safety_p"];
				}
			}
			additional+="</tbody></table><h2>";
			if (use_rs) {
				additional+=s("safety_r")+"</h2>"+procClauses("R",r_text,true)+"<h2>"+s("safety_s")+"</h2>"+procClauses("S",s_text,true);
			}
			if (use_ghs) {
				additional+=s("safety_h")+"</h2>"+procClauses("H",h_text,true)+"<h2>"+s("safety_p")+"</h2>"+procClauses("P",p_text,true);
			}
		}
		printWin.setiHTML("additional",additional);
		datasetArray=[];
		
		if (isSafari || isOpera || isChrome || isFF1x) {
			var dummy=printWin.document.body.offsetWidth;
			printWin.scrollBy(0,1);
			safariPrintWin=printWin;
			safariLoadCount=0;
			printSafari(max);
		}
		else {
			printWin.print();
		}
		//~ printWin.close();
		showPrintMenu(false);
		showDataset(actIdx,true);
	}
}

function removeHidden(nodeObj,targetObj) {
	if (nodeObj && nodeObj.offsetTop>targetObj.offsetTop+targetObj.offsetHeight) {
		nodeObj.parentNode.removeChild(nodeObj);
		return;
	}
	for (var b=nodeObj.childNodes.length-1;b>=0;b--) {
		removeHidden(nodeObj.childNodes[b],targetObj);
	}
}

function printSafari(clean) {
	for (var c=0;c<clean;c++) {
		// remove objects out of range to prevent bogus pages from being inserted
		var targetObj=safariPrintWin.$("page_"+c);
		removeHidden(targetObj,targetObj);
	}
	for (var b=0,max=safariPrintWin.document.images.length;b<max;b++) {
		if (!safariPrintWin.document.images[b].complete) {
			if (safariLoadCount<20) {
				safariLoadCount++;
				window.setTimeout(function () { printSafari(0); },200);
				return;
			}
			else {
				break;
			}
		}
	}
	safariPrintWin.print();
}

function cleanNode(nodeObj,targetObj) {
	// remove id
	// remove event handlers
	if (nodeObj.attributes) {
		for (var b=nodeObj.attributes.length-1;b>=0;b--) {
			if (nodeObj.className=="print_only") { // make print preview more clear
				nodeObj.style.display="";
			}
			else if (nodeObj.style.display=="none" || nodeObj.className=="noprint") {
				if (nodeObj.parentNode) {
					nodeObj.parentNode.removeChild(nodeObj);
				}
				return;
			}
			var attName=nodeObj.attributes[b].nodeName;
			attName=attName.toLowerCase();
			if (attName=="type") {
				var attValue=nodeObj.attributes[b].nodeValue;
				attValue=attValue.toLowerCase();
				if (attValue=="hidden") {
					nodeObj.parentNode.removeChild(nodeObj);
					return;
				}
			}
			else if (attName=="id" || attName=="name" || startswith(attName,"on")) {
				nodeObj.removeAttributeNode(nodeObj.attributes[b]);
			}
			else if (attName=="href") {
				nodeObj.setAttribute(attName,"#");
			}
		}
	}
	
	// preserve style
	//~ var compStyle;
	//~ if (nodeObj.currentStyle) { // IE
		//~ compStyle=nodeObj.currentStyle;
	//~ }
	//~ else if (document.defaultView.getComputedStyle) { // FF
		//~ compStyle=document.defaultView.getComputedStyle(nodeObj,"");
	//~ }
	
	
	
	for (var b=nodeObj.childNodes.length-1;b>=0;b--) {
	
		// remove script
		if (nodeObj.childNodes[b].tagName) {
			var tagName=nodeObj.childNodes[b].tagName.toLowerCase();
			if (tagName=="script" || tagName=="iframe") {
				nodeObj.removeChild(nodeObj.childNodes[b]);
				continue;
			}
		}

		cleanNode(nodeObj.childNodes[b],targetObj);
	}
	
	// return nodeObj;
}

function insertClone(srcId,targetId,targetFrame) {
	if (!srcId) { // quick exit
		return;
	}
	if (targetFrame==undefined) {
		targetFrame=self;
	}
	var srcObj=$(srcId);
	var targetObj=targetFrame.$(targetId);
	if (!srcObj || ! targetObj) {
		return;
	}
	
	// make copy
	var copyObj=srcObj.cloneNode(true);
	
	// strip id, eventhandlers recursively
	cleanNode(copyObj,targetObj);
	//~ targetObj.appendChild(copyObj);
	targetObj.innerHTML=copyObj.innerHTML;
}

// end functions for printing

function loadCurrentDataset(init) {
	setControlValues(dataCache[a_db_id][a_pk],init);
}

function refreshListFilters(view) {
	if (!edit_views) {
		return;
	}
	if (view==undefined) {
		view=currentView;
	}
	
	// 1. alle subitemlists zurücksetzen
	for (var int_name in controls) {
		if (getControlElementType(int_name)=="subitemlist") {
			SILclearFilter(int_name);
		}
	}
	
	// 2. filter setzen
	if (edit_views[view]["filter"]) {
		for (var b=0,max=edit_views[view]["filter"].length;b<max;b++) {
			SILaddFilter(edit_views[view]["filter"][b]["int_name"],edit_views[view]["filter"][b]["field"],undefined,edit_views[view]["filter"][b]["op"],edit_views[view]["filter"][b]["val"]);
		}
	}
	
	// sort
	// SILsort(list_int_name,int_name,mode)
	if (edit_views[view]["sort"]) {
		for (var b=0,max=edit_views[view]["sort"].length;b<max;b++) {
			var mode=Number(edit_views[view]["sort"][b]["flags"]);
			if (isNaN(mode)) {
				mode=0;
			}
			SILsort(edit_views[view]["sort"][b]["int_name"],edit_views[view]["sort"][b]["field"],undefined,mode);
		}
	}
}

function activateEditView(view,init) {
	// view definition: edit_views[n][VISIBLE,"hidden"]=array(id)
	// edit_views ist $edit_views[$table]
	var oldview;
	if (!edit_views) {
		if (readOnly) {
			loadCurrentDataset(init);
		}
		return;
	}
	if (view==undefined) { // refresh view (after changing the dataset), display and position should be unchanged, but sorting and filtering is required. If performance sucks, do only those 2
		view=currentView;
	}
	else {
		oldview=currentView;
		currentView=view;
	}
	conserveView(currentView);
	if (!edit_views[view]) {
		return;
	}
	
	// visible
	if (edit_views[view]["visibleIds"]) {
		for (var b=0,max=edit_views[view]["visibleIds"].length;b<max;b++) {
			//~ showObj( view_ids [edit_views[view]["visibleIds"][b] ] );
			showObj(edit_views[view]["visibleIds"][b]);
		}
	}
	if (edit_views[view]["visibleControls"]) {
		for (var b=0,max=edit_views[view]["visibleControls"].length;b<max;b++) {
			//~ showControl( view_controls[ edit_views[view]["visibleControls"][b] ] ,true);
			showControl(edit_views[view]["visibleControls"][b],true);
		}
	}
	
	// hidden
	if (edit_views[view]["hiddenIds"]) {
		for (var b=0,max=edit_views[view]["hiddenIds"].length;b<max;b++) {
			//~ hideObj( view_ids[ edit_views[view]["hiddenIds"][b] ] );
			hideObj(edit_views[view]["hiddenIds"][b]);
		}
	}
	if (edit_views[view]["hiddenControls"]) {
		for (var b=0,max=edit_views[view]["hiddenControls"].length;b<max;b++) {
			//~ showControl( view_controls[ edit_views[view]["hiddenControls"][b] ] ,false);
			showControl(edit_views[view]["hiddenControls"][b],false);
		}
	}
	
	// customize button
	visibleObj("customize_view",(view=="custom_view"));
	
	if (readOnly) {
		// Listen setzen für zeitsparenden Aufbau
		loadCurrentDataset(init);
	}
	
	// position
	//~ if (edit_views[view]["position"]) {
		//~ for (var b=0,max=edit_views[view]["position"].length;b<max;b++) {
			//~ positionObj(edit_views[view]["position"][b]["id"],edit_views[view]["position"][b]["left"],edit_views[view]["position"][b]["top"]);
		//~ }
	//~ }
	
	// filter
	refreshListFilters(view);
	
	// class der links anpassen
	if (oldview) { // class wegnehmen
		setObjClass("view_"+oldview,"tab_light");
	}
	setObjClass("view_"+view,"tab_selected");
	focusInput("idx");
	onActivateViewForms(view,oldview);
}

function getAnalyticalDataImgURL(db_id,analytical_data_id,image_no,timestamp) {
	var retval="getGif.php?db_id="+db_id+"&analytical_data_id="+analytical_data_id+"&no_cache="+timestamp;
	if (image_no) {
		retval+="&image_no="+image_no;
	}
	if (archive_entity) {
		retval+="&archive_entity="+archive_entity;
	}
	return retval;
}

function getLiteratureImgURL(db_id,literature_id,timestamp) {
	var retval="getGif.php?db_id="+db_id+"&literature_id="+literature_id; // +"&no_cache="+timestamp; // improve loading time
	if (archive_entity) {
		retval+="&archive_entity="+archive_entity;
	}
	return retval;
}

function invokeAnalyticsEdit(list_int_name,UID,int_name,original) {
	var analytical_data_id=SILgetValue(list_int_name,UID,"analytical_data_id");
	var url="getSpz.php?db_id="+a_db_id+"&analytical_data_id="+analytical_data_id;
	if (original) {
		url+="&original=1";
	}
	if (readOnly==true) {
		self.location.href=url;
	}
	else {
		// get list_int_name and UID
		var rc_uid=SILgetValue(list_int_name,UID,"reaction_chemical_uid"),list_int_names=["reactants","reagents","products"],rc_list_int_name,nvps=[];
		for (var b=0,max=list_int_names.length;b<max;b++) {
			if (SILgetPos(list_int_names[b],rc_uid)!=undefined) {
				rc_list_int_name=list_int_names[b];
				break;
			}
		}
		if (rc_list_int_name) {
			// get Molfile_blob
			var molfile_blob=SILgetValue(rc_list_int_name,rc_uid,"molfile_blob");
		}
		else { // take 1st prod by default
			var molfile_blob=SILgetValue("products",SILgetUID("products",0),"molfile_blob");
		}
		if (molfile_blob) {
			// POST Molfile_blob to getSpz
			nvps.push("molfile_blob");
			nvps.push(molfile_blob);
		}
		asyncRequest(url,nvps);
	}
}

function invokeAnalyticsEditOrig(list_int_name,UID,int_name) {
	invokeAnalyticsEdit(list_int_name,UID,int_name,true);
}

var analytical_data_posFlags=256+512,analytical_data_rH=100,analytical_data_rW=100; // scroll x and y
function getAnalyticalDataImg(list_int_name,UID,int_name,db_id,analytical_data_id,image_no,timestamp,noMagnify) { // no group
	var retval="";
	if (analytical_data_id) {
		var url=getAnalyticalDataImgURL(db_id,analytical_data_id,image_no,timestamp);
		retval+="<img";
		if (!noMagnify) {
			retval+=" onMouseOver=\"showAnalyticsTooltip(event,this,"+fixQuot(url)+");\" onMousemove=\"alignOverlay(event,"+analytical_data_posFlags+","+analytical_data_rW+","+analytical_data_rH+");\"";
		}
		
		if (isMSIE) { // maybe one day they fix their buggy crap
			retval+=" width=\"800\"";
		}
		else if (isFF3x) {
			retval+=" style=\"width:100%\"";
		}
		else {
			//~ retval+=" width=\"100%\"";
			retval+=" style=\"max-width:100%\"";
		}
		if (UID) {
			retval+=" onDblClick=\"invokeAnalyticsEdit("+fixQuot(list_int_name)+","+fixQuot(UID)+","+fixQuot(int_name)+")\"";
		}
		retval+=" src="+fixStr(url)+">"; //  onLoad=\"window.setTimeout(function () { updateScrollPos(); },1000);\"
	}
	return retval;
}

function showAnalyticsTooltip(e,obj,url) {
	if (!obj) {
		return;
	}
	var iHTML="<img id=\"analytics_hover_img\" src=\""+url+"\" onMousemove=\"alignOverlay(event,"+analytical_data_posFlags+","+analytical_data_rW+","+analytical_data_rH+");\" onMouseout=\"hideOverlay();\">";
	prepareOverlay(obj,iHTML);
	alignOverlay(e,analytical_data_posFlags,analytical_data_rW,analytical_data_rH);
}

function getLiteratureImg(list_int_name,UID,int_name,db_id,literature_id,timestamp) {
	var retval="";
	if (literature_id) {
		retval+="<img onDblClick=\"invokeLiteratureRead("+fixQuot(db_id)+","+fixQuot(literature_id)+")\" src="+fixStr(getLiteratureImgURL(db_id,literature_id,timestamp))+">"; //  onLoad=\"window.setTimeout(function () { updateScrollPos(); },1000);\"
	}
	return retval;
}

function getLiteratureImgDelayed(list_int_name,UID,pos,int_name,db_id,literature_id,timestamp) {
	var retval="";
	if (literature_id) {
		if (pos<3) {
			retval+="<img onDblClick=\"invokeLiteratureRead("+fixQuot(db_id)+","+fixQuot(literature_id)+")\" src="+fixStr(getLiteratureImgURL(db_id,literature_id,timestamp))+">";
		}
		else {
			retval+="<img onMouseover=\"this.style.height=&quot;auto&quot;; this.style.width=&quot;auto&quot;; this.src=getLiteratureImgURL("+fixQuot(db_id)+","+fixQuot(literature_id)+","+fixQuot(timestamp)+"); this.onmouseover=null;\" style=\"width:100%;height:75px\"img onDblClick=\"invokeLiteratureRead("+fixQuot(db_id)+","+fixQuot(literature_id)+")\" src=\"lib/1x1.gif\">";
		}
	}
	return retval;
}

function invokeLiteratureRead(db_id,literature_id) {
	var url="getLiterature.php?db_id="+db_id+"&literature_id="+literature_id;
	window.open(url);
}

function refreshLiteratureImgId(list_int_name,UID,int_name) { // leave a_timestamp untouched in general
	var temp=a_timestamp;
	a_timestamp=Number(new Date());
	SILsetValueUID(list_int_name,UID,undefined,undefined,"literature_graphics_blob",undefined,{literature_id:SILgetValue(list_int_name,UID,"literature_id")});
	a_timestamp=temp;
}

function getLiteratureImgId(literature_id) { // id of the img
	return "literatureImg"+literature_id;
}

function updateLiteratureImg(db_id,literature_id,timestamp) {
	setiHTML("analytical_data_img","<img onDblClick=\"invokeLiteratureRead("+fixQuot(db_id)+","+fixQuot(literature_id)+")\" src=\"getGif.php?db_id="+db_id+"&literature_id="+literature_id+"&no_cache="+timestamp+"\">");
}

function updateAnalyticalDataImg(db_id,analytical_data_id,image_no,timestamp,noMagnify) { // for editing single spectrum
	setiHTML("analytical_data_img",getAnalyticalDataImg(undefined,undefined,undefined,db_id,analytical_data_id,image_no,timestamp,noMagnify));
}

function conserveView(view) {
	setSidenavValue("view",view);
	setInputValue("view",view); // exists only in settings-pages
}

function openSearchWin(list_int_name,UID,int_name,group,tableSelect,params) { // open win
	var url="searchWin.php?list_int_name="+list_int_name+"&UID="+UID+"&field="+int_name+"&group="+defBlank(group)+"&tableSelect="+tableSelect+defBlank(params); // +"&selectForTable="+table+"&selectForDbId="+a_db_id+"&selectForPk="+a_pk;
	window.open(url);
}

function analytics_type_updated() {
	// set
	PkSelectSetData("analytics_type_id",["analytics_type_code","analytics_type_text"]);
	PkSelectUpdate("analytics_device_id"); // Geräteliste updaten
}

function analytics_device_updated() {
	var int_name="analytics_device_id";
	// set
	PkSelectSetData(int_name,["analytics_device_driver"]);
	PkSelectUpdate("analytics_method_id"); // Methodenliste updaten
	// set folder/ftp-browser to starting point
	FBstartSearch("spzfile",getControlValue(int_name));
}

function analytics_method_updated() {
	PkSelectSetData("analytics_method_id",["analytics_method_text"]);
}

// geänderte Werte feststellen

function updateSaveButton() {
	visibleObj("btn_save",valuesChanged);
	visibleObj("btn_save_disabled",!valuesChanged);
}

var prepareDone,activeObj,activeVal;
function fC(e,obj) {
	//~ showMessage(String(obj));
	if (obj) {
		if (obj==activeObj) {
			return;
		}
		if (obj.value!=undefined) {
			activeVal=obj.value;
			//~ addiHTML("feedback_message","x"+obj.value+"y"+activeVal+"z");
			activeObj=obj;
			return;
		}
	}
	activeObj=undefined;
	activeVal=undefined;
}

function valChanged(obj,changed) {
	//~ alert(valChanged.caller+"S"+valuesChanged+"E"+changed);
	if (readOnly==true) {
		return true;
	}
	if (obj && obj.type && obj.tagName) {
		var type=obj.type.toLowerCase(),tagName=obj.tagName.toLowerCase();
		if ( (tagName=="input" && (type=="text" || type=="password")) || tagName=="textarea") {
			//~ addiHTML("info_box",obj.value+"x"+activeVal+"<br>");
			var act_value=obj.value;
			if (activeVal==undefined || act_value==activeVal) { // activeVal==undefined || 
				return false;
			}
			activeVal=act_value; // changing back should trigger change
			//~ alert(obj.value+" "+activeVal);
		}
	}
	if (valuesChanged==true && changed!=false) {
		return true;
	}
	if (changed==undefined) {
		changed=true;
	}
	valuesChanged=changed;
	updateSaveButton();
	return true;
}

function f1(e,parentObj) { // für tabellendesign
	if (!parentObj) {
		return;
	}
	var srcObj=getEvtSrc(e);
	if (srcObj) {
		var parentTagName=srcObj.tagName;
		if (parentTagName) {
			if (parentTagName.toLowerCase()!="td") {
				return;
			}
		}
	}
	for (var b=0,max=parentObj.childNodes.length;b<max;b++) {
		var tagName=parentObj.childNodes[b].tagName;
		if (!tagName) {
			continue;
		}
		var type=parentObj.childNodes[b].getAttribute("type");
		if (!type) {
			type="";
		}
		if (tagName) {
			if ((tagName.toLowerCase()=="input" || tagName.toLowerCase()=="textarea") && type.toLowerCase()!="hidden") {
				parentObj.childNodes[b].focus();
				try {
					e.stopPropagation();
				}
				catch (e) {
					// ignore
				}
				return;
			}
		}
	}
}

function hi(obj,active) {
	if (!obj || highlight_inputs==true) { // always white, no change
		return;
	}
	if (active) {
		obj.style.backgroundColor="white";
	}
	else {
		obj.style.backgroundColor="transparent";
	}
}

// stuff for special ones
function validateMultiple() {
	var add_multiple_input_value=parseInt(getInputValue("add_multiple"));
	if (isNaN(add_multiple_input_value) || add_multiple_input_value<1) {
		setInputValue("add_multiple",1);
	}
}

function readExtFeedback(text) {
	if (text==undefined) {
		text="";
	}
	setiHTML("readExtFeedback",text);
}

function readExtFailed() {
	readExtFeedback(s("readExtFailed"));
	parent.showObj("btn_create");
}

function readExt() {
	var cas_nr=getControlValue("cas_nr");
	if (!isCAS(cas_nr)) {
		alert(s("no_cas_nr"));
		return false;
	}
	else {
		readExtFeedback(s("readExtStart"));
		readExtTimeout=window.setTimeout("readExtFailed();",45000);
		var molecule_id=getControlValue("molecule_id");
		//~ var url="readExtAsync.php?editMode="+getBool(editMode)+"&cas_nr="+cas_nr+"&pk_exclude="+molecule_id+"&"+pageParams;
		var url="readExtAsync.php?"+getSelfRef(["~script~"])+"&editMode="+getBool(editMode)+"&cas_nr="+cas_nr+"&pk_exclude="+molecule_id;
		//~ alert(url);
		setFrameURL("edit",url);
		return true;
	}
}

function get_reference_url(ref_table,db_id,pk) {
	return "edit.php?"+getSelfRef(["~script~","table","cached_query"])+"&query=&table="+ref_table+"&db_id="+db_id+"&pk="+pk;
}

function get_reference_link(ref_table,db_id,pk) {
	return "<a href=\""+get_reference_url(ref_table,db_id,pk)+"\" class=\"imgButtonSm\"><img src=\"lib/details_sm.png\" border=\"0\""+getTooltip("details")+"></a>";
}

function goto_reference(ref_table,pk_name) {
	self.location.href=get_reference_url(ref_table,a_db_id,getControlValue(pk_name));
}

function get_reaction_link(db_id,pk,lab_journal_id) {
	var ref_table="reaction";
	var retval="<a href=\""+get_reference_url(ref_table,db_id,pk);
	if (lab_journal_id) {
		retval+="&query=<2>&crit2=lab_journal.lab_journal_id&op2=eq&val2="+lab_journal_id;
	}
	retval+="\" class=\"imgButtonSm\"><img src=\"lib/details_sm.png\" border=\"0\""+getTooltip("details")+"></a>";
	return retval;
}

function get_order_comp_link(db_id,pk) {
	return get_reference_link(db_id,pk,"order_comp");
	//~ return "<a href=\"javascript:goto_order_comp("+db_id+","+pk+")\" class=\"imgButtonSm\"><img src=\"lib/details_sm.png\" border=\"0\""+getTooltip("details")+"></a>";
}

// image buttons
function imgButton(id,img_url,img_url_deac) {
	this.id=id;
	this.img=preloadImg(img_url);
	this.img_deac=preloadImg(img_url_deac);
	this.enable=function(enable) {var img_obj=$(this.id); if (!img_obj) {return;} if (enable) {img_obj.src=this.img.src;} else {img_obj.src=this.img_deac.src;} };
}

var first=new imgButton("imgfirst","lib/1st.png","lib/1st_deac.png"),prev=new imgButton("imgprev","lib/prev.png","lib/prev_deac.png"),next=new imgButton("imgnext","lib/next.png","lib/next_deac.png"),last=new imgButton("imglast","lib/last.png","lib/last_deac.png");
var allowEdit,allowAdd,allowDelete,versionsInited;
function mayWrite() {
	if (allowWrite[a_db_id]!=true || a(formulare,page_forms[0],"disableEdit") || !allowEdit) {
		return false;
	}
	return true;
}

function mayAdd() {
	if (allowCreate[a_db_id]!=true || a(formulare,page_forms[0],"disableEdit") || !allowAdd) {
		return false;
	}
	return true;	
}

function updateButtons() {
	var ro,rw,ro_other,add_btn=true;
	if (editMode) {
		if (mayWrite()==false) { // fremde DB oder keine Schreibber
			if (mayAdd()==false) {
				add_btn=false;
			}
			ro=false;
			rw=false;
			ro_other=true;
		}
		else if (readOnly) { // tatsächlich readOnly, aber darf rw
			ro=true;
			rw=false;
			ro_other=true;
		}
		else {
			ro=false;
			rw=true;		
			ro_other=false;
		}
		visibleObj("buttons_ro",ro); // inline
		visibleObj("buttons_rw",rw); // inline
		visibleObj("buttons_ro_other",ro_other); // inline
		visibleObj("buttons_add",add_btn); // block
		visibleObj("btn_version",a_db_id==-1); // block
		visibleObj("btn_del",allowDelete);
	}
}

// toggle edit mode
function startEditMode(startWithValuesChanged,transferBarcode) {
	//~ if (getCacheValue("allowEdit")+""=="0") {
		//~ return;
	//~ }
	if (editMode) { // datensatz bearbeiten
		if (mayWrite()==false) { // kick out
			return;
		}
		setInputValue("version_before",0);
		setInputValue("version_after",0);
		showVersionsList(false);
		// lock, if already locked, ask
		// alert(a_db_id+" "+a_pk);
		//~ var url="editAsync.php?db_id="+a_db_id+"&pk="+a_pk+"&desired_action=lock&age_seconds="+getAgeSeconds()+"&"+pageParams; // table="+table+"&
		var url="editAsync.php?"+getSelfRef(["~script~"])+"&db_id="+a_db_id+"&pk="+a_pk+"&desired_action=lock&age_seconds="+getAgeSeconds();
		if (startWithValuesChanged) {
			url+="&valuesChanged=true";
		}
		if (transferBarcode) {
			url+="&transferBarcode="+transferBarcode;
		}
		// window.open(url);
		setFrameURL("edit",url);
		hideOverlay();
	}
	if (!editMode) { // done async for editMode because of locking
		readOnlyForms(false);
		readOnly=false;
		valChanged(null,false);
		updateButtons();
	}
}

function dblClickStartEditMode() {
	if (!readOnly || !editMode) {
		return;
	}
	startEditMode();
}

function getSimpleActionURL(desired_action) {
	//~ return "editAsync.php?db_id="+a_db_id+"&pk="+a_pk+"&desired_action="+desired_action+"&"+pageParams;
	return "editAsync.php?"+getSelfRef(["~script~"])+"&db_id="+a_db_id+"&pk="+a_pk+"&desired_action="+desired_action;
}

function getRenewURL() {
	return getSimpleActionURL("renew");
}

function getUnlockURL() {
	return getSimpleActionURL("unlock");
}

function renewLock() {
	if (readOnly==true) { // somehow not reset
		clearInterval(renewInterval);
	}
	else { // initiate lock renewal
		setFrameURL("edit",getRenewURL());
	}
}

function quickUnlock() {
	// unlock and reload dataset
	setFrameURL("edit",getUnlockURL());
}

function cancelEditMode() {
	quickUnlock();
	endEditMode();
	gotoDataset();
}

function prepareSaveChanges(desired_action) {
	if (readOnly || (editMode && !valuesChanged)) {
		return true;
	}
	// check conditions
	if (!checkSubmitForms()) {
		return false;
	}
	prepareSubmitForms();
	if (editMode) {
		prepareUpdate(a_db_id,a_pk,2);
	}
	setInputValue("desired_action",desired_action);
	return true;
}

function performNewVersion(version_before,version_comment_before,version_after,version_comment_after) {
	if (!readOnly && editMode && (version_before || version_after)) {
		setInputValue("version_before",version_before);
		setInputValue("version_comment_before",version_comment_before);
		setInputValue("version_after",version_after);
		setInputValue("version_comment_after",version_comment_after);
		valuesChanged=true; // force save
		saveChanges();
	}	
}

function saveNewVersion() {
	var url="editWin.php?mode=archive_version";
	window.open(url,Number(new Date()),"height=400,width=700,scrollbars=yes");
	// ask for comment
	/* var comment=prompt(s("enter_comment"),""); // no undefined
	if (comment!=null) {
		setInputValue("version_save",1);
		setInputValue("version_comment",comment);
		valuesChanged=true; // force save
		saveChanges();
	}*/
}

function saveChanges() { // Änderungen speichern
	// save and reload dataset (to avoid people believe things have been saved that have not)
	asyncGotoDataset=actIdx;
	if (prepareSaveChanges("update")) {
		//~ alert("Save");
		var main=$("main");
		main.target="edit";
		// alert(main.target);
		performAsync(true);
		endEditMode();
	}
}

function saveDataset() { // neuen Datensatz
	if (prepareSaveChanges("add")) {
		submitForm("main");
	}
}

function endEditMode() {
	readOnlyForms(true);
	readOnly=true;
	updateButtons();
}

function setAction(desired_action) {
	setInputValue("desired_action",desired_action);
}

function activateView(fields) {
	goBack(fields);
}

function goBack(fields) {
	if (fields==undefined) {
		fields=oldFields;
	}
	var url=listURL+"&fields="+fields;
	if (table=="reaction" && ref_reaction) {
		url+=getRefRxnParam();
	}
	if (editMode) {
		if (perPage>0 && actIdx>0) {
			url+="&page="+Math.floor(actIdx/perPage)+"#item"+(actIdx % perPage);
		}
	}
	// alert(url+" "+actIdx+" "+perPage);
	window.location.href=url;
}

// page selection control
function updateTotalCount() {
	var obj=$("totalCount");
	if (obj && dbIdx) {
		var dbIdx_len=dbIdx.length;
		if (dbIdx_len==0) {
			activateView(); // goto list
			return;
		}
		obj.innerHTML=dbIdx_len;
	}
}

function resetPolicies() {
	allowEdit=true,allowAdd=true,allowDelete=true;
	
}

// setzt die "Navigationseinheit" und zeigt über showDataset den angegebenen Datensatz an
function gotoDataset(idx,init) { // init: erstmaliges Laden eines Datensatzes beim Aufruf
	//~ alert(init+"G"+gotoDataset.caller);
	if (!is_numeric(idx)) {
		idx=actIdx;
	}
	if (idx<0) {
		idx=0;
		cancelFastmode();
	}
	else if(idx>dbIdx.length-1) {
		idx=dbIdx.length-1;
		cancelFastmode();
	}
	if (!prepareSaveChanges("update")) { // Speichern vorbereiten und dann mit showDataset ausführen
		return false;
	}
	if (!readOnly && !valuesChanged) {
		// quick unlock using GET
		quickUnlock();
		endEditMode();
	}
	showMessage();
	/* if (!readOnly) { // save when going to other dataset
		endEditMode();
		performAsync(true);
	} */
	
	// version selection
	archive_entity=undefined;
	resetPolicies();
	versionsInited=false;
	showVersionsList(false);
	
	showDataset(idx,init);
		
	// update images
	var isNotFirst=(idx>0),isNotLast=(idx<dbIdx.length-1);
	first.enable(isNotFirst);
	prev.enable(isNotFirst);
	next.enable(isNotLast);
	last.enable(isNotLast);
	$("idx").value=idx+1;
	actIdx=idx;
	updateButtons();
	return true;
}

function printMenuKeyUp(e) {
	var key=getKey(e);
	if (key==13 || key==10) { // number or arrow or f5 or Enter or bs or del or tab
		printDetail();
		return false;
	}
	else if (key==27) {
		showPrintMenu(false);
	}
}

function gotoLast() {
	gotoDataset(dbIdx.length-1);
}

function gotoFirst() {
	gotoDataset(0);
}

function gotoNext(fast) { // 1: running, 2: activate
	gotoDataset(actIdx+1);
	if (fast==2) {
		fastMode=true;
		fast=1;
	}
	if (fast==1 && fastMode) {
		keydownTimeout=window.setTimeout(function () { gotoNext(1); },fastmodeInt);
	}
}

function gotoPrev(fast) { // 1: running, 2: activate
	gotoDataset(actIdx-1);
	if (fast==2) {
		fastMode=true;
		fast=1;
	}
	if (fast==1 && fastMode) {
		keydownTimeout=window.setTimeout(function () { gotoPrev(1); },fastmodeInt);
	}
}

function scrollUp(fast) {
	scrollDefaultObj(-3);
	if (fast==2) {
		fastMode=true;
		fast=1;
	}
	if (fast==1 && fastMode) {
		keydownTimeout=window.setTimeout(function () { scrollUp(1); },scrollInt);
	}
}

function scrollDown(fast) {
	scrollDefaultObj(3);
	if (fast==2) {
		fastMode=true;
		fast=1;
	}
	if (fast==1 && fastMode) {
		keydownTimeout=window.setTimeout(function () { scrollDown(1); },scrollInt);
	}
}

function gotoPrevView() {
	var prev_view,view;
	for (view in edit_views) {
		if (view==currentView) {
			activateEditView(prev_view);
			break;
		}
		prev_view=view;
	}
}

function gotoNextView() {
	var prev_view,view;
	for (view in edit_views) {
		if (prev_view==currentView) {
			activateEditView(view);
			break;
		}
		prev_view=view;
	}
}

function gotoNum() {
	var idx=$("idx").value;
	if (readOnly==true && !is_numeric(idx)) {
		var re=/(.+?)\s*(\d+)/;
		if (table=="reaction" && re.exec(idx)) { // check if matches LJ entry with name of LJ
			idx=parseInt(RegExp.$2);
			gotoLJ(RegExp.$1,idx);
		}
	}
	else {
		idx=parseInt(idx);
		gotoDataset(idx-1);
	}
}

function cancelFastmode() {
	if (keydownTimeout) {
		window.clearTimeout(keydownTimeout);
		fastMode=false;
		fastCount=0;
	}
}

function idxHandleKeyup(e) {
	cancelFastmode();
	var key;
	if (window.event) { // IE
		e=window.event;
	}
	key=e.keyCode;
	// check if no modifiers pressed
	if (e.ctrlKey==true && e.altKey==false && e.shiftKey==false) {
		if (key==37) {
			gotoPrevView();
		}
		else if (key==39) {
			gotoNextView();
		}
	}
	if (e.altKey==true || e.ctrlKey==true || e.shiftKey==true) {
		return true;
	}
	if (key==34) {
		gotoNext();
	}
	else if (key==33) {
		gotoPrev();
	}
	else if (key==36) {
		gotoFirst();
	}
	else if (key==35) {
		gotoLast();
	}
	else if (key==38) { // scroll up
		scrollUp();
	}
	else if (key==40) { // scroll down
		scrollDown();
	}
	else if (key==113) {
		if (is_function(startEditMode)) {
			startEditMode();
		}
	}
	else if (key==115) {
		activateSearch(true);
	}
	/* else if ((key>=48 && key<=57) || (key>=37 && key<=40) || key==116 || key==13 || key==10 || key==8 || key==46 || key==9) { // number or arrow or f5 or Enter or bs or del or tab
		return true;
	} */
	// return true;
}

function idxHandleKeydown(e) {
	if (fastMode) {
		return;
	}
	var key=getKey(e);
	if (key==34) {
		keydownTimeout=window.setTimeout(function () { gotoNext(2); },fastmodeWait);
	}
	else if (key==33) {
		keydownTimeout=window.setTimeout(function () { gotoPrev(2); },fastmodeWait);
	}
	else if (key==38) { // scroll up
		keydownTimeout=window.setTimeout(function () { scrollUp(2); },fastmodeWait);
	}
	else if (key==40) { // scroll down
		keydownTimeout=window.setTimeout(function () { scrollDown(2); },fastmodeWait);
	}
}

function showMessage2(text) {
	if (text==undefined) {
		text="";
	}
	setiHTML("feedback_message2",text);
}

function del(no_confirm) {
	if (!no_confirm && !confirm(s("delWarning"))) {
		return;
	}
	// aktuellen datensatz löschen
	//~ var url="editAsync.php?desired_action=del&"+pageParams+"&db_id="+a_db_id+"&pk="+a_pk; // &table="+table+"
	var url="editAsync.php?"+getSelfRef(["~script~"])+"&desired_action=del&db_id="+a_db_id+"&pk="+a_pk;
	if (table=="reaction") {
		// reload empty data from db
		url+="&refresh=true&refresh_data[]="+a_db_id+","+a_pk;
	}
	setFrameURL("edit",url);
	
	if (table!="reaction") { //  && dispose_instead_of_delete!=true
		// aus cache löschen
		deleteDatasetFromCache(actIdx);
	}
}

function startMerge() {
	togglePkSearch("merge");
	focusInput("srcInput_merge");
}

function merge(new_pk,new_name) {
	var old_name=getCacheValue(a(controls,"merge","nameField"));
	if (!confirm(s("merge_warning1")+old_name+s("merge_warning2")+new_name+s("merge_warning3"))) {
		return;
	}
	// aktuellen datensatz löschen
	//~ var url="editAsync.php?desired_action=merge&db_id="+a_db_id+"&pk="+a_pk+"&new_pk="+new_pk+"&"+pageParams; // &table="+table+"
	var url="editAsync.php?"+getSelfRef(["~script~"])+"&desired_action=merge&db_id="+a_db_id+"&pk="+a_pk+"&new_pk="+new_pk;
	setFrameURL("edit",url);
	// aus cache löschen
	deleteDatasetFromCache(actIdx);
	// maybe: switch to new dataset (?)
}

function saveOpenDataset() {
	// confirm(..) not allowed here
	if (!readOnly) {
		if (!valuesChanged) {
			if (a_db_id && a_pk && !window.open(getUnlockURL())) {
				//console.log("unlocking failed");
				return "Unlocking failed";
			}
			// otherwise nothing to do
		}
		else {
			var main=$("main");
			if (prepareSaveChanges("update")) {
				// open self-closing window to save changes
				// alert("Save on exit");
				main.target="_blank";
				// alert(main.target);
				try {
					//console.log("Submit called");
					main.submit();
				} catch (e) {
					// popup blocker
					return s("discard_changes");
				}
			} else {
				return s("discard_changes");
			}
		}
	}
}

// special functions
function validate_new_chemical_storage() {
	var obj=$("new_chemical_storage");
	if (obj) {
		lockForm("chemical_storage",!obj.checked);
		if (obj.checked) {
			PkSelectUpdate("storage_id"); // update according to poison rules, if not dynamic, nothing happens
		}
		// lockName("chemical_storage_FS",!obj.checked);
	}
}

function validate_new_lab_journal() {
	var obj=$("new_lab_journal");
	if (obj) {
		lockForm("lab_journal",!obj.checked);
	}
}

function moleculePropertyClassChanged(list_int_name,UID,int_name) {
	var className=SILgetValue(list_int_name,UID,int_name),unitValue=SILgetValue(list_int_name,UID,"unit");
	SILsetUnitSelect(list_int_name,UID,"unit",undefined,className,unitValue);
}

function updateSafety(what) {
	if (!what || what=="sym") {
		var iHTML="";
		if (use_rs) {
			iHTML+=getSymbols("rs",getControlValue("safety_sym"));
		}
		if (use_ghs) {
			iHTML+=getSymbols("ghs",getControlValue("safety_sym_ghs"));
		}
		setiHTML("symbols",iHTML);
	}
	if (!what || what=="r") {
		setiHTML("Rclause",procClauses("R",getControlValue("safety_r")));
	}
	if (!what || what=="s") {
		setiHTML("Sclause",procClauses("S",getControlValue("safety_s")));
	}
	if (!what || what=="h") {
		setiHTML("Hclause",procClauses("H",getControlValue("safety_h")));
	}
	if (!what || what=="p") {
		setiHTML("Pclause",procClauses("P",getControlValue("safety_p")));
	}
}

function updateInstructions() {
	// update all working instructions on save
	var list_int_name="molecule_instructions";
	for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
		var UID=controlData[list_int_name]["UIDs"][b];
		if (SILgetDesiredAction(list_int_name,UID)=="") { // "del" should not be changed to "update"
			SILsetDesiredAction(list_int_name,UID,"update");
		}
	}
}

function editHistory(obj,int_name,thisReadOnly) {
	if (obj) {
		showOverlay(obj,nl2br(getCacheValue(int_name)));
	}
}

function editMouseoverCHN(obj,int_name,thisReadOnly) {
	//~ var obj=getControlObj(int_name,thisReadOnly);
	if (obj) {
		showCHNTooltip(obj,getControlValue(int_name));
	}
}

function editHideOverlay(int_name,thisReadOnly) {
	hideOverlay();
}

function updateSearchCommercial(cas_nr,smiles) {
	var obj=$("search_commercial");
	if (obj && cas_nr) {
		obj.style.display=(cas_nr==""?"none":"");
		//~ obj.href="searchExt.php?supplier=all&query=<0>&crit0=molecule.cas_nr&op0=ex&val0="+cas_nr+"&"+pageParams;
		obj.href="searchExt.php?"+getSelfRef(["~script~","table"])+"&supplier=all&query=<0>&crit0=molecule.cas_nr&op0=ex&val0="+cas_nr;
	}
	var obj=$("pred_nmr");
	if (obj && smiles) {
		obj.style.display=(smiles=="" || smiles.indexOf(".")!=-1 || smiles.indexOf("+")!=-1 ?"none":""); // no multipart
		obj.href="http://www.nmrdb.org/service.php?name=all-predictions&smiles="+escape(smiles);
	}
}

function getGotoReactionLink(lab_journal_id,reaction_id) {
	if (reaction_id==undefined) {
		return "";
	}
	return "<a href=\"javascript:void goto_reaction("+lab_journal_id+","+reaction_id+")\" class=\"imgButtonSm\"><img src=\"lib/reaction_sm.png\" border=\"0\""+getTooltip("from_reaction_id")+"></a>";
}

function updateLJSigle() {
	// is there already a sigle?
	if (getInputValue("lab_journal_code")!="") {
		return;
	}
	
	// get selectIndex
	var idx=getSelectedIndex("person_id")-1; // because of none
	
	if (idx<0) {
		return;
	}
	
	// get sigle for person
	var sigle=a(controlData,"person_id","data",idx,"sigle");
	
	// set sigle
	setInputValue("lab_journal_code",sigle);
}

var actual_amount_set,tmd_set;
function actual_amount_focus() {
	var mass_fixed=radioButtonValue("mass_fixed");
	if (!mass_fixed || mass_fixed=="act") {
		// select something else
		if (update_tmd_set()) {
			setMassFixed("tmd");
		}
		else {
			setMassFixed("total");
		}
	}
}

function actual_amount_changed(src) {
	if (src && src.id=="actual_amount_rounded") {
		actual_amount_set=(getInputValue("actual_amount")!=="");
	} else {
		actual_amount_set=false;
	}
	
	// update total amount, tmd is left unchanged
	update_mass_calc("act");
	
	// show field to enter reason
	showControl("reason",true);
}

function amount_changed() {
	//~ alert("Y"+getInputValue("actual_amount"));
	if (actual_amount_set==undefined) {
		 actual_amount_changed();
	}
	if (actual_amount_set==false) {
		actual_amount_focus(); // update radio buttons
		setControlValue("actual_amount",getInputValue("amount"));
		actual_amount_changed();
	}
}

function tmd_focus() {
	var mass_fixed=radioButtonValue("mass_fixed");
	if (!mass_fixed || mass_fixed=="tmd") {
		setMassFixed("act");
	}
}

function setMassFixed(mass_fixed) {
	var tmd_value=getInputValue("tmd");
	if ((mass_fixed=="act" && (getInputValue("actual_amount")!=="" || tmd_value==="")) || (mass_fixed=="tmd" && tmd_value!=="") || (mass_fixed=="total" && getInputValue("total_mass")!=="")) {
		selectRadioButton("mass_fixed",mass_fixed);
	}
	else {
		unselectRadioButton("mass_fixed");
	}
}

function update_tmd_set() {
	tmd_set=(getInputValue("tmd")!=="");
	return tmd_set;
}

function tmd_changed() {
	update_tmd_set();
	
	// update total amount, actual_amount is left unchanged
	update_mass_calc("tmd");
}

function tmd_unit_changed() { // called also by setValues
	setControlValue("total_mass_unit",getInputValue("tmd_unit"));
	tmd_changed();
}

function update_mass_calc(src) {
	var total_mass=getInputValue("total_mass"),tmd=getInputValue("tmd");
	if (total_mass==="" && tmd==="") { // sinnlos
		return;
	}
	
	var amount_unit=getInputValue("amount_unit"),total_mass_unit=getInputValue("total_mass_unit"),tmd_unit=getInputValue("tmd_unit"),density_20=1;
	var amount_is_volume=(getUnitType(amount_unit)=="v"); // getUnitFactor(unitName)
	if (amount_is_volume) {
		density_20=def1(getInputValue("density_20"));
		if (density_20<=0) {
			density_20=1;
		}
	}
	
	var mass_fixed=radioButtonValue("mass_fixed");
	if (mass_fixed!="tmd" && src!="tmd") {
	//~ if (tmd_set==false && src!="tmd") { // neueingabe, tmd berechnen
		var factor=getUnitFactor(tmd_unit);
		if (factor>0) {
			var tmd=(total_mass*getUnitFactor(total_mass_unit)-getInputValue("actual_amount")*getUnitFactor(amount_unit)*density_20)/factor; // fixme units, density
			//~ alert("tmd"+tmd);
			setControlValue("tmd",tmd);
		}
	}
	else if (mass_fixed!="total" && src!="total") {
	//~ else if (src!="total") {
		var actual_amount=getInputValue("actual_amount"),factor=getUnitFactor(total_mass_unit);
		if (tmd_set && factor>0) { // gesamtmasse aus tmd und actual_amount berechnen
			var total_mass=(tmd*getUnitFactor(tmd_unit)+actual_amount*getUnitFactor(amount_unit)*density_20)/factor;
			//~ alert("total_mass"+total_mass);
			setControlValue("total_mass",total_mass);
		}
	}
	else if (mass_fixed!="act" && src!="act") { // vorhandenes tmd nutzen, um menge zu aktualisieren
	//~ else if (src!="act") { // vorhandenes tmd nutzen, um menge zu aktualisieren
		var factor=getUnitFactor(amount_unit);
		if (factor>0) {
			var actual_amount=(total_mass*getUnitFactor(total_mass_unit)-tmd*getUnitFactor(tmd_unit))/(factor*density_20); // fixme units, density
			//~ alert("actual_amount"+actual_amount);
			setControlValue("actual_amount",actual_amount);
		}
	}
}

function total_mass_focus() {
	var mass_fixed=radioButtonValue("mass_fixed");
	if (!mass_fixed || mass_fixed=="total") {
		// select something else
		if (update_tmd_set()) {
			setMassFixed("tmd");
		}
		else {
			setMassFixed("act");
		}
	}
}

function total_mass_changed() {
	if (tmd_set==undefined) {
		 tmd_changed();
	}
	update_mass_calc("total");
}

function total_mass_unit_changed() {
	setControlValue("tmd_unit",getInputValue("total_mass_unit"));
	total_mass_changed();
}

function goto_reaction(lab_journal_id,reaction_id) {
	var url=getSelfRef(["cached_query","fields","order_by"])+"&table=reaction&view=ergebnis&dbs="+a_db_id+"&query=<0>&crit0=lab_journal.lab_journal_id&op0=eq&val0="+lab_journal_id+"&db_id="+a_db_id+"&pk="+reaction_id;
	self.location.href=url;
}

function gotoLJ(lab_journal_code,nr_n_lab_journal) {
	var url=getSelfRef(["cached_query","fields","order_by"])+"&query=<0>&crit0=lab_journal.lab_journal_code&op0=ex&val0="+lab_journal_code+"&select_query=<2>&crit2=reaction.nr_in_lab_journal&op2=eq&val2="+nr_n_lab_journal;
	if (parent && parent.sidenav) {
		parent.sidenav.setSelectedIndex("val0",0);
		parent.sidenav.setSelectSelectedText("val1",lab_journal_code,true);
		parent.sidenav.touchOnChange("val0");
		parent.sidenav.touchOnChange("val1");
	}
	
	// keep view
	if (currentView) {
		url+="&view="+currentView;
	}
	
	self.location.href=url;
}

function filterOff(keepFilter) {
	var url;
	switch (keepFilter) {
	case "lab_journal":
		// set sidenav
		var lab_journal_id=getControlValue("lab_journal_id");
		if (parent && parent.sidenav) {
			parent.sidenav.setSelectedIndex("val0",0);
			parent.sidenav.setInputValue("val1",lab_journal_id);
			parent.sidenav.touchOnChange("val1");
		}
		
		url=getSelfRef(["cached_query","fields","order_by"])+"&query=<0>&crit0=lab_journal_id&op0=eq&val0="+lab_journal_id;
	break;
	case "project":
		// set sidenav
		var project_id=getControlValue("project_id");
		if (parent && parent.sidenav) {
			parent.sidenav.setInputValue("val0",project_id);
			parent.sidenav.touchOnChange("val0");
			parent.sidenav.setSelectedIndex("val1",0);
		}
		
		url=getSelfRef(["cached_query","fields","order_by"])+"&query=<0>&crit0=project_id&op0=eq&val0="+project_id;
	break;
	default:
		// set sidenav
		if (parent && parent.sidenav) {
			parent.sidenav.setSelectedIndex("val0",0);
			parent.sidenav.setSelectedIndex("val1",0);
		}
		
		url=getSelfRef(["cached_query","fields","order_by"])+"&query=";
	}
	
	// keep view
	if (currentView) {
		url+="&view="+currentView;
	}

	if (parent && parent.sidenav) {
		parent.sidenav.setSelectedIndex("val2",0);
		parent.sidenav.setSelectedIndex("val3",0);
		parent.sidenav.setSelectedIndex("val4",0);
		parent.sidenav.setSelectedIndex("reaction_started_when",0,true);
		parent.sidenav.setSelectedIndex("order_by",0);
		parent.sidenav.setSelectedIndex("list_op",0);
	}
	url+="&db_id="+a_db_id+"&pk="+a_pk;
	self.location.href=url;
}
