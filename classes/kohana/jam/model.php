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
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
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
	public function __construct($key = NULL, $meta_name = NULL)
	{
		if ($meta_name === NULL)
		{
			$meta_name = $this;
		}
		
		parent::__construct($key, $meta_name);

		$this->_meta->trigger_behavior_events($this, 'before_construct');

		// Copy over the defaults into the original data.
		$this->_original = $this->_meta->defaults();

		// Have an id? Attempt to load it
		if ($key)
		{
			$result = Jam::query($meta_name, $key)
				->as_object(FALSE)
				->select();

			// Only load if a record is found
			if ($result)
			{
				$this->load_fields($result);
			}
		}

		$this->_meta->trigger_behavior_events($this, 'after_construct');
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
		parent::set($values, $value);

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
	 * @param   Jam_Collection|Jam_Model|array  $values
	 * @return  Jam_Model
	 */
	public function load_fields($values)
	{
		// Clear the object
		$this->clear();

		foreach ($values as $key => $value)
		{
			if ($field = $this->_meta->field($key))
			{
				$this->_original[$field->name] = $field->set($this, $value, FALSE);
			}
			// Unmapped data
			else
			{
				$this->_unmapped[$key] = $value;
			}
		}
		
		// Model is now saved and loaded
		$this->_saved = $this->_loaded = TRUE;

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
		return parent::check( ! $this->loaded() OR $force);
	}

	protected function _move_retrieved_to_changed()
	{
		foreach ($this->_retrieved as $column => $value) 
		{
			if ( ! isset($this->_changed[$column])
				AND $association = $this->_meta->association($column)
				AND $association instanceof Jam_Association_Collection
				AND $value->changed())
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

		Jam::query($this, $this->id())
			->set($values)
			->update();

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
		$this->_is_saving = TRUE;

		$key = $this->_original[$this->_meta->primary_key()];

		$this->_move_retrieved_to_changed();

		// Run validation
		if ($validate !== FALSE)
		{
			$this->check_insist();
		}

		// These will be processed later
		$values = $saveable = array();

		$this->_meta->trigger_attribute_events($this, 'before_save');

		if ($this->_meta->trigger_behavior_events($this, 'before_save') === FALSE)
		{
			return $this;
		}

		// Trigger callbacks and ensure we should proceed
		$event_type = $key ? 'update' : 'create';
		
		if ($this->_meta->trigger_behavior_events($this, 'before_'.$event_type) === FALSE)
		{
			return $this;
		}

		// Iterate through all fields in original in case any unchanged fields
		// have convert() behavior like timestamp updating...
		// 
		
		foreach (array_merge($this->_original, $this->_changed) as $column => $value)
		{
			if ($field = $this->_meta->field($column))
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
					elseif ( ! $key AND ! $this->changed($field->name) AND ! $field->primary)
					{
						$values[$field->name] = $field->default;
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
				Jam::query($this, $key)
					 ->set($values)
					 ->update();
			}
		}
		else
		{
			list($id) = Jam::query($this)
							 ->columns(array_keys($values))
							 ->values(array_values($values))
							 ->insert();

			// Gotta make sure to set this
			$key = $values[$this->_meta->primary_key()] = $id;
		}

		// Re-set any saved values; they may have changed
		$this->set($values);

		$this->_loaded = $this->_saved = TRUE;

		$this->_meta->trigger_attribute_events($this, 'after_save');
		
		$this->_meta->trigger_behavior_events($this, 'after_save');

		$this->_meta->trigger_behavior_events($this, 'after_'.$event_type);
		
		// Set the changed data back as original
		$this->_original = array_merge($this->_original, $this->_changed);

		// We're good!
		$this->_retrieved = $this->_changed = array();

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
		$key = NULL;

		// Are we loaded? Then we're just deleting this record
		if ($this->_loaded)
		{
			$key = $this->_original[$this->_meta->primary_key()];

			$this->_meta->trigger_attribute_events($this, 'before_delete', $key);

			if (($result = $this->_meta->trigger_behavior_events($this, 'before_delete', $key)) !== FALSE)
			{
				$result = Jam::query($this, $key)->delete();
			}
		}

		// Trigger the after-delete
		$this->_meta->trigger_attribute_events($this, 'after_delete', $key);

		$this->_meta->trigger_behavior_events($this, 'after_delete', $key);

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

	public function builder($name)
	{
		return $this->_meta->association_insist($name)->builder($this);
	}

	public function build($name, array $attributes = NULL)
	{
		return $this->_meta->association_insist($name)->build($this, $attributes);
	}

	public function create($name, array $attributes = NULL)
	{
		return $this->_meta->association_insist($name)->create($this, $attributes);
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

}  // End Kohana_Jam_Model
