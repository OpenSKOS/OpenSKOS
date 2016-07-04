/**
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

var EditorLabel = new Class({
    Binds: [
        'labelAdded',
        'showLabelsPerLanguageTab',
        'autocompleteListLabels',
    ],
    addingToContainer: null,
    initialize: function () {
        var self = this;
        $(document.body).addEvent('click:relay(.multi-field-skos-xl-label .skos-xl-label-add)', function (ev) {
            ev.stop();
            self.addingToContainer = ev.target.getParent('.multi-field-skos-xl-label');
            var href = ev.target.getProperty('href');
            href += '?language=' + Editor.Concept.getCurrentLanguage();
            SqueezeBox.open(href, {size: {x: 400, y: 330}, handler: 'iframe'});
        });
        $(document.body).addEvent('click:relay(.multi-field-skos-xl-label .skos-xl-label-edit)', function (ev) {
            ev.stop();
            var uri = ev.target.getParent('.skos-xl-label').getElement('.uri').get('value');
            var href = ev.target.getProperty('href') + '/uri/' + encodeURIComponent(uri);
            SqueezeBox.open(href, {size: {x: 400, y: 330}, handler: 'iframe'});
        });
        $(document.body).addEvent('click:relay(.multi-field-skos-xl-label .skos-xl-label-remove)', function (ev) {
            ev.stop();
            ev.target.getParent('.skos-xl-label').dispose();
            self.ensureOnePrefLabelPerLanguage();
        });
    },
    showLabelsPerLanguageTab: function () {
        var language = Editor.Concept.getCurrentLanguage();
        var container = $('concept-edit-language-skos-xl-labels');
        
        if (container) {
            container.getElements('.skos-xl-label:not(.' + language + ')').hide();
            container.getElements('.skos-xl-label.' + language).show();

            this.ensureOnePrefLabelPerLanguage();
        }
    },
    addLabel: function (uri, literalForm, language) {
        if (!this.addingToContainer.getElement('.uri[value="' + uri + '"')) {
            var newElement = this.addingToContainer.getElement('.template').clone();
            newElement.removeClass('template');
            this.populateElement(newElement, uri, literalForm, language);
            this.addingToContainer.adopt(newElement);
            this.showLabelsPerLanguageTab();
            this.ensureOnePrefLabelPerLanguage();
        }
    },
    editLabel: function (uri, literalForm, language) {
        var self = this;
        $$('.multi-field-skos-xl-label .uri[value="' + uri + '"').each(function (e) {
            self.populateElement(e.getParent('.skos-xl-label'), uri, literalForm, language);
        });
    },
    populateElement: function (element, uri, literalForm, language) {
        element.getElement('.uri').set('value', uri);
        element.getElement('.literalForm').set('html', literalForm);
        if (!element.hasClass(language)) {
            element.addClass(language);
        }
    },
    initAutocomplete: function (formEl, listEl) {
        var self = this;
        formEl.set('send', {
            noCache: true,
            onComplete: function (response) {
                if (JSON.validate(response)) {
                    var data = JSON.decode(response);
                    if (data.status === 'ok') {
                        self.autocompleteListLabels(listEl, data['labels']);
                    }
                }
                // @TODO Show error
            }
        });
        
        formEl.getElement('[name=query]').addEvent('keyup', function () {
            formEl.send();
        });
        
        formEl.send();
    },
    autocompleteListLabels: function (listEl, labels) {
        var self = this;
        listEl.getElements('.item:not(.template)').each(function (el) {
            el.dispose();
        });
        
        var template = listEl.getElement('.template');
        labels.each(function (label) {
            var newElement = template.clone();
            
            newElement.removeClass('template');
            newElement.getElement('.link').addEvent('click', function () {
                self.addLabel(
                    label['uri'],
                    label['literalForm'],
                    label['language']
                );
                SqueezeBox.close();
            });
            newElement.getElement('.literalForm').set('html', label['literalForm']);
            
            listEl.adopt(newElement);
        });
    },
    ensureOnePrefLabelPerLanguage: function () {
        var language = Editor.Concept.getCurrentLanguage();
        var container = $('concept-edit-language-skos-xl-labels');
        
        if (container.getElement('.skosXlPrefLabel .skos-xl-label.' + language)) {
            container.getElement('.skosXlPrefLabel .skos-xl-label-add').hide();
        } else {
            container.getElement('.skosXlPrefLabel .skos-xl-label-add').show();
        }
    }
});
