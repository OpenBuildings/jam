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
abstract class Kohana_Jam_Association_Hasone extends Jam_Association {

	/**
	 * Set this for polymorphic association, this has to be the name of the opposite belongsto relation,
	 * so if the oposite relation was item->parent, then this will have to be 'ites' => Jam::association('hasone, array('as' => 'parent')
	 * If this option is set then the foreign_key default becomes "{$as}_id", and polymorphic_key to "{$as}_model"
	 * @var string
	 */
	public $as = NULL;

	/**
	 * The foreign key
	 * @var string
	 */
	public $foreign_key = NULL;

	public $inverse_of = NULL;

	public $polymorphic_key = NULL;

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

		if ( ! $this->foreign_model)
		{
			$this->foreign_model = $name;
		}

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
	 * Load associated model (from database or after deserialization)
	 * @param  Jam_Validated $model
	 * @param  mixed         $value
	 * @return Jam_Model
	 */
	public function load_fields(Jam_Validated $model, $value)
	{
		if (is_array($value))
		{
			$value = Jam::build($this->foreign_model)->load_fields($value);
		}

		if ($value instanceof Jam_Model AND $this->inverse_of)
		{
			$value->retrieved($this->inverse_of, $model);
		}

		return $value;
	}

	/**
	 * Return a Jam_Query_Builder_Join object to allow a query to join with this association
	 *
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

	/**
	 * Get the belonging model for this association using the foreign key,
	 * if the data was changed, use the key from the changed data.
	 * Assign inverse_of
	 *
	 * @param  Jam_Validated $model
	 * @param  mixed         $value      changed data
	 * @param  boolean       $is_changed
	 * @return Jam_Model
	 */
	public function get(Jam_Validated $model, $value, $is_changed)
	{
		if ($is_changed)
		{
			if ($value instanceof Jam_Validated OR ! $value)
				return $value;

			$key = Jam_Association::primary_key($this->foreign_model, $value);

			if ($key)
			{
				$item = $this->_find_item($this->foreign_model, $key);
			}
			elseif (is_array($value))
			{
				$item = Jam::build($this->foreign_model);
			}
			else
			{
				$item = NULL;
			}

			if ($item AND is_array($value))
			{
				$item->set($value);
			}
		}
		else
		{
			$item = $this->_find_item($this->foreign_model, $model);
		}

		return $this->set($model, $item, $is_changed);
	}

	public function set(Jam_Validated $model, $value, $is_changed)
	{
		if ($value instanceof Jam_Model)
		{
			if ($this->is_polymorphic())
			{
				$value->{$this->polymorphic_key} = $model->meta()->model();
			}

			if ($model->loaded())
			{
				$value->{$this->foreign_key} = $model->id();
			}

			if ($this->as)
			{
				$value->retrieved($this->as, $model);
			}

			if ($this->inverse_of)
			{
				$value->retrieved($this->inverse_of, $model);
			}
		}

		return $value;
	}

	public function build(Jam_Validated $model, array $attributes = NULL)
	{
		$item = Jam::build($this->foreign_model, $attributes);

		$this->set($model, $item, TRUE);

		return $item;
	}

	/**
	 * Perform validation on the belonging model, if it was changed.
	 * @param  Jam_Model      $model
	 * @param  Jam_Event_Data $data
	 * @param  array         $changed
	 */
	public function model_after_check(Jam_Model $model, Jam_Event_Data $data, $changed)
	{
		if (($value = Arr::get($changed, $this->name)) AND Jam_Association::is_changed($value))
		{
			if ( ! $model->{$this->name}->is_validating() AND ! $model->{$this->name}->check())
			{
				$model->errors()->add($this->name, 'association', array(':errors' => $model->{$this->name}->errors()));
			}
		}
	}

	/**
	 * Save the related model after the main model, if it was changed
	 * Only save related model if it has been changed, and is not in a process of saving itself
	 *
	 * @param  Jam_Model      $model
	 * @param  Jam_Event_Data $data
	 * @param  boolean        $changed
	 */
	public function model_after_save(Jam_Model $model, Jam_Event_Data $data, $changed)
	{
		$nullify_query = $this->update_query($model, NULL, NULL);

		if (($value = Arr::get($changed, $this->name)))
		{
			if (Jam_Association::is_changed($value) AND ($item = $model->{$this->name}))
			{
				if ( ! $item->is_saving())
				{
					$this->set($model, $item, TRUE)->save();
				}
				if ($item->id())
				{
					$nullify_query->where('id', '!=', $item->id())->execute();
				}
			}
			else
			{
				$key = Jam_Association::primary_key($this->foreign_model, $value);

				$query = Jam_Query_Builder_Update::factory($this->foreign_model)
					->where(':unique_key', '=', $key)
					->value($this->foreign_key, $model->id());

				if ($this->is_polymorphic())
				{
					$query
						->value($this->polymorphic_key, $model->meta()->model());
				}
				$nullify_query->execute();
				$query->execute();
			}
		}
		elseif (array_key_exists($this->name, $changed))
		{
			$nullify_query->execute();
		}
	}

	/**
	 * Delete related model if it has been assigned as dependent
	 * If dependent is Jam_Association::DELETE - execute the delete method (and all events)
	 * IF dependent is Jam_Association::ERASE - simply remove from database without executing any events (faster)
	 * @param  Jam_Model $model
	 */
	public function model_before_delete(Jam_Model $model)
	{
		switch ($this->dependent)
		{
			case Jam_Association::DELETE:
				if ($model->{$this->name})
				{
					$model->{$this->name}->delete();
				}
			break;

			case Jam_Association::ERASE:
				$this->query_builder('delete', $model)->execute();
			break;

			case Jam_Association::NULLIFY:
				$this->update_query($model, NULL, NULL)->execute();
			break;
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

	protected function _find_item($foreign_model, $key)
	{
		if ( ! $key)
			return;

		if ($key instanceof Jam_Model)
		{
			if ( ! $key->loaded())
				return;

			$query = $this->query_builder('all', $key);
		}
		else
		{
			$query = new Jam_Query_Builder_Collection($foreign_model);
			$query
				->where(':unique_key', '=', $key)
				->limit(1);
		}

		return $query->current();
	}

	public function query_builder($type, Jam_Model $model)
	{
		$query = call_user_func("Jam::{$type}", $this->foreign_model)
			->where($this->foreign_key, '=', $model->id());

		if ($this->is_polymorphic())
		{
			$query->where($this->polymorphic_key, '=', $model->meta()->model());
		}

		return $query;
	}

	public function update_query(Jam_Model $model, $new_id, $new_model)
	{
		$query = $this->query_builder('update', $model)
			->value($this->foreign_key, $new_id);

		if ($this->is_polymorphic())
		{
			$query->value($this->polymorphic_key, $new_model);
		}

		return $query;
	}
}
