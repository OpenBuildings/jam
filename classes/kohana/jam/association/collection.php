<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Common association for has-many and many-to-many relationships
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association_Collection extends Jam_Association {

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

	/**
	 * Assign default forein_model to singular association name
	 * @param  Jam_Meta $meta 
	 * @param  string   $name 
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		if ( ! $this->foreign_model)
		{
			$this->foreign_model = Inflector::singular($name);
		}

		parent::initialize($meta, $name);
	}


	public function load_fields(Jam_Validated $model, $value)
	{
		if ($value instanceof Jam_Query_Builder_Associated)
		{
			$collection = $value;
		}
		else
		{
			$collection = new Jam_Query_Builder_Associated($this->foreign_model);
			$collection
				->load_fields($value);
		}

		return $collection
			->parent($model)
			->association($this);
	}


	public function model_after_check(Jam_Model $model)
	{
		if ($model->changed($this->name) AND ! $model->{$this->name}->check_changed())
		{
			$model->errors()->add($this->name, 'association_collection');
		}
	}

	public function model_after_save(Jam_Model $model)
	{
		if ($model->changed($this->name) AND $collection = $model->{$this->name} AND $collection->changed())
		{
			$collection->save_changed();

			$this->save($model, $collection);
		}
	}

	public function item_get(Jam_Model $model, Jam_Model $item)
	{
	}

	public function item_set(Jam_Model $model, Jam_Model $item)
	{
	}

	public function item_unset(Jam_Model $model, Jam_Model $item)
	{
	}

	abstract public function remove_items_query(array $ids, Jam_Model $model);

	
	abstract public function add_items_query(array $ids, Jam_Model $model);

	public function save(Jam_Model $model, Jam_Query_Builder_Associated $collection)
	{
		if ($old_ids = array_values(array_diff($collection->original_ids(), $collection->ids())))
		{
			$this->remove_items_query($old_ids, $model)->execute();
		}
		
		if ($new_ids = array_values(array_diff($collection->ids(), $collection->original_ids())))
		{
			$this->add_items_query($new_ids, $model)->execute();
		}
	}

	public function clear(Jam_Validated $model, Jam_Query_Builder_Associated $collection)
	{
		if ($ids = array_filter($collection->ids()))
		{
			$this->remove_items_query($ids, $model)->execute(Jam::meta($this->model)->db());
		}
	}

}
