<?php defined('SYSPATH') OR die('No direct script access.');

interface Kohana_Jam_Event_Before_Delete {

	public function before_delete(Jam_Model $model, $value);

}