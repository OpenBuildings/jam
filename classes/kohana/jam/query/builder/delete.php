<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Core class that all associations must extend
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Delete extends Database_Query_Builder_Delete {

	public static function factory($model)
	{
		return new Jam_Query_Builder_Delete($model);
	}

	/**
	 * @var  Jam_Meta  The meta object (if found) that is attached to this builder
	 */
	protected $_meta = NULL;

	/**
	 * Constructs a new Jam_Builder instance.
	 *
	 * $model is not actually allowed to be NULL. It has
	 * a default because PHP throws strict errors otherwise.
	 *
	 * @throws  Kohana_Exception
	 * @param   string|null  $model
	 * @param   mixed|null   $key
	 */
	public function __construct($model = NULL)
	{
		parent::__construct();

		if ( ! $model)
		{
			throw new Kohana_Exception('Jam_Query_Builder_Delete requires model to be set in the constructor');
		}

		$this->_meta  = Jam::meta($model);
	}

	public function compile(Database $db)
	{
		$db = Database::instance($this->meta()->db());

		$this->_table = $this->meta()->table();

		return parent::compile($db);
	}

	protected function _compile_order_by(Database $db, array $order_by)
	{
		foreach ($order_by as & $order) 
		{
			$order[0] = Jam_Query_Builder::resolve_attribute_name($order[0], $this->meta()->model());
		}

		return parent::_compile_order_by($db, $order_by);
	}

	protected function _compile_conditions(Database $db, array $conditions)
	{
		foreach ($conditions as & $group) 
		{
			foreach ($group as & $condition) 
			{
				$condition[0] = Jam_Query_Builder::resolve_attribute_name($condition[0], $this->meta()->model(), $condition[2]);
			}
		}

		return parent::_compile_conditions($db, $conditions);
	}

	public function meta()
	{
		return $this->_meta;
	}

	/**
	 * Passes unknown methods along to the behaviors.
	 *
	 * @param   string  $method
	 * @param   array   $args
	 * @return  mixed
	 **/
	public function __call($method, $args)
	{
		$return = $this->_meta->events()->trigger_callback('builder', $this, $method, $args);
		return $return ? $return : $this;
	}

	/**
	 * Add methods for this builder on the fly (mixins) you can assign:
	 * Class - loads all static methods
	 * array or string/array callback
	 * array of closures
	 * @param  array|string   $callbacks 
	 * @param  mixed $callback  
	 * @return Jam_Meta              $this
	 */
	public function extend($callbacks, $callback = NULL)
	{
		// Handle input with second argument, so you can pass single items without an array
		if ($callback !== NULL)
		{
			$callbacks = array($callbacks => $callback);
		}

		$this->_meta->events()->bind_callbacks('builder', $callbacks);
		return $this;
	}
} // End Kohana_Jam_Association