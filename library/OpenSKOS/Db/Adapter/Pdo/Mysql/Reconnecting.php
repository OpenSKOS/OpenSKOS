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
 * @copyright  Copyright (c) 2014 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

/**
 * If query fails with Zend_Db_Statement_Exception - tries to reconnect several times before throwing the exception.
 */
class OpenSKOS_Db_Adapter_Pdo_Mysql_Reconnecting extends Zend_Db_Adapter_Pdo_Mysql
{
    /**
     * How many attempts to do before throwing the exception.
     */
    const ATTEMPTS = 5;
    
    /**
     * How many seconds to sleep between each attempt.
     */
    const SLEEP_BETWEEN_ATTEMPTS = 30;
    
    /** 
     * The server messages for which we try reconnecting.
     */
    const SERVER_GONE_MESSAGE = 'gone away';
    const CONNECTION_REFUSED_MESSAGE = 'refused';
    const LINK_FAILURE_MESSAGE = 'link failure';
    
    /**
     * Prepares and executes an SQL statement with bound data.
     *
     * @param  mixed  $sql  The SQL statement with placeholders.
     *                      May be a string or Zend_Db_Select.
     * @param  mixed  $bind An array of data to bind to the placeholders.
     * @return Zend_Db_Statement_Interface
     */
    public function query($sql, $bind = array())
    {
        $attempts = 0;
        while ($attempts < self::ATTEMPTS) {
            try {
                return parent::query($sql, $bind);
            } catch (Zend_Db_Exception $ex) {
                $this->catchDbException($ex);
                $attempts ++;
            }
        }
        
        throw new Exception(
            'Query could not be executed in ' . self::ATTEMPTS . ' attempts. Each waiting for ' . self::SLEEP_BETWEEN_ATTEMPTS . ' seconds. '
                . 'See the previous exception: "' . $ex->getMessage() . '"',
            0,
            $ex
        );
    }
    
    /**
     * @param Zend_Db_Exception $ex
     * @throws Zend_Db_Exception
     */
    protected function catchDbException(Zend_Db_Exception $ex)
    {
        if (strpos($ex->getMessage(), self::SERVER_GONE_MESSAGE) !== false
            || strpos($ex->getMessage(), self::CONNECTION_REFUSED_MESSAGE) !== false
            || strpos($ex->getMessage(), self::LINK_FAILURE_MESSAGE) !== false) {

            $this->closeConnection();
            sleep(self::SLEEP_BETWEEN_ATTEMPTS);
        } else {
            throw $ex;
        }
    }
    
    /**
     * @param Zend_Db_Adapter_Abstract $adapter
     * @return \self
     */
    public static function createFromPdoMysql(Zend_Db_Adapter_Pdo_Mysql $adapter)
    {
        return new self($adapter->getConfig());
    }
}