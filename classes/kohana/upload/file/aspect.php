<?php
/**
 * Image aspect to be used for models with width/height attributes
 * 
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Kohana_Upload_File_Aspect extends Image_Aspect
{
	protected $_model;
	protected $_width_attribute;
	protected $_height_attribute;

	function __construct(Jam_Model $model, $width_attribute, $height_attribute)
	{
		$this->_model = $model;
		$this->_width_attribute = $width_attribute;
		$this->_height_attribute = $height_attribute;
	}

	public function width($new_width = NULL)
	{
		if ( ! $this->_width)
		{
			$this->_width = $this->_model->{$this->_width_attribute};
		}

		return parent::width($new_width);
	}

	public function height($new_height = NULL)
	{
		if ( ! $this->_height)
		{
			$this->_height = $this->_model->{$this->_height_attribute};
		}

		return parent::height($new_height);
	}

	public function reload()
	{
		$this->_width = $this->_height = NULL;
	}

	public function ratio()
	{
		if ( ! $this->_ratio)
		{
			if ( ! $this->width() OR ! $this->height())
				throw new Kohana_Exception("Cannot get ratio with width or height = 0 (width: :width, height: :height)", array(':width' => (int) $this->width(), ':height' => (int) $this->height()));

			$this->_ratio = $this->width() / $this->height();
		}
		return $this->_ratio;
	}
}