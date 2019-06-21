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

function $(id) {
	return document.getElementById(id);
}

function updateAllOptions(id,value) {
	var obj=$(id);
	if (obj) {
		for (var b=0,max=obj.childNodes.length;b<max;b++) {
			obj.childNodes[b].selected=value;
		}
	}
}

function selectAllOptions(id) {
	updateAllOptions(id,true);
}

function deselectAllOptions(id) {
	updateAllOptions(id,false);
}

function makeVisible(id) {
	var obj=$(id);
	if (obj) {
		obj.scrollIntoView(false);
	}
}

function getInnerHeight() {
	if (window.innerHeight) {
		return window.innerHeight;
	}
	else if (document.documentElement.clientHeight) { // IE strict
		return document.documentElement.clientHeight;
	}
	else { // IE quirks
		return document.body.clientHeight;
	}
}

function getInnerWidth() {
	if (window.innerHeight) {
		return window.innerWidth;
	}
	else if (document.documentElement.clientWidth) { // IE strict
		return document.documentElement.clientWidth;
	}
	else { // IE quirks
		return document.body.clientWidth;
	}
}

function getElementXY(obj,posCode) { // 0:links, 1: mitte, 2: rechts, 0: oben, 4: mitte, 8: unten, 16: limit top, 32: limit bottom, 64: limit left, 128: limit right
	var x=0,y=0,tagName="",debugText="";
	if (!obj) {
		return [];
	}
	//~ alert(obj.tagName);
	var w=obj.offsetWidth,h=obj.offsetHeight;
	// offsetLeft und offsetTop addieren bis offsetParent.tagName body ist
	do {
		x+=obj.offsetLeft-obj.scrollLeft;
		y+=obj.offsetTop-obj.scrollTop;
		//~ debugText+=tagName+": "+obj.offsetLeft+"x"+obj.offsetTop+"-"+obj.scrollLeft+"x"+obj.scrollTop;
		obj=obj.offsetParent;
		if (!obj) {
			break;
		}
		tagName=obj.tagName;
		if (!tagName) {
			break;
		}
		tagName=tagName.toLowerCase();
	} while (tagName!="body");
	posCode=def0(posCode);
	switch (posCode & 3) {
	case 1:
		x+=w/2;
	break;
	case 2:
		x+=w;
	break;
	}
	switch (posCode & 12) {
	case 4:
		y+=h/2;
	break;
	case 8:
		y+=h;
	break;
	}
	//~ alert(debugText);
	return [x,y];
}

function getElementSize(obj) {
	if (obj) {
		return [obj.offsetWidth,obj.offsetHeight];
	}
	return [];
}

function selectRadioButton(name,value) { // select radio button of the group buttons (same name) where value is value
	var rad_buttons=document.getElementsByName(name);
	for (var b=0,max=rad_buttons.length;b<max;b++) {
		if (rad_buttons[b].value==value) {
			rad_buttons[b].checked=true;
			return;
		}
	}
}

function unselectRadioButton(name) {
	var rad_buttons=document.getElementsByName(name);
	for (var b=0,max=rad_buttons.length;b<max;b++) {
		if (rad_buttons[b].checked) {
			// can be only one
			rad_buttons[b].checked=false;
			break;
		}
	}
}

function getSelectSelectedCount(id) { // get number of selected entries, for multiselect
	var obj=$(id),retval=0;
	if (obj) {
		for (var b=0,max=obj.childNodes.length;b<max;b++) {
			if (obj.childNodes[b].tagName) {
				var tagName=obj.childNodes[b].tagName.toLowerCase();
				if (tagName=="option" && obj.childNodes[b].selected) {
					retval++;
				}
			}
		}
	}
	return retval;
}

function getSelectSelectedText(id,deselect) {
	var obj=$(id);
	if (obj) {
		var idx=obj.selectedIndex;
		for (var b=0,max=obj.childNodes.length;b<max;b++) {
			if (obj.childNodes[b].tagName) {
				var tagName=obj.childNodes[b].tagName.toLowerCase();
				if (tagName=="option") {
					if (idx<=0) {
						if (deselect) {
							obj.selectedIndex=-1;
						}
						return obj.childNodes[b].innerHTML;
					}
					idx--;
				}
			}
		}
	}
}

