<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * A base iterator class, execute _load_contnet on any interaction with the array. Can be serialized properly
 *
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

	/**
	 * An array of booleans that flags which entries have been changed
	 * @var array
	 */
	protected $_changed = array();

	/**
	 * This is set to true when any entries are removed
	 * @var boolean
	 */
	protected $_removed = FALSE;

	/**
	 * The content loaded with _load_content. The main store of this iterator
	 * @var array
	 */
	protected $_content = NULL;

	/**
	 * Iterator implementation
	 * @var integer
	 */
	protected $_current = 0;

	/**
	 * Load the _content variable. This is used to lazy load the content of this iterator
	 */
	protected function _load_content()
	{
		if ( ! $this->_content)
			throw new Kohana_Exception('Content has not been loaded');
	}

	/**
	 * Load each of the entries of this iterator. Called everytime an entry is requested. Use it to lazy load each item
	 * @param  mixed    $value
	 * @param  boolean  $is_changed
	 * @param  int      $offset
	 * @return mixed
	 */
	protected function _load_item($value, $is_changed, $offset)
	{
		return $value;
	}

	/**
	 * Getter / Setter of the content. Lazy loads with _load_content();
	 * @param  array $content
	 * @return array
	 */
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

	/**
	 * Getter for the changed array - check if any or a particular item has been changed
	 * @param  int $offset
	 * @return bool
	 */
	public function changed($offset = NULL)
	{
		if ($offset !== NULL)
			return isset($this->_changed[$offset]);

		return ( (bool) $this->_changed OR $this->_removed);
	}

	/**
	 * Reset the content so it can be loaded again
	 * @return Jam_Array
	 */
	public function reload()
	{
		$this->_changed = array();
		$this->_removed = FALSE;
		$this->_current = 0;
		$this->_content = NULL;

		return $this;
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
	 * Implement ArrayAccess. Lazy load with _load_content, and the item with _load_item
	 *
	 * @param  int $offset
	 * @return Jam_Model
	 */
	public function offsetGet($offset)
	{
		$this->_load_content();

		if (is_array($this->_content) AND ! array_key_exists($offset, $this->_content))
			return NULL;

		return $this->_load_item($this->_content[$offset], isset($this->_changed[$offset]), $offset);
	}

	/**
	 * Implement ArrayAccess. Lazy load with _load_content
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
	 * Implement ArrayAccess. Lazy load with _load_content
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
	 * Implement ArrayAccess. Lazy load with _load_content
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
	 * Implement Iterator. Lazy load with _load_content and the item with _load_item
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
	 * Implement Iterator. Lazy load with _load_content
	 * @return  bool
	 */
	public function valid()
	{
		$this->_load_content();
		return is_array($this->_content) AND array_key_exists($this->_current, $this->_content);
	}

	/**
	 * Implement Serializable. Lazy load with _load_content
	 * @return string
	 */
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

	/**
	 * Implement Serializable.
	 * @param  string $data [description]
	 */
	public function unserialize($data)
	{
		$data = unserialize($data);

		$this->_changed = $data['changed'];
		$this->_content = $data['content'];
		$this->_removed = $data['removed'];
		$this->_current = $data['current'];
	}
}
