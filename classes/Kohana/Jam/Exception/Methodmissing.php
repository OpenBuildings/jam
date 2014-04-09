<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Jam_Exception_Event class for event exceptions
 *
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Kohana_Jam_Exception_Methodmissing extends Kohana_Exception {

	public $method;
	public $args;
	public $sender;

	function __construct($sender, $method, array $args = NULL)
	{
		$this->args = $args;
		$this->sender = $sender;
		$fields[':sender'] = get_class($sender);
		$fields[':method'] = $this->method = $method;

		parent::__construct('Object :sender does not have a method :method', $fields);
	}
}
