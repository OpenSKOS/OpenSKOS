<?php

class Dashboard_JobsController extends OpenSKOS_Controller_Dashboard
{
	public function indexAction()
	{
		$select = Zend_Db_Table::getDefaultAdapter()->select()
			->from('job')
			->join('user', 'user.id=job.user', array('user' => 'name'))
			->join('collection', 'collection.id=job.collection', array('collection' => 'dc_title'))
			->where('collection.tenant=?', $this->_tenant->code)
			->order('created desc')
			->order('started asc');
		if (null!== ($this->view->hideFinishedJobs = $this->getRequest()->getParam('hide-finished-jobs'))) {
			$select->where('finished IS NULL');
		}
		$this->view->assign('jobs', new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select)));
	}
	
	public function viewAction()
	{
		$job = $this->_getJob();
		$this->view->assign('job', $job);
		$this->view->assign('collection', $job->getCollection());
		$this->view->assign('user', $job->getUser());
	}
	
	public function deleteAction()
	{
		$job = $this->_getJob();
		try {
			$job->delete();
		} catch (Zend_Db_Table_Row_Exception $e) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage($e->getMessage());
			$this->_helper->redirector('index');
		}
		$this->getHelper('FlashMessenger')->addMessage(_('Job removed'));
		$this->_helper->redirector('index');
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_Job
	 */
	protected function _getJob()
	{
		if (null === ($id = $this->getRequest()->getParam('job'))) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('No job defined'));
			$this->_helper->redirector('index');
		}
		$model = new OpenSKOS_Db_Table_Jobs();
		$job = $model->find($id)->current();
		if (null === $job) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Job not found'));
			$this->_helper->redirector('index');
		}
		
		$collection = $job->getCollection();
		if (null === $collection) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Collection not found'));
			$this->_helper->redirector('index');
		}
		if ($collection->tenant != $this->_tenant->code) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('You are not allowed to edit this job.'));
			$this->_helper->redirector('index');
		} 
		return $job;
	}
	
}

