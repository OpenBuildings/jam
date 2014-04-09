<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Model
 *
 * Jam_Model is the class all models must extend. It handles
 * various CRUD operations and relationships to other models.
 *
 * @package    Jam
 * @category   Models
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Model extends Jam_Validated {

	/**
	 * @var  boolean  Whether or not the model is loaded
	 */
	protected $_loaded = FALSE;

	/**
	 * @var  boolean  Whether or not the model is saved
	 */
	protected $_saved = FALSE;

	/**
	 * @var  boolean  Whether or not the model is saving
	 */
	protected $_is_saving = FALSE;

	/**
	 * @var  Boolean  A flag that indicates a record has been deleted from the database
	 */
	 protected $_deleted = FALSE;

	/**
	 * Constructor.
	 *
	 * A key can be passed to automatically load a model by its
	 * unique key.
	 *
	 * @param  mixed|null  $key
	 */
	public function __construct($meta_name = NULL)
	{
		parent::__construct($meta_name);

		$this->meta()->events()->trigger('model.before_construct', $this);

		// Copy over the defaults into the original data.
		$this->_original = $this->meta()->defaults();

		$this->meta()->events()->trigger('model.after_construct', $this);
	}

	/**
	 * Gets the value for a field.
	 *
	 * @param   string       $name The field's name
	 * @return  array|mixed
	 */
	public function get($name)
	{
		if ($association = $this->meta()->association($name))
		{
			$name = $association->name;

			return $association->get($this, Arr::get($this->_changed, $name), $this->changed($name));
		}

		return parent::get($name);
	}

	public function __isset($name)
	{
		return ($this->meta()->association($name) OR parent::__isset($name));
	}

	/**
	 * Sets the value of a field.
	 *
	 * You can pass an array of key => value pairs
	 * to set multiple fields at the same time:
	 *
	 *    $model->set(array(
	 *        'field1' => 'value',
	 *        'field2' => 'value',
	 *         ....
	 *    ));
	 *
	 * @param   array|string  $values
	 * @param   string        $value
	 * @return  Jam_Model
	 */
	public function set($values, $value = NULL)
	{
		// Accept set('name', 'value');
		if ( ! is_array($values))
		{
			$values = array($values => $value);
		}

		foreach ($values as $key => & $value)
		{
			if ($association = $this->meta()->association($key))
			{
				if ($association->readonly)
					throw new Kohana_Exception('Cannot change the value of :name, its readonly', array(':name' => $association->name));

				$this->_changed[$association->name] = $association->set($this, $value, TRUE);

				unset($this->_retrieved[$association->name]);
				unset($values[$key]);
			}
		}

		parent::set($values);

		if ($this->_saved AND $this->changed())
		{
			$this->_saved = FALSE;
		}

		return $this;
	}

	/**
	 * Clears the object and loads an array of values into the object.
	 *
	 * This should only be used for setting from database results
	 * since the model declares itself as saved and loaded after.
	 *
	 * @param   Jam_Query_Builder_Collection|Jam_Model|array  $values
	 * @return  Jam_Model
	 */
	public function load_fields($values)
	{
		// Clear the object
		$this->clear();

		$this->meta()->events()->trigger('model.before_load', $this);

		$this->_loaded = TRUE;

		foreach ($values as $key => $value)
		{
			if ($field = $this->meta()->field($key))
			{
				$this->_original[$field->name] = $field->set($this, $value, FALSE);
			}
			elseif ($association = $this->meta()->association($key))
			{
				$association_value = $association->load_fields($this, $value, FALSE);
				if (is_object($association_value))
				{
					$this->_retrieved[$association->name] = $association->load_fields($this, $value, FALSE);
				}
				else
				{
					$this->_changed[$association->name] = $association_value;
				}
			}
			else
			{
				$this->_unmapped[$key] = $value;
			}
		}

		// Model is now saved and loaded
		$this->_saved = TRUE;

		$this->meta()->events()->trigger('model.after_load', $this);

		return $this;
	}

	/**
	 * Validates the current model's data
	 *
	 * @throws  Jam_Exception_Validation
	 * @param   Validation|null   $extra_validation
	 * @return  Kohana_Jam_Model
	 */
	public function check($force = FALSE)
	{
		$this->_move_retrieved_to_changed();

		return parent::check( ! $this->loaded() OR $force);
	}

	protected function _move_retrieved_to_changed()
	{
		foreach ($this->_retrieved as $column => $value)
		{
			if ($value instanceof Jam_Array_Association	AND $value->changed())
			{
				$this->_changed[$column] = $value;
			}
		}
	}

	public function update_fields($values, $value = NULL)
	{
		if ( ! $this->loaded())
			throw new Kohana_Exception('Model must be loaded to use update_fields method');

		if ( ! is_array($values))
		{
			$values = array($values => $value);
		}

		$this->set($values);

		Jam::update($this)
			->where_key($this->id())
			->set($values)
			->execute();

		return $this;
	}

	/**
	 * Creates or updates the current record.
	 *
	 * @param   bool|null        $validate
	 * @return  Kohana_Jam_Model
	 */
	public function save($validate = NULL)
	{
		if ($this->_is_saving)
			throw new Kohana_Exception("Cannot save a model that is already in the process of saving");


		$key = $this->_original[$this->meta()->primary_key()];

		// Run validation
		if ($validate !== FALSE)
		{
			$this->check_insist();
		}

		$this->_is_saving = TRUE;

		// These will be processed later
		$values = $defaults = array();

		if ($this->meta()->events()->trigger('model.before_save', $this, array($this->_changed)) === FALSE)
		{
			return $this;
		}

		// Trigger callbacks and ensure we should proceed
		$event_type = $key ? 'update' : 'create';

		if ($this->meta()->events()->trigger('model.before_'.$event_type, $this, array($this->_changed)) === FALSE)
		{
			return $this;
		}

		$this->_move_retrieved_to_changed();

		// Iterate through all fields in original in case any unchanged fields
		// have convert() behavior like timestamp updating...
		//
		foreach (array_merge($this->_original, $this->_changed) as $column => $value)
		{
			if ($field = $this->meta()->field($column))
			{
				// Only save in_db values
				if ($field->in_db)
				{
					// See if field wants to alter the value on save()
					$value = $field->convert($this, $value, $key);

					// Only set the value to be saved if it's changed from the original
					if ($value !== $this->_original[$column])
					{
						$values[$field->name] = $value;
					}
					// Or if we're INSERTing and we need to set the defaults for the first time
					elseif ( ! $key AND ( ! $this->changed($field->name) OR $field->default === $value) AND ! $field->primary)
					{
						$defaults[$field->name] = $field->default;
					}
				}
			}
		}

		// If we have a key, we're updating
		if ($key)
		{
			// Do we even have to update anything in the row?
			if ($values)
			{
				Jam::update($this)
					->where_key($key)
					->set($values)
					->execute();
			}
		}
		else
		{
			$insert_values = array_merge($defaults, $values);
			list($id) = Jam::insert($this)
				->columns(array_keys($insert_values))
				->values(array_values($insert_values))
				->execute();

			// Gotta make sure to set this
			$key = $values[$this->meta()->primary_key()] = $id;
		}

		// Re-set any saved values; they may have changed
		$this->set($values);

		$this->_loaded = $this->_saved = TRUE;

		$this->meta()->events()->trigger('model.after_save', $this, array($this->_changed, $event_type));

		$this->meta()->events()->trigger('model.after_'.$event_type, $this, array($this->_changed));

		// Set the changed data back as original
		$this->_original = array_merge($this->_original, $this->_changed);

		$this->_changed = array();

		foreach ($this->_retrieved as $name => $retrieved)
		{
			if (($association = $this->meta()->association($name)))
			{
				if ($association instanceof Jam_Association_Collection)
				{
					$retrieved->clear_changed();
				}
			}
			elseif (($field = $this->meta()->field($name)) AND $field->in_db)
			{
				unset($this->_retrieved[$name]);
			}
		}

		$this->_is_saving = FALSE;

		return $this;
	}

	/**
	 * Deletes a single record.
	 *
	 * @return  boolean
	 **/
	public function delete()
	{
		$result = FALSE;

		// Are we loaded? Then we're just deleting this record
		if ($this->_loaded)
		{
			$key = $this->_original[$this->meta()->primary_key()];

			if (($result = $this->meta()->events()->trigger('model.before_delete', $this)) !== FALSE)
			{
				$result = Jam::delete($this)->where_key($key)->execute();
			}
		}

		// Trigger the after-delete
		$this->meta()->events()->trigger('model.after_delete', $this, array($result));

		// Clear the object so it appears deleted anyway
		$this->clear();

		// Set the flag indicatig the model has been successfully deleted
		$this->_deleted = $result;

		return $this->_deleted;
	}

	/**
	 * Removes any changes made to a model.
	 *
	 * This method only works on loaded models.
	 *
	 * @return  Jam_Model
	 */
	public function revert()
	{
		if ($this->_loaded)
		{
			$this->_loaded =
			$this->_saved  = TRUE;

			parent::revert();
		}

		return $this;
	}

	/**
	 * Sets a model to its original state, as if freshly instantiated
	 *
	 * @return  Jam_Model
	 */
	public function clear()
	{
		$this->_loaded =
		$this->_saved  = FALSE;

		parent::clear();

		return $this;
	}

	public function get_insist($attribute_name)
	{
		$attribute = $this->__get($attribute_name);

		if ($attribute === NULL)
			throw new Jam_Exception_Notfound('The association :name was empty on :model_name', NULL, array(
				':name' => $attribute_name,
				':model_name' => (string) $this,
			));

		return $attribute;
	}

	public function build($association_name, array $attributes = array())
	{
		$association = $this->meta()->association($association_name);

		if ( ! $association)
			throw new Kohana_Exception('There is no association named :association_name on model :model', array(':association_name' => $association_name, ':model' => $this->meta()->model()));

		if ($association instanceof Jam_Association_Collection)
			throw new Kohana_Exception(':association_name association must not be a collection on model :model', array(':association_name' => $association_name, ':model' => $this->meta()->model()));

		$item = $association->build($this, $attributes);

		$this->set($association_name, $item);

		return $item;
	}

	/**
	 * Returns whether or not the model is loaded
	 *
	 * @return  boolean
	 */
	public function loaded()
	{
		return $this->_loaded;
	}

	public function loaded_insist()
	{
		if ( ! $this->loaded())
			throw new Jam_Exception_NotLoaded("Model not loaded", $this);

		return $this;
	}

	/**
	 * Whether or not the model is saved
	 *
	 * @return  boolean
	 */
	public function saved()
	{
		return $this->_saved;
	}

	/**
	 * Whether or not the model is in the process of being saved
	 *
	 * @return  boolean
	 */
	public function is_saving()
	{
		return $this->_is_saving;
	}

	/**
	 * Whether or not the model is deleted
	 *
	 * @return  boolean
	 */
	public function deleted()
	{
		return $this->_deleted;
	}

	/**
	 * Build a new model object based on the current one, but without an ID, so it can be saved as a new object
	 * @return Jam_Model
	 */
	public function duplicate()
	{
		$fields = $this->as_array();

		unset($fields[$this->meta()->primary_key()]);

		return Jam::build($this->meta()->model(), $fields);
	}

	/**
	 * Returns a string representation of the model in the
	 * form of `Model_Name (id)` or `Model_Name (NULL)` if
	 * the model is not loaded.
	 *
	 * This is designed to be useful for debugging.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) get_class($this).'('.($this->loaded() ? $this->id() : 'NULL').')';
	}

	public function serialize()
	{
		$this->_move_retrieved_to_changed();

		return serialize(array(
			'original' => $this->_original,
			'changed' => $this->_changed,
			'unmapped' => $this->_unmapped,
			'saved' => $this->_saved,
			'loaded' => $this->_loaded,
			'deleted' => $this->_deleted,
		));
	}

	public function unserialize($data)
	{
		$data = unserialize($data);

		$this->_meta = Jam::meta($this);
		$this->_original = Arr::merge($this->meta()->defaults(), $data['original']);
		$this->_changed = $data['changed'];
		$this->_unmapped = $data['unmapped'];
		$this->_saved = $data['saved'];
		$this->_loaded = $data['loaded'];
		$this->_deleted = $data['deleted'];

		foreach ($this->_changed as $name => $attribute)
		{
			$association = $this->meta()->association($name);
			if ($association AND $association instanceof Jam_Association_Collection)
			{
				$association->assign_internals($this, $attribute);
			}
		}
	}
}
