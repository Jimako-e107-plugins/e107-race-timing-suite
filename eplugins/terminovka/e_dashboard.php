<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Chatbox plugin - Dashboard (Status)
 *
*/

/**
 *	e107 Chatbox plugin
 *
 *	@package	e107_plugins
 *	@subpackage	chatbox
 *	@version 	$Id$;
 */

if (!defined('e107_INIT')) { exit; }

class terminovka_dashboard // include plugin-folder in the name.
{
	function chart()
	{
		return false;
	}

	function modules_panel()
	{
		return true;
	}	
	
	 
}



