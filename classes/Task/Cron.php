<?php defined('SYSPATH') or die('No direct script access.');
 
class Task_Cron extends Minion_Task
{
	protected $_options = array(

	);
 
	/**
	 * CronLLrun
	 *
	 * @return null
	 */
	protected function _execute(array $params)
	{
		Model_Cron::run();
		echo 'cron run success';
	}
}