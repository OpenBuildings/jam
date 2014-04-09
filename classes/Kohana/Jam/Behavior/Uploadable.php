<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 *  Uploadable behavior for Jam ORM library
 *
 * @package    Jam
 * @category   Behavior
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Behavior_Uploadable extends Jam_Behavior
{
	public $_thumbnails = array();
	public $_transformations = array();
	public $_server = NULL;
	public $_save_size = FALSE;
	public $_path = NULL;
	public $_dynamic_server = NULL;
	public $_delete_file = TRUE;

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		if ($this->_save_size)
		{
			$meta
				->field($name.'_width', Jam::field('integer'))
				->field($name.'_height', Jam::field('integer'));
		}

		if ($this->_dynamic_server)
		{
			$this->_dynamic_server = $this->_dynamic_server === TRUE ? $name.'_server' : $this->_dynamic_server;

			$meta->field($this->_dynamic_server, Jam::field('string', array('default' => $this->_server)));
		}

		$meta->field($name, Jam::field('upload', array(
			'path' => $this->_path,
			'thumbnails' => $this->_thumbnails,
			'transformations' => $this->_transformations,
			'server' => $this->_server,
			'dynamic_server' => $this->_dynamic_server,
			'save_size' => $this->_save_size,
			'delete_file' => $this->_delete_file
		)));
	}

	public function model_after_check(Jam_Model $model, Jam_Event_Data $data)
	{
		if ($model->changed($this->_name) AND is_file($model->{$this->_name}->file()))
		{
			$dims = @ getimagesize($model->{$this->_name}->file());
			if ($dims)
			{
				list($model->{$this->_name.'_width'}, $model->{$this->_name.'_height'}) = $dims;
			}
		}
	}

	public function model_call_change_upload_server(Jam_Model $model, Jam_Event_Data $data, $name, $server)
	{
		if ($this->_name == $name)
		{
			if ($old_server = $model->{$this->_dynamic_server} AND $old_server !== $server)
			{
				$model->{$this->_name}->move_to_server($server);
				$model->update_fields($this->_dynamic_server, $server);
			}
		}
	}
}
