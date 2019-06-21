// (c) 2012-2019 Sciformation Consulting GmbH, all rights reserved

function s(key) {
	return defBlank(langDef[key]);
}

function loadLang() {
	if ($.inArray(lang,langs)>=0) {
		document.write("<script type=\"text/javascript\" src=\"lang_"+lang+".js\"></sc"+"ript>");
	}
}

function loadTooltips() {
	// add tooltips to buttons
	$(".button, .wTooltip").attr("title",function () { return s(this.id); }).on("dragstart",cancelEvent);
}