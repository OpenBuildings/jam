<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles many-to-many relationships
 *
 * @package    Jelly
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2010-2011 OpenBuildings
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jelly_Association_ManyToMany extends Jelly_Association_Collection {

	public $through;

	/**
	 * Automatically sets foreign to sensible defaults.
	 *
	 * @param   string  $model
	 * @param   string  $name
	 * @return  void
	 */
	public function initialize(Jelly_Meta $meta, $model, $name)
	{
		if (empty($this->foreign))
		{
			$foreign_model = inflector::singular($name);
			$this->foreign = $foreign_model.'.'.Jelly::meta($foreign_model)->primary_key();
		}
		// Is it model.field?
		elseif (is_string($this->foreign) AND FALSE === strpos($this->foreign, '.'))
		{
			$foreign_model = $this->foreign;
			$this->foreign = $this->foreign.'.'.Jelly::meta($foreign_model)->primary_key();
		}

		// Create the default through connection
		if (empty($this->through) OR is_string($this->through))
		{
			if (empty($this->through))
			{
				$this->through = Jelly_Association_Collection::guess_through_table($foreign_model, $model);
			}

			$this->through = array(
				'model' => $this->through,
				'fields' => array(
					'our' => $meta->foreign_key(),
					'foreign' => Jelly::meta($foreign_model)->foreign_key(),
				)
			);
		}

		parent::initialize($meta, $model, $name);
	}

	public function join(Jelly_Builder $builder, $alias = NULL, $type = NULL)
	{
		return parent::join($builder, $alias, $type)
			->join($this->through(), $type)
			->on($this->through('our'), '=', "{$this->model}.:primary_key")
			->join($this->foreign(NULL, $alias), $type)
			->on($this->foreign('field', $alias), '=', $this->through('foreign'));
	}

	public function builder(Jelly_Model $model)
	{
		$builder = parent::builder($model)
			->join($this->through())
			->on($this->through('foreign'), '=', $this->foreign('field'))
			->where($this->through('our'), '=', $model->id());

		if ($this->extend)
		{
			$builder->extend($this->extend);
		}

		return $builder;
	}

	public function delete(Jelly_Model $model, $key)
	{
		Jelly::query($this->through())
			->where($this->through('our'), '=', $model->id())
			->delete($model->meta()->db());
	}

	public function after_save(Jelly_Model $model, $collection, $is_changed)
	{		
		if ($is_changed AND $collection AND $collection->changed())
		{
			list($old_ids, $new_ids) = $this->diff_collection_ids($model, $collection);

			if ($old_ids)
			{
				Jelly::query($this->through())
					->where($this->through('foreign'), 'IN', $old_ids)
					->delete($model->meta()->db());
			}

			if ($new_ids)
			{
				foreach ($new_ids as $new_id)
				{
					Jelly::query($this->through())
						 ->columns(array_values($this->through['fields']))
						 ->values(array($model->id(), $new_id))
						 ->insert($model->meta()->db());
				}
			}
		}
	}

} // End Kohana_Jelly_Association_ManyToMany