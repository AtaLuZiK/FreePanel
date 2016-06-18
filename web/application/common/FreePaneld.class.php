<?php
defined('BASE_PATH') || exit('No direct script access allowed');

class FreePaneld
{
    private $api;
    
    public function __construct($hostname, $port)
    {
        $this->api = "http://$hostname:$port";
    }
    
    /**
     * @return bool Returns true if connect successful, otherwise returns false
     */
    public function connect()
    {
        return $this->getSystemInformation() != false;
    }
    
    
    public function getSystemInformation()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$this->api/system");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($statusCode != 200) {
            return false;
        }
        $result = json_decode($response);
        return $result;
    }
    
    
    public function setFreePanel($port)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$this->api/settings");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['type' => 'freepanel', 'port' => $port]));
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($statusCode != 200) {
            return false;
        }
        //$result = json_decode($response);
        return $response;
    }
}
