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

class Editor_IndexController extends OpenSKOS_Controller_Editor
{
	public function indexAction()
	{
		$user =  OpenSKOS_Db_Table_Users::requireFromIdentity();
		$apiClient = new Editor_Models_ApiClient();;
		$this->view->assign('conceptSchemes', $apiClient->getAllConceptSchemeUriTitlesMap());
		$this->view->assign('conceptSchemesId',  $apiClient->getConceptSchemeMap('uri', 'uuid'));
		$this->view->assign('disableSearchProfileChanging', $user->disableSearchProfileChanging);
		$this->view->assign('exportForm', Editor_Forms_Export::getInstance());
		$this->view->assign('deleteForm', Editor_Forms_Delete::getInstance());		
		$this->view->assign('changeStatusForm', Editor_Forms_ChangeStatus::getInstance());
		$this->view->assign('historyData', $user->getUserHistory());
		
		$this->view->assign('searchForm', Editor_Forms_Search::getInstance());
	}
}