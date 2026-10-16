<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - includes/racers_name.php
 *
*/

/*
 * Racer display-name builder: the SINGLE place the name is composed
 * (plugin_racers_racers::getRacerName, race_report::getRacerName and
 * aktualne_racer_name are thin wrappers). User-facing behaviour:
 * docs/plugins/racers.md, "Settings"; details: racers-NOTES.md (repo root).
 *
 * No DB queries: reads the 'racers' prefs once per request. Must NOT reuse
 * plugin_racers_racers, whose constructor loads all racers + categories.
 *
 * SECURITY: HTML mode escapes every racer/pref value via toHTML() / toAttribute();
 * the flag size is int-clamped and the stored flag map is re-validated (A-Z only).
 * Plain mode returns UNESCAPED text - the caller must escape it.
 */

if (!defined('e107_INIT'))
{
	exit;
}

class racers_name
{
	/**
	 * Default IOC -> ISO 3166-1 alpha-2 map, used when the flag_map pref is not set
	 * at all (existing installs do not receive new plugin.xml prefs). A saved empty
	 * map stays empty.
	 */
	const DEFAULT_FLAG_MAP = array(
		'AUS' => 'AU',
		'AUT' => 'AT',
		'CZE' => 'CZ',
		'FRA' => 'FR',
		'GER' => 'DE',
		'HUN' => 'HU',
		'IND' => 'IN',
		'NED' => 'NL',
		'POL' => 'PL',
		'RUS' => 'RU',
		'SUI' => 'CH',
		'SVK' => 'SK',
		'UKR' => 'UA',
		'USA' => 'US',
	);

	/** Flag size bounds (px); the flag_size pref is clamped into this range. */
	const FLAG_SIZE_MIN     = 8;
	const FLAG_SIZE_MAX     = 64;
	const FLAG_SIZE_DEFAULT = 16;

	/** @var array|null 'racers' plugin prefs, loaded once per request */
	protected static $prefs = null;

	/** @var array|null validated effective flag map (IOC => ISO), built once */
	protected static $flagMap = null;

	/**
	 * 'racers' plugin prefs, loaded once. A non-array store is treated as empty.
	 *
	 * @return array
	 */
	protected static function prefs()
	{
		if (self::$prefs === null)
		{
			$prefs = e107::pref('racers');
			self::$prefs = is_array($prefs) ? $prefs : array();
		}

		return self::$prefs;
	}

	/**
	 * Normalise an IOC => ISO map: trim + strtoupper both sides, keep only entries
	 * where IOC matches ^[A-Z]{3}$ and ISO matches ^[A-Z]{2}$, sorted by key. Used by
	 * the helper (stored prefs are never trusted) and by the admin save.
	 *
	 * @param mixed $map
	 * @return array IOC => ISO
	 */
	public static function cleanFlagMap($map)
	{
		$clean = array();

		if (!is_array($map))
		{
			return $clean;
		}

		foreach ($map as $ioc => $iso)
		{
			$ioc = strtoupper(trim((string) $ioc));
			$iso = is_scalar($iso) ? strtoupper(trim((string) $iso)) : '';

			if (preg_match('/^[A-Z]{3}$/', $ioc) && preg_match('/^[A-Z]{2}$/', $iso))
			{
				$clean[$ioc] = $iso;
			}
		}

		ksort($clean);

		return $clean;
	}

	/**
	 * Clamp a flag size to FLAG_SIZE_MIN..FLAG_SIZE_MAX.
	 *
	 * @param mixed $size
	 * @return int
	 */
	public static function clampFlagSize($size)
	{
		$size = (int) $size;

		return max(self::FLAG_SIZE_MIN, min(self::FLAG_SIZE_MAX, $size));
	}

	/**
	 * Effective flag map: the class default when the flag_map pref is not set at
	 * all, otherwise the stored map re-validated. Built once per request.
	 *
	 * @return array IOC => ISO
	 */
	protected static function flagMap()
	{
		if (self::$flagMap === null)
		{
			$prefs = self::prefs();
			self::$flagMap = isset($prefs['flag_map'])
				? self::cleanFlagMap($prefs['flag_map'])
				: self::DEFAULT_FLAG_MAP;
		}

		return self::$flagMap;
	}

	/**
	 * Flag for one racer, or '' when the flag is off or the nationality has no
	 * mapping. The emoji is two regional indicator characters built from the ISO
	 * code without mbstring (only A-Z ever reaches the entity builder).
	 *
	 * @param array $racer
	 * @param bool  $plain true = UTF-8 emoji only, false = HTML <span>
	 * @return string
	 */
	protected static function flag(array $racer, $plain)
	{
		$prefs = self::prefs();

		if (empty($prefs['display_flag']))
		{
			return '';
		}

		$ioc = isset($racer['racer_nacionality']) ? strtoupper(trim((string) $racer['racer_nacionality'])) : '';
		$map = self::flagMap();

		if ($ioc === '' || !isset($map[$ioc]))
		{
			return '';
		}

		$iso = $map[$ioc];
		if (!preg_match('/^[A-Z]{2}$/', $iso))
		{
			return '';
		}

		$emoji = '';
		for ($i = 0; $i < 2; $i++)
		{
			$emoji .= '&#x' . dechex(0x1F1E6 + ord($iso[$i]) - 65) . ';';
		}

		if ($plain)
		{
			return html_entity_decode($emoji, ENT_QUOTES, 'UTF-8');
		}

		$size = self::clampFlagSize(isset($prefs['flag_size']) ? $prefs['flag_size'] : self::FLAG_SIZE_DEFAULT);

		return '<span class="racers-flag" style="font-size:' . $size . 'px" title="'
			. e107::getParser()->toAttribute($ioc) . '">' . $emoji . '</span>';
	}

