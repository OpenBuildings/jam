<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents an image in the database.
 *
 * @package  Jam
 */
class Model_Test_Image extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Kohana::TESTING);

		$meta->associations(array(
			'test_holder'     => Jam::association('belongsto', array('polymorphic' => TRUE)),
			'test_copyright'     => Jam::association('hasone', array('dependent' => Jam_Association::ERASE)),
			'test_copyrights'     => Jam::association('hasmany', array('dependent' => Jam_Association::ERASE)),
		));

		// Set fields
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'file'            => Jam::field('upload', array(
				'delete_file' => TRUE,
			)),
		));
	}

} // End Model_Test_Post
