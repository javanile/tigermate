<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

Class Settings_MenuEditor_SaveAjax_Action extends Settings_Vtiger_IndexAjax_View {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('removeModule');
		$this->exposeMethod('addModule');
		$this->exposeMethod('addCustomLink');
		$this->exposeMethod('updateCustomLink');
		$this->exposeMethod('removeCustomLink');
		$this->exposeMethod('saveSequence');
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	function removeModule(Vtiger_Request $request) {
		$sourceModule = $request->get('sourceModule');
		$appName = $request->get('appname');
		$db = PearDatabase::getInstance();
		$db->pquery('UPDATE vtiger_app2tab SET visible = ? WHERE tabid = ? AND appname = ?', array(0, getTabid($sourceModule), $appName));

		$response = new Vtiger_Response();
		$response->setResult(array('success' => true));
		$response->emit();
	}

	function addModule(Vtiger_Request $request) {
		$sourceModules = array($request->get('sourceModule'));
		if ($request->has('sourceModules')) {
			$sourceModules = $request->get('sourceModules');
		}
		$appName = $request->get('appname');
		$db = PearDatabase::getInstance();
		foreach ($sourceModules as $sourceModule) {
			$db->pquery('UPDATE vtiger_app2tab SET visible = ? WHERE tabid = ? AND appname = ?', array(1, getTabid($sourceModule), $appName));
		}

		$response = new Vtiger_Response();
		$response->setResult(array('success' => true));
		$response->emit();
	}

	function saveSequence(Vtiger_Request $request) {
		$moduleSequence = $request->get('sequence');
		$appName = $request->get('appname');
		$customLinkSequence = $request->get('customLinkSequence');
		$db = PearDatabase::getInstance();
		foreach ($moduleSequence as $moduleName => $sequence) {
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
			if (empty($moduleModel)) {
				continue;
			}
			$db->pquery('UPDATE vtiger_app2tab SET sequence = ? WHERE tabid = ? AND appname = ?', array($sequence, $moduleModel->getId(), $appName));
		}
		if (!empty($customLinkSequence)) {
			Settings_MenuEditor_CustomLink_Model::saveSequence($customLinkSequence, $appName);
		}

		$response = new Vtiger_Response();
		$response->setResult(array('success' => true));
		$response->emit();
	}

	function addCustomLink(Vtiger_Request $request) {
		$appName = $request->get('appname');
		$label = trim($request->get('label'));
		$linkUrl = trim($request->get('linkurl'));

		if (empty($appName) || empty($label) || empty($linkUrl)) {
			$response = new Vtiger_Response();
			$response->setError(400, 'Missing required fields');
			$response->emit();
			return;
		}

		Settings_MenuEditor_CustomLink_Model::addCustomLink($appName, $label, $linkUrl);

		$response = new Vtiger_Response();
		$response->setResult(array('success' => true));
		$response->emit();
	}

	function removeCustomLink(Vtiger_Request $request) {
		$linkId = $request->getInteger('customLinkId');
		Settings_MenuEditor_CustomLink_Model::removeCustomLink($linkId);

		$response = new Vtiger_Response();
		$response->setResult(array('success' => true));
		$response->emit();
	}

	function updateCustomLink(Vtiger_Request $request) {
		$linkId = $request->getInteger('customLinkId');
		$appName = $request->get('appname');
		$label = trim($request->get('label'));
		$linkUrl = trim($request->get('linkurl'));

		if (empty($linkId) || empty($appName) || empty($label) || empty($linkUrl)) {
			$response = new Vtiger_Response();
			$response->setError(400, 'Missing required fields');
			$response->emit();
			return;
		}

		Settings_MenuEditor_CustomLink_Model::updateCustomLink($linkId, $appName, $label, $linkUrl);

		$response = new Vtiger_Response();
		$response->setResult(array('success' => true));
		$response->emit();
	}

}

?>
