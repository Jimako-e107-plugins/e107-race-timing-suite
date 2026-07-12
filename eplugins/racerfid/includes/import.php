<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2017 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * RFID import plugin (folder/id: racerfid) - importer
 *
 * Reads RFID chip-reader records from an external database (raw PDO) and
 * imports them into timetracker's race_time table (local e107 db), mapped via
 * race's race_point table.
 *
*/

if (!defined('e107_INIT')){ exit; }

class plugin_racerfid_import
{

	private $plugPrefs;
	private $dbh;

	private function getPlugPrefs()
	{
		return e107::pref('racerfid');
	}

	/**
	 * Whitelist a SQL identifier (table/column name) before it is
	 * interpolated into a query string. Only [A-Za-z0-9_] is allowed.
	 *
	 * Ports the alt_auth fix: reject invalid identifiers instead of quoting
	 * arbitrary text. No user-supplied VALUE reaches these queries (the
	 * importer reads all rows), so identifier hardening is the relevant part.
	 *
	 * @param string $name
	 * @return bool
	 */
	private function isValidIdentifier($name)
	{
		return is_string($name) && $name !== '' && preg_match('/^[A-Za-z0-9_]+$/', $name) === 1;
	}

	/**
	 * Soft dependency gate. The import reads race_point from the `race` plugin
	 * and writes race_time from the `timetracker` plugin, so both must be
	 * installed. Returns true only when both are present.
	 *
	 * @return bool
	 */
	private function dependenciesMet()
	{
		return e107::isInstalled('racetiming') && e107::isInstalled('racetrack');
	}

	private function connectToDatabase()
	{
		$plugPrefs = $this->getPlugPrefs();
		$dsn = "mysql:host=" . trim($plugPrefs['e107db_server'] ?? '') . ";port=" . trim($plugPrefs['e107db_port'] ?? '') . ";dbname=" . trim($plugPrefs['e107db_database'] ?? '');
		try
		{
			$dbh = new PDO($dsn, $plugPrefs['e107db_username'] ?? '', $plugPrefs['e107db_password'] ?? '');
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return $dbh;
		}
		catch (PDOException $e)
		{
			return 'Connection failed: ' . $e->getMessage();
		}
	}

	private function getTableName($plugPrefs)
	{
		return ($plugPrefs['e107db_prefix'] ?? '') . ($plugPrefs['e107db_table'] ?? '');
	}


