<?php
/*
 * e107 website system
 *
 * racereg plugin - variable-symbol generator (shared helper).
 *
 * Single source of truth for the registration variable symbol, reused by the
 * #22 admin CRUD (admin/admin_config.php) and the #24 front-end sign-up flow so
 * the format and uniqueness guarantee live in ONE place.
 *
 * The variable symbol is a unique NUMERIC STRING (never an INT - leading zeros
 * and the SK/CZ banking domain treat it as a string), <= 10 characters. The
 * column also carries a UNIQUE index (racereg_sql.php), so generation is the
 * first line of defence and the index is the hard guarantee.
 */

if (!defined('e107_INIT')) { exit; }

class racereg_vs
{
	/** Registrations table (without the site prefix). */
	const TABLE = 'racereg_registration';

	/**
	 * Generate a unique numeric variable symbol (9 digits, <= 10 chars).
	 *
	 * Uniqueness is checked through the e107 db class (escaped) against the
	 * registrations table; the value is also UNIQUE-indexed in SQL. The loop is
	 * bounded so a pathological collision streak cannot hang the request.
	 *
	 * @return string the generated, currently-unique variable symbol
	 */
	public static function generate()
	{
		$db = e107::getDb();

		$attempts = 0;
		do
		{
			$vs = (string) mt_rand(100000000, 999999999); // 9 digits, <= 10 chars
			$attempts++;

			$taken = (int) $db->count(self::TABLE, '(*)', "variable_symbol='" . $db->escape($vs) . "'");
		}
		while ($taken > 0 && $attempts < 50);

		return $vs;
	}
}
