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

class Api_CollectionsController extends OpenSKOS_Rest_Controller
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
		$model = new OpenSKOS_Db_Table_Collections();
		$context = $this->_helper->contextSwitch()->getCurrentContext();
		$select = $model->select();
		if (null !== ($allow_oai = $this->getRequest()->getParam('allow_oai'))) {
			switch (strtolower($allow_oai)) {
				case '1':
				case 'yes':
				case 'y':
				case 'true':
					$select->where('allow_oai=?', 'Y');
					break;
				case '0':
				case 'no':
				case 'n':
				case 'false':
					$select->where('allow_oai=?', 'N');
					break;
			}
		}
		if ($context == 'json' || $context == 'jsonp') {
			$this->view->assign('collections', $model->fetchAll($select)->toArray());
		} else {
			$this->view->collections = $model->fetchAll($select);
		}
	}
	
	public function getAction()
	{
		$modelTenant = new OpenSKOS_Db_Table_Tenants();
		$id = $this->getRequest()->getParam('id');
		list($tenantCode, $collectionCode) = explode(':', $id);
		$tenant = $modelTenant->find($tenantCode)->current();
		if (null===$tenant) {
			throw new Zend_Controller_Action_Exception('Insitution `'.$tenantCode.'` not found', 404);
		}
		
		$modelCollections = new OpenSKOS_Db_Table_Collections();
		$collection = $tenant->findDependentRowset(
			'OpenSKOS_Db_Table_Collections', null, 
			$modelCollections->select()->where('code=?', $collectionCode)
		)->current();
		if (null===$collection) {
			throw new Zend_Controller_Action_Exception('Collection `'.$id.'` not found', 404);
		}
		
		$context = $this->_helper->contextSwitch()->getCurrentContext();
		if ($context == 'json' || $context == 'jsonp') {
			foreach ($collection as $key => $val) {
				$this->view->assign($key, $val);
			}
		} else {
			$this->view->assign('tenant', $tenant);
			$this->view->assign('collection', $collection);
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