	public function testConnection()
	{
		e107::lan('racerfid', true, true);

		$tp        = e107::getParser();
		$plugPrefs = $this->getPlugPrefs();
		$dbh       = $this->connectToDatabase();

		if (!$dbh instanceof PDO)
		{
			echo $tp->toHTML($dbh);
			return;
		}

		$tableName   = $this->getTableName($plugPrefs);
		$fieldnumber = $plugPrefs['e107db_fieldnumber'] ?? 'TagID';
		$fieldname   = $plugPrefs['e107db_fieldname'] ?? 'Meno';

		// Safe configuration summary - never print the username or password.
		echo "<ul>";
		echo "<li>" . LAN_RACETRACKING_SERVER . " " . $tp->toHTML(trim($plugPrefs['e107db_server'] ?? '')) . "</li>";
		echo "<li>" . LAN_RACETRACKING_DATABASE . " " . $tp->toHTML(trim($plugPrefs['e107db_database'] ?? '')) . "</li>";
		echo "<li>" . LAN_RACETRACKING_TABLE . " " . $tp->toHTML($tableName) . "</li>";
		echo "<li>" . LAN_RACETRACKING_FIELDNAME . " " . $tp->toHTML($fieldname) . "</li>";
		echo "<li>" . LAN_RACETRACKING_FIELDNUMBER . " " . $tp->toHTML($fieldnumber) . "</li>";
		echo "</ul>";

		// Whitelist the identifiers that are interpolated into the queries.
		if (!$this->isValidIdentifier($tableName))
		{
			e107::getMessage()->addError(LAN_RACETRACKING_INVALID_IDENTIFIER . ' ' . $tp->toHTML($tableName));
			return;
		}
		if (!$this->isValidIdentifier($fieldnumber))
		{
			e107::getMessage()->addError(LAN_RACETRACKING_INVALID_IDENTIFIER . ' ' . $tp->toHTML($fieldnumber));
			return;
		}

		echo LAN_RACETRACKING_USED_TABLE . " " . $tp->toHTML($tableName) . "<br>";

		// Query to get the total number of records
		$stmtTotal = $dbh->query("SELECT COUNT(*) FROM `$tableName`");
		$totalRecords = $stmtTotal->fetchColumn();
		echo LAN_RACETRACKING_TOTAL_RECORDS . " " . (int) $totalRecords . "<br>";


		// Query to get records with filled number field
		$stmtMeno = $dbh->query("SELECT COUNT(*) FROM `$tableName` WHERE `{$fieldnumber}` IS NOT NULL AND `{$fieldnumber}` != ''");
		$recordsWithMeno = $stmtMeno->fetchColumn();
		echo LAN_RACETRACKING_RECORDS_WITH . " '" . $tp->toHTML($fieldnumber) . "': " . (int) $recordsWithMeno . "<br><hr>";


		// The race_point mapping comes from the `race` plugin - only read it
		// when the dependencies are present.
		if ($this->dependenciesMet())
		{
			$points = e107::getDb()->retrieve('race_point', "*", " ORDER BY race_point_order ", true);

			if (is_array($points))
			{
				foreach ($points as $point)
				{
					$pointName = $tp->toHTML($point['race_point_name'] ?? '');

					if (empty($point['race_point_dbfield']))
					{
						echo LAN_RACETRACKING_POINT . " <b>" . $pointName . "</b> - " . LAN_RACETRACKING_NOT_IMPORTED . " <br><hr>";
						continue;
					}
					$key = $point['race_point_dbfield'];

					if (!$this->isValidIdentifier($key))
					{
						e107::getMessage()->addError(LAN_RACETRACKING_INVALID_IDENTIFIER . ' ' . $tp->toHTML($key));
						continue;
					}

					echo LAN_RACETRACKING_POINT . " <b>" . $pointName . "</b> " . LAN_RACETRACKING_IMPORTED_FROM_FIELD . " " . $tp->toHTML($key) . "<br>";

					$stmtMenoStart = $dbh->query("SELECT COUNT(*) FROM `$tableName` WHERE `{$fieldnumber}` IS NOT NULL AND `{$fieldnumber}` != '' AND `{$key}` IS NOT NULL AND `{$key}` != ''");
					$recordsWithKey = $stmtMenoStart->fetchColumn();

					echo LAN_RACETRACKING_RECORDS_WITH . " '" . $tp->toHTML($fieldnumber) . "' + '" . $tp->toHTML($key) . "': " . (int) $recordsWithKey . " <br><hr>";
				}
			}
		}
		else
		{
			e107::getMessage()->addWarning(LAN_RACETRACKING_DEPS_MISSING);
		}

		echo "<hr>";

		$raceTrackingRecords = $this->fetchAllRecords($dbh, $tableName);

		echo '<table class="table table-bordered table-striped">';
		echo "<tr><th>ID</th><th>TagID</th><th>Meno</th><th>Start</th><th>Ciel</th><th>Proces</th><th>K01</th><th>K02</th><th>K03</th><th>K04</th><th>K05</th><th>K06</th><th>K07</th><th>K08</th></tr>";

		$cols = array('id', 'TagID', 'Meno', 'Start', 'Ciel', 'Proces', 'K01', 'K02', 'K03', 'K04', 'K05', 'K06', 'K07', 'K08');
		foreach ($raceTrackingRecords as $row)
		{
			echo "<tr>";
			foreach ($cols as $col)
			{
				echo "<td>" . $tp->toHTML($row[$col] ?? '') . "</td>";
			}
			echo "</tr>";
		}

		echo "</table>";

	}


