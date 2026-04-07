<?php
/**
 * Universal workflow method handlers.
 * Register with: $emm->addEntityMethod('*', 'MethodName', 'modules/com_vtiger_workflow/tasks/VTUniversalMethodHandler.php', 'function_name');
 * Each function can optionally define a companion function_fields() that returns input field definitions.
 */

// --- TestUniversalMethod ---

function VTEntityMethodTask_testUniversal($entityData, $params = array()) {
    $moduleName = $entityData->getModuleName();
    $recordId   = $entityData->getId();
    $email      = isset($params['email']) ? $params['email'] : '';
    $message    = isset($params['message']) ? $params['message'] : '';
    error_log("[TestUniversalMethod] module={$moduleName} id={$recordId} email={$email} message={$message}");
}

function VTEntityMethodTask_testUniversal_fields() {
    return array(
        array('name' => 'email',   'label' => 'Email Address'),
        array('name' => 'message', 'label' => 'Message'),
    );
}

// --- SecondTestMethod ---

function VTEntityMethodTask_secondTest($entityData, $params = array()) {
    $moduleName    = $entityData->getModuleName();
    $recordId      = $entityData->getId();
    $targetStatus  = isset($params['target_status']) ? $params['target_status'] : '';
    error_log("[SecondTestMethod] module={$moduleName} id={$recordId} target_status={$targetStatus}");
}

function VTEntityMethodTask_secondTest_fields() {
    return array(
        array('name' => 'target_status', 'label' => 'Target Status'),
    );
}
