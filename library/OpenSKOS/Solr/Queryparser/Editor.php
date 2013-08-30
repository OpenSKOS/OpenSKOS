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

class OpenSKOS_Solr_Queryparser_Editor
{
	/**
	 * Holds the format in which the dates in the options must be.
	 * 
	 * @var string
	 */
	const OPTIONS_DATE_FORMAT = 'dd/MM/yyyy';
	
	/**
	 * Holds the options which are used to build the query.
	 *
	 * @var string
	 */
	protected $_searchOptions;
	
	/**
	 * Holds the available languages.
	 *
	 * @var array
	 */
	protected $_availableLanguages;
	
	/**
	 * Holds the available search options.
	 *
	 * @var array
	 */
	protected $_availableSearchOptions;
	
	/**
	 * Holds the tenant for the searching. Used to create the query.
	 *
	 * @var OpenSKOS_Db_Table_Row_Tenant
	 */
	protected $_tenant;

	/**
	 * Holds the query which is built from the parses.
	 *
	 * @var string
	 */
	protected $_query;

	/**
	 * Parses array of options to a solr query string.
	 *
	 * @param array $searchOptions
	 * @param array $availableLanguages
	 * @param array $availableSearchOptions
	 * @param OpensSKOS_Db_Table_Row_Tenant $tenant
	 * @return string
	 */
	public function parse($searchOptions, $availableLanguages, $availableSearchOptions, OpenSKOS_Db_Table_Row_Tenant $tenant)
	{
		$this->_searchOptions = $searchOptions;
		$this->_availableLanguages = $availableLanguages;
		$this->_availableSearchOptions = $availableSearchOptions;
		$this->_tenant = $tenant;
		
		$this->_query = '';
		
		$this->_parseSearchForText()
		->_parseSearchForStatus()
		->_parseSearchForOnlyTopConcepts()
		->_parseSearchForOnlyOrphanedConcepts()		
		->_parseSearchForConceptScheme()
		->_parseSearchForUserInteraction()
		->_parseSearchForTenants()
		->_parseSearchForCollections();
		
		// If no search query yet - search for everything
		if (empty($this->_query)) {
			$this->_query = '*:*';
		}
		
		return $this->_query;
	}

	/**
	 * Parses the part of the query for searching inside labels and document properties.
	 *
	 * @return OpenSKOS_Solr_Queryparser_Editor
	 */
	protected function _parseSearchForText()
	{
		$fields = array();

		$searchLanguages = $this->_getSearchLanguages();
		
		// Search in notation
		if (isset($this->_searchOptions['searchNotation']) && ! empty($this->_searchOptions['searchNotation'])) {
			$fields[] = 'notation';
		}
		
		// Search in uri
		if (isset($this->_searchOptions['searchUri']) && ! empty($this->_searchOptions['searchUri'])) {
			$fields[] = 'uri';
		}
		
		// Search in labels.
		$allLabels = array();
		if (isset($this->_availableSearchOptions['labels'])) {
			$allLabels = $this->_availableSearchOptions['labels'];
		}

		// If all labels are selected - use LexicalLabelsPhrase for search
		if (isset($this->_searchOptions['label']) && count($allLabels) != count($this->_searchOptions['label'])) {
			if ( ! empty($this->_searchOptions['label'])) {
				foreach ($this->_searchOptions['label'] as $label) {
					$fields = array_merge($fields, $this->_buildPerLanguageFields($label . 'Phrase', $searchLanguages));
				}
			} else {
				// If there are no labels selected - do not search in labels.
			}
		} else {
			$fields = array_merge($fields, $this->_buildPerLanguageFields('LexicalLabelsPhrase', $searchLanguages));
		}

		// Search in document properties.
		$allProperties = array();
		if (isset($this->_availableSearchOptions['docproperties'])) {
			$allProperties = $this->_availableSearchOptions['docproperties'];
		}

		// If properties option not set or all options are selected - use DocumentationPropertiesText for search
		if (isset($this->_searchOptions['properties']) && count($allProperties) != count($this->_searchOptions['properties'])) {
			if ( ! empty($this->_searchOptions['properties'])) {
				foreach ($this->_searchOptions['properties'] as $property) {
					$fields = array_merge($fields, $this->_buildPerLanguageFields($property . 'Text', $searchLanguages));
				}
			} else {
				// If there are no doc properties selected - do not search in doc properties.
			}
		} else {
			$fields = array_merge($fields, $this->_buildPerLanguageFields('DocumentationPropertiesText', $searchLanguages));
		}
		
		if ( ! empty($fields)) {
			
			$searchText = (isset($this->_searchOptions['searchText']) ? $this->_searchOptions['searchText'] : '');
			$truncate = (isset($this->_searchOptions['truncate']) ? $this->_searchOptions['truncate'] : '');
			
			$query = OpenSKOS_Solr_Queryparser_Editor_ParseSearchText::factory()->parse($searchText, $truncate, $fields);
			
			if ( ! empty($query)) {
				$this->_addDefaultQuerySeparator();
				$this->_query .= '(' . $query . ')';
			}
		}

		return $this;
	}

