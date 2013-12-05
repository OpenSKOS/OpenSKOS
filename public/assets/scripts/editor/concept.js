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

/**
 * The follow "class" manages all dynamic functionality while editing/creating concepts in the editor module.
 * @TODO It's a good idea to have all JS caching in a separate object.
 */

var EditorConcept = new Class({
	Binds: ['initConceptForm',
	        //multi language methods
	        '_bindLanguageTabs',
	        '_getCurrentLanguage',
	        '_hasLanguageLayer',
	        'showLanguageLayer',
	        'addLanguageLayer',
	        'addLanguageControl',
	        'removeLanguageLayer',
	        //multi input methods
	        '_bindMultiInputActions',
	        'addMultiInput',
	        'copyMultiInput',
	        'removeMultiInput',
	        'getMultiElementName',
	        //multi scheme methods,
	        '_bindSchemeTabs',
	        'getCurrentScheme',
	        'showSchemeLayer',
	        'removeSchemeLayer',
	        'addSchemeLayer',
	        'getCurrentSchemes',
	        //Util
	        '_getOpenTab'
	        ],
	        
	initialize: function () {
		var self = this;
		this._bindLanguageTabs();
		this._bindSchemeTabs();
		this._bindMultiInputActions();
		
		$(document.body).addEvent('click:relay(#conceptSwitch)', function (e) {
			e.stop();
			if (confirm($('switch-to-view-confirmation').get('text'))) {
				Editor.Control.loadConcept($('uuid').get('value'));
			}	
		});

		$(document.body).addEvent('click:relay(#conceptSave)', function (e) {
			e.stop();
			if (Editor.Concept.confirmDocPropertiesAreSaved()) {
				Editor.Control.saveConcept();
			}
		});
		
		$(document.body).addEvent("click:relay(.delete-concept)", function (e) {
			e.stop();
			Editor.View.showDeleteBox($('uuid').get('value'));
		});
	},
	
	initConceptForm: function () {
				
		Editor.Relations.enableRelationLinks();
		
		this.bindTabsHover();
		this.showLanguageLayer();
		this.showSchemeLayer();
		this.showMappingProperties();
		this.toggleConceptSchemeWarning();
		this.showPrefLabelInTitle();
		this._buildUri();
	},
	
	toggleConceptSchemeWarning: function () {
		var rightTabs = $('concept-edit-tabs').getElement('.concept-form-right');
		if (rightTabs.getElements('.concept-multi-hidden-template:not(.template)').length == 0) {
			rightTabs.getElement('.concept-multi-hidden-add').addClass('warning');
		} else {
			rightTabs.getElement('.concept-multi-hidden-add').removeClass('warning');
		}
	},
	
	showPrefLabelInTitle: function () {
		this._originalTitleText = $('concept-edit-form').getElement('h2').get('text');
		var self = this;		
		$('concept-edit-form').getElement('h2').set('text', self._originalTitleText + ' "' + self.getFirstPrefLabel() + '"');
		$(document.body).addEvent("keyup:relay(input[name^=prefLabel])", function (e) {
			$('concept-edit-form').getElement('h2').set('text', self._originalTitleText + ' "' + self.getFirstPrefLabel() + '"');
		});
	},
	
	isInEditMode: function () {
		return $('conceptSave') !== null;
	},
	
	bindTabsHover: function () {
		$(document.body).addEvent("mouseover:relay(.concept-multi-hidden-label)", function (e) {
			e.target.getParent('.concept-multi-hidden-template').addClass('isHover');
		});
		$(document.body).addEvent("mouseover:relay(.concept-multi-hidden-remove)", function (e) {
			e.target.getParent('.concept-multi-hidden-template').addClass('isHover');
		});
		$(document.body).addEvent("mouseover:relay(.concept-multi-hidden-template)", function (e) {
			e.target.addClass('isHover');
		});
		$(document.body).addEvent("mouseout:relay(.concept-multi-hidden-label)", function (e) {
			e.target.getParent('.concept-multi-hidden-template').removeClass('isHover');
		});
		$(document.body).addEvent("mouseout:relay(.concept-multi-hidden-remove)", function (e) {
			e.target.getParent('.concept-multi-hidden-template').removeClass('isHover');
		});
		$(document.body).addEvent("mouseout:relay(.concept-multi-hidden-template)", function (e) {
			e.target.removeClass('isHover');
		});
	},
		
	//Language multi-field wrappers get appended a language code as a class.
	//This is what we use to display language tab elements in the form
	showLanguageLayer: function (languageCode) {
		var self = this;
		if ((typeof languageCode !== 'undefined') && this._hasLanguageLayer(languageCode)) {
			Array.each($$('[name="conceptLanguages[]"]'), function (tab) {
				if (languageCode === tab.get('value')) {
					tab.getParent('span').addClass('isOpen');
				} else {
					tab.getParent('span').removeClass('isOpen');
				} 
			});
		} else {
			Array.each($$('[name="conceptLanguages[]"]'), function (tab) {
				tab.getParent('span').removeClass('isOpen');
			});
			
			if ($$('[name="conceptLanguages[]"]').length > 1) {
				$$('[name="conceptLanguages[]"]')[1].getParent('span').addClass('isOpen');
			}	
		}
		
		var currentLanguage = this._getCurrentLanguage();
		if ($('conceptPropertySelect')) {
			$('conceptPropertySelect').getElements('option').show();			
		}		
		var option = null;
		
		//The code below does the following:
		//- Loop all multi-field containers (those may contain multiple multi fields for ALL languages)
		//- Loop the content of the field container, check what language it is in, take action accordingly, cache and adjust the option menu.
		Array.each($$('.multi-field'), function (container) {
			container.hide();
			Array.each(container.getElements('.concept-multi-template:not(.template)'), function (wrapper) {
				if (wrapper.hasClass(currentLanguage)) {
					container.show();
					wrapper.show();					
					if ($('conceptPropertySelect')) {
						option = $('conceptPropertySelect').getElement('option[value="' + self.getMultiElementName(wrapper) + '"]');
						if (option !== null) {
							option.hide();
						}
					}
				} else {
					wrapper.hide();
				}
			});
		});
	},
	
	addLanguageLayer: function (languageCode) {
		if (!this._hasLanguageLayer(languageCode)) {
			var template = $$('input[name="conceptLanguages[]"]').pick().getParent('span').clone().removeClass('template');
			template.getElement('.concept-multi-hidden-label').set('text', languageCode.toUpperCase());
			template.getElement('input[name="conceptLanguages[]"]').set('value', languageCode);
			template.inject($$('input[name="conceptLanguages[]"]').pick().getParent('span').getNext('.concept-multi-hidden-add'), 'before');
			
			var currentLanguage = this._getCurrentLanguage();
			Array.each($$('.concept-multi-template.' + currentLanguage), function (element) {
				var mlElement = element.clone().removeClass(currentLanguage).addClass(languageCode);
				Array.each(mlElement.getChildren('textarea, input'), function (input) {
					input.set('name', input.get('name').replace('['+ currentLanguage +']', '['+ languageCode +']'));
					input.set('value', '');
				});
				element.getParent('.multi-field').adopt(mlElement);
			}) ;
		}
		SqueezeBox.close();
		this.showLanguageLayer(languageCode);
	},

	removeLanguageLayer: function (languageCode) {
		var self = this;
		Array.each($$('input[name="conceptLanguages[]"]'), function (tab) {
			if ($$('input[name="conceptLanguages[]"]').length > 2) {
				if (tab.get('value') === languageCode) {
					tab.getParent('.concept-multi-hidden-template').dispose();
					Array.each($$('.' + languageCode), function (el) {
						el.dispose();
					});
					self.showLanguageLayer();
				}
			}
		});
	},
	
	getMultiElementName: function (element) {
		return element.getChildren('textarea, input')[0].get('name').split('[')[0];
	},
	
	addMultiInput: function () {
		var self = this;
		var propertyName = $('conceptPropertySelect').get('value');
		if (propertyName != '') {
			var template = $$('[name="' + propertyName + '[][]' + '"]')[0].getParent('span').clone();
			
			template.removeClass('template');
			template.addClass(this._getCurrentLanguage());
			
			Array.each(template.getChildren('textarea, input'), function (element) {
				element.set('name', $('conceptPropertySelect').get('value') + '[' + self._getCurrentLanguage() + '][]');
				element.set('value', $('conceptPropertyContent').get('value'));
			});
			$('conceptPropertySelect').set('value', '');
			$('conceptPropertyContent').set('value', '');
			$$('[name="' + propertyName + '[][]' + '"]')[0].getParent('span').getParent('span').adopt(template);
			this.showLanguageLayer(this._getCurrentLanguage());
		}
	},
	
	copyMultiInput: function (element) {
		var currentLanguage = this._getCurrentLanguage();
		var wrapper = element.getParent('span').clone();
		Array.each(wrapper.getChildren('textarea, input'), function (input) {
			input.set('value', element.get('value'));
		});
		element.getParent('span').getParent('span').adopt(wrapper);
		this.showLanguageLayer(this._getCurrentLanguage());
	},
	
	removeMultiInput: function (removeButton) {
		//we aren't allowed to remove the last lexical label.
		if ((removeButton.getPrevious('input') !== null)  && ($$('input[name="' + removeButton.getPrevious('input').get('name')+ '"]').length == 1)) {
			return;
		}
		removeButton.getParent('span').hide().destroy();
		this.showLanguageLayer(this._getCurrentLanguage());
	},
	
	getCurrentScheme: function () {
		return this._getOpenTab('inScheme[]');
	},
	
	showSchemeLayer: function (uuid) {
		var self = this;
		
		if (typeof uuid === 'undefined') {
			if ($$('input[name="inScheme[]"]').length > 1){
				uuid = $$('input[name="inScheme[]"]')[1].get('value');
			} else {
				return;
			}		
		}
		Array.each($$('[name="inScheme[]"]'), function (tab) {
			if (uuid === tab.get('value')) {
				tab.getParent('span').addClass('isOpen');
			} else {
				tab.getParent('span').removeClass('isOpen');
			} 
		});
		Array.each($$('.multi-field-complex'), function (el) {
			if ( ! el.getParent('#concept-edit-mapping-properties')) { // Mapping properties are not per scheme. See showMappingProperties.
				el.hide();
				Array.each(el.getElements('.multi-field-list'), function (ulEl) {
					if(ulEl.hasClass(uuid)) {
						el.show();
						ulEl.show();
						ulEl.getPrevious('.concept-complex-title').show();
					} else {
						ulEl.hide();
						ulEl.getPrevious('.concept-complex-title').hide();
					}
				});
			}
		});
		Array.each($('concept-edit-scheme-properties').getElements('input'), function (input) {
			if (input.get('value') === uuid) {
				input.getParent('label').show();
			} else {
				input.getParent('label').hide();			
			}
		});
	},
	
	removeSchemeLayer: function (uuid) {
		var self = this;
		Array.each($$('input[name="inScheme[]"]'), function (tab) {
			if (($$('input[name="inScheme[]"]').length > 2) && (tab.get('value') === uuid)) {
				tab.getParent('.concept-multi-hidden-template').dispose();
				Array.each($('concept-edit-scheme-properties').getElements('input'), function (input) {
					if (input.get('value') === uuid) {
						input.getParent('label').dispose();
					}
				});
				Array.each ($$('.multi-field-complex'), function (el) {
					Array.each(el.getElements('.multi-field-list'), function (ulEl) {
						if (ulEl.hasClass(uuid)) {
							ulEl.dispose();
						}
					});
				});
				self._removeConceptsBaseUrl(uuid);
			}
			self.showSchemeLayer();
		});	
	},
	
	addSchemeLayer: function (uuid, name) { //@TODO refactor - creation of tabs can be refactored.
		if (!this.hasSchemeLayer(uuid)) {
			var template = $$('input[name="inScheme[]"]').pick().getParent('span').clone().removeClass('template');
			template.getElement('.concept-multi-hidden-label').set('text', name);
			template.getElement('input[name="inScheme[]"]').set('value', uuid);
			template.inject($$('input[name="inScheme[]"]').pick().getParent('span').getNext('.concept-multi-hidden-add'), 'before');
		}
		
		Array.each($$('.multi-field-complex'), function (el) {
			if ( ! el.getParent('#concept-edit-mapping-properties')) { // Mapping properties are not per scheme.
				Array.each(el.getElements('.multi-field-list'), function (listEl) {
					var newList = null;
					Array.each(listEl.getElements('.concept-link-header>img'), function (scheme) {
						if (scheme.hasClass(uuid)) {
							if (newList === null) {
								newList = listEl.clone().erase('class').addClass('multi-field-list').addClass(uuid).empty();
								newList.empty();
							} 
							var linkUuid = scheme.getParent('.concept-link-header').getNext('.concept-link-content>a').get('class');
							if (newList.getElement('li>.concept-link-content>a[class="' +linkUuid + '"]') === null) {
								newList.adopt(scheme.getParent('li').clone());
							}
						}
					});
					if (newList !== null) {
						newList.inject(listEl, 'after');
					}
				});
			}
		});
		
		//@FIXME with templates prob
		var isTopElement = null;
		if ( $('concept-edit-scheme-properties').getElement('input[type=checkbox]') !== null) {
			isTopElement = $('concept-edit-scheme-properties').getElements('input[type=checkbox]').pick().getParent('label').clone();
		} else {
			isTopElement = new Element('label').adopt(new Element('input', {type: 'checkbox', value: uuid, name: 'topConceptOf[]'}));
		}
		
		isTopElement.set('for', uuid).getElement('input').set('value', uuid);
		$('concept-edit-scheme-properties').adopt(isTopElement);
		
		SqueezeBox.close();
		this.showSchemeLayer(uuid);
		
		this._addConceptsBaseUrl(uuid);
		
		this.toggleConceptSchemeWarning();
	},
	
	hasSchemeLayer: function (uuid) { //@TODO refactor layers into a general class.
		var hasLayer = false;
		Array.each($$('[name="inScheme[]"]'), function (tab) {
			if (tab.get('value') === uuid)
				hasLayer = true;
		});
		return hasLayer;
	},
	
	showMappingProperties: function () {
		if ($('concept-edit-mapping-properties')) {
			$('concept-edit-mapping-properties').getElements('.multi-field-complex').each(function (el) {
				if (el.getElement('.multi-field-list').getElements('.concept-link:not(.template)').length > 0) {
					el.show();
					el.getElement('.multi-field-list').show();
					el.getElement('.concept-complex-title').show();
				} else {
					el.hide();
					el.getElement('.multi-field-list').hide();
					el.getElement('.concept-complex-title').hide();
				}			
			});
		}
	},
	
	getCurrentSchemes: function () {
		var currentSchemes = [];
		Array.each($$('input[name="inScheme[]"]'), function (inputElement) {
			if (inputElement.get('value') !== '') {
				currentSchemes.push(inputElement.get('value'));
			} 
		}); 
		return currentSchemes;
	},
	
	setApproved: function () {
		if (this.isInEditMode()) {
			var editForm = $('Editconcept');
			if (editForm.getElement('[type=checkbox][name=toBeChecked]')) {
				editForm.getElement('[type=checkbox][name=toBeChecked]').set('checked', false);
			}
			if (editForm.getElement('[type=radio][name=status][value=approved]')) {
				editForm.getElement('[type=radio][name=status][value=approved]').set('checked', true);
			}
		}
	},
	
	getFirstPrefLabel: function () {
		var prefLabelText = '';
		var prefLabels = $('Editconcept').getElements('input[name^=prefLabel]')
		
		for (var i = (prefLabels.length-1); i >= 0; i --) {
			if (prefLabels[i].get('value') != '') {
				prefLabelText = prefLabels[i].get('value');
			}
		}
		return prefLabelText;
	},
	
	confirmDocPropertiesAreSaved: function () {
		// If we have content in conceptPropertyContent and conceptPropertySelect is selected - we need to confirm that the user don't want it.
		if ($('conceptPropertySelect').get('value') !== '' && $('conceptPropertyContent').get('value') !== '') {
			return confirm($('doc-properties-not-saved-confirmation').get('text'));
		} else {
			return true;
		}
	},
	
	_getOpenTab: function (inputName) {
		var tabValue = null;
		Array.each($$('[name="'+ inputName +'"]'), function (tab) {
			if (tab.getParent('span').hasClass('isOpen')) {
				tabValue= tab.get('value');
			}
		});
		return tabValue;
	}.protect(),
	
	//Private & protected
	_getCurrentLanguage: function () {
		return this._getOpenTab('conceptLanguages[]');
	}.protect(),
	
	_hasLanguageLayer: function (languageCode) {
		var hasLayer = false;
		Array.each($$('[name="conceptLanguages[]"]'), function (tab) {
			if (tab.get('value') === languageCode)
				hasLayer = true;
		});
		return hasLayer;
	}.protect(),
	
	_bindLanguageTabs: function () {
		var self = this;
		//Language tabs controls
		$(document.body).addEvent('click:relay(.concept-form-left .concept-multi-hidden-label)', function (e) {
			e.stop();
			if (this.getNext('input[name="conceptLanguages[]"]') !== null) {
				self.showLanguageLayer(this.getNext('input[name="conceptLanguages[]"]').get('value'));
			}
		});
		$(document.body).addEvent('click:relay(.concept-form-left .concept-multi-hidden-add)', function (e){
			e.stop();
			Editor.View.showActionModal($('concept-language-settings').clone())
		});	
		$(document.body).addEvent('click:relay(.concept-form-left .concept-multi-hidden-remove)', function (e) {
			e.stop();
			if (this.getPrevious('input[name="conceptLanguages[]"]') !== null) {
				self.removeLanguageLayer(this.getPrevious('input[name="conceptLanguages[]"]').get('value'));
			}
		});
		$(document.body).addEvent('click:relay([name=conceptLanguageOk])', function (e) {
			e.stop();
			self.addLanguageLayer(this.getPrevious('select').get('value'));
		});
	}.protect(),
	
	_bindSchemeTabs: function () {
		var self = this;
		//Scheme tabs controls
		$(document.body).addEvent('click:relay(.concept-form-right .concept-multi-hidden-label)', function (e) {
			e.stop();
			self.showSchemeLayer(this.getNext('input[name="inScheme[]"]').get('value'));
		});
		$(document.body).addEvent('click:relay(.concept-form-right .concept-multi-hidden-add)', function (e){
			Editor.View.showActionModal($('concept-scheme-settings').clone());
		});	
		$(document.body).addEvent('click:relay(.concept-form-right .concept-multi-hidden-remove)', function (e) {
			e.stop();
			self.removeSchemeLayer(this.getPrevious('input[name="inScheme[]"]').get('value'));
		});
		$(document.body).addEvent('click:relay([name=conceptSchemeOk])', function (e) {
			e.stop();
			self.addSchemeLayer(this.getPrevious('select').get('value'), this.getPrevious('select').getElement(':selected').get('text'));
		});
	}.protect(),
	
	_bindMultiInputActions: function () {
		var self = this;
		$(document.body).addEvent('click:relay(#conceptPropertyAdd)', function (e) {
			e.stop();
			self.addMultiInput();	
		});

		$(document.body).addEvent('click:relay(.concept-multi-add)', function (e){
			e.stop();	
			self.copyMultiInput(this);
		});

		$(document.body).addEvent('click:relay(.concept-multi-remove)', function (e){
			e.stop();
			self.removeMultiInput(this);
		});
	}.protect(),
	
	_addConceptsBaseUrl: function (conceptSchemeUuid) {
		var self = this;
		new Request.JSON({
			url: BASE_URL + "/editor/concept-scheme/get-concepts-base-url", 
			method: 'post',
			data: {uuid: conceptSchemeUuid},
			onSuccess: function(result, text) {
				var baseUriEl = $('concept-edit-form').getElement('#baseUri');
				if (! baseUriEl.getElement('option[value="' + result.result + '"]')) {
					$('concept-edit-form').getElement('#baseUri').adopt(
						new Element('option', {'value': result.result, 'text': result.result})
					);
					self._buildUri();
				}
			}
		}).send();
	}.protect(),
	
	_removeConceptsBaseUrl: function (conceptSchemeUuid) {
		var self = this;
		new Request.JSON({
			url: BASE_URL + "/editor/concept-scheme/get-concepts-base-url", 
			method: 'post',
			data: {uuid: conceptSchemeUuid},
			onSuccess: function(result, text) {
				var baseUriEl = $('concept-edit-form').getElement('#baseUri');
				var option = baseUriEl.getElement('option[value="' + result.result + '"]');
				if (option) {
					option.dispose();
					self._buildUri();
				}
			}
		}).send();
	}.protect(),
	
	_buildUri: function () {
		if ($('concept-edit-form').getElement('#baseUri')) {
			$('concept-edit-form').getElement('#baseUri').addEvent('change', function () {
				var baseUri = $('concept-edit-form').getElement('#baseUri').get('value');
				if (baseUri != "" && ! /\/$/.test(baseUri) && ! /=$/.test(baseUri)) {
					baseUri += '/';
				}
				$('concept-edit-form').getElement('#uri').set('value', baseUri + $('concept-edit-form').getElement('#notation').get('value'));
			});			
			$('concept-edit-form').getElement('#baseUri').fireEvent('change');
		}
	}
});