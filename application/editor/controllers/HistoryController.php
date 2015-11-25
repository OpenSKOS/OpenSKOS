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
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;

class Editor_HistoryController extends OpenSKOS_Controller_Editor
{
    public function indexAction()
    {
        $user =  OpenSKOS_Db_Table_Users::fromIdentity();
        $history = $user->getUserHistory();
        $schemesCache = $this->getDI()->get('Editor_Models_ConceptSchemesCache');
        $data = array();
        foreach ($history as $concept) {
            $conceptData = $concept->toFlatArray([
                'uri',
                'caption',
                OpenSkos::STATUS,
                Skos::SCOPENOTE
            ]);
            
            $conceptData['schemes'] = $schemesCache->fetchConceptSchemesMeta($concept->getProperty(Skos::INSCHEME));
            
            $data[] = $conceptData;
        }
        if (!empty($data)) {
            $this->getHelper('json')->sendJson(array(
                'status' => 'ok',
                'result' => $data));
        } else {
            $this->getHelper('json')->sendJson(array(
                    'status' => 'ok',
                    'result' => ''));
        }
    }
    public function clearHistoryAction()
    {
        $user =  OpenSKOS_Db_Table_Users::fromIdentity();
        if (null !== $user) {
            $user->clearUserHistory();
        }
        $this->getHelper('json')->sendJson(array('status' =>'ok'));
    }
}
