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

var min_similarity=0.68,amount_tolerance=0.05,highlightColor="lightblue",max_differences=8; // cornflowerblue deepskyblue
reaction_chemical_lists=["reactants","reagents","products"];
reaction_chemical_joins={"reactants":["reactants","reagents"],"reagents":["reactants","reagents"],"products":["products"]};
rc_compares=["all","standard_name","cas_nr","molfile_blob","stoch_coeff","rc_amount"];
reaction_chemical_compare=["smiles_stereo","cas_nr"];
reaction_condition_include=["reaction_title","realization_text","realization_observation"];
reaction_condition_exclude=["fix_stoch","reactants_rc_amount_unit","reactants_mass_unit","reactants_volume_unit","reactants_rc_conc_unit", "products_mass_unit","products_rc_amount_unit","betr_anw_gefahren","betr_anw_schutzmass","betr_anw_verhalten","betr_anw_erste_h","betr_anw_entsorgung"];
highlighted_fields=[],diffed_fields=[];

function getRefRxnParam() {
	var retval="";
	if (ref_reaction) {
		retval+="&ref_reaction_db_id="+ref_reaction["db_id"]+"&ref_reaction_id="+ref_reaction["reaction_id"];
	}
	return retval;
}

function setRefRxn(desired_action,type,db_id,reaction_id) {
	switch (desired_action) {
	case "set":
		switch (type) {
		case "edit":
			db_id=a_db_id;
			reaction_id=a_pk;
		break;
		}
		compare_status=0;
		var url="chooseAsync.php?"+getSelfRef(["~script~","ref_reaction_db_id","ref_reaction_id"])+"&desired_action=loadReactionRef&type="+defBlank(type)+"&ref_reaction_db_id="+db_id+"&ref_reaction_id="+reaction_id;
		setFrameURL("comm",url);
		// list.php: reload & display col compare_rxn (which also contains the JSON data for comparison)
	break;
	
	case "reset":
		ref_reaction=undefined;
		ref_reaction_diagram="";
		
		if (top.sidenav) {
			top.sidenav.setiHTML("ref_reaction","");
			if (top.searchBottom && top.searchBottom.showControl) { //  && is_function(top.searchBottom.showControl)
				top.searchBottom.setiHTML("ref_reaction","");
				top.searchBottom.showControl("ref_reaction",false);
			}
		}
		switch (type) {
		case "edit":
			applyComparisonList(type);
		break;
		case "list":
			activateView();
		break;
		}
	break;
	}
}

function assignReactionChemical(ref_list_int_name,ref_pos,list_int_name,pos,sameStruc) {
	var amount_diff;
	
	this.reaction_ref[ref_list_int_name][ref_pos]["used"]=true;
	this.reaction[list_int_name][pos]["found"]=true;
	
	// compare amount
	this.reaction_chemical_diff[list_int_name][pos]["rc_amount"]=tolCompare(amount_tolerance,this.reaction_ref[ref_list_int_name][ref_pos]["rc_amount"]*getUnitFactor(this.reaction_ref[ref_list_int_name][ref_pos]["rc_amount_unit"]),this.reaction[list_int_name][pos]["rc_amount"]*getUnitFactor(this.reaction[list_int_name][pos]["rc_amount_unit"]));
	if (this.reaction_chemical_diff[list_int_name][pos]["rc_amount"]==1) {
		this.reaction_chemical_diff[list_int_name][pos]["stoch_coeff"]=tolCompare(amount_tolerance,this.reaction_ref[ref_list_int_name][ref_pos]["stoch_coeff"],this.reaction[list_int_name][pos]["stoch_coeff"]);
	}
	
	if (this.reaction_chemical_diff[list_int_name][pos]["rc_amount"]==1 || this.reaction_chemical_diff[list_int_name][pos]["stoch_coeff"]==1) {
		amount_diff=true;
	}
	else if (sameStruc) {
		return;
	}
	
	if (sameStruc) {
		this.diff_text+="<li>";
		this.diff_text+=compareGetName(this.reaction,list_int_name,pos);
		this.diff_text+=": "+formatAmount(this.reaction[list_int_name][pos])+" "+s("instead_of")+" "+formatAmount(this.reaction_ref[ref_list_int_name][ref_pos])+"</li>";
	}
	else {
		this.reaction_chemical_diff[list_int_name][pos]["standard_name"]=1;
		this.reaction_chemical_diff[list_int_name][pos]["cas_nr"]=1;
		this.reaction_chemical_diff[list_int_name][pos]["molfile_blob"]=1;
		
		this.diff_text+="<li>"+compareGetName(this.reaction,list_int_name,pos)+" ";
		if (amount_diff) {
			this.diff_text+=formatAmount(this.reaction[list_int_name][pos])+" ";
		}
		this.diff_text+=s("instead_of")+" "+compareGetName(this.reaction_ref,ref_list_int_name,ref_pos);
		if (amount_diff) {
			this.diff_text+=" "+formatAmount(this.reaction_ref[ref_list_int_name][ref_pos]);
		}
		this.diff_text+="</li>";
	}
	this.diff_count++;
}

