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
 * @copyright  Copyright (c) 2012 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

var EditorConceptsSelection = new Class({
	Binds: ['addReady', 'loadReady', 'clearReady', 'remove', 'removeReady'],
	controllerUrl: BASE_URL + '/editor/concepts-selection/',
	selectionContainer: null,
	initialize: function (selectionContainer) {		
		$(document.body).addEvent('click:relay(.action-clear-selection)', function (e) {
			e.stop();
			Editor.ConceptsSelection.clear();
		});
		
		$(document.body).addEvent("click:relay(.add-to-selection)", function (e) {
			e.stop();
			Editor.ConceptsSelection.add(new Element(e.target).getParent('.concept-link').getElement('.uuid').get('text')); 
		});
		
		this.selectionContainer = selectionContainer;
		this.load();
	},
	add: function (uuid) {
		var uuids = new Array();
		uuids.push(uuid);
		this.addMultiple(uuids);
	},
	addMultiple: function (uuids) {
		new Request.JSON({url: this.controllerUrl + 'add', data: {'uuids': uuids}, onSuccess: this.addReady}).send();
	},
	addReady: function (response) {
		if (response.status == 'ok') {
			this.refreshList(response.result);
		} else if (response.status == 'limitReached') {
			this.limitReached(response.limit);
		}
	},
	load: function (response) {
		new Request.JSON({url: this.controllerUrl + 'get-all', onSuccess: this.loadReady}).send();
	},
	loadReady: function (response) {
		this.refreshList(response.result);
	},
	limitReached: function (limit) {
		alert('You have reached the limit of ' + limit + ' selections.')
	},
	clear: function (uuid) {
		new Request.JSON({url: this.controllerUrl + 'clear', onSuccess: this.clearReady}).send();
	},	
	clearReady: function (response) {
		if ($('selection-count')) {
			$('selection-count').set('text', 0);
		}
		Editor.View.emptyContainer($('selection-list'), '.concept-link');
	},
	remove: function (uuid) {
		new Request.JSON({url: this.controllerUrl + 'remove', data: {'uuid': uuid}, onSuccess: this.removeReady}).send();
	},	
	removeReady: function (response) {
		this.refreshList(response.result);
	},
	refreshList: function (concepts) {
		var self = this;
		
		if ($('selection-count')) {
			$('selection-count').set('text', concepts.length);
		}
		
		Editor.View.emptyContainer(this.selectionContainer, '.concept-link');
		
		for (var i = 0; i < concepts.length; i++) {
			var item = Editor.View.renderConceptLink(concepts[i], this.selectionContainer);
			item.getElement('.uuid').set('text', concepts[i].uuid);
			item.getElement('.remove-link').addEvent('click', function (ev) {
				ev.stop(); 
				self.remove(new Element(ev.target).getParent('.concept-link').getElement('.uuid').get('text'));
			});
			item.inject(this.selectionContainer);
		}
	},
	getUuids: function () {
		var items = this.selectionContainer.getElements('.concept-link:not(.template)');
		var uuids = new Array();
		for (i = 0; i < items.length; i ++) {
			uuids.push(items[i].getElement('.uuid').get('text'));
		}
		return uuids;
	}
});