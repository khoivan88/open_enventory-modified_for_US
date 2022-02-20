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
var readOnly,editMode,archive_entity,loadValues;

var tooltip_timeout;

function removeListLine(idx) {
	removeId("table_"+idx);
	
	// reduce count
	var obj=$("list_count");
	if (obj) {
		var num=parseInt(obj.innerHTML);
		if (num>0) {
			num--;
			obj.innerHTML=num;
		}
	}
}

function printLabels(size,per_row,per_col,parameter) {
	var url="labels.php?label_size="+size+"&per_row="+per_row+"&per_col="+per_col+"&"+defBlank(parameter);
	window.open(url);
}

function showExportMenu(show) {
	visibleObj("exportMenu",show);
}

function keyUpInventory(e,pk,idx) {
	var key=getKey(e);
	if (key==13) {
		updateInventory(pk,idx);
	}
}

function saveListOptions(col_options_key) {
	// 
	var activeOptions=[];
	var this_fields=col_options[col_options_key];
	if (!this_fields) {
		return;
	}
	for (var b=0,max=this_fields.length;b<max;b++) {
		if (getChecked(this_fields[b])==true) {
			activeOptions.push(this_fields[b]);
		}
	}
	as("view_options",activeOptions,col_options_key,"fields");
	activateView(fields);
}

function showOptionsMenu(int_name,obj) {
	showObj("options_"+int_name);
	if (obj) {
		showOverlayId(obj,"options_"+int_name,0,0,8);
	}
}

function hideOptionsMenu(int_name) {
	hideObj("options_"+int_name);
}

function changeMessageCompletionList(db_id,pk,status) {
	var url="listAsync.php?desired_action=message_status&table="+table+"&db_id="+db_id+"&pk="+pk+"&completion_status="+status;
	//~ alert(url);
	setFrameURL("comm",url);
}

function displayInventory(idx,values) {
	setiHTML("inventory_"+idx,ifnotempty("",a(values,"inventory_check_by")," (<nobr>"+toGerDate(a(values,"inventory_check_when"))+"</nobr>)"));
	setInputValue("input_actual_amount_"+idx,a(values,"actual_amount"));
	setiHTML("actual_amount_"+idx,a(values,"actual_amount")+"&nbsp;"+a(values,"amount_unit")+" / ");
}

function displayBorrow(idx,value,borrowed_by) { // value=1: given back
	visibleObj("btn_bring_back_"+idx,!value);
	visibleObj("btn_borrow_"+idx,value);
	setiHTML("borrowed_by_"+idx,borrowed_by);
}

function simpleExtSearch(supplier) {
	setInputValue("simpleExtSearchSupplier",supplier);
	submitForm("simpleExtSearch");
}

function setSelect(b) {
	var totalChange=0,url="table="+table+"&desired_action=";
	if (b==undefined) {
		var toValue=getChecked("sel_all");
		url+=(toValue?"select":"unselect");
		for (var b=0,max=itemData.length;b<max;b++) {
			var sel=$("sel_"+b);
			if (!sel) {
				continue;
			}
			sel.checked=toValue;
			totalChange+=toValue-def0(itemData[b][2]);
			itemData[b][2]=toValue;
			url+="&db_id[]="+itemData[b][0]+"&pk[]="+itemData[b][1];
		}
	}
	else {
		var toValue=getChecked("sel_"+b);
		url+=(toValue?"select":"unselect");
		totalChange+=toValue-def0(itemData[b][2]);
		itemData[b][2]=toValue;
		url+="&db_id[]="+itemData[b][0]+"&pk[]="+itemData[b][1];
	}
	setFrameURL("comm","manageSelect.php?"+url);
	if (parent.topnav && table=="chemical_storage") {
		parent.topnav.changeTotalSelect(totalChange);
	}
}

// Column handling

function showCol(colname) {
	if (fields=="") {
		fields=colname;
	}
	else {
		fields+=","+colname;
	}
	activateView(fields);
}

function hideCol(colname) {
	allCols=fields.split(",");
	newCols=[];
	for (b=0,max=allCols.length;b<max;b++) {
		if (allCols[b]!=colname) {
			newCols[newCols.length]=allCols[b];
		}
	}
	activateView(newCols.join(","));
}

function conserveFields(fields) {
	if (getSidenavValue("table")==table) {
		setSidenavValue("fields",fields);
		setSidenavValue("view_options",JSON.stringify(view_options));
	}
	else {
		setSidenavValue("fields","");
		setSidenavValue("view_options","");
	}
}

function del(db_id,pk,idx) {
	if (db_id!=-1) {
		return;
	}
	if (table=="person" && pk==person_id && !warningConfirmed()) {
		return;
	}

	if (confirm(delWarning)) {
		var url="listAsync.php?"+getSelfRef(["~script~"])+"&desired_action=del&db_id="+db_id+"&pk="+pk+"&idx="+defBlank(idx);
		setFrameURL("comm",url);
	}
}

function setOrder(col) {
	self.location.href=getSelfRef(["order_by"])+"&refresh=all&order_by="+col;
}

function refreshData() {
	self.location.href=getSelfRef()+"&refresh=all";
}

function addNew() {
	self.location.href="edit.php?"+getSelfRef(["~script~"])+"&db_id=-1&desired_action=new";
}

function startExport() {
	var url="export.php?output_type="+getInputValue("output_type")+"&"+keepParams(["table","view_options"])+"&fields=";
	if (getChecked("export_visible")) {
		url+=fields;
	}
	else {
		url+="all";
	}
	if (getChecked("export_selection")) {
		url+="&per_page=-1&query=&selected_only=1&list_op=2&ref_cache_id="+a(self_ref,"cached_query");
	}
	else {
		url+="&cached_query="+a(self_ref,"cached_query");
		if (getChecked("export_all")) {
			url+="&per_page=-1";
		}
		else {
			url+="&per_page="+a(self_ref,"per_page")+"&page="+a(self_ref,"page");
		}
	}
	window.open(url);
	showExportMenu(false);
}

function activateView(set_fields) {
	if (!set_fields) {
		set_fields=fields;
	}
	conserveFields(fields);
	var url=getSelfRef(["ref_reaction_db_id","ref_reaction_id","fields"])+"&fields="+set_fields;
	
	if (ref_reaction) {
		url+=getRefRxnParam();
	}
	if (view_options) {
		url+="&view_options="+JSON.stringify(view_options);
	}
	
	self.location.href=url;
}

function updateInventory(pk,idx) {
	var obj=$("input_actual_amount_"+idx);
	if (!obj) {
		return;
	}
	var actual_amount=fixNull(obj.value);
	if (isNaN(actual_amount)) {
		return;
	}

	var url="listAsync.php?"+getSelfRef(["~script~"])+"&db_id=-1&pk="+pk+"&idx="+idx+"&actual_amount="+actual_amount+"&desired_action=inventory";
	setFrameURL("comm",url);
}

function resetSelection(){
	top.topnav.resetTotalSelect();
	self.location.href=getSelfRef();
}

function warningConfirmed() {
	return confirm(s("delWarningUser"));
}

function activateSelfView() {
	if (table=="reaction") {
		activateSearch(false);
	}

	setObjClass("activeView","tab_selected");
}

setTitle();