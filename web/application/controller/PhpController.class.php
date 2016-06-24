<?php
defined('BASE_PATH') || exit('No direct script access allowed');

class PhpController extends AuthorizedController
{
    public function info()
    {
        $this->display('phpinfo');
    }
}
