// (c) 2012-2019 Sciformation Consulting GmbH, all rights reserved

// for minification only
prseInt=parseInt;

function cancelEvent(e) {
	if (isMSIE8orBelow) {
		if (!e) e=window.event;
		e.returnValue=F;
		e.cancelBubble=T;
	} else {
		e.preventDefault();
		e.stopPropagation();
	}
	return F;
}

function len(param) {
	return param.length;
}

function avg() {
	for (var sum=0,i=0,iMax=len(arguments);i<iMax;i++) {
		sum+=arguments[i];
	}
	return sum/iMax;
}

function ksort(arr) { // like in PHP
	var tempArr=[],keys=[],key,i,iMax;
	for (key in arr) {
		keys.push(key);
	}
	keys.sort();
	for (i=0,iMax=len(keys);i<iMax;i++) {
		key=keys[i];
		tempArr[key]=arr[key];
		delete arr[key];
	}
	for (i=0;i<iMax;i++) {
		key=keys[i];
		arr[key]=tempArr[key];
	}
}

function getKey(arr,value) {
	for (var key in arr) {
		if (arr[key]==value) return key;
	}
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

function colSplit(string,colArray,bin) {
	if (!$.isArray(colArray)) {
		return string;
	}
	var retval=[],pos=0,col,value,i,iMax;
	for (i=0,iMax=len(colArray);i<iMax;i++) {
		col=colArray[i];
		value=string.substr(pos,col);
		if (!bin) {
			value=trim(value);
		}
		retval.push(value);
		pos+=col;
	}
	if (len(string)>col) {
		value=string.substr(pos);
		if (!bin) {
			value=trim(value);
		}
		retval.push(value);
	}
	return retval;
}

function spaceSplit(string) {
	for (var retval=[],end,i=0,iMax=(string?len(string):0);i<iMax;i++) {
		if (string.charAt(i)==S) continue;
		end=string.indexOf(S,i+1);
		if (end==-1) {
			retval.push(string.substr(i));
			break;
		}
		retval.push(string.substring(i,end));
		i=end;
	}
	return retval;
}

function startswith(haystack,needle,caseSensitive) {
	var start=haystack.substr(0,len(needle));
	if (caseSensitive!=T) {
		start=start.toLowerCase();
		needle=needle.toLowerCase();
	}
	return (start==needle);
}

function endswith(haystack,needle,caseSensitive) {
	var pos=len(haystack)-len(needle),end;
	if (pos<0) {
		return F;
	}
	end=haystack.substr(pos);
	if (caseSensitive!=T) {
		end=end.toLowerCase();
		needle=needle.toLowerCase();
	}
	return (end==needle);
}

/*function strcut(string,maxlen,endtext,border) {
	if (endtext==UNDF) {
		endtext="...";
	}
	string=defBlank(string);
	var textlen=len(string),cutText,spcpos;
	if (textlen<=maxlen) {
		return string;
	}
	maxlen-=len(endtext);
	cutText=string.substr(0,maxlen);
	if (border==UNDF) {
		return cutText+endtext;
	}
	
	spcpos=cutText.lastIndexOf(border);
	if (spcpos==-1) {
		spcpos=maxlen;
	}
	return cutText.substr(0,spcpos)+endtext;
}

function strrcut(string,maxlen,endtext,border) { // reverse end
	if (endtext==UNDF) {
		endtext="...";
	}
	string=defBlank(string);
	var textlen=len(string),cutText,spcpos;
	if (textlen<=maxlen) {
		return string;
	}
	maxlen-=len(endtext);
	cutText=string.substr(textlen-maxlen,maxlen);
	if (border==UNDF) {
		return cutText;
	}
	spcpos=cutText.indexOf(border);
	if (spcpos==-1) {
		spcpos=0;
	}
	return endtext+cutText.substr(spcpos);
}*/

function removePipes(text) { // remove | and make proper molfile
	if (text==UNDF || text=="") {
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

function multStr(str,num) {
	num=prseInt(num);
	if (isNaN(num) || num<1) {
		return "";
	}
	str=String(str);
	for (var retStr="",a=0;a<num;a++) {
		retStr+=str;
	}
	return retStr;
}

function sp(text,n) {
	return leftPad(text,n,S);
}

function leftPad(text,n,filler) {
	text=String(text);
	if (len(text)>n) {
		return text.substr(0,n); // cut away on right side
	}
	var leng=len(text);
	while (leng<n) { // fill on left side
		leng++;
		text=filler+text;
	}
	return text;
}

function rightPad(text,n,filler) {
	text=String(text);
	if (len(text)>n) {
		return text.substr(0,n); // cut away on right side
	}
	var leng=len(text);
	while (leng<n) { // fill on right side
		leng++;
		text+=filler;
	}
	return text;
}

function addslashes(text) { // do not escape '
	text=String(text);
	return text.replace(/(["\\])/g,"\\$1");
}

function fixStr(text) {
	return "\""+addslashes(text)+"\"";
}

function def(val,defaultVal) {
	if (val==UNDF || val==null || (isNaN(val) && typeof val=="number")) {
		return defaultVal;
	}
	return val;
}

function def0(val) {
	return def(val,0);
}

function defBlank(val) {
	return def(val,"");
}

function getNumber(text) {
	text=String(text);
	var match=text.match(/\-?\d+[\.,]?\d*/);
	if (match!=null && len(match)>0) {
		return defBlank(match[0]);
	}
	return "";
}

function constrainVal(value,bound1,bound2) {
	return M.max(bound1,M.min(bound2,value));
}

function round(number,digits,mode) { // mode = 0 - Runden, 1 - sci, 2 - eng, 3 - zerofill, 4 - sign_digits
	var neg=1,base,zeros,retval;
	number=parseFloat(number);
	digits=prseInt(digits);
	if (isNaN(number) || number==UNDF) {
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
	zeros=M.pow(10,digits);
	switch (mode) {
	case 1: // sci
		base=M.floor(M.log(number)/M.LN10);
	break;
	case 2: // eng
		base=M.floor(M.log(number)/3/M.LN10)*3;
	break;
	case 3: // zerofill
		return number.toFixed(constrainVal(digits,0,20)); // chrome fix
	break;
	case 4: // significant
		// exponent der ersten sig stelle bestimmen
		digits-=M.ceil(M.log(number)/M.LN10);
		return neg*number.toFixed(constrainVal(digits,0,20)); // chrome fix
	break;
	default: // normal round, default
		return (neg*M.round(number*zeros)/zeros);
	}
	
	switch (mode) {
	case 1: // sci
	case 2: // eng
		retval=String(neg*M.round(number/M.pow(10,base)*zeros)/zeros);
		if (base==0) {
			return retval;
		}
		return retval+"&sdot;10<sup>"+base+"</sup>";
	}
}

function getParam(key) {
    var search=location.search,re,result;
    if (search && key) {
        re=new RegExp("[&?]" + key + "=([^&$]*)");
        if (result=re.exec(search)) {
            return result[1];
        }
    }
}

function getHash() {
	return hashId++;
}