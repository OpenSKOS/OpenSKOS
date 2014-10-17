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

/**
 * Holds the editor representation of a concept.
 * Extends Api_Models_Concept
 *
 */
class Editor_Models_Concept extends Api_Models_Concept
{
	/**
	 * Holds errors from any kind of validation.
	 *
	 * @var array
	 */
	private $_errors = array();

	/**
	 * Implements a copy constructor to copy from Api_Models_Concept.
	 *
	 * @TODO Erorr handling on null/different object type initialization.
	 * @param Api_Models_Concept $copyFrom
	 */
	public function __construct(Api_Models_Concept $copyFrom)
	{
		parent::__construct($copyFrom->getData(), $copyFrom->getModel());
	}

	/**
	 * Checks is the concept valid and saves it if it is.
	 *
	 * @see Api_Models_Concept::save()
	 * @param array $extraData
	 * @param bool $commit, optional To do a solr commit or not. Default: true.
	 * @param bool $ignoreValidation, optional, Default: false If set to true the validation on save will not be performed.
	 * @return bool True if the save is successfull. False otherwise. You can see errors by calling getErrors();
	 */
	public function save($extraData = null, $commit = true, $ignoreValidation = false)
	{
		if ($ignoreValidation || $this->_validateSave($extraData)) {
			parent::save($extraData, $commit);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Retrieve all errors if any action fails validation.
	 *
	 * @return array Array of Editor_Models_ConceptValidator_Error objects.
	 */
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	 * Validates concept for saving
	 *
	 * @param array
	 */
	protected function _validateSave($extraData)
	{
		// First check for cycles. If there are cycles we can not continue with the other validators.
		$validator = Editor_Models_ConceptValidator_SameBroaderAndNarrower::factory();
		if ( ! $validator->isValid($this, $extraData)) {
			$this->_errors[] = $validator->getError();
			return false;
		}
		$validator = Editor_Models_ConceptValidator_CycleInBroaders::factory();
		if ( ! $validator->isValid($this, $extraData)) {
			$this->_errors[] = $validator->getError();
			return false;
		}
		$validator = Editor_Models_ConceptValidator_CycleInNarrowers::factory();
		if ( ! $validator->isValid($this, $extraData)) {
			$this->_errors[] = $validator->getError();
			return false;
		}

		// Check for other validators.
		$validators = array();
        $validators[] = Editor_Models_ConceptValidator_UniqueNotation::factory();
		$validators[] = Editor_Models_ConceptValidator_RelatedToItself::factory();
		$validators[] = Editor_Models_ConceptValidator_IsAtLeastInOneScheme::factory();
		$validators[] = Editor_Models_ConceptValidator_DuplicateBroaders::factory();
		$validators[] = Editor_Models_ConceptValidator_DuplicateNarrowers::factory();
		$validators[] = Editor_Models_ConceptValidator_DuplicateRelated::factory();
		$validators[] = Editor_Models_ConceptValidator_UnneededDirectBroaders::factory();
		$validators[] = Editor_Models_ConceptValidator_UnneededDirectNarrowers::factory();
		$validators[] = Editor_Models_ConceptValidator_UniquePrefLabelInScheme::factory();
		$validators[] = Editor_Models_ConceptValidator_ExpiredWithRelations::factory();

		$isValid = true;
		foreach ($validators as $validator) {
			if ( ! $validator->isValid($this, $extraData)) {
				$isValid = false;
				$this->_errors[] = $validator->getError();
			}
		}

		return $isValid;
	}

	/**
	 * Searches for relation with the $searchConcept - direct or via intermidate concepts.
	 *
	 * @param string $type broader, narrower or related.
	 * @param Editor_Models_Concept $searchConcept
	 * @param bool $checkFirstLevel Searches only for relation with intermidiate concept.
	 */
	public function hasRelationInDepth($type, Editor_Models_Concept $searchConcept, $checkFirstLevel = true)
	{
		$relations = $this->getRelationsByField($type, null, array($this, 'getAllRelations'));

		foreach ($relations as $relation) {
			if ($relation['uuid'] == $searchConcept['uuid']) {
				if ($checkFirstLevel) {
					return true;
				}
			} else {
				$relation = new Editor_Models_Concept($relation);
				if ($relation->hasRelationInDepth($type, $searchConcept)) {
					return true;
				}
			}
		}

		return false;
	}


    /**
     * Translates a model concept with all relevant
     * data to an editor form.
     * @return array that can be used directly with to populate an Editor_Forms_Concept
     */

	public function toForm()
	{
		$apiClient = new Editor_Models_ApiClient();

		$languages = $this->getConceptLanguages();

		$languageData = array();
		foreach ($languages as $languageCode) {
			$languageData[strtoupper($languageCode)] = array(strtoupper($languageCode) => $languageCode);
		}

		if (isset($this['inScheme']) && !empty($this['inScheme'])) {
			$schemeTabs = $apiClient->getConceptSchemeMap(array('dcterms_title' => 0), 'uuid', $this['inScheme']);
			array_walk($schemeTabs, function ($v, $k) use (&$schemeTabs) {
				$schemeTabs[$k] = array($k => $v);
			});
		} else {
			$schemeTabs = array();
		}


		$conceptData = array(
				'inScheme' => $schemeTabs,
				'conceptSchemesId' => $apiClient->getConceptSchemeMap('uri', 'uuid'),
				'conceptLanguages' => $languageData,
				'toBeChecked' => isset($this['toBeChecked']) ? $this['toBeChecked'] : false,
				'uri' => $this['uri'],
				'uuid' => $this['uuid'],
				'notation' => $this->getMlField('notation'),
				'conceptSchemeSelect' => $apiClient->getConceptSchemeMap('uuid', array('dcterms_title' => 0)));

		if (isset($this['status'])) {
			$conceptData['status'] = $this['status'];
		}

		$languageData = array();
		foreach ($languages as $languageCode) {
			$languageData = array_merge_recursive(
					$languageData,
					$this->_getParsedMlProperties('LexicalLabels', $languageCode),
					$this->_getParsedMlProperties('DocumentationProperties', $languageCode));
		}

		$conceptData = array_merge($languageData, $conceptData);
		$topConcept = array(); // use the same loop for both form elements.

		if (is_array($this['inScheme'])) {
			foreach ($this['inScheme'] as $scheme) {
				if (isset($conceptData['conceptSchemesId'][$scheme])) {
					$topConcept[$conceptData['conceptSchemesId'][$scheme]] = $this->isTopConceptOf($scheme);
				}

				foreach (self::$classes['SemanticRelations'] as $relation) {
					if (!isset($conceptData[$relation]) || !is_array($conceptData[$relation]))
						$conceptData[$relation] = array();
					if (isset($conceptData['conceptSchemesId'][$scheme])) {
						$relationData = $this->_getRelationToForm($relation, $conceptData['conceptSchemesId'][$scheme], $scheme);
						if (!empty($relationData)) {
							$conceptData[$relation][] = $relationData;
						}
					}
				}
			}
			$conceptData['topConceptOf'] = $topConcept;
		}

		foreach (self::$classes['MappingProperties'] as $relation) {
			if (!isset($conceptData[$relation]) || !is_array($conceptData[$relation])) {
				$conceptData[$relation] = array();
			}

			$relationData = $this->_getRelationToForm($relation, null, null);
			if (!empty($relationData)) {
				$conceptData[$relation][] = $relationData;
			}
		}

		return $conceptData;
	}

	/**
	 * Parses all the form data and
	 * loads it into the model.
	 * @param array $formData
	 * @return $sextraData
	 */
	public function transformFormData(array &$formData) {

		$formMapping = $this->_getFormMapping();

		// Remove notation from language fields. For editor it is not translatable.
		$formMapping['languageFields'] = array_diff($formMapping['languageFields'], array('notation'));

		$extraData = array();
		$apiClient = new Editor_Models_ApiClient();
		$schemeMap = $apiClient->getConceptSchemeMap('uuid', 'uri');

		foreach ($formData as $key => $value) {
			if (in_array($key, $formMapping['languageFields'])) {
				foreach ($formData[$key] as $languageCode => $values) {
					$values = array_filter($values);
					if (!empty($languageCode) && ! empty($values)) {
						$formData[$key.'@'.$languageCode] = $values;
					}
				}
				unset($formData[$key]);
			} else if (in_array($key, $formMapping['uuid2uri'])){
				if (is_array($formData[$key]) && array_filter($formData[$key])) {
					foreach ($formData[$key] as $position => $value) {
						if (isset($schemeMap[$value])) {
							$formData[$key][$position] = $schemeMap[$value];
						} else {
							unset($formData[$key][$position]);
						}
					}
				} else {
					unset($formData[$key]);
				}
			} else if (in_array($key, $formMapping['resourceFields'])) {
				if (!is_array($formData[$key]) || !array_filter($formData[$key])) {
					unset($formData[$key]);
				} else {
					$formData[$key] = array_filter(array_unique($formData[$key]));
					foreach ($formData[$key] as $index => $value) {
						$formData[$key][$index] = $this->_getUriFromUuid($value);
 					}
				}
			} else if (in_array($key, $formMapping['helperFields'])) {
				unset($formData[$key]);
			}
		}

		foreach ($formData as $key => $value) {
			if (in_array($key, $formMapping['extraFields'])){
				$extraData[$key] = $formData[$key];
				unset($formData[$key]);
			}
		}

		return $extraData;
	}

	/**
	 * Get all concepts wich are:
	 * 1. Narrower relations.
	 * 2. Narrower mathces and top concepts in any scheme.
	 *
	 * @param bool $sortByPrevLabel optional, Default: true
	 * @return array Array of Editor_Models_Concepts
	 */
	public function getNarrowers($sortByPrevLabel = true)
	{
		$narrowerRelations = $this->getRelationsByField('narrower', null, array($this, 'getAllRelations'));

		$narrowMatches = $this->getRelationsByField('narrowMatch', null, array($this, 'getAllMappings'));
		foreach ($narrowMatches as $key => $narrowMatch) {
			if ( ! isset($narrowMatch['topConceptOf']) || empty($narrowMatch['topConceptOf'])) {
				unset($narrowMatches[$key]);
			}
		}

		$narrowers = array_merge($narrowerRelations, $narrowMatches);

		if ($sortByPrevLabel) {
			usort($narrowers, array('Api_Models_Concept', 'compareByPreviewLabel'));
		}

		// Copy them to Editor_Models_Concept objects.
		foreach ($narrowers as $key => $concept) {
			$narrowers[$key] = new Editor_Models_Concept($concept);
		}

		return $narrowers;
	}

	/**
	 * Tries to perform real update over the concept without loosing any old data and properly chaning the update data.
	 *
	 * @param array $updateData Leave empty array if no normal data is updated.
	 * @param array $updateExtraData Leave empty array if no extra data is updated.
	 * @param bool $commit, optional, Default: true
	 * @param bool $ignoreValidation, optional, Default: false If set to true the validation on save will not be performed.
	 * @return bool True if the save is successfull. False otherwise. You can see errors by calling getErrors();
	 */
	public function update($updateData, $updateExtraData, $commit, $ignoreValidation = false)
	{
		$data = $this->getData();
		$extraData = $this->getCurrentRequiredData();

		// Fix for preventing multiplying of the notation.
		unset($extraData['notation']);

		//!TODO The fallowing should be added to required data or all the process of editing concept should be refactored so that old data is not lost.
		// Data which will be lost on update if not remembered...
		if (isset($data['toBeChecked'])) {
			$extraData['toBeChecked'] = $data['toBeChecked'];
		}
		if (isset($data['created_by'])) {
			$extraData['created_by'] = $data['created_by'];
		}
		if (isset($data['created_timestamp'])) {
			$extraData['created_timestamp'] = $data['created_timestamp'];
		}
		if (isset($data['modified_by'])) {
			$extraData['modified_by'] = $data['modified_by'];
		}
		if (isset($data['modified_timestamp'])) {
			$extraData['modified_timestamp'] = $data['modified_timestamp'];
		}
		if (isset($data['approved_by'])) {
			$extraData['approved_by'] = $data['approved_by'];
		}
		if (isset($data['approved_timestamp'])) {
			$extraData['approved_timestamp'] = $data['approved_timestamp'];
		}
		if (isset($data['deleted_by'])) {
			$extraData['deleted_by'] = $data['deleted_by'];
		}
		if (isset($data['deleted_timestamp'])) {
			$extraData['deleted_timestamp'] = $data['deleted_timestamp'];
		}
		if (isset($data['status'])) {
			$extraData['status'] = $data['status'];
		}

		$data = array_merge($data, $updateData);
		$extraData = array_merge($extraData, $updateExtraData);

		if (isset($extraData['status'])) {
			if ($extraData['status'] !== 'approved') {
				$data['approved_by'] = '';
				$data['approved_timestamp'] = '';
				$extraData['approved_by'] = '';
				$extraData['approved_timestamp'] = '';
			}

			if ($extraData['status'] !== 'expired') {
				$data['deleted_by'] = '';
				$data['deleted_timestamp'] = '';
				$extraData['deleted_by'] = '';
				$extraData['deleted_timestamp'] = '';
			}
		}

		// The actual update...
		$this->setConceptData($data, $extraData);
		return $this->save($extraData, $commit, $ignoreValidation);
	}

	/**
	 * Gets all relations and mapping properties - both external and internal.
	 * @see Api_Models_Concept::getAllRelations()
	 */
	public function getAllRelationsAndMappings()
	{
		$allSemanticRelations = $this->getRelationsArray(Api_Models_Concept::$classes['SemanticRelations'], null, array($this, 'getAllRelations'));
		$allMappingRelations = $this->getRelationsArray(Api_Models_Concept::$classes['MappingProperties'], null, array($this, 'getAllMappings'));;
		return array_merge($allSemanticRelations, $allMappingRelations);
	}

	/**
	 * Wether the concept has any relations to other concepts or not.
	 * Relations from other concepts to this concept are also counted.
	 *
	 * @return bool
	 */
	public function hasAnyRelations()
	{
		$hasAnyRelation = false;

		$allRealations = $this->getAllRelationsAndMappings();
		foreach ($allRealations as $relations) {
			$hasAnyRelation |= ( ! empty($relations));
		}

		return (bool)$hasAnyRelation;
	}

	/**
	 * This function works with uri/uuid as parameter.
	 * @param string $uuid
	 */
	protected function _getUriFromUuid ($uuid)
	{
		if (null === $this->model) {
			$this->model = Api_Models_Concepts::factory();
		}

		$concept = $this->model->getConcept($uuid);
		if (null !== $concept) {
			return $concept['uri'];
		}
		return '';
	}

	/**
	 * Get a relation description in a format that allows us to send it directly to the form.
	 * @param string $relation
	 * @param string $schemeUuid
	 * @param string $schemeUri
	 */
	protected function _getRelationToForm($relation, $schemeUuid, $schemeUri)
	{
		$apiClient = new Editor_Models_ApiClient();

		$relationData = array('uuid' => $schemeUuid, 'concepts' => array());
		if (in_array($relation, self::$classes['SemanticRelations'])) {
			$callback =  array($this, 'getAllRelations');
		} else {
			$callback = array($this, 'getAllMappings');
		}

		$currentLanguage = Zend_Registry::get('Zend_Locale')->getLanguage();

		$concepts = $this->getRelationsByField($relation, $schemeUri,  $callback, true);
		if (empty($concepts)) {
			return array();
		}

        $schemesData = $apiClient->getConceptSchemes();

		foreach ($concepts as $concept) {
			$previewLabel = $concept->getMlField('prefLabel', $currentLanguage);
			$isInternal = $this->isInternalRelation($concept['uri'], $relation);
			if (!$isInternal) {
				$previewLabel .= '*';
			}

            $shemes = array();
            foreach ($schemesData as $schemeData) {
                if (in_array($schemeData['uri'], $concept['inScheme'])) {
                    $shemes[$schemeData['uri']] = $schemeData;
                }
            }

			$relationData['concepts'][] = array(
					'concept' => array(
							'uuid' => $concept['uuid'],
							'uri' => $concept['uri'],
							'previewLabel' =>  $previewLabel,
							'remove' =>  $isInternal),
					'schemes' => $shemes);
		}
		return $relationData;
	}

	/**
	 * Form content to model fields mapper.
	 */
	protected function _getFormMapping()
	{
		$mapping = $this->getRdfMapping();
		$mapping['helperFields'] = Editor_Forms_Concept::getHelperFields();
		$mapping['extraFields'] = array(
				'uuid',
				'status',
				'toBeChecked',
				'uri'
		);
		$mapping['uuid2uri'] = array(
				'inScheme',
				'topConceptOf'
		);
		return $mapping;
	}

	/**
	 * Gets all ML data in a specific language and formats it in a way that could be easily used by the concept form.
	 * @param string $class
	 * @param string $languageCode
	 * @return array
	 */

	protected function _getParsedMlProperties($class, $languageCode)
	{
		$data = $this->getMlProperties($class, $languageCode);
		//form fix.
		if ($class == 'LexicalLabels') {
			foreach (self::$classes[$class] as $labelField) {
				$labelInLanguage = $labelField.'@'.$languageCode;
				if (!isset($data[$labelInLanguage])) {
					$data[$labelInLanguage] = array("");
				}
			}
		}

		$parsedData = array();
		array_walk($data, function ($v, $k) use (&$parsedData) {
			$k = explode('@', $k);
			$key = array_shift($k);
			$languageCode = array_pop($k);
			if (!isset($parsedData[$key]) || !is_array($parsedData[$key]))
				$parsedData[$key] = array();
			$parsedData[$key][] = array('languageCode' => $languageCode, 'value' => $v);
		});

		return $parsedData;
	}
}
