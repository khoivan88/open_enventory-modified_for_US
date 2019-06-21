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

// Anfang Funktionen für molecule_control

moleculeUpdateQueue=[];

var cont_update_flags=256+512+1024+2048; // flags that require continuous update
function showImageOverlaySelect(e,obj,paramHash) { // pkName,db_id,db_name,pk,w,h,mode,linkTable,linkParams,filename
	var link_url;
	if (paramHash["noLink"]) {
		link_url="javascript: void(0)";
	}
	else if (paramHash["selectButton"]) {
		link_url="javascript:transferPkToUID("+fixQuot(paramHash["linkTable"])+","+fixQuot(paramHash["db_id"])+","+fixQuot(paramHash["linkPk"])+")";
	}
	else {
		link_url="edit.php?query=&table="+paramHash["linkTable"]+"&db_id="+paramHash["db_id"]+"&pk="+paramHash["linkPk"]+"&"+paramHash["linkParams"];
	}
	
	var params=getImgParams(paramHash["pkName"],paramHash["db_id"],paramHash["pk"],paramHash["filename"]);
	var commonAttrib="";
	if (!paramHash["noOverlay"] && (paramHash["posFlags"] & cont_update_flags)) {
		commonAttrib=" onMousemove=\"alignOverlay(event,"+paramHash["posFlags"]+");\"";
	}
	
	var iHTML=getButtonsHTML(params,defTrue(paramHash["showGifButton"]),defTrue(paramHash["showMolfileButton"]),paramHash["mode"],paramHash["mode"],undefined,undefined,commonAttrib);
	iHTML+="<a class=\"structureOverlay\" href="+fixStr(link_url)+commonAttrib+" style=\"min-width:"+paramHash["width"]+"px;min-height:"+paramHash["height"]+"px\""+">";
	
	if (paramHash["noOverlay"]) {
		iHTML+=getImgForSrc(getImgURL(params),paramHash["useSvg"],link_url,"",paramHash["width"],paramHash["height"]);
	}
	else {
		iHTML+=getImgForSrc(getImgURL(params),paramHash["useSvg"],link_url);
	}
	iHTML+="</a>";
	
	if (!paramHash["noOverlay"] && (paramHash["posFlags"] & cont_update_flags)) {
		prepareOverlay(obj,iHTML);
		alignOverlay(e,paramHash["posFlags"]);
	}
	else {
		showOverlay(obj,iHTML,0,0,paramHash["posFlags"]);
	}
}

function imgQuickEdit(e) {
	if (selectActive) {
		transferThisPkToUID();
	}
	else if (readOnly && is_function(startEditMode)) { // not readOnly: molecule in chemical form
		startEditMode();
	}
	hideOverlay(0);
}

