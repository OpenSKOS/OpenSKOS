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

class OpenSKOS_Form_Element_Multitextnolang extends OpenSKOS_Form_Element_Multi
{
    const MULTITEXT_PARTIAL_VIEW = 'partials/multitextnolang.phtml';

    public function __construct($groupName, $groupLabel, $partialView = self::MULTITEXT_PARTIAL_VIEW)
    {
        parent::__construct($groupName, $groupLabel);
        $this->setPartialView($partialView);
    }
}
