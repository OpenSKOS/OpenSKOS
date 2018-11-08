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



use EasyRdf\Graph;

class Editor_IndexController extends OpenSKOS_Controller_Editor
{
    public function indexAction()
    {
    /**
     * Basic serialisation example
     *
     * This example create a simple FOAF graph in memory and then
     * serialises it to the page in the format of choice.
     *
     * @package    EasyRdf
     * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
     * @license    http://unlicense.org/
     */
    $graph = new \EasyRdf\Graph();
    $me = $graph->resource('http://www.example.com/joe#me', 'foaf:Person');
    $me->set('foaf:name', 'Joseph Bloggs');
    $me->set('foaf:title', 'Mr');
    $me->set('foaf:nick', 'Joe');
    $me->add('foaf:homepage', $graph->resource('http://example.com/joe/'));
    // I made these up; they are not officially part of FOAF
    $me->set('foaf:dateOfBirth', new \EasyRdf\Literal\Date('1980-09-08'));
    $me->set('foaf:height', 1.82);
    $project = $graph->newBnode('foaf:Project');
    $project->set('foaf:name', "Joe's current project");
    $project->set('foaf:title', "What is says on the tin!");
    $me->set('foaf:currentProject', $project);


    $serialiser =  new \EasyRdf\Serialiser\JsonLd();
    $res = $serialiser->serialise($graph, 'jsonld');
    header('content-type: application/json');
    print $res;
    exit;



        /*
        $schemesCache = $this->getDI()->get('Editor_Models_ConceptSchemesCache');
        $user =  OpenSKOS_Db_Table_Users::requireFromIdentity();
        $tenant = $this->readTenant()->getOpenSkos2Tenant();

        $this->view->assign('conceptSchemes', $schemesCache->fetchUrisMap());
        $this->view->assign('disableSearchProfileChanging', $user->disableSearchProfileChanging);
        $this->view->assign('exportForm', Editor_Forms_Export::getInstance());
        $this->view->assign('deleteForm', Editor_Forms_Delete::getInstance());
        $this->view->assign('changeStatusForm', Editor_Forms_ChangeStatus::getInstance());
        $this->view->assign('oActiveUser', $user);
        $this->view->assign('oActiveTenant', $tenant);

        $this->view->assign('searchForm', Editor_Forms_Search::getInstance());
        */
    }
}
