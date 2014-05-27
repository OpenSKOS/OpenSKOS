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

class Editor_Models_Export
{	
	/**
	 * Constant for the maximum number of concepts available for export.
	 * 
	 * @var int
	 */
	const MAX_RECORDS_FOR_INSTANT_EXPORT = 1000;
	
	/**
	 * Holds the number of concepts that can be exported at a time.
	 * 
	 * @var int
	 */
	const CONCEPTS_EXPORT_STEP = 1000;
	
	/**
	 * Hours before export gets old and should be removed.
	 *
	 * @var int The lifetime in hours
	 */
	const EXPORT_FILE_LIFETIME = 48; //In hours
	
	/**
	 * Holds the format types.
	 * 
	 * @var string
	 */
	const EXPORT_FORMAT_XML = 'xml';
	const EXPORT_FORMAT_CSV = 'csv';
	const EXPORT_FORMAT_RTF = 'rtf';
	
	/**
	 * Holds all the settings used for export.
	 * 
	 * @var array
	 */
	public $_settings = array();
	
	/**
	 * Gets supported formats.
	 * 
	 * @return array
	 */
	public static function getExportFormats() 
	{
		$result = array();
		$result[self::EXPORT_FORMAT_XML] = 'Xml';
		$result[self::EXPORT_FORMAT_CSV] = 'Csv';
		$result[self::EXPORT_FORMAT_RTF] = 'Rtf';
		
		return $result;
	}
	
	/**
	 * Gets an array of concept fields that can be exported.
	 *
	 * @return array
	 */
	public static function getExportableConceptFields()
	{
		$result = array();
		$result[] = 'uuid';
		$result[] = 'uri';
	
		foreach (Api_Models_Concept::$classes as $fieldsInClass) {
			$result = array_merge($result, $fieldsInClass);
		}
		
		// The field conceptScheme is not usable in the editor.
		unset($result[array_search('conceptScheme', $result)]);
		
		return $result;
	}
	
	/**
	 * Sets setting.
	 * 
	 * @param string $setting
	 * @param mixed $value
	 * @return Editor_Models_Export
	 */
	public function set($setting, $value) 
	{
		$this->_settings[$setting] = $value;
		return $this;
	}
	
	/**
	 * Sets all settings.
	 * 
	 * @param array $settings
	 * @return Editor_Models_Export
	 */
	public function setSettings($settings)
	{
		$this->_settings = $settings;
		return $this;
	}
	
	/**
	 * Get a setting. Throws error if not found.
	 * 
	 * @param string $setting
	 * @return mixed
	 * @throws Zend_Exception
	 */
	public function get($setting)
	{
		if ($this->has($setting)) {
			return $this->_settings[$setting];
		} else {
			throw new Zend_Exception('Setting "' . $setting . '" in Editor_Models_Export must be specified.');
		}
	}
	
	/**
	 * Is a setting specified.
	 *
	 * @param string $setting
	 * @return bool
	 */
	public function has($setting)
	{
		return isset($this->_settings[$setting]);
	}
	
	/**
	 * Get all settings.
	 * 
	 * @return array
	 */
	public function getSettings()
	{
		return $this->_settings;
	}
	
	/**
	 * Gets the file details for export depending on the export format.
	 *
	 * @return array
	 */
	public function getExportFileDetails()
	{
		switch ($this->get('format')) {
			case self::EXPORT_FORMAT_XML: {
				return array('fileName' => $this->get('outputFileName') . '.xml', 'mimeType' => 'text/xml');
			}; break;
			case self::EXPORT_FORMAT_CSV: {
				return array('fileName' => $this->get('outputFileName') . '.csv', 'mimeType' => 'text/csv');
			}; break;
			case self::EXPORT_FORMAT_RTF: {
				return array('fileName' => $this->get('outputFileName') . '.rtf', 'mimeType' => 'application/rtf');
			}; break;
		}
	}
	
