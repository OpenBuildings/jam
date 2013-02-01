<?php defined('SYSPATH') OR die('No direct script access.');

class Unittest_Jam_TestCase extends Unittest_TestCase {

	public function test__dummy()
	{
		
	}

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

	public function build_collection($model, array $collection_result)
	{
		$collection = new Jam_Query_Builder_Collection($model);
		return $collection->load_fields($collection_result);	
	}

	public function build_model($model, $data)
	{
		return Jam::build($model)->load_fields($data);
	}
}