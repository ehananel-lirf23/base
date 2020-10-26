<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Auth extends Controller_Template {

	public function action_login_query()
	{
		$username = $this->request->post('username');
		$password = $this->request->post('password');
		//$captcha = $this->request->post('captcha'); //run it with bootstrap.php
		
		//if (!Captcha::valid($captcha))
		//	return $this->failure('login.captcha', FALSE);
		
		$result = Auth::instance()->login($username, $password);
		if ($result === TRUE) //登录成功
		{	$user = Model::instance('member')->get(Auth::instance()->get_user());
			$referrer = Cookie::get('referrer',NULL);
			stripos($referrer,'auth') !== FALSE && $referrer = Route::url('default');
			if ($user['gid'] == Model_Group::GROUP_ADMIN)
				return $this->success('login.success', URL::site('admin',NULL,FALSE));
			else
				return $this->success('login.success', $referrer);
		}
		elseif ($result === FALSE) //登录失败
		{
			return $this->error('login.failure');
		}
	}

	public function action_login()
	{
		Auth::instance()->logout();
		$referrer = $this->request->referrer();
		Cookie::set('referrer', $referrer);
		//.....
		
		$this->template->set_filename('smarty3:login');
	}

	public function action_logout()
	{
		Auth::instance()->logout();
		Cookie::delete('referrer');
		return $this->success('logout.success', Route::url('default'));
	}
	
	public function action_index()
	{
		$this->action_login();
	}
}