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

	public static function resolve_attribute_name($field, $default_model = NULL)
	{
		if ($field instanceof Database_Expression)
			return $field;

		if (strpos($field, '.') !== FALSE)
		{
			list($table, $column) = explode('.', $field);
			$meta = Jam::meta($table);
			$table = $meta->table();
		}
		else
		{
			if (is_array($default_model))
			{
				list($default_model, $table) = $this->default_model;
				$meta = Jam::meta($default_model);
			}
			else
			{
				$meta = Jam::meta($default_model);
				$table = $meta->table();
			}
		}

		if ($meta)
		{
			$column = Jam_Query_Builder::resolve_meta_attribute($column, $meta);
		}

		return $table.'.'.$column;
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

	public static function aliased_meta($model)
	{
		return Jam::meta(is_array($model) ? $model[0] : $model);
	}

	public static function resolve_meta_attribute($attribute, Jam_Meta $meta)
	{
		if (($pos = strpos($attribute, ':')) > 0)
		{
			$meta = Jam::meta(substr($attribute, 0, $pos));
			$attribute = substr($attribute, $pos);
		}

		switch ($attribute) 
		{
			case ':primary_key':
				$attribute = $meta->primary_key();
			break;

			case ':foreign_key':
				$attribute = $meta->foreign_key();
			break;

			case ':name_key':
				$attribute = $meta->name_key();
			break;
		}

		return $attribute;
	}

} // End Kohana_Jam_Association