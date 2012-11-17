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
abstract class Kohana_Jam_Query_Builder_Select extends Database_Query_Builder_Select {

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
	public function __construct($model = NULL)
	{
		parent::__construct();

		if ( ! $model)
		{
			throw new Kohana_Exception('Jam_Query_Builder_Select requires model to be set in the constructor');
		}

		$this->_meta  = Jam::meta($model);
	}

	public function compile(Database $db)
	{
		$db = Database::instance($this->meta()->db());

		if (empty($this->_from))
		{
			$this->_from[] = $this->meta()->model();
		}

		if (empty($this->_select))
		{
			$this->_select[] = $this->meta()->table().'.*';
		}

		foreach ($this->_from as & $from)
		{
			$from = Jam_Query_Builder::resolve_table_alias($from);
		}

		foreach ($this->_select as & $attribute)
		{
			$attribute = Jam_Query_Builder::resolve_attribute_name($attribute);
		}

		return parent::compile($db);
	}

	public function join($model, $type = NULL)
	{
		$join = Jam_Query_Builder::resolve_join($model, $type, $this->model());

		$this->_join[is_array($model) ? join(':', $model) : $model] = $this->_last_join = $join;

		return $this;
	}

	public function join_nested($model, $type = NULL)
	{
		$join = Jam_Query_Builder::resolve_join($model, $type, $this->model())
			->end($this);

		$this->_join[is_array($model) ? join(':', $model) : $model] = $join;

		return $join;
	}

	protected function _compile_conditions(Database $db, array $conditions)
	{
		foreach ($conditions as & $group) 
		{
			foreach ($group as & $condition) 
			{
				$condition[0] = Jam_Query_Builder::resolve_attribute_name($condition[0], $this->model());
			}
		}

		return parent::_compile_conditions($db, $conditions);
	}

	public function select_count($column = '*')
	{
		$column = Jam_Query_Builder::resolve_attribute_name($column);

		$this->select(array(DB::expr('COUNT('.$column.')'), 'total'));

		return $this;
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
		$return = $this->_meta->events()->trigger_callback('model', $this, $method, $args);
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

	public function model()
	{
		return $this->meta()->model();
	}

	public function meta()
	{
		return $this->_meta;
	}

	/**
	 * Add methods for this builder on the fly (mixins) you can assign:
	 * Class - loads all static methods
	 * array or string/array callback
	 * array of closures
	 * 
	 * @param  array|string   $callbacks 
	 * @param  mixed $callback  
	 * @return Jam_Builder              $this
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