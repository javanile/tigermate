<?php

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
