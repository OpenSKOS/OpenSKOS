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

class OpenSKOS_Utils
{
    /**
     * Generate a Universally Unique Identifier (UUID)
     * 
     * @return string canonical uuid
     * @see http://en.wikipedia.org/wiki/Universally_Unique_Identifier
     */
    static public function uuid()
    {
        mt_srand((double) microtime() * 10000);
        // $charid = strtoupper(md5(uniqid(rand(), true)));
        $charid = md5(uniqid(rand(), true));
        $hyphen = chr(45); // "-"
        return substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12);
    }

    /**
     * Check if a specified uuid is formatted correctly
     * 
     * @param string $uuid
     * @return boolean
     */
    static public function isValidUuid($uuid)
    {
        if (!is_string($uuid)) {
            return false;
        }
        if (empty($uuid)) {
            return false;
        }
        return (boolean) preg_match('/^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$/', $uuid);
    }

	/**
	 * Construct current server URL (optionally with request data)
	 *
	 * @param array $userParams Options passed by a user used to override parameters
	 */
	public static function getAbsoluteUrl(Array $userParams = array())
	{
		$helper = new Zend_View_Helper_ServerUrl();
		$url = $helper->serverUrl();

		if ($userParams) {
	        $router = Zend_Controller_Front::getInstance()->getRouter();
	        $url = rtrim($url, '/') . '/' . ltrim($router->assemble($userParams, null, true), '/');
		}
		return $url;
	}
}