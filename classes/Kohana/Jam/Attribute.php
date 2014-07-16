<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Core class that all fields must extend
 *
 * @package    Jam
 * @category   Attribute
 * @author     Ivan Kerin
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Attribute {

	/**
	 * @var  string  the model's name
	 */
	public $model;

	/**
	 * @var  string  a pretty name for the field
	 */
	public $label;

	/**
	 * @var  string  the field's name in the form
	 */
	public $name;

	/**
	 * Callbacks to be executed after events
	 * @var Jam_Event
	 */
	protected $_events;

	/**
	 * Sets all options.
	 *
	 * @param  array  $options
	 */
	public function __construct($options = array())
	{
		if (is_array($options))
		{
			// Just throw them into the class as public variables
			foreach ($options as $name => $value)
			{
				$this->$name = $value;
			}
		}
		elseif ($options !== NULL)
		{
			throw new Kohana_Exception("Jam_Attribute options must be an array of options");
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
	public function initialize(Jam_Meta $meta, $name)
	{
		// This will come in handy for setting complex relationships
		if ( ! $this->model)
		{
			$this->model = $meta->model();
		}

		// This is for naming form fields
		$this->name = $name;

		// Check for a name, because we can easily provide a default
		if ( ! $this->label)
		{
			$this->label = Inflector::humanize($name);
		}

		$meta->events()->discover_events($this, Jam_Event::ATTRIBUTE_PRIORITY);
	}

	abstract public function get(Jam_Validated $model, $value, $is_changed);

	abstract public function set(Jam_Validated $model, $value, $is_changed);

}
