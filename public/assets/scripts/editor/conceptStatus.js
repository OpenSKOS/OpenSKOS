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
 * Handles some actions for status changes.
 */

var EditorConceptStatus = new Class({
	Binds: ['onStatusChange', 'closeChooseModal', 'chooseConcept', 'chooseConceptOk'],
    
    sboxDefaultStyles: null,
    selectedStatus: null,
    
    statusesWithSecondConcept: ['redirected', 'obsolete'],
    
	initialize: function () {
		
	},
    
    listenForStatusChange: function () {
        $('Editconcept').getElement('#status').addEvent('change', this.onStatusChange);
    },
	
	onStatusChange: function (e) {
        this.selectedStatus = e.target.get('value');
        
        if (this.statusesWithSecondConcept.indexOf(this.selectedStatus) !== -1) {
            this.showChooseModal();
        }
	},
    
    showChooseModal: function () {
        this.conceptChoose = $('status-other-concept').clone();
        
        Editor.View.showActionModal(this.conceptChoose, {size: {x: 300, y: 180}});
        
        this.conceptChoose.getElement('.choose-cancel').addEvent('click', this.closeChooseModal);
        this.conceptChoose.getElement('.choose-ok').addEvent('click', this.chooseConceptOk);
        this.conceptChoose.getElement('.choose-ok').hide();
        
        this.conceptChoose.getElements('.choose-message').hide();
        this.conceptChoose.getElements('.choose-message.' + this.selectedStatus).show();
        
        var sboxOldStyles = $('sbox-overlay').getStyles('width', 'height', 'top', 'left', 'right', 'bottom');
        SqueezeBox.addEvent('close', function() {
            $('sbox-overlay').setStyles(sboxOldStyles);
        });
        
        $('sbox-overlay').setStyles({
            width: 'auto',
            height: 'auto',
            top: 90,
            left: 300,
            right: 300,
            bottom: 30,
        });
        
        this.activateConceptChoose();
    },
    
    closeChooseModal: function () {
        this.deactivateConceptChoose;
        this.conceptChoose = null;
        SqueezeBox.close();
    },
    
    activateConceptChoose: function () {
        Editor.Control.clickConceptCallback = Editor.ConceptStatus.chooseConcept;
    },
    
    deactivateConceptChoose: function () {
        Editor.Control.clickConceptCallback = null;
    },
    
    chooseConcept: function (uuid) {
        this.conceptChoose.getElement('.choose-ok').setStyle('display', 'inline-block');
        this.conceptChoose.getElement('.chosen-concept').show();
        this.chosenConceptUuid = uuid;
        this.conceptChoose.getElement('.chosen-concept-label').set('html', $$('.' + uuid).pick().get('html'));
    },
    
    chooseConceptOk: function () {
        this.conceptForStatusChosen(this.chosenConceptUuid, this.selectedStatus);
        this.closeChooseModal();
    },
    
    conceptForStatusChosen: function (uuid, status) {
        $('Editconcept').getElement('#statusOtherConcept').set('value', uuid);
        
        var chosenConcept = this.conceptChoose.getElement('.chosen-concept').get('html');
        (new Element('span', {'html': chosenConcept})).inject(
            $('Editconcept').getElement('#concept-edit-status'),
            'after'
        );
    }
});