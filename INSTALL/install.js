function updateVisibility(e) {
	var trg=e.target,name=trg.name,value=trg.value,id=trg.id;
	if (name=="lang") {
		$('[lang]').hide();
		$('[lang="'+value+'"]').show();
	} else {
		// startswith or contains, with space prefix
		// _ in front reverses
		$('[class^="'+name+'_"],[class*=" '+name+'_"]').hide();
		$('[class^="_'+name+'_"],[class*=" _'+name+'_"]').show();
		$('.'+id).show();
		$('._'+id).hide();
	}
}