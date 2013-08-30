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

class Editor_JobsController extends OpenSKOS_Controller_Editor
{
	public function indexAction()
	{
		$this->_requireAccess('editor.jobs', 'index');
		
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
		$this->_requireAccess('editor.jobs', 'index');
		
		$job = $this->_getJob();
		
		$this->view->assign('job', $job);
		$this->view->assign('collection', $job->getCollection());
		$this->view->assign('user', $job->getUser());
	}
	
	public function deleteAction()
	{
		$this->_requireAccess('editor.jobs', 'manage');
		
		if ($this->getRequest()->isPost()) {
			$ids = $this->getRequest()->getParam('job');
			$jobs = array();
			foreach ($ids as $id) {
				$jobs[] = $this->_getJob((int)$id);
			}
		} else {
			$jobs = array($this->_getJob());
		}
		foreach ($jobs as $job) {
			try {
				$job->delete();
			} catch (Zend_Db_Table_Row_Exception $e) {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage($e->getMessage());
				$this->_helper->redirector('index');
			}
		}
		if (count($jobs)>1) {
			$this->getHelper('FlashMessenger')->addMessage(_('Jobs removed'));
		} else {
			$this->getHelper('FlashMessenger')->addMessage(_('Job removed'));
		}
		$this->_helper->redirector('index');
	}
	
	public function downloadExportAction()
	{
		$this->_requireAccess('editor.jobs', 'index');
		
		$job = $this->_getJob();
		
		$export = new Editor_Models_Export();
		$export->setSettings($job->getParams());
		
		$fileDetails = $export->getExportFileDetails();
		$filePath = $export->getExportFilesDirPath() . $job->info;
		
		$this->getHelper('file')->sendFile($fileDetails['fileName'], $filePath, $fileDetails['mimeType']);
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_Job
	 */
	protected function _getJob($id = null)
	{
		if (null === $id) {
			if (null === ($id = $this->getRequest()->getParam('job'))) {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('No job defined'));
				$this->_helper->redirector('index');
			}
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

