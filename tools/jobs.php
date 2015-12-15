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
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
include 'autoload.inc.php';

require_once 'Zend/Console/Getopt.php';
$opts = array(
    'help|?' => 'Print this usage message',
    'env|e=s' => 'The environment to use (defaults to "production")',
    'code|c=s' => 'Tenant code (optional, default is all Tenants)',
    'job|j=i' => 'Job ID (optional, default is all Jobs)',
    'task|t=s' => 'Only jobs for the specified task. Options: "import", "export", "harvest", "delete_concept_scheme", "all", "noExport". (optional, default is "noExport")'
);

try {
    $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}

if ($OPTS->help) {
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(0);
}

if (!$OPTS->task) {
    $OPTS->task = 'noExport';
}

$actions = array('list', 'delete', 'process');

$args = $OPTS->getRemainingArgs();
if (!$args || count($args) != 1) {
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    fwrite(STDERR, "Expected an action (" . implode('|', $actions) . ")\n");
    exit(1);
}
$action = $args[0];

if (!in_array($action, $actions)) {
    fwrite(STDERR, "Invalid action `{$action}`, allowed action: " . implode('|', $actions) . "\n");
    exit(1);
}

include 'bootstrap.inc.php';

// Allow loading of application module classes.
$autoloader = new OpenSKOS_Autoloader();
$mainAutoloader = Zend_Loader_Autoloader::getInstance();
$mainAutoloader->pushAutoloader($autoloader, array('Editor_', 'Api_'));



/* @var $diContainer DI\Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

/**
 * @var $resourceManager \OpenSkos2\Rdf\ResourceManager
 */
$resourceManager = $diContainer->get('OpenSkos2\Rdf\ResourceManager');

