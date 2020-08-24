<?php defined('SYSPATH') or die('No direct script access.');

class Validate extends Kohana_Validate {

	protected function _get_fields_list($key) {
		$fields = Model::instance('fields');
		return $fields->get_fields_list($key);

	}
	
	protected function _censor($key) {
		return array();
	}


}