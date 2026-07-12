<?php
/*
 * e107 website system
 *
 * racereg plugin - SEF routing (issue #24).
 *
 * The feature owns its own public route. Mirrors the in-repo race/e_url.php
 * pattern: a `<plugin-folder>_url` class with a config() method returning route
 * definitions (alias / regex / sef / redirect). e107::url('racereg', 'signup')
 * builds the public URL; the regex maps the SEF path back to the controller.
 *
 * Public routes:
 *   signup -> the sign-up page (GET = form, POST = process + confirmation).
 *   pay    -> the tokenized public payment page (issue #40); the 32-hex token
 *             rides in the path (racereg/pay/<token> -> SEF /platba/<token>/) and
 *             is forwarded to pay.php as ?t=$1, resolved server-side. The token is
 *             an unguessable capability credential; no PII / id appears in the path.
 */

if (!defined('e107_INIT')) { exit; }

class racereg_url
{
	public function config()
	{
		$config = array();

		$config['signup'] = array(
			'alias'    => 'prihlaska',                          // SK: "application"
			'regex'    => '^{alias}\/?$',                       // /prihlaska or /prihlaska/
			'sef'      => '{alias}/',
			'redirect' => '{e_PLUGIN}racereg/signup.php',
		);

		$config['pay'] = array(
			'alias'    => 'platba',                             // SK: "payment"
			'regex'    => '^{alias}\/([a-f0-9]{32})\/?$',        // /platba/<32-hex token>/
			'sef'      => '{alias}/{token}/',                   // {token} <- data['token']
			'redirect' => '{e_PLUGIN}racereg/pay.php?t=$1',     // $1 -> $_GET['t']
		);

		return $config;
	}
}
