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

var EditorConceptScheme = new Class({
	Binds: ['autoGenerateUriCode'],
	Extends: EditorConcept,
	initialize: function () {
		var self = this;
		this._bindLanguageTabs();
				
		$(document.body).addEvent('click:relay(#conceptSchemeSave)', function (e) {
			e.stop();
			Editor.Control.saveConceptScheme();
		});
	},
	initConceptSchemeForm: function () {
		
		/* The concepts relations is implemented but needs to be tested. Its not needed for now.
		Editor.Relations.enableRelationLinks(true);
		*/
		
		this.bindCollectionChange();
		this.bindAutoUriCodeGeneration();
		this.bindTabsHover();
		this.showLanguageLayer();
		this.refreshConceptsBaseUrl();
		
		$$('input[name^=dcterms_title]').pop().focus();
	},
	bindCollectionChange: function () {
		$(document.body).addEvent('change:relay(select[name=collection])', function (e) {
			Editor.ConceptScheme.refreshConceptsBaseUrl();
		});
	},
	bindAutoUriCodeGeneration: function () {
		$(document.body).addEvent('keyup:relay(input[name^=dcterms_title])', function (e) {
			Editor.ConceptScheme.autoGenerateUriCode();
		});
		$(document.body).addEvent('click:relay(.concept-multi-hidden-remove)', function (e) {
			Editor.ConceptScheme.autoGenerateUriCode();
		});
	},
	autoGenerateUriCode: function () {
		//$$('input[name^=dcterms_title][value!=""]').pick() somehow does not work. So we make it manually.		
		var firstFilledElement = null;
		var titleElements = $$('input[name^=dcterms_title]');
		for (i = titleElements.length - 1; i >= 0; i--) {
			if (titleElements[i].get('value') != "") {
				firstFilledElement = titleElements[i];
			}
		}
		
		if (firstFilledElement) {
			var valueToUse = firstFilledElement.get('value');
			valueToUse = valueToUse.replace(/[^(\d|\w)]+/gi, '');
			$('uriCode').set('value', valueToUse);
		}
	},
	refreshConceptsBaseUrl: function () {
		var collectionId = $$('select[name=collection]').pop().get('value');
		new Request.JSON({
			url: BASE_URL + "/editor/collections/get-concepts-base-url", 
			method: 'post',
			data: {id: collectionId},
			onSuccess: function(result, text) {
				$$('input[name=uriBase]').pop().set('value', result.result);
			}
		}).send();
	}
});