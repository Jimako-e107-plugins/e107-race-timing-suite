<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * terminovka plugin - e_admin.php
 *
*/

/*
 * Adds the "terminovka.sk track ID" field (own Terminovka tab) to the racetrack
 * track edit form without racetrack referencing terminovka (docs/plugins/
 * terminovka.md, "Track ID — on the track form"). The value is stored in the
 * terminovka_track table and read from there on export.
 *
 * The host is matched via $ui->getEventName() === 'race' (set on racetrack_ui;
 * the sibling point/price controllers leave it unset, so they do not collide).
 *
 * Core plumbing relied upon (ehandlers/admin_ui.php):
 *   - injected fields are forced to data = false (nothing written to race);
 *   - the value arrives in POST as x_terminovka_ext_id;
 *   - process() is called from _manageSubmit() after a successful create/edit
 *     only - not on delete, so deleting a track leaves its mapping row;
 *   - load() is called by setAdminAddonModel() for the list view only.
 *     getCreate() does not call it, so the edit form shows the field empty
 *     (the core fix described in /terminovka-NOTES.md is not present).
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

        // The host page loads racetrack's LAN, not terminovka's.
        e107::lan('terminovka', true, true);

        $config['tabs']['terminovka'] = LAN_TERMINOVKA_TAB;

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
     * Persist the posted external track ID into the owned table on create/edit.
     * The 'delete' branch only runs if core calls process() on delete, which
     * _manageSubmit() currently does not.
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

        // Upsert keyed by race_id (UNIQUE); the value is int-cast and typed via
        // _FIELD_TYPES.
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
