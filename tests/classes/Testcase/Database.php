<?php defined('SYSPATH') OR die('No direct script access.');

use PHPUnit\Framework\TestCase;

abstract class Testcase_Database extends TestCase {

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
