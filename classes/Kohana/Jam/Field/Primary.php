<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles primary keys
 *
 * Currently, a primary key can be an integer or a string.
 *
 * @package    Jam
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Field_Primary extends Jam_Field {

	/**
	 * @var  boolean  defaults primary keys to primary
	 */
	public $primary = TRUE;

	/**
	 * @var  boolean  default to converting empty values to NULL so keys are auto-incremented properly
	 */
	public $allow_null = TRUE;

	/**
	 * @var  int  default is NULL
	 */
	public $default = NULL;

	/**
	 * Ensures allow_null is not set to FALSE on the field, as it prevents
	 * proper auto-incrementing of a primary key.
	 *
	 * @param  array  $options
	 */
	public function __construct($options = array())
	{
		parent::__construct($options);

		// Ensure allow_null is TRUE
		if ( ! $this->allow_null)
		{
			throw new Kohana_Exception(':class cannot have allow_null set to FALSE', array(
				':class' => get_class($this)));
		}
	}

	/**
	 * Converts numeric IDs to ints.
	 *
	 * @param   mixed  $value
	 * @return  int|string
	 */
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		list($value, $return) = $this->_default($model, $value);

		// Allow only strings and integers as primary keys
		if ( ! $return)
		{
			$value = is_numeric($value) ? (int) $value : (string) $value;
		}

		return $value;
	}

} // End Kohana_Jam_Field_Primary