switch ($action) {
    case 'list':
        $db = Zend_Db_Table::getDefaultAdapter();
        $select = $db->select()
                ->from('job', array('id', 'collection', 'created', 'parameters', 'task'))
                ->join('collection', 'collection.id=job.collection', array('tenant', 'collectioncode' => 'code'))
                ->where('finished IS NULL')
                ->order('created asc')
                ->where('started IS NULL');
        if ($OPTS->tenant) {
            $select->where('collection.tenant=?', $OPTS->tenant);
        }
        if ($OPTS->task && $OPTS->task != 'all') {
            if ($OPTS->task == 'noExport') {
                $select->where('job.task!=?', OpenSKOS_Db_Table_Row_Job::JOB_TASK_EXPORT);
            } else {
                $select->where('job.task=?', $OPTS->task);
            }
        }

        $rows = $db->fetchAll($select);
        $columns = array('id', 'tenant', 'collection', 'created', 'task');
        echo fwrite(STDOUT, implode("\t", $columns) . "\n");
        foreach ($rows as $row) {
            $params = OpenSKOS_Db_Table_Jobs::getParams($row['parameters']);
            fwrite(STDOUT, "{$row['id']}\t");
            fwrite(STDOUT, "{$row['tenant']}\t");
            fwrite(STDOUT, str_pad($row['collectioncode'], strlen('collection'), ' ', STR_PAD_RIGHT) . "\t");
            fwrite(STDOUT, "{$row['created']}\t");
            fwrite(STDOUT, "{$row['task']}\t");
            if ($row['task'] === OpenSKOS_Db_Table_Row_Job::JOB_TASK_IMPORT) {
                fwrite(STDOUT, "{$params['name']}\t");
                fwrite(STDOUT, "{$params['size']}");
            }
            fwrite(STDOUT, "\n");
        }
        break;
    case 'delete':
    case 'process':
        if ($OPTS->tenant) {
            fwrite(STDERR, "`tenant` option not allowed with delete and process actions\n");
            exit(1);
        }
        $model = new OpenSKOS_Db_Table_Jobs();
        if ($OPTS->job) {
            $jobs = $model->find($OPTS->job);
            if (!count($jobs)) {
                fwrite(STDERR, "Job `{$OPTS->job}` not found\n");
                exit(1);
            }
        } else {
            $select = $model->select()
                    ->where('finished IS NULL')
                    ->order('created asc')
                    ->where('started IS NULL');
            if ($OPTS->task && $OPTS->task != 'all') {
                if ($OPTS->task == 'noExport') {
                    $select->where('job.task!=?', OpenSKOS_Db_Table_Row_Job::JOB_TASK_EXPORT);
                } else {
                    $select->where('job.task=?', $OPTS->task);
                }
            }

            $jobs = $model->fetchAll($select);
        }
        
        if (!count($jobs)) {
            exit(0);
        }
        if ($action === 'delete') {
            foreach ($jobs as $job) {
                try {
                    $job->delete();
                } catch (Zend_Db_Table_Row_Exception $e) {
                    fwrite(STDERR, "Error deleting job `{$job->id}`: " . $e->getMessage() . "\n");
                }
            }
        } else {
            //look for jobs that have been started but not finished:
            // is found, than assume that this jobhandler is busy
            $busyJobs = $model->fetchAll(
                $model->select()
                    ->where('NOT(started IS NULL)')
                    ->where('finished IS NULL')
            );
            if (count($busyJobs)) {
                exit(0);
            }
            foreach ($jobs as $job) {
                /** @var OpenSKOS_Db_Table_Row_Job $job */
                $set = $job->getCollection();
                switch ($job->task) {
                    case OpenSKOS_Db_Table_Row_Job::JOB_TASK_IMPORT:
                        //init importer

                        $userModel = new OpenSKOS_Db_Table_Users();
                        /**
                         * @var $user OpenSKOS_Db_Table_Row_User
                         */
                        $user = $userModel->find($job['user'])[0];
                        $foaf = $user->getFoafPerson();

                        $importer = new \OpenSkos2\Import\Command(
                            $resourceManager,
                            $diContainer->get('OpenSkos2\ConceptManager'),
                            new \OpenSkos2\Tenant(
                                $job->getCollection()->getTenant()['code']
                            )
                        );
                    
                        $jobLogger = $job->getLogger();
                        $importer->setLogger($jobLogger);

                        $job->start()->save();

                        $importFiles = $job->getFilesList();

                        foreach ($importFiles as $filePath) {
                            $message = new \OpenSkos2\Import\Message(
                                $foaf,
                                $filePath,
                                $set->getUri(),
                                (bool) $job->getParam('ignoreIncomingStatus'),
                                $job->getParam('status'),
                                $job->getParam('onlyNewConcepts'),
                                (bool) $job->getParam('toBeChecked'),
                                $job->getParam('lang'),
                                (bool) $job->getParam('deletebeforeimport'),
                                (bool) $job->getParam('purge')
                            );

                            try {
                                $importer->handle($message);
//								$parser->process($job['user']);
                            } catch (Exception $e) {

                                $job->error("Aborting job because: " . $e->getMessage())->finish()->save();
                                exit($e->getCode());
                            }
                        }

                        $job->finish()->save();

                        break;
                    case OpenSKOS_Db_Table_Row_Job::JOB_TASK_HARVEST:
                        $job->start()->save();
                        $harvester = OpenSKOS_Oai_Pmh_Harvester::factory($set)
                                ->setFrom($job->getParam('from'))
                                ->setUntil($job->getParam('until'))
                                ->setOption('set', $job->getParam('set'));
                        try {
                            foreach ($harvester as $page => $records) {
                                echo "page " . ($page + 1) . ":\n";
                                foreach ($records as $r => $record) {
                                    echo "  record " . ($r + 1) . ": {$record->identifier}\n";
                                }
                            }
                            $job->finish()->save();
                        } catch (OpenSKOS_Oai_Pmh_Harvester_Exception $e) {
                            fwrite(STDERR, $job->id . ': ' . $e->getMessage() . "\n");
                            $job->error($e->getMessage())->finish()->save();
                        }
                        break;
                    case OpenSKOS_Db_Table_Row_Job::JOB_TASK_EXPORT:
                        $job->start()->save();

                        $export = new Editor_Models_Export();
                        $export->setSettings($job->getParams());
                        try {
                            $resultFilePath = $export->exportToFile();

                            $model = new OpenSKOS_Db_Table_Jobs(); // Gets new DB object to prevent connection time out.
                            $job = $model->find($job->id)->current(); // Gets new DB object to prevent connection time out.

                            $job->setInfo($resultFilePath);
                            $job->finish()->save();
                        } catch (Zend_Exception $e) {
                            $model = new OpenSKOS_Db_Table_Jobs(); // Gets new DB object to prevent connection time out.
                            $job = $model->find($job->id)->current(); // Gets new DB object to prevent connection time out.

                            fwrite(STDERR, $job->id . ': ' . $e->getMessage() . "\n");
                            $job->error($e->getMessage())->finish()->save();
                        }
                        break;
                    case OpenSKOS_Db_Table_Row_Job::JOB_TASK_DELETE_CONCEPT_SCHEME:
                        $job->start()->save();

                        try {
                            $schemesManager = $diContainer->get('\OpenSkos2\ConceptSchemeManager');
                            $scheme = $schemesManager->fetchByUri($job->getParam('uri'));
                            $schemesManager->deleteSoft($scheme);
                            
                            $userModel = new OpenSKOS_Db_Table_Users();
                            $user = $userModel->find($job['user'])[0];
                            
                            $conceptsManager = $diContainer->get('\OpenSkos2\ConceptManager');
                            $conceptsManager->deleteSoftInScheme($scheme, $user->getFoafPerson());
                            
                            // Clears the schemes cache after an icon is assigned.
                            $diContainer->get('Editor_Models_ConceptSchemesCache')->clearCache();

                            $model = new OpenSKOS_Db_Table_Jobs(); // Gets new DB object to prevent connection time out.
                            $job = $model->find($job->id)->current(); // Gets new DB object to prevent connection time out.

                            $job->finish()->save();
                        } catch (Zend_Exception $e) {
                            $model = new OpenSKOS_Db_Table_Jobs(); // Gets new DB object to prevent connection time out.
                            $job = $model->find($job->id)->current(); // Gets new DB object to prevent connection time out.

                            fwrite(STDERR, $job->id . ': ' . $e->getMessage() . "\n");
                            $job->error("Aborting job because: " . $e->getMessage())->finish()->save();
                        }
                        break;
                    default:
                        fwrite(STDERR, '@TODO: write handler for task=' . $job->task . "\n");
                        $job->error('No handler for this task')->finish()->save();
                        break;
                }
            }
        }
        break;
    default:
        fwrite(STDERR, "Invalid action `{$action}`, allowed action: " . implode('|', $actions) . "\n");
        exit(1);
        break;
}
