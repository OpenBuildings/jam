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
abstract class Kohana_Jam_Association_Hasmany extends Jam_Association_Collection {

	public $as;

	public $foreign_key = NULL;

	public $polymorphic_key = NULL;

	public $inverse_of = NULL;

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
			$this->foreign_key = $this->as.'_id';
			$this->polymorphic_key = $this->as.'_model';
		}
	}

	public function join($alias, $type = NULL)
	{
		$join = Jam_Query_Builder_Join::factory($alias ? array($this->foreign_model, $alias) : $this->foreign_model, $type)
			->context_model($this->model)
			->on($this->foreign_key, '=', ':primary_key');

		if ($this->is_polymorphic())
		{
			$join->on($this->polymorphic_key, '=', DB::expr('"'.$this->model.'"'));
		}

		return $join;
	}

	public function set(Jam_Validated $model, $value, $is_changed)
	{
		if ($this->inverse_of AND is_array($value))
		{
			foreach ($value as & $item) 
			{
				if ($item instanceof Jam_Validated)
				{
					$item->{$this->inverse_of} = $model;
				}
			}
		}

		return $value;
	}

	public function get(Jam_Validated $model, $value, $is_changed)
	{
		$builder = Jam_Query_Builder_Dynamic::factory($this->foreign_model)
			->where($this->foreign_key, '=', $model->id());

		if ($this->is_polymorphic())
		{
			$builder->where($this->polymorphic_key, '=', $model->meta()->model());
		}

		if ($is_changed)
		{
			$builder->set($value);
		}

		if ($this->inverse_of)
		{
			$builder->assign_after_load(array($this->inverse_of => $model));
		}

		return $builder;
	}

	public function erase_query(Jam_Model $model)
	{
		$query = Jam_Query_Builder_Delete::factory($this->foreign_model)
			->where($this->foreign_key, '=', $model->id());

		if ($this->is_polymorphic())
		{
			$query->where($this->polymorphic_key, '=', $model->meta()->model());
		}

		return $query;
	}

	public function nullify_query(Jam_Model $model)
	{
		$query = Jam_Query_Builder_Update::factory($this->foreign_model)
			->value($this->foreign_key, NULL)
			->where($this->foreign_key, '=', $model->id());

		if ($this->is_polymorphic())
		{
			$query
				->where($this->polymorphic_key, '=', $model->meta()->model())
				->value($this->polymorphic_key, NULL);
		}
		return $query;
	}


	public function remove_items_query(array $ids)
	{
		$query = Jam_Query_Builder_Update::factory($this->foreign_model)
			->where(':primary_key', 'IN', $ids)
			->value($this->foreign_key, NULL);

		if ($this->is_polymorphic())
		{
			$query->value($this->polymorphic_key, NULL);
		}

		return $query; 
	}

	public function add_items_query(array $ids, Jam_Model $model)
	{
		$query = Jam_Query_Builder_Update::factory($this->foreign_model)
			->where(':primary_key', 'IN', $ids)
			->value($this->foreign_key, $model->id());

		if ($this->is_polymorphic())
		{
			$query->value($this->polymorphic_key, $model->meta()->model());
		}
		return $query;
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
				$this->erase_query($model)->execute();
			break;

			case Jam_Association::NULLIFY:
				$this->nullify_query($model)->execute();
			break;
		}
	}

	public function save(Jam_Model $model, Jam_Query_Builder_Dynamic $collection)
	{
		if ($old_ids = array_values(array_diff($collection->original_ids(), $collection->ids())))
		{
			$this->remove_items_query($old_ids)->execute();
		}
		
		if ($new_ids = array_values(array_diff($collection->ids(), $collection->original_ids())))
		{
			$this->add_items_query($new_ids, $model)->execute();
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
} // End Kohana_Jam_Association_HasMany
