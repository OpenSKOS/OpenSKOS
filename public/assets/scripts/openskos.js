/**
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2011 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Mark Lindeman
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

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
				a.set('href', BASE_URL + '/api/concept/' + concept.uuid + '.html');
				a.removeClass('loading');
				var a2 = new Element('a', {
					href:  concept.uri,
					'rel': 'external'
				}).appendText(concept.uri);
				new ExternalLink(a2);
				
				var el = new Element('span')
					.grab(new Element('br'))
					.appendText('(')
					.grab(a2)
					.appendText(')')
					.inject(a.getParent());
				
		});
		
		concept.addEvent('error', function (errorCode) {
			a.removeClass('loading');
		});
		
		concept.load('uuid,uri,prefLabel,class,dc_title');
	});
	
	$$('a[rel=external]').each(function(a) {
		new ExternalLink(a);
	});
});

var ExternalLink = new Class({
	initialize: function (a) {
		a.addEvent('click', function(ev) {
			new Event(ev).stop();
			window.open(this.href);
		});
	}
});

var CheckAllBoxes = new Class({
	initialize: function(element, elements)
	{
		element.addEvent('change', function(){
			elements.each(function(el) {
				el.set('checked', element.get('checked'));
			});
		}.bind(this));
	}
});

var SkosConcept = new Class({
	
	Implements: Events,
	
	initialize: function(id) {
		this.id = id;
	},
	
	load: function(fields)
	{
		if (!fields) fields='*';
		var url = BASE_URL + '/api/find-concepts';
		var self=this;
		new Request.JSON({
			url: url,
			onFailure: function(xhr)
			{
				switch (xhr.status) {
					case 404:
						self.fireEvent('error', [404]);
						break;
					default:
						alert('A XHR error has occurred (HTTP status '+xhr.status+'), please look at your console for the problem');
						break;
				}
			},
			onSuccess: function(response) {
				self = Object.merge(self, response);
				self.fireEvent('complete', []);
			}
		}).get('format=json&fl='+fields+'&id=' + this.id);
	},
	
	getTitle: function()
	{
		if (this.prefLabel) return this.prefLabel[0];
		if (this.dc_title) return this.dc_title[0];
	}
	
});