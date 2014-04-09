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

} // End Kohana_Jam_Field_Integer
