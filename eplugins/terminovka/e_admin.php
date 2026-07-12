<?php
/*
 * e107 website system
 *
 * Terminovka.sk plugin - core e_admin.php addon (issue #34)
 *
 * Injects the "terminovka.sk track ID" field into the racetrack plugin's track
 * edit form WITHOUT racetrack referencing terminovka. The external track ID is
 * owned here: stored in the terminovka_track table, read from there on export.
 *
 * Targeting: the host is matched through $ui->getEventName(), which racetrack_ui
 * sets to 'race' (the event name stays keyed to the unchanged 'race' table, not
 * the plugin folder). The sibling controllers (racetrack_point, racetrack_price)
 * leave their event name unset, so they do not collide.
 *
 * Core plumbing relied upon (verified against the bundled Lite 2.4.x source):
 *   - injected fields get data = false forced by core (nothing is written to
 *     the race table);
 *   - the field arrives in POST as x_terminovka_ext_id;
 *   - process() is called on create/edit (and delete);
 *   - load() populates the list view; the edit form is populated through the
 *     same load() via a small Lite core fix (see NOTES.md).
 */

if (!defined('e107_INIT'))
{
    exit;
}

class terminovka_admin implements e_admin_addon_interface
{
    /**
     * The host event name this addon targets.
     */
    const HOST_EVENT = 'race';

    /**
     * The owned mapping table (race_id <-> terminovka.sk ext_id).
     */
    const TABLE = 'terminovka_track';

    /**
     * Extend the host admin-ui with a dedicated tab + the external-track-ID
     * field, but only on the race track edit form.
     *
     * @param e_admin_ui $ui
     * @return array
     */
    public function config(e_admin_ui $ui)
    {
        $config = array();

        if ($ui->getEventName() !== self::HOST_EVENT)
        {
            return $config;
        }

        // Load own admin LAN so the injected labels/tab are defined on the host
        // (race) admin page, which loads race's LAN, not terminovka's.
        e107::lan('terminovka', true, true);

        // Dedicated terminovka tab on the race track form.
        $config['tabs']['terminovka'] = LAN_TERMINOVKA_TAB;

        // POST arrives as $_POST['x_terminovka_ext_id']; core forces data=false
        // so nothing is written to the race table.
        $config['fields']['ext_id'] = array(
            'title'      => LAN_TERMINOVKA_TRACK_EXTID,
            'type'       => 'number',
            'tab'        => 'terminovka',
            'writeParms' => array('size' => 'small', 'min' => 0),
            'help'       => LAN_TERMINOVKA_TRACK_EXTID_HELP,
            'inline'     => false,
        );

        return $config;
    }

    /**
     * Return stored values for the currently viewed list/edit page.
     *
     * @param string $event host event name.
     * @param string $ids   comma-separated list of race_id values (from core).
     * @return array keyed by race_id, each value array('ext_id' => int).
     */
    public function load($event, $ids)
    {
        $ret = array();

        if ($event !== self::HOST_EVENT || $ids === '' || $ids === null)
        {
            return $ret;
        }

        // $ids comes from core, but defend anyway: only digits and commas.
        if (!preg_match('/^[0-9]+(,[0-9]+)*$/', (string) $ids))
        {
            return $ret;
        }

        $rows = e107::getDb()->retrieve(
            self::TABLE,
            'race_id, ext_id',
            'race_id IN (' . $ids . ')',
            true
        );

        foreach ((array) $rows as $row)
        {
            $ret[(int) $row['race_id']] = array('ext_id' => (int) $row['ext_id']);
        }

        return $ret;
    }

    /**
     * Persist the posted external track ID into the owned table on create/edit
     * and remove the mapping on delete.
     *
     * @param e_admin_ui $ui
     * @param int        $id race_id of the saved/deleted track.
     */
    public function process(e_admin_ui $ui, $id = 0)
    {
        if ($ui->getEventName() !== self::HOST_EVENT)
        {
            return;
        }

        $raceId = (int) $id;
        if ($raceId < 1)
        {
            return;
        }

        $action = $ui->getAction();
        $db     = e107::getDb();

        if ($action === 'delete')
        {
            $db->delete(self::TABLE, 'race_id=' . $raceId);
            return;
        }

        if ($action !== 'create' && $action !== 'edit')
        {
            return;
        }

        $ext = (int) $ui->getPosted('x_terminovka_ext_id', 0);

        // Upsert keyed by race_id (UNIQUE). Parameterized data arrays only,
        // no string concatenation of the value.
        $exists = $db->retrieve(self::TABLE, 'terminovka_track_id', 'race_id=' . $raceId);

        if ($exists)
        {
            $db->update(self::TABLE, array(
                'ext_id'       => $ext,
                'WHERE'        => 'race_id=' . $raceId,
                '_FIELD_TYPES' => array('ext_id' => 'int'),
            ));
        }
        else
        {
            $db->insert(self::TABLE, array(
                'terminovka_track_id' => 0,
                'race_id'             => $raceId,
                'ext_id'              => $ext,
                '_FIELD_TYPES'        => array(
                    'terminovka_track_id' => 'int',
                    'race_id'             => 'int',
                    'ext_id'              => 'int',
                ),
            ));
        }
    }
}