	/**
	 * Parses the part of the query for searching for specific statuses and for the field toBeChecked.
	 *
	 * @return OpenSKOS_Solr_Queryparser_Editor
	 */
	protected function _parseSearchForStatus()
	{
		$allStatuses = array();
		if (isset($this->_availableSearchOptions['statuses'])) {
			$allStatuses = $this->_availableSearchOptions['statuses'];
		}

		// Extract the option "toBeChecked" from statuses array.
		$searchForStatuses = array();
		if (isset($this->_searchOptions['status'])) {
			$searchForStatuses = $this->_searchOptions['status'];
		}

		// If there are statuses selected - and not all of them are selected - adds the statuses to the query.
		if ( ! empty($searchForStatuses) && count($allStatuses) != count($searchForStatuses)) {
				
			$statusQuery = '';
			if (in_array('none', $searchForStatuses)) {
				$excludeStatuses = array_diff($allStatuses, $searchForStatuses);
				$statusQuery .= '-status:(' . implode(' OR ', $excludeStatuses) . ')';
			} else {
				foreach ($searchForStatuses as $statusInd =>  $status) {
					if ($statusInd != 0) {
						$statusQuery .= ' OR ';
					}
					$statusQuery .= 'status:' . $status;
				}
				if ( ! empty($statusQuery)) {
					if (strpos($statusQuery, ' OR ') !== false) {
						// If we have query -status:["" TO *] without anythiong else the brackets brake it. So add them only if we have ORs.
						$statusQuery = '(' . $statusQuery . ')';
					}
				}
			}
			
			if ( ! empty($statusQuery)) {
				$this->_addDefaultQuerySeparator();
				$this->_query .= $statusQuery;
			}
			
		}

		if (isset($this->_searchOptions['toBeChecked']) && ! empty($this->_searchOptions['toBeChecked'])) {
			$this->_addDefaultQuerySeparator();
			$this->_query .= 'toBeChecked:true';
		}

		return $this;
	}


	/**
	 * Parses the part of the query for searching for only top concepts.
	 * Thoese are concepts that are top of at laest one other concept.
	 *
	 * @return OpenSKOS_Solr_Queryparser_Editor
	 */
	protected function _parseSearchForOnlyTopConcepts()
	{
		if (isset($this->_searchOptions['topConcepts']) && ! empty($this->_searchOptions['topConcepts'])) {
			$this->_addDefaultQuerySeparator();
			$this->_query .= 'topConceptOf:[* TO *]';
		}

		return $this;
	}

	/**
	 * Parses the part of the query for searching for only orphaned concepts.
	 * Thoese are concepts that does not have any semantic relations (broader, narrower or related).
	 *
	 * @return OpenSKOS_Solr_Queryparser_Editor
	 */
	protected function _parseSearchForOnlyOrphanedConcepts()
	{
		if (isset($this->_searchOptions['orphanedConcepts']) && ! empty($this->_searchOptions['orphanedConcepts'])) {
			$this->_addDefaultQuerySeparator();
			$this->_query .= '-broader:[* TO *] -narrower:[* TO *] -related:[* TO *]';
		}

		return $this;
	}

