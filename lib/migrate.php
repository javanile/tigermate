<?php


require_once 'debug.php';
include_once 'config.php';
include_once 'include/Webservices/Relation.php';

include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/main/WebUI.php';
include_once 'include/database/PearDatabase.php';
require_once 'include/Webservices/Utils.php';


ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

global $adb;

$adb->setDieOnError(true);

echo "Checking for missing tables and creating them if necessary...\n";

if (!Vtiger_Utils::CheckTable('vtiger_cv2role')) {
    Vtiger_Utils::CreateTable('vtiger_cv2role',
        '(`cvid` int(25) NOT NULL,
				`roleid` varchar(255) NOT NULL,
				KEY `vtiger_cv2role_ibfk_1` (`cvid`),
				CONSTRAINT `vtiger_customview_ibfk_3` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE,
				CONSTRAINT `vtiger_role_ibfk_1` FOREIGN KEY (`roleid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE)', true);
}

if (!Vtiger_Utils::CheckTable('vtiger_cv2rs')) {
    Vtiger_Utils::CreateTable('vtiger_cv2rs',
        '(`cvid` int(25) NOT NULL,
				`rsid` varchar(255) NOT NULL,
				KEY `vtiger_cv2role_ibfk_1` (`cvid`),
				CONSTRAINT `vtiger_customview_ibfk_4` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE,
				CONSTRAINT `vtiger_rolesd_ibfk_1` FOREIGN KEY (`rsid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE)', true);
}

$ganttSettingKey = 'display_mode';
$ganttDefaultDisplayMode = 'all';
$validGanttDisplayModes = array('all', 'tasks', 'milestones');
$ganttDefaultSettings = array(
    'display_mode' => 'all',
    'milestone_primary_progress_field' => 'none',
    'milestone_secondary_progress_field' => 'none',
    'task_primary_progress_field' => 'none',
    'task_secondary_progress_field' => 'none',
    'bar_height' => 'medium',
);
$ganttStorageSettings = array(
    'dm' => 'all',
    'mp' => 'none',
    'ms' => 'none',
    'bh' => 'medium',
    'tp' => 'none',
    'ts' => 'none',
);
$validGanttBarHeights = array('small', 'medium', 'large');
if (!Vtiger_Utils::CheckTable('vtiger_tab_info')) {
    Vtiger_Utils::CreateTable(
        'vtiger_tab_info',
        '(tabid INT, prefname VARCHAR(256), prefvalue VARCHAR(256), FOREIGN KEY fk_1_vtiger_tab_info(tabid) REFERENCES vtiger_tab(tabid) ON DELETE CASCADE ON UPDATE CASCADE)',
        true
    );
    echo "Created table: vtiger_tab_info\n";
}

$tabInfoPrefvalueResult = $adb->pquery("SHOW COLUMNS FROM vtiger_tab_info LIKE 'prefvalue'", array());
if ($adb->num_rows($tabInfoPrefvalueResult) > 0) {
    $prefvalueColumnType = strtolower($adb->query_result($tabInfoPrefvalueResult, 0, 'Type'));
    if ($prefvalueColumnType !== 'text' && $prefvalueColumnType !== 'mediumtext' && $prefvalueColumnType !== 'longtext') {
        $adb->pquery('ALTER TABLE vtiger_tab_info MODIFY prefvalue TEXT', array());
        echo "Extended vtiger_tab_info.prefvalue to TEXT\n";
    } else {
        echo "vtiger_tab_info.prefvalue already extended: {$prefvalueColumnType}\n";
    }
}

$projectTabId = getTabid('Project');
$ganttPrefName = 'tigermate_gantt_settings';
$ganttSerializedSettings = serialize($ganttStorageSettings);

if (Vtiger_Utils::CheckTable('vtiger_gantt_settings')) {
    $legacyGanttSettingResult = $adb->pquery(
        'SELECT value FROM vtiger_gantt_settings WHERE name = ?',
        array($ganttSettingKey)
    );
    if ($adb->num_rows($legacyGanttSettingResult) > 0) {
        $legacyDisplayMode = $adb->query_result($legacyGanttSettingResult, 0, 'value');
        if (in_array($legacyDisplayMode, $validGanttDisplayModes, true)) {
            $ganttStorageSettings['dm'] = $legacyDisplayMode;
            $ganttSerializedSettings = serialize($ganttStorageSettings);
        }
    }
}

$ganttSettingResult = $adb->pquery(
    'SELECT prefvalue FROM vtiger_tab_info WHERE tabid = ? AND prefname = ?',
    array($projectTabId, $ganttPrefName)
);
if ($adb->num_rows($ganttSettingResult) === 0) {
    $adb->pquery(
        'INSERT INTO vtiger_tab_info(tabid, prefname, prefvalue) VALUES(?, ?, ?)',
        array($projectTabId, $ganttPrefName, $ganttSerializedSettings)
    );
    echo "Inserted default Gantt setting into vtiger_tab_info for Project\n";
} else {
    $currentSerializedSettings = html_entity_decode($adb->query_result($ganttSettingResult, 0, 'prefvalue'), ENT_QUOTES, 'UTF-8');
    if (function_exists('decode_html')) {
        $currentSerializedSettings = decode_html($currentSerializedSettings);
    }
    $currentSettings = @unserialize($currentSerializedSettings);
    if (!is_array($currentSettings)) {
        $currentSettings = array();
    }
    $normalizedSettings = array(
        'display_mode' => isset($currentSettings['dm']) ? $currentSettings['dm'] : (isset($currentSettings['display_mode']) ? $currentSettings['display_mode'] : $ganttDefaultSettings['display_mode']),
        'milestone_primary_progress_field' => isset($currentSettings['mp']) ? $currentSettings['mp'] : (isset($currentSettings['milestone_primary_progress_field']) ? $currentSettings['milestone_primary_progress_field'] : $ganttDefaultSettings['milestone_primary_progress_field']),
        'milestone_secondary_progress_field' => isset($currentSettings['ms']) ? $currentSettings['ms'] : (isset($currentSettings['milestone_secondary_progress_field']) ? $currentSettings['milestone_secondary_progress_field'] : $ganttDefaultSettings['milestone_secondary_progress_field']),
        'bar_height' => isset($currentSettings['bh']) ? $currentSettings['bh'] : (isset($currentSettings['bar_height']) ? $currentSettings['bar_height'] : $ganttDefaultSettings['bar_height']),
        'task_primary_progress_field' => isset($currentSettings['tp']) ? $currentSettings['tp'] : (isset($currentSettings['task_primary_progress_field']) ? $currentSettings['task_primary_progress_field'] : $ganttDefaultSettings['task_primary_progress_field']),
        'task_secondary_progress_field' => isset($currentSettings['ts']) ? $currentSettings['ts'] : (isset($currentSettings['task_secondary_progress_field']) ? $currentSettings['task_secondary_progress_field'] : $ganttDefaultSettings['task_secondary_progress_field']),
    );
    if (!in_array($normalizedSettings['display_mode'], $validGanttDisplayModes, true)) {
        $normalizedSettings['display_mode'] = $ganttDefaultDisplayMode;
    }
    if (!in_array($normalizedSettings['bar_height'], $validGanttBarHeights, true)) {
        $normalizedSettings['bar_height'] = $ganttDefaultSettings['bar_height'];
    }
    $normalizedSerializedSettings = serialize(array(
        'dm' => $normalizedSettings['display_mode'],
        'mp' => $normalizedSettings['milestone_primary_progress_field'],
        'ms' => $normalizedSettings['milestone_secondary_progress_field'],
        'bh' => $normalizedSettings['bar_height'],
        'tp' => $normalizedSettings['task_primary_progress_field'],
        'ts' => $normalizedSettings['task_secondary_progress_field'],
    ));
    if ($normalizedSerializedSettings !== serialize($currentSettings)) {
        $adb->pquery(
            'UPDATE vtiger_tab_info SET prefvalue = ? WHERE tabid = ? AND prefname = ?',
            array($normalizedSerializedSettings, $projectTabId, $ganttPrefName)
        );
        echo "Normalized Gantt settings in vtiger_tab_info\n";
    } else {
        echo "Gantt settings already configured in vtiger_tab_info: {$ganttSettingKey}=".$normalizedSettings[$ganttSettingKey]."\n";
    }
}

$ganttSettingsLinkName = 'LBL_GANTT_CONFIG';
$ganttSettingsLinkUrl = 'index.php?module=Vtiger&parent=Settings&view=GanttConfigDetail';
$ganttSettingsBlockId = getSettingsBlockId('LBL_OTHER_SETTINGS');
$ganttSettingsFieldResult = $adb->pquery(
    'SELECT fieldid, blockid, linkto, active FROM vtiger_settings_field WHERE name = ?',
    array($ganttSettingsLinkName)
);
if ($adb->num_rows($ganttSettingsFieldResult) === 0) {
    vtlib_addSettingsLink($ganttSettingsLinkName, $ganttSettingsLinkUrl, 'LBL_OTHER_SETTINGS');
    echo "Registered settings link: {$ganttSettingsLinkName}\n";
} else {
    $ganttSettingsFieldId = $adb->query_result($ganttSettingsFieldResult, 0, 'fieldid');
    $currentBlockId = $adb->query_result($ganttSettingsFieldResult, 0, 'blockid');
    $currentLinkTo = $adb->query_result($ganttSettingsFieldResult, 0, 'linkto');
    $currentActive = (int) $adb->query_result($ganttSettingsFieldResult, 0, 'active');

    if ((int) $currentBlockId !== (int) $ganttSettingsBlockId || $currentLinkTo !== $ganttSettingsLinkUrl || $currentActive !== 0) {
        $adb->pquery(
            'UPDATE vtiger_settings_field SET blockid = ?, linkto = ?, active = 0 WHERE fieldid = ?',
            array($ganttSettingsBlockId, $ganttSettingsLinkUrl, $ganttSettingsFieldId)
        );
        echo "Updated settings link: {$ganttSettingsLinkName}\n";
    } else {
        echo "Settings link already configured: {$ganttSettingsLinkName}\n";
    }
}

$allScheduler = array_reduce(Vtiger_Cron::listAllActiveInstances(), function($result, $scheduler) {;
    $result[] = $scheduler->getName();
    return $result;
}, array());

if (!in_array('GoogleSync', $allScheduler)) {
    Vtiger_Cron::register( 'GoogleSync', 'cron/modules/Google/GoogleSync.service', 900, 'Settings', 1, 5, 'Recommended frequency for Google sync is 15 mins');
}

$workflowManagerPath = 'modules/com_vtiger_workflow/VTWorkflowManager.inc';
$workflowManagerContents = file_get_contents($workflowManagerPath);
$legacyScheduledWorkflowClause = "AND (nexttrigger_time = '' OR nexttrigger_time IS NULL OR nexttrigger_time <= ?)";
$fixedScheduledWorkflowClause = "AND (nexttrigger_time IS NULL OR nexttrigger_time <= ?)";

if ($workflowManagerContents === false) {
    echo "ERROR: Unable to read $workflowManagerPath\n";
} elseif (strpos($workflowManagerContents, $legacyScheduledWorkflowClause) !== false) {
    $updatedWorkflowManagerContents = str_replace(
        $legacyScheduledWorkflowClause,
        $fixedScheduledWorkflowClause,
        $workflowManagerContents
    );

    if ($updatedWorkflowManagerContents !== $workflowManagerContents && file_put_contents($workflowManagerPath, $updatedWorkflowManagerContents) !== false) {
        echo "Patched scheduled workflow query for MySQL 8 compatibility in $workflowManagerPath\n";
    } else {
        echo "ERROR: Unable to patch scheduled workflow query in $workflowManagerPath\n";
    }
} else {
    echo "Scheduled workflow query already compatible in $workflowManagerPath\n";
}

require_once 'modules/com_vtiger_workflow/VTTaskManager.inc';
$existingDbq = $adb->pquery("SELECT id FROM com_vtiger_workflow_tasktypes WHERE tasktypename = ?", array('VTDatabaseQueryTask'));
if ($adb->num_rows($existingDbq) === 0) {
    VTTaskType::registerTaskType(array(
        'name'         => 'VTDatabaseQueryTask',
        'label'        => 'Database Query',
        'classname'    => 'VTDatabaseQueryTask',
        'classpath'    => 'modules/com_vtiger_workflow/tasks/VTDatabaseQueryTask.inc',
        'templatepath' => 'modules/Settings/Workflows/Tasks/VTDatabaseQueryTask.tpl',
        'modules'      => array('include' => array(), 'exclude' => array()),
        'sourcemodule' => '',
    ));
    echo "Registered workflow task type: VTDatabaseQueryTask\n";
} else {
    echo "Workflow task type already registered: VTDatabaseQueryTask\n";
}

require_once 'modules/com_vtiger_workflow/VTEntityMethodManager.inc';
$emm = new VTEntityMethodManager($adb);
$universalMethods = $emm->methodsForModule('*');
if (!in_array('TestUniversalMethod', $universalMethods)) {
    $emm->addEntityMethod('*', 'TestUniversalMethod', 'modules/com_vtiger_workflow/tasks/VTUniversalMethodHandler.php', 'VTEntityMethodTask_testUniversal');
    echo "Registered universal workflow method: TestUniversalMethod\n";
} else {
    echo "Universal workflow method already registered: TestUniversalMethod\n";
}

if (!in_array('SecondTestMethod', $universalMethods)) {
    $emm->addEntityMethod('*', 'SecondTestMethod', 'modules/com_vtiger_workflow/tasks/VTUniversalMethodHandler.php', 'VTEntityMethodTask_secondTest');
    echo "Registered universal workflow method: SecondTestMethod\n";
} else {
    echo "Universal workflow method already registered: SecondTestMethod\n";
}

// Add projectmilestonedeliverydate field to ProjectMilestone module
$projectMilestoneModule = Vtiger_Module::getInstance('ProjectMilestone');
if ($projectMilestoneModule) {
    $existingField = Vtiger_Field::getInstance('projectmilestonedeliverydate', $projectMilestoneModule);
    if (!$existingField) {
        $block = Vtiger_Block::getInstance('LBL_PROJECT_MILESTONE_INFORMATION', $projectMilestoneModule);
        if ($block) {
            $field = new Vtiger_Field();
            $field->name       = 'projectmilestonedeliverydate';
            $field->label      = 'Delivery Date';
            $field->table      = 'vtiger_projectmilestone';
            $field->column     = 'projectmilestonedeliverydate';
            $field->columntype = 'VARCHAR(255)';
            $field->uitype     = 5;      // Date
            $field->typeofdata = 'D~O';  // Date, Optional
            $block->addField($field);
            echo "Created field: projectmilestonedeliverydate on ProjectMilestone\n";
        } else {
            echo "ERROR: Block LBL_PROJECT_MILESTONE_INFORMATION not found on ProjectMilestone\n";
        }
    } else {
        if ($existingField->label !== 'Delivery Date') {
            $adb->pquery("UPDATE vtiger_field SET fieldlabel=? WHERE fieldname=?", ['Delivery Date', 'projectmilestonedeliverydate']);
            echo "Fixed fieldlabel for projectmilestonedeliverydate\n";
        } else {
            echo "Field projectmilestonedeliverydate already exists and is correct\n";
        }
    }
} else {
    echo "ERROR: Module ProjectMilestone not found\n";
}

function ensureWebserviceOperation($name, $handlerPath, $handlerMethod, $requestType, $prelogin, $params) {
    global $adb;

    $result = $adb->pquery('SELECT operationid, handler_path, handler_method, type, prelogin FROM vtiger_ws_operation WHERE name = ?', array($name));
    if ($adb->num_rows($result) > 0) {
        $operationId = $adb->query_result($result, 0, 'operationid');
        $currentPath = $adb->query_result($result, 0, 'handler_path');
        $currentMethod = $adb->query_result($result, 0, 'handler_method');
        $currentType = strtoupper((string) $adb->query_result($result, 0, 'type'));
        $currentPrelogin = (int) $adb->query_result($result, 0, 'prelogin');

        if ($currentPath !== $handlerPath || $currentMethod !== $handlerMethod || $currentType !== strtoupper($requestType) || $currentPrelogin !== (int) $prelogin) {
            $adb->pquery(
                'UPDATE vtiger_ws_operation SET handler_path = ?, handler_method = ?, type = ?, prelogin = ? WHERE operationid = ?',
                array($handlerPath, $handlerMethod, strtoupper($requestType), (int) $prelogin, $operationId)
            );
            echo "Updated webservice operation: $name\n";
        } else {
            echo "Webservice operation already registered: $name\n";
        }
    } else {
        $operationId = vtws_addWebserviceOperation($name, $handlerPath, $handlerMethod, $requestType, $prelogin);
        echo "Registered webservice operation: $name\n";
    }

    $sequence = 1;
    foreach ($params as $paramName => $paramType) {
        $paramResult = $adb->pquery(
            'SELECT 1 FROM vtiger_ws_operation_parameters WHERE operationid = ? AND name = ?',
            array($operationId, $paramName)
        );
        if ($adb->num_rows($paramResult) == 0) {
            vtws_addWebserviceOperationParam($operationId, $paramName, $paramType, $sequence);
            echo "Added webservice parameter $name.$paramName\n";
        }
        $sequence++;
    }
}

ensureWebserviceOperation('inspect', 'include/Webservices/Inspect.php', 'vtws_inspect', 'GET', 0, array(
    'command' => 'string',
    'action' => 'string',
    'table' => 'string',
    'path' => 'string',
    'sql' => 'string',
    'offset' => 'string',
    'length' => 'string',
));

ensureWebserviceOperation('control', 'include/Webservices/Control.php', 'vtws_control', 'POST', 0, array(
    'command' => 'string',
    'action' => 'string',
    'path' => 'string',
    'target' => 'string',
    'sql' => 'string',
    'content' => 'string',
    'recursive' => 'string',
    'createParents' => 'string',
    'encoding' => 'string',
    'transaction' => 'string',
));
