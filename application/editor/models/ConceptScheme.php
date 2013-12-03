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
 * @author     Boyan Bonev, Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

/**
 * Holds the editor representation of a ConceptScheme
 */
class Editor_Models_ConceptScheme extends Api_Models_Concept 
{	
	/**
	 * Copy constructor.
	 * 
	 * @param Api_Models_Concept $copyFrom
	 */
	public function __construct(Api_Models_Concept $copyFrom)
	{
		parent::__construct(array_merge($copyFrom->getData(), array('class' => 'ConceptScheme')));
	}
	
	public function save($extraData = null, $commit = true)
	{
		/* The concepts relations is implemented but needs to be tested. Its not needed for now.
		$this->updateRelatedConcepts($extraData['includeConcepts']);
		unset($extraData['includeConcepts']);
		*/
		
		parent::save($extraData, $commit);
	}
	
	/**
	 * Parses all the form data and prepares it for loading into the model.
	 * 
	 * @param array $formData
	 * @return $sextraData
	 */
	public function transformFormData(array &$formData) 
	{	
		$formMapping = $this->_getFormMapping();
	
		$extraData = array();
		$apiClient = new Editor_Models_ApiClient();
		
		$apiModelConcepts = Api_Models_Concepts::factory();
		
		foreach ($formData as $key => $value) {
			
			if (in_array($key, $formMapping['languageFields'])) {
				
				foreach ($value as $languageCode => $perLanguageValues) {
					
					if ( ! empty($languageCode)) {
						$formData[$key][$languageCode] = $perLanguageValues[0]; // These fields always contain array of one element because the multitext fields are used.
					} else {
						unset($formData[$key][$languageCode]);
					}
				}
				
			} else if (in_array($key, $formMapping['uuid2uri'])) {
				
				if (is_array($formData[$key]) && array_filter($formData[$key])) {
					
					foreach ($formData[$key] as $position => $value) {
						
						if ( ! empty($value)) {
							$concept = $apiModelConcepts->getConcept($value);
							if (null !== $concept) {
								$formData[$key][$position] = $concept['uri'];
							} else {
								unset($formData[$key][$position]);
							}
						} else {
							unset($formData[$key][$position]);
						}
					}
					
				} else {
					unset($formData[$key]);
				}
			}
		}
	
		foreach ($formData as $key => $value) {
			if (in_array($key, $formMapping['extraFields'])){
				
				if ($key == 'uriCode' || $key == 'uriBase') {
					$extraData['uri'] = Editor_Models_ConceptScheme::buildUri($formData['uriCode'], $formData['uriBase']);
					unset($formData['uriCode']);
					unset($formData['uriBase']);
					continue;
				}
				
				$extraData[$key] = $formData[$key];
				unset($formData[$key]);
			}
		}
	
		return $extraData;
	}
	
	/**
	 * Gets the data of the concept scheme in a way that can be populated in the form.
	 * 
	 * @TODO If used for edit needs to be extended.
	 * 
	 */
	public function toForm($extraData, $uriCode = '', $uriBase = '')
	{
		$data = array_merge($this->getData(), $extraData);
		
		$formMapping = $this->_getFormMapping();
		
		// Transform the uri field
		$emptyCodeUri = self::buildUri('', $uriBase);
		$data['uriBase'] = $emptyCodeUri;
		if (empty($uriCode)) {
			$data['uriCode'] = str_replace($data['uriBase'], '', $this['uri']);
		} else {
			$data['uriCode'] = $uriCode;
		}
		
		// Transform the language fields and collect languages.
		$languagesData = array();
		foreach ($formMapping['languageFields'] as $field) {
			$newDataForField = array();
			foreach ($data[$field] as $languageCode => $perLangValue) {
				$newDataForField[] = array('languageCode' => $languageCode, 'value' => array($perLangValue));
				$languagesData[strtoupper($languageCode)] = array(strtoupper($languageCode) => $languageCode);
			}
			$data[$field] = $newDataForField;
		}
		
		$data['conceptLanguages'] = $languagesData;
		
		return $data;
	}
	
