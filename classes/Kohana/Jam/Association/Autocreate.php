<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Creates the record if it does not exist and is a string
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2013 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association_Autocreate extends Jam_Association_Belongsto {

	public $default_fields;

	public function set(Jam_Validated $model, $value, $is_changed)
	{
		if ($is_changed AND $value AND is_string($value) AND ! is_numeric($value))
		{
			$value = Jam::find_or_create($this->foreign_model, array(
				':name_key' => $value
			));

			if ($this->default_fields)
			{
				$value->set($this->default_fields);
			}
		}

		return parent::set($model, $value, $is_changed);
	}
}