function compareReactionChemical(ref_list_int_name,ref_pos,list_int_name,pos) {
	var var1,var2,compare;
	for (var comp=0,max_comp=reaction_chemical_compare.length;comp<max_comp;comp++) {
		compare=reaction_chemical_compare[comp];
		
		var1=this.reaction_ref[ref_list_int_name][ref_pos][compare];
		var2=this.reaction[list_int_name][pos][compare];
		
		if (isEmptyStr(var1)) {
			continue;
		}
		if (var1==var2) {
			this.assignReactionChemical(ref_list_int_name,ref_pos,list_int_name,pos,true);
			return true;
		}
		else {
			return false;
		}
	}
	return false;
}

function formatAmount(rc) {
	return rxnRound(rc["rc_amount"])+"&nbsp;"+rc["rc_amount_unit"]+ifnotempty(" (",rxnRound(rc["stoch_coeff"]),"&nbsp;eq)")
}

function compareGetName(reaction,list_int_name,pos) {
	if (reaction[list_int_name][pos]["standard_name"]) {
		return reaction[list_int_name][pos]["standard_name"];
	}
	else {
		return getRCname(list_int_name)+" "+(pos+1)+ifnotempty(" (",getBeautySum(reaction[list_int_name][pos]["emp_formula"]),")");
	}
}

function tolCompare(tolerance,val1,val2) {
	val1=def0(parseFloat(val1));
	val2=def0(parseFloat(val2));
	if (val1==val2) {
		return 0;
	}
	if (val1==0 || val2==0) {
		return 1;
	}
	return ((Math.abs(val1-val2)/val1>tolerance)?1:0);
}

function getDisableCmpButton() {
	return "<a href=\"Javascript:setRefRxn(&quot;reset&quot;,&quot;"+this.type+"&quot;); \"><img src=\"lib/del_sm.png\" border=\"0\""+getTooltip("compare_rxn_disable")+"></a>";
}

var EM="<em>",ME="</em>",LOOKAHEAD_LIMIT=25,MAX_DISTANCE=50,MAX_TIME=3000,MAX_STEP_TIME=300,split_chars=[" ","\n","\r","-","(",")",",",".",";",":","!","?","/"];
// split words and tags
function htmlSplit(html) {
	var retval=[],tag_active=false,escape_active=false,single_quoted_active=false,double_quoted_active=false,entity_active=false,temp="";
	
	for (var b=0;b<html.length;b++) {
		var character=html.charAt(b);
		
		if (!single_quoted_active && !double_quoted_active) {
			if (character=="<") {
				if (tag_active) {
					// fix
					character="&lt;";
				}
				else {
					if (temp!="") retval.push(temp);
					temp="";
					tag_active=true;
				}
			}
			else if (character==">") {
				if (tag_active) {
					temp+=character;
					character="";
					retval.push(temp);
					temp="";
					tag_active=false;
				}
				else {
					// fix
					character="&gt;";
				}
			}
		}
		
		if (character=="\\") {
			escape_active=true;
		}
		else if (escape_active) {
			escape_active=false;
		}
		else if (!escape_active && !double_quoted_active && character=="\'") {
			single_quoted_active=!single_quoted_active;
		}
		else if (!escape_active && !single_quoted_active && character=="\"") {
			double_quoted_active=!double_quoted_active;
		}
		else if (character=="&") {
			entity_active=true;
		}
		else if (entity_active && character==";") {
			entity_active=false;
		}
		else if (!tag_active && !entity_active && !single_quoted_active && !double_quoted_active && in_array(character,split_chars)) {
			temp+=character;
			character="";
			retval.push(temp);
			temp="";
		}
		
		temp+=character;
	}
	
	// end
	if (temp!="") retval.push(temp);
	
	return retval;
}