	/**
	 * Remove a concept from hasTopConcept
	 * 
	 * @param string $conceptUri
	 * @return Editor_Models_ConceptScheme
	 */
	public function removeTopConcept($conceptUri)
	{
		if (isset($this['hasTopConcept']) && is_array($this['hasTopConcept'])) {
			$this->data['hasTopConcept'] = array_filter($this['hasTopConcept'], function ($v) use ($conceptUri){ return $v !== $conceptUri; });
			$this->setConceptData($this->getData());
			$this->save($this->getCurrentRequiredData());
		}
		return $this;
	}
	
	/**
	 * Delete a concept scheme from everywhere.
	 * 
	 * @param bool $commit, optional, Default: true
	 * @param bool $deletedBy, optional
	 */
	public function delete($commit = true, $deletedBy = null)
	{
		if (null === $deletedBy) {
			$actionUser = OpenSKOS_Db_Table_Users::fromIdentity();
			if (null !== $actionUser) {
				$deletedBy = $actionUser->id;
			}
		}
		
		$affectedConceptsQuery = '(inScheme:"' . $this['uri'] . '" OR topConceptOf:"' . $this['uri'] . '") AND tenant:' . $this['tenant'];
		
		// Update affected concepts by steps. 
		$rows = 1000;
		do {
			// Get concepts which has the scheme in topConceptOf or inScheme.
			$concepts = Editor_Models_ApiClient::factory()->getConceptsByQuery($affectedConceptsQuery, array('rows' => $rows));

			if (count($concepts['data']) > 0) {
			
				// Remove the concept from topConceptOf or inScheme of each concept. Delete concept if it does not have other schemes in inScheme.
				foreach ($concepts['data'] as $key => $concept) {
					
					$concept = new Editor_Models_Concept($concept);
					
					$data = $concept->getData();
					
					$updateData = array();
					$updateExtraData = array();
					if (isset($data['inScheme'])) {
						$updateData['inScheme'] = array_diff($data['inScheme'], array($this['uri']));
					}
					if (isset($data['topConceptOf'])) {
						$updateData['topConceptOf'] = array_diff($data['topConceptOf'], array($this['uri']));
					}
					
					if (empty($updateData['inScheme'])) {
						$updateExtraData['deleted'] = true;
						$updateExtraData['deleted_by'] = $deletedBy;
					}
										
					$concept->update($updateData, $updateExtraData, false, true);
					
					if ($key == (count($concepts['data']) - 1) && $commit) {
						$this->solr()->commit();
					}
				}
			}
			
		} while (count($concepts['data']) == $rows);
		
		// Update the concept scheme
		$updateExtraData['deleted'] = true;
		$updateExtraData['deleted_by'] = $deletedBy;
		$this->update(array(), $updateExtraData);
		
		// Commit
		if ($commit) {
			$this->solr()->commit();
		}
	}
	
	/**
	 * Tries to perform real update over the concept scheme without loosing any old data and properly changing the update data.
	 *
	 * @param array $updateData Leave empty array if no normal data is updated.
	 * @param array $updateExtraData Leave empty array if no extra data is updated.
	 * @param bool $commit, optional, Default: true
	 * @return bool True if the save is successfull. False otherwise. You can see errors by calling getErrors();
	 */
	public function update($updateData, $updateExtraData, $commit = true)
	{
		$data = $this->getData();
		$extraData = $this->getCurrentRequiredData();
	
		//!TODO The fallowing should be added to required data or all the process of editing concept should be refactored so that old data is not lost.
		// Data which will be lost on update if not remembered...
		if (isset($data['created_by'])) {
			$extraData['created_by'] = $data['created_by'];
		}
		if (isset($data['created_timestamp'])) {
			$extraData['created_timestamp'] = $data['created_timestamp'];
		}
		if (isset($data['approved_by'])) {
			$extraData['approved_by'] = $data['approved_by'];
		}
		if (isset($data['approved_timestamp'])) {
			$extraData['approved_timestamp'] = $data['approved_timestamp'];
		}
		if (isset($data['modified_by'])) {
			$extraData['modified_by'] = $data['modified_by'];
		}
		if (isset($data['modified_timestamp'])) {
			$extraData['modified_timestamp'] = $data['modified_timestamp'];
		}
	
		$data = array_merge($data, $updateData);
		$extraData = array_merge($extraData, $updateExtraData);
	
		// The actual update...
		$this->setConceptData($data, $extraData);
		return $this->save($extraData, $commit);
	}
	
