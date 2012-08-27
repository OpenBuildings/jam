<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Image Layout Aspect.
 *
 * @package image-layout
 * @group   image-layout
 * @group   image-layout.aspect
 */
class AspectTest extends Unittest_TestCase {

	public function data_zero_dimensions()
	{
		return array(
			array(0, 100),
			array(100, 0),
			array(0, 0),
		);
	}

	/**
	 * @dataProvider data_zero_dimensions
	 */
	public function test_zero_dimensions($width, $height)
	{
		$this->setExpectedException('Kohana_Exception');
		
		new Aspect($width, $height);
	}

	public function data_construct()
	{
		return array(
			array(100, 500, 0.2, TRUE, FALSE),
			array(500, 200, 2.5, FALSE, TRUE),
			array(300, 300, 1, FALSE, TRUE),
		);
	}

	/**
	 * @dataProvider data_construct
	 */
	public function test_construct($width, $height, $expected_ratio, $expected_is_portrait, $expected_is_landscape)
	{
		$aspect = new Aspect($width, $height);

		$this->assertEquals($width, $aspect->width());
		$this->assertEquals($height, $aspect->height());
		$this->assertEquals($expected_ratio, $aspect->ratio());
		$this->assertEquals($expected_is_portrait, $aspect->is_portrait());
		$this->assertEquals($expected_is_landscape, $aspect->is_landscape());
	}

	public function data_width()
	{
		return array(
			array(100, 500, 50, 250),
			array(500, 200, 100, 40),
			array(300, 300, 200, 200),
		);
	}

	/**
	 * @dataProvider data_width
	 */
	public function test_width($width, $height, $new_width, $expected_height)
	{
		$aspect = new Aspect($width, $height);

		$aspect->width($new_width);

		$this->assertEquals($expected_height, $aspect->height());
	}

	public function data_height()
	{
		return array(
			array(100, 500, 50, 10),
			array(500, 200, 100, 250),
			array(300, 300, 200, 200),
		);
	}

	/**
	 * @dataProvider data_height
	 */
	public function test_height($width, $height, $new_height, $expected_width)
	{
		$aspect = new Aspect($width, $height);

		$aspect->height($new_height);

		$this->assertEquals($expected_width, $aspect->width());
	}

	public function data_constrain()
	{
		return array(
			array(100, 500, 100, 100, array('width' => 20, 'height' => 100, 'x' => 40, 'y' => 0)),
			array(500, 200, 100, 100, array('width' => 100, 'height' => 40, 'x' => 0, 'y' => 30)),
			array(300, 300, 100, 100, array('width' => 100, 'height' => 100, 'x' => 0, 'y' => 0)),
		);
	}

	/**
	 * @dataProvider data_constrain
	 */
	public function test_constrain($width, $height, $constrain_width, $constrain_height, $expected)
	{
		$aspect = new Aspect($width, $height);

		$aspect->constrain($constrain_width, $constrain_height);

		$this->assertEquals($expected, $aspect->as_array());
	}

	public function data_crop()
	{
		return array(
			array(100, 500, 100, 100, array('width' => 100, 'height' => 500, 'x' => 0, 'y' => -200)),
			array(500, 200, 100, 100, array('width' => 250, 'height' => 100, 'x' => -75, 'y' => 0)),
			array(300, 300, 100, 100, array('width' => 100, 'height' => 100, 'x' => 0, 'y' => 0)),
		);
	}

	/**
	 * @dataProvider data_crop
	 */
	public function test_crop($width, $height, $crop_width, $crop_height, $expected)
	{
		$aspect = new Aspect($width, $height);

		$aspect->crop($crop_width, $crop_height);

		$this->assertEquals($expected, $aspect->as_array());
	}

	public function data_center()
	{
		return array(
			array(100, 500, 100, 100, array('width' => 100, 'height' => 500, 'x' => 0, 'y' => -200)),
			array(500, 200, 1000, 1000, array('width' => 500, 'height' => 200, 'x' => 250, 'y' => 400)),
			array(300, 300, 500, 500, array('width' => 300, 'height' => 300, 'x' => 100, 'y' => 100)),
		);
	}

	/**
	 * @dataProvider data_center
	 */
	public function test_center($width, $height, $center_width, $center_height, $expected)
	{
		$aspect = new Aspect($width, $height);

		$aspect->center($center_width, $center_height);

		$this->assertEquals($expected, $aspect->as_array());
	}


}