<?php
class OpenSKOS_Controller_Dashboard extends Zend_Controller_Action
{
	/**
	 * @var $_tenant OpenSKOS_Db_Table_Row_Tenant
	 */
	protected $_tenant;
	
	public function init()
	{
		if ($this->getRequest()->isPost()) {
			if (null!==$this->getRequest()->getParam('cancel')) {
				$this->_helper->redirector('index');
			}
		}
		$tenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
		if (null === $tenant) {
			throw new Zend_Controller_Action_Exception('Tenant not found', 404);
		}
		$tenant->getForm()->setAction($this->getFrontController()->getRouter()->assemble(array('action'=>'save')));
		$this->_tenant = $tenant;
	}
}