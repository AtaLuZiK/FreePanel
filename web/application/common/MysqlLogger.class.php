<?php

/**
 * A logger based on Mysql
 * This class is dependent on Mysql avaliable and Database class
 */
class MysqlLogger extends Logger {
    
    public function onLog($level = LOG_NOTICE, $text = null)
    {
        $username = $this->getName();
        if (!isset($username) || $username == '') {
            $username = 'unknown';
        }

        $now = time();

        $stmt = Database::prepare("
                INSERT INTO `syslog` SET
                `type` = :type,
                `date` = :now,
                `user` = :user,
                `text` = :text"
        );

        $ins_data = array(
            'type' => $level,
            'now' => $now,
            'user' => $username,
            'text' => $text,
        );
        Database::pexecute($stmt, $ins_data);
    }
    
}