	/**
	 * Parses the part of the query for searching for specified concept.
	 *
	 * @return OpenSKOS_Solr_Queryparser_Editor
	 */
	protected function _parseSearchForConceptScheme()
	{
		if (isset($this->_availableSearchOptions['conceptSchemes'])) {
			$allSchemes = $this->_availableSearchOptions['conceptSchemes'];
		} else {
			$allSchemes = array();
		}
		
		if (isset($this->_searchOptions['conceptScheme']) 
				&& ! empty($this->_searchOptions['conceptScheme'])
				&& count($allSchemes) != count($this->_searchOptions['conceptScheme'])) {
			
			$query = '';
			foreach ($this->_searchOptions['conceptScheme'] as $scheme) {
				$query .= ( ! empty($query) ? ' OR ' : '');
				$query .= 'inScheme:"' . $scheme . '"';
			}
			
			if ( ! empty($query) && count($this->_searchOptions['conceptScheme']) > 1) {
				$query = '(' . $query . ')';
			}
			
			if ( ! empty($query)) {
				$this->_addDefaultQuerySeparator();
				$this->_query .= $query;
			}
		}

		return $this;
	}

	/**
	 * Parses the part of the query for searching for created by users and created between dates or modified by users and created between dates.
	 *
	 * @return OpenSKOS_Solr_Queryparser_Editor
	 */
	protected function _parseSearchForUserInteraction()
	{
		$modelUsers = new OpenSKOS_Db_Table_Users();
		$allUsersRows = $modelUsers->fetchAll($modelUsers->select()->where('tenant=?', $this->_tenant->code))->toArray();
		$allUsers = array();
		foreach ($allUsersRows as $userRow) {
			$allUsers[$userRow['id']] = (isset($userRow['name']) ? $userRow['name'] : $userRow['email']);
		}
		
		// Prepare all interaction types for which will search. (created, modified, approved etc.)
		$searchForInteractionTypes = array();
		if (isset($this->_searchOptions['userInteractionType']) && ! empty($this->_searchOptions['userInteractionType'])) {
			$searchForInteractionTypes = $this->_searchOptions['userInteractionType'];
		} else if (isset($this->_availableSearchOptions['interactiontypes'])) {
			$searchForInteractionTypes = array_keys($this->_availableSearchOptions['interactiontypes']);
		}
		
		$query = '';
		foreach ($searchForInteractionTypes as $interactionType) {
			$interactionByUsersQuery = $this->_buildUsersAndUserRolesQuery($interactionType . '_by', 'interactionByUsers', 'interactionByRoles', $allUsers);
			$interactionDatePeriodQuery = $this->_buildDatePeriodQuery($interactionType . '_timestamp', 'interactionDateFrom', 'interactionDateTo');
			$interactionQuery = $this->_combineUsersAndDatePeriodQuery($interactionByUsersQuery, $interactionDatePeriodQuery);
			
			if ( ! empty($interactionQuery)) {
				$query .= ( ! empty($query) ? ' OR ' : '');
				$query .= $interactionQuery;
			}
		}
		
		if ( ! empty($query) && count($searchForInteractionTypes) > 1) {
			$query = '(' . $query . ')';
		}

		if ( ! empty($query)) {
			$this->_addDefaultQuerySeparator();
			$this->_query .= $query;
		}

		return $this;
	}
	
