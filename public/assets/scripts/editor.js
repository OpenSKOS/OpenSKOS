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
var Editor = {};

window.addEvent('load', function() {
	Editor.Url = new EditorUrl();
	Editor.Shortcuts = new EditorShortcuts();
	Editor.Control = new EditorControl();
	Editor.View = new EditorView();
	Editor.Concept = new EditorConcept();
	Editor.ConceptScheme = new EditorConceptScheme();
	Editor.Relations = new EditorRelations();
	Editor.Search = new EditorSearch($('searchform'), $('search-results'));
	Editor.ConceptsSelection = new EditorConceptsSelection($('selection-list'));
	
	Editor.Url.read();
	
	new TabPane(
		'right-panel-tab-container',
		{}, 
		function() {
			var showTab = window.location.hash.match(/tab=(\d+)/);
			return showTab ? showTab[1] : 0;
		});
	
	// Attach logout function to logout link.
	$('logout').addEvent('click', function (e) {
		e.preventDefault();
		logout();
	});
	
	// Implement buttons hover.
	$(document.body).addEvent('mouseover:relay(button, input[type=button], input[type=submit])', function (e) {
		e.target.addClass('hover');
	});
	$(document.body).addEvent('mouseout:relay(button, input[type=button], input[type=submit])', function (e) {
		e.target.removeClass('hover');
	});
});

window.addEvent('hashchange', function() {
	if ( ! Editor.Url.lastHashChangeIsManual) {
		Editor.Url.read();
	}
	Editor.Url.lastHashChangeIsManual = false;
});