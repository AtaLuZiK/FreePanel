<?php
defined('BASE_PATH') || exit('No direct script access allowed');

/**
 * Logger - Abstract-Logger-Class
 * 
 * We're using the syslog constants for all the loggers (partly implemented)
 * LOG_EMERG        system is unusable
 * LOG_ALERT       action must be taken immediately
 * LOG_CRIT         critical conditions
 * LOG_ERR         error conditions
 * LOG_WARNING     warning conditions
 * LOG_NOTICE       normal, but significant, condition
 * LOG_INFO         informational message
 * LOG_DEBUG       debug-level message
 * 
 * Configure:
 * 'LOG' => [
 *     'PROVIDER' => 'MySql',
 *     'LEVEL' => LOG_NOTICE,
 * ]
 * 
 */

abstract class Logger {
    /**
     * Syslogger Objects Array
     * @var loggers
     */
    static private $loggers = array();
    
    private $name;
    private $level = LOG_DEBUG;
    
    /**
     * Get a logger from specified name
     * @param string $name the logger name, 
     * @return Returns a Logger from cached.
     */
    static public function getInstance($name)
    {
        if (!isset(self::$loggers[$name])) {
            $provider = get_config('LOG.PROVIDER', 'Mysql') . 'Logger';
            self::$loggers[$name] = new $provider($name);
        }
        return self::$loggers[$name];
    }
    
    
    static public function toLevelStr($level)
    {
        switch($level) {
            case LOG_INFO:
                return 'information';
            case LOG_NOTICE:
                return 'notice';
            case LOG_WARNING:
                return 'warning';
            case LOG_ERR:
                return 'error';
            case LOG_CRIT:
                return 'critical';
            case LOG_DEBUG:
                return 'debug';
            default:
                return 'unknown';
        }
    }
    
    
    protected function __construct($name)
    {
        $this->name = $name;
        $this->setLevel(get_config('LOG.LEVEL', LOG_WARNING));
    }
    
    
    public function getName()
    {
        return $this->name;
    }
    
    
    /**
     * logs a given text
     * @param int $level
     * @param string $text
     */
    final public function log($level, $text)
    {
        if ($this->getEffectiveLevel() < $level) {
            return;
        }
        $this->onLog($level, $text);
    }
    
    
    /**
     * Return the assigned level value.
     */
    public function getEffectiveLevel()
    {
        return $this->level;
    }
    
    
    public function setLevel($level)
    {
        $this->level = $level;
    }
    
    /**
     * Implements this method to write log anywhere.
     * @param int $level
     * @param string $text
     */
    abstract public function onLog($level, $text);

}