	/**
	 * Parses the part of the query for searching for specified tenants.
	 * By default the search is performed for the current tenant.
	 *
	 * @return OpenSKOS_Solr_Queryparser_Editor
	 */
	protected function _parseSearchForTenants()
	{
		$modelTenants = new OpenSKOS_Db_Table_Tenants();
		$allTenants = $modelTenants->fetchAll();
		
		$searchInTenants = array();
		if (isset($this->_searchOptions['tenants']) && ! empty($this->_searchOptions['tenants'])) {
			$searchInTenants = $this->_searchOptions['tenants'];			
		} else {
			$searchInTenants[] = $this->_tenant->code;
		}
		
		if ( ! empty($searchInTenants) && count($allTenants) != count($searchInTenants)) {
			$query = '';
			foreach ($searchInTenants as $tenantCode) {
				$query .= ( ! empty($query) ? ' OR ' : '');
				$query .= 'tenant:' . $tenantCode;
			}
			
			if ( ! empty($query) && count($searchInTenants) > 1) {
				$query = '(' . $query . ')';
			}
			
			if ( ! empty($query)) {
				$this->_addDefaultQuerySeparator();
				$this->_query .= $query;
			}
		}
	
		return $this;
	}
	
	/**
	 * Parses the part of the query for searching for specified collections.
	 * By default the search is performed for all collections.
	 *
	 * @return OpenSKOS_Solr_Queryparser_Editor
	 */
	protected function _parseSearchForCollections()
	{
		$modelCollections = new OpenSKOS_Db_Table_Collections();
		$allCollections = $modelCollections->fetchAll($modelCollections->select()->where('tenant = ?', $this->_tenant->code));
	
		$searchInCollections = array();
		if (isset($this->_searchOptions['collections'])) {
			$searchInCollections = $this->_searchOptions['collections'];
		}
		
		if (! empty($searchInCollections) && count($searchInCollections) != count($allCollections)) {
			$query = '';
			foreach ($searchInCollections as $collectionId) {
				$query .= ( ! empty($query) ? ' OR ' : '');
				$query .= 'collection:' . $collectionId;
			}
				
			if ( ! empty($query) && count($searchInCollections) > 1) {
				$query = '(' . $query . ')';
			}
				
			if ( ! empty($query)) {
				$this->_addDefaultQuerySeparator();
				$this->_query .= $query;
			}
		}
	
		return $this;
	}

	/**
	 * Gets search languages from the options.
	 *
	 * @param array $options
	 * @return array
	 */
	protected function _getSearchLanguages()
	{
		$searchLanguages = array();
		if (isset($this->_searchOptions['languages']) && ! empty($this->_searchOptions['languages'])) {
			$searchLanguages = $this->_searchOptions['languages'];
		}

		// If there are choosen languages and if they are all the languages from the settings - then the languages will not be part of the query.
		if ( ! empty($searchLanguages)) {
			if (isset($this->_availableLanguages) && (count($this->_availableLanguages) == count($searchLanguages))) {
				return array();
			}
		}

		return $searchLanguages;
	}

	
	/**
	 * Returns array of fields for each language. The field itself is returned if no languages.
	 *
	 * @param string $field
	 * @param array $languages
	 * @return array
	 */
	protected function _buildPerLanguageFields($field, $languages)
	{
		$fields = array();
		if ( ! empty($languages)) {
			foreach ($languages as $language) {
				$fields[] = $field . '@' . $language;
			}
		} else {
			$fields[] = $field;
		}
		return $fields;
	}
	
	/**
	 * Builds query for search by users and by users from specific role.
	 * All the users from the specified role will be added with OR to the query of users.
	 * If the final query of users includes all users - an empty string is returned - the query should not be applied.
	 *
	 * @param string $field The field to search by.
	 * @param string $usersOption The option in $this->_searchOptions to use as users to search by
	 * @param string $usersRoleOption The option in $this->_searchOptions to use as role to search by
	 * @return string
	 */
	protected function _buildUsersAndUserRolesQuery($field, $usersOption, $usersRoleOption, $allUsers)
	{
		$searchUsers = array();

		if (isset($this->_searchOptions[$usersRoleOption]) && ! empty($this->_searchOptions[$usersRoleOption])) {
			$modelUsers = new OpenSKOS_Db_Table_Users();
			$usersByRole = $modelUsers->fetchAll(
				$modelUsers->select()
					->where('tenant=?', $this->_tenant->code)
					->where('role IN (?)', $this->_searchOptions[$usersRoleOption])
					->group('id')
			);
			foreach ($usersByRole as $user) {
				$searchUsers[] = $user->id;
			}
		}

		if (isset($this->_searchOptions[$usersOption]) && ! empty($this->_searchOptions[$usersOption])) {
			$searchUsers = array_merge($searchUsers, $this->_searchOptions[$usersOption]);
			$searchUsers = array_unique($searchUsers);
		}

		$query = '';
		// If not all users are selected - adds each of them to the query.
		if (count($allUsers) != count($searchUsers)) {
			foreach ($searchUsers as $user) {
				$query .= ( ! empty($query) ? ' OR ' : '');
				$query .= $field . ':' . $user;
				if ($field == 'created_by') {
					$query .= ' OR ';
					$query .= 'dcterms_creator:"' . $allUsers[$user] . '"';
				}
			}
		}

		return $query;
	}

