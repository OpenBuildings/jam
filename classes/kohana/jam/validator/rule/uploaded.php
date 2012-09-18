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
	public $maximum_width;
	public $maximum_height;

	public function valid_extensions()
	{
		$extensions = $this->only;

		if ($extensions == 'image')
		{
			$extensions = array('jpg', 'jpeg', 'png', 'gif');
		}

		return $extensions;
	}

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		if ( ! $value->is_empty() AND $value->source())
		{
			if ( ! is_file($value->file()))
			{
				$model->errors()->add($attribute, 'uploaded_is_file');
			}
			elseif ($this->only AND ! in_array(pathinfo($value->filename(), PATHINFO_EXTENSION), $this->valid_extensions()))
			{
				$model->errors()->add($attribute, 'uploaded_extension', array(':extension' => join(', ', $this->valid_extensions())));
			}
			elseif ($this->minimum_width OR $this->minimum_height OR $this->maximum_width OR $this->maximum_height)
			{
				$dims = @ getimagesize($value->file());
				if ($dims)
				{
					list($width, $height) = $dims;
					if ($this->minimum_width AND $width < $this->minimum_width)
					{
						$model->errors()->add($attribute, 'uploaded_minimum_width', array(':minimum_width' => $this->minimum_width));		
					}
					if ($this->minimum_height AND $height < $this->minimum_height)
					{
						$model->errors()->add($attribute, 'uploaded_minimum_height', array(':minimum_height' => $this->minimum_height));		
					}
					if ($this->maximum_width AND $width > $this->maximum_width)
					{
						$model->errors()->add($attribute, 'uploaded_maximum_width', array(':maximum_width' => $this->maximum_width));		
					}
					if ($this->maximum_height AND $height > $this->maximum_height)
					{
						$model->errors()->add($attribute, 'uploaded_maximum_height', array(':maximum_height' => $this->maximum_height));		
					}
				}
			}
		}
	}
}