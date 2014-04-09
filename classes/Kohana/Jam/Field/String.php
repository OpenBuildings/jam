<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles strings
 *
 * By default, strings do not allow NULL to be set on them, because
 * it is generally redundant to have NULLs be allowed when empty strings
 * will suffice.
 *
 * @package    Jam
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Field_String extends Jam_Field {

	/**
	 * @var  string  default value is a string, since we null is FALSE
	 */
	public $default = '';

	/**
	 * @var  boolean  do not allow null values by default
	 */
	public $allow_null = FALSE;

	/**
	 * Casts to a string, preserving NULLs along the way.
	 *
	 * @param   mixed   $value
	 * @return  string
	 */
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		list($value, $return) = $this->_default($model, $value);

		if ( ! $return)
		{
			$value = (string) $value;
		}

		return $value;
	}

} // Kohana_Jam_Field_String
