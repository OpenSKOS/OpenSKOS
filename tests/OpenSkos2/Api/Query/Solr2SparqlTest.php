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
 */

namespace OpenSkos2\Api\Query;

class Solr2SparqlTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruct()
    {
        $request = (new \Zend\Diactoros\ServerRequest())
                ->withQueryParams(['q' => 'test']);
        $s2s = new Solr2Sparql($request);
        $this->assertInstanceOf('OpenSkos2\Api\Query\Solr2Sparql', $s2s);
    }

    public function testSearchTerm()
    {
        $request = (new \Zend\Diactoros\ServerRequest())
                ->withQueryParams(['q' => 'test']);
        $s2s = new Solr2Sparql($request);
        $sparql = $s2s->getSelect(10, 0)->format();

        $expected = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX openskos: <http://openskos.org/xmlns#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#> DESCRIBE ?subject WHERE {
	?subject rdf:type skos:Concept ;
		skos:prefLabel ?pref ;
		skos:altLabel ?alt .
	FILTER (regex (str (?pref), "^test", "i") || regex (str (?alt), "^test", "i"))
}
LIMIT 10 OFFSET 0
';
        $this->assertEquals($expected, $sparql);
    }

    public function testSearchField()
    {
        $request = (new \Zend\Diactoros\ServerRequest())
            ->withQueryParams(['q' => 'prefLabel:test']);
        $s2s = new Solr2Sparql($request);
        $sparql = $s2s->getSelect(10, 0)->format();
        
        $expected = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX openskos: <http://openskos.org/xmlns#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#> DESCRIBE ?subject WHERE {
	?subject rdf:type skos:Concept ; <http://www.w3.org/2004/02/skos/core#prefLabel> ?param0 .
	FILTER (str (?param0) = "test")
}
LIMIT 10 OFFSET 0
';
        $this->assertEquals($expected, $sparql);
    }
    
    public function testSearchFieldWildCard()
    {
        $request = (new \Zend\Diactoros\ServerRequest())
            ->withQueryParams(['q' => 'prefLabel:test*']);
        $s2s = new Solr2Sparql($request);
        $sparql = $s2s->getSelect(10, 0)->format();
        
        $expected = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX openskos: <http://openskos.org/xmlns#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#> DESCRIBE ?subject WHERE {
	?subject rdf:type skos:Concept ; <http://www.w3.org/2004/02/skos/core#prefLabel> ?param0 .
	FILTER (regex (str (?param0), "^test", "i"))
}
LIMIT 10 OFFSET 0
';
        $this->assertEquals($expected, $sparql);
    }
}
