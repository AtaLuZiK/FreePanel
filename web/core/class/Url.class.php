<?php
namespace system;
defined('BASE_PATH') || exit('No direct script access allowed');

class Url
{
    /**
     * 
     * @param string $dest
     * @param boolean/string $scheme
     * @example
     * Url::to('action');
     * Url::to('controller/action', true);
     */
    public static function to($dest = '', $endsWith = '', $scheme = false)
    {
        $dest = ltrim($dest, '/');
        if (empty($dest)) {
            $dest = $_SERVER['REQUEST_URI'];
        } else {
            if (strpos($dest, '/') === false) {
                $dest = CONTROLLER_NAME . '/' .$dest;
            }
            $dest = "/$dest";
        }
        
        $dest = ENTRY_URL . $dest . $endsWith;
        
        if (is_bool($scheme)) {
            return $scheme ? REQUEST_SCHEME . $dest : $dest;
        }
        return $scheme . ':' . $dest;
    }
}
