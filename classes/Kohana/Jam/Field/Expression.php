<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles expression fields, which allow an arbitrary database
 * expression as the field column
 *
 * For example, if you always wanted the field to return a concatenation
 * of two columns in the database, you can do this:
 *
 * 'field' => new Jam_Field_Expression('array(
 *       'column' => DB::expr("CONCAT(`first_name`, ' ', `last_name`)"),
 * ))
 *
 * It is possible to cast the returned value using a Jam field.
 * This should be defined in the 'cast' property:
 *
 * 'field' => new Jam_Field_Expression('array(
 *       'cast'   => 'integer', // This will cast the field using Jam::field('integer')
 *       'column' => DB::expr(`first_name` + `last_name`),
 * ))
 *
 * Keep in mind that aliasing breaks down in Database_Expressions.
 *
 * @package    Jam
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Field_Expression extends Jam_Field {

	/**
	 * @var  boolean  expression fields are not in_db
	 */
	public $in_db = FALSE;

	/**
	 * @var  string  the field type that should be used to cast the returned value
	 */
	public $cast;

	/**
	 * Casts the returned value to a given type.
	 *
	 * @param   mixed   $value
	 * @return  mixed
	 */
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		if (isset($this->cast))
		{
			// Cast the value using the field type defined in 'cast'
			$value = Jam::field($this->cast)->set($value);
		}

		return $value;
	}

} // End Kohana_Jam_Field_Expression
