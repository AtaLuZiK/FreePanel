<?php
defined('BASE_PATH') || exit('No direct script access allowed');

class IndexController extends AuthorizedController
{
    
    public function _empty($name)
    {
        error_reporting(E_ALL ^ E_WARNING);
        $fpServer = 'http://' . get_config('FREEPANELD.HOSTNAME') . ':' . get_config('FREEPANELD.PORT');
        $fpd = new FreePaneld(get_config('FREEPANELD.HOSTNAME'), get_config('FREEPANELD.PORT'));
        if (!$fpd->connect()) {
            $this->assign('redirect_url', \system\Url::to('System/settings'));
            $this->assign('message', '无法连接到freepaneld，请确认配置信息。');
            $this->display('error');
        } else {
            $result = $fpd->getSystemInformation();
            $this->assign('freepaneld', $result->freepaneld);
            $this->assign('partitions', $result->partitions);
            $this->display('index');
        }
    }
    
    
    public function login()
    {
        if (IS_POST) {
            $loginname = get_request_parameter('post.username');
            $password = get_request_parameter('post.password');
            if (empty($loginname) || empty($password)) {
                exit(header('Location: ' . \system\Url::to('Index/login', '?msg=1')));
            }
            $remoteAddr = get_request_parameter('server.REMOTE_ADDR');
            $adminModel = new AdminModel();
            // TODO: add max login attempt
            if (($userinfo = $adminModel->validateLogin($loginname, $password)) == false) {
                $logger = Logger::getInstance($remoteAddr);
                $logger->log(LOG_WARNING, "Unknown user \"$loginname\" tried to login.");
                exit(header('Location: ' . \system\Url::to('/Index/login', '?msg=1')));
            } else {
                $session = [
                    'hash' => md5(uniqid(microtime(), 1)),
                    'userid' => $userinfo['id'],
                    'ipaddress' => $remoteAddr,
                    'useragent' => get_request_parameter('server.HTTP_USER_AGENT'),
                    'lastactivity' => time(),
                ];
                $_SESSION['userinfo'] = $session;
                exit(header('Location: ' . \system\Url::to('index')));
            }
        } else {
            $errno = get_request_parameter('get.msg');
            $message = null;
            if (isset($errno)) {
                switch (intval($errno)) {
                    case 1:
                        $message = [
                            'success' => false,
                            'message' => 'The username or password you typed in is wrong. Please try it again!'
                            ];
                        break;
                }
            }
            $this->assign('message', $message);
            $this->display('login');
        }
    }
    
    
    public function logout()
    {
        session_destroy();
        header('Location: ' . \system\Url::to('index'));
    }
}
