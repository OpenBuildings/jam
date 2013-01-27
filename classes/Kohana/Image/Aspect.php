<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Add canvas variables (x, y, width, height) useful for working with html img tags
 * Add Css styles generation
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Image_Aspect extends Aspect
{
	public static function factory($width, $height)
	{
		return new Image_Aspect($width, $height);
	}

	public static function filter_landscape($item)
	{
		return $item->is_landscape();
	}

	public static function filter_portrait($item)
	{
		return $item->is_portrait();
	}

	
	public static function to_css_style($param, $unit = NULL)
	{
		if ($unit === NULL)
			return $param;

		if (is_array($param))
		{
			$param_flat = array();
			foreach ($param as $i => $point) 
			{
				$param_flat[$i] = Image_Aspect::to_css_style($point, $unit);
			}
			return join(' ', $param_flat);
		}
		
		return $param.$unit;
	}

	function __construct($width, $height) 
	{
		parent::__construct($width, $height);
	}

	protected $_canvas_x = 0;
	protected $_canvas_y = 0;
	protected $_canvas_width = 0;
	protected $_canvas_height = 0;

	public function override_width($width)
	{
		$this->_width = $width;
		return $this;
	}

	public function override_height($height)
	{
		$this->_height = $height;
		return $this;
	}

	public function canvas_x($value = NULL)
	{
		if ($value !== NULL)
		{
			$this->_canvas_x = $value;
			return $this;
		}
		return $this->_canvas_x;
	}

	public function canvas_y($value = NULL)
	{
		if ($value !== NULL)
		{
			$this->_canvas_y = $value;
			return $this;
		}
		return $this->_canvas_y;
	}

	public function canvas_width($value = NULL)
	{
		if ($value !== NULL)
		{
			$this->_canvas_width = $value;
			return $this;
		}
		return $this->_canvas_width;
	}

	public function canvas_height($value = NULL)
	{
		if ($value !== NULL)
		{
			$this->_canvas_height = $value;
			return $this;
		}
		return $this->_canvas_height;
	}

	public function canvas_init()
	{
		$this->_canvas_x = $this->_x;
		$this->_canvas_y = $this->_y;
		$this->_canvas_width = $this->_width;
		$this->_canvas_height = $this->_height;
		return $this;
	}

	public function canvas_pos($x, $y)
	{
		$this->_canvas_x = $x;
		$this->_canvas_y = $y;
		return $this;
	}

	public function canvas_dims($width, $height)
	{
		$this->_canvas_width = $width;
		$this->_canvas_height = $height;
		return $this;
	}

	public function canvas_center($width, $height)
	{
		$this->_canvas_x = (($width - $this->canvas_width()) * 0.5);
		$this->_canvas_y = (($height - $this->canvas_height()) * 0.5);
	}

	public function clip()
	{
		$left = max(0, -$this->x());
		$top = max(0, -$this->y());
		$right = $this->width() + min($this->x(), 0);
		$bottom = $this->height() + min($this->y(), 0);

		return array($top, $right, $bottom, $left);
	}

	public function as_array()
	{
		return array('width' => $this->width(), 'height' => $this->height(), 'top' => $this->y(), 'left' => $this->x(), 'clip' => $this->clip());
	}

	public function style($unit = 'px', $only = array())
	{
		$properties = array(
			'width' => Image_Aspect::to_css_style($this->width(), $unit),
			'height' => Image_Aspect::to_css_style($this->height(), $unit),
			'top' => Image_Aspect::to_css_style($this->y(), $unit),
			'left' => Image_Aspect::to_css_style($this->x(), $unit),
			'clip' => 'rect('.Image_Aspect::to_css_style($this->clip(), $unit).')'
		);
		
		if ( ! empty($only))
		{			
			$properties = array_filter(Arr::extract($properties, $only));			
		}
						
		return join('; ', array_map(function($v, $k){ return $k.':'.$v; }, array_values($properties), array_keys($properties)));		
	}

	public function canvas_style($unit = 'px', $only = array())
	{
		$properties = array(
			'top' => Image_Aspect::to_css_style($this->canvas_y(), $unit),
			'left' => Image_Aspect::to_css_style($this->canvas_x(), $unit),
			'width' => Image_Aspect::to_css_style($this->canvas_width(), $unit),
			'height' => Image_Aspect::to_css_style($this->canvas_height(), $unit)
		);
		
		if ( ! empty($only))
		{			
			$properties = array_filter(Arr::extract($properties, $only));			
		}
				
		return join('; ', array_map(function($v, $k){ return $k.':'.$v; }, array_values($properties), array_keys($properties)));		
	}
		

	public function position_style($unit = 'px')
	{
		return join('; ', array(
			'top: '.Image_Aspect::to_css_style($this->y(), $unit),
			'left: '.Image_Aspect::to_css_style($this->x(), $unit),
		));
	}

	public function html_size()
	{
		return array('width' => $this->width(), 'height' => $this->height());
	}

	public function size_style($unit = 'px')
	{
		return join('; ', array(
			'width: '.Image_Aspect::to_css_style($this->width(), $unit),
			'height: '.Image_Aspect::to_css_style($this->height(), $unit),
			'clip: rect('.Image_Aspect::to_css_style($this->clip(), $unit).')',
		));
	}

	public function __toString()
	{
		return "[Image Aspect] { x: ".$this->_x.", y:".$this->_y.", width:".$this->_width.", height:".$this->_height.", canvas: {x: ".$this->_canvas_x.", y:".$this->_canvas_y.",  width:".$this->_canvas_width.", height:".$this->_canvas_height." }}";
	}

}