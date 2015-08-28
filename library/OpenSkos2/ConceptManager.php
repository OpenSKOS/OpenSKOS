<?php /**  * OpenSKOS  *  * LICENSE  *  * This source file is subject to the GPLv3 license that is bundled  * with this package in the file LICENSE.txt.  * It is also available through the world-wide-web at this URL:  * http://www.gnu.org/licenses/gpl-3.0.txt  *  * @category   OpenSKOS  * @package    OpenSKOS  * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)  * @author     Picturae  * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3  */
namespace OpenSkos2;

use OpenSkos2\Rdf\ResourceManager;

/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 26/08/2015
 * Time: 15:55
 */
class ConceptManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Concept::TYPE;
}
