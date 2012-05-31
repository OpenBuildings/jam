<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Builder SELECT functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.builder
 * @group   jam.builder.select
 */
class Jam_Builder_SelectTest extends Unittest_Jam_TestCase {

	public function test_params()
	{
		$builder = Jam::query('test_post');
		$builder->params(array('param1' => 1));
		$builder->params('param2', 2);
		$builder->params(array('param3' => 3));

		$this->assertEquals(1, $builder->params('param1'));
		$this->assertEquals(2, $builder->params('param2'));
		$this->assertEquals(3, $builder->params('param3'));
	}

	public function test_find()
	{
		$post = Jam::query('test_post')->find(1);
		$this->assertInstanceOf('Model_Test_Post', $post);
		$this->assertTrue($post->loaded());

		$post = Jam::query('test_post')->find_insist(2);
		$this->assertInstanceOf('Model_Test_Post', $post);
		$this->assertTrue($post->loaded());

		$this->setExpectedException('Jam_Exception_NotFound');
		Jam::query('test_post')->find_insist(555);
	}

	public function test_find_multiple()
	{
		// Single element array
		$posts = Jam::query('test_post')->find(array(1));
		$this->assertInstanceOf('Jam_Collection', $posts);
		$this->assertEquals('test_post', $posts->meta()->model(), 'Should have the meta of test_post');
		$this->assertCount(1, $posts, 'Should load only one model');
		$this->assertTrue($posts->exists(1));

		// Zero element array
		$posts = Jam::query('test_post')->find(array());
		$this->assertInstanceOf('Jam_Collection', $posts);
		$this->assertEquals('test_post', $posts->meta()->model(), 'Should have the meta of test_post');
		$this->assertCount(0, $posts, 'Should load zero model');
		
		// Two element array
		$posts = Jam::query('test_post')->find(array(1, 2));
		$this->assertInstanceOf('Jam_Collection', $posts);
		$this->assertEquals('test_post', $posts->meta()->model(), 'Should have the meta of test_post');
		$this->assertCount(2, $posts, 'Should load two model');
		$this->assertTrue($posts->exists(1));
		$this->assertTrue($posts->exists(2));
		
		// Two element array insist
		$posts = Jam::query('test_post')->find_insist(array(1, 2));
		$this->assertInstanceOf('Jam_Collection', $posts);
		$this->assertEquals('test_post', $posts->meta()->model(), 'Should have the meta of test_post');
		$this->assertCount(2, $posts, 'Should load two model');
		$this->assertTrue($posts->exists(1));
		$this->assertTrue($posts->exists(2));

		// Two element array insist exception
		$this->setExpectedException('Jam_Exception_NotFound');
		$posts = Jam::query('test_post')->find_insist(array(1, 2, 555));
	}



	/**
	 * Provides test data for test_multiple_select()
	 *
	 * @return  array
	 */
	public function provider_multiple_select()
	{
		return array(
			// Select all posts
			array(Jam::query('test_post'), 2),
			// Select post with id 1
			array(Jam::query('test_post')->where(':primary_key', '=', 1), 1),
			// Select all posts ordered by is ascending
			array(Jam::query('test_post')->order_by(':primary_key', 'ASC'), 2),
			// Select all posts where id is NULL
			array(Jam::query('test_post')->where(':primary_key', 'IS', NULL), 0),

			// Test aliasing columns
			array(Jam::query('test_author')->order_by('_id', 'ASC'), 3),

			// This does not resolve to any model, but should still work
			array(Jam::query('test_categories_test_posts')->where('test_post:foreign_key', '=', 1), 3, FALSE),

			// This should join both author and approved by author.
			// Since they are both from the same model, we shouldn't
			// have any funny things happening
			array(Jam::query('test_post')->join_association('approved_by'), 1),

			// Miscellaneous things
			array(Jam::query('test_author')->join_association(array('permission')), 2),

			array(Jam::query('test_author')->join_association(array('permission' => 'perms')), 2),

			array(Jam::query('test_post')->select_column('*')->select_column('TRIM("_slug") as trimmed_slug'), 2),

			array(Jam::query('test_post')->select_column('*')->select_column(':primary_key', 'uid'), 2),
			array(Jam::query('test_post')->select_column('*')->select_column(':name_key', 'name_id'), 2),
		);
	}

	/**
	 * Tests basic SELECT functionality and that collections are returned
	 * relatively sane.
	 *
	 * @dataProvider  provider_multiple_select
	 * @param         Jam                     $result
	 * @param         int                       $count
	 * @param         bool                      $is_model
	 * @return        void
	 */
	public function test_multiple_select($result, $count, $is_model = TRUE)
	{
		// Set database connection name
		$db = parent::$database_connection;

		// Ensure the count matches a count() query
		$this->assertEquals($result->count($db), $count);

		// We can now get our collection
		$result = $result->select($db);

		// Ensure we have a collection and our counts match
		$this->assertTrue($result instanceof Jam_Collection);
		$this->assertEquals(count($result), $count);

		// Ensure we can loop through them and all models are loaded
		$verify = 0;

		foreach ($result as $model)
		{
			if ($is_model)
			{
				$this->assertTrue($model->loaded());
				$this->assertTrue($model->saved());
				$this->assertGreaterThan(0, $model->id);
			}

			$verify++;
		}

		// Ensure the loop and result was the same
		$this->assertEquals($verify, $count);
	}

