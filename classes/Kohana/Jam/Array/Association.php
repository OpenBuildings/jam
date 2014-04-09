<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Array_Association extends Jam_Array_Model {

	public static function factory()
	{
		return new Jam_Array_Association();
	}

	protected $_association;
	protected $_parent;

	public function parent($parent = NULL)
	{
		if ($parent !== NULL)
		{
			$this->_parent = $parent;
			return $this;
		}
		return $this->_parent;
	}

	public function association($association = NULL)
	{
		if ($association !== NULL)
		{
			$this->_association = $association;
			return $this;
		}
		return $this->_association;
	}

	protected function _load_content()
	{
		if ($this->_original === NULL AND $this->parent() AND $this->parent()->loaded())
		{
			parent::_load_content();
		}
	}

	public function collection(Jam_Query_Builder_Collection $collection = NULL)
	{
		if ($collection !== NULL)
		{
			$this->_collection = $collection;
			return $this;
		}

		if ( ! $this->_collection AND $this->parent() AND $this->parent()->loaded())
		{
			$this->collection($this->association()->collection($this->parent()));
		}

		return $this->_collection;
	}

	/**
	 * Remove all items from this association, and persist the changes to the database
	 * @return Jam_Array_Association $this
	 */
	public function clear()
	{
		$this->association()->clear($this->parent(), $this);
		return parent::clear();
	}

	public function save()
	{
		$this->association()->save($this->parent(), $this);
		return $this;
	}

	/**
	 * Use the association to get the item from the collection
	 * @param  integer $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		$item = parent::offsetGet($offset);
		if ($item instanceof Jam_Model)
		{
			$this->association()->item_get($this->parent(), $item);
		}
		return $item;
	}

	/**
	 * Use the association to set the item to the colleciton
	 * @param  integer $offset
	 * @param  mixed $item
	 */
	public function offsetSet($offset, $item)
	{
		parent::offsetSet($offset, $item);
		if ($item instanceof Jam_Model)
		{
			$this->association()->item_set($this->parent(), $item);
		}
	}

	/**
	 * Use the association to remove the item from the collection
	 * @param  integer $offset
	 */
	public function offsetUnset($offset)
	{
		$this->_load_content();

		if (isset($this->_content[$offset]))
		{
			$item = $this->offsetGet($offset);
		}

		parent::offsetUnset($offset);

		if (isset($item) AND $item instanceof Jam_Model)
		{
			$this->association()->item_unset($this->parent(), $item);
		}
	}

	public function current()
	{
		$item = parent::current();
		if ($item instanceof Jam_Model)
		{
			$this->association()->item_get($this->parent(), $item);
		}
		return $item;

	}
}
