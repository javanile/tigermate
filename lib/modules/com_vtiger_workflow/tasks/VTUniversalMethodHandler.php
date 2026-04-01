<?php
/**
 * Universal workflow method handlers.
 * Register with: $emm->addEntityMethod('*', 'MethodName', 'modules/com_vtiger_workflow/tasks/VTUniversalMethodHandler.php', 'function_name');
 */

function VTEntityMethodTask_testUniversal($entityData, $params = array()) {
    $moduleName = $entityData->getModuleName();
    $recordId   = $entityData->getId();
    $param1     = isset($params['param1']) ? $params['param1'] : '';
    $param2     = isset($params['param2']) ? $params['param2'] : '';
    error_log("[TestUniversalMethod] module={$moduleName} id={$recordId} param1={$param1} param2={$param2}");
}