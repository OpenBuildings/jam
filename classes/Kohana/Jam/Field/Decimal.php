<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Jam field for prices, money, amounts, costs and others:
 *  - set it like a string
 *  - validate it like a float
 *  - insert it (into the database) like a decimal
 *
 * @package   Jam
 * @category  Fields
 * @author    Haralan Dobrev <hkdobrev@gmail.com>
 * @copyright (c) 2013 OpenBuildings, Inc.
 * @license   http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Field_Decimal extends Jam_Field_String {

	public $default = NULL;

	public $allow_null = TRUE;

	public $convert_empty = TRUE;

	public $precision = 2;

	/**
	 * Cast to a string, preserving NULLs along the way.
	 *
	 * @param   mixed   $value
	 * @return  string
	 */
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		list($value) = $this->_default($model, $value);

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
		if ($this->convert_empty AND empty($value) AND $value !== 0 AND $value !== 0.0)
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

	/**
	 * Called just before saving.
	 *
	 * If $in_db, it is expected to return a value suitable for insertion
	 * into the database.
	 *
	 * @param   Jam_Model $model
	 * @param   mixed     $value
	 * @param   bool      $is_loaded
	 * @return  NULL|float
	 */
	public function convert(Jam_Validated $model, $value, $is_loaded)
	{
		if ($value === NULL)
			return NULL;

		$value = (float) $value;

		if (is_numeric($this->precision))
		{
			$value = round($value, $this->precision);
		}

		return $value;
	}
}
