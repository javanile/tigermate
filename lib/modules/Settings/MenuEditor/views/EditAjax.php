<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class Settings_MenuEditor_EditAjax_View extends Settings_Vtiger_Index_View {

	public function __construct() {
		parent::__construct();
		$this->exposeMethod('showAddModule');
		$this->exposeMethod('showCustomLinkForm');
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	function showAddModule(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		$appName = $request->get('appname');

		$viewer->assign('SELECTED_APP_NAME', $appName);
		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->view('AddModule.tpl', $qualifiedModuleName);
	}

	function showCustomLinkForm(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);

		$viewer->assign('SELECTED_APP_NAME', $request->get('appname'));
		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('CUSTOM_LINK_ID', $request->getInteger('customLinkId'));
		$viewer->assign('CUSTOM_LINK_LABEL', $request->get('label'));
		$viewer->assign('CUSTOM_LINK_URL', $request->get('linkurl'));
		$viewer->assign('FORM_MODE', $request->get('formMode'));
		$viewer->view('CustomLinkForm.tpl', $qualifiedModuleName);
	}

}
