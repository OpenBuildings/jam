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
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Event {

	const ATTRIBUTE_PRIORITY = 20;
	const BEHAVIOR_PRIORITY = 10;

	protected $serial = 0;

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
	public function bind($event, $callback, $priority = 0)
	{
		if ( ! isset($this->_events[$event]))
		{
			$this->_events[$event] = new SplPriorityQueue();
		}

		$this->_events[$event]->insert($callback, $priority*100000 + $this->serial--);

		return $this;
	}

	public function discover_events($from, $priority = 0)
	{
		foreach (get_class_methods($from) as $method)
		{
			if (($ns = substr($method, 0, 5)) === 'model'
			OR  ($ns = substr($method, 0, 4)) === 'meta'
			OR  ($ns = substr($method, 0, 7)) === 'builder')
			{
				$this->bind(strtolower($ns.'.'.substr($method, strlen($ns) + 1)), array($from, $method), $priority);
			}
		}
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

			$queue = clone $this->_events[$event];

			foreach ($queue as $callback)
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
	 * @throws Jam_Exception_Methodmissing If no method is found
	 */
	public function trigger_callback($type, $sender, $method, $params)
	{
		$event = "{$type}.call_{$method}";

		if (empty($this->_events[$event]))
			throw new Jam_Exception_Methodmissing($sender, $method, $params);

		return $this->trigger($event, $sender, $params);
	}
}
