<?php
/*
 * e107 website system
 *
 * racereports plugin - simple-mode URL (SEF) configuration.
 *
 * Routes for the report pages this plugin adds:
 *   - point  : per-checkpoint standings   (racereports/report_point.php)
 *   - online : online overall standings    (racereports/report_online.php)
 *   - stu    : per-track finishers-only list (racereports/report_stu.php)
 *   - finish : post-race results list       (racereports/report_finish.php)
 *   - start  : start-point standings list   (racereports/report_start.php)
 *   - dobeh  : checkpoint arrivals board     (racereports/report_dobeh.php)
 *
 * These keys are PLUGIN-SCOPED (e107::url('racereports', <key>, ...)), so the
 * names 'online'/'point' do NOT collide with timetracker's own e_url keys.
 *
 * SEF ALIASES, however, ARE global route segments. The 'online' and 'finish'
 * SEF aliases now use the clean segments: timetracker's matching SEF routes are
 * no longer active (its 'online' route is commented out and it declares no
 * 'finish' route), so there is no collision. The 'point' SEF alias is kept
 * DISTINCT here ('point-times') because timetracker still owns the active
 * 'point' SEF alias during the strangler transition; the clean 'point' SEF
 * alias can be claimed once timetracker's route is retired (see NOTES.md). The
 * 'dobeh' SEF alias is likewise kept DISTINCT ('dobeh'): timetracker's
 * 'dobeh' SEF route is STILL ACTIVE (not commented), so the clean 'dobeh' alias
 * would collide - the distinct alias avoids it during the transition, exactly as
 * 'point-times'/'stu-results' do (see NOTES.md).
 *
 * The redirect targets mirror the legacy query contracts so the pages are
 * drop-in readable:
 *   point  -> ?r=<race_sef>&p=<race_point_sef>
 *   online -> ?r=<race_sef>&c=<race_category_sef>
 *   dobeh  -> ?r=<race_sef>&p=<race_point_sef>
 */

