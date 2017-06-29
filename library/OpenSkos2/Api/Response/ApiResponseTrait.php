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
use OpenSKOS_Db_Table_Row_Tenant;
use OpenSKOS_Db_Table_Row_User;
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
     * @param \OpenSkos2\Rdf\ResourceManager $manager
     * @return \OpenSkos2\Tenant
     * @throws InvalidArgumentException
     */
    protected function getTenant($tenantCode, \OpenSkos2\Rdf\ResourceManager $manager)
    {
        $tenant = $manager->fetchByUuid($tenantCode, \OpenSkos2\Tenant::TYPE, 'openskos:code');
        if (null === $tenant) {
            throw new InvalidArgumentException(
                "No such tenant $tenantCode",
                404
            );
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
    // Meertens: we do not define this method resourceEditAllowed here
    // we have moved resourceEditingAllowed methods to the Custom directory
    // so that the developers of the given institution can adjust it for their own requirements
    // alos, there are different authorisation requirements for different sorts of resources
    // (e.g. compare concept scheme and concepts, different roles are allowed to do different things)
}
