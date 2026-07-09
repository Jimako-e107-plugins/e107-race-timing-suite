<?php
/*
 * e107 website system
 *
 * racereg plugin - per-track registration config READ API (issue #30).
 *
 * Lives in `racereg` (its only consumer) and reads the `race` / `race_price`
 * tables - owned by `racetrack` - directly. This is the correct direction:
 * racereg already declares a <dependency> on racetrack, so reading racetrack's
 * tables from here is sound. racetrack itself never calls this API.
 *
 * Single, documented read point for the registration config a `racereg`
 * consumer (#24 sign-up, #26 admin) needs from `race`:
 *   - the registration flags + capacity for a track, and
 *   - the date-tiered price resolved for a given timestamp.
 *
 * Native e107 db class only; ids are cast to int before use and the result is a
 * plain, typed array - no SQL is exposed to callers.
 *
 * USAGE (consumer side):
 *   require_once(e_PLUGIN . 'racereg/includes/race_registration.php');
 *   $cfg   = race_registration::getTrackConfig($trackId);          // flags + capacity
 *   $price = race_registration::resolvePrice($trackId, $when);     // date-tiered fee
 *   $all   = race_registration::getTrackRegistration($trackId, $when); // both
 *   $open  = race_registration::getOpenTracks();                   // id => name
 *
 * SCHEMA (racetrack_sql.php):
 *   race.race_capacity INT, race.race_unlimited_capacity TINYINT,
 *   race.race_requires_approval TINYINT, race.race_registration_closed TINYINT.
 *   race_price(race_price_id PK, race_price_race -> race.race_id [indexed],
 *              race_price_value DECIMAL(10,2), race_price_from INT timestamp).
 *
 * COLUMN NAMING: the storage keeps the race_ / race_price_ prefixes so the
 * already-built racereg #24 read sites keep working verbatim (no rework). This
 * API deliberately returns clean, prefix-free keys (capacity, unlimited_capacity,
 * requires_approval, registration_closed, price) so new consumers (#26) depend on
 * the API shape, not the column names.
 */

if (!defined('e107_INIT')) { exit; }

class race_registration
{
	/** Tracks table (without site prefix). */
	const TABLE_TRACK = 'race';

	/** Date-tiered price child table (1:N track -> prices). */
	const TABLE_PRICE = 'race_price';

	/**
	 * Registration config (flags + capacity) for one track.
	 *
	 * @param int $trackId
	 * @return array|false  false if the track does not exist, otherwise:
	 *   array(
	 *     'track_id'             => int,
	 *     'name'                 => string,   // raw race_name (caller escapes on output)
	 *     'capacity'             => int,
	 *     'unlimited_capacity'   => int (0|1),
	 *     'requires_approval'    => int (0|1),
	 *     'registration_closed'  => int (0|1),
	 *   )
	 */
	public static function getTrackConfig($trackId)
	{
		$trackId = (int) $trackId;
		if ($trackId < 1)
		{
			return false;
		}

		$row = e107::getDb()->retrieve(self::TABLE_TRACK, '*', 'race_id = ' . $trackId);
		if (empty($row))
		{
			return false;
		}

		return array(
			'track_id'            => (int) $row['race_id'],
			'name'                => isset($row['race_name']) ? $row['race_name'] : '',
			'capacity'            => (int) $row['race_capacity'],
			'unlimited_capacity'  => (int) $row['race_unlimited_capacity'],
			'requires_approval'   => (int) $row['race_requires_approval'],
			'registration_closed' => (int) $row['race_registration_closed'],
		);
	}

	/**
	 * Resolve the applicable fee for a track at a given moment: the price tier
	 * with the greatest race_price_from <= $when. Returns 0.00 when the track has
	 * no tier effective at $when.
	 *
	 * @param int      $trackId
	 * @param int|null $when Unix timestamp; defaults to now()
	 * @return float
	 */
	public static function resolvePrice($trackId, $when = null)
	{
		$trackId = (int) $trackId;
		$when    = ($when === null) ? time() : (int) $when;
		if ($trackId < 1)
		{
			return 0.00;
		}

		// Multi-row retrieve (LIMIT 1) rather than single-mode: a track with no
		// tier then returns [] instead of tripping array_shift(false) in the db
		// class on PHP 8.
		$rows = e107::getDb()->retrieve(
			self::TABLE_PRICE,
			'race_price_value',
			'race_price_race = ' . $trackId . ' AND race_price_from <= ' . $when
			. ' ORDER BY race_price_from DESC LIMIT 1',
			true
		);

		if (empty($rows) || !is_array($rows))
		{
			return 0.00;
		}

		$first = reset($rows);

		return isset($first['race_price_value']) ? (float) $first['race_price_value'] : 0.00;
	}

	/**
	 * Convenience: registration config + the price resolved for $when in one call.
	 *
	 * @param int      $trackId
	 * @param int|null $when Unix timestamp; defaults to now()
	 * @return array|false getTrackConfig() plus 'price' => float, or false.
	 */
	public static function getTrackRegistration($trackId, $when = null)
	{
		$cfg = self::getTrackConfig($trackId);
		if ($cfg === false)
		{
			return false;
		}

		$cfg['price'] = self::resolvePrice($trackId, $when);

		return $cfg;
	}

	/**
	 * All tracks currently open for registration (registration_closed = 0).
	 *
	 * @return array id => name (name raw; caller escapes on output)
	 */
	public static function getOpenTracks()
	{
		$out  = array();
		$rows = e107::getDb()->retrieve(
			self::TABLE_TRACK,
			'race_id, race_name',
			'race_registration_closed = 0 ORDER BY race_name ASC',
			true
		);

		if (is_array($rows))
		{
			foreach ($rows as $row)
			{
				$out[(int) $row['race_id']] = $row['race_name'];
			}
		}

		return $out;
	}

	/**
	 * Price tiers for a track (newest first), for admin display / verification.
	 *
	 * @param int $trackId
	 * @return array list of array('value' => float, 'from_date' => int)
	 */
	public static function getPriceTiers($trackId)
	{
		$trackId = (int) $trackId;
		if ($trackId < 1)
		{
			return array();
		}

		$rows = e107::getDb()->retrieve(
			self::TABLE_PRICE,
			'race_price_value, race_price_from',
			'race_price_race = ' . $trackId . ' ORDER BY race_price_from DESC',
			true
		);

		$out = array();
		if (is_array($rows))
		{
			foreach ($rows as $row)
			{
				$out[] = array(
					'value'     => (float) $row['race_price_value'],
					'from_date' => (int) $row['race_price_from'],
				);
			}
		}

		return $out;
	}
}
