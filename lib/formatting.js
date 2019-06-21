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
function sp(text,n) {
	return leftPad(text,n," ");
}

function leftPad(text,n,filler) {
	text=String(text);
	if (text.length>n) {
		return text.substr(0,n); // cut away on right side
	}
	var len=text.length;
	while (len<n) { // space-fill on left side
		len++;
		text=filler+text;
	}
	return text;
}

function getHTMLAttrib(name,val) {
	if (empty(val)) {
		return "";
	}
	return " "+name+"="+fixStr(val);
}

function getFormattedAdress(values,prefix) {
	prefix=defBlank(prefix);
	return ifnotempty("",values[prefix+"institution_name"],"<br>")+ifnotempty("",values[prefix+"department_name"],"<br>")+ifnotempty("",values[prefix+"person_name"],"<br>")+defBlank(values[prefix+"street"])+" "+defBlank(values[prefix+"street_number"])+"<br>"+defBlank(values[prefix+"postcode"])+" "+defBlank(values[prefix+"city"])+ifnotempty("<br>",values[prefix+"country"]);
}

function def(val,defaultVal) {
	if (val==undefined || val==null || (isNaN(val) && typeof val=="number")) {
		return defaultVal;
	}
	return val;
}

function def0(val) {
	return def(val,0);
}

function def1(val) {
	return def(val,1);
}

function yieldFmt(yield) {
	if (yield!=="") {
		return round(yield,yield_digits,yield_mode)+"%";
	}
	return "-";
}

function fixPurity(val) {
	if (val>=0 && val<=1) {
		return val;
	}
	return 1;
}

function defArr(val) {
	if (is_array(val)) {
		return val;
	}
	var retval=[];
	if (is_string(val)) {
		retval[0]=val;
	}
	return retval;
}

function defFalse(val) {
	return def(val,false);
}

function defTrue(val) {
	return def(val,true);
}

function defBlank(val) {
	return def(val,"");
}

function empty(val) {
	return defBlank(val)=="";
}

function defNbsp(val) {
	return def(val,"&nbsp;");
}

function getNumber(text) {
	text=String(text);
	var match=text.match(/\-?\d+[\.,]?\d*/);
	if (match!=null && match.length>0) {
		return defBlank(match[0]);
	}
	return "";
}

function softLineBreaks(text,limit) {
	if (limit>0) {
		var retval="";
		retval+=text.substr(0,limit);
		text=text.substr(limit);
		while (text.length>0) {
			retval+="<wbr>"+text.substr(0,limit);
			text=text.substr(limit);
		}
		return retval;
	}
	return text;
}

function startswith(haystack,needle,caseSensitive) {
	var start=haystack.substr(0,needle.length);
	if (caseSensitive!=true) {
		start=start.toLowerCase();
		needle=needle.toLowerCase();
	}
	return (start==needle);
}

function endswith(haystack,needle,caseSensitive) {
	var pos=haystack.length-needle.length;
	if (pos<0) {
		return false;
	}
	var end=haystack.substr(pos);
	if (caseSensitive!=true) {
		end=end.toLowerCase();
		needle=needle.toLowerCase();
	}
	return (end==needle);
}

