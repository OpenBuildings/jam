<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Core class that all fields must extend
 *
 * @package    Jam
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Field extends Jam_Attribute {

	/**
	 * @var  string  the model's name
	 */
	public $model;

	/**
	 * @var  string  the column's name in the database
	 */
	public $column;

	/**
	 * @var  string  a pretty name for the field
	 */
	public $label;

	/**
	 * @var  string  the field's name in the form
	 */
	public $name;

	/**
	* @var  boolean  a primary key field.
	*/
	public $primary = FALSE;

	/**
	* @var  boolean  the column is present in the database table. Default: TRUE
	*/
	public $in_db = TRUE;

	/**
	* @var  mixed  default value
	*/
	public $default = NULL;

	/**
	 * @var  boolean  whether or not empty() values should be converted to NULL
	 */
	public $convert_empty = FALSE;

	/**
	 * @var  mixed  the value to convert empty values to. This is only used if convert_empty is TRUE
	 */
	public $empty_value = NULL;

	/**
	 * @var  boolean  whether or not NULL values are allowed
	 */
	public $allow_null = TRUE;

	/**
	* @var  array  filters are called whenever data is set on the field
	*/
	public $filters = array();

	/**
	 * Sets all options.
	 *
	 * @param  array  $options
	 */
	public function __construct($options = array())
	{
		// Assume it's the column name
		if (is_string($options))
		{
			$this->column = $options;
		}
		elseif (is_array($options))
		{
			// Just throw them into the class as public variables
			foreach ($options as $name => $value)
			{
				$this->$name = $value;
			}
		}
		elseif ($options !== NULL)
		{
			throw new Kohana_Exception("Jam_Field options must be either string or an array of options");
		}

		// See if we need to allow_null values because of convert_empty
		if ($this->convert_empty AND $this->empty_value === NULL)
		{
			$this->allow_null = TRUE;
		}

		// Default value is going to be NULL if null is true
		// to mimic the SQL defaults.
		if ( ! array_key_exists('default', (array) $options) AND $this->allow_null)
		{
			$this->default = NULL;
		}

		// Default the empty value to NULL when allow_null is TRUE, but be careful not
		// to override a programmer-configured empty_value.
		if ( ! empty($options['allow_null']) AND ! array_key_exists('empty_value', (array) $options))
		{
			$this->empty_value = NULL;
		}
	}

	/**
	 * This is called after construction so that fields can finish
	 * constructing themselves with a copy of the column it represents.
	 *
	 * @param   string  $model
	 * @param   string  $column
	 * @return  void
	 **/
	public function initialize(Jam_Meta $meta, $model, $name)
	{
		parent::initialize($meta, $model, $name);

		if ( ! $this->column)
		{
			$this->column = $name;
		}

		if ($this->filters)
		{
			$this->extension('filters', Jam::extension('filters'));
		}
	}


	public function convert($model, $value, $is_loaded)
	{
		return $this->trigger('convert', $model, $value, $is_loaded);
	}


	/**
	 * Sets a particular value processed according
	 * to the class's standards.
	 *
	 * @param   mixed  $value
	 * @return  mixed
	 **/
	public function attribute_set($model, $value)
	{
		list($value, $return) = $this->_default($value);

		return $value;
	}

	/**
	 * Returns a particular value processed according
	 * to the class's standards.
	 *
	 * @param   Jam_Model  $model
	 * @param   mixed        $value
	 * @return  mixed
	 **/
	public function attribute_get($model, $value, $is_changed)
	{
		return $value;
	}

	/**
	 * Called just before saving.
	 *
	 * If $in_db, it is expected to return a value suitable for insertion
	 * into the database.
	 *
	 * @param   Jam_Model  $model
	 * @param   mixed        $value
	 * @param   bool         $loaded
	 * @return  mixed
	 */
	public function attribute_convert($model, $value, $is_loaded)
	{
		return $value;
	}

	/**
	 * Shortcut for setting a filter
	 * @param  string $filter_name 
	 * @param  array $values    values, defaults to array(':value')
	 * @return Jam_Field            $this
	 */
	public function filter($filter_name, $values = NULL)
	{
		$this->filters[] = array($filter_name, $values);
		return $this;
	}

	/**
	 * Potentially converts the value to NULL or default depending on
	 * the fields configuration. An array is returned with the first
	 * element being the new value and the second being a boolean
	 * as to whether the field should return the value provided or
	 * continue processing it.
	 *
	 * @param   mixed  $value
	 * @return  array
	 */
	protected function _default($value)
	{
		$return = FALSE;

		// Convert empty values to NULL, if needed
		if ($this->convert_empty AND empty($value))
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
} // End Kohana_Jam_Field