	/**
	 * Determines if the export will take a lot of time.
	 * This happens in the fallowing cases.
	 * 1. The number of concepts is higher than the MAX_RECORDS_FOR_INSTANT_EXPORT constant.
	 *
	 * @return bool
	 */
	public function isTimeConsumingExport()
	{
		// Export is slow if export format is rtf
		/*
		if ($this->get('format') == self::EXPORT_FORMAT_RTF) {
			return true;
		}
		*/
		
		// Export is slow if depth is more than 1
		if ($this->get('maxDepth') > 1) {
			return true;
		}
	
		// Export is slow if export type is search and the search results are more than MAX_RECORDS_FOR_INSTANT_EXPORT
		if ($this->get('type') == 'search') {
			$searchOptions = $this->get('searchOptions');
			$searchOptions['start'] = 0;
			$searchOptions['rows'] = 0;
				
			$searchResult = $this->_getApiClientInstance()->searchConcepts($searchOptions);
				
			return $searchResult['numFound'] > Editor_Models_Export::MAX_RECORDS_FOR_INSTANT_EXPORT;
		}
	
		return false;
	}
	
	/**
	 * Exports to string with the specified settings.
	 * 
	 * @return string
	 */
	public function exportToString()
	{
		$streamHandle = fopen('php://memory', 'rw');
		
		$this->exportToStream($streamHandle);
		
		rewind($streamHandle);
		$result = stream_get_contents($streamHandle);
		fclose($streamHandle);
		
		return $result;
	}
	
	/**
	 * Exports to file with the specified settings.
	 * 
	 * @param array $editorOptions, optional
	 * @return string The relative (up to main export dir) path to the exported file
	 */
	public function exportToFile()
	{
		$mainDirPath = $this->getExportFilesDirPath();
		
		if ( ! is_dir($mainDirPath)) {
			throw new Zend_Exception('Directory "' . $mainDirPath . '" must exist and must have read and write rights for the export to work.');
		}
		
		// Clean old exports.
		$this->_cleanUpOldExports($mainDirPath);
		
		// Creates the new export directory
		$exportDirName = uniqid();
		$dirPath = rtrim($mainDirPath, '/') . '/' . $exportDirName;
		mkdir($dirPath);
		
		$fileDetails = $this->getExportFileDetails();		
		$filePath = $dirPath . '/' . $fileDetails['fileName'];
		
		$streamHandle = fopen($filePath, 'w');
	
		$this->exportToStream($streamHandle);
	
		fclose($streamHandle);
		
		return $exportDirName . '/' . $fileDetails['fileName'];
	}
	
	/**
	 * Creates an openskos background job to export with the given settings to a file.
	 * 
	 * @return int The job id
	 */
	public function exportWithBackgroundJob()
	{
		$user = OpenSKOS_Db_Table_Users::requireById($this->get('userId'));
		
		$tenant = OpenSKOS_Db_Table_Tenants::fromCode($user->tenant);
		
		$tenantCollections = $tenant->findDependentRowset('OpenSKOS_Db_Table_Collections');
		if ( ! $tenantCollections->count()) {
			throw new Zend_Exception('Current tenant does not have any collections. At least one is required.', 404);
		}
		
		// We use the first collection of the tenant for the export job, 
		// because the collection is important for the jobs, but the export is not related to any specific collection. 
		$firstTenantCollection = $tenantCollections->current();
		
		$model = new OpenSKOS_Db_Table_Jobs();
		$job = $model->fetchNew()->setFromArray(array(
				'collection' => $firstTenantCollection->id,
				'user' => $user->id,
				'task' => OpenSKOS_Db_Table_Row_Job::JOB_TASK_EXPORT,
				'parameters' => serialize($this->getSettings()),
				'created' => new Zend_Db_Expr('NOW()')
		))->save();
		
		return $job;
	}
	
	/**
	 * Exports to the specified stream.
	 * 
	 * @param long $streamHandle
	 */
	public function exportToStream($streamHandle)
	{
		switch ($this->get('format')) {
			case self::EXPORT_FORMAT_CSV: {
				$this->_exportCsv($streamHandle);
			}; break;
			case self::EXPORT_FORMAT_RTF: {
				$this->_exportRtf($streamHandle);
			}; break;
			case self::EXPORT_FORMAT_XML:
			default: {
				$this->_exportXml($streamHandle);
			}; break;
		}
	}
	
