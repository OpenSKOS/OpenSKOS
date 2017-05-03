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

namespace OpenSkos2\Api;

use OpenSkos2\Authorisation;
use OpenSkos2\Deletion;
use OpenSkos2\RelationManager;

require_once dirname(__FILE__) . '/../config.inc.php';

class Relation extends AbstractTripleStoreResource
{

    public function __construct(RelationManager $manager)
    {
        $this->manager = $manager;
        $this->authorisation = new Authorisation($manager);
        $this->deletion = new Deletion($manager);
    }

    public function mapNameSearchID()
    {
        return []; // implement when relation instances become stand-alone resources
    }
}
