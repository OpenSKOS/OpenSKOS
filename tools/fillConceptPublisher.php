<?php

/*
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
 *
 * Index Jena to SOLR run this if the search is out of sync
 */

require dirname(__FILE__) . '/autoload.inc.php';

$options = [
    'env|e=s'   => 'The environment to use (defaults to "production")',
    'verbose|v' => 'Verbose',
    'jenaQueryUri=s' => 'Override the Jena query endpoint that is the config file.',
    'jenaUpdateUri=s' => 'Override the Jena query endpoint that is the config file.',
    'jenaRows=s' => 'Number of Jena rows to process with each fetch',
    'help|h'    => 'Show this help',
];

try {
    $OPTS = new Zend_Console_Getopt($options);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}

require dirname(__FILE__) . '/bootstrap.inc.php';

if ($OPTS->getOption('help')) {
    exit($OPTS->getUsageMessage());
}

/* @var $diContainer DI\Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

$logger = new \Monolog\Logger("Logger");
$logLevel = \Monolog\Logger::INFO;
if ($OPTS->getOption('verbose')) {
    $logLevel = \Monolog\Logger::DEBUG;
}
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler(
    \Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM,
    $logLevel
));

// Used to record time it takes for the script execution
$scriptStart = microtime(true);

$sparqlOptions = OpenSKOS_Application_BootstrapAccess::getOption('sparql');

\EasyRdf\Http::getDefaultHttpClient()->setConfig(['timeout' => 100]);


$jenaQueryUri = $OPTS->getOption('jenaQueryUri') ? $OPTS->getOption('jenaQueryUri') : $sparqlOptions['queryUri'];
$jenaUpdateUri = $OPTS->getOption('jenaUpdateUri') ? $OPTS->getOption('jenaUpdateUri') : $sparqlOptions['updateUri'];

$sparqlClient = new \OpenSkos2\EasyRdf\Sparql\Client(
    $jenaQueryUri,
    $jenaUpdateUri
);


/* @var $resourceManager \OpenSkos2\Rdf\ResourceManagerWithSearch */
$resourceManager = new \OpenSkos2\Rdf\ResourceManager($sparqlClient);

$sparqlCount = getSparqlCountUnbound();

/*
 * Rows setting:
 * Docker containers seem to get into trouble with values above a 1000. Keep it at that when using docker
 *
 * For dedicated Jena/Solr servers, A value of around 10000 seem to work well
 */
$rows = 100000;
if($OPTS->getOption('jenaRows')){
    $rows = $OPTS->getOption('jenaRows');
}

$result = $resourceManager->query($sparqlCount);
$total = $result->getArrayCopy()[0]->count->getValue();

$sparqlUpdate = getSparqlUpdateQuery($rows);

$logger->info('Total in Jena: ' . $total);

$logger->info('Start Updating Jena');
$offset = 0;

while ($offset < $total) {
    $counter = $offset;
    $pageStart = microtime(true);
    
    $result = $sparqlClient->update($sparqlUpdate);
    $interimTime = $pageTime = round(microtime(true) - $pageStart, 3);
    $logger->debug("Jena fetch in pageTime: $pageTime");

    $offset = $offset + $rows;

    $pageTime = round(microtime(true) - $pageStart, 3);
    $logger->debug(sprintf("\nSolr Write in pageTime: %.4f", $pageTime - $interimTime));

    $pageTime = round(microtime(true) - $pageStart, 3);
    $processTime = round(microtime(true) - $scriptStart, 3);
    $logger->debug("PageTime: $pageTime, totalTime: $processTime");
    if ($OPTS->getOption('verbose')) {
        print "\n";
    }
    exit;
}

$logger->debug('Total in Jena: ' . $total);

$logger->info("Done!");

/*
 * getSparqlCountUnbound
 *
 * Counts how many records we have to process
 *
 */
function getSparqlCountUnbound()
{
    $query = <<<SPARQL_WHERE
SELECT (COUNT(DISTINCT ?concept) as ?count)
  WHERE {
    ?concept <%s> <%s>.
    OPTIONAL {?concept <%s> ?tenant }
    FILTER (!BOUND(?tenant))
}
SPARQL_WHERE;

    $query = sprintf(
        $query,
        OpenSkos2\Namespaces\Rdf::TYPE,
        OpenSkos2\Namespaces\Skos::CONCEPT,
        OpenSkos2\Namespaces\DcTerms::PUBLISHER
    );

    return $query;

}

/*
 * getSparqlUpdateQuery
 *
 * Build the query to update the publisher URI on concepts
 * Use a limit, because Jena needs its hand held when processing large amounts of data
 *
 */
function getSparqlUpdateQuery($rows)
{
    $updateQuery = <<<SPARQL_UPDATE
    INSERT {?concept  <%s> ?tenantSetUri }
WHERE {
SELECT *		
  WHERE {
    ?concept <%s> <%s>.
    ?concept <%s> ?set .
    ?set <%s> ?setTenantCode .
    ?tenantSetUri <%s> ?setTenantCode
    OPTIONAL {?concept <%s> ?tenant }
    FILTER (!BOUND(?tenant))

  }
  LIMIT %s
}
SPARQL_UPDATE;
    $updateQuery = sprintf(
        $updateQuery,
        OpenSkos2\Namespaces\DcTerms::PUBLISHER,
        OpenSkos2\Namespaces\Rdf::TYPE,
        OpenSkos2\Namespaces\Skos::CONCEPT,
        OpenSkos2\Namespaces\OpenSkos::SET,
        OpenSkos2\Namespaces\OpenSkos::TENANT,
        OpenSkos2\Namespaces\OpenSkos::CODE,
        OpenSkos2\Namespaces\DcTerms::PUBLISHER,
        $rows
    );

    return $updateQuery;
}

