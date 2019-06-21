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

// client cache functions

var asyncGotoDataset,asyncInProgress,a_db_id,a_pk,updateInProgress,refUpdateInProgress,actIdx,detail_cache_range,fast_cache_range,min_reload,force_distance,fastmodeWait,fastmodeInt,maxDatasets;
var loadQueueLength=0,datasetCount=0; // loadQueue is array of lists
dbIdx=[];
isThere=[];
loadQueue=[];
dataCache=[];

function cacheDataset(db_id,pk,JSONstring) {
	//~ alert(db_id+"X"+pk);
	// write data into cache
	var timestamp=Number(new Date()),pkName;
	as("dataCache",JSONstring,db_id,pk);
	as("dataCache",timestamp,db_id,pk,"timestamp"); // for image reload onChange
	// preload images for standard controls
	for (var int_name in controls) {
		switch(getControlElementType(int_name)) {
		case "structure":
			var pkName=a(controls,int_name,"pkName");
			preloadImg(getImgURL(getImgParams(pkName,db_id,pk,"",timestamp)));
		break;
		case "subitemlist":
			if (!is_array(dataCache[db_id][pk][int_name])) {
				continue;
			}
			// search for img cols
			for (var field=0,max=controls[int_name]["fields"].length;field<max;field++) {
				switch(controls[int_name]["fields"][field]["item"]) {
				case "structure": // for rxn
					var fieldName=a(controls,int_name,"fields",field,"int_name");
					// Ergebnis-Datensätze durchgehen
					pkName=a(controls,int_name,"fields",field,"pkName");
					for (var b=0,max2=dataCache[db_id][pk][int_name].length;b<max2;b++) {
						preloadImg(getImgURL( getImgParams(pkName,db_id,dataCache[db_id][pk][int_name][b][pkName],"",timestamp) ));
					}
				break;
				}
			}
		break;
		}
	}
	// call custom functions 
	//~ executeForms("loadDatasetIntoCache",dataCache[db_id][pk]);
	as("isThere",true,db_id,pk); // Datensatz da
	datasetCount++; // what if it was there already before??
	
	if (db_id==a_db_id && pk==a_pk && !archive_entity) { // reload current
		gotoDataset();
	}
}

function deleteDatasetFromCache(idx) { // dauerhaft löschen
	// löschen
	var db_id=dbIdx[idx]["db_id"],pk=dbIdx[idx]["pk"];
	dbIdx.splice(idx,1);
	dataCache[db_id][pk]=undefined;
	// wenn gelöschter Datensatz der aktuelle, nächsten Datensatz auswählen
	if (idx==actIdx) {
		gotoDataset(idx);
	}
	updateTotalCount();
}

function uncacheDataset(db_id,pk) { // free some memory
	if (!a(isThere,db_id,pk)) {
		return;
	}
	delete dataCache[db_id][pk];
	as("isThere",false,db_id,pk);
	datasetCount--;
}

function gcCache() { // unused, printing tec must be changed
	// if datasetCount is above max:
	if (datasetCount<=maxDatasets) {
		return;
	}
	// go through dbIdx and uncache all out of fast_cache_range
	for (var b=0,max=dbIdx.length;b<max;b++) {
		if (b<actIdx && b>actIdx-fast_cache_range) {
			b+=2*fast_cache_range;
		}
		else {
			uncacheDataset(dbIdx[b]["db_id"],dbIdx[b]["pk"]);
		}
	}
}

function getCacheValue(int_name) {
	return a(dataCache,a_db_id,a_pk,int_name);
}

function showDataset(idx,init) { // init: erstmaliges Laden eines Datensatzes beim Aufruf
	//~ alert(init+"F"+showDataset.caller);
	// get datasets to load
	if (!dbIdx[idx]) {
		return;
	}
	var db_id=dbIdx[idx]["db_id"],pk=dbIdx[idx]["pk"],force;
	if (init!=true) {
		if (!fastMode || fastCount==0 || fastCount>fast_cache_range-detail_cache_range) { // check cache only ~ every 25 datasets in fastmode
			fastCount=0;
			var cache_range=(fastMode?fast_cache_range:detail_cache_range);
			var start=Math.max(0,idx-cache_range),end=Math.min(dbIdx.length-1,idx+cache_range),loadCount=0;
			for (var b=start;b<=end;b++) {
				var distance=Math.abs(idx-b),this_db_id=dbIdx[b]["db_id"],this_pk=dbIdx[b]["pk"];
				if (distance!=0 && distance<=force_distance && !a(isThere,this_db_id,this_pk)) {
					force=true;
				}
				requestDataset(b); // was idx, seems to be wrong
			}
		}
		if (fastMode) {
			fastCount++;
		}
	}

	a_db_id=db_id;
	a_pk=pk;
	// var values=a(dataCache,db_id,pk);
	thisIsThere=a(isThere,db_id,pk);
	if (thisIsThere && readOnly) { // gleich wechseln
		//~ var param=dataCache[db_id][pk]; // unkritisch
		//~ setControlValues(param,false); // pk nicht initialisieren
		resetAlreadyLoaded();
		// sort and filter lists
		activateEditView(undefined,false); // enthält setControlValues
	}
	else {
		asyncGotoDataset=idx;
	}
	if (!readOnly || !thisIsThere || (!asyncInProgress && loadQueueLength>=min_reload) || force) { // reload data
	//~ alert("ro"+readOnly+"\nthisIsThere"+thisIsThere+"\nloadQueueLength"+loadQueueLength+"\nforce"+force);
	// geänderte werte speichern, 
				// fehlenden datensatz laden, 
							// genügend fehlende datensätze
														// enger nachbar fehlt
		var wasReadOnly=readOnly;
		if (!readOnly) {
			//~ if (getChecked("keep_edit_open")) {
				//~ startEditMode();
			//~ }
			//~ else {
				endEditMode();
			//~ }
		}
		performAsync(!wasReadOnly); // speichern über POST, einfaches Nachladen über GET
	}
}

