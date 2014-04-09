<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Builder SELECT functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.behavior
 * @group   jam.behavior.nested
 */
class Jam_Behavior_NestedTest extends Testcase_Database {


	public function provider_builder_root()
	{
		return array(
			array(TRUE, 'SELECT `test_categories`.* FROM `test_categories` WHERE (`test_categories`.`parent_id` = 0 OR `test_categories`.`parent_id` IS NULL)'),
			array(FALSE, 'SELECT `test_categories`.* FROM `test_categories` WHERE `test_categories`.`parent_id` != 0 AND `test_categories`.`parent_id` IS NOT NULL'),
		);
	}

	/**
	 * @dataProvider provider_builder_root
	 */
	public function test_builder_root($is_root, $expected_sql)
	{
		$this->assertEquals($expected_sql, (string) Jam::all('test_category')->root($is_root));
	}

	public function test_is_root()
	{
		$category = Jam::build('test_category')->load_fields(array('id' => 1, 'name' => 'Category 1', 'parent_id' => 0));

		$this->assertTrue($category->is_root());

		$category = Jam::build('test_category')->load_fields(array('id' => 2, 'name' => 'Category 2', 'parent_id' => 1));

		$this->assertFalse($category->is_root());
	}

	public function test_all_parents()
	{
		$category = Jam::build('test_category')->load_fields(array('id' => 1, 'name' => 'Category 1', 'parent_id' => 0));

		$expected_sql = 'SELECT `test_categories`.* FROM `test_categories` JOIN (SELECT @row AS `_id`, (SELECT @row := `parent_id` FROM `test_categories` WHERE `id` = _id) AS `parent_id`, @l := @l + 1 AS `lvl` FROM (SELECT @row := NULL, @l := 0) AS `vars`, `test_categories` WHERE @row != 0) AS `recursion_table` ON (`recursion_table`.`_id` = `test_categories`.`id`) ORDER BY `recursion_table`.`lvl` DESC';

		$this->assertEquals($expected_sql, (string) $category->parents());
	}
}
