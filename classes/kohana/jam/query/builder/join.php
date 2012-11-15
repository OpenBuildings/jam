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
abstract class Kohana_Jam_Query_Builder_Join extends Database_Query_Builder_Join {

	public static function factory($model, $type = NULL)
	{
		return new Jam_Query_Builder_Join($model, $type);
	}

	/**
	 * @var  Jam_Meta  The meta object (if found) that is attached to this builder
	 */
	protected $_meta;

	protected $_joins = array();

	protected $_context_model = NULL;
	
	protected $_end = NULL;

	public function __construct($model, $type = NULL)
	{
		$this->_meta = Jam_Query_Builder::aliased_meta($model))

		parent::__construct($table, $type);
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

	public function end(Database_Builder $end = NULL)
	{
		if ($end !== NULL)
		{
			$this->_end = $end;
			return $this;
		}
		return $this->_end;
	}

	public function meta()
	{
		return $this->_meta;
	}

	public function join($model, $type = NULL)
	{
		$this->_joins[] = Jam_Query_Builder_Join::factory($model, $type)
			->context_model($this->meta()->model());
		return $this;
	}

	public function join_nested($model, $type = NULL)
	{
		$join = Jam_Query_Builder_Join::factory($model, $type)
			->context_model($this->meta()->model())
			->end($this);

		$this->_joins[] = $join;
		return $join;
	}

	public function compile(Database $db)
	{
		$db = Database::instance($this->meta()->db());

		if (empty($this->_on) AND $context_model = $this->context_model())
		{
			$context_meta = Jam_Query_Builder::aliased_meta($context_model);

			if ($association = $context_meta->association())
			{
				$this->_table = $association->model();
				$association->join($this);
			}
		}

		if ( ! empty($this->_on))
		{
			foreach ($this->_on as & $condition) 
			{
				$condition[0] = Jam_Query_Builder::resolve_attribute_name($condition[0], $this->_table);
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

		return parent::compile($db).$additional_joins;
	}

} // End Kohana_Jam_Association