<?php defined('SYSPATH') OR die('No direct access allowed.');

class Auth_Member extends Auth {

	protected function _login($username, $password, $remember)
	{

		$password = $this->hash_password($username, $password);
		$uid = Model::instance('member')->check($username, $password);
		!empty($uid) && $this->complete_login($uid);

		return !empty($uid);
	}

	public function make_signature($username, $uid)
	{
		$password = $uid;
		$data = array('username' => $username, 'password' => $password, 'timeline' => time());

		return Encrypt::instance()->encode(serialize($data));
	}

	public function resolve_signature($signature)
	{
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