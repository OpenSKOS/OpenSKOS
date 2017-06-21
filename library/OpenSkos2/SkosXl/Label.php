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

namespace OpenSkos2\SkosXl;

use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\SkosXl;
use OpenSkos2\Namespaces\Rdf;
use Rhumsaa\Uuid\Uuid;
use OpenSkos2\Namespaces\DcTerms;

class Label extends Resource
{

    const TYPE = SkosXl::LABEL;

    /**
     * Resource constructor.
     * @param string $uri , optional
     */
    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }
    
    /**
     * Get tenant
     *
     * @return Literal
     */
    public function getTenant()
    {
        $values = $this->getProperty(OpenSkos::TENANT);
        if (isset($values[0])) {
            return $values[0];
        } else {
            return null;
        }
    }
        
    /**
     * Get institution row. Code adapted from OpenSkos2\Concept
     * @TODO Remove dependency on OpenSKOS v1 library
     * @return OpenSKOS_Db_Table_Row_Tenant
     */
    public function getInstitution()
    {
        // @TODO Remove dependency on OpenSKOS v1 library
        $model = new \OpenSKOS_Db_Table_Tenants();
        return $model->find($this->getTenant())->current();
    }
    
    /**
     * Ensure all mandatory properties are set before label is written in DB
     * @param string $tenantCode
     */
    public function ensureMetadata( \OpenSkos2\Tenant $tenant, 
        \OpenSkos2\Set $set = null, 
        \OpenSkos2\Person $person = null, 
        \OpenSkos2\PersonManager $personManager = null, 
        \OpenSkos2\SkosXl\LabelManager $labelManager = null, 
        $existingConcept = null, 
        $forceCreationOfXl = false)
    {
        $currentTenant = $this->getTenant();
        
        //Ensure tenant is set
        if (empty($currentTenant) && !empty($tenant->getCode()->getValue())) {
            $this->setProperty(OpenSkos::TENANT, $tenant->getCode());
        }
        
        //Ensure date modified is updated
        $nowLiteral = new Literal(date('c'), null, \OpenSkos2\Rdf\Literal::TYPE_DATETIME);
        $this->setProperty(DcTerms::MODIFIED, $nowLiteral);
    }
    
    /**
     * Generates label uri
     * @return string
     */
    public static function generateUri()
    {
        $separator = '/';
        
        $baseUri = rtrim(self::getBaseApiUri(), $separator);
        
        return $baseUri . $separator . 'labels' . $separator . Uuid::uuid4();
    }
    
    /**
     * @TODO temp function for base api uri
     */
    protected static function getBaseApiUri()
    {
        $apiOptions = \OpenSKOS_Application_BootstrapAccess::getOption('api');
        return $apiOptions['baseUri'];
    }
}
