<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - e_url.php
 *
*/

/*
 * SEF routes - listed in docs/reference/routes.md and docs/plugins/racereports.md.
 * Route keys are plugin-scoped (e107::url('racereports', <key>, ...)); the aliases are
 * global URL segments. The redirect targets keep the report pages' query parameters
 * (r/c, r/p, p=race_id, trasa/od/do, n).
 */

class racereports_url // plugin-folder + '_url'
{
	function config()
	{
		$config = array();

		// Checkpoint times. race_point has no sef column: {race_point_sef} carries
		// race_point_code.
		$config['point'] = array(
			'alias'    => 'point',
			'regex'    => '^{alias}\/(.*)\/(.*)\/$',
			'sef'      => '{alias}/{race_sef}/{race_point_sef}/',
			'redirect' => '{e_PLUGIN}racereports/report_point.php?r=$1&p=$2',
		);

		// Online results.
		$config['online'] = array(
			'alias'    => 'online',
			'regex'    => '^{alias}\/(.*)\/(.*)\/(.*)$',
			'sef'      => '{alias}/{race_sef}/{race_category_sef}/',
			'redirect' => '{e_PLUGIN}racereports/report_online.php?r=$1&c=$2',
		);

		// Results - SUT. The {race_id} token is the int race id (?p), not a sef.
		$config['stu'] = array(
			'alias'    => 'stu',
			'regex'    => '^{alias}\/(.*)\/$',
			'sef'      => '{alias}/{race_id}/',
			'redirect' => '{e_PLUGIN}racereports/report_stu.php?p=$1',
		);

		// Results - finish. r may be 'overview' (all tracks): the {race_sef} token is
		// free, so 'overview' passes through like any value.
		$config['finish'] = array(
			'alias'    => 'finish',
			'regex'    => '^{alias}\/(.*)\/(.*)\/$',
			'sef'      => '{alias}/{race_sef}/{race_category_sef}/',
			'redirect' => '{e_PLUGIN}racereports/report_finish.php?r=$1&c=$2',
		);

		// Full results. The {race_id} token is the int race id (?p), as for 'stu'.
		$config['aktualne'] = array(
			'alias'    => 'aktualne',
			'regex'    => '^{alias}\/(.*)\/$',
			'sef'      => '{alias}/{race_id}/',
			'redirect' => '{e_PLUGIN}racereports/report_aktualne.php?p=$1',
		);

		// Start list. r may be 'overview' (all tracks), as for 'finish'.
		$config['start'] = array(
			'alias'    => 'start',
			'regex'    => '^{alias}\/(.*)\/(.*)\/$',
			'sef'      => '{alias}/{race_sef}/{race_category_sef}/',
			'redirect' => '{e_PLUGIN}racereports/report_start.php?r=$1&c=$2',
		);

		// Segment (Od-Do): {race_sef}/{race_point_1}/{race_point_2} ->
		// ?trasa=&od=&do=. The point tokens carry race_point_code.
		$config['custom'] = array(
			'alias'    => 'custom',
			'regex'    => '^{alias}(?:\/([^\/]+))?(?:\/([^\/]+))?(?:\/([^\/]+))?\/?$',
			'sef'      => '{alias}/{race_sef}/{race_point_1}/{race_point_2}/',
			'redirect' => '{e_PLUGIN}racereports/report_custom.php?trasa=$1&od=$2&do=$3',
		);
		// Arrivals board. Same parameters as 'point'; r/p may be 'komplet'.
		$config['dobeh'] = array(
			'alias'    => 'dobeh',
			'regex'    => '^{alias}\/(.*)\/(.*)\/$',
			'sef'      => '{alias}/{race_sef}/{race_point_sef}/',
			'redirect' => '{e_PLUGIN}racereports/report_dobeh.php?r=$1&p=$2',
		);

		// Racer progression. {race_number} carries the bib STRING (leading zeros
		// preserved - never int-cast). Singular alias 'racer': the racers plugin
		// uses the plural 'racers'.
		$config['number'] = array(
			'alias'    => 'racer',
			'regex'    => '^{alias}\/(.*)\/$',
			'sef'      => '{alias}/{race_number}/',
			'redirect' => '{e_PLUGIN}racereports/report_number.php?n=$1',
		);

		return $config;
	}
}
