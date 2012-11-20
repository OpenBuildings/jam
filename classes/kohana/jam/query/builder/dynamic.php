<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Dynamic extends Jam_Query_Builder_Collection {

	protected function _convert()
	{
		if ( ! $this->result() instanceof Jam_Query_Builder_Dynamic_Result)
		{
			$this->result(new Jam_Query_Builder_Dynamic_Result($this->result()->as_array(), NULL, FALSE));
		}
		return $this;
	}

	protected function _load_by_key($value)
	{
		$select = new Jam_Query_Builder_Select($this->meta()->model());

		$model = $select
			->where(':unique_key', '=', $value)
			->limit(1)
			->execute()
			->current();
	}

	protected function _load_model($value)
	{
		if ($value instanceof Jam_Model)
		{
			$model = $value;
		}			
		else
		{
			if ( ! is_array($value))
			{
				$value = $this->_load_by_key($value);
			}

			$model = parent::_load_model($value);
		}

		return $model;
	}

	protected function _convert_to_array($items)
	{
		if ($items instanceof Jam_Query_Builder_Dynamic)
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
		{
			return $value->id();
		}
		elseif (is_numeric($value)) 
		{
			return (int) $value;
		}
		else 
		{
			if ( ! is_array($value))
			{
				$value = $this->_load_by_key($value);
			}
			return (int) Arr::get($value, $this->meta()->primary_key());
		}
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
		$this->_convert()->result()->force_offsetSet($offset, $value);
	}

	public function offsetUnset($offset)
	{
		$this->_convert()->result()->force_offsetUnset($offset);
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
			}
		}

		return $this;
	}



} // End Kohana_Jam_Association