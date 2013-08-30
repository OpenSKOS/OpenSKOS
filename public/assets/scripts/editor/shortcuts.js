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

EditorShortcuts = new Class({	 
	// Currently all shortcuts are ctrl+{key} and are for edit a concept mode.
	shortcutsMap: {
		u: function () {Editor.Concept.setApproved();},
		1: function () {Editor.Relations.addMultipleRelations(Editor.ConceptsSelection.getUuids(), 'broader')},
		2: function () {Editor.Relations.addMultipleRelations(Editor.ConceptsSelection.getUuids(), 'narrower')},
		3: function () {Editor.Relations.addMultipleRelations(Editor.ConceptsSelection.getUuids(), 'related')},
		4: function () {Editor.Relations.addMultipleRelations(Editor.ConceptsSelection.getUuids(), 'exactMatch')},
		5: function () {Editor.Relations.addMultipleRelations(Editor.ConceptsSelection.getUuids(), 'closeMatch')},
		6: function () {Editor.Relations.addMultipleRelations(Editor.ConceptsSelection.getUuids(), 'broadMatch')},
		7: function () {Editor.Relations.addMultipleRelations(Editor.ConceptsSelection.getUuids(), 'narrowMatch')},
		8: function () {Editor.Relations.addMultipleRelations(Editor.ConceptsSelection.getUuids(), 'relatedMatch')},
		s: function () {Editor.Control.saveConcept();}
	},
	
	// Handle shortcuts
	Binds: ['keydown', 'keyup'],
	isControlPressed: false,
	initialize: function () {
		window.addEvent('keydown', this.keydown);
		window.addEvent('keyup', this.keyup);
	},
	keydown: function (e) {
		if (e.key == 'control') {
			this.isControlPressed = true;
		} else if (this.isControlPressed && this.shortcutsMap[e.key] !== undefined) {
			if (Editor.Concept.isInEditMode()) {
				e.stop();
				this.shortcutsMap[e.key]();
			}
			this.isControlPressed = false;
		}
	},
	keyup: function (e) {
		this.isControlPressed = false;
	}
});