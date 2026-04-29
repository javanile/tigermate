<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_MenuEditor_CustomLink_Model extends Vtiger_Base_Model {

	const LINK_TYPE = 'APPMENUCUSTOM';

	public static function ensureTable() {
		return true;
	}

	public static function getAllVisibleByApp() {
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT * FROM vtiger_links WHERE linktype = ? ORDER BY handler_class, sequence, linkid', array(self::LINK_TYPE));
		$links = array();
		$count = $db->num_rows($result);

		for ($i = 0; $i < $count; $i++) {
			$model = new self();
			$appName = $db->query_result($result, $i, 'handler_class');
			$id = (int) $db->query_result($result, $i, 'linkid');
			$model->setData(array(
				'id' => $id,
				'name' => 'CustomLink'.$id,
				'key' => 'customlink_'.$id,
				'label' => $db->query_result($result, $i, 'linklabel'),
				'linkurl' => $db->query_result($result, $i, 'linkurl'),
				'icon' => $db->query_result($result, $i, 'linkicon'),
				'appname' => $appName,
				'app2tab_sequence' => (int) $db->query_result($result, $i, 'sequence'),
				'isCustomLink' => true,
			));
			$links[$appName][$model->get('key')] = $model;
		}

		return $links;
	}

	public static function addCustomLink($appName, $label, $linkUrl, $icon = 'fa-link') {
		$db = PearDatabase::getInstance();
		$sequence = self::getMaxSequenceForApp($appName) + 1;
		$linkId = $db->getUniqueID('vtiger_links');
		$db->pquery(
			'INSERT INTO vtiger_links (linkid, tabid, linktype, linklabel, linkurl, linkicon, sequence, handler_class) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
			array($linkId, 0, self::LINK_TYPE, $label, $linkUrl, $icon, $sequence, $appName)
		);
	}

	public static function removeCustomLink($id) {
		$db = PearDatabase::getInstance();
		$db->pquery('DELETE FROM vtiger_links WHERE linkid = ? AND linktype = ?', array((int) $id, self::LINK_TYPE));
	}

	public static function updateCustomLink($id, $appName, $label, $linkUrl, $icon = 'fa-link') {
		$db = PearDatabase::getInstance();
		$db->pquery(
			'UPDATE vtiger_links SET linklabel = ?, linkurl = ?, linkicon = ?, handler_class = ? WHERE linkid = ? AND linktype = ?',
			array($label, $linkUrl, $icon, $appName, (int) $id, self::LINK_TYPE)
		);
	}

	public static function saveSequence($sequenceById, $appName) {
		$db = PearDatabase::getInstance();
		foreach ($sequenceById as $id => $sequence) {
			$db->pquery(
				'UPDATE vtiger_links SET sequence = ? WHERE linkid = ? AND linktype = ? AND handler_class = ?',
				array((int) $sequence, (int) $id, $appName)
			);
		}
	}

	public static function getMaxSequenceForApp($appName) {
		$db = PearDatabase::getInstance();
		$linkResult = $db->pquery(
			'SELECT MAX(sequence) AS maxsequence FROM vtiger_links WHERE linktype = ? AND handler_class = ?',
			array(self::LINK_TYPE, $appName)
		);
		$appResult = $db->pquery(
			'SELECT MAX(sequence) AS maxsequence FROM vtiger_app2tab WHERE appname = ?',
			array($appName)
		);

		$linkMax = ($db->num_rows($linkResult) > 0) ? (int) $db->query_result($linkResult, 0, 'maxsequence') : 0;
		$appMax = ($db->num_rows($appResult) > 0) ? (int) $db->query_result($appResult, 0, 'maxsequence') : 0;

		return max($linkMax, $appMax);
	}

	public function isCustomLink() {
		return true;
	}

	public function getDefaultUrl() {
		return $this->get('linkurl');
	}

	public function getModuleIcon() {
		$icon = $this->get('icon');
		if (empty($icon)) {
			$icon = 'fa-link';
		}

		return '<i class="fa '.$icon.'" title="'.htmlspecialchars($this->get('label'), ENT_QUOTES, 'UTF-8').'"></i>';
	}

	public function needsAppParameter() {
		$linkUrl = trim($this->get('linkurl'));
		return !(preg_match('/^(?:[a-z]+:)?\/\//i', $linkUrl) || strpos($linkUrl, 'javascript:') === 0);
	}
}
