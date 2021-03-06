<?php
defined('BASE_PATH') || exit('No direct script access allowed');

class SystemController extends AuthorizedController
{
    public function settings()
    {
        $this->display('system/settings');
    }
    
    public function save()
    {
        $type = get_request_parameter('get.type');
        if (!empty($type)) {
            $type[0] = chr(ord($type[0]) & 0x5F);
            $func = 'save' . $type;
            if (method_exists($this, $func)) {
                exit(call_user_func_array(array($this, $func), array()));
            }
        }
        $this->responseJson([
            'success' => false,
            'message' => 'An invalid argument was supplied.'
        ]);
    }
    
    
    public function logs()
    {
        $pageSize = 50;
        $page = get_request_parameter('page', 1, 'intval');
        $result_stmt = Database::query('SELECT COUNT(1) FROM `syslog`');
        $totalRow = intval($result_stmt->fetch(PDO::FETCH_COLUMN));
        $sql = 'SELECT * FROM `syslog` WHERE id <= (SELECT id FROM `syslog` ORDER BY id desc LIMIT ' . ($page - 1) * $pageSize . ", 1) ORDER BY id desc LIMIT $pageSize";
        $result_stmt = Database::query($sql);
        $result = $result_stmt->fetchAll();
        $this->assign('logs', $result);
        $this->assign('totalPage', ceil($totalRow / $pageSize));
        $this->assign('page', $page);
        $this->display('system/logs');
    }
    
    
    private function saveFreepaneld()
    {
        $hostname = get_request_parameter('post.hostname');
        $port = get_request_parameter('post.port');
        if (empty($hostname) || empty($port)) {
            $this->responseJson([
                'success' => false,
                'message' => 'hostname and port cannot be empty.',
            ]);
        }
        // test connect avliable
        $fpd = new FreePaneld($hostname, $port);
        if ($fpd->connect()) {
            // save new configure to file
            $filename = APP_PATH . 'config' . DIRECTORY_SEPARATOR . 'freepaneld.php';
            $file = fopen($filename, 'w');
            fwrite($file, '<?php' . PHP_EOL);
            fwrite($file, 'return [' . PHP_EOL);
            fwrite($file, "'HOSTNAME' => '$hostname'," . PHP_EOL);
            fwrite($file, "'PORT' => '$port'," . PHP_EOL);
            fwrite($file, '];' . PHP_EOL);
            fclose($file);
            
            $this->responseJson([
                'success' => true,
            ]);
        } else {
            $this->responseJson([
                'success' => false,
                'message' => 'could not connect to freepaned',
            ]);
        }
    }
    
    
    private function saveFreepanel()
    {
        $oldPort = get_request_parameter('server.SERVER_PORT');
        $newPort = get_request_parameter('post.port');
        $fpd = $this->getFreePaneld(array($this, 'failedToConnectFreePaneld'));
        $result = $fpd->setFreePanel($newPort);
        if ($result == 'success') {
            $redirect = str_replace($oldPort, $newPort, \system\Url::to('System/settings'));
            $this->responseJson([
                'success' => true,
                'redirect' => $redirect,
            ]);
        } else {
            $this->responseJson([
                'success' => false,
                'message' => $result,
            ]);
        }
    }
    
    
    private function failedToConnectFreePaneld()
    {
        $this->responseJson([
            'success' => false,
            'message' => 'could not connect to freepaneld',
        ]);
    }
    
    
    private function getFreePaneld($failedCallback = NULL)
    {
        $fpd = new FreePaneld(get_config('FREEPANELD.HOSTNAME'), get_config('FREEPANELD.PORT'));
        if (!$fpd->connect()) {
            if (isset($failedCallback)) {
                exit(call_user_func_array($failedCallback, []));
            }
        }
        return $fpd;
    }
}
