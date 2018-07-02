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

var EditorControl = new Class({
    Binds: ['clearHistory',
        'loadHistory',
        'loadConcept',
        'clickConcept'],
    loadedConcept: '',
    loadingTimeoutHandle: null,
    _statusSuccess: 'ok',
    clickConceptCallback: null,
    initialize: function () {

        // Bind History actions
        $(document.body).addEvent('click:relay(.action-clear-history)', function (e) {
            e.stop();
            Editor.Control.clearHistory($('history-list'));
        });

        // Bind Nested concepts actions
        $(document.body).addEvent("click:relay(.show-narrower-relations)", function (e) {
            e.stop();
            Editor.Control.showNarrowerRelations(new Element(e.target).getParent('.concept-link'));
        });
        $(document.body).addEvent("click:relay(.hide-narrower-relations)", function (e) {
            e.stop();
            Editor.Control.hideNarrowerRelations(new Element(e.target).getParent('.concept-link'));
        });

        // Bind Single concept actions
        $(document.body).addEvent('click:relay(.concept-link-content>a)', function (e) {
            e.stop();
            Editor.Control.clickConcept(this.getParent('.concept-link').getElement('.uri').get('text'));
        });
        $(document.body).addEvent('click:relay(#concept-edit)', function (e) {
            Editor.Control.editConcept($('uri').get('value'));
        });

        $(document.body).addEvent('click:relay(#concept-add)', function (e) {
            var newConceptName = this.getParent('div').getPrevious('.no-results-search-text').get('text');
            newConceptName = newConceptName.replace(/^[\*\?]+|[\*\?]+$/, '');
            Editor.Control.addConcept(newConceptName);
        });

        // Bind Export actions
        $(document.body).addEvent("click:relay(.export-concept)", function (e) {
            e.stop();
            Editor.View.showExportBox('concept', $('uri').get('value'));
        });
        $(document.body).addEvent("click:relay(.export-history)", function (e) {
            e.stop();
            Editor.View.showExportBox('history');
        });
        $(document.body).addEvent("click:relay(.export-selection)", function (e) {
            e.stop();
            Editor.View.showExportBox('selection');
        });
        $(document.body).addEvent("click:relay(.export-search)", function (e) {
            e.stop();
            Editor.View.showExportBox('search', JSON.encode($('searchform').toQueryString().parseQueryString()));
        });

        // Bind change status actions
        $(document.body).addEvent("click:relay(.change-status)", function (e) {
            e.stop();
            Editor.View.showChangeStatusBox();
        });

        // Initially loads the history
        this.loadHistory($('history-list'));
    },
    clickConcept: function (uri) {
        if (Editor.Control.clickConceptCallback === null) {
            Editor.Control.loadConcept(uri);
        } else {
            Editor.Control.clickConceptCallback.attempt(uri);
        }
    },
    loadConcept: function (uri) {

        Editor.Url.setParam('concept', uri);

        Editor.Relations.disableRelationLinks();

        var self = this;
        self.showLoading();
        new Request.HTML({
            url: BASE_URL + '/editor/concept/view/uri/' + encodeURIComponent(uri),
            onSuccess: function (responseTree, responseElements, responseHTML, responseJavaScript) {
                self.stopLoading();

                $('central-content').empty();
                $('central-content').set('html', responseHTML);
                self.loadHistory($('history-list'), uri);

                new TabPane('concept-language-tab-container', {}, false);
                new TabPane('concept-scheme-tab-container', {}, false);

                self.loadedConcept = uri;

                Editor.View.markConceptActive(uri);
            }
        }).get();
    },
    editConcept: function (uri) {
        var self = this;
        self.showLoading();
        new Request.HTML({
            url: BASE_URL + '/editor/concept/edit/uri/' + encodeURIComponent(uri),
            onSuccess: function (responseTree, responseElements, responseHTML, responseJavaScript) {
                self.stopLoading();

                $('central-content').empty();
                $('central-content').set('html', responseHTML);
                Editor.Concept.initConceptForm();
                Editor.View.markConceptActive(uri);
            }
        }).get();
    },
    addConcept: function (name) {
        new Request.HTML({
            url: BASE_URL + '/editor/concept/create/label/' + name,
            onSuccess: function (responseTree, responseElements, responseHTML, responseJavaScript) {

                $('central-content').empty();
                $('central-content').set('html', responseHTML);
                $('conceptSave').addEvent('click', function (e) {
                    e.stop();
                    if (Editor.Concept.confirmDocPropertiesAreSaved()) {
                        Editor.Control.checkNewConcept();
                    }
                });
                Editor.Concept.initConceptForm();
                Editor.View.markConceptActive('no-uuid');
            }
        }).get();
    },
    saveConcept: function () {
        var self = this;
        $('Editconcept').set('send', {
            onSuccess: function (responseHTML) {
                self.stopLoading();

                $('central-content').empty();
                $('central-content').set('html', responseHTML);
                if (null !== $('Editconcept')) {
                    Editor.Concept.initConceptForm();
                } else {
                    Editor.Control.loadHistory($('history-list'), $('concept-view').getElement('#uri').get('value'));
                    new TabPane('concept-language-tab-container', {}, false);
                    new TabPane('concept-scheme-tab-container', {}, false);
                    Editor.Relations.disableRelationLinks();
                }
            },
            onFailure: function (response) {
                // Show the error response
                $('central-content').empty();
                $('central-content').set('html', response.response);
                $('central-content').addClass('error');
            }
        });
        $('Editconcept').send();

        self.showLoading();
    },
    checkNewConcept: function () {

        // Gets the first (the original) pref label.		
        var prefLabelText = Editor.Concept.getFirstPrefLabel();

        if (prefLabelText != '') {
            new Request.JSON({
                url: BASE_URL + "/editor/concept/check-pref-label",
                method: 'post',
                data: {prefLabel: prefLabelText},
                onSuccess: function (result, text) {
                    Editor.View.showCreateConfirmationBox(result.result.doExist);
                }
            }).send();
        } else {
            alert('Please fill "Preferred label".');
        }
    },
    addConceptScheme: function () {
        new Request.HTML({
            url: BASE_URL + '/editor/concept-scheme/create/',
            onSuccess: function (responseTree, responseElements, responseHTML, responseJavaScript) {
                $('central-content').empty();
                $('central-content').set('html', responseHTML);
                Editor.ConceptScheme.initConceptSchemeForm();
            }
        }).get();
    },
    saveConceptScheme: function () {
        $('Editconceptscheme').set('send', {
            onSuccess: function (responseHTML) {
                $('central-content').empty();
                $('central-content').set('html', responseHTML);

                if (null !== $('Editconceptscheme')) {
                    // There are errors.
                    Editor.ConceptScheme.initConceptSchemeForm();
                } else {
                    // The save is successfull.
                    window.location.href = BASE_URL + '/editor/concept-scheme/index/';
                }
            },
            onFailure: function (response) {
                // Show the error response
                $('central-content').empty();
                $('central-content').set('html', response.response);
                $('central-content').addClass('error');
            }
        });
        $('Editconceptscheme').send();

        // Show loading
        $('central-content').empty();
        $('central-content').adopt(new Element('div').addClass('loading').set('text', 'Loading...'));
    },
    clearHistory: function (element) {
        var self = this;
        new Request.JSON({
            url: BASE_URL + "/editor/history/clear-history",
            method: 'get',
            onSuccess: function (result, text) {
                if (result.status === self._statusSuccess) {
                    self.loadHistory(element);
                }
            }
        }).send();
    },
    loadHistory: function (element, forUri) {
        var self = this;
        new Request.JSON({
            url: BASE_URL + "/editor/history",
            method: 'get',
            onSuccess: function (result, text) {
                if (result.status === self._statusSuccess) {
                    Editor.View.emptyContainer(element, '.concept-link');
                    Editor.View.showHistory(result.result, element);

                    if (forUri) {
                        Editor.View.markConceptActive(forUri);
                    }
                }
            }
        }).send();
    },
    showNarrowerRelations: function (conceptLi) {
        var self = this;

        conceptLi.getParent('ul').getElements('.concept-link').each(Editor.Control.hideNarrowerRelations);

        if (conceptLi.getElement('.show-narrower-relations')) {
            conceptLi.getElement('.show-narrower-relations').hide();
        }

        if (conceptLi.getElement('.narrower-relations').get('html') == '') {

            if (conceptLi.getElement('.show-narrower-relations-loading')) {
                conceptLi.getElement('.show-narrower-relations-loading').show();
            }

            new Request.JSON({
                url: BASE_URL + "/editor/concept/get-narrower-relations/",
                data: {uri: conceptLi.getElement('.uri').get('text')},
                onSuccess: function (result, text) {
                    if (result.status === self._statusSuccess) {

                        if (conceptLi.getElement('.show-narrower-relations-loading')) {
                            conceptLi.getElement('.show-narrower-relations-loading').hide();
                        }

                        if (result.result.length > 0) {

                            if (conceptLi.getElement('.hide-narrower-relations')) {
                                conceptLi.getElement('.hide-narrower-relations').show();
                            }
                            conceptLi.getElement('.narrower-relations').show();

                            Editor.View.showSubConcepts(result.result, conceptLi.getParent('.concepts-list'), conceptLi.getElement('.narrower-relations'));

                        } else {
                            if (conceptLi.getElement('.show-narrower-relations-empty')) {
                                conceptLi.getElement('.show-narrower-relations-empty').show();
                            }
                        }
                    }
                }
            }).send();
        } else {
            if (conceptLi.getElement('.hide-narrower-relations')) {
                conceptLi.getElement('.hide-narrower-relations').show();
            }
            conceptLi.getElement('.narrower-relations').show();
        }
    },
    hideNarrowerRelations: function (conceptLi) {

        if (conceptLi.getElement('.narrower-relations').get('html') != '') {
            if (conceptLi.getElement('.show-narrower-relations')) {
                conceptLi.getElement('.show-narrower-relations').show();
            }
            if (conceptLi.getElement('.show-narrower-relations-loading')) {
                conceptLi.getElement('.show-narrower-relations-loading').hide();
            }
            if (conceptLi.getElement('.show-narrower-relations-empty')) {
                conceptLi.getElement('.show-narrower-relations-empty').hide();
            }
            if (conceptLi.getElement('.hide-narrower-relations')) {
                conceptLi.getElement('.hide-narrower-relations').hide();
            }
        }

        conceptLi.getElement('.narrower-relations').hide();
    },
    conceptDeleted: function (response) {
        var data = JSON.decode(response);
        if (data.status == 'ok') {
            var message = $('concept-deleted-successfully').get('text');
            $('central-content').empty();
            $('central-content').adopt(new Element('div').addClass('message').set('text', message));
            Editor.Control.loadHistory($('history-list'));
            Editor.ConceptsSelection.load();
            if (Editor.Search.getSearchResultsCount() > 0) {
                Editor.Search.search();
            }
            SqueezeBox.close();
        } else {
            $('sbox-content').getElement('.errors').set('html', data.message);
        }
    },
    showLoading: function () {
        // Show loading
        this.loadingTimeoutHandle = setTimeout(function () {
            $('central-content').empty();
            $('central-content').adopt(new Element('div').addClass('loading').set('text', 'Loading...'));
        }, 1000);
    },
    stopLoading: function () {
        clearTimeout(this.loadingTimeoutHandle);
    }
});