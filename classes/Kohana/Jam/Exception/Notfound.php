<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Resource_Jam_Exception_Notfound class
 * Jam NotFound Exception
 *
 * @package    Despark/http-resource
 * @author     Haralan Dobrev
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Kohana_Jam_Exception_Notfound extends Kohana_Exception {

	public $model;

	function __construct($message, $model, $fields = NULL)
	{
		$fields[':model'] = $this->model = $model;

		parent::__construct($message, $fields);
	}
}
