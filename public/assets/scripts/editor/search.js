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

EditorSearch = new Class({
	Binds: ['search', 'delayedSearch', 'toggleInstantResults', 'searchReady', 'showMoreResults', 'scrollingResults'],	
	searchForm: null,
	searchResults: null,
	defaultUser: null,
	defaultRowsCount: null,
	appendResults: false,
	moreResultsProccessing: false,
	searchFromUrl: false,
	resultsFound: 0,
	delayedSearchDelay: 500,
	delayedSearchTimeoutHandle: null,
	initialize: function (searchForm, searchResults) {
		var self = this;
		this.searchForm = searchForm;
		this.searchResults = searchResults;
		this.defaultUser = this.searchForm.getElement('[name=user]').value;
		this.defaultRowsCount = parseInt(this.searchForm.getElement('[name=rows]').value);		
		
		// Makes the form to submit with ajax.
		this.searchForm.set('send', {
			noCache: true,
	        onComplete: this.searchReady
		});
		
		// Empty the results container
		Editor.View.emptyContainer(this.searchResults.getElement('.concepts-list'), '.concept-link');
		
		// Pagination
		this.searchResults.getElement('.more-results').addEvent('click', function(ev) { ev.stop(); self.showMoreResults(); });
		this.handleScrolling();
		
		// Toggle instant results (if no instantResults checkbox - instant results are by default).
		if (this.searchForm.getElement('[type=checkbox][name=instantResults]')) {
			this.searchForm.getElement('[type=checkbox][name=instantResults]').addEvent('change', this.toggleInstantResults);
		}
		this.toggleInstantResults();
		
		// Handle button clicking or other form submit.
		this.searchForm.addEvent('submit', function(ev) { ev.stop(); self.search(); });
		
		this.initAdvancedOptionsBox();
		
		// Focus on the search text
		this.searchForm.getElement('[name=searchText]').focus();
		
		// Initialize concepts selection methods
		this.searchResults.getElement('.add-all-to-selection').addEvent('click', function(ev) { ev.stop(); self.addAllSearchResultsToSelection(); });
		
		if (this.searchForm.getElement('[name=searchText]').value) {
			this.search();
		}
                
                this.hideProfilesSelectIfNoOptions();
	},
	toggleInstantResults: function () {
		if ( ! this.searchForm.getElement('[type=checkbox][name=instantResults]') || 
				this.searchForm.getElement('[type=checkbox][name=instantResults]').checked) {
			this.searchForm.getElement('[name=searchText]').addEvent('keyup', this.delayedSearch);
			this.searchForm.getElements('[name=truncate]').addEvent('change', this.search);
			this.searchForm.addEvent('change:relay([name="conceptScheme[]"])', this.delayedSearch);
			this.searchForm.addEvent('change:relay([name="allowedConceptScheme[]"])', this.delayedSearch);
			if (this.searchForm.getElement('[name="searchProfileId"]')) {
				this.searchForm.getElements('[name="searchProfileId"]').addEvent('change', this.search);
			}
		} else {
			this.searchForm.getElement('[name=searchText]').removeEvent('keyup', this.delayedSearch);
			this.searchForm.getElements('[name=truncate]').removeEvent('change', this.search);
			this.searchForm.getElements('[name="conceptScheme[]"]').removeEvent('change', this.delayedSearch);
			this.searchForm.getElements('[name="allowedConceptScheme[]"]').removeEvent('change', this.delayedSearch);
			if (this.searchForm.getElement('[name="searchProfileId"]')) {
				this.searchForm.getElements('[name="searchProfileId"]').removeEvent('change', this.search);
			}
		}
	},
	search: function () {
		
		clearTimeout(this.delayedSearchTimeoutHandle);
		
		this.hideCustomProfileIfNotSelected();
		
		if ( ! this.searchFromUrl) {
			// If a manual search is performed the default user must be used, not a user from the url.
			this.searchForm.getElement('[name=user]').set('value', this.defaultUser);
			
			Editor.Url.setParam('search', this.searchForm.getElement('[name=searchText]').get('value'));
			Editor.Url.setParam('user', this.searchForm.getElement('[name=user]').get('value'));
		}
		this.searchFromUrl = false;
		
		this.appendResults = false; // Each search will remove all old results. They are kept only in case of paging.
		this.searchForm.send();
	},
	delayedSearch: function () {
		clearTimeout(this.delayedSearchTimeoutHandle);
		this.delayedSearchTimeoutHandle = this.search.delay(this.delayedSearchDelay);
	},
	searchReady: function (response) {
		this.searchResults.getElement('.no-search').hide();
		this.searchResults.getElement('.no-results').hide();
		this.searchResults.getElement('.no-results-no-search-string').hide();
		this.searchResults.getElement('.errors').hide();
		
		if ( ! this.appendResults) {
			Editor.View.emptyContainer(this.searchResults.getElement('.concepts-list'), '.concept-link');
		}
		
		if (JSON.validate(response)) {
			var data = JSON.decode(response);
			if (data.status == 'ok') {
				
				this.searchResults.getElement('.actions').show();
				
				if (data.concepts.length) {
					this.searchResults.getElement('.results-found').set('text', '(' + data.numFound + ')');
					this.resultsFound = data.numFound;
					for (var i = 0; i < data.concepts.length; i++) {
						this.addResultItem(data.concepts[i]);
					}
					this.toggleMoreResultsLink(data.numFound);
					if (this.resultsFound == 1 && ! Editor.Concept.isInEditMode()) {
						Editor.Control.loadConcept(data.concepts[0].uuid);
					}
				} else {
					if ( ! this.appendResults) {
						this.searchResults.getElement('.results-found').empty();
						this.resultsFound = 0;
						if (this.searchForm.getElementById('searchText').value != '') {
							this.searchResults.getElement('.no-results-search-text').set('text', this.searchForm.getElementById('searchText').value);
							this.searchResults.getElement('.no-results').show();
						} else {
							this.searchResults.getElement('.no-results-no-search-string').show();
						}
						this.searchResults.getElement('.actions').hide();
					}
					this.toggleMoreResultsLink(0);
				}
				
				if (this.moreResultsProccessing) {
					this.moreResultsProccessing = false;
				}
				
				this.setConceptSchemeOptions(data.conceptSchemeOptions);
				this.setProfilesOptions(data.profileOptions);
			} else {
				this.showError(data.message);
			}
		} else {
			this.showError();
		} 
	},
	showError: function (message) {
		this.searchResults.getElement('.results-found').empty();
		this.resultsFound = 0;
		this.searchResults.getElement('.actions').hide();
		this.toggleMoreResultsLink(0);
		if (message) {
			this.searchResults.getElement('.errors').set('text', message);
		}
		this.searchResults.getElement('.errors').show();
	},
	showMoreResults: function (ev) {
		if (! this.moreResultsProccessing) {
			this.moreResultsProccessing = true;
			var rows = parseInt(this.searchForm.getElement('[name=rows]').value);
			// Gets the next defaultRowsCount rows.
			this.searchForm.getElement('[name=start]').value = rows;
			this.searchForm.getElement('[name=rows]').value = this.defaultRowsCount;

			this.appendResults = true; // We need to add the new results to the current results.
			this.searchForm.send();

			// Gets all the results if new search is performed.
			this.searchForm.getElement('[name=start]').value = 0;
			this.searchForm.getElement('[name=rows]').value = rows + this.defaultRowsCount;
		}
	},
	toggleMoreResultsLink: function (numFound) {
		if (numFound > this.searchForm.getElement('[name=rows]').value) {
			this.searchResults.getElement('.more-results').show();
		} else {
			this.searchResults.getElement('.more-results').hide();
		}
	},
	handleScrolling: function () {
		if ($('left-bottom-panel')) {
			var self = this;
			$('left-bottom-panel').addEvent('scroll', function() {self.scrollingResults($('left-bottom-panel'));});
		}
	},
	scrollingResults: function (container) {
		if (container.scrollTop == (container.scrollHeight - container.clientHeight)) {
			this.showMoreResults();
		}		
	},
	addResultItem: function (data) {
		var item = Editor.View.renderConceptLink(data, this.searchResults.getElement('.concepts-list'));		
		item.getElement('.uuid').setProperty('text', data.uuid);
		item.inject(this.searchResults.getElement('.concepts-list'));
	},
	initAdvancedOptionsBox: function () {
		var self = this;
		
		if ($('advanced-options-link')) {
			$('advanced-options-link').addEvent('click', function(ev) {
				ev.stop();				
				SqueezeBox.open($('advanced-options-link').getProperty('href'), {size: {x: 450, y: 500}, handler: 'iframe'});
			});
			
			// This will be used from inside the iframe with advanced options.
			window.onAdvancedOptionsChanged = function (conceptSchemeOptions, profilesOptions, onlyUpdateProfiles) {
				self.setConceptSchemeOptions(conceptSchemeOptions);
				self.setProfilesOptions(profilesOptions);
				if ( ! onlyUpdateProfiles) {
					SqueezeBox.close();				
					self.search();
				}
			};
		}
	},
	addAllSearchResultsToSelection: function () {
		var uuids = this.getVisibleResultsUuids();
		if (Editor.ConceptsSelection) {
			Editor.ConceptsSelection.addMultiple(uuids);
		}
	},
	getSearchResultsCount: function () {
		return this.resultsFound;
	},
	getVisibleResultsUuids: function () {
		var resultItems = this.searchResults.getElements('.concept-link:not(.template)');
		var uuids = new Array();
		for (i = 0; i < resultItems.length; i ++) {
			uuids.push(resultItems[i].getElement('.uuid').get('text'));
		}
		return uuids;
	},
	setConceptSchemeOptions: function (conceptSchemeOptions) {
                var elementPrefix = 'conceptScheme';
                if (! this.searchForm.getElement('#' + elementPrefix + '-element')) {
                    elementPrefix = 'allowedConceptScheme';
                }
                
		if (this.searchForm.getElement('#' + elementPrefix + '-element')) {
			var conceptSchemeElement = this.searchForm.getElement('#' + elementPrefix + '-element');
			conceptSchemeElement.empty();
			
			for (var i = 0; i < conceptSchemeOptions.length; i ++) {
				var optionKey = elementPrefix + '-' + conceptSchemeOptions[i].id.replace(/[^\w]/g, '');
				var label = new Element('label', {'for': optionKey});
				var checkbox = new Element('input', {'type': 'checkbox', 'name': elementPrefix + '[]', 'id': optionKey, 'value': conceptSchemeOptions[i].id});
				if (conceptSchemeOptions[i].selected) {
					checkbox.setAttribute('checked', 'checked');
				}
				label.adopt(checkbox);
				label.appendText(conceptSchemeOptions[i].name);
				conceptSchemeElement.adopt(label);
				conceptSchemeElement.adopt(new Element('br'));
			}
		}
	},
	setProfilesOptions: function (profilesOptions) {
		if (this.searchForm.getElement('select[name=searchProfileId]')) {
			var searchDropdown = this.searchForm.getElement('select[name=searchProfileId]');
			searchDropdown.empty();
			for (var i = 0; i < profilesOptions.length; i ++) {
				if (profilesOptions[i].id == 'custom' && ! profilesOptions[i].selected) {
					continue;
				}
				var option = new Element('option', {value: profilesOptions[i].id, text: profilesOptions[i].name, selected: profilesOptions[i].selected});
				option.inject(searchDropdown);
			}
                        this.hideProfilesSelectIfNoOptions();
		}
	},
        hideProfilesSelectIfNoOptions: function() {
            var searchDropdown = this.searchForm.getElement('select[name=searchProfileId]');
            if (searchDropdown) {
                if (searchDropdown.getElements('option').length > 1) {
                        $('search-profile-selector').show();
                } else {
                        $('search-profile-selector').hide();
                }
            }
        },
	hideCustomProfileIfNotSelected: function () {
		if (this.searchForm.getElement('select[name=searchProfileId]') && this.searchForm.getElement('select[name=searchProfileId]').get('value') != 'custom' && this.searchForm.getElement('select[name=searchProfileId]').getElement('option[value=custom]')) {
			this.searchForm.getElement('select[name=searchProfileId]').getElement('option[value=custom]').hide();
		}
	}
});