	/**
	 * Build the racer display name.
	 *
	 * Order: [flag before] name (or md5 hash) [flag after] -> bib -> local marker ->
	 * team suffix.
	 *
	 * Options (all optional):
	 *   'team'  bool, default true  - append the team suffix when the display_team pref is on
	 *   'bib'   bool, default false - archive format: the bib number follows the name
	 *   'hash'  bool, default true  - replace the name by md5(surname firstname) when
	 *                                 the name_hash pref is on (hash over the RAW values)
	 *   'plain' bool, default false - return plain text without any HTML
	 *   'flag'  bool, default true  - add the country flag when the display_flag pref
	 *                                 is on and the nationality is in the flag map
	 *
	 * Flag: before or directly after the name (flag_position, default 'after'), one
	 * space on the side facing the name; also shown when name_hash replaces the
	 * name. No flag and no extra space when off or the nationality is not mapped.
	 *
	 * HTML mode (default) returns an HTML-safe string: surname, firstname, bib,
	 * text_local and team all go through $tp->toHTML(); the flag is a
	 * <span class="racers-flag"> with an int font-size and a toAttribute() title.
	 *
	 * Plain mode returns text that is NOT HTML-escaped: the raw surname/firstname,
	 * bib and team plus the tag-stripped local text and the UTF-8 flag emoji. The
	 * caller MUST escape it for its output context (HTML, CSV, ...).
	 *
	 * The bib number stays a string (leading zeros are data) - never cast to int.
	 *
	 * @param array $racer   racer row
	 * @param array $options see above
	 * @return string
	 */
	public static function build(array $racer, array $options = array())
	{
		$tp    = e107::getParser();
		$prefs = self::prefs();

		$withTeam = isset($options['team']) ? (bool) $options['team'] : true;
		$withBib  = isset($options['bib']) ? (bool) $options['bib'] : false;
		$withHash = isset($options['hash']) ? (bool) $options['hash'] : true;
		$plain    = isset($options['plain']) ? (bool) $options['plain'] : false;
		$withFlag = isset($options['flag']) ? (bool) $options['flag'] : true;

		$surname   = isset($racer['racer_surname']) ? (string) $racer['racer_surname'] : '';
		$firstname = isset($racer['racer_firstname']) ? (string) $racer['racer_firstname'] : '';
		$number    = isset($racer['racer_number']) ? (string) $racer['racer_number'] : '';
		$team      = isset($racer['racer_team']) ? (string) $racer['racer_team'] : '';
		$local     = isset($racer['racer_local']) ? $racer['racer_local'] : '';

		$showLocal = !empty($prefs['display_local']) && !empty($prefs['text_local']) && !empty($local);
		$showTeam  = $withTeam && !empty($prefs['display_team']) && !empty($racer['racer_team']);

		// Name (or hash). The hash is over the raw values; md5 output is safe.
		if ($withHash && !empty($prefs['name_hash']))
		{
			$name = md5($surname . ' ' . $firstname);
		}
		elseif ($plain)
		{
			$name = $surname . ' ' . $firstname;
		}
		else
		{
			$name = $tp->toHTML($surname, false, 'TITLE') . ' ' . $tp->toHTML($firstname, false, 'TITLE');
		}

		// Flag: before or directly after the name, before bib / local / team.
		$flag = $withFlag ? self::flag($racer, $plain) : '';
		if ($flag !== '')
		{
			$before = isset($prefs['flag_position']) && $prefs['flag_position'] === 'before';
			$name   = $before ? ($flag . ' ' . $name) : ($name . ' ' . $flag);
		}

		if ($plain)
		{
			if ($withBib)
			{
				$name .= ' (' . $number . ')';
			}

			if ($showLocal)
			{
				// Keep the pref's own spacing (as HTML mode does); skip if only whitespace.
				$localText = strip_tags($tp->toHTML($prefs['text_local'], 'TITLE', true));
				if (trim($localText) !== '')
				{
					$name .= $localText;
				}
			}

			if ($showTeam)
			{
				$name .= ' (' . $team . ')';
			}

			return $name;
		}

		if ($withBib)
		{
			// Exact legacy archive spacing: two spaces before, one after.
			$name .= "  (" . $tp->toHTML($number, false, 'TITLE') . ") ";
		}

		if ($showLocal)
		{
			$name .= $tp->toHTML($prefs['text_local'], 'TITLE', true);
		}

		if ($showTeam)
		{
			$name .= "&nbsp;<small><i>(" . $tp->toHTML($team, 'TITLE', true) . ")</i></small>";
		}

		return $name;
	}
}