function prepareUpdate(db_id,pk,mode) { // 0: update (nur wenn da), 1: load (nur wenn nicht da), 2: force
	// ist datensatz schon in queue?
	if (a(loadQueue,db_id,pk)) {
		return;
	}
	var thisIsThere=a(isThere,db_id,pk);
	// ist Datensatz im Cache? oder Laden zwingend?
	if (mode==2 || (mode==0 && thisIsThere==true && (db_id!=a_db_id || pk!=a_pk)) || (mode==1 && thisIsThere==false)) { // yes
		loadQueueLength++;
		as("loadQueue",true,db_id,pk);
	}
}

function requestDataset(idx) {
	prepareUpdate(dbIdx[idx]["db_id"],dbIdx[idx]["pk"],1);
}

function loadAll(datasetArray) {
	if (datasetArray==undefined) { // all
		for (var b=0,max=dbIdx.length;b<max;b++) {
			requestDataset(b);
		}
	}
	else {
		for (var b=0,max=datasetArray.length;b<max;b++) {
			requestDataset( datasetArray[b] );
		}
	}
	performAsync(true,true); // könnten sehr viele Daten sein, deshalb POST
}

function replaceResults(thisDbIdx) { // dont load form again, simple the data
	dbIdx=thisDbIdx;
	dataCache.length=0;
	// Änderungen speichern
	if (!readOnly && valuesChanged && confirm(s("save_changes")+"?")) {
		// save changes
		asyncGotoDataset=0;
		requestDataset(0);
		saveChanges();
	}
	else {
		readOnlyForms(true);
		showDataset(0);
	}
}

var query_timestamp=Number(new Date());
function getAgeSeconds() {
	return (Number(new Date())-query_timestamp)/1000;
}

function getLoadData() {
	var loadData=[],haveData=false;
	for (var db_id in loadQueue) {
		db_id=parseInt(db_id);
		if (isNaN(db_id)) {
			continue;
		}
		loadData[db_id]=db_id;
		for (var pk in loadQueue[db_id]) {
			pk=parseInt(pk);
			if (isNaN(pk)) {
				continue;
			}
			loadData[db_id]+=","+pk;
		}
		haveData=true;
	}
	if (haveData) {
		return loadData;
	}
}

function getRefreshStr(loadData) {
	var retval="";
	for (var refresh_data in loadData) {
		var loadStr=loadData[refresh_data];
		if (typeof loadStr=="string") {
			retval+="&refresh_data[]="+loadStr;
		}
	}
	return retval;
}

function prepareAsync() {
	if (asyncInProgress) { // wait 200 ms and reset then
		// window.setTimeout("performAsync("+post+")",400); // 
		return "";
	}
	var loadData=getLoadData();
	if (loadData==undefined) {
		return "";
	}
	var age_seconds=getAgeSeconds();
	asyncInProgress=true;
	return "&age_seconds="+age_seconds+getRefreshStr(loadData);
}

function performAsync(post,for_print) { // post zum speichern
	//~ alert(performAsync.caller);
	//~ alert(performAsync.caller+"V"+asyncInProgress);
	if (asyncInProgress) { // wait 200 ms and reset then
		// window.setTimeout("performAsync("+post+")",400); // 
		return;
	}
	// Datensätze nachladen und ggf. geänderte werte posten
	var loadData=getLoadData();
	if (loadData==undefined && post==false) {
		return;
	}
	asyncInProgress=true;
	var age_seconds=getAgeSeconds();
	if (post) {
		//yes - POST
		var iHTML="<input type=\"hidden\" name=\"age_seconds\" value=\""+age_seconds+"\">";
		for (var refresh_data in loadData) {
			var loadStr=loadData[refresh_data];
			if (typeof loadStr=="string") {
				iHTML+="<input type=\"hidden\" name=\"refresh_data[]\" value=\""+loadStr+"\">";
			}
		}
		//~ iHTML+="<input type=\"hidden\" name=\"table\" value=\""+table+"\">";
		if (asyncGotoDataset!=undefined) { // angeforderter Datensatz muß noch geladen werden
			iHTML+="<input type=\"hidden\" name=\"goto_page\" value=\""+asyncGotoDataset+"\">";
			asyncGotoDataset=undefined;
		}
		if (for_print) {
			iHTML+="<input type=\"hidden\" name=\"for_print\" value=\"1\">";
		}
		setiHTML("additionalFields",iHTML);
		$("main").submit();
	}
	else {
		// no - GET
		//~ var url="editAsync.php?age_seconds="+age_seconds+"&"+pageParams+getRefreshStr(loadData);
		var url="editAsync.php?"+getSelfRef(["~script~","desired_action"])+"&age_seconds="+age_seconds+getRefreshStr(loadData);
		if (asyncGotoDataset!=undefined) {
			url+="&goto_page="+asyncGotoDataset;
			asyncGotoDataset=undefined;
		}
		if (for_print) {
			url+="&for_print=1";
		}
		setFrameURL("edit",url);
	}
}

function clearQueue() {
	loadQueueLength=0;
	loadQueue=[];
	query_timestamp=Number(new Date());
}

function asyncComplete() {
	asyncInProgress=false;
	// performAsync if necessary
	if (loadQueueLength>=min_reload) {
		performAsync();
	}
}