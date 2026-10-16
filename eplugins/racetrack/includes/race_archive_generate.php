<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetrack plugin - includes/race_archive_generate.php
 *
*/

/*
 * Shared archive generate routine, used by both archive triggers: the
 * Archive button on the Track list (admin/admin_config.php) and the Regenerate
 * button on the Archive screen (admin/admin_archive.php). User-facing behaviour:
 * docs/plugins/racetrack.md, "Archive".
 *
 * The results matrix (html + data) comes from racereports'
 * racereports_aktualne_build(), loaded by path together with the racetiming
 * engine (race_clock, race_format) exactly as report_aktualne.php does; both
 * plugins are runtime-guarded, not hard dependencies.
 *
 * SECURITY: the caller (racetrack_archive_trigger() below) checks the e_token;
 * the admin pages check getperms('P'). racetrack_archive_generate() re-asserts
 * ADMIN, int-casts race_id and writes only through e107::getDb(). It echoes
 * nothing and returns a status the trigger turns into a message.
 */

if (!defined('e107_INIT'))
{
	exit;
}

if (!function_exists('racetrack_archive_generate'))
{
	/**
	 * (Re)build and persist the archive row for a race. Overwrites the row
	 * linked to this race if there is one, else inserts a new one.
	 *
	 * @param int $raceId race id (int-cast here defensively)
	 * @return array ['status' => 'created'|'updated'|'unavailable'|'norace'|'fail',
	 *                'name' => race name (for the caller's message)]
	 */
	function racetrack_archive_generate($raceId)
	{
		$raceId = (int) $raceId;

		// Defensive ADMIN re-assert (the caller is the real gate).
		if (!defined('ADMIN') || !ADMIN)
		{
			return array('status' => 'fail', 'name' => '');
		}

		// No racereports, no builder: fail without a fatal error.
		if (!e107::isInstalled('racereports'))
		{
			return array('status' => 'unavailable', 'name' => '');
		}

		$sql = e107::getDb();

		// Snapshot source: race_code -> sef, race_name -> name and desc.
		$race = $sql->retrieve('race', '*', 'race_id = ' . $raceId);
		if (empty($race) || !is_array($race))
		{
			return array('status' => 'norace', 'name' => '');
		}
		$raceName = isset($race['race_name']) ? (string) $race['race_name'] : '';

		// Same set report_aktualne.php loads. All four files are e107_INIT-guarded
		// and define-only, so this never double-loads or echoes.
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

		// One read of race_time (split map + DNF/DSQ sets), then the full matrix.
		$report = new race_report();
		$clock  = new race_clock();
		$clock->build();

		$built = racereports_aktualne_build($raceId, $clock, $report);
		$html  = isset($built['html']) ? (string) $built['html'] : '';
		$data  = isset($built['data']) ? $built['data'] : array();

		// race_archive_sef is copied from race_code; desc repeats the name.
		$row = array(
			'race_archive_race'    => $raceId,
			'race_archive_sef'     => isset($race['race_code']) ? (string) $race['race_code'] : '',
			'race_archive_name'    => $raceName,
			'race_archive_desc'    => $raceName,
			'race_archive_html'    => $html,
			'race_archive_data'    => e107::serialize($data),
			'race_archive_updated' => time(),
		);

		// Overwrite vs insert. race_archive_race is a varchar; a numeric value
		// compares cleanly against the int-cast id.
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
	 * e_token gate shared by both archive buttons (Archive in admin_config.php,
	 * Regenerate in admin_archive.php), then run the generate and add a
	 * localized message. Returns nothing.
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