function setSelectSelectedText(id,value,caseInsens) {
	var obj=$(id);
	if (caseInsens) {
		value=value.toLowerCase();
	}
	if (obj) {
		for (var b=0,max=obj.childNodes.length;b<max;b++) {
			if (obj.childNodes[b].tagName) {
				var tagName=obj.childNodes[b].tagName.toLowerCase();
				if (tagName=="option") {
					var test=obj.childNodes[b].innerHTML;
					if (caseInsens) {
						test=test.toLowerCase();
					}
					if (test==value) {
						obj.selectedIndex=b;
						return;
					}
				}
			}
		}
	}
}

function radioButtonValue(buttonName) { // returns the selected value of a group of radio buttons
	var buttons=document.getElementsByName(buttonName);
	for (var b=0,max=buttons.length;b<max;b++) {
		if (buttons[b].checked) {
			return buttons[b].value;
		}
	}
}

function setFrameURL(frameName,url) {
	var obj=window.frames[frameName];
	if (obj) {
		obj.location.href=url;
		return;
	}
	var obj=top.frames[frameName];
	if (obj) {
		obj.location.href=url;
	}
}

function setFrameHash(frameName,hash) {
	var obj=window.frames[frameName];
	if (obj) {
		obj.location.hash=hash;
	}
}

function touchOnChange(id) {
	var obj=$(id);
	if (obj) {
		if (is_function(obj.onchange)) {
			obj.onchange.call();
		}
	}
}

function removeId(id) {
	var obj=$(id);
	if (obj) {
		obj.parentNode.removeChild(obj);
	}
}

function setImgSrc(id,src,useSvg) {
	var obj=$(id);
	if (obj) {
		if (useSvg) {
			obj.data=src;
		}
		else {
			obj.src=src;
		}
	}
}

function setLinkHref(id,href) {
	var obj=$(id);
	if (obj) {
		obj.href=href;
	}
}

function setiHTML(id,iHTML) {
	var obj=$(id);
	if (obj) {
		obj.innerHTML=iHTML;
	}
}

function getiHTML(id) {
	var obj=$(id);
	if (obj) {
		return obj.innerHTML;
	}
	return "";
}

function addiHTML(id,iHTML) {
	var obj=$(id);
	if (obj) {
		obj.innerHTML+=iHTML;
	}
}

function lockedObj(id,locked) {
	var obj=$(id);
	if (obj) {
		obj.disabled=(locked?"disabled":"");
	}
}

function lockObj(id) {
	lockedObj(id,true);
}

function unlockObj(id) {
	lockedObj(id,false);
}

function getChecked(id) {
	var obj=$(id);
	if (obj) {
		return obj.checked;
	}
}

function setChecked(id,checked) {
	var obj=$(id);
	if (obj) {
		obj.checked=checked;
	}
}

function uncheckObj(id) {
	setChecked(id,false);
}

function checkObj(id) {
	setChecked(id,true);
}

function showObj(id) {
	handleVisibleObj(id,true);
}

function hideObj(id) {
	handleVisibleObj(id,false);
}

function setObjClass(id,className) {
	var obj=$(id);
	if (obj) {
		obj.className=className;
	}

}

function visibleObj(obj,visible) {
	if (visible==undefined) {
		return toggleVisible(obj);
	}
	else {
		handleVisibleObj(obj,visible);
		return visible;
	}
}

function handleVisibleObj(obj,visible) {
	switch (typeof obj) {
	case "string":
		obj=$(obj);
	break;
	case "object":
	
	break;
	default:
		return;
	}
	if (obj) {
		obj.style.display=(visible?"":"none");
	}
}

function toggleVisible(obj) {
	switch (typeof obj) {
	case "string":
		obj=$(obj);
	break;
	case "object":
	
	break;
	default:
		return;
	}
	if (obj) {
		var setVisible=(obj.style.display=="none");
		handleVisibleObj(obj,setVisible);
		return setVisible;
	}
}

function positionObj(id,x,y) {
	var obj=$(id);
	if (obj) {
		obj.style.left=x;
		obj.style.top=y;
	}
}

