<?php
/*
 * e107 website system
 *
 * racereg plugin - event addon.
 *
 * Auto-discovered by the e107 event system (getAddonConfig('e_event')) and
 * registered into the e_event_list pref when the plugin is scanned. Every
 * racereg lifecycle event has a registered handler here so a trigger is never a
 * dead no-op. The handler bodies are PLACEHOLDERS for now - they only log that
 * the hook fired (E_LOG_INFORMATIVE, no PII). The real logic is decided later
 * and filled in here; editing this file's config()/methods needs NO re-scan
 * (only ADDING the addon file the first time does).
 */

if (!defined('e107_INIT')) { exit; }

class racereg_event
{
	function config()
	{
		return array(
			array('name' => 'racereg_registration_submitted', 'function' => 'onSubmitted'),
			array('name' => 'racereg_registration_approved',  'function' => 'onApproved'),
			array('name' => 'racereg_registration_rejected',  'function' => 'onRejected'),
			array('name' => 'racereg_substitute_promoted',    'function' => 'onPromoted'),
		);
	}

	function onSubmitted($data) { $this->placeholder('racereg_registration_submitted', $data); }
	function onApproved($data)  { $this->placeholder('racereg_registration_approved',  $data); }
	function onRejected($data)  { $this->placeholder('racereg_registration_rejected',  $data); }
	function onPromoted($data)  { $this->placeholder('racereg_substitute_promoted',    $data); }

	// Placeholder: wired + functional, logic TBD. Logs the hook firing, no PII.
	private function placeholder($event, $data)
	{
		$regId = (int) varset($data['registration_id'], 0);
		e107::getLog()->add(
			'RACEREG_EVENT',
			$event . ' fired (placeholder handler, logic TBD) registration_id=' . $regId,
			E_LOG_INFORMATIVE,
			''
		);
	}
}