	/**
	 * Gets the path to the dir where the export files should be placed.
	 *
	 * @return string
	 */
	public function getExportFilesDirPath()
	{
		$editorOptions = OpenSKOS_Application_BootstrapAccess::getOption('editor');
	
		if (isset($editorOptions['export']['filesPath'])) {
			$mainDirPath = $editorOptions['export']['filesPath'];
		} else {
			$mainDirPath = APPLICATION_PATH . '/../public/data/export';
		}
	
		$mainDirPath = rtrim($mainDirPath, '/') . '/';
		
		return $mainDirPath;
	}
	
	/**
	 * Exports in xml format. Writes the result to the stream.
	 * 
	 * @param long $streamHandle
	 */
	protected function _exportXml($streamHandle)
	{
		$view = new Zend_View();
		$view->setBasePath(APPLICATION_PATH . '/editor/views/');
		$template = 'export/xml.phtml';
		
		$step = self::CONCEPTS_EXPORT_STEP;
		
		// Collect namespaces.
		$namespaces = array();
		$start = 0;
		$hasMore = false;
		do {
			$concepts = $this->_getConcepts($start, $step, $hasMore);
			$namespaces = array_merge($namespaces, $this->_getConceptsNamespaces($concepts));
			$start += $step;
		} while ($hasMore);
		
		// Writes the header of the xml.
		$view->namespaces = $namespaces;
		$view->assign('renderHeader', true)->assign('renderBody', false)->assign('renderFooter', false);
		fwrite($streamHandle, $view->render($template));
		
		// Writes the concepts data. The body of the xml.
		$start = 0;		
		$hasMore = false;
		do {
			$concepts = $this->_getConcepts($start, $step, $hasMore);
			
			$view->data = $concepts;
			$view->assign('renderHeader', false)->assign('renderBody', true)->assign('renderFooter', false);
			fwrite($streamHandle, $view->render($template));
			
			$start += $step;
		} while ($hasMore);
		
		// Writes the footer of the xml.
		$view->assign('renderHeader', false)->assign('renderBody', false)->assign('renderFooter', true);
		fwrite($streamHandle, $view->render($template));
	}
	
	/**
	 * Exports in csv format. Writes the result to the stream.
	 *
	 * @param long $streamHandle
	 */
	protected function _exportCsv($streamHandle)
	{
		$fieldsToExport = $this->get('fieldsToExport');
		if (empty($fieldsToExport)) {
			$fieldsToExport = self::getExportableConceptFields();
		}		
		fputcsv($streamHandle, $fieldsToExport);
		
		// Writes the concepts data		
		$step = self::CONCEPTS_EXPORT_STEP;
		$start = 0;
		$hasMore = false;
		do {
			$concepts = $this->_getConcepts($start, $step, $hasMore);
			
			if ( ! empty($concepts)) {
				foreach ($concepts as $concept) {
					fputcsv($streamHandle, $this->_prepareConceptDataForCsv($concept, $fieldsToExport));
				}
			}
			
			$start += $step;				
		} while ($hasMore);
	}
	
	/**
	 * Exports in rtf format. Wrtites the result to the stream.
	 *
	 * @param long $streamHandle
	 */
	protected function _exportRtf($streamHandle)
	{
		$view = new Zend_View();
		$view->setBasePath(APPLICATION_PATH . '/editor/views/');
		$template = 'export/rtf.phtml';
		
		// Writes the header of the rtf.
		$view->assign('renderHeader', true)->assign('renderBody', false)->assign('renderFooter', false);
		fwrite($streamHandle, $view->render($template));
		
		// Writes the concepts data. The body of the rtf.
		
		// Gets fields which will be exported.
		$fieldsToExport = $this->get('fieldsToExport');
		if (empty($fieldsToExport)) {
			$fieldsToExport = self::getExportableConceptFields();
		}
				
		$step = self::CONCEPTS_EXPORT_STEP;
		$start = 0;
		$hasMore = false;
		do {
			$concepts = $this->_getConcepts($start, $step, $hasMore);
			
			if ( ! empty($concepts)) {				
				$conceptsData = array();
				foreach ($concepts as $concept) {					
					$conceptsData[] = $this->_prepareConceptDataForRtf($concept, $fieldsToExport);
				}
				
				$view->data = $conceptsData;
				$view->assign('renderHeader', false)->assign('renderBody', true)->assign('renderFooter', false);
				fwrite($streamHandle, $view->render($template));
			}
			
			$start += $step;
		} while ($hasMore);
		
		// Writes the footer of the rtf.
		$view->assign('renderHeader', false)->assign('renderBody', false)->assign('renderFooter', true);
		fwrite($streamHandle, $view->render($template));
	}
		
