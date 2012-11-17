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

	public static function resolve_attribute_name($column, $model = NULL)
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
				$column = Jam_Query_Builder::resolve_meta_attribute($column, $meta);
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

	public static function resolve_table_alias($model)
	{
		if (is_array($model))
		{
			if ($meta = Jam::meta($model[0]))
			{
				$model[0] = $meta->table();
			}
		}
		elseif ($meta = Jam::meta($model))
		{
			$model = $meta->table();
		}

		return $model;
	}

	public static function resolve_join($table, $type = NULL, $context_model = NULL)
	{
		if ($meta = Jam::meta(Jam_Query_Builder::aliased_model($context_model)))
		{
			if ($association = $meta->association(Jam_Query_Builder::aliased_model($table)))
			{
				if (is_array($association))
				{
					$table[0] = $association->foreign_model;
				}
				else
				{
					$table = $association->foreign_model;
				}

				return $association
					->join($table, $type)
					->context_model($context_model);
			}
		}

		return Jam_Query_Builder_Join::factory($table, $type)
			->context_model($context_model);
	}

	public static function aliased_model($model)
	{
		return is_array($model) ? $model[0] : $model;
	}

	public static function resolve_meta_attribute($attribute, Jam_Meta $meta)
	{
		switch ($attribute) 
		{
			case ':primary_key':
				$attribute = $meta->primary_key();
			break;

			case ':name_key':
				$attribute = $meta->name_key();
			break;
		}

		return $attribute;
	}

} // End Kohana_Jam_Association