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
		// Empty? The model defaults to the the singularized name
		// of this field, and the field defaults to this field's model's foreign key
		if (empty($this->foreign))
		{
			$this->foreign = Inflector::singular($name).'.'.Jam::meta($meta->model())->foreign_key();
		}
		// We have a model? Default the field to this field's model's foreign key
		elseif (FALSE === strpos($this->foreign, '.'))
		{
			$this->foreign = $this->foreign.'.'.Jam::meta($meta->model())->foreign_key();
		}

		parent::initialize($meta, $name);

		if ( ! $this->foreign_model)
		{
			$this->foreign_model = $name;
		}

		if ( ! $this->foreign_key)
		{
			$this->foreign_key = $this->model.'_id';
		}

		if ($this->as)
		{
			if ( ! is_string($this->as))
			{
				$this->as = $this->model;
			}
			$this->foreign['as'] = $this->as.'_model';
			$this->foreign['field'] = $this->as.'_id';
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
		return Jam_Query_Builder_Join::factory($alias ? array($this->foreign_model, $alias) : $this->foreign_model, $type)
			->context_model($this->model)
			->on($this->foreign_key, '=', ':primary_key');
	}


	public function attribute_join(Jam_Builder $builder, $alias = NULL, $type = NULL)
	{
		$join = $builder
			->join($this->foreign(NULL, $alias), $type)
			->on($this->foreign('field', $alias), '=', "{$this->model}.:primary_key");

		if ($this->is_polymorphic())
		{
			$join->on($this->foreign('as', $alias), '=', DB::expr('"'.$this->model.'"'));
		}

		return $join;
	}

	public function builder(Jam_Model $model)
	{
		if ( ! $model->loaded())
			throw new Kohana_Exception("Cannot create Jam_Builder on :model->:name because model is not loaded", array(':name' => $this->name, ':model' => $model->meta()->model()));


		$builder = Jam::query($this->foreign())
			->limit(1)
			->where($this->foreign('field'), '=', $model->id());

		if ($this->as)
		{
			$builder->where($this->foreign('as'), '=', $model->meta()->model());
		}

		return $builder;
	}

	public function get(Jam_Validated $model, $value, $is_changed)
	{
		$foreign_model = $model->loaded() ? $this->builder($model)->select() : Jam::factory($this->foreign());

		return $this->assign_relation($model, $foreign_model);
	}

	public function set(Jam_Validated $model, $value, $is_changed)
	{
		$item = $this->model_from_array($value);
		return $this->assign_relation($model, $item);
	}

	public function assign_relation(Jam_Model $model, $item)
	{
		$item = parent::assign_relation($model, $item);

		$item->set($this->foreign['field'], $model->id());

		if ($this->is_polymorphic())
		{
			$item->set($this->foreign['as'], $model->meta()->model());
		}

		return $item;
	}

	public function model_before_delete(Jam_Model $model)
	{
		switch ($this->dependent) 
		{
			case Jam_Association::DELETE:
				$model->{$this->name}->delete();
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

		if ($this->is_polymorphic())
		{
			$builder->value($this->foreign('as'), NULL);
		}

		return $builder;
	}

	public function model_after_check(Jam_Model $model)
	{
		if ($model->changed($this->name) AND $model->{$this->name} AND ! $model->{$this->name}->is_validating() AND ! $model->{$this->name}->check())
		{
			$model->errors()->add($this->name, 'association', array(':errors' => $model->{$this->name}->errors()));
		}
	}


	public function model_after_save(Jam_Model $model)
	{
		if ($model->changed($this->name))
		{
			$nullify = $this->nullify_builder($model);

			if ($new_item = $model->{$this->name})
			{
				$new_item->set($this->foreign['field'], $model->id());

				if ($this->is_polymorphic())
				{
					$new_item->set($this->foreign['as'], $model->meta()->model());
				}

				$new_item->save();
				
				$nullify->where($this->foreign().':primary_key', '!=', $new_item->id());
			}

			$nullify->update();
		}
	}
} // End Kohana_Jam_Association_HasOne