function addMarkedToBuffer(buffer,text) {
	if (text.charAt(0)=="<" || trim(text)=="") {
		buffer.push(text);
	}
	else {
		buffer.push(EM+text+ME);
	}
}

function textEqual(text1,text2,tolerant) {
	if (text1=="" && text2=="") { // bogus
		return false;
	}
	if (text1==text2) { // save time
		return true;
	}
	var trim1=trim(text1);
	if (!tolerant && trim1=="") {
		return false;
	}
	return trim1==trim(text2);
}

function htmlDiff(src,dst,src_name,dst_name) {
	var retval="<table class=\"diff\"><thead><tr><th class=\"texttitle\">"+src_name+"</th><th class=\"texttitle\">"+dst_name+"</th></tr></thead><tbody>";
	var src_words=htmlSplit(cleanHTML(src)),dst_words=htmlSplit(cleanHTML(dst)); // do not forget to clean the HTML before
	var src_ff_pos=0,dst_ff_pos=0,src_ff_buffer=[],dst_ff_buffer=[];
	var src_rw_pos=src_words.length-1,dst_rw_pos=dst_words.length-1,src_rw_buffer=[],dst_rw_buffer=[];
	var basis,distance,something_found,start_time=Number(new Date()),step_start_time,now,ignore_result=false,text1,text2,text3,text4;
	
	// go along src_words
	MainLoop: while (src_ff_pos<=src_rw_pos && dst_ff_pos<=dst_rw_pos) {
		text1=src_words[src_ff_pos],text2=dst_words[dst_ff_pos];
		
		// empty stuff
		if (trim(text1)=="") {
			src_ff_buffer.push(text1);
			src_ff_pos++;
			continue;
		}
		if (trim(text2)=="") {
			dst_ff_buffer.push(text2);
			dst_ff_pos++;
			continue;
		}
		if (src_ff_pos<src_rw_pos) {
			text3=src_words[src_rw_pos];
			if (trim(text3)=="") {
				src_rw_buffer.push(text3);
				src_rw_pos--;
				continue;
			}
		}
		else {
			text3="";
		}
		if (dst_ff_pos<dst_rw_pos) {
			text4=dst_words[dst_rw_pos];
			if (trim(text4)=="") {
				dst_rw_buffer.push(text4);
				dst_rw_pos--;
				continue;
			}
		}
		else {
			text4="";
		}
		
		// matches from beginning
		if (ignore_result || textEqual(text1,text2,true)) { // identical
			src_ff_pos++;
			dst_ff_pos++;
			if (!ignore_result) {
				src_ff_buffer.push(text1);
				dst_ff_buffer.push(text2);
				continue;
			}
			else {
				addMarkedToBuffer(src_ff_buffer,text1);
				addMarkedToBuffer(dst_ff_buffer,text2);
			}
		}
		
		// matches from end
		if (ignore_result || textEqual(text3,text4,true)) { // identical
			src_rw_pos--;
			dst_rw_pos--;
			if (!ignore_result) {
				src_rw_buffer.push(text3);
				dst_rw_buffer.push(text4);
				continue;
			}
			else {
				addMarkedToBuffer(src_rw_buffer,text3);
				addMarkedToBuffer(dst_rw_buffer,text4);
			}
		}
		
		if (ignore_result) {
			ignore_result=false;
			continue;
		}
		
		// neither match
		var delta=Math.max(src_rw_pos-src_ff_pos,dst_rw_pos-dst_ff_pos);
		
		basis=0;
		step_start_time=Number(new Date());
		something_found=false;
		while (basis<delta && basis<LOOKAHEAD_LIMIT) {
			distance=Math.max(basis,1); // 0,0 does make much sense
			while (distance<delta && distance<MAX_DISTANCE) {
				// look ahead for src_words[src_ff_pos] in dst_words
				if (textEqual(src_words[src_ff_pos+basis],dst_words[dst_ff_pos+distance],false)) {
					// match found
					
					// add all between src_ff_pos and basis
					for (var c=0;c<basis;c++) {
						addMarkedToBuffer(src_ff_buffer,src_words[src_ff_pos]);
						src_ff_pos++;
					}
					
					// add all between dst_ff_pos and distance
					for (var c=0;c<distance;c++) {
						addMarkedToBuffer(dst_ff_buffer,dst_words[dst_ff_pos]);
						dst_ff_pos++;
					}
					something_found=true;
					break; // rerun outer loop
				}
				
				// look ahead for dst_words[dst_ff_pos] in src_words
				if (textEqual(src_words[src_ff_pos+distance],dst_words[dst_ff_pos+basis],false)) {
					// match found
					
					// add all between dst_ff_pos and basis
					for (var c=0;c<basis;c++) {
						addMarkedToBuffer(dst_ff_buffer,dst_words[dst_ff_pos]);
						dst_ff_pos++;
					}
					
					// add all between src_ff_pos and distance
					for (var c=0;c<distance;c++) {
						addMarkedToBuffer(src_ff_buffer,src_words[src_ff_pos]);
						src_ff_pos++;
					}
					something_found=true;
					break; // rerun outer loop
				}
				
				// look before for src_words[src_rw_pos] in dst_words
				if (textEqual(src_words[src_rw_pos-basis],dst_words[dst_rw_pos-distance],false)) {
					// match found
					
					// add all between src_rw_pos and basis
					for (var c=0;c<basis;c++) {
						addMarkedToBuffer(src_rw_buffer,src_words[src_rw_pos]);
						src_rw_pos--;
					}
					
					// add all between dst_rw_pos and distance
					for (var c=0;c<distance;c++) {
						addMarkedToBuffer(dst_rw_buffer,dst_words[dst_rw_pos]);
						dst_rw_pos--;
					}
					something_found=true;
					break; // rerun outer loop
				}
				
				// look before for dst_words[dst_rw_pos] in src_words
				if (textEqual(src_words[src_rw_pos-distance],dst_words[dst_rw_pos-basis],false)) {
					// match found
					
					// add all between dst_rw_pos and basis
					for (var c=0;c<basis;c++) {
						addMarkedToBuffer(dst_rw_buffer,dst_words[dst_rw_pos]);
						dst_rw_pos--;
					}
					
					// add all between src_rw_pos and distance
					for (var c=0;c<distance;c++) {
						addMarkedToBuffer(src_rw_buffer,src_words[src_rw_pos]);
						src_rw_pos--;
					}
					something_found=true;
					break; // rerun outer loop
				}
				
				distance++;
			}
			now=Number(new Date());
			if (now-start_time>MAX_TIME) {
				break MainLoop;
			}
			if (something_found || now-step_start_time>MAX_STEP_TIME) {
				break; // hand over to outer loop
			}
				
			basis++;
		}
		if (!something_found) {
			ignore_result=true; // add one part everywhere as unmatched
		}
	}
	
	// add missing stuff
	if (src_ff_pos<=src_rw_pos) {
		for (;src_ff_pos<=src_rw_pos;src_ff_pos++) {
			addMarkedToBuffer(src_ff_buffer,src_words[src_ff_pos]);
		}
	}
	if (dst_ff_pos<=dst_rw_pos) {
		for (;dst_ff_pos<=dst_rw_pos;dst_ff_pos++) {
			addMarkedToBuffer(dst_ff_buffer,dst_words[dst_ff_pos]);
		}
	}
	
	// build result
	retval+="<td>"+src_ff_buffer.join("");
	for (var b=src_rw_buffer.length-1;b>=0;b--) {
		retval+=src_rw_buffer[b];
	}
	retval+="</td><td>"+dst_ff_buffer.join("");
	for (var b=dst_rw_buffer.length-1;b>=0;b--) {
		retval+=dst_rw_buffer[b];
	}
	retval+="</td></tr></tbody></table>";
	
	// clean
	var match,pattern=/<\/em>([\s\n\r\t]*)<em>/i;
	while (match=pattern.exec(retval)) {
		retval=retval.replace(match[0],match[1]);
	}
	retval=nl2br(retval,true);
	//~ alert(src+"\n\n"+dst+"\n\n"+retval);
	return retval;
}

