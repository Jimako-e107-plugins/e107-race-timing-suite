<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetiming plugin - includes/race_format.php
 *
*/

/*
 * Elapsed-duration formatter. Pure: no DB, no e107 singletons, no superglobals.
 *
 * The output keeps the legacy quirks on purpose (trailing millisecond zeros
 * trimmed, hours not wrapped, negative durations malformed) - see
 * racetiming-NOTES.md in the repo root, "Intentional-fix candidates".
 * $decimals is display-only and never affects sort order.
 */

class race_format
{
	/**
	 * Format an elapsed duration (seconds, with a fractional part) as
	 * HH:MM:SS[.fff], TRUNCATED (never rounded) to $decimals fractional digits,
	 * e.g. .700 shows as ".7" at $decimals = 1.
	 *
	 * Display only: rank by the full-precision float from
	 * race_clock::elapsedToPoint(), never by this string.
	 *
	 * @param float|int|string $seconds  elapsed seconds (already round(...,3) upstream)
	 * @param int              $decimals displayed fractional digits (default 1)
	 * @return string
	 */
	public static function formatElapsed($seconds, $decimals = 1)
	{
		if (!is_numeric($seconds))
		{
			// Defensive: never assume a numeric value reached us.
			return '';
		}

		$full = self::legacyDuration((float) $seconds);

		$decimals = (int) $decimals;
		if ($decimals < 0)
		{
			$decimals = 0;
		}

		// "HH:MM:SS" = 8 chars; + "." + N fractional digits.
		$len = 8 + ($decimals > 0 ? 1 + $decimals : 0);

		return substr($full, 0, $len);
	}

	/**
	 * HH:MM:SS.mmm with trailing millisecond zeros trimmed and ".000" for whole
	 * seconds. intdiv()/% on an int avoid PHP 8 float-to-int deprecation notices.
	 * Negative durations come out malformed (racetiming-NOTES.md, D-11).
	 *
	 * @param float $militime
	 * @return string
	 */
	private static function legacyDuration($militime)
	{
		$s = (int) floor($militime);

		$miliseconds = ($militime - $s) * 1000;

		$d = sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s, 60) % 60, $s % 60);

		if ($miliseconds > 0)
		{
			$d .= rtrim(sprintf('.%03d', (int) $miliseconds), '0');
		}
		else
		{
			$d .= ".000";
		}

		return $d;
	}
}
