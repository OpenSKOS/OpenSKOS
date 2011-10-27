<?php
require_once 'Zend/Console/Getopt.php';
$opts = array(
	'help|?' => 'Print this usage message',
	'env|e=s' => 'The environment to use (defaults to "production")',
	'collection|c=s' => 'Collection id or code (required)',
	'tenant|t=s' => 'Tenant code (required if a collection code is used)',
	'metadataPrefix=s' => 'The OAI `metadataPrefix` argument (defaults to `oai_rdf`)',
	'set=s' => 'The OAI `set` argument',
	'from=s' => 'The OAI `from` argument (defaults to the last date from Solr, use null to harvest all records)',
	'until=s' => 'The OAI `until` argument (defaults to null)',
	'query|q=s' => 'Optional Solr query',
	'rows|r' => 'Optional maximum number of records to harvest per page'
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

include 'bootstrap.inc.php';

if (null === $OPTS->collection) {
	fwrite(STDERR, "missing required collection argument\n");
	exit(2);
}

$model = new OpenSKOS_Db_Table_Collections();
if (preg_match('/^\d+$/', $OPTS->collection)) {
	$collection = $model->find($OPTS->collection)->current();
} else {
	$tenant = $OPTS->tenant;
	if (null === $tenant) {
		fwrite(STDERR, "if you want to select a collection by it's code, a tenant code is required\n");
	}
	$collection = $model->fetchRow(
		$model->select()
			->where('code=?', $OPTS->collection)
			->where('tenant=?', $tenant)
		);
}

if (null === $collection) {
	fwrite(STDERR, "collection `{$OPTS->collection}` not found\n");
	exit(2);
}

if (!$collection->OAI_baseURL) {
	fwrite(STDERR, "collection `{$OPTS->collection}` has no OAI base URL\n");
	exit(3);
}

$from = $OPTS->from;
if (null === $from) {
	//get last modified date from Solr:
	$solr = Zend_Registry::get('OpenSKOS_Solr');
	$result = $solr->search(
		"collection:{$collection->id} AND tenant:{$collection->tenant}",
		array(
			'rows' => 1,
			'fl' => 'timestamp',
			'sort' => 'timestamp desc'
		)
	);
	if ($result['response']['numFound']==0) {
		$ts = null;
	} else {
		$ts = strtotime($result['response']['docs'][0]['timestamp']);
	}
} else {
	if (strtolower($from) === 'null') {
		$ts = null;
	} else {
		$ts = strtotime($from);
		if (false === $ts) {
			fwrite(STDERR, "unrecognized date format: `{$from}`\n");
			exit(3);
		}
	}
}

$from = $ts;

$until = $OPTS->until;
if (null !== $until) {
	$ts = strtotime($until);
	if (false === $ts) {
		fwrite(STDERR, "unrecognized date format: `{$until}`\n");
		exit(3);
	}
	$until = $ts;
}

$params = array();

$harvester = OpenSKOS_Oai_Pmh_Harvester::factory($collection)
	->setFrom($from)
	->setUntil($until)
	->setOption('set', $OPTS->set)
	->setOption('q', $OPTS->query)
	->setOption('rows', $OPTS->rows);

try {
	foreach ($harvester as $page => $records) {
		echo "page ".($page + 1).":\n";
		foreach ($records as $r => $record) {
			echo "  record ".($r+1).": {$record->identifier}\n";
		}
	}
} catch (OpenSKOS_Oai_Pmh_Harvester_Exception $e) {
	fwrite(STDERR, $e->getMessage()."\n");
	exit(4);
}