function performCompare() { // compare 1 reaction object to reference
	this.diff_text="";
	
	if (this.reaction_ref["db_id"]==this.reaction["db_id"] && this.reaction_ref["reaction_id"]==this.reaction["reaction_id"]) {
		this.diagram="<table class=\"diagram_table\"><tbody><tr><td>"+this.getDisableCmpButton()+"</td><td style=\"width:"+(bar_width+5)+"px\">"+s("reference_reaction")+"</td><td style=\"width:"+(bar_width+5)+"px\">"+ref_reaction_diagram+"</td></tr></tbody></table>"; // todo: show different products in diagram?
		this.is_reference=true;
		return;
	}
	this.diagram="<table class=\"diagram_table\"><tbody><tr><td>"+this.getDisableCmpButton()+"</td><td style=\"width:"+(bar_width+5)+"px\">"+getGraphicalYield(this.reaction["products"])+"</td><td style=\"width:"+(bar_width+5)+"px\">"+ref_reaction_diagram+"</td></tr></tbody></table>"; // todo: show different products in diagram?
	this.is_reference=false;
	this.diff_text+="<ul class=\"compare_rxn\">";
	this.diff_count=0;
	
	// conditions
	var reaction_chemical_list,reaction_chemical_join,compare_condition,compare_condition2,compare_conditions=array_unique(array_diff(arr_merge(this.reaction_ref["reaction_property"],this.reaction["reaction_property"],reaction_condition_include),reaction_condition_exclude)),additional=[],missing=[];
	
	// 1st pass
	for (var b=0,max=compare_conditions.length;b<max;b++) {
		compare_condition=compare_conditions[b];
		
		switch (compare_condition) {
		// fine tune comparison here
		case "realization_text": // in 2nd pass
		case "realization_observation": // in 2nd pass
		case "ref_amount_unit":
		break;
		case "ref_amount":
			compare_condition2="ref_amount_unit";
			if (tolCompare(amount_tolerance,this.reaction_ref[compare_condition]*getUnitFactor(this.reaction_ref[compare_condition2]),this.reaction[compare_condition]*getUnitFactor(this.reaction[compare_condition2]))) {
				this.diff_list.push(compare_condition);
				this.diff_text+="<li>"+ifnotempty("",s(compare_condition),": ")+defBlank(this.reaction[compare_condition])+" "+defBlank(this.reaction[compare_condition2])+" "+s("instead_of")+" "+defBlank(this.reaction_ref[compare_condition])+" "+defBlank(this.reaction_ref[compare_condition2])+"</li>";
				this.diff_count++;
			}
		break;
		default:
			if (defBlank(this.reaction_ref[compare_condition])!=defBlank(this.reaction[compare_condition])) {
				this.diff_list.push(compare_condition);
				this.diff_text+="<li>"+ifnotempty("",s(compare_condition),": ")+defBlank(this.reaction[compare_condition])+" "+s("instead_of")+" "+defBlank(this.reaction_ref[compare_condition])+"</li>";
				this.diff_count++;
			}
		}
	}
	
	// create dummy objects if necessary
	for (var b=0,max=reaction_chemical_lists.length;b<max;b++) {
		reaction_chemical_list=reaction_chemical_lists[b];
		
		if (!this.reaction_chemical_diff[reaction_chemical_list]) {
			this.reaction_chemical_diff[reaction_chemical_list]=[];
		}
		
		// prepare empty list
		if (!this.reaction[reaction_chemical_list]) {
			this.reaction[reaction_chemical_list]=[];
		}
		for (var c=0,max2=this.reaction[reaction_chemical_list].length;c<max2;c++) { // reset as object is referenced
			this.reaction[reaction_chemical_list][c]["found"]=false;
		}
		
		// prepare empty list
		if (!this.reaction_ref[reaction_chemical_list]) {
			this.reaction_ref[reaction_chemical_list]=[];
		}
		for (var d=0,max3=this.reaction_ref[reaction_chemical_list].length;d<max3;d++) { // reset as object is referenced
			this.reaction_ref[reaction_chemical_list][d]["used"]=false;
		}
	}
	
	// components
	// A same place same structure
	var max3,struc_changed=false;
	for (var b=0,max=reaction_chemical_lists.length;b<max;b++) {
		reaction_chemical_list=reaction_chemical_lists[b];
		max3=this.reaction_ref[reaction_chemical_list].length;
		
		for (var c=0,max2=this.reaction[reaction_chemical_list].length;c<max2;c++) {
			this.reaction_chemical_diff[reaction_chemical_list][c]=[]; // init here
			
			if (c<max3) {
				this.compareReactionChemical(reaction_chemical_list,c,reaction_chemical_list,c);
			}
		}
	}
	
	// B different place, same structure
	for (var b=0,max=reaction_chemical_lists.length;b<max;b++) {
		reaction_chemical_list=reaction_chemical_lists[b];
		
		for (var c=0,max2=this.reaction[reaction_chemical_list].length;c<max2;c++) {
			if (this.reaction[reaction_chemical_list][c]["found"]) {
				continue;
			}
			reaction_chemical_join_loop: for (var e=0,max4=reaction_chemical_joins[reaction_chemical_list].length;e<max4;e++) {
				reaction_chemical_join=reaction_chemical_joins[reaction_chemical_list][e];
				
				for (var d=0,max3=this.reaction_ref[reaction_chemical_join].length;d<max3;d++) {
					if (this.reaction_ref[reaction_chemical_join][d]["used"]) {
						continue;
					}
					
					if (this.compareReactionChemical(reaction_chemical_join,d,reaction_chemical_list,c)) {
						break reaction_chemical_join_loop;
					}
				}
			}
		}
	}
	
	// C same place, similar formula
	for (var b=0,max=reaction_chemical_lists.length;b<max;b++) {
		reaction_chemical_list=reaction_chemical_lists[b];
		
		for (var c=0,max2=Math.min(this.reaction[reaction_chemical_list].length,this.reaction_ref[reaction_chemical_list].length);c<max2;c++) {
			if (this.reaction[reaction_chemical_list][c]["found"] || this.reaction_ref[reaction_chemical_list][c]["used"]) {
				continue;
			}
			if (0.9*getSumFormulaSimilarity(this.reaction_ref[reaction_chemical_list][c]["emp_formula"],this.reaction[reaction_chemical_list][c]["emp_formula"])>min_similarity) {
				this.assignReactionChemical(reaction_chemical_list,c,reaction_chemical_list,c,false);
			}
		}
	}
	
	// D no match
	for (var b=0,max=reaction_chemical_lists.length;b<max;b++) {
		reaction_chemical_list=reaction_chemical_lists[b];
		
		for (var c=0,max2=this.reaction[reaction_chemical_list].length;c<max2;c++) {
			if (!this.reaction[reaction_chemical_list][c]["found"]) {
				this.reaction_chemical_diff[reaction_chemical_list][c]["all"]=1;
				this.reaction_chemical_diff[reaction_chemical_list][c]["molfile_blob"]=1;
				this.reaction_chemical_diff[reaction_chemical_list][c]["stoch_coeff"]=1;
				this.reaction_chemical_diff[reaction_chemical_list][c]["rc_amount"]=1;
				additional.push(compareGetName(this.reaction,reaction_chemical_list,c)+" "+formatAmount(this.reaction[reaction_chemical_list][c]) );
			}
		}
	}
	
	// E unmatched
	for (var b=0,max=reaction_chemical_lists.length;b<max;b++) {
		reaction_chemical_list=reaction_chemical_lists[b];
		for (var d=0,max3=this.reaction_ref[reaction_chemical_list].length;d<max3;d++) {
			if (!this.reaction_ref[reaction_chemical_list][d]["used"]) {
				missing.push(compareGetName(this.reaction_ref,reaction_chemical_list,d));
			}
		}
	}
	
	this.diff_count+=additional.length+missing.length;
	
	if (this.diff_count==0) {
		this.diff_text=s("identical"); // this.getDisableCmpButton()+
	}
	else if (this.diff_count>max_differences) {
		this.diff_text=s("fundamentally_different"); // this.getDisableCmpButton()+
		this.diff_list=[];
		this.reaction_chemical_diff={};
	}
	else {
		if (additional.length) {
			this.diff_text+="<li>"+s("additional1")+" "+additional.join(", ")+" "+s("additional2")+"</li>";
			struc_changed=true;
		}
		if (missing.length) {
			this.diff_text+="<li>"+s("missing1")+" "+missing.join(", ")+" "+s("missing2")+"</li>";
			struc_changed=true;
		}
	}
	
	if (this.diff_count<=max_differences && this.type=="edit") { // slow stuff
		// 2nd pass
		for (var b=0,max=compare_conditions.length;b<max;b++) {
			compare_condition=compare_conditions[b];
			
			switch (compare_condition) {
			case "realization_text":
			case "realization_observation":
				// diff
				var src=this.reaction[compare_condition],dst=this.reaction_ref[compare_condition];
				if (src && dst && src!=dst) { // save time otherwise
					var html_diff=htmlDiff(src,dst,getReactionName(this.reaction),getReactionName(this.reaction_ref));
					if (html_diff.indexOf("<em>")>=0) {
						// some change found
						this.html_diff[compare_condition]=html_diff;
						// do not count as difference
					}
				}
			break;
			}
		}
	}
	
	if (struc_changed) {
		this.diff_list.push("rxn_structure");
	}
	
	// analytics: maybe later
	this.diff_text+="</ul>";
}

