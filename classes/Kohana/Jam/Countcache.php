<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles count cache counting for hasmany
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Countcache {

	public static function update_counters($model, $ids, array $counters)
	{
		$query = Jam::update($model);

		foreach ($counters as $name => $change)
		{
			$change = (int) $change;
			$operator = $change < 0 ? '-' : '+';

			$query->value($name, DB::expr(strtr('(COALESCE(:name, 0) :operator :value)', array(
				':value' => abs($change),
				':operator' => $operator,
				':name' => Database::instance($query->meta()->db())->quote_column($name),
			))));
		}

		return $query->where(':primary_key', 'IN', (array) $ids);
	}

	public static function increment($model, $counter, $id)
	{
		Jam_CountCache::update_counters($model, $id, array($counter => +1))->execute();
	}

	public static function decrement($model, $counter, $id)
	{
		Jam_CountCache::update_counters($model, $id, array($counter => -1))->execute();
	}
}