	/**
	 * Updates all concepts that must be included (related) to the concept scheme.
	 * For each of them the inScheme field is updated and if they are part of hasTopConcept - the topConceptOf field is updated.
	 *
	 * !NOTE Currently this method is not used.
	 * !TODO Check for tenant.
	 *
	 * @param array $includeConcepts Array of uris of concepts to be included in scheme.
	 */
	protected function updateRelatedConcepts($includeConcepts)
	{
		// Old related concepts will be used for removing from that scheme if not part of the $newRelatedConcepts.
		$oldRelatedConcepts = Editor_Models_ApiClient::factory()->getConceptsInScheme($this['uri']);
	
		// All concepts - top or regular will be added to the scheme (by updating theirs inScheme field).
		$newRelatedConceptsUris = array_unique(array_merge($this['hasTopConcept'], $includeConcepts));
		$newRelatedConcepts = Api_Models_Concepts::factory()->getEnumeratedConceptsMapByUris($newRelatedConceptsUris);
		foreach ($newRelatedConcepts as $concept) {
			$conceptData = $concept->getData();
	
			if (! isset($conceptData['inScheme'])) {
				$conceptData['inScheme'] = array();
			}
			if ( ! isset($conceptData['topConceptOf'])) {
				$conceptData['topConceptOf'] = array();
			}
			
			$needsUpdate = false;
			if ( ! in_array($this['uri'], $conceptData['inScheme'])) {
				$conceptData['inScheme'][] = $this['uri'];
				$needsUpdate = true;
			}
			
			if (in_array($conceptData['uri'], $this['hasTopConcept'])
					&& ! in_array($this['uri'], $conceptData['topConceptOf'])) {
				$conceptData['topConceptOf'][] = $this['uri'];
				$needsUpdate = true;
			}
	
			if ($needsUpdate) {
				$concept->setConceptData($conceptData);
				$concept->save($concept->getCurrentRequiredData());
			}
		}
	
		// Remove scheme from the inScheme field of concepts which should not be related anymore.
		$conceptsToRemove = array_udiff($oldRelatedConcepts, $newRelatedConcepts, array('Api_Models_Concept', 'compare'));
		foreach ($conceptsToRemove as $concept) {
			$conceptData = $concept->getData();
			$conceptData['inScheme'] = array_diff($conceptData['inScheme'], array($this['uri']));
			$conceptData['topConceptOf'] = array_diff($conceptData['topConceptOf'], array($this['uri']));
			$concept->setConceptData($conceptData);
			$concept->save($concept->getCurrentRequiredData());
		}
	}
	
	/**
	 * Form content to model fields mapper.
	 * 
	 * @return array
	 */
	protected function _getFormMapping()
	{
		$mapping['extraFields'] = array(
				'uuid',
				'uriCode',
				'uriBase',
				'collection',
				'includeConcepts'
		);
		$mapping['languageFields'] = array(
				'dcterms_title',
				'dcterms_description',
				'dcterms_creator'
		);
		$mapping['uuid2uri'] = array(
				'hasTopConcept',
				'includeConcepts'
		);
		return $mapping;
	}
	
	/**
	 * Get resource fields rdf mapping for a concept scheme.
	 * 
	 * @return array
	 */
	protected function getRdfMapping()
	{
		return array('resourceFields' => array('hasTopConcept'), 'dctermsDateFields' => array(), 'simpleSkosFields' => array());
	}
	
	/**
	 * Get dc fields mapping
	 *
	 * @return array
	 */
	protected function getDcFieldsMapping()
	{
		return array('normalFields' => array(), 'languageFields' => array('dcterms_title', 'dcterms_description', 'dcterms_creator'));
	}
	
