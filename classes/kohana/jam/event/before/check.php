<?php defined('SYSPATH') OR die('No direct script access.');

interface Kohana_Jam_Event_Before_Check {

	public function before_check(Jam_Model $model, Jam_Validation $validation, $new_item);

}