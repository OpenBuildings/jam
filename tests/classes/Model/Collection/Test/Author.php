<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents an author in the database.
 *
 * @package  Jam
 */
class Model_Collection_Test_Author extends Jam_Query_Builder_Collection {

	public function where_author($id)
	{
		return $this->where_key($id);
	}
}
