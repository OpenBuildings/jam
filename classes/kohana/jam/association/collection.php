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
	 * a method or closure to call in order to modify the query
	 * 
	 * @var closure
	 */
	public $query;


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

	public function initialize(Jam_Meta $meta, $name)
	{
		if ( ! $this->foreign_model)
		{
			$this->foreign_model = Inflector::singular($name);
		}

		parent::initialize($meta, $name);
	}


	public function model_after_check(Jam_Model $model)
	{
		if ($model->changed($this->name) AND ! $model->{$this->name}->check_changed())
		{
			$model->errors()->add($this->name, 'association_collection');
		}
	}

	abstract public function save_collection(Jam_Model $model, Jam_Query_Builder_Dynamic $collection);

	public function model_after_save(Jam_Model $model)
	{
		if ($model->changed($this->name) AND $collection = $model->{$this->name} AND $collection->changed())
		{
			$collection->save_changed();

			$this->save_collection($model, $collection);
		}
	}
}
