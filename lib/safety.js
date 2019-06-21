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

function getFontSizeCSS(normalSize,unit,text,normLength) {
	var fontSizeFactor=getFontSizeFactor(normalSize,text,normLength);
	return "font-size:"+round(fontSizeFactor,5)+unit+";line-height:"+round(1.05*fontSizeFactor,5)+unit+";";
}

function getFontSizeFactor(normalSize,text,normLength) {
	if (text) {
		var length=text.length;
		if (length>normLength) {
			if (normLength<=0) {
				normLength=1;
			}
			// length = 40, normLength = 20 means shrink to 50% of the area
			return normalSize/Math.sqrt(length/normLength);
		}
	}
	return normalSize;
}

function showRSTooltip(obj,type,value) {
	var iHTML="<div class=\"structureOverlay\">"+procClauses(type,value)+"</div>";
	showBottomOverlay(obj,iHTML);
}

function showRSTooltipEdit(obj,int_name,thisReadOnly) {
	var value=getInputValue(int_name);
	switch (int_name) {
	case "safety_r":
		int_name="r";
	break;
	case "safety_s":
		int_name="s";
	break;
	case "safety_h":
		int_name="h";
	break;
	case "safety_p":
		int_name="p";
	break;
	}
	showRSTooltip(obj,int_name,value);
}

function getSymbolFilename(type,thisSym) {
	if (thisSym) {
		thisSym=thisSym.toUpperCase();
		var retval;
		if (type=="ghs") {
			thisSym=parseInt(getNumber(thisSym),10); // bugfix for octal numbers, what a bogus!
			retval=a(arrSymURL,thisSym);
		}
		else {
			retval=a(arrSymURL,thisSym.substr(0,1));
		}
		if (retval!="") {
			return "lib/"+retval;
		}
	}
	return "";
}

function getSymbolHtml(type,thisSym,w,h) {
	if (thisSym) {
		var filename=getSymbolFilename(type,thisSym);
		if (type=="ghs") {
			thisSym=parseInt(getNumber(thisSym),10); // bugfix for octal numbers, what a bogus!
			thisSym="GHS0"+thisSym;
		}
		if (filename) {
			return "<img src="+fixStr(filename)+" height="+fixStr(h)+" width="+fixStr(w)+" align=\"left\""+getTooltipP(thisSym+": "+a(arrSymTooltip,thisSym))+">";
		}
	}
	return "";
}

function splitSymbols(strSym) {
	if (strSym.indexOf(",")>=0) {
		return strSym.split(",");
	}
	else {
		return strSym.split(" ");
	}
}

function getSymbols(type,strSym,w,h) {
	var retval="",filename;
	if (!w) {
		w=62;
	}
	if (!h) {
		h=61;
	}
	var arrSym=splitSymbols(strSym);
	for (var b=0,max=arrSym.length;b<max;b++) {
		retval+=getSymbolHtml(type,arrSym[b],w,h);
	}
	return retval;
}

function getSingleSafetyText(type,num) { // take clean data only
	if (num.indexOf("/")==-1 && num.indexOf(".")==-1 && num.indexOf("EU")==-1) {
		var retval=a(safetyText,type+"simple",parseInt(num)-1);
		if (retval!="") {
			return retval;
		}
	}
	return defBlank(a(safetyText,type+"complex",num));
}

function getSafetyText(type,num) { // does additional cleaning
	if (num==undefined || num=="") {
		return "";
	}
	
	// starts with EUH?
	if (startswith(num,"euh")) {
		
	}
	else if (num.indexOf("+")!=-1) {
		// compound GHS statement, may need cleaning
		num=num.split("+");
		for (var b=0,max=num.length;b<max;b++) {
			num[b]=trim(num[b]);
			if (isNaN(parseInt(num[b].charAt(0)))) {
				// strip away 1st letter, if any
				num[b]=num[b].substr(1);
			}
			// trim again
			num[b]=trim(num[b]);
		}
		num=num.join("+");
	}
	
	var retval=getSingleSafetyText(type,num);
	if (retval=="") {
		// fall back to parts
		if (num.indexOf("+")!=-1) {
			num=num.split("+");
		}
		else if (num.indexOf("/")!=-1) {
			num=num.split("/");
		}
		else { // cannot split
			return retval;
		}
		for (var b=0,max=num.length;b<max;b++) {
			retval+=getSingleSafetyText(type,num[b])+" ";
		}
	}
	return retval;
}

