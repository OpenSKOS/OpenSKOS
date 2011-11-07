<?php
require_once 'Zend/Console/Getopt.php';
$opts = array(
	'help|?' => 'Print this usage message',
	'env|e=s' => 'The environment to use (defaults to "production")',
	'code|c=s' => 'Tenant code (optional, default is all Tenants)',
	'job|j=i' => 'Job ID (optional, default is all Jobs)'
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
			$jobs = $model->fetchAll($model->select()
			->where('finished IS NULL')
			->order('created asc')
			->where('started IS NULL'));
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
						$job->start();
						$file = $job->getFile();
						if($job->isZip($file)) {
							$zip = new ZipArchive();
							if ($zip->open($file)!==true) {
								fwrite(STDERR, "cannot open <$file>\n");
								$job
									->error('cannot open ZIP')
									->finish()->save();
								break;
							}
							
							if ($zip->numFiles >= 1) {
								$msg = _('only ZIP files with exactly one file can be processed');
								fwrite(STDERR, "{$job->id}/{$file}: {$msg}\n");
								$job
									->error($msg)
									->finish()->save();
								break;
							}
							for ($i=0; $i<$zip->numFiles;$i++) {
						    	echo "index: $i\n";
		    					print_r($zip->statIndex($i));
							}
							print_r($zip);
						}
						break;
					case OpenSKOS_Db_Table_Row_Job::JOB_TASK_HARVEST:
						$job->start();
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

