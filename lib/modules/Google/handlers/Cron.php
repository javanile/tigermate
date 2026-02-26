<?php

class Google_Cron_Handler
{
    public static function runSchedule()
    {
        echo "Running Google Sync...\n";

        $allUsers = getAllUserName();

        foreach ($allUsers as $user) {
            var_dump($user);
        }
    }
}
