<?php

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

class Editor_Forms_Label extends OpenSKOS_Form
{
    public function init()
    {
        $this
            ->setName('editlabel')
            ->setAction(
                Zend_Controller_Front::getInstance()->getRouter()->assemble(
                    ['controller' => 'label', 'action' => 'save']
                )
            )
            ->setMethod('post');
        
        $this
            ->buildInputs()
            ->buildButtons();
    }
    
    protected function buildInputs()
    {
        $this->addElement('hidden', 'uri', [
            'decorators' => array()
        ]);
        
        $this->addElement('text', 'language', [
            'label' => 'Language',
            'decorators' => array(),
            'readonly' => true,
        ]);
                
        $this->addElement('text', 'literalForm', [
            'label' => 'Literal form',
            'filters' => array('StringTrim'),
        ]);
        return $this;
    }
    
    protected function buildButtons()
    {
        $this->addElement('submit', 'okButton', array(
                'label' => 'Ok',
                'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'span','closeOnly' => true)))
        ));
        return $this;
    }
    
    /**
     * @return Editor_Forms_Label
     */
    public static function getInstance()
    {
        static $instance;
        
        if (null === $instance) {
            $instance = new Editor_Forms_Label();
        }
        
        return $instance;
    }
}
