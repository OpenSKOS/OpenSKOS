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
 * @copyright  Copyright (c) 2015 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */


include dirname(__FILE__) . '/../autoload.inc.php';

/* 
 * Updates the status expired to status obsolete
 */

require_once 'Zend/Console/Getopt.php';
$opts = array(
    'env|e=s' => 'The environment to use (defaults to "production")',
);

try {
    $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}

include dirname(__FILE__) . '/../bootstrap.inc.php';

// Allow loading of application module classes.
$autoloader = new OpenSKOS_Autoloader();
$mainAutoloader = Zend_Loader_Autoloader::getInstance();
$mainAutoloader->pushAutoloader($autoloader, array('Editor_', 'Api_'));

class EchoLogger extends \Psr\Log\AbstractLogger
{
    public function log($level, $message, array $context = array())
    {
        echo $message . PHP_EOL;
    }
}

// Test....

$xml = '<rdf:RDF 
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
    xmlns:openskos="http://openskos.org/xmlns#"
    xmlns:skos="http://www.w3.org/2004/02/skos/core#"
    xmlns:dcterms="http://dublincore.org/documents/dcmi-terms/#terms-">2
    
    <rdf:Description rdf:about="http://myconcepts/beng/1">  
        <skos:notation>1</skos:notation>
        <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
        <skos:prefLabel xml:lang="nl">Cocnept 1</skos:prefLabel>
        <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/Persoonsnamen"/>
        <openskos:tenant>beng</openskos:tenant>
        <skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/67391"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/72908"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/222220"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/350064"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89349"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89348"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89347"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89426"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89418"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89413"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89411"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89454"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89370"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89453"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89398"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89405"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89432"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89397"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89421"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89425"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89419"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89451"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89361"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89409"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89359"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89380"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89355"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89377"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89400"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89414"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89417"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89452"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89384"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89450"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89401"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89415"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89378"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89410"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89368"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89386"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89353"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89420"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89448"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89369"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89354"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89392"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89351"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89408"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89399"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89402"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89379"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89367"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89428"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89404"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89422"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89403"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89364"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89373"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89381"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89382"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89387"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89375"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89427"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89372"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89391"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89388"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89390"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89394"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89406"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89424"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89436"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89442"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89416"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89374"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89449"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89366"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89393"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89383"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89376"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89445"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89455"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89395"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89407"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89440"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89431"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89362"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89385"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89363"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89371"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89446"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89356"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89423"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89357"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89439"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89435"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89437"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89429"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89441"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89365"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89434"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89430"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89444"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89433"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89443"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89352"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89438"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89511"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89487"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89459"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89461"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89532"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89551"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89533"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89509"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89476"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89464"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89491"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89492"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89471"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89558"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89524"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89519"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89545"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89555"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89494"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89495"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89525"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89490"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89486"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89537"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89562"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89500"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89485"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89552"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89517"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89501"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89468"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89561"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89541"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89463"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89534"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89478"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89506"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89507"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89547"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89457"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89564"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89510"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89484"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89498"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89521"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89522"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89512"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89480"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89467"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89493"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89482"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89546"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89539"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89508"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89503"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89470"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89530"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89528"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89505"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89483"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89540"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89526"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89535"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89474"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89515"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89504"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89481"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89557"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89563"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89473"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89527"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89465"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89553"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89556"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89456"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89489"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89462"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89502"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89488"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89544"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89458"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89536"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89549"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89550"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89529"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89477"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89559"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89538"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89460"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89513"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89497"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89496"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89499"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89542"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89543"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89479"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89626"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89599"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89669"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89654"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89569"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89601"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89657"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89664"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89652"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89607"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89585"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89646"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89573"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89578"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89642"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89565"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89589"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89598"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89574"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89577"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89656"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89590"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89639"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89606"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89597"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89613"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89571"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89668"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89619"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89653"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89673"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89650"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89575"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89593"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89621"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89608"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89636"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89648"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89632"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89620"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89595"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89655"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89627"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89602"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89596"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89638"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89614"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89663"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89672"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89649"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89665"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89587"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89600"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89622"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89591"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89583"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89603"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89667"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89616"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89617"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89640"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89576"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89611"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89628"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89615"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89633"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89618"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89647"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89582"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89666"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89579"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89643"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89592"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89594"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89568"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89588"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89572"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89624"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89634"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89644"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89645"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89584"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89612"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89671"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89581"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89609"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89605"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89567"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89570"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89586"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89631"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89580"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89651"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89670"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89566"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89625"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89763"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89687"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89755"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89732"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89747"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89742"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89754"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89685"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89752"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89762"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89765"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89750"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89740"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89688"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89741"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89678"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89749"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89729"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89676"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89725"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89681"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89682"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89683"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89779"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89726"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89677"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89695"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89761"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89739"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89718"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89731"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89776"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89694"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89769"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89701"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89772"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89780"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89691"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89745"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89782"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89679"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89680"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89774"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89696"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89684"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89715"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89720"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89751"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89778"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89737"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89767"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89733"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89704"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89675"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89730"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89770"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89689"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89771"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89766"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89734"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89728"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89744"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89753"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89777"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89719"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89773"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89775"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89738"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89759"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89686"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89674"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89748"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89735"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89727"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89746"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89743"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89764"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89724"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89879"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89862"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89863"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89854"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89874"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89783"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89878"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89869"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89784"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89821"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89876"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89797"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89864"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89820"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89853"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89816"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89828"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89866"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89846"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89845"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89822"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89818"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89830"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89790"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89804"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89810"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89885"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89844"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89817"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89872"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89798"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89826"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89867"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89819"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89858"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89886"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89832"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89825"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89829"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89843"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89870"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89805"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89868"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89857"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89786"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89787"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89794"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89785"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89873"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89877"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89809"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89801"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89806"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89807"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89814"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89815"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89888"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89812"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89871"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89875"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89887"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89792"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89795"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89802"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89813"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89796"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89799"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89884"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89861"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89831"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89841"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89803"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89848"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89882"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89859"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89788"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89811"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89827"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89860"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89789"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89883"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89808"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89824"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89880"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89856"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89793"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89855"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89965"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89941"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89972"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89966"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89935"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89986"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89891"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89900"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89964"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89903"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89913"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89952"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89973"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89987"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89980"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89909"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89904"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89923"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89960"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89959"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89931"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89943"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89997"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89930"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89922"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89908"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89901"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89988"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89963"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89921"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89914"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89932"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89899"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89974"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89990"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89934"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89985"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89948"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89944"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89984"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89976"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89957"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89910"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89892"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89938"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89897"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89956"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89906"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89890"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89992"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89979"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89911"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89927"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89954"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89996"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89902"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89936"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89970"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89919"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89942"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89961"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89918"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89977"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89912"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89920"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89981"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89925"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89929"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89933"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89962"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89945"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89975"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89983"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89916"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89894"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89993"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89939"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89940"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89907"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89967"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89982"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89896"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89898"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89989"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89955"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89991"/>
<skos:narrowMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/89994"/>
<skos:narrowMatch rdf:resource="http://blabla/1"/>
    </rdf:Description>
</rdf:RDF>';


$client = new Zend_Http_Client('http://openskos/api/concept', array(
'maxredirects' => 0,
'timeout'      => 30));
$response = $client
->setEncType('text/xml')
->setRawData($xml)
->setParameterGet('tenant', 'beng')
->setParameterGet('collection', 'mycol')
->setParameterGet('key', 'alexandar')
->setParameterGet('autoGenerateIdentifiers', false)
->request('PUT');
if ($response->isSuccessful()) {
echo 'Concept created';
} else {
echo 'Failed to create concept: ' . $response->getHeader('X-Error-Msg');
}