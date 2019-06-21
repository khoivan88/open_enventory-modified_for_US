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

var fs_obj=top.$("sideframe"),f_obj=top.$("sidenav");

function switchSideframe(big) {
	if (big==undefined) {
		big=true;
	}
	if (!fs_obj) {
		return;
	}
	if (!top.mainpage) {
		return;
	}
	if (top.mainpage.saveScrollPos) {
		top.mainpage.saveScrollPos();
	}
	if (big) {
		fs_obj.cols=top.fs_obj_orig;
		f_obj.scrolling="auto";
	}
	else {
		fs_obj.cols="35,*,0"; // 3rd row ignored if not there
		f_obj.scrolling="no";
	}
	if (top.mainpage.updateScrollPos) {
		top.mainpage.updateScrollPos();
	}
	visibleObj("collapse",big);
	visibleObj("expand",!big);
	visibleObj("bg_layer",!big);
}

// List handling

function updateListOp() {
	if (parent.mainpage) {
		if (parent.mainpage.table!=getInputValue("table")) { // table changed
			visibleObj("list_logic",false);
			setInputValue("prev_cache_id","");
			setInputValue("ref_cache_id","");
		}
		else {
			var hasResults=(parent.mainpage.resultCount>0),hasPrev=parent.mainpage.prev_cache_id;
			visibleObj("list_logic",(hasResults || hasPrev));
			setInputValue("prev_cache_id",defBlank(parent.mainpage.prev_cache_id));
			setInputValue("ref_cache_id",defBlank(parent.mainpage.cache_id));
			var obj=$("list_op");
			if (obj) {
				obj.options[1].style.display=(hasResults?"":"none");
				obj.options[2].style.display=(hasResults?"":"none");
				obj.options[3].style.display=(hasResults?"":"none");
				obj.options[4].style.display=(hasPrev?"":"none");
				if (!hasResults) {
					obj.value="1";
				}
			}
		}
	}	
}

function listOpChanged() {
	if (getInputValue("list_op")==5) { // go back to prev list
		if (!parent.mainpage) {
			return;
		}
		var prev_cache_id=getInputValue("prev_cache_id");
		if (!prev_cache_id) {
			return;
		}
		// goto prev results
		var url=getFormAction()+"?table="+getInputValue("table")+"&cached_query="+prev_cache_id;
		//~ alert(url);
		parent.mainpage.location.href=url;
		setInputValue("list_op",1); // reset <select
	}
}

// reaction search

function sidenavToRxn(id) {
	if (parent.searchBottom && is_function(parent.searchBottom.sidenavToRxn)) {
		parent.searchBottom.sidenavToRxn(id);
	}
}

function selectUpdated(int_name) {
	var update_int_names=dependent[int_name];
	if (!is_array(update_int_names)) {
		return;
	}
	pk_select_update_queue=pk_select_update_queue.concat(update_int_names);
	PkSelectUpdateNext();
	/*
	for (var b=0,max=update_int_names.length;b<max;b++) {
		PkSelectUpdate(update_int_names[b]);
	}
	*/
}

function search_literature() {
	setInputValue("val6",getInputValue("val5")); // copy author
	// steuerelemente durchgehen und query bauen
	setInputValue("query",getQueryString("literature_search"));
	// suchen
	setFormAction();
}

function search_reaction() {
	// steuerelemente durchgehen und query bauen
	setInputValue("query",getQueryString("reaction_search"));
	// suchen
	setFormAction();
}

function search_analytical_data() {
	// steuerelemente durchgehen und query bauen
	setInputValue("query",getQueryString("analytical_data_search"));
	// suchen
	setFormAction();
}

// end reaction search

noValueSearchModes=["on","of","du","nu","an"];

