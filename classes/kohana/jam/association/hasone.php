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
abstract class Kohana_Jam_Association_HasOne extends Jam_Association {

	public $as = NULL;

	public $foreign_default = 0;

	public $foreign_key = NULL;

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
			$this->polymorphic_key = $this->as.'_model';
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

	protected function _find_item($foreign_model, $key)
	{
		return Jam_Query_Builder_Collection::factory($foreign_model)->limit(1)->where(':unique_key', '=', $key)->current();
	}

	public function get(Jam_Validated $model, $value, $is_changed)
	{
		if ($is_changed)
		{
			if ($value instanceof Jam_Validated OR ! $value)
				return $value;

			list($key, $foreign_model) = Jam_Association::value_to_key_and_model($value);

			$item = $this->_find_item($foreign_model, $key);

			$item->{$this->foreign_key} = $model->id();
			if ($this->is_polymorphic())
			{
				$item->{$this->polymorphic} = $model->meta()->model();
			}

			if (is_array($value))
			{
				$item->set($value);
			}
			return $item;
		}
		else
		{
			$builder = Jam_Query_Builder_Dynamic::factory($this->foreign_model)
				->limit(1)
				->where($this->foreign_key, '=', $model->id());
		}

		if ($this->is_polymorphic())
		{
			$builder->where($this->polymorphic_key, '=', $model->meta()->model());
		}

		return $builder->current();
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

	public function model_after_check(Jam_Model $model, Jam_Event_Data $data, $changed)
	{
		if ($value = Arr::get($changed, $this->name) AND Jam_Association::value_is_changed($value))
		{
			if ( ! $model->{$this->name}->is_validating() AND ! $model->{$this->name}->check())
			{
				$model->errors()->add($this->name, 'association', array(':errors' => $model->{$this->name}->errors()));
			}
		}
	}

	public function model_after_save(Jam_Model $model, Jam_Event_Data $data, $changed)
	{
		if ($value = Arr::get($changed, $this->name))
		{
			$nullify = $this->nullify_query($model);

			if (Jam_Association::value_is_changed($value) AND $item = $model->{$this->name})
			{
				$item->save();

				$nullify->where(':primary_key', '!=', $item->id());
			}
			else
			{
				list($key) = Jam_Association::value_to_key_and_model($value);

				$query = Jam_Query_Builder_Update::factory($this->foreign_model)
					->where(':unique_key', '=', $key)
					->value($this->foreign_key, $model->id());

				if ($this->is_polymorphic())
				{
					$query
						->value($this->polymorphic_key, $model->meta()->model());
				}
				$query->execute();
				$nullify->where(':primary_key', '!=', $key);
			}
			$nullify->execute();
		}
	}
} // End Kohana_Jam_Association_HasOne
