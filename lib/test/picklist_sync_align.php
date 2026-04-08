<?php
chdir(__DIR__ . '/..');
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'includes/runtime/BaseModel.php';
require_once 'modules/Vtiger/models/Module.php';
require_once 'modules/Vtiger/models/Field.php';
require_once 'modules/Settings/Picklist/models/Field.php';
require_once 'modules/Settings/Picklist/models/Module.php';

$sourceFieldId = isset($argv[1]) ? (int) $argv[1] : 0;
if (!$sourceFieldId) {
    fwrite(STDERR, "Missing field id\n");
    exit(1);
}

$moduleModel = new Settings_Picklist_Module_Model();
$moduleModel->alignLinkedPicklistFields($sourceFieldId);
echo "ok\n";
