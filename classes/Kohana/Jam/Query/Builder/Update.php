<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * A class to create queries for updating jam models from the database
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Update extends Database_Query_Builder_Update {

	/**
	 * Create object of class Jam_Query_Builder_Update
	 * @param  string $model
	 * @return Jam_Query_Builder_Update
	 */
	public static function factory($model, $key = NULL)
	{
		return new Jam_Query_Builder_Update($model, $key);
	}

	/**
	 * @var  Jam_Meta  The meta object (if found) that is attached to this builder
	 */
	protected $_meta = NULL;

	/**
	 * A store for user defined values for the builder
	 * @var array
	 */
	protected $_params = array();

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
	public function __construct($model, $key = NULL)
	{
		parent::__construct();

		$this->_meta = Jam::meta($model);

		if ($key !== NULL)
		{
			Jam_Query_Builder::find_primary_key($this, $key);
		}

		$this->meta()->events()->trigger('builder.after_construct', $this);
	}

	public function where_key($unique_key)
	{
		Jam_Query_Builder::find_by_primary_key($this, $unique_key);

		return $this;
	}

	public function compile($db = NULL)
	{
		if ($db === NULL AND $this->meta())
		{
			$db = Database::instance($this->meta()->db());
		}

		$this->_table = $this->meta()->table();

		$this->meta()->events()->trigger('builder.before_update', $this);

		$result = parent::compile($db);

		$this->meta()->events()->trigger('builder.after_update', $this);

		return $result;
	}

	public function execute($db = NULL, $as_object = NULL, $object_params = NULL)
	{
		if ($db === NULL AND $this->meta())
		{
			$db = Database::instance($this->meta()->db());
		}

		return parent::execute($db, $as_object, $object_params);
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
	 * Getter/setter for the params array used to store arbitrary values by the behaviors
	 *
	 * @param  array|string $params
	 * @param  mixed $param
	 * @return Jam_Builder         $this
	 */
	public function params($params = NULL, $param = NULL)
	{
		// Accept params('name', 'param');
		if ($param !== NULL)
		{
			$params = array($params => $param);
		}

		if (is_array($params))
		{
			$this->_params = Arr::merge($params, $this->_params);
			return $this;
		}

		if (is_string($params))
		{
			return Arr::get($this->_params, $params);
		}

		return $this->_params;
	}

	/**
	 * You can get some of the parameters of the jam query builder.
	 *
	 * @param  string $name one of set, from, where, order_by, limit, parameters
	 * @return mixed
	 */
	public function __get($name)
	{
		$allowed = array('set', 'table', 'where', 'order_by', 'limit', 'parameters');

		if ( ! in_array($name, $allowed))
				throw new Kohana_Exception('You cannot get :name, only :allowed', array(':name' => $name, ':allowed' => join(', ', $allowed)));

		return $this->{'_'.$name};
	}

	public function __toString()
	{
		try
		{
			// Return the SQL string
			return $this->compile();
		}
		catch (Exception $e)
		{
			return Kohana_Exception::text($e);
		}
	}
}