	/**
	 * Provides test data for test_single_select()
	 *
	 * @return  array
	 */
	public function provider_single_select()
	{
		return array(
			// Select post with id 1
			array(Jam::query('test_post', 1), TRUE),
			// Select post with id 0
			array(Jam::query('test_post', 0), FALSE),
			// Select post with id 1 using where statement and limiting to 1
			array(Jam::query('test_post')->where(':primary_key', '=', 1)->limit(1), TRUE),
			// Select post with id 1 and order it by id ascending
			array(Jam::query('test_post', 1)->order_by(':primary_key', 'ASC'), TRUE),
			// Select post by name key 
			array(Jam::query('test_post', 'First Post'), TRUE),
			// Select post by name key missing
			array(Jam::query('test_post', 'First Post Not Exists'), FALSE),
		);
	}

	/**
	 * Tests returning a model directly from a SELECT.
	 *
	 * @dataProvider  provider_single_select
	 * @param         Jam                   $model
	 * @param         bool                    $exists
	 * @return        void
	 */
	public function test_single_select($builder, $exists)
	{
		$model = $builder->select();
		$this->assertTrue($model instanceof Jam_Model);

		if ($exists)
		{
			$this->assertTrue($model->loaded());
			$this->assertTrue($model->saved());
			$this->assertTrue($model->id > 0);
		}
		else
		{
			$this->assertFalse($model->loaded());
			$this->assertFalse($model->saved());
			$this->assertTrue($model->id === $model->meta()->field('id')->default);
		}
	}

	// /**
	//  * Provides test data for test_with()
	//  *
	//  * @return  array
	//  */
	// public function provider_with()
	// {
	// 	return array(
	// 		// Single 'with' using non-standard relationship naming
	// 		array(Jam::query('test_post'), array('approved_by')),
	// 		// Multiple 'with' using non-standard relationship naming
	// 		array(Jam::query('test_post'), array('approved_by', 'permission')),
	// 	);
	// }

	// /**
	//  * Tests for with()
	//  *
	//  * @dataProvider  provider_with
	//  * @param         Jam          $query
	//  * @param         array          $with
	//  * @return        void
	//  */
	// public function test_with($query, $with)
	// {
	// 	// Load query
	// 	$query = $query->with(implode(':', $with))->select();

	// 	// Ensure we find the proper columns in the result
	// 	foreach ($query->as_array() as $array)
	// 	{
	// 		$this->assertTrue(array_key_exists(':test_author:id', $array));
	// 		$this->assertTrue(array_key_exists(':approved_by:id', $array));
	// 	}

	// 	// Ensure we can actually access the models
	// 	foreach ($query as $model)
	// 	{
	// 		$this->assertTrue($model->test_author instanceof Model_Test_Author);
	// 		$this->assertTrue($model->test_author->permission instanceof Model_Test_Role);
	// 	}
	// }

	/**
	 * Provides test data for test_as_object()
	 *
	 * @return  array
	 */
	public function provider_as_object()
	{
		return array(
			array(Jam::query('test_post')->select(), 'Model_Test_Post'),
			array(Jam::query('test_post')->as_object('Model_Test_Post')->select(), 'Model_Test_Post'),
			array(Jam::query('test_post')->as_object(TRUE)->select(), 'Model_Test_Post'),
			array(Jam::query('test_post')->as_assoc()->select(), FALSE),
			array(Jam::query('test_post')->as_object(FALSE)->select(), FALSE),
		);
	}

	/**
	 * Tests for Jam_Builder::as_object()
	 *
	 * @dataProvider  provider_as_object
	 * @param         Jam               $result
	 * @param         string|bool         $class
	 * @return        void
	 */
	public function test_as_object($result, $class)
	{
		if ($class)
		{
			$this->assertTrue($result->current() instanceof $class);
		}
		else
		{
			$this->assertTrue(is_array($result->current()));
		}
	}

	// /**
	//  * Test for issue #58 that ensures count() uses any load_with
	//  * conditions specified.
	//  *
	//  * @return  void
	//  */
	// public function test_count_uses_load_with()
	// {
	// 	$count = Jam::query('test_post')
	// 		// Where condition includes a column from joined table
	// 		// this will cause a SQL error if load_with hasn't been taken into account
	// 		->where(':test_author.name', '=', 'Jonathan Geiger')
	// 		->count();

	// 	$this->assertEquals(2, $count);
	// }

	/**
	 * Test for Issue #95. This only fails when testing on Postgres.
	 *
	 * @return  void
	 */
	public function test_count_works_on_postgres()
	{
		// Should discard the select and order_by clauses
		Jam::query('test_post')
			 ->select_column('foo')
			 ->order_by('foo')
			 ->count();
	}

} // End Jam_Builder_SelectTest