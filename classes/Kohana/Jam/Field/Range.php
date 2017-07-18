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

	public $format;

	public function get(Jam_Validated $model, $value, $is_loaded)
	{
		if ( ! $value)
			return NULL;

		return ($value instanceof Jam_Range) ? $value : new Jam_Range($value, $this->format);
	}

	public function set(Jam_Validated $model, $value, $is_changed)
	{
		list($value, $return) = $this->_default($model, $value);

		if ( ! $return AND ! ($value instanceof Jam_Range))
		{
			$value = new Jam_Range($value, $this->format);
		}

		return $value;
	}

	public function convert(Jam_Validated $model, $value, $is_loaded)
	{
		if ($value === NULL)
			return NULL;

		return $value->__toString();
	}

	protected function _default(Jam_Validated $model, $value)
	{
		$return = FALSE;

		$value = $this->run_filters($model, $value);

		$min = NULL;
		$max = NULL;

		if (is_array($value))
		{
			$min = $value[0];
			$max = $value[1];
		}

		if (is_string($value) AND strstr($value, '|'))
		{
			$values = explode('|', $value);

			$min = isset($values[0]) ? $values[0] : NULL;
			$max = isset($values[1]) ? $values[1] : NULL;
		}

		// Convert empty values to NULL, if needed
		if ($this->convert_empty AND ($min == '' OR $max == ''))
		{
			$value  = $this->empty_value;
			$return = TRUE;
		}

		// Allow NULL values to pass through untouched by the field
		if ($this->allow_null AND ($min === NULL OR $max === NULL))
		{
			$value  = NULL;
			$return = TRUE;
		}

		return array($value, $return);
	}
}
