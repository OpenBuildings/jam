<?php

/**
 *  An easy width/height manipulations with preservation of aspect ratio
 *  
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 * @since  28.12.2008
 */
class Kohana_Aspect {

	const NO_UPSCALE = 0;
	const UPSCALE = 1;
	const SHRINK = 2;
	const SHRINK_WIDTH = 4;
	const SHRINK_HEIGHT = 8;

	public static function factory($width, $height)
	{
		return new Aspect($width, $height);
	}
	
	protected $_width = 0;
	protected $_height = 0;
	
	protected $_x = 0;
	protected $_y = 0;
	
	protected $_ratio = 0;
	protected $_upscale = TRUE;
	
	/**
	 *	After it's created with given width/height the aspect object preserves their ratio, so that for example if you modify the width,
	 *	the height will be changed accordingly. This also allows for more complex manipulation - as centering and cropping
	 *	
	 */
	function __construct($width, $height) 
	{
		if ( ! $width OR ! $height)
			throw new Kohana_Exception("Aspect must have width and height bigger than zero, was (:width, :height)", array(':width' => $width, ':height' => $height));

		$this->_width = $width;
		$this->_height = $height;
		
		$this->_ratio = $width / $height;
	}

	/**
	 * Get / Set whether to upscale the image or not
	 * @param  bool $upscale 
	 * @return bool|Image_Aspect          
	 */
	public function upscale($upscale = NULL)
	{
		if ($upscale !== NULL)
		{
			$this->_upscale = $upscale;
			return $this;
		}

		return $$this->_upscalet;
	}
	
	/**
	 * Set Width, preserving aspect ratio, or get the current width
	 * @param  integer $new_width 
	 * @return integer|Aspect width | $this
	 */
	public function width($new_width = NULL)
	{
		if ($new_width !== NULL)
		{
			$this->_height = $new_width / $this->ratio();
			$this->_width = $new_width;
			return $this;
		}

		return $this->_width;
	}

	/**
	 * Set Height, preserving aspect ratio, or get the current width
	 * @param  integer $new_height 
	 * @return integer|Aspect height | $this
	 */
	public function height($new_height = NULL)
	{
		if ($new_height !== NULL)
		{
			$this->_width = $new_height * $this->ratio();
			$this->_height = $new_height;
			return $this;
		}

		return $this->_height;
	}

	/**
	 * Modify x and y 
	 * @param  integer $x 
	 * @param  integer $y 
	 * @return Aspect    $this
	 */
	public function offset($x, $y)
	{
		$this->_x += $x;
		$this->_y += $y;
		
		return $this;
	}

	/**
	 * Getter x
	 * @return integer 
	 */
	public function x()
	{ 
		return $this->_x;
	}

	/**
	 * Getter y
	 * @return integer 
	 */
	public function y()
	{
		return $this->_y;
	}
	
	/**
	 * Getter aspect ratio (width / height)
	 * @return integer
	 */
	public function ratio()
	{
		return $this->_ratio;
	}

	/**
	 * Check if the image is portrait (width < height)
	 * @return boolean
	 */
	public function is_portrait()
	{
		return $this->ratio() < 1;
	}

	/**
	 * Check if the image is landscape (height >= width)
	 * @return boolean 
	 */
	public function is_landscape()
	{
		return $this->ratio() >= 1;
	}

	/**
	 * get x + width or set x relative to the right border
	 * @param  integer $value 
	 * @return integer|Aspect        
	 */
	public function right($value = NULL) 
	{ 
		if ($value !== NULL)
		{
			$this->_x = $value - $this->width();
			return $this;
		}

		return $this->x() + $this->width();
	}
	
	/**
	 * get y + height or set y relative to the bottom border
	 * @param  integer $value 
	 * @return integer|Aspect        
	 */
	public function bottom($value = NULL) 
	{ 
		if ($value !== NULL)
		{
			$this->_y = $value - $this->height();
			return $this;
		}

		return $this->y() + $this->height(); 
	}

