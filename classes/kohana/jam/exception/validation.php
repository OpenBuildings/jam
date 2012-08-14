<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Resource_Jam_Exception_NotLoaded class
 * Jam NotLoaded Exception
 *
 * @package    OpenBuildings/http-resource
 * @author     Haralan Dobrev
 * @copyright  (c) 2012 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Kohana_Jam_Exception_Validation extends Kohana_Exception {
	
	protected $_model;
	
	function __construct($message, $model, $fields = NULL)
	{
		$this->_model = $model;
		
		$fields[':model'] = $model->meta()->model;
		$fields[':errors'] = $model->errors();
		
		parent::__construct($message, $fields);
	}

	public function model()
	{
		return $this->_model;
	}

	public function errors()
	{
		return $this->_model->errors();
	}
}