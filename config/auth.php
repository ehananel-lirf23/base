<?php defined('SYSPATH') OR die('No direct access allowed.');

return array(

	'driver'       => 'Member',
	'hash_method'  => 'md5',
	'hash_key'     => '{$PROJECT_HASH}',
	'lifetime'     => 1209600,
	'online_expire' => 300, //在线时间过期
	'session_type' => Session::$default,
	'session_key'  => '{$PROJECT_NAME}_auth_user',

);
