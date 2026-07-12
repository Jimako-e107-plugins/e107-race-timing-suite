<?php
/*
 * e107 website system
 *
 * racereg plugin - notify addon.
 *
 * Auto-discovered by the e107 notify system (getAddonConfig('e_notify')). When
 * an admin assigns a recipient to "New registration submitted" in
 * Admin -> Notify, this addon e-mails that recipient every time a sign-up is
 * stored. Until a recipient is assigned the trigger is a harmless no-op.
 *
 * Only the integer registration_id travels through the event payload; the PII
 * for the message is loaded here, escaped on output, and never pushed through
 * the event. The plugin must be re-scanned (Admin -> Plugins) after this file
 * is added so e107 registers it into the e_notify_list pref.
 */

if (!defined('e107_INIT')) { exit; }

e107::lan('racereg', true, true); // _admin - notify caption + message LAN live in _admin

class racereg_notify extends notify
{
	function config()
	{
		return array(
			array(
				'name'     => LAN_RACEREG_NT_SIGNUP,            // shown in Admin -> Notify
				'function' => 'racereg_registration_submitted', // == the triggered event id
				'category' => '',
			),
		);
	}

	// Runs when 'racereg_registration_submitted' fires AND an admin has assigned a recipient.
	function racereg_registration_submitted($data)
	{
		$tp = e107::getParser();

		// Notify admin "test" button sends a dummy payload.
		if (isset($data['id']) && isset($data['data']))
		{
			$this->send('racereg_registration_submitted', LAN_RACEREG_NT_SIGNUP, 'Notify test: new registration');
			return true;
		}

		$regId = (int) varset($data['registration_id'], 0);
		if ($regId < 1) { return false; }

		$row = e107::getDb()->retrieve('racereg_registration', '*', 'registration_id=' . $regId);
		if (empty($row)) { return false; }

		// Track display name (racetrack is a dependency; table present).
		$track = e107::getDb()->retrieve('race', 'race_name', 'race_id=' . (int) $row['track_id']);
		$track = !empty($track) ? $track : ('#' . (int) $row['track_id']);

		// Absolute admin-detail link (no e_url route for admin pages -> replaceConstants).
		$link = $tp->replaceConstants(e_PLUGIN . 'racereg/admin/admin_config.php', 'full')
		      . '?mode=main&action=edit&id=' . $regId;

		$vars = array(
			'name'   => $tp->toHTML($row['first_name'], false, 'defs') . ' ' . $tp->toHTML($row['last_name'], false, 'defs'),
			'track'  => $tp->toHTML($track, false, 'defs'),
			'vs'     => $tp->toHTML($row['variable_symbol'], false, 'defs'),
			'amount' => number_format((float) $row['amount_due'], 2),
			'link'   => $link,
		);

		$subject = LAN_RACEREG_NT_SIGNUP;
		$message = $tp->lanVars(LAN_RACEREG_NT_SIGNUP_MSG, $vars);

		$this->send('racereg_registration_submitted', $subject, $message);
		return true;
	}
}
