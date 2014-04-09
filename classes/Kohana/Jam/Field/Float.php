<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles floats
 *
 * You can specify an optional places property to
 * round the value to the specified number of places.
 *
 * @package    Jam
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Field_Float extends Jam_Field {

	/**
	 * @var  int  default value is 0, per the SQL standard
	 */
	public $default = 0.0;

	/**
	 * @var  int  the number of places to round the number, NULL to forgo rounding
	 */
	public $places = NULL;

	/**
	 * Converts to float and rounds the number if necessary.
	 *
	 * @param   mixed  $value
	 * @return  mixed
	 */
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		list($value, $return) = $this->_default($model, $value);

		// Convert to a float and set the places properly
		if ( ! $return)
		{
			$value = (float) $value;

			if (is_numeric($this->places))
			{
				$value = round($value, $this->places);
			}
		}

		return $value;
	}

} // End Kohana_Jam_Field_Float
