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

	public static $allowed = array('native', 'json', 'csv', 'query');

	public $method = 'native';

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		if ( ! in_array($this->method, Jam_Field_Serialized::$allowed))
			throw new Kohana_Exception("Unnown serialization method ':method', can use only :allowed", array(':method' => $this->method, ':allowed' => Jam_Field_Serialized::$allowed));
	}

	/**
	 * Unserializes data as soon as it comes in.
	 *
	 * Incoming data that isn't actually serialized will not be harmed.
	 *
	 * @param   mixed  $value
	 * @return  mixed
	 */
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		list($value, $return) = $this->_default($model, $value);

		if ( ! $return)
		{
		 	if (is_string($value) AND ($new_value = $this->unserialize($value)) !== FALSE)
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
	public function convert(Jam_Validated $model, $value, $is_loaded)
	{
		if ($this->allow_null AND $value === NULL)
		{
			return NULL;
		}

		return $this->serialize($value);
	}

	public function serialize($value)
	{
		switch ($this->method)
		{
			case 'native':
				return serialize($value);

			case 'csv':
				return join(',', $value);

			case 'json':
				return json_encode($value);

			case 'query':
				return http_build_query($value);
		}
	}

	public function unserialize($value)
	{
		switch ($this->method)
		{
			case 'native':
				return unserialize($value);

			case 'csv':
				return explode(',', $value);

			case 'json':
				return json_decode($value, TRUE);

			case 'query':
				parse_str($value, $value);
				return $value;
		}
	}

} // Kohana_Jam_Field_Serialized
