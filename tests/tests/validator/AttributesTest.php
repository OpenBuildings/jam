<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.attributes
 */
class Jam_Validator_AttributesTest extends Testcase_Validate {

	public function test_permit()
	{
		$data = array(
			'test' => 10,
			'test2' => 20,
			'test_object' => array(
				'var1' => 5,
				'var2' => 10
			),
			'test_array' => array(
				array('name' => 8, 'id' => 1),
				array('name' => 9, 'id' => 2),
			)
		);

		$clean = Jam::permit(array('test'), $data);
		$this->assertEquals(array('test' => 10), $clean);

		$clean = Jam::permit(array('test', 'test2'), $data);
		$this->assertEquals(array('test' => 10, 'test2' => 20), $clean);

		$clean = Jam::permit(array('test', 'test2', 'test_object', 'test_array'), $data);
		$this->assertEquals($data, $clean);

		$clean = Jam::permit(array('test', 'test2', 'test_object' => array('var1')), $data);
		$this->assertEquals(array('test' => 10, 'test2' => 20, 'test_object' => array('var1' => 5)), $clean);

		$clean = Jam::permit(array('test', 'test2', 'test_array' => array('name')), $data);
		$this->assertEquals(array('test' => 10, 'test2' => 20, 'test_array' => array(array('name' => 8), array('name' => 9))), $clean);
	}
}