function setCompare(comp_reaction) { // compare 1 reaction object to reference
	this.reaction=comp_reaction;
	this.diff_text="";
	this.diagram="";
	this.diff_notice=""; // less relevant information
	this.diff_list=[];
	this.reaction_chemical_diff={};
	this.html_diff={};
	if (this.reaction_ref) { // otherwise doing reset
		this.performCompare();
	}
}

function reaction_comparison(reference_reaction,type) {
	this.reaction_ref=reference_reaction; // obj
	this.diagram=getGraphicalYield(a(reference_reaction,"products"));
	this.is_reference=false;
	this.type=type;
	this.getDisableCmpButton=getDisableCmpButton;
	this.diff_count=0;
	this.assignReactionChemical=assignReactionChemical;
	this.compareReactionChemical=compareReactionChemical;
	this.performCompare=performCompare;
	this.setCompare=setCompare;
}

function getReactionName(reaction) {
	if (reaction) {
		return reaction["lab_journal_code"]+reaction["nr_in_lab_journal"];
	}
	return "";
}

function listGetId(idx,col,index,sub_item) {
	return col+ifnotempty("_",index)+ifnotempty("_",sub_item)+"_"+idx;
}

function unhighlightAll() {
	for (var b=0,max=highlighted_fields.length;b<max;b++) {
		cmpUnhighlight(highlighted_fields[b]);
	}
	highlighted_fields.length=0;
	var key;
	for (var b=0,max=diffed_fields.length;b<max;b++) {
		key=diffed_fields[b];
		setiHTML("value_"+key,dataCache[a_db_id][a_pk][key]);
		//~ setControl(diffed_fields[b],);
	}
	diffed_fields.length=0;
}
	
