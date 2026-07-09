<?php
/*
 * e107 website system
 *
 * racetrack plugin - SHARED archive generate routine.
 *
 * ONE routine, called by BOTH archive triggers (they do the SAME thing):
 *   - the "Archivovat" row button in the race list (racetrack_ui over #race);
 *   - the "Pregenerovat" button in the archive admin (race_archive_ui).
 * Given a race_id it (re)builds the FULL per-race results matrix and persists it
 * into #race_archive, OVERWRITING the existing row for that race when one exists
 * (count(#race_archive WHERE race_archive_race = raceId) > 0) or INSERTing a new
 * row otherwise - the exact legacy aktualne.php:115 condition.
 *
 * The matrix HTML + structured data come from racereports' PURE builder
 * racereports_aktualne_build($raceId, $clock, $report) (returns ['html','data']);
 * we load it by path, GUARDED by isInstalled('racereports') so racetrack degrades
 * cleanly when racereports is absent. The racetiming engine (race_clock +
 * race_format) is the time source and is loaded the same way report_aktualne.php
 * loads it before calling the builder.
 *
 * SECURITY: the CALLER is responsible for the ADMIN + e_token gate (both triggers
 * check getperms('P') and e107::getSession()->checkFormToken()); this routine
 * re-asserts ADMIN defensively and never trusts $_GET/$_POST. race_id is int-cast;
 * every payload is written through e107::getDb() (escaped); the snapshot fields
 * are read from the #race row. This routine ECHOES nothing and renders no chrome -
 * it returns a status the caller turns into a localized message.
 *
 * The thin trigger wrapper racetrack_archive_trigger() (the shared e_token gate +
 * localized flash) lives here too, so BOTH archive triggers reach it from ONE
 * place: race_ui's "Archivovat" row button (admin/admin_config.php) and
 * race_archive_ui's "Pregenerovat" button (admin/admin_archive.php) each
 * require_once this helper and call the same wrapper - no duplication.
 */

if (!defined('e107_INIT'))
{
	exit;
}

