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

/*function gotoLiterature(list_int_name,UID) {
	autoScrollInProgress=true;
	if (readOnly) {
		//~ document.location.hash="ro_"+list_int_name+"_"+UID;
		//~ makeVisible("tr_readOnly_"+list_int_name+"_"+UID+"_0");
		makeVisible("tr_readOnly_"+list_int_name+"_"+UID+"_1");
	}
	else {
		//~ document.location.hash=list_int_name+"_"+UID;
		makeVisible("tr_"+list_int_name+"_"+UID+"_1");
	}
	autoScrollInProgress=false;
}*/

function getLitNav() { // show list of spectra at fixed position top right with links
	var list_int_names=["project_literature","reaction_literature_for_project"],UID,retval="<table class=\"rxnlabel\" style=\"max-width:95%\"><tbody>";
	for (var c=0,max2=list_int_names.length;c<max2;c++) {
		list_int_name=list_int_names[c];
		for (var b=0,max=controlData[list_int_name]["UIDs"].length;b<max;b++) {
			UID=controlData[list_int_name]["UIDs"][b];
			retval+="<tr><td><a href=\"Javascript:SILscrollIntoView(&quot;"+list_int_name+"&quot;,&quot;"+UID+"&quot;)\">"+getiHTML(SILgetObjName(list_int_name,UID,"citation",undefined,true))+"</a></td></tr>";
		}
	}
	retval+="</tbody></table>";
	return retval;
}

function showLitNav(obj) {
	var overlayObj=$("overlay");
	if (!overlayObj) {
		return;
	}
	overlayObj.innerHTML=getLitNav();
	overlayObj.style.display="";
	var dim=getElementSize(overlayObj);
	showOverlayObj(obj,overlayObj,-dim[0],0,0);
}

// subitemlist

function editLiterature(list_int_name,UID,int_name) {
	var literature_id=SILgetValue(list_int_name,UID,"literature_id");
	if (literature_id==undefined) {
		literature_id="";
	}
	openSearchWin(list_int_name,UID,int_name,undefined,"literature","&autoNew=true&editDbId=-1&editPk="+literature_id);
}

function delLiterature(list_int_name,UID,int_name) {
	var pk=SILgetValue(list_int_name,UID,"literature_id");
	if (pk=="") {
		return;
	}
	if (confirm(s("delWarningLiterature"))) { // löschen?
		var url="editAsync.php?"+getSelfRef(["~script~","table"])+"&desired_action=del&table=literature&db_id=-1&pk="+pk;
		setFrameURL("comm",url);
		return true;
	}
	return false;
}

function literature_getDownload(literature_data) {
	if (literature_data["has_literature_blob"] && literature_data["literature_id"]) {
		return "<a href=\"getLiterature.php?db_id=-1&literature_id="+literature_data["literature_id"]+"\" class=\"imgButtonSm\" target=\"_blank\"><img src=\"lib/edit_sm.png\" border=\"0\""+getTooltip("get_literature")+"></a>";
	}
	return "";
}

function unlinkLiterature(list_int_name,UID,int_name) {
	// remove from list
	SILdelLine(list_int_name,UID);
}

// literature

function getCitation(literature_data,mode) {
	if (literature_data.length==0) {
		return "";
	}
	
	// last, first; or first last,
	var journal_year_sep="",formatInfo="";
	switch (mode) {
	case 1: // acs
		journal_year_sep=",";
		formatInfo="JACS: ";
	break;
	case 2: // rsc
		journal_year_sep=",";
		formatInfo="RSC: ";
	break;
	case 0:
		formatInfo="Angew: ";
	// no break;
	default: // angew without info
		
	}
	
	var retval=[];
	/*
	var authors=[];
	if (is_array(literature_data["authors"])) {
		for (var b=0,max=literature_data["authors"].length;b<max;b++) {
			if (literature_data["authors"][b]["author_first"] || literature_data["authors"][b]["author_last"]) {
				if (name_order==0) {
					authors.push(literature_data["authors"][b]["author_first"]+" "+literature_data["authors"][b]["author_last"]);
				}
				else if (name_order==1) {
					authors.push(literature_data["authors"][b]["author_last"]+", "+literature_data["authors"][b]["author_first"]);
				}
			}
		}
	}
	if (authors.length) {
		//~ retval.push(authors.join(name_sep));
	}
	*/
	retval.push(getAuthorNames(literature_data,mode));
	
	if (literature_data["sci_journal_abbrev"] || literature_data["literature_year"]) {
		var text=ifnotempty("<i>",literature_data["sci_journal_abbrev"],"</i>")+ifnotempty(journal_year_sep+" <b>",literature_data["literature_year"],"</b>");
		if (mode==1) { // no comma
			retval[0]+=" "+text;
		}
		else { // also rsc
			retval.push(text);
		}
	}
	
	if (literature_data["literature_volume"] || literature_data["issue"]) {
		var text="<i>"+defBlank(literature_data["literature_volume"]);
		if (mode==undefined) {
			text+=ifnotempty(" (",literature_data["issue"],")");
		}
		text+="</i>";
		retval.push(text);
	}
	
	if (literature_data["page_low"] || literature_data["page_high"]) {
		retval.push(joinIfNotEmpty([literature_data["page_low"],literature_data["page_high"]],"-"));
	}
	else if (literature_data["doi"]) {
		retval.push("DOI "+literature_data["doi"]);
	}
	
	return "<b>"+formatInfo+"</b>"+joinIfNotEmpty(retval,", ")+".";
}

