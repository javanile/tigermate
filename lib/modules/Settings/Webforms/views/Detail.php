<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_Webforms_Detail_View extends Settings_Vtiger_Index_View {

	public function checkPermission(Vtiger_Request $request) {
		parent::checkPermission($request);

		$recordId = $request->get('record');
		$moduleModel = Vtiger_Module_Model::getInstance($request->getModule());

		$currentUserPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if(!$recordId || !$currentUserPrivilegesModel->hasModulePermission($moduleModel->getId())) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
        return true;
	}

	public function process(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);

		$recordModel = Settings_Webforms_Record_Model::getInstanceById($recordId, $qualifiedModuleName);
		$siteUrl = $postUrl = vglobal('site_URL');
		if($siteUrl[strlen($siteUrl)-1] != '/') $postUrl .= '/';
		$postUrl .= 'modules/Webforms/capture.php';
		$recordModel->set('posturl', $postUrl);

		$recordStructure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
		$moduleModel = $recordModel->getModule();
		$roundRobinUsers = $recordModel->get('roundrobin_userid');
		if (is_string($roundRobinUsers) && $roundRobinUsers !== '') {
			$decodedRoundRobinUsers = json_decode(html_entity_decode($roundRobinUsers, ENT_QUOTES, 'UTF-8'), true);
			$roundRobinUsers = is_array($decodedRoundRobinUsers) ? $decodedRoundRobinUsers : array();
		}
		if (!is_array($roundRobinUsers)) {
			$roundRobinUsers = array();
		}

		$roundRobinUserNames = array();
		foreach ($roundRobinUsers as $roundRobinUserId) {
			if ($roundRobinUserId) {
				$roundRobinUserNames[] = getOwnerName($roundRobinUserId);
			}
		}

		$detailInformation = array(
			array('label' => 'Webform Name', 'value' => $recordModel->getName()),
			array('label' => 'Module', 'value' => vtranslate($recordModel->get('targetmodule'), $recordModel->get('targetmodule'))),
			array('label' => 'Public Id', 'value' => $recordModel->get('publicid')),
			array('label' => 'Post Url', 'value' => $recordModel->get('posturl')),
			array('label' => 'Return Url', 'value' => $recordModel->get('returnurl')),
			array('label' => 'Status', 'value' => $recordModel->get('enabled') ? vtranslate('LBL_ACTIVE', $qualifiedModuleName) : vtranslate('LBL_INACTIVE', $qualifiedModuleName)),
			array('label' => 'Captcha Enabled', 'value' => $recordModel->get('captcha') ? vtranslate('LBL_YES', $qualifiedModuleName) : vtranslate('LBL_NO', $qualifiedModuleName)),
			array('label' => 'Assigned To', 'value' => getOwnerName($recordModel->get('ownerid'))),
			array('label' => 'LBL_ASSIGN_ROUND_ROBIN', 'value' => $recordModel->get('roundrobin') ? vtranslate('LBL_YES', $qualifiedModuleName) : vtranslate('LBL_NO', $qualifiedModuleName)),
			array('label' => 'LBL_ROUNDROBIN_USERS_LIST', 'value' => implode(', ', $roundRobinUserNames)),
			array('label' => 'Description', 'value' => $recordModel->get('description')),
		);

		$navigationInfo = ListViewSession::getListViewNavigation($recordId);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_NAME', $qualifiedModuleName);
		$viewer->assign('RECORD_STRUCTURE', $recordStructure->getStructure());
		$viewer->assign('MODULE_MODEL', $moduleModel);

		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('SOURCE_MODULE', $recordModel->get('targetmodule'));
		$viewer->assign('DETAIL_INFORMATION', $detailInformation);
		$viewer->assign('DETAILVIEW_LINKS', $recordModel->getDetailViewLinks());
		$viewer->assign('SELECTED_FIELD_MODELS_LIST', $recordModel->getSelectedFieldsList());
		$viewer->assign('DOCUMENT_FILE_FIELDS', $recordModel->getFileFields());
		$viewer->assign('NO_PAGINATION',true);

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$userCurrencyInfo = getCurrencySymbolandCRate($currentUserModel->get('currency_id'));
		$viewer->assign('USER_CURRENCY_SYMBOL', $userCurrencyInfo['symbol']);

		$viewer->view('DetailView.tpl', $qualifiedModuleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.Settings.Vtiger.resources.Detail",
			"modules.Settings.$moduleName.resources.Detail"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

}
