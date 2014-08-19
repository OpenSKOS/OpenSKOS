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
	fwrite(STDERR, $e->getMessage()."\n");
	echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
	exit(1);
}

if ($OPTS->help) {
	echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
	exit(0);
}

if ( ! $OPTS->task) {
	$OPTS->task = 'noExport';
}

$actions = array('list', 'delete', 'process');

$args = $OPTS->getRemainingArgs();
if (!$args || count($args)!=1) {
	echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
	fwrite(STDERR, "Expected an action (".implode('|', $actions).")\n");
	exit(1);
}
$action = $args[0];

if (!in_array($action, $actions)) {
	fwrite(STDERR, "Invalid action `{$action}`, allowed action: ".implode('|', $actions)."\n");
	exit(1);
	
}

include 'bootstrap.inc.php';

// Allow loading of application module classes.
$autoloader = new OpenSKOS_Autoloader();
$mainAutoloader = Zend_Loader_Autoloader::getInstance();
$mainAutoloader->pushAutoloader($autoloader, array('Editor_', 'Api_'));

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
		$columns = array('id', 'tenant', 'collection', 'created         ', 'task');
		echo fwrite(STDOUT, implode("\t", $columns)."\n");
		foreach ($rows as $row) {
			$params = OpenSKOS_Db_Table_Jobs::getParams($row['parameters']);
			fwrite(STDOUT, "{$row['id']}\t");
			fwrite(STDOUT, "{$row['tenant']}\t");
			fwrite(STDOUT, str_pad($row['collectioncode'], strlen('collection'), ' ', STR_PAD_RIGHT). "\t");
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
				} catch(Zend_Db_Table_Row_Exception $e) {
					fwrite(STDERR, "Error deleting job `{$job->id}`: ".$e->getMessage()."\n");
				}
			}
		} else {
			//look for jobs that have been started but not finished:
			// is found, than assume that this jobhandler is busy
			$busyJobs = $model->fetchAll($model->select()
				->where('NOT(started IS NULL)')
				->where('finished IS NULL')
			);
			if (count($busyJobs)) {
				exit(0);
			}
			foreach ($jobs as $job) {
				$collection = $job->getCollection();
				switch ($job->task) {
					case OpenSKOS_Db_Table_Row_Job::JOB_TASK_IMPORT:						
						$job->start()->save();
						
						$importFiles = array();
						$file = $job->getFile();
						$fromZip = false;
						if ($job->isZip($file)) {
							$fromZip = true;
							
							$zip = new ZipArchive();
							if ($zip->open($file) !== true) {
								fwrite(STDERR, "Can not open <$file>\n");
								$job->error("Can not open <$file> as zip.")->finish()->save();
								break;
							}
							
							// Makes dir in which to extract the files.
							$extractDirPath = $job->getParam('destination') . '_' . substr($job->getParam('name'), 0, strrpos($job->getParam('name'), '.'));
							mkdir($extractDirPath);
							
							$zip->extractTo($extractDirPath);
							$extractedFiles = scandir($extractDirPath);
							foreach ($extractedFiles as $file) {
								if ($file != '.' && $file != '..') {
									$importFiles[] = $extractDirPath . '/' . $file;
								}
							}
						} else {
							$importFiles[] = $file;
						}
						
						// If delete before import option is set - remove all concepts in the collection.
						if ((bool)$job->getParam('deletebeforeimport')) {
							$solrClient = Zend_Registry::get('OpenSKOS_Solr');
							$solrClient->delete('collection:' . $collection->id);
							$solrClient->commit();
							$solrClient->optimize();
						}
						
						// Prepare import arguments and call the parser process for each file.
						$arguments = array();
						$arguments[] = '--env';
						$arguments[] = $OPTS->env;
						
						// Collection args
						$arguments[] = '--tenant';
						$arguments[] = $collection->tenant;
						$arguments[] = '--collection';
						$arguments[] = $collection->id;
						
						$arguments[] = '--status';
						$arguments[] = $job->getParam('status');
						if ((bool)$job->getParam('ignoreIncomingStatus')) {
							$arguments[] = '--ignoreIncomingStatus';
						}
						$arguments[] = '--lang';
						$arguments[] = $job->getParam('lang');
						if ((bool)$job->getParam('toBeChecked')) {
							$arguments[] = '--toBeChecked';
						}
						if ((bool)$job->getParam('purge')) {
							$arguments[] = '--purge';
						}
                        if ((bool)$job->getParam('onlyNewConcepts')) {
							$arguments[] = '--onlyNewConcepts';
						}
							
						$arguments[] = '--commit';
                        
						$duplicateConceptSchemes = array();
						$notImportedNotations = array();
						foreach ($importFiles as $filePath) {
							$parserOpts = new Zend_Console_Getopt(OpenSKOS_Rdf_Parser::$get_opts);
							$parserOpts->setArguments(array_merge($arguments, array($filePath))); // The last argument must be the file path.
							try {
								$parser = OpenSKOS_Rdf_Parser::factory($parserOpts);
								$parser->process($job['user']);
								$duplicateConceptSchemes = array_merge($duplicateConceptSchemes, $parser->getDuplicateConceptSchemes());
								$notImportedNotations = array_merge($notImportedNotations, $parser->getNotImportedNotations());
							} catch (Exception $e) {
								$model = new OpenSKOS_Db_Table_Jobs(); // Gets new DB object to prevent connection time out.
								$job = $model->find($job->id)->current(); // Gets new DB object to prevent connection time out.
								
								fwrite(STDERR, $job->id.': '.$e->getMessage()."\n");
								$job->error($e->getMessage())->finish()->save();
								exit($e->getCode());
							}
						}
						
						// Delete extracted files when done.
						if ($fromZip) {
							foreach ($importFiles as $filePath) {
								unlink($filePath);
							}
							rmdir($extractDirPath);
						}
						
						// Clears the schemes cache after import.
						OpenSKOS_Cache::getCache()->remove(Editor_Models_ApiClient::CONCEPT_SCHEMES_CACHE_KEY);
						
						$model = new OpenSKOS_Db_Table_Jobs(); // Gets new DB object to prevent connection time out.
						$job = $model->find($job->id)->current(); // Gets new DB object to prevent connection time out.
						
						$info = '';
						if ( ! empty($duplicateConceptSchemes)) {
							$info .= '<span class="errors">' . _('Tried to import the fallowing already existing concept schemes:') .  '"' . implode('", "', $duplicateConceptSchemes) . '"</span><br /><br />';
						}
						if ( ! empty($notImportedNotations)) {
							// If there are thousands of not imported notations - show only first 100
							if (count($notImportedNotations) > 100) {
								$notImportedNotations = array_slice($notImportedNotations, 0, 100);
								$notImportedNotations[] = '...';
							}
							$info .= _('The documents with the fallowing notations were not imported because already exist:') .  '"' . implode('", "', $notImportedNotations) . '"';
						}
						
						$job->setInfo($info);
						$job->finish()->save();
						
						break;
					case OpenSKOS_Db_Table_Row_Job::JOB_TASK_HARVEST:
						$job->start()->save();
						$harvester = OpenSKOS_Oai_Pmh_Harvester::factory($collection)
							->setFrom($job->getParam('from'))
							->setUntil($job->getParam('until'))
							->setOption('set', $job->getParam('set'));
						try {
							foreach ($harvester as $page => $records) {
								echo "page ".($page + 1).":\n";
								foreach ($records as $r => $record) {
									echo "  record ".($r+1).": {$record->identifier}\n";
								}
							}
							$job->finish()->save();
						} catch (OpenSKOS_Oai_Pmh_Harvester_Exception $e) {
							fwrite(STDERR, $job->id.': '.$e->getMessage()."\n");
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
								
								fwrite(STDERR, $job->id.': '.$e->getMessage()."\n");
								$job->error($e->getMessage())->finish()->save();
							}
							break;
					case OpenSKOS_Db_Table_Row_Job::JOB_TASK_DELETE_CONCEPT_SCHEME:
						$job->start()->save();

						try {
							$response = Api_Models_Concepts::factory()->getConcepts('uuid:' . $job->getParam('uuid'));
							if ( ! isset($response['response']['docs']) || (1 !== count($response['response']['docs']))) {
								throw new Zend_Exception('The requested concept scheme was not found');
							}								
							$conceptScheme = new Editor_Models_ConceptScheme(new Api_Models_Concept(array_shift($response['response']['docs'])));
							$conceptScheme->delete(true, $job['user']);
							
							// Clears the schemes cache after the scheme is removed.
							OpenSKOS_Cache::getCache()->remove(Editor_Models_ApiClient::CONCEPT_SCHEMES_CACHE_KEY);
							
							$model = new OpenSKOS_Db_Table_Jobs(); // Gets new DB object to prevent connection time out.
							$job = $model->find($job->id)->current(); // Gets new DB object to prevent connection time out.
							
							$job->finish()->save();
						} catch (Zend_Exception $e) {
							$model = new OpenSKOS_Db_Table_Jobs(); // Gets new DB object to prevent connection time out.
							$job = $model->find($job->id)->current(); // Gets new DB object to prevent connection time out.
					
							fwrite(STDERR, $job->id.': '.$e->getMessage()."\n");
							$job->error($e->getMessage())->finish()->save();
						}
						break;
					default:
						fwrite(STDERR, '@TODO: write handler for task='.$job->task."\n");
						$job->error('No handler for this task')->finish()->save();
						break;
				}
			}
		}
		break;
	default:
		fwrite(STDERR, "Invalid action `{$action}`, allowed action: ".implode('|', $actions)."\n");
		exit(1);
		break;
}

