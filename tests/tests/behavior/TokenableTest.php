<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Builder Sortable Behavior functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.behavior
 * @group   jam.behavior.tokenable
 */
class Jam_Behavior_TokenableTest extends Testcase_Database {

	public function test_initialize()
	{
		$this->assertNotNull(Jam::meta('test_video')->field('token'));
	}

	public function test_before_create()
	{
		$video = Jam::create('test_video', array('file' => 'test.mp4'));

		$this->assertNotNull($video->token);
	}

	public static function new_token_test()
	{
		return 'new test token';
	}

	public function test_new_token()
	{
		$behavior = new Jam_Behavior_Tokenable();

		foreach (range(0, 10) as $i)
		{
			$token = $behavior->new_token();
			$this->assertRegExp('/[a-z0-9]/', $token);
		}

		$behavior = new Jam_Behavior_Tokenable(array('uppercase' => TRUE));

		foreach (range(0, 10) as $i)
		{
			$token = $behavior->new_token();
			$this->assertRegExp('/[A-Z0-9]/', $token);
		}

		$behavior = new Jam_Behavior_Tokenable(array('token_function' => 'Jam_Behavior_TokenableTest::new_token_test'));

		$token = $behavior->new_token();

		$this->assertEquals('new test token', $token);
	}
}
