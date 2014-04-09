<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents a specific role in the database.
 *
 * @package  Jam
 */
class Model_Test_Position_Big extends Model_Test_Position {

	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);
		$meta
			->table('test_positions')
			->field('size', Jam::field('string'));
	}

} // End Model_Test_Role
