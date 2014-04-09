<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Jam Slugmismatch Exception
 *
 * @package    Jam
 * @subpackage Sluggable
 * @category   Exceptions
 * @author     Ivan Kerin
 * @author     Haralan Dobrev <hdobrev@despark.com>
 * @copyright  (c) 2011-2013 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Kohana_Jam_Exception_Slugmismatch extends Kohana_Exception {

	public $model;

	public $slug;

	public function __construct($message, $model, $slug, array $variables = NULL)
	{
		$variables[':model'] = $this->model = $model;
		$variables[':slug'] = $this->slug = $slug;

		parent::__construct($message, $variables);
	}
}
