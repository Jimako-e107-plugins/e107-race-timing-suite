<?php
/*
 * e107 website system
 *
 * raceevent base plugin - season maintenance page (udrzba).
 *
 * Maintenance is a cross-plugin, season-lifecycle operation: cleaning the
 * previous season's data (results, times, racers, ...) when a new event/season
 * is set up. That belongs in the base plugin, not in timetracker. The page
 * references timing/results tables by name across plugins - acceptable for a
 * base-level season hub.
 *
 * This is a custom controller screen: the default action 'udrzba' is served by
 * the controller method udrzbaPage() (e107's admin action->method convention),
 * with an EMPTY $table/$pid (no DB-table CRUD), exactly like the plugin's prefs
 * controllers (admin/admin_config.php). Bootstrap mirrors admin_config.php.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


class raceevent_maintenance_ui extends e_admin_ui
{
	protected $pluginTitle   = LAN_RACEEVENT_PLUGIN;
	protected $pluginName    = 'raceevent';

	// Custom controller screen - no DB table.
	protected $table         = '';
	protected $pid           = '';

	protected $defaultAction = 'udrzba';


public function udrzbaPage()
{
    $tp   = e107::getParser();
    $sql  = e107::getDb();
    $frm  = e107::getForm();
    $mesg = e107::getMessage();

    // Warn if archives are still linked to a track before a new season.
    $linkedArchives = (int) e107::getDb()->count('race_archive', '(*)', "race_archive_race != 0");

    if ($linkedArchives > 0)
    {
        $mesg->addWarning(
            LAN_TR_UDRZBA_ARCHIVE_WARN_TITLE . "<br>" .
            $tp->lanVars(LAN_TR_UDRZBA_ARCHIVE_WARN_LINKED, $linkedArchives, true) . "<br>" .
            LAN_TR_UDRZBA_ARCHIVE_WARN_ADVICE
        );
    }

    // Process POST actions first (validates the form token internally).
    $this->processMaintenanceActions($sql, $frm);

    $text  = $mesg->render();

    // Single form for all tables.
    $text .= '<form method="post" action="' . e_SELF . '?' . e_QUERY . '">';
    $text .= $frm->token();  // mandatory security token

    $text .= $this->renderActiveTablesSection($sql, $frm);
    $text .= $this->renderLegacyTablesSection($sql, $frm);

    // Shared submit button at the bottom of the page.
    $text .= '
    <div class="text-center" style="margin: 30px 0;">
     <button class="btn btn-success" type="submit" name="execute_selected"><span>' . LAN_TR_UDRZBA_EXECUTE . '</span> </button>

    </div>';

    $text .= $frm->close();

    $text .= $this->renderPluginsCheckSection();

    $link = e_ADMIN_ABS . 'db.php?mode=pref_editor';

    $text .= '
    <div class="text-center" style="margin: 30px 0;">
        <a class="btn btn-info" href="' . $link . '" target="_blank">
            <span>' . LAN_TR_UDRZBA_EDIT_PREFS . '</span>
        </a>
    </div>';

    return $text;
}


private function processMaintenanceActions($sql, $frm)
{
    if (empty($_POST)) {
        return;
    }

    $tp   = e107::getParser();
    $mesg = e107::getMessage();

    // Validate the e107 form token before any clean/DROP. The form renders
    // $frm->token(); reject the request if it is missing or invalid.
    $token = $_POST['e-token'] ?? '';
    if (!e107::getSession()->checkFormToken($token)) {
        $mesg->addError(LAN_TR_UDRZBA_MSG_BAD_TOKEN);
        return;
    }

    // Active tables. Keys come from the getActiveMaintenanceTables() whitelist;
    // the identifier check is kept as defence in depth (folded in from the old
    // dead delete helper).
    $activeTables = $this->getActiveMaintenanceTables();
    foreach ($activeTables as $table => $label) {

        if (isset($_POST['action'][$table]) && $_POST['action'][$table] == "clean") {

            if (!empty($activeTables[$table]['requires_empty_others'])
                && !$this->allOtherActiveTablesEmpty($sql, $table)) {
                $mesg->addError(LAN_TR_UDRZBA_MSG_TRACKS_BLOCKED);
                continue;
            }

            if (!$this->isValidMaintenanceTable($table)) {
                $mesg->addError(LAN_TR_UDRZBA_MSG_INVALID_TABLE);
                continue;
            }

            // Clean by table name, not by plugin dependency. Some targets are
            // owned by optional plugins (e.g. race_tracking belongs to racerfid),
            // so skip silently when the table is absent - a no-op rather than an
            // error when that plugin is not installed.
            if (!$sql->isTable($table)) {
                continue;
            }

            $deleted = e107::getDb()->delete($table, TRUE, true);

            if ($deleted !== false) {
                $mesg->addSuccess($tp->lanVars(LAN_TR_UDRZBA_MSG_CLEANED, array('x' => $table, 'y' => $deleted), true));
                e107::getLog()->add('TimeTracker maintenance', "Cleaned table {$table} ({$deleted} rows).", E_LOG_INFORMATIVE, 'TT_UDRZBA');
            }
        }
    }

    // Legacy tables (whitelist from getLegacyTables()).
    $legacyTables = $this->getLegacyTables();
    foreach ($legacyTables as $table => $label) {

        $action = $_POST['action'][$table] ?? '';
        if ($action !== 'clean' && $action !== 'drop') {
            continue;
        }

        if (!$this->isValidMaintenanceTable($table)) {
            $mesg->addError(LAN_TR_UDRZBA_MSG_INVALID_TABLE);
            continue;
        }

        if (!$sql->isTable($table)) {
            continue;
        }

        if ($action === 'clean') {
            $sql->delete($table, '1=1');
            $mesg->addSuccess($tp->lanVars(LAN_TR_UDRZBA_MSG_LEGACY_CLEANED, $table, true));
            e107::getLog()->add('TimeTracker maintenance', "Cleaned legacy table {$table}.", E_LOG_INFORMATIVE, 'TT_UDRZBA');
        }
        elseif ($action === 'drop') {
            $sql->gen("DROP TABLE IF EXISTS `#{$table}`");
            $mesg->addSuccess($tp->lanVars(LAN_TR_UDRZBA_MSG_LEGACY_DROPPED, $table, true));
            e107::getLog()->add('TimeTracker maintenance', "Dropped legacy table {$table} (DROP).", E_LOG_INFORMATIVE, 'TT_UDRZBA');
        }
    }
}

/**
 * Identifier whitelist for maintenance targets - only our own race* tables
 * (race, racer, race_category, race_result, race_time, race_tracking,
 * race_racer). Folded in from the removed dead delete helper; generalized
 * from the original ^race_[a-z_]+$ so it also accepts the legitimate `race`
 * and `racer` tables. Defence in depth - $table already comes from the
 * getActiveMaintenanceTables()/getLegacyTables() whitelists.
 *
 * @param string $table
 * @return bool
 */
