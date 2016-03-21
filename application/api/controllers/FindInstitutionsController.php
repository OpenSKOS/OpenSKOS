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

class Api_FindInstitutionsController extends OpenSKOS_Rest_Controller {

    public function init()
    {
        parent::init();

        $this->_helper->contextSwitch()
                ->initContext($this->getRequestedFormat());

        if ('html' == $this->_helper->contextSwitch()->getCurrentContext()) {
            //enable layout:
            $this->getHelper('layout')->enableLayout();
        }
    }

    public function indexAction()
    {
        var_dump("index"); 
        if ('json' !== $this->_helper->contextSwitch()->getCurrentContext()) {
            $this->_501('This action, which lists the uris of all institutions (tenants), is currently  implemented only for json format output.');
        };
        $resourceManager = $this -> getResourceManager();
        $result = $resourceManager ->fetchTenants();
        $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
        $this->getResponse()->setBody(json_encode($result, JSON_UNESCAPED_SLASHES));
    }
    
   public function getAction()
    {
        $this->_501('GET');
    }
    
    
    public function postAction()
    {
        $this->_501('POST');
    }

    public function putAction()
    {
        $this->_501('PUT');
    }

    public function deleteAction()
    {
        $this->_501('DELETE');
    }

    
}
