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

// pk_select functions
var pk_select_update_queue=[];

function PkSelectUpdateNext() {
	var int_name=pk_select_update_queue.shift();
	if (int_name) {
		PkSelectUpdate(int_name);
	}
}

function PkSelectSetData(int_name,set_int_names) {
	// setzt weitere Felder anhand der Daten zur ausgewählten Option
	var idx=$(int_name).selectedIndex,values=[];
	for (var b=0,max=set_int_names.length;b<max;b++) {
		var this_int_name=set_int_names[b];
		values[this_int_name]=a(controlData,int_name,"data",idx,this_int_name);
	}
	setControlValues(values);
}

function PkSelectGetRefreshURL(int_name) {
	return "chooseAsync.php?desired_action=loadDataForPkSelect&int_name="+int_name;
}

function PkSelectUpdate(int_name) {
	// aktualisiert ein dynamisch gefiltertes <select
	if (!a(controls,int_name,"dynamic")) {
		return;
	}
	// requires comm-frame
	var filter=controls[int_name]["getFilter"](int_name),filterText;
	if (filter===false) { // allow cancel
		return;
	}
	else if (is_object(filter)) {
		var order_by=filter["order_by"];
		if (!order_by) {
			order_by=a(controls,int_name,"order_by");
		}
		filterText="dbs="+filter["dbs"]+"&order_by="+order_by+"&"+filter["filter"];
	}
	else {
		filterText="dbs="+a(controls,int_name,"dbs")+"&order_by="+a(controls,int_name,"order_by")+"&"+filter;
		//~ alert(filterText+"A2"+int_name);
	}
	//~ alert(filterText+"A"+int_name+PkSelectUpdate.caller);
	var url=PkSelectGetRefreshURL(int_name)+"&filterDisabled="+a(controls,int_name,"filterDisabled")+"&table="+a(controls,int_name,"table")+"&flags="+a(controls,int_name,"flags")+"&"+filterText;
	setFrameURL("comm",url);
}

function trackDynValue(int_name) {
	as("controlData",getInputValue(int_name),int_name,"value");
}

function PkSelectUpdated(int_name) {
	// setzt Texte und Werte eines dynamisch gefilterten <select
	if (!a(controls,int_name,"dynamic")) {
		return;
	}
	//~ alert("C"+int_name);
	// get int_names and texts
	var selectData=a(controlData,int_name,"data"),pkName=a(controls,int_name,"pkName"),text,maxTextLen=a(controls,int_name,"maxTextLen");
	controls[int_name]["texts"]=[];
	controls[int_name]["tooltips"]=[];
	controls[int_name]["int_names"]=[];
	// allowAuto
	if (a(controls,int_name,"allowAuto")) {
		controls[int_name]["int_names"].push("-1");
		text=a(controls,int_name,"autoText");
		controls[int_name]["texts"].push(text);
		controls[int_name]["tooltips"].push(text);
	}
	// allowNone
	if (a(controls,int_name,"allowNone")) {
		controls[int_name]["int_names"].push("");
		text=a(controls,int_name,"noneText");
		controls[int_name]["texts"].push(text);
		controls[int_name]["tooltips"].push(text);
	}
	// update control array
	for (var b=0,max=selectData.length;b<max;b++) {
		controls[int_name]["int_names"].push( selectData[b][pkName] );
		text=controls[int_name]["getText"](int_name, selectData[b]);
		controls[int_name]["tooltips"].push(text);
		if (maxTextLen) {
			text=strcut(text,maxTextLen);
		}
		controls[int_name]["texts"].push(text);
	}
	// beforePkSelectUpdate
	if (is_function(controls[int_name]["beforePkSelectUpdate"])) {
		controls[int_name]["beforePkSelectUpdate"](int_name); // the rest is already in controls
	}
	// write list
	PkSelectSetOptions(int_name);
	PkSelectCallUpdate(int_name);
	updateControl(int_name);
	PkSelectUpdateNext();
}

function PkSelectCallUpdate(int_name) {
	// ruft die Update-Funktion eines dynamisch gefilterten <select auf, ohne direkt valuesChanged auszulösen
	if (a(controls,int_name,"dynamic") && is_function(controls[int_name]["updateFunction"])) {
		// get list if there is no controlData for current dataset
		controls[int_name]["updateFunction"](int_name);
	}
}

