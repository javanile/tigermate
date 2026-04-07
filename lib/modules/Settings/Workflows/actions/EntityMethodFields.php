<?php
require_once 'modules/com_vtiger_workflow/VTEntityMethodManager.inc';

class Settings_Workflows_EntityMethodFields_Action extends Settings_Vtiger_Basic_Action {

    function __construct() {
        parent::__construct();
        $this->exposeMethod('get');
    }

    public function process(Vtiger_Request $request) {
        $mode = $request->getMode();
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    public function get(Vtiger_Request $request) {
        global $adb;
        $methodName = $request->get('methodName');
        $moduleName = $request->get('module_name');

        $emm = new VTEntityMethodManager($adb);
        $fields = $emm->getMethodFields($moduleName, $methodName);

        $result = new Vtiger_Response();
        $result->setResult($fields);
        $result->emit();
    }
}
