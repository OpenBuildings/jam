<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jam
 * @group   jam
 * @group   jam.jam
 */
class Jam_JamTest extends Testcase_Database {

	public function test_find()
	{
		$result = Jam::find('test_blog', 1);


		$this->assertInstanceOf('Model_Test_Blog', $result);
		$this->assertEquals(1, $result->id());

		$result = Jam::find('test_blog', array(1, 2));

		$this->assertCount(2, $result);

		$this->assertContainsOnlyInstancesOf('Model_Test_Blog', $result);
		$this->assertEquals(array(1, 2), $result->as_array(NULL, 'id'));

		$result = Jam::find('test_blog', 423948);

		$this->assertNull($result);
	}

	/**
	 * @expectedException Jam_Exception_Invalidargument
	 */
	public function test_find_invalid_id()
	{
		Jam::find('test_blog', NULL);
	}

	/**
	 * @expectedException Jam_Exception_Invalidargument
	 */
	public function test_find_invalid_ids()
	{
		Jam::find('test_blog', array());
	}

	public function test_find_insist()
	{
		$result = Jam::find_insist('test_blog', 1);

		$this->assertInstanceOf('Model_Test_Blog', $result);
		$this->assertEquals(1, $result->id());

		$result = Jam::find_insist('test_blog', array(1, 2));

		$this->assertCount(2, $result);

		$this->assertContainsOnlyInstancesOf('Model_Test_Blog', $result);
		$this->assertEquals(array(1, 2), $result->as_array(NULL, 'id'));
	}

	/**
	 * @expectedException Jam_Exception_Invalidargument
	 */
	public function test_find_insist_invalid_id()
	{
		Jam::find_insist('test_blog', NULL);
	}

	/**
	 * @expectedException Jam_Exception_Notfound
	 */
	public function test_find_insist_notfound()
	{
		Jam::find_insist('test_blog', 31231123);
	}

	/**
	 * @expectedException Jam_Exception_Invalidargument
	 */
	public function test_find_insist_invalid_ids()
	{
		Jam::find_insist('test_blog', array());
	}

	/**
	 * @expectedException Jam_Exception_Notfound
	 */
	public function test_find_insist_notfound_array()
	{
		Jam::find_insist('test_blog', array(1, 47583475));
	}
}
