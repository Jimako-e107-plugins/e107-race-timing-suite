<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - includes/race_export.php
 *
*/

/*
 * Shared CSV / "fake XLS" download renderers, no external library (user-facing
 * behaviour: docs/plugins/racereports.md, "Export"). Used by report_stu.php,
 * report_finish.php and report_start.php.
 *
 * The renderers are generic: the caller passes its own display labels and cells and
 * they only stream them, so the file matches the page.
 *   $filename : base name WITHOUT extension (.csv / .xls is appended).
 *   $headers  : ordered column labels.
 *   $rows     : rows of cells ALREADY FORMATTED as displayed; nothing is computed or
 *               reformatted here.
 *   $textCols : indices of columns forced to TEXT in the XLS (bib "0042", time
 *               strings) so a spreadsheet does not reinterpret them.
 * Each renderer ends the request (exit), so call it before HEADERF / any output.
 *
 * Header pattern modelled on the core eadmin users.php RenderCsv/RenderXls. CSV cells
 * are written by fputcsv; XLS cells are htmlspecialchars()'d, so no cell can break
 * out of its <td>.
 */

if (!defined('e107_INIT')) { exit; }


class race_export
{
	/**
	 * Stream the rows as a UTF-8 CSV download and exit.
	 *
	 * Discards any buffered output, sends the no-cache + CSV disposition headers,
	 * prepends a UTF-8 BOM (Excel then reads diacritics correctly) and fputcsv()s
	 * the header row and every data row.
	 *
	 * $textCols is accepted for a uniform signature with xls() but is a no-op for
	 * CSV: the value is already verbatim in the file, and wrapping cells in ="..."
	 * would pollute machine imports (see racereports-NOTES.md, "Deferred:
	 * CSV-as-Excel-text toggle").
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
	 * An HTML <table> sent as application/vnd.ms-excel opens directly in
	 * Excel/LibreOffice. Each $textCols cell gets mso-number-format:'@' (Excel
	 * "Text") so a bib keeps its leading zeros and a time string is not
	 * reinterpreted.
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
