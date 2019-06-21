function updateVisibility(e) {
	var trg=e.target,name=trg.name,value=trg.value,id=trg.id;
	if (name=="lang") {
		$('[lang]').hide();
		$('[lang="'+value+'"]').show();
	} else {
		$('[class^="'+name+'_"],[class*=" '+name+'_"]').hide();
		$('.'+id).show();
	}
}