function positionObjPx(id,x,y) {
	x=parseFloat(x);
	y=parseFloat(y);
	if (!isNaN(x) && !isNaN(y)) {
		positionObj(id,x+"px",y+"px");
	}
}

function submitForm(id) {
	var obj=$(id);
	if (obj) {
		obj.submit();
	}
}

function focusInput(id) {
	var obj=$(id);
	if (obj) {
		obj.focus();
	}
}

function setInputValue(id,val) {
	var obj=$(id);
	if (obj) {
		obj.value=val;
	}
}

function clearInput(id) {
	setInputValue(id,"");
	touchOnChange(id);
}

function setSelectValue(id,val) {
	var obj=$(id);
	if (obj) for (var b=0,max=obj.childNodes.length;b<max;b++) {
		if (obj.childNodes[b].value==val) {
			obj.childNodes[b].selected=true;
			break;
		}
	}
}

function getInputValue(id,defaultValue) {
	var obj=$(id);
	if (obj) {
		return obj.value;
	}
	return defaultValue;
}

function getSelectedIndex(id) {
	var obj=$(id);
	if (obj) {
		return obj.selectedIndex;
	}
}

function setSelectedIndex(id,idx,touch) {
	var obj=$(id);
	if (obj) {
		obj.selectedIndex=idx;
		if (touch==true && is_function(obj.onchange)) {
			obj.onchange.call();
		}
	}
}

function selAddOption(obj,value,text,selected,tooltip) {
	if (!obj) {
		return;
	}
	if (!obj.options) {
		return;
	}
	/* works for FF, but IE sh*t as usual
	var opt=document.createElement("option"),textNode=document.createTextNode(text);
	opt.setAttribute("value",value);
	if (selected) {
		opt.setAttribute("selected","selected");
	}
	opt.appendChild(textNode);
	obj.appendChild(opt); */
	opt=new Option(text,value,false,selected);
	if (tooltip) {
		opt.title=tooltip;
	}
	obj.options[obj.options.length]=opt;
}

function cloneObject(obj) {
	var retval={};
	for (var child in obj) {
		if (typeof obj[child]=="object") {
			retval[child]=cloneObject(obj[child]);
		}
		else {
			retval[child]=obj[child];
		}
	}
	return retval;
}

function getEvtSrc(e) {
	if (window.event) {
		return window.event.srcElement;
	}
	else {
		return e.target;
	}
}

function getKey(e) {
	if (window.event) {
		return window.event.keyCode;
	}
	else if (e) {
		return e.keyCode;
	}
}

function cancelEvent(e) {
	if (isMSIE8orBelow) {
		if (!e) e=window.event;
		e.returnValue=false;
		e.cancelBubble=true;
	} else {
		e.preventDefault();
		e.stopPropagation();
	}
	return false;
}

function getSelectText(int_names,texts,thisValue) { // to set text for readOnly <select> which is displayed as <span>
	if (int_names!=undefined) for (var b=0,max=int_names.length;b<max;b++) {
		if (int_names[b]==thisValue) {
			if (texts[b]!=undefined) {
				return texts[b];
			}
			else {
				return int_names[b];
			}
		}
	}
	return "";
}

function clearChildElementsForObj(obj) {
	if (!obj) {
		return;
	}
	while (child=obj.firstChild) {
		obj.removeChild(child);
	}
}

function clearChildElements(id) { // removal of DOM child nodes
	var obj=$(id),child;
	clearChildElementsForObj(obj);
}

function delElement(id) { // kill DOM elements by id
	var obj=$(id);
	if (obj) {
		obj.parentNode.removeChild(obj);
	}
}


// overlay stuff
var overlayTimeout=[];

function cancelOverlayTimeout(idx) {
	window.clearTimeout(overlayTimeout[def0(idx)]);
}

function hideOverlayId(id,timeout) {
	if (timeout==0) {
		hideObj(id);
		return;
	}
	if (timeout==undefined) {
		timeout=100;
	}
	overlayTimeout[0]=window.setTimeout("hideObj("+fixStr(id)+");",timeout);
}

function showOverlay(obj,iHTML,xOffset,yOffset,relFlags) { // benötigt <div id="overlay" style="position:absolute"></div>
	scaled_obj=null;
	if (!overlay_obj) { // not avail at load time
		overlay_obj=$("overlay");
	}
	overlay_obj.innerHTML=iHTML;
	showOverlayObj(obj,overlay_obj,xOffset,yOffset,relFlags);
}

