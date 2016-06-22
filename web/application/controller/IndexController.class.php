<?php
defined('BASE_PATH') || exit('No direct script access allowed');

class IndexController extends \system\TemplateController
{
    public function _empty($name)
    {
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
}
