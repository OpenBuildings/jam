<?php defined('SYSPATH') OR die('No direct access allowed.'); 

/**
 * Jam_Exception_Upload class
 *
 * @package    Despark/jam
 * @author     Yasen Yanev
 * @copyright  (c) 2014 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Jam_Exception_Upload extends Kohana_Jam_Exception_Upload {
	
	function __construct($message, $fields = NULL)
	{
		parent::__construct($message, $fields);
	}
}