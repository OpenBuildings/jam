<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Event acts as a manager for all events bound to a model
 *
 * The standard events are documented in the guide. Binding and
 * triggering custom events is entirely possible.
 *
 * @package    Jam
 * @category   Events
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Event {

	/**
	 * @var  array  The current model
	 */
	protected $_model = NULL;

	/**
	 * @var  array  Bound events
	 */
	protected $_events = array();

	/**
	 * Constructor.
	 *
	 * @param  string  $model
	 */
	public function __construct($model)
	{
		$this->_model = $model;
	}

	/**
	 * Binds an event.
	 *
	 * @param   string    $event
	 * @param   callback  $callback
	 * @return  Jam_Event
	 */
	public function bind($event, $callback)
	{
		$this->_events[$event][] = $callback;
	}

	/**
	 * Triggers an event.
	 *
	 * @param   string  $event
	 * @param   mixed   $sender
	 * @param   mixed   $params...
	 * @return  mixed
	 */
	public function trigger($event, $sender, $params = array())
	{
		if ( ! empty($this->_events[$event]))
		{
			$data = new Jam_Event_Data(array(
				'event'  => $event,
				'sender' => $sender,
				'args'   => $params,
			));

			// Create the params to be passed to the callback
			// Sender, Params and Event, then params passed from the trigger
			array_unshift($params, $data);
			array_unshift($params, $sender);

			foreach ($this->_events[$event] as $callback)
			{
				call_user_func_array($callback, $params);

				if ($data->stop)
				{
					break;
				}
			}

			return $data->return;
		}

		return NULL;
	}

	/**
	 * Trigger a callback, if there are no callbacks found, throws an exception
	 * 
	 * @param  string $type   'model', 'builder' or 'meta'
	 * @param  mixed  $sender Jam_Model, Jam_Builder or Jam_Meta
	 * @param  string $method 
	 * @param  array  $params passed to the method
	 * @return mixed          returns the response from the callback
	 * @throws Jam_Exception_MethodMissing If no method is found
	 */
	public function trigger_callback($type, $sender, $method, $params)
	{
		$event = "{$type}.call_{$method}";

		if (empty($this->_events[$event]))
			throw new Jam_Exception_MethodMissing($sender, $method, $params);

		return $this->trigger($event, $sender, $params);
	}

	/**
	 * Bind callback methods for specfic types. Supported types are 'model', 'builder' and 'meta'
	 * 
	 * @param  string $type      'model', 'builder' or 'meta'
	 * @param  array  $callbacks Callbacks - array of string/array method names, Closure objects or a whole class to mixin
	 * @return NULL
	 */
	public function bind_callbacks($type, array $callbacks)
	{
		// Traditional callbacks and closures
		if (is_array($callbacks))
		{
			foreach ($callbacks as $method_name => $callback) 
			{
				$this->bind("{$type}.call_{$method_name}", $callback);
			}
		}
		// Use class with static methods for callbacks
		elseif (class_exists($callbacks))
		{
			$reflection = new ReflectionClass($callbacks);
			foreach ($reflection->getMethods(ReflectionMethod::IS_STATIC) as $method) 
			{
				$this->bind("{$type}.call_{$method->name}", "$callbacks::{$method->name}");
			}
		}
		else
		{
			throw new Kohana_Exception('Extensions for :type for model :model, callbacks :callbacks are invalid', array(
				':model' => $this->model,
				':type' => $type,
				':callbacks' => print_r($callbacks)
			));
		}
	}

} // End Kohana_Jam_Event