function showStructureTooltip(e,obj,int_name,UID,field,group) {
	if (!obj) {
		return;
	}
	var thisReadOnly=a(controls,int_name,READONLY),SIL,pasteURL,paramHash,commonAttrib="",link_url,delLink;
	if (UID && field) { // list
		paramHash=SILgetField(int_name,field,group);
		var JSitemParams=SILgetJSitemParams(int_name,UID,field,group),params=SILimgGetParams(int_name,UID,field,group);
	}
	else { // single element
		paramHash=a(controls,int_name);
		var JSitemParams=fixQuot(int_name),params=imgGetParams(int_name),UID=a(paramHash,"UID");
	}
	var w=a(paramHash,"width"),h=a(paramHash,"height"),mode=a(paramHash,"mode"),copyBtn=a(paramHash,"showCopyPasteButton"),searchBtn;
	
	if (thisReadOnly) {
		link_url="javascript:void imgQuickEdit();";
	}
	else {
		link_url="javascript:void startEdit("+JSitemParams+");";
		if (copyBtn) {
			pasteURL=getPasteURL(int_name,UID,field,group,w,h,mode);
		}
		if (paramHash["showDelButton"]) {
			delLink="javascript:delStruct("+JSitemParams+")";
		}
	}
	
	if (copyBtn) {
		copyBtn=mode;
		searchBtn=mode;
	}
	if (!paramHash["noOverlay"] && (paramHash["posFlags"] & cont_update_flags)) {
		commonAttrib+=" onMousemove=\"alignOverlay(event,"+paramHash["posFlags"]+");\"";
	}
	var commonParams=" border=\"0\"",iHTML=getButtonsHTML(params,paramHash["showGifButton"],paramHash["showMolfileButton"],copyBtn,searchBtn,delLink,pasteURL,commonAttrib)+"<a";
	iHTML+=" onMouseout=\"hideOverlay();\" class=\"structureOverlay\" href="+fixStr(link_url)+commonAttrib+" style=\"min-width:"+Math.max(w,60)+"px;min-height:"+h+"px\">";
	
	if (paramHash["noOverlay"]) {
		iHTML+=getImgForSrc(getImgURL(params),paramHash["useSvg"],link_url,commonParams,w,h);
	}
	else {
		iHTML+=getImgForSrc(getImgURL(params),paramHash["useSvg"],link_url,commonParams);
	}
	iHTML+="</a>";
	if (!paramHash["noOverlay"] && (paramHash["posFlags"] & cont_update_flags)) {
		prepareOverlay(obj,iHTML);
		alignOverlay(e,paramHash["posFlags"]);
	}
	else {
		showOverlay(obj,iHTML,0,0,paramHash["posFlags"]);
	}
}

function startEdit(int_name,UID,field,group) {
	var SIL;
	if (UID && field) {
		SIL=true;
		var fieldIndex=SILgetFieldIndex(int_name,field,group);
		var paramHash=a(controls,int_name,"fields",fieldIndex);
	}
	else {
		var paramHash=a(controls,int_name);
		UID=a(controls,int_name,"UID");
		field="";
	}
	// get screen res and take 90% for editWin
	var width=1024,height=768;
	if (screen.availWidth) {
		width=screen.availWidth*0.9;
	}
	if (screen.availHeight) {
		height=screen.availHeight*0.9;
	}
	var url="editWin.php?mode="+paramHash["mode"]+"&force="+defBlank(paramHash["force"])+"&int_name="+int_name+"&UID="+UID+"&field="+field+"&group="+defBlank(group)+"&width="+paramHash["width"]+"&height="+paramHash["height"]+"&desired_action="+defBlank(paramHash["desired_action"]);
	if (paramHash["autoUpdate"]) { // do not trigger update manually
		url+="&autoUpdate="+paramHash["autoUpdate"];
	}
	window.open(url,Number(new Date()),"left=0,top=0,width="+width+",height="+height+",location=no,menubar=no,status=no,toolbar=no");
}

function getImgURL(params,save) {
	var retval="getGif.php?"+params;
	if (save) {
		retval+="&save=true";
	}
	return retval;
}

function getMolfileURL(params) {
	var retval="getMolfile.php?"+params;
	return retval;
}

function getCopyURL(params,mode) {
	var retval="clipAsync.php?"+params+"&desired_action=copyFromDb";
	if (mode) {
		retval+="&mode="+mode;
	}
	return retval;
}

function getSearchURL(params,role) {
	var retval="clipAsync.php?"+params+"&desired_action=searchReaction&role="+role;
	return retval;
}

function getPasteURL(int_name,UID,field,group,width,height,mode) {
	var retval="clipAsync.php?desired_action=pasteUID&int_name="+int_name+"&UID="+UID+"&width="+width+"&height="+height+"&field="+defBlank(field)+"&group="+defBlank(group);
	if (mode) {
		retval+="&mode="+mode;
	}
	return retval;
}

