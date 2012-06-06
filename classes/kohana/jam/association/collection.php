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

	public $extend;

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

	public function builder(Jam_Model $model)
	{
		$builder = parent::builder($model);

		if ($this->extend)
		{
			$builder->extend($this->extend);
		}

		return $builder;
	}

	public function get(Jam_Model $model)
	{
		if ( ! $model->loaded())
			return $this->set($model, array());

		return $this->builder($model)->select_all()->_parent_association($model, $this);
	}

	public function set(Jam_Model $model, $value)
	{
		$new_collection = new Jam_Collection($value, Jam::class_name($this->foreign()));
		return $new_collection->_parent_association($model, $this);
	}

	public function convert_new_result_to_models(Jam_Model $model, Jam_Collection $collection)
	{
		if ($collection->changed())
		{
			foreach ($collection->result() as $i => $item)
			{
				$item = $this->model_from_array($item);
				$this->assign_relation($model, $item);

				$collection[$i] = $item;
			}
		}
	}

	public function after_check(Jam_Model $model, Jam_Validation $validation, $collection)
	{
		if ($collection AND $collection->changed())
		{
			$validation_errors = array();

			foreach ($collection as $i => $item)
			{
				// Avoid child / parent relations 
				if ($this->inverse_of AND $item->{$this->inverse_of} === $model)
				{
					$validation_errors[] = TRUE;
				}
				else
				{
					$validation_errors[] = $item->check();
				}
				
				$collection[$i] = $item;
			}

			if (in_array(FALSE, $validation_errors))
			{
				$validation->error($this->name, 'validation');
			}
		}

		if ($this->required AND ( ! $collection OR count($collection) == 0))
		{
			$validation->error($this->name, 'required');
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
				if ( ! $item->deleted())
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
} // End Kohana_Jam_Association_Collection