private function isValidMaintenanceTable($table)
{
    return is_string($table) && preg_match('/^race[a-z_]*$/', $table) === 1;
}

private function renderActiveTablesSection($sql, $frm)
{
    $tables = $this->getActiveMaintenanceTables();
    if (empty($tables)) return '';

    $tp = e107::getParser();
    $text = "
    <div class='panel panel-default'>
        <div class='panel-heading'>" . LAN_TR_UDRZBA_ACTIVE_HEADING . "</div>
        <div class='panel-body'>
            <table class='table table-striped table-hover'>
                <thead>
                    <tr>
                        <th class='text-center' style='width:40px'>" . LAN_TR_UDRZBA_CLEAN . "</th>
                        <th>" . LAN_TR_UDRZBA_ITEM . "</th>
                        <th class='text-center'>" . LAN_TR_UDRZBA_COUNT . "</th>
                    </tr>
                </thead>
                <tbody>";

    foreach ($tables as $table => $data)
    {
        $label    = $data['label'];
        $disabled = $data['disabled'] ?? false;
        $action   = $data['action'] ?? 'clean';

        $hint = '';
        if (!empty($data['requires_empty_others'])) {
            $disabled = !$this->allOtherActiveTablesEmpty($sql, $table);
            if ($disabled) {
                $hint = " <small class='text-muted'>(" . LAN_TR_UDRZBA_TRACKS_HINT . ")</small>";
            }
        }

        $count = $sql->isTable($table) ? (int)$sql->count($table) : 0;

        $checkbox = $frm->checkbox(
            "action[{$table}]",           // name = action[race] etc.
            $action,                      // value = action type (here only clean)
            false,                        // checked?
            [
            'id'       => "chk_{$table}",
            'disabled' => $disabled,
            ]
        );

        $text .= "
        <tr>
            <td class='text-center'>{$checkbox}</td>
            <td>" . $tp->toHTML($label, false) . $hint . "</td>
            <td class='text-center'><strong>" . number_format($count) . "</strong></td>
        </tr>";
    }




    $text .= "</tbody></table></div></div>";
    return $text;
}

