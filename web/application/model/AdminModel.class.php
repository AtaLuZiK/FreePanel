<?php
defined('BASE_PATH') || exit('No direct script access allowed');

class AdminModel extends \system\Model{
    
    const SECRET_SALT = 'freepanel';    // TODO: This salt should be set by user in installing.
    /**
     * 
     * @param string $username
     * @param string $password MD5 hash of the password in plain text
     * @return Returns user information if function success, otherwise returns false
     * @mark You needn't filter illegal  characters, function do it.
     */
    public function validateLogin($username, $password)
    {
        if (empty($username) || empty($password))
            return false;
        $password = md5($password . self::SECRET_SALT);
        // we don't filter illegal parameters, PDO do it
        $stmt = Database::prepare("SELECT * FROM `admin` WHERE `username`= :username");
        Database::pexecute($stmt, array('username' => $username));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['username'] == $username && $row['password'] == $password) {
            return $row;
        } else {
            return false;
        }
    }
    
}
