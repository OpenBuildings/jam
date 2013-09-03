<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles serialized data
 *
 * When set, the field attempts to unserialize the data into it's
 * actual PHP representation. When the model is saved, the value
 * is serialized back and saved as a string into the column.
 *
 * @package    Jam
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Field_Range extends Jam_Field {

	public function get(Jam_Validated $model, $value, $is_loaded)
	{
		return ($value instanceof Jam_Range) ? $value : new Jam_Range($value);
	}

	public function set(Jam_Validated $model, $value, $is_changed)
	{
		return ($value instanceof Jam_Range) ? $value : new Jam_Range($value);
	}

	public function convert(Jam_Validated $model, $value, $is_loaded)
	{
		if ($value === NULL)
			return NULL;

		return $value->__toString();
	}
}