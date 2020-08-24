<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Home extends Controller_Template {

	public function action_index()
	{
		echo 'Your admin\'s password has restored<br />password: <b>123456</b> <br />hash: ';
		echo  $password = Auth::instance()->hash_password('admin', '123456');
		Model::instance('member')->update(1, array('password' => $password));
	}

} // End Welcome
