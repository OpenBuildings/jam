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
abstract class Kohana_Jam_Query_Builder {

	/**
	 * Convert a column to a `table`.`column` using the appropriate model info
	 * @param  string $column
	 * @param  string $model
	 * @param  mixed $value
	 * @return string
	 */
	public static function resolve_attribute_name($column, $model = NULL, $value = NULL)
	{
		if (is_array($column))
		{
			list($column, $alias) = $column;
		}

		if ( ! ($column instanceof Database_Expression) AND $column !== '*')
		{
			if (strpos($column, '.') !== FALSE)
			{
				list($model, $column) = explode('.', $column);
			}

			if ($meta = Jam::meta(Jam_Query_Builder::aliased_model($model)))
			{
				$column = Jam_Query_Builder::resolve_meta_attribute($column, $meta, $value);
			}

			if ($model)
			{
				if (is_array($model))
				{
					$model = $model[1];
				}
				elseif ($meta)
				{
					$model = $meta->table();
				}

				$column = $model.'.'.$column;
			}
		}

		if ( ! empty($alias))
		{
			return array($column, $alias);
		}
		else
		{
			return $column;
		}
	}

	/**
	 * Set a primary key condition. If its an array make in an IN condition.
	 * @param  Database_Query $query
	 * @param  string         $key
	 * @return Database_Query
	 */
	public static function find_by_primary_key(Database_Query $query, $key)
	{
		if (is_array($key))
		{
			if ( ! $key)
				throw new Kohana_Exception('Arrays of primary keys is empty');

			$query->where(':primary_key', 'IN', $key);
		}
		else
		{
			if ( ! $key)
				throw new Kohana_Exception('Primary key must not be empty');

			$query->where(':unique_key', '=', $key);
		}
		return $query;
	}

	/**
	 * Set the table name, even if its in an alias array (table, alias)
	 * @param string|array $model
	 * @param string $table
	 * @return string|array
	 */
	public static function set_table_name($model, $table)
	{
		if (is_array($model))
		{
			$model[0] = $table;
		}
		else
		{
			$model = $table;
		}
		return $model;
	}

	/**
	 * Convert model name to its corresponding table, even if its in an array (model, alias)
	 * @param  string|array $model
	 * @return string|array
	 */
	public static function resolve_table_alias($model)
	{
		if ($meta = Jam::meta(Jam_Query_Builder::aliased_model($model)))
		{
			$model = Jam_Query_Builder::set_table_name($model, $meta->table());
		}
		return $model;
	}

	/**
	 * Generate Jam_Query_Builder_Join based on the given arguments
	 * @param  string  $table
	 * @param  string  $type                LEFT, NATURAL...
	 * @param  string  $context_model       the model of the parent
	 * @param  boolean $resolve_table_model wether to resolve the name of the model to a tablename
	 * @return Jam_Query_Builder_Join
	 */
	public static function resolve_join($table, $type = NULL, $context_model = NULL, $resolve_table_model = TRUE)
	{
		$context_model_name = Jam_Query_Builder::aliased_model($context_model);

		if ($resolve_table_model AND is_string($context_model_name) AND $meta = Jam::meta($context_model_name))
		{
			$table_name = Jam_Query_Builder::aliased_model($table);
			if (is_string($table_name) AND $association = $meta->association($table_name))
			{
				return $association->join(is_array($table) ? $table[1] : NULL, $type);
			}
		}

		$join = Jam_Query_Builder_Join::factory($table, $type);
		if ($context_model)
		{
			$join->context_model($context_model);
		}
		return $join;
	}

	/**
	 * Return the model if its alias array (model, alias)
	 * @param  string|array $model
	 * @return string
	 */
	public static function aliased_model($model)
	{
		return is_array($model) ? $model[0] : $model;
	}

	/**
	 * Convert :primary_key, :name_kay and :unique_key to their corresponding column names
	 * @param  string   $attribute
	 * @param  Jam_Meta $meta
	 * @param  mixed   $value
	 * @return string
	 */
	public static function resolve_meta_attribute($attribute, Jam_Meta $meta, $value = NULL)
	{
		switch ($attribute)
		{
			case ':primary_key':
				$attribute = $meta->primary_key();
			break;

			case ':name_key':
				$attribute = $meta->name_key();
			break;

			case ':unique_key':
				$attribute = $meta->unique_key($value);
			break;
		}

		return $attribute;
	}
}
