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
		$meta->db(Kohana::TESTING);

		$meta->associations(array(
			'test_owner'             => Jam::association('belongsto', array('foreign_model' => 'test_author', 'foreign_key' => 'test_owner_id')),
			'test_featured_category' => Jam::association('hasone', array(
				'foreign_model' => 'test_category'
			)),
			'test_posts'             => Jam::association('hasmany', array('inverse_of' => 'test_blog', 'count_cache' => TRUE, 'dependent' => Jam_Association::DELETE)),
			'test_categories'        => Jam::association('hasmany', array('required' => TRUE, 'inverse_of' => 'test_blog')),
			'test_tags'              => Jam::association('manytomany', array())
		));

		// Define fields
		$meta->fields(array(
			'id'               => Jam::field('primary'),
			'name'             => Jam::field('string'),
			'url'              => Jam::field('weblink'),
			'test_posts_count' => Jam::field('integer'),
		 ));
	}

} // End Model_Test_Author
