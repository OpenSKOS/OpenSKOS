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

class Editor_Models_ApiClient
{
	/**
	 * Holds the key to the concept schemes cache.
	 * 
	 * @var string
	 */
	const CONCEPT_SCHEMES_CACHE_KEY = 'ConceptSchemes';
	
	/**
	 * Holds the maximum rows limit to be used if there is no other rows limit.
	 *
	 * @var int
	 */
	const DEFAULT_MAX_ROWS_LIMIT = 1000;
	
	/**
	 * Holds a replacement of the currently logged tenant.
	 * If not set the currently logged tenant will be used.
	 *
	 * @var OpensSKOS_Db_Table_Row_Tenant
	 */
	protected $_tenant;
	
	/**
	 * Gets a list of all concept schemes.
	 * 
	 * @return array An array with the labels of the schemes
	 */
	public function getAllConceptSchemeUriTitlesMap($tenant = null, $inCollections = array()) 
	{
		return $this->getConceptSchemeMap('uri', array('dcterms_title' => 0), null, $tenant, $inCollections);
	}
	
	/**
	 * Gets array concept scheme data of type $field => $value.
	 * 
	 * @param string $field
	 * @param string $value
	 * @return array
	 */
	public function getConceptSchemeMap($field, $value, $uris = null, $tenant = null, $inCollections = array())
	{
		$conceptSchemes = $this->getConceptSchemes($uris, $tenant, $inCollections);
		
		$result = array();
		foreach ($conceptSchemes as $scheme) {
			if (is_array($value)) {
				$valueKey = key($value);
				$valueIndex = $value[$valueKey];
				$result[$scheme[$field]] = isset($scheme[$valueKey][$valueIndex]) ? $scheme[$valueKey][$valueIndex] : null;
			} else if (is_array($field)){
				$fieldKey = key($field);
				$fieldIndex = $field[$fieldKey];
				$result[$scheme[$fieldKey][$fieldIndex]] = isset($scheme[$value]) ? $scheme[$value] : null;
			} else {
				$result[$scheme[$field]] = isset($scheme[$value]) ? $scheme[$value] : null;
			}
		}
		return $result;
	}
	
	/**
	 * Gets array concept scheme data of type uri => data.
	 * 
	 * @param string $uri, optional If specified - selects the specified concept scheme
	 * @return array
	 */
	public function getConceptSchemeUriMap($uris = null, $tenant = null, $inCollections = array())
	{
		$conceptSchemes = $this->getConceptSchemes($uris, $tenant, $inCollections);
		$result = array();
		foreach ($conceptSchemes as $scheme) {
			if (isset($scheme['uri'])) {
				$result[$scheme['uri']] = $scheme;
			}
		}
		return $result;
	}
	
	/**
	 * Get all ConceptScheme documents for the current tenant.
	 * The result is once cached in Zend_Registry and retrieved from there when search again.
	 *  
	 * @param string $uri, optional If specified - selects the specified concept scheme
	 * @param string $tenant, optional If specified concept schemes for this tenant will be returned. If not - concept schemes for current tenant.
	 * @return array An array of concept scheme documents data, or the single concept scheme data if uri is specified.
	 */
	public function getConceptSchemes($uri = null, $tenant = null, $inCollections = array())
	{
		if (null === $tenant) {
			$tenant = $this->_getCurrentTenant()->code;
		}
		
		if (null === $inCollections) {
			$inCollections = array();
		}
		
		$conceptSchemes = OpenSKOS_Cache::getCache()->load(self::CONCEPT_SCHEMES_CACHE_KEY);
		if ($conceptSchemes === false) {
			$conceptSchemes = array();
		}
		
		$schemesCacheKey = $tenant . implode('', $inCollections);
		
		if (! isset($conceptSchemes[$schemesCacheKey])) {
			$query = 'class:ConceptScheme tenant:' . $tenant;
			
			if (! empty($inCollections)) {
				if (count($inCollections) == 1) {
					$query .= sprintf(' collection:%s', $inCollections[0]);
				} else {
					$query .= sprintf(' collection:(%s)', implode(' OR ', $inCollections));
				}
			}
			
			$response = Api_Models_Concepts::factory()->setQueryParams(array('rows' => self::DEFAULT_MAX_ROWS_LIMIT))->getConcepts($query);
			$response = $response['response'];
			
			$conceptSchemes[$schemesCacheKey] = array();
			if ($response['numFound'] > 0) {
				foreach ($response['docs'] as $doc) {
					$doc['iconPath'] = Editor_Models_ConceptScheme::buildIconPath($doc['uuid'], $this->_tenant);
					$conceptSchemes[$schemesCacheKey][] = $doc;
				}
			}
			
			usort($conceptSchemes[$schemesCacheKey], array('Editor_Models_ConceptScheme', 'compareDocs'));
			
			OpenSKOS_Cache::getCache()->save($conceptSchemes, self::CONCEPT_SCHEMES_CACHE_KEY);
		}
		
		if (null !== $uri) {
			if (!is_array($uri)) {
				$uri = array($uri);
			}
			$schemes = array();
			foreach ($conceptSchemes[$schemesCacheKey] as $schemeLine) {
				if (isset($schemeLine['uri']) && in_array($schemeLine['uri'], $uri)) {
					$schemes[$schemeLine['uri']] = $schemeLine;
				}
			}
			return $schemes;
		}
		
		return $conceptSchemes[$schemesCacheKey];
	}
	
