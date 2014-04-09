<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * A class representing one or more joins
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Join extends Database_Query_Builder_Join {

	/**
	 * Create object of class Jam_Query_Builder_Join
	 * @param  string $model
	 * @param  string $type LEFT, RIGHT, NATURAL...
	 * @return Jam_Query_Builder_Join
	 */
	public static function factory($model, $type = NULL)
	{
		return new Jam_Query_Builder_Join($model, $type);
	}

	protected $_joins = array();

	protected $_context_model = NULL;

	protected $_model = NULL;

	protected $_end = NULL;

	public function join($model, $type = NULL)
	{
		$this->_joins[] = Jam_Query_Builder::resolve_join($model, $type, $this->model() ? $this->model() : $this->_table);

		return $this;
	}

	public function join_nested($model, $type = NULL)
	{
		$join = Jam_Query_Builder::resolve_join($model, $type, $this->model() ? $this->model() : $this->_table)
			->end($this);

		$this->_joins[] = $join;
		return $join;
	}

	public function join_table($model, $type = NULL)
	{
		$join = Jam_Query_Builder::resolve_join($model, $type, $this->_table, FALSE)
			->end($this);

		$this->_joins[] = $join;
		return $join;
	}

	public function compile($db = NULL)
	{
		if ($this->context_model() AND $meta = Jam::meta(Jam_Query_Builder::aliased_model($this->context_model())))
		{
			$db = Database::instance($meta->db());
		}

		$original_on = $this->_on;
		$original_using = $this->_using;
		$original_table = $this->_table;

		if ( ! empty($this->_on))
		{
			foreach ($this->_on as & $condition)
			{
				$condition[0] = Jam_Query_Builder::resolve_attribute_name($condition[0], $this->model() ? $this->model() : $this->_table);
				$condition[2] = Jam_Query_Builder::resolve_attribute_name($condition[2], $this->context_model());
			}
		}
		$this->_table = Jam_Query_Builder::resolve_table_alias($this->_table);

		if ( ! empty($this->_using))
		{
			foreach ($this->_using as & $column)
			{
				$column = Jam_Query_Builder::resolve_attribute_name($column, $this->meta());
			}
		}

		$additional_joins = '';
		foreach ($this->_joins as $join)
		{
			$additional_joins .= ' '.$join->compile($db);
		}

		$compiled = parent::compile($db).$additional_joins;
		$this->_on = $original_on;
		$this->_using = $original_using;
		$this->_table = $original_table;

		return $compiled;
	}

	public function context_model($context_model = NULL)
	{
		if ($context_model !== NULL)
		{
			$this->_context_model = $context_model;
			return $this;
		}
		return $this->_context_model;
	}

	public function model($model = NULL)
	{
		if ($model !== NULL)
		{
			$this->_model = $model;
			return $this;
		}
		return $this->_model;
	}

	public function end(Database_Query_Builder $end = NULL)
	{
		if ($end !== NULL)
		{
			$this->_end = $end;
			return $this;
		}
		return $this->_end;
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

} // End Kohana_Jam_Association
