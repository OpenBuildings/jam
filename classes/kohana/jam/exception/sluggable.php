<?php defined('SYSPATH') OR die('No direct access allowed.'); 

/**
 * Resource_Jam_Exception_Sluggable class
 * Jam Sluggable Exception
 *
 * @package    OpenBuildings/jam
 * @author     Yasen Yanev
 * @copyright  (c) 2012 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Kohana_Jam_Exception_Sluggable extends Kohana_Exception {
	
	public $slug;
	public $model;

	function __construct($message, $model, $slug = NULL, $fields = NULL)
	{
		$fields[':slug'] = $this->slug = $slug;
		$fields[':model'] = $this->model = $model;

		parent::__construct($message, $fields);
	}
}