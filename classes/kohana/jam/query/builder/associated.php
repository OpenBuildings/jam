<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * This is used as the contents of associated collections (hasmany / manytomany associations) 
 * Handles adding and removing items from them
 * 
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

	/**
	 * The parent model to from where this association is defined
	 * @var Jam_Model
	 */
	protected $_parent;

	/**
	 * The association itself
	 * @var Jam_Association
	 */
	protected $_association;
	
	/**
	 * Association getter / setter
	 * @param  Jam_Association $association 
	 * @return Jam_Association|Jam_Query_Builder_Associated              
	 */
	public function association(Jam_Association $association = NULL)
	{
		if ($association !== NULL)
		{
			$this->_association = $association;
			return $this;
		}
		return $this->_association;
	}

	/**
	 * Parent model getter / setter
	 * @param  Jam_Model $parent
	 * @return Jam_Model|Jam_Query_Builder_Associated              
	 */
	public function parent(Jam_Model $parent = NULL)
	{
		if ($parent !== NULL)
		{
			$this->_parent = $parent;
			return $this;
		}
		return $this->_parent;
	}

	/**
	 * Remove all items from this association, and persist the changes to the database
	 * @return Jam_Query_Builder_Associated $this
	 */
	public function clear()
	{
		$this->association()->clear($this->parent(), $this);
		$this->_result = NULL;
		$this->load_fields(array());
		return $this;
	}

	/**
	 * Build a new Jam Model, add it to the collection and return the newly built model
	 * @param  array $values set values on the new model
	 * @return Jam_Model         
	 */
	public function build(array $values = NULL)
	{
		$item = Jam::build($this->meta()->model(), $values);

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
		if ($this->result()->offsetExists($offset))
		{
			$item = $this->result()->offsetGet($offset);
		}

		parent::offsetUnset($offset);

		if (isset($item) AND $item instanceof Jam_Model)
		{
			$this->association()->item_unset($this->parent(), $item);
		}
	}
} 