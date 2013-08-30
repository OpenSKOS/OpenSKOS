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
 * @author     Boyan Bonev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

var EditorView = new Class({
	Binds: ['render', 
	        'renderConceptLink', 
	        'showHistory',
	        'emptyContainer',
	        'showSelections', 
	        'showConcept',
	        'showConceptForm',
	        'showActionModal',
	        'hideExportBox'],
	render: function (data, container, cb) {
		Object.each(data, function (concept) {
			container.adopt(cb(concept, container));
		});
	},
	renderConceptLink: function (conceptData, container) {
		var liEl = container.getElement('.concept-link.template').clone().removeClass('template');
		var headerEl = liEl.getElement('.concept-link-header');
		
		Object.each(conceptData.schemes, function (scheme) {
			if (scheme.iconPath != '') {
				var schemeHolder = new Element('img', {title: scheme.dcterms_title[0], alt: scheme.dcterms_title[0].substring(0, 2), src: scheme.iconPath});
			} else {
				var schemeHolder = new Element('span', {text: '(' + scheme.dcterms_title[0].substring(0, 2) + ')'});
			}
			schemeHolder.addClass(scheme.uuid);
			headerEl.adopt(schemeHolder);
		});
		
		var contentEl = liEl.getElement('.concept-link-content');
		contentEl.adopt(new Element('a', {'class': conceptData.uuid, href: '#', html: conceptData.previewLabel, title: conceptData.previewLabel + (conceptData.previewScopeNote ? ' {' + conceptData.previewScopeNote + '}' : '')}));	
		
		if (conceptData.status) {
			liEl.addClass('status-' + conceptData.status);
		}
		
		if (liEl.getElement('.uuid')) {
			liEl.getElement('.uuid').set('text', conceptData.uuid);
		}
		
		return liEl;
	},
	emptyContainer: function (container, selector) {
		var elements = container.getElements(selector);
		Array.each(elements, function (element){
			if (!(element.hasClass('template')))
				element.empty().destroy();
		});
	},
	markConceptActive: function (uuid) {
		$$('li.concept-link.active').each(function (item) {
			item.removeClass('active');
		});
		$$('li.concept-link a.' + uuid).each(function (item) {
			item.getParent('li.concept-link').addClass('active');
		});
	},
	showHistory: function (data, element) {
		$('history-count').empty().set('text', data.length);
		this.render(data, element, this.renderConceptLink);
	},
	showActionModal: function (contentElement, options) {
		SqueezeBox.initialize();
		
		var boxContent = contentElement;
		var defaultOptions = {
				handler: 'adopt',
				size: {x: 300, y:  80},
				onOpen: function () {boxContent.show();},
				onClose: function () {boxContent.destroy();} 
		};
		if (typeof options !== 'undefined') {
			for (x in options ) {
				defaultOptions[x] = options[x];
			}
		}

		SqueezeBox.open(boxContent, defaultOptions);
	},
	showSubConcepts: function (concepts, templateContainer, container) {
		var subConceptsOffset = 10;
		
		for (var i = 0; i < concepts.length; i++) {
			var item = Editor.View.renderConceptLink(concepts[i], templateContainer);
			item.getElement('.uuid').set('text', concepts[i].uuid);
			
			// Fix item's content width if right column is resized.
			var itemContentWidth = container.getParent('.concept-link').getElement('.concept-link-content').getStyle('width').toInt() - subConceptsOffset;
			item.getElement('.concept-link-content').setStyles({width: itemContentWidth});
			
			item.inject(container);
		}
	},
	showExportBox: function(type, additionalData) {
		
		var exportBox = $('export-box').clone();
		var showBackgroundJobAlert = false;
		
		toggleBackgroundJobAlert = function() {
			if ((type == 'search' && Editor.Search.getSearchResultsCount() > MAX_RECORDS_FOR_INSTANT_EXPORT)
					|| exportBox.getElement('form').getElement('[name=maxDepth]').get('value') > 1) {
				
				exportBox.getElement('.limit-note').show();
				showBackgroundJobAlert = true;
			} else {
				exportBox.getElement('.limit-note').hide();
				showBackgroundJobAlert = false;
			}
		};
		toggleBackgroundJobAlert();		
		exportBox.getElement('.depth-selector').addEvent('change', function () {
			toggleBackgroundJobAlert();
		});
		
		exportBox.getElement('form').getElement('[name=currentUrl]').set('value', window.location.href);
		exportBox.getElement('form').getElement('[name=type]').set('value', type);
		exportBox.getElement('form').getElement('[name=additionalData]').set('value', additionalData);
		exportBox.getElement('form').addEvent('submit', function () {
			if (showBackgroundJobAlert) {
				alert(exportBox.getElement('.background-job-alert').get('text'));
			}
			
			Editor.View.hideExportBox();
		});
		
		
		// Initialize fields selector
		exportBox.getElement('[name=format]').addEvent('change', function () {
			if (exportBox.getElement('[name=format]').get('value') == 'csv'
				|| exportBox.getElement('[name=format]').get('value') == 'rtf') {
				exportBox.getElement('.fields-selector').show();
				if (exportBox.getElement('[name=format]').get('value') == 'rtf') {
					exportBox.getElement('.depth-selector').show();
				} else {
					exportBox.getElement('.depth-selector').hide();
				}
			} else {
				exportBox.getElement('.fields-selector').hide();
				exportBox.getElement('.depth-selector').hide();
			}
		});
		var exportFieldsSortable = new Sortables(exportBox.getElement('.export-fields'), {
			onSort: function () {
				exportBox.getElement('input[name=fieldsToExport]').set('value', exportFieldsSortable.serialize().join(','));
			}
		});
		exportBox.getElement('.add-to-export').addEvent('click', function () {
			
			var field = exportBox.getElement('.exportable-fields').get('value');
			
			if (field != '') {
				var item = new Element('li').set('id', field).set('text', field);
				var removeItem = new Element('a').addClass('fields-to-export-remove');
				removeItem.addEvent('click', function (e) {
					e.stop();
					exportBox.getElement('.exportable-fields').getElement('[value=' + e.target.getParent('li').get('id') + ']').show();
					e.target.getParent('li').destroy();
				});
				removeItem.inject(item);
				item.inject(exportBox.getElement('.export-fields'));
				
				exportBox.getElement('.exportable-fields').getElement('[value=' + field + ']').hide();
				exportBox.getElement('.exportable-fields').set('value', '');
				
				exportFieldsSortable.addItems(item);
				
				exportBox.getElement('input[name=fieldsToExport]').set('value', exportFieldsSortable.serialize().join(','));
			}
		});
				
		SqueezeBox.open(exportBox, {size: {x: 350, y: 420}, handler: 'adopt'});
	},
	hideExportBox: function () {
		setTimeout(function () {
			SqueezeBox.close();
		}, 1000); // Dealy the closing by 1s so that the file download can start.
	},
	showDeleteBox: function(uuid) {
		var deleteBox = $('delete-box').clone();
		deleteBox.getElement('form').set('send', {
			noCache: true,
	        onComplete: Editor.Control.conceptDeleted
		});
		deleteBox.getElement('form').addEvent('submit', function (e) {e.stop(); new Element(e.target).send()});
		deleteBox.getElement('form').getElement('[name=uuid]').set('value', uuid);
		SqueezeBox.open(deleteBox, {size: {x: 400, y: 250}, handler: 'adopt'});
	},
	showChangeStatusBox: function() {
		var changeStatusBox = $('change-status-box').clone();
		changeStatusBox.getElement('form').set('send', {
			noCache: true,
	        onComplete: function () {
	        	if (Editor.Search.resultsFound > 0) {
	        		Editor.Search.search();
	        	}
	        	if (Editor.Control.loadedConcept) {
	        		Editor.Control.loadConcept(Editor.Control.loadedConcept);
	        	}
	        	Editor.ConceptsSelection.load();
	        	SqueezeBox.close();
        	}
		});
		changeStatusBox.getElement('form').addEvent('submit', function (e) {
			e.stop(); 
			changeStatusBox.getElement('form').send();
			changeStatusBox.getElement('form').hide();
			changeStatusBox.getElement('.loading').show();
		});
		this.showActionModal(changeStatusBox, {size: {x: 300, y: 180}});
	},
	showCreateConfirmationBox: function(doExist) {
		var confirmationBox = $('create-confirmation-box').clone();
		
		if (doExist) {
			confirmationBox.getElement('.do-exist-message').show();
			confirmationBox.getElement('.do-not-exist-message').hide();
		} else {
			confirmationBox.getElement('.do-not-exist-message').show();
			confirmationBox.getElement('.do-exist-message').hide();
		}
		
		confirmationBox.getElement('[name=yes]').addEvent('click', function (e) { e.stop(); SqueezeBox.close(); Editor.Control.saveConcept(); });
		confirmationBox.getElement('[name=no]').addEvent('click', function (e) { e.stop(); SqueezeBox.close(); });
		
		Editor.View.showActionModal(confirmationBox);
	}
});