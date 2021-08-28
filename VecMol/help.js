// (c) 2012-2019 Sciformation Consulting GmbH, all rights reserved

var lang=def(getParam("lang"),"en"),langs=["en","de"],mode=def(getParam("mode"),"mol"),isEmbedded=!!getParam("embedded");

loadLang();

$(window).load(function() {
	$('[lang]').hide();
	$('[lang='+fixStr(lang)+']').show();
	if (isEmbedded) {
		$('.notEmbedded').hide();
	}
	
	if (mode=="rxn") {
		$(".modeRxn").show();
	} else if (mode=="tmpl") {
		$(".modeTmpl").show();
	} else {
		$(".modeMol").show();
	}
	
	loadTooltips();
});