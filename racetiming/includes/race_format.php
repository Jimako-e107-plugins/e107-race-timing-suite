<?php
/*
 * e107 website system
 *
 * racetiming plugin - elapsed-duration formatter (PART A, presentation-free).
 *
 * Single canonical formatter for an elapsed race duration. It replaces the
 * legacy timetracker formatter `secondsToTime()`
 * (timetracker/classes/timetracker_class.php:516-535) together with the per-call
 * `substr()` truncation that every legacy consumer applied on top of it
 * (e.g. timetracker_class.php:271 `substr($diff['text'], 0, 10)`).
 *
 * PARITY MANDATE: this reproduces the legacy displayed string BYTE-FOR-BYTE.
 * The legacy chain is `secondsToTime($s)` then `substr(..., 0, 10)`; here that is
 * `formatElapsed($s, 1)` (decimals = 1 -> 10-char cut). The known legacy quirks
 * (rtrim of trailing ms zeros; hours via integer truncation without %24/%60) are
 * deliberately preserved - see racetiming/NOTES.md for the post-parity
 * intentional-fix candidates. This class does NO computation that affects sort
 * order: $decimals is DISPLAY-ONLY.
 *
 * Pure: no DB, no e107 singletons, no superglobals.
 */

class race_format
{
	/**
	 * Format an elapsed duration (seconds, with a fractional part) as a
	 * truncated HH:MM:SS[.fff] string.
	 *
	 * The value is formatted to the full legacy `HH:MM:SS.mmm` representation
	 * and then TRUNCATED (never rounded) to exactly $decimals fractional digits.
	 * Truncation reproduces the legacy `substr()` cut so that, e.g., a stored
	 * millisecond value of .700 displays as ".7" at $decimals = 1 - the live
	 * "tenths" precision of the online/point reports.
	 *
	 * $decimals is a DISPLAY-ONLY parameter. It MUST NOT be used anywhere a sort
	 * key or a computation is derived; rank by the full-precision float from
	 * race_clock::elapsedToPoint(), format with this method only for output.
	 *
	 * Legacy mapping: formatElapsed($s, 1) === substr(secondsToTime($s), 0, 10).
	 *
	 * @param float|int|string $seconds  elapsed seconds (already round(...,3) upstream)
	 * @param int              $decimals displayed fractional digits (default 1 = legacy)
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

		// "HH:MM:SS" = 8 chars; + "." + N fractional digits. With $decimals = 1
		// this is substr(..., 0, 10), the legacy online/point cut.
		$len = 8 + ($decimals > 0 ? 1 + $decimals : 0);

		return substr($full, 0, $len);
	}

	/**
	 * Reproduce the legacy `secondsToTime()` body byte-for-byte
	 * (timetracker_class.php:516-535), producing `HH:MM:SS.mmm` with the legacy
	 * rtrim-of-trailing-zeros quirk and the ".000" branch for whole seconds.
	 *
	 * PHP-8 hardening only: the integer field computations use intdiv()/% on an
	 * explicit int instead of the legacy float-into-%02d, which yields IDENTICAL
	 * output for non-negative durations while avoiding the implicit
	 * float-to-int deprecation notices. (Negative durations remain malformed in
	 * both - see NOTES.md D-11, intentional-fix candidate.)
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
