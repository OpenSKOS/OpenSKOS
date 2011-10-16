<?php

class Dashboard_JobsController extends OpenSKOS_Controller_Dashboard
{
	public function indexAction()
	{
		$select = Zend_Db_Table::getDefaultAdapter()->select()
			->from('job')
			->join('user', 'user.id=job.user', array('user' => 'name'))
			->join('collection', 'collection.id=job.collection', array('collection' => 'dc_title'))
			->where('finished IS NULL')
			->order('created desc')
			->order('started asc');
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
		$this->getHelper('FlashMessenger')->addMessage('Job removed');
		$this->_helper->redirector('index');
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_Job
	 */
	protected function _getJob()
	{
		if (null === ($id = $this->getRequest()->getParam('job'))) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage('No job defined');
			$this->_helper->redirector('index');
		}
		$model = new OpenSKOS_Db_Table_Jobs();
		$job = $model->find($id)->current();
		if (null === $job) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage('Job not found');
			$this->_helper->redirector('index');
		}
		
		$collection = $job->getCollection();
		if (null === $collection) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage('Collection not found');
			$this->_helper->redirector('index');
		}
		if ($collection->tenant != $this->_tenant->code) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage('It is not very nice to remove other people\'s jobs, is it?');
			$this->_helper->redirector('index');
		} 
		return $job;
	}
	
}