function cmpUnhighlight(id) { // idx,col,index,sub_item
	var obj=$(id); // listGetId(idx,col,index,sub_item)
	if (obj) {
		obj.style.backgroundColor="";
	}
}

function idHighlight(id) {
	var obj=$(id);
	if (obj) {
		obj.style.backgroundColor=highlightColor;
		highlighted_fields.push(id);
	}
}

// highlight in detail mode also the editable items
function editHighlight(int_name) {
	idHighlight(int_name);
	idHighlight("ro_"+int_name);
}

function SILHighlight(list_int_name,UID,int_name) {
	editHighlight(SILgetObjName(list_int_name,UID,int_name));
}

function listHighlight(idx,col,index,sub_item) {
	idHighlight(listGetId(idx,col,index,sub_item));
}

function getYieldBar(value,title,color,style) { // very simplified compared to the resp PHP
	if (value<=0 || isNaN(value)) {
		return "";
	}
	
	var borderWidth=1,borderStyle="solid";
	
	switch (style) {
	case 2:
		borderStyle="dotted";
	break;
	case 3:
		borderWidth=2;
	break;
	}
	
	var width=bar_width*value*0.01-borderWidth*2;
	return "<td class=\"diagram\" title="+fixStr(title)+" style=\"width:"+width+"px;min-width:"+width+"px;height:"+bar_height+"px;background-color:"+color+";border:"+borderWidth+"px "+borderStyle+" black\">&nbsp;</td>";
}

