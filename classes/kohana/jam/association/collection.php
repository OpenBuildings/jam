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

	/**
	 * Load associated models (from database or after deserialization)
	 * @param  Jam_Validated $model 
	 * @param  mixed         $value 
	 * @return Jam_Query_Builder_Associated
	 */
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

	/**
	 * Perform checks on all changed items from this collection
	 * @param  Jam_Model $model 
	 */
	public function model_after_check(Jam_Model $model)
	{
		if ($model->changed($this->name) AND ! $model->{$this->name}->check_changed())
		{
			$model->errors()->add($this->name, 'association_collection');
		}
	}

	/**
	 * Persist this collection in the database
	 * @param  Jam_Model $model 
	 */
	public function model_after_save(Jam_Model $model)
	{
		if ($model->changed($this->name) AND $collection = $model->{$this->name} AND $collection->changed())
		{
			$collection->save_changed();

			$this->save($model, $collection);
		}
	}

	/**
	 * Use this method to perform special actions when an item is requested from the collection
	 * @param  Jam_Model $model 
	 * @param  Jam_Model $item  
	 */
	public function item_get(Jam_Model $model, Jam_Model $item)
	{
		// Extend
	}

	/**
	 * Use this method to perform special actions when an item is set to the collection
	 * @param  Jam_Model $model 
	 * @param  Jam_Model $item  
	 */
	public function item_set(Jam_Model $model, Jam_Model $item)
	{
		// Extend
	}

	/**
	 * Use this method to perform special actions when an item is removed from the collection
	 * @param  Jam_Model $model 
	 * @param  Jam_Model $item  
	 */
	public function item_unset(Jam_Model $model, Jam_Model $item)
	{
		// Extend
	}

	/**
	 * A database query used to remove items from the model
	 * @param  array     $ids   
	 * @param  Jam_Model $model 
	 * @return Database_Query
	 */
	abstract public function remove_items_query(Jam_Model $model, array $ids);

	/**
	 * A database query used to add items to the model
	 * @param  array     $ids   
	 * @param  Jam_Model $model 
	 * @return Database_Query
	 */
	abstract public function add_items_query(Jam_Model $model, array $ids);

	/**
	 * Execute remove_items_query and add_items_query to persist the colleciton to the datbaase
	 * @param  Jam_Model                    $model      
	 * @param  Jam_Query_Builder_Associated $collection 
	 */
	public function save(Jam_Model $model, Jam_Query_Builder_Associated $collection)
	{
		if ($old_ids = array_values(array_diff($collection->original_ids(), $collection->ids())))
		{
			$this->remove_items_query($model, $old_ids)->execute(Jam::meta($this->model)->db());
		}
		
		if ($new_ids = array_values(array_diff($collection->ids(), $collection->original_ids())))
		{
			$this->add_items_query($model, $new_ids)->execute(Jam::meta($this->model)->db());
		}
	}

	/**
	 * Use the remove query to remove all items from the collection
	 * @param  Jam_Validated                $model      
	 * @param  Jam_Query_Builder_Associated $collection 
	 */
	public function clear(Jam_Validated $model, Jam_Query_Builder_Associated $collection)
	{
		if ($ids = array_filter($collection->ids()))
		{
			$this->remove_items_query($model, $ids)->execute(Jam::meta($this->model)->db());
		}
	}

}