	/**
	 * Gets the concepts for the export depending on the type of the export and the export settings.
	 *
	 * @param int $start From which record number to select the concepts.
	 * @param int $rows How many concepts to select.
	 * @param bool $hasMore output
	 * @return array An array of Api_Models_Concept
	 */
	protected function _getConcepts($start = 0, $rows = self::MAX_RECORDS_FOR_INSTANT_EXPORT, &$hasMore)
	{
		$concepts = array();
		switch ($this->get('type')) {
			case 'concept' : {
				$concepts[] = Api_Models_Concepts::factory()->getConcept($this->get('conceptUuid'));
				$hasMore = false;
			} break;
			case 'history' : {
				$user = OpenSKOS_Db_Table_Users::requireById($this->get('userId'));
				$concepts = $user->getUserHistory();
				$hasMore = false;
			} break;
			case 'selection' : {
				$user = OpenSKOS_Db_Table_Users::requireById($this->get('userId'));
				$concepts = $user->getConceptsSelection();
				$hasMore = false;
			} break;
			case 'search' : {
				$searchOptions = $this->get('searchOptions');
				$searchOptions['start'] = $start;
				$searchOptions['rows'] = $rows;
				
				$searchResult = $this->_getApiClientInstance()->searchConcepts($searchOptions);
				
				$concepts = $searchResult['data'];
				$hasMore = $searchResult['numFound'] > $start + $rows;
			} break;
		}
		return $concepts;
	}
	
	/**
	 * Gets all namespaces that are used inside the concepts.
	 *
	 * @param array $concepts An array of Api_Models_Concept
	 * @return array An array of type $prefix => $uri
	 */
	protected function _getConceptsNamespaces($concepts)
	{
		$namespacesPrefixes = array();
		foreach ($concepts as $concept) {
			$namespacesPrefixes = array_unique(array_merge($namespacesPrefixes, $concept['xmlns']));
		}
		$model = new OpenSKOS_Db_Table_Namespaces();
		return $model->fetchPairs($model->select()->where('prefix IN ("' . implode('","', $namespacesPrefixes) . '")'));
	}
	
	/**
	 * Remove any old export files.
	 * 
	 * @param string $mainDir
	 */
	protected function _cleanUpOldExports($mainDir)
	{
		$mainDir = rtrim($mainDir, '/') . '/'; // Ensure that the dir path ends with /
		
		$exportDirectories = scandir($mainDir);
		
		foreach ($exportDirectories as $currentDir) {						
			if ($currentDir != '.' && $currentDir != '..') {
				$exportFiles = scandir($mainDir . $currentDir);
				
				foreach ($exportFiles as $currentFile) {
					if ($currentFile != '.' && $currentFile != '..') {
						if (filemtime($mainDir . $currentDir . '/' . $currentFile) < strtotime('- ' . self::EXPORT_FILE_LIFETIME . ' hours')) { 
							unlink($mainDir . $currentDir . '/' . $currentFile);
						}
					}
				}
				
				// If the directory remains empty - remove it
				$exportFiles = scandir($mainDir . $currentDir);
				if (count($exportFiles) <= 2) { // Ignore "." and ".."
					rmdir($mainDir . $currentDir);
				}
			}
		}
	}
	
	/**
	 * Prepare concept data for exporting in csv format.
	 * 
	 * @param Api_Models_Concept $concept
	 * @param array $fieldsToExport
	 * @return array The result concept data
	 */
	protected function _prepareConceptDataForCsv(Api_Models_Concept $concept, $fieldsToExport)
	{
		$conceptData = array();
			
		foreach ($fieldsToExport as $field) {
			if (isset($concept[$field])) {
				if (is_array($concept[$field])) {
					$conceptData[$field] = implode(';', $concept[$field]);
				} else {
					$conceptData[$field] = $concept[$field];
				}
			} else {
				$conceptData[$field] = '';
			}
		}
		
		return $conceptData;
	}
	
