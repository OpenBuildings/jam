<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jam
 * @group   jam
 * @group   jam.countcache
 */
class Jam_CountercacheTest extends PHPUnit_Framework_TestCase {

	public function data_update_counters()
	{
		return array(
			array('test_blog', 5, array('test_counts' => -1), 'UPDATE `test_blogs` SET `test_counts` = (COALESCE(`test_counts`, 0) - 1) WHERE `test_blogs`.`id` IN (5)'),
			array('test_blog', array(5, 10), array('test_counts' => 1), 'UPDATE `test_blogs` SET `test_counts` = (COALESCE(`test_counts`, 0) + 1) WHERE `test_blogs`.`id` IN (5, 10)'),
			array('test_blog', array(5, 10), array('test_counts' => 1, 'test_tags' => -1), 'UPDATE `test_blogs` SET `test_counts` = (COALESCE(`test_counts`, 0) + 1), `test_tags` = (COALESCE(`test_tags`, 0) - 1) WHERE `test_blogs`.`id` IN (5, 10)'),
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
