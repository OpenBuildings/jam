<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents an image copyright in the database.
 *
 * @package  Jam
 */
class Model_Test_Copyright extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Kohana::TESTING);

		$meta->associations(array(
			'test_image'     => Jam::association('belongsto', array('dependent' => Jam_Association::DELETE)),
		));

		// Set fields
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'name'            => Jam::field('string'),
		));
	}

} // End Model_Test_Post
