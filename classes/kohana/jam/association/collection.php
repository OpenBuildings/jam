<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Common association for has-many and many-to-many relationships
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2010-2011 OpenBuildings
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association_Collection extends Jam_Association {

	public $through;

	public function through($field_name = NULL)
	{
		if ($field_name !== NULL)
		{
			$field_name = '.'.Arr::get($this->through['fields'], $field_name, $field_name);
		}
		return $this->through['model'].$field_name;
	}

	/**
	 * Find the join table based on the two model names pluralized,
	 * sorted alphabetically and with an underscore separating them
	 * 
	 * @param  string $model1 
	 * @param  string $model2 
	 * @return string
	 */
	static public function guess_through_table($model1, $model2)
	{
		$through = array(
			Inflector::plural($model1),
			Inflector::plural($model2)
		);

		sort($through);
		return implode('_', $through);
	}

	public function attribute_get(Jam_Model $model)
	{
		if ( ! $model->loaded())
			return $this->set($model, array(), TRUE);

		return $this->builder($model)->select_all()->_parent_association($model, $this);
	}

	public function attribute_set(Jam_Model $model, $value, $is_changed)
	{
		$new_collection = new Jam_Collection($value, Jam::class_name($this->foreign()));
		return $new_collection->_parent_association($model, $this);
	}

	public function attribute_after_check(Jam_Model $model, $is_changed)
	{
		if ($is_changed)
		{
			$collection = $model->{$this->name};

			foreach ($collection as $i => $item)
			{
				if (is_object($item) AND ! $item->deleted() AND is_numeric($i))
				{
					$this->assign_relation($model, $item);
					if ( ! $item->check())
					{
						$model->errors()->add($this->name, 'association', array(':errors' => $item->errors()));
					}
					$collection[$i] = $item;
				}
			}	
		}
	}
	
	public function diff_collection_ids(Jam_Model $model, Jam_Collection $collection)
	{
		$current_ids = $this->builder($model)
			->select_column(array($model->meta()->primary_key()))
			->select_ids();

		if ($collection->changed())
		{
			foreach ($collection as $i => $item)
			{
				if ( ! $item->deleted() AND is_numeric($i))
				{
					$this->assign_relation($model, $item);
					$this->preserve_item_changes($item);
					$collection[$i] = $item;
				}
			}
		}

		return array(
			array_diff($current_ids, $collection->ids()),
			array_diff($collection->ids(), $current_ids)
		);	
	}
}
