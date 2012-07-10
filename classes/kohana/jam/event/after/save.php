<?php defined('SYSPATH') OR die('No direct script access.');

interface Kohana_Jam_Event_After_Save {

	public function after_save(Jam_Model $model, $value);

}