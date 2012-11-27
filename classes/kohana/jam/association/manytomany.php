<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles many-to-many relationships
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association_ManyToMany extends Jam_Association_Collection {

	public $through;
	public $through_dependent = TRUE;

	public $foreign_key = NULL;
	public $association_foreign_key = NULL;
	public $join_table = NULL;

	/**
	 * Automatically sets foreign to sensible defaults.
	 *
	 * @param   string  $model
	 * @param   string  $name
	 * @return  void
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		// if (empty($this->foreign))
		// {
		// 	$foreign_model = Inflector::singular($name);
		// 	$this->foreign = $foreign_model.'.'.Jam::meta($foreign_model)->primary_key();
		// }
		// // Is it model.field?
		// elseif (is_string($this->foreign) AND FALSE === strpos($this->foreign, '.'))
		// {
		// 	$foreign_model = $this->foreign;
		// 	$this->foreign = $this->foreign.'.'.Jam::meta($foreign_model)->primary_key();
		// }

		// // Create the default through connection
		// if (empty($this->through) OR is_string($this->through))
		// {
		// 	if (empty($this->through))
		// 	{
		// 		$this->through = Jam_Association_Collection::guess_through_table($foreign_model, $meta->model());
		// 	}

		// 	$this->through = array(
		// 		'model' => $this->through,
		// 		'fields' => array(
		// 			'our' => $meta->foreign_key(),
		// 			'foreign' => Jam::meta($foreign_model)->foreign_key(),
		// 		)
		// 	);
		// }

		parent::initialize($meta, $name);

		if ( ! $this->join_table)
		{
			$this->join_table = Jam_Association_Collection::guess_through_table($this->foreign_model, $this->model);
		}

		if ( ! $this->foreign_key)
		{
			$this->foreign_key = $this->model.'_id';
		}

		if ( ! $this->association_foreign_key)
		{
			$this->association_foreign_key = $this->foreign_model.'_id';
		}
	}

	public function join($alias, $type = NULL)
	{
		return Jam_Query_Builder_Join::factory($alias ? array($this->foreign_model, $alias) : $this->foreign_model, $type)
			->context_model($this->model)
			->on(':primary_key', '=' , $this->join_table.'.'.$this->association_foreign_key)
			->join_nested($this->join_table)
				->context_model($this->model)
				->on($this->join_table.'.'.$this->foreign_key, '=', ':primary_key')
			->end();
	}


	public function attribute_join(Jam_Builder $builder, $alias = NULL, $type = NULL)
	{
		return $builder
			->join($this->through(), $type)
			->on($this->through('our'), '=', "{$this->model}.:primary_key")
			->join($this->foreign(NULL, $alias), $type)
			->on($this->foreign('field', $alias), '=', $this->through('foreign'));
	}

	public function builder(Jam_Model $model)
	{
		if ( ! $model->loaded())
			throw new Kohana_Exception("Cannot create Jam_Builder on :model->:name because model is not loaded", array(':name' => $this->name, ':model' => $model->meta()->model()));

		$builder = Jam::query($this->foreign())
			->join($this->through())
			->on($this->through('foreign'), '=', $this->foreign('field'))
			->where($this->through('our'), '=', $model->id());

		return $builder;
	}

	public function model_before_delete(Jam_Model $model)
	{
		if ($model->loaded() AND $this->through_dependent)
		{
			Jam::query($this->through())
				->where($this->through('our'), '=', $model->id())
				->delete($model->meta()->db());
		}
	}

	public function model_after_save(Jam_Model $model)
	{
		if ($model->changed($this->name) AND $collection = $model->{$this->name} AND $collection->changed())
		{
			list($old_ids, $new_ids) = $this->diff_collection_ids($model, $collection);

			if ($old_ids)
			{
				Jam::query($this->through())
					->where($this->through('foreign'), 'IN', $old_ids)
					->delete($model->meta()->db());
			}

			if ($new_ids)
			{
				foreach ($new_ids as $new_id)
				{
					Jam::query($this->through())
						 ->columns(array_values($this->through['fields']))
						 ->values(array($model->id(), $new_id))
						 ->insert($model->meta()->db());
				}
			}
		}
	}

} // End Kohana_Jam_Association_ManyToMany