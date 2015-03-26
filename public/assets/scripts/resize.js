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
window.addEvent('domready', function() {
	if ($('left-panel')) {
		makeLeftPanelResizable();
	}
});

/**
 * Hold some global variables for the panel resizing.
 */
var currentLeftPanelWidth = 0;
var minLeftPanelWidth = 170;
var maxLeftPanelWidth = 1200;

/**
 * Makes the left pannel resizable.
 * The concept-link-content, the search box, the schemes select box and profiles select box are resizable by default.
 * 
 * @returns void
 */
function makeLeftPanelResizable()
{
	currentLeftPanelWidth = $('left-panel').getStyle('width').toInt();
	currentLeftPanelHeight = $('left-panel').getStyle('height').toInt();
    
	$('left-panel').makeResizable({
        handle: $('left-panel-resizer'),
        limit: {
            x: [minLeftPanelWidth, maxLeftPanelWidth],
            y: [currentLeftPanelHeight, currentLeftPanelHeight]
        }
    });
	
	// This is needed because of bug in IE8 and IE7
	var subConceptsOffset = 10;
	var initialLeftColumnWidth = $('left-panel').getStyle('width').toInt();
	var initialSearchTextWidth = $('left-panel').getElement('input[name="searchText"]').getStyle('width').toInt();
	if ($('left-panel').getElement('#conceptScheme-element')) {
		var initialConceptSchemeWidth = $('left-panel').getElement('#conceptScheme-element').getStyle('width').toInt();
	} else {
		var initialConceptSchemeWidth = 0;
	}
	if ($('left-panel').getElement('#allowedConceptScheme-element')) {
		var initialAllowedConceptSchemeSchemeWidth = $('left-panel').getElement('#allowedConceptScheme-element').getStyle('width').toInt();
	} else {
		var initialAllowedConceptSchemeSchemeWidth = 0;
	}
	var initialConceptLinkContentWidth = $('left-panel').getElement('.concept-link-content').getStyle('width').toInt();
	if ($('left-panel').getElement('#searchProfileId-element select')) {
		var initialProfilesSelectWidth = $('left-panel').getElement('#searchProfileId-element select').getStyle('width').toInt();
	}
	
	$('left-panel').addEvent('resize', function (e) {
		$('left-panel').setStyles({height: 'auto'});
        
		var newWidth = $('left-panel').getStyle('width').toInt();
		var additonalWitdh = newWidth - currentLeftPanelWidth;
		
		if (newWidth < minLeftPanelWidth) {
			$('left-panel').setStyles({width: currentLeftPanelWidth});
			return false;
		}
		
        $('central-panel').setStyle('left', newWidth);
		
		$('left-panel').getElements('.concept-link-content').each(function (el) {
			el.setStyle('width', initialConceptLinkContentWidth + newWidth - initialLeftColumnWidth - (el.getParents('ul.narrower-relations').length * subConceptsOffset));
		});
		
		$('left-panel').getElements('input[name="searchText"]').each(function (el) {			
			el.setStyle('width', initialSearchTextWidth + newWidth - initialLeftColumnWidth);
			if (el.getStyle('background-position')) {
				el.setStyle('background-position', (el.getStyle('background-position').toInt() + additonalWitdh) + 'px center');
			}
		});
		
		$('left-panel').getElements('#conceptScheme-element').each(function (el) {
			el.setStyle('width', initialConceptSchemeWidth + newWidth - initialLeftColumnWidth);
		});
		
		$('left-panel').getElements('#conceptScheme-element').each(function (el) {
			el.setStyle('width', initialConceptSchemeWidth + newWidth - initialLeftColumnWidth + 12); // +12 for scroller.
		});
		
		$('left-panel').getElements('#allowedConceptScheme-element').each(function (el) {
			el.setStyle('width', initialAllowedConceptSchemeSchemeWidth + newWidth - initialLeftColumnWidth);
		});
		
		$('left-panel').getElements('#allowedConceptScheme-element').each(function (el) {
			el.setStyle('width', initialAllowedConceptSchemeSchemeWidth + newWidth - initialLeftColumnWidth + 12); // +12 for scroller.
		});
		
		$('left-panel').getElements('#searchProfileId-element select').each(function (el) {
			el.setStyle('width', initialProfilesSelectWidth + newWidth - initialLeftColumnWidth);
		});
		
		currentLeftPanelWidth = newWidth;
	});
}