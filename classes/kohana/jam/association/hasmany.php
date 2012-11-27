<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Handles has one to relationships
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association_HasMany extends Jam_Association_Collection {

	public $as;

	public $foreign_key = NULL;

	public $polymorphic_key = NULL;

	public $count_cache = NULL;

	/**
	 * Automatically sets foreign to sensible defaults.
	 *
	 * @param   string  $model
	 * @param   string  $name
	 * @return  void
	 */
	public function initialize(Jam_Meta $meta, $name)
	{

		parent::initialize($meta, $name);

		if ( ! $this->foreign_key)
		{
			$this->foreign_key = $this->model.'_id';
		}

		// Polymorphic associations
		if ($this->as)
		{
			$this->polymorphic_key = $this->as.'_model';
		}

		// Count Cache
		if ($this->count_cache)
		{
			if ($this->is_polymorphic())
				throw new Kohana_Exception('Cannot use count cache on polymorphic associations');
			
			if ($this->count_cache === TRUE)
			{
				$this->count_cache = $this->name.'_count';
			}

			$meta->field($this->count_cache, Jam::field('integer', array('default' => 0, 'allow_null' => FALSE)));

			// $this->extension('countcache', Jam::extension('countcache'));
		}
	}

	public function join($alias, $type = NULL)
	{
		$join = Jam_Query_Builder_Join::factory($alias ? array($this->foreign_model, $alias) : $this->foreign_model, $type)
			->context_model($this->model)
			->on($this->foreign_key, '=', ':primary_key');

		if ($this->is_polymorphic())
		{
			$join->on($this->polymorphic_key, '=', "{$this->model}.:primary_key");
		}

		return $join;
	}

	public function builder(Jam_Model $model)
	{
		if ( ! $model->loaded())
			throw new Kohana_Exception("Cannot create Jam_Builder on :model->:name because model is not loaded", array(':name' => $this->name, ':model' => $model->meta()->model()));

		$builder = Jam::query($this->foreign())
			->where($this->foreign('field'), '=', $model->id());

		if ($this->as)
		{
			$builder->where($this->foreign('as'), '=', $model->meta()->model());
		}

		return $builder;
	}

	public function model_before_delete(Jam_Model $model)
	{
		switch ($this->dependent) 
		{
			case Jam_Association::DELETE:
				foreach ($model->{$this->name} as $item) 
				{
					$item->delete();
				}
			break;
			case Jam_Association::ERASE:
				$this->builder($model)->delete();
			break;
			case Jam_Association::NULLIFY:
				$this->nullify_builder($model)->update();
			break;
		}
	}

	public function nullify_builder(Jam_Model $model)
	{
		$builder = $this->builder($model)
			->value($this->foreign('field'), $this->foreign_default);

		if ($this->as)
		{
			$builder->value($this->foreign('as'), NULL);
		}

		return $builder;
	}


	public function model_after_save(Jam_Model $model)
	{
		if ($model->changed($this->name) AND $collection = $model->{$this->name} AND $collection->changed())
		{
			list($old_ids, $new_ids) = $this->diff_collection_ids($model, $collection);

			if (array_filter($old_ids))
			{
				$this->nullify_builder($model)->key($old_ids)->update();
			}
			
			if ($new_ids)
			{
				$new_items_builder = Jam::query($this->foreign())
					->key($new_ids)
					->value($this->foreign('field'), $model->id());

				if ($this->as)
				{
					$new_items_builder->value($this->foreign('as'), $model->meta()->model());
				}

				$new_items_builder->update();
			}
		}
	}

	/**
	 * See if the association is polymorphic
	 * @return boolean 
	 */
	public function is_polymorphic()
	{
		return (bool) $this->as;
	}

	public function assign_relation(Jam_Model $model, $item)
	{
		if ($item instanceof Jam_Model)
		{
			$item->set($this->foreign['field'], $model->id());

			if ($this->is_polymorphic())
			{
				$item->set($this->foreign['as'], $model->meta()->model());
			}
		}
		return parent::assign_relation($model, $item);
	}


} // End Kohana_Jam_Association_HasMany
