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
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

class OpenSKOS_Solr_Queryparser_Editor_ParseSearchText
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
	private static $_operatorsDeterminingTextAsQuery = array('AND', 'OR', 'NOT', '&&', '||');
	
	/**
	 * Holds an array of the fields which are not tokenized and which don't need any processing of the text.
	 *
	 * @var array
	 */
	private static $_nonTokenizedFields = array('notaion', 'uri', 'LexicalLabelsPhrase', 'prefLabelPhrase', 'altLabelPhrase', 'hiddenLabelPhrase');

	/**
	 * Holds the regular expression for characters that are part of the solr syntax and needs escaping.
	 *
	 * @var string
	 */
	//!NOTE "\" must be first in the list.
	private static $_charsToEscape = array('\\', ' ', '+', '-', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', ':');
	
	/**
	 * Holds the regular expression for characters that needs to be replaced with white space for searching in tokenized fields.
	 *
	 * @var string
	 */
	private static $_tokenizeDelimiterCharsRegex = '/[^\w|\p{L}|\*|\?]+/u';
	
	/**
	 * Parses the search text to a query wich will search in each of the specified fields.
	 * If operators AND, OR or NOT are used in the text it is considered search text query and is passed as it is.
	 * If it is a simple text - it is escaped and truncated.
	 * 
	 * @param string $searchText
	 * @param array $fields
	 * @param string $truncate
	 */
	public function parse($searchText, $truncate, $fields)
	{
		$nonTokenizedFields = array_uintersect($fields, self::$_nonTokenizedFields, array('OpenSKOS_Solr_Queryparser_Editor_ParseSearchText', 'compareMultiLangFields'));
		$fields = array_udiff($fields, self::$_nonTokenizedFields, array('OpenSKOS_Solr_Queryparser_Editor_ParseSearchText', 'compareMultiLangFields'));
		
		$simpleFieldsQuery = '';
		$normalFieldsQuery = '';
		
		if ($this->_isSearchTextQuery($searchText)) {
			$simpleFieldsQuery = $this->_buildQueryForSearchTextInFields('(' . $searchText . ')', $nonTokenizedFields);
			$normalFieldsQuery = $this->_buildQueryForSearchTextInFields('(' . $searchText . ')', $fields);
		} else {
			$trimedSearchText = trim($searchText);
			if (empty($trimedSearchText)) {
				$searchText = '*';
			}
			
			$searchTextForNonTokenized = $this->_escapeSpecialChars($searchText);
			$simpleFieldsQuery = $this->_buildQueryForSearchTextInFields('(' . $searchTextForNonTokenized . ')', $nonTokenizedFields);
			
			$searchTextForTokenized = $this->_replaceTokenizeDelimiterChars($searchText);
			$normalFieldsQuery = $this->_buildQueryForSearchTextInFields('(' . $searchTextForTokenized . ')', $fields);
		}
		
		if ($simpleFieldsQuery != '' && $normalFieldsQuery != '') {
			return $simpleFieldsQuery . ' OR ' . $normalFieldsQuery;
		} else {
			return $simpleFieldsQuery . $normalFieldsQuery;
		}
	}
	
	/**
	 * Replaces all self::CHARACTERS_TO_REPLACE_WITH_SPACE with white space.
	 * 
	 * @param string $text
	 * @return string
	 */
	protected function _replaceTokenizeDelimiterChars($text)
	{
		return preg_replace(self::$_tokenizeDelimiterCharsRegex, ' ', $text);
	}
	
	/**
	 * Escapes chars that are part of solr syntax.
	 * 
	 * @param string $text
	 * @return string
	 */
	protected function _escapeSpecialChars($text)
	{
		foreach (self::$_charsToEscape as $char) {
			$text = str_ireplace($char, '\\' . $char, $text);
		}
		return $text;
	}
	
	/**
	 * Adds * on the places where the search text should be truncated.
	 *
	 * @NOTE The truncation does not happen automatically anymore. "*" or "?" has to be added manually.
	 *
	 * @param string $text
	 * @param string $truncate "both", "right" or "left"
	 * @return string
	 */
	protected function _truncateSearchText($text, $truncate)
	{
		if ( ! empty($truncate)) {
			switch ($truncate) {
				case 'left' :
					$text = '*' . $text;
					break;
				case 'right' :
					$text = $text . '*';
					break;
				case 'both' :
					$text = '*' . $text . '*';
					break;
			}
		} else {
			$text = '*' . $text . '*';
		}
	
		return $text;
	}
	
	/**
	 * Builds query for search for text inside specified fields
	 *
	 * @param string $searchText
	 * @param array $fields
	 * @return string
	 */
	protected function _buildQueryForSearchTextInFields($text, $fields)
	{
		$query = '';
		foreach ($fields as $field) {
			$query .= ( ! empty($query) ? ' OR ' : '');
			$query .= $field . ':' . $text;
		}
		return $query;
	}
	
	/**
	 * Checks the search text for is it a complex query or a simple search text.
	 * 
	 * @param string $searchText
	 */
	protected function _isSearchTextQuery($searchText)
	{
		$parts = preg_split(self::SEARCH_TEXT_SPLIT_REGEX, $searchText);
		
		foreach ($parts as $part) {
			if (in_array($part, self::$_operatorsDeterminingTextAsQuery)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Compares two fields after removing their multilang siffix.
	 * 
	 * @param string $field1
	 * @param string $field2
	 */
	public static function compareMultiLangFields($field1, $field2)
	{
		if (strpos($field1, '@') !== false) {
			$field1 = substr($field1, 0, strpos($field1, '@'));
		}
		if (strpos($field2, '@') !== false) {
			$field2 = substr($field2, 0, strpos($field2, '@'));
		}
		return strcasecmp($field1, $field2);
	}
	
	/**
	 * @return OpenSKOS_Solr_Queryparser_Editor_ParseSearchText
	 */
	public static function factory()
	{
		return new OpenSKOS_Solr_Queryparser_Editor_ParseSearchText();
	}
}