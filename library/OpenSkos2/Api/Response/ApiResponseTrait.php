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

namespace OpenSkos2\Api\Response;

use OpenSkos2\Api\Exception\InvalidArgumentException;
use OpenSkos2\Api\Exception\UnauthorizedException;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSKOS_Db_Table_Row_Tenant;
use OpenSKOS_Db_Table_Row_User;
use OpenSKOS_Db_Table_Tenants;
use OpenSKOS_Db_Table_Users;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * Contains some shared functionality to deal with API requests
 */
trait ApiResponseTrait
{

    /**
     * Get error response
     *
     * @param integer $status
     * @param string $message
     * @return ResponseInterface
     */
    protected function getErrorResponse($status, $message)
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($message);
        $response = (new Response($stream, $status, ['X-Error-Msg' => $message]));
        return $response;
    }
    
    /**
     * Get tenant
     *
     * @param string $tenantCode
     * @return OpenSKOS_Db_Table_Row_Tenant
     * @throws InvalidArgumentException
     */
    protected function getTenant($tenantCode)
    {
        $model = new OpenSKOS_Db_Table_Tenants();
        $tenant = $model->find($tenantCode)->current();
        if (null === $tenant) {
            throw new InvalidArgumentException('No such tenant: `'.$tenantCode.'`', 404);
        }
        return $tenant;
    }

    /**
     * @params string $key
     * @return OpenSKOS_Db_Table_Row_User
     * @throws InvalidArgumentException
     */
    protected function getUserByKey($key)
    {
        $user = OpenSKOS_Db_Table_Users::fetchByApiKey($key);
        if (null === $user) {
            throw new InvalidArgumentException('No such API-key: `' . $key . '`', 401);
        }

        if (!$user->isApiAllowed()) {
            throw new InvalidArgumentException('Your user account is not allowed to use the API', 401);
        }

        if (strtolower($user->active) !== 'y') {
            throw new InvalidArgumentException('Your user account is blocked', 401);
        }

        return $user;
    }
    
    /**
     * Check if the user is from the given tenant
     * and if the resource matches the tenant
     *
     * @param Resource $resource
     * @param OpenSKOS_Db_Table_Row_Tenant $tenant
     * @param OpenSKOS_Db_Table_Row_User $user
     * @throws UnauthorizedException
     */
    public function resourceEditAllowed(
        Resource $resource,
        OpenSKOS_Db_Table_Row_Tenant $tenant,
        OpenSKOS_Db_Table_Row_User $user
    ) {
        if ($user->tenant !== $tenant->code) {
            throw new UnauthorizedException('Tenant does not match user given', 403);
        }
        
        $resourceTenant = current($resource->getProperty(OpenSkos::TENANT));
        if ($tenant->code !== (string)$resourceTenant) {
            throw new UnauthorizedException('Resource has tenant ' . (string)$resourceTenant . ' which differs from the given ' . $tenant -> code, 403);
        }
        
        return true;
    }
    
   
}
