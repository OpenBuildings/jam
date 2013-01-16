<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Associated extends Jam_Query_Builder_Dynamic {

	public static function factory($model, $key = NULL)
	{
		return new Jam_Query_Builder_Associated($model, $key);
	}

	protected $_parent;
	protected $_association;
	
	public function association($association = NULL)
	{
		if ($association !== NULL)
		{
			$this->_association = $association;
			return $this;
		}
		return $this->_association;
	}

	public function parent($parent = NULL)
	{
		if ($parent !== NULL)
		{
			$this->_parent = $parent;
			return $this;
		}
		return $this->_parent;
	}

	public function clear()
	{
		$this->association()->clear($this->parent(), $this);
		$this->_result = NULL;
		$this->load_fields(array());
		return $this;
	}

	public function build(array $values = NULL)
	{
		$item = Jam::build($this->meta()->model(), $values);

		$this->add($item);

		return $item;
	}

	public function create(array $values = NULL)
	{
		return $this->build($values)->save();
	}

	public function offsetGet($offset)
	{
		$item = parent::offsetGet($offset);
		if ($item instanceof Jam_Model)
		{
			$this->association()->item_get($this->parent(), $item, $this);
		}
		return $item;
	}

	public function offsetSet($offset, $item)
	{
		parent::offsetSet($offset, $item);
		if ($item instanceof Jam_Model)
		{
			$this->association()->item_set($this->parent(), $item, $this);
		}
	}

	public function offsetUnset($offset)
	{
		if ($item instanceof Jam_Model)
		{
			$this->association()->item_unset($this->parent(), $item, $this);
		}
		parent::offsetUnset($offset);
	}
} 