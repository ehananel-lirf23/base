<?php defined('SYSPATH') OR die('No direct access allowed.');

class Auth_Member extends Auth {

	protected function _login($username, $password, $remember)
	{

		$password = $this->hash_password($username, $password);
		$uid = Model::instance('member')->check($username, $password);
		if (!empty($uid))
		{
			$this->complete_login($uid);
			$remember === TRUE && $remember = $this->_config['lifetime'];
			!empty($remember) && Cookie::set($this->_config['session_key'], $this->make_signature($username, $password), $remember);
		}
		return !empty($uid);
	}

	public function logout($destroy = FALSE, $logout_all = FALSE)
	{
		//destory cookie
		Cookie::delete($this->_config['session_key']);
		return parent::logout($destory, $logout_all);
	}

	public function make_signature($username, $password)
	{
		$data = array('username' => $username, 'password' => $password, 'timeline' => time());
		return Encrypt::instance()->encode(serialize($data));
	}

	public function resolve_signature($signature)
	{
		if (empty($signature)) return NULL;
		$str = Encrypt::instance()->decode($signature);
		return unserialize($str);
	}

	public function hash_password($username, $password = '')
	{
		return $this->hash(strtolower($username). $this->_config['hash_key'] .$password);
	}

	public function get_user($default = NULL)
	{
		$uid = $this->_session->get($this->_config['session_key'], $default);
		if (empty($uid))
		{
			$signature = Cookie::get($this->_config['session_key']);
			if (!empty($signature))
			{
				$data = $this->resolve_signature($signature);
				if (!empty($data) && !empty($data['username']))
				{
					$uid = Model::instance('member')->check($data['username'], $data['password']);
					!empty($uid) && $this->complete_login($uid); // relogin
				}
			}
		}
		return intval($uid);
	}

	public function check_password($password)
	{
		$uid = $this->get_user();
		$user = Model::instance('member')->get($uid);

		if (empty($uid) || empty($user))
			return FALSE;

		return ($this->hash_password($user['username'], $password) === $user['password']);
	}

	public function password($username)
	{
		$user = Model::instance('member')->get_byusername($username);

		return empty($user) ? NULL : $user['password'];
	}

	public function online($uid = 0)
	{
		empty($uid) && $uid = $this->get_user();
		return Model::instance('member')->online($uid, $this->_config['online_expire'], TRUE);
	}

	public function is_online($uid = 0)
	{
		empty($uid) && $uid = $this->get_user();
		return Model::instance('member')->online($uid, $this->_config['online_expire']);
	}

}