<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles boolean values
 *
 * No special processing is added for this field other
 * than a validation rule that ensures the email address is valid.
 *
 * @package    Jam
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Field_Boolean extends Jam_Field {

	/**
	 * @var  mixed  how TRUE is represented in the database
	 */
	public $true = 1;

	/**
	 * @var mixed  how FALSE is represented in the database
	 */
	public $false = 0;

	/**
	 * @var  boolean  null values are not allowed
	 */
	public $allow_null = FALSE;

	/**
	 * @var  boolean  default value is FALSE, since NULL isn't allowed
	 */
	public $default = FALSE;

	/**
	 * Ensures convert_empty is not set on the field, as it prevents FALSE
	 * from ever being set on the field.
	 *
	 * @param   array  $options
	 */
	public function __construct($options = array())
	{
		parent::__construct($options);

		// Ensure convert_empty is FALSE
		if ($this->convert_empty)
		{
			throw new Kohana_Exception(':class cannot have convert_empty set to TRUE', array(
				':class' => get_class($this)));
		}
	}

	/**
	 * Validates a boolean out of the value with filter_var.
	 *
	 * @param   mixed  $value
	 * @return  void
	 */
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		list($value, $return) = $this->_default($model, $value);

		if ( ! $return)
		{
			$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
		}

		return $value;
	}

} // End Kohana_Jam_Field_Boolean