function getGraphicalYield(products) {
	var retval="",yield_text=" ("+s("diagram_yield")+") : ",gc_yield_text=" ("+s("diagram_gc_yield")+") : ",product_text=s("product");
	
	if (!products) {
		return retval;
	}
	
	for (var b=0,max=products.length;b<max;b++) {
		retval+="<table cellspacing=0 class=\"diagram\"><tbody><tr>";
		var this_yield=parseFloat(products[b]["yield"]),this_gc_yield=parseFloat(products[b]["gc_yield"]);
		var product_name=ifempty(products[b]["standard_name"],product_text+" "+(b+1));
		if (this_yield>this_gc_yield || isNaN(this_yield)) {
			retval+=
				getYieldBar(this_gc_yield,product_name+gc_yield_text+yieldFmt(this_gc_yield),diagram_colors[b],2)+
				getYieldBar(this_yield-this_gc_yield,product_name+yield_text+yieldFmt(this_yield),diagram_colors[b],3);
		}
		else {
			retval+=
				getYieldBar(this_yield,product_name+yield_text+yieldFmt(this_yield),diagram_colors[b],3)+
				getYieldBar(this_gc_yield-this_yield,product_name+gc_yield_text+yieldFmt(this_gc_yield),diagram_colors[b],2);
		}
		retval+="</tr></tbody></table>";
	}
	return retval;
}

