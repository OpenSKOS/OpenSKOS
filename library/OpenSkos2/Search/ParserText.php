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
     * Holds the format in which the dates in the options must be.
     * @var string
     */
    const OPTIONS_DATE_FORMAT = 'dd/MM/yyyy';
    
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
    
    /**
     * Builds query for date period - like created_timestamp:[{startDate} TO {endDate}].
     * @param string $field The field to search by.
     * @param string $startDate Use as start date (it is converted to timestamp)
     * @param string $endDate Use as end date (it is converted to timestamp)
     * @return string
     */
    public function buildDatePeriodQuery($field, $startDate, $endDate)
    {
        $isStartDateSpecified = !empty($startDate);
        $isEndDateSpecified = !empty($endDate);
        if (!$isStartDateSpecified && !$isEndDateSpecified) {
            return '';
        }
        if ($isStartDateSpecified) {
            $startDate = $this->dateToSolrDate($startDate);
        } else {
            $startDate = '*';
        }
        if ($isEndDateSpecified) {
            $endDate = $this->dateToSolrDate($endDate);
        } else {
            $endDate = '*';
        }
        return $field . ':[' . $startDate . ' TO ' . $endDate . ']';
    }
    
    /**
     * Converts the given date into a solr date (ISO 8601)
     * @return string The solr date
     */
    public function dateToSolrDate($date)
    {
        if ($date instanceof \DateTime) {
            $timestamp = $date->getTimestamp();
        } else {
            $parsedDate = new \Zend_Date($date, self::OPTIONS_DATE_FORMAT);
            $timestamp = $parsedDate->toString('U');
        }
        
        return gmdate('Y-m-d\TH:i:s.z\Z', $timestamp);
    }
}
