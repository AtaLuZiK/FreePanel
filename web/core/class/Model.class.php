<?php
namespace system;
defined('BASE_PATH') || exit('No direct script access allowed');

/**
 * Model is the base class for data models.
 * @author AtaLuZiK
 */
class Model
{
    protected $name;
    
    /*
     * field(
     *  type,
     *  isPrimary,
     *  isAutoIncrement
     *  canNull,
     *  default,
     *  sizeInBytes
     * )
     * 
     */
    protected $fields = array();
    protected $_fields = null;   // internal fields included details
    protected $db;
    protected $dbType;
    protected $data = array();
    protected $pk;
    protected $errors;
    protected static $database = null;
    
    const FIELD_TYPE_NULL = 0;
    const FIELD_TYPE_INTEGER = 1;
    const FIELD_TYPE_CHAR = 2;
    const FIELD_TYPE_BLOB = 3;
    const FIELD_TYPE_REAL = 4;
    
    
    public function __construct()
    {
        $this->getModelName();
        $dbConfig = get_config('DATABASE');
        $this->dbType = strtolower($dbConfig['TYPE']);
        $this->db = self::getDatabase();
        $this->getFieldsType();
    }
    
    
    public function getModelName()
    {
        if (empty($this->name)) {
            $name = strtolower(substr(get_class($this), 0, -strlen('Model')));
            if ($pos = strrpos($name, '\\')) {
                $this->name = substr($name, $pos + 1);
            } else {
                $this->name = $name;
            }
        }
        return $this->name;
    }
    
    
    /**
     * 
     * @param array $data
     */
    public function create($data = null)
    {
        $this->_fields = self::getFieldsType();
        
        // create from request
        if (!isset($data)) {
            $data = get_request_parameter(IS_POST ? 'post.' : 'get.');
        }
        $model = array_fill_keys(array_keys($this->_fields), null);
        $data = array_merge($model, array_intersect_key($data, $model));
        $validated = $this->validateData($data);
        $this->data = $data;
        return $validated ? $this->data : $validated;
    }
    
    
    /**
     * @deprecated
     * @param string $columns
     * @param string $condition
     * @return multitype:unknown
     */
    public function select($columns = null, $condition = null)
    {
        if (empty($columns)) {
            $columns = '*';
        }
        if (is_string($condition)) {
            $query = 'SELECT * FROM ' . $this->name . ' WHERE ' . $condition;
            $result = $this->db->query($query)->fetchAll();
        } else {
            $result = $this->db->select($this->name, $columns, $condition);
        }
        $className = get_class($this);
        $models = array();
        foreach ($result as $row) {
            $model = new $className();
            $model->create($row);
            $models[] = $model;
        }
        return $models;
    }
    
    
    public function save()
    {
        $pk = $this->pk;
        if (!empty($pk) && !empty($this->data[$pk])) {
            return $this->db->update($this->name, $this->data, [ $this->pk => $this->data[$pk] ]) != 0;
        } else {
            if (empty($this->data[$pk]))
                $this->data[$pk] = null;    // avoid pk equals zero
            $insertId = $this->db->insert($this->name, $this->data);
            if (!empty($pk) && $this->_fields[$pk]['isAutoInc']) {
                $this->data[$pk] = $insertId;
            }
            return true;
        }
        return false;
    }
    
    
    private function validateData(&$data)
    {
        $error = array();
        if (!is_array($data)) {
            $error[] = 'Empty data';
            return false;
        }
        
        foreach ($this->_fields as $field => $attributes) {
            if ($attributes['isPrimary']) {
                if (!isset($this->pk)) {
                    $this->pk = $field;
                } else {
                    die('Reset primary key');
                }
                if (!isset($data[$field]) && $attributes['isAutoInc']) {
                    continue;
                }
            }
            
            if (!$attributes['isCanNull'] && !isset($data[$field])) {
                if (empty($attributes['default'])) {
                    $error[] = "Field \"$field\" can not be null";
                } else {
                    $data[$field] = $attributes['default'];
                }
            } else if (!isset($data[$field]) && $attributes['isCanNull']) {
                continue;
            }
            switch ($attributes['type']) {
                case self::FIELD_TYPE_INTEGER:
                    $data[$field] = intval($data[$field]);
                    break;
                case self::FIELD_TYPE_CHAR:
                    $data[$field] = strval($data[$field]);
                    break;
                case self::FIELD_TYPE_NULL:
                    $data[$field] = null;
                    break;
                case self::FIELD_TYPE_REAL:
                    $data[$field] = doubleval($data[$field]);
                    break;
                default:
                    throw new Exception();
            }
        }
        $this->errors = $error;
        return empty($error);
    }
    
    
    public function __get($name)
    {
        return $this->onFormat($name, $this->getRawData($name));
    }
    
    
    public function getRawData($name)
    {
        return array_key_exists($name, $this->data) ? $this->data[$name] : null;
    }
    
    
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
    
    
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }
    
    
    public function __unset($name)
    {
        unset($this->data[$name]);
    }
    
    
    /**
     * Returns a value indicating whether there is any error.
     * @return boolean Whether there is any error.
     */
    final public function hasErrors()
    {
        return !empty($this->errors);
    }
    
    
    /**
     * Returns the errors
     * @return array Empty array is returned if no error.
     * @example
     * [
     * 	'id' => [
     * 		'ID is required.',
     * 		'ID must contain only number.',
     * 	],
     * 	'username' => [
     * 		'Username is exists',
     * 	]
     * ]
     */
    public final function getErrors()
    {
        return $this->errors;
    }
    
    
    protected function onFormat($field, $value)
    {
        return $value;
    }
    
    
    protected static function &getDatabase()
    {
        if (!isset(self::$database)) {
            $dbConfig = get_config('DATABASE');
            $dbType = strtolower($dbConfig['TYPE']);
            switch($dbType) {
                case 'sqlite':
                    $dbConn = new \medoo([
                    'database_type' => 'sqlite',
                    'database_file' => $dbConfig['FILE']
                    ]);
                    break;
                default:
                    throw new Exception();
            }
            self::$database = array(
                'connection' => $dbConn,
                'type' => $dbType,
                'config' => $dbConfig
            );
        }
        return self::$database['connection'];
    }
    
    
    public static function getDatabaseType()
    {
        return self::$database['type'];
    }
    
    
    public static function getFieldsType($table = '')
    {
        $db = self::getDatabase();
        if (empty($table)) {
            $table = self::getStaticModelName();
        }
        // TODO: load from cache
        $dbType = self::getDatabaseType();
        switch ($dbType) {
            case 'sqlite':
                $result = $db->query("PRAGMA table_info('$table');")->fetchAll();
                $fields = array();
                foreach ($result as $field) {
                    $type = strtolower($field['type']);
                    if (strpos($field['type'], ' ')) {
                        list($type, $size) = explode(' ', $type, 2);
                    }
                    switch ($type) {
                        case 'integer':
                        case 'int':
                        case 'bigint':
                        case 'boolean':
                            $type = self::FIELD_TYPE_INTEGER;
                            break;
                        case 'varchar':
                        case 'text':
                        case 'char':
                            $type = self::FIELD_TYPE_CHAR;
                            break;
                        default:
                            die($type);
                    }
                    $isPrimary = $field['pk'] == 1;
                    $isAutoInc = false;
                    if ($isPrimary) {
                        $isAutoInc = $db->count('sqlite_sequence', [ 'name' => $table ]) != 0;
                    }
    
                    $fields[$field['name']] = array(
                        'name' => $field['name'],
                        'type' => $type,
                        'isCanNull' => $field['notnull'] == 0,
                        'default' => $field['dflt_value'],
                        'isPrimary' => $isPrimary,
                        'isAutoInc' => $isAutoInc
                    );
                }
                break;
            default:
                throw new Exception("Not supported $dbType");
        }
        // TODO: store in cache
        return $fields;
    }
    
    
    /**
     * 
     * @param mixed $columns
     * @param array $condition
     * @example
     * UserModel::fetch();	// return all record in User
     * PostModel::fetch(['id', 'title']);
     */
    public static function fetch($columns = null, $condition = null)
    {
        $modelName = self::getStaticModelName();
        if (empty($modelName)) {
            throw new Exception('DON\'T USE \\system\\Model DIRECTLY.');
        }
        $db = self::getDatabase();
        if (empty($columns)) {
            $columns = '*';
        }
        $result = $db->select($modelName, $columns, $condition);
        $className = $modelName . 'Model';
        $models = array();
        foreach ($result as $row) {
            $model = new $className();
            $model->create($row);
            $models[] = $model;
        }
        return $models;
    }
    
    
    /**
     * Get only one record from table
     * @param string/array $columns The target columns of data will be fetch.
     * @param array $condition The WHERE clause to filter records.
     * @return null if function failds.
     * @throws Exception
     */
    public static function selectOne($columns = null, $condition = null)
    {
        $modelName = self::getStaticModelName();
        if (empty($modelName)) {
            throw new Exception('DON\'T USE \\system\\Model DIRECTLY.');
        }
        $db = self::getDatabase();
        if (empty($columns)) {
            $columns = '*';
        }
        $result = $db->get($modelName, $columns, $condition);
        $className = $modelName . 'Model';
        $model = new $className();
        $model->create($result);
        return $model;
    }
    
    
    public static function update($data, $condition)
    {
        $db = self::getDatabase();
        return $db->update(self::getStaticModelName(), $data, $condition);
    }
    
    
    public static function delete($condition)
    {
        $db = self::getDatabase();
        return $db->delete(self::getStaticModelName(), $condition);
    }
    
    
    public static function getStaticModelName()
    {
        $name = strtolower(substr(get_called_class(), 0, -strlen('Model')));
        if ($pos = strrpos($name, '\\')) {
            $name = substr($name, $pos + 1);
        } else {
            $name = $name;
        }
        return $name;
    }
    
    
    /**
     * 
     * @param array/Model $models
     */
    public static function toArray($models) {
        if (is_array($models)) {
            $arrModel = array();
            foreach ($models as $model) {
                $arrModel[] = self::toArray($model);
            }
            return $arrModel;
        } else if (is_subclass_of($models, __CLASS__)) {
            return $models->data;
        }
        return null;
    }
}