function cleanHTML(text) {
	text=text.replace(/ _moz_dirty=\"\"/gi,"");
	return text;
}

function nl2br(text,clean) {
	if (text==undefined) {
		return "";
	}
	text=String(text);
	if (clean) {
		text=cleanHTML(text);
		text=text.replace(/[\r\n]+$/g,"");
		//~ text=text.replace(/<br[^>]*><\\?\/span>/gi,"</span>");
		text=text.replace(/(<br[^>]*>)+$/gi,"");
	}
	text=text.replace(/\r\n/g,"<br>");
	text=text.replace(/\n/g,"<br>");
	text=text.replace(/\r/g,"<br>");
	return text;
}

function fixNL(text) {
	if (!text) {
		return "";
	}
	text=String(text);
	text=text.replace(/ _moz_dirty=\"\"/gi,"");
	text=text.replace(/[\n\r]*(<br[^>]*>)[\n\r]*/gi,"<br>"); // obsolete
	//~ text=text.replace(/[\n\r]+/g," ");
	text=text.replace(/(<br[^>]*>)+$/gi,"");
	return text;
}

function multStr(str,num) {
	num=parseInt(num);
	if (isNaN(num) || num<1) {
		return "";
	}
	var retStr="";
	str=String(str);
	for (var a=0;a<num;a++) {
		retStr+=str;
	}
	return retStr;
}

function fillZero(number,digits) {
	if (digits==undefined) {
		digits=2;
	}
	var retval=multStr("0",digits)+number;
	return retval.substr(retval.length-digits); // nicht byRef wie im PHP
}

function constrainVal(value,bound1,bound2) {
	return Math.max(bound1,Math.min(bound2,value));
}

function round(number,digits,mode,inputField) { // mode = 0 - Runden, 1 - sci, 2 - eng, 3 - zerofill, 4 - sign_digits
// inputField: write exponentials as 1234e567
	var neg=1;
	number=parseFloat(number);
	digits=parseInt(digits);
	if (isNaN(number) || number==undefined) {
		return "";
	}
	if (mode!=3) {
		if (number==0) {
			return 0;
		}
		if (number<0) {
			neg=-1;
			number*=-1;
		}
	}
	var zeros=Math.pow(10,digits);
	switch (mode) {
	case 1: // sci
		var base=Math.floor(Math.log(number)/Math.LN10);
	break;
	case 2: // eng
		var base=Math.floor(Math.log(number)/3/Math.LN10)*3;
	break;
	case 3: // zerofill
		return number.toFixed(constrainVal(digits,0,20)); // chrome fix
		// var retval=String(Math.round(number*zeros)/zeros);
	break;
	case 4: // significant
		// exponent der ersten sig stelle bestimmen
		//~ digits-=Math.ceil(Math.log(number)/Math.LN10);
		//~ digits=Math.max(0,digits);
		//~ return number.toFixed(digits);
		digits-=Math.ceil(Math.log(number)/Math.LN10);
		return neg*number.toFixed(constrainVal(digits,0,20)); // chrome fix
		/* var signi=Math.pow(10,Math.floor(Math.log(number)/Math.LN10)+1);
		var retval=String(Math.round(number/signi*zeros)/zeros*signi); */
	break;
	default: // normal round, default
		return (neg*Math.round(number*zeros)/zeros);
	}
	
	switch (mode) {
	case 1: // sci
	case 2: // eng
		var retval=""+(neg*Math.round(number/Math.pow(10,base)*zeros)/zeros);
		if (base==0) {
			return retval;
		}
		if (inputField) {
			return retval+"e"+base;
		}
		return retval+"&sdot;10<sup>"+base+"</sup>";
	}
}

function addslashes(text) { // do not escape '
	text=String(text);
	return text.replace(/(["\\])/g,"\\$1");
}

function fixStr(text) {
	return "\""+addslashes(text)+"\"";
}

function fixQuot(text) {
	// return "\""+text.replace(/(["'\\])/g,"\\$1")+"\"";
	return "&quot;"+addslashes(text)+"&quot;";
}

function fixNull(variable) {
	if (variable==null || variable==undefined || variable=="") {
		return 0;
	}
	if (variable.indexOf(",")!=-1 && variable.indexOf(".")==-1) { // german style
		variable=variable.replace(/,/,".");
	}
	if (variable.indexOf("%")!=-1) { // percent
		variable=variable.replace(/%/,"/100");
		variable=eval(variable);
	}
	return parseFloat(variable);
}

function fixNbsp(text) {
	if (text=="") {
		return "&nbsp;";
	}
	return text;
}

function isCAS(text) {
	var cas_fmt=/^\d+-\d{2}-\d$/;
	return cas_fmt.test(trim(text));
}

function ifnotempty(pre,text,post,pDefault) { // returns pre+text+post if text is not empty, otherwise pDefault
	if (text+""=="" || text==undefined || text==null) {
		return defBlank(pDefault);
	}
	post=defBlank(post);
	return pre+text+post;
}

function addPipes(text) { // replace cr/lf by | for JME
	if (text==undefined || text=="") {
		return "";
	}
	text=String(text);
	text=text.replace(/\r\n/g,"|");
	text=text.replace(/\n/g,"|");
	text=text.replace(/\r/g,"|");
	return text;
}

function removePipes(text) { // remove | and make proper molfile
	if (text==undefined || text=="") {
		return "";
	}
	text=String(text);
	var replaceText="\n";
	text=text.replace(/:/g,"."); // Bugfix for Marvin on Safari
	text=text.replace(/\r\n/g,replaceText);
	text=text.replace(/\r/g,replaceText);
	text=text.replace(/\|/g,replaceText);
	return text;
}

function numToLett(num) { // give number as letter code (0 => A, 26 => AA)
	num=parseInt(num);
	var digit,retval="";
	do {
		digit=num%26;
		num-=digit;
		num/=26;
		retval=String.fromCharCode(digit+64)+retval;
	} while (num>0);
	return retval;
}

function fixId(num) {
	if (num=="-1") {
		return "_1";
	}
	return num;
}

function today() {
	return getGerDate(getDeltaDate(Number(new Date()) ) );
}

function getGerDate(dateObj) {
	return dateObj.getDate()+"."+(1+dateObj.getMonth())+"."+dateObj.getFullYear();
}

function getDeltaDate(timestamp,deltaDays) {
	var day_msec=24*60*60*1000;
	return new Date(timestamp+def0(deltaDays)*day_msec);
}

function toSQLDate(gerDate) {
	if (!gerDate) {
		return invalidSQLDate;
	}
	var gerDateFmt=/^(\d\d?)\.(\d\d?)\.(\d{2,4})$/,found=0; // TT.MM.JJ oder TT.MM.JJJJ
	if (gerDateFmt.exec(gerDate)) {
		var year=parseInt(Number(RegExp.$3)),month=RegExp.$2,day=RegExp.$1;
		found=1;
	}
	if (found==0) {
		var gerDateTimeFmt1=/^(\d\d?)\.(\d\d?)\.(\d{2,4}) (\d\d?)[\.:](\d\d)[\.:](\d\d)$/;
		if (gerDateTimeFmt1.exec(gerDate)) {
			var year=parseInt(Number(RegExp.$3)),month=RegExp.$2,day=RegExp.$1,hour=RegExp.$4,minute=RegExp.$5,second=RegExp.$6;
			found=2;
		}
	}
	if (found==0) {
		var gerDateTimeFmt2=/^(\d\d?)\.(\d\d?)\.(\d{2,4}) (\d\d?)[\.:](\d\d)$/;
		if (gerDateTimeFmt2.exec(gerDate)) {
			var year=parseInt(Number(RegExp.$3)),month=RegExp.$2,day=RegExp.$1,hour=RegExp.$4,minute=RegExp.$5,second="00";
			found=2;
		}
	}
	if (found>0) {
		if (year<30) {
			year+=2000;
		}
		else if (year<100) {
			year+=1900;
		}
	}
	if (found==1) {
		return year+"-"+month+"-"+day;
	}
	if (found==2) {
		return year+"-"+month+"-"+day+" "+hour+":"+minute+":"+second;
	}
	return invalidSQLDate;
}

function toGerDate(SQLDate) {
	if (!SQLDate || SQLDate==invalidSQLDate || SQLDate==invalidSQLDateTime) {
		return "";
	}
	var SQLDateFmt=/^(\d{4})-(\d\d)-(\d\d)$/,SQLDateTimeFmt=/^(\d{4})-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)$/; // JJJJ-MM-TT
	if (SQLDateFmt.exec(SQLDate)) {
		return RegExp.$3+"."+RegExp.$2+"."+RegExp.$1;
	}
	else if (SQLDateTimeFmt.exec(SQLDate)) {
		return RegExp.$3+"."+RegExp.$2+"."+RegExp.$1+" "+RegExp.$4+":"+RegExp.$5+":"+RegExp.$6;
	}
	else {
		return "";
	}
}

function formatRange(low,high,unit) {
	var retval;
	low=parseFloat(low);
	high=parseFloat(high);
	unit=def(unit,"°C");
	if (isNaN(low) && isNaN(high)) {
		return "";
	}
	else if (isNaN(low)) {
		retval=high;
	}
	else if (isNaN(high)) {
		retval=low;
	}
	else {
		if (high<low) {
			var temp=high;
			high=low;
			low=temp;
		}
		if (low<0) {
			low="("+low+")";
		}
		if (high<0) {
			high="("+high+")";
		}
		retval=low+"-"+high;
	}
	return retval+ifnotempty("&nbsp;",unit);
}

function formatBoilingPoint(low,high,press,press_unit) {
	var unit="°C",temp_range=formatRange(low,high,unit);
	if (temp_range=="") {
		return "";
	}
	var press=parseFloat(press);
	if ((isNaN(press) || press==1) && (empty(press_unit) || press_unit=="bar")) {
		return temp_range;
	}
	else if (isNaN(press) || empty(press_unit)) {
		return "";
	}
	return temp_range+" ("+press+"&nbsp;"+press_unit+")";
}

function formatRangeForSQL(fromVal,toVal) {
	fromVal=parseFloat(fromVal);
	toVal=parseFloat(toVal);
	if (isNaN(fromVal)) {
		if (isNaN(toVal)) {
			return "";
		}
		// nur toVal numerisch
		return toVal;
	}
	// nur fromVal numerisch
	if (isNaN(toVal)) {
		return fromVal;
	}
	// a-,-a-,...
	if (toVal==Number.MAX_VALUE) {
		return fromVal+"-";
	}
	if (fromVal==-Number.MAX_VALUE) {
		return "-("+toVal+")";
	}
	if (toVal<0) {
		return "("+fromVal+")-("+toVal+")";
	}
	return fromVal+"-"+toVal;
}

function splitRange(text) {
	// mögliche Fälle:
	// 12-14
	// -2-4
	//-20--18
	//(-20)-(-18) und Kombinationen
	var expr=/\(?(\-?[\d\.]+)\)?[\-<>]\(?(\-?[\d\.]+)\)?/; // a-b,-a-b,-a-(-b)
	var val1=/\(?(\-?[\d\.]+)\)?\-/; // a-, -a-, (-a)-
	var val1b=/\(?(\-?[\d\.]+)\)?</; // a<, -a<, (-a)<
	var val1c=/>\(?(\-?[\d\.]+)\)?/; // >a, >a, >(-a)
	var val2=/\-\((\-?[\d\.]+)\)/; // -(b), -(-b) force brackets for negative
	var val2b=/<\(?(\-?[\d\.]+)\)?/; // <(b), <(-b), <-b
	var val2c=/\(?(\-?[\d\.]+)\)?>/; // (b)>, (-b)>, -b>
	retval=[];
	if (expr.exec(text)) { // a-b,-a-b,-a-(-b)
		retval[0]=parseFloat(RegExp.$1);
		retval[1]=parseFloat(RegExp.$2);
	}
	else if (val1.exec(text) || val1b.exec(text) || val1c.exec(text)) { // a-, -a-, (-a)-
		retval[0]=parseFloat(RegExp.$1);
		retval[1]=Number.MAX_VALUE;
	}
	else if (val2.exec(text) || val2b.exec(text) || val2c.exec(text)) { // -(b), -(-b)
		retval[0]=-Number.MAX_VALUE;
		retval[1]=parseFloat(RegExp.$1);
	}
	else { // einzelne Zahl
		retval[0]="";
		retval[1]=parseFloat(text);
		if (isNaN(retval[1])) {
			retval[1]="";
		}
	}
	if (retval[0]!="") {
		retval.sort(Numsort);
	}
	// ergänzen; a-, -(b)
	return retval;
}

function trim(text) {
	if (!text) {
		return text;
	}
	// reg exp
	var expr=/^[\s\n\r\t]*(.*?)[\s\n\r\t]*$/;
	if (expr.exec(text)) {
		return RegExp.$1;
	}
}

function splitDatasetRange(datasetRange,shift_down) {
	if (!datasetRange) {
		return; // all
	}
	if (shift_down==undefined) {
		shift_down=1;
	}
	datasetRange=String(datasetRange);
	datasetRange=datasetRange.replace(/;/g,",");
	var fragments=datasetRange.split(","),retval=[],maxDataset=dbIdx.length;
	for (var b=0,max=fragments.length;b<max;b++) {
		var range=splitRange(trim(fragments[b]));
		if (range[1]=="") {
			continue;
		}
		if (range[0]=="") {
			retval.push(range[1]-shift_down);
			continue;
		}
		for (var c=Math.max(1,Math.min(range[0],range[1])),max=Math.min(Math.max(range[0],range[1]),maxDataset);c<=max;c++) { // avoid endless loops from a-b where a>b
			retval.push(c-shift_down);
		}
	}
	return retval; // array, no join!
}

function isEmptyStr(str) {
	if (str+""=="" || str==undefined) {
		return true;
	}
}

function joinIfNotEmpty(strArray,delimiter) {
	if (delimiter==undefined) {
		delimiter=", ";
	}
	var retStr="";
	for (var b=0,max=strArray.length;b<max;b++) {
		if (isEmptyStr(retStr) && !isEmptyStr(strArray[b])) {
			retStr=strArray[b];
		}
		else if (!isEmptyStr(strArray[b])) {
			retStr+=delimiter+strArray[b];
		}
	}
	return retStr;
}

function ifempty(text,pDefault,valueifnotempty) {
	if (isEmptyStr(text)) {
		return pDefault;
	}
	if (valueifnotempty==undefined) {
		return text;
	}
	return valueifnotempty;
}

function formatPerson(values,prefix) {
	prefix=defBlank(prefix);
	return ifempty(joinIfNotEmpty([a(values,prefix+"last_name"),a(values,prefix+"first_name"),a(values,prefix+"title")]),a(values,prefix+"username"));
}

function formatPersonNameNatural(dataset,prefix) { // returns "nice" name, or username if empty
	prefix=defBlank(prefix);
	return ifempty(joinIfNotEmpty([dataset[prefix+"title"],dataset[prefix+"first_name"],dataset[prefix+"last_name"]]," "),dataset[prefix+"username"]);
}

function strcut(string,maxlen,endtext,border) {
	if (endtext==undefined) {
		endtext="...";
	}
	if (string==undefined || string==null) {
		string="";
	}
	var textlen=string.length;
	if (textlen<=maxlen) {
		return string;
	}
	maxlen-=endtext.length;
	var cutText=string.substr(0,maxlen);
	if (border==undefined) {
		return cutText+endtext;
	}
	
	var spcpos=cutText.lastIndexOf(border);
	if (spcpos==-1) {
		spcpos=maxlen;
	}
	return cutText.substr(0,spcpos)+endtext;
}

function strrcut(string,maxlen,endtext,border) { // reverse end
	if (endtext==undefined) {
		endtext="...";
	}
	if (string==undefined || string==null) {
		string="";
	}
	var textlen=string.length;
	if (textlen<=maxlen) {
		return string;
	}
	maxlen-=endtext.length;
	var cutText=string.substr(textlen-maxlen,maxlen);
	if (border==undefined) {
		return cutText;
	}
	var spcpos=cutText.indexOf(border);
	if (spcpos==-1) {
		spcpos=0;
	}
	return endtext+cutText.substr(spcpos);
}

function evalNum(num) {
	if (num==="") {
		return num;
	}
	num=num.replace(/[a-zA-Z]/g,""); // prevent function calls
	num=num.replace(/,/g,".");
	num=num.replace(/%/g,"/100");
	num=eval(num);
	return num;
}

function colSplit(string,colArray,bin) {
	if (!is_array(colArray)) {
		return string;
	}
	var retval=[],pos=0,col,value;
	for (var b=0,max=colArray.length;b<max;b++) {
		col=colArray[b];
		value=string.substr(pos,col);
		if (!bin) {
			value=trim(value);
		}
		retval.push(value);
		pos+=col;
	}
	if (string.length>col) {
		value=string.substr(pos);
		if (!bin) {
			value=trim(value);
		}
		retval.push(value);
	}
	return retval;
}
