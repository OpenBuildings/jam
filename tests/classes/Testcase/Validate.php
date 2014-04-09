<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Testcase_Validate extends PHPUnit_Framework_TestCase {

	public function assertHasError($model, $attribute, $error)
	{
		$errors = $model->errors()->as_array();

		$this->assertArrayHasKey($error, (array) Arr::get($errors, $attribute), 'Should have error '.$error.' for '.$attribute);
	}

	public function assertNotHasError($model, $attribute, $error)
	{
		$errors = $model->errors()->as_array();

		$this->assertArrayNotHasKey($error, (array) Arr::get($errors, $attribute), 'Should not have error '.$error.' for '.$attribute);
	}
}
