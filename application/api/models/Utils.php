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

class Api_Models_Utils
{
    public static function addStatusToQuery($q)
    {
        // Only do it if status is not in the query already.
        if (strripos($q, 'status:') === false) {
            $apiOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('api');
            if (!empty($apiOptions['showOnlyApproved'])) {
                $q = '(' . $q . ') AND status:' . OpenSKOS_Concept_Status::APPROVED;
            }
        }
        
        return $q;
    }
}