function doSearch() {
	// wo suchen
	var type=getCritType(getInputValue("crit0")),table=getInputValue("table"),ext=(table=="supplier_search");
	if (type=="structure") {
		var thisMolfile=getMolfile("JME0");
		var moldata=computeMolecule(thisMolfile,0+(ext?0:2));
		//~ if (procMolfile["MW"]==0) {
		if (moldata["chemFormula"]=="") {
			if (!ext) {
				getAll();
				return false;
			}
			alert(s("no_structure"));
			return false;	
		}
	}
	else if (sF.val0.value=="" && !in_array(getInputValue("op0"),noValueSearchModes)){
		if (!ext) {
			getAll();
			return false;
		}
		alert(s("no_search_term"));
		return false;	
	}
	if (ext) { // extSupplier
		sF.action="searchExt.php?"+getSelfRef(["~script~","table","cached_query"]);
		//~ sF.per_page.value="-1";
	}
	else {
		// sF.action="list.php";
		setFormAction();
		//~ sF.per_page.value=default_per_page;
	}
	
	if (type=="structure") {
		sF.val0a.value=getMolfile("JME0");
		//~ sF.val0.value=getSmiles("JME0");
	}
	else if (type=="emp_formula") {
		var moldata=computeMolecule(sF.val0.value,1+(ext?0:2) ); // no wildcards for ext
		sF.val0.value=moldata["chemFormula"];
		if (moldata["chemFormula"]=="") {
			if (!ext) {
				getAll();
				return false;
			}
			alert(s("no_search_term"));
			return false;	
		}
	}
	return true;
}

function allDBs() {
	selectAllOptions("dbs");
}

function gotoDetailSearch() {
	var url=getSelfRef(["table","cached_query"])+"&desired_action=detail_search&table="+getInputValue("table");
	self.location.href=url;
}

function allDBs() {
	var dbs_select=$("dbs");
	if (dbs_select) {
		for (var b=0,max=dbs_select.childNodes.length;b<max;b++) {
			dbs_select.childNodes[b].selected=true;
		}
	}
}

function openStartPage(code) {
	var url=a(startPages,code);
	if (url!="") {
		window.open(url);
	}
}

function rollButton(act_source,active) {
	$("link_"+act_source).className=(active?"imgButton buttonActive":"imgButton");
}

pos=[];
crits=[];
ops=[];
values=[];
units=[];

function getUnitSelect(element,crit,type,old_unit) {
	if (type=="money") {
		var unit_list=currency_list;
	}
	else {
		var unit_list=getUnitsList(getAllowedClasses(crit));
	}
	
	if (unit_list.length==0) {
		return "";
	}
	var retval="<select name=\"val"+element+"a\" id=\"val"+element+"a\">";
	for (var b=0,max=unit_list.length;b<max;b++) {
		retval+="<option value=\""+unit_list[b]+"\"";
		if (old_unit==unit_list[b]) {
			retval+=" selected=\"selected\"";
		}
		retval+=">"+unit_list[b];
	}
	retval+="</select>";
	return retval;
}

function handleSelectChange(element) { // JS
	var op_td=$("op_td"+element),val_td=$("val_td"+element),crit_obj=$("crit"+element),op_obj=$("op"+element),val_obj=$("val"+element),unit_td=$("unit_td"+element),op_iHTML="",val_iHTML="",unit_iHTML="";
	if (!crit_obj || !op_td || !val_td) {
		return false;
	}
	var val=crit_obj.value; // ,idx=crit_obj.selectedIndex;
	var type=getCritType(val);
	if (op_obj) {
		ops[element]=op_obj.value;
	}
	if (val_obj) {
		values[element]=val_obj.value;
	}
	//alert(val);
	op_td.innerHTML=getOpSelect(element,type);
	var old_unit=getInputValue("val"+element+"a");
	val_td.innerHTML=getValInput(element,type);
	setiHTML("unit"+element,getUnitSelect(element,val,type,old_unit));
        //~ if (crits[element]==type) {
		// set values for op and val
		$("op"+element).value=ops[element];
		if (in_array(type,["structure","rc_structure","r_structure"])) {
			window.setTimeout("document.JME"+element+".readMolFile(values["+element+"])",300);
		}
		else {
			$("val"+element).value=defBlank(values[element]);
		}
	//~ }
	crits[element]=type;
        //~ setOptions(element,val)
        return true;
}

function getCritSelect(element,onChange) {
	onChange=onChange+"("+element+"); ";
	var table=getInputValue("table");
	return "<select name=\"crit"+element+"\" id=\"crit"+element+"\" onKeyup=\""+onChange+"\" onChange=\""+onChange+"\">"+getCritOptions(table)+"</select>";
}

