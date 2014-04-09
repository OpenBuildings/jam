<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Behavior
 *
 * Jam_Behavior is a simple class that all behaviors must extend
 * so that behaviors can be consistently packaged and used.
 *
 * Behaviors are expected to bind themselves to events on the
 * model in their initialize() method. By default, Jam will
 * auto-discover your events and bind them for you as long as
 * you prefix the method with either "model" or "builder".
 *
 * For example, a method in your behavior named "model_before_save"
 * will be bound to the "model.before_save" event.
 *
 * You can prevent this auto-discovering of methods by overloading
 * the `event()` method in this class and binding the events
 * yourself.
 *
 * The guide explains all available events, as well as how to
 * use custom events.
 *
 * @package    Jam
 * @category   Behaviors
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Behavior {

	/**
	 * @var  string  The model this is attached to
	 */
	protected $_model;

	/**
	 * @var  string  The name of this behavior
	 */
	protected $_name;

	/**
	 * Constructor.
	 *
	 * @param   array   $params
	 */
	public function __construct($params = array())
	{
		foreach ($params as $key => $param)
		{
			$this->{'_'.$key} = $param;
		}
	}

	/**
	 * Initialize.
	 *
	 * @param   Jam_Meta  $meta
	 * @param   string       $name
	 * @return  void
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		$this->_name  = $name;
		$this->_model = $meta->model();

		$meta->events()->discover_events($this, Jam_Event::BEHAVIOR_PRIORITY);
	}
} // End Kohana_Jam_Behavior