	/**
	 * Return an array (width, height)
	 * @return array
	 */
	public function dims($width = NULL, $height = NULL)
	{
		if ($width !== NULL AND $height !== NULL)
		{
			$this->_width = $width;
			$this->_height = $height;
			
			$this->_ratio = $width / $height;
		}
		return array($this->width(), $this->height());
	}
	
	/**
	 * Return an array (x, y)
	 * @return array 
	 */
	public function pos($x = NULL, $y = NULL)
	{
		if ($x !== NULL AND $y !== NULL)
		{
			$this->_x = $x;
			$this->_y = $y;
		}
		return array($this->x(), $this->y());
	}
	
	/**
	 * The new height/width will be the largest posible to fit the given width/height, without going out of those dimensions
	 * @param integer $width
	 * @param integer $height
	 * @param bool    $upscale
	 * @return Aspect
	 */
	public function constrain($width, $height, $flags = Aspect::UPSCALE)
	{
		$this->_x = $this->_y = 0;
		
		$is_aspect_smaller =  ($width / $height) < $this->ratio();
		
		if (Aspect::UPSCALE & $flags)
		{
			if ($is_aspect_smaller)
			{
				$this->width($width);
			}
			else
			{
				$this->height($height);
			}
		}
		else
		{
			if ($is_aspect_smaller)
			{
				$original_height = $this->height();			
				$this->width(min($width, $this->width()));				
				$this->height(min($original_height, $this->height()));				
			}
			else
			{
				$original_width = $this->width();
				$this->height(min($height, $this->height()));
				$this->width(min($original_width, $this->width()));
			}	
		}

		if (Aspect::SHRINK & $flags OR Aspect::SHRINK_WIDTH & $flags)
		{
			$width = $this->width();
		}

		if (Aspect::SHRINK & $flags OR Aspect::SHRINK_HEIGHT & $flags)
		{
			$height = $this->height();
		}
						
		$this->_x = ($width - $this->width()) / 2.0;
		$this->_y = ($height - $this->height()) / 2.0;

		return $this;
	}
	
	/**
	 * Add to the widht and the height, keeping the image in place (reducing x and y)
	 *	@return Aspect
	 */
	public function inflate($width, $height)
	{
		$this->_width = $this->width() + $width;
		$this->_height = $this->height() + $height;
		$this->_x = $this->x() - ($width / 2);
		$this->_y = $this->y() - ($height / 2);
		
		return $this;
		
	}
	
	/**
	 *	The width and height will become the smallest possible so that they fully enclose those dimensions
	 *	@return		Aspect
	 */
	public function crop($width, $height)
	{
		$this->_x = $this->_y = 0;

		if (($width / $height) > $this->ratio()) 
		{
			$this->width($width);
			$this->_y = ($height - $this->height()) / 2.0;
		}
		else 
		{
			$this->height($height);
			$this->_x = ($width - $this->width()) / 2.0;
		}
		
		return $this;
	}

	/**
	 *	@return		Aspect
	 */
	public function center($width, $height)
	{
		return $this->relative($width, $height, 0.5, 0.5);
	}
	
	/**
	 * Position an element in a relative position of the parent
	 *	@return		Aspect
	 */
	public function relative($width, $height, $x_part, $y_part)
	{
		$this->_x = (($width - $this->width()) * $x_part);
		$this->_y = (($height - $this->height()) * $y_part);

		return $this;
	}

	public function as_array()
	{
		return array(
			'width' => $this->width(),
			'height' => $this->height(),
			'x' => $this->x(),
			'y' => $this->y(),
		);
	}
	
	/**
	 * Round all variables (x, y, width, height)
	 * @return Aspect
	 */
	public function round_all()
	{
		$this->_x = round($this->x());
		$this->_y = round($this->y());
		$this->_width = round($this->width());
		$this->_height = round($this->height());

		return $this;
	}
	
	public function __toString()
	{
		return "[Aspect] { x: ".$this->x().", y:".$this->y().",  width:".$this->width().", height:".$this->height()." }";
	}
}