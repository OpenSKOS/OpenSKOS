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
namespace OpenSkos2\SkosXl;

use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\SkosXl\Label;
use OpenSkos2\Namespaces\SkosXl;

class LabelManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Label::TYPE;
    
    public function autoComplete($query, $language)
    {
        // @TODO Implement
        
        $labelsAll = $this->fetch();
        
        $labels = new LabelCollection([]);
        foreach ($labelsAll as $label) {
            $literalForm = $label->getPropertyFlatValue(SkosXl::LITERALFORM, $language);
            if (!empty($literalForm) && preg_match('/^' . preg_quote($query) . '/i', $literalForm)) {
                $labels[] = $label;
            }
        }
        
        $labels->uasort(
            function (Label $label1, Label $label2) use ($language) {
                return strcmp(
                    $label1->getPropertyFlatValue(SkosXl::LITERALFORM, $language),
                    $label2->getPropertyFlatValue(SkosXl::LITERALFORM, $language)
                );
            }
        );
        
        return $labels;
    }
}