var firstCrit=1,next_num=firstCrit,conditions=0;
function addCrit(after_num) {
	if (after_num==undefined) {
		var position=pos.length;
	}
	else {
		var position=getPos(after_num);
	}
	// add Div to subqueries
	// <select
	// and span
	// <div id=\"subquery"+next_num+"\">
	// </div>
	var iHTML="<legend class=\"condition\">&lt;"+next_num+"&gt;</legend><table class=\"hidden\"><tr><td><a href=\"Javascript:removeCrit("+next_num+")\" class=\"imgButtonSm\"><img src=\"./lib/del_sm.png\" border=\"0\""+getTooltip("delete")+"></a></td><td><select name=\"conj"+next_num+"\" id=\"conj"+next_num+"\" onKeyup=\"autoQuery()\" onChange=\"autoQuery()\"><option value=\"AND\">"+s("AND")+"<option value=\"OR\">"+s("OR")+"<option value=\"XOR\">"+s("XOR")+"<option value=\"AND NOT\">"+s("AND")+s("NOT")+"<option value=\"OR NOT\">"+s("OR")+s("NOT")+"<option value=\"XOR NOT\">"+s("XOR")+s("NOT")+"</select></td><td><a href=\"javascript:addCrit("+next_num+")\" class=\"imgButtonSm\"><nobr><img src=\"lib/filter.png\" height=\"20\" width=\"20\" border=\"0\" align=\"absmiddle\""+getTooltip("add_condition")+">+</nobr></a></td><td></td></tr><tr><td colspan=\"3\">"+getCritSelect(next_num,"handleSelectChange")+"</td><td id=\"op_td"+next_num+"\"></td></tr><tr><td id=\"val_td"+next_num+"\" colspan=\"4\"></td></tr></table>";
	// add condition to query
	var newElement=document.createElement("fieldset");
	newElement.setAttribute("id","subquery"+next_num);
	var nextElement=$("subquery"+after_num);
	/* if (nextElement) {
		nextElement=nextElement.nextSibling;
	} */
	$("subqueries").insertBefore( newElement,nextElement );
	setiHTML("subquery"+next_num,iHTML);
	// ändern zu addElement...
		// with op and val
	handleSelectChange(next_num);
	crits[next_num]=0;
	ops[next_num]=getInputValue("op"+next_num);
	values[next_num]="";
	for (var b=pos.length;b>position;b--) {
		pos[b]=pos[b-1];
	}
	pos[position]=next_num;
	var query_insert="";
	if (conditions>0) {
		query_insert+=" AND ";
	}
	query_insert+="<"+next_num+">";
	if (!autoQuery()) {
		wrapSelection($("query"),"",query_insert);
	}
	// increase next_num
	next_num++;
	conditions++;
}

function searchExt(where) {
	if (sF.crit0.value=="molfile_blob" && where==0) {
		window.open("http://www.emolecules.com/cgi-bin/search?t=ex&q="+getSmiles("JMEinput"));
		return;
	}
	if (sF.val0) {
		var search=sF.val0.value;	
		if (search!="") {
			switch (where) {
			case 0: // emol
				window.open("http://www.emolecules.com/cgi-bin/search?q="+search);
			break;
			case 1: // chem.de
				window.open("http://www.chemie.de/search/index.php3?language=d&domain=chem&doanno=0&benchmark=0&scheme=default&pattern="+search+"&searchbutton=%24msgs%5B%40key%3D%27suchen%27%5D&selmedia%5Ball%5D=1&sellang=&selrank=auto&vkid=1");	
			break;
			}
			return;
		}
	}
	switch (where) {
	case 0: // emol
		window.open("http://www.emolecules.com");
	break;
	case 1: // chem.de
		window.open("http://www.chemie.de");	
	break;
	}
}

function changeTable(obj) {
	if (!obj) {
		return;
	}
	var newTable=obj.value;
	if (confirm(s("change_table"))) {
		location.href=getSelfRef(["table","cached_query"])+"&table="+newTable;
	}
}

