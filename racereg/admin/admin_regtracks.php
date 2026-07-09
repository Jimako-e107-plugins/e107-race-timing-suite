<?php
/*
 * e107 website system
 *
 * racereg plugin - registration-by-track overview (read-only admin page).
 *
 * One row per track with the registration figures the organizer wants at a
 * glance: total / approved / rejected / pending / no-fee / paid registrations,
 * plus the starter count from the racers plugin. NOTHING is edited here, so this
 * is NOT an e_admin_ui (CRUD) mode - it is a controller-only mode in the shared
 * dispatcher: the page body is one HTML table returned by ListPage(), which the
 * dispatcher (admin/admin_menu.php) wraps with the left admin menu and admin
 * chrome (header/footer). Reached via the 'regtracks/list' menu entry.
 *
 * Counting logic stays in the plugin that owns the data:
 *   - registration counts come from racereg_actions::countsByTrack();
 *   - starter counts come from racers (plugin_racers_racers::countOnTrack),
 *     guarded on e107::isInstalled('racers') so racereg has NO hard dependency
 *     on racers - the column degrades to "-" when racers is absent.
 *
 * SECURITY: read-only; admin-gated by the dispatcher (auth.php) and getperms('P');
 * all ids (int)-cast; every count goes through the db class (no string-built WHERE
 * from untrusted input); the track name is output via $tp->toHTML(). No new
 * SQL-injection / XSS surface.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu / perms / LAN
require_once(e_PLUGIN . "racereg/includes/racereg_actions.php"); // countsByTrack()

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


class racereg_regtracks_ui extends e_admin_controller
{
	/**
	 * Registration-by-track table. Returns the body HTML; the dispatcher renders
	 * it inside the admin menu + chrome (same mechanism as
	 * racereg_payment_ui::MarkpaidPage()).
	 */
	public function ListPage()
	{
		$tp  = e107::getParser();
		$sql = e107::getDb();

		// Track list from the race table (racereg already depends on racetrack, so
		// reading `race` directly is fine). Stable sort by race_id.
		$tracks = $sql->retrieve('race', 'race_id, race_name', 'ORDER BY race_id ASC', true);
		if (!is_array($tracks))
		{
			$tracks = array();
		}

		// Per-track registration figures (logic owned by racereg).
		$counts = racereg_actions::countsByTrack();

		// Starters come from racers - guarded, no hard dependency.
		$racersInstalled = e107::isInstalled('racers');
		if ($racersInstalled)
		{
			require_once(e_PLUGIN . 'racers/includes/racers.php');
		}

		$text  = "<table class='table table-striped table-bordered adminlist'>";
		$text .= "<thead><tr>"
			. "<th>" . LAN_RACEREG_RT_ID . "</th>"
			. "<th>" . LAN_RACEREG_RT_NAME . "</th>"
			. "<th class='center'>" . LAN_RACEREG_RT_ALL . "</th>"
			. "<th class='center'>" . LAN_RACEREG_RT_APPROVED . "</th>"
			. "<th class='center'>" . LAN_RACEREG_RT_REJECTED . "</th>"
			. "<th class='center'>" . LAN_RACEREG_RT_PENDING . "</th>"
			. "<th class='center'>" . LAN_RACEREG_RT_NOFEE . "</th>"
			. "<th class='center'>" . LAN_RACEREG_RT_PAID . "</th>"
			. "<th class='center'>" . LAN_RACEREG_RT_STARTERS . "</th>"
			. "</tr></thead><tbody>";

		if (empty($tracks))
		{
			$text .= "<tr><td colspan='9' class='center'><em>" . LAN_RACEREG_RT_NOTRACKS . "</em></td></tr>";
		}
		else
		{
			foreach ($tracks as $track)
			{
				$raceId = (int) $track['race_id'];
				$c      = isset($counts[$raceId]) ? $counts[$raceId] : array(
					'all' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0, 'nofee' => 0, 'paid' => 0,
				);

				// racers not installed -> degrade to a dash (no hard dependency on racers).
				$starters = $racersInstalled
					? (string) plugin_racers_racers::countOnTrack($raceId)
					: '&mdash;';

				$text .= "<tr>"
					. "<td>" . $raceId . "</td>"
					. "<td>" . $tp->toHTML($track['race_name'], false) . "</td>"
					. "<td class='center'>" . (int) $c['all'] . "</td>"
					. "<td class='center'>" . (int) $c['approved'] . "</td>"
					. "<td class='center'>" . (int) $c['rejected'] . "</td>"
					. "<td class='center'>" . (int) $c['pending'] . "</td>"
					. "<td class='center'>" . (int) $c['nofee'] . "</td>"
					. "<td class='center'>" . (int) $c['paid'] . "</td>"
					. "<td class='center'>" . $starters . "</td>"
					. "</tr>";
			}
		}

		$text .= "</tbody></table>";

		// Return the table wrapped by tablerender; the dispatcher renders the
		// result inside the admin menu + chrome.
		return e107::getRender()->tablerender(LAN_RACEREG_RT_TITLE, $text, 'default', true);
	}
}


new racereg_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
