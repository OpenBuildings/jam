<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles belongs to relationships
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association_BelongsTo extends Jam_Association {

	/**
	 * Indicates whether this is a polymorphic association. Will add the polymorphic field,
	 * named <name>_model, if you set this as a string you can change the name of the field to it.
	 * @var boolean|string
	 */
	public $polymorphic = FALSE;

	/**
	 * Default value for polymorphic model column. Allowes using polymorphic associations as normal belnogs to
	 * @var string
	 */
	public $polymorphic_default_model = NULL;

	/**
	 * The name of the actual field holding the id of the associated model. Defaults to
	 * <name>_id
	 * @var string
	 */
	public $column = '';

	public $foreign_key = NULL;

	/**
	 * Automatically sets foreign to sensible defaults.
	 *
	 * @param   string  $model
	 * @param   string  $name
	 * @return  void
	 */
	public function initialize(Jam_Meta $meta, $model, $name)
	{
		// Default to the foreign model's primary key
		if (empty($this->column))
		{
			$this->column = $name.'_id';
		}
		
		if ($this->column === $name)
			throw new Kohana_Exception("In association :name for model :model - invalid column name. Field and Association cannot be the same name", array(
					':model' => $model,
					':name' => ':name'
				));
		
		$meta->field($this->column, Jam::field('integer', array(
			'default' => NULL,
			'allow_null' => TRUE,
			'convert_empty' => TRUE
		)));


		// We initialize a bit earlier as we want to modify the $fthis->oreign array
		parent::initialize($meta, $model, $name);

		if ( ! $this->foreign_key)
		{
			$this->foreign_key = $this->foreign_model.'_id';
		}

		if ($this->is_polymorphic())
		{
			if ( ! is_string($this->polymorphic))
			{
				$this->polymorphic = $name.'_model';
			}

			$meta->field($this->polymorphic, Jam::field('string'));
		}

		// Count Cache
		if ($this->inverse_of)
		{
			// $this->extension('countcache', Jam::extension('countcache'));
		}
	}

	public function is_polymorphic()
	{
		return (bool) $this->polymorphic;
	}

	public function model_after_check(Jam_Model $model, $is_changed)
	{
		if ($is_changed AND $model->{$this->name} AND ! $model->{$this->name}->is_validating() AND ! $model->{$this->name}->check())
		{
			$model->errors()->add($this->name, 'association', array(':errors' => $model->{$this->name}->errors()));
		}
	}

	public function join($table, $type = NULL)
	{
		if ($this->is_polymorphic())
		{
			$foreign_model = is_array($table) ? $table[1] : $this->polymorphic_default_model;

			if ( ! $foreign_model)
				throw new Kohana_Exception('Jam does not join automatically polymorphic belongsto associations!');

			$join = Jam_Query_Builder_Join::factory($foreign_model, $type)
				->on($this->polymorphic, '=', DB::expr("'$foreign_model'"));
		}
		else
		{
			$join = Jam_Query_Builder_Join::factory($table, $type);
		}

		return $join
			->context_model($this->model)
			->on(':primary_key', '=', $this->foreign_key);
	}

	public function query_builder(Jam_Model $model)
	{
		$builder = new Jam_Query_Builder_Collection($this->foreign_model($model));

		return $builder
			->limit(1)
			->where(':primary_key', '=', $model->id());
	}

	public function foreign_model(Jam_Model $model)
	{
		if ($this->is_polymorphic())
		{
			$foreign_model = $model->{$this->polymorphic} ? $model->{$this->polymorphic} : $this->polymorphic_default_model;

			if ( ! $foreign_model)
				throw new Kohana_Exception('Could not find the foreign_model of the polymorphic association');
		}
		else
		{
			$foreign_model = $this->foreign_model;
		}

		return $foreign_model;
	}

	public function get(Jam_Validated $model, $value, $is_changed)
	{
		$item = $this->query_builder($model)->offsetGet(0);

		if ( ! $item)
			return NULL;

		$this->assign_relation($foreign_model);

		return $foreign_model;
	}

	public function set(Jam_Validated $model, $new_item, $is_changed)
	{
		if ($new_item)
		{
			$new_item = $this->model_from_array($new_item);

			if ($this->is_polymorphic())
			{
				$model->set($this->polymorphic, $new_item->meta()->model());
			}

			if ($new_item->loaded())
			{
				$model->set($this->column, $new_item->id());
			}
		}
		else
		{
			$model->set($this->column, NULL);

			if ($this->polymorphic)
			{
				$model->set($this->polymorphic, NULL);
			}
		}

		return $new_item;
	}

	public function assign_relation(Jam_Model $model, $item)
	{
		$item = parent::assign_relation($model, $item);
		$model->set($this->name, $item);
		return $item;
	}

	public function model_before_save(Jam_Model $model)
	{
		if ($model->changed($this->name) AND $new_item = $model->{$this->name})
		{
			$this->preserve_item_changes($new_item);
			$this->set($model, $new_item, TRUE);
		}
	}

	public function model_before_delete(Jam_Model $model)
	{
		if ($this->dependent == Jam_Association::DELETE)
		{
			$model->{$this->name}->delete();
		}
		elseif ($this->dependent == Jam_Association::ERASE)
		{
			$this->builder($model)->delete();	
		}
	}
} // End Kohana_Jam_Association_BelongsTo