function getNew() {
	var table=getInputValue("table");
	var url="edit.php?"+getSelfRef(["~script~","table","cached_query"])+"&desired_action=new&table="+table;
	setFrameURL("mainpage",url);
}

function getAll() {
	var query=sF.query.value;
	sF.query.value="";
	setFormAction();
	sF.submit();
	sF.query.value=query;
}

function searchExt(where) {
	switch (where) {
	case 0: // emol
		window.open("http://www.emolecules.com");
	break;
	case 1: // chem.de
		window.open("http://www.chemie.de");	
	break;
	}
}

function disableAuto() {
	$("auto").checked=false;
}

function autoQuery() {
	var retval="";
	if (!$("auto").checked) {
		return false;
	}
	for (var position=0,max=pos.length;position<max;position++) {
		var b=pos[position];
		var conj_obj=$("conj"+b);
		if (conj_obj) {
			if (retval=="") { // 1st condition
				if (conj_obj.selectedIndex<=2) { // pos
					var new_conj="";
				}
				else { //
					var new_conj="NOT ";
				}
			}
			else {
				var new_conj=" "+conj_obj.value+" ";
			}
			retval+=new_conj+"<"+b+">";
		}
	}
	$("query").value=retval;
	return true;
}

function charCount(haystack,needle) {
	var npos=-1,count=-1;
	if (haystack.length<1 || needle.length<1) {
		return 0;
	}
	
	do {
		npos=haystack.indexOf(needle,npos+1);
		count++;
	} while (npos>=0);
	return count;
}

function getPos(num) {
	for (var a=0;a<pos.length;a++) {
		if (pos[a]==num) {
			return a;
		}
	}
}

function removeCrit(num) {
	position=getPos(num);
	$("subqueries").removeChild($("subquery"+num));
	for (var b=position,max=pos.length-1;b<max;b++) {
		pos[b]=pos[b+1];
	}
	pos.length-=1;
	setInputValue("query",removeCritFromQuery($("query").value,num));
	autoQuery();
	crits[num]="";
	ops[num]="";
	values[num]="";
	conditions--;
}

function removeCritFromQuery(query,num) {
	var retval=query;
	var p1=new RegExp(" *(AND|OR|XOR)( *NOT)? *<"+num+">","gi"); // remove with front operators
	var p2=new RegExp("(NOT *)?<"+num+"> *(AND|OR|XOR) *","gi"); // remove with back operators
	var p3=new RegExp("(NOT *)?<"+num+">","gi"); // remove without operators
	retval=retval.replace(p1,"");
	retval=retval.replace(p2,"");
	retval=retval.replace(p3,"");
	// remove senseless (), (<12>)
	retval=retval.replace(/\(((NOT)?\\s*<\d+>)?\)/gi,'$1');
	return retval;
}

function queryKeypress(e,obj) {
	var key;
	if(window.event) {
		key=window.event.keyCode;
	}
	else {
		key=e.which;
	}
	// alert(key);
	// alert(obj.tagName);
	var val=obj.value,selEnd=getSelEnd(obj); // selEnd=obj.selectionEnd;
	if (key==60 && val.charAt(selEnd)!=">") { // <
		obj.value=val.substr(0,selEnd)+">"+val.substr(selEnd);
		setSel(obj,selEnd,selEnd);
		// obj.selectionEnd=selEnd;
	}
	else if (key!=8 && (key<48 || key>57)) { // no number
		if (val.charAt(selEnd)==">") {
			obj.value=val.substr(0,selEnd+1)+" "+val.substr(selEnd+1);
			setSel(obj,selEnd+2,selEnd+2);
			// obj.selectionStart=selEnd+2;
			// obj.selectionEnd=selEnd+2;
		}
	}
	return true;
}

function prepare_submit() {
	for (var b=firstCrit;b<next_num;b++) {
		switch (crits[b]) {
		case "structure":
		case "rc_structure":
		case "r_structure":
			setInputValue("val"+b,getSmiles("JME"+b));
			setInputValue("val"+b+"a",getMolfile("JME"+b));
		break;
		case "emp_formula":
			var moldata=computeMolecule(getInputValue("val"+b),1);
			setInputValue("val"+b,moldata["chemFormula"]);
		break;
		}
	}
	setFormAction();
	return true;
}

