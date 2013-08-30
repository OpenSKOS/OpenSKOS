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

class Editor_InstitutionController extends OpenSKOS_Controller_Editor
{
	public function indexAction()
	{
		$this->_requireAccess('editor.institution');
		
		$this->view->assign('tenant', $this->_tenant);
	}
	
	public function saveAction()
	{
		$this->_requireAccess('editor.institution');
		
		if (!$this->getRequest()->isPost()) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('No POST data recieved'));
			$this->_helper->redirector('index');
		}
		$form = $this->_tenant->getForm();
		if (!$form->isValid($this->getRequest()->getParams())) {
			return $this->_forward('index');
		} else {
			$this->_tenant->setFromArray($form->getValues())->save();
			$this->getHelper('FlashMessenger')->addMessage(_('Data saved'));
			$this->_helper->redirector('index');
		}
	}
	
}

