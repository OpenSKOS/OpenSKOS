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
 * @copyright  Copyright (c) 2012 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

class Editor_Forms_Delete extends Zend_Form
{
	public function init() 
	{
		$this->setName('deleteform')
		->setAction(Zend_Controller_Front::getInstance()->getRouter()->assemble(array('controller'=>'concept', 'action' => 'delete')))
		->setMethod('post');

		$this->buildHiddenInputs()
		->buildButtons();
	}
		
	protected function buildHiddenInputs()
	{
		$this->addElement('hidden', 'uuid', array(
				'filters' => array('StringTrim'),
				'label' => '',
		));
		return $this;
	}
	
	protected function buildButtons()
	{
		$this->addElement('submit', 'deleteButton', array(
				'label' => 'Delete'
		));
		return $this;
	}
	
	/**
	 * @return Editor_Forms_Export
	 */
	public static function getInstance()
	{
		static $instance;
		
		if (null === $instance) {
			$instance = new Editor_Forms_Delete();
		}
		
		return $instance;
	}
}