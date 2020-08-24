<?php defined('SYSPATH') or die('No direct script access.');

class Model_Cron extends Model_Database {


	public function __construct()
	{

	}

	public static function run()
	{
		$class = Model::instance('Cron');
		
	}

}