function applyComparisonList(type) {
	// check conditions
	switch (type) {
	case "edit":
		var comparison=new reaction_comparison(ref_reaction,type),UID,id,diagram="";
		comparison.setCompare(a(dataCache,a_db_id,a_pk));
		
		// unhighlight all
		unhighlightAll();
		
		setiHTML("compare_rxn_td",comparison.diagram+comparison.diff_text); // Text
		
		if (comparison.is_reference==true) {
			return;
		}
		
		// highlight diffs
		for (var c=0,max2=comparison.diff_list.length;c<max2;c++) {
			editHighlight(comparison.diff_list[c]);
		}
		
		// html diffs
		for (var key in comparison.html_diff) {
			setiHTML("value_"+key,comparison.html_diff[key]);
			diffed_fields.push(key);
		}
		
		// reaction_chemical
		for (var e=0,max4=reaction_chemical_lists.length;e<max4;e++) { // Listen durchgehen
			reaction_chemical_list=reaction_chemical_lists[e];
			if (!comparison.reaction_chemical_diff[reaction_chemical_list]) {
				continue;
			}
			
			for (var c=0,max2=comparison.reaction[reaction_chemical_list].length;c<max2;c++) { // diese Liste (zB Edukte) durchgehen
				if (!comparison.reaction_chemical_diff[reaction_chemical_list][c]) {
					continue;
				}
				
				UID=SILgetUID(reaction_chemical_list,c);
				
				for (var d=0,max3=rc_compares.length;d<max3;d++) { // Kriterien durchgehen
					rc_compare=rc_compares[d];
					if (comparison.reaction_chemical_diff[reaction_chemical_list][c][rc_compare]==1) {
						//~ if (rc_compare=="all") {
							//~ rc_compare=undefined;
						//~ }
						if (rc_compare=="molfile_blob") {
							id=SILgetObjName(reaction_chemical_list,UID,rc_compare);
							idHighlight("img"+id);
							idHighlight("img_ro_"+id);
						}
						else {
							SILHighlight(reaction_chemical_list,UID,rc_compare);
						}
					}
				}
			}
		}
		
	break;
	case "list":
		if (compare_status==1) { // done already
			return;
		}
		var reaction_chemical_list,rc_compare;
		
		// unhighlight all
		unhighlightAll();
		
		// go through list
		for (var b=0,max=compare_obj.length;b<max;b++) {
			var comparison=new reaction_comparison(ref_reaction,type);
			comparison.setCompare(compare_obj[b]);
			setiHTML("compare_rxn_"+b,comparison.diagram+comparison.diff_text); // Text
			
			if (comparison.is_reference==true || !ref_reaction) {
				continue;
			}
			
			// highlight diffs
			for (var c=0,max2=comparison.diff_list.length;c<max2;c++) {
				listHighlight(b,comparison.diff_list[c]);
			}
			
			// reaction_chemical
			for (var e=0,max4=reaction_chemical_lists.length;e<max4;e++) { // Listen durchgehen
				reaction_chemical_list=reaction_chemical_lists[e];
				
				if (comparison.reaction_chemical_diff[reaction_chemical_list]) for (var c=0,max2=comparison.reaction[reaction_chemical_list].length;c<max2;c++) { // diese Liste (zB Edukte) durchgehen
					for (var d=0,max3=rc_compares.length;d<max3;d++) { // Kriterien durchgehen
						rc_compare=rc_compares[d];
						if (comparison.reaction_chemical_diff[reaction_chemical_list][c][rc_compare]==1) {
							if (rc_compare=="all") {
								rc_compare=undefined;
							}
							listHighlight(b,reaction_chemical_list,c,rc_compare);
						}
					}
				}
			}
		}
		compare_status=1;
	break;
	}
}