class racereports_url // plugin-folder + '_url'
{
	function config()
	{
		$config = array();

		// Per-checkpoint report (clean equivalent of timetracker 'point').
		// SEF alias kept DISTINCT from timetracker's 'point' (see header).
		$config['point'] = array(
			'alias'    => 'point',
			'regex'    => '^{alias}\/(.*)\/(.*)\/$',
			'sef'      => '{alias}/{race_sef}/{race_point_sef}/',
			'redirect' => '{e_PLUGIN}racereports/report_point.php?r=$1&p=$2',
		);

		// Online overall standings (clean equivalent of timetracker 'online').
		// Uses the clean 'online' SEF alias — timetracker's 'online' SEF route is
		// commented out, so there is no collision (see header).
		$config['online'] = array(
			'alias'    => 'online',
			'regex'    => '^{alias}\/(.*)\/(.*)\/(.*)$',
			'sef'      => '{alias}/{race_sef}/{race_category_sef}/',
			'redirect' => '{e_PLUGIN}racereports/report_online.php?r=$1&c=$2',
		);

		// SUT - per-TRACK finishers-only results list (clean equivalent of
		// timetracker/stu.php). Legacy ?p selects a TRACK by race_id
		// (timetracker/stu.php:36; timetracker/e_url.php 'stu' sef
		// `{alias}/{race_id}/`), so this route mirrors that EXACTLY: the
		// {race_id} token is the int race id and report_stu.php reads ?p as a
		// race_id. SEF alias kept DISTINCT from timetracker's still-active 'stu'
		// alias (we use 'stu-results') to avoid a SEF routing collision during the
		// strangler transition; the clean 'stu' SEF alias can be claimed once
		// timetracker's route is retired (see NOTES.md).
		$config['stu'] = array(
			'alias'    => 'stu',
			'regex'    => '^{alias}\/(.*)\/$',
			'sef'      => '{alias}/{race_id}/',
			'redirect' => '{e_PLUGIN}racereports/report_stu.php?p=$1',
		);

		// FINISH - post-race results list (clean equivalent of timetracker's
		// timetracker_finish.php). Same query contract as the online route
		// (?r=<race_sef>&c=<race_category_sef>), where c may be 'komplet' (all
		// categories) and r may be 'overview' (the all-tracks-on-one-page mode; the
		// {race_sef} token is free, so 'overview' passes through like any value).
		// Uses the clean 'finish' SEF alias — timetracker declares no 'finish' SEF
		// route, so there is no collision (see header).
		$config['finish'] = array(
			'alias'    => 'finish',
			'regex'    => '^{alias}\/(.*)\/(.*)\/$',
			'sef'      => '{alias}/{race_sef}/{race_category_sef}/',
			'redirect' => '{e_PLUGIN}racereports/report_finish.php?r=$1&c=$2',
		);

		// AKTUALNE - the FULL per-race results matrix (clean equivalent of
		// timetracker/aktualne.php + timetrackerArchive_class). Legacy ?p selects a
		// RACE by race_id (timetracker/aktualne.php:35), so this route mirrors that
		// EXACTLY: the {race_id} token is the int race id and report_aktualne.php
		// reads ?p as a race_id (same contract as the 'stu' route above).
		//
		// SEF alias kept DISTINCT ('aktualne'): unlike the task's premise,
		// timetracker STILL declares an ACTIVE 'aktualne' SEF alias
		// (timetracker/e_url.php:85-90 -> timetracker/aktualne.php), so claiming the
		// bare 'aktualne' alias here would create an active SEF collision. This
		// follows the SAME strangler pattern as 'stu' (stu-results) / 'point'
		// (point-times): the clean 'aktualne' SEF alias can be claimed once
		// timetracker's route is retired (see NOTES.md). The plugin-scoped KEY stays
		// 'aktualne'.
		$config['aktualne'] = array(
			'alias'    => 'aktualne',
			'regex'    => '^{alias}\/(.*)\/$',
			'sef'      => '{alias}/{race_id}/',
			'redirect' => '{e_PLUGIN}racereports/report_aktualne.php?p=$1',
		);

		// START - start-point standings list (clean equivalent of the legacy
		// timetracker 'category' report, whose OLD URL was /category/<race>/<cat>).
		// Same query contract as the online/finish routes
		// (?r=<race_sef>&c=<race_category_sef>), where c may be 'komplet' (all
		// categories) and r may be 'overview' (the all-tracks-on-one-page mode; the
		// {race_sef} token is free, so 'overview' passes through like any value).
		// Uses a CLEAN racereports 'start' SEF alias: timetracker's own 'start' SEF
		// route is COMMENTED OUT and its still-active 'category' route uses a
		// DIFFERENT ('category') alias, so the clean 'start' alias collides with
		// neither (timetracker's commented routes are intentional traces - not a
		// collision; see header / NOTES.md).
		$config['start'] = array(
			'alias'    => 'start',
			'regex'    => '^{alias}\/(.*)\/(.*)\/$',
			'sef'      => '{alias}/{race_sef}/{race_category_sef}/',
			'redirect' => '{e_PLUGIN}racereports/report_start.php?r=$1&c=$2',
		);

		// CUSTOM - SEGMENT (split) report between TWO arbitrary points of ONE race
		// (clean equivalent of timetracker/timetracker_custom.php). THREE params:
		// {race_sef}/{race_point_1}/{race_point_2} -> report_custom.php
		// ?trasa=$1&od=$2&do=$3. The page reads ?trasa / ?od / ?do (the legacy query
		// contract) and lists each racer's time ELAPSED BETWEEN point od and point do,
		// ranked fastest-first.
		//
		// SEF alias kept DISTINCT ('segment') from timetracker's 'custom' alias: UNLIKE
		// the online/finish/start routes (whose timetracker SEF counterparts are
		// commented out or absent), timetracker's 'custom' SEF route is STILL ACTIVE
		// (timetracker/e_url.php declares an uncommented 'custom' => alias 'custom'),
		// so the clean 'custom' alias would collide during the strangler transition.
		// The same handling as 'point' (point-times) / 'stu' (stu-results): the e_url
		// KEY stays 'custom' (plugin-scoped via e107::url('racereports','custom',…) -
		// no key collision with timetracker's key), only the global SEF ALIAS is made
		// distinct. The clean 'custom' alias can be claimed once timetracker's route is
		// retired (see NOTES.md). race_point has no sef column, so the
		// {race_point_1}/{race_point_2} tokens carry race_point_code (the value
		// report_custom.php resolves od/do by).
		$config['custom'] = array(
			'alias'    => 'custom',
			'regex'    => '^{alias}(?:\/([^\/]+))?(?:\/([^\/]+))?(?:\/([^\/]+))?\/?$',
			'sef'      => '{alias}/{race_sef}/{race_point_1}/{race_point_2}/',
			'redirect' => '{e_PLUGIN}racereports/report_custom.php?trasa=$1&od=$2&do=$3',
		);
		// DOBEH - checkpoint ARRIVALS board (clean equivalent of timetracker's
		// timetracker_dobeh.php). Same query contract as the 'point' route
		// (?r=<race_sef>&p=<race_point_sef>), where r/p may be 'komplet'. SEF alias
		// kept DISTINCT from timetracker's STILL-ACTIVE 'dobeh' alias (we use
		// 'dobeh') to avoid a SEF routing collision during the strangler
		// transition; the clean 'dobeh' SEF alias can be claimed once timetracker's
		// route is retired (see header / NOTES.md).
		$config['dobeh'] = array(
			'alias'    => 'dobeh',
			'regex'    => '^{alias}\/(.*)\/(.*)\/$',
			'sef'      => '{alias}/{race_sef}/{race_point_sef}/',
			'redirect' => '{e_PLUGIN}racereports/report_dobeh.php?r=$1&p=$2',
		);

		// NUMBER - ONE racer's progression through the WHOLE course (start ->
		// checkpoints -> finish), keyed by a single bib. ONE param: {race_number}
		// carries the bib STRING -> report_number.php?n=$1. The page resolves the
		// racer (and therefore the track + its checkpoints) from the bib alone, so
		// there is no race/komplet param.
		//
		// SEF alias is the CLEAN singular 'racer'. timetracker's own 'number' route
		// is plugin-scoped under the DISTINCT plural alias 'racers'
		// (timetracker/e_url.php `$config['number']` -> sef `racers/number/...`), so
		// the singular 'racer' segment collides with NO active route (the racers
		// plugin's 'racers'-aliased routes are also plural). The e_url KEY 'number'
		// is plugin-scoped (e107::url('racereports','number',…)) - no key collision
		// with timetracker's same-named key. The {race_number} token carries the bib
		// STRING (leading zeros preserved - never int-cast).
		$config['number'] = array(
			'alias'    => 'racer',
			'regex'    => '^{alias}\/(.*)\/$',
			'sef'      => '{alias}/{race_number}/',
			'redirect' => '{e_PLUGIN}racereports/report_number.php?n=$1',
		);

		return $config;
	}
}
