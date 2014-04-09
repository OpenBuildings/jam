<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles polymorphic columns
 *
 * A polymorphic column is typically a string that specifies
 * the model to use for the row.
 *
 * @package    Jam
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Field_Polymorphic extends Jam_Field_String {

	/**
	 * @var  boolean  this is a polymorphic field
	 */
	public $polymorphic = TRUE;

	/**
	 * Sets the default for the field to the model.
	 *
	 * @param   string  $model
	 * @param   string  $column
	 * @return  void
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$this->default = $this->model;
	}

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

} // End Kohana_Jam_Field_Polymorphic
