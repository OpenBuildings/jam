<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Jam_Exception_Event class for event exceptions
 *
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Kohana_Jam_Exception_Event extends Kohana_Exception {

	public $event;

	function __construct($message, $event, $fields = NULL)
	{
		$fields[':event'] = $this->event = $event;

		parent::__construct($message, $fields);
	}
}
