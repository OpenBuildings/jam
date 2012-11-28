<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Dynamic extends Jam_Query_Builder_Collection {

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

	public static function factory($model)
	{
		return new Jam_Query_Builder_Dynamic($model);
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
	}

	protected function _find_item($key)
	{
		return Jam::factory($this->meta()->model(), $key);
	}

	protected function _load_model_changed($value, $is_changed)
	{
		if ($value instanceof Jam_Model OR ! $value)
			return $value;
					
		if ( ! is_array($value))
		{ 
			return $this->_find_item($value);
		}

		if (is_array($value) AND $is_changed)
		{
			$key = Arr::get($value, $this->meta()->primary_key());

			unset($value[$this->meta()->primary_key()]);

			$model = $this->_find_item($key);
			$model->set($value);
			return $model;
		}
			
		return $this->_load_model($value);
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
		return array_map(array($this, '_id'), $this->result()->as_array());
	}

	public function has($item)
	{
		return $this->search($item) !== NULL;
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
		$item = $this->result->getOffset($offset);
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
			foreach ($this->result() as $offset => $item) 
			{
				if ($item = $this->offsetGetChanged($offset))
				{
					$item->save();
				}
			}
		}
		return $this;
	}
} // End Kohana_Jam_Association