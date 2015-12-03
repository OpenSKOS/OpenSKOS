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

class ParserText
{
    /**
     * Holds the regular expression for splitting the search text for search by text.
     *
     * @var string
     */
    const SEARCH_TEXT_SPLIT_REGEX = '/[\s]+/';

    /**
     * Holds an array of the chars which if are contained in the text field it will be considered query.
     *
     * @var array
     */
    private $operatorsDeterminingTextAsQuery = ['AND', 'OR', 'NOT', '&&', '||'];

    /**
     * Holds the regular expression for characters that are part of the solr syntax and needs escaping.
     *
     * @var string
     */
    //!NOTE "\" must be first in the list.
    private $charsToEscape = array('\\', ' ', '+', '-', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', ':');

    /**
     * Escapes chars that are part of solr syntax.
     * 
     * @param string $text
     * @return string
     */
    public function escapeSpecialChars($text)
    {
        foreach ($this->charsToEscape as $char) {
            $text = str_ireplace($char, '\\' . $char, $text);
        }
        return $text;
    }

    /**
     * Checks the search text for is it a complex query or a simple search text.
     * 
     * @param string $searchText
     */
    public function isSearchTextQuery($searchText)
    {
        $parts = preg_split(self::SEARCH_TEXT_SPLIT_REGEX, $searchText);

        foreach ($parts as $part) {
            if (in_array($part, $this->operatorsDeterminingTextAsQuery)) {
                return true;
            }
        }

        return false;
    }
}
