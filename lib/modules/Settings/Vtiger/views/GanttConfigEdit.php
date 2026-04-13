<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_Vtiger_GanttConfigEdit_View extends Settings_Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$qualifiedName = $request->getModule(false);
		$moduleModel = Settings_Vtiger_GanttConfig_Model::getInstance();

		$viewer = $this->getViewer($request);
		$viewer->assign('MODEL', $moduleModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$viewer->view('GanttConfigEdit.tpl', $qualifiedName);
	}

	function getPageTitle(Vtiger_Request $request) {
		return vtranslate('LBL_GANTT_CONFIG', $request->getModule(false));
	}

	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsScriptInstances = $this->checkAndConvertJsScripts(array(
			'modules.Settings.Vtiger.resources.GanttConfig',
		));
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
