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
	fwrite(STDERR, "Expected an actions (".implode('|', $actions).")\n");
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
			->from('job', array('id', 'collection', 'created', 'parameters'))
			->join('collection', 'collection.id=job.collection', array('tenant', 'collectioncode' => 'code'))
			->where('finished IS NULL')
			->order('created asc')
			->where('started IS NULL');
		if ($OPTS->tenant) {
			$select->where('collection.tenant=?', $OPTS->tenant);
		}
		$rows = $db->fetchAll($select);
		$columns = array('id', 'tenant', 'collection', 'created', 'file', 'size');
		echo fwrite(STDOUT, implode("\t", $columns)."\n");
		foreach ($rows as $row) {
			$params = OpenSKOS_Db_Table_Jobs::getParams($row['parameters']);
			fwrite(STDOUT, "{$row['id']}\t");
			fwrite(STDOUT, "{$row['tenant']}\t");
			fwrite(STDOUT, "{$row['collectioncode']}\t");
			fwrite(STDOUT, "{$row['created']}\t");
			fwrite(STDOUT, "{$params['name']}\t");
			fwrite(STDOUT, "{$params['size']}\n");
		}
		break;
	case 'delete':
	case 'process':
		if ($OPTS->tenant) {
			fwrite(STDERR, "`tenant` option not allowed with process action\n");
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
			$jobs = $model->fetchAll();
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
						fwrite(STDERR, "{$file}: {$msg}\n");
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
			}
		}
		break;
	default:
		fwrite(STDERR, "Invalid action `{$action}`, allowed action: ".implode('|', $actions)."\n");
		exit(1);
		break;
}

