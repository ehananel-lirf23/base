<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Controller_Template extends Controller_Base {

	// Set the name of the page layout template to use
	public $template = 'smarty3:blank';
	public $template_tips = 'smarty3:tips';

	public $user = array();

	public $group = array();

	public $fields = array();

	public $site = array();

	public $model = array();

	public function before()
	{
		parent::before();

		Auth::instance()->online(); //保持在线
		
		$this->site = (array)Kohana::$config->load('site');

		$this->model = array(
			'member' => Model::instance('member'),
			'fields' => Model::instance('fields'),
			'group' => Model::instance('group'),
		);

		$uid = Auth::instance()->get_user();
		$this->user = $this->model['member']->get($uid);

		if (empty($this->user))
			$this->user = array('uid' => $uid,'realname' => 'Guest');

		$this->group = $this->model['group']->get_auth($uid);
		$this->fields = $this->model['fields']->get_fields_list();

		
		if ($this->template instanceof View)
		{
			$this->template->_site = $this->site;
			$this->template->_fields = $this->fields;
			$this->template->_user = $this->user;
			$this->template->_group = $this->group;
		}

		if ($this->request->method() == 'POST' && !$this->check_referrer())
			return $this->error_referrer();

		//Cron
		Model_Cron::run();

	}

	public function after()
	{
		if ($this->template instanceof View)
		{
			$this->template->_site = $this->site;
			$this->template->_fields = $this->fields;
			$this->template->_user = $this->user;
			$this->template->_group = $this->group;
		}

		parent::after();
	}

	protected function checkauth($auth = NULL, $auto_check = TRUE) //不传参数，则只检查是否登录
	{
		if ($auto_check)
		{
			if ($auth === NULL)
			{
				if (empty($this->user['uid']))
					return $this->failure_unlogin();
			}
			elseif (!$this->group[$auth])
			{
				if (empty($this->user['uid']))
					return $this->failure_unlogin();
				else
					return $this->failure_auth();
			}
		}
		
		return $this->group[$auth];
	}

	protected function set_subtitle($sub_key, $detail = NULL)
	{
		$subtitle = empty($sub_key) ? NULL : Arr::path($this->site['subtitle'], $sub_key);
		$this->site['subtitle'] = !empty($subtitle) ? $subtitle : NULL;
		$this->site['detail'] = $detail;
		$this->template->_site = $this->site;
	}

}
