<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Generate a model file
 *
 * @package Jam tart
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Minion_Jam_Generate {

	public static function modify_file($file, $content, $force = FALSE, $unlink = FALSE)
	{
		if ($unlink)
		{
			$file_already_exists = is_file($file);

			if ($file_already_exists)
			{
				unlink($file);
				Minion_CLI::write(Minion_CLI::color('Removed file '.Debug::path($file), 'light_green'));
			}
			else
			{
				Minion_CLI::write(Minion_CLI::color('File does not exist '.Debug::path($file), 'brown'));
			}
		}
		elseif ($force)
		{
			$file_already_exists = is_file($file);

			if ( ! is_dir(dirname($file)))
			{
				mkdir(dirname($file), 0777, TRUE);
			}

			file_put_contents($file, $content);

			if ($file_already_exists)
			{
				Minion_CLI::write(Minion_CLI::color('Overwritten file '.Debug::path($file), 'brown'));
			}
			else
			{
				Minion_CLI::write(Minion_CLI::color('Generated file '.Debug::path($file), 'light_green'));
			}
		}
		else
		{
			if (is_file($file))
			{
				Minion_CLI::write(Minion_CLI::color('File already exists '.Debug::path($file), 'brown'));
			}
			else
			{
				if ( ! is_dir(dirname($file)))
				{
					mkdir(dirname($file), 0777, TRUE);
				}

				file_put_contents($file, $content);

				Minion_CLI::write(Minion_CLI::color('Generated file '.Debug::path($file), 'light_green'));
			}
		}
	}
}
