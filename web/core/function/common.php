<?php
defined('BASE_PATH') || exit('No direct script access allowed');

function starts_with($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}


function ends_with($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}


/**
 * load and render the template, this function not trigger redirect
 * @param string $templateName
 */
function load_template($templateName)
{
    $template = new Template();
    $template->show($templateName);
}


function get_config($name = null, $default = null)
{
    return isset($GLOBALS['theApp']) ? $GLOBALS['theApp']->getConfig($name, $default) : $default;
}


function get_request_parameter($name, $default = null, $filters = null)
{
    return $GLOBALS['theApp']->getRequestParameter($name, $default, $filters);
}


function make_url($dest = '', $endsWith = '', $scheme = false)
{
    return \system\Url::to($dest, $endsWith, $scheme);
}


/**
 * Concatenates two strings that represent properly formed paths into one path; alos concatenates any relative path elements.
 * @param string $path A string contains the first path.
 * @param string $more A string contains the second path. This value can be null
 * @return string The combined path string. If this function does not return successfully, return null.
 * @remark The file path should be in correct form that represents the file name part of the path. If the directory path ends
 * with a (back)slash, the (back)slash will be maintained.
 */
function path_combine($path, $more)
{
    if (!ends_with($path, '/') && !ends_with($path, '\\')) {
        $path = $path . DIRECTORY_SEPARATOR;
    }
    $path = $path . ltrim(ltrim($more, '\\'), '/');
    return realpath($path);
}
