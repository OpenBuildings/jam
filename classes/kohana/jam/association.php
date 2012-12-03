<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Core class that all associations must extend
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association extends Jam_Attribute {

	const NULLIFY  = 'nullify';
	const ERASE    = 'erase';
	const DELETE   = 'delete';

	public static function value_to_key_and_model($value, $model, $is_polymorphic = FALSE)
	{
		if ( ! $value)
		{
			return array(NULL, NULL);
		}			
		elseif ($value instanceof Jam_Validated) 
		{
			return array($value->id(), $value->meta()->model());
		}
		elseif (is_integer($value) OR is_string($value)) 
		{
			return array($value, $model);
		}
		elseif (array($value)) 
		{
			if ($is_polymorphic)
			{
				$model = key($value);
				$value = current($value);

				if (is_integer($value) OR is_string($value))
					return array($value, $model);
			}

			$key = Arr::get($value, Jam::meta($model)->primary_key());
			return array($key, $model);	
		}
	}

	public static function value_is_changed($value, $is_polymorphic = FALSE)
	{
		if ($value instanceof Jam_Model AND ( ! $value->loaded() OR $value->changed()))
		{
			return TRUE;
		}
		elseif ($is_polymorphic)
		{
			return (is_array($value) AND is_array(current($value))) ? TRUE : FALSE;
		}
		else
		{
			return is_array($value) ? TRUE : FALSE;
		}
	}

	public $foreign_model = NULL;

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
	 * Default initialize set model, name and foreign variables
	 * 
	 * @param  Jam_Meta $meta
	 * @param  string     $model   the model name 
	 * @param  string     $name    the name of the association 
	 * @return NULL            
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		// if ($this->touch)
		// {
		// 	$this->extension('touch', Jam::extension('touch', $this->touch));
		// }

		// $this->extension('general', Jam::extension('general'));
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
			{
				if ($this->polymorphic_default_model)
				{
					$foreign_model = $this->polymorphic_default_model;
				}
				else
				{
					throw new Kohana_Exception('Model :model, association :name is polymorphic so you can only mass assign arrays', 
						array(':model' => $this->model, ':name' => $this->name));
				}
			}
			else
			{
				$foreign_model = key($array);
				$array = reset($array);

				if ( ! Jam::meta($foreign_model))
					throw new Kohana_Exception('Model :model, association :name is polymorphic and in mass assignment, model ":new_model" does not exist or the array is not constructed properly ( must be array("model" => aray("fields"...)) )', 
						array(':model' => $this->model, ':name' => $this->name, ':new_model' => $foreign_model));
			}
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

	abstract public function join($table, $type = NULL);

	public function set(Jam_Validated $model, $value, $is_changed)
	{
		return $value;
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