	/**
	 * Gets a list of concepts matching the search options.
	 * 
	 * @param array $searchOptions
	 * @return array An array with the labels of the schemes
	 */
	public function searchConcepts($searchOptions) 
	{
		$editorOptions = OpenSKOS_Application_BootstrapAccess::getOption('editor');
		
		$availableOptions = Editor_Forms_SearchOptions::getAvailableSearchOptions();
		$availableOptions['conceptSchemes'] = $this->getAllConceptSchemeUriTitlesMap();
		
		$query = OpenSKOS_Solr_Queryparser_Editor::factory()->parse($searchOptions, $editorOptions['languages'], $availableOptions, $this->_getCurrentTenant());
		
		// Pagination
		$queryParams = array();
		if (isset($searchOptions['start']) && ! empty($searchOptions['start'])) {
			$queryParams['start'] = $searchOptions['start'];
		}
		if (isset($searchOptions['rows']) && ! empty($searchOptions['rows'])) {
			$queryParams['rows'] = $searchOptions['rows'];
		}
                //@TODO: implement language specific sort by using prefLabelSort@... Solr fields
                $queryParams['sort'] = 'prefLabelSort asc';

		$response = Api_Models_Concepts::factory()->setQueryParams($queryParams)->getConcepts($query);	
		$response = $response['response'];
		$result = array();
		$result['numFound'] = $response['numFound'];
		$result['data'] = array();
		if (isset($response['start'])) {
			$result['start'] = $response['start'];
		}
		if ($response['numFound'] > 0) {
			foreach ($response['docs'] as &$record) {
				$result['data'][] = new Api_Models_Concept($record);
			}
		}
		
		return $result;
	}
	
	/**
	 * 
	 * 
	 * @param string $query
	 * @param array $params
	 * @param bool $includeDeleted
	 * @return array Contains 
	 * 					"numFound" - int
	 * 					"start" - int
	 * 					"data" - array of Api_Models_Concept objects
	 */
	public function getConceptsByQuery($query, $params = array(), $includeDeleted = false) 
	{
		$response = Api_Models_Concepts::factory()->setQueryParams($params)->getConcepts($query, $includeDeleted);
		$response = $response['response'];
		$result = array();
		$result['numFound'] = $response['numFound'];
		$result['data'] = array();
		if (isset($response['start'])) {
			$result['start'] = $response['start'];
		}
		if ($response['numFound'] > 0) {
			foreach ($response['docs'] as &$record) {
				$result['data'][] = new Api_Models_Concept($record);
			}
		}
		
		return $result;
	}
	
	/**
	 * Gets a list of all concept schemes.
	 * 
	 * !NOTE Currently this method is not used.
	 * !TODO Check for tenant.
	 * 
	 * @return array An array with the labels of the schemes
	 */
	public function getConceptsInScheme($schemeUri)
	{
		$query = 'inScheme:"' . $schemeUri . '"';
	
		$response = Api_Models_Concepts::factory()->setQueryParams(array('rows' => self::DEFAULT_MAX_ROWS_LIMIT))->getConcepts($query);
		$response = $response['response'];
		$concepts = array();
		if ($response['numFound'] > 0) {
			foreach ($response['docs'] as &$record) {
				$concepts[] = new Api_Models_Concept($record);
			}
		}
	
		return $concepts;
	}
	
	/**
	 * Selects the count of concepts with the specified pref label.
	 * 
	 * @param string $prefLabel
	 * @return int
	 */
	public function getConceptsCountByPrefLabel($prefLabel)
	{
		$queryParams = array();
		$queryParams['rows'] = 0;
		$query = 'prefLabelPhrase:' . $prefLabel;
		$response = Api_Models_Concepts::factory()->setQueryParams($queryParams)->getConcepts($query);		
		return intval($response['response']['numFound']);
	}
	
	/**
	 * Sets the tenant. If not set the currently logged user's tenant will be used.
	 * 
	 * @param OpenSKOS_Db_Table_Row_Tenant $tenant
	 * @return Editor_Models_ApiClient
	 */
	public function setTenant(OpenSKOS_Db_Table_Row_Tenant $tenant) 
	{
		$this->_tenant = $tenant;
		return $this;
	}
	
	/**
	 * Gets the currently logged user's tenant.
	 * 
	 * @return OpenSKOS_Db_Table_Row_Tenant
	 */
	protected function _getCurrentTenant() 
	{
		if (null !== $this->_tenant) {
			return $this->_tenant;
		} else {
			$tenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
			if (null === $tenant) {
				throw new Zend_Exception('Tenant not found. Needed for request to the api.');
			}
			return $tenant;
		}
	}
	
	/**
	 * @return Editor_Models_ApiClient
	 */
	public static function factory()
	{
		return new Editor_Models_ApiClient();
	}
}