function PkSelectSetOptions(int_name) {
	// save old value
	//~ var oldVal=getControlValue(int_name);
	// liste leeren
	var obj=$(int_name),oldVal,oldValFound;
	if (!obj) {
		return;
	}
	oldVal=ar(controlData,int_name,"value"); // gives undefined if undefined
	if (typeof oldVal!="string") {
		oldValFound=true;
	}
	clearChildElementsForObj(obj);
	// write list
	var texts=a(controls,int_name,"texts"),tooltips=a(controls,int_name,"tooltips"),int_names=a(controls,int_name,"int_names"),selected=false;
	for (var b=0,max=int_names.length;b<max;b++) {
		selected=false;
		if (!oldValFound && oldVal==int_names[b]) {
			selected=true;
			oldValFound=true;
		}
		selAddOption(obj,int_names[b],texts[b],selected,tooltips[b]);
	}
	trackDynValue(int_name);
}

// pk functions

function togglePkSearch(int_name,searchVisible) {
	var obj=$("edit_"+int_name);
	if (!obj) {
		return;
	}
	if (searchVisible==undefined) {
		searchVisible=!a(controls,int_name,"searchVisible");
	}
	as("controls",searchVisible,int_name,"searchVisible");
	obj.style.display=searchVisible?"block":"none";
	// set focus
	if (searchVisible) {
		var obj=$("srcInput_"+int_name);
		if (obj) {
			obj.focus();
		}
	}
}

function keyUpPk(e,int_name) {
	var key;
	if(window.event) {
		key=window.event.keyCode;
	}
	else {
		key=e.which;
	}
	if (key==13) {
		searchPk(int_name);
		return false;
	}
	return true;
}

function searchPk(int_name) {
	var obj=$("srcInput_"+int_name);
	if (!obj) {
		return;
	}
	var searchText=obj.value;
	if (is_function(controls[int_name]["prepareSearch"])) {
		var searchText=controls[int_name]["prepareSearch"](searchText);
		if (a(controls,int_name,"showModifiedSearchText")) {
			obj.value=searchText;
		}
	}
	var url="chooseAsync.php?desired_action=searchPk&int_name="+int_name+"&dbs="+a(controls,int_name,"dbs")+"&table="+a(controls,int_name,"table")+"&order_by="+a(controls,int_name,"order_by")+"&search="+searchText;
	if (a(controls,int_name,"forMerge")) {
		url+="&pk_exclude="+a_pk;
	}
	as("controls","",int_name,"cached_query");
	as("controls","",int_name,"results");
	// window.open(url);
	setFrameURL("comm",url);
}

function gotoPagePk(int_name,page) {
	var url="chooseAsync.php?desired_action=searchPk&int_name="+int_name+"&table="+a(controls,int_name,"table")+"&cached_query="+a(controls,int_name,"cached_query")+"&page="+parseInt(page);
	// window.open(url);
	setFrameURL("comm",url);
}

function setNonePk(int_name) {
	choosePk(int_name,-1);
}

function getChoosePk(int_name,text,index) {
	return "<a href=\"javascript:choosePk(&quot;"+int_name+"&quot;,"+index+")\">"+fixNbsp(text)+"</a>";
}

function choosePk(int_name,index) {
	if (index==-1) { // none chosen
		setControl(int_name,{int_name:""},true);
	}
	else {
		// values from result
		var pkName=a(controls,int_name,"pkName"),selected_values=a(controls,int_name,"results",index);
		if (a(controls,int_name,"forMerge")) {
			var nameField=a(controls,int_name,"nameField");
			merge(selected_values[pkName],selected_values[nameField]);
		}
		else {
			// delete selected_values["db_id"];
			// init heißt: der Benutzer hat einen neuen Wert angeklickt, die Werte müssen reingeladen werden. Sonst kommen die Werte von setControlValues und zweimal laden ist pure zeitverschwendung
			setControl(int_name,selected_values,true); // die einzige Stellen, wo true stehen darf!!!!
		}
	}
	togglePkSearch(int_name,false);
	valChanged();
}