	/**
	 * Holds an array of field - title map for the fields when used in rtf export.
	 * @var array
	 */
	protected $_rtfFieldsTitlesMap = array(
			'uuid' => 'UUID',
			'uri' => 'URI',
			'broader' => 'BT',
			'narrower' => 'NT',
			'related' => 'RT',
			'exampleNote' => 'Voorbeeld:',
			'dcterms_dateSubmited' => 'DS',
			'dcterms_dateAccepted' => 'DA',
			'dcterms_modified' => 'DM',
			'dcterms_creator' => 'C',
	);
	
	/**
	 * Prepares concept data for exporting in rtf format.
	 * 
	 * @param Api_Models_Concept $concept
	 * @param array $fieldsToExport
	 * @return array The result concept data
	 */
	protected function _prepareConceptDataForRtf(Api_Models_Concept $concept, $fieldsToExport) 
	{
		$conceptData = array();
		$conceptData['previewLabel'] = $this->_constructRtfFieldData('previewLabel', $concept->getPreviewLabel());
		$conceptData['fields'] = array();
		
		// Prepares concept schemes titles map
		$schemesUris = array();
		$schemesFields = Api_Models_Concept::$classes['ConceptSchemes']; 
		foreach ($schemesFields as $schemeField) {
			if (in_array($schemeField, $fieldsToExport) && ! empty($concept[$schemeField])) {
				$schemesUris = array_merge($schemesUris, $concept[$schemeField]);
			}
		}
		$schemesUris = array_unique($schemesUris);
		$schemesTitleMap = $this->_getApiClientInstance()->getConceptSchemeMap('uri', array('dcterms_title' => 0), $schemesUris);
		
		// Prepares related concepts map
		$relatedConceptsUris = array();
		$relationFields = array_merge(Api_Models_Concept::$classes['SemanticRelations'], Api_Models_Concept::$classes['MappingProperties']);
		foreach ($relationFields as $relationField) {
			if (in_array($relationField, $fieldsToExport) && ! empty($concept[$relationField])) {
				$relatedConceptsUris = array_merge($relatedConceptsUris, $concept[$relationField]);
			}
		}
		$relatedConceptsUris = array_unique($relatedConceptsUris);
		$relatedConceptsMap = Api_Models_Concepts::factory()->getEnumeratedConceptsMapByUris($relatedConceptsUris);
		
		// Prepare language dependant fields. Remove the orginal field from fields to export and adds each per language field (field@en for example).
		$allConceptFields = $concept->getFields();
		$fieldsToExportInLanguages = array();
		foreach ($allConceptFields as $currentConceptField) {
			if (preg_match('/^([^@]+)@([^@]+)$/i', $currentConceptField, $matches) && in_array($matches[1], $fieldsToExport)) {
				if ( ! isset($fieldsToExportInLanguages[$matches[1]])) {
					$fieldsToExportInLanguages[$matches[1]] = array();
				}
				$fieldsToExportInLanguages[$matches[1]][] = $matches[2];
			}
		}
		
		// Goes trought each export field
		foreach ($fieldsToExport as $field) {
		
			if (isset($concept[$field])) {
				
				if (isset($fieldsToExportInLanguages[$field])) {
					
					foreach ($fieldsToExportInLanguages[$field] as $language) {
						foreach ($concept[$field . '@' . $language] as $value) {
							$conceptData['fields'][] = $this->_constructRtfFieldData($field, $value, $language);
						}
					}
					
				} else if (is_array($concept[$field])) {
					
					foreach ($concept[$field] as $value) {
						
						if (in_array($field, $schemesFields) && isset($schemesTitleMap[$value])) {
							$value = $schemesTitleMap[$value];
						} else if (in_array($field, $relationFields) && isset($relatedConceptsMap[$value])) {
							$value = $relatedConceptsMap[$value]->getPreviewLabel();
						}
						
						$conceptData['fields'][] = $this->_constructRtfFieldData($field, $value);
					}
					
				} else {
					$conceptData['fields'][] = $this->_constructRtfFieldData($field, $concept[$field]);
				}
			}		
		}
		
		// Get concept children (narrowers)
		if ($this->get('maxDepth') > 1) {
			 $narrowers = $this->_getRtfNarrowers(new Editor_Models_Concept($concept), 1);
			 if ( ! empty($narrowers)) {
			 	$conceptData['narrowers'] = $narrowers;
			 }
		}
		
		return $conceptData;
	}
	
