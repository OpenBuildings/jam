<?php defined('SYSPATH') OR die('No direct script access.');

interface Kohana_Jam_Event_After_Check {

	public function after_check(Jam_Model $model, Jam_Validation $validation, $new_item);

}