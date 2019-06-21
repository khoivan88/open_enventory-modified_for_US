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

function updateGCcross(peak_no,rc_no) { // handles user clicks
	// ist checked?
	if (getChecked(getCheckID(peak_no,rc_no))==false) {
		return;
	}
	
	// unset all other in line
	if (peak_no!=0) { // peak_no==0 => delete
		for (var b=0,max=gc_rc_uids.length;b<max;b++) {
			if (b!=rc_no) {
				uncheckObj(getCheckID(peak_no,b));
			}
		}
	}
	
	// unset all other in col
	for (var c=0,max2=peaks.length;c<max2;c++) {
		if (c!=peak_no) {
			uncheckObj(getCheckID(c,rc_no));
		}
	}
}

function initSimilarOverlay(id,retention_time) {
	if (id && analytics_type_id && analytics_device_id && analytics_method_id && retention_time) {
		var url="chooseAsync.php?desired_action=findSimilarRetentionTimes&id="+id+"&analytics_type_id="+analytics_type_id+"&analytics_device_id="+analytics_device_id+"&analytics_method_id="+analytics_method_id+"&retention_time="+retention_time;
		setFrameURL("comm",url);
	}
}

function showSimilarOverlay(id,data) {
	// show name, CAS, emp_formula, mw, bp@press
	var bp,gif,iHTML="<table class=\"subitemlist\"><thead><tr><td>"+s("molecule_name")+"<br>"+s("cas_nr")+"</td><td>"+s("structure")+"</td><td>"+s("emp_formula")+"<br>"+s("mw")+"</td><td>"+s("bp_high")+"</td><td>"+s("ret_time")+"</td></tr></thead><tbody>",overlayId="overlay";
	//~ var obj=$(id);
	var obj=$("analytical_data_img");
	for (var b=0,max=data.length;b<max;b++) {
		if (data[b]["molecule_id"]) { // molecule
			bp=formatBoilingPoint(data[b]["bp_low"],data[b]["bp_high"],data[b]["bp_press"],data[b]["press_unit"]);
			gif="molecule_id="+data[b]["molecule_id"];
		}
		else if (data[b]["reaction_chemical_id"]) { // reaction_chemical
			bp="&nbsp;";
			gif="reaction_chemical_id="+data[b]["reaction_chemical_id"];
		}
		else {
			continue;
		}
		// Name, CAS | structure | emp_formula, mw | bp@press | rt
		iHTML+="<tr><td>"+defBlank(data[b]["molecule_name"])+"<br>"+defBlank(data[b]["cas_nr"])+"</td><td><img src=\"getGif.php?db_id=-1&"+gif+"\"></td><td>"+getBeautySum(data[b]["emp_formula"])+"<br>"+round(data[b]["mw"],2)+"</td><td>"+bp+"</td><td>"+data[b]["retention_time"]+"</td></tr>";
	}
	iHTML+="</tbody></table>";
	setiHTML(overlayId,iHTML);
	//~ showOverlayId(obj,overlayId,0,0,8);
	showOverlayId(obj,overlayId,0,0,2+16+128);
}

function setOpenerData(int_name,col,value,noOverwrite) {
	// set RT in opener
	var id=int_name+"_"+UID+"_"+gc_rc_uids[col];
	if (noOverwrite) {
		if (opener.getInputValue(id)!="") {
			return;
		}
	}
	opener.setInputValue(id,value);
	opener.touchOnChange(id);
}

function setOpenerRT(col,value) {
	// set RT in opener
	setOpenerData("gc_peak_retention_time",col,value);
}

function setOpenerComment(col,value) {
	setOpenerData("gc_peak_gc_peak_comment",col,value,true);
}

function okClicked() {
	if (!opener) {
		return;
	}
	// spalten durchgehen und retentionszeiten in opener schreiben, der rest dürfte automatisch gehen
	var found;
	for (var b=0,max=gc_rc_uids.length;b<max;b++) {
		// zeilen durchgehen
		found=false;
		for (var c=0,max2=peaks.length;c<max2;c++) {
			if (getChecked(getCheckID(c,b))==true) {
				// set RT in opener
				setOpenerRT(b,peaks[c]["time"]);
				if (c==0) { // delete
					setOpenerData("gc_peak_area_percent",b,"");
					setOpenerData("gc_peak_response_factor",b,"");
				}
				if (show_comment) {
					setOpenerComment(b,peaks[c]["comment"]);
				}
				found=true;
				break;
			}
		}
		// nothing checked, set empty
		if (found==false) {
			if (opener.getInputValue("gc_peak_retention_time_"+UID+"_"+gc_rc_uids[b])!=="") {
				setOpenerData("gc_peak_area_percent",b,"");
			}
			setOpenerRT(b,"");
		}
	}
	
	window.close();
}

function getCheckID(peak_no,rc_no) {
	return "cross_"+peak_no+"_"+rc_no;
}