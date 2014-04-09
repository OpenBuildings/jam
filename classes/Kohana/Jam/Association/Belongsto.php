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
abstract class Kohana_Jam_Association_Belongsto extends Jam_Association {

	/**
	 * Indicates whether this is a polymorphic association. Will add the polymorphic field,
	 * named <name>_model, if you set this as a string you can change the name of the field to it.
	 * @var boolean|string
	 */
	public $polymorphic = FALSE;

	/**
	 * This will be set to the polymorphic model column automatically if nothing is set there
	 * @var model
	 */
	public $polymorphic_default_model = NULL;

	/**
	 * The name of the actual field holding the id of the associated model. Defaults to
	 * <name>_id
	 * @var string
	 */
	public $foreign_key = NULL;

	public $inverse_of = NULL;

	public $count_cache = NULL;

	public $field_options = array();

	protected $_default_field_options = array(
		'default' => NULL,
		'allow_null' => TRUE,
		'convert_empty' => TRUE,
	);

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
			$this->foreign_key = $name.'_id';
		}

		if ($this->foreign_key === $name)
			throw new Kohana_Exception('In association ":name" for model ":model" - invalid foreign_key name. Field and Association cannot be the same name', array(
					':model' => $this->model,
					':name' => $name,
				));

		$meta->field($this->foreign_key, Jam::field('integer', array_merge($this->_default_field_options, $this->field_options)));

		if ($this->is_polymorphic())
		{
			if ( ! is_string($this->polymorphic))
			{
				$this->polymorphic = $name.'_model';
			}

			$meta->field($this->polymorphic, Jam::field('string', array('convert_empty' => TRUE)));
		}
		elseif ( ! $this->foreign_model)
		{
			$this->foreign_model = $name;
		}

		if ($this->count_cache)
		{
			if ($this->is_polymorphic())
				throw new Kohana_Exception('Cannot use count cache on polymorphic associations');

			if ($this->count_cache === TRUE)
			{
				$this->count_cache = Inflector::plural($this->model).'_count';
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

		return $value;
	}

	/**
	 * Return a Jam_Query_Builder_Join object to allow a query to join with this association
	 * You can join polymorphic association only when you pass an alias, wich will be used as the
	 * name of the model to match to the polymorphic_key
	 *
	 * @param  string $alias table name alias
	 * @param  string $type  join type (LEFT, NATURAL)
	 * @return Jam_Query_Builder_Join
	 */
	public function join($alias, $type = NULL)
	{
		if ($this->is_polymorphic())
		{
			$foreign_model = $alias;

			if ( ! $foreign_model)
				throw new Kohana_Exception('Jam does not join automatically polymorphic belongsto associations!');

			$join = new Jam_Query_Builder_Join($foreign_model, $type);
			$join->on(DB::expr(':model', array(':model' => $foreign_model)), '=', $this->polymorphic);
		}
		else
		{
			$join = new Jam_Query_Builder_Join($alias ? array($this->foreign_model, $alias) : $this->foreign_model, $type);
		}

		return $join
			->context_model($this->model)
			->on(':primary_key', '=', $this->foreign_key);
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

			$key = Jam_Association::primary_key($this->foreign_model($model), $value);
		}
		else
		{
			$key = $model->{$this->foreign_key};
		}

		if ($key)
		{
			$item = $this->_find_item($this->foreign_model($model), $key);
		}
		elseif (is_array($value))
		{
			$item = Jam::build($this->foreign_model($model));
		}
		else
		{
			$item = NULL;
		}


		if ($item)
		{
			if (is_array($value) AND Jam_Association::is_changed($value))
			{
				$item->set($value);
			}

			if ($item instanceof Jam_Model AND $this->inverse_of AND $item->meta()->association($this->inverse_of) instanceof Jam_Association_Hasone)
			{
				$item->retrieved($this->inverse_of, $model);
			}
		}

		return $item;
	}

	/**
	 * Set releated model, by assigning foreign key for this model
	 * @param Jam_Validated $model
	 * @param mixed         $value
	 * @param boolean       $is_changed
	 */
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		if ($this->polymorphic_default_model AND ! $model->{$this->polymorphic})
		{
			$model->{$this->polymorphic} = $this->polymorphic_default_model;
		}

		if (is_array($value) AND $this->is_polymorphic())
		{
			$model->{$this->polymorphic} = key($value);
			$value = current($value);
		}
		elseif ($value instanceof Jam_Model)
		{
			$model->{$this->polymorphic} = $value->meta()->model();
		}

		$key = Jam_Association::primary_key($this->foreign_model($model), $value);

		if (is_numeric($key) OR $key === NULL)
		{
			$model->{$this->foreign_key} = $key;
		}

		if ($value instanceof Jam_Model AND $this->inverse_of AND $value->meta()->association($this->inverse_of) instanceof Jam_Association_Hasone)
		{
			$value->retrieved($this->inverse_of, $model);
		}

		return $value;
	}

	public function build(Jam_Validated $model, array $attributes = NULL)
	{
		$foreign = Jam::meta($this->foreign_model($model));

		if ( ! $foreign)
			return NULL;

		$item = Jam::build($foreign->model(), $attributes);

		if ($this->inverse_of AND $foreign->association($this->inverse_of) instanceof Jam_Association_Hasone)
		{
			$item->retrieved($this->inverse_of, $model);
		}

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
		if ($value = Arr::get($changed, $this->name) AND Jam_Association::is_changed($value))
		{
			if ( ! $model->{$this->name}->is_validating() AND ! $model->{$this->name}->check())
			{
				$model->errors()->add($this->name, 'association', array(':errors' => $model->{$this->name}->errors()));
			}
		}
	}

	/**
	 * Save the related model before the main model, because we'll need the id to assign to the foreign key
	 * Only save related model if it has been changed, and is not in a process of saving itself
	 * @param  Jam_Model      $model
	 * @param  Jam_Event_Data $data
	 * @param  boolean        $changed
	 */
	public function model_before_save(Jam_Model $model, Jam_Event_Data $data, $changed)
	{
		if ($value = Arr::get($changed, $this->name))
		{
			if (Jam_Association::is_changed($value) AND ($item = $model->{$this->name}))
			{
				if ( ! $item->is_saving())
				{
					$item->save();
				}
				$this->set($model, $item, TRUE);
			}
			else
			{
				$this->set($model, $value, TRUE);
			}
		}
	}


	/**
	 * If we're using count_cache, increment the count_cache field on the foreign model
	 * @param  Jam_Model $model
	 */
	public function model_after_create(Jam_Model $model)
	{
		if ($this->count_cache AND $model->{$this->foreign_key})
		{
			Jam_Countcache::increment($this->foreign_model, $this->count_cache, $model->{$this->foreign_key});
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
		if ($this->dependent == Jam_Association::DELETE AND $model->{$this->name})
		{
			$model->{$this->name}->delete();
		}
		elseif ($this->dependent == Jam_Association::ERASE)
		{
			$this->_delete_item($this->foreign_model($model), $model->{$this->foreign_key});
		}
	}

	/**
	 * If we're using count_cache, decrement the count_cache field on the foreign model
	 * @param  Jam_Model $model
	 */
	public function model_after_delete(Jam_Model $model)
	{
		if ($this->count_cache AND $model->{$this->foreign_key})
		{
			Jam_Countcache::decrement($this->foreign_model, $this->count_cache, $model->{$this->foreign_key});
		}
	}

	/**
	 * Check if association is polymophic
	 * @return boolean
	 */
	public function is_polymorphic()
	{
		return (bool) $this->polymorphic;
	}

	/**
	 * Get the foreign model, if its a polymorphic, use the polymorphic field (e.g. item_model is the polymorphic field, then it's contents will be used)
	 * @param  Jam_Model $model
	 * @return string
	 */
	public function foreign_model(Jam_Model $model)
	{
		return $this->is_polymorphic() ? ($model->{$this->polymorphic} ?: $this->polymorphic_default_model) : $this->foreign_model;
	}

	/**
	 * Get an item based on a unique key from the database
	 * @param  string $foreign_model
	 * @param  string $key
	 * @return Jam_Model
	 */
	protected function _find_item($foreign_model, $key)
	{
		if ( ! $foreign_model)
			return NULL;

		return Jam::find($foreign_model, $key);
	}

	/**
	 * Delete an item with a specific key from the database
	 * @param  string $foreign_model
	 * @param  string $key
	 * @return Database_Result
	 */
	protected function _delete_item($foreign_model, $key)
	{
		if ( ! $foreign_model)
			return NULL;

		return Jam::delete($foreign_model)->where_key($key)->execute();
	}

}
