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
     * Fallback for status expired
     */
    const _EXPIRED = 'expired';
    
    /**
     * List of possible statuses
     * @var array
     */
    protected static $statuses = [
        self::CANDIDATE,
        self::APPROVED,
        self::REDIRECTED,
        self::NOT_COMPLIANT,
        self::REJECTED,
        self::OBSOLETE,
        self::DELETED,
    ];
    
    /**
     * Mapped of allowed transitions between statuses.
     * @var array 
     */
    protected static $transitionsMap = [
        self::CANDIDATE => [
            self::CANDIDATE,
            self::APPROVED,
            self::REDIRECTED,
            self::NOT_COMPLIANT,
            self::REJECTED,
        ],
        self::APPROVED => [
            self::APPROVED,
            self::REDIRECTED,
            self::OBSOLETE,
        ],
        self::REDIRECTED => [
            self::REDIRECTED,
            self::OBSOLETE,
        ],
        self::NOT_COMPLIANT => [
            self::NOT_COMPLIANT,
            self::APPROVED,
            self::REDIRECTED,
            self::OBSOLETE,
        ],
        self::REJECTED => [
            self::REJECTED,
            self::DELETED,
        ],
        self::OBSOLETE => [
            self::OBSOLETE,
            self::DELETED,
        ],
        self::DELETED => [
            self::DELETED,
        ],
    ];
    
    /**
     * Gets all possible statuses
     * @return type
     */
    public static function getStatuses()
    {
        return self::$statuses;
    }
    
    /**
     * Makes a key => translate(value) array from the statuses.
     * @param array $statuses
     * @return array key => translate(value) array
     */
    public static function statusesToOptions($statuses = null)
    {
        if ($statuses == null) {
            $statuses = self::getStatuses();
        }
        
        $options = [];
        foreach ($statuses as $status) {
            $options[$status] = _($status);
        }
        return $options;
    }
    
    /**
     * Gets the list of all available statuses depending on the current status.
     * @param string $currentStatus One of the statuses.
     * @return array
     * @throws \Exception
     */
    public static function getAvailableStatuses($currentStatus)
    {
        if (empty($currentStatus)) {
            $statuses = self::getStatuses();
            $statuses = array_diff($statuses, [self::DELETED]);
            return $statuses;
        } elseif (isset(self::$transitionsMap[$currentStatus])) {            
            return self::$transitionsMap[$currentStatus];
        } else {
            throw new \Exception(
                'No transition info for status "' . $currentStatus . '".'
            );
        }
    }
    
    /**
     * Checks if the $toStatus is allowed to come after $fromStatus.
     * @see self::$transitionsMap
     * @param string $fromStatus
     * @param string $toStatus
     * @return bool
     */
    public static function isTransitionAllowed($fromStatus, $toStatus)
    {
        return in_array($toStatus, self::getAvailableStatuses($fromStatus));
    }
    
    /**
     * Checks if the $status is expired, obsolete or deleted.
     * @param string $status
     * @return bool
     */
    public static function isStatusLikeDeleted($status)
    {
        return in_array($status, [self::_EXPIRED, self::OBSOLETE, self::DELETED]);
    }
}