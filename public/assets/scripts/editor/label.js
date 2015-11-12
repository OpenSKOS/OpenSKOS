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
        'labelAdded'
    ],
    addingToContainer: null,
    initialize: function () {
        var self = this;
        $(document.body).addEvent('click:relay(.multi-field-skos-xl-label .skos-xl-label-add)', function (ev) {
            ev.stop();
            self.addingToContainer = ev.target.getParent('.multi-field-skos-xl-label');
            SqueezeBox.open(ev.target.getProperty('href'), {size: {x: 450, y: 500}, handler: 'iframe'});
        });
        $(document.body).addEvent('click:relay(.multi-field-skos-xl-label .skos-xl-label-edit)', function (ev) {
            ev.stop();
            var uri = ev.target.getParent('.skos-xl-label').getElement('.uri').get('value');
            var href = ev.target.getProperty('href') + '/uri/' + encodeURIComponent(uri);
            SqueezeBox.open(href, {size: {x: 450, y: 500}, handler: 'iframe'});
        });
        $(document.body).addEvent('click:relay(.multi-field-skos-xl-label .skos-xl-label-remove)', function (ev) {
            ev.stop();
            ev.target.getParent('.skos-xl-label').dispose();
        });
    },
    addLabel: function (uri, literalForm, language) {
        if (!this.addingToContainer.getElement('.uri[value="' + uri + '"')) {
            var newElement = this.addingToContainer.getElement('.template').clone();
            newElement.removeClass('template');
            this.populateElement(newElement, uri, literalForm, language);
            this.addingToContainer.adopt(newElement);
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
    }
});