function getButtonsHTML(params,gifButton,molfileButton,copyButton,searchButton,delLink,pasteURL,commonAttribs) {
	var retval="<div class=\"structureButtons\"><nobr>",commonAttribs=defBlank(commonAttribs);
	/* if (editLink) {
		retval+="<a href="+fixStr(editLink)+"><img border=\"0\" src=\"lib/details_sm.png\""+getTooltip("edit_structure")+"></a>";
	}*/
	if (delLink) {
		retval+="<a href="+fixStr(delLink)+commonAttribs+"><img border=\"0\" src=\"lib/del_sm.png\""+getTooltip("delete")+"></a>";
	}
	if (gifButton!=false) {
		var url=getImgURL(params,true);
		retval+="<a href="+fixStr(url+"&format=gif")+commonAttribs+"><img border=\"0\" src=\"lib/giffile_sm.png\""+getTooltip("save_gif")+"></a>";
		retval+="<a href="+fixStr(url+"&format=svg")+commonAttribs+"><img border=\"0\" src=\"lib/svgfile_sm.png\""+getTooltip("save_svg")+"></a>";
	}
	if (molfileButton!=false) {
		retval+="<a href="+fixStr(getMolfileURL(params))+commonAttribs+"><img border=\"0\" src=\"lib/molfile_sm.png\""+getTooltip("save_molfile")+"></a>";
	}
	if (copyButton!=false) {
		retval+="<a href="+fixStr(getCopyURL(params,copyButton))+commonAttribs+" target=\"comm\"><img border=\"0\" src=\"lib/copy_sm.png\""+getTooltip("copy_structure")+"></a>";
	}
	if (pasteURL) {
		retval+="<a href="+fixStr(pasteURL)+commonAttribs+" target=\"comm\"><img border=\"0\" src=\"lib/paste_sm.png\""+getTooltip("paste_structure")+"></a>";
	}
	if (searchButton=="mol") {
		retval+="<a href="+fixStr(getSearchURL(params,"1,2"))+commonAttribs+" target=\"comm\"><img border=\"0\" src=\"lib/this_as_educt_sm.png\""+getTooltip("search_as_educt")+"></a><a href="+fixStr(getSearchURL(params,"6"))+commonAttribs+" target=\"comm\"><img border=\"0\" src=\"lib/this_as_prod_sm.png\""+getTooltip("search_as_product")+"></a>";
	}
	retval+="</nobr></div>";
	return retval;
}

function getImgParams(pkName,db_id,pk,filename,timestamp) {
	var retval="db_id="+db_id+"&"+pkName+"="+pk+"&filename="+filename;
	if (timestamp) {
		retval+="&no_cache="+timestamp;
	}
	if (archive_entity) {
		retval+="&archive_entity="+archive_entity;
	}
	return retval;
}

function imgGetParams(int_name) {
	return a(controls,int_name,"params");
}

function imgSetParams(int_name,params) {
	as("controls",params,int_name,"params");
}

function updateImg(int_name) {
	var params=imgGetParams(int_name);
	setImgSrc(int_name+"_img",getImgURL(params),a(controls,int_name,"useSvg"));
}

function addMoleculeToUpdateQueue(int_name,UID,field,group,desired_action) {
	for (var b=0;b<moleculeUpdateQueue.length;b++) { // check for double
		if (moleculeUpdateQueue[b][0]==int_name && moleculeUpdateQueue[b][1]==UID && moleculeUpdateQueue[b][2]==field && moleculeUpdateQueue[b][3]==group) {
			return;
		}
	}
	// alert(addMoleculeToUpdateQueue.caller);
	// alert(int_name+" "+UID+" "+field);
	moleculeUpdateQueue.push([int_name,UID,defBlank(field),defBlank(group),defBlank(desired_action)]);
	// alert(moleculeUpdateQueue.length);
}

function MEgetMolfileInput(int_name,UID,field,group) {
	var SIL;
	if (UID && field) {
		SIL=true;
		return SILgetObj(int_name,UID,field,group);
	}
	else {
		return $(int_name);
	}
}

function MEgetMolfile(int_name,UID,field,group) {
	var obj=MEgetMolfileInput(int_name,UID,field,group);
	if (obj) {
		return obj.value;
	}
}

