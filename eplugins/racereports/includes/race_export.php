<?php
/*
 * e107 website system
 *
 * racereports plugin - SHARED export module (CSV + fake-XLS), no external library.
 *
 * Two GENERIC static renderers that stream a report to the browser as a download.
 * They are deliberately NOT tied to any table, report or column set: the caller
 * passes the report's ALREADY-DISPLAYED labels + cells and these functions only
 * stream them. This is the ONE place the byte-level download format lives, so
 * report_stu (first consumer) and any future report (online / point / a finish
 * list) call the same two functions with THEIR displayed columns - no copy-paste,
 * no second column definition. See NOTES.md for the contract.
 *
 * Modelled on the proven eadmin users.php RenderCsv/RenderXls header/output
 * pattern (fputcsv to php://output for CSV; an Excel-readable HTML <table> echoed
 * under an application/vnd.ms-excel header for the "fake XLS").
 *
 * CONTRACT (what the caller must hand in):
 *   $filename : base name WITHOUT extension (the renderer appends .csv / .xls).
 *   $headers  : ordered column labels - the report's OWN display labels.
 *   $rows     : array of rows; each row = ordered array of cell values ALREADY
 *               FORMATTED as displayed (e.g. a time already run through
 *               race_format::formatElapsed with the stu_decimals pref). This
 *               module NEVER computes or reformats a value - it streams the
 *               string it is given, so the file is byte-identical to the page.
 *   $textCols : indices (or keys) of columns that MUST be forced to TEXT so a
 *               spreadsheet does not mangle them (a start number with leading
 *               zeros like "0042" must not become 42; a time string like
 *               "12:17:55.48" must not be reinterpreted as a time/number).
 *
 * Each renderer ENDS the request (exit) right after streaming - no header/footer,
 * no other output. Call them at the TOP of a report page, before HEADERF / any
 * echo.
 *
 * SECURITY: header/disposition values are caller-supplied filenames built from
 * sanitised race data; CSV cells are written verbatim (fputcsv quotes/escapes per
 * RFC 4180), XLS cells are htmlspecialchars()'d so no cell value can break out of
 * the <td> or inject markup.
 */

if (!defined('e107_INIT')) { exit; }


class race_export
{
	/**
	 * Stream the rows as a UTF-8 CSV download and exit.
	 *
	 * Mirrors users.php: discard any buffered chrome, send the no-cache + CSV
	 * disposition headers, PREPEND a UTF-8 BOM so Excel reads Slovak diacritics
	 * correctly, then fputcsv the header row and every data row.
	 *
	 * $textCols is accepted for a uniform signature with xls() but is a no-op for
	 * CSV: the raw string is already correct IN THE FILE (BOM + verbatim value),
	 * and wrapping cells in ="..." to force Excel-text would pollute machine
	 * imports. A future Excel-open-safe toggle is noted in NOTES.md (deferred).
	 *
	 * @param string $filename base name, no extension
	 * @param array  $headers  ordered column labels
	 * @param array  $rows     rows of display-formatted cells
	 * @param array  $textCols ignored for CSV (see above)
	 * @return void  exits
	 */
	public static function csv($filename, array $headers, array $rows, array $textCols = array())
	{
		// Drop any output e107 has buffered so our headers win and no page chrome
		// leaks into the file. Guarded so it is a no-op when no buffer is active.
		if (ob_get_length() !== false)
		{
			ob_clean();
		}

		header('Pragma: no-cache');
		header('Expires: 0');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment;filename=' . $filename . '.csv');

		// UTF-8 BOM: makes Excel open the file as UTF-8 (Slovak diacritics intact).
		echo "\xEF\xBB\xBF";

		$out = fopen('php://output', 'w');
		fputcsv($out, $headers);
		foreach ($rows as $row)
		{
			fputcsv($out, $row);
		}
		fclose($out);

		exit;
	}

	/**
	 * Stream the rows as a "fake XLS" (an Excel-readable HTML table) and exit.
	 *
	 * No external library: an HTML <table> sent under the application/vnd.ms-excel
	 * MIME type opens directly in Excel/LibreOffice. Each $textCols cell is emitted
	 * with mso-number-format:'@' (Excel "Text" format) so a start number keeps its
	 * leading zeros and a time string is not reinterpreted - the legacy users.php
	 * did this for dates; here it is the start number + time columns.
	 *
	 * @param string $filename base name, no extension
	 * @param array  $headers  ordered column labels
	 * @param array  $rows     rows of display-formatted cells
	 * @param array  $textCols indices/keys of columns to force to Excel TEXT
	 * @return void  exits
	 */
	public static function xls($filename, array $headers, array $rows, array $textCols = array())
	{
		if (ob_get_length() !== false)
		{
			ob_clean();
		}

		header('Pragma: no-cache');
		header('Expires: 0');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Content-Type: application/vnd.ms-excel; charset=utf-8');
		header('Content-Disposition: attachment;filename=' . $filename . '.xls');

		// Flip to a set keyed by column index/key for O(1) isset() lookups.
		$textCols = array_flip($textCols);

		echo '<meta charset="utf-8">';
		echo '<table border="1">';

		echo '<tr>';
		foreach ($headers as $header)
		{
			echo '<th>' . htmlspecialchars((string) $header, ENT_QUOTES) . '</th>';
		}
		echo '</tr>';

		foreach ($rows as $row)
		{
			echo '<tr>';
			foreach ($row as $col => $value)
			{
				$cell = htmlspecialchars((string) $value, ENT_QUOTES);

				// '@' = force Excel "Text": protects leading-zero start numbers and
				// the formatted time string from numeric/date reinterpretation.
				if (isset($textCols[$col]))
				{
					echo '<td style="mso-number-format:\'\@\'">' . $cell . '</td>';
				}
				else
				{
					echo '<td>' . $cell . '</td>';
				}
			}
			echo '</tr>';
		}

		echo '</table>';

		exit;
	}
}
