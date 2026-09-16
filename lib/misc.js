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

// handle selection in input,textarea
/* function wrapSelection(text_input,before,after,mode) { // mode: 0 gotoEnd of after 1 keepSelection 2 overwrite
	if (!text_input || before==undefined) {
		return;
	}
	if (after==undefined) {
		after="";
	}
	var insText="";
	
	if (typeof document.selection!="undefined") {
		text_input.focus();
		var range=document.selection.createRange();
		if (mode==1) {
			insText=range.text;
		}
		//~ alert(insText);
		
		range.text=before+insText+after;
		
		range=document.selection.createRange();
		if (insText.length==0) {
			range.move("character",-after.length);
		}
		else {
			range.moveStart("character",before.length+insText.length+after.length);      
		}
		range.select();
	}
	else if (typeof text_input.selectionStart!="undefined") {
		var start=text_input.selectionStart;
		var end=text_input.selectionEnd;
		if (mode==1) {
			insText=text_input.value.substring(start,end);
		}
		//~ alert(insText);
		
		text_input.value=text_input.value.substr(0,start)+before+insText+after+text_input.value.substr(end);
		
		var newStart=start+before.length;
		var newEnd=newStart;
		if (insText.length>0) {
			newEnd+=insText.length;
		}
		else {
			newEnd+=after.length-1;
			if (mode==0) {
				newStart=newEnd;
			}
		}
		text_input.selectionStart=newStart;
		text_input.selectionEnd=newEnd;
	}
}*/

function getCookie(name) {
	if (!document.cookie) {
		return undefined;
	}
	var cook=document.cookie,begin,end;
	begin=cook.indexOf("; "+name+"=");
	if (begin==-1) {
		if (cook.indexOf(name+"=")==0) {
			begin=-2;
		}
		else {
			return undefined;
		}
	}
	end=cook.indexOf(";",begin+name.length+2);
	if (end!=-1) {
		return unescape(cook.substring(begin+name.length+3,end));
	}
	else {
		return unescape(cook.substring(begin+name.length+3));
	}
}

function getRCname(list_int_name) { // for reaction analytics and comparison
	return s(list_int_name.substr(0,list_int_name.length-1));
}

function showMessage(text) {
	setiHTML("feedback_message",defBlank(text));
}

function getBool(variable) {
	return variable?true:false;
}

function wrapSelection(text_input,before,after,mode) { // mode: 0 gotoEnd of after 1 keepSelection 2 overwrite
	if (!text_input || before==undefined) {
		return;
	}
	text_input.focus();
	if (after==undefined) {
		after="";
	}
	var val=text_input.value,selStart=getSelStart(text_input),selEnd=getSelEnd(text_input),selText="";
	if (mode!=2) {
		selText=val.substr(selStart,selEnd-selStart);// selStart=text_input.selectionStart,selEnd=text_input.selectionEnd;
	}
	text_input.value=val.substr(0,selStart)+before+selText+after+val.substr(selEnd);
	
	var newStart=selStart+before.length;
	var newEnd=newStart;
	if (selText.length>0) {
		newEnd+=selText.length;
	}
	else {
		newEnd+=after.length;
		if (mode==0) {
			newStart=newEnd;
		}
	}
	setSel(text_input,newStart,newEnd);
	updateSel();
	// text_input.selectionStart=selStart+before.length+(mode==1?0:val.length+after.length);
	// text_input.selectionEnd=selEnd+before.length+(mode==1?0:after.length);
	if (text_input.tagName.toLowerCase()=="input") {
		text_input.focus();
	}
}

var queryStart=0,queryEnd=0;
function updateSel() {
	if (document.selection!=undefined) { // IE sh*t
		queryStart=Math.abs(document.selection.createRange().moveStart("character",-1000000));
		queryEnd=Math.abs(document.selection.createRange().moveEnd("character",-1000000));
	}
}

function setSel(inp,start,end) {
	if (document.selection!=undefined) { // IE sh*t
		var range=inp.createTextRange();
		range.collapse(true);
		range.moveStart("character",start);
		range.moveEnd("character",end-start);
		range.select();
	}
	else {
		inp.setSelectionRange(start,end);
	}
}

function getSelStart(inp) {
	if (document.selection!=undefined) { // IE sh*t
		// inp.focus();
		return queryStart; // Math.abs(document.selection.createRange().moveStart("character",-1000000));
	}
	else {
		return inp.selectionStart;
	}
}

function getSelEnd(inp) {
	if (document.selection!=undefined) { // IE sh*t
		// inp.focus();
		return queryEnd; // Math.abs(document.selection.createRange().moveEnd("character",-1000000));
	}
	else {
		return inp.selectionEnd;
	}
}

function XOR(a,b) {
	return ( a || b ) && !( a && b );
}

function getPageBreak() {
	return "<br style=\"page-break-before:always\">";
}