function perform_reset() {
	next_num=firstCrit;
	conditions=0;
	crits=[];
	ops=[];
	values=[];
	pos=[];
	setiHTML("subqueries","");
	addCrit();
}

// simple search

function updateSource(table) { // Klick auf Button für Tabelle
	if (table==undefined) {
		table=sidenav_tables[0];
	}
	
	setInputValue("table",table);
	setInputValue("fields","");
	
	updateListOp();

	for (var b=0,max=sidenav_tables.length;b<max;b++) {
		rollButton(sidenav_tables[b],sidenav_tables[b]==table);
	}
	
	// update searchWhere
	var ext=(table=="supplier_search");
	visibleObj("searchWhereFS",!ext);
	visibleObj("searchExtFS",ext);
	visibleObj("link_all",!ext);
	visibleObj("link_new",!ext);
	visibleObj("radio_view",!ext);
	visibleObj("rw_selected_only",!ext);
	
	// updateCrit
	var oldCrit=getInputValue("crit0");
	updateCrit();
	setSelectValue("crit0",oldCrit);
	updateSelects();
}

function updateCrit() {
	// crit
	setiHTML("searchCrit",getCritSelect(0,"updateSelects"));
	//~ var type=getCritType(getInputValue("crit0"));
}

function updateSelects() {
	var crit=getInputValue("crit0");
	var type=getCritType(crit);
	//~ alert(type+"X"+currentType);
	var old_unit=getInputValue("val0a");
	if (type==currentType) { // no change of type
		if (type=="structure") {
			var oldOp=getInputValue("op0");
			updateOp(type);
			setSelectValue("op0",oldOp);
		}
	}
	else {
		currentType=type;

		// op
		updateOp(type);
		
		// val
		updateVal(type);
		prevType=type;
	}
	setiHTML("unit0",getUnitSelect(0,crit,type,old_unit));
}

function updateOp(type) {
	if (getInputValue("table")=="supplier_search" && type=="structure") { // commercial search
		type="structure_ex";
	}
	setiHTML("searchModeSpan",getOpSelect(0,type));
}

function setOldMolfile(appName) {
	if (isAppletReady(appName,"mol")) {
		loadCount=0;
		putMolfile(appName,oldMolfile);
	}
	else {
		loadCount++;
		if (loadCount<40) {
			window.setTimeout(function () { setOldMolfile(appName); },300);
		}
	}
}

function updateVal(type) {
	if (prevType=="structure" && prevType!=type) {
		oldMolfile=getMolfile("JME0");
	}
	var oldVal=defBlank(getInputValue("val0"));
	setiHTML("searchSrcInput",getValInput(0,type));
	setInputValue("val0",oldVal);
	if (oldMolfile && type=="structure" && prevType!=type) {
		setOldMolfile("JME0");
	}
}

function updateDate() {
	var reaction_started_when=parseInt(getInputValue("reaction_started_when")),new_value="",int_name="val4",custom_val=5,now=Number(new Date());
	switch (reaction_started_when) {
	//~ case -1: // any
	//~ break;
	case 1: // today
		new_value=getGerDate(getDeltaDate(now));
	break;
	case 2: // yesterday
		new_value=getGerDate(getDeltaDate(now,-1));
	break;
	case 3: // this week
		new_value=getGerDate(getDeltaDate(now,-6))+"-"+getGerDate(getDeltaDate(now));
	break;
	case 4: // this month
		new_value=getGerDate(getDeltaDate(now,-29))+"-"+getGerDate(getDeltaDate(now));
	break;
	//~ case custom_val: // from-to
	//~ break;
	}
	//~ showControl(int_name,(reaction_started_when==custom_val));
	visibleObj(int_name,(reaction_started_when==custom_val));
	visibleObj(int_name+"_br",(reaction_started_when==custom_val));
	if (reaction_started_when!=custom_val) {
		setInputValue(int_name,new_value);
	}
}

function clearRefRxn() {
	setRefRxn("reset");
}