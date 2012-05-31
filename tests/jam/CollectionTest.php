<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Collection.
 *
 * @package Jam
 * @group   jam
 * @group   jam.collection
 */
class Jam_CollectionTest extends Unittest_Jam_TestCase {

	/**
	 * Provider for test type
	 */
	public function provider_construction()
	{
		// Set database connection name
		$db = parent::$database_connection;

		// Set result
		$result = DB::select()->from('test_posts');
		
		return array(
			array(new Jam_Collection($result->execute($db), 'Model_Test_Post'), 'Model_Test_Post'),
			array(new Jam_Collection($result->execute($db), Jam::factory('test_post')), 'Model_Test_Post'),
			array(new Jam_Collection($result->execute($db)), FALSE),
			array(new Jam_Collection($result->execute($db), 'Model_Test_Post'), 'Model_Test_Post'),
			array(new Jam_Collection($result->as_object()->execute($db)), FALSE),
			array(new Jam_Collection(array(array('id' => 1, 'test_author_id' => 1, 'nam' => 'NAME'))), FALSE),
			array(new Jam_Collection(array(array('id' => 1, 'test_author_id' => 1, 'nam' => 'NAME')), 'Model_Test_Post'), 'Model_Test_Post'),
			array(new Jam_Collection(array(), 'Model_Test_Post'), 'Model_Test_Post'),
			array(new Jam_Collection(NULL, 'Model_Test_Post'), 'Model_Test_Post'),
			array(new Jam_Collection('', 'Model_Test_Post'), 'Model_Test_Post'),
		);
	}
	
	/**
	 * Tests Jam_Collections properly handle database results and 
	 * different types of return values.
	 *
	 * @dataProvider  provider_construction
	 */
	public function test_construction($result, $class)
	{
		if (is_string($class))
		{
			$this->assertInstanceOf($class, $result->current());
		}
		else
		{
			$this->assertInternalType('array', $result->current());
		}
	}

	public function test_as_array()
	{
		$posts = Jam::query('test_post')->select_all();
		$posts_array = $posts->as_array();

		$this->assertEquals(count($posts), count($posts_array), 'Should have the same count of posts');

		foreach ($posts_array as $post) 
		{
			$this->assertInstanceOf('Model_Test_Post', $post, 'Should be a model object');
			$this->assertTrue($posts->exists($post), 'Should have the post in the posts array');
		}

		$posts_keys = $posts->as_array(':primary_key');

		foreach ($posts_keys as $key => $post) 
		{
			$this->assertInstanceOf('Model_Test_Post', $post, 'Should be a model object');
			$this->assertTrue($posts->exists($post), 'Should have the post in the posts array');
			$this->assertEquals($post->id(), $key, 'Shoul have a key primary_key');
		}

		$name_keys = $posts->as_array(':name_key');

		foreach ($name_keys as $name => $post) 
		{
			$this->assertInstanceOf('Model_Test_Post', $post, 'Should be a model object');
			$this->assertTrue($posts->exists($post), 'Should have the post in the posts array');
			$this->assertEquals($post->name(), $name, 'Shoul have a key name_key');
		}

		$keys = $posts->as_array(NULL, ':primary_key');
		foreach ($keys as $i => $key) 
		{
			$this->assertTrue(is_numeric($key), 'the primary_key should be numeric');
			$this->assertEquals($posts[$i]->id(), $key);
		}

		$names = $posts->as_array(NULL, ':name_key');
		foreach ($names as $i => $name) 
		{
			$this->assertInternalType('string', $name, 'the name_key should be string');
			$this->assertEquals($posts[$i]->name(), $name);
		}

		$key_names = $posts->as_array(':primary_key', ':name_key');
		$i = 0;
		foreach ($key_names as $key => $name) 
		{
			$this->assertTrue(is_numeric($key), 'the primary_key should be numeric');
			$this->assertInternalType('string', $name, 'the name_key should be string');
			$this->assertEquals($posts[$i]->name(), $name);
			$this->assertEquals($posts[$i]->id(), $key);
			$i++;
		}
	}

	public function test_first()
	{
		$collection = Jam::query('test_post')->select_all();
		$this->assertInstanceOf('Model_Test_Post', $collection->first());
		$this->assertEquals($collection->first()->id(), $collection[0]->id());
	}
	
	public function test_last()
	{
		$collection = Jam::query('test_post')->select_all();
		$this->assertInstanceOf('Model_Test_Post', $collection->first());
		$this->assertEquals($collection->last()->id(), $collection[count($collection)-1]->id());
	}
	
	public function test_at()
	{
		$collection = Jam::query('test_post')->select_all();
		$this->assertInstanceOf('Model_Test_Post', $collection->at(1));
		$this->assertEquals($collection->at(1)->id(), $collection[1]->id());
	}

	public function test_result_changing()
	{
		$post = Jam::factory('test_post', 1);
		$tags = array();
		foreach ($post->test_tags as $i => $tag) 
		{
			$tags[$i] = $tag;
			$post->test_tags[$i] = $tag;
		}

		foreach ($tags as $i => $tag) 
		{
			$this->assertSame($tag, $post->test_tags[$i], 'should be the same object');
		}

		foreach ($tags as $i => & $tag) 
		{
			$tag = $tag->as_array();
		}

		$post->test_tags = $tags;

		foreach ($tags as $i => $tag) 
		{
			$this->assertEquals($tag, $post->test_tags[$i]->as_array(), 'should be the same object');
		}
	}

}