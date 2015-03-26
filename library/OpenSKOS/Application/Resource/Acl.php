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

class OpenSKOS_Application_Resource_Acl extends Zend_Application_Resource_ResourceAbstract
{
    const REGISTRY_KEY = 'OpenSKOS_Acl';

    public function init()
    {
    	$acl = new Zend_Acl();
    	$acl->addRole(OpenSKOS_Db_Table_Users::USER_ROLE_GUEST);
    	$acl->addRole(OpenSKOS_Db_Table_Users::USER_ROLE_USER, OpenSKOS_Db_Table_Users::USER_ROLE_GUEST);
    	$acl->addRole(OpenSKOS_Db_Table_Users::USER_ROLE_EDITOR, OpenSKOS_Db_Table_Users::USER_ROLE_USER);
    	$acl->addRole(OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR, OpenSKOS_Db_Table_Users::USER_ROLE_EDITOR);
    	$acl->addRole(OpenSKOS_Db_Table_Users::USER_ROLE_ROOT, OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR);
    	
    	$acl->addResource('website');
    	$acl->addResource('editor');
    	$acl->addResource('editor.concepts', 'editor');
    	$acl->addResource('editor.concept-schemes', 'editor');
    	$acl->addResource('editor.institution', 'editor');
    	$acl->addResource('editor.collections', 'editor');
    	$acl->addResource('editor.delete-all-concepts-in-collection', 'editor');
    	$acl->addResource('editor.users', 'editor');
    	$acl->addResource('editor.jobs', 'editor');
    	$acl->addResource('editor.manage-search-profiles', 'editor');
    	 
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_GUEST, 'website', 'view');
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_USER, 'editor', 'view');
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_USER, 'editor.concepts', 'view');
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_EDITOR, 'editor.concepts', array('propose'));
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR, 'editor.concepts',  array('full-create', 'edit', 'delete'));
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR, 'editor.concept-schemes', array('index', 'create', 'edit', 'delete', 'manage-icons'));
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR, 'editor.institution',  null);
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR, 'editor.collections',  array('index', 'manage'));
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR, 'editor.users',  array('index', 'manage'));
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR, 'editor.jobs',  array('index', 'manage'));
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR, 'editor.manage-search-profiles', null);
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_ROOT, 'editor.concepts',  array('bulk-status-edit'));
    	$acl->allow(OpenSKOS_Db_Table_Users::USER_ROLE_ROOT, 'editor.delete-all-concepts-in-collection', null);
    	
		Zend_Registry::set(self::REGISTRY_KEY, $acl);
		
		//store the ACL for the view:
		Zend_View_Helper_Navigation_HelperAbstract::setDefaultAcl($acl);
    }
}