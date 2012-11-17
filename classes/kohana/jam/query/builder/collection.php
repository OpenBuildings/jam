<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Query_Builder_Collection extends Jam_Query_Builder_Select implements Countable{

	public function count()
	{
		return $this->select_count()->execute()->get('total');
	}

} // End Kohana_Jam_Association