<?php defined('SYSPATH') OR die('No direct script access.');

interface Kohana_Jam_Event_Before_Save {

	public function before_save(Jam_Model $model, $value);

}