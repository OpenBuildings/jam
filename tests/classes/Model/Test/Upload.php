<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents an upload item in the database.
 *
 * @package  Jam
 */
class Model_Test_Upload extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Kohana::TESTING);

		// Define fields
		$meta->fields(array(
			'id'         => Jam::field('primary'),
			'file'       => Jam::field('upload', array(
				'server' => 'test_local'
			)),
			'file2'       => Jam::field('upload', array(
				'server' => 'test_local',
				'path' => 'test/:model/:model:id',
			))
		 ));
	}

} // End Model_Test_Author
