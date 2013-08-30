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
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2011 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Mark Lindeman
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

class Editor_Forms_Login extends Zend_Form
{
   public function init() {
        $this->setName("sign");
        $this->setMethod('post');
        $this->addElement('text', 'tenant', array(
            'filters' => array('StringTrim', 'StringToLower'),
            'validators' => array(array('StringLength', false, array(2, 10))),
            'required' => true,
            'label' => _('Institution code'),
        ));
        $this->addElement('text', 'username', array(
            'filters' => array('StringTrim', 'StringToLower'),
            'validators' => array(array('EmailAddress')),
            'required' => true,
            'label' => _('E-mail'),
        ));
        $this->addElement('password', 'password', array(
            'filters' => array('StringTrim'),
            'validators' => array(array('StringLength', false, array(4, 30))),
            'required' => true,
            'label' => _('Password'),
        ));
        $this->addElement('checkbox', 'rememberme', array('label' => _('Remember me')));
        $this->getElement('rememberme')->setChecked(true);
        $this->addElement('submit', 'login', array(
            'required' => false,
            'ignore' => true,
            'label' => _('Login'),
        ));
        
    }
    
    /**
	 * @return Editor_Forms_Login
	 */
	public static function getInstance()
	{
		static $instance;
		
		if (null === $instance) {
			$instance = new Editor_Forms_Login();
		}
		
		return $instance;
	}
}