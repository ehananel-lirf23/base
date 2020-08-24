<?php defined('SYSPATH') OR die('No direct script access.');

return array(
	//'enabled' => TRUE, //生产环境中可以打开
	'appid' => '{$PROJECT_ID}',
	'secret' => '{$PROJECT_HASH}', //生产环境,本地环境请勿相同
);