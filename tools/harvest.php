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
	'collection|c=s' => 'Collection id or code (required)',
	'tenant|t=s' => 'Tenant code (required if a collection code is used)',
	'metadataPrefix=s' => 'The OAI `metadataPrefix` argument (defaults to `oai_rdf`)',
	'set=s' => 'The OAI `set` argument',
	'from=s' => 'The OAI `from` argument (defaults to the last date from Solr, use null to harvest all records)',
	'until=s' => 'The OAI `until` argument (defaults to null)',
	'query|q=s' => 'Optional Solr query',
	'rows|r' => 'Optional maximum number of records to harvest per page',
    'verbose|v' => 'Show debug information'
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

$model = new OpenSKOS_Db_Table_Collections();
if (null !== $OPTS->collection) {
    if (preg_match('/^\d+$/', $OPTS->collection)) {
    	$collection = $model->find($OPTS->collection)->current();
    } else {
        $tenant = $OPTS->tenant;
        if (null === $tenant) {
            fwrite(STDERR, "if you want to select a collection by it's code, a tenant code is required\n");
            exit(1);
        }
	    $collection = $model->fetchRow(
		    $model->select()
			    ->where('code=?', $OPTS->collection)
			    ->where('tenant=?', $tenant)
		);
        if (null === $collection) {
	        fwrite(STDERR, "collection `{$OPTS->collection}` not found\n");
	        exit(2);
        }
        if (!$collection->OAI_baseURL) {
	        fwrite(STDERR, "collection `{$OPTS->collection}` has no OAI base URL\n");
	        exit(3);
        }
        $collections = array($collection);
    }
} else {
    $collections = $model->fetchAll($model->select()->where('OAI_baseURL<>?', ''));
}

foreach ($collections as $collection) {
    if (null!==$OPTS->verbose) {
        fwrite(STDOUT, "processing collection `{$collection->tenant}/{$collection->dc_title}`: \n");
    }


    $from = $OPTS->from;
    if (null === $from) {
    	//get last modified date from Solr:
    	if (null!==$OPTS->verbose) {
    	    fwrite(STDOUT, "fetching last modified date from Solr: ");
    	}
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
    	if (null!==$OPTS->verbose) {
    	    fwrite(STDOUT, ($ts===null ? 'null' : date('c', $ts)) . "\n");
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
    	->setUntil($until);
    foreach (array('set', 'q', 'row') as $opt) {
        if (null!==$OPTS->$opt) {
            $harvester->setOption($opt, $OPTS->$opt);
        }
    }
    
    $data = array(
            'tenant' => $collection->tenant,
            'collection' => $collection->id
    );
    
    try {
    	foreach ($harvester as $page => $records) {
    	    if (null!==$OPTS->verbose) {
    	        fwrite(STDOUT, sprintf("fetching page %d (%d records):\n", $page+1, count($harvester)));
    	    }
    		foreach ($records as $r => $record) {
    		    if (null!==$OPTS->verbose) {
    		        fwrite(STDOUT, "  processing record {$record->identifier}: ");
    		    }
    			$doc = new DOMDocument();
        		if (!@$doc->loadXML((string)$record)) { 
    		        if (null!==$OPTS->verbose) {
    		            fwrite(STDOUT, "FAILED\n");
    		        }
        		    fwrite(STDERR, "Recieved RDF-XML of record `{$record->identifier}` is not valid XML\n");
        			continue;
        		}
        		
        		//do some basic tests
        		if($doc->documentElement->nodeName != 'rdf:RDF') {
    		        if (null!==$OPTS->verbose) {
    		            fwrite(STDOUT, "FAILED\n");
    		        }
        		    fwrite(STDERR, "Recieved RDF-XML of record `{$record->identifier}`  is not valid: expected <rdf:RDF/> rootnode, got <{$doc->documentElement->nodeName}/>\n");
        			continue;
        		}
        		
        		$Descriptions = $doc->documentElement->getElementsByTagNameNs(OpenSKOS_Rdf_Parser::$namespaces['rdf'],'Description');
        		if ($Descriptions->length != 1) {
    		        if (null!==$OPTS->verbose) {
    		            fwrite(STDOUT, "FAILED\n");
    		        }
        		    fwrite(STDERR, "Expected exactly one /rdf:RDF/rdf:Description, got {$Descriptions->length}\n");
        			continue;
        		}
        		try {
    		        $solrDocument = OpenSKOS_Rdf_Parser::DomNode2SolrDocument($Descriptions->item(0), $data);
    	        } catch (OpenSKOS_Rdf_Parser_Exception $e) {
    		        if (null!==$OPTS->verbose) {
        	            fwrite(STDOUT, "FAILED\n");
    		        }
    	            fwrite(STDERR, "record {$record->identifier}: ".$e->getMessage(). "\n");
    		        continue;
    	        }
    			try {
    		    	$solrDocument->save();
    		    } catch (OpenSKOS_Solr_Exception $e) {
    		        if (null!==$OPTS->verbose) {
    		            fwrite(STDOUT, "FAILED\n");
    		        }
    		        fwrite(STDERR, 'Failed to save Concept `'.$solrDocument['uri'][0].'`: '.$e->getMessage()."\n");
    		        continue;
    		    }
    		    if (null!==$OPTS->verbose) {
    		        fwrite(STDOUT, "  done\n");
    		    }
    		}
    	}
    } catch (OpenSKOS_Oai_Pmh_Harvester_Exception $e) {
    	fwrite(STDERR, $e->getMessage()."\n");
    	continue;
    }
}