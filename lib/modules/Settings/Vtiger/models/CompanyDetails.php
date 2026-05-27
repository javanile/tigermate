<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_Vtiger_CompanyDetails_Model extends Settings_Vtiger_Module_Model {

	STATIC $logoSupportedFormats = array('jpeg', 'jpg', 'png', 'gif', 'pjpeg', 'x-png');
	STATIC $faviconSupportedFormats = array('ico', 'png', 'jpg', 'jpeg', 'gif');
	var $faviconFilename = 'favicon.ico';

	var $baseTable = 'vtiger_organizationdetails';
	var $baseIndex = 'organization_id';
	var $listFields = array('organizationname');
	var $nameFields = array('organizationname');
	var $logoPath = 'test/logo/';

	var $fields = array(
		'organizationname' => 'text',
		'logoname' => 'text',
		'logo' => 'file',
		'address' => 'textarea',
		'city' => 'text',
		'state' => 'text',
		'code'  => 'text',
		'country' => 'text',
		'phone' => 'text',
		'fax' => 'text',
		'website' => 'text',
		'vatid' => 'text' 
	);

	var $companyBasicFields = array(
		'organizationname' => 'text',
		'logoname' => 'text',
		'logo' => 'file',
		'address' => 'textarea',
		'city' => 'text',
		'state' => 'text',
		'code'  => 'text',
		'country' => 'text',
		'phone' => 'text',
		'fax' => 'text',
		'vatid' => 'text'
	);

	var $companySocialLinks = array(
		'website' => 'text',
	);

	var $selfAccountFieldMap = array(
		'organizationname' => 'accountname',
		'address' => 'bill_street',
		'city' => 'bill_city',
		'state' => 'bill_state',
		'code' => 'bill_code',
		'country' => 'bill_country',
		'phone' => 'phone',
		'fax' => 'fax',
		'website' => 'website',
	);

	/**
	 * Function to get Edit view Url
	 * @return <String> Url
	 */
	public function getEditViewUrl() {
		return 'index.php?module=Vtiger&parent=Settings&view=CompanyDetailsEdit';
	}

	/**
	 * Function to get CompanyDetails Menu item
	 * @return menu item Model
	 */
	public function getMenuItem() {
		$menuItem = Settings_Vtiger_MenuItem_Model::getInstance('LBL_COMPANY_DETAILS');
		return $menuItem;
	}

	/**
	 * Function to get Index view Url
	 * @return <String> URL
	 */
	public function getIndexViewUrl() {
		$menuItem = $this->getMenuItem();
		return 'index.php?module=Vtiger&parent=Settings&view=CompanyDetails&block='.$menuItem->get('blockid').'&fieldid='.$menuItem->get('fieldid');
	}

	/**
	 * Function to get fields
	 * @return <Array>
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * Function to get Logo path to display
	 * @return <String> path
	 */
	public function getLogoPath() {
		$logoPath = $this->logoPath;
		$handler = @opendir($logoPath);
		$logoName = decode_html($this->get('logoname'));
		if ($logoName && $handler) {
			while ($file = readdir($handler)) {
				if($logoName === $file && in_array(str_replace('.', '', strtolower(substr($file, -4))), self::$logoSupportedFormats) && $file != "." && $file!= "..") {
					closedir($handler);
					return $logoPath.$logoName;
				}
			}
		}
		return '';
	}

	/**
	 * Function to get Favicon path to display
	 */
	public function getFaviconPath() {
		$path = $this->logoPath . $this->faviconFilename;
		return file_exists($path) ? $path : '';
	}

	/**
	 * Function to save the favicon
	 */
	public function saveFavicon() {
		$uploadDir = vglobal('root_directory') . '/' . $this->logoPath;
		move_uploaded_file($_FILES['favicon']['tmp_name'], $uploadDir . $this->faviconFilename);
	}

	/**
	 * Function to save the logoinfo
	 */
	public function saveLogo($logoName) {
		$uploadDir = vglobal('root_directory'). '/' .$this->logoPath;
		$logoName = $uploadDir.$logoName;
		move_uploaded_file($_FILES["logo"]["tmp_name"], $logoName);
		copy($logoName, $uploadDir.'application.ico');
	}

	/**
	 * Function to save the Company details
	 */
	public function save() {
		$db = PearDatabase::getInstance();
		$id = $this->get('id');
		$fieldsList = $this->getFields();
		unset($fieldsList['logo']);
		$tableName = $this->baseTable;

		if ($id) {
			$params = array();

			$query = "UPDATE $tableName SET ";
			foreach ($fieldsList as $fieldName => $fieldType) {
				$query .= " $fieldName = ?, ";
				array_push($params, $this->get($fieldName));
			}
			$query .= " logo = NULL WHERE organization_id = ?";

			array_push($params, $id);
		} else {
			$params = $this->getData();

			$query = "INSERT INTO $tableName (";
			foreach ($fieldsList as $fieldName => $fieldType) {
				$query .= " $fieldName,";
			}
			$query .= " organization_id) VALUES (". generateQuestionMarks($params). ", ?)";

			array_push($params, $db->getUniqueID($this->baseTable));
		}
		$db->pquery($query, $params);
	}

	public function syncSelfAccount() {
		global $current_user;
		if (empty($current_user)) {
			$current_user = Users::getActiveAdminUser();
		}

		$organizationName = trim((string) $this->get('organizationname'));
		if ($organizationName === '') {
			return;
		}

		$db = PearDatabase::getInstance();
		$organizationId = $this->get('id') ? $this->get('id') : $this->get('organization_id');
		$selfAccountId = (int) $this->get('self_account_id');
		$accountRecord = null;

		if ($selfAccountId > 0 && $this->isActiveAccount($selfAccountId)) {
			$accountRecord = Vtiger_Record_Model::getInstanceById($selfAccountId, 'Accounts');
		} else {
			$selfAccountId = $this->findAccountByName($organizationName);
			if ($selfAccountId > 0) {
				$accountRecord = Vtiger_Record_Model::getInstanceById($selfAccountId, 'Accounts');
			}
		}

		if (!$accountRecord) {
			$accountRecord = Vtiger_Record_Model::getCleanInstance('Accounts');
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$currentUserId = $currentUser->getId() ? $currentUser->getId() : 1;
			$accountRecord->set('assigned_user_id', $currentUserId);
		}

		foreach ($this->selfAccountFieldMap as $companyField => $accountField) {
			$accountRecord->set($accountField, $this->get($companyField));
		}

		$accountRecord->save();
		$savedAccountId = $accountRecord->getId();

		if ($organizationId && $savedAccountId && (int) $this->get('self_account_id') !== (int) $savedAccountId) {
			$db->pquery(
				'UPDATE vtiger_organizationdetails SET self_account_id = ? WHERE organization_id = ?',
				array($savedAccountId, $organizationId)
			);
			$this->set('self_account_id', $savedAccountId);
		}
	}

	protected function isActiveAccount($accountId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			'SELECT crmid FROM vtiger_crmentity WHERE crmid = ? AND setype = ? AND deleted = 0',
			array($accountId, 'Accounts')
		);
		return $db->num_rows($result) > 0;
	}

	protected function findAccountByName($accountName) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			'SELECT vtiger_account.accountid FROM vtiger_account INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_account.accountid WHERE vtiger_account.accountname = ? AND vtiger_crmentity.deleted = 0 ORDER BY vtiger_account.accountid ASC',
			array($accountName)
		);
		if ($db->num_rows($result) > 0) {
			return (int) $db->query_result($result, 0, 'accountid');
		}
		return 0;
	}

	/**
	 * Function to get the instance of Company details module model
	 * @return <Settings_Vtiger_CompanyDetais_Model> $moduleModel
	 */
	public static function getInstance($name = '') {
		$moduleModel = new self();
		$db = PearDatabase::getInstance();

		$result = $db->pquery("SELECT * FROM vtiger_organizationdetails", array());
		if ($db->num_rows($result) == 1) {
			$moduleModel->setData($db->query_result_rowdata($result));
			$moduleModel->set('id', $moduleModel->get('organization_id'));
		}

		$moduleModel->getFields();
		return $moduleModel;
	}
}
