<?php

namespace OpenSkos2\Api;

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Api\Exception\NotFoundException;

class Set extends AbstractTripleStoreResource
{

    /**
     * Search autocomplete
     *
     * @var Autocomplete
     */
    protected $searchAutocomplete;

    /**
     *
     * @param \OpenSkos2\SetManager $manager
     * @param \OpenSkos2\Search\Autocomplete $autocomplete
     * @param \OpenSkos2\PersonManager $personManager
     */
    public function __construct(
    \OpenSkos2\SetManager $manager, \OpenSkos2\Search\Autocomplete $searchAutocomplete, \OpenSkos2\PersonManager $personManager)
    {
        $this->manager = $manager;
        $this->authorisation = new \OpenSkos2\Authorisation($manager);
        $this->deletion = new \OpenSkos2\Deletion($manager);
        $this->personManager = $personManager;
        $this->init = parse_ini_file(__DIR__ . '/../../../application/configs/application.ini');
    }

    /**
     * Get openskos resource
     *
     * @param string|Uri $id
     * @throws NotFoundException
     * @return a sublcass of \OpenSkos2\Set
     */
    public function getResource($id)
    {
        try {
            $set = parent::getResource($id);
        } catch (ResourceNotFoundException $ex) {
            $set = $this->manager->fetchByCode($id, Set::TYPE);
        }

        if (!$set) {
            throw new NotFoundException('Set not found by uri/uuid/code: ' . $id, 404);
        }
        return $set;
    }

     /**
     * Get the resource from the request to insert or update
     * does some validation to see if the xml is valid
     *
     * @param ServerRequestInterface $request
     * @param Tenant $inloggedTenant
     * @return \OpenSkos2\*
     * @throws InvalidArgumentException
     */
    protected function getResourceFromRequest(ServerRequestInterface $request, $inloggedTenant)
    {
        
        $set = parent::getResourceFromRequest($request, $inloggedTenant);
        
        if ($this->init['custom.backward_compatible']) {
            $xmlTenantCode = $set->getProperty(OpenSkos::TENANT); // literal, code
            if (count($xmlTenantCode)>1) {
                throw new InvalidArgumentException('More than 1 tenant specified in the xml body', 400);
            }
            if (count($xmlTenantCode)===0) {
                $set->addUniqueProperty(DcTerms::PUBLISHER, new Uri($inloggedTenant->getUri()));
            } 
            if (count($xmlTenantCode)===1) {
                $tenant = $this->getTenant($xmlTenantCode[0], $this->manager);
                $set->unsetPoperty(OpenSkos::TENANT);
                $set->addUniqueProperty(DcTerms::PUBLISHER, new Uri($tenant->getUri()));
            } 
            
        } else {
            $publishers= $set->getProperty(DcTerms::PUBLISHER); // uri
            if (count($publishers)>1) {
                throw new InvalidArgumentException('More than 1 publisher specified in the xml body', 400);
            }
            if (count($publishers)===0) {
                $set->addUniqueProperty(DcTerms::PUBLISHER, new Uri($inloggedTenant->getUri()));
            } 
        }
        return $set;
    }
    
 
    
    protected function getRequiredParameters(){
       
        return ['key', 'tenant'];
    }
    
     // no set is needed to implement set API
    
    protected function getSet($params, $tenant)
    {
        return new \OpenSkos2\Set();
    }
    
   
}
