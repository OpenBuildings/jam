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
abstract class Kohana_Jam_Field_Serialized extends Jam_Field {

	/**
	 * Unserializes data as soon as it comes in.
	 *
	 * Incoming data that isn't actually serialized will not be harmed.
	 *
	 * @param   mixed  $value
	 * @return  mixed
	 */
	public function attribute_set($model, $value, $is_changed)
	{
		list($value, $return) = $this->_default($value);

		if ( ! $return)
		{
		 	if (is_string($value) AND ($new_value = @unserialize($value)) !== FALSE)
			{
				$value = $new_value;
			}
		}

		return $value;
	}

	/**
	 * Saves the value as a serialized string.
	 *
	 * @param   Jam_Model  $model
	 * @param   mixed        $value
	 * @param   boolean      $loaded
	 * @return  null|string
	 */
	public function attribute_convert($model, $value, $is_loaded)
	{
		if ($this->allow_null AND $value === NULL)
		{
			return NULL;
		}

		return @serialize($value);
	}

} // Kohana_Jam_Field_Serialized