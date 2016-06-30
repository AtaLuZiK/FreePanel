<?php
defined('BASE_PATH') || exit('No direct script access allowed');

class SiteController extends AuthorizedController
{
    public function _empty($name)
    {
        if ($name == 'list') {
            $this->index();
        } else {
            parent::_empty($name);
        }
    }
    
    
    public function index()
    {
        $vhosts = VHostModel::getAll(VHostModel::ID | VHostModel::DOMAIN | VHostModel::CREATETIME);
        var_dump($vhosts);die();
        $this->display('site/list');
    }
    
    
    public function create()
    {
        $this->display('site/create');
    }
    
    
    public function edit()
    {
        $domainFieldName = 'domain';
        if (IS_POST) {
            $id = get_request_parameter('request.id');
            $domain = get_request_parameter('post.domain');
            $alias = get_request_parameter('post.alias');
            $documentRoot = get_request_parameter('post.documentRoot');
            if (isset($_POST['createFTP'])) {
                $ftpUsername = get_request_parameter('post.ftpUsername');
                $ftpPassword = get_request_parameter('post.ftpPassword');
            }
            if (isset($_POST['createMysql'])) {
                $ftpUsername = get_request_parameter('post.mysqlUsername');
                $ftpPassword = get_request_parameter('post.mysqlPassword');
            }
            
            $fpd = new FreePaneld(get_config('FREEPANELD.HOSTNAME'), get_config('FREEPANELD.PORT'));
            if (!$fpd->connect()) {
                $this->responseJson([
                    'success' => false,
                    'message' => '无法连接到freepaneld，请确认配置信息。'
                ]);
            }
            
            // checks
            $domain_check_stmt = Database::prepare("SELECT `id`, `domain` FROM `website` WHERE `domain` = :domain");
            $domain_check = Database::pexecute_first($domain_check_stmt, array('domain' => strtolower($domain)));
            if ($domain_check) {
                $this->responseJson([
                    'success' => false,
                    'message' => 'Domain exists.',
                ]);
            }
            
            $result = $fpd->createHost($domain, [
                'DOCUMENT_ROOT' => $documentRoot,
                'ALIAS' => $alias,
            ]);
            if ($result !== true) {
                $this->responseJson([
                    'success' => false,
                    'message' => $result,
                ]);
            }
            $ins_stmt = Database::prepare("INSERT INTO `website` SET `domain` = :domain");
            Database::pexecute($ins_stmt, array('domain' => $domain));
            $logger = Logger::getInstance($_SESSION['userinfo']['username']);
            $logger->log(LOG_INFO, "added host '" . $domain . "'");
            $this->responseJson([
                'success' => true,
            ]);
        } else {
            
        }
    }
}
