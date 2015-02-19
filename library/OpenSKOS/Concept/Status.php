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

class OpenSKOS_Concept_Status
{
    /**#@+
     * All possible statuses
     */
    const CANDIDATE = 'candidate';
    const APPROVED = 'approved';
    const REDIRECTED = 'redirected';
    const NOT_COMPLIANT = 'not_compliant';
    const REJECTED = 'rejected';
    const OBSOLETE = 'obsolete';
    const DELETED = 'deleted';
    /**#@-*/
    
    /**
     * List of possible statuses
     * @var array
     */
    protected static $statuses = array(
        self::CANDIDATE,
        self::APPROVED,
        self::REDIRECTED,
        self::NOT_COMPLIANT,
        self::REJECTED,
        self::OBSOLETE,
        self::DELETED,
    );
    
    /**
     * Gets all possible statuses
     * @return type
     */
    public static function getStatuses()
    {
        return self::$statuses;
    }
}