<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Associated extends Jam_Query_Builder_Dynamic {

	public static function factory($model, $key = NULL)
	{
		return new Jam_Query_Builder_Dynamic($model, $key);
	}

	public function add($value='')
	{
		# code...
	}

} 