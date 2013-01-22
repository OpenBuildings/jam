<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Array implements Countable, ArrayAccess, Iterator, Serializable {

	public static function factory()
	{
		return new Jam_Array();
	}

	protected $_changed = array();
	protected $_removed = FALSE;
	protected $_content = NULL;
	protected $_current = 0;
	protected $_collection;

	protected function _load_content()
	{
		if ( ! $this->_content)
			throw new Kohana_Exception('Content has not been loaded');
	}

	protected function _load_item($value, $is_changed, $offset)
	{
		return $value;
	}

	public function content(array $content = NULL)
	{
		if ($content !== NULL)
		{
			$this->_content = $content;
			return $this;
		}

		$this->_load_content();

		return $this->_content;
	}

	public function changed($offset = NULL)
	{
		if ($offset !== NULL)
			return isset($this->_changed[$offset]);

		return ( (bool) $this->_changed OR $this->_removed);
	}

	/**
	 * Implement Countable
	 * @return int 
	 */
	public function count()
	{
		$this->_load_content();
		
		if ($this->_content === NULL)
			return 0;

		return count($this->_content);
	}

	/**
	 * Implement ArrayAccess
	 * 
	 * @param  int $offset 
	 * @return Jam_Model         
	 */
	public function offsetGet($offset)
	{
		$this->_load_content();

		if ( ! isset($this->_content[$offset]))
			return NULL;
		
		return $this->_load_item($this->_content[$offset], isset($this->_changed[$offset]), $offset);
	}

	/**
	 * Implement ArrayAccess
	 * 
	 * @param  int $offset 
	 * @return boolean         
	 */
	public function offsetExists($offset)
	{
		$this->_load_content();

		return isset($this->_content[$offset]);
	}

	/**
	 * Implement ArrayAccess
	 */
	public function offsetSet($offset, $value)
	{
		$this->_load_content();

		if ($offset === NULL)
		{
			$this->_content[] = $value;
			$this->_changed[count($this->_content) - 1] = TRUE;
		}
		elseif ($this->_content !== NULL)
		{
			$this->_content[$offset] = $value;
			$this->_changed[$offset] = TRUE;
		}
		
	}

	/**
	 * Implement ArrayAccess
	 */
	public function offsetUnset($offset)
	{
		$this->_load_content();
		if ($this->_content)
		{
			array_splice($this->_content, $offset, 1);
			array_splice($this->_changed, $offset, 1);

			$this->_removed = TRUE;
		}

	}

	/**
	 * Implement Iterator
	 */
	public function rewind()
	{
		$this->_current = 0;
	}

	/**
	 * Implement Iterator
	 * @return  Jam_Model 
	 */
	public function current()
	{
		$this->_load_content();
		if ( ! $this->valid())
			return NULL;

		return $this->_load_item($this->_content[$this->_current], isset($this->_changed[$this->_current]), $this->_current);
	}

	/**
	 * Implement Iterator
	 * @return  int
	 */
	public function key()
	{
		return $this->_current;
	}

	/**
	 * Implement Iterator
	 */
	public function next()
	{
		$this->_current ++;
	}

	/**
	 * Implement Iterator
	 * @return  bool
	 */
	public function valid()
	{
		return isset($this->_content[$this->_current]);
	}


	public function serialize()
	{
		$this->_load_content();

		return serialize(array(
			'changed' => $this->_changed,
			'content' => $this->_content,
			'removed' => $this->_removed,
			'current' => $this->_current,
		));
	}

	public function unserialize($data)
	{
		$data = unserialize($data);

		$this->_changed = $data['changed'];
		$this->_content = $data['content'];
		$this->_removed = $data['removed'];
		$this->_current = $data['current'];
	}
} 