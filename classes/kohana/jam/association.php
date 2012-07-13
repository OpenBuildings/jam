<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Core class that all associations must extend
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association extends Jam_Attribute {

	const NULLIFY  = 'nullify';
	const ERASE    = 'erase';
	const DELETE   = 'delete';

	/**
	 * @var string the foreign relationship model and field - model or model.field
	 */
	public $foreign = '';

	/**
	 * This is used to define the inverse association (has_many/has_one -> belongs_to)
	 * 
	 * @var string
	 */
	public $inverse_of = NULL;

	/**
	 * And Arry of conditions to be applied on the builder for this associaiton,
	 * Example array('where' => array('id', '=', '2'), 'or_where' => array('name', '=', 'name'))
	 * 
	 * @var array
	 */
	public $conditions;

	/**
	 * If set to true, will delete the association object when this one gets deleted
	 * possible values are Jam_Association::DELETE and Jam_Association::ERASE and Jam_Association::NULLIFY
	 * Jam_Association::DELETE will run the delete event of the associated model, Jam_Association::ERASE will not
	 * 
	 * @var boolean|string
	 */
	public $dependent = FALSE;

	public $extend;

	/**
	 * A boolean flag for validating the existance of this association, if it's not set, will result in a validation error
	 * 
	 * @var boolean
	 */
	public $required = FALSE;
	
	/**
	 * Indicates whether the foreign model should be touched (updated) after the association has been updated.
	 * @var boolean
	 */
	public $touch = FALSE;

	/**
	 * Default initialize set model, name and foreign variables
	 * 
	 * @param  Jam_Meta $meta
	 * @param  string     $model   the model name 
	 * @param  string     $name    the name of the association 
	 * @return NULL            
	 */
	public function initialize(Jam_Meta $meta, $model, $name)
	{
		parent::initialize($meta, $model, $name);

		if ( ! is_string($this->foreign))
			throw new Kohana_Exception("Cannot initialize association :association for model :model: foreign field must be a string",
				array(':association' => $name, ':model' => $model));

		// Convert $this->foreign to an array for easier access
		$this->foreign = array_combine(array('model', 'field'), explode('.', $this->foreign));

		if ($this->touch)
		{
			$this->touch = ($this->touch === TRUE) ? 'updated_at' : $this->touch;

			$this->extension('touch', Jam::extension('touch'));
		}

		$this->extension('general', Jam::extension('general'));
	}

	/**
	 * This method should perform a check after the parent model is checked
	 * 
	 * @param  Jam_Model $model
	 * @param  Jam_Validation $validation
	 * @param  boolean $new_item  is the association new
	 * @return NULL                   
	 */
	public function attribute_after_check(Jam_Model $model, Jam_Validation $validation, $new_item)
	{
		if ($new_item AND ! $new_item->is_validating() AND ! $new_item->check())
		{
			$validation->error($this->name, 'validation');
		}
	}

	/**
	 * Convert an array to a model, mostly for mass assignment and nested forms. 
	 * Handle polymorphic associations with one more level of nesting the arrays. 
	 * Load and update objects if they pass an id in the array
	 * 
	 * @param  array|string|Jam_Model  $array       
	 * @return Jam_Model               The converted item
	 */
	public function model_from_array($array)
	{
		if ($array instanceof Jam_Model)
			return $array;

		if ($this->is_polymorphic() AND $this instanceof Jam_Association_BelongsTo)
		{
			if ( ! is_array($array))
				throw new Kohana_Exception('Model :model, association :name is polymorphic so you can only mass assign arrays', 
					array(':model' => $this->model, ':name' => $this->name));
				
			$foreign_model = key($array);
			$array = reset($array);

			if ( ! Jam::meta($foreign_model))
				throw new Kohana_Exception('Model :model, association :name is polymorphic and in mass assignment, model ":new_model" does not exist or the array is not constructed properly ( must be array("model" => aray("fields"...)) )', 
					array(':model' => $this->model, ':name' => $this->name, ':new_model' => $foreign_model));
		}
		else
		{
			$foreign_model = $this->foreign();
		}

		// Handle cases where there is an ID available - we load them first and then set the new attributes
		if (is_array($array)) 
		{
			$key = Arr::get($array, Jam::meta($foreign_model)->primary_key());
			$item = Jam::factory($foreign_model, $key)->set($array);
		}
		else
		{
			$item = Jam::factory($foreign_model, $array);
		}

		return $item;
	}

	/**
	 * See if the association is polymorphic. 
	 * This is overloaded in the associations themselves
	 * 
	 * @return boolean 
	 */
	public function is_polymorphic()
	{
		return FALSE;
	}

	/**
	 * Help the builder join the association
	 * 
	 * @param  Jam_Builder $builder
	 * @param  string $alias
	 * @param  string $type To be used by all joins
	 * @return $this             
	 */
	public function join(Jam_Builder $builder, $alias = NULL, $type = NULL)
	{
		return $this->trigger('join', $builder, $alias, $type);
	}

	/**
	 * Create the builder required to load the associated models
	 * 
	 * @param  Jam_Model $model parent model
	 * @return Jam_Builder             
	 */
	public function builder(Jam_Model $model)
	{
		return $this->trigger('builder', $model);
	}

	public function delete(Jam_Model $model, $value)
	{
		return $this->trigger('delete', $model, $value);
	}

	/**
	 * This method is executed for each child model so that it's values are properly assigned
	 * 
	 * @param  Jam_Model $model parent model
	 * @param  Jam_Model|mixed  $item  child model item
	 * @return Jam_Model        $item
	 */
	public function assign_relation(Jam_Model $model, $item)
	{
		return $this->assign_inverse($model, $item);
	}

	/**
	 * This method is used to create a child model, that is connected to the parent model with the association
	 * @param  Jam_Model $model      parent model
	 * @param  array       $attributes attributes to set for the new model
	 * @return Jam_Model             
	 */
	public function build(Jam_Model $model, array $attributes = NULL)
	{
		return $this->assign_relation($model, Jam::factory($this->foreign())->set($attributes));
	}

	/**
	 * The same as build, but save the model to the database
	 * @param  Jam_Model $model      parent model
	 * @param  array       $attributes attributes to set for the new model
	 * @return Jam_Model             
	 */
	public function create(Jam_Model $model, array $attributes = NULL)
	{
		return $this->build($model, $attributes)->save();
	}

	/**
	 * Used to easily get field names for the builder
	 * @param  string $field_name name of the foreign field
	 * @return string
	 */
	public function foreign($field_name = NULL, $alias = NULL)
	{
		if ($field_name)
		{
			$model = $alias ? $alias : $this->foreign['model'];
			return $model.'.'.Arr::get($this->foreign, $field_name, $field_name);
		}
		else
		{
			return $alias ? array($this->foreign['model'], $alias) : $this->foreign['model'];
		}
		
	}

	/**
	 * Assigns the inverse model for the association
	 * 
	 * @param  Jam_Model $model parent model
	 * @param  Jam_Model $item  child model
	 * @return Jam_Model        $item
	 */
	public function assign_inverse(Jam_Model $model, Jam_Model $item)
	{
		if ($this->inverse_of)
		{
			$item->set($this->inverse_of, $model);
		}
		return $item;
	}

	public function inverse_association()
	{
		if ( ! $this->is_polymorphic() AND $this->inverse_of)
		{
			return Jam::meta($this->foreign())->association($this->inverse_of);
		}
		return NULL;
	}

	/**
	 * If the item is changed or not yet saved - save it to the database
	 * 
	 * @param  Jam_Model $item 
	 * @return Jam_Model         $item
	 */
	public function preserve_item_changes(Jam_Model $item)
	{
		if ( ! $item->is_saving() AND ($item->changed() OR ! $item->loaded()))
		{
			$item->save();
		}
		return $item;
	}
} // End Kohana_Jam_Association