	/**
	 * Get the narrowers of the concept prepared for rtf.
	 * 
	 * @param Editor_Models_Concept $concept
	 */
	protected function _getRtfNarrowers($concept, $depthLevel) 
	{
		$result = array();
		$narrowers = $concept->getNarrowers();
		foreach ($narrowers as $key => $narrowerConcept) {
			$narrowerConceptData = array();
			$narrowerConceptData['previewLabel'] = $this->_constructRtfFieldData('previewLabel', $narrowerConcept->getPreviewLabel());
			
			if ($depthLevel < ($this->get('maxDepth') - 1)) {
				$narrowerConceptNarrowers = $this->_getRtfNarrowers($narrowerConcept, $depthLevel + 1);
				if ( ! empty($narrowerConceptNarrowers)) {
					$narrowerConceptData['narrowers'] = $narrowerConceptNarrowers;
				}
			} 
			$result[] = $narrowerConceptData;
		}
		return $result;
	}
	
	/**
	 * Returns an array of the data needed to create field in the rtf export.
	 *
	 * @param string $field
	 * @param string $value
	 * @param string $language, optional
	 * @param array $children, optional
	 */
	protected function _constructRtfFieldData($field, $value, $language = '', $children = array())
	{
		// Field title
		$result = array();
		if (isset($this->_rtfFieldsTitlesMap[$field])) {
			$result['fieldTitle'] = $this->_rtfFieldsTitlesMap[$field];
		} else {
			$fieldTitleParts = preg_split('/(?=[A-Z])/', $field);
			$result['fieldTitle'] = '';
			foreach ($fieldTitleParts as $part) {
				$result['fieldTitle'] .= strtoupper(substr($part, 0, 1));
			}
		}
	
		// Field value (escape)
		$result['value'] = str_replace('\\', '\\\\', $value);
		$result['value'] = str_replace('{', '\\{', $result['value']);
		$result['value'] = str_replace('}', '\\}', $result['value']);
		$result['value'] = $this->_utf82rtf($result['value']);
		
		// Language
		$result['language'] = $language;
		
		// Children
		if ( ! empty($children)) {
			$result['children'] = $children;
		}
		
		return $result;
	}
	
	/**
	 * Converts the unicode chars inside the given utf8 text to rtf replacements.
	 * 
	 * @param string $utf8_text
	 * @return string
	 */
	protected function _utf82rtf($utf8_text)
	{
		$utf8_text = str_replace("\n", "\\par\n", str_replace("\r", "\n", str_replace("\r\n", "\n", $utf8_text)));
		return preg_replace_callback("/([\\xC2-\\xF4][\\x80-\\xBF]+)/", array($this, '_fixUnicodeForRtf'), $utf8_text);
	}
	
	/**
	 * Use for callback in _utf82rtf's preg_replace_callback.
	 * 
	 * @param array $matches
	 * @return string
	 */
	protected function _fixUnicodeForRtf($matches) 
	{
		return '\u'.hexdec(bin2hex(iconv('UTF-8', 'UTF-16BE', $matches[1]))).'?';
	}
	
	/**
	 * Holds an instance of the api client. Frequently used inside the export class.
	 *  
	 * @var Editor_Models_ApiClient
	 */
	protected $_apiClient;
	
	/**
	 * Gets an instance of the api client. Sets the api client tenant.
	 * 
	 * return Editor_Models_ApiClient
	 */
	protected function _getApiClientInstance()
	{
		if (null === $this->_apiClient) {
			$user = OpenSKOS_Db_Table_Users::requireById($this->get('userId'));
			$tenant = OpenSKOS_Db_Table_Tenants::fromCode($user->tenant);
			$this->_apiClient = Editor_Models_ApiClient::factory();
			$this->_apiClient->setTenant($tenant);
		}		
		
		return $this->_apiClient;
	}
}