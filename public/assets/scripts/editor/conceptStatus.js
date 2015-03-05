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
 * @copyright  Copyright (c) 2015 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

/**
 * 
 */

var EditorConceptStatus = new Class({
	Binds: ['onStatusChange', 'chooseConcept'],
    
	initialize: function () {
		
	},
    
    listenForStatusChange: function () {
        $('Editconcept').getElement('#status').addEvent('change', this.onStatusChange);
    },
	
	onStatusChange: function (e) {
        this.activateConceptChoose();
	},
    
    activateConceptChoose: function () {
        Editor.Control.clickConceptCallback = Editor.ConceptStatus.chooseConcept;
    },
    
    deactivateConceptChoose: function () {
        Editor.Control.clickConceptCallback = null;
    },
    
    chooseConcept: function (uuid) {
        console.log(uuid);
        
        this.deactivateConceptChoose();
    }
});