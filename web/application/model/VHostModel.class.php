<?php
defined('BASE_PATH') || exit('No direct script access allowed');

class VHostModel extends \system\Model
{
    const DOMAIN = 0x0001;
    const ALIAS = 0x0002;
    const DOCUMENTROOT = 0x0004;
    const ID = 0x10000;
    const CREATETIME = 0x20000;
    protected $id;
    protected $domain;
    protected $alias; // string array
    protected $documentRoot;
    
    protected function __construct()
    {
        
    }
    
    
    public function save()
    {
        
    }
    
    
    /**
     * get all virtual host configures
     * @param string $columns Querying columns, allowed one or more followings:
     * 
     * Value                    | Description
     * ------------------------ | -----------
     * VHostModel::ID           | Result contains id in database
     * VHostModel::DOMAIN       | Result contains domain
     * VHostModel::ALIAS        | Result contains alias
     * VHostModel::DOCUMENTROOT | Result contains document directory
     * VHostModel::CREATETIME   | Result contains create in database
     * 
     * @return array Returns an array contains all virtual host
     * 
     * Example:
     * @code {.php}
     * <?php
     * $vhosts = VHostModel::getAll(VHostModel::DOMAIN | VHostModel::DOCUMENTROOT);
     * foreach ($vhosts as $domain => $vhost) {
     *     echo 'Domain: ' . $domain . ', DocumentRoot: ' . $vhost['documentRoot'];
     * }
     * @endcode
     * 
     * @see @ref fpdrp
     */
    static function getAll($columns)
    {
        $fields = array();
        if ($columns & self::ID) {
            $fields[] = '`id`';
        }
        if ($columns & self::CREATETIME) {
            $fields[] = '`create_time`';
        }
        
        $dbResult = null;
        if (!empty($fields)) {
            $fields = implode(',', $fields);
            $stmt = Database::prepare("SELECT $fields FROM `website`");
            Database::pexecute($stmt);
            $dbResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $fpd = new FreePaneld(get_config('FREEPANELD.HOSTNAME'), get_config('FREEPANELD.PORT'));
        if (!$fpd->connect()) {
            throw new Exception('Can not connect to freepaneld');
        }
        $fpd->getHost(null, $columns);
        
        return $dbResult;
    }
}
