var EditorRelations = new Class({
	Binds: [
		'bindRelationActions',
		'addMultipleRelations',
		'removeRelation',	        
		'_getRelationSchemes',
		'_createRelationElement',
	],
	
	areRelationsWithConceptScheme: false,
	
	relationTypes: {
		semantic: ['broader', 'narrower', 'related'],
		mapping: ['exactMatch', 'closeMatch', 'broadMatch', 'narrowMatch', 'relatedMatch'],
		withConceptScheme: ['topConcepts', 'concepts']
	},
	
	initialize: function () {
		this.bindRelationActions();
		this.disableRelationLinks();
	},
	
	enableRelationLinks: function (widthConceptScheme) {
		
		if (widthConceptScheme) {
			this.areRelationsWithConceptScheme = true;
		} else {
			this.areRelationsWithConceptScheme = false;
		}
		
		if (ARE_RELATIONS_ALLOWED) {
			$$('.add-relation').each(function (el) { el.removeClass('disabled'); });
		}
	},
	
	disableRelationLinks: function () {
		$$('.add-relation').each(function (el) { el.addClass('disabled'); });
	}, 
	
	showAddRelationBox: function (uuid) {
		var uuids = new Array(uuid);
		this.showAddMultipleRelationBox(uuids);
	},
	
	showAddMultipleRelationBox: function (uuids) {
		
		var relationBox = null;
		if (this.areRelationsWithConceptScheme) {
			relationBox = $('relation-box-for-scheme').clone();
		} else {
			relationBox = $('relation-box').clone();
			
			var relationPosibility = this.checkConceptsRelationPosibilityWithCurrent(uuids);
			
			relationBox.getElement('.relation-box-message.not-consistant').hide();
			relationBox.getElement('.relation-box-message.semantic').hide();
			relationBox.getElement('.relation-box-message.mapping').hide();
			if (relationPosibility.areConsistant) {
				if (relationPosibility.shareScheme) {
					relationBox.getElement('.relation-box-message.semantic').show();
					relationBox.getElement('.relation-box-list.semantic').removeClass('disabled');			
				} else {
					relationBox.getElement('.relation-box-message.mapping').show();
					relationBox.getElement('.relation-box-list.mapping').removeClass('disabled');
				}
			} else {
				relationBox.getElement('.relation-box-message.not-consistant').show();
			}
			
			relationBox.getElements('.relation-box-list.disabled').each(function (el) { 
				el.getElements('.relation-action-link').each(function (el) {
					el.addEvent('click', function (e) { e.stop(); });
				});
			});			
		}
		
		relationBox.getElement('.uuids').set('text', uuids.join(','));
		Editor.View.showActionModal(relationBox, {size:{x: 360, y: 340}});
	},
	
	checkConceptsRelationPosibilityWithCurrent: function (uuids) {
		var currentSchemes = Editor.Concept.getCurrentSchemes();
		var result = {shareScheme: null, areConsistant: true};
		
		for (i = 0; i < uuids.length; i ++) {
			var relationSchemes = this._getRelationSchemes(uuids[i]);
			var hasMatch = this.hasSchemesMatch(currentSchemes, relationSchemes);

			if (result.shareScheme !== null && hasMatch != result.shareScheme) {
				result.areConsistant = false;
				break;
			}
			
			result.shareScheme = hasMatch;
		}		
		return result;
	},
	
	hasSchemesMatch: function (schemes1, schemes2) {
		var hasMatch = false;
		for (x in schemes2) {
			if (schemes1.contains(schemes2[x])) {
				return true;
			}
		}
		return false;
	},
	
	addMultipleRelations: function (uuids, relation) {
		
		var isRelationPossible = true;
		
		if (this.areRelationsWithConceptScheme) {
			isRelationPossible = true; // Relations with concept scheme are always possible.
		} else {
			var relationPosibility = this.checkConceptsRelationPosibilityWithCurrent(uuids);
			isRelationPossible = relationPosibility.areConsistant;
			if (relationPosibility.shareScheme) {
				if ( ! this.relationTypes.semantic.contains(relation)) {
					isRelationPossible = false;
				}
			} else {
				if ( ! this.relationTypes.mapping.contains(relation)) {
					isRelationPossible = false;
				}
			}
		}
		
		if (isRelationPossible) {
			for (i = 0; i < uuids.length; i ++) {
				this.addRelation(uuids[i], relation);
			}
		} else {
			this.showAddMultipleRelationBox(uuids);
			return;
		}
	},
	
	addRelation: function (uuid, relation) {
		var self = this;
		
		if (this.areRelationsWithConceptScheme) {
			this._createRelationElement(uuid, relation, null);
		} else {
			//Apply to common schemes view.
			var currentSchemes = Editor.Concept.getCurrentSchemes();
			var matchingSchemes = self._getRelationSchemes(uuid).filter(function (value) { return currentSchemes.contains(value)});
			
			if (matchingSchemes.length > 0) {
				// If there are matching schemes - we can suppose that the relation is semantic and create it in each matching scheme.
				var self = this;
				Array.each(matchingSchemes, function (schemeUuid) {
					self._createRelationElement(uuid, relation, schemeUuid);
				});
				Editor.Concept.showSchemeLayer(Editor.Concept.getCurrentScheme());
			} else {
				// If there are no matching schemes - we can suppose that the relation is mapping property and create it without dependency to the schemes.
				this._createRelationElement(uuid, relation, null);
			}
		}
		
		SqueezeBox.close();
	},
	
	removeRelation: function (uuid, relation) {
		var self = this;
		
		Array.each($$('.multi-field-complex#' + relation + '>ul:not(.template)'), function (ulElement){
			Array.each(ulElement.getElements('li:not(.template)'), function (liElement){
				if (liElement.getElement('.concept-link-content>a').hasClass(uuid)) {
					//relation link removed.
					liElement.dispose();
					var count = ulElement.getElements('li:not(.template)').length;
					var title = ulElement.getPrevious('.concept-complex-title').get('html').replace(new RegExp('\\d+'), count);
					ulElement.getPrevious('.concept-complex-title').set('html', title);
					if (!(count > 0)) {
						//the list contains no other elements.
						ulElement.set('class', 'multi-field-list template');
						ulElement.getPrevious('.concept-complex-title').hide();
					}
				}
			;})
		});
		
		if ( ! this.areRelationsWithConceptScheme) {
			Editor.Concept.showSchemeLayer(Editor.Concept.getCurrentScheme());
		}		
	},
	
	bindRelationActions: function () {
		var self = this;
		$(document.body).addEvent('click:relay(.add-relation.single)', function (e) {
			e.stop();
			// If in edit mode and access allowed - opens add relation box.
			if (ARE_RELATIONS_ALLOWED && Editor.Concept.isInEditMode()) {
				var uuid = this.getPrevious('.concept-link-content>a').get('class');
				self.showAddRelationBox(uuid);
			}
		});
		
		$(document.body).addEvent('click:relay(.add-relation.search)', function (e) {
			e.stop();			
			// If in edit mode and access allowed - opens add relation box.
			if (ARE_RELATIONS_ALLOWED && Editor.Concept.isInEditMode()) {
				self.showAddMultipleRelationBox(Editor.Search.getVisibleResultsUuids());
			}
		});
		
		$(document.body).addEvent('click:relay(.add-relation.selection)', function (e) {
			e.stop();			
			// If in edit mode and access allowed - opens add relation box.
			if (ARE_RELATIONS_ALLOWED && Editor.Concept.isInEditMode()) {
				self.showAddMultipleRelationBox(Editor.ConceptsSelection.getUuids());
			}
		});
		
		$(document.body).addEvent('click:relay(.relation-action-link)', function (e) {
			e.stop();
			self.addMultipleRelations(this.getParent('.relation-box').getElement('.uuids').get('text').split(','), this.getParent('li').get('class'));
		});
		
		$(document.body).addEvent('click:relay(.concept-link-remove-action)', function (e) {
			e.stop();
			self.removeRelation(this.getPrevious('.concept-link-content>a').get('class'), this.getParent('.multi-field-complex').get('id'));
		});
	},
		
	_createRelationElement: function (uuid, relation, schemeUuid) {
		
		Array.each($$('.multi-field-complex#' + relation), function (holder) {
			var inserted = false;
			//if we have a list for the scheme, insert
			Array.each(holder.getElements('ul:not(.template)'), function (ulElement) {
				if (ulElement.hasClass(schemeUuid) || ( ! schemeUuid)) {
					//we already have other concepts with the same relation in this scheme.
					var newRelation =  ulElement.getElement('.template').clone().removeClass('template');
					Array.each($$('.' + uuid), function (element) {
						if (element.get('tag') === 'a' && element.getParent('li.concept-link').getElement('.concept-link-header')) {							
							newRelation.getElement('.concept-link-content').set('html', element.getParent('span').get('html'));							
							newRelation.getElement('.concept-link-header').set('html', element.getParent('li.concept-link').getElement('.concept-link-header').get('html'));
							newRelation.getElement('.concept-link-remove-action>input').set('value', uuid);
						}
					});
					ulElement.adopt(newRelation);
					var count = ulElement.getElements('li:not(.template)').length;
					var title = ulElement.getPrevious('.concept-complex-title').get('html').replace(new RegExp('\\d+'), count);
					ulElement.getPrevious('.concept-complex-title').set('html', title);
					inserted = true;
				} 
			});
			if (!inserted) {
				
				//There are no concepts with said relation in the scheme.
				var ulElement = $$('.multi-field-complex#' + relation + '>ul').pick();
				var titleEl = $$('.multi-field-complex#' + relation + '>.concept-complex-title').pick();
				if (schemeUuid) {
					// If the relation is per scheme - the ui and the title must be cloned.
					ulElement = ulElement.clone()
					ulElement.set('class', 'multi-field-list ' + schemeUuid);
					titleEl = titleEl.clone();
				} else {
					ulElement.set('class', 'multi-field-list');
				}
				
				Editor.View.emptyContainer(ulElement, '.concept-link');
				
				var newRelation = ulElement.getElement('.template').clone().removeClass('template');
				Array.each($$('.' + uuid), function (element) {
					if (element.get('tag') === 'a') {
						newRelation.getElement('.concept-link-content').set('html', element.getParent('span').get('html'));
						newRelation.getElement('.concept-link-header').set('html', element.getParent('span').getPrevious('.concept-link-header').get('html'));
						newRelation.getElement('.concept-link-remove-action>input').set('value', uuid);
					}
				});
				ulElement.adopt(newRelation);
				
				var title = titleEl.get('html').replace(new RegExp('\\d+'), 1);
				titleEl.set('html', title);
				
				if (schemeUuid) {
					$$('.multi-field-complex#' + relation).pick().adopt(titleEl).adopt(ulElement);
				} else {
					titleEl.show();
					$$('.multi-field-complex#' + relation).pick().show();
				}
			} 
		});
	}.protect(),
	
	_getCurrentSchemes: function () {
		var currentSchemes = [];
		Array.each($$('input[name="inScheme[]"]'), function (inputElement) {
			if (inputElement.get('value') !== '') {
				currentSchemes.push(inputElement.get('value'));
			} 
		}); 
		return currentSchemes;
	}.protect(),
	
	_getRelationSchemes: function (uuid) {
		var relationSchemes = [];
		Array.each($$('.' + uuid), function (element) {
			if (element.get('tag') === 'a') {
				var header = element.getParent('span').getPrevious('.concept-link-header');
				if (header) {
					Array.each(header.getElements('span'), function (schemeHolder) {
						relationSchemes.push(schemeHolder.get('class'));
					});
					Array.each(header.getElements('img'), function (schemeHolder) {
						relationSchemes.push(schemeHolder.get('class'));
					});
				}
			} 
		});
		
		return relationSchemes.unique();
	}
});