function showBottomOverlay(obj,iHTML,xOffset,yOffset) { // benötigt <div id="overlay" style="position:absolute"></div>
	showOverlay(obj,iHTML,xOffset,yOffset,8); // 0:links, 1: mitte, 2: rechts, 0: oben, 4: mitte, 8: unten
}

function hideOverlay(timeout) {
	hideOverlayId("overlay",timeout);
}

function showOverlayObj(obj,overlay_obj,xOffset,yOffset,posCode) { // 0:links, 1: mitte, 2: rechts, 0: oben, 4: mitte, 8: unten, 16: limit top, 32: limit bottom, 64: limit left, 128: limit right
	posCode=def0(posCode);
	var coords=getElementXY(obj,posCode);
	
	if (!coords || !overlay_obj) {
		return;
	}
	
	xOffset=def0(xOffset);
	yOffset=def0(yOffset);
	switch (posCode & 48) {
	case 16:
		coords[1]=Math.max(coords[1],document.documentElement.scrollTop);
	break;
	case 32:
		var h=overlay_obj.offsetHeight;
		coords[1]=Math.min(coords[1]+h,document.documentElement.scrollTop+getInnerHeight())-h;
	break;
	}
	switch (posCode & 192) {
	case 64:
		coords[0]=Math.max(coords[0],document.documentElement.scrollLeft);
	break;
	case 128:
		var w=overlay_obj.offsetWidth;
		coords[0]=Math.min(coords[0]+w,document.documentElement.scrollLeft+getInnerWidth())-w;
	break;
	}
	cancelOverlayTimeout();
	overlay_obj.style.left=(coords[0]+xOffset)+"px";
	overlay_obj.style.top=(coords[1]+yOffset)+"px";
	overlay_obj.style.clip=clip_auto;
	overlay_obj.style.display="";
}

function showOverlayId(obj,overlayId,xOffset,yOffset,posCode) { // 0:links, 1: mitte, 2: rechts, 0: oben, 4: mitte, 8: unten, 16: limit top, 32: limit bottom, 64: limit left, 128: limit right
	showOverlayObj(obj,$(overlayId),xOffset,yOffset,posCode);
}

// Vorbreitung: darunterliegendes Objekt mit (skalierter) Größe, unskalierte Größe des Overlay-Objekts (immer nur eine derartige Funktion gleichzeitig aktiv!!)
var scaled_obj,scaled_obj_size,overlay_obj,overlay_obj_size,ratioX,ratioY,default_scroll_obj,clip_auto="auto";

function scrollDefaultObj(delta) {
	if (default_scroll_obj && delta) {
		default_scroll_obj.scrollTop+=delta*40;
		hideOverlay();
	}
}

function redirWheel(e) { // redir to scaled_obj
	if (default_scroll_obj) {
		var delta=0;
		
		// get delta
		if (!e) {
			e=window.event;
		}
		if (e.wheelDelta) {
			delta=e.wheelDelta*(isOpera?1:-1)/120;
		}
		else if (e.detail) {
			 delta=e.detail/3;
		}
		//~ alert(delta);
		
		scrollDefaultObj(delta);
	}
}

function setDefaultScrollObj(id) {
	default_scroll_obj=$(id);
	if (!overlay_obj) { // not avail at load time
		overlay_obj=$("overlay");
	}
	if (!overlay_obj) {
		return;
	}
	
	if (overlay_obj.addEventListener) {
		overlay_obj.addEventListener("DOMMouseScroll", redirWheel, false);
	}
	overlay_obj.onmousewheel=redirWheel;
}

function prepareOverlay(new_scaled_obj,iHTML) {
	scaled_obj=new_scaled_obj;
	if (!overlay_obj) { // not avail at load time
		overlay_obj=$("overlay");
	}
	if (!overlay_obj) {
		return;
	}
	overlay_obj.innerHTML=iHTML;
	overlay_obj.style.display="";

	overlay_obj_size=getElementSize(overlay_obj); // unskalierte größe
	scaled_obj_size=getElementSize(scaled_obj); // skalierte größe
	ratioX=overlay_obj_size[0]/scaled_obj_size[0];
	ratioY=overlay_obj_size[1]/scaled_obj_size[1];
}

