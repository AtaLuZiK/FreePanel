<?php
defined('BASE_PATH') || exit('No direct script access allowed');

class IndexController extends \system\TemplateController
{
    public function _empty($name)
    {
        $fpServer = 'http://' . get_config('FREEPANELD.HOSTNAME') . ':' . get_config('FREEPANELD.PORT');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fpServer . '/system');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($statusCode != 200) {
            $this->assign('redirect_url', \system\Url::to('System/settings'));
            $this->assign('message', '无法连接到freepaneld，请确认配置信息。');
            $this->display('error');
        } else {
            $result = json_decode($response);
            $this->assign('freepaneld', $result->freepaneld);
            $this->assign('partitions', $result->partitions);
            $this->display('index');
        }
    }
}
