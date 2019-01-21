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

use \OpenSkos2\Namespaces\OpenSkos;

class Editor_JobsController extends OpenSKOS_Controller_Editor
{
    public function indexAction()
    {
        $this->_requireAccess('editor.jobs', 'index');

        $di = $this->getDI();
        $tenantManager = $di->get('OpenSkos2\TenantManager');
        $tenantUri = $this->_tenant->getUri();
        $setsForTenant = $tenantManager->fetchSetUrisForTenant($this->_tenant->getCode());

        $select = Zend_Db_Table::getDefaultAdapter()->select()
            ->from('job')
            ->join('user', 'user.id=job.user', array('user' => 'name'))
            ->where('set_uri IN (?)', $setsForTenant)
            ->order('created desc')
            ->order('started asc');
        if (null !== ($this->view->hideFinishedJobs = $this->getRequest()->getParam('hide-finished-jobs'))) {
            $select->where('finished IS NULL');
        }
        $this->view->assign('jobs', new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($select)));
    }

    public function viewAction()
    {
        $this->_requireAccess('editor.jobs', 'index');

        $job = $this->_getJob();

        $this->view->assign('job', $job);
        $set = $this->_getSet($job);
        $this->view->assign('set', $set);
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
        if (count($jobs) > 1) {
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

        if(file_exists($filePath)){
            $this->getHelper('file')->sendFile($fileDetails['fileName'], $filePath, $fileDetails['mimeType']);
        } else {
            $this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(
                _('The export file for this job is no longer available')
            );
            return $this->_helper->redirector('view', 'jobs', 'editor', array('job' => $job->id));
        }
    }

    /**
     * @return \OpenSkos2\Set
     */
    protected function _getSet($job)
    {
        $set_uri = $job->set_uri;
        $di = $this->getDI();
        $setManager = $di->get('OpenSkos2\SetManager');
        $set = $setManager->fetchByUri($set_uri);
        return $set;
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
        
        //
        $set = $this->_getSet($job);

        $tenant_code =$set->getProperty(OpenSkos::TENANT)[0]->getValue();

        if (null === $set) {
            $this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Collection not found'));
            $this->_helper->redirector('index');
        }

        if ($tenant_code != $this->_tenant->code) {
            $this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('You are not allowed to edit this job.'));
            $this->_helper->redirector('index');
        }
        return $job;
    }
}
