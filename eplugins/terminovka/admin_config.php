<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2013 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Terminovka.sk plugin - admin config + logs
 *
 * Two admin modes:
 *   - main:    preferences (API token, URL, activation flag)
 *   - results: list/edit race_result rows (the export log)
*/

require_once("../../class2.php");

if (!getperms('P'))
{
    e107::redirect('admin');
    exit;
}

e107::lan('terminovka', true, true);


/* -------------------------------------------------------------------------
 * Dispatcher - declares two admin modes
 * ------------------------------------------------------------------------- */

class terminovka_adminArea extends e_admin_dispatcher
{
    protected $modes = array(
        'main' => array(
            'controller' => 'terminovka_prefs_ui',
            'path'       => null,
            'ui'         => 'terminovka_prefs_form_ui',
            'uipath'     => null,
        ),
        'results' => array(
            'controller' => 'race_result_ui',
            'path'       => null,
            'ui'         => 'race_result_form_ui',
            'uipath'     => null,
        ),
    );

    protected $adminMenu = array(
        'main/prefs' => array(
            'caption' => LAN_PREFS,
            'perm'    => 'P',
        ),
        'results/list' => array(
            'caption' => LAN_TERMINOVKA_EXPORT_LOGS,
            'perm'    => 'P',
        ),
    );

    protected $adminMenuAliases = array();

    protected $menuTitle = 'Terminovka.sk';

    // The export log is read-only: timetracker owns the race_result table
    // and is the only place it may be edited. Denying these routes hides the
    // create/edit/delete buttons and the batch options in the list view and
    // blocks direct access to those actions.
    protected $access = array(
        'results/create' => e_UC_NOBODY,
        'results/edit'   => e_UC_NOBODY,
        'results/delete' => e_UC_NOBODY,
    );

    /**
     * Append the centralized cross-plugin admin-menu shortcuts. The canonical
     * nav map lives in raceevent/includes/admin_links.php; we pass our own
     * plugin name so terminovka's own entry is excluded. The isInstalled guard
     * keeps terminovka installable as an independent leaf - links simply don't
     * appear when raceevent is absent.
     */
    public function init()
    {
        if (e107::isInstalled('raceevent'))
        {
            require_once(e_PLUGIN . 'raceevent/includes/admin_links.php');
            $this->adminMenu = array_merge(
                $this->adminMenu,
                raceevent_admin_links::get(array('terminovka'))
            );
        }
    }
}


/* -------------------------------------------------------------------------
 * Preferences controller
 * ------------------------------------------------------------------------- */

class terminovka_prefs_ui extends e_admin_ui
{
    protected $pluginTitle = 'Terminovka.sk';
    protected $pluginName  = 'terminovka';

    // Dummy table - we only use this controller for prefs, not for CRUD.
    protected $table = 'race_result';
    protected $pid   = 'race_result_id';

    protected $prefs = array(
        'export_actived' => array(
            'title' => LAN_TERMINOVKA_PREF_ACTIVE,
            'type'  => 'boolean',
            'data'  => 'int',
        ),
        'export_apikey' => array(
            'title'      => LAN_TERMINOVKA_PREF_TOKEN,
            'type'       => 'text',
            'data'       => 'str',
            'writeParms' => array('size' => 'block-level'),
        ),
        'export_url' => array(
            'title'      => LAN_TERMINOVKA_PREF_URL,
            'type'       => 'text',
            'data'       => 'str',
            'writeParms' => array('size' => 'block-level'),
            'help'       => LAN_TERMINOVKA_PREF_URL_HELP,
        ),
        'refresh_interval' => array(
            'title' => LAN_TERMINOVKA_PREF_INTERVAL,
            'type'  => 'number',
            'data'  => 'int',
            'help'  => LAN_TERMINOVKA_PREF_INTERVAL_HELP,
        ),
    );

