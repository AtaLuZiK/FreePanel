<?php
namespace system;
defined('BASE_PATH') OR exit('No direct script access allowed');

class Controller
{
    public function _empty($name)
    {
        throw new Exception("$name action not found");
    }
    
    
    protected function responseJson($data, $handler = null, $options = 0)
    {
        header('Content-Type: application/json; charset=utf-8');
        $jsonStr = json_encode($data, $options);
        if (empty($handler) || !is_string($handler)) {
            exit($jsonStr);
        } else {
            exit("$handler($jsonStr);");
        }
    }
    
    
    protected function responseXml($data)
    {
        header('Content-Type: text/xml; charset=utf-8');
        exit(xml_encode($data));
    }
}
