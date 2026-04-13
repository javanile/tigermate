<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_Vtiger_GanttConfig_Model extends Settings_Vtiger_Module_Model {

	const TAB_INFO_TABLE_NAME = 'vtiger_tab_info';
	const MODULE_NAME = 'Project';
	const PREF_NAME = 'tigermate_gantt_settings';
	const DISPLAY_MODE_KEY = 'display_mode';
	const MILESTONE_PRIMARY_PROGRESS_FIELD_KEY = 'milestone_primary_progress_field';
	const MILESTONE_SECONDARY_PROGRESS_FIELD_KEY = 'milestone_secondary_progress_field';
	const TASK_PRIMARY_PROGRESS_FIELD_KEY = 'task_primary_progress_field';
	const TASK_SECONDARY_PROGRESS_FIELD_KEY = 'task_secondary_progress_field';
	const BAR_HEIGHT_KEY = 'bar_height';
	const DEFAULT_DISPLAY_MODE = 'all';
	const DEFAULT_BAR_HEIGHT = 'medium';
	const NONE_FIELD_VALUE = 'none';
	const STORAGE_DISPLAY_MODE_KEY = 'dm';
	const STORAGE_MILESTONE_PRIMARY_PROGRESS_FIELD_KEY = 'mp';
	const STORAGE_MILESTONE_SECONDARY_PROGRESS_FIELD_KEY = 'ms';
	const STORAGE_BAR_HEIGHT_KEY = 'bh';
	const STORAGE_TASK_PRIMARY_PROGRESS_FIELD_KEY = 'tp';
	const STORAGE_TASK_SECONDARY_PROGRESS_FIELD_KEY = 'ts';

	public function getMenuItem() {
		return Settings_Vtiger_MenuItem_Model::getInstance('LBL_GANTT_CONFIG');
	}

	public function getEditViewUrl() {
		$menuItem = $this->getMenuItem();
		$url = 'index.php?module=Vtiger&parent=Settings&view=GanttConfigEdit';
		if ($menuItem) {
			$url .= '&block='.$menuItem->get('blockid').'&fieldid='.$menuItem->get('fieldid');
		}
		return $url;
	}

	public function getDetailViewUrl() {
		$menuItem = $this->getMenuItem();
		$url = 'index.php?module=Vtiger&parent=Settings&view=GanttConfigDetail';
		if ($menuItem) {
			$url .= '&block='.$menuItem->get('blockid').'&fieldid='.$menuItem->get('fieldid');
		}
		return $url;
	}

	public function getViewableData() {
		return self::getStoredSettings();
	}

	public function getEditableFields() {
		return array(
			self::DISPLAY_MODE_KEY => array(
				'label' => 'LBL_GANTT_DISPLAY_MODE',
				'fieldType' => 'picklist',
			),
			self::MILESTONE_PRIMARY_PROGRESS_FIELD_KEY => array(
				'label' => 'LBL_GANTT_MILESTONE_PRIMARY_PROGRESS_FIELD',
				'fieldType' => 'picklist',
			),
			self::MILESTONE_SECONDARY_PROGRESS_FIELD_KEY => array(
				'label' => 'LBL_GANTT_MILESTONE_SECONDARY_PROGRESS_FIELD',
				'fieldType' => 'picklist',
			),
			self::BAR_HEIGHT_KEY => array(
				'label' => 'LBL_GANTT_BAR_HEIGHT',
				'fieldType' => 'picklist',
			),
			self::TASK_PRIMARY_PROGRESS_FIELD_KEY => array(
				'label' => 'LBL_GANTT_TASK_PRIMARY_PROGRESS_FIELD',
				'fieldType' => 'picklist',
			),
			self::TASK_SECONDARY_PROGRESS_FIELD_KEY => array(
				'label' => 'LBL_GANTT_TASK_SECONDARY_PROGRESS_FIELD',
				'fieldType' => 'picklist',
			),
		);
	}

	public function getPicklistValues($fieldName) {
		if ($fieldName === self::DISPLAY_MODE_KEY) {
			return array(
				'all' => 'LBL_GANTT_SHOW_TASKS_AND_MILESTONES',
				'tasks' => 'LBL_GANTT_SHOW_ONLY_TASKS',
				'milestones' => 'LBL_GANTT_SHOW_ONLY_MILESTONES',
			);
		}
		if ($fieldName === self::BAR_HEIGHT_KEY) {
			return array(
				'small' => 'LBL_GANTT_BAR_HEIGHT_SMALL',
				'medium' => 'LBL_GANTT_BAR_HEIGHT_MEDIUM',
				'large' => 'LBL_GANTT_BAR_HEIGHT_LARGE',
			);
		}
		if (in_array($fieldName, array(self::TASK_PRIMARY_PROGRESS_FIELD_KEY, self::TASK_SECONDARY_PROGRESS_FIELD_KEY), true)) {
			return self::getPercentageFieldOptions('ProjectTask');
		}
		if (in_array($fieldName, array(self::MILESTONE_PRIMARY_PROGRESS_FIELD_KEY, self::MILESTONE_SECONDARY_PROGRESS_FIELD_KEY), true)) {
			return self::getPercentageFieldOptions('ProjectMilestone');
		}

		return array();
	}

	public function save() {
		$updatedFields = $this->get('updatedFields');
		$editableFields = array_keys($this->getEditableFields());
		$updatedFields = array_intersect_key($updatedFields, array_flip($editableFields));
		$updatedFields = array_merge(self::getDefaultSettings(), $updatedFields);
		$validationInfo = $this->validateFieldValues($updatedFields);

		if ($validationInfo !== true) {
			return $validationInfo;
		}

		$db = PearDatabase::getInstance();
		$tabId = self::getModuleTabId();
		if (empty($tabId)) {
			return 'LBL_NO_GANTT_MODULE_FOUND';
		}
		$serializedSettings = self::encodeSettings($updatedFields);
		$result = $db->pquery(
			'SELECT 1 FROM '.self::TAB_INFO_TABLE_NAME.' WHERE tabid = ? AND prefname = ?',
			array($tabId, self::PREF_NAME)
		);
		if ($db->num_rows($result) > 0) {
			$db->pquery(
				'UPDATE '.self::TAB_INFO_TABLE_NAME.' SET prefvalue = ? WHERE tabid = ? AND prefname = ?',
				array($serializedSettings, $tabId, self::PREF_NAME)
			);
		} else {
			$db->pquery(
				'INSERT INTO '.self::TAB_INFO_TABLE_NAME.'(tabid, prefname, prefvalue) VALUES(?, ?, ?)',
				array($tabId, self::PREF_NAME, $serializedSettings)
			);
		}

		$verifyResult = $db->pquery(
			'SELECT prefvalue FROM '.self::TAB_INFO_TABLE_NAME.' WHERE tabid = ? AND prefname = ?',
			array($tabId, self::PREF_NAME)
		);
		$storedSerializedSettings = null;
		if ($db->num_rows($verifyResult) > 0) {
			$storedSerializedSettings = $db->query_result($verifyResult, 0, 'prefvalue');
		}
		$normalizedStoredSerializedSettings = self::normalizeSerializedSettings($storedSerializedSettings);
		$normalizedExpectedSerializedSettings = self::normalizeSerializedSettings($serializedSettings);
		if ($normalizedStoredSerializedSettings !== $normalizedExpectedSerializedSettings) {
			die('<pre>'.print_r(array(
				'debug' => 'gantt-config-save-verification-failed',
				'module' => self::MODULE_NAME,
				'tabid' => $tabId,
				'prefname' => self::PREF_NAME,
				'expected_serialized' => $serializedSettings,
				'stored_serialized' => $storedSerializedSettings,
				'normalized_expected_serialized' => $normalizedExpectedSerializedSettings,
				'normalized_stored_serialized' => $normalizedStoredSerializedSettings,
				'expected_decoded' => self::decodeSettings($serializedSettings),
				'stored_decoded' => self::decodeSettings($storedSerializedSettings),
				'updated_fields' => $updatedFields,
			), true).'</pre>');
		}

		return true;
	}

	public function validateFieldValues($updatedFields) {
		$displayMode = $updatedFields[self::DISPLAY_MODE_KEY];
		if (!in_array($displayMode, self::getSupportedDisplayModes(), true)) {
			return 'LBL_INVALID_GANTT_DISPLAY_MODE';
		}

		$fieldModuleMap = array(
			self::MILESTONE_PRIMARY_PROGRESS_FIELD_KEY => 'ProjectMilestone',
			self::MILESTONE_SECONDARY_PROGRESS_FIELD_KEY => 'ProjectMilestone',
			self::TASK_PRIMARY_PROGRESS_FIELD_KEY => 'ProjectTask',
			self::TASK_SECONDARY_PROGRESS_FIELD_KEY => 'ProjectTask',
		);
		foreach ($fieldModuleMap as $fieldKey => $moduleName) {
			$fieldName = isset($updatedFields[$fieldKey]) ? $updatedFields[$fieldKey] : self::NONE_FIELD_VALUE;
			$allowedOptions = array_keys(self::getPercentageFieldOptions($moduleName));
			if (!in_array($fieldName, $allowedOptions, true)) {
				return 'LBL_INVALID_GANTT_PROGRESS_FIELD';
			}
		}
		$barHeight = isset($updatedFields[self::BAR_HEIGHT_KEY]) ? $updatedFields[self::BAR_HEIGHT_KEY] : self::DEFAULT_BAR_HEIGHT;
		if (!in_array($barHeight, self::getSupportedBarHeights(), true)) {
			return 'LBL_INVALID_GANTT_BAR_HEIGHT';
		}

		return true;
	}

	public static function getSupportedDisplayModes() {
		return array('all', 'tasks', 'milestones');
	}

	public static function getDisplayMode() {
		$settings = self::getStoredSettings();
		$displayMode = $settings[self::DISPLAY_MODE_KEY];
		if (!in_array($displayMode, self::getSupportedDisplayModes(), true)) {
			return self::DEFAULT_DISPLAY_MODE;
		}

		return $displayMode;
	}

	public static function getSupportedBarHeights() {
		return array('small', 'medium', 'large');
	}

	public static function getBarHeight() {
		$settings = self::getStoredSettings();
		$barHeight = $settings[self::BAR_HEIGHT_KEY];
		if (!in_array($barHeight, self::getSupportedBarHeights(), true)) {
			return self::DEFAULT_BAR_HEIGHT;
		}

		return $barHeight;
	}

	public static function getStoredSettings() {
		$db = PearDatabase::getInstance();
		$defaultSettings = self::getDefaultSettings();
		if (!Vtiger_Utils::CheckTable(self::TAB_INFO_TABLE_NAME)) {
			return $defaultSettings;
		}

		$tabId = self::getModuleTabId();
		if (empty($tabId)) {
			return $defaultSettings;
		}
		$result = $db->pquery(
			'SELECT prefvalue FROM '.self::TAB_INFO_TABLE_NAME.' WHERE tabid = ? AND prefname = ?',
			array($tabId, self::PREF_NAME)
		);

		if ($db->num_rows($result) === 0) {
			return $defaultSettings;
		}

		return self::decodeSettings($db->query_result($result, 0, 'prefvalue'));
	}

	public static function getInstance() {
		return new self();
	}

	public static function encodeSettings($settings) {
		return serialize(self::toStorageSettings($settings));
	}

	public static function decodeSettings($serializedSettings) {
		$defaultSettings = self::getDefaultSettings();

		$serializedSettings = self::normalizeSerializedSettings($serializedSettings);
		if (empty($serializedSettings)) {
			return $defaultSettings;
		}

		$settings = @unserialize($serializedSettings);
		if (!is_array($settings)) {
			return $defaultSettings;
		}

		return array_merge($defaultSettings, self::fromStorageSettings($settings));
	}

	public static function getDefaultSettings() {
		return array(
			self::DISPLAY_MODE_KEY => self::DEFAULT_DISPLAY_MODE,
			self::MILESTONE_PRIMARY_PROGRESS_FIELD_KEY => self::NONE_FIELD_VALUE,
			self::MILESTONE_SECONDARY_PROGRESS_FIELD_KEY => self::NONE_FIELD_VALUE,
			self::BAR_HEIGHT_KEY => self::DEFAULT_BAR_HEIGHT,
			self::TASK_PRIMARY_PROGRESS_FIELD_KEY => self::NONE_FIELD_VALUE,
			self::TASK_SECONDARY_PROGRESS_FIELD_KEY => self::NONE_FIELD_VALUE,
		);
	}

	public static function getPercentageFieldOptions($moduleName) {
		$options = array(
			self::NONE_FIELD_VALUE => 'LBL_NONE',
		);
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if (!$moduleModel) {
			return $options;
		}
		foreach ($moduleModel->getFields() as $fieldName => $fieldModel) {
			if ($fieldModel->getFieldDataType() !== 'percentage') {
				continue;
			}
			$options[$fieldName] = decode_html(vtranslate($fieldModel->get('label'), $moduleName));
		}
		return $options;
	}

	public function getPicklistOptionLabel($fieldName, $optionValue) {
		$options = $this->getPicklistValues($fieldName);
		if (!isset($options[$optionValue])) {
			return '';
		}
		if ($fieldName === self::DISPLAY_MODE_KEY || $fieldName === self::BAR_HEIGHT_KEY || $optionValue === self::NONE_FIELD_VALUE) {
			return vtranslate($options[$optionValue], 'Settings:Vtiger');
		}
		return $options[$optionValue];
	}

	public static function normalizeSerializedSettings($serializedSettings) {
		if ($serializedSettings === null) {
			return null;
		}
		if (function_exists('decode_html')) {
			$serializedSettings = decode_html($serializedSettings);
		}
		return html_entity_decode($serializedSettings, ENT_QUOTES, 'UTF-8');
	}

	public static function toStorageSettings($settings) {
		$settings = array_merge(self::getDefaultSettings(), $settings);

		return array(
			self::STORAGE_DISPLAY_MODE_KEY => $settings[self::DISPLAY_MODE_KEY],
			self::STORAGE_MILESTONE_PRIMARY_PROGRESS_FIELD_KEY => $settings[self::MILESTONE_PRIMARY_PROGRESS_FIELD_KEY],
			self::STORAGE_MILESTONE_SECONDARY_PROGRESS_FIELD_KEY => $settings[self::MILESTONE_SECONDARY_PROGRESS_FIELD_KEY],
			self::STORAGE_BAR_HEIGHT_KEY => $settings[self::BAR_HEIGHT_KEY],
			self::STORAGE_TASK_PRIMARY_PROGRESS_FIELD_KEY => $settings[self::TASK_PRIMARY_PROGRESS_FIELD_KEY],
			self::STORAGE_TASK_SECONDARY_PROGRESS_FIELD_KEY => $settings[self::TASK_SECONDARY_PROGRESS_FIELD_KEY],
		);
	}

	public static function fromStorageSettings($settings) {
		$mappedSettings = array();
		$keyMap = array(
			self::STORAGE_DISPLAY_MODE_KEY => self::DISPLAY_MODE_KEY,
			self::STORAGE_MILESTONE_PRIMARY_PROGRESS_FIELD_KEY => self::MILESTONE_PRIMARY_PROGRESS_FIELD_KEY,
			self::STORAGE_MILESTONE_SECONDARY_PROGRESS_FIELD_KEY => self::MILESTONE_SECONDARY_PROGRESS_FIELD_KEY,
			self::STORAGE_BAR_HEIGHT_KEY => self::BAR_HEIGHT_KEY,
			self::STORAGE_TASK_PRIMARY_PROGRESS_FIELD_KEY => self::TASK_PRIMARY_PROGRESS_FIELD_KEY,
			self::STORAGE_TASK_SECONDARY_PROGRESS_FIELD_KEY => self::TASK_SECONDARY_PROGRESS_FIELD_KEY,
		);

		foreach ($keyMap as $storageKey => $runtimeKey) {
			if (isset($settings[$storageKey])) {
				$mappedSettings[$runtimeKey] = $settings[$storageKey];
			}
		}

		foreach (self::getDefaultSettings() as $runtimeKey => $defaultValue) {
			if (isset($settings[$runtimeKey])) {
				$mappedSettings[$runtimeKey] = $settings[$runtimeKey];
			}
		}

		return $mappedSettings;
	}

	public static function getModuleTabId() {
		$moduleModel = Vtiger_Module_Model::getInstance(self::MODULE_NAME);
		if ($moduleModel) {
			return $moduleModel->getId();
		}
		if (function_exists('getTabid')) {
			return getTabid(self::MODULE_NAME);
		}
		return null;
	}
}
