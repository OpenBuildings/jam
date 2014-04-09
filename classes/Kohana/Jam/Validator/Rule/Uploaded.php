<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Validatior Rule
 *
 * @package    Jam
 * @category   Validation
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Validator_Rule_Uploaded extends Jam_Validator_Rule {

	/**
	 * array of allowed extensions
	 * @var array
	 */
	public $only = array();

	public $minimum_width;
	public $minimum_height;
	public $minimum_size;

	public $maximum_width;
	public $maximum_height;
	public $maximum_size;

	public $exact_width;
	public $exact_height;
	public $exact_size;

	public function valid_extensions()
	{
		$extensions = $this->only;

		if ($extensions == 'image')
		{
			$extensions = array('jpg', 'jpeg', 'png', 'gif');
		}

		return array_map('strtolower', $extensions);
	}

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		if ($value AND ! $value->is_empty() AND $value->source())
		{
			if (($error = $value->source()->error()))
			{
				$model->errors()->add($attribute, 'uploaded_native', array(':message' => $error));
			}
			elseif ( ! is_file($value->file()))
			{
				$model->errors()->add($attribute, 'uploaded_is_file');
			}
			if ($this->only AND ! in_array(strtolower(pathinfo($value->filename(), PATHINFO_EXTENSION)), $this->valid_extensions()))
			{
				$model->errors()->add($attribute, 'uploaded_extension', array(':extension' => join(', ', $this->valid_extensions())));
			}
			if ($this->minimum_size OR $this->maximum_size OR $this->exact_size)
			{
				$size = @ filesize($value->file());

				if ($this->minimum_size AND $minimum_size = Num::bytes($this->minimum_size) AND (int) $size < (int) $minimum_size)
				{
					$model->errors()->add($attribute, 'uploaded_minimum_size', array(':minimum_size' => $this->minimum_size));
				}
				if ($this->maximum_size AND $maximum_size = Num::bytes($this->maximum_size) AND (int) $size > (int) $maximum_size)
				{
					$model->errors()->add($attribute, 'uploaded_maximum_size', array(':maximum_size' => $this->maximum_size));
				}
				if ($this->exact_size AND $exact_size = Num::bytes($this->exact_size) AND (int) $size !== (int) $exact_size)
				{
					$model->errors()->add($attribute, 'uploaded_exact_size', array(':exact_size' => $this->exact_size));
				}
			}
			if ($this->minimum_width OR $this->minimum_height OR $this->maximum_width OR $this->maximum_height OR $this->exact_width OR $this->exact_height)
			{
				$dims = @ getimagesize($value->file());
				if ($dims)
				{
					list($width, $height) = $dims;

					if ($this->exact_width AND (int) $width !== (int) $this->exact_width)
					{
						$model->errors()->add($attribute, 'uploaded_exact_width', array(':exact_width' => $this->exact_width));
					}
					if ($this->exact_height AND (int) $height !== (int) $this->exact_height)
					{
						$model->errors()->add($attribute, 'uploaded_exact_height', array(':exact_height' => $this->exact_height));
					}
					if ($this->minimum_width AND (int) $width < (int) $this->minimum_width)
					{
						$model->errors()->add($attribute, 'uploaded_minimum_width', array(':minimum_width' => $this->minimum_width));
					}
					if ($this->minimum_height AND (int) $height < (int) $this->minimum_height)
					{
						$model->errors()->add($attribute, 'uploaded_minimum_height', array(':minimum_height' => $this->minimum_height));
					}
					if ($this->maximum_width AND (int) $width > (int) $this->maximum_width)
					{
						$model->errors()->add($attribute, 'uploaded_maximum_width', array(':maximum_width' => $this->maximum_width));
					}
					if ($this->maximum_height AND (int) $height > (int) $this->maximum_height)
					{
						$model->errors()->add($attribute, 'uploaded_maximum_height', array(':maximum_height' => $this->maximum_height));
					}
				}
			}
		}
	}
}
