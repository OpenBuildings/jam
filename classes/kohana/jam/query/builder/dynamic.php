<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Dynamic extends Jam_Query_Builder_Collection {

	public $_original;
	
	protected $_assign_after_load = array();
	
	public static function convert_collection_to_array($items)
	{
		if ($items instanceof Jam_Query_Builder_Collection)
		{
			$array = $items->as_array();
		}
		elseif ( ! is_array($items)) 
		{
			$array = array($items);
		}
		else
		{
			$array = $items;
		}
		return array_filter($array);
	}

	public static function factory($model, $key = NULL)
	{
		return new Jam_Query_Builder_Dynamic($model, $key);
	}

	protected $_changed = FALSE;

	public function result(Database_Result $result = NULL)
	{
		if ($result !== NULL)
		{
			$this->_result = $result;
			return $this;
		}

		if ( ! $this->_result)
		{
			$this->_result = new Jam_Query_Builder_Dynamic_Result($this->original()->as_array(), NULL, FALSE);
		}

		return $this->_result;
	}

	public function original()
	{
		if ( ! $this->_original)
		{
			$this->_original = $this->execute()->cached();
		}
		return $this->_original;
	}

	public function assign_after_load(array $assign_after_load = NULL)
	{
		if ($assign_after_load !== NULL)
		{
			$this->_assign_after_load = $assign_after_load;
			return $this;
		}
		return $this->_assign_after_load;
	}

	protected function _find_item($key)
	{
		return Jam::factory($this->meta()->model(), $key);
	}

	protected function _load_model_changed($value, $is_changed)
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
			$key = Arr::get($value, $this->meta()->primary_key());

			unset($value[$this->meta()->primary_key()]);

			$model = $this->_find_item($key);
			$model->set($value);
			$item = $model;
		}
		else
		{
			$item = $this->_load_model($value);	
		}
		
		if ($this->_assign_after_load) 
		{
			$item->set($this->_assign_after_load);
		}

		return $item;
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

	protected function _id($value)
	{
		if ($value instanceof Jam_Model)
			return $value->id();

		if (is_numeric($value)) 
			return (int) $value;

		if (is_string($value))
			return $this->_find_item($value)->id();

		if (array($value))
			return (int) Arr::get($value, $this->meta()->primary_key());
	}

	public function current()
	{
		$value = $this->result()->current();
		if ( ! $value)
			return NULL;

		return $this->offsetGet($this->result()->key());
	}

	public function offsetGet($offset)
	{
		$value = $this->result()->offsetGet($offset);
		if ( ! $value)
			return NULL;

		$is_changed = $this->result()->changed($offset);
		$model = $this->_load_model_changed($value, $is_changed);

		$this->result()->force_offsetSet($offset, $model, FALSE);

		return $model;
	}

	public function offsetSet($offset, $value)
	{
		$this->result()->force_offsetSet($offset, $value, TRUE);
	}

	public function offsetUnset($offset)
	{
		$this->result()->force_offsetUnset($offset);
	}

	public function search($item)
	{
		$search_id = $this->_id($item);

		foreach ($this->result() as $offset => $current)
		{
			if ($this->_id($current) === $search_id)
			{
				return (int) $offset;
			}
		}

		return NULL;
	}

	public function ids()
	{
		return array_filter(array_map(array($this, '_id'), $this->result()->as_array()));
	}

	public function original_ids()
	{
		return array_filter($this->original()->as_array(NULL, $this->meta()->primary_key()));
	}

	public function has($item)
	{
		return $this->search($item) !== NULL;
	}

	public function set($items)
	{
		$items = Jam_Query_Builder_Dynamic::convert_collection_to_array($items);
		$this->result(new Jam_Query_Builder_Dynamic_Result($items, NULL, FALSE));
		$this->result()->changed(TRUE);
		return $this;
	}

	public function add($items)
	{
		$items = Jam_Query_Builder_Dynamic::convert_collection_to_array($items);

		foreach ($items as $item) 
		{
			$this->offsetSet($this->search($item), $item);
		}

		return $this;
	}

	public function remove($items)
	{
		$items = Jam_Query_Builder_Dynamic::convert_collection_to_array($items);

		foreach ($items as $item) 
		{
			if (($offset = $this->search($item)) !== NULL)
			{
				$this->offsetUnset($offset);
			}
		}

		return $this;
	}

	public function changed()
	{
		return $this->result()->changed();
	}


	public function offsetGetChanged($offset)
	{
		$item = $this->result()->offsetGet($offset);
		if ($item instanceof Jam_Model AND ! $item->deleted() AND ( ! $item->loaded() OR $item->changed()))
		{
			return $item;
		}
		elseif (is_array($item) AND $this->result()->changed($offset))
		{
			return $this->offsetGet($offset);
		}
	}

	public function check_changed()
	{
		$check = TRUE;
		if ($this->changed())
		{
			foreach ($this->result() as $offset => $item) 
			{
				if ($item = $this->offsetGetChanged($offset) AND ! $item->check())
				{
					$check = FALSE;
				}
			}
		}

		return $check;
	}

	public function save_changed()
	{
		if ($this->changed())
		{
			foreach ($this as $offset => $item) 
			{
				if ($item = $this->offsetGetChanged($offset) AND ! $item->is_saving())
				{
					$item->save();
				}
			}
		}
		return $this;
	}
} 