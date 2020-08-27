<?php defined('SYSPATH') or die('No direct script access.');


class Controller_Admin_Home extends Controller_Template {

	public function before()
	{
		parent::before();
		$this->checkauth('allow_view_admin');

	}

	public function action_index()
	{

		//$this->template->set_filename('smarty3:admin/index');
	}

	
}