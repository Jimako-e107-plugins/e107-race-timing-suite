<?php
/*
 * e107 website system
 *
 * raceevent base plugin - navigation (sitelink) check page.
 *
 * Sitelinks may carry a link_function of the form "plugin::method"; e107
 * dispatches it as {plugin}_sitelink::method() (see e107_handlers/
 * sitelinks_class.php). The plugin is resolved from the part BEFORE "::",
 * never from link_owner. When a feature moves between plugins but the
 * navigation item is not updated, the top-level link still shows yet its
 * dropdown is dead, and nothing warns about it.
 *
 * This page lists every function-driven sitelink, flags the broken ones
 * (missing plugin, missing method, malformed function) and the merely
 * suspicious ones (owner does not match the called plugin), and lets the
 * organizer HIDE a broken link by setting its userclass to "nobody"
 * (e_UC_NOBODY) - a reversible change. It never deletes rows; deletion stays
 * in the core Site Links admin, reachable here via an Edit link.
 *
 * This is a custom controller screen: the default action 'check' is served by
 * the controller method checkPage() (e107's admin action->method convention),
 * with an EMPTY $table/$pid (no DB-table CRUD), exactly like the plugin's
 * prefs / maintenance controllers. Bootstrap mirrors admin_config.php. The
 * existing maintenance (udrzba) code is not touched.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


class raceevent_checklinks_ui extends e_admin_ui
{
	protected $pluginTitle   = LAN_RACEEVENT_PLUGIN;
	protected $pluginName    = 'raceevent';

	// Custom controller screen - no DB table.
	protected $table         = '';
	protected $pid           = '';

	protected $defaultAction = 'check';


	/**
	 * Default screen. Mirrors the maintenance page's udrzbaPage(): process the
	 * POST first (which validates the form token), render any messages, then a
	 * single token-protected form holding the check table and the submit button.
	 *
	 * @return string
	 */
	public function checkPage()
	{
		$tp   = e107::getParser();
		$sql  = e107::getDb();
		$frm  = e107::getForm();
		$mesg = e107::getMessage();

		// Process POST actions first (validates the form token internally).
		$this->processCheckActions($sql, $frm);

		$text  = $mesg->render();

		// Single token-protected form for the whole page.
		$text .= '<form method="post" action="' . e_SELF . '?' . e_QUERY . '">';
		$text .= $frm->token();  // mandatory security token

		$text .= $this->renderSitelinksCheckSection($sql, $frm);

		// Shared submit button at the bottom of the page.
		$text .= '
		<div class="text-center" style="margin: 30px 0;">
			<button class="btn btn-success" type="submit" name="hide_selected"><span>' . LAN_RACEEVENT_CL_EXECUTE . '</span></button>
		</div>';

		$text .= $frm->close();

		return $text;
	}


	/**
	 * Single source of truth for a function-driven sitelink's state. Resolves the
	 * plugin/method strictly from link_function (the part before "::"), checks the
	 * plugin is installed and its e_sitelink.php class exposes the method, and
	 * raises a non-breaking warning when link_owner disagrees with that plugin.
	 *
	 * The foreign e_sitelink.php is only included after isInstalled() and
	 * is_readable() pass; those files are e107_INIT-guarded class definitions. The
	 * method is probed with method_exists() on the class NAME - the class is never
	 * instantiated, avoiding any constructor side effects.
	 *
	 * @param array $row a #links row
	 * @return array array('code'=>string, 'broken'=>bool, 'plugin'=>string, 'method'=>string)
	 */
	public function evaluateSitelink(array $row)
	{
		$func = (string) $row['link_function'];

		if (strpos($func, '::') === false)
		{
			return array('code' => 'malformed', 'broken' => true, 'plugin' => '', 'method' => '');
		}

		list($plugin, $method) = explode('::', $func, 2);

		// Drop any "(params)" tail, as the core dispatcher does.
		if (strpos($method, '(') !== false)
		{
			$method = substr($method, 0, strpos($method, '('));
		}

		$plugin = trim($plugin);
		$method = trim($method);

		if ($plugin === '' || $method === '')
		{
			return array('code' => 'malformed', 'broken' => true, 'plugin' => $plugin, 'method' => $method);
		}

		$file = e_PLUGIN . $plugin . '/e_sitelink.php';

		if (!e107::isInstalled($plugin) || !is_readable($file))
		{
			return array('code' => 'broken_plugin', 'broken' => true, 'plugin' => $plugin, 'method' => $method);
		}

		include_once($file);

		$class = $plugin . '_sitelink';

		if (!class_exists($class))
		{
			return array('code' => 'broken_plugin', 'broken' => true, 'plugin' => $plugin, 'method' => $method);
		}

		if (!method_exists($class, $method)) // do NOT instantiate
		{
			return array('code' => 'broken_method', 'broken' => true, 'plugin' => $plugin, 'method' => $method);
		}

		// Runtime-OK. Owner heuristic: a function link is normally owned by the
		// plugin it calls; a mismatch is suspicious but not broken.
		$owner = (string) $row['link_owner'];

		if ($owner !== '' && $owner !== $plugin)
		{
			return array('code' => 'owner_mismatch', 'broken' => false, 'plugin' => $plugin, 'method' => $method);
		}

		return array('code' => 'ok', 'broken' => false, 'plugin' => $plugin, 'method' => $method);
	}


	/**
	 * Render the table of function-driven sitelinks with their evaluated state and
	 * the per-row action (hide checkbox for broken links, plus an Edit link to the
	 * core Site Links editor). All displayed values go through $tp->toHTML().
	 *
	 * @param object $sql
	 * @param object $frm
	 * @return string
	 */
	private function renderSitelinksCheckSection($sql, $frm)
	{
		$tp = e107::getParser();

		// Function-driven links only. Parameter-free query, no user input.
		$rows = $sql->retrieve(
			"SELECT * FROM #links WHERE link_function != '' ORDER BY link_category, link_order",
			true
		);

		if (empty($rows))
		{
			return "
			<div class='panel panel-default'>
				<div class='panel-heading'>" . LAN_RACEEVENT_CL_HEADING . "</div>
				<div class='panel-body'>" . LAN_RACEEVENT_CL_NONE . "</div>
			</div>";
		}

		$text = "
		<div class='panel panel-default'>
			<div class='panel-heading'>" . LAN_RACEEVENT_CL_HEADING . "</div>
			<div class='panel-body'>
				<table class='table table-striped table-hover'>
					<thead>
						<tr>
							<th>" . LAN_RACEEVENT_CL_COL_LINK . "</th>
							<th>" . LAN_RACEEVENT_CL_COL_FUNCTION . "</th>
							<th>" . LAN_RACEEVENT_CL_COL_PLUGIN . "</th>
							<th>" . LAN_RACEEVENT_CL_COL_METHOD . "</th>
							<th>" . LAN_RACEEVENT_CL_COL_OWNER . "</th>
							<th class='text-center'>" . LAN_RACEEVENT_CL_COL_STATUS . "</th>
							<th class='text-center'>" . LAN_RACEEVENT_CL_COL_ACTION . "</th>
						</tr>
					</thead>
					<tbody>";

		foreach ($rows as $row)
		{
			$state = $this->evaluateSitelink($row);
			$id    = (int) $row['link_id'];

			$status = $this->statusLabel($state['code']);

			// Action cell: a hide checkbox for a broken link that is not already
			// hidden, an "already hidden" note for a broken link at nobody, and
			// always an Edit link to the core Site Links editor.
			$action = '';

			if ($state['broken'])
			{
				if ((int) $row['link_class'] != e_UC_NOBODY)
				{
					$action .= $frm->checkbox("hide_link[" . $id . "]", 1, false, array('id' => "hide_" . $id));
				}
				else
				{
					$action .= '<span class="text-muted"><em>' . LAN_RACEEVENT_CL_ALREADY_HIDDEN . '</em></span>';
				}
				$action .= ' ';
			}

			$editUrl = e_ADMIN_ABS . 'links.php?mode=main&action=edit&id=' . $id;
			$action .= '<a class="btn btn-default btn-xs" href="' . $editUrl . '" target="_blank">'
				. LAN_RACEEVENT_CL_EDIT . '</a>';

			$text .= "
					<tr>
						<td>" . $tp->toHTML($row['link_name'], false) . "</td>
						<td><code>" . $tp->toHTML($row['link_function'], false) . "</code></td>
						<td>" . $tp->toHTML($state['plugin'], false) . "</td>
						<td>" . $tp->toHTML($state['method'], false) . "</td>
						<td>" . $tp->toHTML($row['link_owner'], false) . "</td>
						<td class='text-center'>{$status}</td>
						<td class='text-center'>{$action}</td>
					</tr>";
		}

		$text .= "
					</tbody>
				</table>
			</div>
		</div>";

		return $text;
	}


	/**
	 * Map an evaluateSitelink() code to a coloured Bootstrap label. OK is success,
	 * an owner mismatch is a warning, everything broken is danger.
	 *
	 * @param string $code
	 * @return string
	 */
	private function statusLabel($code)
	{
		switch ($code)
		{
			case 'ok':
				return '<span class="label label-success">' . LAN_RACEEVENT_CL_OK . '</span>';

			case 'owner_mismatch':
				return '<span class="label label-warning">' . LAN_RACEEVENT_CL_OWNER_MISMATCH . '</span>';

			case 'broken_plugin':
				return '<span class="label label-danger">' . LAN_RACEEVENT_CL_BROKEN_PLUGIN . '</span>';

			case 'broken_method':
				return '<span class="label label-danger">' . LAN_RACEEVENT_CL_BROKEN_METHOD . '</span>';

			case 'malformed':
			default:
				return '<span class="label label-danger">' . LAN_RACEEVENT_CL_MALFORMED . '</span>';
		}
	}


	/**
	 * Process the "Hide selected" submission. No-op without a POST. The form token
	 * is validated first; then every checked link is RE-EVALUATED server-side and
	 * hidden (link_class = nobody) only when it is genuinely broken and not already
	 * hidden - defence in depth, so a crafted POST can never hide a healthy link.
	 * Each hide is logged. The links table is a CORE table, so writes target a
	 * single row by (int) link_id only.
	 *
	 * @param object $sql
	 * @param object $frm
	 * @return void
	 */
	private function processCheckActions($sql, $frm)
	{
		if (empty($_POST))
		{
			return;
		}

		$tp   = e107::getParser();
		$mesg = e107::getMessage();

		// Validate the e107 form token before any write.
		$token = $_POST['e-token'] ?? '';
		if (!e107::getSession()->checkFormToken($token))
		{
			$mesg->addError(LAN_RACEEVENT_CL_MSG_BAD_TOKEN);
			return;
		}

		if (empty($_POST['hide_link']) || !is_array($_POST['hide_link']))
		{
			return;
		}

		foreach ($_POST['hide_link'] as $rawId => $checked)
		{
			$id = (int) $rawId;
			if ($id <= 0)
			{
				continue;
			}

			$row = $sql->retrieve('links', '*', 'link_id = ' . $id);
			if (empty($row))
			{
				continue;
			}

			// Re-evaluate: only hide a genuinely broken link that is not already
			// at nobody (never hide a healthy link via a crafted POST).
			$state = $this->evaluateSitelink($row);
			if ($state['broken'] !== true || (int) $row['link_class'] == e_UC_NOBODY)
			{
				continue;
			}

			$sql->update('links', "link_class = " . e_UC_NOBODY . " WHERE link_id = " . $id);

			$mesg->addSuccess($tp->lanVars(LAN_RACEEVENT_CL_MSG_HIDDEN, $row['link_name'], true));
			e107::getLog()->add(
				'RACEEVENT_CHECKLINKS',
				"Hid broken sitelink #{$id} ({$row['link_function']}) -> nobody.",
				E_LOG_INFORMATIVE,
				''
			);
		}
	}


	public function renderHelp()
	{
		$caption = LAN_HELP;
		$text    = LAN_RACEEVENT_CHECKLINKS_HELP;

		return array('caption' => $caption, 'text' => $text);
	}
}


class raceevent_checklinks_form_ui extends e_admin_form_ui
{
}


new raceevent_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
