<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Model functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.select
 */
class Jam_SelectTest extends Unittest_Jam_TestCase {

	public function test_constructor()
	{
		$select = Jam::select('test_author');
		$this->assertInstanceOf('Jam_Select', $select, 'Should get the base select class');

		$select = Jam::select('test_alias');
		$this->assertInstanceOf('Jam_Select_Test_Alias', $select, 'Should be extended builder class');
	}
}