private function renderLegacyTablesSection($sql, $frm)
{
    $tables = $this->getLegacyTables();
    if (empty($tables)) return '';

    $tp = e107::getParser();
    $text = "
    <div class='panel panel-danger' style='margin-top: 2.5em;'>
        <div class='panel-heading'>" . LAN_TR_UDRZBA_LEGACY_HEADING . "</div>
        <div class='panel-body'>
            <table class='table table-striped table-hover'>
                <thead>
                    <tr>
                        <th class='text-center' style='width:40px'>" . LAN_TR_UDRZBA_CLEAN . "</th>
                        <th class='text-center' style='width:40px'>" . LAN_TR_UDRZBA_DROP . "</th>
                        <th>" . LAN_TR_UDRZBA_TABLE_DESC . "</th>
                        <th class='text-center'>" . LAN_TR_UDRZBA_STATUS . "</th>
                    </tr>
                </thead>
                <tbody>";

    foreach ($tables as $table => $label)
    {
        $isTable = e107::getDb()->isTable($table);

        $cnt = $isTable ? (int)$sql->count($table) : 0;

        if (!$isTable)
        {
            $status = '<span class="label label-success">' . LAN_TR_UDRZBA_NOTEXIST . ' &#10003;</span>';
            $chkClean = $chkDrop = '<em>-</em>';
        }
        else
        {
            $status = $cnt === 0
                ? '<span class="label label-info">' . LAN_TR_UDRZBA_EMPTY . '</span>'
                : '<span class="label label-danger">' . number_format($cnt) . ' ' . LAN_TR_UDRZBA_ROWS . '</span>';

            $chkClean = $frm->checkbox(
                "action[{$table}]",
                "clean",
                false,
                ['id' => "chk_clean_{$table}"]
            );

            $chkDrop = $frm->checkbox(
                "action[{$table}]",
                "drop",
                false,
                ['id' => "chk_drop_{$table}"]
            );
        }

        $text .= "
        <tr>
            <td class='text-center'>{$chkClean}</td>
            <td class='text-center'>{$chkDrop}</td>
            <td>" . $tp->toHTML($label, false) . " <small>({$table})</small></td>
            <td class='text-center'>{$status}</td>
        </tr>";
    }

    $text .= "</tbody></table></div></div>";
    return $text;
}

/**
 * True when every active maintenance table EXCEPT $excludeTable is empty
 * (count 0) or absent. Used to gate clearing of the parent `race` (tracks)
 * table: tracks may only be cleared once all dependent data is already gone.
 */
private function allOtherActiveTablesEmpty($sql, $excludeTable)
{
    foreach ($this->getActiveMaintenanceTables() as $t => $d) {
        if ($t === $excludeTable) continue;
        if ($sql->isTable($t) && (int)$sql->count($t) > 0) {
            return false;
        }
    }
    return true;
}

private function getActiveMaintenanceTables()
{
    return [
        'race' => [
            'label'                 => LAN_TR_TBL_RACES,
            'disabled'              => true,            // default-safe
            'requires_empty_others' => true,           // unlock only when others empty
            'action'                => 'clean',
        ],
        'race_point' => [
            'label'    => LAN_TR_TBL_POINTS,
            'disabled' => false,
            'action'   => 'clean',
        ],
        'race_category' => [
            'label'    => LAN_TR_TBL_CATEGORIES,
            'disabled' => false,
            'action'   => 'clean',
        ],
        'racer' => [
            'label'    => LAN_TR_TBL_RACERS,
            'disabled' => false,
            'action'   => 'clean',
        ],
        'race_result' => [
            'label'    => LAN_TR_TBL_RESULTS,
            'disabled' => false,
            'action'   => 'clean',
        ],
        'race_time' => [
            'label'    => LAN_TR_TBL_TIMES,
            'disabled' => false,
            'action'   => 'clean',
        ],
        'race_tracking' => [
            'label'    => LAN_TR_TBL_READER,
            'disabled' => false,
            'action'   => 'clean',
        ],
        // 'race_archive' => [
        //     'label'    => 'Archived races',
        //     'disabled' => true,           // example of a disabled row
        //     'action'   => 'clean',
        // ],
    ];
}

private function getLegacyTables()
{
    return [
        'race_racer'    => LAN_TR_TBL_OLD_RACERS,

        // 'race_old_xyz' => 'Another old table',
    ];
}