function alignOverlay(e,flags,r_w,r_h) { // event, flags (x=256, y=512), clip_width,clip_height, keep lower flags free for alignment options
	if (!scaled_obj || !overlay_obj_size || !scaled_obj_size) { // bugfix for IE-crap
		return;
	}
	
	var pageX,pageY,layerX,layerY,newX,newY;
	var coords=getElementXY(scaled_obj); // Position obere linke Ecke des skalierten Bildes
	if (window.event) { // IE
		pageX=window.event.clientX+document.documentElement.scrollLeft;
		pageY=window.event.clientY+document.documentElement.scrollTop;
	}
	else {
		pageX=e.pageX;
		pageY=e.pageY;
	}
	layerX=pageX-coords[0];
	layerY=pageY-coords[1];
	
	if (layerX>=Math.max(overlay_obj_size[0],scaled_obj_size[0]) || layerY>=Math.max(overlay_obj_size[1],scaled_obj_size[1]) || ((flags & 1024) && layerX>=scaled_obj_size[0]) || ((flags & 2048) && layerY>=scaled_obj_size[1])) {
		hideOverlay();
		return;
	}
	
	cancelOverlayTimeout();
	newX=coords[0];
	switch (flags & 259) {
	case 1:
		newX+=(scaled_obj_size[0]-overlay_obj_size[0])/2;
	break;
	case 2:
		newX+=scaled_obj_size[0]-overlay_obj_size[0];
	break;
	case 256:
		newX+=(1-ratioX)*layerX;
	break;
	}
	switch (flags & 192) { // limiting
	case 64:
		newX=Math.max(coords[0],document.documentElement.scrollLeft);
	break;
	case 128:
		newX=Math.min(newX+scaled_obj_size[0],document.documentElement.scrollLeft+getInnerWidth())-scaled_obj_size[0];
	break;
	}
	
	newY=coords[1];
	switch (flags & 524) {
	case 4: // middle
		newY+=(scaled_obj_size[1]-overlay_obj_size[1])/2;
	break;
	case 8: // bottom
		newY+=scaled_obj_size[1]-overlay_obj_size[1];
	break;
	case 512:
		newY+=(1-ratioY)*layerY;
	break;
	}
	switch (flags & 48) { // limiting
	case 16:
		newY=Math.max(coords[1],document.documentElement.scrollTop);
	break;
	case 32:
		newY=Math.min(newY+scaled_obj_size[1],document.documentElement.scrollTop+getInnerHeight())-scaled_obj_size[1];
	break;
	}
	
	overlay_obj.style.left=newX+"px";
	overlay_obj.style.top=newY+"px";
	overlay_obj.style.display=""; // in case of fast mouse movement
	//~ showMessage(layerX+"/"+layerY+" "+coords[0]+"/"+coords[1]+" "+newX+"/"+newY);
	if (r_w && r_h) {
		overlay_obj.style.clip="rect("+(layerY*ratioY-r_h)+"px "+(layerX*ratioX+r_w)+"px "+(layerY*ratioY+r_h)+"px "+(layerX*ratioX-r_w)+"px)"; // no commas for IE7-crap
	}
	else {
		overlay_obj.style.clip=clip_auto;
	}
}

function adjustElementSize(master,slave,flags,delta_x,delta_y,add_width,add_height) { // left: 4096, top: 8192, width: 16384, height: 32768
	if (master && slave) {
		if (flags & (4096+8192)) { // get master pos
			var master_coords=getElementXY(master,flags);
			if (flags & 4096) {
				slave.style.left=(master_coords[0]+def0(delta_x))+"px";
			}
			if (flags & 8192) {
				slave.style.top=(master_coords[1]+def0(delta_y))+"px";
			}
		}
		if (flags & (16384+32768)) { // get master pos
			var master_size=getElementSize(master);
			if (flags & 16384) {
				slave.style.minWidth=(master_size[0]+def0(add_width))+"px";
			}
			if (flags & 32768) {
				slave.style.minHeight=(master_size[1]+def0(add_height))+"px";
			}
		}
	}
}