	/**
	 * Builds query for date period - like created_timestamp:[{startDate} TO {endDate}].
	 *
	 * @param string $field The field to search by.
	 * @param string $startDateOption The option in $this->_searchOptions to use as start date (it is converted to timestamp)
	 * @param string $endDateOption The option in $this->_searchOptions to use as end date (it is converted to timestamp)
	 * @return string
	 */
	protected function _buildDatePeriodQuery($field, $startDateOption, $endDateOption)
	{
		$isStartDateSpecified = (isset($this->_searchOptions[$startDateOption]) && ! empty($this->_searchOptions[$startDateOption]));
		$isEndDateSpecified = (isset($this->_searchOptions[$endDateOption]) && ! empty($this->_searchOptions[$endDateOption]));
		if (( ! $isStartDateSpecified) && ( ! $isEndDateSpecified)) {
			return '';
		}

		if ($isStartDateSpecified) {
			$startDate = $this->_dateToSolrDate($this->_searchOptions[$startDateOption]);
		} else {
			$startDate = '*';
		}

		if ($isEndDateSpecified) {
			$endDate = $this->_dateToSolrDate($this->_searchOptions[$endDateOption]);
		} else {
			$endDate = '*';
		}

		return $field . ':[' . $startDate . ' TO ' . $endDate . ']';
	}
	
	/**
	 * Combines query for users and query for date period in one query.
	 * 
	 * @param string $usersQuery
	 * @param string $datePeriodQuery
	 * @return string
	 */
	protected function _combineUsersAndDatePeriodQuery($usersQuery, $datePeriodQuery)
	{
		$result = '';
		if ( ! empty($usersQuery) && ! empty($datePeriodQuery)) {
			$result = '((' . $usersQuery . ') AND ' . $datePeriodQuery . ')';
		} else if ( ! empty($usersQuery)) {
			$result = '(' . $usersQuery . ')';
		} else if ( ! empty($datePeriodQuery)) {
			$result = $datePeriodQuery;
		}
		return $result;
	}
	
	/**
	 * Adds to the query the default separator for use between query parts.
	 *
	 * @return OpenSKOS_Solr_Queryparser_Editor
	 */
	protected function _addDefaultQuerySeparator()
	{
		if ( ! empty($this->_query)) {
			$this->_query .= ' ';
		}
		return $this;
	}

	/**
	 * Converts the given date into a solr date (ISO 8601)
	 *
	 * @return string The solr date
	 */
	protected function _dateToSolrDate($date)
	{
		$result = new Zend_Date($date, self::OPTIONS_DATE_FORMAT);
		$result = $result->get(Zend_Date::ISO_8601);

		// Fix for the solr date.
		// The format from php ISO_8601 is 2004-02-12T15:19:21+00:00
		// The format for solr is 2004-02-12T15:19:21.0Z
		$result = substr($result, 0, strpos($result, '+')) . '.0Z';
		return $result;
	}
	
	/**
	 * @return OpenSKOS_Solr_Queryparser_Editor
	 */
	public static function factory()
	{
		return new OpenSKOS_Solr_Queryparser_Editor();
	}
}