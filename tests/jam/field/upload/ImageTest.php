<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Tests for core Jam methods.
 * @group jam
 * @group jam.field
 * @group jam.field.upload_image
 * @package Jam
 */
if ( ! defined("Jam_UPLOAD_TEMP"))
	define("Jam_UPLOAD_TEMP", MODPATH . "extensions/jam/tests/test_data/temp");
	
if ( ! defined("Jam_UPLOAD_TEST_LOCAL"))
	define("Jam_UPLOAD_TEST_LOCAL", MODPATH . "extensions/jam/tests/test_data/test_local");

class Jam_Field_Upload_ImageTest extends Unittest_TestCase {

	protected $environmentDefault = array(
		'jam.upload.temp' => array(
			'path' => Jam_UPLOAD_TEMP,
			'web' => '/temp/',
		),
		'jam.upload.servers' => array(
			'test_local' => array(
				'type' => 'local',
				'params' => array(
					'path' => Jam_UPLOAD_TEST_LOCAL,
					'web' => 'upload',
				),
			),
		),
	);

	static public function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
		
		if ( ! is_dir(Jam_UPLOAD_LOCAL))
		{
			mkdir(Jam_UPLOAD_LOCAL, 0777, true);	
		}
	}	

	public function test_test()
	{
		
	}
	

}