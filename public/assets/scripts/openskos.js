window.addEvent('load', function(){
	$$('.resolvable-concept').each(function(a){
		var uri = a.get('href');
		a.addClass('loading');
		var concept = new SkosConcept(a.get('text'));
		concept.addEvent('complete', function () {
			var title = concept.getTitle();
			if (title) {
				a.set('title', a.get('text'))
					.set('text', concept.getTitle());
				}
				a.set('href', '/api/concept/' + concept.uuid);
				a.removeClass('loading');
		});
		concept.load('uuid,uri,prefLabel,class,dc_title');
	});
});

var SkosConcept = new Class({
	
	Implements: Events,
	
	initialize: function(id) {
		this.id = id;
	},
	
	load: function(fields)
	{
		if (!fields) fields='*';
		var url = '/api/findConcepts';
		var self=this;
		new Request.JSON({
			url: url,
			onFailure: function(xhr)
			{
				alert('A XHR error has occurred, plead look at your console for the problem');
				try {
					console.log(xhr);
				} catch (e) {}
			},
			onSuccess: function(response) {
				console.log(response);
				self = Object.merge(self, response);
				self.fireEvent('complete', []);
			}
		}).get('fl='+fields+'&id=' + this.id);
	},
	
	getTitle: function()
	{
		if (this.prefLabel) return this.prefLabel[0];
		if (this.dc_title) return this.dc_title[0];
	}
	
});