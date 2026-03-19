<?php

class Google_Cron_Handler
{
    public static function runSchedule()
    {
        global $current_user;
        echo "Running Google Sync...\n";

        #ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

        $tempCurrentUser = $current_user;
        $allUsers = getAllUserName();
        foreach ($allUsers as $userId => $userLabel) {
            echo "Syncing for user: $userLabel (ID: $userId)\n";
            $current_user = new Users();
            $current_user->retrieveCurrentUserInfoFromFile($userId, 'Users');
            $currentUserModel = Users_Record_Model::getCurrentUserModel();
            if ($currentUserModel->getId() != $userId) {
                echo "ERROR: Failed to set current user to $userLabel (ID: $userId). Skipping sync for this user.\n";
                continue;
            }
            $params = array(
                'module' => 'Google',
                'view' => 'Sync',
            );
            $httpRequest = new Vtiger_Request($params, $params);
            $syncView = new Google_Sync_View();
            $syncView->process($httpRequest);

            echo "\n";
        }

        $current_user = $tempCurrentUser;
    }
}
