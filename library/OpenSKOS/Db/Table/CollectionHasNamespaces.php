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

class OpenSKOS_Db_Table_CollectionHasNamespaces extends Zend_Db_Table 
{
	protected $_name = 'collection_has_namespace';
	protected $_sequence = false;
	
	protected $_referenceMap    = array(
        'Collections' => array(
            'columns'           => array('collection'),
            'refTableClass'     => 'OpenSKOS_Db_Table_Collections',
            'refColumns'        => array('id')
        ),
        'Namespaces' => array(
            'columns'           => array('namespace'),
            'refTableClass'     => 'OpenSKOS_Db_Table_Namespaces',
            'refColumns'        => array('prefix')
        )
    );
}