<?php defined('SYSPATH') OR die('No direct script access.');

interface Kohana_Jam_Event_After_Delete {

	public function after_delete(Jam_Model $model, $value);

}