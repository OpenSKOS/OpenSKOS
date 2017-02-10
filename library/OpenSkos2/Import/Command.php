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

namespace OpenSkos2\Import;

use OpenSkos2\Concept;
use OpenSkos2\Converter\File;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\ConceptManager;
use OpenSkos2\Tenant;
use OpenSkos2\Import\Command\CollectionHelper;
use OpenSkos2\Validator\Collection as CollectionValidator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ResourceManager
     */
    private $resourceManager;

    /**
     * @var ConceptManager
     */
    private $conceptManager;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * Command constructor.
     * @param ResourceManager $resourceManager
     * @param Tenant $tenant optional If specified - tenant specific validation can be made.
     */
    public function __construct(
        ResourceManager $resourceManager,
        ConceptManager $conceptManager,
        Tenant $tenant = null
    ) {
        $this->resourceManager = $resourceManager;
        $this->conceptManager = $conceptManager;
        $this->tenant = $tenant;
    }


    public function handle(Message $message)
    {
        $file = new File($message->getFile());
        $resourceCollection = $file->getResources(Concept::$classes['SkosXlLabels']);
        
        // Disable commit's for every concept
        $this->conceptManager->setIsNoCommitMode(true);
        
        $helper = new CollectionHelper(
            $this->resourceManager,
            $this->conceptManager,
            $this->tenant,
            $message
        );
        $helper->setLogger($this->logger);
        $helper->prepare($resourceCollection);
        
        $validator = new CollectionValidator($this->resourceManager, $this->tenant);
        if (!$validator->validate($resourceCollection, $this->logger)) {
            throw new \Exception('Failed validation: ' . PHP_EOL . implode(PHP_EOL, $validator->getErrorMessages()));
        }
        
        if ($message->getClearSet()) {
            $this->conceptManager->deleteBy([\OpenSkos2\Namespaces\OpenSkos::SET => $message->getSetUri()]);
            $this->resourceManager->deleteBy([\OpenSkos2\Namespaces\OpenSkos::SET => $message->getSetUri()]);
        }

        if ($message->getDeleteSchemes()) {
            $conceptSchemes = [];
            foreach ($resourceCollection as $resourceToInsert) {
                foreach ($resourceToInsert->getProperty(Skos::INSCHEME) as $scheme) {
                    /** @var $scheme Uri */
                    $conceptSchemes[$scheme->getUri()] = $scheme;
                }
            }
            foreach ($conceptSchemes as $scheme) {
                $this->conceptManager->deleteBy([Skos::INSCHEME => $scheme]);
                $this->resourceManager->delete($scheme);
            }
        }

        foreach ($resourceCollection as $resourceToInsert) {
            if ($resourceToInsert instanceof Concept) {
                $this->conceptManager->replace($resourceToInsert);
            } else {
                $this->resourceManager->replace($resourceToInsert);
            }
        }

        // Commit all solr documents
        $this->conceptManager->commit();
    }
}