	/**
	 * Gets xml nodes which will be part of the concept xml
	 *
	 * @param DOMXPath $xpath
	 * @return array
	 */
	protected function getXmlNodes(DOMXPath $xpath)
	{
		$rdfNodes = $this->getRdfFromData();
		$dcNodes = $this->getDcNodesFromData();
		$nodes = array_merge($rdfNodes, $dcNodes);
		 
		return $nodes;
	}
	
	/**
	 * Translate data from dc mapping to dc nodes
	 *
	 * @return array
	 */
	protected function getDcNodesFromData()
	{
		$dcNodes = array();
		$dcMapping = $this->getDcFieldsMapping();
	
		foreach ($dcMapping['normalFields'] as $field) {
			if (isset($this->data[$field])) {
				$dcNodes = array_merge($dcNodes, OpenSKOS_Rdf_Parser::createDcField($field, $this->data[$field]));
			}
		}
	
		foreach ($dcMapping['languageFields'] as $field) {
			if (isset($this->data[$field])) {
				$dcNodes = array_merge($dcNodes, OpenSKOS_Rdf_Parser::createDcLanguageField($field, $this->data[$field]));
			}
		}
	
		return $dcNodes;
	}
	
	/**
	 * Builds concept scheme uri from the given uri code.
	 * Blank code can be passed for template uri.
	 *
	 * @param string $uriCode
	 * @param string $uriBase
	 */
	public static function buildUri($uriCode, $uriBase)
	{
		if (empty($uriBase)) {
			throw new Exception('Must provide uri base.');
		} else {
			if (substr($uriBase, -1) != '/') {
				$uriBase .= '/';
			}
			return $uriBase . $uriCode;
		}
	}
	
	/**
	 * Builds the path to the concept scheme icon.
	 * Returns empty string if the file does not exist. 
	 * 
	 * @param srtring $uuid
	 * @param OpenSKOS_Db_Table_Row_Tenant $tenant optional, Default null. If not set the currently logged one will be used.
	 * @return string
	 */
	public static function buildIconPath($uuid, $tenant = null)
	{
		$editorOptions = OpenSKOS_Application_BootstrapAccess::getBootstrap()->getOption('editor');
		
		if (null === $tenant) {
			$tenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
		}
		
		// We always need tenant for getting icon path.
		if (null !== $tenant) {
		
			if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['assignPath'])) {
				$iconsAssignPath = APPLICATION_PATH . $editorOptions['schemeIcons']['assignPath'] . '/' . $tenant->code;
			} else {
				$iconsAssignPath = APPLICATION_PATH . Editor_Forms_UploadIcon::DEFAULT_ASSIGN_PATH . '/' . $tenant->code;
			}
			
			if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['assignHttpPath'])) {
				$iconsAssignHttpPath = $editorOptions['schemeIcons']['assignHttpPath'] . '/' . $tenant->code;
			} else {
				$iconsAssignHttpPath = Editor_Forms_UploadIcon::DEFAULT_ASSIGN_HTTP_PATH . '/' . $tenant->code;
			}
			
			if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['extension'])) {
				$iconsExtension = $editorOptions['schemeIcons']['extension'];
			} else {
				$iconsExtension = 'png';
			}
			
			if (is_file($iconsAssignPath . '/' . $uuid . '.' . $iconsExtension)) {
				return $iconsAssignHttpPath . '/' . $uuid . '.' . $iconsExtension . '?nocache=' . time();
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
	
	/**
	 * Compares two concept scheme documents.
	 * 
	 * @param array $doc1
	 * @param array $doc2
	 */
	public static function compareDocs($doc1, $doc2)
	{
		return strcasecmp($doc1['dcterms_title'][0], $doc2['dcterms_title'][0]);
	}
	
	/**
	 * @param array $data
	 * @param Api_Models_Concepts $model
	 * @return Editor_Models_ConceptScheme
	 */
	public static function factory($data = array(), Api_Models_Concepts $model = null)
	{
		return new Editor_Models_ConceptScheme(parent::factory($data, $model));
	}
}