var scrollPosX=0,scrollPosY=0,autoScrollInProgress,updateInProgress;
function saveScrollPos() {
	if (updateInProgress || autoScrollInProgress) {
		return;
	}
	var obj=$("browsemain");
	//~ alert(autoScrollInProgress+saveScrollPos.caller);
	if (obj) {
		scrollPosX=obj.scrollLeft;
		scrollPosY=obj.scrollTop;
		//~ alert(scrollPosX+"Y"+scrollPosY);
	}
}

function updateScrollPos() {
	if (updateInProgress) {
		return;
	}
	var obj=$("browsemain");
	if (obj) {
		//~ alert(updateScrollPos.caller);
		//~ alert(scrollPosX+"X"+scrollPosY);
		autoScrollInProgress=true;
		obj.scrollLeft=scrollPosX;
		obj.scrollTop=scrollPosY;
		autoScrollInProgress=false;
	}
}

if (!top.defaultTitle) {
	top.defaultTitle=top.document.title;
}

function setTitle(text) {
	if (text!=undefined) {
		top.document.title=text+ifnotempty(" - ",top.defaultTitle);
	}
	else {
		top.document.title=top.defaultTitle;
	}
}

function searchSidenav(val0) {
	if (top.sidenav) {
		top.sidenav.setInputValue("val0",val0);
		top.sidenav.submitForm("searchForm");
	}
}

function showInfo(text) {
	if (text==undefined) {
		text="";
	}
	setiHTML("info_box",text);
}

function getImgForSrc(url,useSvg,href,commonParams,w,h) {
	var retval="";
	commonParams=defBlank(commonParams);
	if (w) {
		commonParams+=" width="+fixStr(w);
	}
	if (h) {
		commonParams+=" height="+fixStr(h);
	}
	if (useSvg) {
		retval+="<object data="+fixStr(url)+commonParams+" type=\"image/svg+xml\"";
		if (href) {
			retval+=" href="+fixStr(href);
		}
		retval+="><param name=\"src\" value="+fixStr(url)+"></object>";
	}
	else {
		retval+="<img src="+fixStr(url)+commonParams+" border=\"0\">";
	}
	return retval;
}

function acceptPkSelection(thisTransferParameters,thisTable,db_id,pk) { // nimmt von childWin die Daten entgegen, und zwar Tabelle, aus der ausgewählt wurde (manchmal mehrere möglich und db_id/pk
	var url="chooseAsync.php?desired_action=loadDataForPk&table="+thisTable+"&db_id="+db_id+"&pk="+pk;
	url+="&list_int_name="+defBlank(thisTransferParameters["list_int_name"])+"&UID="+defBlank(thisTransferParameters["UID"])+"&field="+defBlank(thisTransferParameters["field"])+"&group="+defBlank(thisTransferParameters["group"])+"&beforeUID="+defBlank(thisTransferParameters["beforeUID"]);
	url+="&editDbId="+defBlank(thisTransferParameters["editDbId"])+"&editPk="+defBlank(thisTransferParameters["editPk"])+"&sess_proof="+defBlank(thisTransferParameters["sess_proof"])+"&selectForTable="+table+"&selectForDbId="+a_db_id+"&selectForPk="+a_pk;
	setFrameURL("comm",url);
}

function transferThisEntryToUID(list_int_name,UID,int_name,group) {
	var table,db_id,pk;
	switch (list_int_name) {
	case "products":
		table="reaction_chemical";
		db_id=a_db_id;
		pk=SILgetValue(list_int_name,UID,"reaction_chemical_id",group);
	break;
	case "chemical_storage":
		table="chemical_storage";
		db_id=a_db_id;
		pk=SILgetValue(list_int_name,UID,"chemical_storage_id",group);
	break;
	default:
		return;
	}
	transferPkToUID(table,db_id,pk);
}

function transferThisPkToUID() { // wählt im edit.php den aktuellen Datensatz aus
	transferPkToUID(table,a_db_id,a_pk);
}

function transferFromOpener(list_int_name,UID,int_name,group) {
	var this_value=opener.SILgetValue(list_int_name,UID,int_name,group);
	if (this_value!=undefined) {
		setInputValue(int_name,this_value);
	}
}

// coming from lib_global_funcs.php
var valuesChanged=false;
function valChanged(changed) {
	return true;
}

function getFormAction() {
	if ($("view_edit").checked) {
		return "edit.php";
	}
	else {
		return "list.php";
	}
}

function setFormAction() { // for sidenav & reaction search
	// postProc form
	prepareSubmitForms();
	
	// set target
	var obj=$("searchForm");
	if (obj) {
		obj.action=getFormAction()+"?"+getSelfRef(["~script~","style","table","order_by","cached_query","no_cache"]);
	}
}

