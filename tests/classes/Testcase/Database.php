<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Testcase_Database extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		parent::setUp();
		Database::instance(Kohana::TESTING)->begin();
	}

	public function tearDown()
	{
		Database::instance(Kohana::TESTING)->rollback();
		parent::tearDown();
	}
}
