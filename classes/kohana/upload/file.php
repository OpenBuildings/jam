<?php

/**
 * @package    OpenBuildings/jam-upload
 * @author     Ivan Kerin
 * @copyright  (c) 2011 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
class Kohana_Upload_File
{
	protected $_hidden_filename;
	protected $_path;
	protected $_file;
	protected $_web;
	protected $_width;
	protected $_height;

	public function __construct($file, $hidden_filename, $web, $path, $sizes = NULL)
	{
		$this->_file = $file;
		$this->_hidden_filename = $hidden_filename;
		$this->_web = $web;
		$this->_path = $path;

		if ($sizes)
		{
			$this->_width = $sizes[0];
			$this->_height = $sizes[1];
		}
	}

	/**
	 * Return the dimensions of the image (if the file is an image)
	 * you can pass width/height to constrain the new dimensions
	 * @param  integer  $new_width  constrain to these dimensions
	 * @param  integer  $new_height constrain to this height
	 * @param  boolean  $upscale    default true - allow upscaling of dimensions
	 * @return array                new dimensions
	 */
	public function dimensions($new_width = NULL, $new_height = NULL, $upscale = TRUE)
	{
		if ($new_width === NULL AND $new_height === NULL)
			return array('width' => $this->_width, 'height' => $this->_height);

		if ( ! $this->_width OR ! $this->_height)
			return array('width' => NULL, 'height' => NULL);
		
		$aspect = $this->_width / $this->_height;
		$keep_aspect = FALSE;
		
		if ($new_height === NULL)
		{			
			$new_aspect = $aspect;
			$keep_aspect = TRUE;
		}
		elseif ($new_width === NULL)
		{
			$new_aspect = $aspect;
			$keep_aspect = TRUE;
		}
		else		
		{
			$new_aspect = $new_width / $new_height;
		}
		
		if ($keep_aspect)
		{
			$new = array('width' => $new_width === NULL ? $new_height * $new_aspect : $new_width, 'height' => ($new_height === NULL ? $new_width / $new_aspect : $new_height));
		}
		elseif ($new_aspect < $aspect)
		{
			$new = array('width' => $new_width, 'height' => $this->_height * ($new_width / $this->_width));
		} 
		else 
		{
			$new = array('width' => $this->_width * ($new_height / $this->_height), 'height' => $new_height);
		}

		if ( ! $upscale AND ($new['width'] > $this->_width OR $new['height'] > $this->_height))
		{
			if ($new['width'] > $this->_width)
			{
				$new['width'] = $this->_width;
				$new['height'] = $new['width']/$aspect;
			}
			elseif($new['height'] > $this->_height)
			{
				$new['height'] = $this->_height;
				$new['width'] = $new['height']*$aspect;
			}
		}

		return $new;
	}

	public function is_portrait()
	{
		return (float) ($this->_width / $this->_height) < 1.00;
	}
	
	public function is_landscape()
	{
		return ! $this->is_portrait();
	}
	
	/**
	 * Get the width of the image (or calculate new width when constrained to dimensions)
	 * @param  integer $new_width  constrained to this widht
	 * @param  integer $new_height constrained to this height
	 * @return integer             the width
	 */
	public function width($new_width = NULL, $new_height = NULL )
	{
		return Arr::get($this->dimensions($new_width,$new_height), 'width');
	}

	/**
	 * Get the height of the image (or calculate new height when constrained to dimensions)
	 * @param  integer $new_width  constrained to this widht
	 * @param  integer $new_height constrained to this height
	 * @return integer             the height
	 */
	public function height( $new_width = NULL, $new_height = NULL )
	{
		return Arr::get($this->dimensions($new_width,$new_height), 'height');
	}

	/**
	 * Check if the file variable is populated
	 * @return boolean 
	 */
	public function is_empty()
	{
		return ! (bool) $this->_file;
	}

	/**
	 * Return the file name
	 * @return string 
	 */
	public function __toString()
	{
		return (string) $this->_file;
	}

	public function hidden_filename()
	{
		return $this->_hidden_filename ? $this->_hidden_filename : $this->_file;
	}

	/**
	 * Get the publically availble url of the file
	 * @param  string $thumbnail if it's an image with thumbnails you can pass this to get a thumbnail
	 * @param  string $protocol  absolute path with protocol (http/https) or TRUE for current protocol
	 * @param  boolean $index    use index file or not
	 * @return string            
	 */
	public function url($thumbnail = NULL, $protocol = NULL, $index = NULL)
	{
		$url = Upload_Server::combine_path($this->_web, $thumbnail, $this->_file);
		if (strpos($this->_web, '://') !== FALSE)
		{
			return $url;
		}
		return url::site($url, $protocol, $index);
	}

	/**
	 * Get the filename on the server (for local files)
	 * @param  string $thumbnail if tt's an image with thumbanils you can pass this to get a thumbnail
	 * @return [type]            [description]
	 */
	public function file($thumbnail = NULL)
	{
		return Upload_Server::combine_path($this->_path, $thumbnail, $this->_file);
	}

}