private function renderPluginsCheckSection()
{
    $db   = e107::getDb();
    $tp   = e107::getParser();
    $mesg = e107::getMessage();

    $required = $this->getRequiredPlugins();

    $text = "
    <div class='panel panel-info' style='margin-top: 2.5em;'>
        <div class='panel-heading'>" . LAN_TR_UDRZBA_PLUGINS_HEADING . "</div>
        <div class='panel-body'>
            <table class='table table-striped table-hover'>
                <thead>
                    <tr>
                        <th>" . LAN_TR_UDRZBA_PLUGIN . "</th>
                        <th>" . LAN_TR_UDRZBA_DESC . "</th>
                        <th class='text-center'>" . LAN_TR_UDRZBA_MANDATORY . "</th>
                        <th class='text-center'>" . LAN_TR_UDRZBA_STATUS . "</th>
                        <th class='text-center'>" . LAN_TR_UDRZBA_PREFS . "</th>
                    </tr>
                </thead>
                <tbody>";

    foreach ($required as $folder => $info)
    {
        // Folder presence on the filesystem.
        $folderExists = is_dir(e_PLUGIN . $folder);

        $active = e107::isInstalled($folder);

        // Preference count (e107::pref returns an array or false/null).
        $prefs = e107::pref($folder);
        $prefsCount = is_array($prefs) ? count($prefs) : 0;

        // Status logic + label colour.
        if (!$folderExists) {
            $statusLabel = 'danger';
            $statusText  = LAN_TR_UDRZBA_PLUGIN_MISSING;
        }  elseif (!$active) {
            $statusLabel = 'warning';
            $statusText  = LAN_TR_UDRZBA_PLUGIN_DISABLED;
        } else {
            $statusLabel = 'success';
            $statusText  = LAN_TR_UDRZBA_PLUGIN_ACTIVE;
        }

        $status = '<span class="label label-' . $statusLabel . '">' . $statusText . '</span>';

        $mandatory = !empty($info['mandatory'])
            ? '<span class="label label-danger">' . LAN_TR_UDRZBA_YES . '</span>'
            : '<span class="label label-default">' . LAN_TR_UDRZBA_NO . '</span>';

        $prefsDisplay = $prefsCount > 0 ? $prefsCount : '<em>' . LAN_TR_UDRZBA_NONE . '</em>';

        $text .= "
        <tr>
            <td>
                <strong>" . $tp->toHTML($info['title'] ?? '', false) . "</strong>
                <small>({$folder})</small>
            </td>
            <td>" . $tp->toHTML($info['description'] ?? '', false) . "</td>
            <td class='text-center'>{$mandatory}</td>
            <td class='text-center'>{$status}</td>
            <td class='text-center'>{$prefsDisplay}</td>
        </tr>";
    }

    $text .= "
            </tbody>
        </table>
    </div>";

    return $text;
}

private function getRequiredPlugins()
{
    // Mandatory: timetracker, race, racers. Optional: racerfid,
    // terminovka, and registracia (planned, not yet built - listed so the
    // check shows it as not-installed rather than a missing dependency).
    return [
        'racereports' => [
            'title'       => LAN_TR_PLUG_RACEREPORTS,
            'description' => LAN_TR_PLUG_RACEREPORTS_DESC,
            'mandatory'   => true,
        ],
        'racetrack' => [
            'title'       => LAN_TR_PLUG_RACE,
            'description' => LAN_TR_PLUG_RACE_DESC,
            'mandatory'   => true,
        ],

        'racers' => [
            'title'       => LAN_TR_PLUG_RACERS,
            'description' => LAN_TR_PLUG_RACERS_DESC,
            'mandatory'   => true,
        ],

        'racerfid' => [
            'title'       => LAN_TR_PLUG_RACETRACKING,
            'description' => LAN_TR_PLUG_RACETRACKING_DESC,
            'mandatory'   => false,
        ],

        'terminovka' => [
            'title'       => LAN_TR_PLUG_TERMINOVKA,
            'description' => LAN_TR_PLUG_TERMINOVKA_DESC,
            'mandatory'   => false,
        ],

        'racereg' => [
            'title'       => LAN_TR_PLUG_REGISTRACIA,
            'description' => LAN_TR_PLUG_REGISTRACIA_DESC,
            'mandatory'   => false,
        ],
    ];
}


}


class raceevent_maintenance_form_ui extends e_admin_form_ui
{
}


new raceevent_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
