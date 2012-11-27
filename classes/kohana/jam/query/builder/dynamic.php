<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Dynamic extends Jam_Query_Builder_Collection {

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
			$this->_result = new Jam_Query_Builder_Dynamic_Result($this->execute()->as_array(), NULL, FALSE);
		}

		return $this->_result;
	}

	protected function _find_item($key)
	{
		return Jam::factory($this->meta()->model(), $key);
	}

	protected function _load_model($value)
	{
		if ($value instanceof Jam_Model OR ! $value)
			return $value;
					
		if ( ! is_array($value))
			return $this->_find_item($value);
			
		return parent::_load_model($value);
	}

	protected function _convert_to_array($items)
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
		$model = parent::offsetGet($offset);
		if ($model)
		{
			$this->offsetSet($offset, $model);
		}
		return $model;
	}

	public function offsetSet($offset, $value)
	{
		if (is_array($value))
		{
			$key = Arr::get($value, $this->meta()->primary_key());
			$value = $this->_find_item($key)->set($value);
		}
		$this->result()->force_offsetSet($offset, $value);
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
		$items = $this->_convert_to_array($items);

		foreach ($items as $item) 
		{
			$this->offsetSet($this->search($item), $item);
			$this->_changed = TRUE;
		}

		return $this;
	}

	public function remove($items)
	{
		$items = $this->_convert_to_array($items);

		foreach ($items as $item) 
		{
			if (($offset = $this->search($item)) !== NULL)
			{
				$this->offsetUnset($offset);
				$this->_changed = TRUE;
			}
		}

		return $this;
	}

	public function changed()
	{
		return $this->_changed;
	}

	public function preserve_changed()
	{
		if ($this->changed())
		{
			foreach ($this->result() as $offset => $item) 
			{
				if ( $item instanceof Jam_Model AND ! $item->deleted() AND ( ! $item->loaded() OR $item->changed()))
				{
					$item->save();
				}
			}
		}
	}
} // End Kohana_Jam_Association