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

class OpenSKOS_Db_Table_Tenants extends Zend_Db_Table
{
	protected $_name = 'tenant';
	protected $_sequence = false;
    
    /**
     * Classname for row
     *
     * @var string
     */
    protected $_rowClass = 'OpenSKOS_Db_Table_Row_Tenant';
    
    /**
     * Classname for rowset
     *
     * @var string
     */
    protected $_rowsetClass = 'OpenSKOS_Db_Table_Rowset_Tenant';

    protected $_dependentTables = array('OpenSKOS_Db_Table_Collections');

    /**
     * @return OpenSKOS_Db_Table_Row_Tenant
     */
    public static function fromIdentity()
    {
    	if (!Zend_Auth::getInstance()->hasIdentity()) {
    		return;
    	}
    	return self::fromCode(Zend_Auth::getInstance()->getIdentity()->tenant);
    }
    
    public static function fromCode($code)
    {
        $className = __CLASS__;
        $model = new $className();
        return $model->find($code)->current();
    }
}

class OpenSKOS_Db_Table_Rowset_Tenant extends Zend_Db_Table_Rowset
{
	public function toRdf()
	{
		$doc = OpenSKOS_Db_Table_Row_Tenant::getRdfDocument();
		foreach($this as $tenant) {
			$doc->documentElement->appendChild($doc->importNode($tenant->toRdf()->getElementsByTagname('v:Vcard')->item(0), true));
		}
		return $doc;
	}
	
}