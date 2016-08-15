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

use \Interop\Container\ContainerInterface;

return [
    'EasyRdf\Sparql\Client' => function (ContainerInterface $c) {
        foreach (\OpenSkos2\Namespaces::getAdditionalNamespaces() as $prefix => $namespace) {
            EasyRdf\RdfNamespace::set($prefix, $namespace);
        }
        
        // @TODO Why is that OpenSKOS_Application_BootstrapAccess needed?
        $sparqlOptions = OpenSKOS_Application_BootstrapAccess::getOption('sparql');
        
        return new \EasyRdf\Sparql\Client(
            $sparqlOptions['queryUri'],
            $sparqlOptions['updateUri']
        );
    },
    'Solarium\Client' => function (ContainerInterface $c) {
        
        $solr = OpenSKOS_Application_BootstrapAccess::getOption('solr');

        return new Solarium\Client([
            'endpoint' => [
                'localhost' => [
                    'host' => $solr['host'],
                    'port' => $solr['port'],
                    'path' => $solr['context'],
                    'timeout' => 30,
                ]
            ]
        ]);
    },
    'Editor_Models_ConceptSchemesCache' => function (ContainerInterface $c) {
        $conceptsSchemesCache = new Editor_Models_ConceptSchemesCache(
            $c->get('OpenSkos2\ConceptSchemeManager'),
            OpenSKOS_Cache::getCache()
        );
        
        $tenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
        if (!empty($tenant)) {
            $conceptsSchemesCache->setTenantCode($tenant->code);
        }
        
        return $conceptsSchemesCache;
    }
];