function showPkResults(int_name,page,per_page,totalCount,cached_query,results) {
	var obj=$("srcResults_"+int_name),resHTML="<table class=\"listtable\"><thead><tr><td>",totalpages=Math.ceil(totalCount/per_page);
	if (!obj) {
		return;
	}
	as("controls",cached_query,int_name,"cached_query");
	as("controls",results,int_name,"results");
	results=a(controls,int_name,"results");
	if (results.length>0) {
		// thead
		switch (a(controls,int_name,"table")) {
		case "institution":
			resHTML+=s("institution_name");
		break;
		case "literature":
			resHTML+=s("literature_citation");
		break;
		case "molecule":
			resHTML+="&nbsp;</td><td>"+s("molecule_name")+"</td><td>"+s("emp_formula")+"</td><td>"+s("cas_nr")+"</td><td>"+s("db_name");
		break;
		case "project":
			resHTML+=s("project_name");
		break;
		case "reaction":
			resHTML+=s("reaction")+"</td><td>"+s("reaction_carried_out_by");
		break;
		case "sci_journal":
			resHTML+=s("sci_journal_name")+"</td><td>"+s("sci_journal_abbrev");
		break;
		case "storage":
			resHTML+=s("storage_name")+"</td><td>"+s("institution");
		break;
		}
		// tbody
		resHTML+="</td></tr></thead><tbody>";
		for (var b=0,max=results.length;b<max;b++) {
			resHTML+="<tr><td>";
			switch (a(controls,int_name,"table")) {
			case "institution":
				resHTML+=getChoosePk(int_name,results[b]["institution_name"],b);
			break;
			case "literature":
				resHTML+=getChoosePk(int_name,getCitation(results[b]),b);
			break;
			case "molecule":
				resHTML+=getChoosePk(int_name,"<img src=\"getGif.php?db_id="+results[b]["db_id"]+"&molecule_id="+results[b]["molecule_id"]+"\" border=\"0\">",b)+"</td><td>"+getChoosePk(int_name,results[b]["molecule_name"],b)+"</td><td>"+fixNbsp(results[b]["emp_formula"])+"</td><td>"+fixNbsp(results[b]["cas_nr"])+"</td><td>"+fixNbsp(results[b]["show_db_beauty_name"]);
			break;
			case "project":
				resHTML+=getChoosePk(int_name,results[b]["project_name"],b);
			break;
			case "reaction":
				resHTML+=getChoosePk(int_name,results[b]["lab_journal_code"]+" "+results[b]["nr_in_lab_journal"],b)+"</td><td>"+fixNbsp(results[b]["reaction_carried_out_by"]);
			break;
			case "sci_journal":
				resHTML+=getChoosePk(int_name,results[b]["sci_journal_name"],b)+"</td><td>"+fixNbsp(results[b]["sci_journal_abbrev"]);
			break;
			case "storage":
				resHTML+=getChoosePk(int_name,results[b]["storage_name"],b)+"</td><td>"+fixNbsp(defBlank(results[b]["institution_name"]));
			break;
			}
			resHTML+="</td></tr>";
		}
		resHTML+="</tbody></table>";
		// nav
		if (totalpages>1) {
			if (page==0) {
				resHTML+="<img src=\"lib/prev_deac.png\" width=\"16\" height=\"18\" border=\"0\" class=\"noprint\">";
			}
			else {
				resHTML+="<a href=\"javascript:gotoPagePk(&quot;"+int_name+"&quot;,"+(page-1)+")\" class=\"noprint\"><img src=\"lib/prev.png\" width=\"16\" height=\"18\" border=\"0\"></a>";
			}
			resHTML+="<select size=\"1\" onChange=\"gotoPagePk(&quot;"+int_name+"&quot;,this.value)\" class=\"noprint\">";
			for (var b=0;b<totalpages;b++) {
				resHTML+="<option value=\""+b+"\""+(b==page?" selected=\"selected\"":"")+">"+(b+1);
			}
			resHTML+="</select>";
			if (page==totalpages-1) {
				resHTML+="<img src=\"lib/next_deac.png\" width=\"16\" height=\"18\" border=\"0\" class=\"noprint\">";
			}
			else {
				resHTML+="<a href=\"javascript:gotoPagePk(&quot;"+int_name+"&quot;,"+(page+1)+")\" class=\"noprint\"><img src=\"lib/next.png\" width=\"16\" height=\"18\" border=\"0\"></a>";
			}
			resHTML+=" ";
			if (totalCount==1) {
				resHTML+=s("total1_sing")+totalCount+s("total2_sing");
			}
			else {
				resHTML+=s("total1")+totalCount+s("total2");
			}
		}
	}
	else {
		resHTML=s("no_results");
	}
	obj.innerHTML=resHTML;
}
