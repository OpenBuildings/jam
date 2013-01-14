<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Collection.
 *
 * @package Jam
 * @group   jam
 * @group   jam.countcache
 */
class Jam_CountercacheTest extends Unittest_Jam_TestCase {

	public function data_update_counters()
	{
		return array(
			array('test_blog', 5, array('test_counts' => -1), 'asd'),
		);
	}

	/**
	 * @dataProvider data_update_counters
	 */
	public function test_update_counters($model, $ids, $counters, $expected_sql)
	{
		$this->assertEquals($expected_sql, (string) Jam_Countcache::update_counters($model, $ids, $counters), 'message');
	}
}