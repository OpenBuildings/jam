<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * A class to create queries for selecting data for jam models from the database
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Select extends Database_Query_Builder_Select {

	/**
	 * Create object of class Jam_Query_Builder_Select
	 * @param  string $model
	 * @return Jam_Query_Builder_Select
	 */
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

	protected static $_modifiable = array('select', 'from', 'join', 'where', 'group_by', 'having', 'order_by', 'union', 'distinct', 'limit', 'offset', 'parameters');

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

		if ( ! $this->_meta)
			throw new Kohana_Exception('There is no model :model for select', array(':model' => $model));

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

		$original_select = $this->_select;
		$original_from = $this->_from;

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
			$attribute = Jam_Query_Builder::resolve_attribute_name($attribute, $this->meta()->model());
		}

		$this->meta()->events()->trigger('builder.before_select', $this);

		$result = parent::compile($db);

		$this->meta()->events()->trigger('builder.after_select', $this);

		$this->_select = $original_select;
		$this->_from = $original_from;

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

	protected function _join($association, $type, $resolve_table_model = TRUE)
	{
		$join_key = is_array($association) ? join(':', $association) : $association;

		if ( ! isset($this->_join[$join_key]))
		{
			$join = Jam_Query_Builder::resolve_join($association, $type, $this->meta()->model(), $resolve_table_model);

			$this->_join[$join_key] = $join;

			return $join;
		}
		else
		{
			return $this->_join[$join_key];
		}
	}

	public function join($association, $type = NULL)
	{
		$this->_last_join = $this->_join($association, $type);

		return $this;
	}

	public function on($c1, $op, $c2)
	{
		if ( ! $this->_last_join)
			throw new Kohana_Exception('You must specifiy a JOIN first!');

		return parent::on($c1, $op, $c2);
	}


	public function join_nested($association, $type = NULL)
	{
		return $this->_join($association, $type)->end($this);
	}

	public function join_table($table, $type = NULL)
	{
		return $this->_join($table, $type, FALSE)->end($this);
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

		return parent::_compile_group_by($db, $group_by);
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
			$query->except('group_by', 'order_by', 'limit', 'offset');
		}

		return (int) $query->execute()->get('result');
	}

	public function count_with_subquery()
	{
		return (int) DB::select(array(DB::expr('COUNT(*)'), 'result'))->from(array($this, 'result_table'))->execute($this->meta()->db())->get('result');
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

	/**
	 * You can get some of the parameters of the jam query builder.
	 *
	 * @param  string $name one of select, from, join, where, group_by, having, order_by, union, distinct, limit, offset, parameters
	 * @return mixed
	 */
	public function __get($name)
	{
		if ( ! in_array($name, Jam_Query_Builder_Select::$_modifiable))
				throw new Kohana_Exception('You cannot get :name, only :modifiable', array(':name' => $name, ':modifiable' => join(', ', Jam_Query_Builder_Select::$_modifiable)));

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

	public function except($name)
	{
		$except = func_get_args();

		if ($not_modifiable = array_diff($except, Jam_Query_Builder_Select::$_modifiable))
				throw new Kohana_Exception('You cannot modify :not_modifiable, only :modifiable', array(':not_modifiable' => join(', ', $not_modifiable), ':modifiable' => join(', ', Jam_Query_Builder_Select::$_modifiable)));

		$new = new Database_Query_Builder_Select;

		foreach ($except as $name)
		{
			$this->{'_'.$name} = $new->{'_'.$name};
		}

		return $this;
	}
}
