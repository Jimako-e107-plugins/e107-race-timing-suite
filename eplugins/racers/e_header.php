<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - e_header.php
 *
*/

/*
 * Registers css/racers_flags.css (bundled flag font + .racers-flag) on front-end
 * pages only, and only when the display_flag pref is on. Outputs nothing.
 */

if (!defined('e107_INIT')) { exit; }

if (deftrue('USER_AREA') && e107::pref('racers', 'display_flag'))
{
	e107::css('racers', 'css/racers_flags.css');
}
