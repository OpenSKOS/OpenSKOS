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

class Editor_ConceptsSelectionController extends OpenSKOS_Controller_Editor
{
	public function addAction()
	{
		$user = OpenSKOS_Db_Table_Users::fromIdentity();
		if (null === $user) {
			throw new Zend_Controller_Action_Exception('User not found', 404);
		}
		
		$addingResult = $user->addConceptsToSelection($this->getRequest()->getPost('uuids'));
		
		if ($addingResult) {
			$selection = $user->getConceptsSelection();
			$this->getHelper('json')->sendJson(array('status' => 'ok', 'result' => $this->_prepareSelectionData($selection)));
		} else {
			$this->getHelper('json')->sendJson(array('status' => 'limitReached', 'limit' => OpenSKOS_Db_Table_Row_User::USER_SELECTION_SIZE));
		}
	}
	
	public function getAllAction()
	{
		$user = OpenSKOS_Db_Table_Users::fromIdentity();
		if (null === $user) {
			throw new Zend_Controller_Action_Exception('User not found', 404);
		}
		
		$selection = $user->getConceptsSelection();
		$this->getHelper('json')->sendJson(array('status' => 'ok', 'result' => $this->_prepareSelectionData($selection)));
	}
	
	public function clearAction()
	{
		$user = OpenSKOS_Db_Table_Users::fromIdentity();
		if (null === $user) {
			throw new Zend_Controller_Action_Exception('User not found', 404);
		}
		$user->clearConceptsSelection();
		$this->getHelper('json')->sendJson(array('status' => 'ok'));
	}
	
	public function removeAction()
	{
		$user = OpenSKOS_Db_Table_Users::fromIdentity();
		if (null === $user) {
			throw new Zend_Controller_Action_Exception('User not found', 404);
		}
		$user->removeConceptFromSelection($this->getRequest()->getPost('uuid'));
		
		$selection = $user->getConceptsSelection();
		$this->getHelper('json')->sendJson(array('status' => 'ok', 'result' => $this->_prepareSelectionData($selection)));
	}
	
	protected function _prepareSelectionData($selection)
	{
		$data = array();
		foreach ($selection as $concept) {
			$data[] = $concept->toArray(array('uuid', 'uri', 'status', 'schemes', 'previewLabel', 'previewScopeNote'));
		}
		return $data;
	}
}