var searchActive;
function activateSearch(doActivate) {
	if (!parent || !parent.searchBottom) {
		return;
	}
	// framegröße
	var frameRows;
	if (doActivate) {
		frameRows="82,*";
		className="tab_selected";
		// for list
		setObjClass("activeView","tab_light");
		// for edit
		setObjClass("view_"+currentView,"tab_light");
	}
	else {
		frameRows="*,0";
		className="tab_light";
	}
	var framesetLJ=top.$("lj");
	if (framesetLJ) {
		// frame-Größe ändern
		framesetLJ.rows=frameRows;
		// Button auf ausgewählt/nicht ausgewählt setzen
		setObjClass("view_search",className);
	}
	// ggf URL laden
	if (doActivate && !parent.searchBottom.formulare) {
		parent.searchBottom.location.href="searchRxn.php?table=reaction&"+getSelfRef(["~script~","table"]);
	}
	searchActive=doActivate;
}

var cache_id,resultCount,prev_cache_id;
function setRefCacheId(this_cache_id,this_resultCount,this_prev_cache_id) {
	cache_id=this_cache_id;
	resultCount=this_resultCount;
	prev_cache_id=this_prev_cache_id;
}

function getSidenavValue(int_name) {
	if (!parent.sidenav) {
		return;
	}
	if (parent.sidenav.getInputValue) {
		return parent.sidenav.getInputValue(int_name);
	}
}

function setSidenavValue(int_name,thisValue) {
	// check if there is
	if (!parent.sidenav) {
		return;
	}
	if (parent.sidenav.setInputValue) {
		parent.sidenav.setInputValue(int_name,thisValue);
	}
	
	// RXN search
	if (!parent.searchBottom || !parent.searchBottom.setInputValue) {
		return;
	}
	parent.searchBottom.setInputValue(int_name,thisValue);
}

function setSideNavRadio(name,value) {
	// check if there is
	if (!parent.sidenav) {
		return;
	}
	if (parent.sidenav.selectRadioButton) {
		parent.sidenav.selectRadioButton(name,value);
	}
}

function transferPkToUID(table,db_id,pk) {
	// get target frame
	if (!top.opener) {
		return;
	}
	if (is_function_or_object(top.opener.acceptPkSelection)) { // IE Bug, the usual sh*t
		// do transfer, further handling is done in target frame
		top.opener.acceptPkSelection(transferParameters,table,db_id,pk);
		top.close();
	}
}

function getTooltipP(text) {
	return " alt=\""+text+"\" title=\""+text+"\"";
}

function getTooltip(code,doAddslashes) {
	var text=getTooltipP(s(code));
	if (doAddslashes) {
		text=addslashes(text);
	}
	return text;
}

function s(code) {
	return a(localizedString,code);
}

// Javascript Misc Functions, also common safety stuff

function asyncRequest(url,nvps) { // get data for molfile
	// one request for all (drastically reduces overhead)
	// form name="update_queue" id="update_queue" target="comm" method="post" somewhere on page
	var formHTML="",formObj=$("update_queue");
	if (!formObj || (nvps.length%2)!=0) {
		return;
	}
	formObj.action=url;
	for (var b=0,max=nvps.length;b<max;b+=2) {
		formHTML+="<input type=\"hidden\" id=\"asyncReq"+b+"\" name=\""+nvps[b]+"\">"; // value=\""+nvps[a+1]+"\">";
	}
	// formObj.target="_new";
	formObj.innerHTML=formHTML;
	for (var b=0,max=nvps.length;b<max;b+=2) {
		$("asyncReq"+b).value=nvps[b+1];
	}
	formObj.submit();
}

function selfPrint() {
	self.focus();
	self.print();
}

function preloadImg(url) {
	if (url=="" || url ==undefined) {
		return;
	}
	var img=new Image();
	img.src=url;
	return img;
}

function Numsort(a, b) {
	return parseFloat(a)-parseFloat(b);
}

function keepParams(paramsArray) {
	var retval_arr=[],key,val;
	paramsArray=defArr(paramsArray);
	for (var b=0,max=paramsArray.length;b<max;b++) {
		key=paramsArray[b];
		val=a(self_ref,key);
		if (val!=="") {
			retval_arr.push(key+"="+val);
		}
	}
	return retval_arr.join("&");
}

function getSelfRef(suppress) {
	var retval="",retval_arr=[];
	suppress=defArr(suppress);
	if (!in_array("~script~",suppress)) {
		//~ retval+=self_ref["~script~"]+"?";
		retval+=location.pathname+"?";
	}
	for (var key in self_ref) {
		if (in_array(key,suppress)) { // key=="~script~" || 
			continue;
		}
		retval_arr.push(key+"="+self_ref[key]);
	}
	return retval+retval_arr.join("&");
}

// for reaction, edit and list

function gotoNewReaction(db_id,pk,lab_journal_id) {
	if (table!="reaction") {
		return;
	}
	var url="edit.php?style=lj&table=reaction&db_id="+db_id+"&pk="+pk+"&edit=true&dbs=-1&query=<0>&crit0=reaction.lab_journal_id&op0=eq&val0="+lab_journal_id;
	self.location.href=url;
}
