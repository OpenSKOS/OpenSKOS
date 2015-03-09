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
    oldSelectedStatus: null,
    
    statusesWithSecondConcept: ['redirected', 'obsolete'],
    statusesWithNoReturn: ['rejected', 'deleted'],
    
	initialize: function () {
		
	},
    
    listenForStatusChange: function () {
        var statusEl = $('Editconcept').getElement('#status');        
        statusEl.addEvent('change', this.onStatusChange);        
        this.selectedStatus = statusEl.get('value');
    },
	
	onStatusChange: function (e) {
        this.oldSelectedStatus = this.selectedStatus;
        this.selectedStatus = e.target.get('value');
        
        if (this.statusesWithNoReturn.indexOf(this.selectedStatus) !== -1) {
            this.showConfirmationModal();
        }
        
        if (this.statusesWithSecondConcept.indexOf(this.selectedStatus) !== -1) {
            this.showChooseModal();
        } else {
            $('Editconcept').getElement('#statusOtherConcept').set('value', '');
            if ($('Editconcept').getElement('.concept-edit-status-other-concept') !== null) {
                $('Editconcept').getElement('.concept-edit-status-other-concept').dispose();
            }
        }
	},
    
    showConfirmationModal: function () {
        this.conceptConfirmation = $('status-confirmation').clone();
        
        Editor.View.showActionModal(this.conceptConfirmation, {size: {x: 300, y: 90}});
        
        var self = this;
        var closeConfirmation = function () {
            SqueezeBox.close();
        };
        var isOk = false;
        this.conceptConfirmation.getElement('.confirmation-cancel').addEvent('click', closeConfirmation);
        this.conceptConfirmation.getElement('.confirmation-ok').addEvent('click', function () {
            isOk = true;
            closeConfirmation();
        });
        
        SqueezeBox.addEvent('close', function() {
            if (!isOk) {
                $('Editconcept').getElement('#status').set('value', self.oldSelectedStatus);
                self.selectedStatus = self.oldSelectedStatus;
            }
        });
    },
    
    showChooseModal: function () {
        this.conceptChoose = $('status-other-concept').clone();
        
        Editor.View.showActionModal(this.conceptChoose, {size: {x: 300, y: 140}});
        
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
        this.deactivateConceptChoose();
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
        if ($('Editconcept').getElement('.concept-edit-status-other-concept') !== null) {
            $('Editconcept').getElement('.concept-edit-status-other-concept').dispose();
        }
        (new Element('span', {'html': chosenConcept, 'class': 'concept-edit-status-other-concept'})).inject(
            $('Editconcept').getElement('#concept-edit-status'),
            'after'
        );
    }
});