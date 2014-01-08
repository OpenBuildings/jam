<?php defined('SYSPATH') OR die('No direct script access.');
/**
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Image_Generator
{
	protected $_path_dir = NULL;
	protected $_web_dir = NULL;
	protected $_file_template = NULL;
	protected $_image = NULL;
	protected $_model = NULL;
	protected $_auto_generate_filename = TRUE;
	protected $_filename_generator = NULL;
	
	public function __construct(Jam_Model $model, $image = 'cover')
	{
		$this->_path_dir = Kohana::$config->load('jam-image-generator.path_dir');
		$this->_web_dir = Kohana::$config->load('jam-image-generator.web_dir');
		$this->_file_template = Kohana::$config->load('jam-image-generator.file');

		$this->_image = $image;
		$this->_model = $model;
	}

	public function auto_generate_filename($auto_generate_filename = NULL)
	{
		if ($auto_generate_filename !== NULL)
		{
			$this->_auto_generate_filename = (bool) $auto_generate_filename;
			return $this;
		}
		return $this->_auto_generate_filename;
	}

	public function filename_generator($filename_generator = NULL)
	{
		if ($filename_generator !== NULL)
		{
			$this->_filename_generator = $filename_generator;
			return $this;
		}
		return $this->_filename_generator;
	}

	public function file_template($file_template = NULL)
	{
		if ($file_template !== NULL)
		{
			$this->_file_template = $file_template;
			return $this;
		}
		return $this->_file_template;
	}

	public function web_dir()
	{
		return $this->_web_dir;
	}

	public function image()
	{
		return $this->_image;
	}

	public function model()
	{
		return $this->_model;
	}

	public function path_dir()
	{
		return $this->_path_dir;
	}

	public function resolved_file()
	{
		return strtr($this->file_template(), array(
			':model' => $this->model()->meta()->model(),
			':group' => ceil($this->model()->id() / 10000),
			':filename' => $this->filename(),
			':id' => $this->model()->id(),
			':image' => $this->image(),
		));
	}

	public function filename($filename = NULL)
	{
		if ( ! $this->filename_generator())
		{
			return ($filename === NULL) ? (string) $this->model()->id() : $this;
		}
		else
		{
			$field = $this->filename_generator();

			if ($filename !== NULL)
			{
				$this->model()->$field = $filename;
				$this->_update_model_field($this->model()->$field);
				return $this;
			}

			if ($this->auto_generate_filename() AND ! $this->model()->$field)
			{
				$this->model()->$field = $this->generate_filename();
				$this->_update_model_field($this->model()->$field);
			}

			return $this->model()->$field;
		}
	}

	protected function _update_model_field($filename)
	{
		if ($this->filename_generator())
		{
			$this->model()->update_fields($this->filename_generator(), $filename);
		}
	}
	
	public function generate_filename()
	{
		if ($this->filename_generator())
		{
			return $this->model()->{"image_generator_{$this->image()}_filename"}();
		}
		else
		{
			return (string) $this->model()->id();
		}
	}

	public function path()
	{
		return $this->path_dir().$this->resolved_file();
	}

	public function url($full_path = FALSE)
	{
		return URL::site($this->web_dir().$this->resolved_file(), $full_path);
	}

	public function clear()
	{
		if ($this->filename_generator())
		{
			if ($this->model()->{$this->filename_generator()})
			{
				if (is_file($this->path()))
				{
					unlink($this->path());
				}
				
				$this->_update_model_field('');
			}
		}
		elseif (is_file($this->path()))
		{
			unlink($this->path());
		}
	}

	public function update_cache()
	{
		$new_filename = $this->generate_filename();

		$this->filename($new_filename);
	}

	public function generate()
	{
		if ( ! is_dir(dirname($this->path())))
		{
			mkdir(dirname($this->path()), 0777, TRUE);
		}

		$this->update_cache();

		$image = $this->model()->{'image_generator_'.$this->image()}($this->path());
		$image->save($this->path());
	}
}