<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents a blog in the database.
 *
 * @package  Jam
 */
class Model_Test_Blog extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Unittest_Jam_Testcase::$database_connection);

		$meta->associations(array(
			'test_owner'             => Jam::association('belongsto'),
			'test_featured_category' => Jam::association('hasone', array(
				'foreign' => 'test_category',
				'conditions' => array(
					'where' => array('test_category.is_featured', '=', TRUE),
					'limit' => array(1)
				)
			)),
			'test_posts'             => Jam::association('hasmany', array('inverse_of' => 'test_blog', 'dependent' => Jam_Association::DELETE)),
			'test_categories'        => Jam::association('hasmany', array('required' => TRUE, 'inverse_of' => 'test_blog')),
			'test_tags'              => Jam::association('manytomany', array(
				'required' => TRUE
			))
		));

		// Define fields
		$meta->fields(array(
			'id'         => Jam::field('primary'),
			'name'       => Jam::field('string'),
			'url'        => Jam::field('weblink'),
			// Aliases for testing
			'_id'        => 'id',
		 ));
	}

} // End Model_Test_Author