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

	protected function _load_model_changed($value, $is_changed)
	{
		$item = parent::_load_model_changed($value, $is_changed);

		if ($this->association()->inverse_of)
		{
			$item->{$this->association()->inverse_of} = $this->parent();
		}
		
		return $item;
	}

	protected function assign_inverse($item)
	{
		if ($this->association()->inverse_of)
		{
			$item->{$this->association()->inverse_of} = $this->parent();
		}
		return $this;
	}

	public function delete()
	{
		$this->association()->delete($this->parent(), $this);
		$this->_result = NULL;
		$this->load_fields(array());
		return $this;
	}

	public function build()
	{
		$item = Jam::build($this->meta()->model());
		$this->assign_inverse($item);
		return $this->add($item);
	}

	public function create()
	{
		$item = Jam::build($this->meta()->model());
		$this->assign_inverse($item);
		$this->add($item);
		$item->save();
		return $this;
	}

} 