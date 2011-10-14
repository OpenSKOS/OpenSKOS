<?php

class Dashboard_InstitutionController extends OpenSKOS_Controller_Dashboard
{
	public function indexAction()
	{
		$tenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
		if (null === $tenant) {
			throw new Zend_Controller_Action_Exception('Tenant not found', 404);
		}
		$this->view->assign('tenant', $tenant);
	}

}

