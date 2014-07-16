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

	/**
	 * Set this for polymorphic associations, this has to be the name of the opposite belongsto relation,
	 * so if the oposite relation was item->parent, then this will have to be 'items' => Jam::association('hasmany', array('as' => 'parent')
	 * If this option is set then the foreign_key default becomes "{$as}_id", and polymorphic_key to "{$as}_model"
	 * @var string
	 */
	public $as;

	/**
	 * The field in the opposite model that is used to linking to this one.
	 * It defaults to "{$foreign_model}_id", but you can custumize it
	 * @var string
	 */
	public $foreign_key = NULL;

	/**
	 * The field in the opposite model that is used by the polymorphic association to determine the model
	 * It defaults to "{$as}_model"
	 * @var string
	 */
	public $polymorphic_key = NULL;

	/**
	 * You can set this to the name of the opposite belongsto relation for optimization purposes
	 * @var string
	 */
	public $inverse_of = NULL;

	/**
	 * Optionally delete the item when it is removed from the association
	 * @var string
	 */
	public $delete_on_remove = NULL;

	/**
	 * Initialize foreign_key, as, and polymorphic_key with default values
	 *
	 * @param   string  $model
	 * @param   string  $name
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
			if ( ! $this->polymorphic_key)
			{
				$this->polymorphic_key = $this->as.'_model';
			}
		}
	}

	/**
	 * Return a Jam_Query_Builder_Join object to allow a query to join with this association
	 * @param  string $alias table name alias
	 * @param  string $type  join type (LEFT, NATURAL)
	 * @return Jam_Query_Builder_Join
	 */
	public function join($alias, $type = NULL)
	{
		$join = Jam_Query_Builder_Join::factory($alias ? array($this->foreign_model, $alias) : $this->foreign_model, $type)
			->context_model($this->model)
			->on($this->foreign_key, '=', ':primary_key');

		if ($this->is_polymorphic())
		{
			$join->on($this->polymorphic_key, '=', DB::expr(':model', array(':model' => $this->model)));
		}

		return $join;
	}

	public function collection(Jam_Model $model)
	{
		$collection = Jam::all($this->foreign_model);

		$collection->where($this->foreign_key, '=', $model->id());

		if ($this->is_polymorphic())
		{
			$collection->where($this->polymorphic_key, '=', $this->model);
		}

		return $collection;
	}

	/**
	 * Assign inverse associations to elements of arrays
	 * @param Jam_Validated $model
	 * @param mixed         $value
	 * @param boolean       $is_changed
	 */
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		if (($this->inverse_of OR $this->as) AND is_array($value))
		{
			foreach ($value as & $item)
			{
				if ($item instanceof Jam_Model)
				{
					$this->assign_item($item, $model->id(), $this->model, $model);
				}
			}
		}

		return $value;
	}

	/**
	 * Before the model is deleted, and the depenedent option is set, remove the dependent models
	 * @param  Jam_Model $model
	 */
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

	/**
	 * Remove items from this association (withought deleteing it) and persist the data in the database
	 * @param  Jam_Validated                $model
	 * @param  Jam_Array_Association $collection
	 */
	public function clear(Jam_Validated $model, Jam_Array_Association $collection)
	{
		foreach ($collection as $item)
		{
			$item->{$this->foreign_key} = NULL;
		}

		$this->nullify_query($model)->execute();
	}

	/**
	 * Generate a query to delete associated models in the database
	 * @param  Jam_Model $model
	 * @return Database_Query
	 */
	public function erase_query(Jam_Model $model)
	{
		$query = Jam_Query_Builder_Delete::factory($this->foreign_model)
			->where($this->foreign_key, '=', $model->id());

		if ($this->is_polymorphic())
		{
			$query->where($this->polymorphic_key, '=', $this->model);
		}

		return $query;
	}

	/**
	 * Generate a query to remove models from this association (without deleting them)
	 * @param  Jam_Model $model
	 * @return Database_Query
	 */
	public function nullify_query(Jam_Model $model)
	{
		$query = Jam_Query_Builder_Update::factory($this->foreign_model)
			->value($this->foreign_key, NULL)
			->where($this->foreign_key, '=', $model->id());

		if ($this->is_polymorphic())
		{
			$query
				->where($this->polymorphic_key, '=', $this->model)
				->value($this->polymorphic_key, NULL);
		}
		return $query;
	}

	/**
	 * Generate a query to remove models from the association (without deleting them), for specific ids
	 * @param  Jam_Model $model
	 * @param  array     $ids
	 * @return Database_Query
	 */
	public function remove_items_query(Jam_Model $model, array $ids)
	{
		switch ($this->delete_on_remove)
		{
			case TRUE:
			case Jam_Association::DELETE:
				foreach (Jam::all($this->foreign_model)->where_key($ids) as $item )
				{
					$item->delete();
				}
				$query = NULL;
			break;

			case Jam_Association::ERASE:
				$query = Jam_Query_Builder_Delete::factory($this->foreign_model)
					->where(':primary_key', 'IN', $ids);

				if ($this->is_polymorphic())
				{
					$query->value($this->polymorphic_key, NULL);
				}
			break;

			default:
				$query = Jam_Query_Builder_Update::factory($this->foreign_model)
					->where(':primary_key', 'IN', $ids)
					->value($this->foreign_key, NULL);

				if ($this->is_polymorphic())
				{
					$query->value($this->polymorphic_key, NULL);
				}
		}

		return $query;
	}

	/**
	 * Generate a query to add models from the association (without deleting them), for specific ids
	 * @param  Jam_Model $model
	 * @param  array     $ids
	 * @return Database_Query
	 */
	public function add_items_query(Jam_Model $model, array $ids)
	{
		$query = Jam_Query_Builder_Update::factory($this->foreign_model)
			->where(':primary_key', 'IN', $ids)
			->value($this->foreign_key, $model->id());

		if ($this->is_polymorphic())
		{
			$query->value($this->polymorphic_key, $this->model);
		}
		return $query;
	}


	protected function assign_item(Jam_Model $item, $foreign_key, $polymorphic_key, $inverse_of)
	{
		$item->{$this->foreign_key} = $foreign_key;

		if ($this->is_polymorphic())
		{
			$item->{$this->polymorphic_key} = $polymorphic_key;
		}

		if ($this->inverse_of)
		{
			$item->retrieved($this->inverse_of, $inverse_of);
		}

		if ($this->as)
		{
			$item->retrieved($this->as, $inverse_of);
		}

	}

	/**
	 * Set the foreign and polymorphic keys on an item when its requested from the associated collection
	 *
	 * @param  Jam_Model $model
	 * @param  Jam_Model $item
	 */
	public function item_get(Jam_Model $model, Jam_Model $item)
	{
		$this->assign_item($item, $model->id(), $this->model, $model);
	}

	/**
	 * Set the foreign and polymorphic keys on an item when its set to the associated collection
	 *
	 * @param  Jam_Model $model
	 * @param  Jam_Model $item
	 */
	public function item_set(Jam_Model $model, Jam_Model $item)
	{
		$this->assign_item($item, $model->id(), $this->model, $model);
	}

	/**
	 * Unset the foreign and polymorphic keys on an item when its removed from the associated collection
	 *
	 * @param  Jam_Model $model
	 * @param  Jam_Model $item
	 */
	public function item_unset(Jam_Model $model, Jam_Model $item)
	{
		$this->assign_item($item, NULL, NULL, NULL);
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