    public function renderHelp()
    {
        $caption = LAN_HELP;

        $linkBatch = e_PLUGIN_ABS . "terminovka/terminovka.php";
        $linkTest  = e_PLUGIN_ABS . "terminovka/terminovka_test.php?n=test";

        $text  = "<div class='alert alert-info'><b>" . LAN_TERMINOVKA_HELP_BATCH_TITLE . ":</b><br>";
        $text .= LAN_TERMINOVKA_HELP_BATCH_TEXT . "<br>";
        $text .= "<a class='btn btn-sm btn-info' target='_blank' href='" . $linkBatch . "'>" . LAN_TERMINOVKA_HELP_BATCH_BTN . "</a>";
        $text .= "</div>";

        $text .= "<div class='alert alert-success'><b>" . LAN_TERMINOVKA_HELP_TEST_TITLE . ":</b><br>";
        $text .= LAN_TERMINOVKA_HELP_TEST_TEXT . "<br>";
        $text .= "<a class='btn btn-sm btn-success' target='_blank' href='" . $linkTest . "'>" . LAN_TERMINOVKA_HELP_TEST_BTN . "</a>";
        $text .= "</div>";

        $text .= "<div class='alert alert-warning'><b>" . LAN_TERMINOVKA_HELP_INTERVAL_TITLE . ":</b><br>";
        $text .= LAN_TERMINOVKA_HELP_INTERVAL_TEXT;
        $text .= "</div>";

        return array('caption' => $caption, 'text' => $text);
    }
}


class terminovka_prefs_form_ui extends e_admin_form_ui
{
}


/* -------------------------------------------------------------------------
 * race_result list/edit UI
 * (migrated from timetracker/admin/admin_results.php)
 * ------------------------------------------------------------------------- */

class race_result_ui extends e_admin_ui
{
    protected $pluginTitle = 'Terminovka.sk';
    protected $pluginName  = 'terminovka';

    protected $table       = 'race_result';
    protected $pid         = 'race_result_id';
    protected $perPage     = 200;
    // Read-only export log - no batch actions. Editing happens in timetracker.
    protected $batchDelete = false;
    protected $batchExport = false;
    protected $batchCopy   = false;

    protected $listOrder = 'race_result_id DESC';

    // Disallow the write actions at controller level too, so the routes are
    // hard-blocked even if reached directly (the dispatcher already hides the
    // buttons via $access).
    protected $disallow = array('create', 'edit', 'delete', 'copy', 'batch', 'inline');

    protected $fields = array(
        'checkboxes'             => array('title' => '', 'type' => null, 'data' => null, 'width' => '5%', 'thclass' => 'center', 'forced' => 'value', 'class' => 'center', 'toggle' => 'e-multiselect'),
        'race_result_id'         => array('title' => LAN_ID, 'type' => 'number', 'data' => 'int', 'width' => '5%'),
        'race_result_number'     => array('title' => LAN_TERMINOVKA_FIELD_NUMBER, 'type' => 'text', 'data' => 'safestr', 'width' => 'auto', 'filter' => true),
        'race_result_time'       => array('title' => LAN_TERMINOVKA_FIELD_TIME, 'type' => 'text', 'data' => 'safestr', 'width' => 'auto'),
        'race_result_sent'       => array('title' => LAN_TERMINOVKA_FIELD_SENT, 'type' => 'boolean', 'data' => 'int', 'width' => 'auto', 'filter' => true),
        'race_result_log'        => array('title' => LAN_TERMINOVKA_FIELD_LOG, 'type' => 'method', 'data' => 'json', 'width' => 'auto'),
        'race_result_created'    => array('title' => LAN_TERMINOVKA_FIELD_CREATED, 'type' => 'datestamp', 'data' => 'int', 'width' => 'auto'),
        'race_result_updated'    => array('title' => LAN_TERMINOVKA_FIELD_UPDATED, 'type' => 'datestamp', 'data' => 'int', 'width' => 'auto'),
        'race_result_timesent'   => array('title' => LAN_TERMINOVKA_FIELD_TIMESENT, 'type' => 'datestamp', 'data' => 'int', 'width' => 'auto'),
        'options'                => array('title' => LAN_OPTIONS, 'type' => null, 'data' => null, 'width' => '10%', 'thclass' => 'center last', 'class' => 'center last', 'forced' => 'value'),
    );

    protected $fieldpref = array('race_result_number', 'race_result_time', 'race_result_sent', 'race_result_log', 'race_result_timesent');

    protected $prefs = array();

    public function init()
    {
        $this->fields['race_result_log']['readParms']['pre']  = '<pre>';
        $this->fields['race_result_log']['readParms']['post'] = '</pre>';
    }

    public function renderHelp()
    {
        $caption = LAN_HELP;
        $text    = LAN_TERMINOVKA_LOG_HELP;

        return array('caption' => $caption, 'text' => $text);
    }
}


class race_result_form_ui extends e_admin_form_ui
{
    function race_result_log($curVal, $mode)
    {
        if (empty($curVal)) return null;

        $value = e107::unserialize($curVal);

        if ($mode === 'read')
        {
            return empty($value) ? null : print_a($value, true);
        }
    }
}


/* -------------------------------------------------------------------------
 * Bootstrap
 * ------------------------------------------------------------------------- */

new terminovka_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
