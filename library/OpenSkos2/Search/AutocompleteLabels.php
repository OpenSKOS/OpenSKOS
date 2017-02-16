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

namespace OpenSkos2\Search;

use OpenSkos2\Rdf\Resource;
use Solarium\Core\Query\Helper as QueryHelper;
use OpenSkos2\SkosXl\LabelManager;
use OpenSkos2\SkosXl\LabelCollection;

class AutocompleteLabels
{
    /**
     * @var LabelManager
     */
    protected $manager;

    /**
     * @var OpenSKOS_Db_Table_Users
     */
    protected $usersModel;

    /**
     * @param LabelManager $manager
     * @param \OpenSKOS_Db_Table_Users $usersModel
     */
    public function __construct(LabelManager $manager, \OpenSKOS_Db_Table_Users $usersModel)
    {
        $this->manager = $manager;
        $this->usersModel = $usersModel;
    }

    /**
     * Perform a autocomplete search with a search profile from the editor
     *
     * @param array $options
     * @return LabelCollection
     */
    public function search($options, &$numFound)
    {
        $helper = new QueryHelper();
        $parser = new ParserText();
        
        $searchText = $options['searchText'];
        
        // Empty query and query for all is replaced with *
        $searchText = trim($searchText);
        if (empty($searchText) || $searchText == '*:*') {
            $searchText = '*';
        }
        
        // In all other cases - start parsing the query
        if ($searchText != '*') {
            if ($parser->isFieldSearch($searchText) || $parser->isSearchTextQuery($searchText)) {
                throw new \OpenSkos2\Exception\InvalidArgumentException('Not a valid label query');
            } else {
                if ($parser->isFullyQuoted($searchText)) {
                    $searchText = $searchText;
                } elseif ($parser->isWildcardSearch($searchText)) {
                    // do not escape wildcard search with the new tokenizer
                    // $searchText = $helper->escapePhrase($searchText);
                } else {
                    $searchText = $helper->escapePhrase($searchText);
                }
            }
        }
        
        $solrQuery = 'a_skosXlLiteralForm_' . $options['language'] . ':' . $searchText;
        
        return $this->manager->search($solrQuery, $options['rows'], $options['start'], $numFound);
    }
}
