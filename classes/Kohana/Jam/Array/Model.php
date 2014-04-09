<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents an array of models. Lazy loaded from a Jam_Query_Builder_Collection, and can be changed, checked and saved at once.
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Array_Model extends Jam_Array {

	public static function factory()
	{
		return new Jam_Array_Model();
	}

	/**
	 * Convert a collection to an array, keep an array or make a Jam_Model to an array(Jam_Model)
	 * @param  mixed $collection
	 * @return array
	 */
	public static function convert_collection_to_array($collection)
	{
		if ($collection instanceof Jam_Query_Builder_Collection OR $collection instanceof Jam_Array_Model)
		{
			$array = $collection->as_array();
		}
		elseif ( ! is_array($collection))
		{
			$array = array($collection);
		}
		else
		{
			$array = $collection;
		}
		return array_values(array_filter($array));
	}

	/**
	 * A collection used to load the content
	 * @var Jam_Query_Builder_Collection
	 */
	protected $_collection;

	/**
	 * The name of the models in this iterator
	 * @var string
	 */
	protected $_model;

	/**
	 * The original content, loaded from the database
	 * @var array
	 */
	protected $_original;

	/**
	 * This is set if the whole collection has been replaced.
	 * @var boolean
	 */
	protected $_replace = FALSE;

	/**
	 * Getter / Setter of the model name
	 * @param  string $model
	 * @return string
	 */
	public function model($model = NULL)
	{
		if ($model !== NULL)
		{
			$this->_model = $model;
			return $this;
		}
		return $this->_model;
	}

	/**
	 * Getter of the meta object for this iterator (based on $_model)
	 * @return Jam_Model
	 * @throws Kohana_Exception If $_model not present
	 */
	public function meta()
	{
		if ( ! $this->model())
			throw new Kohana_Exception('Model not set');

		return Jam::meta($this->model());
	}

	/**
	 * Getter / Setter of the collection, used to lazy load the data for this iterator
	 * @param  Jam_Query_Builder_Collection $collection
	 * @return Jam_Query_Builder_Collection
	 */
	public function collection(Jam_Query_Builder_Collection $collection = NULL)
	{
		if ($collection !== NULL)
		{
			$this->_collection = $collection;
			return $this;
		}

		return $this->_collection;
	}

	/**
	 * Load the content from the database, using $_collection.
	 * If some items have been added to the iterator before it has been loaded, merge the results
	 * @throws Kohana_Exception If the $_collection has not been loaded
	 */
	protected function _load_content()
	{
		if ($this->_original === NULL)
		{
			if ( ! $this->collection())
				throw new Kohana_Exception('Cannot load content because collection not loaded for Jam_Array(:model)', array(':model' => $this->model()));

			$collection = clone $this->collection();

			$this->_original = $collection->result()->as_array();

			if ( ! $this->_replace)
			{
				if ($this->_content !== NULL)
				{
					$this->_content = array_merge($this->_content, $this->_original);
				}
				else
				{
					$this->_content = $this->_original;
				}
			}
		}
	}

	public function reload()
	{
		$this->_original = NULL;
		$this->_replace = FALSE;
		return parent::reload();
	}

	/**
	 * Load an item from the database, based on a unique key
	 * @param  string $key
	 * @return Jam_Model
	 */
	protected function _find_item($key)
	{
		return Jam::find($this->model(), $key);
	}

	/**
	 * Convert an item from the $_content to a Jam_Model
	 * @param  mixed $value
	 * @param  boolean $is_changed
	 * @param  int $offset
	 * @return Jam_Model
	 */
	protected function _load_item($value, $is_changed, $offset)
	{
		if ($value instanceof Jam_Model OR ! $value)
		{
			$item = $value;
		}
		elseif ( ! is_array($value))
		{
			$item = $this->_find_item($value);
		}
		elseif (is_array($value) AND $is_changed)
		{
			$key = $this->meta()->primary_key();
			if (isset($value[$key]) AND $value[$key])
			{
				$item_key = is_array($value[$key]) ? reset($value[$key]) : $value[$key];

				$model = $this->_find_item($item_key);
				if ( ! $model)
				{
					$model = clone Jam::build_template($this->model(), $value);
				}
				else
				{
					unset($value[$key]);
				}
			}
			else
			{
				$model = clone Jam::build_template($this->model(), $value);
			}

			$item = $model->set($value);
		}
		else
		{
			$item = clone Jam::build_template($this->model(), $value);
			$item = $item->load_fields($value);
		}

		$this->_content[$offset] = $item;

		return $item;
	}

	/**
	 * Find out the primary_key of an item of the $_content
	 * @param  mixed $value
	 * @return int
	 */
	protected function _id($value)
	{
		if ($value instanceof Jam_Model)
			return $value->id();

		if (is_numeric($value))
			return (int) $value;

		if (is_string($value) AND $item = $this->_find_item($value))
			return $item->id();

		if (is_array($value) AND isset($value[$this->meta()->primary_key()]))
			return (int) $value[$this->meta()->primary_key()];
	}

	public function search($item)
	{
		$search_id = $this->_id($item);

		if ( ! $search_id)
			return NULL;

		$this->_load_content();

		if ($this->_content)
		{
			foreach ($this->_content as $offset => $current)
			{
				if ($this->_id($current) === $search_id)
				{
					return (int) $offset;
				}
			}
		}

		return NULL;
	}

	public function as_array($key = NULL, $value = NULL)
	{
		$results = array();
		$key = Jam_Query_Builder::resolve_meta_attribute($key, $this->meta());
		$value = Jam_Query_Builder::resolve_meta_attribute($value, $this->meta());

		foreach ($this as $i => $item)
		{
			$results[$key ? $item->$key : $i] = $value ? $item->$value : $item;
		}

		return $results;
	}

	public function ids()
	{
		$this->_load_content();

		return $this->_content ? array_filter(array_map(array($this, '_id'), $this->_content)) : array();
	}

	public function load_fields(array $data)
	{
		$this->_content = $this->_original = $data;
		return $this;
	}

	public function original()
	{
		return $this->_original;
	}

	public function original_ids()
	{
		$this->_load_content();

		return $this->_original ? array_filter(array_map(array($this, '_id'), $this->_original)) : array();
	}

	public function has($item)
	{
		return $this->search($item) !== NULL;
	}

	public function set($items)
	{
		$this->_content = Jam_Array_Model::convert_collection_to_array($items);
		$this->_changed = count($this->_content) ? array_fill(0, count($this->_content), TRUE) : array();
		$this->_replace = TRUE;
		$this->_removed = TRUE;

		return $this;
	}

	public function add($items)
	{
		$items = Jam_Array_Model::convert_collection_to_array($items);

		foreach ($items as $item)
		{
			$this->offsetSet($this->search($item), $item);
		}

		return $this;
	}

	public function remove($items)
	{
		$items = Jam_Array_Model::convert_collection_to_array($items);

		foreach ($items as $item)
		{
			if (($offset = $this->search($item)) !== NULL)
			{
				$this->offsetUnset($offset);
			}
		}

		return $this;
	}

	/**
	 * Build a new Jam Model, add it to the collection and return the newly built model
	 * @param  array $values set values on the new model
	 * @return Jam_Model
	 */
	public function build(array $values = NULL)
	{
		$item = clone Jam::build_template($this->model(), $values);
		if ($values)
		{
			$item->set($values);
		}
		$this->add($item);

		return $item;
	}

	/**
	 * The same as build but saves the model in the database
	 * @param  array $values
	 * @return Jam_Model
	 */
	public function create(array $values = NULL)
	{
		return $this->build($values)->save();
	}

	public function check_changed()
	{
		$check = TRUE;

		foreach ($this->_changed as $offset => $is_changed)
		{
			$item = $this->offsetGet($offset);
			if ($is_changed AND $item AND ! $item->check())
			{
				$check = FALSE;
			}
		}

		return $check;
	}

	public function save_changed()
	{
		foreach ($this->_changed as $offset => $is_changed)
		{
			$item = $this->offsetGet($offset);
			if ($is_changed AND $item AND ! $item->is_saving())
			{
				$item->save();
			}
		}

		return $this;
	}

	public function clear()
	{
		$this->_changed =
		$this->_original =
		$this->_content = array();

		return $this;
	}

	public function clear_changed()
	{
		$this->_changed = array();
		$this->_original = $this->_content;

		return $this;
	}

	public function __toString()
	{
		if ( ! $this->collection())
			return 'Jam_Array ('.$this->model().')[NOT LOADED COLLECTION]';

		return $this->collection()->__toString();
	}

	public function __call($method, $args)
	{
		if ( ! $this->collection())
			throw new Kohana_Exception('Cannot call method :method on collection because collection not loaded for Jam_Array(:model)', array(':method' => $method, ':model' => $this->model()));

		return call_user_func_array(array(clone $this->collection(), $method), $args);
	}

	/**
	 * Getter for the changed array - check if any or a particular item has been changed
	 * @param  int $offset
	 * @return bool
	 */
	public function changed($offset = NULL)
	{
		if ($this->_content)
		{
			foreach ($this->_content as $key => $value)
			{
				if ( ! isset($this->_changed[$key]) AND $value instanceof Jam_Model AND $value->changed())
				{
					$this->_changed[$key] = TRUE;
				}
			}
		}

		return parent::changed($offset);
	}

	public function serialize()
	{
		$this->_load_content();


		return serialize(array(
			'original' => $this->_original,
			'model'    => $this->_model,
			'replace'  => $this->_replace,
			'changed'  => $this->_changed,
			'content'  => $this->_content,
			'removed'  => $this->_removed,
			'current'  => $this->_current,
		));
	}

	public function unserialize($data)
	{
		$data = unserialize($data);

		$this->_original = $data['original'];
		$this->_model    = $data['model'];
		$this->_replace  = $data['replace'];
		$this->_changed  = $data['changed'];
		$this->_content  = $data['content'];
		$this->_removed  = $data['removed'];
		$this->_current  = $data['current'];
	}
}