if (!function_exists('racetrack_archive_generate'))
{
	/**
	 * (Re)build and persist the archive row for a race. Overwrites an existing
	 * row for the race, else inserts a new one (legacy count() condition).
	 *
	 * @param int $raceId race id (int-cast here defensively)
	 * @return array ['status' => 'created'|'updated'|'unavailable'|'norace'|'fail',
	 *                'name' => race name (for the caller's message)]
	 */
	function racetrack_archive_generate($raceId)
	{
		$raceId = (int) $raceId;

		// Defensive ADMIN re-assert (the caller is the real gate). Never write
		// from a non-admin context even if a trigger forgot to check.
		if (!defined('ADMIN') || !ADMIN)
		{
			return array('status' => 'fail', 'name' => '');
		}

		// The matrix builder lives in racereports; without it there is nothing to
		// build from. Fail gracefully (no fatal) so the admin still loads.
		if (!e107::isInstalled('racereports'))
		{
			return array('status' => 'unavailable', 'name' => '');
		}

		$sql = e107::getDb();

		// Snapshot source: the #race row (race_code -> sef, race_name -> name/desc).
		$race = $sql->retrieve('race', '*', 'race_id = ' . $raceId);
		if (empty($race) || !is_array($race))
		{
			return array('status' => 'norace', 'name' => '');
		}
		$raceName = isset($race['race_name']) ? (string) $race['race_name'] : '';

		// Load the racetiming engine + racereports report helper + PURE builder by
		// path - exactly the set report_aktualne.php requires before building. All
		// four files are e107_INIT-guarded and define-only, so this never
		// double-loads or echoes.
		$engineClock  = e_PLUGIN . 'racetiming/includes/race_clock.php';
		$engineFormat = e_PLUGIN . 'racetiming/includes/race_format.php';
		$reportHelper = e_PLUGIN . 'racereports/includes/race_report.php';
		$builder      = e_PLUGIN . 'racereports/includes/aktualne_build.php';

		if (!is_file($engineClock) || !is_file($engineFormat) || !is_file($reportHelper) || !is_file($builder))
		{
			return array('status' => 'unavailable', 'name' => $raceName);
		}

		require_once($engineClock);
		require_once($engineFormat);
		require_once($reportHelper);
		require_once($builder);

		if (!function_exists('racereports_aktualne_build'))
		{
			return array('status' => 'unavailable', 'name' => $raceName);
		}

		// Build the engine once (one read of race_time -> split map + DNF/DSQ sets)
		// and the report helper, then build the FULL matrix (html + data).
		$report = new race_report();
		$clock  = new race_clock();
		$clock->build();

		$built = racereports_aktualne_build($raceId, $clock, $report);
		$html  = isset($built['html']) ? (string) $built['html'] : '';
		$data  = isset($built['data']) ? $built['data'] : array();

		// Persisted row. race_archive_sef SNAPSHOTS race_code; desc mirrors name
		// (legacy quirk kept). Data is serialized like the legacy archive.
		$row = array(
			'race_archive_race'    => $raceId,
			'race_archive_sef'     => isset($race['race_code']) ? (string) $race['race_code'] : '',
			'race_archive_name'    => $raceName,
			'race_archive_desc'    => $raceName,
			'race_archive_html'    => $html,
			'race_archive_data'    => e107::serialize($data),
			'race_archive_updated' => time(),
		);

		// Overwrite vs insert: the EXACT legacy aktualne.php:115 condition. The
		// race id is int-cast; race_archive_race is compared as the stored value
		// (a numeric varchar compares cleanly against the int).
		$exists = (int) $sql->count('race_archive', '(*)', 'race_archive_race = ' . $raceId);

		if ($exists > 0)
		{
			$row['WHERE'] = 'race_archive_race = ' . $raceId;
			$ok = $sql->update('race_archive', $row);
			// e_db::update returns false on error, else the affected-row count
			// (0 when the row was byte-identical - still a success).
			return array('status' => ($ok === false) ? 'fail' : 'updated', 'name' => $raceName);
		}

		$row['race_archive_created'] = time();
		$ok = $sql->insert('race_archive', $row);

		return array('status' => ($ok === false || $ok === 0) ? 'fail' : 'created', 'name' => $raceName);
	}
}

if (!function_exists('racetrack_archive_trigger'))
{
	/**
	 * ADMIN + e_token gate shared by both archive triggers, then run the shared
	 * generate and flash a localized message. Returns nothing - it only adds
	 * messages. Called by race_ui's "Archivovat" row button (admin_config.php) and
	 * race_archive_ui's "Pregenerovat" button (admin_archive.php).
	 *
	 * @param int $raceId race to (re)generate the archive for
	 */
	function racetrack_archive_trigger($raceId)
	{
		$mes = e107::getMessage();

		// e_token: the trigger form rendered $frm->token(); reject otherwise.
		$token = isset($_POST['e-token']) ? $_POST['e-token'] : '';
		if (!e107::getSession()->checkFormToken($token))
		{
			$mes->addError(LAN_ADMIN_ARCHIVE_MSG_BAD_TOKEN);
			return;
		}

		$result = racetrack_archive_generate((int) $raceId);
		$name   = isset($result['name']) ? $result['name'] : '';

		switch ($result['status'])
		{
			case 'created':
				$mes->addSuccess(LAN_ADMIN_ARCHIVE_MSG_CREATED . ($name !== '' ? ': ' . $name : ''));
				break;
			case 'updated':
				$mes->addSuccess(LAN_ADMIN_ARCHIVE_MSG_UPDATED . ($name !== '' ? ': ' . $name : ''));
				break;
			case 'unavailable':
				$mes->addWarning(LAN_ADMIN_ARCHIVE_MSG_NO_RR);
				break;
			case 'norace':
				$mes->addError(LAN_ADMIN_ARCHIVE_MSG_NORACE);
				break;
			default:
				$mes->addError(LAN_ADMIN_ARCHIVE_MSG_FAIL . ($name !== '' ? ': ' . $name : ''));
				break;
		}
	}
}
