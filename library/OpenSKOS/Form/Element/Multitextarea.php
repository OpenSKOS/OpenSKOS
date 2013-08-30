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
 * @copyright  Copyright (c) 2012 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Boyan Bonev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

class OpenSKOS_Form_Element_Multitextarea extends OpenSKOS_Form_Element_Multi {

	const MULTITEXTAREA_PARTIAL_VIEW  = 'partials/multitextarea.phtml';
	
	public function __construct($groupName, $groupLabel, $partialView = self::MULTITEXTAREA_PARTIAL_VIEW)
	{
		parent::__construct($groupName, $groupLabel);
		$this->setPartialView($partialView);
	}
}