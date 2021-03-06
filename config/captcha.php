<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package  Captcha
 *
 * Captcha configuration is defined in groups which allows you to easily switch
 * between different Captcha settings for different forms on your website.
 * Note: all groups inherit and overwrite the default group.
 *
 * Group Options:
 *  type		Captcha type, e.g. basic, alpha, word, math, riddle
 *  width		Width of the Captcha image
 *  height		Height of the Captcha image
 *  complexity	Difficulty level (0-10), usage depends on chosen style
 *  background	Path to background image file
 *  fontpath	Path to font folder
 *  fonts		Font files
 *  promote		Valid response count threshold to promote user (FALSE to disable)
 */

return array(
	'default' => array(
		'style'      	=> 'alpha',
		'width'      	=> 90,
		'height'     	=> 45,
		'complexity' 	=> 4,
		'background' 	=> '',
		'promote'    	=> FALSE, //用户错误次数
	),
	
);
