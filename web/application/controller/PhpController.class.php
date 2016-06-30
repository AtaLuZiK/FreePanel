<?php
defined('BASE_PATH') || exit('No direct script access allowed');

class PhpController extends AuthorizedController
{
    public function info()
    {
        $this->display('php/info');
    }
    
    public function settings()
    {
        $this->display('php/settings');
    }
    
    
    public function edit()
    {
        
    }
}