function updateMolecules(invocationCommand) { // array of int_names as parameter
	if (moleculeUpdateQueue.length==0) {
		return;
	}
	var int_name,UID,field,desired_action,SIL;
	if (!invocationCommand) {
		invocationCommand="";
	}
	// POST-Parameter aufbauen
	var params=[];
	for (var b=0,max=moleculeUpdateQueue.length;b<max;b++) { // geht die int_names durch, deren Molekül upgedatet werden muß
		int_name=moleculeUpdateQueue[b][0]; // list_int_name
		UID=moleculeUpdateQueue[b][1];
		field=moleculeUpdateQueue[b][2]; // also known as int_name
		group=moleculeUpdateQueue[b][3]; // also known as int_name
		desired_action=defBlank(moleculeUpdateQueue[b][4]);
		SIL=false;
		
		if (UID && field) { // in subitemlist
			SIL=true;
		}
		if (SIL) {
			var fieldIdx=SILgetFieldIndex(int_name,field,group);
			var moleculeData=a(controls,int_name,"fields",fieldIdx);
		}
		else {
			var moleculeData=a(controls,int_name);
			UID=a(controls,int_name,"UID");
		}
		/*
		var pkField=moleculeData["pkField"],pk="";
		if (pkField) {
			if (SIL) { // in subitemlist
				pk=SILgetValue(int_name,UID,pkField,group);
			}
			else {
				pk=getControlValue(pkField);
				field="";
			}
		}*/
		var pkName=moleculeData["pkName"],pk="";
		if (pkName) {
			if (SIL) { // in subitemlist
				pk=SILgetValue(int_name,UID,pkName,group);
			}
			else {
				pk=getControlValue(pkName);
				field="";
			}
		}		
		var thisReadOnly=moleculeData[READONLY],timestamp=getCacheValue("timestamp");
		if (a_db_id!="" && pk!="" && thisReadOnly) { // Bild auf der DB holen
			// performMoleculeImgDB("display",a_db_id,pk,timestamp,int_name,UID,field);
		}
		else { // generate using chooseAsync
			// int_name,molfile,width,height,desired_action, mode
			var molfile=MEgetMolfile(int_name,UID,field,group);
			params.push("molecule_UID[]",UID);
			params.push("int_name_"+UID,int_name);
			params.push("field_"+UID,field);
			params.push("group_"+UID,group);
			params.push("molfile_"+UID,molfile);
			params.push("width_"+UID,moleculeData["width"]);
			params.push("height_"+UID,moleculeData["height"]);
			params.push("desired_action_"+UID,desired_action);
			params.push("mode_"+UID,moleculeData["mode"]);
		}
	}
	if (params.length>0) {
		if (typeof table!="undefined") {
			params.push("table",table);
		}
		//~ alert(params);
		params.push("invocationCommand",invocationCommand);
		asyncRequest("chooseAsync.php",params);
		moleculeUpdateQueue.length=0;
	}
}

function moleculeUpdated(img_UID,int_name,UID,field,group) { // wird von chooseAsync.php aufgerufen und setzt NUR die neue Grafik. Alle weiteren Daten werden an executeForms gesendet
	if (UID && field) {
		var params="timestamp="+img_UID;
		SILimgSetParams(int_name,UID,field,group,params);
		var gif=getImgURL(params),nameRw=SILgetObjName(int_name,UID,field,group),nameRo=SILgetObjName(int_name,UID,field,group,true);
		
		setImgSrc(nameRw+"_img",gif);
		setImgSrc(nameRo+"_img",gif);
	}
	else {
		var params="timestamp="+img_UID;
		imgSetParams(int_name,params);
		updateImg(int_name);
	}
}

function delStruct(int_name,UID,field,group) {
	var SIL;
	if (UID && field) {
		SIL=true;
		var molfile_obj=SILgetObj(int_name,UID,field,group);
	}
	else {
		var molfile_obj=$(int_name);
		UID=a(controls,int_name,"UID");
	}
	if (molfile_obj) {
		molfile_obj.value="";
		addMoleculeToUpdateQueue(int_name,UID,field,group);
		updateMolecules();
		if (is_function(molfile_obj.onchange)) {
			molfile_obj.onchange.call();
		}
	}
}
