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

	const SELECT =     'Select';
	const INSERT =     'Insert';
	const UPDATE =     'Update';
	const DELETE =     'Delete';
	const COLLECTION = 'Collection';
	const DYNAMIC =    'Dynamic';

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

	public static function find_by_primary_key(Database_Query $query, $key)
	{
		if (is_array($key))
		{
			$query->where(':primary_key', 'IN', $key);
		}
		else
		{
			$query->where(':unique_key', '=', $key);
		}
		return $query;
	}

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

	public static function resolve_table_alias($model)
	{
		if ($meta = Jam::meta(Jam_Query_Builder::aliased_model($model)))
		{
			$model = Jam_Query_Builder::set_table_name($model, $meta->table());
		}
		return $model;
	}

	public static function resolve_join($table, $type = NULL, $context_model = NULL)
	{
		$context_model_name = Jam_Query_Builder::aliased_model($context_model);

		if (is_string($context_model_name) AND $meta = Jam::meta($context_model_name))
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

	public static function aliased_model($model)
	{
		return is_array($model) ? $model[0] : $model;
	}

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

} // End Kohana_Jam_Association