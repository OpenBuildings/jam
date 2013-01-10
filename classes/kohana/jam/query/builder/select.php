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

	public static function factory($model)
	{
		return new Jam_Query_Builder_Select($model);
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
	public function __construct($model)
	{
		parent::__construct();

		$this->_meta  = Jam::meta($model);

		$this->meta()->events()->trigger('builder.after_construct', $this);
	}

	public function where_key($unique_key)
	{
		Jam_Query_Builder::find_by_primary_key($this, $unique_key);

		return $this;
	}

	public function compile(Database $db)
	{
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

		$this->meta()->events()->trigger('builder.before_select', $this);

		$result = parent::compile($db);
		
		$this->meta()->events()->trigger('builder.after_select', $this);

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

	protected function _join($model, $type)
	{
		$join_key = is_array($model) ? join(':', $model) : $model;

		if ( ! isset($this->_join[$join_key]))
		{
			$join = Jam_Query_Builder::resolve_join($model, $type, $this->meta()->model());

			$this->_join[$join_key] = $join;

			return $join;
		}
		else
		{
			return $this->_join[$join_key];
		}
	}

	public function join($model, $type = NULL)
	{
		$this->_last_join = $this->_join($model, $type);

		return $this;
	}

	public function join_nested($model, $type = NULL)
	{
		return $this->_join($model, $type)->end($this);
	}

	protected function _compile_order_by(Database $db, array $order_by)
	{
		foreach ($order_by as & $order) 
		{
			$order[0] = Jam_Query_Builder::resolve_attribute_name($order[0], $this->meta()->model());
		}

		return parent::_compile_order_by($db, $order_by);
	}

	protected function _compile_group_by(Database $db, array $group_by)
	{
		foreach ($group_by as & $group) 
		{
			$group = Jam_Query_Builder::resolve_attribute_name($group, $this->meta()->model());
		}

		return parent::_compile_group_by($db, $conditions);
	}

	protected function _compile_conditions(Database $db, array $conditions)
	{
		foreach ($conditions as & $group) 
		{
			foreach ($group as & $condition) 
			{
				if (is_array($condition))
				{
					$condition[0] = Jam_Query_Builder::resolve_attribute_name($condition[0], $this->meta()->model(), $condition[2]);
				}
			}
		}

		return parent::_compile_conditions($db, $conditions);
	}

	public function aggregate_query($function, $column = NULL)
	{
		if ($column === NULL OR $column === '*')
		{
			$column = '*';
		}
		else
		{
			$db = Database::instance($this->meta()->db());
			$column = Jam_Query_Builder::resolve_attribute_name($column, $this->meta()->model());
			$column = $db->quote_column($column);
		}

		$count = clone $this;
		return $count->select(array(DB::expr("{$function}({$column})"), 'result'));
	}

	public function aggregate($function, $column = NULL)
	{
		return $this->aggregate_query($function, $column)->execute()->get('result');	
	}

	public function count_all($without_grouping = FALSE)
	{
		$query = $this->aggregate_query('COUNT');

		if ($without_grouping)
		{
			$query->group_by(NULL)->order_by(NULL);
		}

		return $query->execute()->get('result');
	}

	public function count_with_subquery()
	{
		return DB::select(array(DB::expr('COUNT(*)', 'result')))->from($this)->execute()->get('result');
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

	public function meta()
	{
		return $this->_meta;
	}
}