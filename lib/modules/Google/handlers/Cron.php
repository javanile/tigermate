<?php

class Google_Cron_Handler
{
    public static function runSchedule()
    {
        global $current_user;
        echo "Running Google Sync...\n";

        $tempCurrentUser = $current_user;
        $allUsers = getAllUserName();
        foreach ($allUsers as $userId => $userLabel) {
            echo "Syncing for user: $userLabel (ID: $userId)\n";
            $current_user = Users_Record_Model::getInstanceById($userId, 'Users');;
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
