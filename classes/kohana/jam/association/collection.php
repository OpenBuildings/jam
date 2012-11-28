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

	// public function get(Jam_Validated $model, $value, $is_changed)
	// {
	// 	if ( ! $model->loaded())
	// 		return $this->set($model, array(), TRUE);

	// 	return $this->builder($model)->select_all()->_parent_association($model, $this);
	// }

	public function initialize(Jam_Meta $meta, $name)
	{
		if ( ! $this->foreign_model)
		{
			$this->foreign_model = Inflector::singular($name);
		}

		parent::initialize($meta, $name);
	}
	
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		$new_collection = new Jam_Collection($value, Jam::class_name($this->foreign()));
		return $new_collection->_parent_association($model, $this);
	}

	public function model_after_check(Jam_Model $model)
	{
		if ($model->changed($this->name) AND ! $model->{$this->name}->check_changed())
		{
			$model->errors()->add($this->name, 'association_collection');
		}
	}
	
	public function diff_collection_ids(Jam_Model $model, Jam_Query_Builder_Dynamic $collection)
	{
		$current_ids = $collection->original()->as_array(NULL, $collection->meta()->primary_key());

		$collection->save_changed();

		return array(
			array_filter(array_diff($current_ids, $collection->ids())),
			array_filter(array_diff($collection->ids(), $current_ids))
		);	
	}
}
