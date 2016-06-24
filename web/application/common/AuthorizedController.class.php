<?php
defined('BASE_PATH') || exit('No direct script access allowed');

/**
 * 
 * @see IndexController::login
 */
class AuthorizedController extends \system\TemplateController
{
    public function _before($name)
    {
        $lifetime = 180;
        session_set_cookie_params($lifetime * 60);
        session_cache_expire($lifetime);
        session_start();
        if ($name !== 'login' && !isset($_SESSION['userinfo'])) {
            header('Location: ' . \system\Url::to('Index/login'));
        } else if ($name === 'login' && isset($_SESSION['userinfo'])) {
            header('Location: ' . \system\Url::to('Index'));
        }
    }
}
