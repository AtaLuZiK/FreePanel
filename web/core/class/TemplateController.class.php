<?php
namespace system;
defined('BASE_PATH') or exit('No direct script access allowed');


class TemplateController extends Controller
{
	private $template;
	
	public function __construct()
	{
		$this->template = new Template();
	}
	
	
	final protected function display($templateName = '')
	{
		$this->template->show($templateName);
	}
	
	
	final protected function assign($name, $value)
	{
		$this->template->assign($name, $value);
	}
}
