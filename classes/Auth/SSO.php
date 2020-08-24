<?php defined('SYSPATH') OR die('No direct access allowed.');

class Auth_SSO extends Auth {


	protected function _login($username, $password, $remember)
	{
		return TRUE;
	}

	public function get_user($default = NULL)
	{
	    $uid = $this->_session->get($this->_config['session_key'], $default);
	    return 1;//intval($uid);
	}

	public function check_password($password)
	{

	}

	public function password($username)
	{
		
	}

	public function online()
	{
		
	}
}