function procClauses(type,data,removeDbl) {
	var arr=[],retval="";
	data=String(data);
	type=type.toUpperCase()
	
	// replace , and ; by -
	data=data.replace(/[,;]/gi,"-");
	
	if (data.indexOf("-")!=-1) {
		arr=data.split("-");
	}
	else {
		arr[0]=data;
	}
	
	// trim
	for (var b=0,max=arr.length;b<max;b++) {
		arr[b]=trim(arr[b]);
		// starts with EUH?
		if (startswith(arr[b],"euh")) {
			
		}
		else if (isNaN(parseInt(arr[b].charAt(0)))) {
			// strip away 1st letter, if any
			arr[b]=arr[b].substr(1);
		}
		// trim again
		arr[b]=trim(arr[b]);
	}
	
	if (removeDbl) {
		arr=array_unique(arr);
		arr.sort(Numsort);
	}
	
	for (var b=0,max=arr.length;b<max;b++) {
		if (arr[b]) {
			retval+=type+" "+arr[b]+": "+getSafetyText(type,arr[b])+"<br>";
		}
	}
	return retval;	
}

function getSchutzklasseKL(safety_sym,safety_r) {
	var arrR=safety_r.split("-"),retval=0;
	if (safety_sym.indexOf(",")>=0) {
		var arrSym=safety_sym.split(",");
	}
	else {
		var arrSym=safety_sym.split(" ");
	}
	// search for cancerogenic, etc
	for (var b=0,max=arrR.length;b<max;b++) {
		switch (arrR[b]) {
		case "39":
		case "40":
		case "45":
		case "46":
		case "49":
		case "60":
		case "61":
		case "62":
		case "63":
			return 4; // can't get higher
		break;
		case "29":
		case "31":
		case "32":
		case "23": // giftig
		case "24":
		case "25":
		case "26": // sehr giftig
		case "27":
		case "28":
			retval=3; // can still get higher
		break;
		}
	}
	if (retval==3 || in_array("T",arrSym) || in_array("T+",arrSym)) {
		return 3;
	}
	return 1;
}

// give recommendations for working instructions
var isoMandMap={M002:{p:["103","201","202"],s:["61"]},M004:{p:["280","282"],s:["39"]},M005:{s:["33"]},M009:{p:["280","282"],s:["37"]},M010:{p:["280"],s:["36"]},M013:{p:["280","282"],s:["39"]},M017:{p:["284"],s:["38","42"]},P003:{p:["210"]},P011:{r:["14","29"],h:["260","261"],p:["223"]}};
function getProtEquip(safety_s,safety_p,safety_h) {
	var retval=[],found;
	for (var mandAc in isoMandMap) {
		var pictos=isoMandMap[mandAc]["p"];
		found=false;
		if (pictos && safety_p) for (var b=0,bMax=pictos.length;b<bMax;b++) {
			if (safety_p.indexOf(pictos[b])!=-1) {
				// found
				found=true;
				break;
			}
		}
		pictos=isoMandMap[mandAc]["s"];
		if (!found && pictos && safety_s) for (var b=0,bMax=pictos.length;b<bMax;b++) {
			if (safety_s.indexOf(pictos[b])!=-1) {
				// found
				found=true;
				break;
			}
		}
		
		pictos=isoMandMap[mandAc]["h"];
		if (!found && pictos && safety_h) for (var b=0,bMax=pictos.length;b<bMax;b++) {
			if (safety_h.indexOf(pictos[b])!=-1) {
				// found
				found=true;
				break;
			}
		}
		
		if (found) {
			retval.push(mandAc);
		}
	}
	return retval.join(",");
}