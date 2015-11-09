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

class OpenSKOS_Controller_Editor extends Zend_Controller_Action {
    
    use \OpenSkos2\Zf1\Psr7Trait;
    
    /**
     * Holds constants for defining reposnse type.
     * @var string
     */
    const RESPONSE_TYPE_JSON = 'json';
    const RESPONSE_TYPE_PARTIAL_HTML = 'partialHtml';
    const RESPONSE_TYPE_HTML = 'html';

    /**
     * @var $_tenant OpenSKOS_Db_Table_Row_Tenant
     */
    protected $_tenant;

    public function init()
    {
        if (false === OpenSKOS_Db_Table_Users::fromIdentity()->isAllowed('editor', 'view')) {
            $this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Your access level does not allow you to use the Editor.'));
            $this->_helper->redirector('index', 'index', 'website');
        }

        if ($this->getRequest()->isPost()) {
            if (null !== $this->getRequest()->getParam('cancel')) {
                $this->_helper->redirector('index');
            }
        }

        $tenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
        if (null === $tenant) {
            throw new Zend_Controller_Action_Exception('Tenant not found', 404);
        }
        $tenant->getForm()->setAction($this->getFrontController()->getRouter()->assemble(array('action' => 'save')));
        $this->_tenant = $tenant;

        $this->_helper->_layout->setLayout('editor');
        $this->view->navigation()
                ->setAcl(Zend_Registry::get(OpenSKOS_Application_Resource_Acl::REGISTRY_KEY))
                ->setRole(OpenSKOS_Db_Table_Users::fromIdentity()->role)
                ->setContainer($this->_getNavigationContainer());
    }

    /**
     * Gets the current user.
     * 
     * @return OpenSKOS_Db_Table_Row_User
     */
    public function getCurrentUser()
    {
        $user = OpenSKOS_Db_Table_Users::fromIdentity();
        if (null === $user) {
            throw new Zend_Controller_Action_Exception('User not found', 404);
        }
        return $user;
    }

    /**
     * @return Zend_Navigation
     */
    protected function _getNavigationContainer()
    {
        return new Zend_Navigation(array(
            array(
                'label' => 'OpenSKOS',
                'action' => 'index',
                'controller' => 'index',
                'module' => 'website',
                'pages' => array(
                    array(
                        'label' => 'Management',
                        'action' => 'index',
                        'controller' => 'index',
                        'module' => 'editor',
                        'pages' => array(
                            array(
                                'label' => 'Search, browse and edit',
                                'action' => 'index',
                                'module' => 'editor',
                                'controller' => 'index',
                                'resource' => 'editor.concepts',
                                'privilege' => 'view'
                            ),
                            array(
                                'label' => 'Manage institution',
                                'action' => 'index',
                                'module' => 'editor',
                                'controller' => 'institution',
                                'resource' => 'editor.institution'
                            ),
                            array(
                                'label' => 'Manage collections',
                                'action' => 'index',
                                'module' => 'editor',
                                'controller' => 'collections',
                                'resource' => 'editor.collections',
                                'privilege' => 'index',
                                'pages' => array(
                                    array(
                                        'label' => 'Create/Edit collection',
                                        'action' => 'edit',
                                        'module' => 'editor',
                                        'controller' => 'collections',
                                        'resource' => 'editor.collections',
                                        'privilege' => 'manage'
                                    ),
                                )
                            ),
                            array(
                                'label' => 'Manage users',
                                'action' => 'index',
                                'module' => 'editor',
                                'controller' => 'users',
                                'resource' => 'editor.users',
                                'privilege' => 'index',
                                'pages' => array(
                                    array(
                                        'label' => 'Create/Edit user',
                                        'action' => 'edit',
                                        'module' => 'editor',
                                        'controller' => 'users',
                                        'resource' => 'editor.users',
                                        'privilege' => 'manage'
                                    ),
                                )
                            ),
                            array(
                                'label' => 'Manage jobs',
                                'action' => 'index',
                                'module' => 'editor',
                                'controller' => 'jobs',
                                'resource' => 'editor.jobs',
                                'privilege' => 'index'
                            ),
                            array(
                                'label' => 'Manage concept schemes',
                                'action' => 'index',
                                'module' => 'editor',
                                'controller' => 'concept-scheme',
                                'resource' => 'editor.concept-schemes',
                                'privilege' => 'index',
                                'pages' => array(
                                    array(
                                        'label' => 'Create',
                                        'action' => 'create',
                                        'module' => 'editor',
                                        'controller' => 'concept-scheme',
                                        'resource' => 'editor.concept-schemes',
                                        'privilege' => 'create'
                                    ),
                                    array(
                                        'label' => 'Delete',
                                        'action' => 'delete',
                                        'module' => 'editor',
                                        'controller' => 'concept-scheme',
                                        'resource' => 'editor.concept-schemes',
                                        'privilege' => 'delete'
                                    ),
                                    array(
                                        'label' => 'Manage icons',
                                        'action' => 'show-icons',
                                        'module' => 'editor',
                                        'controller' => 'concept-scheme',
                                        'resource' => 'editor.concept-schemes'
                                    ),
                                    array(
                                        'label' => 'Upload icon',
                                        'action' => 'upload-icon',
                                        'module' => 'editor',
                                        'controller' => 'concept-scheme',
                                        'resource' => 'editor.concept-schemes'
                                    ),
                                    array(
                                        'label' => 'Delete icon',
                                        'action' => 'delete-icon',
                                        'module' => 'editor',
                                        'controller' => 'concept-scheme',
                                        'resource' => 'editor.concept-schemes'
                                    ),
                                )
                            )
                        )
                    )
        ))));
    }

    /**
     * Check does the user have access to the specified resource with the specified privilege.
     * 
     * @param string $resource
     * @param string $privilege, optional, Default: null
     * @param string $responseType, optional, Default: RESPONSE_TYPE_HTML. One of RESPONSE_TYPE_HTML, RESPONSE_TYPE_PARTIAL_HTML or RESPONSE_TYPE_JSON.
     */
    protected function _requireAccess($resource, $privilege = null, $responseType = self::RESPONSE_TYPE_HTML)
    {
        if (false === OpenSKOS_Db_Table_Users::fromIdentity()->isAllowed($resource, $privilege)) {
            $message = _('Your access level does not allow you access to') . ' "' . $resource . '" - "' . $privilege . '".';

            switch ($responseType) {
                case self::RESPONSE_TYPE_JSON: {
                        $this->getHelper('json')->sendJson(array('status' => 'accessDenied', 'message' => $message));
                    };
                    break;
                case self::RESPONSE_TYPE_PARTIAL_HTML: {
                        $this->getHelper('FlashMessenger')->setNamespace('error')->addMessage($message);
                        $redirectToUrl = $this->getHelper('url')->url(array('module' => 'editor'), null, true);
                        $redirectorJs = '<script type="text/javascript">window.location.href="' . $redirectToUrl . '";</script>';
                        $this->getResponse()->setBody($redirectorJs)->sendResponse();
                        exit;
                    };
                    break;
                case self::RESPONSE_TYPE_HTML:
                default: {
                        $this->getHelper('FlashMessenger')->setNamespace('error')->addMessage($message);
                        $this->_helper->redirector('index', 'index', 'editor');
                    };
                    break;
            }
        }
    }
    
    /**
     * Get dependency injection container
     * 
     * @return \DI\Container
     */
    public function getDI()
    {
       return Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();
    }
}
