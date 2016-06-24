<?php
defined('BASE_PATH') || exit('No direct script access allowed');

/**
 * Class Database
 *
 * Wrapper-class for PHP-PDO
 * 
 * @example Configures
 * 'DATABASE' => [
 *     'HOSTNAME' => 'localhost',
 *     'PORT' => 3306,
 *     'NAME' => 'name of database',
 *     'USERNAME' => 'username',
 *     'PASSWORD' => 'password',
 * ]
 */
class Database {

    /**
     * current database link
     *
     * @var object
     */
    private static $_link = null ;

    /**
     * indicator whether to use root-connection or not
     */
    private static $_needroot = false;

    /**
     * indicator which database-server we're on (not really used)
     */
    private static $_dbserver = 0;

    /**
     * used database-name
     */
    private static $_dbname = null;

    /**
     * sql-access data
     */
    private static $_needsqldata = false;
    private static $_sqldata = null;

    /**
     * Wrapper for PDOStatement::execute so we can catch the PDOException
     * and display the error nicely on the panel
     *
     * @param PDOStatement $stmt
     * @param array $params (optional)
     * @param bool $showerror suppress errordisplay (default true)
     */
    public static function pexecute(&$stmt, $params = null, $showerror = true)
    {
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            self::_showerror($e, $showerror);
        }
    }

    /**
     * Wrapper for PDOStatement::execute so we can catch the PDOException
     * and display the error nicely on the panel - also fetches the
     * result from the statement and returns the resulting array
     *
     * @param PDOStatement $stmt
     * @param array $params (optional)
     * @param bool $showerror suppress errordisplay (default true)
     *
     * @return array
     */
    public static function pexecute_first(&$stmt, $params = null, $showerror = true)
    {
        self::pexecute($stmt, $params, $showerror);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * returns the number of found rows of the last select query
     *
     * @return int
     */
    public static function num_rows()
    {
        return Database::query("SELECT FOUND_ROWS()")->fetchColumn();
    }

    /**
     * returns the database-name which is used
     *
     * @return string
     */
    public static function getDbName()
    {
        return self::$_dbname;
    }

    /**
     * enabled the usage of a root-connection to the database
     * Note: must be called *before* any prepare/query/etc.
     * and should be called again with 'false'-parameter to resume
     * the 'normal' database-connection
     *
     * @param bool $needroot
     * @param int $dbserver optional
     */
    public static function needRoot($needroot = false, $dbserver = 0)
    {
        // force re-connecting to the db with corresponding user
        // and set the $dbserver (mostly to 0 = default)
        self::_setServer($dbserver);
        self::$_needroot = $needroot;
    }

    /**
     * enable the temporary access to sql-access data
     * note: if you want root-sqldata you need to
     * call needRoot(true) first. Also, this will
     * only give you the data ONCE as it disable itself
     * after the first access to the data
     *
     */
    public static function needSqlData()
    {
        self::$_needsqldata = true;
        self::$_sqldata = array();
        self::$_link = null;
        // we need a connection here because
        // if getSqlData() is called RIGHT after
        // this function and no "real" PDO
        // function was called, getDB() wasn't
        // involved and no data collected
        self::getDB();
    }

    /**
     * returns the sql-access data as array using indeces
     * 'user', 'passwd' and 'host'. Returns false if not enabled
     *
     * @return array|bool
     */
    public static function getSqlData()
    {
        $return = false;
        if (self::$_sqldata !== null
                && is_array(self::$_sqldata)
                && isset(self::$_sqldata['user'])
        ) {
            $return = self::$_sqldata;
            // automatically disable sql-data
            self::$_sqldata = null;
            self::$_needsqldata = false;
        }
        return $return;
    }

    /**
     * let's us interact with the PDO-Object by using static
     * call like "Database::function()"
     *
     * @param string $name
     * @param mixed $args
     *
     * @return mixed
     */
    public static function __callStatic($name, $args) {
        $callback = array(self::getDB(), $name);
        $result = null;
        try {
            $result = call_user_func_array($callback, $args );
        } catch (PDOException $e) {
            self::_showerror($e);
        }
        return $result;
    }

    /**
     * set the database-server (relevant for root-connection)
     *
     * @param int $dbserver
     */
    private static function _setServer($dbserver = 0)
    {
        self::$_dbserver = $dbserver;
        self::$_link = null;
    }

    /**
     * function that will be called on every static call
     * which connects to the database if necessary
     *
     * @param bool $root
     *
     * @return object
     */
    private static function getDB()
    {

        if (!extension_loaded('pdo') || in_array("mysql", PDO::getAvailableDrivers()) == false) {
            self::_showerror(new Exception("The php PDO extension or PDO-MySQL driver is not available"));
        }

        // do we got a connection already?
        if (self::$_link) {
            // return it
            return self::$_link;
        }
        
        $dbConfig = get_config('DATABASE');
        
        $caption = 'localhost';
        $user = $dbConfig['USERNAME'];
        $password = $dbConfig['PASSWORD'];
        $host = $dbConfig['HOSTNAME'];
        $socket = null;
        $port = isset($dbConfig['PORT']) ? $dbConfig['PORT'] : '3306';

        // save sql-access-data if needed
        if (self::$_needsqldata) {
            self::$_sqldata = array(
                    'user' => $user,
                    'passwd' => $password,
                    'host' => $host,
                    'port' => $port,
                    'socket' => $socket,
                    'db' => $dbConfig["NAME"],
                    'caption' => $caption
            );
        }

        // build up connection string
        $driver = 'mysql';
        $dsn = $driver.":";
        $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8');
        $attributes = array('ATTR_ERRMODE' => 'ERRMODE_EXCEPTION');

        $dbconf["dsn"] = array(
                'dbname' => $dbConfig["NAME"],
                'charset' => 'utf8'
        );

        $dbconf["dsn"]['host'] = $host;
        $dbconf["dsn"]['port'] = $port;

        self::$_dbname = $dbConfig["NAME"];

        // add options to dsn-string
        foreach ($dbconf["dsn"] as $k => $v) {
            $dsn .= $k."=".$v.";";
        }

        // clean up
        unset($dbconf);

        // try to connect
        try {
            self::$_link = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            self::_showerror($e);
        }

        // set attributes
        foreach ($attributes as $k => $v) {
            self::$_link->setAttribute(constant("PDO::".$k), constant("PDO::".$v));
        }

        // return PDO instance
        return self::$_link;
    }

    /**
     * display a nice error if it occurs and log everything
     *
     * @param PDOException $error
     * @param bool $showerror if set to false, the error will be logged but we go on
     */
    private static function _showerror($error, $showerror = true)
    {
        $dbConfig = get_config('DATABASE');
        // le format
        if (isset($dbConfig['USERNAME'])
            && isset($dbConfig['PASSWORD'])
            && (!isset($sql_root) || !is_array($sql_root))
        ) {
            $sql_root = array(0 => array(
                'caption' => 'Default', 
                'host' => $dbConfig['HOSTNAME'], 
                'user' => $dbConfig['USERNAME'], 
                'password' => $dbConfig['PASSWORD']));
        }

        $substitutions = array(
            $dbConfig['PASSWORD'] => 'DB_UNPRIV_PWD',
            $sql_root[0]['password'] => 'DB_ROOT_PWD',
        );

        // hide username/password in messages
        $error_message = $error->getMessage();
        $error_trace = $error->getTraceAsString();
        // error-message
        $error_message = self::_substitute($error_message, $substitutions);
        // error-trace
        $error_trace = self::_substitute($error_trace, $substitutions);

        if ($error->getCode() == 2003) {
            $error_message = "Unable to connect to database. Either the mysql-server is not running or your user/password is wrong.";
            $error_trace = "";
        }

        /**
         * log to a file, so we can actually ask people for the error
         */
        $logDir = APP_PATH . 'logs' . DIRECTORY_SEPARATOR;
        if (!file_exists($logDir)) {
            @mkdir($logDir, 0755);
        }

        /**
         * log error for reporting
        */
        $errid = substr(md5(microtime()), 5, 5);
        $err_file = $logDir . DIRECTORY_SEPARATOR.$errid . "_sql-error.log";
        $errlog = @fopen($err_file, 'w');
        @fwrite($errlog, "|CODE ".$error->getCode()."\n");
        @fwrite($errlog, "|MSG ".$error_message."\n");
        @fwrite($errlog, "|FILE ".$error->getFile()."\n");
        @fwrite($errlog, "|LINE ".$error->getLine()."\n");
        @fwrite($errlog, "|TRACE\n".$error_trace."\n");
        @fclose($errlog);

        if ($showerror) {
            die("We are sorry, but a MySQL - error occurred. The administrator may find more information in the syslog");
        }
    }

    /**
     * Substitutes patterns in content.
     *
     * @param string $content
     * @param array $substitutions
     * @param int $minLength
     * @return string
     */
    private static function _substitute($content, array $substitutions, $minLength = 6) {
        $replacements = array();

        foreach ($substitutions as $search => $replace) {
            $replacements = $replacements + self::_createShiftedSubstitutions($search, $replace, $minLength);
        }

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );

        return $content;
    }

    /**
     * Creates substitutions, shifted by length, e.g.
     *
     * _createShiftedSubstitutions('abcdefgh', 'value', 4):
     *   array(
     *     'abcdefgh' => 'value',
     *     'abcdefg' => 'value',
     *     'abcdef' => 'value',
     *     'abcde' => 'value',
     *     'abcd' => 'value',
     *   )
     *
     * @param string $search
     * @param string $replace
     * @param int $minLength
     * @return array
     */
    private static function _createShiftedSubstitutions($search, $replace, $minLength) {
        $substitutions = array();
        $length = strlen($search);

        if ($length > $minLength) {
            for ($shiftedLength = $length; $shiftedLength >= $minLength; $shiftedLength--) {
                $substitutions[substr($search, 0, $shiftedLength)] = $replace;
            }
        }

        return $substitutions;
    }
}
