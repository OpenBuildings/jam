<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Core class that all fields must extend
 *
 * @package    Jam
 * @category   Attribute
 * @author     Ivan Kerin
 * @copyright  (c) 2012 OpenBuildings Ltd.
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
	 * An array of extension objects for easy reference
	 * @var array
	 */
	protected $_extensions = array();

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
			throw new Kohana_Exception("Jam_Attribute options must be either array of options");
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
		// This will come in handy for setting complex relationships
		$this->model = $model;

		// This is for naming form fields
		$this->name = $name;

		// Check for a name, because we can easily provide a default
		if ( ! $this->label)
		{
			$this->label = Inflector::humanize($name);
		}
	}

	public function trigger($method, $arg0, $arg1 = NULL, $arg2 = NULL)
	{
		$method_name = 'attribute_'.$method;
		$return = NULL;

		$arguments = func_get_args();
		$arguments = array_slice($arguments, 1);

		if ($this->_events)
		{
			$this->_events->trigger('before.'.$method, $this, $arguments);
		}

		if (method_exists($this, $method_name))
		{
			$return = $this->$method_name($arg0, $arg1, $arg2);
		}

		if ($this->_events)
		{
			array_push($arguments, $return);
			$event_return = $this->_events->trigger('after.'.$method, $this, $arguments);
			
			if ($event_return !== NULL)
			{
				$return = $event_return;
			}
		}

		return $return;
	}

	public function bind($event, $callback)
	{
		if ( ! $this->_events)
		{
			$this->_events = new Jam_Event($this->name);
		}

		$this->_events->bind($event, $callback);

		return $this;
	}

	public function extension($extension_name, Jam_Extension $extension = NULL)
	{
		if ($extension !== NULL)
		{
			if (isset($this->extensions[$extension_name]))
				throw new Kohana_Exception('The extension :extension_name has already been added to :class (:name)', array(':extension_name' => $extension_name, ':class' => get_class($this), ':name' => $this->name));

			$this->extensions[$extension_name] = $extension;
			$extension->initialize($this);
			return $this;
		}

		return $this->extensions[$extension_name];
	}

	public function get(Jam_Model $model, $value)
	{
		return $this->trigger('get', $model, $value);
	}

	public function set($model, $value)
	{
		return $this->trigger('set', $model, $value);
	}

	public function before_delete(Jam_Model $model, $value)
	{
		return $this->trigger('before_delete', $model, $value);
	}

	public function after_delete(Jam_Model $model, $value)
	{
		return $this->trigger('before_delete', $model, $value);
	}

	public function before_save(Jam_Model $model, $value, $is_changed)
	{
		return $this->trigger('before_save', $model, $value, $is_changed);
	}

	public function after_save(Jam_Model $model, $value, $is_changed)
	{
		return $this->trigger('after_save', $model, $value, $is_changed);
	}

	public function before_check(Jam_Model $model, Jam_Validation $validation, $value)
	{
		return $this->trigger('before_check', $model, $validation, $value);
	}

	public function after_check(Jam_Model $model, Jam_Validation $validation, $value)
	{
		return $this->trigger('after_check', $model, $validation, $value);
	}

}