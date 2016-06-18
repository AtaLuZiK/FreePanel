<?php
defined('BASE_PATH') || exit('No direct script access allowed');

class PhpController extends \system\TemplateController
{
    public function info()
    {
        $this->display('phpinfo');
    }
}