	public function importTracking()
	{
		e107::lan('racerfid', true, true);

		// Abort gracefully (admin warning, no fatal) when the plugins that own
		// race_point / race_time are not installed.
		if (!$this->dependenciesMet())
		{
			e107::getMessage()->addWarning(LAN_RACETRACKING_DEPS_MISSING);
			return;
		}

		$plugPrefs = $this->getPlugPrefs();
		$dbh = $this->connectToDatabase();

		if (!$dbh instanceof PDO)
		{
			e107::getMessage()->addError($dbh);
			return;
		}

		$sql = e107::getDb();
		$tableName = ($plugPrefs['e107db_prefix'] ?? '') . "race_tracking";

		if (!$this->isValidIdentifier($tableName))
		{
			e107::getMessage()->addError(LAN_RACETRACKING_INVALID_IDENTIFIER . ' ' . e107::getParser()->toHTML($tableName));
			return;
		}

		$raceTrackingRecords = $this->fetchAllRecords($dbh, $tableName);

		$points = e107::getDb()->retrieve('race_point', "*", true, true);

		if (!is_array($points))
		{
			return;
		}

		foreach($points AS $point) {
			if(empty($point['race_point_dbfield'])) continue;
			$key  = $point['race_point_dbfield'];
			$code = $point['race_point_code'] ?? '';

			// Skip points whose mapped field name is not a safe identifier.
			if (!$this->isValidIdentifier($key))
			{
				e107::getMessage()->addError(LAN_RACETRACKING_INVALID_IDENTIFIER . ' ' . e107::getParser()->toHTML($key));
				continue;
			}

			$this->processRaceTimeRecords($sql, $plugPrefs, $raceTrackingRecords, $code, $key);
		}
	}

	private function fetchAllRecords($dbh, $tableName)
	{
		// Defensive: never interpolate an unchecked identifier, even though
		// every caller validates $tableName first.
		if (!$this->isValidIdentifier($tableName))
		{
			e107::getMessage()->addError(LAN_RACETRACKING_INVALID_IDENTIFIER . ' ' . e107::getParser()->toHTML($tableName));
			return array();
		}

		$stmt = $dbh->query("SELECT * FROM `$tableName`");
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	private function processRaceTimeRecords($sql, $plugPrefs, $raceTrackingRecords, $point, $key)
	{
		$tp = e107::getParser();
		$existingRecords = $sql->retrieve("race_time", "*", "WHERE `race_time_point` = '" . $tp->toDB($point) . "'", true);
		$existingRaceTimeMap = array_column($existingRecords, null, 'race_time_racer_number');
		$insertedCount = 0;

		foreach ($raceTrackingRecords as $race)
		{
			$tagID    = $race['TagID'] ?? '';
			$keyValue = $race[$key] ?? '';

			if (!isset($existingRaceTimeMap[$tagID]) && $keyValue != "" && $keyValue != NULL && $tagID)
			{
				$data = [
					"race_time_racer_number" => $tagID,
					"race_time_point" => $point,
					"race_time_time" => str_replace(',', '.', $keyValue),
					"race_time_created" => time(),
					"race_time_updated" => time()
				];

				if (e_DEBUG)
				{
					print_a($data);
				}
				if ($sql->insert("race_time", $data ))
				{
					$insertedCount++;
				}
			}
		}
		if (e_DEBUG)
		{
			e107::getMessage()->add("Processed " . count($raceTrackingRecords) . " race_tracking records.", E_MESSAGE_SUCCESS);
			e107::getMessage()->add("Existing records " . count($existingRecords) . " race_time_point " . $point .".", E_MESSAGE_SUCCESS);
			e107::getMessage()->add("$insertedCount new records inserted into race_time for $point.", E_MESSAGE_SUCCESS);
		}
	}


}