function showOtherCitations(obj,list_int_name,UID,int_name,group,thisReadOnly) {
	cancelOverlayTimeout();
	overlayTimeout[0]=window.setTimeout(function () { visibleObj(SILgetObjName(list_int_name,UID,"other_citations",group,thisReadOnly),true); },250);
}

function hideOtherCitations(obj,list_int_name,UID,int_name,group,thisReadOnly) {
	cancelOverlayTimeout();
	overlayTimeout[0]=window.setTimeout(function () { visibleObj(SILgetObjName(list_int_name,UID,"other_citations",group,thisReadOnly),false); },500);
}

function showOtherCitationsList(idx) {
	cancelOverlayTimeout(idx);
	overlayTimeout[idx]=window.setTimeout(function () { visibleObj("other_citations_"+idx,true); },250);
}

function hideOtherCitationsList(idx) {
	cancelOverlayTimeout(idx);
	overlayTimeout[idx]=window.setTimeout(function () { visibleObj("other_citations_"+idx,false); },500);
}

function getOtherCitations(literature_data) {
	var retval=[],modes=3;
	for (var mode=0;mode<modes;mode++) {
		retval.push(getCitation(literature_data,mode));
	}
	return retval.join("<br>");
}

function displayCitation(idx,values) {
	setiHTML("citation_"+idx,getCitation(values));
	setiHTML("other_citations_"+idx,getOtherCitations(values));
}

function getDOILink(literature_data) {
	// add angew hack with DOI
	var doi=literature_data["doi"],journal,iHTML="",re=/(10\.1002\/)(an[gi]e)(\D*)(\d{4})(.*)$/;
	
	if (re.exec(doi) && Number(RegExp.$4)>1997) {
		if (RegExp.$2=="ange") {
			journal="anie";
		}
		else {
			journal="ange";
		}
		iHTML="<br><a href=\"http://dx.doi.org/"+RegExp.$1+journal+RegExp.$3+RegExp.$4+RegExp.$5+"\" target=\"_blank\">"+journal.toUpperCase()+"</a>";
	}
	
	return ifnotempty("<a href=\"http://dx.doi.org/"+doi+"\" target=\"_blank\">",strcut(doi,25),"</a>"+iHTML,"&nbsp;");
}

function abbrevGivenNames(given,spaces) {
	given=String(given);
	given=given.charAt(0).toUpperCase()+given.substr(1);
	given=given.replace(/([A-ZÄÖÜ])[a-zäöüßàáâèéêìíîóòôúùûçñ]+/g,"$1.");
	switch (spaces) {
	case 1: // force
		given=given.replace(/\.[\s\-]*(.+)/g,". $1");
	break;
	case 2: // remove
		given=given.replace(/\s/g,"");
	break;
	}
	return given;
}

function getAuthorNames(values,mode) {
	var authors=a(values,"authors"),retval="",separator="";
	for (var b=0,max=authors.length;b<max;b++) {
		if (authors[b]["author_first"]=="" && authors[b]["author_last"]=="") { // remove
			continue;
		}
		retval+=separator; // is "" in first round
		switch (mode) {
		case 1:
		case 2:
			if (b+2<max) {
				separator=", ";
			}
			else {
				separator=" and ";
			}
		break;
		case 0:
		default:
			separator=", ";
		}
		
		// name
		switch (mode) {
		case 0:
		case 2:
			retval+=abbrevGivenNames(authors[b]["author_first"],1)+" "+authors[b]["author_last"];
		break;
		case 1:
			retval+=authors[b]["author_last"]+", "+abbrevGivenNames(authors[b]["author_first"],1);
		break;
		default:
			retval+=authors[b]["author_first"]+" "+authors[b]["author_last"];
		}
	}
	return retval;
}