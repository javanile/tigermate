<?php


require_once 'debug.php';
include_once 'config.php';
include_once 'include/Webservices/Relation.php';

include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/main/WebUI.php';
include_once 'include/database/PearDatabase.php';


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

$allScheduler = array_reduce(Vtiger_Cron::listAllActiveInstances(), function($result, $scheduler) {;
    $result[] = $scheduler->getName();
    return $result;
}, array());

if (!in_array('GoogleSync', $allScheduler)) {
    Vtiger_Cron::register( 'GoogleSync', 'cron/modules/Google/GoogleSync.service', 900, 'Settings', 1, 5, 'Recommended frequency for Google sync is 15 mins');
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
