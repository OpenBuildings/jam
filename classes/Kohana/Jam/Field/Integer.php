<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles integer data-types
 *
 * @package    Jam
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Field_Integer extends Jam_Field {

	/**
	 * @var  int  default value is 0, per the SQL standard
	 */
	public $default = 0;

	/**
	 * Converts the value to an integer.
	 *
	 * @param   mixed  $value
	 * @return  int
	 */
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		list($value, $return) = $this->_default($model, $value);

		if ( ! $return AND ! $value instanceof Database_Expression)
		{
			$value = (int) $value;
		}

		return $value;
	}
	
	/**
	 * Like Jam_Field::_default, but it won't convert 0 and 0.0 to empty_value
	 *
	 * @param  Jam_Validated $model
	 * @param  mixed $value
	 * @return array 1st element the converted value; 2nd element a boolean
	 * indicating if the value should not be processed further
	 */
	protected function _default(Jam_Validated $model, $value)
	{
		$return = FALSE;

		$value = $this->run_filters($model, $value);

		// Convert empty values to NULL, if needed
		if ($this->convert_empty AND empty($value) AND $value !== 0 AND $value !== 0.0 AND $value !== "0" AND $value !== "0.0")
		{
			$value  = $this->empty_value;
			$return = TRUE;
		}

		// Allow NULL values to pass through untouched by the field
		if ($this->allow_null AND $value === NULL)
		{
			$value  = NULL;
			$return = TRUE;
		}

		return array($value, $return);
	}

} // End Kohana_Jam_Field_Integer
