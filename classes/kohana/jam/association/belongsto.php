<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles belongs to relationships
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2010-2011 OpenBuildings
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
	 * The name of the actual field holding the id of the associated model. Defaults to
	 * <name>_id
	 * @var string
	 */
	public $column = '';

	/**
	 * Default value of the column in the database
	 * @var integer
	 */
	public $default = NULL;

	/**
	 * Should we allow NULL values for the foreign key
	 * @var boolean
	 */
	public $allow_null = TRUE;

	/**
	 * Whether empty values for the foreign key should be converted to NULL
	 * @var boolean
	 */
	public $convert_empty = TRUE;
	

	/**
	 * Automatically sets foreign to sensible defaults.
	 *
	 * @param   string  $model
	 * @param   string  $name
	 * @return  void
	 */
	public function initialize(Jam_Meta $meta, $model, $name)
	{
		// Default to the name of the column
		if (empty($this->foreign))
		{
			$this->foreign = $name.'.:primary_key';
		}
		// Is it model.field?
		elseif (FALSE === strpos($this->foreign, '.'))
		{
			$this->foreign = $this->foreign.'.:primary_key';
		}

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
		
		/**
		 * Assign the accual field in the database. Default value can be set with $this->default
		 */
		$meta->field($this->column, Jam::field('integer', array(
			'default' => $this->default,
			'allow_null' => $this->allow_null,
			'convert_empty' => $this->convert_empty
		)));

		// We initialize a bit earlier as we want to modify the $fthis->oreign array
		parent::initialize($meta, $model, $name);

		if ($this->is_polymorphic())
		{
			if ( ! is_string($this->polymorphic))
			{
				$this->polymorphic = $name.'_model';
			}
			$this->foreign['model'] = $this->polymorphic;

			$meta->field($this->polymorphic, Jam::field('string'));
		}
	}

	public function is_polymorphic()
	{
		return (bool) $this->polymorphic;
	}

	public function join(Jam_Builder $builder, $alias = NULL, $type = NULL)
	{
		$join = parent::join($builder, $alias, $type);

		if ($this->is_polymorphic())
		{
			if ( ! $alias)
				throw new Kohana_Exception('Jam does not join automatically polymorphic belongsto associations!');

			$join
				->join($alias, $type)
				->on($this->foreign('field', $alias), '=', $this->model.'.'.$this->column)
				->on($this->polymorphic, '=', DB::expr('"'.$alias.'"'));
		}
		else
		{
			$join
				->join($this->foreign(NULL, $alias), $type)
				->on($this->foreign('field', $alias), '=', $this->model.'.'.$this->column);
		}

		return $join;
	}

	public function builder(Jam_Model $model)
	{
		if ($this->is_polymorphic())
		{
			$parent_model = $model->{$this->polymorphic};

			if ( ! $parent_model)
				return NULL;

			$builder = Jam::query($parent_model)
				->limit(1)
				->select_column(array("$parent_model.*"))
				->where($parent_model.'.'.$this->foreign['field'], '=', $model->{$this->column});

			$builder = $this->apply_conditions($builder);

			if ($this->extend)
			{
				$builder->extend($this->extend);
			}
		}
		else
		{
			$builder = parent::builder($model)
				->limit(1)
				->where($this->foreign('field'), '=', $model->{$this->column});
		}

		return $builder;
	}

	public function after_check(Jam_Model $model, Jam_Validation $validation, $new_item)
	{
		if ($this->required AND ( ! (($new_item AND $new_item instanceof Jam_Model) OR ($model->{$this->column} > 0))))
		{
			$validation->error($this->name, 'required');
		}
		
		parent::after_check($model, $validation, $new_item);
	}

	public function get(Jam_Model $model)
	{
		if ($builder = $this->builder($model))
			return $builder->find();

		if ($this->is_polymorphic())
			return NULL;

		$foreign_model = Jam::factory($this->foreign());

		$this->assign_relation($foreign_model);

		return $foreign_model;
	}

	public function set(Jam_Model $model, $new_item)
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

	public function create(Jam_Model $model, array $attributes = NULL)
	{
		$new_item = parent::create($model, $attributes);
		$model->set($this->column, $new_item->id());
		return $new_item;
	}

	public function before_save(Jam_Model $model, $new_item, $is_changed)
	{
		if ($is_changed)
		{
			if ($new_item)
			{
				$this->preserve_item_changes($new_item);
				$this->set($model, $new_item);
			}
		}
	}
	
	public function delete(Jam_Model $model, $key)
	{
		if ($this->dependent == Jam_Association::DELETE)
		{
			$this->get($model)->delete();
		}
		elseif ($this->dependent == Jam_Association::ERASE)
		{
			$this->builder($model)->delete();	
		}
	}
} // End Kohana_Jam_Association_BelongsTo
