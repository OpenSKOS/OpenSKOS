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

class Api_InstitutionsController extends OpenSKOS_Rest_Controller
{
	public function init()
	{
		parent::init();
		$this->_helper->contextSwitch()
			->initContext($this->getRequest()->getParam('format', 'rdf'));
		
		if('html' == $this->_helper->contextSwitch()->getCurrentContext()) {
			//enable layout:
			$this->getHelper('layout')->enableLayout();
		}
	}
	
	public function indexAction()
	{
		$model = new OpenSKOS_Db_Table_Tenants();
		$context = $this->_helper->contextSwitch()->getCurrentContext();
		if ($context == 'json' || $context == 'jsonp') {
			$this->view->assign('institutions', $model->fetchAll()->toArray());
		} else {
			$this->view->tenants = $model->fetchAll();
		}
	}
	
	public function getAction()
	{
		$model = new OpenSKOS_Db_Table_Tenants();
		$code = $this->getRequest()->getParam('id');
		$tenant = $model->find($code)->current();
		if (null===$tenant) {
			throw new Zend_Controller_Action_Exception('Insitution `'.$code.'` not found', 404);
		}
		$context = $this->_helper->contextSwitch()->getCurrentContext();
		if ($context == 'json' || $context == 'jsonp') {
			foreach ($tenant as $key => $val) {
				$this->view->assign($key, $val);
			}
			$this->view->assign('collections', $tenant->findDependentRowset('OpenSKOS_Db_Table_Collections')->toArray());
		} else {
			$this->view->assign('tenant', $tenant);
		}
	}
	
	public function postAction()
	{
		$this->_501('post');
	}
	
	public function putAction()
	{
		$this->_501('put');
	}
	
	public function deleteAction()
	{
		$this->_501('delete');
	}
	
}