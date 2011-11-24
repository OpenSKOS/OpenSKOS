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

class Dashboard_UsersController extends OpenSKOS_Controller_Dashboard
{
	public function indexAction()
	{
		$this->view->users = $this->_tenant->findDependentRowset('OpenSKOS_Db_Table_Users');
	}
	
	public function editAction()
	{
		$this->view->assign('user', $this->_getUser());
	}

	public function saveAction()
	{
		if (!$this->getRequest()->isPost()) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('No POST data recieved'));
			$this->_helper->redirector('index');
		}
		$user = $this->_getuser();
		
		if (null!==$this->getRequest()->getParam('delete')) {
			if (!$user->id) {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('You can not delete an empty user.'));
				$this->_helper->redirector('index');
			}
			
			if ($user->id == Zend_Auth::getInstance()->getIdentity()->id) {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('You can not delete yourself.'));
				$this->_helper->redirector('index');
			}
			
			$user->delete();
			$this->getHelper('FlashMessenger')->addMessage(_('The user has been deleted.'));
			$this->_helper->redirector('index');
		}
		
		$form = $user->getForm();
		if (!$form->isValid($this->getRequest()->getParams())) {
			return $this->_forward('edit');
		} else {
			$user
				->setFromArray($form->getValues())
				->setFromArray(array('tenant' => $this->_tenant->code));
			if ($pw =$form->getValue('pw1')) {
				$user->setPassword($pw);
			}
			try {
				$user->save();
			} catch (Zend_Db_Statement_Exception $e) {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage($e->getMessage());
				return $this->_forward('edit');
			}
			$this->getHelper('FlashMessenger')->addMessage(_('Data saved'));
			$this->_helper->redirector('index');
		}
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_User
	 */
	protected function _getUser()
	{
		$model = new OpenSKOS_Db_Table_Users();
		if (null === ($id = $this->getRequest()->getParam('user'))) {
			//create a new collection:
			$user = $model->createRow(array('tenant' => $this->_tenant->code));
		} else {
			$user = $model->find((int)$id)->current();
			if (null === $user) {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('User not found'));
				$this->_helper->redirector('index');
			}
		}
		
		if ($user->tenant != $this->_tenant->code) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('You are not allowed to edit this user.'));
			$this->_helper->redirector('index');
		}
		return $user;
	}
	
}