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

var EditorConceptSchemesIcons = new Class({
	Bind: ['deleteIcon'],
	initialize: function () {
		var self = this;
		
		if ($('manage-icons-list')) {
			$('manage-icons-list').getElements('img').each(function (el) { 
				el.addEvent('click', function(e) { e.stop(); self.deleteIcon(e.target.get('id'))});
			});
		}
		
		if ($('concept-schemes-list')) {
			$('concept-schemes-list').getElements('.assign-icon').each(function (el) { 
				el.addEvent('click', function(e) { 
					e.stop(); 
					self.showAssignIconBox(e.target.getParent('a').get('id'));
				});
			});
		}
		
		if ($('assign-icons-box')) {
			$(document.body).addEvent("click:relay(.assign-icons-box img)", function (e) {
				e.stop(); 
				self.assignIcon(e.target.getParent('.assign-icons-box').getElement('.schemeUuid').get('value'), e.target.get('class'));
			});
		}
	}, 
	deleteIcon: function (iconFile) {
		if (confirm($('delete-confirmation-message').get('text'))) {
			new Request.JSON({
				url: BASE_URL + "/editor/concept-scheme/delete-icon", 
				method: 'post',
				data: {iconFile: iconFile},
				onSuccess: function(result, text) {
					$(iconFile).hide();
				}
			}).send();
		}
	},
	showAssignIconBox: function (schemeUuid) {
		var assignBox = $('assign-icons-box').clone().removeClass('do-not-show');
		assignBox.getElement('.schemeUuid').set('value', schemeUuid);
		SqueezeBox.open(assignBox, {size: {x: 550, y: 250}, handler: 'adopt'});
	},
	assignIcon: function (schemeUuid, iconFile) {
		new Request.JSON({
			url: BASE_URL + "/editor/concept-scheme/assign-icon", 
			method: 'post',
			data: {schemeUuid: schemeUuid, iconFile: iconFile},
			onSuccess: function(result, text) {
				SqueezeBox.close();
				$(schemeUuid).empty();
				var icon = new Element('img', {src: result.result.newIconPath + '?nocache=' + (new Date().getTime())});
				icon.inject($(schemeUuid));
			}
		}).send();
	}
});

window.addEvent('load', function() {
	new EditorConceptSchemesIcons();
});